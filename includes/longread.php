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
 *  - Allowed on ALL blog posts.
 *  - Allowed on pages with the "pijler" tag.
 *  - NEVER active on sales pages (sfp_page_type set to coaching, training,
 *    or incompany). Even if the legacy meta is set, we ignore it.
 *
 * Within the allowed scope, the longread nav is enabled either via the
 * new `sfp_longread` meta or the legacy `longread` meta from the old
 * ASE Pro snippet.
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

    $post_type = get_post_type( $post_id );

    // Pages must have the "pijler" tag to qualify.
    if ( 'page' === $post_type ) {
        $terms = wp_get_object_terms( $post_id, 'post_tag', array( 'fields' => 'slugs' ) );
        if ( is_wp_error( $terms ) || ! in_array( 'pijler', (array) $terms, true ) ) {
            return false;
        }
    } elseif ( 'post' !== $post_type ) {
        return false;
    }

    // Inside the allowed scope: check meta toggles.
    if ( '1' === get_post_meta( $post_id, 'sfp_longread', true ) ) {
        return true;
    }
    if ( '1' === get_post_meta( $post_id, 'longread', true ) ) {
        return true;
    }

    return false;
}
