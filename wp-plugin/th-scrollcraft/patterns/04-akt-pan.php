<?php
/**
 * Title: Akt · Pan (waagerechte Schiene)
 * Slug: th-scrollcraft/akt-pan
 * Categories: sc-akte
 * Description: Seitliche Fahrt statt senkrechter. Waagerecht liest sich wie Auswahl, senkrecht wie Argument. Für den Beat, an dem es etwas zu vergleichen gibt.
 * Keywords: pan, schiene, horizontal, auswahl, karten
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
<!-- wp:group {"metadata":{"name":"Akt · Pan","sc":{"act":"pan","span":3.2}},"align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull">
<!-- wp:group {"metadata":{"name":"Bühne","sc":{"stage":true}},"layout":{"type":"default"}} -->
<div class="wp-block-group">
<!-- wp:group {"metadata":{"name":"Schiene","sc":{"pan":0.06}},"className":"sc-rail","layout":{"type":"default"}} -->
<div class="wp-block-group sc-rail">
<!-- wp:group {"className":"sc-rail__lead sc-stack","layout":{"type":"default"}} -->
<div class="wp-block-group sc-rail__lead sc-stack">
<!-- wp:heading {"className":"sc-display sc-display--md"} -->
<h2 class="wp-block-heading sc-display sc-display--md">Die Auswahl.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sc-body"} -->
<p class="sc-body">Eine Zeile dazu, wie man wählt.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"sc-rail__item","layout":{"type":"default"}} -->
<div class="wp-block-group sc-rail__item">
<!-- wp:group {"metadata":{"sc":{"tilt":6}},"layout":{"type":"default"}} -->
<div class="wp-block-group">
<!-- wp:image {"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="<?php echo $th_sc_platzhalter; ?>" alt="" width="1600" height="900"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Eins</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sc-body"} -->
<p class="sc-body">Ein Satz.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"sc-rail__item","layout":{"type":"default"}} -->
<div class="wp-block-group sc-rail__item">
<!-- wp:group {"metadata":{"sc":{"tilt":6}},"layout":{"type":"default"}} -->
<div class="wp-block-group">
<!-- wp:image {"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="<?php echo $th_sc_platzhalter; ?>" alt="" width="1600" height="900"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Zwei</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sc-body"} -->
<p class="sc-body">Ein Satz.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"sc-rail__item","layout":{"type":"default"}} -->
<div class="wp-block-group sc-rail__item">
<!-- wp:group {"metadata":{"sc":{"tilt":6}},"layout":{"type":"default"}} -->
<div class="wp-block-group">
<!-- wp:image {"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="<?php echo $th_sc_platzhalter; ?>" alt="" width="1600" height="900"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Drei</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sc-body"} -->
<p class="sc-body">Ein Satz.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
