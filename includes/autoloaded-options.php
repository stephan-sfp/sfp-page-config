<?php
/**
 * SFP Page Config - Autoloaded Options Analyzer
 *
 * Adds a submenu page under "SFP Page Config" that visualises the
 * wp_options rows WordPress loads on every request (the autoloaded
 * options) and lets the admin:
 *
 *   - See total autoload size with a colour-coded status bar
 *     (green < 600 KB, orange 600-800 KB, red > 800 KB, which is
 *     the threshold Site Health warns about since WP 6.6).
 *   - Sort options by size and filter by "large" (>1 KB), "orphaned"
 *     (no matching active plugin), or "transients" only.
 *   - Recognise the source of each option via a prefix map that
 *     covers the SFP technical stack (Astra, ASE Pro, SureRank, etc.).
 *   - Turn autoload off for individual options or in bulk.
 *   - Delete orphaned options that are not on the protected list.
 *
 * All mutations go through admin-post.php with nonce + capability +
 * protected-list checks, and show a cache-purge reminder after each
 * action.
 *
 * WordPress 6.6+ uses autoload values 'on', 'auto' and 'yes' for
 * options that load automatically. 'off' and 'no' are non-autoloaded.
 * This file treats all three autoloaded values as "autoloaded".
 *
 * @package SFP_Page_Config
 * @since   2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Autoload values we consider "on"
 * ====================================================================== */

/**
 * Return the autoload values WordPress treats as autoloaded.
 *
 * @return string[]
 */
function sfp_ao_autoload_on_values() {
    return array( 'on', 'auto', 'yes' );
}

/* =========================================================================
 * Menu registration
 * ====================================================================== */

add_action( 'admin_menu', 'sfp_ao_register_menu', 20 );

/**
 * Register the submenu page under the SFP Page Config parent.
 */
function sfp_ao_register_menu() {
    add_submenu_page(
        'sfp-page-config',
        'Autoloaded Options',
        'Autoloaded Options',
        'manage_options',
        'sfp-autoloaded-options',
        'sfp_ao_render_page'
    );
}

/* =========================================================================
 * Enqueue minimal inline styling on our page only
 * ====================================================================== */

add_action( 'admin_enqueue_scripts', 'sfp_ao_enqueue' );

/**
 * Add tiny inline CSS for the status bar and cache reminder, only on
 * our own admin page so the rest of the dashboard is not affected.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function sfp_ao_enqueue( $hook_suffix ) {
    if ( false === strpos( (string) $hook_suffix, 'sfp-autoloaded-options' ) ) {
        return;
    }

    $css = '
        .sfp-ao-stats { display: flex; gap: 12px; flex-wrap: wrap; margin: 16px 0 24px; }
        .sfp-ao-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 12px 16px; min-width: 160px; }
        .sfp-ao-card strong { display: block; font-size: 22px; line-height: 1.2; }
        .sfp-ao-card span { color: #646970; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; }
        .sfp-ao-bar { height: 22px; background: #f0f0f1; border-radius: 11px; overflow: hidden; position: relative; margin: 8px 0 4px; border: 1px solid #dcdcde; }
        .sfp-ao-bar .fill { height: 100%; transition: width 0.2s ease; }
        .sfp-ao-bar.green .fill { background: #46b450; }
        .sfp-ao-bar.orange .fill { background: #dba617; }
        .sfp-ao-bar.red .fill { background: #d63638; }
        .sfp-ao-bar-threshold { position: absolute; top: -2px; bottom: -2px; width: 2px; background: #1d2327; left: 0; }
        .sfp-ao-bar-label { font-size: 12px; color: #50575e; margin-bottom: 12px; }
        .sfp-ao-filters { margin: 12px 0; }
        .sfp-ao-filters a { text-decoration: none; margin-right: 12px; }
        .sfp-ao-filters a.current { font-weight: 600; color: #1d2327; }
        .sfp-ao-filters a .count { color: #646970; font-weight: 400; }
        .sfp-ao-status-badge { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
        .sfp-ao-status-active { background: #e7f5ea; color: #1a7f37; }
        .sfp-ao-status-orphan { background: #fde8e9; color: #8a1f24; }
        .sfp-ao-status-transient { background: #fff4e5; color: #8a5a00; }
        .sfp-ao-status-core { background: #e5f0fb; color: #0b4f8a; }
        .sfp-ao-protected { color: #646970; font-style: italic; }
        .sfp-ao-reminder { background: #f0f6fc; border-left: 4px solid #2271b1; padding: 10px 14px; margin: 12px 0 20px; }
        .sfp-ao-option-name { font-family: Consolas, Monaco, monospace; word-break: break-all; }
        .sfp-ao-size { font-variant-numeric: tabular-nums; white-space: nowrap; }
    ';

    wp_register_style( 'sfp-ao-inline', false, array(), SFP_PAGE_CONFIG_VERSION );
    wp_enqueue_style( 'sfp-ao-inline' );
    wp_add_inline_style( 'sfp-ao-inline', $css );
}

/* =========================================================================
 * Known source prefixes (option_name → plugin/theme)
 * ====================================================================== */

/**
 * Map option_name prefixes to human-readable source labels.
 *
 * Longer, more specific prefixes are listed first so they match
 * before shorter prefixes (e.g. 'admin_site_enhancements_extra' is
 * detected as ASE Pro before 'admin_site_enhancements' could trigger).
 *
 * @return array<string, string>
 */
