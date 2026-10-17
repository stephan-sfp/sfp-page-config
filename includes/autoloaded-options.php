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
 *   - Delete orphaned and unknown options after a confirmation screen
 *     with the exact list and a JSON backup (since 3.2.0).
 *   - Show leftovers whose autoload is already off (since 3.2.0).
 *   - Read the same table over REST: GET /sfp/v1/autoload (since 3.2.0).
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
        .sfp-ao-status-unknown { background: #f0f0f1; color: #50575e; }
        .sfp-ao-status-eigen { background: #f3e8fd; color: #5b2a86; }
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
        'fluentmail'                 => 'FluentSMTP',
        '_fluentmail'                => 'FluentSMTP',
        'bsr_'                       => 'Better Search Replace',
        'cptui_'                     => 'Custom Post Type UI',
        'googlesitekit'              => 'Google Site Kit',
        'aioseo'                     => 'All in One SEO',
        'aioseop'                    => 'All in One SEO',
        'fs_'                        => 'Freemius',
        'bsf_'                       => 'Brainstorm Force',
        'brainstrom'                 => 'Brainstorm Force',
        // v2.9.4: more prefixes of the current stack and shared libraries.
        'asenha_'                    => 'ASE Pro',
        'default_term_asenha'        => 'ASE Pro',
        'allowed_astra_notices'      => 'Astra Theme',
        '_astra_'                    => 'Astra Theme',
        'ast-'                       => 'Astra Theme',
        'ast_'                       => 'Astra Theme',
        'is_beta_enable_rollback_'   => 'Brainstorm Force',
        'allowed_BSF'                => 'Brainstorm Force',
        'bsf-'                       => 'Brainstorm Force',
        'nps-survey-convertpro'      => 'Convert Pro',
        'nps-survey-'                => 'Brainstorm Force',
        'uagb-'                      => 'Spectra',
        '_uagb_'                     => 'Spectra',
        '__uagb_'                    => 'Spectra',
        '__spectra_pro_'             => 'Spectra',
        'srfm-'                      => 'SureForms',
        '__srfm_'                    => 'SureForms',
        'presto_'                    => 'Presto Player',
        'latepoint'                  => 'LatePoint',
        'gtm_server_side'            => 'Stape Conversion Tracking',
        'automatic_platform_optimization' => 'Cloudflare',
        'cloudflare_'                => 'Cloudflare',
        'sgo_'                       => 'SiteGround Optimizer',
        'sgs_'                       => 'SiteGround Security',
        'sg_security'                => 'SiteGround Security',
        'wpr_'                       => 'WP Rocket',
        'action_scheduler'           => 'Action Scheduler (gedeeld)',
        'schema-ActionScheduler'     => 'Action Scheduler (gedeeld)',
        'cp-pro-'                    => 'Convert Pro',
        'cpro-'                      => 'Convert Pro',
        // Retired plugins seen on the network (autoload clean-up 25-09-2026).
        'code_snippets'              => 'Code Snippets',
        'recently_activated_snippets' => 'Code Snippets',
        'duplicate_post_'            => 'Yoast Duplicate Post',
        'cfkef'                      => 'Cool FormKit (Elementor)',
        'cfef_'                      => 'Conditional Fields (Elementor)',
        'pum_'                       => 'Popup Maker',
        'pum-'                       => 'Popup Maker',
        '_pum_'                      => 'Popup Maker',
        'popmake_'                   => 'Popup Maker',
        'thirsty'                    => 'ThirstyAffiliates',
        'wpstg_'                     => 'WP Staging',
        'cfturnstile_'               => 'Simple Cloudflare Turnstile',
        'kc_us_'                     => 'URL Shortify',
        'wts_eae_'                   => 'Elementor Addon Elements',
        'ai1wm_'                     => 'All-in-One WP Migration',
        'omapi_'                     => 'OptinMonster',
        'optinmonster_'              => 'OptinMonster',
        'mmb_'                       => 'ManageWP Worker',
        'worker_migration_version'   => 'ManageWP Worker',
        'image_optimizer_'           => 'Image Optimizer (Elementor)',
        'clarity_'                   => 'Microsoft Clarity',
        'hello_theme_'               => 'Hello Elementor',
        'e_editor_'                  => 'Elementor',
        'uael-'                      => 'Ultimate Addons for Elementor',
        'swift_performance_'         => 'Swift Performance',
        '_wpforms_'                  => 'WPForms',
        'wpforms'                    => 'WPForms',
        // v3.2.1: actieve plugins met afwijkende optienamen.
        'suretrigger'                => 'Ottokit (SureTriggers)',
        'fsmtp_'                     => 'FluentSMTP',
        'zip_ai_'                    => 'Brainstorm Force',
        'wpmedia_mcp'                => 'WP Rocket',
        'mcp_jwt_secret'             => 'WP Rocket',
        'latepoint_'                 => 'LatePoint',
        // v3.2.1: nog meer plugins die niet meer op het netwerk staan.
        'rsssl_'                     => 'Really Simple SSL',
        'perfmatters'                => 'Perfmatters',
        'trustindex'                 => 'Trustindex',
        'rankability'                => 'Rankability',
        'wpmdb_'                     => 'WP Migrate',
        'monsterinsights'            => 'MonsterInsights',
        'eae_'                       => 'Elementor Addon Elements',
        'hello-elementor'            => 'Hello Elementor',
        'convertpro_'                => 'Convert Pro',
        'wpsurfer_'                  => 'Surfer SEO',
        'matomo'                     => 'Matomo',
        // v3.2.0: plugins die niet meer op het netwerk staan (opruiming 16-10-2026).
        'tagging_settings_'          => 'Stape Conversion Tracking',
        'suremails'                  => 'SureMails',
        'su_option_'                 => 'Shortcodes Ultimate',
        'grw_'                       => 'Google Reviews Widget',
        'rplg_'                      => 'Google Reviews Widget',
        'tlwp_'                      => 'Temporary Login Without Password',
        'woocommerce_'               => 'WooCommerce',
        'rtafar_'                    => 'Real-Time Find and Replace',
        'acf_'                       => 'Advanced Custom Fields',
        'vwo_'                       => 'VWO',
        'bitforms_'                  => 'Bit Form',
        'atfp'                       => 'Automatic Translations for Polylang',
        'polylang'                   => 'Polylang',
        'pll_'                       => 'Polylang',
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
        'Better Search Replace'  => array( 'better-search-replace' ),
        'Custom Post Type UI'    => array( 'custom-post-type-ui' ),
        'Google Site Kit'        => array( 'google-site-kit' ),
        'All in One SEO'         => array( 'all-in-one-seo-pack', 'all-in-one-seo-pack-pro' ),
        'Surfer SEO'             => array( 'surferseo' ),
        'Freemius'               => array( 'admin-site-enhancements', 'admin-site-enhancements-pro', 'imagify', 'presto-player', 'presto-player-pro', 'sureforms', 'sureforms-pro', 'convert-pro', 'convert-pro-addon' ),
        'LatePoint'              => array( 'latepoint' ),
        'Stape Conversion Tracking' => array( 'gtm-server-side' ),
        'Cloudflare'             => array( 'cloudflare' ),
        'SiteGround Security'    => array( 'sg-security' ),
        'Action Scheduler (gedeeld)' => array( 'surecontact', 'sureforms', 'sureforms-pro', 'suretriggers', 'wp-rocket', 'presto-player', 'latepoint', 'surerank', 'imagify' ),
        'WPForms'                => array( 'wpforms', 'wpforms-lite' ),
        'Code Snippets'          => array( 'code-snippets', 'code-snippets-pro' ),
        'Yoast Duplicate Post'   => array( 'duplicate-post' ),
        'SureMails'              => array( 'suremails' ),
        'Shortcodes Ultimate'    => array( 'shortcodes-ultimate', 'shortcodes-ultimate-pro' ),
        'Google Reviews Widget'  => array( 'widget-google-reviews', 'business-reviews-bundle' ),
        'Temporary Login Without Password' => array( 'temporary-login-without-password' ),
        'WooCommerce'            => array( 'woocommerce' ),
        'Real-Time Find and Replace' => array( 'real-time-find-and-replace' ),
        'Advanced Custom Fields' => array( 'advanced-custom-fields', 'advanced-custom-fields-pro', 'secure-custom-fields' ),
        'VWO'                    => array( 'vwo' ),
        'Bit Form'               => array( 'bit-form', 'bit-form-pro' ),
        'Automatic Translations for Polylang' => array( 'automatic-translations-for-polylang' ),
        'Polylang'               => array( 'polylang', 'polylang-pro' ),
        'Really Simple SSL'      => array( 'really-simple-ssl', 'really-simple-ssl-pro' ),
        'Perfmatters'            => array( 'perfmatters' ),
        'Trustindex'             => array( 'wp-reviews-plugin-for-google', 'trustindex' ),
        'Rankability'            => array( 'rankability' ),
        'WP Migrate'             => array( 'wp-migrate-db', 'wp-migrate-db-pro' ),
        'MonsterInsights'        => array( 'google-analytics-for-wordpress', 'google-analytics-premium' ),
        'Elementor Addon Elements' => array( 'addon-elements-for-elementor-page-builder' ),
        'Hello Elementor'        => array( 'hello-elementor' ),
        'Matomo'                 => array( 'matomo' ),
        'Brainstorm Force'       => array( 'astra','spectra-pro', 'ultimate-addons-for-gutenberg', 'surerank', 'surerank-pro', 'surecart', 'sureforms', 'sureforms-pro', 'surecontact', 'convert-pro', 'convert-pro-addon', 'presto-player-pro', 'sfp-page-config' ),
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
        'bsr_',
        'cptui_',
        'googlesitekit',
        // v2.9.4: 'fluentmail' removed. FluentSMTP (active on every site)
        // stores its settings in 'fluentmail-settings'; the old entry made
        // the analyzer flag that option as orphan and Safe Cleanup delete it.
        // Added in v2.9.4 after the autoload clean-up of CVD and DST.
        'html-regression',
        'progress_planner',
        'code_snippets',
        'recently_activated_snippets',
        'duplicate_post_',
        'cfkef',
        'cfef_',
        'pum_',
        'pum-',
        '_pum_',
        'popmake_',
        'thirsty',
        'wpstg_',
        'cfturnstile_',
        'kc_us_',
        'wts_eae_',
        'ai1wm_',
        'omapi_',
        'optinmonster_',
        'mmb_',
        'worker_migration_version',
        'image_optimizer_',
        'clarity_',
        'hello_theme_',
        'e_editor_',
        'uael-',
        'swift_performance_',
        'nps-survey-convertpro',
        'cp_parent_page_url',
        'cp-pro-',
        'cpro-',
        '_wpforms_',
        // NOTE: 'xrk_' was temporarily in this list in v2.6.3 but has
        // been removed in v2.6.4 because a random-looking prefix like
        // 'xrk_' on a hardened install is the WordPress db-prefix, not
        // an orphan marker. Core-prefixed options (e.g. xrk_user_roles)
        // are protected via sfp_ao_is_core_prefixed_option().
    );
}

