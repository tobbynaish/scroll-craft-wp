# Das Vokabular

Kopierfertiges Block-Markup je Gerät. Ohne diese Datei erfindet jeder Durchlauf
eigene Klassennamen, und das Aufräumen kostet mehr als das Bauen.

**Regel:** Erfinde keine Klasse, die hier nicht steht. Brauchst du eine, kommt
sie zuerst hierher und dann ins Markup.

Was die Geräte tun und wann man welches nimmt, steht in
[devices.md](devices.md). Hier steht nur, wie es in Gutenberg aussieht.

---

## Die drei Ebenen eines Akts

Jeder Akt hat denselben Aufbau, und die Aufteilung ist keine Geschmacksfrage.

```
Akt          core/group, alignfull, metadata.sc.act        ← die Zeitachse
└ Bühne      core/group, metadata.sc.stage                 ← klebt
  ├ Medien   core/html                                     ← Mechanik
  └ Text     core/group.sc-copy + echte Blöcke darin       ← bleibt bearbeitbar
```

**Medien als `core/html`.** Video, Standbild und Verlauf sind Mechanik, kein
Inhalt. Sie gehören nicht in den Editor, wo jemand sie versehentlich löscht.

**Text als echte Blöcke.** Überschrift, Absatz, Knopf. Damit bleibt die Seite
pflegbar, ohne dass jemand HTML anfassen muss.

---

## Alle Schlüssel

Im Block als `{"metadata":{"sc":{ ... }}}`. camelCase wird zu Bindestrich, also
`srcMobile` zu `data-sc-src-mobile`. Beide Schreibweisen sind erlaubt.

| Schlüssel | Typ | Gehört an | Bedeutung |
|---|---|---|---|
| `act` | `scrub` `pin` `pan` `flow` | den Akt | Welches Gerät die Zeitachse treibt |
| `span` | Zahl | den Akt | Bildschirmhöhen Scroll, nur bei geklebten Geräten. Voreinstellung 1.5 |
| `dwell` | 0 bis 0.6 | den Akt | Kamera setzt sich in der Mitte, bewegt sich an den Rändern schneller |
| `drift` | Hexfarbe | den Akt | Seitengrund wandert zu dieser Farbe, solange der Akt sichtbar ist |
| `clipMap` | `travel` | den Akt | Schaltet die Vollzeit-Zuordnung ab. Fast nie richtig |
| `lerp` | 0.02 bis 1 | Akt oder Video | Glättung des Abspielkopfs. Voreinstellung 0.18 |
| `stage` | `true` | die Bühne | Das Element, das klebt |
| `cue` | `von bis [Rampe rein] [Rampe raus]` | jedes Element | Deckkraft und Anstieg, an den Fortschritt gekoppelt |
| `rise` | Zahl | ein Cue-Element | Faktor für die Anstiegsbewegung. 0 schaltet sie ab |
| `kinetic` | `lines` `words` `chars` | **dasselbe** Cue-Element | Zerlegt den Text und staffelt ihn |
| `reveal` | `up` `down` `left` `right` `iris` | jedes Element | Wischer über `clip-path` |
| `revealAt` | `von bis` | dasselbe Element | Fenster des Wischers. Voreinstellung `0 0.5` |
| `count` | `von bis` | ein Textelement | Zahl läuft hoch. Nur echte Zahlen |
| `countAt` | `von bis` | dasselbe Element | Fenster des Zählers. Voreinstellung `0.1 0.55` |
| `parallax` | -2 bis 2 | eine Ebene | Verschiebung in Hundert Pixeln über den ganzen Akt |
| `pan` | Zahl | die Schiene | Zusätzliche Fahrstrecke als Faktor |
| `in` | `true` | einen Fluss-Abschnitt | Blendet einmalig ein, feuert nicht zurück |
| `stagger` | Millisekunden | dasselbe Element | Staffelt die direkten Kinder |
| `tilt` | Grad | jedes Element | Neigt sich zum Zeiger. Nur Maus |
| `magnet` | 0 bis 1 | jedes Element | Driftet zum Zeiger. Nur Maus |
| `spotlight` | `true` | eine Fläche | Licht folgt dem Zeiger. Nur Maus |
| `progress` | `true` | ein leeres Element | Fortschrittsbalken der ganzen Seite |
| `src` `srcMobile` | Anhang-ID oder URL | ein Video | Quelle. ID ist besser, sie überlebt einen Domainwechsel |

