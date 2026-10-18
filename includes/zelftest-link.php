<?php
/**
 * SFP Page Config - Zelftest: pdf-link
 *
 * Eén adres per test, in SFP Beheer in te stellen (geen pagina in
 * WordPress, eigen route van de plugin):
 *
 *   <pdf_pad>/?code=DPS-XXXX-XXXX-XXXX
 *       geldige code binnen de termijn: de pdf; na de termijn: "verlopen"
 *       (P17); onbekende code: "niet gevonden" (P18). Zolang de pdf nog
 *       gemaakt wordt: een wachtpagina die zichzelf ververst.
 *
 * Altijd: geen cache (WP Rocket, SiteGround, browser), noindex. De regel
 * voor Cloudflare zet WordPress Toolkit.
 *
 * @package SFP_Page_Config
 * @since   3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'template_redirect', 'sfp_zelftest_link_route', 0 );

/**
 * Bij welke test hoort dit verzoek?
 *
 * @return array|null array( 'test' => ... ), of null.
 */
function sfp_zelftest_link_herken() {
    if ( ! function_exists( 'sfp_page_config_onderdeel_aan' ) || ! sfp_page_config_onderdeel_aan( 'zelftest' ) ) {
        return null;
    }
    global $wp;
    $pad = isset( $wp->request ) ? trim( strtolower( (string) $wp->request ), '/' ) : '';
    if ( '' === $pad ) {
        return null;
    }
    foreach ( sfp_zelftest_tests() as $test ) {
        $inst = sfp_zelftest_instellingen( $test );
        if ( '' !== $inst['pdf_pad'] && $pad === $inst['pdf_pad'] ) {
            return array( 'test' => $test );
        }
    }
    return null;
}

/**
 * Geen cache en geen index voor deze antwoorden.
 *
 * @param  string $cache Cache-Control.
 * @return void
 */
function sfp_zelftest_geen_cache( $cache = 'no-store, private, max-age=0' ) {
    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- afspraak van WP Rocket.
    }
    nocache_headers();
    header( 'Cache-Control: ' . $cache );
    header( 'X-Robots-Tag: noindex, nofollow' );
}

/**
 * Handel de pdf-link af.
 */
function sfp_zelftest_link_route() {
    $route = sfp_zelftest_link_herken();
    if ( ! $route ) {
        return;
    }
    $pakket = sfp_zelftest_pakket( $route['test'] );
    if ( ! $pakket ) {
        return;
    }
    sfp_zelftest_geen_cache();
    // Het adres bevat een code: nooit doorgeven aan een volgende pagina of dienst.
    header( 'Referrer-Policy: no-referrer' );
    sfp_zelftest_link_pdf( $pakket );
    exit;
}

/* =========================================================================
 * Pdf-link
 * ====================================================================== */

/**
 * De pdf-link.
 *
 * @param  array $pakket Pakket.
 * @return void
 */
function sfp_zelftest_link_pdf( array $pakket ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- openbare link uit een e-mail.
    $code = isset( $_GET['code'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['code'] ) ) ) : '';
    $rij  = null;
    if ( preg_match( '/^[A-Z]{2,6}-[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]{4}$/', $code ) && sfp_zelftest_limiet( 'pdf-ip', sfp_zelftest_ip(), 120 ) ) {
        $rij = sfp_zelftest_rij_op( 'code', $code );
    }
    if ( ! $rij || $rij['test'] !== $pakket['test'] || (int) $rij['geanonimiseerd'] || empty( $rij['afgerond_op'] ) ) {
        sfp_zelftest_link_pagina( $pakket, 'onbekend', 404 );
        return;
    }
    $dagen = (int) $pakket['manifest']['geldigheid_dagen'];
    if ( strtotime( $rij['afgerond_op'] . ' UTC' ) + $dagen * DAY_IN_SECONDS < time() ) {
        sfp_zelftest_link_pagina( $pakket, 'verlopen', 410 );
        return;
    }
    if ( empty( $rij['pdf_eerste_klik'] ) ) {
        sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_eerste_klik' => sfp_zelftest_nu() ) );
    }

    $pad = sfp_zelftest_pdf_pad( $rij );
    if ( '' === $pad && 'geen' === $rij['pdf_status'] ) {
        // Het pakket heeft geen blad: er komt nooit een pdf. Een instelfout, geen wachtpagina.
        sfp_zelftest_log( $rij['test'], 'fout', 'Pdf-link geopend, maar het pakket heeft geen blad.html (rij ' . (int) $rij['id'] . ').' );
        sfp_zelftest_link_pagina( $pakket, 'onbekend', 404 );
        return;
    }
    if ( '' === $pad ) {
        if ( 'klaar' === $rij['pdf_status'] ) {
            // Bestand weg terwijl de link nog geldt: opnieuw maken.
            sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'wacht', 'pdf_bestand' => '' ) );
        }
        // Nog niet klaar: nu maken, zonder de bezoeker op een vorige aanvraag te laten wachten.
        sfp_zelftest_pdf_verwerk( (int) $rij['id'], true, false );
        $rij = sfp_zelftest_rij_op( 'id', (string) (int) $rij['id'] );
        $pad = $rij ? sfp_zelftest_pdf_pad( $rij ) : '';
    }
    if ( '' === $pad ) {
        sfp_zelftest_link_pagina( $pakket, 'wachten', 200 );
        return;
    }

    $namen = sfp_zelftest_pdf_bestandsnaam( $pakket, (string) $rij['code'] );
    status_header( 200 );
    header( 'Content-Type: application/pdf' );
    // Twee namen: een gewone voor oudere programma's, en de echte met punten (RFC 5987).
    header( 'Content-Disposition: inline; filename="' . $namen['ascii'] . '"; filename*=UTF-8\'\'' . rawurlencode( $namen['utf8'] ) );
    header( 'Content-Length: ' . filesize( $pad ) );
    header( 'X-Content-Type-Options: nosniff' );
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- de pdf zelf.
    readfile( $pad );
}

