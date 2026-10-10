/**
 * SFP Page Config - Bouwstenen in de blokeditor.
 *
 * De blokken zijn in PHP geregistreerd (naam, attributen, weergave); dit
 * script levert alleen het bewerken. Opslaan bewaart de binnenblokken; de
 * omhullende HTML maakt de server bij het tonen.
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
	var be = wp.blockEditor;
	var c = wp.components;
	var InnerBlocks = be.InnerBlocks;
	var useBlockProps = be.useBlockProps;
	var useInnerBlocksProps = be.useInnerBlocksProps;
	var InspectorControls = be.InspectorControls;
	var RichText = be.RichText;

	function opslaan() {
		return el( InnerBlocks.Content );
	}

	function paneel( titel, kinderen ) {
		return el( InspectorControls, null, el( c.PanelBody, { title: titel, initialOpen: true }, kinderen ) );
	}

	function keuze( label, waarde, opties, zet ) {
		return el( c.SelectControl, {
			label: label,
			value: waarde,
			options: opties,
			onChange: zet,
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
		} );
	}

	function schakelaar( label, waarde, zet, hulp ) {
		return el( c.ToggleControl, {
			label: label,
			checked: !! waarde,
			onChange: zet,
			help: hulp,
			__nextHasNoMarginBottom: true,
		} );
	}

	function tekstveld( label, waarde, zet, hulp ) {
		return el( c.TextControl, {
			label: label,
			value: waarde || '',
			onChange: zet,
			help: hulp,
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
		} );
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

	/* sfp/sectie */
	blok( 'sfp/sectie', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-sectie' + ( 'tint' === a.achtergrond ? ' sfp-sectie--tint' : '' ) } );
		var inner = useInnerBlocksProps( { className: 'sfp-sectie__binnen' }, { template: [ [ 'core/heading', { level: 2 } ], [ 'core/paragraph' ] ] } );
		return el( Fragment, null,
			paneel( 'Sectie', [
				keuze( 'Achtergrond', a.achtergrond, [ { label: 'Wit', value: 'wit' }, { label: 'Tint', value: 'tint' } ], function ( v ) { props.setAttributes( { achtergrond: v } ); } ),
				tekstveld( 'Anker', a.anker, function ( v ) { props.setAttributes( { anker: v } ); }, 'Voor links naar deze sectie, zonder #. Bijvoorbeeld discovery-call.' ),
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
			paneel( 'Held', [
				keuze( 'Beeld', a.variant, [ { label: 'Recht (dienstpagina, About)', value: 'recht' }, { label: 'Breed (home)', value: 'breed' } ], function ( v ) { props.setAttributes( { variant: v } ); } ),
				tekstveld( 'Anker', a.anker, function ( v ) { props.setAttributes( { anker: v } ); } ),
				el( 'p', { key: 'uitleg', className: 'components-base-control__help' }, 'Volgorde: H1, intro, afbeelding, knoppen. Op tablet en mobiel valt de intro weg.' ),
			] ),
			el( 'section', blockProps, el( 'div', inner ) )
		);
	} );

	/* sfp/kolommen */
	var indelingen = [
		{ label: 'Twee gelijk', value: 'twee' },
		{ label: 'Drie gelijk', value: 'drie' },
		{ label: 'Tekst en beeld (1,2 : 1)', value: 'tekst-beeld' },
		{ label: 'Tekst en boek (1,4 : 1)', value: 'tekst-boek' },
		{ label: 'Boekingskaart', value: 'boeking' },
		{ label: 'Breed en smal (1,2 : 1)', value: 'breed-smal' },
	];
	blok( 'sfp/kolommen', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-kolommen sfp-kolommen--' + a.indeling } );
		var inner = useInnerBlocksProps( blockProps, { orientation: 'horizontal', template: [ [ 'sfp/kolom' ], [ 'sfp/kolom' ] ] } );
		return el( Fragment, null,
			paneel( 'Kolommen', [
				keuze( 'Indeling', a.indeling, indelingen, function ( v ) { props.setAttributes( { indeling: v } ); } ),
				el( 'p', { key: 'uitleg', className: 'components-base-control__help' }, 'Elk blok direct in Kolommen is een kolom. Gebruik Kolom om meerdere blokken in één kolom te zetten.' ),
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
				keuze( 'Variant', a.variant, [ { label: 'Standaard (groene bovenrand)', value: 'standaard' }, { label: 'Vlak (pakket)', value: 'vlak' }, { label: 'Donker', value: 'donker' } ], function ( v ) { props.setAttributes( { variant: v } ); } ),
				schakelaar( 'Ruim', a.ruim, function ( v ) { props.setAttributes( { ruim: v } ); }, 'Meer binnenruimte vanaf 981 px.' ),
				schakelaar( 'Smal en gecentreerd', a.smal, function ( v ) { props.setAttributes( { smal: v } ); }, 'Maximaal 760 px, bijvoorbeeld voor een formulier.' ),
				tekstveld( 'Hele kaart linkt naar', a.link, function ( v ) { props.setAttributes( { link: v } ); }, 'Leeg: alleen de links in de kaart zijn klikbaar.' ),
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
	blok( 'sfp/uitklap', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-uitklap sfp-uitklap--' + a.variant } );
		var inner = useInnerBlocksProps( blockProps, { allowedBlocks: [ 'sfp/uitklap-regel' ], template: [ [ 'sfp/uitklap-regel' ] ] } );
		return el( Fragment, null,
			paneel( 'Uitklap', [
				keuze( 'Variant', a.variant, [ { label: 'Groot', value: 'groot' }, { label: 'Compact (Read more)', value: 'compact' }, { label: 'Kaart', value: 'kaart' }, { label: 'Verhaal (in een artikel)', value: 'verhaal' } ], function ( v ) { props.setAttributes( { variant: v } ); } ),
				schakelaar( 'Eén regel tegelijk open', a.eenTegelijk, function ( v ) { props.setAttributes( { eenTegelijk: v } ); } ),
			] ),
			el( 'div', inner )
		);
	} );
	blok( 'sfp/uitklap-regel', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-editor-regel' } );
		var inner = useInnerBlocksProps( { className: 'sfp-uitklap__inhoud' }, { template: [ [ 'core/paragraph' ] ] } );
		return el( Fragment, null,
			paneel( 'Uitklapregel', [
				tekstveld( 'Regel onder de titel', a.sub, function ( v ) { props.setAttributes( { sub: v } ); }, 'Alleen in de variant Kaart.' ),
				tekstveld( 'Label boven de titel', a.label, function ( v ) { props.setAttributes( { label: v } ); }, 'Alleen in de variant Verhaal. Bijvoorbeeld: A typical case.' ),
				tekstveld( 'Anker', a.anker, function ( v ) { props.setAttributes( { anker: v } ); }, 'Een link naar #anker opent deze regel.' ),
			] ),
			el( 'div', blockProps,
				el( 'span', { className: 'sfp-editor-label' }, 'Uitklapregel' ),
				el( RichText, { tagName: 'div', value: a.titel, placeholder: 'Titel van de regel', allowedFormats: [ 'core/bold', 'core/italic' ], onChange: function ( v ) { props.setAttributes( { titel: v } ); } } ),
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
				tekstveld( 'Naam voor schermlezers', a.label, function ( v ) { props.setAttributes( { label: v } ); }, 'Bijvoorbeeld: Three sources of persuasion.' ),
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
				keuze( 'Stijl', a.stijl, [ { label: 'Vink', value: 'vink' }, { label: 'Kruis', value: 'kruis' }, { label: 'Nummers', value: 'nummers' }, { label: 'Regels', value: 'regels' }, { label: 'Letters', value: 'letters' } ], function ( v ) { props.setAttributes( { stijl: v } ); } ),
				el( 'p', { key: 'uitleg', className: 'components-base-control__help' }, 'Letters: begin elk item met een vette letter.' ),
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
	function uitleg( tekst ) {
		return el( 'p', { key: 'uitleg', className: 'components-base-control__help' }, tekst );
	}

	/* sfp/samenvatting */
	blok( 'sfp/samenvatting', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-samenvatting' } );
		var inner = useInnerBlocksProps( {}, { allowedBlocks: [ 'core/list' ], template: [ [ 'core/list' ] ], templateLock: 'insert' } );
		return el( Fragment, null,
			paneel( 'Samenvatting', [
				tekstveld( 'Label', a.label, function ( v ) { props.setAttributes( { label: v } ); }, 'Leeg: ' + t( 'samenvatting', 'What you need to know' ) + '.' ),
				tekstveld( 'Onderwerp op het deelbeeld', a.onderwerp, function ( v ) { props.setAttributes( { onderwerp: v } ); }, 'Kort, bijvoorbeeld: The spotlight effect. Leeg: de titel van het artikel.' ),
				uitleg( 'Drie of vier punten in volledige zinnen, elk hoogstens circa 70 tekens. De server maakt bij opslaan een deelbeeld van 1080 x 1080.' ),
			] ),
			el( 'aside', blockProps,
				el( 'div', { className: 'sfp-samenvatting__kop' }, el( 'span', { className: 'sfp-blok-label' }, a.label || t( 'samenvatting', 'What you need to know' ) ) ),
				el( 'div', inner )
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
		{ label: 'Vrij (eigen label of geen)', value: 'vrij' },
	];
	blok( 'sfp/kader', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-kader sfp-kader--' + a.soort } );
		var inner = useInnerBlocksProps( {}, { template: 'checklist' === a.soort ? [ [ 'core/list' ] ] : [ [ 'core/paragraph' ] ] } );
		var label = a.label || ( 'vrij' === a.soort ? '' : t( 'kader_' + a.soort, '' ) );
		return el( Fragment, null,
			paneel( 'Kader', [
				keuze( 'Soort', a.soort, kaderSoorten, function ( v ) { props.setAttributes( { soort: v } ); } ),
				tekstveld( 'Eigen label', a.label, function ( v ) { props.setAttributes( { label: v } ); }, 'Leeg: het vaste label van de soort, in de taal van de site.' ),
				tekstveld( 'Anker', a.anker, function ( v ) { props.setAttributes( { anker: v } ); }, 'Voor links naar dit kader, zonder #.' ),
				uitleg( 'Alle soorten hebben dezelfde rustige vorm; alleen het label verschilt. Zet nooit twee bouwstenen direct onder elkaar.' ),
			] ),
			el( 'aside', blockProps,
				label ? el( 'span', { className: 'sfp-blok-label' }, label ) : null,
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
				el( 'footer', null, el( RichText, { tagName: 'span', value: a.bijschrift, placeholder: 'Bijschrift, bijvoorbeeld: Stephan on a coachee who …', allowedFormats: [ 'core/bold', 'core/italic' ], onChange: function ( v ) { props.setAttributes( { bijschrift: v } ); } } ) )
			)
		);
	} );

	/* sfp/inzicht */
	blok( 'sfp/inzicht', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-inzicht' } );
		return el( Fragment, null,
			paneel( 'Inzicht', [
				tekstveld( 'Label', a.label, function ( v ) { props.setAttributes( { label: v } ); }, 'Leeg: ' + t( 'inzicht', 'Insight' ) + '.' ),
				uitleg( 'Eén kernzin van hoogstens circa 90 tekens. Zet het accentwoord cursief: dat krijgt de accentkleur. De server maakt bij opslaan een beeld van 1200 x 627 en gebruikt het eerste inzicht als deelbeeld van het artikel.' ),
			] ),
			el( 'figure', blockProps,
				el( 'div', { className: 'sfp-deelkaart sfp-deelkaart--liggend' },
					el( 'span', { className: 'sfp-deelkaart__label' }, a.label || t( 'inzicht', 'Insight' ) ),
					el( RichText, { tagName: 'p', className: 'sfp-deelkaart__tekst', value: a.tekst, placeholder: 'De kernzin', allowedFormats: [ 'core/italic' ], onChange: function ( v ) { props.setAttributes( { tekst: v } ); } } ),
					el( 'span', { className: 'sfp-deelkaart__voet' }, '' )
				)
			)
		);
	} );

	/* sfp/lees-ook */
	blok( 'sfp/lees-ook', function ( props ) {
		var a = props.attributes;
		var zoek = wp.element.useState( '' );
		var gevonden = wp.data.useSelect( function ( select ) {
			var kern = select( 'core' );
			var lijst = zoek[ 0 ] ? kern.getEntityRecords( 'postType', 'post', { search: zoek[ 0 ], per_page: 10, status: 'publish', _fields: 'id,title' } ) : null;
			var gekozen = a.postId ? kern.getEntityRecord( 'postType', 'post', a.postId ) : null;
			return { lijst: lijst || [], gekozen: gekozen };
		}, [ zoek[ 0 ], a.postId ] );
		var opties = gevonden.lijst.map( function ( p ) { return { value: String( p.id ), label: ( p.title && ( p.title.rendered || p.title.raw ) ) || ( '#' + p.id ) }; } );
		if ( gevonden.gekozen && ! opties.some( function ( o ) { return o.value === String( a.postId ); } ) ) {
			opties.unshift( { value: String( a.postId ), label: ( gevonden.gekozen.title && ( gevonden.gekozen.title.rendered || gevonden.gekozen.title.raw ) ) || ( '#' + a.postId ) } );
		}
		var titel = a.titel || ( gevonden.gekozen && gevonden.gekozen.title && gevonden.gekozen.title.rendered ) || '';
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
					help: 'Typ om te zoeken. Titel, link en uitgelichte afbeelding komen uit dat artikel.',
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
				} ),
				tekstveld( 'Eigen titel', a.titel, function ( v ) { props.setAttributes( { titel: v } ); }, 'Leeg: de titel van het gekozen artikel.' ),
				tekstveld( 'Of een losse link', a.url, function ( v ) { props.setAttributes( { url: v } ); }, 'Alleen zonder gekozen artikel, met een eigen titel.' ),
			] ),
			el( 'div', blockProps,
				el( 'span', { className: 'sfp-lees-ook__beeld sfp-lees-ook__beeld--leeg' } ),
				el( 'span', { className: 'sfp-lees-ook__tekst' },
					el( 'small', { className: 'sfp-blok-label' }, t( 'lees_ook', 'Read also' ) ),
					el( 'b', { dangerouslySetInnerHTML: { __html: titel || 'Kies een artikel in de zijbalk' } } )
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
			el( 'span', { className: 'sfp-editor-label' }, 'Warming-up (verschijnt als knop in de kopkaart, niet op deze plek)' ),
			el( 'div', inner )
		);
	} );
	blok( 'sfp/warming-up-vraag', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps( { className: 'sfp-editor-vlak' } );
		function veld( sleutel, label, hulp ) {
			return el( c.TextControl, {
				key: sleutel,
				label: label,
				value: a[ sleutel ] || '',
				onChange: function ( v ) { var n = {}; n[ sleutel ] = v; props.setAttributes( n ); },
				help: hulp,
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} );
		}
		return el( 'div', blockProps, [
			veld( 'vraag', 'Vraag' ),
			veld( 'optie1', 'Antwoord 1' ),
			veld( 'optie2', 'Antwoord 2' ),
			veld( 'optie3', 'Antwoord 3' ),
			veld( 'anker', 'Hoofdstuk met het antwoord', 'Het anker van de H2, zonder #. Leeg: geen verwijzing.' ),
		] );
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
		var NEUTRAAL = { 'sfp/warming-up': 1 };
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
