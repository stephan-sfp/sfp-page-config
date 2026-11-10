<?php
/**
 * Tests voor de cursusvarianten (3.43.0): prijs per aantal dagen in
 * Cursusdata, de variantkeuze op de groepskaarten, de feitenkaart met Duur,
 * Tabs die de keuze volgen, de optietekst in het keuzemenu, het Course-schema
 * per variant en het blok Prijs van de training. Daarnaast: zonder prijs per
 * aantal dagen blijft alles zoals voor 3.43.0.
 *
 * Draait in een echte WordPress (een lokale testinstallatie, nooit een live
 * site). Maakt eigen pagina's en ruimt ze op. Elk scenario heeft een eigen
 * post_id: de groepen en varianten worden per post_id onthouden.
 *
 * Draaien: wp eval-file wp-content/plugins/sfp-page-config/tests/cursusvarianten-wp.php
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
$paginas = array();
$maak    = function ( $titel, $inhoud, $data, $info ) use ( &$paginas ) {
    $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $titel, 'post_content' => $inhoud ) );
    update_post_meta( $id, 'sfp_page_type', 'training' );
    sfp_page_config_cursusdata_bewaar( $id, $data );
    sfp_page_config_cursusinfo_bewaar( $id, $info );
    $GLOBALS['post'] = get_post( $id );
    setup_postdata( $GLOBALS['post'] );
    $paginas[] = $id;
    return $id;
};
$kaal = function ( $h ) {
    return (string) preg_replace( '#<style[^>]*>.*?</style>#s', '', (string) $h );
};
$held = array( 'feiten' => array( array( 'label' => 'Waar', 'waarde' => 'Utrecht' ), array( 'label' => 'Groep', 'waarde' => '4 tot 8' ) ) );
$tabs = '<!-- wp:sfp/tabs {"variant":true} --><!-- wp:sfp/tab {"titel":"3 dagen"} --><!-- wp:paragraph --><p>Drie</p><!-- /wp:paragraph --><!-- /wp:sfp/tab --><!-- wp:sfp/tab {"titel":"2 dagen"} --><!-- wp:paragraph --><p>Twee</p><!-- /wp:paragraph --><!-- /wp:sfp/tab --><!-- wp:sfp/tab {"titel":"1 dag"} --><!-- wp:paragraph --><p>Een</p><!-- /wp:paragraph --><!-- /wp:sfp/tab --><!-- /wp:sfp/tabs -->';

/* ---------- Opschonen ---------- */
$info = sfp_page_config_cursusinfo_schoon( array( 'prijs' => '1995', 'prijzen' => array( '3' => '€ 1.995', '1' => '995', '2' => '', '11' => '5', 'x' => '7' ) ) );
$is( array( '1' => 995, '3' => 1995 ), $info['prijzen'], 'prijzen: lege, onbekende en te grote aantallen weg, op volgorde' );
$is( array(), sfp_page_config_cursusinfo_schoon( array( 'prijs' => '1995' ) )['prijzen'], 'zonder prijzen een lege lijst' );
$is( 1995, sfp_page_config_cursus_prijs( $info, 3 ), 'prijs voor 3 dagen' );
$is( 1995, sfp_page_config_cursus_prijs( $info, 2 ), 'geen prijs voor 2 dagen: de gewone prijs' );
$is( 3, sfp_page_config_tab_dagen( '3 dagen' ), 'tab 3 dagen' );
$is( 1, sfp_page_config_tab_dagen( '<strong>1 dag</strong>' ), 'tab 1 dag met opmaak' );
$is( 2, sfp_page_config_tab_dagen( '2 days' ), 'tab 2 days' );
$is( 0, sfp_page_config_tab_dagen( 'Programma' ), 'tab zonder aantal dagen' );

