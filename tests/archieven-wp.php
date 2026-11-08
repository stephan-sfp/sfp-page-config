<?php
/**
 * Tests voor de bouwstenen van de archieven en de leeskolom (3.35.0):
 * Archiefkop, Gidsen, Artikelraster, Auteurskop, Contactgegevens, het
 * alfabet bij de begrippen, de telefoonnotatie, de instelling Contact en de
 * kaart van Laatste artikelen (H4) die gelijk moet blijven. Draait in een
 * echte WordPress (een lokale testinstallatie, nooit een live site). Maakt
 * eigen berichten, een categorie en een gebruiker en ruimt ze op.
 *
 * Draaien: wp eval-file wp-content/plugins/sfp-page-config/tests/archieven-wp.php
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
$zonder_css = function ( $html ) {
    return preg_replace( '#<style[^>]*>.*?</style>#s', '', (string) $html );
};
$ga = function ( $url ) {
    // Een verzoek aan de voorkant nabootsen: de hoofdlijst opnieuw opbouwen.
    $GLOBALS['wp']->main( wp_parse_url( $url, PHP_URL_QUERY ) ? wp_parse_url( $url, PHP_URL_QUERY ) : '' );
    $GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
};

/* ---------- Telefoonnotatie ---------- */
$is( '085 060 1355', sfp_page_config_telefoon( '085 060 13 55' )['tekst'], 'vast nummer 3-3-4' );
$is( '+31850601355', sfp_page_config_telefoon( '085 060 13 55' )['tel'], 'tel met +31' );
$is( '085 060 1355', sfp_page_config_telefoon( '31850601355' )['tekst'], 'van 31 naar 0' );
$is( '085 060 1355', sfp_page_config_telefoon( '+31 85 060 1355' )['tekst'], 'met +31 en spaties' );
$is( '085 060 1355', sfp_page_config_telefoon( '0031850601355' )['tekst'], 'met 0031' );
$is( '06 1234 5678', sfp_page_config_telefoon( '0612345678' )['tekst'], 'mobiel 2-4-4' );
$is( '+55 11 91234 5678', sfp_page_config_telefoon( '+55 11 91234 5678' )['tekst'], 'buitenland blijft zoals ingevuld' );
$is( '', sfp_page_config_telefoon( 'geen' )['tekst'], 'geen cijfers: leeg' );

/* ---------- Instelling Contact ---------- */
$schoon = sfp_page_config_contact_schoon( array( 'telefoon' => '085<b>060</b>1355', 'email' => 'geen mail', 'adres' => 'Straat 1 <script>x</script>' ) );
$is( '0850601355', str_replace( ' ', '', $schoon['telefoon'] ), 'telefoon zonder tags' );
$is( '', $schoon['email'], 'ongeldig e-mailadres valt weg' );
$bevat( '<script', $schoon['adres'], 'adres zonder script', false );
$oud = get_option( 'sfp_settings', array() );
$s   = is_array( $oud ) ? $oud : array();
$s['contact'] = array( 'telefoon' => '', 'email' => '', 'adres' => '' );
unset( $s['whatsapp_number'] );
update_option( 'sfp_settings', $s );
$c = sfp_page_config_contact();
$is( '085 060 1355', $c['telefoon']['tekst'], 'leeg: het nummer van het netwerk' );
$is( '', $c['adres'], 'leeg adres: geen adres' );
$html = $zonder_css( do_blocks( '<!-- wp:sfp/contactgegevens /-->' ) );
$bevat( 'sfp-tekstlink', $html, 'zonder adres geen Maps-link', false );
$s['contact'] = array( 'telefoon' => '085 060 13 55', 'email' => 'info@voorbeeld.nl', 'adres' => 'Keizersgracht 520h, 1017 EK Amsterdam' );
update_option( 'sfp_settings', $s );
$html = $zonder_css( do_blocks( '<!-- wp:sfp/contactgegevens /-->' ) );
$is( 4, substr_count( $html, '<dt' ), 'vier regels' );
$bevat( 'href="tel:+31850601355"', $html, 'tel-link' );
$bevat( '>085 060 1355<', $html, 'nummer in de netwerknotatie' );
$bevat( 'https://wa.me/31850601355', $html, 'WhatsApp-link' );
$bevat( 'query=Keizersgracht%20520h', $html, 'Maps-link uit het adres' );
$bevat( 'openstreetmap', $html, 'geen kaart van OpenStreetMap', false );

