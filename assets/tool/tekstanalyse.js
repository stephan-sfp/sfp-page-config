/**
 * SFP Page Config - Tools: soort tekstanalyse
 *
 * De bezoeker plakt een tekst; de motor deelt hem in zinnen en meet per zin
 * (woorden, lettergrepen, komma's, lijdende vorm, woorden uit een lijst). De
 * beschrijving kiest de metingen, rekent met de totalen in de rekentaal van
 * de generator en bepaalt wat er getoond wordt. De tekst blijft in de
 * browser: geen mail, geen deellink, niets bewaard.
 *
 * @since 3.10.0
 */
( function () {
	'use strict';
	var M = window.sfpToolMotor;
	if ( ! M || ! M.reken ) { return; }
	var t = M.t, el = M.el, R = M.reken;

	var WOORD = /\p{N}+(?:[.,]\p{N}+)*|[\p{L}\p{N}]+(?:['’\-][\p{L}\p{N}]+)*/gu;
	var VOLTOOID = /^(\p{L}{2,6}ge|ge|be|ver|ont|her|er)\p{L}{2,}(d|t|en)$/u;
	var WORDEN = [ 'word', 'wordt', 'worden', 'werd', 'werden' ];

	function woordenVan( s ) { return String( s ).match( WOORD ) || []; }

	/* Lettergrepen: groepen klinkers; een klinker met trema begint een nieuwe groep. */
	function lettergrepen( w ) {
		var m = String( w ).toLowerCase().match( /[äëïöü]|[aeiouyàáâèéêìíîòóôùúû]+/g );
		return Math.max( 1, m ? m.length : 0 );
	}

	/* Zinnen met hun plek in de tekst. Een punt na een afkorting of voor een kleine letter sluit geen zin af. */
	function zinnen( tekst, afkortingen ) {
		var afk = ( afkortingen || [] ).map( function ( a ) { return String( a ).toLowerCase(); } );
		var uit = [], begin = 0, re = /([.!?…]+["'”’)]*)(?=\s|$)|\n[ \t]*\n/g, m;
		while ( ( m = re.exec( tekst ) ) ) {
			var eind = m.index + m[ 0 ].length;
			if ( m[ 1 ] ) {
				var voor = tekst.slice( begin, m.index ).split( /\s+/ ).pop() + m[ 1 ].charAt( 0 );
				if ( '.' === m[ 1 ].charAt( 0 ) && afk.indexOf( voor.toLowerCase() ) >= 0 ) { continue; }
				var na = tekst.slice( eind ).match( /^\s*(\S)/ );
				if ( na && /\p{Ll}/u.test( na[ 1 ] ) ) { continue; }
			}
			voegZin( uit, tekst, begin, m[ 1 ] ? eind : m.index );
			begin = eind;
		}
		voegZin( uit, tekst, begin, tekst.length );
		return uit;
	}
	function voegZin( uit, tekst, van, tot ) {
		var deel = tekst.slice( van, tot );
		var links = deel.length - deel.replace( /^\s+/, '' ).length;
		var schoon = deel.trim();
		if ( schoon && /[\p{L}\p{N}]/u.test( schoon ) ) { uit.push( { van: van + links, tot: van + links + schoon.length, tekst: schoon } ); }
	}

	/* Plekken van woorden uit een lijst, langste eerst, zonder overlap. */
	function termen( zin, lijst ) {
		var gevonden = [];
		var laag = zin.toLowerCase();
		( lijst || [] ).slice().sort( function ( x, y ) { return y.length - x.length; } ).forEach( function ( term ) {
			var tl = String( term ).toLowerCase().trim();
			if ( ! tl ) { return; }
			var i = 0;
			while ( ( i = laag.indexOf( tl, i ) ) >= 0 ) {
				var e = i + tl.length;
				var grensVoor = 0 === i || ! /[\p{L}\p{N}]/u.test( laag.charAt( i - 1 ) );
				var grensNa = e >= laag.length || ! /[\p{L}\p{N}]/u.test( laag.charAt( e ) );
				var vrij = gevonden.every( function ( g ) { return e <= g[ 0 ] || i >= g[ 1 ]; } );
				if ( grensVoor && grensNa && vrij ) { gevonden.push( [ i, e ] ); }
				i = e;
			}
		} );
		return gevonden.sort( function ( x, y ) { return x[ 0 ] - y[ 0 ]; } );
	}

	function passief( woorden ) {
		var laag = woorden.map( function ( w ) { return w.toLowerCase(); } );
		for ( var i = 0; i < laag.length; i++ ) {
			if ( WORDEN.indexOf( laag[ i ] ) < 0 ) { continue; }
			for ( var k = i + 1; k < Math.min( laag.length, i + 9 ); k++ ) { if ( VOLTOOID.test( laag[ k ] ) && WORDEN.indexOf( laag[ k ] ) < 0 ) { return 1; } }
		}
		return 0;
	}

	/* Het splitspunt het dichtst bij het midden: na een komma of voor een voegwoord. */
	function splits( zin, cfg ) {
		var kandidaten = [];
		var re = /,/g, m;
		while ( ( m = re.exec( zin ) ) ) {
			if ( /\d/.test( zin.charAt( m.index - 1 ) ) && /\d/.test( zin.charAt( m.index + 1 ) ) ) { continue; }
			kandidaten.push( { links: zin.slice( 0, m.index ), rechts: zin.slice( m.index + 1 ), plek: m.index } );
		}
		( cfg.voegwoorden || [] ).forEach( function ( v ) {
			var vre = new RegExp( '\\s(' + String( v ).replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + ')(?=\\s)', 'giu' );
			while ( ( m = vre.exec( zin ) ) ) {
				if ( /^tot\s+en\s+met\b/i.test( zin.slice( m.index + 1 ) ) || /\btot\s*$/i.test( zin.slice( 0, m.index ) ) ) { continue; }
				kandidaten.push( { links: zin.slice( 0, m.index ), rechts: zin.slice( m.index + 1 ), plek: m.index } ); }
		} );
		var midden = zin.length / 2, beste = null;
		kandidaten.forEach( function ( c ) {
			c.links = c.links.replace( /[\s,;:]+$/, '' );
			c.rechts = c.rechts.replace( /^[\s,;:]+/, '' );
			if ( c.links.length < cfg.min_tekens || c.rechts.length < cfg.min_tekens ) { return; }
			if ( ! beste || Math.abs( c.plek - midden ) < Math.abs( beste.plek - midden ) ) { beste = c; }
		} );
		return beste;
	}

	function labelVoor( labels, n ) {
		for ( var i = 0; i < labels.length; i++ ) { if ( n >= labels[ i ].vanaf ) { return { label: labels[ i ], niveau: i }; } }
		return null;
	}

	/* De hele analyse: zinnen met metingen, totalen en rekenwaarden. */
	function analyseer( b, tekst ) {
		var lijsten = b.lijsten || {};
		var zl = zinnen( tekst, b.afkortingen ).map( function ( z, i ) {
			var ws = woordenVan( z.tekst );
			var x = { nr: i + 1, van: z.van, tot: z.tot, tekst: z.tekst, waarden: { woorden: ws.length, lettergrepen: 0, kommas: ( z.tekst.match( /,/g ) || [] ).length }, termen: {} };
			ws.forEach( function ( w ) { x.waarden.lettergrepen += lettergrepen( w ); } );
			( b.per_zin || [] ).forEach( function ( p ) {
				var n = 0;
				if ( 'woorden' === p.meet ) { n = ws.length; }
				else if ( 'lettergrepen' === p.meet ) { n = x.waarden.lettergrepen; }
				else if ( 'kommas' === p.meet ) { n = x.waarden.kommas; }
				else if ( 'passief' === p.meet ) { n = passief( ws ); }
				else if ( 'lange_woorden' === p.meet ) { n = ws.filter( function ( w ) { return w.length >= 8 && lettergrepen( w ) >= 4; } ).length; }
				else if ( 'lijst' === p.meet ) { x.termen[ p.id ] = termen( z.tekst, lijsten[ p.lijst ] ); n = x.termen[ p.id ].length; }
				x.waarden[ p.id ] = n;
			} );
			return x;
		} );
		var totaal = { zinnen: zl.length, woorden: 0, lettergrepen: 0, kommas: 0, tekens: tekst.replace( /\s/g, '' ).length };
		( b.per_zin || [] ).forEach( function ( p ) { totaal[ p.id ] = 0; totaal[ 'zinnen_met_' + p.id ] = 0; } );
		zl.forEach( function ( z ) {
			[ 'woorden', 'lettergrepen', 'kommas' ].forEach( function ( k ) { totaal[ k ] += z.waarden[ k ]; } );
			( b.per_zin || [] ).forEach( function ( p ) { if ( 'woorden' !== p.id && 'lettergrepen' !== p.id && 'kommas' !== p.id ) { totaal[ p.id ] += z.waarden[ p.id ]; } if ( z.waarden[ p.id ] > 0 ) { totaal[ 'zinnen_met_' + p.id ]++; } } );
		} );
		if ( b.klassen ) {
			b.klassen.labels.forEach( function ( l ) { if ( l.sleutel ) { totaal[ 'klasse_' + l.sleutel ] = 0; } } );
			zl.forEach( function ( z ) {
				var k = labelVoor( b.klassen.labels, R.getal( R.reken( b, b.klassen.op, z.waarden ) ) );
				z.klasse = k;
				if ( k && k.label.sleutel ) { totaal[ 'klasse_' + k.label.sleutel ]++; }
			} );
		}
		( b.reken || [] ).forEach( function ( r ) { totaal[ r.id ] = R.reken( b, r.formule, Object.assign( {}, totaal ) ); } );
		var oordeel = b.oordeel ? labelVoor( b.oordeel.labels, R.getal( R.reken( b, b.oordeel.op, totaal ) ) ) : null;
		totaal.oordeel = oordeel ? oordeel.label.sleutel : '';
		totaal.oordeel_tekst = oordeel ? oordeel.label.tekst : '';
		totaal.oordeel_uitleg = oordeel ? oordeel.label.uitleg : '';
		return { zinnen: zl, totaal: totaal };
	}

	M.soort( 'tekstanalyse', function ( tool ) {
		var b = tool.b, app = tool.app, inv = b.invoer || {};
		var acties = b.acties || {};
		function tx( k ) { return tool.tx.apply( tool, arguments ); }
		var id = 'sfp-tool-' + tool.id + '-tekst';
		var label = el( 'label', { class: 'sfp-tool__vraag', for: id, text: inv.label || '' } );
		var hint = inv.hint ? el( 'p', { class: 'sfp-tool__hint', text: inv.hint } ) : null;
		var veld = el( 'textarea', { class: 'sfp-tool__veld', id: id, rows: '9', maxlength: String( inv.max_tekens || 5000 ), placeholder: inv.voorbeeld || '', oninput: function () { teller.textContent = tx( 'tool_tekens', this.value.length, inv.max_tekens || 5000 ); } } );
		var teller = el( 'p', { class: 'sfp-tool__tekstvak-teller', 'aria-live': 'polite', text: tx( 'tool_tekens', 0, inv.max_tekens || 5000 ) } );
		var melding = el( 'p', { class: 'sfp-tool__melding is-fout', role: 'alert', hidden: true } );
		var knop = el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: tx( 'tool_analyseer' ), onclick: function () { start(); } } );
		var invoer = el( 'div', { class: 'sfp-tool__scherm' }, [ label, hint, veld, teller, melding, el( 'div', { class: 'sfp-tool__nav' }, [ knop ] ) ] );
		var resultaat = el( 'div', { class: 'sfp-tool__scherm sfp-tool__scherm--resultaat', hidden: true } );
		var laatste = null;

		function start() {
			var tekst = veld.value.trim();
			melding.hidden = true;
			if ( tekst.length < ( inv.min_tekens || 50 ) ) { melding.textContent = tx( 'tool_te_kort', inv.min_tekens || 50 ); melding.hidden = false; veld.focus(); return; }
			laatste = analyseer( b, tekst );
			laatste.tekst = tekst;
			toon( laatste );
		}

		function als( s, extra ) { return ! s.als || ! s.als.formule || R.waar( R.reken( b, s.als.formule, extra ) ); }
		function markeerZin( z, markeer ) {
			var span = el( 'span', { class: 'sfp-tool__zin' } );
			if ( z.klasse && b.klassen && z.klasse.niveau < b.klassen.labels.length - 1 && markeer.indexOf( 'klasse' ) >= 0 ) { span.classList.add( 'is-niveau-' + Math.min( 1, z.klasse.niveau ) ); }
			var stukken = [];
			( b.per_zin || [] ).forEach( function ( p ) {
				if ( markeer.indexOf( p.id ) < 0 ) { return; }
				if ( 'lijst' === p.meet ) { stukken = stukken.concat( z.termen[ p.id ] || [] ); }
				else if ( z.waarden[ p.id ] > 0 ) { span.classList.add( 'is-vlag' ); }
			} );
			stukken.sort( function ( x, y ) { return x[ 0 ] - y[ 0 ]; } );
			var pos = 0;
			stukken.forEach( function ( s ) {
				if ( s[ 0 ] < pos ) { return; }
				span.appendChild( document.createTextNode( z.tekst.slice( pos, s[ 0 ] ) ) );
				span.appendChild( el( 'mark', { class: 'sfp-tool__term', text: z.tekst.slice( s[ 0 ], s[ 1 ] ) } ) );
				pos = s[ 1 ];
			} );
			span.appendChild( document.createTextNode( z.tekst.slice( pos ) ) );
			return span;
		}
		function zinExtra( z, totaal ) {
			return Object.assign( {}, totaal, z.waarden, { nr: z.nr, klasse: z.klasse ? z.klasse.label.sleutel : '', klasse_tekst: z.klasse ? z.klasse.label.tekst : '' } );
		}
		function metaVan( s, z, totaal ) {
			return s.regel ? R.vul( b, s.regel, zinExtra( z, totaal ) ) : tx( 'tool_zin_nr', z.nr ) + ( z.klasse ? ' · ' + z.klasse.label.tekst : '' );
		}

		/* Blokken voor pdf en Word. */
		function blokken( an ) {
			var uit = [ { type: 'kop', tekst: b.resultaat_kop ? R.vul( b, b.resultaat_kop, an.totaal ) : tool.titel } ];
			( b.uitvoer || [] ).forEach( function ( s ) {
				if ( ! als( s, an.totaal ) ) { return; }
				if ( s.kop ) { uit.push( { type: 'subkop', tekst: R.vul( b, s.kop, an.totaal ) } ); }
				if ( 'getal' === s.type ) { uit.push( { type: 'tabel', rijen: [ [ R.vul( b, s.label || '', an.totaal ), R.formaat( b, R.reken( b, s.waarde, an.totaal ), s.formaat ) ] ] } ); if ( s.onder ) { uit.push( { type: 'tekst', tekst: R.vul( b, s.onder, an.totaal ) } ); } }
				else if ( 'tellers' === s.type ) { uit.push( { type: 'tabel', rijen: ( s.items || [] ).map( function ( it ) { return [ R.vul( b, it.label, an.totaal ), R.formaat( b, R.reken( b, it.waarde, an.totaal ), it.formaat ) ]; } ) } ); }
				else if ( 'tekst' === s.type ) { var tk = R.vul( b, s.tekst || '', an.totaal ).trim(); if ( tk ) { uit.push( { type: 'tekst', tekst: tk } ); } }
				else if ( 'gemarkeerd' === s.type ) { uit.push( { type: 'tekst', tekst: an.tekst } ); }
				else if ( 'zinnen' === s.type ) {
					an.zinnen.forEach( function ( z ) {
						var ex = zinExtra( z, an.totaal );
						if ( s.alleen && ! R.waar( R.reken( b, s.alleen, ex ) ) ) { return; }
						uit.push( { type: 'tabel', rijen: [ [ metaVan( s, z, an.totaal ), z.tekst ] ] } );
						var sp = s.suggestie && b.splitsen && z.waarden.woorden >= b.splitsen.vanaf ? splits( z.tekst, b.splitsen ) : null;
						if ( s.suggestie && b.splitsen && z.waarden.woorden >= b.splitsen.vanaf ) { uit.push( { type: 'tekst', tekst: sp ? tx( 'tool_splits_hier' ) + ': ' + sp.links + ' | ' + sp.rechts : tx( 'tool_geen_splits' ) } ); }
					} );
				}
			} );
			return uit;
		}

		function toon( an ) {
			resultaat.innerHTML = '';
			resultaat.hidden = false;
			var tot = an.totaal;
			resultaat.appendChild( el( 'h3', { class: 'sfp-tool__kop sfp-tool__kop--resultaat', tabindex: '-1', text: b.resultaat_kop ? R.vul( b, b.resultaat_kop, tot ) : tool.titel } ) );
			if ( b.resultaat_intro ) { M.alineas( R.vul( b, b.resultaat_intro, tot ) ).forEach( function ( x ) { resultaat.appendChild( el( 'p', { class: 'sfp-tool__tekst', text: x } ) ); } ); }
			( b.uitvoer || [] ).forEach( function ( s ) {
				if ( ! als( s, tot ) ) { return; }
				if ( s.kop ) { resultaat.appendChild( el( 'h4', { class: 'sfp-tool__titel sfp-tool__titel--klein', text: R.vul( b, s.kop, tot ) } ) ); }
				if ( 'getal' === s.type ) {
					resultaat.appendChild( el( 'div', { class: 'sfp-tool__getal' }, [ s.label ? el( 'p', { class: 'sfp-tool__getal-label', text: R.vul( b, s.label, tot ) } ) : null, el( 'p', { class: 'sfp-tool__getal-waarde', text: R.formaat( b, R.reken( b, s.waarde, tot ), s.formaat ) } ), s.onder ? el( 'p', { class: 'sfp-tool__getal-onder', text: R.vul( b, s.onder, tot ) } ) : null ] ) );
				} else if ( 'tellers' === s.type ) {
					resultaat.appendChild( el( 'div', { class: 'sfp-tool__tellers' }, ( s.items || [] ).map( function ( it ) {
						return el( 'div', { class: 'sfp-tool__teller' }, [ el( 'span', { class: 'sfp-tool__teller-waarde', text: R.formaat( b, R.reken( b, it.waarde, tot ), it.formaat ) } ), el( 'span', { class: 'sfp-tool__teller-label', text: R.vul( b, it.label, tot ) } ) ] );
					} ) ) );
				} else if ( 'tekst' === s.type ) {
					M.alineas( R.vul( b, s.tekst || '', tot ) ).forEach( function ( x ) { resultaat.appendChild( el( 'p', { class: 'sfp-tool__tekst', text: x } ) ); } );
				} else if ( 'gemarkeerd' === s.type ) {
					var markeer = s.markeer || [];
					var vak = el( 'div', { class: 'sfp-tool__gemarkeerd' } );
					var pos = 0;
					an.zinnen.forEach( function ( z ) { vak.appendChild( document.createTextNode( an.tekst.slice( pos, z.van ) ) ); vak.appendChild( markeerZin( z, markeer ) ); pos = z.tot; } );
					vak.appendChild( document.createTextNode( an.tekst.slice( pos ) ) );
					resultaat.appendChild( vak );
					if ( s.legenda ) {
						var leg = el( 'p', { class: 'sfp-tool__legenda' } );
						if ( b.klassen && markeer.indexOf( 'klasse' ) >= 0 ) { b.klassen.labels.slice( 0, -1 ).forEach( function ( l, i ) { leg.appendChild( el( 'span', null, [ el( 'span', { class: 'sfp-tool__zin is-niveau-' + Math.min( 1, i ), text: 'abc' } ), l.tekst ] ) ); } ); }
						( b.per_zin || [] ).forEach( function ( p ) { if ( markeer.indexOf( p.id ) >= 0 && p.label ) { leg.appendChild( el( 'span', null, [ 'lijst' === p.meet ? el( 'mark', { class: 'sfp-tool__term', text: 'abc' } ) : el( 'span', { class: 'sfp-tool__zin is-vlag', text: 'abc' } ), p.label ] ) ); } } );
						resultaat.appendChild( leg );
					}
				} else if ( 'zinnen' === s.type ) {
					var ol = el( 'ol', { class: 'sfp-tool__zinnen' } );
					an.zinnen.forEach( function ( z ) {
						var ex = zinExtra( z, tot );
						if ( s.alleen && ! R.waar( R.reken( b, s.alleen, ex ) ) ) { return; }
						var li = el( 'li', null, [ el( 'p', { class: 'sfp-tool__zin-meta', text: metaVan( s, z, tot ) } ), el( 'p', { class: 'sfp-tool__zin-tekst' }, [ markeerZin( z, s.markeer || [ 'klasse' ] ) ] ) ] );
						if ( s.suggestie && b.splitsen && z.waarden.woorden >= b.splitsen.vanaf ) {
							var sp = splits( z.tekst, b.splitsen );
							li.appendChild( sp ? el( 'p', { class: 'sfp-tool__zin-suggestie' }, [ tx( 'tool_splits_hier' ) + ': ', sp.links, ' ', el( 'mark', { text: '|' } ), ' ', sp.rechts ] ) : el( 'p', { class: 'sfp-tool__zin-suggestie', text: tx( 'tool_geen_splits' ) } ) );
						}
						ol.appendChild( li );
					} );
					if ( ol.children.length ) { resultaat.appendChild( ol ); }
				}
			} );
			var rij = el( 'div', { class: 'sfp-tool__acties sfp-tool__acties--uitslag' } );
			var links = el( 'div', { class: 'sfp-tool__acties-links' } );
			var dm = el( 'p', { class: 'sfp-tool__melding sfp-tool__melding--klein', role: 'status', hidden: true } );
			function bestand( k, p ) { var oud = k.textContent; k.disabled = true; k.textContent = tx( 'tool_bezig' ) + '…'; p.then( function () { k.disabled = false; k.textContent = oud; }, function () { k.disabled = false; k.textContent = oud; dm.textContent = tx( 'tool_bestand_fout' ); dm.hidden = false; } ); }
			var naam = acties.pdf_bestandsnaam ? R.vul( b, acties.pdf_bestandsnaam, tot ).replace( /[\\\/:*?"<>|]+/g, '' ).trim().slice( 0, 80 ) || tool.bestandsnaam() : tool.bestandsnaam();
			if ( acties.pdf !== false ) { rij.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: tx( 'tool_pdf' ), onclick: function () { bestand( this, tool.pdf( blokken( an ), naam ) ); } } ) ); }
			if ( acties.word !== false ) { links.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink', text: tx( 'tool_word' ), onclick: function () { bestand( this, tool.word( blokken( an ), naam ) ); } } ) ); }
			if ( acties.kopieer ) { links.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink', text: tx( 'tool_kopieer' ), onclick: function () { var tk = R.platteTekst( tool.titel, blokken( an ).slice( 1 ), {}, false ); if ( navigator.clipboard ) { navigator.clipboard.writeText( tk ).then( function () { dm.textContent = tx( 'tool_gekopieerd' ); dm.hidden = false; } ); } } } ) ); }
			rij.appendChild( links );
			resultaat.appendChild( rij );
			resultaat.appendChild( dm );
			resultaat.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink sfp-tool__opnieuw', text: tx( 'tool_opnieuw' ), onclick: function () { veld.value = ''; teller.textContent = tx( 'tool_tekens', 0, inv.max_tekens || 5000 ); resultaat.hidden = true; resultaat.innerHTML = ''; veld.focus(); } } ) );
			if ( acties.duimpjes !== false ) { resultaat.appendChild( tool.duimWidget() ); }
			if ( tool.formulier ) { tool.formulier.hidden = false; }
			var kop = resultaat.querySelector( '.sfp-tool__kop' );
			if ( kop ) { try { kop.focus( { preventScroll: true } ); } catch ( e ) {} }
			if ( resultaat.getBoundingClientRect().top < 0 || resultaat.getBoundingClientRect().top > window.innerHeight ) { resultaat.scrollIntoView( { behavior: 'smooth', block: 'start' } ); }
		}

		return {
			start: function () {
				app.appendChild( el( 'div', { class: 'sfp-tool__schermen' }, [ invoer, resultaat ] ) );
			},
			analyseer: function ( tekst ) { return analyseer( b, tekst ); }
		};
	} );

	M.tekstanalyse = { analyseer: analyseer, zinnen: zinnen, lettergrepen: lettergrepen, splits: splits, passief: function ( s ) { return passief( woordenVan( s ) ); } };
} )();
