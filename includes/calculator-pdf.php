<?php
/**
 * SFP Page Config - Incompanycalculator: pdf en link (sinds 3.36.0).
 *
 * Het blad is HTML uit de eigen berekening van de server, met de kleurrollen
 * en fonts van de site, en gaat via Cloudflare Browser Run naar pdf (dezelfde
 * aanvraag, wachtrij en Cloudflare-instelling als de zelftest). De link is
 * <calculatorpagina>/begroting/?b=DPS-B-26-0042.<sleutel>: het
 * kenmerk alleen is te raden (een teller), de sleutel niet. Noindex, geen cache.
 *
 * @package SFP_Page_Config
 * @since   3.36.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Blad
 * ====================================================================== */

/**
 * Een lijst uit een tekst met regels.
 *
 * @param  string $tekst Regels.
 * @return string        HTML.
 */
function sfp_calculator_blad_lijst( $tekst ) {
    $regels = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $tekst ) ), 'strlen' );
    if ( ! $regels ) {
        return '';
    }
    return '<ul>' . implode(
        '',
        array_map(
            function ( $r ) {
                return '<li>' . esc_html( $r ) . '</li>';
            },
            $regels
        )
    ) . '</ul>';
}

/**
 * Een blok met kop.
 *
 * @param  string $kop    Kop.
 * @param  string $inhoud HTML.
 * @param  string $klasse Extra klasse.
 * @return string
 */
function sfp_calculator_blad_blok( $kop, $inhoud, $klasse = '' ) {
    if ( '' === trim( wp_strip_all_tags( $inhoud ) ) ) {
        return '';
    }
    return '<section class="blok ' . esc_attr( $klasse ) . '"><h2>' . esc_html( $kop ) . '</h2>' . $inhoud . '</section>';
}

/**
 * De tabel met bedragen.
 *
 * @param  array  $rij Rij.
 * @param  bool   $volledig Met btw en totaal incl. (pdf), anders alleen excl.
 * @return string HTML.
 */
function sfp_calculator_blad_tabel( array $rij, $volledig = true ) {
    $taal   = (string) $rij['taal'];
    $ber    = sfp_calculator_json( $rij, 'berekening' );
    $u      = isset( $ber['uitkomst'] ) ? $ber['uitkomst'] : array();
    $o      = isset( $u['uitkomst'] ) && is_array( $u['uitkomst'] ) ? $u['uitkomst'] : array();
    $valuta = isset( $ber['norm']['valuta'] ) ? $ber['norm']['valuta'] : 'EUR';
    $t      = function ( $s, $v = array() ) use ( $taal ) {
        return sfp_calculator_t( $s, $v, $taal );
    };
    if ( empty( $o['regels'] ) ) {
        return '';
    }
    $html = '<table class="bedragen"><thead><tr><th>' . esc_html( $t( 'pdf_omschrijving' ) ) . '</th><th class="n">' . esc_html( $t( 'pdf_aantal' ) ) . '</th><th class="n">' . esc_html( $t( 'pdf_prijs' ) ) . '</th><th class="n">' . esc_html( $t( 'pdf_bedrag' ) ) . '</th></tr></thead><tbody>';
    foreach ( $o['regels'] as $r ) {
        $label = $t( 'regel_' . $r['code'] );
        $prijs = $r['prijs'] / 100;
        $html .= '<tr><td>' . esc_html( $label ) . '</td><td class="n z">' . (int) $r['aantal'] . ' x</td><td class="n z">'
            . esc_html( 0 === $r['prijs'] % 100 ? sfp_calculator_bedrag( (int) $prijs, $valuta ) : str_replace( ' ', ' ', sfp_calculator_bedrag( (int) floor( $prijs ), $valuta ) ) . ',' . str_pad( (string) ( abs( $r['prijs'] ) % 100 ), 2, '0', STR_PAD_LEFT ) )
            . '</td><td class="n">' . ( $r['gratis'] ? esc_html( $t( 'inbegrepen' ) ) : esc_html( sfp_calculator_bedrag( (int) $r['bedrag'], $valuta ) ) ) . '</td></tr>';
    }
    $html .= '</tbody><tfoot><tr class="totaal"><td colspan="3">' . esc_html( ( ! empty( $u['richtprijs'] ) ? $t( 'richtprijs' ) : $t( 'totaal' ) ) . ' ' . $t( 'excl' ) ) . '</td><td class="n">' . esc_html( sfp_calculator_bedrag( (int) $o['totaal_excl'], $valuta ) ) . '</td></tr>';
    if ( $volledig ) {
        foreach ( (array) $o['btw'] as $tarief => $bedrag ) {
            if ( 0 === (int) $bedrag ) {
                continue;
            }
            $html .= '<tr class="btw"><td colspan="3">' . esc_html( $t( 'btw_regel', array( 'n' => $tarief ) ) ) . '</td><td class="n">' . esc_html( sfp_calculator_bedrag( (int) $bedrag, $valuta ) ) . '</td></tr>';
        }
        $html .= '<tr class="incl"><td colspan="3">' . esc_html( $t( 'incl' ) ) . '</td><td class="n">' . esc_html( sfp_calculator_bedrag( (int) $o['totaal_incl'], $valuta ) ) . '</td></tr>';
    }
    $html .= '</tfoot></table>';
    if ( ! empty( $u['bandbreedte'] ) && isset( $u['mensen']['van'] ) ) {
        $html .= '<p class="klein">' . esc_html( $t( 'bandbreedte', array( 'van' => $u['mensen']['van'], 'tot' => $u['mensen']['tot'], 'prijs' => sfp_calculator_bedrag( $u['bandbreedte']['van'], $valuta ) . ' - ' . sfp_calculator_bedrag( $u['bandbreedte']['tot'], $valuta ) ) ) . ' ' . $t( 'excl' ) ) . '</p>';
    }
    return $html;
}

