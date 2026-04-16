<?php
/**
 * SFP Page Config - Longread Navigation
 *
 * Outputs the sidebar TOC (desktop) and bottom chapter bar (mobile)
 * on pages/posts with longread mode enabled. Brand colours are pulled
 * from sfp_page_config_get_brand() so every site in the network gets
 * its own colour scheme automatically.
 *
 * Replaces the old ASE Pro "Longreadnavigatie" snippet.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Inject viewport-fit=cover as an early inline script in <head> so that
// env(safe-area-inset-bottom) returns the correct value when the longread
// CSS is parsed. The JS-only approach in longread-nav.js (kept as fallback)
// runs too late: by the time the footer CSS is parsed, the viewport meta
// has already been evaluated without viewport-fit, causing env() to return 0
// and leaving a visible gap between the bar and the screen edge on iPhones.
add_action( 'wp_head', 'sfp_page_config_longread_viewport_fit', 3 );

function sfp_page_config_longread_viewport_fit() {
    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return;
    }
    if ( ! function_exists( 'sfp_page_config_is_longread' ) || ! sfp_page_config_is_longread() ) {
        return;
    }
    // Synchronous inline script: runs before any subsequent CSS is parsed,
    // so env(safe-area-inset-bottom) will resolve correctly in the footer.
    echo '<script id="sfp-viewport-fit">'
        . '(function(){var m=document.querySelector("meta[name=viewport]");'
        . 'if(m&&m.content.indexOf("viewport-fit")===-1){m.content+=", viewport-fit=cover";}'
        . '})();'
        . "</script>\n";
}

// Enqueue the nav JS on the standard enqueue hook. Previously the script
// was registered during wp_footer, which races with wp_print_footer_scripts
// (both at priority 20) and caused the nav to silently disappear on some
// pages. Enqueuing on wp_enqueue_scripts is the standard, reliable place.
add_action( 'wp_enqueue_scripts', 'sfp_page_config_longread_nav_enqueue' );

function sfp_page_config_longread_nav_enqueue() {

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return;
    }

    if ( ! function_exists( 'sfp_page_config_is_longread' ) || ! sfp_page_config_is_longread() ) {
        return;
    }

    wp_enqueue_script(
        'sfp-longread-nav',
        SFP_PAGE_CONFIG_URL . 'assets/longread-nav.js',
        array(),
        SFP_PAGE_CONFIG_VERSION,
        true
    );
}

add_action( 'wp_footer', 'sfp_page_config_longread_nav_output', 20 );

/**
 * Render the longread navigation HTML, CSS and JS in the footer.
 */
function sfp_page_config_longread_nav_output() {

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return;
    }

    if ( ! function_exists( 'sfp_page_config_is_longread' ) || ! sfp_page_config_is_longread() ) {
        return;
    }

    $brand = sfp_page_config_get_brand();

    // CSS custom properties from brand config.
    $lr_brand        = isset( $brand['lr_brand'] )         ? $brand['lr_brand']         : '#333333';
    $lr_bar_bg       = isset( $brand['lr_bar_bg'] )        ? $brand['lr_bar_bg']        : '#333333';
    $lr_bar_text     = isset( $brand['lr_bar_text'] )      ? $brand['lr_bar_text']      : '#ffffff';
    $lr_drawer_bg    = isset( $brand['lr_drawer_bg'] )     ? $brand['lr_drawer_bg']     : '#F7FCFE';
    $lr_drawer_text  = isset( $brand['lr_drawer_text'] )   ? $brand['lr_drawer_text']   : $lr_bar_bg;
    $lr_sidebar_text  = isset( $brand['lr_sidebar_text'] )   ? $brand['lr_sidebar_text']   : '#333333';
    $lr_sidebar_muted = isset( $brand['lr_sidebar_muted'] )  ? $brand['lr_sidebar_muted']  : '#cccccc';
    $lr_sidebar_active= isset( $brand['lr_sidebar_active'] ) ? $brand['lr_sidebar_active'] : $lr_bar_bg;
    $lr_sidebar_h3    = isset( $brand['lr_sidebar_h3'] )     ? $brand['lr_sidebar_h3']     : '#575757';
    $heading_font    = isset( $brand['font'] )            ? $brand['font']            : "'Nunito', sans-serif";

    // Strip characters that could break out of the CSS value context.
    // esc_attr() turns quotes into HTML entities (invalid in CSS), so
    // we use a whitelist instead. Same pattern as body-class.php.
    $safe_font = preg_replace( '/[^a-zA-Z0-9\s\'",\-]/', '', $heading_font );
    ?>

