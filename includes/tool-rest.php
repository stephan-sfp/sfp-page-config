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
    if ( function_exists( 'sfp_zelftest_ip' ) ) {
        $ip = (string) sfp_zelftest_ip();
    } else {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    }
    $bin = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ? @inet_pton( $ip ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
    if ( false !== $bin && 16 === strlen( $bin ) ) {
        return inet_ntop( substr( $bin, 0, 8 ) . str_repeat( "\0", 8 ) ) . '/64';
    }
    return $ip;
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
    if ( ! is_array( $antwoorden ) ) {
        return new WP_REST_Response( array( 'ok' => false, 'message' => 'Geen antwoorden.' ), 400 );
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
    if ( ! sfp_page_config_tool_limiet( 'mail-ip-' . sfp_page_config_tool_ip(), 20, HOUR_IN_SECONDS )
        || ! sfp_page_config_tool_limiet( 'mail-adres-' . sfp_page_config_tool_adres_sleutel( $email ), 5, DAY_IN_SECONDS )
        || ! sfp_page_config_tool_limiet( 'mail-site', 100, HOUR_IN_SECONDS ) ) {
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
    // Van gedachten veranderd: de vorige stem eraf, niet onder nul.
    if ( in_array( $vorige, array( 'omhoog', 'omlaag' ), true ) && $vorige !== $stem ) {
        $tellingen[ $slug ][ $vorige ] = max( 0, (int) $tellingen[ $slug ][ $vorige ] - 1 );
    }
    update_option( 'sfp_page_config_tool_feedback', $tellingen, false );
    return new WP_REST_Response( array( 'ok' => true ), 200 );
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
