---
name: scrollcraft-wp
description: Baut eine scroll-getriebene WordPress-Seite auf einem OllieWP-Blocktheme. Scroll wird zur Zeitachse: Video läuft Bild für Bild unter dem Mausrad, Abschnitte kleben und blättern weiter, Schienen fahren seitlich, Überschriften bauen sich Zeile für Zeile auf, der Seitengrund wandert die Farbe. Führt zuerst ein Interview (Vorhaben, vorhandene Inhalte und Bilder, Gefühlskurve, Signature Move), wählt dann eine Seiten-Grammatik, erzeugt oder veredelt die Assets, baut echte Gutenberg-Patterns auf Ollies Design-Tokens statt einer HTML-Datei daneben, spielt nach Staging aus und prüft das Ergebnis, indem es sich selbst durchscrollt und fotografiert. Triggert bei "Scroll-Seite bauen", "Scrollytelling", "Apple-Style Landingpage", "Seite wo beim Scrollen ein Video läuft", "interaktive Landingpage", "Scrollcraft", "die Seite soll sich wie ein Erlebnis anfühlen", "das sieht aus wie ein Template".
allowed-tools: Bash, Read, Write, Edit, Glob, Grep, AskUserQuestion
---

# Scroll-Seite auf OllieWP

Scroll ist die einzige Eingabe, die jeder Besucher schon beherrscht. Dieser Skill
behandelt sie als Zeitachse: das Mausrad ist der Schieberegler, die Seite ist ein
Film mit echtem Text darüber, und jeder Abschnitt verhält sich anders genug, dass
weitergescrollt wird, um zu sehen, was der nächste macht.

**Herkunft:** Der Motor und die Gestaltungslehre stammen von Nate Herk
(`nateherkai/scroll-craft`, MIT). Der Motor ist unverändert. Angepasst ist alles,
was in einer einzelnen HTML-Datei richtig war und in WordPress falsch wäre.

**Kernsatz des Ports:** Die Scroll-Seite ist kein zweites System neben dem Theme.
Sie erbt Ollies Farben, Schriften, Größen und Abstände über `theme.json` und
bringt nur Bewegung dazu.

**Was am Ende dasteht:** ein Interview-Brief, eine Grammatik, eine Kundenreise,
eine Gefühlskurve mit genau einem Höhepunkt, eine Partitur, ein Signature Move,
die Assets in der Mediathek, eine echte WordPress-Seite aus Gutenberg-Blöcken,
und eine Reihe Screenshots, die belegt, dass es an jeder Scroll-Position hält.

## Wann anwenden

- Neue Landingpage oder Startseite, die sich wie ein Erlebnis lesen soll
- Bestehende Ollie-Seite in eine Scroll-Seite umbauen
- Kampagnen- oder Produktseite mit einem echten Höhepunkt statt einer Kachelwand

**Nicht anwenden** für gewöhnliche Inhaltsseiten, Blogbeiträge, Archive oder
Formularstrecken. Dafür ist `olliewp-website` zuständig. Dieser Skill setzt
darauf auf und ersetzt ihn nicht.

## Was das nicht ist

Es ist nicht "erzeuge einen Kameraflug und leg Text drauf". Daraus wird ein
einziges Mittel, über die ganze Seite ausgewalzt, und jede so gebaute Seite ist
auf einen Blick erkennbar: dieselbe weiche Knet-Landschaft, derselbe zentrierte
Text, derselbe `01 / 06`-Zähler, derselbe "scroll to explore"-Hinweis. Fünf
Abschnitte, die sich gleich verhalten, sind ein Abschnitt, fünfmal gezeigt.

Vier Regeln folgen daraus, und sie sind das Rückgrat:

1. **Vielfalt ist das Produkt.** Mindestens vier Gerätefamilien pro Seite, nie
   zweimal dieselbe hintereinander. Siehe [references/devices.md](references/devices.md).
