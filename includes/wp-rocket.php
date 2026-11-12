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

require_once __DIR__ . '/bestanden.php';

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
 * De URL van een bestand onder assets/, met ?ver=. Staat er een verkleinde
 * versie naast (.min.js of .min.css, gebouwd door de releaseworkflow), dan
 * die. In de repo staan alleen de leesbare bronnen.
 *
 * @param  string $bestand Pad onder assets/, bijvoorbeeld 'artikel.js'.
 * @return string
 */
function sfp_page_config_asset( $bestand ) {
    return add_query_arg( 'ver', SFP_PAGE_CONFIG_VERSION, sfp_page_config_asset_url( $bestand ) );
}

/**
 * Een script van de plugin als los bestand in de footer: met defer, niet
 * uitgesteld door WP Rocket (nowprocket) en door de browser te cachen tussen
 * pagina's (sinds 3.22.0; daarvoor stond de code inline op elke pagina).
 *
 * @param string $id      Id van de scripttag.
 * @param string $bestand Pad onder assets/.
 */
function sfp_page_config_script_tag( $id, $bestand ) {
    if ( ! is_readable( SFP_PAGE_CONFIG_DIR . 'assets/' . ltrim( $bestand, '/' ) ) ) {
        return;
    }
    echo '<script id="' . esc_attr( $id ) . '" src="' . esc_url( sfp_page_config_asset( $bestand ) ) . '" defer nowprocket></script>' . "\n"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- in de footer na de wachtrij, zie sfp_page_config_artikel_js().
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
        'sfp-tool-generator',
        'sfp-tool-tekstanalyse',
        'sfp-tool-kaart',
        'sfp-longread-nav',
        'sfp-zelftest',
        // Sinds 3.36.0: de incompanycalculator.
        'sfp-calculator',
    );
}

/**
 * Een stijlbestand van de plugin, verkleind. Eén keer per verzoek gelezen.
 *
 * @param  string $bestand Pad onder assets/, bijvoorbeeld 'reviews/reviews.css'.
 * @return string
 */
function sfp_page_config_css_bestand( $bestand ) {
    return sfp_page_config_css_lees( $bestand );
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
add_filter( 'rocket_delay_js_exclusions', 'sfp_page_config_rocket_delay' );

/**
 * Alle <style>-ids van de plugin die WP Rocket Remove Unused CSS moet laten
 * staan (sinds 3.22.0 op één plek). Regels voor wat pas na het laden of na
 * een klik verschijnt (resultaat van een tool, open uitklap, tooltip,
 * panelen) zijn bij het meten niet in beeld en zouden anders wegvallen.
 *
 * @return string[]
 */
function sfp_page_config_rocket_rucss_ids() {
    $ids = array(
        'sfp-artikel',
        'sfp-blokken',
        'sfp-footer-groepen',
        'sfp-kleurrollen',
        'sfp-kopfont-ux',
        'sfp-kop-leestekens',
        'sfp-kopfont',
        'sfp-tooltip-css',
        'sfp-kopieer-inline-css',
        'sfp-bronnen-inline-css',
        'sfp-praktijkblok-inline-css',
        'sfp-auteursbio-inline-css',
    );
    foreach ( sfp_page_config_css_inline_handles() as $handle ) {
        $ids[] = $handle . '-inline-css';
    }
    return $ids;
}

/**
 * Scripts die WP Rocket niet mag uitstellen (sinds 3.22.0 op één plek). Ze
 * zijn klein en moeten er direct zijn: de tool en de zelftest staan anders
 * pas na de eerste aanraking, de knop onderaan mist de held, tabs en de
 * inhoudsopgave reageren niet en de footer verspringt. De inline configuratie
 * (sfp...Config) hoort erbij: WP Rocket stelt inline scripts los uit.
 *
 * @return string[]
 */
function sfp_page_config_rocket_delay_lijst() {
    return array(
        'sfp-page-config/assets/dropdown',
        'sfp-page-config/assets/sticky-cta',
        'sfp-page-config/assets/longread-nav',
        'sfp-page-config/assets/artikel',
        'sfp-page-config/assets/blokken/',
        'sfp-page-config/assets/tool/',
        'sfp-page-config/assets/tooltip/',
        'sfp-page-config/assets/footer-groepen',
        'assets/zelftest/',
        // Sinds 3.36.0: de incompanycalculator en zijn configuratie.
        'assets/calculator/',
        'sfpCalculator',
        'sfpStickyConfig',
        'sfpCourseData',
        // Sinds 3.32.0: anders draait het keuzemenu eerst zonder deze vlag,
        // verbergt het veld en toont het de tekst voor geen startdata.
        'sfpAlleenWachtlijst',
        'sfpDropdownConfig',
        'sfpToolConfig',
        'sfpZelftest',
        'sfpArtikel',
        'sfp-artikel-js',
        'sfp-artikel-viewport',
        'sfp-blokken-js',
        'sfp-inhoud-js',
        'sfp-logostrook-js',
        'sfp-keuzehulp-js',
        'sfp-footer-groepen-js',
        'sfp-fj__groep',
        'sfp-zelftest',
        'sfp-calculator',
    );
}

/**
 * @param  array $uitsluitingen Uitsluitingen op attribuut.
 * @return array
 */
function sfp_page_config_css_inline_rucss( $uitsluitingen ) {
    return array_values( array_unique( array_merge( (array) $uitsluitingen, sfp_page_config_rocket_rucss_ids() ) ) );
}

/**
 * @param  array $uitsluitingen Uitsluitingen.
 * @return array
 */
function sfp_page_config_rocket_delay( $uitsluitingen ) {
    return array_values( array_unique( array_merge( (array) $uitsluitingen, sfp_page_config_rocket_delay_lijst() ) ) );
}

add_filter( 'rocket_lazyload_excluded_attributes', 'sfp_page_config_rocket_lazy_uit' );

/**
 * Logo's in de logostrook (3.37.1) laden niet via de lazyload van WP Rocket:
 * de logo's buiten de vijf plekken staan verborgen en zou WP Rocket dan nooit
 * laden. Ze houden loading="lazy" van de browser; het script laadt het
 * volgende logo kort voor de wissel.
 *
 * @param  array $uit Uitgesloten attributen.
 * @return array
 */
function sfp_page_config_rocket_lazy_uit( $uit ) {
    $uit   = is_array( $uit ) ? $uit : array();
    $uit[] = 'sfp-logostrook__logo';
    return $uit;
}
