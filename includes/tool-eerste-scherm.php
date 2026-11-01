<?php
/**
 * SFP Page Config - Tools: het eerste scherm al in de HTML (3.22.0).
 *
 * Tot 3.21.0 stond de tool leeg en verborgen in de pagina; motor.js bouwde
 * hem pas na het laden en duwde alles eronder 300 tot 570 px omlaag
 * (gemeten 31-10: DPS spreektijd CLS 0,35, SFP communicatiestijlentest 0,226
 * bij een trage verbinding). Nu zet de server het eerste scherm al neer, met
 * dezelfde opbouw en klassen als de motor, in .sfp-tool__voorlopig. De motor
 * haalt dat weg en bouwt op dezelfde plek de echte tool: de hoogte blijft
 * gelijk, er verspringt niets.
 *
 * Het voorlopige scherm is alleen beeld: inert, zonder namen op de velden,
 * en het telt niet voor schermlezers (de echte tool komt direct daarna).
 *
 * Wat de motor pas weet in de browser (een gedeelde link, bewaarde
 * voortgang), verandert het eerste scherm daarna nog; dat is een keuze van
 * de bezoeker, niet het laden van de pagina.
 *
 * Soorten: test (per vraag, per groep, alles) en de soorten op de
 * generatormotor (generator, rekenaar, checklist), ook live en in de
 * kaartweergave. De tekstanalyse heeft één vast scherm.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Een vaste tekst van de motor, met de eigen teksten van de tool voor.
 *
 * @param  array  $b       Beschrijving.
 * @param  string $sleutel Sleutel (tool_...).
 * @param  bool   $eigen   Ook de eigen teksten van de tool (zoals tx() in de motor).
 * @return string
 */
function sfp_page_config_tool_es_tekst( array $b, $sleutel, $eigen = true ) {
    $args = array_slice( func_get_args(), 3 );
    $s    = $eigen && isset( $b['teksten'][ $sleutel ] ) && '' !== (string) $b['teksten'][ $sleutel ] ? (string) $b['teksten'][ $sleutel ] : (string) sfp_page_config_artikel_tekst( $sleutel );
    $i    = 0;
    return (string) preg_replace_callback(
        '/%(\d+\$)?[sd]/',
        function ( $m ) use ( $args, &$i ) {
            $idx = ! empty( $m[1] ) ? (int) $m[1] - 1 : $i++;
            return isset( $args[ $idx ] ) ? (string) $args[ $idx ] : '';
        },
        $s
    );
}

/**
 * Alinea's uit een tekst, zoals alineas() in de motor.
 *
 * @param  string $tekst Tekst.
 * @return string        HTML.
 */
function sfp_page_config_tool_es_alineas( $tekst ) {
    $uit = '';
    foreach ( preg_split( '/\n\s*\n/', trim( (string) $tekst ) ) as $a ) {
        if ( '' !== trim( $a ) ) {
            $uit .= '<p>' . esc_html( trim( $a ) ) . '</p>';
        }
    }
    return $uit;
}

/**
 * Het eerste scherm van een tool, of een lege tekst als deze vorm (nog)
 * niet vooraf kan.
 *
 * @param  array  $b     Beschrijving.
 * @param  int    $id    Post-id van de tool.
 * @param  string $titel Titel van de tool (kop van een live uitkomst zonder eigen kop).
 * @return string        HTML voor in .sfp-tool__app.
 */
function sfp_page_config_tool_eerste_scherm( array $b, $id, $titel = '' ) {
    $soort = isset( $b['soort'] ) ? (string) $b['soort'] : '';
    if ( 'test' === $soort ) {
        $html = sfp_page_config_tool_es_test( $b, (int) $id );
    } elseif ( function_exists( 'sfp_page_config_tool_op_generator' ) && sfp_page_config_tool_op_generator( $soort ) ) {
        $html = sfp_page_config_tool_es_generator( $b, (int) $id, (string) $titel );
    } elseif ( 'tekstanalyse' === $soort ) {
        $html = sfp_page_config_tool_es_tekstanalyse( $b );
    } else {
        $html = '';
    }
    if ( '' === $html ) {
        return '';
    }
    // Geen name en id op de velden van het voorbeeld: die horen bij de echte tool.
    $html = preg_replace( '/\s(?:name|id|for|aria-labelledby)="[^"]*"/', '', $html );
    // De regel voor schermlezers zet de motor ook vooraan; hij neemt een pixel in.
    return '<div class="sfp-tool__voorlopig" aria-hidden="true" inert><span class="sfp-tool__sr"></span>' . $html . '</div>';
}

/* =========================================================================
 * Test
 * ====================================================================== */

