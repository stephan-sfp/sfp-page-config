<?php
/**
 * SFP Page Config - Bouwstenen (Gutenberg-blokken)
 *
 * Negen dynamische blokken voor pagina's en artikelen, netwerkbreed
 * beschikbaar. De opmaak zit in de plugin, zodat een wijziging direct
 * geldt voor alle bestaande inhoud:
 *
 *   sfp/sectie     band over de volle breedte (wit of tint)
 *   sfp/held       hero met H1, intro, beeld en knoppen
 *   sfp/kolommen   twee of drie kolommen of een vaste verhouding
 *   sfp/kolom      kolom binnen sfp/kolommen
 *   sfp/kaart      kaart (standaard, vlak, donker; ruim, smal, link)
 *   sfp/stappen    genummerde stappen (sfp/stap)
 *   sfp/uitklap    uitklaplijst (groot, compact, kaart; sfp/uitklap-regel)
 *   sfp/tabs       tabbladen (sfp/tab)
 *   sfp/lijst      lijst in stijl vink, kruis, nummers, regels of letters (core/list erin)
 *   sfp/faq        FAQ met FAQPage-schema (sfp/faq-vraag)
 *
 * De blokken voor artikelen (samenvatting, kader, citaat, inzicht, lees ook,
 * vervolg, warming-up) staan in includes/blokken-artikel.php en worden hier
 * samengevoegd.
 *
 * Het blok slaat alleen zijn instellingen en de binnenblokken op. De
 * omhullende HTML maakt de server bij het tonen (render_callback).
 *
 * Kleuren: alleen via de rolvariabelen (--sfp-rol-*) met de Astra-waarden
 * van de site als terugval. Fonts: Astra en de kopfont-variabelen. Geen
 * merkwaarden in code. Wit en zwart in schaduwen en maskers zijn
 * functioneel.
 *
 * CSS: per blok een bestand in assets/blokken/. In de head komt alleen
 * de CSS van de blokken die op de pagina staan (inline, uitgesloten van
 * WP Rocket Remove Unused CSS). Staat een blok ergens anders (Astra-hook,
 * herbruikbaar blok), dan zet het blok zijn CSS zelf voor zich neer.
 *
 * Opmaak overgenomen uit het SwS-snippet "Vormgeving: kernpagina's"
 * (stand 9 oktober 2026), goedgekeurd door Stephan op 9 oktober 2026.
 *
 * Terugdraaien: dit bestand uit de lijst in sfp-page-config.php halen.
 * Inhoud met deze blokken toont dan alleen de binnenblokken, zonder
 * omhulling.
 *
 * @package SFP_Page_Config
 * @since   2.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/bestanden.php';

/* =========================================================================
 * Definities
 * ====================================================================== */

/**
 * Alle blokken met hun instellingen.
 *
 * Sleutel 'css' is het CSS-bestand in assets/blokken/ (zonder .css).
 *
 * @return array<string, array>
 */
