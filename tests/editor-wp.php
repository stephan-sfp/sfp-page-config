<?php
/**
 * Tests voor 3.1.0 in een echte WordPress.
 *
 * Draaien: wp eval-file wp-content/plugins/sfp-page-config/tests/editor-wp.php
 * in een proefinstallatie met SFP Page Config aan. Maakt eigen berichten en
 * een eigen gebruiker aan en ruimt ze aan het eind op.
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

$opruimen   = array();
$admin      = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
wp_set_current_user( $admin->ID );
$oud_inst   = get_option( 'sfp_settings' );

/* ---------- Knop onderaan: welk anker eerst ---------- */

$training = sfp_page_config_knop_voor_pagina( 0, 'training' );
$coaching = sfp_page_config_knop_voor_pagina( 0, 'coaching' );
$same( array( 'inschrijven', 'aanvragen' ), array_slice( $training['ankers'], -2 ), 'Training: eerst inschrijven' );
$same( array( 'aanvragen', 'inschrijven' ), array_slice( $coaching['ankers'], -2 ), 'Coaching: eerst aanvragen' );

/* ---------- Bestemming: alleen een hekje is het formulier ---------- */

$same( '#', sfp_page_config_sanitize_cta_href( '#' ), 'Hekje blijft een hekje' );
$same( '#inschrijven', sfp_page_config_sanitize_cta_href( ' #inschrijven ' ), 'Anker blijft' );
$pagina = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Proef knop', 'post_content' => '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->' ) );
$opruimen[] = $pagina;
update_post_meta( $pagina, 'sfp_page_type', 'coaching' );
update_post_meta( $pagina, 'sfp_cta_href', '#' );
$knop = sfp_page_config_knop_voor_pagina( $pagina, 'coaching' );
$same( 'form', $knop['doel'], 'Hekje: de knop gaat naar het formulier' );
$same( false, in_array( '', $knop['ankers'], true ), 'Hekje: geen leeg anker' );

/* ---------- Licht laden: een lege lijst blijft leeg ---------- */

sfp_page_config_instellingen_bewaar( array( 'ajax_lean_skip' => array() ) );
$alle = sfp_page_config_instellingen();
$same( array(), $alle['ajax_lean_skip'], 'Lege lijst bij licht laden blijft leeg' );

/* ---------- Affiliatepatronen ---------- */

$same( 1, preg_match( sfp_page_config_affiliate_regex( 'managementboek.nl*affiliate' ), 'https://www.managementboek.nl/boek/123?affiliate=9' ), 'Sterretje: een willekeurig stuk' );
$same( 0, preg_match( sfp_page_config_affiliate_regex( 'managementboek.nl?affiliate=' ), 'https://www.managementboek.nl/boek/123?affiliate=9' ), 'Vraagteken telt letterlijk' );
$artikel = wp_insert_post( array( 'post_status' => 'publish', 'post_title' => 'Proef affiliate', 'post_content' => '<!-- wp:paragraph --><p><a href="https://www.managementboek.nl/boek/1?affiliate=2">boek</a></p><!-- /wp:paragraph -->' ) );
$opruimen[] = $artikel;
sfp_page_config_instellingen_bewaar( array( 'affiliate_patronen' => array( 'managementboek.nl*affiliate', 'managementboek.nl?affiliate=' ) ) );
$treffers = sfp_page_config_affiliate_treffers();
$waar( $treffers['managementboek.nl*affiliate'] >= 1, 'Patroon met sterretje vindt het artikel' );
$same( 0, $treffers['managementboek.nl?affiliate='], 'Patroon met vraagteken vindt niets' );
$stand = sfp_page_config_beheer_stand();
$waar( isset( $stand['artikel']['treffers'] ), 'Beheer kent de treffers' );

/* ---------- Gegevens per pagina via REST ---------- */

