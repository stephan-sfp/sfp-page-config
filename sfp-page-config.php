<?php
/**
 * Plugin Name: SFP Page Config
 * Plugin URI:  https://schoolforprofessionals.com
 * Description: Centrale paginaconfiguratie, cursusdata, sales-page styling, longread-modus en shortcodes voor het School for Professionals netwerk.
 * Version:     2.8.1
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

define( 'SFP_PAGE_CONFIG_VERSION', '2.8.1' );
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
            'lr_brand'          => '#d22d00',
            'lr_bar_bg'         => '#d22d00',
            'lr_bar_text'       => '#ffffff',
            'lr_drawer_bg'      => '#F7FCFE',
            'lr_drawer_text'    => '#d22d00',
            'lr_sidebar_text'   => '#333333',
            'lr_sidebar_muted'  => '#cccccc',
            'lr_sidebar_active' => '#d22d00',
            'lr_sidebar_h3'     => '#575757',
        ),
        'degespreksacademie.nl' => array(
            'cta_bg'    => '#fc5130',
            'cta_hover' => '#fd7257',
            'font'      => "'Rubik', sans-serif",
            'weight'    => '900',
            'lr_brand'          => '#fc5130',
            'lr_bar_bg'         => '#fc5130',
            'lr_bar_text'       => '#ffffff',
            'lr_drawer_bg'      => '#F7FCFE',
            'lr_drawer_text'    => '#fc5130',
            'lr_sidebar_text'   => '#333333',
            'lr_sidebar_muted'  => '#cccccc',
            'lr_sidebar_active' => '#fc5130',
            'lr_sidebar_h3'     => '#575757',
        ),
        'depresenteerschool.nl' => array(
            'cta_bg'    => '#ff5a06',
            'cta_hover' => '#ff7420',
            'font'      => "'Nunito', sans-serif",
            'weight'    => '900',
            'lr_brand'          => '#2E2864',
            'lr_bar_bg'         => '#00B0E3',
            'lr_bar_text'       => '#ffffff',
            'lr_drawer_bg'      => '#F7FCFE',
            'lr_drawer_text'    => '#00B0E3',
            'lr_sidebar_text'   => '#2E2864',
            'lr_sidebar_muted'  => '#A1D9F4',
            'lr_sidebar_active' => '#00B0E3',
            'lr_sidebar_h3'     => '#575757',
        ),
        'centrumvoordidactiek.nl' => array(
            'cta_bg'    => '#ff3c38',
            'cta_hover' => '#ff625f',
            'font'      => "'Nunito', sans-serif",
            'weight'    => '900',
            'lr_brand'          => '#ff3c38',
            'lr_bar_bg'         => '#ff3c38',
            'lr_bar_text'       => '#ffffff',
            'lr_drawer_bg'      => '#F7FCFE',
            'lr_drawer_text'    => '#ff3c38',
            'lr_sidebar_text'   => '#333333',
            'lr_sidebar_muted'  => '#cccccc',
            'lr_sidebar_active' => '#ff3c38',
            'lr_sidebar_h3'     => '#575757',
        ),
        'deschrijftrainers.nl' => array(
            'cta_bg'    => '#ff9f1c',
            'cta_hover' => '#ffb857',
            'font'      => "'Rubik', sans-serif",
            'weight'    => '900',
            'lr_brand'          => '#ff9f1c',
            'lr_bar_bg'         => '#ff9f1c',
            'lr_bar_text'       => '#ffffff',
            'lr_drawer_bg'      => '#F7FCFE',
            'lr_drawer_text'    => '#ff9f1c',
            'lr_sidebar_text'   => '#333333',
            'lr_sidebar_muted'  => '#cccccc',
            'lr_sidebar_active' => '#ff9f1c',
            'lr_sidebar_h3'     => '#575757',
        ),
    );

    $resolved = $configs['schoolforprofessionals.com']; // Safe fallback.
    foreach ( $configs as $host => $cfg ) {
        if ( false !== strpos( $domain, $host ) ) {
            $resolved = $cfg;
            break;
        }
    }

    // Merge stored longread branding overrides from the Instellingen tab.
    // An empty or invalid value falls back to the domain default, so a
    // cleared field never produces a broken CSS variable.
    $settings = get_option( 'sfp_settings', array() );
    $overrides = array(
        'lr_brand'         => 'lr_brand',
        'lr_bar_bg'        => 'lr_bar_bg',
        'lr_bar_text'      => 'lr_bar_text',
        'lr_drawer_bg'     => 'lr_drawer_bg',
        'lr_drawer_text'   => 'lr_drawer_text',
        'lr_sidebar_text'   => 'lr_sidebar_text',
        'lr_sidebar_muted'  => 'lr_sidebar_muted',
        'lr_sidebar_active' => 'lr_sidebar_active',
        'lr_sidebar_h3'     => 'lr_sidebar_h3',
    );
    foreach ( $overrides as $option_key => $brand_key ) {
        if ( ! empty( $settings[ $option_key ] ) ) {
            $hex = sanitize_hex_color( $settings[ $option_key ] );
            if ( $hex ) {
                $resolved[ $brand_key ] = $hex;
            }
        }
    }

    return $resolved;
}

/* =========================================================================
 * Sticky CTA config per page type
 * ====================================================================== */