/**
 * Voettekst voor Cloudflare: label, kenmerk en paginanummer.
 *
 * @param  array $rij Rij.
 * @return string
 */
function sfp_calculator_blad_voet( array $rij ) {
    $taal    = (string) $rij['taal'];
    $bedrijf = function_exists( 'sfp_page_config_bedrijf' ) ? sfp_page_config_bedrijf() : array();
    $label   = '' !== ( $bedrijf['label'] ?? '' ) ? $bedrijf['label'] : get_bloginfo( 'name' );
    return '<style>html,body{margin:0;padding:0}.v{font-family:system-ui,sans-serif;font-size:8px;color:#666;width:100%;padding:0 16mm;display:flex;justify-content:space-between}</style>'
        . '<div class="v"><span>' . esc_html( $label ) . '</span><span>' . esc_html( sfp_calculator_t( 'begroting', array(), $taal ) . ' ' . $rij['kenmerk'] ) . '</span>'
        . '<span>' . esc_html( sfp_calculator_t( 'pdf_pagina', array(), $taal ) ) . ' <span class="pageNumber"></span> ' . esc_html( sfp_calculator_t( 'pdf_van', array(), $taal ) ) . ' <span class="totalPages"></span></span></div>';
}

/* =========================================================================
 * Pdf per rij
 * ====================================================================== */

/**
 * Het pad van de pdf van een rij, als die er is.
 *
 * @param  array $rij Rij.
 * @return string
 */
function sfp_calculator_pdf_pad( array $rij ) {
    if ( ! preg_match( '/^[a-f0-9]{32}\.pdf$/', (string) $rij['pdf_bestand'] ) || ! function_exists( 'sfp_zelftest_map' ) ) {
        return '';
    }
    $map = sfp_zelftest_map( 'calculator' );
    return ( '' !== $map && is_readable( $map . $rij['pdf_bestand'] ) ) ? $map . $rij['pdf_bestand'] : '';
}

/**
 * Maak de pdf van één rij als hij aan de beurt is. Bij een fout opnieuw na
 * 1, 5, 30 en 120 minuten (zoals de zelftest).
 *
 * @param  int  $id          Rij.
 * @param  bool $forceer     Ook als hij nog niet aan de beurt is.
 * @param  bool $mag_wachten Mag tot 10 seconden wachten op de vorige aanvraag.
 * @return true|WP_Error|null
 */
