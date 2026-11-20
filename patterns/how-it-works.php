<?php
/**
 * Title: Cómo funciona (API TMDB)
 * Slug: butaca/how-it-works
 * Categories: butaca, text
 * Keywords: api, tmdb, cómo funciona
 * Viewport width: 1400
 */

$butaca_steps = array(
	array( 'API de TMDB', 'PHP consulta la cartelera, estrenos, populares, géneros, reparto y tráilers de The Movie Database.' ),
	array( 'Caché inteligente', 'Las respuestas se guardan con transients de WordPress para cargar rápido y respetar los límites de la API.' ),
	array( 'API REST propia', 'El navegador nunca ve tu clave: la ficha y el buscador consultan /wp-json/butaca/v1/.' ),
	array( 'Modo demo', 'Sin clave o si la API falla, se muestra un catálogo ficticio para que el sitio nunca quede vacío.' ),
);
?>
<!-- wp:group {"align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1360px"}} -->
<div class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"className":"butaca-kicker"} -->
<p class="butaca-kicker">Detrás de cámaras</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-x-large-font-size">Cómo funciona</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--30)"><?php foreach ( $butaca_steps as $i => $s ) : ?><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"textColor":"accent","style":{"typography":{"fontFamily":"var:preset|font-family|display","fontSize":"3rem","lineHeight":"1"}}} -->
<p class="has-accent-color has-text-color" style="font-family:var(--wp--preset--font-family--display);font-size:3rem;line-height:1">0<?php echo (int) $i + 1; ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"fontSize":"large","style":{"typography":{"fontFamily":"var:preset|font-family|body","fontWeight":"600","letterSpacing":"0"}}} -->
<h3 class="wp-block-heading has-large-font-size" style="font-family:var(--wp--preset--font-family--body);font-weight:600;letter-spacing:0"><?php echo esc_html( $s[0] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php echo esc_html( $s[1] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --><?php endforeach; ?></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
