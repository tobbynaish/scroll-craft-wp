<?php
/**
 * Die Brücke: Block-Metadaten werden zu data-sc-Attributen.
 *
 * Der Motor liest ausschließlich data-sc-* am gerenderten Markup. Gutenberg
 * hat kein Feld dafür, und ein eigener Blocktyp bräuchte einen Build-Prozess.
 *
 * Der Ausweg ist das metadata-Attribut. Jeder Core-Block darf es tragen, der
 * Editor reicht es unverändert durch, es taucht in keiner Oberfläche auf und
 * überlebt jede Bearbeitung. Im Pattern steht also:
 *
 *   <!-- wp:group {"metadata":{"sc":{"act":"scrub","span":2.6}}} -->
 *
 * und dieser Filter macht daraus am äußeren Tag:
 *
 *   <div class="wp-block-group" data-sc-act="scrub" data-sc-span="2.6">
 *
 * Geschrieben wird mit WP_HTML_Tag_Processor, nicht mit einer Ersetzung per
 * regulärem Ausdruck. Der Processor kennt die HTML-Grammatik, eine Ersetzung
 * rät. Bei verschachtelten Gruppen mit gleichem Klassennamen rät sie falsch.
 *
 * @package th-scrollcraft
 */

defined( 'ABSPATH' ) || exit;

/**
 * Erlaubte Schlüssel.
 *
 * Eine Whitelist, keine Durchreiche. Ohne sie könnte jeder Block über
 * metadata ein beliebiges Attribut an sein Wrapper-Tag schreiben, und das ist
 * ein Einfallstor, kein Feature. Der Wert legt fest, wie geprüft wird:
 *
 *   'flag'   vorhanden oder nicht, kein Wert
 *   'num'    Fließkommazahl
 *   'text'   kurzer Bezeichner
 *   'color'  Hex-Farbe
 *   'nums'   Folge aus Zahlen, durch Leerzeichen getrennt
 *   'url'    Verweis auf eine Datei in dieser Installation
 *
 * @var array<string,string>
 */
const TH_SC_KEYS = array(
	// Akte.
	'act'        => 'text',
	'span'       => 'num',
	'dwell'      => 'num',
	'drift'      => 'color',
	'clip-map'   => 'text',
	'lerp'       => 'num',
	'stage'      => 'flag',
	// Geräte.
	'scrub'      => 'flag',
	'sequence'   => 'text',
	'src'        => 'url',
	'src-mobile' => 'url',
	'poster'     => 'flag',
	'pan'        => 'num',
	'parallax'   => 'num',
	'cue'        => 'nums',
	'rise'       => 'num',
	'kinetic'    => 'text',
	'reveal'     => 'text',
	'reveal-at'  => 'nums',
	'count'      => 'nums',
	'count-at'   => 'nums',
	'in'         => 'flag',
	'stagger'    => 'num',
	'progress'   => 'flag',
	// Zeiger.
	'tilt'       => 'num',
	'magnet'     => 'num',
	'spotlight'  => 'flag',
	// Worldflight.
	'mode'       => 'text',
	'seam'       => 'num',
	'world'      => 'flag',
	'world-copy' => 'flag',
	'spacer'     => 'flag',
	'segment'    => 'flag',
	'w'          => 'num',
	'linger'     => 'num',
	'waypoint'   => 'text',
	'copy'       => 'flag',
	'window'     => 'text',
);

/**
 * camelCase aus dem Editor auf die Schreibweise des Motors bringen.
 *
 * Im JSON steht srcMobile, der Motor liest data-sc-src-mobile. Beide
 * Schreibweisen sind erlaubt, damit von Hand geschriebenes Markup nicht an
 * einem Bindestrich scheitert.
 *
 * @param string $key Schlüssel aus dem metadata-Objekt.
 * @return string Schlüssel in Bindestrich-Schreibweise.
 */
function th_sc_key_to_attr( string $key ): string {
	$key = preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', $key );

	return strtolower( (string) $key );
}

/**
 * Einen Wert gegen seinen Typ prüfen.
 *
 * @param mixed  $value Rohwert aus dem metadata-Objekt.
 * @param string $type  Typ aus TH_SC_KEYS.
 * @return string|bool|null Geprüfter Wert, true für ein Flag, null bei Ablehnung.
 */
