<?php
/**
 * Run with: php tests/faq-schema-prijs.php
 *
 * FAQ-schema met prijzen (3.78.1): [sfp_prijs] in een sfp/faq-vraag komt als
 * bedrag in het FAQPage-schema, niet als letterlijke code.
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
define( 'MINUTE_IN_SECONDS', 60 );
define( 'DAY_IN_SECONDS', 86400 );

$GLOBALS['opties']       = array();
$GLOBALS['instellingen'] = array();
$GLOBALS['home']         = 'https://deschrijftrainers.nl/';
$GLOBALS['taal']         = 'nl';
$GLOBALS['uit']          = '';

function get_option( $n, $d = false ) { return array_key_exists( $n, $GLOBALS['opties'] ) ? $GLOBALS['opties'][ $n ] : $d; }
function update_option( $n, $w, $a = null ) { $GLOBALS['opties'][ $n ] = $w; return true; }
function home_url( $p = '' ) { return $GLOBALS['home']; }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function trailingslashit( $s ) { return rtrim( $s, '/\\' ) . '/'; }
function sfp_page_config_get_setting( $k, $d = '' ) { return isset( $GLOBALS['instellingen'][ $k ] ) ? $GLOBALS['instellingen'][ $k ] : $d; }
function sfp_page_config_taal() { return $GLOBALS['taal']; }
function add_action() {}
function add_shortcode() {}
function shortcode_exists() { return false; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function shortcode_atts( $d, $a, $c = '' ) { return array_merge( $d, array_intersect_key( (array) $a, $d ) ); }
function shortcode_parse_atts( $t ) {
    preg_match_all( '/([\w-]+)\s*=\s*"([^"]*)"/', (string) $t, $m, PREG_SET_ORDER );
    $uit = array();
    foreach ( $m as $x ) {
        $uit[ strtolower( $x[1] ) ] = $x[2];
    }
    return $uit;
}
function wp_kses( $s, $toegestaan ) { return strip_tags( (string) $s, '<' . implode( '><', array_keys( $toegestaan ) ) . '>' ); }
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }
function is_admin() { return false; }
function is_singular() { return true; }
function sanitize_title( $s ) { return strtolower( preg_replace( '/\W+/', '-', (string) $s ) ); }
function get_block_wrapper_attributes( $e ) { return 'class="' . $e['class'] . '"'; }
function sfp_page_config_blok_attr( $a, $k, $d = '' ) { return isset( $a[ $k ] ) ? $a[ $k ] : $d; }
function sfp_page_config_blok_titel_kses() { return array( 'em' => array(), 'strong' => array() ); }
function sfp_page_config_json_ld( $schema, $id ) { $GLOBALS['uit'] = $schema; return ''; }

$dir = dirname( __DIR__ );
require $dir . '/includes/prijzen.php';

// Alleen de FAQ-functies uit blokken.php laden (die heeft veel afhankelijkheden).
$bron = (string) file_get_contents( $dir . '/includes/blokken.php' );
foreach ( array( 'sfp_page_config_faq_verzamel', 'sfp_page_config_faq_prijzen', 'sfp_page_config_render_faq_vraag', 'sfp_page_config_faq_schema' ) as $f ) {
    if ( ! preg_match( '/^function ' . $f . '\(.*?^}\n/ms', $bron, $m ) ) {
        echo "FOUT: functie $f niet gevonden\n";
        exit( 1 );
    }
    eval( $m[0] ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- test.
}

$fouten = 0;
$checks = 0;
function check( $ok, $wat ) {
    global $fouten, $checks;
    ++$checks;
    if ( ! $ok ) {
        ++$fouten;
        echo "FOUT: $wat\n";
    }
}

// De vangnettabel in prijzen.php: ic_dagdeel = 1.500, ic_3c = 9.000.
check( 150000 === sfp_prijs( 'ic_dagdeel' ) && 900000 === sfp_prijs( 'ic_3c' ), 'vangnettabel zoals verwacht' );

/* 1. Hulpfunctie. */
check( 'tussen de € 1.500 en € 9.000' === sfp_page_config_faq_prijzen( 'tussen de [sfp_prijs sleutel="ic_dagdeel"] en [sfp_prijs sleutel="ic_3c"]' ), 'twee codes worden bedragen' );
check( 'kost € 1.500.' === sfp_page_config_faq_prijzen( 'kost [sfp_prijs sleutel=&quot;ic_dagdeel&quot;].' ), 'geëscapete aanhalingstekens' );
check( 'zonder code' === sfp_page_config_faq_prijzen( 'zonder code' ), 'tekst zonder code ongewijzigd' );
check( 'leeg:  .' === sfp_page_config_faq_prijzen( 'leeg: [sfp_prijs sleutel="bestaat_niet"] .' ), 'onbekende sleutel wordt leeg, net als op de pagina' );
check( 'uitleg [sfp_prijs sleutel="x"]' === sfp_page_config_faq_prijzen( 'uitleg [[sfp_prijs sleutel="x"]]' ), 'ontsnapte code blijft tekst' );
check( '[sfp_calculator]' === sfp_page_config_faq_prijzen( '[sfp_calculator]' ), 'andere korte codes niet uitgevoerd' );

/* 2. Blok en schema, zoals op de voorbeeldpagina. */
$antwoord = '<p>Een incompanycursus kost tussen de [sfp_prijs sleutel="ic_dagdeel"] en [sfp_prijs sleutel="ic_3c"] per groep excl. btw.</p><p>Met de <a href="https://deschrijftrainers.nl/incompany/calculator/">calculator</a> reken je het uit.</p>';
$html     = sfp_page_config_render_faq_vraag( array( 'vraag' => 'Wat kost de Incompanycursus Informatief Schrijven?' ), $antwoord );
check( false !== strpos( $html, '[sfp_prijs' ), 'zichtbare HTML laat de code staan voor the_content (geen dubbele verwerking)' );
sfp_page_config_faq_schema();
$tekst = $GLOBALS['uit']['mainEntity'][0]['acceptedAnswer']['text'] ?? '';
check( false !== strpos( $tekst, '€ 1.500' ) && false !== strpos( $tekst, '€ 9.000' ), 'schema bevat € 1.500 en € 9.000' );
check( false === strpos( $tekst, '[sfp_prijs' ), 'schema bevat geen [sfp_prijs' );
check( false !== strpos( $tekst, '<a href="https://deschrijftrainers.nl/incompany/calculator/">calculator</a>' ), 'link blijft (Google staat <a> toe)' );
check( false !== strpos( $tekst, '<br><br>' ), 'alinea\'s worden regelovergangen' );

/* 3. Engelse site. */
$GLOBALS['taal'] = 'en';
check( 'from €1,500' === sfp_page_config_faq_prijzen( 'from [sfp_prijs sleutel="ic_dagdeel"]' ), 'Engelse opmaak' );

echo ( $fouten ? "$fouten van $checks controles mislukt\n" : "Alle $checks controles geslaagd\n" );
exit( $fouten ? 1 : 0 );
