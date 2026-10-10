<?php
/**
 * SFP Page Config - Voorbeeldlink voor een concept
 *
 * Een concept is uitgelogd niet te zien, terwijl een proef juist uitgelogd
 * beoordeeld moet worden (ingelogd krijg je een andere pagina dan een
 * bezoeker). Deze module maakt per bericht een link met een sleutel waarmee
 * het concept twee weken uitgelogd te bekijken is.
 *
 *   POST   /sfp/v1/voorbeeld/<id>   maak of vernieuw de link (geeft de URL)
 *   DELETE /sfp/v1/voorbeeld/<id>   trek de link in
 *
 * Alleen voor gebruikers die het bericht mogen bewerken. De pagina achter
 * de link krijgt noindex en gaat niet in de cache van WP Rocket. Cloudflare
 * cachet HTML per URL: zet bij elke meting een eigen waarde achter &v=.
 *
 * Vervangt het tijdelijke snippet "Conceptvoorbeeld met sleutel".
 *
 * Terugdraaien: dit bestand uit de lijst in sfp-page-config.php halen.
 *
 * @package SFP_Page_Config
 * @since   2.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', 'sfp_page_config_voorbeeld_rest' );

/**
 * Registreer de routes.
 */
function sfp_page_config_voorbeeld_rest() {
    $mag = function ( WP_REST_Request $verzoek ) {
        return current_user_can( 'edit_post', (int) $verzoek['id'] );
    };
    register_rest_route(
        'sfp/v1',
        '/voorbeeld/(?P<id>\d+)',
        array(
            array(
                'methods'             => 'POST',
                'permission_callback' => $mag,
                'callback'            => function ( WP_REST_Request $verzoek ) {
                    $post_id = (int) $verzoek['id'];
                    if ( ! get_post( $post_id ) ) {
                        return new WP_Error( 'sfp_voorbeeld_geen_bericht', 'Bericht niet gevonden.', array( 'status' => 404 ) );
                    }
                    $sleutel = wp_generate_password( 28, false );
                    $tot     = time() + 14 * DAY_IN_SECONDS;
                    update_post_meta(
                        $post_id,
                        '_sfp_voorbeeld',
                        array(
                            'sleutel' => $sleutel,
                            'tot'     => $tot,
                        )
                    );
                    return rest_ensure_response(
                        array(
                            'url' => add_query_arg(
                                array(
                                    'p'             => $post_id,
                                    'sfp_voorbeeld' => $sleutel,
                                ),
                                home_url( '/' )
                            ),
                            'tot' => wp_date( 'Y-m-d H:i', $tot ),
                        )
                    );
                },
            ),
            array(
                'methods'             => 'DELETE',
                'permission_callback' => $mag,
                'callback'            => function ( WP_REST_Request $verzoek ) {
                    delete_post_meta( (int) $verzoek['id'], '_sfp_voorbeeld' );
                    return rest_ensure_response( array( 'ingetrokken' => true ) );
                },
            ),
        )
    );
}

add_action( 'pre_get_posts', 'sfp_page_config_voorbeeld_aanvraag' );

/**
 * Een aanvraag met een geldige sleutel mag het concept zien.
 *
 * @param WP_Query $query De hoofdquery.
 */
function sfp_page_config_voorbeeld_aanvraag( $query ) {
    if ( is_admin() || ! $query->is_main_query() || empty( $_GET['sfp_voorbeeld'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- de sleutel is de controle.
        return;
    }
    $post_id = (int) $query->get( 'p' );
    if ( ! $post_id ) {
        $post_id = (int) $query->get( 'page_id' );
    }
    $bewaard = $post_id ? get_post_meta( $post_id, '_sfp_voorbeeld', true ) : null;
    $sleutel = sanitize_text_field( wp_unslash( $_GET['sfp_voorbeeld'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! is_array( $bewaard ) || empty( $bewaard['sleutel'] ) || empty( $bewaard['tot'] ) || (int) $bewaard['tot'] < time() || ! hash_equals( (string) $bewaard['sleutel'], $sleutel ) ) {
        return;
    }

    // Niet in een cache en niet in een zoekmachine.
    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }
    nocache_headers();
    if ( ! headers_sent() ) {
        header( 'X-Robots-Tag: noindex, nofollow', true );
    }
    add_filter( 'wp_robots', 'wp_robots_no_robots' );
    // Geen doorverwijzing naar de nette URL: daar hoort de sleutel niet bij.
    remove_action( 'template_redirect', 'redirect_canonical' );

    // WordPress haalt een concept voor een uitgelogde bezoeker uit de
    // uitkomst. Zet het hier terug, ongewijzigd: de status van het bericht
    // blijft concept, ook in de objectcache.
    $toon = function ( $posts, $q ) use ( &$toon, $post_id ) {
        if ( ! $q->is_main_query() ) {
            return $posts;
        }
        remove_filter( 'the_posts', $toon, 10 );
        if ( empty( $posts ) ) {
            $post = get_post( $post_id );
            if ( $post && in_array( $post->post_status, array( 'draft', 'pending', 'future' ), true ) ) {
                return array( $post );
            }
        }
        return $posts;
    };
    add_filter( 'the_posts', $toon, 10, 2 );
}
