<?php
/**
 * Gedeelde hulp voor de REST-routes van SFP Page Config.
 *
 * - SFP_PAGE_CONFIG_REST: de namespace sfp/v1.
 * - Rechten: beheerder, of iemand die dit bericht mag bewerken.
 * - Body als JSON of als formulier.
 * - Het IP van de bezoeker om te tellen (CF-Connecting-IP alleen van
 *   Cloudflare zelf; IPv6 per /64) en één limietteller.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'SFP_PAGE_CONFIG_REST' ) ) {
    define( 'SFP_PAGE_CONFIG_REST', 'sfp/v1' );
}

/**
 * Mag de huidige gebruiker het beheer zien?
 *
 * @return bool
 */
function sfp_page_config_rest_beheerder() {
    return current_user_can( 'manage_options' );
}

/**
 * Mag de huidige gebruiker het bericht uit de route (parameter id) bewerken?
 *
 * @param  WP_REST_Request $verzoek Verzoek.
 * @return bool
 */
function sfp_page_config_rest_mag_bewerken( $verzoek ) {
    return current_user_can( 'edit_post', (int) $verzoek['id'] );
}

/**
 * De parameters uit de body: JSON als die er is, anders het formulier.
 *
 * @param  WP_REST_Request $verzoek Verzoek.
 * @return array
 */
function sfp_page_config_rest_json( $verzoek ) {
    $in = $verzoek->get_json_params();
    if ( ! is_array( $in ) ) {
        $in = $verzoek->get_body_params();
    }
    return is_array( $in ) ? $in : array();
}

/**
 * Het IP van de bezoeker om te tellen (wordt nooit opgeslagen). Achter
 * Cloudflare het adres uit CF-Connecting-IP, maar alleen als het verzoek
 * echt van Cloudflare komt. Een IPv6-adres telt per /64: dat blok krijgt
 * één aansluiting meestal, en binnen dat blok wisselen is gratis.
 *
 * @return string
 */
function sfp_page_config_bezoeker_ip() {
    $direct = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    $cf     = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) : '';
    $ip     = ( filter_var( $cf, FILTER_VALIDATE_IP ) && function_exists( 'sfp_zelftest_is_cloudflare' ) && sfp_zelftest_is_cloudflare( $direct ) ) ? $cf : $direct;
    $bin    = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ? @inet_pton( $ip ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
    if ( false !== $bin && 16 === strlen( $bin ) ) {
        return inet_ntop( substr( $bin, 0, 8 ) . str_repeat( "\0", 8 ) ) . '/64';
    }
    return (string) $ip;
}

/**
 * JSON-LD als scripttag. Eén plek, met de vlaggen die het uitbreken uit de
 * tag onmogelijk maken (< en > als < en >, & als &): Google
 * leest die gewoon als tekst.
 *
 * @param  array  $gegevens Schema.
 * @param  string $id       Optionele id van de tag.
 * @return string           Lege tekst als het coderen mislukt.
 */
function sfp_page_config_json_ld( array $gegevens, $id = '' ) {
    $json = wp_json_encode( $gegevens, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );
    if ( ! $json ) {
        return '';
    }
    return '<script type="application/ld+json"' . ( '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '>' . $json . "</script>\n";
}


/**
 * Een browser-user-agent met de actuele stabiele Chrome-versie, voor
 * verzoeken naar de sites van het netwerk (SiteGround blokkeert een
 * verouderde versie, Cloudflare een verzoek zonder user-agent). De versie
 * komt van Google en staat een week bewaard; er staat nergens een vast
 * versienummer.
 *
 * @return string Leeg als de versie niet op te halen is.
 */
function sfp_page_config_browser_agent() {
    $versie = get_transient( 'sfp_pc_chrome_versie' );
    if ( false === $versie ) {
        $antwoord = wp_remote_get( 'https://versionhistory.googleapis.com/v1/chrome/platforms/mac/channels/stable/versions?pageSize=1', array( 'timeout' => 5 ) );
        $data     = is_wp_error( $antwoord ) ? null : json_decode( (string) wp_remote_retrieve_body( $antwoord ), true );
        $versie   = isset( $data['versions'][0]['version'] ) && preg_match( '/^\d+(\.\d+){1,3}$/', (string) $data['versions'][0]['version'] ) ? (string) $data['versions'][0]['version'] : '';
        set_transient( 'sfp_pc_chrome_versie', $versie, '' !== $versie ? WEEK_IN_SECONDS : HOUR_IN_SECONDS );
    }
    if ( '' === $versie ) {
        return '';
    }
    return 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/' . $versie . ' Safari/537.36';
}
