<?php
/**
 * Tests voor de banner (3.17.0); stubs gelijk aan tests/wachtlijst.php.
 * Oorspronkelijk: tests voor de wachtlijst (3.17.0): toekomstige groepen, [cursus_datum] met
 * wachtlijsttekst en intro, [cursus_tijd] met dezelfde nummering, het wisselen
 * van het formulier en de klassen sfp-met-data en sfp-zonder-data. Met kleine
 * WordPress-stubs. Elk scenario gebruikt een eigen post_id, want de code
 * onthoudt per post_id wat hij al las.
 *
 * Draaien: php tests/banner.php
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
function get_transient( $k = '' ) {
    return 'sfp_dashboard_pages_training' === $k ? ( $GLOBALS['training_ids'] ?? false ) : false;
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
function get_permalink( $id ) {
    return 'https://voorbeeld.test/pagina-' . (int) $id . '/';
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


$GLOBALS['front'] = false;
$GLOBALS['titels'] = array();
function get_the_title( $id ) {
    return $GLOBALS['titels'][ $id ] ?? ( 'Pagina ' . $id );
}
function esc_url( $u ) {
    return (string) $u;
}
function esc_url_raw( $u ) {
    return filter_var( (string) $u, FILTER_VALIDATE_URL ) ? (string) $u : '';
}
function wp_strip_all_tags( $s ) {
    return trim( strip_tags( (string) $s ) );
}
function is_front_page() {
    return $GLOBALS['front'];
}
function get_posts() {
    return array();
}

require dirname( __DIR__ ) . '/includes/instellingen.php';
require dirname( __DIR__ ) . '/includes/teksten.php';
require dirname( __DIR__ ) . '/includes/wachtlijst.php';
require dirname( __DIR__ ) . '/includes/banner.php';

function training( $id, $dagen, $titel ) {
    $GLOBALS['types'][ $id ]  = 'page';
    $GLOBALS['status'][ $id ] = 'publish';
    $GLOBALS['titels'][ $id ] = $titel;
    $GLOBALS['meta'][ $id ]   = array(
        'sfp_page_type'  => 'training',
        'sfp_cursusdata' => json_encode( array_map( function ( $d ) { return array( 'data' => $d ); }, $dagen ) ),
    );
    return $id;
}
function ctx( $pagina = 0, $front = false, $inhoud = false ) {
    return array( 'pagina' => $pagina, 'front' => $front, 'inhoud' => $inhoud );
}
function instel( $w ) {
    sfp_page_config_instellingen_bewaar( $w );
}

instel( array( 'onderdelen' => array( 'cursusdata' => '1' ) ) );
training( 22577, array( array( '2026-11-26', '2026-12-03' ) ), 'Presentatietraining' );
training( 22603, array( array( '2026-10-20' ), array( '2027-01-14' ) ), 'Spreekangsttraining' );
// sfp_page_config_banner_cursussen leest de lijst trainingen uit de transient.
$GLOBALS['training_ids'] = array( 22577, 22603 );

/* ---------- Schoonmaken ---------- */

$s = sfp_page_config_banner_schoon(
    array(
        'kleur'  => 'paars',
        'cursus' => array( 'aan' => '0', 'waar' => 'raar', 'niet' => '5, 6 ,x' ),
        'aanbod' => array(
            array( 'titel' => '', 'pagina' => 3 ),
            array( 'titel' => 'Boek', 'pagina' => 0, 'link' => 'geen adres' ),
            array( 'titel' => '<b>De FASTER-methode</b>', 'pagina' => 31375, 'van' => '2026-13-45', 'tot' => '2026-11-30' ),
        ),
    )
);
same( '', $s['kleur'], 'Onbekende kleurrol valt weg' );
same( '0', $s['cursus']['aan'], 'Cursusbanner uit bewaard' );
same( 'overal', $s['cursus']['waar'], 'Onbekende plaatsing wordt overal' );
same( array( 5, 6 ), $s['cursus']['niet'], 'Uitzonderingen als lijst getallen' );
same( 1, count( $s['aanbod'] ), 'Aanbod zonder titel of bestemming valt weg' );
same( 'De FASTER-methode', $s['aanbod'][0]['titel'], 'HTML uit de titel' );
same( '', $s['aanbod'][0]['van'], 'Ongeldige datum leeg' );
same( '1', sfp_page_config_banner_schoon( array() )['cursus']['aan'], 'Cursusbanner staat standaard aan' );

/* ---------- Cursusbanner ---------- */

