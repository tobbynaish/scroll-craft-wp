# Prüfen

Eine Scroll-Seite hat keinen einzelnen Zustand. Jede Position ist ein anderes
Bild, und die Fehler liegen zwischen den beiden, die man sich zufällig angesehen
hat. Vier davon sind so gebaut, dass jeder einzelne Screenshot richtig aussieht.

Der Screenshot-Harness und was er misst, steht unverändert in
[verify-handwerk.md](verify-handwerk.md). Hier stehen die fünf Prüfungen, die es
nur in WordPress gibt, und zwei Hostinger-Fallen, ohne die diese Phase falsche
Ergebnisse liefert.

**Reihenfolge:** die fünf Prüfungen zuerst, der Screenshot-Lauf danach. Eine
Bühne, die nicht klebt, produziert einen tadellosen Kontaktbogen.

---

## Stand der Prüfungen auf diesem Port

Gemessen am 2026-08-25 gegen WordPress 7.0.4, PHP 8.3, OllieWP 1.6.1 auf
`staging.tobiasherold.de`, Theme th-swiss.

| Prüfung | Ergebnis |
|---|---|
| Sechs Akte kleben, volle Breite, keine tote Strecke | grün |
| Tokens lösen auf Ollies elf Slugs auf | grün |
| Hell und dunkel, Drift greift in beiden | grün |
| Kinetik an Kind, an Container, kanonisch | grün, siehe MOTOR-PATCHES.md |
| Echter Clip durch encode, Mediathek, Scrub | grün, currentTime steigt monoton |
| **Editor-Rundlauf, metadata.sc** | **grün, 6 rein, 6 raus, Nutzlast gleich** |
| **Echtes Gerät, Scrub am Telefon** | **grün, Clip läuft beim Scrollen mit** |
| Kontrast über echten Bildern | offen, braucht playwright-core |
| Tastatur-Fokusreihenfolge | offen |

Die zwei fett gesetzten Zeilen sind die, die kein Skript beantworten kann.
Beide brauchten einen angemeldeten Menschen und ein echtes Telefon.

**Eine Lehre aus dem Gerätetest:** der Testclip war ein `testsrc2` aus ffmpeg,
also wörtlich ein Testbild. Auf die Frage, ob der Clip läuft, kam sinngemäß
zurück, da sei nur ein Testbild. Das war eine Aussage über den Inhalt, nicht
über die Bewegung, und ich hätte es beinahe als Fehler diagnostiziert.

Für den nächsten Gerätetest deshalb einen Clip nehmen, bei dem stehend und
laufend auf einen Blick zu unterscheiden sind: eine große Zahl, die hochzählt,
oder ein Balken, der wandert. Nicht ein Muster, das im Standbild genauso
aussieht wie in Bewegung.

## Prüfung 1: Überlebt `metadata` den Editor

Die wichtigste Prüfung von allen, weil an ihr die ganze Attribut-Brücke hängt.
Sie läuft einmal je WordPress-Hauptversion, nicht bei jedem Bau.

```bash
# 1. Zustand vor dem Editor festhalten
wp post get <ID> --field=content | grep -o '"sc":{[^}]*}' | wc -l
```

```
# 2. Die Seite im Block-Editor öffnen, nichts ändern, speichern.
#    Gutenberg serialisiert beim Speichern den ganzen Blockbaum neu. Wird
#    metadata.sc dabei verworfen, dann hier.
```

```bash
# 3. Zustand danach. Die Zahl muss gleich sein.
wp post get <ID> --field=content | grep -o '"sc":{[^}]*}' | wc -l
```

Ist die Zahl kleiner, ist die Brücke gebrochen. Der Ausweg steht in
[ollie-bruecke.md §2](ollie-bruecke.md). Erst dann bauen, nicht vorher auf
Verdacht.

Gegenprobe im Frontend, ob die Attribute wirklich ankommen:

```js
[...document.querySelectorAll('[data-sc-act]')].map(a => ({
  akt:  a.dataset.scAct,
  span: a.dataset.scSpan,
  cues: a.querySelectorAll('[data-sc-cue]').length,
}))
```

## Prüfung 2: Klebt die Bühne wirklich