<div id="sfp-lr-nav" aria-label="Artikelnavigatie">
    <!-- Desktop: sticky sidebar TOC -->
    <nav id="sfp-lr-sidebar" class="sfp-lr-sidebar" aria-label="Inhoudsopgave">
        <button id="sfp-lr-sidebar-toggle" class="sfp-lr-sidebar__title" aria-expanded="true">
            Inhoudsopgave
            <svg class="sfp-lr-sidebar__toggle-icon" width="10" height="10" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M3 6l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div id="sfp-lr-toc-list" class="sfp-lr-sidebar__list"></div>
        <button id="sfp-lr-sidebar-top" class="sfp-lr-sidebar__top" aria-label="Naar boven">
            <svg width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M3 10l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Naar boven
        </button>
    </nav>

    <!-- Mobiel: bottom chapter bar -->
    <div id="sfp-lr-bar" class="sfp-lr-bar" role="navigation" aria-label="Hoofdstuknavigatie">
        <div id="sfp-lr-drawer" class="sfp-lr-drawer" aria-hidden="true"></div>
        <button class="sfp-lr-bar__btn" id="sfp-lr-prev" aria-label="Vorig hoofdstuk">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="sfp-lr-bar__chapter" id="sfp-lr-chapter-wrap" role="button" aria-expanded="false" tabindex="0">
            <span id="sfp-lr-chapter-label"></span>
            <svg class="sfp-lr-bar__toggle" width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <line x1="3" y1="8" x2="13" y2="8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <line class="sfp-lr-bar__toggle-v" x1="8" y1="3" x2="8" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <button class="sfp-lr-bar__btn" id="sfp-lr-next" aria-label="Volgend hoofdstuk">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <button class="sfp-lr-bar__btn sfp-lr-bar__top" id="sfp-lr-top" aria-label="Naar boven">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M3 10l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>
</div>

<style>
:root {
    --lr-brand: <?php echo esc_attr( $lr_brand ); ?>;
    --lr-bar-bg: <?php echo esc_attr( $lr_bar_bg ); ?>;
    --lr-bar-text: <?php echo esc_attr( $lr_bar_text ); ?>;
    --lr-drawer-bg: <?php echo esc_attr( $lr_drawer_bg ); ?>;
    --lr-drawer-text: <?php echo esc_attr( $lr_drawer_text ); ?>;
    --lr-sidebar-text: <?php echo esc_attr( $lr_sidebar_text ); ?>;
    --lr-sidebar-muted: <?php echo esc_attr( $lr_sidebar_muted ); ?>;
    --lr-sidebar-active: <?php echo esc_attr( $lr_sidebar_active ); ?>;
    --lr-sidebar-h3: <?php echo esc_attr( $lr_sidebar_h3 ); ?>;
    --lr-heading-font: <?php echo $safe_font; ?>;
    --lr-sticky-offset: 120px;
}

/* Hide floating widgets on longread pages */
.is-longread .whatsapp-float,
.is-longread .wa__btn_popup {
    display: none !important;
    visibility: hidden !important;
}
/* Hide scroll-to-top, Complianz consent button and cookie toggle when
   the mobile longread bar is active (i.e. sidebar does NOT have room). */
.is-longread:not(.sfp-lr-has-sidebar) #ast-scroll-top,
.is-longread:not(.sfp-lr-has-sidebar) .ast-scroll-top,
.is-longread:not(.sfp-lr-has-sidebar) .cmplz-manage-consent,
.is-longread:not(.sfp-lr-has-sidebar) .cookie-toggle {
    display: none !important;
    visibility: hidden !important;
}

/* === Bottom bar (mobile) ===
 *
 * Total bar height = 54px content row + env(safe-area-inset-bottom).
 * The safe-area value is 0 on Android and non-iOS browsers; on iPhones
 * with a home indicator it typically ranges from 20px to 34px.
 *
 * The bar is anchored to bottom: 0 and its background extends across
 * the full height including the safe area, so the bar always sits
 * flush against the physical bottom of the screen. Padding-bottom
 * offsets the button row so the buttons themselves stay above the
 * home indicator.
 *
 * viewport-fit=cover is added to the viewport meta by the JS init so
 * env(safe-area-inset-bottom) actually returns non-zero on iOS; without
 * it Safari ignores the variable and reports 0.
 */
