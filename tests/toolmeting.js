/**
 * Toolmeting (3.24.0): de events sfp_tool van de motor, met een kleine nep-DOM.
 * Draaien: node tests/toolmeting.js <map assets/tool/>  (of via php tests/toolmeting.php)
 */
'use strict';
var fs = require( 'fs' );
var vm = require( 'vm' );
var path = require( 'path' );
var map = process.argv[ 2 ] || path.join( __dirname, '..', 'assets', 'tool' );

function Elem( tag ) {
	this.tagName = String( tag ).toUpperCase();
	this.attrs = {};
	this.kinderen = [];
	this.luisteraars = {};
	this.style = {};
	this.nodeType = 1;
}
Elem.prototype.setAttribute = function ( k, v ) { this.attrs[ k ] = String( v ); };
Elem.prototype.getAttribute = function ( k ) { return Object.prototype.hasOwnProperty.call( this.attrs, k ) ? this.attrs[ k ] : null; };
Elem.prototype.appendChild = function ( k ) { this.kinderen.push( k ); k.parentElement = this; return k; };
Elem.prototype.addEventListener = function ( s, f ) { ( this.luisteraars[ s ] = this.luisteraars[ s ] || [] ).push( f ); };
Elem.prototype.querySelector = function () { return null; };
Elem.prototype.contains = function ( x ) { while ( x ) { if ( x === this ) { return true; } x = x.parentElement; } return false; };
Elem.prototype.closest = function ( sel ) {
	var m = sel.match( /^\[([a-z-]+)\]$/ );
	var x = this;
	while ( x ) { if ( m && null !== x.getAttribute( m[ 1 ] ) ) { return x; } x = x.parentElement; }
	return null;
};
Elem.prototype.vuur = function ( soort, extra ) {
	var e = Object.assign( { type: soort }, extra || {} );
	( this.luisteraars[ soort ] || [] ).forEach( function ( f ) { f( e ); } );
};

var sessie = {};
var selectie = null;
var ctx = {
	console: console,
	setTimeout: function () {},
	Promise: Promise,
	JSON: JSON,
	document: {
		readyState: 'complete',
		title: 'Titel',
		documentElement: { lang: 'nl' },
		createElement: function ( t ) { return new Elem( t ); },
		createTextNode: function ( t ) { return { nodeType: 3, text: t }; },
		querySelectorAll: function () { return []; },
		addEventListener: function () {}
	}
};
ctx.window = ctx;
ctx.sessionStorage = {
	getItem: function ( k ) { return Object.prototype.hasOwnProperty.call( sessie, k ) ? sessie[ k ] : null; },
	setItem: function ( k, v ) { sessie[ k ] = String( v ); }
};
ctx.localStorage = { getItem: function () { return null; }, setItem: function () {}, removeItem: function () {} };
ctx.getSelection = function () { return selectie; };
ctx.sfpToolConfig = { site: 'test', teksten: {} };
vm.createContext( ctx );
vm.runInContext( fs.readFileSync( path.join( map, 'motor.js' ), 'utf8' ), ctx );

var fouten = 0, checks = 0;
function controleer( v, wat ) { checks++; if ( ! v ) { fouten++; console.log( 'FOUT: ' + wat ); } }

function nieuweTool( id, modus ) {
	var w = new Elem( 'div' );
	w.setAttribute( 'data-tool', String( id ) );
	w.setAttribute( 'data-slug', 'tool-' + id );
	var t = new ctx.sfpToolMotor.Tool( w );
	t.b = { soort: 'generator', weergave: modus ? { modus: modus } : {} };
	t.luister();
	return t;
}
function events() { return ( ctx.dataLayer || [] ).filter( function ( e ) { return 'sfp_tool' === e.event; } ); }
function acties() { return events().map( function ( e ) { return e.actie; } ); }

/* Zonder dataLayer: niets, en geen fout. */
var t0 = nieuweTool( 1, 'kaart' );
t0.wortel.vuur( 'click' );
t0.stap( 2, 5 );
t0.afgerond();
controleer( undefined === ctx.dataLayer, 'zonder dataLayer geen push en geen dataLayer aangemaakt' );

ctx.dataLayer = [];

/* Laden telt niet: een stap voor de eerste interactie geeft geen event. */
var t = nieuweTool( 1076, 'kaart' );
t.stap( 1, 7 );
t.afgerond();
controleer( 0 === events().length, 'laden en een deellink zonder interactie: geen events' );

