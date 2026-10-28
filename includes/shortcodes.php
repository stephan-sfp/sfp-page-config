<?php
/**
 * SFP Page Config - Shortcodes
 *
 * [training_naam]  - Returns the training name from post meta.
 * [cursus_datum]   - Flexible date display from sfp_cursusdata JSON,
 *                    with fallback to legacy startdatum field. Only future
 *                    groups (first day today or later) since 3.17.0.
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
 *   intro     - Text shown before the dates, only when there are dates.
 *
 * Only groups whose first day is today or later are shown (3.17.0). Without
 * such a group a training page shows the waitlist text (includes/wachtlijst.php).
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
        'intro'     => '',
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

    // Alleen groepen waarvan de eerste dag vandaag of later is
    // (includes/wachtlijst.php). Verlopen groepen staan nergens meer.
    $data = sfp_page_config_cursusdata_toekomst( $post_id );

    if ( ! empty( $data ) && is_array( $data ) ) {

        // Optionally filter to a single groep.
        if ( '' !== $atts['groep'] ) {
            $idx  = intval( $atts['groep'] ) - 1;
            if ( ! isset( $data[ $idx ] ) ) {
                // Er is wel een toekomstige groep, alleen niet deze.
                return '';
            }
            $data = array( $data[ $idx ] );
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
                $formatted = sfp_page_config_format_datum( $d, $atts['format'] );
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
                    . '<span class="cursus-datum-label">' . esc_html( sfp_page_config_tekst_f( 'groep', $label_num ) ) . ':</span> '
                    . $dates_str
                    . '</div>';
            } else {
                $output_parts[] = $dates_str;
            }
        }

        if ( ! empty( $output_parts ) ) {
            // De intro staat er alleen met data, zodat een vraag als "Wanneer
            // start de volgende groep?" zonder data niet tegenstrijdig wordt.
            $intro = '' !== trim( (string) $atts['intro'] ) ? '<span class="cursus-datum-intro">' . esc_html( $atts['intro'] ) . '</span><br><br>' : '';
            return $intro . '<div class="cursus-data-wrapper">' . implode( '', $output_parts ) . '</div>';
        }
    }

    // Geen toekomstige groep. Op een trainingspagina de wachtlijsttekst (het
    // oude veld startdatum telt al mee in sfp_page_config_cursusdata_toekomst()).
    if ( 'training' === get_post_meta( $post_id, 'sfp_page_type', true ) && sfp_page_config_onderdeel_aan( 'cursusdata' ) ) {
        return sfp_page_config_wachtlijst_html();
    }
    return '';
}

/* =========================================================================
 * [cursus_tijd]
 * ====================================================================== */

// Only register if the shortcode hasn't already been registered by ASE.
if ( ! shortcode_exists( 'cursus_tijd' ) ) {
    add_shortcode( 'cursus_tijd', 'sfp_page_config_shortcode_cursus_tijd' );
}

/**
 * Shortcode: [cursus_tijd]
 *
 * Renders the lesson time(s) for a training page, read from the optional
 * 'start'/'eind' keys per startmoment in the sfp_cursusdata JSON. Mirrors
 * [cursus_datum]: same post-id resolution, request-level caching and Dutch
 * output.
 *
 * Parameters:
 *   post_id   - Defaults to current queried object (works in SureForms).
 *   groep     - 1-based index to show a single groep's time only.
 *   separator - Between start and end time. Default: ' - '.
 *   suffix    - Trailing text after the time. Default: the site's own text
 *               (Dutch: ' uur', see includes/teksten.php). Use suffix=""
 *               to omit it.
 *   layout    - 'inline' (default) or 'list' (each groep on its own line with
 *               a "Groep 1:" label, useful when times differ).
 *   fallback  - Text to show when no per-startmoment time exists. When empty,
 *               the site-wide default lestijd from the Instellingen tab is
 *               used; if that is also empty, nothing is rendered.
 *
 * Behaviour:
 *   - When every groep shares the same time, a single line is returned so a
 *     hero shows "09:00 - 17:00 uur" once.
 *   - When times differ, layout=list labels each groep; inline joins the
 *     distinct times with ' / '.
 *
 * @param  array $atts Shortcode attributes.
 * @return string      Formatted HTML.
 */
