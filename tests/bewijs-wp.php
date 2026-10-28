<?php
/**
 * Tests voor 3.14.0 in een echte WordPress: Logostrook, Review, de
 * vermelding bij eigen reviews en de kleuren van het reviewblok.
 *
 * Draaien: wp eval-file wp-content/plugins/sfp-page-config/tests/bewijs-wp.php
 * in een proefinstallatie met SFP Page Config aan (nooit een live site).
 * Maakt eigen bijlagen en reviews aan en zet alles aan het eind terug.
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
$bevat = function ( $hooiberg, $naald, $wat ) use ( $same ) {
    $same( true, false !== strpos( (string) $hooiberg, (string) $naald ), $wat . ' (zoekt ' . $naald . ')' );
};
$mist = function ( $hooiberg, $naald, $wat ) use ( $same ) {
    $same( false, false !== strpos( (string) $hooiberg, (string) $naald ), $wat . ' (zonder ' . $naald . ')' );
};

$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
wp_set_current_user( $admin->ID );
$oud_eigen = get_option( SFP_Reviews_Manual::OPTION, null );
$oud_inst  = get_option( 'sfp_settings' );
$weg       = array();

/* ---------- Registratie ---------- */

$reg = WP_Block_Type_Registry::get_instance();
$waar( $reg->is_registered( 'sfp/logostrook' ), 'Logostrook geregistreerd' );
$waar( $reg->is_registered( 'sfp/review' ), 'Review geregistreerd' );
$same( 'sfp-bouwstenen', $reg->get_registered( 'sfp/logostrook' )->category, 'Logostrook in de groep Bouwstenen' );
$same( 'sfp-bouwstenen', $reg->get_registered( 'sfp/review' )->category, 'Review in de groep Bouwstenen' );
$same( array(), $reg->get_registered( 'sfp/logostrook' )->attributes['logos']['default'], 'Logostrook: standaard geen logo\'s' );
foreach ( array( 'logostrook', 'review-kaart' ) as $bestand ) {
    $waar( '' !== sfp_page_config_blokken_css_bestand( $bestand ), 'CSS-bestand ' . $bestand . ' bestaat' );
    $mist( sfp_page_config_blokken_css_bestand( $bestand ), 'font-family:"', 'Geen fontnaam in ' . $bestand );
}
$css_kaart = sfp_page_config_blokken_css_bestand( 'review-kaart' );
preg_match_all( '/#[0-9a-fA-F]{3,8}\b/', $css_kaart . sfp_page_config_blokken_css_bestand( 'logostrook' ), $hex );
$same( array( '#FBBC04' ), array_values( array_unique( $hex[0] ) ), 'Enige vaste kleur is de reviewster' );
$bevat( $css_kaart, 'border:1px solid var(--sfp-b-lijn)', 'Kaartrand zoals de andere kaarten' );
$bevat( $css_kaart, 'font-style:normal', 'Review niet cursief' );
$bevat( $css_kaart, 'quotes:none', 'Review zonder aanhalingstekens' );
$bevat( sfp_page_config_blokken_css_bestand( 'logostrook' ), '.sfp-logostrook__label + .sfp-logostrook__logos{margin-top:44px}', 'Meer ruimte tussen label en logo\'s' );

/* ---------- Logostrook ---------- */

$maak_beeld = function ( $naam, $breed, $hoog ) use ( &$weg ) {
    $map = wp_upload_dir();
    $pad = trailingslashit( $map['path'] ) . $naam . '.png';
    $img = imagecreatetruecolor( $breed, $hoog );
    imagefill( $img, 0, 0, imagecolorallocate( $img, 200, 200, 200 ) );
    imagepng( $img, $pad );
    imagedestroy( $img );
    $id = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => $naam, 'post_status' => 'inherit' ), $pad );
    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $pad ) );
    update_post_meta( $id, '_wp_attachment_image_alt', $naam . ' logo' );
    $weg[] = $id;
    return $id;
};
$a = $maak_beeld( 'proef-klant-a', 600, 200 );
$b = $maak_beeld( 'proef-klant-b', 1200, 200 );
$tekstpost = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Geen beeld' ) );
$weg[]     = $tekstpost;

