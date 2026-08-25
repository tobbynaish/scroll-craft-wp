<?php
/**
 * Die Bausteine als Patterns.
 *
 * WordPress registriert Patterns aus einem patterns-Verzeichnis nur bei
 * Themes, nicht bei Plugins. Also wird hier von Hand geladen, mit demselben
 * Dateikopf wie im Theme, damit dieselben Dateien in beiden Welten laufen.
 *
 * Zwei Kategorien, mehr braucht es nicht:
 *   sc-akte     einzelne Akte, das Vokabular
 *   sc-seiten   ganze Seitengerüste
 *
 * @package th-scrollcraft
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kategorien anmelden.
 *
 * @return void
 */
function th_scrollcraft_pattern_kategorien(): void {
	register_block_pattern_category(
		'sc-akte',
		array(
			'label'       => __( 'Scrollcraft — Akte', 'th-scrollcraft' ),
			'description' => __( 'Einzelne Scroll-Akte. Ein Akt ist ein Beat der Reise.', 'th-scrollcraft' ),
		)
	);

	register_block_pattern_category(
		'sc-seiten',
		array(
			'label'       => __( 'Scrollcraft — Seiten', 'th-scrollcraft' ),
			'description' => __( 'Komplette Scroll-Seiten als Startgerüst.', 'th-scrollcraft' ),
		)
	);
}
add_action( 'init', 'th_scrollcraft_pattern_kategorien', 9 );

/**
 * Den Dateikopf eines Patterns lesen.
 *
 * @param string $datei Absoluter Pfad.
 * @return array<string,string>
 */
function th_scrollcraft_pattern_kopf( string $datei ): array {
	return get_file_data(
		$datei,
		array(
			'title'         => 'Title',
			'slug'          => 'Slug',
			'description'   => 'Description',
			'categories'    => 'Categories',
			'keywords'      => 'Keywords',
			'viewportWidth' => 'Viewport Width',
			'blockTypes'    => 'Block Types',
			'inserter'      => 'Inserter',
		)
	);
}

/**
 * Alle Patterns aus dem Plugin registrieren.
 *
 * @return void
 */
function th_scrollcraft_patterns_laden(): void {
	$dateien = glob( TH_SCROLLCRAFT_DIR . 'patterns/*.php' );

	if ( ! is_array( $dateien ) ) {
		return;
	}

	foreach ( $dateien as $datei ) {
		$kopf = th_scrollcraft_pattern_kopf( $datei );

		if ( '' === $kopf['slug'] || '' === $kopf['title'] ) {
			continue;
		}

		ob_start();
		include $datei;
		$inhalt = (string) ob_get_clean();

		if ( '' === trim( $inhalt ) ) {
			continue;
		}

		$args = array(
			'title'      => $kopf['title'],
			'content'    => $inhalt,
			'source'     => 'plugin',
			'categories' => array_filter( array_map( 'trim', explode( ',', $kopf['categories'] ) ) ),
		);

		if ( '' !== $kopf['description'] ) {
			$args['description'] = $kopf['description'];
		}
		if ( '' !== $kopf['keywords'] ) {
			$args['keywords'] = array_filter( array_map( 'trim', explode( ',', $kopf['keywords'] ) ) );
		}
		if ( '' !== $kopf['viewportWidth'] ) {
			$args['viewportWidth'] = (int) $kopf['viewportWidth'];
		}
		if ( '' !== $kopf['blockTypes'] ) {
			$args['blockTypes'] = array_filter( array_map( 'trim', explode( ',', $kopf['blockTypes'] ) ) );
		}
		if ( 'no' === strtolower( trim( $kopf['inserter'] ) ) ) {
			$args['inserter'] = false;
		}

		register_block_pattern( $kopf['slug'], $args );
	}
}
add_action( 'init', 'th_scrollcraft_patterns_laden', 11 );
