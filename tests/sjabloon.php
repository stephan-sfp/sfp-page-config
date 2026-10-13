<?php
/**
 * Run with: php tests/sjabloon.php
 * Optioneel: php tests/sjabloon.php <map> [shortcode,shortcode,...]
 * telt per .html-bestand in <map> wat de controle "Sjabloontekst niet
 * ingevuld" nog meldt, met de opgegeven shortcodes als bekend op de voorkant.
 */
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );
define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['options']        = array();
$GLOBALS['transients']     = array();
$GLOBALS['shortcode_tags'] = array();
$GLOBALS['is_admin']       = true;
function get_option( $k, $d = false ) { return $GLOBALS['options'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['options'][ $k ] = $v; return true; }
function delete_transient( $k ) { unset( $GLOBALS['transients'][ $k ] ); return true; }
function add_action( $h, $cb, $p = 10, $n = 1 ) {}
function has_action( $h, $cb = false ) { return false; }
function is_admin() { return $GLOBALS['is_admin']; }
function wp_doing_ajax() { return false; }
function wp_doing_cron() { return false; }
function add_shortcode( $t, $cb ) { $GLOBALS['shortcode_tags'][ $t ] = $cb; }
function shortcode_exists( $t ) { return isset( $GLOBALS['shortcode_tags'][ $t ] ); }
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );

$checks = 0;
function same( $expected, $actual, $message ) {
    global $checks;
    ++$checks;
    if ( $expected !== $actual ) {
        throw new RuntimeException( $message . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
    }
}

require dirname( __DIR__ ) . '/includes/placeholder-guard.php';

// Tellen op echte inhoud.
if ( isset( $argv[1] ) ) {
    foreach ( array_filter( explode( ',', $argv[2] ?? '' ) ) as $tag ) {
        add_shortcode( $tag, '__return_empty_string' );
    }
    $totaal = 0;
    foreach ( glob( rtrim( $argv[1], '/' ) . '/*.html' ) as $bestand ) {
        $gevonden = sfp_page_config_find_placeholders( file_get_contents( $bestand ) );
        if ( $gevonden ) {
            ++$totaal;
            echo basename( $bestand, '.html' ) . ': ' . implode( ' | ', array_map( function ( $f ) { return mb_substr( preg_replace( '/\s+/', ' ', $f ), 0, 70 ); }, $gevonden ) ) . "\n";
        }
    }
    echo "Pagina's met een melding: $totaal\n";
    exit;
}

add_shortcode( 'training_naam', '__return_empty_string' );
add_shortcode( 'sureforms', '__return_empty_string' );

// Leestekst: script, style, opmerkingen, blokattributen en tags vallen weg.
$inhoud = '<!-- wp:uagb/testimonial {"ctaText":"[Naam] • [Organisatie]"} /-->'
    . '<!-- wp:html --><script>var a = b[i]; document.querySelector(\'meta[name="robots"]\'); x[currentStep];</script><!-- /wp:html -->'
    . '<style>.x[aria-level="2"]{color:red}</style>'
    . '<!-- [LET OP: SCHEMA] -->'
    . '<a href="/plan/" data-sel=\'[href$="/plan-een-gesprek/"]\'>Plan</a>'
    . '<p>Training [training_naam], [sureforms id="1"]. Ellips... en drie puntjes.</p>';
same( array(), sfp_page_config_find_placeholders( $inhoud ), 'Alleen code, attributen en bekende shortcodes: geen melding' );

// Echte sjabloontekst in de leestekst blijft gemeld.
same( array( '[Probleem]', '.....' ), sfp_page_config_find_placeholders( '<p>Ons [Probleem] en ..... hier.</p>' ), 'Sjabloontekst in een alinea' );

// Een shortcode die nergens bestaat blijft gemeld, ook met HTML erin.
same(
    array( "[tooltip 'Via Bol.com Bol']" ),
    sfp_page_config_find_placeholders( "<p>In zijn boek [tooltip 'Via <a href=\"https://www.bol.com/\">Bol.com</a> Bol']<em>Words</em></p>" ),
    'Oude tooltip-code is zichtbaar voor bezoekers'
);

// Bewuste invulformule voor de lezer blijft gemeld (negeerbaar per pagina).
same( array( '[doelgroep specificeren]' ), sfp_page_config_find_placeholders( '<p>Ik wil [doelgroep specificeren] bereiken.</p>' ), 'Invulformule voor de lezer' );

// Shortcode die alleen aan de voorkant bestaat.
$p = '<p>[dynamische_cta]</p>';
same( array( '[dynamische_cta]' ), sfp_page_config_find_placeholders( $p ), 'Onbekend voor het eerste bezoek' );
$GLOBALS['is_admin'] = false;
add_shortcode( 'dynamische_cta', '__return_empty_string' );
$GLOBALS['transients']['sfp_pc_paginas'] = array( 1 );
sfp_page_config_onthoud_shortcodes_voorkant();
same( true, in_array( 'dynamische_cta', $GLOBALS['options']['sfp_pc_shortcodes_voorkant']['tags'], true ), 'Voorkant onthoudt de shortcode' );
same( false, isset( $GLOBALS['transients']['sfp_pc_paginas'] ), 'Bewaarde controle vervalt bij een nieuwe set' );
$GLOBALS['transients']['sfp_pc_paginas'] = array( 1 );
sfp_page_config_onthoud_shortcodes_voorkant();
same( true, isset( $GLOBALS['transients']['sfp_pc_paginas'] ), 'Zelfde set: niets opnieuw' );
unset( $GLOBALS['shortcode_tags']['dynamische_cta'] );
$GLOBALS['is_admin'] = true;
same( array(), sfp_page_config_find_placeholders( $p ), 'In het beheer bekend via de voorkant' );
sfp_page_config_onthoud_shortcodes_voorkant();
same( true, in_array( 'dynamische_cta', $GLOBALS['options']['sfp_pc_shortcodes_voorkant']['tags'], true ), 'Het beheer overschrijft de voorkant niet' );

// Verborgen blok: met alles erin weg, ook met geneste blokken van dezelfde soort.
$verborgen = '<!-- wp:group {"metadata":{"name":"Oud","blockVisibility":false}} --><div>'
    . '<!-- wp:group --><div><p>[Naam] • [Organisatie]</p></div><!-- /wp:group -->'
    . '<p>[Nog]</p></div><!-- /wp:group -->'
    . '<!-- wp:paragraph --><p>[Zichtbaar]</p><!-- /wp:paragraph -->'
    . '<!-- wp:uagb/info-box {"metadata":{"blockVisibility":false}} /-->';
same( array( '[Zichtbaar]' ), sfp_page_config_find_placeholders( $verborgen ), 'Verborgen blok telt niet, het blok erna wel' );
same( array( '[Smal]' ), sfp_page_config_find_placeholders( '<!-- wp:group {"metadata":{"blockVisibility":{"viewport":{"mobile":false}}}} --><p>[Smal]</p><!-- /wp:group -->' ), 'Alleen per schermbreedte verborgen telt wel' );
same( array(), sfp_page_config_find_placeholders( '<!-- wp:group {"metadata":{"blockVisibility":false}} --><p>[Weg]</p>' ), 'Verborgen blok zonder einde' );

// Opmerking zonder einde: de rest telt niet.
same( array(), sfp_page_config_find_placeholders( '<p>Tekst</p><!-- [Naam] zonder einde' ), 'Opmerking zonder einde' );

// Hoofdlettergevoelig, zoals shortcode_exists().
same( array( '[Training_naam]' ), sfp_page_config_find_placeholders( '<p>[Training_naam]</p>' ), 'Hoofdletter telt' );

echo "OK: $checks checks\n";
