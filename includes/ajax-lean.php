<?php
/**
 * Ajax Lean: lichtere LatePoint-boekformulieraanroepen.
 *
 * Elke klik in een LatePoint-boekformulier is een admin-ajax-aanroep die de
 * hele site opstart, inclusief plugins die voor het boeken niets doen. Een
 * gewone plugin kan andere plugins niet tegenhouden, want die zijn dan al
 * geladen. Daarom plaatst deze module een klein mu-plugin
 * (mu-plugin/sfp-ajax-lean.php) dat bij die aanroepen de ingestelde plugins
 * overslaat.
 *
 * Het mu-plugin staat er alleen als de functie in de Instellingen-tab aan
 * staat, en wordt verwijderd bij uitzetten of bij deactiveren van SFP Page
 * Config. Het werkt alleen voor aanroepen van bezoekers; LatePoint in
 * wp-admin laadt altijd alles.
 *
 * Gemeten op DPS (2026-10-08): ruim 5000 PHP-bestanden per aanroep, ongeveer
 * 850 ms serverrespons, waarvan LatePoint zelf ongeveer 95 ms.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Versie van het meegeleverde mu-plugin; ophogen bij elke wijziging van dat bestand. */
define( 'SFP_AJAX_LEAN_MU_VERSION', '1.0.0' );

/**
 * Plugins die bij een boekformulieraanroep standaard worden overgeslagen.
 *
 * Bewust een lijst van wat weg mag, niet van wat moet blijven: een nieuwe
 * plugin laadt dus altijd, tot iemand hem hier of in de Instellingen-tab
 * toevoegt. Blijven altijd laden: LatePoint en add-ons, OttoKit, SureContact,
 * FluentSMTP, Stape, Sigmize, ASE Pro (snippets voor de boekbevestiging),
 * SiteGround Security en Speed Optimizer, en SFP Page Config zelf.
 *
 * @return string[] Pluginmappen.
 */
function sfp_ajax_lean_default_skip() {
    return array(
        'surerank',
        'surerank-pro',
        'ultimate-addons-for-gutenberg',
        'spectra-pro',
        'astra-addon',
        'complianz-gdpr-premium',
        'imagify',
        'presto-player',
        'presto-player-pro',
        'sureforms',
        'sureforms-pro',
        'wp-rocket',
        'cloudflare',
        'sfp-google-reviews',
        'sfp-tooltip',
    );
}

/**
 * Pluginmappen die nooit overgeslagen mogen worden.
 *
 * Moet gelijk lopen met sfp_ajax_lean_protected() in het mu-plugin.
 *
 * @return string[]
 */
function sfp_ajax_lean_protected_folders() {
    return array( 'sfp-page-config', 'latepoint', 'latepoint-pro-features', 'latepoint-google-calendar', 'latepoint-outlook-calendar' );
}

/**
 * Sanitize the skip list from the Instellingen tab.
 *
 * Accepts a textarea string (one folder per line, commas also allowed) or
 * an array. Keeps only valid folder names and drops protected folders.
 *
 * @param  mixed $raw Raw input.
 * @return string[]
 */
function sfp_ajax_lean_sanitize_skip( $raw ) {
    if ( is_array( $raw ) ) {
        $items = $raw;
    } else {
        $items = preg_split( '/[\s,]+/', (string) $raw );
    }
    $clean = array();
    foreach ( (array) $items as $item ) {
        $item = strtolower( trim( (string) $item ) );
        if ( '' === $item || ! preg_match( '/^[a-z0-9][a-z0-9._-]*$/', $item ) ) {
            continue;
        }
        if ( in_array( $item, sfp_ajax_lean_protected_folders(), true ) ) {
            continue;
        }
        $clean[ $item ] = $item;
    }
    return array_values( $clean );
}

/**
 * Full path of the installed mu-plugin.
 *
 * @return string
 */
function sfp_ajax_lean_mu_target() {
    return trailingslashit( WPMU_PLUGIN_DIR ) . 'sfp-ajax-lean.php';
}

