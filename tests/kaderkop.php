<?php
/**
 * 3.69.0: Kop in kader (alinea met H3-opmaak, geen kop) en praktijkblok 16 px.
 *
 * Gebruik: php tests/kaderkop.php
 */
$root = dirname( __DIR__ );
$css  = file_get_contents( $root . '/assets/blokken/kader.css' );
$pb   = file_get_contents( $root . '/assets/blokken/praktijkblok.css' );
$php  = file_get_contents( $root . '/includes/blokken.php' );
$ed   = file_get_contents( $root . '/includes/editor.php' );
$js   = file_get_contents( $root . '/assets/blokken/editor.js' );
$fout = 0;
$ok   = 0;
function kk_check( $voorwaarde, $wat ) {
	global $ok, $fout;
	if ( $voorwaarde ) {
		++$ok;
	} else {
		++$fout;
		echo "FOUT: $wat\n";
	}
}
kk_check( false !== strpos( $php, "'sfp-kaderkop'     => 'Kop in kader" ), 'alineastijl geregistreerd' );
kk_check( (bool) preg_match( "/sfp_page_config_vorm_alineastijlen\(\) \{\s*return array\([^)]*'sfp-kaderkop'/", $ed ), 'stijl ook in artikel, begrip en tool' );
kk_check( (bool) preg_match( '/p\.is-style-sfp-kaderkop\{[^}]*font-family:var\(--sfp-b-kop\);font-weight:900;font-size:clamp\(18px,1\.5vw \+ 10px,22px\)[^}]*text-transform:uppercase[^}]*color:var\(--sfp-b-primair\)/', $css ), 'H3-opmaak op rol, geen merkkleur of font' );
kk_check( ! preg_match( '/kaderkop\{[^}]*#[0-9a-f]{3,6}/i', $css ), 'geen vaste kleur' );
kk_check( false !== strpos( $js, 'kaderZonderKoppen( props.clientId );' ), 'kader roept de omzetting aan' );
kk_check( false !== strpos( $js, "'core/heading' === b.name" ) && false !== strpos( $js, "createBlock( 'core/paragraph', { content: kop.attributes.content, className: klassen.join( ' ' ), anchor: kop.attributes.anchor } )" ), 'kop wordt alinea met inhoud en anker' );
kk_check( false !== strpos( $pb, '.wp-block-quote:is(.praktijkblok,.is-style-praktijkblok) p{margin:0 0 12px;font-size:16px;line-height:1.6}' ), 'praktijkblok 16 px, regel 1,6' );
kk_check( false !== strpos( $pb, 'p.praktijkblok-kop{' ) && false !== strpos( $pb, 'font-size:15px' ), 'kop praktijkblok blijft 15 px' );
echo "kaderkop: $ok goed, $fout fout\n";
exit( $fout ? 1 : 0 );
