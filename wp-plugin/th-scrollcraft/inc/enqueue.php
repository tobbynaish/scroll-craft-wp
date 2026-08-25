<?php
/**
 * Wann der Motor geladen wird, und wann nicht.
 *
 * Motor plus Stildatei sind rund 40 KB. Auf einer Seite ohne Scrollcraft
 * haben sie nichts verloren, also wird erkannt statt pauschal geladen.
 *
 * Erkannt wird in dieser Reihenfolge:
 *
 *   1. Seitenvorlage traegt scrollcraft im Namen
 *   2. Beitragsmeta _th_scrollcraft ist gesetzt
 *   3. Der Inhalt enthaelt "sc":{ oder data-sc-
 *   4. Filter th_scrollcraft_enqueue sagt ja
 *
 * Punkt 3 greift auch bei rohem HTML in einem core/html-Block, und das ist
 * der Weg, auf dem die Buehnen-Ebene gebaut wird.
 *
 * @package th-scrollcraft
 */

defined( 'ABSPATH' ) || exit;

/**
 * Merken, dass auf dieser Seite tatsaechlich Scrollcraft-Markup gerendert wurde.
 *
 * Der render_block-Filter ruft das auf. Es ist das Sicherheitsnetz fuer den
 * Fall, dass die Erkennung im Kopfbereich danebenlag, etwa weil das Markup
 * aus einem synchronisierten Pattern kam.
 *
 * @return void
 */
function th_scrollcraft_mark_used(): void {
	$GLOBALS['th_scrollcraft_used'] = true;
}

/**
 * Braucht diese Anfrage den Motor?
 *
 * @return bool
 */
function th_scrollcraft_needed(): bool {
	static $antwort = null;

	if ( null !== $antwort ) {
		return $antwort;
	}

	$antwort = false;

	if ( is_singular() ) {
		$post = get_post();

		if ( $post instanceof WP_Post ) {
			$vorlage = (string) get_page_template_slug( $post );

			if ( str_contains( $vorlage, 'scrollcraft' ) ) {
				$antwort = true;
			} elseif ( get_post_meta( $post->ID, '_th_scrollcraft', true ) ) {
				$antwort = true;
			} else {
				$inhalt  = (string) $post->post_content;
				$antwort = str_contains( $inhalt, '"sc":{' ) || str_contains( $inhalt, 'data-sc-' );
			}
		}
	}

	/**
	 * Letztes Wort ueber das Laden des Motors.
	 *
	 * @param bool $antwort Ergebnis der Erkennung.
	 */
	$antwort = (bool) apply_filters( 'th_scrollcraft_enqueue', $antwort );

	return $antwort;
}

/**
 * Version einer Datei aus ihrem Aenderungsdatum.
 *
 * Hostinger liefert statische Dateien mit langer Haltbarkeit aus. Ohne diesen
 * Wert sieht der Browser nach einem Deploy die alte Datei, und man sucht den
 * Fehler im Markup, wo keiner ist.
 *
 * @param string $rel Pfad relativ zum Plugin-Verzeichnis.
 * @return string
 */
function th_scrollcraft_ver( string $rel ): string {
	$datei = TH_SCROLLCRAFT_DIR . $rel;

	return file_exists( $datei ) ? (string) filemtime( $datei ) : TH_SCROLLCRAFT_VERSION;
}

/**
 * Stildatei und Motor einreihen.
 *
 * @return void
 */
function th_scrollcraft_enqueue(): void {
	if ( is_admin() || ! th_scrollcraft_needed() ) {
		return;
	}

	// Die Stildatei haengt am Theme-Stylesheet, damit sie danach kommt und
	// eigene Korrekturen des Childs sie nicht aus Versehen ueberschreiben.
	$abhaengig = wp_style_is( 'th-swiss-style', 'registered' ) ? array( 'th-swiss-style' ) : array();

	wp_enqueue_style(
		'th-scrollcraft',
		TH_SCROLLCRAFT_URL . 'assets/scrollcraft.css',
		$abhaengig,
		th_scrollcraft_ver( 'assets/scrollcraft.css' )
	);

	wp_enqueue_script(
		'th-scrollcraft',
		TH_SCROLLCRAFT_URL . 'assets/scrollcraft.js',
		array(),
		th_scrollcraft_ver( 'assets/scrollcraft.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	wp_add_inline_script( 'th-scrollcraft', th_scrollcraft_mount_script() );
}
add_action( 'wp_enqueue_scripts', 'th_scrollcraft_enqueue', 20 );

/**
 * Das Startskript.
 *
 * Ein aufgeschobenes Skript laeuft vor DOMContentLoaded, ein spaet
 * nachgeladenes danach. Beide Faelle werden bedient, und ein zweites Mounten
 * wird verhindert, weil sonst jeder Akt zwei Beobachter bekaeme.
 *
 * @return string
 */
function th_scrollcraft_mount_script(): string {
	return <<<'JS'
(function () {
  function los() {
    if (!window.ScrollCraft || window.ScrollCraft.instances.length) return;
    ScrollCraft.mount(document.body);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', los);
  } else {
    los();
  }
})();
JS;
}

/**
 * Body-Klasse.
 *
 * Jede Regel der Stildatei haengt an .sc-page. Ohne die Klasse veraendert die
 * Datei nichts, und genau das soll sie auf einer Seite ohne Scrollcraft auch
 * nicht.
 *
 * @param string[] $klassen Bestehende Klassen.
 * @return string[]
 */
function th_scrollcraft_body_class( array $klassen ): array {
	if ( th_scrollcraft_needed() ) {
		$klassen[] = 'sc-page';
	}

	return $klassen;
}
add_filter( 'body_class', 'th_scrollcraft_body_class' );

/**
 * Sicherheitsnetz.
 *
 * Wenn Markup gerendert wurde, der Motor aber nicht geladen ist, lag die
 * Erkennung daneben. Dann kommt beides im Fussbereich nach. Das flackert
 * einmal, ist aber besser als eine Seite, auf der nichts passiert, und die
 * Warnung in der Konsole sagt, wo nachzubessern ist.
 *
 * @return void
 */
function th_scrollcraft_late_rescue(): void {
	if ( empty( $GLOBALS['th_scrollcraft_used'] ) ) {
		return;
	}
	if ( wp_script_is( 'th-scrollcraft', 'done' ) || wp_script_is( 'th-scrollcraft', 'enqueued' ) ) {
		return;
	}

	wp_enqueue_style(
		'th-scrollcraft',
		TH_SCROLLCRAFT_URL . 'assets/scrollcraft.css',
		array(),
		th_scrollcraft_ver( 'assets/scrollcraft.css' )
	);
	wp_enqueue_script(
		'th-scrollcraft',
		TH_SCROLLCRAFT_URL . 'assets/scrollcraft.js',
		array(),
		th_scrollcraft_ver( 'assets/scrollcraft.js' ),
		array( 'in_footer' => true )
	);

	// Die Klasse fehlt hier am Body, weil body_class laengst durch ist.
	wp_add_inline_script(
		'th-scrollcraft',
		"document.body.classList.add('sc-page');\n"
		. "console.warn('[th-scrollcraft] Motor kam zu spaet. Setze das Postmeta _th_scrollcraft oder die Seitenvorlage page-scrollcraft.');",
		'before'
	);
	wp_add_inline_script( 'th-scrollcraft', th_scrollcraft_mount_script() );
}
add_action( 'wp_footer', 'th_scrollcraft_late_rescue', 5 );
