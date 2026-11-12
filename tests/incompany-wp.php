<?php
/**
 * Tests voor de incompanybouwstenen (3.53.0): Portfolio (Tabs), Onderwerpenregel,
 * Slot kort (Kolommen) en Keuzehulp vorm, plus Service en ItemList. Draait in een
 * echte WordPress (een lokale testinstallatie, nooit een live site). Maakt eigen
 * pagina's en ruimt ze op.
 *
 * Draaien: wp eval-file wp-content/plugins/sfp-page-config/tests/incompany-wp.php
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

/* ---------- Onderwerpnaam ---------- */
$is( 'Pitchen', sfp_page_config_onderwerp_naam( 'Cursus Pitchen' ), 'Cursus valt weg' );
$is( 'Adviesvaardigheden', sfp_page_config_onderwerp_naam( 'Training Adviesvaardigheden' ), 'Training valt weg' );
$is( 'Presenteren voor de Camera', sfp_page_config_onderwerp_naam( 'Cursus Presenteren voor de Camera' ), 'rest blijft zoals geschreven' );
$is( 'Storytelling', sfp_page_config_onderwerp_naam( 'Incompanycursus storytelling' ), 'Incompanycursus valt weg, hoofdletter' );
$is( 'Cursus', sfp_page_config_onderwerp_naam( 'Cursus' ), 'alleen het woord: titel blijft' );
$is( 'Timemanagement', sfp_page_config_onderwerp_naam( 'Timemanagement' ), 'zonder voorvoegsel ongewijzigd' );

/* ---------- Doelen ---------- */
$is( '#adviesgesprek', sfp_page_config_incompany_adres( '#adviesgesprek' ), 'anker' );
$is( home_url( '/incompany/calculator/' ), sfp_page_config_incompany_adres( '/incompany/calculator/' ), 'pad op de site' );
$is( '', sfp_page_config_incompany_adres( 'javascript:alert(1)' ), 'geen javascript' );
$is( '', sfp_page_config_incompany_adres( '' ), 'leeg' );
$is( '', sfp_page_config_incompany_tekstlink( 'Tekst', '' ), 'tekstlink zonder doel: niets' );
$is( '', sfp_page_config_incompany_knop( '', '#a' ), 'knop zonder tekst: niets' );

/* ---------- Onderwerpenregel ---------- */
$ouder = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Incompany test' ) );
$weg[] = $ouder;
update_post_meta( $ouder, 'sfp_page_type', 'incompany' );
$coach = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Coachgesprek', 'post_parent' => $ouder ) );
$weg[] = $coach;
$kind  = array();
foreach ( array( 3 => 'Cursus Storytelling', 1 => 'Cursus Presenteren', 2 => 'Cursus Pitchen' ) as $o => $t ) {
    $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $t, 'post_parent' => $ouder, 'menu_order' => $o ) );
    update_post_meta( $id, 'sfp_page_type', 'incompany' );
    $kind[ $t ] = $id;
    $weg[]      = $id;
}
$concept = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Cursus Concept', 'post_parent' => $ouder ) );
update_post_meta( $concept, 'sfp_page_type', 'incompany' );
$weg[] = $concept;

$lijst = sfp_page_config_onderwerpen( $ouder );
$is( array( 'Presenteren', 'Pitchen', 'Storytelling' ), wp_list_pluck( $lijst, 'naam' ), 'alleen Incompany, gepubliceerd, op Volgorde' );
$html = do_blocks( '<!-- wp:sfp/onderwerpen {"ouder":' . $ouder . '} /-->' );
$is( 1, preg_match( '/<ul class="[^"]*\bsfp-onderwerpen\b/', $html ), 'lijst als ul' );
$bevat( 'is-style-sfp-pijl"><a href="' . esc_url( get_permalink( $kind['Cursus Pitchen'] ) ) . '">Pitchen</a>', $html, 'tekstlink met pijl naar de onderwerppagina' );
$bevat( 'Coachgesprek', $html, 'coachgesprek staat er niet in', false );
$is( '', do_blocks( '<!-- wp:sfp/onderwerpen {"ouder":' . $coach . '} /-->' ), 'zonder onderwerpen: niets' );

