<?php
/**
 * Tests voor het blok Merkwaarden (3.23.0), met kleine WordPress-stubs.
 *
 * - de lezer: labels en koppen in nl, en en pt, uit de bestanden van main
 *   (tests/merkwaarden/), en een fout bij een onherkenbare opbouw;
 * - ophalen, transient, laatst gelukte versie en de meegeleverde kopie;
 * - verversen via de API met het raw-adres als terugval;
 * - het slot per taal: schoonmaken, melding voor beheerders, niets voor
 *   bezoekers;
 * - de weergave: kaarten met ankers, grens in een vlak, geen hexcode of
 *   fontnaam in de PHP en de CSS van dit onderdeel.
 *
 * Draaien: php tests/merkwaarden.php
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
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'SFP_PAGE_CONFIG_VERSION', 'test' );

/* ---------- Stubs ---------- */

$GLOBALS['options']    = array( 'sfp_settings' => array() );
$GLOBALS['transients'] = array();
$GLOBALS['ttl']        = array();
$GLOBALS['locale']     = 'nl_NL';
$GLOBALS['beheerder']  = false;
$GLOBALS['antwoorden'] = array(); // url => array( code, body ) of 'fout'.
$GLOBALS['opgevraagd'] = array();
$GLOBALS['codes']      = array();

