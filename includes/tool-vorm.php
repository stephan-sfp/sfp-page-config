<?php
/**
 * SFP Page Config - Vorm Tool
 *
 * De pagina van een tool in de artikelopmaak (concept 6, besluiten S15 en
 * S19): regel boven de titel (Self-test in <pijler>), de regel onder de
 * titel (samenvatting, hoogstens één zin), de tool zelf boven de tekst,
 * de makerskaart Made by onderaan vóór het vervolg, Andere tools, en de
 * gegevens voor Google (WebApplication, gratis, isBasedOn naar "Hoe deze
 * test rekent"). Geen kopkaart, inhoudsopgave, promokaart of zijkolom.
 *
 * @package SFP_Page_Config
 * @since   3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Is dit bericht een tool in de vorm Tool?
 *
 * @param  WP_Post|int|null $post Bericht.
 * @return bool
 */
function sfp_page_config_is_tool_vorm( $post ) {
    return sfp_page_config_is_tool( $post ) && sfp_page_config_tool_vorm_aan();
}

/**
 * De naam van de soort, als tekst in de taal van de site.
 *
 * @param  array|null $b Beschrijving.
 * @return string
 */
function sfp_page_config_tool_soortnaam( $b ) {
    $soorten = sfp_page_config_tool_soorten();
    $sleutel = $b && isset( $soorten[ $b['soort'] ] ) ? $soorten[ $b['soort'] ] : 'tools';
    return sfp_page_config_artikel_tekst( $sleutel );
}

/**
 * De regel boven de titel: Self-test in <pijler>, of alleen de soort met een
 * link naar het toolsoverzicht.
 *
 * @param  WP_Post $post Tool.
 * @return string
 */
function sfp_page_config_tool_pijlerregel( $post ) {
    $t       = 'sfp_page_config_artikel_tekst';
    $b       = sfp_page_config_tool_beschrijving( $post );
    $archief = get_post_type_archive_link( 'tool' );
    $soort   = sfp_page_config_tool_soortnaam( $b );
    $woord   = $archief ? '<a href="' . esc_url( $archief ) . '">' . esc_html( $soort ) . '</a>' : esc_html( $soort );
    $pijler  = function_exists( 'sfp_page_config_begrip_pijler' ) ? sfp_page_config_begrip_pijler( $post ) : null;
    if ( $pijler ) {
        $url  = get_term_link( $pijler );
        $naam = is_wp_error( $url ) ? esc_html( $pijler->name ) : '<a href="' . esc_url( $url ) . '">' . esc_html( $pijler->name ) . '</a>';
        return '<p class="sfp-art-pijler">' . sprintf( esc_html( $t( 'tool_in' ) ), $woord, $naam ) . '</p>';
    }
    return '<p class="sfp-art-pijler">' . $woord . '</p>';
}

/**
 * De regel onder de titel: de samenvatting, hoogstens één zin (S15).
 *
 * @param  WP_Post $post Tool.
 * @return string
 */
function sfp_page_config_tool_ondertitel( $post ) {
    $zin = trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
    if ( '' === $zin ) {
        return '';
    }
    return '<p class="sfp-art-lead sfp-tool-lead">' . esc_html( $zin ) . '</p>';
}

/**
 * De makerskaart "Made by" onderaan: foto, naam, één zin, Updated,
 * Fact-checked, Based on (link naar de rekenregel), More about.
 *
 * @param  WP_Post $post Tool.
 * @return string
 */
