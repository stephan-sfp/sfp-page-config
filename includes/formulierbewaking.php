<?php
/**
 * SFP Page Config - Onderdeel Formulierbewaking (3.24.0)
 *
 * Neemt twee netwerksnippets over, met hetzelfde gedrag:
 *
 *   "Formulieren: logs uitlezen voor de cloudcontrole" (SFP Logendpoint v1.0)
 *     GET /wp-json/sfp/v1/logs, alleen lezen.
 *       bron   = alles (standaard) | formulierfouten | vangnet | monitor
 *       regels = 1 tot 500, standaard 50
 *     Rechten: ingelogd met een account dat update_plugins heeft (claude-api).
 *     Zelfde adres, zelfde parameters, zelfde antwoordvorm (versie 1.0).
 *
 *   "Formulieren: dagelijkse controle" (SFP formulierbewaking 13-09-2026)
 *     A. De dagelijkse testinzending van het monitoradres: SureContact en de
 *        foutenteller van SureForms overslaan (prioriteit 1), de inzending na
 *        de meldingsmail verwijderen en loggen in sfp_formmonitor_log
 *        (prioriteit 99, laatste 30).
 *     B. Een mail naar info@<domein> zodra een foutenteller van SureForms bij
 *        een echte bezoeker stijgt, hoogstens één per uur.
 *
 * Dubbel draaien kan niet: zolang een van de snippets actief is (de functies
 * sfp_logs_v1_routes en sfp_bewaking_monitor_afschermen bestaan), doet dit
 * onderdeel dat deel niet en registreert het de route niet. Gaat het snippet
 * uit, dan neemt de plugin het bij het volgende verzoek over, zonder dat het
 * adres of de antwoordvorm verandert.
 *
 * De bron vangnet (optie sfp_srfm_nojs_log) blijft in de route zolang het
 * no-JS vangnet draait. De teksten van de foutmail blijven letterlijk gelijk
 * aan het snippet: de dagelijkse controle herkent de mail aan het onderwerp.
 *
 * @package SFP_Page_Config
 * @since   3.24.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Het adres van de dagelijkse testinzending.
 *
 * @return string
 */
function sfp_page_config_formulierbewaking_monitoradres() {
    return (string) apply_filters( 'sfp_page_config_formulierbewaking_monitoradres', 'formmonitor@schoolforprofessionals.com' );
}

/**
 * Staat het onderdeel aan op deze site?
 *
 * @return bool
 */
function sfp_page_config_formulierbewaking_aan() {
    return ! function_exists( 'sfp_page_config_onderdeel_aan' ) || sfp_page_config_onderdeel_aan( 'formulierbewaking' );
}

/**
 * Draait het snippet met de logroute nog?
 *
 * @return bool
 */
function sfp_page_config_formulierbewaking_route_snippet() {
    return function_exists( 'sfp_logs_v1_routes' );
}

/**
 * Draait het snippet met de dagelijkse controle nog?
 *
 * @return bool
 */
function sfp_page_config_formulierbewaking_controle_snippet() {
    return function_exists( 'sfp_bewaking_monitor_afschermen' );
}

/**
 * Wie draait wat: plugin, snippet of niemand. Voor SFP Beheer en de REST-stand.
 *
 * @return array{route: string, controle: string}
 */
function sfp_page_config_formulierbewaking_stand() {
    $aan = sfp_page_config_formulierbewaking_aan();
    return array(
        'route'    => sfp_page_config_formulierbewaking_route_snippet() ? 'snippet' : ( $aan ? 'plugin' : 'uit' ),
        'controle' => sfp_page_config_formulierbewaking_controle_snippet() ? 'snippet' : ( $aan ? 'plugin' : 'uit' ),
    );
}

/* =========================================================================
 * Logroute
 * ====================================================================== */

// Prioriteit 20: het snippet registreert op 10. Bestaat zijn functie, dan
// heeft het de route en slaat de plugin hem over.
add_action( 'rest_api_init', 'sfp_page_config_formulierbewaking_routes', 20 );

/**
 * Registreer GET /sfp/v1/logs, alleen als het snippet het niet al doet.
 */