function sfp_page_config_blokken() {
    static $alle = null;
    if ( null !== $alle ) {
        return $alle;
    }
    $tekst = array(
        'type'    => 'string',
        'default' => ''
    );

    $alle = array(
        'sfp/sectie'        => array(
            'title'       => 'Sectie',
            'description' => 'Band over de volle breedte. Achtergrond wit of tint.',
            'icon'        => 'align-full-width',
            'css'         => 'basis',
            'css_extra'   => array( 'formulier' ),
            'attributes'  => array(
                'achtergrond' => array(
                    'type'    => 'string',
                    'default' => 'wit',
                    'enum'    => array( 'wit', 'tint' )
                ),
                'anker'       => $tekst,
            ),
            'render'      => 'sfp_page_config_render_sectie',
        ),
        'sfp/held'          => array(
            'title'       => 'Held',
            'description' => 'Bovenkant van een pagina: titel, intro, beeld en knoppen.',
            'icon'        => 'cover-image',
            'css'         => 'held',
            'attributes'  => array(
                'variant'       => array(
                    'type'    => 'string',
                    'default' => 'recht',
                    'enum'    => array( 'recht', 'breed', 'wit', 'foto' )
                ),
                'anker'         => $tekst,
                // Held foto (3.34.0, home): aparte foto voor de telefoon.
                'mobielId'      => array(
                    'type'    => 'number',
                    'default' => 0
                ),
                'focusMobiel'   => array(
                    'type'    => 'string',
                    'default' => '60% 20%'
                ),
                // Held wit (3.34.0, O4): feitenkaart tonen.
                'feitenkaart'   => array(
                    'type'    => 'boolean',
                    'default' => true
                ),
                // Held wit (3.30.0): feitenkaart en bewijsregel.
                'volgendeStart' => array(
                    'type'    => 'boolean',
                    'default' => true
                ),
                'feiten'        => array(
                    'type'    => 'array',
                    'default' => array()
                ),
                'deelnemers'    => $tekst,
                'initialen'     => $tekst,
                // Sinds 3.31.0: focuspunt van de foto en feiten die mogen afbreken.
                'focus'         => array(
                    'type'    => 'string',
                    'default' => '50% 30%'
                ),
                'afbreken'      => array(
                    'type'    => 'boolean',
                    'default' => false
                ),
            ),
            'render'      => 'sfp_page_config_render_held',
        ),
        'sfp/kolommen'      => array(
            'title'       => 'Kolommen',
            'description' => 'Twee of drie kolommen naast elkaar. Op een telefoon onder elkaar.',
            'icon'        => 'columns',
            'css'         => 'kolommen',
            'attributes'  => array(
                'indeling'   => array(
                    'type'    => 'string',
                    'default' => 'twee',
                    'enum'    => array( 'twee', 'drie', 'tekst-beeld', 'tekst-boek', 'boeking', 'breed-smal', 'situaties', 'reviews', 'boek', 'inschrijven', 'pakketten', 'slot' )
                ),
                // Reviews als carrousel op een telefoon (3.34.0, H6).
                'carrousel'  => array(
                    'type'    => 'boolean',
                    'default' => false
                ),
                // Verticale uitlijning (3.22.0). Leeg: zoals de indeling het doet.
                'uitlijning' => array(
                    'type'    => 'string',
                    'default' => '',
                    'enum'    => array( '', 'boven', 'midden', 'onder', 'rekken' )
                ),
            ),
            'render'      => 'sfp_page_config_render_kolommen',
        ),
        'sfp/kolom'         => array(
            'title'       => 'Kolom',
            'description' => 'Kolom binnen Kolommen.',
            'icon'        => 'align-center',
            'css'         => 'kolommen',
            'ancestor'    => array( 'sfp/kolommen' ),
            'attributes'  => array(),
            'render'      => 'sfp_page_config_render_kolom',
        ),
        'sfp/kaart'         => array(
            'title'       => 'Kaart',
            'description' => 'Kaart met rand: met accentrand, vlak of donker.',
            'icon'        => 'id-alt',
            'css'         => 'kaart',
            'attributes'  => array(
                'variant' => array(
                    'type'    => 'string',
                    'default' => 'standaard',
                    'enum'    => array( 'standaard', 'vlak', 'donker' )
                ),
                'ruim'    => array(
                    'type'    => 'boolean',
                    'default' => false
                ),
                'smal'    => array(
                    'type'    => 'boolean',
                    'default' => false
                ),
                // Beeld boven over de volle breedte (3.34.0, H3).
                'beeld'   => array(
                    'type'    => 'boolean',
                    'default' => false
                ),
                'link'    => $tekst,
            ),
            'render'      => 'sfp_page_config_render_kaart',
        ),
        'sfp/stappen'       => array(
            'title'       => 'Stappen',
            'description' => 'Genummerde stappen.',
            'icon'        => 'editor-ol',
            'css'         => 'stappen',
            'attributes'  => array(
                'vorm' => array(
                    'type'    => 'string',
                    'default' => 'stappen',
                    'enum'    => array( 'stappen', 'uitkomsten' )
                ),
            ),
            'render'      => 'sfp_page_config_render_stappen',
        ),
        'sfp/stap'          => array(
            'title'       => 'Stap',
            'description' => 'Stap binnen Stappen.',
            'icon'        => 'editor-ol',
            'css'         => 'stappen',
            'parent'      => array( 'sfp/stappen' ),
            'attributes'  => array(),
            'render'      => 'sfp_page_config_render_stap',
        ),
        'sfp/uitklap'       => array(
            'title'       => 'Uitklap',
            'description' => 'Regels die openklappen: groot, lees meer, als kaart of als verhaal in een artikel.',
            'icon'        => 'arrow-down-alt2',
            'css'         => 'uitklap',
            'attributes'  => array(
                'variant'     => array(
                    'type'    => 'string',
                    'default' => 'groot',
                    'enum'    => array( 'groot', 'compact', 'kaart', 'verhaal', 'programma', 'vergelijk' )
                ),
                'eenTegelijk' => array(
                    'type'    => 'boolean',
                    'default' => false
                ),
            ),
            'render'      => 'sfp_page_config_render_uitklap',
        ),
        'sfp/uitklap-regel' => array(
            'title'       => 'Uitklapregel',
            'description' => 'Eén regel binnen Uitklap.',
            'icon'        => 'arrow-down-alt2',
            'css'         => 'uitklap',
            'parent'      => array( 'sfp/uitklap' ),
            'attributes'  => array(
                'titel' => $tekst,
                'sub'   => $tekst,
                'label' => $tekst,
                'anker' => $tekst,
            ),
            'render'      => 'sfp_page_config_render_uitklap_regel',
        ),
        'sfp/tabs'          => array(
            'title'       => 'Tabs',
            'description' => 'Tabbladen naast elkaar, elk met een eigen inhoud.',
            'icon'        => 'index-card',
            'css'         => 'tabs',
            'attributes'  => array(
                'label' => $tekst,
                // Als kaart (3.34.0, O1): links uitgelijnd, icoon rechts.
                'vorm'    => array(
                    'type'    => 'string',
                    'default' => '',
                    'enum'    => array( '', 'kaart' )
                ),
                // Sinds 3.43.0: volgt de variantkeuze van de groepskaarten
                // (een tab per variant, het aantal dagen staat in de titel).
                'variant' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
            ),
            'render'      => 'sfp_page_config_render_tabs',
        ),
        'sfp/tab'           => array(
            'title'       => 'Tab',
            'description' => 'Tabblad binnen Tabs.',
            'icon'        => 'index-card',
            'css'         => 'tabs',
            'parent'      => array( 'sfp/tabs' ),
            'attributes'  => array(
                'titel'   => $tekst,
                // Icoon rechts in Tabs als kaart (3.34.0): een SVG uit de media.
                'icoonId' => array(
                    'type'    => 'number',
                    'default' => 0
                ),
                // Of een icoon uit de vaste set (3.37.0, assets/iconen/). Een SVG uit de media gaat voor.
                'icoon'   => array(
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
            'render'      => 'sfp_page_config_render_tab',
        ),
        'sfp/lijst'         => array(
            'title'       => 'Lijst',
            'description' => 'Lijst in stijl vink, kruis, nummers, regels of letters.',
            'icon'        => 'editor-ul',
            'css'         => 'lijst',
            'attributes'  => array(
                'stijl' => array(
                    'type'    => 'string',
                    'default' => 'vink',
                    'enum'    => array( 'regels', 'letters', 'vink', 'kruis', 'nummers' )
                ),
                // Vakjes naast elkaar, even hoog (3.30.0, Inbegrepen).
                'rij'   => array(
                    'type'    => 'boolean',
                    'default' => false
                ),
            ),
            'render'      => 'sfp_page_config_render_lijst',
        ),
        'sfp/faq'           => array(
            'title'       => 'FAQ',
            'description' => 'Vragen en antwoorden, met de gegevens voor Google erbij.',
            'icon'        => 'editor-help',
            'css'         => 'faq',
            'attributes'  => array(),
            'render'      => 'sfp_page_config_render_faq',
        ),
        'sfp/faq-vraag'     => array(
            'title'       => 'FAQ-vraag',
            'description' => 'Vraag met antwoord binnen FAQ.',
            'icon'        => 'editor-help',
            'css'         => 'faq',
            'parent'      => array( 'sfp/faq' ),
            'attributes'  => array(
                'vraag' => $tekst,
            ),
            'render'      => 'sfp_page_config_render_faq_vraag',
        ),
    );

    // Blokken die de CSS van de paginablokken (sectie, knoppen) niet nodig
    // hebben: staat er alleen zo'n blok op de pagina, dan blijft basis.css weg.
    foreach ( array( 'sfp/kolommen', 'sfp/kolom', 'sfp/uitklap', 'sfp/uitklap-regel', 'sfp/lijst', 'sfp/faq', 'sfp/faq-vraag' ) as $naam ) {
        $alle[ $naam ]['zonder_basis'] = true;
    }

    // De artikelblokken (includes/blokken-artikel.php, sinds 2.12.0).
    if ( function_exists( 'sfp_page_config_blokken_artikel' ) ) {
        $alle = array_merge( $alle, sfp_page_config_blokken_artikel() );
    }

    // Gegevens van de site als blok (includes/blokken-gegevens.php, sinds 3.1.0).
    if ( function_exists( 'sfp_page_config_blokken_gegevens' ) ) {
        $alle = array_merge( $alle, sfp_page_config_blokken_gegevens() );
    }

    /**
     * Blokken van andere onderdelen (zelftest-oproep, begrip, tool), sinds 3.6.0.
     *
     * @param array $alle Blokken.
     */
    $alle = (array) apply_filters( 'sfp_page_config_blokken_extra', $alle );
    return $alle;
}

/**
 * Blokstijlen op WordPress-blokken. Klasse wordt is-style-<naam>.
 *
 * @return array<string, array<string, string>>
 */
function sfp_page_config_blokstijlen() {
    return array(
        'core/paragraph' => array(
            'sfp-intro'        => 'Intro',
            'sfp-groot'        => 'Groot',
            'sfp-pijl'         => 'Pijllink',
            'sfp-bovenschrift' => 'Bovenschrift',
            'sfp-getal'        => 'Getal (pakket)',
            'sfp-prijs'        => 'Prijs',
            'sfp-wie'          => 'Naam met portret',
            'sfp-label'        => 'Label (boven een kop)',
            'sfp-mailregel'    => 'Mailregel',
            'sfp-klein'        => 'Klein',
            'sfp-lead'         => 'Lead (eerste alinea van een artikel)',
            'sfp-kernzin'      => 'Kernzin (vet, onder een kop)',
            'sfp-omvang'       => 'Omvang (pakket)',
            'sfp-plus'         => 'Plus-regel (pakket: Alles in X, plus)',
        ),
        'core/table'     => array(
            'sfp-tabel' => 'Huisstijl',
        ),
        'core/heading'   => array(
            'sfp-paginatitel' => 'Paginatitel over de volle breedte',
        ),
        'core/image'     => array(
            'sfp-afgerond'        => 'Afgerond',
            'sfp-omslag'          => 'Boekomslag',
            'sfp-alleen-telefoon' => 'Alleen op een telefoon',
            'sfp-niet-telefoon'   => 'Niet op een telefoon',
        ),
        'core/button'    => array(
            'sfp-licht'  => 'Licht (op donker)',
            'sfp-tweede' => 'Tweede (op licht)',
            'sfp-tekst'  => 'Tekstlink (tweede keus naast een volle knop)',
        ),
        'sfp/kolom'      => array(
            'sfp-wachtlijst' => 'Wachtlijst (veld en knop op één regel)',
        ),
    );
}

/* =========================================================================
 * Registratie
 * ====================================================================== */

add_action( 'init', 'sfp_page_config_blokken_registreer' );

/**
 * Registreer editorscript, blokken en blokstijlen.
 */
function sfp_page_config_blokken_registreer() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }

    wp_register_script(
        'sfp-blokken-editor',
        sfp_page_config_asset_url( 'blokken/editor.js' ),
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-core-data', 'wp-notices', 'wp-url', 'wp-html-entities', 'wp-server-side-render', 'wp-hooks', 'wp-compose', 'wp-rich-text' ),
        SFP_PAGE_CONFIG_VERSION,
        true
    );

    // De vaste labels van de artikelblokken, in de taal van de site.
    if ( function_exists( 'sfp_page_config_artikel_tekst' ) ) {
        $labels = array();
        foreach ( array( 'samenvatting', 'inzicht', 'lees_ook', 'kader_wist-je-dat', 'kader_kanttekening', 'kader_grondslag', 'kader_reflectievraag', 'kader_checklist', 'definitie', 'ook_wel', 'herkomst', 'eerst_beschreven', 'deel_van', 'in_een_talk', 'check' ) as $sleutel ) {
            $labels[ $sleutel ] = sfp_page_config_artikel_tekst( $sleutel );
        }
        wp_add_inline_script(
            'sfp-blokken-editor',
            'window.sfpBlokken=' . wp_json_encode(
                array(
                    'teksten' => $labels,
                    // De waarschuwingen in de editor (ritme, uitgelichte afbeelding) horen bij de artikelopmaak.
                    'artikel' => function_exists( 'sfp_page_config_artikel_aan' ) && sfp_page_config_artikel_aan(),
                )
            ) . ';',
            'before'
        );
    }

    // De vaste iconenset voor de keuze in een tab (3.37.0).
    $iconen = array();
    foreach ( sfp_page_config_iconen() as $naam => $omschrijving ) {
        $iconen[] = array(
            'id'    => $naam,
            'label' => $omschrijving,
            'url'   => sfp_page_config_icoon_data( $naam ),
        );
    }
    wp_add_inline_script( 'sfp-blokken-editor', 'window.sfpIconen=' . wp_json_encode( $iconen ) . ';', 'before' );

    foreach ( sfp_page_config_blokken() as $naam => $def ) {
        $args = array(
            'api_version'     => 3,
            'title'           => $def['title'],
            'description'     => $def['description'],
            // Alle eigen blokken in één groep in de blokkenlijst (ontwerpregel 11).
            'category'        => 'sfp-bouwstenen',
            'icon'            => $def['icon'],
            'attributes'      => $def['attributes'],
            'supports'        => array(
                'html'            => false,
                'className'       => true,
                'customClassName' => true,
                'inserter'        => ! isset( $def['inserter'] ) || $def['inserter'],
            ),
            'editor_script'   => 'sfp-blokken-editor',
            'render_callback' => $def['render'],
        );
        if ( isset( $def['context'] ) ) {
            $args['uses_context'] = $def['context'];
        }
        // Uitlijning breed of volle breedte (sinds 3.23.0, het blok Merkwaarden).
        if ( isset( $def['align'] ) ) {
            $args['supports']['align'] = $def['align'];
        }
        // Een voorbeeld als je het blok aanwijst in de blokkenlijst (ontwerpregel 23).
        $voorbeeld = sfp_page_config_blok_voorbeeld( $naam );
        if ( null !== $voorbeeld ) {
            $args['example'] = $voorbeeld;
        }
        if ( isset( $def['parent'] ) ) {
            $args['parent'] = $def['parent'];
        }
        if ( isset( $def['ancestor'] ) ) {
            $args['ancestor'] = $def['ancestor'];
        }
        register_block_type( $naam, $args );
    }

    if ( function_exists( 'register_block_style' ) ) {
        foreach ( sfp_page_config_blokstijlen() as $blok => $stijlen ) {
            foreach ( $stijlen as $naam => $label ) {
                register_block_style(
                    $blok,
                    array(
                        'name'  => $naam,
                        'label' => $label
                    )
                );
            }
        }
    }
}

// Laat (99): SureForms zet zijn eigen groepen op 10 ook bovenaan; deze moet daarna komen.
add_filter( 'block_categories_all', 'sfp_page_config_blokken_groep', 99, 2 );

/**
 * De eigen groep in de blokkenlijst, bovenaan.
 *
 * @param  array $groepen Groepen.
 * @return array
 */
function sfp_page_config_blokken_groep( $groepen ) {
    foreach ( (array) $groepen as $groep ) {
        if ( isset( $groep['slug'] ) && 'sfp-bouwstenen' === $groep['slug'] ) {
            return $groepen;
        }
    }
    array_unshift(
        $groepen,
        array(
            'slug'  => 'sfp-bouwstenen',
            'title' => 'Bouwstenen',
            'icon'  => null,
        )
    );
    return $groepen;
}

