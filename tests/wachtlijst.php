<?php
/**
 * Tests voor de wachtlijst (3.17.0): toekomstige groepen, [cursus_datum] met
 * wachtlijsttekst en intro, [cursus_tijd] met dezelfde nummering, het wisselen
 * van het formulier en de klassen sfp-met-data en sfp-zonder-data. Met kleine
 * WordPress-stubs. Elk scenario gebruikt een eigen post_id, want de code
 * onthoudt per post_id wat hij al las.
 *
 * Draaien: php tests/wachtlijst.php
 */
error_reporting( E_ALL );
set_error_handler(
    function ( $severity, $message, $file, $line ) {
        throw new ErrorException( $message, 0, $severity, $file, $line );
    }
);
define( 'ABSPATH', __DIR__ . '/' );

/* ---------- Stubs ---------- */

$GLOBALS['options'] = array( 'sfp_settings' => array() );
$GLOBALS['meta']    = array();
$GLOBALS['types']   = array( 501 => 'sureforms_form', 502 => 'sureforms_form', 503 => 'page' );
$GLOBALS['status']  = array( 501 => 'publish', 502 => 'publish', 503 => 'publish' );
$GLOBALS['vandaag'] = '2026-10-28';
$GLOBALS['pagina']  = 0;
$GLOBALS['admin']   = false;

