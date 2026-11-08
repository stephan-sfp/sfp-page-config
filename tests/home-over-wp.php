<?php
/**
 * Tests voor de bouwstenen van home en Over (3.34.0): held foto, kaart met
 * beeld boven, kolommen slot en carrousel, Laatste artikelen, Reviewkop,
 * Team, Tabs als kaart, Netwerk, paginasoort Home, ItemList zonder label en
 * de keuze "vanzelf" in Review. Draait in een echte WordPress (een lokale
 * testinstallatie, nooit een live site). Maakt eigen berichten en ruimt ze op.
 *
 * Draaien: wp eval-file wp-content/plugins/sfp-page-config/tests/home-over-wp.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

$checks = 0;
$fouten = 0;
$is     = function ( $verwacht, $echt, $wat ) use ( &$checks, &$fouten ) {
    ++$checks;
    if ( $verwacht !== $echt ) {
        ++$fouten;
        echo "FOUT: $wat: verwacht " . var_export( $verwacht, true ) . ', kreeg ' . var_export( $echt, true ) . "\n";
    }
};
$bevat = function ( $naald, $hooi, $wat, $ja = true ) use ( $is ) {
    $is( $ja, false !== strpos( (string) $hooi, $naald ), $wat );
};
$weg = array();

/* ---------- Held foto ---------- */
$beeld = (int) get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'numberposts' => 1, 'fields' => 'ids' ) )[0];
$img   = '<!-- wp:image {"id":' . $beeld . '} --><figure class="wp-block-image"><img src="' . wp_get_attachment_url( $beeld ) . '" alt="x" class="wp-image-' . $beeld . '"/></figure><!-- /wp:image -->';
$html  = do_blocks( '<!-- wp:sfp/held {"variant":"foto","mobielId":' . $beeld . ',"deelnemers":"500 deelnemers"} --><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Titel</h1><!-- /wp:heading -->' . $img . '<!-- /wp:sfp/held -->' );
$bevat( 'sfp-held--foto', $html, 'held foto heeft zijn klasse' );
$bevat( 'sfp-held__achter', $html, 'foto staat achter de tekst' );
$bevat( '<picture><source media="(max-width: 700px)"', $html, 'telefoonfoto als bron' );
$bevat( 'fetchpriority="high"', $html, 'heldfoto met voorrang' );
$bevat( 'loading="lazy"', preg_replace( '/<div class="sfp-held__binnen">.*/s', '', $html ), 'heldfoto niet lui', false );
$bevat( '--sfp-held-focus:68% 40%', $html, 'standaard focus rechts' );
$bevat( 'wp-block-heading h1-xl', $html, 'H1 krijgt h1-xl' );
$is( 1, preg_match( '/sfp-held__binnen">.*sfp-held__bewijs/s', $html ), 'bewijsregel in de tekst' );

/* ---------- Held wit zonder feitenkaart (O4) ---------- */
$wit = do_blocks( '<!-- wp:sfp/held {"variant":"wit","feitenkaart":false,"feiten":[{"label":"Waar","waarde":"Utrecht"}]} -->' . $img . '<!-- /wp:sfp/held -->' );
$bevat( 'sfp-held__feit', $wit, 'feitenkaart uit', false );
$wit2 = do_blocks( '<!-- wp:sfp/held {"variant":"wit","feiten":[{"label":"Waar","waarde":"Utrecht"}]} -->' . $img . '<!-- /wp:sfp/held -->' );
$bevat( 'sfp-held__feit', $wit2, 'feitenkaart standaard aan' );

/* ---------- Kaart met beeld boven ---------- */
$k = do_blocks( '<!-- wp:sfp/kaart {"beeld":true,"link":"/x/"} -->' . $img . '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Kop</h3><!-- /wp:heading --><!-- /wp:sfp/kaart -->' );
$bevat( 'sfp-kaart--beeld', $k, 'kaart met beeld boven' );
$is( 1, preg_match( '/<\/figure><div class="sfp-kaart__tekst"><h3/', $k ), 'beeld eerst, tekst in een vak' );