/**
 * Het voorbeeld van een blok in de blokkenlijst. Binnenblokken (zoals Stap
 * of Kolom) hebben geen eigen voorbeeld: ze staan niet los in de lijst.
 *
 * @param  string $naam Bloknaam.
 * @return array|null
 */
function sfp_page_config_blok_voorbeeld( $naam ) {
    $alinea = function ( $tekst, $klasse = '' ) {
        $a = array( 'content' => $tekst );
        if ( '' !== $klasse ) {
            $a['className'] = $klasse;
        }
        return array(
            'name'       => 'core/paragraph',
            'attributes' => $a
        );
    };
    $kop    = function ( $tekst, $niveau = 2 ) {
        return array(
            'name'       => 'core/heading',
            'attributes' => array(
                'content' => $tekst,
                'level'   => $niveau
            )
        );
    };
    $lijst  = function ( array $items ) {
        return array(
            'name'        => 'core/list',
            'innerBlocks' => array_map(
                function ( $item ) {
                    return array(
                        'name'       => 'core/list-item',
                        'attributes' => array( 'content' => $item )
                    );
                },
                $items
            ),
        );
    };

    // Sinds 3.22.0 neutraal en in de taal van de site (punt 27): geen
    // onderwerp van een merk, zodat de voorbeelden op elke site passen.
    $taal = function_exists( 'sfp_page_config_taal' ) ? sfp_page_config_taal() : 'nl';
    $t    = function ( $nl, $en, $pt ) use ( $taal ) {
        return 'en' === $taal ? $en : ( 'pt' === $taal ? $pt : $nl );
    };

    $voorbeelden = array(
        'sfp/sectie'       => array( 'innerBlocks' => array( $kop( $t( 'Wat je leert', 'What you learn', 'O que você aprende' ) ), $alinea( $t( 'Een band over de volle breedte van de pagina, met een eigen onderwerp.', 'A band across the full width of the page, with its own topic.', 'Uma faixa na largura total da página, com um tema próprio.' ) ) ) ),
        'sfp/held'         => array( 'innerBlocks' => array( $kop( $t( 'Een duidelijke titel', 'A clear title', 'Um título claro' ), 1 ), $alinea( $t( 'Eén zin die zegt wat de bezoeker hier vindt.', 'One sentence that says what the visitor finds here.', 'Uma frase que diz o que o visitante encontra aqui.' ), 'is-style-sfp-intro' ) ) ),
        'sfp/kolommen'     => array(
            'innerBlocks' => array(
                array(
                    'name'        => 'sfp/kolom',
                    'innerBlocks' => array( $alinea( $t( 'Eerste kolom met tekst.', 'First column with text.', 'Primeira coluna com texto.' ) ) )
                ),
                array(
                    'name'        => 'sfp/kolom',
                    'innerBlocks' => array( $alinea( $t( 'Tweede kolom met tekst.', 'Second column with text.', 'Segunda coluna com texto.' ) ) )
                ),
            ),
        ),
        'sfp/kaart'        => array( 'innerBlocks' => array( $kop( $t( 'Persoonlijke begeleiding', 'Personal guidance', 'Acompanhamento individual' ), 3 ), $alinea( $t( 'Eén op één, rond je eigen vraag.', 'One to one, around your own question.', 'Individual, em torno da sua pergunta.' ) ) ) ),
        'sfp/stappen'      => array(
            'innerBlocks' => array(
                array(
                    'name'        => 'sfp/stap',
                    'innerBlocks' => array( $kop( $t( 'Kennismaken', 'Getting to know you', 'Conversa inicial' ), 3 ), $alinea( $t( 'We bespreken je doel.', 'We discuss your goal.', 'Conversamos sobre o seu objetivo.' ) ) )
                ),
                array(
                    'name'        => 'sfp/stap',
                    'innerBlocks' => array( $kop( $t( 'Oefenen', 'Practice', 'Prática' ), 3 ), $alinea( $t( 'Met je eigen materiaal.', 'With your own material.', 'Com o seu próprio material.' ) ) )
                ),
            ),
        ),
        'sfp/uitklap'      => array(
            'innerBlocks' => array(
                array(
                    'name'        => 'sfp/uitklap-regel',
                    'attributes'  => array( 'titel' => $t( 'Hoe lang duurt de training?', 'How long is the training?', 'Quanto tempo dura o treinamento?' ) ),
                    'innerBlocks' => array( $alinea( $t( 'Twee dagen.', 'Two days.', 'Dois dias.' ) ) )
                ),
                array(
                    'name'        => 'sfp/uitklap-regel',
                    'attributes'  => array( 'titel' => $t( 'Waar vindt hij plaats?', 'Where does it take place?', 'Onde acontece?' ) ),
                    'innerBlocks' => array( $alinea( $t( 'Op locatie of online.', 'On location or online.', 'Presencial ou online.' ) ) )
                ),
            ),
        ),
        'sfp/tabs'         => array(
            'innerBlocks' => array(
                array(
                    'name'        => 'sfp/tab',
                    'attributes'  => array( 'titel' => $t( 'Inhoud', 'Content', 'Conteúdo' ) ),
                    'innerBlocks' => array( $alinea( $t( 'Wat je leert.', 'What you learn.', 'O que você aprende.' ) ) )
                ),
                array(
                    'name'        => 'sfp/tab',
                    'attributes'  => array( 'titel' => $t( 'Vorm', 'Format', 'Formato' ) ),
                    'innerBlocks' => array( $alinea( $t( 'Hoe het gaat.', 'How it works.', 'Como funciona.' ) ) )
                ),
            ),
        ),
        'sfp/lijst'        => array( 'innerBlocks' => array( $lijst( array( $t( 'Je oefent met je eigen materiaal', 'You practise with your own material', 'Você pratica com o seu próprio material' ), $t( 'Je krijgt direct feedback', 'You get direct feedback', 'Você recebe feedback na hora' ) ) ) ) ),
        'sfp/faq'          => array(
            'innerBlocks' => array(
                array(
                    'name'        => 'sfp/faq-vraag',
                    'attributes'  => array( 'vraag' => $t( 'Voor wie is dit bedoeld?', 'Who is this for?', 'Para quem é?' ) ),
                    'innerBlocks' => array( $alinea( $t( 'Voor iedereen die er in het werk mee te maken heeft.', 'For anyone who deals with it at work.', 'Para quem lida com isso no trabalho.' ) ) )
                ),
            ),
        ),
        'sfp/samenvatting' => array( 'innerBlocks' => array( $lijst( array( $t( 'Begin met de kern.', 'Start with the core.', 'Comece pelo essencial.' ), $t( 'Eén punt per alinea.', 'One point per paragraph.', 'Um ponto por parágrafo.' ), $t( 'Sluit af met een vervolgstap.', 'End with a next step.', 'Termine com um próximo passo.' ) ) ) ) ),
        'sfp/kader'        => array(
            'attributes'  => array( 'soort' => 'wist-je-dat' ),
            'innerBlocks' => array( $alinea( $t( 'De meeste lezers onthouden vooral het begin en het einde.', 'Most readers mainly remember the beginning and the end.', 'A maioria dos leitores lembra sobretudo do início e do fim.' ) ) )
        ),
        'sfp/citaat'       => array(
            'attributes'  => array(
                'bijschrift' => $t( 'Een deelnemer over de eerste sessie', 'A participant about the first session', 'Um participante sobre a primeira sessão' ),
                'portret'    => false
            ),
            'innerBlocks' => array( $alinea( $t( 'Na twee sessies pakte ik het anders aan.', 'After two sessions I approached it differently.', 'Depois de duas sessões, passei a fazer diferente.' ) ) )
        ),
        'sfp/inzicht'      => array( 'attributes' => array( 'tekst' => $t( 'Een kleine fout valt <em>minder</em> op dan je denkt.', 'A small mistake stands out <em>less</em> than you think.', 'Um pequeno erro chama <em>menos</em> atenção do que você pensa.' ) ) ),
        'sfp/lees-ook'     => array( 'attributes' => array( 'titel' => $t( 'Zo begin je sterk', 'How to start strong', 'Como começar bem' ) ) ),
        'sfp/vervolg'      => array(
            'innerBlocks' => array(
                array(
                    'name'        => 'sfp/vervolg-vlak',
                    'attributes'  => array( 'label' => $t( 'Begeleiding', 'Guidance', 'Acompanhamento' ) ),
                    'innerBlocks' => array( $kop( $t( 'Klaar voor de volgende stap?', 'Ready for the next step?', 'Pronto para o próximo passo?' ), 3 ), $alinea( $t( 'Werk er een op een aan.', 'Work on it one to one.', 'Trabalhe nisso individualmente.' ) ) )
                ),
            ),
        ),
        'sfp/warming-up'   => array(
            'innerBlocks' => array(
                array(
                    'name'       => 'sfp/warming-up-vraag',
                    'attributes' => array(
                        'vraag'  => $t( 'Hoeveel mensen zien een kleine fout?', 'How many people notice a small mistake?', 'Quantas pessoas percebem um pequeno erro?' ),
                        'optie1' => $t( 'Bijna iedereen', 'Almost everyone', 'Quase todos' ),
                        'optie2' => $t( 'De helft', 'Half', 'Metade' ),
                        'optie3' => $t( 'Heel weinig', 'Very few', 'Muito poucas' )
                    )
                ),
            ),
        ),
        'sfp/cursusdatum'  => array(),
        'sfp/cursusprijs'  => array(),
        'sfp/lestijd'      => array(),
        'sfp/leestijd'     => array(),
        'sfp/reviews'      => array( 'attributes' => array( 'weergave' => 'cijfer' ) ),
        // Sinds 3.19.0 ook de blokken die zich los registreren (ontwerpregel 23).
        'sfp/thermometer'  => array(
            'attributes'  => array(
                'kop'    => $t( 'Hoe herkenbaar is dit voor jou?', 'How familiar is this to you?', 'O quanto isso é familiar para você?' ),
                'links'  => $t( 'Klopt niet', 'Not true', 'Não é verdade' ),
                'rechts' => $t( 'Klopt helemaal', 'Completely true', 'Totalmente verdade' ),
            ),
            'innerBlocks' => array(
                array(
                    'name'       => 'sfp/thermometer-stelling',
                    'attributes' => array( 'tekst' => $t( 'Ik zie er de dag ervoor al tegen op.', 'I dread it the day before.', 'Já fico apreensivo no dia anterior.' ) )
                ),
                array(
                    'name'       => 'sfp/thermometer-stelling',
                    'attributes' => array( 'tekst' => $t( 'Ik zie op tegen onverwachte vragen.', 'I dread unexpected questions.', 'Tenho receio de perguntas inesperadas.' ) )
                ),
            ),
        ),
        'sfp/zelftest-cta' => array(),
        'sfp/tool'         => array(),
        'sfp/definitie'    => array(
            'attributes' => array(
                'zin'      => $t( 'Een begrip is een woord met een vaste betekenis binnen een vakgebied.', 'A term is a word with a fixed meaning within a field.', 'Um termo é uma palavra com significado fixo dentro de uma área.' ),
                'uitleg'   => $t( 'Eén zin uitleg, in gewone taal.', 'One sentence of explanation, in plain language.', 'Uma frase de explicação, em linguagem simples.' ),
                'ook'      => $t( 'Vakterm', 'Technical term', 'Termo técnico' ),
                'herkomst' => $t( 'Uit het Latijn', 'From Latin', 'Do latim' ),
            ),
        ),
        'sfp/in-een-talk'  => array(
            'attributes' => array(
                'zin'    => $t( 'Ik merk dat ik even zoek. Dat mag.', 'I notice I am searching for a moment. That is fine.', 'Percebo que estou procurando as palavras. Tudo bem.' ),
                'uitleg' => $t( 'Benoemen haalt de spanning eraf.', 'Naming it takes the tension away.', 'Dizer em voz alta tira a tensão.' ),
            ),
        ),
        // Sinds 3.20.0: Logostrook en Review met een voorbeeld dat alleen in
        // de blokkenlijst staat (neutrale logo's, een verzonnen review).
        'sfp/logostrook'   => array( 'attributes' => array( 'voorbeeld' => true ) ),
        'sfp/review'       => array( 'attributes' => array( 'voorbeeld' => true ) ),
        'sfp/bronnenlijst' => array( 'attributes' => array( 'voorbeeld' => true ) ),
        'sfp/begrip-check' => array(
            'innerBlocks' => array(
                array(
                    'name'       => 'sfp/begrip-vraag',
                    'attributes' => array(
                        'vraag'  => $t( 'Wanneer wordt een gewoonte een probleem?', 'When does a habit become a problem?', 'Quando um hábito vira um problema?' ),
                        'optie1' => $t( 'Altijd', 'Always', 'Sempre' ),
                        'optie2' => $t( 'Als je erdoor gaat vermijden', 'When it makes you avoid things', 'Quando faz você evitar coisas' ),
                        'optie3' => $t( 'Nooit', 'Never', 'Nunca' ),
                        'juist'  => 2
                    )
                ),
            ),
        ),
    );
    if ( ! isset( $voorbeelden[ $naam ] ) ) {
        return null;
    }
    $voorbeeld = $voorbeelden[ $naam ];
    // Een leeg object, geen lege lijst: de editor verwacht attributen als object.
    if ( empty( $voorbeeld['attributes'] ) ) {
        $voorbeeld['attributes'] = new stdClass();
    }
    if ( isset( $voorbeeld['innerBlocks'] ) ) {
        $voorbeeld['innerBlocks'] = sfp_page_config_blok_voorbeeld_binnen( $voorbeeld['innerBlocks'] );
    }
    return $voorbeeld;
}

