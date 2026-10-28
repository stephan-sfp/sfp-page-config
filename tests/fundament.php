<?php
/**
 * Tests voor het fundament (3.15.0): kleurrollen, kopfont als variabele en
 * de berichttypes begrippen en tool. Met kleine WordPress-stubs.
 *
 * Draaien: php tests/fundament.php
 */
error_reporting( E_ALL );
set_error_handler(
    function ( $severity, $message, $file, $line ) {
        throw new ErrorException( $message, 0, $severity, $file, $line );
    }
);
define( 'ABSPATH', __DIR__ . '/' );

/* ---------- Stubs ---------- */

$GLOBALS['options'] = array();
$GLOBALS['astra']   = array();
$GLOBALS['locale']  = 'nl_NL';
$GLOBALS['types']   = array();
$GLOBALS['flushes'] = 0;
$GLOBALS['filters'] = array();

function get_option( $k, $d = false ) {
    if ( 'astra-settings' === $k ) {
        return $GLOBALS['astra'];
    }
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
function add_filter( $hook, $cb, $prio = 10, $n = 1 ) {
    $GLOBALS['filters'][ $hook ][] = $cb;
}
function apply_filters( $hook, $value ) {
    return $value;
}
function is_admin() {
    return false;
}
function get_locale() {
    return $GLOBALS['locale'];
}
function get_posts() {
    return array();
}
function sanitize_text_field( $s ) {
    return trim( strip_tags( (string) $s ) );
}
function sanitize_key( $s ) {
    return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) );
}
function sanitize_title( $s ) {
    return preg_replace( '/[^a-z0-9\-]/', '', strtolower( (string) $s ) );
}
function wp_strip_all_tags( $s ) {
    return strip_tags( (string) $s );
}
function post_type_exists( $t ) {
    return isset( $GLOBALS['types'][ $t ] );
}
function register_post_type( $t, $args ) {
    $obj              = (object) $args;
    $obj->name        = $t;
    $obj->labels      = (object) $args['labels'];
    $GLOBALS['types'][ $t ] = $obj;
    return $obj;
}
function get_post_type_object( $t ) {
    return $GLOBALS['types'][ $t ] ?? null;
}
function get_post_type_labels( $obj ) {
    return (object) array(
        'name'          => 'Posts',
        'singular_name' => 'Post',
        'add_new'       => 'Add New Post',
        'edit_item'     => 'Edit Post',
    );
}
function get_post_type_archive_link( $t ) {
    return 'https://example.test/' . $GLOBALS['types'][ $t ]->rewrite['slug'] . '/';
}
function flush_rewrite_rules() {
    ++$GLOBALS['flushes'];
}

function sfp_page_config_astra_option( $k ) {
    $v = $GLOBALS['astra'][ $k ] ?? '';
    return is_string( $v ) || is_numeric( $v ) ? trim( (string) $v ) : '';
}
function sfp_page_config_sanitize_font_family( $value ) {
    $value = trim( preg_replace( '/[^a-zA-Z0-9\s\'",\-]/', '', (string) $value ) );
    if ( '' === $value || 0 === strcasecmp( $value, 'inherit' ) ) {
        return '';
    }
    return $value;
}
function sfp_page_config_sanitize_css_color( $v ) {
    return preg_match( '/^#[0-9a-f]{3,8}$/i', (string) $v ) ? $v : '';
}

require dirname( __DIR__ ) . '/includes/instellingen.php';
require dirname( __DIR__ ) . '/includes/teksten.php';
require dirname( __DIR__ ) . '/includes/fundament.php';

$checks = 0;
$fouten = 0;
function same( $verwacht, $echt, $wat ) {
    global $checks, $fouten;
    ++$checks;
    if ( $verwacht !== $echt ) {
        ++$fouten;
        echo 'FOUT: ' . $wat . "\n  verwacht: " . var_export( $verwacht, true ) . "\n  kreeg:    " . var_export( $echt, true ) . "\n";
    }
}
function gesorteerd( $a ) {
    ksort( $a );
    return $a;
}
function zet( $id, $aan ) {
    $alle                      = get_option( 'sfp_settings', array() );
    $alle['onderdelen'][ $id ] = $aan ? '1' : '0';
    update_option( 'sfp_settings', $alle );
}

/* ---------- Onderdelen staan standaard uit ---------- */

foreach ( array( 'kleurrollen', 'kopfont', 'berichttypes', 'paginabeheer' ) as $id ) {
    same( true, array_key_exists( $id, sfp_page_config_onderdelen() ), "Onderdeel $id bestaat" );
    same( false, sfp_page_config_onderdeel_aan( $id ), "Onderdeel $id staat standaard uit" );
}

/* ---------- Kleurrollen: schoonmaken ---------- */

