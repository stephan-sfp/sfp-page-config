<?php
/**
 * SFP Page Config - Fundament van de site (sinds 3.15.0)
 *
 * Drie onderdelen die tot en met 3.14.0 uit ASE-snippets kwamen. Elk staat
 * standaard uit en gaat per site aan na een nameting; daarna kan het snippet
 * uit (besluit Stephan 26-10-2026, fundament-19).
 *
 *   kleurrollen   --sfp-rol-* op :root, per rol een paletslot van Astra.
 *                 Vervangt het snippet "Kleuren: rollen van het palet".
 *                 De toewijzing staat in sfp_settings[kleurrollen]; bij het
 *                 aanzetten neemt de plugin hem eenmalig over van het snippet.
 *                 Er staat geen domeinnaam en geen hexcode in deze code.
 *   kopfont       --ux-display-font (H1) en --ux-heading-font (H2 tot H6)
 *                 uit de Astra-instellingen, in de head en zonder script.
 *                 Vervangt het snippet "Typografie: kopfont als variabele".
 *                 --sfp-kopfont en --sfp-kopfont-gewicht staan sinds 2.9.7
 *                 al in includes/kopfont.php en blijven daar.
 *   berichttypes  de berichttypes begrippen en tool, met hun menu-iconen.
 *                 Vervangt de snippets "Begrippen: berichttype", "Tools:
 *                 berichttype" (FL: Glossário en Ferramentas) en
 *                 "Berichttypes: eigen menu-iconen". Sleutels, adressen,
 *                 rest_base en labels neemt de plugin eenmalig over van de
 *                 registratie door het snippet, zodat er niets verschuift.
 *
 * Zolang een snippet nog aan staat, werkt het naast de plugin: de waarden
 * zijn gelijk, dus de site verandert niet.
 *
 * Terugdraaien: het onderdeel uitzetten in SFP Beheer, Onderdelen, en het
 * snippet weer aanzetten.
 *
 * @package SFP_Page_Config
 * @since   3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Kleurrollen
 * ====================================================================== */

/**
 * De rollen die een site kan voeren, in de volgorde van het beheer.
 *
 * @return array<string, string> rol => naam in het beheer.
 */
function sfp_page_config_kleurrollen_rollen() {
    return array(
        'primair'   => 'Primair',
        'secundair' => 'Secundair',
        'tertiair'  => 'Tertiair',
        'links'     => 'Links en knoppen',
        'hover'     => 'Hover',
        'tint-1'    => 'Tint 1',
        'tint-2'    => 'Tint 2',
        'tint-3'    => 'Tint 3',
        'wit'       => 'Wit',
        'body'      => 'Bodytekst',
        'pijl'      => 'Pijlen (vorige en volgende)',
    );
}

/**
 * Maak een toewijzing rol => slot schoon. Onbekende rollen en slots buiten
 * 0 tot en met 8 vallen weg; een lege waarde betekent dat de site die rol
 * niet voert.
 *
 * @param  mixed $ruw Ruwe toewijzing.
 * @return array<string, int>
 */
function sfp_page_config_kleurrollen_schoon( $ruw ) {
    $uit = array();
    if ( ! is_array( $ruw ) ) {
        return $uit;
    }
    foreach ( sfp_page_config_kleurrollen_rollen() as $rol => $naam ) {
        if ( ! isset( $ruw[ $rol ] ) || '' === $ruw[ $rol ] || null === $ruw[ $rol ] || ! is_numeric( $ruw[ $rol ] ) ) {
            continue;
        }
        $slot = (int) $ruw[ $rol ];
        if ( $slot >= 0 && $slot <= 8 ) {
            $uit[ $rol ] = $slot;
        }
    }
    return $uit;
}

/**
 * Heeft de site een eigen toewijzing bewaard?
 *
 * @return bool
 */
function sfp_page_config_kleurrollen_bewaard() {
    $alle = sfp_page_config_instellingen();
    return isset( $alle['kleurrollen'] ) && is_array( $alle['kleurrollen'] );
}

/**
 * De toewijzing rol => paletslot van deze site.
 *
 * Met het onderdeel aan: de bewaarde toewijzing. Is er nog niets bewaard en
 * staat het snippet er, dan neemt de plugin die toewijzing één keer over en
 * bewaart hem. Met het onderdeel uit: het snippet als dat er is, anders de
 * bewaarde toewijzing. Zo houden editor, beheer, deelbeeld en zelftest-pdf
 * dezelfde rollen als de voorkant, ook nadat het snippet uit gaat.
 *
 * @return array<string, int>
 */
