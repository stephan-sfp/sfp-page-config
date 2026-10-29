<?php
/**
 * SFP Page Config - Zelftest: beheer en REST voor beheerders
 *
 *   GET  /wp-json/sfp/v1/zelftest                       stand (zonder geheimen)
 *   POST /wp-json/sfp/v1/zelftest/<test>/pakket         pakket inlezen (JSON met bestanden, of een zip als 'pakket')
 *   POST /wp-json/sfp/v1/zelftest/<test>/pakket/terug   vorige versie terugzetten
 *   POST /wp-json/sfp/v1/zelftest/<test>/instellingen   adressen, webhooks, proefmodus, husselen
 *   POST /wp-json/sfp/v1/zelftest/cloudflare            account-ID en token
 *   POST /wp-json/sfp/v1/zelftest/<test>/proef          pdf voor opgegeven antwoorden (niets opgeslagen of verstuurd)
 *   POST /wp-json/sfp/v1/zelftest/wissen                alles van een e-mailadres wissen
 *
 * Alleen beheerders (manage_options). Geheimen (token, webhookadressen) zijn
 * alleen te schrijven, nooit te lezen.
 *
 * @package SFP_Page_Config
 * @since   3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De stand van de zelftest voor het beheer en de REST-route.
 *
 * @return array
 */
function sfp_zelftest_stand() {
    $cf    = sfp_zelftest_cloudflare();
    $tests = array();
    foreach ( sfp_zelftest_tests() as $test ) {
        $pakket   = sfp_zelftest_pakket( $test );
        $inst     = sfp_zelftest_instellingen( $test );
        $basis    = sfp_zelftest_map( 'pakket/' . $test );
        $controle = $pakket ? sfp_zelftest_pakket_controle(
            array(
                'manifest.json' => wp_json_encode( $pakket['manifest'] ),
                'inhoud.json'   => wp_json_encode( $pakket['inhoud'] ),
                'motor.js'      => $pakket['motor'],
                'label.js'      => $pakket['label'],
                'blad.html'     => $pakket['blad'],
            )
        ) : array( 'fouten' => array(), 'let_op' => array() );
        $tests[] = array(
            'test'         => $test,
            'naam'         => $pakket ? sfp_zelftest_tekst( $pakket, 'algemeen.testnaam' ) : $test,
            'versie'       => $pakket ? (string) $pakket['manifest']['pakket_versie'] : '',
            'ingelezen'    => $pakket ? wp_date( 'j F Y H:i', $pakket['ingelezen'] ) : '',
            'talen'        => $pakket ? (array) $pakket['manifest']['talen'] : array(),
            'vragen'       => $pakket ? count( (array) $pakket['inhoud']['vragen'] ) : 0,
            'lengtes'      => $pakket ? array_map(
                function ( $l ) {
                    return count( (array) $l['vragen'] );
                },
                (array) $pakket['manifest']['lengtes']
            ) : array(),
            'blad'         => $pakket && '' !== $pakket['blad'],
            'vorige'       => '' !== $basis && is_readable( $basis . 'vorig/manifest.json' ),
            'let_op'       => $controle['let_op'],
            'instellingen' => array(
                'testpagina'       => (int) $inst['testpagina'],
                'testpagina_url'   => sfp_zelftest_testpagina_url( $test ),
                'logo'             => (int) $inst['logo'],
                // Sinds 3.19.0: een voorbeeld van het logo bij de keuze uit de mediabibliotheek.
                'logo_url'         => (int) $inst['logo'] ? (string) wp_get_attachment_image_url( (int) $inst['logo'], 'medium' ) : '',
                'pdf_pad'          => $inst['pdf_pad'],
                // Sinds 3.19.0 maakt de plugin het adres zelf; hier wat geldt, per taal.
                'pdf_adressen'     => (object) array_map(
                    function ( $pad ) {
                        return home_url( '/' . $pad . '/' );
                    },
                    sfp_zelftest_pdf_paden( $test )
                ),
                'webhook_start'    => '' !== $inst['webhook_start'],
                'webhook_afgerond' => '' !== $inst['webhook_afgerond'],
                'proefmodus'       => '1' === (string) $inst['proefmodus'],
                'husselen'         => '1' === (string) $inst['husselen'],
                'cta_kop'          => $inst['cta_kop'],
                'cta_zin'          => $inst['cta_zin'],
                'cta_knop'         => $inst['cta_knop'],
                'talen'            => (object) $inst['talen'],
            ),
            'cta'          => function_exists( 'sfp_zelftest_cta_gegevens' ) ? sfp_zelftest_cta_gegevens( $test ) : null,
            'tellingen'    => sfp_zelftest_tellingen( $test ),
        );
    }
    $log = get_option( 'sfp_zelftest_log', array() );
    return array(
        'aan'        => sfp_page_config_onderdeel_aan( 'zelftest' ),
        'site_test'  => function_exists( 'sfp_zelftest_site_test' ) ? sfp_zelftest_site_test() : '',
        'tests'      => $tests,
        'cloudflare' => array(
            'account' => '' !== $cf['account'],
            'token'   => '' !== $cf['token'],
            'pauze'   => (int) get_transient( 'sfp_pc_zt_cf_pauze' ) > time() ? gmdate( 'c', (int) get_transient( 'sfp_pc_zt_cf_pauze' ) ) : '',
        ),
        'zip'        => class_exists( 'ZipArchive' ),
        'log'        => is_array( $log ) ? array_slice( $log, 0, 20 ) : array(),
    );
}