same(
    array( 'primair' => 2, 'tint-3' => 8, 'wit' => 5 ),
    sfp_page_config_kleurrollen_schoon( array( 'primair' => '2', 'tint-3' => 8, 'wit' => 5, 'onzin' => 1, 'hover' => '', 'links' => 9, 'body' => -1, 'pijl' => 'x' ) ),
    'Schoon: alleen bekende rollen, slot 0-8, leeg valt weg'
);
same( array(), sfp_page_config_kleurrollen_schoon( 'tekst' ), 'Schoon: geen lijst geeft niets' );

/* ---------- Kleurrollen: zonder snippet en zonder toewijzing ---------- */

same( array(), sfp_page_config_kleurrollen_slots(), 'Geen snippet, uit: geen rollen' );
same( '', sfp_page_config_kleurrollen_declaraties(), 'Geen declaraties zonder rollen' );

/* ---------- Kleurrollen: snippet aan, onderdeel uit ---------- */

// Binnen een if: PHP maakt een functie op het hoogste niveau al bij het laden aan.
if ( true ) {
    function sfp_kleurrollen_slots() {
        return $GLOBALS['snippet_slots'];
    }
}
$GLOBALS['snippet_slots'] = array( 'secundair' => 0, 'links' => 1, 'primair' => 2, 'body' => 3, 'tint-1' => 4, 'wit' => 5, 'tint-2' => 6, 'hover' => 7, 'tint-3' => 8, 'pijl' => 0 );

same( gesorteerd( $GLOBALS['snippet_slots'] ), gesorteerd( sfp_page_config_kleurrollen_slots() ), 'Uit: volgt het snippet' );
same( false, sfp_page_config_kleurrollen_bewaard(), 'Uit: neemt niets over' );
ob_start();
sfp_page_config_kleurrollen_head();
same( '', ob_get_clean(), 'Uit: geen uitvoer in de head' );

/* ---------- Kleurrollen: aanzetten neemt het snippet over ---------- */

zet( 'kleurrollen', true );
same( gesorteerd( $GLOBALS['snippet_slots'] ), gesorteerd( sfp_page_config_kleurrollen_slots() ), 'Aan: zelfde rollen als het snippet' );
same( true, sfp_page_config_kleurrollen_bewaard(), 'Aan: toewijzing overgenomen' );
same( gesorteerd( $GLOBALS['snippet_slots'] ), gesorteerd( get_option( 'sfp_settings' )['kleurrollen'] ), 'Overname staat in sfp_settings' );

ob_start();
sfp_page_config_kleurrollen_head();
$head   = ob_get_clean();
$decl   = function ( $css ) {
    preg_match_all( '/--sfp-rol-[a-z0-9-]+:var\(--ast-global-color-\d\)/', $css, $m );
    sort( $m[0] );
    return $m[0];
};
$snipp = '';
foreach ( $GLOBALS['snippet_slots'] as $rol => $slot ) {
    $snipp .= '--sfp-rol-' . $rol . ':var(--ast-global-color-' . (int) $slot . ');';
}
same( 0, strpos( $head, '<style id="sfp-kleurrollen">:root{' ), 'Head: zelfde blok en id als het snippet' );
same( $decl( $snipp ), $decl( $head ), 'Head: dezelfde declaraties als het snippet' );

// Het snippet verandert daarna: de plugin houdt zijn eigen toewijzing.
$GLOBALS['snippet_slots'] = array( 'primair' => 0 );
same( 2, sfp_page_config_kleurrollen_slots()['primair'], 'Aan: bewaarde toewijzing wint van een later gewijzigd snippet' );

/* ---------- Kleurrollen: opslaan via het beheer ---------- */

$bewaard = sfp_page_config_instellingen_bewaar( array( 'kleurrollen' => array( 'primair' => '3', 'hover' => 12, 'wit' => 5 ) ) );
same( array( 'kleurrollen' ), $bewaard, 'Opslaan kleurrollen' );
same( array( 'primair' => 3, 'wit' => 5 ), sfp_page_config_kleurrollen_slots(), 'Opslaan: slot buiten het palet valt weg' );
same( '1', get_option( 'sfp_settings' )['onderdelen']['kleurrollen'], 'Opslaan laat de onderdelen staan' );

$stand = sfp_page_config_kleurrollen_stand();
same( 11, count( $stand['rollen'] ), 'Stand: elf rollen' );
same( 9, count( $stand['palet'] ), 'Stand: negen paletkleuren' );
same( null, $stand['rollen'][4]['slot'], 'Stand: hover niet gevoerd' );

/* ---------- Kopfont ---------- */

