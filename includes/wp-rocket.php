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
 * Om dezelfde reden blijven de stijlbestanden van de plugin staan naast de
 * gebruikte CSS (Remove Unused CSS): anders blijft een verouderde selectie
 * van de stijlen staan tot iemand de gebruikte CSS met de hand leegt.
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

add_filter( 'rocket_rucss_external_exclusions', 'sfp_page_config_rocket_css_laten_staan' );

/**
 * Laat de stijlbestanden van deze plugin staan bij Remove Unused CSS.
 *
 * @param  array $uitsluitingen Bestanden die WP Rocket laat staan.
 * @return array
 */
function sfp_page_config_rocket_css_laten_staan( $uitsluitingen ) {
    $uitsluitingen   = (array) $uitsluitingen;
    $uitsluitingen[] = '/wp-content/plugins/sfp-page-config/';
    return $uitsluitingen;
}