/**
 * Bestandsnaam van de pdf: "DPS • Presentatietest • 8H02-ZW6N-Y9R7.pdf"
 * (besluit 18-10-2026). Voorvoegsel en naam komen uit het manifest; het
 * voorvoegsel staat al vooraan, dus de code zonder voorvoegsel.
 *
 * @param  array  $pakket Pakket.
 * @param  string $code   Resultaatcode.
 * @return array{utf8:string,ascii:string}
 */
function sfp_zelftest_pdf_bestandsnaam( array $pakket, $code ) {
    $m     = isset( $pakket['manifest'] ) ? $pakket['manifest'] : array();
    $voor  = isset( $m['code_voorvoegsel'] ) ? strtoupper( (string) $m['code_voorvoegsel'] ) : strtoupper( (string) $pakket['test'] );
    $naam  = isset( $m['naam'] ) && '' !== trim( (string) $m['naam'] ) ? trim( (string) $m['naam'] ) : (string) $pakket['test'];
    $code  = strtoupper( $code );
    if ( '' !== $voor && 0 === strpos( $code, $voor . '-' ) ) {
        $code = substr( $code, strlen( $voor ) + 1 );
    }
    // Alleen letters, cijfers, spatie, streepje en punt: geen tekens die een bestandsnaam of de kop breken.
    $schoon = function ( $s ) {
        return trim( preg_replace( '/[^\p{L}\p{N} .\-]+/u', '', (string) $s ) );
    };
    $delen = array_values( array_filter( array( $schoon( $voor ), $schoon( $naam ), $schoon( $code ) ), 'strlen' ) );
    $utf8  = implode( " \u{2022} ", $delen ) . '.pdf';
    $ascii = function_exists( 'remove_accents' ) ? remove_accents( implode( ' - ', $delen ) ) : implode( ' - ', $delen );
    $ascii = preg_replace( '/[^A-Za-z0-9 .\-]+/', '', $ascii ) . '.pdf';
    return array( 'utf8' => $utf8, 'ascii' => $ascii );
}

/**
 * Een eenvoudige pagina in het thema van de site: verlopen, onbekend of
 * wachten. De teksten komen uit het pakket.
 *
 * @param  array  $pakket Pakket.
 * @param  string $soort  verlopen, onbekend of wachten.
 * @param  int    $status HTTP-status.
 * @return void
 */
function sfp_zelftest_link_pagina( array $pakket, $soort, $status ) {
    if ( 'wachten' === $soort ) {
        $kop   = sfp_zelftest_tekst( $pakket, 'module.wachten_kop' );
        $tekst = sfp_zelftest_tekst( $pakket, 'module.wachten_tekst' );
        $knop  = '';
    } else {
        $kop   = sfp_zelftest_tekst( $pakket, $soort . '.kop' );
        $tekst = sfp_zelftest_tekst( $pakket, $soort . '.tekst' );
        $knop  = sfp_zelftest_tekst( $pakket, $soort . '.knop' );
    }
    $inhoud = '<div class="sfp-zt-melding"><h1>' . esc_html( $kop ) . '</h1><p>' . esc_html( $tekst ) . '</p>'
        . ( '' !== $knop ? '<p><a class="sfp-zt-knop" href="' . esc_url( sfp_zelftest_testpagina_url( $pakket['test'] ) ) . '">' . esc_html( $knop ) . '</a></p>' : '' )
        . '</div>';
    $extra_kop = 'wachten' === $soort ? '<meta http-equiv="refresh" content="8">' : '';
    sfp_zelftest_kale_pagina( $kop, $inhoud, $status, $extra_kop );
}

