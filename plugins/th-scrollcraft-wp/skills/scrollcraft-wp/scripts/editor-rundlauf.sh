#!/usr/bin/env bash
# Ueberlebt der Blockbaum das Speichern im Block-Editor?
#
# Die wichtigste Pruefung des ganzen Ports, weil an metadata.sc die komplette
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
URL="${SCWP_URL:-https://DEINE-DOMAIN}"

hole_inhalt() {
	ssh -p "$PORT" -i "$KEY" -o BatchMode=yes "$SCWP_HOST" \
		"cd '$SCWP_WP' && wp post get $ID --field=content" 2>/dev/null
}

# grep gibt 1 zurueck, wenn es nichts findet. Zusammen mit set -e und pipefail
# stirbt das Skript daran stumm, mitten in einer Zuweisung, ohne eine einzige
# Zeile Ausgabe. Genau das ist beim ersten echten Einsatz passiert. Das || true
# faengt den leeren Fall ab, damit aus "nichts gefunden" eine Meldung wird und
# kein Raetsel.
zaehle() {
	printf '%s' "$1" | grep -o "$2" | wc -l | tr -d ' ' || true
}

bericht() {
	local inhalt="$1"
	printf '  metadata.sc: %-4s wp:pattern: %-4s Zeichen: %s\n' \
		"$(zaehle "$inhalt" '"sc":{')" \
		"$(zaehle "$inhalt" 'wp:pattern')" \
		"${#inhalt}"
}

VOR_INHALT="$(hole_inhalt)"

if [ -z "$VOR_INHALT" ]; then
	echo "  Beitrag $ID hat keinen Inhalt, oder die SSH-Verbindung steht nicht."
	exit 2
fi

VOR_SC="$(zaehle "$VOR_INHALT" '"sc":{')"
VOR_PAT="$(zaehle "$VOR_INHALT" 'wp:pattern')"

echo
echo "  Beitrag $ID, vorher"
bericht "$VOR_INHALT"
echo

if [ "$VOR_SC" = "0" ] && [ "$VOR_PAT" != "0" ]; then
	echo "  Achtung: diese Seite besteht aus Pattern-Verweisen. Das metadata"
	echo "  steckt in den Pattern-Dateien, nicht im Beitrag. Ein Rundlauf hier"
	echo "  prueft, ob die Verweise das Speichern ueberstehen, aber NICHT, ob"
	echo "  metadata.sc erhalten bleibt. Dafuer eine Seite mit echtem Markup."
	echo
elif [ "$VOR_SC" = "0" ]; then
	echo "  Auf Beitrag $ID steht weder metadata.sc noch ein Pattern-Verweis."
	echo "  Falsche ID?"
	exit 2
fi

echo "  Jetzt bitte:"
echo "    1. $URL/wp-admin/post.php?post=$ID&action=edit"
echo "    2. Nichts aendern. Nur speichern."
echo "       (Ist der Knopf grau, ein Leerzeichen tippen und wieder loeschen.)"
echo
read -r -p "  Gespeichert? Dann Enter. " _

NACH_INHALT="$(hole_inhalt)"
NACH_SC="$(zaehle "$NACH_INHALT" '"sc":{')"
NACH_PAT="$(zaehle "$NACH_INHALT" 'wp:pattern')"

echo
echo "  Beitrag $ID, nachher"
bericht "$NACH_INHALT"
echo

ROT=0

if [ "$VOR_SC" != "$NACH_SC" ]; then
	echo "  ROT. metadata.sc: $VOR_SC vorher, $NACH_SC nachher."
	echo "  Der Editor wirft unsere Attribute weg. Ausweg in"
	echo "  references/ollie-bruecke.md, Abschnitt 2."
	ROT=1
fi

if [ "$VOR_PAT" != "$NACH_PAT" ]; then
	echo "  Hinweis: wp:pattern $VOR_PAT vorher, $NACH_PAT nachher."
	echo "  Der Editor hat die Verweise aufgeloest und das Markup fest"
	echo "  eingesetzt. Kein Fehler, aber die Seite haengt danach nicht mehr"
	echo "  an den Pattern-Dateien. Aenderungen am Pattern erreichen sie nicht"
	echo "  mehr."
fi

if [ "$ROT" = "1" ]; then
	exit 1
fi

echo "  GRUEN. Der Blockbaum uebersteht den Editor."
