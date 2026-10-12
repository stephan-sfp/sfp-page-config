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
