<?php
/**
 * Shortcodes que pintan la cartelera. Se usan dentro de los patrones del tema
 * (bloque Shortcode) y pueden insertarse en cualquier página.
 *
 * @package Butaca
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tarjeta de póster.
 */
function butaca_card( $m, $extra = '' ) {
	$genres = implode( ' · ', array_slice( $m['genres'], 0, 2 ) );
	ob_start();
	?>
	<article class="butaca-card" data-genres="<?php echo esc_attr( implode( ',', $m['genre_ids'] ) ); ?>">
		<button type="button" class="butaca-card__btn" data-movie="<?php echo esc_attr( $m['id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Ver ficha de %s', $m['title'] ) ); ?>">
			<?php if ( $m['poster'] ) : ?>
				<img class="butaca-card__poster" src="<?php echo esc_url( $m['poster'] ); ?>" alt="<?php echo esc_attr( sprintf( 'Póster de %s', $m['title'] ) ); ?>" loading="lazy" width="500" height="750" />
			<?php else : ?>
				<span class="butaca-card__poster butaca-card__poster--empty"><?php echo esc_html( $m['title'] ); ?></span>
			<?php endif; ?>
			<?php if ( $m['rating'] > 0 ) : ?>
				<span class="butaca-rating" style="--r:<?php echo esc_attr( $m['rating'] * 10 ); ?>"><?php echo esc_html( number_format_i18n( $m['rating'], 1 ) ); ?></span>
			<?php endif; ?>
			<span class="butaca-card__cta">Ver ficha</span>
		</button>
		<?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput -- generado y escapado por el llamador. ?>
		<h3 class="butaca-card__title"><?php echo esc_html( $m['title'] ); ?></h3>
		<p class="butaca-card__meta"><?php echo esc_html( trim( $m['year'] . ( $genres ? ' · ' . $genres : '' ) ) ); ?></p>
	</article>
	<?php
	return ob_get_clean();
}

/**
 * Aviso visible solo para administradores cuando se usan datos demo o hay error.
 */
function butaca_admin_notice( $data ) {
	if ( ! current_user_can( 'manage_options' ) || 'demo' !== $data['source'] ) {
		return '';
	}
	$msg = $data['error']
		? sprintf( 'TMDB respondió con un error (%s). Mostrando catálogo demo.', $data['error'] )
		: 'Modo demo: añade tu clave de TMDB para ver la cartelera real.';
	return '<p class="butaca-notice">' . esc_html( $msg ) . ' <a href="' . esc_url( admin_url( 'options-general.php?page=butaca' ) ) . '">Ajustes → Butaca</a></p>';
}

/**
 * [butaca_destacada lista="now_playing"]
 */
