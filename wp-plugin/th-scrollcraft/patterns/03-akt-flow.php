<?php
/**
 * Title: Akt · Flow (normaler Abschnitt mit Aufdecken)
 * Slug: th-scrollcraft/akt-flow
 * Categories: sc-akte
 * Description: Der einzige Akt, der sich wie ein Dokument liest, und genau deshalb gehört einer davon in die Mitte. Text staffelt sich ein, das Bild wird seitlich aufgedeckt.
 * Keywords: flow, reveal, text, abschnitt, ruhe
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
<!-- wp:group {"metadata":{"name":"Akt · Flow","sc":{"act":"flow"}},"align":"full","className":"sc-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull sc-section">
<!-- wp:columns {"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center">
<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center">
<!-- wp:group {"metadata":{"sc":{"in":true,"stagger":70}},"className":"sc-stack","layout":{"type":"default"}} -->
<div class="wp-block-group sc-stack">
<!-- wp:heading {"className":"sc-display sc-display--lg"} -->
<h2 class="wp-block-heading sc-display sc-display--lg">Was sich ändert.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sc-body"} -->
<p class="sc-body">Zwei kurze Absätze. Mehr braucht dieser Beat nicht, er ist die Atempause zwischen zwei lauten Akten.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center">
<!-- wp:image {"metadata":{"sc":{"reveal":"left","revealAt":"0.18 0.62"}},"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="<?php echo $th_sc_platzhalter; ?>" alt="Beschreibe das Bild, nicht die Marke." width="1600" height="900"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