/* ---------- Kolommen slot en carrousel ---------- */
$bevat( 'sfp-kolommen--slot', do_blocks( '<!-- wp:sfp/kolommen {"indeling":"slot"} --><!-- /wp:sfp/kolommen -->' ), 'indeling slot' );
$bevat( 'sfp-reviews-rij--carrousel', do_blocks( '<!-- wp:sfp/kolommen {"indeling":"reviews","carrousel":true} --><!-- /wp:sfp/kolommen -->' ), 'carrousel bij reviews' );
$bevat( 'sfp-reviews-rij--carrousel', do_blocks( '<!-- wp:sfp/kolommen {"indeling":"twee","carrousel":true} --><!-- /wp:sfp/kolommen -->' ), 'geen carrousel buiten reviews', false );

/* ---------- Laatste artikelen ---------- */
$p1    = wp_insert_post( array( 'post_title' => 'Artikel een', 'post_status' => 'publish', 'post_date' => wp_date( 'Y-m-d H:i:s', time() - 60 ) ) );
$p2    = wp_insert_post( array( 'post_title' => 'Artikel twee', 'post_status' => 'publish', 'post_date' => wp_date( 'Y-m-d H:i:s', time() - 30 ) ) );
$weg[] = $p1;
$weg[] = $p2;
set_post_thumbnail( $p1, $beeld );
$a = do_blocks( '<!-- wp:sfp/artikelen {"aantal":2} /-->' );
$is( 2, substr_count( $a, 'sfp-artikel"' ), 'twee kaarten' );
$is( true, strpos( $a, 'Artikel twee' ) < strpos( $a, 'Artikel een' ), 'nieuwste eerst' );
$bevat( 'sfp-geenbeeld', $a, 'zonder beeld een vlak' );
$bevat( 'sfp-kaart__link', $a, 'hele kaart klikbaar' );
$bevat( sfp_page_config_tekst( 'artikel_lees' ), $a, 'tekst uit teksten.php' );
$bevat( 'sfp-artikel__uittreksel', $a, 'geen uittreksel standaard', false );

/* ---------- Team ---------- */
$t = do_blocks( '<!-- wp:sfp/team --><!-- wp:sfp/teamlid {"naam":"Anna","rol":"Trainer","zin":"Zin","link":"/anna/","fotoId":' . $beeld . '} /--><!-- wp:sfp/teamlid {"naam":""} /--><!-- /wp:sfp/team -->' );
$is( 1, substr_count( $t, '<div class="sfp-team__lid">' ), 'teamlid zonder naam valt weg' );
$bevat( '<a href="/anna/">Anna</a>', $t, 'naam als link' );
$bevat( 'alt="Anna"', $t, 'naam als alt' );
$is( '', do_blocks( '<!-- wp:sfp/team --><!-- /wp:sfp/team -->' ), 'leeg team toont niets' );

/* ---------- Tabs als kaart ---------- */
$tb = do_blocks( '<!-- wp:sfp/tabs {"vorm":"kaart"} --><!-- wp:sfp/tab {"titel":"Een","icoonId":' . $beeld . '} --><!-- wp:paragraph --><p>a</p><!-- /wp:paragraph --><!-- /wp:sfp/tab --><!-- wp:sfp/tab {"titel":"Twee"} --><!-- wp:paragraph --><p>b</p><!-- /wp:paragraph --><!-- /wp:sfp/tab --><!-- /wp:sfp/tabs -->' );
$bevat( 'sfp-tabs--kaart', $tb, 'tabs als kaart' );
$is( 1, substr_count( $tb, 'sfp-tabpaneel__icoon' ), 'één icoon' );
$is( 2, substr_count( $tb, 'sfp-tabpaneel__tekst' ), 'tekstvak per tab' );
$bevat( 'sfp-tabpaneel__tekst', do_blocks( '<!-- wp:sfp/tabs --><!-- wp:sfp/tab {"titel":"Een"} --><!-- /wp:sfp/tab --><!-- /wp:sfp/tabs -->' ), 'gewone tabs ongewijzigd', false );