function sfp_ao_known_prefixes() {
    return array(
        'admin_site_enhancements'    => 'ASE Pro',
        'astra-'                     => 'Astra Theme',
        'astra_'                     => 'Astra Theme',
        '_transient_'                => 'WordPress Transient',
        '_site_transient_'           => 'WordPress Transient',
        'theme_mods_'                => 'WordPress Core (Theme)',
        'sidebars_widgets'           => 'WordPress Core (Widgets)',
        'widget_'                    => 'WordPress Core (Widgets)',
        'active_plugins'             => 'WordPress Core',
        'cron'                       => 'WordPress Core',
        'rewrite_rules'              => 'WordPress Core',
        'surerank'                   => 'SureRank SEO',
        'surecart'                   => 'SureCart',
        'sureforms'                  => 'SureForms',
        'srfm_'                      => 'SureForms',
        'surecontact'                => 'SureContact',
        'suretriggers'               => 'Ottokit (SureTriggers)',
        'ottokit'                    => 'Ottokit',
        'sfp_'                       => 'SFP Page Config',
        'sfp-'                       => 'SFP Page Config',
        'imagify'                    => 'Imagify',
        'wp_rocket'                  => 'WP Rocket',
        'wprocket'                   => 'WP Rocket',
        'rocket_'                    => 'WP Rocket',
        'complianz'                  => 'Complianz',
        'cmplz'                      => 'Complianz',
        'convert_pro'                => 'Convert Pro',
        'cp_'                        => 'Convert Pro',
        'presto_player'              => 'Presto Player',
        'presto-player'              => 'Presto Player',
        'updraftplus'                => 'UpdraftPlus',
        'updraft_'                   => 'UpdraftPlus',
        'mainwp'                     => 'MainWP Child',
        'mwp_'                       => 'MainWP Child',
        'progress_planner'           => 'Progress Planner',
        'html-regression'            => 'Progress Planner',
        'fluentsmtp'                 => 'FluentSMTP',
        'fluent_smtp'                => 'FluentSMTP',
        'sigmize'                    => 'Sigmize',
        'sg_'                        => 'SiteGround Optimizer',
        'siteground_'                => 'SiteGround Optimizer',
        'spectra_'                   => 'Spectra',
        'uag_'                       => 'Spectra',
        'uagb_'                      => 'Spectra',
        // Ex-plugins and shared frameworks (added in v2.6.3).
        'wpseo'                      => 'Yoast SEO',
        'yoast'                      => 'Yoast SEO',
        'rank_math'                  => 'Rank Math SEO',
        'rank-math'                  => 'Rank Math SEO',
        'rankmath'                   => 'Rank Math SEO',
        'surfer_'                    => 'Surfer SEO',
        'elementor_'                 => 'Elementor',
        'elementor-'                 => 'Elementor',
        'fluentmail'                 => 'FluentMail',
        'bsr_'                       => 'Better Search Replace',
        'cptui_'                     => 'Custom Post Type UI',
        'googlesitekit'              => 'Google Site Kit',
        'aioseo'                     => 'All in One SEO',
        'aioseop'                    => 'All in One SEO',
        'fs_'                        => 'Freemius',
        'bsf_'                       => 'Brainstorm Force',
        'brainstrom'                 => 'Brainstorm Force',
        'xrk_'                       => 'Onbekend (xrk)',
    );
}

/**
 * Map known source labels to the plugin slug(s) that would appear in
 * WordPress' active_plugins list when that source is installed and
 * active. Also used for active-theme recognition (Astra).
 *
 * This lets us mark options as "Actief" when their option_name prefix
 * does not match the plugin's folder slug. Example: Complianz lives in
 * the folder "complianz-gdpr/" but its options start with "cmplz_";
 * without this map they would be flagged Verweesd by mistake.
 *
 * If a source is not listed here, the status falls back to the
 * folder-slug match in sfp_ao_active_prefixes().
 *
 * @return array<string, string[]>
 */
function sfp_ao_source_to_active_slugs() {
    return array(
        'ASE Pro'                => array( 'admin-site-enhancements', 'admin-site-enhancements-pro' ),
        'Astra Theme'            => array( 'astra' ),
        'Spectra'                => array( 'spectra-pro', 'ultimate-addons-for-gutenberg' ),
        'SureRank SEO'           => array( 'surerank', 'surerank-pro' ),
        'SureCart'               => array( 'surecart' ),
        'SureForms'              => array( 'sureforms', 'sureforms-pro' ),
        'SureContact'            => array( 'surecontact' ),
        'Ottokit (SureTriggers)' => array( 'suretriggers', 'ottokit' ),
        'Ottokit'                => array( 'suretriggers', 'ottokit' ),
        'SFP Page Config'        => array( 'sfp-page-config' ),
        'Imagify'                => array( 'imagify' ),
        'WP Rocket'              => array( 'wp-rocket' ),
        'Complianz'              => array( 'complianz-gdpr', 'complianz-gdpr-premium' ),
        'Convert Pro'            => array( 'convert-pro', 'convert-pro-addon' ),
        'Presto Player'          => array( 'presto-player', 'presto-player-pro' ),
        'UpdraftPlus'            => array( 'updraftplus' ),
        'MainWP Child'           => array( 'mainwp-child' ),
        'Progress Planner'       => array( 'progress-planner' ),
        'FluentSMTP'             => array( 'fluent-smtp' ),
        'Sigmize'                => array( 'sigmize' ),
        'SiteGround Optimizer'   => array( 'sg-cachepress' ),
        'Yoast SEO'              => array( 'wordpress-seo', 'wordpress-seo-premium' ),
        'Rank Math SEO'          => array( 'seo-by-rank-math', 'seo-by-rank-math-pro' ),
        'Elementor'              => array( 'elementor', 'elementor-pro' ),
        'FluentMail'             => array( 'fluent-mail' ),
        'Better Search Replace'  => array( 'better-search-replace' ),
        'Custom Post Type UI'    => array( 'custom-post-type-ui' ),
        'Google Site Kit'        => array( 'google-site-kit' ),
        'All in One SEO'         => array( 'all-in-one-seo-pack', 'all-in-one-seo-pack-pro' ),
        'Surfer SEO'             => array( 'surferseo' ),
        'Freemius'               => array( 'imagify', 'presto-player', 'presto-player-pro', 'sureforms', 'sureforms-pro', 'convert-pro', 'convert-pro-addon' ),
        'Brainstorm Force'       => array( 'astra', 'spectra-pro', 'ultimate-addons-for-gutenberg', 'surerank', 'surerank-pro', 'surecart', 'sureforms', 'sureforms-pro', 'surecontact', 'convert-pro', 'convert-pro-addon', 'presto-player-pro', 'sfp-page-config' ),
    );
}

