/*
 * SFP Page Config 3.53.0: I4 Keuzehulp vorm. Logica 3.64.0 (besluiten Stephan 14-11-2026):
 *   eigen tempo ja: coaching, e-learning eronder;
 *   anders 1 collega: coaching, open training eronder;
 *   2-3: incompany met de knop naar het adviesgesprek, open training eronder;
 *   4-8 of 9+: incompany.
 * De vraag naar de praktijk is weg. De incompanykaart blijft altijd zichtbaar
 * (onderaan en rustiger als hij niet past); hooguit drie kaarten. Zonder
 * script staan alle kaarten onder elkaar.
 */
( function () {
	'use strict';

	function waarde( form, naam ) {
		var gekozen = form.querySelector( 'input[name$="-' + naam + '"]:checked' );
		return gekozen ? gekozen.value : '';
	}

	// Gespreksknop zonder eigen doel: het anker van het slot (Kolommen Boeking).
	function slot( form ) {
		var a = form.querySelector( '[data-sfp-kv-slot]' );
		if ( ! a ) {
			return;
		}
		var k = document.querySelector( '.sfp-kolommen--boeking' );
		var plek = k ? ( k.id ? k : k.closest( '[id]' ) ) : null;
		if ( plek && plek.id ) {
			a.setAttribute( 'href', '#' + plek.id );
		} else {
			a.closest( '.sfp-kv__gesprek' ).remove();
		}
		a.removeAttribute( 'data-sfp-kv-slot' );
	}

	function zet( form ) {
		var kaarten = form.querySelector( '.sfp-kv__kaarten' );
		var uit = form.querySelector( '.sfp-kv__uitkomst' );
		if ( ! kaarten || ! uit ) {
			return;
		}
		var a = waarde( form, 'aantal' );
		var t = waarde( form, 'tempo' );
		var past = '';
		var zoek = '';
		var tekst = uit.getAttribute( 'data-start' );
		var gesprek = false;
		if ( 'ja' === t ) {
			past = 'coaching';
			zoek = 'elearning';
		} else if ( '1' === a ) {
			past = 'coaching';
			zoek = 'training';
		} else if ( '2-3' === a ) {
			past = 'incompany';
			zoek = 'training';
			gesprek = !! form.querySelector( '.sfp-kv__gesprek' );
		} else if ( '4-8' === a || '9+' === a ) {
			past = 'incompany';
		}
		if ( past ) {
			tekst = uit.getAttribute( gesprek ? 'data-gesprek' : 'data-gekozen' ) || uit.getAttribute( 'data-gekozen' );
		}
		Array.prototype.forEach.call( kaarten.querySelectorAll( '.sfp-kv__kaart' ), function ( k ) {
			var vorm = k.getAttribute( 'data-vorm' );
			k.classList.toggle( 'is-zichtbaar', 'incompany' === vorm || vorm === past || vorm === zoek );
			k.classList.toggle( 'is-uitgelicht', vorm === ( past || 'incompany' ) );
			k.classList.toggle( 'is-zoek', vorm === zoek );
			k.classList.toggle( 'is-groepen', 'incompany' === vorm && 'incompany' === past && '9+' === a );
		} );
		form.classList.toggle( 'is-gesprek', gesprek );
		var g = form.querySelector( '.sfp-kv__gesprek' );
		if ( g ) {
			g.hidden = ! gesprek;
		}
		kaarten.classList.toggle( 'is-gekozen', !! past );
		if ( uit.textContent !== tekst ) {
			uit.textContent = tekst;
		}
	}

	function start() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-sfp-kv]' ), function ( form ) {
			form.classList.add( 'is-actief' );
			slot( form );
			form.addEventListener( 'change', function () {
				zet( form );
			} );
			zet( form );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
