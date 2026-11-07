<?php
/**
 * Tests voor de NL-vorm (3.30.0): cursusdata met status, plaats en prijs,
 * Groepskaarten, held wit, USP-strook, de delen van nlvorm-*.css en het
 * Course-schema. Draait in een echte WordPress (een lokale testinstallatie,
 * nooit een live site). Maakt eigen pagina's en ruimt ze op.
 *
 * Draaien: wp eval-file wp-content/plugins/sfp-page-config/tests/nlvorm-wp.php
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

/* ---------- Cursusdata: status, plaats en prijs ---------- */
$schoon = sfp_page_config_cursusdata_schoon(
    array(
        array( 'data' => array( '2099-03-01' ), 'status' => 'vol' ),
        array( 'data' => array( '2099-01-01' ), 'status' => 'laatste' ),
        array( 'data' => array( '2099-02-01' ), 'status' => 'onzin' ),
    )
);
$is( 'laatste', $schoon[0]['status'], 'status laatste blijft bewaard' );
$is( false, isset( $schoon[1]['status'] ), 'onbekende status valt weg' );
$is( 'vol', $schoon[2]['status'], 'status vol blijft bewaard' );

$info = sfp_page_config_cursusinfo_schoon( array( 'plaats' => '  Utrecht <b>CS</b> ', 'prijs' => '€ 1.995,-' ) );
$is( 'Utrecht CS', $info['plaats'], 'plaats opgeschoond' );
$is( 1995, $info['prijs'], 'prijs in hele euro' );
$is( 0, sfp_page_config_cursusinfo_schoon( array() )['prijs'], 'geen prijs is 0' );

/* ---------- USP-strook ---------- */
$u = sfp_page_config_uspstrook_schoon( array( 'usps' => array( 'een', '', 'twee', 'drie', 'vier' ), 'telefoon' => '085 060 13 55<script>', 'soorten' => array( 'training', 'artikel' ) ) );
$is( array( 'een', 'twee', 'drie' ), $u['usps'], 'hoogstens drie redenen, lege weg' );
$is( '085 060 13 55', $u['telefoon'], 'telefoon alleen cijfers en spaties' );
$is( array( 'training' ), $u['soorten'], 'alleen bekende soorten' );
$is( array( 'training', 'coaching' ), sfp_page_config_uspstrook_schoon( array() )['soorten'], 'standaard trainingen en coaching' );

/* ---------- Delen van de opmaak ---------- */
$is( array( 'nlvorm-held' ), sfp_page_config_nlvorm_delen( '<!-- wp:sfp/held {"variant":"wit"} -->' ), 'held wit laadt het deel held' );
$is( array(), sfp_page_config_nlvorm_delen( '<!-- wp:sfp/held {"variant":"breed"} -->' ), 'held breed laadt niets' );
$is( array( 'nlvorm-inhoud', 'nlvorm-groepen', 'nlvorm-inschrijven' ), sfp_page_config_nlvorm_delen( '<!-- wp:sfp/lijst {"rij":true} --><!-- wp:sfp/groepskaarten /--><!-- wp:sfp/kolommen {"indeling":"inschrijven"} -->' ), 'drie delen naar de inhoud' );

/* ---------- Groepskaarten op een eigen pagina ---------- */
$pagina = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'NL-vorm test', 'post_content' => '<!-- wp:sfp/groepskaarten /-->' ) );
update_post_meta( $pagina, 'sfp_page_type', 'training' );
sfp_page_config_cursusdata_bewaar(
    $pagina,
    array(
        array( 'data' => array( '2099-01-08', '2099-01-15' ) ),
        array( 'data' => array( '2099-02-05' ), 'status' => 'vol' ),
        array( 'data' => array( '2000-01-01' ) ),
    )
);
sfp_page_config_cursusinfo_bewaar( $pagina, array( 'plaats' => 'Utrecht', 'prijs' => '1995' ) );
$GLOBALS['post'] = get_post( $pagina );
setup_postdata( $GLOBALS['post'] );
$html = sfp_page_config_render_groepskaarten( array() );
$is( 2, substr_count( $html, 'class="sfp-groep__kopregel"' ), 'twee toekomstige groepen, de oude niet' );
$is( 3, substr_count( $html, 'class="sfp-groep__dag"' ), 'dagen van groep 1 en 2: samen drie rijen' );
$bevat( 'data-sfp-groep="1"', $html, 'link kiest groep 1' );
$bevat( 'data-sfp-groep="2"', $html, 'volle groep heeft geen inschrijflink', false );
$bevat( 'sfp-groep--vol', $html, 'volle groep gemarkeerd' );
$bevat( 'data-sfp-groep="wachtlijst"', $html, 'wachtlijstregel aanwezig' );
$bevat( 'Utrecht', $html, 'plaats op de kaart' );

