<?php
/** Run with: php tests/zelftest-husselen.php (schakelaar Husselen, 3.13.3) */
error_reporting( E_ALL );
set_error_handler(
    function ( $severity, $message, $file, $line ) {
        throw new ErrorException( $message, 0, $severity, $file, $line );
    }
);
define( 'ABSPATH', __DIR__ . '/' );
define( 'SFP_PAGE_CONFIG_DIR', dirname( __DIR__ ) . '/' );
define( 'SFP_PAGE_CONFIG_URL', 'https://example.test/wp-content/plugins/sfp-page-config/' );
define( 'SFP_PAGE_CONFIG_VERSION', 'test' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'ENT_HTML5_TEST', 1 );

/* ---------- Kleine WordPress-stubs ---------- */

$GLOBALS['options']    = array();
$GLOBALS['transients'] = array();
$GLOBALS['posts']      = array();
$GLOBALS['meta']       = array();
$GLOBALS['locale']     = 'nl_NL';
$GLOBALS['admin']      = false;

function get_option( $k, $d = false ) {
    return $GLOBALS['options'][ $k ] ?? $d;
}
function update_option( $k, $v ) {
    $GLOBALS['options'][ $k ] = $v;
    return true;
}
function delete_option( $k ) {
    unset( $GLOBALS['options'][ $k ] );
    return true;
}
function get_transient( $k ) {
    return $GLOBALS['transients'][ $k ] ?? false;
}
function set_transient( $k, $v, $t = 0 ) {
    $GLOBALS['transients'][ $k ] = $v;
    return true;
}
function delete_transient( $k ) {
    unset( $GLOBALS['transients'][ $k ] );
    return true;
}
function add_action() {}
function add_filter() {}
function has_action() {
    return false;
}
function do_action() {}
function apply_filters( $hook, $value ) {
    return $value;
}
function is_admin() {
    return $GLOBALS['admin'];
}
function get_locale() {
    return $GLOBALS['locale'];
}
function determine_locale() {
    return $GLOBALS['locale'];
}
function home_url( $path = '' ) {
    return 'https://example.test' . $path;
}
function wp_unslash( $v ) {
    return is_array( $v ) ? array_map( 'wp_unslash', $v ) : ( is_string( $v ) ? stripslashes( $v ) : $v );
}
function wp_strip_all_tags( $s ) {
    return trim( strip_tags( (string) $s ) );
}
function sanitize_text_field( $s ) {
    return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $s ) ) );
}
function sanitize_textarea_field( $s ) {
    return trim( strip_tags( (string) $s ) );
}
function sanitize_key( $s ) {
    return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) );
}
function sanitize_email( $s ) {
    return filter_var( trim( (string) $s ), FILTER_VALIDATE_EMAIL ) ? trim( (string) $s ) : '';
}
function is_email( $s ) {
    return (bool) filter_var( (string) $s, FILTER_VALIDATE_EMAIL );
}
function absint( $n ) {
    return abs( (int) $n );
}
function esc_url_raw( $u ) {
    $u = trim( (string) $u );
    return preg_match( '#^(https?:)?//|^/|^\#|^mailto:|^tel:#i', $u ) ? $u : '';
}
function esc_url( $u ) {
    return esc_url_raw( $u );
}
function esc_html( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function wp_json_encode( $v, $flags = 0 ) {
    return json_encode( $v, $flags );
}
function wp_parse_url( $url, $component = -1 ) {
    return parse_url( $url, $component );
}
function wp_date( $format, $ts = null ) {
    return gmdate( $format, null === $ts ? time() : $ts );
}
function current_time( $type ) {
    return 'timestamp' === $type ? time() : gmdate( 'Y-m-d H:i:s' );
}
function date_i18n( $format, $ts = false ) {
    return gmdate( $format, false === $ts ? time() : $ts );
}
function get_post_status( $id ) {
    return $GLOBALS['posts'][ $id ]['status'] ?? false;
}
function get_post_type( $id ) {
    return $GLOBALS['posts'][ $id ]['type'] ?? false;
}
function get_permalink( $id ) {
    return isset( $GLOBALS['posts'][ $id ] ) ? home_url( '/' . $GLOBALS['posts'][ $id ]['slug'] . '/' ) : false;
}
function get_the_title( $id ) {
    return $GLOBALS['posts'][ $id ]['titel'] ?? '';
}
function url_to_postid( $url ) {
    foreach ( $GLOBALS['posts'] as $id => $p ) {
        if ( rtrim( $url, '/' ) === rtrim( home_url( '/' . $p['slug'] . '/' ), '/' ) || rtrim( $url, '/' ) === '/' . $p['slug'] ) {
            return $id;
        }
    }
    return 0;
}
function wp_get_sidebars_widgets() {
    return $GLOBALS['options']['sidebars_widgets'] ?? array();
}
function get_post_meta( $id, $key = '', $single = false ) {
    return $GLOBALS['meta'][ $id ][ $key ] ?? '';
}
function update_post_meta( $id, $key, $value ) {
    $GLOBALS['meta'][ $id ][ $key ] = is_string( $value ) ? stripslashes( $value ) : $value;
    return true;
}
function delete_post_meta( $id, $key ) {
    unset( $GLOBALS['meta'][ $id ][ $key ] );
    return true;
}
function wp_slash( $v ) {
    return is_string( $v ) ? addslashes( $v ) : $v;
}
function get_post( $id ) {
    return null;
}
function sfp_page_config_sanitize_time( $t ) {
    return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', (string) $t ) ? (string) $t : '';
}
function sfp_page_config_sanitize_css_color( $c ) {
    return (string) $c;
}
function sfp_page_config_format_date_nl( $date, $format = 'short' ) {
    return 'nl:' . $date;
}

$checks = 0;
function same( $expected, $actual, $message ) {
    global $checks;
    ++$checks;
    if ( $expected !== $actual ) {
        throw new RuntimeException( $message . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
    }
}
if ( ! function_exists( 'wp_attachment_is_image' ) ) {
    function wp_attachment_is_image( $id ) {
        return false;
    }
}
if ( ! function_exists( 'get_post_mime_type' ) ) {
    function get_post_mime_type( $id ) {
        return '';
    }
}
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {}
}
require dirname( __DIR__ ) . '/includes/instellingen.php';
require dirname( __DIR__ ) . '/includes/teksten.php';
require dirname( __DIR__ ) . '/includes/zelftest.php';
require dirname( __DIR__ ) . '/includes/zelftest-weergave.php';

$GLOBALS['checks'] = 0;
/* Een pakket met de opbouw van DPS: 40 vragen, 39 met meerdere antwoorden. */
$vragen = array();
for ( $n = 1; $n <= 40; $n++ ) {
    $vragen[] = array( 'nr' => $n, 'blok' => 'A', 'meerdere' => 39 === $n ? true : null );
}
$lang   = range( 1, 40 );
shuffle( $lang ); // Volgorde in het manifest doet er niet toe.
$pakket = array(
    'manifest' => array(
        'lengtes'  => array( 'long' => array( 'vragen' => $lang ), 'short' => array( 'vragen' => array( 3, 4, 5, 7, 9, 15, 16, 18, 20, 30, 31, 33, 10, 12, 38, 39 ) ) ),
        'weergave' => array( 'volgorde_vragen' => 'vast', 'volgorde_opties' => 'vast', 'blokkop' => 'ja' ),
    ),
    'inhoud'   => array( 'vragen' => $vragen ),
);

/* Standaard: aan, ook als het pakket vast zegt. */
$w = sfp_zelftest_weergave( $pakket, 'dps' );
same( 'willekeurig', $w['vragen'], 'standaard: vragen willekeurig' );
same( 'willekeurig', $w['opties'], 'standaard: opties willekeurig' );
same( false, $w['blokkop'], 'standaard: geen bloknaam' );
same( range( 6, 35 ), $w['kern'], 'kern: lange versie zonder eerste en laatste vijf' );
$verwacht = array_merge( array( 4, 5 ), range( 6, 35 ) );
same( $verwacht, $w['opties_bij'], 'opties: profielvragen 4 en 5 plus de kern' );
same( '1', sfp_zelftest_instellingen( 'dps' )['husselen'], 'instelling standaard 1' );
same( array(), sfp_page_config_afwijkingen_zelftest_test(), 'aan: geen afwijking' );

/* Uit: vaste volgorde met bloknaam. */
sfp_zelftest_instellingen_bewaar( 'dps', array( 'husselen' => false ) );
same( '0', sfp_zelftest_instellingen( 'dps' )['husselen'], 'uit bewaard' );
same( '1', sfp_zelftest_instellingen( 'dps' )['proefmodus'], 'proefmodus ongemoeid' );
$w = sfp_zelftest_weergave( $pakket, 'dps' );
same( array( 'vast', 'vast', true, array(), array() ), array( $w['vragen'], $w['opties'], $w['blokkop'], $w['kern'], $w['opties_bij'] ), 'uit: vast met bloknaam' );
$afw = sfp_page_config_afwijkingen_zelftest_test();
same( 1, count( $afw ), 'uit: een afwijking in Overzicht' );
same( 'zelftest.dps.husselen', $afw[0]['pad'], 'pad van de afwijking' );
same( true, sfp_page_config_instelling_naar_norm( 'zelftest.dps.husselen' ), 'terug naar de norm' );
same( '1', sfp_zelftest_instellingen( 'dps' )['husselen'], 'weer aan na terug naar de norm' );
same( false, sfp_page_config_instelling_naar_norm( 'zelftest.../x.husselen' ), 'onzin-pad geweigerd' );

/* Waarden 'true' en '1' van het formulier. */
sfp_zelftest_instellingen_bewaar( 'sws', array( 'husselen' => 'true' ) );
same( '1', sfp_zelftest_instellingen( 'sws' )['husselen'], "'true' is aan" );
sfp_zelftest_instellingen_bewaar( 'sws', array( 'husselen' => 'nee' ) );
same( '0', sfp_zelftest_instellingen( 'sws' )['husselen'], 'iets anders is uit' );

/* Pakket met eigen lijsten. */
$pakket['manifest']['weergave']['kern']   = array( 10, 11, 12 );
$pakket['manifest']['weergave']['opties'] = array( 1, 39, 10 );
$w = sfp_zelftest_weergave( $pakket, 'eigen' );
same( array( 10, 11, 12 ), $w['kern'], 'kern uit het pakket' );
same( array( 1, 10 ), $w['opties_bij'], 'opties uit het pakket, zonder meerdere' );

/* Zonder testnaam (oude aanroep): aan. */
same( 'willekeurig', sfp_zelftest_weergave( $pakket )['vragen'], 'zonder testnaam: aan' );

echo 'OK: ' . $GLOBALS['checks'] . " checks\n";

/**
 * Alleen de afwijkingen van de zelftest.
 *
 * @return array
 */
function sfp_page_config_afwijkingen_zelftest_test() {
    return array_values(
        array_filter(
            sfp_page_config_afwijkingen(),
            function ( $a ) {
                return 0 === strpos( $a['pad'], 'zelftest.' );
            }
        )
    );
}
