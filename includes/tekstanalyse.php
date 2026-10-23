<?php
/**
 * SFP Page Config - Tools: soort tekstanalyse
 *
 * De bezoeker plakt een tekst; de motor in de browser
 * (assets/tool/tekstanalyse.js) meet per zin en voor de hele tekst en toont
 * tellers, een oordeel, de tekst met markeringen en per zin een regel. De
 * tekst blijft in de browser: geen mail, geen deellink, niets bewaard.
 *
 * Hier alleen normaliseren, controleren en samenvatten.
 *
 * @package SFP_Page_Config
 * @since   3.10.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De meetregels per zin.
 *
 * @return string[]
 */
function sfp_page_config_tekstanalyse_metingen() {
    return array( 'woorden', 'lettergrepen', 'kommas', 'passief', 'lijst', 'lange_woorden' );
}

/**
 * De soorten uitvoer.
 *
 * @return string[]
 */
function sfp_page_config_tekstanalyse_uitvoertypen() {
    return array( 'getal', 'tellers', 'tekst', 'gemarkeerd', 'zinnen' );
}

/**
 * Labels met vanaf, hoogste eerst.
 *
 * @param  mixed $labels Ruwe labels.
 * @return array
 */
function sfp_page_config_tekstanalyse_labels( $labels ) {
    $uit = array();
    foreach ( is_array( $labels ) ? $labels : array() as $l ) {
        if ( is_array( $l ) && isset( $l['vanaf'] ) && is_numeric( $l['vanaf'] ) ) {
            $uit[] = array(
                'vanaf'   => 0 + $l['vanaf'],
                'sleutel' => isset( $l['sleutel'] ) && is_scalar( $l['sleutel'] ) ? (string) $l['sleutel'] : '',
                'tekst'   => isset( $l['tekst'] ) && is_scalar( $l['tekst'] ) ? (string) $l['tekst'] : '',
                'uitleg'  => isset( $l['uitleg'] ) && is_scalar( $l['uitleg'] ) ? (string) $l['uitleg'] : '',
            );
        }
    }
    usort(
        $uit,
        function ( $x, $y ) {
            return $y['vanaf'] <=> $x['vanaf'];
        }
    );
    return $uit;
}

/**
 * Een lijst teksten.
 *
 * @param  mixed $w Waarde.
 * @return string[]
 */
function sfp_page_config_tekstanalyse_teksten( $w ) {
    return is_array( $w ) ? array_values( array_map( 'strval', array_filter( $w, 'is_scalar' ) ) ) : array();
}

/**
 * Vul de standaardwaarden in.
 *
 * @param  array $b Ruwe beschrijving.
 * @return array
 */
