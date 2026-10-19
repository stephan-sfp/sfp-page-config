<?php
/**
 * Tests voor de tools (3.8.0) in een lokale WordPress:
 *   wp eval-file wp-content/plugins/sfp-page-config/tests/tool-wp.php [pad-naar-json]
 *
 * Maakt een tool met een beschrijving (standaard een klein voorbeeld, of de
 * JSON uit het pad), en controleert het metaveld, de weigering van een
 * foute beschrijving via REST, de weergave, de gegevens voor Google, de
 * controle, de mailroute, de duimpjes met de overname van oude tellingen,
 * het blok en de stand voor SFP Beheer. Ruimt alles op.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$fouten = 0;
$goed   = 0;
$check  = function ( $naam, $ok, $detail = '' ) use ( &$fouten, &$goed ) {
    if ( $ok ) {
        ++$goed;
        return;
    }
    ++$fouten;
    echo "FOUT: $naam" . ( '' !== $detail ? ' (' . $detail . ')' : '' ) . "\n";
};

$pad   = isset( $args[0] ) ? $args[0] : '';
$json  = '' !== $pad && is_readable( $pad ) ? file_get_contents( $pad ) : wp_json_encode(
    array(
        'soort'     => 'test',
        'dimensies' => array( 'a' => 'Alpha', 'b' => 'Beta' ),
        'vragen'    => array(
            array( 'id' => 'q1', 'type' => 'likert', 'dimensie' => 'a', 'tekst' => 'Ik ben a.' ),
            array( 'id' => 'q2', 'type' => 'likert', 'dimensie' => 'b', 'tekst' => 'Ik ben b.' ),
        ),
        'score'     => array( 'per_dimensie' => 'percentage' ),
        'uitkomst'  => array( 'regel' => 'hoogste' ),
        'uitslagen' => array( 'a' => array( 'titel' => 'Alpha', 'tekst' => 'Je bent alpha.' ), 'b' => array( 'titel' => 'Beta', 'tekst' => 'Je bent beta.' ) ),
        'mail'      => array( 'onderwerp' => 'Jouw uitslag: {uitslag.titel}', 'intro' => 'Hierbij.' ),
        'acties'    => array( 'formulier' => 0 ),
    )
);

$bewaard = get_option( 'sfp_settings', array() );
$s       = is_array( $bewaard ) ? $bewaard : array();

/* Met Tools uit: geen eigen berichttype en geen tool in de paginatypes (3.8.0-beta.3). */
$uit = $s;
$uit['onderdelen']['tools'] = '0';
update_option( 'sfp_settings', $uit );
if ( empty( $GLOBALS['sfp_page_config_tool_eigen_type'] ) && ! post_type_exists( 'tool' ) ) {
    sfp_page_config_tool_posttype();
    $check( 'tools uit: plugin registreert geen berichttype', ! post_type_exists( 'tool' ) );
}
$check( 'tools uit: tool niet in de paginatypes', ! in_array( 'tool', sfp_page_config_post_types(), true ) );

$s['artikel_aan']          = '1';
$s['onderdelen']['tools']  = '1';
$s['tool_feedback_mail']   = 'feedback@example.test';
update_option( 'sfp_settings', $s );
$oud_feedback = get_option( 'sfp_page_config_tool_feedback', null );
delete_option( 'sfp_page_config_tool_feedback' );
update_option( 'cvd_tool_feedback_counts', array( 'proef-tool' => array( 'omhoog' => 7, 'omlaag' => 2 ) ), false );
// Het berichttype komt bij init; zette deze test Tools pas net aan, dan nu.
if ( ! post_type_exists( 'tool' ) ) {
    sfp_page_config_tool_posttype();
    sfp_page_config_tool_meta();
}
$check( 'tools aan: tool in de paginatypes', in_array( 'tool', sfp_page_config_post_types(), true ) );

// Restanten van een eerdere, afgebroken run.
foreach ( get_posts( array( 'post_type' => 'tool', 'post_status' => 'any', 'posts_per_page' => 50, 's' => 'Proef tool', 'fields' => 'ids' ) ) as $rest ) {
    wp_delete_post( $rest, true );
}

$check( 'onderdeel tools aan', sfp_page_config_tool_aan() );
$check( 'vorm tool aan', sfp_page_config_tool_vorm_aan() );