function sfp_calculator_pdf_verwerk( $id, $forceer = false, $mag_wachten = true ) {
    global $wpdb;
    if ( ! sfp_calculator_tabel_klaar() || ! function_exists( 'sfp_zelftest_maak_pdf' ) ) {
        return null;
    }
    $tabel  = sfp_calculator_tabel();
    $nu     = gmdate( 'Y-m-d H:i:s' );
    $straks = gmdate( 'Y-m-d H:i:s', time() + 150 );
    $sql    = $forceer
        ? "UPDATE {$tabel} SET pdf_status = 'bezig', pdf_volgende = %s WHERE id = %d AND geanonimiseerd = 0 AND (pdf_status IN ('wacht','mislukt','uitgesteld') OR (pdf_status = 'bezig' AND pdf_volgende <= %s))"
        : "UPDATE {$tabel} SET pdf_status = 'bezig', pdf_volgende = %s WHERE id = %d AND geanonimiseerd = 0 AND ((pdf_status = 'wacht' AND (pdf_volgende IS NULL OR pdf_volgende <= %s)) OR (pdf_status = 'bezig' AND pdf_volgende <= %s))";
    $args   = $forceer ? array( $straks, (int) $id, $nu ) : array( $straks, (int) $id, $nu, $nu );
    if ( ! $wpdb->query( $wpdb->prepare( $sql, $args ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        return null;
    }
    $rij = sfp_calculator_rij_op( 'id', (int) $id );
    if ( ! $rij ) {
        return null;
    }
    $html = sfp_calculator_blad_html( $rij );
    $pdf  = sfp_zelftest_maak_pdf( array( 'manifest' => array( 'blad' => array( 'schaal' => 1 ) ) ), $html, $mag_wachten, sfp_calculator_blad_voet( $rij ) );
    if ( ! is_wp_error( $pdf ) ) {
        $map  = sfp_zelftest_map( 'calculator' );
        $naam = bin2hex( random_bytes( 16 ) ) . '.pdf';
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- eigen werkmap.
        if ( '' !== $map && false !== file_put_contents( $map . $naam, $pdf ) ) {
            sfp_calculator_pdf_weg( $rij );
            sfp_calculator_rij_bij(
                (int) $rij['id'],
                array(
                    'pdf_status'   => 'klaar',
                    'pdf_bestand'  => $naam,
                    'pdf_pogingen' => 0,
                    'pdf_volgende' => null,
                )
            );
            return true;
        }
        $pdf = new WP_Error( 'sfp_calculator_map', 'De pdf kon niet worden opgeslagen.' );
    }
    $code = $pdf->get_error_code();
    if ( 'sfp_zelftest_cf_geen' === $code ) {
        sfp_calculator_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'uitgesteld', 'pdf_volgende' => null ) );
        sfp_calculator_log( 'fout', 'Pdf uitgesteld: Cloudflare is niet ingesteld (Onderhoud, Zelftest).' );
        return $pdf;
    }
    if ( 'sfp_zelftest_cf_pauze' === $code ) {
        $data = $pdf->get_error_data();
        $tot  = is_array( $data ) && ! empty( $data['tot'] ) ? (int) $data['tot'] : time() + 60;
        sfp_calculator_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'wacht', 'pdf_volgende' => gmdate( 'Y-m-d H:i:s', $tot ) ) );
        sfp_calculator_wachtrij_plannen();
        return $pdf;
    }
    $poging = (int) $rij['pdf_pogingen'] + 1;
    $wacht  = function_exists( 'sfp_zelftest_wachttijd' ) ? sfp_zelftest_wachttijd( $poging ) : null;
    if ( null === $wacht ) {
        sfp_calculator_log( 'fout', 'Pdf mislukt na 5 pogingen (' . $rij['kenmerk'] . '): ' . $pdf->get_error_message() );
        sfp_calculator_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'mislukt', 'pdf_pogingen' => 0, 'pdf_volgende' => null ) );
    } else {
        sfp_calculator_log( 'fout', 'Pdf mislukt, poging ' . $poging . ' (' . $rij['kenmerk'] . '): ' . $pdf->get_error_message() );
        sfp_calculator_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'wacht', 'pdf_pogingen' => $poging, 'pdf_volgende' => gmdate( 'Y-m-d H:i:s', time() + (int) $wacht ) ) );
        sfp_calculator_wachtrij_plannen();
    }
    return $pdf;
}