function sfp_page_config_shortcode_cursus_tijd( $atts ) {

    $atts = shortcode_atts( array(
        'post_id'   => '',
        'groep'     => '',
        'separator' => ' - ',
        // Niet opgegeven: de tekst van de site (Nederlands: " uur").
        'suffix'    => null,
        'layout'    => 'inline',
        'fallback'  => '',
    ), $atts, 'cursus_tijd' );

    // Resolve post ID: explicit param > main query > get_the_ID().
    if ( '' !== $atts['post_id'] ) {
        $post_id = intval( $atts['post_id'] );
    } else {
        $queried = get_queried_object_id();
        $post_id = $queried ? $queried : intval( get_the_ID() );
    }

    // Dezelfde groepen en nummering als [cursus_datum]: alleen groepen
    // waarvan de eerste dag vandaag of later is.
    $data = sfp_page_config_cursusdata_toekomst( $post_id );

    $sep    = $atts['separator'];
    $suffix = $atts['suffix'];

    // Collect formatted times per groep (1-based) for groups that have one.
    $times = array();
    if ( ! empty( $data ) && is_array( $data ) ) {
        foreach ( $data as $i => $sm ) {
            $start     = isset( $sm['start'] ) ? $sm['start'] : '';
            $eind      = isset( $sm['eind'] ) ? $sm['eind'] : '';
            $formatted = sfp_page_config_format_lestijd( $start, $eind, $sep, $suffix );
            if ( '' !== $formatted ) {
                $times[ $i + 1 ] = $formatted;
            }
        }
    }

    // Single groep requested.
    if ( '' !== $atts['groep'] ) {
        $num = intval( $atts['groep'] );
        if ( isset( $times[ $num ] ) ) {
            return '<span class="cursus-tijd">' . esc_html( $times[ $num ] ) . '</span>';
        }
        // No time for that groep: fall through to the fallback logic.
        $times = array();
    }

    if ( ! empty( $times ) ) {

        $unique = array_values( array_unique( $times ) );

        // All groups share one time: render it once.
        if ( 1 === count( $unique ) ) {
            return '<span class="cursus-tijd">' . esc_html( $unique[0] ) . '</span>';
        }

        // Distinct times, per-groep layout.
        if ( 'list' === $atts['layout'] ) {
            $parts = array();
            foreach ( $times as $num => $t ) {
                $parts[] = '<div class="cursus-tijd-groep">'
                    . '<span class="cursus-tijd-label">' . esc_html( sfp_page_config_tekst_f( 'groep', intval( $num ) ) ) . ':</span> '
                    . '<span class="cursus-tijd">' . esc_html( $t ) . '</span>'
                    . '</div>';
            }
            return '<div class="cursus-tijd-wrapper">' . implode( '', $parts ) . '</div>';
        }

        // Distinct times, inline: join the unique values.
        $spans = array();
        foreach ( $unique as $t ) {
            $spans[] = '<span class="cursus-tijd">' . esc_html( $t ) . '</span>';
        }
        return implode( ' / ', $spans );
    }

    // No per-startmoment time. Fallback order: explicit param > site default.
    if ( '' !== $atts['fallback'] ) {
        return '<span class="cursus-tijd">' . esc_html( $atts['fallback'] ) . '</span>';
    }

    $def_start = function_exists( 'sfp_page_config_get_setting' ) ? sfp_page_config_get_setting( 'cursus_tijd_default_start', '' ) : '';
    $def_eind  = function_exists( 'sfp_page_config_get_setting' ) ? sfp_page_config_get_setting( 'cursus_tijd_default_eind', '' ) : '';
    $def       = sfp_page_config_format_lestijd( $def_start, $def_eind, $sep, $suffix );

    return '' !== $def
        ? '<span class="cursus-tijd">' . esc_html( $def ) . '</span>'
        : '';
}

