<?php
/**
 * SFP Page Config - Zelftest: routes voor de browser van de bezoeker
 *
 *   POST /wp-json/sfp/v1/zelftest/<test>/start
 *   POST /wp-json/sfp/v1/zelftest/<test>/afgerond
 *   POST /wp-json/sfp/v1/zelftest/<test>/opnieuw-sturen
 *
 * Openbaar, zonder nonce: de testpagina staat in de cache van WP Rocket en
 * een nonce verloopt. Bescherming: limiet per IP-adres en per e-mailadres,
 * een verborgen lokveld, en een sessie die alleen geldt voor de eigen rij.
 * De rekenregel draait in de browser; de server controleert de antwoorden
 * tegen het manifest en de CRM-velden tegen de toegestane lijst.
 *
 * @package SFP_Page_Config
 * @since   3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', 'sfp_zelftest_rest_bezoeker' );

/**
 * Registreer de routes voor de bezoeker.
 */
function sfp_zelftest_rest_bezoeker() {
    $routes = array(
        'start'          => 'sfp_zelftest_rest_start',
        'afgerond'       => 'sfp_zelftest_rest_afgerond',
        'opnieuw-sturen' => 'sfp_zelftest_rest_opnieuw',
    );
    foreach ( $routes as $pad => $callback ) {
        register_rest_route(
            'sfp/v1',
            '/zelftest/(?P<test>[a-z0-9][a-z0-9-]{0,39})/' . $pad,
            array(
                'methods'             => 'POST',
                'permission_callback' => '__return_true', // Openbaar; zie de controles in de callback.
                'callback'            => $callback,
            )
        );
    }
}

/* =========================================================================
 * Hulpfuncties
 * ====================================================================== */

/**
 * Het pakket van een test, als het onderdeel aan staat en de tabel er is.
 *
 * @param  WP_REST_Request $verzoek Verzoek.
 * @return array|WP_Error
 */
function sfp_zelftest_rest_pakket( WP_REST_Request $verzoek ) {
    $pakket = sfp_page_config_onderdeel_aan( 'zelftest' ) ? sfp_zelftest_pakket( (string) $verzoek['test'] ) : null;
    if ( ! $pakket || ! sfp_zelftest_tabel_klaar() ) {
        return new WP_Error( 'sfp_zelftest_niet_gevonden', 'Niet gevonden.', array( 'status' => 404 ) );
    }
    return $pakket;
}

/**
 * Een fout voor de bezoeker, met de tekst uit het pakket.
 *
 * @param  array  $pakket Pakket.
 * @param  string $code   Foutcode.
 * @param  string $tekst  Sleutel in inhoud.module.
 * @param  int    $status HTTP-status.
 * @return WP_Error
 */
function sfp_zelftest_rest_fout( array $pakket, $code, $tekst, $status ) {
    return new WP_Error( $code, sfp_zelftest_tekst( $pakket, 'module.' . $tekst ), array( 'status' => $status ) );
}

/**
 * Verhoog een teller en meld of de limiet bereikt is.
 *
 * @param  string $soort  Soort (start-ip, start-mail, ...).
 * @param  string $sleutel Waarde om te tellen (wordt gehasht).
 * @param  int    $max    Maximum per uur.
 * @return bool           true als het verzoek mag.
 */
function sfp_zelftest_limiet( $soort, $sleutel, $max ) {
    $naam  = 'sfp_pc_zt_' . substr( md5( $soort . '|' . $sleutel . '|' . wp_salt( 'nonce' ) ), 0, 20 );
    $aantal = (int) get_transient( $naam );
    if ( $aantal >= $max ) {
        return false;
    }
    set_transient( $naam, $aantal + 1, HOUR_IN_SECONDS );
    return true;
}

/**
 * Het IP-adres van de bezoeker. Achter Cloudflare het adres uit
 * CF-Connecting-IP, alleen om te tellen (wordt nooit opgeslagen).
 *
 * @return string
 */
