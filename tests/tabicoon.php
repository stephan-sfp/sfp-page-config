<?php
/**
 * 3.50.1: icoon in tabs als kaart zonder cirkel, 96 px, telefoon verborgen.
 *
 * Gebruik: php tests/tabicoon.php
 */
$css  = file_get_contents( dirname( __DIR__ ) . '/assets/blokken/nlvorm-over.css' );
$fout = 0;
$ok   = 0;
foreach (
	array(
		'vak zonder cirkel'     => ':root .sfp-tabpaneel__icoon{width:140px;height:140px;display:grid;place-items:center}',
		'icoon 96 px'           => ':root .sfp-tabpaneel__icoon i{display:block;width:96px;height:96px;background:var(--sfp-b-secundair);',
		'telefoon verborgen'    => ':root .sfp-tabpaneel__icoon{display:none}',
	) as $wat => $regel
) {
	if ( false === strpos( $css, $regel ) ) {
		++$fout;
		echo "FOUT: $wat\n";
	} else {
		++$ok;
	}
}
if ( preg_match( '/\.sfp-tabpaneel__icoon\{[^}]*(border-radius|background)/', $css ) ) {
	++$fout;
	echo "FOUT: nog een vlak of ronde vorm op het vak\n";
} else {
	++$ok;
}
echo "tabicoon: $ok goed, $fout fout\n";
exit( $fout ? 1 : 0 );
