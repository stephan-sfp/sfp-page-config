<?php
/**
 * SFP Page Config - Admin Dashboard
 *
 * Single admin page with tabbed interface:
 *   Cursusdata | Instellingen | Shortcode Referentie
 *
 * The Cursusdata tab provides full CRUD for course dates across all pages,
 * with filter, search, and hide/show functionality.
 *
 * @package SFP_Page_Config
 * @since   1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Clear dashboard cache on post save
 * ====================================================================== */

add_action( 'save_post_page', function() {
    delete_transient( 'sfp_dashboard_pages_training' );
    // Legacy key from versions before 1.9.10.
    delete_transient( 'sfp_dashboard_pages' );
} );

/* =========================================================================
 * Admin menu (single menu item)
 * ====================================================================== */

// Only register our admin menu if the ASE snippet hasn't already registered it.
if ( ! has_action( 'admin_menu', 'sfp_cursusdata_menu' ) ) {
    add_action( 'admin_menu', 'sfp_page_config_add_dashboard_page' );
}

/**
 * Register the single admin menu page.
 */
function sfp_page_config_add_dashboard_page() {
    add_menu_page(
        'SFP Page Config',
        'SFP Page Config',
        'edit_pages',
        'sfp-page-config',
        'sfp_page_config_render_admin',
        'dashicons-admin-generic',
        30
    );
}

/* =========================================================================
 * Register settings (for the Instellingen tab)
 * ====================================================================== */

add_action( 'admin_init', 'sfp_page_config_register_settings' );

function sfp_page_config_register_settings() {
    register_setting( 'sfp_settings', 'sfp_settings', array(
        'type'              => 'array',
        'sanitize_callback' => 'sfp_page_config_sanitize_settings',
    ) );
}

function sfp_page_config_sanitize_settings( $input ) {
    $clean = array();

    // WhatsApp.
    $clean['whatsapp_number']  = sanitize_text_field( $input['whatsapp_number'] ?? '' );
    $clean['whatsapp_message'] = sanitize_text_field( $input['whatsapp_message'] ?? '' );

    // Leestijd.
    $clean['words_per_minute'] = absint( $input['words_per_minute'] ?? 250 );
    if ( $clean['words_per_minute'] < 100 ) $clean['words_per_minute'] = 100;
    if ( $clean['words_per_minute'] > 500 ) $clean['words_per_minute'] = 500;

    // Promo/Convert Pro.
    $clean['promo_scroll_gate']    = absint( $input['promo_scroll_gate'] ?? 30 );
    $clean['promo_cooldown_hours'] = absint( $input['promo_cooldown_hours'] ?? 24 );

    // Cron notification email.
    $clean['cron_email'] = sanitize_email( $input['cron_email'] ?? '' );

    // Longread branding overrides (hex colors).
    // An empty value is allowed: it means "fall back to the domain default"
    // in sfp_page_config_get_brand(). Invalid values are stored as empty
    // so they also fall back safely.
    foreach ( array( 'lr_brand', 'lr_bar_bg', 'lr_bar_text', 'lr_sidebar_text', 'lr_sidebar_muted' ) as $color_key ) {
        $raw = isset( $input[ $color_key ] ) ? trim( (string) $input[ $color_key ] ) : '';
        if ( '' === $raw ) {
            $clean[ $color_key ] = '';
            continue;
        }
        $hex = sanitize_hex_color( $raw );
        $clean[ $color_key ] = $hex ? $hex : '';
    }

    // Custom CSS for the reading-time meter and scroll progress bar.
    // Stored as-is, but stripped of HTML tags so no </style> can escape
    // the inline <style> block we render on the front-end.
    $clean['custom_css_rp'] = '';
    if ( isset( $input['custom_css_rp'] ) ) {
        $clean['custom_css_rp'] = wp_strip_all_tags( (string) $input['custom_css_rp'] );
    }

    // Sticky CTA overrides per paginatype (coaching, training, incompany).
    // Each type has four optional fields: text, href, anchor, hero.
    // Empty strings mean "fall back to the hardcoded default" in
    // sfp_page_config_get_sticky_cta(), so editors can wipe a field
    // without breaking the CTA.
    $clean['sticky_cta'] = array();
    $types = array( 'coaching', 'training', 'incompany' );
    if ( isset( $input['sticky_cta'] ) && is_array( $input['sticky_cta'] ) ) {
        foreach ( $types as $type ) {
            $raw = isset( $input['sticky_cta'][ $type ] ) ? (array) $input['sticky_cta'][ $type ] : array();
            $clean['sticky_cta'][ $type ] = array(
                'text'   => isset( $raw['text'] )   ? sanitize_text_field( $raw['text'] ) : '',
                'href'   => isset( $raw['href'] )   ? esc_url_raw( trim( (string) $raw['href'] ) ) : '',
                'anchor' => isset( $raw['anchor'] ) ? sanitize_key( ltrim( (string) $raw['anchor'], '#' ) ) : '',
                'hero'   => isset( $raw['hero'] )   ? sanitize_text_field( $raw['hero'] ) : '',
            );
        }
    }

    return $clean;
}

/**
 * Get a single setting with fallback.
 */
function sfp_page_config_get_setting( $key, $default = '' ) {
    $settings = get_option( 'sfp_settings', array() );
    return isset( $settings[ $key ] ) && '' !== $settings[ $key ] ? $settings[ $key ] : $default;
}

/* =========================================================================
 * Enqueue dashboard assets (only on our admin page)
 * ====================================================================== */

add_action( 'admin_enqueue_scripts', 'sfp_page_config_dashboard_assets' );

