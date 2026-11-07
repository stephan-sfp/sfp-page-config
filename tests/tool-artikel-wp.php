<?php
/**
 * Tests voor "Alleen in artikel" (3.28.0) en de zijkolom van een begrip in een lokale WordPress:
 *   wp eval-file wp-content/plugins/sfp-page-config/tests/tool-artikel-wp.php [houd]
 *
 * Maakt twee tools (een alleen in artikel, een gewoon), een artikel dat de
 * eerste plaatst met het blok Tool, en een begrip met drie hoofdstukken.
 * Controleert de lijsten, de sitemapfilters, de zoekfunctie naar het artikel,
 * de voorbeeldlink en de zijkolom. Met het argument "houd" blijft de inhoud
 * staan (voor een doorloop in de browser) en print het script de adressen.
 *
 * @package SFP_Page_Config
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}

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
$houd = isset( $args[0] ) && 'houd' === $args[0];

$bewaard = get_option( 'sfp_settings', array() );
$s       = is_array( $bewaard ) ? $bewaard : array();
$s['artikel_aan']          = '1';
$s['onderdelen']['tools']  = '1';
$s['onderdelen']['begrip'] = '1';
update_option( 'sfp_settings', $s );
if ( ! post_type_exists( 'tool' ) ) {
    sfp_page_config_berichttypes_registreer();
    sfp_page_config_tool_meta();
}
$check( 'tools aan', sfp_page_config_tool_aan() );

$beschrijving = wp_json_encode(
    array(
        'soort'     => 'test',
        'dimensies' => array( 'a' => 'Alpha' ),
        'vragen'    => array(
            array( 'id' => 'q1', 'type' => 'likert', 'dimensie' => 'a', 'tekst' => 'Ik ben a.' ),
            array( 'id' => 'q2', 'type' => 'likert', 'dimensie' => 'a', 'tekst' => 'Ik ben ook a.' ),
        ),
        'uitslag'   => array( 'regel' => 'hoogste', 'profielen' => array( 'a' => array( 'titel' => 'Alpha', 'tekst' => 'Je bent a.' ) ) ),
    )
);

// Restanten van een eerdere run.
foreach ( get_posts( array( 'post_type' => array( 'tool', 'post', 'begrippen' ), 'post_status' => 'any', 'posts_per_page' => 50, 's' => 'Proef alleen-artikel', 'suppress_filters' => true ) ) as $oud ) {
    wp_delete_post( $oud->ID, true );
}

$tool_a = wp_insert_post( array( 'post_type' => 'tool', 'post_status' => 'publish', 'post_title' => 'Proef alleen-artikel tool A', 'post_name' => 'proef-alleen-artikel-a', 'post_content' => '' ) );
$tool_b = wp_insert_post( array( 'post_type' => 'tool', 'post_status' => 'publish', 'post_title' => 'Proef alleen-artikel tool B', 'post_name' => 'proef-alleen-artikel-b', 'post_content' => '' ) );
$tool_c = wp_insert_post( array( 'post_type' => 'tool', 'post_status' => 'draft', 'post_title' => 'Proef alleen-artikel tool C concept', 'post_name' => 'proef-alleen-artikel-c', 'post_content' => '' ) );
update_post_meta( $tool_a, 'sfp_tool', $beschrijving );
update_post_meta( $tool_b, 'sfp_tool', $beschrijving );
update_post_meta( $tool_c, 'sfp_tool', $beschrijving );
update_post_meta( $tool_a, 'sfp_tool_alleen_artikel', true );

$artikel = wp_insert_post(
    array(
        'post_type'    => 'post',
        'post_status'  => 'publish',
        'post_title'   => 'Proef alleen-artikel artikel',
        'post_name'    => 'proef-alleen-artikel-artikel',
        'post_content' => '<!-- wp:paragraph --><p>Intro.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Een</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Tekst.</p><!-- /wp:paragraph --><!-- wp:sfp/tool {"tool":' . (int) $tool_a . '} /--><!-- wp:heading --><h2>Twee</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Meer.</p><!-- /wp:paragraph -->',
    )
);
$begrip  = wp_insert_post(
    array(
        'post_type'    => 'begrippen',
        'post_status'  => 'publish',
        'post_title'   => 'Proef alleen-artikel begrip',
        'post_name'    => 'proef-alleen-artikel-begrip',
        'post_content' => '<!-- wp:paragraph --><p>Definitie.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Herkomst</h2><!-- /wp:heading --><!-- wp:paragraph --><p>A.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Gebruik</h2><!-- /wp:heading --><!-- wp:paragraph --><p>B.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Kritiek</h2><!-- /wp:heading --><!-- wp:paragraph --><p>C.</p><!-- /wp:paragraph -->',
    )
);

/* Meta in REST. */
$meta = get_registered_meta_keys( 'post', 'tool' );
$check( 'meta geregistreerd met show_in_rest', isset( $meta['sfp_tool_alleen_artikel'] ) && ! empty( $meta['sfp_tool_alleen_artikel']['show_in_rest'] ) && 'boolean' === $meta['sfp_tool_alleen_artikel']['type'] );
$check( 'alleen_artikel waar voor A', sfp_page_config_tool_alleen_artikel( $tool_a ) );
$check( 'alleen_artikel onwaar voor B', ! sfp_page_config_tool_alleen_artikel( $tool_b ) );

