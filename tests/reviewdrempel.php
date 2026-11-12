<?php
/**
 * Tests voor 3.54.0: het aantal reviews pas vanaf een drempel (standaard 25,
 * besluit Stephan 12-11-2026). Onder de drempel blijven cijfer en sterren.
 * - instelling aantal_vanaf: standaard 25, opgeschoond tussen 0 en 1000;
 * - sfp_page_config_reviews_aantal_tonen();
 * - samenvatting boven [google_reviews] en de teksten voor de reviewkop.
 * Met kleine WordPress-stubs.
 *
 * Draaien: php tests/reviewdrempel.php
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
error_reporting( E_ALL );
set_error_handler(
    function ( $severity, $message, $file, $line ) {
        throw new ErrorException( $message, 0, $severity, $file, $line );
    }
);
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );

/* ---------- Stubs ---------- */

$GLOBALS['opties'] = array();
function get_option( $k, $d = false ) {
    return $GLOBALS['opties'][ $k ] ?? $d;
}
function esc_html( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_url( $s ) {
    return (string) $s;
}
function sanitize_key( $s ) {
    return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) );
}
function sanitize_text_field( $s ) {
    return trim( (string) $s );
}
function number_format_i18n( $n, $d = 0 ) {
    return number_format( (float) $n, $d, ',', '.' );
}
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function shortcode_exists() {
    return false;
}
function sfp_page_config_tekst( $k ) {
    $t = array(
        'reviews_basis_een'  => 'Op basis van %s review',
        'reviews_basis'      => 'Op basis van %s reviews',
        'reviews_uitstekend' => 'Uitstekend',
        'reviews_zeer_goed'  => 'Zeer goed',
        'reviews_goed'       => 'Goed',
        'reviews_gemiddeld'  => 'Gemiddeld',
        'reviews_matig'      => 'Matig',
        'reviews_sterren'    => '%d van de 5 sterren',
    );
    return $t[ $k ] ?? $k;
}
function sfp_page_config_tekst_f( $k, ...$args ) {
    return vsprintf( sfp_page_config_tekst( $k ), $args );
}

require dirname( __DIR__ ) . '/includes/onderdeel-reviews.php';
require dirname( __DIR__ ) . '/includes/reviews/class-sfp-reviews-renderer.php';

$checks = 0;
$fouten = 0;
$waar   = function ( $echt, $wat ) use ( &$checks, &$fouten ) {
    $checks++;
    if ( ! $echt ) {
        $fouten++;
        echo "FOUT: $wat\n";
    }
};

/* ---------- Instelling ---------- */

$o = sfp_page_config_reviews_opties();
$waar( 25 === $o['aantal_vanaf'], 'standaard drempel is 25' );
$waar( 0 === sfp_page_config_reviews_opties_schoon( array( 'aantal_vanaf' => '-3' ) )['aantal_vanaf'], 'negatief wordt 0' );
$waar( 1000 === sfp_page_config_reviews_opties_schoon( array( 'aantal_vanaf' => '5000' ) )['aantal_vanaf'], 'boven 1000 wordt 1000' );
$waar( 12 === sfp_page_config_reviews_opties_schoon( array( 'aantal_vanaf' => '12' ) )['aantal_vanaf'], '12 blijft 12' );
$waar( 25 === sfp_page_config_reviews_opties_schoon( array( 'layout' => 'grid' ) )['aantal_vanaf'], 'andere sleutel laat de drempel staan' );

/* ---------- Drempel ---------- */

$waar( false === sfp_page_config_reviews_aantal_tonen( 2 ), '2 reviews: geen aantal' );
$waar( false === sfp_page_config_reviews_aantal_tonen( 24 ), '24 reviews: geen aantal' );
$waar( true === sfp_page_config_reviews_aantal_tonen( 25 ), '25 reviews: aantal' );
$waar( true === sfp_page_config_reviews_aantal_tonen( 62 ), '62 reviews: aantal' );
$waar( false === sfp_page_config_reviews_aantal_tonen( 0 ), '0 reviews: geen aantal' );
$GLOBALS['opties']['sfp_reviews_options'] = array( 'aantal_vanaf' => 0 );
$waar( true === sfp_page_config_reviews_aantal_tonen( 1 ), 'drempel 0: altijd tonen' );
$GLOBALS['opties']['sfp_reviews_options'] = array( 'aantal_vanaf' => 1 );
$waar( true === sfp_page_config_reviews_aantal_tonen( 1 ), 'drempel 1: 1 review tonen' );
$GLOBALS['opties']['sfp_reviews_options'] = array();

/* ---------- Samenvatting en reviewkop ---------- */

$r = new SFP_Reviews_Renderer();

$weinig = $r->samenvatting( array( 'rating' => 5.0, 'total' => 2 ) );
$waar( false !== strpos( $weinig, 'Uitstekend' ), 'onder drempel: oordeel blijft' );
$waar( false !== strpos( $weinig, 'sfp-reviews-summary__stars' ), 'onder drempel: sterren blijven' );
$waar( false === strpos( $weinig, 'sfp-reviews-summary__total' ), 'onder drempel: geen regel met aantal' );
$waar( false === strpos( $weinig, 'Op basis van' ), 'onder drempel: geen "Op basis van"' );

$veel = $r->samenvatting( array( 'rating' => 4.9, 'total' => 62 ) );
$waar( false !== strpos( $veel, 'Op basis van 62 reviews' ), 'boven drempel: aantal staat er' );

$t = $r->samenvatting_teksten( array( 'rating' => 5.0, 'total' => 2 ) );
$waar( 'Uitstekend' === $t['oordeel'] && '' === $t['basis'], 'reviewkop onder drempel: alleen oordeel' );
$t = $r->samenvatting_teksten( array( 'rating' => 4.9, 'total' => 62 ) );
$waar( 'Op basis van 62 reviews' === $t['basis'], 'reviewkop boven drempel: met aantal' );

echo "\n$checks controles, $fouten fouten\n";
exit( $fouten ? 1 : 0 );