/**
 * Een losse pagina zonder het thema: geen scripts van derden (statistiek,
 * A/B-test, cookiemelding) die het adres met de code kunnen lezen. Kleuren
 * uit de kleurrollen van de site, fonts van de site.
 *
 * @param  string $titel     Titel.
 * @param  string $inhoud    HTML (al ge-escaped).
 * @param  int    $status    HTTP-status.
 * @param  string $extra_kop Extra HTML in de head (vaste HTML).
 * @return void
 */
function sfp_zelftest_kale_pagina( $titel, $inhoud, $status, $extra_kop = '' ) {
    status_header( (int) $status );
    header( 'Content-Type: text/html; charset=utf-8' );
    $rollen = array( 'primair', 'secundair', 'links', 'hover', 'tint-1', 'tint-2', 'tint-3', 'wit', 'body' );
    $vars   = '';
    foreach ( $rollen as $rol ) {
        $hex = function_exists( 'sfp_zelftest_rol_hex' ) ? sfp_zelftest_rol_hex( $rol ) : '';
        if ( '' !== $hex ) {
            $vars .= '--sfp-rol-' . $rol . ':' . $hex . ';';
        }
    }
    $css     = (string) file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/zelftest/zelftest.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- eigen pluginbestand.
    $kop     = function_exists( 'sfp_page_config_sanitize_font_family' ) ? sfp_page_config_sanitize_font_family( sfp_page_config_astra_option( 'headings-font-family' ) ) : '';
    $tekst   = function_exists( 'sfp_page_config_sanitize_font_family' ) ? sfp_page_config_sanitize_font_family( sfp_page_config_astra_option( 'body-font-family' ) ) : '';
    $fonts   = is_readable( trailingslashit( WP_CONTENT_DIR ) . 'astra-local-fonts/astra-local-fonts.css' ) ? '<link rel="stylesheet" href="' . esc_url( content_url( 'astra-local-fonts/astra-local-fonts.css' ) ) . '">' : '';
    $taal    = function_exists( 'sfp_page_config_taal' ) ? sfp_page_config_taal() : 'nl';
    $basis   = ':root{' . $vars . '}' . sfp_zelftest_merk_css()
        . ':root{--sfp-b-kop:' . ( '' !== $kop ? $kop : 'inherit' ) . ';--sfp-b-tekst:' . ( '' !== $tekst ? $tekst : 'system-ui,sans-serif' ) . '}'
        . 'html,body{margin:0;background:var(--sfp-b-wit,#fff)}body{font-family:var(--sfp-b-tekst);color:var(--sfp-b-body)}';
    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste HTML, ge-escapete titel en inhoud.
    echo '<!doctype html><html lang="' . esc_attr( $taal ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="robots" content="noindex,nofollow"><meta name="referrer" content="no-referrer">'
        . '<title>' . esc_html( $titel ) . ' | ' . esc_html( get_bloginfo( 'name' ) ) . '</title>'
        . $fonts . '<style>' . $basis . $css . '</style>' . $extra_kop . '</head><body>'
        . '<main class="sfp-zt sfp-zt-route"><div class="sfp-zt-wrap">' . $inhoud . '</div></main></body></html>';
    // phpcs:enable
}

/* =========================================================================
 * WP Rocket
 * ====================================================================== */

add_filter( 'rocket_cache_reject_uri', 'sfp_zelftest_rocket_niet_cachen' );

/**
 * WP Rocket bewaart de pdf-link nooit.
 *
 * @param  array $uris Uitsluitingen.
 * @return array
 */
function sfp_zelftest_rocket_niet_cachen( $uris ) {
    $uris = (array) $uris;
    if ( ! function_exists( 'sfp_zelftest_tests' ) ) {
        return $uris;
    }
    foreach ( sfp_zelftest_tests() as $test ) {
        $inst = sfp_zelftest_instellingen( $test );
        if ( '' !== $inst['pdf_pad'] ) {
            $uris[] = '/' . $inst['pdf_pad'] . '/(.*)';
        }
    }
    return $uris;
}
