<?php
/**
 * Tabicoon in tabs als kaart.
 * 3.50.1: zonder cirkel, 96 px, telefoon verborgen.
 * 3.51.0: afbeelding uit de media in eigen kleuren (img, lege alt), icoon uit
 * de vaste set blijft silhouet in secundair; in de editor ook in het canvas.
 *
 * Gebruik: php tests/tabicoon.php
 */
$root = dirname( __DIR__ );
$css  = file_get_contents( $root . '/assets/blokken/nlvorm-over.css' );
$php  = file_get_contents( $root . '/includes/blokken.php' );
$js   = file_get_contents( $root . '/assets/blokken/editor.js' );
$fout = 0;
$ok   = 0;
function ti_check( $voorwaarde, $wat ) {
	global $ok, $fout;
	if ( $voorwaarde ) {
		++$ok;
	} else {
		++$fout;
		echo "FOUT: $wat\n";
	}
}
ti_check( false !== strpos( $css, ':root .sfp-tabpaneel__icoon{width:140px;height:140px;display:grid;place-items:center}' ), 'vak zonder cirkel' );
ti_check( false !== strpos( $css, ':root .sfp-tabpaneel__icoon i{display:block;width:96px;height:96px;background:var(--sfp-b-secundair);' ), 'set: silhouet 96 px in secundair' );
ti_check( false !== strpos( $css, ':root .sfp-tabpaneel__icoon img{display:block;width:96px;height:96px;object-fit:contain;margin:0}' ), 'media: afbeelding 96 px' );
ti_check( false !== strpos( $css, ':root .sfp-tabpaneel__icoon{display:none}' ), 'telefoon verborgen' );
ti_check( ! preg_match( '/\.sfp-tabpaneel__icoon\{[^}]*(border-radius|background)/', $css ), 'geen vlak of ronde vorm op het vak' );
ti_check( ! preg_match( '/\.sfp-tabpaneel__icoon img\{[^}]*(mask|filter|background)/', $css ), 'afbeelding zonder masker, filter of vlak' );
ti_check( false !== strpos( $php, '<img src="\' . esc_url( $beeld ) . \'" alt="" width="96" height="96" loading="lazy" decoding="async">' ), 'render: img met lege alt' );
ti_check( false !== strpos( $php, '<span class="sfp-tabpaneel__icoon" aria-hidden="true"><img' ), 'render: decoratief (aria-hidden)' );
ti_check( false !== strpos( $js, "useInnerBlocksProps( kaartVorm ? { className: 'sfp-tabpaneel__tekst' } : blockProps" ), 'editor: tekstvak als kaart' );
ti_check( false !== strpos( $js, "kaartVorm ? el( 'div', blockProps, el( 'div', inner ), canvasIcoon ) : el( 'div', inner )" ), 'editor: icoon in het canvas' );
ti_check( false !== strpos( $js, "el( 'img', { src: icoon.source_url, alt: '', width: 96, height: 96 } )" ), 'editor: media als afbeelding' );
echo "tabicoon: $ok goed, $fout fout\n";
exit( $fout ? 1 : 0 );
