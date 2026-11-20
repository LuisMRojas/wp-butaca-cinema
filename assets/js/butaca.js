/* Butaca — interacción de la cartelera (sin dependencias). */
( function () {
	'use strict';

	var cfg = window.BUTACA || { rest: '/wp-json/butaca/v1/' };
	var cache = {};

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	function api( path ) {
		var url = cfg.rest + ( cfg.rest.indexOf( '?' ) > -1 ? path.replace( '?', '&' ) : path );
		if ( ! cache[ url ] ) {
			cache[ url ] = fetch( url, { headers: { Accept: 'application/json' } } ).then( function ( r ) {
				if ( ! r.ok ) {
					throw new Error( 'HTTP ' + r.status );
				}
				return r.json();
			} );
			cache[ url ].catch( function () {
				delete cache[ url ];
			} );
		}
		return cache[ url ];
	}

	/* ---------------- Modal de ficha ---------------- */
	var modal = document.getElementById( 'butaca-modal' );
	var body = modal && modal.querySelector( '.butaca-modal__body' );
	var lastFocus = null;

	function runtime( min ) {
		if ( ! min ) {
			return '';
		}
		return Math.floor( min / 60 ) + ' h ' + ( min % 60 ) + ' min';
	}

	function renderMovie( m ) {
		var media = m.trailer
			? '<div class="butaca-modal__media has-video"><iframe src="https://www.youtube-nocookie.com/embed/' + esc( m.trailer ) + '?autoplay=1&rel=0" title="Tráiler de ' + esc( m.title ) + '" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe></div>'
			: '<div class="butaca-modal__media">' + ( m.backdrop ? '<img src="' + esc( m.backdrop ) + '" alt="">' : '' ) + '</div>';

		var facts = [];
		if ( m.rating > 0 ) {
			facts.push( '<li class="butaca-star">★ ' + esc( m.rating.toFixed( 1 ) ) + ' <small>(' + esc( m.votes.toLocaleString( cfg.locale ) ) + ' votos)</small></li>' );
		}
		if ( m.date ) {
			facts.push( '<li>' + esc( new Date( m.date + 'T12:00:00' ).toLocaleDateString( cfg.locale, { day: 'numeric', month: 'long', year: 'numeric' } ) ) + '</li>' );
		}
		if ( m.runtime ) {
			facts.push( '<li>' + esc( runtime( m.runtime ) ) + '</li>' );
		}
		( m.genres || [] ).forEach( function ( g ) {
			facts.push( '<li>' + esc( g ) + '</li>' );
		} );

		var cast = ( m.cast || [] )
			.map( function ( c ) {
				var pic = c.photo
					? '<img src="' + esc( c.photo ) + '" alt="" loading="lazy">'
					: '<span class="butaca-avatar">' + esc( c.name.replace( /^Voz:\s*/, '' ).charAt( 0 ) ) + '</span>';
				return '<li>' + pic + '<span>' + esc( c.name ) + '<br><small>' + esc( c.character ) + '</small></span></li>';
			} )
			.join( '' );

		var note = m.trailer
			? ''
			: '<p class="butaca-modal__note">' + ( m.source === 'demo' ? 'Película ficticia del modo demo: conecta tu clave de TMDB para ver tráilers reales.' : 'Tráiler no disponible para este título.' ) + '</p>';

		return (
			media +
			'<div class="butaca-modal__content">' +
			( m.poster ? '<img class="butaca-modal__poster" src="' + esc( m.poster ) + '" alt="Póster de ' + esc( m.title ) + '">' : '<span></span>' ) +
			'<div>' +
			'<h2 id="butaca-modal-title">' + esc( m.title ) + '</h2>' +
			( m.tagline ? '<p class="butaca-modal__tagline">' + esc( m.tagline ) + '</p>' : '' ) +
			'<ul class="butaca-modal__facts">' + facts.join( '' ) + '</ul>' +
			'<p>' + esc( m.overview || 'Sin sinopsis disponible.' ) + '</p>' +
			( m.director ? '<p><strong>Dirección:</strong> ' + esc( m.director ) + '</p>' : '' ) +
			( cast ? '<p><strong>Reparto</strong></p><ul class="butaca-modal__cast">' + cast + '</ul>' : '' ) +
			note +
			( m.url ? '<p><a class="butaca-btn" href="' + esc( m.url ) + '" target="_blank" rel="noopener">Ver en TMDB ↗</a></p>' : '' ) +
			'</div></div>'
		);
	}

	function openMovie( id, trigger ) {
		if ( ! modal ) {
			return;
		}
		lastFocus = trigger || document.activeElement;
		body.innerHTML = '<div class="butaca-skeleton">Cargando ficha…</div>';
		if ( typeof modal.showModal === 'function' ) {
			modal.showModal();
		} else {
			modal.setAttribute( 'open', '' );
		}
		document.documentElement.style.overflow = 'hidden';
		if ( history.replaceState ) {
			history.replaceState( null, '', '#pelicula-' + id );
		}
		api( 'pelicula/' + id )
			.then( function ( m ) {
				body.innerHTML = renderMovie( m );
			} )
			.catch( function () {
				body.innerHTML = '<div class="butaca-skeleton">No pudimos cargar esta película. Inténtalo de nuevo.</div>';
			} );
	}

	function closeModal() {
		if ( ! modal ) {
			return;
		}
		body.innerHTML = ''; // detiene el tráiler
		if ( modal.open && typeof modal.close === 'function' ) {
			modal.close();
		}
		modal.removeAttribute( 'open' );
		document.documentElement.style.overflow = '';
		if ( history.replaceState && /^#pelicula-/.test( location.hash ) ) {
			history.replaceState( null, '', location.pathname + location.search );
		}
		if ( lastFocus && lastFocus.focus ) {
			lastFocus.focus();
		}
	}

	document.addEventListener( 'click', function ( e ) {
		var t = e.target.closest( '[data-movie]' );
		if ( t ) {
			e.preventDefault();
			openMovie( t.getAttribute( 'data-movie' ), t );
		}
	} );

	if ( modal ) {
		modal.querySelector( '.butaca-modal__close' ).addEventListener( 'click', closeModal );
		modal.addEventListener( 'cancel', function ( e ) {
			e.preventDefault();
			closeModal();
		} );
		modal.addEventListener( 'click', function ( e ) {
			if ( e.target === modal ) {
				closeModal(); // clic en el fondo
			}
		} );
		var deep = location.hash.match( /^#pelicula-(\d+)$/ );
		if ( deep ) {
			openMovie( deep[ 1 ] );
		}
	}

	/* ---------------- Filtro por género ---------------- */
	document.querySelectorAll( '[data-butaca-filter]' ).forEach( function ( wrap ) {
		wrap.addEventListener( 'click', function ( e ) {
			var chip = e.target.closest( '.butaca-chip' );
			if ( ! chip ) {
				return;
			}
			var g = chip.getAttribute( 'data-genre' );
			wrap.querySelectorAll( '.butaca-chip' ).forEach( function ( c ) {
				c.classList.toggle( 'is-active', c === chip );
				c.setAttribute( 'aria-pressed', c === chip ? 'true' : 'false' );
			} );
			wrap.querySelectorAll( '.butaca-card' ).forEach( function ( card ) {
				var ids = ( card.getAttribute( 'data-genres' ) || '' ).split( ',' );
				card.classList.toggle( 'is-hidden', g !== 'all' && ids.indexOf( g ) === -1 );
			} );
		} );
	} );

	/* ---------------- Carrusel ---------------- */
	document.querySelectorAll( '.butaca-carousel' ).forEach( function ( car ) {
		var track = car.querySelector( '.butaca-carousel__track' );
		car.querySelectorAll( '.butaca-arrow' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				track.scrollBy( { left: parseInt( btn.getAttribute( 'data-dir' ), 10 ) * track.clientWidth * 0.8 } );
			} );
		} );
	} );

	/* ---------------- Buscador en vivo ---------------- */
	document.querySelectorAll( '[data-butaca-search]' ).forEach( function ( box ) {
		var input = box.querySelector( 'input' );
		var status = box.querySelector( '.butaca-search__status' );
		var results = box.querySelector( '.butaca-search__results' );
		var timer;
		input.addEventListener( 'input', function () {
			clearTimeout( timer );
			var q = input.value.trim();
			if ( q.length < 2 ) {
				results.innerHTML = '';
				status.textContent = '';
				return;
			}
			status.textContent = 'Buscando…';
			timer = setTimeout( function () {
				api( 'buscar?q=' + encodeURIComponent( q ) )
					.then( function ( data ) {
						if ( input.value.trim() !== q ) {
							return;
						}
						var list = data.movies || [];
						status.textContent = list.length ? list.length + ' resultado' + ( list.length > 1 ? 's' : '' ) + ' para “' + q + '”' : 'Sin resultados para “' + q + '”';
						results.innerHTML = list
							.map( function ( m ) {
								return (
									'<article class="butaca-card"><button type="button" class="butaca-card__btn" data-movie="' + m.id + '" aria-label="Ver ficha de ' + esc( m.title ) + '">' +
									( m.poster ? '<img class="butaca-card__poster" src="' + esc( m.poster ) + '" alt="" loading="lazy">' : '<span class="butaca-card__poster butaca-card__poster--empty">' + esc( m.title ) + '</span>' ) +
									( m.rating > 0 ? '<span class="butaca-rating" style="--r:' + m.rating * 10 + '">' + m.rating.toFixed( 1 ) + '</span>' : '' ) +
									'<span class="butaca-card__cta">Ver ficha</span></button>' +
									'<h3 class="butaca-card__title">' + esc( m.title ) + '</h3><p class="butaca-card__meta">' + esc( m.year ) + '</p></article>'
								);
							} )
							.join( '' );
					} )
					.catch( function () {
						status.textContent = 'Error al buscar. Inténtalo de nuevo.';
					} );
			}, 300 );
		} );
	} );
} )();