/* Lijsten. */
$ids = wp_list_pluck( sfp_page_config_tools(), 'ID' );
$check( 'tools(): B erin', in_array( $tool_b, $ids, true ) );
$check( 'tools(): A eruit', ! in_array( $tool_a, $ids, true ) );
$alles = wp_list_pluck( sfp_page_config_tools( true ), 'ID' );
$check( 'tools(true): A erin (keuzelijst editor)', in_array( $tool_a, $alles, true ) );
$check( 'ids alleen artikel = [A]', array( (int) $tool_a ) === sfp_page_config_tool_alleen_artikel_ids() );

/* Andere tools op B noemt A niet. */
$andere = sfp_page_config_tool_andere( get_post( $tool_b ) );
$check( 'Andere tools zonder A', false === strpos( $andere, 'proef-alleen-artikel-a' ) );

/* Een lijstquery van tools (archief, berichtenraster). */
$q = new WP_Query( array( 'post_type' => 'tool', 'post_status' => 'publish', 'posts_per_page' => 50, 'sfp_voorkant' => true ) );
$qi = wp_list_pluck( $q->posts, 'ID' );
$check( 'WP_Query tool: A eruit, B erin', ! in_array( $tool_a, $qi, true ) && in_array( $tool_b, $qi, true ), implode( ',', $qi ) );
$q2 = new WP_Query( array( 'post_type' => 'tool', 'p' => $tool_a ) );
$check( 'WP_Query enkel bericht A blijft bereikbaar', 1 === count( $q2->posts ) );
$q3 = new WP_Query( array( 'post_type' => 'tool', 'post_status' => 'publish', 'posts_per_page' => 50 ) );
$check( 'WP_Query in het beheer (WP-CLI, REST): A erin', in_array( $tool_a, wp_list_pluck( $q3->posts, 'ID' ), true ) );

/* Sitemapfilters. */
$check( 'surerank sitemap sluit A uit', in_array( (int) $tool_a, sfp_page_config_tool_alleen_artikel_sitemap( array( 5 ) ), true ) );
$args = sfp_page_config_tool_alleen_artikel_wp_sitemap( array( 'post_type' => 'tool' ), 'tool' );
$check( 'wp sitemap sluit A uit', in_array( (int) $tool_a, $args['post__not_in'], true ) );
$args2 = sfp_page_config_tool_alleen_artikel_wp_sitemap( array( 'post_type' => 'post' ), 'post' );
$check( 'wp sitemap raakt post niet', ! isset( $args2['post__not_in'] ) );

/* Het artikel dat A plaatst. */
$van = sfp_page_config_tool_artikel_van( $tool_a );
$check( 'artikel van A gevonden', $van && (int) $van->ID === (int) $artikel, $van ? (string) $van->ID : 'geen' );
$check( 'artikel van B: geen', null === sfp_page_config_tool_artikel_van( $tool_b ) );

/* Het blok rendert A gewoon. */
$html = sfp_page_config_tool_html( $tool_a );
$check( 'sfp/tool rendert A', false !== strpos( $html, 'data-tool="' . (int) $tool_a . '"' ) );

/* Voorbeeldlink: een concept rendert als de sleutel van dat bericht geldt. */
$check( 'concept C zonder sleutel: niets', '' === sfp_page_config_tool_html( $tool_c ) );
sfp_page_config_voorbeeld_geldig( $tool_c );
$check( 'concept C met geldige voorbeeldsleutel: rendert', false !== strpos( sfp_page_config_tool_html( $tool_c ), 'data-tool="' . (int) $tool_c . '"' ) );
sfp_page_config_voorbeeld_geldig( 0 );

/* Beheer: de stand noemt het vinkje. */
$stand = sfp_page_config_tool_beheer_stand();
$rij   = array_values( array_filter( $stand['tools'], function ( $r ) use ( $tool_a ) { return (int) $r['id'] === (int) $tool_a; } ) );
$check( 'beheer stand: alleen_artikel', $rij && ! empty( $rij[0]['alleen_artikel'] ) );

/* Zijkolom van een begrip: inhoudsopgave bij drie hoofdstukken. */
$GLOBALS['post'] = get_post( $begrip );
setup_postdata( $GLOBALS['post'] );
$render = sfp_page_config_artikel_render( get_post( $begrip ) );
wp_reset_postdata();
$check( 'begrip: zijkolom aanwezig', false !== strpos( $render, 'sfp-artikel__zij' ) );
$check( 'begrip: inhoudsopgave in de zijkolom', false !== strpos( $render, 'sfp-art-toc' ) );
$check( 'begrip: niet zonder-zij', false === strpos( $render, 'sfp-artikel--zonder-zij' ) );

echo "OK: $goed checks, $fouten fouten\n";
if ( $houd ) {
    echo 'artikel=' . get_permalink( $artikel ) . "\n";
    echo 'tool_a=' . get_permalink( $tool_a ) . "\n";
    echo 'tool_b=' . get_permalink( $tool_b ) . "\n";
    echo 'tool_c=' . (int) $tool_c . "\n";
    echo 'begrip=' . get_permalink( $begrip ) . "\n";
    echo 'archief=' . get_post_type_archive_link( 'tool' ) . "\n";
} else {
    foreach ( array( $tool_a, $tool_b, $tool_c, $artikel, $begrip ) as $id ) {
        wp_delete_post( $id, true );
    }
    update_option( 'sfp_settings', $bewaard );
}
exit( $fouten ? 1 : 0 );
