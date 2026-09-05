#!/usr/bin/env node
/**
 * zaehler-probe.mjs: laeuft der Zaehler beim Erscheinen wirklich hoch?
 *
 * Seit Motor v0.3.0 gibt es zwei Sorten von `data-sc-count`, und sie sehen im
 * Screenshot identisch aus:
 *
 *   IN einem data-sc-act   haengt am Akt-Fortschritt, laeuft mit dem Mausrad
 *                          vor und zurueck
 *   AUSSERHALB             tickt einmal beim Erscheinen hoch und bleibt stehen
 *
 * Wer das Markup in einen Akt schiebt, bekommt keinen Fehler und keine Warnung.
 * Die Zahl haengt dann stumm am Scroll, und ohne data-sc-count-at heisst ihr
 * Fenster 0 bis 1: sie steht fast die ganze Seite lang auf dem Startwert.
 * Genau das prueft dieses Skript.
 *
 *   node zaehler-probe.mjs --url https://staging.example.de/seite/ [--shot lab/z.png]
 *
 * Ergaenzt probe.mjs, ersetzt es nicht: probe.mjs prueft Akte, dieses hier
 * prueft die Zaehler ausserhalb von Akten. Auf einer Seite ohne solche Zaehler
 * meldet es das und gibt 0 zurueck.
 *
 * Rueckgabewert 1, sobald ein Zaehler seinen Zielwert nicht erreicht oder beim
 * Zurueckscrollen wieder faellt.
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

const profil = await mkdtemp(path.join(tmpdir(), 'sc-zaehler-'));
const port = 9800 + Math.floor((Date.now() / 1000) % 150);

// --headless=new, und ein eigener Browser statt eines eingebetteten Fensters.
// Ein ausgeblendetes Fenster meldet document.visibilityState "hidden", und
// Chromium liefert dann keine IntersectionObserver-Rueckrufe. Beide Mechanismen
// dieser Pruefung haengen daran: data-sc-in und der Zaehler beim Erscheinen.
// Die Seite sieht dort aus, als sei sie kaputt, und ist es nicht.
const chrome = spawn(CHROME, [
  '--headless=new', `--remote-debugging-port=${port}`, `--user-data-dir=${profil}`,
  `--window-size=${W},${H}`, '--hide-scrollbars', '--no-first-run',
  '--disable-gpu', '--force-device-scale-factor=1', 'about:blank',
], { stdio: ['ignore', 'ignore', 'pipe'] });
process.on('exit', () => { try { chrome.kill(); } catch {} });

// Chrome schreibt beim Beenden noch in sein Profil. Ohne Wartezeit bricht das
// Aufraeumen mit ENOTEMPTY ab, und der Lauf endet rot, obwohl er sauber war.
const aufraeumen = async () => {
  try { chrome.kill(); } catch {}
  await new Promise(r => setTimeout(r, 700));
  try { await rm(profil, { recursive: true, force: true, maxRetries: 6, retryDelay: 250 }); } catch {}
};

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

const ws = new WebSocket(await warteAufChrome());
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

const { targetId } = await senden('Target.createTarget', { url: 'about:blank' });
const { sessionId } = await senden('Target.attachToTarget', { targetId, flatten: true });
const cdp = (m, p) => senden(m, p, sessionId);

await cdp('Page.enable');
await cdp('Runtime.enable');
await cdp('Log.enable');
await cdp('Emulation.setDeviceMetricsOverride', { width: W, height: H, deviceScaleFactor: 1, mobile: false });

const geladen = new Promise(res => {
  const t = setInterval(() => {
    if (ereignisse.some(e => e.method === 'Page.loadEventFired')) { clearInterval(t); res(); }
  }, 50);
  setTimeout(() => { clearInterval(t); res(); }, 20000);
});
await cdp('Page.navigate', { url: URL_ });
await geladen;
await new Promise(r => setTimeout(r, 1200));

async function auswerten(ausdruck) {
  const r = await cdp('Runtime.evaluate', { expression: ausdruck, awaitPromise: true, returnByValue: true });
  if (r.exceptionDetails) throw new Error(r.exceptionDetails.exception?.description || 'Fehler in der Seite');
  return r.result.value;
}

const ergebnis = await auswerten(`(async () => {
  const rafs = n => new Promise(r => { let i = 0; const t = () => (++i >= n ? r() : requestAnimationFrame(t)); requestAnimationFrame(t); });
  const schlaf = ms => new Promise(r => setTimeout(r, ms));

  // Auf den Motor warten statt einmal nachzusehen: das Startskript haengt an
  // DOMContentLoaded, das Motorskript laeuft mit defer.
  for (let i = 0; i < 120 && !(window.ScrollCraft && ScrollCraft.instances.length); i++) await schlaf(50);
  if (!window.ScrollCraft || !ScrollCraft.instances.length) {
    // Zwei sehr verschiedene Faelle, die sich hier gleich anfuehlen. Ohne
    // Trennung sucht man den Fehler im Motor, obwohl man nur die falsche
    // Adresse geprueft hat.
    const markup = document.querySelectorAll('[data-sc-act], [data-sc-count], [data-sc-cue], [data-sc-in]').length;
    return markup
      ? { fehler: 'Motor nicht gemountet, obwohl ' + markup + ' Elemente data-sc-Attribute tragen. Skript geladen? Konsole ansehen.' }
      : { keinScrollcraft: true };
  }

  const alle = [...document.querySelectorAll('[data-sc-count]')];
  const imAkt = alle.filter(c => c.closest('[data-sc-act]'));
  const els = alle.filter(c => !c.closest('[data-sc-act]'));
  if (!els.length) {
    return { keineZaehlerAusserhalb: true, zaehlerImAkt: imAkt.length, hinweis: 'Nichts zu pruefen. Zaehler in Akten pruefst du mit probe.mjs.' };
  }

  const lesen = () => els.map(e => e.textContent.trim());
  const roh = s => String(s).replace(/,/g, '');
  const soll = els.map(e => {
    const t = (e.getAttribute('data-sc-count') || '').trim().split(/\\s+/);
    return { start: t[0] || '0', ziel: t[1] || '0' };
  });

  const felder = els.map((e, i) => ({
    ziel: soll[i].ziel,
    ms: e.getAttribute('data-sc-count-ms') || '(Vorgabe 1400)',
    // Die Kommafalle: formatNum() rechnet englisch und setzt ab 10000 von sich
    // aus Kommas. Aus 12500 wird "12,500" statt "12.500".
    kommafalle: Math.abs(parseFloat(roh(soll[i].ziel)) || 0) >= 10000,
  }));

  window.scrollTo({ top: 0, behavior: 'instant' });
  await rafs(3); await schlaf(400);
  const ersterZaehler = els[0];
  const oben = {
    werte: lesen(),
    ersterUnterDerFalz: ersterZaehler.getBoundingClientRect().top > innerHeight,
  };

  // Ziel: den ersten Zaehler mittig ins Bild. behavior instant, weil die
  // Stildatei scroll-behavior smooth setzt und ein animierter Sprung jede
  // Messung danach verfaelscht.
  const zielY = ersterZaehler.getBoundingClientRect().top + scrollY - innerHeight / 2;
  window.scrollTo({ top: zielY, behavior: 'instant' });
  await rafs(2);

  const t0 = performance.now();
  const verlauf = [];
  for (const zeit of [0, 150, 350, 700, 1100, 1700, 2500]) {
    while (performance.now() - t0 < zeit) await rafs(1);
    verlauf.push({ tMs: Math.round(performance.now() - t0), werte: lesen() });
  }
  const endwerte = lesen();

  // Gegenprobe. Ein Zaehler beim Erscheinen bleibt stehen, ein am Akt
  // haengender faellt beim Hochscrollen auf den Startwert zurueck.
  window.scrollTo({ top: 0, behavior: 'instant' });
  await rafs(3); await schlaf(500);
  const wiederOben = lesen();
  window.scrollTo({ top: zielY, behavior: 'instant' });
  await rafs(3); await schlaf(500);
  const wiederUnten = lesen();

  const befunde = els.map((e, i) => ({
    ziel: soll[i].ziel,
    erreicht: roh(endwerte[i]) === roh(soll[i].ziel),
    endwert: endwerte[i],
    bewegteSich: endwerte[i] !== oben.werte[i],
    faelltZurueck: roh(wiederOben[i]) !== roh(endwerte[i]),
    feuertErneut: roh(wiederUnten[i]) !== roh(endwerte[i]),
    kommafalle: felder[i].kommafalle,
    ms: felder[i].ms,
  }));

  return {
    fenster: innerWidth + 'x' + innerHeight,
    zaehlerAusserhalbVonAkten: els.length,
    zaehlerImAkt: imAkt.length,
    scIn: [...document.querySelectorAll('[data-sc-in]')].map(e => ({
      hatScIn: e.classList.contains('sc-in'),
      opacity: getComputedStyle(e).opacity,
    })),
    oben,
    verlauf,
    wiederOben,
    wiederUnten,
    befunde,
  };
})()`);

const konsole = ereignisse
  .filter(e => e.method === 'Log.entryAdded' && ['error', 'warning'].includes(e.params.entry.level))
  .map(e => `${e.params.entry.level}: ${e.params.entry.text}`);
if (konsole.length) ergebnis.konsole = konsole;

console.log(JSON.stringify(ergebnis, null, 2));

if (SHOT) {
  await mkdir(path.dirname(path.resolve(SHOT)), { recursive: true });
  const { data } = await cdp('Page.captureScreenshot', { format: 'png' });
  await writeFile(path.resolve(SHOT), Buffer.from(data, 'base64'));
  console.error('Bild: ' + path.resolve(SHOT));
}

await aufraeumen();

if (ergebnis.keinScrollcraft) {
  console.error('\nDiese Seite nutzt kein Scrollcraft. Kein data-sc-Markup, kein Motor. Adresse pruefen.');
  process.exit(2);
}
if (ergebnis.fehler) {
  console.error('\nFEHLER: ' + ergebnis.fehler);
  process.exit(1);
}
if (ergebnis.keineZaehlerAusserhalb) {
  console.error(`\nKeine Zaehler ausserhalb von Akten. ${ergebnis.zaehlerImAkt} im Akt, die pruefst du mit probe.mjs.`);
  process.exit(0);
}

const klagen = [];
ergebnis.befunde.forEach((b, i) => {
  const n = `Zaehler ${i + 1} (Ziel ${b.ziel})`;
  if (!b.bewegteSich) klagen.push(`${n} hat sich nie bewegt. Liegt er doch in einem Akt, oder ist er beim Laden schon im Bild?`);
  else if (!b.erreicht) klagen.push(`${n} blieb bei ${b.endwert} stehen.`);
  if (b.faelltZurueck) klagen.push(`${n} faellt beim Hochscrollen auf ${ergebnis.wiederOben[i]} zurueck. Das ist das Verhalten im Akt, nicht beim Erscheinen.`);
  if (b.feuertErneut) klagen.push(`${n} laeuft beim Wiederkommen erneut. Er soll einmal feuern.`);
});

const fallen = ergebnis.befunde.filter(b => b.kommafalle);
if (fallen.length) {
  console.error(`\nWARNUNG: ${fallen.length} Zielwert(e) ab 10000. formatNum() rechnet englisch und`);
  console.error('setzt dort Kommas: aus 12500 wird "12,500" statt "12.500". Einheit wechseln.');
}

if (klagen.length) {
  console.error('\nFEHLER:\n  ' + klagen.join('\n  '));
  process.exit(1);
}
console.error(`\n${ergebnis.zaehlerAusserhalbVonAkten} Zaehler ticken beim Erscheinen hoch und bleiben stehen.`);
process.exit(0);
