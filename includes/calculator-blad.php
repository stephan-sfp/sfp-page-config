<?php
/**
 * SFP Page Config - Incompanycalculator: het blad van de begroting
 *
 * Sinds 3.45.0 opgebouwd als de pdf van de zelftest: een voorblad met de kern
 * (totaal, wat erin zit, geldig tot), daarna genummerde hoofdstukken en een
 * bijlage voor inkoop. Kleuren en fonts uit de kleurrollen en Astra; geen
 * merkkleur of fontnaam in de code. Koppen, labels en knoppen in hoofdletters
 * via text-transform (branding.md, Hoofdlettergebruik). De actiekleur alleen
 * op de knop.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De kleuren van de site als CSS-variabelen, plus de hexwaarden.
 *
 * @return array{css: string, hex: array<string, string>}
 */
function sfp_calculator_blad_kleuren() {
    $hex = array();
    $css = '';
    foreach ( array( 'primair', 'secundair', 'links', 'tint-1', 'tint-2', 'tint-3', 'body' ) as $rol ) {
        $w = function_exists( 'sfp_zelftest_rol_hex' ) ? sfp_zelftest_rol_hex( $rol ) : '';
        if ( '' !== $w ) {
            $hex[ $rol ] = $w;
            $css        .= '--' . $rol . ':' . $w . ';';
        }
    }
    return array(
        'css' => $css,
        'hex' => $hex,
    );
}

/**
 * Een mediabestand inline als data-URI, of ''.
 *
 * @param  int    $id    Bijlage.
 * @param  string $maat  Afbeeldingsmaat.
 * @param  int    $max   Maximale bestandsgrootte in bytes.
 * @return string
 */
