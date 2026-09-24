<?php
/** Run with: php tests/brand-regression.php [raw] */
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );
define( 'ABSPATH', __DIR__ . '/' );
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_dir_url( $file ) { return 'https://example.test/plugin/'; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function home_url( $path = '' ) { return 'https://' . $GLOBALS['host'] . $path; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
if ( ! in_array( 'raw', $argv ?? array(), true ) ) {
    function astra_get_option( $key ) { return $GLOBALS['options']['astra-settings'][ $key ] ?? ''; }
}

// Execute the actual production brand functions without unrelated WP hooks.
$source = file_get_contents( dirname( __DIR__ ) . '/sfp-page-config.php' );
$end = strpos( $source, '/* =========================================================================' , strpos( $source, 'function sfp_page_config_longread_color_keys' ) );
if ( false === $end ) { throw new RuntimeException( 'Brand section not found' ); }
eval( '?>' . substr( $source, 0, $end ) );
$checks = 0;
function same( $expected, $actual, $message ) {
    global $checks;
    ++$checks;
    if ( $expected !== $actual ) {
        throw new RuntimeException( $message . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
    }
}
function configure( $host, $heading = 'var(--ast-global-color-2)' ) {
    $GLOBALS['host'] = $host;
    $GLOBALS['options'] = array( 'astra-settings' => array(
        'heading-base-color' => $heading,
        'button-bg-color' => 'var(--ast-global-color-1)',
        'button-bg-h-color' => 'var(--ast-global-color-7)',
        'button-color' => 'var(--ast-global-color-5)',
        'headings-font-family' => "'Rubik', sans-serif",
        'headings-font-weight' => '900',
        'body-font-family' => 'Roboto, sans-serif',
    ) );
}

foreach ( array( 'schoolforprofessionals.com', 'depresenteerschool.nl', 'degespreksacademie.nl', 'centrumvoordidactiek.nl', 'deschrijftrainers.nl', 'stephanborggreve.com' ) as $host ) {
    configure( $host );
    $b = sfp_page_config_get_brand();
    same( 'var(--ast-global-color-2)', $b['primary'], $host . ' primary unchanged' );
    same( $b['primary'], $b['lr_sidebar_text'], $host . ' TOC primary' );
    same( $b['cta_bg'], $b['lr_sidebar_active'], $host . ' active item button colour' );
    same( $b['cta_bg'], $b['lr_bar_bg'], $host . ' bar button colour' );
    same( '900', $b['weight'], $host . ' heading weight' );
}
// Geen uitzondering per domein: elke site volgt zijn eigen Astra-koppenkleur.
// SwS en FL voeren daar nu de verkeerde rol; dat is een Astra-bevinding,
// geen pluginregel. Zie openstaand.md.
foreach ( array( 'speakwithsteve.com', 'www.SpeakWithSteve.com', 'falaliberada.com.br', 'www.falaliberada.com.br' ) as $host ) {
    configure( $host );
    $b = sfp_page_config_get_brand();
    same( 'var(--ast-global-color-2)', $b['primary'], $host . ' follows the Astra heading colour' );
    same( $b['primary'], $b['lr_sidebar_text'], $host . ' TOC primary' );
    same( $b['primary'], $b['lr_drawer_text'], $host . ' drawer primary' );
    same( $b['cta_bg'], $b['lr_sidebar_active'], $host . ' active item button colour' );
    same( 'var(--ast-global-color-1)', $b['lr_bar_bg'], $host . ' bar keeps button role' );
    same( 'color-mix(in srgb, var(--ast-global-color-2) 75%, transparent)', $b['lr_sidebar_h3'], $host . ' H3 derives from primary' );
}

// Geen enkel domein krijgt een andere uitkomst dan een ander domein met
// dezelfde Astra-instellingen. Dit is de regel die 2.9.1 bewaakt.
configure( 'schoolforprofessionals.com' );
$reference = sfp_page_config_get_brand();
foreach ( array( 'speakwithsteve.com', 'falaliberada.com.br', 'onbekend.example' ) as $host ) {
    configure( $host );
    same( $reference, sfp_page_config_get_brand(), $host . ' has no domain-specific branch' );
}

configure( 'depresenteerschool.nl' );
$GLOBALS['options']['astra-settings']['headings-font-weight'] = 900;
same( '900', sfp_page_config_get_brand()['weight'], 'Numeric Astra weight survives' );
$GLOBALS['options']['astra-settings']['button-bg-color'] = '#123456';
same( '#123456', sfp_page_config_get_brand()['lr_bar_bg'], 'Astra changes apply without release' );
$GLOBALS['options']['sfp_settings'] = array( 'lr_sidebar_text' => '#abcdef', 'lr_bar_bg' => 'red;}body{display:none' );
same( '#abcdef', sfp_page_config_get_brand()['lr_sidebar_text'], 'Explicit overrides preserved' );
same( '#123456', sfp_page_config_get_brand()['lr_bar_bg'], 'Unsafe override rejected' );
$GLOBALS['options']['astra-settings']['headings-font-weight'] = array( 'malformed' );
same( 'inherit', sfp_page_config_get_brand()['weight'], 'Malformed weight does not become 700' );
configure( 'other.example', '#345678' );
same( '#345678', sfp_page_config_get_brand()['lr_sidebar_text'], 'Unknown host keeps theme fallback' );
$GLOBALS['options'] = array();
same( 'currentColor', sfp_page_config_get_brand()['primary'], 'Missing theme colour stays valid' );
same( '', sfp_page_config_sanitize_css_color( '</style><script>alert(1)</script>' ), 'CSS breakout rejected' );
echo 'PASS: ' . $checks . " brand assertions\n";
