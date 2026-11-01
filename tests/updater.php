<?php
/** Run with: php tests/updater.php */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
error_reporting( E_ALL );
define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_PLUGIN_DIR', sys_get_temp_dir() . '/sfp-upd-plugins' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
$GLOBALS['tr']     = array();
$GLOBALS['http']  = null;
$GLOBALS['opt']   = array();
$GLOBALS['dl']    = '';
class WP_Error { public $c; public $m; function __construct( $c = '', $m = '' ) { $this->c = $c; $this->m = $m; } }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function add_filter() {} function add_action() {}
function plugin_basename( $f ) { return 'sfp-page-config/sfp-page-config.php'; }
function get_transient( $k ) { return $GLOBALS['tr'][ $k ] ?? false; }
function set_transient( $k, $v ) { $GLOBALS['tr'][ $k ] = $v; }
function delete_transient( $k ) { unset( $GLOBALS['tr'][ $k ] ); }
function get_option( $k, $d = false ) { return $GLOBALS['opt'][ $k ] ?? $d; }
function is_admin() { return false; }
function wp_remote_get() { return $GLOBALS['http']; }
function wp_remote_retrieve_response_code( $r ) { return $r['code']; }
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function wp_parse_url( $u, $c ) { return parse_url( $u, $c ); }
function download_url( $u ) { $f = tempnam( sys_get_temp_dir(), 'sfp' ); file_put_contents( $f, $GLOBALS['dl'] ); return $f; }
function wp_delete_file( $f ) { @unlink( $f ); }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function activate_plugin() {}
require dirname( __DIR__ ) . '/includes/updater.php';

$fout = 0; $n = 0;
function ok( $c, $m ) { global $fout, $n; $n++; if ( ! $c ) { $fout++; echo "FOUT: $m\n"; } }
function rel( $assets, $body ) {
    return array( 'code' => 200, 'body' => json_encode( array( 'tag_name' => 'v9.0.0', 'html_url' => 'x', 'body' => $body, 'assets' => $assets ) ) );
}
$pre = 'https://github.com/stephan-sfp/sfp-page-config/releases/download/v9.0.0/';
$inhoud = 'pakketinhoud';
$som = hash( 'sha256', $inhoud );
$u = new SFP_Page_Config_Updater( __FILE__, '3.22.0' );
$trans = function () { return (object) array( 'checked' => array( 'x' => 1 ), 'response' => array(), 'no_update' => array() ); };

// 1. Goede release.
$GLOBALS['http'] = rel( array( array( 'name' => 'bron.zip', 'browser_download_url' => $pre . 'bron.zip' ), array( 'name' => 'sfp-page-config.zip', 'browser_download_url' => $pre . 'sfp-page-config.zip' ) ), "Tekst\n\nSHA-256 sfp-page-config.zip: $som\n" );
$t = $u->check_update( $trans() );
ok( isset( $t->response['sfp-page-config/sfp-page-config.php'] ), 'goede release wordt aangeboden' );
ok( $pre . 'sfp-page-config.zip' === $t->response['sfp-page-config/sfp-page-config.php']->package, 'exacte bijlage, niet de eerste zip' );
$GLOBALS['dl'] = $inhoud;
$r = $u->pre_download( false, $pre . 'sfp-page-config.zip', null, array( 'plugin' => 'sfp-page-config/sfp-page-config.php' ) );
ok( is_string( $r ) && file_get_contents( $r ) === $inhoud, 'getal klopt: bestand terug' );
$GLOBALS['dl'] = 'vervalst';
$r = $u->pre_download( false, $pre . 'sfp-page-config.zip', null, array( 'plugin' => 'sfp-page-config/sfp-page-config.php' ) );
ok( is_wp_error( $r ), 'getal klopt niet: geweigerd' );
$r = $u->pre_download( false, 'https://evil.example/sfp-page-config.zip', null, array( 'plugin' => 'sfp-page-config/sfp-page-config.php' ) );
ok( is_wp_error( $r ), 'vreemde host geweigerd' );
$r = $u->pre_download( false, 'https://downloads.wordpress.org/plugin/x.zip', null, array( 'plugin' => 'x/x.php' ) );
ok( false === $r, 'andere plugin met rust gelaten' );

// 2. Zonder getal, zonder bijlage, alleen zipball: niets aangeboden.
foreach ( array(
    'zonder getal'    => rel( array( array( 'name' => 'sfp-page-config.zip', 'browser_download_url' => $pre . 'sfp-page-config.zip' ) ), 'geen getal' ),
    'zonder bijlage'  => rel( array(), "SHA-256 sfp-page-config.zip: $som" ),
    'andere repo'     => rel( array( array( 'name' => 'sfp-page-config.zip', 'browser_download_url' => 'https://github.com/iemand/sfp-page-config/releases/download/v9/sfp-page-config.zip' ) ), "SHA-256 sfp-page-config.zip: $som" ),
) as $naam => $http ) {
    $GLOBALS['tr'] = array(); $GLOBALS['http'] = $http;
    $t = $u->check_update( $trans() );
    ok( ! isset( $t->response['sfp-page-config/sfp-page-config.php'] ), "$naam: niet aangeboden" );
}

// 3. Fout van GitHub: 15 minuten onthouden.
$GLOBALS['tr'] = array(); $GLOBALS['http'] = array( 'code' => 403, 'body' => '' );
$u->check_update( $trans() );
ok( isset( $GLOBALS['tr']['sfp_page_config_github_release_fout'] ), 'fout onthouden' );
SFP_Page_Config_Updater::wis_cache();
ok( ! isset( $GLOBALS['tr']['sfp_page_config_github_release_fout'] ), 'wis_cache wist de fout' );

echo $fout ? "$fout fouten\n" : "OK: $n checks\n";
exit( $fout ? 1 : 0 );
