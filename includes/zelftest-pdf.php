<?php
/**
 * SFP Page Config - Zelftest: pdf via Cloudflare Browser Run
 *
 * Het blad (blad.html uit het pakket) wordt per persoon klaargemaakt en naar
 * de Chrome van Cloudflare gestuurd:
 *
 *   - kleuren: elke hexwaarde uit manifest.blad.kleuren wordt de waarde van
 *     die rol op deze site (Astra-palet via het snippet met de kleurrollen);
 *   - fonts: de link naar Google Fonts gaat eruit; de fonts van de site gaan
 *     inline mee, zodat Cloudflare niets van de site hoeft op te halen;
 *   - gegevens: window.sfpZelftestBlad met naam, antwoorden, lengte, code,
 *     datum en taal, plus de rekenregel (motor.js).
 *
 * Endpoints (documentatie Cloudflare, gecontroleerd 17-10-2026):
 *   POST https://api.cloudflare.com/client/v4/accounts/<account>/browser-run/pdf
 * Token met de permissie "Browser Run - Edit".
 *
 * Gratis plan: 1 aanvraag per 10 seconden en 10 minuten browsertijd per dag,
 * per account (dus voor alle sites samen). Daarom een wachtrij: per site één
 * aanvraag tegelijk, minstens 10 seconden ertussen, en bij 429 de tijd uit
 * Retry-After afwachten.
 *
 * @package SFP_Page_Config
 * @since   3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Het blad klaarmaken
 * ====================================================================== */

/**
 * Hex van een kleurrol op deze site, of ''.
 *
 * @param  string $rol Rol (primair, secundair, links, hover, tint-1, ...).
 * @return string
 */
function sfp_zelftest_rol_hex( $rol ) {
    $slots = function_exists( 'sfp_kleurrollen_slots' ) ? (array) sfp_kleurrollen_slots() : array();
    $slots = (array) apply_filters( 'sfp_page_config_deelbeeld_slots', $slots );
    $hex   = isset( $slots[ $rol ] ) && function_exists( 'sfp_page_config_deelbeeld_slot_hex' ) ? sfp_page_config_deelbeeld_slot_hex( (int) $slots[ $rol ] ) : '';
    // Wit is geen merkkleur; een merk zonder rol wit houdt wit.
    if ( '' === $hex && 'wit' === $rol ) {
        return '#FFFFFF';
    }
    return $hex;
}

/**
 * De fonts van de site als @font-face met de bestanden inline.
 *
 * Eerst de lokale fonts van Astra (wp-content/astra-local-fonts), alleen de
 * Latijnse reeksen. Zonder die map de TTF-bestanden van het deelbeeld.
 *
 * @param  string[] $families Families die het blad gebruikt.
 * @return string             CSS.
 */