/**
 * Get the default sticky CTA config per page type.
 *
 * These act as a fallback when the editor has not entered anything in
 * the Sticky CTA section of the Instellingen tab.
 *
 * The 'href' default is intentionally empty. A booking URL differs per
 * site and per page type and therefore belongs in the Instellingen tab,
 * not in this file. When no URL is configured the CTA falls back to the
 * on-page anchor (see sfp_page_config_get_sticky_cta()), which always
 * points at the section holding the real button.
 *
 * Hero detection is handled in sticky-cta.js with three layers:
 *   1. Manual override via the 'hero' field (CSS selector).
 *   2. Auto-detect: first Spectra container in .entry-content.
 *   3. Scroll-threshold fallback (default 400px).
 *
 * The 'hero' default is intentionally empty so that auto-detection
 * kicks in without requiring per-page configuration.
 *
 * @return array<string, array>
 */
function sfp_page_config_get_sticky_cta_defaults() {

    return array(
        'coaching' => array(
            'text'   => 'Boek je gratis proefsessie',
            'href'   => '',
            'target' => '_blank',
            'anchor' => 'aanvragen',
            'hero'   => '',
        ),
        'training' => array(
            'text'   => 'Plan een kennismaking in',
            'href'   => '',
            'target' => '_blank',
            'anchor' => 'inschrijven',
            'hero'   => '',
        ),
        'incompany' => array(
            'text'   => 'Plan een kennismaking in',
            'href'   => '',
            'target' => '_blank',
            'anchor' => 'aanvragen',
            'hero'   => '',
        ),
    );
}

/**
 * Get the sticky CTA configuration for a given page type, merging any
 * per-paginatype overrides from the Instellingen tab on top of the
 * hardcoded defaults.
 *
 * Overrides are stored under the `sticky_cta` key in the `sfp_settings`
 * option as a nested array: `sticky_cta[<type>][text|href|anchor|hero]`.
 * Empty strings mean "fall back to default" so an editor can clear a
 * field without breaking the CTA.
 *
 * @param  string $type  One of 'coaching', 'training', 'incompany'.
 * @return array|null    Merged config array or null when the type is unknown.
 */
function sfp_page_config_get_sticky_cta( $type ) {

    $defaults = sfp_page_config_get_sticky_cta_defaults();
    if ( ! isset( $defaults[ $type ] ) ) {
        return null;
    }

    $default = $defaults[ $type ];

    if ( ! function_exists( 'sfp_page_config_get_setting' ) ) {
        return sfp_page_config_resolve_sticky_cta_href( $default );
    }

    $overrides_all = sfp_page_config_get_setting( 'sticky_cta', array() );
    if ( ! is_array( $overrides_all ) || empty( $overrides_all[ $type ] ) ) {
        return sfp_page_config_resolve_sticky_cta_href( $default );
    }

    $override = $overrides_all[ $type ];
    $merged   = $default;
    foreach ( array( 'text', 'href', 'anchor', 'hero' ) as $key ) {
        if ( isset( $override[ $key ] ) && '' !== trim( (string) $override[ $key ] ) ) {
            $merged[ $key ] = (string) $override[ $key ];
        }
    }

    return sfp_page_config_resolve_sticky_cta_href( $merged );
}

