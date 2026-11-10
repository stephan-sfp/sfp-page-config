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
    $html = '<table class="bedragen"><tbody>';
    foreach ( $o['regels'] as $r ) {
        $label = $t( 'regel_' . $r['code'] );
        $prijs = $r['prijs'] / 100;
        $html .= '<tr><td>' . esc_html( $label ) . '</td><td class="n">' . (int) $r['aantal'] . ' x</td><td class="n">'
            . esc_html( 0 === $r['prijs'] % 100 ? sfp_calculator_bedrag( (int) $prijs, $valuta ) : str_replace( ' ', ' ', sfp_calculator_bedrag( (int) floor( $prijs ), $valuta ) ) . ',' . str_pad( (string) ( abs( $r['prijs'] ) % 100 ), 2, '0', STR_PAD_LEFT ) )
            . '</td><td class="n">' . ( $r['gratis'] ? esc_html( $t( 'inbegrepen' ) ) : esc_html( sfp_calculator_bedrag( (int) $r['bedrag'], $valuta ) ) ) . '</td></tr>';
    }
    $html .= '</tbody><tfoot><tr class="totaal"><td colspan="3">' . esc_html( ( ! empty( $u['richtprijs'] ) ? $t( 'richtprijs' ) : $t( 'totaal' ) ) . ' ' . $t( 'excl' ) ) . '</td><td class="n">' . esc_html( sfp_calculator_bedrag( (int) $o['totaal_excl'], $valuta ) ) . '</td></tr>';
    if ( $volledig ) {
        foreach ( (array) $o['btw'] as $tarief => $bedrag ) {
            if ( 0 === (int) $bedrag ) {
                continue;
            }
            $html .= '<tr><td colspan="3">' . esc_html( $t( 'btw_regel', array( 'n' => $tarief ) ) ) . '</td><td class="n">' . esc_html( sfp_calculator_bedrag( (int) $bedrag, $valuta ) ) . '</td></tr>';
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
 * Het blad van een begroting.
 *
 * @param  array $rij Rij.
 * @return string     HTML.
 */
function sfp_calculator_blad_html( array $rij ) {
    $taal    = (string) $rij['taal'];
    $inst    = sfp_calculator_instellingen();
    $bedrijf = function_exists( 'sfp_page_config_bedrijf' ) ? sfp_page_config_bedrijf() : array();
    $keuzes  = sfp_calculator_json( $rij, 'keuzes' );
    $sit     = sfp_calculator_json( $rij, 'situatie' );
    $ber     = sfp_calculator_json( $rij, 'berekening' );
    $u       = isset( $ber['uitkomst'] ) ? $ber['uitkomst'] : array();
    $o       = isset( $u['uitkomst'] ) && is_array( $u['uitkomst'] ) ? $u['uitkomst'] : array();
    $valuta  = isset( $ber['norm']['valuta'] ) ? $ber['norm']['valuta'] : 'EUR';
    $t       = function ( $s, $v = array() ) use ( $taal ) {
        return sfp_calculator_t( $s, $v, $taal );
    };
    $e       = function ( $s ) {
        return esc_html( (string) $s );
    };
    $datum   = function ( $ymd ) use ( $taal ) {
        $ts = strtotime( $ymd . ' 12:00:00' );
        return wp_date( 'nl' === $taal ? 'j F Y' : 'j F Y', $ts );
    };
    $vandaag = wp_date( 'Y-m-d', strtotime( ( $rij['afgerond_op'] ? $rij['afgerond_op'] : $rij['gestart_op'] ) . ' UTC' ) );

    // Kleuren en fonts van de site.
    $vars = '';
    foreach ( array( 'primair', 'secundair', 'links', 'tint-1', 'tint-2', 'tint-3', 'body' ) as $rol ) {
        $hex = function_exists( 'sfp_zelftest_rol_hex' ) ? sfp_zelftest_rol_hex( $rol ) : '';
        if ( '' !== $hex ) {
            $vars .= '--' . $rol . ':' . $hex . ';';
        }
    }
    $kop_font   = function_exists( 'sfp_page_config_deelbeeld_familie' ) ? sfp_page_config_deelbeeld_familie( sfp_page_config_astra_option( 'headings-font-family' ) ) : '';
    $tekst_font = function_exists( 'sfp_page_config_deelbeeld_familie' ) ? sfp_page_config_deelbeeld_familie( sfp_page_config_astra_option( 'body-font-family' ) ) : '';
    $families   = array_values( array_filter( array( $kop_font, $tekst_font ), 'strlen' ) );
    $fonts      = function_exists( 'sfp_zelftest_fonts_css' ) ? sfp_zelftest_fonts_css( $families ) : '';
    $ff         = function ( $f ) {
        return '' !== $f ? '"' . str_replace( '"', '', $f ) . '",' : '';
    };
    $css = '@page{size:A4;margin:18mm 16mm 20mm}'
        . ':root{' . $vars . '}'
        . 'html,body{margin:0;padding:0}'
        . 'body{font-family:' . $ff( $tekst_font ) . 'system-ui,sans-serif;font-size:10.5pt;line-height:1.5;color:var(--body,#222)}'
        . 'h1,h2,h3{font-family:' . $ff( $kop_font ) . 'system-ui,sans-serif;color:var(--primair,#222);line-height:1.2;margin:0 0 .4em}'
        . 'h1{font-size:22pt}h2{font-size:13pt;margin-top:0}h3{font-size:11pt}'
        . '.kop{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid var(--primair,#222);padding-bottom:10px;margin-bottom:18px}'
        . '.kop img{max-height:46px;max-width:200px}'
        . '.meta{font-size:9pt;text-align:right}.meta b{display:block}'
        . '.blok{margin:0 0 16px;break-inside:avoid}'
        . '.beslisser{background:var(--tint-1,#f3f3f3);border-left:4px solid var(--secundair,var(--primair,#222));padding:14px 16px;margin-bottom:18px}'
        . '.groot{font-size:20pt;font-family:' . $ff( $kop_font ) . 'system-ui,sans-serif;color:var(--primair,#222);margin:6px 0}'
        . 'table{width:100%;border-collapse:collapse;font-size:10pt}td{padding:5px 4px;border-bottom:1px solid var(--tint-2,#ddd);vertical-align:top}'
        . 'td.n{text-align:right;white-space:nowrap}tfoot td{border-bottom:0}tr.totaal td,tr.incl td{font-weight:700;border-top:2px solid var(--primair,#222)}'
        . '.klein{font-size:9pt;color:var(--body,#444)}ul{margin:.2em 0 .6em;padding-left:1.2em}'
        . '.contact{display:flex;gap:14px;align-items:flex-start}.contact img{width:72px;height:72px;border-radius:50%;object-fit:cover}'
        . '.twee{display:flex;gap:24px}.twee>div{flex:1}'
        . 'blockquote{margin:0;padding:10px 14px;border-left:4px solid var(--secundair,var(--primair,#222));font-style:italic}'
        . '.proef{position:fixed;top:40%;left:0;right:0;text-align:center;font-size:90pt;color:rgba(200,0,0,.12);transform:rotate(-24deg);z-index:9}'
        . '*{font-variant-ligatures:none!important;font-feature-settings:"locl" 0!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}';

    // Logo: eigen logo van de calculator, anders het logo van de site.
    $logo_id = (int) $inst['logo'] > 0 ? (int) $inst['logo'] : (int) get_theme_mod( 'custom_logo' );
    $logo    = '';
    if ( $logo_id > 0 ) {
        $pad = get_attached_file( $logo_id );
        if ( $pad && is_readable( $pad ) && filesize( $pad ) < 400000 ) {
            $mime = (string) get_post_mime_type( $logo_id );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- eigen mediabestand inline.
            $logo = '<img alt="" src="data:' . esc_attr( $mime ) . ';base64,' . base64_encode( (string) file_get_contents( $pad ) ) . '">';
        }
    }
    if ( '' === $logo ) {
        $logo = '<strong>' . $e( '' !== ( $bedrijf['label'] ?? '' ) ? $bedrijf['label'] : get_bloginfo( 'name' ) ) . '</strong>';
    }

    $proef = ! empty( $rij['proef'] ) ? '<div class="proef">' . $e( $t( 'proef' ) ) . '</div>' : '';
    $naam  = trim( $rij['voornaam'] . ' ' . $rij['achternaam'] );
    $wie   = implode( ', ', array_filter( array( (string) $rij['organisatie'], (string) ( $rij['afdeling'] ?? '' ) ), 'strlen' ) );

    // 1. Voor wie beslist.
    $samenvat = '';
    if ( 'A' === $rij['route'] && $o ) {
        $m       = isset( $u['mensen'] ) ? $u['mensen'] : array();
        $mensen  = 'marge' === ( $m['soort'] ?? '' ) ? $m['van'] . ' - ' . $m['tot'] : ( 'onbekend' === ( $m['soort'] ?? '' ) ? $t( 'mensen_onbekend' ) : (string) ( $m['aantal'] ?? '' ) );
        $groepen = 1 === (int) $o['groepen'] ? $t( 'groep' ) : $t( 'groepen', array( 'n' => $o['groepen'] ) );
        $samenvat = '<p>' . $e( $t( 'vorm_' . $keuzes['vorm'] ) . ', ' . $groepen . ' (' . $t( 'mensen_aantal' ) . ': ' . $mensen . ')' ) . '</p>'
            . '<p class="groot">' . $e( sfp_calculator_bedrag( (int) $o['totaal_excl'], $valuta ) ) . ' <span class="klein">' . $e( $t( 'excl' ) ) . '</span></p>'
            . ( ! empty( $u['richtprijs'] ) ? '<p><b>' . $e( $t( 'pdf_maatwerk' ) ) . '</b></p>' : '' );
    } elseif ( 'B' === $rij['route'] ) {
        $samenvat = '<p>' . $e( $t( 'doel_evenement' ) . ', ' . (int) $keuzes['sprekers'] . ' ' . strtolower( $t( 'sprekers_vraag' ) ) ) . '</p>'
            . '<p class="groot">' . $e( $t( 'evenement_prijs', array( 'prijs' => sfp_calculator_bedrag( (int) ( $u['evenement_prijs'] ?? 0 ), $valuta ) ) ) ) . '</p>'
            . '<p><b>' . $e( $t( 'pdf_maatwerk' ) ) . '</b></p>';
    }
    if ( '' !== ( $sit['doel'] ?? '' ) ) {
        $samenvat .= '<p><b>' . $e( $t( 'pdf_doel' ) ) . ':</b> ' . nl2br( $e( $sit['doel'] ) ) . '</p>';
    }
    $samenvat .= '<p>' . $e( $t( 'pdf_volgende_zin' ) ) . '</p>';
    $html      = '<div class="beslisser"><h2>' . $e( $t( 'pdf_beslisser' ) ) . '</h2>' . $samenvat . '</div>';

    // 2. Situatie en vorig traject.
    $situ = '';
    if ( ! empty( $sit['waarom'] ) ) {
        $situ .= '<p><b>' . $e( $t( 'pdf_waarom' ) ) . ':</b> ' . $e( implode( ', ', array_map( function ( $w ) use ( $t ) { return $t( 'waarom_' . $w ); }, $sit['waarom'] ) ) ) . '</p>';
    }
    if ( ! empty( $keuzes['doelgroep'] ) ) {
        $situ .= '<p><b>' . $e( $t( 'doelgroep_vraag' ) ) . '</b> ' . $e( $t( 'doelgroep_' . $keuzes['doelgroep'] ) . ( 'anders' === $keuzes['doelgroep'] ? ' ' . $keuzes['doelgroep_anders'] : '' ) ) . '</p>';
    }
    if ( ! empty( $keuzes['fase'] ) ) {
        $situ .= '<p><b>' . $e( $t( 'pdf_fase' ) ) . ':</b> ' . $e( $t( 'fase_' . $keuzes['fase'] ) ) . '</p>';
    }
    if ( ! empty( $keuzes['uitvraag'] ) ) {
        $situ .= '<p><b>' . $e( $t( 'pdf_uitvraag' ) ) . ':</b> ' . ( '' !== ( $sit['criteria'] ?? '' ) ? nl2br( $e( $sit['criteria'] ) ) : $e( $t( 'ja' ) ) ) . '</p>';
    }
    foreach ( sfp_calculator_intern_regels( $keuzes, $taal, $valuta ) as $label => $waarde ) {
        $situ .= '<p><b>' . $e( $label ) . ':</b> ' . nl2br( $e( $waarde ) ) . '</p>';
    }
    $html .= sfp_calculator_blad_blok( $t( 'pdf_situatie' ), $situ );

    // 4. Programma (alleen route A).
    if ( 'A' === $rij['route'] && ! empty( $keuzes['vorm'] ) ) {
        $prog  = '<p>' . $e( $t( 'pdf_opbouw' ) ) . ( '' !== $inst['methode'] ? ' ' . $e( $t( 'pdf_methode', array( 'n' => $inst['methode'] ) ) ) : '' ) . '</p>';
        $prog .= sfp_calculator_blad_lijst( $inst['programma'][ (string) $keuzes['vorm'] ] ?? '' );
        if ( ! empty( $keuzes['doelgroep'] ) && '' !== ( $inst['doelgroepzin'][ $keuzes['doelgroep'] ] ?? '' ) ) {
            $prog .= '<p><b>' . $e( $t( 'pdf_doelgroep' ) ) . ':</b> ' . $e( $inst['doelgroepzin'][ $keuzes['doelgroep'] ] ) . '</p>';
        }
        $html .= sfp_calculator_blad_blok( $t( 'pdf_programma' ), $prog );
        // 6. Groepsgrootte.
        $html .= sfp_calculator_blad_blok( $t( 'pdf_groepsgrootte' ), '<p>' . $e( $t( 'pdf_groepsgrootte_zin' ) ) . ( $o ? ' ' . $e( $t( 'verdeling', array( 'n' => implode( ', ', (array) $o['verdeling'] ) ) ) ) : '' ) . '</p>' );
        // 7. Inbegrepen en niet.
        $html .= '<div class="twee blok"><div><h3>' . $e( $t( 'pdf_inbegrepen' ) ) . '</h3>' . sfp_calculator_blad_lijst( $t( 'pdf_inbegrepen_lijst' ) ) . '</div><div><h3>' . $e( $t( 'pdf_niet' ) ) . '</h3>' . sfp_calculator_blad_lijst( $t( 'pdf_niet_lijst' ) ) . '</div></div>';
        // 8. Begroting.
        $loc = '';
        if ( ! empty( $keuzes['locatie'] ) ) {
            $loc = '<p class="klein"><b>' . $e( $t( 'pdf_locatie' ) ) . ' (' . $t( 'op_aanvraag' ) . '):</b> ' . $e( implode( ', ', array_map( function ( $l ) use ( $t ) { return $t( 'locatie_' . $l ); }, $keuzes['locatie'] ) ) ) . '</p>';
        } else {
            $loc = '<p class="klein"><b>' . $e( $t( 'pdf_locatie' ) ) . ':</b> ' . $e( $t( 'locatie_klant' ) ) . '</p>';
        }
        $html .= sfp_calculator_blad_blok( $t( 'begroting' ), sfp_calculator_blad_tabel( $rij, true ) . $loc );
    }

    // 5. Contactpersoon.
    $c = $inst['contact'];
    if ( '' !== $c['naam'] ) {
        $foto = '';
        if ( (int) $c['foto'] > 0 ) {
            $pad = get_attached_file( (int) $c['foto'] );
            $klein = image_get_intermediate_size( (int) $c['foto'], 'thumbnail' );
            if ( $klein && ! empty( $klein['path'] ) ) {
                $pad = trailingslashit( wp_get_upload_dir()['basedir'] ) . $klein['path'];
            }
            if ( $pad && is_readable( $pad ) && filesize( $pad ) < 300000 ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- eigen mediabestand inline.
                $foto = '<img alt="" src="data:' . esc_attr( (string) get_post_mime_type( (int) $c['foto'] ) ) . ';base64,' . base64_encode( (string) file_get_contents( $pad ) ) . '">';
            }
        }
        $html .= sfp_calculator_blad_blok(
            $t( 'pdf_contact' ),
            '<div class="contact">' . $foto . '<div><b>' . $e( $c['naam'] ) . '</b>' . ( '' !== $c['functie'] ? ', ' . $e( $c['functie'] ) : '' )
            . ( '' !== $c['profiel'] ? '<p>' . nl2br( $e( $c['profiel'] ) ) . '</p>' : '' )
            . '<p class="klein">' . $e( implode( ' | ', array_filter( array( $c['telefoon'], $c['email'] ), 'strlen' ) ) ) . '</p></div></div>'
        );
    }

    // 12. Referentie.
    $r = $inst['referentie'];
    if ( '' !== $r['citaat'] ) {
        $html .= sfp_calculator_blad_blok( $t( 'pdf_referentie' ), '<blockquote>' . nl2br( $e( $r['citaat'] ) ) . '</blockquote><p class="klein">' . $e( implode( ', ', array_filter( array( $r['naam'], $r['functie'] ), 'strlen' ) ) ) . '</p>' );
    }

    // 9. Voorwaarden.
    $vw     = '<p>' . $e( $t( 'pdf_voorwaarden_zin' ) ) . '</p><p>' . $e( $t( 'pdf_geldig' ) ) . ': ' . $e( $datum( sfp_calculator_geldig_tot( $rij ) ) ) . '.</p>';
    $vw_url = sfp_calculator_url( 'voorwaarden' );
    if ( '' !== $vw_url ) {
        $vw .= '<p class="klein">' . $e( $t( 'pdf_voorwaarden_link' ) ) . ': <a href="' . esc_url( $vw_url ) . '">' . $e( $vw_url ) . '</a></p>';
    }
    $html .= sfp_calculator_blad_blok( $t( 'pdf_voorwaarden' ), $vw );

    // 10. Leveranciersgegevens, met het blok voor inkoop.
    $lev = '';
    foreach ( sfp_page_config_bedrijf_velden() as $sleutel => $label ) {
        if ( 'label' !== $sleutel && '' !== ( $bedrijf[ $sleutel ] ?? '' ) ) {
            $lev .= '<tr><td>' . $e( $label ) . '</td><td>' . nl2br( $e( $bedrijf[ $sleutel ] ) ) . '</td></tr>';
        }
    }
    $lev = '' !== $lev ? '<table>' . $lev . '</table>' : '';
    if ( ! empty( $keuzes['inkoop']['aan'] ) ) {
        $ink = '';
        foreach ( array( 'po', 'kostenplaats', 'referentie', 'factuuradres' ) as $veld ) {
            if ( '' !== ( $keuzes['inkoop'][ $veld ] ?? '' ) ) {
                $ink .= '<tr><td>' . $e( $t( 'inkoop_' . $veld ) ) . '</td><td>' . nl2br( $e( $keuzes['inkoop'][ $veld ] ) ) . '</td></tr>';
            }
        }
        if ( '' !== $naam || '' !== $wie ) {
            $ink = '<tr><td>' . $e( $t( 'pdf_beslisser' ) ) . '</td><td>' . $e( implode( ', ', array_filter( array( $naam, $wie ), 'strlen' ) ) ) . '</td></tr>' . $ink;
        }
        $html .= sfp_calculator_blad_blok( $t( 'pdf_inkoop' ), '<div class="twee"><div><h3>' . $e( $t( 'pdf_leverancier' ) ) . '</h3>' . $lev . '</div><div>' . ( '' !== $ink ? '<table>' . $ink . '</table>' : '' ) . '</div></div>' );
    } else {
        $html .= sfp_calculator_blad_blok( $t( 'pdf_leverancier' ), $lev );
    }

    // 11 en 13. Aankondiging en volgende stap.
    $volgende = '<p>' . $e( $t( 'pdf_volgende_zin' ) ) . '</p>';
    $boek     = sfp_calculator_url( 'boekpagina' );
    if ( '' !== $boek ) {
        $volgende .= '<p><a href="' . esc_url( add_query_arg( array( 'kenmerk' => $rij['kenmerk'] ), $boek ) ) . '">' . $e( $t( 'knop_gesprek' ) ) . '</a></p>';
    }
    $volgende .= '<p class="klein">' . $e( $t( 'pdf_aankondiging' ) ) . '</p>';
    $html     .= sfp_calculator_blad_blok( $t( 'pdf_volgende' ), $volgende );

    $kop = '<header class="kop"><div>' . $logo . '<h1>' . $e( $t( 'pdf_titel' ) ) . '</h1>'
        . ( '' !== $naam || '' !== $wie ? '<div>' . $e( implode( ', ', array_filter( array( $naam, $wie ), 'strlen' ) ) ) . '</div>' : '' )
        . '</div><div class="meta"><b>' . $e( $t( 'pdf_kenmerk' ) ) . '</b>' . $e( $rij['kenmerk'] ) . '<b>' . $e( $t( 'pdf_datum' ) ) . '</b>' . $e( $datum( $vandaag ) )
        . '<b>' . $e( $t( 'pdf_geldig' ) ) . '</b>' . $e( $datum( sfp_calculator_geldig_tot( $rij ) ) ) . '</div></header>';

    return '<!doctype html><html lang="' . esc_attr( $taal ) . '"><head><meta charset="utf-8"><title>' . $e( $t( 'pdf_titel' ) . ' ' . $rij['kenmerk'] ) . '</title>'
        . '<style>' . $fonts . $css . '</style></head><body>' . $proef . $kop . $html . '</body></html>';
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