2. **Die Welt ist fotografisch**, außer die Marke ist wirklich illustriert.
   Weiche Low-Poly-Knetlandschaft ist als Voreinstellung verboten. Siehe
   [references/worlds.md](references/worlds.md).
3. **Keine durchgehende Kette.** Ein einziger ununterbrochener Kameraflug ist das
   Teuerste und Zerbrechlichste, was gebaut werden kann. Wechselt das Gerät, ist
   der Schnitt umsonst verschwunden.
4. **Eine andere Welt ist keine andere Seite.** Struktur ist eine eigene Achse
   und muss bewusst entschieden werden. Siehe
   [references/uniqueness.md](references/uniqueness.md).

## Reihenfolge

```
0. Interview             ← vor allem anderen, auch vor jeder Datei
1. Zugang und Umgebung   ← ohne das ist alles danach Theorie
2. Brief, Reise, Partitur← allein, das Denken vor dem Bauen
3. Assets                ← teuer, deshalb erst wenn die Partitur steht
4. Bauen                 ← Patterns, Seite, Deploy
5. Prüfen                ← nicht ansehen, messen
6. Übergabe
```

Nach jeder Phase ein Checkpoint mit Zusammenfassung, dann warten. Der Mensch
entscheidet, ob weitergebaut wird.

---

## Phase 0: Das Interview

**Immer erst den Menschen fragen, bevor irgendetwas erzeugt wird.** Kein Brief,
den du aus dem Firmennamen abgeleitet hast, kein Plan zur Abnicke. Echte Fragen,
gestellt, beantwortet, aufgeschrieben. Eine Seite aus Annahmen sieht aus wie die
letzte Seite aus Annahmen.

**Zuerst inventarisieren, dann fragen.** Wenn im Auftrag schon Inhalte, Bilder,
Texte oder eine bestehende Seite mitgegeben wurden, wird das zuerst gelesen und
als Liste zurückgespiegelt. Fragen, deren Antwort schon dasteht, werden nicht
gestellt. Das ist der häufigste Grund, warum ein Interview nervt.

Acht Fragen zur Sache, in einem Durchgang:

1. **Stimmung in drei bis fünf Wörtern**, plus bis zu drei Vorbilder aus jedem
   Medium. Ein Film, ein Plattencover, ein Laden, eine Zeitschrift, ein Spiel.
   Nicht "Websites, die du magst": Websites nennen ist der Weg zu einer Seite,
   die aussieht wie eine bestehende Seite.
2. **Die Scroll-Reise, Abschnitt für Abschnitt, in seinen Worten.** Was zuerst
   kommt, was danach, was das Letzte ist. Seine Reihenfolge, nicht deine Auswahl.
3. **Die Energiekurve.** Wo ruhig, wo laut. Eine Seite, die durchgehend laut ist,
   ist so flach wie eine, die durchgehend leise ist.
4. **Wie soll sich das anfühlen, Stufe für Stufe, und was ist der EINE Moment,
   der hängen bleibt?** Energie ist Lautstärke, das hier ist Gefühl, und die
   beiden decken sich nicht. Die Stufenantwort wird zur Gefühlskurve, der eine
   Moment wird zum Höhepunkt. Beides ist Pflicht im Brief. Siehe
   [references/feel.md](references/feel.md).
5. **Eine Sache, die diese Seite tut, die er auf keiner anderen gesehen hat.**
   Der Keim des Signature Move. Auf einer echten Antwort bestehen, "einprägsam
   sein" ist keine.
6. **Wie weit weg von zurückhaltend-hochwertig.** Die Bandbreite aus
   [uniqueness.md §5](references/uniqueness.md) anbieten: brutalistisch,
   maximalistisch, verspielt, retro, dicht, redaktionell, zurückhaltend.
7. **Eine durchgehende Welt oder getrennte Szenen?** Die größte strukturelle
   Weiche, und sie gehört ihm, nicht dir. Beides schlicht anbieten, keins ist
   die Voreinstellung. Siehe [references/worldflight.md](references/worldflight.md).
