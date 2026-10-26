<?php
/**
 * Run with: php tests/css-inline.php
 *
 * Checks the inline CSS of the front end (3.12.0): every front-end stylesheet
 * goes through sfp_page_config_css_inline(), the output is minified and keeps
 * every selector, WP Rocket Remove Unused CSS leaves the inline styles alone,
 * no front-end file enqueues a stylesheet by URL, and review photos are asked
 * at 80 pixels.
 */
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );

define( 'ABSPATH', __DIR__ . '/' );
define( 'SFP_PAGE_CONFIG_DIR', dirname( __DIR__ ) . '/' );
define( 'SFP_PAGE_CONFIG_URL', 'https://example.test/wp-content/plugins/sfp-page-config/' );
define( 'SFP_PAGE_CONFIG_VERSION', 'test' );

$GLOBALS['filters'] = array();
$GLOBALS['styles']  = array();
function add_filter( $hook, $cb ) { $GLOBALS['filters'][ $hook ][] = $cb; }
function add_action( $hook, $cb ) { add_filter( $hook, $cb ); }
function apply_filters( $hook, $value ) {
    foreach ( $GLOBALS['filters'][ $hook ] ?? array() as $cb ) {
        $value = $cb( $value );
    }
    return $value;
}
function wp_style_is( $handle, $list ) {
    $s = $GLOBALS['styles'][ $handle ] ?? null;
    if ( 'registered' === $list ) {
        return null !== $s;
    }
    return null !== $s && ! empty( $s[ $list ] );
}
function wp_register_style( $handle, $src ) {
    $GLOBALS['styles'][ $handle ] = array( 'src' => $src, 'inline' => array() );
}
function wp_enqueue_style( $handle ) { $GLOBALS['styles'][ $handle ]['enqueued'] = true; }
function wp_add_inline_style( $handle, $css ) { $GLOBALS['styles'][ $handle ]['inline'][] = $css; }

require SFP_PAGE_CONFIG_DIR . 'includes/wp-rocket.php';

