/**
 * SFP Page Config - Bouwstenen in de blokeditor.
 *
 * De blokken zijn in PHP geregistreerd (naam, attributen, weergave); dit
 * script levert alleen het bewerken. Opslaan bewaart de binnenblokken; de
 * omhullende HTML maakt de server bij het tonen.
 *
 * Sinds 3.1.0 volgens de ontwerpregels van SFP Beheer: tekst die de lezer
 * ziet typ je in het blok, een keuze heet naar wat de lezer ziet en toont
 * een klein plaatje, een instelling staat er alleen als hij iets doet,
 * ankers maakt de plugin zelf en schrijfregels telt het blok.
 *
 * Geen buildstap: gewone JavaScript met wp.element.createElement.
 *
 * @since 2.11.0
 */
( function ( wp ) {
	if ( ! wp || ! wp.blocks || ! wp.blockEditor ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var be = wp.blockEditor;
	var c = wp.components;
	var InnerBlocks = be.InnerBlocks;
	var useBlockProps = be.useBlockProps;
	var useInnerBlocksProps = be.useInnerBlocksProps;
	var InspectorControls = be.InspectorControls;
	var RichText = be.RichText;
	var ServerSideRender = wp.serverSideRender;

	function opslaan() {
		return el( InnerBlocks.Content );
	}

	function paneel( titel, kinderen ) {
		return el( InspectorControls, null, el( c.PanelBody, { title: titel, initialOpen: true }, kinderen ) );
	}

	function keuze( label, waarde, opties, zet, hulp ) {
		return el( c.SelectControl, {
			key: 'keuze-' + label,
			label: label,
			value: waarde,
			options: opties,
			onChange: zet,
			help: hulp,
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
		} );
	}

	function schakelaar( label, waarde, zet, hulp ) {
		return el( c.ToggleControl, {
			key: 'schakel-' + label,
			label: label,
			checked: !! waarde,
			onChange: zet,
			help: hulp,
			__nextHasNoMarginBottom: true,
		} );
	}

	function tekstveld( label, waarde, zet, hulp ) {
		return el( c.TextControl, {
			key: 'tekst-' + label,
			label: label,
			value: waarde || '',
			onChange: zet,
			help: hulp,
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
		} );
	}

	function uitleg( tekst ) {
		return el( 'p', { key: 'uitleg-' + tekst, className: 'components-base-control__help' }, tekst );
	}

	/**
	 * Een keuze met een klein plaatje van wat de lezer ziet (ontwerpregel 20).
	 *
	 * @param {string}   label  Naam van de keuze.
	 * @param {string}   waarde Gekozen waarde.
	 * @param {Array}    opties { value, label, vorm } met vorm een rij van
	 *                          vlakken: 'n' naast elkaar, 'o' onder elkaar,
	 *                          gevolgd door verhoudingen, bijvoorbeeld 'n:1,1'.
	 * @param {Function} zet    Bij kiezen.
	 */
	function vormkeuze( label, waarde, opties, zet ) {
		return el( 'div', { key: 'vorm-' + label, className: 'sfp-vormkeuze' },
			el( 'span', { className: 'sfp-vormkeuze__label' }, label ),
			el( 'div', { className: 'sfp-vormkeuze__rij', role: 'radiogroup', 'aria-label': label },
				opties.map( function ( o ) {
					var delen = ( o.vorm || 'n:1' ).split( ':' );
					var richting = delen[ 0 ];
					var maten = ( delen[ 1 ] || '1' ).split( ',' );
					return el( 'button', {
						key: o.value,
						type: 'button',
						role: 'radio',
						'aria-checked': waarde === o.value ? 'true' : 'false',
						className: 'sfp-vormkeuze__optie' + ( waarde === o.value ? ' is-gekozen' : '' ),
						onClick: function () { zet( o.value ); },
					},
						el( 'span', { className: 'sfp-vormkeuze__mini sfp-vormkeuze__mini--' + richting + ( o.donker ? ' sfp-vormkeuze__mini--donker' : '' ) + ( o.rand ? ' sfp-vormkeuze__mini--rand' : '' ) },
							maten.map( function ( m, i ) { return el( 'i', { key: i, style: { flexGrow: parseFloat( m ) || 1 } } ); } )
						),
						el( 'span', null, o.label )
					);
				} )
			)
		);
	}

	/** Maak van een tekst een anker zoals de site het gebruikt. */
	function maakSlug( tekst ) {
		var d = document.createElement( 'div' );
		d.innerHTML = tekst && tekst.toString ? tekst.toString() : String( tekst || '' );
		var t = ( d.textContent || '' ).trim();
		var s = ( wp.url && wp.url.cleanForSlug ) ? wp.url.cleanForSlug( t ) : t.toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-|-$/g, '' );
		return s || 'deel';
	}

	/** Alle ankers die al op de pagina staan. */
	function gebruikteAnkers() {
		var uit = {};
		( function loop( blokken ) {
			( blokken || [] ).forEach( function ( b ) {
				if ( b.attributes ) {
					if ( b.attributes.anchor ) { uit[ b.attributes.anchor ] = true; }
					if ( b.attributes.anker ) { uit[ b.attributes.anker ] = true; }
				}
				loop( b.innerBlocks );
			} );
		}( wp.data.select( 'core/block-editor' ).getBlocks() ) );
		return uit;
	}

	/**
	 * "Link naar dit blok": de plugin maakt het anker zelf (ontwerpregel 7).
	 * Een bestaand anker blijft staan, zodat links ernaar blijven werken.
	 *
	 * @param {Object}   props Blokprops.
	 * @param {Function} bron  Geeft de tekst waar het anker van gemaakt wordt.
	 */
	function linkNaarBlok( props, bron ) {
		return el( LinkNaarBlok, { key: 'link-naar-blok', props: props, bron: bron } );
	}
	function LinkNaarBlok( p ) {
		var props = p.props;
		var anker = props.attributes.anker || '';
		var gekopieerd = useState( false );
		var basis = wp.data.select( 'core/editor' ) ? wp.data.select( 'core/editor' ).getPermalink() : '';
		var kopieer = function () {
			var a = anker;
			if ( ! a ) {
				var gebruikt = gebruikteAnkers();
				var b = maakSlug( p.bron() );
				a = b;
				var nr = 2;
				while ( gebruikt[ a ] ) { a = b + '-' + nr++; }
				props.setAttributes( { anker: a } );
			}
			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( ( basis || '' ) + '#' + a ).then( function () {
					gekopieerd[ 1 ]( true );
					setTimeout( function () { gekopieerd[ 1 ]( false ); }, 2000 );
				} );
			}
		};
		return el( 'div', { className: 'sfp-linkblok' },
			el( 'span', { className: 'sfp-vormkeuze__label' }, 'Link naar dit blok' ),
			el( c.Button, { variant: 'secondary', onClick: kopieer }, gekopieerd[ 0 ] ? 'Gekopieerd' : 'Kopieer de link' ),
			uitleg( 'Plak hem bij een link of knop. Hernoem je de kop, dan blijft de link werken.' )
		);
	}

	/** Teller onder een tekst (ontwerpregel 22). */
	function teller( tekst, over ) {
		return el( 'span', { className: 'sfp-teller' + ( over ? ' sfp-teller--over' : '' ), 'aria-live': 'polite' }, tekst );
	}

	function tekens( html ) {
		var d = document.createElement( 'div' );
		d.innerHTML = html && html.toString ? html.toString() : String( html || '' );
		return ( d.textContent || '' ).trim().length;
	}

	/**
	 * Registreer het bewerken van een blok dat in PHP bestaat.
	 *
	 * @param {string}   naam Bloknaam.
	 * @param {Function} edit Bewerkcomponent.
	 */
	function blok( naam, edit ) {
		var bestaand = wp.blocks.getBlockType( naam );
		if ( bestaand ) {
			wp.blocks.unregisterBlockType( naam );
		}
		wp.blocks.registerBlockType( naam, {
			apiVersion: 3,
			edit: edit,
			save: opslaan,
		} );
	}

	/** De eerste kop binnen een blok, voor een anker. */
	function eersteKop( clientId ) {
		var vind = function ( blokken ) {
			for ( var i = 0; i < blokken.length; i++ ) {
				if ( 'core/heading' === blokken[ i ].name ) { return blokken[ i ].attributes.content; }
				var x = vind( blokken[ i ].innerBlocks || [] );
				if ( x ) { return x; }
			}
			return '';
		};
		return vind( wp.data.select( 'core/block-editor' ).getBlocks( clientId ) );
	}

	/* sfp/sectie */
	blok( 'sfp/sectie', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-sectie' + ( 'tint' === a.achtergrond ? ' sfp-sectie--tint' : '' ) } );
		var inner = useInnerBlocksProps( { className: 'sfp-sectie__binnen' }, { template: [ [ 'core/heading', { level: 2 } ], [ 'core/paragraph' ] ] } );
		return el( Fragment, null,
			paneel( 'Sectie', [
				vormkeuze( 'Achtergrond', a.achtergrond, [ { label: 'Wit', value: 'wit', vorm: 'n:1' }, { label: 'Tint', value: 'tint', vorm: 'n:1', donker: true } ], function ( v ) { props.setAttributes( { achtergrond: v } ); } ),
				linkNaarBlok( props, function () { return eersteKop( props.clientId ) || 'sectie'; } ),
			] ),
			el( 'section', blockProps, el( 'div', inner ) )
		);
	} );

	/* sfp/held */
	blok( 'sfp/held', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-held sfp-held--recht' + ( 'breed' === a.variant ? ' sfp-held--breed' : '' ) } );
		var inner = useInnerBlocksProps( { className: 'sfp-held__binnen' }, {
			template: [
				[ 'core/heading', { level: 1 } ],
				[ 'core/paragraph', { className: 'is-style-sfp-intro' } ],
				[ 'core/image', {} ],
				[ 'core/buttons', {}, [ [ 'core/button' ], [ 'core/button', { className: 'is-style-sfp-licht' } ] ] ],
			],
		} );
		return el( Fragment, null,
			paneel( 'Bovenkant van de pagina', [
				vormkeuze( 'Beeld', a.variant, [ { label: 'Tekst naast beeld', value: 'recht', vorm: 'n:1.2,1' }, { label: 'Beeld over de volle breedte', value: 'breed', vorm: 'o:1' } ], function ( v ) { props.setAttributes( { variant: v } ); } ),
				uitleg( 'Titel, intro, beeld en knoppen typ je in het blok zelf. Op een telefoon valt de intro weg.' ),
				a.anker ? linkNaarBlok( props, function () { return a.anker; } ) : null,
			] ),
			el( 'section', blockProps, el( 'div', inner ) )
		);
	} );

	/* sfp/kolommen */
	var indelingen = [
		{ label: 'Twee gelijk', value: 'twee', vorm: 'n:1,1' },
		{ label: 'Drie gelijk', value: 'drie', vorm: 'n:1,1,1' },
		{ label: 'Tekst en beeld', value: 'tekst-beeld', vorm: 'n:1.2,1' },
		{ label: 'Tekst en boek', value: 'tekst-boek', vorm: 'n:1.4,1' },
		{ label: 'Tekst en boekkaart', value: 'boeking', vorm: 'n:1,1.1' },
		{ label: 'Breed en smal', value: 'breed-smal', vorm: 'n:1.2,1' },
	];
	blok( 'sfp/kolommen', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-kolommen sfp-kolommen--' + a.indeling } );
		var inner = useInnerBlocksProps( blockProps, { orientation: 'horizontal', template: [ [ 'sfp/kolom' ], [ 'sfp/kolom' ] ] } );
		return el( Fragment, null,
			paneel( 'Kolommen', [
				vormkeuze( 'Indeling', a.indeling, indelingen, function ( v ) { props.setAttributes( { indeling: v } ); } ),
				uitleg( 'Op een telefoon staan de kolommen onder elkaar.' ),
			] ),
			el( 'div', inner )
		);
	} );

	/* sfp/kolom */
	blok( 'sfp/kolom', function () {
		var blockProps = useBlockProps( { className: 'sfp-kolom' } );
		var inner = useInnerBlocksProps( blockProps, { template: [ [ 'core/paragraph' ] ] } );
		return el( 'div', inner );
	} );

	/* sfp/kaart */
	blok( 'sfp/kaart', function ( props ) {
		var a = props.attributes;
		var klassen = 'sfp-kaart' + ( 'standaard' !== a.variant ? ' sfp-kaart--' + a.variant : '' ) + ( a.ruim ? ' sfp-kaart--ruim' : '' ) + ( a.smal ? ' sfp-kaart--smal' : '' );
		var blockProps = useBlockProps( { className: klassen } );
		var inner = useInnerBlocksProps( blockProps, { template: [ [ 'core/heading', { level: 3 } ], [ 'core/paragraph' ] ] } );
		return el( Fragment, null,
			paneel( 'Kaart', [
				vormkeuze( 'Vorm', a.variant, [ { label: 'Met accentrand', value: 'standaard', vorm: 'n:1', rand: true }, { label: 'Vlak', value: 'vlak', vorm: 'n:1' }, { label: 'Donker', value: 'donker', vorm: 'n:1', donker: true } ], function ( v ) { props.setAttributes( { variant: v } ); } ),
				schakelaar( 'Ruim', a.ruim, function ( v ) { props.setAttributes( { ruim: v } ); }, 'Meer ruimte rond de inhoud op een groot scherm.' ),
				schakelaar( 'Smal en in het midden', a.smal, function ( v ) { props.setAttributes( { smal: v } ); }, 'Bijvoorbeeld voor een formulier.' ),
				el( 'div', { key: 'link', className: 'sfp-linkveld' },
					el( 'span', { className: 'sfp-vormkeuze__label' }, 'De hele kaart gaat naar' ),
					el( be.URLInput, {
						value: a.link || '',
						onChange: function ( v ) { props.setAttributes( { link: v } ); },
						placeholder: 'Zoek een pagina of plak een link',
						__nextHasNoMarginBottom: true,
					} ),
					uitleg( 'Leeg: alleen de links in de kaart zijn klikbaar.' )
				),
			] ),
			el( 'div', inner )
		);
	} );

	/* sfp/stappen en sfp/stap */
	blok( 'sfp/stappen', function () {
		var blockProps = useBlockProps( { className: 'sfp-stappen' } );
		var inner = useInnerBlocksProps( blockProps, { allowedBlocks: [ 'sfp/stap' ], template: [ [ 'sfp/stap' ], [ 'sfp/stap' ], [ 'sfp/stap' ] ] } );
		return el( 'ol', inner );
	} );
	blok( 'sfp/stap', function () {
		var blockProps = useBlockProps( { className: 'sfp-stap' } );
		var inner = useInnerBlocksProps( blockProps, { template: [ [ 'core/heading', { level: 3 } ], [ 'core/paragraph' ] ] } );
		return el( 'li', inner );
	} );

	/* sfp/uitklap en sfp/uitklap-regel */
	var uitklapVormen = [
		{ label: 'Grote regels', value: 'groot', vorm: 'o:1,1' },
		{ label: 'Lees meer', value: 'compact', vorm: 'o:1' },
		{ label: 'Kaarten', value: 'kaart', vorm: 'n:1,1' },
		{ label: 'Verhaal', value: 'verhaal', vorm: 'o:.4,1' },
	];
	var uitklapUitleg = {
		groot: 'Elke regel is een brede balk met de titel.',
		compact: 'Eén regel die de rest van de tekst laat zien.',
		kaart: 'Regels als kaarten, met een regel onder de titel.',
		verhaal: 'Een verhaal in een artikel, met een label boven de titel.',
	};
	blok( 'sfp/uitklap', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-uitklap sfp-uitklap--' + a.variant } );
		var inner = useInnerBlocksProps( blockProps, { allowedBlocks: [ 'sfp/uitklap-regel' ], template: [ [ 'sfp/uitklap-regel' ] ] } );
		return el( Fragment, null,
			paneel( 'Uitklap', [
				vormkeuze( 'Vorm', a.variant, uitklapVormen, function ( v ) { props.setAttributes( { variant: v } ); } ),
				uitleg( uitklapUitleg[ a.variant ] || '' ),
				schakelaar( 'Eén regel tegelijk open', a.eenTegelijk, function ( v ) { props.setAttributes( { eenTegelijk: v } ); } ),
			] ),
			el( 'div', inner )
		);
	} );
	blok( 'sfp/uitklap-regel', function ( props ) {
		var a = props.attributes;
		var variant = wp.data.useSelect( function ( select ) {
			var bovenId = select( 'core/block-editor' ).getBlockRootClientId( props.clientId );
			var boven = bovenId ? select( 'core/block-editor' ).getBlock( bovenId ) : null;
			return boven && boven.attributes ? boven.attributes.variant : 'groot';
		}, [ props.clientId ] );
		var blockProps = useBlockProps( { className: 'sfp-editor-regel' } );
		var inner = useInnerBlocksProps( { className: 'sfp-uitklap__inhoud' }, { template: [ [ 'core/paragraph' ] ] } );
		// Label en regel onder de titel typ je in het blok, alleen in de vorm die ze toont.
		var metLabel = 'verhaal' === variant || a.label;
		var metSub = 'kaart' === variant || a.sub;
		return el( Fragment, null,
			paneel( 'Uitklapregel', [
				linkNaarBlok( props, function () { return a.titel || 'regel'; } ),
				uitleg( 'Een link naar deze regel opent hem.' ),
			] ),
			el( 'div', blockProps,
				metLabel ? el( RichText, { tagName: 'small', className: 'sfp-blok-label', value: a.label, placeholder: 'Label boven de titel, bijvoorbeeld: Een typisch geval', allowedFormats: [], onChange: function ( v ) { props.setAttributes( { label: v } ); } } ) : null,
				el( RichText, { tagName: 'div', value: a.titel, placeholder: 'Titel van de regel', allowedFormats: [ 'core/bold', 'core/italic' ], onChange: function ( v ) { props.setAttributes( { titel: v } ); } } ),
				metSub ? el( RichText, { tagName: 'div', className: 'sfp-uitklap__sub', value: a.sub, placeholder: 'Regel onder de titel', allowedFormats: [], onChange: function ( v ) { props.setAttributes( { sub: v } ); } } ) : null,
				el( 'div', inner )
			)
		);
	} );

	/* sfp/tabs en sfp/tab */
	blok( 'sfp/tabs', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-tabs' } );
		var inner = useInnerBlocksProps( blockProps, { allowedBlocks: [ 'sfp/tab' ], template: [ [ 'sfp/tab' ], [ 'sfp/tab' ] ] } );
		return el( Fragment, null,
			paneel( 'Tabs', [
				tekstveld( 'Naam voor schermlezers', a.label, function ( v ) { props.setAttributes( { label: v } ); }, 'Wordt voorgelezen, niet getoond. Bijvoorbeeld: Drie bronnen van overtuiging.' ),
			] ),
			el( 'div', inner )
		);
	} );
	blok( 'sfp/tab', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-editor-regel' } );
		var inner = useInnerBlocksProps( { className: 'sfp-tabpaneel' }, { template: [ [ 'core/paragraph', { className: 'is-style-sfp-groot' } ], [ 'core/paragraph' ] ] } );
		return el( 'div', blockProps,
			el( 'span', { className: 'sfp-editor-label' }, 'Tabblad' ),
			el( RichText, { tagName: 'div', value: a.titel, placeholder: 'Naam van het tabblad', allowedFormats: [], onChange: function ( v ) { props.setAttributes( { titel: v } ); } } ),
			el( 'div', inner )
		);
	} );

	/* sfp/lijst */
	blok( 'sfp/lijst', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-lijst sfp-lijst--' + a.stijl } );
		var inner = useInnerBlocksProps( blockProps, { allowedBlocks: [ 'core/list' ], template: [ [ 'core/list' ] ], templateLock: 'insert' } );
		return el( Fragment, null,
			paneel( 'Lijst', [
				keuze( 'Teken voor elk punt', a.stijl, [ { label: 'Vink', value: 'vink' }, { label: 'Kruis', value: 'kruis' }, { label: 'Nummers', value: 'nummers' }, { label: 'Regels', value: 'regels' }, { label: 'Letters', value: 'letters' } ], function ( v ) { props.setAttributes( { stijl: v } ); }, 'letters' === a.stijl ? 'Begin elk punt met een vette letter.' : undefined ),
			] ),
			el( 'div', inner )
		);
	} );

	/* sfp/faq en sfp/faq-vraag */
	blok( 'sfp/faq', function () {
		var blockProps = useBlockProps( { className: 'sfp-faq' } );
		var inner = useInnerBlocksProps( blockProps, { allowedBlocks: [ 'sfp/faq-vraag' ], template: [ [ 'sfp/faq-vraag' ] ] } );
		return el( 'div', inner );
	} );
	blok( 'sfp/faq-vraag', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-faq__vraag sfp-editor-vraag' } );
		var inner = useInnerBlocksProps( { className: 'sfp-faq__antwoord' }, { template: [ [ 'core/paragraph' ] ] } );
		return el( 'div', blockProps,
			el( RichText, { tagName: 'div', className: 'sfp-editor-vraagtekst', value: a.vraag, placeholder: 'Vraag', allowedFormats: [], onChange: function ( v ) { props.setAttributes( { vraag: v } ); }, style: { padding: '18px 22px' } } ),
			el( 'div', inner )
		);
	} );

	/* =====================================================================
	 * Artikelblokken (sinds 2.12.0)
	 * ================================================================== */

	var teksten = ( window.sfpBlokken && window.sfpBlokken.teksten ) || {};
	function t( sleutel, terugval ) {
		return teksten[ sleutel ] || terugval;
	}

	/* sfp/samenvatting */
	var SAMENVATTING_PUNTEN = 4;
	var SAMENVATTING_TEKENS = 70;
	blok( 'sfp/samenvatting', function ( props ) {
		var a = props.attributes;
		var punten = wp.data.useSelect( function ( select ) {
			var lijst = select( 'core/block-editor' ).getBlocks( props.clientId ).filter( function ( b ) { return 'core/list' === b.name; } )[ 0 ];
			return lijst ? lijst.innerBlocks.map( function ( li ) { return tekens( li.attributes.content ); } ) : [];
		}, [ props.clientId ] );
		var gevuld = punten.filter( function ( n ) { return n > 0; } );
		var langste = gevuld.length ? Math.max.apply( null, gevuld ) : 0;
		var teVeel = gevuld.length > SAMENVATTING_PUNTEN;
		var teLang = langste > SAMENVATTING_TEKENS;
		var blockProps = useBlockProps( { className: 'sfp-samenvatting' } );
		var inner = useInnerBlocksProps( {}, { allowedBlocks: [ 'core/list' ], template: [ [ 'core/list' ] ], templateLock: 'insert' } );
		return el( Fragment, null,
			paneel( 'Samenvatting', [
				tekstveld( 'Onderwerp op het beeld om te delen', a.onderwerp, function ( v ) { props.setAttributes( { onderwerp: v } ); }, 'Leeg: de titel van het artikel.' ),
				uitleg( 'Het label en de punten typ je in het blok zelf.' ),
			] ),
			el( 'aside', blockProps,
				el( 'div', { className: 'sfp-samenvatting__kop' }, el( RichText, { tagName: 'span', className: 'sfp-blok-label', value: a.label, placeholder: t( 'samenvatting', 'What you need to know' ), allowedFormats: [], onChange: function ( v ) { props.setAttributes( { label: v } ); } } ) ),
				el( 'div', inner ),
				teller( gevuld.length + ' van ' + SAMENVATTING_PUNTEN + ' punten · langste punt ' + langste + ' van ' + SAMENVATTING_TEKENS + ' tekens' + ( teVeel ? ' · te veel punten' : '' ) + ( teLang ? ' · een punt is te lang' : '' ), teVeel || teLang )
			)
		);
	} );

	/* sfp/kader */
	var kaderSoorten = [
		{ label: 'Wist je dat', value: 'wist-je-dat' },
		{ label: 'Kanttekening', value: 'kanttekening' },
		{ label: 'Grondslag', value: 'grondslag' },
		{ label: 'Reflectievraag', value: 'reflectievraag' },
		{ label: 'Checklist', value: 'checklist' },
		{ label: 'Eigen label of geen', value: 'vrij' },
	];
	blok( 'sfp/kader', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-kader sfp-kader--' + a.soort } );
		var inner = useInnerBlocksProps( {}, { template: 'checklist' === a.soort ? [ [ 'core/list' ] ] : [ [ 'core/paragraph' ] ] } );
		var standaard = 'vrij' === a.soort ? 'Eigen label (leeg: geen label)' : t( 'kader_' + a.soort, '' );
		return el( Fragment, null,
			paneel( 'Kader', [
				keuze( 'Soort', a.soort, kaderSoorten, function ( v ) { props.setAttributes( { soort: v } ); }, 'Alle soorten hebben dezelfde rustige vorm; alleen het label verschilt.' ),
				linkNaarBlok( props, function () { return a.label || standaard || 'kader'; } ),
			] ),
			el( 'aside', blockProps,
				el( RichText, { tagName: 'span', className: 'sfp-blok-label', value: a.label, placeholder: standaard, allowedFormats: [], onChange: function ( v ) { props.setAttributes( { label: v } ); } } ),
				el( 'div', inner )
			)
		);
	} );

	/* sfp/citaat */
	blok( 'sfp/citaat', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-citaat' } );
		var inner = useInnerBlocksProps( {}, { allowedBlocks: [ 'core/paragraph' ], template: [ [ 'core/paragraph' ] ] } );
		return el( Fragment, null,
			paneel( 'Citaat', [
				schakelaar( 'Foto van de auteur bij het bijschrift', a.portret, function ( v ) { props.setAttributes( { portret: v } ); } ),
				uitleg( 'Voor een anekdote uit eigen praktijk. Het bijschrift zegt van wie en waarover.' ),
			] ),
			el( 'div', blockProps,
				el( 'blockquote', inner ),
				el( 'footer', null, el( RichText, { tagName: 'span', value: a.bijschrift, placeholder: 'Bijschrift: van wie en waarover', allowedFormats: [ 'core/bold', 'core/italic' ], onChange: function ( v ) { props.setAttributes( { bijschrift: v } ); } } ) )
			)
		);
	} );

	/* sfp/inzicht */
	var INZICHT_TEKENS = 90;
	blok( 'sfp/inzicht', function ( props ) {
		var a = props.attributes;
		var n = tekens( a.tekst );
		var blockProps = useBlockProps( { className: 'sfp-inzicht' } );
		return el( Fragment, null,
			paneel( 'Inzicht', [
				uitleg( 'Eén kernzin. Zet het woord dat moet opvallen cursief: dat krijgt de accentkleur. Het eerste inzicht wordt het beeld om dit artikel te delen.' ),
			] ),
			el( 'figure', blockProps,
				el( 'div', { className: 'sfp-deelkaart sfp-deelkaart--liggend' },
					el( RichText, { tagName: 'span', className: 'sfp-deelkaart__label', value: a.label, placeholder: t( 'inzicht', 'Insight' ), allowedFormats: [], onChange: function ( v ) { props.setAttributes( { label: v } ); } } ),
					el( RichText, { tagName: 'p', className: 'sfp-deelkaart__tekst', value: a.tekst, placeholder: 'De kernzin', allowedFormats: [ 'core/italic' ], onChange: function ( v ) { props.setAttributes( { tekst: v } ); } } ),
					el( 'span', { className: 'sfp-deelkaart__voet' }, '' )
				),
				teller( n + ' van ' + INZICHT_TEKENS + ' tekens' + ( n > INZICHT_TEKENS ? ' · te lang' : '' ), n > INZICHT_TEKENS )
			)
		);
	} );

	/* sfp/definitie en sfp/in-een-talk: alleen bij een begrip in de blokkenlijst (ontwerpregel 4). */
	var bijBegrip = 'begrippen' === window.typenow;
	function blokBegrip( naam, edit ) {
		var bestaand = wp.blocks.getBlockType( naam );
		if ( bestaand ) {
			wp.blocks.unregisterBlockType( naam );
		}
		wp.blocks.registerBlockType( naam, {
			apiVersion: 3,
			edit: edit,
			save: function () { return null; },
			supports: { html: false, className: true, customClassName: true, inserter: bijBegrip },
		} );
	}
	var DEFINITIE_TEKENS = 160;
	function woorden( html ) {
		var d = document.createElement( 'div' );
		d.innerHTML = html && html.toString ? html.toString() : String( html || '' );
		var t = ( d.textContent || '' ).trim();
		return t ? t.split( /\s+/ ).length : 0;
	}
	blokBegrip( 'sfp/definitie', function ( props ) {
		var a = props.attributes;
		var zet = function ( sleutel ) { return function ( v ) { var n = {}; n[ sleutel ] = v; props.setAttributes( n ); }; };
		var n = tekens( a.zin );
		var w = woorden( a.zin );
		var teLang = n > DEFINITIE_TEKENS || w > 30;
		var titel = wp.data.select( 'core/editor' ) ? ( wp.data.select( 'core/editor' ).getEditedPostAttribute( 'title' ) || '' ) : '';
		var begin = titel && a.zin ? ( function () { var d = document.createElement( 'div' ); d.innerHTML = a.zin; return ( d.textContent || '' ).trim().toLowerCase().replace( /^(the|a|an|de|het|een)\s+/, '' ).indexOf( titel.trim().toLowerCase() ) === 0; } )() : true;
		var blockProps = useBlockProps( { className: 'sfp-definitie' } );
		var vak = function ( sleutel, label, hint ) {
			return el( 'div', { key: sleutel },
				el( 'dt', null, label ),
				el( 'dd', null,
					el( RichText, { tagName: 'span', value: a[ sleutel ], placeholder: hint, allowedFormats: [ 'core/italic', 'core/bold' ], onChange: zet( sleutel ) } ),
					el( RichText, { tagName: 'small', value: a[ sleutel + '2' ], placeholder: 'Tweede regel (mag leeg)', allowedFormats: [ 'core/italic' ], onChange: zet( sleutel + '2' ) } )
				)
			);
		};
		return el( Fragment, null,
			paneel( 'Definitie', [
				uitleg( 'Eén zin die met de term begint, 15 tot 30 woorden en hoogstens 160 tekens. Deze zin staat ook in de begrippenlijst, als tooltip in artikelen en in de gegevens voor Google. Een leeg feitenvak verschijnt niet.' ),
			] ),
			el( 'div', blockProps,
				el( 'span', { className: 'sfp-blok-label' }, t( 'definitie', 'Definition' ) ),
				el( RichText, { tagName: 'p', className: 'sfp-definitie__zin', value: a.zin, placeholder: ( titel || 'De term' ) + ' is ...', allowedFormats: [ 'core/italic', 'core/bold' ], onChange: zet( 'zin' ) } ),
				teller( w + ' woorden · ' + n + ' van ' + DEFINITIE_TEKENS + ' tekens' + ( teLang ? ' · te lang' : '' ) + ( ! begin ? ' · begint niet met de term' : '' ), teLang || ! begin ),
				el( RichText, { tagName: 'p', className: 'sfp-definitie__uitleg', value: a.uitleg, placeholder: 'Uitleg in hoogstens twee zinnen', allowedFormats: [ 'core/italic', 'core/bold', 'core/link' ], onChange: zet( 'uitleg' ) } ),
				el( 'dl', { className: 'sfp-definitie__feiten' },
					vak( 'ook', t( 'ook_wel', 'Also called' ), 'Andere naam' ),
					vak( 'herkomst', t( 'herkomst', 'Origin' ), 'Herkomst van het woord' ),
					vak( 'eerst', t( 'eerst_beschreven', 'First described' ), 'Wie, waar' ),
					vak( 'deel', t( 'deel_van', 'Part of' ), 'Waar het bij hoort' )
				)
			)
		);
	} );
	blokBegrip( 'sfp/in-een-talk', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-in-talk' } );
		return el( Fragment, null,
			paneel( 'In a talk', [ uitleg( 'Eén voorbeeldzin uit een echte presentatie en één regel uitleg. Hoogstens één per begrip.' ) ] ),
			el( 'aside', blockProps,
				el( 'span', { className: 'sfp-blok-label' }, t( 'in_een_talk', 'In a talk' ) ),
				el( RichText, { tagName: 'p', className: 'sfp-in-talk__zin', value: a.zin, placeholder: 'De voorbeeldzin', allowedFormats: [ 'core/italic' ], onChange: function ( v ) { props.setAttributes( { zin: v } ); } } ),
				el( RichText, { tagName: 'p', value: a.uitleg, placeholder: 'Eén regel uitleg', allowedFormats: [ 'core/italic' ], onChange: function ( v ) { props.setAttributes( { uitleg: v } ); } } )
			)
		);
	} );

	/* sfp/lees-ook */
	blok( 'sfp/lees-ook', function ( props ) {
		var a = props.attributes;
		var zoek = useState( '' );
		var gevonden = wp.data.useSelect( function ( select ) {
			var kern = select( 'core' );
			var lijst = zoek[ 0 ] ? kern.getEntityRecords( 'postType', 'post', { search: zoek[ 0 ], per_page: 10, status: 'publish', _fields: 'id,title' } ) : null;
			var gekozen = a.postId ? kern.getEntityRecord( 'postType', 'post', a.postId ) : null;
			return { lijst: lijst || [], gekozen: gekozen };
		}, [ zoek[ 0 ], a.postId ] );
		var opties = gevonden.lijst.map( function ( p ) { return { value: String( p.id ), label: ( p.title && ( p.title.rendered || p.title.raw ) ) || ( 'Artikel ' + p.id ) }; } );
		if ( gevonden.gekozen && ! opties.some( function ( o ) { return o.value === String( a.postId ); } ) ) {
			opties.unshift( { value: String( a.postId ), label: ( gevonden.gekozen.title && ( gevonden.gekozen.title.rendered || gevonden.gekozen.title.raw ) ) || ( 'Artikel ' + a.postId ) } );
		}
		var eigenTitel = ( gevonden.gekozen && gevonden.gekozen.title && gevonden.gekozen.title.rendered ) || '';
		var blockProps = useBlockProps( { className: 'sfp-lees-ook' } );
		return el( Fragment, null,
			paneel( 'Lees ook', [
				el( c.ComboboxControl, {
					key: 'artikel',
					label: 'Artikel',
					value: a.postId ? String( a.postId ) : '',
					options: opties,
					onFilterValueChange: function ( v ) { zoek[ 1 ]( v ); },
					onChange: function ( v ) { props.setAttributes( { postId: v ? parseInt( v, 10 ) : 0 } ); },
					help: 'Typ om te zoeken. Titel, link en afbeelding komen uit dat artikel.',
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
				} ),
				! a.postId ? el( 'div', { key: 'los', className: 'sfp-linkveld' },
					el( 'span', { className: 'sfp-vormkeuze__label' }, 'Of een andere link' ),
					el( be.URLInput, { value: a.url || '', onChange: function ( v ) { props.setAttributes( { url: v } ); }, placeholder: 'Plak de link', __nextHasNoMarginBottom: true } ),
					uitleg( 'Typ dan de titel in het blok.' )
				) : null,
			] ),
			el( 'div', blockProps,
				el( 'span', { className: 'sfp-lees-ook__beeld sfp-lees-ook__beeld--leeg' } ),
				el( 'span', { className: 'sfp-lees-ook__tekst' },
					el( 'small', { className: 'sfp-blok-label' }, t( 'lees_ook', 'Read also' ) ),
					el( RichText, { tagName: 'b', value: a.titel, placeholder: eigenTitel ? wp.htmlEntities ? wp.htmlEntities.decodeEntities( eigenTitel ) : eigenTitel : 'Kies een artikel in de zijbalk', allowedFormats: [], onChange: function ( v ) { props.setAttributes( { titel: v } ); } } )
				)
			)
		);
	} );

	/* sfp/vervolg en sfp/vervolg-vlak */
	blok( 'sfp/vervolg', function ( props ) {
		var aantal = wp.data.useSelect( function ( select ) {
			return select( 'core/block-editor' ).getBlockCount( props.clientId );
		}, [ props.clientId ] );
		var blockProps = useBlockProps( { className: 'sfp-vervolg' + ( aantal > 1 ? ' sfp-vervolg--twee' : '' ) } );
		var inner = useInnerBlocksProps( blockProps, { allowedBlocks: [ 'sfp/vervolg-vlak' ], template: [ [ 'sfp/vervolg-vlak' ], [ 'sfp/vervolg-vlak' ] ], orientation: 'horizontal' } );
		return el( Fragment, null,
			paneel( 'Vervolg', [
				uitleg( 'Een of twee vlakken. Hoogstens één knop; de tweede keus is een pijllink (alinea met de stijl Pijllink).' ),
			] ),
			el( 'section', inner )
		);
	} );
	blok( 'sfp/vervolg-vlak', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-vervolg__vlak' } );
		var inner = useInnerBlocksProps( {}, { template: [ [ 'core/heading', { level: 3 } ], [ 'core/paragraph' ], [ 'core/paragraph', { className: 'is-style-sfp-pijl' } ] ] } );
		return el( 'div', blockProps,
			el( RichText, { tagName: 'span', className: 'sfp-blok-label', value: a.label, placeholder: 'Label, bijvoorbeeld: Coaching', allowedFormats: [], onChange: function ( v ) { props.setAttributes( { label: v } ); } } ),
			el( 'div', inner )
		);
	} );

	/* sfp/warming-up en sfp/warming-up-vraag */
	blok( 'sfp/warming-up', function () {
		var blockProps = useBlockProps( { className: 'sfp-editor-regel' } );
		var inner = useInnerBlocksProps( {}, { allowedBlocks: [ 'sfp/warming-up-vraag' ], template: [ [ 'sfp/warming-up-vraag' ], [ 'sfp/warming-up-vraag' ], [ 'sfp/warming-up-vraag' ] ] } );
		return el( 'div', blockProps,
			el( 'span', { className: 'sfp-editor-label' }, 'Warming-up · staat op de site als knop in de kopkaart, niet op deze plek' ),
			el( 'div', inner )
		);
	} );

	/** De hoofdstukken (tussenkoppen van niveau 2) van het artikel. */
	function hoofdstukken( select ) {
		return select( 'core/block-editor' ).getBlocks().filter( function ( b ) {
			return 'core/heading' === b.name && 2 === ( b.attributes.level || 2 ) && tekens( b.attributes.content ) > 0;
		} ).map( function ( b ) {
			return { id: b.clientId, titel: b.attributes.content, anker: b.attributes.anchor || '', slug: maakSlug( b.attributes.content ) };
		} );
	}

	blok( 'sfp/warming-up-vraag', function ( props ) {
		var a = props.attributes;
		// Als tekst, zodat het blok alleen opnieuw tekent als de hoofdstukken veranderen.
		var lijst = JSON.parse( wp.data.useSelect( function ( select ) { return JSON.stringify( hoofdstukken( select ) ); }, [] ) );
		// Een opgeslagen verwijzing hoort bij een kop met dat anker, of (van
		// voor 3.1.0) bij een kop zonder eigen anker met die tekst.
		var gekozen = a.anker ? lijst.filter( function ( h ) { return h.anker === a.anker || ( ! h.anker && h.slug === a.anker ); } )[ 0 ] : null;
		var kies = function ( id ) {
			var h = lijst.filter( function ( x ) { return x.id === id; } )[ 0 ];
			if ( ! h ) { props.setAttributes( { anker: '' } ); return; }
			var anker = h.anker;
			if ( ! anker ) {
				// De kop krijgt een vast anker, zodat de verwijzing blijft kloppen als je hem hernoemt.
				var gebruikt = gebruikteAnkers();
				anker = h.slug;
				var nr = 2;
				while ( gebruikt[ anker ] ) { anker = h.slug + '-' + nr++; }
				wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( h.id, { anchor: anker } );
			}
			props.setAttributes( { anker: anker } );
		};
		var blockProps = useBlockProps( { className: 'sfp-editor-vlak sfp-editor-warm' } );
		function antwoord( sleutel, nr ) {
			return el( RichText, {
				key: sleutel,
				tagName: 'span',
				className: 'sfp-editor-warm__optie',
				value: a[ sleutel ],
				placeholder: 'Antwoord ' + nr,
				allowedFormats: [],
				onChange: function ( v ) { var n = {}; n[ sleutel ] = v; props.setAttributes( n ); },
			} );
		}
		return el( Fragment, null,
			paneel( 'Warming-upvraag', [
				uitleg( 'De vraag en de antwoorden typ je in het blok zelf.' ),
				keuze( 'Het antwoord staat in hoofdstuk', gekozen ? gekozen.id : '', [ { value: '', label: lijst.length ? 'Geen hoofdstuk' : 'Het artikel heeft nog geen tussenkoppen' } ].concat( lijst.map( function ( h ) {
					var d = document.createElement( 'div' ); d.innerHTML = h.titel; return { value: h.id, label: d.textContent };
				} ) ), kies, 'Hernoem je het hoofdstuk, dan blijft de verwijzing kloppen.' ),
			] ),
			el( 'div', blockProps,
				el( RichText, { tagName: 'div', className: 'sfp-editor-warm__vraag', value: a.vraag, placeholder: 'De vraag', allowedFormats: [], onChange: function ( v ) { props.setAttributes( { vraag: v } ); } } ),
				el( 'div', { className: 'sfp-editor-warm__opties' }, [ antwoord( 'optie1', 1 ), antwoord( 'optie2', 2 ), antwoord( 'optie3', 3 ) ] ),
				a.anker && ! gekozen ? el( 'span', { className: 'sfp-blokmelding' }, 'Het hoofdstuk bij deze vraag staat niet meer in het artikel. Kies het opnieuw in de zijbalk.' ) : null,
				( a.vraag || a.optie1 ) && [ a.optie1, a.optie2, a.optie3 ].filter( function ( o ) { return tekens( o ) > 0; } ).length < 2 ? el( 'span', { className: 'sfp-blokmelding' }, 'Een vraag heeft minstens twee antwoorden. Zonder die verschijnt hij niet op de site.' ) : null
			)
		);
	} );

	/* =====================================================================
	 * Zelftest-oproep (sinds 3.6.0)
	 * ================================================================== */

	blok( 'sfp/zelftest-cta', function ( props ) {
		var a = props.attributes;
		var zt = window.sfpZelftestTests || { tests: [], aan: false };
		var opties = [ { value: '', label: 'De test van de site' } ].concat( ( zt.tests || [] ).map( function ( t ) { return { value: t.test, label: t.naam || t.test }; } ) );
		var leeg = ! zt.aan ? 'Het onderdeel Zelftest staat uit (SFP Beheer, Onderdelen).'
			: 'Geen zelftest om te tonen: de testpagina is nog niet gepubliceerd of er is geen pakket. Bezoekers zien hier niets.';
		return el( Fragment, null,
			paneel( 'Zelftest-oproep', [
				keuze( 'Test', a.test || '', opties, function ( v ) { props.setAttributes( { test: v } ); } ),
				tekstveld( 'Bronlabel', a.bron, function ( v ) { props.setAttributes( { bron: v } ); }, 'Komt als ?bron= in de link, voor GA4. Leeg: het soort pagina en het adres, bijvoorbeeld pagina-presenteren-training.' ),
				uitleg( 'Kop, zin en knoptekst staan in SFP Beheer, Zelftest, en gelden voor elke oproep en de promokaart van een pijler. Niet voor toolpagina\'s: de zelftest is geen tool.' ),
			] ),
			gegeven( 'sfp/zelftest-cta', null, props, leeg )
		);
	} );

	/* =====================================================================
	 * Gegevens van de site (sinds 3.1.0)
	 * ================================================================== */

	function gegeven( naam, kinderen, props, leeg ) {
		var postId = wp.data.select( 'core/editor' ) ? wp.data.select( 'core/editor' ).getCurrentPostId() : 0;
		var blockProps = useBlockProps();
		return el( 'div', blockProps,
			ServerSideRender ? el( ServerSideRender, {
				block: naam,
				attributes: props.attributes,
				urlQueryArgs: postId ? { post_id: postId } : {},
				EmptyResponsePlaceholder: function () { return el( 'div', { className: 'sfp-gegeven-leeg' }, leeg ); },
			} ) : el( 'div', { className: 'sfp-gegeven-leeg' }, leeg ),
			kinderen
		);
	}

	function groepKeuze( props ) {
		var opties = [ { value: '0', label: 'Alle groepen' } ];
		for ( var i = 1; i <= 6; i++ ) { opties.push( { value: String( i ), label: 'Groep ' + i } ); }
		return keuze( 'Welke groep', String( props.attributes.groep || 0 ), opties, function ( v ) { props.setAttributes( { groep: parseInt( v, 10 ) || 0 } ); } );
	}

	blok( 'sfp/cursusdatum', function ( props ) {
		var a = props.attributes;
		return el( Fragment, null,
			paneel( 'Startmomenten', [
				groepKeuze( props ),
				keuze( 'Welke dagen', a.toon, [ { value: 'alle', label: 'Alle dagen' }, { value: 'eerste', label: 'Alleen de eerste dag' } ], function ( v ) { props.setAttributes( { toon: v } ); } ),
				vormkeuze( 'Vorm', a.indeling, [ { label: 'Op één regel', value: 'regel', vorm: 'o:1' }, { label: 'Onder elkaar', value: 'lijst', vorm: 'o:1,1,1' } ], function ( v ) { props.setAttributes( { indeling: v } ); } ),
				uitleg( 'De data vul je in bij SFP Beheer, Cursusdata.' ),
			] ),
			gegeven( 'sfp/cursusdatum', null, props, 'Deze pagina heeft nog geen startmomenten. Vul ze in bij SFP Beheer, Cursusdata.' )
		);
	} );

	blok( 'sfp/lestijd', function ( props ) {
		var a = props.attributes;
		return el( Fragment, null,
			paneel( 'Lestijd', [
				groepKeuze( props ),
				vormkeuze( 'Vorm', a.indeling, [ { label: 'Op één regel', value: 'regel', vorm: 'o:1' }, { label: 'Per groep onder elkaar', value: 'lijst', vorm: 'o:1,1' } ], function ( v ) { props.setAttributes( { indeling: v } ); } ),
				uitleg( 'De lestijd vul je in bij SFP Beheer, Cursusdata.' ),
			] ),
			gegeven( 'sfp/lestijd', null, props, 'Er is nog geen lestijd ingevuld. Vul hem in bij SFP Beheer, Cursusdata.' )
		);
	} );

	blok( 'sfp/leestijd', function ( props ) {
		return el( Fragment, null,
			paneel( 'Leestijd', [ uitleg( 'Telt vanzelf mee met de tekst, bij de laatste keer opslaan.' ) ] ),
			gegeven( 'sfp/leestijd', null, props, 'Leestijd' )
		);
	} );

	blok( 'sfp/reviews', function ( props ) {
		var a = props.attributes;
		return el( Fragment, null,
			paneel( 'Reviews', [
				vormkeuze( 'Vorm', a.weergave, [
					{ label: 'Zoals bij SFP Beheer', value: '', vorm: 'n:1' },
					{ label: 'Eén tegelijk', value: 'slider', vorm: 'n:1' },
					{ label: 'Naast elkaar', value: 'grid', vorm: 'n:1,1,1' },
					{ label: 'Band', value: 'banner', vorm: 'o:.4' },
					{ label: 'Alleen het cijfer', value: 'cijfer', vorm: 'o:.3' },
				], function ( v ) { props.setAttributes( { weergave: v } ); } ),
				'cijfer' !== a.weergave ? keuze( 'Het cijfer boven de reviews', a.samenvatting, [ { value: '', label: 'Zoals bij SFP Beheer' }, { value: 'ja', label: 'Tonen' }, { value: 'nee', label: 'Niet tonen' } ], function ( v ) { props.setAttributes( { samenvatting: v } ); } ) : null,
			] ),
			gegeven( 'sfp/reviews', null, props, 'Er zijn nog geen reviews.' )
		);
	} );

	/*
	 * Rustig ritme in artikelen: tussen twee UX-elementen horen minstens
	 * drie alinea's. Staan er twee te dicht op elkaar, dan verschijnt een
	 * waarschuwing boven het bericht. Publiceren blijft mogelijk.
	 *
	 * Alinea: een gevulde paragraaf of een lijst. Koppen tellen niet mee en
	 * onderbreken de telling niet. UX-element: de eigen blokken (samenvatting,
	 * kader, citaat, inzicht, lees ook, uitklap, kolommen, FAQ) en beeld,
	 * tabel, citaat en video uit WordPress.
	 */
	( function () {
		var data = wp.data;
		if ( ! data || ! data.subscribe || ! ( window.sfpBlokken && window.sfpBlokken.artikel ) ) {
			return;
		}
		var MINIMUM = 3;
		var MELDING = 'sfp-ux-ritme';
		var TEKST = { 'core/list': 1, 'sfp/lijst': 1 };
		var NEUTRAAL = { 'sfp/warming-up': 1, 'sfp/leestijd': 1, 'sfp/cursusdatum': 1, 'sfp/lestijd': 1 };
		var KERN_UX = { 'core/image': 1, 'core/gallery': 1, 'core/table': 1, 'core/quote': 1, 'core/pullquote': 1, 'core/video': 1, 'core/embed': 1, 'core/media-text': 1, 'core/cover': 1 };

		function soort( blok ) {
			var naam = blok.name || '';
			if ( 'core/paragraph' === naam ) {
				var inhoud = blok.attributes ? blok.attributes.content : '';
				var tekst = inhoud && inhoud.toString ? inhoud.toString() : '';
				return tekst.replace( /<[^>]*>/g, '' ).trim() ? 'tekst' : 'neutraal';
			}
			if ( TEKST[ naam ] ) { return 'tekst'; }
			if ( NEUTRAAL[ naam ] ) { return 'neutraal'; }
			if ( KERN_UX[ naam ] || 0 === naam.indexOf( 'sfp/' ) || 0 === naam.indexOf( 'presto-player/' ) ) { return 'ux'; }
			return 'neutraal';
		}
		function titel( blok ) {
			var type = wp.blocks.getBlockType( blok.name );
			return type && type.title ? type.title : blok.name;
		}
		function teDicht( blokken ) {
			var fouten = [], vorige = null, alineas = 0;
			blokken.forEach( function ( blok ) {
				var s = soort( blok );
				if ( 'tekst' === s ) { alineas++; return; }
				if ( 'ux' !== s ) { return; }
				if ( vorige && alineas < MINIMUM ) {
					fouten.push( { id: blok.clientId, tekst: titel( blok ) + ' na ' + titel( vorige ) + ' (' + alineas + ( 1 === alineas ? ' alinea' : ' alinea’s' ) + ' ertussen)' } );
				}
				vorige = blok;
				alineas = 0;
			} );
			return fouten;
		}

		var laatsteBlokken = null, laatsteSleutel = '';
		data.subscribe( function () {
			var editor, blokken;
			try {
				editor = data.select( 'core/editor' );
				if ( ! editor || ! editor.getCurrentPostType || 'post' !== editor.getCurrentPostType() ) {
					return;
				}
				blokken = data.select( 'core/block-editor' ).getBlocks();
			} catch ( fout ) {
				return;
			}
			if ( blokken === laatsteBlokken ) {
				return;
			}
			laatsteBlokken = blokken;
			var fouten = teDicht( blokken );
			var sleutel = fouten.map( function ( f ) { return f.tekst; } ).join( '|' );
			if ( sleutel === laatsteSleutel ) {
				return;
			}
			laatsteSleutel = sleutel;
			var meldingen = data.dispatch( 'core/notices' );
			if ( ! meldingen ) {
				return;
			}
			if ( ! fouten.length ) {
				meldingen.removeNotice( MELDING );
				return;
			}
			var eerste = fouten[ 0 ].id;
			meldingen.createWarningNotice(
				'Rustig ritme: tussen twee UX-elementen horen minstens drie alinea’s. Te dicht op elkaar: ' + fouten.map( function ( f ) { return f.tekst; } ).join( '; ' ) + '.',
				{
					id: MELDING,
					isDismissible: true,
					actions: [ {
						label: 'Ga naar het eerste',
						onClick: function () { data.dispatch( 'core/block-editor' ).selectBlock( eerste ); },
					} ],
				}
			);
		} );
	}() );

	/*
	 * Elk artikel hoort een uitgelichte afbeelding te hebben: die is nodig
	 * voor Lees ook, de overzichten en het delen. Het artikel zelf toont hem
	 * niet. Ontbreekt hij, dan verschijnt een waarschuwing boven het bericht.
	 * Publiceren blijft mogelijk.
	 */
	( function () {
		var data = wp.data;
		if ( ! data || ! data.subscribe || ! ( window.sfpBlokken && window.sfpBlokken.artikel ) ) {
			return;
		}
		var MELDING = 'sfp-uitgelicht';
		var laatste = null;
		data.subscribe( function () {
			var editor, ontbreekt;
			try {
				editor = data.select( 'core/editor' );
				if ( ! editor || ! editor.getCurrentPostType || 'post' !== editor.getCurrentPostType() ) {
					return;
				}
				// Pas melden als er aan het artikel geschreven wordt.
				ontbreekt = ! editor.getEditedPostAttribute( 'featured_media' ) && data.select( 'core/block-editor' ).getBlockCount() >= 3;
			} catch ( fout ) {
				return;
			}
			if ( ontbreekt === laatste ) {
				return;
			}
			laatste = ontbreekt;
			var meldingen = data.dispatch( 'core/notices' );
			if ( ! meldingen ) {
				return;
			}
			if ( ! ontbreekt ) {
				meldingen.removeNotice( MELDING );
				return;
			}
			meldingen.createWarningNotice(
				'Dit artikel heeft nog geen uitgelichte afbeelding. Die is nodig voor Lees ook, de overzichten en het delen.',
				{ id: MELDING, isDismissible: true }
			);
		} );
	}() );
}( window.wp ) );
