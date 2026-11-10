<?php
/**
 * Run with: php tests/calculator-reken.php
 *
 * Vergelijkt de rekenregel in PHP (includes/calculator-reken.php) met die in
 * JavaScript (assets/calculator/motor.js) op 40 scenario's, en controleert
 * een reeks uitkomsten tegen de besluiten (claude/incompanycalculator-besluitenlog.md).
 * Vraagt node.
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
$dir = dirname( __DIR__ );
require $dir . '/includes/calculator-norm.php';
require $dir . '/includes/calculator-reken.php';

function sfp_calculator_site_afk() {
    return 'DPS';
}

$norm = sfp_calculator_site_norm( array() );

$a = function ( $n, $vorm, array $extra = array() ) {
    return array_merge(
        array(
            'route'  => 'A',
            'mensen' => array(
                'soort'  => 'getal',
                'aantal' => $n,
            ),
            'vorm'   => $vorm,
        ),
        $extra
    );
};

$scenarios = array();
foreach ( array( 1, 3, 4, 8, 9, 10, 11, 16, 17, 30, 33, 40, 41 ) as $n ) {
    $scenarios[ 'aantal ' . $n . ', 1 dag' ]   = $a( $n, 1 );
    $scenarios[ 'aantal ' . $n . ', 2 dagen' ] = $a( $n, 2 );
}
$scenarios['marge 6 tot 14, 2 dagen']           = array( 'route' => 'A', 'mensen' => array( 'soort' => 'marge', 'van' => 6, 'tot' => 14 ), 'vorm' => 2 );
$scenarios['marge 20 tot 12 (omgedraaid), 3']   = array( 'route' => 'A', 'mensen' => array( 'soort' => 'marge', 'van' => 20, 'tot' => 12 ), 'vorm' => 3 );
$scenarios['onbekend, 2 dagen met begeleiding'] = array( 'route' => 'A', 'mensen' => array( 'soort' => 'onbekend' ), 'vorm' => 2, 'begeleiding' => true );
$scenarios['8, 2 dagen met begeleiding']        = $a( 8, 2, array( 'begeleiding' => true ) );
$scenarios['8, 3 dagen alle extras']            = $a(
    8,
    3,
    array(
        'extras' => array(
            'coachuren' => array( 'deelnemers' => 8, 'uren' => 4 ),
            'acteur'    => 2,
            'terugkom'  => 'dagdeel',
            'lotj'      => array( 'deelnemers' => 8, 'uren' => 4 ),
            'scan'      => 'team',
        ),
    )
);
$scenarios['7, 1 dag clinic boek scan individueel'] = $a( 7, 1, array( 'extras' => array( 'clinic' => true, 'boek' => true, 'scan' => 'individueel', 'terugkom' => 'online' ) ) );
$scenarios['32, 2 dagen voordeel korting']      = $a( 32, 2, array( 'groepsvoordeel' => 'korting' ) );
$scenarios['32, 3 dagen voordeel coachuren']    = $a( 32, 3, array( 'groepsvoordeel' => 'coachuren', 'extras' => array( 'coachuren' => array( 'deelnemers' => 5, 'uren' => 12 ) ) ) );
$scenarios['17, 2 dagen 3 deelnemers 8 uur']    = $a( 17, 2, array( 'extras' => array( 'coachuren' => array( 'deelnemers' => 3, 'uren' => 8 ), 'lotj' => array( 'deelnemers' => 40, 'uren' => 5 ) ) ) );
$scenarios['10, 2 dagen acteur te veel']        = $a( 10, 2, array( 'extras' => array( 'acteur' => 9 ) ) );
$scenarios['route B']                           = array( 'route' => 'B', 'sprekers' => 6 );
$scenarios['route C wordt A']                   = array( 'route' => 'C', 'mensen' => array( 'soort' => 'getal', 'aantal' => 8 ), 'vorm' => 1 );
$scenarios['0 mensen']                          = $a( 0, 2 );
$scenarios['geen vorm']                         = $a( 8, '' );
$scenarios['24, 3 dagen boek clinic voordeel']  = $a( 24, 3, array( 'extras' => array( 'boek' => true, 'clinic' => true ) ) );

$fout = 0;
if ( count( $scenarios ) < 40 ) {
    fwrite( STDERR, "Minder dan 40 scenario's.\n" );
    $fout++;
}

// JavaScript draaien.
$tmp = sys_get_temp_dir() . '/sfp-calc-' . getmypid() . '.json';
file_put_contents( $tmp, json_encode( array( 'norm' => $norm, 'scenarios' => $scenarios ) ) );
$js  = 'const m=require(' . json_encode( $dir . '/assets/calculator/motor.js' ) . ');'
    . 'const d=JSON.parse(require("fs").readFileSync(' . json_encode( $tmp ) . ',"utf8"));'
    . 'const u={};for(const [k,s] of Object.entries(d.scenarios)){u[k]=m.reken(s,d.norm);}'
    . 'process.stdout.write(JSON.stringify(u));';
$uit = shell_exec( 'node -e ' . escapeshellarg( $js ) );
unlink( $tmp );
$js_uit = json_decode( (string) $uit, true );
if ( ! is_array( $js_uit ) ) {
    fwrite( STDERR, "node gaf geen uitkomst.\n" );
    exit( 1 );
}

/** Maak beide kanten vergelijkbaar (sleutels gesorteerd, getallen als getal). */
function sfp_t_norm( $w ) {
    if ( is_array( $w ) ) {
        $lijst = array_keys( $w ) === range( 0, count( $w ) - 1 );
        $uit   = array();
        foreach ( $w as $k => $v ) {
            $uit[ $k ] = sfp_t_norm( $v );
        }
        if ( ! $lijst ) {
            ksort( $uit );
        }
        return $uit;
    }
    if ( is_float( $w ) && floor( $w ) === $w ) {
        return (int) $w;
    }
    return $w;
}

