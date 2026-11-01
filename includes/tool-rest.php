<?php
/**
 * SFP Page Config - Tools: routes
 *
 *   POST /sfp/v1/tool/mail        mail naar jezelf (openbaar, met limieten)
 *   POST /sfp/v1/tool/duim        duimpjes en tekstfeedback (openbaar)
 *   POST /sfp/v1/tool/controle    beschrijving controleren (edit_posts)
 *   GET  /sfp/v1/tool/feedback    tellingen en tekstfeedback (manage_options)
 *
 * De mailroute rekent de uitslag zelf opnieuw uit de beschrijving en bouwt
 * de mail als tekst; de browser levert alleen de antwoorden. De tellingen
 * van de duimpjes komen bij de eerste keer uit de oude snippets
 * (<sitecode>_tool_feedback_counts).
 *
 * @package SFP_Page_Config
 * @since   3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', 'sfp_page_config_tool_routes' );

/**
 * De routes.
 */
function sfp_page_config_tool_routes() {
    register_rest_route(
        'sfp/v1',
        '/tool/mail',
        array(
            'methods'             => 'POST',
            'callback'            => 'sfp_page_config_tool_rest_mail',
            'permission_callback' => '__return_true',
        )
    );
    register_rest_route(
        'sfp/v1',
        '/tool/duim',
        array(
            'methods'             => 'POST',
            'callback'            => 'sfp_page_config_tool_rest_duim',
            'permission_callback' => '__return_true',
        )
    );
    register_rest_route(
        'sfp/v1',
        '/tool/controle',
        array(
            'methods'             => 'POST',
            'callback'            => 'sfp_page_config_tool_rest_controle_route',
            'permission_callback' => function () {
                return current_user_can( 'edit_posts' );
            },
        )
    );
    register_rest_route(
        'sfp/v1',
        '/tool/feedback',
        array(
            'methods'             => 'GET',
            'callback'            => 'sfp_page_config_tool_rest_feedback',
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        )
    );
}

/**
 * Het IP van de bezoeker om te tellen. CF-Connecting-IP alleen als het
 * verzoek echt van Cloudflare komt (dezelfde controle als de zelftest);
 * anders is de kop na te maken. Een IPv6-adres telt per /64, het blok dat
 * één aansluiting meestal krijgt.
 *
 * @return string
 */
function sfp_page_config_tool_ip() {
    return sfp_page_config_bezoeker_ip();
}

/**
 * Tel een limiet op en zeg of hij nog past. Een vast venster: de teller
 * begint opnieuw als het venster om is, ook als er intussen verzoeken
 * kwamen.
 *
 * @param  string $sleutel Sleutel (zonder voorvoegsel).
 * @param  int    $max     Maximum.
 * @param  int    $venster Seconden.
 * @return bool            True als het nog mag.
 */
function sfp_page_config_tool_limiet( $sleutel, $max, $venster ) {
    $sleutel = 'sfp_tool_rl_' . substr( md5( $sleutel . '|' . wp_salt( 'nonce' ) ), 0, 24 );
    $nu      = time();
    $stand   = get_transient( $sleutel );
    if ( ! is_array( $stand ) || ! isset( $stand['n'], $stand['tot'] ) || (int) $stand['tot'] <= $nu ) {
        $stand = array( 'n' => 0, 'tot' => $nu + $venster );
    }
    if ( (int) $stand['n'] >= $max ) {
        return false;
    }
    $stand['n'] = (int) $stand['n'] + 1;
    set_transient( $sleutel, $stand, max( 1, (int) $stand['tot'] - $nu ) );
    return true;
}

/**
 * Grootste body van een openbaar toolverzoek (mail, duimpje), in bytes.
 */
if ( ! defined( 'SFP_PAGE_CONFIG_TOOL_MAX_BODY' ) ) {
    define( 'SFP_PAGE_CONFIG_TOOL_MAX_BODY', 64 * 1024 );
}

