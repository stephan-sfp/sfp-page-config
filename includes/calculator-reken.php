<?php
/**
 * SFP Page Config - Incompanycalculator: de rekenregel op de server (sinds 3.36.0).
 *
 * Kopie van assets/calculator/motor.js. De server vertrouwt de uitkomst uit
 * de browser niet en rekent bij het opslaan zelf. tests/calculator-reken.php
 * vergelijkt beide op dezelfde scenario's: wijzig ze altijd samen.
 *
 * @package SFP_Page_Config
 * @since   3.36.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Geheel getal binnen grenzen.
 *
 * @param  mixed $w   Waarde.
 * @param  int   $min Minimum.
 * @param  int   $max Maximum.
 * @return int
 */
function sfp_calculator_geheel( $w, $min, $max ) {
    $n = is_numeric( $w ) ? (int) $w : ( is_string( $w ) && preg_match( '/^\s*-?\d+/', $w, $m ) ? (int) $m[0] : 0 );
    return max( $min, min( $max, $n ) );
}

/**
 * Verdeel n deelnemers zo gelijk mogelijk over groepen.
 *
 * @param  int   $n    Deelnemers.
 * @param  array $norm Norm.
 * @return int[]
 */
function sfp_calculator_verdeel( $n, array $norm ) {
    $n = (int) $n;
    if ( $n <= 0 ) {
        return array();
    }
    if ( $n <= (int) $norm['groep_ruimte'] ) {
        return array( $n );
    }
    $g     = (int) ceil( $n / (int) $norm['groep_norm'] );
    $basis = intdiv( $n, $g );
    $rest  = $n % $g;
    $uit   = array();
    for ( $i = 0; $i < $g; $i++ ) {
        $uit[] = $basis + ( $i < $rest ? 1 : 0 );
    }
    return $uit;
}

/**
 * Euro's van aantal x prijs in centen, afgerond op hele euro's.
 *
 * @param  int $aantal Aantal.
 * @param  int $centen Prijs.
 * @return int
 */
function sfp_calculator_euros( $aantal, $centen ) {
    return (int) round( ( $aantal * $centen ) / 100 );
}

/**
 * Eén regel van de begroting.
 *
 * @return array
 */
function sfp_calculator_regel( $code, $aantal, $prijs, $btw, $gratis ) {
    return array(
        'code'   => (string) $code,
        'aantal' => (int) $aantal,
        'prijs'  => (int) $prijs,
        'bedrag' => $gratis ? 0 : sfp_calculator_euros( (int) $aantal, (int) $prijs ),
        'btw'    => (int) $btw,
        'gratis' => (bool) $gratis,
    );
}

/**
 * Uren per deelnemer voor een deel van de groep (zie motor.js, persoonUren).
 *
 * @param  mixed $w    array( deelnemers, uren ).
 * @param  int   $pp   Deelnemers in de berekening.
 * @param  array $norm Norm.
 * @return array|null  deelnemers, uren, blokken.
 */
function sfp_calculator_persoon_uren( $w, $pp, array $norm ) {
    if ( ! is_array( $w ) ) {
        return null;
    }
    $uren    = sfp_calculator_geheel( isset( $w['uren'] ) ? $w['uren'] : 0, 0, 200 );
    $blokken = (int) ceil( $uren / (int) $norm['blok_uren'] );
    $n       = sfp_calculator_geheel( isset( $w['deelnemers'] ) ? $w['deelnemers'] : 0, 0, (int) $pp );
    if ( ! $blokken || ! $n ) {
        return null;
    }
    return array(
        'deelnemers' => $n,
        'uren'       => $blokken * (int) $norm['blok_uren'],
        'blokken'    => $blokken,
    );
}

/**
 * De berekening voor één aantal deelnemers.
 *
 * @return array
 */
