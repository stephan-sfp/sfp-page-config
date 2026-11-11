<?php
/**
 * SFP Page Config - Incompanycalculator: het blad van de begroting
 *
 * Opbouw als de pdf van de zelftest: een voorblad met de kern (totaal, wat
 * erin zit, sociaal bewijs), daarna de begroting, het doel en het programma,
 * intern voorleggen met de volgende stap, en een bijlage voor inkoop. Eén
 * systeem: een donker vlak (primair) voor de kernmomenten, een licht vlak
 * (Tint 2) voor de rest, overal 6 px radius, één soort opsomming. Kleuren en
 * fonts uit de kleurrollen en Astra; hoofdletters via text-transform; de
 * actiekleur alleen op de knop.
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
 * De stijl van het blad. Eén systeem: een donker vlak (primair) voor de
 * kernmomenten, een licht vlak (Tint 2) voor de rest, overal een radius van
 * 6 px, één soort opsomming (vinkjes). Elke pagina is een vlak van A4 dat
 * zijn ruimte verdeelt.
 *
 * @param  string $vars       CSS-variabelen.
 * @param  string $kop_font   Kopfont (of '').
 * @param  string $tekst_font Bodyfont (of '').
 * @return string
 */
function sfp_calculator_blad_stijl( $vars, $kop_font, $tekst_font ) {
    $ff = function ( $f ) {
        return '' !== $f ? '"' . str_replace( '"', '', $f ) . '",' : '';
    };
    $k = $ff( $kop_font ) . 'system-ui,sans-serif';
    $b = $ff( $tekst_font ) . 'system-ui,sans-serif';
    return '@page{size:A4;margin:15mm 16mm 17mm}'
        . ':root{--primair:#222;--secundair:#222;--links:#222;--tint-1:#ddd;--tint-2:#eee;--tint-3:#f7f7f7;--body:#575757;' . $vars . '--r:6px}'
        . '*{box-sizing:border-box}html,body{margin:0;padding:0}'
        . 'body{font:400 10.5pt/1.55 ' . $b . ';color:var(--body)}'
        . 'p{margin:0}'
        . 'h1,h2,h3,.lab,.knop,th,.cijfer{font-family:' . $k . ';font-weight:900;text-transform:uppercase}'
        . 'h1,h2,h3{color:var(--primair);margin:0;text-wrap:balance}'
        . 'h1{font-size:27pt;line-height:1.08;letter-spacing:.005em}'
        . 'h2{font-size:16pt;line-height:1.15;letter-spacing:.01em}'
        . 'h2 .nr{color:var(--secundair);margin-right:7pt}'
        . 'h3{font-size:10pt;letter-spacing:.04em;line-height:1.25}'
        . '.lab{display:block;font-size:7.5pt;letter-spacing:.09em;line-height:1.3;color:var(--primair);margin-bottom:6pt}'
        . '.kernzin{font:700 13.5pt/1.4 ' . $b . ';color:var(--primair);text-wrap:balance}'
        . '.klein{font-size:9pt;line-height:1.5}'
        // Pagina's.
        . '.pagina{min-height:264mm;display:flex;flex-direction:column;gap:20pt;break-after:page}'
        . '.pagina:last-child{break-after:auto}'
        . '.duw{margin-top:auto}'
        . '.kop{display:flex;flex-direction:column;gap:8pt}'
        . '.vlak{background:var(--tint-2);border-radius:var(--r);padding:16pt 18pt;break-inside:avoid}'
        . '.donker{background:var(--primair);color:#fff;border-radius:var(--r);padding:22pt 24pt;break-inside:avoid}'
        . '.donker h2,.donker h3,.donker .lab,.donker .kernzin,.donker li{color:#fff}'
        . '.rij{display:grid;gap:12pt}.rij.r2{grid-template-columns:1fr 1fr}.rij.r3{grid-template-columns:repeat(3,1fr)}'
        // Vinkjes.
        . '.vink{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6pt}'
        . '.vink li{position:relative;padding-left:17pt;color:var(--primair);line-height:1.45}'
        . '.vink li:before{content:"";position:absolute;left:3pt;top:2pt;width:4.5pt;height:8.5pt;border:solid var(--secundair);border-width:0 1.8pt 1.8pt 0;transform:rotate(45deg)}'
        // Voorblad.
        . '.top{display:flex;justify-content:space-between;align-items:flex-start}'
        . '.top img{height:58pt;width:auto;max-width:180pt}'
        . '.meta{display:grid;grid-template-columns:auto auto;gap:3pt 14pt;font-size:9pt;color:var(--primair)}'
        . '.meta .lab{margin:0;align-self:center}'
        . '.held{display:grid;grid-template-columns:1.05fr 1fr;gap:26pt;align-items:start}'
        . '.bedrag{font:900 40pt/1 ' . $k . ';color:#fff;white-space:nowrap;margin:2pt 0 8pt}'
        . '.donker .sub{font-size:9.5pt;color:#fff;opacity:.85}'
        . '.donker .feiten{display:flex;gap:22pt;margin-top:18pt}'
        . '.donker .feiten .lab{opacity:.75;margin-bottom:3pt}.donker .feiten span{font-size:10pt}'
        . '.donker .vink li{color:#fff}'
        . '.cijfers{display:flex;flex-direction:column;gap:12pt}'
        . '.tegels{display:grid;grid-template-columns:repeat(auto-fit,minmax(0,1fr));gap:12pt}'
        . '.tegels .vlak{display:flex;align-items:center;padding:14pt 16pt}'
        . '.cijfer{font-size:11.5pt;line-height:1.25;letter-spacing:.02em;color:var(--primair)}'
        . '.partner{font-size:10pt;color:var(--primair)}'
        . 'blockquote{margin:0;position:relative;padding:0 0 0 40pt;break-inside:avoid}'
        . 'blockquote p{font:700 17pt/1.35 ' . $b . ';color:var(--primair);text-wrap:balance}'
        . 'blockquote .aanhaal{position:absolute;left:0;top:-8pt;font:900 52pt/1 ' . $k . ';color:var(--secundair)}'
        . 'blockquote .lab{margin:10pt 0 0}'
        // Begroting.
        . 'table{width:100%;border-collapse:separate;border-spacing:0;font-size:10pt;break-inside:avoid}'
        . 'th{font-size:7.5pt;letter-spacing:.08em;text-align:left;padding:9pt 10pt;background:var(--primair);color:#fff}'
        . 'th:first-child{border-radius:var(--r) 0 0 0}th:last-child{border-radius:0 var(--r) 0 0}'
        . 'td{padding:8pt 10pt;border-bottom:.8pt solid var(--tint-2);vertical-align:top;color:var(--primair)}'
        . 'td.n,th.n{text-align:right;white-space:nowrap}td.z{color:var(--body)}'
        . 'tfoot td{border-bottom:0}'
        . 'tr.totaal td{font-weight:700;background:var(--tint-2);border-top:1.5pt solid var(--primair)}'
        . 'tr.btw td{color:var(--body);padding-top:6pt;padding-bottom:6pt}'
        . 'tr.incl td{font-weight:700;background:var(--tint-2)}'
        . 'tr.incl td:first-child{border-radius:0 0 0 var(--r)}tr.incl td:last-child{border-radius:0 0 var(--r) 0}'
        . '.vlak h3{margin-bottom:10pt}'
        . '.voorwaarden{display:flex;flex-direction:column;gap:6pt}'
        . '.voorwaarden a{color:var(--primair)}'
        // Doel en programma.
        . '.doel{display:flex;flex-direction:column;gap:6pt}.doel .kernzin{font-size:15pt}'
        . '.antwoord p{color:var(--primair)}'
        . '.dagen{display:grid;grid-template-columns:repeat(var(--n),1fr);gap:12pt}'
        . '.dag{border-radius:var(--r);background:var(--tint-2);overflow:hidden;break-inside:avoid}'
        . '.dag h3{background:var(--primair);color:#fff;padding:9pt 14pt;font-size:9pt;letter-spacing:.08em}'
        . '.dag .vink{padding:14pt}'
        // Intern.
        . '.stap .nummer{font:900 26pt/1 ' . $k . ';color:var(--secundair);margin-bottom:10pt}'
        . '.stap p{color:var(--primair)}'
        // Volgende stap.
        . '.volgende{display:grid;grid-template-columns:auto 1fr auto;gap:22pt;align-items:center}'
        . '.volgende .foto{width:84pt;height:84pt;border-radius:var(--r);object-fit:cover;display:block}'
        . '.volgende .tekst{display:flex;flex-direction:column;gap:9pt}'
        . '.volgende p{color:#fff}'
        . '.volgende .naam{font:900 9.5pt/1.3 ' . $k . ';text-transform:uppercase;letter-spacing:.05em}'
        . '.volgende .naam small{display:block;font:400 8pt ' . $b . ';text-transform:none;letter-spacing:0;opacity:.85}'
        . '.qr{display:flex;flex-direction:column;align-items:center;gap:8pt;text-align:center}'
        . '.qr svg{width:86pt;height:86pt;display:block;border-radius:var(--r)}'
        . '.qr .lab{color:#fff;margin:0;max-width:96pt}'
        . '.qr a{color:#fff;font-size:8pt}'
        . '.knop{display:inline-block;align-self:flex-start;background:var(--links);color:#fff;text-decoration:none;font-size:9.5pt;letter-spacing:.05em;padding:11pt 20pt;border-radius:var(--r)}'
        . '.contactregel{font-size:8.5pt;opacity:.85}.contactregel a{color:#fff}'
        // Inkoop.
        . '.lev{border-collapse:collapse}.lev td{font-size:9.5pt;padding:6pt 0;border-bottom:.8pt solid var(--tint-1)}.lev td:first-child{color:var(--body);width:40%;padding-right:10pt}'
        . '.lev tr:last-child td{border-bottom:0}'
        . '.proef{position:fixed;top:40%;left:0;right:0;text-align:center;font:900 90pt ' . $k . ';color:rgba(164,38,44,.12);transform:rotate(-24deg);z-index:9}'
        . '*{font-variant-ligatures:none!important;font-feature-settings:"locl" 0!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}';
}

