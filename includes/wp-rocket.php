<?php
/**
 * SFP Page Config - Samenwerking met WP Rocket
 *
 * De bestanden van deze plugin (scripts en stijlen) gaan niet door de
 * verkleining van WP Rocket. WP Rocket bewaart een verkleinde kopie onder
 * hetzelfde pad en ververst die niet als de plugin wordt bijgewerkt; daardoor
 * kregen bezoekers na 3.0.0 nog de scripts van een oudere versie (gemeten
 * 12-10-2026 op SFP, DPS, DGA, CVD en DST: sticky-cta.js van 6 oktober).
 * Ongeminificeerd laden ze met ?ver=<versie>, zodat elke cachelaag
 * (WP Rocket, SiteGround, Cloudflare, de browser) een nieuwe versie ziet.
 * De bestanden zijn klein; verkleinen leverde weinig op.
 *
 * De opmaak van de voorkant laadt niet als los bestand maar inline, in een
 * eigen <style id="<handle>-inline-css">, alleen op pagina's waar het
 * onderdeel iets toont, en buiten Remove Unused CSS. Een los bestand hield
 * het tonen van de pagina op (render-blocking), en Remove Unused CSS mist
 * regels voor wat pas na het laden verschijnt. Zie sfp_page_config_css_inline().
 *
 * Regel voor nieuwe opmaak: altijd via sfp_page_config_css_inline(), nooit
 * een losse stylesheet op de voorkant. Boven 10 KB verkleind per onderdeel:
 * eerst voorleggen.
 *
 * Uitgesteld laden van scripts (delay JS) blijft zoals het was.
 *
 * @package SFP_Page_Config
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'rocket_exclude_js', 'sfp_page_config_rocket_niet_verkleinen' );
add_filter( 'rocket_exclude_css', 'sfp_page_config_rocket_niet_verkleinen' );

/**
 * Sluit de bestanden van deze plugin uit van verkleinen.
 *
 * @param  array $uitsluitingen Paden (patronen) die WP Rocket overslaat.
 * @return array
 */
function sfp_page_config_rocket_niet_verkleinen( $uitsluitingen ) {
    $uitsluitingen   = (array) $uitsluitingen;
    $uitsluitingen[] = '/wp-content/plugins/sfp-page-config/(.*)';
    return $uitsluitingen;
}

/**
 * Handles van de voorkant waarvan de opmaak inline staat.
 *
 * @return string[]
 */
function sfp_page_config_css_inline_handles() {
    return array(
        'sfp-reading-time',
        'sfp-whatsapp',
        'sfp-reviews',
        'sfp-page-config-sales',
        'sfp-tool',
        'sfp-longread-nav',
        'sfp-zelftest',
    );
}

/**
 * Een stijlbestand van de plugin, verkleind. Eén keer per verzoek gelezen.
 *
 * @param  string $bestand Pad onder assets/, bijvoorbeeld 'reviews/reviews.css'.
 * @return string
 */
function sfp_page_config_css_bestand( $bestand ) {
    static $cache = array();
    if ( isset( $cache[ $bestand ] ) ) {
        return $cache[ $bestand ];
    }
    $pad = SFP_PAGE_CONFIG_DIR . 'assets/' . ltrim( (string) $bestand, '/' );
    $css = is_readable( $pad ) ? (string) file_get_contents( $pad ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokaal pluginbestand.
    $css = preg_replace( '#/\*.*?\*/#s', '', $css );
    $css = preg_replace( '/\s+/', ' ', $css );
    $css = trim( str_replace( array( ' {', '{ ', ' }', '; ', ': ', ', ', ';}' ), array( '{', '{', '}', ';', ':', ',', '}' ), $css ) );
    $cache[ $bestand ] = $css;
    return $css;
}

/**
 * Zet de opmaak van een onderdeel inline in de pagina, onder de gegeven
 * handle. Extra opmaak (merkvariabelen) kan daarna nog met
 * wp_add_inline_style() op dezelfde handle. Wordt het na de head
 * aangeroepen, dan zet WordPress de opmaak in de footer.
 *
 * @param string $handle  Handle, ook de basis van het id van de <style>.
 * @param string $bestand Pad onder assets/.
 */
function sfp_page_config_css_inline( $handle, $bestand ) {
    if ( wp_style_is( $handle, 'enqueued' ) || wp_style_is( $handle, 'done' ) ) {
        return;
    }
    if ( ! wp_style_is( $handle, 'registered' ) ) {
        wp_register_style( $handle, false, array(), SFP_PAGE_CONFIG_VERSION ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter -- inline opmaak.
    }
    wp_enqueue_style( $handle );
    wp_add_inline_style( $handle, sfp_page_config_css_bestand( $bestand ) );
}

add_filter( 'rocket_rucss_inline_atts_exclusions', 'sfp_page_config_css_inline_rucss' );

/**
 * WP Rocket Remove Unused CSS laat de inline opmaak van de plugin staan:
 * regels voor wat pas na het laden verschijnt (resultaat van een tool,
 * staat van de WhatsApp-knop) zijn bij het meten niet in beeld.
 *
 * @param  array $uitsluitingen Uitsluitingen op attribuut.
 * @return array
 */
function sfp_page_config_css_inline_rucss( $uitsluitingen ) {
    $uitsluitingen = (array) $uitsluitingen;
    foreach ( sfp_page_config_css_inline_handles() as $handle ) {
        $uitsluitingen[] = $handle . '-inline-css';
    }
    return $uitsluitingen;
}