function sfp_zelftest_ip() {
    $direct = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    $cf     = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) : '';
    // CF-Connecting-IP alleen als het verzoek echt van Cloudflare komt; anders is het na te maken.
    if ( filter_var( $cf, FILTER_VALIDATE_IP ) && sfp_zelftest_is_cloudflare( $direct ) ) {
        return $cf;
    }
    return (string) $direct;
}

/**
 * Komt dit adres uit een netblok van Cloudflare (https://www.cloudflare.com/ips/)?
 *
 * @param  string $ip Adres.
 * @return bool
 */
function sfp_zelftest_is_cloudflare( $ip ) {
    $bin = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- ongeldige invoer geeft false.
    if ( false === $bin ) {
        return false;
    }
    $blokken = (array) apply_filters(
        'sfp_zelftest_cloudflare_ips',
        array(
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
            '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
        )
    );
    foreach ( $blokken as $blok ) {
        list( $net, $lengte ) = array_pad( explode( '/', (string) $blok ), 2, '' );
        $netbin = @inet_pton( $net ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        if ( false === $netbin || strlen( $netbin ) !== strlen( $bin ) ) {
            continue;
        }
        $lengte = (int) $lengte;
        $bytes  = intdiv( $lengte, 8 );
        $rest   = $lengte % 8;
        if ( substr( $bin, 0, $bytes ) !== substr( $netbin, 0, $bytes ) ) {
            continue;
        }
        if ( 0 === $rest || ( ( ord( $bin[ $bytes ] ) ^ ord( $netbin[ $bytes ] ) ) & ( 0xFF << ( 8 - $rest ) ) & 0xFF ) === 0 ) {
            return true;
        }
    }
    return false;
}

/**
 * Een tekstveld uit het verzoek, schoongemaakt en ingekort.
 *
 * @param  array  $in    Invoer.
 * @param  string $naam  Sleutel.
 * @param  int    $max   Maximum aantal tekens.
 * @return string
 */
function sfp_zelftest_veld( array $in, $naam, $max ) {
    $w = isset( $in[ $naam ] ) && is_scalar( $in[ $naam ] ) ? sanitize_text_field( (string) $in[ $naam ] ) : '';
    return mb_substr( $w, 0, $max );
}

/**
 * De vragen van het pakket: nummer => aantal opties en of meer antwoorden mogen.
 *
 * @param  array $pakket Pakket.
 * @return array<int, array{opties: int, meerdere: bool}>
 */
function sfp_zelftest_vragen( array $pakket ) {
    $taal   = sfp_zelftest_taal( $pakket );
    $vragen = array();
    foreach ( (array) $pakket['inhoud']['vragen'] as $vraag ) {
        $nr = (int) $vraag['nr'];
        $o  = isset( $vraag[ $taal ]['o'] ) && is_array( $vraag[ $taal ]['o'] ) ? $vraag[ $taal ]['o'] : array();
        $vragen[ $nr ] = array( 'opties' => count( $o ), 'meerdere' => ! empty( $vraag['meerdere'] ) );
    }
    return $vragen;
}

/**
 * Controleer de antwoorden tegen het manifest: elke vraag van deze lengte
 * beantwoord, geen andere vragen, letters binnen de opties.
 *
 * @param  array  $pakket Pakket.
 * @param  string $lengte long of short.
 * @param  mixed  $ruw    Antwoorden uit het verzoek.
 * @return array|null     Schone antwoorden, of null als ze niet kloppen.
 */
function sfp_zelftest_antwoorden_schoon( array $pakket, $lengte, $ruw ) {
    if ( ! is_array( $ruw ) || ! isset( $pakket['manifest']['lengtes'][ $lengte ]['vragen'] ) ) {
        return null;
    }
    $vragen = sfp_zelftest_vragen( $pakket );
    $nodig  = array_map( 'intval', (array) $pakket['manifest']['lengtes'][ $lengte ]['vragen'] );
    $schoon = array();
    foreach ( $ruw as $nr => $waarde ) {
        if ( ! in_array( (int) $nr, $nodig, true ) ) {
            return null;
        }
    }
    foreach ( $nodig as $nr ) {
        if ( ! isset( $vragen[ $nr ] ) || ! isset( $ruw[ $nr ] ) ) {
            return null;
        }
        $waarde  = $ruw[ $nr ];
        $letters = substr( 'abcdefghi', 0, $vragen[ $nr ]['opties'] );
        if ( $vragen[ $nr ]['meerdere'] ) {
            if ( ! is_array( $waarde ) || ! $waarde || count( $waarde ) > strlen( $letters ) ) {
                return null;
            }
            $lijst = array();
            foreach ( $waarde as $l ) {
                if ( ! is_string( $l ) || 1 !== strlen( $l ) || false === strpos( $letters, $l ) || in_array( $l, $lijst, true ) ) {
                    return null;
                }
                $lijst[] = $l;
            }
            $schoon[ $nr ] = $lijst;
        } else {
            if ( ! is_string( $waarde ) || 1 !== strlen( $waarde ) || false === strpos( $letters, $waarde ) ) {
                return null;
            }
            $schoon[ $nr ] = $waarde;
        }
    }
    ksort( $schoon );
    return $schoon;
}

/**
 * Controleer de uitkomst uit de browser: CRM-velden uit de toegestane lijst,
 * notitieregels als korte tekst.
 *
 * @param  array $pakket Pakket.
 * @param  mixed $ruw    Uitkomst.
 * @return array|null    array( 'crm' => [...], 'notitie' => [[label, waarde], ...] ), of null.
 */
function sfp_zelftest_uitkomst_schoon( array $pakket, $ruw ) {
    if ( ! is_array( $ruw ) || ! isset( $ruw['crm'] ) || ! is_array( $ruw['crm'] ) ) {
        return null;
    }
    $crm = array();
    foreach ( (array) $pakket['manifest']['crm']['afgerond'] as $veld => $toegestaan ) {
        $w = isset( $ruw['crm'][ $veld ] ) && is_string( $ruw['crm'][ $veld ] ) ? $ruw['crm'][ $veld ] : null;
        if ( null === $w || ! in_array( $w, (array) $toegestaan, true ) ) {
            return null;
        }
        $crm[ $veld ] = $w;
    }
    $notitie = array();
    foreach ( array_slice( isset( $ruw['notitie'] ) && is_array( $ruw['notitie'] ) ? $ruw['notitie'] : array(), 0, 20 ) as $regel ) {
        if ( is_array( $regel ) && 2 === count( $regel ) && isset( $regel[0], $regel[1] ) && is_scalar( $regel[0] ) && is_scalar( $regel[1] ) ) {
            $notitie[] = array( mb_substr( sanitize_text_field( (string) $regel[0] ), 0, 60 ), mb_substr( sanitize_text_field( (string) $regel[1] ), 0, 300 ) );
        }
    }
    // Welke deelafbeeldingen bestaan (het label bepaalt het; heel bestaat altijd).
    $varianten = isset( $ruw['varianten'] ) && is_array( $ruw['varianten'] ) ? array_values( array_intersect( array( 'sterk', 'heel' ), $ruw['varianten'] ) ) : array();
    if ( ! in_array( 'heel', $varianten, true ) ) {
        $varianten[] = 'heel';
    }
    return array( 'crm' => $crm, 'notitie' => $notitie, 'varianten' => $varianten );
}

/**
 * Het e-mailadres uit het verzoek, of ''.
 *
 * @param  array $in Invoer.
 * @return string
 */
function sfp_zelftest_email( array $in ) {
    $mail = isset( $in['email'] ) && is_string( $in['email'] ) ? strtolower( trim( $in['email'] ) ) : '';
    $mail = sanitize_email( $mail );
    return ( '' !== $mail && is_email( $mail ) && strlen( $mail ) <= 190 ) ? $mail : '';
}

/* =========================================================================
 * Routes
 * ====================================================================== */

/**
 * Start: een rij aanmaken en send 1 in de wachtrij.
 *
 * @param  WP_REST_Request $verzoek Verzoek.
 * @return WP_REST_Response|WP_Error
 */
function sfp_zelftest_rest_start( WP_REST_Request $verzoek ) {
    $pakket = sfp_zelftest_rest_pakket( $verzoek );
    if ( is_wp_error( $pakket ) ) {
        return $pakket;
    }
    $in = (array) $verzoek->get_json_params();

    // Lokveld: mensen zien het niet en vullen het niet in.
    if ( ! empty( $in['website'] ) ) {
        return sfp_zelftest_rest_fout( $pakket, 'sfp_zelftest_geweigerd', 'fout_versturen', 400 );
    }
    $voornaam = sfp_zelftest_veld( $in, 'voornaam', 100 );
    $email    = sfp_zelftest_email( $in );
    $lengte   = isset( $in['lengte'] ) && is_string( $in['lengte'] ) ? $in['lengte'] : '';
    if ( '' === $voornaam || '' === $email || ! isset( $pakket['manifest']['lengtes'][ $lengte ] ) ) {
        return sfp_zelftest_rest_fout( $pakket, 'sfp_zelftest_ongeldig', 'fout_versturen', 400 );
    }
    if ( ! sfp_zelftest_limiet( 'start-ip', sfp_zelftest_ip(), 60 ) || ! sfp_zelftest_limiet( 'start-mail', $email, 5 ) ) {
        return sfp_zelftest_rest_fout( $pakket, 'sfp_zelftest_te_vaak', 'fout_te_vaak', 429 );
    }

    $bron = isset( $in['lead_source_url'] ) && is_string( $in['lead_source_url'] ) ? esc_url_raw( mb_substr( $in['lead_source_url'], 0, 500 ), array( 'http', 'https' ) ) : '';

    global $wpdb;
    $sessie = bin2hex( random_bytes( 16 ) );
    $ok     = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        sfp_zelftest_tabel(),
        array(
            'test'            => $pakket['test'],
            'pakket_versie'   => (string) $pakket['manifest']['pakket_versie'],
            'sessie'          => $sessie,
            'email'           => $email,
            'voornaam'        => $voornaam,
            'lengte'          => $lengte,
            'nieuwsbrief'     => ! empty( $in['nieuwsbrief'] ) ? 1 : 0,
            'lead_source_url' => $bron,
            'utm_source'      => sfp_zelftest_veld( $in, 'utm_source', 200 ),
            'utm_medium'      => sfp_zelftest_veld( $in, 'utm_medium', 200 ),
            'utm_campaign'    => sfp_zelftest_veld( $in, 'utm_campaign', 200 ),
            'gclid'           => sfp_zelftest_veld( $in, 'gclid', 200 ),
            'extra'           => wp_json_encode( array( 'dienst' => sanitize_key( sfp_zelftest_veld( $in, 'dienst', 20 ) ) ) ),
            'gestart_op'      => sfp_zelftest_nu(),
            'crm_start'       => 'wacht',
            'crm_volgende'    => sfp_zelftest_nu(),
        )
    );
    if ( ! $ok ) {
        return sfp_zelftest_rest_fout( $pakket, 'sfp_zelftest_opslaan', 'fout_versturen', 500 );
    }
    sfp_zelftest_na_antwoord( (int) $wpdb->insert_id );
    return rest_ensure_response( array( 'sessie' => $sessie ) );
}