function sfp_zelftest_fonts_css( array $families ) {
    $css_pad = trailingslashit( WP_CONTENT_DIR ) . 'astra-local-fonts/astra-local-fonts.css';
    $css     = '';
    if ( is_readable( $css_pad ) ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokaal bestand.
        $bron = (string) file_get_contents( $css_pad );
        $map  = wp_normalize_path( trailingslashit( WP_CONTENT_DIR ) . 'astra-local-fonts/' );
        preg_match_all( '/@font-face\s*\{[^}]*\}/i', $bron, $blokken );
        foreach ( $blokken[0] as $blok ) {
            if ( ! preg_match( '/font-family:\s*[\'"]?([^;\'"]+)/i', $blok, $fam ) || ! in_array( strtolower( trim( $fam[1] ) ), array_map( 'strtolower', $families ), true ) ) {
                continue;
            }
            // Alleen Latijn en Latijn uitgebreid: daarin staan alle tekens van de drie talen.
            if ( ! preg_match( '/unicode-range:[^;]*(U\+0000-00FF|U\+0100-02BA)/i', $blok ) ) {
                continue;
            }
            if ( ! preg_match( '/url\(\s*[\'"]?([^\'")]+\.woff2)[\'"]?\s*\)/i', $blok, $url ) ) {
                continue;
            }
            $pad = wp_normalize_path( $map . preg_replace( '#^.*?/astra-local-fonts/#', '', strtok( $url[1], '?' ) ) );
            if ( 0 !== strpos( $pad, $map ) || ! is_readable( $pad ) ) {
                continue;
            }
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- lokaal fontbestand inline.
            $data = 'data:font/woff2;base64,' . base64_encode( (string) file_get_contents( $pad ) );
            $css .= preg_replace( '/url\([^)]*\)\s*format\([^)]*\)|url\([^)]*\)/i', 'url(' . $data . ') format("woff2")', $blok, 1 ) . "\n";
        }
    }
    if ( '' !== $css || ! function_exists( 'sfp_page_config_deelbeeld_fonts' ) ) {
        return $css;
    }
    // Terugval: de TTF-bestanden die het deelbeeld al gebruikt.
    $fonts = sfp_page_config_deelbeeld_fonts( true );
    if ( ! $fonts ) {
        return '';
    }
    $kop  = sfp_page_config_deelbeeld_familie( sfp_page_config_astra_option( 'headings-font-family' ) );
    $body = sfp_page_config_deelbeeld_familie( sfp_page_config_astra_option( 'body-font-family' ) );
    $gew  = (int) sfp_page_config_astra_option( 'headings-font-weight' );
    $gew  = ( $gew >= 100 && $gew <= 900 ) ? $gew : 900;
    $face = function ( $familie, $gewicht, $pad ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- lokaal fontbestand inline.
        return '@font-face{font-family:"' . $familie . '";font-style:normal;font-weight:' . (int) $gewicht . ';src:url(data:font/ttf;base64,' . base64_encode( (string) file_get_contents( $pad ) ) . ') format("truetype");}' . "\n";
    };
    return $face( '' !== $kop ? $kop : $body, $gew, $fonts['kop'] ) . $face( $body, 400, $fonts['tekst'] ) . $face( $body, 700, $fonts['vet'] );
}

/**
 * Kleuren en fonts van het blad naar die van deze site.
 *
 * Elke hexwaarde uit manifest.blad.kleuren wordt de waarde van die rol op
 * deze site, in één doorgang, zodat een nieuwe waarde nooit nog eens wordt
 * vervangen. Familienamen uit manifest.blad.fonts worden die van de site.
 * Geldt voor het blad en voor de voettekst.
 *
 * @param  array  $pakket Pakket.
 * @param  string $tekst  HTML of CSS.
 * @param  bool   $log    Ontbrekende kleurrollen in het logboek zetten.
 * @return array          tekst, families (de families die de site moet leveren).
 */
function sfp_zelftest_merk( array $pakket, $tekst, $log = false ) {
    $blad = isset( $pakket['manifest']['blad'] ) && is_array( $pakket['manifest']['blad'] ) ? $pakket['manifest']['blad'] : array();

    $ontbreekt = array();
    $kaart     = array();
    foreach ( ( isset( $blad['kleuren'] ) ? (array) $blad['kleuren'] : array() ) as $hex => $rol ) {
        $nieuw = sfp_zelftest_rol_hex( (string) $rol );
        if ( '' === $nieuw ) {
            $ontbreekt[] = $rol;
            continue;
        }
        $kaart[ strtoupper( (string) $hex ) ] = $nieuw;
    }
    if ( $kaart ) {
        $tekst = preg_replace_callback(
            '/#[0-9a-f]{6}(?![0-9a-f])/i',
            function ( $m ) use ( $kaart ) {
                $sleutel = strtoupper( $m[0] );
                return isset( $kaart[ $sleutel ] ) ? $kaart[ $sleutel ] : $m[0];
            },
            $tekst
        );
    }
    if ( $ontbreekt && $log ) {
        sfp_zelftest_log( $pakket['test'], 'fout', 'Kleurrol(len) niet gevonden op deze site, oorspronkelijke kleur gebruikt: ' . implode( ', ', array_unique( $ontbreekt ) ) . '.' );
    }

    $families = array();
    $kop      = function_exists( 'sfp_page_config_deelbeeld_familie' ) ? sfp_page_config_deelbeeld_familie( sfp_page_config_astra_option( 'headings-font-family' ) ) : '';
    $body     = function_exists( 'sfp_page_config_deelbeeld_familie' ) ? sfp_page_config_deelbeeld_familie( sfp_page_config_astra_option( 'body-font-family' ) ) : '';
    foreach ( ( isset( $blad['fonts'] ) ? (array) $blad['fonts'] : array() ) as $familie => $rol ) {
        $doel = 'kop' === $rol ? ( '' !== $kop ? $kop : $body ) : $body;
        if ( '' === $doel ) {
            $families[] = (string) $familie;
            continue;
        }
        $families[] = $doel;
        if ( 0 !== strcasecmp( (string) $familie, $doel ) ) {
            $tekst = preg_replace( '/(?<![A-Za-z])' . preg_quote( (string) $familie, '/' ) . '(?![A-Za-z])/', $doel, $tekst );
        }
    }
    return array(
        'tekst'    => $tekst,
        'families' => array_values( array_unique( $families ) ),
    );
}

