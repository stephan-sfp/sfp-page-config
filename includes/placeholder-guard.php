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
 *   3. Offers a site-wide sweep on the SFP Page Config dashboard that lists
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

/**
 * Render the "Invulplekken" dashboard tab: a counter plus a table of live
 * pages/posts that still contain placeholders, with view/edit links and a
 * rescan button. Registered from includes/dashboard.php.
 */
function sfp_page_config_render_tab_controle() {

    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'Geen toegang.' );
    }

    // Handle a rescan request.
    $force = false;
    if ( isset( $_POST['sfp_pc_rescan_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sfp_pc_rescan_nonce'] ) ), 'sfp_pc_rescan' ) ) {
        delete_transient( 'sfp_pc_sweep' );
        $force = true;
    }

    $results = sfp_page_config_scan_placeholders( $force );
    $count   = count( $results );

    $rescan_url = admin_url( 'admin.php?page=sfp-page-config&tab=controle' );

    ?>
    <p>Deze controle scant alle <strong>gepubliceerde</strong> pagina's, posts en begrippen op mogelijk niet-vervangen invulplekken uit het sjabloon: teksten tussen vierkante haken die geen shortcode zijn (zoals <code>[Probleem]</code>, <code>[Naam]</code>), en reeksen van vier of meer puntjes. Concepten en priv&eacute;pagina's blijven buiten beschouwing.</p>

    <form method="post" action="<?php echo esc_url( $rescan_url ); ?>" style="margin:15px 0;">
        <?php wp_nonce_field( 'sfp_pc_rescan', 'sfp_pc_rescan_nonce' ); ?>
        <?php if ( 0 === $count ) : ?>
            <span class="sfp-pc-badge sfp-pc-badge-ok">Geen invulplekken gevonden op live pagina's.</span>
        <?php else : ?>
            <span class="sfp-pc-badge sfp-pc-badge-warn"><?php echo esc_html( (string) $count ); ?> live pagina<?php echo 1 === $count ? '' : "'s"; ?> met mogelijke invulplekken</span>
        <?php endif; ?>
        <button type="submit" class="button" style="margin-left:10px;">Opnieuw scannen</button>
    </form>

    <style>
        .sfp-pc-badge{display:inline-block;padding:4px 12px;border-radius:12px;font-weight:600;font-size:13px}
        .sfp-pc-badge-ok{background:#edfaef;color:#007a2a;border:1px solid #9ad3a8}
        .sfp-pc-badge-warn{background:#fcf4e6;color:#8a5a00;border:1px solid #edc887}
        .sfp-pc-table{width:100%;border-collapse:collapse;margin-top:12px;background:#fff;max-width:1000px}
        .sfp-pc-table th,.sfp-pc-table td{padding:10px 14px;border:1px solid #e1e1e1;text-align:left;vertical-align:top}
        .sfp-pc-table th{background:#f6f7f7;font-weight:600}
        .sfp-pc-table code{background:#fcecec;color:#8a1f1f;padding:1px 5px;border-radius:3px}
    </style>

    <?php if ( $count > 0 ) : ?>
    <table class="sfp-pc-table">
        <thead>
            <tr>
                <th style="width:32%">Pagina</th>
                <th style="width:12%">Type</th>
                <th>Gevonden invulplekken</th>
                <th style="width:130px">Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $results as $pid => $found ) :
                $tags = array();
                foreach ( $found as $item ) {
                    $tags[] = '<code>' . esc_html( $item ) . '</code>';
                }
                ?>
                <tr>
                    <td><strong><?php echo esc_html( get_the_title( $pid ) ); ?></strong></td>
                    <td><?php echo esc_html( get_post_type( $pid ) ); ?></td>
                    <td><?php echo implode( ' ', $tags ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></td>
                    <td>
                        <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" target="_blank">Bekijk</a>
                        &nbsp;|&nbsp;
                        <a href="<?php echo esc_url( (string) get_edit_post_link( $pid ) ); ?>" target="_blank">Bewerk</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p style="color:#757575;font-size:12px;margin-top:8px;">De uitslag wordt maximaal een uur gecachet en ververst automatisch zodra je een pagina opslaat. Gebruik "Opnieuw scannen" om nu te verversen.</p>
    <?php endif; ?>
    <?php
}
