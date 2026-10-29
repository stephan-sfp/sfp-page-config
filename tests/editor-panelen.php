<?php
/**
 * Run with: php tests/editor-panelen.php
 *
 * Controles voor 3.19.0 (editor-panelen en beheer) met kleine WordPress-stubs:
 * eigen reviews (invoerschema, bijwerken zonder velden te verliezen, 400 en
 * 404, de abilities en het akkoord bij weghalen), het pdf-adres dat de plugin
 * zelf maakt, de netwerknorm voor het adres van een berichttype met
 * doorsturen, de Nederlandse namen in het beheer, de editor zonder vrije
 * kleur en maat in een vorm, een link van de eigen site als pagina, het
 * sjabloon van een begrip en de nieuwe teksten.
 * Vervangt geen proef in een echte WordPress met een browser.
 */
error_reporting( E_ALL );
set_error_handler(
    function ( $severity, $message, $file, $line ) {
        throw new ErrorException( $message, 0, $severity, $file, $line );
    }
);
define( 'ABSPATH', __DIR__ . '/' );
define( 'SFP_PAGE_CONFIG_DIR', dirname( __DIR__ ) . '/' );
define( 'SFP_PAGE_CONFIG_URL', 'https://example.test/wp-content/plugins/sfp-page-config/' );
define( 'SFP_PAGE_CONFIG_VERSION', 'test' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );

/* ---------- Stubs ---------- */

$GLOBALS['options']    = array();
$GLOBALS['transients'] = array();
$GLOBALS['locale']     = 'en_GB';
$GLOBALS['admin']      = false;
$GLOBALS['caps']       = true;
$GLOBALS['types']      = array();
$GLOBALS['paginas']    = array(); // id => array( uri, status, type )
$GLOBALS['redirect']   = null;
$GLOBALS['is404']      = false;
$GLOBALS['aan']        = array( 'reviews' => true, 'berichttypes' => true, 'tools' => false, 'artikel' => true );

class WP_Error {
    public $code;
    public $message;
    public $data;
    public function __construct( $code = '', $message = '', $data = null ) {
        $this->code    = $code;
        $this->message = $message;
        $this->data    = $data;
    }
    public function get_error_code() {
        return $this->code;
    }
    public function get_error_message() {
        return $this->message;
    }
    public function get_error_data() {
        return $this->data;
    }
}
class WP_Post {
    public $ID;
    public $post_type;
    public function __construct( $id, $type ) {
        $this->ID        = $id;
        $this->post_type = $type;
    }
}
class StubRedirect extends Exception {}

function is_wp_error( $x ) {
    return $x instanceof WP_Error;
}
function get_option( $k, $d = false ) {
    return $GLOBALS['options'][ $k ] ?? $d;
}
function update_option( $k, $v, $autoload = null ) {
    $GLOBALS['options'][ $k ] = $v;
    return true;
}
function delete_option( $k ) {
    unset( $GLOBALS['options'][ $k ] );
    return true;
}
function get_transient( $k ) {
    return $GLOBALS['transients'][ $k ] ?? false;
}
function set_transient( $k, $v, $t = 0 ) {
    $GLOBALS['transients'][ $k ] = $v;
    return true;
}
function delete_transient( $k ) {
    unset( $GLOBALS['transients'][ $k ] );
    return true;
}
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function has_action() {
    return false;
}
function do_action() {}
function apply_filters( $hook, $value ) {
    return $value;
}
function is_admin() {
    return $GLOBALS['admin'];
}
function is_404() {
    return $GLOBALS['is404'];
}
function is_user_logged_in() {
    return $GLOBALS['caps'];
}
function current_user_can() {
    return $GLOBALS['caps'];
}
function get_locale() {
    return $GLOBALS['locale'];
}
function determine_locale() {
    return $GLOBALS['locale'];
}
function home_url( $path = '' ) {
    return 'https://example.test' . $path;
}
function sanitize_text_field( $s ) {
    return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $s ) ) );
}
function sanitize_textarea_field( $s ) {
    return trim( strip_tags( (string) $s ) );
}
function sanitize_key( $s ) {
    return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) );
}
function sanitize_title( $s ) {
    return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $s ) ), '-' );
}
function absint( $n ) {
    return abs( (int) $n );
}
function wp_json_encode( $v, $flags = 0 ) {
    return json_encode( $v, $flags );
}
function wp_parse_url( $url, $component = -1 ) {
    return parse_url( $url, $component );
}
function esc_url_raw( $u ) {
    return (string) $u;
}
function post_type_exists( $t ) {
    return isset( $GLOBALS['types'][ $t ] );
}
function get_post_type_object( $t ) {
    return $GLOBALS['types'][ $t ] ?? null;
}
function url_to_postid( $url ) {
    foreach ( $GLOBALS['paginas'] as $id => $p ) {
        if ( rtrim( parse_url( $url, PHP_URL_PATH ), '/' ) === '/' . $p['uri'] ) {
            return $id;
        }
    }
    return 0;
}
function get_post_type( $id ) {
    return $GLOBALS['paginas'][ $id ]['type'] ?? false;
}
function get_post_status( $id ) {
    return $GLOBALS['paginas'][ $id ]['status'] ?? false;
}
function get_page_uri( $id ) {
    return $GLOBALS['paginas'][ $id ]['uri'] ?? '';
}
function wp_safe_redirect( $url, $status = 302 ) {
    $GLOBALS['redirect'] = array( $url, $status );
    throw new StubRedirect();
}
function zet_aan( $id, $aan ) {
    $alle                      = get_option( 'sfp_settings', array() );
    $alle['onderdelen'][ $id ] = $aan ? '1' : '0';
    update_option( 'sfp_settings', $alle );
}
function sfp_page_config_artikel_aan() {
    return ! empty( $GLOBALS['aan']['artikel'] );
}