same( '"Archivo Black", sans-serif', sfp_page_config_kopfont_normaal( "'Archivo Black', sans-serif" ), 'Normaal: naam met spatie tussen dubbele aanhalingstekens' );
same( 'Archivo, sans-serif', sfp_page_config_kopfont_normaal( "'Archivo', sans-serif" ), 'Normaal: enkel woord zonder aanhalingstekens' );
same( '"Rubik Mono One", sans-serif', sfp_page_config_kopfont_normaal( '"Rubik Mono One",sans-serif' ), 'Normaal: komma en spatie' );

$GLOBALS['astra'] = array(
    'headings-font-family' => "'Rubik', sans-serif",
    'font-family-h1'       => "'Rubik Mono One', sans-serif",
    'body-font-family'     => "'Roboto', sans-serif",
);
same( array( 'display' => '"Rubik Mono One", sans-serif', 'kop' => 'Rubik, sans-serif' ), sfp_page_config_kopfont_ux(), 'UX: H1 en koppen uit Astra' );
$GLOBALS['astra']['font-family-h1'] = 'inherit';
same( 'Rubik, sans-serif', sfp_page_config_kopfont_ux()['display'], 'UX: H1 volgt de koppen bij inherit' );
$GLOBALS['astra']['headings-font-family'] = 'inherit';
same( 'Roboto, sans-serif', sfp_page_config_kopfont_ux()['kop'], 'UX: koppen volgen de body bij inherit' );
$GLOBALS['astra']['headings-font-family'] = "'Nunito', sans-serif;}</style><script>";
same( false, strpos( sfp_page_config_kopfont_ux_declaraties(), '<' ), 'UX: geen tag uit een kapot font' );

ob_start();
sfp_page_config_kopfont_ux_head();
same( '', ob_get_clean(), 'UX uit: geen uitvoer' );
zet( 'kopfont', true );
$GLOBALS['astra'] = array( 'headings-font-family' => "'Nunito', sans-serif", 'font-family-h1' => "'Nunito', sans-serif" );
ob_start();
sfp_page_config_kopfont_ux_head();
same( '<style id="sfp-kopfont-ux">:root{--ux-display-font:Nunito, sans-serif;--ux-heading-font:Nunito, sans-serif;}</style>' . "\n", ob_get_clean(), 'UX aan: blok in de head' );

same( true, in_array( 'sfp-kleurrollen', sfp_page_config_fundament_rucss( array() ), true ), 'RUCSS laat de kleurrollen staan' );
same( true, in_array( 'sfp-kopfont-ux', sfp_page_config_fundament_rucss( array() ), true ), 'RUCSS laat de fontvariabelen staan' );

/* ---------- Berichttypes: standaard per taal ---------- */

$GLOBALS['locale'] = 'nl_NL';
same( 'begrippen', sfp_page_config_berichttype_standaard( 'begrippen' )['slug'], 'NL: /begrippen/' );
same( 'Begrip', sfp_page_config_berichttype_standaard( 'begrippen' )['labels']['singular_name'], 'NL: Begrip' );
same( 'tools', sfp_page_config_berichttype_standaard( 'tool' )['slug'], 'NL: /tools/' );
$GLOBALS['locale'] = 'en_US';
same( 'glossary', sfp_page_config_berichttype_standaard( 'begrippen' )['slug'], 'EN: /glossary/' );
same( 'Term', sfp_page_config_berichttype_standaard( 'begrippen' )['labels']['singular_name'], 'EN: Term' );
$GLOBALS['locale'] = 'pt_BR';
same( 'glossario', sfp_page_config_berichttype_standaard( 'begrippen' )['slug'], 'PT: /glossario/' );
same( 'ferramentas', sfp_page_config_berichttype_standaard( 'tool' )['slug'], 'PT: /ferramentas/' );
same( 'Ferramenta', sfp_page_config_berichttype_standaard( 'tool' )['labels']['singular_name'], 'PT: Ferramenta' );

/* ---------- Berichttypes: niets met het onderdeel uit ---------- */

$GLOBALS['locale'] = 'pt_BR';
sfp_page_config_berichttypes_registreer();
same( array(), array_keys( $GLOBALS['types'] ), 'Uit: geen berichttypes' );

/* ---------- Berichttypes: Tools aan zonder snippet (FL) ---------- */

zet( 'tools', true );
sfp_page_config_berichttypes_registreer();
same( array( 'tool' ), array_keys( $GLOBALS['types'] ), 'Tools aan: alleen tool' );
same( 'ferramentas', $GLOBALS['types']['tool']->rewrite['slug'], 'FL zonder snippet: /ferramentas/ in plaats van /tools/' );
same( 'dashicons-calculator', $GLOBALS['types']['tool']->menu_icon, 'Icoon tool' );
same( true, in_array( 'revisions', $GLOBALS['types']['tool']->supports, true ), 'Tool met revisies' );
sfp_page_config_berichttypes_adressen();
same( 1, $GLOBALS['flushes'], 'Adresregels opnieuw bij nieuwe eigen registratie' );
sfp_page_config_berichttypes_adressen();
same( 1, $GLOBALS['flushes'], 'Niet opnieuw bij gelijke stand' );
zet( 'tools', false );
$GLOBALS['types'] = array();