function sfp_page_config_kleurrollen_slots() {
    $alle    = sfp_page_config_instellingen();
    $snippet = function_exists( 'sfp_kleurrollen_slots' ) ? sfp_page_config_kleurrollen_schoon( sfp_kleurrollen_slots() ) : null;

    if ( sfp_page_config_onderdeel_aan( 'kleurrollen' ) ) {
        if ( sfp_page_config_kleurrollen_bewaard() ) {
            return sfp_page_config_kleurrollen_schoon( $alle['kleurrollen'] );
        }
        if ( null !== $snippet && $snippet ) {
            $alle['kleurrollen'] = $snippet;
            update_option( 'sfp_settings', $alle );
            return $snippet;
        }
        return array();
    }

    if ( null !== $snippet ) {
        return $snippet;
    }
    return sfp_page_config_kleurrollen_bewaard() ? sfp_page_config_kleurrollen_schoon( $alle['kleurrollen'] ) : array();
}

/**
 * De variabelen --sfp-rol-* als CSS-declaraties, zonder :root eromheen.
 *
 * @param  array<string, int>|null $slots Toewijzing, of null voor die van de site.
 * @return string
 */
function sfp_page_config_kleurrollen_declaraties( $slots = null ) {
    $slots = null === $slots ? sfp_page_config_kleurrollen_slots() : $slots;
    $css   = '';
    foreach ( (array) $slots as $rol => $slot ) {
        $css .= '--sfp-rol-' . preg_replace( '/[^a-z0-9-]/', '', (string) $rol ) . ':var(--ast-global-color-' . (int) $slot . ');';
    }
    return $css;
}

add_action( 'wp_head', 'sfp_page_config_kleurrollen_head', 5 );

/**
 * De kleurrollen in de head, voor bezoekers. Zelfde id als het snippet,
 * zodat bestaande uitzonderingen in WP Rocket blijven gelden.
 */
function sfp_page_config_kleurrollen_head() {
    if ( is_admin() || ! sfp_page_config_onderdeel_aan( 'kleurrollen' ) ) {
        return;
    }
    $css = sfp_page_config_kleurrollen_declaraties();
    if ( '' === $css ) {
        return;
    }
    echo '<style id="sfp-kleurrollen">:root{' . $css . '}</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- alleen vaste namen en gehele getallen.
}

/**
 * Het Astra-palet van de site, negen slots.
 *
 * @return string[] Slot => kleur ('' als het slot leeg of ongeldig is).
 */
function sfp_page_config_kleurrollen_palet() {
    $astra = get_option( 'astra-settings', array() );
    $palet = is_array( $astra ) && isset( $astra['global-color-palette']['palette'] ) && is_array( $astra['global-color-palette']['palette'] ) ? array_values( $astra['global-color-palette']['palette'] ) : array();
    $uit   = array();
    for ( $i = 0; $i < 9; $i++ ) {
        $kleur   = isset( $palet[ $i ] ) && is_string( $palet[ $i ] ) ? $palet[ $i ] : '';
        $uit[ $i ] = function_exists( 'sfp_page_config_sanitize_css_color' ) ? sfp_page_config_sanitize_css_color( $kleur ) : '';
    }
    return $uit;
}

/**
 * De stand voor het beheer.
 *
 * @return array
 */
function sfp_page_config_kleurrollen_stand() {
    $slots = sfp_page_config_kleurrollen_slots();
    $rijen = array();
    foreach ( sfp_page_config_kleurrollen_rollen() as $rol => $naam ) {
        $rijen[] = array(
            'rol'  => $rol,
            'naam' => $naam,
            'slot' => isset( $slots[ $rol ] ) ? (int) $slots[ $rol ] : null,
        );
    }
    return array(
        'rollen'  => $rijen,
        'palet'   => sfp_page_config_kleurrollen_palet(),
        'bewaard' => sfp_page_config_kleurrollen_bewaard(),
        'snippet' => function_exists( 'sfp_kleurrollen_slots' ),
    );
}

/* =========================================================================
 * Kopfont als variabele
 * ====================================================================== */

/**
 * Schrijf een font-family zoals de browser hem teruggeeft: namen die een
 * gewoon woord zijn zonder aanhalingstekens, andere tussen dubbele
 * aanhalingstekens, gescheiden door komma en spatie. Zo is de waarde gelijk
 * aan wat het oude meetscript zette.
 *
 * @param  string $font Waarde uit sfp_page_config_sanitize_font_family().
 * @return string
 */
