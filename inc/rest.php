<?php
/**
 * API REST propia: /wp-json/butaca/v1/…
 * El navegador consulta estos endpoints; la clave de TMDB permanece en el servidor.
 *
 * @package Butaca
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function butaca_register_routes() {
	register_rest_route(
		'butaca/v1',
		'/pelicula/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'args'                => array( 'id' => array( 'sanitize_callback' => 'absint' ) ),
			'callback'            => function ( WP_REST_Request $req ) {
				$movie = butaca_get_movie( $req['id'] );
				if ( is_wp_error( $movie ) ) {
					return new WP_Error( $movie->get_error_code(), $movie->get_error_message(), array( 'status' => 404 ) );
				}
				return rest_ensure_response( $movie );
			},
		)
	);

	register_rest_route(
		'butaca/v1',
		'/buscar',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'args'                => array(
				'q' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			'callback'            => function ( WP_REST_Request $req ) {
				return rest_ensure_response( butaca_search( $req['q'] ) );
			},
		)
	);

	register_rest_route(
		'butaca/v1',
		'/lista/(?P<lista>[a-z_]+)',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => function ( WP_REST_Request $req ) {
				$data = butaca_get_movies( $req['lista'], min( 20, absint( $req->get_param( 'cantidad' ) ? $req->get_param( 'cantidad' ) : 12 ) ) );
				unset( $data['error'] );
				return rest_ensure_response( $data );
			},
		)
	);
}
add_action( 'rest_api_init', 'butaca_register_routes' );
