# Die Ollie-Brücke

Was sich ändert, wenn Scrollcraft nicht mehr in einer HTML-Datei steht, sondern
in einem Blocktheme. Diese Datei ist der Grund, warum ein direkter Kopiervorgang
scheitert.

**Kernsatz:** Ollies Slugs behalten, nur die Werte tauschen. Die Scroll-Seite
erbt Farben, Schriften, Größen und Abstände aus `theme.json` und bringt nur
Bewegung dazu.

---

## 1. Die Token-Brücke

Nates Stildatei setzt zwölf feste Werte auf `:root`. In einem Blocktheme kommen
genau diese Werte aus `theme.json`, und ein zweiter Satz daneben ist ein zweites
Design-System. Die WordPress-Fassung zeigt deshalb auf Ollies Presets, mit Nates
Originalwert als Rückfallebene hinter dem Komma.

| Scrollcraft | zeigt auf | Ollie-Slug |
|---|---|---|
| `--sc-canvas` | `--wp--preset--color--base` | Base |
| `--sc-surface` | `--wp--preset--color--tertiary` | Tint |
| `--sc-ink` | `--wp--preset--color--main` | Contrast |
| `--sc-ink-soft` | `--wp--preset--color--secondary` | Base Accent |
| `--sc-accent` | `--wp--preset--color--primary` | Brand |
| `--sc-accent-ink` | `--wp--preset--color--primary-accent` | Brand Accent |
| `--sc-hairline` | `--wp--preset--color--border-light` | Border Base |
| `--sc-font-display` | `--wp--preset--font-family--primary` | Primary |
| `--sc-font-text` | `--wp--preset--font-family--primary` | Primary |
| `--sc-font-mono` | `--wp--preset--font-family--monospace` | Monospace |
| `--sc-t-xs` bis `--sc-t-4xl` | `--wp--preset--font-size--x-small` bis `--xxx-large` | Schriftgrößen |
| `--sc-section` | `--wp--preset--spacing--xx-large` | Abstände |
| `--sc-gutter` | `--wp--style--root--padding-left` | Seitenrand |
| `--sc-maxw` | `--wp--style--global--wide-size` | Breite Ausrichtung |

Daraus folgt: **wer die Farben der Website ändert, ändert die Scroll-Seite mit.**
Und umgekehrt: eine feste Hexfarbe im Seiten-CSS ist ein Fehler, kein Feintuning.

### Die eine Ausnahme

`data-sc-drift` schreibt `--sc-canvas` als Inline-Stil auf das `html`-Element.
Deshalb stehen die Tokens auf `:root` und nicht auf `.sc-page`. Stünde
`--sc-canvas` zusätzlich auf `body`, würde der geerbte Driftwert dort wieder
überschrieben, und der Farbverlauf über die Seite bliebe stehen. Das ist keine
Schlamperei, das ist die Kaskade.

### Der Editor-Rundlauf, einmal gemessen

**Gemessen am 2026-08-25 gegen WordPress 7.0.4, Ergebnis grün.**

Eine Seite mit sechs `metadata.sc`-Blöcken im Block-Editor geöffnet und ohne
Änderung gespeichert:

| | vorher | nachher |
|---|---|---|
| `metadata.sc` | 6 | 6 |
| Nutzlast je Block | | Zeichen für Zeichen gleich |
| `wp:106/107/108` in `core/html` | da | da |
| `data-sc-scrub` | da | da |
| Länge | 1529 | 1559 |

Die 30 Zeichen mehr sind Normalisierung, keine Verluste. Ein zweites Speichern
ändert nichts mehr, das Format ist konvergiert. Kein Hin- und Herwandern bei
jedem Öffnen.

Bemerkenswert: der Editor **ergänzt** das metadata-Objekt um `categories` und
`patternName`, statt es zu ersetzen. Unser `sc` steht unangetastet daneben.
Genau darauf ist die Brücke gebaut.

Damit ist die Frage beantwortet, an der der ganze Port hängt. Die Prüfung
gehört trotzdem einmal je WordPress-Hauptversion wiederholt, mit
`scripts/editor-rundlauf.sh <id>`.

### Pattern-Verweise überleben das Speichern nicht

