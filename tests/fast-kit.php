<?php
/**
 * Tests voor 3.55.0, FAST-kit: filter deelzin (PHP en JS gelijk), de nieuwe
 * velden suggesties, zin, aanbevolen_label en aanbevolen_als blijven bewaard
 * na het opschonen, en volledig scherm staat standaard aan bij een kaart.
 *
 * Draaien: php tests/fast-kit.php (node nodig voor de vergelijking met JS).
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
define( 'SFP_PAGE_CONFIG_DIR', dirname( __DIR__ ) . '/' );

function shortcode_exists( $s ) {
    return 'sureforms' === $s;
}
function esc_html( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_url( $s ) {
    return $s;
}
function get_bloginfo() {
    return 'nl-NL';
}
function apply_filters( $h, $v ) {
    return $v;
}
function add_filter() {}
function add_action() {}
function get_locale() {
    return 'nl_NL';
}
function wp_json_encode( $a, $f = 0 ) {
    return json_encode( $a, $f );
}
function sfp_page_config_taal() {
    return 'nl';
}
require_once SFP_PAGE_CONFIG_DIR . 'includes/artikel-teksten.php';
require_once SFP_PAGE_CONFIG_DIR . 'includes/tool-beschrijving.php';
require_once SFP_PAGE_CONFIG_DIR . 'includes/generator.php';

function sfp_page_config_tool_soorten() {
    return array( 'test' => 'tool_test', 'generator' => 'tool_generator', 'rekenaar' => 'tool_rekenaar', 'checklist' => 'tool_checklist', 'tekstanalyse' => 'tool_tekstanalyse' );
}

$fouten = 0;
$aantal = 0;
function ok( $cond, $naam ) {
    global $fouten, $aantal;
    $aantal++;
    if ( ! $cond ) {
        $fouten++;
        echo "FOUT: $naam\n";
    }
}

/* ---------- 1. Filter deelzin ---------- */
$gevallen = array(
    'They approve the pilot.'   => 'they approve the pilot',
    'they approve the pilot'    => 'they approve the pilot',
    'Wat werkt er beter?!  '    => 'wat werkt er beter',
    'KPI-rapport wordt goedgekeurd.' => 'KPI-rapport wordt goedgekeurd',
    'Ótima ideia...'            => 'ótima ideia',
    'I sign up.'                => 'I sign up',
    'In the end they agree.'    => 'in the end they agree',
    ''                          => '',
    '...'                       => '',
);
foreach ( $gevallen as $in => $uit ) {
    ok( $uit === sfp_page_config_generator_deelzin( $in ), "deelzin PHP: '$in' wordt '$uit', kreeg '" . sfp_page_config_generator_deelzin( $in ) . "'" );
}
// JS gelijk aan PHP: generator-bouw.js in node met een kleine motor-stub.
$js = sys_get_temp_dir() . '/sfp-fast-kit-' . getmypid() . '.js';
file_put_contents( $js, 'global.window={sfpToolMotor:{t:function(s){return s;},el:function(){},alineas:function(){return [];}}};global.document={};require(' . json_encode( SFP_PAGE_CONFIG_DIR . 'assets/tool/generator-bouw.js' ) . ');var D=window.sfpToolMotor.generatorDelen;var g=' . json_encode( array_keys( $gevallen ) ) . ';console.log(JSON.stringify(g.map(D.deelzin)));' );
$uit_js = json_decode( (string) shell_exec( 'node ' . escapeshellarg( $js ) . ' 2>&1' ), true );
@unlink( $js );
ok( is_array( $uit_js ), 'JS deelzin draait in node' );
if ( is_array( $uit_js ) ) {
    ok( array_values( $gevallen ) === $uit_js, 'deelzin JS gelijk aan PHP: ' . json_encode( $uit_js ) );
}

/* ---------- 2. Nieuwe velden blijven bewaard ---------- */
$b = sfp_page_config_tool_normaliseer(
    array(
        'soort'         => 'generator',
        'resultaat_kop' => '{verb} so that {res}.',
        'weergave'      => array( 'modus' => 'kaart', 'groepen' => array( array( 'letter' => 'F', 'naam' => 'Doel', 'stappen' => 3 ) ) ),
        'stappen'       => array(
            array( 'titel' => 'A', 'velden' => array( array( 'id' => 'verb', 'type' => 'keuze', 'verplicht' => true, 'opties' => array( array( 'waarde' => 'Inform', 'tekst' => 'Inform' ), array( 'waarde' => 'Persuade', 'tekst' => 'Persuade' ) ) ) ) ),
            array( 'titel' => 'B', 'velden' => array( array( 'id' => 'res', 'type' => 'kort', 'zin' => true, 'suggesties' => array( 'een', array( 'tekst' => 'twee', 'als' => array( 'veld' => 'verb', 'is' => 'Inform' ) ), array( 'geen tekst' => 1 ), '' ) ) ) ),
            array( 'titel' => 'C', 'velden' => array( array( 'id' => 'estr', 'type' => 'keuze', 'aanbevolen_label' => 'Advised for {verb|bank:praat}', 'opties' => array( array( 'waarde' => 'wsn', 'tekst' => 'What', 'aanbevolen_als' => array( 'veld' => 'verb', 'in' => array( 'Inform' ) ) ), array( 'waarde' => 'p', 'tekst' => 'Problem' ) ) ) ) ),
        ),
    )
);
$res  = $b['stappen'][1]['velden'][0];
$estr = $b['stappen'][2]['velden'][0];
ok( true === ( $res['zin'] ?? null ), 'zin true blijft' );
ok( array( array( 'tekst' => 'een' ), array( 'tekst' => 'twee', 'als' => array( 'veld' => 'verb', 'is' => 'Inform' ) ) ) === ( $res['suggesties'] ?? null ), 'suggesties: tekst en voorwaarde, lege en ongeldige weg' );
ok( 'Advised for {verb|bank:praat}' === ( $estr['aanbevolen_label'] ?? null ), 'aanbevolen_label blijft' );
ok( array( 'veld' => 'verb', 'in' => array( 'Inform' ) ) === ( $estr['opties'][0]['aanbevolen_als'] ?? null ), 'aanbevolen_als op de optie blijft' );
ok( ! isset( $estr['opties'][1]['aanbevolen_als'] ), 'zonder aanbevolen_als geen sleutel' );
ok( true === ( $b['weergave']['volledig'] ?? null ), 'volledig scherm standaard aan' );
$uit = sfp_page_config_tool_normaliseer( array( 'soort' => 'generator', 'weergave' => array( 'modus' => 'kaart', 'volledig' => false ), 'stappen' => array( array( 'titel' => 'A', 'velden' => array( array( 'id' => 'x', 'type' => 'kort' ) ) ) ) ) );
ok( false === ( $uit['weergave']['volledig'] ?? null ), 'volledig scherm uit te zetten' );

/* ---------- 3. Filter in een sjabloon ---------- */
ok( 'Inform so that they approve the pilot.' === sfp_page_config_generator_vul( '{verb} so that {res|deelzin}.', $b, array( 'verb' => 'Inform', 'res' => 'They approve the pilot.' ) ), 'filter deelzin in vul' );

echo "$aantal controles, $fouten fouten\n";
exit( $fouten ? 1 : 0 );