/**
 * Prefixes that the Safe Cleanup button will purge in one click.
 *
 * This is a STRICT subset of sfp_ao_known_orphan_prefixes(): only
 * plugins that Stephan has definitively moved away from across the
 * SFP stack and whose options are therefore residue on every site.
 * Each prefix is paired with the human-readable plugin label that
 * shows up in the preview.
 *
 * Prefixes with potential ambiguity (generic 'jetpack', 'w3tc',
 * 'wordfence' etc.) are intentionally NOT included: those require
 * manual judgement because Stephan might want to re-enable them in
 * the future. The list can be extended per release.
 *
 * @return array<string, string>  prefix => plugin label
 * @since 2.6.4
 */
function sfp_ao_safe_cleanup_prefixes() {
    return array(
        // Retired SEO plugins (replaced by SureRank across the stack).
        'wpseo'         => 'Yoast SEO',
        'yoast'         => 'Yoast SEO',
        'rank_math'     => 'Rank Math SEO',
        'rank-math'     => 'Rank Math SEO',
        'rankmath'      => 'Rank Math SEO',
        'aioseo'        => 'All in One SEO',
        'aioseop'       => 'All in One SEO',
        'surfer_'       => 'Surfer SEO',

        // Retired page builder (switched to Spectra).
        'elementor_'    => 'Elementor',
        'elementor-'    => 'Elementor',

        // Retired multi-site management tooling.
        'mainwp'        => 'MainWP',
        'mwp_'          => 'MainWP',

        // Retired backup/admin helpers. 'fluentmail' was removed in
        // v2.9.4: those options belong to FluentSMTP, which is active.
        'bsr_'          => 'Better Search Replace',
        'cptui_'        => 'Custom Post Type UI',
        'googlesitekit' => 'Google Site Kit',
    );
}

/**
 * Build the list of option names that Safe Cleanup will delete.
 *
 * A row is included when:
 *   - Its status is 'orphan' (no active plugin or theme owns it).
 *   - Its name matches one of the safe-cleanup prefixes above.
 *   - It is NOT on the protected list, NOT a structural widget
 *     option, and NOT a core-prefixed option.
 *   - It is NOT a transient (transients clear themselves on schedule).
 *
 * @param array $rows   Row objects from sfp_ao_fetch_rows(). Must have
 *                      ->option_name, ->size and (optional) ->status.
 * @param array $active Active prefix set from sfp_ao_active_prefixes().
 * @return array {
 *     @type array<int, object> $rows   Row objects that match.
 *     @type int                $bytes  Total byte count of those rows.
 *     @type array<string, int> $by_label  Per-plugin count for summary.
 * }
 * @since 2.6.4
 */
