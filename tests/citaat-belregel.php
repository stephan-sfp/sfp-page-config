<?php
/**
 * 3.73.2: citaat in de foto met citaat kleiner; belregel onder een formulier
 * krijgt altijd zijn stijl mee (ook in een tab, waar WordPress de inhoud twee
 * keer tekent).
 *
 * Gebruik: php tests/citaat-belregel.php
 */
$root = dirname( __DIR__ );
$fout = 0;
$css  = file_get_contents( $root . '/assets/blokken/nlvorm-inschrijven.css' );
$php  = file_get_contents( $root . '/includes/formulierregel.php' );
$c    = array(
	'citaat 22/19 px'          => false !== strpos( $css, 'font-size:clamp(19px,.7vw + 11.9px,22px)' ),
	'oude citaatmaat weg'      => false === strpos( $css, 'clamp(20px,1.1vw + 8px,24px)' ),
	'kapitalen blijven'        => 1 === preg_match( '/sfp-founder__citaat p,:root \.sfp-founder__citaat-tekst\{[^}]*text-transform:uppercase/', $css ),
	'stijl altijd mee'         => false === strpos( $php, 'static $css' ),
	'founder hoeken gelijk'    => false === strpos( $css, 'var(--sfp-b-r-kaart) 56px' ),
	'boekingskaart sticky'     => false !== strpos( $css, ':root .sfp-kolommen.sfp-kolommen--boeking>.sfp-kolom:first-child{align-self:start;position:sticky;top:24px}' ),
	'body clip bij boeking'    => false !== strpos( $css, 'html:has(.sfp-kolommen--boeking),body:has(.sfp-kolommen--boeking){overflow-x:clip!important}' ),
	'stijl 24 px boven'        => false !== strpos( $php, ':root .sfp-form-fallback.sfp-form-fallback{margin:24px 0 0;' ),
);
foreach ( $c as $wat => $ok ) {
	if ( ! $ok ) {
		++$fout;
		echo "FOUT: $wat\n";
	}
}
echo 'citaat-belregel: ' . ( count( $c ) - $fout ) . " goed, $fout fout\n";
exit( $fout ? 1 : 0 );
