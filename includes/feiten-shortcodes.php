<?php
/**
 * Shortcodes voor de feitelijke verantwoording.
 *
 * [sfp_reviews]      Google-beoordeling van het eigen bedrijfsprofiel, met reviewaantal.
 * [sfp_publicaties]  Aantal artikelen in de kennisbanken van het netwerk.
 * [sfp_stand]        Wijzigingsdatum van de pagina zelf.
 *
 * Elke shortcode geeft een leesbare terugvalzin als de bron leeg is, zodat er
 * nooit een rauwe shortcode of een leeg vlak op de pagina staat.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * [sfp_reviews]
 * ====================================================================== */

/**
 * Leest de beoordeling van het eigen Google-bedrijfsprofiel.
 *
 * Bron in volgorde: het filter sfp_feiten_reviews, daarna de opgeslagen data
 * van SFP Google Reviews. Geeft array( rating, count ) of een lege array.
 *
 * @return array
 */
function sfp_feiten_reviews_data() {
    $data = apply_filters( 'sfp_feiten_reviews', array() );

    if ( ! empty( $data['rating'] ) ) {
        return $data;
    }

    $kandidaten = array(
        'sfp_google_reviews_data',
        'sfp_google_reviews_cache',
        'sfp_gr_reviews_data',
        'sfp_gr_cache',
    );

    foreach ( $kandidaten as $optie ) {
        $waarde = get_option( $optie );

        if ( empty( $waarde ) ) {
            continue;
        }

        if ( is_string( $waarde ) ) {
            $waarde = json_decode( $waarde, true );
        }

        if ( ! is_array( $waarde ) ) {
            continue;
        }

        $rating = 0;
        $aantal = 0;

        foreach ( array( 'rating', 'average', 'average_rating', 'score' ) as $sleutel ) {
            if ( isset( $waarde[ $sleutel ] ) && is_numeric( $waarde[ $sleutel ] ) ) {
                $rating = (float) $waarde[ $sleutel ];
                break;
            }
        }

        foreach ( array( 'user_ratings_total', 'total', 'count', 'reviews_count' ) as $sleutel ) {
            if ( isset( $waarde[ $sleutel ] ) && is_numeric( $waarde[ $sleutel ] ) ) {
                $aantal = (int) $waarde[ $sleutel ];
                break;
            }
        }

        if ( $rating > 0 ) {
            return array(
                'rating' => $rating,
                'count'  => $aantal,
            );
        }
    }

    return array();
}

/**
 * Rendert de Google-beoordeling met het reviewaantal erbij.
 *
 * @return string
 */
function sfp_page_config_shortcode_reviews() {
    $data = sfp_feiten_reviews_data();

    if ( empty( $data['rating'] ) ) {
        return esc_html__( 'ons actuele cijfer lees je op ons Google-bedrijfsprofiel', 'sfp-page-config' );
    }

    $rating = number_format_i18n( (float) $data['rating'], 1 );
    $aantal = isset( $data['count'] ) ? (int) $data['count'] : 0;

    if ( $aantal < 1 ) {
        return esc_html( $rating );
    }

    /* translators: 1: cijfer, 2: aantal reviews. */
    return esc_html(
        sprintf(
            _n( '%1$s op basis van %2$s review', '%1$s op basis van %2$s reviews', $aantal, 'sfp-page-config' ),
            $rating,
            number_format_i18n( $aantal )
        )
    );
}
add_shortcode( 'sfp_reviews', 'sfp_page_config_shortcode_reviews' );

/* =========================================================================
 * [sfp_publicaties]
 * ====================================================================== */

/**
 * Telt de artikelen in de kennisbanken van het netwerk.
 *
 * Telt de eigen site met een directe query en de andere netwerksites via hun
 * REST API. De uitkomst staat 24 uur in een transient, zodat een paginaweergave
 * nooit op een externe call wacht die niet gecacht is.
 *
 * @return int
 */
function sfp_feiten_publicaties_aantal() {
    $cache = get_transient( 'sfp_feiten_publicaties' );

    if ( false !== $cache ) {
        return (int) $cache;
    }

    $eigen = (int) wp_count_posts( 'post' )->publish;

    $netwerk = apply_filters(
        'sfp_feiten_publicaties_sites',
        array(
            'https://depresenteerschool.nl',
            'https://degespreksacademie.nl',
            'https://centrumvoordidactiek.nl',
            'https://deschrijftrainers.nl',
            'https://schoolforprofessionals.com',
        )
    );

    $eigen_host = wp_parse_url( home_url(), PHP_URL_HOST );
    $totaal     = $eigen;

    foreach ( $netwerk as $basis ) {
        if ( wp_parse_url( $basis, PHP_URL_HOST ) === $eigen_host ) {
            continue;
        }

        $antwoord = wp_remote_get(
            $basis . '/wp-json/wp/v2/posts?per_page=1&status=publish&_fields=id',
            array( 'timeout' => 5 )
        );

        if ( is_wp_error( $antwoord ) ) {
            continue;
        }

        $aantal = (int) wp_remote_retrieve_header( $antwoord, 'x-wp-total' );

        if ( $aantal > 0 ) {
            $totaal += $aantal;
        }
    }

    set_transient( 'sfp_feiten_publicaties', $totaal, DAY_IN_SECONDS );

    return $totaal;
}

/**
 * Rendert het aantal artikelen in de kennisbanken van het netwerk.
 *
 * @return string
 */
function sfp_page_config_shortcode_publicaties() {
    $aantal = sfp_feiten_publicaties_aantal();

    if ( $aantal < 1 ) {
        return esc_html__( 'ruim 200', 'sfp-page-config' );
    }

    return esc_html( number_format_i18n( $aantal ) );
}
add_shortcode( 'sfp_publicaties', 'sfp_page_config_shortcode_publicaties' );

/* =========================================================================
 * [sfp_stand]
 * ====================================================================== */

/**
 * Rendert de wijzigingsdatum van de huidige pagina.
 *
 * @return string
 */
function sfp_page_config_shortcode_stand() {
    $post_id = get_queried_object_id();

    if ( ! $post_id ) {
        return esc_html( wp_date( 'j F Y' ) );
    }

    $datum = get_post_modified_time( 'U', false, $post_id );

    if ( ! $datum ) {
        $datum = get_post_time( 'U', false, $post_id );
    }

    if ( ! $datum ) {
        return esc_html( wp_date( 'j F Y' ) );
    }

    return esc_html( wp_date( 'j F Y', $datum ) );
}
add_shortcode( 'sfp_stand', 'sfp_page_config_shortcode_stand' );