function sfp_page_config_formulierbewaking_routes() {
    if ( sfp_page_config_formulierbewaking_route_snippet() || ! sfp_page_config_formulierbewaking_aan() ) {
        return;
    }
    register_rest_route(
        'sfp/v1',
        '/logs',
        array(
            'methods'             => 'GET',
            'callback'            => 'sfp_page_config_formulierbewaking_lezen',
            'permission_callback' => 'sfp_page_config_formulierbewaking_mag',
            'args'                => array(
                'bron'   => array(
                    'default'           => 'alles',
                    'validate_callback' => 'sfp_page_config_formulierbewaking_bron_geldig',
                ),
                'regels' => array(
                    'default'           => 50,
                    'sanitize_callback' => 'absint',
                ),
            ),
        )
    );
}

/**
 * Geldige bron.
 *
 * @param  mixed $waarde Waarde.
 * @return bool
 */
function sfp_page_config_formulierbewaking_bron_geldig( $waarde ) {
    return in_array( (string) $waarde, array( 'alles', 'formulierfouten', 'vangnet', 'monitor' ), true );
}

/**
 * Alleen een ingelogd account met update_plugins.
 *
 * @return bool
 */
function sfp_page_config_formulierbewaking_mag() {
    return is_user_logged_in() && current_user_can( 'update_plugins' );
}

/**
 * Het antwoord van de logroute, gelijk aan SFP Logendpoint v1.0.
 *
 * @param  WP_REST_Request $request Verzoek.
 * @return WP_REST_Response
 */
function sfp_page_config_formulierbewaking_lezen( $request ) {
    $bron   = sanitize_key( (string) $request->get_param( 'bron' ) );
    $regels = (int) $request->get_param( 'regels' );
    $regels = max( 1, min( 500, $regels ) );

    $uit = array(
        'versie' => '1.0',
        'site'   => get_bloginfo( 'name' ),
        'host'   => wp_parse_url( home_url(), PHP_URL_HOST ),
        // Bewust in UTC, met de letters UTC erachter: zo leest de cloudcontrole het.
        'tijd'   => gmdate( 'Y-m-d H:i:s' ) . ' UTC',
    );

    if ( 'alles' === $bron || 'formulierfouten' === $bron ) {
        $uit['formulierfouten'] = sfp_page_config_formulierbewaking_sureforms( $regels );
    }

    // Het no-JS vangnet: blijft zolang dat vangnet draait; de evaluatie leest het.
    if ( 'alles' === $bron || 'vangnet' === $bron ) {
        $log            = get_option( 'sfp_srfm_nojs_log', array() );
        $uit['vangnet'] = array(
            'totaal' => is_array( $log ) ? count( $log ) : 0,
            'regels' => is_array( $log ) ? array_slice( $log, 0, $regels ) : array(),
        );
    }

    if ( 'alles' === $bron || 'monitor' === $bron ) {
        $log            = get_option( 'sfp_formmonitor_log', array() );
        $uit['monitor'] = array(
            'totaal' => is_array( $log ) ? count( $log ) : 0,
            'regels' => is_array( $log ) ? array_slice( $log, 0, $regels ) : array(),
        );
    }

    return rest_ensure_response( $uit );
}

/**
 * Het foutenlog van SureForms (Client_Logger): tellers en de laatste regels.
 *
 * @param  int $regels Aantal regels.
 * @return array
 */
function sfp_page_config_formulierbewaking_sureforms( $regels ) {
    $data = array(
        'logs_aan'  => null,
        'tellers'   => array(),
        'bestand'   => '',
        'omvang'    => 0,
        'regels'    => array(),
        'opmerking' => '',
    );

    if ( ! class_exists( '\SRFM\Inc\Client_Logger' ) ) {
        $data['opmerking'] = 'SureForms Client_Logger niet aanwezig op deze site.';
        return $data;
    }

    $data['logs_aan'] = (bool) \SRFM\Inc\Client_Logger::is_enabled();
    $tellers          = \SRFM\Inc\Client_Logger::get_failures();
    $data['tellers']  = is_array( $tellers ) ? $tellers : array();

    $pad = \SRFM\Inc\Client_Logger::get_log_path( false );

    if ( '' === $pad || ! file_exists( $pad ) ) {
        $data['opmerking'] = 'Nog geen logbestand aangemaakt.';
        return $data;
    }

    $data['bestand'] = basename( $pad );
    $data['omvang']  = (int) filesize( $pad );

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- WP_Filesystem kan om inloggegevens vragen op een REST-verzoek; dit is alleen lezen.
    $inhoud = file_get_contents( $pad );

    if ( ! is_string( $inhoud ) || '' === trim( $inhoud ) ) {
        $data['opmerking'] = 'Logbestand is leeg.';
        return $data;
    }

    $lijnen = array_values( array_filter( array_map( 'trim', explode( "\n", $inhoud ) ) ) );
    $lijnen = array_slice( $lijnen, -$regels );

    foreach ( array_reverse( $lijnen ) as $lijn ) {
        $rij              = json_decode( $lijn, true );
        $data['regels'][] = is_array( $rij ) ? $rij : array( 'ruw' => $lijn );
    }

    return $data;
}