/**
 * De voettekst van de pdf, als sjabloon voor Cloudflare.
 *
 * De Chrome van Cloudflare tekent de voettekst van het blad niet (CSS
 * @page-margevakken, 18-10-2026). Daarom zet Cloudflare hem zelf onder elke
 * pagina, uit manifest.blad.voet:
 *
 *   "voet": {
 *     "css":   "...",   opmaak voor .sfp-zt-voet, .links, .midden, .rechts
 *     "tekst": { "nl": { "links": "...", "midden": "{naam}", "rechts": "PAGINA {pagina} VAN {paginas}" }, ... }
 *   }
 *
 * Kleuren en fonts gaan door dezelfde vertaling als het blad.
 *
 * @param  array $pakket   Pakket.
 * @param  array $gegevens Gegevens van de persoon (naam).
 * @return string          HTML, of '' zonder voettekst.
 */
function sfp_zelftest_blad_voet( array $pakket, array $gegevens ) {
    $voet = isset( $pakket['manifest']['blad']['voet'] ) && is_array( $pakket['manifest']['blad']['voet'] ) ? $pakket['manifest']['blad']['voet'] : array();
    if ( empty( $voet['tekst'] ) || ! is_array( $voet['tekst'] ) ) {
        return '';
    }
    $taal  = sfp_zelftest_taal( $pakket );
    $tekst = isset( $voet['tekst'][ $taal ] ) && is_array( $voet['tekst'][ $taal ] ) ? $voet['tekst'][ $taal ] : reset( $voet['tekst'] );
    if ( ! is_array( $tekst ) ) {
        return '';
    }
    $vak = function ( $sleutel ) use ( $tekst, $gegevens ) {
        $t = isset( $tekst[ $sleutel ] ) ? esc_html( (string) $tekst[ $sleutel ] ) : '';
        return strtr(
            $t,
            array(
                '{naam}'    => esc_html( isset( $gegevens['naam'] ) ? (string) $gegevens['naam'] : '' ),
                '{pagina}'  => '<span class="pageNumber"></span>',
                '{paginas}' => '<span class="totalPages"></span>',
            )
        );
    };
    $css  = isset( $voet['css'] ) ? wp_strip_all_tags( (string) $voet['css'] ) : '';
    $merk = sfp_zelftest_merk( $pakket, $css );
    return '<style>' . sfp_zelftest_fonts_css( $merk['families'] )
        . 'html,body{margin:0;padding:0}*{font-variant-ligatures:none!important;font-feature-settings:"locl" 0!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}'
        . $merk['tekst'] . '</style>'
        . '<div class="sfp-zt-voet"><span class="links">' . $vak( 'links' ) . '</span><span class="midden">' . $vak( 'midden' ) . '</span><span class="rechts">' . $vak( 'rechts' ) . '</span></div>';
}

