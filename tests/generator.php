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
ok( '"Citaat' === sfp_page_config_generator_filter( $b, array(), '"citaat', 'x', 'hoofdletter', null ), 'hoofdletter na een aanhalingsteken' );
ok( 'je beheert' === sfp_page_config_generator_filter( $b, array(), 'Je beheert', 'x', 'kleinbegin', null ), 'kleine beginletter' );
ok( 'creeren-en-doen' === sfp_page_config_generator_filter( $b, array(), 'Creëren en doen!', 'x', 'slug', null ), 'slug zonder accenten' );
ok( 'Aa • Bee' === sfp_page_config_generator_vul( '{vink|label|samen: • }', $b, array( 'vink' => array( 'a', 'b' ) ) ), 'vinkjes samenvoegen met eigen teken' );
ok( 'sara' === sfp_page_config_generator_vul( '{naam}', $b, array( 'naam' => '  sara  ' ) ), 'kort veld zonder spaties eromheen' );
ok( 'Zin een.' === sfp_page_config_generator_filter( $b, array(), 'Zin een. Zin twee.', 'x', 'eerstezin', null ), 'eerste zin' );
ok( 'Klaar.' === sfp_page_config_generator_filter( $b, array(), 'Klaar', 'x', 'zin', null ) && 'Klaar?' === sfp_page_config_generator_filter( $b, array(), 'Klaar?', 'x', 'zin', null ), 'zin met punt' );
$m = sfp_page_config_generator_mail_antwoorden( $b, array( 'rol' => 'x', 'naam' => str_repeat( 'a', 200 ), 'tekst' => 'lang verhaal', 'vreemd' => 'x' ) );
ok( ! isset( $m['tekst'] ) && ! isset( $m['vreemd'] ) && 80 === mb_strlen( $m['naam'] ), 'mail: geen tekstvak, onbekend veld weg, kort veld afgekapt' );

$fout = sfp_page_config_generator_controleer( sfp_page_config_tool_normaliseer( array( 'soort' => 'generator', 'stappen' => array( array( 'velden' => array( array( 'id' => 'a', 'type' => 'raar' ), array( 'id' => 'a', 'type' => 'keuze' ) ) ) ), 'uitvoer' => array( array( 'type' => 'lijst', 'bank' => 'geen' ), array( 'type' => 'herhaal', 'over' => 'a' ) ) ) ) );
ok( count( $fout['fouten'] ) >= 4, 'controle vindt type, dubbel id, keuze zonder opties en ontbrekende bank ' . json_encode( $fout['fouten'] ) );

/* ---------- 1b. Rekentaal ---------- */

