#!/usr/bin/env node
/**
 * probe.mjs: die Bewegungsprobe ohne npm-Paket.
 *
 * Nates shoot.mjs ist der vollstaendige Harness (Kontrast, Kontaktbogen,
 * totes Scroll, Kontaktbogen als Bild) und braucht playwright-core. Dieses
 * Skript ist der kleine Bruder fuer den Fall, dass kein Paket installiert
 * werden darf oder soll: es spricht Chrome direkt ueber das DevTools-Protokoll
 * und braucht ausser Node 22 und einem installierten Chrome nichts.
 *
 * Es beantwortet genau eine Frage, aber die richtig: bewegt Scroll wirklich
 * jeden Akt, oder steht die Seite nur gut da.
 *
 *   node probe.mjs --url https://staging.example.de/seite/ [--width 1440] [--height 900]
 *   node probe.mjs --url ... --shot lab/probe.png
 *
 * Was es NICHT kann: Kontrast messen, Kontaktbogen bauen, Videobilder
 * vergleichen. Dafuer ist shoot.mjs da, und dafuer ist playwright-core noetig.
 */
import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { mkdtemp, rm, writeFile, mkdir } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';

const argv = process.argv.slice(2);
const arg = (n, d) => { const i = argv.indexOf(n); return i > -1 && argv[i + 1] ? argv[i + 1] : d; };
const URL_ = arg('--url', '');
const W = parseInt(arg('--width', '1440'), 10);
const H = parseInt(arg('--height', '900'), 10);
const SHOT = arg('--shot', '');
if (!URL_) { console.error('--url fehlt'); process.exit(2); }

const CHROME = [
  process.env.SCROLLCRAFT_CHROME,
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  '/Applications/Chromium.app/Contents/MacOS/Chromium',
  '/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser',
].find(p => p && existsSync(p));
if (!CHROME) { console.error('Kein Chrome gefunden. SCROLLCRAFT_CHROME setzen.'); process.exit(2); }

const profil = await mkdtemp(path.join(tmpdir(), 'sc-probe-'));
const port = 9222 + Math.floor((Date.now() / 1000) % 500);

// --headless=new statt --headless=old: der alte Modus tickt requestAnimationFrame
// nicht zuverlaessig, und ohne rAF laeuft der Motor nicht. Genau daran scheitert
// jede Pruefung in einem ausgeblendeten Browser-Fenster.
const chrome = spawn(CHROME, [
  '--headless=new', `--remote-debugging-port=${port}`, `--user-data-dir=${profil}`,
  `--window-size=${W},${H}`, '--hide-scrollbars', '--no-first-run',
  '--disable-gpu', '--force-device-scale-factor=1', 'about:blank',
], { stdio: ['ignore', 'ignore', 'pipe'] });

// Chrome schreibt beim Beenden noch in sein Profil. Ohne Wartezeit und
// Wiederholungen bricht das Aufraeumen mit ENOTEMPTY ab, und der Prozess endet
// mit einem Fehler, obwohl die Pruefung sauber durchgelaufen ist. Ein Rest im
// temporaeren Verzeichnis ist der kleinere Schaden als ein falsches Rot,
// deshalb wird der Fehler geschluckt.
const aufraeumen = async () => {
  try { chrome.kill(); } catch {}
  await new Promise( r => setTimeout( r, 700 ) );
  try {
    await rm( profil, { recursive: true, force: true, maxRetries: 6, retryDelay: 250 } );
  } catch {}
};
process.on('exit', () => { try { chrome.kill(); } catch {} });

async function warteAufChrome() {
  for (let i = 0; i < 100; i++) {
    try {
      const r = await fetch(`http://127.0.0.1:${port}/json/version`);
      if (r.ok) return (await r.json()).webSocketDebuggerUrl;
    } catch {}
    await new Promise(r => setTimeout(r, 100));
  }
  throw new Error('Chrome antwortet nicht auf dem Debug-Port.');
}

const wsUrl = await warteAufChrome();
const ws = new WebSocket(wsUrl);
await new Promise((res, rej) => { ws.onopen = res; ws.onerror = rej; });

let id = 0;
const offen = new Map();
const ereignisse = [];
ws.onmessage = e => {
  const m = JSON.parse(e.data);
  if (m.id && offen.has(m.id)) {
    const { res, rej } = offen.get(m.id); offen.delete(m.id);
    m.error ? rej(new Error(m.error.message)) : res(m.result);
  } else if (m.method) { ereignisse.push(m); }
};
const senden = (method, params = {}, sessionId) => new Promise((res, rej) => {
  const n = ++id; offen.set(n, { res, rej });
  ws.send(JSON.stringify({ id: n, method, params, sessionId }));
});

// Eigenen Tab aufmachen und daran andocken.
const { targetId } = await senden('Target.createTarget', { url: 'about:blank' });
const { sessionId } = await senden('Target.attachToTarget', { targetId, flatten: true });
const cdp = (m, p) => senden(m, p, sessionId);

