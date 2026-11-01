<?php
/**
 * Tests voor het onderdeel Formulierbewaking (3.24.0), met kleine WordPress-stubs.
 *
 * - de route /sfp/v1/logs: alleen geregistreerd als het snippet hem niet al
 *   heeft en het onderdeel aan staat; zelfde parameters, rechten en
 *   antwoordvorm als SFP Logendpoint v1.0 (versie, site, host, tijd in UTC,
 *   formulierfouten, vangnet, monitor; regels 1 tot 500);
 * - de testinzending: alleen bij het monitoradres SureContact en de
 *   foutenteller overslaan, opruimen en loggen (laatste 30);
 * - de foutmail: alleen bij een stijgende teller, hoogstens een per uur,
 *   onderwerp en tekst letterlijk gelijk aan het snippet;
 * - niets dubbel zolang de snippets draaien.
 *
 * Draaien: php tests/formulierbewaking.php
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
define( 'HOUR_IN_SECONDS', 3600 );

/* ---------- Stubs ---------- */

$GLOBALS['options']    = array();
$GLOBALS['transients'] = array();
$GLOBALS['routes']     = array();
$GLOBALS['mails']      = array();
$GLOBALS['ingelogd']   = true;
$GLOBALS['caps']       = array( 'update_plugins' => true );
$GLOBALS['onderdeel']  = true;
$GLOBALS['wp_filter']  = array();

class WP_Hook_Stub {
    public $callbacks = array();
}
function add_action( $hook, $cb, $prio = 10, $args = 1 ) {
    if ( ! isset( $GLOBALS['wp_filter'][ $hook ] ) ) {
        $GLOBALS['wp_filter'][ $hook ] = new WP_Hook_Stub();
    }
    $GLOBALS['wp_filter'][ $hook ]->callbacks[ $prio ][] = array( 'function' => $cb, 'accepted_args' => $args );
}
function remove_action( $hook, $cb, $prio = 10 ) {
    if ( empty( $GLOBALS['wp_filter'][ $hook ]->callbacks[ $prio ] ) ) {
        return false;
    }
    foreach ( $GLOBALS['wp_filter'][ $hook ]->callbacks[ $prio ] as $i => $c ) {
        if ( $c['function'] === $cb ) {
            unset( $GLOBALS['wp_filter'][ $hook ]->callbacks[ $prio ][ $i ] );
            return true;
        }
    }
    return false;
}
function apply_filters( $hook, $waarde ) {
    return $waarde;
}
function sfp_page_config_onderdeel_aan( $id ) {
    return 'formulierbewaking' === $id ? $GLOBALS['onderdeel'] : true;
}
function register_rest_route( $ns, $route, $args ) {
    $GLOBALS['routes'][ $ns . $route ][] = $args;
}
function is_user_logged_in() {
    return $GLOBALS['ingelogd'];
}
function current_user_can( $cap ) {
    return ! empty( $GLOBALS['caps'][ $cap ] );
}
function get_option( $k, $d = false ) {
    return array_key_exists( $k, $GLOBALS['options'] ) ? $GLOBALS['options'][ $k ] : $d;
}
function update_option( $k, $v, $autoload = null ) {
    $GLOBALS['options'][ $k ] = $v;
    return true;
}
function get_transient( $k ) {
    return $GLOBALS['transients'][ $k ] ?? false;
}
function set_transient( $k, $v, $ttl = 0 ) {
    $GLOBALS['transients'][ $k ] = $v;
    return true;
}
function get_bloginfo( $k ) {
    return 'De Presenteerschool';
}
function home_url() {
    return 'https://www.depresenteerschool.nl';
}
function admin_url( $p ) {
    return 'https://depresenteerschool.nl/wp-admin/' . $p;
}
function wp_parse_url( $url, $part ) {
    return parse_url( $url, $part );
}
function sanitize_key( $k ) {
    return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) );
}
function absint( $v ) {
    return abs( (int) $v );
}
function wp_json_encode( $v ) {
    return json_encode( $v );
}
function wp_date( $f ) {
    return gmdate( $f );
}
function wp_specialchars_decode( $s ) {
    return $s;
}
function rest_ensure_response( $x ) {
    return $x;
}
function wp_mail( $aan, $onderwerp, $tekst, $koppen ) {
    $GLOBALS['mails'][] = compact( 'aan', 'onderwerp', 'tekst', 'koppen' );
    return true;
}
class Verzoek_Stub {
    private $p;
    public function __construct( $p ) {
        $this->p = $p;
    }
    public function get_param( $k ) {
        return $this->p[ $k ] ?? null;
    }
}