/**
 * Resolve the sticky CTA href and matching target.
 *
 * When no booking URL is configured for this page type, the CTA falls
 * back to the on-page anchor (e.g. "#inschrijven"). That section holds
 * the real button, so the visitor always lands somewhere useful instead
 * of on whatever URL happened to be hardcoded in the plugin.
 *
 * The target follows the resolved href: an on-page anchor stays in the
 * same tab, an external URL opens in a new one.
 *
 * @param  array $config Merged sticky CTA config.
 * @return array         Config with resolved 'href' and 'target'.
 */
function sfp_page_config_resolve_sticky_cta_href( $config ) {

    $href = isset( $config['href'] ) ? trim( (string) $config['href'] ) : '';

    if ( '' === $href ) {
        $anchor = isset( $config['anchor'] ) ? trim( (string) $config['anchor'] ) : '';
        $anchor = ltrim( $anchor, '#' );
        $href   = ( '' !== $anchor ) ? '#' . $anchor : '';
    }

    $config['href']   = $href;
    $config['target'] = ( '' === $href || 0 === strpos( $href, '#' ) ) ? '_self' : '_blank';

    return $config;
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
    return array( 'page', 'post', 'begrippen' );
}

/* =========================================================================
 * Shared utility function for Dutch date formatting
 *
 * Defined BEFORE includes so that modules (shortcodes, date-injector)
 * can call it safely during require_once.
 * ====================================================================== */

/**
 * Format a date string to Dutch.
 *
 * Supports two modes:
 *
 * 1. Keyword format:
 *    - 'short' → "di 15 apr"
 *    - 'long'  → "dinsdag 15 april 2026"
 *
 * 2. PHP date format string (e.g. 'l j F Y', 'j F', 'D j F Y'):
 *    Month names (F, M) and day names (l, D) are replaced with Dutch.
 *    All other PHP date tokens (j, d, n, m, Y, y, etc.) work as usual.
 *
 * Uses the site's timezone via wp_date() so dates rendered near
 * midnight don't drift on servers set to UTC.
 *
 * @param  string $date_string Date string in Y-m-d or other parseable format.
 * @param  string $format      'short', 'long', or a PHP date format string.
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

    // Keyword shortcuts (used internally by date-injector, cron, dashboard).
    if ( 'long' === $format ) {
        $day_en  = wp_date( 'l', $ts );
        $month_n = (int) wp_date( 'n', $ts );
        return sprintf(
            '%s %s %s %s',
            $days_long[ $day_en ] ?? $day_en,
            wp_date( 'j', $ts ),
            $months_long[ $month_n ] ?? $month_n,
            wp_date( 'Y', $ts )
        );
    }
    if ( 'short' === $format ) {
        $day_en  = wp_date( 'l', $ts );
        $month_n = (int) wp_date( 'n', $ts );
        return sprintf(
            '%s %s %s',
            $days_short[ $day_en ] ?? $day_en,
            wp_date( 'j', $ts ),
            $months_short[ $month_n ] ?? $month_n
        );
    }

    // PHP date format string. Render via wp_date() to honour the site
    // timezone, then substitute English day/month names with Dutch.
    $formatted = wp_date( $format, $ts );
    if ( ! $formatted ) {
        return '';
    }

    // Replace full names first (to avoid matching inside them).
    $map = array();
    foreach ( $days_long as $en => $nl ) {
        $map[ $en ] = $nl;
    }
    // Full month names (January → januari, etc.). wp_date outputs English
    // when format uses F/M because the Dutch translation only kicks in if
    // the site locale is nl_NL. We handle both cases safely.
    $months_en_long = array(
        'January'=>'januari','February'=>'februari','March'=>'maart','April'=>'april',
        'May'=>'mei','June'=>'juni','July'=>'juli','August'=>'augustus',
        'September'=>'september','October'=>'oktober','November'=>'november','December'=>'december',
    );
    foreach ( $months_en_long as $en => $nl ) {
        $map[ $en ] = $nl;
    }

    // Short names after (D, M).
    foreach ( $days_short as $en => $nl ) {
        $map[ substr( $en, 0, 3 ) ] = $nl;
    }
    $months_en_short = array(
        'Jan'=>'jan','Feb'=>'feb','Mar'=>'mrt','Apr'=>'apr','May'=>'mei','Jun'=>'jun',
        'Jul'=>'jul','Aug'=>'aug','Sep'=>'sep','Oct'=>'okt','Nov'=>'nov','Dec'=>'dec',
    );
    foreach ( $months_en_short as $en => $nl ) {
        $map[ $en ] = $nl;
    }

    return strtr( $formatted, $map );
}

/* =========================================================================
 * Time helpers for cursus lestijden
 *
 * Defined BEFORE includes so shortcodes.php and dashboard.php can call
 * them during require_once. Times are stored per startmoment in the
 * sfp_cursusdata JSON as 'start' and 'eind' keys (24h "HH:MM").
 * ====================================================================== */