/**
 * Limieten per adres en per site voor de toolmail (de limiet per IP staat
 * al vooraan in de route): 5 per adres per dag, per site 30 per uur en 200
 * per dag.
 *
 * @param  string $email Adres.
 * @return bool          True als de mail nog mag.
 */
function sfp_page_config_tool_mail_plafond( $email ) {
    return sfp_page_config_tool_limiet( 'mail-adres-' . sfp_page_config_tool_adres_sleutel( $email ), 5, DAY_IN_SECONDS )
        && sfp_page_config_tool_limiet( 'mail-site', 30, HOUR_IN_SECONDS )
        && sfp_page_config_tool_limiet( 'mail-site-dag', 200, DAY_IN_SECONDS );
}

/**
 * Een mailadres om te tellen: kleine letters en zonder +label, zodat
 * naam+1@ en naam+2@ als hetzelfde adres tellen.
 *
 * @param  string $email Adres.
 * @return string
 */
function sfp_page_config_tool_adres_sleutel( $email ) {
    $email = strtolower( trim( (string) $email ) );
    $at    = strrpos( $email, '@' );
    if ( false === $at ) {
        return $email;
    }
    $naam = substr( $email, 0, $at );
    $plus = strpos( $naam, '+' );
    if ( false !== $plus ) {
        $naam = substr( $naam, 0, $plus );
    }
    return $naam . substr( $email, $at );
}

/**
 * De titel van een bericht als platte tekst, zonder &#8217; en &amp;.
 *
 * @param  WP_Post $post Bericht.
 * @return string
 */
function sfp_page_config_tool_platte_titel( $post ) {
    return html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}

/**
 * De deellink naar het resultaat, op de server gebouwd uit de antwoorden
 * (zelfde vorm als Tool.deellink in assets/tool/motor.js).
 *
 * @param  WP_Post $post       Tool.
 * @param  array   $antwoorden id => waarde.
 * @return string
 */