Der teuerste stille Fehler in diesem Port. Ein Akt, dessen Bühne nicht klebt,
scrollt einfach durch. Die Cues rechnen weiter richtig, die Textebene blendet an
den richtigen Stellen ein, jeder automatische Test läuft grün, und der Screenshot
sieht plausibel aus. Nur die ganze Idee der Seite ist weg.

```js
[...document.querySelectorAll('[data-sc-act]')].map(a => {
  const b = a.querySelector('[data-sc-stage], .sc-stage');
  return {
    akt: a.dataset.scAct,
    position: b ? getComputedStyle(b).position : 'KEINE BUEHNE',
    hoehe: Math.round(a.getBoundingClientRect().height),
  };
})
```

Jeder Akt mit `scrub`, `pin` oder `pan` muss `position: "sticky"` melden. Alles
andere ist ein Fehler, kein Randfall.

Meldet einer `static` oder `relative`, hat ein Vorfahr `overflow: hidden`. Wer es
ist, findet man so:

```js
let el = document.querySelector('.sc-stage');
const kette = [];
while ((el = el.parentElement)) {
  const s = getComputedStyle(el);
  kette.push({ tag: el.tagName, klasse: el.className.slice(0, 60), overflow: s.overflow, transform: s.transform });
}
kette.filter(k => k.overflow !== 'visible' || k.transform !== 'none')
```

Der Motor warnt zusätzlich von selbst in der Konsole. Die Konsole gehört gelesen,
nicht überflogen.

## Prüfung 3: Greifen die Tokens auf Ollie durch

Wenn die Scroll-Seite ihre eigenen Farben mitbringt, hat das Projekt zwei
Design-Systeme, und beim nächsten Farbwechsel ändert sich die Hälfte der Seite.

```js
const cs = getComputedStyle(document.documentElement);
const g = n => cs.getPropertyValue(n).trim();
({
  canvas:  g('--sc-canvas'),  base:    g('--wp--preset--color--base'),
  ink:     g('--sc-ink'),     main:    g('--wp--preset--color--main'),
  accent:  g('--sc-accent'),  primary: g('--wp--preset--color--primary'),
  schrift: g('--sc-font-display'),
  gutter:  g('--sc-gutter'),
})
```

`canvas` und `base` müssen gleich sein, `ink` und `main` ebenso, `accent` und
`primary` ebenso. Weicht eins ab, steht irgendwo ein fester Wert.

Ausnahme: läuft `data-sc-drift` gerade, ist `canvas` absichtlich anders. Also am
Seitenanfang messen, bei Scrollposition 0.

## Prüfung 4: Volle Breite und kein Rand

```js
[...document.querySelectorAll('.sc-act')].map(a => {
  const r = a.getBoundingClientRect();
  const s = getComputedStyle(a);
  return {
    breite: Math.round(r.width),
    links: Math.round(r.left),
    padding: s.paddingLeft + ' / ' + s.paddingRight,
  };
})
```

`breite` muss der Fensterbreite entsprechen, `links` muss 0 sein, `padding` muss
`0px / 0px` sein. Steht dort ein Wert, fehlt `alignfull`, oder die Regel gegen
`useRootPaddingAwareAlignments` greift nicht.

## Prüfung 5: Lädt der Motor überhaupt

```js
({
  motor: typeof ScrollCraft,
  instanzen: window.ScrollCraft ? ScrollCraft.instances.length : 0,
  bodyklasse: document.body.classList.contains('sc-page'),
  akte: window.ScrollCraft?.instances[0]?.acts.length ?? 0,
  clips: window.ScrollCraft?.instances[0]?.clips.length ?? 0,
})
```

`instanzen` muss genau 1 sein. Bei 2 wurde zweimal gemountet, und dann bekommt
jeder Akt zwei Beobachter, was sich als Zittern zeigt. Bei 0 hat die Erkennung
im Plugin nichts gefunden, dann `wp post meta update <ID> _th_scrollcraft 1`.

---

## Der Screenshot-Lauf

```bash
STAMP=$(date +%s)
URL="https://staging.DOMAIN.de/SEITE/?v=$STAMP"

npm i playwright-core          # einmalig im Projekt
node <skill>/scripts/shoot.mjs --url "$URL" --out lab/desktop
node <skill>/scripts/shoot.mjs --url "$URL" --out lab/mobil    --width 390 --height 844
node <skill>/scripts/shoot.mjs --url "$URL" --out lab/reduziert --reduced-motion
```