function sfp_page_config_dashboard_assets( $hook_suffix ) {

    if ( 'toplevel_page_sfp-page-config' !== $hook_suffix ) {
        return;
    }

    wp_enqueue_script(
        'sfp-page-config-dashboard',
        SFP_PAGE_CONFIG_URL . 'assets/dashboard.js',
        array(),
        SFP_PAGE_CONFIG_VERSION,
        true
    );

    wp_localize_script( 'sfp-page-config-dashboard', 'sfpDashboard', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'sfp_dashboard_nonce' ),
    ) );

    // Native WordPress color picker for the Instellingen tab, limited to
    // the Astra global color palette so we only pick from brand-approved
    // values instead of arbitrary hex codes.
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );

    $palette = sfp_page_config_get_astra_palette();
    wp_add_inline_script(
        'wp-color-picker',
        'var sfpAstraPalette = ' . wp_json_encode( $palette ) . ';' .
        'jQuery(function($){' .
        '  var opts = { hide: true, change: null };' .
        '  if (Array.isArray(sfpAstraPalette) && sfpAstraPalette.length) {' .
        '    opts.palettes = sfpAstraPalette;' .
        '  }' .
        '  $(".sfp-color-field").wpColorPicker(opts);' .
        '});'
    );
}

/**
 * Pull the Astra Customizer global color palette so the color picker
 * only offers brand-approved swatches.
 *
 * Astra stores its color palette in the `astra-settings` option under
 * `global-color-palette.palette`. That array always contains 9 hex
 * values in a fixed order (primary, secondary, ...). We return up to
 * 8 entries because the WP color picker renders at most 8 swatches.
 *
 * Returns an empty array when no palette can be resolved, in which case
 * the color picker falls back to its default swatches.
 *
 * @return string[]
 */
function sfp_page_config_get_astra_palette() {

    $astra = get_option( 'astra-settings', array() );
    if ( ! is_array( $astra ) ) {
        return array();
    }

    $palette = array();
    if ( isset( $astra['global-color-palette']['palette'] ) && is_array( $astra['global-color-palette']['palette'] ) ) {
        $palette = $astra['global-color-palette']['palette'];
    }

    $clean = array();
    foreach ( $palette as $hex ) {
        $hex = is_string( $hex ) ? trim( $hex ) : '';
        $hex = sanitize_hex_color( $hex );
        if ( $hex ) {
            $clean[] = $hex;
        }
        if ( count( $clean ) >= 8 ) {
            break;
        }
    }

    return $clean;
}

/* =========================================================================
 * Main admin page renderer (tabbed)
 * ====================================================================== */

function sfp_page_config_render_admin() {

    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'Geen toegang.' );
    }

    $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'cursusdata';
    $tabs = array(
        'cursusdata'   => 'Cursusdata',
        'instellingen' => 'Instellingen',
        'shortcodes'   => 'Shortcode Referentie',
    );

    $base_url = admin_url( 'admin.php?page=sfp-page-config' );

    ?>
    <div class="wrap">
        <h1>SFP Page Config <small style="font-size:12px;color:#888;">v<?php echo esc_html( SFP_PAGE_CONFIG_VERSION ); ?></small></h1>

        <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
            <?php foreach ( $tabs as $slug => $label ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base_url ) ); ?>"
                   class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php
        switch ( $tab ) {
            case 'instellingen':
                sfp_page_config_render_tab_settings();
                break;
            case 'shortcodes':
                sfp_page_config_render_tab_shortcodes();
                break;
            default:
                sfp_page_config_render_tab_cursusdata();
                break;
        }
        ?>
    </div>
    <?php
}

/* =========================================================================
 * Tab: Cursusdata (full dashboard with filter, search, hide/show)
 * ====================================================================== */