function sfp_page_config_tool_deellink( $post, array $antwoorden ) {
    $json = wp_json_encode( array( 'id' => (int) $post->ID, 'a' => (object) $antwoorden ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    $code = rtrim( strtr( base64_encode( (string) $json ), '+/', '-_' ), '=' );
    return add_query_arg( 't', $code, get_permalink( $post ) );
}

/**
 * Het adres waar tekstfeedback heen gaat: de instelling, anders het
 * beheerdersadres van de site.
 *
 * @return string
 */
function sfp_page_config_tool_feedback_adres() {
    $adres = function_exists( 'sfp_page_config_get_setting' ) ? (string) sfp_page_config_get_setting( 'tool_feedback_mail', '' ) : '';
    if ( '' === $adres || ! is_email( $adres ) ) {
        $adres = (string) get_option( 'admin_email' );
    }
    return $adres;
}

/* =========================================================================
 * Mail naar jezelf
 * ====================================================================== */

/**
 * @param  WP_REST_Request $verzoek Verzoek.
 * @return WP_REST_Response
 */
function sfp_page_config_tool_rest_mail( WP_REST_Request $verzoek ) {
    $t = 'sfp_page_config_artikel_tekst';
    if ( '' !== trim( (string) $verzoek->get_param( 'website' ) ) ) {
        // Honeypot: doen alsof het lukte.
        return new WP_REST_Response( array( 'ok' => true ), 200 );
    }
    // Eerst de goedkope grenzen: de omvang van het verzoek en de limiet per
    // IP, voordat er iets met de antwoorden gebeurt.
    if ( strlen( (string) $verzoek->get_body() ) > SFP_PAGE_CONFIG_TOOL_MAX_BODY ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => 'Verzoek te groot.' ), 413 );
    }
    if ( ! sfp_page_config_tool_limiet( 'mail-ip-' . sfp_page_config_tool_ip(), 20, HOUR_IN_SECONDS ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => 'Te veel verzoeken. Probeer het later opnieuw.' ), 429 );
    }
    if ( ! sfp_page_config_tool_aan() ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => 'Tools staan uit.' ), 404 );
    }
    $post = get_post( (int) $verzoek->get_param( 'tool' ) );
    if ( ! $post || 'tool' !== $post->post_type || 'publish' !== $post->post_status || post_password_required( $post ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => 'Onbekende tool.' ), 404 );
    }
    $b = sfp_page_config_tool_beschrijving( $post );
    if ( ! $b || ! $b['acties']['mail'] ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => 'Mail staat bij deze tool uit.' ), 400 );
    }
    $email = sanitize_email( (string) $verzoek->get_param( 'email' ) );
    if ( ! is_email( $email ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => $t( 'tool_mail_fout' ) ), 400 );
    }
    $antwoorden = $verzoek->get_param( 'antwoorden' );
    if ( ! is_array( $antwoorden ) || count( $antwoorden ) > 300 ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => 'Geen antwoorden.' ), 400 );
    }
    if ( sfp_page_config_tool_op_generator( $b['soort'] ) ) {
        return sfp_page_config_generator_rest_mail( $post, $b, $email, $antwoorden );
    }
    if ( 'test' !== $b['soort'] ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => $t( 'tool_mail_fout' ) ), 400 );
    }
    // Alleen antwoorden op vragen van deze tool, op hun eigen id (Q1 blijft
    // Q1). Vrije tekst gaat niet mee: dan zou iedereen eigen tekst naar elk
    // adres kunnen laten mailen. De mail bevat zo alleen teksten van de site.
    $schoon = array();
    foreach ( $b['vragen'] as $v ) {
        if ( 'tekst' === $v['type'] || ! array_key_exists( $v['id'], $antwoorden ) ) {
            continue;
        }
        $w = sfp_page_config_tool_antwoord( $antwoorden[ $v['id'] ] );
        if ( null !== $w ) {
            $schoon[ $v['id'] ] = mb_substr( $w, 0, 200 );
        }
    }
    if ( ! sfp_page_config_tool_mail_plafond( $email ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => 'Te veel verzoeken. Probeer het later opnieuw.' ), 429 );
    }
    $r = sfp_page_config_tool_reken( $b, $schoon );
    if ( ! $r['compleet'] ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => sprintf( $t( 'tool_ontbreekt' ), count( $r['ontbreekt'] ) ) ), 400 );
    }
    $titel     = sfp_page_config_tool_platte_titel( $post );
    $onderwerp = '' !== $b['mail']['onderwerp'] ? sfp_page_config_tool_vul( $b['mail']['onderwerp'], $b, $r ) : $titel;
    $onderwerp = sanitize_text_field( $onderwerp );
    $eigen     = (string) wp_parse_url( home_url(), PHP_URL_HOST );
    $link      = sfp_page_config_tool_deellink( $post, $schoon );
    $body  = '' !== $b['mail']['intro'] ? sfp_page_config_tool_vul( $b['mail']['intro'], $b, $r ) . "\n\n" : '';
    $body .= sfp_page_config_tool_tekst_uitvoer( $b, $r, $titel );
    $body .= "\n" . sprintf( $t( 'tool_mail_slot' ), $link ) . "\n\n";
    $body .= sprintf( $t( 'tool_mail_afzender' ), $eigen ) . "\n";
    $body  = wp_strip_all_tags( wp_check_invalid_utf8( $body, true ) );
    $ok    = wp_mail( $email, $onderwerp, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
    if ( ! $ok ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => $t( 'tool_mail_fout' ) ), 500 );
    }
    return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/**
 * Mail naar jezelf bij een generator: de server bouwt het resultaat opnieuw
 * uit de antwoorden. Alleen keuzes en korte velden gaan mee (zie
 * sfp_page_config_generator_mail_antwoorden), zodat de mail geen lappen
 * tekst van de bezoeker bevat.
 *
 * @param  WP_Post $post       Tool.
 * @param  array   $b          Beschrijving.
 * @param  string  $email      Adres.
 * @param  array   $antwoorden Ruwe antwoorden.
 * @return WP_REST_Response
 */