/* ---------- Zonder prijs per aantal dagen: zoals voor 3.43.0 ---------- */
$a    = $maak(
    'Variant test zonder',
    '<!-- wp:sfp/groepskaarten /-->' . $tabs,
    array( array( 'data' => array( '2099-03-03', '2099-03-10', '2099-03-17' ) ), array( 'data' => array( '2099-04-07', '2099-04-14' ) ) ),
    array( 'plaats' => 'Utrecht', 'prijs' => '1995' )
);
$html = $kaal( sfp_page_config_render_groepskaarten( array() ) );
$bevat( 'sfp-variantkeuze', $html, 'zonder prijzen: geen keuze', false );
$bevat( 'data-sfp-variant', $html, 'zonder prijzen: geen variant op de kaart', false );
$bevat( '>Groep 2<', $html, 'zonder prijzen: doorlopende nummering' );
$is( 2, substr_count( $html, '1,995' ) + substr_count( $html, '1.995' ), 'zonder prijzen: de gewone prijs op beide kaarten' );
$bevat( 'sfp-groep__duur', $html, 'zonder prijzen: geen duur bij de prijs', false );
$feit = sfp_page_config_nlvorm_feitenkaart( $held );
$is( 3, substr_count( $feit, '<strong>' ), 'zonder prijzen: drie feiten, geen Duur' );
$bevat( 'data-sfp-feit', $feit, 'zonder prijzen: geen data-sfp-feit', false );
$t = $kaal( do_blocks( $tabs ) );
$bevat( 'sfp-tabs--variant', $t, 'zonder prijzen: Tabs volgen niets', false );
$bevat( 'data-sfp-dagen', $t, 'zonder prijzen: geen dagen op de tabs', false );
$schema = sfp_page_config_nlvorm_schema_data( $a, sfp_page_config_nlvorm_groepen( $a ), sfp_page_config_cursusinfo( $a ) );
$is( 'Offer', $schema['offers']['@type'], 'zonder prijzen: één Offer zoals voorheen' );
$bevat( 'courseWorkload', wp_json_encode( $schema ), 'zonder prijzen: geen courseWorkload', false );

/* ---------- Met prijzen en drie varianten ---------- */
sfp_page_config_instellingen_bewaar( array( 'cursus_tijd_default_start' => '09:00', 'cursus_tijd_default_eind' => '17:00', 'onderdelen' => array( 'cursusdata' => '1' ) ) );
$b    = $maak(
    'Variant test drie',
    '<!-- wp:sfp/groepskaarten /-->' . $tabs,
    array(
        array( 'data' => array( '2099-03-03', '2099-03-10', '2099-03-17' ), 'status' => 'vol' ),
        array( 'data' => array( '2099-03-20' ) ),
        array( 'data' => array( '2099-04-07', '2099-04-14' ) ),
        array( 'data' => array( '2099-05-12', '2099-05-19', '2099-05-26' ) ),
    ),
    array( 'plaats' => 'Utrecht', 'prijs' => '1995', 'prijzen' => array( '1' => '995', '2' => '1495', '3' => '1995' ) )
);
$v = sfp_page_config_cursus_varianten( $b );
$is( true, $v['aan'], 'varianten aan met prijzen' );
$is( array( 1, 2, 3 ), $v['lijst'], 'drie geplande varianten' );
$is( 1, $v['standaard'], 'standaard: de variant van de eerstvolgende groep die niet vol is' );
$html = $kaal( sfp_page_config_render_groepskaarten( array() ) );
$is( 3, substr_count( $html, 'role="radio"' ), 'keuze met drie knoppen' );
$bevat( 'data-sfp-variant="1" data-sfp-duur="1 dag"', $html, 'knop 1 dag met duur' );
preg_match_all( '/<button[^>]*aria-checked="true"/', $html, $m );
$is( 1, count( $m[0] ), 'één knop gekozen' );
$bevat( 'data-sfp-variant="1" data-sfp-duur="1 dag" data-sfp-start="', $html, 'knop draagt de start voor de feitenkaart' );
$is( 3, substr_count( $html, ' hidden>' ), 'drie kaarten van andere varianten verborgen (twee van 3 dagen, een van 2)' );
$is( 2, substr_count( $html, '>Groep 2<' ) + substr_count( $html, '>Groep 1<' ) - 2, 'nummering per variant: 3 dagen heeft Groep 1 en Groep 2' );
$bevat( '3 dagen</span> &bull; &euro;&nbsp;1', $html, 'prijsregel 3 dagen met bedrag' );
$bevat( '1 dag</span> &bull; &euro;&nbsp;995', $html, 'prijsregel 1 dag' );
$bevat( 'data-sfp-groep="4"', $html, 'link houdt het doorlopende groepsnummer voor het keuzemenu' );
$bevat( 'sfp-groepen--varianten', $html, 'omhulling gemarkeerd' );
$is( true, sfp_page_config_variant_script_nodig(), 'script van de keuze gemarkeerd' );