/**
 * Een lijst met vinkjes.
 *
 * @param  string[] $regels Regels.
 * @param  callable $e      Escapen.
 * @return string
 */
function sfp_calculator_blad_vinken( array $regels, $e ) {
    $regels = array_values( array_filter( array_map( 'trim', $regels ), 'strlen' ) );
    return $regels ? '<ul class="vink">' . implode( '', array_map( function ( $r ) use ( $e ) { return '<li>' . $e( $r ) . '</li>'; }, $regels ) ) . '</ul>' : '';
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
    $bedrag  = function ( $n ) use ( $valuta ) {
        return sfp_calculator_bedrag( (int) $n, $valuta );
    };
    $vinken  = function ( array $r ) use ( $e ) {
        return sfp_calculator_blad_vinken( $r, $e );
    };
    $vandaag = wp_date( 'Y-m-d', strtotime( ( $rij['afgerond_op'] ? $rij['afgerond_op'] : $rij['gestart_op'] ) . ' UTC' ) );

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

    // Wie doet mee, hoeveel groepen.
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

    $nr  = 0;
    $kop = function ( $tekst, $inleiding = '' ) use ( &$nr, $e ) {
        $nr++;
        return '<div class="kop"><h2><span class="nr">' . $nr . '</span>' . $e( $tekst ) . '</h2>' . ( '' !== $inleiding ? '<p>' . $e( $inleiding ) . '</p>' : '' ) . '</div>';
    };
    $paginas = array();

    /* ---------- Voorblad ---------- */
    $titel = $route_b ? $t( 'pdf_kop_evenement' ) : ( '' !== $org ? $t( 'pdf_kop_org', array( 'org' => $org ) ) : $t( 'pdf_kop' ) );
    if ( $route_b ) {
        $kernzin = $t( 'pdf_kernzin_evenement', array( 'n' => (int) ( $keuzes['sprekers'] ?? 0 ) ) );
    } elseif ( $o ) {
        $vorm    = $t( 'vorm_' . (int) $keuzes['vorm'] );
        $kernzin = (int) $o['groepen'] > 1 ? $t( 'pdf_kernzin', array( 'vorm' => $vorm, 'wie' => $wie, 'groepen' => $groepen ) ) : $t( 'pdf_kernzin_een', array( 'vorm' => $vorm, 'wie' => $wie ) );
    } else {
        $kernzin = '';
    }
    $meta = '<div class="meta"><span class="lab">' . $e( $t( 'begroting' ) ) . '</span><span>' . $e( $rij['kenmerk'] ) . '</span>'
        . '<span class="lab">' . $e( $t( 'pdf_datum' ) ) . '</span><span>' . $e( $datum( $vandaag ) ) . '</span>'
        . ( '' !== $naam ? '<span class="lab">' . $e( $t( 'pdf_voor' ) ) . '</span><span>' . $e( implode( ', ', array_filter( array( $naam, $afd ), 'strlen' ) ) ) . '</span>' : '' )
        . '</div>';
    $feiten = '<div class="feiten"><div><span class="lab">' . $e( $t( 'pdf_geldig' ) ) . '</span><span>' . $e( $datum( sfp_calculator_geldig_tot( $rij ) ) ) . '</span></div>'
        . ( $o && count( (array) $o['verdeling'] ) > 1 ? '<div><span class="lab">' . $e( $t( 'pdf_groepen_kop' ) ) . '</span><span>' . $e( implode( ', ', (array) $o['verdeling'] ) ) . '</span></div>' : '' )
        . '</div>';
    if ( $route_b ) {
        $links = '<span class="lab">' . $e( $t( 'richtprijs' ) ) . '</span><p class="bedrag">' . $e( $bedrag( $u['evenement_prijs'] ?? 0 ) ) . '</p>'
            . '<p class="sub">' . $e( $t( 'evenement_prijs', array( 'prijs' => $bedrag( $u['evenement_prijs'] ?? 0 ) ) ) ) . '</p>';
    } elseif ( $o ) {
        $links = '<span class="lab">' . $e( ! empty( $u['richtprijs'] ) ? $t( 'richtprijs' ) : $t( 'totaal' ) ) . '</span>'
            . '<p class="bedrag">' . $e( $bedrag( $o['totaal_excl'] ) ) . '</p>'
            . '<p class="sub">' . $e( $t( 'excl' ) . ' • ' . $t( 'pdf_incl_kort', array( 'prijs' => $bedrag( $o['totaal_incl'] ) ) ) ) . '</p>'
            . ( ! empty( $u['richtprijs'] ) ? '<p class="sub">' . $e( $t( 'pdf_maatwerk' ) ) . '</p>' : '' );
    } else {
        $links = '';
    }
    $paginas[] = '<section class="pagina voorblad">'
        . '<div class="top">' . $meta . $logo . '</div>'
        . '<div class="kop"><span class="lab">' . $e( $t( 'lab' ) ) . '</span><h1>' . $e( $titel ) . '</h1>'
        . ( '' !== $kernzin ? '<p class="kernzin">' . $e( $kernzin ) . '</p>' : '' ) . '</div>'
        . '<div class="donker held"><div>' . $links . $feiten . '</div>'
        . ( $erin ? '<div><span class="lab">' . $e( $t( 'pdf_erin' ) ) . '</span>' . $vinken( $erin ) . '</div>' : '<div></div>' ) . '</div>'
        . '<div class="duw">' . sfp_calculator_blad_cijfers( $inst, $label, $t, $e ) . '</div>'
        . '</section>';

    /* ---------- 1. De begroting ---------- */
    if ( ! $route_b && $o ) {
        $groep = array();
        foreach ( array_values( (array) $o['verdeling'] ) as $i => $n ) {
            $groep[] = $t( 'pdf_groep_n', array( 'n' => $i + 1 ) ) . ': ' . $t( 'deelnemers_n', array( 'n' => (int) $n ) );
        }
        $niet   = preg_split( '/\r\n|\r|\n/', $t( 'pdf_niet_lijst' ) );
        $niet[] = $t( 'pdf_locatie' ) . ': ' . ( ! empty( $keuzes['locatie'] )
            ? $t( 'locatie_anders' ) . ' (' . $t( 'op_aanvraag' ) . ')'
            : $t( 'locatie_klant' ) );
        $vw     = '<p class="klein">' . $e( $t( 'pdf_voorwaarden_zin' ) ) . '</p>';
        $vw_url = sfp_calculator_url( 'voorwaarden' );
        if ( '' !== $vw_url ) {
            $vw .= '<p class="klein"><a href="' . esc_url( $vw_url ) . '">' . $e( $t( 'pdf_voorwaarden_link' ) ) . '</a></p>';
        }
        $paginas[] = '<section class="pagina">'
            . $kop( $t( 'pdf_h_begroting' ), $t( 'pdf_begroting_zin' ) )
            . sfp_calculator_blad_tabel( $rij, true )
            . '<div class="rij r3">'
            . '<div class="vlak"><h3>' . $e( $t( 'pdf_groepen_kop' ) ) . '</h3>' . $vinken( $groep ) . '<p class="klein" style="margin-top:10pt">' . $e( $t( 'pdf_groepsgrootte_zin' ) ) . '</p></div>'
            . '<div class="vlak"><h3>' . $e( $t( 'pdf_inbegrepen' ) ) . '</h3>' . $vinken( preg_split( '/\r\n|\r|\n/', $t( 'pdf_inbegrepen_lijst' ) ) ) . '</div>'
            . '<div class="vlak"><h3>' . $e( $t( 'pdf_niet' ) ) . '</h3>' . $vinken( $niet ) . '</div>'
            . '</div>'
            . '<div class="voorwaarden duw"><span class="lab">' . $e( $t( 'pdf_voorwaarden' ) ) . '</span>' . $vw . '</div>'
            . '</section>';
    }

    /* ---------- 2. Wat jullie willen bereiken, 3. Het programma ---------- */
    $doel = '';
    if ( '' !== ( $sit['doel'] ?? '' ) ) {
        $doel = '<div class="vlak doel"><span class="lab">' . $e( $t( 'pdf_doel' ) ) . '</span><p class="kernzin">' . nl2br( $e( $sit['doel'] ) ) . '</p></div>';
    }
    $antw = array();
    if ( ! empty( $sit['waarom'] ) ) {
        $antw[] = array( $t( 'pdf_waarom' ), implode( ', ', array_map( function ( $w ) use ( $t ) { return $t( 'waarom_' . $w ); }, (array) $sit['waarom'] ) ) );
    }
    if ( ! empty( $keuzes['doelgroep'] ) ) {
        $antw[] = array( $t( 'doelgroep_vraag' ), $t( 'doelgroep_' . $keuzes['doelgroep'] ) . ( 'anders' === $keuzes['doelgroep'] && '' !== ( $keuzes['doelgroep_anders'] ?? '' ) ? ': ' . $keuzes['doelgroep_anders'] : '' ) );
    }
    if ( 'ja' === ( $keuzes['intern']['eerder'] ?? '' ) ) {
        foreach ( sfp_calculator_intern_regels( array( 'intern' => array( 'eerder' => 'ja', 'beviel' => $keuzes['intern']['beviel'] ?? '', 'vorig' => $keuzes['intern']['vorig'] ?? '' ) ), $taal, $valuta ) as $l => $w ) {
            $antw[] = array( $l, $w );
        }
    }
    if ( ! empty( $keuzes['uitvraag'] ) && '' !== ( $sit['criteria'] ?? '' ) ) {
        $antw[] = array( $t( 'criteria_vraag' ), $sit['criteria'] );
    }
    $antw_html = $antw ? '<div class="rij r' . min( 3, max( 2, count( $antw ) ) ) . '">' . implode( '', array_map( function ( $a ) use ( $e ) { return '<div class="antwoord"><span class="lab">' . $e( $a[0] ) . '</span><p>' . nl2br( $e( $a[1] ) ) . '</p></div>'; }, $antw ) ) . '</div>' : '';

    $doel_kop = '' !== $doel . $antw_html ? $kop( $t( 'pdf_h_doel' ), $t( 'pdf_doel_zin' ) ) : '';
    $prog = '';
    if ( ! $route_b && ! empty( $keuzes['vorm'] ) ) {
        $dagen = sfp_calculator_blad_dagen( $inst['programma'][ (string) $keuzes['vorm'] ] ?? '' );
        if ( $dagen ) {
            $prog = '<div class="dagen" style="--n:' . max( 1, count( $dagen ) ) . '">';
            foreach ( $dagen as $d => $regels ) {
                $prog .= '<div class="dag"><h3>' . $e( $d ? $t( 'pdf_dag', array( 'n' => $d ) ) : $t( 'pdf_h_programma' ) ) . '</h3>' . $vinken( $regels ) . '</div>';
            }
            $prog .= '</div>';
        }
        if ( ! empty( $keuzes['doelgroep'] ) && '' !== ( $inst['doelgroepzin'][ $keuzes['doelgroep'] ] ?? '' ) ) {
            $prog .= '<div class="vlak"><span class="lab">' . $e( $t( 'pdf_doelgroep' ) ) . '</span><p class="antwoord">' . $e( $inst['doelgroepzin'][ $keuzes['doelgroep'] ] ) . '</p></div>';
        }
        $prog = $kop( $t( 'pdf_h_programma' ), $t( 'pdf_opbouw' ) . ( '' !== $inst['methode'] ? ' ' . $t( 'pdf_methode', array( 'n' => $inst['methode'] ) ) : '' ) . ' ' . $t( 'pdf_programma_zin' ) ) . $prog;
    }
    if ( '' !== $doel . $antw_html . $prog ) {
        $paginas[] = '<section class="pagina">'
            . $doel_kop . $doel . $antw_html
            . $prog
            . '</section>';
    }

    /* ---------- 4. Zo leg je het intern voor, en de volgende stap ---------- */
    $stappen = '<div class="rij r3">';
    foreach ( array( 1, 2, 3 ) as $s ) {
        $stappen .= '<div class="vlak stap"><div class="nummer">' . $s . '</div><p>' . $e( $t( 'pdf_stap_' . $s ) ) . '</p></div>';
    }
    $stappen .= '</div>';
    $gaven    = array();
    if ( ! empty( $keuzes['fase'] ) ) {
        $gaven[] = array( $t( 'pdf_fase' ), $t( 'fase_' . $keuzes['fase'] ) );
    }
    foreach ( sfp_calculator_intern_regels( array( 'intern' => array( 'beslist' => $keuzes['intern']['beslist'] ?? array(), 'budget' => $keuzes['intern']['budget'] ?? '' ) ), $taal, $valuta ) as $l => $w ) {
        $gaven[] = array( $l, $w );
    }
    $gaven_html = $gaven ? '<div class="vlak"><span class="lab">' . $e( $t( 'pdf_aangegeven' ) ) . '</span><div class="rij r' . min( 3, max( 2, count( $gaven ) ) ) . '">'
        . implode( '', array_map( function ( $a ) use ( $e ) { return '<div class="antwoord"><h3 style="font-size:8pt;margin-bottom:4pt">' . $e( $a[0] ) . '</h3><p>' . $e( $a[1] ) . '</p></div>'; }, $gaven ) ) . '</div></div>' : '';
    $paginas[] = '<section class="pagina">'
        . $kop( $t( 'pdf_h_intern' ), $t( 'pdf_intern_zin' ) )
        . $stappen . $gaven_html
        . '<p class="klein">' . $e( $t( 'pdf_aankondiging' ) ) . '</p>'
        . '<div class="duw">' . sfp_calculator_blad_volgende( $inst, $boek, $t, $e ) . '</div>'
        . '</section>';

    /* ---------- Bijlage: voor inkoop ---------- */
    $lev = '';
    foreach ( array_keys( sfp_page_config_bedrijf_velden() ) as $sleutel ) {
        if ( 'label' !== $sleutel && '' !== ( $bedrijf[ $sleutel ] ?? '' ) ) {
            $lev .= '<tr><td>' . $e( $t( 'pdf_lev_' . $sleutel ) ) . '</td><td>' . nl2br( $e( $bedrijf[ $sleutel ] ) ) . '</td></tr>';
        }
    }
    if ( ! empty( $keuzes['inkoop']['aan'] ) ) {
        $hun = '';
        foreach ( array( 'pdf_organisatie' => $org, 'pdf_afdeling' => $afd ) as $l => $w ) {
            if ( '' !== $w ) {
                $hun .= '<tr><td>' . $e( $t( $l ) ) . '</td><td>' . $e( $w ) . '</td></tr>';
            }
        }
        foreach ( array( 'po', 'kostenplaats', 'referentie', 'factuuradres' ) as $veld ) {
            if ( '' !== ( $keuzes['inkoop'][ $veld ] ?? '' ) ) {
                $hun .= '<tr><td>' . $e( $t( 'inkoop_' . $veld ) ) . '</td><td>' . nl2br( $e( $keuzes['inkoop'][ $veld ] ) ) . '</td></tr>';
            }
        }
        $paginas[] = '<section class="pagina">'
            . '<div class="kop"><h2>' . $e( $t( 'pdf_h_inkoop' ) ) . '</h2><p>' . $e( $t( 'pdf_inkoop_zin' ) ) . '</p></div>'
            . '<div class="rij r2"><div class="vlak"><h3>' . $e( $t( 'pdf_leverancier' ) ) . '</h3><table class="lev">' . $lev . '</table></div>'
            . '<div class="vlak"><h3>' . $e( $t( 'pdf_jullie' ) ) . '</h3><table class="lev">' . $hun . '</table></div></div>'
            . '</section>';
    } elseif ( '' !== $lev && count( $paginas ) > 1 ) {
        // Zonder inkoop: de leveranciersgegevens klein onder de voorwaarden.
        $paginas[1] = str_replace( '</div></section>', '<table class="lev" style="margin-top:8pt;width:60%">' . $lev . '</table></div></section>', $paginas[1] );
    }

    $proef = ! empty( $rij['proef'] ) ? '<div class="proef">' . $e( $t( 'proef' ) ) . '</div>' : '';
    return '<!doctype html><html lang="' . esc_attr( $taal ) . '"><head><meta charset="utf-8"><title>' . $e( $t( 'pdf_titel' ) . ' ' . $rij['kenmerk'] ) . '</title>'
        . '<style>' . $fonts . sfp_calculator_blad_stijl( $kleur['css'], $kop_font, $tekst_font ) . '</style></head><body>' . $proef . implode( '', $paginas ) . '</body></html>';
}

