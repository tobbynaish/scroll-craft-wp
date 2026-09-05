<?php
/**
 * Plugin Name:       TH Scrollcraft
 * Plugin URI:        https://github.com/tobbynaish/scroll-craft-wp
 * Description:       Der Scrollcraft-Motor für WordPress-Blockthemes. Übersetzt Block-Metadaten in data-sc-Attribute, lädt Motor und Stildatei nur dort, wo sie gebraucht werden, und bringt die Bausteine als Patterns mit. Gebaut gegen OllieWP 1.6.1.
 * Version:           0.2.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Tobias Herold
 * Author URI:        https://tobiasherold.de
 * License:           MIT
 * Text Domain:       th-scrollcraft
 *
 * Der Motor stammt von Nate Herk (nateherkai/scroll-craft, MIT), übernommen bis
 * auf die zwei Fehlerkorrekturen in MOTOR-PATCHES.md. Angepasst ist alles darum
 * herum: das Laden, die Attribut-Brücke, die Stildatei gegen theme.json und die
 * Patterns.
 *
 * Warum ein Plugin und nicht das Child-Theme: der Motor treibt Inhalte, und
 * Inhalte überleben einen Theme-Wechsel. Der Motor muss es auch.
 *
 * @package th-scrollcraft
 */

defined( 'ABSPATH' ) || exit;

define( 'TH_SCROLLCRAFT_VERSION', '0.2.0' );
define( 'TH_SCROLLCRAFT_DIR', plugin_dir_path( __FILE__ ) );
define( 'TH_SCROLLCRAFT_URL', plugin_dir_url( __FILE__ ) );

// WP_HTML_Tag_Processor kam mit 6.2. Ohne ihn gibt es keine Attribut-Brücke,
// und eine Ersetzung per regulärem Ausdruck ist hier kein Ersatz, sondern ein
// stiller Fehler bei jeder verschachtelten Gruppe.
if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p><strong>TH Scrollcraft</strong> braucht WordPress 6.2 oder neuer.</p></div>';
		}
	);

	return;
}

require_once TH_SCROLLCRAFT_DIR . 'inc/enqueue.php';
require_once TH_SCROLLCRAFT_DIR . 'inc/grund.php';
require_once TH_SCROLLCRAFT_DIR . 'inc/render.php';
require_once TH_SCROLLCRAFT_DIR . 'inc/patterns.php';
require_once TH_SCROLLCRAFT_DIR . 'inc/editor.php';