Eine Seite, die aus `<!-- wp:pattern {"slug":"..."} /-->` besteht, sieht nach
dem ersten Speichern im Editor völlig anders aus. Gemessen an derselben Seite:

| | vorher | nachher |
|---|---|---|
| `wp:pattern` | 6 | 0 |
| `metadata.sc` | 0 | 30 |
| Länge | 430 | 11377 |

Der Editor setzt das Markup der Patterns fest ein. Das ist WordPress-Verhalten
für unsynchronisierte Patterns und kein Fehler, es hat aber eine Folge, die man
kennen muss: **danach hängt die Seite nicht mehr an den Pattern-Dateien.** Wer
später ein Pattern korrigiert, erreicht diese Seite nicht mehr.

Für gebaute Seiten ist das richtig herum. Eine fertige Kundenseite soll sich
nicht ändern, weil jemand ein Pattern anfasst. Es heißt nur, dass Patterns
Bausteine für den Aufbau sind und keine dauerhafte Verbindung.

Nachgeprüft: die Seite läuft nach der Umformung unverändert, alle sechs Akte
bewegen sich, dieselben p-Werte wie vorher.

### Hell oder dunkel, je Seite

Die Wahl gehört zur Seite, nicht zum Theme. Firmenauftritte werden hell gebaut,
einzelne Landingpages vertragen dunkel. Gesetzt wird es über ein Beitragsmeta:

```bash
wp post meta update <ID> _th_scrollcraft_grund dunkel     # oder hell
```

Voreinstellung ist `hell`, weil das die Farben des Themes unverändert
übernimmt. Das Plugin hängt daraufhin `sc-dunkel` an das **html**-Element, und
dort steht die getauschte Palette.

| Rolle | hell | dunkel |
|---|---|---|
| `--sc-canvas` | `base` | `main` |
| `--sc-surface` | `tertiary` | `main-accent` |
| `--sc-ink` | `main` | `base` |
| `--sc-ink-soft` | `secondary` | `base` bei 62 Prozent |
| `--sc-accent` | `primary` | `primary` |
| `--sc-accent-ink` | `primary-accent` | `primary-accent` |
| `--sc-hairline` | `border-light` | aus `--sc-ink` gemischt |

Zwei Rollen werden bewusst **nicht** getauscht. Das Theme hat `primary` und
`primary-accent` als Paar gewählt, auf th-swiss also Rot mit Weiß darauf, und
dieses Paar stimmt auf beiden Gründen. Wer `--sc-accent-ink` mitdreht, bekommt
Schwarz auf Rot.

`--sc-hairline` dagegen muss getauscht werden. `border-light` ist auf einem
hellen Theme eine dunkle Transparenz und auf schwarzem Grund unsichtbar.

**Warum die Klasse an html geht und nicht an body.** `data-sc-drift` schreibt
`--sc-canvas` als Inline-Stil auf das html-Element. Stünde die dunkle Palette
auf `body`, würde sie diesen geerbten Wert wieder überschreiben, weil `body` das
nähere Element ist. Der Farbverlauf über die Seite bliebe dann auf einer dunklen
Seite einfach stehen, und nichts davon sähe nach Fehler aus. Am html-Element ist
die Reihenfolge richtig herum: ein Inline-Stil schlägt jede Klassenregel am
selben Element.

### Einzelne Akte gegen den Rest

`.sc-dark` an einem Akt in einer hellen Seite, `.sc-light` in einer dunklen.
Dieselbe Mechanik, nur auf einem Element statt auf der Seite.

Sparsam einsetzen. Eine dunkle Aussage in einer hellen Seite wirkt, drei sind
ein Muster.

**Nicht mit `data-sc-drift` am selben Akt kombinieren.** Der Drift schreibt auf
html, `.sc-dark` auf den Akt, und der Akt gewinnt für alles in ihm. Das ist so
gewollt, sieht aber wie ein kaputter Drift aus, wenn man es nicht weiß.

### Radien

Auf einem Swiss-Auftritt gehören zusätzlich alle vier auf 0:

```css
body.sc-page { --sc-r-sm: 0; --sc-r-md: 0; --sc-r-lg: 0; --sc-r-pill: 0; }
```

---

## 2. Wie die Parameter in den Block kommen

