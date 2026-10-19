<?php
/**
 * Tests voor de toolmotor (soort test).
 *
 *   php tests/tool.php                 de ingebouwde controles
 *   php tests/tool.php <map-met-json>  ook: elke beschrijving in die map
 *                                      controleren en PHP tegen JS leggen
 *                                      (node nodig)
 *
 * Legt sfp_page_config_tool_reken() (PHP) naast assets/tool/test.js met
 * dezelfde antwoorden: beide moeten dezelfde uitslag, dezelfde waarden per
 * dimensie en dezelfde volgorde geven.
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

function sfp_page_config_taal() {
    return 'nl';
}
require_once SFP_PAGE_CONFIG_DIR . 'includes/artikel-teksten.php';
require_once SFP_PAGE_CONFIG_DIR . 'includes/tool-beschrijving.php';

function sfp_page_config_tool_soorten() {
    return array( 'test' => 'tool_test' );
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

/* ---------- 1. Het Kolb-voorbeeld uit het ontwerp ---------- */
$kolb = array(
    'versie'    => 1,
    'soort'     => 'test',
    'dimensies' => array( 'doener' => 'Doener', 'dromer' => 'Dromer', 'denker' => 'Denker', 'beslisser' => 'Beslisser' ),
    'vragen'    => array(
        array( 'id' => 'rol', 'type' => 'context', 'tekst' => 'Rol?', 'opties' => array( array( 'waarde' => 'a', 'tekst' => 'A' ), array( 'waarde' => 'b', 'tekst' => 'B' ) ) ),
        array( 'id' => 'do1', 'type' => 'likert', 'dimensie' => 'doener', 'tekst' => 'x' ),
        array( 'id' => 'do2', 'type' => 'likert', 'dimensie' => 'doener', 'tekst' => 'x' ),
        array( 'id' => 'dr1', 'type' => 'likert', 'dimensie' => 'dromer', 'tekst' => 'x' ),
        array( 'id' => 'dr2', 'type' => 'likert', 'dimensie' => 'dromer', 'tekst' => 'x' ),
        array( 'id' => 'de1', 'type' => 'likert', 'dimensie' => 'denker', 'tekst' => 'x' ),
        array( 'id' => 'de2', 'type' => 'likert', 'dimensie' => 'denker', 'tekst' => 'x' ),
        array( 'id' => 'be1', 'type' => 'likert', 'dimensie' => 'beslisser', 'tekst' => 'x' ),
        array( 'id' => 'be2', 'type' => 'likert', 'dimensie' => 'beslisser', 'tekst' => 'x', 'omgekeerd' => true ),
        array( 'id' => 'aandacht', 'type' => 'tekst', 'na_uitslag' => true, 'optioneel' => true ),
    ),
    'score'     => array( 'per_dimensie' => 'percentage' ),
    'uitkomst'  => array(
        'regel'      => 'kwadrant',
        'assen'      => array(
            'x' => array( 'plus' => array( 'denker', 'beslisser' ), 'min' => array( 'doener', 'dromer' ) ),
            'y' => array( 'plus' => array( 'doener', 'beslisser' ), 'min' => array( 'dromer', 'denker' ) ),
        ),
        'kwadranten' => array( 'x+y+' => 'beslisser', 'x+y-' => 'denker', 'x-y+' => 'doener', 'x-y-' => 'dromer' ),
    ),
    'labels'    => array( array( 'vanaf' => 75, 'tekst' => 'Sterk' ), array( 'vanaf' => 40, 'tekst' => 'Matig' ), array( 'vanaf' => 0, 'tekst' => 'Zwak' ) ),
    'uitslagen' => array( 'doener' => array( 'titel' => 'Doener', 'tekst' => 'Je bent {uitslag.naam}.', 'lijsten' => array( 'tips' => array( 'a', 'b' ) ) ), 'dromer' => 'D', 'denker' => 'De', 'beslisser' => 'B' ),
    'uitvoer'   => array( array( 'type' => 'profiel' ), array( 'type' => 'balken' ), array( 'type' => 'lijst', 'kop' => 'Tips', 'bron' => 'uitslag.tips' ), array( 'type' => 'tekst', 'bron' => 'vraag.aandacht' ) ),
);
$b = sfp_page_config_tool_normaliseer( $kolb );
$b_kolb = $b;
$c = sfp_page_config_tool_controleer( $b );
ok( empty( $c['fouten'] ), 'kolb: geen fouten (' . implode( ' ', $c['fouten'] ) . ')' );
$r = sfp_page_config_tool_reken( $b, array( 'rol' => 'a', 'do1' => 5, 'do2' => 5, 'dr1' => 1, 'dr2' => 1, 'de1' => 3, 'de2' => 3, 'be1' => 4, 'be2' => 2, 'aandacht' => 'hoi' ) );
ok( $r['compleet'], 'kolb: compleet' );
ok( 100.0 === $r['dimensies']['doener']['waarde'], 'kolb: doener 100%' );
ok( 0.0 === $r['dimensies']['dromer']['waarde'], 'kolb: dromer 0%' );
ok( 75.0 === $r['dimensies']['beslisser']['waarde'], 'kolb: beslisser (4 + omgekeerd 2 = 4) 75%' );
ok( 'Sterk' === $r['dimensies']['doener']['label']['tekst'], 'kolb: label sterk' );
// x = (50 + 75) - (100 + 0) = 25 > 0 ; y = (100 + 75) - (0 + 50) = 125 > 0 -> beslisser
ok( 'beslisser' === $r['uitslag'], 'kolb: kwadrant beslisser, is ' . $r['uitslag'] );
ok( array( 'doener', 'beslisser', 'denker', 'dromer' ) === $r['volgorde'], 'kolb: volgorde' );
$r2 = sfp_page_config_tool_reken( $b, array( 'do1' => 5 ) );
ok( ! $r2['compleet'] && in_array( 'rol', $r2['ontbreekt'], true ) && ! in_array( 'aandacht', $r2['ontbreekt'], true ), 'kolb: ontbreekt zonder optionele' );
ok( 'Je bent Doener.' === sfp_page_config_tool_vul( '{uitslag.tekst}', $b, array_merge( $r, array( 'uitslag' => 'doener' ) ) ), 'vul plaatshouder' );
$tekst = sfp_page_config_tool_tekst_uitvoer( $b, array_merge( $r, array( 'uitslag' => 'doener' ) ), 'Kolb' );
ok( false !== strpos( $tekst, '1. a' ) && false !== strpos( $tekst, 'hoi' ), 'tekstuitvoer bevat lijst en vrij veld' );
ok( false !== strpos( sfp_page_config_tool_hoe_rekent( $b ), 'kwadrant' ) || false !== strpos( sfp_page_config_tool_hoe_rekent( $b ), 'assen' ), 'hoe rekent noemt de regel' );