$same( '', sfp_page_config_render_logostrook( array( 'logos' => array() ) ), 'Logostrook zonder logo\'s: niets op de site' );
$same( '', sfp_page_config_render_logostrook( array( 'logos' => array( $tekstpost, 999999 ) ) ), 'Logostrook met alleen geen-beelden: niets' );
$same( array( $b, $a ), sfp_page_config_logostrook_ids( array( $b, (string) $a, $b, $tekstpost, -3 ) ), 'Volgorde blijft, dubbel en geen beeld eruit' );

$html = sfp_page_config_render_logostrook( array( 'logos' => array( $b, $a ) ) );
$bevat( $html, 'class="sfp-logostrook__label">' . esc_html( sfp_page_config_tekst( 'logostrook_label' ) ) . '<', 'Leeg label: standaardtekst' );
$bevat( $html, '<ul class="sfp-logostrook__logos" role="list">', 'Logo\'s als lijst' );
$same( 2, substr_count( $html, '<img ' ), 'Twee logo\'s' );
$waar( strpos( $html, 'proef-klant-b logo' ) < strpos( $html, 'proef-klant-a logo' ), 'Logo\'s in de gekozen volgorde' );
$bevat( $html, 'alt="proef-klant-a logo"', 'Alt-tekst uit de mediabibliotheek' );
$bevat( $html, 'loading="lazy"', 'Logo\'s laden lui' );
$mist( $html, 'srcset=', 'Logo\'s als origineel, zonder kleinere varianten' );
$bevat( $html, 'width="1200" height="200"', 'Logo met de maten van het origineel' );
$html = sfp_page_config_render_logostrook( array( 'logos' => array( $a ), 'label' => 'Klanten <b>van</b> ons' ) );
$bevat( $html, '>Klanten van ons</p>', 'Eigen label, zonder opmaak' );
$same( 'Trusted by teams at', sfp_page_config_tekst_standaard( 'logostrook_label', 'en' ), 'Standaardlabel Engels zoals op SwS' );
$waar( '' !== sfp_page_config_tekst_standaard( 'logostrook_label', 'nl' ) && '' !== sfp_page_config_tekst_standaard( 'logostrook_label', 'pt' ), 'Standaardlabel in nl en pt' );

// Zoals het in de inhoud staat: één blok zonder binnenblokken.
$inhoud = '<!-- wp:sfp/logostrook {"logos":[' . $a . ',' . $b . ']} /-->';
$blokken = parse_blocks( $inhoud );
$same( 'sfp/logostrook', $blokken[0]['blockName'], 'Opgeslagen vorm wordt herkend' );
$waar( preg_match( '/class="[^"]*\\bsfp-logostrook\\b[^"]*\\bwp-block-sfp-logostrook\\b|class="[^"]*\\bwp-block-sfp-logostrook\\b[^"]*\\bsfp-logostrook\\b/', render_block( $blokken[0] ) ), 'Omhulling met blokklasse' );

/* ---------- Eigen reviews: vermelding ---------- */

SFP_Reviews_Manual::save_all( array() );
$lijst = SFP_Reviews_Manual::add( array( 'author' => 'Vy Lê', 'rating' => 5, 'datum' => '2024-03-20', 'text' => "Thank you, Stephan for the very insightful training.", 'vermelding' => 'Google review of my Dutch company, De Presenteerschool.' ) );
$r1    = $lijst[0];
$same( 'Google review of my Dutch company, De Presenteerschool.', $r1['vermelding'], 'Vermelding wordt bewaard' );
$same( '', SFP_Reviews_Manual::schoon( array( 'author' => 'x', 'text' => 'y' ) )['vermelding'], 'Zonder vermelding: leeg' );
$mist( SFP_Reviews_Manual::schoon( array( 'vermelding' => "a <script>x</script>\nb" ) )['vermelding'], '<', 'Vermelding is platte tekst' );