/* ---------- Berichttypes: snippet registreert, onderdeel aan neemt over ---------- */

$GLOBALS['locale'] = 'pt_BR';
register_post_type(
    'begrippen',
    array(
        'labels'  => array( 'name' => 'Glossario', 'singular_name' => 'Termo', 'add_new' => 'Add New Post', 'edit_item' => 'Editar termo' ),
        'rewrite' => array( 'slug' => 'glossario', 'with_front' => true ),
        'rest_base' => false,
    )
);
zet( 'berichttypes', true );
sfp_page_config_berichttypes_registreer();
$over = get_option( 'sfp_settings' )['berichttypes']['begrippen'];
same( 'glossario', $over['slug'], 'Overname: slug' );
same( true, $over['with_front'], 'Overname: with_front' );
same( 'begrippen', $over['rest_base'], 'Overname: rest_base standaard gelijk aan de sleutel' );
same( array( 'name' => 'Glossario', 'singular_name' => 'Termo', 'edit_item' => 'Editar termo' ), $over['labels'], 'Overname: alleen eigen labels, geen WordPress-standaard' );
same( 'geen', get_option( 'sfp_settings' )['berichttypes']['tool']['slug'] ?? 'geen', 'Tool zonder snippet: niets overgenomen, de plugin registreert zelf' );
same( 'ferramentas', $GLOBALS['types']['tool']->rewrite['slug'], 'Tool zonder snippet: plugin registreert /ferramentas/' );

// Snippet uit: de plugin registreert begrippen met wat hij overnam.
unset( $GLOBALS['types']['begrippen'] );
$GLOBALS['locale'] = 'en_US';
sfp_page_config_berichttypes_registreer();
same( 'glossario', $GLOBALS['types']['begrippen']->rewrite['slug'], 'Snippet uit: zelfde adres als overgenomen, ook als de taal verschilt' );
same( true, $GLOBALS['types']['begrippen']->rewrite['with_front'], 'Snippet uit: with_front blijft' );
same( 'Termo', $GLOBALS['types']['begrippen']->labels->singular_name, 'Snippet uit: zelfde naam' );
same( 'dashicons-book-alt', $GLOBALS['types']['begrippen']->menu_icon, 'Icoon begrippen' );
same( 'begrippen', $GLOBALS['types']['begrippen']->rest_base, 'rest_base begrippen' );

$st = sfp_page_config_berichttypes_stand();
same( 'plugin', $st[0]['bron'], 'Stand: begrippen uit de plugin' );
same( true, $st[0]['bewaard'], 'Stand: begrippen overgenomen' );

/* ---------- Iconen voor een registratie door een snippet ---------- */

same( 'dashicons-calculator', sfp_page_config_berichttypes_iconen( array( 'menu_icon' => 'dashicons-admin-tools' ), 'tool' )['menu_icon'], 'Iconenfilter tool' );
same( 'x', sfp_page_config_berichttypes_iconen( array( 'menu_icon' => 'x' ), 'page' )['menu_icon'], 'Iconenfilter laat andere types staan' );
zet( 'berichttypes', false );
same( 'dashicons-admin-tools', sfp_page_config_berichttypes_iconen( array( 'menu_icon' => 'dashicons-admin-tools' ), 'tool' )['menu_icon'], 'Iconenfilter uit met het onderdeel uit' );

/* ---------- Schoonmaken van een bewaarde registratie ---------- */

same( null, sfp_page_config_berichttype_schoon( array( 'slug' => '' ) ), 'Schoon: zonder slug niets' );
same( 'tools', sfp_page_config_berichttype_schoon( array( 'slug' => 'Tools<script>', 'labels' => array( 'name' => '<b>Tools</b>', 'Rare Sleutel' => 'x' ) ) )['slug'] === 'toolsscript' ? 'tools' : 'fout', 'Schoon: slug zonder tekens' );
same( array( 'name' => 'Tools' ), sfp_page_config_berichttype_schoon( array( 'slug' => 'tools', 'labels' => array( 'name' => '<b>Tools</b>', 'Rare Sleutel' => 'x' ) ) )['labels'], 'Schoon: labels zonder HTML en rare sleutels' );

/* ---------- Paginabeheer: klasse alleen met het onderdeel aan ---------- */

require dirname( __DIR__ ) . '/includes/paginabeheer.php';
same( false, class_exists( 'SFP_Content_MCP', false ), 'Paginabeheer uit: geen klasse, het snippet werkt zoals altijd' );

echo ( $fouten ? "FOUT: $fouten van $checks checks\n" : "OK: $checks checks\n" );
exit( $fouten ? 1 : 0 );