$rk = function ( $f, $namen = array() ) {
    $boom = sfp_page_config_reken_boom( $f );
    return null === $boom ? 'FOUT' : sfp_page_config_reken_uit( $boom, function ( $n ) use ( $namen ) { return $namen[ $n ] ?? null; } );
};
ok( 7.0 === $rk( '1 + 2 * 3' ) && 9.0 === $rk( '(1 + 2) * 3' ) && -1.0 === $rk( '-(3 - 2)' ), 'rekenen met voorrang en haakjes' );
ok( null === $rk( '1 / 0' ) && null === $rk( 'x + 1' ), 'delen door nul en lege naam geven leeg' );
ok( true === $rk( 'a = "los" en b >= 3', array( 'a' => 'los', 'b' => '4' ) ) && false === $rk( 'niet (a = "los")', array( 'a' => 'los' ) ), 'vergelijken, en, niet' );
ok( -13.0 === $rk( 'afronden(-12.5)' ) && 13.0 === $rk( 'afronden(12.5)' ) && 2.35 === $rk( 'afronden(2.345, 2)' ), 'afronden van nul af' );
ok( 3.0 === $rk( 'tel(l, 9, 10)', array( 'l' => array( 9, 10, 8, 10.0, 11 ) ) ) && 2.0 === $rk( 'aantal(l)', array( 'l' => array( 1, 2 ) ) ), 'tel en aantal' );
ok( 5.0 === $rk( 'max(1, l, 3)', array( 'l' => array( 5, 2 ) ) ) && 1.0 === $rk( 'min(l)', array( 'l' => array( 5, 1 ) ) ) && 6.0 === $rk( 'som(l)', array( 'l' => array( 5, 1 ) ) ), 'min, max en som over lijsten' );
ok( 'b' === $rk( 'als(x > 2, "a", "b")', array( 'x' => 1 ) ) && true === $rk( 'bevat(l, "zorg")', array( 'l' => array( 'zorg', 'ict' ) ) ), 'als en bevat' );
ok( 4.0 === $rk( 'woorden(t)', array( 't' => "Eén twee,  drie -- vier!" ) ), 'woorden tellen' );
ok( 'FOUT' === $rk( '1 +' ) && 'FOUT' === $rk( 'onbekend(1)' ) && 'FOUT' === $rk( 'a ; b' ) && 'FOUT' === $rk( '' ), 'foute formules' );
ok( '+12' === sfp_page_config_reken_formaat( 'teken', '', '12' ) && '1:00' === sfp_page_config_reken_formaat( 'minsec', '', '59.6' ) && '12,5%' === sfp_page_config_reken_formaat( 'procent', '1', '12.5' ), 'opmaak teken, minsec, procent' );
ok( '€ 12.346' === sfp_page_config_reken_formaat( 'euro', '', '12345.5' ) && '1:30' === sfp_page_config_reken_formaat( 'klok', '', '90' ) && 'x' === sfp_page_config_reken_formaat( 'getal', '', 'x' ), 'opmaak euro, klok en geen getal' );

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
    file_put_contents( $tpad, json_encode( array( 'tool_maanden' => sfp_page_config_artikel_tekst( 'tool_maanden' ), 'tool_getal_scheiding' => sfp_page_config_artikel_tekst( 'tool_getal_scheiding' ), 'tool_euro' => sfp_page_config_artikel_tekst( 'tool_euro' ) ) ) );
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
                        if ( 0 === mt_rand( 0, 4 ) ) {
                            $a[ $v['id'] ] = mt_rand( 0, 1 ) ? '0' : ( mt_rand( 0, 99 ) / 4 );
                        }
                        break;
                    case 'getallen':
                        $l = array();
                        for ( $k = mt_rand( 0, 25 ); $k > 0; $k-- ) {
                            $l[] = mt_rand( -2, 12 );
                        }
                        $a[ $v['id'] ] = implode( mt_rand( 0, 1 ) ? ', ' : "\n", $l ) . ( mt_rand( 0, 3 ) ? '' : ' x 7.5 ;3' );
                        break;
                    default:
                        $a[ $v['id'] ] = $vrij[ mt_rand( 0, count( $vrij ) - 1 ) ];
                }
            }
        }
        if ( mt_rand( 0, 1 ) ) {
            $a['_vink'] = array( '0.1', 'oef-a', 'x', '2.3', 'p2', 5 );
        }
        $sets[] = $a;
    }
    return $sets;
}

vergelijk_js( 'bouwstenen', $b, maak_sets( $b, 300 ) );

