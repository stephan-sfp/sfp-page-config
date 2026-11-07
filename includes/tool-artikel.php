<?php
/**
 * Tool alleen in artikel (3.28.0).
 *
 * Een test die bij één artikel hoort, krijgt het vinkje "Alleen in artikel"
 * (metaveld sfp_tool_alleen_artikel, zichtbaar in REST). Zo'n tool blijft
 * een gewoon gepubliceerd Tool-item, zodat het blok sfp/tool hem in het
 * artikel rendert en de deellink, de duimpjes en de mail werken. Maar hij
 * staat niet in het tooloverzicht (/tools/ en elke andere lijst van tools),
 * niet in de kaarten Andere tools, niet in de keuzelijst van de sitemap en
 * niet op een eigen adres: dat adres stuurt door naar het artikel dat hem
 * plaatst, of geeft 404 als er geen artikel is. Zoekmachines krijgen op dat
 * adres bovendien noindex.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Staat deze tool alleen in een artikel?
 *
 * @param  int|WP_Post $post Tool.
 * @return bool
 */
function sfp_page_config_tool_alleen_artikel( $post ) {
    $post = get_post( $post );
    return $post && 'tool' === $post->post_type && (bool) get_post_meta( $post->ID, 'sfp_tool_alleen_artikel', true );
}

/**
 * De meta_query die tools weglaat die alleen in een artikel staan.
 *
 * @return array
 */
function sfp_page_config_tool_alleen_artikel_meta_query() {
    return array(
        'relation' => 'OR',
        array(
            'key'     => 'sfp_tool_alleen_artikel',
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key'     => 'sfp_tool_alleen_artikel',
            'value'   => '1',
            'compare' => '!=',
        ),
    );
}

/**
 * De id's van alle tools die alleen in een artikel staan, één keer per aanvraag.
 *
 * @return int[]
 */
function sfp_page_config_tool_alleen_artikel_ids() {
    static $ids = null;
    if ( null !== $ids ) {
        return $ids;
    }
    $ids = array();
    if ( ! post_type_exists( 'tool' ) ) {
        return $ids;
    }
    $ids = array_map(
        'intval',
        get_posts(
            array(
                'post_type'        => 'tool',
                'post_status'      => 'any',
                'posts_per_page'   => 200,
                'fields'           => 'ids',
                'no_found_rows'    => true,
                'suppress_filters' => true,
                'sfp_alle_tools'   => true,
                'meta_key'         => 'sfp_tool_alleen_artikel', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- hoogstens 200 tools.
                'meta_value'       => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            )
        )
    );
    return $ids;
}

/**
 * Het artikel (of de pagina, het begrip) dat deze tool plaatst met het blok
 * sfp/tool of de code [sfp_tool]. Het eerste gepubliceerde bericht telt.
 *
 * @param  int $tool_id Tool.
 * @return WP_Post|null
 */
function sfp_page_config_tool_artikel_van( $tool_id ) {
    global $wpdb;
    $tool_id = (int) $tool_id;
    if ( ! $tool_id ) {
        return null;
    }
    $blok = '%' . $wpdb->esc_like( '"tool":' . $tool_id ) . '%';
    $code = '%' . $wpdb->esc_like( '[sfp_tool id="' . $tool_id . '"' ) . '%';
    $id   = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- alleen bij een doorverwijzing van het eigen adres.
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type <> 'tool' AND post_type <> 'revision' AND ( post_content LIKE %s OR post_content LIKE %s ) ORDER BY post_date DESC LIMIT 1",
            $blok,
            $code
        )
    );
    if ( ! $id ) {
        return null;
    }
    $post = get_post( (int) $id );
    if ( ! $post ) {
        return null;
    }
    return has_block( 'sfp/tool', $post ) || has_shortcode( $post->post_content, 'sfp_tool' ) ? $post : null;
}

add_action( 'pre_get_posts', 'sfp_page_config_tool_alleen_artikel_uit_lijsten', 20 );

/**
 * Elke lijst van tools aan de voorkant (het archief /tools/, een berichtenraster,
 * een eigen WP_Query met post_type tool) laat de tools weg die alleen in een
 * artikel staan. Een enkel bericht blijft bereikbaar; dat regelt de doorverwijzing.
 * Een query met 'sfp_alle_tools' => true (editor, beheer) krijgt alles; het
 * beheer (wp-admin, REST, WP-CLI) ziet altijd alles.
 *
 * @param WP_Query $query Query.
 */
