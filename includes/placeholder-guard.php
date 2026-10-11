<?php
/**
 * SFP Page Config - Placeholder Guard
 *
 * Safety net against unreplaced template placeholders going live.
 *
 * Training pages are duplicated from a private template ("Trainingspagina
 * (concept)") that is full of fill-in markers like [Probleem], [Programma],
 * [Naam], [Organisatie] and runs of dots ("....."). When an editor forgets
 * to replace one, it can end up on a published page. This module:
 *
 *   1. Detects likely placeholders (bracketed tokens that are not registered
 *      shortcodes, plus runs of four or more dots).
 *   2. Warns (never blocks) in the block editor right after a publish/update,
 *      and via an admin notice on the edit screen.
 *   3. Feeds the site-wide check on the screen Pagina's in SFP Beheer, which lists
 *      published pages/posts still containing placeholders.
 *
 * Drafts, pending and private content are skipped on purpose, so the template
 * itself may keep its markers.
 *
 * @package SFP_Page_Config
 * @since   2.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Core detector
 * ====================================================================== */

/**
 * Find likely-unreplaced template placeholders in a content string.
 *
 * Detection rules:
 *   1. Any [bracketed] token whose tag is NOT a registered shortcode. The
 *      tag must start with a letter, so numeric footnote markers like [1]
 *      and JSON arrays in block comments (["a","b"], [1,2,3]) are ignored.
 *      Registered shortcodes ([cursus_datum], [training_naam], [cursus_tijd],
 *      [google_reviews], ...) are skipped automatically via shortcode_exists(),
 *      so the detector grows with the shortcode set without maintenance.
 *   2. Runs of four or more literal dots ("....."), used as fill-in blanks.
 *      Three-dot ellipses and the Unicode ellipsis are left alone.
 *
 * @param  string $content Raw post content.
 * @return string[]        De-duplicated list of matched fragments, order kept.
 */
function sfp_page_config_find_placeholders( $content ) {

    $found = array();

    if ( ! is_string( $content ) || '' === $content ) {
        return $found;
    }

    // 1. Bracketed tokens that are not registered shortcodes.
    if ( preg_match_all( '/\[([A-Za-z][A-Za-z0-9_-]*)[^\]\[]*\]/', $content, $matches, PREG_SET_ORDER ) ) {
        foreach ( $matches as $match ) {
            $tag = $match[1];
            // Case-sensitive on purpose: [Cursus_datum] would not render as a
            // shortcode either, so flagging it is correct.
            if ( shortcode_exists( $tag ) ) {
                continue;
            }
            $found[] = trim( $match[0] );
        }
    }

    // 2. Runs of four or more dots.
    if ( preg_match_all( '/\.{4,}/', $content, $dot_matches ) ) {
        foreach ( $dot_matches[0] as $dots ) {
            $found[] = $dots;
        }
    }

    return array_values( array_unique( $found ) );
}

/**
 * Lower-cased list of every currently registered shortcode tag.
 *
 * Used to hand the block-editor script the same "known good" set the PHP
 * detector relies on, so both sides agree on what is a placeholder.
 *
 * @return string[]
 */
function sfp_page_config_registered_shortcodes() {
    global $shortcode_tags;
    if ( empty( $shortcode_tags ) || ! is_array( $shortcode_tags ) ) {
        return array();
    }
    return array_values( array_keys( $shortcode_tags ) );
}

/* =========================================================================
 * Per-page check on save (publish/update)
 * ====================================================================== */

if ( ! has_action( 'save_post', 'sfp_page_config_check_placeholders_on_save' ) ) {
    add_action( 'save_post', 'sfp_page_config_check_placeholders_on_save', 20, 2 );
}

/**
 * On save of a published, supported post: scan the content and stash any
 * findings in a short-lived transient so the edit screen can warn about it.
 * Also invalidates the site-wide sweep cache so the dashboard stays current.
 *
 * @param int     $post_id The post ID.
 * @param WP_Post $post    The post object.
 */