function sfp_ao_collect_safe_cleanup( array $rows, array $active ) {
    $safe_prefixes = sfp_ao_safe_cleanup_prefixes();
    // Sort by prefix length DESC so longer, more specific prefixes
    // match first (rank_math before rank- before rank).
    $keys = array_keys( $safe_prefixes );
    usort( $keys, function ( $a, $b ) {
        return strlen( $b ) - strlen( $a );
    } );

    $protected    = sfp_ao_protected_options();
    $matched_rows = array();
    $total_bytes  = 0;
    $by_label     = array();

    foreach ( $rows as $row ) {
        $name = (string) $row->option_name;

        if ( 0 === strpos( $name, '_transient_' )
            || 0 === strpos( $name, '_site_transient_' )
        ) {
            continue;
        }
        if ( in_array( $name, $protected, true )
            || sfp_ao_is_structural_widget_option( $name )
            || sfp_ao_is_core_prefixed_option( $name )
        ) {
            continue;
        }
        // Only clean up orphans. If the option matches an active
        // plugin prefix (directly or via source-to-slug), leave it.
        if ( sfp_ao_matches_active_plugin( $name, $active ) ) {
            continue;
        }

        foreach ( $keys as $prefix ) {
            if ( 0 === strpos( $name, $prefix ) ) {
                $label = $safe_prefixes[ $prefix ];
                $matched_rows[] = $row;
                $total_bytes   += (int) $row->size;
                if ( ! isset( $by_label[ $label ] ) ) {
                    $by_label[ $label ] = 0;
                }
                $by_label[ $label ]++;
                break;
            }
        }
    }

    // Sort the per-label summary by count DESC for readability.
    arsort( $by_label );

    return array(
        'rows'     => $matched_rows,
        'bytes'    => $total_bytes,
        'by_label' => $by_label,
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

/**
 * WordPress stores a handful of core options under a name derived
 * from the database table prefix (e.g. '{prefix}user_roles'). On
 * hardened installs that prefix is set to a random string like
 * 'xrk_' so the option ends up named 'xrk_user_roles'. These are
 * still core options and must never be flagged as orphan or deleted.
 *
 * @return string[] Suffixes that indicate a core-prefixed option.
 * @since 2.6.4
 */
function sfp_ao_core_prefixed_suffixes() {
    return array(
        'user_roles',
        'user_count',
        'user_count_start',
    );
}

/**
 * Check whether an option name ends in one of the core-prefixed
 * suffixes (e.g. 'xrk_user_roles' on a hardened-prefix install).
 *
 * @param string $name Option name.
 * @return bool
 * @since 2.6.4
 */
function sfp_ao_is_core_prefixed_option( $name ) {
    foreach ( sfp_ao_core_prefixed_suffixes() as $suffix ) {
        $needle = '_' . $suffix;
        $len    = strlen( $needle );
        if ( $len > 0 && strlen( $name ) > $len
            && substr( $name, -$len ) === $needle
        ) {
            return true;
        }
    }
    return false;
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

    // v2.9.4: '{db-prefix}user_roles' and friends are core options on
    // installs with a custom table prefix (e.g. 'xrk_user_roles').
    if ( sfp_ao_is_core_prefixed_option( $name ) ) {
        return 'WordPress Core';
    }
    if ( in_array( $name, sfp_ao_core_option_names(), true ) ) {
        return 'WordPress Core';
    }

    foreach ( sfp_ao_name_variants( $name ) as $variant ) {
        foreach ( $sorted_keys as $prefix ) {
            if ( 0 === strpos( $variant, $prefix ) ) {
                return $prefixes[ $prefix ];
            }
        }
    }

    $eigen = sfp_ao_own_source( $name );
    if ( '' !== $eigen ) {
        return $eigen;
    }

    return 'Onbekend';
}

/**
 * De naam zelf en, als die met liggende streepjes begint, de naam zonder.
 * Plugins zetten vaak een '_' voor interne opties ('_fluentsmtp_...').
 *
 * @param string $name Option name.
 * @return string[]
 * @since 3.2.1
 */
function sfp_ao_name_variants( $name ) {
    $kaal = ltrim( (string) $name, '_' );
    return ( '' !== $kaal && $kaal !== $name ) ? array( (string) $name, $kaal ) : array( (string) $name );
}

/**
 * Opties die bij geen enkele losse plugin horen maar door actieve plugins
 * gedeeld worden: licentiegegevens, updatecontroles, Action Scheduler.
 * Die gelden altijd als actief.
 *
 * @return array<string, string> prefix => bron
 * @since 3.2.1
 */
function sfp_ao_shared_prefixes() {
    return array(
        'edd_sl_'           => 'Licentiegegevens (gedeeld)',
        'edd_api_request_'  => 'Licentiegegevens (gedeeld)',
        'external_updates-' => 'Updatecontrole (gedeeld)',
        'as_has_'           => 'Action Scheduler (gedeeld)',
    );
}

/**
 * Sitecodes van het netwerk. Opties die daarmee beginnen zijn gegevens
 * van eigen snippets.
 *
 * @return string[]
 * @since 3.2.1
 */
function sfp_ao_site_codes() {
    return array( 'dps', 'dga', 'cvd', 'dst', 'slb', 'sws', 'fl' );
}

/**
 * Alle code van ASE-snippets op deze site, eenmaal per verzoek gelezen.
 *
 * @return string
 * @since 3.2.1
 */
function sfp_ao_snippet_code( $vernieuw = false ) {
    static $code = null;
    if ( null !== $code && ! $vernieuw ) {
        return $code;
    }
    global $wpdb;
    $rows = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT post_content FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ( 'publish', 'draft', 'private', 'pending', 'future' )",
            'asenha_code_snippet'
        )
    );
    $code = implode( "\n", array_map( 'strval', (array) $rows ) );
    return $code;
}

/**
 * Is dit een optie van een eigen snippet? Ja als de naam met een sitecode
 * begint, of als de naam tussen aanhalingstekens in de code van een
 * ASE-snippet staat.
 *
 * @param string $name Option name.
 * @return string Bron, of '' als het geen eigen optie is.
 * @since 3.2.1
 */
function sfp_ao_own_source( $name ) {
    $name = (string) $name;
    foreach ( sfp_ao_site_codes() as $code ) {
        if ( 0 === strpos( $name, $code . '_' ) || 0 === strpos( $name, $code . '-' ) ) {
            return 'Eigen gegevens (sitecode)';
        }
    }
    $snippets = sfp_ao_snippet_code();
    if ( '' !== $snippets && ( false !== strpos( $snippets, "'" . $name . "'" ) || false !== strpos( $snippets, '"' . $name . '"' ) ) ) {
        return 'Eigen gegevens (snippet)';
    }
    return '';
}

/**
 * Determine the status classification of an option.
 *
 * Returns one of: 'transient', 'orphan', 'core', 'active', 'unknown'.
 *
 * Since v2.9.4 an option that matches no known prefix is 'unknown'
 * instead of 'orphan'. Only 'orphan' options can be deleted.
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
        || in_array( $name, sfp_ao_core_option_names(), true )
        || 0 === strpos( $name, 'theme_mods_' )
        || 0 === strpos( $name, 'widget_' )
        || sfp_ao_is_core_prefixed_option( $name )
        || ( sfp_ao_is_core_pattern( $name ) && 0 !== strpos( $name, 'default_term_asenha' ) )
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

    // v3.2.1: gedeelde opties van actieve plugins (licenties, updates).
    foreach ( sfp_ao_name_variants( $name ) as $variant ) {
        foreach ( sfp_ao_shared_prefixes() as $prefix => $_ ) {
            if ( 0 === strpos( $variant, $prefix ) ) {
                return 'active';
            }
        }
    }

    // v3.2.1: gegevens van eigen snippets. Nooit verwijderbaar.
    if ( '' !== sfp_ao_own_source( $name ) ) {
        return 'eigen';
    }

    // Known-deactivated plugin prefixes.
    foreach ( sfp_ao_known_orphan_prefixes() as $orphan ) {
        foreach ( sfp_ao_name_variants( $name ) as $variant ) {
            if ( 0 === strpos( $variant, $orphan ) ) {
                return 'orphan';
            }
        }
    }

    // v3.2.0: herkende bron waarvan geen enkele eigenaar actief is, ook als
    // de plugin nog geinstalleerd staat (zoals Spectra op SwS). Was een van
    // de eigenaren actief, dan had de controle hierboven al 'active' gegeven.
    $slugs = sfp_ao_source_to_active_slugs();
    if ( isset( $slugs[ sfp_ao_detect_source( $name ) ] ) ) {
        return 'orphan';
    }

    // Default fallback (v2.9.4): no prefix matches, so we do not know
    // who owns this option. That is not proof it is orphaned.
    return 'unknown';
}

/**
 * Option names WordPress core creates itself (populate_options() and
 * options core adds later at runtime). These are always 'core'.
 *
 * @return string[]
 * @since 2.9.4
 */
