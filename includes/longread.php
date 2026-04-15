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
 * Check whether the current singular page has longread mode enabled.
 *
 * Checks both the new `sfp_longread` meta key and the legacy `longread`
 * key from the old ASE Pro snippet.
 *
 * @param  int|null $post_id Optional post ID. Defaults to queried object.
 * @return bool
 */
function sfp_page_config_is_longread( $post_id = null ) {

    if ( null === $post_id ) {
        $post_id = get_queried_object_id();
    }

    // New key (set via SFP Page Config metabox).
    if ( '1' === get_post_meta( $post_id, 'sfp_longread', true ) ) {
        return true;
    }

    // Legacy key (set via old ASE Pro "Longreadnavigatie" snippet).
    if ( '1' === get_post_meta( $post_id, 'longread', true ) ) {
        return true;
    }

    return false;
}