function sfp_page_config_render_tab_cursusdata() {

    // Only training pages (sfp_page_type = training) can have cursusdata,
    // so there is no point offering editors the full page list. The cache
    // key was bumped to invalidate any stale "all pages" transients saved
    // by prior versions.
    $cache_key = 'sfp_dashboard_pages_training';
    $pages = get_transient( $cache_key );

    if ( false === $pages ) {
        $pages = get_posts( array(
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'   => 'sfp_page_type',
                    'value' => 'training',
                ),
            ),
        ) );
        set_transient( $cache_key, $pages, 6 * HOUR_IN_SECONDS );
    }

    $nonce  = wp_create_nonce( 'sfp_cursusdata_nonce' );
    $hidden = get_option( 'sfp_cursusdata_hidden', array() );
    if ( ! is_array( $hidden ) ) {
        $hidden = array();
    }

    // Build page data array for JS.
    $page_data_js = array();
    foreach ( $pages as $p ) {
        $json = get_post_meta( $p->ID, 'sfp_cursusdata', true );
        $page_data_js[ $p->ID ] = $json ? json_decode( $json, true ) : array();
    }

    // Dutch day/month abbreviations for display.
    $dagen = array(
        'Monday' => 'ma', 'Tuesday' => 'di', 'Wednesday' => 'wo',
        'Thursday' => 'do', 'Friday' => 'vr', 'Saturday' => 'za', 'Sunday' => 'zo',
    );
    $maanden = array(
        'January' => 'jan', 'February' => 'feb', 'March' => 'mrt',
        'April' => 'apr', 'May' => 'mei', 'June' => 'jun',
        'July' => 'jul', 'August' => 'aug', 'September' => 'sep',
        'October' => 'okt', 'November' => 'nov', 'December' => 'dec',
    );

    ?>
    <p>Beheer hier de cursusdata voor alle <strong>open-trainingpagina's</strong> op deze site (paginatype <code>training</code>). Klik op <strong>Bewerken</strong> om startmomenten en dagdata toe te voegen.</p>

    <style>
        .sfp-cd-table{width:100%;border-collapse:collapse;margin-top:15px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.04)}
        .sfp-cd-table th,.sfp-cd-table td{padding:10px 14px;border:1px solid #e1e1e1;text-align:left;vertical-align:top}
        .sfp-cd-table th{background:#f6f7f7;font-weight:600;position:sticky;top:0}
        .sfp-cd-table tr:hover{background:#f9f9f9}
        .sfp-cd-table tr.sfp-cd-hidden-row{opacity:.5}
        .sfp-cd-sm{background:#f0f6fc;border:1px solid #c3d9ed;border-radius:4px;padding:10px 12px;margin-bottom:8px}
        .sfp-cd-sm .sfp-cd-sm-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
        .sfp-cd-sm .datum-row{display:flex;align-items:center;gap:8px;margin:4px 0}
        .sfp-cd-sm input[type="date"]{padding:5px 8px;border:1px solid #bbb;border-radius:3px;font-size:13px}
        .sfp-cd-btn{padding:5px 12px;border:1px solid #999;border-radius:3px;cursor:pointer;font-size:12px;background:#fff;line-height:1.4}
        .sfp-cd-btn:hover{background:#eee}
        .sfp-cd-btn-save{background:#2271b1;color:#fff;border-color:#2271b1;font-weight:600}
        .sfp-cd-btn-save:hover{background:#135e96}
        .sfp-cd-btn-add{background:#00a32a;color:#fff;border-color:#00a32a}
        .sfp-cd-btn-add:hover{background:#008a20}
        .sfp-cd-btn-del{color:#b32d2e;border-color:#b32d2e;padding:3px 8px}
        .sfp-cd-btn-del:hover{background:#fcf0f1}
        .sfp-cd-btn-hide{color:#666;border-color:#ccc;font-size:11px;padding:3px 8px}
        .sfp-cd-btn-hide:hover{background:#f0f0f0}
        .sfp-cd-saved{color:#00a32a;font-weight:bold;display:none;margin-left:10px}
        .sfp-cd-no-dates{color:#999;font-style:italic}
        .sfp-cd-edit-panel{display:none}
        .sfp-cd-display .sfp-cd-sm-display{margin-bottom:4px;padding:4px 8px;background:#f0f6fc;border-radius:3px;display:inline-block;margin-right:6px}
        .sfp-cd-filter{margin:15px 0;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
        .sfp-cd-filter select,.sfp-cd-filter input[type="text"]{padding:6px 10px;border:1px solid #bbb;border-radius:3px}
        .sfp-cd-count{background:#2271b1;color:#fff;border-radius:10px;padding:2px 10px;font-size:12px;margin-left:6px}
    </style>

    <div class="sfp-cd-filter">
        <label><strong>Filter:</strong></label>
        <select id="sfp-cd-filter">
            <option value="all">Alle pagina's</option>
            <option value="with">Met cursusdata</option>
            <option value="without">Zonder cursusdata</option>
            <option value="hidden">Verborgen pagina's</option>
        </select>
        <input type="text" id="sfp-cd-search" placeholder="Zoek op paginanaam..." style="width:280px;">
        <span id="sfp-cd-counter"></span>
    </div>

    <table class="sfp-cd-table">
        <thead>
            <tr>
                <th style="width:28%">Pagina</th>
                <th>Cursusdata</th>
                <th style="width:130px">Acties</th>
            </tr>
        </thead>
        <tbody id="sfp-cd-tbody">
        <?php foreach ( $pages as $page ) :
            $data      = isset( $page_data_js[ $page->ID ] ) ? $page_data_js[ $page->ID ] : array();
            $has_dates = ! empty( $data );
            $pid       = intval( $page->ID );
            $is_hidden = in_array( $pid, $hidden ) ? '1' : '0';
            $row_class = $is_hidden === '1' ? 'sfp-cd-row sfp-cd-hidden-row' : 'sfp-cd-row';
        ?>
            <tr class="<?php echo $row_class; ?>"
                data-pid="<?php echo $pid; ?>"
                data-has="<?php echo $has_dates ? '1' : '0'; ?>"
                data-hidden="<?php echo $is_hidden; ?>">

                <!-- Kolom: Pagina -->
                <td>
                    <strong><?php echo esc_html( $page->post_title ); ?></strong><br>
                    <small>
                        <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" target="_blank">Bekijk</a>
                        | <a href="<?php echo esc_url( get_edit_post_link( $pid ) ); ?>" target="_blank">Bewerk pagina</a>
                    </small>
                </td>

                <!-- Kolom: Cursusdata -->
                <td>
                    <div class="sfp-cd-display" id="dsp-<?php echo $pid; ?>">
                    <?php if ( $has_dates ) :
                        foreach ( $data as $i => $sm ) :
                            $formatted = array();
                            if ( isset( $sm['data'] ) && is_array( $sm['data'] ) ) {
                                foreach ( $sm['data'] as $d ) {
                                    $ts = strtotime( $d );
                                    if ( ! $ts ) continue;
                                    $str = date( 'D j M Y', $ts );
                                    foreach ( $dagen as $en => $nl ) {
                                        $str = str_replace( substr( $en, 0, 3 ), $nl, $str );
                                    }
                                    foreach ( $maanden as $en => $nl ) {
                                        $str = str_replace( substr( $en, 0, 3 ), $nl, $str );
                                    }
                                    $formatted[] = $str;
                                }
                            }
                            $num = $i + 1;
                            ?>
                            <span class="sfp-cd-sm-display">
                                <strong><?php echo $num; ?>.</strong>
                                <?php echo esc_html( implode( ' | ', $formatted ) ); ?>
                            </span>
                        <?php endforeach;
                    else : ?>
                        <span class="sfp-cd-no-dates">Geen data ingesteld</span>
                    <?php endif; ?>
                    </div>

                    <div class="sfp-cd-edit-panel" id="edt-<?php echo $pid; ?>">
                        <div id="sm-<?php echo $pid; ?>"></div>
                        <div style="display:flex;align-items:center;gap:8px;margin-top:10px;">
                            <button class="sfp-cd-btn sfp-cd-btn-add" onclick="sfpAddSM(<?php echo $pid; ?>)">+ Startmoment</button>
                            <button class="sfp-cd-btn sfp-cd-btn-save" onclick="sfpSave(<?php echo $pid; ?>)">Opslaan</button>
                            <button class="sfp-cd-btn" onclick="sfpCancel(<?php echo $pid; ?>)">Annuleren</button>
                            <span class="sfp-cd-saved" id="ok-<?php echo $pid; ?>">Opgeslagen!</span>
                        </div>
                    </div>
                </td>

                <!-- Kolom: Acties -->
                <td>
                    <button class="sfp-cd-btn" onclick="sfpToggle(<?php echo $pid; ?>)" id="btn-<?php echo $pid; ?>">Bewerken</button>
                    <?php if ( $is_hidden === '1' ) : ?>
                        <button class="sfp-cd-btn sfp-cd-btn-hide" onclick="sfpHide(<?php echo $pid; ?>,0)" id="hbtn-<?php echo $pid; ?>">Tonen</button>
                    <?php else : ?>
                        <button class="sfp-cd-btn sfp-cd-btn-hide" onclick="sfpHide(<?php echo $pid; ?>,1)" id="hbtn-<?php echo $pid; ?>">Verbergen</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <script>
    var sfpN = <?php echo wp_json_encode( $nonce ); ?>;
    var sfpU = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var sfpD = <?php echo wp_json_encode( $page_data_js ); ?>;

    function sfpToggle(id) {
        var d = document.getElementById('dsp-' + id);
        var e = document.getElementById('edt-' + id);
        var b = document.getElementById('btn-' + id);
        if (e.style.display === 'block') {
            e.style.display = 'none';
            d.style.display = 'block';
            b.textContent = 'Bewerken';
        } else {
            d.style.display = 'none';
            e.style.display = 'block';
            b.textContent = 'Sluiten';
            sfpRender(id);
        }
    }

    function sfpCancel(id) {
        document.getElementById('dsp-' + id).style.display = 'block';
        document.getElementById('edt-' + id).style.display = 'none';
        document.getElementById('btn-' + id).textContent = 'Bewerken';
    }

    function sfpRender(id) {
        var c = document.getElementById('sm-' + id);
        var data = sfpD[id] || [];
        if (data.length === 0) {
            data = [{data: ['']}];
            sfpD[id] = data;
        }
        var h = '';
        data.forEach(function(sm, si) {
            h += '<div class="sfp-cd-sm">';
            h += '<div class="sfp-cd-sm-head"><strong>Startmoment ' + (si+1) + '</strong>';
            h += '<button class="sfp-cd-btn sfp-cd-btn-del" onclick="sfpDelSM('+id+','+si+')">Verwijderen</button></div>';
            (sm.data || ['']).forEach(function(d, di) {
                h += '<div class="datum-row">';
                h += '<label style="min-width:45px">Dag '+(di+1)+':</label>';
                h += '<input type="date" value="'+(d||'')+'" onchange="sfpUpd('+id+','+si+','+di+',this.value)">';
                if (di > 0) h += ' <button class="sfp-cd-btn sfp-cd-btn-del" onclick="sfpDelD('+id+','+si+','+di+')" title="Dag verwijderen">x</button>';
                h += '</div>';
            });
            if ((sm.data || []).length < 10) {
                h += '<button class="sfp-cd-btn" onclick="sfpAddD('+id+','+si+')" style="margin-top:6px;font-size:11px">+ Dag toevoegen</button>';
            }
            h += '</div>';
        });
        c.innerHTML = h;
    }

    function sfpAddSM(id) { sfpD[id] = sfpD[id]||[]; sfpD[id].push({data:['']}); sfpRender(id); }
    function sfpDelSM(id,si) { sfpD[id].splice(si,1); sfpRender(id); }
    function sfpAddD(id,si) { sfpD[id][si].data.push(''); sfpRender(id); }
    function sfpDelD(id,si,di) { sfpD[id][si].data.splice(di,1); sfpRender(id); }
    function sfpUpd(id,si,di,v) { sfpD[id][si].data[di] = v; }

    function sfpSave(id) {
        var fd = new FormData();
        fd.append('action', 'sfp_save_cursusdata');
        fd.append('nonce', sfpN);
        fd.append('post_id', id);
        fd.append('cursusdata', JSON.stringify(sfpD[id] || []));
        fetch(sfpU, {method: 'POST', body: fd})
            .then(function(r) { return r.json(); })
            .then(function(r) {
                if (r.success) {
                    sfpD[id] = r.data.clean;
                    var dsp = document.getElementById('dsp-' + id);
                    var cl = r.data.clean;
                    if (cl.length > 0) {
                        var h = '';
                        cl.forEach(function(sm, i) {
                            var parts = sm.data.map(function(d) {
                                var dt = new Date(d + 'T12:00:00');
                                var days = ['zo','ma','di','wo','do','vr','za'];
                                var months = ['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'];
                                return days[dt.getDay()] + ' ' + dt.getDate() + ' ' + months[dt.getMonth()] + ' ' + dt.getFullYear();
                            });
                            h += '<span class="sfp-cd-sm-display"><strong>' + (i+1) + '.</strong> ' + parts.join(' | ') + '</span>';
                        });
                        dsp.innerHTML = h;
                        dsp.closest('tr').setAttribute('data-has', '1');
                    } else {
                        dsp.innerHTML = '<span class="sfp-cd-no-dates">Geen data ingesteld</span>';
                        dsp.closest('tr').setAttribute('data-has', '0');
                    }
                    var ok = document.getElementById('ok-' + id);
                    ok.style.display = 'inline';
                    setTimeout(function() { ok.style.display = 'none'; }, 2500);
                    sfpRender(id);
                    sfpCount();
                }
            });
    }

    function sfpHide(id, hide) {
        var fd = new FormData();
        fd.append('action', 'sfp_toggle_hidden');
        fd.append('nonce', sfpN);
        fd.append('post_id', id);
        fd.append('hide', hide ? '1' : '0');
        fetch(sfpU, {method: 'POST', body: fd})
            .then(function(r) { return r.json(); })
            .then(function(r) {
                if (r.success) {
                    var row = document.querySelector('tr[data-pid="' + id + '"]');
                    var btn = document.getElementById('hbtn-' + id);
                    if (hide) {
                        row.setAttribute('data-hidden', '1');
                        row.classList.add('sfp-cd-hidden-row');
                        btn.textContent = 'Tonen';
                        btn.setAttribute('onclick', 'sfpHide(' + id + ',0)');
                    } else {
                        row.setAttribute('data-hidden', '0');
                        row.classList.remove('sfp-cd-hidden-row');
                        btn.textContent = 'Verbergen';
                        btn.setAttribute('onclick', 'sfpHide(' + id + ',1)');
                    }
                    sfpFilter();
                }
            });
    }

    document.getElementById('sfp-cd-filter').addEventListener('change', sfpFilter);
    document.getElementById('sfp-cd-search').addEventListener('input', sfpFilter);

    function sfpFilter() {
        var f = document.getElementById('sfp-cd-filter').value;
        var s = document.getElementById('sfp-cd-search').value.toLowerCase();
        document.querySelectorAll('.sfp-cd-row').forEach(function(r) {
            var has = r.getAttribute('data-has') === '1';
            var hid = r.getAttribute('data-hidden') === '1';
            var t = r.querySelector('strong').textContent.toLowerCase();
            var sf = false;
            if (f === 'all') sf = !hid;
            else if (f === 'with') sf = has && !hid;
            else if (f === 'without') sf = !has && !hid;
            else if (f === 'hidden') sf = hid;
            var ss = !s || t.indexOf(s) > -1;
            r.style.display = (sf && ss) ? '' : 'none';
        });
        sfpCount();
    }

    function sfpCount() {
        var vis = document.querySelectorAll('.sfp-cd-row:not([style*="display: none"])').length;
        var tot = document.querySelectorAll('.sfp-cd-row:not([data-hidden="1"])').length;
        var wd  = document.querySelectorAll('.sfp-cd-row[data-has="1"]:not([data-hidden="1"])').length;
        var hd  = document.querySelectorAll('.sfp-cd-row[data-hidden="1"]').length;
        var txt = vis + ' van ' + tot + " pagina's getoond ";
        txt += '<span class="sfp-cd-count">' + wd + ' met data</span>';
        if (hd > 0) txt += ' <span class="sfp-cd-count" style="background:#666">' + hd + ' verborgen</span>';
        document.getElementById('sfp-cd-counter').innerHTML = txt;
    }

    sfpFilter();
    </script>
    <?php
}

/* =========================================================================
 * Tab: Instellingen
 * ====================================================================== */

function sfp_page_config_render_tab_settings() {

    // Handle save.
    if ( isset( $_POST['sfp_settings_nonce'] ) && wp_verify_nonce( $_POST['sfp_settings_nonce'], 'sfp_save_settings' ) ) {
        $input = isset( $_POST['sfp_settings'] ) ? wp_unslash( $_POST['sfp_settings'] ) : array();
        $clean = sfp_page_config_sanitize_settings( $input );
        update_option( 'sfp_settings', $clean );
        echo '<div class="notice notice-success is-dismissible"><p>Instellingen opgeslagen.</p></div>';
    }

    $s = get_option( 'sfp_settings', array() );
    $brand = sfp_page_config_get_brand();
    $domain = parse_url( home_url(), PHP_URL_HOST );

    ?>
    <form method="post">
        <?php wp_nonce_field( 'sfp_save_settings', 'sfp_settings_nonce' ); ?>

        <!-- CTA branding (read-only, set in code) -->
        <h2>CTA-branding <small style="font-size:12px;color:#888;">(<?php echo esc_html( $domain ); ?>)</small></h2>
        <p style="color:#666;">CTA-kleuren en fonts worden centraal per site vastgesteld in de plugincode. Hieronder de huidige waarden ter referentie.</p>
        <table class="form-table" role="presentation">
            <tr>
                <th>CTA achtergrondkleur</th>
                <td>
                    <span style="display:inline-block;width:24px;height:24px;background:<?php echo esc_attr( $brand['cta_bg'] ); ?>;border:1px solid #ccc;vertical-align:middle;border-radius:3px;"></span>
                    <code style="margin-left:8px;"><?php echo esc_html( $brand['cta_bg'] ); ?></code>
                </td>
            </tr>
            <tr>
                <th>CTA hoverkleur</th>
                <td>
                    <span style="display:inline-block;width:24px;height:24px;background:<?php echo esc_attr( $brand['cta_hover'] ); ?>;border:1px solid #ccc;vertical-align:middle;border-radius:3px;"></span>
                    <code style="margin-left:8px;"><?php echo esc_html( $brand['cta_hover'] ); ?></code>
                </td>
            </tr>
            <tr>
                <th>Heading font</th>
                <td><code><?php echo esc_html( $brand['font'] ); ?></code></td>
            </tr>
            <tr>
                <th>Button font-weight</th>
                <td><code><?php echo esc_html( $brand['weight'] ); ?></code></td>
            </tr>
        </table>

        <!-- Longread-branding (editable per site) -->
        <?php
        // The resolved brand already contains the merged values (domain defaults
        // + stored overrides). To show the user what's *stored* vs. what's
        // inherited, also pull the raw settings.
        $lr_fields = array(
            'lr_brand'         => array(
                'label'   => 'Accentkleur (actief item in TOC)',
                'help'    => 'Kleur van het actieve link-item en de geactiveerde lijn in de zijbalk.',
            ),
            'lr_bar_bg'        => array(
                'label'   => 'Balk-achtergrond (mobiel)',
                'help'    => 'Achtergrondkleur van de hoofdstukbalk onderaan op mobiel.',
            ),
            'lr_bar_text'      => array(
                'label'   => 'Balk-tekstkleur (mobiel)',
                'help'    => 'Tekstkleur in de mobiele hoofdstukbalk.',
            ),
            'lr_sidebar_text'  => array(
                'label'   => 'Zijbalk-tekstkleur (desktop)',
                'help'    => 'Standaardkleur van de links in de TOC op desktop.',
            ),
            'lr_sidebar_muted' => array(
                'label'   => 'Zijbalk-muted (desktop)',
                'help'    => 'Kleur van de verticale lijn en het titellabel boven de TOC.',
            ),
        );
        ?>
        <h2>Longread-branding</h2>
        <p style="color:#666;">Kleuren voor de inhoudsopgave (desktop) en hoofdstukbalk (mobiel). Laat een veld leeg om terug te vallen op de domein-default in de plugincode.</p>
        <table class="form-table" role="presentation">
            <?php foreach ( $lr_fields as $key => $meta ) :
                $stored  = isset( $s[ $key ] ) ? $s[ $key ] : '';
                $current = $brand[ $key ] ?? ''; // Resolved (stored or fallback).
                $is_inherited = ( '' === $stored );
                ?>
            <tr>
                <th><label for="sfp-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $meta['label'] ); ?></label></th>
                <td>
                    <input type="text"
                           id="sfp-<?php echo esc_attr( $key ); ?>"
                           class="sfp-color-field"
                           name="sfp_settings[<?php echo esc_attr( $key ); ?>]"
                           value="<?php echo esc_attr( $stored ); ?>"
                           data-default-color="<?php echo esc_attr( $current ); ?>" />
                    <p class="description">
                        <?php echo esc_html( $meta['help'] ); ?>
                        <?php if ( $is_inherited ) : ?>
                            <br><em>Huidig (default): <code><?php echo esc_html( $current ); ?></code></em>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <!-- Sticky CTA per paginatype -->
        <?php
        $sticky_defaults  = sfp_page_config_get_sticky_cta_defaults();
        $sticky_stored    = isset( $s['sticky_cta'] ) && is_array( $s['sticky_cta'] ) ? $s['sticky_cta'] : array();
        $sticky_type_meta = array(
            'coaching'  => 'Coaching (salespage)',
            'training'  => 'Training (salespage)',
            'incompany' => 'Incompany (salespage)',
        );
        ?>
        <h2>Sticky CTA</h2>
        <p style="color:#666;">De sticky CTA verschijnt op mobiel en tablet (tot 1024px) zodra de hero uit beeld scrollt, en verdwijnt weer zodra de bezoeker bij het aanvraag- of inschrijfformulier is. Kleuren komen automatisch uit de CTA-branding hierboven. Laat een veld leeg om terug te vallen op de default rechts.</p>

        <?php foreach ( $sticky_type_meta as $type => $label ) :
            $def    = $sticky_defaults[ $type ];
            $stored = isset( $sticky_stored[ $type ] ) && is_array( $sticky_stored[ $type ] ) ? $sticky_stored[ $type ] : array();
            ?>
            <h3 style="margin-top:1.5em;"><?php echo esc_html( $label ); ?></h3>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="sfp-sticky-<?php echo esc_attr( $type ); ?>-text">Knoptekst</label></th>
                    <td>
                        <input type="text"
                               id="sfp-sticky-<?php echo esc_attr( $type ); ?>-text"
                               name="sfp_settings[sticky_cta][<?php echo esc_attr( $type ); ?>][text]"
                               value="<?php echo esc_attr( $stored['text'] ?? '' ); ?>"
                               class="regular-text"
                               placeholder="<?php echo esc_attr( $def['text'] ); ?>" />
                        <p class="description">Default: <code><?php echo esc_html( $def['text'] ); ?></code></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="sfp-sticky-<?php echo esc_attr( $type ); ?>-href">Knoplink (URL)</label></th>
                    <td>
                        <input type="url"
                               id="sfp-sticky-<?php echo esc_attr( $type ); ?>-href"
                               name="sfp_settings[sticky_cta][<?php echo esc_attr( $type ); ?>][href]"
                               value="<?php echo esc_attr( $stored['href'] ?? '' ); ?>"
                               class="regular-text code"
                               placeholder="<?php echo esc_attr( $def['href'] ); ?>" />
                        <p class="description">Default: <code><?php echo esc_html( $def['href'] ); ?></code></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="sfp-sticky-<?php echo esc_attr( $type ); ?>-anchor">Anchor-ID op de pagina</label></th>
                    <td>
                        <input type="text"
                               id="sfp-sticky-<?php echo esc_attr( $type ); ?>-anchor"
                               name="sfp_settings[sticky_cta][<?php echo esc_attr( $type ); ?>][anchor]"
                               value="<?php echo esc_attr( $stored['anchor'] ?? '' ); ?>"
                               class="regular-text code"
                               placeholder="<?php echo esc_attr( $def['anchor'] ); ?>" />
                        <p class="description">Zonder de <code>#</code>. Zodra dit element in beeld komt verdwijnt de sticky CTA. Default: <code><?php echo esc_html( $def['anchor'] ); ?></code></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="sfp-sticky-<?php echo esc_attr( $type ); ?>-hero">Hero-selector (CSS)</label></th>
                    <td>
                        <input type="text"
                               id="sfp-sticky-<?php echo esc_attr( $type ); ?>-hero"
                               name="sfp_settings[sticky_cta][<?php echo esc_attr( $type ); ?>][hero]"
                               value="<?php echo esc_attr( $stored['hero'] ?? '' ); ?>"
                               class="regular-text code"
                               placeholder="<?php echo esc_attr( $def['hero'] ); ?>" />
                        <p class="description">CSS-selector van de hero-sectie. Zodra deze uit beeld scrollt verschijnt de sticky CTA. Default: <code><?php echo esc_html( $def['hero'] ); ?></code></p>
                    </td>
                </tr>
            </table>
        <?php endforeach; ?>

        <!-- WhatsApp -->
        <h2>WhatsApp-widget</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="sfp-wa-number">Telefoonnummer</label></th>
                <td>
                    <input type="text" id="sfp-wa-number" name="sfp_settings[whatsapp_number]"
                           value="<?php echo esc_attr( $s['whatsapp_number'] ?? '' ); ?>"
                           class="regular-text" placeholder="31850601355" />
                    <p class="description">Zonder + of spaties, bijv. 31850601355.</p>
                </td>
            </tr>
            <tr>
                <th><label for="sfp-wa-msg">Standaardbericht</label></th>
                <td>
                    <input type="text" id="sfp-wa-msg" name="sfp_settings[whatsapp_message]"
                           value="<?php echo esc_attr( $s['whatsapp_message'] ?? '' ); ?>"
                           class="large-text" placeholder="Hallo! Ik heb een vraag over..." />
                </td>
            </tr>
        </table>

        <!-- Leestijd -->
        <h2>Leestijd en voortgangsbalk</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="sfp-wpm">Woorden per minuut</label></th>
                <td>
                    <input type="number" id="sfp-wpm" name="sfp_settings[words_per_minute]"
                           value="<?php echo esc_attr( $s['words_per_minute'] ?? 250 ); ?>"
                           min="100" max="500" step="10" style="width:80px;" />
                    <p class="description">Gemiddelde leessnelheid (standaard 250).</p>
                </td>
            </tr>
            <tr>
                <th><label for="sfp-custom-css-rp">Aangepaste CSS</label></th>
                <td>
                    <textarea id="sfp-custom-css-rp"
                              name="sfp_settings[custom_css_rp]"
                              rows="10"
                              class="large-text code"
                              placeholder=".custom-read-meter { ... }&#10;.tijd-getal { ... }&#10;#sfp-scroll-container { ... }&#10;#sfp-scroll-bar { ... }"
                              spellcheck="false"><?php echo esc_textarea( $s['custom_css_rp'] ?? '' ); ?></textarea>
                    <p class="description">
                        CSS-regels voor de leestijdmeter en voortgangsbalk. De volgende selectors zijn beschikbaar:
                        <br><code>.custom-read-meter</code> &mdash; de container van de leestijd.
                        <br><code>.tijd-getal</code> &mdash; alleen het cijfer in de leestijd.
                        <br><code>#sfp-scroll-container</code> &mdash; de container van de voortgangsbalk (staat bovenaan de viewport).
                        <br><code>#sfp-scroll-bar</code> &mdash; het oplopende balkje zelf.
                        <br>Wordt via een <code>&lt;style&gt;</code>-blok in de head van elke pagina en post uitgevoerd.
                    </p>
                </td>
            </tr>
        </table>

        <!-- Promo / Convert Pro -->
        <h2>Promo / Convert Pro</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="sfp-scroll-gate">Scroll gate (%)</label></th>
                <td>
                    <input type="number" id="sfp-scroll-gate" name="sfp_settings[promo_scroll_gate]"
                           value="<?php echo esc_attr( $s['promo_scroll_gate'] ?? 30 ); ?>"
                           min="0" max="100" step="5" style="width:80px;" />
                    <p class="description">Percentage van de pagina dat gescrolld moet zijn voor popups verschijnen.</p>
                </td>
            </tr>
            <tr>
                <th><label for="sfp-cooldown">Cooldown (uren)</label></th>
                <td>
                    <input type="number" id="sfp-cooldown" name="sfp_settings[promo_cooldown_hours]"
                           value="<?php echo esc_attr( $s['promo_cooldown_hours'] ?? 24 ); ?>"
                           min="1" max="168" step="1" style="width:80px;" />
                    <p class="description">Hoe lang een popup verborgen blijft na sluiting.</p>
                </td>
            </tr>
        </table>

        <!-- Cron -->
        <h2>Cursusdata-meldingen</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="sfp-cron-email">E-mailadres</label></th>
                <td>
                    <input type="email" id="sfp-cron-email" name="sfp_settings[cron_email]"
                           value="<?php echo esc_attr( $s['cron_email'] ?? '' ); ?>"
                           class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
                    <p class="description">Ontvangt dagelijkse meldingen over verlopen/aankomende cursusdata. Leeg = site-admin.</p>
                </td>
            </tr>
        </table>

        <?php submit_button( 'Instellingen opslaan' ); ?>
    </form>
    <?php
}

/* =========================================================================
 * Tab: Shortcode Referentie
 * ====================================================================== */

function sfp_page_config_render_tab_shortcodes() {
    ?>
    <h2><code>[cursus_datum]</code></h2>
    <p>Toont cursusdatums van de huidige pagina. Werkt automatisch op trainingspagina's, in SureForms en in herbruikbare blokken.</p>

    <table class="widefat fixed striped" style="max-width:800px;">
        <thead>
            <tr>
                <th style="width:20%;">Attribuut</th>
                <th style="width:25%;">Opties</th>
                <th style="width:20%;">Standaard</th>
                <th style="width:35%;">Toelichting</th>
            </tr>
        </thead>
        <tbody>
            <tr><td><code>groep</code></td><td><code>1</code>, <code>2</code>, <code>3</code></td><td>alle groepen</td><td>Toon alleen deze groep (1-gebaseerd).</td></tr>
            <tr><td><code>format</code></td><td><code>l j F Y</code>, <code>j F Y</code>, <code>j F</code>, <code>D j F</code></td><td><code>l j F Y</code></td><td>PHP-datumformaat. <code>l</code> = volledige dagnaam, <code>D</code> = korte dagnaam, <code>j</code> = dag, <code>F</code> = maand, <code>Y</code> = jaar.</td></tr>
            <tr><td><code>separator</code></td><td>elk teken/tekst</td><td><code> &amp;bull; </code> (bullet)</td><td>Scheidingsteken tussen data binnen een groep.</td></tr>
            <tr><td><code>show</code></td><td><code>all</code>, <code>first</code></td><td><code>all</code></td><td><code>first</code> toont alleen de eerste datum per groep.</td></tr>
            <tr><td><code>layout</code></td><td><code>inline</code>, <code>list</code></td><td><code>inline</code></td><td><code>list</code> toont elke groep op een eigen regel met label.</td></tr>
            <tr><td><code>post_id</code></td><td>een post ID</td><td>huidige pagina</td><td>Forceer data van een andere pagina.</td></tr>
        </tbody>
    </table>

    <h3>Voorbeelden</h3>
    <table class="widefat fixed striped" style="max-width:800px;">
        <thead>
            <tr>
                <th style="width:55%;">Shortcode</th>
                <th style="width:45%;">Resultaat</th>
            </tr>
        </thead>
        <tbody>
            <tr><td><code>[cursus_datum]</code></td><td>Alle groepen, alle data, bullet-gescheiden.</td></tr>
            <tr><td><code>[cursus_datum layout="list"]</code></td><td>Elke groep onder elkaar met label ("Groep 1:", "Groep 2:").</td></tr>
            <tr><td><code>[cursus_datum groep="1"]</code></td><td>Alleen groep 1, alle data.</td></tr>
            <tr><td><code>[cursus_datum groep="1" show="first"]</code></td><td>Alleen de eerste datum van groep 1.</td></tr>
            <tr><td><code>[cursus_datum groep="1" show="first" format="j F"]</code></td><td>Eerste datum, zonder jaar en dagnaam (bijv. "1 juli").</td></tr>
            <tr><td><code>[cursus_datum format="j F Y"]</code></td><td>Alle data zonder dagnaam.</td></tr>
            <tr><td><code>[cursus_datum separator=" | "]</code></td><td>Pipe als scheidingsteken.</td></tr>
            <tr><td><code>[cursus_datum separator="&lt;br&gt;"]</code></td><td>Elke datum op een eigen regel.</td></tr>
        </tbody>
    </table>

    <h2 style="margin-top:2em;"><code>[training_naam]</code></h2>
    <p>Toont de trainingsnaam zoals ingesteld in de metabox. Geen attributen.</p>
    <?php
}

/* =========================================================================
 * AJAX: Save cursusdata for a single post
 *
 * Used by the Cursusdata tab. Cleans data, sorts dates and groups,
 * and updates the legacy 'startdatum' field automatically.
 * ====================================================================== */

// Only register if ASE snippet hasn't already hooked this action.
if ( ! has_action( 'wp_ajax_sfp_save_cursusdata' ) ) {
    add_action( 'wp_ajax_sfp_save_cursusdata', 'sfp_page_config_ajax_save' );
}

function sfp_page_config_ajax_save() {

    check_ajax_referer( 'sfp_cursusdata_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_send_json_error( 'Geen rechten.' );
    }

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) {
        wp_send_json_error( 'Ongeldig post ID.' );
    }

    $raw  = isset( $_POST['cursusdata'] ) ? wp_unslash( $_POST['cursusdata'] ) : '[]';
    $data = json_decode( $raw, true );

    if ( ! is_array( $data ) ) {
        $data = array();
    }

    // Clean: remove empty dates, sort dates within each group.
    $clean = array();
    foreach ( $data as $sm ) {
        if ( ! isset( $sm['data'] ) || ! is_array( $sm['data'] ) ) {
            continue;
        }
        $dates = array();
        foreach ( $sm['data'] as $d ) {
            $d = sanitize_text_field( $d );
            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) && strtotime( $d ) ) {
                $dates[] = $d;
            }
        }
        if ( ! empty( $dates ) ) {
            sort( $dates );
            $clean[] = array( 'data' => $dates );
        }
    }

    // Sort groups by their first date.
    usort( $clean, function( $a, $b ) {
        $da = isset( $a['data'][0] ) ? $a['data'][0] : '';
        $db = isset( $b['data'][0] ) ? $b['data'][0] : '';
        return strcmp( $da, $db );
    } );

    if ( ! empty( $clean ) ) {
        update_post_meta( $post_id, 'sfp_cursusdata', wp_json_encode( $clean ) );
    } else {
        delete_post_meta( $post_id, 'sfp_cursusdata' );
    }

    // Update legacy 'startdatum' field with next upcoming date.
    $today   = wp_date( 'Y-m-d' );
    $closest = null;
    foreach ( $clean as $sm ) {
        foreach ( $sm['data'] as $d ) {
            if ( $d >= $today && ( null === $closest || $d < $closest ) ) {
                $closest = $d;
            }
        }
    }

    if ( $closest ) {
        update_post_meta( $post_id, 'startdatum', $closest );
    } else {
        delete_post_meta( $post_id, 'startdatum' );
    }

    // Invalidate the dashboard transient so the Cursusdata tab reflects
    // the change immediately on next load (instead of waiting up to 6h).
    delete_transient( 'sfp_dashboard_pages_training' );
    delete_transient( 'sfp_dashboard_pages' );

    // Reset the cron notification state for this training. The cron
    // handler listens for this action (see includes/cron.php).
    do_action( 'sfp_page_config_cursusdata_updated', $post_id );

    wp_send_json_success( array(
        'clean'         => $clean,
        'cursusdata'    => $clean,
        'eerstvolgende' => $closest ? sfp_page_config_format_date_nl( $closest, 'short' ) : '-',
    ) );
}

/* =========================================================================
 * AJAX: Toggle hide/show post on dashboard
 * ====================================================================== */

// Only register if ASE snippet hasn't already hooked this action.
if ( ! has_action( 'wp_ajax_sfp_toggle_hidden' ) ) {
    add_action( 'wp_ajax_sfp_toggle_hidden', 'sfp_page_config_ajax_toggle_hidden' );
}

function sfp_page_config_ajax_toggle_hidden() {

    check_ajax_referer( 'sfp_cursusdata_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_send_json_error( 'Geen rechten.' );
    }

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) {
        wp_send_json_error( 'Ongeldig post ID.' );
    }

    $hide   = isset( $_POST['hide'] ) && $_POST['hide'] === '1';
    $hidden = get_option( 'sfp_cursusdata_hidden', array() );
    if ( ! is_array( $hidden ) ) {
        $hidden = array();
    }

    if ( $hide ) {
        if ( ! in_array( $post_id, $hidden ) ) {
            $hidden[] = $post_id;
        }
    } else {
        $hidden = array_values( array_diff( $hidden, array( $post_id ) ) );
    }

    update_option( 'sfp_cursusdata_hidden', $hidden );

    wp_send_json_success( array( 'hidden' => $hidden ) );
}
