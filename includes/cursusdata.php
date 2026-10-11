<?php
/**
 * SFP Page Config - Cursusdata bewaren
 *
 * De startmomenten van een training staan als JSON in de meta
 * `sfp_cursusdata`: [{data:[Y-m-d], start, eind}]. Dit bestand schoont ze op
 * en bewaart ze, voor het beheer (REST, includes/beheer.php) en voor de oude
 * AJAX-aanroep `sfp_save_cursusdata`.
 *
 * Tot en met 2.12.0 stond dit in includes/dashboard.php, samen met de oude
 * tabbladen. Die tabbladen zijn in 3.0.0 vervangen door SFP Beheer.
 *
 * Let op bij een nieuw veld per startmoment: sfp_page_config_cursusdata_schoon()
 * herbouwt de lijst en gooit alles weg wat het niet kent.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Lijst van trainingspagina's
 * ====================================================================== */

add_action( 'save_post_page', 'sfp_page_config_cursusdata_wis_lijst' );

/**
 * De lijst met trainingspagina's kan na een opslag anders zijn.
 */
function sfp_page_config_cursusdata_wis_lijst() {
    delete_transient( 'sfp_dashboard_pages_training' );
    // Oude sleutel van voor 1.9.10.
    delete_transient( 'sfp_dashboard_pages' );
}

/**
 * Alle gepubliceerde trainingspagina's met hun startmomenten.
 *
 * @return array<int, array{id: int, titel: string, link: string, bewerk: string, data: array}>
 */
function sfp_page_config_cursusdata_paginas() {
    $ids = get_transient( 'sfp_dashboard_pages_training' );
    if ( ! is_array( $ids ) || ( $ids && ! is_int( reset( $ids ) ) ) ) {
        $ids = get_posts(
            array(
                'post_type'      => 'page',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
                'fields'         => 'ids',
                'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- zes uur bewaard.
                    array(
                        'key'   => 'sfp_page_type',
                        'value' => 'training',
                    ),
                ),
            )
        );
        $ids = array_map( 'intval', $ids );
        set_transient( 'sfp_dashboard_pages_training', $ids, 6 * HOUR_IN_SECONDS );
    }

    $uit = array();
    foreach ( $ids as $id ) {
        $json = get_post_meta( $id, 'sfp_cursusdata', true );
        $data = $json ? json_decode( $json, true ) : array();
        $uit[] = array(
            'id'     => (int) $id,
            'titel'  => html_entity_decode( get_the_title( $id ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
            'link'   => (string) get_permalink( $id ),
            'bewerk' => (string) get_edit_post_link( $id, 'raw' ),
            'data'   => is_array( $data ) ? array_values( $data ) : array(),
        );
    }
    return $uit;
}

/* =========================================================================
 * Opschonen en bewaren
 * ====================================================================== */

/**
 * Schoon een lijst startmomenten op: lege en ongeldige dagen eruit, dagen op
 * volgorde, hoogstens tien dagen per startmoment, startmomenten op volgorde
 * van hun eerste dag.
 *
 * @param  mixed $data Ruwe lijst.
 * @return array
 */
function sfp_page_config_cursusdata_schoon( $data ) {
    $clean = array();
    if ( ! is_array( $data ) ) {
        return $clean;
    }
    foreach ( $data as $sm ) {
        if ( ! is_array( $sm ) || ! isset( $sm['data'] ) || ! is_array( $sm['data'] ) ) {
            continue;
        }
        $dates = array();
        foreach ( $sm['data'] as $d ) {
            $d = sanitize_text_field( (string) $d );
            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) && strtotime( $d ) ) {
                $dates[] = $d;
            }
        }
        if ( empty( $dates ) ) {
            continue;
        }
        $dates = array_slice( array_values( array_unique( $dates ) ), 0, 10 );
        sort( $dates );
        $group = array( 'data' => $dates );

        // De lestijd per startmoment, als "HH:MM". Leeg of ongeldig valt weg,
        // zodat de code voor de lestijd schoon terugvalt.
        $start = isset( $sm['start'] ) ? sfp_page_config_sanitize_time( $sm['start'] ) : '';
        $eind  = isset( $sm['eind'] ) ? sfp_page_config_sanitize_time( $sm['eind'] ) : '';
        if ( '' !== $start ) {
            $group['start'] = $start;
        }
        if ( '' !== $eind ) {
            $group['eind'] = $eind;
        }
        $clean[] = $group;
    }

    usort(
        $clean,
        function ( $a, $b ) {
            return strcmp( $a['data'][0], $b['data'][0] );
        }
    );
    return $clean;
}

/**
 * Bewaar de startmomenten van één pagina.
 *
 * @param  int   $post_id Pagina.
 * @param  mixed $data    Ruwe lijst startmomenten.
 * @return array{clean: array, eerstvolgende: string}
 */
function sfp_page_config_cursusdata_bewaar( $post_id, $data ) {
    $clean = sfp_page_config_cursusdata_schoon( $data );

    if ( ! empty( $clean ) ) {
        update_post_meta( $post_id, 'sfp_cursusdata', wp_slash( wp_json_encode( $clean ) ) );
    } else {
        delete_post_meta( $post_id, 'sfp_cursusdata' );
    }

    // Het oude veld `startdatum` volgt de eerstvolgende dag.
    $today   = wp_date( 'Y-m-d' );
    $closest = null;
    foreach ( $clean as $sm ) {
        foreach ( $sm['data'] as $d ) {
            if ( $d >= $today && ( null === $closest || $d < $closest ) ) {
                $closest = $d;
            }
        }
    }
    if ( $closest ) {
        update_post_meta( $post_id, 'startdatum', $closest );
    } else {
        delete_post_meta( $post_id, 'startdatum' );
    }

    sfp_page_config_cursusdata_wis_lijst();

    // De dagelijkse controle begint voor deze training opnieuw (includes/cron.php).
    do_action( 'sfp_page_config_cursusdata_updated', $post_id );

    return array(
        'clean'         => $clean,
        'eerstvolgende' => $closest ? sfp_page_config_format_date_nl( $closest, 'short' ) : '-',
    );
}

/* =========================================================================
 * AJAX (blijft bestaan voor oude aanroepen)
 * ====================================================================== */

// Alleen als een oud ASE-snippet deze actie niet al afhandelt.
if ( ! has_action( 'wp_ajax_sfp_save_cursusdata' ) ) {
    add_action( 'wp_ajax_sfp_save_cursusdata', 'sfp_page_config_ajax_save' );
}

/**
 * AJAX: bewaar de startmomenten van één pagina.
 */
function sfp_page_config_ajax_save() {

    check_ajax_referer( 'sfp_cursusdata_nonce', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) {
        wp_send_json_error( 'Ongeldig post ID.' );
    }
    if ( ! current_user_can( 'edit_page', $post_id ) ) {
        wp_send_json_error( 'Geen rechten.' );
    }

    $raw    = isset( $_POST['cursusdata'] ) ? wp_unslash( $_POST['cursusdata'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- opgeschoond in sfp_page_config_cursusdata_schoon().
    $result = sfp_page_config_cursusdata_bewaar( $post_id, json_decode( (string) $raw, true ) );

    wp_send_json_success(
        array(
            'clean'         => $result['clean'],
            'cursusdata'    => $result['clean'],
            'eerstvolgende' => $result['eerstvolgende'],
        )
    );
}
