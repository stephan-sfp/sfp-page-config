<?php
/**
 * SFP Page Config - Zelftest-oproep (sinds 3.6.0)
 *
 * Eén bron voor de oproep om de zelftest te doen: de test van de site, een
 * kop, één zin, een knop en het adres van de testpagina. Die bron gebruiken:
 *
 *   - het blok Zelftest-oproep (sfp/zelftest-cta) en de code
 *     [sfp_zelftest_cta test="" bron=""], voor de leadmagnetplek op trainings-
 *     en coachpagina's en onder artikelen (in het vervolg van een pijler);
 *   - de promokaart en de actie in de kopkaart van een pijler.
 *
 * De tekst staat per test in SFP Beheer, Zelftest (cta_kop, cta_zin,
 * cta_knop). Leeg: de naam van de test uit het pakket en de vaste knoptekst.
 *
 * Elke plaatsing zet een bronlabel in de link (?bron=...), zodat GA4 ziet
 * waar een bezoeker vandaan kwam.
 *
 * De zelftest is geen tool: op een toolpagina en het overzicht van de tools
 * verschijnt de oproep niet (besluit Stephan, 18-10-2026).
 *
 * @package SFP_Page_Config
 * @since   3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De test van de site: de eerste test met een gepubliceerde testpagina.
 *
 * @return string Naam van de test, of ''.
 */
function sfp_zelftest_site_test() {
    if ( ! function_exists( 'sfp_zelftest_tests' ) ) {
        return '';
    }
    foreach ( sfp_zelftest_tests() as $test ) {
        $inst = sfp_zelftest_instellingen( $test );
        $id   = (int) $inst['testpagina'];
        if ( $id && 'publish' === get_post_status( $id ) && sfp_zelftest_pakket( $test ) ) {
            return $test;
        }
    }
    return '';
}

/**
 * Een schoon bronlabel: kleine letters, cijfers en streepjes, hoogstens 40.
 *
 * @param  mixed $bron Ruw label.
 * @return string
 */
function sfp_zelftest_cta_bron( $bron ) {
    $bron = strtolower( trim( (string) $bron ) );
    $bron = preg_replace( '/[^a-z0-9-]+/', '-', $bron );
    return substr( trim( $bron, '-' ), 0, 40 );
}

/**
 * De gegevens van de oproep.
 *
 * @param  string $test Naam van de test; leeg is de test van de site.
 * @param  string $bron Bronlabel voor de link.
 * @return array|null   test, naam, kop, zin, knop, url, vragen; null als er
 *                      niets te tonen is (onderdeel uit, geen pakket, testpagina
 *                      niet gepubliceerd).
 */
function sfp_zelftest_cta_gegevens( $test = '', $bron = '' ) {
    if ( ! function_exists( 'sfp_page_config_onderdeel_aan' ) || ! sfp_page_config_onderdeel_aan( 'zelftest' ) ) {
        return null;
    }
    $test = '' === (string) $test ? sfp_zelftest_site_test() : sfp_zelftest_naam( $test );
    if ( '' === $test ) {
        return null;
    }
    $pakket = sfp_zelftest_pakket( $test );
    $inst   = sfp_zelftest_instellingen( $test );
    $pagina = (int) $inst['testpagina'];
    if ( ! $pakket || ! $pagina || 'publish' !== get_post_status( $pagina ) ) {
        return null;
    }
    $naam = (string) sfp_zelftest_tekst( $pakket, 'algemeen.testnaam' );
    $url  = (string) get_permalink( $pagina );
    $bron = sfp_zelftest_cta_bron( $bron );
    if ( '' !== $bron ) {
        $url = add_query_arg( 'bron', $bron, $url );
    }
    // Het aantal vragen van de eerste (standaard)lengte.
    $vragen  = 0;
    $lengtes = isset( $pakket['manifest']['lengtes'] ) ? (array) $pakket['manifest']['lengtes'] : array();
    $eerste  = reset( $lengtes );
    if ( is_array( $eerste ) && isset( $eerste['vragen'] ) ) {
        $vragen = count( (array) $eerste['vragen'] );
    }
    $t = function_exists( 'sfp_page_config_artikel_tekst' ) ? 'sfp_page_config_artikel_tekst' : null;
    return array(
        'test'   => $test,
        'naam'   => $naam,
        'kop'    => '' !== $inst['cta_kop'] ? $inst['cta_kop'] : $naam,
        'zin'    => $inst['cta_zin'],
        'knop'   => '' !== $inst['cta_knop'] ? $inst['cta_knop'] : ( $t ? $t( 'doe_zelftest' ) : '' ),
        'url'    => $url,
        'vragen' => $vragen,
    );
}

/**
 * Staan we op een toolpagina of het overzicht van de tools?
 *
 * @return bool
 */
function sfp_zelftest_cta_op_tool() {
    if ( ! post_type_exists( 'tool' ) ) {
        return false;
    }
    if ( is_singular( 'tool' ) || is_post_type_archive( 'tool' ) ) {
        return true;
    }
    $post = get_post();
    return $post && 'tool' === $post->post_type;
}

