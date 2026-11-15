<?php
/**
 * 3.73.0: anker per tab, de knop onderaan naar een tab, en de wie-regel
 * overal zoals in de slot. Het gedrag in de browser is getest met een
 * testpagina in headless Chrome (laden met anker, klik, hashchange, knop
 * onderaan, wachtlijststand); dit bestand bewaakt de code.
 *
 * Gebruik: php tests/tabs-anker.php
 */
$root = dirname( __DIR__ );
$ok   = 0;
$fout = 0;
function ta_check( $voorwaarde, $wat ) {
	global $ok, $fout;
	if ( $voorwaarde ) {
		++$ok;
	} else {
		++$fout;
		echo "FOUT: $wat\n";
	}
}
$php    = file_get_contents( $root . '/includes/blokken.php' );
$js     = file_get_contents( $root . '/assets/blokken/editor.js' );
$sticky = file_get_contents( $root . '/assets/sticky-cta.js' );
$basis  = file_get_contents( $root . '/assets/blokken/wie.css' );
$basis0 = file_get_contents( $root . '/assets/blokken/basis.css' );

// Blok en weergave.
ta_check( 1 === preg_match( "/'sfp\/tab'\s+=> array\(.*?'anker'\s+=> \\\$tekst,.*?'render'\s+=> 'sfp_page_config_render_tab'/s", $php ), 'sfp/tab heeft het attribuut anker' );
ta_check( false !== strpos( $php, "\$id       = '' !== \$anker ? \$anker : wp_unique_id( 'sfp-tab-' );" ), 'paneel krijgt het anker als id' );
ta_check( false !== strpos( $php, "sanitize_title( sfp_page_config_blok_attr( \$tab->attributes, 'anker' ) )" ), 'anker wordt opgeschoond' );

// Script: openen, in beeld brengen, alle wegen.
$a  = strpos( $php, "\$js = '(function(){'" );
$b  = strpos( $php, "})();';", $a ) + 8;
$js_site = '';
eval( substr( $php, $a, $b - $a ) ); // phpcs:ignore -- vaste code uit dit project.
$js_site = $js;
ta_check( false !== strpos( $js_site, 'closest("[role=tabpanel]")' ), 'script: anker binnen een tab' );
ta_check( false !== strpos( $js_site, 'x.click()' ), 'script: opent de tab via de tabknop' );
ta_check( false !== strpos( $js_site, 'addEventListener("hashchange"' ), 'script: hashchange' );
ta_check( false !== strpos( $js_site, 'addEventListener("load"' ), 'script: opnieuw in beeld na laden' );
ta_check( false !== strpos( $js_site, '},true);' ), 'script: klik in de vangfase (voor het thema)' );
ta_check( false !== strpos( $js_site, 'd.tagName==="DETAILS")d.open=true' ), 'script: uitklapper blijft werken' );
ta_check( false !== strpos( $js_site, '.site-header,#masthead,#ast-fixed-header,.ast-sticky-active' ), 'script: ruimte voor een vaste kopbalk' );

// Editor.
$js = file_get_contents( $root . '/assets/blokken/editor.js' );
ta_check( false !== strpos( $js, "paneel( 'Link naar deze tab'" ), 'editor: veld Anker in de zijbalk van de tab' );
ta_check( false !== strpos( $js, 'gebruikteAnkersBehalve( props.clientId )' ), 'editor: waarschuwing bij dubbel anker' );

// Knop onderaan.
ta_check( false !== strpos( $sticky, "el = tabPaneel(boek) || boek.closest(" ), 'knop: wachtlijststand naar de tab met de boekkaart' );
ta_check( false !== strpos( $sticky, "var waar = tabPaneel(formulier) ? formulier.closest('.sfp-tabs') : formulier;" ), 'knop: wijkt zolang de tabs in beeld zijn' );

// Wie-regel.
ta_check( false !== strpos( $basis, ':root .is-style-sfp-wie{display:grid;grid-template-columns:56px minmax(0,1fr)' ), 'wie: raster overal' );
ta_check( false !== strpos( $basis, '.is-style-sfp-wie>:is(img,picture){grid-row:1/span 2}' ), 'wie: portret ook in picture' );
ta_check( false !== strpos( $basis, '.is-style-sfp-wie br{display:none}' ), 'wie: rol onder de naam' );
ta_check( false === strpos( $basis0, 'is-style-sfp-wie' ), 'wie: niet meer in basis.css' );
ta_check( false !== strpos( $php, "array_unshift( \$bestanden, 'basis', 'wie' );" ), 'wie: laadt altijd met basis' );
ta_check( false !== strpos( $basis, '.sfp-kolommen--boeking .is-style-sfp-wie span{font-size:inherit}' ), 'wie: boekingskolom houdt 13 px' );

echo "tabs-anker: $ok goed, $fout fout\n";
exit( $fout ? 1 : 0 );
