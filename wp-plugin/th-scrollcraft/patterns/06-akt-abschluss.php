<?php
/**
 * Title: Akt · Abschluss (Aufforderung, die stehen bleibt)
 * Slug: th-scrollcraft/akt-abschluss
 * Categories: sc-akte
 * Description: Der letzte Block der Seite. Kurze Spanne, einwertige Cues, damit nichts ausblendet bevor die Seite endet. Die Fläche reagiert auf den Zeiger, der Knopf zieht ihn an.
 * Keywords: abschluss, cta, close, aufforderung, ende
 * Viewport Width: 1440
 *
 * @package th-scrollcraft
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"metadata":{"name":"Akt · Abschluss","sc":{"act":"pin","span":1.15}},"align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull">
<!-- wp:group {"metadata":{"name":"Bühne","sc":{"stage":true,"spotlight":true}},"className":"sc-close","layout":{"type":"default"}} -->
<div class="wp-block-group sc-close">
<!-- wp:group {"metadata":{"name":"Text"},"className":"sc-copy sc-copy--center","layout":{"type":"default"}} -->
<div class="wp-block-group sc-copy sc-copy--center">
<!-- wp:heading {"metadata":{"sc":{"cue":"0.06","kinetic":"lines"}},"className":"sc-display sc-display--lg"} -->
<h2 class="wp-block-heading sc-display sc-display--lg">Das Letzte, was hängen bleiben soll.</h2>
<!-- /wp:heading -->

<!-- wp:buttons {"metadata":{"sc":{"cue":"0.06","rise":0}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"metadata":{"sc":{"magnet":0.26}}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Dieselbe Beschriftung wie oben</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
