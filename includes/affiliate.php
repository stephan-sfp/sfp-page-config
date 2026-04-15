<?php
/**
 * SFP Page Config - Affiliate Box
 *
 * Registers `_has_affiliate_links` post meta and appends the affiliate
 * disclosure box (from an Astra Custom Layout) after the post content
 * when the checkbox is enabled.
 *
 * The affiliate toggle is shown in the SFP Page Config metabox (on posts
 * only) instead of a separate Gutenberg sidebar panel.
 *
 * The Astra layout post ID is configured per site so each domain can
 * use its own disclosure template.
 *
 * Replaces the old ASE Pro "Affiliate checkbox" snippet.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * 1. Register post meta (needed for REST API / Gutenberg compat)
 * ====================================================================== */

add_action( 'init', 'sfp_page_config_register_affiliate_meta' );

/**
 * Register the _has_affiliate_links meta key for posts.
 */
function sfp_page_config_register_affiliate_meta() {
    register_post_meta( 'post', '_has_affiliate_links', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'boolean',
        'default'       => false,
        'auth_callback' => function () {
            return current_user_can( 'edit_posts' );
        },
    ) );
}

/* =========================================================================
 * 2. Affiliate layout post ID per site
 * ====================================================================== */

/**
 * Get the Astra Custom Layout post ID that contains the affiliate box.
 *
 * Returns null when no layout is configured for the current site, which
 * effectively disables the feature on that domain.
 *
 * @return int|null
 */
function sfp_page_config_get_affiliate_layout_id() {

    // Highest priority: value set in the Instellingen tab. This lets each
    // site override the hardcoded default without a plugin release.
    $override = (int) sfp_page_config_get_setting( 'affiliate_layout_id', 0 );
    if ( $override > 0 ) {
        return $override;
    }

    $domain = wp_parse_url( home_url(), PHP_URL_HOST );

    $layouts = array(
        'depresenteerschool.nl' => 27038,
    );

    foreach ( $layouts as $host => $post_id ) {
        if ( false !== strpos( $domain, $host ) ) {
            return $post_id;
        }
    }

    return null;
}

/* =========================================================================
 * 3. Append affiliate box via the_content
 * ====================================================================== */

add_filter( 'the_content', 'sfp_page_config_affiliate_content', 50 );

/**
 * Append the affiliate disclosure box after the post content when enabled.
 *
 * @param  string $content The post content.
 * @return string
 */
function sfp_page_config_affiliate_content( $content ) {

    static $running = false;

    // Prevent recursion.
    if ( $running ) {
        return $content;
    }

    if ( ! is_singular( 'post' ) ) {
        return $content;
    }

    if ( ! get_post_meta( get_the_ID(), '_has_affiliate_links', true ) ) {
        return $content;
    }

    $layout_id = sfp_page_config_get_affiliate_layout_id();
    if ( ! $layout_id ) {
        return $content;
    }

    $layout_content = get_post_field( 'post_content', $layout_id );
    if ( ! $layout_content ) {
        return $content;
    }

    try {
        $running = true;
        $rendered = apply_filters( 'the_content', $layout_content );
    } finally {
        $running = false;
    }

    $content .= $rendered;

    return $content;
}
