<?php
/**
 * Proef voor het eerste scherm van de tools (3.22.0): bouwt per voorbeeld in
 * tests/tool-voorbeelden een HTML-pagina met de tool-CSS, het eerste scherm
 * van de server en de motor. bouw/eerste-scherm-proef.js meet daarna de
 * hoogte zonder en met scripts.
 *
 *   php bouw/eerste-scherm-proef.php <uitvoermap>
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
define( 'ABSPATH', __DIR__ . '/' );
define( 'SFP_PAGE_CONFIG_DIR', dirname( __DIR__ ) . '/' );
function shortcode_exists( $s ) {
    return false; }
function esc_html( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $s ) {
    return $s; }
function get_bloginfo() {
    return 'nl-NL'; }
function apply_filters( $h, $v ) {
    return $v; }
function add_filter() {}
function add_action() {}
function get_locale() {
    return 'nl_NL'; }
function wp_json_encode( $a, $f = 0 ) {
    return json_encode( $a, $f ); }
function sfp_page_config_taal() {
    return 'nl'; }
require_once SFP_PAGE_CONFIG_DIR . 'includes/artikel-teksten.php';
require_once SFP_PAGE_CONFIG_DIR . 'includes/tool-beschrijving.php';
require_once SFP_PAGE_CONFIG_DIR . 'includes/generator.php';
require_once SFP_PAGE_CONFIG_DIR . 'includes/tool-eerste-scherm.php';
function sfp_page_config_tool_soorten() {
    return array(
        'test'         => 1,
        'generator'    => 1,
        'rekenaar'     => 1,
        'checklist'    => 1,
        'tekstanalyse' => 1
    );
}
function css( $f ) {
    $s = file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/' . $f );
    return preg_replace( '#/\*.*?\*/#s', '', $s );
}
$uit = rtrim( $argv[1] ?? 'dist/proef', '/' );
@mkdir( $uit, 0777, true );
$teksten = array();
$alle    = sfp_page_config_artikel_teksten();
foreach ( $alle['en'] as $k => $w ) {
    if ( 0 === strpos( $k, 'tool' ) ) {
        $teksten[ $k ] = $alle['nl'][ $k ] ?? $w;
    }
}
$config = array(
    'rest'     => '/x/',
    'vendor'   => '/v/',
    'site'     => 'proef',
    'sitenaam' => 'Proef',
    'siteurl'  => 'proef',
    'versie'   => 'x',
    'teksten'  => $teksten
);
$vars   = ':root{--sfp-b-primair:#1d2b4f;--sfp-b-secundair:#2a7;--sfp-b-links:#c33;--sfp-b-hover:#a22;--sfp-b-tekstlink:#c33;--sfp-b-knoptekst:#fff;--sfp-b-tint1:#dde;--sfp-b-tint2:#eef;--sfp-b-tint3:#f6f6fb;--sfp-b-wit:#fff;--sfp-b-body:#333;--sfp-b-lijn:rgba(0,0,0,.12);--sfp-b-lijn-sterk:rgba(0,0,0,.26);--sfp-b-kop:Georgia,serif;--sfp-b-tekst:Arial,sans-serif;--sfp-b-r-knop:6px;--sfp-b-r-kaart:6px}body{font:16px/1.6 Arial,sans-serif;margin:0;padding:16px}main{max-width:760px;margin:0 auto}';
$lijst  = array();
foreach ( glob( SFP_PAGE_CONFIG_DIR . 'tests/tool-voorbeelden/*/*.json' ) as $pad ) {
    $data = json_decode( (string) file_get_contents( $pad ), true );
    if ( ! is_array( $data ) ) {
        continue;
    }
    $b = sfp_page_config_tool_normaliseer( $data );
    if ( ! $b ) {
        continue;
    }
    $naam   = basename( dirname( $pad ) ) . '-' . basename( $pad, '.json' );
    $eerste = sfp_page_config_tool_eerste_scherm( $b, 101, 'Proef' );
    $c      = css( 'tool/tool.css' );
    $soort  = $b['soort'];
    $gen    = sfp_page_config_tool_op_generator( $soort ) || 'tekstanalyse' === $soort;
    if ( $gen ) {
        $c .= css( 'tool/tool-generator.css' ); }
    if ( 'tekstanalyse' === $soort ) {
        $c .= css( 'tool/tool-tekstanalyse.css' ); }
    $kaart = isset( $b['weergave']['modus'] ) && 'kaart' === $b['weergave']['modus'];
    if ( $kaart ) {
        $c .= css( 'tool/tool-kaart.css' ); }
    $scripts = array( 'motor' );
    if ( sfp_page_config_tool_op_generator( $soort ) ) {
        $scripts[] = 'generator-bouw';
        $scripts[] = 'generator';
    } elseif ( 'tekstanalyse' === $soort ) {
        $scripts[] = 'generator-bouw';
        $scripts[] = 'tekstanalyse';
    } else {
        $scripts[] = $soort; }
    $js = '';
    foreach ( $scripts as $s ) {
        $js .= '<script src="' . SFP_PAGE_CONFIG_DIR . 'assets/tool/' . $s . '.js"></script>'; }
    unset( $b['mail'], $b['genegeerd'] );
    $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>' . $vars . $c . '</style></head><body><main><p>Boven</p>'
        . '<div class="sfp-tool sfp-tool--' . esc_attr( $soort ) . ' sfp-tool--volledig' . ( $kaart ? ' sfp-tool--kaart' : '' ) . '" id="sfp-tool-101" data-tool="101" data-slug="proef" data-titel="Proef" data-weergave="volledig">'
        . '<script type="application/json" class="sfp-tool__data">' . json_encode( $b, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>'
        . ( '' !== $eerste ? '<div class="sfp-tool__app">' . $eerste . '</div>' : '<div class="sfp-tool__app" hidden></div>' )
        . '</div><p id="onder">Onder</p></main><script>window.sfpToolConfig=' . json_encode( $config, JSON_UNESCAPED_UNICODE ) . ';</script>' . $js . '</body></html>';
    file_put_contents( "$uit/$naam.html", $html );
    $lijst[] = array(
        'naam'   => $naam,
        'soort'  => $soort,
        'vooraf' => '' !== $eerste,
        'kaart'  => $kaart,
        'live'   => ! empty( $b['live'] )
    );
}
file_put_contents( "$uit/lijst.json", json_encode( $lijst, JSON_PRETTY_PRINT ) );
echo count( $lijst ) . " pagina's\n";
