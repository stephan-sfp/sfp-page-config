<?php
/**
 * Tests voor het Fact-checked-paneel (3.44.0), met kleine WordPress-stubs:
 *   php tests/factcheck.php
 *
 * Controleert: het schoonmaken van de controles (datum, toekomst,
 * gebruiker, dubbel, volgorde), het label met en zonder controle (knop
 * of link naar de redactionele verantwoording), het paneel (controleur,
 * rol, datum, geschiedenis nieuwste eerst, link), de geschiedenis van
 * Inhoudelijk herzien, de startvulling en de invoercontrole.
 *
 * @package SFP_Page_Config
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );

/* ---------- Stubs ---------- */
$GLOBALS['meta']    = array();
$GLOBALS['posts']   = array();
$GLOBALS['opties']  = array();
$GLOBALS['links']   = array( 'artikel_redactie' => 'https://voorbeeld.nl/redactionele-verantwoording/', 'artikel_factcheck' => 'https://voorbeeld.nl/feitelijke-verantwoording/' );
$GLOBALS['gebruikers'] = array( 4 => 'Nina Lima', 7 => 'Stephan Borggreve' );
$GLOBALS['acties']  = array();
$GLOBALS['taal']    = 'nl';

class WP_Error {
    public $code;
    public function __construct( $code = '', $msg = '', $data = array() ) { $this->code = $code; }
}
class WP_Post {
    public $ID; public $post_type; public $post_date; public $post_modified; public $post_title = 'Titel';
    public function __construct( $a ) { foreach ( $a as $k => $v ) { $this->$k = $v; } }
}
function add_action( $h, $f, $p = 10, $n = 1 ) { $GLOBALS['acties'][ $h ][] = $f; }
function add_filter( $h, $f, $p = 10, $n = 1 ) {}
function apply_filters( $h, $v ) { return $v; }
function do_action( $h, ...$a ) { foreach ( isset( $GLOBALS['acties'][ $h ] ) ? $GLOBALS['acties'][ $h ] : array() as $f ) { $f( ...$a ); } }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function wp_timezone() { return new DateTimeZone( 'Europe/Amsterdam' ); }
function wp_date( $f, $ts = null ) { $d = new DateTime( '@' . ( null === $ts ? time() : $ts ) ); $d->setTimezone( wp_timezone() ); return $d->format( $f ); }
function get_post( $p ) { if ( $p instanceof WP_Post ) { return $p; } return isset( $GLOBALS['posts'][ $p ] ) ? $GLOBALS['posts'][ $p ] : null; }
function get_post_timestamp( $p, $soort = 'date' ) { $p = get_post( $p ); return strtotime( 'date' === $soort ? $p->post_date : $p->post_modified ); }
function get_post_meta( $id, $k, $single = true ) { return isset( $GLOBALS['meta'][ $id ][ $k ] ) ? $GLOBALS['meta'][ $id ][ $k ] : ''; }
function update_post_meta( $id, $k, $v ) {
    $bestond = isset( $GLOBALS['meta'][ $id ][ $k ] );
    if ( $bestond ) { do_action( 'update_postmeta', 1, $id, $k, $v ); }
    $GLOBALS['meta'][ $id ][ $k ] = $v;
    do_action( $bestond ? 'updated_post_meta' : 'added_post_meta', 1, $id, $k, $v );
    return true;
}
function get_userdata( $id ) { return isset( $GLOBALS['gebruikers'][ $id ] ) ? (object) array( 'ID' => $id ) : false; }
function get_the_author_meta( $k, $id ) { return isset( $GLOBALS['gebruikers'][ $id ] ) ? $GLOBALS['gebruikers'][ $id ] : ''; }
function get_users( $a ) { $u = array(); foreach ( $GLOBALS['gebruikers'] as $id => $n ) { $u[] = (object) array( 'ID' => $id, 'display_name' => $n ); } return $u; }
function get_avatar( $id, $s, $d, $alt, $a ) { return '<img class="' . $a['class'] . '" src="https://secure.gravatar.com/avatar/x" alt="' . $alt . '" width="' . $s . '">'; }
function get_option( $k, $d = false ) { return isset( $GLOBALS['opties'][ $k ] ) ? $GLOBALS['opties'][ $k ] : $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['opties'][ $k ] = $v; return true; }
function post_type_exists( $t ) { return in_array( $t, array( 'post', 'begrippen' ), true ); }
function sfp_page_config_begrip_aan() { return true; }
function sfp_page_config_link_instelling( $k ) { return isset( $GLOBALS['links'][ $k ] ) ? $GLOBALS['links'][ $k ] : ''; }
function sfp_page_config_artikel_icoon( $n ) { return '<svg data-i="' . $n . '"></svg>'; }
function sfp_page_config_artikel_tekst( $k ) {
    $t = array(
        'nl' => array( 'gecheckt' => 'Gefactcheckt', 'fc_kop' => 'Gecontroleerd door', 'fc_rol' => 'Redactie en factcheck', 'fc_op' => 'Gecontroleerd op %s', 'fc_log' => 'Geschiedenis', 'fc_controle' => 'Gecontroleerd', 'fc_herzien' => 'Inhoudelijk herzien', 'fc_gepub' => 'Gepubliceerd', 'fc_link' => 'Zo controleren we onze artikelen' ),
    );
    return $t['nl'][ $k ];
}
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_url( $s ) { return (string) $s; }
function wp_list_pluck( $l, $k ) { return array_map( function ( $x ) use ( $k ) { return $x[ $k ]; }, $l ); }
function register_post_meta() {}
function register_rest_route() {}
function current_user_can() { return true; }

