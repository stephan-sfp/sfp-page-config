/**
 * SFP Page Config - De tool in het canvas van de editor (sinds 3.16.0).
 *
 * Op de pagina van een tool zet de site de tool zelf onder de titel. De
 * editor toont hem op die plek, scherm voor scherm zoals de lezer hem ziet,
 * en elke tekst die de lezer leest typ je daar: vragen, antwoorden, de
 * schaal, stappen, velden en hints, uitslagen en de koppen in het resultaat.
 *
 * De beschrijving (het metaveld sfp_tool) blijft de opslag. Dit script past
 * alleen bestaande teksten in die beschrijving aan; vragen of uitslagen
 * toevoegen en schrappen gaat onder Geavanceerd in het paneel Deze pagina.
 * De site controleert de beschrijving bij elke opslag, zoals voorheen.
 *
 * Sinds 3.18.0 ook de voortgangsbalk onder de voortgangsregel, de uitklap
 * "Hoe deze test rekent" (dicht, zoals op de site) en de ondertitel onder
 * de titel: die typ je hier en hij gaat naar de samenvatting.
 *
 * Geen buildstap: gewone JavaScript met wp.element.createElement.
 */
( function ( wp ) {
	if ( ! wp || ! wp.element || ! wp.data || ! wp.element.createRoot ) {
		return;
	}
	var S = window.sfpToolCanvas || {};
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useRef = wp.element.useRef;
	var useEffect = wp.element.useEffect;
	var data = wp.data;
	var T = S.teksten || {};

	function tekst( sleutel, terugval ) { return T[ sleutel ] || terugval; }

	/* ---------- De beschrijving lezen en schrijven ---------- */

	function lees( json ) {
		if ( ! json || ! String( json ).trim() ) { return null; }
		try {
			var b = JSON.parse( json );
			return b && 'object' === typeof b && ! Array.isArray( b ) ? b : null;
		} catch ( e ) {
			return null;
		}
	}

	/** Zelfde inspringing als de opgeslagen tekst, zodat een kleine wijziging klein blijft. */
	function inspringing( json ) {
		var m = /^\{\s*\n([ \t]+)"/.exec( String( json || '' ) );
		return m ? ( '\t' === m[ 1 ][ 0 ] ? '\t' : m[ 1 ].length ) : 0;
	}

	function haal( b, pad ) {
		var x = b;
		for ( var i = 0; i < pad.length; i++ ) {
			if ( null === x || 'object' !== typeof x ) { return undefined; }
			x = x[ pad[ i ] ];
		}
		return x;
	}

	/** Een kopie van b met op pad de nieuwe tekst. Alleen een bestaande tekst wordt vervangen. */
	function zet( b, pad, waarde ) {
		var kopie = JSON.parse( JSON.stringify( b ) );
		var x = kopie;
		for ( var i = 0; i < pad.length - 1; i++ ) {
			if ( null === x || 'object' !== typeof x ) { return b; }
			x = x[ pad[ i ] ];
		}
		var laatste = pad[ pad.length - 1 ];
		if ( null === x || 'object' !== typeof x || 'string' !== typeof x[ laatste ] ) { return b; }
		x[ laatste ] = waarde;
		return kopie;
	}

	/* ---------- Een tekst om te typen ---------- */

	/**
	 * De getypte tekst zoals hij in de beschrijving hoort. Niet innerText:
	 * dat geeft hoofdletters terug waar de opmaak ze toont (text-transform).
	 */
	function tekstVan( n ) {
		var uit = '';
		Array.prototype.forEach.call( n.childNodes, function ( k ) {
			if ( 3 === k.nodeType ) { uit += k.data; return; }
			if ( 1 !== k.nodeType ) { return; }
			if ( 'BR' === k.nodeName ) { uit += '\n'; return; }
			var blok = 'DIV' === k.nodeName || 'P' === k.nodeName;
			uit += ( blok && uit && '\n' !== uit.slice( -1 ) ? '\n' : '' ) + tekstVan( k );
		} );
		return uit;
	}

	/**
	 * Gewone tekst die je ter plekke typt. Bij verlaten gaat hij de
	 * beschrijving in. Meer alinea's: Enter maakt een nieuwe regel.
	 */
	function Bewerk( p ) {
		var ref = useRef( null );
		var waarde = haal( p.b, p.pad );
		var leeg = 'string' !== typeof waarde || '' === waarde;
		useEffect( function () {
			var n = ref.current;
			if ( n && n.ownerDocument.activeElement !== n ) {
				var nu = 'string' === typeof waarde ? waarde : '';
				if ( tekstVan( n ) !== nu ) { n.textContent = nu; }
			}
		}, [ waarde ] );
		if ( 'string' !== typeof waarde ) { return null; }
		return el( p.tag || 'span', {
			ref: ref,
			className: 'sfp-tc-bewerk' + ( p.className ? ' ' + p.className : '' ) + ( leeg ? ' is-leeg' : '' ),
			contentEditable: 'plaintext-only',
			suppressContentEditableWarning: true,
			spellCheck: true,
			role: 'textbox',
			'aria-multiline': p.meer ? 'true' : 'false',
			'aria-label': p.naam || 'Tekst',
			'data-leeg': p.leeg || p.naam || 'Leeg',
			onKeyDown: function ( e ) {
				e.stopPropagation();
				if ( 'Enter' === e.key && ! p.meer ) { e.preventDefault(); e.target.blur(); }
				if ( 'Escape' === e.key ) { e.target.textContent = waarde; e.target.blur(); }
			},
			onBlur: function ( e ) {
				// Precies wat er staat: spaties en regels aan begin en eind
				// horen soms bij een sjabloon en blijven dus staan.
				var nieuw = tekstVan( e.target );
				if ( ! p.meer && -1 === waarde.indexOf( '\n' ) ) { nieuw = nieuw.replace( /\n+/g, ' ' ); }
				if ( nieuw !== waarde ) { p.zet( p.pad, nieuw ); }
			},
		} );
	}

	/* ---------- Schermen zoals de lezer ze ziet ---------- */

	var VELD_TEKSTEN = [ [ 'label', 'Naam van het veld' ], [ 'hint', 'Hint' ], [ 'voorbeeld', 'Voorbeeld in het veld' ], [ 'eenheid', 'Eenheid' ] ];
	var UITVOER_TEKSTEN = [ [ 'kop', 'Kop' ], [ 'label', 'Label' ], [ 'tekst', 'Tekst', true ], [ 'onder', 'Tekst eronder' ], [ 'kaartkop', 'Kop per kaart' ], [ 'leeg', 'Tekst als er niets is' ], [ 'regel', 'Regel per zin' ] ];
	// Sleutels in een bank die zeggen bij welke keuze iets hoort: geen tekst voor de lezer.
	var BANK_GEEN_TEKST = { id: 1, categorie: 1, fase: 1, niveau: 1, soort: 1, vorm: 1, doel: 1, waarde: 1, sleutel: 1 };

	/** Een lijst, ook als de beschrijving daar iets anders heeft staan. */
	function lijstVan( x ) { return Array.isArray( x ) ? x : []; }

	function namen( o ) { return o && 'object' === typeof o ? Object.keys( o ) : []; }

	/** Alle teksten in de banken van een generator: zinnen die de tool kiest voor de uitslag. */
	function bankTeksten( b ) {
		var uit = [];
		( function loop( x, pad, sleutel ) {
			if ( 'string' === typeof x ) {
				if ( ! BANK_GEEN_TEKST[ sleutel ] && x.trim() ) { uit.push( pad ); }
				return;
			}
			if ( Array.isArray( x ) ) {
				x.forEach( function ( y, i ) { loop( y, pad.concat( [ i ] ), sleutel ); } );
				return;
			}
			if ( x && 'object' === typeof x ) {
				namen( x ).forEach( function ( k ) { loop( x[ k ], pad.concat( [ k ] ), k ); } );
			}
		}( b.banken || {}, [ 'banken' ], '' ) );
		return uit;
	}

	/**
	 * De schermen van deze tool, in de volgorde van de lezer. Elk scherm
	 * hoort bij een stap in de balk bovenaan.
	 */
	function schermen( b ) {
		var lijst = [];
		var soort = b.soort || 'test';
		if ( 'test' === soort ) {
			var W = b.weergave || {};
			var vragen = b.vragen || [];
			var voor = [], na = [];
			vragen.forEach( function ( v, i ) { ( 'tekst' === v.type && v.na_uitslag ? na : voor ).push( i ); } );
			if ( W.intro && 'string' === typeof W.intro.tekst ) { lijst.push( { stap: 0, naam: 'Begin', soort: 'intro' } ); }
			voor.forEach( function ( i, n ) { lijst.push( { stap: 0, naam: 'Vraag ' + ( n + 1 ) + ' van ' + voor.length, soort: 'vraag', i: i } ); } );
			lijst.push( { stap: 1, naam: 'Uitslag', soort: 'uitslag' } );
			if ( namen( b.dimensies ).length || ( b.labels || [] ).length || ( b.uitvoer || [] ).length ) { lijst.push( { stap: 1, naam: 'Onderdelen van het resultaat', soort: 'resultaat' } ); }
			na.forEach( function ( i ) { lijst.push( { stap: 1, naam: 'Vraag na de uitslag', soort: 'vraag', i: i } ); } );
		} else if ( 'tekstanalyse' === soort ) {
			lijst.push( { stap: 0, naam: 'Je tekst', soort: 'invoer' } );
			lijst.push( { stap: 1, naam: 'Resultaat', soort: 'resultaat' } );
		} else {
			( b.stappen || [] ).forEach( function ( s, i ) { lijst.push( { stap: i, naam: ( s.titel || 'Stap ' + ( i + 1 ) ), soort: 'stap', i: i } ); } );
			lijst.push( { stap: ( b.stappen || [] ).length, naam: 'Resultaat', soort: 'resultaat' } );
			if ( bankTeksten( b ).length ) { lijst.push( { stap: ( b.stappen || [] ).length, naam: 'Zinnen die de tool kiest', soort: 'banken' } ); }
		}
		if ( ( b.controles || [] ).length || ( b.meldingen || [] ).length || b.mail || namen( b.teksten ).length ) {
			lijst.push( { stap: -1, naam: 'Meldingen, mail en knoppen', soort: 'overig' } );
		}
		return lijst;
	}

	/** De namen in de stappenbalk, zoals de site ze toont. */
	function stapNamen( b ) {
		var soort = b.soort || 'test';
		if ( 'test' === soort ) {
			var st = b.weergave && b.weergave.stappen;
			if ( st && st.length ) { return st.map( function ( n, i ) { return { pad: [ 'weergave', 'stappen', i ] }; } ); }
			return [ { vast: tekst( 'tool_vragen', 'Vragen' ) }, { vast: tekst( 'tool_resultaat', 'Resultaat' ) } ];
		}
		if ( 'tekstanalyse' === soort ) {
			// Een tekstanalyse heeft op de site geen stappenbalk.
			return [];
		}
		return ( b.stappen || [] ).map( function ( s, i ) { return { pad: [ 'stappen', i, 'titel' ] }; } ).concat( [ { vast: tekst( 'tool_resultaat', 'Resultaat' ) } ] );
	}

	function Stukje( p ) {
		return el( 'div', { className: 'sfp-tc-stuk' }, p.label ? el( 'span', { className: 'sfp-tc-label' }, p.label ) : null, p.children );
	}

	function veld( b, z, pad, label, meer, tag, klasse ) {
		if ( 'string' !== typeof haal( b, pad ) ) { return null; }
		return el( Stukje, { key: pad.join( '.' ), label: label }, el( Bewerk, { b: b, zet: z, pad: pad, naam: label, meer: meer, tag: tag, className: klasse } ) );
	}

	function lijstVelden( b, z, pad, label ) {
		var l = haal( b, pad );
		if ( ! Array.isArray( l ) || ! l.length ) { return null; }
		return el( Stukje, { key: pad.join( '.' ), label: label },
			el( 'ul', { className: 'sfp-tc-lijst' }, l.map( function ( x, i ) {
				if ( 'string' === typeof x ) { return el( 'li', { key: i }, el( Bewerk, { b: b, zet: z, pad: pad.concat( [ i ] ), naam: label } ) ); }
				if ( x && 'string' === typeof x.tekst ) { return el( 'li', { key: i }, el( Bewerk, { b: b, zet: z, pad: pad.concat( [ i, 'tekst' ] ), naam: label } ) ); }
				return null;
			} ) )
		);
	}

	/**
	 * Hoe vol de voortgangsbalk bij deze vraag staat, zoals op de site: per
	 * scorende vraag, of per groep als de test per groep loopt. Geen balk bij
	 * alles op één pagina en bij een vraag die niet meetelt (context, tekst).
	 */
	function balkDeel( b, i ) {
		var W = b.weergave || {};
		if ( 'alles' === W.modus ) { return -1; }
		var soortVan = function ( v ) { var t = v.type || 'likert'; return 'ab' === t || 'janee' === t ? 'keuze' : t; };
		var voor = ( b.vragen || [] ).filter( function ( v ) { return ! ( 'tekst' === v.type && v.na_uitslag ); } );
		var v = ( b.vragen || [] )[ i ];
		if ( ! v || voor.indexOf( v ) < 0 ) { return -1; }
		if ( 'per-groep' === W.modus ) {
			var groepen = voor.map( function ( x ) { return x.groep || ''; } ).filter( function ( g, k, a ) { return a.indexOf( g ) === k; } );
			var gi = groepen.indexOf( v.groep || '' );
			return groepen.length && gi >= 0 ? ( gi + 1 ) / groepen.length : -1;
		}
		var scorend = voor.filter( function ( x ) { var t = soortVan( x ); return 'likert' === t || 'keuze' === t; } );
		var qi = scorend.indexOf( v );
		return qi >= 0 ? ( qi + 1 ) / scorend.length : -1;
	}

	/* Test: één vraag. */
	function Vraag( p ) {
		var b = p.b, z = p.zet, i = p.i, v = ( b.vragen || [] )[ i ] || {};
		var W = b.weergave || {};
		var type = v.type || 'likert';
		var schaalPad = v.schaal && namen( v.schaal ).length ? [ 'vragen', i, 'schaal' ] : [ 'weergave', 'schaal' ];
		var veldDeel;
		if ( 'likert' === type ) {
			var alsTekst = !! W.likert_als_tekst;
			veldDeel = el( 'div', { className: 'sfp-tool__likert-wrap' },
				el( 'div', { className: 'sfp-tool__likert' + ( alsTekst ? ' sfp-tool__likert--tekst' : '' ) },
					[ '1', '2', '3', '4', '5' ].map( function ( w ) {
						return el( 'span', { key: w, className: 'sfp-tool__optie sfp-tool__optie--likert' },
							alsTekst && 'string' === typeof haal( b, schaalPad.concat( [ w ] ) )
								? el( Bewerk, { b: b, zet: z, pad: schaalPad.concat( [ w ] ), naam: 'Schaal ' + w, className: 'sfp-tool__optie-tekst' } )
								: el( 'span', { className: 'sfp-tool__optie-tekst' }, w ) );
					} )
				),
				alsTekst ? null : el( 'div', { className: 'sfp-tool__uiteinden' }, [ '1', '3', '5' ].map( function ( w ) {
					return 'string' === typeof haal( b, schaalPad.concat( [ w ] ) ) ? el( Bewerk, { key: w, b: b, zet: z, pad: schaalPad.concat( [ w ] ), naam: 'Betekenis van ' + w } ) : el( 'span', { key: w } );
				} ) )
			);
		} else if ( 'keuze' === type || 'ab' === type || 'janee' === type || 'context' === type ) {
			veldDeel = el( 'div', { className: 'sfp-tool__keuzes' }, lijstVan( v.opties ).map( function ( o, j ) {
				return el( 'span', { key: j, className: 'sfp-tool__optie' }, el( Bewerk, { b: b, zet: z, pad: [ 'vragen', i, 'opties', j, 'tekst' ], naam: 'Antwoord ' + ( j + 1 ), className: 'sfp-tool__optie-tekst' } ) );
			} ) );
		} else {
			veldDeel = el( 'div', { className: 'sfp-tool__veld sfp-tc-veld' }, 'string' === typeof v.voorbeeld ? el( Bewerk, { b: b, zet: z, pad: [ 'vragen', i, 'voorbeeld' ], naam: 'Voorbeeld in het veld', leeg: 'Voorbeeld in het veld' } ) : ' ' );
		}
		var deel = balkDeel( b, i );
		return el( 'div', { className: 'sfp-tool__scherm' },
			'string' === typeof v.intro ? el( Bewerk, { b: b, zet: z, pad: [ 'vragen', i, 'intro' ], naam: 'Inleiding', meer: true, tag: 'div', className: 'sfp-tool__intro-tekst' } ) : null,
			'string' === typeof v.groep ? el( 'p', { className: 'sfp-tool__voortgang' }, el( Groep, { b: b, zet: p.zetHeel, i: i } ), ' · ' + p.naam ) : el( 'p', { className: 'sfp-tool__voortgang' }, p.naam ),
			deel >= 0 ? el( 'div', { className: 'sfp-tool__balk sfp-tc-voortgangsbalk' }, el( 'div', { className: 'sfp-tool__balk-vul', style: { width: Math.round( deel * 100 ) + '%' } } ) ) : null,
			'string' === typeof W.vraagkop ? el( Bewerk, { b: b, zet: z, pad: [ 'weergave', 'vraagkop' ], naam: 'Kop boven elke vraag', tag: 'p', className: 'sfp-tool__vraagkop' } ) : null,
			el( Bewerk, { b: b, zet: z, pad: [ 'vragen', i, 'tekst' ], naam: 'Vraag', tag: 'p', className: 'sfp-tool__vraag' } ),
			veldDeel,
			veld( b, z, [ 'vragen', i, 'label' ], 'Label', false ),
			veld( b, z, [ 'vragen', i, 'hint' ], 'Hint', false )
		);
	}

	/**
	 * De groep van een vraag (Houding). Andere vragen in dezelfde groep
	 * krijgen dezelfde naam mee, zodat ze bij elkaar blijven.
	 */
	function Groep( p ) {
		var b = p.b, oud = b.vragen[ p.i ].groep;
		return el( Bewerk, {
			b: b, pad: [ 'vragen', p.i, 'groep' ], naam: 'Groep van de vraag',
			zet: function ( pad, nieuw ) {
				var kopie = JSON.parse( JSON.stringify( b ) );
				kopie.vragen.forEach( function ( v ) { if ( v.groep === oud ) { v.groep = nieuw; } } );
				p.zet( kopie );
			},
		} );
	}

	/* Test: één uitslag, te kiezen. */
	function Uitslag( p ) {
		var b = p.b, z = p.zet;
		var sleutels = namen( b.uitslagen ).filter( function ( k ) { var u = b.uitslagen[ k ]; return u && 'object' === typeof u && ( 'string' === typeof u.titel || 'string' === typeof u.tekst || 'string' === typeof u.kop || u.lijsten ); } );
		var gekozen = useState( sleutels[ 0 ] || '' );
		var k = sleutels.indexOf( gekozen[ 0 ] ) >= 0 ? gekozen[ 0 ] : sleutels[ 0 ];
		if ( ! k ) { return el( 'p', { className: 'sfp-tc-uitleg' }, 'Deze test heeft geen uitslagteksten.' ); }
		var u = b.uitslagen[ k ];
		var titel = function ( x ) { return 'string' === typeof x.titel ? x.titel : ( 'string' === typeof x.kop ? x.kop : x === u ? k : '' ); };
		return el( 'div', { className: 'sfp-tool__resultaat sfp-tc-uitslag' },
			el( 'label', { className: 'sfp-tc-kies' }, el( 'span', null, 'Uitslag ' + ( sleutels.indexOf( k ) + 1 ) + ' van ' + sleutels.length ),
				el( 'select', { value: k, onChange: function ( e ) { gekozen[ 1 ]( e.target.value ); } }, sleutels.map( function ( s ) {
					var x = b.uitslagen[ s ];
					return el( 'option', { key: s, value: s }, ( titel( x ) || s ) + ( titel( x ) && titel( x ) !== s ? ' (' + s + ')' : '' ) );
				} ) )
			),
			// Zoals het profiel in het resultaat op de site: kicker, titel, tekst.
			el( 'div', { className: 'sfp-tool__profiel' },
				'string' === typeof u.kop ? el( Bewerk, { b: b, zet: z, pad: [ 'uitslagen', k, 'kop' ], naam: 'Regel boven de titel', tag: 'p', className: 'sfp-tool__kicker' } ) : null,
				'string' === typeof u.titel ? el( Bewerk, { b: b, zet: z, pad: [ 'uitslagen', k, 'titel' ], naam: 'Titel van de uitslag', tag: 'h2', className: 'sfp-tool__titel' } ) : null,
				'string' === typeof u.tekst ? el( Bewerk, { b: b, zet: z, pad: [ 'uitslagen', k, 'tekst' ], naam: 'Tekst van de uitslag', meer: true, tag: 'div', className: 'sfp-tool__tekst' } ) : null
			),
			namen( u.lijsten ).map( function ( l ) { return lijstVelden( b, z, [ 'uitslagen', k, 'lijsten', l ], l ); } )
		);
	}

	/* Test: dimensies, labels en de koppen in het resultaat. */
	function TestResultaat( p ) {
		var b = p.b, z = p.zet;
		return el( 'div', { className: 'sfp-tool__resultaat' },
			namen( b.dimensies ).length ? el( Stukje, { label: 'Dimensies' }, el( 'ul', { className: 'sfp-tc-lijst' }, namen( b.dimensies ).map( function ( d ) {
				if ( 'string' === typeof b.dimensies[ d ] ) { return el( 'li', { key: d }, el( Bewerk, { b: b, zet: z, pad: [ 'dimensies', d ], naam: 'Dimensie' } ) ); }
				return el( 'li', { key: d }, el( Bewerk, { b: b, zet: z, pad: [ 'dimensies', d, 'naam' ], naam: 'Korte naam' } ),
					'string' === typeof ( b.dimensies[ d ] || {} ).volledig ? el( 'span', { className: 'sfp-tc-sub' }, el( Bewerk, { b: b, zet: z, pad: [ 'dimensies', d, 'volledig' ], naam: 'Volledige naam' } ) ) : null );
			} ) ) ) : null,
			lijstVelden( b, z, [ 'labels' ], 'Niveaus' ),
			Uitvoer( b, z )
		);
	}

	function Uitvoer( b, z ) {
		return lijstVan( b.uitvoer ).map( function ( u, i ) {
			if ( ! u || 'object' !== typeof u ) { return null; }
			var stukken = UITVOER_TEKSTEN.map( function ( x ) { return veld( b, z, [ 'uitvoer', i, x[ 0 ] ], x[ 1 ], !! x[ 2 ], 'tekst' === x[ 0 ] ? 'div' : 'span' ); } );
			stukken.push( lijstVelden( b, z, [ 'uitvoer', i, 'items' ], 'Punten' ) );
			lijstVan( u.regels ).forEach( function ( r, j ) {
				if ( r && 'object' === typeof r ) {
					stukken.push( el( 'div', { key: 'r' + j, className: 'sfp-tc-regel' }, veld( b, z, [ 'uitvoer', i, 'regels', j, 'label' ], 'Label', false ), veld( b, z, [ 'uitvoer', i, 'regels', j, 'tekst' ], 'Tekst', true, 'div' ) ) );
				}
			} );
			lijstVan( u.delen ).forEach( function ( d, j ) { stukken.push( veld( b, z, [ 'uitvoer', i, 'delen', j, 'label' ], 'Deel', false ) ); } );
			lijstVan( u.inhoud ).forEach( function ( d, j ) { stukken.push( veld( b, z, [ 'uitvoer', i, 'inhoud', j, 'tekst' ], 'Tekst in de kaart', true, 'div' ) ); } );
			if ( u.groeperen ) { stukken.push( veld( b, z, [ 'uitvoer', i, 'groeperen', 'kop' ], 'Kop per groep', false ) ); }
			lijstVan( u.items ).forEach( function ( d, j ) { if ( d && 'object' === typeof d && 'string' === typeof d.label ) { stukken.push( veld( b, z, [ 'uitvoer', i, 'items', j, 'label' ], 'Label', false ) ); } } );
			if ( ! stukken.some( Boolean ) ) { return null; }
			return el( 'section', { key: 'u' + i, className: 'sfp-tool__blok sfp-tc-blok' }, el( 'span', { className: 'sfp-tc-soort' }, 'Onderdeel ' + ( i + 1 ) ), stukken );
		} );
	}

	/* Generator, rekenaar, checklist: één stap. */
	function Stap( p ) {
		var b = p.b, z = p.zet, i = p.i, s = ( b.stappen || [] )[ i ] || {};
		return el( 'div', { className: 'sfp-tool__scherm' },
			veld( b, z, [ 'stappen', i, 'titel' ], null, false, 'h3', 'sfp-tool__staptitel' ),
			veld( b, z, [ 'stappen', i, 'intro' ], null, true, 'div', 'sfp-tool__intro-tekst' ),
			lijstVan( s.velden ).map( function ( v, j ) {
				var pad = [ 'stappen', i, 'velden', j ];
				return el( 'div', { key: j, className: 'sfp-tool__veldgroep sfp-tc-veldgroep' },
					VELD_TEKSTEN.map( function ( x ) { return veld( b, z, pad.concat( [ x[ 0 ] ] ), 'label' === x[ 0 ] ? null : x[ 1 ], false, 'label' === x[ 0 ] ? 'p' : 'span', 'label' === x[ 0 ] ? 'sfp-tool__label' : '' ); } ),
					( v.opties || [] ).length ? el( 'div', { className: 'sfp-tool__keuzes' }, v.opties.map( function ( o, k ) {
						return el( 'span', { key: k, className: 'sfp-tool__optie' },
							el( Bewerk, { b: b, zet: z, pad: pad.concat( [ 'opties', k, 'tekst' ] ), naam: 'Keuze ' + ( k + 1 ), className: 'sfp-tool__optie-tekst' } ),
							'string' === typeof o.uitleg ? el( 'span', { className: 'sfp-tc-sub' }, el( Bewerk, { b: b, zet: z, pad: pad.concat( [ 'opties', k, 'uitleg' ] ), naam: 'Uitleg bij de keuze' } ) ) : null );
					} ) ) : null,
					lijstVan( v.label_als ).map( function ( la, k ) { return veld( b, z, pad.concat( [ 'label_als', k, 'tekst' ] ), 'Naam bij een eerdere keuze', false ); } )
				);
			} )
		);
	}

	function GeneratorResultaat( p ) {
		var b = p.b, z = p.zet;
		return el( 'div', { className: 'sfp-tool__resultaat' },
			veld( b, z, [ 'resultaat_kop' ], null, false, 'h3', 'sfp-tool__titel' ),
			veld( b, z, [ 'resultaat_intro' ], null, true, 'div', 'sfp-tool__tekst' ),
			b.oordeel ? lijstVelden( b, z, [ 'oordeel', 'labels' ], 'Oordeel' ) : null,
			b.oordeel ? ( b.oordeel.labels || [] ).map( function ( l, i ) { return veld( b, z, [ 'oordeel', 'labels', i, 'uitleg' ], 'Uitleg bij ' + ( l.tekst || l.sleutel || i + 1 ), true, 'div' ); } ) : null,
			b.klassen ? lijstVelden( b, z, [ 'klassen', 'labels' ], 'Klassen per zin' ) : null,
			lijstVan( b.per_zin ).map( function ( m, i ) { return veld( b, z, [ 'per_zin', i, 'label' ], 'Meting per zin', false ); } ),
			Uitvoer( b, z ),
			veld( b, z, [ 'resultaat_slot' ], 'Slot', true, 'div' )
		);
	}

	function Invoer( p ) {
		var b = p.b, z = p.zet;
		return el( 'div', { className: 'sfp-tool__scherm' },
			veld( b, z, [ 'invoer', 'label' ], null, false, 'p', 'sfp-tool__label' ),
			veld( b, z, [ 'invoer', 'hint' ], 'Hint', false ),
			el( 'div', { className: 'sfp-tool__veld sfp-tc-veld' }, veld( b, z, [ 'invoer', 'voorbeeld' ], 'Voorbeeld', true, 'div' ) )
		);
	}

	function Banken( p ) {
		var b = p.b, z = p.zet;
		var paden = bankTeksten( b );
		return el( 'div', { className: 'sfp-tool__resultaat' },
			el( 'p', { className: 'sfp-tc-uitleg' }, 'De tool kiest uit deze zinnen voor de uitslag, afhankelijk van wat de lezer invult.' ),
			el( 'ul', { className: 'sfp-tc-lijst' }, paden.map( function ( pad ) {
				var naam = pad.slice( 1 ).filter( function ( x ) { return 'number' !== typeof x; } ).join( ' · ' );
				return el( 'li', { key: pad.join( '.' ) }, el( 'span', { className: 'sfp-tc-label' }, naam ), el( Bewerk, { b: b, zet: z, pad: pad, naam: naam, meer: true, tag: 'div' } ) );
			} ) )
		);
	}

	function Overig( p ) {
		var b = p.b, z = p.zet;
		return el( 'div', { className: 'sfp-tool__resultaat' },
			lijstVan( b.controles ).map( function ( c, i ) { return veld( b, z, [ 'controles', i, 'melding' ], 'Melding als iets ontbreekt', false ); } ),
			lijstVan( b.meldingen ).map( function ( c, i ) { return veld( b, z, [ 'meldingen', i, 'melding' ], 'Tip onder de velden', false ); } ),
			b.mail ? veld( b, z, [ 'mail', 'onderwerp' ], 'Onderwerp van de mail', false ) : null,
			b.mail ? veld( b, z, [ 'mail', 'intro' ], 'Begin van de mail', true, 'div' ) : null,
			b.acties ? veld( b, z, [ 'acties', 'pdf_bestandsnaam' ], 'Naam van de pdf', false ) : null,
			b.weergave && b.weergave.intro ? veld( b, z, [ 'weergave', 'intro', 'knop' ], 'Knop om te beginnen', false ) : null,
			namen( b.teksten ).map( function ( k ) { return veld( b, z, [ 'teksten', k ], ( S.knoppen && S.knoppen[ k ] ) || 'Vaste tekst', false ); } )
		);
	}

	/**
	 * "Hoe deze test rekent" onder de tool, dicht zoals op de site. De inhoud
	 * komt uit de beschrijving zoals bij de laatste keer opslaan.
	 */
	function Hoe() {
		var naam = tekst( 'tool_hoe_rekent', 'Hoe deze test rekent' );
		var inhoud = 'string' === typeof S.hoe && S.hoe ? S.hoe : '';
		return el( 'details', { className: 'sfp-tool__hoe' },
			el( 'summary', null, naam ),
			inhoud ? el( 'div', { className: 'sfp-tc-hoe', dangerouslySetInnerHTML: { __html: inhoud } } ) : el( 'p', { className: 'sfp-tc-uitleg' }, 'De uitleg komt uit de beschrijving en staat hier na opslaan.' )
		);
	}

	/**
	 * De ondertitel onder de titel, zoals de site hem toont: de samenvatting,
	 * hoogstens één zin. Typen hier past de samenvatting aan; het veld in het
	 * zijpaneel blijft werken.
	 */
	function Ondertitel() {
		var ref = useRef( null );
		var zin = data.useSelect( function ( select ) { return select( 'core/editor' ).getEditedPostAttribute( 'excerpt' ) || ''; }, [] );
		useEffect( function () {
			var n = ref.current;
			if ( n && n.ownerDocument.activeElement !== n && tekstVan( n ) !== zin ) { n.textContent = zin; }
		}, [ zin ] );
		return el( 'p', {
			ref: ref,
			className: 'sfp-art-lead sfp-tool-lead sfp-tc-bewerk sfp-tc-ondertitel' + ( zin ? '' : ' is-leeg' ),
			contentEditable: 'plaintext-only',
			suppressContentEditableWarning: true,
			spellCheck: true,
			role: 'textbox',
			'aria-multiline': 'false',
			'aria-label': 'Ondertitel (samenvatting)',
			'data-leeg': 'Ondertitel: één zin onder de titel',
			onKeyDown: function ( e ) {
				e.stopPropagation();
				if ( 'Enter' === e.key ) { e.preventDefault(); e.target.blur(); }
				if ( 'Escape' === e.key ) { e.target.textContent = zin; e.target.blur(); }
			},
			onBlur: function ( e ) {
				var nieuw = tekstVan( e.target ).replace( /\s*\n+\s*/g, ' ' ).trim();
				if ( nieuw !== zin.trim() ) { data.dispatch( 'core/editor' ).editPost( { excerpt: nieuw } ); }
				if ( ! nieuw ) { e.target.textContent = ''; }
			},
		} );
	}

	function Geheel() {
		return el( wp.element.Fragment, null, S.ondertitel ? el( Ondertitel ) : null, el( ToolCanvas ) );
	}

	/* ---------- Vangnet ---------- */

	function Vangnet( props ) { wp.element.Component.call( this, props ); this.state = { fout: null }; }
	Vangnet.prototype = Object.create( wp.element.Component.prototype );
	Vangnet.prototype.constructor = Vangnet;
	Vangnet.getDerivedStateFromError = function ( fout ) { return { fout: fout }; };
	Vangnet.prototype.componentDidUpdate = function ( vorige ) {
		if ( this.state.fout && vorige.sleutel !== this.props.sleutel ) { this.setState( { fout: null } ); }
	};
	Vangnet.prototype.render = function () {
		if ( this.state.fout ) {
			return el( 'p', { className: 'sfp-tc-uitleg' }, 'Dit scherm is hier niet te tonen. De teksten ervan pas je aan onder Geavanceerd in het paneel Deze pagina; de tool op de site werkt gewoon.' );
		}
		return this.props.children;
	};

	/* ---------- Het geheel ---------- */

	function ToolCanvas() {
		var json = data.useSelect( function ( select ) {
			var meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
			return meta.sfp_tool || '';
		}, [] );
		var b = lees( json );
		var nr = useState( 0 );
		if ( ! String( json ).trim() ) {
			return el( 'div', { className: 'sfp-tc sfp-tc--leeg' }, el( 'span', { className: 'sfp-vanzelf__label' }, 'Tool' ), el( 'p', null, 'Deze tool heeft nog geen beschrijving. Die komt onder Geavanceerd in het paneel Deze pagina; daarna staat de tool hier en typ je de teksten op hun plek.' ) );
		}
		if ( ! b ) {
			return el( 'div', { className: 'sfp-tc sfp-tc--leeg' }, el( 'span', { className: 'sfp-vanzelf__label' }, 'Tool' ), el( 'p', null, 'De beschrijving van deze tool is niet te lezen. Herstel hem onder Geavanceerd in het paneel Deze pagina; zolang staat de tool hier niet.' ) );
		}
		var heel = function ( nieuw ) {
			var tekstNu = JSON.stringify( nieuw, null, inspringing( json ) );
			data.dispatch( 'core/editor' ).editPost( { meta: { sfp_tool: tekstNu } } );
		};
		var zetTekst = function ( pad, waarde ) {
			var nieuw = zet( b, pad, waarde );
			if ( nieuw !== b ) { heel( nieuw ); }
		};
		var lijst = schermen( b );
		var i = Math.min( nr[ 0 ], lijst.length - 1 );
		var sch = lijst[ i ] || null;
		var stappen = stapNamen( b );
		var props = { b: b, zet: zetTekst, zetHeel: heel };
		var inhoud = null;
		if ( sch ) {
			if ( 'intro' === sch.soort ) {
				inhoud = el( 'div', { className: 'sfp-tool__scherm sfp-tool__scherm--intro' }, veld( b, zetTekst, [ 'weergave', 'intro', 'tekst' ], null, true, 'div' ), veld( b, zetTekst, [ 'weergave', 'intro', 'knop' ], null, false, 'span', 'sfp-tool__knop sfp-tool__knop--primair' ) );
			} else if ( 'vraag' === sch.soort ) {
				inhoud = el( Vraag, Object.assign( { i: sch.i, naam: sch.naam }, props ) );
			} else if ( 'uitslag' === sch.soort ) {
				inhoud = el( Uitslag, props );
			} else if ( 'resultaat' === sch.soort ) {
				inhoud = 'test' === ( b.soort || 'test' ) ? el( TestResultaat, props ) : el( GeneratorResultaat, props );
			} else if ( 'stap' === sch.soort ) {
				inhoud = el( Stap, Object.assign( { i: sch.i }, props ) );
			} else if ( 'invoer' === sch.soort ) {
				inhoud = el( Invoer, props );
			} else if ( 'banken' === sch.soort ) {
				inhoud = el( Banken, props );
			} else {
				inhoud = el( Overig, props );
			}
		}
		var naar = function ( j ) { nr[ 1 ]( Math.max( 0, Math.min( lijst.length - 1, j ) ) ); };
		var eersteVan = function ( stap ) { for ( var j = 0; j < lijst.length; j++ ) { if ( lijst[ j ].stap === stap ) { return j; } } return -1; };
		return el( 'div', { className: 'sfp-tc' },
			el( 'div', { className: 'sfp-tc-balk' },
				el( 'span', { className: 'sfp-vanzelf__label' }, 'De tool, zoals de lezer hem ziet · klik in een tekst om hem te typen' ),
				el( 'div', { className: 'sfp-tc-nav' },
					el( 'button', { type: 'button', onClick: function () { naar( i - 1 ); }, disabled: i <= 0, 'aria-label': 'Vorig scherm' }, '‹' ),
					el( 'select', { value: String( i ), onChange: function ( e ) { naar( parseInt( e.target.value, 10 ) ); }, 'aria-label': 'Scherm' }, lijst.map( function ( s, j ) { return el( 'option', { key: j, value: String( j ) }, s.naam ); } ) ),
					el( 'button', { type: 'button', onClick: function () { naar( i + 1 ); }, disabled: i >= lijst.length - 1, 'aria-label': 'Volgend scherm' }, '›' )
				)
			),
			el( 'div', { className: 'sfp-tool sfp-tc-tool' },
				el( 'div', { className: 'sfp-tool__app' },
					stappen.length ? el( 'ul', { className: 'sfp-tool__stappen' }, stappen.map( function ( s, j ) {
						var actief = sch && ( sch.stap === j || ( sch.stap === -1 && j === stappen.length - 1 ) );
						var spring = eersteVan( j );
						return el( 'li', {
							key: j,
							className: 'sfp-tool__stap' + ( actief ? ' is-actief' : '' ) + ( j === stappen.length - 1 ? ' is-slot' : '' ),
							onClick: spring >= 0 && ! actief ? function () { naar( spring ); } : undefined,
						}, s.pad ? el( Bewerk, { b: b, zet: zetTekst, pad: s.pad, naam: 'Naam van de stap' } ) : s.vast );
					} ) ) : null,
					el( 'div', { className: 'sfp-tool__paneel is-actief' }, el( Vangnet, { sleutel: i + ':' + json.length }, inhoud ) )
				),
				'test' === ( b.soort || 'test' ) ? el( Hoe ) : null
			)
		);
	}

	/* ---------- In het canvas zetten, onder de titel ---------- */

	var plek = null;
	var wortel = null;

	function canvas() {
		var iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
		try { return iframe ? iframe.contentDocument : document; } catch ( e ) { return null; }
	}

	function plaats() {
		var doc = canvas();
		if ( ! doc || ! doc.body ) { return; }
		var titel = doc.querySelector( '.editor-visual-editor__post-title-wrapper' );
		if ( ! titel ) { return; }
		if ( ! plek || plek.ownerDocument !== doc ) {
			if ( wortel ) { try { wortel.unmount(); } catch ( e ) {} }
			plek = doc.createElement( 'div' );
			plek.id = 'sfp-tool-canvas';
			plek.className = 'sfp-tc-plek sfp-artikel';
			plek.setAttribute( 'contenteditable', 'false' );
			wortel = wp.element.createRoot( plek );
			wortel.render( el( Geheel ) );
			doc.body.classList.add( 'sfp-met-tool-canvas' );
			doc.body.classList.toggle( 'sfp-met-tool-ondertitel', !! S.ondertitel );
		}
		var anker = doc.getElementById( 'sfp-vanzelf-kop' ) || titel;
		if ( anker.nextSibling !== plek ) {
			anker.parentNode.insertBefore( plek, anker.nextSibling );
		}
	}

	var wacht = null;
	data.subscribe( function () {
		if ( wacht ) { return; }
		wacht = setTimeout( function () { wacht = null; plaats(); }, 300 );
	} );
}( window.wp ) );