8. **Was ist schon da?** Videomaterial, Fotos, Produktaufnahmen, ein Markenpaket,
   Aufnahmen von ihm selbst. Echtes Material verankert die Welt und spart
   Erzeugung. "Nichts" ist eine gültige Antwort.

Sechs Fragen zur WordPress-Seite, die in Nates Fassung fehlen und ohne die man
in die falsche Richtung baut:

9. **Neue Seite oder Umbau?** Bei Umbau: die URL, und ob die alte Fassung
   erhalten bleiben soll. Eine Startseite umzubauen ist etwas anderes als eine
   Kampagnenseite anzulegen.
10. **Staging oder Live?** Gebaut wird auf Staging. Immer. Wenn es kein Staging
    gibt, ist das Anlegen eines Stagings der erste Arbeitsschritt, nicht der
    letzte.
11. **Wer pflegt die Seite danach?** Entscheidet, wie viel in bearbeitbaren
    Blöcken steht und wie viel in `core/html`. Wer nie wieder ran will, bekommt
    weniger Blöcke und mehr rohes Markup, und umgekehrt.
12. **Kopfbereich und Fußbereich: mit oder ohne?** Eine Scroll-Seite ohne
    klebenden Ollie-Kopf ist einfacher, dichter und schneller. Mit Kopf braucht
    es `--sc-top` und eine eigene Vorlage. Siehe
    [references/ollie-bruecke.md](references/ollie-bruecke.md).
13. **Wieviel Gewicht ist erlaubt?** Ein 5-Sekunden-Clip in 1080p sind grob 2 bis
    5 MB, und der Motor lädt ihn komplett, bevor er scrubben kann. Zwei Clips
    sind machbar, sechs sind eine Entscheidung. Das gehört vorher geklärt, nicht
    nachher entschuldigt.
14. **Heller oder dunkler Grund?** Die Frage wird gestellt, nicht abgeleitet.
    Scrollcraft ist auf dunkle Seiten hin gebaut, die meisten Ollie-Auftritte
    sind hell, und beides ist richtig, je nach Projekt.

    | | |
    |---|---|
    | **hell** | Firmenauftritte, Enterprise, alles was neben bestehenden Seiten desselben Hauses steht. Die Scroll-Seite bleibt erkennbar dieselbe Marke |
    | **dunkel** | einzelne Landingpages, Kampagnen, Produkteinführungen. Fotos und Video stehen auf dunklem Grund besser, und die Seite darf sich absetzen |

    Gesetzt wird es je Seite, nicht je Theme:

    ```bash
    wp post meta update <ID> _th_scrollcraft_grund dunkel
    ```

    Beides bleibt bei Ollies elf Farb-Slugs, getauscht werden nur die Rollen.
    Einzelne Akte können gegen den Rest laufen, `.sc-dark` in einer hellen
    Seite, `.sc-light` in einer dunklen. Sparsam: eine dunkle Aussage pro Seite
    wirkt, drei sind ein Muster.

Die Antworten kommen wörtlich nach `<workspace>/builds/<name>/BRIEF.md`, bevor
irgendein Akt geplant wird. Nicht in Marketingprosa umgeschrieben. Alles danach
liest aus dieser Datei.

`BRIEF.md` enthält mindestens:

- Die vierzehn Antworten, wörtlich.
- **Die Gefühlskurve.** Eine Zeile je Akt: das Gefühl, dann was auf dem Schirm es
  auslöst. Geschrieben bevor die Akte existieren.
- **Den Höhepunkt.** Der eine Moment, formuliert als der Satz, den ein Besucher
  einem Freund sagen würde, plus in welchem Akt er liegt.
- **Den fertigen Erzählsatz.** "Das ist die Seite, auf der ___", gefüllt mit
  einem Erlebnis, nicht mit einem Gerätenamen.
- Jede beabsichtigte Stille, damit die Prüfung sie von totem Scroll unterscheiden
  kann.

