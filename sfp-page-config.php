<?php
/**
 * Plugin Name: SFP Page Config
 * Plugin URI:  https://schoolforprofessionals.com
 * Description: Centrale paginaconfiguratie, cursusdata, sales-page styling, longread-modus en shortcodes voor het School for Professionals netwerk.
 * Version:     1.9.6
 * Author:      School for Professionals
 * Author URI:  https://schoolforprofessionals.com
 * License:     GPL-2.0-or-later
 * Text Domain: sfp-page-config
 * Requires at least: 6.4
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Constants
 * ====================================================================== */

define( 'SFP_PAGE_CONFIG_VERSION', '1.9.6' );
define( 'SFP_PAGE_CONFIG_FILE',    __FILE__ );
define( 'SFP_PAGE_CONFIG_DIR',     plugin_dir_path( __FILE__ ) );
define( 'SFP_PAGE_CONFIG_URL',     plugin_dir_url( __FILE__ ) );

/* =========================================================================
 * Brand configuration per site
 *
 * Returns an array with CTA colours, heading font and weight for the
 * current site, detected via the domain in home_url().
 * ====================================================================== */

/**
 * Get the brand configuration for the current site.
 *
 * @return array{cta_bg: string, cta_hover: string, font: string, weight: string}
 */
function sfp_page_config_get_brand() {

    $domain = parse_url( home_url(), PHP_URL_HOST );

    $configs = array(
        'schoolforprofessionals.com' => array(
            'cta_bg'    => '#d22d00',
            'cta_hover' => '#f89b80',
            'font'      => "'Archivo Black', sans-serif",
            'weight'    => '400',
            // Longread navigation colours.
            'lr_brand'        => '#d22d00',
            'lr_bar_bg'       => '#d22d00',
            'lr_bar_text'     => '#ffffff',
            'lr_sidebar_text' => '#333333',
            'lr_sidebar_muted'=> '#cccccc',
        ),
        'degespreksacademie.nl' => array(
            'cta_bg'    => '#fc5130',
            'cta_hover' => '#fd7257',
            'font'      => "'Rubik', sans-serif",
            'weight'    => '900',
            'lr_brand'        => '#1a1a2e',
            'lr_bar_bg'       => '#fc5130',
            'lr_bar_text'     => '#ffffff',
            'lr_sidebar_text' => '#1a1a2e',
            'lr_sidebar_muted'=> '#cccccc',
        ),
        'depresenteerschool.nl' => array(
            'cta_bg'    => '#ff5a06',
            'cta_hover' => '#ff7420',
            'font'      => "'Nunito', sans-serif",
            'weight'    => '900',
            'lr_brand'        => '#2E2864',
            'lr_bar_bg'       => '#00B0E3',
            'lr_bar_text'     => '#ffffff',
            'lr_sidebar_text' => '#2E2864',
            'lr_sidebar_muted'=> '#A1D9F4',
        ),
        'centrumvoordidactiek.nl' => array(
            'cta_bg'    => '#ff3c38',
            'cta_hover' => '#ff625f',
            'font'      => "'Nunito', sans-serif",
            'weight'    => '900',
            'lr_brand'        => '#1a1a2e',
            'lr_bar_bg'       => '#ff3c38',
            'lr_bar_text'     => '#ffffff',
            'lr_sidebar_text' => '#1a1a2e',
            'lr_sidebar_muted'=> '#cccccc',
        ),
        'deschrijftrainers.nl' => array(
            'cta_bg'    => '#ff9f1c',
            'cta_hover' => '#ffb857',
            'font'      => "'Rubik', sans-serif",
            'weight'    => '900',
            'lr_brand'        => '#1a1a2e',
            'lr_bar_bg'       => '#ff9f1c',
            'lr_bar_text'     => '#ffffff',
            'lr_sidebar_text' => '#1a1a2e',
            'lr_sidebar_muted'=> '#cccccc',
        ),
    );

    foreach ( $configs as $host => $cfg ) {
        if ( false !== strpos( $domain, $host ) ) {
            return $cfg;
        }
    }

    // Fallback: SFP branding.
    return $configs['schoolforprofessionals.com'];
}

/* =========================================================================
 * Sticky CTA config per page type
 * ====================================================================== */

/**
 * Get the sticky CTA configuration for a given page type.
 *
 * @param  string $type  One of 'coaching', 'training', 'incompany'.
 * @return array|null    Config array or null when the type is unknown.
 */
function sfp_page_config_get_sticky_cta( $type ) {

    $calendar_url = 'https://calendar.app.google/eqRPknhnTDV3FjjX7';

    $configs = array(
        'coaching' => array(
            'text'   => 'Boek je gratis proefsessie',
            'href'   => $calendar_url,
            'target' => '_blank',
            'anchor' => 'aanvragen',
            'hero'   => '.uagb-block-0b4df88b',
        ),
        'training' => array(
            'text'   => 'Plan een kennismaking in',
            'href'   => $calendar_url,
            'target' => '_blank',
            'anchor' => 'inschrijven',
            'hero'   => '.uagb-block-0b4df88b',
        ),
        'incompany' => array(
            'text'   => 'Plan een kennismaking in',
            'href'   => $calendar_url,
            'target' => '_blank',
            'anchor' => 'aanvragen',
            'hero'   => '.uagb-block-0b4df88b',
        ),
    );

    return isset( $configs[ $type ] ) ? $configs[ $type ] : null;
}

