/**
 * Uitklapbare logostrook (3.36.1). Twee rijen zichtbaar, de knop toont de
 * rest en klapt weer in. Past alles in twee rijen, dan verdwijnt de knop.
 */
( function () {
	'use strict';
	function past( strook ) {
		var vak = strook.querySelector( '.sfp-logostrook__vak' );
		return ! vak || vak.scrollHeight <= vak.clientHeight + 1;
	}
	function start() {
		document.querySelectorAll( '.sfp-logostrook--klap' ).forEach( function ( strook ) {
			var knop = strook.querySelector( '.sfp-logostrook__knop' );
			if ( ! knop || strook.dataset.sfpKlaar ) {
				return;
			}
			strook.dataset.sfpKlaar = '1';
			if ( past( strook ) ) {
				strook.classList.remove( 'sfp-logostrook--klap' );
				knop.remove();
				return;
			}
			knop.addEventListener( 'click', function () {
				var open = 'true' !== knop.getAttribute( 'aria-expanded' );
				knop.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				strook.classList.toggle( 'is-open', open );
				var tekst = knop.querySelector( '.sfp-logostrook__knoptekst' ) || knop;
				tekst.textContent = open ? knop.dataset.minder : knop.dataset.meer;
			} );
		} );
	}
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
