<?php
/**
 * Tests voor Vervolg per pijler in [dynamische_cta] (3.21.0) in een lokale
 * WordPress (nooit een live site):
 *   wp eval-file wp-content/plugins/sfp-page-config/tests/vervolg-cta-wp.php
 *
 * Maakt pijlers, berichten en vervolg-berichten en een stand-in voor de
 * Astra-layouts aan de actie dynamische_cta. Controleert dat een bericht
 * met een vervolg het vervolg toont in plaats van de layouts, dat de
 * layouts blijven bij een pijler zonder vervolg, op een begrip, op een
 * pagina en bij een concept, dat een subpijler het vervolg van de pijler
 * erboven krijgt, dat het vervolg er niet dubbel staat als het onderdeel
 * Artikelen aan staat, en dat een vervolg met de code erin zichzelf niet
 * herhaalt. Elk scenario heeft een eigen bericht (caches op post_id).
 * Ruimt alles op.
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

$check( 'code bestaat', shortcode_exists( 'dynamische_cta' ) );
$check( 'code is van de plugin', isset( $GLOBALS['shortcode_tags']['dynamische_cta'] ) && 'sfp_page_config_code_dynamisch' === $GLOBALS['shortcode_tags']['dynamische_cta'] );

$bewaard = get_option( 'sfp_settings', array() );
$s       = is_array( $bewaard ) ? $bewaard : array();
$zet_artikel = function ( $aan ) use ( $s ) {
    $x                = $s;
    $x['artikel_aan'] = $aan ? '1' : '';
    update_option( 'sfp_settings', $x );
};
$zet_artikel( false );

// De oude layouts: wat Astra nu aan de actie hangt.
$oud = function () {
    echo '<div class="oude-layout">OUDE-LAYOUT</div>';
};
add_action( 'dynamische_cta', $oud );
delete_transient( 'sfp_pc_vervolg_layouts' );

$maak_term = function ( $naam, $ouder = 0 ) {
    $r = wp_insert_term( $naam . ' ' . wp_generate_password( 6, false ), 'category', array( 'parent' => $ouder ) );
    return is_wp_error( $r ) ? 0 : (int) $r['term_id'];
};
$pijler_met   = $maak_term( 'Pijler met vervolg' );
$pijler_zonder = $maak_term( 'Pijler zonder vervolg' );
$sub          = $maak_term( 'Subpijler', $pijler_met );
$pijler_concept = $maak_term( 'Pijler met concept' );
$pijler_zelf  = $maak_term( 'Pijler met code in vervolg' );
$termen       = array( $pijler_met, $pijler_zonder, $sub, $pijler_concept, $pijler_zelf );
$check( 'pijlers gemaakt', 5 === count( array_filter( $termen ) ) );

$berichten = array();
$vervolg_maak = function ( $term_id, $inhoud, $status ) use ( &$berichten ) {
    $id = sfp_page_config_vervolg_maak( $term_id, $inhoud, $status );
    $berichten[] = (int) $id;
    return (int) $id;
};
$inhoud_vervolg = '<!-- wp:sfp/vervolg --><!-- wp:sfp/vervolg-vlak {"label":"Volgende stap"} --><!-- wp:paragraph --><p>VERVOLG-TEKST</p><!-- /wp:paragraph --><!-- /wp:sfp/vervolg-vlak --><!-- /wp:sfp/vervolg -->';
$v1 = $vervolg_maak( $pijler_met, $inhoud_vervolg, 'publish' );
$v2 = $vervolg_maak( $pijler_concept, $inhoud_vervolg, 'draft' );
$v3 = $vervolg_maak( $pijler_zelf, '<!-- wp:paragraph --><p>ZELF</p><!-- /wp:paragraph --><!-- wp:shortcode -->[dynamische_cta]<!-- /wp:shortcode -->', 'publish' );
$check( 'vervolgen gemaakt', $v1 && $v2 && $v3 );

$bericht = function ( $type, $term = 0 ) use ( &$berichten ) {
    $id = wp_insert_post(
        array(
            'post_type'     => $type,
            'post_status'   => 'publish',
            'post_title'    => 'CTA-test ' . wp_generate_password( 6, false ),
            'post_content'  => '<!-- wp:paragraph --><p>Tekst.</p><!-- /wp:paragraph --><!-- wp:shortcode -->[dynamische_cta]<!-- /wp:shortcode -->',
            'post_category' => $term ? array( $term ) : array(),
        )
    );
    $berichten[] = (int) $id;
    return (int) $id;
};
// Toon een bericht zoals een bezoeker: de hoofdquery staat op dat bericht.
$toon = function ( $id ) {
    global $wp_query, $post;
    $type = get_post_type( $id );
    $wp_query = new WP_Query( 'page' === $type ? array( 'page_id' => $id ) : array( 'p' => $id, 'post_type' => $type ) );
    $GLOBALS['wp_the_query'] = $wp_query;
    $wp_query->the_post();
    $post = get_post( $id );
    setup_postdata( $post );
    $uit = do_shortcode( '[dynamische_cta]' );
    wp_reset_postdata();
    return $uit;
};

// 1. Pijler met vervolg, Artikelen uit: het vervolg, niet de layouts.
$uit = $toon( $bericht( 'post', $pijler_met ) );
$check( 'met vervolg: vervolg getoond', false !== strpos( $uit, 'VERVOLG-TEKST' ), substr( $uit, 0, 120 ) );
$check( 'met vervolg: geen oude layout', false === strpos( $uit, 'OUDE-LAYOUT' ) );
$check( 'met vervolg: blok Vervolg met opmaak', false !== strpos( $uit, 'sfp-vervolg' ) );

// 2. Pijler zonder vervolg: de oude layout.
$uit = $toon( $bericht( 'post', $pijler_zonder ) );
$check( 'zonder vervolg: oude layout', false !== strpos( $uit, 'OUDE-LAYOUT' ) && false === strpos( $uit, 'VERVOLG-TEKST' ) );

// 3. Subpijler: het vervolg van de pijler erboven.
$uit = $toon( $bericht( 'post', $sub ) );
$check( 'subpijler: vervolg van de bovenliggende pijler', false !== strpos( $uit, 'VERVOLG-TEKST' ) && false === strpos( $uit, 'OUDE-LAYOUT' ) );

// 4. Concept-vervolg telt niet.
$uit = $toon( $bericht( 'post', $pijler_concept ) );
$check( 'concept-vervolg: oude layout', false !== strpos( $uit, 'OUDE-LAYOUT' ) && false === strpos( $uit, 'VERVOLG-TEKST' ) );

// 5. Vervolg met de code erin: één keer, geen herhaling.
$uit = $toon( $bericht( 'post', $pijler_zelf ) );
$check( 'code in vervolg: vervolg één keer', 1 === substr_count( $uit, 'ZELF' ), (string) substr_count( $uit, 'ZELF' ) );
$check( 'code in vervolg: oude layout binnen het vervolg', false !== strpos( $uit, 'OUDE-LAYOUT' ) );

// 6. Pagina: de oude layout.
$uit = $toon( $bericht( 'page' ) );
$check( 'pagina: oude layout', false !== strpos( $uit, 'OUDE-LAYOUT' ) && false === strpos( $uit, 'VERVOLG-TEKST' ) );

// 7. Begrip: de oude layout, ook als het begrip een pijler heeft.
if ( ! post_type_exists( 'begrippen' ) ) {
    register_post_type( 'begrippen', array( 'public' => true, 'taxonomies' => array( 'category' ) ) );
}
$begrip = $bericht( 'begrippen' );
update_post_meta( $begrip, 'sfp_pijler', $pijler_met );
$uit = $toon( $begrip );
$check( 'begrip: oude layout', false !== strpos( $uit, 'OUDE-LAYOUT' ) && false === strpos( $uit, 'VERVOLG-TEKST' ) );

// 8. Artikelen aan: het vervolg staat onder het artikel, dus de code geeft niets.
$zet_artikel( true );
$uit = $toon( $bericht( 'post', $pijler_met ) );
$check( 'artikelen aan, met vervolg: niets', '' === trim( $uit ), substr( $uit, 0, 120 ) );
$uit = $toon( $bericht( 'post', $pijler_zonder ) );
$check( 'artikelen aan, zonder vervolg: oude layout', false !== strpos( $uit, 'OUDE-LAYOUT' ) );
$zet_artikel( false );

// 9. Buiten een bericht (archief, zonder hoofdquery op een bericht): de oude layout.
global $wp_query;
$wp_query = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 1 ) );
$GLOBALS['wp_the_query'] = $wp_query;
$check( 'archief: oude layout', false !== strpos( do_shortcode( '[dynamische_cta]' ), 'OUDE-LAYOUT' ) );
wp_reset_query();

// 10. De andere twee codes blijven de actie volgen.
add_action( 'dynamische_pijlerlink', function () { echo 'PIJLERLINK'; } );
$id = $bericht( 'post', $pijler_met );
$wp_query = new WP_Query( array( 'p' => $id ) );
$GLOBALS['wp_the_query'] = $wp_query;
$wp_query->the_post();
$check( 'pijlerlink ongewijzigd', 'PIJLERLINK' === do_shortcode( '[dynamische_pijlerlink]' ) );
wp_reset_query();

// Opruimen.
remove_action( 'dynamische_cta', $oud );
foreach ( $berichten as $id ) {
    wp_delete_post( $id, true );
}
foreach ( array_reverse( $termen ) as $term ) {
    wp_delete_term( $term, 'category' );
}
update_option( 'sfp_settings', $bewaard );
delete_transient( 'sfp_pc_vervolg_layouts' );

echo $fouten ? "$fouten fouten, $goed goed\n" : "OK: $goed controles\n";