$id = wp_insert_post(
    array(
        'post_type'    => 'tool',
        'post_status'  => 'publish',
        'post_title'   => 'Proef tool',
        'post_name'    => 'proef-tool',
        'post_author'  => 1,
        'post_excerpt' => 'Een zin onder de titel.',
        'post_content' => '<!-- wp:paragraph --><p>Uitleg.</p><!-- /wp:paragraph -->',
        'meta_input'   => array( 'sfp_tool' => wp_slash( $json ) ),
    )
);
$check( 'tool aangemaakt', $id > 0 );
$b = sfp_page_config_tool_beschrijving_vers( $id );
$check( 'beschrijving gelezen', is_array( $b ) && 'test' === $b['soort'] );
$c = sfp_page_config_tool_controleer( $b );
$check( 'beschrijving zonder fouten: ' . implode( ' ', $c['fouten'] ), empty( $c['fouten'] ) );

/* REST: een foute beschrijving wordt geweigerd. */
$verzoek = new WP_REST_Request( 'POST', '/wp/v2/tool/' . $id );
$verzoek->set_param( 'meta', array( 'sfp_tool' => '{"soort":"onbekend"}' ) );
$uit = sfp_page_config_tool_rest_controle( (object) array( 'ID' => $id ), $verzoek );
$check( 'REST weigert een foute beschrijving', is_wp_error( $uit ) && 'sfp_tool_ongeldig' === $uit->get_error_code() );
$verzoek->set_param( 'meta', array( 'sfp_tool' => $json ) );
$check( 'REST laat een goede beschrijving door', ! is_wp_error( sfp_page_config_tool_rest_controle( (object) array( 'ID' => $id ), $verzoek ) ) );
$verzoek->set_param( 'meta', array( 'sfp_tool' => '' ) );
$check( 'REST laat een lege beschrijving door', ! is_wp_error( sfp_page_config_tool_rest_controle( (object) array( 'ID' => $id ), $verzoek ) ) );

/* Metaveld met revisies. */
$meta_keys = get_registered_meta_keys( 'post', 'tool' );
$check( 'metaveld geregistreerd met revisies', isset( $meta_keys['sfp_tool'] ) && ! empty( $meta_keys['sfp_tool']['revisions_enabled'] ) );

/* Weergave. */
$html = sfp_page_config_tool_html( $id );
$check( 'html met data', false !== strpos( $html, 'class="sfp-tool__data"' ) && false !== strpos( $html, 'data-tool="' . $id . '"' ) );
$check( 'html zonder mailteksten voor de browser', false === strpos( $html, 'Hierbij.' ) );
$check( 'html met hoe rekent', false !== strpos( $html, 'sfp-tool-' . $id . '-hoe' ) );
$check( 'html met noscript', false !== strpos( $html, '<noscript>' ) );
$check( 'motor aangemeld', wp_script_is( 'sfp-tool-motor', 'enqueued' ) && wp_script_is( 'sfp-tool-test', 'enqueued' ) );
$check( 'shortcode', false !== strpos( do_shortcode( '[sfp_tool id="' . $id . '" weergave="compact"]' ), 'sfp-tool--compact' ) );
$check( 'blok', false !== strpos( sfp_page_config_render_tool_blok( array( 'tool' => $id ) ), 'sfp-tool__app' ) );
$check( 'blok zonder tool leeg voor bezoekers', '' === sfp_page_config_render_tool_blok( array( 'tool' => 0 ) ) );

/* Vorm en controle. */
$post = get_post( $id );
$check( 'pijlerregel', false !== strpos( sfp_page_config_tool_pijlerregel( $post ), 'sfp-art-pijler' ) );
$check( 'ondertitel', false !== strpos( sfp_page_config_tool_ondertitel( $post ), 'Een zin onder de titel.' ) );
$maker = sfp_page_config_tool_makerskaart( $post );
$check( 'makerskaart met Based on', false !== strpos( $maker, 'sfp-tool-' . $id . '-hoe' ) );
$meldingen = sfp_page_config_tool_controle( $post );
$codes     = array_map( function ( $m ) { return $m['code']; }, $meldingen );
$check( 'controle: geen pijler en geen formulier gemeld', in_array( 'tool-pijler', $codes, true ) && in_array( 'tool-formulier', $codes, true ) );
$check( 'controle: geen fout', ! in_array( 'tool-fout', $codes, true ) );
update_post_meta( $id, 'sfp_tool', '{"soort":"test"}' );
$codes = array_map( function ( $m ) { return $m['code']; }, sfp_page_config_tool_controle( get_post( $id ) ) );
$check( 'controle: fout bij lege beschrijving', in_array( 'tool-fout', $codes, true ) );
update_post_meta( $id, 'sfp_tool', wp_slash( $json ) );