/* ---------- 2. Andere regels ---------- */
$basis = array(
    'soort'     => 'test',
    'dimensies' => array( 'a' => 'A', 'b' => 'B', 'c' => 'C' ),
    'vragen'    => array(
        array( 'id' => 'q1', 'type' => 'keuze', 'tekst' => 'q', 'opties' => array( array( 'waarde' => 'x', 'tekst' => 'x', 'punten' => array( 'a' => 1 ) ), array( 'waarde' => 'y', 'tekst' => 'y', 'punten' => array( 'b' => 1 ) ) ) ),
        array( 'id' => 'q2', 'type' => 'keuze', 'tekst' => 'q', 'opties' => array( array( 'waarde' => 'x', 'tekst' => 'x', 'punten' => array( 'a' => 1 ) ), array( 'waarde' => 'y', 'tekst' => 'y', 'punten' => array( 'c' => 2 ) ) ) ),
        array( 'id' => 'q3', 'type' => 'keuze', 'tekst' => 'q', 'opties' => array( array( 'waarde' => 'x', 'tekst' => 'x', 'punten' => array( 'b' => 1 ) ), array( 'waarde' => 'y', 'tekst' => 'y', 'punten' => array( 'c' => 1 ) ) ) ),
    ),
    'uitslagen' => array( 'a' => 'A', 'b' => 'B', 'c' => 'C', 'a+b' => 'AB', 'ab' => 'AB2', 'niets' => 'N', 'laag' => 'L', 'hoog' => 'H' ),
);
$t1 = sfp_page_config_tool_normaliseer( array_merge( $basis, array( 'score' => array( 'per_dimensie' => 'som' ), 'uitkomst' => array( 'regel' => 'hoogste' ) ) ) );
$r = sfp_page_config_tool_reken( $t1, array( 'q1' => 'x', 'q2' => 'x', 'q3' => 'x' ) );
ok( 'a' === $r['uitslag'] && 2.0 === $r['dimensies']['a']['waarde'] && 2.0 === $r['dimensies']['a']['max'], 'som + hoogste' );
$r = sfp_page_config_tool_reken( $t1, array( 'q1' => 'y', 'q2' => 'x', 'q3' => 'y' ) ); // a1 b1 c1 -> gelijkspel -> a
ok( 'a' === $r['uitslag'] && $r['gelijkspel'], 'gelijkspel: eerste in volgorde' );
$t2 = sfp_page_config_tool_normaliseer( array_merge( $basis, array( 'score' => array( 'per_dimensie' => 'telling' ), 'uitkomst' => array( 'regel' => 'paar', 'vertaal' => array( 'a+b' => 'ab' ) ) ) ) );
$r = sfp_page_config_tool_reken( $t2, array( 'q1' => 'x', 'q2' => 'x', 'q3' => 'x' ) ); // a2 b1 c0
ok( 'ab' === $r['uitslag'], 'paar met vertaal: ' . $r['uitslag'] );
$t3 = sfp_page_config_tool_normaliseer( array_merge( $basis, array( 'uitkomst' => array( 'regel' => 'regels', 'regels' => array( array( 'als' => array( array( 'totaal', '>=', 3 ) ), 'dan' => 'hoog' ) ), 'anders' => array( 'regel' => 'paar', 'paren' => array( 'a+b' => 'ab' ), 'anders' => 'niets' ) ) ) ) );
$r = sfp_page_config_tool_reken( $t3, array( 'q1' => 'x', 'q2' => 'x', 'q3' => 'x' ) ); // totaal 3 -> hoog
ok( 'hoog' === $r['uitslag'], 'regels: totaal' );
$r = sfp_page_config_tool_reken( $t3, array( 'q1' => 'x', 'q2' => 'y', 'q3' => 'x' ) ); // a1 b1 c2 totaal 4 -> hoog
ok( 'hoog' === $r['uitslag'], 'regels: totaal 4' );
$t3b = sfp_page_config_tool_normaliseer( array_merge( $basis, array( 'uitkomst' => array( 'regel' => 'regels', 'regels' => array( array( 'als' => array( array( 'totaal', '>=', 10 ) ), 'dan' => 'hoog' ) ), 'anders' => array( 'regel' => 'paar', 'paren' => array( 'a+b' => 'ab' ), 'anders' => 'niets' ) ) ) ) );
$r = sfp_page_config_tool_reken( $t3b, array( 'q1' => 'x', 'q2' => 'x', 'q3' => 'x' ) ); // a2 b1 -> paar a+b -> ab
ok( 'ab' === $r['uitslag'], 'geneste anders paar: ' . $r['uitslag'] );
$r = sfp_page_config_tool_reken( $t3b, array( 'q1' => 'y', 'q2' => 'y', 'q3' => 'y' ) ); // b1 c3 -> b+c niet in paren -> niets
ok( 'niets' === $r['uitslag'], 'geneste anders vangnet: ' . $r['uitslag'] );
$t4 = sfp_page_config_tool_normaliseer( array_merge( $basis, array( 'uitkomst' => array( 'regel' => 'totaal', 'labels' => array( array( 'vanaf' => 3, 'tekst' => 'Hoog', 'sleutel' => 'hoog' ), array( 'vanaf' => 0, 'tekst' => 'Laag', 'sleutel' => 'laag' ) ) ) ) ) );
$r = sfp_page_config_tool_reken( $t4, array( 'q1' => 'x', 'q2' => 'y', 'q3' => 'y' ) ); // a1 c3 = 4
ok( 'hoog' === $r['uitslag'], 'totaal met eigen labels' );
$t5 = sfp_page_config_tool_normaliseer( array_merge( $basis, array( 'uitkomst' => array( 'regel' => 'hoogste', 'minimaal' => 3, 'anders' => 'niets' ) ) ) );
$r = sfp_page_config_tool_reken( $t5, array( 'q1' => 'x', 'q2' => 'x', 'q3' => 'x' ) );
ok( 'niets' === $r['uitslag'], 'minimaal' );
$t6 = sfp_page_config_tool_normaliseer( array_merge( $basis, array( 'uitkomst' => array( 'regel' => 'drempels' ), 'labels' => array( array( 'vanaf' => 2, 'tekst' => 'Sterk' ), array( 'vanaf' => 0, 'tekst' => 'Zwak' ) ) ) ) );
$r = sfp_page_config_tool_reken( $t6, array( 'q1' => 'x', 'q2' => 'x', 'q3' => 'x' ) );
ok( 'Sterk' === $r['niveaus']['a'] && 'Zwak' === $r['niveaus']['c'], 'drempels' );
$sel = sfp_page_config_tool_selectie( $r, 'laagste' );
ok( array( 'c' ) === $sel, 'selectie laagste' );

