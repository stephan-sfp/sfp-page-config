<?php
/**
 * DPS Editor Branding - Tofheids Additions v2
 *
 * Aanvullingen op de Aangepaste beheer CSS:
 *   - Admin browser-tab favicon per label
 *   - Browser-tab title prefix "DPS | ..."
 *   - Custom title placeholder in Block editor
 *   - Custom WP login page logo URL en text
 *   - Footer-tekst gelijktrekken
 *
 * INSTALLATIE: ASE Pro > Code Snippets > PHP-snippet toevoegen
 * Titel: "DPS Editor Branding Tofheid v2"
 *
 * @package SFP-DPS
 * @version 1.0.0
 */

// 1. ADMIN BROWSER-TAB FAVICON
add_action( 'admin_head', 'dps_admin_favicon' );
function dps_admin_favicon() {
    $favicon_url = '/wp-content/uploads/2026/01/BB-De-Presenteerschool-icoon-alternatief-1.webp';
    echo '<link rel="icon" type="image/webp" href="' . esc_url( $favicon_url ) . '">';
}

// 2. BROWSER-TAB TITLE PREFIX
add_filter( 'admin_title', 'dps_admin_title_prefix', 10, 2 );
function dps_admin_title_prefix( $admin_title, $title ) {
    return 'DPS | ' . $title;
}

// 3. BLOCK EDITOR TITLE PLACEHOLDER
add_filter( 'enter_title_here', 'dps_enter_title_here' );
function dps_enter_title_here( $title ) {
    return 'Voer titel in voor De Presenteerschool...';
}

// 4. LOGIN PAGE LOGO LINK URL
add_filter( 'login_headerurl', 'dps_login_header_url' );
function dps_login_header_url() {
    return home_url();
}

add_filter( 'login_headertext', 'dps_login_header_text' );
function dps_login_header_text() {
    return 'De Presenteerschool - Onderdeel van School for Professionals';
}

// 5. ADMIN FOOTER TEXT
add_filter( 'admin_footer_text', 'dps_admin_footer_text' );
function dps_admin_footer_text( $text ) {
    return 'Welkom bij <strong>De Presenteerschool</strong>';
}

add_filter( 'update_footer', 'dps_admin_footer_version', 11 );
function dps_admin_footer_version( $text ) {
    return 'Onderdeel van <a href="https://schoolforprofessionals.com" target="_blank" rel="noopener">School for Professionals</a>';
}
