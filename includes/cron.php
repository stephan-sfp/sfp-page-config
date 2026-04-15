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
 * Check training pages for expired course dates and notify admin
 * when new dates need to be scheduled.
 *
 * Logic: for each training page, find the latest date across all
 * startmomenten. If that date is in the past, the training needs
 * new dates. Only these trainings trigger an email.
 */
function sfp_page_config_run_daily_check() {

    $admin_email = get_option( 'admin_email' );
    $site_name   = get_bloginfo( 'name' );
    $today       = current_time( 'Y-m-d' );

    // Only check pages marked as 'training' in SFP Page Config.
    $pages = get_posts( array(
        'post_type'      => sfp_page_config_post_types(),
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'   => 'sfp_page_type',
                'value' => 'training',
            ),
        ),
    ) );

    $needs_new_dates = array();
    $upcoming        = array();

    foreach ( $pages as $page ) {

        $json = get_post_meta( $page->ID, 'sfp_cursusdata', true );
        $data = $json ? json_decode( $json, true ) : array();

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $data = array();
        }

        // No course data at all: this training has no dates configured.
        if ( empty( $data ) || ! is_array( $data ) ) {
            $needs_new_dates[] = sprintf(
                '- %s (geen data ingevoerd)',
                $page->post_title
            );
            continue;
        }

        // Find the latest date across all startmomenten.
        $latest_date = null;

        foreach ( $data as $sm ) {
            if ( empty( $sm['data'] ) || ! is_array( $sm['data'] ) ) {
                continue;
            }
            foreach ( $sm['data'] as $date_str ) {
                $parsed = date( 'Y-m-d', strtotime( $date_str ) );
                // Skip invalid dates (1970-01-01 from empty/bad values).
                if ( $parsed <= '1970-01-02' ) {
                    continue;
                }
                if ( null === $latest_date || $parsed > $latest_date ) {
                    $latest_date = $parsed;
                }
            }
        }

        if ( null === $latest_date ) {
            // Data exists but no valid dates found.
            $needs_new_dates[] = sprintf(
                '- %s (geen geldige data gevonden)',
                $page->post_title
            );
            continue;
        }

        if ( $latest_date < $today ) {
            // All dates are in the past: new dates needed.
            $needs_new_dates[] = sprintf(
                '- %s (laatste datum: %s)',
                $page->post_title,
                sfp_page_config_format_date_nl( $latest_date, 'short' ) ?: $latest_date
            );
        } else {
            // Still has future dates: check if the last one is within 14 days.
            $days_left = (int) round(
                ( strtotime( $latest_date ) - strtotime( $today ) ) / DAY_IN_SECONDS
            );
            if ( $days_left <= 14 ) {
                $upcoming[] = sprintf(
                    '- %s (laatste datum: %s, nog %d dagen)',
                    $page->post_title,
                    sfp_page_config_format_date_nl( $latest_date, 'short' ) ?: $latest_date,
                    $days_left
                );
            }
        }
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

    wp_mail( $admin_email, "[{$site_name}] Cursusdata update", $message );
}
