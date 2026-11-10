<?php
/**
 * SFP Page Config - Incompanycalculator: de netwerknorm (sinds 3.36.0).
 *
 * Prijzen, groepsregels en extra's staan hier, netwerkbreed. Een site kan in
 * SFP Beheer, Calculator, afwijken (prijzen, valuta, extra's en routes aan of
 * uit); SFP Beheer, Overzicht, toont elke afwijking. Op de Nederlandse sites
 * hoort de norm te staan: een afwijking is een bevinding.
 *
 * Bron: claude/incompanycalculator-besluitenlog.md (B1 tot B16).
 *
 * @package SFP_Page_Config
 * @since   3.36.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De netwerknorm van de calculator.
 *
 * @return array
 */
function sfp_calculator_norm() {
    return array(
        'valuta'               => 'EUR',
        'btw_hoog'             => 21,
        'btw_laag'             => 9,
        // Per groep, excl. btw. 2 dagen: persoonlijke begeleiding als keuze; 3 dagen: inbegrepen.
        'vormen'               => array(
            '1' => array(
                'prijs'       => 3000,
                'begeleiding' => 'nee',
            ),
            '2' => array(
                'prijs'       => 6000,
                'begeleiding' => 'keuze',
            ),
            '3' => array(
                'prijs'       => 8500,
                'begeleiding' => 'inbegrepen',
            ),
        ),
        'begeleiding_prijs'    => 1000,
        // Groepen (B1).
        'groep_norm'           => 8,
        'groep_ruimte'         => 10,
        'klein_tot'            => 4,
        'maatwerk_groepen'     => 5,
        'max_deelnemers'       => 500,
        // Extra's (B5, B6, B7, extra's versie 1).
        'blok_prijs'           => 1000,
        'blok_uren'            => 4,
        'abonnement_uren'      => 12,
        'clinic_prijs'         => 250,
        'clinic_gratis_vanaf'  => 3,
        'boek_centen'          => 1995,
        'boek_gratis_vanaf'    => 2,
        'acteur_prijs'         => 1250,
        'terugkom'             => array(
            'dagdeel' => 1500,
            'online'  => 750,
        ),
        'scan_individueel'     => 250,
        'scan_team'            => 750,
        // Groepsvoordeel (B12).
        'voordeel_vanaf_groep' => 3,
        'voordeel_bedrag'      => 1000,
        // Eigen evenement (B4): richtprijs per coach per dag.
        'evenement_prijs'      => 2000,
        'extras_aan'           => array(
            'coachuren' => true,
            'clinic'    => true,
            'boek'      => true,
            'acteur'    => true,
            'terugkom'  => true,
            'lotj'      => true,
            'scan'      => true,
        ),
        // A: training voor het team; B: sprekers voor een eigen evenement (B4).
        // Meerdere aanbieders is geen route maar een vraag (besluit 10-11).
        'routes_aan'           => array(
            'A' => true,
            'B' => false,
        ),
        // Begroting (B14).
        'geldig_dagen'         => 60,
    );
}

/**
 * Sites waar route B (eigen evenement) standaard aan staat (B4).
 *
 * @return string[] Afkortingen.
 */
function sfp_calculator_evenement_sites() {
    return array( 'DPS', 'SFP', 'SWS' );
}

/**
 * Bedragen die een site mag wijzigen, met label voor het overzicht.
 *
 * @return array<string, string> pad in de norm => label.
 */
function sfp_calculator_prijs_paden() {
    return array(
        'vormen.1.prijs'     => '1 dag per groep',
        'vormen.2.prijs'     => '2 dagen per groep',
        'vormen.3.prijs'     => '3 dagen per groep',
        'begeleiding_prijs'  => 'Persoonlijke begeleiding per groep',
        'blok_prijs'         => 'Blok van 4 uur per deelnemer',
        'clinic_prijs'       => 'Presentation Clinic per deelnemer',
        'boek_centen'        => 'Boek per stuk (centen)',
        'acteur_prijs'       => 'Trainingsacteur per dag',
        'terugkom.dagdeel'   => 'Terugkomsessie dagdeel',
        'terugkom.online'    => 'Terugkomsessie online',
        'scan_individueel'   => 'Scan per deelnemer',
        'scan_team'          => 'Teamscan per groep',
        'voordeel_bedrag'    => 'Groepsvoordeel per groep',
        'evenement_prijs'    => 'Evenement per coach per dag',
    );
}

/**
 * Lees een pad (a.b.c) uit een array.
 *
 * @param  array  $bron Array.
 * @param  string $pad  Pad met punten.
 * @return mixed        Waarde of null.
 */
function sfp_calculator_pad( array $bron, $pad ) {
    foreach ( explode( '.', $pad ) as $deel ) {
        if ( ! is_array( $bron ) || ! array_key_exists( $deel, $bron ) ) {
            return null;
        }
        $bron = $bron[ $deel ];
    }
    return $bron;
}