/**
 * @param  array $b  Beschrijving.
 * @param  int   $id Tool.
 * @return string
 */
function sfp_page_config_tool_es_test( array $b, $id ) {
    $w         = isset( $b['weergave'] ) && is_array( $b['weergave'] ) ? $b['weergave'] : array();
    $modus     = isset( $w['modus'] ) ? (string) $w['modus'] : 'per-vraag';
    $per_groep = 'per-groep' === $modus;
    $per_vraag = 'alles' !== $modus;
    $vragen    = array();
    foreach ( isset( $b['vragen'] ) && is_array( $b['vragen'] ) ? $b['vragen'] : array() as $v ) {
        if ( ! is_array( $v ) ) {
            continue;
        }
        $type = isset( $v['type'] ) && '' !== $v['type'] ? (string) $v['type'] : 'likert';
        if ( 'ab' === $type || 'janee' === $type ) {
            $type = 'keuze';
        }
        $v['type'] = $type;
        if ( 'tekst' === $type && ! empty( $v['na_uitslag'] ) ) {
            continue;
        }
        $vragen[] = $v;
    }
    if ( ! $vragen ) {
        return '';
    }
    $context = false;
    foreach ( $vragen as $v ) {
        if ( 'context' === $v['type'] ) {
            $context = true;
        }
    }
    $namen = ! empty( $w['stappen'] ) && is_array( $w['stappen'] ) ? $w['stappen'] : ( $context
        ? array( sfp_page_config_tool_es_tekst( $b, 'tool_context', false ), sfp_page_config_tool_es_tekst( $b, 'tool_vragen', false ), sfp_page_config_tool_es_tekst( $b, 'tool_resultaat', false ) )
        : array( sfp_page_config_tool_es_tekst( $b, 'tool_vragen', false ), sfp_page_config_tool_es_tekst( $b, 'tool_resultaat', false ) ) );

    // Welke schermen er zijn, zoals in de motor.
    $schermen = array();
    if ( ! empty( $w['intro']['tekst'] ) ) {
        $knop       = ! empty( $w['intro']['knop'] ) ? (string) $w['intro']['knop'] : sfp_page_config_tool_es_tekst( $b, 'tool_start' );
        $schermen[] = array(
            'intro' => true,
            'html'  => '<div class="sfp-tool__scherm sfp-tool__scherm--intro" data-intro="1">' . sfp_page_config_tool_es_alineas( $w['intro']['tekst'] ) . '<button type="button" class="sfp-tool__knop sfp-tool__knop--primair">' . esc_html( $knop ) . '</button></div>'
        );
    }
    if ( $per_groep ) {
        $groepen = array();
        foreach ( $vragen as $i => $v ) {
            $naam = isset( $v['groep'] ) ? (string) $v['groep'] : '';
            $n    = count( $groepen );
            if ( ! $n || $groepen[ $n - 1 ]['naam'] !== $naam ) {
                $groepen[] = array(
                    'naam'   => $naam,
                    'vragen' => array()
                );
                $n++;
            }
            $groepen[ $n - 1 ]['vragen'][ $i ] = $v;
        }
        foreach ( $groepen as $g ) {
            $in = '';
            foreach ( $g['vragen'] as $i => $v ) {
                if ( ! empty( $v['intro'] ) ) {
                    $in .= '<div class="sfp-tool__intro-tekst">' . sfp_page_config_tool_es_alineas( $v['intro'] ) . '</div>';
                }
                $in .= sfp_page_config_tool_es_vraag( $b, $v, $i, $vragen, $per_vraag, $per_groep, true, $id );
            }
            $in        .= '<button type="button" class="sfp-tool__knop sfp-tool__knop--primair">' . esc_html( sfp_page_config_tool_es_tekst( $b, 'tool_volgende' ) ) . '</button>';
            $schermen[] = array(
                'groep' => $g,
                'html'  => '<div class="sfp-tool__scherm sfp-tool__scherm--groep">' . $in . '</div>'
            );
        }
    } else {
        foreach ( $vragen as $i => $v ) {
            if ( ! empty( $v['intro'] ) ) {
                $schermen[] = array(
                    'intro' => true,
                    'html'  => $per_vraag
                        ? '<div class="sfp-tool__scherm sfp-tool__scherm--intro" data-intro="1">' . sfp_page_config_tool_es_alineas( $v['intro'] ) . '<button type="button" class="sfp-tool__knop">' . esc_html( sfp_page_config_tool_es_tekst( $b, 'tool_start' ) ) . '</button></div>'
                        : '<div class="sfp-tool__scherm sfp-tool__intro-tekst is-alles">' . sfp_page_config_tool_es_alineas( $v['intro'] ) . '</div>',
                );
            }
            $schermen[] = array(
                'vraag' => $v,
                'index' => $i,
                'html'  => sfp_page_config_tool_es_vraag( $b, $v, $i, $vragen, $per_vraag, $per_groep, ! $per_vraag, $id )
            );
        }
    }

    // De opschriftregel en de balk bij het eerste scherm.
    $scorend = array();
    foreach ( $vragen as $v ) {
        if ( 'likert' === $v['type'] || 'keuze' === $v['type'] ) {
            $scorend[] = $v;
        }
    }
    $voortgang = '';
    $breedte   = '';
    $zonder    = true;
    $eerste    = $schermen[0];
    if ( $per_vraag ) {
        if ( isset( $eerste['groep'] ) ) {
            $gn        = count( $schermen );
            $naam      = isset( $eerste['groep']['vragen'] ) ? (string) reset( $eerste['groep']['vragen'] )['groep'] : '';
            $voortgang = '' !== $naam ? $naam . ' · 1/' . $gn : sfp_page_config_tool_es_tekst( $b, 'tool_vraag_van', true, 1, $gn );
            $breedte   = round( 1 / max( 1, $gn ) * 100 ) . '%';
            $zonder    = false;
        } elseif ( isset( $eerste['vraag'] ) && ( 'likert' === $eerste['vraag']['type'] || 'keuze' === $eerste['vraag']['type'] ) ) {
            $groepen   = array_unique(
                array_map(
                    function ( $v ) {
                        return isset( $v['groep'] ) ? (string) $v['groep'] : '';
                    },
                    $scorend
                )
            );
            $voortgang = ( count( $groepen ) > 1 && ! empty( $eerste['vraag']['groep'] ) ? $eerste['vraag']['groep'] . ' · ' : '' ) . sfp_page_config_tool_es_tekst( $b, 'tool_vraag_van', true, 1, count( $scorend ) );
            $breedte   = round( 1 / max( 1, count( $scorend ) ) * 100 ) . '%';
            $zonder    = false;
        } elseif ( isset( $eerste['vraag'] ) && 'context' !== $eerste['vraag']['type'] && ! empty( $eerste['vraag']['groep'] ) ) {
            $voortgang = (string) $eerste['vraag']['groep'];
        }
    }

    $stappen = '';
    foreach ( $namen as $k => $naam ) {
        $actief   = 0 === $k ? ' is-actief' : '';
        $stappen .= '<li class="sfp-tool__stap' . $actief . ( count( $namen ) - 1 === $k ? ' is-slot' : '' ) . '" role="tab" aria-selected="' . ( 0 === $k ? 'true' : 'false' ) . '">' . esc_html( (string) $naam ) . '</li>';
    }
    $html  = '<ul class="sfp-tool__stappen" role="tablist">' . $stappen . '</ul>';
    $html .= '<div class="sfp-tool__paneel is-actief" role="tabpanel">';
    $html .= '<p class="sfp-tool__voortgang"' . ( ! $per_vraag || ( $zonder && '' === $voortgang ) ? ' hidden' : '' ) . '>' . esc_html( $voortgang ) . '</p>';
    $html .= '<div class="sfp-tool__balk"' . ( ! $per_vraag || $zonder ? ' hidden' : '' ) . '><div class="sfp-tool__balk-vul"' . ( '' !== $breedte ? ' style="width:' . esc_attr( $breedte ) . '"' : '' ) . '></div></div>';
    $html .= '<div class="sfp-tool__schermen">';
    if ( $per_vraag ) {
        $html .= $eerste['html'];
    } else {
        foreach ( $schermen as $s ) {
            $html .= $s['html'];
        }
        $html .= '<button type="button" class="sfp-tool__knop sfp-tool__knop--primair">' . esc_html( sfp_page_config_tool_es_tekst( $b, 'tool_naar_resultaat' ) ) . '</button>';
    }
    $html .= '</div></div>';
    return $html;
}