function sfp_page_config_generator_rest_mail( $post, array $b, $email, array $antwoorden ) {
    $t = 'sfp_page_config_artikel_tekst';
    foreach ( $b['stappen'] as $stap ) {
        if ( sfp_page_config_generator_ontbreekt( $b, $antwoorden, $stap ) ) {
            return new WP_REST_Response( array( 'ok' => false, 'message' => sprintf( $t( 'tool_vul_in' ), $stap['titel'] ) ), 400 );
        }
    }
    if ( ! sfp_page_config_tool_mail_plafond( $email ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => 'Te veel verzoeken. Probeer het later opnieuw.' ), 429 );
    }
    $schoon    = sfp_page_config_generator_mail_antwoorden( $b, $antwoorden );
    $titel     = sfp_page_config_tool_platte_titel( $post );
    $onderwerp = '' !== $b['mail']['onderwerp'] ? sfp_page_config_generator_vul( $b['mail']['onderwerp'], $b, $schoon ) : $titel;
    $onderwerp = sanitize_text_field( $onderwerp );
    $eigen     = (string) wp_parse_url( home_url(), PHP_URL_HOST );
    $link      = sfp_page_config_tool_deellink( $post, $schoon );
    $body      = '' !== $b['mail']['intro'] ? sfp_page_config_generator_vul( $b['mail']['intro'], $b, $schoon ) . "\n\n" : '';
    $body     .= sfp_page_config_generator_tekst( sfp_page_config_generator_bouw( $b, $schoon ), $titel );
    $body     .= "\n" . sprintf( $t( 'tool_mail_slot' ), $link ) . "\n\n";
    $body     .= sprintf( $t( 'tool_mail_afzender' ), $eigen ) . "\n";
    $body      = wp_strip_all_tags( wp_check_invalid_utf8( $body, true ) );
    $ok        = wp_mail( $email, $onderwerp, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
    if ( ! $ok ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => $t( 'tool_mail_fout' ) ), 500 );
    }
    return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/* =========================================================================
 * Duimpjes
 * ====================================================================== */

/**
 * De tellingen, met de oude tellingen uit de snippets erbij als de plugin
 * ze nog niet heeft overgenomen.
 *
 * @return array<string, array{omhoog: int, omlaag: int}>
 */
function sfp_page_config_tool_feedback_tellingen() {
    $tellingen = get_option( 'sfp_page_config_tool_feedback', null );
    if ( is_array( $tellingen ) ) {
        return $tellingen;
    }
    $tellingen = array();
    foreach ( array( 'dps', 'sfp', 'cvd', 'dga', 'dst', 'slb', 'sws', 'fl' ) as $code ) {
        $oud = get_option( $code . '_tool_feedback_counts', null );
        if ( ! is_array( $oud ) ) {
            continue;
        }
        foreach ( $oud as $slug => $stand ) {
            $slug = sanitize_title( (string) $slug );
            if ( '' === $slug || ! is_array( $stand ) ) {
                continue;
            }
            if ( ! isset( $tellingen[ $slug ] ) ) {
                $tellingen[ $slug ] = array( 'omhoog' => 0, 'omlaag' => 0 );
            }
            $tellingen[ $slug ]['omhoog'] += isset( $stand['omhoog'] ) ? (int) $stand['omhoog'] : 0;
            $tellingen[ $slug ]['omlaag'] += isset( $stand['omlaag'] ) ? (int) $stand['omlaag'] : 0;
        }
    }
    update_option( 'sfp_page_config_tool_feedback', $tellingen, false );
    return $tellingen;
}

/**
 * @param  WP_REST_Request $verzoek Verzoek.
 * @return WP_REST_Response
 */