Ist der Mensch wirklich nicht erreichbar und der Lauf autonom, schreibst du
`BRIEF.md` selbst, markierst die Datei oben mit `Selbst verfasst, nicht
interviewt`, und sagst es im Schlussbericht. Ein selbst verfasster Brief ist ein
Notbehelf, nie der Plan.

---

## Phase 1: Zugang und Umgebung

**Zugang beweisen, bevor gebaut wird.** Ohne bewiesenen Weg auf den Server ist
alles danach Theorie.

```bash
ssh -p 65002 -i ~/.ssh/id_ed25519 NUTZER@IP 'whoami; which wp; php -v | head -1'
```

Der Beweis ist `whoami` plus `which wp`. Ohne wp-cli wird jede spätere Prüfung
mühsam. Schlüsselnamen lügen, im Zweifel alle durchprobieren.

Dann das Plugin. Es trägt den Motor, die Attribut-Brücke und die Bausteine:

```bash
rsync -az --delete -e "ssh -p 65002 -i ~/.ssh/id_ed25519" \
  wp-plugin/th-scrollcraft/ NUTZER@IP:$WP/wp-content/plugins/th-scrollcraft/
ssh ... "wp --path=$WP plugin activate th-scrollcraft"
```

Danach die Umgebung prüfen, nicht raten:

```bash
node <skill>/scripts/doctor.mjs        # node, ffmpeg, playwright, Schlüssel
```

Ein gestrippter ffmpeg meldet einen fehlenden Filter als Syntaxfehler in deinem
Befehl. Der Doctor kennt diese Falle, dein Auge nicht.

**Fertig wenn:** SSH steht, wp-cli antwortet, das Plugin ist aktiv, und
`wp eval` zählt die Scrollcraft-Patterns. Der Pattern-Zähler ist die billigste
Rückmeldung, die es gibt.

---

## Phase 2: Brief, Reise, Grammatik, Partitur

Reine Denkarbeit, keine Datei auf dem Server. Vier Schritte, in dieser Reihenfolge.

**Die Reise zuerst.** Vier bis sieben Beats, jeder eine Verschiebung in dem, was
der Besucher weiß oder fühlt.

```
1  Wiedererkennen  er sieht seinen eigenen Morgen
2  Spannung        was ihn das kostet, klar benannt
3  Wendung         das, was sich ändert
4  Substanz        warum es hält
5  Auswahl         was er wählen kann
6  Zusage          die eine Handlung
```

Beats sind das Rückgrat. Ein Abschnitt, der keinem Beat dient, fliegt raus, so
schön die Aufnahme auch ist.

**Eine Grammatik wählen.** Acht Stück, und sie schließen einander aus, weil jede
verbietet, was die anderen brauchen. Vollständig in
[references/uniqueness.md](references/uniqueness.md). Nav, Hero und Abschluss
folgen aus der Grammatik, sie werden nicht getrennt entschieden.

**Den Signature Move erfinden.** Eine maßgefertigte Interaktion, die es nur auf
dieser Seite gibt, im Seiten-JavaScript codiert, nicht als anderer Parameter an
einem Kit-Gerät. Frage 5 aus dem Interview ist der Keim. Der Motor bleibt
unangetastet.

**Die Gefühlskurve vor der Partitur schreiben.** Eine Zeile je Akt: das Gefühl,
dann was es auslöst. Kurve zuerst, Geräte danach, sonst ist das Gerät ein Gerät
auf der Suche nach einem Grund. Zwei benachbarte Akte mit demselben Gefühl heißt,
einer ist Füllung, und der fliegt hier raus statt nach der Asset-Erzeugung.

**Dann die Partitur als Tabelle.** Ein Gerät je Beat, aufgeschrieben:

| Beat | Gerät | Warum dieses |
|---|---|---|
| Wiedererkennen | `scrub` | Die Kamera unter der eigenen Hand ist der stärkste Anfang |
| Spannung | `pin` + kinetisch | Text baut sich auf, während das Bild still steht |
| Wendung | `reveal` | Ein Wischer ist ein Zustandswechsel, und genau das ist dieser Beat |
| Substanz | `scrub` (Makro) | Struktur in einer Größe, die das Auge sonst nicht bekommt |
| Auswahl | `pan` | Seitliche Fahrt liest sich als Auswahl, senkrechte als Argument |
| Zusage | `pin` + Zeiger | Die Seite hört auf sich zu bewegen und fängt an zu antworten |

