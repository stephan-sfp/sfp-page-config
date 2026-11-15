<?php
/**
 * Test (in een lokale WordPress, via wp eval-file): het doorsturen naar A49.
 *
 * Draait niet in bouw/controle.sh (eindigt op -wp.php).
 *
 * @package SFP_Page_Config
 */

$fouten = 0;
$ok     = function ( $waar, $tekst ) use ( &$fouten ) {
    if ( ! $waar ) {
        ++$fouten;
        echo "FOUT: {$tekst}\n";
    }
};

global $wpdb;
delete_option( 'sfp_calculator_db' );
$ok( sfp_calculator_tabel_klaar(), 'tabel klaar' );
$tabel = sfp_calculator_tabel();
$kolom = $wpdb->get_var( "SHOW COLUMNS FROM {$tabel} LIKE 'crm_status'" ); // phpcs:ignore
$ok( 'crm_status' === $kolom, 'kolom crm_status bestaat' );

$oud = get_option( 'sfp_calculator', array() );

// Instellingen: adres zetten, leeg bewaart, http weigert, - wist.
sfp_calculator_instellingen_bewaar( array( 'code' => 'DPS', 'webhook' => 'https://example.test/hook' ) );
$ok( 'https://example.test/hook' === sfp_calculator_instellingen()['webhook'], 'adres bewaard' );
sfp_calculator_instellingen_bewaar( array( 'webhook' => '' ) );
$ok( 'https://example.test/hook' === sfp_calculator_instellingen()['webhook'], 'leeg bewaart' );
sfp_calculator_instellingen_bewaar( array( 'webhook' => 'http://onveilig.test/' ) );
$ok( 'https://example.test/hook' === sfp_calculator_instellingen()['webhook'], 'http geweigerd' );
$stand = sfp_calculator_stand();
$ok( '' === $stand['instellingen']['webhook'] && true === $stand['webhook_ok'], 'stand verbergt adres' );
$ok( in_array( 'budget_phase', $stand['crm_sleutels'], true ), 'stand noemt sleutels' );

// Een rij die moet worden verstuurd.
$maak = function ( $extra ) use ( $wpdb, $tabel ) {
    $kenmerk = 'DPS-T-' . wp_rand( 100000, 999999 );
    $wpdb->insert( // phpcs:ignore
        $tabel,
        array_merge(
            array(
                'kenmerk'     => $kenmerk,
                'sleutel'     => md5( $kenmerk ),
                'taal'        => 'nl',
                'route'       => 'A',
                'keuzes'      => wp_json_encode( array( 'fase' => 'intern', 'vorm' => '2' ) ),
                'berekening'  => wp_json_encode( array() ),
                'situatie'    => wp_json_encode( array( 'doel' => 'Korter presenteren' ) ),
                'email'       => 'test@example.test',
                'voornaam'    => 'Test',
                'organisatie' => 'Testbedrijf',
                'afdeling'    => 'Marketing',
                'gestart_op'  => gmdate( 'Y-m-d H:i:s' ),
                'afgerond_op' => gmdate( 'Y-m-d H:i:s' ),
                'crm_status'  => 'wacht',
            ),
            $extra
        )
    );
    return (int) $wpdb->insert_id;
};

$gezien = array();
$status = 200;
add_filter(
    'pre_http_request',
    function ( $pre, $args, $url ) use ( &$gezien, &$status ) {
        if ( false === strpos( (string) $url, 'example.test' ) ) {
            return $pre;
        }
        $gezien[] =array( 'url' => $url, 'body' => json_decode( $args['body'], true ) );
        return array( 'headers' => array(), 'body' => '', 'response' => array( 'code' => $status, 'message' => '' ), 'cookies' => array() );
    },
    10,
    3
);

$id = $maak( array() );
sfp_calculator_crm_verwerk( $id );
$rij = sfp_calculator_rij_op( 'id', $id );
$ok( 'verstuurd' === $rij['crm_status'], 'verstuurd (' . $rij['crm_status'] . ')' );
$b = end( $gezien )['body'];
$ok( 'https://example.test/hook' === end( $gezien )['url'], 'naar het adres' );
$ok( 'test@example.test' === $b['email'], 'email' );
$ok( $rij['kenmerk'] === $b['budget_reference'], 'kenmerk' );
$ok( 'approval' === $b['budget_phase'], 'fase approval' );
$ok( 'Marketing' === $b['department'], 'afdeling' );
$ok( 'DPS' === $b['brand'] && 'nl' === $b['language'], 'merk en taal' );
$ok( '' !== $b['budget_url'] && '' !== $b['note'], 'link en notitie' );
$ok( array_keys( sfp_calculator_crm_payload_leeg() ) === array_keys( $b ), 'precies de afgesproken sleutels' );

// Tweede aanroep stuurt niet opnieuw.
$n = count( $gezien );
sfp_calculator_crm_verwerk( $id );
$ok( count( $gezien ) === $n, 'niet dubbel' );

// Mislukt: opnieuw proberen.
$status = 500;
$id2    = $maak( array() );
sfp_calculator_crm_verwerk( $id2 );
$rij2 = sfp_calculator_rij_op( 'id', $id2 );
$ok( 'wacht' === $rij2['crm_status'] && 1 === (int) $rij2['crm_pogingen'] && ! empty( $rij2['crm_volgende'] ), 'herhaalt later' );
$status = 200;

// Proefbegroting: nooit versturen.
$n   = count( $gezien );
$id3 = $maak( array( 'proef' => 1 ) );
sfp_calculator_crm_verwerk( $id3 );
$ok( 'overslaan' === sfp_calculator_rij_op( 'id', $id3 )['crm_status'] && count( $gezien ) === $n, 'proef overgeslagen' );

// Zonder adres: status geen.
sfp_calculator_instellingen_bewaar( array( 'webhook' => '-' ) );
$id4 = $maak( array() );
sfp_calculator_crm_verwerk( $id4 );
$ok( 'geen' === sfp_calculator_rij_op( 'id', $id4 )['crm_status'], 'zonder adres: geen' );

// Fasecodes.
$ok( 'explore' === sfp_calculator_crm_fase( 'verkennen' ) && 'schedule' === sfp_calculator_crm_fase( 'plannen' ) && 'procurement' === sfp_calculator_crm_fase( 'procedure' ) && '' === sfp_calculator_crm_fase( 'x' ), 'fasecodes' );

// Opruimen.
foreach ( array( $id, $id2, $id3, $id4 ) as $weg ) {
    $wpdb->delete( $tabel, array( 'id' => $weg ) ); // phpcs:ignore
}
update_option( 'sfp_calculator', $oud, false );

echo $fouten ? "{$fouten} fout(en)\n" : "OK calculator-crm: alle controles\n";
