<?php
/**
 * Tests voor de toolmotor, soort generator.
 *
 *   php tests/generator.php                 de ingebouwde controles en alle
 *                                           generatoren in tests/tool-voorbeelden
 *   php tests/generator.php <map-met-json>  ook de beschrijvingen in die map
 *
 * Legt sfp_page_config_generator_bouw() (PHP) naast bouw() in
 * assets/tool/generator.js met dezelfde antwoorden: beide moeten dezelfde
 * blokken geven. Node is nodig voor die vergelijking.
 */
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
    return array( 'test' => 'tool_test', 'generator' => 'tool_generator' );
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

/* ---------- 1. Bouwstenen ---------- */

$b = sfp_page_config_tool_normaliseer(
    array(
        'soort'   => 'generator',
        'stappen' => array(
            array(
                'titel'  => 'A',
                'velden' => array(
                    array( 'id' => 'rol', 'type' => 'keuze', 'label' => 'Rol', 'verplicht' => true, 'opties' => array( array( 'waarde' => 'x', 'tekst' => 'Iks' ), array( 'waarde' => 'y', 'tekst' => 'Ei' ) ) ),
                    array( 'id' => 'naam', 'type' => 'kort', 'label' => 'Naam' ),
                    array( 'id' => 'tekst', 'type' => 'regels', 'label' => 'Regels' ),
                    array( 'id' => 'datum', 'type' => 'datum', 'label' => 'Datum' ),
                    array( 'id' => 'vink', 'type' => 'vinkjes', 'label' => 'Vink', 'min' => 1, 'opties' => array( array( 'waarde' => 'a', 'tekst' => 'Aa', 'uitleg' => 'uitleg a' ), array( 'waarde' => 'b', 'tekst' => 'Bee' ) ) ),
                ),
            ),
        ),
        'waarden' => array( 'wie' => '{naam|anders:jij}' ),
        'banken'  => array(
            'q'  => array(
                'x' => array( 'a' => array( 'Vraag a voor {wie}', array( 'tekst' => 'Alleen bij Ei', 'als' => array( 'veld' => 'rol', 'is' => 'y' ) ) ), 'b' => 'Eén vraag b' ),
                'y' => array( 'a' => array( 'Vraag a voor {wie}', array( 'tekst' => 'Alleen bij Ei', 'als' => array( 'veld' => 'rol', 'is' => 'y' ) ) ) ),
            ),
            'lb' => array( 'x' => 'Een iks', 'y' => 'Een ei' ),
        ),
        'uitvoer' => array(
            array( 'type' => 'tekst', 'kop' => 'Kop {rol|label}', 'tekst' => 'Hallo {naam|hoofdletter|anders:daar}. {rol|bank:lb}' ),
            array( 'type' => 'herhaal', 'over' => 'vink', 'kop' => '{@nr}. {@label}', 'inhoud' => array( array( 'type' => 'vragen', 'bank' => 'q', 'pad' => array( '{rol}', '{@}' ) ) ) ),
            array( 'type' => 'lijst', 'regels' => 'tekst', 'voor' => '- ', 'leeg' => 'Niets' ),
            array( 'type' => 'gegevens', 'regels' => array( array( 'label' => 'Datum', 'tekst' => '{datum|datum}' ), array( 'label' => 'Naam', 'tekst' => '{naam}' ) ) ),
            array( 'type' => 'tekst', 'als' => array( 'veld' => 'vink', 'bevat' => 'b' ), 'tekst' => 'B gekozen: {vink|label}' ),
        ),
    )
);
$c = sfp_page_config_generator_controleer( $b );
ok( ! $c['fouten'], 'voorbeeld: geen fouten ' . json_encode( $c ) );
$bl = sfp_page_config_generator_bouw( $b, array( 'rol' => 'x', 'naam' => 'sara', 'vink' => array( 'b', 'a', 'z' ), 'tekst' => "een\n\n* twee", 'datum' => '2026-10-03' ) );
ok( 'Kop Iks' === $bl[0]['tekst'] && 'Hallo Sara. Een iks' === $bl[1]['tekst'], 'tekst, filters en bank op keuze ' . json_encode( $bl ) );
ok( '1. Aa' === $bl[2]['tekst'] && array( 'Vraag a voor sara' ) === $bl[3]['items'] && '2. Bee' === $bl[4]['tekst'] && array( 'Eén vraag b' ) === $bl[5]['items'], 'herhaal in optievolgorde, bank op pad, waarde met terugval' );
ok( '1.a.0' === $bl[3]['sleutel'], 'sleutel van een antwoordveld' );
ok( array( '- een', '- twee' ) === $bl[6]['items'], 'regels met voorvoegsel en zonder opsommingsteken' );
ok( array( array( 'Datum', '3 oktober 2026' ), array( 'Naam', 'sara' ) ) === $bl[7]['rijen'], 'gegevens met lange datum' );
ok( 'B gekozen: Aa, Bee' === $bl[8]['tekst'], 'voorwaarde bevat en label van vinkjes' );
$bl = sfp_page_config_generator_bouw( $b, array( 'rol' => 'y', 'vink' => array( 'a' ) ) );
ok( 'Hallo daar. Een ei' === $bl[1]['tekst'] && array( 'Vraag a voor jij', 'Alleen bij Ei' ) === $bl[3]['items'], 'terugval en item met voorwaarde' );
ok( 'Niets' === $bl[4]['tekst'], 'lege lijst toont de leegtekst' );
ok( array( 'rol' ) === sfp_page_config_generator_ontbreekt( $b, array(), $b['stappen'][0] ) || array( 'rol', 'vink' ) === sfp_page_config_generator_ontbreekt( $b, array(), $b['stappen'][0] ), 'verplicht veld ontbreekt' );
ok( in_array( 'vink', sfp_page_config_generator_ontbreekt( $b, array( 'rol' => 'x' ), $b['stappen'][0] ), true ), 'minimaal één vinkje' );
ok( 'rol' !== ( sfp_page_config_generator_ontbreekt( $b, array( 'rol' => 'onbekend' ), $b['stappen'][0] )[0] ?? '' ) ? false : true, 'onbekende keuze telt als leeg' );
ok( 'Een tekst die heel…' === sfp_page_config_generator_filter( $b, array(), 'Een tekst die heel lang doorgaat', 'x', 'kort:20', null ), 'kort op woordgrens' );
ok( 'sara' === sfp_page_config_generator_vul( '{onbekend|of:naam|anders:niemand}', $b, array( 'naam' => 'sara' ) ) && 'niemand' === sfp_page_config_generator_vul( '{onbekend|of:naam|anders:niemand}', $b, array() ), 'terugval op een ander veld' );
ok( 'Iets met sara' === sfp_page_config_generator_vul( 'Iets{naam|voor: met }', $b, array( 'naam' => 'sara' ) ) && 'Iets' === sfp_page_config_generator_vul( 'Iets{naam|voor: met }', $b, array() ), 'voorvoegsel alleen als gevuld' );
ok( 'Zin een.' === sfp_page_config_generator_filter( $b, array(), 'Zin een. Zin twee.', 'x', 'eerstezin', null ), 'eerste zin' );
ok( 'Klaar.' === sfp_page_config_generator_filter( $b, array(), 'Klaar', 'x', 'zin', null ) && 'Klaar?' === sfp_page_config_generator_filter( $b, array(), 'Klaar?', 'x', 'zin', null ), 'zin met punt' );
$m = sfp_page_config_generator_mail_antwoorden( $b, array( 'rol' => 'x', 'naam' => str_repeat( 'a', 200 ), 'tekst' => 'lang verhaal', 'vreemd' => 'x' ) );
ok( ! isset( $m['tekst'] ) && ! isset( $m['vreemd'] ) && 80 === mb_strlen( $m['naam'] ), 'mail: geen tekstvak, onbekend veld weg, kort veld afgekapt' );