/**
 * Zorg dat elk binnenblok in een voorbeeld attributen en binnenblokken heeft.
 *
 * @param  array $blokken Binnenblokken.
 * @return array
 */
function sfp_page_config_blok_voorbeeld_binnen( array $blokken ) {
    foreach ( $blokken as $i => $blok ) {
        if ( empty( $blok['attributes'] ) ) {
            $blokken[ $i ]['attributes'] = new stdClass();
        }
        $blokken[ $i ]['innerBlocks'] = isset( $blok['innerBlocks'] ) ? sfp_page_config_blok_voorbeeld_binnen( $blok['innerBlocks'] ) : array();
    }
    return $blokken;
}

/* =========================================================================
 * CSS
 * ====================================================================== */

/**
 * Lees één CSS-bestand uit assets/blokken/.
 *
 * @param  string $naam Bestandsnaam zonder .css.
 * @return string
 */
function sfp_page_config_blokken_css_bestand( $naam ) {
    return sfp_page_config_css_lees( 'blokken/' . preg_replace( '/[^a-z-]/', '', (string) $naam ) . '.css' );
}

/**
 * De variabelen van de bouwstenen, met de Astra-waarden als terugval.
 *
 * @return string
 */
function sfp_page_config_blokken_css_variabelen() {
    $brand   = function_exists( 'sfp_page_config_get_brand' ) ? sfp_page_config_get_brand() : array();
    $primair = isset( $brand['primary'] ) && '' !== $brand['primary'] ? $brand['primary'] : 'var(--ast-global-color-2)';
    $links   = isset( $brand['cta_bg'] ) && '' !== $brand['cta_bg'] ? $brand['cta_bg'] : 'var(--ast-global-color-0)';
    $hover   = isset( $brand['cta_hover'] ) && '' !== $brand['cta_hover'] ? $brand['cta_hover'] : $links;
    $link    = isset( $brand['link'] ) && '' !== $brand['link'] ? $brand['link'] : $links;

    return ':root{'
        . '--sfp-b-primair:var(--sfp-rol-primair,' . $primair . ');'
        . '--sfp-b-secundair:var(--sfp-rol-secundair,var(--sfp-b-primair));'
        // Rol label (3.29.0): de kleur van een bloklabel (Inhoud, Samenvatting, Gids). Besluit Stephan
        // 7-11-2026: het primair op alle acht; zonder eigen slot valt de rol daarop terug.
        . '--sfp-b-label:var(--sfp-rol-label,var(--sfp-b-primair));'
        . '--sfp-b-links:var(--sfp-rol-links,' . $links . ');'
        . '--sfp-b-hover:var(--sfp-rol-hover,' . $hover . ');'
        . '--sfp-b-tekstlink:var(--sfp-rol-tekstlink,' . $link . ');'
        . '--sfp-b-knoptekst:' . ( isset( $brand['cta_text'] ) && '' !== $brand['cta_text'] ? $brand['cta_text'] : '#fff' ) . ';'
        . '--sfp-b-tint1:var(--sfp-rol-tint-1,var(--sfp-b-tint3));'
        . '--sfp-b-tint2:var(--sfp-rol-tint-2,var(--ast-global-color-8));'
        . '--sfp-b-tint3:var(--sfp-rol-tint-3,var(--ast-global-color-8));'
        . '--sfp-b-wit:var(--sfp-rol-wit,#fff);'
        . '--sfp-b-body:var(--sfp-rol-body,var(--ast-global-color-3));'
        . '--sfp-b-lijn:color-mix(in srgb,var(--sfp-b-primair) 12%,transparent);'
        . '--sfp-b-lijn-sterk:color-mix(in srgb,var(--sfp-b-primair) 26%,transparent);'
        . '--sfp-b-display:var(--ux-display-font,inherit);'
        . '--sfp-b-kop:var(--ux-heading-font,var(--sfp-kopfont,inherit));'
        . '--sfp-b-tekst:' . ( isset( $brand['body_font'] ) && '' !== $brand['body_font'] ? $brand['body_font'] : 'inherit' ) . ';'
        // Radius 6 px netwerkbreed (merkbron): knoppen, velden, kaarten,
        // vlakken, beelden. Omslagen van boeken en albums hebben geen radius.
        . '--sfp-b-r-knop:6px;--sfp-b-r-kaart:6px;'
        // De drie functionele kleuren uit de merkbron: de enige vaste hexcodes.
        . '--sfp-b-goed:#1E8B45;--sfp-b-fout:#A4262C;--sfp-b-ster:#FBBC04}';
}

/**
 * Houd bij welke CSS-bestanden al op de pagina staan.
 *
 * @param  string|null $bestand Bestand om als geplaatst te markeren, of null om te lezen.
 * @return array<string, bool>
 */
function sfp_page_config_blokken_geplaatst( $bestand = null ) {
    static $geplaatst = array();
    if ( null !== $bestand ) {
        $geplaatst[ $bestand ] = true;
    }
    return $geplaatst;
}

/**
 * CSS-bestanden die bij een lijst bloknamen horen, basis eerst als een van
 * de blokken die nodig heeft.
 *
 * @param  string[] $namen Bloknamen.
 * @return string[]
 */
function sfp_page_config_blokken_bestanden_voor( array $namen ) {
    $defs      = sfp_page_config_blokken();
    $bestanden = array();
    $basis     = false;
    foreach ( $namen as $naam ) {
        if ( ! isset( $defs[ $naam ] ) ) {
            continue;
        }
        if ( empty( $defs[ $naam ]['zonder_basis'] ) ) {
            $basis = true;
        }
        if ( ! empty( $defs[ $naam ]['css'] ) ) {
            $bestanden[ $defs[ $naam ]['css'] ] = true;
        }
        // Gedeelde CSS van meer dan één blok (bijvoorbeeld de deelknoppen).
        if ( ! empty( $defs[ $naam ]['css_extra'] ) ) {
            foreach ( (array) $defs[ $naam ]['css_extra'] as $extra ) {
                $bestanden[ $extra ] = true;
            }
        }
    }
    // Basis staat hooguit één keer in de lijst, en dan vooraan.
    unset( $bestanden['basis'] );
    $bestanden = array_keys( $bestanden );
    if ( $basis ) {
        array_unshift( $bestanden, 'basis' );
    }
    return $bestanden;
}

/**
 * Zoek alle sfp-blokken in een lijst geparste blokken, ook genest en in
 * herbruikbare blokken.
 *
 * @param  array $blokken Uitvoer van parse_blocks().
 * @param  array $namen   Gevonden namen (verzamelt).
 * @param  int   $diepte  Bescherming tegen eindeloze verwijzingen.
 * @return array
 */