---

## Die Falle, die am meisten Zeit kostet

**`kinetic` muss am selben Element sitzen wie `cue`.** Der Motor liest
`data-sc-kinetic` von dem Element, das `data-sc-cue` trägt, nicht von dessen
Kindern.

Nates `template.html` und das erste Beispiel in `devices.md` setzen es an ein
Kind. Dort läuft es stumm ins Leere: die Überschrift blendet mit dem Elternteil
ein, aber sie baut sich nicht Zeile für Zeile auf, und niemand sieht dem
Screenshot an, dass ein Gerät fehlt. Das Beispiel in `devices.md §5` ist richtig.

```html
Falsch:
<!-- wp:group {"metadata":{"sc":{"cue":"0 0.78"}},"className":"sc-copy"} -->
  <!-- wp:heading {"metadata":{"sc":{"kinetic":"lines"}}} -->

Richtig:
<!-- wp:group {"className":"sc-copy"} -->
  <!-- wp:heading {"metadata":{"sc":{"cue":"0 0.78","kinetic":"lines"}}} -->
```

---

## 1. Scrub

```html
<!-- wp:group {"metadata":{"name":"Akt · Scrub","sc":{"act":"scrub","span":2.6,"dwell":0.34,"drift":"#111111"}},"align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull">
<!-- wp:group {"metadata":{"name":"Bühne","sc":{"stage":true}},"layout":{"type":"default"}} -->
<div class="wp-block-group">
<!-- wp:html -->
<img class="sc-stage__poster" src="/wp-content/uploads/2026/08/01-poster.webp" alt="" width="1920" height="1080">
<video data-sc-scrub data-sc-src="/wp-content/uploads/2026/08/01.mp4"
       data-sc-src-mobile="/wp-content/uploads/2026/08/01-m.mp4" muted playsinline></video>
<div class="sc-scrim sc-scrim--lead" aria-hidden="true"></div>
<!-- /wp:html -->

<!-- wp:group {"className":"sc-copy sc-copy--lead","layout":{"type":"default"}} -->
<div class="wp-block-group sc-copy sc-copy--lead">
<!-- wp:heading {"level":1,"metadata":{"sc":{"cue":"0 0.78 0","kinetic":"lines"}},"className":"sc-display sc-display--xl"} -->
<h1 class="wp-block-heading sc-display sc-display--xl">Die Zeile.</h1>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
```

Der dritte Wert im Cue (`0 0.78 0`) ist die Einblend-Rampe. Auf 0 gesetzt heißt:
sofort da, wenn die Seite lädt. Ein Hero, der aus dem Nichts einblendet, blendet
über den einen Moment ein, in dem es sonst nichts zu sehen gibt.

`data-sc-src` steht direkt im HTML, weil es hier kein Block-Attribut gibt. Wer
lieber mit Anhang-IDs arbeitet, setzt sie über `metadata` an einen `core/video`.

**Höchstens zwei Scrub-Akte pro Seite.**

## 2. Pin

```html
<!-- wp:group {"metadata":{"sc":{"act":"pin","span":3.4}},"align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull">
<!-- wp:group {"metadata":{"sc":{"stage":true}},"className":"sc-argument","layout":{"type":"default"}} -->
<div class="wp-block-group sc-argument">
<!-- wp:group {"metadata":{"sc":{"cue":"0 0.32 0"}},"className":"sc-copy sc-copy--center","layout":{"type":"default"}} -->
<div class="wp-block-group sc-copy sc-copy--center">
<!-- wp:paragraph {"className":"sc-display sc-display--md"} -->
<p class="sc-display sc-display--md">Erste Aussage.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- weitere Aussagen: 0.26 0.6 / 0.54 0.88 / 0.82 -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
```

