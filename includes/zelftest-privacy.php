<?php
/**
 * SFP Page Config - Zelftest: bewaartermijn en verwijderverzoeken
 *
 * Dagelijks:
 *   - pdf-bestanden waarvan de link verlopen is (geldigheid uit het manifest)
 *     gaan weg; de rij blijft;
 *   - rijen ouder dan 2 jaar worden geanonimiseerd: naam, e-mail, UTM's,
 *     gclid, herkomstpagina, codes, sessie en deelafbeeldingen weg, tijden
 *     teruggebracht tot de eerste van de maand. Antwoorden, uitkomst en
 *     lengte blijven voor statistiek;
 *   - logregels ouder dan 30 dagen gaan weg.
 *
 * Een verwijderverzoek (SFP Beheer, REST of Gereedschap, Persoonlijke
 * gegevens wissen) wist rij, pdf en deelafbeeldingen van dat e-mailadres.
 *
 * @package SFP_Page_Config
 * @since   3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'sfp_zelftest_dagelijks', 'sfp_zelftest_dagelijks' );
add_action( 'init', 'sfp_zelftest_dagelijks_plannen' );

/**
 * Plan de dagelijkse taak zolang het onderdeel aan staat of er rijen zijn.
 */
function sfp_zelftest_dagelijks_plannen() {
    if ( wp_next_scheduled( 'sfp_zelftest_dagelijks' ) ) {
        return;
    }
    if ( ( function_exists( 'sfp_page_config_onderdeel_aan' ) && sfp_page_config_onderdeel_aan( 'zelftest' ) ) || '' !== (string) get_option( 'sfp_zelftest_db', '' ) ) {
        wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'sfp_zelftest_dagelijks' );
    }
}

/**
 * Ruim op volgens de bewaartermijn.
 *
 * @return array Tellingen (pdf, geanonimiseerd).
 */
function sfp_zelftest_dagelijks() {
    global $wpdb;
    $uit = array( 'pdf' => 0, 'geanonimiseerd' => 0 );
    if ( ! sfp_zelftest_tabel_klaar() ) {
        return $uit;
    }
    $tabel = sfp_zelftest_tabel();

    // 1. Pdf's na de geldigheid van de link.
    $tests = $wpdb->get_col( "SELECT DISTINCT test FROM {$tabel} WHERE pdf_bestand <> ''" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    foreach ( $tests as $test ) {
        $pakket = sfp_zelftest_pakket( $test );
        $dagen  = $pakket ? (int) $pakket['manifest']['geldigheid_dagen'] : 60;
        $grens  = sfp_zelftest_nu( -max( 1, $dagen ) * DAY_IN_SECONDS );
        $rijen  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabel} WHERE test = %s AND pdf_bestand <> '' AND afgerond_op < %s LIMIT 500", $test, $grens ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        foreach ( (array) $rijen as $rij ) {
            $pad = sfp_zelftest_pdf_pad( $rij );
            if ( '' !== $pad ) {
                wp_delete_file( $pad );
            }
            sfp_zelftest_rij_bij( (int) $rij['id'], array( 'pdf_bestand' => '', 'pdf_status' => 'gewist' ) );
            ++$uit['pdf'];
        }
    }

    // 2. Anonimiseren na 2 jaar.
    $grens = sfp_zelftest_nu( -2 * YEAR_IN_SECONDS );
    $rijen = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabel} WHERE geanonimiseerd = 0 AND gestart_op < %s LIMIT 500", $grens ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    foreach ( (array) $rijen as $rij ) {
        sfp_zelftest_anonimiseer( $rij );
        ++$uit['geanonimiseerd'];
    }

    // 3. Logboek: alleen de laatste 30 dagen.
    $log = get_option( 'sfp_zelftest_log', array() );
    if ( is_array( $log ) && $log ) {
        $grens = sfp_zelftest_nu( -30 * DAY_IN_SECONDS );
        $log   = array_values(
            array_filter(
                $log,
                function ( $regel ) use ( $grens ) {
                    return isset( $regel['tijd'] ) && $regel['tijd'] >= $grens;
                }
            )
        );
        update_option( 'sfp_zelftest_log', $log, false );
    }
    return $uit;
}

/**
 * Een tijd teruggebracht tot de eerste van de maand.
 *
 * @param  string|null $tijd MySQL-datum.
 * @return string|null
 */
function sfp_zelftest_maand( $tijd ) {
    if ( empty( $tijd ) ) {
        return null;
    }
    $t = strtotime( $tijd . ' UTC' );
    return $t ? gmdate( 'Y-m-01 00:00:00', $t ) : null;
}

/**
 * Anonimiseer één rij: wat naar een persoon wijst gaat weg.
 *
 * @param  array $rij Rij.
 * @return void
 */
function sfp_zelftest_anonimiseer( array $rij ) {
    sfp_zelftest_bestanden_weg( $rij );
    $extra    = sfp_zelftest_json( $rij, 'extra' );
    $uitkomst = sfp_zelftest_json( $rij, 'uitkomst' );
    // De notitie is tekst uit de browser: die gaat mee weg. CRM-velden blijven voor statistiek.
    unset( $uitkomst['notitie'] );
    sfp_zelftest_rij_bij(
        (int) $rij['id'],
        array(
            'sessie'          => '',
            'code'            => null,
            'deelcode'        => null,
            'email'           => '',
            'voornaam'        => '',
            'lead_source_url' => '',
            'utm_source'      => '',
            'utm_medium'      => '',
            'utm_campaign'    => '',
            'gclid'           => '',
            'extra'           => wp_json_encode( array( 'dienst' => isset( $extra['dienst'] ) ? (string) $extra['dienst'] : '' ) ),
            'uitkomst'        => wp_json_encode( $uitkomst ),
            'gestart_op'      => sfp_zelftest_maand( $rij['gestart_op'] ),
            'afgerond_op'     => sfp_zelftest_maand( $rij['afgerond_op'] ),
            'pdf_eerste_klik' => sfp_zelftest_maand( $rij['pdf_eerste_klik'] ),
            'crm_volgende'    => null,
            'pdf_volgende'    => null,
            'pdf_bestand'     => '',
            'pdf_status'      => 'gewist',
            'deelbeelden'     => null,
            'geanonimiseerd'  => 1,
        )
    );
}

