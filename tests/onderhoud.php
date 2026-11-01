<?php
/**
 * Run with: php tests/onderhoud.php
 *
 * De onderhoudsroute uit de plugin (3.22.0): dezelfde route en parameters als
 * het snippet, geen dubbele route zolang het snippet actief is, en alleen de
 * AI-serviceaccounts met updaterechten.
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
error_reporting( E_ALL );
set_error_handler(
    function ( $severity, $message, $file, $line ) {
        throw new ErrorException( $message, 0, $severity, $file, $line );
    }
);
define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['routes'] = array();
$GLOBALS['user']   = null;
$GLOBALS['caps']   = array();
function add_action( $h, $cb, $p = 10 ) {
    $GLOBALS['acties'][ $h ][] = array( $cb, $p ); }
function register_rest_route( $ns, $route, $args ) {
    $GLOBALS['routes'][] = array( $ns, $route, $args ); }
function apply_filters( $h, $v ) {
    return $v; }
function wp_get_current_user() {
    return $GLOBALS['user']; }
function current_user_can( $c ) {
    return in_array( $c, $GLOBALS['caps'], true ); }
class WP_Error {
    public $code;
    public $data;
    function __construct( $c, $m, $d ) {
        $this->code = $c;
        $this->data = $d; }
}
class Gebruiker {
    public $user_login;
    function __construct( $l ) {
        $this->user_login = $l;
    } function exists() {
        return true; }
}

require dirname( __DIR__ ) . '/includes/onderhoud.php';

$checks = 0;
function same( $e, $a, $m ) {
    global $checks;
    ++$checks;
    if ( $e !== $a ) {
        throw new RuntimeException( $m . ': expected ' . var_export( $e, true ) . ', got ' . var_export( $a, true ) );
    }
}

same( array( 'sfp_page_config_onderhoud_routes', 20 ), $GLOBALS['acties']['rest_api_init'][0], 'route op rest_api_init na het snippet' );

sfp_page_config_onderhoud_routes();
same( 1, count( $GLOBALS['routes'] ), 'route geregistreerd zonder snippet' );
list( $ns, $route, $args ) = $GLOBALS['routes'][0];
same( 'sfp/v1', $ns, 'naamruimte' );
same( '/onderhoud', $route, 'pad' );
same( 'GET', $args[0]['methods'], 'GET' );
same( 'POST', $args[1]['methods'], 'POST' );
same( array( 'stage', 'limit', 'exclude' ), array_keys( $args[1]['args'] ), 'zelfde parameters' );
same( 5, $args[1]['args']['limit']['default'], 'limit standaard 5' );

// Met het oude snippet actief: geen tweede route.
eval( 'function sfp_onderhoud_v1_routes() {}' );
$GLOBALS['routes'] = array();
sfp_page_config_onderhoud_routes();
same( 0, count( $GLOBALS['routes'] ), 'geen dubbele route naast het snippet' );

// Rechten.
$GLOBALS['user'] = null;
same( 401, sfp_page_config_onderhoud_permission()->data['status'], 'niet ingelogd' );
$GLOBALS['user'] = new Gebruiker( 'stephan' );
$GLOBALS['caps'] = array( 'update_plugins', 'update_themes', 'update_languages' );
same( 403, sfp_page_config_onderhoud_permission()->data['status'], 'geen serviceaccount' );
$GLOBALS['user'] = new Gebruiker( 'claude-api' );
$GLOBALS['caps'] = array( 'update_plugins' );
same( 403, sfp_page_config_onderhoud_permission()->data['status'], 'te weinig rechten' );
$GLOBALS['caps'] = array( 'update_plugins', 'update_themes', 'update_languages' );
same( true, sfp_page_config_onderhoud_permission(), 'serviceaccount met rechten' );

// Onbekende stap.
$src = file_get_contents( dirname( __DIR__ ) . '/includes/onderhoud.php' );
same( 1, substr_count( $src, "'Onbekende stage.'" ), 'zelfde foutmelding als het snippet' );
same( 0, preg_match( '/sfp_onderhoud_v1_(?!routes)/', $src ), 'geen functienamen van het snippet' );

echo "OK: {$checks} checks\n";
