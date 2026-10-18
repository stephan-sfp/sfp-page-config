/**
 * SFP Page Config - Knop Begrip in de werkbalk van de editor.
 *
 * Selecteer een woord, klik Begrip en kies het begrip uit de lijst. Op de
 * site krijgt het woord de definitie van dat begrip en Read more als
 * tooltip. Niets typen: de definitie komt uit het begrip zelf.
 *
 * Geen buildstap: gewone JavaScript met wp.element.createElement.
 *
 * @since 3.7.0
 */
( function ( wp ) {
	if ( ! wp || ! wp.richText || ! wp.blockEditor || ! wp.element ) {
		return;
	}
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var c = wp.components;
	var rt = wp.richText;
	var NAAM = 'sfp/begrip';
	var icoon = el( 'svg', { viewBox: '0 0 24 24', width: 24, height: 24, 'aria-hidden': true },
		el( 'path', { d: 'M5 4h11a3 3 0 013 3v13H8a3 3 0 01-3-3V4zm2 2v11a1 1 0 001 1h9V7a1 1 0 00-1-1H7zm2 3h6v2H9V9zm0 4h4v2H9v-2z', fill: 'currentColor' } )
	);

	function tekstVan( html ) {
		var d = document.createElement( 'div' );
		d.innerHTML = html || '';
		return ( d.textContent || '' ).trim();
	}

	function Kiezer( props ) {
		var zoek = useState( '' );
		var lijst = wp.data.useSelect( function ( select ) {
			return select( 'core' ).getEntityRecords( 'postType', 'begrippen', { search: zoek[ 0 ] || undefined, per_page: 20, status: 'publish', orderby: 'title', order: 'asc', _fields: 'id,title' } ) || [];
		}, [ zoek[ 0 ] ] );
		var opties = lijst.map( function ( b ) { return { value: String( b.id ), label: tekstVan( b.title && b.title.rendered ) || ( 'Begrip ' + b.id ) }; } );
		return el( c.Popover, { onClose: props.sluit, placement: 'bottom', className: 'sfp-begrip-kiezer' },
			el( 'div', { style: { padding: '12px', minWidth: '260px' } },
				el( c.ComboboxControl, {
					label: 'Begrip',
					help: 'Het woord krijgt de definitie van dit begrip als tooltip.',
					value: props.huidig || null,
					options: opties,
					onFilterValueChange: function ( v ) { zoek[ 1 ]( v ); },
					onChange: function ( v ) { if ( v ) { props.kies( v ); } },
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
				} ),
				props.huidig ? el( c.Button, { variant: 'link', isDestructive: true, onClick: props.weg, style: { marginTop: '8px' } }, 'Begrip weghalen' ) : null
			)
		);
	}

	rt.registerFormatType( NAAM, {
		title: 'Begrip',
		tagName: 'span',
		className: 'sfp-begrip',
		attributes: { begrip: 'data-sfp-begrip' },
		edit: function ( props ) {
			var open = useState( false );
			var actief = rt.getActiveFormat( props.value, NAAM );
			var huidig = actief && actief.attributes ? actief.attributes.begrip : '';
			return el( wp.element.Fragment, null,
				el( wp.blockEditor.RichTextToolbarButton, {
					icon: icoon,
					title: 'Begrip',
					isActive: props.isActive,
					onClick: function () { open[ 1 ]( true ); },
				} ),
				open[ 0 ] ? el( Kiezer, {
					huidig: huidig,
					sluit: function () { open[ 1 ]( false ); },
					kies: function ( id ) {
						props.onChange( rt.applyFormat( props.value, { type: NAAM, attributes: { begrip: String( id ) } } ) );
						open[ 1 ]( false );
					},
					weg: function () {
						props.onChange( rt.removeFormat( props.value, NAAM ) );
						open[ 1 ]( false );
					},
				} ) : null
			);
		},
	} );
}( window.wp ) );
