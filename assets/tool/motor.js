/**
 * SFP Page Config - Tools: de motor (gedeeld door alle soorten)
 *
 * Leest per tool de beschrijving uit het JSON-script in de pagina en bouwt
 * de tool in de browser. Bewaart niets op de server. Wat de soorten delen:
 * stappenbalk, voortgang, Vorige, voortgang in localStorage, deellink,
 * pdf, Word, mail naar jezelf, duimpjes, formulier na de uitslag.
 *
 * Een soort registreert zich met window.sfpToolMotor.soort('test', fabriek).
 * De fabriek krijgt (tool) en levert { start: fn } op; zie test.js.
 *
 * Teksten: window.sfpToolConfig.teksten (uit includes/artikel-teksten.php).
 *
 * @since 3.8.0
 */
( function () {
	'use strict';
	if ( window.sfpToolMotor ) { return; }

	var CONFIG = window.sfpToolConfig || {};
	var TEKST = CONFIG.teksten || {};
	var SOORTEN = {};

	/* ---------------------------------------------------------------- *
	 * Hulpjes
	 * ---------------------------------------------------------------- */

	function t( sleutel ) {
		var s = Object.prototype.hasOwnProperty.call( TEKST, sleutel ) ? TEKST[ sleutel ] : sleutel;
		var args = Array.prototype.slice.call( arguments, 1 );
		var i = 0;
		return String( s ).replace( /%(\d+\$)?[sd]/g, function ( m, pos ) {
			var idx = pos ? parseInt( pos, 10 ) - 1 : i++;
			return args[ idx ] === undefined ? '' : String( args[ idx ] );
		} );
	}

	function el( tag, attrs, kinderen ) {
		var e = document.createElement( tag );
		if ( attrs ) {
			Object.keys( attrs ).forEach( function ( k ) {
				var v = attrs[ k ];
				if ( v === null || v === undefined || v === false ) { return; }
				if ( 'class' === k ) { e.className = v; }
				else if ( 'text' === k ) { e.textContent = v; }
				else if ( 'html' === k ) { e.innerHTML = v; }
				else if ( 0 === k.indexOf( 'on' ) && 'function' === typeof v ) { e.addEventListener( k.slice( 2 ), v ); }
				else if ( true === v ) { e.setAttribute( k, '' ); }
				else { e.setAttribute( k, v ); }
			} );
		}
		( kinderen || [] ).forEach( function ( k ) {
			if ( k === null || k === undefined || k === false ) { return; }
			e.appendChild( 'string' === typeof k ? document.createTextNode( k ) : k );
		} );
		return e;
	}

	function alineas( tekst ) {
		return String( tekst || '' ).split( /\n\s*\n/ ).map( function ( a ) { return a.trim(); } ).filter( Boolean );
	}

	/* Afronden half naar boven met een kleine marge (57,49999999999999 wordt 58), gelijk aan sfp_page_config_tool_rond() in PHP. */
	function rond( w ) {
		return Math.floor( Number( w ) + 0.5 + 1e-9 );
	}

	function getal( w ) {
		w = Number( w ) || 0;
		return String( Math.floor( w ) === w ? w : rond( w * 10 ) / 10 );
	}

	/* Base64-url van een JSON-object, voor de deellink. */
	function codeer( obj ) {
		try {
			var s = JSON.stringify( obj );
			var b = btoa( unescape( encodeURIComponent( s ) ) );
			return b.replace( /\+/g, '-' ).replace( /\//g, '_' ).replace( /=+$/, '' );
		} catch ( e ) { return ''; }
	}
	function decodeer( s ) {
		try {
			s = String( s || '' ).replace( /-/g, '+' ).replace( /_/g, '/' );
			while ( s.length % 4 ) { s += '='; }
			return JSON.parse( decodeURIComponent( escape( atob( s ) ) ) );
		} catch ( e ) { return null; }
	}

	/* ---------------------------------------------------------------- *
	 * Opslag in de browser (voortgang en duimpjes)
	 * ---------------------------------------------------------------- */

	var opslag = {
		lees: function ( sleutel ) {
			try { var v = localStorage.getItem( sleutel ); return v ? JSON.parse( v ) : null; } catch ( e ) { return null; }
		},
		schrijf: function ( sleutel, waarde ) {
			try { localStorage.setItem( sleutel, JSON.stringify( waarde ) ); } catch ( e ) {}
		},
		wis: function ( sleutel ) {
			try { localStorage.removeItem( sleutel ); } catch ( e ) {}
		}
	};

	/* ---------------------------------------------------------------- *
	 * Verzoeken naar de plugin
	 * ---------------------------------------------------------------- */

	function post( route, gegevens ) {
		return fetch( ( CONFIG.rest || '/wp-json/sfp/v1/tool/' ) + route, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( gegevens )
		} ).then( function ( r ) {
			return r.json().then( function ( d ) { return { ok: r.ok, status: r.status, data: d }; }, function () { return { ok: r.ok, status: r.status, data: {} }; } );
		} );
	}

	/* ---------------------------------------------------------------- *
	 * Bibliotheken van derden, lokaal, pas bij gebruik
	 * ---------------------------------------------------------------- */

	var geladen = {};
	function script( naam, pad, test ) {
		if ( test() ) { return Promise.resolve(); }
		if ( geladen[ naam ] ) { return geladen[ naam ]; }
		geladen[ naam ] = new Promise( function ( res, rej ) {
			var s = document.createElement( 'script' );
			/* Mislukt het laden, dan probeert een volgende klik het opnieuw. */
			function mis() { delete geladen[ naam ]; if ( s.parentNode ) { s.parentNode.removeChild( s ); } rej( new Error( naam ) ); }
			s.src = ( CONFIG.vendor || '' ) + pad;
			s.async = true;
			s.onload = function () { if ( test() ) { res(); } else { mis(); } };
			s.onerror = mis;
			document.head.appendChild( s );
		} );
		return geladen[ naam ];
	}
	function ttfBase64( pad ) {
		return fetch( ( CONFIG.vendor || '' ) + pad ).then( function ( r ) {
			if ( ! r.ok ) { throw new Error( pad ); }
			return r.arrayBuffer();
		} ).then( function ( buf ) {
			var by = new Uint8Array( buf ), s = '', C = 8192;
			for ( var i = 0; i < by.length; i += C ) { s += String.fromCharCode.apply( null, by.subarray( i, i + C ) ); }
			return btoa( s );
		} );
	}
	var fonts = null;
	function laadPdf() {
		return script( 'jspdf', 'jspdf.umd.min.js', function () { return !! ( window.jspdf && window.jspdf.jsPDF ); } ).then( function () {
			if ( fonts ) { return fonts; }
			fonts = Promise.all( [ ttfBase64( 'DejaVuSans.ttf' ), ttfBase64( 'DejaVuSans-Bold.ttf' ) ] ).then( function ( p ) { return { normaal: p[ 0 ], vet: p[ 1 ] }; }, function ( e ) { fonts = null; throw e; } );
			return fonts;
		} );
	}
	function laadWord() {
		return script( 'docx', 'docx.min.js', function () { return !! ( window.docx && window.docx.Document ); } );
	}

	/* Kleuren van de site uit de CSS-variabelen, als [r,g,b] voor de pdf. */
	function kleur( naam, terugval ) {
		try {
			var probe = document.createElement( 'span' );
			probe.style.color = 'var(' + naam + ')';
			probe.style.display = 'none';
			document.body.appendChild( probe );
			var c = getComputedStyle( probe ).color;
			document.body.removeChild( probe );
			var m = c.match( /(\d+)[,\s]+(\d+)[,\s]+(\d+)/ );
			if ( m ) { return [ parseInt( m[ 1 ], 10 ), parseInt( m[ 2 ], 10 ), parseInt( m[ 3 ], 10 ) ]; }
		} catch ( e ) {}
		return terugval;
	}

	/* ---------------------------------------------------------------- *
	 * Een tool op de pagina
	 * ---------------------------------------------------------------- */

	function Tool( wortel ) {
		this.wortel = wortel;
		this.id = parseInt( wortel.getAttribute( 'data-tool' ), 10 ) || 0;
		this.slug = wortel.getAttribute( 'data-slug' ) || String( this.id );
		this.titel = wortel.getAttribute( 'data-titel' ) || document.title;
		this.weergave = wortel.getAttribute( 'data-weergave' ) || 'volledig';
		this.app = wortel.querySelector( '.sfp-tool__app' );
		this.formulier = wortel.querySelector( '.sfp-tool__formulier' );
		var bron = wortel.querySelector( 'script.sfp-tool__data' );
		var b = null;
		try { b = JSON.parse( bron ? bron.textContent : '' ); } catch ( e ) { b = null; }
		this.b = b;
		this.sleutel = 'sfp-tool-' + ( CONFIG.site || '' ) + '-' + this.id;
		this.sr = el( 'span', { class: 'sfp-tool__sr', 'aria-live': 'polite' } );
	}

	Tool.prototype.start = function () {
		if ( ! this.b || ! this.app ) { return false; }
		var fabriek = SOORTEN[ this.b.soort ];
		if ( ! fabriek ) { return false; }
		this.app.hidden = false;
		this.app.appendChild( this.sr );
		this.motor = fabriek( this );
		this.motor.start();
		return true;
	};

	Tool.prototype.zeg = function ( tekst ) {
		var sr = this.sr;
		sr.textContent = '';
		setTimeout( function () { sr.textContent = tekst; }, 40 );
	};

	/* Voortgang in de browser. */
	Tool.prototype.bewaar = function ( staat ) {
		opslag.schrijf( this.sleutel, { v: 1, tijd: Date.now(), staat: staat } );
	};
	Tool.prototype.bewaard = function () {
		var d = opslag.lees( this.sleutel );
		return d && d.staat ? d.staat : null;
	};
	Tool.prototype.wisBewaard = function () {
		opslag.wis( this.sleutel );
	};

	/* Deellink: ?t=<antwoorden>; dezelfde pagina opent op het resultaat. */
	Tool.prototype.uitDeellink = function () {
		try {
			var p = new URLSearchParams( window.location.search );
			var s = p.get( 't' );
			if ( ! s ) { return null; }
			var d = decodeer( s );
			return d && d.id === this.id && d.a ? d.a : null;
		} catch ( e ) { return null; }
	};
	Tool.prototype.deellink = function ( antwoorden ) {
		var basis = window.location.origin + window.location.pathname;
		return basis + '?t=' + codeer( { id: this.id, a: antwoorden } );
	};
	Tool.prototype.deel = function ( antwoorden, melding ) {
		var url = this.deellink( antwoorden );
		var zelf = this;
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( url ).then( function () { melding( t( 'tool_deellink_ok' ) ); zelf.zeg( t( 'tool_deellink_ok' ) ); }, function () { melding( t( 'tool_deellink_toon', url ) ); } );
		} else {
			melding( t( 'tool_deellink_toon', url ) );
		}
	};

	/* Mail naar jezelf: de server rekent opnieuw, bouwt de mail en de link naar het resultaat. */
	Tool.prototype.mail = function ( email, antwoorden, website ) {
		return post( 'mail', { tool: this.id, email: email, antwoorden: antwoorden, website: website || '' } );
	};

	/* Duimpjes: de telling per tool en optioneel een tekst. Bij een wissel gaat de vorige stem eraf. */
	Tool.prototype.duim = function ( stem, tekst, email, website, vorige ) {
		return post( 'duim', { tool: this.slug, stem: stem, vorige: vorige || '', tekst: tekst || '', email: email || '', website: website || '' } );
	};

	/* De widget "Heeft deze tool je geholpen?" */
	Tool.prototype.duimWidget = function () {
		var zelf = this;
		var sleutel = this.sleutel + '-duim';
		var eerder = opslag.lees( sleutel );
		var wrap = el( 'div', { class: 'sfp-tool__duim' } );
		var vraag = el( 'p', { class: 'sfp-tool__duim-vraag', text: t( 'tool_duim_vraag' ) } );
		var knoppen = el( 'div', { class: 'sfp-tool__duim-knoppen' } );
		var vervolg = el( 'div', { class: 'sfp-tool__duim-vervolg', hidden: true } );
		var gekozen = eerder && eerder.stem ? eerder.stem : '';
		var klaar = !! ( eerder && eerder.klaar );
		function knop( stem, label ) {
			return el( 'button', { type: 'button', class: 'sfp-tool__duim-knop' + ( gekozen === stem ? ' is-gekozen' : '' ), 'data-stem': stem, 'aria-pressed': gekozen === stem ? 'true' : 'false', disabled: klaar, onclick: function () {
				if ( klaar ) { return; }
				gekozen = stem;
				Array.prototype.forEach.call( knoppen.children, function ( k ) { var is = k.getAttribute( 'data-stem' ) === stem; k.classList.toggle( 'is-gekozen', is ); k.setAttribute( 'aria-pressed', is ? 'true' : 'false' ); } );
				if ( ! eerder || eerder.stem !== stem ) {
					zelf.duim( stem, '', '', '', eerder && eerder.stem ? eerder.stem : '' ).catch( function () {} );
				}
				eerder = { stem: stem, klaar: false };
				opslag.schrijf( sleutel, eerder );
				vervolg.hidden = false;
				veld.focus();
			} }, [ label ] );
		}
		var veld = el( 'textarea', { class: 'sfp-tool__veld', rows: '2', maxlength: '600', placeholder: t( 'tool_duim_meer' ), 'aria-label': t( 'tool_duim_meer' ) } );
		var mail = el( 'input', { type: 'email', class: 'sfp-tool__veld', maxlength: '160', placeholder: t( 'tool_duim_mail_hint' ), 'aria-label': t( 'tool_duim_mail_hint' ), autocomplete: 'email' } );
		var hp = el( 'input', { type: 'text', name: 'website', tabindex: '-1', autocomplete: 'off', class: 'sfp-tool__hp', 'aria-hidden': 'true' } );
		var dank = el( 'p', { class: 'sfp-tool__duim-dank', hidden: true, text: t( 'tool_duim_dank' ) } );
		var verstuur = el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--klein', text: t( 'tool_duim_verstuur' ), onclick: function () {
			var tekst = veld.value.trim();
			verstuur.disabled = true;
			var p = tekst ? zelf.duim( gekozen || 'omhoog', tekst, mail.value.trim(), hp.value ) : Promise.resolve();
			p.then( function () {
				eerder = { stem: gekozen, klaar: true };
				opslag.schrijf( sleutel, eerder );
				vervolg.hidden = true;
				dank.hidden = false;
				Array.prototype.forEach.call( knoppen.children, function ( k ) { k.disabled = true; } );
			}, function () { verstuur.disabled = false; } );
		} } );
		knoppen.appendChild( knop( 'omhoog', t( 'tool_duim_ja' ) ) );
		knoppen.appendChild( knop( 'omlaag', t( 'tool_duim_nee' ) ) );
		vervolg.appendChild( veld );
		vervolg.appendChild( mail );
		vervolg.appendChild( hp );
		vervolg.appendChild( verstuur );
		wrap.appendChild( vraag );
		wrap.appendChild( knoppen );
		wrap.appendChild( vervolg );
		wrap.appendChild( dank );
		if ( klaar ) { dank.hidden = false; }
		return wrap;
	};

	/* ---------------------------------------------------------------- *
	 * Pdf en Word, uit een platte beschrijving van het resultaat
	 *   blokken: [{ type: 'kop'|'subkop'|'tekst'|'lijst'|'afvink'|'balk'|'tabel', ... }]
	 * ---------------------------------------------------------------- */

	Tool.prototype.pdf = function ( blokken, bestandsnaam ) {
		var zelf = this;
		return laadPdf().then( function ( f ) {
			var jsPDF = window.jspdf.jsPDF;
			var doc = new jsPDF( { unit: 'mm', format: 'a4' } );
			doc.addFileToVFS( 'DejaVuSans.ttf', f.normaal );
			doc.addFont( 'DejaVuSans.ttf', 'DejaVuSans', 'normal' );
			doc.addFileToVFS( 'DejaVuSans-Bold.ttf', f.vet );
			doc.addFont( 'DejaVuSans-Bold.ttf', 'DejaVuSans', 'bold' );
			/* Dezelfde rollen als op de pagina: koppen en opschriften primair in hoofdletters, de uitslag in een vlak
			   in Tint 2, scorebalken in Tint 1 met de uitslag in primair, bolletjes in de secundaire kleur. De kleur
			   voor links en knoppen hoort niet in een document zonder knoppen. */
			var primair = kleur( '--sfp-b-primair', [ 20, 20, 60 ] );
			var secundair = kleur( '--sfp-b-secundair', primair );
			var tint1 = kleur( '--sfp-b-tint1', [ 200, 220, 240 ] );
			var tint = kleur( '--sfp-b-tint2', [ 230, 240, 250 ] );
			var body = kleur( '--sfp-b-body', [ 87, 87, 87 ] );
			var L = 20, R = 190, y = 22, W = R - L, inzet = 0;
			function vul( k ) { doc.setFillColor( k[ 0 ], k[ 1 ], k[ 2 ] ); }
			function nieuw( h ) { if ( y + h > 277 ) { doc.addPage(); y = 22; } }
			function hoofd( t ) { return String( t ).toLocaleUpperCase( document.documentElement.lang || undefined ); }
			function regels( tekst, maat, vet, inspring ) {
				doc.setFont( 'DejaVuSans', vet ? 'bold' : 'normal' );
				doc.setFontSize( maat );
				return doc.splitTextToSize( String( tekst ), W - 2 * inzet - ( inspring || 0 ) );
			}
			function schrijf( tekst, maat, vet, kl, inspring ) {
				var rs = regels( tekst, maat, vet, inspring );
				doc.setTextColor( kl[ 0 ], kl[ 1 ], kl[ 2 ] );
				rs.forEach( function ( r ) { nieuw( maat * 0.45 ); doc.text( r, L + inzet + ( inspring || 0 ), y ); y += maat * 0.42; } );
			}
			function maten( bl ) {
				if ( 'kicker' === bl.type ) { return { tekst: hoofd( bl.tekst ), maat: 8.5, vet: true, kl: primair, voor: inzet ? 0 : 6, na: 1 }; }
				if ( 'kop' === bl.type ) { return { tekst: hoofd( bl.tekst ), maat: 19, vet: true, kl: primair, voor: 6, na: 2 }; }
				return null;
			}
			vul( primair );
			doc.rect( 0, 0, 210, 14, 'F' );
			doc.setFont( 'DejaVuSans', 'bold' ); doc.setFontSize( 10 ); doc.setTextColor( 255, 255, 255 );
			doc.text( hoofd( CONFIG.sitenaam || '' ), L, 9 );
			doc.setFontSize( 9 ); doc.setFont( 'DejaVuSans', 'normal' );
			doc.text( zelf.titel, R, 9, { align: 'right' } );
			/* De uitslag bovenaan (opschrift, titel, uitleg) in een vlak in Tint 2: eerst de hoogte meten, dan tekenen. */
			var eind = 0;
			while ( eind < blokken.length && ( 'kicker' === blokken[ eind ].type || 'kop' === blokken[ eind ].type || ( 'tekst' === blokken[ eind ].type && eind > 0 ) ) ) { eind++; }
			if ( eind > 0 ) {
				inzet = 7;
				var h = 2 * inzet - 2;
				blokken.slice( 0, eind ).forEach( function ( bl ) {
					var m = maten( bl );
					if ( m ) { h += m.voor + regels( m.tekst, m.maat, m.vet ).length * m.maat * 0.42 + m.na; }
					else { alineas( bl.tekst ).forEach( function ( a ) { h += regels( a, 10.5, false ).length * 10.5 * 0.42 + 2.5; } ); }
				} );
				y += 2;
				vul( tint );
				doc.roundedRect( L, y - 5, W, h, 4, 4, 'F' );
				y += inzet - 1;
			}
			blokken.forEach( function ( bl, nr ) {
				if ( eind > 0 && nr === eind ) { inzet = 0; y += 8; }
				var m = maten( bl );
				switch ( bl.type ) {
					case 'kop': case 'kicker': y += m.voor; schrijf( m.tekst, m.maat, m.vet, m.kl ); y += m.na; break;
					case 'subkop': y += 5; nieuw( 12 ); schrijf( hoofd( bl.tekst ), 12, true, primair ); y += 2.5; break;
					case 'tekst': alineas( bl.tekst ).forEach( function ( a ) { schrijf( a, 10.5, false, body ); y += 2.5; } ); break;
					case 'lijst': ( bl.items || [] ).forEach( function ( it, i ) {
						nieuw( 8 );
						if ( bl.nummers ) {
							vul( tint ); doc.circle( L + 2.6, y - 1.3, 2.6, 'F' );
							doc.setFont( 'DejaVuSans', 'bold' ); doc.setFontSize( 8 ); doc.setTextColor( primair[ 0 ], primair[ 1 ], primair[ 2 ] );
							doc.text( String( i + 1 ), L + 2.6, y - 0.3, { align: 'center' } );
						} else { vul( secundair ); doc.circle( L + 1.6, y - 1.3, 1, 'F' ); }
						schrijf( it, 10.5, false, body, 8 ); y += 1.8;
					} ); y += 2; break;
					case 'afvink': ( bl.items || [] ).forEach( function ( it ) { nieuw( 8 ); doc.setDrawColor( primair[ 0 ], primair[ 1 ], primair[ 2 ] ); doc.rect( L + 0.5, y - 3.4, 3.6, 3.6 ); schrijf( it, 10.5, false, body, 8 ); y += 1.5; } ); y += 2; break;
					case 'balk':
						nieuw( 12 );
						doc.setFont( 'DejaVuSans', 'bold' ); doc.setFontSize( 9 ); doc.setTextColor( primair[ 0 ], primair[ 1 ], primair[ 2 ] );
						doc.text( hoofd( bl.naam ), L, y );
						doc.setFontSize( 8.5 );
						doc.text( hoofd( bl.label ), R, y, { align: 'right' } );
						y += 2;
						vul( tint ); doc.roundedRect( L, y, W, 3, 1.5, 1.5, 'F' );
						var breed = W * Math.max( 0, Math.min( 1, bl.deel || 0 ) );
						if ( breed > 0 ) { vul( bl.uitslag ? primair : tint1 ); doc.roundedRect( L, y, Math.max( breed, 3 ), 3, 1.5, 1.5, 'F' ); }
						y += 8;
						break;
					case 'tabel':
						/* Per rij de eerste cel als opschrift, de rest eronder. */
						( bl.rijen || [] ).forEach( function ( rij ) {
							nieuw( 10 );
							schrijf( rij[ 0 ], 9.5, true, primair );
							if ( rij.length > 1 ) { schrijf( rij.slice( 1 ).join( ' · ' ), 9.5, false, body ); }
							y += 2;
						} ); y += 1; break;
				}
			} );
			var n = doc.getNumberOfPages();
			for ( var i = 1; i <= n; i++ ) {
				doc.setPage( i ); doc.setFontSize( 8 ); doc.setFont( 'DejaVuSans', 'normal' ); doc.setTextColor( body[ 0 ], body[ 1 ], body[ 2 ] );
				doc.text( ( CONFIG.siteurl || '' ) + '  ·  ' + i + ' / ' + n, 105, 290, { align: 'center' } );
			}
			doc.save( bestandsnaam + '.pdf' );
		} );
	};

	Tool.prototype.word = function ( blokken, bestandsnaam ) {
		var zelf = this;
		return laadWord().then( function () {
			var d = window.docx;
			/* Koppen en opschriften primair in hoofdletters, zoals op de pagina; de rest in de bodykleur. */
			function hex( k ) { return k.map( function ( x ) { return ( '0' + x.toString( 16 ) ).slice( -2 ); } ).join( '' ); }
			var primair = hex( kleur( '--sfp-b-primair', [ 20, 20, 60 ] ) );
			var body = hex( kleur( '--sfp-b-body', [ 87, 87, 87 ] ) );
			function kop( tekst, maat, voor ) { return new d.Paragraph( { spacing: { before: voor, after: 120 }, children: [ new d.TextRun( { text: tekst, bold: true, allCaps: true, size: maat, color: primair } ) ] } ); }
			function tekst( t, na ) { return new d.Paragraph( { spacing: { after: na }, children: [ new d.TextRun( { text: t, color: body } ) ] } ); }
			var kinderen = [];
			kinderen.push( kop( zelf.titel, 22, 0 ) );
			blokken.forEach( function ( bl ) {
				switch ( bl.type ) {
					case 'kicker': kinderen.push( new d.Paragraph( { spacing: { before: 240, after: 40 }, children: [ new d.TextRun( { text: bl.tekst, bold: true, allCaps: true, size: 16, color: primair } ) ] } ) ); break;
					case 'kop': kinderen.push( kop( bl.tekst, 36, 0 ) ); break;
					case 'subkop': kinderen.push( kop( bl.tekst, 24, 280 ) ); break;
					case 'tekst': alineas( bl.tekst ).forEach( function ( a ) { kinderen.push( tekst( a, 160 ) ); } ); break;
					case 'lijst': ( bl.items || [] ).forEach( function ( it, i ) { kinderen.push( new d.Paragraph( { spacing: { after: 80 }, children: [ new d.TextRun( { text: ( bl.nummers ? ( i + 1 ) + '. ' : '• ' ), bold: true, color: primair } ), new d.TextRun( { text: it, color: body } ) ] } ) ); } ); break;
					case 'afvink': ( bl.items || [] ).forEach( function ( it ) { kinderen.push( new d.Paragraph( { spacing: { after: 80 }, children: [ new d.TextRun( { text: '☐ ', color: primair } ), new d.TextRun( { text: it, color: body } ) ] } ) ); } ); break;
					case 'balk': kinderen.push( new d.Paragraph( { spacing: { after: 80 }, children: [ new d.TextRun( { text: bl.naam + ': ', bold: true, allCaps: true, color: primair } ), new d.TextRun( { text: bl.label, color: body } ) ] } ) ); break;
					case 'tabel': kinderen.push( new d.Table( { rows: ( bl.rijen || [] ).map( function ( rij ) { return new d.TableRow( { children: rij.map( function ( cel ) { return new d.TableCell( { children: [ new d.Paragraph( String( cel ) ) ] } ); } ) } ); } ) } ) ); break;
				}
			} );
			kinderen.push( new d.Paragraph( { children: [ new d.TextRun( { text: ( CONFIG.sitenaam || '' ) + ' · ' + ( CONFIG.siteurl || '' ), size: 16, color: body } ) ], spacing: { before: 400 } } ) );
			var document_ = new d.Document( { sections: [ { children: kinderen } ] } );
			return d.Packer.toBlob( document_ ).then( function ( blob ) {
				var a = document.createElement( 'a' );
				a.href = URL.createObjectURL( blob );
				a.download = bestandsnaam + '.docx';
				document.body.appendChild( a );
				a.click();
				setTimeout( function () { URL.revokeObjectURL( a.href ); document.body.removeChild( a ); }, 1000 );
			} );
		} );
	};

	/* Een veilige bestandsnaam uit de titel. */
	Tool.prototype.bestandsnaam = function () {
		var s = ( CONFIG.sitenaam ? CONFIG.sitenaam + ' - ' : '' ) + this.titel;
		return s.replace( /[\\\/:*?"<>|]+/g, '' ).replace( /\s+/g, ' ' ).trim().slice( 0, 80 );
	};

	/* ---------------------------------------------------------------- *
	 * Starten
	 * ---------------------------------------------------------------- */

	function start() {
		Array.prototype.forEach.call( document.querySelectorAll( '.sfp-tool[data-tool]' ), function ( w ) {
			if ( w.getAttribute( 'data-gestart' ) ) { return; }
			try {
				if ( new Tool( w ).start() ) { w.setAttribute( 'data-gestart', '1' ); }
			} catch ( e ) { if ( window.console ) { console.error( 'sfp-tool', e ); } }
		} );
	}

	window.sfpToolMotor = {
		soort: function ( naam, fabriek ) { SOORTEN[ naam ] = fabriek; if ( 'loading' !== document.readyState ) { start(); } },
		start: start,
		t: t,
		el: el,
		alineas: alineas,
		getal: getal,
		rond: rond,
		codeer: codeer,
		decodeer: decodeer,
		Tool: Tool
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