/**
 * De oproep als HTML.
 *
 * @param  array  $g       Gegevens uit sfp_zelftest_cta_gegevens().
 * @param  string $extra   Extra attributen voor de omhulling (blok).
 * @return string
 */
function sfp_zelftest_cta_html( array $g, $extra = '' ) {
    $label = function_exists( 'sfp_page_config_artikel_tekst' ) ? sfp_page_config_artikel_tekst( 'zelftest' ) : '';
    $id    = wp_unique_id( 'sfp-zt-cta-' );
    $attr  = '' !== $extra ? $extra : 'class="sfp-zt-cta"';
    return '<aside ' . $attr . ' aria-labelledby="' . esc_attr( $id ) . '" data-sfp-zelftest="' . esc_attr( $g['test'] ) . '">'
        . '<span class="sfp-zt-cta__icoon" aria-hidden="true">' . ( function_exists( 'sfp_page_config_artikel_icoon' ) ? sfp_page_config_artikel_icoon( 'test' ) : '' ) . '</span>'
        . '<div class="sfp-zt-cta__tekst">'
        . '<span class="sfp-blok-label">' . esc_html( $label ) . '</span>'
        . '<p class="sfp-zt-cta__kop" id="' . esc_attr( $id ) . '">' . esc_html( $g['kop'] ) . '</p>'
        . ( '' !== $g['zin'] ? '<p class="sfp-zt-cta__zin">' . esc_html( $g['zin'] ) . '</p>' : '' )
        . '</div>'
        . '<div class="wp-block-button sfp-zt-cta__knop"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $g['url'] ) . '">' . esc_html( $g['knop'] ) . '</a></div>'
        . '</aside>';
}

/* =========================================================================
 * Blok en code
 * ====================================================================== */

add_filter( 'sfp_page_config_blokken_extra', 'sfp_zelftest_cta_blok' );

/**
 * Het blok Zelftest-oproep, via de bouwstenen (CSS en editor).
 *
 * @param  array $blokken Blokken.
 * @return array
 */
function sfp_zelftest_cta_blok( $blokken ) {
    $blokken['sfp/zelftest-cta'] = array(
        'title'        => 'Zelftest-oproep',
        'description'  => 'Oproep om de zelftest van de site te doen: kop, één zin en knop. De tekst staat in SFP Beheer, Zelftest. Niet voor toolpagina\'s.',
        'icon'         => 'yes-alt',
        'css'          => 'zelftest-cta',
        'zonder_basis' => true,
        'attributes'   => array(
            'test' => array(
                'type'    => 'string',
                'default' => '',
            ),
            'bron' => array(
                'type'    => 'string',
                'default' => '',
            ),
        ),
        'render'       => 'sfp_zelftest_cta_render',
    );
    return $blokken;
}

/**
 * Het bronlabel als het blok er geen heeft: het berichttype en het adres.
 *
 * @return string
 */
function sfp_zelftest_cta_standaard_bron() {
    $post = get_post();
    if ( ! $post ) {
        return 'oproep';
    }
    $soort = 'post' === $post->post_type ? 'artikel' : ( 'page' === $post->post_type ? 'pagina' : $post->post_type );
    if ( 'sfp_vervolg' === $post->post_type ) {
        $soort = 'vervolg';
    }
    return sfp_zelftest_cta_bron( $soort . '-' . $post->post_name );
}

/**
 * sfp/zelftest-cta.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Niet gebruikt.
 * @return string
 */
function sfp_zelftest_cta_render( $attrs, $content = '' ) {
    if ( sfp_zelftest_cta_op_tool() ) {
        return '';
    }
    $bron = sfp_page_config_blok_attr( $attrs, 'bron' );
    $g    = sfp_zelftest_cta_gegevens( sfp_page_config_blok_attr( $attrs, 'test' ), '' !== $bron ? $bron : sfp_zelftest_cta_standaard_bron() );
    if ( ! $g ) {
        return '';
    }
    $extra = function_exists( 'get_block_wrapper_attributes' ) && class_exists( 'WP_Block_Supports' ) && ! empty( WP_Block_Supports::$block_to_render )
        ? sfp_page_config_blok_omhulling( array( 'sfp-zt-cta' ) )
        : '';
    return sfp_page_config_blokken_css_vangnet( 'sfp/zelftest-cta' ) . sfp_zelftest_cta_html( $g, $extra );
}

add_action( 'init', 'sfp_zelftest_cta_code', 20 );

/**
 * De code [sfp_zelftest_cta test="" bron=""].
 */
function sfp_zelftest_cta_code() {
    if ( ! shortcode_exists( 'sfp_zelftest_cta' ) ) {
        add_shortcode(
            'sfp_zelftest_cta',
            function ( $atts ) {
                $atts = shortcode_atts(
                    array(
                        'test' => '',
                        'bron' => '',
                    ),
                    (array) $atts,
                    'sfp_zelftest_cta'
                );
                return sfp_zelftest_cta_render( $atts );
            }
        );
    }
}
