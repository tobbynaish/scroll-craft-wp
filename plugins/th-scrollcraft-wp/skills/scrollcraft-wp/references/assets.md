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

**Die IDs kommen ins Markup, nicht die URLs.** Geschrieben als `wp:<ID>`, das
Plugin löst sie beim Rendern auf:

```html
<!-- wp:html -->
<img class="sc-stage__poster" src="wp:107" alt="" width="1920" height="1080">
<video data-sc-scrub data-sc-src="wp:108" data-sc-src-mobile="wp:106"
       muted playsinline></video>
<!-- /wp:html -->
```

Aufgelöst werden `src`, `poster`, `data-sc-src` und `data-sc-src-mobile` an
`video`, `img` und `source`, und nur innerhalb von `core/html`.

Warum überhaupt IDs: eine Seite mit fest eingetragenen URLs zeigt nach dem Umzug
von Staging auf Live auf die alte Domain. Ein `wp search-replace` repariert das
zwar, aber nur wenn jemand daran denkt. Eine ID ist von sich aus umzugsfest.

Fehlt der Anhang, bleibt `wp:108` im Quelltext stehen statt still zu
verschwinden. Ein sichtbarer Verweis ist auffindbar, ein leeres `src` nicht.

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


## Figuren vor Weiß, ohne Freistellen

Für einen Swiss-Auftritt die stärkste Bildidee, die es gibt: Menschen als dunkle
Silhouetten auf reinem Weiß, ohne sichtbaren Videorahmen. Kein Alphakanal, keine
zweite Datei für Safari, keine Maske.

**Erzeugen.** Die Stil-Präambel wörtlich in jeden Prompt:

> locked-off tripod camera, absolutely no camera movement, no zoom, no handheld
> drift. Seamless pure white infinity background blown out to paper white, no
> visible floor line, no shadow cast onto the background. High-key studio
> lighting, large soft sources from both sides, crisp and even, hard clean edges
> on the figures. Editorial studio photography, not cinematic. No warm colour
> grade, no lens flare, no film grain, no vignette, no bokeh. Generous empty
> white space around the figures. Single continuous take, slow deliberate
> movement.

Dazu die Kleidung festnageln: **plain dark charcoal clothing, no white or light
garments**. Ein weißes Hemd wird beim Einblenden durchsichtig.

**Was trotzdem herauskommt.** Kein Modell liefert reines Weiß. Gemessen am
ersten Clip: Wand 229, Boden 105 von 255. Der Boden ist das Problem, nicht die
Wand.

**Aufhellen, bis der Raum verschwindet.** Ein Weißpunkt auf den ganzen Clip:

```bash
ffmpeg -i roh.mp4 -vf "colorlevels=rimin=0:rimax=0.30:gimin=0:gimax=0.30:bimin=0:bimax=0.30" \
  -c:v libx264 -crf 12 -pix_fmt yuv420p -an weiss.mp4
```

Den Wert messen, nicht schätzen. Gesucht ist der höchste Wert, bei dem Wand
**und** Boden auf 255 stehen und die Figuren noch dunkel sind:

| Probe | roh | 0.36 | 0.30 |
|---|---|---|---|
| Wand | 229 | 255 | 255 |
| Boden Mitte | 94 | 249 | **255** |
| Gesicht | 19 | 48 | 59 |
| Hand | 112 | 154 | 164 |
| Sakko | 35 | 67 | 77 |

Danach `encode.sh` wie immer. Der Zwischenschritt läuft mit crf 12, damit die
zweite Kompression nicht auf einem bereits beschädigten Bild aufsetzt.

**Einbinden.** Zwei Klassen, mehr nicht:

```html
<div class="wp-block-group alignfull sc-akt-cutout">
  <img class="sc-stage__poster sc-cutout" src="wp:113" alt="">
  <video class="sc-cutout" data-sc-scrub data-sc-src="wp:114"></video>
</div>
```

`sc-akt-cutout` an den Akt setzt den weißen Grund und isoliert die Rechnung,
`sc-cutout` an Video und Standbild macht die Multiplikation.

**Grenzen.** Nur auf hellem Grund. Auf dunklem wird alles schwarz. Kein Scrim
darüber, der würde mitmultipliziert und den Clip abdunkeln, statt den Text
freizustellen. Und alles Weiße im Bild verschwindet mit: der weiße Tisch im
ersten Clip war schon roh bei 207 und ist am Ende nur noch als Umriss da.


