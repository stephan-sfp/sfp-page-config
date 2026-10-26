<?php
/**
 * SFP Page Config - Zelftest: de test op de pagina
 *
 * Blok sfp/zelftest en code [sfp_zelftest test="dps"]. De schermen (start,
 * vragen, eindscherm) bouwt assets/zelftest/zelftest.js op uit de teksten
 * van het pakket; de rekenregel (motor.js) en de labelcode (label.js) staan
 * inline op de pagina. Kleuren en fonts komen uit het thema.
 *
 * @package SFP_Page_Config
 * @since   3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'sfp_zelftest_registreer' );

/**
 * Registreer code en blok.
 */
function sfp_zelftest_registreer() {
    if ( ! shortcode_exists( 'sfp_zelftest' ) ) {
        add_shortcode( 'sfp_zelftest', 'sfp_zelftest_shortcode' );
    }
    wp_register_script( 'sfp-zelftest-editor', SFP_PAGE_CONFIG_URL . 'assets/zelftest/editor.js', array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor' ), SFP_PAGE_CONFIG_VERSION, true );
    if ( ! WP_Block_Type_Registry::get_instance()->is_registered( 'sfp/zelftest' ) ) {
        register_block_type(
            'sfp/zelftest',
            array(
                'api_version'     => 3,
                'title'           => 'Zelftest',
                'category'        => 'widgets',
                'editor_script'   => 'sfp-zelftest-editor',
                'attributes'      => array(
                    'test' => array(
                        'type'    => 'string',
                        'default' => '',
                    ),
                    // Sinds 3.11.0: taal van de test op deze pagina (leeg: de taal van de testpagina of de site).
                    'taal' => array(
                        'type'    => 'string',
                        'default' => '',
                    ),
                ),
                'supports'        => array( 'html' => false ),
                'render_callback' => function ( $attributen ) {
                    return sfp_zelftest_shortcode(
                        array(
                            'test' => isset( $attributen['test'] ) ? $attributen['test'] : '',
                            'taal' => isset( $attributen['taal'] ) ? $attributen['taal'] : '',
                        )
                    );
                },
            )
        );
    }
}

add_action( 'enqueue_block_editor_assets', 'sfp_zelftest_editor_gegevens' );

/**
 * De tests op deze site voor de keuzelijst in het blok.
 */
function sfp_zelftest_editor_gegevens() {
    $tests = array();
    foreach ( sfp_zelftest_tests() as $test ) {
        $pakket  = sfp_zelftest_pakket( $test );
        $tests[] = array(
            'test' => $test,
            'naam'  => $pakket ? sfp_zelftest_tekst( $pakket, 'algemeen.testnaam' ) : $test,
            'talen' => $pakket ? sfp_zelftest_pakket_talen( $pakket ) : array(),
        );
    }
    wp_add_inline_script( 'sfp-zelftest-editor', 'window.sfpZelftestTests=' . wp_json_encode( array( 'tests' => $tests, 'aan' => sfp_page_config_onderdeel_aan( 'zelftest' ) ) ) . ';', 'before' );
}

/**
 * De variabelen voor kleur en font, gelijk aan die van de bouwstenen.
 *
 * @return string
 */
function sfp_zelftest_merk_css() {
    return function_exists( 'sfp_page_config_blokken_css_variabelen' ) ? sfp_page_config_blokken_css_variabelen() : '';
}

/**
 * De stijl van de test, met de variabelen van de site. Eén keer per pagina.
 *
 * @return void
 */
function sfp_zelftest_stijl() {
    static $klaar = false;
    if ( $klaar ) {
        return;
    }
    $klaar = true;
    sfp_page_config_css_inline( 'sfp-zelftest', 'zelftest/zelftest.css' );
    wp_add_inline_style( 'sfp-zelftest', sfp_zelftest_merk_css() );
}

/**
 * De test op de pagina.
 *
 * @param  array|string $atts Attributen.
 * @return string
 */
function sfp_zelftest_shortcode( $atts ) {
    $atts   = shortcode_atts( array( 'test' => '', 'taal' => '' ), (array) $atts, 'sfp_zelftest' );
    $test   = sfp_zelftest_naam( $atts['test'] );
    $pakket = ( '' !== $test && sfp_page_config_onderdeel_aan( 'zelftest' ) ) ? sfp_zelftest_pakket( $test ) : null;
    if ( ! $pakket ) {
        // In de editor en voor beheerders zichtbaar; bezoekers zien niets.
        if ( current_user_can( 'edit_posts' ) ) {
            return '<p class="sfp-zt-leeg">Zelftest: ' . ( '' === $test ? 'kies een test in het blok.' : 'geen pakket voor "' . esc_html( $test ) . '" of het onderdeel staat uit (SFP Beheer, Onderdelen).' ) . '</p>';
        }
        return '';
    }
    static $al = array();
    if ( isset( $al[ $test ] ) ) {
        return '';
    }
    $al[ $test ] = true;

    // Taal: uit het blok of de code, anders die van de testpagina waarop we staan, anders die van de site.
    sfp_zelftest_taal_context( sfp_zelftest_pagina_taal( $test, $atts['taal'] ) );
    $taal   = sfp_zelftest_taal( $pakket );
    $config = array(
        'test'     => $test,
        'taal'     => $taal,
        'rest'     => esc_url_raw( rest_url( 'sfp/v1/zelftest/' . $test . '/' ) ),
        'lengtes'  => $pakket['manifest']['lengtes'],
        'inhoud'   => $pakket['inhoud'],
        'privacy'  => (string) get_privacy_policy_url(),
        // Sinds 3.11.0: volgorde van vragen en opties, en of de bloknaam boven een vraag staat.
        'weergave' => sfp_zelftest_weergave( $pakket ),
    );

    sfp_zelftest_stijl();
    wp_enqueue_script( 'sfp-zelftest', SFP_PAGE_CONFIG_URL . 'assets/zelftest/zelftest.js', array(), SFP_PAGE_CONFIG_VERSION, true );
    $veilig = function ( $js ) {
        return str_ireplace( '</script', '<\/script', (string) $js );
    };
    wp_add_inline_script(
        'sfp-zelftest',
        'window.sfpZelftestConfig=' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP ) . ";\n"
        . $veilig( $pakket['motor'] ) . "\nwindow.sfpZelftestMotor=MOTOR;\n"
        . $veilig( $pakket['label'] ),
        'before'
    );

    return '<div class="sfp-zt" id="sfp-zelftest" data-test="' . esc_attr( $test ) . '" lang="' . esc_attr( $taal ) . '"><div class="sfp-zt-wrap"><p class="sfp-zt-laden">&nbsp;</p></div></div>';
}