$r = sfp_page_config_tool_normaliseer( json_decode( <<<'JSON'
{
  "soort": "rekenaar",
  "stappen": [
    { "titel": "Invoer", "velden": [
      { "id": "methode", "type": "keuze", "label": "Methode", "verplicht": true, "opties": [ { "waarde": "aantal", "tekst": "Aantallen" }, { "waarde": "los", "tekst": "Losse scores" } ] },
      { "id": "p", "type": "getal", "label": "P", "toon_als": { "veld": "methode", "is": "aantal" } },
      { "id": "d", "type": "getal", "label": "D", "toon_als": { "veld": "methode", "is": "aantal" } },
      { "id": "scores", "type": "getallen", "label": "Scores", "min": 0, "max": 10, "toon_als": { "veld": "methode", "is": "los" } },
      { "id": "sector", "type": "lijst", "label": "Sector", "opties": [ { "waarde": "zorg", "tekst": "Zorg", "getallen": { "gem": 8, "top": 20.5 } }, { "waarde": "ict", "tekst": "ICT", "getallen": { "gem": 22, "top": 38 } }, { "waarde": "geen", "tekst": "Geen" } ] }
    ] },
    { "titel": "Plan", "velden": [
      { "id": "tijd", "type": "lijst", "label": "Tijd", "opties": [ { "waarde": "60", "tekst": "1 uur" }, { "waarde": "90", "tekst": "1,5 uur" } ] },
      { "id": "focus", "type": "vinkjes", "label": "Focus", "opties": [ { "waarde": "stem", "tekst": "Stem" }, { "waarde": "lijf", "tekst": "Lijf" }, { "waarde": "leeg", "tekst": "Leeg" } ] },
      { "id": "dagen", "type": "keuze", "label": "Dagen", "opties": [ { "waarde": "3", "tekst": "3 dagen", "getallen": { "n": 3 } }, { "waarde": "7", "tekst": "Week", "getallen": { "n": 7 } } ] }
    ] }
  ],
  "reken": [
    { "id": "pp", "formule": "als(methode = \"los\", tel(scores, 9, 10), p)" },
    { "id": "dd", "formule": "als(methode = \"los\", tel(scores, 0, 6), d)" },
    { "id": "pas", "formule": "als(methode = \"los\", tel(scores, 7, 8), 0)" },
    { "id": "totaal", "formule": "pp + pas + dd" },
    { "id": "score", "formule": "afronden(pp / totaal * 100 - dd / totaal * 100)" },
    { "id": "act", "formule": "afronden(tijd * 12 / 100)" },
    { "id": "rest", "formule": "tijd - act" }
  ],
  "controles": [ { "formule": "totaal < 1", "melding": "Vul iets in.", "stap": 1 } ],
  "oordeel": { "op": "score", "labels": [ { "vanaf": 0, "sleutel": "matig", "tekst": "Matig" }, { "vanaf": 31, "sleutel": "sterk", "tekst": "Sterk" }, { "vanaf": -100, "sleutel": "zorg", "tekst": "Zorgelijk" } ] },
  "banken": {
    "acties": { "sterk": [ "Vier het", "Houd vast" ], "matig": [ "Vraag door" ], "zorg": [ "Grijp in {sector|label}" ] },
    "vormen": [
      { "naam": "Quiz", "fase": "act", "tijd": 5, "doel": [ "kennis" ], "min": 1, "max": 30 },
      { "naam": "Stelling", "fase": "act", "tijd": 10, "doel": [ "attitude" ], "min": 6, "max": 40 },
      { "naam": "Casus", "fase": "toe", "tijd": 30, "doel": [ "vaardigheid" ], "min": 2, "max": 20 },
      { "naam": "Rollenspel", "fase": "toe", "tijd": 20, "doel": [ "vaardigheid", "gedrag" ], "min": 2, "max": 12 },
      { "naam": "Kort", "fase": "act", "tijd": 2, "doel": [], "min": 1, "max": 200 },
      "Losse tekst"
    ],
    "oef": [ { "categorie": "stem", "tekst": "Adem" }, { "categorie": "stem", "tekst": "Volume" }, { "categorie": "lijf", "tekst": "Sta" } ]
  },
  "uitvoer": [
    { "type": "getal", "label": "Jouw score", "waarde": "score", "formaat": "teken", "onder": "{oordeel_tekst} ({oordeel})" },
    { "type": "verdeling", "kop": "Verdeling", "delen": [ { "label": "Pro", "waarde": "pp" }, { "label": "Pas", "waarde": "pas" }, { "label": "Contra", "waarde": "dd" } ] },
    { "type": "gegevens", "kop": "Benchmark {sector|label}", "regels": [ { "label": "Gemiddeld", "tekst": "{sector.gem|getal:1}" }, { "label": "Top", "tekst": "{=sector.top * 2|euro:2}" }, { "label": "Verschil", "tekst": "{=score - sector.gem|teken}" } ] },
    { "type": "lijst", "kop": "Acties", "bank": "acties", "pad": [ "{oordeel}" ] },
    { "type": "tekst", "als": { "formule": "score >= 31" }, "tekst": "Boven de dertig: {score|getal}." },
    { "type": "kaarten", "kop": "Activatie ({act|klok})", "bank": "vormen", "filter": "item.fase = \"act\" en item.tijd <= act + 1", "voorkeur": { "formule": "bevat(item.doel, \"kennis\")", "minimaal": 1 }, "terugval": { "filter": "item.fase = \"act\"", "aantal": 1 }, "sorteer": "abs(item.tijd - act / 2)", "aantal": "2", "kaartkop": "{nr}. {item.naam}", "inhoud": [ { "type": "tekst", "tekst": "{item.tijd} min, groep {item.min} tot {item.max}" } ] },
    { "type": "kaarten", "bank": "vormen", "sorteer": "item.tijd", "aflopend": true, "aantal": "aantal(focus) + 1", "inhoud": [ { "type": "tekst", "tekst": "{nr}: {item.naam|of:item.tekst}" } ] },
    { "type": "kaarten", "kop": "Oefenen", "bank": "oef", "om_en_om": { "veld": "focus", "sleutel": "categorie" }, "aantal": "dagen.n * 2", "groeperen": { "per": "2", "kop": "Dag {groep} van {groepen}" }, "inhoud": [ { "type": "lijst", "items": [ "{item.tekst} ({nr})" ] } ], "leeg": "Kies een focus." },
    { "type": "kaarten", "aantal": "min(3, aantal(focus))", "inhoud": [ { "type": "tekst", "tekst": "Slide {nr}: {=(nr - 1) * rest * 60 / 3|minsec}" } ] },
    { "type": "afvink", "kop": "Check", "items": [ { "id": "oef-a", "tekst": "Oefen hardop" }, { "tekst": "Zonder id" }, { "id": "p2", "tekst": "Alleen bij week", "als": { "formule": "dagen.n >= 7" } } ] }
  ]
}
JSON
, true ) );
$c = sfp_page_config_generator_controleer( $r );
ok( ! $c['fouten'], 'rekenaar: geen fouten ' . json_encode( $c, JSON_UNESCAPED_UNICODE ) );
ok( 'rekenaar' === $r['soort'] && 'sterk' === $r['oordeel']['labels'][0]['sleutel'], 'rekenaar: soort blijft, labels hoogste eerst' );
$bl = sfp_page_config_generator_bouw( $r, array( 'methode' => 'los', 'scores' => "10 9 9, 8;7\n3 11 x", 'sector' => 'zorg', 'tijd' => '90', 'focus' => array( 'lijf', 'stem' ), 'dagen' => '3', '_vink' => array( 'oef-a' ) ) );
$tekst = json_encode( $bl, JSON_UNESCAPED_UNICODE );
ok( 'getal' === $bl[0]['type'] && '+33' === $bl[0]['tekst'] && 'Sterk (sterk)' === $bl[0]['onder'], 'rekenaar: score uit losse scores en oordeel ' . $tekst );
ok( false !== strpos( $tekst, '["Pro","3",50]' ) && false !== strpos( $tekst, '"Gemiddeld","8,0"' ) && false !== strpos( $tekst, '€ 41,00' ) && false !== strpos( $tekst, '"Verschil","+25"' ), 'rekenaar: verdeling, getallen per optie, formules in tekst' );
ok( false !== strpos( $tekst, 'Activatie (0:11)' ) && false !== strpos( $tekst, '1. Quiz' ) && false === strpos( $tekst, '. Stelling' ), 'kaarten: filter, voorkeur, klok' );
ok( false !== strpos( $tekst, 'Dag 1 van 3' ) && false !== strpos( $tekst, '["Adem (1)"]' ) && false !== strpos( $tekst, '["Sta (2)"]' ) && false !== strpos( $tekst, '["Volume (3)"]' ) && false !== strpos( $tekst, '"1: Casus"' ) && false !== strpos( $tekst, '"3: Stelling"' ), 'kaarten: om en om per dag, aflopend sorteren ' . $tekst );
ok( false !== strpos( $tekst, 'Slide 2: 26:20' ), 'kaarten: aantal en minsec' );
ok( false !== strpos( $tekst, '"ids":["oef-a","9.1"],"aan":[true,false]' ), 'afvink: ids en vinkjes ' . $tekst );
ok( array( '!0' ) === sfp_page_config_generator_ontbreekt( $r, array( 'methode' => 'aantal', 'p' => '0', 'd' => '0' ), $r['stappen'][0] ), 'controle blokkeert de stap' );
vergelijk_js( 'rekenaar', $r, maak_sets( $r, 600 ) );

$mappen = array( SFP_PAGE_CONFIG_DIR . 'tests/tool-voorbeelden' );
if ( isset( $argv[1] ) ) {
    $mappen[] = $argv[1];
}
foreach ( $mappen as $map ) {
    foreach ( glob( rtrim( $map, '/' ) . '/{,*/}*.json', GLOB_BRACE ) as $bestand ) {
        $data = json_decode( file_get_contents( $bestand ), true );
        if ( ! is_array( $data ) || ! in_array( $data['soort'] ?? '', sfp_page_config_generator_soorten(), true ) ) {
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
