<?php
/**
 * 3.50.0: tabs in variantstand op volle breedte en links, drie dagen in het
 * programma naast elkaar, drie situaties naast elkaar. Gewone tabs en de
 * tabs als kaart blijven ongewijzigd.
 *
 * Gebruik: php tests/variant-tabs-situaties.php
 */
$root = dirname( __DIR__ );
$ok   = 0;
$fout = 0;
function vts_check( $voorwaarde, $wat ) {
	global $ok, $fout;
	if ( $voorwaarde ) {
		++$ok;
	} else {
		++$fout;
		echo "FOUT: $wat\n";
	}
}
$tabs   = file_get_contents( $root . '/assets/blokken/tabs.css' );
$inhoud = file_get_contents( $root . '/assets/blokken/nlvorm-inhoud.css' );
$php    = file_get_contents( $root . '/includes/blokken.php' );
$nl     = file_get_contents( $root . '/includes/nlvorm.php' );
$js     = file_get_contents( $root . '/assets/blokken/editor.js' );

// Tabs in variantstand.
vts_check( false !== strpos( $tabs, ':root .sfp-tabs--variant{max-width:none;margin:0}' ), 'variant: volle breedte' );
vts_check( false !== strpos( $tabs, ':root .sfp-tabs--variant .sfp-tablijst{justify-content:flex-start}' ), 'variant: tabs links' );
vts_check( false !== strpos( $tabs, ':root .sfp-tabs--variant .sfp-tabpaneel{max-width:none;margin:0;text-align:left;justify-items:stretch}' ), 'variant: paneel links en breed' );
// Gewone tabs ongewijzigd.
vts_check( false !== strpos( $tabs, ':root .sfp-tabs{max-width:900px;margin:0 auto}' ), 'gewone tabs: 900 px gecentreerd' );
vts_check( false !== strpos( $tabs, ':root .sfp-tabpaneel{padding:28px 0 0;display:grid;gap:14px;max-width:680px;margin:0 auto;text-align:center;justify-items:center}' ), 'gewone tabs: paneel gecentreerd' );
vts_check( 1 === preg_match_all( '/\.sfp-tablijst\{[^}]*justify-content:center/', $tabs ), 'gewone tabs: knoppen in het midden' );

// Programma met drie dagen.
$drie = '@media (min-width:701px){:root .sfp-kolommen--situaties:has(>:nth-child(3):last-child),:root .sfp-uitklap--programma.sfp-uitklap--drie{grid-template-columns:repeat(3,minmax(0,1fr))}}';
vts_check( false !== strpos( $inhoud, $drie ), 'programma en situaties: drie naast elkaar vanaf 701 px' );
vts_check( false !== strpos( $inhoud, ':root .sfp-uitklap--programma{grid-template-columns:minmax(0,1fr);gap:12px}' ), 'programma: telefoon onder elkaar' );
vts_check( false !== strpos( $php, "3 === \$aantal ? 'sfp-uitklap--drie'" ), 'render: klasse drie bij drie dagen' );
vts_check( false !== strpos( $php, '$naast  = 2 === $aantal || 3 === $aantal;' ), 'render: twee en drie naast elkaar' );
vts_check( false !== strpos( $php, 'if ( $naast && function_exists( \'sfp_page_config_nlvorm_programma_nodig\' ) )' ), 'render: samen open bij twee en drie' );
vts_check( false !== strpos( $nl, '.sfp-uitklap--programma:not(.sfp-uitklap--gestapeld)' ), 'script: drie dagen gaan samen open' );
vts_check( false !== strpos( $js, "3 === regels ? ' sfp-uitklap--drie'" ), 'editor: drie dagen naast elkaar' );
vts_check( false !== strpos( $js, "( a.variant ? ' sfp-tabs--variant' : '' )" ), 'editor: tabs in variantstand' );

// Situaties.
vts_check( false !== strpos( $inhoud, '@media (max-width:700px){:root .sfp-kolommen--situaties{grid-template-columns:minmax(0,1fr)}}' ), 'situaties: telefoon een kolom' );
// Drie-regel is specifieker dan de basisregel (twee kolommen) en botst niet met de telefoonregel.
vts_check( false !== strpos( $inhoud, ':root .sfp-uitklap--programma{margin:56px 0 0;max-width:none;display:grid;grid-template-columns:repeat(2,minmax(0,1fr))' ), 'programma: twee dagen blijven twee kolommen' );

echo "variant-tabs-situaties: $ok goed, $fout fout\n";
exit( $fout ? 1 : 0 );
