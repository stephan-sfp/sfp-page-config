<?php
/**
 * CVD Editor Branding - Tofheids Additions v2
 *
 * Aanvullingen op de Aangepaste beheer CSS:
 *   - Admin browser-tab favicon per label
 *   - Browser-tab title prefix "CVD | ..."
 *   - Custom title placeholder in Block editor
 *   - Custom WP login page logo URL en text
 *   - Footer-tekst gelijktrekken
 *
 * INSTALLATIE: ASE Pro > Code Snippets > PHP-snippet toevoegen
 * Titel: "CVD Editor Branding Tofheid v2"
 *
 * @package SFP-CVD
 * @version 1.0.0
 */

// 1. ADMIN BROWSER-TAB FAVICON
add_action( 'admin_head', 'cvd_admin_favicon' );
function cvd_admin_favicon() {
    $favicon_url = '/wp-content/uploads/2026/01/BB-Centrum-voor-Didactiek-icoon-alternatief-1.webp';
    echo '<link rel="icon" type="image/webp" href="' . esc_url( $favicon_url ) . '">';
}

// 2. BROWSER-TAB TITLE PREFIX
add_filter( 'admin_title', 'cvd_admin_title_prefix', 10, 2 );
function cvd_admin_title_prefix( $admin_title, $title ) {
    return 'CVD | ' . $title;
}

// 3. BLOCK EDITOR TITLE PLACEHOLDER
add_filter( 'enter_title_here', 'cvd_enter_title_here' );
function cvd_enter_title_here( $title ) {
    return 'Voer titel in voor Centrum voor Didactiek...';
}

// 4. LOGIN PAGE LOGO LINK URL
add_filter( 'login_headerurl', 'cvd_login_header_url' );
function cvd_login_header_url() {
    return home_url();
}

add_filter( 'login_headertext', 'cvd_login_header_text' );
function cvd_login_header_text() {
    return 'Centrum voor Didactiek - Onderdeel van School for Professionals';
}

// 5. ADMIN FOOTER TEXT
add_filter( 'admin_footer_text', 'cvd_admin_footer_text' );
function cvd_admin_footer_text( $text ) {
    return 'Welkom bij <strong>Centrum voor Didactiek</strong>';
}

add_filter( 'update_footer', 'cvd_admin_footer_version', 11 );
function cvd_admin_footer_version( $text ) {
    return 'Onderdeel van <a href="https://schoolforprofessionals.com" target="_blank" rel="noopener">School for Professionals</a>';
}
