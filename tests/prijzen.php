<?php
/**
 * Prijstabel (3.76.0): vangnet, kolommen en terugval, bron en kopie,
 * calculator en cursusdata lezen uit de tabel, tekst van een bedrag.
 *
 * Gebruik: php tests/prijzen.php
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
$GLOBALS['home']         = 'https://depresenteerschool.nl/';
$GLOBALS['taal']         = 'nl';

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

$dir = dirname( __DIR__ );
require $dir . '/includes/prijzen.php';
require $dir . '/includes/calculator-norm.php';
require $dir . '/includes/cursusdata.php';

$fout = 0;
$goed = 0;
$t    = function ( $wat, $ok ) use ( &$fout, &$goed ) {
    if ( $ok ) { ++$goed; } else { ++$fout; echo "FOUT: $wat\n"; }
};
$opnieuw = function () { sfp_prijzen_vergeet(); };

// Vangnet.
$t( 'vangnet zonder tabel', 'vangnet' === sfp_prijzen_stand()['herkomst'] );
$t( 'ic_1 nl 3000', 300000 === sfp_prijs( 'ic_1' ) );
$t( 'boek 19,95', 1995 === sfp_prijs( 'boek' ) );
$t( 'onbekende sleutel leeg', null === sfp_prijs( 'bestaat_niet' ) );
$t( 'sws_fast via terugval op nl-site', 350000 === sfp_prijs( 'sws_fast' ) );

// Calculator gelijk aan de bedragen van voor 3.76.0.
$n = sfp_calculator_norm();
$t( 'calculator 1 dag', 3000 === $n['vormen']['1']['prijs'] );
$t( 'calculator 2 dagen', 6000 === $n['vormen']['2']['prijs'] );
$t( 'calculator 3 dagen', 9000 === $n['vormen']['3']['prijs'] );
$t( 'calculator begeleiding', 1000 === $n['begeleiding_prijs'] );
$t( 'calculator boek in centen', 1995 === $n['boek_centen'] );
$t( 'calculator terugkom', 1500 === $n['terugkom']['dagdeel'] && 750 === $n['terugkom']['online'] );
$t( 'calculator evenement nl', 2000 === $n['evenement_prijs'] );

// Kolom SwS.
$GLOBALS['instellingen']['prijs_kolom'] = 'sws';
$opnieuw();
$t( 'evenement sws 2250', 225000 === sfp_prijs( 'evenement' ) );
$t( 'ic_2 sws leeg valt terug op nl', 600000 === sfp_prijs( 'ic_2' ) );
$t( 'calculator evenement sws', 2250 === sfp_calculator_norm()['evenement_prijs'] );
$GLOBALS['instellingen']['prijs_kolom'] = '';
$opnieuw();

// Kopie van de bron.
update_option( 'sfp_prijzen_kopie', array( 'regels' => array( 'ic_1' => array( 'nl' => 310000 ), 'onzin' => array( 'nl' => 1 ) ), 'bijgewerkt' => 5, 'opgehaald' => time() ) );
$opnieuw();
$t( 'kopie gelezen', 'kopie' === sfp_prijzen_stand()['herkomst'] );
$t( 'kopie ic_1', 310000 === sfp_prijs( 'ic_1' ) );
$t( 'kopie zonder regel: leeg', null === sfp_prijs( 'ic_2' ) );
$t( 'calculator valt terug bij lege regel', 6000 === sfp_calculator_norm()['vormen']['2']['prijs'] );
$t( 'onbekende sleutel uit kopie weg', ! isset( sfp_prijzen_stand()['regels']['onzin'] ) );

// Bron leest eigen tabel, niet de kopie.
$GLOBALS['home'] = 'https://www.schoolforprofessionals.com/';
update_option( 'sfp_prijzen', array( 'regels' => array( 'open_2' => array( 'nl' => 159500 ) ), 'bijgewerkt' => 9 ) );
$opnieuw();
$t( 'is bron (www genegeerd)', sfp_prijzen_is_bron() );
$t( 'bron leest eigen tabel', 159500 === sfp_prijs( 'open_2' ) );

// Cursusdata: eigen prijs gaat voor, anders de tabel.
$info = array( 'prijs' => 0, 'prijzen' => array( '1' => 995 ) );
$t( 'eigen prijs per dag', 995 === sfp_page_config_cursus_prijs( $info, 1 ) );
$t( 'zonder eigen prijs: tabel', 1595 === sfp_page_config_cursus_prijs( $info, 2 ) );
$t( 'zonder varianten: oude prijs', 750 === sfp_page_config_cursus_prijs( array( 'prijs' => 750, 'prijzen' => array() ), 2 ) );

// Opschonen.
$s = sfp_prijzen_schoon( array( 'ic_1' => array( 'nl' => -5, 'sws' => 'abc' ), 'boek' => array( 'nl' => '1995' ) ) );
$t( 'negatief wordt 0', 0 === $s['ic_1']['nl'] );
$t( 'tekst wordt leeg', null === $s['ic_1']['sws'] );
$t( 'getal als tekst', 1995 === $s['boek']['nl'] );

// Tekst.
$t( 'nl 3.500', '€ 3.500' === sfp_prijs_tekst( 350000 ) );
$t( 'nl 19,95', '€ 19,95' === sfp_prijs_tekst( 1995 ) );
$GLOBALS['taal'] = 'en';
$t( 'en €3,500', '€3,500' === sfp_prijs_tekst( 350000 ) );

echo "prijzen: $goed goed, $fout fout\n";
exit( $fout ? 1 : 0 );