/* SureForms en SureContact, zo klein mogelijk. */
// phpcs:disable
eval( 'namespace SRFM\Inc; class Client_Logger { public static $pad = ""; public static function is_enabled() { return true; } public static function get_failures() { return array( "submission" => array( "count" => 2 ) ); } public static function get_log_path( $x ) { return self::$pad; } public function reset_fault_streak() {} }' );
eval( 'namespace SRFM\Inc\Database\Tables; class Entries { public static $weg = array(); public static function delete( $id ) { self::$weg[] = $id; return true; } }' );
eval( 'namespace SureContact\Integrations; class SureForms_Integration { public function handle_form_submission() {} }' );
// phpcs:enable

require __DIR__ . '/../includes/formulierbewaking.php';

/* ---------- Hulp ---------- */

$fouten = 0;
$checks = 0;
function controleer( $voorwaarde, $wat ) {
    global $fouten, $checks;
    $checks++;
    if ( ! $voorwaarde ) {
        $fouten++;
        echo "FOUT: $wat\n";
    }
}

/* ---------- Route ---------- */

sfp_page_config_formulierbewaking_routes();
controleer( isset( $GLOBALS['routes']['sfp/v1/logs'] ), 'route geregistreerd zonder snippet' );
$r = $GLOBALS['routes']['sfp/v1/logs'][0];
controleer( 'GET' === $r['methods'], 'alleen GET' );
controleer( 'alles' === $r['args']['bron']['default'] && 50 === $r['args']['regels']['default'], 'standaard bron alles en 50 regels' );
controleer( 'absint' === $r['args']['regels']['sanitize_callback'], 'regels via absint' );
foreach ( array( 'alles', 'formulierfouten', 'vangnet', 'monitor' ) as $b ) {
    controleer( sfp_page_config_formulierbewaking_bron_geldig( $b ), "bron $b geldig" );
}
controleer( ! sfp_page_config_formulierbewaking_bron_geldig( 'wp_options' ), 'vrije bron ongeldig' );
controleer( true === sfp_page_config_formulierbewaking_mag(), 'claude-api mag' );
$GLOBALS['caps'] = array();
controleer( false === sfp_page_config_formulierbewaking_mag(), 'zonder update_plugins geen toegang' );
$GLOBALS['caps']     = array( 'update_plugins' => true );
$GLOBALS['ingelogd'] = false;
controleer( false === sfp_page_config_formulierbewaking_mag(), 'niet ingelogd geen toegang' );
$GLOBALS['ingelogd'] = true;

$GLOBALS['routes']    = array();
$GLOBALS['onderdeel'] = false;
sfp_page_config_formulierbewaking_routes();
controleer( empty( $GLOBALS['routes'] ), 'onderdeel uit: geen route' );
$GLOBALS['onderdeel'] = true;

/* Antwoordvorm. */
$GLOBALS['options']['sfp_srfm_nojs_log']   = array_fill( 0, 7, array( 'tijd' => 'x' ) );
$GLOBALS['options']['sfp_formmonitor_log'] = array_fill( 0, 3, array( 'tijd' => 'y' ) );
$pad                                       = tempnam( sys_get_temp_dir(), 'srfm' );
file_put_contents( $pad, "{\"a\":1}\nkapot\n{\"a\":3}\n" );
\SRFM\Inc\Client_Logger::$pad = $pad;

