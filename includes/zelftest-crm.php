<?php
/**
 * SFP Page Config - Zelftest: doorsturen naar SureContact en de wachtrij
 *
 * De site stuurt server-side naar de inkomende webhooks van SureContact. De
 * adressen staan alleen in de instellingen, nooit in de pagina. Per rij gaat
 * send 1 (start) altijd voor send 2 (afgerond).
 *
 * Mislukt een verzending, dan opnieuw na 1, 5, 30 en 120 minuten; daarna
 * 'mislukt' en een melding in SFP Beheer. In proefmodus gaat er niets naar
 * SureContact: de payload komt in het logboek.
 *
 * De wachtrij loopt na elk antwoord aan de bezoeker (shutdown) en elke
 * minuut via cron, alleen zolang er werk is.
 *
 * @package SFP_Page_Config
 * @since   3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Wachttijd na een mislukte poging, in seconden.
 *
 * @param  int $poging Aantal mislukte pogingen tot nu (1 of meer).
 * @return int|null    Seconden, of null als er geen poging meer komt.
 */
function sfp_zelftest_wachttijd( $poging ) {
    $tijden = array( 1 => 60, 2 => 300, 3 => 1800, 4 => 7200 );
    return isset( $tijden[ $poging ] ) ? $tijden[ $poging ] : null;
}

/**
 * Send 1: wat bekend is bij de start (Nina, sectie 6.2).
 *
 * @param  array $rij Rij.
 * @return array
 */
function sfp_zelftest_payload_start( array $rij ) {
    return array(
        'email'           => (string) $rij['email'],
        'first_name'      => (string) $rij['voornaam'],
        'test_length'     => (string) $rij['lengte'],
        'lead_source_url' => (string) $rij['lead_source_url'],
        'newsletter'      => (bool) $rij['nieuwsbrief'],
    );
}

/**
 * Send 2: bij afronden (Nina, sectie 6.2). De CRM-velden komen uit de
 * uitkomst die bij het afronden is gecontroleerd.
 *
 * @param  array $rij    Rij.
 * @param  array $pakket Pakket.
 * @return array
 */
function sfp_zelftest_payload_afgerond( array $rij, array $pakket ) {
    $uitkomst = sfp_zelftest_json( $rij, 'uitkomst' );
    $payload  = array(
        'email'      => (string) $rij['email'],
        'first_name' => (string) $rij['voornaam'],
        'result_url' => sfp_zelftest_adres( $rij['test'], 'pdf', array( 'code' => $rij['code'] ) ),
    );
    foreach ( ( isset( $uitkomst['crm'] ) ? (array) $uitkomst['crm'] : array() ) as $veld => $waarde ) {
        $payload[ $veld ] = (string) $waarde;
    }
    $payload['note'] = sfp_zelftest_notitie( $rij, $pakket );
    return $payload;
}

/**
 * De notitie voor het team: versie en datum, de regels van het label, de
 * antwoorden op de vragen uit manifest.crm.notitie_vragen en de link.
 *
 * @param  array $rij    Rij.
 * @param  array $pakket Pakket.
 * @return string
 */
function sfp_zelftest_notitie( array $rij, array $pakket ) {
    $uitkomst   = sfp_zelftest_json( $rij, 'uitkomst' );
    $antwoorden = sfp_zelftest_json( $rij, 'antwoorden' );
    $taal       = sfp_zelftest_taal( $pakket );
    $regels     = array();

    $naam     = sfp_zelftest_tekst( $pakket, 'algemeen.testnaam' );
    $regels[] = ( '' !== $naam ? $naam : $pakket['test'] ) . ', ' . wp_date( 'j F Y H:i', strtotime( $rij['afgerond_op'] . ' UTC' ) );
    foreach ( ( isset( $uitkomst['notitie'] ) ? (array) $uitkomst['notitie'] : array() ) as $regel ) {
        $regels[] = $regel[0] . ': ' . $regel[1];
    }

    $vragen = array();
    foreach ( (array) $pakket['inhoud']['vragen'] as $vraag ) {
        $vragen[ (int) $vraag['nr'] ] = isset( $vraag[ $taal ] ) ? $vraag[ $taal ] : array();
    }
    $lijst = isset( $pakket['manifest']['crm']['notitie_vragen'] ) ? array_map( 'intval', (array) $pakket['manifest']['crm']['notitie_vragen'] ) : array();
    foreach ( $lijst as $nr ) {
        if ( ! isset( $antwoorden[ $nr ] ) || ! isset( $vragen[ $nr ]['v'] ) ) {
            continue;
        }
        $letters = (array) $antwoorden[ $nr ];
        $teksten = array();
        foreach ( $letters as $l ) {
            $i = strpos( 'abcdefghi', (string) $l );
            if ( false !== $i && isset( $vragen[ $nr ]['o'][ $i ] ) ) {
                $teksten[] = $vragen[ $nr ]['o'][ $i ];
            }
        }
        $regels[] = $nr . '. ' . $vragen[ $nr ]['v'] . ' ' . implode( '; ', $teksten );
    }
    $regels[] = sfp_zelftest_adres( $rij['test'], 'pdf', array( 'code' => $rij['code'] ) );
    return implode( "\n", $regels );
}

