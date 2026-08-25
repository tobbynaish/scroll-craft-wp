# Assets für eine WordPress-Scroll-Seite

Zwei Quellen, eine Reihenfolge, ein Ziel: die Mediathek.

Das Handwerk selbst, also Kamerafahrten, Farbangleich, Nahtverriegelung bei
Ketten und Hochformat, steht unverändert in
[assets-handwerk.md](assets-handwerk.md). Hier steht, was in WordPress dazukommt
und was anders läuft als bei Nate.

**Der größte Unterschied:** Nate erzeugt über kie.ai in einen Build-Ordner. Hier
läuft die Erzeugung über den angebundenen Higgsfield-MCP, und alles landet am
Ende in der Mediathek, nicht in einem Ordner neben der HTML-Datei. Eine Datei,
die nicht in der Mediathek liegt, verschwindet beim nächsten Umzug.

---

## Reihenfolge: erst was da ist, dann was fehlt

**Echtes Material schlägt erzeugtes jedes Mal.** Es verankert die Welt, es kostet
nichts, und es sieht aus wie das Unternehmen und nicht wie ein Modell. Frage 8
und Frage 13 des Interviews klären, was vorliegt und wieviel Gewicht erlaubt ist.

Der Ablauf pro Quelle:

1. **Sichten und aussortieren.** Ein schlechtes Foto wird durch keine Bewegung
   besser.
2. **Angleichen.** Alles, was auf eine Seite kommt, muss aussehen wie aus einer
   Aufnahme. Pegel und Sättigung ziehen, siehe assets-handwerk.md.
3. **Encodieren.** Fürs Scrubben, nicht fürs Abspielen.
4. **Hochladen.** In die Mediathek, mit Anhang-ID zurück ins Markup.

Erst wenn diese Runde durch ist, steht fest, was wirklich fehlt. Vorher zu
erzeugen heißt, Bilder zu bezahlen, die danach nicht gebraucht werden.

---

## Erzeugen über Higgsfield

Der MCP ist in dieser Umgebung angebunden, es braucht keinen zusätzlichen
Schlüssel und kein zweites Abo.

| Werkzeug | Wofür |
|---|---|
| `generate_image` | Standbilder, auch die Startbilder für Clips |
| `generate_video` | Clips aus einem Standbild oder aus Text |
| `generate_image_batch` / `generate_video_batch` | Mehrere unabhängige Erzeugungen, dann `jobs_wait` |
| `upscale_image` / `upscale_video` | Auf 2K oder 4K, wenn eine Vollbild-Bühne es braucht |
| `remove_background` | Freisteller für Produktaufnahmen |
| `outpaint_image` | Ein 4:3-Foto auf 16:9 erweitern statt es zu beschneiden |
| `reframe` | Querformat auf Hochformat fürs Telefon |
| `models_explore` | Wenn unklar ist, welches Modell passt. `action: recommend` |

**Für mehrere Erzeugungen die Batch-Werkzeuge nehmen**, dann `jobs_wait`, dann
ein einziges `show_generation_by_ids`. Sechs Einzelaufrufe kosten sechs
Wartezeiten.

### Die Stil-Präambel

Das Wichtigste an der ganzen Phase. **Ein Stil-Vorspann, in jedem Prompt wörtlich
wiederholt.** Das ist, was sechs einzelne Bilder wie eine Aufnahme aussehen lässt.
Einmal schreiben, nie umformulieren, nie "sinngemäß" einsetzen.

Der Vorspann beschreibt Kamera, Licht, Material und Stimmung, nicht das Motiv:

```
Kamera: 35mm, f/2.0, leichte Aufsicht, keine Weitwinkelverzerrung.
Licht: eine weiche Hauptlichtquelle von links hinten, tiefe warme Schatten.
Material: matte Oberflächen, feiner Filmkorn, kein Hochglanz.
Farbe: gedecktes Warmgrau, ein einziger kalter Akzent.
Kein Text im Bild. Keine Menschen im Anschnitt. Kein Bokeh-Kitsch.
```

Danach kommt pro Bild nur die Szene. Der Vorspann bleibt Wort für Wort gleich.

### Jedes Asset ansehen

Erzeugen ist billig, neu erzeugen ist billiger als ein schlechtes Bild
auszuliefern. Jedes erzeugte Bild wird gelesen, bevor es weiterverarbeitet wird.
Ein Bild mit eingebranntem Text, sechs Fingern oder einem Logo, das keins ist,
fliegt raus und wird neu erzeugt, nicht repariert.

### Was nicht erzeugt wird

- **Text im Bild.** Nie. Text kommt als echtes Markup, dann ist er auswählbar,
  übersetzbar, scharf auf jedem Bildschirm und findet sich in der Suche.
