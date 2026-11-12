<?php
/**
 * SFP Page Config - Incompanycalculator: REST-routes (sinds 3.36.0).
 *
 * Bezoeker:
 *   POST /sfp/v1/calculator/begroting       begroting opslaan bij stap 8, geeft kenmerk en de verborgen velden
 * Beheerder (manage_options):
 *   GET  /sfp/v1/calculator/begroting/<kenmerk>  de hele begroting als JSON (voor de offertebouwer)
 *   GET  /sfp/v1/calculator/instellingen    stand voor SFP Beheer, ook de export
 *   POST /sfp/v1/calculator/instellingen    bewaren, ook de import
 *   POST /sfp/v1/calculator/proef           een proefbegroting met vaste keuzes, met pdf
 *
 * @package SFP_Page_Config
 * @since   3.36.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Keuzes
 * ====================================================================== */

/**
 * Codes die de calculator kent.
 *
 * @return array
 */
function sfp_calculator_codes() {
    return array(
        'doelgroep' => array( 'starters', 'ervaren', 'senioren', 'leidinggevend', 'gemengd', 'anders' ),
        'waarom'    => array( 'nieuwe_rol', 'groei', 'moment', 'feedback', 'beleid', 'anders' ),
        'locatie'   => array( 'extern', 'lunch', 'borrel', 'teamdag' ),
        'soort'     => array( 'keynote', 'meerdere', 'pitch', 'anders' ),
        'fase'      => array( 'verkennen', 'intern', 'plannen', 'procedure' ),
        'beslist'   => array( 'zelf', 'leidinggevende', 'hr', 'inkoop', 'directie', 'anders' ),
        'budget'    => array( 'tot5', '5_10', '10_25', 'meer', 'onbekend', 'geen' ),
        'beviel'    => array( 'goed', 'wisselend', 'slecht' ),
    );
}

/**
 * Korte tekst, schoon en ingekort.
 *
 * @param  mixed $w   Waarde.
 * @param  int   $max Tekens.
 * @param  bool  $lang Meerdere regels toestaan.
 * @return string
 */
function sfp_calculator_tekst_schoon( $w, $max, $lang = false ) {
    $w = is_scalar( $w ) ? (string) $w : '';
    $w = $lang ? sanitize_textarea_field( $w ) : sanitize_text_field( $w );
    return mb_substr( $w, 0, $max );
}

/**
 * Maak de keuzes uit de browser schoon, met vaste types.
 *
 * @param  array $in Invoer.
 * @return array keuzes, situatie.
 */