/* ---------- 3. Controle ---------- */
$c = sfp_page_config_tool_controleer_tekst( '{nee' );
ok( 1 === count( $c['fouten'] ), 'ongeldige JSON' );
$c = sfp_page_config_tool_controleer_tekst( wp_json( array( 'soort' => 'test', 'dimensies' => array( 'a' => 'A' ), 'vragen' => array( array( 'id' => 'x', 'type' => 'likert', 'dimensie' => 'b', 'tekst' => 'x' ), array( 'id' => 'x', 'type' => 'likert', 'dimensie' => 'a', 'tekst' => 'x' ) ) ) ) );
ok( count( $c['fouten'] ) >= 2, 'dubbele id en onbekende dimensie: ' . implode( ' | ', $c['fouten'] ) );
function wp_json( $a ) {
    return json_encode( $a );
}

/* ---------- 4. PHP tegen JS ---------- */

/**
 * Leg PHP en het script in de browser naast elkaar op dezelfde antwoorden.
 * Het script krijgt de genormaliseerde beschrijving, zoals de browser hem
 * krijgt. Zonder node: overslaan met een melding.
 */
function vergelijk_js( $naam, array $b, array $sets ) {
    static $harness = null;
    if ( null === $harness ) {
        $harness = '';
        if ( '' !== trim( (string) shell_exec( 'command -v node 2>/dev/null' ) ) ) {
            $harness = sys_get_temp_dir() . '/sfp-tool-harness-' . getmypid() . '.js';
            file_put_contents( $harness, <<<'JS'
const fs = require('fs');
global.window = global; global.document = { readyState: 'loading', addEventListener(){}, querySelectorAll(){ return []; } };
window.sfpToolConfig = { teksten: {} };
eval(fs.readFileSync(process.argv[2] + 'motor.js', 'utf8'));
eval(fs.readFileSync(process.argv[2] + 'test.js', 'utf8'));
const b = JSON.parse(fs.readFileSync(process.argv[3], 'utf8'));
const sets = JSON.parse(fs.readFileSync(process.argv[4], 'utf8'));
const uit = sets.map(a => { const r = window.sfpToolMotor.rekenTest(b, a); const d = {}; Object.keys(r.dimensies).forEach(k => d[k] = r.dimensies[k].waarde); return { uitslag: r.uitslag, volgorde: r.volgorde, dimensies: d, compleet: r.compleet, top: r.top, ontbreekt: r.ontbreekt, niveaus: r.niveaus }; });
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
    $bpad = sys_get_temp_dir() . '/sfp-tool-b-' . getmypid() . '.json';
    $spad = sys_get_temp_dir() . '/sfp-tool-s-' . getmypid() . '.json';
    file_put_contents( $bpad, json_encode( $browser ) );
    file_put_contents( $spad, json_encode( $sets ) );
    $uit = shell_exec( 'node ' . escapeshellarg( $harness ) . ' ' . escapeshellarg( SFP_PAGE_CONFIG_DIR . 'assets/tool/' ) . ' ' . escapeshellarg( $bpad ) . ' ' . escapeshellarg( $spad ) . ' 2>&1' );
    $jsr = json_decode( (string) $uit, true );
    ok( is_array( $jsr ) && count( $jsr ) === count( $sets ), $naam . ': JS leverde uitkomsten (' . substr( (string) $uit, 0, 300 ) . ')' );
    if ( ! is_array( $jsr ) ) {
        return;
    }
    foreach ( $sets as $i => $a ) {
        $r = sfp_page_config_tool_reken( $b, $a );
        $d = array();
        foreach ( $r['dimensies'] as $k => $x ) {
            $d[ (string) $k ] = $x['waarde'];
        }
        $p = array(
            'uitslag'   => $r['uitslag'],
            'volgorde'  => array_map( 'strval', $r['volgorde'] ),
            'dimensies' => $d,
            'compleet'  => $r['compleet'],
            'top'       => array_map( 'strval', $r['top'] ),
            'ontbreekt' => array_map( 'strval', $r['ontbreekt'] ),
            'niveaus'   => $r['niveaus'],
        );
        $j      = $jsr[ $i ] ?? array();
        $gelijk = $p['uitslag'] === ( $j['uitslag'] ?? null )
            && $p['volgorde'] === ( $j['volgorde'] ?? null )
            && $p['compleet'] === ( $j['compleet'] ?? null )
            && $p['top'] === ( $j['top'] ?? null )
            && $p['ontbreekt'] === ( $j['ontbreekt'] ?? null )
            && array_map( 'floatval', $p['dimensies'] ) == array_map( 'floatval', $j['dimensies'] ?? array() )
            && (array) $p['niveaus'] == (array) ( $j['niveaus'] ?? array() );
        ok( $gelijk, $naam . ' set ' . $i . ' ' . json_encode( $a ) . ': PHP ' . json_encode( $p ) . ' JS ' . json_encode( $j ) );
    }
}

/**
 * Antwoordsets: volledig, deels leeg en met vreemde waarden zoals een
 * deellink ze kan bevatten.
 */
function maak_sets( array $b, $aantal, $vreemd = true ) {
    $sets = array();
    for ( $i = 0; $i < $aantal; $i++ ) {
        $a = array();
        foreach ( $b['vragen'] as $v ) {
            if ( $vreemd && $i > 5 && 0 === mt_rand( 0, 9 ) ) {
                $raar = array( true, false, null, array(), '1e1', '0x10', '', ' 3', '3.0', 3.0, 7, -1, 'x' );
                $a[ $v['id'] ] = $raar[ mt_rand( 0, count( $raar ) - 1 ) ];
                continue;
            }
            if ( $vreemd && $i > 5 && 0 === mt_rand( 0, 14 ) ) {
                continue;
            }
            if ( 'likert' === $v['type'] ) {
                $a[ $v['id'] ] = 0 === $i ? 3 : ( 0 === mt_rand( 0, 1 ) ? mt_rand( 1, 5 ) : (string) mt_rand( 1, 5 ) );
            } elseif ( 'tekst' === $v['type'] ) {
                $a[ $v['id'] ] = 'tekst';
            } elseif ( $v['opties'] ) {
                $a[ $v['id'] ] = $v['opties'][ mt_rand( 0, count( $v['opties'] ) - 1 ) ]['waarde'];
            }
        }
        $sets[] = $a;
    }
    return $sets;
}

mt_srand( 20261019 );
foreach ( array( 'kolb' => $b_kolb, 't1' => $t1, 't2' => $t2, 't3' => $t3, 't3b' => $t3b, 't4' => $t4, 't5' => $t5, 't6' => $t6 ) as $naam => $bx ) {
    vergelijk_js( $naam, $bx, maak_sets( $bx, 40 ) );
}

/* ---------- 5. Randgevallen (review 3.8.0-beta.2) ---------- */

// Afronden: 23 / 40 * 100 is in de computer 57,49999999999999; PHP en JS geven nu allebei 58.
ok( 58.0 === sfp_page_config_tool_rond( 23 / 40 * 100 ), 'rond: 57,4999… wordt 58' );
ok( -2.0 === sfp_page_config_tool_rond( -2.5 ), 'rond: -2,5 wordt -2 (half naar boven)' );
ok( '2.3' === sfp_page_config_tool_getal( 2.25 ) && '-2.2' === sfp_page_config_tool_getal( -2.25 ), 'getal: één decimaal, half naar boven' );
$likert = array();
foreach ( array( 'a', 'b' ) as $d ) {
    for ( $i = 1; $i <= 10; $i++ ) {
        $likert[] = array( 'id' => $d . $i, 'type' => 'likert', 'dimensie' => $d, 'tekst' => 'x' );
    }
}
$rond = sfp_page_config_tool_normaliseer( array( 'soort' => 'test', 'dimensies' => array( 'a' => 'A', 'b' => 'B' ), 'vragen' => $likert, 'score' => array( 'per_dimensie' => 'percentage' ), 'uitslagen' => array( 'a' => 'A', 'b' => 'B' ) ) );
$set  = array();
foreach ( array( 4, 4, 4, 3, 3, 3, 3, 3, 3, 3 ) as $i => $w ) { // som 33
    $set[ 'a' . ( $i + 1 ) ] = $w;
}
foreach ( array( 4, 4, 4, 4, 3, 3, 3, 3, 3, 3 ) as $i => $w ) { // som 34
    $set[ 'b' . ( $i + 1 ) ] = $w;
}
$r = sfp_page_config_tool_reken( $rond, $set );
ok( 58.0 === $r['dimensies']['a']['waarde'] && 60.0 === $r['dimensies']['b']['waarde'], 'percentage: 57,5 wordt 58' );
vergelijk_js( 'afronden', $rond, array_merge( array( $set ), maak_sets( $rond, 60, false ) ) );

// Dimensies met alleen cijfers: dezelfde volgorde in PHP en JS.
$cijfers = sfp_page_config_tool_normaliseer(
    array(
        'soort'     => 'test',
        'dimensies' => array( 'b' => 'B', '2' => 'Twee', '1' => 'Een' ),
        'vragen'    => array(
            array( 'id' => 'q1', 'type' => 'likert', 'dimensie' => 'b', 'tekst' => 'x' ),
            array( 'id' => 'q2', 'type' => 'likert', 'dimensie' => '2', 'tekst' => 'x' ),
            array( 'id' => 'q3', 'type' => 'likert', 'dimensie' => '1', 'tekst' => 'x' ),
        ),
        'uitkomst'  => array( 'regel' => 'paar' ),
        'uitslagen' => array( 'b' => 'B', '2' => '2', '1' => '1', 'b+2' => 'b2', 'b+1' => 'b1', '2+1' => '21' ),
    )
);
$r = sfp_page_config_tool_reken( $cijfers, array( 'q1' => 3, 'q2' => 3, 'q3' => 3 ) );
ok( 'b+2' === $r['uitslag'] && array( 'b', '2', '1' ) === $r['volgorde'], 'cijferdimensies: volgorde van de beschrijving (' . $r['uitslag'] . ')' );
vergelijk_js( 'cijferdimensies', $cijfers, array_merge( array( array( 'q1' => 3, 'q2' => 3, 'q3' => 3 ) ), maak_sets( $cijfers, 30 ) ) );

// Geneste anders met labels in willekeurige volgorde, zonder sleutel.
$genest = sfp_page_config_tool_normaliseer(
    array_merge(
        $basis,
        array(
            'uitkomst' => array(
                'regel'  => 'regels',
                'regels' => array( array( 'als' => array( array( 'totaal', '>=', 99 ) ), 'dan' => 'hoog' ) ),
                'anders' => array( 'regel' => 'totaal', 'labels' => array( array( 'vanaf' => 0, 'tekst' => 'laag' ), array( 'vanaf' => 3, 'tekst' => 'hoog' ) ) ),
            ),
        )
    )
);
ok( 3.0 === $genest['uitkomst']['anders']['labels'][0]['vanaf'] && 'hoog' === $genest['uitkomst']['anders']['labels'][0]['sleutel'], 'geneste labels gesorteerd, met sleutel' );
$r = sfp_page_config_tool_reken( $genest, array( 'q1' => 'x', 'q2' => 'y', 'q3' => 'y' ) ); // a1 c3 = 4
ok( 'hoog' === $r['uitslag'], 'geneste labels: hoog (' . $r['uitslag'] . ')' );
vergelijk_js( 'genest', $genest, maak_sets( $genest, 30 ) );

// == vergelijkt de tekst zoals hij is ("1.0" is niet "1").
$gelijk = sfp_page_config_tool_normaliseer(
    array(
        'soort'     => 'test',
        'dimensies' => array( 'a' => 'A' ),
        'vragen'    => array(
            array( 'id' => 'q1', 'type' => 'keuze', 'tekst' => 'q', 'opties' => array( array( 'waarde' => '1.0', 'tekst' => 'x', 'punten' => array( 'a' => 1 ) ), array( 'waarde' => '1', 'tekst' => 'y', 'punten' => array( 'a' => 2 ) ) ) ),
            array( 'id' => 'vrij', 'type' => 'tekst', 'tekst' => 'q' ),
        ),
        'uitkomst'  => array(
            'regel'  => 'regels',
            'regels' => array( array( 'als' => array( array( 'vraag.q1', '==', '1.0' ) ), 'dan' => 'punt' ), array( 'als' => array( array( 'vraag.vrij', '==', 'ja' ) ), 'dan' => 'vrij' ) ),
            'anders' => 'geen',
        ),
        'uitslagen' => array( 'punt' => 'P', 'geen' => 'G', 'vrij' => 'V' ),
    )
);
ok( 'punt' === sfp_page_config_tool_reken( $gelijk, array( 'q1' => '1.0' ) )['uitslag'], '== met 1.0' );
ok( 'geen' === sfp_page_config_tool_reken( $gelijk, array( 'q1' => '1' ) )['uitslag'], '== 1 is niet 1.0' );
ok( 'geen' === sfp_page_config_tool_reken( $gelijk, array( 'q1' => '1', 'vrij' => 'ja' ) )['uitslag'], 'vrije tekst telt niet in regels' );
vergelijk_js( 'gelijk', $gelijk, array( array( 'q1' => '1.0' ), array( 'q1' => '1' ), array( 'q1' => 1.0 ), array( 'q1' => 1 ), array( 'q1' => true ), array( 'q1' => '1', 'vrij' => 'ja' ) ) );

// Likert: alleen 1 tot 5.
$r = sfp_page_config_tool_reken( $rond, array( 'a1' => true, 'a2' => array(), 'a3' => '1e1', 'a4' => '0x10', 'a5' => 3.0, 'a6' => '3', 'a7' => ' 3', 'a8' => 6 ) );
ok( array( 'a1', 'a2', 'a3', 'a4', 'a7', 'a8' ) === array_values( array_intersect( $r['ontbreekt'], array( 'a1', 'a2', 'a3', 'a4', 'a5', 'a6', 'a7', 'a8' ) ) ), 'likert: vreemde waarden ontbreken, 3 en 3.0 tellen (' . implode( ',', $r['ontbreekt'] ) . ')' );
vergelijk_js( 'likert-vreemd', $rond, array( array( 'a1' => true, 'a2' => array(), 'a3' => '1e1', 'a4' => '0x10', 'a5' => 3.0, 'a6' => '3', 'a7' => ' 3', 'a8' => 6, 'a9' => null, 'a10' => false ) ) );

// paar met "anders": null en minimaal: null gedragen zich als afwezig.
$paar = sfp_page_config_tool_normaliseer( array_merge( $basis, array( 'uitkomst' => array( 'regel' => 'paar', 'anders' => null, 'minimaal' => null, 'vertaal' => array( 'a+b' => null ) ) ) ) );
ok( ! isset( $paar['uitkomst']['anders'] ) && ! isset( $paar['uitkomst']['minimaal'] ) && array() === $paar['uitkomst']['vertaal'], 'null valt weg bij normaliseren' );
vergelijk_js( 'paar-null', $paar, maak_sets( $paar, 20 ) );

// Kwadrant met plus als losse tekst: wordt een lijst (de browser liep hierop vast).
$kw = sfp_page_config_tool_normaliseer( array_merge( $kolb, array( 'uitkomst' => array_merge( $kolb['uitkomst'], array( 'assen' => array( 'x' => array( 'plus' => 'denker', 'min' => 'doener' ), 'y' => array( 'plus' => array( 'doener' ), 'min' => 'dromer' ) ) ) ) ) ) );
ok( array( 'denker' ) === $kw['uitkomst']['assen']['x']['plus'] && empty( sfp_page_config_tool_controleer( $kw )['fouten'] ), 'kwadrant: plus als tekst wordt een lijst' );
vergelijk_js( 'kwadrant-tekst', $kw, maak_sets( $kw, 20 ) );

// Uitvoer: lijsten als tekst worden lijsten, rijen voor een tabel blijven rijen.
$uv = sfp_page_config_tool_normaliseer(
    array_merge(
        $basis,
        array(
            'uitvoer' => array(
                array( 'type' => 'vast', 'lijst' => 'een' ),
                array( 'type' => 'alle', 'lijsten' => 'tips', 'koppen' => 'Eerst' ),
                array( 'type' => 'tabel', 'bron' => 'lijsten.rijen' ),
                array( 'type' => 'laagste', 'lijst' => 'tips' ),
            ),
            'lijsten' => array( 'rijen' => array( array( 'a', 'b' ), array( 'c', 2 ) ) ),
        )
    )
);
ok( array( 'een' ) === $uv['uitvoer'][0]['lijst'] && array( 'tips' ) === $uv['uitvoer'][1]['lijsten'] && array( 'Eerst' ) === $uv['uitvoer'][1]['koppen'] && 'tips' === $uv['uitvoer'][3]['lijst'], 'uitvoer: losse teksten worden lijsten' );
ok( array( array( 'a', 'b' ), array( 'c', '2' ) ) === $uv['lijsten']['rijen'], 'tabel: rijen blijven rijen' );
$tekst = sfp_page_config_tool_tekst_uitvoer( $uv, sfp_page_config_tool_reken( $uv, array( 'q1' => 'x', 'q2' => 'x', 'q3' => 'x' ) ), 'T' );
ok( false !== strpos( $tekst, 'a | b' ) && false === strpos( $tekst, 'Array' ), 'tabel in tekstuitvoer' );

// Links: alleen http(s), een pad of een anker.
$link = sfp_page_config_tool_normaliseer( array_merge( $basis, array( 'bron' => array( 'model' => 'M', 'link' => '</script><script>alert(1)</script>' ), 'uitslagen' => array( 'a' => array( 'artikel' => 'javascript:alert(1)' ), 'b' => array( 'artikel' => '/blog/x' ), 'c' => array( 'artikel' => 'https://example.org/y' ) ) ) ) );
ok( '' === $link['bron']['link'] && '' === $link['uitslagen']['a']['artikel'] && '/blog/x' === $link['uitslagen']['b']['artikel'] && 'https://example.org/y' === $link['uitslagen']['c']['artikel'], 'links: alleen veilige adressen' );
$c = sfp_page_config_tool_controleer( $link );
ok( 2 === count( array_filter( $c['let_op'], function ( $m ) { return false !== strpos( $m, 'geen gewoon webadres' ); } ) ), 'links: controle meldt de genegeerde links' );

// Scoreregels die vragen niet meetellen: een let-op-punt.
$c = sfp_page_config_tool_controleer( sfp_page_config_tool_normaliseer( array_merge( $basis, array( 'score' => array( 'per_dimensie' => 'gecentreerd' ) ) ) ) );
ok( (bool) array_filter( $c['let_op'], function ( $m ) { return false !== strpos( $m, 'gecentreerd' ); } ), 'gecentreerd met keuzepunten: let op' );

// Vraag-id's met hoofdletters en punten blijven werken.
$ids = sfp_page_config_tool_normaliseer( array( 'soort' => 'test', 'dimensies' => array( 'a' => 'A' ), 'vragen' => array( array( 'id' => 'Q1', 'type' => 'likert', 'dimensie' => 'a', 'tekst' => 'x' ), array( 'id' => 'rol.1', 'type' => 'likert', 'dimensie' => 'a', 'tekst' => 'x' ) ), 'uitslagen' => array( 'a' => 'A' ) ) );
ok( sfp_page_config_tool_reken( $ids, array( 'Q1' => '4', 'rol.1' => 5 ) )['compleet'], 'id Q1 en rol.1' );

// Balken met sorteer vast: ook de tekstuitvoer (mail) in vaste volgorde.
$vast  = sfp_page_config_tool_normaliseer( array_merge( $basis, array( 'uitvoer' => array( array( 'type' => 'balken', 'sorteer' => 'vast', 'kop' => 'S' ) ) ) ) );
$tekst = sfp_page_config_tool_tekst_uitvoer( $vast, sfp_page_config_tool_reken( $vast, array( 'q1' => 'y', 'q2' => 'y', 'q3' => 'y' ) ), 'T' ); // c hoogst
ok( strpos( $tekst, '- A:' ) < strpos( $tekst, '- B:' ) && strpos( $tekst, '- B:' ) < strpos( $tekst, '- C:' ), 'balken vast: vaste volgorde in de tekst' );

/* ---------- 6. Beschrijvingen uit een map ---------- */
$map = $argv[1] ?? '';
if ( '' !== $map && is_dir( $map ) ) {
    foreach ( glob( rtrim( $map, '/' ) . '/*.json' ) as $bestand ) {
        $naam = basename( $bestand, '.json' );
        $c    = sfp_page_config_tool_controleer_tekst( file_get_contents( $bestand ) );
        ok( empty( $c['fouten'] ), $naam . ': fouten: ' . implode( ' | ', $c['fouten'] ) );
        if ( $c['let_op'] ) {
            echo "  let op $naam: " . implode( ' | ', $c['let_op'] ) . "\n";
        }
        if ( $c['fouten'] ) {
            continue;
        }
        $b = $c['beschrijving'];
        mt_srand( crc32( $naam ) );
        $sets = maak_sets( $b, 30, false );
        foreach ( $sets as $a ) {
            $r = sfp_page_config_tool_reken( $b, $a );
            ok( $r['compleet'], $naam . ': volledige set is compleet' );
            ok( '' !== $r['uitslag'], $naam . ': uitslag niet leeg bij ' . json_encode( $a ) );
            ok( isset( $b['uitslagen'][ $r['uitslag'] ] ) || '' === $r['uitslag'], $naam . ': uitslag "' . $r['uitslag'] . '" heeft een tekst' );
        }
        vergelijk_js( $naam, $b, array_merge( $sets, maak_sets( $b, 30 ) ) );
        $tekst = sfp_page_config_tool_tekst_uitvoer( $b, sfp_page_config_tool_reken( $b, $sets[0] ), $naam );
        ok( strlen( $tekst ) > 40, $naam . ': tekstuitvoer' );
        $hoe = sfp_page_config_tool_hoe_rekent( $b );
        ok( strlen( $hoe ) > 40, $naam . ': hoe rekent' );
        echo "  $naam: " . count( $b['vragen'] ) . ' vragen, ' . count( $b['dimensies'] ) . " dimensies, regel {$b['uitkomst']['regel']}\n";
    }
}

echo "\n$aantal controles, $fouten fouten\n";
exit( $fouten ? 1 : 0 );