await cdp('Page.enable');
await cdp('Runtime.enable');
await cdp('Emulation.setDeviceMetricsOverride', { width: W, height: H, deviceScaleFactor: 1, mobile: false });

const geladen = new Promise(res => {
  const t = setInterval(() => {
    if (ereignisse.some(e => e.method === 'Page.loadEventFired')) { clearInterval(t); res(); }
  }, 50);
  setTimeout(() => { clearInterval(t); res(); }, 20000);
});
await cdp('Page.navigate', { url: URL_ });
await geladen;
await new Promise(r => setTimeout(r, 1500));

async function auswerten(ausdruck) {
  const r = await cdp('Runtime.evaluate', {
    expression: ausdruck, awaitPromise: true, returnByValue: true,
  });
  if (r.exceptionDetails) throw new Error(r.exceptionDetails.exception?.description || 'Fehler in der Seite');
  return r.result.value;
}

const ergebnis = await auswerten(`(async () => {
  const rafs = n => new Promise(r => { let i = 0; const t = () => (++i >= n ? r() : requestAnimationFrame(t)); requestAnimationFrame(t); });
  // Auf den Motor warten statt einmal nachzusehen.
  //
  // Das Startskript haengt an DOMContentLoaded und das Motorskript laeuft mit
  // defer. Auf einer schmalen Ansicht mit viel eingebettetem SVG dauert das
  // spuerbar laenger als auf dem Schreibtisch. Ein einmaliger Blick meldet dann
  // "Motor nicht gemountet", obwohl er eine halbe Sekunde spaeter laeuft.
  //
  // Genau so ist es am 2026-08-26 passiert: mobil rot, und der Fehler lag im
  // Pruefwerkzeug, nicht auf der Seite. Ein Pruefwerkzeug, das falschen Alarm
  // gibt, ist schlimmer als keins, weil man anfaengt ihm zu misstrauen.
  for (let i = 0; i < 60; i++) {
    if (window.ScrollCraft && ScrollCraft.instances.length) break;
    await new Promise(r => setTimeout(r, 100));
  }
  if (!window.ScrollCraft || !ScrollCraft.instances.length) return { fehler: 'Motor nicht gemountet' };
  const akte = [...document.querySelectorAll('[data-sc-act]')];
  const H = innerHeight;
  const zeilen = [];
  for (const a of akte) {
    const top = a.getBoundingClientRect().top + scrollY;
    const hoehe = a.getBoundingClientRect().height;
    const reise = Math.max(hoehe - H, 1);
    const ps = [], cues = [], rail = [], grund = [];
    for (const f of [0.05, 0.35, 0.65, 0.95]) {
      const ziel = a.dataset.scSpan ? top + reise * f : top - H + (hoehe + H) * f;
      scrollTo({ top: Math.round(Math.max(ziel, 0)), behavior: 'instant' });
      await rafs(5);
      ps.push(+(parseFloat(getComputedStyle(a).getPropertyValue('--sc-p')) || 0).toFixed(3));
      const cs = [...a.querySelectorAll('[data-sc-cue]')].map(c => +getComputedStyle(c).opacity);
      cues.push(cs.length ? +Math.max(...cs).toFixed(2) : null);
      const r = a.querySelector('[data-sc-pan]');
      rail.push(r ? Math.round(r.getBoundingClientRect().left) : null);
      grund.push(getComputedStyle(document.body).backgroundColor);
    }
    const spanne = Math.max(...ps) - Math.min(...ps);
    zeilen.push({
      akt: a.dataset.scAct + '/' + (a.dataset.scSpan || 'fluss'),
      p: ps,
      pSpanne: +spanne.toFixed(2),
      totesScroll: spanne < 0.15,
      cueMax: cues,
      cueErreichtVoll: cues.some(c => c !== null && c > 0.98),
      railX: rail,
      railBewegt: rail[0] !== null && Math.abs(rail[0] - rail[3]) > 50,
    });
  }
  scrollTo({ top: 0, behavior: 'instant' });
  return {
    fenster: innerWidth + 'x' + innerHeight,
    bildschirmhoehen: +(document.documentElement.scrollHeight / innerHeight).toFixed(1),
    instanzen: ScrollCraft.instances.length,
    clips: ScrollCraft.instances[0].clips.length,
    akte: zeilen,
  };
})()`);

console.log(JSON.stringify(ergebnis, null, 2));

if (SHOT) {
  await mkdir(path.dirname(path.resolve(SHOT)), { recursive: true });
  const { data } = await cdp('Page.captureScreenshot', { format: 'png' });
  await writeFile(path.resolve(SHOT), Buffer.from(data, 'base64'));
  console.error('Bild: ' + path.resolve(SHOT));
}

await aufraeumen();

// Rueckgabewert: rot, sobald ein Akt totes Scroll meldet.
const tot = (ergebnis.akte || []).filter(a => a.totesScroll);
if (ergebnis.fehler || tot.length) {
  console.error('\nFEHLER: ' + (ergebnis.fehler || tot.map(a => a.akt + ' bewegt sich nicht').join(', ')));
  process.exit(1);
}
console.error('\nAlle Akte bewegen sich.');
process.exit(0);
