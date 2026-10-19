<?php
/**
 * SFP Page Config - Opruimen bij verwijderen
 *
 * Draait alleen als de plugin in WordPress wordt verwijderd (niet bij
 * uitzetten en niet bij bijwerken of een zip uploaden over de bestaande).
 *
 * Wat weggaat: de werkgegevens die de plugin zelf weer opbouwt (bewaarde
 * tussenstanden, meldingen, de stand van de versnelling van het
 * boekformulier) en de geplande dagelijkse controle.
 *
 * Wat blijft: alle instellingen (sfp_settings, reviews, artikelopmaak),
 * de gegevens per pagina (soort pagina, cursusdata, knop onderaan) en de
 * deelbeelden in de mediabibliotheek. Wie de plugin verwijdert en daarna
 * opnieuw installeert, bijvoorbeeld bij terugdraaien, raakt zo niets kwijt.
 *
 * Uitzondering (3.4.0): de zelftest. Gegevens van deelnemers, pdf's,
 * het pakket en de instellingen van de zelftest gaan weg,
 * omdat de bewaartermijn zonder de plugin niet meer wordt bewaakt.
 *
 * @package SFP_Page_Config
 * @since   3.1.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

foreach ( array(
    'sfp_cron_notified',
    'sfp_page_config_deelbeeld_webp',
    'sfp_deelbeeld_fout',
    'sfp_ajax_lean_state',
    'sfp_ajax_lean_error',
    'sfp_page_config_version',
    'sfp_pc_aff_treffers_sleutel',
    'sfp_pc_shortcodes_voorkant',
    'sfp_page_config_tool_adressen',
) as $sfp_optie ) {
    delete_option( $sfp_optie );
}

$sfp_tijdstip = wp_next_scheduled( 'sfp_page_config_daily_check' );
if ( $sfp_tijdstip ) {
    wp_unschedule_event( $sfp_tijdstip, 'sfp_page_config_daily_check' );
}

// Tijdelijk bewaarde gegevens van de plugin.
global $wpdb;
foreach ( array( 'sfp_pc_', 'sfp_feiten_', 'sfp_dashboard_', 'sfp_next_course_' ) as $sfp_voorvoegsel ) {
    $sfp_patroon = $wpdb->esc_like( '_transient_' . $sfp_voorvoegsel ) . '%';
    $sfp_namen   = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $sfp_patroon ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    foreach ( $sfp_namen as $sfp_naam ) {
        delete_transient( substr( $sfp_naam, strlen( '_transient_' ) ) );
    }
}

// Zelftest (3.4.0): gegevens van deelnemers en pdf's gaan
// weg met de plugin. Anders zouden ze blijven staan zonder dat de dagelijkse
// opruiming (bewaartermijn van 2 jaar) nog draait. Het pakket gaat mee weg;
// de bron staat buiten de site.
$sfp_zt_tabel = $wpdb->prefix . 'sfp_zelftest';
$wpdb->query( "DROP TABLE IF EXISTS {$sfp_zt_tabel}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
$sfp_zt_geheim = (string) get_option( 'sfp_zelftest_map', '' );
if ( preg_match( '/^[a-f0-9]{24}$/', $sfp_zt_geheim ) ) {
    $sfp_zt_uploads = wp_upload_dir( null, false );
    $sfp_zt_mappen  = array( dirname( untrailingslashit( ABSPATH ) ) . '/sfp-zelftest-' . $sfp_zt_geheim, trailingslashit( $sfp_zt_uploads['basedir'] ) . 'sfp-zelftest-' . $sfp_zt_geheim );
    foreach ( $sfp_zt_mappen as $sfp_zt_map ) {
    if ( is_dir( $sfp_zt_map ) ) {
        $sfp_zt_items = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $sfp_zt_map, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
        foreach ( $sfp_zt_items as $sfp_zt_item ) {
            if ( $sfp_zt_item->isDir() ) {
                rmdir( $sfp_zt_item->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
            } else {
                wp_delete_file( $sfp_zt_item->getPathname() );
            }
        }
        rmdir( $sfp_zt_map ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
    }
    }
}
foreach ( array( 'sfp_zelftest', 'sfp_zelftest_db', 'sfp_zelftest_map', 'sfp_zelftest_log' ) as $sfp_zt_optie ) {
    delete_option( $sfp_zt_optie );
}
foreach ( array( 'sfp_zelftest_wachtrij', 'sfp_zelftest_dagelijks' ) as $sfp_zt_haak ) {
    wp_clear_scheduled_hook( $sfp_zt_haak );
}
