<?php
/**
 * Run with: php tests/snippets-groep.php
 *
 * Snippetbeheer, groepen (3.22.2): een snippet in een bestaande groep zetten
 * via /sfp/v1/snippets/<id>/groep, zoals het snippet "Snippets beheren via
 * REST" dat deed. Geen nieuwe groepen, wel een melding met de bestaande.
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
error_reporting( E_ALL );
set_error_handler( function ( $s, $m, $f, $l ) { throw new ErrorException( $m, 0, $s, $f, $l ); } );
define( 'ABSPATH', __DIR__ . '/' );

class WP_Error {
    public $code;
    public $data;
    function __construct( $c, $m = '', $d = array() ) { $this->code = $c; $this->data = $d; }
}
class WP_Post {
    public $ID;
    public $post_type = 'asenha_code_snippet';
    public $post_status = 'publish';
    function __construct( $id ) { $this->ID = $id; }
}
$GLOBALS['routes']  = array();
$GLOBALS['taxen']   = array( 'asenha_code_snippet_category' );
$GLOBALS['termen']  = array( 'Formulieren' => 5, 'Beheer' => 6 );
$GLOBALS['gezet']   = array();
$GLOBALS['opties']  = array();
function add_action() {}
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function get_post( $id ) { return 7 === (int) $id ? new WP_Post( 7 ) : null; }
function get_object_taxonomies( $t ) { return $GLOBALS['taxen']; }
function get_term_by( $veld, $naam, $tax ) {
    return isset( $GLOBALS['termen'][ $naam ] ) ? (object) array( 'term_id' => $GLOBALS['termen'][ $naam ], 'name' => $naam ) : false;
}
function get_terms( $a ) { return array_keys( $GLOBALS['termen'] ); }
function wp_set_object_terms( $id, $ids, $tax, $toevoegen ) { $GLOBALS['gezet'][ $id ] = array( $ids, $tax, $toevoegen ); return $ids; }
function get_the_terms( $id, $tax ) {
    if ( empty( $GLOBALS['gezet'][ $id ] ) ) {
        return false;
    }
    $namen = array_flip( $GLOBALS['termen'] );
    return array_map( function ( $t ) use ( $namen ) { return (object) array( 'name' => $namen[ $t ] ); }, $GLOBALS['gezet'][ $id ][0] );
}
function wp_list_pluck( $l, $v ) { return array_map( function ( $o ) use ( $v ) { return $o->$v; }, $l ); }
function get_option( $k, $d = false ) { return $GLOBALS['opties'][ $k ] ?? $d; }
function update_option( $k, $v ) { $GLOBALS['opties'][ $k ] = $v; }
function wp_date( $f ) { return '2026-11-01 20:00:00'; }
function wp_get_current_user() { return (object) array( 'user_login' => 'claude-api' ); }
function register_rest_route( $ns, $r, $a ) { $GLOBALS['routes'][ $r ] = $a; }

require dirname( __DIR__ ) . '/includes/snippets.php';

$checks = 0;
function same( $e, $a, $m ) {
    global $checks;
    ++$checks;
    if ( $e !== $a ) {
        throw new RuntimeException( $m . ': expected ' . var_export( $e, true ) . ', got ' . var_export( $a, true ) );
    }
}

same( 'asenha_code_snippet_category', sfp_page_config_snippets_groep_tax(), 'taxonomie gevonden' );
same( array(), sfp_page_config_snippets_groepen( 7 ), 'nog geen groep' );
same( array( 'Formulieren' ), sfp_page_config_snippets_zet_groep( 7, ' Formulieren ' ), 'groep gezet' );
same( array( array( 5 ), 'asenha_code_snippet_category', false ), $GLOBALS['gezet'][7], 'vervangt, voegt niet toe' );
same( 'Formulieren', end( $GLOBALS['opties']['sfp_page_config_snippets_log'] )['groep'], 'in het logboek' );

$fout = sfp_page_config_snippets_zet_groep( 7, 'Bestaat niet' );
same( 'sfp_snippets_groep_onbekend', $fout->code, 'onbekende groep' );
same( 404, $fout->data['status'], '404' );
same( array( 'Formulieren', 'Beheer' ), $fout->data['groepen'], 'bestaande groepen in de melding' );
same( 400, sfp_page_config_snippets_zet_groep( 7, '  ' )->data['status'], 'lege naam' );
same( 404, sfp_page_config_snippets_zet_groep( 8, 'Beheer' )->data['status'], 'onbekend snippet' );

$GLOBALS['taxen'] = array( 'iets_anders', 'nog_een' );
same( '', sfp_page_config_snippets_groep_tax(), 'geen herkenbare taxonomie' );
same( 503, sfp_page_config_snippets_zet_groep( 7, 'Beheer' )->data['status'], 'geen groepen bij ASE' );
$GLOBALS['taxen'] = array( 'asenha_code_snippet_category' );

sfp_page_config_snippets_rest();
same( true, isset( $GLOBALS['routes']['/snippets/(?P<id>\d+)/groep'] ), 'route groep' );
same( 'POST', $GLOBALS['routes']['/snippets/(?P<id>\d+)/groep']['methods'], 'POST' );
foreach ( array( '/snippets', '/snippets/(?P<id>\d+)', '/snippets/(?P<id>\d+)/actief', '/snippets/(?P<id>\d+)/prullenbak' ) as $r ) {
    same( true, isset( $GLOBALS['routes'][ $r ] ), 'bestaande route ' . $r );
}
same( true, false !== strpos( file_get_contents( dirname( __DIR__ ) . '/includes/snippets.php' ), "'groepen'      => sfp_page_config_snippets_groepen(" ), 'groepen in de lijst' );

echo "OK: {$checks} checks\n";