add_action( 'sfp_calculator_wachtrij', 'sfp_calculator_wachtrij' );

/**
 * Plan de wachtrij (elke minuut) als hij nog niet gepland is.
 *
 * @return void
 */
function sfp_calculator_wachtrij_plannen() {
    if ( ! wp_next_scheduled( 'sfp_calculator_wachtrij' ) ) {
        wp_schedule_event( time() + MINUTE_IN_SECONDS, 'sfp_zelftest_minuut', 'sfp_calculator_wachtrij' );
    }
}

/**
 * Werk de wachtrij af en stop de planning als er niets meer wacht.
 *
 * @return void
 */
function sfp_calculator_wachtrij() {
    global $wpdb;
    if ( ! sfp_calculator_tabel_klaar() ) {
        return;
    }
    $tabel = sfp_calculator_tabel();
    // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$tabel} WHERE geanonimiseerd = 0 AND pdf_status IN ('wacht','bezig') AND (pdf_volgende IS NULL OR pdf_volgende <= %s) ORDER BY id LIMIT 3", gmdate( 'Y-m-d H:i:s' ) ) );
    foreach ( $ids as $id ) {
        sfp_calculator_pdf_verwerk( (int) $id );
    }
    $nog = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabel} WHERE geanonimiseerd = 0 AND pdf_status IN ('wacht','bezig')" );
    // phpcs:enable
    if ( ! $nog ) {
        wp_clear_scheduled_hook( 'sfp_calculator_wachtrij' );
    }
}

/* =========================================================================
 * Link
 * ====================================================================== */

add_action( 'template_redirect', 'sfp_calculator_link_route', 0 );

/**
 * Handel de pdf-link af.
 *
 * @return void
 */
function sfp_calculator_link_route() {
    if ( ! function_exists( 'sfp_page_config_onderdeel_aan' ) || ! sfp_page_config_onderdeel_aan( 'calculator' ) ) {
        return;
    }
    global $wp;
    $pad = isset( $wp->request ) ? trim( strtolower( (string) $wp->request ), '/' ) : '';
    if ( '' === $pad || sfp_calculator_pdf_pad_adres() !== $pad ) {
        return;
    }
    if ( function_exists( 'sfp_zelftest_geen_cache' ) ) {
        sfp_zelftest_geen_cache();
    }
    header( 'Referrer-Policy: no-referrer' );
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- openbare link uit een e-mail.
    $b       = isset( $_GET['b'] ) ? sanitize_text_field( wp_unslash( $_GET['b'] ) ) : '';
    $delen   = explode( '.', $b, 2 );
    $kenmerk = strtoupper( $delen[0] );
    $sleutel = isset( $delen[1] ) ? strtolower( $delen[1] ) : '';
    // phpcs:enable
    $rij = null;
    if ( preg_match( '/^([A-Z]{2,4}-)?B-\d{2}-\d{4,6}$/', $kenmerk ) && ( ! function_exists( 'sfp_zelftest_limiet' ) || sfp_zelftest_limiet( 'calc-pdf-ip', sfp_zelftest_ip(), 120 ) ) ) {
        $rij = sfp_calculator_rij_op( 'kenmerk', $kenmerk );
    }
    if ( ! $rij || ! sfp_calculator_sleutel_klopt( $rij, $sleutel ) || (int) $rij['geanonimiseerd'] || empty( $rij['afgerond_op'] ) ) {
        sfp_calculator_link_pagina( 'pdf_onbekend', 404 );
        exit;
    }
    if ( strtotime( $rij['afgerond_op'] . ' UTC' ) + (int) sfp_calculator_norm()['geldig_dagen'] * DAY_IN_SECONDS < time() ) {
        sfp_calculator_link_pagina( 'pdf_verlopen', 410, (string) $rij['taal'] );
        exit;
    }
    $pdf = sfp_calculator_pdf_pad( $rij );
    if ( '' === $pdf ) {
        if ( 'klaar' === $rij['pdf_status'] ) {
            sfp_calculator_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'wacht', 'pdf_bestand' => '' ) );
        }
        sfp_calculator_pdf_verwerk( (int) $rij['id'], true, false );
        $rij = sfp_calculator_rij_op( 'id', (int) $rij['id'] );
        $pdf = $rij ? sfp_calculator_pdf_pad( $rij ) : '';
    }
    if ( '' === $pdf ) {
        sfp_calculator_link_pagina( 'pdf_klaar', 200, (string) $rij['taal'], true );
        exit;
    }
    $namen = sfp_calculator_pdf_bestandsnaam( $rij );
    status_header( 200 );
    header( 'Content-Type: application/pdf' );
    header( 'Content-Disposition: inline; filename="' . $namen['ascii'] . '"; filename*=UTF-8\'\'' . rawurlencode( $namen['utf8'] ) );
    header( 'Content-Length: ' . filesize( $pdf ) );
    header( 'X-Content-Type-Options: nosniff' );
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- de pdf zelf.
    readfile( $pdf );
    exit;
}