function sfp_page_config_blokken_zoek( array $blokken, array $namen = array(), $diepte = 0 ) {
    if ( $diepte > 8 ) {
        return $namen;
    }
    foreach ( $blokken as $blok ) {
        $naam = isset( $blok['blockName'] ) ? (string) $blok['blockName'] : '';
        if ( 0 === strpos( $naam, 'sfp/' ) && 'sfp/copy' !== $naam ) {
            $namen[ $naam ] = true;
        }
        if ( 'core/block' === $naam && ! empty( $blok['attrs']['ref'] ) ) {
            $ref = get_post( (int) $blok['attrs']['ref'] );
            if ( $ref && 'wp_block' === $ref->post_type ) {
                $namen = sfp_page_config_blokken_zoek( parse_blocks( $ref->post_content ), $namen, $diepte + 1 );
            }
        }
        if ( ! empty( $blok['innerBlocks'] ) ) {
            $namen = sfp_page_config_blokken_zoek( $blok['innerBlocks'], $namen, $diepte + 1 );
        }
    }
    return $namen;
}

add_action( 'wp_head', 'sfp_page_config_blokken_head_css', 20 );

/**
 * Zet de CSS van de blokken op deze pagina in de head.
 */
function sfp_page_config_blokken_head_css() {
    if ( is_admin() || ! is_singular() ) {
        return;
    }
    $post = get_queried_object();
    if ( ! $post instanceof WP_Post ) {
        return;
    }
    $tabel = false !== strpos( $post->post_content, 'is-style-sfp-tabel' );
    if ( ! $tabel && false === strpos( $post->post_content, '<!-- wp:sfp/' ) ) {
        return;
    }
    $namen = array_keys( sfp_page_config_blokken_zoek( parse_blocks( $post->post_content ) ) );
    if ( ! $namen && ! $tabel ) {
        return;
    }
    $bestanden = sfp_page_config_blokken_bestanden_voor( $namen );
    if ( $tabel ) {
        $bestanden[] = 'tabel';
    }
    /**
     * CSS-bestanden van deze pagina (3.30.0: nlvorm bij de held wit).
     *
     * @param string[] $bestanden Bestanden.
     * @param WP_Post  $post      Pagina.
     */
    $bestanden = (array) apply_filters( 'sfp_page_config_blokken_bestanden', $bestanden, $post );
    $geplaatst = sfp_page_config_blokken_geplaatst();
    $css       = empty( $geplaatst['variabelen'] ) ? sfp_page_config_blokken_css_variabelen() : '';
    foreach ( $bestanden as $bestand ) {
        $css .= sfp_page_config_blokken_css_bestand( $bestand );
        sfp_page_config_blokken_geplaatst( $bestand );
    }
    sfp_page_config_blokken_geplaatst( 'variabelen' );
    echo '<style id="sfp-blokken">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste CSS uit de plugin plus gevalideerde kleuren.
}

/**
 * CSS die een blok nog nodig heeft en die nog niet op de pagina staat.
 * Voor blokken buiten de hoofdinhoud (Astra-hooks, widgets).
 *
 * @param  string $naam Bloknaam.
 * @return string       Een style-element, of ''.
 */
function sfp_page_config_blokken_css_vangnet( $naam ) {
    if ( is_admin() || wp_is_json_request() ) {
        return '';
    }
    $geplaatst = sfp_page_config_blokken_geplaatst();
    $css       = '';
    if ( empty( $geplaatst['variabelen'] ) ) {
        $css .= sfp_page_config_blokken_css_variabelen();
        sfp_page_config_blokken_geplaatst( 'variabelen' );
    }
    foreach ( sfp_page_config_blokken_bestanden_voor( array( $naam ) ) as $bestand ) {
        if ( empty( $geplaatst[ $bestand ] ) ) {
            $css .= sfp_page_config_blokken_css_bestand( $bestand );
            sfp_page_config_blokken_geplaatst( $bestand );
        }
    }
    return '' === $css ? '' : '<style id="sfp-blokken-' . esc_attr( sanitize_key( str_replace( 'sfp/', '', $naam ) ) ) . '">' . $css . '</style>';
}

/**
 * Een los CSS-bestand dat nog niet op de pagina staat (3.30.0), voor een
 * variant van een blok met eigen opmaak, zoals de NL-vorm.
 *
 * @param  string $bestand Bestandsnaam zonder .css.
 * @return string          Een style-element, of ''.
 */
function sfp_page_config_blokken_css_vangnet_bestand( $bestand ) {
    if ( is_admin() || wp_is_json_request() ) {
        return '';
    }
    $geplaatst = sfp_page_config_blokken_geplaatst();
    if ( ! empty( $geplaatst[ $bestand ] ) ) {
        return '';
    }
    sfp_page_config_blokken_geplaatst( $bestand );
    return '<style id="sfp-blokken-' . esc_attr( sanitize_key( $bestand ) ) . '">' . sfp_page_config_blokken_css_bestand( $bestand ) . '</style>';
}

add_action( 'enqueue_block_assets', 'sfp_page_config_blokken_editor_css' );

/**
 * Dezelfde CSS in de blokeditor, zodat de blokken er daar ook zo uitzien.
 */
function sfp_page_config_blokken_editor_css() {
    if ( ! is_admin() ) {
        return;
    }
    // Sinds 3.22.0 de vaste opmaak als bestanden (de browser bewaart ze
    // tussen keren openen, verkleind door de releaseworkflow); alleen het merk
    // en de variabelen van deze site staan inline. Zie
    // sfp_page_config_editor_blok_bestanden() in editor.php.
    sfp_page_config_editor_blok_bestanden( sfp_page_config_editor_merk_css() . sfp_page_config_blokken_css_variabelen() );
}

/**
 * De CSS-bestanden van de bouwstenen in de editor. Sinds 3.16.0 ook
 * definitie, sinds 3.18.0 zelftest-cta.
 *
 * @return string[]
 */
function sfp_page_config_blokken_editor_bestanden() {
    return array( 'basis', 'held', 'kolommen', 'kaart', 'stappen', 'uitklap', 'tabs', 'lijst', 'faq', 'tabel', 'deelkaart', 'samenvatting', 'kader', 'citaat', 'inzicht', 'lees-ook', 'vervolg', 'logostrook', 'keuzehulp', 'review-kaart', 'definitie', 'zelftest-cta', 'kopieer', 'bronnen', 'praktijkblok', 'auteursbio', 'merkwaarden', 'nlvorm-held', 'nlvorm-inhoud', 'nlvorm-groepen', 'nlvorm-inschrijven', 'nlvorm-coach', 'nlvorm-home', 'nlvorm-slot', 'nlvorm-over', 'nlvorm-archief', 'verhaal', 'formulier', 'editor' );
}

/**
 * Het merk van de site in de editor, zodat blokken er daar uitzien zoals op
 * de site (ontwerpregel 19). Op de site zetten twee bronnen dit in de head,
 * alleen voor bezoekers:
 *   - het koplettertype (includes/kopfont.php);
 *   - de kleurrollen --sfp-rol-* (includes/fundament.php, onderdeel
 *     Kleurrollen; tot 3.15.0 alleen uit het snippet "Kleuren: rollen van
 *     het palet"). De editor gebruikt dezelfde toewijzing, zodat er één bron
 *     blijft. Zonder toewijzing vallen de blokken terug op Astra.
 *   - met het onderdeel Kopfont aan ook --ux-display-font en
 *     --ux-heading-font, zoals op de site (sinds 3.15.0).
 *
 * @since 3.1.0
 * @return string
 */
function sfp_page_config_editor_merk_css() {
    $css = '';
    if ( function_exists( 'sfp_page_config_get_brand' ) ) {
        $brand  = sfp_page_config_get_brand();
        $font   = isset( $brand['font'] ) ? wp_strip_all_tags( (string) $brand['font'] ) : '';
        $weight = isset( $brand['weight'] ) ? wp_strip_all_tags( (string) $brand['weight'] ) : '';
        if ( '' !== $font ) {
            $css .= '--sfp-kopfont:' . $font . ';';
        }
        if ( '' !== $weight ) {
            $css .= '--sfp-kopfont-gewicht:' . $weight . ';';
        }
    }
    if ( function_exists( 'sfp_page_config_kleurrollen_declaraties' ) ) {
        $css .= sfp_page_config_kleurrollen_declaraties();
    }
    if ( function_exists( 'sfp_page_config_onderdeel_aan' ) && sfp_page_config_onderdeel_aan( 'kopfont' ) && function_exists( 'sfp_page_config_kopfont_ux_declaraties' ) ) {
        $css .= sfp_page_config_kopfont_ux_declaraties();
    }
    return '' === $css ? '' : ':root{' . $css . '}';
}

/* =========================================================================
 * Script (tabs en FAQ-ankers)
 * ====================================================================== */

/**
 * Markeer dat het script nodig is.
 *
 * @param  bool $zet True om te markeren.
 * @return bool
 */
function sfp_page_config_blokken_js_nodig( $zet = false ) {
    static $nodig = false;
    if ( $zet ) {
        $nodig = true;
    }
    return $nodig;
}

add_action( 'wp_footer', 'sfp_page_config_blokken_js', 20 );

/**
 * Het script voor tabs (wisselen, pijltjestoetsen) en FAQ (een link naar
 * #vraag opent die vraag). Werkt aanvullend: zonder script blijft alles
 * leesbaar.
 */
function sfp_page_config_blokken_js() {
    if ( ! sfp_page_config_blokken_js_nodig() ) {
        return;
    }
    $js = '(function(){'
        . 'document.querySelectorAll(".sfp-tabs").forEach(function(w){var t=[].slice.call(w.querySelectorAll("[role=tab]"));if(!t.length)return;'
        . 'function k(x,f){t.forEach(function(y){var o=y===x;y.setAttribute("aria-selected",o?"true":"false");y.tabIndex=o?0:-1;var p=document.getElementById(y.getAttribute("aria-controls"));if(p)p.hidden=!o;});if(f)x.focus();}'
        . 't.forEach(function(x,i){x.addEventListener("click",function(){k(x);});x.addEventListener("keydown",function(e){var n=e.key==="ArrowRight"?1:e.key==="ArrowLeft"?-1:0;if(n)k(t[(i+n+t.length)%t.length],1);});});});'
        . 'function o(){var h=location.hash.slice(1);if(!h)return;var d=document.getElementById(decodeURIComponent(h));if(d&&d.tagName==="DETAILS")d.open=true;}'
        . 'o();window.addEventListener("hashchange",o);'
        . 'document.addEventListener("click",function(e){var a=e.target.closest&&e.target.closest("a[href^=\\"#\\"]");if(a&&a.getAttribute("href")===location.hash)setTimeout(o,0);});'
        . '})();';
    echo '<script id="sfp-blokken-js" nowprocket>' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste JS uit dit bestand.
}