/**
 * Known deactivated / abandoned plugin prefixes. An option matching
 * one of these is flagged as orphaned when its owning plugin is not
 * also present in the active-plugins list.
 *
 * Since v2.6.3 this check runs AFTER the active-plugin match, so the
 * prefix only marks an option orphan when the plugin is truly gone.
 * That keeps the list safe to extend with plugins that might be
 * reinstalled later: they will automatically flip back to "Actief".
 *
 * @return string[]
 */
function sfp_ao_known_orphan_prefixes() {
    return array(
        'aioseo',
        'aioseop',
        'mailerlite',
        'mlwp',
        'yoast',
        'wpseo',
        'rank_math',
        'rank-math',
        'rankmath',
        'jetpack',
        'elementor',
        'wordfence',
        'litespeed',
        'w3tc',
        'wpsupercache',
        // Added in v2.6.3 after audit of CVD and the other SFP sites.
        'surfer_',
        'mainwp',
        'mwp_',
        'fluentmail',
        'bsr_',
        'cptui_',
        'googlesitekit',
        'xrk_',
    );
}

/**
 * Protected option names that must never be deleted from the UI.
 * Autoload may still be toggled off (with a warning) for some of
 * these but most are essentials WordPress or its core plugins need.
 *
 * @return string[]
 */
function sfp_ao_protected_options() {
    return array(
        'siteurl',
        'home',
        'blogname',
        'blogdescription',
        'admin_email',
        'active_plugins',
        'current_theme',
        'template',
        'stylesheet',
        'cron',
        'rewrite_rules',
        'sidebars_widgets',
        'db_version',
        'wp_user_roles',
        'permalink_structure',
        'users_can_register',
        'default_role',
        'WPLANG',
        'timezone_string',
        'date_format',
        'time_format',
        'start_of_week',
        'gmt_offset',
    );
}

/**
 * Does the option name look like a block-based widget container
 * that we also protect from deletion (deleting it blows away all
 * Gutenberg widgets)?
 *
 * @param string $name Option name.
 * @return bool
 */
function sfp_ao_is_structural_widget_option( $name ) {
    return 'widget_block' === $name || 'wp_user_roles' === $name
        || 0 === strpos( $name, 'theme_mods_' );
}

/* =========================================================================
 * Source / status detection
 * ====================================================================== */

/**
 * Determine the human-readable source for an option name.
 *
 * @param string $name Option name.
 * @return string      Source label, or 'Onbekend' if nothing matches.
 */
function sfp_ao_detect_source( $name ) {
    static $prefixes = null;
    if ( null === $prefixes ) {
        $prefixes = sfp_ao_known_prefixes();
    }
    // Sort by prefix length DESC so longer, more specific prefixes win.
    static $sorted_keys = null;
    if ( null === $sorted_keys ) {
        $sorted_keys = array_keys( $prefixes );
        usort( $sorted_keys, function ( $a, $b ) {
            return strlen( $b ) - strlen( $a );
        } );
    }

    foreach ( $sorted_keys as $prefix ) {
        if ( 0 === strpos( $name, $prefix ) ) {
            return $prefixes[ $prefix ];
        }
    }

    return 'Onbekend';
}

/**
 * Determine the status classification of an option.
 *
 * Returns one of: 'transient', 'orphan', 'core', 'active'.
 *
 * @param string $name           Option name.
 * @param array  $active_prefixes Set of prefixes belonging to active plugins.
 * @return string
 */