function sfp_page_config_tool_rest_duim( WP_REST_Request $verzoek ) {
    if ( '' !== trim( (string) $verzoek->get_param( 'website' ) ) ) {
        return new WP_REST_Response( array( 'ok' => true ), 200 );
    }
    if ( strlen( (string) $verzoek->get_body() ) > SFP_PAGE_CONFIG_TOOL_MAX_BODY ) {
        return new WP_REST_Response( array( 'ok' => false ), 413 );
    }
    if ( ! sfp_page_config_tool_aan() ) {
        return new WP_REST_Response( array( 'ok' => false ), 404 );
    }
    if ( ! sfp_page_config_tool_limiet( 'duim-ip-' . sfp_page_config_tool_ip(), 10, HOUR_IN_SECONDS ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => 'Te veel verzoeken. Probeer het later opnieuw.' ), 429 );
    }
    $slug   = sanitize_title( (string) $verzoek->get_param( 'tool' ) );
    $stem   = (string) $verzoek->get_param( 'stem' );
    $vorige = (string) $verzoek->get_param( 'vorige' );
    $tekst = mb_substr( sanitize_textarea_field( (string) $verzoek->get_param( 'tekst' ) ), 0, 600 );
    $email = sanitize_email( (string) $verzoek->get_param( 'email' ) );
    if ( ! is_email( $email ) ) {
        $email = '';
    }
    if ( '' === $slug || mb_strlen( $slug ) > 80 || ! in_array( $stem, array( 'omhoog', 'omlaag' ), true ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => 'Ongeldige aanvraag.' ), 400 );
    }
    // Alleen een gepubliceerde tool met duimpjes aan; anders vult elke
    // verzonnen naam de tellingen.
    $tool = get_page_by_path( $slug, OBJECT, 'tool' );
    $b    = $tool && 'publish' === $tool->post_status ? sfp_page_config_tool_beschrijving( $tool ) : null;
    if ( ! $b || empty( $b['acties']['duimpjes'] ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => 'Onbekende tool.' ), 404 );
    }
    if ( '' !== $tekst ) {
        $log = get_option( 'sfp_page_config_tool_feedback_log', array() );
        $log = is_array( $log ) ? $log : array();
        $log[] = array(
            'tijd'  => wp_date( 'Y-m-d H:i:s' ),
            'tool'  => $slug,
            'stem'  => $stem,
            'tekst' => $tekst,
            'email' => $email,
        );
        if ( count( $log ) > 500 ) {
            $log = array_slice( $log, -500 );
        }
        update_option( 'sfp_page_config_tool_feedback_log', $log, false );
        // Mail alleen binnen een plafond per site; de tekst staat hoe dan
        // ook in het logboek in SFP Beheer.
        if ( ! sfp_page_config_tool_limiet( 'duim-mail-site', 20, HOUR_IN_SECONDS ) ) {
            return new WP_REST_Response( array( 'ok' => true ), 200 );
        }
        $onderwerp = 'Tool-feedback: ' . $slug . ' (duim ' . $stem . ')';
        $body      = 'Tool: ' . $slug . "\nStem: duim " . $stem . "\nTijd: " . wp_date( 'Y-m-d H:i' ) . "\nSite: " . home_url() . "\n\nFeedback:\n" . $tekst . "\n" . ( '' !== $email ? "\nAntwoord-adres: " . $email . "\n" : '' );
        $headers   = '' !== $email ? array( 'Reply-To: ' . $email ) : array();
        wp_mail( sfp_page_config_tool_feedback_adres(), $onderwerp, $body, $headers );
        return new WP_REST_Response( array( 'ok' => true ), 200 );
    }
    $tellingen = sfp_page_config_tool_feedback_tellingen();
    if ( ! isset( $tellingen[ $slug ] ) || ! is_array( $tellingen[ $slug ] ) ) {
        $tellingen[ $slug ] = array( 'omhoog' => 0, 'omlaag' => 0 );
    }
    $tellingen[ $slug ][ $stem ] = (int) $tellingen[ $slug ][ $stem ] + 1;
    // Van gedachten veranderd: de vorige stem eraf, niet onder nul, en alleen
    // met het bewijs dat de server bij die vorige stem gaf (eenmalig). Zo
    // haalt niemand met alleen vorige=... stemmen weg die hij niet gaf.
    if ( in_array( $vorige, array( 'omhoog', 'omlaag' ), true ) && $vorige !== $stem
        && sfp_page_config_tool_duim_bewijs_geldig( $slug, $vorige, (string) $verzoek->get_param( 'bewijs' ) ) ) {
        $tellingen[ $slug ][ $vorige ] = max( 0, (int) $tellingen[ $slug ][ $vorige ] - 1 );
    }
    update_option( 'sfp_page_config_tool_feedback', $tellingen, false );
    return new WP_REST_Response( array( 'ok' => true, 'bewijs' => sfp_page_config_tool_duim_bewijs( $slug, $stem ) ), 200 );
}

