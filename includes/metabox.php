<?php
/**
 * SFP Page Config - Editor Metabox
 *
 * Registers a sidebar metabox on pages and posts with the following fields:
 *   - training_naam       (text)         - pages + posts
 *   - sfp_page_type       (radio)        - pages + posts
 *   - sfp_longread        (checkbox)     - pages + posts
 *   - sfp_cursusdata       (read-only)   - pages only
 *
 * Also hides the default WordPress "Custom Fields" meta box so editors
 * cannot accidentally modify meta keys directly.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Register the metabox + hide Custom Fields
 * ====================================================================== */

add_action( 'add_meta_boxes', 'sfp_page_config_register_metabox' );

/**
 * Register the "SFP Page Config" metabox and remove the default
 * "Custom Fields" meta box on supported post types.
 */
function sfp_page_config_register_metabox() {

    foreach ( sfp_page_config_post_types() as $post_type ) {
        add_meta_box(
            'sfp_page_config_metabox',
            'SFP Page Config',
            'sfp_page_config_render_metabox',
            $post_type,
            'side',
            'high'
        );

        // Hide the default Custom Fields meta box.
        remove_meta_box( 'postcustom', $post_type, 'normal' );
    }
}

/* =========================================================================
 * Render the metabox
 * ====================================================================== */

/**
 * Output the metabox HTML.
 *
 * @param WP_Post $post The current post object.
 */
