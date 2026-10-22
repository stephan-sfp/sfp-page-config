<?php
/**
 * Tests voor het snippetbeheer via REST (3.3.0) in een echte WordPress.
 *
 * Draaien: wp eval-file wp-content/plugins/sfp-page-config/tests/snippets-wp.php
 * in een proefinstallatie (nooit een live site) met ASE Pro en de Code
 * Snippets Manager aan, een beheerder claude-api en een tweede beheerder.
 * Draait de site ook als webserver, geef dan het adres mee in
 * SFP_TEST_URL; dan wordt ook gemeten of de code echt draait.
 * Maakt eigen snippets aan en ruimt ze aan het eind op.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

$checks = 0;
$fouten = 0;
$same   = function ( $verwacht, $echt, $wat ) use ( &$checks, &$fouten ) {
    ++$checks;
    if ( $verwacht !== $echt ) {
        ++$fouten;
        echo 'FOUT: ' . $wat . "\n  verwacht: " . var_export( $verwacht, true ) . "\n  kreeg:    " . var_export( $echt, true ) . "\n";
    }
};
$waar = function ( $echt, $wat ) use ( $same ) {
    $same( true, (bool) $echt, $wat );
};

$claude = get_user_by( 'login', 'claude-api' );
$anders = null;
foreach ( get_users( array( 'role' => 'administrator' ) ) as $u ) {
    if ( 'claude-api' !== $u->user_login ) {
        $anders = $u;
        break;
    }
}
if ( ! $claude || ! $anders ) {
    echo "Nodig: een beheerder claude-api en een tweede beheerder.\n";
    exit( 1 );
}

$oud_inst = get_option( 'sfp_settings' );
$opruimen = array();

$rest = function ( $methode, $pad, $body = null, $gebruiker = null ) {
    wp_set_current_user( $gebruiker ? $gebruiker->ID : 0 );
    $verzoek = new WP_REST_Request( $methode, '/sfp/v1' . $pad );
    if ( null !== $body ) {
        $verzoek->set_header( 'Content-Type', 'application/json' );
        $verzoek->set_body( wp_json_encode( $body ) );
    }
    $antwoord = rest_do_request( $verzoek );
    return array( $antwoord->get_status(), $antwoord->get_data() );
};
$aantal = function () {
    return count( get_posts( array( 'post_type' => 'asenha_code_snippet', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) ) );
};
$boom = function ( $id ) {
    return array_key_exists( (int) $id, sfp_page_config_snippets_boom() );
};
$url  = getenv( 'SFP_TEST_URL' );
$kop  = function () use ( $url ) {
    $r = wp_remote_get( trailingslashit( $url ) . '?nc=' . wp_rand(), array( 'timeout' => 20 ) );
    return is_wp_error( $r ) ? 'fout' : (string) wp_remote_retrieve_header( $r, 'x-sfp-snippet-proef' );
};

/* ---------- Toegang ---------- */

sfp_page_config_instellingen_bewaar( array( 'onderdelen' => array( 'snippets' => '' ) ) );
$alle = sfp_page_config_instellingen();
unset( $alle['onderdelen']['snippets'] );
update_option( 'sfp_settings', $alle );
$same( false, sfp_page_config_onderdeel_aan( 'snippets' ), 'Onderdeel staat standaard uit' );
list( $code, $data ) = $rest( 'GET', '/snippets', null, $claude );
$same( 403, $code, 'Onderdeel uit: 403' );
$same( 'sfp_snippets_uit', $data['code'], 'Onderdeel uit: eigen foutcode' );

sfp_page_config_instellingen_bewaar( array( 'onderdelen' => array( 'snippets' => true ) ) );
list( $code ) = $rest( 'GET', '/snippets', null, $anders );
$same( 403, $code, 'Andere beheerder: 403' );
list( $code ) = $rest( 'GET', '/snippets', null, null );
$same( 401, $code, 'Uitgelogd: 401' );
list( $code, $lijst ) = $rest( 'GET', '/snippets', null, $claude );
$same( 200, $code, 'claude-api: 200' );
$same( true, sfp_page_config_snippets_ase(), 'ASE heeft alles wat nodig is' );
$waar( isset( $lijst['snippets'] ) && is_array( $lijst['snippets'] ), 'Lijst met snippets' );

/* ---------- Controle voor opslaan ---------- */

