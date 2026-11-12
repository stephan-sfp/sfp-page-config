/*
 * SFP Page Config 3.53.0: I4 Keuzehulp vorm.
 * Drie vragen bepalen welke vormkaart past. Logica (ontwerp akkoord 11-11-2026):
 *   eigen tempo ja: coaching, e-learning eronder;
 *   anders 1 collega coaching, 2-3 open training,
 *   4-8 of 9+ met maatwerk ja incompany, met maatwerk nee open training,
 *   4+ zonder antwoord op vraag 2: de regel vraagt om vraag 2.
 * De incompanykaart blijft altijd zichtbaar (onderaan en rustiger als hij niet
 * past); hooguit drie kaarten. Zonder script staan alle kaarten onder elkaar.
 */
( function () {
	'use strict';

	function waarde( form, naam ) {
		var gekozen = form.querySelector( 'input[name$="-' + naam + '"]:checked' );
		return gekozen ? gekozen.value : '';
	}

	function zet( form ) {
		var kaarten = form.querySelector( '.sfp-kv__kaarten' );
		var uit = form.querySelector( '.sfp-kv__uitkomst' );
		if ( ! kaarten || ! uit ) {
			return;
		}
		var a = waarde( form, 'aantal' );
		var z = waarde( form, 'praktijk' );
		var t = waarde( form, 'tempo' );
		var past = '';
		var zoek = '';
		var tekst = uit.getAttribute( 'data-start' );
		if ( 'ja' === t ) {
			past = 'coaching';
			zoek = 'elearning';
		} else if ( '1' === a ) {
			past = 'coaching';
		} else if ( '2-3' === a ) {
			past = 'training';
		} else if ( '4-8' === a || '9+' === a ) {
			if ( 'ja' === z ) {
				past = 'incompany';
			} else if ( 'nee' === z ) {
				past = 'training';
			} else {
				tekst = uit.getAttribute( 'data-vraag2' );
			}
		}
		if ( past ) {
			tekst = uit.getAttribute( 'data-gekozen' );
		}
		Array.prototype.forEach.call( kaarten.querySelectorAll( '.sfp-kv__kaart' ), function ( k ) {
			var vorm = k.getAttribute( 'data-vorm' );
			k.classList.toggle( 'is-zichtbaar', 'incompany' === vorm || vorm === past || vorm === zoek );
			k.classList.toggle( 'is-uitgelicht', vorm === ( past || 'incompany' ) );
			k.classList.toggle( 'is-zoek', vorm === zoek );
			k.classList.toggle( 'is-groepen', 'incompany' === vorm && 'incompany' === past && '9+' === a );
		} );
		kaarten.classList.toggle( 'is-gekozen', !! past );
		if ( uit.textContent !== tekst ) {
			uit.textContent = tekst;
		}
	}

	function start() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-sfp-kv]' ), function ( form ) {
			form.classList.add( 'is-actief' );
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
