<?php
/**
 * SFP Page Config - Date Injector
 *
 * Injects two JavaScript objects on training pages:
 *
 *   1. window.sfpCoursePromo  - Next upcoming course date (for Convert Pro
 *      popups/banners).
 *   2. window.sfpCourseData   - All startmomenten with formatted dates for
 *      the current page (for SureForms dropdown population).
 *
 * Also enqueues a small script that auto-populates any <select> with class
 * `sfp-startmoment-select` from window.sfpCourseData.
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
    if ( ! is_singular( sfp_page_config_post_types() ) ) {
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

    $json = get_post_meta( $post_id, 'sfp_cursusdata', true );
    $data = $json ? json_decode( $json, true ) : array();
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        $data = array();
    }

    $startmomenten = array();

    if ( ! empty( $data ) && is_array( $data ) ) {
        foreach ( $data as $index => $sm ) {
            if ( empty( $sm['data'] ) || ! is_array( $sm['data'] ) ) {
                continue;
            }

            $dates_formatted = array();
            foreach ( $sm['data'] as $d ) {
                $f = sfp_page_config_format_date_nl( $d, 'short' );
                if ( $f ) {
                    $dates_formatted[] = $f;
                }
            }

            if ( ! empty( $dates_formatted ) ) {
                $startmomenten[] = array(
                    'index' => $index + 1,
                    'label' => 'Groep ' . ( $index + 1 ) . ': ' . implode( ' • ', $dates_formatted ),
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

    /* -----------------------------------------------------------------
     * 2. sfpCoursePromo: next upcoming course date (site-wide).
     *    Used by Convert Pro popups and banners.
     * --------------------------------------------------------------- */

    $cache_key = 'sfp_next_course_' . $post_id;
    $next_course = get_transient( $cache_key );

    if ( false === $next_course ) {
        $courses = get_posts( array(
            'post_type'      => 'page',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_key'       => 'startdatum',
            'meta_value'     => current_time( 'Y-m-d' ),
            'meta_compare'   => '>=',
            'meta_type'      => 'DATE',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
        ) );

        $next_course = ! empty( $courses ) ? $courses[0] : 'none';
        set_transient( $cache_key, $next_course ?: 'none', HOUR_IN_SECONDS );
    }

    if ( 'none' === $next_course ) {
        $next_course = false;
    }

    if ( ! empty( $next_course ) ) {
        $promo_post = $next_course;
        $date_raw   = get_post_meta( $promo_post->ID, 'startdatum', true );

        if ( $date_raw ) {
            $output .= sprintf(
                'window.sfpCoursePromo=%s;',
                wp_json_encode( array(
                    'title'         => get_the_title( $promo_post->ID ),
                    'url'           => get_permalink( $promo_post->ID ),
                    'dateRaw'       => $date_raw,
                    'dateFormatted' => sfp_page_config_format_date_nl( $date_raw, 'short' ),
                    'postId'        => $promo_post->ID,
                ) )
            );
        }
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
    if ( 'training' !== $type ) {
        return;
    }

    wp_enqueue_script(
        'sfp-page-config-dropdown',
        SFP_PAGE_CONFIG_URL . 'assets/dropdown.js',
        array(),
        SFP_PAGE_CONFIG_VERSION,
        true
    );

    // Pass brand colour to JS for the no-data fallback message.
    $brand = sfp_page_config_get_brand();
    wp_localize_script( 'sfp-page-config-dropdown', 'sfpDropdownConfig', array(
        'ctaColor' => $brand['cta_bg'],
    ) );
}
