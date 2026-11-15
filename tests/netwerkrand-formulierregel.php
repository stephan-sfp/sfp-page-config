<?php
/**
 * 3.71.0: accentrand op de netwerkkaarten, formulierregel ruimer en niet op contact.
 *
 * Gebruik: php tests/netwerkrand-formulierregel.php
 */
$r = dirname( __DIR__ );
$home = file_get_contents( "$r/includes/nlvorm-home.php" );
$css  = file_get_contents( "$r/assets/blokken/nlvorm-over.css" );
$fr   = file_get_contents( "$r/includes/formulierregel.php" );
$fout = 0;
$c = function ( $v, $w ) use ( &$fout ) { if ( ! $v ) { ++$fout; echo "FOUT: $w\n"; } };
$c( false !== strpos( $home, '<a class="sfp-kaart sfp-klikkaart sfp-netwerk__kaart"' ), 'netwerkkaart zonder vlak' );
$c( false === strpos( $home, 'sfp-kaart--vlak sfp-klikkaart sfp-netwerk__kaart' ), 'geen vlakke netwerkkaart' );
$c( false !== strpos( $css, 'border-top-color:var(--sfp-b-secundair)}' ), 'accentrand blijft bij hover' );
$c( false !== strpos( $fr, ':root .sfp-form-fallback.sfp-form-fallback{margin:24px 0 0;' ), 'formulierregel 24 px' );
$c( false !== strpos( $fr, "has_block( 'sfp/contactgegevens', get_queried_object_id() )" ), 'niet op contact' );
echo 'netwerkrand-formulierregel: ' . ( 5 - $fout ) . " goed, $fout fout\n";
exit( $fout ? 1 : 0 );
