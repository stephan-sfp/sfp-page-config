<?php
/**
 * SFP Page Config - SFP Beheer
 *
 * Het beheer van de plugin, volgens de goedgekeurde dummy "Page Config
 * beheerdummy" (concept 4, 10 oktober 2026) en de 24 ontwerpregels. Eén menu,
 * SFP Beheer, met de schermen Overzicht, Pagina's, Onderdelen, Teksten en
 * Onderhoud, en Cursusdata op sites met trainingen. Vervangt de vier
 * tabbladen van voor 3.0.0, het submenu Autoloaded Options en de losse
 * instellingenpagina's van SFP Tooltip en SFP Google Reviews.
 *
 * Het scherm zelf is één pagina die in de browser wordt opgebouwd
 * (assets/beheer/beheer.js, zonder bouwstap) en met de server praat via de
 * routes onder /sfp/v1/beheer. Alle routes zijn alleen voor beheerders.
 *
 * De adresnaam van het menu blijft `sfp-page-config`, net als de map.
 *
 * @package SFP_Page_Config
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Menu
 * ====================================================================== */

// Alleen als een oud ASE-snippet het menu niet al aanmaakt.
if ( ! has_action( 'admin_menu', 'sfp_cursusdata_menu' ) ) {
    add_action( 'admin_menu', 'sfp_page_config_beheer_menu' );
}

/**
 * Het menu SFP Beheer.
 */
function sfp_page_config_beheer_menu() {
    add_menu_page(
        'SFP Beheer',
        'SFP Beheer',
        'manage_options',
        'sfp-page-config',
        'sfp_page_config_beheer_scherm',
        'dashicons-admin-generic',
        30
    );
}

// Pas na de toegangscontrole van WordPress: wie het item eerder uit het menu
// haalt, maakt het scherm zelf onbereikbaar.
add_action( 'admin_head', 'sfp_page_config_beheer_menu_opruimen' );

/**
 * Het scherm met resten van oude plugins blijft bestaan, maar staat niet meer
 * als los menu-item onder SFP Beheer. Je bereikt het via Onderhoud.
 */
function sfp_page_config_beheer_menu_opruimen() {
    remove_submenu_page( 'sfp-page-config', 'sfp-autoloaded-options' );
    // Met nog één item eronder toont WordPress dat item dubbel onder het menu.
    remove_submenu_page( 'sfp-page-config', 'sfp-page-config' );
}

/**
 * De pagina waarin het beheer wordt opgebouwd.
 */
function sfp_page_config_beheer_scherm() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Geen toegang.' );
    }
    echo '<div class="wrap sfpb-wrap"><h1 class="screen-reader-text">SFP Beheer</h1><div id="sfp-beheer" class="sfpb"><p class="sfpb-laden">SFP Beheer wordt geladen.</p></div><noscript><p>SFP Beheer heeft JavaScript nodig.</p></noscript></div>';
}

/* =========================================================================
 * Bestanden
 * ====================================================================== */

add_action( 'admin_enqueue_scripts', 'sfp_page_config_beheer_bestanden' );

/**
 * Laad het beheer, alleen op de eigen pagina.
 *
 * @param string $hook_suffix Huidige beheerpagina.
 */