/**
 * Maak het blad klaar voor één persoon.
 *
 * @param  array $pakket   Pakket.
 * @param  array $gegevens naam, antwoorden, lengte, code, datum.
 * @return string|WP_Error HTML.
 */
function sfp_zelftest_blad_html( array $pakket, array $gegevens ) {
    $html = (string) $pakket['blad'];
    if ( '' === $html ) {
        return new WP_Error( 'sfp_zelftest_blad', 'Het pakket heeft geen blad.html.' );
    }
    $html = preg_replace( '#<link[^>]+fonts\.(googleapis|gstatic)\.com[^>]*>#i', '', $html );
    $merk = sfp_zelftest_merk( $pakket, $html, true );
    $html = $merk['tekst'];
    $fonts = sfp_zelftest_fonts_css( $merk['families'] );

    $data = array(
        'naam'       => (string) $gegevens['naam'],
        'antwoorden' => (array) $gegevens['antwoorden'],
        'lengte'     => (string) $gegevens['lengte'],
        'code'       => (string) $gegevens['code'],
        'datum'      => (string) $gegevens['datum'],
        'taal'       => sfp_zelftest_taal( $pakket ),
        'inhoud'     => $pakket['inhoud'],
    );
    $kop_html = '<style id="sfp-zt-fonts">' . $fonts . '</style>'
        . '<script>window.sfpZelftestBlad=' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';</script>'
        . '<script>' . str_ireplace( '</script', '<\/script', (string) $pakket['motor'] ) . "\nwindow.sfpZelftestMotor=MOTOR;</script>";

    if ( preg_match( '#<head[^>]*>#i', $html, $m, PREG_OFFSET_CAPTURE ) ) {
        $plek = $m[0][1] + strlen( $m[0][0] );
        // Na de charset, zodat die als eerste blijft staan.
        if ( preg_match( '#<meta[^>]+charset[^>]*>#i', $html, $c, PREG_OFFSET_CAPTURE, $plek ) && $c[0][1] < $plek + 400 ) {
            $plek = $c[0][1] + strlen( $c[0][0] );
        }
        $html = substr( $html, 0, $plek ) . $kop_html . substr( $html, $plek );
    } else {
        $html = $kop_html . $html;
    }

    // De Chrome van Cloudflare zet ligaturen ("fl") en de
    // Nederlandse IJ (OpenType locl) als één teken zonder tekst eronder, zodat
    // kopiëren en zoeken in de pdf een gat geven. Beide uit, met !important,
    // want een font-shorthand in het blad zet de ligaturen anders weer aan.
    // Met een voettekst uit het pakket tekent Cloudflare de voettekst; de
    // margevakken van het blad gaan dan uit, zodat een nieuwere Chrome hem
    // niet dubbel zet.
    $stijl = '*{font-variant-ligatures:none!important;font-feature-settings:"locl" 0!important}';
    if ( '' !== sfp_zelftest_blad_voet( $pakket, $gegevens ) ) {
        $vakken = array( 'top-left-corner', 'top-left', 'top-center', 'top-right', 'top-right-corner', 'right-top', 'right-middle', 'right-bottom', 'bottom-right-corner', 'bottom-right', 'bottom-center', 'bottom-left', 'bottom-left-corner', 'left-bottom', 'left-middle', 'left-top' );
        $stijl .= '@page{' . implode( '', array_map( function ( $v ) { return '@' . $v . '{content:none}'; }, $vakken ) ) . '}';
    }
    $stijl = '<style id="sfp-zt-pdf">' . $stijl . '</style>';
    $plek  = strripos( $html, '</body>' );
    $html  = false === $plek ? $html . $stijl : substr( $html, 0, $plek ) . $stijl . substr( $html, $plek );
    return $html;
}

/**
 * De gegevens van één rij voor het blad.
 *
 * @param  array $rij Rij.
 * @return array
 */
function sfp_zelftest_blad_gegevens( array $rij ) {
    return array(
        'naam'       => (string) $rij['voornaam'],
        'antwoorden' => sfp_zelftest_json( $rij, 'antwoorden' ),
        'lengte'     => (string) $rij['lengte'],
        'code'       => (string) $rij['code'],
        'datum'      => wp_date( 'j F Y', strtotime( $rij['afgerond_op'] . ' UTC' ) ),
    );
}