/* Gegevens voor Google. */
$GLOBALS['wp_query']->queried_object    = $post;
$GLOBALS['wp_query']->queried_object_id = $id;
$GLOBALS['wp_query']->is_singular       = true;
$GLOBALS['wp_query']->is_single         = true;
$GLOBALS['wp_query']->posts             = array( $post );
$GLOBALS['wp_query']->post              = $post;
$GLOBALS['wp_query']->set( 'post_type', 'tool' );
ob_start();
sfp_page_config_tool_schema();
$schema = ob_get_clean();
$check( 'schema WebApplication gratis met isBasedOn', false !== strpos( $schema, '"WebApplication"' ) && false !== strpos( $schema, '"price":"0"' ) && false !== strpos( $schema, '-hoe' ) );

/* Mailroute. */
$gemaild = array();
add_filter(
    'pre_wp_mail',
    function ( $pre, $atts ) use ( &$gemaild ) {
        $gemaild[] = $atts;
        return true;
    },
    10,
    2
);
$antwoorden = array();
foreach ( sfp_page_config_tool_beschrijving_vers( $id )['vragen'] as $v ) {
    if ( 'likert' === $v['type'] ) {
        $antwoorden[ $v['id'] ] = 4;
    } elseif ( 'tekst' === $v['type'] ) {
        $antwoorden[ $v['id'] ] = 'mijn tekst';
    } else {
        $antwoorden[ $v['id'] ] = $v['opties'][0]['waarde'];
    }
}
$b_mail = sfp_page_config_tool_beschrijving_vers( $id );
if ( ! $b_mail['acties']['mail'] ) {
    $s2 = json_decode( $json, true );
    $s2['acties']['mail'] = true;
    update_post_meta( $id, 'sfp_tool', wp_slash( wp_json_encode( $s2, JSON_UNESCAPED_UNICODE ) ) );
}
$m = new WP_REST_Request( 'POST', '/sfp/v1/tool/mail' );
// Een link van buiten wordt genegeerd; de server bouwt de link zelf.
$antwoorden['Vrij'] = 'Bel 0900 voor geld';
$m->set_body_params( array( 'tool' => $id, 'email' => 'test@example.test', 'antwoorden' => $antwoorden, 'website' => '' ) );
$res = sfp_page_config_tool_rest_mail( $m );
$check( 'mail: 200', 200 === $res->get_status() );
$check( 'mail: één mail naar het adres', 1 === count( $gemaild ) && 'test@example.test' === $gemaild[0]['to'] );
$check( 'mail: tekst en link', ! empty( $gemaild[0]['message'] ) && false !== strpos( $gemaild[0]['message'], 'Proef tool' ) && false !== strpos( $gemaild[0]['message'], 'http' ) );
$check( 'mail: onderwerp gevuld zonder plaatshouder', '' !== trim( (string) $gemaild[0]['subject'] ) && false === strpos( (string) $gemaild[0]['subject'], '{' ) );
$check( 'mail: link is de deellink van de tool zelf', false !== strpos( $gemaild[0]['message'], add_query_arg( 't', '', get_permalink( $id ) ) ), $gemaild[0]['message'] );
$check( 'mail: geen tekst van buiten', false === strpos( $gemaild[0]['message'], '0900' ) );
unset( $antwoorden['Vrij'] );
$m->set_body_params( array( 'tool' => $id, 'email' => 'test@example.test', 'antwoorden' => array(), 'website' => '' ) );
$check( 'mail: onvolledig is 400', 400 === sfp_page_config_tool_rest_mail( $m )->get_status() );
$m->set_body_params( array( 'tool' => $id, 'email' => 'nee', 'antwoorden' => $antwoorden, 'website' => '' ) );
$check( 'mail: fout adres is 400', 400 === sfp_page_config_tool_rest_mail( $m )->get_status() );
$m->set_body_params( array( 'tool' => $id, 'email' => 'bot@example.test', 'antwoorden' => $antwoorden, 'website' => 'spam' ) );
$check( 'mail: honeypot doet alsof', 200 === sfp_page_config_tool_rest_mail( $m )->get_status() && 1 === count( $gemaild ) );
for ( $i = 0; $i < 6; $i++ ) {
    $m->set_body_params( array( 'tool' => $id, 'email' => 'limiet@example.test', 'antwoorden' => $antwoorden, 'website' => '' ) );
    $laatste = sfp_page_config_tool_rest_mail( $m )->get_status();
}
$check( 'mail: zesde per adres per dag is 429', 429 === $laatste );
$m->set_body_params( array( 'tool' => $id, 'email' => 'limiet+7@example.test', 'antwoorden' => $antwoorden, 'website' => '' ) );
$check( 'mail: +label telt als hetzelfde adres', 429 === sfp_page_config_tool_rest_mail( $m )->get_status() );