/* =========================================================================
 * Dagelijkse controle: de testinzending en de foutmail
 * ====================================================================== */

add_action( 'srfm_form_submit', 'sfp_page_config_formulierbewaking_afschermen', 1 );
add_action( 'srfm_form_submit', 'sfp_page_config_formulierbewaking_opruimen', 99 );
add_action( 'update_option_srfm_client_log_failures', 'sfp_page_config_formulierbewaking_fout_melden', 10, 2 );

/**
 * Doet de plugin de dagelijkse controle? Niet als het onderdeel uit staat of
 * het snippet nog draait.
 *
 * @return bool
 */
function sfp_page_config_formulierbewaking_controle_hier() {
    return sfp_page_config_formulierbewaking_aan() && ! sfp_page_config_formulierbewaking_controle_snippet();
}

/**
 * Is dit de testinzending van het monitoradres?
 *
 * @param  mixed $respons Antwoord van SureForms bij srfm_form_submit.
 * @return bool
 */
function sfp_page_config_formulierbewaking_is_monitor( $respons ) {
    if ( ! is_array( $respons ) || empty( $respons['entry_id'] ) ) {
        return false;
    }
    $inhoud = isset( $respons['data'] ) ? wp_json_encode( $respons['data'] ) : '';
    if ( ! is_string( $inhoud ) ) {
        return false;
    }
    $adres = sfp_page_config_formulierbewaking_monitoradres();
    return '' !== $adres && false !== stripos( $inhoud, $adres );
}

/**
 * A1 en A2: SureContact en de foutenteller van SureForms overslaan, alleen
 * voor dit ene verzoek met het monitoradres.
 *
 * @param mixed $respons Antwoord van SureForms.
 */
function sfp_page_config_formulierbewaking_afschermen( $respons ) {
    if ( ! sfp_page_config_formulierbewaking_controle_hier() || ! sfp_page_config_formulierbewaking_is_monitor( $respons ) ) {
        return;
    }

    global $wp_filter;
    if ( empty( $wp_filter['srfm_form_submit'] ) || ! is_object( $wp_filter['srfm_form_submit'] ) ) {
        return;
    }

    $weg = array();
    foreach ( $wp_filter['srfm_form_submit']->callbacks as $prio => $callbacks ) {
        foreach ( $callbacks as $cb ) {
            if ( ! is_array( $cb['function'] ) || ! isset( $cb['function'][0], $cb['function'][1] ) ) {
                continue;
            }
            $doel = $cb['function'][0];
            $naam = is_object( $doel ) ? get_class( $doel ) : (string) $doel;

            $is_crm    = false !== stripos( $naam, 'SureContact' );
            $is_teller = false !== stripos( $naam, 'Client_Logger' );

            if ( ! $is_crm && ! $is_teller ) {
                continue;
            }

            remove_action( 'srfm_form_submit', $cb['function'], $prio );
            $weg[] = $naam . '::' . $cb['function'][1];
        }
    }

    if ( $weg ) {
        $GLOBALS['sfp_bewaking_afgeschermd'] = implode( ', ', $weg );
    }
}

/**
 * A3: de testinzending verwijderen na de meldingsmail, en loggen.
 *
 * @param mixed $respons Antwoord van SureForms.
 */
