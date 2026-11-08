<?php
/**
 * Pijler als pagina (3.33.0): de vorm Pijler hoort bij een pagina, niet bij
 * een bericht; de pijler houdt het adres van zijn pagina; links naar de
 * categorie gaan naar de pijler; het archief van de categorie blijft
 * bestaan. Draait met kleine WordPress-stubs, geen echte WordPress.
 *
 * Draaien: php tests/pijler-pagina.php
 *
 * @package SFP_Page_Config
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );

$fouten = 0;
$checks = 0;
function pp_is( $v, $wat ) {
    global $fouten, $checks;
    ++$checks;
    if ( ! $v ) {
        ++$fouten;
        echo "FOUT: $wat\n";
    }
}

/* ---------- Stubs ---------- */
class WP_Post {
    public $ID;
    public $post_type;
    public $post_status;
    public $post_content = '';
    public $post_author  = 1;
    public function __construct( $a ) {
        foreach ( $a as $k => $v ) {
            $this->$k = $v;
        }
    }
}
class WP_Term {
    public $term_id;
    public $slug;
    public $name;
    public $parent = 0;
    public function __construct( $a ) {
        foreach ( $a as $k => $v ) {
            $this->$k = $v;
        }
    }
}
class WP_Error {}

$GLOBALS['pp_posts']   = array();
$GLOBALS['pp_meta']    = array();
$GLOBALS['pp_terms']   = array();
$GLOBALS['pp_filters'] = array();
$GLOBALS['pp_actions'] = array();
$GLOBALS['pp_trans']   = array();
$GLOBALS['pp_meta_reg'] = array();

function add_action( $hook, $cb, $prio = 10, $n = 1 ) {
    $GLOBALS['pp_actions'][ $hook ][] = $cb;
}
function add_filter( $hook, $cb, $prio = 10, $n = 1 ) {
    $GLOBALS['pp_filters'][ $hook ][ $prio ][ is_string( $cb ) ? $cb : spl_object_hash( (object) $cb ) ] = array( $cb, $n );
    return true;
}
function remove_filter( $hook, $cb, $prio = 10 ) {
    unset( $GLOBALS['pp_filters'][ $hook ][ $prio ][ $cb ] );
    return true;
}
function apply_filters( $hook, $waarde, ...$args ) {
    if ( empty( $GLOBALS['pp_filters'][ $hook ] ) ) {
        return $waarde;
    }
    ksort( $GLOBALS['pp_filters'][ $hook ] );
    foreach ( $GLOBALS['pp_filters'][ $hook ] as $lijst ) {
        foreach ( $lijst as $f ) {
            $waarde = call_user_func_array( $f[0], array_slice( array_merge( array( $waarde ), $args ), 0, $f[1] ) );
        }
    }
    return $waarde;
}
function register_post_meta( $type, $sleutel, $args ) {
    $GLOBALS['pp_meta_reg'][ $type ][] = $sleutel;
}
function get_post( $p ) {
    if ( $p instanceof WP_Post ) {
        return $p;
    }
    return isset( $GLOBALS['pp_posts'][ (int) $p ] ) ? $GLOBALS['pp_posts'][ (int) $p ] : null;
}
function get_post_meta( $id, $k, $single = false ) {
    return isset( $GLOBALS['pp_meta'][ $id ][ $k ] ) ? $GLOBALS['pp_meta'][ $id ][ $k ] : '';
}
function get_post_status( $id ) {
    $p = get_post( $id );
    return $p ? $p->post_status : false;
}
function get_term( $id, $tax = '' ) {
    return isset( $GLOBALS['pp_terms'][ (int) $id ] ) ? $GLOBALS['pp_terms'][ (int) $id ] : null;
}
function is_wp_error( $x ) {
    return $x instanceof WP_Error;
}
function get_transient( $k ) {
    return isset( $GLOBALS['pp_trans'][ $k ] ) ? $GLOBALS['pp_trans'][ $k ] : false;
}
function set_transient( $k, $v, $t = 0 ) {
    $GLOBALS['pp_trans'][ $k ] = $v;
}
function delete_transient( $k ) {
    unset( $GLOBALS['pp_trans'][ $k ] );
}
function get_posts( $q ) {
    $uit = array();
    foreach ( $GLOBALS['pp_posts'] as $p ) {
        if ( $q['post_type'] !== $p->post_type || ( isset( $q['post_status'] ) && $q['post_status'] !== $p->post_status ) ) {
            continue;
        }
        if ( isset( $q['meta_key'] ) && get_post_meta( $p->ID, $q['meta_key'] ) !== $q['meta_value'] ) {
            continue;
        }
        $uit[] = ( isset( $q['fields'] ) && 'ids' === $q['fields'] ) ? $p->ID : $p;
    }
    return $uit;
}
function get_permalink( $p ) {
    $p = get_post( $p );
    return $p ? 'https://voorbeeld.test/' . $p->slug . '/' : false;
}
function home_url( $pad = '' ) {
    return 'https://voorbeeld.test' . $pad;
}
function get_term_link( $term, $tax = 'category' ) {
    $term = is_object( $term ) ? $term : get_term( $term );
    return apply_filters( 'term_link', 'https://voorbeeld.test/c/' . $term->slug . '/', $term, 'category' );
}
function sfp_page_config_onderdeel_aan( $id ) {
    return ! empty( $GLOBALS['pp_aan'] );
}
function sfp_page_config_artikel_aan() {
    return ! empty( $GLOBALS['pp_aan'] );
}