function sfp_calculator_reken_voor( array $k, array $norm, $deelnemers, $onbekend ) {
    $verdeling = $onbekend ? array( (int) $norm['groep_norm'] ) : sfp_calculator_verdeel( $deelnemers, $norm );
    $groepen   = count( $verdeling );
    $pp        = $onbekend ? (int) $norm['groep_norm'] : (int) $deelnemers;
    $vorm      = isset( $k['vorm'] ) ? (string) $k['vorm'] : '';
    $v         = isset( $norm['vormen'][ $vorm ] ) ? $norm['vormen'][ $vorm ] : null;
    $ex        = isset( $k['extras'] ) && is_array( $k['extras'] ) ? $k['extras'] : array();
    $aan       = isset( $norm['extras_aan'] ) ? (array) $norm['extras_aan'] : array();
    $regels    = array();
    $meldingen = array();

    if ( ! $v || $groepen < 1 ) {
        return array(
            'groepen'    => 0,
            'verdeling'  => array(),
            'deelnemers' => 0,
            'regels'     => array(),
            'meldingen'  => array(),
        );
    }
    $hoog = (int) $norm['btw_hoog'];

    $regels[] = sfp_calculator_regel( 'vorm_' . $vorm, $groepen, (int) $v['prijs'] * 100, $hoog, false );

    if ( 'keuze' === $v['begeleiding'] && ! empty( $k['begeleiding'] ) ) {
        $regels[] = sfp_calculator_regel( 'begeleiding', $groepen, (int) $norm['begeleiding_prijs'] * 100, $hoog, false );
    } elseif ( 'inbegrepen' === $v['begeleiding'] ) {
        $regels[] = sfp_calculator_regel( 'begeleiding', $groepen, (int) $norm['begeleiding_prijs'] * 100, $hoog, true );
    }

    // Coachuren en learning on the job: een deel van de groep, in uren per deelnemer (blokken van 4).
    $coach = sfp_calculator_persoon_uren( isset( $ex['coachuren'] ) ? $ex['coachuren'] : null, $pp, $norm );
    if ( ! empty( $aan['coachuren'] ) && $coach ) {
        $regels[] = sfp_calculator_regel( 'coachuren', $coach['deelnemers'] * $coach['blokken'], (int) $norm['blok_prijs'] * 100, $hoog, false );
        if ( $coach['uren'] >= (int) $norm['abonnement_uren'] ) {
            $meldingen[] = 'abonnement';
        }
    }
    // Clinic en boek: een keuze, en vanzelf inbegrepen waar ze gratis zijn.
    $clinic_gratis = (int) $vorm >= (int) $norm['clinic_gratis_vanaf'];
    if ( ! empty( $aan['clinic'] ) && ( ! empty( $ex['clinic'] ) || $clinic_gratis ) ) {
        $regels[] = sfp_calculator_regel( 'clinic', $pp, (int) $norm['clinic_prijs'] * 100, $hoog, $clinic_gratis );
    }
    $boek_gratis = (int) $vorm >= (int) $norm['boek_gratis_vanaf'];
    if ( ! empty( $aan['boek'] ) && ( ! empty( $ex['boek'] ) || $boek_gratis ) ) {
        $regels[] = sfp_calculator_regel( 'boek', $pp, (int) $norm['boek_centen'], (int) $norm['btw_laag'], $boek_gratis );
    }
    $acteur = sfp_calculator_geheel( isset( $ex['acteur'] ) ? $ex['acteur'] : 0, 0, (int) $vorm * $groepen );
    if ( ! empty( $aan['acteur'] ) && $acteur > 0 ) {
        $regels[] = sfp_calculator_regel( 'acteur', $acteur, (int) $norm['acteur_prijs'] * 100, $hoog, false );
    }
    $terug = isset( $ex['terugkom'] ) ? (string) $ex['terugkom'] : '';
    if ( ! empty( $aan['terugkom'] ) && ( 'dagdeel' === $terug || 'online' === $terug ) ) {
        $regels[] = sfp_calculator_regel( 'terugkom_' . $terug, $groepen, (int) $norm['terugkom'][ $terug ] * 100, $hoog, false );
    }
    $lotj = sfp_calculator_persoon_uren( isset( $ex['lotj'] ) ? $ex['lotj'] : null, $pp, $norm );
    if ( ! empty( $aan['lotj'] ) && $lotj ) {
        $regels[] = sfp_calculator_regel( 'lotj', $lotj['deelnemers'] * $lotj['blokken'], (int) $norm['blok_prijs'] * 100, $hoog, false );
    }
    $scan = isset( $ex['scan'] ) ? (string) $ex['scan'] : '';
    if ( ! empty( $aan['scan'] ) && 'individueel' === $scan ) {
        $regels[] = sfp_calculator_regel( 'scan_individueel', $pp, (int) $norm['scan_individueel'] * 100, $hoog, false );
    } elseif ( ! empty( $aan['scan'] ) && 'team' === $scan ) {
        $regels[] = sfp_calculator_regel( 'scan_team', $groepen, (int) $norm['scan_team'] * 100, $hoog, false );
    }

    $voordeel = 0;
    $extra_g  = $groepen - ( (int) $norm['voordeel_vanaf_groep'] - 1 );
    if ( (int) $vorm >= 2 && $extra_g > 0 ) {
        $voordeel = $extra_g * (int) $norm['voordeel_bedrag'];
        if ( isset( $k['groepsvoordeel'] ) && 'coachuren' === $k['groepsvoordeel'] ) {
            $regels[] = sfp_calculator_regel( 'voordeel_coachuren', $extra_g, (int) $norm['blok_prijs'] * 100, $hoog, true );
        } else {
            $regels[] = sfp_calculator_regel( 'voordeel_korting', $extra_g, -(int) $norm['voordeel_bedrag'] * 100, $hoog, false );
        }
    }

    if ( ! $onbekend && $deelnemers >= 1 && $deelnemers < (int) $norm['klein_tot'] ) {
        $meldingen[] = 'klein';
    }
    if ( ! $onbekend && 1 === $groepen && $deelnemers > (int) $norm['groep_norm'] ) {
        $meldingen[] = 'negen_tien';
    }
    if ( $groepen >= (int) $norm['maatwerk_groepen'] ) {
        $meldingen[] = 'maatwerk';
    }

    $excl       = 0;
    $per_tarief = array();
    foreach ( $regels as $r ) {
        $excl += $r['bedrag'];
        $per_tarief[ $r['btw'] ] = ( isset( $per_tarief[ $r['btw'] ] ) ? $per_tarief[ $r['btw'] ] : 0 ) + $r['bedrag'];
    }
    ksort( $per_tarief );
    $btw        = array();
    $btw_totaal = 0;
    foreach ( $per_tarief as $tarief => $bedrag ) {
        $btw[ (string) $tarief ] = (int) round( ( $bedrag * (int) $tarief ) / 100 );
        $btw_totaal             += $btw[ (string) $tarief ];
    }

    return array(
        'groepen'     => $groepen,
        'verdeling'   => $verdeling,
        'deelnemers'  => $onbekend ? 0 : (int) $deelnemers,
        'regels'      => $regels,
        'meldingen'   => $meldingen,
        'voordeel'    => $voordeel,
        'totaal_excl' => $excl,
        'btw'         => $btw,
        'totaal_incl' => $excl + $btw_totaal,
    );
}

