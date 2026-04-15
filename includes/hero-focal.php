<?php
/**
 * SFP Page Config - Hero Focal Point
 *
 * Injects a mobile background-position override for .lcp-hero-image
 * based on the focal point set in the SFP Page Config metabox.
 *
 * Only active on singular pages/posts that have the "pijler" tag.
 *
 * The metabox fields (sfp_hero_focal_x, sfp_hero_focal_y) and their
 * interactive canvas are rendered inside metabox.php. This file only
 * handles the frontend CSS injection.
 *
 * Replaces the old ASE Pro "Hero focuspunt" snippet.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_head', 'sfp_page_config_hero_focal_css', 5 );

/**
 * Inject mobile background-position CSS for the hero image.
 */
function sfp_page_config_hero_focal_css() {

    if ( ! is_singular( array( 'page', 'post' ) ) ) {
        return;
    }

    $post_id = get_the_ID();

    // Only on posts/pages tagged "pijler".
    $terms = wp_get_object_terms( $post_id, 'post_tag', array( 'fields' => 'slugs' ) );
    if ( ! in_array( 'pijler', (array) $terms, true ) ) {
        return;
    }

    $x = (int) get_post_meta( $post_id, 'sfp_hero_focal_x', true );
    $y = (int) get_post_meta( $post_id, 'sfp_hero_focal_y', true );
    if ( ! $x ) { $x = 50; }
    if ( ! $y ) { $y = 50; }

    printf(
        '<style>@media(max-width:768px){.lcp-hero-image{background-position:%d%% %d%% !important;background-attachment:local !important}}</style>',
        $x,
        $y
    );
}
