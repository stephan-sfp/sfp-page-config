<?php
/**
 * SFP Page Config - Banner
 *
 * Eén banner boven de header van de site:
 *
 *   1. Aanbod (boek, e-course): per site in SFP Beheer, Onderdelen, Banner,
 *      met tekst, link en een periode. Een lopend aanbod gaat voor de cursus.
 *      Staan er meer, dan geldt de volgorde in de lijst.
 *   2. Cursus: vanzelf uit de cursusdata. De eerstvolgende groep van de site
 *      (eerste lesdag vandaag of later), tot en met de eerste lesdag. Geen
 *      groep, dan geen cursusbanner.
 *
 * Per banner kies je waar hij staat: overal, of alleen op de homepage,
 * artikelen en begrippen (plus pagina's die je noemt). Op de pagina waar hij
 * naartoe wijst en op pagina's die je uitzondert staat hij niet.
 *
 * De server kiest de banner. Omdat pagina's in de cache staan, geeft hij ook
 * de banners mee die binnenkort beginnen of nog lopen; een klein script kiest
 * met de datum van vandaag (tijdzone van de site) opnieuw als de pagina uit
 * de cache komt. Wegklikken onthoudt de browser per banner.
 *
 * @package SFP_Page_Config
 * @since   3.17.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Instellingen
 * ====================================================================== */

/**
 * De kleurrollen die een banner kan dragen (includes/fundament.php).
 *
 * @return string[]
 */
function sfp_page_config_banner_rollen() {
    return array( 'primair', 'secundair', 'tertiair', 'links', 'hover', 'tint-1', 'tint-2', 'tint-3', 'wit', 'body' );
}

/**
 * Schoon een lijst pagina-ID's op.
 *
 * @param  mixed $ruw Lijst of tekst met komma's.
 * @return int[]
 */
function sfp_page_config_banner_ids( $ruw ) {
    if ( ! is_array( $ruw ) ) {
        $ruw = preg_split( '/[\s,]+/', (string) $ruw );
    }
    return array_values( array_unique( array_filter( array_map( 'absint', $ruw ) ) ) );
}

/**
 * Schoon een datum (JJJJ-MM-DD) op, of ''.
 *
 * @param  mixed $d Datum.
 * @return string
 */
function sfp_page_config_banner_datum( $d ) {
    $d = trim( (string) $d );
    return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) && strtotime( $d ) ? $d : '';
}

/**
 * Schoon de plaatsing van een banner op.
 *
 * @param  array $ruw Ruwe waarden.
 * @return array{waar: string, ook: int[], niet: int[]}
 */
function sfp_page_config_banner_plaats_schoon( $ruw ) {
    $ruw = is_array( $ruw ) ? $ruw : array();
    return array(
        'waar' => isset( $ruw['waar'] ) && 'inhoud' === $ruw['waar'] ? 'inhoud' : 'overal',
        'ook'  => sfp_page_config_banner_ids( isset( $ruw['ook'] ) ? $ruw['ook'] : array() ),
        'niet' => sfp_page_config_banner_ids( isset( $ruw['niet'] ) ? $ruw['niet'] : array() ),
    );
}

/**
 * Schoon de instelling banner op.
 *
 * @param  mixed $ruw Ruwe instelling.
 * @return array
 */
