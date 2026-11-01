// Verkleint de eigen JS en CSS in een uitgepakt pakket: naast elk bestand
// komt een .min.js of .min.css. De plugin kiest die als hij er is
// (sfp_page_config_asset_url, sfp_page_config_css_lees). Vendorbestanden en
// bestanden die al .min zijn blijven zoals ze zijn.
//
//   node bouw/verklein.js <map-met-sfp-page-config>
//
// Faalt hard als een verkleiner ontbreekt of een bestand niet te verkleinen
// is: een release zonder verkleinde bestanden is beter dan een kapotte.
'use strict';
const fs = require( 'fs' );
const path = require( 'path' );
const hier = path.join( __dirname, 'node_modules' );
const terser = require( path.join( hier, 'terser' ) );
const lightning = require( path.join( hier, 'lightningcss' ) );

const wortel = path.resolve( process.argv[ 2 ] || '.' );
const assets = path.join( wortel, 'assets' );

function bestanden( map ) {
	let uit = [];
	for ( const naam of fs.readdirSync( map ) ) {
		const pad = path.join( map, naam );
		const st = fs.statSync( pad );
		if ( st.isDirectory() ) {
			if ( 'vendor' !== naam ) { uit = uit.concat( bestanden( pad ) ); }
		} else if ( /\.(js|css)$/.test( naam ) && ! /\.min\.(js|css)$/.test( naam ) ) {
			uit.push( pad );
		}
	}
	return uit;
}

( async () => {
	let voor = 0, na = 0, n = 0;
	for ( const pad of bestanden( assets ) ) {
		const bron = fs.readFileSync( pad, 'utf8' );
		let klein;
		if ( pad.endsWith( '.js' ) ) {
			const r = await terser.minify( bron, { compress: { passes: 1 }, mangle: true, format: { comments: false } } );
			if ( ! r.code ) { throw new Error( 'terser gaf niets voor ' + pad ); }
			klein = r.code;
		} else {
			klein = lightning.transform( { filename: pad, code: Buffer.from( bron ), minify: true } ).code.toString();
		}
		fs.writeFileSync( pad.replace( /\.(js|css)$/, '.min.$1' ), klein );
		voor += Buffer.byteLength( bron ); na += Buffer.byteLength( klein ); n++;
	}
	console.log( 'Verkleind: ' + n + ' bestanden, ' + Math.round( voor / 1024 ) + ' KB naar ' + Math.round( na / 1024 ) + ' KB' );
} )().catch( ( e ) => { console.error( e.message ); process.exit( 1 ); } );
