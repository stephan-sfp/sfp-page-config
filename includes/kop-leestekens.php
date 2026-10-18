<?php
/**
 * SFP Page Config - Leestekens in de H1 (K28)
 *
 * In een displayletter waarin elk teken even breed is, staat een dubbele
 * punt of een koppelteken met een gat ervoor en erna. Met de instelling
 * kop_leestekens trekt de plugin die tekens in de H1 naar het woord ernaast:
 * dubbele punt, puntkomma, uitroepteken, komma en punt links 0,2 em en
 * rechts 0,12 em; het koppelteken aan beide kanten 0,12 em. De apostrof niet:
 * het font zet hem zelf strak tegen de letter (gemeten 17-10-2026).
 *
 * De tekst zelf verandert niet; alleen de tekens krijgen een span. De plugin
 * herkent het font niet zelf (geen fontnaam in de code, en meten in de
 * browser laat de titel verspringen): de instelling staat per site aan op de
 * sites met zo'n letter.
 *
 * Geldt voor de titel in de artikelopmaak, kopblokken van niveau 1 en
 * Spectra-koppen van niveau 1.
 *
 * @package SFP_Page_Config
 * @since   3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Staat de correctie aan op deze site?
 *
 * @return bool
 */
function sfp_page_config_leestekens_aan() {
    return '1' === (string) sfp_page_config_get_setting( 'kop_leestekens', '' );
}

/**
 * Zet de leestekens in een stuk tekst (zonder HTML) in een span.
 *
 * @param  string $tekst Tekst, mag entiteiten bevatten.
 * @return string
 */
function sfp_page_config_leestekens_tekst( $tekst ) {
    $delen = preg_split( '/(&#?[a-zA-Z0-9]+;)/', $tekst, -1, PREG_SPLIT_DELIM_CAPTURE );
    $uit   = '';
    foreach ( $delen as $deel ) {
        if ( '' === $deel ) {
            continue;
        }
        if ( '&' === $deel[0] && ';' === substr( $deel, -1 ) ) {
            $uit .= $deel;
            continue;
        }
        // In één ronde, zodat de klassenaam zelf niet opnieuw wordt bewerkt.
        $uit .= preg_replace_callback(
            '/[:;!,.\-]/',
            function ( $m ) {
                return '<span class="' . ( '-' === $m[0] ? 'sfp-lt-k' : 'sfp-lt' ) . '">' . $m[0] . '</span>';
            },
            $deel
        );
    }
    return $uit;
}

/**
 * Pas de correctie toe op de tekst binnen een H1.
 *
 * @param  string $html Titel of blok-HTML.
 * @param  bool   $kaal True als $html alleen de inhoud van de H1 is.
 * @return string
 */
function sfp_page_config_leestekens( $html, $kaal = false ) {
    if ( ! sfp_page_config_leestekens_aan() || '' === (string) $html ) {
        return $html;
    }
    $binnen = function ( $inhoud ) {
        $stukken = preg_split( '/(<[^>]*>)/', $inhoud, -1, PREG_SPLIT_DELIM_CAPTURE );
        $uit     = '';
        foreach ( $stukken as $stuk ) {
            $uit .= ( '' !== $stuk && '<' === $stuk[0] ) ? $stuk : sfp_page_config_leestekens_tekst( $stuk );
        }
        return $uit;
    };
    if ( $kaal ) {
        return $binnen( $html );
    }
    if ( false === stripos( $html, '<h1' ) || false !== strpos( $html, 'sfp-lt' ) ) {
        return $html;
    }
    return preg_replace_callback(
        '#(<h1\b[^>]*>)(.*?)(</h1>)#is',
        function ( $m ) use ( $binnen ) {
            return $m[1] . $binnen( $m[2] ) . $m[3];
        },
        $html
    );
}

add_filter( 'render_block_core/heading', 'sfp_page_config_leestekens_blok', 10, 2 );
add_filter( 'render_block_uagb/advanced-heading', 'sfp_page_config_leestekens_blok', 10, 2 );

/**
 * Kopblokken van niveau 1.
 *
 * @param  string $html Blok-HTML.
 * @param  array  $blok Blok.
 * @return string
 */
function sfp_page_config_leestekens_blok( $html, $blok ) {
    if ( is_admin() || wp_is_json_request() ) {
        return $html;
    }
    return sfp_page_config_leestekens( $html );
}

add_action( 'wp_head', 'sfp_page_config_leestekens_css', 20 );

/**
 * De opmaak van de spans. Klein en alleen als de correctie aan staat.
 */
function sfp_page_config_leestekens_css() {
    if ( ! sfp_page_config_leestekens_aan() ) {
        return;
    }
    echo '<style id="sfp-kop-leestekens">h1 .sfp-lt{margin-left:-.2em;margin-right:-.12em}h1 .sfp-lt-k{margin-left:-.12em;margin-right:-.12em}</style>' . "\n";
}

add_filter( 'rocket_rucss_inline_atts_exclusions', 'sfp_page_config_leestekens_rucss' );

/**
 * WP Rocket Remove Unused CSS laat deze regels staan.
 *
 * @param  array $uitsluitingen Uitsluitingen op attribuut.
 * @return array
 */
function sfp_page_config_leestekens_rucss( $uitsluitingen ) {
    $uitsluitingen   = (array) $uitsluitingen;
    $uitsluitingen[] = 'sfp-kop-leestekens';
    return $uitsluitingen;
}
