/*
 * SFP Page Config 3.45.0: Lees verder op de reviewkaart.
 * De tekst staat in de opmaak op drie regels. De knop staat verborgen in de
 * pagina en verschijnt alleen als de tekst echt langer is; opnieuw gemeten
 * als de kaart van maat verandert. De hele tekst blijft in de HTML.
 */
( function () {
	'use strict';

	function meet( knop ) {
		var kaart = knop.closest( '.sfp-review-kaart' );
		var tekst = kaart ? kaart.querySelector( '.sfp-review-kaart__tekst' ) : null;
		if ( ! tekst || kaart.classList.contains( 'is-open' ) ) {
			return;
		}
		knop.hidden = tekst.scrollHeight <= tekst.clientHeight + 1;
	}

	function start() {
		var knoppen = document.querySelectorAll( '.sfp-review-kaart__meer' );
		var ro = typeof ResizeObserver !== 'undefined' ? new ResizeObserver( function ( items ) {
			items.forEach( function ( it ) {
				var knop = it.target.parentNode && it.target.parentNode.querySelector( '.sfp-review-kaart__meer' );
				if ( knop ) {
					meet( knop );
				}
			} );
		} ) : null;
		Array.prototype.forEach.call( knoppen, function ( knop ) {
			var kaart = knop.closest( '.sfp-review-kaart' );
			var tekst = kaart ? kaart.querySelector( '.sfp-review-kaart__tekst' ) : null;
			if ( ! tekst ) {
				return;
			}
			knop.setAttribute( 'data-meer', knop.textContent );
			meet( knop );
			if ( ro ) {
				ro.observe( tekst );
			}
			knop.addEventListener( 'click', function () {
				var open = kaart.classList.toggle( 'is-open' );
				knop.textContent = open ? knop.getAttribute( 'data-minder' ) : knop.getAttribute( 'data-meer' );
				knop.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				if ( ! open ) {
					meet( knop );
				}
			} );
		} );
		window.addEventListener( 'load', function () {
			Array.prototype.forEach.call( knoppen, meet );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