/**
 * Eén vraag van een test, zoals maakScherm() in test.js.
 *
 * @param  array $b         Beschrijving.
 * @param  array $v         Vraag.
 * @param  int   $index     Plaats.
 * @param  array $vragen    Alle vragen.
 * @param  bool  $per_vraag Per vraag.
 * @param  bool  $per_groep Per groep.
 * @param  bool  $alles     Getoond als deel van een groep of pagina.
 * @param  int   $id        Tool.
 * @return string
 */
function sfp_page_config_tool_es_vraag( array $b, array $v, $index, array $vragen, $per_vraag, $per_groep, $alles, $id ) {
    $w      = isset( $b['weergave'] ) && is_array( $b['weergave'] ) ? $b['weergave'] : array();
    $vorige = $index > 0 && isset( $vragen[ $index - 1 ] ) ? $vragen[ $index - 1 ] : null;
    $kop    = '';
    if ( ! $per_vraag && ! empty( $v['groep'] ) && ( ! $vorige || ( isset( $vorige['groep'] ) ? $vorige['groep'] : '' ) !== $v['groep'] ) ) {
        $kop = '<p class="sfp-tool__groep">' . esc_html( $v['groep'] ) . '</p>';
    }
    $vraagkop = ! empty( $w['vraagkop'] ) && ! $per_groep && 'tekst' !== $v['type'] ? '<p class="sfp-tool__vraagkop">' . esc_html( $w['vraagkop'] ) . '</p>' : '';
    $tekst    = '<p class="sfp-tool__vraag">' . esc_html( isset( $v['tekst'] ) ? $v['tekst'] : '' ) . '</p>';
    $veld     = '';
    if ( 'likert' === $v['type'] ) {
        $schaal    = ! empty( $v['schaal'] ) && is_array( $v['schaal'] ) ? $v['schaal'] : ( isset( $w['schaal'] ) && is_array( $w['schaal'] ) ? $w['schaal'] : array() );
        $als_tekst = ! empty( $w['likert_als_tekst'] );
        $vakjes    = '';
        for ( $n = 1; $n <= 5; $n++ ) {
            $label   = $als_tekst ? ( isset( $schaal[ (string) $n ] ) ? $schaal[ (string) $n ] : (string) $n ) : (string) $n;
            $vakjes .= '<label class="sfp-tool__optie sfp-tool__optie--likert"><input type="radio" class="sfp-tool__radio" disabled><span class="sfp-tool__optie-tekst">' . esc_html( $label ) . '</span></label>';
        }
        $uiteinden = $als_tekst ? '' : '<div class="sfp-tool__uiteinden"><span>' . esc_html( isset( $schaal['1'] ) ? $schaal['1'] : '' ) . '</span><span>' . esc_html( isset( $schaal['3'] ) ? $schaal['3'] : '' ) . '</span><span>' . esc_html( isset( $schaal['5'] ) ? $schaal['5'] : '' ) . '</span></div>';
        $veld      = '<div class="sfp-tool__likert-wrap"><div class="sfp-tool__likert' . ( $als_tekst ? ' sfp-tool__likert--tekst' : '' ) . '" role="radiogroup">' . $vakjes . '</div>' . $uiteinden . '</div>';
    } elseif ( 'keuze' === $v['type'] || 'context' === $v['type'] ) {
        $veld = '<div class="sfp-tool__keuzes" role="radiogroup">';
        foreach ( isset( $v['opties'] ) && is_array( $v['opties'] ) ? $v['opties'] : array() as $o ) {
            $veld .= '<label class="sfp-tool__optie"><input type="radio" class="sfp-tool__radio" disabled><span class="sfp-tool__optie-tekst">' . esc_html( is_array( $o ) && isset( $o['tekst'] ) ? $o['tekst'] : '' ) . '</span></label>';
        }
        $veld .= '</div>';
    } else {
        $veld = '<textarea class="sfp-tool__veld" rows="3" disabled placeholder="' . esc_attr( isset( $v['voorbeeld'] ) ? $v['voorbeeld'] : '' ) . '"></textarea>';
    }
    $knop = 'tekst' === $v['type'] && $per_vraag && ! $per_groep ? '<button type="button" class="sfp-tool__knop">' . esc_html( sfp_page_config_tool_es_tekst( $b, 'tool_volgende' ) ) . '</button>' : '';
    return '<div class="sfp-tool__scherm' . ( $alles ? ' is-alles' : '' ) . '" data-index="' . (int) $index . '">' . ( $per_groep ? '' : $kop ) . $vraagkop . $tekst . $veld . $knop . '</div>';
}

