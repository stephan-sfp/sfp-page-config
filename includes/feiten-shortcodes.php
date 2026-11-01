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
        return esc_html( sfp_page_config_tekst( 'feiten_reviews_geen' ) );
    }

    $rating = number_format_i18n( (float) $data['rating'], 1 );
    $aantal = isset( $data['count'] ) ? (int) $data['count'] : 0;

    if ( $aantal < 1 ) {
        return esc_html( $rating );
    }

    // strtr in plaats van sprintf: een eigen tekst met een verkeerde
    // plaatshouder geeft zo geen fout op de pagina.
    return esc_html(
        strtr(
            sfp_page_config_tekst( 1 === $aantal ? 'feiten_reviews_een' : 'feiten_reviews_meer' ),
            array( '%1$s' => $rating, '%2$s' => number_format_i18n( $aantal ), '%s' => $rating )
        )
    );
}
add_shortcode( 'sfp_reviews', 'sfp_page_config_shortcode_reviews' );

/* =========================================================================
 * [sfp_publicaties]
 * ====================================================================== */

/**
 * De kennisbanken die meetellen: de instelling publicaties_sites in SFP
 * Beheer (Onderhoud), een adres per regel. Leeg: de vijf Nederlandse sites
 * van het netwerk (besluit Stephan 31-10-2026: alleen die tellen mee).
 *
 * @return string[] Basisadressen met https.
 */
function sfp_feiten_publicaties_sites() {
    $ruw   = function_exists( 'sfp_page_config_get_setting' ) ? (string) sfp_page_config_get_setting( 'publicaties_sites', '' ) : '';
    $sites = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $ruw ) ), 'strlen' ) );
    if ( ! $sites ) {
        $sites = array(
            'https://schoolforprofessionals.com',
            'https://depresenteerschool.nl',
            'https://degespreksacademie.nl',
            'https://centrumvoordidactiek.nl',
            'https://deschrijftrainers.nl',
        );
    }
    return (array) apply_filters( 'sfp_feiten_publicaties_sites', $sites );
}

/**
 * Telt de artikelen opnieuw: de eigen site met een directe telling, de
 * andere kennisbanken via hun REST API, met een browser-user-agent (anders
 * weigeren Cloudflare en SiteGround). Draait in de dagelijkse taak (3.22.0),
 * niet meer bij het tonen van een pagina. Lukt een site niet, dan telt zijn
 * vorige aantal mee in plaats van nul.
 *
 * @return array{totaal: int, per_site: array<string,int>, tijd: int}
 */
function sfp_feiten_publicaties_tel() {
    $vorige     = get_option( 'sfp_feiten_publicaties_stand', array() );
    $vorige     = is_array( $vorige ) && isset( $vorige['per_site'] ) && is_array( $vorige['per_site'] ) ? $vorige['per_site'] : array();
    $eigen_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
    $per_site   = array();
    $agent      = function_exists( 'sfp_page_config_browser_agent' ) ? sfp_page_config_browser_agent() : '';
    foreach ( sfp_feiten_publicaties_sites() as $basis ) {
        $host = (string) wp_parse_url( $basis, PHP_URL_HOST );
        if ( '' === $host ) {
            continue;
        }
        if ( $host === $eigen_host ) {
            $per_site[ $host ] = (int) wp_count_posts( 'post' )->publish;
            continue;
        }
        $antwoord = wp_remote_get(
            'https://' . $host . '/wp-json/wp/v2/posts?per_page=1&status=publish&_fields=id',
            array(
                'timeout'    => 10,
                'user-agent' => '' !== $agent ? $agent : 'SFP-Page-Config/' . SFP_PAGE_CONFIG_VERSION,
            )
        );
        $aantal = is_wp_error( $antwoord ) || 200 !== (int) wp_remote_retrieve_response_code( $antwoord ) ? -1 : (int) wp_remote_retrieve_header( $antwoord, 'x-wp-total' );
        if ( $aantal < 0 ) {
            $aantal = isset( $vorige[ $host ] ) ? (int) $vorige[ $host ] : 0;
        }
        $per_site[ $host ] = $aantal;
    }
    $stand = array(
        'totaal'   => (int) array_sum( $per_site ),
        'per_site' => $per_site,
        'tijd'     => time(),
    );
    update_option( 'sfp_feiten_publicaties_stand', $stand, false );
    delete_transient( 'sfp_feiten_publicaties' );
    return $stand;
}

/**
 * Het aantal artikelen in de kennisbanken: de laatste telling van de
 * dagelijkse taak. Is er nog nooit geteld (net bijgewerkt), dan een keer
 * later in de achtergrond; de pagina wacht nooit op andere sites.
 *
 * @return int
 */
function sfp_feiten_publicaties_aantal() {
    $stand = get_option( 'sfp_feiten_publicaties_stand', array() );
    if ( is_array( $stand ) && isset( $stand['totaal'] ) ) {
        return (int) $stand['totaal'];
    }
    // Overgang: de oude telling uit 3.21.0 zolang die nog bestaat.
    $oud = get_transient( 'sfp_feiten_publicaties' );
    if ( ! wp_next_scheduled( 'sfp_feiten_publicaties_tel_nu' ) ) {
        wp_schedule_single_event( time() + 60, 'sfp_feiten_publicaties_tel_nu' );
    }
    return false !== $oud ? (int) $oud : 0;
}

add_action( 'sfp_feiten_publicaties_tel_nu', 'sfp_feiten_publicaties_tel' );
add_action( 'sfp_page_config_daily_check', 'sfp_feiten_publicaties_tel' );

/**
 * Rendert het aantal artikelen in de kennisbanken van het netwerk.
 *
 * @return string
 */
function sfp_page_config_shortcode_publicaties() {
    $aantal = sfp_feiten_publicaties_aantal();

    if ( $aantal < 1 ) {
        return esc_html( sfp_page_config_tekst( 'feiten_publicaties_terugval' ) );
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
