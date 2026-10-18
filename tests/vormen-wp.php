<?php
/**
 * Tests voor de vormen (3.6.0): pijler, adres, kaart, promokaart per vorm,
 * leestekens in de H1 en de controle.
 *
 * Draait in een lokale WordPress (nooit op een live site):
 *   wp eval-file wp-content/plugins/sfp-page-config/tests/vormen-wp.php
 *
 * Maakt eigen categorieën, berichten en begrippen en ruimt ze op.
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

$bewaard = get_option( 'sfp_settings', array() );
$s       = is_array( $bewaard ) ? $bewaard : array();
$s['artikel_aan']                 = '1';
$s['onderdelen']                  = isset( $s['onderdelen'] ) && is_array( $s['onderdelen'] ) ? $s['onderdelen'] : array();
$s['onderdelen']['pijler']        = '1';
$s['kop_leestekens']              = '1';
update_option( 'sfp_settings', $s );
if ( function_exists( 'sfp_page_config_instellingen_vergeet' ) ) {
    sfp_page_config_instellingen_vergeet();
}

$opruimen = array();
$termen   = array();
$hoofd    = wp_insert_term( 'Test hoofdpijler ' . wp_generate_password( 6, false ), 'category' );
$termen[] = $hoofd['term_id'];
$pijler   = wp_insert_term( 'Test pijler ' . wp_generate_password( 6, false ), 'category', array( 'parent' => $hoofd['term_id'] ) );
$termen[] = $pijler['term_id'];
$ander    = wp_insert_term( 'Test ander ' . wp_generate_password( 6, false ), 'category' );
$termen[] = $ander['term_id'];

$artikelen = array();
for ( $i = 0; $i < 6; $i++ ) {
    $artikelen[] = wp_insert_post(
        array(
            'post_title'    => 'Testartikel ' . $i,
            'post_status'   => 'publish',
            'post_content'  => '<!-- wp:paragraph --><p>Tekst.</p><!-- /wp:paragraph -->',
            'post_category' => array( $pijler['term_id'] ),
            'post_date'     => gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS * ( 10 - $i ) ),
        )
    );
}
$opruimen = array_merge( $opruimen, $artikelen );
$lead     = trim( str_repeat( 'woord ', 60 ) );
$gids     = wp_insert_post(
    array(
        'post_title'    => 'Gids: een test-titel, klaar.',
        'post_status'   => 'publish',
        'post_content'  => '<!-- wp:paragraph --><p>' . $lead . '</p><!-- /wp:paragraph --><!-- wp:paragraph --><p><a href="' . get_permalink( $artikelen[0] ) . '">oud</a></p><!-- /wp:paragraph --><!-- wp:sfp/lees-ook {"postId":' . $artikelen[2] . '} /-->',
        'post_category' => array( $pijler['term_id'] ),
    )
);
$opruimen[] = $gids;
update_post_meta( $gids, 'sfp_vorm', 'pijler' );

// Vorm.
$check( 'vorm pijler', 'pijler' === sfp_page_config_vorm( $gids ) );
$check( 'vorm artikel', 'artikel' === sfp_page_config_vorm( $artikelen[0] ) );
$check( 'gids van de categorie', $gids === sfp_page_config_gids_van_term( $pijler['term_id'] ) );

// Adres: de gids staat op het adres van de categorie.
$check( 'adres is categorie', get_permalink( $gids ) === get_term_link( $pijler['term_id'], 'category' ) );
update_post_meta( $gids, 'sfp_eigen_adres', '1' );
$check( 'eigen adres', get_permalink( $gids ) !== get_term_link( $pijler['term_id'], 'category' ) );
delete_post_meta( $gids, 'sfp_eigen_adres' );

// Verzoek op het categorieadres wordt de gids.
$term   = get_term( $pijler['term_id'], 'category' );
$vars   = sfp_page_config_gids_verzoek( array( 'category_name' => get_term_parents_list( $term->term_id, 'category', array( 'format' => 'slug', 'separator' => '/', 'link' => false ) ) ) );
$check( 'verzoek categorie wordt gids', isset( $vars['p'] ) && (int) $vars['p'] === $gids );
$vars = sfp_page_config_gids_verzoek( array( 'category_name' => $term->slug, 'feed' => 'feed' ) );
$check( 'feed blijft categorie', ! isset( $vars['p'] ) );

// Volgorde in de kaart: eerst wat de gids noemt (link, dan Lees ook), dan nieuwste eerst.
$lijst = sfp_page_config_pijler_artikelen( $term, get_post( $gids ) );
$ids   = wp_list_pluck( $lijst, 'ID' );
$check( 'eerst de link', (int) $ids[0] === $artikelen[0] );
$check( 'dan Lees ook', (int) $ids[1] === $artikelen[2] );
$check( 'dan de nieuwste', (int) $ids[2] === $artikelen[5] );
$check( 'gids niet in eigen kaart', ! in_array( $gids, array_map( 'intval', $ids ), true ) );
$check( 'zes artikelen', 6 === count( $ids ) );

// Kaart: slider vanaf vijf, pagina's van vier.
$kaart = sfp_page_config_gids_kaart( get_post( $gids ) );
$check( 'kaart is slider', false !== strpos( $kaart, 'sfp-gids--slider' ) );
$check( 'twee paginas', 2 === substr_count( $kaart, 'sfp-gids__pagina"' ) );
$check( 'teller 1 / 2', false !== strpos( $kaart, '1 / 2' ) );

// Begrippen: hoogstens acht, dan een link.
if ( post_type_exists( 'begrippen' ) ) {
    for ( $i = 0; $i < 10; $i++ ) {
        $b = wp_insert_post(
            array(
                'post_type'   => 'begrippen',
                'post_title'  => 'Begrip ' . $i,
                'post_status' => 'publish',
            )
        );
        update_post_meta( $b, 'sfp_pijler', $pijler['term_id'] );
        $opruimen[] = $b;
    }
    $kaart = sfp_page_config_gids_kaart( get_post( $gids ) );
    preg_match( '#sfp-gids__rij">(.*?)</p>#s', $kaart, $rij );
    $check( 'acht begrippen in de rij', isset( $rij[1] ) && 9 === substr_count( $rij[1], '<a ' ) );
    $check( 'link naar alle begrippen', false !== strpos( $kaart, 'pijler=' . $term->slug ) );
}

// Hoofdpijler gegroepeerd.
$hoofdgids = wp_insert_post(
    array(
        'post_title'    => 'Hoofdgids',
        'post_status'   => 'publish',
        'post_content'  => '<!-- wp:paragraph --><p>' . $lead . '</p><!-- /wp:paragraph -->',
        'post_category' => array( $hoofd['term_id'] ),
    )
);
$opruimen[] = $hoofdgids;
update_post_meta( $hoofdgids, 'sfp_vorm', 'pijler' );
$kaart = sfp_page_config_gids_kaart( get_post( $hoofdgids ) );
$check( 'hoofdpijler gegroepeerd', false !== strpos( $kaart, 'sfp-gids--groepen' ) );
$check( 'hoogstens vier per groep', 4 === substr_count( $kaart, '<li>' ) );
$check( 'link alle in pijler', false !== strpos( $kaart, 'sfp-gids__alle' ) );
$check( 'pijlerregel hoofdpijler', false !== strpos( sfp_page_config_artikel_pijlerregel( get_post( $hoofdgids ) ), '>Guide<' ) || false !== strpos( sfp_page_config_artikel_pijlerregel( get_post( $hoofdgids ) ), '>Gids<' ) );
$check( 'pijlerregel pijler in', 1 === preg_match( '#(Guide in|Gids in|Guia em) <a#', sfp_page_config_artikel_pijlerregel( get_post( $gids ) ) ) );

// Een concept-pijler toont zijn kaart (voorbeeldlink), maar houdt zijn eigen adres.
$concept = wp_insert_post(
    array(
        'post_title'    => 'Concept pijler',
        'post_status'   => 'draft',
        'post_content'  => '<!-- wp:paragraph --><p>Lead</p><!-- /wp:paragraph -->',
        'post_category' => array( $pijler['term_id'] ),
    )
);
$opruimen[] = $concept;
update_post_meta( $concept, 'sfp_vorm', 'pijler' );
$check( 'concept: kaart', '' !== sfp_page_config_gids_kaart( get_post( $concept ) ) );
$check( 'concept: telt niet als gids', $gids === sfp_page_config_gids_van_term( $pijler['term_id'] ) );

// Kaart na de lead.
$html = sfp_page_config_gids_na_lead( '<p>Lead</p><h2>Kop</h2><p>Meer</p>', '<nav>K</nav>' );
$check( 'kaart na de lead', '<p>Lead</p><nav>K</nav><h2>Kop</h2><p>Meer</p>' === $html );
$html = sfp_page_config_gids_na_lead( '<h2>Kop</h2><p>Meer</p>', '<nav>K</nav>' );
$check( 'kaart bovenaan zonder lead', 0 === strpos( $html, '<nav>K</nav>' ) );

// Twee gidsen voor één categorie: de oudste telt, de controle meldt het.
$tweede = wp_insert_post(
    array(
        'post_title'    => 'Tweede gids',
        'post_status'   => 'publish',
        'post_content'  => '<!-- wp:paragraph --><p>kort</p><!-- /wp:paragraph -->',
        'post_category' => array( $pijler['term_id'] ),
    )
);
$opruimen[] = $tweede;
update_post_meta( $tweede, 'sfp_vorm', 'pijler' );
$check( 'oudste gids telt', $gids === sfp_page_config_gids_van_term( $pijler['term_id'] ) );
$codes = wp_list_pluck( sfp_page_config_pijler_controle( get_post( $tweede ) ), 'code' );
$check( 'controle dubbel', in_array( 'pijler-dubbel', $codes, true ) );
$check( 'controle korte lead', in_array( 'pijler-lead', $codes, true ) );
$codes = wp_list_pluck( sfp_page_config_pijler_controle( get_post( $gids ) ), 'code' );
$check( 'lead van 60 woorden is goed', ! in_array( 'pijler-lead', $codes, true ) );
$check( 'artikelen zonder verwijzing gemeld', in_array( 'pijler-verwijzing', $codes, true ) );
$codes = wp_list_pluck( sfp_page_config_pijler_controle( get_post( $hoofdgids ) ), 'code' );
$check( 'hoofdpijler: geen melding verwijzing', ! in_array( 'pijler-verwijzing', $codes, true ) );

// Gekozen categorie gaat voor de eigen categorie.
update_post_meta( $tweede, 'sfp_gids_van', $ander['term_id'] );
$check( 'gekozen categorie', $tweede === sfp_page_config_gids_van_term( $ander['term_id'] ) );

// Promokaart per vorm.
$s = get_option( 'sfp_settings' );
$s['artikel_promo_titel']  = 'Het boek';
$s['artikel_promo_url']    = 'https://example.com/boek';
$s['artikel_promo2_titel'] = 'Discovery call';
$s['artikel_promo2_url']   = 'https://example.com/call';
$s['artikel_promo2_vorm']  = 'rond';
$s['promo_vorm_artikel']   = '';
$s['promo_vorm_pijler']    = 'tweede';
update_option( 'sfp_settings', $s );
if ( function_exists( 'sfp_page_config_instellingen_vergeet' ) ) {
    sfp_page_config_instellingen_vergeet();
}
$check( 'artikel: kaart van de site', false !== strpos( sfp_page_config_artikel_promokaart( 'zij', $artikelen[0] ), 'Het boek' ) );
$kaart = sfp_page_config_artikel_promokaart( 'zij', $gids );
$check( 'pijler zonder zelftest: tweede kaart', false !== strpos( $kaart, 'Discovery call' ) && false !== strpos( $kaart, 'sfp-art-promo--rond' ) );
$check( 'tool: standaard geen kaart', 'geen' === sfp_page_config_promo_keuze( 'tool' ) );
// De zelftest op de pijlerkaart: zie tests/zelftest-cta-wp.php.

// Thermometer (pijler).
$stel  = function ( $t ) {
    return '<!-- wp:sfp/thermometer-stelling ' . wp_json_encode( array( 'tekst' => $t ) ) . ' /-->';
};
$thermo = '<!-- wp:sfp/thermometer ' . wp_json_encode( array( 'kop' => 'How nervous are you?', 'links' => 'Calm', 'rechts' => 'Nervous', 'hoog_label' => 'Nerves lead', 'laag' => 'Calm.', 'midden' => 'Some nerves.', 'hoog' => 'Nerves rule.', 'hoog_anker' => 'nerves' ) ) . ' -->'
    . $stel( 'I sleep badly before a talk.' ) . $stel( 'My hands shake.' ) . $stel( 'I avoid speaking.' ) . $stel( 'My mind goes blank.' )
    . '<!-- /wp:sfp/thermometer -->';
$check( 'pijler zonder thermometer: melding', in_array( 'pijler-thermometer', wp_list_pluck( sfp_page_config_pijler_controle( get_post( $gids ) ), 'code' ), true ) );
wp_update_post( array( 'ID' => $gids, 'post_content' => get_post( $gids )->post_content . $thermo ) );
$check( 'pijler met thermometer: geen melding', ! in_array( 'pijler-thermometer', wp_list_pluck( sfp_page_config_pijler_controle( get_post( $gids ) ), 'code' ), true ) );
sfp_page_config_thermometer( false );
$check( 'thermometer niet in de tekst', '' === trim( do_blocks( $thermo ) ) );
$th = sfp_page_config_thermometer();
$check( 'thermometer verzameld', is_array( $th ) && 4 === count( $th['stellingen'] ) && 'How nervous are you?' === $th['kop'] );
$paneel = sfp_page_config_thermometer_paneel( $th, array( array( 'id' => 'nerves', 'titel' => 'Start with your nerves' ) ) );
$check( 'paneel: vier stellingen, drie knoppen elk', 4 === substr_count( $paneel, 'sfp-art-thermo__stelling' ) && 12 === substr_count( $paneel, 'data-punt=' ) );
$check( 'paneel: eigen naam en schaaleinden, geen algemeen woord', false !== strpos( $paneel, 'Nerves lead' ) && false !== strpos( $paneel, '<span>Calm</span><span>Nervous</span>' ) && 1 === substr_count( $paneel, 'sfp-art-thermo__stand' ) );
$check( 'paneel: drie uitslagen, link bij hoog', 3 === substr_count( $paneel, 'data-band=' ) && false !== strpos( $paneel, 'href="#nerves"' ) );
$kop = sfp_page_config_artikel_kopkaart( get_post( $gids ), array( array( 'id' => 'nerves', 'titel' => 'Start with your nerves' ) ) );
$check( 'kopkaart pijler: thermometer', false !== strpos( $kop, 'sfp-art-paneel-thermo' ) );
$check( 'kopkaart pijler: geen zelftest', false === strpos( $kop, 'zelftest' ) && false === strpos( $kop, 'sfp-art-paneel-warm' ) );
sfp_page_config_thermometer( false );
$twee = '<!-- wp:sfp/thermometer -->' . $stel( 'a' ) . $stel( 'b' ) . '<!-- /wp:sfp/thermometer -->';
do_blocks( $twee );
$check( 'twee stellingen: geen thermometer', null === sfp_page_config_thermometer() );
$artikel_kop = sfp_page_config_artikel_kopkaart( get_post( $artikelen[0] ), array() );
$check( 'artikel: geen thermometer in de kopkaart', false === strpos( $artikel_kop, 'sfp-art-paneel-thermo' ) );

// Leestekens in de H1.
$check( 'leestekens', 'Presence<span class="sfp-lt">:</span> self<span class="sfp-lt-k">-</span>test' === sfp_page_config_leestekens( 'Presence: self-test', true ) );
$check( 'entiteit blijft heel', 'A &amp; B&#8217;s' === sfp_page_config_leestekens( 'A &amp; B&#8217;s', true ) );
$check( 'alleen in h1', '<h2>a: b</h2>' === sfp_page_config_leestekens( '<h2>a: b</h2>' ) );
$check( 'attribuut blijft heel', '<h1 class="a-b">x<span class="sfp-lt">.</span></h1>' === sfp_page_config_leestekens( '<h1 class="a-b">x.</h1>' ) );

// Uit: geen gids, gewoon adres.
$s = get_option( 'sfp_settings' );
$s['onderdelen']['pijler'] = '0';
update_option( 'sfp_settings', $s );
if ( function_exists( 'sfp_page_config_instellingen_vergeet' ) ) {
    sfp_page_config_instellingen_vergeet();
}
$check( 'uit: geen pijler', 'artikel' === sfp_page_config_vorm( $gids ) );
$check( 'uit: eigen adres', get_permalink( $gids ) !== get_term_link( $pijler['term_id'], 'category' ) );

// Opruimen.
foreach ( $opruimen as $id ) {
    wp_delete_post( $id, true );
}
foreach ( array_reverse( $termen ) as $t ) {
    wp_delete_term( $t, 'category' );
}
update_option( 'sfp_settings', $bewaard );
delete_transient( 'sfp_pc_gidsen' );

echo ( $fouten ? "MISLUKT: $fouten van " . ( $fouten + $goed ) : "OK: $goed controles" ) . "\n";