Prüfungen vor dem Bauen:

- Die Verbote der Grammatik halten.
- Vier oder mehr verschiedene Gerätefamilien. Weniger heißt, die Seite hat eine
  Idee.
- Keine Gerätefamilie zweimal hintereinander.
- Höchstens zwei `scrub`-Akte. Video ist das Schwerste auf der Seite, und der
  dritte ist keine Überraschung mehr.
- Keine zwei benachbarten Akte mit demselben Gefühl.
- Ein Akt ist der Höhepunkt und hat die längste Spanne, sichtbar. Der Akt davor
  ist leiser als er.
- Jeder Akt verdient seine Scroll-Spanne. Gesamtlänge 8 bis 14 Bildschirmhöhen.
  Länger ist nicht eindringlicher, länger ist langsamer.

---

## Phase 3: Assets

Zwei Quellen, und die erste hat Vorrang.

**Erstens: was schon da ist.** Fotos, Videomaterial, Produktaufnahmen aus dem
Auftrag oder aus der Mediathek. Echtes Material schlägt erzeugtes jedes Mal, es
verankert die Welt und kostet nichts. Es wird sortiert, beschnitten, farblich
angeglichen und fürs Scrubben encodiert.

**Zweitens: was fehlt.** Erzeugt über den Higgsfield-MCP, der in dieser Umgebung
angebunden ist. `generate_image` für Standbilder, `generate_video` für Clips,
`upscale_image` und `remove_background` für Nachbearbeitung.

Vollständiger Ablauf, Prompt-Gerüste und der Weg in die Mediathek in
[references/assets.md](references/assets.md).

Drei Dinge entscheiden, ob es hochwertig oder erzeugt aussieht:

- **Eine Stil-Präambel, in jedem Prompt wörtlich wiederholt.** Das ist, was sechs
  einzelne Bilder wie eine Aufnahme aussehen lässt. Einmal schreiben, nie
  umformulieren.
- **Jedes Asset ansehen, bevor es benutzt wird.** Erzeugen ist billig, neu
  erzeugen ist billiger als ein schlechtes Bild auszuliefern.
- **Fürs Scrubben encodieren, nicht fürs Abspielen.** `encode.sh` setzt eine
  dichte Keyframe-Folge, weil ein Sprung im Video vom letzten Keyframe an
  vorwärts läuft. Eine normale Web-Encodierung spielt perfekt und scrubbt wie
  Schlamm.

---

## Phase 4: Bauen

Hier trennt sich diese Fassung am deutlichsten vom Original. Nate schreibt eine
HTML-Datei. Hier entstehen echte Gutenberg-Blöcke, damit Text im Editor
bearbeitbar bleibt und die Seite in WordPress lebt.

**Der Weg vom Gerät zum Block** steht vollständig in
[references/vokabular.md](references/vokabular.md), mit kopierfertigem Markup je
Gerät. Das ist die wichtigste Datei dieses Skills. Ohne sie erfindet jeder
Durchlauf eigene Klassennamen.

Das Prinzip in drei Sätzen:

1. **Parameter reisen im `metadata`-Attribut.** `<!-- wp:group
   {"metadata":{"sc":{"act":"scrub","span":2.6}}} -->` wird serverseitig zu
   `data-sc-act="scrub" data-sc-span="2.6"` am äußeren Tag. Der Editor reicht
   `metadata` unverändert durch, es taucht in keiner Oberfläche auf.
2. **Die Medien-Ebene ist ein `core/html`-Block.** Video, Standbild und Verlauf
   sind Mechanik, kein Inhalt, und gehören nicht in den Editor.
3. **Die Text-Ebene sind echte Blöcke.** Überschrift, Absatz, Knopf. Damit bleibt
   die Seite pflegbar, ohne dass jemand HTML anfassen muss.

