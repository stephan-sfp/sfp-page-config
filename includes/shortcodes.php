<?php
/**
 * SFP Page Config - Shortcodes
 *
 * [training_naam]  - Returns the training name from post meta.
 * [cursus_datum]   - Flexible date display from sfp_cursusdata JSON,
 *                    with fallback to legacy startdatum field.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * [training_naam]
 * ====================================================================== */

add_shortcode( 'training_naam', 'sfp_page_config_shortcode_training_naam' );

/**
 * Shortcode: [training_naam]
 *
 * @return string The training name or empty string.
 */
function sfp_page_config_shortcode_training_naam() {
    return esc_html( get_post_meta( get_the_ID(), 'training_naam', true ) );
}

/* =========================================================================
 * [cursus_datum]
 * ====================================================================== */

// Only register if the shortcode hasn't already been registered by ASE.
if ( ! shortcode_exists( 'cursus_datum' ) ) {
    add_shortcode( 'cursus_datum', 'sfp_page_config_shortcode_cursus_datum' );
}

/**
 * Shortcode: [cursus_datum]
 *
 * Parameters:
 *   post_id   - Defaults to current queried object (works in SureForms).
 *   format    - PHP date format, defaults to 'l j F Y' (e.g. maandag 1 juli 2026).
 *               Common alternatives: 'j F Y', 'j F', 'D j F Y'.
 *   groep     - 1-based index to show a single groep only.
 *   separator - Between dates within one groep. Default: ' &bull; '.
 *   show      - 'all' (default) or 'first' (only the first date per groep).
 *   layout    - 'inline' (default) or 'list' (each groep on its own line
 *               with a label like "Groep 1:").
 *
 * Outputs Dutch day and month names regardless of WP locale setting.
 *
 * Examples:
 *   [cursus_datum]
 *     All groups, all dates, bullet-separated, inline.
 *
 *   [cursus_datum layout="list"]
 *     All groups under each other with "Groep 1:" labels.
 *
 *   [cursus_datum groep="1" show="first" format="j F Y"]
 *     Only the first date of groep 1 without day name.
 *
 *   [cursus_datum groep="2" separator=" | "]
 *     All dates of groep 2, pipe-separated.
 *
 * @param  array $atts Shortcode attributes.
 * @return string      Formatted HTML.
 */
function sfp_page_config_shortcode_cursus_datum( $atts ) {

    $atts = shortcode_atts( array(
        'post_id'   => '',
        'format'    => 'l j F Y',
        'groep'     => '',
        'separator' => ' &bull; ',
        'show'      => 'all',
        'layout'    => 'inline',
    ), $atts, 'cursus_datum' );

    // Backwards compatibility: accept old 'startmoment' attribute as alias.
    if ( empty( $atts['groep'] ) && ! empty( $atts['startmoment'] ) ) {
        $atts['groep'] = $atts['startmoment'];
    }

    // Resolve post ID: explicit param > main query (works inside SureForms/
    // reusable blocks) > get_the_ID() as last resort.
    if ( '' !== $atts['post_id'] ) {
        $post_id = intval( $atts['post_id'] );
    } else {
        $queried = get_queried_object_id();
        $post_id = $queried ? $queried : intval( get_the_ID() );
    }

    // Request-level caching for cursusdata meta lookups.
    static $cache = array();
    if ( isset( $cache[ $post_id ] ) ) {
        $data = $cache[ $post_id ];
    } else {
        $json = get_post_meta( $post_id, 'sfp_cursusdata', true );
        $data = $json ? json_decode( $json, true ) : array();
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $data = array();
        }
        $cache[ $post_id ] = $data;
    }

    if ( ! empty( $data ) && is_array( $data ) ) {

        // Optionally filter to a single groep.
        if ( '' !== $atts['groep'] ) {
            $idx  = intval( $atts['groep'] ) - 1;
            $data = isset( $data[ $idx ] ) ? array( $data[ $idx ] ) : array();
        }

        $output_parts = array();
        $total_groups = count( $data );

        foreach ( $data as $i => $sm ) {
            if ( empty( $sm['data'] ) ) {
                continue;
            }

            $dates_to_show = $sm['data'];

            // Show only the first date if requested.
            if ( 'first' === $atts['show'] ) {
                $dates_to_show = array( $dates_to_show[0] );
            }

            $dates_html = array();
            foreach ( $dates_to_show as $d ) {
                $formatted = sfp_page_config_format_date_nl( $d, $atts['format'] );
                if ( $formatted ) {
                    $dates_html[] = '<span class="cursus-datum">' . esc_html( $formatted ) . '</span>';
                }
            }

            if ( empty( $dates_html ) ) {
                continue;
            }

            $dates_str = implode( $atts['separator'], $dates_html );

            // Add group label when showing multiple groups OR layout=list.
            if ( $total_groups > 1 || 'list' === $atts['layout'] ) {
                $label_num = ( '' !== $atts['groep'] )
                    ? intval( $atts['groep'] )
                    : intval( $i + 1 );

                $output_parts[] = '<div class="cursus-datum-groep">'
                    . '<span class="cursus-datum-label">Groep ' . $label_num . ':</span> '
                    . $dates_str
                    . '</div>';
            } else {
                $output_parts[] = $dates_str;
            }
        }

        if ( ! empty( $output_parts ) ) {
            return '<div class="cursus-data-wrapper">' . implode( '', $output_parts ) . '</div>';
        }
    }

    // Fallback: legacy startdatum field.
    $datum_raw = get_post_meta( $post_id, 'startdatum', true );
    if ( empty( $datum_raw ) ) {
        return '';
    }

    $formatted = sfp_page_config_format_date_nl( $datum_raw, $atts['format'] );
    return $formatted
        ? '<span class="cursus-datum">' . esc_html( $formatted ) . '</span>'
        : '';
}