/**
 * De taal van de test op deze pagina: de opgegeven taal als het pakket die
 * heeft, anders de taal waarvoor deze pagina de testpagina is, anders ''.
 *
 * @param  string $test Naam.
 * @param  string $taal Taal uit het blok of de code.
 * @return string
 */
function sfp_zelftest_pagina_taal( $test, $taal = '' ) {
    $pakket = sfp_zelftest_pakket( $test );
    $talen  = $pakket ? sfp_zelftest_pakket_talen( $pakket ) : array();
    $taal   = sfp_zelftest_taalcode( $taal );
    if ( '' !== $taal && in_array( $taal, $talen, true ) ) {
        return $taal;
    }
    $id = (int) get_queried_object_id();
    if ( $id > 0 ) {
        foreach ( sfp_zelftest_testpaginas( $test ) as $t => $pagina ) {
            if ( '' !== $t && $pagina === $id && in_array( $t, $talen, true ) ) {
                return $t;
            }
        }
    }
    return '';
}

/**
 * Hoe de vragen getoond worden (manifest.weergave, sinds 3.11.0). Standaard
 * zoals altijd: vaste volgorde, bloknaam zichtbaar.
 *
 * @param  array $pakket Pakket.
 * @return array{vragen:string,opties:string,blokkop:bool,scorend:int[]}
 */
function sfp_zelftest_weergave( array $pakket ) {
    $w = isset( $pakket['manifest']['weergave'] ) && is_array( $pakket['manifest']['weergave'] ) ? $pakket['manifest']['weergave'] : array();
    return array(
        'vragen'  => isset( $w['volgorde_vragen'] ) && 'willekeurig' === $w['volgorde_vragen'] ? 'willekeurig' : 'vast',
        'opties'  => isset( $w['volgorde_opties'] ) && 'willekeurig' === $w['volgorde_opties'] ? 'willekeurig' : 'vast',
        'blokkop' => ! ( isset( $w['blokkop'] ) && 'nee' === $w['blokkop'] ),
        // Alleen de vragen met een score wisselen van plek en volgorde van opties; context- en slotvragen blijven staan.
        'scorend' => array_map( 'intval', array_keys( isset( $pakket['manifest']['scoretabel'] ) ? (array) $pakket['manifest']['scoretabel'] : array() ) ),
    );
}

add_filter( 'rocket_delay_js_exclusions', 'sfp_zelftest_rocket_delay' );

/**
 * WP Rocket stelt de test niet uit; anders verschijnt hij pas na de eerste
 * aanraking.
 *
 * @param  array $uitsluitingen Uitsluitingen.
 * @return array
 */
function sfp_zelftest_rocket_delay( $uitsluitingen ) {
    $uitsluitingen   = (array) $uitsluitingen;
    $uitsluitingen[] = 'sfp-zelftest';
    $uitsluitingen[] = 'sfpZelftest';
    $uitsluitingen[] = 'assets/zelftest/';
    return $uitsluitingen;
}