function sfp_page_config_tekstanalyse_normaliseer( array $b ) {
    $tekst = function ( $x, $k ) {
        return isset( $x[ $k ] ) && is_scalar( $x[ $k ] ) ? (string) $x[ $k ] : '';
    };
    $invoer = isset( $b['invoer'] ) && is_array( $b['invoer'] ) ? $b['invoer'] : array();
    $uit    = array(
        'versie'          => isset( $b['versie'] ) ? (int) $b['versie'] : 1,
        'soort'           => 'tekstanalyse',
        'resultaat_kop'   => $tekst( $b, 'resultaat_kop' ),
        'resultaat_intro' => $tekst( $b, 'resultaat_intro' ),
        'invoer'          => array(
            'label'      => $tekst( $invoer, 'label' ),
            'hint'       => $tekst( $invoer, 'hint' ),
            'voorbeeld'  => $tekst( $invoer, 'voorbeeld' ),
            'min_tekens' => isset( $invoer['min_tekens'] ) && is_numeric( $invoer['min_tekens'] ) ? max( 1, (int) $invoer['min_tekens'] ) : 50,
            'max_tekens' => isset( $invoer['max_tekens'] ) && is_numeric( $invoer['max_tekens'] ) ? max( 100, min( 20000, (int) $invoer['max_tekens'] ) ) : 5000,
        ),
        'afkortingen'     => sfp_page_config_tekstanalyse_teksten( isset( $b['afkortingen'] ) ? $b['afkortingen'] : array() ),
    );
    $lijsten = array();
    if ( isset( $b['lijsten'] ) && is_array( $b['lijsten'] ) ) {
        foreach ( $b['lijsten'] as $naam => $l ) {
            if ( preg_match( '/^[a-zA-Z0-9_]+$/', (string) $naam ) ) {
                $lijsten[ (string) $naam ] = sfp_page_config_tekstanalyse_teksten( $l );
            }
        }
    }
    $uit['lijsten'] = $lijsten ? $lijsten : new stdClass();
    $per_zin        = array();
    foreach ( isset( $b['per_zin'] ) && is_array( $b['per_zin'] ) ? $b['per_zin'] : array() as $m ) {
        if ( is_array( $m ) && isset( $m['id'], $m['meet'] ) && is_scalar( $m['id'] ) && is_scalar( $m['meet'] ) ) {
            $per_zin[] = array(
                'id'    => (string) $m['id'],
                'meet'  => (string) $m['meet'],
                'lijst' => $tekst( $m, 'lijst' ),
                'label' => $tekst( $m, 'label' ),
            );
        }
    }
    $uit['per_zin'] = $per_zin;
    $klassen        = isset( $b['klassen'] ) && is_array( $b['klassen'] ) ? $b['klassen'] : null;
    $uit['klassen'] = $klassen && isset( $klassen['op'] ) && is_scalar( $klassen['op'] ) ? array(
        'op'     => (string) $klassen['op'],
        'labels' => sfp_page_config_tekstanalyse_labels( isset( $klassen['labels'] ) ? $klassen['labels'] : array() ),
    ) : null;
    $reken = array();
    foreach ( isset( $b['reken'] ) && is_array( $b['reken'] ) ? $b['reken'] : array() as $r ) {
        if ( is_array( $r ) && isset( $r['id'], $r['formule'] ) && is_scalar( $r['id'] ) && is_scalar( $r['formule'] ) ) {
            $reken[] = array(
                'id'      => (string) $r['id'],
                'formule' => (string) $r['formule'],
            );
        }
    }
    $uit['reken']   = $reken;
    $oordeel        = isset( $b['oordeel'] ) && is_array( $b['oordeel'] ) ? $b['oordeel'] : null;
    $uit['oordeel'] = $oordeel && isset( $oordeel['op'] ) && is_scalar( $oordeel['op'] ) ? array(
        'op'     => (string) $oordeel['op'],
        'labels' => sfp_page_config_tekstanalyse_labels( isset( $oordeel['labels'] ) ? $oordeel['labels'] : array() ),
    ) : null;
    $splitsen        = isset( $b['splitsen'] ) && is_array( $b['splitsen'] ) ? $b['splitsen'] : null;
    $uit['splitsen'] = $splitsen ? array(
        'vanaf'       => isset( $splitsen['vanaf'] ) && is_numeric( $splitsen['vanaf'] ) ? max( 1, (int) $splitsen['vanaf'] ) : 26,
        'voegwoorden' => sfp_page_config_tekstanalyse_teksten( isset( $splitsen['voegwoorden'] ) ? $splitsen['voegwoorden'] : array() ),
        'min_tekens'  => isset( $splitsen['min_tekens'] ) && is_numeric( $splitsen['min_tekens'] ) ? max( 1, (int) $splitsen['min_tekens'] ) : 5,
    ) : null;
    $uitvoer = array();
    foreach ( isset( $b['uitvoer'] ) && is_array( $b['uitvoer'] ) ? $b['uitvoer'] : array() as $s ) {
        if ( ! is_array( $s ) ) {
            continue;
        }
        $x = array( 'type' => $tekst( $s, 'type' ) );
        foreach ( array( 'kop', 'label', 'waarde', 'formaat', 'onder', 'tekst', 'alleen', 'regel' ) as $k ) {
            if ( isset( $s[ $k ] ) && is_scalar( $s[ $k ] ) ) {
                $x[ $k ] = (string) $s[ $k ];
            }
        }
        if ( isset( $s['items'] ) && is_array( $s['items'] ) ) {
            $items = array();
            foreach ( $s['items'] as $it ) {
                if ( is_array( $it ) && isset( $it['waarde'] ) && is_scalar( $it['waarde'] ) ) {
                    $items[] = array(
                        'label'   => $tekst( $it, 'label' ),
                        'waarde'  => (string) $it['waarde'],
                        'formaat' => $tekst( $it, 'formaat' ),
                    );
                }
            }
            $x['items'] = $items;
        }
        if ( isset( $s['markeer'] ) ) {
            $x['markeer'] = sfp_page_config_tekstanalyse_teksten( $s['markeer'] );
        }
        foreach ( array( 'suggestie', 'legenda' ) as $k ) {
            if ( isset( $s[ $k ] ) ) {
                $x[ $k ] = (bool) $s[ $k ];
            }
        }
        if ( isset( $s['als'] ) && is_array( $s['als'] ) ) {
            $x['als'] = $s['als'];
        }
        $uitvoer[] = $x;
    }
    $uit['uitvoer'] = $uitvoer;
    $acties         = isset( $b['acties'] ) && is_array( $b['acties'] ) ? $b['acties'] : array();
    $uit['acties']  = array(
        'pdf'              => ! isset( $acties['pdf'] ) || ! empty( $acties['pdf'] ),
        'word'             => ! isset( $acties['word'] ) || ! empty( $acties['word'] ),
        'kopieer'          => ! empty( $acties['kopieer'] ),
        'deellink'         => false,
        'mail'             => false,
        'duimpjes'         => ! isset( $acties['duimpjes'] ) || ! empty( $acties['duimpjes'] ),
        'formulier'        => isset( $acties['formulier'] ) ? (int) $acties['formulier'] : 0,
        'pdf_bestandsnaam' => $tekst( $acties, 'pdf_bestandsnaam' ),
    );
    $bron         = isset( $b['bron'] ) && is_array( $b['bron'] ) ? $b['bron'] : array();
    $uit['bron']  = array(
        'model' => $tekst( $bron, 'model' ),
        'link'  => sfp_page_config_tool_url( $tekst( $bron, 'link' ) ),
    );
    $uit['teksten']   = isset( $b['teksten'] ) && is_array( $b['teksten'] ) ? array_map( 'strval', array_filter( $b['teksten'], 'is_scalar' ) ) : array();
    $uit['genegeerd'] = array();
    if ( ! empty( $acties['mail'] ) || ! empty( $acties['deellink'] ) ) {
        $uit['genegeerd'][] = 'Een tekstanalyse heeft geen mail en geen deellink: de tekst blijft bij de bezoeker.';
    }
    return $uit;
}