/* ---------- Portfolio ---------- */
$beeld = (int) ( get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'numberposts' => 1, 'fields' => 'ids' ) )[0] ?? 0 );
$img   = $beeld ? '<!-- wp:image {"id":' . $beeld . '} --><figure class="wp-block-image"><img src="' . wp_get_attachment_url( $beeld ) . '" alt="x" class="wp-image-' . $beeld . '"/></figure><!-- /wp:image -->' : '';
$tab   = function ( $naam, $logo ) use ( $img ) {
    return '<!-- wp:sfp/tab {"titel":"' . $naam . '","logoId":' . $logo . '} --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Kop ' . $naam . '</h3><!-- /wp:heading -->' . $img . '<!-- wp:paragraph --><p>Tekst ' . $naam . '</p><!-- /wp:paragraph --><!-- /wp:sfp/tab -->';
};
$pf = do_blocks( '<!-- wp:sfp/tabs {"vorm":"portfolio","label":"Opdrachtgevers","actieTekst":"Plan een adviesgesprek","actieDoel":"#adviesgesprek"} -->' . $tab( 'KLM', $beeld ) . $tab( 'Alliander', 0 ) . '<!-- /wp:sfp/tabs -->' );
$bevat( 'sfp-tabs sfp-tabs--portfolio', $pf, 'portfolio heeft zijn klasse' );
$bevat( 'role="tablist" aria-label="Opdrachtgevers"', $pf, 'tablist met naam' );
if ( $beeld ) {
    $bevat( 'alt="KLM (logo)"', $pf, 'logo-alt volgens N1' );
}
$bevat( '<span class="sfp-portfolio__naam">Alliander</span>', $pf, 'zonder logo de naam in de balk' );
$bevat( '<p class="is-style-sfp-label">KLM</p>', $pf, 'naam als label in het paneel' );
$is( 2, substr_count( $pf, 'href="#adviesgesprek">Plan een adviesgesprek</a>' ), 'tekstlink onder elk paneel' );
$is( 1, substr_count( $pf, 'role="tabpanel"' ) - substr_count( $pf, ' hidden>' ), 'één paneel open' );
if ( $beeld ) {
    $is( 1, preg_match( '/sfp-portfolio__beeld"><figure/', $pf ), 'foto rechts' );
    $is( 0, preg_match( '/sfp-portfolio__tekst">(?:(?!sfp-portfolio__beeld).)*wp-block-image/s', $pf ), 'foto niet in de tekst' );
}
$gewoon = do_blocks( '<!-- wp:sfp/tabs --><!-- wp:sfp/tab {"titel":"A"} --><!-- wp:paragraph --><p>a</p><!-- /wp:paragraph --><!-- /wp:sfp/tab --><!-- /wp:sfp/tabs -->' );
$bevat( 'sfp-portfolio', $gewoon, 'gewone tabs blijven gewone tabs', false );

/* ---------- Slot kort ---------- */
$kort = do_blocks( '<!-- wp:sfp/kolommen {"indeling":"kort"} --><!-- wp:sfp/kolom --><!-- wp:paragraph --><p>a</p><!-- /wp:paragraph --><!-- /wp:sfp/kolom --><!-- /wp:sfp/kolommen -->' );
$bevat( 'sfp-kolommen--kort', $kort, 'indeling kort' );
$is( array( 'lijst', 'review-kaart', 'incompany' ), sfp_page_config_nlvorm_delen( '<!-- wp:sfp/kolommen {"indeling":"kort"} -->' ), 'CSS in de head bij slot kort' );
$is( array(), sfp_page_config_nlvorm_delen( '<!-- wp:sfp/kolommen {"indeling":"twee"} -->' ), 'geen incompany-CSS bij twee' );