/**
 * Zet een pad (a.b.c) in een array.
 *
 * @param  array  $doel   Array.
 * @param  string $pad    Pad.
 * @param  mixed  $waarde Waarde.
 * @return void
 */
function sfp_calculator_pad_zet( array &$doel, $pad, $waarde ) {
    $plek  = &$doel;
    $delen = explode( '.', $pad );
    $laatste = array_pop( $delen );
    foreach ( $delen as $deel ) {
        if ( ! isset( $plek[ $deel ] ) || ! is_array( $plek[ $deel ] ) ) {
            $plek[ $deel ] = array();
        }
        $plek = &$plek[ $deel ];
    }
    $plek[ $laatste ] = $waarde;
}

/**
 * De norm zoals deze site hem gebruikt: netwerknorm plus de afwijkingen uit
 * SFP Beheer.
 *
 * @param  array|null $inst Instellingen (sfp_calculator_instellingen()), of null.
 * @return array
 */
function sfp_calculator_site_norm( $inst = null ) {
    $norm = sfp_calculator_norm();
    $inst = is_array( $inst ) ? $inst : ( function_exists( 'sfp_calculator_instellingen' ) ? sfp_calculator_instellingen() : array() );
    $afk  = function_exists( 'sfp_calculator_site_afk' ) ? sfp_calculator_site_afk() : '';
    $norm['routes_aan']['B'] = in_array( $afk, sfp_calculator_evenement_sites(), true );

    $eigen = isset( $inst['prijzen'] ) && is_array( $inst['prijzen'] ) ? $inst['prijzen'] : array();
    foreach ( sfp_calculator_prijs_paden() as $pad => $label ) {
        if ( isset( $eigen[ $pad ] ) && '' !== (string) $eigen[ $pad ] ) {
            sfp_calculator_pad_zet( $norm, $pad, max( 0, (int) $eigen[ $pad ] ) );
        }
    }
    if ( ! empty( $inst['valuta'] ) && preg_match( '/^[A-Z]{3}$/', (string) $inst['valuta'] ) ) {
        $norm['valuta'] = (string) $inst['valuta'];
    }
    foreach ( array_keys( $norm['extras_aan'] ) as $extra ) {
        if ( isset( $inst['extras'][ $extra ] ) && '' !== (string) $inst['extras'][ $extra ] ) {
            $norm['extras_aan'][ $extra ] = '1' === (string) $inst['extras'][ $extra ];
        }
    }
    foreach ( array( 'A', 'B' ) as $route ) {
        if ( isset( $inst['routes'][ $route ] ) && '' !== (string) $inst['routes'][ $route ] ) {
            $norm['routes_aan'][ $route ] = '1' === (string) $inst['routes'][ $route ];
        }
    }
    return $norm;
}

/**
 * Afwijkingen van de norm op deze site, voor SFP Beheer, Overzicht.
 *
 * @return array[] pad, label, waarde, norm.
 */
function sfp_calculator_afwijkingen() {
    if ( ! function_exists( 'sfp_calculator_instellingen' ) ) {
        return array();
    }
    $inst = sfp_calculator_instellingen();
    $norm = sfp_calculator_norm();
    $site = sfp_calculator_site_norm( $inst );
    $uit  = array();
    foreach ( sfp_calculator_prijs_paden() as $pad => $label ) {
        $a = sfp_calculator_pad( $site, $pad );
        $b = sfp_calculator_pad( $norm, $pad );
        if ( (string) $a !== (string) $b ) {
            $uit[] = array(
                'pad'    => 'calculator.prijzen.' . $pad,
                'label'  => 'Calculator: ' . $label,
                'waarde' => (string) $a,
                'norm'   => (string) $b,
            );
        }
    }
    if ( $site['valuta'] !== $norm['valuta'] ) {
        $uit[] = array(
            'pad'    => 'calculator.valuta',
            'label'  => 'Calculator: valuta',
            'waarde' => $site['valuta'],
            'norm'   => $norm['valuta'],
        );
    }
    foreach ( $norm['extras_aan'] as $extra => $aan ) {
        if ( $site['extras_aan'][ $extra ] !== $aan ) {
            $uit[] = array(
                'pad'    => 'calculator.extras.' . $extra,
                'label'  => 'Calculator: extra ' . $extra,
                'waarde' => $site['extras_aan'][ $extra ] ? 'Aan' : 'Uit',
                'norm'   => $aan ? 'Aan' : 'Uit',
            );
        }
    }
    $route_norm      = $norm['routes_aan'];
    $route_norm['B'] = in_array( sfp_calculator_site_afk(), sfp_calculator_evenement_sites(), true );
    foreach ( $route_norm as $route => $aan ) {
        if ( $site['routes_aan'][ $route ] !== $aan ) {
            $uit[] = array(
                'pad'    => 'calculator.routes.' . $route,
                'label'  => 'Calculator: route ' . $route,
                'waarde' => $site['routes_aan'][ $route ] ? 'Aan' : 'Uit',
                'norm'   => $aan ? 'Aan' : 'Uit',
            );
        }
    }
    return $uit;
}