$uit = sfp_page_config_formulierbewaking_lezen( new Verzoek_Stub( array( 'bron' => 'alles', 'regels' => 50 ) ) );
controleer( array( 'versie', 'site', 'host', 'tijd', 'formulierfouten', 'vangnet', 'monitor' ) === array_keys( $uit ), 'sleutels in dezelfde volgorde als het snippet' );
controleer( '1.0' === $uit['versie'], 'versie 1.0' );
controleer( 'www.depresenteerschool.nl' === $uit['host'], 'host uit home_url' );
controleer( (bool) preg_match( '/^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d UTC$/', $uit['tijd'] ), 'tijd in UTC met achtervoegsel' );
controleer( array( 'logs_aan', 'tellers', 'bestand', 'omvang', 'regels', 'opmerking' ) === array_keys( $uit['formulierfouten'] ), 'formulierfouten met dezelfde sleutels' );
controleer( array( array( 'a' => 3 ), array( 'ruw' => 'kapot' ), array( 'a' => 1 ) ) === $uit['formulierfouten']['regels'], 'logregels nieuwste eerst, kapotte regel als ruw' );
controleer( 7 === $uit['vangnet']['totaal'] && 7 === count( $uit['vangnet']['regels'] ), 'vangnet blijft in de route' );
controleer( 3 === $uit['monitor']['totaal'], 'monitorlog' );

$uit = sfp_page_config_formulierbewaking_lezen( new Verzoek_Stub( array( 'bron' => 'vangnet', 'regels' => 0 ) ) );
controleer( ! isset( $uit['formulierfouten'] ) && ! isset( $uit['monitor'] ) && 1 === count( $uit['vangnet']['regels'] ), 'alleen vangnet, minimaal 1 regel' );
$uit = sfp_page_config_formulierbewaking_lezen( new Verzoek_Stub( array( 'bron' => 'monitor', 'regels' => 9999 ) ) );
controleer( 3 === count( $uit['monitor']['regels'] ), 'regels begrensd' );
\SRFM\Inc\Client_Logger::$pad = '';
$uit                          = sfp_page_config_formulierbewaking_lezen( new Verzoek_Stub( array( 'bron' => 'formulierfouten', 'regels' => 5 ) ) );
controleer( 'Nog geen logbestand aangemaakt.' === $uit['formulierfouten']['opmerking'], 'zonder logbestand dezelfde opmerking' );
unlink( $pad );

/* ---------- Testinzending ---------- */

$GLOBALS['wp_filter'] = array();
$crm                  = new \SureContact\Integrations\SureForms_Integration();
$teller               = new \SRFM\Inc\Client_Logger();
add_action( 'srfm_form_submit', array( $crm, 'handle_form_submission' ), 10 );
add_action( 'srfm_form_submit', array( $teller, 'reset_fault_streak' ), 10 );
add_action( 'srfm_form_submit', 'iets_anders', 10 );

$echt = array( 'entry_id' => 5, 'form_id' => 9, 'data' => array( 'email' => 'iemand@voorbeeld.nl' ) );
sfp_page_config_formulierbewaking_afschermen( $echt );
controleer( 3 === count( $GLOBALS['wp_filter']['srfm_form_submit']->callbacks[10] ), 'echte inzending: niets weggehaald' );
sfp_page_config_formulierbewaking_opruimen( $echt );
controleer( array() === \SRFM\Inc\Database\Tables\Entries::$weg, 'echte inzending blijft staan' );

