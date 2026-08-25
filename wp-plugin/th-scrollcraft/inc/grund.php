<?php
/**
 * Heller oder dunkler Grund, je Seite wählbar.
 *
 * Enterprise-Auftritte werden hell gebaut, einzelne Landingpages vertragen
 * dunkel. Das ist eine Projektentscheidung und keine Eigenschaft des Themes,
 * also gehört sie an die Seite und nicht in die Stildatei.
 *
 * Gesetzt wird über das Beitragsmeta _th_scrollcraft_grund mit 'hell' oder
 * 'dunkel'. Voreinstellung ist hell, weil das die Farben des Themes
 * unverändert übernimmt.
 *
 *   wp post meta update <ID> _th_scrollcraft_grund dunkel
 *
 * WARUM DIE KLASSE AN DAS HTML-ELEMENT GEHT UND NICHT AN DEN BODY
 *
 * data-sc-drift schreibt --sc-canvas als Inline-Stil auf das html-Element,
 * damit der Seitengrund über die Akte hinweg die Farbe wandern kann. Stünde
 * die dunkle Palette auf body, würde sie diesen geerbten Wert wieder
 * überschreiben: body ist das nähere Element und gewinnt. Der Farbverlauf
 * bliebe dann auf einer dunklen Seite einfach stehen, ohne dass irgendwo
 * etwas nach Fehler aussieht.
 *
 * Auf html ist die Reihenfolge richtig herum. Ein Inline-Stil schlägt jede
 * Klassenregel am selben Element, also gewinnt der Driftwert, und ohne Drift
 * greift die Palette.
 *
 * @package th-scrollcraft
 */

defined( 'ABSPATH' ) || exit;

/**
 * Welcher Grund gilt auf dieser Seite.
 *
 * @return string 'hell' oder 'dunkel'.
 */
function th_scrollcraft_grund(): string {
	static $grund = null;

	if ( null !== $grund ) {
		return $grund;
	}

	$grund = 'hell';

	if ( is_singular() ) {
		$post = get_post();

		if ( $post instanceof WP_Post ) {
			$meta = (string) get_post_meta( $post->ID, '_th_scrollcraft_grund', true );

			if ( in_array( $meta, array( 'hell', 'dunkel' ), true ) ) {
				$grund = $meta;
			}
		}
	}

	/**
	 * Den Grund überschreiben, etwa für einen ganzen Inhaltstyp.
	 *
	 * @param string $grund 'hell' oder 'dunkel'.
	 */
	$grund = (string) apply_filters( 'th_scrollcraft_grund', $grund );

	return in_array( $grund, array( 'hell', 'dunkel' ), true ) ? $grund : 'hell';
}

/**
 * Die Klasse an das html-Element hängen.
 *
 * language_attributes() liefert normalerweise nur lang und dir. Trägt ein
 * anderes Plugin schon ein class-Attribut ein, wird ergänzt statt ein zweites
 * anzulegen, sonst gewinnt im Browser das erste und die Klasse geht verloren.
 *
 * @param string $ausgabe Bisherige Attribute.
 * @return string
 */
function th_scrollcraft_html_klasse( $ausgabe ) {
	if ( ! th_scrollcraft_needed() || 'dunkel' !== th_scrollcraft_grund() ) {
		return $ausgabe;
	}

	$ausgabe = (string) $ausgabe;

	if ( preg_match( '/\bclass\s*=\s*"([^"]*)"/', $ausgabe ) ) {
		return preg_replace( '/\bclass\s*=\s*"([^"]*)"/', 'class="$1 sc-dunkel"', $ausgabe, 1 );
	}

	return trim( $ausgabe . ' class="sc-dunkel"' );
}
add_filter( 'language_attributes', 'th_scrollcraft_html_klasse' );

/**
 * Denselben Zustand am Body, damit sich Regeln daran aufhängen lassen, die
 * keine Tokens setzen.
 *
 * @param string[] $klassen Bestehende Klassen.
 * @return string[]
 */
function th_scrollcraft_grund_body_class( array $klassen ): array {
	if ( th_scrollcraft_needed() ) {
		$klassen[] = 'sc-grund-' . th_scrollcraft_grund();
	}

	return $klassen;
}
add_filter( 'body_class', 'th_scrollcraft_grund_body_class' );

/**
 * Das Meta anmelden, damit wp-cli und die REST-Schnittstelle es setzen können.
 *
 * @return void
 */
function th_scrollcraft_grund_meta(): void {
	foreach ( array( 'page', 'post' ) as $typ ) {
		register_post_meta(
			$typ,
			'_th_scrollcraft_grund',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => 'hell',
				'show_in_rest'      => true,
				'description'       => 'Grund der Scroll-Seite: hell oder dunkel.',
				'sanitize_callback' => static fn( $w ): string => in_array( $w, array( 'hell', 'dunkel' ), true ) ? $w : 'hell',
				'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
			)
		);
	}
}
add_action( 'init', 'th_scrollcraft_grund_meta' );