/* =========================================================================
 * Tekstanalyse
 * ====================================================================== */

/**
 * Het invoerscherm van de tekstanalyse, zoals in tekstanalyse.js.
 *
 * @param  array $b Beschrijving.
 * @return string
 */
function sfp_page_config_tool_es_tekstanalyse( array $b ) {
    $inv = isset( $b['invoer'] ) && is_array( $b['invoer'] ) ? $b['invoer'] : array();
    $max = ! empty( $inv['max_tekens'] ) ? (int) $inv['max_tekens'] : 5000;
    $in  = '<label class="sfp-tool__vraag">' . esc_html( isset( $inv['label'] ) ? $inv['label'] : '' ) . '</label>';
    $in .= ! empty( $inv['hint'] ) ? '<p class="sfp-tool__hint">' . esc_html( $inv['hint'] ) . '</p>' : '';
    $in .= '<textarea class="sfp-tool__veld" rows="9" disabled placeholder="' . esc_attr( isset( $inv['voorbeeld'] ) ? $inv['voorbeeld'] : '' ) . '"></textarea>';
    $in .= '<p class="sfp-tool__tekstvak-teller">' . esc_html( sfp_page_config_tool_es_tekst( $b, 'tool_tekens', true, 0, $max ) ) . '</p>';
    $in .= '<p class="sfp-tool__melding is-fout" role="alert" hidden></p>';
    $in .= '<div class="sfp-tool__nav"><button type="button" class="sfp-tool__knop sfp-tool__knop--primair">' . esc_html( sfp_page_config_tool_es_tekst( $b, 'tool_analyseer' ) ) . '</button></div>';
    return '<div class="sfp-tool__schermen"><div class="sfp-tool__scherm">' . $in . '</div></div>';
}

