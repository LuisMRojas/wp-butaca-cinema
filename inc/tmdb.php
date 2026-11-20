<?php
/**
 * Cliente de la API de TMDB (The Movie Database) con caché y modo demo.
 *
 * La clave nunca se expone al navegador: todas las llamadas se hacen desde PHP
 * y el front-end consulta la API REST propia del tema (inc/rest.php).
 *
 * @package Butaca
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BUTACA_TMDB_API = 'https://api.themoviedb.org/3/';
const BUTACA_TMDB_IMG = 'https://image.tmdb.org/t/p/';

/**
 * Lee un ajuste del tema.
 */
function butaca_option( $key, $default = '' ) {
	$opts = get_option( 'butaca_settings', array() );
	return isset( $opts[ $key ] ) && '' !== $opts[ $key ] ? $opts[ $key ] : $default;
}

/**
 * Clave de TMDB: constante BUTACA_TMDB_KEY en wp-config.php o ajuste guardado.
 * Acepta la "API Key" (v3) o el "API Read Access Token" (v4, JWT largo).
 */
function butaca_api_key() {
	$key = defined( 'BUTACA_TMDB_KEY' ) ? BUTACA_TMDB_KEY : butaca_option( 'api_key' );
	return trim( (string) $key );
}

function butaca_is_demo() {
	return '' === butaca_api_key();
}

/**
 * Petición GET a TMDB con caché en transients.
 *
 * @return array|WP_Error
 */
function butaca_tmdb_request( $path, $args = array() ) {
	$key = butaca_api_key();
	if ( '' === $key ) {
		return new WP_Error( 'butaca_no_key', __( 'Falta la clave de TMDB.', 'butaca' ) );
	}

	$args    = wp_parse_args( $args, array( 'language' => butaca_option( 'language', 'es-MX' ) ) );
	$headers = array( 'Accept' => 'application/json' );
	if ( strlen( $key ) > 40 ) {
		$headers['Authorization'] = 'Bearer ' . $key;
	} else {
		$args['api_key'] = $key;
	}

	$url   = add_query_arg( array_map( 'rawurlencode', $args ), BUTACA_TMDB_API . ltrim( $path, '/' ) );
	$cache = 'butaca_' . md5( $url );
	$hit   = get_transient( $cache );
	if ( false !== $hit ) {
		return $hit;
	}

	$response = wp_remote_get( $url, array( 'headers' => $headers, 'timeout' => 8 ) );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 !== $code || ! is_array( $body ) ) {
		$msg = is_array( $body ) && ! empty( $body['status_message'] ) ? $body['status_message'] : 'HTTP ' . $code;
		return new WP_Error( 'butaca_tmdb_http', $msg );
	}

	set_transient( $cache, $body, max( 5, (int) butaca_option( 'cache_minutes', 60 ) ) * MINUTE_IN_SECONDS );
	return $body;
}

/**
 * Borra toda la caché de TMDB.
 */
function butaca_flush_cache() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_butaca\_%' OR option_name LIKE '\_transient\_timeout\_butaca\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/* -------------------------------------------------------------------------
 * Datos
 * ---------------------------------------------------------------------- */

/**
 * Listas disponibles.
 */
function butaca_lists() {
	return array(
		'now_playing' => __( 'En cartelera', 'butaca' ),
		'upcoming'    => __( 'Próximamente', 'butaca' ),
		'popular'     => __( 'Populares', 'butaca' ),
		'top_rated'   => __( 'Mejor valoradas', 'butaca' ),
	);
}

/**
 * Mapa id => nombre de géneros.
 */
function butaca_genres() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$map = array( 28 => 'Acción', 12 => 'Aventura', 16 => 'Animación', 35 => 'Comedia', 80 => 'Crimen', 99 => 'Documental', 18 => 'Drama', 10751 => 'Familia', 14 => 'Fantasía', 36 => 'Historia', 27 => 'Terror', 10402 => 'Música', 9648 => 'Misterio', 10749 => 'Romance', 878 => 'Ciencia ficción', 53 => 'Suspense', 10752 => 'Bélica', 37 => 'Western' );
	if ( ! butaca_is_demo() ) {
		$res = butaca_tmdb_request( 'genre/movie/list' );
		if ( ! is_wp_error( $res ) && ! empty( $res['genres'] ) ) {
			$map = wp_list_pluck( $res['genres'], 'name', 'id' );
		}
	}
	return $map;
}