$checks = 0;
function same( $expected, $actual, $message ) {
    global $checks;
    ++$checks;
    if ( $expected !== $actual ) {
        throw new RuntimeException( $message . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
    }
}
function selectors( $css ) {
    $css = preg_replace( '#/\*.*?\*/#s', '', $css );
    $css = preg_replace( '/\{[^{}]*\}/', '{}', $css );
    preg_match_all( '/([.#][A-Za-z][\w-]+)/', $css, $m );
    $out = array_unique( $m[1] );
    sort( $out );
    return $out;
}

$bestanden = array(
    'sfp-reading-time'      => 'reading-time.css',
    'sfp-whatsapp'          => 'whatsapp.css',
    'sfp-reviews'           => 'reviews/reviews.css',
    'sfp-page-config-sales' => 'sales-page.css',
    'sfp-tool'              => 'tool/tool.css',
    'sfp-longread-nav'      => 'longread-nav.css',
    'sfp-zelftest'          => 'zelftest/zelftest.css',
);

// Every handle that goes inline is known to the exclusion list, and the other way round.
$handles = sfp_page_config_css_inline_handles();
sort( $handles );
$verwacht = array_keys( $bestanden );
sort( $verwacht );
same( $verwacht, $handles, 'inline handles' );

// Remove Unused CSS leaves every inline style alone, and keeps existing exclusions.
$uit = apply_filters( 'rocket_rucss_inline_atts_exclusions', array( 'iets-anders' ) );
same( 'iets-anders', $uit[0], 'existing exclusion kept' );
foreach ( $verwacht as $h ) {
    same( true, in_array( $h . '-inline-css', $uit, true ), 'RUCSS exclusion for ' . $h );
}
// No external exclusion any more: nothing loads as a file on the front end.
same( false, isset( $GLOBALS['filters']['rocket_rucss_external_exclusions'] ), 'no external exclusion' );

// Minified output keeps every selector and contains no comments or newlines.
foreach ( $bestanden as $handle => $bestand ) {
    $bron = file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/' . $bestand );
    $klein = sfp_page_config_css_bestand( $bestand );
    same( false, strpos( $klein, '/*' ), 'no comment in ' . $bestand );
    same( false, strpos( $klein, "\n" ), 'no newline in ' . $bestand );
    same( false, strpos( $klein, '</' ), 'no closing tag in ' . $bestand );
    same( selectors( $bron ), selectors( $klein ), 'selectors kept in ' . $bestand );
    same( substr_count( preg_replace( '#/\*.*?\*/#s', '', $bron ), '{' ), substr_count( $klein, '{' ), 'rules kept in ' . $bestand );
    same( substr_count( $klein, '{' ), substr_count( $klein, '}' ), 'balanced braces in ' . $bestand );
    same( 0, preg_match( '/url\(\s*[\'"]?(?!data:|https?:|#)/', $klein ), 'no relative url() in ' . $bestand );
    printf( "  %-24s %6d bytes source, %6d bytes inline\n", $bestand, strlen( $bron ), strlen( $klein ) );
}

// The helper registers a handle without a file, enqueues it once, and allows extra inline CSS.
sfp_page_config_css_inline( 'sfp-reviews', 'reviews/reviews.css' );
sfp_page_config_css_inline( 'sfp-reviews', 'reviews/reviews.css' );
same( false, $GLOBALS['styles']['sfp-reviews']['src'], 'no file for sfp-reviews' );
same( true, $GLOBALS['styles']['sfp-reviews']['enqueued'], 'sfp-reviews enqueued' );
same( 1, count( $GLOBALS['styles']['sfp-reviews']['inline'] ), 'CSS added once' );
wp_add_inline_style( 'sfp-reviews', ':root{--x:1}' );
same( 2, count( $GLOBALS['styles']['sfp-reviews']['inline'] ), 'extra inline CSS on the same handle' );

// No front-end file enqueues a stylesheet by URL. Admin and editor files may.
$toegestaan = array( 'beheer.php', 'editor.php', 'onderdeel-tooltips.php' );
foreach ( glob( SFP_PAGE_CONFIG_DIR . 'includes/*.php' ) as $pad ) {
    if ( in_array( basename( $pad ), $toegestaan, true ) ) {
        continue;
    }
    $src = file_get_contents( $pad );
    same( 0, preg_match( '/wp_(enqueue|register)_style\s*\([^;]*SFP_PAGE_CONFIG_URL/s', $src ), 'no stylesheet file in ' . basename( $pad ) );
}

// Review photos at 80 pixels.
$src = file_get_contents( SFP_PAGE_CONFIG_DIR . 'includes/onderdeel-reviews.php' );
$start = strpos( $src, 'function sfp_page_config_reviews_foto(' );
$eind  = strpos( $src, "\n}\n", $start );
eval( substr( $src, $start, $eind - $start + 2 ) );
same( 'https://lh3.googleusercontent.com/a/X=s80-c0x00000000-cc-rp-mo-ba2', sfp_page_config_reviews_foto( 'https://lh3.googleusercontent.com/a/X=s128-c0x00000000-cc-rp-mo-ba2' ), 'photo with suffix' );
same( 'https://lh3.googleusercontent.com/a-/Y=s80', sfp_page_config_reviews_foto( 'https://lh3.googleusercontent.com/a-/Y=s128' ), 'photo without suffix' );
same( 'https://example.com/foto=s128.png', sfp_page_config_reviews_foto( 'https://example.com/foto=s128.png' ), 'other host untouched' );
same( '', sfp_page_config_reviews_foto( '' ), 'empty stays empty' );
$renderer = file_get_contents( SFP_PAGE_CONFIG_DIR . 'includes/reviews/class-sfp-reviews-renderer.php' );
same( 0, substr_count( $renderer, "esc_url( \$r['photo'] )" ), 'renderer always resizes' );

echo "OK: {$checks} checks\n";