function sfp_page_config_kopfont_normaal( $font ) {
    $delen = array();
    foreach ( explode( ',', (string) $font ) as $deel ) {
        $naam = trim( trim( $deel ), "\"' " );
        if ( '' === $naam ) {
            continue;
        }
        if ( preg_match( '/^-?[a-zA-Z_][a-zA-Z0-9_-]*$/', $naam ) ) {
            $delen[] = $naam;
        } else {
            $delen[] = '"' . str_replace( '"', '', $naam ) . '"';
        }
    }
    return implode( ', ', $delen );
}

/**
 * De twee fontvariabelen uit Astra.
 *
 * H1 volgt in Astra de koppen als er geen eigen H1-font is gekozen; de koppen
 * volgen de body als er geen kopfont is gekozen.
 *
 * @return array{display: string, kop: string}
 */
function sfp_page_config_kopfont_ux() {
    $kop = sfp_page_config_sanitize_font_family( sfp_page_config_astra_option( 'headings-font-family' ) );
    if ( '' === $kop ) {
        $kop = sfp_page_config_sanitize_font_family( sfp_page_config_astra_option( 'body-font-family' ) );
    }
    $display = sfp_page_config_sanitize_font_family( sfp_page_config_astra_option( 'font-family-h1' ) );
    if ( '' === $display ) {
        $display = $kop;
    }
    return array(
        'display' => '' === $display ? '' : sfp_page_config_kopfont_normaal( $display ),
        'kop'     => '' === $kop ? '' : sfp_page_config_kopfont_normaal( $kop ),
    );
}

/**
 * De declaraties --ux-display-font en --ux-heading-font.
 *
 * @return string
 */
function sfp_page_config_kopfont_ux_declaraties() {
    $f   = sfp_page_config_kopfont_ux();
    $css = '';
    if ( '' !== $f['display'] ) {
        $css .= '--ux-display-font:' . wp_strip_all_tags( $f['display'] ) . ';';
    }
    if ( '' !== $f['kop'] ) {
        $css .= '--ux-heading-font:' . wp_strip_all_tags( $f['kop'] ) . ';';
    }
    return $css;
}

add_action( 'wp_head', 'sfp_page_config_kopfont_ux_head', 4 );

/**
 * De fontvariabelen in de head, voor bezoekers.
 */
function sfp_page_config_kopfont_ux_head() {
    if ( is_admin() || ! sfp_page_config_onderdeel_aan( 'kopfont' ) ) {
        return;
    }
    $css = sfp_page_config_kopfont_ux_declaraties();
    if ( '' === $css ) {
        return;
    }
    echo '<style id="sfp-kopfont-ux">:root{' . $css . '}</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- gevalideerd in sfp_page_config_sanitize_font_family().
}

add_filter( 'rocket_rucss_inline_atts_exclusions', 'sfp_page_config_fundament_rucss' );

/**
 * WP Rocket Remove Unused CSS laat de blokken met variabelen staan.
 *
 * @param  array $uitsluitingen Uitsluitingen op attribuut.
 * @return array
 */
function sfp_page_config_fundament_rucss( $uitsluitingen ) {
    $uitsluitingen   = (array) $uitsluitingen;
    $uitsluitingen[] = 'sfp-kleurrollen';
    $uitsluitingen[] = 'sfp-kopfont-ux';
    return $uitsluitingen;
}

/* =========================================================================
 * Berichttypes begrippen en tool
 * ====================================================================== */

/**
 * De twee berichttypes. Sleutels blijven zoals ze altijd waren.
 *
 * @return array<string, array{icoon: string}>
 */
function sfp_page_config_berichttypes() {
    return array(
        'begrippen' => array( 'icoon' => 'dashicons-book-alt' ),
        'tool'      => array( 'icoon' => 'dashicons-calculator' ),
    );
}

/**
 * De standaard als er nog niets is overgenomen: adres en labels in de taal
 * van de site. Gelijk aan wat de snippets op de sites in die taal zetten.
 *
 * @param  string $type begrippen of tool.
 * @return array{slug: string, with_front: bool, rest_base: string, labels: array}
 */
