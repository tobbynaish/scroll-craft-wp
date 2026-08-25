<?php
/**
 * Was der Editor können muss, damit Scrollcraft-Markup ihn überlebt.
 *
 * @package th-scrollcraft
 */

defined( 'ABSPATH' ) || exit;

/**
 * Blöcke, ohne die kein Akt gebaut werden kann.
 *
 * Kuratierte Inserter sind gut, und dieses Projekt hat einen. Aber wenn
 * core/html fehlt, lässt sich die Bühnen-Ebene nicht anlegen, und der Fehler
 * sieht im Editor aus wie ein kaputtes Pattern.
 *
 * @var string[]
 */
const TH_SC_PFLICHTBLOECKE = array(
	'core/group',
	'core/html',
	'core/heading',
	'core/paragraph',
	'core/image',
	'core/video',
	'core/buttons',
	'core/button',
	'core/columns',
	'core/column',
	'core/pattern',
	'core/block',
);

/**
 * Die Pflichtblöcke zur erlaubten Liste dazulegen.
 *
 * Läuft spät, damit eine bestehende Whitelist zuerst greift und dieser Filter
 * nur ergänzt statt sie zu ersetzen. Eine offene Liste (true) bleibt offen.
 *
 * @param bool|array $erlaubt Bisheriger Wert.
 * @return bool|array
 */
function th_scrollcraft_allowed_blocks( $erlaubt ) {
	if ( ! is_array( $erlaubt ) ) {
		return $erlaubt;
	}

	return array_values( array_unique( array_merge( $erlaubt, TH_SC_PFLICHTBLOECKE ) ) );
}
add_filter( 'allowed_block_types_all', 'th_scrollcraft_allowed_blocks', 50 );

/**
 * Das Kennzeichen anmelden.
 *
 * Über die REST-Schnittstelle sichtbar, damit wp-cli und das Import-Skript es
 * setzen können, ohne den Editor zu öffnen.
 *
 * @return void
 */
function th_scrollcraft_meta_anmelden(): void {
	foreach ( array( 'page', 'post' ) as $typ ) {
		register_post_meta(
			$typ,
			'_th_scrollcraft',
			array(
				'type'          => 'boolean',
				'single'        => true,
				'default'       => false,
				'show_in_rest'  => true,
				'description'   => 'Motor auf dieser Seite laden, auch wenn die Erkennung im Inhalt nichts findet.',
				'auth_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
			)
		);
	}
}
add_action( 'init', 'th_scrollcraft_meta_anmelden' );

/**
 * Die Stildatei auch im Editor laden.
 *
 * Ein Akt sieht im Editor nie aus wie im Frontend, weil dort weder gescrollt
 * noch gepinnt wird. Aber ohne die Stildatei sieht er aus wie ein Stapel
 * nackter Gruppen, und dann räumt jemand auf, was in Ordnung war.
 *
 * @return void
 */
function th_scrollcraft_editor_styles(): void {
	add_editor_style( TH_SCROLLCRAFT_URL . 'assets/scrollcraft-editor.css' );
}
add_action( 'after_setup_theme', 'th_scrollcraft_editor_styles', 20 );