add_action( 'rest_api_init', 'sfp_zelftest_rest_beheer' );

/**
 * Registreer de beheerroutes.
 */
function sfp_zelftest_rest_beheer() {
    $mag  = function () {
        return current_user_can( 'manage_options' );
    };
    $json = function ( WP_REST_Request $verzoek ) {
        $g = $verzoek->get_json_params();
        return is_array( $g ) ? $g : (array) $verzoek->get_body_params();
    };
    $test = '(?P<test>[a-z0-9][a-z0-9-]{0,39})';

    register_rest_route(
        'sfp/v1',
        '/zelftest',
        array(
            'methods'             => 'GET',
            'permission_callback' => $mag,
            'callback'            => function () {
                return rest_ensure_response( sfp_zelftest_stand() );
            },
        )
    );

    register_rest_route(
        'sfp/v1',
        '/zelftest/' . $test . '/pakket',
        array(
            'methods'             => 'POST',
            'permission_callback' => $mag,
            'callback'            => function ( WP_REST_Request $verzoek ) use ( $json ) {
                $bestanden = array();
                $files     = $verzoek->get_file_params();
                if ( ! empty( $files['pakket']['tmp_name'] ) && is_uploaded_file( $files['pakket']['tmp_name'] ) ) {
                    $bestanden = sfp_zelftest_pakket_uit_zip( $files['pakket']['tmp_name'] );
                    if ( is_wp_error( $bestanden ) ) {
                        return $bestanden;
                    }
                } else {
                    $g         = $json( $verzoek );
                    $bestanden = isset( $g['bestanden'] ) && is_array( $g['bestanden'] ) ? array_map(
                        function ( $w ) {
                            return is_scalar( $w ) ? (string) $w : '';
                        },
                        $g['bestanden']
                    ) : array();
                }
                $uitkomst = sfp_zelftest_pakket_inlezen( (string) $verzoek['test'], $bestanden );
                if ( is_wp_error( $uitkomst ) ) {
                    return $uitkomst;
                }
                sfp_zelftest_tabel_klaar();
                return rest_ensure_response( array( 'let_op' => $uitkomst['let_op'], 'stand' => sfp_zelftest_stand() ) );
            },
        )
    );

    register_rest_route(
        'sfp/v1',
        '/zelftest/' . $test . '/pakket/terug',
        array(
            'methods'             => 'POST',
            'permission_callback' => $mag,
            'callback'            => function ( WP_REST_Request $verzoek ) {
                $uitkomst = sfp_zelftest_pakket_terug( (string) $verzoek['test'] );
                return is_wp_error( $uitkomst ) ? $uitkomst : rest_ensure_response( sfp_zelftest_stand() );
            },
        )
    );

    register_rest_route(
        'sfp/v1',
        '/zelftest/' . $test . '/instellingen',
        array(
            'methods'             => 'POST',
            'permission_callback' => $mag,
            'callback'            => function ( WP_REST_Request $verzoek ) use ( $json ) {
                $naam = sfp_zelftest_naam( (string) $verzoek['test'] );
                if ( ! sfp_zelftest_pakket( $naam ) ) {
                    return new WP_Error( 'sfp_zelftest_test', 'Lees eerst een pakket in voor deze test.', array( 'status' => 404 ) );
                }
                $uitkomst = sfp_zelftest_instellingen_bewaar( $naam, $json( $verzoek ) );
                return is_wp_error( $uitkomst ) ? $uitkomst : rest_ensure_response( sfp_zelftest_stand() );
            },
        )
    );

    register_rest_route(
        'sfp/v1',
        '/zelftest/cloudflare',
        array(
            'methods'             => 'POST',
            'permission_callback' => $mag,
            'callback'            => function ( WP_REST_Request $verzoek ) use ( $json ) {
                $uitkomst = sfp_zelftest_cloudflare_bewaar( $json( $verzoek ) );
                return is_wp_error( $uitkomst ) ? $uitkomst : rest_ensure_response( sfp_zelftest_stand() );
            },
        )
    );

    register_rest_route(
        'sfp/v1',
        '/zelftest/' . $test . '/proef',
        array(
            'methods'             => 'POST',
            'permission_callback' => $mag,
            'callback'            => 'sfp_zelftest_rest_proef',
        )
    );

    register_rest_route(
        'sfp/v1',
        '/zelftest/wissen',
        array(
            'methods'             => 'POST',
            'permission_callback' => $mag,
            'callback'            => function ( WP_REST_Request $verzoek ) use ( $json ) {
                $g     = $json( $verzoek );
                $email = isset( $g['email'] ) ? (string) $g['email'] : '';
                if ( ! is_email( $email ) ) {
                    return new WP_Error( 'sfp_zelftest_email', 'Geen geldig e-mailadres.', array( 'status' => 400 ) );
                }
                return rest_ensure_response( array( 'gewist' => sfp_zelftest_wis_email( $email ), 'stand' => sfp_zelftest_stand() ) );
            },
        )
    );
}

