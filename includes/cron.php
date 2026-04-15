<?php
/**
 * SFP Page Config - WP-Cron Notifications
 *
 * Daily check for training pages whose last scheduled course date has
 * passed. Sends a single email when new dates need to be planned.
 * Only checks pages with sfp_page_type = 'training'.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Schedule the cron event
 * ====================================================================== */

/**
 * Schedule the daily cursusdata check if not already scheduled.
 */
function sfp_page_config_schedule_cron() {
    if ( ! wp_next_scheduled( 'sfp_page_config_daily_check' ) && ! wp_next_scheduled( 'sfp_daily_check' ) ) {
        wp_schedule_event( strtotime( 'today 08:00' ), 'daily', 'sfp_page_config_daily_check' );
    }
}
add_action( 'init', 'sfp_page_config_schedule_cron' );

/* =========================================================================
 * Cron callback
 * ====================================================================== */

// Only register if ASE snippet hasn't already hooked this action.
if ( ! has_action( 'sfp_page_config_daily_check' ) && ! has_action( 'sfp_daily_check' ) ) {
    add_action( 'sfp_page_config_daily_check', 'sfp_page_config_run_daily_check' );
}

/**
 * Check training pages for expired or soon-expiring course dates and
 * notify the admin ONCE per state change.
 *
 * Notification rules:
 *  - Trigger conditions:
 *      * No course data at all (new training needs dates).
 *      * Only invalid dates present.
 *      * Latest date is in the past (expired training).
 *      * Latest date is within 14 days (approaching expiry).
 *  - State is tracked per post ID in the `sfp_cron_notified` option. The
 *    stored value is the "signature" of the last notified state
 *    (latest_date, 'no-data', or 'invalid').
 *  - A training is notified AGAIN only when its signature changes. Adding
 *    new dates changes the signature, so the training becomes eligible
 *    for a future notification once it approaches expiry again.
 *  - The recipient address comes from the Instellingen tab (cron_email);
 *    falls back to admin_email.
 */