function sfp_page_config_berichttype_standaard( $type ) {
    $taal  = function_exists( 'sfp_page_config_taal' ) ? sfp_page_config_taal() : 'nl';
    $namen = function_exists( 'sfp_page_config_berichttype_namen' ) ? sfp_page_config_berichttype_namen( $type, $taal ) : array( 'meervoud' => $type, 'enkelvoud' => $type );
    $slugs = array(
        'begrippen' => array( 'nl' => 'begrippen', 'en' => 'glossary', 'pt' => 'glossario' ),
        'tool'      => array( 'nl' => 'tools', 'en' => 'tools', 'pt' => 'ferramentas' ),
    );
    return array(
        'slug'       => $slugs[ $type ][ $taal ] ?? $slugs[ $type ]['nl'],
        'with_front' => false,
        'rest_base'  => $type,
        'labels'     => array(
            'name'           => $namen['meervoud'],
            'singular_name'  => $namen['enkelvoud'],
            'menu_name'      => $namen['meervoud'],
            'name_admin_bar' => $namen['enkelvoud'],
            'all_items'      => $namen['meervoud'],
            'archives'       => $namen['meervoud'],
        ),
    );
}

/**
 * Maak een bewaarde registratie schoon.
 *
 * @param  mixed $ruw Ruwe waarde.
 * @return array|null
 */
function sfp_page_config_berichttype_schoon( $ruw ) {
    if ( ! is_array( $ruw ) || empty( $ruw['slug'] ) ) {
        return null;
    }
    $labels = array();
    foreach ( (array) ( $ruw['labels'] ?? array() ) as $sleutel => $waarde ) {
        if ( is_string( $sleutel ) && is_string( $waarde ) && preg_match( '/^[a-z_]+$/', $sleutel ) ) {
            $labels[ $sleutel ] = sanitize_text_field( $waarde );
        }
    }
    $rest = isset( $ruw['rest_base'] ) ? sanitize_key( (string) $ruw['rest_base'] ) : '';
    return array(
        'slug'       => sanitize_title( (string) $ruw['slug'] ),
        'with_front' => ! empty( $ruw['with_front'] ),
        'rest_base'  => '' === $rest ? '' : $rest,
        'labels'     => $labels,
    );
}

/**
 * Lees een registratie zoals hij nu op de site staat.
 *
 * @param  WP_Post_Type $obj Berichttype.
 * @return array
 */
function sfp_page_config_berichttype_uitlezen( $obj ) {
    $rewrite = is_array( $obj->rewrite ) ? $obj->rewrite : array();
    // Alleen de labels die de registratie zelf zette. De rest vult WordPress
    // in de taal van de gebruiker; die bevriezen we niet in de instelling.
    $standaard = function_exists( 'get_post_type_labels' ) ? (array) get_object_vars( get_post_type_labels( (object) array( 'labels' => array(), 'hierarchical' => false, 'name' => 'post' ) ) ) : array();
    $labels    = array();
    foreach ( (array) get_object_vars( (object) $obj->labels ) as $sleutel => $waarde ) {
        if ( is_string( $waarde ) && ( ! isset( $standaard[ $sleutel ] ) || $standaard[ $sleutel ] !== $waarde ) ) {
            $labels[ $sleutel ] = $waarde;
        }
    }
    return array(
        'slug'       => isset( $rewrite['slug'] ) ? (string) $rewrite['slug'] : (string) $obj->name,
        'with_front' => ! empty( $rewrite['with_front'] ),
        'rest_base'  => is_string( $obj->rest_base ) && '' !== $obj->rest_base ? $obj->rest_base : (string) $obj->name,
        'labels'     => $labels,
    );
}

/**
 * De bewaarde registratie van een berichttype, of null.
 *
 * @param  string $type Sleutel.
 * @return array|null
 */
function sfp_page_config_berichttype_bewaard( $type ) {
    $alle = sfp_page_config_instellingen();
    return isset( $alle['berichttypes'][ $type ] ) ? sfp_page_config_berichttype_schoon( $alle['berichttypes'][ $type ] ) : null;
}

/**
 * Moet de plugin dit berichttype leveren? Begrippen met het onderdeel
 * Berichttypes aan; tool ook met het onderdeel Tools aan, zoals sinds 3.8.0.
 *
 * @param  string $type Sleutel.
 * @return bool
 */
function sfp_page_config_berichttype_nodig( $type ) {
    if ( sfp_page_config_onderdeel_aan( 'berichttypes' ) ) {
        return true;
    }
    return 'tool' === $type && sfp_page_config_onderdeel_aan( 'tools' );
}

add_action( 'init', 'sfp_page_config_berichttypes_registreer', 11 );

