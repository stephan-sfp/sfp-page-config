<?php
/**
 * Shortcodes voor de feitelijke verantwoording.
 *
 * [sfp_reviews]      Google-beoordeling van het eigen bedrijfsprofiel, met reviewaantal
 *                    (uit SFP Google Reviews, met de laatst bekende meting als vangnet).
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
 * Optienaam voor de laatst bekende geldige meting van de Google-beoordeling.
 */
if ( ! defined( 'SFP_FEITEN_REVIEWS_OPTIE' ) ) {
    define( 'SFP_FEITEN_REVIEWS_OPTIE', 'sfp_feiten_reviews_laatste' );
}

/**
 * Leest de actuele profieltotalen uit de cache van SFP Google Reviews.
 *
 * SFP Google Reviews bewaart de place_id in de optie sfp_reviews_options en
 * de opgehaalde data in de transient 'sfp_reviews_' . md5( place_id ). Daarin
 * staan onder ['meta'] de totalen van het Google-bedrijfsprofiel: rating en
 * total. ['reviews'] bevat alleen de gefilterde reviews en is dus nooit een
 * bron voor het cijfer of het aantal.
 *
 * @return array array( rating, count ) of een lege array.
 */
function sfp_feiten_reviews_uit_transient() {
    $opties = get_option( 'sfp_reviews_options' );

    if ( ! is_array( $opties ) || empty( $opties['place_id'] ) || ! is_string( $opties['place_id'] ) ) {
        return array();
    }

    $cache = get_transient( 'sfp_reviews_' . md5( $opties['place_id'] ) );

    if ( ! is_array( $cache ) || empty( $cache['meta'] ) || ! is_array( $cache['meta'] ) ) {
        return array();
    }

    $meta = $cache['meta'];

    if ( ! isset( $meta['rating'] ) || ! is_numeric( $meta['rating'] ) ) {
        return array();
    }

    $rating = (float) $meta['rating'];
    $aantal = ( isset( $meta['total'] ) && is_numeric( $meta['total'] ) ) ? (int) $meta['total'] : 0;

    if ( $rating <= 0 || $rating > 5 || $aantal < 1 ) {
        return array();
    }

    return array(
        'rating' => $rating,
        'count'  => $aantal,
    );
}

/**
 * Bewaart een geldige meting als laatst bekende waarde.
 *
 * Schrijft alleen als cijfer of aantal veranderd is, of als de vorige meting
 * van een andere dag is, zodat een paginaweergave niet elke keer de database
 * raakt.
 *
 * @param array $data array( rating, count ).
 * @return void
 */
function sfp_feiten_reviews_bewaar_meting( $data ) {
    $vorige  = get_option( SFP_FEITEN_REVIEWS_OPTIE );
    $vandaag = wp_date( 'Y-m-d' );

    if (
        is_array( $vorige )
        && isset( $vorige['rating'], $vorige['count'], $vorige['datum'] )
        && (float) $vorige['rating'] === (float) $data['rating']
        && (int) $vorige['count'] === (int) $data['count']
        && wp_date( 'Y-m-d', (int) $vorige['datum'] ) === $vandaag
    ) {
        return;
    }

    update_option(
        SFP_FEITEN_REVIEWS_OPTIE,
        array(
            'rating' => (float) $data['rating'],
            'count'  => (int) $data['count'],
            'datum'  => time(),
        ),
        false
    );
}

/**
 * Leest de beoordeling van het eigen Google-bedrijfsprofiel.
 *
 * Bron in volgorde:
 * 1. het filter sfp_feiten_reviews (override);
 * 2. de transient van SFP Google Reviews (meta.rating en meta.total);
 * 3. de laatst bekende geldige meting, als de transient verlopen is.
 *
 * @return array array( rating, count ) of een lege array als er nooit gemeten is.
 */
function sfp_feiten_reviews_data() {
    $data = apply_filters( 'sfp_feiten_reviews', array() );

    if ( is_array( $data ) && ! empty( $data['rating'] ) ) {
        return $data;
    }

    $actueel = sfp_feiten_reviews_uit_transient();

    if ( ! empty( $actueel ) ) {
        sfp_feiten_reviews_bewaar_meting( $actueel );
        return $actueel;
    }

    $laatste = get_option( SFP_FEITEN_REVIEWS_OPTIE );

    if ( is_array( $laatste ) && ! empty( $laatste['rating'] ) && ! empty( $laatste['count'] ) ) {
        return array(
            'rating' => (float) $laatste['rating'],
            'count'  => (int) $laatste['count'],
        );
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
