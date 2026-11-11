<?php
/**
 * Tests voor 3.47.0: reviews tonen geen datum meer (wens Stephan 11-11-2026).
 * - sfp/reviews (raster en slider): geen sfp-review__time, geen relatieve tijd.
 * - sfp/review met een Google-review: de bron zonder maand en jaar.
 * Met kleine WordPress-stubs.
 *
 * Draaien: php tests/review-zonder-datum.php
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

/* ---------- Stubs ---------- */

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
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function shortcode_exists() {
    return false;
}
function register_block_type() {}
function wp_date( $format, $ts = null ) {
    return gmdate( 'F Y', (int) $ts );
}
function sfp_page_config_tekst( $k ) {
    $t = array(
        'review_google_bron'  => 'Google-review',
        'reviews_lees_verder' => 'Lees verder',
        'reviews_sterren'     => '%d sterren',
        'reviews_vorige'      => 'Vorige',
        'reviews_volgende'    => 'Volgende',
    );
    return $t[ $k ] ?? $k;
}
function sfp_page_config_tekst_f( $k, ...$args ) {
    return vsprintf( sfp_page_config_tekst( $k ), $args );
}
function sfp_page_config_reviews_opties() {
    return array( 'place_id' => 'abc' );
}
function sfp_page_config_reviews_gegevens() {
    return array(
        'reviews' => array(
            array(
                'author' => 'Anna',
                'rating' => 5,
                'text'   => 'Top training.',
                'time'   => '8 maanden geleden',
                'stamp'  => 1772323200, // 1 maart 2026.
            ),
        ),
    );
}

require dirname( __DIR__ ) . '/includes/reviews/class-sfp-reviews-renderer.php';
require dirname( __DIR__ ) . '/includes/blokken-bewijs.php';

$checks = 0;
$fouten = 0;
$waar   = function ( $echt, $wat ) use ( &$checks, &$fouten ) {
    ++$checks;
    if ( ! $echt ) {
        ++$fouten;
        echo 'FOUT: ' . $wat . "\n";
    }
};

/* ---------- sfp/reviews ---------- */

$review = array(
    'author' => 'Anna',
    'rating' => 5,
    'text'   => 'Top training.',
    'time'   => '8 maanden geleden',
    'photo'  => '',
);
$r = new SFP_Reviews_Renderer();
foreach ( array( 'grid', 'slider', 'banner' ) as $layout ) {
    $html = $r->render( array( $review ), array( 'layout' => $layout, 'auto' => 'false', 'interval' => 5000, 'cols_mobile' => 1, 'cols_desktop' => 3 ) );
    $waar( false !== strpos( $html, 'Anna' ), "$layout: naam staat er" );
    $waar( false === strpos( $html, 'sfp-review__time' ), "$layout: geen sfp-review__time" );
    $waar( false === strpos( $html, 'geleden' ), "$layout: geen relatieve tijd" );
}

/* ---------- sfp/review met Google-review ---------- */

$lijst = sfp_page_config_review_google_lijst();
$waar( 1 === count( $lijst ), 'Google-lijst: één review' );
$waar( 'Google-review' === ( $lijst[0]['vermelding'] ?? null ), 'Google-review: bron zonder datum' );
$waar( false === strpos( (string) ( $lijst[0]['vermelding'] ?? '' ), '2026' ), 'Google-review: geen jaar' );

$css = file_get_contents( dirname( __DIR__ ) . '/assets/reviews/reviews.css' );
$waar( false === strpos( $css, 'sfp-review__time' ), 'reviews.css: geen regel voor de tijd' );

echo ( $fouten ? "FOUT: $fouten van $checks\n" : "OK: $checks checks\n" );
exit( $fouten ? 1 : 0 );