function sfp_ao_detect_status( $name, array $active_prefixes ) {

    if ( 0 === strpos( $name, '_transient_' ) || 0 === strpos( $name, '_site_transient_' ) ) {
        return 'transient';
    }

    // Core options and recognised structural WP options. Core always
    // wins over any later prefix check.
    $core_exact = array(
        'siteurl', 'home', 'blogname', 'blogdescription', 'admin_email',
        'active_plugins', 'current_theme', 'template', 'stylesheet',
        'cron', 'rewrite_rules', 'sidebars_widgets', 'db_version',
        'wp_user_roles', 'permalink_structure', 'WPLANG',
    );
    if ( in_array( $name, $core_exact, true )
        || 0 === strpos( $name, 'theme_mods_' )
        || 0 === strpos( $name, 'widget_' )
    ) {
        return 'core';
    }

    // v2.6.3: check the active-plugin set BEFORE the orphan-prefix
    // list so that reinstalled plugins (e.g. a returning Yoast or
    // MainWP) immediately flip back to "Actief" without needing code
    // changes in the orphan list.
    if ( sfp_ao_matches_active_plugin( $name, $active_prefixes ) ) {
        return 'active';
    }

    // Known-deactivated plugin prefixes.
    foreach ( sfp_ao_known_orphan_prefixes() as $orphan ) {
        if ( 0 === strpos( $name, $orphan ) ) {
            return 'orphan';
        }
    }

    // Default fallback: no prefix matches, treat as orphan.
    return 'orphan';
}

/**
 * Derive a set of prefixes from the active plugin slugs plus the
 * currently active theme slug. "Active" prefixes here just mean
 * "something is running that plausibly owns this option".
 *
 * Example: plugin slug 'surerank/surerank.php' becomes prefix
 * 'surerank'; theme 'astra' becomes prefix 'astra'.
 *
 * @return array<string, true>
 */
function sfp_ao_active_prefixes() {
    static $cache = null;
    if ( null !== $cache ) {
        return $cache;
    }

    $prefixes     = array();
    $active_slugs = array();

    $active = (array) get_option( 'active_plugins', array() );
    // Network-active plugins (multisite) would normally be merged in
    // here as well; SFP is all single-site so we skip that.
    foreach ( $active as $plugin_path ) {
        $slug = explode( '/', (string) $plugin_path, 2 )[0];
        if ( '' === $slug ) {
            continue;
        }
        $active_slugs[ $slug ] = true;
        $prefixes[ $slug ]     = true;
        // Common variations: replace dashes with underscores and strip
        // 'wp-' / 'wp_' prefixes so 'wp-rocket' also matches 'wp_rocket'
        // and 'rocket_'.
        $prefixes[ str_replace( '-', '_', $slug ) ] = true;
        $prefixes[ preg_replace( '/^wp[-_]/', '', $slug ) ] = true;
    }

    // Active theme (and parent, if it's a child theme).
    $theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
    if ( $theme ) {
        $stylesheet = $theme->get_stylesheet();
        $template   = $theme->get_template();
        $prefixes[ $stylesheet ]     = true;
        $prefixes[ $template ]       = true;
        $active_slugs[ $stylesheet ] = true;
        $active_slugs[ $template ]   = true;
    }

    // WordPress core prefixes always count as "active".
    $core = array( 'cron', 'widget_', 'theme_mods_', 'sidebars_widgets', 'active_', 'siteurl', 'home', 'blogname', 'blogdescription', '_transient_', '_site_transient_', 'wp_', 'db_', 'rewrite_' );
    foreach ( $core as $p ) {
        $prefixes[ $p ] = true;
    }

    // Recognise SFP plugins even if their slugs don't perfectly match option prefixes.
    $sfp_known = array( 'sfp', 'sfp_', 'sfp-' );
    foreach ( $sfp_known as $p ) {
        $prefixes[ $p ] = true;
    }

    // v2.6.3: cross-reference the known source-label → plugin-slug map.
    // For each option-prefix we recognise, check whether any of the
    // plugin slugs that would own that source is actually active (or
    // the active theme). If yes, add the option-prefix itself to the
    // active set. This fixes false "Verweesd" flags for plugins whose
    // option_name prefix differs from their folder slug, e.g.:
    //   - Complianz:    folder 'complianz-gdpr'  vs options 'cmplz_*'
    //   - SureForms:    folder 'sureforms'       vs options 'srfm_*'
    //   - ASE Pro:      folder 'admin-site-enhancements-pro' vs
    //                   options 'admin_site_enhancements*'
    //   - Progress Pl.: folder 'progress-planner' vs options
    //                   'html-regression-*'
    //   - Brainstorm Force: 'bsf_*' and 'brainstrom_*' options are
    //                   shared by the whole BSF plugin family.
    $source_slugs = sfp_ao_source_to_active_slugs();
    foreach ( sfp_ao_known_prefixes() as $option_prefix => $source_label ) {
        if ( ! isset( $source_slugs[ $source_label ] ) ) {
            continue;
        }
        foreach ( $source_slugs[ $source_label ] as $candidate_slug ) {
            if ( isset( $active_slugs[ $candidate_slug ] ) ) {
                $prefixes[ $option_prefix ] = true;
                break;
            }
        }
    }

    $cache = $prefixes;
    return $cache;
}

/**
 * Check whether an option name has a prefix that overlaps with any
 * of the recognised active-plugin / active-theme prefixes.
 *
 * @param string $name   Option name.
 * @param array  $active Prefix set from sfp_ao_active_prefixes().
 * @return bool
 */
function sfp_ao_matches_active_plugin( $name, array $active ) {
    foreach ( $active as $prefix => $_ ) {
        if ( '' === $prefix ) {
            continue;
        }
        if ( 0 === strpos( $name, $prefix ) ) {
            return true;
        }
    }
    return false;
}

/* =========================================================================
 * Data access
 * ====================================================================== */