$voor = $aantal();
list( $code, $data ) = $rest( 'POST', '/snippets', array( 'titel' => 'Proef fout', 'code' => 'echo "x"' ), $claude );
$same( 400, $code, 'Syntaxfout: 400' );
$same( 'sfp_snippets_syntax', $data['code'], 'Syntaxfout: eigen foutcode' );
$same( $voor, $aantal(), 'Syntaxfout: geen snippet aangemaakt' );

function sfp_snippet_proef_bestaat_al() {}
list( $code, $data ) = $rest( 'POST', '/snippets', array( 'titel' => 'Proef dubbel', 'code' => 'function sfp_snippet_proef_bestaat_al() {}' ), $claude );
$same( 400, $code, 'Bestaande functie: 400' );
$same( 'sfp_snippets_controle', $data['code'], 'Bestaande functie: controle van ASE' );
$same( true, sfp_page_config_snippets_controleer( "<?php\nfunction sfp_snippet_proef_bestaat_al() { return 2; }", "<?php\nfunction sfp_snippet_proef_bestaat_al() {}" ), 'Bijwerken: eigen functie geldt niet als dubbel' );
$waar( is_wp_error( sfp_page_config_snippets_controleer( "<?php\nfunction sfp_snippet_proef_bestaat_al() {}", "<?php\nfunction iets_anders() {}" ) ), 'Bijwerken: andermans functie wel' );
$same( true, sfp_page_config_snippets_controleer( "<?php\nif ( ! function_exists( 'sfp_snippet_proef_bestaat_al' ) ) { function sfp_snippet_proef_bestaat_al() {} }" ), 'function_exists-guard mag' );

list( $code, $data ) = $rest( 'POST', '/snippets', array( 'titel' => '', 'code' => 'x();' ), $claude );
$same( 400, $code, 'Lege titel: 400' );
list( $code, $data ) = $rest( 'POST', '/snippets', array( 'titel' => 'Zonder code' ), $claude );
$same( 400, $code, 'Nieuw zonder code: 400' );
list( $code, $data ) = $rest( 'POST', '/snippets', array( 'titel' => 'Proef', 'code' => 'x();', 'waar' => 'overal' ), $claude );
$same( 400, $code, 'Onbekende waarde voor waar: 400' );

/* ---------- Aanmaken ---------- */

$v1 = "add_action( 'send_headers', function () {\n\theader( 'X-Sfp-Snippet-Proef: v1' );\n} );\n// < > && \"quotes\" en <script>niet weg</script>\n";
// Zoals voor een gebruiker zonder unfiltered_html: dan staan de kses-filters aan.
$geen_html = function ( $caps ) {
    $caps['unfiltered_html'] = false;
    return $caps;
};
add_filter( 'user_has_cap', $geen_html );
wp_set_current_user( 0 ); // Anders slaat WordPress kses_init over bij dezelfde gebruiker.
$kses_aan = null;
$meet_kses = function ( $data ) use ( &$kses_aan ) {
    $kses_aan = has_filter( 'content_save_pre', 'wp_filter_post_kses' );
    return $data;
};
add_filter( 'rest_pre_dispatch', $meet_kses );
list( $code, $s ) = $rest( 'POST', '/snippets', array( 'titel' => 'Proef kop', 'code' => $v1, 'beschrijving' => 'Test 3.3.0' ), $claude );
remove_filter( 'rest_pre_dispatch', $meet_kses );
$same( 10, $kses_aan, 'kses stond aan tijdens het verzoek' );
$same( 10, has_filter( 'content_save_pre', 'wp_filter_post_kses' ), 'kses staat na opslaan weer aan' );
remove_filter( 'user_has_cap', $geen_html );
wp_set_current_user( $claude->ID );
$same( 201, $code, 'Aanmaken: 201' );
$id         = isset( $s['id'] ) ? (int) $s['id'] : 0;
$opruimen[] = $id;
$post       = get_post( $id );
$same( 'asenha_code_snippet', $post ? $post->post_type : '', 'Posttype van ASE' );
$same( "<?php\n" . $v1, $post ? $post->post_content : '', 'Code met <?php ervoor, niet door kses gewijzigd' );
$same( 'publish', $s['status'], 'Nieuw: gepubliceerd' );
$same( false, $s['actief'], 'Nieuw: staat uit' );
$same( 'no', get_post_meta( $id, '_active', true ), 'Nieuw: _active no' );
$opties = get_post_meta( $id, 'options', true );
$same( 'php', $opties['language'], 'Opties: taal php' );
$same( 'plugins_loaded', $opties['execution_location'], 'Opties: standaardhook' );
$same( 'everywhere', $opties['execution_location_details'], 'Opties: overal' );
$same( 'sitewide', $opties['side'], 'Opties: sitewide' );
$same( 'Test 3.3.0', get_post_meta( $id, 'code_snippet_description', true ), 'Beschrijving bewaard' );
$same( true, $s['bestand']['bestaat'], 'Bestand geschreven' );
$same( true, $s['bestand']['gelijk'], 'Bestand gelijk aan database' );
$same( "<?php\n" . $v1, (string) file_get_contents( sfp_page_config_snippets_bestand( $id ) ), 'Bestand bevat de code' );
$same( false, $boom( $id ), 'Uit: niet in de lijst van ASE' );
$same( false, $s['draait'], 'Uit: draait niet' );
$waar( '' !== (string) get_post_meta( $id, '_csm_php_validated_file_hash', true ), 'Controle van ASE bewaard (md5)' );
$same( md5_file( sfp_page_config_snippets_bestand( $id ) ), get_post_meta( $id, '_csm_php_validated_file_hash', true ), 'md5 klopt' );
if ( $url ) {
    $same( '', $kop(), 'Voorkant: uit draait niet' );
}