/* =========================================================================
 * Hulpfuncties voor de weergave
 * ====================================================================== */

/**
 * Attribuut als string.
 *
 * @param  array  $attrs    Blokattributen.
 * @param  string $sleutel  Naam.
 * @param  string $standaard Terugval.
 * @return string
 */
function sfp_page_config_blok_attr( $attrs, $sleutel, $standaard = '' ) {
    return isset( $attrs[ $sleutel ] ) && is_scalar( $attrs[ $sleutel ] ) ? (string) $attrs[ $sleutel ] : $standaard;
}

/**
 * Waarde uit een vaste lijst, anders de eerste.
 *
 * @param  string   $waarde Waarde.
 * @param  string[] $lijst  Toegestane waarden.
 * @return string
 */
function sfp_page_config_blok_kies( $waarde, array $lijst ) {
    return in_array( $waarde, $lijst, true ) ? $waarde : $lijst[0];
}

/**
 * Omhullende attributen met klassen en optioneel een anker.
 *
 * @param  string[] $klassen Klassen.
 * @param  string   $anker   Id zonder #.
 * @return string
 */
function sfp_page_config_blok_omhulling( array $klassen, $anker = '' ) {
    $extra = array( 'class' => implode( ' ', array_filter( $klassen ) ) );
    $anker = sanitize_title( $anker );
    if ( '' !== $anker ) {
        $extra['id'] = $anker;
    }
    return get_block_wrapper_attributes( $extra );
}

/* =========================================================================
 * Weergave per blok
 * ====================================================================== */

/**
 * sfp/sectie.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_sectie( $attrs, $content ) {
    $achtergrond = sfp_page_config_blok_kies( sfp_page_config_blok_attr( $attrs, 'achtergrond', 'wit' ), array( 'wit', 'tint' ) );
    $klassen     = array( 'sfp-sectie', 'tint' === $achtergrond ? 'sfp-sectie--tint' : '' );
    return sfp_page_config_blokken_css_vangnet( 'sfp/sectie' )
        . '<section ' . sfp_page_config_blok_omhulling( $klassen, sfp_page_config_blok_attr( $attrs, 'anker' ) ) . '><div class="sfp-sectie__binnen">' . $content . '</div></section>';
}

/**
 * sfp/held.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_held( $attrs, $content, $blok = null ) {
    $variant = sfp_page_config_blok_kies( sfp_page_config_blok_attr( $attrs, 'variant', 'recht' ), array( 'recht', 'breed', 'wit', 'foto' ) );
    // Held foto (3.34.0, home): tekst op de foto, aparte foto voor de telefoon.
    if ( 'foto' === $variant && function_exists( 'sfp_page_config_nlvorm_held_foto' ) ) {
        return sfp_page_config_blokken_css_vangnet( 'sfp/held' ) . sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-held' ) . sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-home' )
            . sfp_page_config_nlvorm_held_foto( $attrs, $blok, $content );
    }
    // Held wit (3.30.0, NL-vorm): tekst links, foto rechts met feitenkaart.
    if ( 'wit' === $variant && function_exists( 'sfp_page_config_nlvorm_held' ) ) {
        return sfp_page_config_blokken_css_vangnet( 'sfp/held' ) . sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-held' )
            . str_replace( '<section ', '<section style="--sfp-held-focus:' . esc_attr( sfp_page_config_nlvorm_focus( sfp_page_config_blok_attr( $attrs, 'focus', '50% 30%' ) ) ) . '" ', '<section ' . sfp_page_config_blok_omhulling( array( 'sfp-held', 'sfp-held--wit', 'held-feit', 'held-accent', 'held-bewijs' ), sfp_page_config_blok_attr( $attrs, 'anker' ) ) . '>' )
            . sfp_page_config_nlvorm_held( $attrs, $blok, $content ) . '</section>';
    }
    $klassen = array( 'sfp-held', 'sfp-held--recht', 'breed' === $variant ? 'sfp-held--breed' : '' );
    // Een tool in de held staat in de tweede kolom (3.21.0).
    if ( false !== strpos( (string) $content, 'class="sfp-tool ' ) ) {
        $klassen[] = 'sfp-held--tool';
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/held' )
        . '<section ' . sfp_page_config_blok_omhulling( $klassen, sfp_page_config_blok_attr( $attrs, 'anker' ) ) . '><div class="sfp-held__binnen">' . $content . '</div></section>';
}

/**
 * sfp/kolommen.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_kolommen( $attrs, $content, $blok = null ) {
    $indeling   = sfp_page_config_blok_kies( sfp_page_config_blok_attr( $attrs, 'indeling', 'twee' ), array( 'twee', 'drie', 'tekst-beeld', 'tekst-boek', 'boeking', 'breed-smal', 'situaties', 'reviews', 'boek', 'inschrijven', 'pakketten', 'slot' ) );
    $carrousel  = 'reviews' === $indeling && ! empty( $attrs['carrousel'] );
    // De indelingen van de NL-vorm (3.30.0) hebben nlvorm.css nodig.
    $nlvorm     = in_array( $indeling, array( 'situaties', 'reviews' ), true ) ? sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-inhoud' ) : '';
    $nlvorm    .= in_array( $indeling, array( 'boek', 'inschrijven' ), true ) ? sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-inschrijven' ) : '';
    $nlvorm    .= 'pakketten' === $indeling ? sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-coach' ) : '';
    $nlvorm    .= 'slot' === $indeling || $carrousel ? sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-home' ) : '';
    $nlvorm    .= 'slot' === $indeling ? sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-slot' ) : '';
    $uitlijning = sfp_page_config_blok_attr( $attrs, 'uitlijning' );
    $uitlijning = in_array( $uitlijning, array( 'boven', 'midden', 'onder', 'rekken' ), true ) ? 'sfp-kolommen--' . $uitlijning : '';
    // Verhaalvorm (3.42.0, besluit Stephan 10-11): tekst en afgerond beeld met lange tekst.
    if ( 'tekst-beeld' === $indeling && $blok instanceof WP_Block ) {
        $verhaal = sfp_page_config_kolommen_verhaal( $blok );
        if ( '' !== $verhaal ) {
            return sfp_page_config_blokken_css_vangnet( 'sfp/kolommen' ) . $nlvorm . sfp_page_config_blokken_css_vangnet_bestand( 'verhaal' )
                . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-kolommen', 'sfp-kolommen--tekst-beeld', 'sfp-verhaal' ) ) . '>' . $verhaal . '</div>';
        }
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/kolommen' ) . $nlvorm
        . '<div ' . sfp_page_config_blok_omhulling( array_filter( array( 'sfp-kolommen', 'sfp-kolommen--' . $indeling, $uitlijning, 'boeking' === $indeling ? 'sfp-boekkaart' : '', 'reviews' === $indeling ? 'sfp-reviews-rij' : '', $carrousel ? 'sfp-reviews-rij--carrousel' : '' ) ) ) . '>' . $content . '</div>';
}

/**
 * Verhaalvorm voor Kolommen, tekst en beeld (3.42.0, besluit Stephan 10-11):
 * staat naast een afgerond beeld een tekst met meer dan twee alinea's, dan
 * blijven label, kop en eerste alinea naast het beeld staan, even hoog. De
 * rest staat onder een uitklaplink, uitgeklapt in drie kolommen. De tekst
 * blijft in de pagina (hidden="until-found"): zoeken in de pagina en
 * zoekmachines vinden hem. Geldt vanzelf voor elk blok dat hieraan voldoet.
 *
 * @param  WP_Block $blok Het blok Kolommen.
 * @return string Binnenkant, of leeg als de vorm niet past.
 */
function sfp_page_config_kolommen_verhaal( $blok ) {
    $tekst = null;
    $beeld = null;
    foreach ( $blok->inner_blocks as $kind ) {
        $klasse = isset( $kind->attributes['className'] ) ? ' ' . $kind->attributes['className'] . ' ' : ' ';
        if ( 'core/image' === $kind->name && false !== strpos( $klasse, ' is-style-sfp-afgerond ' ) ) {
            $beeld = $beeld ? $beeld : $kind;
            continue;
        }
        if ( 'sfp/kolom' !== $kind->name ) {
            return '';
        }
        $binnen = $kind->inner_blocks;
        if ( 1 === count( $binnen ) && 'core/image' === $binnen[0]->name ) {
            $bk = isset( $binnen[0]->attributes['className'] ) ? ' ' . $binnen[0]->attributes['className'] . ' ' : ' ';
            if ( false !== strpos( $bk, ' is-style-sfp-afgerond ' ) && ! $beeld ) {
                $beeld = $binnen[0];
                continue;
            }
        }
        if ( $tekst ) {
            return '';
        }
        $tekst = $kind;
    }
    if ( ! $tekst || ! $beeld ) {
        return '';
    }
    // Alinea's van de lopende tekst (geen label).
    $alineas = 0;
    foreach ( $tekst->inner_blocks as $kind ) {
        $klasse = isset( $kind->attributes['className'] ) ? (string) $kind->attributes['className'] : '';
        if ( 'core/paragraph' === $kind->name && false === strpos( $klasse, 'is-style-sfp-label' ) ) {
            ++$alineas;
        }
    }
    if ( $alineas < 3 ) {
        return '';
    }
    $boven = '';
    $meer  = '';
    $lead  = false;
    foreach ( $tekst->inner_blocks as $kind ) {
        $html = $kind->render();
        if ( $lead ) {
            $meer .= $html;
            continue;
        }
        $klasse = isset( $kind->attributes['className'] ) ? (string) $kind->attributes['className'] : '';
        if ( 'core/paragraph' === $kind->name && false === strpos( $klasse, 'is-style-sfp-label' ) ) {
            $lead  = true;
            $html  = preg_replace( '/^(\s*<p\b[^>]*?)(\sclass="([^"]*)")/', '$1 class="$3 sfp-verhaal__lead"', $html, 1, $gedaan );
            if ( ! $gedaan ) {
                $html = preg_replace( '/^(\s*<p)\b/', '$1 class="sfp-verhaal__lead"', $html, 1 );
            }
        }
        $boven .= $html;
    }
    static $n = 0;
    ++$n;
    $id     = 'sfp-verhaal-' . $n;
    $open   = sfp_page_config_tekst( 'verhaal_meer' );
    $dicht  = sfp_page_config_tekst( 'verhaal_minder' );
    $klik   = "var m=document.getElementById(this.getAttribute('aria-controls')),o=this.getAttribute('aria-expanded')!=='true';m.hidden=o?false:'until-found';this.setAttribute('aria-expanded',o?'true':'false');this.firstChild.textContent=o?this.dataset.dicht:this.dataset.open;";
    $knop   = '<button type="button" class="sfp-verhaal__knop" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '" data-open="' . esc_attr( $open ) . '" data-dicht="' . esc_attr( $dicht ) . '" onclick="' . esc_attr( $klik ) . '">' . esc_html( $open ) . '<span aria-hidden="true"></span></button>';
    $script = '<script nowprocket>document.getElementById(' . wp_json_encode( $id ) . ').addEventListener("beforematch",function(){var b=document.querySelector("[aria-controls=' . esc_js( $id ) . ']");if(b&&b.getAttribute("aria-expanded")!=="true"){b.click();}});</script>';
    return '<div class="sfp-kolom sfp-verhaal__tekst">' . $boven . $knop . '</div>'
        . '<div class="sfp-verhaal__beeld">' . $beeld->render() . '</div>'
        . '<div class="sfp-verhaal__meer" id="' . esc_attr( $id ) . '" hidden="until-found">' . $meer . '</div>' . $script;
}