/* ---------- Testinhoud ---------- */
$weg   = array();
$cat   = wp_insert_term( 'Proefcategorie archief', 'category', array( 'description' => 'Omschrijving van de proefcategorie.' ) );
$catid = (int) $cat['term_id'];
$user  = wp_insert_user(
    array(
        'user_login'    => 'proefauteur' . wp_rand( 100, 999 ),
        'user_pass'     => wp_generate_password(),
        'display_name'  => 'Proef Auteur',
        'first_name'    => 'Proef',
        'role'          => 'author',
        'description'   => "Eerste alinea.\n\n√ Feit een\n√ Feit twee",
    )
);
update_user_meta( $user, 'sfp_auteur_rol', 'Trainer' );
update_user_meta( $user, 'sfp_auteur_linkedin', 'https://www.linkedin.com/in/proef' );
for ( $i = 1; $i <= 4; $i++ ) {
    $weg[] = wp_insert_post(
        array(
            'post_title'    => 'Proefartikel archief ' . $i,
            'post_status'   => 'publish',
            'post_author'   => $user,
            'post_category' => array( $catid ),
            'post_excerpt'  => 'Samenvatting ' . $i,
            'post_date'     => wp_date( 'Y-m-d H:i:s', time() - $i * 3600 ),
        )
    );
}

/* ---------- Artikelkaart: H4 blijft gelijk, R3 met opties ---------- */
$p   = get_post( $weg[0] );
$h4  = sfp_page_config_artikel_kaart( $p );
$bevat( 'is-style-sfp-pijl', $h4, 'H4 houdt de pijllink' );
$bevat( 'Proef Auteur · ', $h4, 'H4 houdt auteur en datum' );
$bevat( '<h3 class="wp-block-heading">Proefartikel archief 1</h3>', $h4, 'H4 titel zonder link' );
$r3 = sfp_page_config_artikel_kaart( $p, true, array( 'label' => 'Spreekangst', 'meta' => '', 'pijl' => false, 'uittreksel' => 'Eigen uittreksel' ) );
$bevat( 'is-style-sfp-pijl', $r3, 'R3 zonder pijllink', false );
$bevat( '<p class="is-style-sfp-label">Spreekangst</p><h3', $r3, 'R3 label boven de titel' );
$bevat( 'sfp-artikel__meta', $r3, 'lege regel valt weg', false );
$bevat( '>Eigen uittreksel<', $r3, 'eigen uittreksel' );
$bevat( '<h3 class="wp-block-heading"><a href=', $r3, 'R3 titel als link' );

/* ---------- Categorie ---------- */
$ga( add_query_arg( 'cat', $catid, home_url( '/' ) ) );
$kop = $zonder_css( do_blocks( '<!-- wp:sfp/archiefkop /-->' ) );
$is( 'categorie', sfp_page_config_archief_soort(), 'soort categorie' );
$bevat( '<mark class="sfp-accent">Proefcategorie archief</mark>', $kop, 'categorienaam met accent' );
$bevat( 'Omschrijving van de proefcategorie.', $kop, 'omschrijving als kernzin' );
$bevat( 'name="s"', $kop, 'zoekveld' );
$bevat( 'class="is-actief" aria-current="page">Proefcategorie archief', $kop, 'categorie actief in de filterrij' );
$raster = $zonder_css( do_blocks( '<!-- wp:sfp/artikelraster /-->' ) );
$bevat( 'Alle artikelen over proefcategorie archief', $raster, 'kop op een categorie' );
$is( 4, substr_count( $raster, 'sfp-artikel"' ), 'vier kaarten' );
$bevat( '4 artikelen', $raster, 'aantal bij één pagina' );
$bevat( 'sfp-sectie--tint', $raster, 'tint zonder gidsen ervoor' );
$bevat( 'Samenvatting 1', $raster, 'uittreksel uit de samenvatting' );
$is( '', $zonder_css( do_blocks( '<!-- wp:sfp/gidsen /-->' ) ), 'geen gids: niets, ook geen sectie' );

/* ---------- Blog: getypte teksten per soort ---------- */
$GLOBALS['wp_query']->is_category = false;
$GLOBALS['wp_query']->is_archive  = false;
$GLOBALS['wp_query']->is_home     = true;
$kop = $zonder_css( do_blocks( '<!-- wp:sfp/archiefkop {"teksten":{"blog":{"label":"Blog","titel":"Je weet <mark class=\"sfp-accent\">wat je doet</mark><script>x</script>","kernzin":"Kernzin blog"},"begrippen":{"titel":"Alleen begrippen"}}} /-->' ) );
$is( 'blog', sfp_page_config_archief_soort(), 'soort blog' );
$bevat( 'Je weet <mark class="sfp-accent">wat je doet</mark>', $kop, 'getypte titel met accent' );
$bevat( '<script', $kop, 'geen script in de titel', false );
$bevat( 'Alleen begrippen', $kop, 'tekst van een andere soort niet op de blog', false );
$bevat( 'Kernzin blog', $kop, 'kernzin' );
$bevat( 'class="is-actief" aria-current="page">' . sfp_page_config_tekst( 'archief_alle' ), $kop, 'Alle artikelen actief op de blog' );