$verzoek = new WP_REST_Request( 'POST', '/wp/v2/pages/' . $pagina );
$verzoek->set_body_params(
    array(
        'meta' => array(
            'training_naam'    => ' Presenteren <b>met</b> impact ',
            'sfp_longread'     => '1',
            'sfp_whatsapp'     => 'ja',
            'sfp_hero_focal_x' => 140,
            'sfp_cta_pagina'   => 12,
            'sfp_page_type'    => 'training',
        ),
    )
);
$antwoord = rest_do_request( $verzoek );
$same( 200, $antwoord->get_status(), 'Meta via REST bewaren' );
$same( 'Presenteren met impact', get_post_meta( $pagina, 'training_naam', true ), 'Naam van de training opgeschoond' );
$same( '1', get_post_meta( $pagina, 'sfp_longread', true ), 'Inhoudsopgave aan' );
$same( '', get_post_meta( $pagina, 'sfp_whatsapp', true ), 'Onbekende waarde is uit' );
$same( 100, (int) get_post_meta( $pagina, 'sfp_hero_focal_x', true ), 'Uitsnede hoogstens 100' );
$same( 'training', get_post_meta( $pagina, 'sfp_page_type', true ), 'Soort pagina via REST' );

$verzoek = new WP_REST_Request( 'POST', '/wp/v2/pages/' . $pagina );
$verzoek->set_body_params( array( 'meta' => array( 'sfp_cta_href' => '', 'sfp_hero_focal_y' => 50, 'sfp_cta_pagina' => 0, 'sfp_longread' => '' ) ) );
rest_do_request( $verzoek );
$same( false, metadata_exists( 'post', $pagina, 'sfp_cta_href' ), 'Lege bestemming: geen rij' );
$same( false, metadata_exists( 'post', $pagina, 'sfp_hero_focal_y' ), 'Uitsnede in het midden: geen rij' );
$same( false, metadata_exists( 'post', $pagina, 'sfp_cta_pagina' ), 'Geen pagina gekozen: geen rij' );
$same( false, metadata_exists( 'post', $pagina, 'sfp_longread' ), 'Inhoudsopgave uit: rij weg' );
$same( 50, (int) get_post_meta( $pagina, 'sfp_hero_focal_y', true ), 'Uitsnede zonder rij is het midden' );

$verzoek = new WP_REST_Request( 'POST', '/wp/v2/pages/' . $pagina );
$verzoek->set_body_params( array( 'meta' => array( 'sfp_page_type' => 'onzin' ) ) );
rest_do_request( $verzoek );
$same( '', (string) get_post_meta( $pagina, 'sfp_page_type', true ), 'Onbekende soort pagina wordt leeg' );

$gast = wp_insert_user( array( 'user_login' => 'sfp-proef-' . wp_rand(), 'user_pass' => wp_generate_password(), 'role' => 'contributor' ) );
wp_set_current_user( $gast );
$verzoek = new WP_REST_Request( 'POST', '/wp/v2/pages/' . $pagina );
$verzoek->set_body_params( array( 'meta' => array( 'training_naam' => 'Niet van mij' ) ) );
$antwoord = rest_do_request( $verzoek );
$waar( $antwoord->get_status() >= 400, 'Zonder rechten geen wijziging' );
$same( 'Presenteren met impact', get_post_meta( $pagina, 'training_naam', true ), 'Naam onveranderd' );
$antwoord = rest_do_request( new WP_REST_Request( 'GET', '/sfp/v1/editor/' . $pagina ) );
$waar( $antwoord->get_status() >= 400, 'Stand van de editor alleen voor wie mag bewerken' );
wp_set_current_user( $admin->ID );

/* ---------- Stand voor het paneel ---------- */

update_post_meta( $pagina, 'sfp_page_type', 'training' );
update_post_meta( $pagina, 'sfp_cursusdata', wp_json_encode( array( array( 'data' => array( '2020-01-06', '2020-01-13' ), 'start' => '09:30', 'eind' => '16:30' ) ) ) );
$antwoord = rest_do_request( new WP_REST_Request( 'GET', '/sfp/v1/editor/' . $pagina ) );
$s        = $antwoord->get_data();
$same( 200, $antwoord->get_status(), 'Stand van de editor' );
$same( 1, count( $s['cursusdata']['groepen'] ), 'Eén groep' );
$same( true, $s['cursusdata']['verlopen'], 'Verlopen data herkend' );
$waar( false !== strpos( $s['cursusdata']['groepen'][0]['tijd'], '09:30' ), 'Lestijd in de stand' );
$same( true, $s['focus'], 'Uitsnede: er is een gekozen punt' );

/* ---------- Gegevens als blok ---------- */