Ablauf:

```bash
# 1. Patterns ins Child-Theme, seitenspezifisch
#    Wiederverwendbare Akte liegen schon im Plugin.
# 2. Deploy
./deploy.sh all
# 3. Seite anlegen oder aktualisieren
wp post create --post_type=page --post_title="..." --post_status=draft \
   --post_content="$(cat seite.html)"
wp post meta update <ID> _th_scrollcraft 1
```

**Vor jedem Import validieren.** Jedes Block-Markup durch
`pruefe-bloecke.py` aus dem `olliewp-website`-Skill. Ein unbalancierter
Block-Kommentar zerlegt die Seite im Editor, oft unbemerkt.

**Die sechs teuersten Fallen dieser Phase** stehen in
[references/ollie-bruecke.md](references/ollie-bruecke.md). Die erste davon ist
die, die am meisten Zeit kostet: `position: sticky` stirbt lautlos, sobald ein
Vorfahr `overflow: hidden` trägt. Der Akt scrollt einfach durch, jede Cue rechnet
weiter richtig, jeder Screenshot sieht plausibel aus.

---

## Phase 5: Prüfen

Nicht optional, und nicht "müsste gehen". Eine Scroll-Seite hat keinen einzelnen
Zustand: jede Position ist ein anderes Bild, und die Fehler liegen zwischen den
beiden, die man sich zufällig angesehen hat. Vollständig in
[references/verify.md](references/verify.md).

```bash
node <skill>/scripts/shoot.mjs --url "https://staging.DOMAIN.de/SEITE/?v=$(date +%s)" --out lab/shots
node <skill>/scripts/shoot.mjs --url "..." --out lab/mobil --width 390 --height 844
node <skill>/scripts/shoot.mjs --url "..." --out lab/reduziert --reduced-motion
```

Der Harness läuft jeden Akt an sechs Positionen ab, wartet bis das Scrub-Video
wirklich angekommen ist, und meldet **totes Scroll**, **Cues, die nie volle
Deckkraft erreichen**, und **Kontrast, gemessen am hellsten Bild unter jeder
Zeile**. Er schreibt einen Kontaktbogen.

Ohne `playwright-core` beantwortet `scripts/probe.mjs` wenigstens die
wichtigste Frage, ob Scroll jeden Akt wirklich bewegt. Es spricht ein
installiertes Chrome direkt an und braucht kein Paket.

Zähler **außerhalb** eines Aktes prüft keines von beiden, weil sie nicht am
Scroll hängen, sondern einmal beim Erscheinen ticken. Dafür
`scripts/zaehler-probe.mjs`, ebenfalls ohne Paket:

```bash
node <skill>/scripts/zaehler-probe.mjs --url "https://staging.DOMAIN.de/SEITE/?bypass_code=CODE"
```

**Drei Hostinger-Fallen, ohne die diese Phase falsche Ergebnisse liefert:**

- Der Bot-Schutz gibt `curl` und externen Messdiensten 403. Playwright kommt
  durch, weil es ein echter Browser ist. Also nicht mit `curl` gegenprüfen und
  aus einem 403 auf einen Fehler schließen.
- Der Edge-Cache liefert ohne Query-String den alten Stand aus. Jede Prüf-URL
  bekommt einen Cache-Brecher, sonst fotografiert man die Fassung von vorhin.
- Der Wartungsmodus im Hostinger-Plugin liefert eine Coming-Soon-Seite. Im
  Browser steht dann „Demnächst verfügbar", während `wp eval` über SSH alles
  grün meldet. Bypass über `?bypass_code=<CODE>` aus der Option
  `hostinger_tools`, das ist gleichzeitig der Cache-Brecher.

Dann das, was der Harness nicht kann: **den Kontaktbogen ansehen.** Er belegt,
dass ein Clip weiterläuft. Er kann nicht sagen, ob der Bildaufbau gut ist, die
Bewegung rund läuft oder die Seite etwas bedeutet.