function th_sc_clean_value( $value, string $type ) {
	switch ( $type ) {
		case 'flag':
			return false === $value || '' === $value || null === $value ? null : true;

		case 'num':
			return is_numeric( $value ) ? (string) ( 0 + $value ) : null;

		case 'text':
			$value = is_string( $value ) ? trim( $value ) : '';

			return preg_match( '#^[a-z0-9 ._/{}-]{1,64}$#i', $value ) ? $value : null;

		case 'color':
			$value = is_string( $value ) ? trim( $value ) : '';

			return preg_match( '/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $value ) ? $value : null;

		case 'nums':
			// Zahlenfolge, wie der Motor sie liest: "0.14 0.86" oder "0 4200".
			// Aus dem Editor darf sie auch als Array kommen.
			if ( is_array( $value ) ) {
				$value = implode( ' ', $value );
			}
			$value = is_string( $value ) ? trim( $value ) : ( is_numeric( $value ) ? (string) $value : '' );

			return preg_match( '/^-?[0-9.,]+(?: -?[0-9.,]+){0,3}$/', $value ) ? $value : null;

		case 'url':
			return th_sc_clean_media_url( $value );
	}

	return null;
}

/**
 * Einen Medienverweis auf eine sichere URL bringen.
 *
 * Erlaubt sind zwei Formen. Eine Zahl wird als Anhang-ID gelesen und über die
 * Mediathek aufgelöst, das ist der bevorzugte Weg: die Seite überlebt dann
 * einen Domainwechsel. Eine Zeichenkette muss auf dieselbe Installation
 * zeigen. Fremde Hosts werden abgelehnt, weil der Motor die Datei per fetch
 * holt und in ein Blob legt, und das scheitert an CORS ohnehin.
 *
 * @param mixed $value Anhang-ID oder URL.
 * @return string|null Absolute URL oder null.
 */
function th_sc_clean_media_url( $value ): ?string {
	if ( is_numeric( $value ) ) {
		$url = wp_get_attachment_url( (int) $value );

		return is_string( $url ) ? $url : null;
	}

	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		return null;
	}

	$value = trim( $value );

	// Relativer Pfad, etwa /wp-content/uploads/2026/08/hero.mp4.
	if ( str_starts_with( $value, '/' ) && ! str_starts_with( $value, '//' ) ) {
		return esc_url_raw( home_url( $value ) );
	}

	$host    = wp_parse_url( $value, PHP_URL_HOST );
	$mine    = wp_parse_url( home_url(), PHP_URL_HOST );
	$erlaubt = apply_filters( 'th_scrollcraft_allowed_media_hosts', array( $mine ) );

	if ( ! $host || ! in_array( $host, (array) $erlaubt, true ) ) {
		return null;
	}

	return esc_url_raw( $value );
}

/**
 * Das metadata-Objekt eines Blocks in data-sc-Attribute übersetzen.
 *
 * @param string $html  Gerendertes Block-Markup.
 * @param array  $block Geparster Block.
 * @return string Markup, gegebenenfalls mit Attributen am äußeren Tag.
 */
function th_sc_render_block( string $html, array $block ): string {
	$sc = $block['attrs']['metadata']['sc'] ?? null;

	if ( ! is_array( $sc ) || array() === $sc || '' === trim( $html ) ) {
		return $html;
	}

	$p = new WP_HTML_Tag_Processor( $html );

	// next_tag springt über führende Leerzeichen und Zeilenumbrüche, die
	// serialisierte Blöcke fast immer mitbringen. Findet es nichts, besteht
	// das Markup nur aus Text, und dann gibt es nichts zu beschriften.
	if ( ! $p->next_tag() ) {
		return $html;
	}

	$geschrieben = array();

	foreach ( $sc as $key => $value ) {
		$attr = th_sc_key_to_attr( (string) $key );

		if ( ! isset( TH_SC_KEYS[ $attr ] ) ) {
			continue;
		}

		$clean = th_sc_clean_value( $value, TH_SC_KEYS[ $attr ] );

		if ( null === $clean ) {
			continue;
		}

		$p->set_attribute( 'data-sc-' . $attr, $clean );
		$geschrieben[ $attr ] = $clean;
	}

	if ( array() === $geschrieben ) {
		return $html;
	}

	// Klassen gleich mitgeben, damit die Stildatei greift, ohne für jedes
	// Gerät einen Attribut-Selektor zu brauchen. Der Motor setzt .sc-stage
	// und .sc-act--pinned erst beim Mounten, also nach dem ersten Anstrich,
	// und das wäre ein sichtbares Springen.
	//
	// Gelesen wird aus $geschrieben, nicht aus $sc. Sonst bekäme ein Akt,
	// dessen Wert die Prüfung nicht bestanden hat, trotzdem die Klasse, und
	// dann steht .sc-act ohne data-sc-act da: die Stildatei greift, der Motor
	// findet nichts, und der Akt sieht aus wie eine Bühne, die nicht klebt.
	if ( isset( $geschrieben['stage'] ) ) {
		$p->add_class( 'sc-stage' );
	}
	if ( isset( $geschrieben['act'] ) ) {
		$p->add_class( 'sc-act' );
		if ( in_array( $geschrieben['act'], array( 'scrub', 'pin', 'pan' ), true ) ) {
			$p->add_class( 'sc-act--pinned' );
		}
	}

	th_scrollcraft_mark_used();

	return $p->get_updated_html();
}
add_filter( 'render_block', 'th_sc_render_block', 10, 2 );