/**
 * Controleer een tekstanalyse.
 *
 * @param  array $b Beschrijving.
 * @return array{fouten: string[], let_op: string[]}
 */
function sfp_page_config_tekstanalyse_controleer( array $b ) {
    $fouten = array();
    $let_op = isset( $b['genegeerd'] ) ? (array) $b['genegeerd'] : array();
    $namen  = array( 'zinnen', 'woorden', 'lettergrepen', 'tekens', 'kommas' );
    foreach ( $b['per_zin'] as $m ) {
        if ( ! in_array( $m['meet'], sfp_page_config_tekstanalyse_metingen(), true ) ) {
            $fouten[] = 'Per zin "' . $m['id'] . '": onbekende meting "' . $m['meet'] . '". Bekend: ' . implode( ', ', sfp_page_config_tekstanalyse_metingen() ) . '.';
        }
        if ( 'lijst' === $m['meet'] && ! isset( ( (array) $b['lijsten'] )[ $m['lijst'] ] ) ) {
            $fouten[] = 'Per zin "' . $m['id'] . '": de lijst "' . $m['lijst'] . '" bestaat niet.';
        }
        if ( ! preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $m['id'] ) ) {
            $fouten[] = 'Per zin: "' . $m['id'] . '" is geen geldige naam.';
        }
        $namen[] = $m['id'];
    }
    $formule = function ( $f, $plek ) use ( &$fouten ) {
        if ( '' !== trim( (string) $f ) && null === sfp_page_config_reken_boom( $f ) ) {
            $fouten[] = $plek . ': de formule "' . $f . '" klopt niet.';
        }
    };
    foreach ( $b['reken'] as $r ) {
        $formule( $r['formule'], 'Reken ' . $r['id'] );
    }
    if ( $b['oordeel'] ) {
        $formule( $b['oordeel']['op'], 'Oordeel' );
    }
    if ( $b['klassen'] ) {
        $formule( $b['klassen']['op'], 'Klassen' );
        if ( ! $b['klassen']['labels'] ) {
            $fouten[] = 'Klassen zonder labels.';
        }
    }
    if ( ! $b['uitvoer'] ) {
        $fouten[] = 'Geen uitvoer.';
    }
    foreach ( $b['uitvoer'] as $i => $s ) {
        if ( ! in_array( $s['type'], sfp_page_config_tekstanalyse_uitvoertypen(), true ) ) {
            $fouten[] = 'Uitvoer ' . ( $i + 1 ) . ': onbekend type "' . $s['type'] . '". Bekend: ' . implode( ', ', sfp_page_config_tekstanalyse_uitvoertypen() ) . '.';
        }
        foreach ( array( 'waarde', 'alleen' ) as $k ) {
            if ( isset( $s[ $k ] ) ) {
                $formule( $s[ $k ], 'Uitvoer ' . ( $i + 1 ) );
            }
        }
        foreach ( isset( $s['items'] ) ? $s['items'] : array() as $it ) {
            $formule( $it['waarde'], 'Uitvoer ' . ( $i + 1 ) );
        }
    }
    return compact( 'fouten', 'let_op' );
}

/**
 * Korte samenvatting.
 *
 * @param  array $b Beschrijving.
 * @return array
 */
function sfp_page_config_tekstanalyse_samenvatting( array $b ) {
    return array(
        'soort'     => 'tekstanalyse',
        'metingen'  => count( $b['per_zin'] ),
        'secties'   => count( $b['uitvoer'] ),
        'acties'    => array_keys( array_filter( $b['acties'] ) ),
        'formulier' => $b['acties']['formulier'],
    );
}