function sfp_ao_core_option_names() {
    return array(
        'siteurl', 'home', 'blogname', 'blogdescription', 'users_can_register',
        'admin_email', 'start_of_week', 'use_balanceTags', 'use_smilies',
        'require_name_email', 'comments_notify', 'posts_per_rss', 'rss_use_excerpt',
        'mailserver_url', 'mailserver_login', 'mailserver_pass', 'mailserver_port',
        'default_category', 'default_comment_status', 'default_ping_status',
        'default_pingback_flag', 'posts_per_page', 'date_format', 'time_format',
        'links_updated_date_format', 'comment_moderation', 'moderation_notify',
        'permalink_structure', 'rewrite_rules', 'hack_file', 'blog_charset',
        'moderation_keys', 'active_plugins', 'category_base', 'ping_sites',
        'comment_max_links', 'gmt_offset', 'default_email_category', 'recently_edited',
        'template', 'stylesheet', 'comment_registration', 'html_type', 'use_trackback',
        'default_role', 'db_version', 'uploads_use_yearmonth_folders', 'upload_path',
        'blog_public', 'default_link_category', 'show_on_front', 'tag_base',
        'show_avatars', 'avatar_rating', 'upload_url_path', 'thumbnail_size_w',
        'thumbnail_size_h', 'thumbnail_crop', 'medium_size_w', 'medium_size_h',
        'avatar_default', 'large_size_w', 'large_size_h', 'image_default_link_type',
        'image_default_size', 'image_default_align', 'close_comments_for_old_posts',
        'close_comments_days_old', 'thread_comments', 'thread_comments_depth',
        'page_comments', 'comments_per_page', 'default_comments_page', 'comment_order',
        'sticky_posts', 'uninstall_plugins', 'timezone_string', 'page_for_posts',
        'page_on_front', 'default_post_format', 'link_manager_enabled',
        'finished_splitting_shared_terms', 'site_icon', 'medium_large_size_w',
        'medium_large_size_h', 'wp_page_for_privacy_policy', 'show_comments_cookies_opt_in',
        'admin_email_lifespan', 'disallowed_keys', 'comment_previously_approved',
        'auto_plugin_theme_update_emails', 'auto_update_core_dev', 'auto_update_core_minor',
        'auto_update_core_major', 'wp_force_deactivated_plugins', 'wp_attachment_pages_enabled',
        'wp_notes_notify', 'initial_db_version', 'current_theme', 'WPLANG', 'cron',
        'sidebars_widgets', 'can_compress_scripts', 'recently_activated', 'recovery_keys',
        'recovery_mode_email_last_sent', 'https_detection_errors', 'fresh_site',
        'user_count', 'new_admin_email', 'auto_core_update_notified', 'theme_switched',
        'category_children', 'site_logo', 'db_upgraded', 'finished_updating_comment_type',
        'https_migration_required', 'nav_menu_options', 'wp_calendar_block_has_published_posts',
        'widget_block', 'wp_user_roles', 'embed_autourls', 'embed_size_w', 'embed_size_h',
        'secret', 'upload_space_check_disabled',
        // v3.2.0: sleutels die WordPress in de database zet als ze niet in
        // wp-config.php staan, en opties die core zelf aanmaakt.
        'auth_key', 'auth_salt', 'secure_auth_key', 'secure_auth_salt',
        'logged_in_key', 'logged_in_salt', 'nonce_key', 'nonce_salt',
        '_split_terms', '_wp_suggested_policy_text_has_changed', 'adminhash',
        'allowedthemes', 'customize_stashed_theme_mods', 'dashboard_widget_options',
        'delete_blog_hash', 'ftp_credentials', 'post_count', 'stylesheet_root',
        'template_root', 'theme_switch_menu_locations', 'theme_switched_via_customizer',
        'auto_update_plugins', 'auto_update_themes', 'dismissed_update_core',
        'core_updater.lock', 'auto_updater.lock', 'active_sitewide_plugins',
        'using_application_passwords',
    );
}

/**
 * Opties van core met een wisselende naam: standaardtermen per taxonomie,
 * de kinderlijst per taxonomie, herstelmodus en resultaten van verwijderen.
 *
 * @param string $name Option name.
 * @return bool
 * @since 3.2.0
 */
