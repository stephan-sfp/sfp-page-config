<?php
/**
 * SFP Page Config - Body Class + Sticky CTA Config
 *
 * Reads `sfp_page_type` post meta on pages AND posts.
 * - Adds `.is-sales-page` and `.is-sales-page--{type}` body classes.
 * - Injects `window.sfpStickyConfig` for the sticky CTA JavaScript.
 * - Enqueues the sticky CTA JS and sales-page CSS on matching pages.
 *
 * Since 3.0.0 the button finds the top of the page and the form itself, also
 * on pages built with the SFP blocks. The Convert Pro helper (promo.js) is
 * gone: Convert Pro runs on none of the eight sites.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Body classes
 * ====================================================================== */

add_filter( 'body_class', 'sfp_page_config_body_classes' );

/**
 * Add sales-page body classes when sfp_page_type is set.
 *
 * @param  string[] $classes Existing body classes.
 * @return string[]
 */
function sfp_page_config_body_classes( $classes ) {

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return $classes;
    }

    $type = get_post_meta( get_queried_object_id(), 'sfp_page_type', true );

    if ( $type ) {
        $classes[] = 'is-sales-page';
        // Alleen met de knop onderaan gaan de omlijnde knoppen in de tekst
        // op een telefoon weg (assets/sales-page.css).
        if ( sfp_page_config_onderdeel_aan( 'knop' ) ) {
            $classes[] = 'sfp-knop-onderaan';
        }
        $classes[] = 'is-sales-page--' . sanitize_html_class( $type );
    }

    return $classes;
}

/* =========================================================================
 * Inject window.sfpStickyConfig + enqueue assets
 * ====================================================================== */

add_action( 'wp_enqueue_scripts', 'sfp_page_config_enqueue_sales_assets' );

/**
 * Conditionally enqueue the sticky CTA JS and sales-page CSS.
 */
function sfp_page_config_enqueue_sales_assets() {

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return;
    }

    $type = get_post_meta( get_queried_object_id(), 'sfp_page_type', true );
    if ( ! $type ) {
        return;
    }
    // De testpagina van de zelftest heeft geen knop onderaan: alleen het kruisje leidt weg.
    if ( function_exists( 'sfp_zelftest_pagina_test' ) && '' !== sfp_zelftest_pagina_test() ) {
        return;
    }

    // Sales page CSS (always loaded on sales pages).
    sfp_page_config_css_inline( 'sfp-page-config-sales', 'sales-page.css' );

    // Inject brand CSS custom properties. Every value comes from Astra
    // via sfp_page_config_get_brand() and is already validated there:
    // colours by sfp_page_config_sanitize_css_color() (hex, var(), rgb/hsl),
    // fonts by sfp_page_config_sanitize_font_family(). esc_attr() is not
    // used because wp_add_inline_style() outputs raw CSS and esc_attr()
    // would turn the quotes in a font stack into entities.
    $brand      = sfp_page_config_get_brand();
    $inline_css = sprintf(
        ':root{--brand-cta-bg:%s;--brand-cta-hover:%s;--brand-cta-text:%s;--brand-heading-font:%s;--brand-button-weight:%s;}',
        $brand['cta_bg'],
        $brand['cta_hover'],
        $brand['cta_text'],
        $brand['font'],
        $brand['weight']
    );

    wp_add_inline_style( 'sfp-page-config-sales', $inline_css );

    // De knop onderaan.
    if ( ! sfp_page_config_onderdeel_aan( 'knop' ) ) {
        return;
    }
    $config = sfp_page_config_knop_voor_pagina( get_queried_object_id(), $type );
    if ( ! $config ) {
        return;
    }

    wp_enqueue_script(
        'sfp-page-config-sticky-cta',
        SFP_PAGE_CONFIG_URL . 'assets/sticky-cta.js',
        array(),
        SFP_PAGE_CONFIG_VERSION,
        true
    );
    wp_add_inline_script( 'sfp-page-config-sticky-cta', 'window.sfpStickyConfig=' . wp_json_encode( $config ) . ';', 'before' );
}

