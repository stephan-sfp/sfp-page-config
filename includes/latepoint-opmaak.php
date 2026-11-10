<?php
/**
 * SFP Page Config - LatePoint-opmaak (3.37.0, punt 14 home/Over).
 *
 * De pagina waar een klant zijn afspraak beheert of annuleert (link uit de
 * bevestigingsmail, admin-post.php?action=latepoint_route_call) draait in
 * een kaal sjabloon van LatePoint, zonder thema en zonder de lettertypes en
 * kleuren van Astra. Daar:
 *   - de annuleerknop leesbaar: wit op de functionele foutkleur uit de
 *     merkbron (contrast boven 4,5 in plaats van ongeveer 2,6);
 *   - de pagina in het bodyfont van de site.
 *
 * Hangt aan het stijlbestand van LatePoint (latepoint-main-front), zoals het
 * snippet "LatePoint: boekkaart in merkrollen". Onderdeel latepoint in SFP
 * Beheer, standaard aan.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', 'sfp_page_config_latepoint_opmaak', 1000 );
add_filter( 'latepoint_clean_layout_css_files', 'sfp_page_config_latepoint_kale_css' );

/**
 * Het kale sjabloon van LatePoint print geen wp_head, alleen de stijlbestanden
 * uit deze lijst (standaard alleen latepoint-main-front). Zonder dit filter
 * werd het lettertype wel klaargezet maar nooit geladen (3.41.4).
 *
 * @param  mixed $handles Lijst met stijlbestanden.
 * @return mixed
 */
function sfp_page_config_latepoint_kale_css( $handles ) {
    if ( is_array( $handles ) && wp_style_is( 'sfp-latepoint-fonts', 'registered' ) && ! in_array( 'sfp-latepoint-fonts', $handles, true ) ) {
        array_unshift( $handles, 'sfp-latepoint-fonts' );
    }
    return $handles;
}

/**
 * Is dit de kale pagina van LatePoint (een route via admin-post.php)?
 *
 * @return bool
 */
function sfp_page_config_latepoint_kale_pagina() {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- alleen lezen om de pagina te herkennen.
    $actie = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
    // phpcs:enable
    return 'latepoint_route_call' === $actie && false !== strpos( (string) ( $_SERVER['SCRIPT_NAME'] ?? '' ), 'admin-post.php' );
}

/**
 * Zet de opmaak en het lettertype op de kale pagina van LatePoint.
 */
function sfp_page_config_latepoint_opmaak() {
    if ( ! function_exists( 'sfp_page_config_onderdeel_aan' ) || ! sfp_page_config_onderdeel_aan( 'latepoint' ) ) {
        return;
    }
    if ( ! sfp_page_config_latepoint_kale_pagina() || ! wp_style_is( 'latepoint-main-front', 'enqueued' ) ) {
        return;
    }
    $brand = function_exists( 'sfp_page_config_get_brand' ) ? sfp_page_config_get_brand() : array();
    $tekst = isset( $brand['body_font'] ) && '' !== $brand['body_font'] ? $brand['body_font'] : 'system-ui,sans-serif';
    $tekst = (string) preg_replace( '/[^a-zA-Z0-9 ,\'"-]/', '', $tekst );

    if ( function_exists( 'sfp_page_config_beheer_fonts_url' ) ) {
        $fonts = sfp_page_config_beheer_fonts_url();
        if ( '' !== $fonts ) {
            wp_enqueue_style( 'sfp-latepoint-fonts', $fonts, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google bepaalt de versie.
        }
    }

    // De functionele foutkleur uit de merkbron; de enige vaste kleur hier.
    $css = 'body.latepoint-clean-body,body.latepoint-clean-body .latepoint-w :is(div,p,span,a,button,h1,h2,h3,h4,h5,h6,label,input,select,textarea,strong,li,td,th):not([class*="latepoint-icon"]){font-family:' . $tekst . '}'
        . 'body.latepoint-clean-body .latepoint-w .latepoint-btn.latepoint-btn-danger,body.latepoint-clean-body .latepoint-w .latepoint-btn-danger{background-color:#A4262C;border-color:#A4262C;color:#fff;font-weight:700}'
        . 'body.latepoint-clean-body .latepoint-w .latepoint-btn-danger:is(:hover,:focus-visible){background-color:#A4262C;color:#fff;text-decoration:underline;text-underline-offset:3px}'
        . 'body.latepoint-clean-body .latepoint-w .latepoint-btn-danger *{color:inherit}';
    wp_add_inline_style( 'latepoint-main-front', $css );
}
