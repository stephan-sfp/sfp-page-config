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

    wp_enqueue_style(
        'sfp-longread-nav',
        SFP_PAGE_CONFIG_URL . 'assets/longread-nav.css',
        array(),
        SFP_PAGE_CONFIG_VERSION
    );

    // Dynamic brand colours as CSS custom properties.
    $brand = sfp_page_config_get_brand();

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
    $safe_font = preg_replace( '/[^a-zA-Z0-9\s\'\",\-]/', '', $heading_font );

    $root_css = ':root {'
        . '--lr-brand:'          . esc_attr( $lr_brand )         . ';'
        . '--lr-bar-bg:'         . esc_attr( $lr_bar_bg )        . ';'
        . '--lr-bar-text:'       . esc_attr( $lr_bar_text )      . ';'
        . '--lr-drawer-bg:'      . esc_attr( $lr_drawer_bg )     . ';'
        . '--lr-drawer-text:'    . esc_attr( $lr_drawer_text )   . ';'
        . '--lr-sidebar-text:'   . esc_attr( $lr_sidebar_text )  . ';'
        . '--lr-sidebar-muted:'  . esc_attr( $lr_sidebar_muted ) . ';'
        . '--lr-sidebar-active:' . esc_attr( $lr_sidebar_active ). ';'
        . '--lr-sidebar-h3:'     . esc_attr( $lr_sidebar_h3 )    . ';'
        . '--lr-heading-font:'   . $safe_font                    . ';'
        . '--lr-sticky-offset:120px'
        . '}';

    wp_add_inline_style( 'sfp-longread-nav', $root_css );

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

<?php
    // Note: styles and script are enqueued via wp_enqueue_scripts.
}