$feit = sfp_page_config_nlvorm_feitenkaart( $held );
$is( 4, substr_count( $feit, '<strong' ), 'feitenkaart met vier feiten' );
$bevat( 'data-sfp-feit="duur">1 dag<', $feit, 'Duur van de eerstvolgende groep' );
$bevat( 'sfp-held__feit--vier', $feit, 'vier feiten gemarkeerd' );
$bevat( '4 tot 8', $feit, 'Groep blijft staan' );

$t = $kaal( do_blocks( $tabs ) );
$bevat( 'sfp-tabs--variant', $t, 'Tabs volgen de keuze' );
$bevat( 'aria-selected="true" data-sfp-dagen="1"', $t, 'tab van de standaardvariant staat open' );
$bevat( 'aria-selected="false" tabindex="-1" data-sfp-dagen="3"', $t, 'tab 3 dagen dicht' );
preg_match_all( '/role="tabpanel"[^>]*>/', $t, $m );
$is( 2, substr_count( implode( '', $m[0] ), 'hidden' ), 'twee panelen verborgen' );

$schema = sfp_page_config_nlvorm_schema_data( $b, sfp_page_config_nlvorm_groepen( $b ), sfp_page_config_cursusinfo( $b ) );
$is( 3, count( $schema['offers'] ), 'Course: drie aanbiedingen' );
$is( '995', $schema['offers'][0]['price'], 'aanbod 1 dag 995' );
$is( '1 dag', $schema['offers'][0]['name'], 'aanbod heet 1 dag' );
$is( 4, count( $schema['hasCourseInstance'] ), 'vier instanties' );
$is( 'Groep 1 (3 dagen)', $schema['hasCourseInstance'][0]['name'], 'instantie: groep per variant met duur' );
$is( 'Groep 2 (3 dagen)', $schema['hasCourseInstance'][3]['name'], 'tweede groep van 3 dagen' );
$is( '1495', $schema['hasCourseInstance'][2]['offers']['price'], 'instantie 2 dagen: 1495' );
$is( 'PT24H', $schema['hasCourseInstance'][0]['courseWorkload'], 'lesuren: 3 x 8 uur' );
$is( 'https://schema.org/SoldOut', $schema['hasCourseInstance'][0]['offers']['availability'], 'volle groep uitverkocht' );

/* ---------- Eén geplande variant: geen keuze ---------- */
$c    = $maak(
    'Variant test een',
    '<!-- wp:sfp/groepskaarten /-->',
    array( array( 'data' => array( '2099-06-02', '2099-06-09' ) ) ),
    array( 'prijs' => '', 'prijzen' => array( '2' => '1495', '3' => '1995' ) )
);
$html = $kaal( sfp_page_config_render_groepskaarten( array() ) );
$bevat( 'sfp-variantkeuze', $html, 'één variant: geen keuze', false );
$is( 0, substr_count( $html, ' hidden' ), 'één variant: niets verborgen' );
$bevat( '2 dagen</span> &bull; &euro;&nbsp;1', $html, 'één variant: prijsregel 2 dagen' );

