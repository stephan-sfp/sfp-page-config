<?php
/** Run with: php tests/ajax-lean.php */
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );
define( 'ABSPATH', __DIR__ . '/' );
$tmp = sys_get_temp_dir() . '/sfp-ajax-lean-' . getmypid();
define( 'WPMU_PLUGIN_DIR', $tmp . '/mu-plugins' );
define( 'SFP_PAGE_CONFIG_DIR', dirname( __DIR__ ) . '/' );
define( 'SFP_PAGE_CONFIG_VERSION', '2.10.0' );

$GLOBALS['options'] = array();
$GLOBALS['filters'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
function update_option( $k, $v ) { $GLOBALS['options'][ $k ] = $v; return true; }
function delete_option( $k ) { unset( $GLOBALS['options'][ $k ] ); return true; }
function add_filter( $h, $cb, $p = 10, $n = 1 ) { $GLOBALS['filters'][ $h ][] = $cb; }
function add_action( $h, $cb, $p = 10, $n = 1 ) { $GLOBALS['filters'][ $h ][] = $cb; }
function apply_filters( $h, $v ) { foreach ( $GLOBALS['filters'][ $h ] ?? array() as $cb ) { $v = $cb( $v ); } return $v; }
function trailingslashit( $p ) { return rtrim( $p, '/' ) . '/'; }
function wp_mkdir_p( $d ) { return is_dir( $d ) || mkdir( $d, 0777, true ); }
function wp_delete_file( $f ) { @unlink( $f ); }

$checks = 0;
function same( $expected, $actual, $message ) {
    global $checks;
    ++$checks;
    if ( $expected !== $actual ) {
        throw new RuntimeException( $message . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
    }
}

require dirname( __DIR__ ) . '/includes/ajax-lean.php';

// Sanitize: textarea input, invalid names dropped, protected folders dropped.
same( array( 'surerank', 'wp-rocket' ), sfp_ajax_lean_sanitize_skip( "surerank\nwp-rocket, latepoint\n../evil\nsfp-page-config\nsurerank" ), 'sanitize skip list' );

// Upgrade task removes the profiling option exactly once.
$GLOBALS['options']['sfp_prof_resultaten'] = array( 1 );
sfp_page_config_run_upgrades();
same( false, get_option( 'sfp_prof_resultaten' ), 'profiling option removed' );
same( '2.10.0', get_option( 'sfp_page_config_version' ), 'version recorded' );

// Sync: disabled means no file, enabled places it, disabling removes it.
sfp_ajax_lean_maybe_sync();
same( false, file_exists( sfp_ajax_lean_mu_target() ), 'no mu-plugin when disabled' );
$GLOBALS['options']['sfp_settings'] = array( 'ajax_lean_enabled' => '1', 'ajax_lean_skip' => sfp_ajax_lean_default_skip() );
sfp_ajax_lean_maybe_sync();
same( true, file_exists( sfp_ajax_lean_mu_target() ), 'mu-plugin placed when enabled' );
same( SFP_AJAX_LEAN_MU_VERSION, sfp_ajax_lean_installed_version(), 'installed version matches' );
@unlink( sfp_ajax_lean_mu_target() );
sfp_ajax_lean_maybe_sync();
same( true, file_exists( sfp_ajax_lean_mu_target() ), 'missing file is restored' );

// Mu-plugin: front-end LatePoint call skips listed plugins only.
$active = array( 'sfp-page-config/sfp-page-config.php', 'latepoint/latepoint.php', 'surerank/surerank.php', 'wp-rocket/wp-rocket.php', 'suretriggers/suretriggers.php', 'hello.php' );
define( 'DOING_AJAX', true );
$_REQUEST['action'] = 'latepoint_route_call';
$_SERVER['HTTP_REFERER'] = 'https://example.test/adviesgesprek-zelftest/';
require sfp_ajax_lean_mu_target();
same( array( 'sfp-page-config/sfp-page-config.php', 'latepoint/latepoint.php', 'suretriggers/suretriggers.php', 'hello.php' ), apply_filters( 'option_active_plugins', $active ), 'front-end call runs lean' );
same( 2, $GLOBALS['sfp_ajax_lean_skipped'], 'skipped count' );

// Back-office LatePoint call and other actions keep everything.
$_SERVER['HTTP_REFERER'] = 'https://example.test/wp-admin/admin.php?page=latepoint';
same( false, sfp_ajax_lean_is_target_request(), 'wp-admin LatePoint call untouched' );
$_SERVER['HTTP_REFERER'] = '';
$_REQUEST['action'] = 'heartbeat';
same( false, sfp_ajax_lean_is_target_request(), 'other ajax action untouched' );

// Disabling removes the mu-plugin; deactivation removes it too.
$GLOBALS['options']['sfp_settings']['ajax_lean_enabled'] = '';
sfp_ajax_lean_maybe_sync();
same( false, file_exists( sfp_ajax_lean_mu_target() ), 'mu-plugin removed when disabled' );
$GLOBALS['options']['sfp_settings']['ajax_lean_enabled'] = '1';
sfp_ajax_lean_maybe_sync();
sfp_ajax_lean_remove();
same( false, file_exists( sfp_ajax_lean_mu_target() ), 'mu-plugin removed on deactivation' );

@rmdir( WPMU_PLUGIN_DIR );
@rmdir( $tmp );
echo "OK: {$checks} checks\n";