$fout = sfp_page_config_generator_controleer( sfp_page_config_tool_normaliseer( array( 'soort' => 'generator', 'stappen' => array( array( 'velden' => array( array( 'id' => 'a', 'type' => 'raar' ), array( 'id' => 'a', 'type' => 'keuze' ) ) ) ), 'uitvoer' => array( array( 'type' => 'lijst', 'bank' => 'geen' ), array( 'type' => 'herhaal', 'over' => 'a' ) ) ) ) );
ok( count( $fout['fouten'] ) >= 4, 'controle vindt type, dubbel id, keuze zonder opties en ontbrekende bank ' . json_encode( $fout['fouten'] ) );

/* ---------- 2. PHP tegen JS ---------- */

function vergelijk_js( $naam, array $b, array $sets ) {
    static $harness = null;
    if ( null === $harness ) {
        $harness = '';
        if ( '' !== trim( (string) shell_exec( 'command -v node 2>/dev/null' ) ) ) {
            $harness = sys_get_temp_dir() . '/sfp-gen-harness-' . getmypid() . '.js';
            file_put_contents( $harness, <<<'JS'
const fs = require('fs');
global.window = global; global.document = { readyState: 'loading', addEventListener(){}, querySelectorAll(){ return []; } };
window.sfpToolConfig = { teksten: JSON.parse(fs.readFileSync(process.argv[5], 'utf8')) };
eval(fs.readFileSync(process.argv[2] + 'motor.js', 'utf8'));
eval(fs.readFileSync(process.argv[2] + 'generator.js', 'utf8'));
const b = JSON.parse(fs.readFileSync(process.argv[3], 'utf8'));
const sets = JSON.parse(fs.readFileSync(process.argv[4], 'utf8'));
const uit = sets.map(a => ({ blokken: window.sfpToolMotor.bouwGenerator(b, a), ontbreekt: (b.stappen || []).map(s => window.sfpToolMotor.generatorOntbreekt(b, a, s)) }));
process.stdout.write(JSON.stringify(uit));
JS
            );
        } else {
            echo "  (node ontbreekt: PHP tegen JS overgeslagen)\n";
        }
    }
    if ( '' === $harness ) {
        return;
    }
    $browser = $b;
    unset( $browser['mail'], $browser['genegeerd'] );
    $tpad = sys_get_temp_dir() . '/sfp-gen-t-' . getmypid() . '.json';
    $bpad = sys_get_temp_dir() . '/sfp-gen-b-' . getmypid() . '.json';
    $spad = sys_get_temp_dir() . '/sfp-gen-s-' . getmypid() . '.json';
    file_put_contents( $tpad, json_encode( array( 'tool_maanden' => sfp_page_config_artikel_tekst( 'tool_maanden' ) ) ) );
    file_put_contents( $bpad, json_encode( $browser ) );
    file_put_contents( $spad, json_encode( $sets ) );
    $uit = shell_exec( 'node ' . escapeshellarg( $harness ) . ' ' . escapeshellarg( SFP_PAGE_CONFIG_DIR . 'assets/tool/' ) . ' ' . escapeshellarg( $bpad ) . ' ' . escapeshellarg( $spad ) . ' ' . escapeshellarg( $tpad ) . ' 2>&1' );
    $jsr = json_decode( (string) $uit, true );
    ok( is_array( $jsr ) && count( $jsr ) === count( $sets ), $naam . ': JS leverde uitkomsten (' . substr( (string) $uit, 0, 300 ) . ')' );
    if ( ! is_array( $jsr ) ) {
        return;
    }
    $mis = 0;
    foreach ( $sets as $i => $a ) {
        $p = array(
            'blokken'   => sfp_page_config_generator_bouw( $b, $a ),
            'ontbreekt' => array_map(
                function ( $s ) use ( $b, $a ) {
                    return sfp_page_config_generator_ontbreekt( $b, $a, $s );
                },
                $b['stappen']
            ),
        );
        $j = $jsr[ $i ] ?? array();
        if ( json_encode( $p ) !== json_encode( $j ) && $mis < 3 ) {
            $mis++;
            ok( false, $naam . ' set ' . $i . ' ' . json_encode( $a, JSON_UNESCAPED_UNICODE ) . "\n  PHP " . substr( json_encode( $p, JSON_UNESCAPED_UNICODE ), 0, 1500 ) . "\n  JS  " . substr( json_encode( $j, JSON_UNESCAPED_UNICODE ), 0, 1500 ) );
        } else {
            ok( true, '' );
        }
    }
}