/* ---------- Aan en uit ---------- */

list( $code, $s ) = $rest( 'POST', '/snippets/' . $id . '/actief', array( 'aan' => true ), $claude );
$same( 200, $code, 'Aanzetten: 200' );
$same( true, $s['actief'], 'Aanzetten: actief' );
$same( true, $boom( $id ), 'Aan: in de lijst van ASE' );
$same( true, $s['draait'], 'Aan: draait' );
if ( $url ) {
    $same( 'v1', $kop(), 'Voorkant: de code draait' );
}
list( $code, $data ) = $rest( 'POST', '/snippets/' . $id . '/actief', array(), $claude );
$same( 400, $code, 'Aan/uit zonder waarde: 400' );

/* ---------- Bijwerken ---------- */

$v2 = str_replace( 'v1', 'v2', $v1 );
list( $code, $s ) = $rest( 'POST', '/snippets/' . $id, array( 'code' => "<?php\n" . $v2, 'hook' => 'init', 'prioriteit' => 20 ), $claude );
$same( 200, $code, 'Bijwerken: 200' );
$same( "<?php\n" . $v2, $s['code'], 'Bijwerken: nieuwe code, geen dubbele <?php' );
$same( 'init', $s['hook'], 'Bijwerken: hook' );
$same( 20, $s['prioriteit'], 'Bijwerken: prioriteit' );
$same( 'Proef kop', $s['titel'], 'Bijwerken: titel blijft' );
$same( true, $s['actief'], 'Bijwerken: blijft aan' );
$same( true, $s['bestand']['gelijk'], 'Bijwerken: bestand gelijk' );
$tak = sfp_page_config_snippets_boom();
$same( 'init', isset( $tak[ $id ]['execution_location'] ) ? $tak[ $id ]['execution_location'] : '', 'Bijwerken: lijst van ASE kent de nieuwe hook' );
if ( $url ) {
    $same( 'v2', $kop(), 'Voorkant: de nieuwe code draait' );
}
list( $code, $data ) = $rest( 'POST', '/snippets/' . $id, array( 'code' => 'echo "kapot"' ), $claude );
$same( 400, $code, 'Bijwerken met fout: 400' );
$same( "<?php\n" . $v2, get_post( $id )->post_content, 'Bijwerken met fout: oude code blijft' );

/* ---------- Concept ---------- */

list( $code, $s ) = $rest( 'POST', '/snippets/' . $id, array( 'status' => 'draft' ), $claude );
$same( 'draft', $s['status'], 'Concept: status' );
$same( true, $s['actief'], 'Concept: schakelaar blijft aan' );
$same( false, $boom( $id ), 'Concept: niet in de lijst van ASE' );
$same( false, $s['draait'], 'Concept: draait niet' );
if ( $url ) {
    $same( '', $kop(), 'Voorkant: concept draait niet' );
}
$rest( 'POST', '/snippets/' . $id, array( 'status' => 'publish' ), $claude );
list( $code, $s ) = $rest( 'POST', '/snippets/' . $id . '/actief', array( 'aan' => false ), $claude );
$same( false, $s['actief'], 'Uitzetten: uit' );
$same( false, $boom( $id ), 'Uitzetten: niet in de lijst van ASE' );
if ( $url ) {
    $same( '', $kop(), 'Voorkant: uitgezet draait niet' );
}

