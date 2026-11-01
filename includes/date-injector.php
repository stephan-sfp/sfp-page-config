<?php
/**
 * SFP Page Config - Date Injector
 *
 * Injects window.sfpCourseData on training pages: all startmomenten with
 * formatted dates for the current page (for SureForms dropdown population).
 * window.sfpCoursePromo (for Convert Pro) is gone since 3.22.0: Convert Pro
 * runs on none of the eight sites and no snippet reads it (31-10-2026).
 *
 * Also enqueues a small script that fills the dropdown with the startmomenten
 * from window.sfpCourseData. Since 3.0.0 the script finds the dropdown itself;
 * the class `sfp-startmoment-select` on the form field still works but is no
 * longer needed.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* =========================================================================
 * Inject data in wp_footer
 * ====================================================================== */

// Only register if ASE snippet hasn't already hooked a similar function.
if ( ! has_action( 'wp_footer', 'sfp_page_config_inject_course_data' ) && ! has_action( 'wp_footer', 'sfp_inject_course_data' ) ) {
    add_action( 'wp_footer', 'sfp_page_config_inject_course_data', 5 );
}

/**
 * Output <script> tags with course data on training pages.
 */
function sfp_page_config_inject_course_data() {

    // Only inject on training pages.
    if ( ! is_singular( sfp_page_config_post_types() ) || ! sfp_page_config_onderdeel_aan( 'cursusdata' ) ) {
        return;
    }

    $post_id = get_queried_object_id();
    $type    = get_post_meta( $post_id, 'sfp_page_type', true );

    if ( 'training' !== $type ) {
        return;
    }

    $output = '';

    /* -----------------------------------------------------------------
     * 1. sfpCourseData: all startmomenten for THIS page.
     *    Used by the dropdown populator script.
     * --------------------------------------------------------------- */

    // Alleen groepen waarvan de eerste dag vandaag of later is, genummerd
    // zoals [cursus_datum] ze toont (includes/wachtlijst.php).
    $data = sfp_page_config_cursusdata_toekomst( $post_id );

    $startmomenten = array();

    if ( ! empty( $data ) && is_array( $data ) ) {
        foreach ( $data as $index => $sm ) {
            if ( empty( $sm['data'] ) || ! is_array( $sm['data'] ) ) {
                continue;
            }

            $dates_formatted = array();
            foreach ( $sm['data'] as $d ) {
                $f = sfp_page_config_format_datum( $d, 'short' );
                if ( $f ) {
                    $dates_formatted[] = $f;
                }
            }

            if ( ! empty( $dates_formatted ) ) {
                // De lestijd van dit startmoment, als die is ingevuld. De
                // standaardtijd van de site komt hier niet: die staat al op de pagina.
                $tijd = sfp_page_config_format_lestijd(
                    isset( $sm['start'] ) ? $sm['start'] : '',
                    isset( $sm['eind'] ) ? $sm['eind'] : ''
                );
                $startmomenten[] = array(
                    'index' => $index + 1,
                    'label' => sfp_page_config_tekst_f( 'groep', $index + 1 ) . ': ' . implode( ' • ', $dates_formatted ) . ( '' !== $tijd ? ' (' . $tijd . ')' : '' ),
                    'value' => 'groep-' . ( $index + 1 ),
                    'dates' => $dates_formatted,
                );
            }
        }
    }

    if ( ! empty( $startmomenten ) ) {
        $output .= sprintf(
            'window.sfpCourseData=%s;',
            wp_json_encode( $startmomenten )
        );
    }

    if ( $output ) {
        printf( '<script>%s</script>', $output );
    }
}

/* =========================================================================
 * Enqueue the dropdown populator script on training pages
 * ====================================================================== */

add_action( 'wp_enqueue_scripts', 'sfp_page_config_enqueue_dropdown_script' );

/**
 * Enqueue the startmoment dropdown populator on training pages.
 */
function sfp_page_config_enqueue_dropdown_script() {

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return;
    }

    $type = get_post_meta( get_queried_object_id(), 'sfp_page_type', true );
    if ( 'training' !== $type || ! sfp_page_config_onderdeel_aan( 'cursusdata' ) ) {
        return;
    }

    wp_enqueue_script(
        'sfp-page-config-dropdown',
        sfp_page_config_asset_url( 'dropdown.js' ),
        array(),
        SFP_PAGE_CONFIG_VERSION,
        true
    );

    // Pass brand colour to JS for the no-data fallback message.
    $brand = sfp_page_config_get_brand();
    wp_localize_script( 'sfp-page-config-dropdown', 'sfpDropdownConfig', array(
        'ctaColor'  => $brand['cta_bg'],
        // Teksten in de taal van de site (includes/teksten.php).
        'kiesGroep' => sfp_page_config_tekst( 'kies_groep' ),
        'geenData'  => sfp_page_config_tekst( 'geen_data' ),
        'groep'     => sfp_page_config_tekst( 'groep' ),
    ) );
}