## Hausstil aus PNG zurückholen

Der häufigste Fall bei einem Bestandskunden: der Hausstil existiert als
Illustration, aber auf dem Server liegen nur PNG. Kein SVG, kein AI, der
Illustrator ist nicht greifbar.

Das ist kein Sackgassenfall. Eine flache Vektor-Illustration, die zu PNG
gerendert wurde, hat harte Kanten und eine Handvoll echter Farben. Was eine
Zählung als zweitausend Farben meldet, ist fast alles Kantenglättung. Wer auf
die echte Palette quantisiert, bekommt die Flächen zurück, und deren Umrisse
sind wieder Pfade.

```bash
./scripts/vektorisieren.py bild.png ziel.svg --farben 40 --toleranz 0.5 --min-flaeche 3
```

**Die Farbzahl entscheidet über alles.** Gemessen an `vr-illus-automatisierung.png`:

| Farben | Ergebnis |
|---|---|
| 14 | Stiefel verschwunden, Pflanze dunkel statt grün, Akzentpunkte falsch |
| 24 | brauchbar, Roboter weich |
| **40** | **Original bis auf einen Punkt und etwas Roboter-Detail** |

Unter 24 wirft MEDIANCUT echte Töne zusammen, und man merkt es erst, wenn man
beide Bilder nebeneinander rendert. Also immer nebeneinander rendern, nie der
Statistik glauben.

**Nach Gegenständen zerlegen, nicht nach Farbe.** Das Skript trennt jede
Farbfläche zusätzlich in zusammenhängende Stücke. Ohne das ist eine Ebene
„alles Grüne": die Pflanze, der Punkt oben rechts und der Streifen am Ärmel in
einem Pfad. Man könnte die Pflanze nicht bewegen, ohne den Punkt mitzunehmen.

Jedes Stück bekommt eine `id` und ein `data-kasten` mit seinen Maßen. Damit
lässt es sich einzeln an `--sc-p` hängen.

Nebenwirkung, angenehm: mit der Zerlegung fiel der Verwurf von 6804 auf 603
Konturen, weil jedes Stück für sich abgefahren wird statt alle zusammen.

**Kurven, keine Treppen.** Der erste Wurf fuhr die Pixelkante ab und gab nur
gerade Strecken aus. Das Ergebnis war bei Vergroesserung **schlechter als das
PNG**, weil dem PNG wenigstens die Kantenglättung hilft, dem Pfad aber nichts.
Rückmeldung dazu war „total pixelig", und das war richtig.

Zwei Dinge beheben es, beide sind nötig:

1. **Überabtasten und weichzeichnen, bevor die Kontur gesucht wird.** Die Maske
   vierfach feiner, ein leichter Gauß, dann die Kontur bei 0,5. Der
   Weichzeichner verschiebt die Kante um weniger als einen Originalpixel und
   glättet die Treppe vollständig.
2. **Bézier statt Streckenzug.** Aus je drei Stützstellen die Tangente
   schätzen, daraus die Kontrollpunkte eines kubischen Segments. Catmull-Rom
   auf Bézier umgerechnet, Faktor 6.

Der Preis: aus 59 KB werden 166 KB je Illustration. Das ist es wert, alles
andere sieht billig aus.

**Nur den Kasten des Teils abtasten.** Vierfache Überabtastung eines
1280×960-Feldes sind 20 Millionen Werte, je Ebene und je Teil. Der erste Lauf
war nach zwei Minuten noch nicht fertig. Auf den Kasten beschnitten sind es
sieben Sekunden, bei identischem Ergebnis.

**Was das kann und was nicht.**

| geht | geht nicht |
|---|---|
| Verschieben, drehen, skalieren | Eine Figur neu stellen. Ein Arm ist eine Fläche, kein Gelenk |
| Ein- und ausblenden, Reihenfolge ändern | Ein anderer Blickwinkel |
| Farben tauschen, weil jede Ebene ihre Füllung hat | Details unterhalb der Toleranz, die sind weg |
| Beliebig skalieren ohne Schärfeverlust | Verläufe, die werden zu Stufen |

Wer die Figur neu stellen muss, braucht den Illustrator. Alles andere geht.