Der Harness läuft jeden Akt an sechs Positionen ab, wartet bis der Abspielkopf
wirklich angekommen ist, und meldet totes Scroll, Cues die nie volle Deckkraft
erreichen, und Kontrast, gemessen am hellsten Bild unter jeder Zeile. Er schreibt
einen Kontaktbogen.

### Zwei Hostinger-Fallen

**Der Bot-Schutz gibt `curl` und externen Messdiensten 403.** Playwright kommt
durch, weil es ein echter Browser mit echtem User-Agent ist. Also nicht mit
`curl` gegenprüfen und aus einem 403 auf einen Serverfehler schließen. Wer die
Antwort ohne Browser sehen muss, geht über SSH:

```bash
ssh HOST "curl -s -o /dev/null -w '%{http_code}' http://localhost/SEITE/"
```

**Der Edge-Cache liefert ohne Query-String den alten Stand.** Jede Prüf-URL
bekommt einen Cache-Brecher, sonst fotografiert man die Fassung von vorhin und
sucht den Fehler in einem Deploy, der längst angekommen ist. `litespeed-cache`
ist auf dieser Installation inaktiv, der Cache sitzt davor.

**Der Wartungsmodus liefert eine Coming-Soon-Seite statt der Seite.** Hostinger
schaltet ihn im Plugin, nicht in WordPress, und `blog_public` verrät nichts
darüber. Im Browser steht dann „Demnächst verfügbar", während `wp eval` über SSH
alles grün meldet. Der Bypass geht ohne den Modus abzuschalten:

```bash
wp option get hostinger_tools --format=json      # liefert bypass_code
```

```
https://staging.DOMAIN.de/SEITE/?bypass_code=<CODE>
```

Der Parameter setzt ein Cookie und ist gleichzeitig der Cache-Brecher. Zwei
Fliegen, ein Griff.

Nach jedem Deploy zusätzlich:

```bash
ssh HOST "wp --path=\$WP cache flush; wp --path=\$WP transient delete --all"
```

### Wenn playwright-core nicht installiert werden darf

`shoot.mjs` braucht `playwright-core`. Wo das nicht geht, beantwortet
`scripts/probe.mjs` wenigstens die wichtigste Frage: bewegt Scroll wirklich
jeden Akt. Es spricht ein installiertes Chrome direkt über das
DevTools-Protokoll und braucht ausser Node 22 nichts.

```bash
node <skill>/scripts/probe.mjs --url "https://staging.DOMAIN.de/SEITE/?bypass_code=CODE" --shot lab/probe.png
```

Es meldet je Akt die Spanne von `--sc-p`, die Deckkraft der Cues an vier
Positionen, und ob eine Schiene sich bewegt hat. Rückgabewert 1, sobald ein Akt
totes Scroll zeigt.

**Was es nicht kann:** Kontrast messen, einen Kontaktbogen bauen, Videobilder
vergleichen. Es ersetzt `shoot.mjs` nicht, es überbrückt.

**Und der Grund, warum es einen eigenen Browser startet:** ein ausgeblendetes
Browser-Fenster tickt `requestAnimationFrame` nicht. Der Motor läuft dort
schlicht nicht, jede Messung meldet Fortschritt 0, und das sieht aus wie eine
kaputte Seite. Wer in einem eingebetteten Browser prüft, muss das Fenster
sichtbar haben.

**Dasselbe Fenster tötet auch jeden `IntersectionObserver`.** Bei
`document.visibilityState === "hidden"` liefert Chromium keine Rückrufe, und
zwar auch für einen Observer, den man selbst gerade erst angelegt hat. Betroffen
sind `data-sc-in` und der Zähler beim Erscheinen: beide brauchen kein rAF, sie
hängen allein am Observer. Die Folge sieht schlimmer aus als totes Scroll,
nämlich nach fehlendem Inhalt, weil `[data-sc-in]` auf `opacity: 0` stehen
bleibt. Wer das sieht, prüft zuerst `document.visibilityState`, bevor er den
Fehler im Markup sucht.

### Zähler beim Erscheinen

`probe.mjs` prüft Akte. Ein `data-sc-count` **außerhalb** jedes Aktes ist kein
Akt und taucht dort nicht auf, deshalb gibt es dafür `scripts/zaehler-probe.mjs`.

