<?php
/**
 * Incompanycalculator: de begroting naar SureContact (automation A49).
 *
 * Na een inzending stuurt de site server-side een bericht naar de inkomende
 * webhook van A49, zoals de zelftest dat bij A9 doet. A49 vult de velden en
 * zet calcchain; het formulier zet alleen de tags en de contactvelden.
 * Besluit 12-11-2026 (Nina, Stephan), zie claude/incompanycalculator-besluitenlog.md.
 *
 * Sleutels: email, note, budget_reference, budget_url, budget_phase,
 * department, advice_url, brand en language.
 *
 * @package SFP_Page_Config
 * @since   3.67.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De sleutels van het bericht, leeg.
 *
 * @return array<string, string>
 */
function sfp_calculator_crm_payload_leeg() {
    return array_fill_keys( array( 'email', 'note', 'budget_reference', 'budget_url', 'budget_phase', 'department', 'advice_url', 'brand', 'language' ), '' );
}

/**
 * De fase als vaste waarde voor het CRM (veld budget_phase).
 *
 * @param  string $fase Code in de calculator.
 * @return string
 */
function sfp_calculator_crm_fase( $fase ) {
    $codes = array(
        'verkennen' => 'explore',
        'intern'    => 'approval',
        'plannen'   => 'schedule',
        'procedure' => 'procurement',
    );
    return isset( $codes[ $fase ] ) ? $codes[ $fase ] : '';
}

/**
 * Het bericht voor A49.
 *
 * @param  array $rij Rij.
 * @return array<string, string>
 */
function sfp_calculator_crm_payload( array $rij ) {
    $keuzes = sfp_calculator_json( $rij, 'keuzes' );
    return array_merge(
        sfp_calculator_crm_payload_leeg(),
        array(
            'email'            => (string) $rij['email'],
            'note'             => sfp_calculator_notitie( $rij ),
            'budget_reference' => (string) $rij['kenmerk'],
            'budget_url'       => sfp_calculator_pdf_url( $rij ),
            'budget_phase'     => sfp_calculator_crm_fase( isset( $keuzes['fase'] ) ? (string) $keuzes['fase'] : '' ),
            'department'       => (string) ( $rij['afdeling'] ?? '' ),
            'advice_url'       => sfp_calculator_url( 'boekpagina' ),
            'brand'            => sfp_calculator_site_afk(),
            'language'         => (string) $rij['taal'],
        )
    );
}

/**
 * Wachttijd na een mislukte poging, of null na de laatste.
 *
 * @param  int $poging Aantal mislukte pogingen.
 * @return int|null    Seconden.
 */
function sfp_calculator_crm_wachttijd( $poging ) {
    $tijden = array( 1 => 60, 2 => 300, 3 => 900, 4 => 3600 );
    return isset( $tijden[ $poging ] ) ? $tijden[ $poging ] : null;
}

/**
 * Stuur een bericht naar de webhook.
 *
 * @param  string $url     Adres.
 * @param  array  $payload Gegevens.
 * @return true|string     true, of een korte foutomschrijving.
 */
function sfp_calculator_crm_stuur( $url, array $payload ) {
    $antwoord = wp_remote_post(
        $url,
        array(
            'timeout' => 10,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( $payload ),
        )
    );
    if ( is_wp_error( $antwoord ) ) {
        return $antwoord->get_error_message();
    }
    $status = (int) wp_remote_retrieve_response_code( $antwoord );
    return ( $status >= 200 && $status < 300 ) ? true : 'HTTP ' . $status;
}

/**
 * Verstuur het bericht van één rij, als het aan de beurt is.
 *
 * @param  int $id Rij.
 * @return void
 */
function sfp_calculator_crm_verwerk( $id ) {
    global $wpdb;
    if ( ! sfp_calculator_tabel_klaar() ) {
        return;
    }
    $tabel = sfp_calculator_tabel();
    $nu    = gmdate( 'Y-m-d H:i:s' );
    // Claim de rij voor 90 seconden, zodat de wachtrij en het afronden van
    // het verzoek niet tegelijk hetzelfde bericht sturen.
    $claim = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->prepare(
            "UPDATE {$tabel} SET crm_status = 'bezig', crm_volgende = %s WHERE id = %d AND geanonimiseerd = 0 AND ((crm_status = 'wacht' AND (crm_volgende IS NULL OR crm_volgende <= %s)) OR (crm_status = 'bezig' AND crm_volgende <= %s))", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            gmdate( 'Y-m-d H:i:s', time() + 90 ),
            (int) $id,
            $nu,
            $nu
        )
    );
    if ( ! $claim ) {
        return;
    }
    $rij = sfp_calculator_rij_op( 'id', (string) (int) $id );
    if ( ! $rij ) {
        return;
    }
    // Een proefbegroting uit SFP Beheer gaat nooit naar het CRM.
    if ( ! empty( $rij['proef'] ) || '' === (string) $rij['email'] ) {
        sfp_calculator_rij_bij( (int) $rij['id'], array( 'crm_status' => 'overslaan', 'crm_volgende' => null ) );
        return;
    }
    $url = (string) sfp_calculator_instellingen()['webhook'];
    if ( '' === $url ) {
        sfp_calculator_log( 'fout', 'Niet naar SureContact gestuurd: geen webhookadres ingesteld (' . $rij['kenmerk'] . ').' );
        sfp_calculator_rij_bij( (int) $rij['id'], array( 'crm_status' => 'geen', 'crm_pogingen' => 0, 'crm_volgende' => null ) );
        return;
    }
    $uitkomst = sfp_calculator_crm_stuur( $url, sfp_calculator_crm_payload( $rij ) );
    if ( true === $uitkomst ) {
        sfp_calculator_rij_bij( (int) $rij['id'], array( 'crm_status' => 'verstuurd', 'crm_pogingen' => 0, 'crm_volgende' => null ) );
        return;
    }
    $poging = (int) $rij['crm_pogingen'] + 1;
    $wacht  = sfp_calculator_crm_wachttijd( $poging );
    if ( null === $wacht ) {
        sfp_calculator_log( 'fout', 'Niet naar SureContact gestuurd na 5 pogingen (' . $rij['kenmerk'] . '): ' . $uitkomst );
        sfp_calculator_rij_bij( (int) $rij['id'], array( 'crm_status' => 'mislukt', 'crm_pogingen' => 0, 'crm_volgende' => null ) );
        return;
    }
    sfp_calculator_rij_bij(
        (int) $rij['id'],
        array(
            'crm_status'   => 'wacht',
            'crm_pogingen' => $poging,
            'crm_volgende' => gmdate( 'Y-m-d H:i:s', time() + $wacht ),
        )
    );
    if ( function_exists( 'sfp_calculator_wachtrij_plannen' ) ) {
        sfp_calculator_wachtrij_plannen();
    }
}