function sfp_page_config_formulierbewaking_opruimen( $respons ) {
    if ( ! sfp_page_config_formulierbewaking_controle_hier() || ! sfp_page_config_formulierbewaking_is_monitor( $respons ) ) {
        return;
    }

    $entry_id = absint( $respons['entry_id'] );
    if ( ! $entry_id || ! class_exists( '\SRFM\Inc\Database\Tables\Entries' ) ) {
        return;
    }

    $verwijderd = \SRFM\Inc\Database\Tables\Entries::delete( $entry_id );

    $log = get_option( 'sfp_formmonitor_log', array() );
    if ( ! is_array( $log ) ) {
        $log = array();
    }
    array_unshift(
        $log,
        array(
            'tijd'        => wp_date( 'Y-m-d H:i:s' ),
            'entry_id'    => $entry_id,
            'form_id'     => isset( $respons['form_id'] ) ? $respons['form_id'] : '',
            'opgeruimd'   => $verwijderd ? 'ja' : 'MISLUKT',
            'afgeschermd' => isset( $GLOBALS['sfp_bewaking_afgeschermd'] )
                ? $GLOBALS['sfp_bewaking_afgeschermd']
                : 'NIETS GEVONDEN',
        )
    );
    update_option( 'sfp_formmonitor_log', array_slice( $log, 0, 30 ), false );
}

/**
 * B: een mail zodra een foutenteller van SureForms stijgt. Hoogstens één per
 * uur (transient sfp_bewaking_gemeld, gedeeld met het snippet).
 *
 * @param mixed $oud   Oude waarde van srfm_client_log_failures.
 * @param mixed $nieuw Nieuwe waarde.
 */
function sfp_page_config_formulierbewaking_fout_melden( $oud, $nieuw ) {
    if ( ! sfp_page_config_formulierbewaking_controle_hier() || ! is_array( $nieuw ) ) {
        return;
    }
    $oud = is_array( $oud ) ? $oud : array();

    $uitleg = array(
        'submission'   => 'Het versturen van het formulier mislukte in de browser van de bezoeker. Het bericht is mogelijk niet aangekomen.',
        'notification' => 'De meldingsmail van een inzending kon niet worden verstuurd.',
        'integration'  => 'Het doorzetten van een inzending naar een gekoppelde dienst mislukte.',
    );

    $gestegen = array();
    foreach ( $nieuw as $categorie => $gegevens ) {
        if ( ! is_array( $gegevens ) ) {
            continue;
        }
        $nu  = isset( $gegevens['count'] ) ? (int) $gegevens['count'] : 0;
        $was = isset( $oud[ $categorie ]['count'] ) ? (int) $oud[ $categorie ]['count'] : 0;
        if ( $nu <= $was ) {
            continue;
        }
        $gestegen[] = array(
            'categorie' => $categorie,
            'aantal'    => $nu,
            'formulier' => isset( $gegevens['form_title'] ) ? $gegevens['form_title'] : '',
            'form_id'   => isset( $gegevens['form_id'] ) ? $gegevens['form_id'] : 0,
            'uitleg'    => isset( $uitleg[ $categorie ] ) ? $uitleg[ $categorie ] : $categorie,
        );
    }

    if ( ! $gestegen || get_transient( 'sfp_bewaking_gemeld' ) ) {
        return;
    }
    set_transient( 'sfp_bewaking_gemeld', 1, HOUR_IN_SECONDS );

    $site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
    $host = preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

    $regels   = array();
    $regels[] = 'Een bezoeker liep vast op een formulier van ' . $site . '.';
    $regels[] = '';
    foreach ( $gestegen as $g ) {
        $regels[] = $g['uitleg'];
        $regels[] = 'Formulier: ' . ( $g['formulier'] ? $g['formulier'] : 'onbekend' ) . ' (ID ' . $g['form_id'] . ')';
        $regels[] = 'Totaal geteld in deze categorie: ' . $g['aantal'];
        $regels[] = '';
    }
    $regels[] = 'Het volledige rapport staat hier:';
    $regels[] = admin_url( 'admin.php?page=sureforms_form_settings&tab=general-settings' );
    $regels[] = 'Klik op die pagina op de knop "Log downloaden".';
    $regels[] = 'Deze melding komt hoogstens een keer per uur, ook als er meer fouten zijn.';
    $regels[] = 'Tijd: ' . wp_date( 'Y-m-d H:i:s' );

    wp_mail(
        'info@' . $host,
        '[Formulierfout ' . $site . '] bezoeker liep vast op een formulier',
        implode( "\n", $regels ),
        array( 'Content-Type: text/plain; charset=UTF-8' )
    );
}
