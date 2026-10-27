<?php
/**
 * Tests voor de Zelftest-oproep (3.6.0): de gedeelde bron, het blok, de code
 * en de zelftest op de pijlerkaart.
 *
 * Draait in een lokale WordPress (nooit op een live site):
 *   wp eval-file wp-content/plugins/sfp-page-config/tests/zelftest-cta-wp.php
 *
 * Zet een klein nep-pakket "cta-proef" neer (alleen manifest en inhoud), een
 * testpagina, een pijler en een tool, en ruimt alles op.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$fouten = 0;
$goed   = 0;
$check  = function ( $naam, $ok ) use ( &$fouten, &$goed ) {
    if ( $ok ) {
        ++$goed;
        return;
    }
    ++$fouten;
    echo "FOUT: $naam\n";
};

if ( sfp_zelftest_tests() ) {
    echo "Deze test verwacht een site zonder ingelezen zelftest.\n";
    return;
}

$bewaard_inst = get_option( 'sfp_settings', array() );
$bewaard_zt   = get_option( 'sfp_zelftest', null );
$s            = is_array( $bewaard_inst ) ? $bewaard_inst : array();
$s['artikel_aan']              = '1';
$s['onderdelen']               = isset( $s['onderdelen'] ) && is_array( $s['onderdelen'] ) ? $s['onderdelen'] : array();
$s['onderdelen']['pijler']     = '1';
$s['onderdelen']['zelftest']   = '1';
update_option( 'sfp_settings', $s );
if ( function_exists( 'sfp_page_config_instellingen_vergeet' ) ) {
    sfp_page_config_instellingen_vergeet();
}

// Nep-pakket.
$map = sfp_zelftest_map( 'pakket/cta-proef/huidig' );
wp_mkdir_p( $map );
file_put_contents( $map . 'manifest.json', wp_json_encode( array( 'test' => 'cta-proef', 'pakket_versie' => 'x', 'talen' => array( 'en', 'nl' ), 'lengtes' => array( 'long' => array( 'vragen' => array( 1, 2, 3 ) ), 'short' => array( 'vragen' => array( 1 ) ) ) ) ) );
file_put_contents( $map . 'inhoud.json', wp_json_encode( array( 'algemeen' => array( 'testnaam' => array( 'en' => 'Speaker test', 'nl' => 'Sprekerstest' ) ), 'vragen' => array() ) ) );
sfp_zelftest_pakket( '', true );

$opruimen = array();
$pagina   = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Testpagina', 'post_name' => 'proef-testpagina', 'post_status' => 'draft' ) );
$training = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Training', 'post_name' => 'proef-training', 'post_status' => 'publish' ) );
$opruimen = array( $pagina, $training );
sfp_zelftest_instellingen_bewaar( 'cta-proef', array( 'testpagina' => $pagina ) );

$check( 'testpagina concept: geen test van de site', '' === sfp_zelftest_site_test() );
$check( 'testpagina concept: geen oproep', null === sfp_zelftest_cta_gegevens() );

wp_update_post( array( 'ID' => $pagina, 'post_status' => 'publish' ) );
$check( 'test van de site', 'cta-proef' === sfp_zelftest_site_test() );
$g = sfp_zelftest_cta_gegevens( '', 'Pagina Training!' );
$check( 'gegevens', is_array( $g ) && 'cta-proef' === $g['test'] );
$check( 'kop standaard de naam', is_array( $g ) && in_array( $g['kop'], array( 'Speaker test', 'Sprekerstest' ), true ) );
$check( 'knop standaard vaste tekst', is_array( $g ) && $g['knop'] === sfp_page_config_artikel_tekst( 'doe_zelftest' ) );
$check( 'bronlabel schoon in de link', is_array( $g ) && false !== strpos( $g['url'], 'bron=pagina-training' ) && 0 === strpos( $g['url'], get_permalink( $pagina ) ) );
$check( 'aantal vragen eerste lengte', is_array( $g ) && 3 === $g['vragen'] );
$check( 'onbekende test', null === sfp_zelftest_cta_gegevens( 'bestaat-niet' ) );

sfp_zelftest_instellingen_bewaar( 'cta-proef', array( 'cta_kop' => 'How strong is your presence?', 'cta_zin' => '<b>Twelve</b> questions.', 'cta_knop' => str_repeat( 'x', 80 ) ) );
$g = sfp_zelftest_cta_gegevens();
$check( 'eigen kop', 'How strong is your presence?' === $g['kop'] );
$check( 'zin zonder html', 'Twelve questions.' === $g['zin'] );
$check( 'knop afgekapt op 40', 40 === strlen( $g['knop'] ) );
sfp_zelftest_instellingen_bewaar( 'cta-proef', array( 'cta_knop' => '' ) );

// Blok en code.
$GLOBALS['post'] = get_post( $training );
setup_postdata( $GLOBALS['post'] );
$html = render_block( array( 'blockName' => 'sfp/zelftest-cta', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) );
$check( 'blok toont oproep', false !== strpos( $html, 'sfp-zt-cta' ) && false !== strpos( $html, 'How strong is your presence?' ) );
$check( 'blok standaard bron', false !== strpos( $html, 'bron=pagina-proef-training' ) );
$check( 'blok knop van het thema', false !== strpos( $html, 'wp-block-button__link' ) );
$check( 'blok standaard zonder icoon (3.13.0)', false === strpos( $html, '<span class="sfp-zt-cta__icoon"' ) && ! preg_match( '/<aside[^>]*sfp-zt-cta--icoon/', $html ) );
$html = render_block( array( 'blockName' => 'sfp/zelftest-cta', 'attrs' => array( 'icoon' => true ), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) );
$check( 'blok met icoon als instelling (3.13.0)', false !== strpos( $html, '<span class="sfp-zt-cta__icoon"' ) && preg_match( '/<aside[^>]*sfp-zt-cta--icoon/', $html ) );
$html = do_shortcode( '[sfp_zelftest_cta bron="footer"]' );
$check( 'code', false !== strpos( $html, 'bron=footer' ) );
$check( 'code zonder icoon (3.13.0)', false === strpos( $html, '<span class="sfp-zt-cta__icoon"' ) );
$check( 'code met icoon="ja" (3.13.0)', false !== strpos( do_shortcode( '[sfp_zelftest_cta icoon="ja"]' ), '<span class="sfp-zt-cta__icoon"' ) );
$css = sfp_page_config_blokken_css_bestand( 'zelftest-cta' );
$check( 'oproep-CSS zonder fontnaam of hexkleur (3.13.0)', ! preg_match( '/#[0-9a-f]{3,6}\b|Roboto|Nunito|Manrope/i', $css ) );
$check( 'oproep-CSS kop in kapitalen en kopfont, max 760 px (3.13.0)', false !== strpos( $css, 'text-transform:uppercase;color:var(--sfp-b-primair)' ) && false !== strpos( $css, 'max-width:760px' ) && false !== strpos( $css, 'font-family:var(--sfp-b-kop)' ) );
if ( post_type_exists( 'tool' ) ) {
    $tool = wp_insert_post( array( 'post_type' => 'tool', 'post_title' => 'Proeftool', 'post_status' => 'publish' ) );
    $opruimen[] = $tool;
    $GLOBALS['post'] = get_post( $tool );
    setup_postdata( $GLOBALS['post'] );
    $check( 'niet op een toolpagina', '' === do_shortcode( '[sfp_zelftest_cta]' ) );
}
wp_reset_postdata();
$check( 'vervolg laat het blok toe', in_array( 'sfp/zelftest-cta', sfp_page_config_vervolg_blokken( true, (object) array( 'post' => (object) array( 'post_type' => 'sfp_vervolg' ) ) ), true ) );

// Pijler.
$term = wp_insert_term( 'Proef pijler ' . wp_generate_password( 6, false ), 'category' );
$gids = wp_insert_post( array( 'post_title' => 'Proefgids', 'post_status' => 'publish', 'post_category' => array( $term['term_id'] ) ) );
$opruimen[] = $gids;
update_post_meta( $gids, 'sfp_vorm', 'pijler' );
delete_transient( 'sfp_pc_gidsen' );
$kaart = sfp_page_config_artikel_promokaart( 'zij', $gids );
$check( 'pijler: zelftest op de plek van het boek', false !== strpos( $kaart, 'sfp-art-promo--test' ) && false !== strpos( $kaart, 'bron=pijler-promo' ) && false !== strpos( $kaart, 'How strong is your presence?' ) );
$check( 'pijler: kopkaart-bron', false !== strpos( sfp_page_config_pijler_zelftest( $gids, 'pijler-kop' )['url'], 'bron=pijler-kop' ) );
update_post_meta( $gids, 'sfp_zelftest', '-' );
$check( 'pijler zonder zelftest: geen testkaart', false === strpos( sfp_page_config_artikel_promokaart( 'zij', $gids ), 'sfp-art-promo--test' ) );
update_post_meta( $gids, 'sfp_zelftest', 'cta-proef' );
$check( 'pijler met gekozen test', null !== sfp_page_config_pijler_zelftest( $gids ) );

// Onderdeel uit.
$s = get_option( 'sfp_settings' );
$s['onderdelen']['zelftest'] = '0';
update_option( 'sfp_settings', $s );
if ( function_exists( 'sfp_page_config_instellingen_vergeet' ) ) {
    sfp_page_config_instellingen_vergeet();
}
$check( 'onderdeel uit: geen oproep', null === sfp_zelftest_cta_gegevens() );
$check( 'onderdeel uit: pijler geen testkaart', false === strpos( sfp_page_config_artikel_promokaart( 'zij', $gids ), 'sfp-art-promo--test' ) );

// Opruimen.
foreach ( $opruimen as $id ) {
    wp_delete_post( $id, true );
}
wp_delete_term( $term['term_id'], 'category' );
foreach ( array( 'manifest.json', 'inhoud.json' ) as $f ) {
    wp_delete_file( $map . $f );
}
@rmdir( $map ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
@rmdir( dirname( $map ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
update_option( 'sfp_settings', $bewaard_inst );
if ( null === $bewaard_zt ) {
    delete_option( 'sfp_zelftest' );
} else {
    update_option( 'sfp_zelftest', $bewaard_zt );
}
delete_transient( 'sfp_pc_gidsen' );

echo ( $fouten ? "MISLUKT: $fouten van " . ( $fouten + $goed ) : "OK: $goed controles" ) . "\n";