/**
 * Fetch all autoloaded option rows with size, ordered by size DESC.
 *
 * Uses wpdb->prepare() with dynamically-generated IN placeholders so
 * WP versions using 'on' (6.6+) as well as older 'yes' installs are
 * both covered, without concatenating values into the query.
 *
 * @return array<int, object>
 */
function sfp_ao_fetch_rows() {
    global $wpdb;

    $values = sfp_ao_autoload_on_values();
    $placeholders = implode( ', ', array_fill( 0, count( $values ), '%s' ) );
    $sql = $wpdb->prepare(
        "SELECT option_id, option_name, LENGTH(option_value) AS size, autoload
           FROM {$wpdb->options}
          WHERE autoload IN ( $placeholders )
          ORDER BY LENGTH(option_value) DESC, option_name ASC",
        $values
    );

    return (array) $wpdb->get_results( $sql );
}

/**
 * Fetch the totals row for the dashboard summary.
 *
 * @return object { total_count: int, total_bytes: int }
 */
function sfp_ao_fetch_totals() {
    global $wpdb;

    $values = sfp_ao_autoload_on_values();
    $placeholders = implode( ', ', array_fill( 0, count( $values ), '%s' ) );
    $sql = $wpdb->prepare(
        "SELECT COUNT(*) AS total_count,
                COALESCE(SUM(LENGTH(option_value)), 0) AS total_bytes
           FROM {$wpdb->options}
          WHERE autoload IN ( $placeholders )",
        $values
    );

    $row = $wpdb->get_row( $sql );
    if ( ! $row ) {
        return (object) array( 'total_count' => 0, 'total_bytes' => 0 );
    }
    $row->total_count = (int) $row->total_count;
    $row->total_bytes = (int) $row->total_bytes;
    return $row;
}

/* =========================================================================
 * Formatting helpers
 * ====================================================================== */

/**
 * Format a byte count into a short human-readable string.
 *
 * @param int $bytes Byte count.
 * @return string
 */
function sfp_ao_format_size( $bytes ) {
    $bytes = (int) $bytes;
    if ( $bytes < 1024 ) {
        return $bytes . ' B';
    }
    if ( $bytes < 1024 * 100 ) {
        return number_format_i18n( $bytes / 1024, 1 ) . ' KB';
    }
    return number_format_i18n( $bytes / 1024 ) . ' KB';
}

/* =========================================================================
 * Admin-post handlers (mutations)
 * ====================================================================== */

add_action( 'admin_post_sfp_ao_action', 'sfp_ao_handle_action' );

/**
 * Handle the single action / bulk action form submission.
 *
 * Expected POST fields:
 *   - _wpnonce              (nonce 'sfp_ao_action')
 *   - sfp_ao_action         'autoload_off' | 'delete'
 *   - sfp_ao_options[]      array of option names
 *   - sfp_ao_filter         optional filter to return to
 */
function sfp_ao_handle_action() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Geen toegang.', 'sfp-page-config' ), 403 );
    }
    check_admin_referer( 'sfp_ao_action' );

    $raw_action = isset( $_POST['sfp_ao_action'] ) ? sanitize_key( wp_unslash( $_POST['sfp_ao_action'] ) ) : '';
    if ( 'autoload_off' !== $raw_action && 'delete' !== $raw_action ) {
        wp_safe_redirect( sfp_ao_page_url() );
        exit;
    }

    $names = isset( $_POST['sfp_ao_options'] ) && is_array( $_POST['sfp_ao_options'] )
        ? array_map( 'sanitize_text_field', wp_unslash( $_POST['sfp_ao_options'] ) )
        : array();
    $names = array_values( array_filter( $names, 'strlen' ) );

    $filter = isset( $_POST['sfp_ao_filter'] ) ? sanitize_key( wp_unslash( $_POST['sfp_ao_filter'] ) ) : 'all';

    if ( empty( $names ) ) {
        wp_safe_redirect( add_query_arg( array( 'sfp_ao_msg' => 'none', 'filter' => $filter ), sfp_ao_page_url() ) );
        exit;
    }

    $protected = sfp_ao_protected_options();
    $changed   = 0;
    $skipped   = 0;

    global $wpdb;

    foreach ( $names as $name ) {
        if ( 'delete' === $raw_action ) {
            if ( in_array( $name, $protected, true ) || sfp_ao_is_structural_widget_option( $name ) ) {
                $skipped++;
                continue;
            }
            if ( delete_option( $name ) ) {
                $changed++;
            } else {
                $skipped++;
            }
            continue;
        }

        // Autoload off: use wpdb->update so we don't accidentally
        // re-serialise or touch the value. WP's wp_set_option_autoload()
        // exists from 6.4 but the direct update is safer across all
        // installs.
        $result = $wpdb->update(
            $wpdb->options,
            array( 'autoload' => 'off' ),
            array( 'option_name' => $name ),
            array( '%s' ),
            array( '%s' )
        );
        if ( false !== $result ) {
            wp_cache_delete( $name, 'options' );
            wp_cache_delete( 'alloptions', 'options' );
            $changed++;
        } else {
            $skipped++;
        }
    }

    $url = add_query_arg(
        array(
            'sfp_ao_msg'     => $raw_action,
            'sfp_ao_changed' => $changed,
            'sfp_ao_skipped' => $skipped,
            'filter'         => $filter,
        ),
        sfp_ao_page_url()
    );
    wp_safe_redirect( $url );
    exit;
}