Der Motor liest ausschließlich `data-sc-*`. Gutenberg hat kein Feld dafür, und
ein eigener Blocktyp bräuchte einen Build-Prozess plus Pflege bei jedem
WordPress-Update.

Der Weg ist das `metadata`-Attribut. Jeder Core-Block darf es tragen, seit 6.5
transportiert WordPress darin selbst die Block-Bindings und die
Pattern-Overrides, der Editor reicht es unverändert durch, und es taucht in
keiner Oberfläche auf.

```html
<!-- wp:group {"metadata":{"sc":{"act":"scrub","span":2.6,"dwell":0.34}}} -->
```

wird serverseitig zu

```html
<div class="wp-block-group sc-act sc-act--pinned" data-sc-act="scrub"
     data-sc-span="2.6" data-sc-dwell="0.34">
```

Drei Eigenschaften, die das zur richtigen Lösung machen:

- **Kein Build.** Keine `npm`-Kette, kein `block.json`, keine Wartung.
- **Editorfest.** Wer den Text ändert, verliert die Parameter nicht.
- **Geschrieben mit `WP_HTML_Tag_Processor`**, nicht mit einer Ersetzung per
  regulärem Ausdruck. Der Processor kennt die HTML-Grammatik. Eine Ersetzung
  rät, und bei verschachtelten Gruppen mit gleichem Klassennamen rät sie falsch.

**Eine Whitelist, keine Durchreiche.** `inc/render.php` kennt die erlaubten
Schlüssel und prüft jeden Wert gegen seinen Typ. Ohne das könnte jeder Block ein
beliebiges Attribut an sein Wrapper-Tag schreiben, und das ist ein Einfallstor.

**Medien nur aus der eigenen Installation.** `src` und `src-mobile` nehmen eine
Anhang-ID (bevorzugt, überlebt einen Domainwechsel) oder eine URL auf demselben
Host. Fremde Hosts werden abgelehnt, weil der Motor die Datei per `fetch` in ein
Blob holt und das an CORS ohnehin scheitert.

### Wenn `metadata` doch einmal verschwindet

