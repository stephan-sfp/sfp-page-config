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

/* ---------- USP-strook per pagina (3.32.0) ---------- */
sfp_page_config_instellingen_bewaar( array( 'uspstrook' => array( 'usps' => array( 'Site een', 'Site twee', 'Site drie' ), 'telefoon' => '085 060 13 55', 'soorten' => array( 'training' ) ) ) );
$up = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'USP test' ) );
update_post_meta( $up, 'sfp_page_type', 'training' );
$is( array( 'Site een', 'Site twee', 'Site drie' ), sfp_page_config_uspstrook_pagina( $up )['usps'], 'zonder eigen redenen: de set van de site' );
update_post_meta( $up, 'sfp_usp_1', 'Eigen een' );
update_post_meta( $up, 'sfp_usp_3', 'Eigen drie' );
$is( array( 'Eigen een', 'Eigen drie' ), sfp_page_config_uspstrook_pagina( $up )['usps'], 'eigen redenen van de pagina, lege overgeslagen' );
$is( '085 060 13 55', sfp_page_config_uspstrook_pagina( $up )['telefoon'], 'belknop blijft van de site' );
update_post_meta( $up, 'sfp_usp_uit', '1' );
$is( null, sfp_page_config_uspstrook_pagina( $up ), 'strook uit op deze pagina' );
delete_post_meta( $up, 'sfp_usp_uit' );
update_post_meta( $up, 'sfp_page_type', 'coaching' );
$is( array( 'Eigen een', 'Eigen drie' ), sfp_page_config_uspstrook_pagina( $up )['usps'], 'eigen redenen ook als de soort in SFP Beheer niet aanstaat' );
delete_post_meta( $up, 'sfp_usp_1' );
delete_post_meta( $up, 'sfp_usp_3' );
$is( null, sfp_page_config_uspstrook_pagina( $up ), 'soort niet aan en geen eigen redenen: geen strook' );
update_post_meta( $up, 'sfp_usp_2', str_repeat( 'x', 80 ) );
$is( 60, mb_strlen( get_post_meta( $up, 'sfp_usp_2', true ) ), 'eigen reden hoogstens 60 tekens' );
wp_delete_post( $up, true );
sfp_page_config_instellingen_bewaar( array( 'uspstrook' => array() ) );

/* ---------- H1 in de held wit krijgt h1-xl (3.32.0) ---------- */
$is( '<h1 class="wp-block-heading h1-xl">Kop</h1>', sfp_page_config_nlvorm_h1_xl( '<h1 class="wp-block-heading">Kop</h1>' ), 'klasse h1-xl erbij' );
$is( '<h1 class="wp-block-heading h1-xl">Kop</h1>', sfp_page_config_nlvorm_h1_xl( '<h1 class="wp-block-heading h1-xl">Kop</h1>' ), 'h1-xl niet dubbel' );
$is( '<h1 class="h1-xl" id="x">Kop</h1>', sfp_page_config_nlvorm_h1_xl( '<h1 id="x">Kop</h1>' ), 'H1 zonder klasse krijgt er een' );
$is( '<p class="h1">x</p>', sfp_page_config_nlvorm_h1_xl( '<p class="h1">x</p>' ), 'geen H1: niets' );

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