/* ---------- Keuzehulp vorm ---------- */
$zonder_css = function ( $h ) {
    return preg_replace( '#<style[^>]*>.*?</style>#s', '', (string) $h );
};
$kv = $zonder_css( do_blocks( '<!-- wp:sfp/keuzehulp-vorm /-->' ) );
$bevat( 'data-sfp-kv', $kv, 'keuzehulp vorm' );
$is( 3, substr_count( $kv, '<fieldset class="sfp-kv__vraag">' ), 'drie vragen als fieldset' );
$is( 8, substr_count( $kv, 'type="radio"' ), 'acht radioknoppen' );
$bevat( esc_html( sfp_page_config_tekst( 'kv_vraag1' ) ), $kv, 'standaardvraag 1' );
$is( 4, preg_match_all( '/class="sfp-kv__kaart[ "]/', $kv ), 'vier kaarten' );
$is( 1, preg_match( '/data-vorm="incompany".*data-vorm="training".*data-vorm="coaching".*data-vorm="elearning"/s', $kv ), 'incompany eerst' );
$bevat( 'sfp-kv__kaart is-uitgelicht" data-vorm="incompany"', $kv, 'incompany uitgelicht in de beginstand' );
$bevat( 'wp-block-button__link', $kv, 'zonder doel geen knop', false );
$bevat( 'sfp-lijst--plat', $kv, 'e-learning zonder vinkjes' );
$bevat( sfp_page_config_tekst( 'kv_elearning_feit1' ), $kv, 'e-learning standaardregel' );
$kv2 = $zonder_css( do_blocks( '<!-- wp:sfp/keuzehulp-vorm ' . wp_json_encode( array(
    'vragen'  => array( 'v1' => 'Met hoeveel?', 'opties' => array( 'een', '', '', '' ) ),
    'kaarten' => array(
        'incompany' => array( 'feiten' => array( 'A', '', 'B', 'C', 'D', 'E' ), 'doel' => '#adviesgesprek', 'doel2' => '/incompany/calculator/' ),
        'training'  => array( 'actie' => 'knop', 'tekst' => 'Naar de training', 'doel' => '/presentatietraining/' ),
    ),
) ) . ' /-->' ) );
$bevat( 'Met hoeveel?</legend>', $kv2, 'eigen vraag' );
$bevat( '<span>een</span>', $kv2, 'eigen antwoord' );
$bevat( '<span>' . sfp_page_config_tekst( 'kv_optie2' ) . '</span>', $kv2, 'leeg antwoord valt terug' );
$is( 1, preg_match( '/data-vorm="incompany".*?<ul><li>A<\/li><li>B<\/li><li>C<\/li><li>D<\/li><li class="sfp-kv__extra">/s', $kv2 ), 'hooguit vier feiten, lege overgeslagen, extra regel' );
$bevat( 'href="#adviesgesprek">' . sfp_page_config_tekst( 'kv_incompany_actie' ) . '</a></div></div><p class="is-style-sfp-pijl"><a href="' . home_url( '/incompany/calculator/' ) . '">' . sfp_page_config_tekst( 'kv_incompany_actie2' ), $kv2, 'knop en tweede actie' );
$bevat( 'href="' . home_url( '/presentatietraining/' ) . '">Naar de training</a>', $kv2, 'eigen actie als knop' );
$is( 1, preg_match_all( '/name="(sfp-kv-\d+)-aantal"/', $kv . $kv2, $m ) ? count( array_unique( $m[1] ) ) - 1 : 0, 'radionamen per blok uniek' );

/* ---------- Gegevens voor Google ---------- */
$pagina = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Incompany schema', 'post_content' => '<!-- wp:paragraph {"className":"is-style-sfp-kernzin"} --><p class="is-style-sfp-kernzin">Trainingen op maat.</p><!-- /wp:paragraph --><!-- wp:sfp/onderwerpen {"ouder":' . $ouder . '} /-->' ) );
$weg[]  = $pagina;
$is( false, sfp_page_config_incompany_schema_aan( $pagina ), 'zonder soort Incompany geen schema' );
update_post_meta( $pagina, 'sfp_page_type', 'incompany' );
$is( true, sfp_page_config_incompany_schema_aan( $pagina ), 'met soort en bouwsteen wel' );
$is( true, sfp_page_config_nlvorm_eigen_aanbod( $pagina ), 'SureRank-aanbod gaat weg' );
$is( false, sfp_page_config_incompany_schema_aan( $ouder ), 'bestaande incompanypagina zonder bouwsteen: geen schema' );
$is( false, sfp_page_config_nlvorm_eigen_aanbod( $ouder ), 'bestaande pagina: SureRank blijft' );
$service = sfp_page_config_incompany_service( $pagina );
$is( 'Service', $service['@type'], 'Service' );
$is( home_url( '/' ) . '#organization', $service['provider']['@id'], 'provider is de organisatie' );
$is( 'Trainingen op maat.', $service['description'] ?? '', 'beschrijving uit de kernzin' );
$il = sfp_page_config_incompany_itemlist( $pagina );
$is( 'ItemList', $il['@type'] ?? '', 'ItemList' );
$is( array( 'Cursus Presenteren', 'Cursus Pitchen', 'Cursus Storytelling' ), array_column( $il['itemListElement'], 'name' ), 'ItemList met de volle titels' );
$is( 3, count( $il['itemListElement'] ), 'drie onderwerpen' );

foreach ( $weg as $id ) {
    wp_delete_post( $id, true );
}
echo "incompany-wp: $checks controles, $fouten fouten\n";