/**
 * Read the version header of the installed mu-plugin.
 *
 * @return string Version, or '' when the file is missing or unreadable.
 */
function sfp_ajax_lean_installed_version() {
    $target = sfp_ajax_lean_mu_target();
    if ( ! is_readable( $target ) ) {
        return '';
    }
    $head = (string) file_get_contents( $target, false, null, 0, 1024 );
    return preg_match( '/^\s*\*\s*Version:\s*([0-9.]+)/mi', $head, $m ) ? $m[1] : '';
}

/**
 * Bring the mu-plugin in line with the setting.
 *
 * Enabled: copy the bundled file when it is missing or has another version.
 * Disabled: remove it. A failure is stored so the Instellingen tab can show
 * it; the site keeps working either way.
 */
function sfp_ajax_lean_sync() {
    $settings = get_option( 'sfp_settings', array() );
    $enabled  = is_array( $settings ) && ! empty( $settings['ajax_lean_enabled'] );
    $target   = sfp_ajax_lean_mu_target();

    if ( ! $enabled ) {
        if ( file_exists( $target ) ) {
            wp_delete_file( $target );
        }
        delete_option( 'sfp_ajax_lean_error' );
        return;
    }

    if ( SFP_AJAX_LEAN_MU_VERSION === sfp_ajax_lean_installed_version() ) {
        return;
    }

    $source = SFP_PAGE_CONFIG_DIR . 'mu-plugin/sfp-ajax-lean.php';
    if ( ! wp_mkdir_p( WPMU_PLUGIN_DIR ) || ! is_readable( $source ) || ! @copy( $source, $target ) ) {
        update_option( 'sfp_ajax_lean_error', 'Het mu-plugin kon niet in ' . WPMU_PLUGIN_DIR . ' geplaatst worden.', false );
        return;
    }
    delete_option( 'sfp_ajax_lean_error' );
}

/**
 * Sync once per plugin version or settings change, not on every request.
 *
 * The stored fingerprint combines the mu-plugin version and the enabled
 * flag, so a release with a new mu-plugin or a toggle in the Instellingen
 * tab triggers a sync, also after an automatic update without admin visit.
 */
function sfp_ajax_lean_maybe_sync() {
    $settings    = get_option( 'sfp_settings', array() );
    $enabled     = is_array( $settings ) && ! empty( $settings['ajax_lean_enabled'] ) ? '1' : '0';
    $fingerprint = SFP_AJAX_LEAN_MU_VERSION . '|' . $enabled;
    if ( get_option( 'sfp_ajax_lean_state' ) === $fingerprint && ( '0' === $enabled || file_exists( sfp_ajax_lean_mu_target() ) ) ) {
        return;
    }
    sfp_ajax_lean_sync();
    update_option( 'sfp_ajax_lean_state', $fingerprint, true );
}
add_action( 'init', 'sfp_ajax_lean_maybe_sync', 20 );

/**
 * Remove the mu-plugin when SFP Page Config is deactivated.
 */
function sfp_ajax_lean_remove() {
    $target = sfp_ajax_lean_mu_target();
    if ( file_exists( $target ) ) {
        wp_delete_file( $target );
    }
    delete_option( 'sfp_ajax_lean_state' );
}

/* =========================================================================
 * Eenmalige taken per pluginversie
 * ====================================================================== */

/**
 * Run upgrade tasks once after a new plugin version is installed.
 *
 * 2.10.0: remove the measurement results of the temporary ASE profiling
 * snippet used on DPS (2026-10-08). The option was never autoloaded.
 */
function sfp_page_config_run_upgrades() {
    if ( get_option( 'sfp_page_config_version' ) === SFP_PAGE_CONFIG_VERSION ) {
        return;
    }
    delete_option( 'sfp_prof_resultaten' );
    update_option( 'sfp_page_config_version', SFP_PAGE_CONFIG_VERSION, true );
}
add_action( 'init', 'sfp_page_config_run_upgrades', 5 );
