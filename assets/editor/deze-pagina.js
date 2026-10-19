/**
 * SFP Page Config - Het paneel Deze pagina in de blokeditor.
 *
 * Eén paneel in de rechterkolom met alles wat bij deze pagina hoort. Het
 * toont alleen wat hier iets doet en zegt wat een keuze verandert. De
 * gegevens per pagina worden met het bericht bewaard (meta via REST).
 *
 * Daarnaast staat op een artikel in de editor wat er vanzelf bij komt:
 * de kopkaart boven de tekst en de onderdelen eronder, zoals op de site.
 *
 * Geen buildstap: gewone JavaScript met wp.element.createElement.
 *
 * @since 3.1.0
 */
( function ( wp ) {
	if ( ! wp || ! wp.plugins || ! wp.data || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var useRef = wp.element.useRef;
	var c = wp.components;
	var data = wp.data;
	var apiFetch = wp.apiFetch;
	var S = window.sfpDezePagina || {};
	var Paneel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
	if ( ! Paneel ) {
		return;
	}

	/* =====================================================================
	 * Hulp
	 * =================================================================== */

	function slug( tekst ) {
		var s = ( wp.url && wp.url.cleanForSlug ) ? wp.url.cleanForSlug( tekst || '' ) : String( tekst || '' ).toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-|-$/g, '' );
		return s || 'deel';
	}

	function tekstVan( html ) {
		var d = document.createElement( 'div' );
		d.innerHTML = html && html.toString ? html.toString() : String( html || '' );
		return ( d.textContent || '' ).trim();
	}

	function kop( tekst ) {
		return el( 'h3', { className: 'sfp-dp-kop' }, tekst );
	}

	function melding( tekst, soort ) {
		return el( 'div', { className: 'sfp-dp-melding' + ( soort ? ' sfp-dp-melding--' + soort : '' ) }, tekst );
	}

	function hulp( tekst ) {
		return el( 'p', { className: 'sfp-dp-hulp' }, tekst );
	}

	function regel( label, waarde, fout ) {
		return el( 'div', { className: 'sfp-dp-regel' },
			el( 'span', { className: 'sfp-dp-regel__label' }, label ),
			el( 'b', { className: fout ? 'sfp-dp-fout' : '' }, waarde )
		);
	}

	/** Alle blokken van het bericht, plat. */
	function alleBlokken( blokken, uit ) {
		uit = uit || [];
		( blokken || [] ).forEach( function ( b ) {
			uit.push( b );
			if ( b.innerBlocks && b.innerBlocks.length ) {
				alleBlokken( b.innerBlocks, uit );
			}
		} );
		return uit;
	}

	/**
	 * Plekken op deze pagina waar de knop onderaan heen kan: tussenkoppen en
	 * secties. Elk met het anker dat er staat, of dat er bij kiezen komt.
	 */
	function plekken( blokken ) {
		var uit = [];
		var gebruikt = {};
		alleBlokken( blokken ).forEach( function ( b ) {
			var a = b.attributes || {};
			if ( 'core/heading' === b.name && ( a.level || 2 ) >= 2 && ( a.level || 2 ) <= 3 ) {
				var t = tekstVan( a.content );
				if ( ! t ) { return; }
				uit.push( { id: b.clientId, naam: t, anker: a.anchor || '', soort: 'kop', basis: slug( t ) } );
			} else if ( 'sfp/sectie' === b.name ) {
				var eerste = alleBlokken( b.innerBlocks ).filter( function ( x ) { return 'core/heading' === x.name; } )[ 0 ];
				var naam = eerste ? tekstVan( eerste.attributes.content ) : 'Sectie';
				uit.push( { id: b.clientId, naam: 'Sectie: ' + naam, anker: a.anker || '', soort: 'sectie', basis: slug( naam ) } );
			}
			if ( a.anchor ) { gebruikt[ a.anchor ] = true; }
			if ( a.anker ) { gebruikt[ a.anker ] = true; }
		} );
		return { lijst: uit, gebruikt: gebruikt };
	}

	/** Geef een blok een anker als het er nog geen heeft, en geef het terug. */
	function zorgVoorAnker( plek, gebruikt ) {
		if ( plek.anker ) {
			return plek.anker;
		}
		var anker = plek.basis;
		var nr = 2;
		while ( gebruikt[ anker ] ) {
			anker = plek.basis + '-' + nr++;
		}
		var zet = {};
		zet[ 'kop' === plek.soort ? 'anchor' : 'anker' ] = anker;
		data.dispatch( 'core/block-editor' ).updateBlockAttributes( plek.id, zet );
		return anker;
	}

	/** Staat er op de pagina een formulier of boekkaart waar de knop heen kan? */
	function heeftFormulier( inhoud ) {
		if ( /<!-- wp:block \{"ref":/.test( inhoud ) ) {
			return true; // een herbruikbaar blok kan een formulier bevatten: niet melden
		}
		return /wp:(sureforms|srfm)\/|\[sureforms|latepoint|<form\b|sfp-boek-sectie|id=["'](inschrijven|aanvragen)["']|"(anchor|anker)":"(inschrijven|aanvragen)"/i.test( inhoud );
	}

	/* =====================================================================
	 * Het paneel
	 * =================================================================== */

	function DezePagina() {
		var editor = data.useSelect( function ( select ) {
			var e = select( 'core/editor' );
			var be = select( 'core/block-editor' );
			return {
				id: e.getCurrentPostId(),
				type: e.getCurrentPostType(),
				status: e.getEditedPostAttribute( 'status' ),
				meta: e.getEditedPostAttribute( 'meta' ) || {},
				beeld: e.getEditedPostAttribute( 'featured_media' ),
				blokken: be.getBlocks(),
				inhoud: e.getEditedPostContent(),
				opslaan: e.isSavingPost() && ! e.isAutosavingPost(),
				vuil: e.isEditedPostDirty(),
			};
		}, [] );
		var meta = editor.meta;
		var zetMeta = function ( sleutel, waarde ) {
			var n = {};
			n[ sleutel ] = waarde;
			data.dispatch( 'core/editor' ).editPost( { meta: n } );
		};

		// Wat de server per pagina weet; opnieuw na elke keer opslaan.
		var standState = useState( null );
		var stand = standState[ 0 ];
		var wasOpslaan = useRef( false );
		useEffect( function () {
			if ( ! editor.id ) { return; }
			if ( editor.opslaan ) { wasOpslaan.current = true; return; }
			if ( stand && ! wasOpslaan.current ) { return; }
			wasOpslaan.current = false;
			apiFetch( { path: '/sfp/v1/editor/' + editor.id } ).then( function ( s ) {
				standState[ 1 ]( s );
				window.dispatchEvent( new CustomEvent( 'sfp-editor-stand', { detail: s } ) );
			} ).catch( function () {} );
		}, [ editor.id, editor.opslaan ] );

		var soortId = meta.sfp_page_type || '';
		var soort = ( S.soorten || [] ).filter( function ( s ) { return s.id === soortId; } )[ 0 ] || ( S.soorten || [] )[ 0 ] || { id: '', naam: 'Gewone pagina', uitleg: '' };
		var delen = [];

		/* ---------- Soort ---------- */
		if ( S.paginatype || soortId ) {
			delen.push( el( 'div', { key: 'soort', className: 'sfp-dp-deel' },
				el( c.SelectControl, {
					label: 'Soort pagina',
					value: soortId,
					options: ( S.soorten || [] ).map( function ( s ) { return { value: s.id, label: s.naam }; } ),
					onChange: function ( v ) { zetMeta( 'sfp_page_type', v ); },
					help: soort.uitleg,
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
				} )
			) );
		}

		/* ---------- Vorm en pijler ---------- */
		if ( ( S.pijler && 'post' === editor.type ) || ( ( S.pijler || S.begrip || S.tools ) && ( 'begrippen' === editor.type || 'tool' === editor.type ) ) ) {
			delen.push( el( Vorm, { key: 'vorm', meta: meta, zetMeta: zetMeta, editor: editor, stand: stand } ) );
		}

		/* ---------- Tool: de beschrijving (sinds 3.8.0) ---------- */
		if ( 'tool' === editor.type ) {
			delen.push( el( ToolBeschrijving, { key: 'tool', meta: meta, zetMeta: zetMeta, editor: editor, stand: stand } ) );
		}

		/* ---------- Knop onderaan ---------- */
		if ( soortId && S.knop ) {
			delen.push( el( KnopOnderaan, { key: 'knop', meta: meta, zetMeta: zetMeta, soort: soort, editor: editor } ) );
		}

		/* ---------- Training ---------- */
		if ( 'training' === soortId && S.cursusdata ) {
			var cd = stand && stand.cursusdata ? stand.cursusdata : null;
			var gebruiktNaam = /training_naam/.test( editor.inhoud ) || !! meta.training_naam;
			delen.push( el( 'div', { key: 'training', className: 'sfp-dp-deel' },
				kop( 'Training' ),
				gebruiktNaam ? el( c.TextControl, {
					label: 'Naam van de training',
					value: meta.training_naam || '',
					onChange: function ( v ) { zetMeta( 'training_naam', v ); },
					help: 'Komt terug waar de pagina de naam van de training noemt.',
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
				} ) : null,
				cd ? ( cd.groepen.length
					? el( 'div', { className: 'sfp-dp-lijst' }, cd.groepen.map( function ( g ) {
						return regel( 'Groep ' + g.nr, g.dagen + ( g.tijd ? ' (' + g.tijd + ')' : '' ) );
					} ) )
					: melding( 'Er zijn nog geen startmomenten. Het aanmeldformulier heeft dan geen groepen om uit te kiezen.', 'let' ) ) : null,
				cd && cd.verlopen ? melding( 'Alle startmomenten liggen in het verleden.', 'let' ) : null,
				el( c.ExternalLink, { href: S.beheer + '#cursusdata' }, 'Startmomenten beheren' )
			) );
		}

		/* ---------- Extra ---------- */
		var extra = [];
		if ( S.whatsapp ) {
			extra.push( el( c.ToggleControl, {
				key: 'wa',
				label: 'WhatsApp-knop',
				checked: '1' === meta.sfp_whatsapp,
				onChange: function ( v ) { zetMeta( 'sfp_whatsapp', v ? '1' : '' ); },
				help: 'Een zwevende knop waarmee de bezoeker je een bericht stuurt.',
				__nextHasNoMarginBottom: true,
			} ) );
		}
		if ( S.longread && ! soortId ) {
			var koppen = alleBlokken( editor.blokken ).filter( function ( b ) { return 'core/heading' === b.name && 2 === ( b.attributes.level || 2 ); } ).length;
			extra.push( el( c.ToggleControl, {
				key: 'lr',
				label: 'Inhoudsopgave die meeloopt',
				checked: '1' === meta.sfp_longread,
				onChange: function ( v ) { zetMeta( 'sfp_longread', v ? '1' : '' ); },
				help: 'Naast de tekst op een groot scherm, als balk onderaan op een telefoon.',
				__nextHasNoMarginBottom: true,
			} ) );
			if ( '1' === meta.sfp_longread && koppen < 2 ) {
				extra.push( el( 'div', { key: 'lr-let' }, melding( ( 1 === koppen ? 'Deze pagina heeft één tussenkop.' : 'Deze pagina heeft geen tussenkoppen.' ) + ' Een inhoudsopgave verschijnt vanaf twee.', 'let' ) ) );
			}
		}
		if ( S.affiliate ) {
			extra.push( el( c.ToggleControl, {
				key: 'aff',
				label: 'Bevat affiliatelinks',
				checked: !! meta._has_affiliate_links && '0' !== meta._has_affiliate_links,
				onChange: function ( v ) { zetMeta( '_has_affiliate_links', !! v ); },
				help: 'Zet de melding over affiliatelinks onder het bericht.',
				__nextHasNoMarginBottom: true,
			} ) );
		}
		if ( extra.length ) {
			delen.push( el( 'div', { key: 'extra', className: 'sfp-dp-deel' }, kop( 'Extra' ), extra ) );
		}

		/* ---------- Bij dit artikel ---------- */
		if ( S.artikel ) {
			delen.push( el( BijArtikel, { key: 'artikel', editor: editor, stand: stand } ) );
		}

		/* ---------- Uitsnede van de foto ---------- */
		if ( editor.beeld && stand && stand.focus ) {
			delen.push( el( Uitsnede, { key: 'focus', beeld: editor.beeld, meta: meta, zetMeta: zetMeta } ) );
		}

		/* ---------- Laten meelezen ---------- */
		if ( 'publish' !== editor.status && 'auto-draft' !== editor.status && editor.id ) {
			delen.push( el( Voorbeeldlink, { key: 'vl', id: editor.id, stand: stand } ) );
		}

		return el( Paneel, { name: 'sfp-deze-pagina', title: 'Deze pagina', className: 'sfp-deze-pagina' }, delen );
	}

	/* ---------- Vorm en pijler ---------- */

	function Vorm( props ) {
		var meta = props.meta;
		var editor = props.editor;
		var stand = props.stand || {};
		var lijsten = data.useSelect( function ( select ) {
			var kern = select( 'core' );
			return {
				categorieen: kern.getEntityRecords( 'taxonomy', 'category', { per_page: 100, orderby: 'name', order: 'asc', _fields: 'id,name,parent' } ) || [],
			};
		}, [ editor.type ] );
		var catOpties = lijsten.categorieen.map( function ( t ) {
			return { value: String( t.id ), label: ( t.parent ? '\u00a0\u00a0' : '' ) + tekstVan( t.name ) };
		} );
		var stuk = function ( label, help, sleutel, opties, getal ) {
			return el( c.SelectControl, {
				label: label,
				help: help,
				value: String( meta[ sleutel ] || ( getal ? '0' : '' ) ),
				options: opties,
				onChange: function ( v ) { props.zetMeta( sleutel, getal ? ( parseInt( v, 10 ) || 0 ) : v ); },
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} );
		};

		// Begrip en tool: bij welke pijler hoort dit?
		if ( 'post' !== editor.type ) {
			var stukken = [
				kop( 'begrippen' === editor.type ? 'Begrip' : 'Pijler' ),
				stuk( 'Hoort bij de pijler', 'Bepaalt de regel boven de titel, de kaart van de pijler en het vervolg. Leeg: bij geen pijler.', 'sfp_pijler',
					[ { value: '0', label: 'Geen pijler' } ].concat( catOpties ), true )
			];
			if ( 'begrippen' === editor.type && S.begrip ) {
				stukken.push( el( Verwant, { key: 'verwant', meta: meta, zetMeta: props.zetMeta, id: editor.id } ) );
				stukken.push( el( 'div', { key: 'verwant-hulp' }, hulp( 'Hoogstens drie. Leeg: de andere begrippen van dezelfde pijler.' ) ) );
				if ( stand.begrip ) {
					stukken.push( regel( 'Gebruikt in', stand.begrip.gebruikt + ( 1 === stand.begrip.gebruikt ? ' artikel' : ' artikelen' ) + ', komen vanzelf' ) );
					( stand.begrip.meldingen || [] ).forEach( function ( m, i ) {
						stukken.push( el( 'div', { key: 'bm' + i }, melding( m.tekst, 'let' ) ) );
					} );
				}
			}
			return el( 'div', { className: 'sfp-dp-deel' }, stukken );
		}

		var pijler = 'pijler' === meta.sfp_vorm;
		var delen = [
			kop( 'Vorm' ),
			el( c.SelectControl, {
				label: 'Dit bericht is',
				value: pijler ? 'pijler' : '',
				options: [ { value: '', label: 'Een artikel' }, { value: 'pijler', label: 'Een pijler (de gids van een categorie)' } ],
				onChange: function ( v ) { props.zetMeta( 'sfp_vorm', v ); },
				help: pijler ? 'Krijgt de kaart In this guide met alle artikelen en begrippen van de categorie.' : 'Een gewoon artikel in de artikelopmaak.',
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} ),
		];
		if ( pijler ) {
			var auto = stand.vorm && stand.vorm.automatisch ? stand.vorm.automatisch : '';
			delen.push( stuk( 'Dit is de gids van', 'Leeg: de categorie van dit bericht' + ( auto ? ' (' + auto + ')' : '' ) + '.', 'sfp_gids_van',
				[ { value: '0', label: auto ? 'Automatisch: ' + auto : 'Automatisch' } ].concat( catOpties ), true ) );
			delen.push( el( c.SelectControl, {
				label: 'Adres',
				value: '1' === meta.sfp_eigen_adres ? '1' : '',
				options: [ { value: '', label: 'Het adres van de categorie' }, { value: '1', label: 'Het eigen adres van dit bericht' } ],
				onChange: function ( v ) { props.zetMeta( 'sfp_eigen_adres', v ); },
				help: '1' === meta.sfp_eigen_adres ? 'De categorie verwijst naar dit bericht.' : 'Het eigen adres van dit bericht verwijst naar de categorie.',
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} ) );
			var zt = stand.vorm && stand.vorm.zelftests ? stand.vorm.zelftests : { tests: [], site: '' };
			var siteNaam = '';
			zt.tests.forEach( function ( t ) { if ( t.test === zt.site ) { siteNaam = t.naam; } } );
			var testOpties = [ { value: '', label: siteNaam ? 'De test van de site: ' + siteNaam : 'De test van de site (nog geen)' } ]
				.concat( zt.tests.filter( function ( t ) { return t.test !== zt.site; } ).map( function ( t ) { return { value: t.test, label: t.naam }; } ) )
				.concat( [ { value: '-', label: 'Geen zelftest' } ] );
			delen.push( stuk( 'Zelftest van de pijler', 'Uit het onderdeel Zelftest. Komt als promokaart op de plek van het boek. Bij geen zelftest: het boek.', 'sfp_zelftest', testOpties, false ) );
			if ( stand.vorm ) {
				delen.push( regel( 'Hoort onder', stand.vorm.onder ? stand.vorm.onder : 'geen pijler erboven' ) );
				if ( typeof stand.vorm.artikelen === 'number' ) {
					delen.push( regel( 'Artikelen in deze pijler', stand.vorm.artikelen + ', staan vanzelf in de kaart bovenaan' ) );
					delen.push( regel( 'Begrippen in deze pijler', stand.vorm.begrippen + ', staan vanzelf in de kaart bovenaan' ) );
				}
				if ( stand.vorm.adres ) {
					delen.push( regel( 'Staat op', stand.vorm.adres ) );
				}
			}
			( stand.vorm && stand.vorm.meldingen ? stand.vorm.meldingen : [] ).forEach( function ( m, i ) {
				delen.push( el( 'div', { key: 'm' + i }, melding( m.tekst, 'let' ) ) );
			} );
		}
		return el( 'div', { className: 'sfp-dp-deel' }, delen );
	}

	/* ---------- Tool: beschrijving (JSON) met controle ---------- */
	function ToolBeschrijving( props ) {
		var meta = props.meta, stand = props.stand || {};
		var tool = stand.tool || null;
		var tekst = meta.sfp_tool || '';
		var controleState = useState( null );
		var controle = controleState[ 0 ];
		var bezigState = useState( false );
		var bezig = bezigState[ 0 ];
		var stukken = [ kop( 'Tool' ) ];
		if ( ! S.tools ) {
			stukken.push( melding( 'Het onderdeel Tools staat uit (SFP Beheer, Onderdelen). De beschrijving kun je wel al invullen.', 'let' ) );
		}
		stukken.push( el( c.TextareaControl, {
			label: 'Beschrijving (JSON)',
			help: 'De soort, de vragen, de rekenregel en de uitslagteksten. Formaat: claude/tools-motor-ontwerp-test-2026-10-19.md. Claude schrijft dit veld via REST; teksten pas je hier aan.',
			value: tekst,
			rows: 14,
			className: 'sfp-dp-json',
			onChange: function ( v ) { props.zetMeta( 'sfp_tool', v ); controleState[ 1 ]( null ); },
			__nextHasNoMarginBottom: true,
		} ) );
		stukken.push( el( c.Button, {
			variant: 'secondary',
			isBusy: bezig,
			disabled: bezig || ! tekst.trim(),
			onClick: function () {
				bezigState[ 1 ]( true );
				apiFetch( { path: '/sfp/v1/tool/controle', method: 'POST', data: { tekst: tekst } } ).then( function ( r ) {
					controleState[ 1 ]( r ); bezigState[ 1 ]( false );
				} ).catch( function ( e ) { controleState[ 1 ]( { fouten: [ ( e && e.message ) || 'Controleren lukte niet.' ], let_op: [], samenvatting: {} } ); bezigState[ 1 ]( false ); } );
			}
		}, 'Controleren' ) );
		var uit = controle || tool;
		if ( uit ) {
			( uit.fouten || [] ).forEach( function ( f, i ) { stukken.push( el( 'div', { key: 'f' + i }, melding( f, 'fout' ) ) ); } );
			( uit.let_op || [] ).forEach( function ( f, i ) { stukken.push( el( 'div', { key: 'l' + i }, melding( f, 'let' ) ) ); } );
			var sv = uit.samenvatting || {};
			if ( sv.soort ) {
				stukken.push( regel( 'Soort', sv.soort ) );
				stukken.push( regel( 'Vragen', sv.vragen + ( sv.scorend !== sv.vragen ? ' (' + sv.scorend + ' scoren)' : '' ) ) );
				stukken.push( regel( 'Dimensies', ( sv.dimensies || [] ).join( ', ' ) || 'geen' ) );
				stukken.push( regel( 'Uitkomst', sv.regel ) );
				stukken.push( regel( 'Acties', ( sv.acties || [] ).join( ', ' ) ) );
				stukken.push( regel( 'Formulier', sv.formulier ? 'SureForms ' + sv.formulier : 'geen' ) );
				if ( ! ( uit.fouten || [] ).length && controle ) { stukken.push( melding( 'De beschrijving klopt.', 'ok' ) ); }
			}
		}
		if ( tool && tool.meldingen ) {
			tool.meldingen.forEach( function ( m, i ) {
				if ( 'tool-fout' === m.code || 0 === m.code.indexOf( 'tool-letop' ) || 'tool-leeg' === m.code ) { return; }
				stukken.push( el( 'div', { key: 'm' + i }, melding( m.tekst, 'let' ) ) );
			} );
		}
		return el( 'div', { className: 'sfp-dp-deel' }, stukken );
	}

	/* ---------- Verwante begrippen ---------- */

	function Verwant( props ) {
		var ids = ( props.meta.sfp_verwant || [] ).map( function ( x ) { return parseInt( x, 10 ); } ).filter( Boolean );
		var lijst = data.useSelect( function ( select ) {
			return select( 'core' ).getEntityRecords( 'postType', 'begrippen', { per_page: 100, status: 'publish', orderby: 'title', order: 'asc', _fields: 'id,title' } ) || [];
		}, [] );
		var naam = {};
		var id = {};
		lijst.forEach( function ( b ) {
			if ( b.id === props.id ) { return; }
			var t = tekstVan( b.title && b.title.rendered ) || ( 'Begrip ' + b.id );
			naam[ b.id ] = t;
			id[ t ] = b.id;
		} );
		return el( c.FormTokenField, {
			label: 'Verwante begrippen',
			value: ids.map( function ( i ) { return naam[ i ] || ( 'Begrip ' + i ); } ),
			suggestions: Object.keys( id ),
			maxLength: 3,
			onChange: function ( namen ) {
				props.zetMeta( 'sfp_verwant', namen.map( function ( n ) { return id[ n ]; } ).filter( Boolean ) );
			},
			__experimentalExpandOnFocus: true,
			__experimentalShowHowTo: false,
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
		} );
	}

	/* ---------- Knop onderaan ---------- */

	function KnopOnderaan( props ) {
		var meta = props.meta;
		var soort = props.soort;
		var editor = props.editor;
		var href = meta.sfp_cta_href || '';
		var paginaId = parseInt( meta.sfp_cta_pagina, 10 ) || 0;
		var keuzeState = useState( null );
		var modus = keuzeState[ 0 ] || ( paginaId ? 'pagina' : ( '#' === href ? 'form' : ( 0 === href.indexOf( '#' ) ? 'plek' : ( href ? 'link' : '' ) ) ) );
		var zetModus = function ( m ) {
			keuzeState[ 1 ]( m );
			if ( '' === m ) { props.zetMeta( 'sfp_cta_href', '' ); props.zetMeta( 'sfp_cta_pagina', 0 ); }
			if ( 'form' === m ) { props.zetMeta( 'sfp_cta_href', '#' ); props.zetMeta( 'sfp_cta_pagina', 0 ); }
			if ( 'plek' === m || 'link' === m ) { props.zetMeta( 'sfp_cta_pagina', 0 ); if ( 'link' === m && 0 === href.indexOf( '#' ) ) { props.zetMeta( 'sfp_cta_href', '' ); } }
			if ( 'pagina' === m ) { props.zetMeta( 'sfp_cta_href', '' ); }
		};

		var p = plekken( editor.blokken );
		var huidigAnker = 'plek' === modus ? href.replace( /^#/, '' ) : '';
		var plekGevonden = ! huidigAnker || p.lijst.some( function ( x ) { return ( x.anker || '' ) === huidigAnker; } );

		var zoekState = useState( '' );
		var paginas = data.useSelect( function ( select ) {
			var kern = select( 'core' );
			var lijst = zoekState[ 0 ] ? kern.getEntityRecords( 'postType', 'page', { search: zoekState[ 0 ], per_page: 10, status: 'publish', _fields: 'id,title' } ) : null;
			var gekozen = paginaId ? kern.getEntityRecord( 'postType', 'page', paginaId ) : null;
			return { lijst: lijst || [], gekozen: gekozen };
		}, [ zoekState[ 0 ], paginaId ] );
		var paginaOpties = paginas.lijst.map( function ( x ) { return { value: String( x.id ), label: tekstVan( x.title && ( x.title.rendered || x.title.raw ) ) || ( 'Pagina ' + x.id ) }; } );
		if ( paginas.gekozen && ! paginaOpties.some( function ( o ) { return o.value === String( paginaId ); } ) ) {
			paginaOpties.unshift( { value: String( paginaId ), label: tekstVan( paginas.gekozen.title && ( paginas.gekozen.title.rendered || paginas.gekozen.title.raw ) ) } );
		}

		var naarFormulier = 'form' === modus || ( '' === modus && 'form' === soort.doel );
		var linkFout = 'link' === modus && href && ! /^https?:\/\//i.test( href );

		return el( 'div', { className: 'sfp-dp-deel' },
			kop( 'Knop onderaan' ),
			el( c.TextControl, {
				label: 'Tekst op de knop',
				value: meta.sfp_cta_text || '',
				placeholder: soort.tekst,
				onChange: function ( v ) { props.zetMeta( 'sfp_cta_text', v ); },
				help: 'Leeg: de tekst van deze site voor ' + soort.naam.toLowerCase() + '.',
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} ),
			el( c.SelectControl, {
				label: 'De knop gaat naar',
				value: modus,
				options: [
					{ value: '', label: 'Zoals voor ' + soort.naam.toLowerCase() + ( soort.naar ? ': ' + soort.naar : '' ) },
					{ value: 'form', label: 'Het formulier op deze pagina' },
					{ value: 'plek', label: 'Een tussenkop of sectie van deze pagina' },
					{ value: 'pagina', label: 'Een pagina van de site' },
					{ value: 'link', label: 'Een andere link' },
				],
				onChange: zetModus,
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} ),
			'plek' === modus ? el( c.SelectControl, {
				label: 'Welke plek',
				value: huidigAnker && plekGevonden ? p.lijst.filter( function ( x ) { return x.anker === huidigAnker; } )[ 0 ].id : '',
				options: [ { value: '', label: p.lijst.length ? 'Kies een plek' : 'Deze pagina heeft nog geen tussenkoppen' } ].concat( p.lijst.map( function ( x ) { return { value: x.id, label: x.naam }; } ) ),
				onChange: function ( id ) {
					var plek = p.lijst.filter( function ( x ) { return x.id === id; } )[ 0 ];
					props.zetMeta( 'sfp_cta_href', plek ? '#' + zorgVoorAnker( plek, p.gebruikt ) : '' );
				},
				help: 'Hernoem je de kop, dan blijft de knop er heen gaan.',
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} ) : null,
			'plek' === modus && ! plekGevonden ? melding( 'De plek waar de knop heen ging, staat niet meer op de pagina. Kies een andere.', 'let' ) : null,
			'pagina' === modus ? el( c.ComboboxControl, {
				label: 'Welke pagina',
				value: paginaId ? String( paginaId ) : '',
				options: paginaOpties,
				onFilterValueChange: function ( v ) { zoekState[ 1 ]( v ); },
				onChange: function ( v ) { props.zetMeta( 'sfp_cta_pagina', v ? parseInt( v, 10 ) : 0 ); },
				help: 'Typ om te zoeken.',
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} ) : null,
			'link' === modus ? el( c.TextControl, {
				label: 'Link',
				type: 'url',
				value: href,
				placeholder: 'Plak de link',
				onChange: function ( v ) { props.zetMeta( 'sfp_cta_href', v.trim() ); },
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} ) : null,
			linkFout ? melding( 'Een link begint met https://. Voor een pagina van de site kies je Een pagina van de site.', 'let' ) : null,
			naarFormulier && ! heeftFormulier( editor.inhoud ) ? melding( 'Op deze pagina staat geen formulier. Zonder formulier verschijnt de knop niet.', 'let' ) : null
		);
	}

	/* ---------- Bij dit artikel ---------- */

	function BijArtikel( props ) {
		var editor = props.editor;
		var stand = props.stand || {};
		var blokken = alleBlokken( editor.blokken );
		var vragen = blokken.filter( function ( b ) { return 'sfp/warming-up-vraag' === b.name; } ).length;
		var heeftDeelbeeld = blokken.some( function ( b ) { return 'sfp/inzicht' === b.name || 'sfp/samenvatting' === b.name; } );

		return el( Fragment, null,
			el( 'div', { className: 'sfp-dp-deel' },
				kop( 'Bij dit artikel' ),
				el( 'div', { className: 'sfp-dp-lijst' },
					regel( 'Pijler', stand.pijler || 'geen', ! stand.pijler ),
					regel( 'Onderwerpen', stand.onderwerpen && stand.onderwerpen.length ? stand.onderwerpen.join( ', ' ) : 'geen' ),
					regel( 'Warming-up', vragen ? ( 1 === vragen ? '1 vraag' : vragen + ' vragen' ) : 'geen' ),
					regel( 'Vervolg', stand.vervolg || 'geen' ),
					regel( 'Uitgelichte afbeelding', editor.beeld ? 'ingesteld' : 'ontbreekt', ! editor.beeld ),
					stand.promo ? regel( 'Promokaart', 'naast het artikel' ) : null
				),
				hulp( 'Pijler en onderwerpen kies je bij Categorieën en Tags.' )
			),
			heeftDeelbeeld ? el( Deelbeeld, { id: editor.id, vuil: editor.vuil, opslaan: editor.opslaan } ) : null
		);
	}

	/* ---------- Beeld om te delen ---------- */

	function Deelbeeld( props ) {
		var st = useState( { beelden: null, bezig: false, fout: '' } );
		var s = st[ 0 ];
		var zet = function ( n ) { st[ 1 ]( Object.assign( {}, s, n ) ); };
		var lees = function () {
			apiFetch( { path: '/sfp/v1/deelbeeld/' + props.id } ).then( function ( r ) {
				st[ 1 ]( { beelden: r.beelden || {}, bezig: false, fout: r.stand && r.stand.laatste_fout ? String( r.stand.laatste_fout ) : '' } );
			} ).catch( function () {} );
		};
		useEffect( function () { if ( ! props.opslaan ) { lees(); } }, [ props.id, props.opslaan ] );

		var lijst = s.beelden ? Object.keys( s.beelden ).map( function ( k ) { return s.beelden[ k ]; } ) : [];
		var oud = lijst.filter( function ( b ) { return ! b.actueel; } ).length;

		return el( 'div', { className: 'sfp-dp-deel' },
			kop( 'Beeld om te delen' ),
			lijst.length ? el( 'div', { className: 'sfp-dp-beelden' }, lijst.map( function ( b, i ) {
				return b.url ? el( 'img', { key: i, src: b.url, alt: '' } ) : null;
			} ) ) : ( s.beelden ? hulp( 'Nog geen beeld. Het wordt gemaakt als je het artikel opslaat.' ) : null ),
			oud ? melding( 'Een beeld hoort niet meer bij de tekst. Maak het opnieuw.', 'let' ) : null,
			s.fout && ( ! lijst.length || oud ) ? melding( 'Het maken lukte niet: ' + s.fout, 'let' ) : null,
			props.vuil ? hulp( 'Sla eerst op: het beeld wordt gemaakt van de opgeslagen tekst.' ) : null,
			el( c.Button, {
				variant: 'secondary',
				isBusy: s.bezig,
				disabled: s.bezig || props.vuil,
				onClick: function () {
					zet( { bezig: true } );
					apiFetch( { path: '/sfp/v1/deelbeeld/' + props.id, method: 'POST' } ).then( function ( r ) {
						var v = r.verslag || {};
						var fout = v.reden && ( ( v.mislukt && v.mislukt.length ) || ! ( v.gemaakt && v.gemaakt.length ) ) ? String( v.reden ) : '';
						st[ 1 ]( { beelden: r.beelden || {}, bezig: false, fout: fout } );
					} ).catch( function ( e ) {
						st[ 1 ]( { beelden: s.beelden, bezig: false, fout: ( e && e.message ) || 'geen verbinding' } );
					} );
				},
			}, 'Opnieuw maken' )
		);
	}

	/* ---------- Uitsnede van de foto ---------- */

	function Uitsnede( props ) {
		var media = data.useSelect( function ( select ) { return select( 'core' ).getMedia( props.beeld ); }, [ props.beeld ] );
		var url = media ? ( ( media.media_details && media.media_details.sizes && media.media_details.sizes.medium && media.media_details.sizes.medium.source_url ) || media.source_url ) : '';
		var x = typeof props.meta.sfp_hero_focal_x === 'number' ? props.meta.sfp_hero_focal_x : 50;
		var y = typeof props.meta.sfp_hero_focal_y === 'number' ? props.meta.sfp_hero_focal_y : 50;
		return el( 'div', { className: 'sfp-dp-deel' },
			kop( 'Uitsnede van de foto' ),
			hulp( 'Klik op het onderwerp in de foto. Zo blijft het op een telefoon in beeld.' ),
			url ? el( 'div', {
				className: 'sfp-dp-foto',
				role: 'button',
				tabIndex: 0,
				'aria-label': 'Kies het onderwerp in de foto',
				onClick: function ( e ) {
					var r = e.currentTarget.getBoundingClientRect();
					props.zetMeta( 'sfp_hero_focal_x', Math.round( Math.min( 100, Math.max( 1, ( e.clientX - r.left ) / r.width * 100 ) ) ) );
					props.zetMeta( 'sfp_hero_focal_y', Math.round( Math.min( 100, Math.max( 1, ( e.clientY - r.top ) / r.height * 100 ) ) ) );
				},
			},
				el( 'img', { src: url, alt: '', draggable: false } ),
				el( 'span', { className: 'sfp-dp-foto__punt', style: { left: x + '%', top: y + '%' } } )
			) : null,
			el( c.Button, { variant: 'link', onClick: function () { props.zetMeta( 'sfp_hero_focal_x', 50 ); props.zetMeta( 'sfp_hero_focal_y', 50 ); } }, 'Terug naar het midden' )
		);
	}

	/* ---------- Laten meelezen ---------- */

	function Voorbeeldlink( props ) {
		var st = useState( undefined );
		var link = undefined === st[ 0 ] ? ( props.stand ? props.stand.voorbeeld : null ) : st[ 0 ];
		var bezigState = useState( false );
		var gekopieerd = useState( false );
		var maak = function () {
			bezigState[ 1 ]( true );
			apiFetch( { path: '/sfp/v1/voorbeeld/' + props.id, method: 'POST' } ).then( function ( r ) {
				st[ 1 ]( r );
				bezigState[ 1 ]( false );
			} ).catch( function () { bezigState[ 1 ]( false ); } );
		};
		var trekIn = function () {
			apiFetch( { path: '/sfp/v1/voorbeeld/' + props.id, method: 'DELETE' } ).then( function () { st[ 1 ]( null ); } ).catch( function () {} );
		};
		return el( 'div', { className: 'sfp-dp-deel' },
			kop( 'Laten meelezen' ),
			hulp( 'Een link waarmee iemand dit concept zonder inloggen bekijkt, veertien dagen lang.' ),
			link ? el( Fragment, null,
				el( 'p', { className: 'sfp-dp-link' }, link.url ),
				hulp( 'Geldig tot ' + link.tot + '.' ),
				el( 'div', { className: 'sfp-dp-knoppen' },
					el( c.Button, {
						variant: 'secondary',
						onClick: function () {
							if ( navigator.clipboard ) {
								navigator.clipboard.writeText( link.url ).then( function () { gekopieerd[ 1 ]( true ); setTimeout( function () { gekopieerd[ 1 ]( false ); }, 2000 ); } );
							}
						},
					}, gekopieerd[ 0 ] ? 'Gekopieerd' : 'Kopieer' ),
					el( c.Button, { variant: 'tertiary', isDestructive: true, onClick: trekIn }, 'Intrekken' )
				)
			) : el( c.Button, { variant: 'secondary', isBusy: bezigState[ 0 ], disabled: bezigState[ 0 ], onClick: maak }, 'Maak een voorbeeldlink' )
		);
	}

	wp.plugins.registerPlugin( 'sfp-deze-pagina', { render: DezePagina, icon: null } );

	/* =====================================================================
	 * In de editor: wat er vanzelf bij een artikel komt
	 * =================================================================== */

	if ( ! S.artikel ) {
		return;
	}

	var omlijsting = { kop: '', voet: '' };

	function canvas() {
		var iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
		try {
			return iframe ? iframe.contentDocument : document;
		} catch ( e ) {
			return null;
		}
	}

	function vak( doc, id, label, html ) {
		var bestaand = doc.getElementById( id );
		if ( ! html ) {
			if ( bestaand ) { bestaand.remove(); }
			return null;
		}
		if ( bestaand && bestaand.sfpHtml === html ) {
			return bestaand;
		}
		var div = bestaand || doc.createElement( 'div' );
		div.id = id;
		div.className = 'sfp-vanzelf sfp-artikel';
		div.setAttribute( 'contenteditable', 'false' );
		div.setAttribute( 'aria-hidden', 'true' );
		div.sfpHtml = html;
		div.innerHTML = '<span class="sfp-vanzelf__label">' + label + '</span>' + html;
		return div;
	}

	function plaats() {
		var doc = canvas();
		if ( ! doc || ! doc.body ) { return; }
		var titel = doc.querySelector( '.editor-visual-editor__post-title-wrapper' );
		var wortel = doc.querySelector( '.is-root-container' );
		if ( titel ) {
			var boven = vak( doc, 'sfp-vanzelf-kop', 'Komt vanzelf, zoals bij de laatste keer opslaan', omlijsting.kop );
			if ( boven && titel.nextSibling !== boven ) { titel.parentNode.insertBefore( boven, titel.nextSibling ); }
		}
		if ( wortel ) {
			var onder = vak( doc, 'sfp-vanzelf-voet', 'Komt vanzelf onder het artikel', omlijsting.voet );
			if ( onder && wortel.nextSibling !== onder ) { wortel.parentNode.insertBefore( onder, wortel.nextSibling ); }
		}
	}

	window.addEventListener( 'sfp-editor-stand', function ( e ) {
		omlijsting = { kop: ( e.detail && e.detail.kop ) || '', voet: ( e.detail && e.detail.voet ) || '' };
		plaats();
	} );

	// De editor bouwt het kanvas soms opnieuw op (wisselen van weergave,
	// het iframe dat laadt): zet de vakken dan terug.
	var wacht = null;
	data.subscribe( function () {
		if ( wacht || ( ! omlijsting.kop && ! omlijsting.voet ) ) { return; }
		wacht = setTimeout( function () { wacht = null; plaats(); }, 400 );
	} );
}( window.wp ) );
