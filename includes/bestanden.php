<?php
/**
 * SFP Page Config - bestanden van de plugin: URL's (met verkleinde versie
 * als die er is) en de lezer voor inline CSS. Geladen door elk bestand dat
 * ze nodig heeft, zodat ook losse tests ze hebben.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'sfp_page_config_css_lees' ) ) {
    return;
}

/**
 * Een stijlbestand van de plugin, verkleind, voor inline op de pagina. Eén
 * lezer voor alle CSS (sinds 3.22.0; daarvoor drie gelijke kopieën). Staat
 * er een .min.css naast (releaseworkflow), dan die; anders gaan commentaar
 * en witruimte eruit. Eén keer per verzoek gelezen.
 *
 * @param  string $bestand Pad onder assets/, bijvoorbeeld 'blokken/held.css'.
 * @return string
 */
function sfp_page_config_css_lees( $bestand ) {
    static $cache = array();
    $bestand = ltrim( str_replace( '..', '', (string) $bestand ), '/' );
    if ( isset( $cache[ $bestand ] ) ) {
        return $cache[ $bestand ];
    }
    $min = SFP_PAGE_CONFIG_DIR . 'assets/' . preg_replace( '/\.css$/', '.min.css', $bestand );
    if ( '.min.css' !== substr( $bestand, -8 ) && is_readable( $min ) ) {
        $cache[ $bestand ] = trim( (string) file_get_contents( $min ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokaal pluginbestand.
        return $cache[ $bestand ];
    }
    $pad = SFP_PAGE_CONFIG_DIR . 'assets/' . $bestand;
    $css = is_readable( $pad ) ? (string) file_get_contents( $pad ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokaal pluginbestand.
    $css = preg_replace( '#/\*.*?\*/#s', '', $css );
    $css = preg_replace( '/\s+/', ' ', $css );
    $css = trim( str_replace( array( ' {', '{ ', ' }', '; ', ': ', ', ', ';}' ), array( '{', '{', '}', ';', ':', ',', '}' ), $css ) );
    $cache[ $bestand ] = $css;
    return $css;
}

/**
 * De URL van een bestand onder assets/ (zonder ?ver=; dat zet WordPress).
 * Staat er een verkleinde versie naast (.min.js of .min.css, gebouwd door
 * de releaseworkflow, sinds 3.22.0), dan die. In de repo staan alleen de
 * leesbare bronnen; lokaal laden dus de bronnen.
 *
 * @param  string $bestand Pad onder assets/.
 * @return string
 */
function sfp_page_config_asset_url( $bestand ) {
    $bestand = ltrim( (string) $bestand, '/' );
    $min     = preg_replace( '/(?<!\.min)\.(js|css)$/', '.min.$1', $bestand );
    if ( $min !== $bestand && is_readable( SFP_PAGE_CONFIG_DIR . 'assets/' . $min ) ) {
        $bestand = $min;
    }
    return SFP_PAGE_CONFIG_URL . 'assets/' . $bestand;
}

function sfp_page_config_get_setting( $key, $default = '' ) {
    $settings = sfp_page_config_instellingen();
    return isset( $settings[ $key ] ) && '' !== $settings[ $key ] ? $settings[ $key ] : $default;
}