/**
 * Proef: pdf voor opgegeven antwoorden. Slaat niets op en
 * stuurt niets naar het CRM. Antwoord als base64, of met ?bestand=1 het
 * bestand zelf.
 *
 * Invoer: naam, lengte (long|short), antwoorden (object of de korte vorm
 * "1b 2b ... 39a,h 40a"), code.
 *
 * @param  WP_REST_Request $verzoek Verzoek.
 * @return WP_REST_Response|WP_Error
 */
function sfp_zelftest_rest_proef( WP_REST_Request $verzoek ) {
    $pakket = sfp_zelftest_pakket( (string) $verzoek['test'] );
    if ( ! $pakket ) {
        return new WP_Error( 'sfp_zelftest_test', 'Geen pakket voor deze test.', array( 'status' => 404 ) );
    }
    $g      = (array) $verzoek->get_json_params();
    $lengte = isset( $g['lengte'] ) && 'short' === $g['lengte'] ? 'short' : 'long';
    // Proef in een andere taal van het pakket (sinds 3.11.0).
    sfp_zelftest_taal_context( isset( $g['taal'] ) ? (string) $g['taal'] : '' );
    $ruw    = isset( $g['antwoorden'] ) ? $g['antwoorden'] : array();
    if ( is_string( $ruw ) ) {
        $tekst = $ruw;
        $ruw   = array();
        foreach ( preg_split( '/\s+/', trim( $tekst ) ) as $stuk ) {
            if ( preg_match( '/^(\d+)([a-i](?:,[a-i])*)$/', $stuk, $m ) ) {
                $ruw[ (int) $m[1] ] = false !== strpos( $m[2], ',' ) ? explode( ',', $m[2] ) : $m[2];
            }
        }
    }
    // Vragen die niet in deze lengte zitten, vallen weg; meer-antwoordvragen altijd als lijst.
    $vragen  = sfp_zelftest_vragen( $pakket );
    if ( ! isset( $pakket['manifest']['lengtes'][ $lengte ]['vragen'] ) ) {
        return new WP_Error( 'sfp_zelftest_lengte', 'Deze test heeft geen ' . ( 'short' === $lengte ? 'korte' : 'lange' ) . ' versie.', array( 'status' => 400 ) );
    }
    $toegest = array_map( 'intval', (array) $pakket['manifest']['lengtes'][ $lengte ]['vragen'] );
    $schoon  = array();
    foreach ( (array) $ruw as $nr => $w ) {
        if ( in_array( (int) $nr, $toegest, true ) ) {
            $schoon[ (int) $nr ] = ( isset( $vragen[ (int) $nr ] ) && $vragen[ (int) $nr ]['meerdere'] ) ? array_values( (array) $w ) : $w;
        }
    }
    $antwoorden = sfp_zelftest_antwoorden_schoon( $pakket, $lengte, $schoon );
    if ( null === $antwoorden ) {
        return new WP_Error( 'sfp_zelftest_antwoorden', 'De antwoorden passen niet bij deze lengte van de test.', array( 'status' => 400 ) );
    }
    $gegevens = array(
        'naam'       => isset( $g['naam'] ) ? sanitize_text_field( (string) $g['naam'] ) : 'Proef',
        'antwoorden' => $antwoorden,
        'lengte'     => $lengte,
        'code'       => isset( $g['code'] ) ? sanitize_text_field( (string) $g['code'] ) : $pakket['manifest']['code_voorvoegsel'] . '-PROE-F000-0000',
        'datum'      => sfp_zelftest_datum( 'j F Y', time(), $pakket ),
    );
    $html     = sfp_zelftest_blad_html( $pakket, $gegevens );
    $voet     = sfp_zelftest_blad_voet( $pakket, $gegevens );
    if ( is_wp_error( $html ) ) {
        return $html;
    }
    if ( ! empty( $g['alleen_html'] ) ) {
        return rest_ensure_response( array( 'html' => $html, 'voet' => $voet ) );
    }
    $inhoud = sfp_zelftest_maak_pdf( $pakket, $html, true, $voet );
    if ( is_wp_error( $inhoud ) ) {
        // Geen 502: Cloudflare vervangt een 502 van de site door een eigen
        // foutpagina, en dan is de reden weg.
        $inhoud->add_data( array( 'status' => 424 ) );
        return $inhoud;
    }
    return rest_ensure_response(
        array(
            'type'   => 'application/pdf',
            'base64' => base64_encode( $inhoud ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- binair bestand in JSON.
        )
    );
}
