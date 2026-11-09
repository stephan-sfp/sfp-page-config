/**
 * Logostrook, rustig wisselen (3.37.1, besluit Stephan 9-11-2026).
 * Vijf vaste plekken (telefoon drie). Om de 2,2 seconden vervaagt op een
 * willekeurige andere plek het logo naar het volgende uit de rest van de
 * lijst. Pauze onder de muis, bij focus, buiten beeld en bij een verborgen
 * tabblad. Bij prefers-reduced-motion wisselt er niets.
 */
( function () {
	'use strict';
	var TUSSEN = 2200;
	var OVERGANG = 1000;
	var smal = window.matchMedia( '(max-width:600px)' );
	var rustig = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	function laad( el ) {
		var img = el.tagName === 'IMG' ? el : el.querySelector( 'img' );
		if ( ! img ) {
			return null;
		}
		// Ook als een lazyload-plugin de bron nog in data-lazy-* bewaart.
		[ el ].concat( Array.prototype.slice.call( el.querySelectorAll( 'source,img' ) ) ).forEach( function ( b ) {
			[ 'srcset', 'src' ].forEach( function ( a ) {
				var w = b.getAttribute( 'data-lazy-' + a );
				if ( w ) {
					b.setAttribute( a, w );
					b.removeAttribute( 'data-lazy-' + a );
				}
			} );
		} );
		// Zonder lazy geldt sizes="auto" niet; dan de vaste maat.
		if ( /^auto,?/.test( img.getAttribute( 'sizes' ) || '' ) ) {
			img.setAttribute( 'sizes', '(max-width: 600px) 165px, 220px' );
		}
		img.loading = 'eager';
		return img;
	}

	function strook( s ) {
		var plekken = Array.prototype.slice.call( s.querySelectorAll( '.sfp-logostrook__logos > li' ) );
		// Elk logo met zijn eigen maat, los van de plek waar het staat.
		var logos = plekken.map( function ( li ) {
			var el = li.firstElementChild;
			if ( el ) {
				el.style.setProperty( '--sfp-logo-b', li.style.getPropertyValue( '--sfp-logo-b' ) );
				el.style.setProperty( '--sfp-logo-h', li.style.getPropertyValue( '--sfp-logo-h' ) );
			}
			return el;
		} ).filter( Boolean );
		var pauze = false, inBeeld = true, laatst = -1, klok = null;
		var n, toon, voorraad;

		function opzet() {
			n = smal.matches ? 3 : 5;
			toon = logos.slice( 0, n );
			voorraad = logos.slice( n );
			// Terug naar de begintoestand: logo i in plek i.
			plekken.forEach( function ( li, i ) {
				if ( logos[ i ] && li.firstElementChild !== logos[ i ] ) {
					li.replaceChildren( logos[ i ] );
				}
				if ( logos[ i ] ) {
					logos[ i ].classList.remove( 'is-uit' );
					logos[ i ].removeAttribute( 'aria-hidden' );
				}
			} );
			if ( voorraad.length ) {
				laad( voorraad[ 0 ] );
			}
		}

		function wissel() {
			if ( pauze || ! inBeeld || document.hidden || ! voorraad.length ) {
				return;
			}
			var nieuw = voorraad[ 0 ];
			var img = laad( nieuw );
			if ( img && ! img.complete ) {
				return;
			}
			var k;
			do {
				k = Math.floor( Math.random() * n );
			} while ( n > 1 && k === laatst );
			laatst = k;
			var oud = toon[ k ];
			voorraad.shift();
			voorraad.push( oud );
			toon[ k ] = nieuw;
			nieuw.classList.add( 'is-uit' );
			plekken[ k ].appendChild( nieuw );
			oud.setAttribute( 'aria-hidden', 'true' );
			window.requestAnimationFrame( function () {
				window.requestAnimationFrame( function () {
					nieuw.classList.remove( 'is-uit' );
					oud.classList.add( 'is-uit' );
				} );
			} );
			window.setTimeout( function () {
				if ( oud.parentNode === plekken[ k ] && toon[ k ] !== oud ) {
					oud.remove();
				}
				oud.classList.remove( 'is-uit' );
				oud.removeAttribute( 'aria-hidden' );
			}, OVERGANG );
			if ( voorraad.length ) {
				laad( voorraad[ 0 ] );
			}
		}

		function regel() {
			window.clearInterval( klok );
			klok = null;
			opzet();
			if ( ! rustig.matches && logos.length > n ) {
				klok = window.setInterval( wissel, TUSSEN );
			}
		}

		s.addEventListener( 'mouseenter', function () { pauze = true; } );
		s.addEventListener( 'mouseleave', function () { pauze = false; } );
		s.addEventListener( 'focusin', function () { pauze = true; } );
		s.addEventListener( 'focusout', function () { pauze = false; } );
		if ( 'IntersectionObserver' in window ) {
			new IntersectionObserver( function ( r ) {
				inBeeld = r[ 0 ].isIntersecting;
			} ).observe( s );
		}
		[ smal, rustig ].forEach( function ( mq ) {
			if ( mq.addEventListener ) {
				mq.addEventListener( 'change', regel );
			}
		} );
		regel();
	}

	function start() {
		document.querySelectorAll( '.sfp-logostrook--wissel' ).forEach( function ( s ) {
			if ( s.dataset.sfpKlaar ) {
				return;
			}
			s.dataset.sfpKlaar = '1';
			strook( s );
		} );
	}
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
