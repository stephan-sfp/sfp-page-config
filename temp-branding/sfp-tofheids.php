<?php
/**
 * SFP Editor Branding - Tofheids Additions v2
 *
 * Aanvullingen op de Aangepaste beheer CSS:
 *   - Admin browser-tab favicon per label
 *   - Browser-tab title prefix "SFP | ..."
 *   - Custom title placeholder in Block editor
 *   - Custom WP login page logo URL en text
 *   - Footer-tekst gelijktrekken
 *
 * INSTALLATIE: ASE Pro > Code Snippets > PHP-snippet toevoegen
 * Titel: "SFP Editor Branding Tofheid v2"
 *
 * @package SFP-SFP
 * @version 1.0.0
 */

// 1. ADMIN BROWSER-TAB FAVICON
add_action( 'admin_head', 'sfp_admin_favicon' );
function sfp_admin_favicon() {
    $favicon_url = '/wp-content/uploads/2026/01/BB-School-for-Professionals-icoon-alternatief-1.webp';
    echo '<link rel="icon" type="image/webp" href="' . esc_url( $favicon_url ) . '">';
}

// 2. BROWSER-TAB TITLE PREFIX
add_filter( 'admin_title', 'sfp_admin_title_prefix', 10, 2 );
function sfp_admin_title_prefix( $admin_title, $title ) {
    return 'SFP | ' . $title;
}

// 3. BLOCK EDITOR TITLE PLACEHOLDER
add_filter( 'enter_title_here', 'sfp_enter_title_here' );
function sfp_enter_title_here( $title ) {
    return 'Voer titel in voor School for Professionals...';
}

// 4. LOGIN PAGE LOGO LINK URL
add_filter( 'login_headerurl', 'sfp_login_header_url' );
function sfp_login_header_url() {
    return home_url();
}

add_filter( 'login_headertext', 'sfp_login_header_text' );
function sfp_login_header_text() {
    return 'School for Professionals - Onderdeel van School for Professionals';
}

// 5. ADMIN FOOTER TEXT
add_filter( 'admin_footer_text', 'sfp_admin_footer_text' );
function sfp_admin_footer_text( $text ) {
    return 'Welkom bij <strong>School for Professionals</strong>';
}

add_filter( 'update_footer', 'sfp_admin_footer_version', 11 );
function sfp_admin_footer_version( $text ) {
    return 'Onderdeel van <a href="https://schoolforprofessionals.com" target="_blank" rel="noopener">School for Professionals</a>';
}
