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
 * De stijl van het blad, in de taal van de pdf van de zelftest: veel wit,
 * kaarten met een dunne rand en een donkere kopbalk, kernzinnen en
 * aandachtsblokken met een verticale lijn in de secundaire kleur, labels in
 * hoofdletters, overal 6 px radius, een voettekst met een lijn erboven.
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
    return '@page{size:A4;margin:16mm 17mm 18mm}'
        . ':root{--primair:#222;--secundair:#222;--links:#222;--tint-1:#ddd;--tint-2:#eee;--tint-3:#f7f7f7;--body:#575757;' . $vars . '--rand:color-mix(in srgb,var(--primair) 16%,#fff);--r:6px}'
        . '*{box-sizing:border-box}html,body{margin:0;padding:0}'
        . 'body{font:400 10pt/1.6 ' . $b . ';color:var(--body)}'
        . 'p{margin:0}'
        . 'h1,h2,h3,.lab,.knop,th,.getal{font-family:' . $k . ';font-weight:900;text-transform:uppercase}'
        . 'h1,h2,h3{color:var(--primair);margin:0}'
        . 'h1{font-size:21pt;line-height:1.2;letter-spacing:.01em}'
        . 'h2{font-size:15pt;line-height:1.2;letter-spacing:.01em}'
        . 'h2 .nr{color:var(--secundair);margin-right:8pt}'
        . 'h3{font-size:8.5pt;letter-spacing:.07em;line-height:1.3}'
        . '.lab{display:block;font-size:7pt;letter-spacing:.09em;line-height:1.35;color:var(--primair)}'
        . '.kernzin{border-left:2.5pt solid var(--secundair);padding:2pt 0 2pt 14pt;font:400 15pt/1.45 ' . $b . ';color:var(--primair)}'
        . '.klein{font-size:8.5pt;line-height:1.55}'
        // Pagina's.
        . '.pagina{min-height:254mm;display:flex;flex-direction:column;gap:20pt;break-after:page}'
        . '.pagina:last-child{break-after:auto}'
        . '.duw{margin-top:auto}'
        . '.kop{display:flex;flex-direction:column;gap:8pt}'
        // Kaart: witte achtergrond, dunne rand, donkere kopbalk.
        . '.kaart{border:.8pt solid var(--rand);border-radius:var(--r);overflow:hidden;break-inside:avoid;background:#fff}'
        . '.kaart>h3{background:var(--primair);color:#fff;padding:9pt 16pt}'
        . '.kaart .in{padding:16pt}'
        . '.kaart.open>h3{background:#fff;color:var(--primair);border-bottom:.8pt solid var(--rand)}'
        . '.kaart.open{border-style:dashed}'
        // Aandachtsblok.
        . '.aandacht{background:var(--tint-3);border-left:2.5pt solid var(--secundair);border-radius:0 var(--r) var(--r) 0;padding:14pt 18pt;break-inside:avoid}'
        . '.aandacht .lab{margin-bottom:6pt}'
        . '.aandacht p{color:var(--primair)}'
        . '.rij{display:grid;gap:14pt}.rij.r2{grid-template-columns:1fr 1fr}.rij.r3{grid-template-columns:repeat(3,1fr)}'
        // Vinkjes en streepjes.
        . '.vink{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:7pt}'
        . '.vink li{position:relative;padding-left:18pt;color:var(--primair);line-height:1.4}'
        . '.vink li:before{content:"";position:absolute;left:0;top:1pt;width:10pt;height:10pt;border-radius:2pt;background:var(--secundair)}'
        . '.vink li:after{content:"";position:absolute;left:3.6pt;top:2.6pt;width:2.4pt;height:5pt;border:solid #fff;border-width:0 1.3pt 1.3pt 0;transform:rotate(45deg)}'
        . '.vink.kort li{white-space:nowrap}'
        . '.vink.twee{display:grid;grid-template-columns:1fr 1fr;gap:8pt 18pt}'
        . '.streep li:before{background:#fff;border:.8pt solid var(--rand)}'
        . '.streep li:after{left:3pt;top:6pt;width:4pt;height:0;border:solid var(--primair);border-width:1pt 0 0 0;transform:none}'
        // Voorblad.
        . '.top{display:flex;justify-content:space-between;align-items:flex-start}'
        . '.top img{height:46pt;width:auto;max-width:170pt}'
        . '.meta{display:flex;flex-direction:column;gap:2pt}'
        . '.meta .lab{font-size:6.5pt}.meta .lab b{color:var(--secundair);font-weight:900}'
        . '.titel{display:flex;flex-direction:column;gap:16pt}'
        . '.kort .in{display:grid;grid-template-columns:auto 1fr;gap:0 28pt}'
        . '.kort .bedrag{padding-right:28pt;border-right:.8pt solid var(--rand)}'
        . '.getal{font-size:30pt;line-height:1;color:var(--primair);letter-spacing:0;white-space:nowrap;margin:6pt 0 6pt}'
        . '.kort .sub{font-size:8.5pt}'
        . '.kort .geldig{margin-top:16pt}'
        . '.kort .geldig p{color:var(--primair)}'
        . '.feiten{display:grid;grid-template-columns:repeat(auto-fit,minmax(0,1fr));gap:14pt}'
        . '.feit{border-top:2.5pt solid var(--secundair);padding-top:10pt}'
        . '.feit .getal{font-size:22pt;margin:0 0 4pt}'
        . '.feit p{color:var(--primair);font-size:9pt;line-height:1.4}'
        . 'blockquote{margin:0;break-inside:avoid}'
        . 'blockquote p{font:400 14pt/1.45 ' . $b . ';color:var(--primair)}'
        . 'blockquote .lab{margin-top:8pt}'
        . '.bewijs{display:flex;flex-direction:column;gap:18pt}'
        . '.bewijs .partner{color:var(--primair)}'
        // Tabel.
        . 'table{width:100%;border-collapse:separate;border-spacing:0;break-inside:avoid}'
        . '.bedragen{border:.8pt solid var(--rand);border-radius:var(--r);overflow:hidden}'
        . 'th{font-size:7pt;letter-spacing:.09em;text-align:left;padding:9pt 12pt;background:var(--primair);color:#fff}'
        . 'td{padding:8pt 12pt;border-bottom:.8pt solid var(--rand);vertical-align:top;color:var(--primair)}'
        . 'td.n,th.n{text-align:right;white-space:nowrap}td.z{color:var(--body)}'
        . 'tfoot tr:last-child td{border-bottom:0}'
        . 'tr.totaal td{font-weight:700;border-top:1.2pt solid var(--primair)}'
        . 'tr.btw td{color:var(--body)}'
        . 'tr.incl td{font-weight:700;background:var(--tint-3)}'
        // Programma.
        . '.dagen{display:grid;grid-template-columns:repeat(var(--n),1fr);gap:14pt}'
        // Stappen.
        . '.stap{display:flex;gap:12pt;align-items:flex-start}'
        . '.stap .num{flex:none;width:22pt;height:22pt;border-radius:var(--r);background:var(--primair);color:#fff;display:flex;align-items:center;justify-content:center;font:900 10pt/1 ' . $k . '}'
        . '.stap p{color:var(--primair);padding-top:2pt}'
        . '.gaven{display:grid;grid-template-columns:repeat(3,1fr);gap:14pt}'
        . '.gaven p{color:var(--primair)}.gaven .lab{margin-bottom:4pt}'
        // Volgende stap.
        . '.volgende{background:var(--primair);border-radius:var(--r);padding:16pt 20pt;display:grid;gap:20pt;align-items:center;break-inside:avoid;color:#fff}'
        . '.volgende .foto{width:78pt;height:78pt;border-radius:var(--r);object-fit:cover;display:block}'
        . '.volgende .tekst{display:flex;flex-direction:column;gap:6pt}'
        . '.volgende h2{color:#fff;font-size:13pt}.volgende .lab{color:#fff;opacity:.8}'
        . '.volgende p{color:#fff}'
        . '.volgende .naam{font:900 8.5pt/1.3 ' . $k . ';text-transform:uppercase;letter-spacing:.06em}'
        . '.volgende .naam small{display:block;font:400 8pt ' . $b . ';text-transform:none;letter-spacing:0;opacity:.8}'
        . '.qr{display:flex;flex-direction:column;align-items:center;gap:7pt;text-align:center}'
        . '.qr svg{width:70pt;height:70pt;display:block;border-radius:var(--r)}'
        . '.qr .lab{max-width:92pt}.qr a{color:#fff;font-size:7.5pt}'
        . '.knop{display:inline-block;align-self:flex-start;background:var(--links);color:#fff;text-decoration:none;font-size:8.5pt;letter-spacing:.06em;padding:10pt 18pt;border-radius:var(--r)}'
        . '.contactregel{font-size:8pt;opacity:.8}'
        // Voorwaarden en inkoop.
        . '.voorwaarden{display:flex;flex-direction:column;gap:5pt}.voorwaarden a{color:var(--primair)}'
        . '.lev{border-collapse:collapse}.lev td{font-size:9pt;padding:6pt 0;border-bottom:.8pt solid var(--rand)}.lev td:first-child{color:var(--body);width:40%;padding-right:10pt}'
        . '.lev tr:last-child td{border-bottom:0}'
        . '.proef{position:fixed;top:40%;left:0;right:0;text-align:center;font:900 90pt ' . $k . ';color:rgba(164,38,44,.12);transform:rotate(-24deg);z-index:9}'
        . '*{font-variant-ligatures:none!important;font-feature-settings:"locl" 0!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}';
}