/* ---------- Netwerk ---------- */
$oud = get_option( 'sfp_settings', array() );
$s   = $oud;
$s['netwerk'] = array(
    array( 'naam' => 'Ander', 'url' => 'https://ander.example', 'zin' => 'Zin', 'icoon' => 0 ),
    array( 'naam' => 'Zelf', 'url' => home_url( '/' ), 'zin' => 'Weg', 'icoon' => 0 ),
    array( 'naam' => '', 'url' => 'https://leeg.example' ),
);
update_option( 'sfp_settings', $s );
$n = do_blocks( '<!-- wp:sfp/netwerk /-->' );
$bevat( 'Ander', $n, 'ander label staat er' );
$bevat( 'Zelf', $n, 'eigen site valt weg', false );
$is( 2, count( sfp_page_config_netwerk_instelling() ), 'label zonder naam valt weg' );
$is( 1, count( sfp_page_config_netwerk_schoon( array( array( 'naam' => '<b>X</b>', 'url' => 'javascript:alert(1)' ), array( 'naam' => 'Y', 'url' => 'https://y.example', 'icoon' => '7a' ) ) ) ), 'onveilig adres valt weg' );
update_option( 'sfp_settings', $oud );
if ( empty( $oud['netwerk'] ) ) {
    $is( '', do_blocks( '<!-- wp:sfp/netwerk /-->' ), 'zonder labels niets' );
}

/* ---------- Paginasoort Home ---------- */
$is( true, in_array( 'home', sfp_page_config_paginasoorten(), true ), 'home is een paginasoort' );
$knop = sfp_page_config_get_sticky_cta( 'home' );
$is( sfp_page_config_tekst( 'knop_home' ), $knop['text'] ?? ( $knop['tekst'] ?? '' ), 'knoptekst voor de home' );

/* ---------- ItemList: label telt niet ---------- */
$home = wp_insert_post(
    array(
        'post_type'    => 'page',
        'post_title'   => 'Home test',
        'post_status'  => 'publish',
        'post_content' => '<!-- wp:sfp/sectie {"anker":"services"} --><!-- wp:heading --><h2 class="wp-block-heading">Aanbod</h2><!-- /wp:heading --><!-- wp:sfp/kaart {"link":"https://x.example/t/","beeld":true} -->' . $img . '<!-- wp:paragraph {"className":"is-style-sfp-label"} --><p class="is-style-sfp-label">In een groep</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Training</h3><!-- /wp:heading --><!-- wp:paragraph --><p>De omschrijving.</p><!-- /wp:paragraph --><!-- /wp:sfp/kaart --><!-- /wp:sfp/sectie -->',
    )
);
$weg[] = $home;
$g     = sfp_page_config_aanbod_gegevens( get_post( $home ) );
$is( 'De omschrijving.', $g['itemListElement'][0]['item']['description'] ?? '', 'omschrijving is de zin, niet het label' );

/* ---------- Review vanzelf ---------- */
if ( sfp_page_config_review_google_lijst() ) {
    $een  = sfp_page_config_review_kaart_vind( 'google' );
    $twee = sfp_page_config_review_kaart_vind( 'google' );
    $is( true, is_array( $een ) && is_array( $twee ) && $een['id'] !== $twee['id'], 'vanzelf: twee verschillende Google-reviews' );
} else {
    $is( null, sfp_page_config_review_kaart_vind( 'google' ), 'vanzelf zonder Google: niets' );
}

foreach ( $weg as $id ) {
    wp_delete_post( $id, true );
}
echo $fouten ? "MISLUKT: {$fouten} van {$checks}\n" : "OK: {$checks} checks\n";
