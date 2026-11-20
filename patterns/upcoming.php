<?php
/**
 * Title: Próximos estrenos (carrusel)
 * Slug: butaca/upcoming
 * Categories: butaca
 * Keywords: estrenos, próximamente, upcoming, carrusel
 * Viewport width: 1400
 */
?>
<!-- wp:group {"anchor":"proximamente","align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1360px"}} -->
<div id="proximamente" class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"className":"butaca-kicker"} -->
<p class="butaca-kicker">Marca tu calendario</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"fontSize":"x-large","style":{"spacing":{"margin":{"bottom":"1.5rem"}}}} -->
<h2 class="wp-block-heading has-x-large-font-size" style="margin-bottom:1.5rem">Próximamente</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[butaca_carrusel lista="upcoming" cantidad="10"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->
