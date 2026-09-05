<?php
/**
 * Title: Kennzahlen im Fluss (Zähler beim Erscheinen)
 * Slug: th-scrollcraft/kennzahlen-fluss
 * Categories: sc-akte
 * Description: Zahlen ticken einmal hoch, sobald sie ins Bild kommen. Kein Akt, keine Bühne, kein Kleben, also überall im Fluss einsetzbar. Harte Regel: nur echte Zahlen. Gibt es keine belegbare Zahl, gibt es keinen Zähler.
 * Keywords: kennzahlen, zahlen, counter, beleg, statistik, fluss, flow
 * Viewport Width: 1440
 *
 * @package th-scrollcraft
 *
 * Der Unterschied zu "Akt · Kennzahlen": dort hängen die Zähler am
 * Scroll-Fortschritt eines gepinnten Aktes und laufen vor und zurück, hier
 * ticken sie einmal beim Erscheinen und bleiben stehen.
 *
 * DAS PATTERN DARF IN KEINEM data-sc-act LIEGEN. Der Motor sammelt für den
 * Zähler beim Erscheinen ausdrücklich nur die Elemente ohne Akt-Vorfahr
 * (`!c.closest('[data-sc-act]')`). Wer diesen Abschnitt in einen Akt schiebt,
 * bekommt keinen Fehler: der Zähler wird dann vom Akt-Fortschritt getrieben,
 * und ohne data-sc-count-at heißt das Fenster 0 bis 1. Die Zahl hängt dann am
 * Mausrad statt einmal zu laufen, und im Screenshot sieht das richtig aus.
 *
 * ZAHLEN UNTER 10000 HALTEN. formatNum() im Motor rechnet englisch: ab 10000
 * setzt es von sich aus Kommas als Tausendertrenner, aus 12500 wird "12,500"
 * statt "12.500". Ein Punkt im Zielwert hilft nicht, den liest der Motor als
 * Dezimaltrenner. Wer größere Zahlen braucht, wechselt die Einheit
 * (statt 12500 Stunden also 12,5 Tausend) und schreibt sie ins Label.
 *
 * Die Dauer steht an jedem Zähler einzeln über countMs. Ohne den Wert sind es
 * 1400ms. Unter einer Sekunde bemerkt niemand die Bewegung, über zwei wartet
 * man auf eine Zahl, die man längst gelesen hat.
 *
 * Beide Fallen findet zaehler-probe.mjs aus dem Skill, weil es nach dem Zählen
 * wieder hochscrollt: ein Zähler beim Erscheinen bleibt stehen, ein am Akt
 * hängender fällt auf den Startwert zurück.
 *
 *   node <skill>/scripts/zaehler-probe.mjs --url "https://.../SEITE/?bypass_code=CODE"
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"metadata":{"name":"Kennzahlen im Fluss"},"align":"full","className":"sc-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull sc-section">
<!-- wp:group {"metadata":{"sc":{"in":true,"stagger":70}},"className":"sc-stack","layout":{"type":"default"}} -->
<div class="wp-block-group sc-stack">
<!-- wp:heading {"className":"sc-display sc-display--md"} -->
<h2 class="wp-block-heading sc-display sc-display--md">Was dabei herauskommt.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sc-body"} -->
<p class="sc-body">Ein Satz, der die Zahlen einordnet. Ohne ihn sind es drei Ziffern ohne Anspruch, und der Leser muss sich selbst zusammenreimen, worauf sie sich beziehen.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"Zahlenreihe","sc":{"in":true,"stagger":90}},"className":"sc-figures","layout":{"type":"default"}} -->
<div class="wp-block-group sc-figures">
<!-- wp:group {"className":"sc-figure","layout":{"type":"default"}} -->
<div class="wp-block-group sc-figure">
<!-- wp:paragraph {"metadata":{"sc":{"count":"0 1240","countMs":1600}},"className":"sc-display sc-display--lg"} -->
<p class="sc-display sc-display--lg">0</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sc-label"} -->
<p class="sc-label">Was gezählt wird</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"sc-figure","layout":{"type":"default"}} -->
<div class="wp-block-group sc-figure">
<!-- wp:paragraph {"metadata":{"sc":{"count":"0 18","countMs":1600}},"className":"sc-display sc-display--lg"} -->
<p class="sc-display sc-display--lg">0</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sc-label"} -->
<p class="sc-label">Zweite Zahl</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"sc-figure","layout":{"type":"default"}} -->
<div class="wp-block-group sc-figure">
<!-- wp:paragraph {"metadata":{"sc":{"count":"0 96","countMs":1600}},"className":"sc-display sc-display--lg"} -->
<p class="sc-display sc-display--lg">0</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sc-label"} -->
<p class="sc-label">Dritte Zahl</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
