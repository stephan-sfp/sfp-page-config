<?php
/**
 * SFP Page Config - Wachtlijst
 *
 * Een trainingspagina zonder toekomstige groep toont vanzelf de wachtlijst:
 *
 *   1. [cursus_datum] toont de wachtlijsttekst met een link naar het formulier
 *      (includes/shortcodes.php, via sfp_page_config_cursusdata_toekomst()).
 *   2. Het inschrijfformulier van de site wordt het wachtlijstformulier van de
 *      site (instellingen inschrijf_formulier en wachtlijst_formulier).
 *   3. Een blok met de klasse sfp-met-data staat er alleen met een toekomstige
 *      groep, een blok met sfp-zonder-data alleen zonder.
 *
 * Komen er weer toekomstige data, dan keert de pagina vanzelf terug.
 * Een groep telt als toekomstig als de eerste dag vandaag of later is.
 *
 * @package SFP_Page_Config
 * @since   3.17.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De groepen van een pagina waarvan de eerste dag vandaag of later is, in
 * volgorde. Zonder lijst startmomenten telt het oude veld startdatum.
 *
 * @param  int $post_id Pagina.
 * @return array<int, array{data: string[], start?: string, eind?: string}>
 */
function sfp_page_config_cursusdata_toekomst( $post_id ) {
    $post_id = (int) $post_id;
    static $cache = array();
    if ( isset( $cache[ $post_id ] ) ) {
        return $cache[ $post_id ];
    }

    $vandaag = wp_date( 'Y-m-d' );
    $json    = get_post_meta( $post_id, 'sfp_cursusdata', true );
    $data    = $json ? json_decode( $json, true ) : array();
    if ( ! is_array( $data ) ) {
        $data = array();
    }

    $uit = array();
    foreach ( $data as $sm ) {
        if ( ! is_array( $sm ) || empty( $sm['data'] ) || ! is_array( $sm['data'] ) ) {
            continue;
        }
        $dagen = array_values( array_filter( array_map( 'strval', $sm['data'] ), 'strlen' ) );
        if ( ! $dagen ) {
            continue;
        }
        sort( $dagen );
        if ( $dagen[0] >= $vandaag ) {
            $sm['data'] = $dagen;
            $uit[]      = $sm;
        }
    }

    if ( ! $data ) {
        $oud = (string) get_post_meta( $post_id, 'startdatum', true );
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $oud ) && $oud >= $vandaag ) {
            $uit[] = array( 'data' => array( $oud ) );
        }
    }

    usort(
        $uit,
        function ( $a, $b ) {
            return strcmp( $a['data'][0], $b['data'][0] );
        }
    );

    $cache[ $post_id ] = $uit;
    return $uit;
}

/**
 * Is dit een trainingspagina zonder toekomstige groep?
 *
 * @param  int $post_id Pagina.
 * @return bool
 */
function sfp_page_config_wachtlijst_stand( $post_id ) {
    $post_id = (int) $post_id;
    if ( ! $post_id || ! sfp_page_config_onderdeel_aan( 'cursusdata' ) ) {
        return false;
    }
    if ( 'training' !== get_post_meta( $post_id, 'sfp_page_type', true ) ) {
        return false;
    }
    return ! sfp_page_config_cursusdata_toekomst( $post_id );
}

/**
 * De pagina die nu wordt getoond, als het een trainingspagina is.
 *
 * @return int 0 als het geen trainingspagina is.
 */
function sfp_page_config_wachtlijst_pagina() {
    if ( is_admin() || ! is_singular( sfp_page_config_post_types() ) ) {
        return 0;
    }
    $post_id = (int) get_queried_object_id();
    return 'training' === get_post_meta( $post_id, 'sfp_page_type', true ) ? $post_id : 0;
}

/**
 * Het formulier-ID uit een instelling, alleen als het een gepubliceerd
 * SureForms-formulier is.
 *
 * @param  string $sleutel inschrijf_formulier of wachtlijst_formulier.
 * @return int
 */
function sfp_page_config_wachtlijst_formulier_id( $sleutel ) {
    $id = absint( sfp_page_config_get_setting( $sleutel, 0 ) );
    if ( ! $id || 'sureforms_form' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
        return 0;
    }
    return $id;
}

/**
 * Het anker waar het formulier staat, voor de link in de wachtlijsttekst.
 * Volgt het anker dat bij de knop onderaan voor trainingen is ingesteld.
 *
 * @return string Zonder #.
 */