$k = sfp_page_config_banner_kandidaten( ctx( 1 ), '2026-10-29' );
same( 2, count( $k ), 'Twee toekomstige groepen (Spreekangst 20-10 is verlopen)' );
bevat( 'Nog plekken vrij: <strong>Presentatietraining</strong>, start 2026-11-26.', $k[0]['html'], 'Tekst met naam en datum' );
bevat( 'href="https://voorbeeld.test/pagina-22577/#inschrijven"', $k[0]['html'], 'Link naar het formulier op de trainingspagina' );
same( '2026-11-26', $k[0]['tot'], 'Tot en met de eerste lesdag' );
same( 0, sfp_page_config_banner_keuze( $k, '2026-11-26' ), 'Op de eerste lesdag nog de eerste groep' );
same( 1, sfp_page_config_banner_keuze( $k, '2026-11-27' ), 'Daarna de volgende groep' );
same( -1, sfp_page_config_banner_keuze( $k, '2027-01-15' ), 'Alles voorbij: geen banner' );

$k = sfp_page_config_banner_kandidaten( ctx( 22577 ), '2026-10-29' );
same( 'c22603-20270114', $k[0]['id'], 'Op de trainingspagina zelf de andere training' );

/* ---------- Aanbod met voorrang ---------- */

$GLOBALS['types'][31375] = 'page'; $GLOBALS['status'][31375] = 'publish';
$GLOBALS['types'][31382] = 'page'; $GLOBALS['status'][31382] = 'publish';
instel(
    array(
        'banner' => array(
            'aanbod' => array(
                array( 'label' => 'Nieuw boek', 'titel' => 'De FASTER-methode', 'extra' => 'Presale begint 1 december.', 'linktekst' => 'Zet jezelf op de wachtlijst', 'pagina' => 31375, 'tot' => '2026-11-30', 'niet' => array( 31382 ) ),
                array( 'label' => 'Nieuw boek', 'titel' => 'De FASTER-methode', 'linktekst' => 'Bestel nu', 'pagina' => 31375, 'van' => '2026-12-01', 'waar' => 'inhoud', 'ook' => array( 381 ) ),
            ),
        ),
    )
);
$k = sfp_page_config_banner_kandidaten( ctx( 1, false, false ), '2026-10-29' );
same( 'aanbod', $k[0]['soort'], 'Aanbod gaat voor de cursus' );
same( 0, sfp_page_config_banner_keuze( $k, '2026-10-29' ), 'Presale-banner nu' );
bevat( 'Nieuw boek: <strong>De FASTER-methode</strong>.', $k[0]['html'], 'Soort en titel' );
bevat( 'sfp-banner__extra', $k[0]['html'], 'Extra tekst los, voor de telefoon' );
same( 'cursus', $k[ sfp_page_config_banner_keuze( $k, '2026-12-01' ) ]['soort'], 'Op een gewone pagina na 30 november de cursus (bestelbanner alleen op inhoud)' );
$k = sfp_page_config_banner_kandidaten( ctx( 0, true, false ), '2026-10-29' );
bevat( '>Bestel nu</a>', $k[ sfp_page_config_banner_keuze( $k, '2026-12-01' ) ]['html'], 'Op de homepage vanaf 1 december de bestelbanner' );
$k = sfp_page_config_banner_kandidaten( ctx( 381, false, false ), '2026-12-02' );
same( 'aanbod', $k[ sfp_page_config_banner_keuze( $k, '2026-12-02' ) ]['soort'], 'Ook op een gekozen pagina' );
$k = sfp_page_config_banner_kandidaten( ctx( 31382 ), '2026-10-29' );
same( 'cursus', $k[ sfp_page_config_banner_keuze( $k, '2026-10-29' ) ]['soort'], 'Uitgezonderde pagina: geen boekbanner' );
$k = sfp_page_config_banner_kandidaten( ctx( 31375 ), '2026-10-29' );
same( 'cursus', $k[0]['soort'], 'Op de boekpagina zelf geen boekbanner' );
$k = sfp_page_config_banner_kandidaten( ctx( 1 ), '2026-12-05' );
same( 'cursus', $k[0]['soort'], 'Verlopen aanbod staat er niet meer in' );

/* ---------- Cursus uit ---------- */

instel( array( 'banner' => array( 'cursus' => array( 'aan' => '0' ) ) ) );
same( array(), sfp_page_config_banner_kandidaten( ctx( 1 ), '2026-10-29' ), 'Cursusbanner uit en geen aanbod: niets' );
instel( array( 'banner' => array(), 'onderdelen' => array( 'cursusdata' => '0' ) ) );
same( array(), sfp_page_config_banner_kandidaten( ctx( 1 ), '2026-10-29' ), 'Cursusdata uit: geen cursusbanner' );

/* ---------- Norm en teksten ---------- */

same( 'links', sfp_page_config_norm()['banner.kleur']['norm'], 'Norm kleur' );
foreach ( array( 'nl', 'en', 'pt' ) as $taal ) {
    $t = sfp_page_config_tekst_standaard( 'banner_cursus', $taal );
    same( true, false !== strpos( $t, '%1$s' ) && false !== strpos( $t, '%2$s' ), "Cursustekst $taal met naam en datum" );
}

echo "$tests tests, $fouten fouten\n";
exit( $fouten ? 1 : 0 );