function sfp_ao_is_core_pattern( $name ) {
    foreach ( array( 'default_term_', 'recovery_mode_', 'plugins_delete_result_' ) as $prefix ) {
        if ( 0 === strpos( $name, $prefix ) ) {
            return true;
        }
    }
    return '_children' === substr( $name, -9 ) && strlen( $name ) > 9;
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
    foreach ( sfp_ao_name_variants( $name ) as $variant ) {
        foreach ( $active as $prefix => $_ ) {
            if ( '' === $prefix ) {
                continue;
            }
            if ( 0 === strpos( $variant, $prefix ) ) {
                return true;
            }
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

/**
 * Opties die niet automatisch laden, zonder transients. Hieruit komen de
 * restanten die al op autoload uit staan.
 *
 * @return array<int, object>
 * @since 3.2.0
 */
function sfp_ao_fetch_rows_off() {
    global $wpdb;

    $values       = sfp_ao_autoload_on_values();
    $placeholders = implode( ', ', array_fill( 0, count( $values ), '%s' ) );
    $args         = array_merge(
        $values,
        array(
            $wpdb->esc_like( '_transient_' ) . '%',
            $wpdb->esc_like( '_site_transient_' ) . '%',
        )
    );
    $sql = $wpdb->prepare(
        "SELECT option_id, option_name, LENGTH(option_value) AS size, autoload
           FROM {$wpdb->options}
          WHERE autoload NOT IN ( $placeholders )
            AND option_name NOT LIKE %s
            AND option_name NOT LIKE %s
          ORDER BY LENGTH(option_value) DESC, option_name ASC",
        $args
    );

    return (array) $wpdb->get_results( $sql );
}

/**
 * Mag deze optie vanuit de analyzer verwijderd worden?
 *
 * Verweesd en onbekend mogen, na bevestiging met de exacte lijst.
 * Beschermd, core, transients en opties van actieve plugins nooit.
 *
 * @param string $name   Option name.
 * @param string $status Uitkomst van sfp_ao_detect_status().
 * @return bool
 * @since 3.2.0
 */
function sfp_ao_is_deletable( $name, $status ) {
    if ( in_array( $name, sfp_ao_protected_options(), true )
        || sfp_ao_is_structural_widget_option( $name )
        || sfp_ao_is_core_prefixed_option( $name )
    ) {
        return false;
    }
    return in_array( $status, array( 'orphan', 'unknown' ), true );
}

/**
 * De grens waarboven WordPress een optie zelf niet meer automatisch laadt
 * als die zonder expliciete keuze wordt opgeslagen (sinds 6.6: 150.000 bytes).
 *
 * @return int
 * @since 3.2.0
 */
function sfp_ao_max_autoload_size() {
    return (int) apply_filters( 'wp_max_autoloaded_option_size', 150000 );
}

/**
 * Opties boven die grens die expliciet op autoload aan staan ('on' of het
 * oude 'yes'). Met 'auto' beslist WordPress zelf; die tellen niet mee.
 *
 * @param array $rows Rijen met ->option_name, ->size, ->autoload.
 * @return array<int, object>
 * @since 3.2.0
 */
function sfp_ao_collect_large_forced( array $rows ) {
    $max   = sfp_ao_max_autoload_size();
    $groot = array();
    foreach ( $rows as $row ) {
        if ( (int) $row->size > $max && in_array( (string) $row->autoload, array( 'on', 'yes' ), true ) ) {
            $groot[] = $row;
        }
    }
    return $groot;
}

/**
 * De volledige analyse, gedeeld door de pagina en de REST-route, zodat
 * beide altijd dezelfde cijfers geven.
 *
 * @return array
 * @since 3.2.0
 */
function sfp_ao_analyse() {
    $active = sfp_ao_active_prefixes();
    $totals = sfp_ao_fetch_totals();

    $verrijk = function ( array $rows ) use ( $active ) {
        foreach ( $rows as $r ) {
            $r->size         = (int) $r->size;
            $r->source       = sfp_ao_detect_source( $r->option_name );
            $r->status       = sfp_ao_detect_status( $r->option_name, $active );
            $r->verwijderbaar = sfp_ao_is_deletable( $r->option_name, $r->status );
        }
        return $rows;
    };

    $rows = $verrijk( sfp_ao_fetch_rows() );

    // Restanten met autoload uit: alleen verweesd en onbekend.
    $off = $verrijk( sfp_ao_fetch_rows_off() );
    $uit = array_values( array_filter( $off, function ( $r ) {
        return in_array( $r->status, array( 'orphan', 'unknown' ), true );
    } ) );

    // v3.2.1: gegevens van eigen snippets, met autoload aan en uit.
    $eigen = array_values( array_filter( array_merge( $rows, $off ), function ( $r ) {
        return 'eigen' === $r->status;
    } ) );

    $tellingen = array(
        'all'       => count( $rows ),
        'large'     => 0,
        'orphan'    => 0,
        'unknown'   => 0,
        'transient' => 0,
        'uit'       => count( $uit ),
        'eigen'     => count( $eigen ),
    );
    foreach ( $rows as $r ) {
        if ( $r->size > 1024 ) {
            $tellingen['large']++;
        }
        if ( isset( $tellingen[ $r->status ] ) && ! in_array( $r->status, array( 'all', 'eigen' ), true ) ) {
            $tellingen[ $r->status ]++;
        }
    }

    return array(
        'totals'    => $totals,
        'rows'      => $rows,
        'uit'       => $uit,
        'eigen'     => $eigen,
        'groot'     => sfp_ao_collect_large_forced( $rows ),
        'tellingen' => $tellingen,
        'active'    => $active,
    );
}

/**
 * Zet een rij om naar de vorm van de REST-route.
 *
 * @param object $r Verrijkte rij.
 * @return array
 * @since 3.2.0
 */
function sfp_ao_row_export( $r ) {
    return array(
        'naam'          => (string) $r->option_name,
        'grootte'       => (int) $r->size,
        'autoload'      => (string) $r->autoload,
        'bron'          => (string) $r->source,
        'status'        => (string) $r->status,
        'verwijderbaar' => (bool) $r->verwijderbaar,
    );
}

/**
 * Back-up van opties: naam, autoload en de ruwe waarde zoals die in de
 * database staat, zodat terugzetten exact kan.
 *
 * @param string[] $names Option names.
 * @return array
 * @since 3.2.0
 */
function sfp_ao_export_data( array $names ) {
    global $wpdb;

    $names = array_values( array_unique( array_filter( array_map( 'strval', $names ), 'strlen' ) ) );
    $opties = array();
    if ( $names ) {
        $placeholders = implode( ', ', array_fill( 0, count( $names ), '%s' ) );
        $sql = $wpdb->prepare(
            "SELECT option_name, autoload, option_value
               FROM {$wpdb->options}
              WHERE option_name IN ( $placeholders )
              ORDER BY option_name ASC",
            $names
        );
        foreach ( (array) $wpdb->get_results( $sql ) as $row ) {
            $opties[] = array(
                'naam'     => (string) $row->option_name,
                'autoload' => (string) $row->autoload,
                'waarde'   => (string) $row->option_value,
            );
        }
    }
    $gevonden = array_column( $opties, 'naam' );

    return array(
        'site'        => home_url( '/' ),
        'gemaakt'     => wp_date( 'c' ),
        'versie'      => SFP_PAGE_CONFIG_VERSION,
        'aantal'      => count( $opties ),
        'niet_gevonden' => array_values( array_diff( $names, $gevonden ) ),
        'opties'      => $opties,
    );
}

/* =========================================================================
 * REST (alleen lezen)
 * ====================================================================== */

add_action( 'rest_api_init', 'sfp_ao_register_rest' );

/**
 * GET /sfp/v1/autoload         dezelfde tabel als de pagina.
 * GET /sfp/v1/autoload/export  back-up van opties (?namen=a,b of namen[]).
 *
 * Alleen voor beheerders. Er is geen route die iets verandert.
 *
 * @since 3.2.0
 */
function sfp_ao_register_rest() {
    $mag = function () {
        return current_user_can( 'manage_options' );
    };

    register_rest_route(
        'sfp/v1',
        '/autoload',
        array(
            'methods'             => 'GET',
            'permission_callback' => $mag,
            'callback'            => function () {
                return rest_ensure_response( sfp_ao_rest_data() );
            },
        )
    );

    register_rest_route(
        'sfp/v1',
        '/autoload/export',
        array(
            'methods'             => 'GET',
            'permission_callback' => $mag,
            'callback'            => function ( WP_REST_Request $verzoek ) {
                $namen = $verzoek->get_param( 'namen' );
                if ( is_string( $namen ) ) {
                    $namen = explode( ',', $namen );
                }
                $namen = is_array( $namen ) ? array_map( 'sanitize_text_field', $namen ) : array();
                return rest_ensure_response( sfp_ao_export_data( array_map( 'trim', $namen ) ) );
            },
        )
    );
}

/**
 * De gegevens van GET /sfp/v1/autoload.
 *
 * @return array
 * @since 3.2.0
 */
function sfp_ao_rest_data() {
    $a = sfp_ao_analyse();
    return array(
        'totaal'    => array(
            'aantal'  => (int) $a['totals']->total_count,
            'bytes'   => (int) $a['totals']->total_bytes,
            'kb'      => (int) round( $a['totals']->total_bytes / 1024 ),
            'drempel_site_health_bytes' => 800 * 1024,
        ),
        'tellingen' => $a['tellingen'],
        'grens_auto_off_bytes' => sfp_ao_max_autoload_size(),
        'groot_geforceerd' => array_map( 'sfp_ao_row_export', $a['groot'] ),
        'opties'    => array_map( 'sfp_ao_row_export', $a['rows'] ),
        'restanten_autoload_uit' => array_map( 'sfp_ao_row_export', $a['uit'] ),
        'eigen_gegevens' => array_map( 'sfp_ao_row_export', $a['eigen'] ),
    );
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

    // v3.2.0: verwijderen gaat altijd eerst langs een bevestigingsscherm met
    // de exacte lijst en de back-up. Hier wordt nog niets verwijderd.
    if ( 'delete' === $raw_action ) {
        set_transient( sfp_ao_confirm_key(), array_slice( array_values( array_unique( $names ) ), 0, 2000 ), HOUR_IN_SECONDS );
        wp_safe_redirect( add_query_arg( array( 'stap' => 'bevestig', 'filter' => $filter ), sfp_ao_page_url() ) );
        exit;
    }

    $changed = 0;
    $skipped = 0;

    global $wpdb;

    foreach ( $names as $name ) {
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
 * Sleutel van de lijst die op bevestiging wacht, per gebruiker.
 *
 * @return string
 * @since 3.2.0
 */
function sfp_ao_confirm_key() {
    return 'sfp_ao_bevestig_' . get_current_user_id();
}

/**
 * Bepaal per naam of hij na bevestiging verwijderd wordt, met de actuele
 * status. Namen die niet (meer) bestaan vallen af.
 *
 * @param string[] $names Option names.
 * @return array<int, object> Rijen met ->option_name, ->size, ->autoload, ->source, ->status, ->verwijderbaar.
 * @since 3.2.0
 */
function sfp_ao_confirm_rows( array $names ) {
    global $wpdb;

    $names = array_values( array_unique( array_filter( array_map( 'strval', $names ), 'strlen' ) ) );
    if ( ! $names ) {
        return array();
    }
    $placeholders = implode( ', ', array_fill( 0, count( $names ), '%s' ) );
    $sql = $wpdb->prepare(
        "SELECT option_name, LENGTH(option_value) AS size, autoload
           FROM {$wpdb->options}
          WHERE option_name IN ( $placeholders )
          ORDER BY option_name ASC",
        $names
    );
    $active = sfp_ao_active_prefixes();
    $rows   = (array) $wpdb->get_results( $sql );
    foreach ( $rows as $r ) {
        $r->size          = (int) $r->size;
        $r->source        = sfp_ao_detect_source( $r->option_name );
        $r->status        = sfp_ao_detect_status( $r->option_name, $active );
        $r->verwijderbaar = sfp_ao_is_deletable( $r->option_name, $r->status );
    }
    return $rows;
}

/**
 * Vingerafdruk van de lijst die bevestigd wordt. Verandert de lijst tussen
 * het tonen en het bevestigen, dan wordt er niets verwijderd.
 *
 * @param string[] $names Option names.
 * @return string
 * @since 3.2.0
 */
function sfp_ao_list_hash( array $names ) {
    $names = array_values( array_unique( array_map( 'strval', $names ) ) );
    sort( $names, SORT_STRING );
    return hash( 'sha256', implode( "\n", $names ) );
}

add_action( 'admin_post_sfp_ao_delete_bevestigd', 'sfp_ao_handle_delete_confirmed' );

/**
 * Verwijder de bevestigde lijst. Elke naam wordt opnieuw gecontroleerd:
 * beschermd, core, transient en actief blijven geblokkeerd.
 *
 * @since 3.2.0
 */
function sfp_ao_handle_delete_confirmed() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Geen toegang.', 'sfp-page-config' ), 403 );
    }
    check_admin_referer( 'sfp_ao_delete_bevestigd' );

    $names = get_transient( sfp_ao_confirm_key() );
    $names = is_array( $names ) ? $names : array();
    $rows  = sfp_ao_confirm_rows( $names );
    $lijst = array_map( function ( $r ) {
        return (string) $r->option_name;
    }, array_filter( $rows, function ( $r ) {
        return $r->verwijderbaar;
    } ) );

    $hash = isset( $_POST['sfp_ao_hash'] ) ? sanitize_text_field( wp_unslash( $_POST['sfp_ao_hash'] ) ) : '';
    if ( ! $lijst || ! hash_equals( sfp_ao_list_hash( $lijst ), $hash ) ) {
        wp_safe_redirect( add_query_arg( array( 'stap' => 'bevestig', 'sfp_ao_msg' => 'gewijzigd' ), sfp_ao_page_url() ) );
        exit;
    }

    $changed = 0;
    $skipped = count( $rows ) - count( $lijst );
    foreach ( $lijst as $name ) {
        if ( delete_option( $name ) ) {
            $changed++;
        } else {
            $skipped++;
        }
    }
    delete_transient( sfp_ao_confirm_key() );

    wp_safe_redirect(
        add_query_arg(
            array(
                'sfp_ao_msg'     => 'delete',
                'sfp_ao_changed' => $changed,
                'sfp_ao_skipped' => $skipped,
            ),
            sfp_ao_page_url()
        )
    );
    exit;
}

add_action( 'admin_post_sfp_ao_export', 'sfp_ao_handle_export' );

/**
 * Download de back-up van de lijst die op bevestiging wacht, als JSON.
 *
 * @since 3.2.0
 */
function sfp_ao_handle_export() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Geen toegang.', 'sfp-page-config' ), 403 );
    }
    check_admin_referer( 'sfp_ao_export' );

    $names = get_transient( sfp_ao_confirm_key() );
    $data  = sfp_ao_export_data( is_array( $names ) ? $names : array() );
    $host  = preg_replace( '/[^a-z0-9.-]/i', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
    $file  = 'opties-backup-' . $host . '-' . wp_date( 'Y-m-d-His' ) . '.json';

    nocache_headers();
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $file . '"' );
    echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    exit;
}

add_action( 'admin_post_sfp_ao_auto_off', 'sfp_ao_handle_auto_off' );

/**
 * Zet een grote optie die expliciet op autoload aan staat op 'auto-off',
 * net als WordPress zelf doet bij opslaan zonder expliciete keuze.
 *
 * @since 3.2.0
 */
function sfp_ao_handle_auto_off() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Geen toegang.', 'sfp-page-config' ), 403 );
    }
    check_admin_referer( 'sfp_ao_auto_off' );

    $name = isset( $_POST['sfp_ao_option'] ) ? sanitize_text_field( wp_unslash( $_POST['sfp_ao_option'] ) ) : '';
    $ok   = false;
    foreach ( sfp_ao_collect_large_forced( sfp_ao_fetch_rows() ) as $row ) {
        if ( $row->option_name !== $name ) {
            continue;
        }
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->options,
            array( 'autoload' => 'auto-off' ),
            array( 'option_name' => $name ),
            array( '%s' ),
            array( '%s' )
        );
        if ( false !== $result ) {
            wp_cache_delete( $name, 'options' );
            wp_cache_delete( 'alloptions', 'options' );
            $ok = true;
        }
    }

    wp_safe_redirect(
        add_query_arg(
            array(
                'sfp_ao_msg'     => 'auto_off',
                'sfp_ao_changed' => $ok ? 1 : 0,
                'sfp_ao_skipped' => $ok ? 0 : 1,
            ),
            sfp_ao_page_url()
        )
    );
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

    if ( isset( $_GET['stap'] ) && 'bevestig' === $_GET['stap'] ) {
        sfp_ao_render_confirm();
        return;
    }

    // v3.2.0: dezelfde analyse als GET /sfp/v1/autoload.
    $analyse = sfp_ao_analyse();
    $rows    = $analyse['rows'];
    $totals  = $analyse['totals'];
    $active  = $analyse['active'];
    $groot   = $analyse['groot'];

    $count_all       = $analyse['tellingen']['all'];
    $count_large     = $analyse['tellingen']['large'];
    $count_orphan    = $analyse['tellingen']['orphan'];
    $count_unknown   = $analyse['tellingen']['unknown'];
    $count_transient = $analyse['tellingen']['transient'];
    $count_uit       = $analyse['tellingen']['uit'];
    $count_eigen     = $analyse['tellingen']['eigen'];

    $filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : 'all';
    if ( ! in_array( $filter, array( 'all', 'large', 'orphan', 'unknown', 'transient', 'uit', 'eigen' ), true ) ) {
        $filter = 'all';
    }

    // Apply the filter.
    $filtered = in_array( $filter, array( 'uit', 'eigen' ), true ) ? $analyse[ $filter ] : array_values( array_filter( $rows, function ( $r ) use ( $filter ) {
        switch ( $filter ) {
            case 'large':     return $r->size > 1024;
            case 'orphan':    return 'orphan' === $r->status;
            case 'unknown':   return 'unknown' === $r->status;
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

    // Pre-compute the Safe Cleanup set so we can both display the
    // button count and render the preview block when requested.
    $safe_cleanup = sfp_ao_collect_safe_cleanup( $rows, $active );
    $safe_count   = count( $safe_cleanup['rows'] );
    $safe_bytes   = (int) $safe_cleanup['bytes'];
    $show_preview = isset( $_GET['safe_cleanup'] ) && 'preview' === $_GET['safe_cleanup'];

    // v3.2.0: Safe Cleanup loopt via hetzelfde bevestigingsscherm, met back-up.
    if ( $show_preview && $safe_count > 0 ) {
        set_transient( sfp_ao_confirm_key(), array_map( function ( $r ) {
            return (string) $r->option_name;
        }, $safe_cleanup['rows'] ), HOUR_IN_SECONDS );
        sfp_ao_render_confirm();
        return;
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
            <div class="sfp-ao-card">
                <span>Restanten, autoload uit</span>
                <strong><?php echo esc_html( number_format_i18n( $count_uit ) ); ?></strong>
            </div>
        </div>

        <?php if ( $groot ) : ?>
            <div class="notice notice-warning inline" style="margin: 16px 0; padding: 10px 14px;">
                <p style="margin: 0 0 8px;">
                    <strong>Grote opties die verplicht automatisch laden.</strong>
                    <?php
                    printf(
                        esc_html__( 'Deze opties zijn groter dan %s en staan expliciet op autoload aan. WordPress zelf laadt zo\'n optie niet automatisch als hij zonder expliciete keuze wordt opgeslagen (auto-off). De waarde blijft gelijk; alleen het automatisch laden stopt.', 'sfp-page-config' ),
                        esc_html( sfp_ao_format_size( sfp_ao_max_autoload_size() ) )
                    );
                    ?>
                </p>
                <ul style="margin: 0;">
                    <?php foreach ( $groot as $g ) : ?>
                        <li style="display: flex; gap: 12px; align-items: center; margin: 4px 0;">
                            <code><?php echo esc_html( $g->option_name ); ?></code>
                            <span><?php echo esc_html( sfp_ao_format_size( $g->size ) ); ?>, <?php echo esc_html( $g->source ); ?></span>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 0;">
                                <input type="hidden" name="action" value="sfp_ao_auto_off" />
                                <input type="hidden" name="sfp_ao_option" value="<?php echo esc_attr( $g->option_name ); ?>" />
                                <?php wp_nonce_field( 'sfp_ao_auto_off' ); ?>
                                <button type="submit" class="button button-small">Zoals WordPress: auto-off</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

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

        <?php if ( $safe_count > 0 ) : ?>
            <div class="sfp-ao-safe-cleanup" style="margin: 20px 0; padding: 14px 18px; background: #f6f7f7; border: 1px solid #c3c4c7; border-left: 4px solid #d63638; border-radius: 4px;">
                    <p style="margin: 0 0 10px;">
                        <strong>Safe Cleanup beschikbaar:</strong>
                        <?php
                        printf(
                            esc_html__( '%1$s verweesde opties (%2$s) van plugins die niet meer in de SFP-stack zitten kunnen met één druk op de knop verwijderd worden.', 'sfp-page-config' ),
                            '<strong>' . esc_html( number_format_i18n( $safe_count ) ) . '</strong>',
                            '<strong>' . esc_html( sfp_ao_format_size( $safe_bytes ) ) . '</strong>'
                        );
                        ?>
                    </p>
                    <p style="margin: 0; color: #646970; font-size: 13px;">
                        Bron:
                        <?php
                        $parts = array();
                        foreach ( $safe_cleanup['by_label'] as $label => $count ) {
                            $parts[] = esc_html( $label ) . ' (' . esc_html( number_format_i18n( $count ) ) . ')';
                        }
                        echo wp_kses_post( implode( ', ', $parts ) );
                        ?>.
                        Core-opties en actieve plugins worden automatisch overgeslagen.
                    </p>
                    <p style="margin: 10px 0 0;">
                        <a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'safe_cleanup', 'preview', sfp_ao_page_url() ) ); ?>">
                            Safe Cleanup voorbereiden
                        </a>
                    </p>
            </div>
        <?php endif; ?>

        <ul class="subsubsub sfp-ao-filters">
            <?php
            $tabs = array(
                'all'       => array( 'Alles',       $count_all ),
                'large'     => array( 'Groot (>1 KB)', $count_large ),
                'orphan'    => array( 'Verweesd',    $count_orphan ),
                'unknown'   => array( 'Onbekend',    $count_unknown ),
                'transient' => array( 'Transients',  $count_transient ),
                'uit'       => array( 'Restanten, autoload uit', $count_uit ),
                'eigen'     => array( 'Eigen gegevens', $count_eigen ),
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
                        $is_protected = ! $r->verwijderbaar;
                        $status_label = array(
                            'active'    => array( 'Actief',    'sfp-ao-status-active' ),
                            'orphan'    => array( 'Verweesd',  'sfp-ao-status-orphan' ),
                            'transient' => array( 'Transient', 'sfp-ao-status-transient' ),
                            'core'      => array( 'WP Core',   'sfp-ao-status-core' ),
                            'unknown'   => array( 'Onbekend',  'sfp-ao-status-unknown' ),
                            'eigen'     => array( 'Eigen',     'sfp-ao-status-eigen' ),
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
                                    <br><span class="sfp-ao-protected"><?php echo esc_html( in_array( $r->status, array( 'active', 'core', 'transient', 'eigen' ), true ) && ! in_array( $r->option_name, sfp_ao_protected_options(), true ) && ! sfp_ao_is_structural_widget_option( $r->option_name ) ? 'In gebruik (alleen autoload uitzetten kan)' : 'Beschermd (kan niet verwijderd worden)' ); ?></span>
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
            <strong>Verwijderen</strong> schrapt de rij uit <code>wp_options</code>. Dat kan voor opties met
            status Verweesd of Onbekend. Je ziet eerst de exacte lijst en kunt een back-up (JSON) downloaden;
            pas na de bevestiging gaat er iets weg. Opties met status Actief, WP Core of Transient en
            beschermde opties (<code>siteurl</code>, <code>active_plugins</code>, <code>widget_block</code>,
            enzovoort) worden altijd overgeslagen.
        </p>
        <p>
            <strong>Verweesd</strong> betekent: de bron is herkend en geen enkele plugin of thema die daarbij
            hoort is actief, ook als de plugin nog geinstalleerd staat. <strong>Onbekend</strong> betekent:
            de bron is niet herkend. Controleer die opties in de lijst voordat je bevestigt.
        </p>
        <p>
            <strong>Restanten, autoload uit</strong> toont verweesde en onbekende opties die niet automatisch
            laden. Ze kosten geen laadtijd, maar staan nog in de database.
        </p>
        <p>
            <strong>Eigen gegevens</strong> zijn opties van eigen snippets: de naam begint met een sitecode
            (<code>dps_</code>, <code>sws_</code>, <code>fl_</code> enzovoort) of staat in de code van een
            ASE-snippet. Ze zijn zichtbaar maar niet te verwijderen vanuit deze pagina.
        </p>
        <p>
            Dezelfde tabel staat alleen-lezen op <code>/wp-json/sfp/v1/autoload</code> (alleen voor beheerders).
        </p>
        <p>
            <strong>Safe Cleanup</strong> verwijdert in een keer alle opties die horen bij plugins die
            niet meer in de SFP-stack zitten: Yoast, Rank Math, Surfer, Elementor, MainWP,
            Better Search Replace, Custom Post Type UI, Google Site Kit en All in One SEO. De knop
            slaat automatisch alles over wat wel actief is, wat core-prefixed is
            (<code>{prefix}user_roles</code> enzovoort), en wat op de beschermde lijst staat.
            Bevestiging gaat via een aparte preview-knop zodat je eerst de exacte lijst ziet.
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
                    // Het volgende scherm toont de exacte lijst en de back-up.
                    return true;
                }

                return confirm('Autoload uitschakelen voor ' + boxes.length + ' optie(s)?');
            }
        </script>
    </div>
    <?php
}

/**
 * Het bevestigingsscherm voor verwijderen: de exacte lijst, wat geblokkeerd
 * blijft, de back-up als download en de knop om te bevestigen.
 *
 * @since 3.2.0
 */
function sfp_ao_render_confirm() {
    $names  = get_transient( sfp_ao_confirm_key() );
    $rows   = sfp_ao_confirm_rows( is_array( $names ) ? $names : array() );
    $weg    = array_values( array_filter( $rows, function ( $r ) {
        return $r->verwijderbaar;
    } ) );
    $blijft = array_values( array_filter( $rows, function ( $r ) {
        return ! $r->verwijderbaar;
    } ) );
    $bytes  = array_sum( array_map( function ( $r ) {
        return (int) $r->size;
    }, $weg ) );
    $onbekend = count( array_filter( $weg, function ( $r ) {
        return 'unknown' === $r->status;
    } ) );
    $hash   = sfp_ao_list_hash( array_map( function ( $r ) {
        return (string) $r->option_name;
    }, $weg ) );
    $notice = sfp_ao_read_notice();
    $labels = array(
        'active'    => 'Actief',
        'orphan'    => 'Verweesd',
        'transient' => 'Transient',
        'core'      => 'WP Core',
        'unknown'   => 'Onbekend',
        'eigen'     => 'Eigen',
    );
    ?>
    <div class="wrap sfp-ao-wrap">
        <h1>Verwijderen bevestigen</h1>
        <?php if ( $notice ) : ?>
            <div class="notice <?php echo esc_attr( $notice['class'] ); ?>"><p><?php echo wp_kses_post( $notice['message'] ); ?></p></div>
        <?php endif; ?>

        <?php if ( ! $weg ) : ?>
            <p>Er staat niets op de lijst dat verwijderd mag worden.</p>
        <?php else : ?>
            <p style="max-width: 820px;">
                <?php
                printf(
                    esc_html__( 'Deze %1$s opties (%2$s) worden definitief verwijderd. Dat kan niet ongedaan worden gemaakt, behalve met de back-up.', 'sfp-page-config' ),
                    '<strong>' . esc_html( number_format_i18n( count( $weg ) ) ) . '</strong>',
                    '<strong>' . esc_html( sfp_ao_format_size( $bytes ) ) . '</strong>'
                );
                if ( $onbekend > 0 ) {
                    echo ' ';
                    printf(
                        esc_html__( 'Bij %s daarvan is de bron niet herkend (Onbekend): controleer die namen.', 'sfp-page-config' ),
                        '<strong>' . esc_html( number_format_i18n( $onbekend ) ) . '</strong>'
                    );
                }
                ?>
            </p>
        <?php endif; ?>

        <?php foreach ( array( 'Wordt verwijderd' => $weg, 'Blijft staan (geblokkeerd)' => $blijft ) as $kop => $lijst ) : ?>
            <?php if ( ! $lijst ) { continue; } ?>
            <h2><?php echo esc_html( $kop ); ?> (<?php echo esc_html( number_format_i18n( count( $lijst ) ) ); ?>)</h2>
            <table class="wp-list-table widefat fixed striped" style="max-width: 1000px;">
                <thead><tr><th>Option name</th><th style="width: 90px;">Grootte</th><th style="width: 80px;">Autoload</th><th style="width: 200px;">Bron</th><th style="width: 100px;">Status</th></tr></thead>
                <tbody>
                    <?php foreach ( $lijst as $r ) : ?>
                        <tr>
                            <td class="sfp-ao-option-name"><?php echo esc_html( $r->option_name ); ?></td>
                            <td class="sfp-ao-size"><?php echo esc_html( sfp_ao_format_size( $r->size ) ); ?></td>
                            <td><code><?php echo esc_html( $r->autoload ); ?></code></td>
                            <td><?php echo esc_html( $r->source ); ?></td>
                            <td><?php echo esc_html( $labels[ $r->status ] ?? $r->status ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>

        <div style="margin-top: 20px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <?php if ( $weg ) : ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 0;">
                    <input type="hidden" name="action" value="sfp_ao_export" />
                    <?php wp_nonce_field( 'sfp_ao_export' ); ?>
                    <button type="submit" class="button">Back-up downloaden (JSON)</button>
                </form>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 0;">
                    <input type="hidden" name="action" value="sfp_ao_delete_bevestigd" />
                    <input type="hidden" name="sfp_ao_hash" value="<?php echo esc_attr( $hash ); ?>" />
                    <?php wp_nonce_field( 'sfp_ao_delete_bevestigd' ); ?>
                    <button type="submit" class="button button-primary" onclick="return confirm('Definitief verwijderen? Download eerst de back-up als je die wilt bewaren.');">
                        Bevestigen en verwijderen
                    </button>
                </form>
            <?php endif; ?>
            <a class="button" href="<?php echo esc_url( sfp_ao_page_url() ); ?>">Annuleren</a>
        </div>
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
        case 'auto_off':
            return array(
                'class'   => $changed > 0 ? 'notice-success' : 'notice-warning',
                'message' => $changed > 0
                    ? 'Autoload staat op <strong>auto-off</strong>, zoals WordPress dat zelf doet bij grote opties.'
                    : 'Niets veranderd: de optie stond niet (meer) verplicht op autoload aan.',
                'cache'   => $changed > 0,
            );
        case 'gewijzigd':
            return array(
                'class'   => 'notice-error',
                'message' => 'Niets verwijderd: de lijst is veranderd sinds je hem zag. Controleer de lijst hieronder opnieuw.',
                'cache'   => false,
            );
        case 'none':
            return array(
                'class'   => 'notice-warning',
                'message' => 'Geen opties geselecteerd.',
                'cache'   => false,
            );
        case 'safe_cleanup':
            $bytes = isset( $_GET['sfp_ao_bytes'] ) ? (int) $_GET['sfp_ao_bytes'] : 0;
            return array(
                'class'   => 'notice-success',
                'message' => sprintf(
                    'Safe Cleanup voltooid: <strong>%d</strong> optie(s) verwijderd, %s vrijgemaakt.%s',
                    $changed,
                    esc_html( sfp_ao_format_size( $bytes ) ),
                    $skipped > 0 ? sprintf( ' %d overgeslagen.', $skipped ) : ''
                ),
                'cache'   => $changed > 0,
            );
    }
    return null;
}
