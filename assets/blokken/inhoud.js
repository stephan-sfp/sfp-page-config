/*
 * SFP Page Config 3.20.0: kopieerblok en bronnenlijst op de site.
 * Staat onderaan de pagina en wordt niet uitgesteld door WP Rocket, dus de
 * eerste klik werkt meteen.
 */
( function () {
	'use strict';

	/* Kopieerblok */
	function tekstVan( blok ) {
		var t = blok.querySelector( '.sfp-kopieer__tekst' );
		return t ? ( t.innerText || t.textContent || '' ).replace( /\n{3,}/g, '\n\n' ).trim() : '';
	}

	function selecteer( el ) {
		var r = document.createRange();
		r.selectNodeContents( el );
		var s = window.getSelection();
		s.removeAllRanges();
		s.addRange( r );
	}

	function oudeManier( blok ) {
		var t = blok.querySelector( '.sfp-kopieer__tekst' );
		open( blok, true );
		selecteer( t );
		var gelukt = false;
		try {
			gelukt = document.execCommand( 'copy' );
		} catch ( e ) {
			gelukt = false;
		}
		return gelukt;
	}

	function gelukt( blok ) {
		var knop = blok.querySelector( '.sfp-kopieer__knoptekst' );
		var melding = blok.querySelector( '.sfp-kopieer__melding' );
		if ( ! knop.getAttribute( 'data-oud' ) ) {
			knop.setAttribute( 'data-oud', knop.textContent );
		}
		knop.textContent = blok.getAttribute( 'data-klaar' );
		blok.classList.add( 'is-gekopieerd' );
		melding.textContent = '';
		clearTimeout( blok._sfpTijd );
		blok._sfpTijd = setTimeout( function () {
			knop.textContent = knop.getAttribute( 'data-oud' );
			blok.classList.remove( 'is-gekopieerd' );
		}, 3000 );
	}

	function mislukt( blok ) {
		open( blok, true );
		selecteer( blok.querySelector( '.sfp-kopieer__tekst' ) );
		blok.querySelector( '.sfp-kopieer__melding' ).textContent = blok.getAttribute( 'data-mislukt' );
	}

	function open( blok, aan ) {
		var meer = blok.querySelector( '.sfp-kopieer__meer' );
		if ( ! meer ) {
			return;
		}
		blok.classList.toggle( 'is-open', aan );
		meer.setAttribute( 'aria-expanded', aan ? 'true' : 'false' );
		meer.textContent = blok.getAttribute( aan ? 'data-minder' : 'data-meer' );
	}

	function kopieer( blok ) {
		var tekst = tekstVan( blok );
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( tekst ).then( function () {
				gelukt( blok );
			}, function () {
				if ( oudeManier( blok ) ) {
					window.getSelection().removeAllRanges();
					gelukt( blok );
				} else {
					mislukt( blok );
				}
			} );
			return;
		}
		if ( oudeManier( blok ) ) {
			window.getSelection().removeAllRanges();
			gelukt( blok );
		} else {
			mislukt( blok );
		}
	}

	[].forEach.call( document.querySelectorAll( '[data-sfp-kopieer]' ), function ( blok ) {
		var knop = blok.querySelector( '.sfp-kopieer__knop' );
		var meer = blok.querySelector( '.sfp-kopieer__meer' );
		if ( knop ) {
			knop.addEventListener( 'click', function () {
				kopieer( blok );
			} );
		}
		if ( meer ) {
			var tekst = blok.querySelector( '.sfp-kopieer__tekst' );
			// Past de tekst toch in het ingeklapte vak, dan geen Toon alles.
			if ( tekst && tekst.scrollHeight <= tekst.clientHeight + 4 ) {
				blok.classList.remove( 'is-lang' );
				meer.hidden = true;
			}
			meer.addEventListener( 'click', function () {
				var nuOpen = ! blok.classList.contains( 'is-open' );
				open( blok, nuOpen );
				if ( ! nuOpen ) {
					blok.scrollIntoView( { block: 'nearest' } );
				}
			} );
		}
	} );

	/* Bronnenlijst: zoeken terwijl je typt */
	function kaal( s ) {
		s = ( s || '' ).toLowerCase();
		return s.normalize ? s.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ) : s;
	}

	[].forEach.call( document.querySelectorAll( '[data-sfp-bronnen]' ), function ( lijst ) {
		var veld = lijst.querySelector( '.sfp-bronnen__zoek input' );
		var geen = lijst.querySelector( '.sfp-bronnen__geen' );
		var items = [].map.call( lijst.querySelectorAll( '.sfp-bron-item' ), function ( item ) {
			var citaat = item.querySelector( '.sfp-bron-citaat' );
			var samen = item.querySelector( '.sfp-bron-samenvatting' );
			return { el: item, tekst: kaal( ( citaat ? citaat.textContent : '' ) + ' ' + ( samen ? samen.textContent : '' ) ) };
		} );
		var groepen = [].slice.call( lijst.querySelectorAll( '.sfp-bronnen__groep' ) );
		var letters = {};
		[].forEach.call( lijst.querySelectorAll( '.sfp-bib-nav a' ), function ( a ) {
			letters[ ( a.getAttribute( 'href' ) || '' ).slice( 1 ) ] = a;
		} );
		if ( ! veld ) {
			return;
		}
		var wacht;
		veld.addEventListener( 'input', function () {
			clearTimeout( wacht );
			wacht = setTimeout( function () {
				var woorden = kaal( veld.value ).split( /\s+/ ).filter( Boolean );
				var totaal = 0;
				items.forEach( function ( i ) {
					var past = woorden.every( function ( w ) {
						return i.tekst.indexOf( w ) !== -1;
					} );
					i.el.hidden = ! past;
					if ( past ) {
						totaal++;
					}
				} );
				groepen.forEach( function ( g ) {
					var zichtbaar = !! g.querySelector( '.sfp-bron-item:not([hidden])' );
					g.hidden = ! zichtbaar;
					var a = letters[ g.getAttribute( 'data-letter' ) ];
					if ( a ) {
						a.classList.toggle( 'is-leeg', ! zichtbaar );
						a.setAttribute( 'aria-disabled', zichtbaar ? 'false' : 'true' );
					}
				} );
				geen.hidden = totaal > 0;
			}, 120 );
		} );
	} );
}() );