/**
 * Stuur een payload naar een webhook.
 *
 * @param  string $url     Adres.
 * @param  array  $payload Gegevens.
 * @return true|string     true, of een korte foutomschrijving.
 */
function sfp_zelftest_webhook( $url, array $payload ) {
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
 * Werk de CRM-stappen van één rij af, als ze aan de beurt zijn.
 *
 * @param  int $id Rij.
 * @return void
 */
function sfp_zelftest_crm_verwerk( $id ) {
    global $wpdb;
    if ( ! sfp_zelftest_tabel_klaar() ) {
        return;
    }
    // Claim de rij voor 90 seconden, zodat cron en het afronden van een
    // verzoek niet tegelijk dezelfde verzending doen.
    $tabel   = sfp_zelftest_tabel();
    $nu      = sfp_zelftest_nu();
    $claimed = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->prepare(
            "UPDATE {$tabel} SET crm_volgende = %s WHERE id = %d AND geanonimiseerd = 0 AND (crm_start = 'wacht' OR crm_afgerond = 'wacht') AND (crm_volgende IS NULL OR crm_volgende <= %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            sfp_zelftest_nu( 90 ),
            (int) $id,
            $nu
        )
    );
    if ( ! $claimed ) {
        return;
    }
    $rij = sfp_zelftest_rij_op( 'id', (string) (int) $id );
    if ( ! $rij ) {
        return;
    }
    $pakket = sfp_zelftest_pakket( $rij['test'] );
    if ( ! $pakket ) {
        // Zonder pakket kan er niets worden opgebouwd: eindstand, met een melding.
        sfp_zelftest_log( $rij['test'], 'fout', 'Geen pakket voor deze test; CRM-stappen van rij ' . (int) $rij['id'] . ' gestopt.' );
        sfp_zelftest_rij_bij(
            (int) $rij['id'],
            array(
                'crm_start'    => 'wacht' === $rij['crm_start'] ? 'mislukt' : $rij['crm_start'],
                'crm_afgerond' => 'wacht' === $rij['crm_afgerond'] ? 'mislukt' : $rij['crm_afgerond'],
                'crm_volgende' => null,
            )
        );
        return;
    }
    $inst  = sfp_zelftest_instellingen( $rij['test'] );
    $proef = '1' === (string) $inst['proefmodus'];

    $stappen = array(
        'crm_start'    => array( 'webhook_start', 'sfp_zelftest_payload_start' ),
        'crm_afgerond' => array( 'webhook_afgerond', 'sfp_zelftest_payload_afgerond' ),
    );
    foreach ( $stappen as $kolom => $stap ) {
        if ( 'wacht' !== $rij[ $kolom ] ) {
            continue;
        }
        // Send 2 wacht tot send 1 klaar is (of definitief mislukt).
        if ( 'crm_afgerond' === $kolom && 'wacht' === $rij['crm_start'] ) {
            return;
        }
        $payload = call_user_func( $stap[1], $rij, $pakket );
        if ( $proef ) {
            sfp_zelftest_log( $rij['test'], 'proef', 'crm_start' === $kolom ? 'Send 1 (start), niet verstuurd: proefmodus' : 'Send 2 (afgerond), niet verstuurd: proefmodus', $payload );
            sfp_zelftest_rij_bij( (int) $rij['id'], array( $kolom => 'proef', 'crm_pogingen' => 0, 'crm_volgende' => null ) );
            $rij[ $kolom ] = 'proef';
            continue;
        }
        $url = (string) $inst[ $stap[0] ];
        if ( '' === $url ) {
            sfp_zelftest_log( $rij['test'], 'fout', ( 'crm_start' === $kolom ? 'Send 1' : 'Send 2' ) . ' niet verstuurd: geen webhookadres ingesteld (rij ' . (int) $rij['id'] . ').' );
            sfp_zelftest_rij_bij( (int) $rij['id'], array( $kolom => 'mislukt', 'crm_pogingen' => 0, 'crm_volgende' => null ) );
            $rij[ $kolom ] = 'mislukt';
            continue;
        }
        $uitkomst = sfp_zelftest_webhook( $url, $payload );
        if ( true === $uitkomst ) {
            sfp_zelftest_rij_bij( (int) $rij['id'], array( $kolom => 'verstuurd', 'crm_pogingen' => 0, 'crm_volgende' => null ) );
            $rij[ $kolom ] = 'verstuurd';
            continue;
        }
        $poging = (int) $rij['crm_pogingen'] + 1;
        $wacht  = sfp_zelftest_wachttijd( $poging );
        if ( null === $wacht ) {
            sfp_zelftest_log( $rij['test'], 'fout', ( 'crm_start' === $kolom ? 'Send 1' : 'Send 2' ) . ' definitief mislukt na 5 pogingen (rij ' . (int) $rij['id'] . '): ' . $uitkomst );
            sfp_zelftest_rij_bij( (int) $rij['id'], array( $kolom => 'mislukt', 'crm_pogingen' => 0, 'crm_volgende' => null ) );
            $rij[ $kolom ] = 'mislukt';
            continue;
        }
        sfp_zelftest_rij_bij( (int) $rij['id'], array( 'crm_pogingen' => $poging, 'crm_volgende' => sfp_zelftest_nu( $wacht ) ) );
        return;
    }
}