function sfp_calculator_keuzes_schoon( array $in ) {
    $codes = sfp_calculator_codes();
    $k     = isset( $in['keuzes'] ) && is_array( $in['keuzes'] ) ? $in['keuzes'] : array();
    $route = isset( $k['route'] ) && 'B' === $k['route'] ? 'B' : 'A';
    $m     = isset( $k['mensen'] ) && is_array( $k['mensen'] ) ? $k['mensen'] : array();
    $soort = isset( $m['soort'] ) && in_array( $m['soort'], array( 'getal', 'marge', 'onbekend' ), true ) ? $m['soort'] : 'getal';
    $ex    = isset( $k['extras'] ) && is_array( $k['extras'] ) ? $k['extras'] : array();
    $waar  = function ( $w ) {
        return true === $w || 1 === $w || '1' === $w || 'true' === $w;
    };

    $keuzes = array(
        'route'            => $route,
        'mensen'           => array(
            'soort'  => $soort,
            'aantal' => sfp_calculator_geheel( isset( $m['aantal'] ) ? $m['aantal'] : 0, 0, 500 ),
            'van'    => sfp_calculator_geheel( isset( $m['van'] ) ? $m['van'] : 0, 0, 500 ),
            'tot'    => sfp_calculator_geheel( isset( $m['tot'] ) ? $m['tot'] : 0, 0, 500 ),
        ),
        'doelgroep'        => isset( $k['doelgroep'] ) && in_array( $k['doelgroep'], $codes['doelgroep'], true ) ? $k['doelgroep'] : '',
        'doelgroep_anders' => sfp_calculator_tekst_schoon( isset( $k['doelgroep_anders'] ) ? $k['doelgroep_anders'] : '', 120 ),
        'vorm'             => isset( $k['vorm'] ) && in_array( (string) $k['vorm'], array( '1', '2', '3' ), true ) ? (int) $k['vorm'] : 0,
        'begeleiding'      => $waar( isset( $k['begeleiding'] ) ? $k['begeleiding'] : false ),
        'extras'           => array(
            'coachuren' => sfp_calculator_uren_schoon( isset( $ex['coachuren'] ) ? $ex['coachuren'] : null ),
            'clinic'    => $waar( isset( $ex['clinic'] ) ? $ex['clinic'] : false ),
            'boek'      => $waar( isset( $ex['boek'] ) ? $ex['boek'] : false ),
            'acteur'    => sfp_calculator_geheel( isset( $ex['acteur'] ) ? $ex['acteur'] : 0, 0, 200 ),
            'terugkom'  => isset( $ex['terugkom'] ) && in_array( $ex['terugkom'], array( 'dagdeel', 'online' ), true ) ? $ex['terugkom'] : '',
            'lotj'      => sfp_calculator_uren_schoon( isset( $ex['lotj'] ) ? $ex['lotj'] : null ),
            'scan'      => isset( $ex['scan'] ) && in_array( $ex['scan'], array( 'individueel', 'team' ), true ) ? $ex['scan'] : '',
        ),
        'groepsvoordeel'   => isset( $k['groepsvoordeel'] ) && 'coachuren' === $k['groepsvoordeel'] ? 'coachuren' : 'korting',
        'locatie'          => array_values( array_intersect( $codes['locatie'], isset( $k['locatie'] ) && is_array( $k['locatie'] ) ? array_map( 'strval', $k['locatie'] ) : array() ) ),
        'sprekers'         => sfp_calculator_geheel( isset( $k['sprekers'] ) ? $k['sprekers'] : 0, 0, 500 ),
        'soort'            => isset( $k['soort'] ) && in_array( $k['soort'], $codes['soort'], true ) ? $k['soort'] : '',
        'fase'             => isset( $k['fase'] ) && in_array( $k['fase'], $codes['fase'], true ) ? $k['fase'] : '',
        'uitvraag'         => isset( $k['fase'] ) && 'procedure' === $k['fase'],
    );

    $s        = isset( $in['situatie'] ) && is_array( $in['situatie'] ) ? $in['situatie'] : array();
    $situatie = array(
        'doel'     => sfp_calculator_tekst_schoon( isset( $s['doel'] ) ? $s['doel'] : '', 1500, true ),
        'waarom'   => array_values( array_intersect( $codes['waarom'], isset( $s['waarom'] ) && is_array( $s['waarom'] ) ? array_map( 'strval', $s['waarom'] ) : array() ) ),
        'criteria' => sfp_calculator_tekst_schoon( isset( $s['criteria'] ) ? $s['criteria'] : '', 2000, true ),
    );
    $i                = isset( $in['intern'] ) && is_array( $in['intern'] ) ? $in['intern'] : array();
    $eerder           = isset( $i['eerder'] ) && in_array( $i['eerder'], array( 'ja', 'nee' ), true ) ? $i['eerder'] : '';
    $keuzes['intern'] = array(
        'beslist' => array_values( array_intersect( $codes['beslist'], isset( $i['beslist'] ) && is_array( $i['beslist'] ) ? array_map( 'strval', $i['beslist'] ) : array() ) ),
        'budget'  => isset( $i['budget'] ) && in_array( $i['budget'], $codes['budget'], true ) ? $i['budget'] : '',
        'eerder'  => $eerder,
        'beviel'  => 'ja' === $eerder && isset( $i['beviel'] ) && in_array( $i['beviel'], $codes['beviel'], true ) ? $i['beviel'] : '',
        'vorig'   => 'ja' === $eerder ? sfp_calculator_tekst_schoon( isset( $i['vorig'] ) ? $i['vorig'] : '', 1000, true ) : '',
    );
    $p                = isset( $in['inkoop'] ) && is_array( $in['inkoop'] ) ? $in['inkoop'] : array();
    $keuzes['inkoop'] = array(
        'aan'          => $waar( isset( $p['aan'] ) ? $p['aan'] : false ),
        'po'           => sfp_calculator_tekst_schoon( isset( $p['po'] ) ? $p['po'] : '', 100 ),
        'kostenplaats' => sfp_calculator_tekst_schoon( isset( $p['kostenplaats'] ) ? $p['kostenplaats'] : '', 100 ),
        'referentie'   => sfp_calculator_tekst_schoon( isset( $p['referentie'] ) ? $p['referentie'] : '', 150 ),
        'factuuradres' => sfp_calculator_tekst_schoon( isset( $p['factuuradres'] ) ? $p['factuuradres'] : '', 500, true ),
    );
    // Inkoop aangevinkt maar niets ingevuld: geen inkoopblok (de browser houdt dat al tegen).
    if ( $keuzes['inkoop']['aan'] && '' === trim( implode( '', array_slice( $keuzes['inkoop'], 1 ) ) ) ) {
        $keuzes['inkoop']['aan'] = false;
    }
    if ( ! $keuzes['inkoop']['aan'] ) {
        $keuzes['inkoop'] = array( 'aan' => false );
    }
    return array(
        'keuzes'   => $keuzes,
        'situatie' => $situatie,
    );
}

/**
 * Uren per deelnemer voor een deel van de groep, schoon.
 *
 * @param  mixed $w array( deelnemers, uren ).
 * @return array|null
 */
function sfp_calculator_uren_schoon( $w ) {
    if ( ! is_array( $w ) ) {
        return null;
    }
    $n    = sfp_calculator_geheel( isset( $w['deelnemers'] ) ? $w['deelnemers'] : 0, 0, 500 );
    $uren = sfp_calculator_geheel( isset( $w['uren'] ) ? $w['uren'] : 0, 0, 200 );
    return $n && $uren ? array(
        'deelnemers' => $n,
        'uren'       => $uren,
    ) : null;
}