/* Duimpjes met overname. */
$d = new WP_REST_Request( 'POST', '/sfp/v1/tool/duim' );
$d->set_body_params( array( 'tool' => 'proef-tool', 'stem' => 'omhoog', 'website' => '' ) );
$check( 'duim: 200', 200 === sfp_page_config_tool_rest_duim( $d )->get_status() );
$tel = get_option( 'sfp_page_config_tool_feedback' );
$check( 'duim: oude telling overgenomen plus één (8)', isset( $tel['proef-tool']['omhoog'] ) && 8 === (int) $tel['proef-tool']['omhoog'] && 2 === (int) $tel['proef-tool']['omlaag'] );
$voor = count( $gemaild );
$d->set_body_params( array( 'tool' => 'proef-tool', 'stem' => 'omlaag', 'tekst' => 'Kan beter.', 'email' => 'iemand@example.test', 'website' => '' ) );
sfp_page_config_tool_rest_duim( $d );
$check( 'duim: tekst mailt naar het ingestelde adres', count( $gemaild ) === $voor + 1 && 'feedback@example.test' === $gemaild[ $voor ]['to'] );
$check( 'duim: tekst telt niet mee', 2 === (int) get_option( 'sfp_page_config_tool_feedback' )['proef-tool']['omlaag'] );
$d->set_body_params( array( 'tool' => 'proef-tool', 'stem' => 'zijwaarts', 'website' => '' ) );
$check( 'duim: foute stem is 400', 400 === sfp_page_config_tool_rest_duim( $d )->get_status() );
$d->set_body_params( array( 'tool' => 'verzonnen-tool', 'stem' => 'omhoog', 'website' => '' ) );
$check( 'duim: onbekende tool is 404', 404 === sfp_page_config_tool_rest_duim( $d )->get_status() && ! isset( get_option( 'sfp_page_config_tool_feedback' )['verzonnen-tool'] ) );
$d->set_body_params( array( 'tool' => 'proef-tool', 'stem' => 'omlaag', 'vorige' => 'omhoog', 'website' => '' ) );
sfp_page_config_tool_rest_duim( $d );
$tel = get_option( 'sfp_page_config_tool_feedback' );
$check( 'duim: wissel haalt de vorige stem eraf', 7 === (int) $tel['proef-tool']['omhoog'] && 3 === (int) $tel['proef-tool']['omlaag'], wp_json_encode( $tel['proef-tool'] ) );

/* Beheer. */
$stand = sfp_page_config_tool_beheer_stand();
$rij   = null;
foreach ( $stand['tools'] as $t ) {
    if ( $t['id'] === $id ) {
        $rij = $t;
    }
}
$check( 'beheer: tool in de lijst met tellingen', $rij && 7 === $rij['omhoog'] && 'test' === $rij['soort'] && $rij['motor'], $rij ? wp_json_encode( $rij ) : 'geen rij' );
$check( 'beheer: feedbackadres', 'feedback@example.test' === $stand['adres'] );

/* Artikelopmaak: de pagina van de tool. */
$check( 'tool in artikelposttypes', in_array( 'tool', sfp_page_config_artikel_posttypes(), true ) );
$pagina = sfp_page_config_artikel_render( get_post( $id ) );
$check( 'pagina: klasse tool, geen zijkolom', false !== strpos( $pagina, 'sfp-artikel--tool' ) && false !== strpos( $pagina, 'sfp-artikel--zonder-zij' ) );
$check( 'pagina: tool boven de tekst', strpos( $pagina, 'sfp-tool__app' ) < strpos( $pagina, 'sfp-art-inhoud' ) );
$check( 'pagina: makerskaart, geen kopkaart', false !== strpos( $pagina, 'sfp-tool-maker' ) && false === strpos( $pagina, 'sfp-art-kop ' ) );

