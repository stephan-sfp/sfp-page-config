<?php
/**
 * Run with: php tests/tool-iconen.php
 *
 * Het eerste toolscherm (3.22.1) tekent dezelfde pictogrammen als de motor:
 * de lijst komt uit assets/tool/generator-bouw.js, met alleen SVG-vormen.
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
define( 'ABSPATH', __DIR__ . '/' );
define( 'SFP_PAGE_CONFIG_DIR', dirname( __DIR__ ) . '/' );
$GLOBALS['kses'] = null;
function wp_kses( $s, $toegestaan ) {
    $GLOBALS['kses'] = $toegestaan;
    return strip_tags( $s, '<' . implode( '><', array_keys( $toegestaan ) ) . '>' );
}
function add_action() {}
function add_filter() {}
$src   = file_get_contents( SFP_PAGE_CONFIG_DIR . 'includes/tool-eerste-scherm.php' );
$start = strpos( $src, 'function sfp_page_config_tool_iconen(' );
eval( substr( $src, $start, strpos( $src, "\n}\n", $start ) - $start + 2 ) );

$checks = 0;
function same( $e, $a, $m ) {
    global $checks;
    ++$checks;
    if ( $e !== $a ) {
        throw new RuntimeException( $m . ': expected ' . var_export( $e, true ) . ', got ' . var_export( $a, true ) );
    }
}

$js = file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/tool/generator-bouw.js' );
preg_match( '/var ICONEN = \{(.*?)\n\s*\};/s', $js, $blok );
preg_match_all( "/^\s*([a-z]+):/m", $blok[1], $m );
$iconen = sfp_page_config_tool_iconen();
same( $m[1], array_keys( $iconen ), 'zelfde namen als de motor' );
same( true, count( $iconen ) >= 10, 'minstens tien pictogrammen' );
foreach ( $iconen as $naam => $svg ) {
    same( 1, preg_match( '/^(<(path|circle)\b[^<>]*\/>)+$/', $svg ), 'alleen vormen in ' . $naam );
}
same( array( 'path', 'circle' ), array_keys( $GLOBALS['kses'] ), 'alleen path en circle toegestaan' );
same( true, false !== strpos( $src, '$iconen[ $o[\'icoon\'] ]' ), 'tegel gebruikt de lijst' );

echo "OK: {$checks} checks\n";