$php_uit = array();
foreach ( $scenarios as $naam => $s ) {
    $php_uit[ $naam ] = sfp_calculator_reken( $s, $norm );
    $p                = json_encode( sfp_t_norm( json_decode( json_encode( $php_uit[ $naam ] ), true ) ) );
    $j                = json_encode( sfp_t_norm( $js_uit[ $naam ] ) );
    if ( $p !== $j ) {
        fwrite( STDERR, "Verschil bij '$naam':\n PHP $p\n JS  $j\n" );
        $fout++;
    }
}

// Uitkomsten tegen de besluiten.
$t = function ( $naam, $wel, $tekst ) use ( &$fout ) {
    if ( ! $wel ) {
        fwrite( STDERR, "Fout: $naam: $tekst\n" );
        $fout++;
    }
};
$u = function ( $naam ) use ( $php_uit ) {
    return $php_uit[ $naam ]['uitkomst'];
};
$t( 'B1', array( 8, 8, 7, 7 ) === $u( 'aantal 30, 1 dag' )['verdeling'], '30 = 8, 8, 7, 7' );
$t( 'B1', 1 === $u( 'aantal 10, 2 dagen' )['groepen'] && in_array( 'negen_tien', $u( 'aantal 10, 2 dagen' )['meldingen'], true ), '10 = 1 groep met melding' );
$t( 'B1', 2 === $u( 'aantal 11, 1 dag' )['groepen'], 'vanaf 11 twee groepen' );
$t( 'B1', in_array( 'klein', $u( 'aantal 3, 1 dag' )['meldingen'], true ) && ! in_array( 'klein', $u( 'aantal 4, 1 dag' )['meldingen'], true ), 'melding bij 1 tot 3' );
$t( 'B1', 3000 === $u( 'aantal 1, 1 dag' )['totaal_excl'], 'groepsprijs blijft bij 1 deelnemer' );
$t( 'B1', ! empty( $php_uit['aantal 33, 1 dag']['maatwerk'] ) && empty( $php_uit['aantal 30, 1 dag']['maatwerk'] ), 'maatwerk vanaf 5 groepen' );
$t( 'B2', 6000 === $u( 'aantal 8, 2 dagen' )['totaal_excl'], '2 dagen = 6000' );
$t( 'B3', 7000 === $u( '8, 2 dagen met begeleiding' )['totaal_excl'], '2 dagen met begeleiding = 7000' );
$t( 'B12', 22000 === $u( '32, 2 dagen voordeel korting' )['totaal_excl'] && 2000 === $u( '32, 2 dagen voordeel korting' )['voordeel'], '4 groepen van 2 dagen: 24000 met 2000 voordeel' );
$alle = $u( '8, 3 dagen alle extras' );
// 8500 + coach 8x1000 + clinic gratis + boek gratis + acteur 2x1250 + terug 1500 + lotj 8x1000 + teamscan 750.
$t( 'extras', 8500 + 8000 + 2500 + 1500 + 8000 + 750 === $alle['totaal_excl'], 'alle extra\'s bij 3 dagen' );
$zeven = $u( '7, 1 dag clinic boek scan individueel' );
// 3000 + clinic 1750 + boek 7x19,95=139,65 -> 140 + scan 1750 + online 750.
$t( 'btw', 7390 === $zeven['totaal_excl'] && 13 === $zeven['btw']['9'] && 1523 === $zeven['btw']['21'], 'btw per tarief' );
$t( 'abonnement', in_array( 'abonnement', $u( '32, 3 dagen voordeel coachuren' )['meldingen'], true ), 'abonnement vanaf 12 uur' );
$t( 'acteur', 2 === $u( '10, 2 dagen acteur te veel' )['regels'][2]['aantal'], 'acteur hoogstens dagen x groepen' );
$deel = $u( '17, 2 dagen 3 deelnemers 8 uur' );
// 3 groepen x 6000 + coach 3 deelnemers x 2 blokken x 1000 + boek gratis + lotj 17 (max groep) x 2 blokken (5 uur wordt 8) x 1000 - groepsvoordeel 1000.
$t( 'coachuren', 18000 + 6000 + 34000 - 1000 === $deel['totaal_excl'], 'deel van de groep, uren in blokken van 4' );
$t( 'gratis', 'boek' === $deel['regels'][2]['code'] && $deel['regels'][2]['gratis'], 'boek vanzelf inbegrepen vanaf 2 dagen' );
$t( 'route', 'A' === $php_uit['route C wordt A']['route'] && 3000 === $u( 'route C wordt A' )['totaal_excl'], 'route C bestaat niet meer' );
$t( 'marge', 10 === $php_uit['marge 6 tot 14, 2 dagen']['mensen']['midden'] && 6000 === $php_uit['marge 6 tot 14, 2 dagen']['bandbreedte']['van'] && 12000 === $php_uit['marge 6 tot 14, 2 dagen']['bandbreedte']['tot'], 'marge rekent met het midden' );

echo $fout ? "calculator-reken: $fout fout(en)\n" : 'calculator-reken: ' . count( $scenarios ) . " scenario's, PHP en JS gelijk, besluiten kloppen\n";
exit( $fout ? 1 : 0 );
