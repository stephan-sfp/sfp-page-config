<?php
/**
 * Wachtlijstkaart toont met cursusvarianten de prijs van de standaardvariant (3.70.1).
 *
 * Gebruik: php tests/wachtlijst-prijs.php
 */
$s = file_get_contents( dirname( __DIR__ ) . '/includes/nlvorm.php' );
$deel = substr( $s, strpos( $s, 'if ( ! $groepen ) {' ), 2600 );
$fout = 0;
foreach ( array(
	'sfp_page_config_cursus_varianten( $post_id )' => 'leest de varianten',
	"sfp_page_config_cursus_prijs( \$v['info'], \$v['standaard'] )" => 'prijs van de standaardvariant',
	"sfp_page_config_variant_naam( \$v['standaard'] )" => 'noemt het aantal dagen',
) as $naald => $wat ) {
	if ( false === strpos( $deel, $naald ) ) { ++$fout; echo "FOUT: $wat\n"; }
}
$v = file_get_contents( dirname( __DIR__ ) . '/includes/nlvorm.php' );
if ( false === strpos( $v, "in_array( 2, \$sleutels, true ) ? 2" ) ) { ++$fout; echo "FOUT: standaard zonder groep is 2 dagen\n"; }
echo "wachtlijst-prijs: " . ( 4 - $fout ) . " goed, $fout fout\n";
exit( $fout ? 1 : 0 );
