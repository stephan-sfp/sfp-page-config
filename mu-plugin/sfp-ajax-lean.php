<?php
/**
 * Plugin Name: SFP Ajax Lean
 * Description: Laadt bij LatePoint-boekformulieraanroepen van bezoekers alleen de plugins die het boeken nodig heeft. Geplaatst en beheerd door SFP Page Config; wijzig dit bestand niet met de hand.
 * Version:     1.0.0
 * Author:      School for Professionals
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Decide whether this request is a front-end LatePoint call that may run lean.
 *
 * Only admin-ajax requests with action latepoint_route_call qualify, and only
 * when they do not come from wp-admin: LatePoint's own back-office uses the
 * same action and needs the full plugin set.
 *
 * @return bool
 */
function sfp_ajax_lean_is_target_request() {
    if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
        return false;
    }
    // phpcs:ignore WordPress.Security.NonceVerification -- read-only routing check.
    $action = isset( $_REQUEST['action'] ) ? (string) $_REQUEST['action'] : '';
    if ( 'latepoint_route_call' !== $action ) {
        return false;
    }
    $referer = isset( $_SERVER['HTTP_REFERER'] ) ? (string) $_SERVER['HTTP_REFERER'] : '';
    if ( '' !== $referer && false !== strpos( $referer, '/wp-admin/' ) ) {
        return false;
    }
    return true;
}

/**
 * Plugin folders that must always load, whatever the settings say.
 *
 * @return string[]
 */
function sfp_ajax_lean_protected() {
    return array( 'sfp-page-config', 'latepoint', 'latepoint-pro-features', 'latepoint-google-calendar', 'latepoint-outlook-calendar' );
}

if ( sfp_ajax_lean_is_target_request() ) {
    $sfp_ajax_lean_settings = get_option( 'sfp_settings', array() );
    $sfp_ajax_lean_skip     = ( is_array( $sfp_ajax_lean_settings ) && ! empty( $sfp_ajax_lean_settings['ajax_lean_enabled'] ) && ! empty( $sfp_ajax_lean_settings['ajax_lean_skip'] ) && is_array( $sfp_ajax_lean_settings['ajax_lean_skip'] ) )
        ? array_diff( $sfp_ajax_lean_settings['ajax_lean_skip'], sfp_ajax_lean_protected() )
        : array();

    if ( $sfp_ajax_lean_skip ) {
        add_filter(
            'option_active_plugins',
            function ( $plugins ) use ( $sfp_ajax_lean_skip ) {
                if ( ! is_array( $plugins ) || ! in_array( 'sfp-page-config/sfp-page-config.php', $plugins, true ) ) {
                    return $plugins;
                }
                $kept = array();
                foreach ( $plugins as $plugin ) {
                    $folder = strtok( (string) $plugin, '/' );
                    if ( ! in_array( $folder, $sfp_ajax_lean_skip, true ) ) {
                        $kept[] = $plugin;
                    }
                }
                $GLOBALS['sfp_ajax_lean_skipped'] = count( $plugins ) - count( $kept );
                return $kept;
            },
            1
        );
        add_action(
            'init',
            function () {
                if ( ! headers_sent() && isset( $GLOBALS['sfp_ajax_lean_skipped'] ) ) {
                    header( 'X-SFP-Ajax-Lean: ' . (int) $GLOBALS['sfp_ajax_lean_skipped'] );
                }
            }
        );
    }
    unset( $sfp_ajax_lean_settings, $sfp_ajax_lean_skip );
}
