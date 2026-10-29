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
                'description'     => 'De zelftest van de site: start, vragen en eindscherm. Hoort op de testpagina.',
                // Sinds 3.19.0 in de eigen groep, zoals de andere blokken (ontwerpregel 11).
                'category'        => 'sfp-bouwstenen',
                'example'         => array( 'attributes' => new stdClass() ),
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
        // Sinds 3.13.3 bepaalt de schakelaar Husselen in SFP Beheer dat.
        'weergave' => sfp_zelftest_weergave( $pakket, $test ),
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
 * Hoe de vragen getoond worden.
 *
 * Sinds 3.13.3 beslist de schakelaar Husselen per test (SFP Beheer, Zelftest;
 * netwerknorm aan). Aan: de kernvragen wisselen onderling van plek, de
 * antwoorden van de kernvragen en van de profielvragen wisselen van volgorde,
 * en er staat geen bloknaam boven een vraag. Uit: vaste volgorde met bloknaam.
 *
 * De kern is de lange versie zonder de eerste vijf en de laatste vijf vragen;
 * de profielvragen zijn de vierde en vijfde vraag. Een pakket met een andere
 * opbouw kan beide noemen in manifest.weergave.kern en manifest.weergave.opties
 * (lijsten met vraagnummers). Vragen met meerdere antwoorden husselen nooit.
 *
 * @param  array  $pakket Pakket.
 * @param  string $test   Naam van de test.
 * @return array{vragen:string,opties:string,blokkop:bool,kern:int[],opties_bij:int[]}
 */
function sfp_zelftest_weergave( array $pakket, $test = '' ) {
    $inst = '' !== $test ? sfp_zelftest_instellingen( $test ) : array( 'husselen' => '1' );
    if ( '1' !== (string) $inst['husselen'] ) {
        return array(
            'vragen'     => 'vast',
            'opties'     => 'vast',
            'blokkop'    => true,
            'kern'       => array(),
            'opties_bij' => array(),
        );
    }
    $w     = isset( $pakket['manifest']['weergave'] ) && is_array( $pakket['manifest']['weergave'] ) ? $pakket['manifest']['weergave'] : array();
    $lang  = isset( $pakket['manifest']['lengtes']['long']['vragen'] ) ? array_map( 'intval', (array) $pakket['manifest']['lengtes']['long']['vragen'] ) : array();
    sort( $lang );
    $lijst = function ( $sleutel, $standaard ) use ( $w ) {
        if ( isset( $w[ $sleutel ] ) && is_array( $w[ $sleutel ] ) && $w[ $sleutel ] ) {
            return array_values( array_unique( array_map( 'intval', $w[ $sleutel ] ) ) );
        }
        return $standaard;
    };
    $kern   = $lijst( 'kern', count( $lang ) > 10 ? array_slice( $lang, 5, count( $lang ) - 10 ) : array() );
    $profiel = count( $lang ) > 4 ? array( $lang[3], $lang[4] ) : array();
    $opties = $lijst( 'opties', array_values( array_unique( array_merge( $profiel, $kern ) ) ) );
    // Vragen met meerdere antwoorden blijven staan.
    $meerdere = array();
    foreach ( isset( $pakket['inhoud']['vragen'] ) ? (array) $pakket['inhoud']['vragen'] : array() as $v ) {
        if ( ! empty( $v['meerdere'] ) && isset( $v['nr'] ) ) {
            $meerdere[] = (int) $v['nr'];
        }
    }
    return array(
        'vragen'     => 'willekeurig',
        'opties'     => 'willekeurig',
        'blokkop'    => false,
        'kern'       => array_values( array_diff( $kern, $meerdere ) ),
        'opties_bij' => array_values( array_diff( $opties, $meerdere ) ),
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