function sfp_calculator_blad_beeld( $id, $maat = 'full', $max = 400000 ) {
    $id = (int) $id;
    if ( $id <= 0 ) {
        return '';
    }
    $pad = get_attached_file( $id );
    if ( 'full' !== $maat ) {
        $klein = image_get_intermediate_size( $id, $maat );
        if ( $klein && ! empty( $klein['path'] ) ) {
            $pad = trailingslashit( wp_get_upload_dir()['basedir'] ) . $klein['path'];
        }
    }
    if ( ! $pad || ! is_readable( $pad ) || filesize( $pad ) > $max ) {
        return '';
    }
    $mime = (string) get_post_mime_type( $id );
    if ( 'full' !== $maat && preg_match( '/\.(png|jpe?g|webp|gif)$/i', $pad, $m ) ) {
        $mime = 'image/' . ( 'jpg' === strtolower( $m[1] ) ? 'jpeg' : strtolower( $m[1] ) );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- eigen mediabestand inline.
    return 'data:' . $mime . ';base64,' . base64_encode( (string) file_get_contents( $pad ) );
}

/**
 * Het programma als dagen: regels die met "Dag 1" (of "Day 1") beginnen,
 * worden per dag gegroepeerd. Anders één lijst.
 *
 * @param  string $tekst Regels uit SFP Beheer.
 * @return array<int|string, string[]> dag => regels; sleutel 0 = zonder dag.
 */
function sfp_calculator_blad_dagen( $tekst ) {
    $dagen = array();
    foreach ( preg_split( '/\r\n|\r|\n/', (string) $tekst ) as $regel ) {
        $regel = trim( $regel );
        if ( '' === $regel ) {
            continue;
        }
        if ( preg_match( '/^(?:dag|day|dia)\s*(\d+)\s*[,:\-]?\s*(.*)$/iu', $regel, $m ) ) {
            $rest                    = trim( $m[2] );
            $dagen[ (int) $m[1] ][] = '' !== $rest ? mb_strtoupper( mb_substr( $rest, 0, 1 ) ) . mb_substr( $rest, 1 ) : '';
        } else {
            $dagen[0][] = $regel;
        }
    }
    foreach ( $dagen as $d => $r ) {
        $dagen[ $d ] = array_values( array_filter( $r, 'strlen' ) );
    }
    ksort( $dagen );
    return $dagen;
}

/**
 * De stijl van het blad.
 *
 * @param  string $vars     CSS-variabelen.
 * @param  string $kop_font Kopfont (of '').
 * @param  string $tekst_font Bodyfont (of '').
 * @return string
 */
function sfp_calculator_blad_stijl( $vars, $kop_font, $tekst_font ) {
    $ff = function ( $f ) {
        return '' !== $f ? '"' . str_replace( '"', '', $f ) . '",' : '';
    };
    $k = $ff( $kop_font ) . 'system-ui,sans-serif';
    $b = $ff( $tekst_font ) . 'system-ui,sans-serif';
    return '@page{size:A4;margin:16mm 16mm 18mm}'
        . ':root{--primair:#222;--secundair:#222;--links:#222;--tint-1:#eee;--tint-2:#eee;--tint-3:#f7f7f7;--body:#575757;' . $vars . '--lijn:color-mix(in srgb,var(--primair) 18%,transparent);--r:6px}'
        . 'html,body{margin:0;padding:0}'
        . 'body{font:400 10pt/1.55 ' . $b . ';color:var(--body)}'
        . 'p{margin:0 0 6pt}'
        . 'h1,h2,h3,.lab,.knop,th{font-family:' . $k . ';font-weight:900;text-transform:uppercase;color:var(--primair)}'
        . 'h1{font-size:22pt;line-height:1.12;margin:0;letter-spacing:.01em;text-wrap:balance}'
        . 'h2{break-after:avoid;font-size:13pt;line-height:1.2;margin:0 0 4pt;letter-spacing:.02em;display:flex;gap:8pt;align-items:baseline}'
        . 'h2 .nr{color:var(--secundair)}'
        . 'h3{font-size:9pt;letter-spacing:.06em;margin:0 0 6pt}'
        . '.lab{display:block;font-size:7pt;letter-spacing:.08em;margin:0 0 3pt}'
        . '.inleiding{margin:0 0 12pt;break-after:avoid}'
        . '.kernzin{font:700 13pt/1.4 ' . $b . ';color:var(--primair);border-left:3pt solid var(--secundair);padding:1pt 0 1pt 10pt;margin:12pt 0 0;max-width:150mm}'
        . '.hoofdstuk{break-before:page}'
        . '.hoofdstuk.zelfde{break-before:auto;margin-top:22pt}'
        . '.vak{background:var(--tint-3);border-radius:var(--r);padding:12pt 14pt;break-inside:avoid;margin:0 0 10pt}'
        . '.vak.lijn{border-top:2pt solid var(--secundair);border-radius:0 0 var(--r) var(--r)}'
        . '.klein{font-size:8.5pt}'
        // Voorblad.
        . '.voorblad{display:block}'
        . '.top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:26pt}'
        . '.top img{max-height:52pt;max-width:170pt}'
        . '.top .meta{font-size:7.5pt;line-height:1.5}.top .meta b{font-family:' . $k . ';font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:var(--primair)}'
        . '.top .meta .w{color:var(--primair)}'
        . '.bon{margin-top:22pt;border:1.2pt solid var(--lijn);border-top:3pt solid var(--primair);border-radius:0 0 var(--r) var(--r);padding:16pt 18pt;display:grid;grid-template-columns:1fr 1fr;gap:16pt;break-inside:avoid}'
        . '.groot{font:900 30pt/1 ' . $k . ';color:var(--primair);margin:2pt 0 4pt;white-space:nowrap}'
        . '.groot small{font:400 9pt ' . $b . ';color:var(--body);margin-left:4pt}'
        . '.vink{list-style:none;margin:0;padding:0}'
        . '.vink li{position:relative;padding-left:15pt;margin:0 0 4pt;color:var(--primair)}'
        . '.vink li:before{content:"";position:absolute;left:2pt;top:2.5pt;width:4pt;height:7.5pt;border:solid var(--secundair);border-width:0 1.6pt 1.6pt 0;transform:rotate(45deg)}'
        . '.feiten{display:flex;gap:18pt;margin-top:10pt;font-size:8.5pt}.feiten b{display:block}'
        . '.maatwerk{margin-top:8pt;font-weight:700;color:var(--primair)}'
        . ''
        // Doel.
        . '.antwoorden{display:grid;grid-template-columns:1fr 1fr;gap:10pt}'
        . '.antwoorden .vak{margin:0}.antwoorden .breed{grid-column:1/-1}'
        . '.antwoorden p{color:var(--primair);margin:0}'
        // Programma.
        . '.dagen{display:grid;grid-template-columns:repeat(3,1fr);gap:10pt;margin-bottom:12pt}'
        . '.dagen .vak{margin:0}.dagen .nr{display:inline-block;background:var(--primair);color:#fff;font:900 8pt/1 ' . $k . ';text-transform:uppercase;letter-spacing:.06em;padding:4pt 6pt;border-radius:3pt;margin-bottom:8pt}'
        . '.dagen ul{margin:0;padding-left:11pt}.dagen li{margin:0 0 3pt;color:var(--primair)}'
        . '.groepen{display:flex;flex-wrap:wrap;gap:6pt;margin:4pt 0 6pt}'
        . '.groepen span{border:1pt solid var(--lijn);border-radius:var(--r);padding:4pt 8pt;font-size:8.5pt;color:var(--primair)}'
        . '.groepen b{font-family:' . $k . ';font-weight:900;text-transform:uppercase;font-size:7.5pt;letter-spacing:.05em;margin-right:4pt}'
        // Begroting.
        . 'table{width:100%;border-collapse:collapse;font-size:9pt;margin:0 0 12pt;break-inside:avoid}'
        . 'th{font-size:7pt;letter-spacing:.07em;text-align:left;padding:6pt 6pt;background:var(--primair);color:#fff}'
        . 'th:first-child{border-radius:var(--r) 0 0 0}th:last-child{border-radius:0 var(--r) 0 0}'
        . 'td{padding:6pt;border-bottom:.8pt solid var(--tint-2);vertical-align:top;color:var(--primair)}'
        . 'td.n,th.n{text-align:right;white-space:nowrap}td.z{color:var(--body)}'
        . 'tr.totaal td{font-weight:700;border-top:1.5pt solid var(--primair);border-bottom:0}'
        . 'tr.btw td{color:var(--body);border-bottom:0;padding-top:2pt;padding-bottom:2pt}'
        . 'tr.incl td{font-weight:700;border-top:.8pt solid var(--lijn)}'
        . '.twee{display:grid;grid-template-columns:1fr 1fr;gap:10pt;break-inside:avoid}.twee .vak{margin:0}'
        . '.twee ul{margin:0;padding-left:11pt}'
        // Intern.
        . '.stappen{list-style:none;margin:0 0 12pt;padding:0;display:grid;grid-template-columns:repeat(3,1fr);gap:10pt}'
        . '.stappen{break-inside:avoid}.stappen li{background:var(--tint-3);border-radius:var(--r);padding:12pt;color:var(--primair);break-inside:avoid}'
        . '.stappen .nr{display:inline-flex;align-items:center;justify-content:center;width:16pt;height:16pt;background:var(--primair);color:#fff;border-radius:3pt;font:900 9pt/1 ' . $k . ';margin-bottom:8pt}'
        . '.volgende{margin-top:14pt}blockquote{margin:0 0 12pt;padding:4pt 0 4pt 14pt;border-left:3pt solid var(--secundair);font:400 10.5pt/1.55 ' . $b . ';color:var(--primair);break-inside:avoid}'
        . 'blockquote cite{display:block;font:900 7.5pt ' . $k . ';text-transform:uppercase;letter-spacing:.06em;color:var(--primair);margin-top:8pt;font-style:normal}'
        // Volgende stap.
        . '.volgende{background:var(--primair);color:#fff;border-radius:var(--r);padding:16pt 18pt;display:grid;grid-template-columns:auto 1fr auto;gap:16pt;align-items:center;break-inside:avoid}'
        . '.volgende h2,.volgende h3,.volgende .lab{color:#fff}'
        . '.volgende p{color:#fff;margin:0 0 8pt}'
        . '.volgende .foto{width:66pt;height:66pt;border-radius:var(--r);object-fit:cover;background:#fff}'
        . '.volgende .naam{font:900 10pt/1.2 ' . $k . ';text-transform:uppercase;letter-spacing:.03em}'
        . '.volgende .functie{font-size:7.5pt;text-transform:uppercase;letter-spacing:.05em;opacity:.85;margin-bottom:8pt}'
        . '.volgende .qr{display:flex;gap:10pt;align-items:center}.volgende .qr svg{width:64pt;height:64pt;display:block;border-radius:3pt}'
        . '.volgende .qr span{font:900 7pt/1.35 ' . $k . ';text-transform:uppercase;letter-spacing:.05em;max-width:70pt}'
        . '.volgende .qr a{color:#fff;font:400 7.5pt ' . $b . ';text-transform:none;letter-spacing:0;text-decoration:underline}'
        . '.knop{display:inline-block;background:var(--links);color:#fff;text-decoration:none;font-size:9pt;letter-spacing:.04em;padding:9pt 16pt;border-radius:var(--r)}'
        . '.contactregel{font-size:8pt;opacity:.9;margin-top:8pt!important}.contactregel a{color:#fff}'
        . 'a{color:var(--primair)}'
        . '.voorwaarden{margin-top:14pt;font-size:8pt}.voorwaarden h3{margin-bottom:4pt}'
        . '.lev td{font-size:8.5pt}.lev td:first-child{color:var(--body);width:38%}'
        . '.proef{position:fixed;top:40%;left:0;right:0;text-align:center;font:900 90pt ' . $k . ';color:rgba(164,38,44,.12);transform:rotate(-24deg);z-index:9}'
        . '*{font-variant-ligatures:none!important;font-feature-settings:"locl" 0!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}';
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
    $route_b = 'B' === $rij['route'];
    $t       = function ( $s, $v = array() ) use ( $taal ) {
        return sfp_calculator_t( $s, $v, $taal );
    };
    $e       = function ( $s ) {
        return esc_html( (string) $s );
    };
    $datum   = function ( $ymd ) {
        return wp_date( 'j F Y', strtotime( $ymd . ' 12:00:00' ) );
    };
    $vandaag = wp_date( 'Y-m-d', strtotime( ( $rij['afgerond_op'] ? $rij['afgerond_op'] : $rij['gestart_op'] ) . ' UTC' ) );
    $bedrag  = function ( $n ) use ( $valuta ) {
        return sfp_calculator_bedrag( (int) $n, $valuta );
    };

    // Kleuren, fonts en logo van de site.
    $kleur      = sfp_calculator_blad_kleuren();
    $kop_font   = function_exists( 'sfp_page_config_deelbeeld_familie' ) ? sfp_page_config_deelbeeld_familie( sfp_page_config_astra_option( 'headings-font-family' ) ) : '';
    $tekst_font = function_exists( 'sfp_page_config_deelbeeld_familie' ) ? sfp_page_config_deelbeeld_familie( sfp_page_config_astra_option( 'body-font-family' ) ) : '';
    $fonts      = function_exists( 'sfp_zelftest_fonts_css' ) ? sfp_zelftest_fonts_css( array_values( array_filter( array( $kop_font, $tekst_font ), 'strlen' ) ) ) : '';
    $label      = '' !== ( $bedrijf['label'] ?? '' ) ? $bedrijf['label'] : get_bloginfo( 'name' );
    $logo_src   = sfp_calculator_blad_beeld( (int) $inst['logo'] > 0 ? (int) $inst['logo'] : (int) get_theme_mod( 'custom_logo' ) );
    $logo       = '' !== $logo_src ? '<img alt="' . esc_attr( $label ) . '" src="' . esc_attr( $logo_src ) . '">' : '<h3>' . $e( $label ) . '</h3>';

    $naam = trim( $rij['voornaam'] . ' ' . $rij['achternaam'] );
    $org  = trim( (string) $rij['organisatie'] );
    $afd  = trim( (string) ( $rij['afdeling'] ?? '' ) );

    // Wie doet mee.
    $m   = isset( $u['mensen'] ) ? $u['mensen'] : array();
    $wie = $t( 'pdf_wie_onbekend' );
    if ( 'marge' === ( $m['soort'] ?? '' ) ) {
        $wie = $t( 'pdf_wie_marge', array( 'van' => $m['van'], 'tot' => $m['tot'] ) );
    } elseif ( ! empty( $m['aantal'] ) ) {
        $wie = $t( 'deelnemers_n', array( 'n' => (int) $m['aantal'] ) );
    }
    $groepen = $o ? ( 1 === (int) $o['groepen'] ? $t( 'groep' ) : $t( 'groepen', array( 'n' => (int) $o['groepen'] ) ) ) : '';

    // Wat erin zit.
    $erin = array();
    if ( ! $route_b && $o ) {
        foreach ( (array) $o['regels'] as $r ) {
            if ( 0 === strpos( (string) $r['code'], 'vorm_' ) || 'groepsvoordeel' === $r['code'] ) {
                continue;
            }
            $erin[] = $t( 'regel_' . $r['code'] ) . ( ! empty( $r['gratis'] ) ? ' (' . $t( 'inbegrepen' ) . ')' : '' );
        }
        $erin[] = $t( 'punt_calls' );
    }

    // De boeklink met kenmerk, voor knop en QR-code.
    $boek = sfp_calculator_url( 'boekpagina' );
    $boek = '' !== $boek ? add_query_arg( array( 'kenmerk' => $rij['kenmerk'] ), $boek ) : '';

    /* ---------- Voorblad ---------- */
    $kop = $route_b ? $t( 'pdf_kop_evenement' ) : ( '' !== $org ? $t( 'pdf_kop_org', array( 'org' => $org ) ) : $t( 'pdf_kop' ) );
    if ( $route_b ) {
        $kernzin = $t( 'pdf_kernzin_evenement', array( 'n' => (int) ( $keuzes['sprekers'] ?? 0 ) ) );
    } elseif ( $o ) {
        $vorm    = $t( 'vorm_' . (int) $keuzes['vorm'] );
        $kernzin = (int) $o['groepen'] > 1 ? $t( 'pdf_kernzin', array( 'vorm' => $vorm, 'wie' => $wie, 'groepen' => $groepen ) ) : $t( 'pdf_kernzin_een', array( 'vorm' => $vorm, 'wie' => $wie ) );
    } else {
        $kernzin = '';
    }
    if ( $route_b ) {
        $totaal = '<span class="lab">' . $e( $t( 'richtprijs' ) ) . '</span><p class="groot">' . $e( $bedrag( $u['evenement_prijs'] ?? 0 ) ) . '</p>'
            . '<p>' . $e( $t( 'evenement_prijs', array( 'prijs' => $bedrag( $u['evenement_prijs'] ?? 0 ) ) ) ) . '</p>';
    } elseif ( $o ) {
        $totaal = '<span class="lab">' . $e( ! empty( $u['richtprijs'] ) ? $t( 'richtprijs' ) : $t( 'totaal' ) ) . '</span>'
            . '<p class="groot">' . $e( $bedrag( $o['totaal_excl'] ) ) . '<small>' . $e( $t( 'excl' ) ) . '</small></p>'
            . '<p class="klein">' . $e( $t( 'pdf_incl_kort', array( 'prijs' => $bedrag( $o['totaal_incl'] ) ) ) ) . '</p>'
            . ( ! empty( $u['richtprijs'] ) ? '<p class="maatwerk">' . $e( $t( 'pdf_maatwerk' ) ) . '</p>' : '' );
    } else {
        $totaal = '';
    }
    $feiten = '<div class="feiten"><span><b class="lab">' . $e( $t( 'pdf_geldig' ) ) . '</b>' . $e( $datum( sfp_calculator_geldig_tot( $rij ) ) ) . '</span>'
        . ( $o && ! empty( $o['verdeling'] ) && count( (array) $o['verdeling'] ) > 1 ? '<span><b class="lab">' . $e( $t( 'pdf_groepen_kop' ) ) . '</b>' . $e( implode( ', ', (array) $o['verdeling'] ) ) . '</span>' : '' )
        . '</div>';
    $bon = '<div class="bon"><div>' . $totaal . $feiten . '</div>'
        . ( $erin ? '<div><span class="lab">' . $e( $t( 'pdf_erin' ) ) . '</span><ul class="vink">' . implode( '', array_map( function ( $w ) use ( $e ) { return '<li>' . $e( $w ) . '</li>'; }, $erin ) ) . '</ul></div>' : '<div></div>' )
        . '</div>';

    $meta = '<div class="meta"><b>' . $e( $t( 'begroting' ) ) . '</b><br><span class="w">' . $e( $rij['kenmerk'] ) . '</span><br>'
        . '<b>' . $e( $t( 'pdf_datum' ) ) . '</b><br><span class="w">' . $e( $datum( $vandaag ) ) . '</span>'
        . ( '' !== $naam ? '<br><b>' . $e( $t( 'pdf_voor' ) ) . '</b><br><span class="w">' . $e( implode( ', ', array_filter( array( $naam, $afd ), 'strlen' ) ) ) . '</span>' : '' )
        . '</div>';

    $html  = '<section class="voorblad">'
        . '<div class="top">' . $meta . $logo . '</div>'
        . '<span class="lab">' . $e( $t( 'lab' ) ) . '</span><h1>' . $e( $kop ) . '</h1>'
        . ( '' !== $kernzin ? '<p class="kernzin">' . $e( $kernzin ) . '</p>' : '' )
        . $bon
        . '</section>';

    $nr  = 0;
    $kop = function ( $tekst ) use ( &$nr, $e ) {
        $nr++;
        return '<h2><span class="nr">' . $nr . '</span><span>' . $e( $tekst ) . '</span></h2>';
    };

    /* ---------- 1. Wat jullie willen bereiken ---------- */
    $antw = array();
    if ( '' !== ( $sit['doel'] ?? '' ) ) {
        $antw[] = array( $t( 'pdf_doel' ), nl2br( $e( $sit['doel'] ) ), true );
    }
    if ( ! empty( $sit['waarom'] ) ) {
        $antw[] = array( $t( 'pdf_waarom' ), $e( implode( ', ', array_map( function ( $w ) use ( $t ) { return $t( 'waarom_' . $w ); }, (array) $sit['waarom'] ) ) ), false );
    }
    if ( ! empty( $keuzes['doelgroep'] ) ) {
        $antw[] = array( $t( 'doelgroep_vraag' ), $e( $t( 'doelgroep_' . $keuzes['doelgroep'] ) . ( 'anders' === $keuzes['doelgroep'] && '' !== ( $keuzes['doelgroep_anders'] ?? '' ) ? ': ' . $keuzes['doelgroep_anders'] : '' ) ), false );
    }
    if ( ! empty( $keuzes['uitvraag'] ) && '' !== ( $sit['criteria'] ?? '' ) ) {
        $antw[] = array( $t( 'criteria_vraag' ), nl2br( $e( $sit['criteria'] ) ), true );
    }
    if ( 'ja' === ( $keuzes['intern']['eerder'] ?? '' ) ) {
        $regels = sfp_calculator_intern_regels( array( 'intern' => array( 'eerder' => 'ja', 'beviel' => $keuzes['intern']['beviel'] ?? '', 'vorig' => $keuzes['intern']['vorig'] ?? '' ) ), $taal, $valuta );
        foreach ( $regels as $l => $w ) {
            $antw[] = array( $l, nl2br( $e( $w ) ), true );
        }
    }
    if ( $antw ) {
        $html .= '<section class="hoofdstuk zelfde">' . $kop( $t( 'pdf_h_doel' ) ) . '<p class="inleiding">' . $e( $t( 'pdf_doel_zin' ) ) . '</p><div class="antwoorden">'
            . implode( '', array_map( function ( $a ) use ( $e ) { return '<div class="vak lijn' . ( $a[2] ? ' breed' : '' ) . '"><span class="lab">' . $e( $a[0] ) . '</span><p>' . $a[1] . '</p></div>'; }, $antw ) )
            . '</div></section>';
    }

    /* ---------- 2. Het programma ---------- */
    if ( ! $route_b && ! empty( $keuzes['vorm'] ) ) {
        $prog  = '<p class="inleiding">' . $e( $t( 'pdf_opbouw' ) . ( '' !== $inst['methode'] ? ' ' . $t( 'pdf_methode', array( 'n' => $inst['methode'] ) ) : '' ) . ' ' . $t( 'pdf_programma_zin' ) ) . '</p>';
        $dagen = sfp_calculator_blad_dagen( $inst['programma'][ (string) $keuzes['vorm'] ] ?? '' );
        if ( $dagen ) {
            $prog .= '<div class="dagen">';
            foreach ( $dagen as $d => $regels ) {
                $prog .= '<div class="vak"><span class="nr">' . $e( $d ? $t( 'pdf_dag', array( 'n' => $d ) ) : $t( 'pdf_h_programma' ) ) . '</span><ul>'
                    . implode( '', array_map( function ( $r ) use ( $e ) { return '<li>' . $e( $r ) . '</li>'; }, $regels ) ) . '</ul></div>';
            }
            $prog .= '</div>';
        }
        if ( ! empty( $keuzes['doelgroep'] ) && '' !== ( $inst['doelgroepzin'][ $keuzes['doelgroep'] ] ?? '' ) ) {
            $prog .= '<div class="vak lijn"><span class="lab">' . $e( $t( 'pdf_doelgroep' ) ) . '</span><p>' . $e( $inst['doelgroepzin'][ $keuzes['doelgroep'] ] ) . '</p></div>';
        }
        if ( $o ) {
            $prog .= '<div class="vak"><h3>' . $e( $t( 'pdf_groepen_kop' ) ) . '</h3><div class="groepen">';
            foreach ( array_values( (array) $o['verdeling'] ) as $i => $n ) {
                $prog .= '<span><b>' . $e( $t( 'pdf_groep_n', array( 'n' => $i + 1 ) ) ) . '</b>' . $e( $t( 'deelnemers_n', array( 'n' => (int) $n ) ) ) . '</span>';
            }
            $prog .= '</div><p class="klein">' . $e( $t( 'pdf_groepsgrootte_zin' ) ) . '</p></div>';
        }
        $html .= '<section class="hoofdstuk">' . $kop( $t( 'pdf_h_programma' ) ) . $prog . '</section>';
    }

    /* ---------- 3. De begroting ---------- */
    if ( ! $route_b && $o ) {
        $loc = ! empty( $keuzes['locatie'] )
            ? $t( 'locatie_anders' ) . ' (' . $t( 'op_aanvraag' ) . '): ' . implode( ', ', array_map( function ( $l ) use ( $t ) { return $t( 'locatie_' . $l ); }, (array) $keuzes['locatie'] ) )
            : $t( 'locatie_klant' );
        $beg  = '<p class="inleiding">' . $e( $t( 'pdf_begroting_zin' ) ) . '</p>' . sfp_calculator_blad_tabel( $rij, true );
        $beg .= '<div class="twee"><div class="vak"><h3>' . $e( $t( 'pdf_inbegrepen' ) ) . '</h3>' . sfp_calculator_blad_lijst( $t( 'pdf_inbegrepen_lijst' ) ) . '</div>'
            . '<div class="vak"><h3>' . $e( $t( 'pdf_niet' ) ) . '</h3>' . sfp_calculator_blad_lijst( $t( 'pdf_niet_lijst' ) ) . '<p class="klein"><b>' . $e( $t( 'pdf_locatie' ) ) . ':</b> ' . $e( $loc ) . '</p></div></div>';
        $vw     = '<p>' . $e( $t( 'pdf_voorwaarden_zin' ) ) . '</p>';
        $vw_url = sfp_calculator_url( 'voorwaarden' );
        if ( '' !== $vw_url ) {
            $vw .= '<p>' . $e( $t( 'pdf_voorwaarden_link' ) ) . ': <a href="' . esc_url( $vw_url ) . '">' . $e( preg_replace( '#^https?://#', '', untrailingslashit( $vw_url ) ) ) . '</a></p>';
        }
        $beg  .= '<div class="voorwaarden"><h3>' . $e( $t( 'pdf_voorwaarden' ) ) . '</h3>' . $vw . '</div>';
        $html .= '<section class="hoofdstuk zelfde">' . $kop( $t( 'pdf_h_begroting' ) ) . $beg . '</section>';
    }

    /* ---------- 4. Zo leg je het intern voor ---------- */
    $intern = '<p class="inleiding">' . $e( $t( 'pdf_intern_zin' ) ) . '</p><ol class="stappen">';
    foreach ( array( 1, 2, 3 ) as $s ) {
        $intern .= '<li><span class="nr">' . $s . '</span><br>' . $e( $t( 'pdf_stap_' . $s ) ) . '</li>';
    }
    $intern .= '</ol>';
    $gaven   = array();
    if ( ! empty( $keuzes['fase'] ) ) {
        $gaven[ $t( 'pdf_fase' ) ] = $t( 'fase_' . $keuzes['fase'] );
    }
    foreach ( sfp_calculator_intern_regels( array( 'intern' => array( 'beslist' => $keuzes['intern']['beslist'] ?? array(), 'budget' => $keuzes['intern']['budget'] ?? '' ) ), $taal, $valuta ) as $l => $w ) {
        $gaven[ $l ] = $w;
    }
    if ( $gaven ) {
        $intern .= '<div class="vak lijn"><h3>' . $e( $t( 'pdf_aangegeven' ) ) . '</h3>';
        foreach ( $gaven as $l => $w ) {
            $intern .= '<p><b>' . $e( $l ) . ':</b> ' . $e( $w ) . '</p>';
        }
        $intern .= '</div>';
    }
    $r = $inst['referentie'];
    if ( '' !== $r['citaat'] ) {
        $intern .= '<blockquote>' . nl2br( $e( $r['citaat'] ) ) . '<cite>' . $e( implode( ', ', array_filter( array( $r['naam'], $r['functie'] ), 'strlen' ) ) ) . '</cite></blockquote>';
    }
    $intern .= '<p class="klein">' . $e( $t( 'pdf_aankondiging' ) ) . '</p>';
    $html   .= '<section class="hoofdstuk zelfde">' . $kop( $t( 'pdf_h_intern' ) ) . $intern . '</section>';

    /* ---------- 5. De volgende stap ---------- */

    /* ---------- Bijlage: voor inkoop ---------- */
    $lev = '';
    foreach ( array_keys( sfp_page_config_bedrijf_velden() ) as $sleutel ) {
        if ( 'label' !== $sleutel && '' !== ( $bedrijf[ $sleutel ] ?? '' ) ) {
            $lev .= '<tr><td>' . $e( $t( 'pdf_lev_' . $sleutel ) ) . '</td><td>' . nl2br( $e( $bedrijf[ $sleutel ] ) ) . '</td></tr>';
        }
    }
    $hun = '';
    foreach ( array( 'pdf_organisatie' => $org, 'pdf_afdeling' => $afd, 'pdf_beslisser' => $naam ) as $l => $w ) {
        if ( '' !== $w && 'pdf_beslisser' !== $l ) {
            $hun .= '<tr><td>' . $e( $t( $l ) ) . '</td><td>' . $e( $w ) . '</td></tr>';
        }
    }
    if ( ! empty( $keuzes['inkoop']['aan'] ) ) {
        foreach ( array( 'po', 'kostenplaats', 'referentie', 'factuuradres' ) as $veld ) {
            if ( '' !== ( $keuzes['inkoop'][ $veld ] ?? '' ) ) {
                $hun .= '<tr><td>' . $e( $t( 'inkoop_' . $veld ) ) . '</td><td>' . nl2br( $e( $keuzes['inkoop'][ $veld ] ) ) . '</td></tr>';
            }
        }
        $html .= '<section class="hoofdstuk zelfde">' . '<h2><span>' . $e( $t( 'pdf_h_inkoop' ) ) . '</span></h2><p class="inleiding">' . $e( $t( 'pdf_inkoop_zin' ) ) . '</p>'
            . '<div class="twee"><div class="vak"><h3>' . $e( $t( 'pdf_leverancier' ) ) . '</h3><table class="lev">' . $lev . '</table></div>'
            . '<div class="vak"><h3>' . $e( $t( 'pdf_jullie' ) ) . '</h3><table class="lev">' . $hun . '</table></div></div></section>';
    } elseif ( '' !== $lev ) {
        $html .= '<section class="hoofdstuk zelfde"><div class="vak klein"><h3>' . $e( $t( 'pdf_leverancier' ) ) . '</h3><table class="lev">' . $lev . '</table></div></section>';
    }

    // De volgende stap sluit het blad af.
    $html .= '<section class="hoofdstuk zelfde">' . sfp_calculator_blad_volgende( $inst, $boek, $t, $e, true ) . '</section>';

    $proef = ! empty( $rij['proef'] ) ? '<div class="proef">' . $e( $t( 'proef' ) ) . '</div>' : '';
    return '<!doctype html><html lang="' . esc_attr( $taal ) . '"><head><meta charset="utf-8"><title>' . $e( $t( 'pdf_titel' ) . ' ' . $rij['kenmerk'] ) . '</title>'
        . '<style>' . $fonts . sfp_calculator_blad_stijl( $kleur['css'], $kop_font, $tekst_font ) . '</style></head><body>' . $proef . $html . '</body></html>';
}

/**
 * Het blok met de contactpersoon, de QR-code en de knop.
 *
 * @param  array    $inst  Instellingen.
 * @param  string   $boek  Boeklink met kenmerk, of ''.
 * @param  callable $t     Teksten.
 * @param  callable $e     Escapen.
 * @param  bool     $vol   Met contactregel (hoofdstuk 5) of kort (voorblad).
 * @return string
 */
function sfp_calculator_blad_volgende( array $inst, $boek, $t, $e, $vol = false ) {
    $c    = $inst['contact'];
    $foto = sfp_calculator_blad_beeld( (int) $c['foto'], 'medium', 600000 );
    $qr   = '' !== $boek && function_exists( 'sfp_qr_svg' ) ? sfp_qr_svg( $boek, '#000', $t( 'pdf_scan' ) ) : '';
    $mid  = '<div><span class="lab">' . $e( $t( 'pdf_volgende' ) ) . '</span><h2><span>' . $e( $t( 'pdf_volgende_kop' ) ) . '</span></h2>'
        . '<p>' . $e( $t( 'pdf_volgende_tekst' ) ) . '</p>'
        . ( '' !== $c['naam'] ? '<div class="naam">' . $e( $c['naam'] ) . '</div>' . ( '' !== $c['functie'] ? '<div class="functie">' . $e( $c['functie'] ) . '</div>' : '' ) : '' )
        . ( '' !== $boek ? '<a class="knop" href="' . esc_url( $boek ) . '">' . $e( $t( 'knop_gesprek' ) ) . '</a>' : '' )
        . ( $vol ? '<p class="contactregel">' . $e( implode( ' • ', array_filter( array( $c['telefoon'], $c['email'] ), 'strlen' ) ) ) . '</p>' : '' )
        . '</div>';
    return '<div class="volgende">'
        . ( '' !== $foto ? '<img class="foto" alt="" src="' . esc_attr( $foto ) . '">' : '<span></span>' )
        . $mid
        . ( '' !== $qr ? '<div class="qr">' . $qr . '<span>' . $e( $t( 'pdf_scan' ) ) . '<br><a href="' . esc_url( $boek ) . '">' . $e( $t( 'pdf_of_klik' ) ) . '</a></span></div>' : '<span></span>' )
        . '</div>';
}