/**
 * Bewijs van een stem: willekeurige code plus handtekening, 30 dagen geldig.
 *
 * @param  string $slug Tool.
 * @param  string $stem omhoog of omlaag.
 * @return string
 */
function sfp_page_config_tool_duim_bewijs( $slug, $stem ) {
    $code = wp_generate_password( 12, false ) . '.' . time();
    return $code . '.' . substr( hash_hmac( 'sha256', $slug . '|' . $stem . '|' . $code, wp_salt( 'nonce' ) ), 0, 20 );
}

/**
 * Is dit bewijs geldig voor die stem, en nog niet gebruikt? Gebruikte
 * bewijzen worden 30 dagen onthouden.
 *
 * @param  string $slug   Tool.
 * @param  string $stem   Vorige stem.
 * @param  string $bewijs Bewijs uit de browser.
 * @return bool
 */
function sfp_page_config_tool_duim_bewijs_geldig( $slug, $stem, $bewijs ) {
    if ( ! preg_match( '/^([A-Za-z0-9]{12})\.(\d{9,11})\.([0-9a-f]{20})$/', $bewijs, $m ) ) {
        return false;
    }
    if ( (int) $m[2] < time() - 30 * DAY_IN_SECONDS ) {
        return false;
    }
    $code = $m[1] . '.' . $m[2];
    if ( ! hash_equals( substr( hash_hmac( 'sha256', $slug . '|' . $stem . '|' . $code, wp_salt( 'nonce' ) ), 0, 20 ), $m[3] ) ) {
        return false;
    }
    $gebruikt = 'sfp_tool_rl_b' . substr( md5( $code ), 0, 20 );
    if ( false !== get_transient( $gebruikt ) ) {
        return false;
    }
    set_transient( $gebruikt, 1, 30 * DAY_IN_SECONDS );
    return true;
}

/**
 * Tellingen en de laatste tekstfeedback, voor SFP Beheer.
 *
 * @return WP_REST_Response
 */
function sfp_page_config_tool_rest_feedback() {
    $log = get_option( 'sfp_page_config_tool_feedback_log', array() );
    return new WP_REST_Response(
        array(
            'tellingen' => sfp_page_config_tool_feedback_tellingen(),
            'log'       => is_array( $log ) ? array_slice( array_reverse( $log ), 0, 50 ) : array(),
            'adres'     => sfp_page_config_tool_feedback_adres(),
        ),
        200
    );
}

/* =========================================================================
 * Controle voor het paneel
 * ====================================================================== */

/**
 * @param  WP_REST_Request $verzoek Verzoek met `tekst` (JSON).
 * @return WP_REST_Response
 */
function sfp_page_config_tool_rest_controle_route( WP_REST_Request $verzoek ) {
    $tekst    = (string) $verzoek->get_param( 'tekst' );
    $uitkomst = sfp_page_config_tool_controleer_tekst( $tekst );
    unset( $uitkomst['beschrijving'] );
    return new WP_REST_Response( $uitkomst, 200 );
}

/* =========================================================================
 * Privacy: tekstfeedback met e-mailadres
 * ====================================================================== */

