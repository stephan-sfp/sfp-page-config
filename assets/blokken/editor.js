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
				keuze( 'Variant', a.variant, [ { label: 'Groot', value: 'groot' }, { label: 'Compact (Read more)', value: 'compact' }, { label: 'Kaart', value: 'kaart' } ], function ( v ) { props.setAttributes( { variant: v } ); } ),
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
				keuze( 'Stijl', a.stijl, [ { label: 'Vink', value: 'vink' }, { label: 'Regels', value: 'regels' }, { label: 'Letters', value: 'letters' } ], function ( v ) { props.setAttributes( { stijl: v } ); } ),
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
}( window.wp ) );
