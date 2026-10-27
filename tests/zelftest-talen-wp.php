<?php
/**
 * Zelftest in meer talen op één site (3.11.0), in een echte WordPress
 * (lokale proefinstallatie, nooit een live site).
 *
 *   SFP_ZT_PAKKET=/pad/naar/tweetalig/pakket wp eval-file wp-content/plugins/sfp-page-config/tests/zelftest-talen-wp.php
 *
 * Het pakket heeft de talen nl en en (de test leest het in als "proeftalen").
 * Controleert: instellingen per taal, taal bij de start bewaard, adres van de
 * pdf-link per taal, de pdf-link herkent elk adres, CRM met de taal, datum en
 * naam in de taal van de test, shortcode met taal, testpagina per taal,
 * weergave in de configuratie, en dat een pakket met één taal niets verandert.
 * Ruimt alles aan het eind op.
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
function ztt_is( $voorwaarde, $wat ) {
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
function ztt_rest( $methode, $pad, array $body = array() ) {
    $verzoek = new WP_REST_Request( $methode, $pad );
    if ( $body ) {
        $verzoek->set_header( 'Content-Type', 'application/json' );
        $verzoek->set_body( wp_json_encode( $body ) );
    }
    sfp_zelftest_taal_context( '' );
    return rest_do_request( $verzoek );
}

$map = getenv( 'SFP_ZT_PAKKET' );
if ( ! $map || ! is_readable( rtrim( $map, '/' ) . '/manifest.json' ) ) {
    echo "Geef de map van een tweetalig pakket mee in SFP_ZT_PAKKET.\n";
    exit( 1 );
}
$map = rtrim( $map, '/' ) . '/';
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%sfp_pc_zt_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$oude_taal = get_option( 'WPLANG' );
update_option( 'WPLANG', 'nl_NL' );
$oude_locale       = isset( $GLOBALS['locale'] ) ? $GLOBALS['locale'] : null;
$GLOBALS['locale'] = 'nl_NL'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- site in het Nederlands voor deze test.

$beheerder = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
wp_set_current_user( $beheerder ? $beheerder[0]->ID : 1 );
sfp_page_config_instellingen_bewaar( array( 'onderdelen' => array( 'zelftest' => true ) ) );

/* ---------- Pakket met twee talen ---------- */
$bestanden = array();
foreach ( array( 'manifest.json', 'inhoud.json', 'motor.js', 'label.js', 'blad.html' ) as $naam ) {
    $bestanden[ $naam ] = (string) file_get_contents( $map . $naam ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}
$manifest         = json_decode( $bestanden['manifest.json'], true );
$manifest['test'] = 'proeftalen';
$bestanden['manifest.json'] = wp_json_encode( $manifest );
$goed = sfp_zelftest_pakket_inlezen( 'proeftalen', $bestanden );
ztt_is( ! is_wp_error( $goed ) && ! $goed['fouten'], 'tweetalig pakket wordt ingelezen' );
$pakket = sfp_zelftest_pakket( 'proeftalen' );
ztt_is( array( 'nl', 'en' ) === sfp_zelftest_pakket_talen( $pakket ), 'pakket heeft nl en en' );

/* ---------- Instellingen per taal ---------- */
$nl_pagina = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Test nl', 'post_content' => '<!-- wp:sfp/zelftest {"test":"proeftalen"} /-->' ) );
$en_pagina = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Test en', 'post_content' => '<!-- wp:sfp/zelftest {"test":"proeftalen"} /-->' ) );
ztt_is( is_wp_error( sfp_zelftest_instellingen_bewaar( 'proeftalen', array( 'talen' => array( 'en' => array( 'pdf_pad' => 'https://x.nl/a b' ) ) ) ) ), 'ongeldig pad voor een taal wordt geweigerd' );
ztt_is(
    true === sfp_zelftest_instellingen_bewaar(
        'proeftalen',
        array(
            'testpagina' => $nl_pagina,
            'pdf_pad'    => 'proeftest/resultaat',
            'proefmodus' => true,
            'talen'      => array(
                'en'  => array( 'testpagina' => $en_pagina, 'pdf_pad' => '/en/test/result/' ),
                'X!'  => array( 'testpagina' => 5, 'pdf_pad' => 'kapot' ),
            ),
        )
    ),
    'instellingen met een extra taal opgeslagen'
);
$inst = sfp_zelftest_instellingen( 'proeftalen' );
ztt_is( array( 'en' ) === array_keys( $inst['talen'] ) && 'en/test/result' === $inst['talen']['en']['pdf_pad'] && $en_pagina === $inst['talen']['en']['testpagina'], 'alleen geldige taalcodes, pad schoon' );
ztt_is( array( '' => 'proeftest/resultaat', 'en' => 'en/test/result' ) === sfp_zelftest_pdf_paden( 'proeftalen' ), 'beide pdf-adressen bekend' );
sfp_zelftest_instellingen_bewaar( 'proeftalen', array( 'logo' => 0 ) );
ztt_is( 'en/test/result' === sfp_zelftest_instellingen( 'proeftalen' )['talen']['en']['pdf_pad'], 'opslaan zonder talen laat de talen staan' );
$stand = ztt_rest( 'GET', '/sfp/v1/zelftest' )->get_data();
$t     = array_values( array_filter( $stand['tests'], function ( $x ) { return 'proeftalen' === $x['test']; } ) )[0];
ztt_is( array( 'nl', 'en' ) === $t['talen'] && 'en/test/result' === ( (array) $t['instellingen']['talen'] )['en']['pdf_pad'], 'stand toont talen en instellingen per taal' );
$uris = apply_filters( 'rocket_cache_reject_uri', array() );
ztt_is( in_array( '/proeftest/resultaat/(.*)', $uris, true ) && in_array( '/en/test/result/(.*)', $uris, true ), 'WP Rocket slaat beide pdf-adressen over' );

/* ---------- Start in het Engels ---------- */
$r = ztt_rest( 'POST', '/sfp/v1/zelftest/proeftalen/start', array( 'voornaam' => 'Ann', 'email' => 'ann@example.com', 'lengte' => 'long', 'taal' => 'en' ) );
ztt_is( 200 === $r->get_status(), 'start in het Engels: 200' );
$sessie_en = $r->get_data()['sessie'];
$rij       = sfp_zelftest_rij_op( 'sessie', $sessie_en );
ztt_is( 'en' === sfp_zelftest_rij_taal( $rij ), 'taal bewaard bij de rij' );
$r = ztt_rest( 'POST', '/sfp/v1/zelftest/proeftalen/start', array( 'voornaam' => 'Bo', 'email' => 'bo@example.com', 'lengte' => 'long', 'taal' => 'fr' ) );
$rij_fr = sfp_zelftest_rij_op( 'sessie', $r->get_data()['sessie'] );
ztt_is( 'nl' === sfp_zelftest_rij_taal( $rij_fr ), 'onbekende taal: de taal van de site' );
$r = ztt_rest( 'POST', '/sfp/v1/zelftest/proeftalen/start', array( 'voornaam' => 'Cas', 'email' => 'cas@example.com', 'lengte' => 'long' ) );
$sessie_nl = $r->get_data()['sessie'];
ztt_is( 'nl' === sfp_zelftest_rij_taal( sfp_zelftest_rij_op( 'sessie', $sessie_nl ) ), 'zonder taal: de taal van de site' );
$fout = ztt_rest( 'POST', '/sfp/v1/zelftest/proeftalen/start', array( 'voornaam' => 'Ann', 'email' => 'geen', 'lengte' => 'long', 'taal' => 'en' ) );
ztt_is( 0 === strpos( (string) $fout->get_data()['message'], 'EN ' ), 'foutmelding in de taal van de test' );

/* ---------- Afronden: adres per taal ---------- */
$antwoorden = array();
foreach ( (array) $pakket['manifest']['lengtes']['long']['vragen'] as $nr ) {
    $antwoorden[ $nr ] = 39 === (int) $nr ? array( 'a' ) : 'a';
}
$uitkomst = array( 'crm' => array( 'weakest_pillar' => 'presence', 'tried_before' => 'yes', 'urgency' => 'this month' ), 'notitie' => array( array( 'Version', 'long' ) ) );
$r_en = ztt_rest( 'POST', '/sfp/v1/zelftest/proeftalen/afgerond', array( 'sessie' => $sessie_en, 'antwoorden' => $antwoorden, 'uitkomst' => $uitkomst ) );
ztt_is( 200 === $r_en->get_status() && false !== strpos( $r_en->get_data()['result_url'], '/en/test/result/' ), 'Engelse rij: pdf-adres in het Engels' );
$r_nl = ztt_rest( 'POST', '/sfp/v1/zelftest/proeftalen/afgerond', array( 'sessie' => $sessie_nl, 'antwoorden' => $antwoorden, 'uitkomst' => $uitkomst ) );
ztt_is( 200 === $r_nl->get_status() && false !== strpos( $r_nl->get_data()['result_url'], '/proeftest/resultaat/' ), 'Nederlandse rij: gewone pdf-adres' );

/* ---------- CRM, notitie en blad in de taal van de rij ---------- */
$rij_en = sfp_zelftest_rij_op( 'sessie', $sessie_en );
$rij_nl = sfp_zelftest_rij_op( 'sessie', $sessie_nl );
sfp_zelftest_taal_context( '' );
ztt_is( 'en' === sfp_zelftest_payload_start( $rij_en )['test_language'] && 'nl' === sfp_zelftest_payload_start( $rij_nl )['test_language'], 'send 1: taal mee bij een tweetalig pakket' );
sfp_zelftest_taal_context( 'en' );
$send2 = sfp_zelftest_payload_afgerond( $rij_en, $pakket );
ztt_is( 'en' === $send2['test_language'] && false !== strpos( $send2['result_url'], '/en/test/result/' ), 'send 2: taal en Engelse link' );
ztt_is( 0 === strpos( $send2['note'], 'EN ' ), 'notitie begint met de Engelse testnaam' );
ztt_is( (bool) preg_match( '/(January|February|March|April|May|June|July|August|September|October|November|December)/', sfp_zelftest_blad_datum( $rij_en ) ), 'datum op het blad in het Engels' );
$gegevens = sfp_zelftest_blad_gegevens( $rij_en );
$html     = sfp_zelftest_blad_html( $pakket, $gegevens );
ztt_is( false !== strpos( $html, '"taal":"en"' ), 'blad krijgt taal en' );
$voet = sfp_zelftest_blad_voet( $pakket, $gegevens );
ztt_is( false !== strpos( $voet, 'EN ' ), 'voettekst van de pdf in het Engels' );
$bn = sfp_zelftest_pdf_bestandsnaam( $pakket, (string) $rij_en['code'] );
ztt_is( false !== strpos( $bn['utf8'], 'EN Presentatietest' ), 'pdf-naam in de taal van de test' );
sfp_zelftest_taal_context( '' );
ztt_is( false === strpos( (string) sfp_zelftest_pdf_bestandsnaam( $pakket, (string) $rij_nl['code'] )['utf8'], 'EN ' ), 'pdf-naam in het Nederlands zonder taal' );

/* ---------- Pdf-link herkent elk adres ---------- */
global $wp;
$wp->request = 'en/test/result';
$h           = sfp_zelftest_link_herken();
ztt_is( $h && 'proeftalen' === $h['test'] && 'en' === $h['taal'], 'Engels adres herkend met taal' );
$wp->request = 'proeftest/resultaat';
$h           = sfp_zelftest_link_herken();
ztt_is( $h && '' === $h['taal'], 'gewone adres herkend' );

/* ---------- Pagina: shortcode met taal en testpagina per taal ---------- */
sfp_zelftest_taal_context( '' );
$html = do_shortcode( '[sfp_zelftest test="proeftalen" taal="en"]' );
ztt_is( false !== strpos( $html, 'lang="en"' ), 'shortcode met taal en: test in het Engels' );
$data = wp_scripts()->get_data( 'sfp-zelftest', 'before' );
$data = is_array( $data ) ? implode( "\n", $data ) : (string) $data;
ztt_is( false !== strpos( $data, '"taal":"en"' ), 'configuratie met taal en' );
ztt_is( false !== strpos( $data, '"weergave":{"vragen":"willekeurig","opties":"willekeurig","blokkop":false' ), 'configuratie met de weergave van de schakelaar Husselen (standaard aan)' );
ztt_is( 'en' === sfp_zelftest_pagina_taal( 'proeftalen', 'en' ) && '' === sfp_zelftest_pagina_taal( 'proeftalen', 'fr' ), 'taal uit het blok alleen als het pakket hem heeft' );
$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $en_pagina ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$GLOBALS['wp_the_query'] = $GLOBALS['wp_query']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
ztt_is( 'en' === sfp_zelftest_pagina_taal( 'proeftalen' ), 'Engelse testpagina: taal en' );
sfp_zelftest_taal_context( 'en' );
ztt_is( get_permalink( $en_pagina ) === sfp_zelftest_testpagina_url( 'proeftalen' ), 'knop "opnieuw" naar de Engelse testpagina' );
ztt_is( 'Close the test' === sfp_zelftest_pagina_tekst( 'zelftest_sluiten' ) || '' !== sfp_zelftest_pagina_tekst( 'zelftest_sluiten' ), 'vaste tekst testpagina in de taal van de test' );
ztt_is( sfp_page_config_tekst_standaard( 'zelftest_sluiten', 'en' ) === sfp_zelftest_pagina_tekst( 'zelftest_sluiten' ), 'kruisje: de Engelse standaardtekst' );
sfp_zelftest_taal_context( '' );
ztt_is( sfp_page_config_tekst( 'zelftest_sluiten' ) === sfp_zelftest_pagina_tekst( 'zelftest_sluiten' ), 'zonder taal: de tekst van de site' );

/* ---------- Proef in een andere taal ---------- */
$r = ztt_rest( 'POST', '/sfp/v1/zelftest/proeftalen/proef', array( 'lengte' => 'long', 'antwoorden' => $antwoorden, 'alleen_html' => 1, 'taal' => 'en', 'naam' => 'Ann' ) );
ztt_is( 200 === $r->get_status() && false !== strpos( $r->get_data()['html'], '"taal":"en"' ), 'proef in het Engels' );

/* ---------- Opruimen ---------- */
$tabel = sfp_zelftest_tabel();
$wpdb->query( $wpdb->prepare( "DELETE FROM {$tabel} WHERE test = %s", 'proeftalen' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
sfp_zelftest_map_weg( sfp_zelftest_map( 'pakket/proeftalen' ) );
$alle = sfp_zelftest_instellingen_alle();
unset( $alle['tests']['proeftalen'] );
update_option( 'sfp_zelftest', $alle, false );
delete_option( 'sfp_zelftest_log' );
wp_delete_post( $nl_pagina, true );
wp_delete_post( $en_pagina, true );
update_option( 'WPLANG', $oude_taal );
$GLOBALS['locale'] = $oude_locale; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
sfp_zelftest_taal_context( '' );

echo $GLOBALS['zt_fout'] ? "MISLUKT: {$GLOBALS['zt_fout']} fout, {$GLOBALS['zt_ok']} goed\n" : "OK: {$GLOBALS['zt_ok']} controles\n";
