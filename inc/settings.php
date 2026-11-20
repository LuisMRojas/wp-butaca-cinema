<?php
/**
 * Página de ajustes: Ajustes → Butaca (TMDB).
 *
 * @package Butaca
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function butaca_settings_menu() {
	add_options_page( 'Butaca · TMDB', 'Butaca (TMDB)', 'manage_options', 'butaca', 'butaca_settings_page' );
}
add_action( 'admin_menu', 'butaca_settings_menu' );

function butaca_register_settings() {
	register_setting(
		'butaca',
		'butaca_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'butaca_sanitize_settings',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'butaca_register_settings' );

function butaca_sanitize_settings( $input ) {
	$langs   = array_keys( butaca_languages() );
	$regions = array_keys( butaca_regions() );
	$out     = array(
		'api_key'       => isset( $input['api_key'] ) ? preg_replace( '/[^A-Za-z0-9._\-]/', '', $input['api_key'] ) : '',
		'language'      => isset( $input['language'] ) && in_array( $input['language'], $langs, true ) ? $input['language'] : 'es-MX',
		'region'        => isset( $input['region'] ) && in_array( $input['region'], $regions, true ) ? $input['region'] : 'MX',
		'cache_minutes' => isset( $input['cache_minutes'] ) ? max( 5, min( 1440, absint( $input['cache_minutes'] ) ) ) : 60,
	);
	butaca_flush_cache();
	return $out;
}

function butaca_languages() {
	return array( 'es-MX' => 'Español (México)', 'es-ES' => 'Español (España)', 'en-US' => 'English (US)', 'pt-BR' => 'Português (Brasil)' );
}

function butaca_regions() {
	return array( 'MX' => 'México', 'ES' => 'España', 'AR' => 'Argentina', 'CO' => 'Colombia', 'CL' => 'Chile', 'PE' => 'Perú', 'US' => 'Estados Unidos' );
}

/**
 * Acción "Vaciar caché".
 */
function butaca_handle_flush() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sin permisos.', 'butaca' ) );
	}
	check_admin_referer( 'butaca_flush' );
	butaca_flush_cache();
	wp_safe_redirect( add_query_arg( array( 'page' => 'butaca', 'flushed' => 1 ), admin_url( 'options-general.php' ) ) );
	exit;
}
add_action( 'admin_post_butaca_flush', 'butaca_handle_flush' );

function butaca_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$const = defined( 'BUTACA_TMDB_KEY' );
	$status = null;
	if ( ! butaca_is_demo() ) {
		$test   = butaca_tmdb_request( 'configuration' );
		$status = is_wp_error( $test ) ? $test->get_error_message() : true;
	}
	?>
	<div class="wrap">
		<h1>Butaca · Cartelera con TMDB</h1>
		<p>Butaca obtiene la cartelera, los próximos estrenos, las películas populares, fichas, reparto y tráilers desde
			<a href="https://www.themoviedb.org/" target="_blank" rel="noopener">The Movie Database (TMDB)</a>.
			Crea una cuenta gratuita y copia tu clave en <a href="https://www.themoviedb.org/settings/api" target="_blank" rel="noopener">themoviedb.org/settings/api</a>.</p>

		<?php if ( isset( $_GET['flushed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success"><p>Caché vaciada.</p></div>
		<?php endif; ?>

		<?php if ( butaca_is_demo() ) : ?>
			<div class="notice notice-info inline"><p><strong>Modo demo activo.</strong> Sin clave, el sitio muestra un catálogo de películas ficticias incluido en el tema.</p></div>
		<?php elseif ( true === $status ) : ?>
			<div class="notice notice-success inline"><p><strong>Conectado a TMDB.</strong> Los datos se guardan en caché <?php echo esc_html( butaca_option( 'cache_minutes', 60 ) ); ?> minutos.</p></div>
		<?php else : ?>
			<div class="notice notice-error inline"><p><strong>Error de TMDB:</strong> <?php echo esc_html( $status ); ?>. Se muestra el catálogo demo mientras tanto.</p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'butaca' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="butaca-key">Clave de API o token</label></th>
					<td>
						<?php if ( $const ) : ?>
							<code>BUTACA_TMDB_KEY</code> definida en <code>wp-config.php</code>.
						<?php else : ?>
							<input id="butaca-key" class="regular-text" type="password" autocomplete="off" name="butaca_settings[api_key]" value="<?php echo esc_attr( butaca_option( 'api_key' ) ); ?>" />
							<p class="description">Acepta la <em>API Key</em> (v3) o el <em>API Read Access Token</em> (v4). También puedes definir <code>define( 'BUTACA_TMDB_KEY', '…' );</code> en <code>wp-config.php</code>.</p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="butaca-lang">Idioma</label></th>
					<td><select id="butaca-lang" name="butaca_settings[language]">
						<?php foreach ( butaca_languages() as $code => $label ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( butaca_option( 'language', 'es-MX' ), $code ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select></td>
				</tr>
				<tr>
					<th scope="row"><label for="butaca-region">País de la cartelera</label></th>
					<td><select id="butaca-region" name="butaca_settings[region]">
						<?php foreach ( butaca_regions() as $code => $label ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( butaca_option( 'region', 'MX' ), $code ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select></td>
				</tr>
				<tr>
					<th scope="row"><label for="butaca-cache">Caché (minutos)</label></th>
					<td><input id="butaca-cache" type="number" min="5" max="1440" name="butaca_settings[cache_minutes]" value="<?php echo esc_attr( butaca_option( 'cache_minutes', 60 ) ); ?>" /></td>
				</tr>
			</table>
			<?php submit_button( 'Guardar ajustes' ); ?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="butaca_flush" />
			<?php wp_nonce_field( 'butaca_flush' ); ?>
			<?php submit_button( 'Vaciar caché de TMDB', 'secondary', 'submit', false ); ?>
		</form>

		<h2>Shortcodes disponibles</h2>
		<table class="widefat striped" style="max-width:900px">
			<tbody>
				<tr><td><code>[butaca_destacada lista="now_playing"]</code></td><td>Portada con la película destacada y su fondo.</td></tr>
				<tr><td><code>[butaca_cartelera lista="now_playing" cantidad="12" filtros="si"]</code></td><td>Rejilla de pósters con filtro por género.</td></tr>
				<tr><td><code>[butaca_carrusel lista="upcoming" cantidad="10"]</code></td><td>Carrusel horizontal con cuenta regresiva al estreno.</td></tr>
				<tr><td><code>[butaca_ranking lista="popular" cantidad="5"]</code></td><td>Top numerado.</td></tr>
				<tr><td><code>[butaca_buscador]</code></td><td>Buscador de películas en vivo.</td></tr>
			</tbody>
		</table>
		<p class="description">Listas: <code>now_playing</code>, <code>upcoming</code>, <code>popular</code>, <code>top_rated</code>.</p>

		<p style="margin-top:2rem"><small>This product uses the TMDB API but is not endorsed or certified by TMDB.</small></p>
	</div>
	<?php
}
