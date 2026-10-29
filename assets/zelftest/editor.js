/**
 * SFP Page Config - blok Zelftest in de editor
 *
 * Een kader met de naam van de gekozen test; de test zelf verschijnt op de
 * pagina. De lijst met tests komt uit window.sfpZelftestTests.
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
	var SelectControl = wp.components.SelectControl;
	var gegevens = window.sfpZelftestTests || { tests: [], aan: false };

	wp.blocks.registerBlockType('sfp/zelftest', {
		apiVersion: 3,
		title: 'Zelftest',
		description: 'De zelftest van de site: start, vragen en eindscherm. Hoort op de testpagina.',
		category: 'sfp-bouwstenen',
		example: { attributes: {} },
		icon: 'yes-alt',
		attributes: { test: { type: 'string', default: '' }, taal: { type: 'string', default: '' } },
		supports: { html: false, multiple: false },
		edit: function (props) {
			var keuzes = [{ value: '', label: 'Kies een test' }].concat(gegevens.tests.map(function (t) { return { value: t.test, label: t.naam || t.test }; }));
			var gekozen = gegevens.tests.filter(function (t) { return t.test === props.attributes.test; })[0];
			var melding = !gegevens.aan ? 'Het onderdeel Zelftest staat uit (SFP Beheer, Onderdelen).'
				: !gegevens.tests.length ? 'Er is nog geen testpakket ingelezen (SFP Beheer, Onderdelen, Zelftest).'
				: !gekozen ? 'Kies rechts welke test hier komt.'
				: 'Hier komt de ' + (gekozen.naam || gekozen.test) + '. Bekijk de pagina om hem te zien.';
			var blokProps = useBlockProps ? useBlockProps({ className: 'sfp-zt-editor' }) : { className: 'sfp-zt-editor' };
			return el('div', blokProps,
				InspectorControls ? el(InspectorControls, null,
					el(PanelBody, { title: 'Zelftest', initialOpen: true },
						el(SelectControl, { label: 'Test', value: props.attributes.test, options: keuzes, onChange: function (w) { props.setAttributes({ test: w }); } }),
						/* Sinds 3.11.0: een test in meer talen; leeg volgt de testpagina of de site. */
						gekozen && gekozen.talen && gekozen.talen.length > 1 ? el(SelectControl, {
							label: 'Taal', value: props.attributes.taal || '',
							options: [{ value: '', label: 'Taal van de testpagina of de site' }].concat(gekozen.talen.map(function (t) { return { value: t, label: t }; })),
							onChange: function (w) { props.setAttributes({ taal: w }); }
						}) : null
					)
				) : null,
				el('div', { style: { border: '1px dashed currentColor', borderRadius: '6px', padding: '24px', textAlign: 'center' } },
					el('strong', null, 'Zelftest'),
					el('p', { style: { margin: '8px 0 0' } }, melding)
				)
			);
		},
		save: function () { return null; }
	});
})(window.wp);
