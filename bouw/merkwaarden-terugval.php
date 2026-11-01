<?php
/**
 * Bouw assets/merkwaarden/terugval.json: de kopie van de merkwaarden die met
 * de plugin meekomt, voor een site waar het ophalen nog nooit is gelukt.
 *
 * Draaien met de twee bestanden van main uit sfp-brand-source:
 *   php bouw/merkwaarden-terugval.php <pad>/branding.md <pad>/merkwaarden.md
 *
 * Gebruikt dezelfde lezer als de plugin; faalt die, dan schrijft het script
 * niets.
 */
if ( 'cli' !== PHP_SAPI || $argc < 3 ) {
    fwrite( STDERR, "Gebruik: php bouw/merkwaarden-terugval.php branding.md merkwaarden.md\n" );
    exit( 1 );
}
define( 'ABSPATH', __DIR__ . '/' );
class WP_Error {
    private $m;
    public function __construct( $c = '', $m = '' ) {
        $this->m = $m;
    }
    public function get_error_message() {
        return $this->m;
    }
}
function is_wp_error( $x ) {
    return $x instanceof WP_Error;
}
function apply_filters( $h, $v ) {
    return $v;
}
function add_action() {}
function add_filter() {}
require dirname( __DIR__ ) . '/includes/merkwaarden.php';

$bestanden = array(
    'nl' => $argv[1],
    'en' => $argv[2],
    'pt' => $argv[2],
);
$uit       = array();
foreach ( $bestanden as $taal => $pad ) {
    $tekst = sfp_page_config_merkwaarden_lees( (string) file_get_contents( $pad ), $taal );
    if ( is_wp_error( $tekst ) ) {
        fwrite( STDERR, $tekst->get_error_message() . "\n" );
        exit( 1 );
    }
    $tekst['hash']  = substr( md5( json_encode( array( $tekst['titel'], $tekst['kernzin'], $tekst['inleiding'], $tekst['waarden'], $tekst['samen'] ) ) ), 0, 10 );
    $uit[ $taal ] = $tekst;
}
$doel = dirname( __DIR__ ) . '/assets/merkwaarden/terugval.json';
if ( ! is_dir( dirname( $doel ) ) ) {
    mkdir( dirname( $doel ), 0755, true );
}
file_put_contents( $doel, json_encode( $uit, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" );
echo 'Geschreven: ' . $doel . "\n";