function sfp_page_config_tool_makerskaart( $post ) {
    $t      = 'sfp_page_config_artikel_tekst';
    $auteur = (int) $post->post_author;
    $naam   = get_the_author_meta( 'display_name', $auteur );
    if ( ! $auteur || '' === (string) $naam ) {
        return '';
    }
    $voornaam = trim( (string) get_the_author_meta( 'first_name', $auteur ) );
    $rol      = trim( (string) get_user_meta( $auteur, 'sfp_rol', true ) );
    $bio      = trim( (string) get_the_author_meta( 'description', $auteur ) );
    $zin      = '';
    foreach ( preg_split( '/\r\n|\r|\n|<br\s*\/?>/i', $bio ) as $regel ) {
        $regel = trim( wp_strip_all_tags( $regel ) );
        if ( '' !== $regel && 0 !== strpos( $regel, '√' ) ) {
            $zin = $regel;
            break;
        }
    }
    $b          = sfp_page_config_tool_beschrijving( $post );
    $bijgewerkt = (int) get_post_timestamp( $post, 'modified' );
    $bijgewerkt = $bijgewerkt ? $bijgewerkt : time();
    $feiten     = array();
    $feiten[]   = '<span><time datetime="' . esc_attr( wp_date( 'c', $bijgewerkt ) ) . '">' . esc_html( sprintf( $t( 'tool_updated' ), wp_date( 'j M Y', $bijgewerkt ) ) ) . '</time></span>';
    $check_url  = sfp_page_config_link_instelling( 'artikel_factcheck' );
    if ( '' === $check_url ) {
        $check_url = sfp_page_config_link_instelling( 'artikel_redactie' );
    }
    $check    = sfp_page_config_artikel_icoon( 'vink' ) . esc_html( $t( 'gecheckt' ) );
    $feiten[] = '<span>' . ( '' !== $check_url ? '<a class="sfp-art-kop__check" href="' . esc_url( $check_url ) . '">' . $check . '</a>' : '<span class="sfp-art-kop__check">' . $check . '</span>' ) . '</span>';
    if ( $b ) {
        $basis    = '' !== $b['bron']['model'] ? $b['bron']['model'] : $t( 'tool_hoe_rekent' );
        $feiten[] = '<span><a href="#sfp-tool-' . (int) $post->ID . '-hoe">' . esc_html( $t( 'tool_based_on' ) ) . ': ' . esc_html( $basis ) . '</a></span>';
    }
    return '<section class="sfp-art-auteur sfp-tool-maker" aria-labelledby="sfp-tool-maker-kop">'
        . get_avatar( $auteur, 72, '', $naam, array( 'loading' => 'lazy' ) )
        . '<div class="sfp-art-auteur__kop"><span class="sfp-blok-label">' . esc_html( $t( 'tool_made_by' ) ) . '</span><h3 id="sfp-tool-maker-kop">' . esc_html( $naam ) . ( '' !== $rol ? ' <small>' . esc_html( $rol ) . '</small>' : '' ) . '</h3></div>'
        . '<div class="sfp-art-auteur__tekst">' . ( '' !== $zin ? '<p>' . esc_html( $zin ) . '</p>' : '' )
        . '<p class="sfp-art-maker sfp-tool-maker__feiten">' . implode( '', $feiten ) . '</p>'
        . '<a class="sfp-pijl" href="' . esc_url( sfp_page_config_artikel_auteur_url( $auteur ) ) . '">' . esc_html( sprintf( $t( 'meer_over' ), '' !== $voornaam ? $voornaam : $naam ) ) . '</a>'
        . '</div></section>';
}

/**
 * Andere tools: eerst dezelfde pijler, dan de rest, hoogstens zes.
 *
 * @param  WP_Post $post Tool.
 * @return string
 */
function sfp_page_config_tool_andere( $post ) {
    $t      = 'sfp_page_config_artikel_tekst';
    $pijler = (int) get_post_meta( $post->ID, 'sfp_pijler', true );
    $eigen  = array();
    $rest   = array();
    foreach ( sfp_page_config_tools() as $p ) {
        if ( $p->ID === $post->ID ) {
            continue;
        }
        if ( $pijler && (int) get_post_meta( $p->ID, 'sfp_pijler', true ) === $pijler ) {
            $eigen[] = $p;
        } else {
            $rest[] = $p;
        }
    }
    $lijst = array_slice( array_merge( $eigen, $rest ), 0, 6 );
    if ( ! $lijst ) {
        return '';
    }
    $items = '';
    foreach ( $lijst as $p ) {
        $b      = sfp_page_config_tool_beschrijving( $p );
        $soort  = sfp_page_config_tool_soortnaam( $b );
        $zin    = trim( wp_strip_all_tags( (string) $p->post_excerpt ) );
        $items .= '<a class="sfp-verwant__item" href="' . esc_url( get_permalink( $p ) ) . '"><small>' . esc_html( $soort ) . '</small><b>' . esc_html( wp_strip_all_tags( get_the_title( $p ) ) ) . '</b>' . ( '' !== $zin ? '<span>' . esc_html( $zin ) . '</span>' : '' ) . '</a>';
    }
    $term = function_exists( 'sfp_page_config_begrip_pijler' ) ? sfp_page_config_begrip_pijler( $post ) : null;
    $kop  = $term && $eigen ? sprintf( $t( 'tool_andere_pijler' ), $term->name ) : $t( 'tool_andere' );
    return '<section class="sfp-leesverder sfp-tool-andere" aria-label="' . esc_attr( $t( 'tool_andere' ) ) . '"><div class="sfp-leesverder__deel"><span class="sfp-blok-label">' . esc_html( $kop ) . '</span><div class="sfp-verwant">' . $items . '</div></div></section>';
}

