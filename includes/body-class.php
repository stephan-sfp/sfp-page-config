<?php
/**
 * SFP Page Config - Body Class + Sticky CTA Config
 *
 * Reads `sfp_page_type` post meta on pages AND posts.
 * - Adds `.is-sales-page` and `.is-sales-page--{type}` body classes.
 * - Injects `window.sfpStickyConfig` for the sticky CTA JavaScript.
 * - Enqueues the sticky CTA JS and sales-page CSS on matching pages.
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

    // Sales page CSS (always loaded on sales pages).
    wp_enqueue_style(
        'sfp-page-config-sales',
        SFP_PAGE_CONFIG_URL . 'assets/sales-page.css',
        array(),
        SFP_PAGE_CONFIG_VERSION
    );

    // Inject brand CSS custom properties.
    // Note: wp_add_inline_style outputs raw CSS, so we must NOT use
    // esc_attr() for font-family values (it converts quotes to HTML
    // entities which are invalid in CSS). Instead, we strip any
    // characters that could break out of the CSS value context.
    $brand = sfp_page_config_get_brand();
    $safe_font = preg_replace( '/[^a-zA-Z0-9\s\'",\-]/', '', $brand['font'] );
    $inline_css = sprintf(
        ':root{--brand-cta-bg:%s;--brand-cta-hover:%s;--brand-cta-text:%s;--brand-heading-font:%s;--brand-button-weight:%s;}',
        sanitize_hex_color( $brand['cta_bg'] ),
        sanitize_hex_color( $brand['cta_hover'] ),
        sanitize_hex_color( isset( $brand['cta_text'] ) ? $brand['cta_text'] : '#ffffff' ),
        $safe_font,
        esc_attr( $brand['weight'] )
    );

    wp_add_inline_style( 'sfp-page-config-sales', $inline_css );

    // Sticky CTA config + JS.
    $config = sfp_page_config_get_sticky_cta( $type );
    if ( ! $config ) {
        return;
    }

    // Allow per-page overrides for CTA text and href.
    $post_id     = get_queried_object_id();
    $custom_text = get_post_meta( $post_id, 'sfp_cta_text', true );
    $custom_href = get_post_meta( $post_id, 'sfp_cta_href', true );

    if ( $custom_text ) {
        $config['text'] = $custom_text;
    }
    if ( $custom_href ) {
        $config['href'] = $custom_href;
        // Auto-detect external links for target attribute.
        $config['target'] = ( 0 === strpos( $custom_href, '#' ) ) ? '_self' : '_blank';
    }

    wp_enqueue_script(
        'sfp-page-config-sticky-cta',
        SFP_PAGE_CONFIG_URL . 'assets/sticky-cta.js',
        array(),
        SFP_PAGE_CONFIG_VERSION,
        true
    );

    // Pass config to JS.
    $inline_js = sprintf(
        'window.sfpStickyConfig=%s;',
        wp_json_encode( array(
            'text'            => $config['text'],
            'href'            => $config['href'],
            'target'          => $config['target'],
            'anchor'          => $config['anchor'],
            'hero'            => $config['hero'],
            'scrollThreshold' => 400,
        ) )
    );
    wp_add_inline_script( 'sfp-page-config-sticky-cta', $inline_js, 'before' );

    // Promo frequency/conflict resolution JS (loaded alongside sticky CTA).
    wp_enqueue_script(
        'sfp-page-config-promo',
        SFP_PAGE_CONFIG_URL . 'assets/promo.js',
        array(),
        SFP_PAGE_CONFIG_VERSION,
        true
    );

    // Pass configurable thresholds to the promo script. Values come from
    // the Instellingen tab; the sanitizer clamps them to sane ranges.
    $promo_config = array(
        'scrollGate'    => (int) sfp_page_config_get_setting( 'promo_scroll_gate', 30 ),
        'cooldownHours' => (int) sfp_page_config_get_setting( 'promo_cooldown_hours', 24 ),
    );
    wp_add_inline_script(
        'sfp-page-config-promo',
        'window.sfpPromoConfig=' . wp_json_encode( $promo_config ) . ';',
        'before'
    );
}