/**
 * Registreer de berichttypes die nog niemand registreerde. Een snippet doet
 * dat op init met prioriteit 10; staat het er nog, dan neemt de plugin zijn
 * instellingen één keer over (met het onderdeel aan) en registreert zelf
 * niets. Staat het er niet meer, dan registreert de plugin met wat hij
 * overnam, of met de standaard in de taal van de site.
 */
function sfp_page_config_berichttypes_registreer() {
    $alle    = sfp_page_config_instellingen();
    $gewijzd = false;
    $eigen   = array();
    foreach ( sfp_page_config_berichttypes() as $type => $info ) {
        if ( ! sfp_page_config_berichttype_nodig( $type ) ) {
            continue;
        }
        if ( post_type_exists( $type ) ) {
            if ( sfp_page_config_onderdeel_aan( 'berichttypes' ) && null === sfp_page_config_berichttype_bewaard( $type ) ) {
                $alle['berichttypes'][ $type ] = sfp_page_config_berichttype_uitlezen( get_post_type_object( $type ) );
                $gewijzd                       = true;
            }
            continue;
        }
        $reg     = sfp_page_config_berichttype_bewaard( $type );
        $reg     = null === $reg ? sfp_page_config_berichttype_standaard( $type ) : $reg;
        $eigen[] = $type . ':' . $reg['slug'];
        register_post_type(
            $type,
            array(
                'labels'       => $reg['labels'],
                'public'       => true,
                'hierarchical' => false,
                'has_archive'  => true,
                'show_in_rest' => true,
                'rest_base'    => '' !== $reg['rest_base'] ? $reg['rest_base'] : $type,
                'menu_icon'    => $info['icoon'],
                'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'custom-fields', 'revisions' ),
                'rewrite'      => array(
                    'slug'       => $reg['slug'],
                    'with_front' => (bool) $reg['with_front'],
                ),
            )
        );
    }
    if ( $gewijzd ) {
        update_option( 'sfp_settings', $alle );
    }
    $GLOBALS['sfp_page_config_eigen_berichttypes'] = $eigen;
}

add_action( 'init', 'sfp_page_config_berichttypes_adressen', 99 );

/**
 * Registreert de plugin een berichttype zelf (of niet meer), dan bouwt
 * WordPress de adresregels één keer opnieuw op.
 */
function sfp_page_config_berichttypes_adressen() {
    $eigen = isset( $GLOBALS['sfp_page_config_eigen_berichttypes'] ) ? (array) $GLOBALS['sfp_page_config_eigen_berichttypes'] : array();
    sort( $eigen );
    $stand = $eigen ? implode( ',', $eigen ) : 'geen';
    if ( get_option( 'sfp_page_config_berichttypes_adressen', 'geen' ) !== $stand ) {
        flush_rewrite_rules( false );
        update_option( 'sfp_page_config_berichttypes_adressen', $stand, false );
    }
}

add_filter( 'register_post_type_args', 'sfp_page_config_berichttypes_iconen', 20, 2 );

/**
 * De menu-iconen, ook als een snippet het berichttype registreert.
 * Vervangt het snippet "Berichttypes: eigen menu-iconen".
 *
 * @param  array  $args Argumenten.
 * @param  string $type Berichttype.
 * @return array
 */
function sfp_page_config_berichttypes_iconen( $args, $type ) {
    $types = sfp_page_config_berichttypes();
    if ( isset( $types[ $type ] ) && function_exists( 'sfp_page_config_onderdeel_aan' ) && sfp_page_config_onderdeel_aan( 'berichttypes' ) ) {
        $args['menu_icon'] = $types[ $type ]['icoon'];
    }
    return $args;
}

/**
 * De stand voor het beheer.
 *
 * @return array
 */
function sfp_page_config_berichttypes_stand() {
    $uit = array();
    foreach ( array_keys( sfp_page_config_berichttypes() ) as $type ) {
        $obj   = get_post_type_object( $type );
        $reg   = sfp_page_config_berichttype_bewaard( $type );
        $eigen = isset( $GLOBALS['sfp_page_config_eigen_berichttypes'] ) && preg_grep( '/^' . preg_quote( $type, '/' ) . ':/', (array) $GLOBALS['sfp_page_config_eigen_berichttypes'] );
        $uit[] = array(
            'type'    => $type,
            'naam'    => $obj ? (string) $obj->labels->name : '',
            'adres'   => $obj ? (string) get_post_type_archive_link( $type ) : '',
            'bewaard' => null !== $reg,
            'bron'    => ! $obj ? 'geen' : ( $eigen ? 'plugin' : 'snippet' ),
        );
    }
    return $uit;
}