**Fenster um etwa 15 Prozent überlappen lassen.** Sonst entsteht zwischen zwei
Aussagen ein Moment, in dem die Bühne leer ist, und der liest sich als Fehler.
Der letzte Cue ist einwertig, damit er stehen bleibt bis der Akt endet.

## 3. Pan

```html
<!-- wp:group {"metadata":{"sc":{"act":"pan","span":3.2}},"align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull">
<!-- wp:group {"metadata":{"sc":{"stage":true}},"layout":{"type":"default"}} -->
<div class="wp-block-group">
<!-- wp:group {"metadata":{"sc":{"pan":0.06}},"className":"sc-rail","layout":{"type":"default"}} -->
<div class="wp-block-group sc-rail">
  <!-- .sc-rail__lead, dann .sc-rail__item je Karte -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
```

Spanne grob eine Bildschirmhöhe je Karte plus eine. Unter reduzierter Bewegung
wird die Schiene zu einem gewöhnlichen Scrollbereich, damit dieselben Karten
erreichbar bleiben. Das steht in der Stildatei und ist kein Zufall: eine Schiene
auf null zu setzen macht alles hinter der ersten Karte unerreichbar.

## 4. Flow mit Einblenden

```html
<!-- wp:group {"metadata":{"sc":{"act":"flow"}},"align":"full","className":"sc-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull sc-section">
<!-- wp:group {"metadata":{"sc":{"in":true,"stagger":70}},"className":"sc-stack","layout":{"type":"default"}} -->
<div class="wp-block-group sc-stack">
  <!-- Überschrift und Absätze, sie staffeln sich einmalig ein -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
```

`in` feuert einmal über einen IntersectionObserver und blendet beim Zurückscrollen
nicht wieder aus. Inhalt, der sich beim Zurückscrollen versteckt, ist ein Fehler,
kein Effekt.

**Genau ein Flow-Akt pro Seite.** Er ist die Atempause. Zwei davon und die Seite
ist wieder ein Dokument.

## 5. Reveal

```html
<!-- wp:image {"metadata":{"sc":{"reveal":"left","revealAt":"0.18 0.62"}},"sizeSlug":"large"} -->
```

`iris` ist ein Kreis, der aufgeht. Sparsam, er zieht viel Aufmerksamkeit.

## 6. Zähler

```html
<!-- wp:paragraph {"metadata":{"sc":{"count":"0 1240","countAt":"0.12 0.6"}},"className":"sc-display sc-display--lg"} -->
<p class="sc-display sc-display--lg">0</p>
<!-- /wp:paragraph -->
```

**Nur echte Zahlen.** Keine Zahl, kein Zähler. Der Text im Block ist der
Ausgangswert und wird vom Motor überschrieben, er muss trotzdem sinnvoll
dastehen, weil er ohne JavaScript stehen bleibt.

Tausendertrennung schreibt man in den Zielwert hinein, dann formatiert der Motor
danach: `"count":"0 3,500"` ergibt `3,500`, `"count":"0 3500"` ergibt `3500`.

## 7. Parallaxe

```html
<!-- wp:group {"metadata":{"sc":{"parallax":-0.8}},"className":"sc-layer"} -->
```

Die Zahl ist in Hundert Pixeln über den **ganzen** Akt, nicht in
Bildschirmanteilen. 0.35 sind 35 Pixel über drei Bildschirmhöhen, also
unsichtbar. Brauchbar sind grob 0.3 bis 1.5 innerhalb eines Rahmens und 1 bis 2
für eine vollflächige Ebene. Negativ heißt: bewegt sich schneller nach oben als
der Scroll, wirkt also weiter hinten.

Drei Ebenen reichen. Fünf sind ein Diorama.

## 8. Zeigergeräte

```html
<!-- wp:group {"metadata":{"sc":{"tilt":6}}} -->
<!-- wp:button {"metadata":{"sc":{"magnet":0.26}}} -->
<!-- wp:group {"metadata":{"sc":{"stage":true,"spotlight":true}}} -->
```

Alle drei sind an `(hover: hover)` und `(pointer: fine)` gebunden und unter
reduzierter Bewegung aus. Auf dem Telefon feuern sie nie.

