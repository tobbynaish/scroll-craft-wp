# scroll-craft-wp

Scrollcraft für WordPress-Blockthemes. Fork von
[nateherkai/scroll-craft](https://github.com/nateherkai/scroll-craft), angepasst
auf [OllieWP](https://olliewp.com).

Scroll wird zur Zeitachse: Video läuft Bild für Bild unter dem Mausrad,
Abschnitte kleben und blättern weiter, Schienen fahren seitlich, Überschriften
bauen sich Zeile für Zeile auf, der Seitengrund wandert die Farbe.

Der Unterschied zum Original in einem Satz: **hier entsteht keine HTML-Datei,
sondern eine echte WordPress-Seite aus Gutenberg-Blöcken, die ihre Farben,
Schriften und Abstände aus `theme.json` erbt.**

---

## Was im Fork drin ist

| | |
|---|---|
| `plugins/th-scrollcraft-wp/` | Der Claude-Skill `scrollcraft-wp`, auf Deutsch, mit den WordPress-Phasen |
| `wp-plugin/th-scrollcraft/` | Das WordPress-Plugin: Motor, Attribut-Brücke, Bausteine |
| `plugins/nateherk-design/` | Nates Original, unverändert. Referenz beim Abgleich mit dem Upstream |
| `README-upstream.md` | Nates ursprüngliche Anleitung |

Der Motor selbst (`scrollcraft.js`, 1167 Zeilen) ist **unverändert**. Verändert
ist alles darum herum.

## Die vier Anpassungen

**1. Parameter reisen im `metadata`-Attribut.**
Gutenberg hat kein Feld für `data-sc-act="scrub"`. Ein eigener Blocktyp bräuchte
einen Build-Prozess und Pflege bei jedem WordPress-Update. Also:

```html
<!-- wp:group {"metadata":{"sc":{"act":"scrub","span":2.6}}} -->
```

wird serverseitig über `WP_HTML_Tag_Processor` zu

```html
<div class="wp-block-group sc-act sc-act--pinned" data-sc-act="scrub" data-sc-span="2.6">
```

Kein Build, kein npm, editorfest. `metadata` ist seit WordPress 6.5 das Feld, in
dem Core selbst die Block-Bindings transportiert.

**2. Die Stildatei zeigt auf Ollie statt auf eigene Werte.**
`--sc-ink` ist nicht mehr `#f4f2ef`, sondern
`var(--wp--preset--color--main, #f4f2ef)`. Wer die Farben der Website ändert,
ändert die Scroll-Seite mit. Dazu fällt der globale Reset weg, weil `html`,
`body`, `img` und `button` in einem Blocktheme aus `theme.json` kommen.

**3. Die Sticky-Rettung.**
`position: sticky` stirbt lautlos, sobald ein Vorfahr `overflow: hidden` trägt,
und `.wp-site-blocks` sowie `.wp-block-post-content` sind genau solche
Kandidaten. Der Akt scrollt dann einfach durch, jede Cue rechnet weiter richtig,
jeder Screenshot sieht plausibel aus, und niemand findet den Fehler. Deshalb
steht die Rettung ganz oben in der Stildatei und nicht als Fußnote.

**4. Geprüft wird gegen Staging, nicht gegen localhost.**
Mit zwei Hostinger-Eigenheiten, die sonst falsche Ergebnisse liefern: der
Bot-Schutz gibt `curl` 403 und lässt nur echte Browser durch, und der Edge-Cache
liefert ohne Query-String den alten Stand aus.

**5. Hell oder dunkel, je Seite wählbar.**
Scrollcraft ist auf dunkle Seiten hin gebaut, die meisten Ollie-Auftritte sind
hell. Beides ist richtig, je nach Projekt: Firmenauftritte hell, einzelne
Landingpages dürfen dunkel. Gesetzt wird es an der Seite, nicht am Theme.

```bash
wp post meta update <ID> _th_scrollcraft_grund dunkel
```

Beides bleibt bei Ollies elf Farb-Slugs, getauscht werden nur die Rollen.
Einzelne Akte können gegen den Rest laufen, `.sc-dark` in einer hellen Seite,
`.sc-light` in einer dunklen.

## Weiteres, das dazukam

- **Assets über den Higgsfield-MCP** statt über kie.ai, plus einen Weg für
  eigenes Material. Beides endet in der Mediathek, nicht in einem Build-Ordner.
- **Sieben Patterns** als Bausteine: Scrub, Pin, Flow, Pan, Kennzahlen,
  Abschluss und ein Seitengerüst aus sechs Akten.
- **Fünf zusätzliche Interview-Fragen**, ohne die man in WordPress in die
  falsche Richtung baut: neu oder Umbau, Staging oder Live, wer pflegt das
  danach, Kopfbereich mit oder ohne, wieviel Gewicht ist erlaubt.
- **Editor-Stildatei**, damit ein Akt im Backend als beschrifteter Kasten
  erkennbar ist statt als Stapel nackter Gruppen.

## Ein Fund im Motor

`data-sc-kinetic` muss am selben Element sitzen wie `data-sc-cue`. Der Motor
liest es von dem Element, das den Cue trägt, nicht von dessen Kindern.

In Nates `template.html` und im ersten Beispiel von `references/devices.md` steht
es an einem Kind. Dort läuft es stumm ins Leere: die Überschrift blendet mit dem
Elternteil ein, baut sich aber nicht Zeile für Zeile auf. Das Beispiel in
`devices.md §5` ist richtig. Die Patterns in diesem Fork folgen §5.

---

## Einrichten

### 1. Skill

```bash
git clone https://github.com/tobbynaish/scroll-craft-wp.git
ln -s "$PWD/scroll-craft-wp/plugins/th-scrollcraft-wp/skills/scrollcraft-wp" \
      ~/.claude/skills/scrollcraft-wp
```

### 2. WordPress-Plugin

```bash
rsync -az wp-plugin/th-scrollcraft/ \
  HOST:/pfad/zu/wordpress/wp-content/plugins/th-scrollcraft/
ssh HOST "wp plugin activate th-scrollcraft"
```

### 3. Seitenvorlage im Child-Theme

`templates/page-scrollcraft.html` anlegen und in `theme.json` unter
`customTemplates` eintragen. Der Wortlaut steht in
`references/ollie-bruecke.md §4`.

### 4. Zugangsdaten

```bash
cp .scrollcraft-wp.env.example .scrollcraft-wp.env
```

Ausfüllen. Die Datei steht in `.gitignore`.

## Benutzen

Im Gespräch sagen, was gebaut werden soll, und gleich mitgeben, was schon da ist:
Texte, Bilder, eine bestehende Seite. Der Skill inventarisiert das zuerst und
stellt danach nur noch die Fragen, deren Antwort nicht schon dasteht.

```
Bau mir eine Scroll-Seite für <Sache>. Hier ist der Text: ...
Diese Bilder habe ich schon: ...
```

## Voraussetzungen

| | |
|---|---|
| WordPress | 6.4 oder neuer (`WP_HTML_Tag_Processor` braucht 6.2) |
| PHP | 8.0 oder neuer |
| Theme | OllieWP 1.6.1 als Parent, eigenes Child-Theme |
| Server | SSH-Zugang plus `wp-cli`. Ohne beides ist jede Prüfung Handarbeit |
| Lokal | Node 18+, ein vollständiger ffmpeg-Build, `playwright-core` |

`node plugins/th-scrollcraft-wp/skills/scrollcraft-wp/scripts/doctor.mjs` prüft
das lokale Teil davon.

## Lizenz

MIT, wie das Original. Der Motor und die Gestaltungslehre stammen von
[Nate Herk](https://github.com/nateherkai).