function sfp_page_config_beheer_bestanden( $hook_suffix ) {
    if ( 'toplevel_page_sfp-page-config' !== $hook_suffix ) {
        return;
    }

    wp_enqueue_media();

    $fonts = sfp_page_config_beheer_fonts_url();
    if ( '' !== $fonts ) {
        wp_enqueue_style( 'sfp-beheer-merkfonts', $fonts, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- adres van het lettertype.
    }

    wp_enqueue_style( 'sfp-beheer', SFP_PAGE_CONFIG_URL . 'assets/beheer/beheer.css', array(), SFP_PAGE_CONFIG_VERSION );
    wp_add_inline_style( 'sfp-beheer', sfp_page_config_beheer_merk_css() );

    wp_enqueue_script( 'sfp-beheer', SFP_PAGE_CONFIG_URL . 'assets/beheer/beheer.js', array( 'wp-api-fetch' ), SFP_PAGE_CONFIG_VERSION, true );
    wp_add_inline_script( 'sfp-beheer', 'window.sfpBeheer=' . wp_json_encode( sfp_page_config_beheer_stand() ) . ';', 'before' );
}

/**
 * De kleuren en lettertypes van het merk van de site, voor de voorbeelden in
 * het beheer. Alles binnen .sfpb-m draagt het merk; de rest van het beheer
 * is neutraal. De waarden komen uit Astra en uit het snippet met de
 * kleurrollen; er staat geen merkkleur en geen fontnaam in deze code.
 *
 * @return string
 */
function sfp_page_config_beheer_merk_css() {
    $vars  = '';
    $astra = get_option( 'astra-settings', array() );
    $palet = is_array( $astra ) && isset( $astra['global-color-palette']['palette'] ) && is_array( $astra['global-color-palette']['palette'] ) ? array_values( $astra['global-color-palette']['palette'] ) : array();
    foreach ( $palet as $i => $kleur ) {
        $kleur = sfp_page_config_sanitize_css_color( is_string( $kleur ) ? $kleur : '' );
        if ( '' !== $kleur && $i < 9 ) {
            $vars .= '--ast-global-color-' . (int) $i . ':' . $kleur . ';';
        }
    }
    if ( function_exists( 'sfp_kleurrollen_slots' ) ) {
        foreach ( (array) sfp_kleurrollen_slots() as $rol => $slot ) {
            $vars .= '--sfp-rol-' . preg_replace( '/[^a-z0-9-]/', '', (string) $rol ) . ':var(--ast-global-color-' . (int) $slot . ');';
        }
    }
    $brand = sfp_page_config_get_brand();
    $vars .= '--sfpb-m-knop:' . $brand['cta_bg'] . ';--sfpb-m-knop-hover:' . $brand['cta_hover'] . ';--sfpb-m-knoptekst:' . $brand['cta_text'] . ';'
        . '--sfpb-m-kopfont:' . $brand['font'] . ';--sfpb-m-kopgewicht:' . $brand['weight'] . ';--sfpb-m-tekstfont:' . $brand['body_font'] . ';'
        . '--sfp-kopfont:' . $brand['font'] . ';--sfp-kopfont-gewicht:' . $brand['weight'] . ';';

    // De variabelen van de bouwstenen (--sfp-b-*), hier alleen binnen een voorbeeld.
    $blokken = function_exists( 'sfp_page_config_blokken_css_variabelen' ) ? sfp_page_config_blokken_css_variabelen() : '';
    $blokken = rtrim( (string) preg_replace( '/^:root\{(.*)\}$/s', '$1', $blokken ), ';' );
    $blokken = '' === $blokken ? '' : $blokken . ';';

    return '.sfpb-m{' . $vars . $blokken . sfp_page_config_tooltips_css_variabelen() . '}';
}

/**
 * Het adres van de lettertypes van het merk, voor de voorbeelden. Leeg als de
 * site systeemlettertypes gebruikt.
 *
 * @return string
 */
function sfp_page_config_beheer_fonts_url() {
    $brand    = sfp_page_config_get_brand();
    $families = array();
    $eerste   = function ( $stack ) {
        $delen = explode( ',', (string) $stack );
        $naam  = trim( $delen[0], " \t\"'" );
        $vast  = array( 'inherit', 'serif', 'sans-serif', 'monospace', 'system-ui', 'arial', 'helvetica', 'georgia', 'verdana', 'tahoma', 'times new roman', 'courier new', '-apple-system' );
        return '' === $naam || in_array( strtolower( $naam ), $vast, true ) ? '' : $naam;
    };
    $kop = $eerste( $brand['font'] );
    if ( '' !== $kop ) {
        $gewicht          = preg_match( '/^[1-9]00$/', (string) $brand['weight'] ) ? (string) $brand['weight'] : '700';
        $families[ $kop ] = array( $gewicht => true );
    }
    $tekst = $eerste( $brand['body_font'] );
    if ( '' !== $tekst ) {
        $families[ $tekst ] = ( isset( $families[ $tekst ] ) ? $families[ $tekst ] : array() ) + array(
            '400' => true,
            '700' => true,
        );
    }
    if ( ! $families ) {
        return '';
    }
    $delen = array();
    foreach ( $families as $naam => $gewichten ) {
        $g = array_keys( $gewichten );
        sort( $g );
        $delen[] = 'family=' . str_replace( ' ', '+', $naam ) . ':wght@' . implode( ';', $g );
    }
    return 'https://fonts.googleapis.com/css2?' . implode( '&', $delen ) . '&display=swap';
}

/* =========================================================================
 * Stand: alles wat het beheer nodig heeft
 * ====================================================================== */

/**
 * De gepubliceerde pagina's van de site, om uit te kiezen.
 *
 * @return array<int, array{id: int, titel: string}>
 */
function sfp_page_config_beheer_paginakeuze() {
    $ids = get_posts(
        array(
            'post_type'        => 'page',
            'post_status'      => 'publish',
            'posts_per_page'   => 300,
            'orderby'          => 'title',
            'order'            => 'ASC',
            'fields'           => 'ids',
            'suppress_filters' => true,
        )
    );
    $uit = array();
    foreach ( $ids as $id ) {
        $uit[] = array(
            'id'    => (int) $id,
            'titel' => html_entity_decode( get_the_title( $id ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
        );
    }
    return $uit;
}

/**
 * De volledige stand voor het beheer.
 *
 * @return array
 */
function sfp_page_config_beheer_stand() {
    $alle = sfp_page_config_instellingen();

    // Onderdelen.
    $onderdelen = array();
    foreach ( sfp_page_config_onderdelen() as $id => $onderdeel ) {
        $rij = array(
            'id'     => $id,
            'naam'   => $onderdeel['naam'],
            'uitleg' => $onderdeel['uitleg'],
            'detail' => $onderdeel['detail'],
            'aan'    => sfp_page_config_onderdeel_aan( $id ),
            'oud'    => '',
        );
        if ( 'tooltips' === $id && sfp_page_config_tooltips_oude_plugin() ) {
            $rij['oud'] = 'SFP Tooltip';
        }
        if ( 'reviews' === $id && sfp_page_config_reviews_oude_plugin() ) {
            $rij['oud'] = 'SFP Google Reviews';
        }
        $onderdelen[] = $rij;
    }

    // Knop onderaan.
    $knop = array();
    foreach ( sfp_page_config_paginasoorten() as $type ) {
        $eigen         = isset( $alle['sticky_cta'][ $type ] ) && is_array( $alle['sticky_cta'][ $type ] ) ? $alle['sticky_cta'][ $type ] : array();
        $effectief     = sfp_page_config_get_sticky_cta( $type );
        $knop[ $type ] = array(
            'text'   => isset( $eigen['text'] ) ? (string) $eigen['text'] : '',
            'norm'   => sfp_page_config_tekst_standaard( 'knop_' . $type ),
            'doel'   => $effectief ? $effectief['doel'] : 'form',
            'pagina' => isset( $eigen['pagina'] ) ? (int) $eigen['pagina'] : 0,
            'link'   => $effectief && 'link' === $effectief['doel'] ? $effectief['href'] : ( isset( $eigen['link'] ) ? (string) $eigen['link'] : '' ),
        );
    }

    // Teksten, per groep.
    $groepen = array();
    foreach ( sfp_page_config_teksten_register() as $id => $tekst ) {
        $groepen[ $tekst['groep'] ][] = array(
            'id'        => $id,
            'label'     => $tekst['label'],
            'standaard' => sfp_page_config_tekst_standaard( $id ),
            'waarde'    => 'whatsapp_bericht' === $id && '' === sfp_page_config_tekst_eigen( $id ) ? (string) sfp_page_config_get_setting( 'whatsapp_message', '' ) : sfp_page_config_tekst_eigen( $id ),
            'lang'      => ! empty( $tekst['lang'] ),
        );
    }
    $teksten = array();
    foreach ( $groepen as $naam => $items ) {
        $teksten[] = array(
            'groep' => $naam,
            'items' => $items,
        );
    }

    // Artikelen.
    $beeld_id = isset( $alle['artikel_promo_beeld'] ) ? (int) $alle['artikel_promo_beeld'] : 0;
    $beeld    = $beeld_id ? wp_get_attachment_image_url( $beeld_id, 'medium' ) : '';
    $artikel  = array(
        'voorkeursbron' => isset( $alle['artikel_voorkeursbron'] ) && '1' === (string) $alle['artikel_voorkeursbron'],
        'links'         => array(),
        'promo'         => array(
            'beeld'     => $beeld_id,
            'beeld_url' => $beeld ? (string) $beeld : '',
            'label'     => isset( $alle['artikel_promo_label'] ) ? (string) $alle['artikel_promo_label'] : '',
            'titel'     => isset( $alle['artikel_promo_titel'] ) ? (string) $alle['artikel_promo_titel'] : '',
            'tekst'     => isset( $alle['artikel_promo_tekst'] ) ? (string) $alle['artikel_promo_tekst'] : '',
            'linktekst' => isset( $alle['artikel_promo_linktekst'] ) ? (string) $alle['artikel_promo_linktekst'] : '',
            'pagina'    => isset( $alle['artikel_promo_pagina'] ) ? (int) $alle['artikel_promo_pagina'] : 0,
            'url'       => isset( $alle['artikel_promo_url'] ) ? (string) $alle['artikel_promo_url'] : '',
            'vorm'      => isset( $alle['artikel_promo_vorm'] ) ? (string) $alle['artikel_promo_vorm'] : '',
        ),
        'promo2'        => sfp_page_config_beheer_promo( $alle, 'artikel_promo2' ),
        'promoVorm'     => array(
            'artikel' => isset( $alle['promo_vorm_artikel'] ) && '' !== $alle['promo_vorm_artikel'] ? (string) $alle['promo_vorm_artikel'] : 'site',
            'pijler'  => isset( $alle['promo_vorm_pijler'] ) && '' !== $alle['promo_vorm_pijler'] ? (string) $alle['promo_vorm_pijler'] : 'site',
            'begrip'  => isset( $alle['promo_vorm_begrip'] ) && '' !== $alle['promo_vorm_begrip'] ? (string) $alle['promo_vorm_begrip'] : 'site',
            'tool'    => isset( $alle['promo_vorm_tool'] ) && '' !== $alle['promo_vorm_tool'] ? (string) $alle['promo_vorm_tool'] : 'geen',
        ),
        'leestekens'    => isset( $alle['kop_leestekens'] ) && '1' === (string) $alle['kop_leestekens'],
        'patronen'      => array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', isset( $alle['affiliate_patronen'] ) ? (string) $alle['affiliate_patronen'] : '' ) ), 'strlen' ) ),
        'treffers'      => function_exists( 'sfp_page_config_affiliate_treffers' ) ? (object) sfp_page_config_affiliate_treffers() : (object) array(),
        'affiliate'     => function_exists( 'sfp_page_config_affiliate_teksten' ) ? sfp_page_config_affiliate_teksten() : array(
            'regel'  => '',
            'uitleg' => '',
        ),
        'voorbeeld'     => sfp_page_config_beheer_artikel_voorbeeld(),
    );
    foreach ( array( 'factcheck', 'redactie', 'bibliografie' ) as $link ) {
        $artikel['links'][ $link ] = array(
            'pagina' => isset( $alle[ 'artikel_' . $link . '_pagina' ] ) ? (int) $alle[ 'artikel_' . $link . '_pagina' ] : 0,
            'url'    => isset( $alle[ 'artikel_' . $link . '_url' ] ) ? (string) $alle[ 'artikel_' . $link . '_url' ] : '',
        );
    }

    // Reviews.
    $opts    = sfp_page_config_reviews_opties();
    $reviews = array(
        'oud'    => sfp_page_config_reviews_oude_plugin(),
        'opties' => array(
            'layout'    => (string) $opts['layout'],
            'min_stars' => (int) $opts['min_stars'],
            'summary'   => (bool) filter_var( $opts['summary'], FILTER_VALIDATE_BOOLEAN ),
            'place_id'  => (string) $opts['place_id'],
            'sleutel'   => '' !== (string) $opts['api_key'],
        ),
        'stand'  => sfp_page_config_reviews_stand(),
        'eigen'  => class_exists( 'SFP_Reviews_Manual' ) ? SFP_Reviews_Manual::get_all() : array(),
        'voorbeeld' => sfp_page_config_beheer_reviews_voorbeeld(),
    );

    // Footerlinks.
    $footer_bewaard = isset( $alle['footer']['groepen'] ) && is_array( $alle['footer']['groepen'] ) ? $alle['footer']['groepen'] : array();
    $footer         = array(
        'groepen' => $footer_bewaard,
        'widget'  => ! $footer_bewaard && null !== sfp_page_config_footer_uit_widget(),
        'vlak'    => is_active_sidebar( 'footer-widget-4' ),
    );

    // Onderhoud.
    $totaal    = function_exists( 'sfp_ao_fetch_totals' ) ? sfp_ao_fetch_totals() : null;
    $skip      = isset( $alle['ajax_lean_skip'] ) && is_array( $alle['ajax_lean_skip'] ) ? $alle['ajax_lean_skip'] : ( function_exists( 'sfp_ajax_lean_default_skip' ) ? sfp_ajax_lean_default_skip() : array() );
    $plugins   = array();
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $beschermd = function_exists( 'sfp_ajax_lean_protected_folders' ) ? sfp_ajax_lean_protected_folders() : array();
    foreach ( get_plugins() as $bestand => $gegevens ) {
        $map = dirname( $bestand );
        if ( '.' === $map || in_array( $map, $beschermd, true ) || ! is_plugin_active( $bestand ) ) {
            continue;
        }
        $plugins[] = array(
            'map'  => $map,
            'naam' => html_entity_decode( (string) $gegevens['Name'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
            'over' => in_array( $map, $skip, true ),
        );
    }
    $onderhoud = array(
        'kanaal'      => isset( $alle['update_kanaal'] ) && 'proef' === $alle['update_kanaal'] ? 'proef' : '',
        'autoload_kb' => $totaal ? (int) round( $totaal->total_bytes / 1024 ) : 0,
        'autoload_url' => function_exists( 'sfp_ao_page_url' ) ? sfp_ao_page_url() : '',
        'lean_aan'    => isset( $alle['ajax_lean_enabled'] ) && '1' === (string) $alle['ajax_lean_enabled'],
        'lean_fout'   => (string) get_option( 'sfp_ajax_lean_error', '' ),
        'plugins'     => $plugins,
        'deelbeeld'   => function_exists( 'sfp_page_config_deelbeeld_stand' ) ? sfp_page_config_deelbeeld_stand() : null,
        'cron_email'  => isset( $alle['cron_email'] ) ? (string) $alle['cron_email'] : '',
        'beheer_email' => (string) get_option( 'admin_email' ),
    );

    return array(
        'versie'       => SFP_PAGE_CONFIG_VERSION,
        'site'         => array(
            'host' => preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ),
            'taal' => sfp_page_config_taal(),
            'home' => home_url( '/' ),
        ),
        'onderdelen'   => $onderdelen,
        'zelftest'     => function_exists( 'sfp_zelftest_stand' ) ? sfp_zelftest_stand() : null,
        'afwijkingen'  => sfp_page_config_afwijkingen(),
        'knop'         => $knop,
        'teksten'      => $teksten,
        'paginakeuze'  => sfp_page_config_beheer_paginakeuze(),
        'soorten'      => sfp_page_config_soorten(),
        'artikel'      => $artikel,
        'vervolg'      => function_exists( 'sfp_page_config_vervolg_overzicht' ) ? sfp_page_config_vervolg_overzicht() : array(),
        'vervolg_css'  => sfp_page_config_beheer_blok_css( array( 'sfp/vervolg' ) ),
        'vervolg_algemeen' => function_exists( 'sfp_page_config_vervolg_astra_layouts' ) ? array_values( array_column( array_filter( sfp_page_config_vervolg_astra_layouts(), function ( $layout ) {
            return ! empty( $layout['algemeen'] );
        } ), 'titel' ) ) : array(),
        'reviews'      => $reviews,
        'tooltips'     => array(
            'oud' => sfp_page_config_tooltips_oude_plugin(),
        ),
        'whatsapp'     => array(
            'nummer'    => isset( $alle['whatsapp_number'] ) ? (string) $alle['whatsapp_number'] : '',
            'norm'      => sfp_page_config_whatsapp_norm(),
            'bericht'   => sfp_page_config_tekst_eigen( 'whatsapp_bericht' ) ? sfp_page_config_tekst_eigen( 'whatsapp_bericht' ) : (string) sfp_page_config_get_setting( 'whatsapp_message', '' ),
            'standaard' => sfp_page_config_tekst_standaard( 'whatsapp_bericht' ),
        ),
        'footer'       => $footer,
        'onderhoud'    => $onderhoud,
        'cursusdata'   => array(
            'paginas' => sfp_page_config_onderdeel_aan( 'cursusdata' ) ? sfp_page_config_cursusdata_paginas() : array(),
            'start'   => isset( $alle['cursus_tijd_default_start'] ) ? (string) $alle['cursus_tijd_default_start'] : '',
            'eind'    => isset( $alle['cursus_tijd_default_eind'] ) ? (string) $alle['cursus_tijd_default_eind'] : '',
        ),
        'controle'     => sfp_page_config_paginas_overzicht(),
    );
}

/**
 * Gegevens van het nieuwste artikel, voor het voorbeeld van de kopkaart. Zonder
 * artikel: lege waarden, het beheer toont dan een schets.
 *
 * @return array{pijler: string, titel: string, auteur: string, meta: string, gecheckt: string, redactie: string}
 */
function sfp_page_config_beheer_artikel_voorbeeld() {
    $t   = function ( $sleutel ) {
        return function_exists( 'sfp_page_config_artikel_tekst' ) ? sfp_page_config_artikel_tekst( $sleutel ) : '';
    };
    $uit = array(
        'pijler'   => '',
        'titel'    => '',
        'auteur'   => '',
        'meta'     => '',
        'gecheckt' => $t( 'gecheckt' ),
        'redactie' => $t( 'redactiebeleid' ),
    );
    $posts = get_posts(
        array(
            'post_type'        => 'post',
            'post_status'      => 'publish',
            'posts_per_page'   => 1,
            'suppress_filters' => true,
        )
    );
    if ( ! $posts ) {
        return $uit;
    }
    $post   = $posts[0];
    $pijler = function_exists( 'sfp_page_config_vervolg_pijler' ) ? sfp_page_config_vervolg_pijler( $post ) : null;
    $auteur = (int) $post->post_author;
    $naam   = (string) get_the_author_meta( 'display_name', $auteur );
    $rol    = trim( (string) get_user_meta( $auteur, 'sfp_rol', true ) );
    $minuut = function_exists( 'sfp_page_config_artikel_leestijd' ) ? sfp_page_config_artikel_leestijd( $post->post_content ) : 1;

    $uit['pijler'] = $pijler ? html_entity_decode( $pijler->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '';
    $uit['titel']  = html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $uit['auteur'] = '' !== $naam ? $naam . ( '' !== $rol ? ', ' . $rol : '' ) : '';
    $uit['meta']   = wp_date( 'j M Y', (int) get_post_timestamp( $post, 'date' ) ) . ' · ' . sprintf( $t( 'leestijd' ), $minuut );
    return $uit;
}

/**
 * De CSS van een of meer blokken, voor een voorbeeld in het beheer.
 *
 * @param  string[] $namen Bloknamen.
 * @return string
 */
function sfp_page_config_beheer_blok_css( array $namen ) {
    if ( ! function_exists( 'sfp_page_config_blokken_bestanden_voor' ) ) {
        return '';
    }
    $css = '';
    foreach ( sfp_page_config_blokken_bestanden_voor( $namen ) as $bestand ) {
        $css .= sfp_page_config_blokken_css_bestand( $bestand );
    }
    return $css;
}

/**
 * Enkele reviews voor het voorbeeld in het beheer: de bewaarde reviews van
 * Google en de eigen reviews, zonder Google opnieuw te bevragen.
 *
 * @return array<int, array{author: string, rating: int, text: string, time: string}>
 */
function sfp_page_config_beheer_reviews_voorbeeld() {
    $opts = sfp_page_config_reviews_opties();
    $uit  = array();
    $data = '' !== (string) $opts['place_id'] ? get_transient( 'sfp_reviews_' . md5( (string) $opts['place_id'] ) ) : false;
    if ( is_array( $data ) && ! empty( $data['reviews'] ) ) {
        foreach ( $data['reviews'] as $r ) {
            if ( (int) $r['rating'] >= (int) $opts['min_stars'] ) {
                $uit[] = $r;
            }
        }
    }
    if ( class_exists( 'SFP_Reviews_Manual' ) ) {
        $uit = array_merge( $uit, SFP_Reviews_Manual::get_filtered( (int) $opts['min_stars'] ) );
    }
    $schoon = array();
    foreach ( array_slice( $uit, 0, 3 ) as $r ) {
        $schoon[] = array(
            'author' => (string) ( $r['author'] ?? '' ),
            'rating' => (int) ( $r['rating'] ?? 5 ),
            'text'   => wp_trim_words( (string) ( $r['text'] ?? '' ), 28 ),
            'time'   => (string) ( $r['time'] ?? '' ),
        );
    }
    return $schoon;
}

/* =========================================================================
 * REST
 * ====================================================================== */

add_action( 'rest_api_init', 'sfp_page_config_beheer_rest' );

/**
 * De routes van het beheer. Allemaal alleen voor beheerders.
 */
function sfp_page_config_beheer_rest() {
    $mag = function () {
        return current_user_can( 'manage_options' );
    };
    $route = function ( $pad, $methode, $callback ) use ( $mag ) {
        register_rest_route(
            'sfp/v1',
            '/beheer' . $pad,
            array(
                'methods'             => $methode,
                'permission_callback' => $mag,
                'callback'            => $callback,
            )
        );
    };
    $stand = function () {
        return rest_ensure_response( sfp_page_config_beheer_stand() );
    };
    $json = function ( WP_REST_Request $verzoek ) {
        $gegevens = $verzoek->get_json_params();
        return is_array( $gegevens ) ? $gegevens : (array) $verzoek->get_body_params();
    };

    // De hele stand.
    $route( '', 'GET', $stand );

    // Instellingen: alleen de meegestuurde sleutels veranderen.
    $route(
        '/instellingen',
        'POST',
        function ( WP_REST_Request $verzoek ) use ( $json ) {
            sfp_page_config_instellingen_bewaar( $json( $verzoek ) );
            return rest_ensure_response( sfp_page_config_beheer_stand() );
        }
    );

    // Eén instelling terug naar de netwerknorm.
    $route(
        '/norm',
        'POST',
        function ( WP_REST_Request $verzoek ) use ( $json ) {
            $gegevens = $json( $verzoek );
            sfp_page_config_instelling_naar_norm( isset( $gegevens['pad'] ) ? (string) $gegevens['pad'] : '' );
            return rest_ensure_response( sfp_page_config_beheer_stand() );
        }
    );

    // Pagina's: de controle opnieuw doen.
    $route(
        '/paginas',
        'GET',
        function () {
            return rest_ensure_response( sfp_page_config_paginas_overzicht( true ) );
        }
    );

    // Eén pagina: soort, knop onderaan, een melding wegklikken.
    register_rest_route(
        'sfp/v1',
        '/beheer/pagina/(?P<id>\d+)',
        array(
            'methods'             => 'POST',
            'permission_callback' => function ( WP_REST_Request $verzoek ) {
                return current_user_can( 'manage_options' ) && current_user_can( 'edit_post', (int) $verzoek['id'] );
            },
            'callback'            => function ( WP_REST_Request $verzoek ) use ( $json ) {
                $klaar = sfp_page_config_pagina_bewaar( (int) $verzoek['id'], $json( $verzoek ) );
                if ( is_wp_error( $klaar ) ) {
                    return $klaar;
                }
                return rest_ensure_response( sfp_page_config_beheer_stand() );
            },
        )
    );

    // Cursusdata van één training.
    register_rest_route(
        'sfp/v1',
        '/beheer/cursusdata/(?P<id>\d+)',
        array(
            'methods'             => 'POST',
            'permission_callback' => function ( WP_REST_Request $verzoek ) {
                return current_user_can( 'manage_options' ) && current_user_can( 'edit_post', (int) $verzoek['id'] );
            },
            'callback'            => function ( WP_REST_Request $verzoek ) use ( $json ) {
                $gegevens = $json( $verzoek );
                if ( 'page' !== get_post_type( (int) $verzoek['id'] ) ) {
                    return new WP_Error( 'sfp_cursusdata_geen_pagina', 'Deze pagina bestaat niet.', array( 'status' => 404 ) );
                }
                sfp_page_config_cursusdata_bewaar( (int) $verzoek['id'], isset( $gegevens['data'] ) ? $gegevens['data'] : array() );
                return rest_ensure_response( sfp_page_config_beheer_stand() );
            },
        )
    );

    // Vervolg per pijler: maken.
    $route(
        '/vervolg',
        'POST',
        function ( WP_REST_Request $verzoek ) use ( $json ) {
            $gegevens = $json( $verzoek );
            $post_id  = sfp_page_config_vervolg_maak( isset( $gegevens['pijler'] ) ? (int) $gegevens['pijler'] : 0 );
            if ( is_wp_error( $post_id ) ) {
                return $post_id;
            }
            return rest_ensure_response(
                array(
                    'bewerk' => (string) get_edit_post_link( $post_id, 'raw' ),
                    'stand'  => sfp_page_config_beheer_stand(),
                )
            );
        }
    );

    // Vervolg per pijler: een Astra-layout overnemen.
    $route(
        '/vervolg/overnemen',
        'POST',
        function ( WP_REST_Request $verzoek ) use ( $json ) {
            $gegevens = $json( $verzoek );
            $post_id  = sfp_page_config_vervolg_neem_over( isset( $gegevens['layout'] ) ? (int) $gegevens['layout'] : 0, isset( $gegevens['pijler'] ) ? (int) $gegevens['pijler'] : 0 );
            if ( is_wp_error( $post_id ) ) {
                return $post_id;
            }
            return rest_ensure_response( sfp_page_config_beheer_stand() );
        }
    );

    // Footerlinks: de groepen uit de widget lezen, om over te nemen.
    $route(
        '/footer/widget',
        'GET',
        function () {
            $gevonden = sfp_page_config_footer_uit_widget();
            return rest_ensure_response( $gevonden ? $gevonden : array( 'groepen' => array() ) );
        }
    );

    // Reviews: weergave en koppeling met Google.
    $route(
        '/reviews',
        'POST',
        function ( WP_REST_Request $verzoek ) use ( $json ) {
            $gegevens = $json( $verzoek );
            // Een lege sleutel laat de bewaarde sleutel staan.
            if ( isset( $gegevens['api_key'] ) && '' === trim( (string) $gegevens['api_key'] ) ) {
                unset( $gegevens['api_key'] );
            }
            $oud  = sfp_page_config_reviews_opties();
            $nieuw = sfp_page_config_reviews_opties_schoon( $gegevens );
            // "Naast elkaar" met de oude standaard van 1 kolom staat onder elkaar.
            // Wie het hier kiest, krijgt drie kolommen op een breed scherm.
            if ( 'grid' === $nieuw['layout'] && 'grid' !== $oud['layout'] && (int) $nieuw['cols_desktop'] < 2 ) {
                $nieuw['cols_desktop'] = 3;
            }
            update_option( 'sfp_reviews_options', $nieuw );
            if ( $oud['place_id'] !== $nieuw['place_id'] || $oud['api_key'] !== $nieuw['api_key'] ) {
                delete_transient( 'sfp_reviews_' . md5( (string) $nieuw['place_id'] ) );
            }
            delete_transient( 'sfp_pc_in_gebruik' );
            return rest_ensure_response( sfp_page_config_beheer_stand() );
        }
    );

    // Reviews: nu ophalen bij Google.
    $route(
        '/reviews/ophalen',
        'POST',
        function () {
            $opts = sfp_page_config_reviews_opties();
            if ( '' === (string) $opts['place_id'] || '' === (string) $opts['api_key'] ) {
                return new WP_Error( 'sfp_reviews_niet_ingesteld', 'De koppeling met Google is nog niet ingesteld.', array( 'status' => 400 ) );
            }
            if ( ! class_exists( 'SFP_Reviews_Fetcher' ) ) {
                return new WP_Error( 'sfp_reviews_geen_class', 'Reviews zijn op deze site niet beschikbaar.', array( 'status' => 500 ) );
            }
            $fetcher = new SFP_Reviews_Fetcher( (string) $opts['api_key'], (int) $opts['cache_ttl'] );
            $fetcher->flush( (string) $opts['place_id'] );
            $data = $fetcher->get( (string) $opts['place_id'] );
            if ( is_wp_error( $data ) ) {
                return new WP_Error( 'sfp_reviews_ophalen_mislukt', 'Ophalen is niet gelukt: ' . $data->get_error_message(), array( 'status' => 502 ) );
            }
            return rest_ensure_response( sfp_page_config_beheer_stand() );
        }
    );

    // Eigen reviews: toevoegen of bijwerken.
    $route(
        '/reviews/eigen',
        'POST',
        function ( WP_REST_Request $verzoek ) use ( $json ) {
            if ( ! class_exists( 'SFP_Reviews_Manual' ) || ! method_exists( 'SFP_Reviews_Manual', 'schoon' ) ) {
                return new WP_Error( 'sfp_reviews_oude_plugin', 'Eigen reviews beheer je hier zodra de oude plugin SFP Google Reviews uit staat.', array( 'status' => 409 ) );
            }
            $gegevens = $json( $verzoek );
            $review   = array(
                'author' => isset( $gegevens['author'] ) ? $gegevens['author'] : '',
                'rating' => isset( $gegevens['rating'] ) ? $gegevens['rating'] : 5,
                'datum'  => isset( $gegevens['datum'] ) ? $gegevens['datum'] : '',
                'text'   => isset( $gegevens['text'] ) ? $gegevens['text'] : '',
                'time'   => isset( $gegevens['time'] ) ? $gegevens['time'] : '',
            );
            if ( '' === trim( (string) $review['author'] ) || '' === trim( (string) $review['text'] ) ) {
                return new WP_Error( 'sfp_reviews_onvolledig', 'Vul een naam en een tekst in.', array( 'status' => 400 ) );
            }
            if ( ! empty( $gegevens['id'] ) ) {
                SFP_Reviews_Manual::update( (string) $gegevens['id'], $review );
            } else {
                SFP_Reviews_Manual::add( $review );
            }
            delete_transient( 'sfp_pc_in_gebruik' );
            return rest_ensure_response( sfp_page_config_beheer_stand() );
        }
    );

    // Eigen reviews: weghalen.
    $route(
        '/reviews/eigen/weg',
        'POST',
        function ( WP_REST_Request $verzoek ) use ( $json ) {
            if ( ! class_exists( 'SFP_Reviews_Manual' ) ) {
                return new WP_Error( 'sfp_reviews_geen_class', 'Reviews zijn op deze site niet beschikbaar.', array( 'status' => 500 ) );
            }
            $gegevens = $json( $verzoek );
            SFP_Reviews_Manual::delete( isset( $gegevens['id'] ) ? (string) $gegevens['id'] : '' );
            return rest_ensure_response( sfp_page_config_beheer_stand() );
        }
    );
}

/**
 * De gegevens van één promokaart voor het beheer.
 *
 * @param  array  $alle  Instellingen.
 * @param  string $basis artikel_promo of artikel_promo2.
 * @return array
 */
function sfp_page_config_beheer_promo( array $alle, $basis ) {
    $beeld_id = isset( $alle[ $basis . '_beeld' ] ) ? (int) $alle[ $basis . '_beeld' ] : 0;
    $beeld    = $beeld_id ? wp_get_attachment_image_url( $beeld_id, 'medium' ) : '';
    $lees     = function ( $sleutel ) use ( $alle, $basis ) {
        return isset( $alle[ $basis . '_' . $sleutel ] ) ? (string) $alle[ $basis . '_' . $sleutel ] : '';
    };
    return array(
        'beeld'     => $beeld_id,
        'beeld_url' => $beeld ? (string) $beeld : '',
        'label'     => $lees( 'label' ),
        'titel'     => $lees( 'titel' ),
        'tekst'     => $lees( 'tekst' ),
        'linktekst' => $lees( 'linktekst' ),
        'pagina'    => (int) $lees( 'pagina' ),
        'url'       => $lees( 'url' ),
        'vorm'      => $lees( 'vorm' ),
    );
}