/**
 * Een lijst met vinkjes (of streepjes voor wat er niet bij zit).
 *
 * @param  string[] $regels Regels.
 * @param  callable $e      Escapen.
 * @param  string   $klas   Extra klassen.
 * @return string
 */
function sfp_calculator_blad_vinken( array $regels, $e, $klas = '' ) {
    $regels = array_values( array_filter( array_map( 'trim', $regels ), 'strlen' ) );
    return $regels ? '<ul class="vink ' . esc_attr( $klas ) . '">' . implode( '', array_map( function ( $r ) use ( $e ) { return '<li>' . $e( $r ) . '</li>'; }, $regels ) ) . '</ul>' : '';
}

/**
 * Een kaart met kopbalk.
 *
 * @param  string $kop    Kop.
 * @param  string $inhoud HTML.
 * @param  string $klas   Extra klasse.
 * @return string
 */
function sfp_calculator_blad_kaart( $kop, $inhoud, $klas = '' ) {
    return '<div class="kaart ' . esc_attr( $klas ) . '"><h3>' . esc_html( $kop ) . '</h3><div class="in">' . $inhoud . '</div></div>';
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
    $vinken  = function ( array $r, $klas = '' ) use ( $e ) {
        return sfp_calculator_blad_vinken( $r, $e, $klas );
    };
    $regels  = function ( $tekst ) {
        return preg_split( '/\r\n|\r|\n/', (string) $tekst );
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

    // Wat erin zit, kort (elke regel past op één regel).
    $erin = array();
    if ( ! $route_b && $o ) {
        foreach ( (array) $o['regels'] as $r ) {
            $code = (string) $r['code'];
            if ( 0 === strpos( $code, 'vorm_' ) || 0 === strpos( $code, 'voordeel' ) || 'groepsvoordeel' === $code ) {
                continue;
            }
            $kort   = $t( 'erin_' . $code );
            $erin[] = ! empty( $r['gratis'] ) ? $t( 'pdf_gratis', array( 'n' => $kort ) ) : $kort;
        }
        $erin[] = $t( 'erin_calls' );
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
    if ( $route_b ) {
        $titel   = '<h1>' . $e( $t( 'pdf_kop_evenement' ) ) . '</h1>';
        $kernzin = $t( 'pdf_kernzin_evenement', array( 'n' => (int) ( $keuzes['sprekers'] ?? 0 ) ) );
    } else {
        $titel   = '<h1>' . ( '' !== $org ? $e( $t( 'pdf_kop_org', array( 'org' => $org ) ) ) . '<br>' : '' ) . $e( $t( 'pdf_kop' ) ) . '</h1>';
        $kernzin = '';
        if ( $o ) {
            $vorm    = $t( 'vorm_' . (int) $keuzes['vorm'] );
            $kernzin = (int) $o['groepen'] > 1 ? $t( 'pdf_kernzin', array( 'vorm' => $vorm, 'wie' => $wie, 'groepen' => $groepen ) ) : $t( 'pdf_kernzin_een', array( 'vorm' => $vorm, 'wie' => $wie ) );
        }
    }
    $meta = '<div class="meta"><span class="lab">' . $e( $t( 'begroting' ) ) . '</span>'
        . '<span class="lab">' . $e( $t( 'pdf_datum' ) ) . ' <b>' . $e( $datum( $vandaag ) ) . '</b></span>'
        . '<span class="lab">' . $e( $t( 'pdf_kenmerk' ) ) . ' <b>' . $e( $rij['kenmerk'] ) . '</b></span>'
        . ( '' !== $naam ? '<span class="lab">' . $e( $t( 'pdf_voor' ) ) . ' <b>' . $e( implode( ', ', array_filter( array( $naam, $afd ), 'strlen' ) ) ) . '</b></span>' : '' )
        . '</div>';
    if ( $route_b ) {
        $bedrag_html = '<span class="lab">' . $e( $t( 'richtprijs' ) ) . '</span><p class="getal">' . $e( $bedrag( $u['evenement_prijs'] ?? 0 ) ) . '</p>'
            . '<p class="sub">' . $e( $t( 'evenement_prijs', array( 'prijs' => $bedrag( $u['evenement_prijs'] ?? 0 ) ) ) ) . '</p>';
    } elseif ( $o ) {
        $bedrag_html = '<span class="lab">' . $e( ! empty( $u['richtprijs'] ) ? $t( 'richtprijs' ) : $t( 'totaal' ) ) . '</span>'
            . '<p class="getal">' . $e( $bedrag( $o['totaal_excl'] ) ) . '</p>'
            . '<p class="sub">' . $e( $t( 'excl' ) ) . '<br>' . $e( $t( 'pdf_incl_kort', array( 'prijs' => $bedrag( $o['totaal_incl'] ) ) ) ) . '</p>'
            . ( ! empty( $u['richtprijs'] ) ? '<p class="sub">' . $e( $t( 'pdf_maatwerk' ) ) . '</p>' : '' );
    } else {
        $bedrag_html = '';
    }
    $bedrag_html .= '<div class="geldig"><span class="lab">' . $e( $t( 'pdf_geldig' ) ) . '</span><p>' . $e( $datum( sfp_calculator_geldig_tot( $rij ) ) ) . '</p></div>';
    $kort = sfp_calculator_blad_kaart(
        $t( 'pdf_kort' ),
        '<div class="bedrag">' . $bedrag_html . '</div>'
        . ( $erin ? '<div><span class="lab" style="margin-bottom:10pt">' . $e( $t( 'pdf_erin' ) ) . '</span>' . $vinken( $erin, 'kort' . ( count( $erin ) > 5 ? ' twee' : '' ) ) . '</div>' : '<div></div>' ),
        'kort'
    );
    $doel = '' !== ( $sit['doel'] ?? '' ) && ! $route_b
        ? '<div class="aandacht"><span class="lab">' . $e( $t( 'pdf_h_doel' ) ) . '</span><p>' . nl2br( $e( $sit['doel'] ) ) . '</p></div>'
        : '';
    $paginas[] = '<section class="pagina voorblad">'
        . '<div class="top">' . $meta . $logo . '</div>'
        . '<div class="titel">' . $titel . ( '' !== $kernzin ? '<p class="kernzin">' . $e( $kernzin ) . '</p>' : '' ) . '</div>'
        . $kort . $doel
        . '<div class="duw">' . sfp_calculator_blad_cijfers( $inst, $label, $t, $e ) . '</div>'
        . '</section>';

    /* ---------- 1. De begroting ---------- */
    if ( ! $route_b && $o ) {
        $groep = array();
        foreach ( array_values( (array) $o['verdeling'] ) as $i => $n ) {
            $groep[] = $t( 'pdf_groep_n', array( 'n' => $i + 1 ) ) . ': ' . $t( 'deelnemers_n', array( 'n' => (int) $n ) );
        }
        $niet   = $regels( $t( 'pdf_niet_lijst' ) );
        $niet[] = $t( 'pdf_locatie' ) . ': ' . ( ! empty( $keuzes['locatie'] ) ? $t( 'locatie_anders' ) . ' (' . $t( 'op_aanvraag' ) . ')' : $t( 'locatie_klant' ) );
        $vw     = '<p class="klein">' . $e( $t( 'pdf_voorwaarden_zin' ) ) . '</p>';
        $vw_url = sfp_calculator_url( 'voorwaarden' );
        if ( '' !== $vw_url ) {
            $vw .= '<p class="klein"><a href="' . esc_url( $vw_url ) . '">' . $e( $t( 'pdf_voorwaarden_link' ) ) . '</a></p>';
        }
        $paginas[] = '<section class="pagina">'
            . $kop( $t( 'pdf_h_begroting' ), $t( 'pdf_begroting_zin' ) )
            . sfp_calculator_blad_tabel( $rij, true )
            . '<div class="rij r3">'
            . sfp_calculator_blad_kaart( $t( 'pdf_groepen_kop' ), $vinken( $groep ) . '<p class="klein" style="margin-top:10pt">' . $e( $t( 'pdf_groepsgrootte_zin' ) ) . '</p>' )
            . sfp_calculator_blad_kaart( $t( 'pdf_inbegrepen' ), $vinken( $regels( $t( 'pdf_inbegrepen_lijst' ) ) ) )
            . sfp_calculator_blad_kaart( $t( 'pdf_niet' ), $vinken( $niet, 'streep' ), 'open' )
            . '</div>'
            . '<div class="voorwaarden duw"><span class="lab">' . $e( $t( 'pdf_voorwaarden' ) ) . '</span>' . $vw . '%LEV%</div>'
            . '</section>';
    }

    /* ---------- 2. Het programma, 3. Zo leg je het intern voor ---------- */
    $prog = '';
    if ( ! $route_b && ! empty( $keuzes['vorm'] ) ) {
        $dagen = sfp_calculator_blad_dagen( $inst['programma'][ (string) $keuzes['vorm'] ] ?? '' );
        $prog  = $kop( $t( 'pdf_h_programma' ), $t( 'pdf_opbouw' ) . ( '' !== $inst['methode'] ? ' ' . $t( 'pdf_methode', array( 'n' => $inst['methode'] ) ) : '' ) . ' ' . $t( 'pdf_programma_zin' ) );
        if ( $dagen ) {
            $prog .= '<div class="dagen" style="--n:' . max( 1, count( $dagen ) ) . '">';
            foreach ( $dagen as $d => $r ) {
                $prog .= sfp_calculator_blad_kaart( $d ? $t( 'pdf_dag', array( 'n' => $d ) ) : $t( 'pdf_h_programma' ), $vinken( $r ) );
            }
            $prog .= '</div>';
        }
    }
    $stappen = '<div class="rij r3">';
    foreach ( array( 1, 2, 3 ) as $s ) {
        $stappen .= '<div class="stap"><span class="num">' . $s . '</span><p>' . $e( $t( 'pdf_stap_' . $s ) ) . '</p></div>';
    }
    $stappen .= '</div>';
    $gaven    = array();
    if ( ! empty( $keuzes['fase'] ) ) {
        $gaven[] = array( $t( 'pdf_fase' ), $t( 'fase_' . $keuzes['fase'] ) );
    }
    foreach ( sfp_calculator_intern_regels( array( 'intern' => array( 'beslist' => $keuzes['intern']['beslist'] ?? array(), 'budget' => $keuzes['intern']['budget'] ?? '' ) ), $taal, $valuta ) as $l => $w ) {
        $gaven[] = array( $l, $w );
    }
    $gaven_html = $gaven ? '<div class="aandacht"><span class="lab">' . $e( $t( 'pdf_aangegeven' ) ) . '</span><div class="gaven">'
        . implode( '', array_map( function ( $a ) use ( $e ) { return '<div><span class="lab" style="opacity:.7">' . $e( $a[0] ) . '</span><p>' . $e( $a[1] ) . '</p></div>'; }, $gaven ) ) . '</div><p class="klein" style="margin-top:10pt">' . $e( $t( 'pdf_aankondiging' ) ) . '</p></div>' : '<p class="klein">' . $e( $t( 'pdf_aankondiging' ) ) . '</p>';
    $paginas[] = '<section class="pagina">'
        . $prog
        . $kop( $t( 'pdf_h_intern' ), $t( 'pdf_intern_zin' ) )
        . $stappen . $gaven_html
        . sfp_calculator_blad_volgende( $inst, $boek, $t, $e )
        . '</section>';

    /* ---------- Bijlage: voor inkoop ---------- */
    $lev = '';
    foreach ( array_keys( sfp_page_config_bedrijf_velden() ) as $sleutel ) {
        if ( 'label' !== $sleutel && '' !== ( $bedrijf[ $sleutel ] ?? '' ) ) {
            $lev .= '<tr><td>' . $e( $t( 'pdf_lev_' . $sleutel ) ) . '</td><td>' . nl2br( $e( $bedrijf[ $sleutel ] ) ) . '</td></tr>';
        }
    }
    $inkoop = ! empty( $keuzes['inkoop']['aan'] );
    foreach ( $paginas as $i => $p ) {
        $paginas[ $i ] = str_replace( '%LEV%', ! $inkoop && '' !== $lev ? '<table class="lev" style="margin-top:8pt;width:62%">' . $lev . '</table>' : '', $p );
    }
    if ( $inkoop ) {
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
            . '<div class="rij r2">'
            . sfp_calculator_blad_kaart( $t( 'pdf_leverancier' ), '<table class="lev">' . $lev . '</table>' )
            . sfp_calculator_blad_kaart( $t( 'pdf_jullie' ), '<table class="lev">' . $hun . '</table>' )
            . '</div></section>';
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
    return '<div class="volgende" style="grid-template-columns:' . ( '' !== $foto ? 'auto ' : '' ) . '1fr' . ( '' !== $qr ? ' auto' : '' ) . '">'
        . ( '' !== $foto ? '<img class="foto" alt="" src="' . esc_attr( $foto ) . '">' : '' )
        . $mid
        . ( '' !== $qr ? '<div class="qr">' . $qr . '<span class="lab">' . $e( $t( 'pdf_scan' ) ) . '</span><a href="' . esc_url( $boek ) . '">' . $e( $t( 'pdf_of_klik' ) ) . '</a></div>' : '' )
        . '</div>';
}

/**
 * Sociaal bewijs: de quote, de partnerzin, de cijfers (SFP Beheer, anders de
 * netwerknorm) en "Referenties op aanvraag". Begint een feit met een getal,
 * dan staat dat getal groot.
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
    $partner = 'SFP' !== sfp_calculator_site_afk() ? '<p class="partner">' . $e( $t( 'pdf_partner', array( 'label' => $label ) ) ) . ' ' . $e( $t( 'pdf_referenties' ) ) . '</p>' : '<p class="partner">' . $e( $t( 'pdf_referenties' ) ) . '</p>';
    $r       = $inst['referentie'];
    $quote   = '' !== trim( (string) $r['citaat'] )
        ? '<blockquote class="kernzin"><p>' . nl2br( $e( $r['citaat'] ) ) . '</p><span class="lab">' . $e( implode( ', ', array_filter( array( $r['naam'], $r['functie'] ), 'strlen' ) ) ) . '</span></blockquote>'
        : '';
    $tegels = '';
    foreach ( $feiten as $f ) {
        if ( preg_match( '/^(.*?)(\d[\d.,]*\+?)(.*)$/u', $f, $m ) ) {
            $tegels .= '<div class="feit"><p class="getal">' . $e( $m[2] ) . '</p><p>' . $e( trim( trim( $m[1] ) . ' ' . trim( $m[3] ) ) ) . '</p></div>';
        } else {
            $tegels .= '<div class="feit"><p>' . $e( $f ) . '</p></div>';
        }
    }
    return '<div class="bewijs">' . ( '' !== $tegels ? '<div class="feiten">' . $tegels . '</div>' : '' ) . $partner . $quote . '</div>';
}
