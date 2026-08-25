#!/usr/bin/env bash
# Ueberlebt metadata.sc das Speichern im Block-Editor?
#
# Die wichtigste Pruefung des ganzen Ports, weil an metadata die komplette
# Attribut-Bruecke haengt. Sie laeuft einmal je WordPress-Hauptversion, nicht
# bei jedem Bau, und sie braucht einen angemeldeten Menschen: Gutenberg
# serialisiert den Blockbaum im Browser neu, nicht auf dem Server.
#
#   ./editor-rundlauf.sh <post-id>
#
# Zugang kommt aus .scrollcraft-wp.env im Projektwurzelverzeichnis.
set -euo pipefail

suche_env() {
	local d="$PWD"
	while [ "$d" != "/" ]; do
		[ -f "$d/.scrollcraft-wp.env" ] && { echo "$d/.scrollcraft-wp.env"; return 0; }
		d="$(dirname "$d")"
	done
	return 1
}
if ENVDATEI="$(suche_env)"; then
	# shellcheck disable=SC1090
	set -a; . "$ENVDATEI"; set +a
fi

: "${SCWP_HOST:?SCWP_HOST fehlt}"
: "${SCWP_WP:?SCWP_WP fehlt}"
ID="${1:?Aufruf: ./editor-rundlauf.sh <post-id>}"
PORT="${SCWP_PORT:-22}"
KEY="${SCWP_KEY:-$HOME/.ssh/id_ed25519}"

zaehle() {
	ssh -p "$PORT" -i "$KEY" -o BatchMode=yes "$SCWP_HOST" \
		"cd '$SCWP_WP' && wp post get $ID --field=content" 2>/dev/null \
		| grep -o '"sc":{' | wc -l | tr -d ' '
}

VORHER="$(zaehle)"

if [ "$VORHER" = "0" ]; then
	echo "Auf Beitrag $ID steht kein Scrollcraft-Markup. Falsche ID?"
	exit 2
fi

echo
echo "  Vorher: $VORHER Bloecke mit metadata.sc"
echo
echo "  Jetzt bitte:"
echo "    1. ${SCWP_URL:-https://DEINE-DOMAIN}/backend  aufrufen und anmelden"
echo "    2. Seite $ID im Block-Editor oeffnen:"
echo "       ${SCWP_URL:-https://DEINE-DOMAIN}/wp-admin/post.php?post=$ID&action=edit"
echo "    3. Nichts aendern. Nur speichern."
echo "       (Ist der Knopf grau, einmal ein Leerzeichen tippen und wieder loeschen.)"
echo
read -r -p "  Gespeichert? Dann Enter. " _

NACHHER="$(zaehle)"
echo
echo "  Nachher: $NACHHER"
echo

if [ "$VORHER" = "$NACHHER" ]; then
	echo "  GRUEN. metadata ueberlebt den Editor, die Bruecke traegt."
	exit 0
fi

echo "  ROT. $(( VORHER - NACHHER )) Bloecke haben ihr metadata.sc verloren."
echo "  Der Ausweg steht in references/ollie-bruecke.md, Abschnitt 2."
exit 1