/**
 * De begroting (zie motor.js, reken()).
 *
 * @param  array $k    Keuzes.
 * @param  array $norm Norm van de site.
 * @return array
 */
function sfp_calculator_reken( array $k, array $norm ) {
    $route = isset( $k['route'] ) && 'B' === $k['route'] ? 'B' : 'A';
    $uit   = array(
        'route'       => $route,
        'maatwerk'    => false,
        'richtprijs'  => false,
        'bandbreedte' => null,
    );
    if ( 'B' === $route ) {
        $uit['sprekers']        = sfp_calculator_geheel( isset( $k['sprekers'] ) ? $k['sprekers'] : 0, 0, 500 );
        $uit['evenement_prijs'] = (int) $norm['evenement_prijs'];
        $uit['richtprijs']      = true;
        $uit['maatwerk']        = true;
        $uit['uitkomst']        = null;
        return $uit;
    }

    $m     = isset( $k['mensen'] ) && is_array( $k['mensen'] ) ? $k['mensen'] : array();
    $soort = isset( $m['soort'] ) && in_array( $m['soort'], array( 'marge', 'onbekend' ), true ) ? $m['soort'] : 'getal';
    $max   = (int) $norm['max_deelnemers'];
    if ( 'onbekend' === $soort ) {
        $uit['mensen']   = array( 'soort' => $soort );
        $uit['uitkomst'] = sfp_calculator_reken_voor( $k, $norm, 0, true );
    } elseif ( 'marge' === $soort ) {
        $van = sfp_calculator_geheel( isset( $m['van'] ) ? $m['van'] : 0, 1, $max );
        $tot = sfp_calculator_geheel( isset( $m['tot'] ) ? $m['tot'] : 0, 1, $max );
        if ( $van > $tot ) {
            list( $van, $tot ) = array( $tot, $van );
        }
        $midden          = (int) round( ( $van + $tot ) / 2 );
        $uit['mensen']   = array(
            'soort'  => $soort,
            'van'    => $van,
            'tot'    => $tot,
            'midden' => $midden,
        );
        $uit['uitkomst'] = sfp_calculator_reken_voor( $k, $norm, $midden, false );
        if ( $van !== $tot ) {
            $a                  = sfp_calculator_reken_voor( $k, $norm, $van, false );
            $b                  = sfp_calculator_reken_voor( $k, $norm, $tot, false );
            $uit['bandbreedte'] = array(
                'van'         => $a['totaal_excl'],
                'tot'         => $b['totaal_excl'],
                'groepen_van' => $a['groepen'],
                'groepen_tot' => $b['groepen'],
            );
        }
    } else {
        $n               = sfp_calculator_geheel( isset( $m['aantal'] ) ? $m['aantal'] : 0, 0, $max );
        $uit['mensen']   = array(
            'soort'  => $soort,
            'aantal' => $n,
        );
        $uit['uitkomst'] = sfp_calculator_reken_voor( $k, $norm, $n, false );
    }
    if ( $uit['uitkomst'] && $uit['uitkomst']['groepen'] >= (int) $norm['maatwerk_groepen'] ) {
        $uit['maatwerk']   = true;
        $uit['richtprijs'] = true;
    }
    return $uit;
}