function sfp_page_config_banner_schoon( $ruw ) {
    $ruw    = is_array( $ruw ) ? $ruw : array();
    $rollen = sfp_page_config_banner_rollen();
    $uit    = array(
        'kleur'       => isset( $ruw['kleur'] ) && in_array( $ruw['kleur'], $rollen, true ) ? $ruw['kleur'] : '',
        'tekst_kleur' => isset( $ruw['tekst_kleur'] ) && in_array( $ruw['tekst_kleur'], $rollen, true ) ? $ruw['tekst_kleur'] : '',
        'cursus'      => array_merge(
            array( 'aan' => ! isset( $ruw['cursus']['aan'] ) || in_array( $ruw['cursus']['aan'], array( true, 1, '1', 'true' ), true ) ? '1' : '0' ),
            sfp_page_config_banner_plaats_schoon( isset( $ruw['cursus'] ) ? $ruw['cursus'] : array() )
        ),
        'aanbod'      => array(),
    );
    if ( ! empty( $ruw['aanbod'] ) && is_array( $ruw['aanbod'] ) ) {
        foreach ( array_slice( $ruw['aanbod'], 0, 10 ) as $a ) {
            if ( ! is_array( $a ) ) {
                continue;
            }
            $item = array_merge(
                array(
                    'label'     => sanitize_text_field( (string) ( $a['label'] ?? '' ) ),
                    'titel'     => sanitize_text_field( (string) ( $a['titel'] ?? '' ) ),
                    'extra'     => sanitize_text_field( (string) ( $a['extra'] ?? '' ) ),
                    'linktekst' => sanitize_text_field( (string) ( $a['linktekst'] ?? '' ) ),
                    'pagina'    => absint( $a['pagina'] ?? 0 ),
                    'link'      => esc_url_raw( (string) ( $a['link'] ?? '' ) ),
                    'van'       => sfp_page_config_banner_datum( $a['van'] ?? '' ),
                    'tot'       => sfp_page_config_banner_datum( $a['tot'] ?? '' ),
                ),
                sfp_page_config_banner_plaats_schoon( $a )
            );
            // Zonder titel of zonder bestemming is er niets te tonen.
            if ( '' === $item['titel'] || ( ! $item['pagina'] && '' === $item['link'] ) ) {
                continue;
            }
            $uit['aanbod'][] = $item;
        }
    }
    return $uit;
}

/**
 * De bewaarde instelling banner, opgeschoond.
 *
 * @return array
 */
function sfp_page_config_banner_instelling() {
    $alle = sfp_page_config_instellingen();
    return sfp_page_config_banner_schoon( isset( $alle['banner'] ) ? $alle['banner'] : array() );
}

/* =========================================================================
 * Kandidaten
 * ====================================================================== */

/**
 * De groepen van alle trainingen van de site met een eerste lesdag vandaag
 * of later, op volgorde van die dag.
 *
 * @param  int $max Hoeveel.
 * @return array<int, array{pagina: int, dag: string}>
 */
function sfp_page_config_banner_cursussen( $max = 3 ) {
    if ( ! sfp_page_config_onderdeel_aan( 'cursusdata' ) || ! function_exists( 'sfp_page_config_cursusdata_toekomst' ) ) {
        return array();
    }
    $ids = get_transient( 'sfp_dashboard_pages_training' );
    if ( ! is_array( $ids ) && function_exists( 'sfp_page_config_cursusdata_paginas' ) ) {
        $ids = array_column( sfp_page_config_cursusdata_paginas(), 'id' );
    }
    $uit = array();
    foreach ( (array) $ids as $id ) {
        if ( 'publish' !== get_post_status( (int) $id ) ) {
            continue;
        }
        foreach ( sfp_page_config_cursusdata_toekomst( (int) $id ) as $groep ) {
            $uit[] = array(
                'pagina' => (int) $id,
                'dag'    => $groep['data'][0],
            );
        }
    }
    usort(
        $uit,
        function ( $a, $b ) {
            return strcmp( $a['dag'], $b['dag'] );
        }
    );
    return array_slice( $uit, 0, max( 1, (int) $max ) );
}

/**
 * Staat een banner met deze plaatsing op de pagina die nu wordt getoond?
 *
 * @param  array $plaats  waar, ook, niet.
 * @param  int   $doel    Pagina waar de banner naartoe wijst (0: een adres).
 * @param  int   $pagina  Getoonde pagina (0: geen enkele pagina, zoals een archief).
 * @param  array $soort   front, inhoud: wat de getoonde pagina is.
 * @return bool
 */
function sfp_page_config_banner_hier( array $plaats, $doel, $pagina, array $soort ) {
    if ( $pagina && ( $pagina === (int) $doel || in_array( $pagina, $plaats['niet'], true ) ) ) {
        return false;
    }
    if ( 'overal' === $plaats['waar'] ) {
        return true;
    }
    return ! empty( $soort['front'] ) || ! empty( $soort['inhoud'] ) || ( $pagina && in_array( $pagina, $plaats['ook'], true ) );
}