function sfp_page_config_check_placeholders_on_save( $post_id, $post ) {

    if ( ! ( $post instanceof WP_Post ) ) {
        return;
    }

    // Only the content types this plugin manages.
    if ( ! in_array( $post->post_type, sfp_page_config_post_types(), true ) ) {
        return;
    }

    // Skip autosaves and revisions.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Only warn for content that is actually live. Drafts, pending, private
    // and auto-drafts are skipped so the template keeps its markers.
    if ( 'publish' !== $post->post_status ) {
        delete_transient( 'sfp_pc_ph_' . $post_id );
        // A page can move out of "publish"; refresh the sweep either way.
        delete_transient( 'sfp_pc_sweep' );
        return;
    }

    $found = sfp_page_config_find_placeholders( $post->post_content );

    if ( ! empty( $found ) ) {
        set_transient( 'sfp_pc_ph_' . $post_id, $found, 5 * MINUTE_IN_SECONDS );
    } else {
        delete_transient( 'sfp_pc_ph_' . $post_id );
    }

    // The published set changed, so the cached sweep is now stale.
    delete_transient( 'sfp_pc_sweep' );
}

/* =========================================================================
 * Admin notice on the edit screen
 * ====================================================================== */

if ( ! has_action( 'admin_notices', 'sfp_page_config_placeholder_admin_notice' ) ) {
    add_action( 'admin_notices', 'sfp_page_config_placeholder_admin_notice' );
}

/**
 * Show a dismissible warning on the edit screen when the last save of this
 * published page turned up placeholders. Shown once, then the transient is
 * cleared. Complements the immediate block-editor notice for the classic
 * editor and for editors that reload the page.
 */
function sfp_page_config_placeholder_admin_notice() {

    if ( ! function_exists( 'get_current_screen' ) ) {
        return;
    }
    $screen = get_current_screen();
    if ( ! $screen || 'post' !== $screen->base ) {
        return;
    }

    $post = get_post();
    if ( ! $post ) {
        return;
    }

    $found = get_transient( 'sfp_pc_ph_' . $post->ID );
    if ( empty( $found ) || ! is_array( $found ) ) {
        return;
    }

    $tags = array();
    foreach ( $found as $item ) {
        $tags[] = '<code>' . esc_html( $item ) . '</code>';
    }

    printf(
        '<div class="notice notice-warning is-dismissible"><p><strong>SFP Page Config:</strong> deze gepubliceerde pagina bevat mogelijk niet-vervangen invulplekken uit het sjabloon: %s. Controleer of dit bewust is voordat bezoekers het zien.</p></div>',
        implode( ', ', $tags ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- items escaped above.
    );

    delete_transient( 'sfp_pc_ph_' . $post->ID );
}

/* =========================================================================
 * Immediate warning inside the block editor
 * ====================================================================== */

if ( ! has_action( 'enqueue_block_editor_assets', 'sfp_page_config_placeholder_editor_assets' ) ) {
    add_action( 'enqueue_block_editor_assets', 'sfp_page_config_placeholder_editor_assets' );
}

/**
 * Enqueue a tiny script that, after each successful publish/update of a
 * published post, scans the content client-side and raises a dismissible
 * warning notice in the editor. Warn only, never blocks the save.
 */
function sfp_page_config_placeholder_editor_assets() {

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( $screen && ! in_array( $screen->post_type, sfp_page_config_post_types(), true ) ) {
        return;
    }

    wp_enqueue_script(
        'sfp-page-config-placeholder',
        SFP_PAGE_CONFIG_URL . 'assets/placeholder-editor.js',
        array( 'wp-data', 'wp-notices' ),
        SFP_PAGE_CONFIG_VERSION,
        true
    );

    wp_localize_script(
        'sfp-page-config-placeholder',
        'sfpPlaceholderConfig',
        array(
            'shortcodes' => sfp_page_config_registered_shortcodes(),
        )
    );
}

/* =========================================================================
 * Site-wide sweep
 * ====================================================================== */

/**
 * Scan every published, supported post for placeholders.
 *
 * Result is cached for an hour and invalidated on each save. Returns an
 * associative array of post_id => array of matched fragments.
 *
 * @param  bool $force Bypass and rebuild the cache.
 * @return array<int, string[]>
 */
function sfp_page_config_scan_placeholders( $force = false ) {

    $cache_key = 'sfp_pc_sweep';

    if ( ! $force ) {
        $cached = get_transient( $cache_key );
        if ( is_array( $cached ) ) {
            return $cached;
        }
    }

    $results = array();

    $query = new WP_Query(
        array(
            'post_type'              => sfp_page_config_post_types(),
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'suppress_filters'       => true,
        )
    );

    foreach ( $query->posts as $pid ) {
        $content = get_post_field( 'post_content', $pid );
        $found   = sfp_page_config_find_placeholders( $content );
        if ( ! empty( $found ) ) {
            $results[ (int) $pid ] = $found;
        }
    }

    set_transient( $cache_key, $results, HOUR_IN_SECONDS );

    return $results;
}