function butaca_sc_featured( $atts ) {
	$atts = shortcode_atts( array( 'lista' => 'now_playing', 'posicion' => 0 ), $atts, 'butaca_destacada' );
	$data = butaca_get_movies( $atts['lista'], 5 );
	if ( empty( $data['movies'] ) ) {
		return '';
	}
	if ( (int) $atts['posicion'] > 0 ) {
		$m = $data['movies'][ min( count( $data['movies'] ) - 1, (int) $atts['posicion'] - 1 ) ];
	} else {
		// posicion="0": la mejor valorada entre las primeras cinco.
		$m = $data['movies'][0];
		foreach ( $data['movies'] as $cand ) {
			if ( $cand['rating'] > $m['rating'] && $cand['backdrop'] ) {
				$m = $cand;
			}
		}
	}
	ob_start();
	?>
	<section class="butaca-hero" style="--bg:url('<?php echo esc_url( $m['backdrop'] ? $m['backdrop'] : $m['poster'] ); ?>')">
		<div class="butaca-hero__inner">
			<p class="butaca-kicker"><?php echo esc_html( butaca_lists()[ $atts['lista'] ] ); ?> · Destacada</p>
			<h1 class="butaca-hero__title"><?php echo esc_html( $m['title'] ); ?></h1>
			<p class="butaca-hero__meta">
				<?php if ( $m['rating'] > 0 ) : ?><span class="butaca-star">★ <?php echo esc_html( number_format_i18n( $m['rating'], 1 ) ); ?></span><?php endif; ?>
				<span><?php echo esc_html( $m['year'] ); ?></span>
				<span><?php echo esc_html( implode( ' · ', $m['genres'] ) ); ?></span>
			</p>
			<p class="butaca-hero__overview"><?php echo esc_html( wp_trim_words( $m['overview'], 40 ) ); ?></p>
			<div class="butaca-hero__actions">
				<button type="button" class="butaca-btn butaca-btn--primary" data-movie="<?php echo esc_attr( $m['id'] ); ?>">▶ Ver tráiler y ficha</button>
				<a class="butaca-btn" href="#cartelera">Ver cartelera</a>
			</div>
		</div>
		<?php echo butaca_admin_notice( $data ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'butaca_destacada', 'butaca_sc_featured' );

/**
 * [butaca_cartelera lista="now_playing" cantidad="12" filtros="si"]
 */
function butaca_sc_grid( $atts ) {
	$atts = shortcode_atts( array( 'lista' => 'now_playing', 'cantidad' => 12, 'filtros' => 'si' ), $atts, 'butaca_cartelera' );
	$data = butaca_get_movies( $atts['lista'], min( 20, absint( $atts['cantidad'] ) ) );
	if ( empty( $data['movies'] ) ) {
		return '<p class="butaca-empty">No hay películas para mostrar.</p>';
	}
	$map    = butaca_genres();
	$genres = array();
	foreach ( $data['movies'] as $m ) {
		foreach ( $m['genre_ids'] as $gid ) {
			if ( isset( $map[ $gid ] ) ) {
				$genres[ $gid ] = $map[ $gid ];
			}
		}
	}
	asort( $genres );
	ob_start();
	?>
	<div class="butaca-grid-wrap" data-butaca-filter>
		<?php if ( 'si' === $atts['filtros'] && count( $genres ) > 1 ) : ?>
			<div class="butaca-chips" role="toolbar" aria-label="Filtrar por género">
				<button type="button" class="butaca-chip is-active" data-genre="all" aria-pressed="true">Todas</button>
				<?php foreach ( $genres as $gid => $name ) : ?>
					<button type="button" class="butaca-chip" data-genre="<?php echo esc_attr( $gid ); ?>" aria-pressed="false"><?php echo esc_html( $name ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="butaca-grid">
			<?php
			foreach ( $data['movies'] as $m ) {
				echo butaca_card( $m ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'butaca_cartelera', 'butaca_sc_grid' );

/**
 * [butaca_carrusel lista="upcoming" cantidad="10"]
 */
function butaca_sc_carousel( $atts ) {
	$atts = shortcode_atts( array( 'lista' => 'upcoming', 'cantidad' => 10 ), $atts, 'butaca_carrusel' );
	$data = butaca_get_movies( $atts['lista'], min( 20, absint( $atts['cantidad'] ) ) );
	if ( empty( $data['movies'] ) ) {
		return '';
	}
	$today = new DateTimeImmutable( 'today', wp_timezone() );
	ob_start();
	?>
	<div class="butaca-carousel">
		<div class="butaca-carousel__nav">
			<button type="button" class="butaca-arrow" data-dir="-1" aria-label="Anterior">←</button>
			<button type="button" class="butaca-arrow" data-dir="1" aria-label="Siguiente">→</button>
		</div>
		<div class="butaca-carousel__track" tabindex="0">
			<?php
			foreach ( $data['movies'] as $m ) {
				$badge = '';
				if ( $m['date'] ) {
					$date  = new DateTimeImmutable( $m['date'], wp_timezone() );
					$days  = (int) $today->diff( $date )->format( '%r%a' );
					$label = $days > 1 ? sprintf( 'Faltan %d días', $days ) : ( 1 === $days ? 'Mañana' : ( 0 === $days ? '¡Hoy!' : 'Estrenada' ) );
					$badge = '<p class="butaca-release"><strong>' . esc_html( wp_date( 'j M Y', $date->getTimestamp() ) ) . '</strong> <span>' . esc_html( $label ) . '</span></p>';
				}
				echo butaca_card( $m, $badge ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'butaca_carrusel', 'butaca_sc_carousel' );

/**
 * [butaca_ranking lista="popular" cantidad="5"]
 */
function butaca_sc_ranking( $atts ) {
	$atts = shortcode_atts( array( 'lista' => 'popular', 'cantidad' => 5 ), $atts, 'butaca_ranking' );
	$data = butaca_get_movies( $atts['lista'], min( 10, absint( $atts['cantidad'] ) ) );
	ob_start();
	?>
	<ol class="butaca-ranking">
		<?php foreach ( $data['movies'] as $i => $m ) : ?>
			<li>
				<button type="button" class="butaca-ranking__item" data-movie="<?php echo esc_attr( $m['id'] ); ?>">
					<span class="butaca-ranking__num"><?php echo (int) $i + 1; ?></span>
					<?php if ( $m['poster'] ) : ?><img src="<?php echo esc_url( $m['poster'] ); ?>" alt="" loading="lazy" width="80" height="120" /><?php endif; ?>
					<span class="butaca-ranking__text">
						<strong><?php echo esc_html( $m['title'] ); ?></strong>
						<span><?php echo esc_html( $m['year'] . ' · ' . implode( ', ', array_slice( $m['genres'], 0, 2 ) ) ); ?></span>
					</span>
					<?php if ( $m['rating'] > 0 ) : ?><span class="butaca-star">★ <?php echo esc_html( number_format_i18n( $m['rating'], 1 ) ); ?></span><?php endif; ?>
				</button>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php
	return ob_get_clean();
}
add_shortcode( 'butaca_ranking', 'butaca_sc_ranking' );

/**
 * [butaca_buscador]
 */
function butaca_sc_search() {
	ob_start();
	?>
	<div class="butaca-search" data-butaca-search>
		<label class="screen-reader-text" for="butaca-q">Buscar película</label>
		<div class="butaca-search__field">
			<span aria-hidden="true">⌕</span>
			<input id="butaca-q" type="search" placeholder="Busca cualquier película por título…" autocomplete="off" />
		</div>
		<p class="butaca-search__status" aria-live="polite"></p>
		<div class="butaca-grid butaca-search__results"></div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'butaca_buscador', 'butaca_sc_search' );

/**
 * Ventana modal (una sola por página) para la ficha de la película.
 */
function butaca_modal() {
	?>
	<dialog id="butaca-modal" class="butaca-modal" aria-labelledby="butaca-modal-title">
		<button type="button" class="butaca-modal__close" aria-label="Cerrar">✕</button>
		<div class="butaca-modal__body"></div>
	</dialog>
	<?php
}
add_action( 'wp_footer', 'butaca_modal' );