/**
 * Wat de getoonde pagina is, voor de plaatsing.
 *
 * @return array{pagina: int, front: bool, inhoud: bool}
 */
function sfp_page_config_banner_context() {
    $pagina = is_singular() ? (int) get_queried_object_id() : 0;
    return array(
        'pagina' => $pagina,
        'front'  => is_front_page(),
        'inhoud' => is_singular( array( 'post', 'begrippen' ) ),
    );
}

/**
 * De banners voor de getoonde pagina, in volgorde van voorrang, met de
 * periode waarin ze gelden. Verlopen banners en banners die hier niet horen
 * staan er niet in.
 *
 * @param  array|null $context Uit sfp_page_config_banner_context(); null: nu.
 * @param  string     $vandaag JJJJ-MM-DD; leeg: vandaag in de tijdzone van de site.
 * @return array<int, array{id: string, soort: string, html: string, van: string, tot: string}>
 */
function sfp_page_config_banner_kandidaten( $context = null, $vandaag = '' ) {
    $context = null === $context ? sfp_page_config_banner_context() : $context;
    $vandaag = '' !== $vandaag ? $vandaag : wp_date( 'Y-m-d' );
    $inst    = sfp_page_config_banner_instelling();
    $uit     = array();

    foreach ( $inst['aanbod'] as $a ) {
        if ( '' !== $a['tot'] && $a['tot'] < $vandaag ) {
            continue;
        }
        if ( $a['pagina'] && 'publish' !== get_post_status( $a['pagina'] ) ) {
            continue;
        }
        if ( ! sfp_page_config_banner_hier( $a, $a['pagina'], $context['pagina'], $context ) ) {
            continue;
        }
        $link = $a['pagina'] ? (string) get_permalink( $a['pagina'] ) : $a['link'];
        $kern = ( '' !== $a['label'] ? esc_html( $a['label'] ) . ': ' : '' ) . '<strong>' . esc_html( $a['titel'] ) . '</strong>.';
        $uit[] = array(
            'id'    => 'a' . substr( md5( wp_json_encode( $a ) ), 0, 10 ),
            'soort' => 'aanbod',
            'html'  => sfp_page_config_banner_tekst_html( $kern, $a['extra'], $a['linktekst'], $link ),
            'van'   => $a['van'],
            'tot'   => $a['tot'],
        );
    }

    if ( '1' === $inst['cursus']['aan'] ) {
        foreach ( sfp_page_config_banner_cursussen( 3 ) as $c ) {
            if ( ! sfp_page_config_banner_hier( $inst['cursus'], $c['pagina'], $context['pagina'], $context ) ) {
                continue;
            }
            $naam  = html_entity_decode( get_the_title( $c['pagina'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $zin   = sfp_page_config_tekst_f( 'banner_cursus', '%%NAAM%%', sfp_page_config_format_datum( $c['dag'], 'j F' ) );
            $kern  = str_replace( '%%NAAM%%', '<strong>' . esc_html( $naam ) . '</strong>', esc_html( $zin ) );
            $anker = function_exists( 'sfp_page_config_wachtlijst_anker' ) ? sfp_page_config_wachtlijst_anker() : 'inschrijven';
            $uit[] = array(
                'id'    => 'c' . $c['pagina'] . '-' . str_replace( '-', '', $c['dag'] ),
                'soort' => 'cursus',
                'html'  => sfp_page_config_banner_tekst_html( $kern, '', sfp_page_config_tekst( 'banner_cursus_link' ), get_permalink( $c['pagina'] ) . '#' . $anker ),
                'van'   => '',
                'tot'   => $c['dag'],
            );
        }
    }
    return $uit;
}

/**
 * De tekst van één banner.
 *
 * @param  string $kern      Veilige HTML: het eerste deel, altijd zichtbaar.
 * @param  string $extra     Platte tekst: valt op een telefoon weg.
 * @param  string $linktekst Platte tekst.
 * @param  string $link      Adres.
 * @return string HTML.
 */
function sfp_page_config_banner_tekst_html( $kern, $extra, $linktekst, $link ) {
    $html = '<span class="sfp-banner__kern">' . $kern . '</span>';
    if ( '' !== trim( (string) $extra ) ) {
        $html .= ' <span class="sfp-banner__extra">' . esc_html( $extra ) . '</span>';
    }
    if ( '' !== trim( (string) $linktekst ) && '' !== (string) $link ) {
        $html .= ' <a class="sfp-banner__link" href="' . esc_url( $link ) . '">' . esc_html( $linktekst ) . '</a>';
    }
    return $html;
}

/**
 * De eerste banner die op een dag geldt.
 *
 * @param  array  $kandidaten Uit sfp_page_config_banner_kandidaten().
 * @param  string $dag        JJJJ-MM-DD.
 * @return int Index, of -1.
 */
function sfp_page_config_banner_keuze( array $kandidaten, $dag ) {
    foreach ( $kandidaten as $i => $k ) {
        if ( ( '' === $k['van'] || $k['van'] <= $dag ) && ( '' === $k['tot'] || $k['tot'] >= $dag ) ) {
            return $i;
        }
    }
    return -1;
}

/* =========================================================================
 * Voorkant
 * ====================================================================== */

add_action( 'astra_header_before', 'sfp_page_config_banner_toon', 5 );
add_action( 'wp_body_open', 'sfp_page_config_banner_toon_zonder_astra', 5 );

/**
 * Zonder Astra: de banner direct na de body.
 */
function sfp_page_config_banner_toon_zonder_astra() {
    if ( ! did_action( 'astra_header_before' ) && ! function_exists( 'astra_header_before' ) ) {
        sfp_page_config_banner_toon();
    }
}

/**
 * Print de banner.
 */
function sfp_page_config_banner_toon() {
    static $klaar = false;
    if ( $klaar || is_admin() || ! sfp_page_config_onderdeel_aan( 'banner' ) || is_404() ) {
        return;
    }
    $klaar      = true;
    $kandidaten = sfp_page_config_banner_kandidaten();
    if ( ! $kandidaten ) {
        return;
    }
    $keuze = sfp_page_config_banner_keuze( $kandidaten, wp_date( 'Y-m-d' ) );
    $inst  = sfp_page_config_banner_instelling();
    $kleur = '' !== $inst['kleur'] ? $inst['kleur'] : 'links';
    $tekst = '' !== $inst['tekst_kleur'] ? $inst['tekst_kleur'] : 'wit';

    $css = '.sfp-banner{display:block;background:var(--sfp-rol-' . $kleur . ',var(--ast-global-color-1));color:var(--sfp-rol-' . $tekst . ',var(--ast-global-color-5));font-size:15px;line-height:1.4;position:relative;z-index:30}'
        . '.sfp-banner[hidden],.sfp-banner__item[hidden]{display:none!important}'
        . '.sfp-banner__inner{max-width:1280px;margin:0 auto;padding:10px 56px 10px 24px;text-align:center}'
        . '.sfp-banner .sfp-banner__tekst{margin:0;color:inherit}'
        . '.sfp-banner .sfp-banner__tekst strong{color:inherit}'
        . '.sfp-banner .sfp-banner__link,.sfp-banner .sfp-banner__link:hover,.sfp-banner .sfp-banner__link:focus{color:inherit;font-weight:700;text-decoration:underline;text-underline-offset:3px;white-space:nowrap}'
        . '.sfp-banner .sfp-banner__link:hover{text-decoration:none}'
        . '.sfp-banner .sfp-banner__link:focus-visible,.sfp-banner .sfp-banner__sluit:focus-visible{outline:2px solid currentColor;outline-offset:2px}'
        . '.sfp-banner .sfp-banner__sluit,.sfp-banner .sfp-banner__sluit:hover,.sfp-banner .sfp-banner__sluit:focus{position:absolute;top:50%;right:12px;transform:translateY(-50%);width:36px;height:36px;min-height:0;padding:0;margin:0;border:0;border-radius:3px;background:transparent;box-shadow:none;color:inherit;font-size:24px;line-height:36px;text-align:center;cursor:pointer;text-transform:none}'
        . 'body.ast-theme-transparent-header #masthead{top:var(--sfp-banner-h,0px)}'
        . '@media (max-width:600px){.sfp-banner{font-size:14px}.sfp-banner__inner{padding:8px 48px 8px 16px}.sfp-banner .sfp-banner__extra{display:none}.sfp-banner .sfp-banner__link{display:block;margin:2px 0 0}.sfp-banner .sfp-banner__sluit,.sfp-banner .sfp-banner__sluit:hover,.sfp-banner .sfp-banner__sluit:focus{right:6px}}';

    $items = '';
    foreach ( $kandidaten as $i => $k ) {
        $items .= '<p class="sfp-banner__tekst sfp-banner__item" data-id="' . esc_attr( $k['id'] ) . '" data-van="' . esc_attr( $k['van'] ) . '" data-tot="' . esc_attr( $k['tot'] ) . '"' . ( $i === $keuze ? '' : ' hidden' ) . '>' . $k['html'] . '</p>';
    }

    $tz = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : 'UTC';
    $js = '(function(){var b=document.getElementById("sfp-banner"),d=document.documentElement;if(!b)return;'
        . 'var k="sfp_banner_dicht",dag;try{dag=new Intl.DateTimeFormat("en-CA",{timeZone:b.getAttribute("data-tz")}).format(new Date());}catch(e){dag="";}'
        . 'var items=[].slice.call(b.querySelectorAll(".sfp-banner__item")),gekozen=null;'
        . 'items.forEach(function(p){var v=p.getAttribute("data-van"),t=p.getAttribute("data-tot"),ok=!dag||((!v||v<=dag)&&(!t||t>=dag));p.hidden=!(ok&&!gekozen);if(ok&&!gekozen)gekozen=p;});'
        . 'var dicht="";try{dicht=localStorage.getItem(k)||"";}catch(e){}'
        . 'b.hidden=!gekozen||dicht===gekozen.getAttribute("data-id");'
        . 'function h(){d.style.setProperty("--sfp-banner-h",(b.hidden?0:b.offsetHeight)+"px");}h();window.addEventListener("resize",h);'
        . 'var s=b.querySelector(".sfp-banner__sluit");function c(){b.hidden=true;h();try{if(gekozen)localStorage.setItem(k,gekozen.getAttribute("data-id"));}catch(e){}}'
        . 'if(s){s.addEventListener("click",c);}})();';

    printf(
        '<style id="sfp-banner-css">%1$s</style><div class="sfp-banner" id="sfp-banner" role="region" aria-label="%2$s" data-tz="%3$s"%4$s><div class="sfp-banner__inner">%5$s<button type="button" class="sfp-banner__sluit" aria-label="%6$s">&times;</button></div></div><script nowprocket data-cfasync="false">%7$s</script>',
        $css, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste CSS, rollen uit een vaste lijst.
        esc_attr( sfp_page_config_tekst( 'banner_regio' ) ),
        esc_attr( $tz ),
        -1 === $keuze ? ' hidden' : '',
        $items, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- opgebouwd met esc_html en esc_url.
        esc_attr( sfp_page_config_tekst( 'banner_sluit' ) ),
        $js // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste code.
    );
}

/* =========================================================================
 * Beheer
 * ====================================================================== */

/**
 * De stand voor het scherm Banner in SFP Beheer.
 *
 * @return array
 */
function sfp_page_config_banner_stand() {
    $inst = sfp_page_config_banner_instelling();
    $nu   = array();
    foreach ( sfp_page_config_banner_cursussen( 3 ) as $c ) {
        $nu[] = array(
            'titel' => html_entity_decode( get_the_title( $c['pagina'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
            'dag'   => sfp_page_config_format_datum( $c['dag'], 'j F Y' ),
        );
    }
    $voorpagina = sfp_page_config_banner_kandidaten(
        array(
            'pagina' => (int) get_option( 'page_on_front' ),
            'front'  => true,
            'inhoud' => false,
        )
    );
    $keuze = sfp_page_config_banner_keuze( $voorpagina, wp_date( 'Y-m-d' ) );
    return array(
        'instelling' => $inst,
        'rollen'     => sfp_page_config_banner_rollen(),
        'cursussen'  => $nu,
        'nu'         => $keuze >= 0 ? wp_strip_all_tags( $voorpagina[ $keuze ]['html'] ) : '',
        'cursusdata' => sfp_page_config_onderdeel_aan( 'cursusdata' ),
    );
}