/* =========================================================================
 * Supported post types
 * ====================================================================== */

/**
 * The post types that support page-config fields.
 *
 * @return string[]
 */
function sfp_page_config_post_types() {
    return array( 'page', 'post' );
}

/* =========================================================================
 * Shared utility function for Dutch date formatting
 *
 * Defined BEFORE includes so that modules (shortcodes, date-injector)
 * can call it safely during require_once.
 * ====================================================================== */

/**
 * Format a date string to Dutch short format (e.g., "di 15 apr").
 *
 * @param  string $date_string Date string in Y-m-d or other parseable format.
 * @param  string $format      'short' for "di 15 apr" or 'long' for "dinsdag 15 april 2026".
 * @return string              Formatted date or empty on failure.
 */
function sfp_page_config_format_date_nl( $date_string, $format = 'short' ) {
    $months_short = array(1=>'jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec');
    $months_long  = array(1=>'januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december');
    $days_short   = array('Monday'=>'ma','Tuesday'=>'di','Wednesday'=>'wo','Thursday'=>'do','Friday'=>'vr','Saturday'=>'za','Sunday'=>'zo');
    $days_long    = array('Monday'=>'maandag','Tuesday'=>'dinsdag','Wednesday'=>'woensdag','Thursday'=>'donderdag','Friday'=>'vrijdag','Saturday'=>'zaterdag','Sunday'=>'zondag');

    $ts = strtotime( $date_string );
    if ( ! $ts ) {
        return '';
    }

    $day_en  = date( 'l', $ts );
    $month_n = (int) date( 'n', $ts );
    $day_n   = date( 'j', $ts );
    $year    = date( 'Y', $ts );

    if ( 'long' === $format ) {
        return sprintf( '%s %s %s %s', $days_long[ $day_en ] ?? $day_en, $day_n, $months_long[ $month_n ] ?? $month_n, $year );
    }
    return sprintf( '%s %s %s', $days_short[ $day_en ] ?? $day_en, $day_n, $months_short[ $month_n ] ?? $month_n );
}

/* =========================================================================
 * WP Rocket delay-JS exclusions
 *
 * dropdown.js must load before SureForms/Tom Select to populate options.
 * sticky-cta.js must load immediately to observe the hero section.
 * These scripts are small and timing-critical, so excluding them from
 * delay-JS has no measurable impact on page load performance.
 * ====================================================================== */

add_filter( 'rocket_delay_js_exclusions', 'sfp_page_config_delay_js_exclusions' );

function sfp_page_config_delay_js_exclusions( $exclusions ) {
    $exclusions[] = 'sfp-page-config/assets/dropdown';
    $exclusions[] = 'sfp-page-config/assets/sticky-cta';
    // Also exclude the inline config scripts. WP Rocket delays inline
    // <script> tags independently of their associated external scripts.
    // Without these, sfpStickyConfig and sfpCourseData are undefined
    // when the main scripts execute.
    $exclusions[] = 'sfpStickyConfig';
    $exclusions[] = 'sfpCourseData';
    return $exclusions;
}

/* =========================================================================
 * Include modules
 *
 * Each file is self-contained: it registers its own hooks on inclusion.
 * ====================================================================== */

$sfp_includes = array(
    'includes/metabox.php',
    'includes/dashboard.php',
    'includes/shortcodes.php',
    'includes/body-class.php',
    'includes/longread.php',
    'includes/longread-nav.php',
    'includes/reading-time.php',
    'includes/cron.php',
    'includes/whatsapp.php',
    'includes/affiliate.php',
    'includes/hero-focal.php',
    'includes/date-injector.php',
    'includes/updater.php',
);

foreach ( $sfp_includes as $sfp_file ) {
    $sfp_path = SFP_PAGE_CONFIG_DIR . $sfp_file;
    if ( file_exists( $sfp_path ) ) {
        require_once $sfp_path;
    }
}

/* =========================================================================
 * GitHub auto-updater
 * ====================================================================== */

if ( class_exists( 'SFP_Page_Config_Updater' ) ) {
    new SFP_Page_Config_Updater( SFP_PAGE_CONFIG_FILE, SFP_PAGE_CONFIG_VERSION );
}

/* =========================================================================
 * Settings link in plugin overview
 * ====================================================================== */

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sfp_page_config_action_links' );

/**
 * Add a "Instellingen" link to the plugin row on the Plugins page.
 *
 * @param  string[] $links Existing action links.
 * @return string[]
 */
function sfp_page_config_action_links( $links ) {
    $settings_link = sprintf(
        '<a href="%s">Instellingen</a>',
        esc_url( admin_url( 'admin.php?page=sfp-page-config' ) )
    );
    array_unshift( $links, $settings_link );
    return $links;
}

/* =========================================================================
 * Activation / Deactivation
 * ====================================================================== */

/**
 * Runs on plugin activation.
 */
function sfp_page_config_activate() {
    if ( function_exists( 'sfp_page_config_schedule_cron' ) ) {
        sfp_page_config_schedule_cron();
    }
}
register_activation_hook( __FILE__, 'sfp_page_config_activate' );

/**
 * Runs on plugin deactivation.
 */
function sfp_page_config_deactivate() {
    $timestamp = wp_next_scheduled( 'sfp_page_config_daily_check' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'sfp_page_config_daily_check' );
    }
}
register_deactivation_hook( __FILE__, 'sfp_page_config_deactivate' );