function sfp_page_config_tool_alleen_artikel_uit_lijsten( $query ) {
    if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI && ! $query->get( 'sfp_voorkant' ) ) || ! post_type_exists( 'tool' ) ) {
        return;
    }
    if ( $query->is_singular() || $query->get( 'p' ) || $query->get( 'name' ) || $query->get( 'sfp_alle_tools' ) ) {
        return;
    }
    $soort = $query->get( 'post_type' );
    $tool  = 'tool' === $soort || ( is_array( $soort ) && array( 'tool' ) === array_values( $soort ) );
    if ( ! $tool && ! ( $query->is_main_query() && $query->is_post_type_archive( 'tool' ) ) ) {
        return;
    }
    $ids = sfp_page_config_tool_alleen_artikel_ids();
    if ( ! $ids ) {
        return;
    }
    $weg = (array) $query->get( 'post__not_in' );
    $query->set( 'post__not_in', array_values( array_unique( array_merge( array_map( 'intval', $weg ), $ids ) ) ) );
}

add_filter( 'surerank_exclude_posts_from_sitemap', 'sfp_page_config_tool_alleen_artikel_sitemap' );

/**
 * Niet in de sitemap van SureRank.
 *
 * @param  array|string $ids Uitgesloten id's.
 * @return array
 */
function sfp_page_config_tool_alleen_artikel_sitemap( $ids ) {
    $ids = wp_parse_id_list( $ids );
    return array_values( array_unique( array_merge( $ids, sfp_page_config_tool_alleen_artikel_ids() ) ) );
}

add_filter( 'wp_sitemaps_posts_query_args', 'sfp_page_config_tool_alleen_artikel_wp_sitemap', 10, 2 );

/**
 * Ook niet in de sitemap van WordPress zelf, als die aanstaat.
 *
 * @param  array  $args      Queryargumenten.
 * @param  string $post_type Berichttype.
 * @return array
 */
function sfp_page_config_tool_alleen_artikel_wp_sitemap( $args, $post_type ) {
    if ( 'tool' !== $post_type ) {
        return $args;
    }
    $ids = sfp_page_config_tool_alleen_artikel_ids();
    if ( $ids ) {
        $args['post__not_in'] = array_values( array_unique( array_merge( (array) ( $args['post__not_in'] ?? array() ), $ids ) ) );
    }
    return $args;
}

add_filter( 'surerank_set_meta', 'sfp_page_config_tool_alleen_artikel_noindex', 30 );

/**
 * Het eigen adres krijgt noindex, mocht het toch getoond worden (zonder artikel).
 *
 * @param  array $meta Meta van SureRank.
 * @return array
 */
function sfp_page_config_tool_alleen_artikel_noindex( $meta ) {
    if ( is_array( $meta ) && is_singular( 'tool' ) && sfp_page_config_tool_alleen_artikel( get_queried_object_id() ) ) {
        $meta['post_no_index'] = 'yes';
    }
    return $meta;
}

add_filter( 'wp_robots', 'sfp_page_config_tool_alleen_artikel_robots' );

/**
 * Zelfde noindex via WordPress, voor wie SureRank niet draait.
 *
 * @param  array $robots Robots.
 * @return array
 */
function sfp_page_config_tool_alleen_artikel_robots( $robots ) {
    if ( is_singular( 'tool' ) && sfp_page_config_tool_alleen_artikel( get_queried_object_id() ) ) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
        unset( $robots['index'] );
    }
    return $robots;
}

add_action( 'template_redirect', 'sfp_page_config_tool_alleen_artikel_doorsturen', 5 );

/**
 * Het eigen adres van een tool die alleen in een artikel staat: door naar
 * het artikel (301, met de vraagtekens zoals ?t= van een deellink erbij), of
 * 404 als geen bericht hem plaatst. Een voorbeeld of concept voor wie hem
 * mag bewerken blijft gewoon te zien.
 */
function sfp_page_config_tool_alleen_artikel_doorsturen() {
    if ( ! is_singular( 'tool' ) || is_preview() || ! empty( $_GET['sfp_voorbeeld'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- alleen lezen.
        return;
    }
    $post = get_queried_object();
    if ( ! $post || ! sfp_page_config_tool_alleen_artikel( $post ) ) {
        return;
    }
    if ( 'publish' !== $post->post_status && current_user_can( 'edit_post', $post->ID ) ) {
        return;
    }
    $artikel = sfp_page_config_tool_artikel_van( $post->ID );
    if ( $artikel ) {
        $naar  = get_permalink( $artikel );
        $vraag = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
        if ( '' !== $vraag ) {
            $naar .= ( false === strpos( $naar, '?' ) ? '?' : '&' ) . $vraag;
        }
        wp_safe_redirect( $naar, 301 );
        exit;
    }
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
}
