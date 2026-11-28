<?php
/**
 * Knoppen en links (3.81.0, branding.md Knoppen en links).
 *
 * php tests/knoppen-links.php
 */

$root  = dirname( __DIR__ );
$fout  = 0;
$aantal = 0;
$check = function ( $ok, $wat ) use ( &$fout, &$aantal ) {
	$aantal++;
	if ( ! $ok ) {
		$fout++;
		echo "FOUT: $wat\n";
	}
};

$basis = file_get_contents( $root . '/assets/blokken/basis.css' );

// Secundaire knop op donker: elke held behalve Wit.
$check( false !== strpos( $basis, ':root .sfp-held:not(.sfp-held--wit) .is-style-sfp-tweede .wp-block-button__link{border-color:var(--sfp-b-wit);color:var(--sfp-b-wit)}' ), 'Tweede op donker: rand en tekst wit' );
$check( false !== strpos( $basis, ':root .sfp-held:not(.sfp-held--wit) .is-style-sfp-tweede .wp-block-button__link:hover{background:var(--sfp-b-wit);border-color:var(--sfp-b-wit);color:var(--sfp-b-primair)}' ), 'Tweede op donker onder de muis: wit gevuld, tekst primair' );
$check( false !== strpos( $basis, ':root :is(.sfp-sectie,.sfp-held) .is-style-sfp-tweede .wp-block-button__link{background:transparent;border-color:var(--sfp-b-primair);color:var(--sfp-b-primair)}' ), 'Tweede op licht ongewijzigd' );

// Primaire link: accentkleur; op donker wit.
$check( false !== strpos( $basis, "\n.is-style-sfp-pijl a{color:var(--sfp-b-links);" ), 'pijllink in de accentkleur' );
$check( false !== strpos( $basis, '.is-style-sfp-tekst .wp-block-button__link{background:transparent;border-color:transparent;color:var(--sfp-b-links);' ), 'knopstijl Tekst in de accentkleur' );
$check( false !== strpos( $basis, ':root .sfp-held:not(.sfp-held--wit) .is-style-sfp-pijl a{color:var(--sfp-b-wit)}' ), 'pijllink op donker wit' );
$check( false !== strpos( $basis, ':root .sfp-held:not(.sfp-held--wit) .is-style-sfp-tekst .wp-block-button__link{color:var(--sfp-b-wit)}' ), 'knopstijl Tekst op donker wit' );

// Kaarten: blogpost neutraal, donker wit.
$check( false !== strpos( $basis, ':root .sfp-kaart .is-style-sfp-pijl a{color:var(--sfp-b-links);' ), 'pijllink in een kaart: accent' );
$check( false !== strpos( $basis, ':root .sfp-kaart.sfp-kaart--bericht .is-style-sfp-pijl a{color:var(--sfp-b-tekstlink)}' ), 'kaart naar een blogpost: neutraal' );
$p_bericht = strpos( $basis, '.sfp-kaart--bericht .is-style-sfp-pijl a{' );
$p_donker  = strpos( $basis, ':root .sfp-kaart.sfp-kaart--donker .is-style-sfp-pijl a{color:var(--sfp-b-wit)}' );
$check( false !== $p_donker && $p_donker > $p_bericht, 'donkere kaart wint van blogpost (zelfde gewicht, later)' );
$home = file_get_contents( $root . '/includes/nlvorm-home.php' );
$check( false !== strpos( $home, 'sfp-kaart--bericht sfp-artikel' ), 'automatische artikelkaart is een blogkaart' );
$blok = file_get_contents( $root . '/includes/blokken.php' );
$check( false !== strpos( $blok, "sfp_page_config_kaart_naar_bericht( \$link ) ? 'sfp-kaart--bericht' : ''" ), 'sfp/kaart herkent een blogpost' );
$check( false !== strpos( $blok, "'post' === get_post_type( \$id )" ) && false !== strpos( $blok, '$id > 0 &&' ), 'alleen een bestaand bericht van het type post' );

// Gewone tekstlink: primair bij alle acht.
$check( false !== strpos( $blok, "'--sfp-b-tekstlink:var(--sfp-rol-tekstlink,var(--sfp-b-primair));'" ), 'tekstlink valt terug op primair' );

// Onderstreping onder de muis in de kleur van de link, nergens meer in de accentkleur.
$css = '';
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/assets' ) ) as $f ) {
	if ( '.css' === substr( $f->getFilename(), -4 ) ) {
		$css .= file_get_contents( $f->getPathname() );
	}
}
$check( ! preg_match( '/text-decoration-color:\s*var\(--sfp-b-links/', $css ), 'geen onderstreping in de accentkleur' );

// Geen kleurwaarde in de nieuwe regels.
foreach ( array( 'tweede', 'pijl', 'bericht' ) as $w ) {
	preg_match_all( '/[^{}]*' . $w . '[^{}]*\{[^}]*\}/', $basis, $m );
	foreach ( $m[0] as $regel ) {
		$check( ! preg_match( '/#[0-9a-fA-F]{3,8}\b|rgb\(/', $regel ), "geen kleurwaarde: $w" );
	}
}

echo ( $aantal - $fout ) . " van $aantal controles goed\n";
exit( $fout ? 1 : 0 );