/* =========================================================================
 * Een begroting maken
 * ====================================================================== */

/**
 * Maak een rij met een nieuw kenmerk. De server rekent zelf.
 *
 * @param  array $keuzes   Schone keuzes.
 * @param  array $situatie Schone situatie.
 * @param  array $bron     pagina_url, utm (source, medium, campaign), gclid.
 * @param  bool  $proef    Proefbegroting.
 * @return array|WP_Error  De rij.
 */
function sfp_calculator_maak_rij( array $keuzes, array $situatie, array $bron, $proef ) {
    global $wpdb;
    if ( ! sfp_calculator_tabel_klaar() ) {
        return new WP_Error( 'sfp_calculator_tabel', 'De tabel van de calculator is er niet.' );
    }
    $norm = sfp_calculator_site_norm();
    if ( empty( $norm['routes_aan'][ $keuzes['route'] ] ) ) {
        return new WP_Error( 'sfp_calculator_route', 'Deze route staat uit.' );
    }
    if ( 'A' === $keuzes['route'] && ! $keuzes['vorm'] ) {
        return new WP_Error( 'sfp_calculator_vorm', 'Kies een vorm.' );
    }
    $berekening = sfp_calculator_reken( $keuzes, $norm );
    $totaal     = isset( $berekening['uitkomst']['totaal_excl'] ) ? (int) $berekening['uitkomst']['totaal_excl'] : 0;
    $utm        = isset( $bron['utm'] ) && is_array( $bron['utm'] ) ? $bron['utm'] : array();
    $rij        = array(
        'kenmerk'         => sfp_calculator_nieuw_kenmerk(),
        'sleutel'         => bin2hex( random_bytes( 16 ) ),
        'taal'            => sfp_page_config_taal(),
        'route'           => $keuzes['route'],
        'keuzes'          => wp_json_encode( $keuzes ),
        'berekening'      => wp_json_encode( array( 'norm' => $norm, 'uitkomst' => $berekening ) ),
        'situatie'        => wp_json_encode( $situatie ),
        'lead_source_url' => esc_url_raw( mb_substr( isset( $bron['pagina_url'] ) ? (string) $bron['pagina_url'] : '', 0, 500 ) ),
        'utm'             => wp_json_encode(
            array(
                'source'   => sfp_calculator_tekst_schoon( isset( $utm['source'] ) ? $utm['source'] : '', 200 ),
                'medium'   => sfp_calculator_tekst_schoon( isset( $utm['medium'] ) ? $utm['medium'] : '', 200 ),
                'campaign' => sfp_calculator_tekst_schoon( isset( $utm['campaign'] ) ? $utm['campaign'] : '', 200 ),
                'gclid'    => sfp_calculator_tekst_schoon( isset( $bron['gclid'] ) ? $bron['gclid'] : '', 200 ),
            )
        ),
        'totaal_excl'     => $totaal,
        'proef'           => $proef ? 1 : 0,
        'gestart_op'      => gmdate( 'Y-m-d H:i:s' ),
    );
    if ( false === $wpdb->insert( sfp_calculator_tabel(), $rij ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return new WP_Error( 'sfp_calculator_opslaan', 'De begroting kon niet worden opgeslagen.' );
    }
    $rij['id'] = (int) $wpdb->insert_id;
    return $rij;
}

/**
 * De link naar de pdf van een rij.
 *
 * @param  array $rij Rij.
 * @return string
 */
function sfp_calculator_pdf_url( array $rij ) {
    // Eén parameter, zonder &: SureForms bewaart & als &amp; en dan breekt de link in het CRM.
    return home_url( '/' . sfp_calculator_pdf_pad_adres() . '/' ) . '?b=' . rawurlencode( (string) $rij['kenmerk'] . '.' . (string) $rij['sleutel'] );
}

/**
 * Het pad van de pdf-link: <calculatorpagina>/begroting.
 *
 * @return string Zonder slashes aan begin en eind.
 */
function sfp_calculator_pdf_pad_adres() {
    $id   = (int) sfp_calculator_instellingen()['calculatorpagina'];
    $pad  = $id > 0 ? trim( (string) wp_parse_url( (string) get_permalink( $id ), PHP_URL_PATH ), '/' ) : '';
    $pad  = '' !== $pad ? $pad : 'incompany/calculator';
    return strtolower( $pad ) . '/begroting';
}

/**
 * Bedrag als tekst in de valuta van de norm.
 *
 * @param  int    $bedrag Euro's (of de valuta).
 * @param  string $valuta Code.
 * @return string
 */
function sfp_calculator_bedrag( $bedrag, $valuta = 'EUR' ) {
    $taal   = sfp_page_config_taal();
    $teken  = array(
        'EUR' => '€',
        'GBP' => '£',
        'USD' => '$',
    );
    $t      = isset( $teken[ $valuta ] ) ? $teken[ $valuta ] : $valuta;
    $getal  = 'nl' === $taal || 'pt' === $taal ? number_format( abs( (int) $bedrag ), 0, ',', '.' ) : number_format( abs( (int) $bedrag ), 0, '.', ',' );
    return ( (int) $bedrag < 0 ? '- ' : '' ) . $t . ' ' . $getal;
}

/**
 * Een vaste tekst met plaatsvervangers.
 *
 * @param  string $sleutel Sleutel.
 * @param  array  $vervang {n} => ...
 * @param  string $taal    Taal.
 * @return string
 */
function sfp_calculator_t( $sleutel, array $vervang = array(), $taal = '' ) {
    static $cache = array();
    $taal = '' !== $taal ? $taal : sfp_page_config_taal();
    if ( ! isset( $cache[ $taal ] ) ) {
        $cache[ $taal ] = sfp_calculator_teksten( $taal );
    }
    $tekst = isset( $cache[ $taal ][ $sleutel ] ) ? $cache[ $taal ][ $sleutel ] : $sleutel;
    $paren = array();
    foreach ( $vervang as $k => $v ) {
        $paren[ '{' . $k . '}' ] = (string) $v;
    }
    return strtr( $tekst, $paren );
}

/**
 * De leesbare samenvatting (notitie in SureContact en de melding).
 *
 * @param  array $rij Rij.
 * @return string
 */
function sfp_calculator_notitie( array $rij ) {
    $taal   = (string) $rij['taal'];
    $keuzes = sfp_calculator_json( $rij, 'keuzes' );
    $sit    = sfp_calculator_json( $rij, 'situatie' );
    $ber    = sfp_calculator_json( $rij, 'berekening' );
    $u      = isset( $ber['uitkomst'] ) ? $ber['uitkomst'] : array();
    $valuta = isset( $ber['norm']['valuta'] ) ? $ber['norm']['valuta'] : 'EUR';
    $t      = function ( $s, $v = array() ) use ( $taal ) {
        return sfp_calculator_t( $s, $v, $taal );
    };
    $regels   = array();
    $regels[] = $t( 'begroting' ) . ' ' . $rij['kenmerk'] . ( ! empty( $rij['proef'] ) ? ' (' . $t( 'proef' ) . ')' : '' );
    $regels[] = $t( 'B' === $rij['route'] ? 'doel_evenement' : 'doel_training' );
    if ( 'A' === $rij['route'] && ! empty( $u['uitkomst'] ) ) {
        $o        = $u['uitkomst'];
        $m        = isset( $u['mensen'] ) ? $u['mensen'] : array();
        $mensen   = 'marge' === ( $m['soort'] ?? '' ) ? $m['van'] . '-' . $m['tot'] : ( 'onbekend' === ( $m['soort'] ?? '' ) ? $t( 'mensen_onbekend' ) : (string) ( $m['aantal'] ?? '' ) );
        $regels[] = $t( 'mensen_aantal' ) . ': ' . $mensen . ', ' . ( 1 === (int) $o['groepen'] ? $t( 'groep' ) : $t( 'groepen', array( 'n' => $o['groepen'] ) ) ) . ' (' . implode( ', ', (array) $o['verdeling'] ) . ')';
        $regels[] = $t( 'vorm_' . $keuzes['vorm'] );
        $extra    = array();
        foreach ( (array) $o['regels'] as $r ) {
            if ( 0 === strpos( $r['code'], 'vorm_' ) ) {
                continue;
            }
            $extra[] = $t( 'regel_' . $r['code'] ) . ' x' . $r['aantal'] . ( $r['gratis'] ? ' (' . $t( 'inbegrepen' ) . ')' : '' );
        }
        if ( $extra ) {
            $regels[] = implode( '; ', $extra );
        }
        $regels[] = ( ! empty( $u['richtprijs'] ) ? $t( 'richtprijs' ) : $t( 'totaal' ) ) . ': ' . sfp_calculator_bedrag( (int) $o['totaal_excl'], $valuta ) . ' ' . $t( 'excl' );
        if ( ! empty( $u['bandbreedte'] ) ) {
            $regels[] = $t( 'bandbreedte', array( 'van' => $m['van'], 'tot' => $m['tot'], 'prijs' => sfp_calculator_bedrag( $u['bandbreedte']['van'], $valuta ) . ' - ' . sfp_calculator_bedrag( $u['bandbreedte']['tot'], $valuta ) ) );
        }
    } elseif ( 'B' === $rij['route'] ) {
        $regels[] = $t( 'sprekers_vraag' ) . ' ' . (int) $keuzes['sprekers'] . ( $keuzes['soort'] ? ', ' . $t( 'soort_' . $keuzes['soort'] ) : '' );
        $regels[] = $t( 'richtprijs' ) . ': ' . $t( 'evenement_prijs', array( 'prijs' => sfp_calculator_bedrag( (int) ( $u['evenement_prijs'] ?? 0 ), $valuta ) ) );
    }
    if ( ! empty( $keuzes['fase'] ) ) {
        $regels[] = $t( 'pdf_fase' ) . ': ' . $t( 'fase_' . $keuzes['fase'] );
    }
    if ( ! empty( $keuzes['uitvraag'] ) ) {
        $regels[] = $t( 'pdf_uitvraag' ) . ( '' !== ( $sit['criteria'] ?? '' ) ? ': ' . $sit['criteria'] : '' );
    }
    if ( ! empty( $keuzes['doelgroep'] ) ) {
        $regels[] = $t( 'doelgroep_vraag' ) . ' ' . $t( 'doelgroep_' . $keuzes['doelgroep'] ) . ( 'anders' === $keuzes['doelgroep'] && '' !== $keuzes['doelgroep_anders'] ? ' ' . $keuzes['doelgroep_anders'] : '' );
    }
    if ( ! empty( $keuzes['locatie'] ) ) {
        $regels[] = $t( 'pdf_locatie' ) . ': ' . implode( ', ', array_map( function ( $l ) use ( $t ) { return $t( 'locatie_' . $l ); }, $keuzes['locatie'] ) ) . ' (' . $t( 'op_aanvraag' ) . ')';
    }
    if ( '' !== ( $sit['doel'] ?? '' ) ) {
        $regels[] = $t( 'pdf_doel' ) . ': ' . $sit['doel'];
    }
    foreach ( sfp_calculator_intern_regels( $keuzes, $taal, $valuta ) as $label => $waarde ) {
        $regels[] = $label . ': ' . $waarde;
    }
    if ( ! empty( $keuzes['inkoop']['aan'] ) ) {
        $inkoop = array();
        foreach ( array( 'po', 'kostenplaats', 'referentie', 'factuuradres' ) as $veld ) {
            if ( '' !== ( $keuzes['inkoop'][ $veld ] ?? '' ) ) {
                $inkoop[] = $t( 'inkoop_' . $veld ) . ': ' . str_replace( "\n", ', ', $keuzes['inkoop'][ $veld ] );
            }
        }
        $regels[] = $t( 'pdf_inkoop' ) . ( $inkoop ? ': ' . implode( '; ', $inkoop ) : '' );
    }
    $regels[] = sfp_calculator_pdf_url( $rij );
    return implode( "\n", $regels );
}

/**
 * Het interne proces in leesbare regels: wie beslist mee, budget, eerder traject.
 *
 * @param  array  $keuzes Keuzes.
 * @param  string $taal   Taal.
 * @param  string $valuta Valuta.
 * @return array<string, string> label => waarde.
 */
function sfp_calculator_intern_regels( array $keuzes, $taal, $valuta = 'EUR' ) {
    $t   = function ( $s, $v = array() ) use ( $taal ) {
        return sfp_calculator_t( $s, $v, $taal );
    };
    $i   = isset( $keuzes['intern'] ) && is_array( $keuzes['intern'] ) ? $keuzes['intern'] : array();
    $uit = array();
    if ( ! empty( $i['beslist'] ) && is_array( $i['beslist'] ) ) {
        $uit[ $t( 'pdf_beslist' ) ] = implode( ', ', array_map( function ( $w ) use ( $t ) { return $t( 'beslist_' . $w ); }, $i['beslist'] ) );
    }
    if ( ! empty( $i['budget'] ) && is_string( $i['budget'] ) ) {
        $b   = array(
            'tot5'  => array( 'prijs' => sfp_calculator_bedrag( 5000, $valuta ) ),
            '5_10'  => array( 'van' => sfp_calculator_bedrag( 5000, $valuta ), 'tot' => sfp_calculator_bedrag( 10000, $valuta ) ),
            '10_25' => array( 'van' => sfp_calculator_bedrag( 10000, $valuta ), 'tot' => sfp_calculator_bedrag( 25000, $valuta ) ),
            'meer'  => array( 'prijs' => sfp_calculator_bedrag( 25000, $valuta ) ),
        );
        $uit[ $t( 'pdf_budget' ) ] = $t( 'budget_' . $i['budget'], isset( $b[ $i['budget'] ] ) ? $b[ $i['budget'] ] : array() );
    }
    if ( 'ja' === ( $i['eerder'] ?? '' ) ) {
        $uit[ $t( 'pdf_vorig' ) ] = trim( ( ! empty( $i['beviel'] ) ? $t( 'beviel_' . $i['beviel'] ) : $t( 'ja' ) ) . ( '' !== ( $i['vorig'] ?? '' ) ? '. ' . $i['vorig'] : '' ) );
    } elseif ( 'nee' === ( $i['eerder'] ?? '' ) ) {
        $uit[ $t( 'pdf_vorig' ) ] = $t( 'nee' );
    }
    return $uit;
}

/**
 * De waarden voor de verborgen velden van het formulier, uit de eigen rij.
 * Sleutels zijn de labels van de verborgen velden in SureForms.
 *
 * @param  array $rij Rij.
 * @return array<string, string>
 */
function sfp_calculator_velden( array $rij ) {
    $keuzes = sfp_calculator_json( $rij, 'keuzes' );
    $sit    = sfp_calculator_json( $rij, 'situatie' );
    $ber    = sfp_calculator_json( $rij, 'berekening' );
    $o      = isset( $ber['uitkomst']['uitkomst'] ) && is_array( $ber['uitkomst']['uitkomst'] ) ? $ber['uitkomst']['uitkomst'] : array();
    $utm    = sfp_calculator_json( $rij, 'utm' );
    return array(
        'kenmerk'      => (string) $rij['kenmerk'],
        'sleutel'      => (string) $rij['sleutel'],
        'route'        => (string) $rij['route'],
        'vorm'         => 'A' === $rij['route'] && ! empty( $keuzes['vorm'] ) ? (string) $keuzes['vorm'] : '',
        'groepen'      => isset( $o['groepen'] ) ? (string) (int) $o['groepen'] : '',
        'deelnemers'   => isset( $o['deelnemers'] ) && $o['deelnemers'] ? (string) (int) $o['deelnemers'] : '',
        'doelgroep'    => isset( $keuzes['doelgroep'] ) ? (string) $keuzes['doelgroep'] : '',
        'totaal_excl'  => 'A' === $rij['route'] ? (string) (int) $rij['totaal_excl'] : '',
        'pdf_link'     => sfp_calculator_pdf_url( $rij ),
        'trigger_type' => implode( ',', isset( $sit['waarom'] ) ? (array) $sit['waarom'] : array() ),
        'trigger_note' => mb_substr( (string) ( $sit['doel'] ?? '' ), 0, 500 ),
        'pagina_url'   => (string) $rij['lead_source_url'],
        'utm_source'   => (string) ( $utm['source'] ?? '' ),
        'utm_medium'   => (string) ( $utm['medium'] ?? '' ),
        'utm_campaign' => (string) ( $utm['campaign'] ?? '' ),
        'gclid'        => (string) ( $utm['gclid'] ?? '' ),
        'service'      => 'incompany',
        // Een verborgen veld bewaart geen regeleinden: regels gescheiden door ' | '.
        'notitie'      => str_replace( "\n", ' | ', sfp_calculator_notitie( $rij ) ),
    );
}

/**
 * De hele begroting als array (voor het beveiligde adres en de proef).
 *
 * @param  array $rij Rij.
 * @return array
 */
function sfp_calculator_export( array $rij ) {
    $ber = sfp_calculator_json( $rij, 'berekening' );
    return array(
        'kenmerk'     => (string) $rij['kenmerk'],
        'site'        => sfp_calculator_site_afk(),
        'taal'        => (string) $rij['taal'],
        'route'       => (string) $rij['route'],
        'proef'       => (bool) $rij['proef'],
        'gestart_op'  => (string) $rij['gestart_op'],
        'afgerond_op' => (string) $rij['afgerond_op'],
        'geldig_tot'  => sfp_calculator_geldig_tot( $rij ),
        'contact'     => array(
            'voornaam'    => (string) $rij['voornaam'],
            'achternaam'  => (string) $rij['achternaam'],
            'email'       => (string) $rij['email'],
            'organisatie' => (string) $rij['organisatie'],
            'afdeling'    => (string) ( $rij['afdeling'] ?? '' ),
            'functie'     => (string) $rij['functie'],
            'nieuwsbrief' => (bool) $rij['nieuwsbrief'],
        ),
        'keuzes'      => sfp_calculator_json( $rij, 'keuzes' ),
        'situatie'    => sfp_calculator_json( $rij, 'situatie' ),
        'norm'        => isset( $ber['norm'] ) ? $ber['norm'] : array(),
        'berekening'  => isset( $ber['uitkomst'] ) ? $ber['uitkomst'] : array(),
        'notitie'     => sfp_calculator_notitie( $rij ),
        'pdf_url'     => sfp_calculator_pdf_url( $rij ),
        'pdf_status'  => (string) $rij['pdf_status'],
        'bron'        => array(
            'pagina_url' => (string) $rij['lead_source_url'],
            'utm'        => sfp_calculator_json( $rij, 'utm' ),
        ),
    );
}

/**
 * Geldig tot (Y-m-d, tijdzone van de site), of ''.
 *
 * @param  array $rij Rij.
 * @return string
 */
function sfp_calculator_geldig_tot( array $rij ) {
    $basis = ! empty( $rij['afgerond_op'] ) ? $rij['afgerond_op'] : $rij['gestart_op'];
    if ( empty( $basis ) ) {
        return '';
    }
    return wp_date( 'Y-m-d', strtotime( $basis . ' UTC' ) + (int) sfp_calculator_norm()['geldig_dagen'] * DAY_IN_SECONDS );
}

/* =========================================================================
 * Routes
 * ====================================================================== */

add_action( 'rest_api_init', 'sfp_calculator_rest' );

/**
 * Registreer de routes.
 *
 * @return void
 */
function sfp_calculator_rest() {
    $beheerder = function () {
        return current_user_can( 'manage_options' );
    };

    register_rest_route(
        'sfp/v1',
        '/calculator/begroting',
        array(
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => 'sfp_calculator_rest_begroting',
        )
    );
    register_rest_route(
        'sfp/v1',
        '/calculator/begroting/(?P<kenmerk>[A-Za-z0-9-]{4,24})',
        array(
            'methods'             => 'GET',
            'permission_callback' => $beheerder,
            'callback'            => function ( WP_REST_Request $verzoek ) {
                $rij = sfp_calculator_rij_op( 'kenmerk', strtoupper( (string) $verzoek['kenmerk'] ) );
                if ( ! $rij ) {
                    return new WP_Error( 'sfp_calculator_onbekend', 'Onbekend kenmerk.', array( 'status' => 404 ) );
                }
                return rest_ensure_response( sfp_calculator_export( $rij ) );
            },
        )
    );
    register_rest_route(
        'sfp/v1',
        '/calculator/instellingen',
        array(
            array(
                'methods'             => 'GET',
                'permission_callback' => $beheerder,
                'callback'            => function () {
                    return rest_ensure_response( sfp_calculator_stand() );
                },
            ),
            array(
                'methods'             => 'POST',
                'permission_callback' => $beheerder,
                'callback'            => function ( WP_REST_Request $verzoek ) {
                    $in = $verzoek->get_json_params();
                    $in = is_array( $in ) ? $in : array();
                    // Een export bevat ook de stand: alleen de instellingen tellen.
                    if ( isset( $in['instellingen'] ) && is_array( $in['instellingen'] ) ) {
                        $in = $in['instellingen'];
                    }
                    sfp_calculator_instellingen_bewaar( $in );
                    return rest_ensure_response( sfp_calculator_stand() );
                },
            ),
        )
    );
    register_rest_route(
        'sfp/v1',
        '/calculator/proef',
        array(
            'methods'             => 'POST',
            'permission_callback' => $beheerder,
            'callback'            => 'sfp_calculator_rest_proef',
        )
    );
}

/**
 * Een fout voor de bezoeker.
 *
 * @param  string $code   Code.
 * @param  int    $status HTTP-status.
 * @return WP_Error
 */
function sfp_calculator_rest_fout( $code, $status = 400 ) {
    return new WP_Error( $code, sfp_calculator_t( 'fout' ), array( 'status' => (int) $status ) );
}

/**
 * POST /calculator/begroting: de begroting bij stap 8.
 *
 * @param  WP_REST_Request $verzoek Verzoek.
 * @return WP_REST_Response|WP_Error
 */
function sfp_calculator_rest_begroting( WP_REST_Request $verzoek ) {
    if ( ! sfp_calculator_zichtbaar() ) {
        return sfp_calculator_rest_fout( 'sfp_calculator_uit', 404 );
    }
    $in = $verzoek->get_json_params();
    $in = is_array( $in ) ? $in : array();
    // Lokveld: een mens vult het nooit in.
    if ( ! empty( $in['website'] ) ) {
        return sfp_calculator_rest_fout( 'sfp_calculator_lok' );
    }
    $ip = function_exists( 'sfp_zelftest_ip' ) ? sfp_zelftest_ip() : '';
    if ( function_exists( 'sfp_zelftest_limiet' ) && ( ! sfp_zelftest_limiet( 'calc-ip', $ip, 30 ) || ! sfp_zelftest_limiet( 'calc-site', 'site', 500 ) ) ) {
        return sfp_calculator_rest_fout( 'sfp_calculator_limiet', 429 );
    }
    $schoon = sfp_calculator_keuzes_schoon( $in );
    $bron   = array(
        'pagina_url' => isset( $in['pagina_url'] ) ? (string) $in['pagina_url'] : '',
        'utm'        => isset( $in['utm'] ) && is_array( $in['utm'] ) ? $in['utm'] : array(),
        'gclid'      => isset( $in['gclid'] ) ? (string) $in['gclid'] : '',
    );
    $rij    = sfp_calculator_maak_rij( $schoon['keuzes'], $schoon['situatie'], $bron, sfp_calculator_proef() );
    if ( is_wp_error( $rij ) ) {
        sfp_calculator_log( 'fout', $rij->get_error_message() );
        return sfp_calculator_rest_fout( $rij->get_error_code() );
    }
    $ber = sfp_calculator_json( $rij, 'berekening' );
    return rest_ensure_response(
        array(
            'kenmerk'    => $rij['kenmerk'],
            'pdf_url'    => sfp_calculator_pdf_url( $rij ),
            'geldig_tot' => sfp_calculator_geldig_tot( $rij ),
            'velden'     => sfp_calculator_velden( $rij ),
            'berekening' => $ber['uitkomst'],
        )
    );
}

/**
 * POST /calculator/proef: een begroting met vaste keuzes, met pdf.
 *
 * @param  WP_REST_Request $verzoek Verzoek.
 * @return WP_REST_Response|WP_Error
 */
function sfp_calculator_rest_proef( WP_REST_Request $verzoek ) {
    $in = $verzoek->get_json_params();
    $in = is_array( $in ) && ! empty( $in['keuzes'] ) ? $in : array(
        'keuzes'   => array(
            'route'          => 'A',
            'mensen'         => array(
                'soort'  => 'getal',
                'aantal' => 20,
            ),
            'doelgroep'      => 'gemengd',
            'vorm'           => 2,
            'begeleiding'    => true,
            'extras'         => array(
                'clinic'    => true,
                'boek'      => true,
                'coachuren' => array( 'deelnemers' => 4, 'uren' => 4 ),
            ),
            'fase'           => 'procedure',
            'groepsvoordeel' => 'korting',
            'locatie'        => array( 'lunch' ),
        ),
        'situatie' => array(
            'doel'   => 'Proefbegroting uit SFP Beheer.',
            'waarom'   => array( 'groei' ),
            'criteria' => 'Proef: prijs en ervaring.',
        ),
        'intern'   => array(
            'beslist' => array( 'hr', 'inkoop' ),
            'budget'  => '5_10',
            'eerder'  => 'ja',
            'beviel'  => 'wisselend',
            'vorig'   => 'Proef.',
        ),
        'inkoop'   => array(
            'aan' => true,
            'po'  => 'PROEF-001',
        ),
    );
    $schoon = sfp_calculator_keuzes_schoon( $in );
    $rij    = sfp_calculator_maak_rij( $schoon['keuzes'], $schoon['situatie'], array( 'pagina_url' => admin_url() ), true );
    if ( is_wp_error( $rij ) ) {
        return new WP_Error( $rij->get_error_code(), $rij->get_error_message(), array( 'status' => 400 ) );
    }
    $user = wp_get_current_user();
    sfp_calculator_rij_bij(
        (int) $rij['id'],
        array(
            'voornaam'    => (string) $user->first_name,
            'achternaam'  => (string) $user->last_name,
            'email'       => strtolower( (string) $user->user_email ),
            'organisatie' => 'Proef',
            'afdeling'    => 'Proefafdeling',
            'afgerond_op' => gmdate( 'Y-m-d H:i:s' ),
            'pdf_status'  => 'wacht',
        )
    );
    $uitkomst = sfp_calculator_pdf_verwerk( (int) $rij['id'], true );
    $rij      = sfp_calculator_rij_op( 'id', (int) $rij['id'] );
    sfp_calculator_log( 'proef', 'Proefbegroting ' . $rij['kenmerk'] . ': pdf ' . $rij['pdf_status'] . ( is_wp_error( $uitkomst ) ? ' (' . $uitkomst->get_error_message() . ')' : '' ) );
    return rest_ensure_response(
        array(
            'kenmerk'    => $rij['kenmerk'],
            'pdf_url'    => sfp_calculator_pdf_url( $rij ),
            'pdf_status' => $rij['pdf_status'],
            'fout'       => is_wp_error( $uitkomst ) ? $uitkomst->get_error_message() : '',
            'velden'     => sfp_calculator_velden( $rij ),
            'stand'      => sfp_calculator_stand(),
        )
    );
}

/**
 * De stand voor SFP Beheer, Calculator.
 *
 * @return array
 */
function sfp_calculator_stand() {
    $inst = sfp_calculator_instellingen();
    $foto = (int) $inst['contact']['foto'];
    $logo = (int) $inst['logo'];
    return array(
        'aan'           => function_exists( 'sfp_page_config_onderdeel_aan' ) && sfp_page_config_onderdeel_aan( 'calculator' ),
        'instellingen'  => $inst,
        'norm'          => sfp_calculator_norm(),
        'site_norm'     => sfp_calculator_site_norm( $inst ),
        'prijs_paden'   => sfp_calculator_prijs_paden(),
        'afwijkingen'   => sfp_calculator_afwijkingen(),
        'foto_url'      => $foto ? (string) wp_get_attachment_image_url( $foto, 'thumbnail' ) : '',
        'logo_url'      => $logo ? (string) wp_get_attachment_image_url( $logo, 'medium' ) : '',
        'pdf_pad'       => sfp_calculator_pdf_pad_adres(),
        'formulieren'   => function_exists( 'sfp_page_config_beheer_formulieren' ) ? sfp_page_config_beheer_formulieren() : array(),
        'formulier_ok'  => sfp_calculator_formulier_controle(),
        'bedrijf_mist'  => function_exists( 'sfp_page_config_bedrijf_ontbreekt' ) ? sfp_page_config_bedrijf_ontbreekt() : array(),
        'cloudflare_ok' => function_exists( 'sfp_zelftest_cloudflare' ) && '' !== sfp_zelftest_cloudflare()['account'] && '' !== sfp_zelftest_cloudflare()['token'],
        'tellingen'     => sfp_calculator_tellingen(),
        'log'           => array_slice( (array) get_option( 'sfp_calculator_log', array() ), 0, 20 ),
        'velden'        => array_keys( sfp_calculator_velden_leeg() ),
        'feiten_norm'   => sfp_calculator_t( 'pdf_feiten_norm' ),
    );
}

/**
 * De verborgen velden die het formulier nodig heeft, leeg.
 *
 * @return array
 */
function sfp_calculator_velden_leeg() {
    return array_fill_keys( array( 'kenmerk', 'sleutel', 'route', 'vorm', 'groepen', 'deelnemers', 'doelgroep', 'totaal_excl', 'pdf_link', 'trigger_type', 'trigger_note', 'pagina_url', 'utm_source', 'utm_medium', 'utm_campaign', 'gclid', 'service', 'notitie' ), '' );
}