/**
 * Antwoordsets: volledig, deels leeg en met vreemde waarden zoals een
 * deellink ze kan bevatten.
 */
function maak_sets( array $b, $aantal ) {
    $sets  = array();
    $vrij  = array( 'Sara', 'jeroen de vries', '  spatie  ', "regel een\nregel twee\n- drie", 'Een zin. En nog een!', 'ëmoji 😀 test', '{rol} haakjes', 'q1 2026', str_repeat( 'lang woord ', 30 ), '' );
    $raar  = array( true, false, null, array(), 3, 1.5, '1e1', array( 'x' ), array( 'a' => 'b' ) );
    for ( $i = 0; $i < $aantal; $i++ ) {
        $a = array();
        foreach ( $b['stappen'] as $s ) {
            foreach ( $s['velden'] as $v ) {
                if ( $i > 3 && 0 === mt_rand( 0, 9 ) ) {
                    $a[ $v['id'] ] = $raar[ mt_rand( 0, count( $raar ) - 1 ) ];
                    continue;
                }
                if ( 0 === mt_rand( 0, 6 ) ) {
                    continue;
                }
                switch ( $v['type'] ) {
                    case 'keuze':
                    case 'lijst':
                        $a[ $v['id'] ] = $v['opties'] ? $v['opties'][ mt_rand( 0, count( $v['opties'] ) - 1 ) ]['waarde'] : '';
                        break;
                    case 'vinkjes':
                        $w = array();
                        foreach ( $v['opties'] as $o ) {
                            if ( mt_rand( 0, 1 ) ) {
                                $w[] = $o['waarde'];
                            }
                        }
                        shuffle( $w );
                        $a[ $v['id'] ] = $w;
                        break;
                    case 'datum':
                        $a[ $v['id'] ] = mt_rand( 0, 3 ) ? sprintf( '20%02d-%02d-%02d', mt_rand( 20, 30 ), mt_rand( 1, 12 ), mt_rand( 1, 28 ) ) : '3 mei';
                        break;
                    case 'getal':
                        $a[ $v['id'] ] = (string) mt_rand( -5, 500 );
                        break;
                    default:
                        $a[ $v['id'] ] = $vrij[ mt_rand( 0, count( $vrij ) - 1 ) ];
                }
            }
        }
        $sets[] = $a;
    }
    return $sets;
}

