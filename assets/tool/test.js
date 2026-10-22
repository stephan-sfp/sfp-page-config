/**
 * SFP Page Config - Tools: soort test
 *
 * Vragen (Likert, keuze, context, tekst), scores per dimensie en een
 * uitkomst volgens de regel in de beschrijving. Het rekenwerk is gelijk aan
 * includes/tool-beschrijving.php (sfp_page_config_tool_reken).
 *
 * @since 3.8.0
 */
( function () {
	'use strict';
	var M = window.sfpToolMotor;
	if ( ! M ) { return; }
	var t = M.t, el = M.el, getal = M.getal, alineas = M.alineas;

	/* ---------------------------------------------------------------- *
	 * Rekenen
	 * ---------------------------------------------------------------- */

	function label( b, waarde, max, procent, lijst ) {
		lijst = ( lijst && lijst.length ) ? lijst : b.labels;
		if ( ! lijst || ! lijst.length ) { return { tekst: '', sleutel: '' }; }
		var labels = lijst.slice().sort( function ( a, c ) { return ( c.vanaf || 0 ) - ( a.vanaf || 0 ); } );
		var op = waarde;
		if ( b.labels_percentage || ( procent && 'percentage' !== scoreRegel( b ) ) ) { op = max > 0 ? waarde / max * 100 : 0; }
		for ( var i = 0; i < labels.length; i++ ) {
			if ( op >= ( labels[ i ].vanaf || 0 ) ) { return { tekst: labels[ i ].tekst || '', sleutel: labels[ i ].sleutel || labels[ i ].tekst || '' }; }
		}
		var l = labels[ labels.length - 1 ];
		return { tekst: l.tekst || '', sleutel: l.sleutel || l.tekst || '' };
	}
	function scoreRegel( b ) { return ( b.score && b.score.per_dimensie ) || 'som'; }
	/* De dimensies in hun volgorde: een object zet sleutels van alleen cijfers vooraan, de lijst niet. */
	function dimLijst( b ) { return ( b.dimensie_volgorde && b.dimensie_volgorde.length ) ? b.dimensie_volgorde.slice() : Object.keys( b.dimensies || {} ); }
	/* Een antwoord als tekst; ja/nee, lijsten en leeg tellen niet (gelijk aan PHP). */
	function antwoord( w ) { return ( 'string' === typeof w || ( 'number' === typeof w && isFinite( w ) ) ) ? String( w ) : null; }
	function isGetal( w ) { if ( 'number' === typeof w ) { return isFinite( w ); } return 'string' === typeof w && /^\s*-?(\d+\.?\d*|\.\d+)([eE][-+]?\d+)?\s*$/.test( w ); }
	function alsTekst( w ) { return ( 'boolean' === typeof w ) ? ( w ? 'true' : 'false' ) : ( w === null || w === undefined || 'object' === typeof w ? '' : String( w ) ); }
	/* Afronden half naar boven met een kleine marge, gelijk aan sfp_page_config_tool_rond() in PHP. */
	var rond = M.rond;
	function vergelijk( links, op, rechts ) {
		if ( '==' === op ) { return alsTekst( links ) === alsTekst( rechts ); }
		if ( ! isGetal( links ) || ! isGetal( rechts ) ) { return false; }
		var l = Number( links ), r = Number( rechts );
		if ( '>=' === op ) { return l >= r; } if ( '>' === op ) { return l > r; } if ( '<=' === op ) { return l <= r; } if ( '<' === op ) { return l < r; }
		return false;
	}
	function optieWaarde( o, j ) { return o.waarde !== undefined ? String( o.waarde ) : String( j + 1 ); }

	function reken( b, antwoorden ) {
		var dims = dimLijst( b );
		var som = {}, n = {}, maxsom = {}, telling = {}, telmax = {}, cent = {};
		dims.forEach( function ( d ) { som[ d ] = 0; n[ d ] = 0; maxsom[ d ] = 0; telling[ d ] = 0; telmax[ d ] = 0; cent[ d ] = 0; } );
		var gewicht = ( b.score && b.score.gewicht ) || {};
		var bijdragen = {}, context = {}, teksten = {}, ontbreekt = [];
		( b.vragen || [] ).forEach( function ( v ) {
			var id = v.id, w = Object.prototype.hasOwnProperty.call( antwoorden, id ) ? antwoord( antwoorden[ id ] ) : null;
			var type = v.type || 'likert';
			if ( 'ab' === type || 'janee' === type ) { type = 'keuze'; }
			if ( 'tekst' === type ) { teksten[ id ] = w === null ? '' : w.trim(); return; }
			if ( 'context' === type ) {
				context[ id ] = '';
				( v.opties || [] ).forEach( function ( o, j ) { if ( w !== null && w === optieWaarde( o, j ) ) { context[ id ] = o.tekst || ''; } } );
				if ( '' === context[ id ] && ! v.optioneel ) { ontbreekt.push( id ); }
				return;
			}
			if ( 'likert' === type ) {
				var dim = v.dimensie;
				if ( ! ( dim in som ) ) { return; }
				var g = gewicht[ dim ] !== undefined ? Number( gewicht[ dim ] ) : 1;
				n[ dim ]++; maxsom[ dim ] += 5;
				/* Alleen 1 tot 5; een waarde uit een deellink kan van alles zijn. */
				if ( w === null || ! /^[1-5]$/.test( w ) ) { ontbreekt.push( id ); return; }
				var waarde = parseInt( w, 10 );
				if ( v.omgekeerd ) { waarde = 6 - waarde; }
				som[ dim ] += waarde; cent[ dim ] += ( waarde - 3 ) * g;
				bijdragen[ id ] = {}; bijdragen[ id ][ dim ] = waarde;
				return;
			}
			var perdim = {};
			( v.opties || [] ).forEach( function ( o ) { Object.keys( o.punten || {} ).forEach( function ( d ) { var p = Number( o.punten[ d ] ) || 0; if ( perdim[ d ] === undefined || p > perdim[ d ] ) { perdim[ d ] = p; } } ); } );
			Object.keys( perdim ).forEach( function ( d ) { if ( d in maxsom ) { maxsom[ d ] += perdim[ d ]; telmax[ d ]++; } } );
			var gekozen = null;
			( v.opties || [] ).forEach( function ( o, j ) { if ( w !== null && w === optieWaarde( o, j ) ) { gekozen = o; } } );
			if ( ! gekozen ) { if ( ! v.optioneel ) { ontbreekt.push( id ); } return; }
			bijdragen[ id ] = {};
			Object.keys( gekozen.punten || {} ).forEach( function ( d ) { if ( ! ( d in som ) ) { return; } var p = Number( gekozen.punten[ d ] ) || 0; som[ d ] += p; if ( p > 0 ) { telling[ d ]++; } bijdragen[ id ][ d ] = p; } );
		} );
		var waarden = {}, maxima = {}, minima = {};
		dims.forEach( function ( d ) {
			switch ( scoreRegel( b ) ) {
				case 'telling': waarden[ d ] = telling[ d ]; maxima[ d ] = telmax[ d ]; minima[ d ] = 0; break;
				case 'percentage':
					var pct = n[ d ] > 0 ? ( som[ d ] - n[ d ] ) / ( n[ d ] * 4 ) * 100 : ( maxsom[ d ] > 0 ? som[ d ] / maxsom[ d ] * 100 : 0 );
					waarden[ d ] = Math.max( 0, Math.min( 100, rond( pct ) ) ); maxima[ d ] = 100; minima[ d ] = 0; break;
				case 'gecentreerd':
					var gg = gewicht[ d ] !== undefined ? Number( gewicht[ d ] ) : 1;
					waarden[ d ] = cent[ d ]; maxima[ d ] = 2 * n[ d ] * gg; minima[ d ] = -maxima[ d ]; break;
				default: waarden[ d ] = som[ d ]; maxima[ d ] = maxsom[ d ]; minima[ d ] = 0;
			}
		} );
		var volgorde = dims.slice().sort( function ( a, c ) { return waarden[ a ] === waarden[ c ] ? dims.indexOf( a ) - dims.indexOf( c ) : waarden[ c ] - waarden[ a ]; } );
		var totaal = 0, totaalMax = 0;
		dims.forEach( function ( d ) { totaal += waarden[ d ]; totaalMax += maxima[ d ]; } );
		var gemiddelde = totaal / Math.max( 1, dims.length );
		var spreiding = dims.length ? Math.max.apply( null, dims.map( function ( d ) { return waarden[ d ]; } ) ) - Math.min.apply( null, dims.map( function ( d ) { return waarden[ d ]; } ) ) : 0;
		var dimensies = {};
		dims.forEach( function ( d ) { dimensies[ d ] = { waarde: waarden[ d ], max: maxima[ d ], min: minima[ d ], label: label( b, waarden[ d ], maxima[ d ] ) }; } );
		var stand = { waarden: waarden, volgorde: volgorde, totaal: totaal, totaal_max: totaalMax, gemiddelde: gemiddelde, spreiding: spreiding, antwoorden: antwoorden, dimensies: dimensies };
		var uk = uitkomst( b, b.uitkomst || {}, stand );
		var uitslag = uk.uitslag, top = uk.top, niveaus = uk.niveaus, assen = uk.assen;
		var gelijk = volgorde.length > 1 && waarden[ volgorde[ 0 ] ] === waarden[ volgorde[ 1 ] ];
		return { compleet: 0 === ontbreekt.length, ontbreekt: ontbreekt, dimensies: dimensies, volgorde: volgorde, uitslag: uitslag, top: top, niveaus: niveaus, totaal: totaal, totaal_max: totaalMax, gemiddelde: gemiddelde, spreiding: spreiding, assen: assen, gelijkspel: gelijk, bijdragen: bijdragen, context: context, teksten: teksten };
	}

	/* Een uitkomstregel, met geneste anders, vertaal, minimaal en minimale_spreiding (gelijk aan PHP). */
	function uitkomst( b, u, stand ) {
		var dims = dimLijst( b ), waarden = stand.waarden, volgorde = stand.volgorde;
		var regel = u.regel || 'hoogste';
		var labels = ( u.labels && u.labels.length ) ? u.labels : b.labels;
		var uit = { uitslag: '', top: volgorde.slice( 0, 1 ), niveaus: {}, assen: {} };
		/* Het vangnet begint opnieuw: wat de regel al invulde (top bij paar) telt niet (gelijk aan PHP). */
		function anders() {
			if ( u.anders && 'object' === typeof u.anders ) { return uitkomst( b, u.anders, stand ); }
			return { uitslag: u.anders ? String( u.anders ) : '', top: volgorde.slice( 0, 1 ), niveaus: {}, assen: {} };
		}
		function klaar( sleutel ) {
			if ( u.vertaal && u.vertaal[ sleutel ] !== undefined ) { sleutel = String( u.vertaal[ sleutel ] ); }
			uit.uitslag = sleutel;
			return uit;
		}
		if ( ! volgorde.length ) { return anders(); }
		if ( u.minimaal !== undefined && waarden[ volgorde[ 0 ] ] < Number( u.minimaal ) ) { return anders(); }
		if ( u.minimale_spreiding !== undefined && stand.spreiding <= Number( u.minimale_spreiding ) ) { return anders(); }
		switch ( regel ) {
			case 'hoogste': return klaar( volgorde[ 0 ] );
			case 'top': uit.top = volgorde.slice( 0, Math.max( 1, parseInt( u.aantal, 10 ) || 1 ) ); return klaar( volgorde[ 0 ] );
			case 'drempels': dims.forEach( function ( d ) { uit.niveaus[ d ] = label( b, waarden[ d ], stand.dimensies[ d ].max, false, labels ).sleutel; } ); return klaar( volgorde[ 0 ] );
			case 'totaal':
				var pw = u.percentage ? ( stand.totaal_max > 0 ? stand.totaal / stand.totaal_max * 100 : 0 ) : stand.totaal;
				return klaar( label( b, pw, u.percentage ? 100 : stand.totaal_max, false, labels ).sleutel );
			case 'paar':
				if ( volgorde.length < 2 ) { return anders(); }
				var twee = volgorde.slice( 0, 2 ).sort( function ( a, c ) { return dims.indexOf( a ) - dims.indexOf( c ); } );
				uit.top = volgorde.slice( 0, 2 );
				var ps = twee.join( '+' );
				if ( u.paren ) { return u.paren[ ps ] !== undefined ? klaar( String( u.paren[ ps ] ) ) : anders(); }
				if ( ! ( b.uitslagen && b.uitslagen[ ps ] ) && u.anders !== undefined ) { return anders(); }
				return klaar( ps );
			case 'kwadrant':
				var sleutel = '';
				[ 'x', 'y' ].forEach( function ( as ) {
					var def = ( u.assen && u.assen[ as ] ) || {}, plus = 0, min = 0;
					( def.plus || [] ).forEach( function ( d ) { plus += waarden[ d ] || 0; } );
					( def.min || [] ).forEach( function ( d ) { min += waarden[ d ] || 0; } );
					var w = plus - min;
					var nul = u.nul === undefined ? '+' : ( 'object' === typeof u.nul ? ( u.nul[ as ] || '+' ) : u.nul );
					var teken = w > 0 ? '+' : ( w < 0 ? '-' : ( '-' === nul ? '-' : '+' ) );
					uit.assen[ as ] = { waarde: w, naam: def.naam || as, teken: teken };
					sleutel += as + teken;
				} );
				return ( u.kwadranten && u.kwadranten[ sleutel ] !== undefined ) ? klaar( String( u.kwadranten[ sleutel ] ) ) : anders();
			case 'regels': case 'profiel':
				var lijst = { totaal: stand.totaal, gemiddelde: stand.gemiddelde, spreiding: stand.spreiding };
				dims.forEach( function ( d ) { lijst[ 'dimensie.' + d ] = waarden[ d ]; } );
				/* Het antwoord zoals het is; vrije tekst telt niet mee (gelijk aan PHP). */
				( b.vragen || [] ).forEach( function ( v ) { var aw = Object.prototype.hasOwnProperty.call( stand.antwoorden, v.id ) ? antwoord( stand.antwoorden[ v.id ] ) : null; if ( 'tekst' !== v.type && aw !== null ) { lijst[ 'vraag.' + v.id ] = aw; } } );
				var regels = u.regels || [];
				for ( var i = 0; i < regels.length; i++ ) {
					var r = regels[ i ], klopt = true, als = r.als || [];
					for ( var k = 0; k < als.length; k++ ) {
						var vw = als[ k ];
						if ( ! vw || 3 !== vw.length || ! Object.prototype.hasOwnProperty.call( lijst, vw[ 0 ] ) || ! vergelijk( lijst[ vw[ 0 ] ], vw[ 1 ], vw[ 2 ] ) ) { klopt = false; break; }
					}
					if ( klopt ) { return klaar( String( r.dan ) ); }
				}
				return anders();
		}
		return anders();
	}

	function uitslagtekst( b, sleutel ) {
		var u = sleutel && b.uitslagen && b.uitslagen[ sleutel ];
		if ( 'string' === typeof u ) { u = { tekst: u }; }
		u = u || {};
		return { kop: u.kop || '', titel: u.titel || '', tekst: u.tekst || '', lijsten: u.lijsten || {}, artikel: u.artikel || '' };
	}
	function bron( b, r, pad ) {
		var delen = String( pad || '' ).split( '.' ), kop = delen.shift();
		if ( 'totaal' === kop ) { return getal( r.totaal ); }
		if ( 'totaal_max' === kop ) { return getal( r.totaal_max ); }
		if ( 'lijsten' === kop ) { var ln = delen.join( '.' ); return ( b.lijsten && b.lijsten[ ln ] ) ? b.lijsten[ ln ] : null; }
		if ( 'vraag' === kop ) { var id = delen.join( '.' ); return r.teksten[ id ] !== undefined ? r.teksten[ id ] : ( r.context[ id ] !== undefined ? r.context[ id ] : null ); }
		if ( 'context' === kop ) { return r.context[ delen.join( '.' ) ] !== undefined ? r.context[ delen.join( '.' ) ] : null; }
		if ( 'dimensie' === kop ) {
			var d = delen.shift(); if ( ! r.dimensies[ d ] ) { return null; }
			var veld = delen.length ? delen.join( '.' ) : 'waarde';
			if ( 'naam' === veld ) { return dimNaam( b, d ); }
			if ( 'label' === veld ) { return r.dimensies[ d ].label.tekst; }
			return r.dimensies[ d ][ veld ] !== undefined ? String( r.dimensies[ d ][ veld ] ) : null;
		}
		if ( 'uitslag' !== kop ) { return null; }
		var sleutel = r.uitslag, m;
		if ( delen.length && 'laagste' === delen[ 0 ] ) { delen.shift(); sleutel = r.volgorde.length ? r.volgorde[ r.volgorde.length - 1 ] : ''; }
		else if ( delen.length && ( m = delen[ 0 ].match( /^top\[(\d+)\]$/ ) ) ) { delen.shift(); sleutel = r.volgorde[ parseInt( m[ 1 ], 10 ) - 1 ] || ''; }
		var u = uitslagtekst( b, sleutel );
		var v = delen.length ? delen.join( '.' ) : 'tekst';
		if ( 'kop' === v || 'titel' === v || 'tekst' === v || 'artikel' === v ) { return u[ v ]; }
		if ( 'naam' === v ) { return b.dimensies[ sleutel ] ? dimNaam( b, sleutel ) : u.titel; }
		return u.lijsten[ v ] !== undefined ? u.lijsten[ v ] : null;
	}
	function vul( tekst, b, r, diepte ) {
		var uit = String( tekst || '' ).replace( /\{([a-z0-9_.\[\]]+)\}/gi, function ( m, p ) { var w = bron( b, r, p ); return w === null || w === undefined ? '' : ( Array.isArray( w ) ? w.map( item ).join( ', ' ) : String( w ) ); } );
		return ( ! diepte && uit.indexOf( '{' ) >= 0 ) ? vul( uit, b, r, 1 ) : uit;
	}
	/* zonderHoogste: dimensies met de topscore vallen weg (gelijk aan PHP, sinds 3.8.1). */
	function selectie( r, soort, aantal, zonderHoogste ) {
		var v = r.volgorde; if ( ! v.length ) { return []; }
		var w = function ( d ) { return r.dimensies[ d ].waarde; };
		if ( zonderHoogste ) { var top = w( v[ 0 ] ); v = v.filter( function ( d ) { return w( d ) !== top; } ); if ( ! v.length ) { return []; } }
		if ( 'hoogste' === soort ) { var mx = w( v[ 0 ] ); return v.filter( function ( d ) { return w( d ) === mx; } ); }
		if ( 'laagste' === soort ) { var mn = w( v[ v.length - 1 ] ); return v.filter( function ( d ) { return w( d ) === mn; } ); }
		if ( 'top' === soort ) { return v.slice( 0, Math.max( 1, aantal || 1 ) ); }
		return v.slice();
	}
	/* Eén item van een lijst als tekst (een rij: de cellen achter elkaar). */
	function item( it ) { return Array.isArray( it ) ? it.map( String ).join( ' | ' ) : String( it ); }
	/* Alleen http(s), een pad op de site of een anker (gelijk aan sfp_page_config_tool_url()). */
	function veiligeUrl( u ) { u = String( u || '' ).replace( /[\x00-\x1f\x7f]+/g, '' ).trim().replace( / /g, '%20' ); return ( /^https?:\/\/[a-z0-9\[]/i.test( u ) || /^\/(?![\/\\])/.test( u ) || 0 === u.indexOf( '#' ) ) ? u : ''; }
	function dimNaam( b, d ) { var x = b.dimensies[ d ]; return 'string' === typeof x ? x : ( x && x.naam ) || d; }
	function dimVolledig( b, d ) { var x = b.dimensies[ d ]; return 'string' === typeof x ? x : ( x && ( x.volledig || x.naam ) ) || d; }

	/* ---------------------------------------------------------------- *
	 * De test op de pagina
	 * ---------------------------------------------------------------- */

	M.soort( 'test', function ( tool ) {
		var b = tool.b;
		var app = tool.app;
		var vragen = ( b.vragen || [] ).map( function ( v ) { v = Object.assign( {}, v ); if ( 'ab' === v.type || 'janee' === v.type ) { v.type = 'keuze'; } if ( ! v.type ) { v.type = 'likert'; } return v; } );
		var W = b.weergave || {};
		var perGroep = 'per-groep' === W.modus;
		var perVraag = ! ( 'alles' === W.modus );
		var antwoorden = {};
		var schermen = [];
		var huidig = 0;
		var tekstvragenNa = vragen.filter( function ( v ) { return 'tekst' === v.type && v.na_uitslag; } );
		var vragenVoor = vragen.filter( function ( v ) { return ! ( 'tekst' === v.type && v.na_uitslag ); } );
		var heeftContext = vragenVoor.some( function ( v ) { return 'context' === v.type; } );
		var stapNamen = ( b.weergave && b.weergave.stappen && b.weergave.stappen.length ) ? b.weergave.stappen : ( heeftContext ? [ t( 'tool_context' ), t( 'tool_vragen' ), t( 'tool_resultaat' ) ] : [ t( 'tool_vragen' ), t( 'tool_resultaat' ) ] );
		var scorend = vragenVoor.filter( function ( v ) { return 'likert' === v.type || 'keuze' === v.type; } ).length;
		var eigen = b.teksten || {};
		function tx( sleutel ) { var a = Array.prototype.slice.call( arguments, 1 ); return eigen[ sleutel ] ? t.apply( null, [ eigen[ sleutel ] ].concat( a ) ) : t.apply( null, [ sleutel ].concat( a ) ); }

		/* Opbouw */
		var stappen = el( 'ul', { class: 'sfp-tool__stappen', role: 'tablist' } );
		var paneelVragen = el( 'div', { class: 'sfp-tool__paneel is-actief', role: 'tabpanel' } );
		var paneelResultaat = el( 'div', { class: 'sfp-tool__paneel', role: 'tabpanel', hidden: true } );
		var balk = el( 'div', { class: 'sfp-tool__balk' }, [ el( 'div', { class: 'sfp-tool__balk-vul' } ) ] );
		var voortgang = el( 'p', { class: 'sfp-tool__voortgang', 'aria-live': 'polite' } );
		var legenda = el( 'div', { class: 'sfp-tool__legenda', hidden: true } );
		var vorige = el( 'button', { type: 'button', class: 'sfp-tool__vorige', hidden: true, text: '‹ ' + tx( 'tool_vorige' ), onclick: function () { ga( huidig - 1 ); } } );
		var schermenWrap = el( 'div', { class: 'sfp-tool__schermen' } );
		var melding = el( 'p', { class: 'sfp-tool__melding', role: 'alert', hidden: true } );
		var stapItems = stapNamen.map( function ( naam, i ) {
			var laatste = i === stapNamen.length - 1;
			return el( 'li', { class: 'sfp-tool__stap' + ( 0 === i ? ' is-actief' : '' ) + ( laatste ? ' is-slot' : '' ), role: 'tab', 'aria-selected': 0 === i ? 'true' : 'false', tabindex: 0 === i ? '0' : '-1', 'data-stap': String( i ), onclick: function () { naarStap( i ); }, onkeydown: function ( e ) { if ( 'Enter' === e.key || ' ' === e.key ) { e.preventDefault(); naarStap( i ); } } }, [ naam ] );
		} );
		stapItems.forEach( function ( li ) { stappen.appendChild( li ); } );
		/* De stappen staan er de hele tijd: waar je bent in het geheel; de balk en de teller zeggen waar je bent in de vragen. */
		app.appendChild( stappen );
		/* Rustig en in leesvolgorde: waar je bent, de vraag, dan pas Vorige. */
		paneelVragen.appendChild( voortgang );
		paneelVragen.appendChild( balk );
		paneelVragen.appendChild( schermenWrap );
		paneelVragen.appendChild( vorige );
		paneelVragen.appendChild( melding );
		app.appendChild( paneelVragen );
		app.appendChild( paneelResultaat );

		/* Schermen */
		function schaal( v ) { return ( v.schaal && Object.keys( v.schaal ).length ) ? v.schaal : ( ( b.weergave && b.weergave.schaal ) || {} ); }
		function maakScherm( v, index ) {
			/* Per vraag of per groep staat de groep in de ene opschriftregel boven de balk; alleen bij alles op
			   één pagina een eigen opschrift, en dan alleen waar een nieuwe groep begint. */
			var vorigeVraag = index > 0 ? vragenVoor[ index - 1 ] : null;
			var kop = ! perVraag && v.groep && ( ! vorigeVraag || vorigeVraag.groep !== v.groep ) ? el( 'p', { class: 'sfp-tool__groep', text: v.groep } ) : null;
			var vraagId = 'sfp-tool-' + tool.id + '-v-' + index;
			var vraagkop = ( W.vraagkop && ! perGroep && ! ( 'tekst' === v.type ) ) ? el( 'p', { class: 'sfp-tool__vraagkop', text: W.vraagkop } ) : null;
			var tekst = el( 'p', { class: 'sfp-tool__vraag', id: vraagId, text: v.tekst, tabindex: '-1' } );
			var veld;
			if ( 'likert' === v.type ) {
				var s = schaal( v );
				var alsTekst = !! W.likert_als_tekst;
				veld = el( 'div', { class: 'sfp-tool__likert' + ( alsTekst ? ' sfp-tool__likert--tekst' : '' ), role: 'radiogroup', 'aria-labelledby': vraagId } );
				for ( var w = 1; w <= 5; w++ ) {
					veld.appendChild( maakRadio( v, String( w ), alsTekst ? ( s[ String( w ) ] || String( w ) ) : String( w ), index, alsTekst ? null : s[ String( w ) ] ) );
				}
				/* De betekenis van 1, 3 en 5 onder de vakjes, één keer (geen aparte legenda). */
				var uiteinden = alsTekst ? null : el( 'div', { class: 'sfp-tool__uiteinden' }, [ el( 'span', { text: s[ '1' ] || '' } ), el( 'span', { text: s[ '3' ] || '' } ), el( 'span', { text: s[ '5' ] || '' } ) ] );
				veld = el( 'div', { class: 'sfp-tool__likert-wrap' }, [ veld, uiteinden ] );
			} else if ( 'keuze' === v.type || 'context' === v.type ) {
				veld = el( 'div', { class: 'sfp-tool__keuzes', role: 'radiogroup', 'aria-labelledby': vraagId } );
				( v.opties || [] ).forEach( function ( o, j ) { veld.appendChild( maakRadio( v, optieWaarde( o, j ), o.tekst || '', index ) ); } );
			} else {
				veld = el( 'textarea', { class: 'sfp-tool__veld', rows: '3', maxlength: String( v.maxlengte || 600 ), placeholder: v.voorbeeld || '', 'aria-labelledby': vraagId, oninput: function () { antwoorden[ v.id ] = this.value; bewaar(); } } );
			}
			var scherm = el( 'div', { class: 'sfp-tool__scherm', 'data-index': String( index ), hidden: true }, [ kop, vraagkop, tekst, veld ] );
			if ( 'tekst' === v.type && perVraag && ! perGroep ) {
				scherm.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__knop', text: tx( 'tool_volgende' ), onclick: function () { ga( huidig + 1 ); } } ) );
			}
			return scherm;
		}
		function maakRadio( v, waarde, tekst, index, hint ) {
			var naam = 'sfp-tool-' + tool.id + '-' + v.id;
			var input = el( 'input', { type: 'radio', name: naam, value: waarde, class: 'sfp-tool__radio', onchange: function () {
				antwoorden[ v.id ] = waarde;
				bewaar();
				/* Met de pijltjes door de keuzes: niet doorgaan, Enter gaat verder. */
				if ( pijl ) { return; }
				if ( perGroep ) { var g = schermen[ huidig ]; if ( g && g.vragen && g.vragen.every( function ( q ) { return 'tekst' === q.type || antwoorden[ q.id ] !== undefined; } ) ) { setTimeout( function () { ga( huidig + 1 ); }, 260 ); } }
				else if ( perVraag ) { setTimeout( function () { ga( huidig + 1 ); }, 220 ); }
			} } );
			var lab = el( 'label', { class: 'sfp-tool__optie' + ( 'likert' === v.type ? ' sfp-tool__optie--likert' : '' ) }, [ input, el( 'span', { class: 'sfp-tool__optie-tekst', text: tekst } ) ] );
			if ( hint ) { lab.setAttribute( 'title', hint ); input.setAttribute( 'aria-label', waarde + ( hint ? ' = ' + hint : '' ) ); }
			return lab;
		}
		function maakIntro( v, index ) {
			return el( 'div', { class: 'sfp-tool__scherm sfp-tool__scherm--intro', hidden: true, 'data-intro': '1' }, [
				alineas( v.intro ).map( function ( a ) { return el( 'p', { text: a } ); } ),
				el( 'button', { type: 'button', class: 'sfp-tool__knop', text: tx( 'tool_start' ), onclick: function () { ga( huidig + 1 ); } } )
			].reduce( function ( acc, x ) { return acc.concat( x ); }, [] ) );
		}
		if ( W.intro && W.intro.tekst ) {
			schermen.push( { intro: true, el: el( 'div', { class: 'sfp-tool__scherm sfp-tool__scherm--intro', hidden: true, 'data-intro': '1' }, alineas( W.intro.tekst ).map( function ( a ) { return el( 'p', { text: a } ); } ).concat( [ el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: W.intro.knop || tx( 'tool_start' ), onclick: function () { ga( huidig + 1 ); } } ) ] ) ) } );
		}
		if ( perGroep ) {
			var groepen = [];
			vragenVoor.forEach( function ( v, i ) {
				var naam = v.groep || '';
				var laatste = groepen[ groepen.length - 1 ];
				if ( ! laatste || laatste.naam !== naam ) { laatste = { naam: naam, vragen: [], indexen: [] }; groepen.push( laatste ); }
				laatste.vragen.push( v ); laatste.indexen.push( i );
			} );
			groepen.forEach( function ( g ) {
				var wrap = el( 'div', { class: 'sfp-tool__scherm sfp-tool__scherm--groep', hidden: true } );
				g.vragen.forEach( function ( v, k ) { if ( v.intro ) { wrap.appendChild( el( 'div', { class: 'sfp-tool__intro-tekst' }, alineas( v.intro ).map( function ( a ) { return el( 'p', { text: a } ); } ) ) ); } var sch = maakScherm( v, g.indexen[ k ] ); sch.hidden = false; sch.classList.add( 'is-alles' ); var gr = sch.querySelector( '.sfp-tool__groep' ); if ( gr ) { gr.remove(); } wrap.appendChild( sch ); } );
				wrap.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: tx( 'tool_volgende' ), onclick: function () { var open = g.vragen.filter( function ( q ) { return 'tekst' !== q.type && antwoorden[ q.id ] === undefined; } ); if ( open.length ) { toonMelding( tx( 'tool_ontbreekt', open.length ), true ); return; } ga( huidig + 1 ); } } ) );
				schermen.push( { vragen: g.vragen, vraag: g.vragen[ 0 ], groep: true, el: wrap } );
			} );
		} else {
			vragenVoor.forEach( function ( v, i ) {
				if ( v.intro ) { schermen.push( { intro: true, el: perVraag ? maakIntro( v, i ) : el( 'div', { class: 'sfp-tool__scherm sfp-tool__intro-tekst', hidden: true }, alineas( v.intro ).map( function ( a ) { return el( 'p', { text: a } ); } ) ) } ); }
				schermen.push( { vraag: v, el: maakScherm( v, i ) } );
			} );
		}
		if ( ! perVraag ) {
			/* Alles op één pagina: geen teller en geen balk. */
			balk.hidden = true; voortgang.hidden = true;
			schermen.forEach( function ( s ) { s.el.hidden = false; s.el.classList.add( 'is-alles' ); schermenWrap.appendChild( s.el ); } );
			schermenWrap.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: tx( 'tool_naar_resultaat' ), onclick: function () { probeerResultaat(); } } ) );
		} else {
			schermen.forEach( function ( s ) { schermenWrap.appendChild( s.el ); } );
		}

		/* Navigatie */
		function vraagIndex( i ) { var s = schermen[ i ]; return s && s.vraag ? vragenVoor.indexOf( s.vraag ) : -1; }
		function toonMelding( tekst, fout ) { melding.textContent = tekst; melding.hidden = ! tekst; melding.classList.toggle( 'is-fout', !! fout ); }
		function ga( i ) {
			if ( ! perVraag ) { return; }
			if ( i >= schermen.length ) { probeerResultaat(); return; }
			if ( i < 0 ) { i = 0; }
			huidig = i;
			schermen.forEach( function ( s, k ) { s.el.hidden = k !== i; } );
			toonMelding( '' );
			var s = schermen[ i ];
			var scorendV = vragenVoor.filter( function ( v ) { return 'likert' === v.type || 'keuze' === v.type; } );
			var qi = s.vraag ? scorendV.indexOf( s.vraag ) : -1;
			var qn = scorendV.length;
			/* Eén opschriftregel: de groep (als de vragen verschillende groepen hebben) en waar je bent. */
			var groepenScorend = scorendV.map( function ( v ) { return v.groep || ''; } ).filter( function ( g, k, a ) { return a.indexOf( g ) === k; } );
			if ( s.groep ) {
				var gi = schermen.filter( function ( x ) { return x.groep; } ).indexOf( s ), gn = schermen.filter( function ( x ) { return x.groep; } ).length;
				voortgang.textContent = s.vragen[ 0 ].groep ? s.vragen[ 0 ].groep + ' · ' + ( gi + 1 ) + '/' + gn : tx( 'tool_vraag_van', gi + 1, gn );
				balk.firstChild.style.width = Math.round( ( gi + 1 ) / gn * 100 ) + '%';
			} else if ( s.vraag && qi >= 0 ) {
				voortgang.textContent = ( groepenScorend.length > 1 && s.vraag.groep ? s.vraag.groep + ' · ' : '' ) + tx( 'tool_vraag_van', qi + 1, qn );
				balk.firstChild.style.width = Math.round( ( qi + 1 ) / qn * 100 ) + '%';
			} else {
				/* Bij contextvragen staat Context al in de stappen, dus geen opschriftregel; elders alleen een eigen groep. */
				voortgang.textContent = ( s.vraag && 'context' !== s.vraag.type && s.vraag.groep ) || '';
				balk.firstChild.style.width = s.vraag && qi < 0 ? '0%' : balk.firstChild.style.width;
			}
			/* Voor de eerste scorende vraag (context, intro) nog geen lege balk of lege regel. */
			var zonderVoortgang = ! s.groep && ! ( s.vraag && qi >= 0 );
			balk.hidden = zonderVoortgang;
			voortgang.hidden = zonderVoortgang && ! voortgang.textContent;
			legenda.hidden = true;
			if ( ! legenda.hidden ) {
				var sc = schaal( s.vraag );
				legenda.innerHTML = '';
				Object.keys( sc ).sort().forEach( function ( k ) { legenda.appendChild( el( 'span', { text: tx( 'tool_label_legenda', k, sc[ k ] ) } ) ); } );
			}
			vorige.hidden = 0 === i;
			var actief = heeftContext && s.vraag && 'context' === s.vraag.type ? 0 : ( heeftContext ? 1 : 0 );
			zetStap( actief );
			/* De focus naar de vraag zelf (niet naar het eerste vakje, dat dan op een keuze lijkt); bij een open vraag naar het veld. */
			var focus = s.el.querySelector( 'textarea' ) || s.el.querySelector( '.sfp-tool__vraag' ) || s.el.querySelector( 'input,button' );
			if ( focus && document.activeElement && app.contains( document.activeElement ) ) { focus.focus( { preventScroll: true } ); }
			tool.zeg( ( s.vraag && s.vraag.tekst ) || '' );
		}
		function zetStap( i ) {
			stapItems.forEach( function ( li, k ) { li.classList.toggle( 'is-actief', k === i ); li.setAttribute( 'aria-selected', k === i ? 'true' : 'false' ); li.tabIndex = k === i ? 0 : -1; } );
		}
		function naarStap( i ) {
			var laatste = i === stapNamen.length - 1;
			if ( laatste ) { probeerResultaat( true ); return; }
			paneelResultaat.hidden = true; paneelVragen.hidden = false;
			if ( perVraag ) {
				var doel = 0;
				if ( heeftContext && i > 0 ) { for ( var k = 0; k < schermen.length; k++ ) { if ( schermen[ k ].vraag && 'context' !== schermen[ k ].vraag.type ) { doel = k; break; } } }
				ga( doel );
			} else { zetStap( i ); }
		}
		function probeerResultaat( stil ) {
			var r = reken( b, antwoorden );
			if ( ! r.compleet ) {
				var n = r.ontbreekt.length;
				paneelResultaat.hidden = true; paneelVragen.hidden = false;
				if ( perVraag ) {
					var eerste = -1;
					for ( var k = 0; k < schermen.length && eerste < 0; k++ ) { var vs2 = schermen[ k ].vragen || ( schermen[ k ].vraag ? [ schermen[ k ].vraag ] : [] ); for ( var m2 = 0; m2 < vs2.length; m2++ ) { if ( r.ontbreekt.indexOf( vs2[ m2 ].id ) >= 0 ) { eerste = k; break; } } }
					if ( eerste >= 0 ) { ga( eerste ); }
				}
				if ( ! stil || ! perVraag ) { toonMelding( tx( 'tool_ontbreekt', n ), true ); }
				else { toonMelding( tx( 'tool_resultaat_slot', scorend ), false ); }
				return;
			}
			toonResultaat( r );
		}

		/* Resultaat */
		var mailbox = null;
		function toonResultaat( r ) {
			paneelVragen.hidden = true;
			paneelResultaat.hidden = false;
			paneelResultaat.innerHTML = '';
			zetStap( stapNamen.length - 1 );
			stapItems[ stapNamen.length - 1 ].classList.remove( 'is-slot' );
			var u = uitslagtekst( b, r.uitslag );
			var blokken = [];
			if ( eigen.resultaat_kop ) { paneelResultaat.appendChild( el( 'h2', { class: 'sfp-tool__kop sfp-tool__kop--resultaat', text: eigen.resultaat_kop } ) ); blokken.push( { type: 'subkop', tekst: eigen.resultaat_kop } ); }
			b.uitvoer = ( b.uitvoer && b.uitvoer.length ) ? b.uitvoer : [ { type: 'profiel' }, { type: 'balken' } ];
			b.uitvoer.forEach( function ( o ) {
				o = 'string' === typeof o ? { type: o } : o;
				var kop = o.kop ? vul( o.kop, b, r ) : '';
				switch ( o.type ) {
					case 'profiel':
						var ps = r.uitslag, pm = o.bron && o.bron.match( /^uitslag\.top\[(\d+)\]$/ );
						if ( pm ) { ps = r.volgorde[ parseInt( pm[ 1 ], 10 ) - 1 ] || ''; }
						var pu = pm ? uitslagtekst( b, ps ) : u;
						var wrap = el( 'div', { class: 'sfp-tool__profiel' + ( pm ? ' sfp-tool__profiel--tweede' : '' ) } );
						var pk = kop || ( pu.kop ? vul( pu.kop, b, r ) : '' );
						if ( pk ) { wrap.appendChild( el( 'p', { class: 'sfp-tool__kicker', text: pk } ) ); blokken.push( { type: 'kicker', tekst: pk } ); }
						if ( pu.titel ) { wrap.appendChild( el( pm ? 'h3' : 'h2', { class: pm ? 'sfp-tool__titel sfp-tool__titel--klein' : 'sfp-tool__titel', text: vul( pu.titel, b, r ) } ) ); blokken.push( { type: pm ? 'subkop' : 'kop', tekst: vul( pu.titel, b, r ) } ); }
						alineas( vul( pu.tekst, b, r ) ).forEach( function ( a ) { wrap.appendChild( el( 'p', { class: 'sfp-tool__tekst', text: a } ) ); } );
						if ( pu.tekst ) { blokken.push( { type: 'tekst', tekst: vul( pu.tekst, b, r ) } ); }
						if ( ! pm && r.gelijkspel && b.uitkomst && b.uitkomst.gelijkspel_tekst ) { wrap.appendChild( el( 'p', { class: 'sfp-tool__tekst sfp-tool__tekst--klein', text: b.uitkomst.gelijkspel_tekst } ) ); blokken.push( { type: 'tekst', tekst: b.uitkomst.gelijkspel_tekst } ); }
						paneelResultaat.appendChild( wrap );
						break;
					case 'vast':
						if ( kop ) { paneelResultaat.appendChild( el( 'h3', { class: 'sfp-tool__kop', text: kop } ) ); blokken.push( { type: 'subkop', tekst: kop } ); }
						if ( o.tekst ) { alineas( vul( o.tekst, b, r ) ).forEach( function ( a ) { paneelResultaat.appendChild( el( 'p', { class: 'sfp-tool__tekst', text: a } ) ); } ); blokken.push( { type: 'tekst', tekst: vul( o.tekst, b, r ) } ); }
						if ( Array.isArray( o.lijst ) && o.lijst.length ) { var vl = el( 'ul', { class: 'sfp-tool__lijst' } ); o.lijst.forEach( function ( it ) { vl.appendChild( el( 'li', { text: item( it ) } ) ); } ); paneelResultaat.appendChild( vl ); blokken.push( { type: 'lijst', items: o.lijst.map( item ) } ); }
						break;
					case 'totaal':
						var tt = ( kop || tx( 'tool_scores' ) ) + ': ' + getal( r.totaal ) + ( r.totaal_max > 0 ? ' / ' + getal( r.totaal_max ) : '' );
						paneelResultaat.appendChild( el( 'p', { class: 'sfp-tool__totaal', text: tt } ) );
						blokken.push( { type: 'tekst', tekst: tt } );
						break;
					case 'alle':
						var keuze = selectie( r, o.selectie || 'alle', parseInt( o.aantal, 10 ) || 0, !! o.zonder_hoogste );
						if ( kop ) { paneelResultaat.appendChild( el( 'h3', { class: 'sfp-tool__kop', text: kop } ) ); blokken.push( { type: 'subkop', tekst: kop } ); }
						if ( ! keuze.length && o.leeg ) { paneelResultaat.appendChild( el( 'p', { class: 'sfp-tool__tekst', text: o.leeg } ) ); blokken.push( { type: 'tekst', tekst: o.leeg } ); }
						keuze.forEach( function ( d, i ) {
							var ut = uitslagtekst( b, d );
							var naam = ut.titel || dimVolledig( b, d );
							var kaart = el( 'div', { class: 'sfp-tool__kaart' + ( d === r.uitslag ? ' is-uitslag' : '' ) } );
							if ( Array.isArray( o.koppen ) && o.koppen[ i ] ) { kaart.appendChild( el( 'p', { class: 'sfp-tool__kicker', text: o.koppen[ i ] } ) ); blokken.push( { type: 'kicker', tekst: o.koppen[ i ] } ); }
							kaart.appendChild( el( 'h4', { text: naam + ( d === r.uitslag && o.markeer ? ' ' + o.markeer : '' ) } ) );
							blokken.push( { type: 'subkop', tekst: naam + ( d === r.uitslag && o.markeer ? ' ' + o.markeer : '' ) } );
							if ( ut.tekst ) { alineas( vul( ut.tekst, b, r ) ).forEach( function ( a ) { kaart.appendChild( el( 'p', { class: 'sfp-tool__tekst', text: a } ) ); } ); blokken.push( { type: 'tekst', tekst: vul( ut.tekst, b, r ) } ); }
							( Array.isArray( o.lijsten ) ? o.lijsten : [] ).forEach( function ( ln ) {
								var items = Array.isArray( ut.lijsten[ ln ] ) ? ut.lijsten[ ln ] : [];
								if ( ! items.length ) { return; }
								var ul3 = el( 'ul', { class: 'sfp-tool__lijst' } );
								items.forEach( function ( it ) { ul3.appendChild( el( 'li', { text: item( it ) } ) ); } );
								kaart.appendChild( ul3 );
								blokken.push( { type: 'lijst', items: items.map( item ) } );
							} );
							paneelResultaat.appendChild( kaart );
						} );
						break;
					case 'balken': case 'niveaus':
						var sec = el( 'section', { class: 'sfp-tool__balken' } );
						sec.appendChild( el( 'h3', { class: 'sfp-tool__kop', text: kop || tx( 'tool_scores' ) } ) );
						blokken.push( { type: 'subkop', tekst: kop || tx( 'tool_scores' ) } );
						var volg = 'vast' === o.sorteer ? dimLijst( b ) : r.volgorde;
						var hoogste = r.volgorde.length ? r.dimensies[ r.volgorde[ 0 ] ].waarde : 0;
						volg.forEach( function ( d ) {
							var dim = r.dimensies[ d ];
							var bereik = 'hoogste' === o.schaal ? hoogste - dim.min : dim.max - dim.min;
							var deel = bereik > 0 ? ( dim.waarde - dim.min ) / bereik : 0;
							var eenh = 'percentage' === scoreRegel( b ) ? '%' : ( dim.max > 0 ? ' / ' + getal( dim.max ) : '' );
							var lab = getal( dim.waarde ) + eenh + ( dim.label.tekst ? ' • ' + dim.label.tekst : '' );
							var rij = el( 'div', { class: 'sfp-tool__balkrij' + ( d === r.uitslag ? ' is-uitslag' : '' ) }, [
								el( 'div', { class: 'sfp-tool__balkkop' }, [ el( 'p', { class: 'sfp-tool__balknaam', text: dimVolledig( b, d ) } ), el( 'span', { text: lab } ) ] ),
								el( 'div', { class: 'sfp-tool__balk' }, [ el( 'div', { class: 'sfp-tool__balk-vul', style: 'width:' + Math.round( deel * 100 ) + '%' } ) ] )
							] );
							if ( 'niveaus' === o.type ) {
								var nt = uitslagtekst( b, d + ':' + dim.label.sleutel );
								if ( nt.tekst ) { rij.appendChild( el( 'p', { class: 'sfp-tool__tekst sfp-tool__tekst--klein', text: nt.tekst } ) ); }
							}
							sec.appendChild( rij );
							blokken.push( { type: 'balk', naam: dimVolledig( b, d ), label: lab, deel: deel, uitslag: d === r.uitslag } );
							if ( 'niveaus' === o.type ) { var nt2 = uitslagtekst( b, d + ':' + dim.label.sleutel ); if ( nt2.tekst ) { blokken.push( { type: 'tekst', tekst: nt2.tekst } ); } }
						} );
						paneelResultaat.appendChild( sec );
						break;
					case 'lijst': case 'afvinklijst':
						var lijst = bron( b, r, o.bron );
						if ( Array.isArray( lijst ) && lijst.length ) {
							if ( kop ) { paneelResultaat.appendChild( el( 'h3', { class: 'sfp-tool__kop', text: kop } ) ); blokken.push( { type: 'subkop', tekst: kop } ); }
							var ul = el( 'afvinklijst' === o.type ? 'ul' : 'ol', { class: 'sfp-tool__lijst' + ( 'afvinklijst' === o.type ? ' sfp-tool__lijst--afvink' : '' ) } );
							lijst.forEach( function ( it ) {
								ul.appendChild( 'afvinklijst' === o.type ? el( 'li', null, [ el( 'label', null, [ el( 'input', { type: 'checkbox' } ), el( 'span', { text: item( it ) } ) ] ) ] ) : el( 'li', { text: item( it ) } ) );
							} );
							paneelResultaat.appendChild( ul );
							blokken.push( { type: 'afvinklijst' === o.type ? 'afvink' : 'lijst', items: lijst.map( item ), nummers: 'lijst' === o.type } );
						}
						break;
					case 'tekst':
						var w = bron( b, r, o.bron );
						if ( 'string' === typeof w && w ) {
							if ( kop ) { paneelResultaat.appendChild( el( 'h3', { class: 'sfp-tool__kop', text: kop } ) ); blokken.push( { type: 'subkop', tekst: kop } ); }
							alineas( w ).forEach( function ( a ) { paneelResultaat.appendChild( el( 'p', { class: 'sfp-tool__tekst', text: a } ) ); } );
							blokken.push( { type: 'tekst', tekst: w } );
						}
						break;
					case 'laagste':
						var aantal = Math.max( 1, parseInt( o.aantal, 10 ) || 1 ), naam = o.lijst || '';
						var laag = r.volgorde.slice().reverse().slice( 0, aantal );
						if ( kop ) { paneelResultaat.appendChild( el( 'h3', { class: 'sfp-tool__kop', text: kop } ) ); blokken.push( { type: 'subkop', tekst: kop } ); }
						var ul2 = el( 'ul', { class: 'sfp-tool__lijst' } ), items2 = [];
						laag.forEach( function ( d ) { var ut = uitslagtekst( b, d ); var tip = ( Array.isArray( ut.lijsten[ naam ] ) && ut.lijsten[ naam ].length ) ? item( ut.lijsten[ naam ][ 0 ] ) : ''; var regel = dimVolledig( b, d ) + ( tip ? ': ' + tip : '' ); ul2.appendChild( el( 'li', null, [ el( 'b', { text: dimVolledig( b, d ) } ), tip ? ': ' + tip : '' ] ) ); items2.push( regel ); } );
						paneelResultaat.appendChild( ul2 );
						blokken.push( { type: 'lijst', items: items2 } );
						break;
					case 'link':
						var url = bron( b, r, o.bron || 'uitslag.artikel' );
						url = 'string' === typeof url ? veiligeUrl( url ) : '';
						if ( url ) { paneelResultaat.appendChild( el( 'p', null, [ el( 'a', { class: 'sfp-tool__link', href: url, text: kop || url } ) ] ) ); blokken.push( { type: 'tekst', tekst: ( kop ? kop + ': ' : '' ) + url } ); }
						break;
					case 'onderbouwing':
						var det = el( 'details', { class: 'sfp-tool__onderbouwing' } );
						det.appendChild( el( 'summary', { text: kop || tx( 'tool_onderbouwing' ) } ) );
						var tabel = el( 'table', { class: 'sfp-tool__tabel' } ), rijen = [];
						vragenVoor.forEach( function ( v ) {
							if ( ! r.bijdragen[ v.id ] ) { return; }
							var delen = Object.keys( r.bijdragen[ v.id ] ).map( function ( d ) { return dimNaam( b, d ) + ' ' + getal( r.bijdragen[ v.id ][ d ] ); } );
							tabel.appendChild( el( 'tr', null, [ el( 'td', { text: v.tekst } ), el( 'td', { text: delen.join( ', ' ) } ) ] ) );
							rijen.push( [ v.tekst, delen.join( ', ' ) ] );
						} );
						det.appendChild( tabel );
						paneelResultaat.appendChild( det );
						blokken.push( { type: 'subkop', tekst: kop || tx( 'tool_onderbouwing' ) } );
						blokken.push( { type: 'tabel', rijen: rijen } );
						break;
					case 'tabel':
						var rijen2 = bron( b, r, o.bron );
						if ( Array.isArray( rijen2 ) && rijen2.length ) {
							if ( kop ) { paneelResultaat.appendChild( el( 'h3', { class: 'sfp-tool__kop', text: kop } ) ); blokken.push( { type: 'subkop', tekst: kop } ); }
							var tb = el( 'table', { class: 'sfp-tool__tabel' } );
							rijen2.forEach( function ( rij ) { var cellen = Array.isArray( rij ) ? rij : [ rij ]; tb.appendChild( el( 'tr', null, cellen.map( function ( c ) { return el( 'td', { text: String( c ) } ); } ) ) ); } );
							paneelResultaat.appendChild( tb );
							blokken.push( { type: 'tabel', rijen: rijen2.map( function ( rij ) { return Array.isArray( rij ) ? rij.map( String ) : [ String( rij ) ]; } ) } );
						}
						break;
				}
			} );

			/* Vrije velden na de uitslag */
			tekstvragenNa.forEach( function ( v ) {
				var id = 'sfp-tool-' + tool.id + '-na-' + v.id;
				var veld = el( 'textarea', { class: 'sfp-tool__veld', id: id, rows: '2', maxlength: String( v.maxlengte || 320 ), placeholder: v.voorbeeld || '', oninput: function () { antwoorden[ v.id ] = this.value; bewaar(); } } );
				veld.value = antwoorden[ v.id ] || '';
				paneelResultaat.appendChild( el( 'div', { class: 'sfp-tool__na' }, [ el( 'label', { for: id, class: 'sfp-tool__label' }, [ v.label || v.tekst || '', v.hint ? el( 'span', { class: 'sfp-tool__hint', text: ' (' + v.hint + ')' } ) : el( 'span', { class: 'sfp-tool__hint', text: ' (' + tx( 'tool_tekst_hint' ) + ')' } ) ] ), veld ] ) );
			} );

			/* Acties */
			var acties = b.acties || {};
			var aan = function ( k ) { return acties[ k ] === undefined ? true : !! acties[ k ]; };
			var rij = el( 'div', { class: 'sfp-tool__acties sfp-tool__acties--uitslag' } );
			/* De knop op een eigen regel, de tekstlinks samen op de regel eronder. */
			var rijLinks = el( 'div', { class: 'sfp-tool__acties-links' } );
			var deelMelding = el( 'p', { class: 'sfp-tool__melding sfp-tool__melding--klein', hidden: true } );
			function naam() { var a = b.acties || {}; return a.pdf_bestandsnaam ? vul( a.pdf_bestandsnaam, b, r ).replace( /[\\\/:*?"<>|]+/g, '' ).trim().slice( 0, 80 ) || tool.bestandsnaam() : tool.bestandsnaam(); }
			function blokkenMet() {
				var extra = [];
				tekstvragenNa.forEach( function ( v ) { var w = ( antwoorden[ v.id ] || '' ).trim(); if ( w ) { extra.push( { type: 'subkop', tekst: v.label || v.tekst || '' } ); extra.push( { type: 'tekst', tekst: w } ); } } );
				return blokken.concat( extra );
			}
			function wacht( knop, p ) { var oud = knop.textContent; knop.disabled = true; knop.textContent = tx( 'tool_bezig' ) + '…'; return p.then( function () { knop.disabled = false; knop.textContent = oud; }, function ( e ) { knop.disabled = false; knop.textContent = oud; throw e; } ); }
			/* Lukt een pdf of Word-bestand niet (bijvoorbeeld geen verbinding), dan een melding; een volgende klik probeert het opnieuw. */
			function bestand( knop, p ) { deelMelding.hidden = true; wacht( knop, p ).catch( function () { deelMelding.textContent = tx( 'tool_bestand_fout' ); deelMelding.hidden = false; } ); }
			if ( aan( 'pdf' ) ) { rij.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: tx( 'tool_pdf' ), onclick: function () { bestand( this, tool.pdf( blokkenMet(), naam() ) ); } } ) ); }
			/* Eén knop (pdf); de rest zijn tekstlinks met een pijl, zoals de merkbron een tweede keus wil. */
			if ( aan( 'word' ) ) { rijLinks.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink', text: tx( 'tool_word' ), onclick: function () { bestand( this, tool.word( blokkenMet(), naam() ) ); } } ) ); }
			if ( aan( 'mail' ) ) { rijLinks.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink', text: tx( 'tool_mail' ), 'aria-expanded': 'false', onclick: function () { mailbox.hidden = ! mailbox.hidden; this.setAttribute( 'aria-expanded', mailbox.hidden ? 'false' : 'true' ); if ( ! mailbox.hidden ) { mailbox.querySelector( 'input[type=email]' ).focus(); } } } ) ); }
			if ( aan( 'deellink' ) ) { rijLinks.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink', text: tx( 'tool_deellink' ), onclick: function () { tool.deel( antwoorden, function ( m ) { deelMelding.textContent = m; deelMelding.hidden = false; setTimeout( function () { deelMelding.hidden = true; }, 6000 ); } ); } } ) ); }
			if ( rijLinks.children.length ) { rij.appendChild( rijLinks ); }
			paneelResultaat.appendChild( rij );
			paneelResultaat.appendChild( deelMelding );
			if ( aan( 'mail' ) ) {
				var email = el( 'input', { type: 'email', class: 'sfp-tool__veld', placeholder: tx( 'tool_mail_veld' ), 'aria-label': tx( 'tool_mail_veld' ), autocomplete: 'email', maxlength: '160', required: true } );
				var hp = el( 'input', { type: 'text', name: 'website', tabindex: '-1', autocomplete: 'off', class: 'sfp-tool__hp', 'aria-hidden': 'true' } );
				var mailMelding = el( 'p', { class: 'sfp-tool__melding sfp-tool__melding--klein', hidden: true } );
				var stuur = el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: tx( 'tool_mail_verstuur' ), onclick: function () {
					if ( ! email.value || ! email.checkValidity() ) { email.focus(); return; }
					var knop = this;
					wacht( knop, tool.mail( email.value.trim(), antwoorden, hp.value ).then( function ( res ) {
						mailMelding.textContent = res.ok ? tx( 'tool_mail_ok' ) : ( ( res.data && res.data.message ) || tx( 'tool_mail_fout' ) );
						mailMelding.classList.toggle( 'is-fout', ! res.ok );
						mailMelding.hidden = false;
					}, function () { mailMelding.textContent = tx( 'tool_mail_fout' ); mailMelding.classList.add( 'is-fout' ); mailMelding.hidden = false; } ) );
				} } );
				mailbox = el( 'div', { class: 'sfp-tool__mailbox', hidden: true }, [ el( 'div', { class: 'sfp-tool__mailrij' }, [ email, stuur ] ), hp, mailMelding ] );
				paneelResultaat.appendChild( mailbox );
			}
			/* Opnieuw beginnen is geen call-to-action: een rustige link zonder pijl, zoals Vorige. */
			paneelResultaat.appendChild( el( 'p', { class: 'sfp-tool__nav' }, [ el( 'button', { type: 'button', class: 'sfp-tool__opnieuw', text: tx( 'tool_opnieuw' ), onclick: opnieuw } ) ] ) );
			if ( aan( 'duimpjes' ) ) { paneelResultaat.appendChild( tool.duimWidget() ); }
			if ( tool.formulier ) { tool.formulier.hidden = false; }
			tool.zeg( ( u.kop ? u.kop + ' ' : '' ) + ( u.titel || '' ) );
			try { paneelResultaat.scrollIntoView( { block: 'start', behavior: 'smooth' } ); } catch ( e ) {}
			window.dispatchEvent( new CustomEvent( 'sfp-tool-resultaat', { detail: { tool: tool.id, slug: tool.slug, uitslag: r.uitslag } } ) );
		}

		function opnieuw() {
			antwoorden = {};
			tool.wisBewaard();
			Array.prototype.forEach.call( app.querySelectorAll( 'input[type=radio]' ), function ( i ) { i.checked = false; } );
			Array.prototype.forEach.call( app.querySelectorAll( 'textarea' ), function ( i ) { i.value = ''; } );
			paneelResultaat.hidden = true; paneelVragen.hidden = false;
			stapItems[ stapNamen.length - 1 ].classList.add( 'is-slot' );
			if ( tool.formulier ) { tool.formulier.hidden = true; }
			try { var url = window.location.pathname; window.history.replaceState( null, '', url ); } catch ( e ) {}
			if ( perVraag ) { ga( 0 ); } else { zetStap( 0 ); window.scrollTo( { top: app.getBoundingClientRect().top + window.pageYOffset - 80, behavior: 'smooth' } ); }
		}
		function bewaar() { tool.bewaar( { a: antwoorden, i: huidig } ); }
		/* Antwoorden uit een deellink of de browser: alleen bekende vragen, alleen tekst of getallen. */
		function vulIn( a ) {
			antwoorden = {};
			a = ( a && 'object' === typeof a ) ? a : {};
			vragen.forEach( function ( v ) { if ( Object.prototype.hasOwnProperty.call( a, v.id ) && antwoord( a[ v.id ] ) !== null ) { antwoorden[ v.id ] = antwoord( a[ v.id ] ); } } );
			Array.prototype.forEach.call( app.querySelectorAll( 'input[type=radio]' ), function ( rd ) {
				vragen.forEach( function ( v ) { if ( rd.name === 'sfp-tool-' + tool.id + '-' + v.id ) { rd.checked = antwoorden[ v.id ] !== undefined && rd.value === antwoorden[ v.id ]; } } );
			} );
			schermen.forEach( function ( s ) { if ( s.vraag && 'tekst' === s.vraag.type ) { var ta = s.el.querySelector( 'textarea' ); if ( ta ) { ta.value = antwoorden[ s.vraag.id ] || ''; } } } );
		}
		function eersteOpen() {
			for ( var k = 0; k < schermen.length; k++ ) {
				var vs = schermen[ k ].vragen || ( schermen[ k ].vraag ? [ schermen[ k ].vraag ] : [] );
				for ( var m = 0; m < vs.length; m++ ) { if ( 'tekst' !== vs[ m ].type && ( antwoorden[ vs[ m ].id ] === undefined || '' === antwoorden[ vs[ m ].id ] ) ) { return k; } }
			}
			return schermen.length - 1;
		}

		/* Toetsen: 1 tot 5 bij een Likert-vraag; de pijltjes kiezen binnen een
		   vraag zonder door te gaan, Enter gaat dan naar de volgende vraag. */
		var pijl = false;
		app.addEventListener( 'keydown', function ( e ) {
			pijl = /^Arrow/.test( e.key ) && 'radio' === e.target.type;
			if ( ! perVraag || paneelVragen.hidden ) { return; }
			var s = schermen[ huidig ];
			if ( ! s ) { return; }
			var tag = e.target.tagName;
			if ( 'Enter' === e.key && 'radio' === e.target.type && ! perGroep && s.vraag && antwoorden[ s.vraag.id ] !== undefined ) {
				e.preventDefault(); ga( huidig + 1 ); return;
			}
			if ( ! /^[1-5]$/.test( e.key ) || 'TEXTAREA' === tag || 'INPUT' === tag && 'radio' !== e.target.type ) { return; }
			/* Welke vraag: per groep de vraag met de focus, anders de eerste open Likert-vraag van de groep. */
			var doel = null;
			if ( s.groep ) {
				var bij = e.target.closest ? e.target.closest( '.sfp-tool__scherm[data-index]' ) : null;
				var q = bij ? vragenVoor[ parseInt( bij.getAttribute( 'data-index' ), 10 ) ] : null;
				if ( ! q || 'likert' !== q.type ) { q = null; s.vragen.some( function ( x ) { if ( 'likert' === x.type && antwoorden[ x.id ] === undefined ) { q = x; return true; } return false; } ); }
				doel = q ? s.el.querySelector( '.sfp-tool__scherm[data-index="' + vragenVoor.indexOf( q ) + '"]' ) : null;
			} else if ( s.vraag && 'likert' === s.vraag.type ) {
				doel = s.el;
			}
			var rd = doel ? doel.querySelector( 'input[type=radio][value="' + e.key + '"]' ) : null;
			if ( ! rd ) { return; }
			pijl = false; rd.checked = true; rd.dispatchEvent( new Event( 'change' ) ); e.preventDefault();
			/* Per groep schuift de focus door naar de volgende open vraag. */
			var volgende = null;
			if ( s.groep ) { s.vragen.some( function ( x ) { if ( 'likert' === x.type && antwoorden[ x.id ] === undefined ) { volgende = s.el.querySelector( '.sfp-tool__scherm[data-index="' + vragenVoor.indexOf( x ) + '"] input[type=radio]' ); return true; } return false; } ); }
			( volgende || rd ).focus( { preventScroll: ! volgende } );
		} );
		app.addEventListener( 'keyup', function () { pijl = false; } );
		app.addEventListener( 'pointerdown', function () { pijl = false; } );

		return {
			start: function () {
				var deel = tool.uitDeellink();
				if ( deel ) {
					vulIn( deel );
					var r = reken( b, antwoorden );
					if ( r.compleet ) { toonResultaat( r ); return; }
				}
				var bewaard = tool.bewaard();
				if ( bewaard && bewaard.a && Object.keys( bewaard.a ).length && ! deel ) {
					var vraag = el( 'div', { class: 'sfp-tool__scherm sfp-tool__scherm--verder' }, [
						el( 'p', { class: 'sfp-tool__vraag', text: tx( 'tool_verder' ) } ),
						el( 'div', { class: 'sfp-tool__acties' }, [
							el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: tx( 'tool_verder_ja' ), onclick: function () { schermenWrap.removeChild( vraag ); vulIn( bewaard.a ); if ( perVraag ) { ga( eersteOpen() ); } } } ),
							el( 'button', { type: 'button', class: 'sfp-tool__tekstlink', text: tx( 'tool_verder_nee' ), onclick: function () { schermenWrap.removeChild( vraag ); tool.wisBewaard(); if ( perVraag ) { ga( 0 ); } } } )
						] )
					] );
					schermenWrap.insertBefore( vraag, schermenWrap.firstChild );
					schermen.forEach( function ( s ) { s.el.hidden = true; } );
					voortgang.textContent = '';
					return;
				}
				if ( perVraag ) { ga( 0 ); }
			},
			reken: function ( a ) { return reken( b, a ); }
		};
	} );

	window.sfpToolMotor.rekenTest = reken;
} )();