/* =========================================================================
 * Generator, rekenaar, checklist
 * ====================================================================== */

/**
 * @param  array $b  Beschrijving.
 * @param  int   $id Tool.
 * @return string
 */
function sfp_page_config_tool_es_generator( array $b, $id, $titel = '' ) {
    if ( ! function_exists( 'sfp_page_config_generator_klopt' ) ) {
        return '';
    }
    $stappen = isset( $b['stappen'] ) && is_array( $b['stappen'] ) ? array_values( $b['stappen'] ) : array();
    if ( ! $stappen ) {
        return '';
    }
    $live  = ! empty( $b['live'] );
    $kaart = isset( $b['weergave']['modus'] ) && 'kaart' === $b['weergave']['modus'];
    // De standaardwaarden, zoals standaard() in de motor (zonder vandaag).
    $a = array();
    foreach ( $stappen as $s ) {
        foreach ( isset( $s['velden'] ) && is_array( $s['velden'] ) ? $s['velden'] : array() as $v ) {
            if ( isset( $v['id'], $v['standaard'] ) && is_string( $v['standaard'] ) && '' !== $v['standaard'] && 'vandaag' !== $v['standaard'] ) {
                $a[ $v['id'] ] = $v['standaard'];
            } elseif ( isset( $v['id'], $v['standaard'] ) && 'vinkjes' === ( $v['type'] ?? '' ) && is_array( $v['standaard'] ) ) {
                $a[ $v['id'] ] = array_map( 'strval', $v['standaard'] );
            }
        }
    }
    $schermen = '';
    foreach ( $stappen as $i => $s ) {
        if ( ! $live && ! $kaart && $i > 0 ) {
            break;
        }
        $in = '';
        if ( ! empty( $s['intro'] ) ) {
            $in .= '<div class="sfp-tool__intro-tekst" data-stap="' . (int) $i . '">' . sfp_page_config_tool_es_alineas( sfp_page_config_generator_vul( $s['intro'], $b, $a ) ) . '</div>';
        }
        $alleen_keuzes = true;
        $zicht         = 0;
        foreach ( isset( $s['velden'] ) && is_array( $s['velden'] ) ? $s['velden'] : array() as $v ) {
            $in .= sfp_page_config_tool_es_veld( $b, $v, $a, $kaart );
            if ( sfp_page_config_generator_klopt( $b, $a, isset( $v['toon_als'] ) ? $v['toon_als'] : null ) ) {
                $zicht++;
                if ( 'keuze' !== ( isset( $v['type'] ) ? $v['type'] : '' ) ) {
                    $alleen_keuzes = false;
                }
            }
        }
        if ( ! $live ) {
            $laatste = count( $stappen ) - 1 === $i;
            $tekst   = $laatste && ! $kaart ? sfp_page_config_tool_es_tekst( $b, 'tool_maak' ) : sfp_page_config_tool_es_tekst( $b, 'tool_volgende' );
            $verberg = $kaart && $zicht > 0 && $alleen_keuzes;
            $in     .= '<div class="sfp-tool__nav"' . ( $verberg ? ' hidden' : '' ) . '><button type="button" class="sfp-tool__knop sfp-tool__knop--primair">' . esc_html( $tekst ) . '</button></div>';
        }
        $klasse    = 'sfp-tool__scherm' . ( $kaart && $i > 0 ? ' is-weg' : '' );
        $schermen .= '<div class="' . $klasse . '"' . ( $kaart && $i > 0 ? ' aria-hidden="true"' : '' ) . '>' . $in . '</div>';
    }

    if ( $live ) {
        // Eén scherm met de velden, de uitkomst eronder: de kop, de acties
        // en de duimpjes; de getallen zelf rekent de motor.
        $kop = ! empty( $b['resultaat_kop'] ) ? sfp_page_config_generator_vul( $b['resultaat_kop'], $b, $a ) : (string) $titel;
        $res = '<div class="sfp-tool__scherm sfp-tool__scherm--resultaat sfp-tool__scherm--live">' . ( '' !== $kop ? '<h3 class="sfp-tool__kop sfp-tool__kop--resultaat">' . esc_html( $kop ) . '</h3>' : '' ) . '<div class="sfp-tool__uitvoer"></div>' . sfp_page_config_tool_es_slot( $b ) . '</div>';
        return '<p class="sfp-tool__melding" role="alert" hidden></p><div class="sfp-tool__schermen">' . $schermen . $res . '</div>';
    }
    if ( $kaart ) {
        $groepen = ! empty( $b['weergave']['groepen'] ) && is_array( $b['weergave']['groepen'] ) ? $b['weergave']['groepen'] : array();
        if ( ! $groepen ) {
            foreach ( $stappen as $k => $s ) {
                $groepen[] = array(
                    'letter' => (string) ( $k + 1 ),
                    'naam'   => isset( $s['titel'] ) ? (string) $s['titel'] : ''
                );
            }
        }
        $letters = '';
        foreach ( $groepen as $k => $g ) {
            $letters .= '<li><button type="button" class="sfp-tool__letter' . ( 0 === $k ? ' is-actief' : '' ) . '">' . esc_html( isset( $g['letter'] ) ? $g['letter'] : '' ) . '</button></li>';
        }
        $naam = sfp_page_config_tool_es_tekst( $b, 'tool_groep_van', true, 1, count( $groepen ) ) . ( ! empty( $groepen[0]['naam'] ) ? ' · ' . $groepen[0]['naam'] : '' );
        return '<div class="sfp-tool__kaartkop"><ol class="sfp-tool__letters">' . $letters . '</ol><button type="button" class="sfp-tool__tekstlink sfp-tool__terug" hidden>' . esc_html( sfp_page_config_tool_es_tekst( $b, 'tool_terug' ) ) . '</button></div>'
            . '<p class="sfp-tool__groepnaam">' . esc_html( $naam ) . '</p>'
            . '<div class="sfp-tool__balk sfp-tool__balk--kaart"><div class="sfp-tool__balk-vul" style="width:0%"></div></div>'
            . '<p class="sfp-tool__melding" role="alert" hidden></p><div class="sfp-tool__schermen">' . $schermen . '</div>';
    }
    // Gewoon: de stappen bovenaan, dan het eerste scherm.
    $balk  = '';
    $namen = array();
    foreach ( $stappen as $s ) {
        $namen[] = isset( $s['titel'] ) ? (string) $s['titel'] : '';
    }
    $namen[] = sfp_page_config_tool_es_tekst( $b, 'tool_resultaat' );
    foreach ( $namen as $k => $naam ) {
        $balk .= '<li class="sfp-tool__stap' . ( 0 === $k ? ' is-actief' : '' ) . ( count( $namen ) - 1 === $k ? ' is-slot' : '' ) . '" role="tab" aria-selected="' . ( 0 === $k ? 'true' : 'false' ) . '">' . esc_html( $naam ) . '</li>';
    }
    return '<ol class="sfp-tool__stappen" role="tablist">' . $balk . '</ol><button type="button" class="sfp-tool__vorige" hidden></button><p class="sfp-tool__melding" role="alert" hidden></p><div class="sfp-tool__schermen">' . $schermen . '</div>';
}

