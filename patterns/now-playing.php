<?php
/**
 * Title: En cartelera (rejilla con filtros)
 * Slug: butaca/now-playing
 * Categories: butaca, featured
 * Keywords: cartelera, películas, now playing
 * Viewport width: 1400
 */
?>
<!-- wp:group {"anchor":"cartelera","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1360px"}} -->
<div id="cartelera" class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"className":"butaca-kicker"} -->
<p class="butaca-kicker">Hoy en salas</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"butaca-section-title","fontSize":"x-large"} -->
<h2 class="wp-block-heading butaca-section-title has-x-large-font-size">En cartelera</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[butaca_cartelera lista="now_playing" cantidad="12" filtros="si"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->