/* ---------- Gegevens ---------- */
$GLOBALS['pp_aan']   = true;
$GLOBALS['pp_terms'] = array(
    76 => new WP_Term( array( 'term_id' => 76, 'slug' => 'spreekangst', 'name' => 'Spreekangst' ) ),
    80 => new WP_Term( array( 'term_id' => 80, 'slug' => 'storytelling', 'name' => 'Storytelling' ) ),
);
$GLOBALS['pp_posts'] = array(
    22138 => new WP_Post( array( 'ID' => 22138, 'post_type' => 'page', 'post_status' => 'publish', 'slug' => 'spreekangst' ) ),
    33036 => new WP_Post( array( 'ID' => 33036, 'post_type' => 'post', 'post_status' => 'draft', 'slug' => 'spreekangst-2' ) ),
    500   => new WP_Post( array( 'ID' => 500, 'post_type' => 'page', 'post_status' => 'publish', 'slug' => 'contact' ) ),
    600   => new WP_Post( array( 'ID' => 600, 'post_type' => 'page', 'post_status' => 'draft', 'slug' => 'storytelling' ) ),
);
$GLOBALS['pp_meta'] = array(
    22138 => array( 'sfp_vorm' => 'pijler', 'sfp_gids_van' => 76 ),
    33036 => array( 'sfp_vorm' => 'pijler', 'sfp_gids_van' => 76 ),
    600   => array( 'sfp_vorm' => 'pijler', 'sfp_gids_van' => 80 ),
);

require dirname( __DIR__ ) . '/includes/vormen.php';
sfp_page_config_vormen_meta();

/* ---------- Vorm ---------- */
pp_is( 'pijler' === sfp_page_config_vorm( 22138 ), 'pagina met vorm Pijler is een pijler' );
pp_is( 'artikel' === sfp_page_config_vorm( 33036 ), 'bericht is altijd een artikel, ook met sfp_vorm pijler' );
pp_is( '' === sfp_page_config_vorm( 500 ), 'gewone pagina heeft geen vorm' );
pp_is( sfp_page_config_is_pijlerpagina( 22138 ) && ! sfp_page_config_is_pijlerpagina( 500 ) && ! sfp_page_config_is_pijlerpagina( 33036 ), 'is_pijlerpagina' );
pp_is( in_array( 'sfp_vorm', $GLOBALS['pp_meta_reg']['page'], true ) && in_array( 'sfp_gids_van', $GLOBALS['pp_meta_reg']['page'], true ) && in_array( 'sfp_zelftest', $GLOBALS['pp_meta_reg']['page'], true ), 'vorm, gids van en zelftest geregistreerd bij pagina' );
pp_is( empty( $GLOBALS['pp_meta_reg']['post'] ), 'bij berichten geen pijlergegevens meer' );

