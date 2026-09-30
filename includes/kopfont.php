<?php
/**
 * Kopfont als CSS-variabele op elke pagina.
 *
 * Zet op de front-end twee variabelen op :root:
 *   --sfp-kopfont          het font van H2 tot en met H6 (Astra headings-font-family)
 *   --sfp-kopfont-gewicht  het gewicht daarvan (Astra headings-font-weight)
 *
 * Bedoeld voor elementen die in kapitalen staan maar geen kop zijn, zoals de
 * groepslabels in de footer. De merkregel is dat kapitalen en kopfont bij
 * elkaar horen; met deze variabele kan CSS dat font gebruiken zonder dat er
 * een fontnaam in code staat. De waarden komen uit Astra via
 * sfp_page_config_get_brand() en zijn daar al gevalideerd.
 *
 * Gebruik: font-family: var(--sfp-kopfont, inherit);
 *          font-weight: var(--sfp-kopfont-gewicht, inherit);
 *
 * Terugdraaien: dit bestand uit de lijst in sfp-page-config.php halen.
 *
 * @package SFP_Page_Config
 * @since   2.9.7
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_head', 'sfp_page_config_kopfont_vars', 4 );

/**
 * Print de kopfont-variabelen in de head.
 */
function sfp_page_config_kopfont_vars() {
    if ( is_admin() || ! function_exists( 'sfp_page_config_get_brand' ) ) {
        return;
    }

    $brand  = sfp_page_config_get_brand();
    $font   = isset( $brand['font'] ) ? (string) $brand['font'] : 'inherit';
    $weight = isset( $brand['weight'] ) ? (string) $brand['weight'] : 'inherit';

    // Beide waarden zijn gevalideerd in sfp_page_config_get_brand():
    // het font door sfp_page_config_sanitize_font_family(), het gewicht
    // tegen een vaste lijst. esc_attr() zou de aanhalingstekens in een
    // fontstack in entiteiten omzetten, dus die gebruiken we hier niet.
    // Een sluitende style-tag in een font is uitgesloten door de sanitizer;
    // strip_tags() is een tweede slot.
    $font   = wp_strip_all_tags( $font );
    $weight = wp_strip_all_tags( $weight );

    printf(
        "<style id=\"sfp-kopfont\">:root{--sfp-kopfont:%s;--sfp-kopfont-gewicht:%s;}</style>\n",
        $font, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- gevalideerd, zie hierboven.
        $weight // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- gevalideerd, zie hierboven.
    );
}

add_filter( 'rocket_rucss_inline_atts_exclusions', 'sfp_page_config_kopfont_rucss_exclusion' );

/**
 * Laat WP Rocket (Remove Unused CSS) dit inline blok ongemoeid.
 *
 * @param  array $exclusions Uitsluitingen op attribuut.
 * @return array
 */
function sfp_page_config_kopfont_rucss_exclusion( $exclusions ) {
    $exclusions   = (array) $exclusions;
    $exclusions[] = 'sfp-kopfont';
    return $exclusions;
}
