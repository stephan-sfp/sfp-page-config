/**
 * SFP Page Config 3.53.0 - Bewerken van de incompanyblokken.
 *
 * sfp/onderwerpen: voorbeeld van de server (de onderwerppagina's onder deze
 * pagina), met in de zijbalk eventueel een andere ouderpagina.
 * sfp/keuzehulp-vorm: alle teksten typ je in het blok (besluit Stephan
 * 12-11-2026); leeg is de standaardtekst van de site, die als lichte tekst
 * in het veld staat. Knoppen, tekstlinks en doelen per kaart in de zijbalk.
 * In de editor staan alle vier de kaarten onder elkaar; op de site toont de
 * keuze alleen de passende.
 *
 * Geen buildstap: gewone JavaScript met wp.element.createElement.
 */
( function ( wp ) {
	if ( ! wp || ! wp.blocks || ! wp.blockEditor ) {
		return;
	}
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var be = wp.blockEditor;
	var c = wp.components;
	var RichText = be.RichText;
	var useBlockProps = be.useBlockProps;
	var InspectorControls = be.InspectorControls;
	var ServerSideRender = wp.serverSideRender;
	var data = window.sfpIncompany || { teksten: {} };
	var T = function ( sleutel ) {
		return ( data.teksten && data.teksten[ sleutel ] ) || '';
	};

	function registreer( naam, edit ) {
		if ( wp.blocks.getBlockType( naam ) ) {
			wp.blocks.unregisterBlockType( naam );
		}
		wp.blocks.registerBlockType( naam, {
			apiVersion: 3,
			edit: edit,
			save: function () {
				return null;
			},
		} );
	}

	function veld( label, waarde, zet, hulp ) {
		return el( c.TextControl, {
			key: 'veld-' + label,
			label: label,
			value: waarde || '',
			onChange: zet,
			help: hulp,
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
		} );
	}

	function tekst( tag, klasse, waarde, standaard, zet ) {
		return el( RichText, {
			tagName: tag,
			className: klasse,
			value: waarde || '',
			placeholder: standaard,
			allowedFormats: [],
			withoutInteractiveFormatting: true,
			onChange: function ( v ) {
				zet( ( v || '' ).replace( /<[^>]*>/g, '' ) );
			},
		} );
	}

	/* ---------- I2 Onderwerpenregel ---------- */
	registreer( 'sfp/onderwerpen', function ( props ) {
		var a = props.attributes;
		var blockProps = useBlockProps();
		return el( Fragment, null,
			el( InspectorControls, null, el( c.PanelBody, { title: 'Onderwerpenregel', initialOpen: true }, [
				el( 'p', { key: 'u', className: 'components-base-control__help' }, 'De gepubliceerde pagina\'s onder deze pagina met soort pagina Incompany, als links met pijl. Op de link staat alleen het onderwerp: "Cursus Pitchen" wordt "Pitchen". Volgorde: het veld Volgorde van de pagina (Pagina-attributen), daarna de titel.' ),
				el( c.TextControl, {
					key: 'o',
					label: 'Andere ouderpagina (nummer)',
					type: 'number',
					value: a.ouder ? String( a.ouder ) : '',
					onChange: function ( v ) { props.setAttributes( { ouder: parseInt( v, 10 ) || 0 } ); },
					help: 'Leeg: deze pagina. Alleen nodig als de regel op een andere pagina staat dan /incompany/.',
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
				} ),
			] ) ),
			el( 'div', blockProps, ServerSideRender ? el( ServerSideRender, {
				block: 'sfp/onderwerpen',
				attributes: a,
				urlQueryArgs: { post_id: wp.data.select( 'core/editor' ) ? wp.data.select( 'core/editor' ).getCurrentPostId() : 0 },
				EmptyResponsePlaceholder: function () {
					return el( 'p', { className: 'components-base-control__help' }, 'Nog geen onderwerppagina\'s: maak pagina\'s onder deze pagina met soort pagina Incompany. Op de site staat hier dan niets.' );
				},
			} ) : null )
		);
	} );

	/* ---------- I4 Keuzehulp vorm ---------- */
	var VORMEN = [ 'incompany', 'training', 'coaching', 'elearning' ];
	var VORMNAAM = { incompany: 'Incompanycursus', training: 'Open training', coaching: 'Individuele coaching', elearning: 'E-learning' };

	registreer( 'sfp/keuzehulp-vorm', function ( props ) {
		var a = props.attributes;
		var vragen = a.vragen || {};
		var kaarten = a.kaarten || {};
		var zetVraag = function ( sleutel, waarde ) {
			var nieuw = Object.assign( {}, vragen );
			nieuw[ sleutel ] = waarde;
			props.setAttributes( { vragen: nieuw } );
		};
		var zetOptie = function ( i, waarde ) {
			var opties = ( vragen.opties || [ '', '', '', '' ] ).slice();
			while ( opties.length < 4 ) {
				opties.push( '' );
			}
			opties[ i ] = waarde;
			zetVraag( 'opties', opties );
		};
		var kaart = function ( vorm ) {
			return kaarten[ vorm ] || {};
		};
		var zetKaart = function ( vorm, sleutel, waarde ) {
			var nieuw = Object.assign( {}, kaarten );
			var k = Object.assign( {}, nieuw[ vorm ] || {} );
			k[ sleutel ] = waarde;
			nieuw[ vorm ] = k;
			props.setAttributes( { kaarten: nieuw } );
		};
		var zetFeit = function ( vorm, i, waarde ) {
			var feiten = ( kaart( vorm ).feiten || [] ).slice();
			while ( feiten.length < 4 ) {
				feiten.push( '' );
			}
			feiten[ i ] = waarde;
			zetKaart( vorm, 'feiten', feiten );
		};
		var blockProps = useBlockProps( { className: 'sfp-kv sfp-editor-kv' } );

		var vraag = function ( nr, sleutel, standaard, opties ) {
			return el( 'fieldset', { key: sleutel, className: 'sfp-kv__vraag' },
				el( 'legend', null, el( 'span', { className: 'sfp-kv__nr', 'aria-hidden': true }, String( nr ) ), tekst( 'span', '', vragen[ sleutel ], standaard, function ( v ) { zetVraag( sleutel, v ); } ) ),
				el( 'div', { className: 'sfp-kv__opties' }, opties )
			);
		};
		var optie = function ( key, waarde, standaard, zet ) {
			return el( 'label', { key: key }, tekst( 'span', '', waarde, standaard, zet ) );
		};
		var opties1 = [ 0, 1, 2, 3 ].map( function ( i ) {
			return optie( 'o' + i, ( vragen.opties || [] )[ i ], T( 'kv_optie' + ( i + 1 ) ), function ( v ) { zetOptie( i, v ); } );
		} );
		var janee = function ( k ) {
			return [ optie( k + 'j', vragen.ja, T( 'kv_ja' ), function ( v ) { zetVraag( 'ja', v ); } ), optie( k + 'n', vragen.nee, T( 'kv_nee' ), function ( v ) { zetVraag( 'nee', v ); } ) ];
		};

		var kaartEl = function ( vorm ) {
			var k = kaart( vorm );
			var feiten = [ 0, 1, 2, 3 ].map( function ( i ) {
				var standaard = 'elearning' === vorm && i < 2 ? T( 'kv_elearning_feit' + ( i + 1 ) ) : 'Feit ' + ( i + 1 ) + ' (leeg: geen regel)';
				return tekst( 'li', '', ( k.feiten || [] )[ i ], standaard, function ( v ) { zetFeit( vorm, i, v ); } );
			} );
			if ( 'incompany' === vorm ) {
				feiten.push( tekst( 'li', 'sfp-kv__extra', k.extra, T( 'kv_incompany_extra' ), function ( v ) { zetKaart( vorm, 'extra', v ); } ) );
			}
			var actie = k.actie || ( 'incompany' === vorm ? 'knop' : 'elearning' === vorm ? 'geen' : 'tekstlink' );
			var actieTekst = k.tekst || T( 'kv_' + vorm + '_actie' );
			var acties = [];
			if ( 'knop' === actie ) {
				acties.push( el( 'div', { key: 'k', className: 'wp-block-buttons' }, el( 'div', { className: 'wp-block-button' }, el( 'span', { className: 'wp-block-button__link wp-element-button' }, actieTekst ) ) ) );
			} else if ( 'tekstlink' === actie ) {
				acties.push( el( 'p', { key: 't', className: 'is-style-sfp-pijl' }, el( 'a', { href: '#', onClick: function ( e ) { e.preventDefault(); } }, actieTekst ) ) );
			}
			if ( 'incompany' === vorm ) {
				acties.push( el( 'p', { key: 't2', className: 'is-style-sfp-pijl' }, el( 'a', { href: '#', onClick: function ( e ) { e.preventDefault(); } }, k.tekst2 || T( 'kv_incompany_actie2' ) ) ) );
			}
			return el( 'div', { key: vorm, className: 'sfp-kv__kaart' + ( 'incompany' === vorm ? ' is-uitgelicht' : '' ) },
				tekst( 'p', 'is-style-sfp-label', k.label, T( 'kv_' + vorm + '_label' ), function ( v ) { zetKaart( vorm, 'label', v ); } ),
				tekst( 'h3', 'wp-block-heading', k.titel, T( 'kv_' + vorm + '_titel' ), function ( v ) { zetKaart( vorm, 'titel', v ); } ),
				el( 'div', { className: 'sfp-lijst ' + ( 'elearning' === vorm ? 'sfp-lijst--plat' : 'sfp-lijst--vink' ) }, el( 'ul', null, feiten ) ),
				acties.length ? el( 'div', { className: 'sfp-kv__acties' }, acties ) : null
			);
		};

		var paneelKaart = function ( vorm ) {
			var k = kaart( vorm );
			var actie = k.actie || ( 'incompany' === vorm ? 'knop' : 'elearning' === vorm ? 'geen' : 'tekstlink' );
			return el( c.PanelBody, { key: vorm, title: VORMNAAM[ vorm ], initialOpen: 'incompany' === vorm },
				el( c.SelectControl, {
					label: 'Actie',
					value: actie,
					options: [ { label: 'Knop', value: 'knop' }, { label: 'Tekstlink', value: 'tekstlink' }, { label: 'Geen', value: 'geen' } ],
					onChange: function ( v ) { zetKaart( vorm, 'actie', v ); },
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
				} ),
				'geen' === actie ? null : veld( 'Tekst', k.tekst, function ( v ) { zetKaart( vorm, 'tekst', v ); }, 'Leeg: ' + ( T( 'kv_' + vorm + '_actie' ) || 'geen tekst' ) ),
				'geen' === actie ? null : veld( 'Doel', k.doel, function ( v ) { zetKaart( vorm, 'doel', v ); }, 'Een anker (#adviesgesprek), een pad (/presentatietraining/) of een paginanummer. Leeg: geen actie op de site.' ),
				'incompany' === vorm ? veld( 'Tweede actie: tekst', k.tekst2, function ( v ) { zetKaart( vorm, 'tekst2', v ); }, 'Tekstlink naast de knop. Leeg: ' + T( 'kv_incompany_actie2' ) ) : null,
				'incompany' === vorm ? veld( 'Tweede actie: doel', k.doel2, function ( v ) { zetKaart( vorm, 'doel2', v ); }, 'Bijvoorbeeld /incompany/calculator/. Leeg: geen tweede actie op de site.' ) : null
			);
		};

		return el( Fragment, null,
			el( InspectorControls, null,
				el( c.PanelBody, { title: 'Keuzehulp vorm', initialOpen: true },
					el( 'p', { className: 'components-base-control__help' }, 'Typ vragen, antwoorden en kaartteksten in het blok; leeg is de lichte standaardtekst. In de editor staan alle kaarten; op de site komt de passende kaart bovenaan en is de incompanykaart de beginstand. Zonder script staan alle kaarten onder elkaar.' ),
					veld( 'Regel na een keuze', vragen.gekozen, function ( v ) { zetVraag( 'gekozen', v ); }, 'Leeg: de standaard (Past het best bij jullie).' ),
					veld( 'Regel als vraag 2 nog open is', vragen.vraag2, function ( v ) { zetVraag( 'vraag2', v ); }, 'Leeg: de standaard (Beantwoord ook vraag 2).' ),
					veld( 'Naam voor schermlezers', a.label, function ( v ) { props.setAttributes( { label: v } ); }, 'Wordt voorgelezen, niet getoond.' )
				),
				VORMEN.map( paneelKaart )
			),
			el( 'div', blockProps,
				el( 'div', { className: 'sfp-kv__vragen' },
					vraag( 1, 'v1', T( 'kv_vraag1' ), opties1 ),
					vraag( 2, 'v2', T( 'kv_vraag2' ), janee( 'a' ) ),
					vraag( 3, 'v3', T( 'kv_vraag3' ), janee( 'b' ) )
				),
				el( 'div', { className: 'sfp-kv__kaarten' },
					tekst( 'p', 'is-style-sfp-label sfp-kv__uitkomst', vragen.start, T( 'kv_regel_start' ), function ( v ) { zetVraag( 'start', v ); } ),
					VORMEN.map( kaartEl )
				)
			)
		);
	} );
}( window.wp ) );