vergelijk_js( 'bouwstenen', $b, maak_sets( $b, 300 ) );

$mappen = array( SFP_PAGE_CONFIG_DIR . 'tests/tool-voorbeelden' );
if ( isset( $argv[1] ) ) {
    $mappen[] = $argv[1];
}
foreach ( $mappen as $map ) {
    foreach ( glob( rtrim( $map, '/' ) . '/{,*/}*.json', GLOB_BRACE ) as $bestand ) {
        $data = json_decode( file_get_contents( $bestand ), true );
        if ( ! is_array( $data ) || ( $data['soort'] ?? '' ) !== 'generator' ) {
            continue;
        }
        $naam = basename( dirname( $bestand ) ) . '/' . basename( $bestand, '.json' );
        $uit  = sfp_page_config_tool_controleer_tekst( file_get_contents( $bestand ) );
        ok( ! $uit['fouten'], $naam . ': geen fouten ' . json_encode( $uit['fouten'], JSON_UNESCAPED_UNICODE ) );
        if ( $uit['let_op'] ) {
            echo '  let op ' . $naam . ': ' . implode( ' | ', $uit['let_op'] ) . "\n";
        }
        vergelijk_js( $naam, $uit['beschrijving'], maak_sets( $uit['beschrijving'], 200 ) );
    }
}

echo "\n$aantal controles, $fouten fouten\n";
exit( $fouten ? 1 : 0 );