add_action( 'wp_head', 'sfp_page_config_tool_schema', 30 );

/**
 * WebApplication voor een tool: gratis, met de maker, de datum en isBasedOn
 * naar de ingeklapte rekenregel op de pagina.
 */
function sfp_page_config_tool_schema() {
    if ( ! is_singular( 'tool' ) || ! sfp_page_config_tool_aan() ) {
        return;
    }
    $post = get_queried_object();
    if ( ! $post instanceof WP_Post ) {
        return;
    }
    $b   = sfp_page_config_tool_beschrijving( $post );
    $url = get_permalink( $post );
    $gegevens = array(
        '@context'             => 'https://schema.org',
        '@type'                => 'WebApplication',
        '@id'                  => $url . '#tool',
        'name'                 => wp_strip_all_tags( get_the_title( $post ) ),
        'url'                  => $url,
        'applicationCategory'  => 'EducationalApplication',
        'operatingSystem'      => 'Any',
        'browserRequirements'  => 'Requires JavaScript',
        'isAccessibleForFree'  => true,
        'offers'               => array(
            '@type'         => 'Offer',
            'price'         => '0',
            'priceCurrency' => 'EUR',
        ),
        'inLanguage'           => get_bloginfo( 'language' ),
        'datePublished'        => wp_date( 'c', (int) get_post_timestamp( $post ) ),
        'dateModified'         => wp_date( 'c', (int) get_post_timestamp( $post, 'modified' ) ),
    );
    $zin = trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
    if ( '' !== $zin ) {
        $gegevens['description'] = $zin;
    }
    if ( $b ) {
        $gegevens['isBasedOn'] = $url . '#sfp-tool-' . (int) $post->ID . '-hoe';
        if ( '' !== $b['bron']['link'] ) {
            $gegevens['citation'] = $b['bron']['link'];
        }
    }
    $maker = function_exists( 'sfp_page_config_maker_id' ) ? sfp_page_config_maker_id( (int) $post->post_author ) : '';
    if ( '' !== $maker ) {
        $gegevens['author']  = array( '@id' => $maker );
        $gegevens['creator'] = array( '@id' => $maker );
    }
    echo '<script type="application/ld+json" id="sfp-tool-schema">' . wp_json_encode( $gegevens, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON uit eigen gegevens.
}

/* =========================================================================
 * Controle
 * ====================================================================== */

/**
 * De meldingen voor een tool: geen of ongeldige beschrijving, geen pijler,
 * geen samenvatting of een samenvatting van meer dan één zin, geen formulier.
 *
 * @param  WP_Post $post Bericht.
 * @return array
 */
function sfp_page_config_tool_controle( $post ) {
    $meldingen = array();
    if ( ! sfp_page_config_is_tool( $post ) || ! sfp_page_config_tool_aan() ) {
        return $meldingen;
    }
    $tekst = trim( (string) get_post_meta( $post->ID, 'sfp_tool', true ) );
    if ( '' === $tekst ) {
        $meldingen[] = array(
            'code'  => 'tool-leeg',
            'kort'  => 'Geen beschrijving',
            'tekst' => 'Deze tool heeft nog geen beschrijving. De pagina toont alleen de tekst, zonder tool.',
            'actie' => 'editor',
        );
        return $meldingen;
    }
    $uitkomst = sfp_page_config_tool_controleer_tekst( $tekst );
    if ( $uitkomst['fouten'] ) {
        $meldingen[] = array(
            'code'  => 'tool-fout',
            'kort'  => 'Beschrijving klopt niet',
            'tekst' => implode( ' ', $uitkomst['fouten'] ),
            'actie' => 'editor',
        );
    }
    foreach ( $uitkomst['let_op'] as $i => $l ) {
        $meldingen[] = array(
            'code'  => 'tool-letop:' . substr( md5( $l ), 0, 8 ),
            'kort'  => 'Let op in de beschrijving',
            'tekst' => $l,
            'actie' => 'negeer',
        );
    }
    if ( ! (int) get_post_meta( $post->ID, 'sfp_pijler', true ) ) {
        $meldingen[] = array(
            'code'  => 'tool-pijler',
            'kort'  => 'Geen pijler',
            'tekst' => 'Deze tool hoort bij geen pijler. De regel boven de titel toont dan alleen de soort.',
            'actie' => 'negeer',
        );
    }
    $zin = trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
    if ( '' === $zin ) {
        $meldingen[] = array(
            'code'  => 'tool-samenvatting',
            'kort'  => 'Geen regel onder de titel',
            'tekst' => 'Vul de samenvatting in: één zin onder de titel, ook de omschrijving voor Google.',
            'actie' => 'editor',
        );
    } elseif ( preg_match_all( '/[.!?](\s|$)/u', $zin ) > 1 ) {
        $meldingen[] = array(
            'code'  => 'tool-samenvatting-lang',
            'kort'  => 'Regel onder de titel is meer dan één zin',
            'tekst' => 'De samenvatting heeft meer dan één zin. Onder de titel van een tool staat hoogstens één zin (besluit S15).',
            'actie' => 'editor',
        );
    }
    $b = $uitkomst['beschrijving'];
    if ( $b && 0 === $b['acties']['formulier'] ) {
        $meldingen[] = array(
            'code'  => 'tool-formulier',
            'kort'  => 'Geen formulier na de uitslag',
            'tekst' => 'Deze tool heeft geen SureForms-formulier na de uitslag (acties.formulier).',
            'actie' => 'negeer',
        );
    }
    return $meldingen;
}

/* =========================================================================
 * SFP Beheer
 * ====================================================================== */

/**
 * De stand voor het scherm Tools in SFP Beheer: de tools met hun soort en
 * meldingen, de duimpjes en het adres voor tekstfeedback.
 *
 * @return array
 */
function sfp_page_config_tool_beheer_stand() {
    $lijst     = array();
    $tellingen = function_exists( 'sfp_page_config_tool_feedback_tellingen' ) ? sfp_page_config_tool_feedback_tellingen() : array();
    if ( post_type_exists( 'tool' ) ) {
        $alle = get_posts(
            array(
                'post_type'        => 'tool',
                'post_status'      => array( 'publish', 'draft', 'pending', 'future', 'private' ),
                'posts_per_page'   => 200,
                'orderby'          => 'title',
                'order'            => 'ASC',
                'no_found_rows'    => true,
                'suppress_filters' => true,
            )
        );
        foreach ( $alle as $p ) {
            $tekst     = trim( (string) get_post_meta( $p->ID, 'sfp_tool', true ) );
            $b         = '' !== $tekst ? sfp_page_config_tool_beschrijving( $p ) : null;
            $meldingen = sfp_page_config_tool_aan() ? sfp_page_config_tool_controle( $p ) : array();
            $slug      = $p->post_name;
            $lijst[]   = array(
                'id'        => (int) $p->ID,
                'titel'     => wp_strip_all_tags( get_the_title( $p ) ),
                'status'    => $p->post_status,
                'soort'     => $b ? $b['soort'] : '',
                'motor'     => '' !== $tekst,
                'fouten'    => count( array_filter( $meldingen, function ( $m ) { return 'tool-fout' === $m['code']; } ) ),
                'meldingen' => count( $meldingen ),
                'omhoog'    => isset( $tellingen[ $slug ]['omhoog'] ) ? (int) $tellingen[ $slug ]['omhoog'] : 0,
                'omlaag'    => isset( $tellingen[ $slug ]['omlaag'] ) ? (int) $tellingen[ $slug ]['omlaag'] : 0,
                'bewerk'    => get_edit_post_link( $p->ID, 'raw' ),
                'link'      => get_permalink( $p ),
            );
        }
    }
    $log = get_option( 'sfp_page_config_tool_feedback_log', array() );
    return array(
        'bestaat'  => post_type_exists( 'tool' ),
        'tools'    => $lijst,
        'adres'    => (string) sfp_page_config_get_setting( 'tool_feedback_mail', '' ),
        'beheer'   => (string) get_option( 'admin_email' ),
        'log'      => is_array( $log ) ? array_slice( array_reverse( $log ), 0, 20 ) : array(),
    );
}