/**
 * Het blok van de volgende stap: foto, tekst, knop en QR-code.
 *
 * @param  array    $inst Instellingen.
 * @param  string   $boek Boeklink met kenmerk, of ''.
 * @param  callable $t    Teksten.
 * @param  callable $e    Escapen.
 * @return string
 */
function sfp_calculator_blad_volgende( array $inst, $boek, $t, $e ) {
    $c    = $inst['contact'];
    $foto = sfp_calculator_blad_beeld( (int) $c['foto'], 'medium', 600000 );
    $qr   = '' !== $boek && function_exists( 'sfp_qr_svg' ) ? sfp_qr_svg( $boek, '#000', $t( 'pdf_scan' ) ) : '';
    $mid  = '<div class="tekst"><span class="lab">' . $e( $t( 'pdf_volgende' ) ) . '</span><h2>' . $e( $t( 'pdf_volgende_kop' ) ) . '</h2>'
        . '<p>' . $e( $t( 'pdf_volgende_tekst' ) ) . '</p>'
        . ( '' !== $c['naam'] ? '<p class="naam">' . $e( $c['naam'] ) . ( '' !== $c['functie'] ? '<small>' . $e( $c['functie'] ) . '</small>' : '' ) . '</p>' : '' )
        . ( '' !== $boek ? '<a class="knop" href="' . esc_url( $boek ) . '">' . $e( $t( 'knop_gesprek' ) ) . '</a>' : '' )
        . ( '' !== $c['telefoon'] . $c['email'] ? '<p class="contactregel">' . $e( implode( ' • ', array_filter( array( $c['telefoon'], $c['email'] ), 'strlen' ) ) ) . '</p>' : '' )
        . '</div>';
    return '<div class="donker volgende" style="grid-template-columns:' . ( '' !== $foto ? 'auto ' : '' ) . '1fr' . ( '' !== $qr ? ' auto' : '' ) . '">'
        . ( '' !== $foto ? '<img class="foto" alt="" src="' . esc_attr( $foto ) . '">' : '' )
        . $mid
        . ( '' !== $qr ? '<div class="qr">' . $qr . '<span class="lab">' . $e( $t( 'pdf_scan' ) ) . '</span><a href="' . esc_url( $boek ) . '">' . $e( $t( 'pdf_of_klik' ) ) . '</a></div>' : '' )
        . '</div>';
}