/**
 * URL to the analyzer page, without any query args.
 *
 * @return string
 */
function sfp_ao_page_url() {
    return admin_url( 'admin.php?page=sfp-autoloaded-options' );
}

/* =========================================================================
 * Render
 * ====================================================================== */

/**
 * Render the Autoloaded Options Analyzer admin page.
 */
function sfp_ao_render_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Geen toegang.', 'sfp-page-config' ), 403 );
    }

    $rows   = sfp_ao_fetch_rows();
    $totals = sfp_ao_fetch_totals();

    // Pre-compute source + status for every row.
    $active = sfp_ao_active_prefixes();
    foreach ( $rows as $r ) {
        $r->source = sfp_ao_detect_source( $r->option_name );
        $r->status = sfp_ao_detect_status( $r->option_name, $active );
    }

    // Counts per filter (for the tab labels).
    $count_all       = count( $rows );
    $count_large     = 0;
    $count_orphan    = 0;
    $count_transient = 0;
    foreach ( $rows as $r ) {
        if ( $r->size > 1024 ) {
            $count_large++;
        }
        if ( 'orphan' === $r->status ) {
            $count_orphan++;
        }
        if ( 'transient' === $r->status ) {
            $count_transient++;
        }
    }

    $filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : 'all';
    if ( ! in_array( $filter, array( 'all', 'large', 'orphan', 'transient' ), true ) ) {
        $filter = 'all';
    }

    // Apply the filter.
    $filtered = array_values( array_filter( $rows, function ( $r ) use ( $filter ) {
        switch ( $filter ) {
            case 'large':     return $r->size > 1024;
            case 'orphan':    return 'orphan' === $r->status;
            case 'transient': return 'transient' === $r->status;
            default:          return true;
        }
    } ) );

    // Sort. Default: size DESC. Clickable column headers set orderby +
    // order via the query string; a second click on the same column
    // flips the order.
    $orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'size';
    $order   = isset( $_GET['order'] )   ? strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : '';

    $valid_orderby = array( 'option_name', 'size', 'autoload', 'source', 'status' );
    if ( ! in_array( $orderby, $valid_orderby, true ) ) {
        $orderby = 'size';
    }
    if ( ! in_array( $order, array( 'asc', 'desc' ), true ) ) {
        // Sensible default per column: numeric columns DESC, text columns ASC.
        $order = ( 'size' === $orderby ) ? 'desc' : 'asc';
    }

    usort( $filtered, function ( $a, $b ) use ( $orderby, $order ) {
        if ( 'size' === $orderby ) {
            $cmp = ( (int) $a->size ) <=> ( (int) $b->size );
        } else {
            $cmp = strnatcasecmp( (string) $a->{$orderby}, (string) $b->{$orderby} );
        }
        // Secondary sort by option_name ASC so equal values stay stable.
        if ( 0 === $cmp && 'option_name' !== $orderby ) {
            $cmp = strnatcasecmp( (string) $a->option_name, (string) $b->option_name );
        }
        return 'desc' === $order ? -$cmp : $cmp;
    } );

    // Status bar maths. Site Health warns at 800 KB; we use 600 KB as
    // the green/orange cut-off so the warning comes well before WP's.
    $threshold = 800 * 1024;
    $bytes     = $totals->total_bytes;
    $percent   = $threshold > 0 ? min( 100, (int) round( ( $bytes / $threshold ) * 100 ) ) : 0;
    if ( $bytes > $threshold ) {
        $bar_class = 'red';
    } elseif ( $bytes > 600 * 1024 ) {
        $bar_class = 'orange';
    } else {
        $bar_class = 'green';
    }

    // Admin notice after an action.
    $notice = sfp_ao_read_notice();

    ?>
    <div class="wrap sfp-ao-wrap">
        <h1 class="wp-heading-inline">Autoloaded Options</h1>
        <p class="description" style="max-width: 820px;">
            Overzicht van alle opties met autoload aan (<code>on</code>, <code>auto</code>, <code>yes</code>).
            Vanaf WordPress 6.6 waarschuwt Site Health zodra de totale omvang boven de 800 KB komt. Op deze pagina
            kun je zien welke opties het meeste ruimte innemen, verweesde opties van oude plugins opsporen,
            en autoload uitzetten of opties verwijderen.
        </p>

        <?php if ( $notice ) : ?>
            <div class="notice <?php echo esc_attr( $notice['class'] ); ?> is-dismissible">
                <p><?php echo wp_kses_post( $notice['message'] ); ?></p>
                <?php if ( ! empty( $notice['cache'] ) ) : ?>
                    <p class="sfp-ao-reminder" style="margin: 8px 0 0;">
                        <strong>Cache-reminder:</strong> purge nu de caches op alle lagen: WP Rocket,
                        SiteGround (SG Optimizer), Cloudflare, en je browser.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="sfp-ao-stats">
            <div class="sfp-ao-card">
                <span>Totaal aantal</span>
                <strong><?php echo esc_html( number_format_i18n( $totals->total_count ) ); ?></strong>
            </div>
            <div class="sfp-ao-card">
                <span>Totale omvang</span>
                <strong><?php echo esc_html( sfp_ao_format_size( $totals->total_bytes ) ); ?></strong>
            </div>
            <div class="sfp-ao-card">
                <span>Site Health drempel</span>
                <strong>800 KB</strong>
            </div>
            <div class="sfp-ao-card">
                <span>Verweesd</span>
                <strong><?php echo esc_html( number_format_i18n( $count_orphan ) ); ?></strong>
            </div>
        </div>

        <div class="sfp-ao-bar <?php echo esc_attr( $bar_class ); ?>" aria-hidden="true">
            <div class="fill" style="width: <?php echo esc_attr( $percent ); ?>%;"></div>
        </div>
        <p class="sfp-ao-bar-label">
            <?php
            printf(
                esc_html__( '%1$s van 800 KB (%2$s%%). Groen: onder 600 KB. Oranje: 600-800 KB. Rood: boven 800 KB.', 'sfp-page-config' ),
                esc_html( sfp_ao_format_size( $bytes ) ),
                esc_html( (string) $percent )
            );
            ?>
        </p>

        <ul class="subsubsub sfp-ao-filters">
            <?php
            $tabs = array(
                'all'       => array( 'Alles',       $count_all ),
                'large'     => array( 'Groot (>1 KB)', $count_large ),
                'orphan'    => array( 'Verweesd',    $count_orphan ),
                'transient' => array( 'Transients',  $count_transient ),
            );
            $last_key = array_key_last( $tabs );
            foreach ( $tabs as $key => $meta ) :
                $url     = add_query_arg(
                    array(
                        'filter'  => $key,
                        'orderby' => $orderby,
                        'order'   => $order,
                    ),
                    sfp_ao_page_url()
                );
                $current = $filter === $key ? ' class="current"' : '';
                ?>
                <li>
                    <a href="<?php echo esc_url( $url ); ?>"<?php echo $current; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
                        <?php echo esc_html( $meta[0] ); ?>
                        <span class="count">(<?php echo esc_html( number_format_i18n( $meta[1] ) ); ?>)</span>
                    </a><?php if ( $key !== $last_key ) : ?> |<?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return sfpAoConfirm(this);">
            <input type="hidden" name="action" value="sfp_ao_action" />
            <input type="hidden" name="sfp_ao_filter" value="<?php echo esc_attr( $filter ); ?>" />
            <?php wp_nonce_field( 'sfp_ao_action' ); ?>

            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <label for="sfp-ao-bulk-top" class="screen-reader-text">Bulk actie</label>
                    <select name="sfp_ao_action" id="sfp-ao-bulk-top">
                        <option value="">Bulk actie</option>
                        <option value="autoload_off">Autoload uitschakelen</option>
                        <option value="delete">Verwijderen</option>
                    </select>
                    <button type="submit" class="button action">Toepassen</button>
                </div>
                <div class="alignleft actions">
                    <span class="description">
                        <?php echo esc_html( sprintf( '%d opties getoond', count( $filtered ) ) ); ?>
                    </span>
                </div>
            </div>

            <?php
            $columns = array(
                'option_name' => array( 'Option name', '' ),
                'size'        => array( 'Grootte',    'width: 90px;' ),
                'autoload'    => array( 'Autoload',   'width: 70px;' ),
                'source'      => array( 'Bron',       'width: 180px;' ),
                'status'      => array( 'Status',     'width: 110px;' ),
            );
            ?>
            <table class="wp-list-table widefat fixed striped sfp-ao-table">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" onclick="sfpAoToggleAll(this);" />
                        </td>
                        <?php foreach ( $columns as $col_key => $col_meta ) :
                            $is_current = $orderby === $col_key;
                            // Clicking the current column flips its order;
                            // clicking a new column uses the column's default.
                            if ( $is_current ) {
                                $next_order = 'asc' === $order ? 'desc' : 'asc';
                            } else {
                                $next_order = ( 'size' === $col_key ) ? 'desc' : 'asc';
                            }
                            $col_url = add_query_arg(
                                array(
                                    'filter'  => $filter,
                                    'orderby' => $col_key,
                                    'order'   => $next_order,
                                ),
                                sfp_ao_page_url()
                            );
                            $th_classes = array( 'manage-column', 'sortable' );
                            if ( $is_current ) {
                                $th_classes[] = 'sorted';
                                $th_classes[] = $order;
                            } else {
                                $th_classes[] = 'desc'; // idle state gets down-chevron per WP convention
                            }
                            $style_attr = $col_meta[1] ? ' style="' . esc_attr( $col_meta[1] ) . '"' : '';
                            ?>
                            <th scope="col" class="<?php echo esc_attr( implode( ' ', $th_classes ) ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
                                <a href="<?php echo esc_url( $col_url ); ?>">
                                    <span><?php echo esc_html( $col_meta[0] ); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $filtered ) ) : ?>
                        <tr><td colspan="6" style="text-align:center; padding: 24px;">
                            Geen opties gevonden voor dit filter.
                        </td></tr>
                    <?php else : foreach ( $filtered as $r ) :
                        $is_protected = in_array( $r->option_name, sfp_ao_protected_options(), true )
                                     || sfp_ao_is_structural_widget_option( $r->option_name );
                        $status_label = array(
                            'active'    => array( 'Actief',    'sfp-ao-status-active' ),
                            'orphan'    => array( 'Verweesd',  'sfp-ao-status-orphan' ),
                            'transient' => array( 'Transient', 'sfp-ao-status-transient' ),
                            'core'      => array( 'WP Core',   'sfp-ao-status-core' ),
                        );
                        $label = $status_label[ $r->status ] ?? $status_label['active'];
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox"
                                       name="sfp_ao_options[]"
                                       value="<?php echo esc_attr( $r->option_name ); ?>"
                                       data-protected="<?php echo $is_protected ? '1' : '0'; ?>" />
                            </th>
                            <td class="sfp-ao-option-name">
                                <?php echo esc_html( $r->option_name ); ?>
                                <?php if ( $is_protected ) : ?>
                                    <br><span class="sfp-ao-protected">Beschermd (kan niet verwijderd worden)</span>
                                <?php endif; ?>
                            </td>
                            <td class="sfp-ao-size"><?php echo esc_html( sfp_ao_format_size( $r->size ) ); ?></td>
                            <td><code><?php echo esc_html( $r->autoload ); ?></code></td>
                            <td><?php echo esc_html( $r->source ); ?></td>
                            <td>
                                <span class="sfp-ao-status-badge <?php echo esc_attr( $label[1] ); ?>">
                                    <?php echo esc_html( $label[0] ); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <label for="sfp-ao-bulk-bottom" class="screen-reader-text">Bulk actie</label>
                    <select name="sfp_ao_action_bottom" id="sfp-ao-bulk-bottom" onchange="document.getElementById('sfp-ao-bulk-top').value = this.value;">
                        <option value="">Bulk actie</option>
                        <option value="autoload_off">Autoload uitschakelen</option>
                        <option value="delete">Verwijderen</option>
                    </select>
                    <button type="submit" class="button action">Toepassen</button>
                </div>
            </div>
        </form>

        <h2 style="margin-top: 32px;">Uitleg</h2>
        <p>
            <strong>Autoload uitschakelen</strong> verplaatst de optie van automatisch laden naar "op verzoek".
            WordPress laadt de optie dan alleen nog als een plugin er expliciet om vraagt.
            Dit is voor bijna elke optie veilig, ook voor opties die een plugin af en toe nodig heeft.
        </p>
        <p>
            <strong>Verwijderen</strong> schrapt de rij uit <code>wp_options</code>. Doe dit alleen voor
            verweesde opties van gedeinstalleerde plugins. Beschermde opties (<code>siteurl</code>,
            <code>active_plugins</code>, <code>widget_block</code>, enzovoort) kunnen vanuit deze pagina
            niet verwijderd worden.
        </p>

        <script>
            function sfpAoToggleAll(src) {
                var boxes = document.querySelectorAll('input[name="sfp_ao_options[]"]');
                for (var i = 0; i < boxes.length; i++) {
                    boxes[i].checked = src.checked;
                }
            }
            function sfpAoConfirm(form) {
                var action = form.querySelector('select[name="sfp_ao_action"]').value
                          || form.querySelector('select[name="sfp_ao_action_bottom"]').value;
                if (!action) {
                    alert('Kies eerst een bulkactie.');
                    return false;
                }
                // Keep top + bottom selects in sync.
                form.querySelector('select[name="sfp_ao_action"]').value = action;

                var boxes = form.querySelectorAll('input[name="sfp_ao_options[]"]:checked');
                if (!boxes.length) {
                    alert('Selecteer minstens een optie.');
                    return false;
                }

                if (action === 'delete') {
                    var protectedCount = 0;
                    for (var i = 0; i < boxes.length; i++) {
                        if (boxes[i].getAttribute('data-protected') === '1') protectedCount++;
                    }
                    var msg = 'Weet je zeker dat je ' + boxes.length + ' optie(s) permanent wilt verwijderen?\n\n'
                            + 'Dit kan niet ongedaan worden gemaakt.';
                    if (protectedCount > 0) {
                        msg += '\n\n(' + protectedCount + ' beschermde optie(s) worden overgeslagen.)';
                    }
                    return confirm(msg);
                }

                return confirm('Autoload uitschakelen voor ' + boxes.length + ' optie(s)?');
            }
        </script>
    </div>
    <?php
}