require __DIR__ . '/../includes/factcheck.php';

$fouten = 0;
$goed   = 0;
$check  = function ( $naam, $ok, $detail = '' ) use ( &$fouten, &$goed ) {
    if ( $ok ) { ++$goed; return; }
    ++$fouten;
    echo "FOUT: $naam" . ( '' !== $detail ? ' (' . $detail . ')' : '' ) . "\n";
};

/* ---------- Schoonmaken ---------- */
$morgen2 = wp_date( 'Y-m-d', time() + 3 * DAY_IN_SECONDS );
$schoon  = sfp_page_config_factcheck_schoon(
    array(
        array( 'datum' => '2026-11-07', 'door' => 4 ),
        array( 'datum' => '2026-11-07', 'door' => 4 ),
        array( 'datum' => '2026-02-30', 'door' => 4 ),
        array( 'datum' => $morgen2, 'door' => 4 ),
        array( 'datum' => '2026-10-01', 'door' => 99 ),
        array( 'datum' => '7-11-2026', 'door' => 4 ),
        (object) array( 'datum' => '2026-03-05', 'door' => '7' ),
        'onzin',
    )
);
$check( 'schoon: twee geldige regels over', 2 === count( $schoon ), json_encode( $schoon ) );
$check( 'schoon: oudste eerst', isset( $schoon[0] ) && '2026-03-05' === $schoon[0]['datum'] && 7 === $schoon[0]['door'] );
$check( 'schoon: geen lijst wordt leeg', array() === sfp_page_config_factcheck_schoon( 'x' ) );

/* ---------- Artikel zonder controle ---------- */
$GLOBALS['posts'][10] = new WP_Post( array( 'ID' => 10, 'post_type' => 'post', 'post_date' => '2026-03-05 20:44:14', 'post_modified' => '2026-11-07 10:00:00' ) );
$GLOBALS['meta'][10]  = array( 'sfp_herzien' => '2026-11-07' );
$label = sfp_page_config_factcheck_label( 10 );
$check( 'zonder controle: link naar redactionele verantwoording', false !== strpos( $label, '<a class="sfp-art-kop__check" href="https://voorbeeld.nl/redactionele-verantwoording/">' ), $label );
$check( 'zonder controle: geen paneel', '' === sfp_page_config_factcheck_paneel( 10 ) );
$GLOBALS['links']['artikel_redactie'] = '';
$check( 'zonder redactie: reserve feitelijke verantwoording', false !== strpos( sfp_page_config_factcheck_label( 10 ), 'feitelijke-verantwoording' ) );
$GLOBALS['links']['artikel_factcheck'] = '';
$check( 'zonder links: span', 0 === strpos( sfp_page_config_factcheck_label( 10 ), '<span class="sfp-art-kop__check">' ) );
$GLOBALS['links'] = array( 'artikel_redactie' => 'https://voorbeeld.nl/redactionele-verantwoording/', 'artikel_factcheck' => 'https://voorbeeld.nl/feitelijke-verantwoording/' );

/* ---------- Startvulling ---------- */
$check( 'vastleggen', true === sfp_page_config_factcheck_leg_vast( 10, '2026-11-07', 4 ) );
$check( 'nogmaals hetzelfde: niets', false === sfp_page_config_factcheck_leg_vast( 10, '2026-11-07', 4 ) );
$check( 'standaard controleur onthouden', 4 === sfp_page_config_factcheck_standaard() );
$label = sfp_page_config_factcheck_label( 10 );
$check( 'met controle: knop die het paneel opent', false !== strpos( $label, '<button type="button" class="sfp-art-kop__check sfp-art-kop__check--knop" aria-expanded="false" aria-controls="sfp-art-paneel-check" data-sfp-paneel data-sfp-groep="kop">' ), $label );
$check( 'met controle: pijl', false !== strpos( $label, 'class="sfp-art-kop__pijl"' ) );