- **Zahlen, die etwas behaupten.** Ein Zähler zeigt nur belegbare Zahlen.
- **Menschen, die es gibt.** Kein erzeugtes Porträt eines echten Mitarbeiters.
- **Weiche Low-Poly-Knetlandschaft** als Voreinstellung. Siehe worlds.md.

---

## Encodieren

```bash
bash <skill>/scripts/encode.sh roh/01.mp4 assets/01.mp4            # Desktop
bash <skill>/scripts/encode.sh roh/01.mp4 assets/01-m.mp4 mobile   # Telefon
```

Der Grund steht im Kopf des Skripts: eine normale Web-Encodierung setzt alle zwei
bis fünf Sekunden einen Keyframe. Ein Sprung im Video läuft vom letzten Keyframe
an vorwärts, also fühlt sich eine solche Datei unter dem Mausrad an wie Schlamm,
während sie beim Abspielen perfekt aussieht. Dichte Keyframes kosten Dateigröße
und kaufen Reaktion. Dieser Tausch ist der ganze Sinn des Skripts.

**Korniges Material braucht ein höheres CRF.** Rauch, Filmkorn, Unterwasser,
Biolumineszenz: dort verdoppelt die dichte Keyframe-Folge die Kosten des Korns.
22 bis 23 statt der voreingestellten 20.

```bash
bash <skill>/scripts/encode.sh roh/01.mp4 assets/01.mp4 desktop 23
```

**Das Standbild kommt aus dem Clip**, nicht aus einer zweiten Erzeugung. Sonst
springt das Bild in dem Moment, in dem das Video übernimmt.

```bash
ffmpeg -i assets/01.mp4 -frames:v 1 -q:v 2 assets/01-poster.webp
```

---

## Ins WordPress

```bash
bash <skill>/scripts/mediathek.sh assets/
```

Das Skript spielt den Ordner nach `/tmp` auf dem Server, importiert jede Datei
mit `wp media import` und gibt eine Tabelle aus Dateiname und Anhang-ID zurück.
Die IDs kommen ins Markup, nicht die URLs:

```html
<!-- wp:group {"metadata":{"sc":{"src":184,"srcMobile":185}}} -->
```

Eine Anhang-ID überlebt einen Domainwechsel und den Umzug von Staging auf Live.
Eine fest eingetragene URL nicht.

**Fremde Hosts gehen nicht, und das ist kein Versehen.** Der Motor holt den Clip
per `fetch` in ein Blob, damit er springen kann, ohne dass der Server
Range-Anfragen beherrschen muss. Ein Video von YouTube, Vimeo oder einem CDN
scheitert daran an CORS. Nebeneffekt: die DSGVO-Frage nach externen Einbindungen
stellt sich gar nicht erst.

### Wenn der Upload klemmt

Shared Hosting begrenzt `upload_max_filesize` oft auf 64 MB und die Laufzeit auf
30 Sekunden. `wp media import` über SSH umgeht beides, weil die Datei schon auf
dem Server liegt und PHP sie nur noch registriert. Deshalb geht der Weg über
`/tmp` und nicht über das Backend.

---

## Das Gewichtsbudget

Der Motor lädt einen Scrub-Clip **komplett** als Blob, bevor er springen kann.
Das ist der Preis dafür, dass er ohne Range-Anfragen auskommt, und es ist die
Zahl, die im Interview geklärt gehört.

| | grob |
|---|---|
| 5 Sekunden, 1080p, dichte Keyframes | 2 bis 5 MB |
| dasselbe in 720p fürs Telefon | 0,8 bis 2 MB |
| ein Standbild in WebP, 1920 breit | 120 bis 300 KB |
| Motor plus Stildatei | 40 KB |

Zwei Scrub-Akte sind ein kleiner Betrag. Sechs sind eine Entscheidung, und die
trifft der Mensch, nicht du.

Drei Dinge, die das Gewicht drücken, ohne dass es auffällt:

- **Zwei Fassungen je Clip.** `srcMobile` in 720p spart auf dem Telefon die
  Hälfte, und dort ist die Leitung schlechter.
- **Vorher laden, nicht bei Ankunft.** Der Motor holt einen Clip, sobald der Akt
  drei Bildschirmhöhen entfernt ist. Wer schneller scrollt, kommt trotzdem an
  einem Standbild an, und genau dafür ist das Standbild da.
- **Unter reduzierter Bewegung wird kein Clip geholt.** Das Standbild trägt die
  Szene, die Cues laufen normal weiter. Die Seite ist dort nicht kaputt, sie ist
  leichter.