/**
 * Afgerond: antwoorden en uitkomst controleren, code maken, send 2 en de pdf
 * in de wachtrij.
 *
 * @param  WP_REST_Request $verzoek Verzoek.
 * @return WP_REST_Response|WP_Error
 */
function sfp_zelftest_rest_afgerond( WP_REST_Request $verzoek ) {
    $pakket = sfp_zelftest_rest_pakket( $verzoek );
    if ( is_wp_error( $pakket ) ) {
        return $pakket;
    }
    $in     = (array) $verzoek->get_json_params();
    $sessie = isset( $in['sessie'] ) && is_string( $in['sessie'] ) && preg_match( '/^[a-f0-9]{32}$/', $in['sessie'] ) ? $in['sessie'] : '';
    $rij    = '' === $sessie ? null : sfp_zelftest_rij_op( 'sessie', $sessie );
    if ( ! $rij || $rij['test'] !== $pakket['test'] ) {
        return sfp_zelftest_rest_fout( $pakket, 'sfp_zelftest_sessie', 'fout_versturen', 400 );
    }
    // Al afgerond: hetzelfde antwoord, geen nieuwe code (dubbele klik, opnieuw versturen na een netwerkfout).
    if ( ! empty( $rij['afgerond_op'] ) && ! empty( $rij['code'] ) ) {
        return rest_ensure_response( array( 'code' => $rij['code'], 'result_url' => sfp_zelftest_adres( $rij['test'], 'pdf', array( 'code' => $rij['code'] ) ) ) );
    }
    if ( strtotime( $rij['gestart_op'] . ' UTC' ) < time() - DAY_IN_SECONDS ) {
        return sfp_zelftest_rest_fout( $pakket, 'sfp_zelftest_sessie', 'fout_versturen', 400 );
    }
    $antwoorden = sfp_zelftest_antwoorden_schoon( $pakket, $rij['lengte'], isset( $in['antwoorden'] ) ? $in['antwoorden'] : null );
    $uitkomst   = sfp_zelftest_uitkomst_schoon( $pakket, isset( $in['uitkomst'] ) ? $in['uitkomst'] : null );
    if ( null === $antwoorden || null === $uitkomst ) {
        return sfp_zelftest_rest_fout( $pakket, 'sfp_zelftest_ongeldig', 'fout_versturen', 400 );
    }

    $code     = sfp_zelftest_nieuwe_code( (string) $pakket['manifest']['code_voorvoegsel'] );
    $deelcode = sfp_zelftest_nieuwe_deelcode();
    $nu       = sfp_zelftest_nu();
    $duur     = max( 0, time() - (int) strtotime( $rij['gestart_op'] . ' UTC' ) );
    $heeft_blad = '' !== $pakket['blad'];
    $velden     = array(
        'code'         => $code,
        'deelcode'     => $deelcode,
        'antwoorden'   => wp_json_encode( $antwoorden ),
        'uitkomst'     => wp_json_encode( $uitkomst ),
        'afgerond_op'  => $nu,
        'duur_sec'     => $duur,
        'crm_afgerond' => 'wacht',
        'pdf_status'   => $heeft_blad ? 'wacht' : 'geen',
        'pdf_volgende' => $nu,
    );
    // Wacht send 1 nog op een nieuwe poging, dan houdt die zijn tijd.
    if ( 'wacht' !== $rij['crm_start'] ) {
        $velden['crm_volgende'] = $nu;
    }
    // Alleen als er nog geen code is: twee gelijktijdige verzoeken geven zo één code.
    global $wpdb;
    $sets = array();
    $args = array();
    foreach ( $velden as $kolom => $waarde ) {
        $sets[] = $kolom . ' = ' . ( is_int( $waarde ) ? '%d' : '%s' );
        $args[] = $waarde;
    }
    $args[]   = (int) $rij['id'];
    $tabel    = sfp_zelftest_tabel();
    $gewijzigd = $wpdb->query( $wpdb->prepare( "UPDATE {$tabel} SET " . implode( ', ', $sets ) . ' WHERE id = %d AND code IS NULL', $args ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
    if ( ! $gewijzigd ) {
        $rij = sfp_zelftest_rij_op( 'id', (string) (int) $rij['id'] );
        if ( $rij && ! empty( $rij['code'] ) ) {
            return rest_ensure_response( array( 'code' => $rij['code'], 'result_url' => sfp_zelftest_adres( $rij['test'], 'pdf', array( 'code' => $rij['code'] ) ) ) );
        }
        return sfp_zelftest_rest_fout( $pakket, 'sfp_zelftest_opslaan', 'fout_versturen', 500 );
    }
    sfp_zelftest_na_antwoord( (int) $rij['id'] );
    return rest_ensure_response( array( 'code' => $code, 'result_url' => sfp_zelftest_adres( $rij['test'], 'pdf', array( 'code' => $code ) ) ) );
}

/**
 * Opnieuw sturen naar een verbeterd e-mailadres (eindscherm: "Verkeerd
 * e-mailadres? Aanpassen"). Hoogstens drie keer, binnen een dag.
 *
 * @param  WP_REST_Request $verzoek Verzoek.
 * @return WP_REST_Response|WP_Error
 */
function sfp_zelftest_rest_opnieuw( WP_REST_Request $verzoek ) {
    $pakket = sfp_zelftest_rest_pakket( $verzoek );
    if ( is_wp_error( $pakket ) ) {
        return $pakket;
    }
    $in     = (array) $verzoek->get_json_params();
    $sessie = isset( $in['sessie'] ) && is_string( $in['sessie'] ) && preg_match( '/^[a-f0-9]{32}$/', $in['sessie'] ) ? $in['sessie'] : '';
    $rij    = '' === $sessie ? null : sfp_zelftest_rij_op( 'sessie', $sessie );
    $email  = sfp_zelftest_email( $in );
    if ( ! $rij || $rij['test'] !== $pakket['test'] || empty( $rij['afgerond_op'] ) || '' === $email ) {
        return sfp_zelftest_rest_fout( $pakket, 'sfp_zelftest_ongeldig', 'fout_versturen', 400 );
    }
    if ( (int) $rij['opnieuw_gestuurd'] >= 3 || strtotime( $rij['afgerond_op'] . ' UTC' ) < time() - DAY_IN_SECONDS || ! sfp_zelftest_limiet( 'opnieuw-mail', $email, 2 ) ) {
        return sfp_zelftest_rest_fout( $pakket, 'sfp_zelftest_te_vaak', 'fout_te_vaak', 429 );
    }
    sfp_zelftest_rij_bij(
        (int) $rij['id'],
        'wacht' === $rij['crm_start'] ? array(
            'email'            => $email,
            'opnieuw_gestuurd' => (int) $rij['opnieuw_gestuurd'] + 1,
            'crm_afgerond'     => 'wacht',
        ) : array(
            'email'            => $email,
            'opnieuw_gestuurd' => (int) $rij['opnieuw_gestuurd'] + 1,
            'crm_afgerond'     => 'wacht',
            'crm_pogingen'     => 0,
            'crm_volgende'     => sfp_zelftest_nu(),
        )
    );
    sfp_zelftest_na_antwoord( (int) $rij['id'] );
    return rest_ensure_response( array( 'ok' => true ) );
}

/* =========================================================================
 * Werk na het antwoord
 * ====================================================================== */

/**
 * Onthoud een rij om na het antwoord aan de bezoeker af te handelen, zodat
 * de bezoeker niet op SureContact of Cloudflare wacht.
 *
 * @param  int $id Rij.
 * @return void
 */
function sfp_zelftest_na_antwoord( $id ) {
    static $rijen = array();
    static $haak  = false;
    $rijen[] = (int) $id;
    if ( $haak ) {
        return;
    }
    $haak = true;
    add_action(
        'shutdown',
        function () use ( &$rijen ) {
            if ( function_exists( 'fastcgi_finish_request' ) ) {
                fastcgi_finish_request();
            } elseif ( function_exists( 'litespeed_finish_request' ) ) {
                litespeed_finish_request();
            }
            foreach ( array_unique( $rijen ) as $rij_id ) {
                sfp_zelftest_crm_verwerk( $rij_id );
            }
            foreach ( array_unique( $rijen ) as $rij_id ) {
                if ( function_exists( 'sfp_zelftest_pdf_verwerk' ) ) {
                    sfp_zelftest_pdf_verwerk( $rij_id );
                }
            }
        },
        1
    );
    // Vangnet als dit verzoek eerder stopt: de wachtrij loopt ook via cron.
    sfp_zelftest_cron_plannen();
}