/* Concept, wachtwoord en twee keer op een pagina. */
wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
wp_set_current_user( 0 );
$check( 'concept: geen tool voor bezoekers', '' === sfp_page_config_tool_html( $id ) );
wp_set_current_user( 1 );
$check( 'concept: wel voor wie mag bewerken', '' !== sfp_page_config_tool_html( $id ) );
wp_set_current_user( 0 );
wp_update_post( array( 'ID' => $id, 'post_status' => 'publish', 'post_password' => 'geheim' ) );
$check( 'wachtwoord: geen tool', '' === sfp_page_config_tool_html( $id ) );
$m->set_body_params( array( 'tool' => $id, 'email' => 'ander@example.test', 'antwoorden' => $antwoorden, 'website' => '' ) );
$check( 'wachtwoord: geen mail', 404 === sfp_page_config_tool_rest_mail( $m )->get_status() );
wp_update_post( array( 'ID' => $id, 'post_password' => '' ) );
$een  = sfp_page_config_tool_html( $id );
$twee = sfp_page_config_tool_html( $id );
preg_match( '/class="sfp-tool [^"]*" id="([^"]+)"/', $een, $id1 );
preg_match( '/class="sfp-tool [^"]*" id="([^"]+)"/', $twee, $id2 );
$check( 'twee keer: eigen id', ! empty( $id1[1] ) && ! empty( $id2[1] ) && $id1[1] !== $id2[1] );

/* De oude koppelcode van een site ([cvd_tool_auto]) toont de nieuwe tool of niets, nooit twee keer. */
add_shortcode( 'cvd_tool_auto', function () { return '<div class="cvd-tool-missing">oud</div>'; } );
$GLOBALS['wp_query']->queried_object = get_post( $id );
$GLOBALS['wp_query']->is_singular    = true;
$GLOBALS['wp_query']->is_single      = true;
$GLOBALS['wp_query']->set( 'post_type', 'tool' );
$GLOBALS['post'] = get_post( $id );
setup_postdata( $GLOBALS['post'] );
$check( 'oude koppelcode: al getoond, dus niets', '' === do_shortcode( '[cvd_tool_auto]' ) );
update_post_meta( $id, 'sfp_tool', '' );
$check( 'oude koppelcode: zonder beschrijving de oude tool', false !== strpos( do_shortcode( '[cvd_tool_auto]' ), 'cvd-tool-missing' ) );
update_post_meta( $id, 'sfp_tool', wp_slash( $json ) );
remove_shortcode( 'cvd_tool_auto' );

/* Een link in de beschrijving die geen webadres is, komt nergens op de pagina. */
$vies            = json_decode( $json, true );
$vies['bron']    = array( 'model' => 'Model', 'link' => '</script><script>alert(1)</script>' );
$vies['uitslagen']['a']['artikel'] = 'javascript:alert(1)';
update_post_meta( $id, 'sfp_tool', wp_slash( wp_json_encode( $vies ) ) );
$html = sfp_page_config_tool_html( $id );
ob_start();
sfp_page_config_tool_schema();
$schema = ob_get_clean();
$check( 'link: niet in html of schema', false === strpos( $html, 'alert(1)' ) && false === strpos( $schema, 'alert(1)' ) && false === stripos( $schema, '</script><script' ) );
update_post_meta( $id, 'sfp_tool', wp_slash( $json ) );

/* Opruimen. */
wp_delete_post( $id, true );
delete_option( 'cvd_tool_feedback_counts' );
delete_option( 'sfp_page_config_tool_feedback_log' );
if ( null === $oud_feedback ) {
    delete_option( 'sfp_page_config_tool_feedback' );
} else {
    update_option( 'sfp_page_config_tool_feedback', $oud_feedback, false );
}
update_option( 'sfp_settings', $bewaard );
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sfp_tool_rl_%' OR option_name LIKE '_transient_timeout_sfp_tool_rl_%'" );

echo $fouten ? "$fouten fouten, $goed goed\n" : "OK: $goed controles\n";