/**
 * De pdf en de deelafbeeldingen van een rij weg.
 *
 * @param  array $rij Rij.
 * @return void
 */
function sfp_zelftest_bestanden_weg( array $rij ) {
    $pdf = sfp_zelftest_pdf_pad( $rij );
    if ( '' !== $pdf ) {
        wp_delete_file( $pdf );
    }
    if ( preg_match( '/^[0-9A-Z]{14}$/', (string) $rij['deelcode'] ) ) {
        foreach ( array( 'sterk', 'heel' ) as $variant ) {
            $pad = sfp_zelftest_deelbeeld_pad( $rij, $variant, false );
            if ( '' !== $pad ) {
                wp_delete_file( $pad );
            }
        }
    }
}

/**
 * Wis alles van een e-mailadres: rijen, pdf's en deelafbeeldingen.
 *
 * @param  string $email E-mailadres.
 * @return int           Aantal gewiste rijen.
 */
function sfp_zelftest_wis_email( $email ) {
    global $wpdb;
    $email = strtolower( trim( (string) $email ) );
    if ( '' === $email || ! is_email( $email ) || ! sfp_zelftest_tabel_klaar() ) {
        return 0;
    }
    $tabel = sfp_zelftest_tabel();
    $rijen = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabel} WHERE email = %s", $email ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    foreach ( (array) $rijen as $rij ) {
        sfp_zelftest_bestanden_weg( $rij );
        $wpdb->delete( $tabel, array( 'id' => (int) $rij['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }
    // Ook uit het logboek (proefmodus bewaart payloads met e-mailadres).
    $log = get_option( 'sfp_zelftest_log', array() );
    if ( is_array( $log ) && $log ) {
        $log = array_values(
            array_filter(
                $log,
                function ( $regel ) use ( $email ) {
                    return false === stripos( wp_json_encode( $regel ), $email );
                }
            )
        );
        update_option( 'sfp_zelftest_log', $log, false );
    }
    return count( (array) $rijen );
}

/* =========================================================================
 * Privacygereedschap van WordPress
 * ====================================================================== */

add_filter( 'wp_privacy_personal_data_erasers', 'sfp_zelftest_privacy_wisser' );
add_filter( 'wp_privacy_personal_data_exporters', 'sfp_zelftest_privacy_exporteur' );

/**
 * Wisser voor Gereedschap, Persoonlijke gegevens wissen.
 *
 * @param  array $wissers Wissers.
 * @return array
 */
function sfp_zelftest_privacy_wisser( $wissers ) {
    $wissers['sfp-zelftest'] = array(
        'eraser_friendly_name' => 'Zelftest (SFP Page Config)',
        'callback'             => function ( $email ) {
            $aantal = sfp_zelftest_wis_email( $email );
            return array(
                'items_removed'  => $aantal > 0,
                'items_retained' => false,
                'messages'       => array(),
                'done'           => true,
            );
        },
    );
    return $wissers;
}

/**
 * Exporteur voor Gereedschap, Persoonlijke gegevens exporteren.
 *
 * @param  array $exporteurs Exporteurs.
 * @return array
 */
function sfp_zelftest_privacy_exporteur( $exporteurs ) {
    $exporteurs['sfp-zelftest'] = array(
        'exporter_friendly_name' => 'Zelftest (SFP Page Config)',
        'callback'               => function ( $email ) {
            global $wpdb;
            $data  = array();
            $email = strtolower( trim( (string) $email ) );
            if ( '' !== $email && sfp_zelftest_tabel_klaar() ) {
                $tabel = sfp_zelftest_tabel();
                $rijen = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabel} WHERE email = %s", $email ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                foreach ( (array) $rijen as $rij ) {
                    $data[] = array(
                        'group_id'    => 'sfp-zelftest',
                        'group_label' => 'Zelftest',
                        'item_id'     => 'sfp-zelftest-' . (int) $rij['id'],
                        'data'        => array(
                            array( 'name' => 'Test', 'value' => $rij['test'] ),
                            array( 'name' => 'Voornaam', 'value' => $rij['voornaam'] ),
                            array( 'name' => 'E-mailadres', 'value' => $rij['email'] ),
                            array( 'name' => 'Versie', 'value' => $rij['lengte'] ),
                            array( 'name' => 'Gestart', 'value' => (string) $rij['gestart_op'] ),
                            array( 'name' => 'Afgerond', 'value' => (string) $rij['afgerond_op'] ),
                            array( 'name' => 'Resultaatcode', 'value' => (string) $rij['code'] ),
                            array( 'name' => 'Antwoorden', 'value' => (string) $rij['antwoorden'] ),
                            array( 'name' => 'Nieuwsbrief', 'value' => $rij['nieuwsbrief'] ? 'ja' : 'nee' ),
                            array( 'name' => 'Herkomstpagina', 'value' => $rij['lead_source_url'] ),
                        ),
                    );
                }
            }
            return array( 'data' => $data, 'done' => true );
        },
    );
    return $exporteurs;
}