**Nie `magnet` und `cue` an dasselbe Element.** Beide schreiben `transform`,
der Magnet in jedem Bild, der Cue beim Scrollen. Das ergibt ein Flackern. Cue an
den Eltern-Block, Magnet an das Kind.

## 9. Fortschritt und Korn

```html
<!-- wp:html -->
<span data-sc-progress></span>
<div class="sc-grain" aria-hidden="true"></div>
<!-- /wp:html -->
```

## 10. Worldflight

Der Modus für eine durchgehende Welt. Eine feste Bühne für die ganze Seite, im
Dokumentfluss steht nur der Platzhalter. **Voraussetzung: eine Seitenvorlage ohne
Kopf- und Fußbereich.** Sobald ein echter Block über die feste Bühne scrollt, hat
die Seite wieder eine Naht, und genau die soll dieser Modus vermeiden.

Weil hier nichts bearbeitbar sein muss außer den Textfenstern, ist die ganze
Bühne ein `core/html`-Block und nur die Textebene besteht aus Blöcken. Aufbau und
Regeln in [worldflight.md](worldflight.md).

```html
<!-- wp:html -->
<div data-sc-mode="worldflight" data-sc-seam="0.12">
  <div data-sc-world>
    <div data-sc-segment data-sc-w="1.4" data-sc-linger="0.3" data-sc-waypoint="Anflug">
      <img class="sc-world__poster" src="/wp-content/uploads/.../p1.webp" alt="">
      <video data-sc-src="/wp-content/uploads/.../leg1.mp4"
             data-sc-src-mobile="/wp-content/uploads/.../leg1-m.mp4"></video>
    </div>
  </div>
  <div data-sc-world-copy>
    <div class="sc-world__scrim sc-scrim sc-scrim--band"></div>
  </div>
  <div data-sc-spacer aria-hidden="true"></div>
</div>
<!-- /wp:html -->
```

Die Textfenster kommen als eigene Blöcke in `[data-sc-world-copy]`:

```html
<!-- wp:group {"metadata":{"sc":{"copy":true,"window":"hero"}},"className":"sc-copy sc-copy--lead"} -->
```

`window` nimmt `hero`, `finale` oder `von bis [rein] [raus]` als Anteile der
**ganzen** Strecke, nicht eines Segments.

---

## Klassenliste

Alles, was es gibt. Mehr wird nicht erfunden.

| Klasse | Wofür |
|---|---|
| `sc-stage__poster` | Standbild, hält das Bild bis das Video malt |
| `sc-scrim` | Verlauf über der Bühne |
| `sc-scrim--lead` `--trail` `--band` `--bottom` `--left` `--right` `--vignette` | Wohin der Verlauf dunkelt |
| `sc-copy` | Textebene über einer Bühne |
| `sc-copy--lead` `--center` `--trail` | Wo sie sitzt |
| `sc-display` `sc-display--xl` `--lg` `--md` | Auszeichnungsschrift |
| `sc-lede` `sc-body` `sc-label` | Vorspann, Fließtext, Kleinschrift |
| `sc-wrap` `sc-section` `sc-stack` `sc-rule` | Layout-Hilfen |
| `sc-rail` `sc-rail__lead` `sc-rail__item` | Waagerechte Schiene |
| `sc-argument` `sc-close` | Bühne ohne Bild, zentriert |
| `sc-figures` `sc-figure` | Kennzahlen-Raster |
| `sc-grain` | Korn über der Seite |
| `sc-dark` | Ein Akt dunkel in einer hellen Seite |
| `sc-light` | Ein Akt hell in einer dunklen Seite |

Vom Plugin gesetzt, nie selbst schreiben: `sc-page`, `sc-grund-hell`,
`sc-grund-dunkel` am Body, `sc-dunkel` am html-Element. Der Grund kommt aus dem
Beitragsmeta `_th_scrollcraft_grund`.

Vom Motor gesetzt, nie selbst schreiben: `sc-act`, `sc-act--pinned`, `sc-stage`,
`sc-has-clip`, `sc-in`, `sc-ready`, `sc-split`, `sc-split__i`, `sc-is-split`,
`sc-world`, `sc-world__seg`, `sc-world__poster`, `sc-world__copy`,
`sc-world__spacer`.