/**
 * Read any admin-notice that should be shown after a redirect back
 * from admin-post.php.
 *
 * @return array|null Array with 'class', 'message' and optional 'cache' flag.
 */
function sfp_ao_read_notice() {
    if ( empty( $_GET['sfp_ao_msg'] ) ) {
        return null;
    }
    $msg     = sanitize_key( wp_unslash( $_GET['sfp_ao_msg'] ) );
    $changed = isset( $_GET['sfp_ao_changed'] ) ? (int) $_GET['sfp_ao_changed'] : 0;
    $skipped = isset( $_GET['sfp_ao_skipped'] ) ? (int) $_GET['sfp_ao_skipped'] : 0;

    switch ( $msg ) {
        case 'autoload_off':
            return array(
                'class'   => 'notice-success',
                'message' => sprintf(
                    'Autoload uitgeschakeld voor <strong>%d</strong> optie(s).%s',
                    $changed,
                    $skipped > 0 ? sprintf( ' %d overgeslagen.', $skipped ) : ''
                ),
                'cache'   => $changed > 0,
            );
        case 'delete':
            return array(
                'class'   => 'notice-success',
                'message' => sprintf(
                    '<strong>%d</strong> optie(s) verwijderd.%s',
                    $changed,
                    $skipped > 0 ? sprintf( ' %d overgeslagen (beschermd of al weg).', $skipped ) : ''
                ),
                'cache'   => $changed > 0,
            );
        case 'none':
            return array(
                'class'   => 'notice-warning',
                'message' => 'Geen opties geselecteerd.',
                'cache'   => false,
            );
    }
    return null;
}
