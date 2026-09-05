# Was wir am Motor geändert haben

`assets/scrollcraft.js` ist Nates `engine/scrollcraft.js` aus
`nateherkai/scroll-craft` (MIT). Grundsatz: **der Motor bleibt unangetastet.**
Was eine Seite braucht, wird über `data-sc-*` und eigenes JavaScript in der
Seite gelöst, nicht im Motor.

Ausnahme sind Fehler im Motor selbst. Die stehen hier, damit ein späterer
Abgleich mit dem Original weiß, was von uns ist. Jeder Patch steht im Code in
einem Block, der mit `th-scrollcraft ==` am Zeilenende geöffnet und mit `==== */`
geschlossen wird, dazwischen die Nummer. Der Grep dafür lautet:

```bash
grep -n "th-scrollcraft ==" assets/scrollcraft.js   # muss 2 Treffer geben
```

**Abgleichstand: `upstream/main` bei `0b81622`, geprüft am 2026-09-05.** Beide
Patches sind dort weiterhin nötig, `scroll-craft#1` ist offen. Nates Änderung in
v0.3.0 ist rein additiv (Counter beim Erscheinen) und berührt keine der
Patch-Stellen.

| Nr | Was | Warum | Fällt weg wenn |
|---|---|---|---|
| 1 | `data-sc-kinetic` an einem Kind des Cue-Elements wird übernommen | Der Motor liest es nur vom Cue-Element selbst. Am Kind läuft es stumm ins Leere, und kein Screenshot zeigt einen Unterschied | [scroll-craft#1](https://github.com/nateherkai/scroll-craft/issues/1) behoben ist |
| 2 | `splitText()` verweigert Elemente mit Kindelementen | Die Routine setzt `el.textContent = ''` und löscht damit die Kinder. Aus Überschrift plus Absatz würden flache Spans, und im Screenshot sähe es nur nach falscher Schriftgröße aus | dito |

## Patch 1 im Detail

Nach dem Einsammeln der Cues läuft ein zweiter Durchgang über alle Elemente mit
`data-sc-kinetic`, die **kein** eigenes `data-sc-cue` tragen. Für jedes wird ein
zusätzlicher Cue angelegt, der das Fenster des nächsten Cue-Vorfahren erbt.

Ergebnis: der Träger blendet weiter als Ganzes ein, das Kind baut sich
zusätzlich Zeile für Zeile auf. Also das, was jemand erwartet, der es so
geschrieben hat.

Findet sich kein Cue-Vorfahr, kommt eine Warnung in die Konsole statt stiller
Wirkungslosigkeit.

**Die eigenen Patterns nutzen das nicht.** Sie setzen beide Attribute an
dasselbe Element, so wie `devices.md §5` es zeigt. Damit läuft ihr Markup auch
auf dem unveränderten Original. Der Patch ist ein Netz für von Hand
geschriebenes Markup, keine neue Schreibweise.

## Patch 2 im Detail, und die Falle darin

`splitText()` gibt `null` zurück, wenn das Element Kindelemente hat. Der
Aufrufer schaltet den Cue dann dauerhaft auf gewöhnliches Einblenden um.

**Eigene Split-Spans zählen nicht als Kinder.** Nach `document.fonts.ready`
zerlegt der Motor absichtlich ein zweites Mal, weil Zeilenumbrüche erst mit der
echten Schrift stimmen. Zu diesem Zeitpunkt stehen die Spans des ersten
Durchgangs noch im DOM.

Die erste Fassung des Wächters prüfte nur `el.children.length` und lehnte
deshalb genau diesen legitimen zweiten Durchgang ab. Weil es davon abhängt, ob
die Schrift vor oder nach dem ersten Sichtbarwerden geladen ist, war das ein
Wettlauf: mal blieb die Kinetik, mal war sie weg. Der Selektor lautet deshalb
`:scope > :not(.sc-split)`.

Gefunden beim Video-Test am 2026-08-25, nicht durch Nachdenken. Eine Warnung in
der Konsole auf einer Seite, auf der nichts falsch geschrieben war.

## Beim Abgleich mit dem Original

```bash
git fetch upstream
git diff upstream/main -- plugins/nateherk-design/skills/scroll-craft/engine/scrollcraft.js
```

`plugins/nateherk-design/` bleibt immer der unveränderte Stand. Wer den Motor
neu übernimmt, kopiert von dort nach `wp-plugin/th-scrollcraft/assets/` und
trägt die Patches aus dieser Liste erneut ein, sofern sie noch nötig sind.

Die Patches von Hand abzutippen ist der fehleranfällige Weg, sie sind zu 87
Zeilen überwiegend Kommentar. Besser als Diff gegen den alten Original-Stand
sichern und auf die neue Fassung auftragen:

```bash
git show <alter-abgleichstand>:plugins/nateherk-design/skills/scroll-craft/engine/scrollcraft.js > /tmp/orig_alt.js
diff -u /tmp/orig_alt.js assets/scrollcraft.js > /tmp/th-patches.diff   # muss 4 Hunks, 87 Zeilen sein
cp ../../plugins/nateherk-design/skills/scroll-craft/engine/scrollcraft.js assets/scrollcraft.js
patch assets/scrollcraft.js < /tmp/th-patches.diff
```

Danach muss `diff` zwischen neuem Original und `assets/scrollcraft.js` wieder
genau diese vier Hunks zeigen und sonst nichts. Zeigt er mehr, ist der Patch
verrutscht.

**`assets/scrollcraft.css` wird dabei nie mitkopiert.** Sie sieht aus wie die
Schwesterdatei zur Engine und ist es nicht: sie ist eine eigene Fassung gegen
`theme.json`, ohne globalen Reset, mit Tokens auf `var(--wp--preset--*)` und der
Sticky-Rettung für `.wp-site-blocks`. Nates `engine/scrollcraft.css` darüber zu
kopieren wirft all das weg, und weil beide Dateien gleich heißen und die Seite
danach noch scrollt, fällt es erst beim Blick auf die Farben auf. Beim Abgleich
am 2026-09-05 genau so passiert. Der Vergleich, der es zeigt:

```bash
grep -c "wp--preset" assets/scrollcraft.css   # muss 34 sein, nicht 0
```