```bash
node <skill>/scripts/zaehler-probe.mjs --url "https://staging.DOMAIN.de/SEITE/?bypass_code=CODE" --shot lab/zaehler.png
```

Es scrollt den ersten Zähler mittig ins Bild, tastet den Verlauf über 2,5
Sekunden ab und scrollt danach wieder hoch. Die Gegenprobe ist der eigentliche
Punkt: **ein Zähler beim Erscheinen bleibt stehen, ein am Akt hängender fällt
beim Hochscrollen auf den Startwert zurück.** Nur so unterscheidet man die
beiden Sorten, im Standbild sehen sie gleich aus.

Rückgabewerte: 0 grün oder nichts zu prüfen, 1 wenn ein Zähler sein Ziel nicht
erreicht, zurückfällt oder erneut feuert, 2 wenn die Seite gar kein Scrollcraft
nutzt. Zielwerte ab 10000 quittiert es mit einer Warnung, weil `formatNum()`
englisch rechnet und dort Kommas setzt: aus 12500 wird `12,500` statt `12.500`.

**Ein zweiter Stolperstein bei jeder Messung von Hand:** die Stildatei setzt
`scroll-behavior: smooth`. Ein `scrollTo(0, y)` läuft dann animiert, und wer
100 Millisekunden später misst, misst die alte Position. Immer
`scrollTo({ top: y, behavior: 'instant' })`. `shoot.mjs` macht das schon.

### Dann den Kontaktbogen ansehen

Der Harness belegt, dass ein Clip weiterläuft. Er kann nicht sagen, ob der
Bildaufbau gut ist, die Bewegung rund läuft oder die Seite etwas bedeutet. Also
`sheet.png` wirklich öffnen und lesen. Und einmal mit der Tabulatortaste
durchgehen, ob die Reihenfolge des Fokus stimmt.

### Dann die Gefühlsprobe

Die Seite kalt durchscrollen, ein Wort je Akt aufschreiben für das, was du
gefühlt hast, und **erst danach** `BRIEF.md` aufmachen und gegen die geplante
Kurve halten. Wo sie sich widersprechen, ist die Seite falsch, nicht der Brief.
Auf dem Kontaktbogen zusätzlich bestätigen, dass der Höhepunkt die größte
sichtbare Veränderung ist und den meisten Scroll bekommt, und dass der letzte
Bildschirm auflöst statt auszufransen.

---

## Was ein grüner Lauf nicht abdeckt

**Ein echtes Telefon.** Headless Chrome kann den Video-Decoder eines iPhones
nicht nachstellen, nicht die Autoplay-Regeln, nicht den Stromsparmodus, nicht das
Scrollen mit dem Finger. Nates Notizen berichten von einem Bau, der vier grüne
Runden hatte, während der Hero-Clip auf dem echten Gerät eingefroren stand.

Wird ein Fehler am Telefon gemeldet, kommt `references/device-diag.html` in der
**ersten** Runde neben die Seite, und das Gerät antwortet selbst. Nicht von einer
Maschine aus theoretisieren, die den Fehler nicht erzeugen kann.

```bash
rsync -e "ssh -p 65002 -i ~/.ssh/id_ed25519" \
  <skill>/references/device-diag.html HOST:$WP/sc-diag.html
# dann https://staging.DOMAIN.de/sc-diag.html am Telefon aufrufen
```

Danach wieder löschen. Eine Diagnoseseite, die im Netz stehen bleibt, ist eine
Diagnoseseite, die jemand findet.

**Und der Editor.** Ein Akt sieht im Editor nie aus wie im Frontend, das ist in
Ordnung. Nicht in Ordnung ist, wenn er dort wie Schrott aussieht, denn dann räumt
jemand auf, was funktioniert hat. Einmal die Seite im Editor öffnen und schauen,
ob die Akte als beschriftete Kästen erkennbar sind. Dafür ist
`scrollcraft-editor.css` da.

## Berichten, was wirklich geprüft wurde

Nicht "geprüft und funktioniert". Sondern: welche der fünf Prüfungen grün war,
welche Auflösungen fotografiert wurden, ob ein echtes Telefon in der Hand war,
und was offen blieb. Eine Lücke, die benannt ist, kostet nichts. Eine Lücke, die
als erledigt gemeldet wurde, kostet den nächsten Bau.
