/**
 * SFP Page Config - blok Incompanycalculator in de editor (sinds 3.36.0)
 *
 * Een kader met de stand; de calculator zelf verschijnt op de pagina.
 */
(function (wp) {
	'use strict';
	if (!wp || !wp.blocks || !wp.element) {
		return;
	}
	var el = wp.element.createElement;
	var InspectorControls = (wp.blockEditor || {}).InspectorControls;
	var useBlockProps = (wp.blockEditor || {}).useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;
	var gegevens = window.sfpCalculatorEditor || { aan: false, proef: true };

	wp.blocks.registerBlockType('sfp/calculator', {
		apiVersion: 3,
		title: 'Incompanycalculator',
		description: 'De incompanycalculator: volledig op de calculatorpagina, ingebed op de incompany-pagina\'s.',
		category: 'sfp-bouwstenen',
		icon: 'calculator',
		attributes: { ingebed: { type: 'boolean', default: false } },
		supports: { html: false, multiple: false },
		edit: function (props) {
			var melding = !gegevens.aan ? 'Het onderdeel Calculator staat uit (SFP Beheer, Onderdelen).'
				: (props.attributes.ingebed ? 'Ingebed: route en aantal mensen, daarna door naar de calculatorpagina.' : 'Volledig: alle stappen, voor de calculatorpagina.')
				+ (gegevens.proef ? ' Proefmodus: alleen beheerders zien hem.' : '');
			var blokProps = useBlockProps ? useBlockProps({ className: 'sfp-calc-editor' }) : { className: 'sfp-calc-editor' };
			return el('div', blokProps,
				InspectorControls ? el(InspectorControls, null,
					el(PanelBody, { title: 'Incompanycalculator', initialOpen: true },
						el(ToggleControl, { label: 'Ingebed (op een incompany-pagina)', checked: !!props.attributes.ingebed, onChange: function (w) { props.setAttributes({ ingebed: w }); } })
					)
				) : null,
				el('div', { style: { border: '1px dashed currentColor', borderRadius: '6px', padding: '24px', textAlign: 'center' } },
					el('strong', null, 'Incompanycalculator'),
					el('p', { style: { margin: '8px 0 0' } }, melding)
				)
			);
		},
		save: function () { return null; }
	});
})(window.wp);
