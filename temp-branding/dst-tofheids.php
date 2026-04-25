<?php
/**
 * DST Editor Branding - Tofheids Additions v2
 *
 * Aanvullingen op de Aangepaste beheer CSS:
 *   - Admin browser-tab favicon per label
 *   - Browser-tab title prefix "DST | ..."
 *   - Custom title placeholder in Block editor
 *   - Custom WP login page logo URL en text
 *   - Footer-tekst gelijktrekken
 *
 * INSTALLATIE: ASE Pro > Code Snippets > PHP-snippet toevoegen
 * Titel: "DST Editor Branding Tofheid v2"
 *
 * @package SFP-DST
 * @version 1.0.0
 */

// 1. ADMIN BROWSER-TAB FAVICON
add_action( 'admin_head', 'dst_admin_favicon' );
function dst_admin_favicon() {
    $favicon_url = '/wp-content/uploads/2026/01/BB-De-Schrijftrainers-icoon-alternatief-1.webp';
    echo '<link rel="icon" type="image/webp" href="' . esc_url( $favicon_url ) . '">';
}

// 2. BROWSER-TAB TITLE PREFIX
add_filter( 'admin_title', 'dst_admin_title_prefix', 10, 2 );
function dst_admin_title_prefix( $admin_title, $title ) {
    return 'DST | ' . $title;
}

// 3. BLOCK EDITOR TITLE PLACEHOLDER
add_filter( 'enter_title_here', 'dst_enter_title_here' );
function dst_enter_title_here( $title ) {
    return 'Voer titel in voor De Schrijftrainers...';
}

// 4. LOGIN PAGE LOGO LINK URL
add_filter( 'login_headerurl', 'dst_login_header_url' );
function dst_login_header_url() {
    return home_url();
}

add_filter( 'login_headertext', 'dst_login_header_text' );
function dst_login_header_text() {
    return 'De Schrijftrainers - Onderdeel van School for Professionals';
}

// 5. ADMIN FOOTER TEXT
add_filter( 'admin_footer_text', 'dst_admin_footer_text' );
function dst_admin_footer_text( $text ) {
    return 'Welkom bij <strong>De Schrijftrainers</strong>';
}

add_filter( 'update_footer', 'dst_admin_footer_version', 11 );
function dst_admin_footer_version( $text ) {
    return 'Onderdeel van <a href="https://schoolforprofessionals.com" target="_blank" rel="noopener">School for Professionals</a>';
}