class WP_Error {
    private $c;
    private $m;
    public function __construct( $c = '', $m = '' ) {
        $this->c = $c;
        $this->m = $m;
    }
    public function get_error_message() {
        return $this->m;
    }
    public function get_error_code() {
        return $this->c;
    }
}
function is_wp_error( $x ) {
    return $x instanceof WP_Error;
}
function get_option( $k, $d = false ) {
    return $GLOBALS['options'][ $k ] ?? $d;
}
function update_option( $k, $v ) {
    $GLOBALS['options'][ $k ] = $v;
    return true;
}
function get_transient( $k ) {
    return $GLOBALS['transients'][ $k ] ?? false;
}
function set_transient( $k, $v, $t = 0 ) {
    $GLOBALS['transients'][ $k ] = $v;
    $GLOBALS['ttl'][ $k ]        = $t;
    return true;
}
function delete_transient( $k ) {
    unset( $GLOBALS['transients'][ $k ] );
    return true;
}
function add_action() {}
function add_filter() {}
function apply_filters( $h, $v ) {
    return $v;
}
function shortcode_exists( $c ) {
    return isset( $GLOBALS['codes'][ $c ] );
}
function add_shortcode( $c, $f ) {
    $GLOBALS['codes'][ $c ] = $f;
}
function shortcode_atts( $standaard, $atts ) {
    return array_merge( $standaard, array_intersect_key( (array) $atts, $standaard ) );
}
function get_locale() {
    return $GLOBALS['locale'];
}
function current_user_can() {
    return $GLOBALS['beheerder'];
}
function is_user_logged_in() {
    return $GLOBALS['beheerder'];
}
function home_url( $p = '' ) {
    return 'https://voorbeeld.test' . $p;
}
function wp_remote_get( $url, $args = array() ) {
    $GLOBALS['opgevraagd'][] = array( $url, $args );
    $a                       = $GLOBALS['antwoorden'][ $url ] ?? 'fout';
    if ( 'fout' === $a ) {
        return new WP_Error( 'http', 'cURL error 28: time-out' );
    }
    return array(
        'code' => $a[0],
        'body' => $a[1],
    );
}
function wp_remote_retrieve_response_code( $r ) {
    return $r['code'];
}
function wp_remote_retrieve_body( $r ) {
    return $r['body'];
}
function wp_json_encode( $x ) {
    return json_encode( $x );
}
function wp_date( $f, $t ) {
    return gmdate( $f, $t );
}
function esc_html( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_url( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_url_raw( $s ) {
    return preg_match( '#^https?://#', $s ) ? $s : '';
}
function sanitize_text_field( $s ) {
    return trim( strip_tags( (string) $s ) );
}
function sanitize_title( $s ) {
    $s = strtolower( (string) $s );
    $s = strtr( $s, array( 'í' => 'i', 'é' => 'e', 'ê' => 'e', 'ó' => 'o', 'ú' => 'u', 'á' => 'a', 'ã' => 'a', 'ç' => 'c' ) );
    return trim( preg_replace( '/[^a-z0-9]+/', '-', $s ), '-' );
}
function sanitize_html_class( $s ) {
    return preg_replace( '/[^A-Za-z0-9_-]/', '', $s );
}
function absint( $x ) {
    return abs( (int) $x );
}
function get_post_status( $id ) {
    return 77 === $id ? 'publish' : false;
}
function get_permalink( $id ) {
    return 'https://voorbeeld.test/pagina-' . $id . '/';
}
function sfp_page_config_instellingen() {
    return (array) get_option( 'sfp_settings', array() );
}
function sfp_page_config_pad_lees( array $bron, $pad ) {
    foreach ( explode( '.', $pad ) as $deel ) {
        if ( ! is_array( $bron ) || ! array_key_exists( $deel, $bron ) ) {
            return null;
        }
        $bron = $bron[ $deel ];
    }
    return $bron;
}
function sfp_page_config_get_setting( $k, $d = '' ) {
    return $d;
}

require dirname( __DIR__ ) . '/includes/teksten.php';
require dirname( __DIR__ ) . '/includes/merkwaarden.php';

$fouten = 0;
$checks = 0;
function check( $voorwaarde, $wat ) {
    global $fouten, $checks;
    $checks++;
    if ( ! $voorwaarde ) {
        $fouten++;
        echo "FOUT: $wat\n";
    }
}
function laatste_log() {
    $log = get_option( SFP_PC_MW_LOG, array() );
    return $log ? $log[ count( $log ) - 1 ] : array();
}
function reset_stand() {
    $GLOBALS['transients'] = array();
    $GLOBALS['opgevraagd'] = array();
    unset( $GLOBALS['options'][ SFP_PC_MW_LAATST ], $GLOBALS['options'][ SFP_PC_MW_LOG ] );
}

$branding    = file_get_contents( __DIR__ . '/merkwaarden/branding.md' );
$merkwaarden = file_get_contents( __DIR__ . '/merkwaarden/merkwaarden.md' );
$bron        = sfp_page_config_merkwaarden_bronnen();

/* ---------- 1. De lezer ---------- */

$verwacht = array(
    'nl' => array( 'Onze merkwaarden', array( 'Robuust', 'Eenvoudig', 'Aansprekend' ), 'Samen', $branding ),
    'en' => array( 'Our brand values', array( 'Robust', 'Simple', 'Appealing' ), 'Together', $merkwaarden ),
    'pt' => array( 'Nossos valores de marca', array( 'Robusto', 'Simples', 'Cativante' ), 'Juntos', $merkwaarden ),
);
foreach ( $verwacht as $taal => $v ) {
    $t = sfp_page_config_merkwaarden_lees( $v[3], $taal );
    check( is_array( $t ), "$taal: lezer geeft een tekst" );
    if ( ! is_array( $t ) ) {
        echo $t->get_error_message() . "\n";
        continue;
    }
    check( $v[0] === $t['titel'], "$taal: titel" );
    check( array_column( $t['waarden'], 'naam' ) === $v[1], "$taal: drie waarden in volgorde" );
    check( $v[2] === $t['samen']['kop'], "$taal: kop Samen" );
    check( '' !== $t['kernzin'] && '' !== $t['inleiding'], "$taal: kernzin en inleiding" );
    foreach ( $t['waarden'] as $w ) {
        check( '' !== $w['vraag'] && '' !== $w['grens'] && 1 === count( $w['tekst'] ), "$taal: {$w['naam']} heeft vraag, één alinea tekst en grens" );
        check( false === strpos( $w['tekst'][0], '**' ), "$taal: geen label in de tekst van {$w['naam']}" );
    }
    check( false !== strpos( $t['samen']['tekst'][0], 'LEGO' ), "$taal: Samen noemt LEGO" );
}
// De uitleg boven Titel hoort niet in het blok.
$nl = sfp_page_config_merkwaarden_lees( $branding, 'nl' );
check( false === strpos( wp_json_encode( $nl ), 'Dit is de tekst die de sites tonen' ), 'nl: uitleg voor de bronlezer niet in het blok' );
// Andere delen van ## Merkwaarden (de controlevragen) lekken niet mee.
check( false === strpos( wp_json_encode( $nl ), 'Controlevragen' ), 'nl: alleen ### Voor bezoekers' );

// De volgorde binnen een waarde maakt niet uit: lezen op labels.
$omgedraaid = str_replace(
    "#### Robuust\n\n**Vraag:** Kun je vertrouwen op wat je ziet?",
    "#### Robuust\n\n**Grens:** Eerst de grens.\n\n**Vraag:** Kun je vertrouwen op wat je ziet?",
    $branding
);
$knip       = strpos( $omgedraaid, '#### Robuust' );
$omgedraaid = substr( $omgedraaid, 0, $knip ) . preg_replace( '/\n\*\*Grens:\*\* Stevig[^\n]*\n/', "\n", substr( $omgedraaid, $knip ), 1 );
$t          = sfp_page_config_merkwaarden_lees( $omgedraaid, 'nl' );
check( is_array( $t ) && 'Eerst de grens.' === $t['waarden'][0]['grens'], 'lezen op labels, niet op volgorde' );

// Onherkenbaar: geen sectie, een label weg, twee waarden.
check( is_wp_error( sfp_page_config_merkwaarden_lees( str_replace( '### Voor bezoekers', '### Bezoekers', $branding ), 'nl' ) ), 'zonder ### Voor bezoekers: fout' );
check( is_wp_error( sfp_page_config_merkwaarden_lees( str_replace( '## Merkwaarden', '## Kernwaarden', $branding ), 'nl' ) ), 'zonder ## Merkwaarden: fout' );
check( is_wp_error( sfp_page_config_merkwaarden_lees( str_replace( '**Question:** Can you trust', 'Can you trust', $merkwaarden ), 'en' ) ), 'zonder Question: fout' );
$twee = sfp_page_config_merkwaarden_lees( preg_replace( '/#### Eenvoudig.*?(?=#### Aansprekend)/s', '', $branding ), 'nl' );
check( is_wp_error( $twee ) && false !== strpos( $twee->get_error_message(), 'drie waarden' ), 'twee waarden: fout met uitleg' );
check( is_wp_error( sfp_page_config_merkwaarden_lees( $branding, 'de' ) ), 'onbekende taal: fout' );

/* ---------- 2. Ophalen en terugvallen ---------- */

reset_stand();
$GLOBALS['antwoorden'][ $bron['nl']['raw'] ] = array( 200, $branding );
$r = sfp_page_config_merkwaarden_tekst( 'nl' );
check( 'bron' === $r['herkomst'] && 'Onze merkwaarden' === $r['tekst']['titel'], 'eerste keer: opgehaald uit de bron' );
check( 12 * HOUR_IN_SECONDS === $GLOBALS['ttl'][ SFP_PC_MW_TRANSIENT . 'nl' ], 'transient van 12 uur' );
check( isset( get_option( SFP_PC_MW_LAATST )['nl'] ), 'laatst gelukte versie bewaard' );
check( $bron['nl']['raw'] === $GLOBALS['opgevraagd'][0][0], 'gewoon ophalen via het raw-adres' );
check( false !== strpos( $GLOBALS['opgevraagd'][0][1]['headers']['User-Agent'], 'SFP-Page-Config' ), 'met een user-agent' );

// Bron onbereikbaar: verversen geeft een fout, melding, laatste versie blijft.
$GLOBALS['transients']                       = array();
$GLOBALS['antwoorden'][ $bron['nl']['raw'] ] = 'fout';
check( is_wp_error( sfp_page_config_merkwaarden_ververs_taal( 'nl' ) ), 'onbereikbaar: ververs geeft een fout' );
check( 'fout' === ( laatste_log()['soort'] ?? '' ) && false !== strpos( laatste_log()['tekst'], 'niet bereikbaar' ), 'onbereikbaar: melding in het log' );
check( 'Onze merkwaarden' === get_option( SFP_PC_MW_LAATST )['nl']['titel'], 'onbereikbaar: laatst gelukte versie blijft staan' );

// Onherkenbare opbouw: ook de laatste versie, met melding.
$GLOBALS['antwoorden'][ $bron['nl']['raw'] ] = array( 200, str_replace( '**Kernzin:**', '**Zin:**', $branding ) );
check( is_wp_error( sfp_page_config_merkwaarden_ververs_taal( 'nl' ) ), 'onherkenbaar: fout' );
check( false !== strpos( laatste_log()['tekst'], 'Kernzin' ), 'onherkenbaar: melding noemt wat ontbreekt' );
check( 'Onze merkwaarden' === get_option( SFP_PC_MW_LAATST )['nl']['titel'], 'onherkenbaar: laatste versie niet overschreven' );

// Terugval in de weergave (een taal die in deze test nog niet gevraagd is).
reset_stand();
$GLOBALS['options'][ SFP_PC_MW_LAATST ]      = array( 'pt' => array_merge( sfp_page_config_merkwaarden_lees( $merkwaarden, 'pt' ), array( 'hash' => 'oud' ) ) );
$GLOBALS['antwoorden'][ $bron['pt']['raw'] ] = 'fout';
$r = sfp_page_config_merkwaarden_tekst( 'pt' );
check( 'laatst' === $r['herkomst'] && 'Nossos valores de marca' === $r['tekst']['titel'], 'onbereikbaar: blok toont de laatste versie' );
check( 15 * MINUTE_IN_SECONDS === $GLOBALS['ttl'][ SFP_PC_MW_TRANSIENT . 'pt' ], 'terugval een kwartier bewaard, niet bij elke bezoeker opnieuw proberen' );

// Nog nooit gelukt en onbereikbaar: de meegeleverde kopie.
reset_stand();
$GLOBALS['antwoorden'][ $bron['en']['raw'] ] = 'fout';
$r = sfp_page_config_merkwaarden_tekst( 'en' );
check( 'meegeleverd' === $r['herkomst'] && 'Our brand values' === $r['tekst']['titel'], 'nooit gelukt: meegeleverde kopie, blok niet leeg' );

// Meegeleverde kopie gelijk aan wat de lezer van de testbestanden maakt.
$kopie = json_decode( file_get_contents( dirname( __DIR__ ) . '/assets/merkwaarden/terugval.json' ), true );
foreach ( array( 'nl', 'en', 'pt' ) as $taal ) {
    $t = sfp_page_config_merkwaarden_lees( 'nl' === $taal ? $branding : $merkwaarden, $taal );
    check( $kopie[ $taal ]['waarden'] === $t['waarden'] && $kopie[ $taal ]['titel'] === $t['titel'], "meegeleverde kopie $taal gelijk aan de bron" );
}

/* ---------- 3. Verversen ---------- */

reset_stand();
$nieuw = str_replace( 'Onze merkwaarden', 'Onze merkwaarden (nieuw)', $branding );
$GLOBALS['antwoorden'][ $bron['nl']['api'] ] = array( 200, $nieuw );
$GLOBALS['antwoorden'][ $bron['nl']['raw'] ] = array( 200, $branding );
$GLOBALS['antwoorden'][ $bron['en']['api'] ] = 'fout';
$GLOBALS['antwoorden'][ $bron['en']['raw'] ] = array( 200, $merkwaarden );
$GLOBALS['antwoorden'][ $bron['pt']['api'] ] = array( 403, 'rate limit' );
$GLOBALS['antwoorden'][ $bron['pt']['raw'] ] = array( 200, $merkwaarden );
$GLOBALS['transients'][ SFP_PC_MW_TRANSIENT . 'nl' ] = array(
    'waarden' => array( 1 ),
    'titel'   => 'oud',
);
$v = sfp_page_config_merkwaarden_ververs();
check( $v['talen']['nl']['gelukt'] && 'Onze merkwaarden (nieuw)' === $v['talen']['nl']['titel'], 'verversen: via de API, meteen de nieuwe tekst' );
check( 'Onze merkwaarden (nieuw)' === get_transient( SFP_PC_MW_TRANSIENT . 'nl' )['titel'], 'verversen: transient vervangen' );
check( $v['talen']['en']['gelukt'] && $v['talen']['pt']['gelukt'], 'verversen: API weg of 403, dan het raw-adres' );
check( '' !== $v['stand']['talen']['nl']['opgehaald'], 'verversen: stand met tijd' );
check( 'ververst' === ( laatste_log()['soort'] ?? '' ), 'verversen: in het log' );
check( 'application/vnd.github.raw' === $GLOBALS['opgevraagd'][0][1]['headers']['Accept'], 'verversen: API vraagt de ruwe tekst' );

/* ---------- 4. Slot ---------- */

$schoon = sfp_page_config_merkwaarden_slot_schoon(
    array(
        'nl' => array(
            'zin'    => ' Klaar om te <b>bouwen</b>? ',
            'knop'   => 'Bekijk de huisstijl',
            'pagina' => '77',
            'link'   => 'javascript:alert(1)',
        ),
        'en' => array(
            'zin'  => '',
            'knop' => '',
        ),
        'de' => array( 'zin' => 'nee' ),
    )
);
check( 'Klaar om te bouwen?' === $schoon['nl']['zin'] && 77 === $schoon['nl']['pagina'] && '' === $schoon['nl']['link'], 'slot: geschoond, geen javascript-link' );
check( ! isset( $schoon['en'] ) && ! isset( $schoon['de'] ), 'slot: leeg en onbekende taal vallen weg' );

/* ---------- 5. Weergave ---------- */

reset_stand();
$GLOBALS['options']['sfp_settings']          = array();
$GLOBALS['antwoorden'][ $bron['nl']['raw'] ] = array( 200, $branding );
$GLOBALS['antwoorden'][ $bron['en']['raw'] ] = array( 200, $merkwaarden );
$GLOBALS['beheerder']                        = false;
$html                                        = sfp_page_config_merkwaarden_render(
    array(
        'taal'  => 'nl',
        'align' => 'full',
    )
);
check( 1 === substr_count( $html, '<h1' ) && 4 === substr_count( $html, '<h2' ), 'weergave: één H1, vier H2 (drie waarden en Samen)' );
check( 3 === substr_count( $html, 'class="sfp-mw__kaart"' ), 'weergave: drie kaarten' );
check( false !== strpos( $html, 'href="#sfp-mw-robuust"' ) && false !== strpos( $html, 'id="sfp-mw-robuust"' ), 'weergave: kaart wijst naar de sectie' );
check( 3 === substr_count( $html, 'class="sfp-mw__grens"' ) && false !== strpos( $html, '<strong>Grens:</strong>' ), 'weergave: grens in een vlak met label' );
check( false !== strpos( $html, 'Meer over robuust' ), 'weergave: tekstlink in de kaart' );
check( false !== strpos( $html, 'alignfull' ), 'weergave: volle breedte' );
check( false === strpos( $html, 'sfp-mw__slot' ) && false === strpos( $html, 'class="sfp-mw-melding"' ), 'zonder slot: bezoeker ziet niets' );
$GLOBALS['beheerder'] = true;
check( false !== strpos( sfp_page_config_merkwaarden_render( array( 'taal' => 'nl' ) ), 'Alleen voor beheerders: vul de slotzin' ), 'zonder slot: beheerder ziet een melding' );
$GLOBALS['beheerder']               = false;
$GLOBALS['options']['sfp_settings'] = array(
    'merkwaarden' => array(
        'slot' => array(
            'nl' => array(
                'zin'    => 'Klaar om te bouwen?',
                'knop'   => 'Bekijk de huisstijl',
                'pagina' => 77,
                'link'   => '',
            ),
            'en' => array(
                'zin'    => 'Want them at hand?',
                'knop'   => 'Read the brand values',
                'pagina' => 0,
                'link'   => 'https://example.com/en/',
            ),
        ),
    ),
);
$html = sfp_page_config_merkwaarden_render( array( 'taal' => 'nl' ) );
check( false !== strpos( $html, 'href="https://voorbeeld.test/pagina-77/"' ) && false !== strpos( $html, 'Klaar om te bouwen?' ), 'slot nl: pagina als bestemming' );
$GLOBALS['locale'] = 'en_GB';
$html              = sfp_page_config_merkwaarden_render( array() );
check( false !== strpos( $html, 'lang="en"' ) && false !== strpos( $html, 'Our brand values' ) && false !== strpos( $html, 'https://example.com/en/' ), 'zonder taal: de taal van de site, met het eigen slot' );
check( false !== strpos( $html, 'More about robust' ) && false !== strpos( $html, '<strong>Boundary:</strong>' ), 'vaste teksten in het Engels' );
$GLOBALS['locale'] = 'nl_NL';
$html              = sfp_page_config_merkwaarden_render( array( 'taal' => 'en' ) );
check( false !== strpos( $html, 'Our brand values' ) && false !== strpos( $html, 'More about robust' ), 'taal="en" op een Nederlandse site (SFP, SLB)' );
check( false === strpos( $html, '<script' ), 'weergave zonder script' );

// Code tussen haken, achter een guard.
$GLOBALS['codes'] = array();
sfp_page_config_merkwaarden_code();
check( isset( $GLOBALS['codes']['sfp_merkwaarden'] ), 'code [sfp_merkwaarden] geregistreerd' );
$GLOBALS['codes']['sfp_merkwaarden'] = 'oud';
sfp_page_config_merkwaarden_code();
check( 'oud' === $GLOBALS['codes']['sfp_merkwaarden'], 'bestaande code niet dubbel geregistreerd' );

/* ---------- 6. Geen merkhex en geen fontnaam ---------- */

$code = file_get_contents( dirname( __DIR__ ) . '/includes/merkwaarden.php' ) . file_get_contents( dirname( __DIR__ ) . '/assets/blokken/merkwaarden.css' );
check( ! preg_match( '/#[0-9a-fA-F]{3,8}\b/', str_replace( 'href="#', '', $code ) ), 'geen hexcode in de code van dit onderdeel' );
foreach ( array( 'Archivo', 'Rubik', 'Nunito', 'Roboto' ) as $font ) {
    check( false === stripos( $code, $font ), "geen fontnaam $font" );
}
// Teksten: elke tekst in drie talen met dezelfde invultekens.
foreach ( sfp_page_config_teksten_register() as $id => $regel ) {
    if ( 0 !== strpos( $id, 'merkwaarden_' ) ) {
        continue;
    }
    check( substr_count( $regel['nl'], '%s' ) === substr_count( $regel['en'], '%s' ) && substr_count( $regel['en'], '%s' ) === substr_count( $regel['pt'], '%s' ), "tekst $id: zelfde invultekens in drie talen" );
}

echo $fouten ? "$fouten van $checks controles faalden\n" : "OK: $checks checks\n";
exit( $fouten ? 1 : 0 );