$paneel = sfp_page_config_factcheck_paneel( 10 );
$check( 'paneel verborgen', false !== strpos( $paneel, 'id="sfp-art-paneel-check" hidden' ) );
$check( 'paneel: naam', false !== strpos( $paneel, '<span class="sfp-art-fc__naam">Nina Lima</span>' ) );
$check( 'paneel: rol', false !== strpos( $paneel, 'Redactie en factcheck' ) );
$check( 'paneel: datum van controle', false !== strpos( $paneel, 'Gecontroleerd op <span class="sfp-art-fc__datum">7 Nov 2026</span>' ), $paneel );
$check( 'paneel: foto', false !== strpos( $paneel, 'class="sfp-art-fc__foto"' ) );
$check( 'paneel: link redactionele verantwoording', false !== strpos( $paneel, '<a class="sfp-pijl" href="https://voorbeeld.nl/redactionele-verantwoording/">' ) );
preg_match_all( '/<li[^>]*><time datetime="([^"]+)">[^<]*<\/time><(?:b|span)>(?:<svg[^>]*><\/svg>)?([^<]+)</', $paneel, $m );
$check( 'geschiedenis: controle, herziening, publicatie', array( 'Gecontroleerd', 'Inhoudelijk herzien', 'Gepubliceerd' ) === $m[2], json_encode( $m[2] ) );
$check( 'geschiedenis: data', array( '2026-11-07', '2026-11-07', '2026-03-05' ) === $m[1], json_encode( $m[1] ) );

/* ---------- Herziening na de controle: label blijft, geschiedenis groeit ---------- */
update_post_meta( 10, 'sfp_herzien', '2026-11-09' );
$check( 'vorige herziening bewaard', array( '2026-11-07' ) === get_post_meta( 10, 'sfp_herzien_log', true ) );
$check( 'label blijft na herziening', false !== strpos( sfp_page_config_factcheck_label( 10 ), 'sfp-art-kop__check--knop' ) );
$g = array_map( function ( $r ) { return $r[1] . ' ' . $r[3]; }, sfp_page_config_factcheck_geschiedenis( 10 ) );
$check( 'geschiedenis na herziening', array( 'herzien 2026-11-09', 'controle 2026-11-07', 'herzien 2026-11-07', 'gepub 2026-03-05' ) === $g, json_encode( $g ) );

/* Tweede controle door een ander. */
sfp_page_config_factcheck_leg_vast( 10, '2026-11-10', 7 );
$check( 'nieuwste controleur in het paneel', false !== strpos( sfp_page_config_factcheck_paneel( 10 ), 'Stephan Borggreve' ) );
$check( 'standaard volgt de nieuwste', 7 === sfp_page_config_factcheck_standaard() );

/* ---------- Herziening op de publicatiedag telt niet ---------- */
$GLOBALS['posts'][11] = new WP_Post( array( 'ID' => 11, 'post_type' => 'post', 'post_date' => '2026-11-02 09:00:00', 'post_modified' => '2026-11-02 11:00:00' ) );
$GLOBALS['meta'][11]  = array( 'sfp_herzien' => '2026-11-02' );
sfp_page_config_factcheck_leg_vast( 11, '2026-11-02', 4 );
$g = array_map( function ( $r ) { return $r[1]; }, sfp_page_config_factcheck_geschiedenis( 11 ) );
$check( 'geen herziening op de publicatiedag', array( 'controle', 'gepub' ) === $g, json_encode( $g ) );

/* ---------- Begrip ---------- */
$GLOBALS['posts'][12] = new WP_Post( array( 'ID' => 12, 'post_type' => 'begrippen', 'post_date' => '2026-01-10 09:00:00', 'post_modified' => '2026-06-01 11:00:00' ) );
sfp_page_config_factcheck_leg_vast( 12, '2026-06-02', 4 );
$label = sfp_page_config_factcheck_label( 12, 'sfp-art-maker-check', 'maker' );
$check( 'begrip: eigen paneel en groep', false !== strpos( $label, 'aria-controls="sfp-art-maker-check" data-sfp-paneel data-sfp-groep="maker"' ) );
$g = array_map( function ( $r ) { return $r[1] . ' ' . $r[3]; }, sfp_page_config_factcheck_geschiedenis( 12 ) );
$check( 'begrip: herziening uit laatste wijziging', array( 'controle 2026-06-02', 'herzien 2026-06-01', 'gepub 2026-01-10' ) === $g, json_encode( $g ) );

/* ---------- Andere soort: geen controle mogelijk ---------- */
$GLOBALS['posts'][13] = new WP_Post( array( 'ID' => 13, 'post_type' => 'page', 'post_date' => '2026-01-10 09:00:00', 'post_modified' => '2026-06-01 11:00:00' ) );
$check( 'pagina: niet vastleggen', false === sfp_page_config_factcheck_leg_vast( 13, '2026-06-02', 4 ) );

/* ---------- Invoer ---------- */
$check( 'invoer: goed', array( '2026-11-01', 4 ) === sfp_page_config_factcheck_invoer( array( 'datum' => '2026-11-01', 'door' => 4 ) ) );
$check( 'invoer: toekomst', is_wp_error( sfp_page_config_factcheck_invoer( array( 'datum' => $morgen2, 'door' => 4 ) ) ) );
$check( 'invoer: onbekende gebruiker', is_wp_error( sfp_page_config_factcheck_invoer( array( 'datum' => '2026-11-01', 'door' => 99 ) ) ) );
$check( 'invoer: leeg', is_wp_error( sfp_page_config_factcheck_invoer( array() ) ) );

echo ( $fouten ? "FOUTEN: $fouten, " : 'OK: ' ) . ( $goed + $fouten ) . " checks\n";
exit( $fouten ? 1 : 0 );
