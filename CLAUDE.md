# scroll-craft-wp

Fork von `nateherkai/scroll-craft`, angepasst auf OllieWP-Blockthemes.

## Auto-Commit freigegeben

**Stand 2026-08-25, freigegeben von Tobias.** In diesem Repo darf ohne Rückfrage
committet werden. Grund: es ist ein Eigen-Projekt in Beta, einziger Nutzer ist
Tobias, nichts davon läuft produktiv bei einem Kunden.

Regeln, die trotzdem gelten:

- **Nur auf `olliewp`**, nie auf `main`. `main` bleibt am Upstream ausgerichtet,
  damit Nates Updates weiter mergebar sind.
- **Kein Force-Push, kein Hard-Reset, kein `git clean -fd`.**
- **Kein Push, solange der PHP-Lint nicht durch ist.** Der läuft über SSH gegen
  Staging, weil lokal kein PHP installiert ist.
- **Nie Zugangsdaten committen.** `.scrollcraft-wp.env` steht in `.gitignore`,
  die Vorlage daneben ist die einzige Datei mit Platzhaltern.
- Sobald das Ding bei einem Kunden produktiv läuft, fällt diese Freigabe weg
  und die globale Regel gilt wieder.

## Aufbau

| Pfad | Was |
|---|---|
| `wp-plugin/th-scrollcraft/` | Das WordPress-Plugin. Motor, Attribut-Brücke, Patterns |
| `plugins/th-scrollcraft-wp/` | Der Claude-Skill `scrollcraft-wp` |
| `plugins/nateherk-design/` | Nates Original, unverändert. Nicht bearbeiten |

## Ohne lokales PHP arbeiten

Auf diesem Rechner ist kein PHP installiert. Lint läuft deshalb über SSH gegen
den Staging-Server:

```bash
. .scrollcraft-wp.env
ssh -p "$SCWP_PORT" -i "$SCWP_KEY" "$SCWP_HOST" 'php -l' < datei.php
```

Host, Port und Pfad stehen in `.scrollcraft-wp.env`. Diese Datei steht in
`.gitignore` und gehört nie ins Repo, weil das Repo öffentlich ist. Die Vorlage
daneben enthält nur Platzhalter.

Deploy nach Staging über `deploy.sh all` im Projektordner des jeweiligen
Auftritts. Das Plugin hängt dort als Symlink.

## Was beim Bearbeiten leicht schiefgeht

1. **`esc_url()` verwirft `data:`-URIs.** Ein `core/image` mit dadurch leerem
   `src` wird von WordPress komplett verworfen, der Block verschwindet spurlos.
2. **Eine Klasse mit `height` am selben Element wie `.sc-stage`** überschreibt
   deren Höhe, weil gleiche Spezifität. Die Bühne wird so hoch wie der Akt und
   hat keinen Weg mehr zu kleben.
3. **`overflow: hidden` über einem Akt tötet `sticky` lautlos.** Immer `clip`.
4. **Tokens gehören auf `:root` oder `html`, nie auf `body`.** `data-sc-drift`
   schreibt `--sc-canvas` per Inline-Stil auf das html-Element, und eine
   Definition auf `body` würde den geerbten Driftwert wieder überschreiben.
5. **`data-sc-kinetic` muss am selben Element sitzen wie `data-sc-cue`.**

## Verifikation

```bash
node plugins/th-scrollcraft-wp/skills/scrollcraft-wp/scripts/probe.mjs \
  --url "$SCWP_URL/SEITE/?bypass_code=CODE"
```

Steht der Auftritt hinter Hostingers Wartungsmodus, landet ohne `bypass_code`
jeder Browser auf der Coming-Soon-Seite, während `wp eval` über SSH alles grün
meldet. Den Code liefert `wp option get hostinger_tools --format=json`.