.sfp-lr-bar {
    position: fixed !important;
    bottom: 0 !important;
    left: 0 !important;
    right: 0 !important;
    height: calc(54px + env(safe-area-inset-bottom, 0)) !important;
    padding-bottom: env(safe-area-inset-bottom, 0) !important;
    display: none !important;
    align-items: center !important;
    background: var(--lr-bar-bg) !important;
    color: var(--lr-bar-text) !important;
    z-index: 9990 !important;
    box-shadow: 0 -2px 12px rgba(0,0,0,0.18) !important;
    padding-left: 4px !important;
    padding-right: 4px !important;
    box-sizing: border-box !important;
    /* Force own compositing layer so iOS Safari does not shift the
     * bar while the address bar hides/shows during scroll. */
    transform: translateZ(0) !important;
    will-change: transform !important;
}
.sfp-lr-bar.is-visible { display: flex !important; }

.sfp-lr-bar__btn {
    background: none !important;
    border: none !important;
    color: var(--lr-bar-text) !important;
    padding: 0 14px !important;
    height: 54px !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    opacity: 1 !important;
    transition: opacity 0.15s !important;
}
.sfp-lr-bar__btn:disabled {
    opacity: 0.25 !important;
    cursor: default !important;
    pointer-events: none !important;
}
.sfp-lr-bar__btn:not(:disabled):hover { opacity: 0.7 !important; }
.sfp-lr-bar__top {
    border-left: 1px solid rgba(255,255,255,0.15) !important;
    margin-left: auto !important;
}
.sfp-lr-bar__chapter {
    flex: 1 1 auto !important;
    overflow: hidden !important;
    text-align: left !important;
    font-family: var(--lr-heading-font) !important;
    font-size: 13px !important;
    font-weight: 900 !important;
    line-height: 1.3 !important;
    white-space: nowrap !important;
    text-overflow: ellipsis !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    padding: 0 8px !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 6px !important;
}
.sfp-lr-bar__toggle {
    flex-shrink: 0 !important;
}
.sfp-lr-bar__toggle-v {
    transition: transform 0.2s ease, opacity 0.2s ease !important;
    transform-origin: center !important;
}
.sfp-lr-bar.drawer-open .sfp-lr-bar__toggle-v {
    transform: rotate(90deg) !important;
    opacity: 0 !important;
}

/* Drawer (mobile sub-chapters) */
.sfp-lr-drawer {
    position: absolute !important;
    /* Drawer's bottom edge must align with the bar's top edge, which
     * sits at (54px + safe-area) above the viewport bottom. */
    bottom: calc(54px + env(safe-area-inset-bottom, 0)) !important;
    left: 0 !important;
    right: 0 !important;
    background: var(--lr-drawer-bg) !important;
    max-height: 0 !important;
    overflow: hidden !important;
    transition: max-height 0.25s ease !important;
    -webkit-overflow-scrolling: touch !important;
    overscroll-behavior: contain !important;
}
/* When open, cap the drawer at 70% of the viewport minus the bar height
 * (including safe-area), and enable vertical scrolling so long TOCs
 * (>7-8 H3s) are reachable. */
.sfp-lr-bar.drawer-open .sfp-lr-drawer {
    max-height: calc(70vh - 54px - env(safe-area-inset-bottom, 0)) !important;
    overflow-y: auto !important;
}
.sfp-lr-drawer__item {
    display: block !important;
    padding: 10px 20px !important;
    font-family: var(--lr-heading-font) !important;
    font-size: 12px !important;
    font-weight: 900 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    color: var(--lr-drawer-text) !important;
    text-decoration: none !important;
    border-top: 1px solid rgba(0,0,0,0.08) !important;
    cursor: pointer !important;
}
.sfp-lr-drawer__item.is-active { color: var(--lr-brand) !important; }
.sfp-lr-drawer__item:active { background: rgba(0,0,0,0.05) !important; }

/* Extra bottom padding so content is not hidden behind bar.
 * Add safe-area so the last line of text clears the home indicator
 * as well as the 54px bar. */
.is-longread .site-content,
.is-longread article {
    padding-bottom: calc(60px + env(safe-area-inset-bottom, 0)) !important;
}