add_filter( 'wp_privacy_personal_data_erasers', 'sfp_page_config_tool_privacy_wisser' );
add_filter( 'wp_privacy_personal_data_exporters', 'sfp_page_config_tool_privacy_exporteur' );
add_action( 'sfp_page_config_daily_check', 'sfp_page_config_tool_feedback_bewaartermijn' );

/**
 * Regels uit het feedbacklogboek met dit adres.
 *
 * @param  string $email Adres.
 * @return array<int, array> Index => regel.
 */
function sfp_page_config_tool_feedback_van( $email ) {
    $email = strtolower( trim( (string) $email ) );
    $log   = get_option( 'sfp_page_config_tool_feedback_log', array() );
    $uit   = array();
    if ( '' === $email || ! is_array( $log ) ) {
        return $uit;
    }
    foreach ( $log as $i => $regel ) {
        if ( is_array( $regel ) && isset( $regel['email'] ) && strtolower( (string) $regel['email'] ) === $email ) {
            $uit[ $i ] = $regel;
        }
    }
    return $uit;
}

/**
 * @param  array $wissers Wissers.
 * @return array
 */
function sfp_page_config_tool_privacy_wisser( $wissers ) {
    $wissers['sfp-tool-feedback'] = array(
        'eraser_friendly_name' => 'Toolfeedback (SFP Page Config)',
        'callback'             => function ( $email ) {
            $weg = sfp_page_config_tool_feedback_van( $email );
            if ( $weg ) {
                $log = array_values( array_diff_key( (array) get_option( 'sfp_page_config_tool_feedback_log', array() ), $weg ) );
                update_option( 'sfp_page_config_tool_feedback_log', $log, false );
            }
            return array( 'items_removed' => (bool) $weg, 'items_retained' => false, 'messages' => array(), 'done' => true );
        },
    );
    return $wissers;
}

/**
 * @param  array $exporteurs Exporteurs.
 * @return array
 */
function sfp_page_config_tool_privacy_exporteur( $exporteurs ) {
    $exporteurs['sfp-tool-feedback'] = array(
        'exporter_friendly_name' => 'Toolfeedback (SFP Page Config)',
        'callback'               => function ( $email ) {
            $data = array();
            foreach ( sfp_page_config_tool_feedback_van( $email ) as $i => $regel ) {
                $data[] = array(
                    'group_id'    => 'sfp-tool-feedback',
                    'group_label' => 'Toolfeedback',
                    'item_id'     => 'sfp-tool-feedback-' . (int) $i,
                    'data'        => array(
                        array( 'name' => 'Tijd', 'value' => (string) ( $regel['tijd'] ?? '' ) ),
                        array( 'name' => 'Tool', 'value' => (string) ( $regel['tool'] ?? '' ) ),
                        array( 'name' => 'Feedback', 'value' => (string) ( $regel['tekst'] ?? '' ) ),
                        array( 'name' => 'E-mailadres', 'value' => (string) ( $regel['email'] ?? '' ) ),
                    ),
                );
            }
            return array( 'data' => $data, 'done' => true );
        },
    );
    return $exporteurs;
}

/**
 * Bewaartermijn: tekstfeedback ouder dan 12 maanden gaat uit het logboek.
 */
function sfp_page_config_tool_feedback_bewaartermijn() {
    $log = get_option( 'sfp_page_config_tool_feedback_log', array() );
    if ( ! is_array( $log ) || ! $log ) {
        return;
    }
    $grens = wp_date( 'Y-m-d H:i:s', time() - YEAR_IN_SECONDS );
    $nieuw = array_values(
        array_filter(
            $log,
            function ( $regel ) use ( $grens ) {
                return is_array( $regel ) && isset( $regel['tijd'] ) && (string) $regel['tijd'] >= $grens;
            }
        )
    );
    if ( count( $nieuw ) !== count( $log ) ) {
        update_option( 'sfp_page_config_tool_feedback_log', $nieuw, false );
    }
}