/* =========================================================================
 * Cron: de wachtrij
 * ====================================================================== */

add_filter( 'cron_schedules', 'sfp_zelftest_cron_schema' );

/**
 * Een schema van één minuut voor de wachtrij.
 *
 * @param  array $schemas Schema's.
 * @return array
 */
function sfp_zelftest_cron_schema( $schemas ) {
    $schemas['sfp_zelftest_minuut'] = array(
        'interval' => MINUTE_IN_SECONDS,
        'display'  => 'Elke minuut (SFP Zelftest)',
    );
    return $schemas;
}

add_action( 'sfp_zelftest_wachtrij', 'sfp_zelftest_wachtrij' );

/**
 * Plan de wachtrij als hij nog niet gepland is.
 *
 * @return void
 */
function sfp_zelftest_cron_plannen() {
    if ( ! wp_next_scheduled( 'sfp_zelftest_wachtrij' ) ) {
        wp_schedule_event( time() + MINUTE_IN_SECONDS, 'sfp_zelftest_minuut', 'sfp_zelftest_wachtrij' );
    }
}

/**
 * Werk de wachtrij af: CRM-stappen en pdf's die aan de beurt zijn. Stopt de
 * eigen planning als er niets meer wacht.
 *
 * @return void
 */
function sfp_zelftest_wachtrij() {
    global $wpdb;
    if ( ! sfp_zelftest_tabel_klaar() ) {
        return;
    }
    $tabel = sfp_zelftest_tabel();
    $nu    = sfp_zelftest_nu();
    // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $crm = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$tabel} WHERE geanonimiseerd = 0 AND (crm_start = 'wacht' OR crm_afgerond = 'wacht') AND (crm_volgende IS NULL OR crm_volgende <= %s) ORDER BY id LIMIT 20", $nu ) );
    foreach ( $crm as $id ) {
        sfp_zelftest_crm_verwerk( (int) $id );
    }
    if ( function_exists( 'sfp_zelftest_pdf_verwerk' ) ) {
        // Ook een pdf die 'bezig' bleef staan omdat een eerder verzoek stopte.
        $pdf = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$tabel} WHERE geanonimiseerd = 0 AND pdf_status IN ('wacht','bezig') AND (pdf_volgende IS NULL OR pdf_volgende <= %s) ORDER BY id LIMIT 3", $nu ) );
        foreach ( $pdf as $id ) {
            sfp_zelftest_pdf_verwerk( (int) $id );
        }
    }
    $nog = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabel} WHERE geanonimiseerd = 0 AND (crm_start = 'wacht' OR crm_afgerond = 'wacht' OR pdf_status IN ('wacht','bezig'))" );
    // phpcs:enable
    if ( ! $nog ) {
        wp_clear_scheduled_hook( 'sfp_zelftest_wachtrij' );
    }
}
