// Meet per proefpagina de plek van #onder zonder scripts (alleen het eerste
// scherm van de server) en met scripts (de echte tool). Verschil in px.
//   node bouw/eerste-scherm-proef.js <map>
const { chromium } = require( 'playwright' );
const fs = require( 'fs' );
const path = require( 'path' );
( async () => {
	const map = path.resolve( process.argv[ 2 ] || 'dist/proef' );
	const lijst = JSON.parse( fs.readFileSync( path.join( map, 'lijst.json' ), 'utf8' ) );
	const browser = await chromium.launch();
	const uit = [];
	for ( const breedte of [ 375, 1280 ] ) {
		const zonder = await browser.newContext( { viewport: { width: breedte, height: 900 }, javaScriptEnabled: false } );
		const met = await browser.newContext( { viewport: { width: breedte, height: 900 } } );
		for ( const t of lijst ) {
			const url = 'file://' + path.join( map, t.naam + '.html' );
			const p1 = await zonder.newPage(); await p1.goto( url );
			const y1 = await p1.evaluate( () => document.getElementById( 'onder' ).getBoundingClientRect().top + window.scrollY );
			await p1.close();
			const vers = await browser.newContext( { viewport: { width: breedte, height: 900 } } );
			const p2 = await vers.newPage(); const fouten = []; p2.on( 'pageerror', ( e ) => fouten.push( e.message ) );
			await p2.goto( url ); await p2.waitForTimeout( 150 );
			const r = await p2.evaluate( () => ( { y: document.getElementById( 'onder' ).getBoundingClientRect().top + window.scrollY, gestart: !! document.querySelector( '.sfp-tool[data-gestart]' ), rest: !! document.querySelector( '.sfp-tool__voorlopig' ) } ) );
			await p2.close(); await vers.close();
			uit.push( { naam: t.naam, soort: t.soort, kaart: t.kaart, live: t.live, vooraf: t.vooraf, breedte, zonder: Math.round( y1 ), met: Math.round( r.y ), verschil: Math.round( r.y - y1 ), gestart: r.gestart, voorlopigOver: r.rest, fouten } );
		}
		await zonder.close(); await met.close();
	}
	await browser.close();
	fs.writeFileSync( path.join( map, 'uitkomst.json' ), JSON.stringify( uit, null, 1 ) );
	for ( const r of uit ) {
		console.log( [ r.breedte, r.naam.padEnd( 44 ), r.soort.padEnd( 12 ), r.vooraf ? 'vooraf' : 'leeg  ', String( r.verschil ).padStart( 5 ), r.gestart ? '' : 'NIET GESTART', r.voorlopigOver ? 'VOORLOPIG BLEEF' : '', r.fouten.join( ' | ' ) ].join( ' ' ) );
	}
} )();