/**
 * Catálogo de demostración (películas ficticias con pósters originales).
 */
function butaca_demo_movies() {
	static $movies = null;
	if ( null === $movies ) {
		$json   = file_get_contents( get_theme_file_path( 'demo/movies.json' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$movies = json_decode( $json, true );
	}
	return $movies;
}

/**
 * Normaliza una película (de TMDB o demo) al formato que usa el tema.
 */
function butaca_normalize( $m, $source ) {
	$genres = butaca_genres();
	$ids    = isset( $m['genre_ids'] ) ? $m['genre_ids'] : wp_list_pluck( isset( $m['genres'] ) ? $m['genres'] : array(), 'id' );

	if ( 'demo' === $source ) {
		$poster   = get_theme_file_uri( 'demo/img/poster-' . $m['id'] . '.jpg' );
		$backdrop = get_theme_file_uri( 'demo/img/backdrop-' . $m['id'] . '.jpg' );
	} else {
		$poster   = ! empty( $m['poster_path'] ) ? BUTACA_TMDB_IMG . 'w500' . $m['poster_path'] : '';
		$backdrop = ! empty( $m['backdrop_path'] ) ? BUTACA_TMDB_IMG . 'w1280' . $m['backdrop_path'] : '';
	}

	return array(
		'id'        => (int) $m['id'],
		'title'     => isset( $m['title'] ) ? $m['title'] : '',
		'overview'  => isset( $m['overview'] ) ? $m['overview'] : '',
		'date'      => isset( $m['release_date'] ) ? $m['release_date'] : '',
		'year'      => ! empty( $m['release_date'] ) ? substr( $m['release_date'], 0, 4 ) : '',
		'rating'    => isset( $m['vote_average'] ) ? round( (float) $m['vote_average'], 1 ) : 0,
		'votes'     => isset( $m['vote_count'] ) ? (int) $m['vote_count'] : 0,
		'genre_ids' => array_map( 'intval', $ids ),
		'genres'    => array_values( array_filter( array_map( function ( $id ) use ( $genres ) { return isset( $genres[ $id ] ) ? $genres[ $id ] : null; }, $ids ) ) ),
		'poster'    => $poster,
		'backdrop'  => $backdrop,
		'source'    => $source,
	);
}

/**
 * Devuelve una lista de películas.
 *
 * @return array{source:string,error:string,movies:array}
 */
function butaca_get_movies( $list = 'now_playing', $limit = 12 ) {
	$list = array_key_exists( $list, butaca_lists() ) ? $list : 'now_playing';
	$error = '';

	if ( ! butaca_is_demo() ) {
		$args = array( 'page' => 1 );
		if ( in_array( $list, array( 'now_playing', 'upcoming' ), true ) ) {
			$args['region'] = butaca_option( 'region', 'MX' );
		}
		$res = butaca_tmdb_request( 'movie/' . $list, $args );
		if ( ! is_wp_error( $res ) ) {
			$movies = array_map( function ( $m ) { return butaca_normalize( $m, 'tmdb' ); }, isset( $res['results'] ) ? $res['results'] : array() );
			if ( 'upcoming' === $list ) {
				$today  = gmdate( 'Y-m-d' );
				$movies = array_values( array_filter( $movies, function ( $m ) use ( $today ) { return $m['date'] >= $today; } ) );
				usort( $movies, function ( $a, $b ) { return strcmp( $a['date'], $b['date'] ); } );
			}
			return array( 'source' => 'tmdb', 'error' => '', 'movies' => array_slice( $movies, 0, $limit ) );
		}
		$error = $res->get_error_message();
	}

	// Modo demo (sin clave) o error de la API: catálogo local.
	$movies = array_values( array_filter( butaca_demo_movies(), function ( $m ) use ( $list ) { return in_array( $list, $m['lists'], true ); } ) );
	$sorters = array(
		'now_playing' => function ( $a, $b ) { return strcmp( $b['release_date'], $a['release_date'] ); },
		'upcoming'    => function ( $a, $b ) { return strcmp( $a['release_date'], $b['release_date'] ); },
		'popular'     => function ( $a, $b ) { return $b['popularity'] <=> $a['popularity']; },
		'top_rated'   => function ( $a, $b ) { return $b['vote_average'] <=> $a['vote_average']; },
	);
	usort( $movies, $sorters[ $list ] );
	$movies = array_map( function ( $m ) { return butaca_normalize( $m, 'demo' ); }, array_slice( $movies, 0, $limit ) );

	return array( 'source' => 'demo', 'error' => $error, 'movies' => $movies );
}

/**
 * Ficha completa de una película: sinopsis, duración, reparto, dirección y tráiler.
 *
 * @return array|WP_Error
 */
function butaca_get_movie( $id ) {
	$id = absint( $id );

	if ( ! butaca_is_demo() ) {
		$res = butaca_tmdb_request(
			'movie/' . $id,
			array(
				'append_to_response'     => 'videos,credits',
				'include_video_language' => substr( butaca_option( 'language', 'es-MX' ), 0, 2 ) . ',en,null',
			)
		);
		if ( ! is_wp_error( $res ) ) {
			$movie = butaca_normalize( $res, 'tmdb' );
			$crew  = isset( $res['credits']['crew'] ) ? $res['credits']['crew'] : array();
			$dirs  = array_filter( $crew, function ( $c ) { return 'Director' === $c['job']; } );
			$cast  = array_slice( isset( $res['credits']['cast'] ) ? $res['credits']['cast'] : array(), 0, 8 );
			$trailer = '';
			foreach ( isset( $res['videos']['results'] ) ? $res['videos']['results'] : array() as $v ) {
				if ( 'YouTube' === $v['site'] && in_array( $v['type'], array( 'Trailer', 'Teaser' ), true ) ) {
					$trailer = $v['key'];
					if ( 'Trailer' === $v['type'] ) {
						break;
					}
				}
			}
			return array_merge(
				$movie,
				array(
					'tagline'  => isset( $res['tagline'] ) ? $res['tagline'] : '',
					'runtime'  => isset( $res['runtime'] ) ? (int) $res['runtime'] : 0,
					'director' => implode( ', ', wp_list_pluck( $dirs, 'name' ) ),
					'cast'     => array_map(
						function ( $c ) {
							return array(
								'name'      => $c['name'],
								'character' => $c['character'],
								'photo'     => ! empty( $c['profile_path'] ) ? BUTACA_TMDB_IMG . 'w185' . $c['profile_path'] : '',
							);
						},
						$cast
					),
					'trailer'  => $trailer,
					'url'      => 'https://www.themoviedb.org/movie/' . $id,
				)
			);
		}
		// Si TMDB falla, intenta el catálogo demo antes de devolver el error.
		$error = $res;
	}

	foreach ( butaca_demo_movies() as $m ) {
		if ( (int) $m['id'] === $id ) {
			return array_merge(
				butaca_normalize( $m, 'demo' ),
				array(
					'tagline'  => $m['tagline'],
					'runtime'  => $m['runtime'],
					'director' => $m['director'],
					'cast'     => array_map( function ( $c ) { return array( 'name' => $c[0], 'character' => $c[1], 'photo' => '' ); }, $m['cast'] ),
					'trailer'  => '',
					'url'      => '',
				)
			);
		}
	}
	return isset( $error ) ? $error : new WP_Error( 'butaca_not_found', __( 'Película no encontrada.', 'butaca' ), array( 'status' => 404 ) );
}

/**
 * Búsqueda por título.
 */
function butaca_search( $query, $limit = 12 ) {
	$query = trim( wp_strip_all_tags( $query ) );
	if ( mb_strlen( $query ) < 2 ) {
		return array( 'source' => butaca_is_demo() ? 'demo' : 'tmdb', 'movies' => array() );
	}
	if ( ! butaca_is_demo() ) {
		$res = butaca_tmdb_request( 'search/movie', array( 'query' => $query, 'include_adult' => 'false' ) );
		if ( ! is_wp_error( $res ) ) {
			$movies = array_map( function ( $m ) { return butaca_normalize( $m, 'tmdb' ); }, $res['results'] );
			return array( 'source' => 'tmdb', 'movies' => array_slice( $movies, 0, $limit ) );
		}
	}
	$found = array_filter( butaca_demo_movies(), function ( $m ) use ( $query ) { return false !== mb_stripos( remove_accents( $m['title'] ), remove_accents( $query ) ); } );
	return array( 'source' => 'demo', 'movies' => array_values( array_map( function ( $m ) { return butaca_normalize( $m, 'demo' ); }, $found ) ) );
}