/* ---------- Gidsen ---------- */
$gidsen = sfp_page_config_gidsen( true );
pp_is( array( 76 => 22138 ) === $gidsen, 'gids van 76 is pagina 22138; concept 600 telt niet' );
pp_is( 76 === (int) sfp_page_config_is_gids( 22138 )->term_id, 'is_gids op de pagina' );
pp_is( null === sfp_page_config_is_gids( 33036 ), 'bericht is nooit een gids' );
pp_is( 80 === (int) sfp_page_config_is_gids( 600 )->term_id, 'concept-pijlerpagina toont zijn categorie (voorbeeld)' );
pp_is( null === sfp_page_config_gids_term( 500 ), 'zonder keuze geen categorie' );

/* ---------- Adres ---------- */
pp_is( 'https://voorbeeld.test/spreekangst/' === get_permalink( 22138 ), 'adres blijft de pagina-URL' );
pp_is( empty( $GLOBALS['pp_filters']['post_link'] ) && empty( $GLOBALS['pp_filters']['request'] ) && empty( $GLOBALS['pp_actions']['template_redirect'] ), 'geen adres van de categorie, geen verzoek, geen doorsturen' );
pp_is( 'https://voorbeeld.test/spreekangst/' === get_term_link( 76 ), 'categorielink gaat naar de pijlerpagina' );
pp_is( 'https://voorbeeld.test/c/storytelling/' === get_term_link( 80 ), 'categorie met alleen een concept-pijler: gewoon archief' );
pp_is( 'https://voorbeeld.test/c/spreekangst/' === sfp_page_config_categorie_archief_url( $GLOBALS['pp_terms'][76] ), 'archief van de categorie blijft bereikbaar' );
pp_is( 'https://voorbeeld.test/spreekangst/' === get_term_link( 76 ), 'na het archiefadres staat de filter weer aan' );
$GLOBALS['pp_aan']   = false;
$GLOBALS['pp_trans'] = array();
pp_is( 'https://voorbeeld.test/c/spreekangst/' === get_term_link( 76 ), 'onderdeel Pijler uit: categorielink ongewijzigd' );
$GLOBALS['pp_aan'] = true;

/* ---------- Opmaak, editor, schema (broncode) ---------- */
$basis   = dirname( __DIR__ ) . '/';
$artikel = (string) file_get_contents( $basis . 'includes/artikel.php' );
$vormen  = (string) file_get_contents( $basis . 'includes/vormen.php' );
$vervolg = (string) file_get_contents( $basis . 'includes/vervolg.php' );
$editor  = (string) file_get_contents( $basis . 'includes/editor.php' );
$js      = (string) file_get_contents( $basis . 'assets/editor/deze-pagina.js' );
$lr      = (string) file_get_contents( $basis . 'includes/longread.php' );
pp_is( false !== strpos( $artikel, "is_page() && function_exists( 'sfp_page_config_is_pijlerpagina' )" ), 'artikelopmaak op een pijlerpagina' );
pp_is( false !== strpos( $vervolg, "'page' === \$post->post_type" ) && false !== strpos( $vervolg, 'sfp_page_config_gids_term( $post )' ), 'vervolg van de pijler op een pijlerpagina' );
pp_is( false !== strpos( $lr, 'sfp_page_config_is_pijlerpagina( $post_id )' ), 'geen meelopende inhoudsopgave op een pijlerpagina' );
pp_is( false !== strpos( $vormen, "is_singular( 'page' )" ) && false !== strpos( $vormen, "'mainEntityOfPage' => array( '@id' => \$adres . '#webpage' )" ) && false !== strpos( $vormen, "'publisher'        => array( '@id' => \$home . '#organization' )" ), 'volledig Article op de pijlerpagina, gekoppeld aan SureRank' );
pp_is( false !== strpos( $editor, "'page' === \$post->post_type && function_exists( 'sfp_page_config_pijler_aan' )" ), 'editorstand vorm bij pagina' );
pp_is( false !== strpos( $js, "( S.pijler && 'page' === editor.type )" ) && false === strpos( $js, "S.pijler && 'post' === editor.type" ), 'paneel: pijler kiezen bij pagina, niet bij bericht' );
pp_is( false === strpos( $js, 'sfp_eigen_adres' ) && false === strpos( $vormen, 'sfp_eigen_adres' ), 'keuze eigen adres is weg' );
pp_is( false === strpos( $vormen . $js, "\u{2014}" ), 'geen em dash' );

echo $fouten ? "$fouten van $checks controles faalden\n" : "OK: $checks checks\n";
exit( $fouten ? 1 : 0 );