// Bijwerken via het beheer houdt de vermelding (en verliest hem niet stil).
rest_get_server();
$verzoek = new WP_REST_Request( 'POST', '/sfp/v1/beheer/reviews/eigen' );
$verzoek->set_header( 'content-type', 'application/json' );
$verzoek->set_body( wp_json_encode( array( 'id' => $r1['id'], 'author' => 'Vy Lê', 'rating' => 5, 'datum' => '2024-03-20', 'text' => 'Thank you, Stephan for the very insightful training.', 'vermelding' => 'Translated from Dutch.' ) ) );
$antwoord = rest_do_request( $verzoek );
$same( 200, $antwoord->get_status(), 'Beheer: eigen review opslaan' );
$same( 'Translated from Dutch.', SFP_Reviews_Manual::get_all()[0]['vermelding'], 'Beheer: vermelding bijgewerkt' );
$verzoek->set_body( wp_json_encode( array( 'id' => $r1['id'], 'author' => 'Vy Lê', 'rating' => 4, 'datum' => '2024-03-20', 'text' => 'Thank you.', 'vermelding' => 'Translated from Dutch.' ) ) );
rest_do_request( $verzoek );
$same( 'Translated from Dutch.', SFP_Reviews_Manual::get_all()[0]['vermelding'], 'Beheer: andere wijziging houdt de vermelding' );
$stand = rest_do_request( new WP_REST_Request( 'GET', '/sfp/v1/beheer' ) )->get_data();
$same( 'Translated from Dutch.', $stand['reviews']['eigen'][0]['vermelding'], 'Beheer: vermelding in de stand' );

/* ---------- Review ---------- */

$inst = is_array( $oud_inst ) ? $oud_inst : array();
$inst['onderdelen']['reviews'] = '1';
update_option( 'sfp_settings', $inst );
if ( function_exists( 'sfp_page_config_instellingen_vergeet' ) ) {
    sfp_page_config_instellingen_vergeet();
}
SFP_Reviews_Manual::save_all( array() );
$lijst = SFP_Reviews_Manual::add( array( 'author' => 'Petra', 'rating' => 4, 'datum' => '2025-01-02', 'text' => "Eerste regel.\nTweede regel.\n\nNieuwe alinea met <b>html</b> & meer.", 'vermelding' => 'Translated from Dutch.' ) );
$petra = $lijst[0];

if ( sfp_page_config_reviews_actief() ) {
    $html = sfp_page_config_render_review_kaart( array( 'review' => $petra['id'] ) );
    $bevat( $html, '<div class="sfp-review-kaart', 'Review: kaart' );
    $same( 5, substr_count( $html, '<svg class="sfp-review-kaart__ster' ), 'Review: vijf sterren' );
    $same( 4, substr_count( $html, 'sfp-review-kaart__ster is-vol' ), 'Review: gevuld tot het cijfer' );
    $bevat( $html, 'aria-label="' . esc_attr( sfp_page_config_tekst_f( 'reviews_sterren', 4 ) ) . '"', 'Review: sterren voor schermlezers' );
    $bevat( $html, '<p>Eerste regel.<br>Tweede regel.</p><p>Nieuwe alinea met html &amp; meer.</p>', 'Review: alinea\'s en veilige tekst' );
    $bevat( $html, '<p class="sfp-review-kaart__naam">Petra</p>', 'Review: naam' );
    $bevat( $html, '<p class="sfp-review-kaart__bron">Translated from Dutch.</p>', 'Review: vermelding' );
    $mist( $html, '<blockquote', 'Review: geen citaatblok' );
    $mist( $html, '<em', 'Review: geen cursief' );
    $mist( $html, '“', 'Review: geen aanhalingsteken' );
    $same( '', sfp_page_config_render_review_kaart( array( 'review' => '' ) ), 'Review: niets gekozen, niets op de site' );
    $same( '', sfp_page_config_render_review_kaart( array( 'review' => 'mr_bestaat_niet' ) ), 'Review: weg, niets op de site' );
    $zonder = SFP_Reviews_Manual::add( array( 'author' => 'Anne', 'rating' => 5, 'text' => 'Goed.' ) );
    $mist( sfp_page_config_render_review_kaart( array( 'review' => $zonder[1]['id'] ) ), 'sfp-review-kaart__bron', 'Review zonder vermelding: geen lege regel' );

    // Zoals het in de inhoud staat.
    $blokken = parse_blocks( '<!-- wp:sfp/review {"review":"' . $petra['id'] . '"} /-->' );
    $bevat( render_block( $blokken[0] ), 'sfp-review-kaart__tekst', 'Opgeslagen vorm wordt getoond' );

    // Onderdeel uit: niets.
    $inst['onderdelen']['reviews'] = '0';
    update_option( 'sfp_settings', $inst );
    if ( function_exists( 'sfp_page_config_instellingen_vergeet' ) ) {
        sfp_page_config_instellingen_vergeet();
    }
    if ( ! sfp_page_config_reviews_actief() ) {
        $same( '', sfp_page_config_render_review_kaart( array( 'review' => $petra['id'] ) ), 'Review: onderdeel uit, niets op de site' );
    } else {
        echo "LET OP: onderdeel Reviews kon in deze test niet uit (instellingen in een vaste cache).\n";
    }
} else {
    ++$fouten;
    echo "FOUT: onderdeel Reviews staat niet aan in de proef\n";
}