/**
 * Sanitize a time string to a strict 24-hour "HH:MM" value.
 *
 * Accepts "9:00", "09.00", "09:00" and normalises to "09:00". Returns an
 * empty string for anything that is not a valid time, so a bad value never
 * ends up stored or rendered.
 *
 * @param  mixed $value Raw time input.
 * @return string       "HH:MM" or "" when invalid.
 */
function sfp_page_config_sanitize_time( $value ) {

    if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
        return '';
    }

    $value = trim( (string) $value );
    if ( '' === $value ) {
        return '';
    }

    // Allow a dot as separator ("09.00") next to the canonical colon.
    $value = str_replace( '.', ':', $value );

    if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', $value, $m ) ) {
        return '';
    }

    $hours   = (int) $m[1];
    $minutes = (int) $m[2];

    if ( $hours > 23 || $minutes > 59 ) {
        return '';
    }

    return sprintf( '%02d:%02d', $hours, $minutes );
}

/**
 * Format a start/end time pair into a Dutch lestijd string.
 *
 * Examples:
 *   ("09:00", "17:00")               -> "09:00 - 17:00 uur"
 *   ("09:00", "")                    -> "vanaf 09:00 uur"
 *   ("", "17:00")                    -> "tot 17:00 uur"
 *   ("", "")                         -> ""
 *
 * @param  string $start     Sanitised "HH:MM" start time (may be empty).
 * @param  string $eind      Sanitised "HH:MM" end time (may be empty).
 * @param  string $separator Separator between start and end. Default " - ".
 * @param  string $suffix    Trailing text. Default " uur".
 * @return string            Formatted string or "" when both times are empty.
 */
function sfp_page_config_format_cursus_tijd( $start, $eind, $separator = ' - ', $suffix = ' uur' ) {

    $start = sfp_page_config_sanitize_time( $start );
    $eind  = sfp_page_config_sanitize_time( $eind );

    if ( '' !== $start && '' !== $eind ) {
        $body = $start . $separator . $eind;
    } elseif ( '' !== $start ) {
        $body = 'vanaf ' . $start;
    } elseif ( '' !== $eind ) {
        $body = 'tot ' . $eind;
    } else {
        return '';
    }

    return $body . $suffix;
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
    $exclusions[] = 'sfp-page-config/assets/longread-nav';
    // Also exclude the inline config scripts. WP Rocket delays inline
    // <script> tags independently of their associated external scripts.
    // Without these, sfpStickyConfig and sfpCourseData are undefined
    // when the main scripts execute.
    $exclusions[] = 'sfpStickyConfig';
    $exclusions[] = 'sfpCourseData';
    $exclusions[] = 'sfpPromoConfig';
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
    'includes/placeholder-guard.php',
    'includes/body-class.php',
    'includes/longread.php',
    'includes/longread-nav.php',
    'includes/reading-time.php',
    'includes/cron.php',
    'includes/whatsapp.php',
    'includes/affiliate.php',
    'includes/hero-focal.php',
    'includes/date-injector.php',
    'includes/schema-fix.php',
    'includes/autoloaded-options.php',
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

