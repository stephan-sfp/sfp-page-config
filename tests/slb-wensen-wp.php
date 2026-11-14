<?php
/**
 * Tests voor 3.58.0 (wensen uit de SLB-bouw en het deelbeeld met eigen kop):
 * held gesplitst, bewijsregel in held recht met schakelaar, uitklaptekst per
 * blok in de verhaalvorm, afbeeldingsstijl Logo, deelbeeld van Wat je moet
 * weten met eigen kop en opnieuw bij een titelwijziging. Draait in een echte
 * WordPress (lokale testinstallatie, nooit een live site). Ruimt op.
 *
 * Draaien: wp eval-file wp-content/plugins/sfp-page-config/tests/slb-wensen-wp.php
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

/* ---------- Beeld voor de tests ---------- */
$pad = wp_upload_dir()['basedir'] . '/sfp-test-' . wp_rand() . '.png';
$im  = imagecreatetruecolor( 500, 200 );
imagepng( $im, $pad );
$beeld = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'logo', 'post_status' => 'inherit' ), $pad );
require_once ABSPATH . 'wp-admin/includes/image.php';
wp_update_attachment_metadata( $beeld, wp_generate_attachment_metadata( $beeld, $pad ) );
$weg[] = $beeld;
$img   = '<!-- wp:image {"id":' . $beeld . '} --><figure class="wp-block-image"><img src="' . wp_get_attachment_url( $beeld ) . '" alt="x" class="wp-image-' . $beeld . '"/></figure><!-- /wp:image -->';

/* ---------- 1. Held gesplitst ---------- */
$html = do_blocks( '<!-- wp:sfp/held {"variant":"gesplitst","mobielId":' . $beeld . ',"deelnemers":"2,500+ trained"} --><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Titel</h1><!-- /wp:heading -->' . $img . '<!-- /wp:sfp/held -->' );
$bevat( 'sfp-held--gesplitst', $html, 'gesplitst heeft zijn klasse' );
$is( 0, preg_match( '/<section[^>]*class="[^"]*sfp-held--foto/', $html ), 'gesplitst is geen held foto' );
$bevat( 'sfp-held__achter', $html, 'foto in een eigen vlak' );
$bevat( '<picture><source media="(max-width: 700px)"', $html, 'telefoonfoto als bron' );
$bevat( '--sfp-held-focus:50% 30%', $html, 'standaard focus midden, iets boven' );
$bevat( 'sfp-held__bewijs', $html, 'bewijsregel in gesplitst' );
$bevat( 'fetchpriority="high"', $html, 'foto met voorrang' );
$is( array( 'nlvorm-held', 'held-gesplitst' ), array_values( array_intersect( sfp_page_config_nlvorm_delen( '<!-- wp:sfp/held {"variant":"gesplitst"} -->' ), array( 'nlvorm-held', 'held-gesplitst' ) ) ), 'delen voor gesplitst' );
$css = sfp_page_config_blokken_css_bestand( 'held-gesplitst' );
$bevat( 'sfp-held--gesplitst', $css, 'CSS gesplitst bestaat' );
$bevat( '#', preg_replace( '/#[a-z0-9-]+\{/i', '', preg_replace( '#/\*.*?\*/#s', '', $css ) ), 'geen hexkleur in CSS gesplitst', false );

/* Held foto ongewijzigd */
$foto = do_blocks( '<!-- wp:sfp/held {"variant":"foto"} --><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Titel</h1><!-- /wp:heading -->' . $img . '<!-- /wp:sfp/held -->' );
$bevat( 'sfp-held--foto', $foto, 'foto houdt zijn klasse' );
$bevat( '--sfp-held-focus:68% 40%', $foto, 'foto houdt standaard focus' );