/* Tab of pijltjes tellen niet als interactie. */
t.wortel.vuur( 'keydown', { key: 'Tab' } );
t.wortel.vuur( 'keydown', { key: 'ArrowDown' } );
controleer( 0 === events().length, 'Tab en pijltjes: geen start' );

/* Eerste klik: start, met de stap waar de bezoeker staat. */
t.wortel.vuur( 'pointerdown' );
t.wortel.vuur( 'click' );
t.wortel.vuur( 'change' );
controleer( 'start' === acties().join( ',' ), 'een start bij de eerste interactie, niet vaker' );
var e = events()[ 0 ];
controleer( 1076 === e.tool_id && 'tool-1076' === e.tool_slug && 'kaart' === e.weergave, 'tool_id, tool_slug en weergave kaart' );
controleer( 1 === e.stap_nummer && 7 === e.stap_totaal, 'start met stap 1 van 7' );
controleer( [ 'event', 'actie', 'tool_id', 'tool_slug', 'stap_nummer', 'stap_totaal', 'weergave' ].join() === Object.keys( e ).join(), 'precies de zeven sleutels' );

/* Stappen: alleen bij een wissel. */
t.stap( 2, 7 ); t.stap( 2, 7 ); t.stap( 3, 7 );
controleer( 'start,stap,stap' === acties().join( ',' ), 'stap alleen bij een wissel' );
controleer( 3 === events()[ 2 ].stap_nummer, 'stap_nummer 3' );

/* Afgerond: een keer per sessie per tool. */
t.afgerond(); t.afgerond();
controleer( 1 === acties().filter( function ( a ) { return 'afgerond' === a; } ).length, 'afgerond een keer' );
controleer( '1' === sessie[ 'sfp-tool-afgerond-test-1076' ], 'afgerond bewaard in sessionStorage' );
var t2 = nieuweTool( 1076, 'kaart' );
t2.wortel.vuur( 'click' );
t2.afgerond();
controleer( 1 === acties().filter( function ( a ) { return 'afgerond' === a; } ).length, 'afgerond niet opnieuw na herladen in dezelfde sessie' );

/* Acties na de uitslag. */
t.meet( 'kopieren' ); t.meet( 'download_pdf' ); t.meet( 'download_word' ); t.meet( 'opnieuw' );
var laatste4 = acties().slice( -4 ).join( ',' );
controleer( 'kopieren,download_pdf,download_word,opnieuw' === laatste4, 'kopieren, downloads en opnieuw' );
controleer( false === t.meet( 'iets_anders' ), 'onbekende actie wordt niet gepusht' );

/* Een deel met data-sfp-meet: kopiëren daaruit geeft slides_prompt. */
var deel = new Elem( 'details' );
deel.setAttribute( 'data-sfp-meet', 'slides_prompt' );
t.wortel.appendChild( deel );
var p = new Elem( 'p' );
deel.appendChild( p );
selectie = { anchorNode: { nodeType: 3, parentElement: p } };
t.wortel.vuur( 'copy' );
controleer( 'slides_prompt' === acties().slice( -1 )[ 0 ], 'kopiëren uit het deel: slides_prompt' );
var los = new Elem( 'p' );
t.wortel.appendChild( los );
var voor = events().length;
selectie = { anchorNode: los };
t.wortel.vuur( 'copy' );
controleer( voor === events().length, 'kopiëren buiten zo een deel: geen event' );

/* Weergave lijst en een andere actie voor de start: start komt voorop. */
var t3 = nieuweTool( 55, 'per-vraag' );
t3.meet( 'download_pdf' );
var laatste2 = events().slice( -2 );
controleer( 'start' === laatste2[ 0 ].actie && 'download_pdf' === laatste2[ 1 ].actie && 'lijst' === laatste2[ 1 ].weergave, 'start voorop, weergave lijst' );
controleer( null === laatste2[ 1 ].stap_nummer && null === laatste2[ 1 ].stap_totaal, 'zonder stap null' );

/* Nooit antwoorden of tekst in een event. */
controleer( events().every( function ( x ) { return Object.keys( x ).length === 7; } ), 'geen extra velden in een event' );

if ( fouten ) { console.log( fouten + ' van ' + checks + ' controles mislukt' ); process.exit( 1 ); }
console.log( 'OK: ' + checks + ' checks' );