$test = array( 'entry_id' => 77, 'form_id' => 9, 'data' => array( 'email' => 'FormMonitor@schoolforprofessionals.com' ) );
sfp_page_config_formulierbewaking_afschermen( $test );
$over = array_values( $GLOBALS['wp_filter']['srfm_form_submit']->callbacks[10] );
controleer( 1 === count( $over ) && 'iets_anders' === $over[0]['function'], 'monitor: SureContact en de foutenteller weg, de rest blijft' );
controleer( false !== strpos( $GLOBALS['sfp_bewaking_afgeschermd'], 'SureForms_Integration::handle_form_submission' ), 'afgeschermd vastgelegd' );
$GLOBALS['options']['sfp_formmonitor_log'] = array_fill( 0, 30, array( 'oud' => 1 ) );
sfp_page_config_formulierbewaking_opruimen( $test );
controleer( array( 77 ) === \SRFM\Inc\Database\Tables\Entries::$weg, 'testinzending verwijderd' );
$log = $GLOBALS['options']['sfp_formmonitor_log'];
controleer( 30 === count( $log ) && 77 === $log[0]['entry_id'] && 'ja' === $log[0]['opgeruimd'], 'log: nieuwste bovenaan, maximaal 30' );
controleer( array( 'tijd', 'entry_id', 'form_id', 'opgeruimd', 'afgeschermd' ) === array_keys( $log[0] ), 'logregel met dezelfde sleutels' );

/* ---------- Foutmail ---------- */

$GLOBALS['mails'] = array();
sfp_page_config_formulierbewaking_fout_melden( array( 'submission' => array( 'count' => 2 ) ), array( 'submission' => array( 'count' => 2 ) ) );
controleer( array() === $GLOBALS['mails'], 'gelijke teller: geen mail' );
sfp_page_config_formulierbewaking_fout_melden( array( 'submission' => array( 'count' => 2 ) ), array( 'submission' => array( 'count' => 3, 'form_title' => 'Contact', 'form_id' => 12 ) ) );
controleer( 1 === count( $GLOBALS['mails'] ), 'stijgende teller: een mail' );
$m = $GLOBALS['mails'][0];
controleer( 'info@depresenteerschool.nl' === $m['aan'], 'naar info@ van het domein zonder www' );
controleer( '[Formulierfout De Presenteerschool] bezoeker liep vast op een formulier' === $m['onderwerp'], 'onderwerp letterlijk gelijk' );
controleer( false !== strpos( $m['tekst'], 'Formulier: Contact (ID 12)' ) && false !== strpos( $m['tekst'], 'Totaal geteld in deze categorie: 3' ), 'tekst met formulier en telling' );
sfp_page_config_formulierbewaking_fout_melden( array(), array( 'notification' => array( 'count' => 1 ) ) );
controleer( 1 === count( $GLOBALS['mails'] ), 'binnen het uur geen tweede mail' );

/* ---------- Snippets draaien nog: niets dubbel ---------- */

// Pas hier bestaand (eval): een gewone functie zou PHP vooraf al kennen.
eval( 'function sfp_logs_v1_routes() {} function sfp_bewaking_monitor_afschermen() {}' ); // phpcs:ignore
$GLOBALS['routes'] = array();
sfp_page_config_formulierbewaking_routes();
controleer( empty( $GLOBALS['routes'] ), 'snippet actief: plugin registreert de route niet' );
\SRFM\Inc\Database\Tables\Entries::$weg = array();
sfp_page_config_formulierbewaking_opruimen( $test );
controleer( array() === \SRFM\Inc\Database\Tables\Entries::$weg, 'snippet actief: plugin ruimt niet op' );
$GLOBALS['transients'] = array();
$GLOBALS['mails']      = array();
sfp_page_config_formulierbewaking_fout_melden( array(), array( 'submission' => array( 'count' => 9 ) ) );
controleer( array() === $GLOBALS['mails'], 'snippet actief: plugin mailt niet' );
$stand = sfp_page_config_formulierbewaking_stand();
controleer( 'snippet' === $stand['route'] && 'snippet' === $stand['controle'], 'stand noemt het snippet' );

if ( $fouten ) {
    echo "$fouten van $checks controles mislukt\n";
    exit( 1 );
}
echo "OK: $checks checks\n";