/**
 * De knop onderaan voor één pagina: de instelling van de soort pagina, met
 * daaroverheen wat bij de pagina zelf is gekozen.
 *
 * Het script zoekt bij het doel "form" zelf het formulier op de pagina: eerst
 * een onderdeel met een van de bekende ankers (zoals op pagina's van voor
 * 3.0.0), dan de boekkaart, dan het eerste formulier in de inhoud.
 *
 * @param  int    $post_id Pagina.
 * @param  string $type    coaching, training of incompany.
 * @return array|null      Gegevens voor assets/sticky-cta.js.
 */
function sfp_page_config_knop_voor_pagina( $post_id, $type ) {
    $config = sfp_page_config_get_sticky_cta( $type );
    if ( ! $config ) {
        return null;
    }

    $eigen_tekst = trim( (string) get_post_meta( $post_id, 'sfp_cta_text', true ) );
    if ( '' !== $eigen_tekst ) {
        $config['text'] = $eigen_tekst;
    }

    // Ankers waaraan het formulier te herkennen is. Het oude veld per soort
    // pagina telt nog mee voor sites die daar iets eigens hadden ingevuld.
    // Een training heeft een inschrijfformulier, coaching en incompany een
    // aanvraagformulier: dat anker wordt eerst gezocht.
    $ankers = 'training' === $type ? array( 'inschrijven', 'aanvragen' ) : array( 'aanvragen', 'inschrijven' );
    $alle   = sfp_page_config_instellingen();
    $eigen  = isset( $alle['sticky_cta'][ $type ] ) && is_array( $alle['sticky_cta'][ $type ] ) ? $alle['sticky_cta'][ $type ] : array();
    if ( ! empty( $eigen['anchor'] ) ) {
        array_unshift( $ankers, sfp_page_config_anker_schoon( (string) $eigen['anchor'] ) );
    }
    // Tot en met 2.12.0 kon in het adresveld ook een anker staan. Hetzelfde
    // geldt voor een anker dat in het veld "een andere link" is getypt.
    foreach ( array( 'href', 'link' ) as $veld ) {
        if ( ! empty( $eigen[ $veld ] ) && 0 === strpos( trim( (string) $eigen[ $veld ] ), '#' ) && 'form' === $config['doel'] ) {
            array_unshift( $ankers, sfp_page_config_anker_schoon( (string) $eigen[ $veld ] ) );
        }
    }
    // Een bovenkant die tot en met 2.12.0 met de hand was aangewezen.
    $hero = ! empty( $eigen['hero'] ) ? sanitize_text_field( (string) $eigen['hero'] ) : '';

    // Per pagina: een eigen bestemming. Een anker (#...) wijst naar een
    // onderdeel van de pagina zelf, al het andere is een link.
    $eigen_pagina = (int) get_post_meta( $post_id, 'sfp_cta_pagina', true );
    $eigen_doel   = trim( (string) get_post_meta( $post_id, 'sfp_cta_href', true ) );
    if ( $eigen_pagina && 'publish' === get_post_status( $eigen_pagina ) ) {
        $config = sfp_page_config_sticky_cta_vorm( $config['text'], 'pagina', (string) get_permalink( $eigen_pagina ) );
    } elseif ( '' !== $eigen_doel ) {
        if ( 0 === strpos( $eigen_doel, '#' ) ) {
            $config = sfp_page_config_sticky_cta_vorm( $config['text'], 'form', '' );
            array_unshift( $ankers, sfp_page_config_anker_schoon( $eigen_doel ) );
        } else {
            $config = sfp_page_config_sticky_cta_vorm( $config['text'], 'link', $eigen_doel );
        }
    }

    $config['hero']   = $hero;
    $config['ankers'] = array_values( array_unique( array_filter( $ankers ) ) );
    // Voor het geval de pagina geen herkenbare bovenkant heeft.
    $config['scrollThreshold'] = 400;

    return $config;
}

/**
 * Maak van een anker (#naam of naam) een id zoals het in de pagina staat.
 * Hoofdletters blijven: een id is hoofdlettergevoelig.
 *
 * @param  string $anker Anker, met of zonder hekje.
 * @return string
 */
function sfp_page_config_anker_schoon( $anker ) {
    return (string) preg_replace( '/[^A-Za-z0-9_\-:.]/', '', ltrim( trim( (string) $anker ), '#' ) );
}