/* ---------- Coachpagina (3.31.0): labelnaam, pakketten, Service ---------- */
$p = sfp_page_config_nlvorm_labelnaam( '<p class="is-style-sfp-wie">Oprichter van [label]</p>', array( 'blockName' => 'core/paragraph', 'attrs' => array( 'className' => 'is-style-sfp-wie' ) ) );
$bevat( get_bloginfo( 'name' ), $p, 'labelnaam vervangen door de sitenaam' );
$bevat( '[label]', $p, 'geen [label] meer over', false );
$coach = '<!-- wp:sfp/kolommen {"indeling":"pakketten"} --><!-- wp:sfp/kolom --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Spoed</h3><!-- /wp:heading --><!-- wp:paragraph {"className":"is-style-sfp-omvang"} --><p class="is-style-sfp-omvang">2 tot 4 sessies</p><!-- /wp:paragraph --><!-- /wp:sfp/kolom --><!-- wp:sfp/kolom --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Intensief</h3><!-- /wp:heading --><!-- /wp:sfp/kolom --><!-- /wp:sfp/kolommen -->';
$pk = sfp_page_config_nlvorm_pakketten( $coach );
$is( 2, count( $pk ), 'twee pakketten gelezen' );
$pagina3 = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Coaching test', 'post_content' => $coach ) );
$sd = sfp_page_config_nlvorm_service_data( $pagina3, $coach );
$is( 'Service', isset( $sd['@type'] ) ? $sd['@type'] : '', 'Service-schema' );
$is( 2, isset( $sd['offers'] ) ? count( $sd['offers'] ) : 0, 'een aanbod per pakket' );
$is( array(), sfp_page_config_nlvorm_service_data( $pagina3, '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->' ), 'zonder pakketten geen Service' );
update_post_meta( $pagina3, 'sfp_page_type', 'coaching' );
$is( true, sfp_page_config_nlvorm_eigen_aanbod( $pagina3 ), 'coachpagina met Pakketten: eigen aanbod' );
wp_delete_post( $pagina3, true );

/* ---------- Coach en SureRank (3.31.1) ---------- */
$is( 'https://stephanborggreve.com/#person', sfp_page_config_coach_id(), 'coach standaard' );
sfp_page_config_instellingen_bewaar( array( 'coach_persoon' => 'https://voorbeeld.test/over/' ) );
$is( 'https://voorbeeld.test/#person', sfp_page_config_coach_id(), 'coach ingesteld' );
sfp_page_config_instellingen_bewaar( array( 'coach_persoon' => 'https://stephanborggreve.com' ) );
$is( '', (string) sfp_page_config_get_setting( 'coach_persoon', '' ), 'de norm wordt leeg bewaard' );
sfp_page_config_instellingen_bewaar( array( 'coach_persoon' => '' ) );
$is( true, sfp_page_config_nlvorm_eigen_aanbod( $pagina ), 'trainingspagina met groepen: eigen aanbod' );
$is( false, sfp_page_config_nlvorm_eigen_aanbod( $pagina2 ), 'verwijderde pagina zonder groepen: geen eigen aanbod' );
$sr  = '<meta name="x" content="y"><script type="application/ld+json" id="surerank-schema">{"@context":"https://schema.org","@graph":[{"@type":"WebPage","@id":"a"},{"@type":"Course","name":"Oud"},{"@type":["Service"],"name":"Oud"}]}</script>';
$uit = sfp_page_config_nlvorm_zonder_aanbod( $sr );
$bevat( '"Course"', $uit, 'Course van SureRank weg', false );
$bevat( 'Service', $uit, 'Service van SureRank weg', false );
$bevat( '"WebPage"', $uit, 'de rest van SureRank blijft' );
$bevat( '<meta name="x" content="y">', $uit, 'andere uitvoer van SureRank blijft letterlijk' );
$alleen = '<script type="application/ld+json" id="surerank-schema">{"@graph":[{"@type":"Course"}]}</script>';
$is( '', sfp_page_config_nlvorm_zonder_aanbod( $alleen ), 'alleen een Course: geen leeg script' );
$zonder = '<script type="application/ld+json" id="surerank-schema">{"@graph":[{"@type":"WebPage"}]}</script>';
$is( $zonder, sfp_page_config_nlvorm_zonder_aanbod( $zonder ), 'zonder aanbod letterlijk gelijk' );

/* ---------- Editorvoorbeeld (3.31.3) ---------- */
$vs = sfp_page_config_nlvorm_volgende_start( $pagina );
$is( true, is_array( $vs ) && '' !== $vs['waarde'] && false !== strpos( $vs['waarde'], 'jan' ), 'volgende start voor de editor: eerste groep die niet vol is' );

/* ---------- Opruimen ---------- */
wp_reset_postdata();
wp_delete_post( $pagina, true );

echo $fouten ? "$fouten fouten in $checks controles\n" : "OK: $checks checks\n";