/* ---------- 2. Bewijsregel in held recht ---------- */
$recht = do_blocks( '<!-- wp:sfp/held {"deelnemers":"500 deelnemers"} --><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">T</h1><!-- /wp:heading --><!-- /wp:sfp/held -->' );
$bevat( 'sfp-held__bewijs', $recht, 'recht zonder schakelaar: geen bewijsregel', false );
$recht = do_blocks( '<!-- wp:sfp/held {"bewijs":true,"deelnemers":"500 deelnemers","initialen":"AB CD"} --><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">T</h1><!-- /wp:heading --><!-- /wp:sfp/held -->' );
$bevat( 'sfp-held__bewijs', $recht, 'recht met schakelaar: bewijsregel' );
$bevat( 'held-bewijs', $recht, 'recht met schakelaar: klasse' );
$bevat( '500 deelnemers', $recht, 'deelnemers in de regel' );
$is( true, in_array( 'nlvorm-held', sfp_page_config_nlvorm_delen( '<!-- wp:sfp/held {"bewijs":true} -->' ), true ), 'recht met bewijs laadt nlvorm-held' );
$is( false, in_array( 'nlvorm-held', sfp_page_config_nlvorm_delen( '<!-- wp:sfp/held {"variant":"breed"} -->' ), true ), 'breed zonder bewijs laadt nlvorm-held niet' );

/* ---------- 5. Uitklaptekst verhaalvorm ---------- */
$verhaal = function ( $extra ) use ( $beeld ) {
    $a = '';
    for ( $i = 1; $i <= 3; $i++ ) {
        $a .= '<!-- wp:paragraph --><p>Alinea ' . $i . '</p><!-- /wp:paragraph -->';
    }
    $b = '<!-- wp:image {"id":' . $beeld . ',"className":"is-style-sfp-afgerond"} --><figure class="wp-block-image is-style-sfp-afgerond"><img src="x.png" alt="" class="wp-image-' . $beeld . '"/></figure><!-- /wp:image -->';
    return do_blocks( '<!-- wp:sfp/kolommen {"indeling":"tekst-beeld"' . $extra . '} --><!-- wp:sfp/kolom -->' . $a . '<!-- /wp:sfp/kolom --><!-- wp:sfp/kolom -->' . $b . '<!-- /wp:sfp/kolom --><!-- /wp:sfp/kolommen -->' );
};
$v = $verhaal( '' );
$bevat( 'sfp-verhaal__knop', $v, 'verhaalvorm actief' );
$bevat( esc_html( sfp_page_config_tekst( 'verhaal_meer' ) ), $v, 'zonder eigen tekst: tekst van de site' );
$v = $verhaal( ',"meerTekst":"Read the full story"' );
$bevat( 'data-open="Read the full story"', $v, 'eigen tekst in data-open' );
$bevat( '>Read the full story<span', $v, 'eigen tekst op de knop' );

/* ---------- 6. Stijl Logo ---------- */
$logo = do_blocks( '<!-- wp:image {"id":' . $beeld . ',"className":"is-style-sfp-logo"} --><figure class="wp-block-image is-style-sfp-logo"><img src="x.png" alt="" class="wp-image-' . $beeld . '"/></figure><!-- /wp:image -->' );
$bevat( '--sfp-logo-b:', $logo, 'logo krijgt optische breedte' );
$bevat( '--sfp-logo-h:', $logo, 'logo krijgt optische hoogte' );
$geen = do_blocks( $img );
$bevat( '--sfp-logo-b:', $geen, 'gewone afbeelding blijft ongewijzigd', false );
$bevat( 'is-style-sfp-logo', sfp_page_config_blokken_css_bestand( 'kaart' ), 'opmaak Logo in kaart.css' );

/* ---------- 4. Ruimte alinea voor blok ---------- */
$bevat( 'p:not(.is-style-sfp-kernzin)+.sfp-kolommen{margin-top:32px}', sfp_page_config_blokken_css_bestand( 'kolommen' ), 'ruimte voor kolommen' );
$bevat( 'p:not(.is-style-sfp-kernzin)+.sfp-stappen{margin-top:32px}', sfp_page_config_blokken_css_bestand( 'stappen' ), 'ruimte voor stappen' );