/* ---------- Prijzen zonder groep: wachtlijst en Course met aanbod ---------- */
$d    = $maak(
    'Variant test wachtlijst',
    '<!-- wp:sfp/groepskaarten /-->' . $tabs,
    array(),
    array( 'prijs' => '1995', 'prijzen' => array( '1' => '995', '2' => '1495', '3' => '1995' ) )
);
$html = $kaal( sfp_page_config_render_groepskaarten( array() ) );
$bevat( 'sfp-groep--wachtlijst', $html, 'zonder groep: wachtlijstkaart' );
$bevat( 'sfp-variantkeuze', $html, 'zonder groep: geen keuze', false );
$is( 3, sfp_page_config_cursus_varianten( $d )['standaard'], 'zonder groep: standaard 3 dagen' );
$feit = sfp_page_config_nlvorm_feitenkaart( $held );
$bevat( 'data-sfp-feit="duur"', $feit, 'zonder groep: geen Duur', false );
$t = $kaal( do_blocks( $tabs ) );
$bevat( 'aria-selected="true" data-sfp-dagen="3"', $t, 'zonder groep: tab 3 dagen open' );
$schema = sfp_page_config_nlvorm_schema_data( $d, array(), sfp_page_config_cursusinfo( $d ) );
$is( 'Course', $schema['@type'] ?? '', 'zonder groep: toch een Course' );
$is( false, isset( $schema['hasCourseInstance'] ), 'zonder groep: geen instanties' );
$is( 3, count( $schema['offers'] ?? array() ), 'zonder groep: drie aanbiedingen' );
$is( true, sfp_page_config_nlvorm_eigen_aanbod( $d ), 'zonder groep: plugin levert het aanbod (SureRank-Course eraf)' );
$is( array(), sfp_page_config_nlvorm_schema_data( $a, array(), array( 'prijs' => 1995, 'prijzen' => array() ) ), 'zonder groep en zonder prijzen: geen Course (zoals voorheen)' );

/* ---------- Blok Prijs van de training ---------- */
$p = sfp_page_config_cursusprijs_tekst( $d );
$bevat( 'De training kost ', $p, 'prijszin begint goed' );
$bevat( '995 voor 1 dag, ', $p, 'prijszin 1 dag' );
$bevat( ' voor 2 dagen en ', $p, 'prijszin 2 dagen met en' );
$bevat( ' voor 3 dagen, exclusief btw.', $p, 'prijszin eindigt met btw' );
$p = sfp_page_config_cursusprijs_tekst( $a );
$bevat( 'De training kost ', $p, 'zonder varianten: de gewone prijs' );
$bevat( ' voor ', $p, 'zonder varianten: geen voor', false );
$e = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Zonder prijs' ) );
$is( '', sfp_page_config_cursusprijs_tekst( $e ), 'zonder prijs: niets' );
$paginas[] = $e;

/* ---------- Keuzemenu ---------- */
// Keuzemenu: optietekst met de variant voorop.
$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $b ) );
$GLOBALS['wp_query']->is_singular = true;
$GLOBALS['wp_query']->is_page = true;
$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
$GLOBALS['wp_query']->the_post();
ob_start();
sfp_page_config_inject_course_data();
$js = ob_get_clean();
$bevat( '"label":"1 dag \\u2022 20 ', $js, 'optietekst 1 dag voorop' );
$bevat( '3 dagen \\u2022 12, 19 en 26 ', $js, 'optietekst 3 dagen met reeks' );
$bevat( '"value":"groep-4"', $js, 'waarde blijft groep-N' );
$bevat( '"value":"groep-1"', $js, 'volle groep niet in het menu', false );
$is( '24 februari, 3 en 10 maart', str_replace( array( 'February', 'March' ), array( 'februari', 'maart' ), sfp_page_config_variant_data_tekst( array( '2099-02-24', '2099-03-03', '2099-03-10' ) ) ), 'reeks over twee maanden' );

wp_reset_query();

foreach ( $paginas as $id ) {
    wp_delete_post( $id, true );
}
echo $fouten ? "MISLUKT: $fouten van $checks\n" : "OK: $checks checks\n";
