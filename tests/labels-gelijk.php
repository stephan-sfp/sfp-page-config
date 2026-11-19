<?php
/**
 * Alle labels dezelfde maat (sinds 3.77.0, besluit Stephan 19-11-2026):
 * één labelregel (12 px, 0.12em, 900, hoofdletters) voor elke labelklasse,
 * geen eigen labelmaat in een component, ook niet op telefoon. De kop van de
 * logostrook is een kop: maat van de h3, op elke pagina.
 *
 * Gebruik: php tests/labels-gelijk.php
 */
$root = dirname( __DIR__ );
$ok   = 0;
$fout = 0;
function lb_check( $v, $wat ) {
	global $ok, $fout;
	if ( $v ) {
		++$ok;
	} else {
		++$fout;
		echo "FOUT: $wat\n";
	}
}
$php = file_get_contents( $root . '/includes/blokken.php' );
$fn  = substr( $php, strpos( $php, 'function sfp_page_config_label_css' ), 900 );
lb_check( false !== strpos( $fn, ':root :is(.is-style-sfp-label,.sfp-blok-label,.sfp-boek-label,.fictief-label)' ), 'regel geldt voor alle vier labelklassen' );
lb_check( false !== strpos( $fn, 'font-size:12px!important' ), 'labelmaat 12 px' );
lb_check( false !== strpos( $fn, 'letter-spacing:.12em!important' ), 'letterafstand 0.12em' );
lb_check( false !== strpos( $fn, 'font-weight:var(--sfp-kopfont-gewicht,900)!important' ), 'gewicht 900' );
lb_check( false !== strpos( $fn, 'text-transform:uppercase!important' ), 'hoofdletters' );
$var = substr( $php, strpos( $php, 'function sfp_page_config_blokken_css_variabelen' ), 3500 );
lb_check( false !== strpos( $var, '. sfp_page_config_label_css();' ), 'labelregel staat bij de variabelen' );
lb_check( false !== strpos( $var, '--sfp-b-h3:clamp(18px,1.5vw + 10px,22px)' ), 'variabele h3-maat' );

// Geen eigen maat of letterafstand meer op een labelselector in de CSS.
$bestanden = array_merge( glob( $root . '/assets/*.css' ), glob( $root . '/assets/blokken/*.css' ) );
foreach ( $bestanden as $f ) {
	$css = preg_replace( '#/\*.*?\*/#s', '', file_get_contents( $f ) );
	if ( ! preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $css, $m, PREG_SET_ORDER ) ) {
		continue;
	}
	foreach ( $m as $r ) {
		$sel = $r[1];
		if ( ! preg_match( '/\.(is-style-sfp-label|sfp-blok-label|sfp-boek-label|fictief-label)(?![\w-])/', $sel ) ) {
			continue;
		}
		// Een labelselector die eindigt op het label (niet op een kind ervan).
		$laatste = trim( substr( $sel, strrpos( $sel, ',' ) === false ? 0 : strrpos( $sel, ',' ) + 1 ) );
		if ( preg_match( '/font-size:(?!12px)/', $r[2] ) && preg_match( '/(label)(\)|\s*)$/', $laatste ) ) {
			lb_check( false, basename( $f ) . ': eigen labelmaat in ' . trim( $sel ) );
		}
		if ( preg_match( '/letter-spacing:(?!\.12em)/', $r[2] ) ) {
			lb_check( false, basename( $f ) . ': eigen letterafstand in ' . trim( $sel ) );
		}
	}
}
lb_check( true, 'CSS doorzocht' );

$logo = file_get_contents( $root . '/assets/blokken/logostrook.css' );
$held = file_get_contents( $root . '/assets/blokken/nlvorm-held.css' );
lb_check( false !== strpos( $logo, 'font-size:var(--sfp-b-h3)' ), 'logostrookkop in h3-maat' );
lb_check( false === strpos( $held, '.sfp-logostrook__label{font-size' ), 'geen aparte logostrookmaat bij de held' );

echo "labels-gelijk: $ok goed, $fout fout\n";
exit( $fout ? 1 : 0 );
