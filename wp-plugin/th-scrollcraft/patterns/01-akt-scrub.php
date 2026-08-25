<?php
/**
 * Title: Akt · Scrub (Video am Scrollrad)
 * Slug: th-scrollcraft/akt-scrub
 * Categories: sc-akte
 * Description: Eine vorgerenderte Kamerafahrt läuft Bild für Bild unter der Hand des Lesers. Das Ankergerät, gehört an den Anfang. Höchstens zwei davon pro Seite.
 * Keywords: scrub, video, hero, kamerafahrt, scroll
 * Viewport Width: 1440
 *
 * @package th-scrollcraft
 */

defined( 'ABSPATH' ) || exit;

// Ein echter Platzhalter aus dem Plugin, keine data-URI. esc_url() verwirft
// data: , weil das Schema nicht in wp_allowed_protocols() steht, und ein
// core/image mit leerem src wird von WordPress komplett verworfen. Der Block
// verschwindet dann spurlos, und im Editor sieht es aus, als fehle er im
// Pattern.
$th_sc_platzhalter = esc_url( TH_SCROLLCRAFT_URL . 'assets/platzhalter.svg' );
?>
<!-- wp:group {"metadata":{"name":"Akt · Scrub","sc":{"act":"scrub","span":2.6,"dwell":0.34}},"align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull">
<!-- wp:group {"metadata":{"name":"Bühne","sc":{"stage":true}},"layout":{"type":"default"}} -->
<div class="wp-block-group">
<!-- wp:html -->
<img class="sc-stage__poster" src="<?php echo $th_sc_platzhalter; ?>" alt="" width="1600" height="900">
<video data-sc-scrub muted playsinline></video>
<div class="sc-scrim sc-scrim--lead" aria-hidden="true"></div>
<!-- /wp:html -->

<!-- wp:group {"metadata":{"name":"Text"},"className":"sc-copy sc-copy--lead","layout":{"type":"default"}} -->
<div class="wp-block-group sc-copy sc-copy--lead">
<!-- wp:heading {"level":1,"metadata":{"sc":{"cue":"0 0.78 0","kinetic":"lines"}},"className":"sc-display sc-display--xl"} -->
<h1 class="wp-block-heading sc-display sc-display--xl">Das Versprechen, in unter neun Wörtern.</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"metadata":{"sc":{"cue":"0.1 0.8"}},"className":"sc-body"} -->
<p class="sc-body">Ein klarer Satz. Zwanzig Wörter, höchstens.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
