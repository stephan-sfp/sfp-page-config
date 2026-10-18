<?php
/**
 * Zelftest (3.4.0) in een echte WordPress (lokale proefinstallatie, nooit een
 * live site).
 *
 *   SFP_ZT_PAKKET=/pad/naar/pakket wp eval-file wp-content/plugins/sfp-page-config/tests/zelftest-wp.php
 *
 * Het pakket is een map met manifest.json, inhoud.json, motor.js, label.js en
 * blad.html. De test leest het in als test "proeftest", maakt eigen rijen en
 * ruimt ze aan het eind op. Cloudflare en SureContact worden niet aangeroepen
 * (proefmodus; de pdf-stap wordt alleen op zijn wachtrij getoetst).
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

$GLOBALS['zt_ok']   = 0;
$GLOBALS['zt_fout'] = 0;

/**
 * Een controle.
 *
 * @param bool   $voorwaarde Moet waar zijn.
 * @param string $wat        Omschrijving.
 */
function zt_is( $voorwaarde, $wat ) {
    if ( $voorwaarde ) {
        ++$GLOBALS['zt_ok'];
    } else {
        ++$GLOBALS['zt_fout'];
        echo "FOUT: {$wat}\n";
    }
}

/**
 * Een REST-verzoek binnen dezelfde PHP.
 *
 * @param  string $methode Methode.
 * @param  string $pad     Pad onder /wp-json.
 * @param  array  $body    JSON.
 * @return WP_REST_Response
 */
function zt_rest( $methode, $pad, array $body = array() ) {
    $verzoek = new WP_REST_Request( $methode, $pad );
    if ( $body ) {
        $verzoek->set_header( 'Content-Type', 'application/json' );
        $verzoek->set_body( wp_json_encode( $body ) );
    }
    return rest_do_request( $verzoek );
}

/**
 * Leeg de tellers van de limiet, zodat tests elkaar niet blokkeren.
 */
function zt_limiet_leeg() {
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%sfp_pc_zt_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    wp_cache_flush();
}

$map = getenv( 'SFP_ZT_PAKKET' );
if ( ! $map || ! is_readable( rtrim( $map, '/' ) . '/manifest.json' ) ) {
    echo "Geef de map van een pakket mee in SFP_ZT_PAKKET.\n";
    exit( 1 );
}
$map = rtrim( $map, '/' ) . '/';

// Een beheerder voor de beheerroutes.
$beheerder = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
wp_set_current_user( $beheerder ? $beheerder[0]->ID : 1 );
sfp_page_config_instellingen_bewaar( array( 'onderdelen' => array( 'zelftest' => true ) ) );