**Dann die Gefühlsprobe** ([references/feel.md §6](references/feel.md)). Die Seite
kalt durchscrollen, ein Wort je Akt aufschreiben für das, was du gefühlt hast,
und erst danach `BRIEF.md` aufmachen und gegen die geplante Kurve halten. Wo sie
sich widersprechen, ist die Seite falsch, nicht der Brief.

**Und sagen, was ein grüner Lauf nicht abdeckt: ein echtes Telefon.** Headless
Chrome kann den Video-Decoder eines iPhones nicht nachstellen, nicht die
Autoplay-Regeln, nicht den Stromsparmodus, nicht das Scrollen mit dem Finger.
Wird ein Fehler am Telefon gemeldet, kommt `references/device-diag.html` in der
**ersten** Runde neben die Seite, und das Gerät antwortet selbst.

---

## Phase 6: Übergabe

1. Der Kontaktbogen als Abnahme
2. Das Deploy-Skript, das er selbst ausführen kann
3. Liste der offenen Punkte, nach Dringlichkeit
4. Dokumentierte Abweichungen vom ursprünglichen Auftrag
5. Was geprüft wurde und was nicht

Was **nicht** in die Übergabe gehört: die Behauptung, alles sei fertig, wenn das
Telefon nie in der Hand war.

## Harte Regeln

Auslieferungssperren, keine Vorlieben. Jede einzelne ist etwas, das eine Seite
maschinengemacht wirken lässt.

| Nie | Stattdessen |
|---|---|
| Knetlandschaft, Low-Poly oder Claymation als Voreinstellung | Fotografisch. Siehe worlds.md |
| Ein "Scroll"-Hinweis, Pfeil oder animiertes Maussymbol | Nichts. Sie sehen den Hero, sie wissen es |
| `01 / 06`-Abschnittszähler | Löschen. Reihenfolge ist hier keine Information |
| Eine Kleinüberschrift über jeder Überschrift | Höchstens eine je drei Abschnitte |
| Gedankenstrich oder Geviertstrich im sichtbaren Text | Punkt, Komma, Doppelpunkt, Klammern |
| Zentrierter Text in jedem Akt | Anker wechseln: links, rechts, Mitte, geteilt |
| Dieselbe Gerätefamilie zweimal hintereinander | Partitur in Phase 2 richtig schreiben |
| Irgendetwas erzeugen, bevor der Mensch interviewt wurde | Phase 0 laufen lassen |
| Eine Seite ohne gebauten Höhepunkt, oder mit drei konkurrierenden | Einer. Er bekommt das Asset-Budget, die Stille davor und den meisten Scroll |
| Ein Ende, das ausfranst, ausblendet oder einfach Fußbereich wird | Der Abschluss löst auf und bleibt stehen |
| Akte planen, bevor die Gefühlskurve steht | Kurve zuerst, Geräte danach |
| Ausliefern ohne einen maßgefertigten Signature Move | Einen erfinden. Ein umgefärbter Spot ist keiner |
| Den Motor bearbeiten, um ein Sonderverhalten zu bekommen | Eigenes JavaScript in der Seite, getrieben von `--sc-p` |
| Zur filmischen Einstellung greifen, weil der letzte Bau das tat | Aus allen acht Grammatiken wählen und sagen, warum die anderen sieben verloren |
| Eine ganzflächige Abdunklung gegen Kontrastprobleme | Ein Verlauf nur dort, wo der Text sitzt |
| Text in ein erzeugtes Bild einbrennen | Echtes Markup, immer. Auswählbar, übersetzbar, scharf |
| Erfundene Zahlen in einem Zähler | Nur echte Zahlen. Keine Zahl, kein Zähler |
| `transition: all`, oder width, height, top, left animieren | `transform` und `opacity`, `clip-path` für Wischer |
| Verlaufstext, Neonschein, Schatten ohne Versatz | Gewicht und Größe für Betonung |
| Ton auf einem Scrub-Clip | Spur entfernen. `encode.sh` tut das schon |
| Ausliefern ohne Phase 5 | Phase 5 laufen lassen |

