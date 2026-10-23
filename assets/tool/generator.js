/**
 * SFP Page Config - Tools: soort generator
 *
 * Stappen met velden (keuze, keuzelijst, vinkjes, kort tekstveld, tekstvak,
 * regels, datum, getal); daarna bouwt de motor het resultaat uit vaste
 * secties, banken en sjablonen met plaatshouders. Geen willekeur: dezelfde
 * antwoorden geven altijd hetzelfde resultaat. Het bouwen is gelijk aan
 * includes/generator.php (sfp_page_config_generator_bouw); de server bouwt
 * de mail zo opnieuw uit de antwoorden.
 *
 * @since 3.9.0
 */
( function () {
	'use strict';
	var M = window.sfpToolMotor;
	if ( ! M ) { return; }
	var t = M.t, el = M.el, alineas = M.alineas;

	/* ---------------------------------------------------------------- *
	 * Bouwen (gelijk aan PHP)
	 * ---------------------------------------------------------------- */

	function heeft( o, k ) { return o !== null && 'object' === typeof o && Object.prototype.hasOwnProperty.call( o, k ); }
	function alsTekst( w ) { return ( 'string' === typeof w ) ? w : ( ( 'number' === typeof w && isFinite( w ) ) ? String( w ) : '' ); }
	function isLeeg( w ) { return w === null || w === undefined || ( Array.isArray( w ) ? ! w.length : '' === alsTekst( w ).trim() ); }
	function eersteLetter( s, klein ) { var c = Array.from( s ); for ( var i = 0; i < c.length; i++ ) { if ( c[ i ].toUpperCase() !== c[ i ].toLowerCase() ) { c[ i ] = klein ? c[ i ].toLowerCase() : c[ i ].toUpperCase(); break; } } return c.join( '' ); }

	/* Alle velden op id. */
	function velden( b ) {
		if ( b._velden ) { return b._velden; }
		var uit = {};
		( b.stappen || [] ).forEach( function ( s ) { ( s.velden || [] ).forEach( function ( v ) { uit[ v.id ] = v; } ); } );
		Object.defineProperty( b, '_velden', { value: uit, enumerable: false } );
		return uit;
	}
	function optie( v, w ) {
		var gevonden = null;
		( v && v.opties ? v.opties : [] ).forEach( function ( o ) { if ( null === gevonden && String( o.waarde ) === String( w ) ) { gevonden = o; } } );
		return gevonden;
	}

	/* Een antwoord zoals de motor het gebruikt: tekst, of bij vinkjes een lijst van bekende waarden in de volgorde van de opties. */
	function antwoordVan( b, a, id ) {
		var v = velden( b )[ id ];
		var w = heeft( a, id ) ? a[ id ] : undefined;
		if ( ! v ) { return ''; }
		if ( 'vinkjes' === v.type ) {
			var lijst = Array.isArray( w ) ? w.map( alsTekst ) : [];
			return ( v.opties || [] ).filter( function ( o ) { return lijst.indexOf( String( o.waarde ) ) >= 0; } ).map( function ( o ) { return String( o.waarde ); } );
		}
		w = alsTekst( w );
		if ( 'keuze' === v.type || 'lijst' === v.type ) { return optie( v, w ) ? w : ''; }
		if ( 'datum' === v.type ) { return /^\d{4}-\d{2}-\d{2}$/.test( w ) ? w : ''; }
		if ( 'getal' === v.type ) { return /^-?\d+(\.\d+)?$/.test( w.trim() ) ? w.trim() : ''; }
		var max = v.maxlengte || ( 'kort' === v.type ? 160 : 2000 );
		if ( 'kort' === v.type ) { w = w.trim(); }
		return Array.from( w ).slice( 0, max ).join( '' );
	}

	/* De waarde achter een naam: een veld, een afgeleide waarde of het huidige item (@). */
	function lees( b, a, naam, ctx ) {
		if ( '@' === naam.charAt( 0 ) ) {
			if ( ! ctx || ! ctx.item ) { return ''; }
			var deel = naam.slice( 1 );
			if ( '' === deel ) { return ctx.item.waarde; }
			if ( 'label' === deel ) { return ctx.item.optie ? ctx.item.optie.tekst || '' : ctx.item.waarde; }
			if ( 'uitleg' === deel ) { return ctx.item.optie ? ctx.item.optie.uitleg || '' : ''; }
			if ( 'nr' === deel ) { return String( ctx.item.nr ); }
			return '';
		}
		if ( velden( b )[ naam ] ) { return antwoordVan( b, a, naam ); }
		if ( heeft( b.waarden, naam ) ) {
			var diepte = ctx && ctx.diepte ? ctx.diepte : 0;
			if ( diepte > 4 ) { return ''; }
			return vul( b.waarden[ naam ], b, a, { item: ctx ? ctx.item : null, diepte: diepte + 1 } );
		}
		return '';
	}

	function lange_datum( w ) {
		var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec( w );
		if ( ! m ) { return w; }
		var maanden = String( t( 'tool_maanden' ) ).split( ',' );
		var maand = maanden[ parseInt( m[ 2 ], 10 ) - 1 ];
		return parseInt( m[ 3 ], 10 ) + ' ' + ( maand ? maand.trim() : m[ 2 ] ) + ' ' + m[ 1 ];
	}

	function filter( b, a, w, naam, f, ctx ) {
		var dp = f.indexOf( ':' );
		var soort = dp >= 0 ? f.slice( 0, dp ) : f;
		var arg = dp >= 0 ? f.slice( dp + 1 ) : '';
		var v = velden( b )[ naam ];
		switch ( soort ) {
			case 'label':
				if ( Array.isArray( w ) ) { return w.map( function ( x ) { var o = optie( v, x ); return o ? o.tekst : x; } ); }
				var o = optie( v, w );
				return o ? o.tekst : w;
			case 'samen': return Array.isArray( w ) ? w.join( arg ) : w;
			case 'bank':
				var bank = heeft( b.banken, arg ) ? b.banken[ arg ] : null;
				var zoek = function ( x ) { return bank && ! Array.isArray( bank ) && heeft( bank, x ) && 'string' === typeof bank[ x ] ? vul( bank[ x ], b, a, ctx ) : ''; };
				return Array.isArray( w ) ? w.map( zoek ) : zoek( w );
		}
		if ( Array.isArray( w ) ) { w = w.join( ', ' ); }
		w = String( w );
		switch ( soort ) {
			case 'hoofdletter': return eersteLetter( w );
			case 'klein': return w.toLowerCase();
			case 'kleinbegin': return eersteLetter( w, true );
			case 'slug': return w.normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' ).toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' );
			case 'zin': w = w.trim(); return ( '' === w || /[.!?…:]$/.test( w ) ) ? w : w + '.';
			case 'eerstezin': var mz = /^[\s\S]*?[.!?](?=\s|$)/.exec( w.trim() ); return mz ? mz[ 0 ] : w.trim();
			case 'eersteregel': return w.trim().split( /\r?\n/ )[ 0 ].trim();
			case 'kort':
				var n = parseInt( arg, 10 ) || 60;
				var tekens = Array.from( w.trim() );
				if ( tekens.length <= n ) { return tekens.join( '' ); }
				var knip = tekens.slice( 0, n ).join( '' );
				var spatie = knip.lastIndexOf( ' ' );
				return ( spatie > n / 2 ? knip.slice( 0, spatie ) : knip ).replace( /[\s,;:.]+$/, '' ) + '…';
			case 'datum': return lange_datum( w );
			case 'anders': return '' === w.trim() ? vul( arg, b, a, ctx ) : w;
			case 'voor': return '' === w.trim() ? w : arg + w;
			case 'na': return '' === w.trim() ? w : w + arg;
			case 'of': if ( '' !== w.trim() ) { return w; } var ander = lees( b, a, arg, ctx ); return Array.isArray( ander ) ? ander.join( ', ' ) : String( ander );
		}
		return w;
	}

	/* Plaatshouders {naam|filter|filter:arg} in een sjabloon. */
	function vul( sjabloon, b, a, ctx ) {
		return String( sjabloon || '' ).replace( /\{(@?[a-zA-Z0-9_]*)((?:\|[^{}|]+)*)\}/g, function ( m, naam, filters ) {
			if ( '' === naam ) { return m; }
			var w = lees( b, a, naam, ctx );
			if ( filters ) { filters.slice( 1 ).split( '|' ).forEach( function ( f ) { w = filter( b, a, w, naam, f, ctx ); } ); }
			if ( Array.isArray( w ) ) { w = w.join( ', ' ); }
			return String( w );
		} );
	}

	/* Een voorwaarde: { veld, is | niet | in | gevuld | bevat }, een lijst (alle) of { of: [...] }. */
	function klopt( b, a, als, ctx ) {
		if ( ! als ) { return true; }
		if ( Array.isArray( als ) ) { return als.every( function ( x ) { return klopt( b, a, x, ctx ); } ); }
		if ( 'object' !== typeof als ) { return true; }
		if ( Array.isArray( als.of ) ) { return als.of.some( function ( x ) { return klopt( b, a, x, ctx ); } ); }
		var w = lees( b, a, String( als.veld || '' ), ctx );
		var bevat = function ( x ) { x = String( x ); return Array.isArray( w ) ? w.indexOf( x ) >= 0 : alsTekst( w ) === x; };
		if ( heeft( als, 'is' ) ) { return bevat( als.is ); }
		if ( heeft( als, 'niet' ) ) { return ! bevat( als.niet ); }
		if ( Array.isArray( als['in'] ) ) { return als['in'].some( bevat ); }
		if ( heeft( als, 'gevuld' ) ) { return !! als.gevuld === ! isLeeg( w ); }
		if ( heeft( als, 'bevat' ) ) { return Array.isArray( w ) ? bevat( als.bevat ) : alsTekst( w ).indexOf( String( als.bevat ) ) >= 0; }
		return true;
	}

	/* De items van een lijst: vaste sjablonen, een bank op een pad of de regels van een tekstvak. */
	function items( b, a, s, ctx ) {
		var uit = [];
		var elk = function ( w ) { if ( ! s.elk ) { return w; } String( s.elk ).split( '|' ).forEach( function ( f ) { w = String( filter( b, a, w, '', f, ctx ) ); } ); return w; };
		var voeg = function ( x ) {
			if ( x && 'object' === typeof x && ! Array.isArray( x ) ) {
				if ( ! klopt( b, a, x.als, ctx ) ) { return; }
				x = x.tekst;
			}
			if ( 'string' !== typeof x ) { return; }
			var w = vul( x, b, a, ctx ).trim();
			if ( '' !== w ) { uit.push( elk( w ) ); }
		};
		if ( Array.isArray( s.items ) ) { s.items.forEach( voeg ); }
		if ( s.bank ) {
			var knoop = heeft( b.banken, s.bank ) ? b.banken[ s.bank ] : null;
			( s.pad || [] ).forEach( function ( p ) { var k = vul( p, b, a, ctx ); knoop = heeft( knoop, k ) && ! Array.isArray( knoop ) ? knoop[ k ] : null; } );
			if ( Array.isArray( knoop ) ) { knoop.forEach( voeg ); } else if ( 'string' === typeof knoop ) { voeg( knoop ); }
		}
		if ( s.regels ) {
			alsTekst( lees( b, a, s.regels, ctx ) ).split( /\r?\n/ ).forEach( function ( r ) {
				r = r.trim().replace( /^[-*•]\s*/, '' );
				if ( '' !== r ) { uit.push( ( s.voor ? vul( s.voor, b, a, ctx ) : '' ) + elk( r ) ); }
			} );
		}
		return uit;
	}

	/* Het resultaat als blokken: { type, tekst | items | rijen, nummers, sleutel, lijnen }. */
	function bouw( b, a ) {
		var blokken = [];
		function sectie( s, ctx, pad ) {
			if ( ! s || ! klopt( b, a, s.als, ctx ) ) { return; }
			var kop = s.kop ? vul( s.kop, b, a, ctx ).trim() : '';
			var type = s.type || 'tekst';
			if ( 'herhaal' === type ) {
				var v = velden( b )[ s.over ];
				var gekozen = v ? antwoordVan( b, a, s.over ) : [];
				if ( ! Array.isArray( gekozen ) ) { gekozen = isLeeg( gekozen ) ? [] : [ gekozen ]; }
				gekozen.forEach( function ( w, i ) {
					var c = { item: { waarde: w, optie: optie( v, w ), nr: i + 1 } };
					var ik = s.kop ? vul( s.kop, b, a, c ).trim() : '';
					if ( ik ) { blokken.push( { type: 'subkop', tekst: ik } ); }
					( s.inhoud || [] ).forEach( function ( x, j ) { sectie( x, c, pad + '.' + w + '.' + j ); } );
				} );
				return;
			}
			if ( 'tekst' === type ) {
				var tekst = vul( s.tekst, b, a, ctx ).trim();
				if ( '' === tekst && ! s.altijd ) { return; }
				if ( kop ) { blokken.push( { type: 'subkop', tekst: kop } ); }
				if ( '' !== tekst ) { blokken.push( { type: 'tekst', tekst: tekst } ); }
				return;
			}
			if ( 'gegevens' === type ) {
				var rijen = [];
				( s.regels || [] ).forEach( function ( r ) {
					if ( ! r || ! klopt( b, a, r.als, ctx ) ) { return; }
					var w = vul( r.tekst, b, a, ctx ).trim();
					if ( '' !== w ) { rijen.push( [ vul( r.label, b, a, ctx ).trim(), w ] ); }
				} );
				if ( ! rijen.length ) { return; }
				if ( kop ) { blokken.push( { type: 'subkop', tekst: kop } ); }
				blokken.push( { type: 'tabel', rijen: rijen } );
				return;
			}
			var lijst = items( b, a, s, ctx );
			if ( ! lijst.length ) {
				if ( s.leeg ) { if ( kop ) { blokken.push( { type: 'subkop', tekst: kop } ); } blokken.push( { type: 'tekst', tekst: vul( s.leeg, b, a, ctx ) } ); }
				return;
			}
			if ( kop ) { blokken.push( { type: 'subkop', tekst: kop } ); }
			if ( 'afvink' === type ) { blokken.push( { type: 'afvink', items: lijst } ); return; }
			if ( 'vragen' === type ) { blokken.push( { type: 'invul', items: lijst, nummers: s.nummers !== false, sleutel: pad, lijnen: s.lijnen === undefined ? 3 : Math.max( 0, Math.min( 12, parseInt( s.lijnen, 10 ) || 0 ) ) } ); return; }
			if ( 'alineas' === type ) { lijst.forEach( function ( x ) { blokken.push( { type: 'tekst', tekst: x } ); } ); return; }
			blokken.push( { type: 'lijst', items: lijst, nummers: !! s.nummers } );
		}
		( b.uitvoer || [] ).forEach( function ( s, i ) { sectie( s, null, String( i ) ); } );
		return blokken;
	}

	/* Wat er in een stap nog ontbreekt: ids van velden. */
	function zichtbaar( b, a, v ) { return klopt( b, a, v.toon_als, null ); }
	function verplicht( b, a, v ) { return zichtbaar( b, a, v ) && ( true === v.verplicht || ( v.verplicht && 'object' === typeof v.verplicht && klopt( b, a, v.verplicht, null ) ) ); }
	function ontbreekt( b, a, stap ) {
		var uit = [];
		( stap.velden || [] ).forEach( function ( v ) {
			var w = antwoordVan( b, a, v.id );
			if ( 'vinkjes' === v.type && zichtbaar( b, a, v ) && ( v.min || 0 ) > w.length ) { uit.push( v.id ); return; }
			if ( 'regels' === v.type && zichtbaar( b, a, v ) && ( v.min || 0 ) > w.split( /\r?\n/ ).filter( function ( r ) { return '' !== r.trim(); } ).length ) { uit.push( v.id ); return; }
			if ( verplicht( b, a, v ) && isLeeg( w ) ) { uit.push( v.id ); }
		} );
		return uit;
	}

	/* Platte tekst van de blokken, voor kopieren. */
	function platteTekst( titel, blokken, antw, kaal ) {
		var r = kaal ? [] : [ titel, '' ];
		blokken.forEach( function ( bl ) {
			if ( 'subkop' === bl.type ) { r.push( '', bl.tekst.toUpperCase() ); }
			else if ( 'tekst' === bl.type ) { r.push( bl.tekst, '' ); }
			else if ( 'tabel' === bl.type ) { bl.rijen.forEach( function ( x ) { r.push( x[ 0 ] + ': ' + x[ 1 ] ); } ); r.push( '' ); }
			else {
				bl.items.forEach( function ( it, i ) {
					r.push( ( 'afvink' === bl.type ? '[ ] ' : ( bl.nummers ? ( i + 1 ) + '. ' : '- ' ) ) + it );
					var aw = 'invul' === bl.type && antw ? antw[ bl.sleutel + '.' + i ] : '';
					if ( aw ) { r.push( '   ' + aw ); }
				} );
				r.push( '' );
			}
		} );
		return r.join( '\n' ).replace( /\n{3,}/g, '\n\n' ).trim() + '\n';
	}

	window.sfpToolMotor.bouwGenerator = bouw;
	window.sfpToolMotor.generatorOntbreekt = ontbreekt;

	/* ---------------------------------------------------------------- *
	 * In de browser
	 * ---------------------------------------------------------------- */

	M.soort( 'generator', function ( tool ) {
		var b = tool.b;
		var app = tool.app;
		var stappen = b.stappen || [];
		var eigen = b.teksten || {};
		function tx( sleutel ) { var x = Array.prototype.slice.call( arguments, 1 ); return eigen[ sleutel ] ? t.apply( null, [ eigen[ sleutel ] ].concat( x ) ) : t.apply( null, [ sleutel ].concat( x ) ); }
		var antwoorden = {};
		var invul = {};
		var bewerkt = null;
		var huidig = 0;
		var acties = b.acties || {};
		var aan = function ( k ) { return acties[ k ] === undefined ? true : !! acties[ k ]; };

		var namen = stappen.map( function ( s ) { return s.titel || ''; } ).concat( [ tx( 'tool_resultaat' ) ] );
		var balk = el( 'ol', { class: 'sfp-tool__stappen', role: 'tablist' } );
		var stapItems = namen.map( function ( naam, i ) {
			var li = el( 'li', { class: 'sfp-tool__stap' + ( 0 === i ? ' is-actief' : '' ) + ( i === namen.length - 1 ? ' is-slot' : '' ), role: 'tab', 'aria-selected': 0 === i ? 'true' : 'false', tabindex: 0 === i ? '0' : '-1', onclick: function () { naar( i ); }, onkeydown: function ( e ) { if ( 'Enter' === e.key || ' ' === e.key ) { e.preventDefault(); naar( i ); } } }, [ naam ] );
			balk.appendChild( li );
			return li;
		} );
		var melding = el( 'p', { class: 'sfp-tool__melding', role: 'alert', hidden: true } );
		var schermenWrap = el( 'div', { class: 'sfp-tool__schermen' } );
		var resultaat = el( 'div', { class: 'sfp-tool__scherm sfp-tool__scherm--resultaat', hidden: true } );
		var invoer = {};

		function toonMelding( tekst ) { melding.textContent = tekst; melding.hidden = ! tekst; melding.classList.toggle( 'is-fout', !! tekst ); }
		function bewaar() { tool.bewaar( { a: antwoorden, i: huidig } ); }
		function zet( id, w ) { antwoorden[ id ] = w; bewerkt = null; bewaar(); werkZichtbaar(); }

		function maakVeld( v, stapIndex ) {
			var id = 'sfp-tool-' + tool.id + '-g-' + v.id;
			var labelTekst = v.label || '';
			var labelEl = el( 'p', { class: 'sfp-tool__vraag', id: id + '-l', text: labelTekst } );
			var hint = v.hint ? el( 'p', { class: 'sfp-tool__hint', text: '' } ) : null;
			var veld;
			if ( 'keuze' === v.type ) {
				veld = el( 'div', { class: 'sfp-tool__keuzes', role: 'radiogroup', 'aria-labelledby': id + '-l' } );
				( v.opties || [] ).forEach( function ( o ) {
					var inp = el( 'input', { type: 'radio', class: 'sfp-tool__radio', name: id, value: String( o.waarde ), onchange: function () {
						zet( v.id, String( o.waarde ) );
						if ( v.verder && 1 === stappen[ stapIndex ].velden.filter( function ( x ) { return zichtbaar( b, antwoorden, x ); } ).length ) { setTimeout( function () { volgende(); }, 220 ); }
					} } );
					veld.appendChild( el( 'label', { class: 'sfp-tool__optie' }, [ inp, el( 'span', { class: 'sfp-tool__optie-tekst' }, [ o.tekst || '', o.uitleg ? el( 'small', { class: 'sfp-tool__optie-uitleg', text: o.uitleg } ) : null ] ) ] ) );
				} );
			} else if ( 'vinkjes' === v.type ) {
				veld = el( 'div', { class: 'sfp-tool__keuzes sfp-tool__keuzes--vinkjes', role: 'group', 'aria-labelledby': id + '-l' } );
				( v.opties || [] ).forEach( function ( o ) {
					var inp = el( 'input', { type: 'checkbox', class: 'sfp-tool__radio', value: String( o.waarde ), onchange: function () {
						var nu = Array.prototype.filter.call( veld.querySelectorAll( 'input' ), function ( x ) { return x.checked; } ).map( function ( x ) { return x.value; } );
						zet( v.id, nu );
					} } );
					veld.appendChild( el( 'label', { class: 'sfp-tool__optie' }, [ inp, el( 'span', { class: 'sfp-tool__optie-tekst' }, [ o.tekst || '', o.uitleg ? el( 'small', { class: 'sfp-tool__optie-uitleg', text: o.uitleg } ) : null ] ) ] ) );
				} );
			} else if ( 'lijst' === v.type ) {
				veld = el( 'select', { class: 'sfp-tool__veld', id: id, 'aria-labelledby': id + '-l', onchange: function () { zet( v.id, this.value ); } }, [ el( 'option', { value: '', text: tx( 'tool_kies' ) } ) ].concat( ( v.opties || [] ).map( function ( o ) { return el( 'option', { value: String( o.waarde ), text: o.tekst || '' } ); } ) ) );
			} else if ( 'tekstvak' === v.type || 'regels' === v.type ) {
				veld = el( 'textarea', { class: 'sfp-tool__veld', id: id, rows: 'regels' === v.type ? '5' : '4', maxlength: String( v.maxlengte || 2000 ), placeholder: v.voorbeeld || '', 'aria-labelledby': id + '-l', oninput: function () { zet( v.id, this.value ); } } );
			} else {
				var soort = 'datum' === v.type ? 'date' : ( 'getal' === v.type ? 'number' : 'text' );
				veld = el( 'input', { type: soort, class: 'sfp-tool__veld', id: id, maxlength: 'kort' === v.type ? String( v.maxlengte || 160 ) : null, placeholder: v.voorbeeld || '', 'aria-labelledby': id + '-l', oninput: function () { zet( v.id, this.value ); } } );
			}
			var wrap = el( 'div', { class: 'sfp-tool__veldgroep', 'data-veld': v.id }, [ labelEl, hint, veld ] );
			invoer[ v.id ] = { wrap: wrap, veld: veld, label: labelEl, hint: hint, v: v };
			return wrap;
		}

		var intros = [];
		var schermen = stappen.map( function ( s, i ) {
			var scherm = el( 'div', { class: 'sfp-tool__scherm', hidden: true } );
			if ( s.intro ) { var intro = el( 'div', { class: 'sfp-tool__intro-tekst' } ); intro.setAttribute( 'data-stap', String( i ) ); scherm.appendChild( intro ); intros.push( { el: intro, tekst: s.intro } ); }
			( s.velden || [] ).forEach( function ( v ) { scherm.appendChild( maakVeld( v, i ) ); } );
			scherm.appendChild( el( 'div', { class: 'sfp-tool__nav' }, [ el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: i === stappen.length - 1 ? tx( 'tool_maak' ) : tx( 'tool_volgende' ), onclick: function () { volgende(); } } ) ] ) );
			schermenWrap.appendChild( scherm );
			return scherm;
		} );
		schermenWrap.appendChild( resultaat );
		var vorige = el( 'button', { type: 'button', class: 'sfp-tool__vorige', hidden: true, text: '‹ ' + tx( 'tool_vorige' ), onclick: function () { naar( huidig - 1 ); } } );

		/* Labels die van een ander veld afhangen, en velden die alleen onder een voorwaarde verschijnen. */
		function werkZichtbaar() {
			intros.forEach( function ( x ) { x.el.innerHTML = ''; alineas( vul( x.tekst, b, antwoorden ) ).forEach( function ( a ) { x.el.appendChild( el( 'p', { text: a } ) ); } ); } );
			Object.keys( invoer ).forEach( function ( id ) {
				var x = invoer[ id ];
				x.wrap.hidden = ! zichtbaar( b, antwoorden, x.v );
				var lab = x.v.label || '';
				( x.v.label_als || [] ).some( function ( r ) { if ( klopt( b, antwoorden, r.als, null ) ) { lab = r.tekst; return true; } return false; } );
				if ( x.hint ) { x.hint.textContent = vul( x.v.hint, b, antwoorden ); }
				if ( x.v.voorbeeld && x.veld.setAttribute && 'DIV' !== x.veld.tagName && 'SELECT' !== x.veld.tagName ) { x.veld.setAttribute( 'placeholder', vul( x.v.voorbeeld, b, antwoorden ) ); }
				x.label.textContent = vul( lab, b, antwoorden ) + ( verplicht( b, antwoorden, x.v ) ? '' : ( x.v.type !== 'keuze' && x.v.type !== 'vinkjes' ? ' ' + tx( 'tool_optioneel' ) : '' ) );
			} );
		}

		function naar( i ) {
			if ( i < 0 ) { return; }
			/* Vooruit alleen langs stappen die compleet zijn; anders naar de eerste stap met een open veld. */
			for ( var k = 0; k < Math.min( i, stappen.length ); k++ ) {
				if ( ontbreekt( b, antwoorden, stappen[ k ] ).length ) { toon( k ); toonOntbreekt( k ); return; }
			}
			toon( i );
		}
		function toon( i ) {
			huidig = i;
			toonMelding( '' );
			schermen.forEach( function ( s, k ) { s.hidden = k !== i; } );
			resultaat.hidden = i !== stappen.length;
			vorige.hidden = 0 === i;
			stapItems.forEach( function ( li, k ) { li.classList.toggle( 'is-actief', k === i ); li.setAttribute( 'aria-selected', k === i ? 'true' : 'false' ); li.tabIndex = k === i ? 0 : -1; } );
			if ( i === stappen.length ) { toonResultaat(); }
			bewaar();
			var focus = i === stappen.length ? resultaat.querySelector( '.sfp-tool__kop' ) : schermen[ i ].querySelector( 'input, select, textarea' );
			if ( focus && tool.gestart ) { try { focus.focus( { preventScroll: true } ); } catch ( e ) {} }
			if ( tool.gestart && app.getBoundingClientRect().top < 0 ) { app.scrollIntoView( { behavior: 'smooth', block: 'start' } ); }
			tool.gestart = true;
		}
		function toonOntbreekt( k ) {
			var mis = ontbreekt( b, antwoorden, stappen[ k ] );
			var v = invoer[ mis[ 0 ] ] ? invoer[ mis[ 0 ] ].v : null;
			toonMelding( v && 'vinkjes' === v.type && v.min ? tx( 'tool_kies_minimaal', v.min ) : tx( 'tool_vul_in', v ? ( v.label || '' ) : '' ) );
			if ( invoer[ mis[ 0 ] ] ) { var f = invoer[ mis[ 0 ] ].wrap.querySelector( 'input, select, textarea' ); if ( f ) { f.focus(); } }
		}
		function volgende() {
			if ( ontbreekt( b, antwoorden, stappen[ huidig ] ).length ) { toonOntbreekt( huidig ); return; }
			naar( huidig + 1 );
		}

		function naam() { return acties.pdf_bestandsnaam ? vul( acties.pdf_bestandsnaam, b, antwoorden ).replace( /[\\\/:*?"<>|]+/g, '' ).trim().slice( 0, 80 ) || tool.bestandsnaam() : tool.bestandsnaam(); }
		function huidigeBlokken() { return bewerkt || bouw( b, antwoorden ); }
		/* Voor pdf en Word: de antwoordvelden ingevuld, of lege regels om te schrijven. */
		function voorBestand() {
			var uit = [];
			uit.push( { type: 'kop', tekst: b.resultaat_kop ? vul( b.resultaat_kop, b, antwoorden ) : tool.titel } );
			if ( b.resultaat_intro ) { uit.push( { type: 'tekst', tekst: vul( b.resultaat_intro, b, antwoorden ) } ); }
			huidigeBlokken().forEach( function ( bl ) {
				if ( 'invul' !== bl.type ) { uit.push( bl ); return; }
				uit.push( { type: 'invul', nummers: bl.nummers, lijnen: bl.lijnen, items: bl.items.map( function ( it, i ) { return { tekst: it, antwoord: ( invul[ bl.sleutel + '.' + i ] || '' ).trim() }; } ) } );
			} );
			return uit;
		}

		function toonResultaat() {
			resultaat.innerHTML = '';
			var blokken = huidigeBlokken();
			resultaat.appendChild( el( 'h3', { class: 'sfp-tool__kop sfp-tool__kop--resultaat', tabindex: '-1', text: b.resultaat_kop ? vul( b.resultaat_kop, b, antwoorden ) : tool.titel } ) );
			if ( b.resultaat_intro ) { alineas( vul( b.resultaat_intro, b, antwoorden ) ).forEach( function ( x ) { resultaat.appendChild( el( 'p', { class: 'sfp-tool__tekst', text: x } ) ); } ); }
			var inhoud = el( 'div', { class: 'sfp-tool__uitvoer' } );
			if ( b.bewerkbaar ) { resultaat.appendChild( el( 'p', { class: 'sfp-tool__hint sfp-tool__hint--bewerk', text: tx( 'tool_bewerk_hint' ) } ) ); }
			blokken.forEach( function ( bl, bi ) {
				if ( 'subkop' === bl.type ) { inhoud.appendChild( el( 'h4', { class: 'sfp-tool__titel sfp-tool__titel--klein', text: bl.tekst } ) ); return; }
				if ( 'tekst' === bl.type ) {
					if ( b.bewerkbaar ) { inhoud.appendChild( el( 'div', { class: 'sfp-tool__bewerk', contenteditable: 'true', role: 'textbox', 'aria-multiline': 'true', 'aria-label': tx( 'tool_pas_aan' ), spellcheck: 'true', oninput: function () { var x = huidigeBlokken().slice(); x[ bi ] = { type: 'tekst', tekst: this.innerText.replace( /\n{3,}/g, '\n\n' ).trim() }; bewerkt = x; } }, [ bl.tekst ] ) ); return; }
					alineas( bl.tekst ).forEach( function ( x ) { inhoud.appendChild( el( 'p', { class: 'sfp-tool__tekst' }, x.split( '\n' ).reduce( function ( acc, r, k ) { return k ? acc.concat( [ el( 'br' ), r ] ) : acc.concat( [ r ] ); }, [] ) ) ); } );
					return;
				}
				if ( 'tabel' === bl.type ) { inhoud.appendChild( el( 'dl', { class: 'sfp-tool__gegevens' }, bl.rijen.reduce( function ( acc, r ) { return acc.concat( [ el( 'dt', { text: r[ 0 ] } ), el( 'dd', { text: r[ 1 ] } ) ] ); }, [] ) ) ); return; }
				var lijst = el( bl.nummers ? 'ol' : 'ul', { class: 'sfp-tool__lijst' + ( 'afvink' === bl.type ? ' sfp-tool__lijst--afvink' : '' ) } );
				bl.items.forEach( function ( it, i ) {
					var li = el( 'li', null, [ 'afvink' === bl.type ? el( 'label', null, [ el( 'input', { type: 'checkbox' } ), ' ', it ] ) : it ] );
					if ( 'invul' === bl.type ) {
						var sleutel = bl.sleutel + '.' + i;
						li.appendChild( el( 'textarea', { class: 'sfp-tool__veld sfp-tool__veld--antwoord', rows: '2', maxlength: '2000', 'aria-label': tx( 'tool_jouw_antwoord' ) + ': ' + it, placeholder: tx( 'tool_jouw_antwoord' ), oninput: function () { invul[ sleutel ] = this.value; } }, [ invul[ sleutel ] || '' ] ) );
					}
					lijst.appendChild( li );
				} );
				inhoud.appendChild( lijst );
			} );
			resultaat.appendChild( inhoud );

			var rij = el( 'div', { class: 'sfp-tool__acties sfp-tool__acties--uitslag' } );
			var rijLinks = el( 'div', { class: 'sfp-tool__acties-links' } );
			var deelMelding = el( 'p', { class: 'sfp-tool__melding sfp-tool__melding--klein', role: 'status', hidden: true } );
			function wacht( knop, p ) { var oud = knop.textContent; knop.disabled = true; knop.textContent = tx( 'tool_bezig' ) + '…'; return p.then( function () { knop.disabled = false; knop.textContent = oud; }, function ( e ) { knop.disabled = false; knop.textContent = oud; throw e; } ); }
			function bestand( knop, p ) { deelMelding.hidden = true; wacht( knop, p ).catch( function () { deelMelding.textContent = tx( 'tool_bestand_fout' ); deelMelding.hidden = false; } ); }
			function kort( m ) { deelMelding.textContent = m; deelMelding.hidden = false; setTimeout( function () { deelMelding.hidden = true; }, 6000 ); }
			if ( aan( 'pdf' ) ) { rij.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: tx( 'tool_pdf' ), onclick: function () { bestand( this, tool.pdf( voorBestand(), naam() ) ); } } ) ); }
			if ( aan( 'kopieer' ) ) { rijLinks.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink', text: tx( 'tool_kopieer' ), onclick: function () {
				var kopTekst = b.resultaat_kop ? vul( b.resultaat_kop, b, antwoorden ) : tool.titel;
				var tekst = platteTekst( kopTekst, ( ! acties.kopieer_kaal && b.resultaat_intro ? [ { type: 'tekst', tekst: vul( b.resultaat_intro, b, antwoorden ) } ] : [] ).concat( huidigeBlokken() ), invul, !! acties.kopieer_kaal );
				if ( navigator.clipboard && navigator.clipboard.writeText ) { navigator.clipboard.writeText( tekst ).then( function () { kort( tx( 'tool_gekopieerd' ) ); tool.zeg( tx( 'tool_gekopieerd' ) ); }, function () { kort( tx( 'tool_bestand_fout' ) ); } ); } else { kort( tx( 'tool_bestand_fout' ) ); }
			} } ) ); }
			if ( aan( 'word' ) ) { rijLinks.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink', text: tx( 'tool_word' ), onclick: function () { bestand( this, tool.word( voorBestand(), naam() ) ); } } ) ); }
			var mailbox = null;
			if ( aan( 'mail' ) ) {
				rijLinks.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink', text: tx( 'tool_mail' ), 'aria-expanded': 'false', onclick: function () { mailbox.hidden = ! mailbox.hidden; this.setAttribute( 'aria-expanded', mailbox.hidden ? 'false' : 'true' ); if ( ! mailbox.hidden ) { mailbox.querySelector( 'input[type=email]' ).focus(); } } } ) );
				var email = el( 'input', { type: 'email', class: 'sfp-tool__veld', maxlength: '160', autocomplete: 'email', placeholder: tx( 'tool_mail_veld' ), 'aria-label': tx( 'tool_mail_veld' ) } );
				var hp = el( 'input', { type: 'text', name: 'website', tabindex: '-1', autocomplete: 'off', class: 'sfp-tool__hp', 'aria-hidden': 'true' } );
				var mailMelding = el( 'p', { class: 'sfp-tool__melding sfp-tool__melding--klein', role: 'status', hidden: true } );
				var stuur = el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: tx( 'tool_mail_verstuur' ), onclick: function () {
					var knop = this;
					mailMelding.classList.remove( 'is-fout' );
					wacht( knop, tool.mail( email.value.trim(), antwoorden, hp.value ).then( function ( res ) {
						mailMelding.textContent = res.ok ? tx( 'tool_mail_ok' ) : ( ( res.data && res.data.message ) || tx( 'tool_mail_fout' ) );
						mailMelding.classList.toggle( 'is-fout', ! res.ok );
						mailMelding.hidden = false;
					}, function () { mailMelding.textContent = tx( 'tool_mail_fout' ); mailMelding.classList.add( 'is-fout' ); mailMelding.hidden = false; } ) );
				} } );
				mailbox = el( 'div', { class: 'sfp-tool__mailbox', hidden: true }, [ el( 'div', { class: 'sfp-tool__mailrij' }, [ email, stuur ] ), hp, mailMelding ] );
			}
			if ( aan( 'deellink' ) ) { rijLinks.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink', text: tx( 'tool_deellink' ), onclick: function () { tool.deel( antwoorden, kort ); } } ) ); }
			rij.appendChild( rijLinks );
			resultaat.appendChild( rij );
			if ( mailbox ) { resultaat.appendChild( mailbox ); }
			resultaat.appendChild( deelMelding );
			resultaat.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink sfp-tool__opnieuw', text: tx( 'tool_opnieuw' ), onclick: opnieuw } ) );
			if ( aan( 'duimpjes' ) ) { resultaat.appendChild( tool.duimWidget() ); }
			if ( b.resultaat_slot ) { var slot = el( 'div', { class: 'sfp-tool__slot' } ); alineas( vul( b.resultaat_slot, b, antwoorden ) ).forEach( function ( x ) { slot.appendChild( el( 'p', { class: 'sfp-tool__tekst', text: x } ) ); } ); resultaat.insertBefore( slot, rij ); }
			if ( tool.formulier ) {
				tool.formulier.hidden = false;
				/* Verborgen velden van het formulier vullen met het resultaat (@uitvoer) of een sjabloon. */
				var fv = acties.formulier_velden || {};
				Object.keys( fv ).forEach( function ( naam ) {
					var w = '@uitvoer' === fv[ naam ] ? platteTekst( tool.titel, huidigeBlokken(), invul, true ) : vul( fv[ naam ], b, antwoorden );
					Array.prototype.forEach.call( tool.formulier.querySelectorAll( 'input, textarea' ), function ( inp ) {
						var n = inp.getAttribute( 'name' ) || '';
						if ( n === naam || n.slice( - ( naam.length + 1 ) ) === '-' + naam ) { inp.value = w; inp.dispatchEvent( new Event( 'input', { bubbles: true } ) ); }
					} );
				} );
			}
		}

		function vulIn( a ) {
			antwoorden = {};
			Object.keys( invoer ).forEach( function ( id ) {
				var v = invoer[ id ].v;
				if ( ! heeft( a, id ) ) { return; }
				var w = antwoordVan( b, a, id );
				if ( isLeeg( w ) ) { return; }
				antwoorden[ id ] = w;
				var veld = invoer[ id ].veld;
				if ( 'keuze' === v.type || 'vinkjes' === v.type ) { Array.prototype.forEach.call( veld.querySelectorAll( 'input' ), function ( x ) { x.checked = Array.isArray( w ) ? w.indexOf( x.value ) >= 0 : x.value === w; } ); }
				else { veld.value = w; }
			} );
			werkZichtbaar();
		}
		/* Standaardwaarden: vinkjes die al aan staan, de datum van vandaag. */
		function standaard() {
			var a = {};
			Object.keys( invoer ).forEach( function ( id ) {
				var v = invoer[ id ].v;
				if ( 'vinkjes' === v.type && Array.isArray( v.standaard ) ) { a[ id ] = v.standaard.map( String ); }
				else if ( 'datum' === v.type && 'vandaag' === v.standaard ) { var d = new Date(); a[ id ] = d.getFullYear() + '-' + ( '0' + ( d.getMonth() + 1 ) ).slice( -2 ) + '-' + ( '0' + d.getDate() ).slice( -2 ); }
				else if ( 'string' === typeof v.standaard && '' !== v.standaard ) { a[ id ] = v.standaard; }
			} );
			return a;
		}
		function opnieuw() {
			tool.wisBewaard();
			invul = {}; bewerkt = null;
			Array.prototype.forEach.call( app.querySelectorAll( 'input[type=radio], input[type=checkbox]' ), function ( i ) { i.checked = false; } );
			Array.prototype.forEach.call( app.querySelectorAll( 'input[type=text], input[type=date], input[type=number], textarea, select' ), function ( i ) { i.value = ''; } );
			vulIn( standaard() );
			if ( tool.formulier ) { tool.formulier.hidden = true; }
			naar( 0 );
		}

		return {
			start: function () {
				app.appendChild( balk );
				app.appendChild( vorige );
				app.appendChild( melding );
				app.appendChild( schermenWrap );
				vulIn( standaard() );
				var gedeeld = tool.uitDeellink();
				if ( gedeeld ) { vulIn( Object.assign( standaard(), gedeeld ) ); tool.gestart = false; naar( stappen.length ); return; }
				var bewaard = tool.bewaard();
				if ( bewaard && bewaard.a && Object.keys( bewaard.a ).length ) {
					var vraag = el( 'div', { class: 'sfp-tool__scherm sfp-tool__scherm--intro' }, [
						el( 'p', { text: tx( 'tool_verder' ) } ),
						el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: tx( 'tool_verder_ja' ), onclick: function () { schermenWrap.removeChild( vraag ); vulIn( Object.assign( standaard(), bewaard.a ) ); naar( Math.min( bewaard.i || 0, stappen.length ) ); } } ),
						el( 'button', { type: 'button', class: 'sfp-tool__tekstlink', text: tx( 'tool_verder_nee' ), onclick: function () { schermenWrap.removeChild( vraag ); tool.wisBewaard(); naar( 0 ); } } )
					] );
					schermen.forEach( function ( s ) { s.hidden = true; } );
					schermenWrap.insertBefore( vraag, schermenWrap.firstChild );
					return;
				}
				tool.gestart = false;
				naar( 0 );
			},
			bouw: function ( a ) { return bouw( b, a ); }
		};
	} );
} )();
