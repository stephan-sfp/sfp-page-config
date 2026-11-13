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
 *
 * Sinds 3.22.0 alleen het scherm; het bouwen en de rekentaal staan in
 * generator-bouw.js, dat eerst laadt.
 */
( function () {
	'use strict';
	var M = window.sfpToolMotor;
	if ( ! M ) { return; }
	var t = M.t, el = M.el, alineas = M.alineas;
	var D = M.generatorDelen;
	if ( ! D ) { return; }
	var ICONEN = D.ICONEN, antwoordVan = D.antwoordVan, bouw = D.bouw, filter = D.filter, heeft = D.heeft, icoon = D.icoon, isLeeg = D.isLeeg, items = D.items, klopt = D.klopt, metLinks = D.metLinks, ontbreekt = D.ontbreekt, platteTekst = D.platteTekst, rWaar = D.rWaar, reken = D.reken, velden = D.velden, verplicht = D.verplicht, vinkjes = D.vinkjes, vul = D.vul, waarde = D.waarde, zichtbaar = D.zichtbaar;
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
				/* Hulptegel (3.27.0): met v.hulp staat de uitleg van de opties niet in elke tegel maar achter één extra tegel, die een paneel onder de tegels opent. */
				var hulp = v.hulp && opties.some( function ( o ) { return !! o.uitleg; } ) ? String( v.hulp ) : '';
				var metUitleg = ! hulp && opties.some( function ( o ) { return !! o.uitleg; } );
				var kol = v.kolommen || ( kw || 4 === opties.length || metUitleg ? 2 : 3 );
				veld = el( 'div', { class: 'sfp-tool__tegels sfp-tool__tegels--' + kol + ( kw ? ' sfp-tool__tegels--kwadrant' : '' ) + ( metUitleg ? ' sfp-tool__tegels--uitleg' : '' ), role: 'radiogroup', 'aria-labelledby': id + '-l' } );
				var tegel = function ( o ) {
					var inp = el( 'input', { type: 'radio', class: 'sfp-tool__radio', name: id, value: String( o.waarde ),
						onchange: function () { zet( v.id, String( o.waarde ) ); },
						/* Een klik (of spatie) kiest en gaat door, ook op een keuze die al gekozen was; pijltjes kiezen alleen. */
						onclick: function () { zet( v.id, String( o.waarde ) ); if ( alleenKeuzes( stapIndex ) ) { setTimeout( function () { if ( huidig === stapIndex ) { volgende(); } }, 180 ); } }
					} );
					var lab = el( 'label', { class: 'sfp-tool__tegel' + ( o.icoon && ICONEN[ o.icoon ] ? ' met-icoon' : '' ) }, [ inp, o.icoon && ICONEN[ o.icoon ] ? icoon( o.icoon ) : null, el( 'b', { text: o.tekst || '' } ), o.uitleg && ! hulp ? el( 'small', { text: o.uitleg } ) : null ] );
					/* Structuuradvies (3.55.0): een label dat verschijnt zolang de voorwaarde van de optie klopt. */
					if ( o.aanbevolen_als ) { var badge = el( 'span', { class: 'sfp-tool__aanbevolen', hidden: true } ); var bTekst = lab.querySelector( 'b' ); var kol2 = el( 'span', { class: 'sfp-tool__tegeltekst' } ); bTekst.parentNode.insertBefore( kol2, bTekst ); kol2.appendChild( bTekst ); kol2.appendChild( badge ); advies.push( { o: o, label: lab, badge: badge } ); }
					return lab;
				};
				var advies = [];
				var hulpPaneel = null;
				if ( hulp ) {
					var hulpId = id + '-hulp';
					hulpPaneel = el( 'div', { class: 'sfp-tool__hulp', id: hulpId, hidden: true, role: 'region', 'aria-label': hulp }, [
						el( 'dl', { class: 'sfp-tool__hulp-lijst' }, opties.filter( function ( o ) { return !! o.uitleg; } ).reduce( function ( acc, o ) {
							return acc.concat( [ el( 'dt', { text: o.tekst || String( o.waarde ) } ), el( 'dd', { text: o.uitleg } ) ] );
						}, [] ) )
					] );
					var hulpKnop = el( 'button', { type: 'button', class: 'sfp-tool__tegel sfp-tool__tegel--hulp met-icoon', 'aria-expanded': 'false', 'aria-controls': hulpId, onclick: function () {
						var open = hulpPaneel.hidden;
						hulpPaneel.hidden = ! open;
						this.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
						this.classList.toggle( 'is-open', open );
						if ( open ) { tool.meet( 'hulp' ); }
					}, onkeydown: function ( e ) { if ( 'Escape' === e.key && ! hulpPaneel.hidden ) { e.preventDefault(); this.click(); } } }, [ icoon( 'vraag' ), el( 'b', { text: hulp } ) ] );
					hulpPaneel.addEventListener( 'keydown', function ( e ) { if ( 'Escape' === e.key ) { e.preventDefault(); hulpKnop.click(); hulpKnop.focus(); } } );
				}
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
					if ( hulp ) { veld.appendChild( hulpKnop ); }
				}
				if ( advies.length && ! kw ) { adviezen.push( { v: v, raster: veld, tegels: advies, voor: hulp ? hulpKnop : null } ); }
				if ( hulpPaneel ) { veld = el( 'div', { class: 'sfp-tool__tegelgroep' }, [ veld, hulpPaneel ] ); }
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
			/* Keuzetegels met eigen invoer (3.55.0, besluit Stephan 13-11-2026): een tegel vult het veld, dat daarna aan te passen is. */
			var invoerVeld = veld;
			var sugRaster = null;
			if ( kaart && v.suggesties && v.suggesties.length && ( 'kort' === v.type || 'tekstvak' === v.type ) ) {
				sugRaster = el( 'div', { class: 'sfp-tool__tegels sfp-tool__tegels--2 sfp-tool__suggesties', role: 'group', 'aria-labelledby': id + '-l' } );
				veld = el( 'div', { class: 'sfp-tool__suggestiegroep' }, [ sugRaster, invoerVeld ] );
			}
			/* De zin tot nu toe boven de vraag (3.55.0). */
			var zinEl = kaart && v.zin ? el( 'p', { class: 'sfp-tool__zin' } ) : null;
			var wrap = el( 'div', { class: 'sfp-tool__veldgroep', 'data-veld': v.id }, [ zinEl, labelEl, hint, veld ] );
			invoer[ v.id ] = { wrap: wrap, veld: invoerVeld, label: labelEl, hint: hint, v: v, sug: sugRaster, zin: zinEl, sugSleutel: null };
			return wrap;
		}

		var intros = [];
		var tips = [];
		var adviezen = [];
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
			Object.keys( invoer ).forEach( function ( id ) {
				var x = invoer[ id ];
				if ( x.zin ) { werkZin( x ); }
				if ( x.sug ) { werkSuggesties( x ); }
			} );
			adviezen.forEach( werkAdvies );
		}
		/* De zin met het open deel gemarkeerd: wat tussen [ ] staat is nog niet ingevuld. */
		function werkZin( x ) {
			var tekst = vul( true === x.v.zin ? ( b.resultaat_kop || '' ) : String( x.v.zin ), b, kopAntwoorden() );
			x.zin.innerHTML = '';
			tekst.split( /(\[[^\]]*\])/ ).forEach( function ( deel ) {
				if ( '' === deel ) { return; }
				x.zin.appendChild( /^\[[^\]]*\]$/.test( deel ) ? el( 'span', { class: 'sfp-tool__zin-open', text: deel.slice( 1, -1 ) } ) : document.createTextNode( deel ) );
			} );
			x.zin.hidden = '' === tekst.trim();
		}
		/* De tegels die nu gelden (voorwaarde per tegel), en welke overeenkomt met het veld. */
		function werkSuggesties( x ) {
			var lijst = ( x.v.suggesties || [] ).filter( function ( s ) { return ! s.als || klopt( b, antwoorden, s.als, null ); } ).map( function ( s ) { return vul( s.tekst, b, antwoorden ); } ).filter( function ( s ) { return '' !== s.trim(); } );
			var sleutel = lijst.join( '\n' );
			if ( sleutel !== x.sugSleutel ) {
				x.sugSleutel = sleutel;
				x.sug.innerHTML = '';
				lijst.forEach( function ( tekst ) {
					x.sug.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tegel sfp-tool__suggestie', 'aria-pressed': 'false', 'data-tekst': tekst, onclick: function () { x.veld.value = tekst; zet( x.v.id, tekst ); } }, [ el( 'b', { text: tekst } ) ] ) );
				} );
				x.sug.appendChild( el( 'button', { type: 'button', class: 'sfp-tool__tegel sfp-tool__tegel--eigen', onclick: function () { try { x.veld.focus(); x.veld.select(); } catch ( e ) {} } }, [ el( 'b', { text: tx( 'tool_eigen' ) } ) ] ) );
				x.sug.hidden = ! lijst.length;
			}
			var nu = String( antwoorden[ x.v.id ] || '' ).trim();
			Array.prototype.forEach.call( x.sug.querySelectorAll( '.sfp-tool__suggestie' ), function ( k ) { var aan = '' !== nu && k.getAttribute( 'data-tekst' ) === nu; k.setAttribute( 'aria-pressed', aan ? 'true' : 'false' ); k.classList.toggle( 'is-gekozen', aan ); } );
		}
		/* Structuuradvies: aanbevolen tegels krijgen een label en staan vooraan; de rest houdt de volgorde uit de data. */
		function werkAdvies( r ) {
			var labelTekst = r.v.aanbevolen_label ? vul( r.v.aanbevolen_label, b, antwoorden ) : '';
			var aan = [], uit = [];
			r.tegels.forEach( function ( t ) {
				var ja = klopt( b, antwoorden, t.o.aanbevolen_als, null );
				t.label.classList.toggle( 'is-aanbevolen', ja );
				t.badge.hidden = ! ja;
				t.badge.textContent = '' !== labelTekst.trim() ? labelTekst : tx( 'tool_aanbevolen' );
				( ja ? aan : uit ).push( t.label );
			} );
			var alle = Array.prototype.filter.call( r.raster.children, function ( k ) { return 'LABEL' === k.tagName; } );
			var orde = aan.concat( uit ).concat( alle.filter( function ( k ) { return aan.indexOf( k ) < 0 && uit.indexOf( k ) < 0; } ) );
			/* Alleen verplaatsen als de volgorde verandert: een verplaatste tegel verliest zijn focus. */
			if ( orde.some( function ( k, i ) { return alle[ i ] !== k; } ) ) { orde.forEach( function ( k ) { r.raster.insertBefore( k, r.voor ); } ); }
		}
		/* Eigen tekst in de resultaatkop als deel van de zin (3.55.0): kleine eerste letter, geen punt aan het eind. */
		function kopAntwoorden() {
			var uit = {};
			Object.keys( antwoorden ).forEach( function ( k ) {
				var x = invoer[ k ];
				uit[ k ] = x && ( 'kort' === x.v.type || 'tekstvak' === x.v.type ) && 'string' === typeof antwoorden[ k ] ? D.deelzin( antwoorden[ k ] ) : antwoorden[ k ];
			} );
			return uit;
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
			if ( i < stappen.length ) { tool.stap( i + 1, stappen.length ); } else { tool.afgerond(); }
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
			if ( focus && tool.gestart ) {
				try { focus.focus( { preventScroll: true } ); } catch ( e ) {}
				/* Het vorige scherm wordt inert terwijl het nog de focus heeft; de browser haalt die pas daarna weg (3.55.0): nog een keer, na dat moment. */
				if ( document.activeElement !== focus ) { setTimeout( function () { if ( huidig === i && ! app.contains( document.activeElement ) || document.activeElement === document.body ) { try { focus.focus( { preventScroll: true } ); } catch ( e ) {} } }, 30 ); }
			}
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
			uit.push( { type: 'kop', tekst: b.resultaat_kop ? vul( b.resultaat_kop, b, kopAntwoorden() ) : tool.titel } );
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
			resultaat.appendChild( el( 'h3', { class: 'sfp-tool__kop sfp-tool__kop--resultaat', tabindex: '-1', text: b.resultaat_kop ? vul( b.resultaat_kop, b, kopAntwoorden() ) : tool.titel } ) );
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
			if ( b.live ) { tool.afgerond(); }
			var voortgang = null, totaalVink = 0;
			function telVink() { if ( ! voortgang ) { return; } var n = vinkjes( antwoorden ).filter( function ( id ) { return alleIds.indexOf( id ) >= 0; } ).length; voortgang.textContent = tx( 'tool_afgevinkt', n, totaalVink ); }
			var alleIds = [];
			blokken.forEach( function ( bl ) { if ( 'afvink' === bl.type ) { alleIds = alleIds.concat( bl.ids || [] ); } } );
			totaalVink = alleIds.length;
			if ( 'checklist' === b.soort && totaalVink ) { voortgang = el( 'p', { class: 'sfp-tool__voortgang sfp-tool__voortgang--vink', 'aria-live': 'polite' } ); inhoud.appendChild( voortgang ); telVink(); }
			var doel = inhoud;
			blokken.forEach( function ( bl, bi ) {
				/* Een deel met een eigen actie (sleutel meet): alles wat dit blok toevoegt krijgt data-sfp-meet. */
				var plek = doel, voor = doel.childNodes.length, plekInhoud = inhoud.childNodes.length;
				toonBlok( bl, bi );
				if ( bl.meet ) {
					[ [ plek, voor ], [ inhoud, plekInhoud ] ].forEach( function ( p ) {
						for ( var k = p[ 1 ]; k < p[ 0 ].childNodes.length; k++ ) { if ( 1 === p[ 0 ].childNodes[ k ].nodeType ) { p[ 0 ].childNodes[ k ].setAttribute( 'data-sfp-meet', bl.meet ); } }
					} );
				}
			} );
			function toonBlok( bl, bi ) {
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
			}
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
				var kopTekst = b.resultaat_kop ? vul( b.resultaat_kop, b, kopAntwoorden() ) : tool.titel;
				var tekst = platteTekst( kopTekst, ( ! acties.kopieer_kaal && b.resultaat_intro ? [ { type: 'tekst', tekst: vul( b.resultaat_intro, b, antwoorden ) } ] : [] ).concat( huidigeBlokken() ), invul, !! acties.kopieer_kaal );
				if ( navigator.clipboard && navigator.clipboard.writeText ) { navigator.clipboard.writeText( tekst ).then( function () { kort( tx( 'tool_gekopieerd' ) ); tool.zeg( tx( 'tool_gekopieerd' ) ); tool.meet( 'kopieren' ); }, function () { kort( tx( 'tool_bestand_fout' ) ); } ); } else { kort( tx( 'tool_bestand_fout' ) ); }
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
			tool.meet( 'opnieuw' );
			tool.wisBewaard();
			invul = {}; bewerkt = null;
			Array.prototype.forEach.call( app.querySelectorAll( 'input[type=radio], input[type=checkbox]' ), function ( i ) { i.checked = false; } );
			Array.prototype.forEach.call( app.querySelectorAll( 'input[type=text], input[type=date], input[type=number], textarea, select' ), function ( i ) { i.value = ''; } );
			vulIn( standaard() );
			if ( tool.formulier ) { tool.formulier.hidden = true; }
			naar( 0 );
		}

		/* Volledig scherm (3.55.0, besluit Stephan 13-11-2026): een kaart met letters opent na de eerste keuze over het hele scherm.
		   De tool verhuist naar het einde van body (een ouder met transform zou position:fixed anders vangen); een lege plek
		   van dezelfde hoogte houdt de pagina op zijn plaats. Sluiten of Escape zet alles terug, met de antwoorden en de focus. */
		var volAan = kaart && !! ( b.weergave && b.weergave.groepen && b.weergave.groepen.length ) && ! ( b.weergave && false === b.weergave.volledig );
		var wortel = tool.wortel;
		var isVol = false, plek = null, stil = [];
		var volSluit = el( 'button', { type: 'button', class: 'sfp-tool__sluit', 'aria-label': tx( 'tool_sluiten_label' ), onclick: function () { sluitVol(); } }, [ el( 'span', { text: tx( 'tool_sluiten' ) } ), el( 'span', { class: 'sfp-tool__sluit-x', 'aria-hidden': 'true', text: '×' } ) ] );
		var volBalk = el( 'div', { class: 'sfp-tool__volbalk', hidden: true }, [ el( 'span', { class: 'sfp-tool__volnaam', text: tool.titel } ), volSluit ] );
		function openVol() {
			if ( ! volAan || isVol || ! wortel || ! wortel.parentNode ) { return; }
			var actief = document.activeElement;
			isVol = true;
			plek = el( 'div', { class: 'sfp-tool__plek', 'aria-hidden': 'true' } );
			plek.style.height = wortel.getBoundingClientRect().height + 'px';
			wortel.parentNode.insertBefore( plek, wortel );
			document.body.appendChild( wortel );
			stil = Array.prototype.filter.call( document.body.children, function ( k ) { return k !== wortel && ! k.inert && 'SCRIPT' !== k.tagName; } );
			stil.forEach( function ( k ) { k.inert = true; } );
			wortel.classList.add( 'is-vol' );
			wortel.setAttribute( 'role', 'dialog' );
			wortel.setAttribute( 'aria-modal', 'true' );
			wortel.setAttribute( 'aria-label', tool.titel );
			document.documentElement.classList.add( 'sfp-tool-vol' );
			volBalk.hidden = false;
			wortel.scrollTop = 0;
			if ( actief && wortel.contains( actief ) ) { try { actief.focus( { preventScroll: true } ); } catch ( e ) {} }
			/* De verhuizing haalt de focus weg: dan de kaart zelf, zodat Tab binnen de kit verder gaat. */
			if ( ! wortel.contains( document.activeElement ) ) { app.setAttribute( 'tabindex', '-1' ); try { app.focus( { preventScroll: true } ); } catch ( e ) {} }
		}
		function sluitVol() {
			if ( ! isVol ) { return; }
			isVol = false;
			stil.forEach( function ( k ) { k.inert = false; } );
			stil = [];
			if ( plek && plek.parentNode ) { plek.parentNode.replaceChild( wortel, plek ); }
			plek = null;
			wortel.classList.remove( 'is-vol' );
			wortel.removeAttribute( 'role' );
			wortel.removeAttribute( 'aria-modal' );
			wortel.removeAttribute( 'aria-label' );
			document.documentElement.classList.remove( 'sfp-tool-vol' );
			volBalk.hidden = true;
			/* Focus terug naar de kit: het eerste veld van het actieve scherm, anders de kop van de uitslag of de kaart. */
			var doel = huidig >= stappen.length ? resultaat.querySelector( '.sfp-tool__kop' ) : ( schermen[ huidig ] && schermen[ huidig ].querySelector( 'input:checked, input, textarea, select, button' ) );
			if ( ! doel ) { doel = app; app.setAttribute( 'tabindex', '-1' ); }
			try { doel.focus( { preventScroll: true } ); } catch ( e ) {}
			app.scrollIntoView( { block: 'nearest' } );
		}
		if ( volAan ) {
			/* Pas bij een echte keuze of invoer van de bezoeker, niet bij het terugzetten van bewaarde antwoorden. */
			app.addEventListener( 'click', function ( e ) {
				if ( ! e.isTrusted || isVol ) { return; }
				var doel = e.target && e.target.closest ? e.target.closest( '.sfp-tool__tegel, .sfp-tool__nav .sfp-tool__knop' ) : null;
				if ( doel && ! doel.classList.contains( 'sfp-tool__tegel--hulp' ) && ! resultaat.contains( doel ) ) { openVol(); }
			} );
			app.addEventListener( 'input', function ( e ) { if ( e.isTrusted && ! resultaat.contains( e.target ) ) { openVol(); } } );
			document.addEventListener( 'keydown', function ( e ) { if ( isVol && 'Escape' === e.key && ! e.defaultPrevented ) { e.preventDefault(); sluitVol(); } } );
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
					if ( volAan ) { app.appendChild( volBalk ); }
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
