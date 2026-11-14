<?php
/**
 * Tests voor 3.65.0: bouwstenen na de DGA-ronde (overdracht WordPress Toolkit
 * 14-11-2026). Draait in een lokale WordPress, nooit op een live site.
 *
 * Draaien: wp eval-file wp-content/plugins/sfp-page-config/tests/dga-ronde-wp.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}
$fouten = 0;
$aantal = 0;
$check  = function ( $naam, $ok ) use ( &$fouten, &$aantal ) {
    ++$aantal;
    if ( ! $ok ) {
        ++$fouten;
        echo "FOUT: $naam\n";
    }
};

/* ---------- Reviewraster: volle rijen ---------- */
$check( 'raster 0 blijft 0', 0 === SFP_Reviews_Renderer::raster_aantal( 0 ) );
$check( 'raster 3 blijft 3', 3 === SFP_Reviews_Renderer::raster_aantal( 3 ) );
$check( 'raster 5 wordt 4', 4 === SFP_Reviews_Renderer::raster_aantal( 5 ) );
$check( 'raster 7 wordt 4', 4 === SFP_Reviews_Renderer::raster_aantal( 7 ) );
$check( 'raster 8 blijft 8', 8 === SFP_Reviews_Renderer::raster_aantal( 8 ) );
$check( 'raster 20 wordt 8', 8 === SFP_Reviews_Renderer::raster_aantal( 20 ) );

/* ---------- Reviews zonder echte naam ---------- */
$check( 'Cursist telt niet', ! sfp_page_config_reviews_met_naam( array( 'author' => 'Cursist' ) ) );
$check( 'cursist klein telt niet', ! sfp_page_config_reviews_met_naam( array( 'author' => ' cursist ' ) ) );
$check( 'lege naam telt niet', ! sfp_page_config_reviews_met_naam( array( 'author' => '' ) ) );
$check( 'Anônimo telt niet', ! sfp_page_config_reviews_met_naam( array( 'author' => 'ANÔNIMO' ) ) );
$check( 'echte naam telt', sfp_page_config_reviews_met_naam( array( 'author' => 'Carlissa R.' ) ) );

$oud_eigen = get_option( SFP_Reviews_Manual::OPTION, null );
$lijst     = array();
foreach ( array( 'Anna B.', 'Cursist', 'Bram C.', 'Cees D.', 'Dirk E.', 'Eva F.' ) as $n ) {
    $lijst[] = SFP_Reviews_Manual::schoon( array( 'author' => $n, 'rating' => 5, 'text' => 'Goede training met veel oefening.' ) );
}
SFP_Reviews_Manual::save_all( $lijst );
$html = sfp_page_config_reviews_render( array( 'layout' => 'grid', 'manual_only' => 'true', 'min_stars' => 4 ) );
$check( 'raster: vijf met naam worden er vier', 4 === substr_count( $html, 'class="sfp-review"' ) );
$check( 'raster: geen Cursist', false === strpos( $html, '>Cursist<' ) );
$check( 'raster: kolommen 4', false !== strpos( $html, '--sfp-reviews-kolommen:4' ) );
SFP_Reviews_Manual::save_all( array_slice( $lijst, 0, 3 ) );
$html = sfp_page_config_reviews_render( array( 'layout' => 'grid', 'manual_only' => 'true', 'min_stars' => 4 ) );
$check( 'raster: twee met naam blijven twee, rij schaalt mee', 2 === substr_count( $html, 'class="sfp-review"' ) && false !== strpos( $html, '--sfp-reviews-kolommen:2' ) );
$meta = sfp_page_config_reviews_eigen_meta();
$check( 'gemiddelde telt de review zonder naam mee', 3 === (int) ( $meta['total'] ?? 0 ) );
if ( null === $oud_eigen ) {
    delete_option( SFP_Reviews_Manual::OPTION );
} else {
    update_option( SFP_Reviews_Manual::OPTION, $oud_eigen, false );
}

/* ---------- Laatste artikelen: standaard met samenvatting ---------- */
$type = WP_Block_Type_Registry::get_instance()->get_registered( 'sfp/artikelen' );
$check( 'artikelen: uittreksel standaard aan', $type && true === $type->attributes['uittreksel']['default'] );
$p    = wp_insert_post(
    array(
        'post_title'   => 'Test samenvatting',
        'post_status'  => 'publish',
        'post_content' => '<!-- wp:paragraph --><p>Dit is de openingszin van het artikel.</p><!-- /wp:paragraph -->',
    )
);
$html = do_blocks( '<!-- wp:sfp/artikelen {"aantal":1} /-->' );
$check( 'artikelen: samenvatting in de kaart', false !== strpos( $html, 'sfp-artikel__uittreksel' ) && false !== strpos( $html, 'openingszin' ) );
$html = do_blocks( '<!-- wp:sfp/artikelen {"aantal":1,"uittreksel":false} /-->' );
$check( 'artikelen: uit te zetten per blok', false === strpos( $html, 'sfp-artikel__uittreksel' ) );
wp_delete_post( $p, true );

/* ---------- Boekkaart laadt de opmaak van de coachingpagina ---------- */
$check( 'boeking in de NL-vormdelen', in_array( 'nlvorm-inschrijven', sfp_page_config_nlvorm_delen( '<!-- wp:sfp/kolommen {"indeling":"boeking"} -->' ), true ) );

/* ---------- CSS ---------- */
$css = function ( $b ) {
    return (string) file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/' . $b );
};
$check( 'slot: foto ook in picture', false !== strpos( $css( 'blokken/nlvorm-slot.css' ), '.is-style-sfp-wie>:is(img,picture){grid-column:1;grid-row:1/span 2' ) );
$check( 'slot: zonder foto volle breedte', false !== strpos( $css( 'blokken/nlvorm-slot.css' ), '.is-style-sfp-wie:not(:has(img)){grid-template-columns:minmax(0,1fr)}' ) );
$check( 'kaart: accentrand op vlak en klikbaar', false !== strpos( $css( 'blokken/kaart.css' ), '.sfp-kaart--vlak.sfp-kaart--link{border-top:4px solid var(--sfp-b-secundair)}' ) );
$check( 'kaart: knop onderin', false !== strpos( $css( 'blokken/nlvorm-home.css' ), '.sfp-kaart__tekst>:is(.is-style-sfp-pijl,.wp-block-buttons):last-child{margin-top:auto' ) );
$check( 'calculator: knop zonder onderstreping', false !== strpos( $css( 'calculator/calculator.css' ), ':root .sfp-calc a.sfp-calc-knop' ) );
$check( 'portfolio: logo in picture', false !== strpos( $css( 'blokken/incompany.css' ), '.sfp-tablijst picture{display:block;height:44px' ) );
$check( 'portfolio: kernzin 18 px', false !== strpos( $css( 'blokken/incompany.css' ), '.is-style-sfp-kernzin{font-size:18px}' ) );
$check( 'geen hexkleur toegevoegd', ! preg_match( '/#[0-9a-f]{6}/i', $css( 'blokken/incompany.css' ) . $css( 'blokken/nlvorm-slot.css' ) ) );

echo ( $fouten ? "$fouten van $aantal controles mislukt\n" : "OK: $aantal controles\n" );
