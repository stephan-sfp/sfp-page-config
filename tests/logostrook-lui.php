<?php
/**
 * Logostrook lui laden (3.80.1): alleen de eerste vijf logo's krijgen de
 * klasse sfp-logostrook__logo--direct, die WP Rocket overslaat; de rest laat
 * WP Rocket lui laden. sizes volgt de weergavemaat per logo.
 *
 * Gebruik: php tests/logostrook-lui.php
 */
$root = dirname( __DIR__ );
$fout = 0;
$ok   = 0;
function ll_check( $voorwaarde, $wat ) {
	global $ok, $fout;
	if ( $voorwaarde ) {
		++$ok;
	} else {
		++$fout;
		echo "FOUT: $wat\n";
	}
}

// Kleine WordPress-stubs.
define( 'ABSPATH', __DIR__ . '/' );
function add_filter() {}
function add_action() {}
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }
function wp_list_pluck( $l, $k ) { return array_map( function ( $r ) use ( $k ) { return $r[ $k ]; }, $l ); }
function wp_attachment_is_image( $id ) { return $id > 0 && $id < 100; }
function wp_get_attachment_metadata( $id ) { return array( 'width' => 300, 'height' => 300 / ( 1 + $id % 4 ) ); }
$GLOBALS['ll_attr'] = array();
function wp_get_attachment_image( $id, $maat, $icoon, $attr ) {
	$GLOBALS['ll_attr'][ $id ] = $attr;
	$uit = '<img src="logo-' . $id . '.png"';
	foreach ( $attr as $k => $v ) {
		$uit .= ' ' . $k . '="' . esc_attr( $v ) . '"';
	}
	return $uit . ' />';
}
function absint( $n ) { return abs( (int) $n ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function get_language_attributes() { return 'lang="nl-NL"'; }
function sfp_page_config_instellingen() {
	return array( 'logostrook' => array( 'label' => 'Label', 'logos' => range( 1, $GLOBALS['ll_aantal'] ) ) );
}
function sfp_page_config_blokken_css_vangnet() { return ''; }
function sfp_page_config_blok_omhulling( $klassen ) { return 'class="' . implode( ' ', $klassen ) . '"'; }

require $root . '/includes/blokken-bewijs.php';

// Veel logo's (wissel): vijf direct, de rest niet.
$GLOBALS['ll_aantal'] = 12;
$html = sfp_page_config_render_logostrook( array() );
ll_check( 12 === substr_count( $html, '<img ' ), 'twaalf logo\'s in de HTML' );
ll_check( 5 === substr_count( $html, 'sfp-logostrook__logo--direct' ), 'precies vijf logo\'s direct' );
$volgorde = array();
foreach ( $GLOBALS['ll_attr'] as $id => $a ) {
	$volgorde[ $id ] = false !== strpos( $a['class'], '--direct' );
}
ll_check( array( 1, 2, 3, 4, 5 ) === array_keys( array_filter( $volgorde ) ), 'de eerste vijf in volgorde zijn direct' );
ll_check( 12 === substr_count( $html, 'loading="lazy"' ), 'loading="lazy" blijft op elk img (zonder WP Rocket lui in de browser)' );
ll_check( false !== strpos( $html, 'sfp-logostrook--wissel' ), 'wisselvariant' );

// sizes per logo uit de weergavemaat.
foreach ( $GLOBALS['ll_attr'] as $id => $a ) {
	$m = sfp_page_config_logostrook_maat( $id );
	ll_check( '(max-width: 600px) ' . min( 165, (int) round( $m[0] * 0.75 ) ) . 'px, ' . min( 220, $m[0] ) . 'px' === $a['sizes'], "sizes logo $id volgt de maat" );
}
ll_check( '(max-width: 600px) 165px, 220px' === sfp_page_config_logostrook_sizes( null ), 'zonder maat: grenzen uit de CSS' );
ll_check( '(max-width: 600px) 23px, 30px' === sfp_page_config_logostrook_sizes( array( 30, 36 ) ), 'staand logo (Rabobank): klein' );
ll_check( '(max-width: 600px) 165px, 220px' === sfp_page_config_logostrook_sizes( array( 220, 30 ) ), 'breed logo: tot de grens' );

// Weinig logo's (DST: drie, geen wissel): alles direct.
$GLOBALS['ll_aantal'] = 3;
$GLOBALS['ll_attr']   = array();
$html = sfp_page_config_render_logostrook( array() );
ll_check( 3 === substr_count( $html, 'sfp-logostrook__logo--direct' ), 'drie logo\'s zonder wissel: alle direct' );
ll_check( false === strpos( $html, 'sfp-logostrook--wissel' ), 'drie logo\'s: geen wissel' );

// WP Rocket slaat alleen de directe logo's over.
$rocket = file_get_contents( $root . '/includes/wp-rocket.php' );
ll_check( false !== strpos( $rocket, "\$uit[] = 'sfp-logostrook__logo--direct';" ), 'WP Rocket: alleen --direct uitgesloten' );
ll_check( false === strpos( $rocket, "\$uit[] = 'sfp-logostrook__logo';" ), 'WP Rocket: niet meer alle logo\'s uitgesloten' );

// Wisselscript: zet ook data-lazy-sizes om en wisselt pas bij een echt beeld.
$js = file_get_contents( $root . '/assets/blokken/logostrook.js' );
ll_check( false !== strpos( $js, "[ 'sizes', 'srcset', 'src' ]" ), 'script: sizes, srcset en src uit data-lazy-*' );
ll_check( false !== strpos( $js, '/^data:/.test( img.currentSrc || img.src )' ), 'script: geen wissel naar een plaatshouder' );

echo "logostrook-lui: $ok goed, $fout fout\n";
exit( $fout ? 1 : 0 );