/**
 * Bestandsnaam: "DPS • Begroting incompany • B-26-0042.pdf".
 *
 * @param  array $rij Rij.
 * @return array{utf8:string,ascii:string}
 */
function sfp_calculator_pdf_bestandsnaam( array $rij ) {
    $code    = sfp_calculator_site_afk();
    $kenmerk = (string) $rij['kenmerk'];
    if ( '' !== $code && 0 === strpos( $kenmerk, $code . '-' ) ) {
        $kenmerk = substr( $kenmerk, strlen( $code ) + 1 );
    }
    $delen = array_values( array_filter( array( $code, sfp_calculator_t( 'pdf_bestand', array(), (string) $rij['taal'] ), $kenmerk ), 'strlen' ) );
    $ascii = function_exists( 'remove_accents' ) ? remove_accents( implode( ' - ', $delen ) ) : implode( ' - ', $delen );
    return array(
        'utf8'  => implode( " \u{2022} ", $delen ) . '.pdf',
        'ascii' => preg_replace( '/[^A-Za-z0-9 .\-]+/', '', $ascii ) . '.pdf',
    );
}

/**
 * Een losse pagina: onbekend, verlopen of wachten.
 *
 * @param  string $sleutel Tekst.
 * @param  int    $status  HTTP-status.
 * @param  string $taal    Taal.
 * @param  bool   $ververs Ververs na 8 seconden.
 * @return void
 */
function sfp_calculator_link_pagina( $sleutel, $status, $taal = '', $ververs = false ) {
    $tekst  = sfp_calculator_t( $sleutel, array(), $taal );
    $inhoud = '<div class="sfp-zt-melding"><h1>' . esc_html( sfp_calculator_t( 'begroting', array(), $taal ) ) . '</h1><p>' . esc_html( $tekst ) . '</p>';
    $pagina = sfp_calculator_url( 'calculatorpagina' );
    if ( ! $ververs && '' !== $pagina ) {
        $inhoud .= '<p><a class="sfp-zt-knop" href="' . esc_url( $pagina ) . '">' . esc_html( sfp_calculator_t( 'titel', array(), $taal ) ) . '</a></p>';
    }
    $inhoud .= '</div>';
    if ( function_exists( 'sfp_zelftest_kale_pagina' ) ) {
        sfp_zelftest_kale_pagina( sfp_calculator_t( 'begroting', array(), $taal ), $inhoud, $status, $ververs ? '<meta http-equiv="refresh" content="8">' : '' );
        return;
    }
    status_header( (int) $status );
    echo $inhoud; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ge-escaped.
}

add_filter( 'rocket_cache_reject_uri', 'sfp_calculator_rocket_niet_cachen' );

/**
 * WP Rocket bewaart de pdf-link nooit.
 *
 * @param  array $uris Uitsluitingen.
 * @return array
 */
function sfp_calculator_rocket_niet_cachen( $uris ) {
    $uris   = (array) $uris;
    $uris[] = '/' . sfp_calculator_pdf_pad_adres() . '/(.*)';
    return $uris;
}