function same( $verwacht, $echt, $tekst ) {
    global $fouten, $tel;
    $tel++;
    if ( $verwacht !== $echt ) {
        $fouten++;
        echo "FOUT: $tekst\n  verwacht: " . var_export( $verwacht, true ) . "\n  kreeg:    " . var_export( $echt, true ) . "\n";
    }
}
$fouten = 0;
$tel    = 0;
zet_aan( 'reviews', true );
zet_aan( 'berichttypes', true );
zet_aan( 'tools', false );

require dirname( __DIR__ ) . '/includes/instellingen.php';
require dirname( __DIR__ ) . '/includes/teksten.php';
require dirname( __DIR__ ) . '/includes/artikel-teksten.php';
require dirname( __DIR__ ) . '/includes/reviews/class-sfp-reviews-manual.php';
require dirname( __DIR__ ) . '/includes/fundament.php';
require dirname( __DIR__ ) . '/includes/reviews-abilities.php';

// Uit beheer.php en editor.php alleen de functies die hier getest worden:
// die bestanden zelf hangen aan veel meer van WordPress.
function stub_functies( $bestand, array $namen ) {
    $code = file_get_contents( dirname( __DIR__ ) . '/includes/' . $bestand );
    foreach ( $namen as $naam ) {
        $start = strpos( $code, 'function ' . $naam . '(' );
        if ( false === $start ) {
            throw new Exception( "$naam niet gevonden in $bestand" );
        }
        $open  = strpos( $code, '{', $start );
        $diep  = 0;
        $i     = $open;
        $len   = strlen( $code );
        for ( ; $i < $len; $i++ ) {
            if ( '{' === $code[ $i ] ) {
                $diep++;
            } elseif ( '}' === $code[ $i ] ) {
                $diep--;
                if ( 0 === $diep ) {
                    break;
                }
            }
        }
        eval( substr( $code, $start, $i - $start + 1 ) ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- alleen in de test.
    }
}
stub_functies( 'beheer.php', array( 'sfp_page_config_reviews_eigen_args', 'sfp_page_config_reviews_eigen_bewaar', 'sfp_page_config_reviews_eigen_weg', 'sfp_page_config_beheer_eigen_pagina' ) );
stub_functies( 'editor.php', array( 'sfp_page_config_vorm_alineastijlen', 'sfp_page_config_editor_is_vorm', 'sfp_page_config_editor_zonder_kleur', 'sfp_page_config_editor_vorm_instellingen' ) );
stub_functies( 'zelftest.php', array( 'sfp_zelftest_pad_schoon', 'sfp_zelftest_pdf_pad_vanzelf' ) );
stub_functies( 'begrip.php', array( 'sfp_page_config_begrip_sjabloon_blokken' ) );
function sfp_page_config_artikel_posttypes() {
    return array( 'post', 'begrippen', 'tool' );
}

/* ---------- Eigen reviews ---------- */

SFP_Reviews_Manual::save_all(
    array(
        array( 'id' => 'mr_1', 'author' => 'Anne', 'rating' => 5, 'time' => '', 'datum' => '2026-01-02', 'text' => 'Goed.', 'vermelding' => 'Vertaald', 'photo' => '' ),
        array( 'id' => 'mr_2', 'author' => 'Bas', 'rating' => 4, 'time' => '6 maanden geleden', 'text' => 'Fijn.', 'photo' => '' ),
    )
);

$args = sfp_page_config_reviews_eigen_args();
same( true, $args['author']['required'] && $args['text']['required'], 'REST: naam en tekst verplicht' );
same( false, $args['id']['required'], 'REST: id niet verplicht (nieuw)' );
same( 1, preg_match( '#' . $args['datum']['pattern'] . '#', '2026-10-29' ), 'datumpatroon klopt voor een datum' );
same( 1, preg_match( '#' . $args['datum']['pattern'] . '#', '' ), 'datumpatroon laat leeg toe' );
same( 0, preg_match( '#' . $args['datum']['pattern'] . '#', '29-10-2026' ), 'datumpatroon weigert een andere vorm' );
same( true, sfp_page_config_reviews_eigen_args( true )['id']['required'], 'bijwerken: id verplicht' );

$r = sfp_page_config_reviews_eigen_bewaar( array( 'id' => 'mr_1', 'text' => 'Heel goed.' ), true );
same( 'Heel goed.', $r['text'], 'samenvoegen: tekst bijgewerkt' );
same( 'Vertaald', $r['vermelding'], 'samenvoegen: vermelding blijft' );
same( '2026-01-02', $r['datum'], 'samenvoegen: datum blijft' );
same( 5, $r['rating'], 'samenvoegen: sterren blijven' );

$r = sfp_page_config_reviews_eigen_bewaar( array( 'id' => 'mr_2', 'author' => 'Bas', 'text' => 'Fijn.', 'rating' => '3' ) );
same( 3, $r['rating'], 'beheer: sterren als tekst worden een getal' );
same( '', $r['time'], 'beheer stuurt alles: zonder time is time leeg' );

$e = sfp_page_config_reviews_eigen_bewaar( array( 'id' => 'mr_bestaat_niet', 'author' => 'X', 'text' => 'Y' ) );
same( 404, is_wp_error( $e ) ? $e->get_error_data()['status'] : 0, 'onbekend id: 404' );
same( 2, count( SFP_Reviews_Manual::get_all() ), 'onbekend id: niets toegevoegd' );

$e = sfp_page_config_reviews_eigen_bewaar( array( 'author' => '  ', 'text' => 'Y' ) );
same( 400, is_wp_error( $e ) ? $e->get_error_data()['status'] : 0, 'lege naam: 400' );

$nieuw = sfp_page_config_reviews_eigen_bewaar( array( 'author' => 'Cor', 'text' => 'Top.', 'rating' => 5 ) );
same( true, is_array( $nieuw ) && 0 === strpos( $nieuw['id'], 'mr_' ), 'toevoegen geeft de nieuwe review met id' );
same( 3, count( SFP_Reviews_Manual::get_all() ), 'toevoegen: drie reviews' );

/* Abilities */
$GLOBALS['registratie'] = array();
function wp_register_ability( $naam, $args ) {
    $GLOBALS['registratie'][ $naam ] = $args;
}
function wp_register_ability_category( $naam, $args ) {
    $GLOBALS['groepen'][ $naam ] = $args;
}
sfp_page_config_reviews_ability_groep();
sfp_page_config_reviews_abilities();
same( array( 'sfp-reviews/list', 'sfp-reviews/update', 'sfp-reviews/add', 'sfp-reviews/delete' ), array_keys( $GLOBALS['registratie'] ), 'vier abilities' );
same( true, isset( $GLOBALS['groepen']['sfp-reviews'] ), 'groep sfp-reviews' );
same( array( 'id' ), $GLOBALS['registratie']['sfp-reviews/update']['input_schema']['required'], 'update: alleen id verplicht' );
same( array( 'author', 'text' ), $GLOBALS['registratie']['sfp-reviews/add']['input_schema']['required'], 'add: naam en tekst verplicht' );
same( false, isset( $GLOBALS['registratie']['sfp-reviews/add']['input_schema']['properties']['id'] ), 'add: geen id' );
same( array( 'id', 'akkoord_stephan' ), $GLOBALS['registratie']['sfp-reviews/delete']['input_schema']['required'], 'delete: akkoord verplicht' );
same( true, $GLOBALS['registratie']['sfp-reviews/delete']['meta']['annotations']['destructive'], 'delete is destructief' );
same( true, $GLOBALS['registratie']['sfp-reviews/list']['meta']['annotations']['readonly'], 'list alleen lezen' );

$l = sfp_page_config_reviews_ability_lijst();
same( 3, $l['aantal'], 'list: drie' );
$u = sfp_page_config_reviews_ability_bijwerken( array( 'id' => 'mr_1', 'vermelding' => '' ) );
same( 'Heel goed.', $u['review']['text'], 'ability update: tekst blijft' );
same( '', $u['review']['vermelding'], 'ability update: vermelding leeg gemaakt' );
$w = sfp_page_config_reviews_ability_weg( array( 'id' => $nieuw['id'] ) );
same( 'sfp_reviews_geen_akkoord', is_wp_error( $w ) ? $w->get_error_code() : '', 'delete zonder akkoord weigert' );
same( 3, count( SFP_Reviews_Manual::get_all() ), 'delete zonder akkoord: niets weg' );
$w = sfp_page_config_reviews_ability_weg( array( 'id' => $nieuw['id'], 'akkoord_stephan' => true ) );
same( $nieuw['id'], $w['weggehaald'] ?? '', 'delete met akkoord' );
same( 2, count( SFP_Reviews_Manual::get_all() ), 'delete met akkoord: twee over' );
same( 404, sfp_page_config_reviews_eigen_weg( 'mr_weg' )->get_error_data()['status'], 'weg: onbekend id 404' );
$GLOBALS['caps'] = false;
same( 'sfp_reviews_verboden', sfp_page_config_reviews_ability_lijst()->get_error_code(), 'zonder rechten: verboden' );
$GLOBALS['caps'] = true;
zet_aan( 'reviews', false );
$GLOBALS['registratie'] = array();
sfp_page_config_reviews_abilities();
same( array(), $GLOBALS['registratie'], 'reviews uit: geen abilities' );
zet_aan( 'reviews', true );

/* ---------- Pdf-adres ---------- */

$GLOBALS['paginas'][964]   = array( 'uri' => 'presentation-self-assessment', 'status' => 'publish', 'type' => 'page' );
$GLOBALS['paginas'][32218] = array( 'uri' => 'presentatietest', 'status' => 'publish', 'type' => 'page' );
$GLOBALS['paginas'][77]    = array( 'uri' => 'ouder/teste', 'status' => 'publish', 'type' => 'page' );
same( 'presentation-self-assessment/result', sfp_zelftest_pdf_pad_vanzelf( 964, 'en' ), 'SwS: gelijk aan het huidige adres' );
same( 'presentatietest/resultaat', sfp_zelftest_pdf_pad_vanzelf( 32218, 'nl' ), 'DPS: gelijk aan het huidige adres' );
same( 'ouder/teste/resultado', sfp_zelftest_pdf_pad_vanzelf( 77, 'pt' ), 'pt en een onderliggende pagina' );
same( '', sfp_zelftest_pdf_pad_vanzelf( 0, 'nl' ), 'zonder testpagina geen adres' );
same( 'presentatietest/result', sfp_zelftest_pdf_pad_vanzelf( 32218, '' ), 'zonder taal: de taal van de site (en_GB)' );

/* ---------- Berichttypes: namen in het beheer, norm en doorsturen ---------- */

$GLOBALS['types']['begrippen'] = (object) array(
    'name'    => 'begrippen',
    'label'   => 'Begrippen',
    'labels'  => (object) array( 'name' => 'Begrippen', 'singular_name' => 'Begrip' ),
    'rewrite' => array( 'slug' => 'glossary' ),
);
$GLOBALS['types']['tool'] = (object) array(
    'name'         => 'tool',
    'label'        => 'Tools',
    'labels'       => (object) array( 'name' => 'Tools', 'singular_name' => 'Tool' ),
    'rewrite'      => array( 'slug' => 'tools' ),
    'show_in_menu' => true,
);
$GLOBALS['types']['begrippen']->labels->name = 'Glossary';
$GLOBALS['admin'] = true;
sfp_page_config_berichttypes_beheer();
same( 'Begrippen', $GLOBALS['types']['begrippen']->labels->name, 'beheer: begrippen in het Nederlands' );
same( 'Nieuw begrip toevoegen', $GLOBALS['types']['begrippen']->labels->add_new_item, 'beheer: het begrip' );
same( 'Online tools', $GLOBALS['types']['tool']->labels->menu_name, 'beheer: Online tools' );
same( 'Nieuwe online tool toevoegen', $GLOBALS['types']['tool']->labels->add_new_item, 'beheer: de online tool' );
same( false, $GLOBALS['types']['tool']->show_in_menu, 'tools uit: niet in het menu' );
same( 'Glossary', $GLOBALS['sfp_page_config_site_namen']['begrippen'], 'de naam voor de bezoeker blijft bewaard' );
$GLOBALS['admin'] = false;

// SLB: Engelse site met het Nederlandse adres en de Nederlandse namen.
update_option(
    'sfp_settings',
    array(
        'berichttypes' => array(
            'begrippen' => array( 'slug' => 'begrippen', 'with_front' => false, 'rest_base' => 'begrippen', 'labels' => array( 'name' => 'Begrippen', 'singular_name' => 'Begrip' ) ),
            'tool'      => array( 'slug' => 'tools', 'with_front' => false, 'rest_base' => 'tool', 'labels' => array( 'name' => 'Tools' ) ),
        ),
        'eigen_sleutel' => 'blijft',
        'onderdelen'    => get_option( 'sfp_settings' )['onderdelen'],
    )
);
same( array( 'nu' => 'begrippen', 'norm' => 'glossary' ), sfp_page_config_berichttype_afwijking( 'begrippen' ), 'SLB: adres wijkt af' );
same( null, sfp_page_config_berichttype_afwijking( 'tool' ), 'tools: geen afwijking' );
$af = array_values( array_filter( sfp_page_config_afwijkingen(), function ( $a ) { return 'berichttypes.begrippen' === $a['pad']; } ) );
same( '/glossary/', $af[0]['norm'] ?? '', 'Overzicht toont de afwijking met de norm' );
same( true, sfp_page_config_instelling_naar_norm( 'berichttypes.begrippen' ), 'terug naar de norm' );
$alle = get_option( 'sfp_settings' );
same( 'glossary', $alle['berichttypes']['begrippen']['slug'], 'nieuw adres' );
same( 'Glossary', $alle['berichttypes']['begrippen']['labels']['name'], 'Engelse naam' );
same( 'begrippen', $alle['berichttypes']['begrippen']['rest_base'], 'REST-basis blijft' );
same( array( 'begrippen' ), $alle['berichttypes_vorig']['begrippen'], 'oud adres onthouden' );
same( 'blijft', $alle['eigen_sleutel'], 'andere instellingen blijven' );
same( false, sfp_page_config_instelling_naar_norm( 'berichttypes.begrippen' ), 'tweede keer: niets te doen' );

// Doorsturen van het oude adres.
$GLOBALS['types']['begrippen']->rewrite = array( 'slug' => 'glossary' );
$GLOBALS['wp'] = (object) array( 'request' => 'begrippen/spreekangst' );
$GLOBALS['is404'] = true;
try {
    sfp_page_config_berichttypes_doorsturen();
} catch ( StubRedirect $x ) {
    unset( $x );
}
same( array( 'https://example.test/glossary/spreekangst/', 301 ), $GLOBALS['redirect'], 'oud adres stuurt door met de rest van het pad' );
$GLOBALS['redirect'] = null;
$GLOBALS['wp']       = (object) array( 'request' => 'begrippenlijst' );
sfp_page_config_berichttypes_doorsturen();
same( null, $GLOBALS['redirect'], 'een ander adres dat zo begint stuurt niet door' );
$GLOBALS['wp'] = (object) array( 'request' => 'begrippen' );
try {
    sfp_page_config_berichttypes_doorsturen();
} catch ( StubRedirect $x ) {
    unset( $x );
}
same( array( 'https://example.test/glossary/', 301 ), $GLOBALS['redirect'], 'archief stuurt door' );
$GLOBALS['is404'] = false;

/* ---------- Editor: vorm zonder vrije kleur en maat ---------- */

$instellingen = array(
    '__experimentalFeatures' => array(
        'color'      => array( 'text' => true, 'background' => true, 'palette' => array( 'theme' => array( 1 ) ) ),
        'typography' => array( 'fontSizes' => array( 'theme' => array( 1 ) ), 'customFontSize' => true ),
        'blocks'     => array( 'core/button' => array( 'color' => array( 'text' => true ) ) ),
    ),
    'fontSizes'              => array( 1 ),
);
$context      = (object) array( 'post' => new WP_Post( 5, 'post' ) );
$uit          = sfp_page_config_editor_vorm_instellingen( $instellingen, $context );
same( false, $uit['__experimentalFeatures']['color']['text'], 'artikel: geen tekstkleur' );
same( false, $uit['__experimentalFeatures']['color']['background'], 'artikel: geen achtergrond' );
same( array(), $uit['__experimentalFeatures']['color']['gradients'], 'artikel: geen verloop' );
same( array( 'theme' => array( 1 ) ), $uit['__experimentalFeatures']['color']['palette'], 'het palet zelf blijft (blokken van het merk)' );
same( false, $uit['__experimentalFeatures']['typography']['customFontSize'], 'artikel: geen eigen lettergrootte' );
same( array(), $uit['__experimentalFeatures']['typography']['fontSizes'], 'artikel: geen maten S tot XL' );
same( false, $uit['__experimentalFeatures']['blocks']['core/button']['color']['text'], 'ook per blok uit' );
same( true, $uit['disableCustomColors'], 'oude sleutel ook uit' );
$pagina = sfp_page_config_editor_vorm_instellingen( $instellingen, (object) array( 'post' => new WP_Post( 6, 'page' ) ) );
same( $instellingen, $pagina, 'pagina: alles blijft' );
$GLOBALS['aan']['artikel'] = false;
same( $instellingen, sfp_page_config_editor_vorm_instellingen( $instellingen, $context ), 'Artikelen uit: alles blijft' );
$GLOBALS['aan']['artikel'] = true;
same( array( 'sfp-lead', 'sfp-pijl', 'sfp-klein' ), sfp_page_config_vorm_alineastijlen(), 'drie alineastijlen in een vorm' );

/* ---------- Beheer: link van de eigen site als pagina ---------- */

$GLOBALS['paginas'][31] = array( 'uri' => 'facts', 'status' => 'publish', 'type' => 'page' );
$GLOBALS['paginas'][32] = array( 'uri' => 'oud', 'status' => 'draft', 'type' => 'page' );
same( 31, sfp_page_config_beheer_eigen_pagina( 'https://example.test/facts/' ), 'eigen link wordt de pagina' );
same( 0, sfp_page_config_beheer_eigen_pagina( 'https://elders.test/facts/' ), 'link van een andere site blijft een link' );
same( 0, sfp_page_config_beheer_eigen_pagina( 'https://example.test/oud/' ), 'concept blijft een link' );

/* ---------- Begrip: sjabloon ---------- */

$sjabloon = sfp_page_config_begrip_sjabloon_blokken();
same( array( 'sfp/definitie', 'sfp/in-een-talk', 'sfp/begrip-check' ), array_column( $sjabloon, 0 ), 'sjabloon: drie blokken in volgorde' );
same( array( 'move' => true, 'remove' => true ), $sjabloon[0][1]['lock'], 'Definitie vast bovenaan' );

/* ---------- Teksten ---------- */

$en = function ( $sleutel ) {
    $GLOBALS['locale'] = 'en_GB';
    return sfp_page_config_artikel_tekst( $sleutel );
};
same( 'Self-assessment', $en( 'zelftest' ), 'label oproep Engels' );
same( 'Take the self-assessment', $en( 'doe_zelftest' ), 'knop oproep Engels' );
same( 'Test', $en( 'tool_test' ), 'test-tool Engels' );
$GLOBALS['locale'] = 'nl_NL';
same( 'Test', sfp_page_config_artikel_tekst( 'tool_test' ), 'test-tool Nederlands' );
$GLOBALS['locale'] = 'pt_BR';
same( 'Teste', sfp_page_config_artikel_tekst( 'tool_test' ), 'test-tool Portugees' );

echo ( $fouten ? "MISLUKT: $fouten van $tel\n" : "OK: $tel checks\n" );
exit( $fouten ? 1 : 0 );
