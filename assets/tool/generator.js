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


	/* ---------------------------------------------------------------- *
	 * De rekentaal (gelijk aan includes/reken.php)
	 * ---------------------------------------------------------------- */

	var FUNCTIES = [ 'min', 'max', 'abs', 'afronden', 'omlaag', 'omhoog', 'als', 'som', 'aantal', 'tel', 'bevat', 'woorden' ];
	var bomen = {};

	function delen( s ) {
		var uit = [], re = /\s*(?:(\d+(?:\.\d+)?)|"([^"]*)"|([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+)?)|(<=|>=|!=|[-+*\/()<>=,]))/y, m;
		s = String( s );
		re.lastIndex = 0;
		while ( re.lastIndex < s.length ) {
			var start = re.lastIndex;
			m = re.exec( s );
			if ( ! m ) { if ( '' === s.slice( start ).trim() ) { break; } return null; }
			if ( m[ 0 ].length === 0 ) { return null; }
			if ( undefined !== m[ 1 ] ) { uit.push( [ 'g', parseFloat( m[ 1 ] ) ] ); }
			else if ( undefined !== m[ 2 ] ) { uit.push( [ 't', m[ 2 ] ] ); }
			else if ( undefined !== m[ 3 ] ) { uit.push( [ 'en', 'of', 'niet' ].indexOf( m[ 3 ] ) >= 0 ? [ 'o', m[ 3 ] ] : [ 'n', m[ 3 ] ] ); }
			else { uit.push( [ 'o', m[ 4 ] ] ); }
			if ( re.lastIndex < s.length && '' === s.slice( re.lastIndex ).trim() ) { break; }
		}
		return uit;
	}
	function ontleed( d, p, niveau ) {
		function is( w ) { return p.i < d.length && 'o' === d[ p.i ][ 0 ] && d[ p.i ][ 1 ] === w; }
		var l, r, x, op;
		if ( 0 === niveau || 1 === niveau || 4 === niveau || 5 === niveau ) {
			var ops = { 0: [ 'of' ], 1: [ 'en' ], 4: [ '+', '-' ], 5: [ '*', '/' ] }[ niveau ];
			l = ontleed( d, p, niveau + 1 );
			while ( null !== l && p.i < d.length && 'o' === d[ p.i ][ 0 ] && ops.indexOf( d[ p.i ][ 1 ] ) >= 0 ) {
				op = d[ p.i ][ 1 ]; p.i++;
				r = ontleed( d, p, niveau + 1 );
				if ( null === r ) { return null; }
				l = [ 'op', op, l, r ];
			}
			return l;
		}
		if ( 2 === niveau ) {
			if ( is( 'niet' ) ) { p.i++; x = ontleed( d, p, 2 ); return null === x ? null : [ 'niet', x ]; }
			return ontleed( d, p, 3 );
		}
		if ( 3 === niveau ) {
			l = ontleed( d, p, 4 );
			if ( null !== l && p.i < d.length && 'o' === d[ p.i ][ 0 ] && [ '=', '!=', '<', '<=', '>', '>=' ].indexOf( d[ p.i ][ 1 ] ) >= 0 ) {
				op = d[ p.i ][ 1 ]; p.i++;
				r = ontleed( d, p, 4 );
				return null === r ? null : [ 'op', op, l, r ];
			}
			return l;
		}
		if ( 6 === niveau ) {
			if ( is( '-' ) ) { p.i++; x = ontleed( d, p, 6 ); return null === x ? null : [ 'neg', x ]; }
			return ontleed( d, p, 7 );
		}
		if ( p.i >= d.length ) { return null; }
		var t = d[ p.i ];
		if ( 'g' === t[ 0 ] || 't' === t[ 0 ] ) { p.i++; return [ 'w', t[ 1 ] ]; }
		if ( is( '(' ) ) {
			p.i++;
			x = ontleed( d, p, 0 );
			if ( null === x || ! is( ')' ) ) { return null; }
			p.i++;
			return x;
		}
		if ( 'n' === t[ 0 ] ) {
			p.i++;
			if ( is( '(' ) ) {
				if ( FUNCTIES.indexOf( t[ 1 ] ) < 0 ) { return null; }
				p.i++;
				var args = [];
				if ( ! is( ')' ) ) {
					while ( true ) {
						x = ontleed( d, p, 0 );
						if ( null === x ) { return null; }
						args.push( x );
						if ( is( ',' ) ) { p.i++; continue; }
						break;
					}
				}
				if ( ! is( ')' ) ) { return null; }
				p.i++;
				return [ 'f', t[ 1 ], args ];
			}
			return [ 'n', t[ 1 ] ];
		}
		return null;
	}
	function boom( s ) {
		s = String( s );
		if ( Object.prototype.hasOwnProperty.call( bomen, s ) ) { return bomen[ s ]; }
		var d = delen( s ), uit = null;
		if ( d && d.length ) { var p = { i: 0 }; uit = ontleed( d, p, 0 ); if ( p.i !== d.length ) { uit = null; } }
		bomen[ s ] = uit;
		return uit;
	}
	function rGetal( v ) {
		if ( 'boolean' === typeof v ) { return v ? 1 : 0; }
		if ( 'number' === typeof v ) { return isFinite( v ) ? v : null; }
		if ( 'string' === typeof v && /^\s*-?\d+(\.\d+)?\s*$/.test( v ) ) { return parseFloat( v ); }
		return null;
	}
	function rWaar( v ) {
		if ( Array.isArray( v ) ) { return v.length > 0; }
		if ( 'string' === typeof v ) { return '' !== v; }
		if ( 'number' === typeof v ) { return 0 !== v && ! isNaN( v ); }
		return !! v;
	}
	function afronden( x, dec ) {
		var f = Math.pow( 10, Math.max( 0, Math.min( 10, dec || 0 ) ) );
		var r = Math.floor( Math.abs( x ) * f + 0.5 + 1e-9 ) / f;
		return x < 0 && r > 0 ? -r : r;
	}
	function tekstGetal( n ) {
		var r = afronden( n, 6 );
		return 0 === r ? '0' : String( r );
	}
	function rTekst( v ) {
		if ( null === v || undefined === v ) { return ''; }
		if ( 'boolean' === typeof v ) { return v ? '1' : ''; }
		if ( 'number' === typeof v ) { return isFinite( v ) ? tekstGetal( v ) : ''; }
		if ( Array.isArray( v ) ) { return v.map( rTekst ).join( ', ' ); }
		return String( v );
	}
	function plat( args ) {
		var uit = [];
		args.forEach( function ( a ) { ( Array.isArray( a ) ? a : [ a ] ).forEach( function ( x ) { var n = rGetal( x ); if ( null !== n ) { uit.push( n ); } } ); } );
		return uit;
	}
	function telWoorden( s ) {
		return String( s ).split( /[ \t\r\n\f\x0B\u00A0]+/ ).filter( function ( w ) { return /[\p{L}\p{N}]/u.test( w ); } ).length;
	}
	function uitrekenen( bm, naam ) {
		var l, r, ln, rn;
		switch ( bm[ 0 ] ) {
			case 'w': return bm[ 1 ];
			case 'n': return naam( bm[ 1 ] );
			case 'neg': l = rGetal( uitrekenen( bm[ 1 ], naam ) ); return null === l ? null : -l;
			case 'niet': return ! rWaar( uitrekenen( bm[ 1 ], naam ) );
			case 'op':
				l = uitrekenen( bm[ 2 ], naam );
				if ( 'en' === bm[ 1 ] ) { return rWaar( l ) && rWaar( uitrekenen( bm[ 3 ], naam ) ); }
				if ( 'of' === bm[ 1 ] ) { return rWaar( l ) || rWaar( uitrekenen( bm[ 3 ], naam ) ); }
				r = uitrekenen( bm[ 3 ], naam );
				ln = rGetal( l ); rn = rGetal( r );
				if ( '=' === bm[ 1 ] || '!=' === bm[ 1 ] ) {
					var gelijk = ( null !== ln && null !== rn ) ? ln === rn : rTekst( l ) === rTekst( r );
					return '=' === bm[ 1 ] ? gelijk : ! gelijk;
				}
				if ( null === ln || null === rn ) { return [ '<', '<=', '>', '>=' ].indexOf( bm[ 1 ] ) >= 0 ? false : null; }
				switch ( bm[ 1 ] ) {
					case '+': return ln + rn;
					case '-': return ln - rn;
					case '*': return ln * rn;
					case '/': return 0 === rn ? null : ln / rn;
					case '<': return ln < rn;
					case '<=': return ln <= rn;
					case '>': return ln > rn;
					case '>=': return ln >= rn;
				}
				return null;
			case 'f': return functie( bm[ 1 ], bm[ 2 ].map( function ( x ) { return uitrekenen( x, naam ); } ) );
		}
		return null;
	}
	function functie( f, args ) {
		function n( k ) { return k < args.length ? rGetal( args[ k ] ) : null; }
		var g, x, w;
		switch ( f ) {
			case 'min': case 'max': g = plat( args ); return g.length ? Math[ f ].apply( null, g ) : null;
			case 'abs': return null === n( 0 ) ? null : Math.abs( n( 0 ) );
			case 'afronden': return null === n( 0 ) ? null : afronden( n( 0 ), null === n( 1 ) ? 0 : Math.trunc( n( 1 ) ) );
			case 'omlaag': return null === n( 0 ) ? null : Math.floor( n( 0 ) );
			case 'omhoog': return null === n( 0 ) ? null : Math.ceil( n( 0 ) );
			case 'als': return rWaar( args.length > 0 ? args[ 0 ] : null ) ? ( args.length > 1 ? args[ 1 ] : null ) : ( args.length > 2 ? args[ 2 ] : null );
			case 'som': return plat( args ).reduce( function ( t, y ) { return t + y; }, 0 );
			case 'aantal':
				x = args.length ? args[ 0 ] : null;
				if ( Array.isArray( x ) ) { return x.length; }
				if ( 'string' === typeof x ) { return x.split( /\r?\n/ ).filter( function ( r ) { return '' !== r.trim(); } ).length; }
				return null === x || undefined === x ? 0 : 1;
			case 'tel':
				var van = n( 1 ), tot = n( 2 );
				return plat( [ args.length ? args[ 0 ] : null ] ).filter( function ( y ) { return ( null === van || y >= van ) && ( null === tot || y <= tot ); } ).length;
			case 'bevat':
				x = args.length ? args[ 0 ] : null; w = rTekst( args.length > 1 ? args[ 1 ] : null );
				if ( Array.isArray( x ) ) { return x.map( rTekst ).indexOf( w ) >= 0; }
				return '' !== w && rTekst( x ).indexOf( w ) >= 0;
			case 'woorden': return telWoorden( rTekst( args.length ? args[ 0 ] : null ) );
		}
		return null;
	}
	function opmaak( n, dec ) {
		dec = Math.max( 0, Math.min( 6, dec || 0 ) );
		var r = afronden( n, dec );
		var tekens = Array.from( String( t( 'tool_getal_scheiding' ) ) );
		var komma = tekens[ 0 ] || ',', punt = tekens[ 1 ] || '.';
		var s = Math.abs( r ).toFixed( dec ).split( '.' );
		var heel = s[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, punt );
		return ( r < 0 ? '-' : '' ) + heel + ( dec > 0 ? komma + s[ 1 ] : '' );
	}
	var FORMATEN = [ 'getal', 'euro', 'procent', 'teken', 'minsec', 'klok' ];
	function formaat( soort, arg, w ) {
		var n = rGetal( w );
		if ( null === n ) { return w; }
		var kaal = '~' === String( arg ).charAt( 0 );
		if ( kaal ) { arg = String( arg ).slice( 1 ); }
		var dec = '' !== arg && ! isNaN( Number( arg ) ) ? Math.trunc( Number( arg ) ) : 0;
		var x;
		switch ( soort ) {
			case 'getal':
				x = opmaak( n, dec );
				if ( kaal && dec > 0 ) { var komma = Array.from( String( t( 'tool_getal_scheiding' ) ) )[ 0 ] || ','; var dl = x.split( komma ); if ( 2 === dl.length ) { var st = dl[ 1 ].replace( /0+$/, '' ); x = dl[ 0 ] + ( st ? komma + st : '' ); } }
				return x;
			case 'euro': return t( 'tool_euro', opmaak( n, dec ) );
			case 'procent': return opmaak( n, dec ) + '%';
			case 'teken': return ( afronden( n, dec ) > 0 ? '+' : '' ) + opmaak( n, dec );
			case 'minsec': case 'klok': x = Math.max( 0, afronden( n, 0 ) ); return Math.floor( x / 60 ) + ':' + ( '0' + ( x % 60 ) ).slice( -2 );
		}
		return w;
	}

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

	/* De getallen uit een veld getallen, binnen min en max. */
	function getallenVan( v, w ) {
		return String( w ).split( /[ \t\r\n;,]+/ ).filter( function ( d ) { return /^-?\d+(\.\d+)?$/.test( d ); } ).map( parseFloat ).filter( function ( n ) {
			return ! ( ( undefined !== v.min && n < v.min ) || ( undefined !== v.max && n > v.max ) );
		} );
	}
	function extraVan( ctx ) { return ctx && ctx.extra ? ctx.extra : null; }
	function reken( b, a, formule, ctx ) {
		var bm = boom( formule );
		if ( null === bm ) { return null; }
		return uitrekenen( bm, function ( naam ) { return waarde( b, a, naam, ctx ); } );
	}
	/* De waarde van een naam in een formule. */
	function waarde( b, a, naam, ctx ) {
		var diepte = ctx && ctx.diepte ? ctx.diepte : 0;
		if ( diepte > 20 ) { return null; }
		var extra = extraVan( ctx );
		if ( extra && heeft( extra, naam ) ) { return extra[ naam ]; }
		var item = ctx && ctx.item ? ctx.item : null;
		if ( item && [ 'nr', 'groep', 'groepen' ].indexOf( naam ) >= 0 && undefined !== item[ naam ] && null !== item[ naam ] ) { return Number( item[ naam ] ); }
		var vv = velden( b );
		var v, w;
		if ( naam.indexOf( '.' ) >= 0 ) {
			var voor = naam.slice( 0, naam.indexOf( '.' ) ), na = naam.slice( naam.indexOf( '.' ) + 1 );
			if ( 'item' === voor ) {
				var obj = item && item.obj && 'object' === typeof item.obj ? item.obj : {};
				if ( ! heeft( obj, na ) ) { return null; }
				var x = obj[ na ];
				return ( null === x || [ 'string', 'number', 'boolean' ].indexOf( typeof x ) >= 0 || Array.isArray( x ) ) ? x : null;
			}
			if ( ! vv[ voor ] ) { return null; }
			v = vv[ voor ]; w = antwoordVan( b, a, voor );
			var som = null;
			( Array.isArray( w ) ? w : [ w ] ).forEach( function ( y ) { var o = optie( v, y ); if ( o && o.getallen && heeft( o.getallen, na ) ) { som = ( null === som ? 0 : som ) + Number( o.getallen[ na ] ); } } );
			return som;
		}
		if ( vv[ naam ] ) {
			v = vv[ naam ]; w = antwoordVan( b, a, naam );
			if ( 'getal' === v.type ) { return rGetal( w ); }
			if ( 'getallen' === v.type ) { return getallenVan( v, w ); }
			return w;
		}
		var rk = ( b.reken || [] ).filter( function ( x ) { return x.id === naam; } )[ 0 ];
		if ( rk ) { return reken( b, a, rk.formule, { item: item, diepte: diepte + 1, extra: extra } ); }
		if ( 'oordeel' === naam || 'oordeel_tekst' === naam ) { var o2 = oordeel( b, a, ctx ); return o2 ? ( 'oordeel' === naam ? o2.sleutel : o2.tekst ) : ''; }
		if ( heeft( b.waarden, naam ) ) { return vul( b.waarden[ naam ], b, a, { item: item, diepte: diepte + 1, extra: extra } ); }
		return null;
	}
	function oordeel( b, a, ctx ) {
		if ( ! b.oordeel ) { return null; }
		var diepte = ctx && ctx.diepte ? ctx.diepte : 0;
		var n = rGetal( reken( b, a, b.oordeel.op, { diepte: diepte + 1, extra: extraVan( ctx ) } ) );
		if ( null === n ) { return null; }
		var uit = null;
		( b.oordeel.labels || [] ).some( function ( l ) { if ( n >= l.vanaf ) { uit = l; return true; } return false; } );
		return uit;
	}
	function vinkjes( a ) {
		var w = a && Array.isArray( a._vink ) ? a._vink : [];
		return w.filter( function ( x ) { return 'string' === typeof x; } );
	}
	/* De kaarten van een sectie kaarten. */
	function kaarten( b, a, s, ctx ) {
		var extra = extraVan( ctx );
		var alle = [];
		if ( s.bank ) {
			var knoop = heeft( b.banken, s.bank ) ? b.banken[ s.bank ] : null;
			( s.pad || [] ).forEach( function ( p ) { var k = vul( p, b, a, ctx ); knoop = knoop && 'object' === typeof knoop && ! Array.isArray( knoop ) && heeft( knoop, k ) ? knoop[ k ] : null; } );
			if ( Array.isArray( knoop ) ) {
				knoop.forEach( function ( x ) {
					if ( 'string' === typeof x ) { alle.push( { tekst: x } ); }
					else if ( x && 'object' === typeof x && ! Array.isArray( x ) ) { alle.push( x ); }
				} );
			}
		}
		var aantal = null;
		if ( undefined !== s.aantal && '' !== s.aantal ) {
			var n = rGetal( reken( b, a, s.aantal, ctx ) );
			aantal = null === n ? 0 : Math.max( 0, Math.min( 200, Math.floor( n ) ) );
		}
		function test( f, obj ) { return rWaar( reken( b, a, f, { item: { obj: obj }, extra: extra } ) ); }
		if ( s.om_en_om ) {
			var w = antwoordVan( b, a, s.om_en_om.veld );
			var cats = Array.isArray( w ) ? w : ( isLeeg( w ) ? [] : [ w ] );
			var per = {};
			cats.forEach( function ( c ) { per[ c ] = alle.filter( function ( x ) { var y = x[ s.om_en_om.sleutel ]; return null !== y && undefined !== y && 'object' !== typeof y && String( y ) === String( c ); } ); } );
			var uit = [];
			for ( var k = 0; cats.length && k < ( aantal || 0 ); k++ ) {
				var lijst = per[ cats[ k % cats.length ] ];
				if ( lijst.length ) { uit.push( lijst[ Math.floor( k / cats.length ) % lijst.length ] ); }
			}
			return uit;
		}
		if ( ! s.bank ) { var leeg = []; for ( var j = 0; j < ( aantal || 0 ); j++ ) { leeg.push( {} ); } return leeg; }
		var l = alle;
		if ( s.filter ) { l = l.filter( function ( x ) { return test( s.filter, x ); } ); }
		if ( s.voorkeur ) { var vk = l.filter( function ( x ) { return test( s.voorkeur.formule, x ); } ); if ( vk.length >= s.voorkeur.minimaal ) { l = vk; } }
		if ( ! l.length && s.terugval ) {
			l = s.terugval.filter ? alle.filter( function ( x ) { return test( s.terugval.filter, x ); } ) : alle.slice();
			if ( s.terugval.aantal > 0 ) { l = l.slice( 0, s.terugval.aantal ); }
		}
		if ( s.sorteer ) {
			var sl = l.map( function ( x, i ) { var g = rGetal( reken( b, a, s.sorteer, { item: { obj: x }, extra: extra } ) ); return [ null === g ? Infinity : g, i ]; } );
			var volgorde = l.map( function ( x, i ) { return i; } );
			volgorde.sort( function ( x, y ) {
				var kx = sl[ x ][ 0 ], ky = sl[ y ][ 0 ], c;
				if ( Infinity === kx || Infinity === ky ) { c = ( Infinity === kx ? 1 : 0 ) - ( Infinity === ky ? 1 : 0 ); }
				else { c = kx < ky ? -1 : ( kx > ky ? 1 : 0 ); if ( s.aflopend ) { c = -c; } }
				return c || ( x - y );
			} );
			l = volgorde.map( function ( i ) { return l[ i ]; } );
		}
		if ( null !== aantal ) { l = l.slice( 0, aantal ); }
		return l;
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
		if ( '=' === naam.charAt( 0 ) ) { return rTekst( reken( b, a, naam.slice( 1 ), ctx ) ); }
		var extra = extraVan( ctx );
		if ( extra && heeft( extra, naam ) ) { return rTekst( extra[ naam ] ); }
		if ( velden( b )[ naam ] ) { return antwoordVan( b, a, naam ); }
		if ( heeft( b.waarden, naam ) ) {
			var diepte = ctx && ctx.diepte ? ctx.diepte : 0;
			if ( diepte > 4 ) { return ''; }
			return vul( b.waarden[ naam ], b, a, { item: ctx ? ctx.item : null, diepte: diepte + 1, extra: extra } );
		}
		return rTekst( waarde( b, a, naam, ctx ) );
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
		if ( FORMATEN.indexOf( soort ) >= 0 ) { return formaat( soort, arg, w ); }
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
		return String( sjabloon || '' ).replace( /\{(@?[a-zA-Z0-9_.]*|=[^{}|]+)((?:\|[^{}|]+)*)\}/g, function ( m, naam, filters ) {
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
		if ( heeft( als, 'formule' ) && [ 'string', 'number', 'boolean' ].indexOf( typeof als.formule ) >= 0 ) { return rWaar( reken( b, a, String( als.formule ), ctx ) ); }
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
	function items( b, a, s, ctx, ids, pad ) {
		var uit = [];
		ids = ids || [];
		pad = pad || '';
		var nr = 0;
		var elk = function ( w ) { if ( ! s.elk ) { return w; } String( s.elk ).split( '|' ).forEach( function ( f ) { w = String( filter( b, a, w, '', f, ctx ) ); } ); return w; };
		var voeg = function ( x ) {
			var id = pad + '.' + nr;
			nr++;
			if ( x && 'object' === typeof x && ! Array.isArray( x ) ) {
				if ( ! klopt( b, a, x.als, ctx ) ) { return; }
				if ( null !== x.id && undefined !== x.id && 'object' !== typeof x.id && '' !== String( x.id ) ) { id = String( x.id ); }
				x = x.tekst;
			}
			if ( 'string' !== typeof x ) { return; }
			var w = vul( x, b, a, ctx ).trim();
			if ( '' !== w ) { uit.push( elk( w ) ); ids.push( id ); }
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
				if ( '' !== r ) { uit.push( ( s.voor ? vul( s.voor, b, a, ctx ) : '' ) + elk( r ) ); ids.push( pad + '.r' + uit.length ); }
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
			if ( 'kaarten' === type ) {
				var kl = kaarten( b, a, s, ctx );
				if ( ! kl.length ) {
					if ( s.leeg ) { if ( kop ) { blokken.push( { type: 'subkop', tekst: kop } ); } blokken.push( { type: 'tekst', tekst: vul( s.leeg, b, a, ctx ) } ); }
					return;
				}
				if ( kop ) { blokken.push( { type: 'subkop', tekst: kop } ); }
				var per = 0;
				if ( s.groeperen ) { var gn = rGetal( reken( b, a, s.groeperen.per, ctx ) ); per = null === gn ? 0 : Math.max( 0, Math.floor( gn ) ); }
				var groepen = per > 0 ? Math.ceil( kl.length / per ) : 0;
				kl.forEach( function ( obj, k ) {
					var c = { item: { obj: obj, waarde: String( k + 1 ), optie: null, nr: k + 1, groep: per > 0 ? Math.floor( k / per ) + 1 : 0, groepen: groepen }, extra: extraVan( ctx ) };
					if ( per > 0 && 0 === k % per ) { var gk = vul( s.groeperen.kop, b, a, c ).trim(); if ( gk ) { blokken.push( { type: 'subkop', tekst: gk } ); } }
					if ( s.kaartkop ) { var kk = vul( s.kaartkop, b, a, c ).trim(); if ( kk ) { blokken.push( { type: 'subkop', tekst: kk } ); } }
					( s.inhoud || [] ).forEach( function ( x, j ) { sectie( x, c, pad + '.' + ( k + 1 ) + '.' + j ); } );
				} );
				return;
			}
			if ( 'getal' === type ) {
				var gw = reken( b, a, s.waarde || '', ctx );
				var gt = rTekst( gw );
				if ( '' === gt || Array.isArray( gw ) ) { return; }
				( s.formaat ? String( s.formaat ).split( '|' ) : [] ).forEach( function ( f ) { var y = filter( b, a, gt, '', f, ctx ); gt = Array.isArray( y ) ? y.join( ', ' ) : String( y ); } );
				if ( kop ) { blokken.push( { type: 'subkop', tekst: kop } ); }
				blokken.push( { type: 'getal', label: vul( s.label || '', b, a, ctx ).trim(), tekst: gt, onder: vul( s.onder || '', b, a, ctx ).trim() } );
				return;
			}
			if ( 'verdeling' === type ) {
				var dl = [], totaal = 0;
				( s.delen || [] ).forEach( function ( d ) {
					var dn = rGetal( reken( b, a, d.waarde, ctx ) );
					dn = null === dn ? 0 : Math.max( 0, dn );
					totaal += dn;
					dl.push( [ vul( d.label, b, a, ctx ).trim(), dn ] );
				} );
				if ( totaal <= 0 ) { return; }
				var rij = dl.map( function ( d ) {
					var dt = rTekst( d[ 1 ] );
					( s.formaat ? String( s.formaat ).split( '|' ) : [] ).forEach( function ( f ) { dt = String( filter( b, a, dt, '', f, ctx ) ); } );
					return [ d[ 0 ], dt, afronden( d[ 1 ] / totaal * 100, 0 ) ];
				} );
				if ( kop ) { blokken.push( { type: 'subkop', tekst: kop } ); }
				blokken.push( { type: 'verdeling', rijen: rij } );
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
			var ids = [];
			var lijst = items( b, a, s, ctx, ids, pad );
			if ( ! lijst.length ) {
				if ( s.leeg ) { if ( kop ) { blokken.push( { type: 'subkop', tekst: kop } ); } blokken.push( { type: 'tekst', tekst: vul( s.leeg, b, a, ctx ) } ); }
				return;
			}
			if ( kop ) { blokken.push( { type: 'subkop', tekst: kop } ); }
			if ( 'afvink' === type ) { var gezet = vinkjes( a ); blokken.push( { type: 'afvink', items: lijst, ids: ids, aan: ids.map( function ( id ) { return gezet.indexOf( id ) >= 0; } ) } ); return; }
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
		var index = ( b.stappen || [] ).indexOf( stap );
		( b.controles || [] ).forEach( function ( c, i ) {
			if ( index >= 0 && c.stap === index + 1 && rWaar( reken( b, a, c.formule, null ) ) ) { uit.push( '!' + i ); }
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
			else if ( 'getal' === bl.type ) { r.push( ( bl.label ? bl.label + ': ' : '' ) + bl.tekst ); if ( bl.onder ) { r.push( bl.onder ); } r.push( '' ); }
			else if ( 'verdeling' === bl.type ) { bl.rijen.forEach( function ( x ) { r.push( x[ 0 ] + ': ' + x[ 1 ] + ' (' + x[ 2 ] + '%)' ); } ); r.push( '' ); }
			else {
				bl.items.forEach( function ( it, i ) {
					r.push( ( 'afvink' === bl.type ? ( bl.aan && bl.aan[ i ] ? '[x] ' : '[ ] ' ) : ( bl.nummers ? ( i + 1 ) + '. ' : '- ' ) ) + it );
					var aw = 'invul' === bl.type && antw ? antw[ bl.sleutel + '.' + i ] : '';
					if ( aw ) { r.push( '   ' + aw ); }
				} );
				r.push( '' );
			}
		} );
		return r.join( '\n' ).replace( /\n{3,}/g, '\n\n' ).trim() + '\n';
	}

	/* Webadressen in een tekst aanklikbaar maken (alleen https en http). */
	function metLinks( r ) {
		var uit = [], pos = 0, re = /https?:\/\/[^\s<>"]+[^\s<>".,;:!?)]/g, m;
		while ( ( m = re.exec( r ) ) ) {
			if ( m.index > pos ) { uit.push( r.slice( pos, m.index ) ); }
			uit.push( el( 'a', { href: m[ 0 ], text: m[ 0 ], rel: 'noopener', target: '_blank' } ) );
			pos = m.index + m[ 0 ].length;
		}
		if ( pos < r.length ) { uit.push( r.slice( pos ) ); }
		return uit.length ? uit : [ r ];
	}

	/* Iconen voor tegels in de kaartweergave (3.21.0): lijntekeningen in de kleur van de tekst eromheen. */
	var ICONEN = {
		info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/>',
		lijst: '<path d="M5 6h14M5 12h10M5 18h6"/>',
		vinkje: '<path d="M4 12l5 5L20 6"/>',
		pijl: '<path d="M5 12h12M13 6l6 6-6 6"/>',
		ster: '<path d="M12 3l2.6 5.6 6 .7-4.5 4.1 1.2 6L12 16.4 6.7 19.4l1.2-6L3.4 9.3l6-.7z"/>',
		duo: '<circle cx="8" cy="9" r="3"/><circle cx="16" cy="9" r="3"/><path d="M3 19c.8-3 2.8-4.5 5-4.5s4.2 1.5 5 4.5M11 19c.8-3 2.8-4.5 5-4.5s4.2 1.5 5 4.5"/>',
		persoon: '<circle cx="12" cy="7" r="3"/><path d="M6 20c.8-3.5 3.2-5.5 6-5.5s5.2 2 6 5.5M9 3.5l3-1.5 3 1.5"/>',
		groep: '<circle cx="12" cy="6" r="2.5"/><circle cx="5" cy="13" r="2"/><circle cx="12" cy="13" r="2"/><circle cx="19" cy="13" r="2"/><path d="M12 8.5v2.5M5 11V10h14v1M2.5 20c.4-2 1.4-3 2.5-3s2.1 1 2.5 3M9.5 20c.4-2 1.4-3 2.5-3s2.1 1 2.5 3M16.5 20c.4-2 1.4-3 2.5-3s2.1 1 2.5 3"/>',
		wereld: '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.5 5.5 3.5 9s-1 6.5-3.5 9c-2.5-2.5-3.5-5.5-3.5-9s1-6.5 3.5-9"/>',
		lagen: '<path d="M4 7h16M4 12h16M4 17h16"/>',
		klok: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		doel: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
		vraag: '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .8-1 1.5v.7M12 17v.5"/>',
		boek: '<path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5zM20 5.5A1.5 1.5 0 0 0 18.5 4H13v16h5.5a1.5 1.5 0 0 0 1.5-1.5z"/>'
	};
	function icoon( naam ) {
		return el( 'span', { class: 'sfp-tool__icoon', 'aria-hidden': 'true', html: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">' + ICONEN[ naam ] + '</svg>' } );
	}

	window.sfpToolMotor.bouwGenerator = bouw;
	window.sfpToolMotor.generatorOntbreekt = ontbreekt;
	/* De rekentaal en het invullen van sjablonen, ook voor de tekstanalyse. */
	window.sfpToolMotor.reken = {
		reken: function ( b, formule, extra ) { return reken( b, {}, formule, { extra: extra } ); },
		vul: function ( b, sjabloon, extra ) { return vul( sjabloon, b, {}, { extra: extra } ); },
		tekst: rTekst,
		getal: rGetal,
		waar: rWaar,
		afronden: afronden,
		formaat: function ( b, w, formaten ) { var x = rTekst( w ); ( formaten ? String( formaten ).split( '|' ) : [] ).forEach( function ( f ) { x = String( filter( b, {}, x, '', f, null ) ); } ); return x; },
		platteTekst: platteTekst
	};

	/* ---------------------------------------------------------------- *
	 * In de browser
	 * ---------------------------------------------------------------- */

	function fabriek( tool ) {
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
		/* Kaartweergave (3.21.0): tegels, letters als voortgang, vaste hoogte, uitklapblokken. Alleen als de beschrijving erom vraagt. */
		var kaart = !! ( b.weergave && 'kaart' === b.weergave.modus );
		var groepen = kaart && b.weergave.groepen && b.weergave.groepen.length ? b.weergave.groepen : stappen.map( function ( s, i ) { return { letter: String( i + 1 ), naam: s.titel || '', stappen: 1 }; } );
		var groepStart = [];
		( function () { var n = 0; groepen.forEach( function ( g ) { groepStart.push( n ); n += g.stappen; } ); } )();
		function groepVan( i ) { var g = 0; groepStart.forEach( function ( s, k ) { if ( i >= s ) { g = k; } } ); return g; }
		function alleenKeuzes( i ) {
			var zicht = ( stappen[ i ] && stappen[ i ].velden ? stappen[ i ].velden : [] ).filter( function ( x ) { return zichtbaar( b, antwoorden, x ); } );
			return zicht.length > 0 && zicht.every( function ( x ) { return 'keuze' === x.type; } );
		}

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
		function bewaar() { tool.bewaar( kaart ? { a: antwoorden, i: huidig, n: invul } : { a: antwoorden, i: huidig } ); }
		var notitieTimer = null;
		function bewaarNoot() { clearTimeout( notitieTimer ); notitieTimer = setTimeout( bewaar, 400 ); }
		function zet( id, w ) { antwoorden[ id ] = w; bewerkt = null; bewaar(); werkZichtbaar(); if ( b.live ) { liveBij(); } }
		var liveTimer = null;
		function liveBij() { clearTimeout( liveTimer ); liveTimer = setTimeout( function () { if ( uitvoerEl ) { vulUitvoer( uitvoerEl ); } else { toonResultaat(); } }, 120 ); }

		function maakVeld( v, stapIndex ) {
			var id = 'sfp-tool-' + tool.id + '-g-' + v.id;
			var labelTekst = v.label || '';
			var labelEl = el( 'p', { class: 'sfp-tool__vraag', id: id + '-l', text: labelTekst } );
			var hint = v.hint ? el( 'p', { class: 'sfp-tool__hint', text: '' } ) : null;
			var veld;
			if ( 'keuze' === v.type && kaart ) {
				var opties = v.opties || [];
				var kw = 'kwadrant' === v.tegels && 4 === opties.length && v.assen && 2 === ( v.assen.kolommen || [] ).length && 2 === ( v.assen.rijen || [] ).length;
				var metUitleg = opties.some( function ( o ) { return !! o.uitleg; } );
				var kol = v.kolommen || ( kw || 4 === opties.length || metUitleg ? 2 : 3 );
				veld = el( 'div', { class: 'sfp-tool__tegels sfp-tool__tegels--' + kol + ( kw ? ' sfp-tool__tegels--kwadrant' : '' ) + ( metUitleg ? ' sfp-tool__tegels--uitleg' : '' ), role: 'radiogroup', 'aria-labelledby': id + '-l' } );
				var tegel = function ( o ) {
					var inp = el( 'input', { type: 'radio', class: 'sfp-tool__radio', name: id, value: String( o.waarde ),
						onchange: function () { zet( v.id, String( o.waarde ) ); },
						/* Een klik (of spatie) kiest en gaat door, ook op een keuze die al gekozen was; pijltjes kiezen alleen. */
						onclick: function () { zet( v.id, String( o.waarde ) ); if ( alleenKeuzes( stapIndex ) ) { setTimeout( function () { if ( huidig === stapIndex ) { volgende(); } }, 180 ); } }
					} );
					return el( 'label', { class: 'sfp-tool__tegel' + ( o.icoon && ICONEN[ o.icoon ] ? ' met-icoon' : '' ) }, [ inp, o.icoon && ICONEN[ o.icoon ] ? icoon( o.icoon ) : null, el( 'b', { text: o.tekst || '' } ), o.uitleg ? el( 'small', { text: o.uitleg } ) : null ] );
				};
				if ( kw ) {
					veld.appendChild( el( 'span', { class: 'sfp-tool__as sfp-tool__as--hoek', 'aria-hidden': 'true' } ) );
					v.assen.kolommen.forEach( function ( c ) { veld.appendChild( el( 'span', { class: 'sfp-tool__as', 'aria-hidden': 'true', text: c } ) ); } );
					[ 0, 1 ].forEach( function ( r ) {
						veld.appendChild( el( 'span', { class: 'sfp-tool__as sfp-tool__as--rij', 'aria-hidden': 'true', text: v.assen.rijen[ r ] } ) );
						veld.appendChild( tegel( opties[ r * 2 ] ) );
						veld.appendChild( tegel( opties[ r * 2 + 1 ] ) );
					} );
				} else {
					opties.forEach( function ( o ) { veld.appendChild( tegel( o ) ); } );
				}
			} else if ( 'keuze' === v.type ) {
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
			} else if ( 'tekstvak' === v.type || 'regels' === v.type || 'getallen' === v.type ) {
				veld = el( 'textarea', { class: 'sfp-tool__veld', id: id, rows: 'tekstvak' === v.type ? '4' : '5', maxlength: String( v.maxlengte || 2000 ), placeholder: v.voorbeeld || '', 'aria-labelledby': id + '-l', oninput: function () { zet( v.id, this.value ); } } );
			} else {
				var soort = 'datum' === v.type ? 'date' : ( 'getal' === v.type ? 'number' : 'text' );
				veld = el( 'input', { type: soort, class: 'sfp-tool__veld', id: id, maxlength: 'kort' === v.type ? String( v.maxlengte || 160 ) : null, min: 'getal' === v.type && undefined !== v.min ? String( v.min ) : null, max: 'getal' === v.type && undefined !== v.max ? String( v.max ) : null, step: 'getal' === v.type ? String( v.stap || 'any' ) : null, inputmode: 'getal' === v.type ? 'decimal' : null, placeholder: v.voorbeeld || '', 'aria-labelledby': id + '-l', oninput: function () { zet( v.id, this.value ); }, onkeydown: kaart ? function ( e ) { if ( 'Enter' === e.key && ! e.isComposing ) { e.preventDefault(); volgende(); } } : null } );
				if ( 'getal' === v.type && v.eenheid ) { veld = el( 'div', { class: 'sfp-tool__getalveld' }, [ veld, el( 'span', { class: 'sfp-tool__eenheid', text: v.eenheid } ) ] ); }
			}
			var wrap = el( 'div', { class: 'sfp-tool__veldgroep', 'data-veld': v.id }, [ labelEl, hint, veld ] );
			invoer[ v.id ] = { wrap: wrap, veld: veld, label: labelEl, hint: hint, v: v };
			return wrap;
		}

		var intros = [];
		var tips = [];
		var uitvoerEl = null;
		var navs = [];
		var schermen = stappen.map( function ( s, i ) {
			var scherm = el( 'div', { class: 'sfp-tool__scherm', hidden: true } );
			if ( s.intro ) { var intro = el( 'div', { class: 'sfp-tool__intro-tekst' } ); intro.setAttribute( 'data-stap', String( i ) ); scherm.appendChild( intro ); intros.push( { el: intro, tekst: s.intro } ); }
			( s.velden || [] ).forEach( function ( v ) { scherm.appendChild( maakVeld( v, i ) ); } );
			if ( ( b.meldingen || [] ).some( function ( m ) { return m.stap === i + 1; } ) ) { var tip = el( 'div', { class: 'sfp-tool__tips', 'aria-live': 'polite' } ); tips.push( { el: tip, stap: i + 1 } ); scherm.appendChild( tip ); }
			if ( b.live ) { schermenWrap.appendChild( scherm ); return scherm; }
			var nav = el( 'div', { class: 'sfp-tool__nav' }, [ el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: i === stappen.length - 1 && ! kaart ? tx( 'tool_maak' ) : tx( 'tool_volgende' ), onclick: function () { volgende(); } } ) ] );
			scherm.appendChild( nav );
			navs[ i ] = nav;
			schermenWrap.appendChild( scherm );
			return scherm;
		} );
		schermenWrap.appendChild( resultaat );
		var vorige = el( 'button', { type: 'button', class: 'sfp-tool__vorige', hidden: true, text: '‹ ' + tx( 'tool_vorige' ), onclick: function () { naar( huidig - 1 ); } } );

		/* Kaartweergave: letters bovenaan (afgeronde letters klikbaar), Terug, de naam van de groep en een balk. */
		var letters = el( 'ol', { class: 'sfp-tool__letters', 'aria-label': tx( 'tool_voortgang' ) } );
		var letterKnoppen = groepen.map( function ( g, k ) {
			var knop = el( 'button', { type: 'button', class: 'sfp-tool__letter', text: g.letter, onclick: function () { if ( this.classList.contains( 'is-klaar' ) ) { naar( groepStart[ k ] ); } } } );
			letters.appendChild( el( 'li', null, [ knop ] ) );
			return knop;
		} );
		var terug = el( 'button', { type: 'button', class: 'sfp-tool__tekstlink sfp-tool__terug', hidden: true, text: tx( 'tool_terug' ), onclick: function () { naar( Math.min( huidig, stappen.length ) - 1 ); } } );
		var kaartKop = el( 'div', { class: 'sfp-tool__kaartkop' }, [ letters, terug ] );
		var groepNaam = el( 'p', { class: 'sfp-tool__groepnaam' } );
		var groepBalk = el( 'div', { class: 'sfp-tool__balk sfp-tool__balk--kaart', 'aria-hidden': 'true' }, [ el( 'div', { class: 'sfp-tool__balk-vul' } ) ] );
		function werkKaartKop( i ) {
			var g = i >= stappen.length ? groepen.length : groepVan( i );
			letterKnoppen.forEach( function ( knop, k ) {
				var klaar = k < g, actief = k === g;
				knop.classList.toggle( 'is-klaar', klaar );
				knop.classList.toggle( 'is-actief', actief );
				knop.setAttribute( 'aria-label', klaar ? tx( 'tool_letter_klaar', groepen[ k ].naam || groepen[ k ].letter ) : ( groepen[ k ].naam || groepen[ k ].letter ) );
				if ( actief ) { knop.setAttribute( 'aria-current', 'step' ); } else { knop.removeAttribute( 'aria-current' ); }
			} );
			terug.hidden = 0 === i;
			var vraag = i < stappen.length;
			groepNaam.hidden = ! vraag;
			groepBalk.hidden = ! vraag;
			if ( vraag ) {
				groepNaam.textContent = tx( 'tool_groep_van', g + 1, groepen.length ) + ( groepen[ g ].naam ? ' · ' + groepen[ g ].naam : '' );
				groepBalk.firstChild.style.width = Math.round( ( i / stappen.length ) * 100 ) + '%';
			}
		}

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
			tips.forEach( function ( x ) {
				x.el.innerHTML = '';
				( b.meldingen || [] ).forEach( function ( m ) { if ( m.stap === x.stap && rWaar( reken( b, antwoorden, m.formule, null ) ) ) { x.el.appendChild( el( 'p', { class: 'sfp-tool__tip', text: vul( m.melding, b, antwoorden ) } ) ); } } );
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
			if ( kaart ) {
				/* Alle vragen liggen op dezelfde plek; de hoogste bepaalt de hoogte, dus niets verspringt. */
				schermen.forEach( function ( s, k ) { var weg = k !== i; s.hidden = false; s.classList.toggle( 'is-weg', weg ); s.inert = weg; s.setAttribute( 'aria-hidden', weg ? 'true' : 'false' ); } );
				schermenWrap.classList.toggle( 'is-uitslag', i === stappen.length );
				/* Bij een keuze geen knop Volgende; voor alle schermen tegelijk, anders verandert de hoogte onderweg. */
				navs.forEach( function ( n, k ) { if ( n ) { n.hidden = alleenKeuzes( k ); } } );
				werkKaartKop( i );
			} else {
				schermen.forEach( function ( s, k ) { s.hidden = k !== i; } );
			}
			resultaat.hidden = i !== stappen.length;
			vorige.hidden = 0 === i;
			stapItems.forEach( function ( li, k ) { li.classList.toggle( 'is-actief', k === i ); li.setAttribute( 'aria-selected', k === i ? 'true' : 'false' ); li.tabIndex = k === i ? 0 : -1; } );
			if ( i === stappen.length ) { toonResultaat(); }
			bewaar();
			var focus = i === stappen.length ? resultaat.querySelector( '.sfp-tool__kop' ) : schermen[ i ].querySelector( 'input, select, textarea' );
			if ( kaart && focus && i < stappen.length && 'INPUT' === focus.tagName && 'radio' === focus.type ) { focus = null; }
			if ( focus && tool.gestart ) { try { focus.focus( { preventScroll: true } ); } catch ( e ) {} }
			if ( kaart && tool.gestart && i === stappen.length ) {
				/* De uitslag groeit: de pagina gaat naar de kop van de uitslag. */
				var kopUit = resultaat.querySelector( '.sfp-tool__kop' );
				if ( kopUit && kopUit.getBoundingClientRect().top < 0 || kopUit && kopUit.getBoundingClientRect().top > window.innerHeight * 0.6 ) { kopUit.scrollIntoView( { behavior: 'smooth', block: 'start' } ); }
			} else if ( tool.gestart && app.getBoundingClientRect().top < 0 ) { app.scrollIntoView( { behavior: 'smooth', block: 'start' } ); }
			tool.gestart = true;
		}
		function toonOntbreekt( k ) {
			var mis = ontbreekt( b, antwoorden, stappen[ k ] );
			if ( mis.length && '!' === String( mis[ 0 ] ).charAt( 0 ) ) { var c = ( b.controles || [] )[ parseInt( mis[ 0 ].slice( 1 ), 10 ) ]; toonMelding( c ? vul( c.melding, b, antwoorden ) : '' ); return; }
			var v = invoer[ mis[ 0 ] ] ? invoer[ mis[ 0 ] ].v : null;
			toonMelding( v && 'vinkjes' === v.type && v.min ? tx( 'tool_kies_minimaal', v.min ) : tx( 'tool_vul_in', v ? ( v.label || '' ) : '' ) );
			if ( invoer[ mis[ 0 ] ] ) { var f = invoer[ mis[ 0 ] ].wrap.querySelector( 'input, select, textarea' ); if ( f ) { f.focus(); } }
		}
		function volgende() {
			/* WP Rocket speelt een klik soms nog eens af nadat de scripts geladen zijn; op het resultaat is er geen volgende stap. */
			if ( huidig >= stappen.length ) { return; }
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
				if ( 'getal' === bl.type ) { uit.push( { type: 'tabel', rijen: [ [ bl.label || '', bl.tekst ] ] } ); if ( bl.onder ) { uit.push( { type: 'tekst', tekst: bl.onder } ); } return; }
				if ( 'verdeling' === bl.type ) { bl.rijen.forEach( function ( x ) { uit.push( { type: 'balk', naam: x[ 0 ], label: x[ 1 ] + ' (' + x[ 2 ] + '%)', deel: x[ 2 ] / 100 } ); } ); return; }
				if ( 'afvink' === bl.type ) { var gezet = vinkjes( antwoorden ); uit.push( { type: 'afvink', items: bl.items, aan: ( bl.ids || [] ).map( function ( id ) { return gezet.indexOf( id ) >= 0; } ) } ); return; }
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
			uitvoerEl = inhoud;
			vulUitvoer( inhoud, blokken );
			resultaat.appendChild( inhoud );
			toonActies();
		}
		/* Het resultaat zelf; bij live opnieuw bij elke wijziging. */
		function vulUitvoer( inhoud, blokken ) {
			inhoud.innerHTML = '';
			blokken = blokken || huidigeBlokken();
			if ( b.live && ontbreekt( b, antwoorden, stappen[ 0 ] || { velden: [] } ).length ) { return; }
			var voortgang = null, totaalVink = 0;
			function telVink() { if ( ! voortgang ) { return; } var n = vinkjes( antwoorden ).filter( function ( id ) { return alleIds.indexOf( id ) >= 0; } ).length; voortgang.textContent = tx( 'tool_afgevinkt', n, totaalVink ); }
			var alleIds = [];
			blokken.forEach( function ( bl ) { if ( 'afvink' === bl.type ) { alleIds = alleIds.concat( bl.ids || [] ); } } );
			totaalVink = alleIds.length;
			if ( 'checklist' === b.soort && totaalVink ) { voortgang = el( 'p', { class: 'sfp-tool__voortgang sfp-tool__voortgang--vink', 'aria-live': 'polite' } ); inhoud.appendChild( voortgang ); telVink(); }
			var doel = inhoud;
			blokken.forEach( function ( bl, bi ) {
				if ( kaart && 'subkop' === bl.type ) {
					var blokIn = el( 'div', { class: 'sfp-tool__blokin' } );
					inhoud.appendChild( el( 'details', { class: 'sfp-tool__blok' }, [ el( 'summary', null, [ el( 'span', { text: bl.tekst } ) ] ), blokIn ] ) );
					doel = blokIn;
					return;
				}
				if ( 'getal' === bl.type ) {
					doel.appendChild( el( 'div', { class: 'sfp-tool__getal' }, [ bl.label ? el( 'p', { class: 'sfp-tool__getal-label', text: bl.label } ) : null, el( 'p', { class: 'sfp-tool__getal-waarde', text: bl.tekst } ), bl.onder ? el( 'p', { class: 'sfp-tool__getal-onder', text: bl.onder } ) : null ] ) );
					return;
				}
				if ( 'verdeling' === bl.type ) {
					bl.rijen.forEach( function ( x ) {
						var vulling = el( 'div', { class: 'sfp-tool__balk-vul' } );
						vulling.style.width = x[ 2 ] + '%';
						doel.appendChild( el( 'div', { class: 'sfp-tool__balkrij' }, [ el( 'div', { class: 'sfp-tool__balkrij-kop' }, [ el( 'span', { text: x[ 0 ] } ), el( 'span', { text: x[ 1 ] + ' (' + x[ 2 ] + '%)' } ) ] ), el( 'div', { class: 'sfp-tool__balk' }, [ vulling ] ) ] ) );
					} );
					return;
				}
				if ( 'afvink' === bl.type ) {
					var ul = el( 'ul', { class: 'sfp-tool__lijst sfp-tool__lijst--afvink' } );
					bl.items.forEach( function ( it, i ) {
						var id = ( bl.ids || [] )[ i ];
						var vak = el( 'input', { type: 'checkbox', onchange: function () {
							var nu = vinkjes( antwoorden ).filter( function ( x ) { return x !== id; } );
							if ( this.checked ) { nu.push( id ); }
							antwoorden._vink = nu;
							bewaar();
							telVink();
						} } );
						vak.checked = vinkjes( antwoorden ).indexOf( id ) >= 0;
						ul.appendChild( el( 'li', null, [ el( 'label', null, [ vak, ' ', it ] ) ] ) );
					} );
					doel.appendChild( ul );
					return;
				}
				if ( 'subkop' === bl.type ) { doel.appendChild( el( 'h4', { class: 'sfp-tool__titel sfp-tool__titel--klein', text: bl.tekst } ) ); return; }
				if ( 'tekst' === bl.type ) {
					if ( b.bewerkbaar ) { doel.appendChild( el( 'div', { class: 'sfp-tool__bewerk', contenteditable: 'true', role: 'textbox', 'aria-multiline': 'true', 'aria-label': tx( 'tool_pas_aan' ), spellcheck: 'true', oninput: function () { var x = huidigeBlokken().slice(); x[ bi ] = { type: 'tekst', tekst: this.innerText.replace( /\n{3,}/g, '\n\n' ).trim() }; bewerkt = x; } }, [ bl.tekst ] ) ); return; }
					alineas( bl.tekst ).forEach( function ( x ) { doel.appendChild( el( 'p', { class: 'sfp-tool__tekst' }, x.split( '\n' ).reduce( function ( acc, r, k ) { return ( k ? acc.concat( [ el( 'br' ) ] ) : acc ).concat( metLinks( r ) ); }, [] ) ) ); } );
					return;
				}
				if ( 'tabel' === bl.type ) { doel.appendChild( el( 'dl', { class: 'sfp-tool__gegevens' }, bl.rijen.reduce( function ( acc, r ) { return acc.concat( [ el( 'dt', { text: r[ 0 ] } ), el( 'dd', { text: r[ 1 ] } ) ] ); }, [] ) ) ); return; }
				if ( kaart && 'invul' === bl.type ) {
					bl.items.forEach( function ( it, i ) {
						var sl = bl.sleutel + '.' + i;
						doel.appendChild( el( 'label', { class: 'sfp-tool__notitie' }, [ el( 'span', { text: it } ), el( 'textarea', { class: 'sfp-tool__veld sfp-tool__veld--notitie', rows: String( Math.max( 2, bl.lijnen || 3 ) ), maxlength: '2000', oninput: function () { invul[ sl ] = this.value; bewaarNoot(); } }, [ invul[ sl ] || '' ] ) ] ) );
					} );
					return;
				}
				var lijst = el( bl.nummers ? 'ol' : 'ul', { class: 'sfp-tool__lijst' + ( 'afvink' === bl.type ? ' sfp-tool__lijst--afvink' : '' ) } );
				bl.items.forEach( function ( it, i ) {
					var li = el( 'li', null, [ 'afvink' === bl.type ? el( 'label', null, [ el( 'input', { type: 'checkbox' } ), ' ', it ] ) : it ] );
					if ( 'invul' === bl.type ) {
						var sleutel = bl.sleutel + '.' + i;
						li.appendChild( el( 'textarea', { class: 'sfp-tool__veld sfp-tool__veld--antwoord', rows: '2', maxlength: '2000', 'aria-label': tx( 'tool_jouw_antwoord' ) + ': ' + it, placeholder: tx( 'tool_jouw_antwoord' ), oninput: function () { invul[ sleutel ] = this.value; } }, [ invul[ sleutel ] || '' ] ) );
					}
					lijst.appendChild( li );
				} );
				doel.appendChild( lijst );
			} );
		}
		function toonActies() {
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
			if ( kaart ) { rijLinks.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink', text: tx( 'tool_opnieuw' ), onclick: opnieuw } ) ); }
			rij.appendChild( rijLinks );
			resultaat.appendChild( rij );
			if ( mailbox ) { resultaat.appendChild( mailbox ); }
			resultaat.appendChild( deelMelding );
			if ( ! kaart ) { resultaat.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tekstlink sfp-tool__opnieuw', text: tx( 'tool_opnieuw' ), onclick: opnieuw } ) ); }
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
				else if ( veld.tagName === 'DIV' && veld.querySelector( 'input' ) ) { veld.querySelector( 'input' ).value = w; }
				else { veld.value = w; }
			} );
			if ( a && Array.isArray( a._vink ) ) { antwoorden._vink = vinkjes( a ); }
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
				if ( b.live ) {
					/* Live: één scherm, de uitkomst rekent mee terwijl de bezoeker typt. */
					app.appendChild( melding );
					app.appendChild( schermenWrap );
					var gedeeldLive = tool.uitDeellink();
					vulIn( Object.assign( standaard(), gedeeldLive || {} ) );
					if ( schermen[ 0 ] ) { schermen[ 0 ].hidden = false; }
					resultaat.hidden = false;
					resultaat.classList.add( 'sfp-tool__scherm--live' );
					toonResultaat();
					tool.gestart = true;
					return;
				}
				if ( kaart ) {
					app.classList.add( 'sfp-tool__app--kaart' );
					app.appendChild( kaartKop );
					app.appendChild( groepNaam );
					app.appendChild( groepBalk );
				} else {
					app.appendChild( balk );
					app.appendChild( vorige );
				}
				app.appendChild( melding );
				app.appendChild( schermenWrap );
				vulIn( standaard() );
				var gedeeld = tool.uitDeellink();
				if ( gedeeld ) { vulIn( Object.assign( standaard(), gedeeld ) ); tool.gestart = false; naar( stappen.length ); return; }
				var bewaard = tool.bewaard();
				if ( bewaard && bewaard.a && Object.keys( bewaard.a ).length ) {
					var vraag = el( 'div', { class: 'sfp-tool__scherm sfp-tool__scherm--intro' }, [
						el( 'p', { text: tx( 'tool_verder' ) } ),
						el( 'button', { type: 'button', class: 'sfp-tool__knop sfp-tool__knop--primair', text: tx( 'tool_verder_ja' ), onclick: function () { schermenWrap.removeChild( vraag ); if ( kaart && bewaard.n && 'object' === typeof bewaard.n ) { invul = Object.assign( {}, bewaard.n ); } vulIn( Object.assign( standaard(), bewaard.a ) ); naar( Math.min( bewaard.i || 0, stappen.length ) ); } } ),
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
	}
	M.soort( 'generator', fabriek );
	M.soort( 'rekenaar', fabriek );
	M.soort( 'checklist', fabriek );
} )();