/* ---------- Zoeken ---------- */
$ga( add_query_arg( 's', 'Proefartikel archief', home_url( '/' ) ) );
$kop = $zonder_css( do_blocks( '<!-- wp:sfp/archiefkop /-->' ) );
$is( 'zoeken', sfp_page_config_archief_soort(), 'soort zoeken' );
$bevat( 'Resultaten voor <mark class="sfp-accent">Proefartikel archief</mark>', $kop, 'zoekterm in de titel' );
$bevat( 'value="Proefartikel archief"', $kop, 'zoekterm in het veld' );
$bevat( 'sfp-filter', $kop, 'geen filterrij bij zoeken', false );
$raster = $zonder_css( do_blocks( '<!-- wp:sfp/artikelraster /-->' ) );
$bevat( '>' . sfp_page_config_tekst( 'archief_soort_artikel' ) . '</p><h3', $raster, 'soort als label bij zoeken' );
$bevat( 'sfp-artikel__meta', $raster, 'artikel houdt auteur en datum bij zoeken' );

/* ---------- Auteur ---------- */
$ga( add_query_arg( 'author', $user, home_url( '/' ) ) );
$auteur = $zonder_css( do_blocks( '<!-- wp:sfp/auteurskop /-->' ) );
$is( 'auteur', sfp_page_config_archief_soort(), 'soort auteur' );
$bevat( '<h1 class="wp-block-heading">Proef Auteur</h1>', $auteur, 'naam als H1' );
$bevat( 'sfp-auteur__rol">Trainer', $auteur, 'rol uit het profiel' );
$bevat( '<div class="sfp-lijst sfp-lijst--vink"><ul><li>Feit een</li><li>Feit twee</li></ul></div>', $auteur, 'regels met vinkje als lijst' );
$bevat( 'Eerste alinea.', $auteur, 'gewone alinea blijft' );
$bevat( 'sfp-auteur--zonder-foto', $auteur, 'zonder foto een kolom' );
$bevat( 'gravatar', $auteur, 'geen externe foto', false );
$bevat( 'LinkedIn', $auteur, 'LinkedIn-link' );
$raster = $zonder_css( do_blocks( '<!-- wp:sfp/artikelraster /-->' ) );
$bevat( sfp_page_config_tekst_f( 'archief_van', 'Proef' ), $raster, 'kop Artikelen van voornaam' );
$bevat( 'Proef Auteur · ', $raster, 'op de auteurspagina alleen de datum', false );

/* ---------- Gidsen en wit na gidsen ---------- */
$ga( home_url( '/' ) );
$GLOBALS['wp_query']->is_home = true;
sfp_page_config_archief_gidsen_getoond( true );
$raster = $zonder_css( do_blocks( '<!-- wp:sfp/artikelraster /-->' ) );
$bevat( 'sfp-sectie--tint', $raster, 'wit na een Gidsen-blok', false );
$bevat( 'sfp-tint', do_blocks( '<!-- wp:sfp/artikelraster {"achtergrond":"tint"} /-->' ), 'gekozen tint (klasse)', false );
$bevat( 'sfp-sectie--tint', do_blocks( '<!-- wp:sfp/artikelraster {"achtergrond":"tint"} /-->' ), 'gekozen tint' );
sfp_page_config_archief_gidsen_getoond( false );

/* ---------- Alfabet ---------- */
$is( 'A', sfp_page_config_archief_letter( 'Ademhaling' ), 'beginletter' );
$is( 'E', sfp_page_config_archief_letter( 'Éénheid' ), 'accent weg' );
$is( '', sfp_page_config_archief_letter( '3 tips' ), 'cijfer: geen letter' );
$_GET['letter'] = 'p';
$is( 'P', sfp_page_config_archief_gekozen_letter(), 'letter als hoofdletter' );
$_GET['letter'] = 'PP';
$is( '', sfp_page_config_archief_gekozen_letter(), 'twee letters: geen filter' );
unset( $_GET['letter'] );

/* ---------- Teksten in drie talen ---------- */
foreach ( array( 'archief_resultaten_voor', 'archief_alle_over', 'archief_pagina_van', 'archief_meer_over', 'contact_maps' ) as $k ) {
    $r = sfp_page_config_teksten_register()[ $k ];
    foreach ( array( 'nl', 'en', 'pt' ) as $taal ) {
        $is( true, '' !== (string) $r[ $taal ], "tekst $k in $taal" );
    }
}

/* ---------- Opruimen ---------- */
foreach ( $weg as $id ) {
    wp_delete_post( $id, true );
}
wp_delete_term( $catid, 'category' );
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $user );
update_option( 'sfp_settings', $oud );

echo ( $fouten ? "FOUTEN: $fouten van $checks\n" : "OK: $checks checks\n" );
