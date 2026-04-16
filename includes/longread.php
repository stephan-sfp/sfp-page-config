<?php
/**
 * SFP Page Config - Longread Body Class
 *
 * Reads `sfp_longread` post meta on pages and posts.
 * Also checks the legacy `longread` key (from the old ASE Pro snippet)
 * so existing posts keep working without manual migration.
 *
 * Adds `.is-longread` body class to activate longread typography,
 * navigation sidebar/bar, and reading progress bar.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'body_class', 'sfp_page_config_longread_body_class' );

/**
 * Add `.is-longread` body class when the longread toggle is on.
 *
 * @param  string[] $classes Existing body classes.
 * @return string[]
 */
function sfp_page_config_longread_body_class( $classes ) {

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return $classes;
    }

    if ( sfp_page_config_is_longread() ) {
        $classes[] = 'is-longread';
    }

    return $classes;
}

/**
 * Check whether the current singular page has longread navigation enabled.
 *
 * Scope rules (enforced here so every caller respects them):
 *  - Longread is OFF by default on all posts and pages.
 *  - Editors activate it explicitly via the sfp_longread meta (checkbox
 *    in the metabox), which is available on posts and pages except on
 *    sales pages.
 *  - NEVER active on sales pages (sfp_page_type set to coaching, training,
 *    or incompany). Even if the legacy meta is set, we ignore it.
 *
 * @param  int|null $post_id Optional post ID. Defaults to queried object.
 * @return bool
 */
function sfp_page_config_is_longread( $post_id = null ) {

    if ( null === $post_id ) {
        $post_id = get_queried_object_id();
    }

    if ( ! $post_id ) {
        return false;
    }

    // Hard exclusion: sales pages never get longread nav.
    if ( get_post_meta( $post_id, 'sfp_page_type', true ) ) {
        return false;
    }

    // Only posts and pages qualify for longread.
    $post_type = get_post_type( $post_id );
    if ( 'post' !== $post_type && 'page' !== $post_type ) {
        return false;
    }

    // Check meta toggles (new key first, legacy key as fallback).
    if ( '1' === get_post_meta( $post_id, 'sfp_longread', true ) ) {
        return true;
    }
    if ( '1' === get_post_meta( $post_id, 'longread', true ) ) {
        return true;
    }

    return false;
}