function sfp_page_config_wachtlijst_anker() {
    $alle  = sfp_page_config_instellingen();
    $eigen = isset( $alle['sticky_cta']['training']['anchor'] ) ? (string) $alle['sticky_cta']['training']['anchor'] : '';
    $anker = '' !== $eigen && function_exists( 'sfp_page_config_anker_schoon' ) ? sfp_page_config_anker_schoon( $eigen ) : '';
    return '' !== $anker ? $anker : 'inschrijven';
}

/**
 * De tekst die [cursus_datum] toont zonder toekomstige groep. Met een
 * wachtlijstformulier: de wachtlijsttekst met link, anders de oude tekst
 * "geen startmomenten".
 *
 * @return string HTML.
 */
function sfp_page_config_wachtlijst_html() {
    if ( ! sfp_page_config_wachtlijst_formulier_id( 'wachtlijst_formulier' ) ) {
        return '<span class="sfp-wachtlijst-tekst" style="margin:0">' . esc_html( sfp_page_config_tekst( 'geen_data' ) ) . '</span>';
    }
    $link = '<a href="#' . esc_attr( sfp_page_config_wachtlijst_anker() ) . '">' . esc_html( sfp_page_config_tekst( 'wachtlijst_link' ) ) . '</a>';
    $zin  = sfp_page_config_tekst_f( 'wachtlijst_tekst', '%%LINK%%' );
    // margin:0: Spectra geeft elke span in een FAQ-antwoord een marge, dan
    // springt deze tekst verder in dan de andere antwoorden.
    return '<span class="sfp-wachtlijst-tekst" style="margin:0">' . str_replace( '%%LINK%%', $link, esc_html( $zin ) ) . '</span>';
}

/* =========================================================================
 * Formulier wisselen
 * ====================================================================== */

add_filter( 'render_block_data', 'sfp_page_config_wachtlijst_formulier_wissel', 10, 1 );

/**
 * Zonder toekomstige groep wordt het inschrijfformulier het wachtlijstformulier.
 *
 * @param  array $block Blok voor het tekenen.
 * @return array
 */
function sfp_page_config_wachtlijst_formulier_wissel( $block ) {
    if ( empty( $block['blockName'] ) || 'srfm/form' !== $block['blockName'] ) {
        return $block;
    }
    $post_id = sfp_page_config_wachtlijst_pagina();
    if ( ! $post_id || ! sfp_page_config_wachtlijst_stand( $post_id ) ) {
        return $block;
    }
    $inschrijf  = sfp_page_config_wachtlijst_formulier_id( 'inschrijf_formulier' );
    $wachtlijst = sfp_page_config_wachtlijst_formulier_id( 'wachtlijst_formulier' );
    if ( ! $inschrijf || ! $wachtlijst || empty( $block['attrs']['id'] ) || (int) $block['attrs']['id'] !== $inschrijf ) {
        return $block;
    }
    $block['attrs']['id'] = $wachtlijst;
    return $block;
}

/* =========================================================================
 * Blokken met data of zonder data
 * ====================================================================== */

add_filter( 'render_block', 'sfp_page_config_wachtlijst_blokken', 10, 2 );

/**
 * Laat een blok met sfp-met-data weg zonder toekomstige groep, en een blok
 * met sfp-zonder-data weg met een toekomstige groep. Alleen op een
 * trainingspagina met het onderdeel Cursusdata aan.
 *
 * @param  string $html  Getekend blok.
 * @param  array  $block Blok.
 * @return string
 */
function sfp_page_config_wachtlijst_blokken( $html, $block ) {
    $klassen = isset( $block['attrs']['className'] ) ? ' ' . (string) $block['attrs']['className'] . ' ' : '';
    $met     = false !== strpos( $klassen, ' sfp-met-data ' );
    $zonder  = false !== strpos( $klassen, ' sfp-zonder-data ' );
    if ( ! $met && ! $zonder ) {
        return $html;
    }
    $post_id = sfp_page_config_wachtlijst_pagina();
    if ( ! $post_id || ! sfp_page_config_onderdeel_aan( 'cursusdata' ) ) {
        return $html;
    }
    $wacht = sfp_page_config_wachtlijst_stand( $post_id );
    if ( ( $met && $wacht ) || ( $zonder && ! $wacht ) ) {
        return '';
    }
    return $html;
}
