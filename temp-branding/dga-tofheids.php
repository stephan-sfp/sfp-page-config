<?php
/**
 * DGA Editor Branding — Tofheids Additions v2 (PILOT)
 *
 * Aanvullingen op de Aangepaste beheer CSS die niet via CSS alleen kunnen:
 *   - Admin browser-tab favicon per label
 *   - Browser-tab title prefix "DGA | ..."
 *   - Custom title placeholder in Block editor: "Voer titel in voor DGA..."
 *   - Custom WP login page logo URL
 *
 * INSTALLATIE: ASE Pro > Code Snippets > PHP-snippet toevoegen
 * Titel: "DGA Editor Branding Tofheid v2"
 * Hook: admin_init / wp_loaded
 * Locatie: alleen in admin
 *
 * @package SFP-DGA
 * @version 1.0.0
 */

// ============================================================================
// 1. ADMIN BROWSER-TAB FAVICON
// ============================================================================
// Vervangt het generieke WP-icoon in de browser-tab van /wp-admin
add_action( 'admin_head', 'dga_admin_favicon' );
function dga_admin_favicon() {
	$favicon_url = '/wp-content/uploads/2026/01/BB-De-Gespreksacademie-icoon-alternatief-1.webp';
	echo '<link rel="icon" type="image/webp" href="' . esc_url( $favicon_url ) . '">';
}

// ============================================================================
// 2. BROWSER-TAB TITLE PREFIX "DGA | ..."
// ============================================================================
// Maakt de browser-tab title herkenbaar per label, ook bij meerdere admin-tabs
add_filter( 'admin_title', 'dga_admin_title_prefix', 10, 2 );
function dga_admin_title_prefix( $admin_title, $title ) {
	return 'DGA | ' . $title;
}

// ============================================================================
// 3. BLOCK EDITOR TITLE PLACEHOLDER
// ============================================================================
// Vervangt "Voer titel in" door label-specifieke versie in Gutenberg
add_filter( 'enter_title_here', 'dga_enter_title_here' );
function dga_enter_title_here( $title ) {
	return 'Voer titel in voor De Gespreksacademie...';
}

// ============================================================================
// 4. LOGIN PAGE LOGO LINK URL
// ============================================================================
// Login-logo linkt standaard naar wordpress.org. Vervang naar eigen homepage.
add_filter( 'login_headerurl', 'dga_login_header_url' );
function dga_login_header_url() {
	return home_url();
}

// Login logo title attribute
add_filter( 'login_headertext', 'dga_login_header_text' );
function dga_login_header_text() {
	return 'De Gespreksacademie — Onderdeel van School for Professionals';
}

// ============================================================================
// 5. ADMIN FOOTER TEXT (network-consistent)
// ============================================================================
// ASE Pro doet dit al, maar voor zekerheid network-consistent format
add_filter( 'admin_footer_text', 'dga_admin_footer_text' );
function dga_admin_footer_text( $text ) {
	return 'Welkom bij <strong>De Gespreksacademie</strong>';
}

add_filter( 'update_footer', 'dga_admin_footer_version', 11 );
function dga_admin_footer_version( $text ) {
	return 'Onderdeel van <a href="https://schoolforprofessionals.com" target="_blank" rel="noopener">School for Professionals</a>';
}