/**
 * sfp/kolom.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_kolom( $attrs, $content ) {
    return '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-kolom' ) ) . '>' . $content . '</div>';
}

/**
 * sfp/kaart.
 *
 * Met een link wordt de hele kaart klikbaar via een onzichtbare laag;
 * links in de kaart zelf blijven werken en blijven bereikbaar met het
 * toetsenbord.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_kaart( $attrs, $content, $blok = null ) {
    $variant = sfp_page_config_blok_kies( sfp_page_config_blok_attr( $attrs, 'variant', 'standaard' ), array( 'standaard', 'vlak', 'donker' ) );
    $link    = esc_url( sfp_page_config_blok_attr( $attrs, 'link' ) );
    $beeld   = ! empty( $attrs['beeld'] );
    $klassen = array(
        'sfp-kaart',
        'standaard' !== $variant ? 'sfp-kaart--' . $variant : '',
        ! empty( $attrs['ruim'] ) ? 'sfp-kaart--ruim' : '',
        ! empty( $attrs['smal'] ) ? 'sfp-kaart--smal' : '',
        '' !== $link ? 'sfp-kaart--link' : '',
        $beeld ? 'sfp-kaart--beeld' : '',
    );
    $laag    = '' !== $link ? '<a class="sfp-kaart__link" href="' . $link . '" tabindex="-1" aria-hidden="true"></a>' : '';
    $extra   = '';
    // Beeld boven (3.34.0, H3): de eerste afbeelding over de volle breedte,
    // de rest in een eigen vak met ruimte eromheen.
    if ( $beeld ) {
        $extra = sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-home' );
        if ( $blok instanceof WP_Block && $blok->inner_blocks ) {
            $foto  = '';
            $tekst = '';
            foreach ( $blok->inner_blocks as $kind ) {
                $html = $kind->render();
                if ( '' === $foto && 'core/image' === $kind->name ) {
                    $foto = $html;
                } else {
                    $tekst .= $html;
                }
            }
            $content = $foto . '<div class="sfp-kaart__tekst">' . $tekst . '</div>';
        }
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/kaart' ) . $extra
        . '<div ' . sfp_page_config_blok_omhulling( $klassen ) . '>' . $content . $laag . '</div>';
}

/**
 * sfp/stappen.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_stappen( $attrs, $content ) {
    // Uitkomsten (3.30.0, NL-vorm): genummerd 01 tot en met 03, met lijnen.
    if ( 'uitkomsten' === sfp_page_config_blok_attr( $attrs, 'vorm', 'stappen' ) ) {
        return sfp_page_config_blokken_css_vangnet( 'sfp/stappen' ) . sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-inhoud' )
            . '<ol ' . sfp_page_config_blok_omhulling( array( 'sfp-stappen', 'sfp-uitkomsten' ) ) . '>' . $content . '</ol>';
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/stappen' )
        . '<ol ' . sfp_page_config_blok_omhulling( array( 'sfp-stappen' ) ) . '>' . $content . '</ol>';
}

/**
 * sfp/stap.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_stap( $attrs, $content ) {
    return '<li ' . sfp_page_config_blok_omhulling( array( 'sfp-stap' ) ) . '>' . $content . '</li>';
}

/**
 * Toegestane opmaak in titels van regels, vragen en tabs.
 *
 * @return array
 */
function sfp_page_config_blok_titel_kses() {
    return array(
        'strong' => array(),
        'em'     => array(),
        'b'      => array(),
        'i'      => array(),
        'br'     => array(),
        'span'   => array( 'class' => true ),
        'code'   => array(),
    );
}

/**
 * sfp/uitklap.
 *
 * @param  array    $attrs   Attributen.
 * @param  string   $content Binnenblokken.
 * @param  WP_Block $block   Blokinstantie.
 * @return string
 */
function sfp_page_config_render_uitklap( $attrs, $content, $block = null ) {
    $variant = sfp_page_config_blok_kies( sfp_page_config_blok_attr( $attrs, 'variant', 'groot' ), array( 'groot', 'compact', 'kaart', 'verhaal', 'programma', 'vergelijk' ) );
    // Vergelijk (3.31.0, NL-vorm coach): uitklap als kaart met een vergelijktabel.
    if ( 'vergelijk' === $variant ) {
        return sfp_page_config_blokken_css_vangnet( 'sfp/uitklap' ) . sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-coach' )
            . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-uitklap', 'sfp-uitklap--kaart', 'sfp-uitklap--vergelijk' ) ) . '>' . $content . '</div>';
    }
    // Programma (3.30.0, NL-vorm): kaarten; twee dagen naast elkaar en samen
    // open, meer dan twee gestapeld.
    if ( 'programma' === $variant ) {
        $aantal = substr_count( (string) $content, '<details class="sfp-uitklap__regel' );
        $klas   = array( 'sfp-uitklap', 'sfp-uitklap--kaart', 'sfp-uitklap--programma', 2 === $aantal ? '' : 'sfp-uitklap--gestapeld' );
        if ( 2 === $aantal && function_exists( 'sfp_page_config_nlvorm_programma_nodig' ) ) {
            sfp_page_config_nlvorm_programma_nodig( true );
        }
        return sfp_page_config_blokken_css_vangnet( 'sfp/uitklap' ) . sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-inhoud' )
            . '<div ' . sfp_page_config_blok_omhulling( $klas ) . '>' . $content . '</div>';
    }
    if ( ! empty( $attrs['eenTegelijk'] ) ) {
        // Eén regel tegelijk open: het name-attribuut van details doet dat
        // zonder JavaScript in browsers die het kennen.
        $groep   = esc_attr( wp_unique_id( 'sfp-uitklap-' ) );
        $content = str_replace( '<details class="sfp-uitklap__regel', '<details name="' . $groep . '" class="sfp-uitklap__regel', $content );
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/uitklap' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-uitklap', 'sfp-uitklap--' . $variant ) ) . '>' . $content . '</div>';
}

/**
 * sfp/uitklap-regel.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_uitklap_regel( $attrs, $content ) {
    $titel = wp_kses( sfp_page_config_blok_attr( $attrs, 'titel' ), sfp_page_config_blok_titel_kses() );
    $sub   = wp_kses( sfp_page_config_blok_attr( $attrs, 'sub' ), sfp_page_config_blok_titel_kses() );
    $kop   = '' === $sub ? $titel : '<span class="sfp-uitklap__titel">' . $titel . '<span class="sfp-uitklap__sub">' . $sub . '</span></span>';
    // Variant verhaal: een label boven de titel ("Een typisch geval").
    // Sinds 3.22.0 toont de regel onder de titel (teaser) ook met een label.
    $label = wp_kses( sfp_page_config_blok_attr( $attrs, 'label' ), sfp_page_config_blok_titel_kses() );
    if ( '' !== $label ) {
        $kop = '<span class="sfp-uitklap__titel"><small class="sfp-blok-label">' . $label . '</small><b>' . $titel . '</b>' . ( '' !== $sub ? '<span class="sfp-uitklap__sub">' . $sub . '</span>' : '' ) . '</span>';
    }
    $anker = sfp_page_config_blok_attr( $attrs, 'anker' );
    if ( '' !== $anker ) {
        // Een link naar #anker opent deze regel; dat doet het script.
        sfp_page_config_blokken_js_nodig( true );
    }
    return '<details ' . sfp_page_config_blok_omhulling( array( 'sfp-uitklap__regel' ), $anker ) . '><summary>' . $kop . '</summary><div class="sfp-uitklap__inhoud">' . $content . '</div></details>';
}

/**
 * sfp/tabs. Bouwt de tablijst uit de titels van de tabbladen; het eerste
 * tabblad staat open, de andere zijn verborgen tot het script wisselt.
 *
 * @param  array    $attrs   Attributen.
 * @param  string   $content Binnenblokken (niet gebruikt; de tabbladen worden hier zelf getoond).
 * @param  WP_Block $block   Blokinstantie.
 * @return string
 */
