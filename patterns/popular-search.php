<?php
/**
 * Title: Ranking de populares + buscador
 * Slug: butaca/popular-search
 * Categories: butaca
 * Keywords: populares, ranking, top, buscador
 * Viewport width: 1400
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1360px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"width":"45%","anchor":"populares"} -->
<div id="populares" class="wp-block-column" style="flex-basis:45%"><!-- wp:paragraph {"className":"butaca-kicker"} -->
<p class="butaca-kicker">Lo que todos están viendo</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-x-large-font-size">Top 5 populares</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[butaca_ranking lista="popular" cantidad="5"]
<!-- /wp:shortcode --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"55%","anchor":"buscar"} -->
<div id="buscar" class="wp-block-column" style="flex-basis:55%"><!-- wp:paragraph {"className":"butaca-kicker"} -->
<p class="butaca-kicker">Más de un millón de títulos</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-x-large-font-size">Busca una película</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[butaca_buscador]
<!-- /wp:shortcode --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