function sfp_page_config_run_daily_check() {

    $site_name = get_bloginfo( 'name' );
    $today     = wp_date( 'Y-m-d' );

    // Recipient: settings override, fallback to admin_email.
    $recipient = sfp_page_config_get_setting( 'cron_email', '' );
    if ( empty( $recipient ) || ! is_email( $recipient ) ) {
        $recipient = get_option( 'admin_email' );
    }

    // Only check PAGES marked as 'training'.
    $pages = get_posts( array(
        'post_type'      => 'page',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'   => 'sfp_page_type',
                'value' => 'training',
            ),
        ),
    ) );

    // Fetch previously notified signatures (per post ID).
    $notified = get_option( 'sfp_cron_notified', array() );
    if ( ! is_array( $notified ) ) {
        $notified = array();
    }

    $needs_new_dates = array();
    $upcoming        = array();
    $new_signatures  = $notified; // Copy; we update only on actual notifies.
    $seen_ids        = array();   // Track existing pages so we can prune stale state.

    foreach ( $pages as $page ) {

        $seen_ids[] = $page->ID;

        $json = get_post_meta( $page->ID, 'sfp_cursusdata', true );
        $data = $json ? json_decode( $json, true ) : array();
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $data = array();
        }

        // Determine state and signature.
        $signature   = '';
        $latest_date = null;

        if ( empty( $data ) || ! is_array( $data ) ) {
            $signature = 'no-data';
        } else {
            foreach ( $data as $sm ) {
                if ( empty( $sm['data'] ) || ! is_array( $sm['data'] ) ) {
                    continue;
                }
                foreach ( $sm['data'] as $date_str ) {
                    $ts = strtotime( $date_str );
                    if ( ! $ts ) {
                        continue;
                    }
                    $parsed = wp_date( 'Y-m-d', $ts );
                    if ( $parsed <= '1970-01-02' ) {
                        continue;
                    }
                    if ( null === $latest_date || $parsed > $latest_date ) {
                        $latest_date = $parsed;
                    }
                }
            }
            if ( null === $latest_date ) {
                $signature = 'invalid';
            } else {
                $signature = $latest_date;
            }
        }

        // Has this exact signature already been notified? If yes, skip.
        $previously_notified = isset( $notified[ $page->ID ] ) && $notified[ $page->ID ] === $signature;
        if ( $previously_notified ) {
            continue;
        }

        // Decide whether the CURRENT state warrants a notification.
        $should_notify = false;
        $message_line  = '';

        if ( 'no-data' === $signature ) {
            $should_notify = true;
            $message_line  = sprintf( '- %s (geen cursusdata ingevoerd)', $page->post_title );
            $needs_new_dates[] = $message_line;
        } elseif ( 'invalid' === $signature ) {
            $should_notify = true;
            $message_line  = sprintf( '- %s (geen geldige cursusdata gevonden)', $page->post_title );
            $needs_new_dates[] = $message_line;
        } else {
            // We have a latest_date. Check where it sits relative to today.
            $days_left = (int) round(
                ( strtotime( $latest_date ) - strtotime( $today ) ) / DAY_IN_SECONDS
            );

            if ( $days_left < 0 ) {
                // Expired: all dates are in the past.
                $should_notify = true;
                $message_line  = sprintf(
                    '- %s (laatste datum was %s, %d dagen geleden)',
                    $page->post_title,
                    sfp_page_config_format_date_nl( $latest_date, 'short' ) ?: $latest_date,
                    abs( $days_left )
                );
                $needs_new_dates[] = $message_line;
            } elseif ( $days_left <= 14 ) {
                // Approaching expiry within 14 days.
                $should_notify = true;
                $message_line  = sprintf(
                    '- %s (laatste datum: %s, nog %d dagen)',
                    $page->post_title,
                    sfp_page_config_format_date_nl( $latest_date, 'short' ) ?: $latest_date,
                    $days_left
                );
                $upcoming[] = $message_line;
            }
            // else: plenty of runway, no notification.
        }

        if ( $should_notify ) {
            $new_signatures[ $page->ID ] = $signature;
        }
    }

    // Prune signatures for pages that no longer exist / were unpublished.
    foreach ( array_keys( $new_signatures ) as $stored_id ) {
        if ( ! in_array( $stored_id, $seen_ids, true ) ) {
            unset( $new_signatures[ $stored_id ] );
        }
    }

    // Always persist pruned state (even if nothing to notify).
    if ( $new_signatures !== $notified ) {
        update_option( 'sfp_cron_notified', $new_signatures, false );
    }

    if ( empty( $needs_new_dates ) && empty( $upcoming ) ) {
        return;
    }

    $message = "Cursusdata update voor {$site_name}\n\n";
    if ( ! empty( $needs_new_dates ) ) {
        $message .= "NIEUWE DATA NODIG:\n" . implode( "\n", $needs_new_dates ) . "\n\n";
    }
    if ( ! empty( $upcoming ) ) {
        $message .= "BIJNA VERLOPEN (binnen 14 dagen):\n" . implode( "\n", $upcoming ) . "\n\n";
    }
    $message .= "Je ontvangt per training slechts één melding. Voer nieuwe data in via het dashboard om de teller te resetten.\n";

    wp_mail( $recipient, "[{$site_name}] Cursusdata update", $message );
}

/* =========================================================================
 * Reset notification state when cursusdata is saved.
 *
 * This hook fires from the AJAX save handler (dashboard.php) via
 * do_action( 'sfp_page_config_cursusdata_updated', $post_id ). We clear
 * the stored signature for that post so the next cron run can send a
 * fresh notification when appropriate.
 * ====================================================================== */
add_action( 'sfp_page_config_cursusdata_updated', 'sfp_page_config_cron_reset_state' );

function sfp_page_config_cron_reset_state( $post_id ) {
    $post_id = (int) $post_id;
    if ( ! $post_id ) {
        return;
    }
    $notified = get_option( 'sfp_cron_notified', array() );
    if ( is_array( $notified ) && isset( $notified[ $post_id ] ) ) {
        unset( $notified[ $post_id ] );
        update_option( 'sfp_cron_notified', $notified, false );
    }
}