/* === Sidebar TOC (desktop) === */
.sfp-lr-sidebar {
    position: fixed !important;
    width: 300px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    display: none;
    opacity: 0;
    transition: opacity 0.25s ease !important;
    z-index: 9989 !important;
    scrollbar-width: none !important;
}
.sfp-lr-sidebar::-webkit-scrollbar { display: none !important; }
.sfp-lr-sidebar.is-visible { opacity: 1 !important; }

.sfp-lr-sidebar__title {
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    background: none !important;
    border: none !important;
    font-family: var(--lr-heading-font) !important;
    font-size: 10px !important;
    font-weight: 900 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.12em !important;
    color: var(--lr-sidebar-muted) !important;
    margin: 0 0 10px 14px !important;
    padding: 0 !important;
    cursor: pointer !important;
    width: 100% !important;
}
.sfp-lr-sidebar__title:hover { color: var(--lr-sidebar-text) !important; }
.sfp-lr-sidebar__toggle-icon {
    transition: transform 0.2s !important;
    flex-shrink: 0 !important;
}
.sfp-lr-sidebar.is-minimized .sfp-lr-sidebar__toggle-icon {
    transform: rotate(-90deg) !important;
}
.sfp-lr-sidebar.is-minimized .sfp-lr-sidebar__list,
.sfp-lr-sidebar.is-minimized .sfp-lr-sidebar__top {
    display: none !important;
}

.sfp-lr-sidebar__top {
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    margin: 12px 0 0 14px !important;
    padding: 0 !important;
    background: none !important;
    border: none !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    color: var(--lr-sidebar-muted) !important;
    text-transform: uppercase !important;
    letter-spacing: 0.08em !important;
    cursor: pointer !important;
    transition: color 0.15s !important;
}
.sfp-lr-sidebar__top:hover { color: var(--lr-sidebar-text) !important; }

.sfp-lr-sidebar__list {
    margin: 0 0 0 12px !important;
    padding: 0 !important;
    border-left: 2px solid var(--lr-sidebar-muted) !important;
}

.sfp-lr-toc__item { margin: 0 !important; padding: 0 !important; }
.sfp-lr-toc__link {
    display: block !important;
    font-family: var(--lr-heading-font) !important;
    font-size: 12px !important;
    font-weight: 400 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    line-height: 1.4 !important;
    padding: 5px 8px 5px 14px !important;
    margin-left: -2px !important;
    color: var(--lr-sidebar-text) !important;
    text-decoration: none !important;
    border-left: 2px solid transparent !important;
    transition: color 0.15s, border-color 0.15s !important;
    word-break: break-word !important;
}
.sfp-lr-toc__link:hover {
    color: var(--lr-sidebar-active) !important;
    border-left-color: var(--lr-sidebar-muted) !important;
}
.sfp-lr-toc__link.is-active {
    color: var(--lr-sidebar-active) !important;
    border-left-color: var(--lr-sidebar-active) !important;
    font-weight: 700 !important;
}

/* H3 sub-items */
.sfp-lr-toc__item--h3 .sfp-lr-toc__link {
    padding-left: 26px !important;
    font-family: 'Roboto', sans-serif !important;
    font-size: 12px !important;
    font-weight: 400 !important;
    text-transform: none !important;
    letter-spacing: 0 !important;
    color: var(--lr-sidebar-h3) !important;
}
.sfp-lr-toc__item--h3 .sfp-lr-toc__link:hover {
    color: var(--lr-sidebar-active) !important;
    border-left-color: var(--lr-sidebar-muted) !important;
}
.sfp-lr-toc__item--h3 .sfp-lr-toc__link.is-active {
    color: var(--lr-sidebar-active) !important;
    border-left-color: var(--lr-sidebar-active) !important;
    font-weight: 700 !important;
}

/* Desktop: only hide the bar when the sidebar actually has room. The JS
 * adds .sfp-lr-has-sidebar to <body> in that case. Without that class we
 * fall back to the mobile bar (e.g. on tablets in landscape or narrow
 * desktops) because the sidebar cannot be positioned there. */
@media (min-width: 1024px) {
    .is-longread.sfp-lr-has-sidebar .sfp-lr-bar { display: none !important; }
    .is-longread.sfp-lr-has-sidebar .site-content,
    .is-longread.sfp-lr-has-sidebar article { padding-bottom: 0 !important; }
}
</style>

<?php
    // Note: the script is enqueued earlier via wp_enqueue_scripts.
}
