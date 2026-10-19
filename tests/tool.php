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

/* ---------- 4. Beschrijvingen uit een map, PHP tegen JS ---------- */
$map = $argv[1] ?? '';
if ( '' !== $map && is_dir( $map ) ) {
    $js = SFP_PAGE_CONFIG_DIR . 'assets/tool/';
    $harness = <<<'JS'
const fs = require('fs');
global.window = global; global.document = { readyState: 'loading', addEventListener(){}, querySelectorAll(){ return []; } };
window.sfpToolConfig = { teksten: {} };
eval(fs.readFileSync(process.argv[2] + 'motor.js', 'utf8'));
eval(fs.readFileSync(process.argv[2] + 'test.js', 'utf8'));
const b = JSON.parse(fs.readFileSync(process.argv[3], 'utf8'));
const sets = JSON.parse(fs.readFileSync(process.argv[4], 'utf8'));
const uit = sets.map(a => { const r = window.sfpToolMotor.rekenTest(b, a); const d = {}; Object.keys(r.dimensies).forEach(k => d[k] = r.dimensies[k].waarde); return { uitslag: r.uitslag, volgorde: r.volgorde, dimensies: d, compleet: r.compleet, top: r.top }; });
process.stdout.write(JSON.stringify(uit));
JS;
    file_put_contents( sys_get_temp_dir() . '/sfp-tool-harness.js', $harness );
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
        // Dertig willekeurige volledige antwoordsets, met vaste seed.
        mt_srand( crc32( $naam ) );
        $sets = array();
        for ( $i = 0; $i < 30; $i++ ) {
            $a = array();
            foreach ( $b['vragen'] as $v ) {
                if ( 'likert' === $v['type'] ) {
                    $a[ $v['id'] ] = 0 === $i ? 3 : mt_rand( 1, 5 );
                } elseif ( 'tekst' === $v['type'] ) {
                    $a[ $v['id'] ] = 'tekst';
                } else {
                    $a[ $v['id'] ] = $v['opties'][ mt_rand( 0, count( $v['opties'] ) - 1 ) ]['waarde'];
                }
            }
            $sets[] = $a;
        }
        $php = array();
        foreach ( $sets as $a ) {
            $r   = sfp_page_config_tool_reken( $b, $a );
            $d   = array();
            foreach ( $r['dimensies'] as $k => $x ) {
                $d[ $k ] = $x['waarde'];
            }
            $php[] = array( 'uitslag' => $r['uitslag'], 'volgorde' => $r['volgorde'], 'dimensies' => $d, 'compleet' => $r['compleet'], 'top' => $r['top'] );
            ok( $r['compleet'], $naam . ': volledige set is compleet' );
            ok( '' !== $r['uitslag'], $naam . ': uitslag niet leeg bij ' . json_encode( $a ) );
            ok( isset( $b['uitslagen'][ $r['uitslag'] ] ) || '' === $r['uitslag'], $naam . ': uitslag "' . $r['uitslag'] . '" heeft een tekst' );
        }
        $setpad = sys_get_temp_dir() . '/sfp-tool-sets.json';
        file_put_contents( $setpad, json_encode( $sets ) );
        $uit = shell_exec( 'node ' . escapeshellarg( sys_get_temp_dir() . '/sfp-tool-harness.js' ) . ' ' . escapeshellarg( $js ) . ' ' . escapeshellarg( $bestand ) . ' ' . escapeshellarg( $setpad ) . ' 2>&1' );
        $jsr = json_decode( (string) $uit, true );
        ok( is_array( $jsr ) && count( $jsr ) === count( $php ), $naam . ': JS leverde uitkomsten (' . substr( (string) $uit, 0, 200 ) . ')' );
        if ( is_array( $jsr ) ) {
            foreach ( $php as $i => $p ) {
                $j = $jsr[ $i ] ?? array();
                $gelijk = $p['uitslag'] === ( $j['uitslag'] ?? null ) && $p['volgorde'] === ( $j['volgorde'] ?? null ) && array_map( 'floatval', $p['dimensies'] ) == array_map( 'floatval', $j['dimensies'] ?? array() );
                ok( $gelijk, $naam . ' set ' . $i . ': PHP ' . json_encode( $p ) . ' JS ' . json_encode( $j ) );
            }
        }
        $tekst = sfp_page_config_tool_tekst_uitvoer( $b, sfp_page_config_tool_reken( $b, $sets[0] ), $naam );
        ok( strlen( $tekst ) > 40, $naam . ': tekstuitvoer' );
        $hoe = sfp_page_config_tool_hoe_rekent( $b );
        ok( strlen( $hoe ) > 40, $naam . ': hoe rekent' );
        echo "  $naam: " . count( $b['vragen'] ) . ' vragen, ' . count( $b['dimensies'] ) . " dimensies, regel {$b['uitkomst']['regel']}, voorbeelduitslag " . $php[1]['uitslag'] . "\n";
    }
}

echo "\n$aantal controles, $fouten fouten\n";
exit( $fouten ? 1 : 0 );
