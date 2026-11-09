/**
 * Keuzehulp (3.41.0): van vraag naar vraag, naar Help mij kiezen of naar een
 * pagina (gewone link). Terug gaat één stap terug; Opnieuw begint bij de
 * eerste vraag. De nieuwe vraag krijgt de focus.
 */
( function () {
	'use strict';
	function start( kaart ) {
		if ( kaart.dataset.sfpKlaar ) {
			return;
		}
		kaart.dataset.sfpKlaar = '1';
		var stappen = Array.prototype.slice.call( kaart.querySelectorAll( '.sfp-keuzehulp__stap' ) );
		if ( ! stappen.length ) {
			return;
		}
		var pad = [ stappen[ 0 ].dataset.stap ];
		function toon( id, focus ) {
			stappen.forEach( function ( s ) {
				s.hidden = s.dataset.stap !== id;
			} );
			if ( focus ) {
				var kop = kaart.querySelector( '.sfp-keuzehulp__stap[data-stap="' + id + '"] .sfp-keuzehulp__vraag' );
				if ( kop ) {
					kop.focus( { preventScroll: true } );
				}
				var r = kaart.getBoundingClientRect();
				if ( r.top < 0 ) {
					kaart.scrollIntoView( { block: 'start', behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth' } );
				}
			}
		}
		kaart.addEventListener( 'click', function ( e ) {
			var knop = e.target.closest( 'button' );
			if ( ! knop || ! kaart.contains( knop ) ) {
				return;
			}
			if ( knop.hasAttribute( 'data-naar' ) ) {
				var doel = knop.getAttribute( 'data-naar' );
				if ( kaart.querySelector( '.sfp-keuzehulp__stap[data-stap="' + doel + '"]' ) ) {
					pad.push( doel );
					toon( doel, true );
				}
			} else if ( knop.hasAttribute( 'data-terug' ) ) {
				if ( pad.length > 1 ) {
					pad.pop();
				}
				toon( pad[ pad.length - 1 ], true );
			} else if ( knop.hasAttribute( 'data-opnieuw' ) ) {
				pad = [ stappen[ 0 ].dataset.stap ];
				toon( pad[ 0 ], true );
			}
		} );
	}
	function alle() {
		document.querySelectorAll( '[data-sfp-keuzehulp]' ).forEach( start );
	}
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', alle );
	} else {
		alle();
	}
}() );