$GLOBALS['post'] = get_post( $pagina );
setup_postdata( $GLOBALS['post'] );
$html = do_blocks( '<!-- wp:sfp/cursusdatum {"toon":"eerste"} /-->' );
$waar( false !== strpos( $html, 'sfp-gegeven--cursusdatum' ), 'Blok startmomenten toont iets' );
$waar( false !== strpos( $html, '2020' ) || false !== strpos( $html, 'januari' ) || false !== strpos( $html, 'jan' ), 'Blok startmomenten toont de datum' );
$html = do_blocks( '<!-- wp:sfp/lestijd /-->' );
$waar( false !== strpos( $html, '09:30' ), 'Blok lestijd toont de tijd' );
$html = do_blocks( '<!-- wp:sfp/leestijd /-->' );
$waar( false !== strpos( $html, 'tijd-getal' ), 'Blok leestijd' );
wp_reset_postdata();

/* ---------- Leestijd: geen eindeloze herhaling ---------- */

$zelf = wp_insert_post( array( 'post_status' => 'publish', 'post_title' => 'Proef leestijd', 'post_content' => 'Tekst [mijn_leestijd] tekst' ) );
$opruimen[] = $zelf;
$GLOBALS['post'] = get_post( $zelf );
$waar( false !== strpos( do_shortcode( '[mijn_leestijd]' ), 'custom-read-meter' ), 'Leestijd werkt' );
$waar( false !== strpos( do_shortcode( '[mijn_leestijd]' ), 'custom-read-meter' ), 'Leestijd werkt een tweede keer' );

/* ---------- Uitsnede zonder de tag pijler ---------- */

$this_post = get_post( $pagina );
$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $pagina ) );
$GLOBALS['wp_query']->the_post();
ob_start();
sfp_page_config_hero_focal_css();
$css = ob_get_clean();
$waar( false !== strpos( $css, '100% 50%' ), 'Uitsnede werkt zonder de tag pijler' );
wp_reset_query();

/* ---------- Groep in de keuzelijst met lestijd ---------- */

$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $pagina ) );
$GLOBALS['wp_query']->the_post();
$GLOBALS['wp_query']->is_singular = true;
$GLOBALS['wp_query']->is_page     = true;
$GLOBALS['wp_query']->queried_object    = get_post( $pagina );
$GLOBALS['wp_query']->queried_object_id = $pagina;
$oud_onderdelen = sfp_page_config_onderdeel_aan( 'cursusdata' );
sfp_page_config_instellingen_bewaar( array( 'onderdelen' => array( 'cursusdata' => '1' ) ) );
ob_start();
sfp_page_config_inject_course_data();
$js = ob_get_clean();
$waar( false !== strpos( $js, '09:30' ), 'Keuzelijst toont de lestijd van de groep' );
wp_reset_query();

/* ---------- Mail over cursusdata met links ---------- */

$mail = null;
add_filter(
    'pre_wp_mail',
    function ( $kort, $atts ) use ( &$mail ) {
        $mail = $atts;
        return true;
    },
    10,
    2
);
delete_option( 'sfp_cron_notified' );
sfp_page_config_run_daily_check();
$waar( is_array( $mail ) && false !== strpos( $mail['message'], get_permalink( $pagina ) ), 'Mail noemt de link naar de pagina' );
$waar( is_array( $mail ) && false !== strpos( $mail['message'], 'page=sfp-page-config#cursusdata' ), 'Mail noemt de link naar Cursusdata' );

/* ---------- Blokken: eigen groep, voorbeelden en binnenblokken ---------- */

$register = WP_Block_Type_Registry::get_instance();
$same( 'sfp-bouwstenen', $register->get_registered( 'sfp/kaart' )->category, 'Eigen groep in de blokkenlijst' );
$waar( ! empty( $register->get_registered( 'sfp/samenvatting' )->example ), 'Samenvatting heeft een voorbeeld' );
$same( null, $register->get_registered( 'sfp/stap' )->example, 'Een binnenblok heeft geen eigen voorbeeld' );
$groepen = apply_filters( 'block_categories_all', array( array( 'slug' => 'text', 'title' => 'Tekst' ) ), null );
$same( 'sfp-bouwstenen', $groepen[0]['slug'], 'Groep bovenaan' );

/* ---------- Opruimen ---------- */

foreach ( $opruimen as $id ) {
    wp_delete_post( $id, true );
}
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $gast );
update_option( 'sfp_settings', $oud_inst );

echo ( $fouten ? "MISLUKT: $fouten van $checks\n" : "OK: $checks checks\n" );