$schema = sfp_page_config_nlvorm_schema_data( $pagina, sfp_page_config_nlvorm_groepen( $pagina ), sfp_page_config_cursusinfo( $pagina ) );
$is( 'Course', $schema['@type'], 'Course-schema' );
$is( 2, count( $schema['hasCourseInstance'] ), 'een instantie per groep' );
$is( '2099-01-15', $schema['hasCourseInstance'][0]['endDate'], 'einddatum is de laatste dag' );
$is( 'https://schema.org/SoldOut', $schema['hasCourseInstance'][1]['offers']['availability'], 'volle groep is uitverkocht' );

/* ---------- Held wit: feitenkaart en bewijsregel ---------- */
$feit = sfp_page_config_nlvorm_feitenkaart( array( 'feiten' => array( array( 'label' => 'Waar', 'waarde' => 'Utrecht' ), array( 'label' => 'Prijs', 'waarde' => '' ) ) ) );
$is( 2, substr_count( $feit, '<strong>' ), 'volgende start plus één ingevuld feit' );
$bevat( 'sfp-lang', $feit, 'datum ingekort op een telefoon' );
$bewijs = sfp_page_config_nlvorm_bewijsregel( array( 'deelnemers' => 'meer dan 2.000 deelnemers', 'initialen' => 'JK MV SB XY' ) );
preg_match( '/<span class="sfp-held__koppen"[^>]*>(.*?)<\/span><\/span>/', $bewijs, $k );
$is( 3, isset( $k[1] ) ? substr_count( $k[1], '<span>' ) : 0, 'hoogstens drie initialen' );

/* ---------- Zonder geplande groep: wachtlijstkaart (3.31.0) ---------- */
// Eigen post_id: de toekomstige groepen worden per post_id onthouden.
$pagina2 = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'NL-vorm test zonder groep', 'post_content' => '<!-- wp:sfp/groepskaarten /-->' ) );
update_post_meta( $pagina2, 'sfp_page_type', 'training' );
sfp_page_config_cursusdata_bewaar( $pagina2, array( array( 'data' => array( '2000-01-01' ) ) ) );
$GLOBALS['post'] = get_post( $pagina2 );
setup_postdata( $GLOBALS['post'] );
$leeg = sfp_page_config_render_groepskaarten( array() );
$bevat( 'sfp-groep--wachtlijst', $leeg, 'wachtlijstkaart zonder groep' );
$bevat( 'class="sfp-groep__data"', $leeg, 'wachtlijstkaart zonder datumrijen', false );
$bevat( 'data-sfp-groep="wachtlijst"', $leeg, 'wachtlijstkaart linkt naar de wachtlijst' );
$is( true, sfp_page_config_nlvorm_alleen_wachtlijst( $pagina2 ), 'pagina met Groepskaarten zonder groep: alleen wachtlijst' );
$is( false, sfp_page_config_nlvorm_alleen_wachtlijst( $pagina ), 'pagina met groepen: niet alleen wachtlijst' );
wp_delete_post( $pagina2, true );

/* ---------- Opruimen ---------- */
wp_reset_postdata();
wp_delete_post( $pagina, true );

echo $fouten ? "$fouten fouten in $checks controles\n" : "OK: $checks checks\n";
