<?php
/**
 * Toolmeting (3.24.0): draait tests/toolmeting.js met node, en controleert
 * dat de soorten van de motor de events aanroepen. Zonder node: alleen het
 * tweede deel, met een melding.
 *
 * Draaien: php tests/toolmeting.php
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
$map    = dirname( __DIR__ ) . '/assets/tool/';
$fouten = 0;
$checks = 0;

$aanroepen = array(
    'generator.js'       => array( 'tool.stap( i + 1, stappen.length )', 'tool.afgerond()', "tool.meet( 'kopieren' )", "tool.meet( 'opnieuw' )", 'data-sfp-meet' ),
    'generator-bouw.js'  => array( 's.meet' ),
    'test.js'            => array( 'tool.stap( i + 1, schermen.length )', 'tool.afgerond()', "tool.meet( 'opnieuw' )" ),
    'tekstanalyse.js'    => array( 'tool.afgerond()', "tool.meet( 'kopieren' )", "tool.meet( 'opnieuw' )" ),
    'motor.js'           => array( "zelf.meet( 'download_pdf' )", "zelf.meet( 'download_word' )", 'this.luister()' ),
);
foreach ( $aanroepen as $bestand => $nodig ) {
    $code = (string) file_get_contents( $map . $bestand );
    foreach ( $nodig as $stuk ) {
        $checks++;
        if ( false === strpos( $code, $stuk ) ) {
            $fouten++;
            echo "FOUT: $bestand mist $stuk\n";
        }
    }
    // Geen eigen GA-code in de motor.
    $checks++;
    if ( preg_match( '/\bgtag\s*\(|google-analytics|googletagmanager/', $code ) ) {
        $fouten++;
        echo "FOUT: $bestand bevat GA- of gtag-code\n";
    }
}

$checks++;
$leesmij = (string) file_get_contents( $map . 'LEESMIJ.md' );
foreach ( array( 'start', 'stap', 'afgerond', 'kopieren', 'download_pdf', 'download_word', 'slides_prompt', 'opnieuw' ) as $actie ) {
    if ( false === strpos( $leesmij, '`' . $actie . '`' ) ) {
        $fouten++;
        echo "FOUT: LEESMIJ beschrijft $actie niet\n";
    }
}

if ( '' !== trim( (string) shell_exec( 'command -v node 2>/dev/null' ) ) ) {
    $uit = (string) shell_exec( 'node ' . escapeshellarg( __DIR__ . '/toolmeting.js' ) . ' ' . escapeshellarg( $map ) . ' 2>&1' );
    echo $uit;
    $checks++;
    if ( 0 !== strpos( trim( substr( $uit, strrpos( rtrim( $uit ), "\n" ) ?: 0 ) ), 'OK' ) ) {
        $fouten++;
    }
} else {
    echo "  (node ontbreekt: gedrag van de events overgeslagen)\n";
}

if ( $fouten ) {
    echo "$fouten fouten\n";
    exit( 1 );
}
echo "OK: $checks checks\n";