Sollte ein WordPress-Update `metadata` auf bekannte Unterschlüssel beschränken,
bricht die Brücke. Der Test dafür steht in
[verify.md](verify.md#pruefung-1-ueberlebt-metadata-den-editor) und gehört in
jeden Bau. Der Ausweg wäre dann eine kleine Editor-JS-Datei ohne Build:

```js
wp.hooks.addFilter( 'blocks.registerBlockType', 'th/sc', ( s ) => (
  { ...s, attributes: { ...s.attributes, scData: { type: 'object' } } }
) );
```

Erst bauen, wenn der Test rot ist. Vorher ist es Code auf Verdacht.

---

## 3. Die sechs teuersten Fallen

| Symptom | Ursache | Gegenmittel |
|---|---|---|
| Akt scrollt einfach durch, klebt nicht, alles andere sieht richtig aus | Ein Vorfahr trägt `overflow: hidden` und wird damit zum Scroll-Container | `overflow: clip` statt `hidden`. Die Rettung steht in `scrollcraft.css` ganz oben. Der Motor warnt zusätzlich in der Konsole |
| Bühne ist nur so breit wie der Text | Der Akt hat kein `alignfull`, das constrained-Layout klemmt ihn auf Contentbreite | `"align":"full"` an den äußeren Gruppen-Block |
| Vollbild-Bühne hat links und rechts einen Rand | `useRootPaddingAwareAlignments` gibt jedem `alignfull` den Seitenrand als `padding` zurück | `padding-inline: 0` auf `.sc-act.alignfull`, steht schon in der Stildatei |
| Ollies klebender Kopfbereich liegt über der Bühne | Beide kleben bei `top: 0` | Seitenvorlage ohne Kopfbereich, oder `--sc-top` auf die Kopfhöhe setzen |
| Neues Pattern erscheint nicht im Inserter | WordPress cached die Pattern-Dateiliste | `wp_clean_themes_cache()` in jeden Deploy. Steht in `deploy.sh` |
| Motor lädt nicht, obwohl Markup da ist | Die Erkennung im Kopfbereich fand nichts, etwa bei einem synchronisierten Pattern | `wp post meta update <ID> _th_scrollcraft 1`. Das Sicherheitsnetz im Fußbereich meldet es in der Konsole |

Weitere 35 Fallen zu Ollie allgemein stehen in der `fallen-referenz.md` des
`olliewp-website`-Skills. Sie gelten hier unverändert weiter.

---

## 4. Die Seitenvorlage

Eine Scroll-Seite ohne klebenden Kopfbereich ist einfacher, dichter und
schneller. Das ist die empfohlene Bauweise, und im Worldflight-Modus ist sie
Pflicht: sobald ein echter Block über die feste Bühne scrollt, hat die Seite
wieder eine Naht, und genau die soll der Modus vermeiden.

`th-swiss/templates/page-scrollcraft.html`:

```html
<!-- wp:group {"tagName":"main","metadata":{"name":"Scrollcraft"},"layout":{"type":"constrained"}} -->
<main class="wp-block-group">
<!-- wp:post-content {"layout":{"type":"constrained"}} /-->
</main>
<!-- /wp:group -->
```

**Constrained, nicht flow.** Das wirkt falsch, ist aber richtig: nur im
constrained-Layout greift WordPress' eigene Behandlung von `alignfull` samt der
negativen Seitenränder, die den Akt aus dem Wurzel-Innenabstand herausholen. Im
flow-Layout gibt es diese Behandlung nicht, und die Bühne bekommt links und
rechts einen Rand, den niemand bestellt hat.

Registriert wird die Vorlage über `theme.json`:

```json
"customTemplates": [
  { "name": "page-scrollcraft", "title": "Scroll-Seite", "postTypes": [ "page" ] }
]
```

Der Name enthält `scrollcraft`, und daran erkennt das Plugin die Seite. Das ist
Absicht, kein Zufall.

Wer den Kopfbereich behalten will, setzt seine Höhe:

```css
body.sc-page { --sc-top: 72px; }
```

Der Motor rechnet die Pin-Strecke weiter gegen die volle Bildschirmhöhe, die
Abweichung ist bei üblichen Kopfhöhen unter fünf Prozent und fällt nicht auf.
Bei einem hohen Kopfbereich fällt sie auf, und dann ist die Vorlage ohne Kopf
die richtige Antwort.

---

## 5. Was das Child-Theme beisteuert, und was nicht

| Gehört ins Plugin | Gehört ins Child-Theme |
|---|---|
| Der Motor, JS und CSS | Die Design-Tokens in `theme.json` |
| Die Attribut-Brücke | Die Seitenvorlage `page-scrollcraft.html` |
| Die wiederverwendbaren Akt-Patterns | Die seitenspezifischen Patterns |
| Die Erkennung, wann geladen wird | Radien, Formsprache, eigene Klassen |

Grund für die Trennung: der Motor treibt Inhalte, und Inhalte überleben einen
Theme-Wechsel. Die Gestaltung nicht.

## 6. Zusammenspiel mit dem bestehenden th-swiss

Drei Stellen, die man kennen muss.

**`inc/block-manager.php`** hält eine Whitelist erlaubter Blöcke. `core/html`,
`core/group`, `core/video` und `core/image` sind für `page` bereits dabei. Das
Plugin legt seine Pflichtblöcke trotzdem mit Priorität 50 dazu, damit es auf
einer fremden Ollie-Installation ohne diese Whitelist genauso läuft.

**`inc/performance.php`** hängt `defer` an alle Frontend-Skripte. Der Motor
verträgt das, er wird ohnehin mit `strategy: defer` eingereiht und mountet auf
`DOMContentLoaded`.

**`assets/js/th-motion.js`** bringt eigene Scroll-Animationen mit
IntersectionObserver mit. Auf einer Scrollcraft-Seite kollidieren die beiden
nicht, weil sie unterschiedliche Klassen ansprechen. Wenn ein Element trotzdem
zweimal eingeblendet wird, gewinnt der zuletzt geschriebene Inline-Stil, und das
sieht aus wie ein Flackern. Dann bekommt die Scroll-Seite `th-motion` abgehängt:

```php
add_filter( 'th_scrollcraft_enqueue', function ( $an ) {
    if ( $an ) {
        wp_dequeue_script( 'th-swiss-motion' );
    }
    return $an;
} );
```