### Zusätzlich, nur in WordPress

| Nie | Stattdessen |
|---|---|
| Eigene Farb- oder Schrift-Slugs neben Ollies elf | Ollies Slugs behalten, nur die Werte tauschen |
| Feste Farbwerte im Seiten-CSS | `var(--wp--preset--color--...)`, damit ein Theme-Wechsel durchschlägt |
| Einen Akt ohne `alignfull` | Das constrained-Layout klemmt ihn auf Contentbreite |
| `overflow: hidden` irgendwo über einem Akt | `overflow: clip`. Nur `hidden` tötet `sticky` |
| Ein eigener Blocktyp mit Build-Prozess | `metadata` plus `render_block`. Kein Build, keine Wartung bei Updates |
| Die Seite direkt auf Live bauen | Staging. Immer |
| Video über YouTube oder Vimeo einbinden | Selbst gehostet aus der Mediathek. Der Motor holt die Datei per fetch, fremde Hosts scheitern an CORS, und die DSGVO-Frage stellt sich gar nicht erst |
| Den Motor mit `wp_enqueue_script` auf jeder Seite laden | Die Erkennung im Plugin macht das schon |

## Fünf Entscheidungen, die der Mensch trifft

Nie selbst entscheiden, immer melden und warten:

1. **Gestaltung**, wenn keine Referenz existiert
2. **Fakten**, wenn Quellen sich widersprechen
3. **Rechtstexte**, Impressum und Datenschutz brauchen anwaltliche Prüfung
4. **Preise**, recherchieren und einordnen ja, festlegen nein
5. **Seitengewicht**, wenn die Assets über das im Interview genannte Budget gehen

Faustregel für alles andere: Was den Auftraggeber überraschen würde, wenn er es
später entdeckt, wird sofort gemeldet.

## Referenzen

| Datei | Wofür | Herkunft |
|---|---|---|
| [references/ollie-bruecke.md](references/ollie-bruecke.md) | Tokens, Vorlage, die sechs WordPress-Fallen | neu |
| [references/vokabular.md](references/vokabular.md) | Block-Markup je Gerät, kopierfertig | neu |
| [references/assets.md](references/assets.md) | Higgsfield, eigenes Material, Mediathek | neu |
| [references/verify.md](references/verify.md) | Prüfung gegen Staging, Hostinger-Fallen | neu |
| [references/devices.md](references/devices.md) | Die neun Geräte im Detail | Nate Herk |
| [references/feel.md](references/feel.md) | Gefühlskurve, Höhepunkt, Gefühlsprobe | Nate Herk |
| [references/taste.md](references/taste.md) | Abstand, Typografie, Tiefe, Farbe | Nate Herk |
| [references/uniqueness.md](references/uniqueness.md) | Acht Grammatiken, Fingerabdruck-Prüfung | Nate Herk |
| [references/worlds.md](references/worlds.md) | Bildwelten statt Knetlandschaft | Nate Herk |
| [references/worldflight.md](references/worldflight.md) | Der Modus für eine durchgehende Welt | Nate Herk |

Die vier Dateien von Nate sind bewusst auf Englisch geblieben. Sie sind
Nachschlagewerk für die Maschine, keine Kundenprosa, und eine Übersetzung würde
nur eine Fehlerquelle beim nächsten Abgleich mit dem Original einbauen.

## Ausgabe

Der Bau-Ordner samt `BRIEF.md`, dann ein kurzer Bericht: die Grammatik und warum
die anderen sieben verloren, der Signature Move, die Reise, die Gefühlskurve und
der Höhepunkt, der Abgleich zwischen geplanter und gefühlter Kurve, die Partitur,
was erzeugt wurde, was mit Screenshots belegt ist, und was nicht geprüft werden
konnte. Sagen, ob der Brief selbst verfasst statt interviewt war. Die
Staging-URL angeben. Kurz halten, die Seite ist das Ergebnis.