/* ---------- Pakket ---------- */
$bestanden             = array();
foreach ( array( 'manifest.json', 'inhoud.json', 'motor.js', 'label.js', 'blad.html' ) as $naam ) {
    $bestanden[ $naam ] = (string) file_get_contents( $map . $naam ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}
$manifest         = json_decode( $bestanden['manifest.json'], true );
$manifest['test'] = 'proeftest';
$bestanden['manifest.json'] = wp_json_encode( $manifest );

// Weigeren: ontbrekend bestand, kapotte JSON, verkeerde test, onbekende lengtevraag.
$fout = sfp_zelftest_pakket_inlezen( 'proeftest', array_diff_key( $bestanden, array( 'motor.js' => 1 ) ) );
zt_is( is_wp_error( $fout ), 'pakket zonder motor.js wordt geweigerd' );
$kapot                  = $bestanden;
$kapot['inhoud.json']   = '{kapot';
zt_is( is_wp_error( sfp_zelftest_pakket_inlezen( 'proeftest', $kapot ) ), 'kapotte inhoud.json wordt geweigerd' );
zt_is( is_wp_error( sfp_zelftest_pakket_inlezen( 'andere', $bestanden ) ), 'manifest van een andere test wordt geweigerd' );
$m2                                 = $manifest;
$m2['lengtes']['short']['vragen'][] = 99;
$vreemd                             = $bestanden;
$vreemd['manifest.json']            = wp_json_encode( $m2 );
zt_is( is_wp_error( sfp_zelftest_pakket_inlezen( 'proeftest', $vreemd ) ), 'korte versie met een vraag die niet bestaat wordt geweigerd' );
zt_is( null === sfp_zelftest_pakket( 'proeftest' ), 'na een weigering is er geen pakket' );

$goed = sfp_zelftest_pakket_inlezen( 'proeftest', $bestanden );
zt_is( ! is_wp_error( $goed ), 'geldig pakket wordt ingelezen' );
$pakket = sfp_zelftest_pakket( 'proeftest' );
zt_is( is_array( $pakket ) && 'proeftest' === $pakket['test'], 'pakket is leesbaar na inlezen' );
zt_is( in_array( 'proeftest', sfp_zelftest_tests(), true ), 'test staat in de lijst' );

// Map is afgesloten.
$basis = sfp_zelftest_map();
zt_is( is_readable( $basis . '.htaccess' ) && false !== strpos( (string) file_get_contents( $basis . '.htaccess' ), 'denied' ), 'werkmap heeft een .htaccess die alles weigert' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
zt_is( (bool) preg_match( '#/sfp-zelftest-[a-f0-9]{24}/$#', $basis ), 'werkmap heeft een willekeurige naam' );

// Tweede inlezen: vorige versie bewaard en terug te zetten.
$m3                         = $manifest;
$m3['pakket_versie']        = 'proef-2';
$b3                         = $bestanden;
$b3['manifest.json']        = wp_json_encode( $m3 );
sfp_zelftest_pakket_inlezen( 'proeftest', $b3 );
zt_is( 'proef-2' === sfp_zelftest_pakket( 'proeftest' )['manifest']['pakket_versie'], 'nieuwe versie actief' );
sfp_zelftest_pakket_terug( 'proeftest' );
zt_is( $manifest['pakket_versie'] === sfp_zelftest_pakket( 'proeftest' )['manifest']['pakket_versie'], 'vorige versie teruggezet' );

/* ---------- Instellingen ---------- */
zt_is( is_wp_error( sfp_zelftest_instellingen_bewaar( 'proeftest', array( 'pdf_pad' => 'https://ergens.nl/x y' ) ) ), 'ongeldig pad wordt geweigerd' );
zt_is( is_wp_error( sfp_zelftest_instellingen_bewaar( 'proeftest', array( 'webhook_start' => 'http://onveilig.nl' ) ) ), 'webhook zonder https wordt geweigerd' );
zt_is( true === sfp_zelftest_instellingen_bewaar( 'proeftest', array( 'pdf_pad' => '/proeftest/resultaat/', 'webhook_start' => 'https://voorbeeld.nl/a', 'proefmodus' => true ) ), 'geldige instellingen opgeslagen' );
$inst = sfp_zelftest_instellingen( 'proeftest' );
zt_is( 'proeftest/resultaat' === $inst['pdf_pad'], 'pad zonder schuine strepen aan de randen' );
sfp_zelftest_instellingen_bewaar( 'proeftest', array( 'webhook_start' => '' ) );
zt_is( 'https://voorbeeld.nl/a' === sfp_zelftest_instellingen( 'proeftest' )['webhook_start'], 'leeg webhookveld houdt de bewaarde waarde' );
sfp_zelftest_instellingen_bewaar( 'proeftest', array( 'webhook_start' => '-' ) );
zt_is( '' === sfp_zelftest_instellingen( 'proeftest' )['webhook_start'], '- wist het webhookadres' );

$stand = zt_rest( 'GET', '/sfp/v1/zelftest' )->get_data();
zt_is( false === strpos( wp_json_encode( $stand ), 'voorbeeld.nl' ), 'stand toont geen webhookadres' );
zt_is( true === sfp_zelftest_cloudflare_bewaar( array( 'account' => str_repeat( 'a', 32 ), 'token' => 'geheim_token_1234567890abcdef' ) ), 'Cloudflare opgeslagen' );
$stand = zt_rest( 'GET', '/sfp/v1/zelftest' )->get_data();
zt_is( false === strpos( wp_json_encode( $stand ), 'geheim_token' ), 'stand toont het token niet' );
zt_is( is_wp_error( sfp_zelftest_cloudflare_bewaar( array( 'account' => 'kort' ) ) ), 'ongeldig account-ID wordt geweigerd' );

// Beheerroutes zonder rechten.
wp_set_current_user( 0 );
zt_is( 401 === zt_rest( 'GET', '/sfp/v1/zelftest' )->get_status(), 'uitgelogd: stand 401' );
zt_is( 401 === zt_rest( 'POST', '/sfp/v1/zelftest/wissen', array( 'email' => 'a@b.nl' ) )->get_status(), 'uitgelogd: wissen 401' );
zt_is( 401 === zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/pakket', array( 'bestanden' => $bestanden ) )->get_status(), 'uitgelogd: pakket 401' );

/* ---------- Start ---------- */
zt_limiet_leeg();
$r = zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/start', array( 'voornaam' => 'Proef', 'email' => 'geen-adres', 'lengte' => 'long' ) );
zt_is( 400 === $r->get_status(), 'start met ongeldig e-mailadres: 400' );
$r = zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/start', array( 'voornaam' => 'Proef', 'email' => 'proef@example.com', 'lengte' => 'middel' ) );
zt_is( 400 === $r->get_status(), 'start met onbekende lengte: 400' );
$r = zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/start', array( 'voornaam' => 'Proef', 'email' => 'proef@example.com', 'lengte' => 'long', 'website' => 'spam' ) );
zt_is( 400 === $r->get_status(), 'start met ingevuld lokveld: 400' );
$r = zt_rest( 'POST', '/sfp/v1/zelftest/onbekend/start', array( 'voornaam' => 'Proef', 'email' => 'proef@example.com', 'lengte' => 'long' ) );
zt_is( 404 === $r->get_status(), 'start van een onbekende test: 404' );

$r = zt_rest(
    'POST',
    '/sfp/v1/zelftest/proeftest/start',
    array( 'voornaam' => '<b>Proef</b>', 'email' => 'Proef@Example.com', 'lengte' => 'long', 'nieuwsbrief' => true, 'lead_source_url' => 'https://depresenteerschool.nl/presentatietraining/', 'utm_source' => 'nieuwsbrief', 'gclid' => 'abc', 'dienst' => 'training' )
);
zt_is( 200 === $r->get_status(), 'geldige start: 200' );
$sessie = (string) $r->get_data()['sessie'];
zt_is( (bool) preg_match( '/^[a-f0-9]{32}$/', $sessie ), 'start geeft een sessie' );
$rij = sfp_zelftest_rij_op( 'sessie', $sessie );
zt_is( 'proef@example.com' === $rij['email'] && 'Proef' === $rij['voornaam'], 'e-mail in kleine letters, voornaam zonder HTML' );
zt_is( 'wacht' === $rij['crm_start'] && empty( $rij['code'] ), 'send 1 in de wachtrij, nog geen code' );
zt_is( 'training' === sfp_zelftest_json( $rij, 'extra' )['dienst'], 'dienst bewaard' );

/* ---------- Afgerond ---------- */
$antw_tekst = '1b 2b 3b 4b 5b 6a 7a 8b 9b 10b 11b 12b 13b 14b 15b 16a 17a 18a 19b 20a 21b 22a 23a 24b 25a 26b 27b 28b 29c 30a 31b 32b 33a 34b 35a 36b 37c 38b 39a,h 40a';
$antw       = array();
foreach ( explode( ' ', $antw_tekst ) as $stuk ) {
    preg_match( '/^(\d+)(.+)$/', $stuk, $m );
    $antw[ (int) $m[1] ] = false !== strpos( $m[2], ',' ) ? explode( ',', $m[2] ) : $m[2];
}
$uitkomst = array( 'crm' => array( 'weakest_pillar' => 'connection', 'tried_before' => 'yes', 'urgency' => 'coming months' ), 'notitie' => array( array( 'Scores', '63 85 40' ) ), 'varianten' => array( 'sterk', 'heel' ) );

$fout_antw = $antw;
unset( $fout_antw[17] );
zt_is( 400 === zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/afgerond', array( 'sessie' => $sessie, 'antwoorden' => $fout_antw, 'uitkomst' => $uitkomst ) )->get_status(), 'ontbrekend antwoord: 400' );
$fout_antw      = $antw;
$fout_antw[7]   = 'd';
zt_is( 400 === zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/afgerond', array( 'sessie' => $sessie, 'antwoorden' => $fout_antw, 'uitkomst' => $uitkomst ) )->get_status(), 'letter buiten de opties: 400' );
$fout_antw      = $antw;
$fout_antw[39]  = 'a';
zt_is( 400 === zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/afgerond', array( 'sessie' => $sessie, 'antwoorden' => $fout_antw, 'uitkomst' => $uitkomst ) )->get_status(), 'meer-antwoordvraag als losse letter: 400' );
$fout_uit                           = $uitkomst;
$fout_uit['crm']['weakest_pillar']  = 'alles';
zt_is( 400 === zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/afgerond', array( 'sessie' => $sessie, 'antwoorden' => $antw, 'uitkomst' => $fout_uit ) )->get_status(), 'CRM-waarde buiten de lijst: 400' );
zt_is( 400 === zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/afgerond', array( 'sessie' => str_repeat( 'a', 32 ), 'antwoorden' => $antw, 'uitkomst' => $uitkomst ) )->get_status(), 'onbekende sessie: 400' );

$r = zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/afgerond', array( 'sessie' => $sessie, 'antwoorden' => $antw, 'uitkomst' => $uitkomst ) );
zt_is( 200 === $r->get_status(), 'geldig afgerond: 200' );
$code = (string) $r->get_data()['code'];
zt_is( (bool) preg_match( '/^' . $manifest['code_voorvoegsel'] . '-[0-9A-HJKMNP-TV-Z]{4}-[0-9A-HJKMNP-TV-Z]{4}-[0-9A-HJKMNP-TV-Z]{4}$/', $code ), 'code in het juiste formaat (Crockford, zonder I L O U)' );
zt_is( false !== strpos( (string) $r->get_data()['result_url'], 'proeftest/resultaat/?code=' . $code ), 'result_url wijst naar de pdf-link' );
$r2 = zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/afgerond', array( 'sessie' => $sessie, 'antwoorden' => $antw, 'uitkomst' => $uitkomst ) );
zt_is( $code === (string) $r2->get_data()['code'], 'dubbel afgerond: dezelfde code, geen nieuwe' );
$rij = sfp_zelftest_rij_op( 'code', $code );
zt_is( 'wacht' === $rij['crm_afgerond'] && 'wacht' === $rij['pdf_status'], 'send 2 en pdf in de wachtrij' );
zt_is( ! array_key_exists( 'deelcode', $rij ) && ! array_key_exists( 'deelbeelden', $rij ), 'tabel zonder kolommen van het delen' );

// Opnieuw doen geeft een nieuwe code.
zt_limiet_leeg();
$s2 = (string) zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/start', array( 'voornaam' => 'Proef', 'email' => 'proef@example.com', 'lengte' => 'long' ) )->get_data()['sessie'];
$c2 = (string) zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/afgerond', array( 'sessie' => $s2, 'antwoorden' => $antw, 'uitkomst' => $uitkomst ) )->get_data()['code'];
zt_is( '' !== $c2 && $c2 !== $code, 'opnieuw doen: een nieuwe code' );

// Korte versie: alleen de vragen van die lengte.
$kort_vragen = array_map( 'intval', $manifest['lengtes']['short']['vragen'] );
$kort        = array_intersect_key( $antw, array_flip( $kort_vragen ) );
$s3          = (string) zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/start', array( 'voornaam' => 'Kort', 'email' => 'kort@example.com', 'lengte' => 'short' ) )->get_data()['sessie'];
zt_is( 400 === zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/afgerond', array( 'sessie' => $s3, 'antwoorden' => $antw, 'uitkomst' => $uitkomst ) )->get_status(), 'korte versie met antwoorden van de lange: 400' );
zt_is( 200 === zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/afgerond', array( 'sessie' => $s3, 'antwoorden' => $kort, 'uitkomst' => $uitkomst ) )->get_status(), 'korte versie: 200' );

/* ---------- CRM in proefmodus ---------- */
delete_option( 'sfp_zelftest_log' );
global $wpdb;
$tabel = sfp_zelftest_tabel();
$wpdb->query( $wpdb->prepare( "UPDATE {$tabel} SET crm_volgende = %s WHERE id = %d", sfp_zelftest_nu( -5 ), (int) $rij['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
sfp_zelftest_crm_verwerk( (int) $rij['id'] );
$rij = sfp_zelftest_rij_op( 'id', (string) $rij['id'] );
zt_is( 'proef' === $rij['crm_start'] && 'proef' === $rij['crm_afgerond'], 'proefmodus: beide stappen naar het logboek' );
$log = get_option( 'sfp_zelftest_log', array() );
$send2 = isset( $log[0]['gegevens'] ) ? $log[0]['gegevens'] : array();
zt_is( isset( $send2['note'], $send2['result_url'], $send2['weakest_pillar'] ) && 'connection' === $send2['weakest_pillar'], 'send 2 bevat note, result_url en de CRM-velden' );
zt_is( array( 'email', 'first_name', 'result_url', 'weakest_pillar', 'tried_before', 'urgency', 'note' ) === array_keys( $send2 ), 'send 2 heeft precies de velden uit sectie 6.2' );
$send1 = isset( $log[1]['gegevens'] ) ? $log[1]['gegevens'] : array();
zt_is( array( 'email', 'first_name', 'test_length', 'lead_source_url', 'newsletter' ) === array_keys( $send1 ), 'send 1 heeft precies de velden uit sectie 6.2' );
zt_is( false !== strpos( (string) $send2['note'], '39. ' ) && false !== strpos( (string) $send2['note'], $code ), 'notitie met antwoorden en link' );

/* ---------- Opnieuw sturen ---------- */
$r = zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/opnieuw-sturen', array( 'sessie' => $sessie, 'email' => 'nieuw@example.com' ) );
zt_is( 200 === $r->get_status() && 'nieuw@example.com' === sfp_zelftest_rij_op( 'code', $code )['email'], 'opnieuw sturen past het adres aan' );
zt_is( 'wacht' === sfp_zelftest_rij_op( 'code', $code )['crm_afgerond'], 'opnieuw sturen zet send 2 opnieuw in de wachtrij' );
zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/opnieuw-sturen', array( 'sessie' => $sessie, 'email' => 'nieuw2@example.com' ) );
zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/opnieuw-sturen', array( 'sessie' => $sessie, 'email' => 'nieuw3@example.com' ) );
zt_is( 429 === zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/opnieuw-sturen', array( 'sessie' => $sessie, 'email' => 'nieuw4@example.com' ) )->get_status(), 'opnieuw sturen hoogstens drie keer' );

/* ---------- Limiet ---------- */
zt_limiet_leeg();
$statussen = array();
for ( $i = 0; $i < 9; $i++ ) {
    $statussen[] = zt_rest( 'POST', '/sfp/v1/zelftest/proeftest/start', array( 'voornaam' => 'L', 'email' => 'limiet@example.com', 'lengte' => 'long' ) )->get_status();
}
zt_is( 429 === end( $statussen ) && 200 === $statussen[0], 'limiet per e-mailadres: 429 na 8 starts in een uur' );
zt_limiet_leeg();

/* ---------- Blad ---------- */
$html = sfp_zelftest_blad_html( sfp_zelftest_pakket( 'proeftest' ), sfp_zelftest_blad_gegevens( sfp_zelftest_rij_op( 'code', $code ) ) );
zt_is( is_string( $html ) && false !== strpos( $html, 'window.sfpZelftestBlad=' ) && false !== strpos( $html, $code ), 'blad bevat de gegevens en de code' );
zt_is( is_string( $html ) && ! preg_match( '#fonts\.googleapis\.com#', $html ), 'blad zonder Google Fonts' );
$hex = isset( $manifest['blad']['kleuren'] ) ? array_keys( $manifest['blad']['kleuren'] ) : array();
foreach ( $hex as $h ) {
    $rol = $manifest['blad']['kleuren'][ $h ];
    if ( '' !== sfp_zelftest_rol_hex( $rol ) && strtoupper( sfp_zelftest_rol_hex( $rol ) ) !== strtoupper( $h ) ) {
        zt_is( false === stripos( $html, $h ), 'oude kleur ' . $h . ' (' . $rol . ') vervangen' );
    }
}

// Pdf: ligaturen uit; met een voettekst in het pakket de margevakken uit en een sjabloon voor Cloudflare.
$pk   = sfp_zelftest_pakket( 'proeftest' );
$geg  = sfp_zelftest_blad_gegevens( sfp_zelftest_rij_op( 'code', $code ) );
$voet = sfp_zelftest_blad_voet( $pk, $geg );
zt_is( is_string( $html ) && false !== strpos( $html, 'font-variant-ligatures:none!important' ) && false !== strpos( $html, '"locl" 0' ), 'pdf-blad zet ligaturen en locl uit' );
if ( ! empty( $manifest['blad']['voet'] ) ) {
    zt_is( false !== strpos( $voet, 'class="pageNumber"' ) && false !== strpos( $voet, 'class="totalPages"' ), 'voettekst met paginanummers' );
    zt_is( false !== strpos( $voet, esc_html( $geg['naam'] ) ), 'voettekst met de naam' );
    zt_is( false !== strpos( $html, '@bottom-left{content:none}' ), 'margevakken van het blad uit' );
    foreach ( $hex as $h ) {
        $rol = $manifest['blad']['kleuren'][ $h ];
        if ( '' !== sfp_zelftest_rol_hex( $rol ) && strtoupper( sfp_zelftest_rol_hex( $rol ) ) !== strtoupper( $h ) ) {
            zt_is( false === stripos( $voet, $h ), 'voettekst: oude kleur ' . $h . ' vervangen' );
        }
    }
    $zonder = $pk;
    unset( $zonder['manifest']['blad']['voet'] );
    zt_is( '' === sfp_zelftest_blad_voet( $zonder, $geg ), 'zonder voet in het pakket geen sjabloon' );
}
zt_is( ! function_exists( 'sfp_zelftest_maak_deelbeeld' ) && ! function_exists( 'sfp_zelftest_link_deel' ), 'geen deelfunctie meer' );
zt_is( ! array_key_exists( 'deel_pad', sfp_zelftest_instellingen( 'proeftest' ) ), 'geen instelling voor een deelpagina' );

/* ---------- IP-adres ---------- */
zt_is( sfp_zelftest_is_cloudflare( '162.158.1.1' ) && sfp_zelftest_is_cloudflare( '2606:4700::1' ), 'adressen van Cloudflare herkend' );
zt_is( ! sfp_zelftest_is_cloudflare( '8.8.8.8' ) && ! sfp_zelftest_is_cloudflare( 'rommel' ), 'andere adressen niet' );
$oud_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;
$_SERVER['REMOTE_ADDR']           = '8.8.8.8';
$_SERVER['HTTP_CF_CONNECTING_IP'] = '1.2.3.4';
zt_is( '8.8.8.8' === sfp_zelftest_ip(), 'CF-Connecting-IP genegeerd buiten Cloudflare' );
$_SERVER['REMOTE_ADDR'] = '162.158.1.1';
zt_is( '1.2.3.4' === sfp_zelftest_ip(), 'CF-Connecting-IP gebruikt achter Cloudflare' );
$_SERVER['REMOTE_ADDR'] = $oud_ip;
unset( $_SERVER['HTTP_CF_CONNECTING_IP'] );

/* ---------- Pdf-wachtrij ---------- */
$rij = sfp_zelftest_rij_op( 'code', $c2 );
$cf_oud = sfp_zelftest_cloudflare();
sfp_zelftest_cloudflare_bewaar( array( 'account' => '-', 'token' => '-' ) );
sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'wacht', 'pdf_volgende' => null ) );
sfp_zelftest_pdf_verwerk( (int) $rij['id'] );
zt_is( 'uitgesteld' === sfp_zelftest_rij_op( 'code', $c2 )['pdf_status'], 'zonder Cloudflare: pdf uitgesteld, geen poging' );
sfp_zelftest_cloudflare_bewaar( array( 'account' => str_repeat( 'b', 32 ), 'token' => 'geheim_token_1234567890abcdef' ) );
zt_is( 'wacht' === sfp_zelftest_rij_op( 'code', $c2 )['pdf_status'], 'Cloudflare ingesteld: uitgestelde pdf weer in de wachtrij' );
sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_status' => 'wacht', 'pdf_volgende' => null, 'afgerond_op' => sfp_zelftest_nu( -61 * DAY_IN_SECONDS ) ) );
sfp_zelftest_pdf_verwerk( (int) $rij['id'] );
zt_is( 'verlopen' === sfp_zelftest_rij_op( 'code', $c2 )['pdf_status'], 'na de geldigheid: geen pdf meer gemaakt' );
sfp_zelftest_rij_bij( (int) $rij['id'], array( 'afgerond_op' => sfp_zelftest_nu() ) );
if ( '' !== $cf_oud['account'] ) {
    sfp_zelftest_cloudflare_bewaar( array( 'account' => $cf_oud['account'], 'token' => $cf_oud['token'] ) );
}

/* ---------- Bewaartermijn ---------- */
$rij = sfp_zelftest_rij_op( 'code', $code );
$pdf_map = sfp_zelftest_map( 'pdf' );
file_put_contents( $pdf_map . str_repeat( 'b', 32 ) . '.pdf', '%PDF-proef' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_bestand' => str_repeat( 'b', 32 ) . '.pdf', 'pdf_status' => 'klaar', 'afgerond_op' => sfp_zelftest_nu( -61 * DAY_IN_SECONDS ) ) );
$uit = sfp_zelftest_dagelijks();
zt_is( $uit['pdf'] >= 1 && ! file_exists( $pdf_map . str_repeat( 'b', 32 ) . '.pdf' ), 'pdf na 60 dagen gewist' );
zt_is( 'gewist' === sfp_zelftest_rij_op( 'code', $code )['pdf_status'], 'rij blijft, pdf-status gewist' );
sfp_zelftest_rij_bij( (int) $rij['id'], array( 'gestart_op' => gmdate( 'Y-m-d H:i:s', time() - 2 * YEAR_IN_SECONDS - DAY_IN_SECONDS ), 'afgerond_op' => gmdate( 'Y-m-d H:i:s', time() - 2 * YEAR_IN_SECONDS - 3600 ) ) );
sfp_zelftest_dagelijks();
$anon = sfp_zelftest_rij_op( 'id', (string) $rij['id'] );
zt_is( '' === $anon['email'] && '' === $anon['voornaam'] && null === $anon['code'] && '' === $anon['lead_source_url'] && '' === $anon['utm_source'] && '' === $anon['gclid'] && '' === $anon['sessie'], 'na 2 jaar: geen persoonsgegevens' );
zt_is( '01 00:00:00' === substr( (string) $anon['gestart_op'], 8 ) && '1' === (string) $anon['geanonimiseerd'], 'na 2 jaar: tijden naar de maand, rij gemarkeerd' );
zt_is( '' !== (string) $anon['antwoorden'] && '' !== (string) $anon['uitkomst'], 'na 2 jaar: antwoorden en uitkomst blijven' );
zt_is( ! isset( sfp_zelftest_json( $anon, 'uitkomst' )['notitie'] ) && isset( sfp_zelftest_json( $anon, 'uitkomst' )['crm'] ), 'na 2 jaar: notitie weg, CRM-velden blijven' );

/* ---------- Wissen ---------- */
zt_is( sfp_zelftest_wis_email( 'proef@example.com' ) >= 1, 'wissen op e-mailadres' );
zt_is( null === sfp_zelftest_rij_op( 'sessie', $s2 ), 'gewiste rij is weg' );
$wissers = apply_filters( 'wp_privacy_personal_data_erasers', array() );
zt_is( isset( $wissers['sfp-zelftest'] ), 'aangesloten op Persoonlijke gegevens wissen' );
$uit = call_user_func( $wissers['sfp-zelftest']['callback'], 'kort@example.com' );
zt_is( ! empty( $uit['items_removed'] ), 'wisser van WordPress wist de rij' );

/* ---------- Opruimen ---------- */
$wpdb->query( $wpdb->prepare( "DELETE FROM {$tabel} WHERE test = %s", 'proeftest' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
sfp_zelftest_map_weg( sfp_zelftest_map( 'pakket/proeftest' ) );
$alle = sfp_zelftest_instellingen_alle();
unset( $alle['tests']['proeftest'] );
update_option( 'sfp_zelftest', $alle, false );
delete_option( 'sfp_zelftest_log' );

echo $GLOBALS['zt_fout'] ? "MISLUKT: {$GLOBALS['zt_fout']} fout, {$GLOBALS['zt_ok']} goed\n" : "OK: {$GLOBALS['zt_ok']} controles\n";
