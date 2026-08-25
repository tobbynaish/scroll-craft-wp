#!/usr/bin/env bash
# scrollcraft-wp: Assets in die WordPress-Mediathek bringen.
#
#   ./mediathek.sh assets/                 ganzen Ordner
#   ./mediathek.sh assets/01.mp4 a.webp    einzelne Dateien
#   ./mediathek.sh --liste                 zeigt, was schon drin ist
#
# Warum der Umweg über /tmp auf dem Server und nicht der Upload über das
# Backend: Shared Hosting begrenzt upload_max_filesize oft auf 64 MB und die
# Laufzeit auf 30 Sekunden. Ein 1080p-Clip mit dichter Keyframe-Folge reisst
# beides. Liegt die Datei schon auf dem Server, registriert PHP sie nur noch,
# und keins der beiden Limits greift.
#
# Zugangsdaten kommen aus der Umgebung, nicht aus dieser Datei. Das Repo ist
# oeffentlich.
#
#   export SCWP_HOST="uXXXXXXXXX@1.2.3.4"
#   export SCWP_PORT=65002
#   export SCWP_KEY="$HOME/.ssh/id_ed25519"
#   export SCWP_WP="/home/uXXXXXXXXX/domains/staging.example.de/public_html"
#
# Alternativ eine Datei .scrollcraft-wp.env im Projektwurzelverzeichnis mit
# denselben Zeilen. Sie gehoert in .gitignore.
set -euo pipefail

HIER="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Konfiguration einlesen, falls vorhanden. Aufwaerts suchen, damit das Skript
# aus jedem Unterordner des Projekts laeuft.
suche_env() {
	local d="$PWD"
	while [ "$d" != "/" ]; do
		if [ -f "$d/.scrollcraft-wp.env" ]; then
			echo "$d/.scrollcraft-wp.env"
			return 0
		fi
		d="$(dirname "$d")"
	done
	return 1
}
if ENVDATEI="$(suche_env)"; then
	# shellcheck disable=SC1090
	set -a; . "$ENVDATEI"; set +a
	echo "Konfiguration: $ENVDATEI" >&2
fi

: "${SCWP_HOST:?SCWP_HOST fehlt. Siehe Kopf dieser Datei.}"
: "${SCWP_WP:?SCWP_WP fehlt. Siehe Kopf dieser Datei.}"
PORT="${SCWP_PORT:-22}"
KEY="${SCWP_KEY:-$HOME/.ssh/id_ed25519}"

SSH_CMD="ssh -p $PORT -i $KEY -o BatchMode=yes -o StrictHostKeyChecking=accept-new"
FERN="/tmp/scrollcraft-import"

wp_fern() {
	$SSH_CMD "$SCWP_HOST" "wp --path='$SCWP_WP' $*"
}

if [ "${1:-}" = "--liste" ]; then
	wp_fern "post list --post_type=attachment --fields=ID,post_title,post_mime_type --format=table"
	exit 0
fi

[ $# -gt 0 ] || { echo "Nichts angegeben. Aufruf: ./mediathek.sh <ordner|dateien...>" >&2; exit 1; }

# Dateiliste zusammenstellen.
DATEIEN=()
for arg in "$@"; do
	if [ -d "$arg" ]; then
		while IFS= read -r -d '' f; do DATEIEN+=("$f"); done \
			< <(find "$arg" -maxdepth 1 -type f \
				\( -iname '*.mp4' -o -iname '*.webm' -o -iname '*.webp' \
				   -o -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.png' \
				   -o -iname '*.avif' -o -iname '*.svg' \) -print0 | sort -z)
	elif [ -f "$arg" ]; then
		DATEIEN+=("$arg")
	else
		echo "uebersprungen, nicht gefunden: $arg" >&2
	fi
done

[ ${#DATEIEN[@]} -gt 0 ] || { echo "Keine passenden Dateien gefunden." >&2; exit 1; }

echo "→ ${#DATEIEN[@]} Dateien nach $SCWP_HOST:$FERN"
$SSH_CMD "$SCWP_HOST" "mkdir -p '$FERN'"
# Kein --info=NAME1: macOS liefert bis heute rsync 2.6.9 aus, und das kennt
# den Schalter nicht. Es bricht dann mit der kompletten Aufrufhilfe ab, was wie
# ein Fehler im Skript aussieht und keiner ist.
rsync -a -e "$SSH_CMD" "${DATEIEN[@]}" "$SCWP_HOST:$FERN/"

echo
printf '%-34s %-8s %s\n' "DATEI" "ID" "URL"
printf '%-34s %-8s %s\n' "----------------------------------" "--------" "---"

for f in "${DATEIEN[@]}"; do
	name="$(basename "$f")"
	# --porcelain gibt nur die ID aus. Ohne das muss man die Meldung parsen,
	# und die aendert sich zwischen wp-cli-Versionen.
	id="$(wp_fern "media import '$FERN/$name' --porcelain" 2>/dev/null | tr -d '\r' | tail -1)"
	case "$id" in
		''|*[!0-9]*)
			printf '%-34s %-8s %s\n' "$name" "FEHLER" "Import fehlgeschlagen"
			;;
		*)
			url="$(wp_fern "post get $id --field=guid" 2>/dev/null | tr -d '\r')"
			printf '%-34s %-8s %s\n' "$name" "$id" "$url"
			;;
	esac
done

echo
echo "→ Aufraeumen"
$SSH_CMD "$SCWP_HOST" "rm -rf '$FERN'"
echo "Fertig. Die IDs gehoeren ins Markup, nicht die URLs."