/* ---------- Deelbeeld: eigen kop en titelwijziging ---------- */
if ( function_exists( 'sfp_page_config_deelbeeld_slots' ) ) {
    $sv  = '<!-- wp:sfp/samenvatting %s --><!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>Een</li><!-- /wp:list-item --><!-- wp:list-item --><li>Twee</li><!-- /wp:list-item --><!-- wp:list-item --><li>Drie</li><!-- /wp:list-item --></ul><!-- /wp:list --><!-- /wp:sfp/samenvatting -->';
    $pid = wp_insert_post( array( 'post_title' => 'Oude titel', 'post_status' => 'publish', 'post_content' => sprintf( $sv, '' ) ) );
    $weg[] = $pid;
    $s = sfp_page_config_deelbeeld_slots( get_post( $pid ) );
    $is( 'Oude titel', $s['samenvatting-1']['titel'], 'zonder kop: titel van het artikel' );
    $is( true, '' !== $s['samenvatting-1']['label'], 'zonder kop: label' );
    wp_update_post( array( 'ID' => $pid, 'post_content' => sprintf( $sv, '{"kop":"4 dingen om te weten over kaartjes"}' ) ) );
    $s = sfp_page_config_deelbeeld_slots( get_post( $pid ) );
    $is( '4 dingen om te weten over kaartjes', $s['samenvatting-1']['titel'], 'eigen kop als titel' );
    $is( '', $s['samenvatting-1']['label'], 'eigen kop zonder label' );
    $is( '4 dingen om te weten over kaartjes', $s['samenvatting-1']['alt'], 'alt is de eigen kop' );
    wp_update_post( array( 'ID' => $pid, 'post_title' => 'Nieuwe titel' ) );
    $s = sfp_page_config_deelbeeld_slots( get_post( $pid ) );
    $is( '4 dingen om te weten over kaartjes', $s['samenvatting-1']['titel'], 'eigen kop blijft bij titelwijziging' );
    if ( sfp_page_config_deelbeeld_kan() && '' === sfp_page_config_deelbeeld_voorwaarden()['reden'] ) {
        $b1 = get_post_meta( $pid, '_sfp_deelbeeld', true );
        wp_update_post( array( 'ID' => $pid, 'post_content' => sprintf( $sv, '{"kop":"Andere kop"}' ) ) );
        $b2 = get_post_meta( $pid, '_sfp_deelbeeld', true );
        $is( true, $b1['samenvatting-1']['hash'] !== $b2['samenvatting-1']['hash'], 'nieuwe kop maakt nieuw beeld' );
        wp_update_post( array( 'ID' => $pid, 'post_content' => sprintf( $sv, '' ) ) );
        $b3 = get_post_meta( $pid, '_sfp_deelbeeld', true );
        $u3 = wp_get_attachment_url( $b3['samenvatting-1']['id'] );
        wp_update_post( array( 'ID' => $pid, 'post_title' => 'Weer een titel' ) );
        $b4 = get_post_meta( $pid, '_sfp_deelbeeld', true );
        $u4 = wp_get_attachment_url( $b4['samenvatting-1']['id'] );
        $is( true, $b3['samenvatting-1']['hash'] !== $b4['samenvatting-1']['hash'], 'titelwijziging maakt nieuw beeld (zonder kop)' );
        $is( true, $u3 !== $u4, 'nieuw beeld heeft een nieuw adres' );
        $is( true, is_readable( get_attached_file( $b4['samenvatting-1']['id'] ) ), 'nieuw bestand staat er' );
        foreach ( array( $b1, $b2, $b3, $b4 ) as $b ) {
            $weg[] = (int) $b['samenvatting-1']['id'];
        }
    } else {
        echo "LET OP: deelbeeld niet getekend (server mist GD/FreeType of fonts); hashcontroles overgeslagen.\n";
    }
}

foreach ( array_unique( $weg ) as $id ) {
    'attachment' === get_post_type( $id ) ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true );
}
echo $fouten ? "MISLUKT: $fouten van $checks controles\n" : "OK: $checks controles\n";