/* =========================================================================
 * Cloudflare
 * ====================================================================== */

/**
 * Een aanvraag bij Cloudflare Browser Run.
 *
 * @param  string $soort        pdf (het enige soort dat de module gebruikt).
 * @param  array  $body         Gegevens.
 * @param  bool   $mag_wachten  Mag deze aanvraag tot 10 seconden wachten op de vorige?
 * @return string|WP_Error      De inhoud (pdf of png).
 */
function sfp_zelftest_cloudflare_vraag( $soort, array $body, $mag_wachten = true ) {
    $cf = sfp_zelftest_cloudflare();
    if ( '' === $cf['account'] || '' === $cf['token'] ) {
        return new WP_Error( 'sfp_zelftest_cf_geen', 'Cloudflare is niet ingesteld (account-ID en token).' );
    }
    $pauze = (int) get_transient( 'sfp_pc_zt_cf_pauze' );
    if ( $pauze > time() ) {
        return new WP_Error( 'sfp_zelftest_cf_pauze', 'Cloudflare vroeg om te wachten tot ' . gmdate( 'H:i:s', $pauze ) . ' UTC.', array( 'tot' => $pauze ) );
    }
    $laatst = (float) get_transient( 'sfp_pc_zt_cf_laatst' );
    $wacht  = $laatst + 10.5 - microtime( true );
    if ( $wacht > 0 ) {
        if ( ! $mag_wachten ) {
            return new WP_Error( 'sfp_zelftest_cf_pauze', 'Te kort na de vorige aanvraag.', array( 'tot' => time() + (int) ceil( $wacht ) ) );
        }
        usleep( (int) ( min( $wacht, 11 ) * 1000000 ) );
    }
    set_transient( 'sfp_pc_zt_cf_laatst', microtime( true ), MINUTE_IN_SECONDS );

    $url      = 'https://api.cloudflare.com/client/v4/accounts/' . rawurlencode( $cf['account'] ) . '/browser-run/pdf';
    $antwoord = wp_remote_post(
        $url,
        array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $cf['token'],
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
        )
    );
    if ( is_wp_error( $antwoord ) ) {
        return $antwoord;
    }
    $status = (int) wp_remote_retrieve_response_code( $antwoord );
    $inhoud = (string) wp_remote_retrieve_body( $antwoord );
    if ( 429 === $status ) {
        $na  = (int) wp_remote_retrieve_header( $antwoord, 'retry-after' );
        $tot = time() + ( $na > 0 ? min( $na, DAY_IN_SECONDS ) : 60 );
        if ( false !== stripos( $inhoud, 'time limit' ) ) {
            // Daglimiet op: wachten tot de volgende dag in UTC.
            $tot = (int) strtotime( 'tomorrow', time() ) + 60;
        }
        set_transient( 'sfp_pc_zt_cf_pauze', $tot, max( 60, $tot - time() ) );
        return new WP_Error( 'sfp_zelftest_cf_pauze', 'Cloudflare: te veel aanvragen (429).', array( 'tot' => $tot ) );
    }
    if ( 200 !== $status ) {
        $melding = '';
        $json    = json_decode( $inhoud, true );
        if ( is_array( $json ) && ! empty( $json['errors'][0]['message'] ) ) {
            $melding = ': ' . mb_substr( (string) $json['errors'][0]['message'], 0, 200 );
        }
        return new WP_Error( 'sfp_zelftest_cf_fout', 'Cloudflare gaf HTTP ' . $status . $melding . '.' );
    }
    $verwacht = '%PDF';
    if ( 0 !== strpos( $inhoud, $verwacht ) ) {
        return new WP_Error( 'sfp_zelftest_cf_fout', 'Cloudflare gaf geen pdf terug.' );
    }
    return $inhoud;
}