/* ---------- Lezen ---------- */

list( $code, $lijst ) = $rest( 'GET', '/snippets', null, $claude );
$rij = null;
foreach ( $lijst['snippets'] as $r ) {
    if ( $r['id'] === $id ) {
        $rij = $r;
    }
}
$waar( $rij, 'Lijst bevat het snippet' );
$same( false, $rij && array_key_exists( 'code', $rij ), 'Lijst zonder code' );
list( $code, $een ) = $rest( 'GET', '/snippets/' . $id, null, $claude );
$same( 200, $code, 'Eén snippet: 200' );
$same( "<?php\n" . $v2, $een['code'], 'Eén snippet: met code' );
list( $code ) = $rest( 'GET', '/snippets/999999', null, $claude );
$same( 404, $code, 'Onbekend snippet: 404' );
$pagina = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Geen snippet' ) );
list( $code ) = $rest( 'GET', '/snippets/' . $pagina, null, $claude );
$same( 404, $code, 'Andere post: 404' );
wp_delete_post( $pagina, true );

/* ---------- Andere taal ---------- */

$css = wp_insert_post( array( 'post_type' => 'asenha_code_snippet', 'post_status' => 'publish', 'post_title' => 'Proef css', 'post_content' => 'body{}' ) );
$opruimen[] = $css;
update_post_meta( $css, 'options', array( 'language' => 'css' ) );
list( $code, $data ) = $rest( 'POST', '/snippets/' . $css, array( 'code' => 'x();' ), $claude );
$same( 409, $code, 'CSS-snippet bijwerken: 409' );
$same( 'body{}', get_post( $css )->post_content, 'CSS-snippet blijft ongewijzigd' );
list( $code, $data ) = $rest( 'GET', '/snippets/' . $css, null, $claude );
$same( 'css', $data['taal'], 'CSS-snippet is wel te lezen' );

/* ---------- Prullenbak (3.8.1) ---------- */

$weg_id = wp_insert_post( array( 'post_type' => 'asenha_code_snippet', 'post_status' => 'publish', 'post_title' => 'Proef prullenbak', 'post_content' => "<?php\n// leeg" ) );
$opruimen[] = $weg_id;
update_post_meta( $weg_id, 'options', array( 'language' => 'php' ) );
update_post_meta( $weg_id, '_active', 'yes' );
list( $code ) = $rest( 'POST', '/snippets/' . $weg_id . '/prullenbak', null, $claude );
$same( 409, $code, 'Prullenbak: snippet dat aan staat geweigerd' );
$same( 'publish', get_post( $weg_id )->post_status, 'Prullenbak: snippet dat aan staat blijft staan' );
list( $code ) = $rest( 'POST', '/snippets/' . $weg_id . '/prullenbak', null, $anders );
$same( 403, $code, 'Prullenbak: andere beheerder geweigerd' );
update_post_meta( $weg_id, '_active', 'no' );
list( $code, $data ) = $rest( 'POST', '/snippets/' . $weg_id . '/prullenbak', null, $claude );
$same( 200, $code, 'Prullenbak: snippet dat uit staat, 200' );
$same( 'trash', get_post( $weg_id )->post_status, 'Prullenbak: in de prullenbak, niet weg' );
list( $code ) = $rest( 'GET', '/snippets/' . $weg_id, null, $claude );
$same( 404, $code, 'Prullenbak: daarna niet meer te lezen' );
list( $code ) = $rest( 'POST', '/snippets/' . $pagina . '/prullenbak', null, $claude );
$same( 404, $code, 'Prullenbak: andere post 404' );

/* ---------- Opruimen ---------- */

wp_set_current_user( $claude->ID );
foreach ( $opruimen as $weg ) {
    foreach ( array( 'php', 'css' ) as $taal ) {
        $bestand = sfp_page_config_snippets_bestand( $weg, $taal );
        if ( is_file( $bestand ) ) {
            unlink( $bestand );
        }
    }
    wp_delete_post( $weg, true );
}
Code_Snippets_Manager()->rebuild_snippets_tree();
update_option( 'sfp_settings', $oud_inst );

echo ( $fouten ? "MISLUKT: $fouten van $checks\n" : "OK: $checks checks\n" );
