<?php
/**
 * Test voor het kleurschema van het deelbeeld (3.61.0).
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'SFP_PAGE_CONFIG_DIR', dirname( __DIR__ ) . '/' );
$fout = 0;
function same( $a, $b, $m ) {
    global $fout;
    if ( $a !== $b ) {
        ++$fout;
        echo "FOUT: $m\n  verwacht: " . var_export( $a, true ) . "\n  kreeg:    " . var_export( $b, true ) . "\n";
    }
}
$src = file_get_contents( SFP_PAGE_CONFIG_DIR . 'includes/deelbeeld.php' );
preg_match( '/function sfp_page_config_deelbeeld_schema\(.*?\n}\n/s', $src, $m );
eval( $m[0] );
$k = array( 'primair' => '#2E2864', 'secundair' => '#00B0E3', 'wit' => '#FFFFFF' );
$d = sfp_page_config_deelbeeld_schema( $k, '#2E2864' );
same( false, $d['licht'], 'Donker: niet licht' );
same( '#FFFFFF', $d['voor'], 'Donker: tekst wit' );
same( '#00B0E3', $d['accent'], 'Donker: accent secundair' );
same( '#282358', $d['baan'], 'Donker: baan 12% donkerder' );
$f = sfp_page_config_deelbeeld_schema( array( 'primair' => '#000000', 'secundair' => '#FEDD03', 'wit' => '#FFFFFF' ), '#FEDD03' );
same( true, $f['licht'], 'Geel: licht' );
same( '#000000', $f['voor'], 'Geel: tekst primair' );
same( '#000000', $f['accent'], 'Geel: accent primair' );
same( '#fee74f', $f['baan'], 'Geel: baan 30% lichter' );
echo $fout ? "FOUT: $fout checks\n" : "OK deelbeeld-schema\n";
exit( $fout ? 1 : 0 );