/**
 * Eén veld, zoals maakVeld() in generator.js, met de label zoals
 * werkZichtbaar() hem zet.
 *
 * @param  array $b     Beschrijving.
 * @param  mixed $v     Veld.
 * @param  array $a     Standaardantwoorden.
 * @param  bool  $kaart Kaartweergave.
 * @return string
 */
function sfp_page_config_tool_es_veld( array $b, $v, array $a, $kaart ) {
    if ( ! is_array( $v ) ) {
        return '';
    }
    $type  = isset( $v['type'] ) ? (string) $v['type'] : 'kort';
    $zicht = sfp_page_config_generator_klopt( $b, $a, isset( $v['toon_als'] ) ? $v['toon_als'] : null );
    $lab   = isset( $v['label'] ) ? (string) $v['label'] : '';
    foreach ( isset( $v['label_als'] ) && is_array( $v['label_als'] ) ? $v['label_als'] : array() as $r ) {
        if ( is_array( $r ) && isset( $r['tekst'] ) && sfp_page_config_generator_klopt( $b, $a, isset( $r['als'] ) ? $r['als'] : null ) ) {
            $lab = (string) $r['tekst'];
            break;
        }
    }
    $verplicht = $zicht && ( true === ( $v['verplicht'] ?? null ) || ( isset( $v['verplicht'] ) && is_array( $v['verplicht'] ) && sfp_page_config_generator_klopt( $b, $a, $v['verplicht'] ) ) );
    $lab       = sfp_page_config_generator_vul( $lab, $b, $a ) . ( $verplicht || 'keuze' === $type || 'vinkjes' === $type ? '' : ' ' . sfp_page_config_tool_es_tekst( $b, 'tool_optioneel' ) );
    $label     = '<p class="sfp-tool__vraag">' . esc_html( $lab ) . '</p>';
    $hint      = ! empty( $v['hint'] ) ? '<p class="sfp-tool__hint">' . esc_html( sfp_page_config_generator_vul( $v['hint'], $b, $a ) ) . '</p>' : '';
    $opties    = isset( $v['opties'] ) && is_array( $v['opties'] ) ? $v['opties'] : array();
    $vb        = isset( $v['voorbeeld'] ) ? sfp_page_config_generator_vul( $v['voorbeeld'], $b, $a ) : '';
    if ( 'keuze' === $type && $kaart ) {
        $kw         = 'kwadrant' === ( $v['tegels'] ?? '' ) && 4 === count( $opties ) && isset( $v['assen']['kolommen'], $v['assen']['rijen'] ) && 2 === count( (array) $v['assen']['kolommen'] ) && 2 === count( (array) $v['assen']['rijen'] );
        $met_uitleg = false;
        foreach ( $opties as $o ) {
            if ( ! empty( $o['uitleg'] ) ) {
                $met_uitleg = true;
            }
        }
        $kol   = ! empty( $v['kolommen'] ) ? (int) $v['kolommen'] : ( $kw || 4 === count( $opties ) || $met_uitleg ? 2 : 3 );
        $tegel = function ( $o ) {
            $iconen = sfp_page_config_tool_iconen();
            $icoon  = ! empty( $o['icoon'] ) && isset( $iconen[ $o['icoon'] ] ) ? '<span class="sfp-tool__icoon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">' . $iconen[ $o['icoon'] ] . '</svg></span>' : '';
            return '<label class="sfp-tool__tegel' . ( '' !== $icoon ? ' met-icoon' : '' ) . '"><input type="radio" class="sfp-tool__radio" disabled>' . $icoon . '<b>' . esc_html( $o['tekst'] ?? '' ) . '</b>' . ( ! empty( $o['uitleg'] ) ? '<small>' . esc_html( $o['uitleg'] ) . '</small>' : '' ) . '</label>';
        };
        $veld  = '<div class="sfp-tool__tegels sfp-tool__tegels--' . $kol . ( $kw ? ' sfp-tool__tegels--kwadrant' : '' ) . ( $met_uitleg ? ' sfp-tool__tegels--uitleg' : '' ) . '" role="radiogroup">';
        if ( $kw ) {
            $veld .= '<span class="sfp-tool__as sfp-tool__as--hoek" aria-hidden="true"></span>';
            foreach ( $v['assen']['kolommen'] as $c ) {
                $veld .= '<span class="sfp-tool__as" aria-hidden="true">' . esc_html( $c ) . '</span>';
            }
            foreach ( array( 0, 1 ) as $r ) {
                $veld .= '<span class="sfp-tool__as sfp-tool__as--rij" aria-hidden="true">' . esc_html( $v['assen']['rijen'][ $r ] ) . '</span>' . $tegel( $opties[ $r * 2 ] ) . $tegel( $opties[ $r * 2 + 1 ] );
            }
        } else {
            foreach ( $opties as $o ) {
                $veld .= $tegel( (array) $o );
            }
        }
        $veld .= '</div>';
    } elseif ( 'keuze' === $type || 'vinkjes' === $type ) {
        $veld = '<div class="sfp-tool__keuzes' . ( 'vinkjes' === $type ? ' sfp-tool__keuzes--vinkjes' : '' ) . '" role="' . ( 'vinkjes' === $type ? 'group' : 'radiogroup' ) . '">';
        foreach ( $opties as $o ) {
            $o     = (array) $o;
            $aan   = 'vinkjes' === $type && isset( $a[ $v['id'] ] ) && is_array( $a[ $v['id'] ] ) && in_array( (string) ( $o['waarde'] ?? '' ), $a[ $v['id'] ], true );
            $veld .= '<label class="sfp-tool__optie"><input type="' . ( 'vinkjes' === $type ? 'checkbox' : 'radio' ) . '" class="sfp-tool__radio" disabled' . ( $aan ? ' checked' : '' ) . '><span class="sfp-tool__optie-tekst">' . esc_html( $o['tekst'] ?? '' ) . ( ! empty( $o['uitleg'] ) ? '<small class="sfp-tool__optie-uitleg">' . esc_html( $o['uitleg'] ) . '</small>' : '' ) . '</span></label>';
        }
        $veld .= '</div>';
    } elseif ( 'lijst' === $type ) {
        $veld = '<select class="sfp-tool__veld" disabled><option value="">' . esc_html( sfp_page_config_tool_es_tekst( $b, 'tool_kies' ) ) . '</option></select>';
    } elseif ( in_array( $type, array( 'tekstvak', 'regels', 'getallen' ), true ) ) {
        $veld = '<textarea class="sfp-tool__veld" rows="' . ( 'tekstvak' === $type ? '4' : '5' ) . '" disabled placeholder="' . esc_attr( $vb ) . '"></textarea>';
    } else {
        $soort = 'datum' === $type ? 'date' : ( 'getal' === $type ? 'number' : 'text' );
        $veld  = '<input type="' . $soort . '" class="sfp-tool__veld" disabled placeholder="' . esc_attr( $vb ) . '">';
        if ( 'getal' === $type && ! empty( $v['eenheid'] ) ) {
            $veld = '<div class="sfp-tool__getalveld">' . $veld . '<span class="sfp-tool__eenheid">' . esc_html( $v['eenheid'] ) . '</span></div>';
        }
    }
    return '<div class="sfp-tool__veldgroep"' . ( $zicht ? '' : ' hidden' ) . '>' . $label . $hint . $veld . '</div>';
}