function get_option( $k, $d = false ) {
    return $GLOBALS['options'][ $k ] ?? $d;
}
function update_option( $k, $v ) {
    $GLOBALS['options'][ $k ] = $v;
    return true;
}
function get_transient() {
    return false;
}
function set_transient() {
    return true;
}
function delete_transient() {
    return true;
}
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function shortcode_exists() {
    return false;
}
function apply_filters( $hook, $value ) {
    return $value;
}
function is_admin() {
    return $GLOBALS['admin'];
}
function is_singular() {
    return (bool) $GLOBALS['pagina'];
}
function get_queried_object_id() {
    return $GLOBALS['pagina'];
}
function get_the_ID() {
    return $GLOBALS['pagina'];
}
function get_locale() {
    return 'nl_NL';
}
function sanitize_text_field( $s ) {
    return trim( strip_tags( (string) $s ) );
}
function sanitize_textarea_field( $s ) {
    return trim( strip_tags( (string) $s ) );
}
function sanitize_email( $s ) {
    return (string) $s;
}
function sanitize_key( $s ) {
    return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) );
}
function absint( $n ) {
    return abs( (int) $n );
}
function esc_html( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function wp_date( $format, $ts = null ) {
    return 'Y-m-d' === $format && null === $ts ? $GLOBALS['vandaag'] : gmdate( $format, null === $ts ? time() : $ts );
}
function date_i18n( $format, $ts = false ) {
    return gmdate( $format, $ts ? $ts : time() );
}
function get_post_type( $id ) {
    return $GLOBALS['types'][ $id ] ?? false;
}
function get_post_status( $id ) {
    return $GLOBALS['status'][ $id ] ?? false;
}
function get_post_meta( $id, $key = '', $single = false ) {
    return $GLOBALS['meta'][ $id ][ $key ] ?? '';
}
function wp_json_encode( $v, $flags = 0 ) {
    return json_encode( $v, $flags );
}
function shortcode_atts( $pairs, $atts ) {
    $atts = (array) $atts;
    $out  = array();
    foreach ( $pairs as $name => $default ) {
        $out[ $name ] = array_key_exists( $name, $atts ) ? $atts[ $name ] : $default;
    }
    return $out;
}
function sfp_page_config_post_types() {
    return array( 'page' );
}
function sfp_page_config_anker_schoon( $a ) {
    return sanitize_key( ltrim( (string) $a, '#' ) );
}
function sfp_page_config_format_date_nl( $date, $format = 'short' ) {
    return (string) $date;
}
function sfp_page_config_sanitize_time( $t ) {
    return preg_match( '/^\d{2}:\d{2}$/', (string) $t ) ? (string) $t : '';
}

$fouten = 0;
$tests  = 0;
function same( $verwacht, $echt, $wat ) {
    global $fouten, $tests;
    $tests++;
    if ( $verwacht !== $echt ) {
        $fouten++;
        echo "FOUT: $wat\n  verwacht: " . var_export( $verwacht, true ) . "\n  kreeg:    " . var_export( $echt, true ) . "\n";
    }
}
function bevat( $naald, $hooi, $wat ) {
    same( true, false !== strpos( (string) $hooi, $naald ), $wat );
}
function bevat_niet( $naald, $hooi, $wat ) {
    same( false, false !== strpos( (string) $hooi, $naald ), $wat );
}

require dirname( __DIR__ ) . '/includes/instellingen.php';
require dirname( __DIR__ ) . '/includes/teksten.php';
require dirname( __DIR__ ) . '/includes/wachtlijst.php';
require dirname( __DIR__ ) . '/includes/shortcodes.php';

function training( $id, $groepen, $extra = array() ) {
    $GLOBALS['types'][ $id ]  = 'page';
    $GLOBALS['status'][ $id ] = 'publish';
    $GLOBALS['meta'][ $id ]   = array_merge(
        array(
            'sfp_page_type'  => 'training',
            'sfp_cursusdata' => null === $groepen ? '' : json_encode( $groepen ),
        ),
        $extra
    );
    return $id;
}
function instel( $wijzigingen ) {
    sfp_page_config_instellingen_bewaar( $wijzigingen );
}

instel( array( 'onderdelen' => array( 'cursusdata' => '1' ) ) );

/* ---------- Toekomstige groepen ---------- */

$p = training( 10, array( array( 'data' => array( '2026-10-01', '2026-10-15' ) ), array( 'data' => array( '2026-11-03', '2026-11-17' ), 'start' => '09:00', 'eind' => '17:00' ), array( 'data' => array( '2026-10-28' ) ) ) );
$t = sfp_page_config_cursusdata_toekomst( $p );
same( 2, count( $t ), 'Verlopen groep valt weg, groep van vandaag telt mee' );
same( '2026-10-28', $t[0]['data'][0], 'Volgorde op eerste dag' );
same( '09:00', $t[1]['start'], 'Lestijd blijft bewaard' );

$p = training( 11, array( array( 'data' => array( '2026-10-20', '2026-11-10' ) ) ) );
same( array(), sfp_page_config_cursusdata_toekomst( $p ), 'Begonnen groep (eerste dag voorbij) telt niet' );

$p = training( 12, null, array( 'startdatum' => '2026-12-01' ) );
same( 1, count( sfp_page_config_cursusdata_toekomst( $p ) ), 'Oud veld startdatum in de toekomst telt' );
$p = training( 13, null, array( 'startdatum' => '2026-01-01' ) );
same( array(), sfp_page_config_cursusdata_toekomst( $p ), 'Oud veld startdatum verlopen telt niet' );
$p = training( 14, 'kapot' );
$GLOBALS['meta'][14]['sfp_cursusdata'] = '{kapot';
same( array(), sfp_page_config_cursusdata_toekomst( $p ), 'Kapotte JSON geeft geen groepen' );

/* ---------- Stand ---------- */

same( true, sfp_page_config_wachtlijst_stand( 11 ), 'Training zonder toekomstige groep: wachtlijst' );
same( false, sfp_page_config_wachtlijst_stand( 10 ), 'Training met toekomstige groep: geen wachtlijst' );
$GLOBALS['meta'][15] = array( 'sfp_page_type' => 'coaching' );
same( false, sfp_page_config_wachtlijst_stand( 15 ), 'Geen training: geen wachtlijst' );
instel( array( 'onderdelen' => array( 'cursusdata' => '0' ) ) );
same( false, sfp_page_config_wachtlijst_stand( 11 ), 'Onderdeel Cursusdata uit: geen wachtlijst' );
instel( array( 'onderdelen' => array( 'cursusdata' => '1' ) ) );

/* ---------- [cursus_datum] ---------- */

$GLOBALS['pagina'] = 10;
$uit = sfp_page_config_shortcode_cursus_datum( array( 'intro' => 'De volgende groepen starten op:' ) );
bevat( 'De volgende groepen starten op:', $uit, 'Intro met data' );
bevat( 'Groep 1:', $uit, 'Nummering vanaf 1 over toekomstige groepen' );
bevat( 'Groep 2:', $uit, 'Tweede toekomstige groep' );
bevat_niet( 'Groep 3:', $uit, 'Verlopen groep niet getoond' );
same( '', sfp_page_config_shortcode_cursus_datum( array( 'groep' => '3' ) ), 'Niet bestaande groep met wel data: leeg' );

$GLOBALS['pagina'] = 11;
$uit = sfp_page_config_shortcode_cursus_datum( array( 'intro' => 'De volgende groepen starten op:' ) );
bevat_niet( 'De volgende groepen', $uit, 'Geen intro zonder data' );
bevat( 'Neem contact op', $uit, 'Zonder wachtlijstformulier: oude tekst geen startmomenten' );

instel( array( 'inschrijf_formulier' => '501', 'wachtlijst_formulier' => '502' ) );
same( 501, sfp_page_config_wachtlijst_formulier_id( 'inschrijf_formulier' ), 'Inschrijfformulier bewaard' );
same( 502, sfp_page_config_wachtlijst_formulier_id( 'wachtlijst_formulier' ), 'Wachtlijstformulier bewaard' );
instel( array( 'wachtlijst_formulier' => '503' ) );
same( '', $GLOBALS['options']['sfp_settings']['wachtlijst_formulier'], 'Geen SureForms-formulier: niet bewaard' );
instel( array( 'wachtlijst_formulier' => '502' ) );

$uit = sfp_page_config_shortcode_cursus_datum( array() );
bevat( 'Er staat nog geen nieuwe groep gepland.', $uit, 'Wachtlijsttekst' );
bevat( '<a href="#inschrijven">Zet jezelf op de wachtlijst</a>', $uit, 'Link naar het formulieranker' );
bevat( 'van start gaat!', $uit, 'Tekst na de link' );

instel( array( 'sticky_cta' => array( 'training' => array( 'anchor' => '#aanmelden' ) ) ) );
if ( isset( $GLOBALS['options']['sfp_settings']['sticky_cta']['training']['anchor'] ) ) {
    bevat( 'href="#aanmelden"', sfp_page_config_wachtlijst_html(), 'Anker volgt de instelling van de knop onderaan' );
}

$GLOBALS['pagina'] = 15;
same( '', sfp_page_config_shortcode_cursus_datum( array() ), 'Geen training zonder data: leeg zoals voorheen' );

/* ---------- [cursus_tijd] ---------- */

$GLOBALS['pagina'] = 10;
bevat( '09:00', sfp_page_config_shortcode_cursus_tijd( array( 'groep' => '2' ) ), 'Lestijd van de tweede toekomstige groep' );

/* ---------- Formulier wisselen ---------- */

$GLOBALS['pagina'] = 11;
$blok = sfp_page_config_wachtlijst_formulier_wissel( array( 'blockName' => 'srfm/form', 'attrs' => array( 'id' => 501 ) ) );
same( 502, $blok['attrs']['id'], 'Zonder toekomstige groep: wachtlijstformulier' );
$blok = sfp_page_config_wachtlijst_formulier_wissel( array( 'blockName' => 'srfm/form', 'attrs' => array( 'id' => 777 ) ) );
same( 777, $blok['attrs']['id'], 'Ander formulier blijft' );
$GLOBALS['pagina'] = 10;
$blok = sfp_page_config_wachtlijst_formulier_wissel( array( 'blockName' => 'srfm/form', 'attrs' => array( 'id' => 501 ) ) );
same( 501, $blok['attrs']['id'], 'Met toekomstige groep: inschrijfformulier blijft' );
$GLOBALS['pagina'] = 11;
$GLOBALS['admin']  = true;
$blok = sfp_page_config_wachtlijst_formulier_wissel( array( 'blockName' => 'srfm/form', 'attrs' => array( 'id' => 501 ) ) );
same( 501, $blok['attrs']['id'], 'In het beheer: niets wisselen' );
$GLOBALS['admin'] = false;
$GLOBALS['status'][502] = 'draft';
$blok = sfp_page_config_wachtlijst_formulier_wissel( array( 'blockName' => 'srfm/form', 'attrs' => array( 'id' => 501 ) ) );
same( 501, $blok['attrs']['id'], 'Wachtlijstformulier niet gepubliceerd: niets wisselen' );
$GLOBALS['status'][502] = 'publish';

/* ---------- Klassen ---------- */

$met    = array( 'attrs' => array( 'className' => 'iets sfp-met-data' ) );
$zonder = array( 'attrs' => array( 'className' => 'sfp-zonder-data' ) );
$GLOBALS['pagina'] = 11;
same( '', sfp_page_config_wachtlijst_blokken( '<p>a</p>', $met ), 'Zonder data: sfp-met-data weg' );
same( '<p>b</p>', sfp_page_config_wachtlijst_blokken( '<p>b</p>', $zonder ), 'Zonder data: sfp-zonder-data staat er' );
$GLOBALS['pagina'] = 10;
same( '<p>a</p>', sfp_page_config_wachtlijst_blokken( '<p>a</p>', $met ), 'Met data: sfp-met-data staat er' );
same( '', sfp_page_config_wachtlijst_blokken( '<p>b</p>', $zonder ), 'Met data: sfp-zonder-data weg' );
$GLOBALS['pagina'] = 15;
same( '<p>b</p>', sfp_page_config_wachtlijst_blokken( '<p>b</p>', $zonder ), 'Geen training: klassen doen niets' );
same( '<p>c</p>', sfp_page_config_wachtlijst_blokken( '<p>c</p>', array( 'attrs' => array( 'className' => 'sfp-met-data-x' ) ) ), 'Alleen de exacte klasse telt' );

/* ---------- Teksten ---------- */

foreach ( array( 'nl', 'en', 'pt' ) as $taal ) {
    same( 1, substr_count( sfp_page_config_tekst_standaard( 'wachtlijst_tekst', $taal ), '%s' ), "Wachtlijsttekst $taal heeft een plek voor de link" );
}

echo "$tests tests, $fouten fouten\n";
exit( $fouten ? 1 : 0 );
