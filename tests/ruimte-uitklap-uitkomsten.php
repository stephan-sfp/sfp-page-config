<?php
/**
 * Ruimte na een kop en onder uitkomstregels (sinds 3.78.0):
 * grote uitklap direct na een h2 krijgt 32 px; het nummer van een uitkomstregel
 * telt geen rijen mee (geen vaste span over negen rijen meer).
 *
 * Gebruik: php tests/ruimte-uitklap-uitkomsten.php
 */
$root = dirname( __DIR__ );
$ok   = 0;
$fout = 0;
function ru_check( $v, $wat ) {
	global $ok, $fout;
	if ( $v ) {
		++$ok;
	} else {
		++$fout;
		echo "FOUT: $wat\n";
	}
}
$uitklap = file_get_contents( $root . '/assets/blokken/uitklap.css' );
ru_check( false !== strpos( $uitklap, ':root .sfp-sectie__binnen>h2+.sfp-uitklap--groot{margin-top:32px}' ), 'grote uitklap na h2 krijgt 32 px' );
ru_check( false !== strpos( $uitklap, ':root .sfp-sectie__binnen>p:not(.is-style-sfp-kernzin)+.sfp-uitklap{margin-top:32px}' ), 'regel na alinea blijft' );
ru_check( false === strpos( $uitklap, 'h2+.sfp-uitklap{' ), 'geen kopregel voor alle varianten (programma, kaart, verhaal houden eigen ruimte)' );

$inhoud = file_get_contents( $root . '/assets/blokken/nlvorm-inhoud.css' );
preg_match( '/:root \.sfp-uitkomsten>\.sfp-stap::before\{[^}]*\}/', $inhoud, $voor );
$voor = $voor[0] ?? '';
ru_check( '' !== $voor, 'nummerregel gevonden' );
ru_check( false === strpos( $voor, 'span' ), 'nummer spant geen rijen' );
ru_check( false !== strpos( $voor, 'position:absolute' ), 'nummer staat los van de rijen' );
preg_match( '/:root \.sfp-uitkomsten>\.sfp-stap\{[^}]*\}/', $inhoud, $stap );
ru_check( false !== strpos( $stap[0] ?? '', 'position:relative' ), 'regel is het ankerpunt voor het nummer' );

echo "$ok goed, $fout fout\n";
exit( $fout ? 1 : 0 );