function sfp_page_config_render_tabs( $attrs, $content, $block = null ) {
    if ( ! $block instanceof WP_Block ) {
        return $content;
    }
    sfp_page_config_blokken_js_nodig( true );
    $label   = sfp_page_config_blok_attr( $attrs, 'label' );
    $kaart   = 'kaart' === sfp_page_config_blok_attr( $attrs, 'vorm' );
    $knoppen = '';
    $panelen = '';
    $nr      = 0;
    // Sinds 3.43.0: Tabs die de variantkeuze volgen. Elke tab krijgt het
    // aantal dagen uit zijn titel; de tab van de standaardvariant staat open.
    $dagen  = array();
    $open   = 0;
    $volgt  = ! empty( $attrs['variant'] ) && function_exists( 'sfp_page_config_cursus_varianten' );
    if ( $volgt ) {
        $v     = sfp_page_config_cursus_varianten( (int) get_the_ID() );
        $volgt = $v['aan'];
    }
    if ( $volgt ) {
        $i = 0;
        foreach ( $block->inner_blocks as $tab ) {
            if ( 'sfp/tab' !== $tab->name ) {
                continue;
            }
            $dagen[ $i ] = sfp_page_config_tab_dagen( sfp_page_config_blok_attr( $tab->attributes, 'titel' ) );
            if ( $dagen[ $i ] && $dagen[ $i ] === (int) $v['standaard'] ) {
                $open = $i;
            }
            ++$i;
        }
    }
    foreach ( $block->inner_blocks as $tab ) {
        if ( 'sfp/tab' !== $tab->name ) {
            continue;
        }
        $id       = wp_unique_id( 'sfp-tab-' );
        $titel    = wp_kses( sfp_page_config_blok_attr( $tab->attributes, 'titel' ), sfp_page_config_blok_titel_kses() );
        $eerste   = $open === $nr;
        $knoppen .= '<button type="button" role="tab" id="' . esc_attr( $id ) . '-k" aria-controls="' . esc_attr( $id ) . '" aria-selected="' . ( $eerste ? 'true' : 'false' ) . '"' . ( $eerste ? '' : ' tabindex="-1"' ) . ( ! empty( $dagen[ $nr ] ) ? ' data-sfp-dagen="' . esc_attr( (string) $dagen[ $nr ] ) . '"' : '' ) . '>' . $titel . '</button>';
        $binnen   = $tab->render();
        // Als kaart (3.34.0, O1): tekst links, icoon rechts in de secundaire kleur.
        if ( $kaart ) {
            $icoon  = sfp_page_config_tab_icoon( (int) ( $tab->attributes['icoonId'] ?? 0 ), (string) ( $tab->attributes['icoon'] ?? '' ) );
            $binnen = '<div class="sfp-tabpaneel__tekst">' . $binnen . '</div>' . $icoon;
        }
        $panelen .= '<div class="sfp-tabpaneel" role="tabpanel" id="' . esc_attr( $id ) . '" aria-labelledby="' . esc_attr( $id ) . '-k"' . ( $eerste ? '' : ' hidden' ) . '>' . $binnen . '</div>';
        ++$nr;
    }
    $lijst = '<div class="sfp-tablijst" role="tablist"' . ( '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : '' ) . '>' . $knoppen . '</div>';
    return sfp_page_config_blokken_css_vangnet( 'sfp/tabs' ) . ( $kaart ? sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-over' ) : '' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-tabs', $kaart ? 'sfp-tabs--kaart' : '', $volgt ? 'sfp-tabs--variant' : '' ) ) . '>' . $lijst . $panelen . '</div>';
}

/**
 * Het aantal dagen uit de titel van een tab (3.43.0): het eerste getal, als
 * er een woord voor dag of dagen achter staat ("3 dagen", "1 day", "2 dias").
 *
 * @param  string $titel Titel van de tab.
 * @return int 0 als er geen aantal dagen in staat.
 */
function sfp_page_config_tab_dagen( $titel ) {
    $tekst = html_entity_decode( wp_strip_all_tags( (string) $titel ), ENT_QUOTES, 'UTF-8' );
    if ( preg_match( '/(\d{1,2})\s*(dag|dagen|day|days|dia|dias)\b/iu', $tekst, $m ) ) {
        return (int) $m[1];
    }
    return 0;
}

/**
 * Het icoon van een tab (3.34.0): een afbeelding uit de media als masker,
 * zodat het de secundaire kleur van de site krijgt. Sinds 3.37.0 ook een
 * icoon uit de vaste set; een afbeelding uit de media gaat voor. Geen
 * icoon: leeg.
 *
 * @param  int    $id    Bijlage.
 * @param  string $vast  Naam uit de vaste set.
 * @return string
 */
function sfp_page_config_tab_icoon( $id, $vast = '' ) {
    $url = '';
    if ( $id > 0 && wp_attachment_is_image( $id ) ) {
        $url = esc_url( (string) wp_get_attachment_url( $id ) );
    }
    if ( '' === $url && '' !== $vast ) {
        $url = sfp_page_config_icoon_data( $vast );
    }
    if ( '' === $url ) {
        return '';
    }
    return '<span class="sfp-tabpaneel__icoon" aria-hidden="true"><i style="--sfp-icoon:url(&quot;' . $url . '&quot;)"></i></span>';
}

/**
 * De vaste iconenset (3.37.0, punt 5): lijniconen in assets/iconen/, één
 * kleur via het masker. Aanvullen kan zonder code met een SVG uit de media;
 * een icoon aan deze set toevoegen is een bestand plus een regel hier.
 *
 * @return array<string, string> Naam => omschrijving.
 */
function sfp_page_config_iconen() {
    return array(
        'verbinding'     => 'Verbinding',
        'structuur'      => 'Structuur',
        'authenticiteit' => 'Hart',
        'gesprek'        => 'Gesprek',
        'afstemming'     => 'Afstemming',
        'focus'          => 'Focus',
        'kennis'         => 'Kennis',
        'proces'         => 'Proces',
        'groei'          => 'Groei',
        'beter'          => 'Beter worden',
        'voorbeeld'      => 'Ster',
        'eenvoud'        => 'Eenvoud',
        'correct'        => 'Correct',
        'helder'         => 'Helder',
        'doeltreffend'   => 'Doeltreffend',
        'spreker'        => 'Spreker',
        'schrijven'      => 'Schrijven',
        'groep'          => 'Groep',
    );
}

/**
 * Een icoon uit de vaste set als data-URI, voor een CSS-masker.
 *
 * @param  string $naam Naam uit sfp_page_config_iconen().
 * @return string       Leeg bij een onbekende naam.
 */
function sfp_page_config_icoon_data( $naam ) {
    $naam = sanitize_key( (string) $naam );
    if ( ! isset( sfp_page_config_iconen()[ $naam ] ) ) {
        return '';
    }
    $pad = SFP_PAGE_CONFIG_DIR . 'assets/iconen/' . $naam . '.svg';
    $svg = is_readable( $pad ) ? trim( (string) file_get_contents( $pad ) ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- eigen bestand.
    return '' === $svg ? '' : 'data:image/svg+xml,' . rawurlencode( $svg );
}

/**
 * sfp/tab. De omhulling maakt sfp/tabs.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_tab( $attrs, $content ) {
    return $content;
}

/**
 * sfp/lijst.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_lijst( $attrs, $content ) {
    $stijl = sfp_page_config_blok_kies( sfp_page_config_blok_attr( $attrs, 'stijl', 'vink' ), array( 'vink', 'regels', 'letters', 'kruis', 'nummers' ) );
    $rij   = ! empty( $attrs['rij'] );
    return sfp_page_config_blokken_css_vangnet( 'sfp/lijst' ) . ( $rij ? sfp_page_config_blokken_css_vangnet_bestand( 'nlvorm-inhoud' ) : '' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-lijst', 'sfp-lijst--' . $stijl, $rij ? 'sfp-lijst--rij' : '' ) ) . '>' . $content . '</div>';
}

/* =========================================================================
 * FAQ en schema
 * ====================================================================== */

/**
 * Verzamel vragen en antwoorden voor het FAQPage-schema.
 *
 * @param  array|null $paar Vraag en antwoord, of null om te lezen.
 * @return array
 */
function sfp_page_config_faq_verzamel( $paar = null ) {
    static $paren = array();
    if ( null !== $paar ) {
        $paren[] = $paar;
    }
    return $paren;
}

/**
 * sfp/faq.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_faq( $attrs, $content ) {
    sfp_page_config_blokken_js_nodig( true );
    return sfp_page_config_blokken_css_vangnet( 'sfp/faq' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-faq' ) ) . '>' . $content . '</div>';
}

/**
 * sfp/faq-vraag. Registreert het paar voor het schema.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken (het antwoord).
 * @return string
 */
function sfp_page_config_render_faq_vraag( $attrs, $content ) {
    $vraag = wp_kses( sfp_page_config_blok_attr( $attrs, 'vraag' ), sfp_page_config_blok_titel_kses() );
    $tekst = trim( wp_strip_all_tags( $vraag ) );
    if ( '' === $tekst ) {
        return '';
    }
    // Alinea's worden regelovergangen; links en nadruk blijven staan.
    $antwoord = preg_replace( '#</p>\s*<p[^>]*>#', '<br><br>', (string) $content );
    $antwoord = trim(
        wp_kses(
            $antwoord,
            array(
                'br'     => array(),
                'ul'     => array(),
                'ol'     => array(),
                'li'     => array(),
                'a'      => array( 'href' => true ),
                'strong' => array(),
                'b'      => array(),
                'em'     => array(),
                'i'      => array(),
            )
        )
    );
    if ( '' !== $antwoord && ! is_admin() ) {
        sfp_page_config_faq_verzamel(
            array(
                'vraag'    => html_entity_decode( $tekst, ENT_QUOTES, 'UTF-8' ),
                'antwoord' => $antwoord
            )
        );
    }
    $extra = array(
        'class' => 'sfp-faq__vraag',
        'id'    => sanitize_title( $tekst ),
    );
    return '<details ' . get_block_wrapper_attributes( $extra ) . '><summary>' . $vraag . '</summary><div class="sfp-faq__antwoord">' . $content . '</div></details>';
}

add_action( 'wp_footer', 'sfp_page_config_faq_schema', 5 );

/**
 * Eén FAQPage-schema voor alle FAQ-blokken op de pagina.
 */
function sfp_page_config_faq_schema() {
    $paren = sfp_page_config_faq_verzamel();
    if ( ! $paren || ! is_singular() ) {
        return;
    }
    $vragen = array();
    $gezien = array();
    foreach ( $paren as $paar ) {
        if ( isset( $gezien[ $paar['vraag'] ] ) ) {
            continue;
        }
        $gezien[ $paar['vraag'] ] = true;
        $vragen[]                 = array(
            '@type'          => 'Question',
            'name'           => $paar['vraag'],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => $paar['antwoord'],
            ),
        );
    }
    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $vragen,
    );
    echo sfp_page_config_json_ld( $schema, 'sfp-faq-schema' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON met JSON_HEX_TAG.
}