/**
 * Wat onder een live uitkomst staat: acties, Opnieuw en de duimpjes, zodat
 * de hoogte vooraf klopt.
 *
 * @param  array $b Beschrijving.
 * @return string
 */
function sfp_page_config_tool_es_slot( array $b ) {
    $acties = isset( $b['acties'] ) && is_array( $b['acties'] ) ? $b['acties'] : array();
    $html   = '<div class="sfp-tool__acties sfp-tool__acties--uitslag"><div class="sfp-tool__acties-links"></div></div>';
    $html  .= '<p class="sfp-tool__melding sfp-tool__melding--klein" role="status" hidden></p>';
    $html  .= '<button type="button" class="sfp-tool__tekstlink sfp-tool__opnieuw">' . esc_html( sfp_page_config_tool_es_tekst( $b, 'tool_opnieuw' ) ) . '</button>';
    if ( ! array_key_exists( 'duimpjes', $acties ) || ! empty( $acties['duimpjes'] ) ) {
        $html .= '<div class="sfp-tool__duim"><p class="sfp-tool__duim-vraag">' . esc_html( sfp_page_config_tool_es_tekst( $b, 'tool_duim_vraag' ) ) . '</p><div class="sfp-tool__duim-knoppen"><button type="button" class="sfp-tool__duim-knop">' . esc_html( sfp_page_config_tool_es_tekst( $b, 'tool_duim_ja' ) ) . '</button><button type="button" class="sfp-tool__duim-knop">' . esc_html( sfp_page_config_tool_es_tekst( $b, 'tool_duim_nee' ) ) . '</button></div></div>';
    }
    return $html;
}

/**
 * De pictogrammen van de toolmotor (sinds 3.22.1), gelezen uit
 * assets/tool/generator-bouw.js zodat er één bron is. Het eerste scherm en
 * de editor tonen zo dezelfde lijntekening als de motor.
 *
 * @return array<string,string> Naam naar SVG-inhoud.
 */
function sfp_page_config_tool_iconen() {
    static $iconen = null;
    if ( null !== $iconen ) {
        return $iconen;
    }
    $iconen = array();
    $bron   = @file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/tool/generator-bouw.js' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
    if ( ! is_string( $bron ) || ! preg_match( '/var ICONEN = \{(.*?)\n\s*\};/s', $bron, $blok ) ) {
        return $iconen;
    }
    preg_match_all( "/^\s*([a-z]+): '([^']*)'/m", $blok[1], $m, PREG_SET_ORDER );
    foreach ( $m as $r ) {
        // Alleen SVG-vormen, geen andere opmaak.
        $iconen[ $r[1] ] = wp_kses(
            $r[2],
            array(
                'path'   => array( 'd' => true ),
                'circle' => array( 'cx' => true, 'cy' => true, 'r' => true ),
            )
        );
    }
    return $iconen;
}