/**
 * Maak een pdf van een klaargemaakt blad.
 *
 * @param  array  $pakket      Pakket.
 * @param  string $html        Blad.
 * @param  bool   $mag_wachten Zie sfp_zelftest_cloudflare_vraag().
 * @param  string $voet        Voettekst (sfp_zelftest_blad_voet()), of ''.
 * @return string|WP_Error     Pdf.
 */
function sfp_zelftest_maak_pdf( array $pakket, $html, $mag_wachten = true, $voet = '' ) {
    $schaal = isset( $pakket['manifest']['blad']['schaal'] ) ? (float) $pakket['manifest']['blad']['schaal'] : 0.8;
    $opties = array(
        'format'            => 'a4',
        'printBackground'   => true,
        'preferCSSPageSize' => true,
        'scale'             => ( $schaal >= 0.1 && $schaal <= 2 ) ? $schaal : 0.8,
        'timeout'           => 30000,
    );
    if ( '' !== $voet ) {
        $opties['displayHeaderFooter'] = true;
        $opties['headerTemplate']      = '<span></span>';
        $opties['footerTemplate']      = $voet;
    }
    return sfp_zelftest_cloudflare_vraag(
        'pdf',
        array(
            'html'        => $html,
            'pdfOptions'  => $opties,
            'gotoOptions' => array(
                'waitUntil' => 'networkidle0',
                'timeout'   => 45000,
            ),
        ),
        $mag_wachten
    );
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
function sfp_zelftest_pdf_pad( array $rij ) {
    if ( ! preg_match( '/^[a-f0-9]{32}\.pdf$/', (string) $rij['pdf_bestand'] ) ) {
        return '';
    }
    $map = sfp_zelftest_map( 'pdf' );
    return ( '' !== $map && is_readable( $map . $rij['pdf_bestand'] ) ) ? $map . $rij['pdf_bestand'] : '';
}

/**
 * Maak de pdf van één rij, als hij aan de beurt is. Bij een fout opnieuw na
 * 1, 5, 30 en 120 minuten; daarna 'mislukt' (de eerste klik op de link
 * probeert het dan nog een keer).
 *
 * @param  int  $id          Rij.
 * @param  bool $forceer     Ook als hij nog niet aan de beurt is (klik op de link).
 * @param  bool $mag_wachten Mag tot 10 seconden wachten op de vorige aanvraag (niet bij een bezoeker die wacht).
 * @return true|WP_Error|null true als de pdf er is, null als er niets te doen was.
 */
function sfp_zelftest_pdf_verwerk( $id, $forceer = false, $mag_wachten = true ) {
    global $wpdb;
    if ( ! sfp_zelftest_tabel_klaar() ) {
        return null;
    }
    $tabel = sfp_zelftest_tabel();
    $nu    = sfp_zelftest_nu();
    // Claim: wacht (aan de beurt), bezig maar vastgelopen, of bij een klik ook mislukt.
    $status_sql = $forceer ? "pdf_status IN ('wacht','mislukt','uitgesteld') OR (pdf_status = 'bezig' AND pdf_volgende <= %s)" : "(pdf_status = 'wacht' AND (pdf_volgende IS NULL OR pdf_volgende <= %s)) OR (pdf_status = 'bezig' AND pdf_volgende <= %s)";
    $args       = $forceer ? array( sfp_zelftest_nu( 150 ), (int) $id, $nu ) : array( sfp_zelftest_nu( 150 ), (int) $id, $nu, $nu );
    $claimed    = $wpdb->query( $wpdb->prepare( "UPDATE {$tabel} SET pdf_status = 'bezig', pdf_volgende = %s WHERE id = %d AND geanonimiseerd = 0 AND ({$status_sql})", $args ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    if ( ! $claimed ) {
        return null;
    }
    $rij    = sfp_zelftest_rij_op( 'id', (string) (int) $id );
    $pakket = $rij ? sfp_zelftest_pakket( $rij['test'] ) : null;
    if ( ! $rij || ! $pakket || '' === $pakket['blad'] ) {
        if ( $rij ) {
            sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'geen', 'pdf_volgende' => null ) );
        }
        return null;
    }
    // Na de geldigheid van de link heeft een pdf geen zin meer: geen aanvraag bij Cloudflare.
    if ( strtotime( $rij['afgerond_op'] . ' UTC' ) + (int) $pakket['manifest']['geldigheid_dagen'] * DAY_IN_SECONDS < time() ) {
        sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'verlopen', 'pdf_volgende' => null ) );
        return null;
    }

    $gegevens = sfp_zelftest_blad_gegevens( $rij );
    $html     = sfp_zelftest_blad_html( $pakket, $gegevens );
    $pdf      = is_wp_error( $html ) ? $html : sfp_zelftest_maak_pdf( $pakket, $html, $mag_wachten, sfp_zelftest_blad_voet( $pakket, $gegevens ) );
    if ( ! is_wp_error( $pdf ) ) {
        $map   = sfp_zelftest_map( 'pdf' );
        $naam  = bin2hex( random_bytes( 16 ) ) . '.pdf';
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- eigen werkmap.
        if ( '' !== $map && false !== file_put_contents( $map . $naam, $pdf ) ) {
            $oud = sfp_zelftest_pdf_pad( $rij );
            if ( '' !== $oud ) {
                wp_delete_file( $oud );
            }
            sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'klaar', 'pdf_bestand' => $naam, 'pdf_pogingen' => 0, 'pdf_volgende' => null ) );
            return true;
        }
        $pdf = new WP_Error( 'sfp_zelftest_map', 'De pdf kon niet worden opgeslagen.' );
    }

    // Cloudflare nog niet ingesteld: uitgesteld tot de gegevens er zijn (geen poging, geen cron).
    if ( 'sfp_zelftest_cf_geen' === $pdf->get_error_code() ) {
        sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'uitgesteld', 'pdf_volgende' => null ) );
        return $pdf;
    }
    // Pauze bij Cloudflare telt niet als poging.
    if ( 'sfp_zelftest_cf_pauze' === $pdf->get_error_code() ) {
        $data = $pdf->get_error_data();
        $tot  = is_array( $data ) && ! empty( $data['tot'] ) ? (int) $data['tot'] : time() + 60;
        sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'wacht', 'pdf_volgende' => gmdate( 'Y-m-d H:i:s', $tot ) ) );
        sfp_zelftest_cron_plannen();
        return $pdf;
    }
    $poging = (int) $rij['pdf_pogingen'] + 1;
    $wacht  = sfp_zelftest_wachttijd( $poging );
    if ( null === $wacht ) {
        sfp_zelftest_log( $rij['test'], 'fout', 'Pdf definitief mislukt na 5 pogingen (rij ' . (int) $rij['id'] . '): ' . $pdf->get_error_message() );
        sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'mislukt', 'pdf_pogingen' => 0, 'pdf_volgende' => null ) );
    } else {
        sfp_zelftest_log( $rij['test'], 'fout', 'Pdf mislukt, poging ' . $poging . ' (rij ' . (int) $rij['id'] . '): ' . $pdf->get_error_message() );
        sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'wacht', 'pdf_pogingen' => $poging, 'pdf_volgende' => sfp_zelftest_nu( $wacht ) ) );
        sfp_zelftest_cron_plannen();
    }
    return $pdf;
}

/**
 * Zet pdf's die wachtten op de gegevens van Cloudflare terug in de wachtrij
 * (alleen die nog binnen de geldigheid van hun link vallen; de rest maakt de
 * wachtrij af als verlopen).
 *
 * @return void
 */
function sfp_zelftest_uitgesteld_hervat() {
    global $wpdb;
    if ( ! sfp_zelftest_tabel_klaar() ) {
        return;
    }
    $gewijzigd = $wpdb->query( "UPDATE " . sfp_zelftest_tabel() . " SET pdf_status = 'wacht', pdf_volgende = NULL WHERE pdf_status = 'uitgesteld' AND geanonimiseerd = 0" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
    if ( $gewijzigd ) {
        sfp_zelftest_cron_plannen();
    }
}