/* ---------- Reviewblok: vermelding, enkelvoud, kleuren ---------- */

$rend = new SFP_Reviews_Renderer();
$kaart = $rend->render( array( SFP_Reviews_Manual::schoon( array( 'author' => 'X', 'rating' => 5, 'text' => 'Tekst', 'vermelding' => 'Vertaald.' ) ) + array( 'time' => '' ) ), array( 'layout' => 'grid' ) );
$bevat( $kaart, '<p class="sfp-review__vermelding">Vertaald.</p>', 'Reviewblok: vermelding onder de tekst' );
$kaart = $rend->render( array( SFP_Reviews_Manual::schoon( array( 'author' => 'X', 'rating' => 5, 'text' => 'Tekst' ) ) ), array( 'layout' => 'grid' ) );
$mist( $kaart, 'sfp-review__vermelding', 'Reviewblok zonder vermelding: geen lege regel' );
$same( sfp_page_config_tekst_f( 'reviews_basis_een', '1' ), $rend->samenvatting_teksten( array( 'rating' => 5, 'total' => 1 ) )['basis'], 'Eén review: enkelvoud (wens 5)' );
$same( sfp_page_config_tekst_f( 'reviews_basis', '2' ), $rend->samenvatting_teksten( array( 'rating' => 5, 'total' => 2 ) )['basis'], 'Twee reviews: meervoud' );
$bevat( $rend->samenvatting( array( 'rating' => 5, 'total' => '1' ) ), esc_html( sfp_page_config_tekst_f( 'reviews_basis_een', '1' ) ), 'Eén review als tekst uit Google: enkelvoud' );
$rcss = (string) file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/reviews/reviews.css' );
$same( 2, substr_count( $rcss, 'color-mix(in srgb, var(--sfp-rol-primair, currentColor) 20%, transparent)' ), 'Reviewblok: rand en lege ster primair 20% (wens 6)' );
$mist( $rcss, '--sfp-review-border:        var(--sfp-rol-tint-2', 'Reviewblok: rand niet meer op de tint' );

/* ---------- Editor: gegevens voor de keuzelijst ---------- */

wp_scripts()->remove( 'sfp-blokken-editor' );
wp_register_script( 'sfp-blokken-editor', 'x.js', array(), '1', true );
sfp_page_config_bewijs_editor_data();
$data = wp_scripts()->get_data( 'sfp-blokken-editor', 'before' );
$data = is_array( $data ) ? implode( "\n", $data ) : (string) $data;
$bevat( $data, 'window.sfpBewijs=', 'Editor: gegevens staan klaar' );
$bevat( $data, '"author":"Petra"', 'Editor: eigen reviews in de keuzelijst' );
$bevat( $data, 'page=sfp-page-config#onderdelen\\/reviews', 'Editor: link naar SFP Beheer, Reviews' );

/* ---------- Opruimen ---------- */

foreach ( $weg as $id ) {
    wp_delete_post( $id, true );
}
if ( null === $oud_eigen ) {
    delete_option( SFP_Reviews_Manual::OPTION );
} else {
    update_option( SFP_Reviews_Manual::OPTION, $oud_eigen );
}
if ( false === $oud_inst ) {
    delete_option( 'sfp_settings' );
} else {
    update_option( 'sfp_settings', $oud_inst );
}

echo ( $fouten ? 'MISLUKT: ' . $fouten . ' van ' : 'OK: ' ) . $checks . " controles\n";
