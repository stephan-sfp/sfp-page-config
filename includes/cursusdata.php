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
    delete_option( 'sfp_pc_komende_cursussen' );
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
        $json  = get_post_meta( $id, 'sfp_cursusdata', true );
        $data  = $json ? json_decode( $json, true ) : array();
        $uit[] = array(
            'id'     => (int) $id,
            'titel'  => html_entity_decode( get_the_title( $id ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
            'link'   => (string) get_permalink( $id ),
            'bewerk' => (string) get_edit_post_link( $id, 'raw' ),
            'data'   => is_array( $data ) ? array_values( $data ) : array(),
            'info'   => sfp_page_config_cursusinfo( $id ),
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
            if ( sfp_page_config_geldige_datum( $d ) ) {
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
        // De status van de groep voor de groepskaart (3.30.0): leeg is
        // "nog plekken vrij", verder alleen "laatste" of "vol".
        if ( isset( $sm['status'] ) && in_array( $sm['status'], sfp_page_config_cursus_statussen(), true ) ) {
            $group['status'] = (string) $sm['status'];
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
 * Statussen van een groep naast "nog plekken vrij" (3.30.0).
 *
 * @return string[]
 */
function sfp_page_config_cursus_statussen() {
    return array( 'laatste', 'vol' );
}

/**
 * Plaats en prijs van een training (3.30.0), meta `sfp_cursusinfo`.
 *
 * Prijs in hele euro's exclusief btw; leeg of 0 betekent: geen prijs tonen.
 * Sinds 3.43.0 ook `prijzen`: prijs per aantal dagen, bijvoorbeeld
 * {"1":995,"2":1495,"3":1995}.
 *
 * @param  int $post_id Pagina.
 * @return array{plaats: string, prijs: int, prijzen: array<string, int>}
 */
function sfp_page_config_cursusinfo( $post_id ) {
    $ruw = get_post_meta( (int) $post_id, 'sfp_cursusinfo', true );
    $ruw = is_string( $ruw ) && '' !== $ruw ? json_decode( $ruw, true ) : $ruw;
    return sfp_page_config_cursusinfo_schoon( is_array( $ruw ) ? $ruw : array() );
}

/**
 * Schoon plaats en prijs op.
 *
 * @param  mixed $ruw Ruwe gegevens.
 * @return array{plaats: string, prijs: int}
 */
function sfp_page_config_cursusinfo_schoon( $ruw ) {
    $ruw   = is_array( $ruw ) ? $ruw : array();
    $prijs = isset( $ruw['prijs'] ) ? preg_replace( '/[^0-9]/', '', (string) $ruw['prijs'] ) : '';
    // Sinds 3.43.0: prijs per aantal dagen (cursusvarianten). Sleutel is het
    // aantal dagen (1 tot 10), waarde hele euro's exclusief btw; leeg of 0 valt weg.
    $prijzen = array();
    if ( isset( $ruw['prijzen'] ) && is_array( $ruw['prijzen'] ) ) {
        foreach ( $ruw['prijzen'] as $dagen => $p ) {
            $dagen = (int) $dagen;
            $p     = preg_replace( '/[^0-9]/', '', (string) $p );
            if ( $dagen >= 1 && $dagen <= 10 && '' !== $p && (int) $p > 0 ) {
                $prijzen[ (string) $dagen ] = min( 99999, (int) $p );
            }
        }
        ksort( $prijzen, SORT_NUMERIC );
    }
    return array(
        'plaats'  => isset( $ruw['plaats'] ) ? mb_substr( sanitize_text_field( (string) $ruw['plaats'] ), 0, 120 ) : '',
        'prijs'   => '' === $prijs ? 0 : min( 99999, (int) $prijs ),
        'prijzen' => $prijzen,
    );
}

/**
 * Staan de cursusvarianten aan op deze training (3.43.0)? Dat is zo zodra er
 * een prijs per aantal dagen is ingevuld. Zonder: alles zoals voor 3.43.0.
 *
 * @param  int|array $info Pagina of de cursusinfo zelf.
 * @return bool
 */
function sfp_page_config_cursus_varianten_aan( $info ) {
    if ( ! is_array( $info ) ) {
        $info = sfp_page_config_cursusinfo( (int) $info );
    }
    return ! empty( $info['prijzen'] );
}

/**
 * De prijs van een groep met dit aantal dagen: de prijs voor dat aantal dagen,
 * anders de gewone prijs (3.43.0).
 *
 * @param  array $info  Cursusinfo.
 * @param  int   $dagen Aantal dagen.
 * @return int
 */
function sfp_page_config_cursus_prijs( array $info, $dagen ) {
    $sleutel = (string) (int) $dagen;
    if ( ! empty( $info['prijzen'][ $sleutel ] ) ) {
        return (int) $info['prijzen'][ $sleutel ];
    }
    return isset( $info['prijs'] ) ? (int) $info['prijs'] : 0;
}

/**
 * Bewaar plaats en prijs van een training.
 *
 * @param  int   $post_id Pagina.
 * @param  mixed $ruw     Ruwe gegevens.
 * @return array{plaats: string, prijs: int}
 */
function sfp_page_config_cursusinfo_bewaar( $post_id, $ruw ) {
    $info = sfp_page_config_cursusinfo_schoon( $ruw );
    if ( '' === $info['plaats'] && 0 === $info['prijs'] && ! $info['prijzen'] ) {
        delete_post_meta( (int) $post_id, 'sfp_cursusinfo' );
    } else {
        update_post_meta( (int) $post_id, 'sfp_cursusinfo', wp_slash( wp_json_encode( $info ) ) );
    }
    return $info;
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
        'eerstvolgende' => $closest ? sfp_page_config_format_datum( $closest, 'short' ) : '-',
    );
}

/**
 * Is dit een bestaande datum in de vorm JJJJ-MM-DD? strtotime() neemt ook
 * 2026-02-31 aan (en rolt door naar maart); checkdate() niet.
 *
 * @param  string $d Datum.
 * @return bool
 */
function sfp_page_config_geldige_datum( $d ) {
    if ( ! is_string( $d ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m ) ) {
        return false;
    }
    return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
}