function sfp_page_config_render_metabox( $post ) {

    wp_nonce_field( 'sfp_page_config_save', 'sfp_page_config_nonce' );

    $training_naam = get_post_meta( $post->ID, 'training_naam', true );
    $page_type     = get_post_meta( $post->ID, 'sfp_page_type', true );
    $longread      = get_post_meta( $post->ID, 'sfp_longread', true );
    $is_page       = ( 'page' === $post->post_type );

    $page_types = array(
        ''          => 'Geen (standaard)',
        'coaching'  => 'Coaching',
        'training'  => 'Training',
        'incompany' => 'Incompany',
    );

    ?>
    <style>
        .sfp-mb-field { margin-bottom: 14px; }
        .sfp-mb-field:last-child { margin-bottom: 0; }
        .sfp-mb-field label.sfp-mb-label {
            display: block;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .sfp-mb-field input[type="text"] { width: 100%; }
        .sfp-mb-radios label {
            display: block;
            margin-bottom: 2px;
            font-weight: normal;
        }
        .sfp-mb-hint {
            display: block;
            font-size: 11px;
            color: #757575;
            margin-top: 3px;
            font-family: SFMono-Regular, Consolas, 'Liberation Mono', Menlo, monospace;
            background: #f6f7f7;
            padding: 2px 5px;
            border-radius: 3px;
            user-select: all;
        }
        .sfp-mb-note {
            font-size: 11px;
            color: #757575;
            margin-top: 3px;
        }
        .sfp-mb-summary {
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            padding: 8px 10px;
            font-size: 12px;
            line-height: 1.5;
        }
        .sfp-mb-summary .sfp-mb-sm {
            margin-bottom: 4px;
        }
        .sfp-mb-summary .sfp-mb-sm:last-child {
            margin-bottom: 0;
        }
        .sfp-mb-separator {
            border: 0;
            border-top: 1px solid #dcdcde;
            margin: 14px 0;
        }
    </style>

    <!-- Training naam -->
    <div class="sfp-mb-field">
        <label class="sfp-mb-label" for="sfp_training_naam">Training naam</label>
        <input
            type="text"
            id="sfp_training_naam"
            name="sfp_training_naam"
            value="<?php echo esc_attr( $training_naam ); ?>"
            placeholder="bijv. Presenteren met Impact"
        />
        <code class="sfp-mb-hint">[training_naam]</code>
    </div>

    <!-- Page type -->
    <div class="sfp-mb-field">
        <label class="sfp-mb-label">Paginatype</label>
        <div class="sfp-mb-radios">
            <?php foreach ( $page_types as $value => $label ) : ?>
                <label>
                    <input
                        type="radio"
                        name="sfp_page_type"
                        value="<?php echo esc_attr( $value ); ?>"
                        <?php checked( $page_type, $value ); ?>
                    />
                    <?php echo esc_html( $label ); ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sticky CTA overrides (only relevant when page type is set) -->
    <?php
    $cta_text = get_post_meta( $post->ID, 'sfp_cta_text', true );
    $cta_href = get_post_meta( $post->ID, 'sfp_cta_href', true );
    $cta_style = $page_type ? '' : ' style="display:none;"';
    ?>
    <div class="sfp-mb-field sfp-cta-override"<?php echo $cta_style; ?>>
        <label class="sfp-mb-label" for="sfp_cta_text">CTA knoptekst</label>
        <input
            type="text"
            id="sfp_cta_text"
            name="sfp_cta_text"
            value="<?php echo esc_attr( $cta_text ); ?>"
            placeholder="Standaard per paginatype"
        />
    </div>
    <div class="sfp-mb-field sfp-cta-override"<?php echo $cta_style; ?>>
        <label class="sfp-mb-label" for="sfp_cta_href">CTA anker / URL</label>
        <input
            type="text"
            id="sfp_cta_href"
            name="sfp_cta_href"
            value="<?php echo esc_attr( $cta_href ); ?>"
            placeholder="bijv. #inschrijven of https://..."
        />
        <span class="sfp-mb-note">Leeg = standaard per paginatype</span>
    </div>

    <script>
    (function(){
        var radios = document.querySelectorAll('input[name="sfp_page_type"]');
        var fields = document.querySelectorAll('.sfp-cta-override');
        function toggle(){
            var val = document.querySelector('input[name="sfp_page_type"]:checked');
            var show = val && val.value !== '';
            fields.forEach(function(f){ f.style.display = show ? '' : 'none'; });
        }
        radios.forEach(function(r){ r.addEventListener('change', toggle); });
    })();
    </script>

    <?php
    /* =====================================================================
     * Longread navigation toggle
     *
     * Only applicable on:
     *  - Blog posts
     *  - Pages tagged "pijler"
     *
     * On sales pages (paginatype set), the toggle has no effect because
     * sfp_page_config_is_longread() always returns false there. We still
     * show the checkbox state read-only if a legacy value is stored, so
     * the editor can see it exists, but we disable interaction.
     *
     * Reading time and scroll progress bar are now sitewide and no longer
     * controlled here. See reading-time.php.
     * =================================================================== */
    $is_post         = ( 'post' === $post->post_type );
    $is_pijler_page  = false;
    if ( ! $is_post && 'page' === $post->post_type ) {
        $lr_terms       = wp_get_object_terms( $post->ID, 'post_tag', array( 'fields' => 'slugs' ) );
        $is_pijler_page = ! is_wp_error( $lr_terms ) && in_array( 'pijler', (array) $lr_terms, true );
    }
    $longread_available = $is_post || $is_pijler_page;
    ?>
    <?php if ( $longread_available ) : ?>
        <hr class="sfp-mb-separator" />
        <div class="sfp-mb-field">
            <label>
                <input
                    type="checkbox"
                    name="sfp_longread"
                    value="1"
                    <?php checked( $longread, '1' ); ?>
                />
                Longread-navigatie activeren
            </label>
            <span class="sfp-mb-note">Inhoudsopgave op desktop, hoofdstukbalk op mobiel. Alleen op blogposts en pijlerpagina's.</span>
        </div>
    <?php endif; ?>

    <!-- WhatsApp button toggle -->
    <?php $whatsapp = get_post_meta( $post->ID, 'sfp_whatsapp', true ); ?>
    <div class="sfp-mb-field">
        <label>
            <input
                type="checkbox"
                name="sfp_whatsapp"
                value="1"
                <?php checked( $whatsapp, '1' ); ?>
            />
            WhatsApp-button tonen
        </label>
    </div>

    <?php
    /* =====================================================================
     * Affiliate toggle - posts only
     * =================================================================== */
    if ( 'post' === $post->post_type ) :
        $has_affiliate = get_post_meta( $post->ID, '_has_affiliate_links', true );
    ?>
    <div class="sfp-mb-field">
        <label>
            <input
                type="checkbox"
                name="sfp_has_affiliate"
                value="1"
                <?php checked( $has_affiliate, '1' ); ?>
            />
            Bevat affiliate links
        </label>
        <span class="sfp-mb-note">Toont de affiliatebox onderaan de post</span>
    </div>
    <?php endif; ?>

    <?php
    /* =====================================================================
     * Cursusdata section - pages only
     * =================================================================== */
    if ( $is_page ) :
        $cursusdata = get_post_meta( $post->ID, 'sfp_cursusdata', true );
    ?>
    <hr class="sfp-mb-separator" />

    <div class="sfp-mb-field">
        <label class="sfp-mb-label">Cursusdata</label>
        <?php
        $data = $cursusdata ? json_decode( $cursusdata, true ) : array();

        if ( ! empty( $data ) && is_array( $data ) ) {
            echo '<div class="sfp-mb-summary">';
            foreach ( $data as $index => $startmoment ) {
                if ( empty( $startmoment['data'] ) || ! is_array( $startmoment['data'] ) ) {
                    continue;
                }
                $dates_formatted = array();
                foreach ( $startmoment['data'] as $date_str ) {
                    $ts = strtotime( $date_str );
                    if ( $ts ) {
                        $dates_formatted[] = esc_html( date_i18n( 'j M Y', $ts ) );
                    }
                }
                if ( ! empty( $dates_formatted ) ) {
                    printf(
                        '<div class="sfp-mb-sm"><strong>Groep %d:</strong> %s</div>',
                        intval( $index + 1 ),
                        implode( ' &bull; ', $dates_formatted )
                    );
                }
            }
            echo '</div>';
        } else {
            echo '<p style="color:#757575;font-size:12px;margin:0;">Geen cursusdata ingesteld.</p>';
        }

        $dashboard_url = admin_url( 'admin.php?page=sfp-customizations' );
        printf(
            '<p style="margin:6px 0 0;"><a href="%s">Beheer in SFP Customizations &rarr;</a></p>',
            esc_url( $dashboard_url )
        );
        ?>
        <code class="sfp-mb-hint">[cursus_datum]</code>
        <span class="sfp-mb-note">Params: <code>format</code>, <code>groep</code>, <code>separator</code>, <code>show</code>, <code>layout</code></span>
    </div>
    <?php endif; ?>

    <?php
    /* =====================================================================
     * Hero focal point - only on posts/pages with "pijler" tag
     * =================================================================== */
    $terms = wp_get_object_terms( $post->ID, 'post_tag', array( 'fields' => 'slugs' ) );
    if ( in_array( 'pijler', (array) $terms, true ) ) :
        $focal_x = (int) get_post_meta( $post->ID, 'sfp_hero_focal_x', true );
        $focal_y = (int) get_post_meta( $post->ID, 'sfp_hero_focal_y', true );
        if ( ! $focal_x ) { $focal_x = 50; }
        if ( ! $focal_y ) { $focal_y = 50; }
        $thumbnail_id  = get_post_thumbnail_id( $post->ID );
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : '';
    ?>
    <hr class="sfp-mb-separator" />

    <div class="sfp-mb-field">
        <label class="sfp-mb-label">Hero focuspunt</label>
        <?php if ( $thumbnail_url ) : ?>
            <p style="font-size:11px;color:#666;margin-bottom:6px;">Klik op het onderwerp in de foto.</p>
            <div id="sfp-focal-canvas" style="position:relative;display:inline-block;width:100%;cursor:crosshair;border:1px solid #ddd;border-radius:4px;overflow:hidden;line-height:0;">
                <img id="sfp-focal-img" src="<?php echo esc_url( $thumbnail_url ); ?>" style="width:100%;display:block;" draggable="false" />
                <div id="sfp-focal-marker" style="position:absolute;width:20px;height:20px;margin-left:-10px;margin-top:-10px;border-radius:50%;background:rgba(255,90,6,0.85);border:2px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,0.4);pointer-events:none;left:<?php echo esc_attr( $focal_x ); ?>%;top:<?php echo esc_attr( $focal_y ); ?>%;"></div>
            </div>
        <?php else : ?>
            <p style="font-size:11px;color:#999;margin:0;">Stel eerst een uitgelichte afbeelding in.</p>
        <?php endif; ?>
        <div style="display:flex;gap:8px;margin-top:8px;align-items:center;">
            <label style="font-size:11px;flex:1;">X&nbsp;<input type="number" id="sfp_hero_focal_x" name="sfp_hero_focal_x" value="<?php echo esc_attr( $focal_x ); ?>" min="0" max="100" step="1" style="width:54px;" />%</label>
            <label style="font-size:11px;flex:1;">Y&nbsp;<input type="number" id="sfp_hero_focal_y" name="sfp_hero_focal_y" value="<?php echo esc_attr( $focal_y ); ?>" min="0" max="100" step="1" style="width:54px;" />%</label>
            <button type="button" id="sfp-focal-reset" style="font-size:11px;padding:2px 6px;">Reset</button>
        </div>
    </div>

    <script>
    (function() {
        var canvas = document.getElementById('sfp-focal-canvas');
        var marker = document.getElementById('sfp-focal-marker');
        var inpX   = document.getElementById('sfp_hero_focal_x');
        var inpY   = document.getElementById('sfp_hero_focal_y');
        var reset  = document.getElementById('sfp-focal-reset');
        if (!canvas) return;
        function setFocal(x, y) {
            x = Math.min(100, Math.max(0, Math.round(x)));
            y = Math.min(100, Math.max(0, Math.round(y)));
            inpX.value = x; inpY.value = y;
            marker.style.left = x + '%'; marker.style.top = y + '%';
        }
        canvas.addEventListener('click', function(e) {
            var rect = canvas.getBoundingClientRect();
            setFocal((e.clientX - rect.left) / rect.width * 100, (e.clientY - rect.top) / rect.height * 100);
        });
        inpX.addEventListener('input', function() { marker.style.left = inpX.value + '%'; });
        inpY.addEventListener('input', function() { marker.style.top  = inpY.value + '%'; });
        reset.addEventListener('click', function() { setFocal(50, 50); });
    })();
    </script>
    <?php endif; ?>

    <?php
}

/* =========================================================================
 * Save the metabox fields
 * ====================================================================== */

add_action( 'save_post', 'sfp_page_config_save_metabox', 10, 2 );

/**
 * Persist metabox field values on save.
 *
 * @param int     $post_id The post ID.
 * @param WP_Post $post    The post object.
 */
function sfp_page_config_save_metabox( $post_id, $post ) {

    // Verify nonce.
    $nonce = isset( $_POST['sfp_page_config_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sfp_page_config_nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'sfp_page_config_save' ) ) {
        return;
    }

    // Skip autosaves and revisions.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Check post type is supported.
    if ( ! in_array( $post->post_type, sfp_page_config_post_types(), true ) ) {
        return;
    }

    // Check capabilities.
    $capability = ( 'page' === $post->post_type ) ? 'edit_page' : 'edit_post';
    if ( ! current_user_can( $capability, $post_id ) ) {
        return;
    }

    // --- Training naam ---
    if ( isset( $_POST['sfp_training_naam'] ) ) {
        $value = sanitize_text_field( wp_unslash( $_POST['sfp_training_naam'] ) );
        if ( '' !== $value ) {
            update_post_meta( $post_id, 'training_naam', $value );
        } else {
            delete_post_meta( $post_id, 'training_naam' );
        }
    }

    // --- Page type ---
    $allowed_types = array( 'coaching', 'training', 'incompany' );
    if ( isset( $_POST['sfp_page_type'] ) ) {
        $type = sanitize_text_field( wp_unslash( $_POST['sfp_page_type'] ) );
        if ( in_array( $type, $allowed_types, true ) ) {
            update_post_meta( $post_id, 'sfp_page_type', $type );
        } else {
            delete_post_meta( $post_id, 'sfp_page_type' );
        }
    }

    // --- CTA overrides ---
    if ( isset( $_POST['sfp_cta_text'] ) ) {
        $cta_text = sanitize_text_field( wp_unslash( $_POST['sfp_cta_text'] ) );
        if ( '' !== $cta_text ) {
            update_post_meta( $post_id, 'sfp_cta_text', $cta_text );
        } else {
            delete_post_meta( $post_id, 'sfp_cta_text' );
        }
    }

    if ( isset( $_POST['sfp_cta_href'] ) ) {
        $cta_href = esc_url_raw( wp_unslash( $_POST['sfp_cta_href'] ) );
        if ( '' !== $cta_href ) {
            update_post_meta( $post_id, 'sfp_cta_href', $cta_href );
        } else {
            delete_post_meta( $post_id, 'sfp_cta_href' );
        }
    }

    // --- Longread ---
    if ( ! empty( $_POST['sfp_longread'] ) ) {
        update_post_meta( $post_id, 'sfp_longread', '1' );
    } else {
        delete_post_meta( $post_id, 'sfp_longread' );
    }

    // --- WhatsApp button ---
    if ( ! empty( $_POST['sfp_whatsapp'] ) ) {
        update_post_meta( $post_id, 'sfp_whatsapp', '1' );
    } else {
        delete_post_meta( $post_id, 'sfp_whatsapp' );
    }

    // --- Affiliate (posts only) ---
    if ( 'post' === $post->post_type ) {
        if ( ! empty( $_POST['sfp_has_affiliate'] ) ) {
            update_post_meta( $post_id, '_has_affiliate_links', '1' );
        } else {
            delete_post_meta( $post_id, '_has_affiliate_links' );
        }
    }

    // --- Hero focal point ---
    foreach ( array( 'sfp_hero_focal_x', 'sfp_hero_focal_y' ) as $focal_key ) {
        if ( isset( $_POST[ $focal_key ] ) ) {
            update_post_meta( $post_id, $focal_key, min( 100, max( 0, (int) $_POST[ $focal_key ] ) ) );
        }
    }

    // Note: sfp_cursusdata is managed exclusively via the Cursusdata Dashboard
    // (includes/dashboard.php) and is intentionally not editable here.
}
