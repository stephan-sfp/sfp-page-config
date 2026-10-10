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
    $tekst = array( 'type' => 'string', 'default' => '' );

    $alle = array(
        'sfp/sectie'        => array(
            'title'       => 'Sectie',
            'description' => 'Band over de volle breedte. Achtergrond wit of tint.',
            'icon'        => 'align-full-width',
            'css'         => 'basis',
            'attributes'  => array(
                'achtergrond' => array( 'type' => 'string', 'default' => 'wit', 'enum' => array( 'wit', 'tint' ) ),
                'anker'       => $tekst,
            ),
            'render'      => 'sfp_page_config_render_sectie',
        ),
        'sfp/held'          => array(
            'title'       => 'Held',
            'description' => 'Hero met H1, intro, beeld en knoppen.',
            'icon'        => 'cover-image',
            'css'         => 'held',
            'attributes'  => array(
                'variant' => array( 'type' => 'string', 'default' => 'recht', 'enum' => array( 'recht', 'breed' ) ),
                'anker'   => $tekst,
            ),
            'render'      => 'sfp_page_config_render_held',
        ),
        'sfp/kolommen'      => array(
            'title'       => 'Kolommen',
            'description' => 'Twee of drie kolommen, of een vaste verhouding. Onder 981 px één kolom.',
            'icon'        => 'columns',
            'css'         => 'kolommen',
            'attributes'  => array(
                'indeling' => array( 'type' => 'string', 'default' => 'twee', 'enum' => array( 'twee', 'drie', 'tekst-beeld', 'tekst-boek', 'boeking', 'breed-smal' ) ),
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
            'description' => 'Kaart met rand. Standaard met groene bovenrand, vlak of donker.',
            'icon'        => 'id-alt',
            'css'         => 'kaart',
            'attributes'  => array(
                'variant' => array( 'type' => 'string', 'default' => 'standaard', 'enum' => array( 'standaard', 'vlak', 'donker' ) ),
                'ruim'    => array( 'type' => 'boolean', 'default' => false ),
                'smal'    => array( 'type' => 'boolean', 'default' => false ),
                'link'    => $tekst,
            ),
            'render'      => 'sfp_page_config_render_kaart',
        ),
        'sfp/stappen'       => array(
            'title'       => 'Stappen',
            'description' => 'Genummerde stappen.',
            'icon'        => 'editor-ol',
            'css'         => 'stappen',
            'attributes'  => array(),
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
            'description' => 'Uitklapregels. Groot, compact (Read more), als kaart of als verhaal in een artikel.',
            'icon'        => 'arrow-down-alt2',
            'css'         => 'uitklap',
            'attributes'  => array(
                'variant'     => array( 'type' => 'string', 'default' => 'groot', 'enum' => array( 'groot', 'compact', 'kaart', 'verhaal' ) ),
                'eenTegelijk' => array( 'type' => 'boolean', 'default' => false ),
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
            'description' => 'Tabbladen. Zonder JavaScript staan alle panelen onder elkaar.',
            'icon'        => 'index-card',
            'css'         => 'tabs',
            'attributes'  => array(
                'label' => $tekst,
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
                'titel' => $tekst,
            ),
            'render'      => 'sfp_page_config_render_tab',
        ),
        'sfp/lijst'         => array(
            'title'       => 'Lijst',
            'description' => 'Lijst in stijl vink, kruis, nummers, regels of letters.',
            'icon'        => 'editor-ul',
            'css'         => 'lijst',
            'attributes'  => array(
                'stijl' => array( 'type' => 'string', 'default' => 'vink', 'enum' => array( 'regels', 'letters', 'vink', 'kruis', 'nummers' ) ),
            ),
            'render'      => 'sfp_page_config_render_lijst',
        ),
        'sfp/faq'           => array(
            'title'       => 'FAQ',
            'description' => 'Vragen en antwoorden met FAQPage-schema.',
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
            'sfp-intro'       => 'Intro',
            'sfp-groot'       => 'Groot',
            'sfp-pijl'        => 'Pijllink',
            'sfp-bovenschrift' => 'Bovenschrift',
            'sfp-getal'       => 'Getal (pakket)',
            'sfp-prijs'       => 'Prijs',
            'sfp-wie'         => 'Naam met portret',
            'sfp-mailregel'   => 'Mailregel',
            'sfp-klein'       => 'Klein',
            'sfp-lead'        => 'Lead (eerste alinea van een artikel)',
        ),
        'core/table'     => array(
            'sfp-tabel' => 'Huisstijl',
        ),
        'core/heading'   => array(
            'sfp-paginatitel' => 'Paginatitel over de volle breedte',
        ),
        'core/image'     => array(
            'sfp-afgerond' => 'Afgerond',
            'sfp-omslag'   => 'Boekomslag',
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
        SFP_PAGE_CONFIG_URL . 'assets/blokken/editor.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-notices' ),
        SFP_PAGE_CONFIG_VERSION,
        true
    );

    // De vaste labels van de artikelblokken, in de taal van de site.
    if ( function_exists( 'sfp_page_config_artikel_tekst' ) ) {
        $labels = array();
        foreach ( array( 'samenvatting', 'inzicht', 'lees_ook', 'kader_wist-je-dat', 'kader_kanttekening', 'kader_grondslag', 'kader_reflectievraag', 'kader_checklist' ) as $sleutel ) {
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

    foreach ( sfp_page_config_blokken() as $naam => $def ) {
        $args = array(
            'api_version'     => 3,
            'title'           => $def['title'],
            'description'     => $def['description'],
            'category'        => 'design',
            'icon'            => $def['icon'],
            'attributes'      => $def['attributes'],
            'supports'        => array(
                'html'            => false,
                'className'       => true,
                'customClassName' => true,
            ),
            'editor_script'   => 'sfp-blokken-editor',
            'render_callback' => $def['render'],
        );
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
                register_block_style( $blok, array( 'name' => $naam, 'label' => $label ) );
            }
        }
    }
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
    static $cache = array();
    if ( isset( $cache[ $naam ] ) ) {
        return $cache[ $naam ];
    }
    $naam = preg_replace( '/[^a-z-]/', '', (string) $naam );
    $pad  = SFP_PAGE_CONFIG_DIR . 'assets/blokken/' . $naam . '.css';
    $css  = is_readable( $pad ) ? (string) file_get_contents( $pad ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokaal pluginbestand.
    // Commentaar en witruimte eruit; de bestanden blijven leesbaar in de repo.
    $css = preg_replace( '#/\*.*?\*/#s', '', $css );
    $css = preg_replace( '/\s+/', ' ', $css );
    $css = str_replace( array( ' {', '{ ', ' }', '; ', ': ', ', ' ), array( '{', '{', '}', ';', ':', ',' ), $css );
    $cache[ $naam ] = trim( $css );
    return $cache[ $naam ];
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

    return ':root{'
        . '--sfp-b-primair:var(--sfp-rol-primair,' . $primair . ');'
        . '--sfp-b-secundair:var(--sfp-rol-secundair,var(--sfp-b-primair));'
        . '--sfp-b-links:var(--sfp-rol-links,' . $links . ');'
        . '--sfp-b-hover:var(--sfp-rol-hover,' . $hover . ');'
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
        . '--sfp-b-r-knop:6px;--sfp-b-r-kaart:16px}';
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

add_action( 'enqueue_block_assets', 'sfp_page_config_blokken_editor_css' );

/**
 * Dezelfde CSS in de blokeditor, zodat de blokken er daar ook zo uitzien.
 */
function sfp_page_config_blokken_editor_css() {
    if ( ! is_admin() ) {
        return;
    }
    $css = sfp_page_config_blokken_css_variabelen();
    foreach ( array( 'basis', 'held', 'kolommen', 'kaart', 'stappen', 'uitklap', 'tabs', 'lijst', 'faq', 'tabel', 'deelkaart', 'samenvatting', 'kader', 'citaat', 'inzicht', 'lees-ook', 'vervolg', 'editor' ) as $bestand ) {
        $css .= sfp_page_config_blokken_css_bestand( $bestand );
    }
    wp_register_style( 'sfp-blokken-editor', false, array(), SFP_PAGE_CONFIG_VERSION );
    wp_enqueue_style( 'sfp-blokken-editor' );
    wp_add_inline_style( 'sfp-blokken-editor', $css );
}

add_filter( 'rocket_rucss_inline_atts_exclusions', 'sfp_page_config_blokken_rucss' );

/**
 * WP Rocket Remove Unused CSS laat de CSS van de blokken staan. Zonder
 * dit verdwijnen onder meer de regels voor geopende uitklapregels en
 * verborgen tabpanelen, omdat die bij het meten niet in beeld zijn.
 *
 * @param  array $uitsluitingen Uitsluitingen op attribuut.
 * @return array
 */
function sfp_page_config_blokken_rucss( $uitsluitingen ) {
    $uitsluitingen   = (array) $uitsluitingen;
    $uitsluitingen[] = 'sfp-blokken';
    return $uitsluitingen;
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

add_filter( 'rocket_delay_js_exclusions', 'sfp_page_config_blokken_delay' );

/**
 * WP Rocket stelt het script niet uit; anders werken tabs pas na de
 * eerste aanraking.
 *
 * @param  array $uitsluitingen Uitsluitingen.
 * @return array
 */
function sfp_page_config_blokken_delay( $uitsluitingen ) {
    $uitsluitingen   = (array) $uitsluitingen;
    $uitsluitingen[] = 'sfp-blokken-js';
    return $uitsluitingen;
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
function sfp_page_config_render_held( $attrs, $content ) {
    $variant = sfp_page_config_blok_kies( sfp_page_config_blok_attr( $attrs, 'variant', 'recht' ), array( 'recht', 'breed' ) );
    $klassen = array( 'sfp-held', 'sfp-held--recht', 'breed' === $variant ? 'sfp-held--breed' : '' );
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
function sfp_page_config_render_kolommen( $attrs, $content ) {
    $indeling = sfp_page_config_blok_kies( sfp_page_config_blok_attr( $attrs, 'indeling', 'twee' ), array( 'twee', 'drie', 'tekst-beeld', 'tekst-boek', 'boeking', 'breed-smal' ) );
    return sfp_page_config_blokken_css_vangnet( 'sfp/kolommen' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-kolommen', 'sfp-kolommen--' . $indeling ) ) . '>' . $content . '</div>';
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
function sfp_page_config_render_kaart( $attrs, $content ) {
    $variant = sfp_page_config_blok_kies( sfp_page_config_blok_attr( $attrs, 'variant', 'standaard' ), array( 'standaard', 'vlak', 'donker' ) );
    $link    = esc_url( sfp_page_config_blok_attr( $attrs, 'link' ) );
    $klassen = array(
        'sfp-kaart',
        'standaard' !== $variant ? 'sfp-kaart--' . $variant : '',
        ! empty( $attrs['ruim'] ) ? 'sfp-kaart--ruim' : '',
        ! empty( $attrs['smal'] ) ? 'sfp-kaart--smal' : '',
        '' !== $link ? 'sfp-kaart--link' : '',
    );
    $laag = '' !== $link ? '<a class="sfp-kaart__link" href="' . $link . '" tabindex="-1" aria-hidden="true"></a>' : '';
    return sfp_page_config_blokken_css_vangnet( 'sfp/kaart' )
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
    $variant = sfp_page_config_blok_kies( sfp_page_config_blok_attr( $attrs, 'variant', 'groot' ), array( 'groot', 'compact', 'kaart', 'verhaal' ) );
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
    $label = wp_kses( sfp_page_config_blok_attr( $attrs, 'label' ), sfp_page_config_blok_titel_kses() );
    if ( '' !== $label ) {
        $kop = '<span class="sfp-uitklap__titel"><small class="sfp-blok-label">' . $label . '</small><b>' . $titel . '</b></span>';
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
    $knoppen = '';
    $panelen = '';
    $nr      = 0;
    foreach ( $block->inner_blocks as $tab ) {
        if ( 'sfp/tab' !== $tab->name ) {
            continue;
        }
        $id       = wp_unique_id( 'sfp-tab-' );
        $titel    = wp_kses( sfp_page_config_blok_attr( $tab->attributes, 'titel' ), sfp_page_config_blok_titel_kses() );
        $eerste   = 0 === $nr;
        $knoppen .= '<button type="button" role="tab" id="' . esc_attr( $id ) . '-k" aria-controls="' . esc_attr( $id ) . '" aria-selected="' . ( $eerste ? 'true' : 'false' ) . '"' . ( $eerste ? '' : ' tabindex="-1"' ) . '>' . $titel . '</button>';
        $panelen .= '<div class="sfp-tabpaneel" role="tabpanel" id="' . esc_attr( $id ) . '" aria-labelledby="' . esc_attr( $id ) . '-k"' . ( $eerste ? '' : ' hidden' ) . '>' . $tab->render() . '</div>';
        ++$nr;
    }
    $lijst = '<div class="sfp-tablijst" role="tablist"' . ( '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : '' ) . '>' . $knoppen . '</div>';
    return sfp_page_config_blokken_css_vangnet( 'sfp/tabs' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-tabs' ) ) . '>' . $lijst . $panelen . '</div>';
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
    return sfp_page_config_blokken_css_vangnet( 'sfp/lijst' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-lijst', 'sfp-lijst--' . $stijl ) ) . '>' . $content . '</div>';
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
    $antwoord = trim( wp_kses(
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
    ) );
    if ( '' !== $antwoord && ! is_admin() ) {
        sfp_page_config_faq_verzamel( array( 'vraag' => html_entity_decode( $tekst, ENT_QUOTES, 'UTF-8' ), 'antwoord' => $antwoord ) );
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
        $vragen[] = array(
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
    echo '<script type="application/ld+json" id="sfp-faq-schema">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON uit wp_json_encode met JSON_HEX_TAG.
}