/**
 * Sociaal bewijs: de partnerzin, de cijfers als tegels (SFP Beheer, anders
 * de netwerknorm), de quote en "Referenties op aanvraag".
 *
 * @param  array    $inst  Instellingen.
 * @param  string   $label Labelnaam.
 * @param  callable $t     Teksten.
 * @param  callable $e     Escapen.
 * @return string
 */
function sfp_calculator_blad_cijfers( array $inst, $label, $t, $e ) {
    $bron    = '' !== trim( (string) ( $inst['feiten'] ?? '' ) ) ? (string) $inst['feiten'] : $t( 'pdf_feiten_norm' );
    $feiten  = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $bron ) ), 'strlen' ) );
    $partner = 'SFP' !== sfp_calculator_site_afk() ? '<p class="partner">' . $e( $t( 'pdf_partner', array( 'label' => $label ) ) ) . '</p>' : '';
    $r       = $inst['referentie'];
    $quote   = '' !== trim( (string) $r['citaat'] )
        ? '<blockquote><span class="aanhaal" aria-hidden="true">&ldquo;</span><p>' . nl2br( $e( $r['citaat'] ) ) . '</p><span class="lab">' . $e( implode( ', ', array_filter( array( $r['naam'], $r['functie'] ), 'strlen' ) ) ) . '</span></blockquote>'
        : '';
    return '<div class="cijfers">' . $quote . $partner
        . ( $feiten ? '<div class="tegels">' . implode( '', array_map( function ( $f ) use ( $e ) { return '<div class="vlak"><span class="cijfer">' . $e( $f ) . '</span></div>'; }, $feiten ) ) . '</div>' : '' )
        . '<p class="klein">' . $e( $t( 'pdf_referenties' ) ) . '</p></div>';
}
