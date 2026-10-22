<?php
/**
 * SFP Page Config - Tools: de beschrijving en het rekenwerk
 *
 * De beschrijving (JSON) van een tool: normaliseren, controleren en
 * uitrekenen. Het rekenwerk hier is gelijk aan assets/tool/test.js; de
 * browser rekent voor de bezoeker, de server rekent opnieuw voor de mail
 * en voor de tekst "Hoe deze test rekent". tests/tool.php legt beide naast
 * elkaar.
 *
 * Formaat: claude/tools-motor-ontwerp-test-2026-10-19.md (project).
 *
 * @package SFP_Page_Config
 * @since   3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Normaliseren
 * ====================================================================== */

/**
 * Vul de standaardwaarden in en zet lijsten in een vaste vorm, zodat de
 * rest van de code geen isset() nodig heeft.
 *
 * @param  array $b Ruwe beschrijving.
 * @return array
 */
function sfp_page_config_tool_normaliseer( array $b ) {
    $b['versie'] = isset( $b['versie'] ) ? (int) $b['versie'] : 1;
    $b['soort']  = isset( $b['soort'] ) ? (string) $b['soort'] : '';

    $weergave       = isset( $b['weergave'] ) && is_array( $b['weergave'] ) ? $b['weergave'] : array();
    $intro          = isset( $weergave['intro'] ) && is_array( $weergave['intro'] ) ? $weergave['intro'] : array();
    $b['weergave']  = array(
        'modus'           => isset( $weergave['modus'] ) && in_array( $weergave['modus'], array( 'alles', 'per-groep' ), true ) ? $weergave['modus'] : 'per-vraag',
        'stappen'         => isset( $weergave['stappen'] ) && is_array( $weergave['stappen'] ) ? array_values( array_map( 'strval', $weergave['stappen'] ) ) : array(),
        'schaal'          => isset( $weergave['schaal'] ) && is_array( $weergave['schaal'] ) ? $weergave['schaal'] : array(),
        'likert_als_tekst' => ! empty( $weergave['likert_als_tekst'] ),
        'vraagkop'        => isset( $weergave['vraagkop'] ) ? (string) $weergave['vraagkop'] : '',
        'intro'           => array(
            'tekst' => isset( $intro['tekst'] ) ? (string) $intro['tekst'] : '',
            'knop'  => isset( $intro['knop'] ) ? (string) $intro['knop'] : '',
        ),
    );

    $dimensies = array();
    if ( isset( $b['dimensies'] ) && is_array( $b['dimensies'] ) ) {
        foreach ( $b['dimensies'] as $id => $d ) {
            $id = (string) $id;
            if ( is_string( $d ) ) {
                $d = array( 'naam' => $d );
            }
            $d = is_array( $d ) ? $d : array();
            $dimensies[ $id ] = array(
                'naam'     => isset( $d['naam'] ) ? (string) $d['naam'] : $id,
                'volledig' => isset( $d['volledig'] ) ? (string) $d['volledig'] : ( isset( $d['naam'] ) ? (string) $d['naam'] : $id ),
                'uitleg'   => isset( $d['uitleg'] ) ? (string) $d['uitleg'] : '',
            );
        }
    }
    $b['dimensies'] = $dimensies;
    // De volgorde van de dimensies als lijst: in de browser zet een object
    // sleutels van alleen cijfers vooraan, in PHP blijft de volgorde staan.
    $b['dimensie_volgorde'] = array_map( 'strval', array_keys( $dimensies ) );
    $genegeerd              = array();

    $vragen = array();
    if ( isset( $b['vragen'] ) && is_array( $b['vragen'] ) ) {
        foreach ( $b['vragen'] as $i => $v ) {
            if ( ! is_array( $v ) ) {
                continue;
            }
            $v['id']   = isset( $v['id'] ) ? (string) $v['id'] : 'v' . ( $i + 1 );
            $v['type'] = isset( $v['type'] ) ? (string) $v['type'] : 'likert';
            if ( 'ab' === $v['type'] || 'janee' === $v['type'] ) {
                $v['type'] = 'keuze';
            }
            $v['tekst']     = isset( $v['tekst'] ) ? (string) $v['tekst'] : '';
            $v['groep']     = isset( $v['groep'] ) ? (string) $v['groep'] : '';
            $v['intro']     = isset( $v['intro'] ) ? (string) $v['intro'] : '';
            $v['omgekeerd'] = ! empty( $v['omgekeerd'] );
            $v['dimensie']  = isset( $v['dimensie'] ) ? (string) $v['dimensie'] : '';
            $v['optioneel'] = ! empty( $v['optioneel'] );
            $v['na_uitslag'] = ! empty( $v['na_uitslag'] );
            $opties = array();
            if ( isset( $v['opties'] ) && is_array( $v['opties'] ) ) {
                foreach ( $v['opties'] as $j => $o ) {
                    if ( is_string( $o ) ) {
                        $o = array( 'tekst' => $o );
                    }
                    $o = is_array( $o ) ? $o : array();
                    $punten = array();
                    if ( isset( $o['punten'] ) && is_array( $o['punten'] ) ) {
                        foreach ( $o['punten'] as $dim => $p ) {
                            $punten[ (string) $dim ] = (float) $p;
                        }
                    }
                    $opties[] = array(
                        'waarde' => isset( $o['waarde'] ) ? (string) $o['waarde'] : (string) ( $j + 1 ),
                        'tekst'  => isset( $o['tekst'] ) ? (string) $o['tekst'] : '',
                        'punten' => $punten,
                    );
                }
            }
            $v['opties'] = $opties;
            $vragen[]    = $v;
        }
    }
    $b['vragen'] = $vragen;

    $score      = isset( $b['score'] ) && is_array( $b['score'] ) ? $b['score'] : array();
    $b['score'] = array(
        'per_dimensie' => isset( $score['per_dimensie'] ) ? (string) $score['per_dimensie'] : 'som',
        'gewicht'      => isset( $score['gewicht'] ) && is_array( $score['gewicht'] ) ? array_map( 'floatval', $score['gewicht'] ) : array(),
    );

    $b['uitkomst'] = sfp_page_config_tool_uitkomst_schoon( isset( $b['uitkomst'] ) ? $b['uitkomst'] : array() );
    $b['labels']   = sfp_page_config_tool_labels_schoon( isset( $b['labels'] ) ? $b['labels'] : null );

    $uitslagen = array();
    if ( isset( $b['uitslagen'] ) && is_array( $b['uitslagen'] ) ) {
        foreach ( $b['uitslagen'] as $sleutel => $u ) {
            if ( is_string( $u ) ) {
                $u = array( 'tekst' => $u );
            }
            $u = is_array( $u ) ? $u : array();
            $lijsten = array();
            if ( isset( $u['lijsten'] ) && is_array( $u['lijsten'] ) ) {
                foreach ( $u['lijsten'] as $naam => $lijst ) {
                    $lijsten[ (string) $naam ] = sfp_page_config_tool_lijst_schoon( $lijst );
                }
            }
            $artikel = isset( $u['artikel'] ) && is_scalar( $u['artikel'] ) ? (string) $u['artikel'] : '';
            if ( '' !== trim( $artikel ) && '' === sfp_page_config_tool_url( $artikel ) ) {
                $genegeerd[] = 'Uitslag "' . $sleutel . '": de link bij artikel is geen gewoon webadres en wordt genegeerd.';
            }
            $uitslagen[ (string) $sleutel ] = array(
                'kop'     => isset( $u['kop'] ) ? (string) $u['kop'] : '',
                'titel'   => isset( $u['titel'] ) ? (string) $u['titel'] : '',
                'tekst'   => isset( $u['tekst'] ) ? (string) $u['tekst'] : '',
                'lijsten' => $lijsten,
                'artikel' => sfp_page_config_tool_url( $artikel ),
            );
        }
    }
    $b['uitslagen'] = $uitslagen;

    $uitvoer = array();
    if ( isset( $b['uitvoer'] ) && is_array( $b['uitvoer'] ) ) {
        foreach ( $b['uitvoer'] as $u ) {
            if ( is_string( $u ) ) {
                $u = array( 'type' => $u );
            }
            if ( is_array( $u ) && ! empty( $u['type'] ) && is_scalar( $u['type'] ) ) {
                $uitvoer[] = sfp_page_config_tool_uitvoer_schoon( $u );
            }
        }
    }
    if ( ! $uitvoer ) {
        $uitvoer = array( array( 'type' => 'profiel', 'kop' => '', 'bron' => '' ), array( 'type' => 'balken', 'kop' => '', 'bron' => '' ) );
    }
    $b['uitvoer'] = $uitvoer;

    $acties      = isset( $b['acties'] ) && is_array( $b['acties'] ) ? $b['acties'] : array();
    $b['acties'] = array(
        'pdf_bestandsnaam' => isset( $acties['pdf_bestandsnaam'] ) ? (string) $acties['pdf_bestandsnaam'] : '',
        'pdf'       => ! isset( $acties['pdf'] ) || ! empty( $acties['pdf'] ),
        'word'      => ! isset( $acties['word'] ) || ! empty( $acties['word'] ),
        'deellink'  => ! isset( $acties['deellink'] ) || ! empty( $acties['deellink'] ),
        'mail'      => ! isset( $acties['mail'] ) || ! empty( $acties['mail'] ),
        'duimpjes'  => ! isset( $acties['duimpjes'] ) || ! empty( $acties['duimpjes'] ),
        'formulier' => isset( $acties['formulier'] ) ? (int) $acties['formulier'] : 0,
    );

    $mail      = isset( $b['mail'] ) && is_array( $b['mail'] ) ? $b['mail'] : array();
    $b['mail'] = array(
        'onderwerp' => isset( $mail['onderwerp'] ) ? (string) $mail['onderwerp'] : '',
        'intro'     => isset( $mail['intro'] ) ? (string) $mail['intro'] : '',
    );

    $bron      = isset( $b['bron'] ) && is_array( $b['bron'] ) ? $b['bron'] : array();
    $link      = isset( $bron['link'] ) && is_scalar( $bron['link'] ) ? (string) $bron['link'] : '';
    if ( '' !== trim( $link ) && '' === sfp_page_config_tool_url( $link ) ) {
        $genegeerd[] = 'De link bij bron is geen gewoon webadres en wordt genegeerd.';
    }
    $b['bron'] = array(
        'model' => isset( $bron['model'] ) ? (string) $bron['model'] : '',
        'link'  => sfp_page_config_tool_url( $link ),
    );

    $b['labels_percentage'] = ! empty( $b['labels_percentage'] );

    $b['teksten'] = isset( $b['teksten'] ) && is_array( $b['teksten'] ) ? array_map( 'strval', $b['teksten'] ) : array();

    // Gedeelde lijsten, bereikbaar als bron lijsten.<naam>.
    $lijsten = array();
    if ( isset( $b['lijsten'] ) && is_array( $b['lijsten'] ) ) {
        foreach ( $b['lijsten'] as $naam => $lijst ) {
            $lijsten[ (string) $naam ] = sfp_page_config_tool_lijst_schoon( $lijst );
        }
    }
    $b['lijsten'] = $lijsten;

    // Wat bij het normaliseren is weggelaten; de controle meldt het, de
    // browser krijgt het niet (sfp_page_config_tool_voor_browser).
    $b['genegeerd'] = $genegeerd;

    return $b;
}

/**
 * De dimensies in hun volgorde, als tekst.
 *
 * @param  array $b Beschrijving (genormaliseerd).
 * @return string[]
 */
function sfp_page_config_tool_dims( array $b ) {
    return isset( $b['dimensie_volgorde'] ) ? $b['dimensie_volgorde'] : array_map( 'strval', array_keys( $b['dimensies'] ) );
}

/**
 * Een link uit de beschrijving: alleen http(s), een pad op de eigen site
 * of een anker. Al het andere (javascript:, data:) wordt leeg.
 *
 * @param  string $url Adres.
 * @return string
 */
function sfp_page_config_tool_url( $url ) {
    $url = str_replace( ' ', '%20', trim( preg_replace( '/[\x00-\x1f\x7f]+/', '', (string) $url ) ) );
    if ( preg_match( '#^https?://[a-z0-9\[]#i', $url ) || preg_match( '#^/(?![/\\\\])#', $url ) || 0 === strpos( $url, '#' ) ) {
        return $url;
    }
    return '';
}

/**
 * Een lijst in vaste vorm: elk item tekst, of een rij (lijst van teksten)
 * voor een tabel.
 *
 * @param  mixed $lijst Lijst of één item.
 * @return array
 */
function sfp_page_config_tool_lijst_schoon( $lijst ) {
    $lijst = is_array( $lijst ) ? array_values( $lijst ) : array( $lijst );
    $uit   = array();
    foreach ( $lijst as $item ) {
        if ( is_array( $item ) ) {
            $uit[] = array_values( array_map( 'sfp_page_config_tool_tekst_schoon', $item ) );
        } else {
            $uit[] = sfp_page_config_tool_tekst_schoon( $item );
        }
    }
    return $uit;
}

/**
 * Eén waarde als tekst; een lijst of object wordt leeg.
 *
 * @param  mixed $w Waarde.
 * @return string
 */
function sfp_page_config_tool_tekst_schoon( $w ) {
    if ( is_bool( $w ) ) {
        return $w ? 'true' : 'false';
    }
    return is_scalar( $w ) ? (string) $w : '';
}

/**
 * Een lijst van teksten (een losse tekst wordt een lijst van één).
 *
 * @param  mixed $w Waarde.
 * @return string[]
 */
function sfp_page_config_tool_teksten_schoon( $w ) {
    if ( null === $w ) {
        return array();
    }
    $w = is_array( $w ) ? array_values( $w ) : array( $w );
    return array_values( array_map( 'sfp_page_config_tool_tekst_schoon', array_filter( $w, 'is_scalar' ) ) );
}

/**
 * Een tabel van sleutel naar tekst (vertaal, paren, kwadranten).
 *
 * @param  mixed $w Waarde.
 * @return array<string, string>|null Null als het geen tabel is.
 */
function sfp_page_config_tool_tabel_schoon( $w ) {
    if ( ! is_array( $w ) ) {
        return null;
    }
    $uit = array();
    foreach ( $w as $k => $v ) {
        if ( is_scalar( $v ) && ! is_bool( $v ) ) {
            $uit[ (string) $k ] = (string) $v;
        }
    }
    return $uit;
}

/**
 * Een uitkomstregel in vaste vorm, ook een geneste `anders`. Wat ontbreekt
 * of null is, staat er daarna niet in; PHP en de browser lezen daardoor
 * hetzelfde.
 *
 * @param  mixed $u      Regel.
 * @param  int   $diepte Nesting (hoogstens vijf).
 * @return array
 */
function sfp_page_config_tool_uitkomst_schoon( $u, $diepte = 0 ) {
    $u   = is_array( $u ) ? $u : array();
    $uit = array( 'regel' => isset( $u['regel'] ) && is_scalar( $u['regel'] ) ? (string) $u['regel'] : 'hoogste' );
    if ( isset( $u['labels'] ) ) {
        $uit['labels'] = sfp_page_config_tool_labels_schoon( $u['labels'] );
    }
    if ( isset( $u['anders'] ) ) {
        if ( is_array( $u['anders'] ) ) {
            if ( $diepte < 5 ) {
                $uit['anders'] = sfp_page_config_tool_uitkomst_schoon( $u['anders'], $diepte + 1 );
            }
        } elseif ( is_scalar( $u['anders'] ) && ! is_bool( $u['anders'] ) ) {
            $uit['anders'] = (string) $u['anders'];
        }
    }
    foreach ( array( 'vertaal', 'paren', 'kwadranten' ) as $veld ) {
        if ( isset( $u[ $veld ] ) ) {
            $tabel = sfp_page_config_tool_tabel_schoon( $u[ $veld ] );
            if ( null !== $tabel ) {
                $uit[ $veld ] = $tabel;
            }
        }
    }
    foreach ( array( 'minimaal', 'minimale_spreiding' ) as $veld ) {
        if ( isset( $u[ $veld ] ) && is_numeric( $u[ $veld ] ) ) {
            $uit[ $veld ] = (float) $u[ $veld ];
        }
    }
    if ( isset( $u['aantal'] ) && is_numeric( $u['aantal'] ) ) {
        $uit['aantal'] = (int) $u['aantal'];
    }
    if ( ! empty( $u['percentage'] ) ) {
        $uit['percentage'] = true;
    }
    if ( isset( $u['nul'] ) ) {
        if ( is_array( $u['nul'] ) ) {
            $uit['nul'] = array();
            foreach ( array( 'x', 'y' ) as $as ) {
                if ( isset( $u['nul'][ $as ] ) && is_scalar( $u['nul'][ $as ] ) ) {
                    $uit['nul'][ $as ] = (string) $u['nul'][ $as ];
                }
            }
        } elseif ( is_scalar( $u['nul'] ) ) {
            $uit['nul'] = (string) $u['nul'];
        }
    }
    if ( isset( $u['assen'] ) && is_array( $u['assen'] ) ) {
        $uit['assen'] = array();
        foreach ( array( 'x', 'y' ) as $as ) {
            if ( ! isset( $u['assen'][ $as ] ) || ! is_array( $u['assen'][ $as ] ) ) {
                continue;
            }
            $def                 = $u['assen'][ $as ];
            $uit['assen'][ $as ] = array(
                'plus' => sfp_page_config_tool_teksten_schoon( isset( $def['plus'] ) ? $def['plus'] : null ),
                'min'  => sfp_page_config_tool_teksten_schoon( isset( $def['min'] ) ? $def['min'] : null ),
            );
            if ( isset( $def['naam'] ) && is_scalar( $def['naam'] ) ) {
                $uit['assen'][ $as ]['naam'] = (string) $def['naam'];
            }
        }
    }
    if ( isset( $u['regels'] ) && is_array( $u['regels'] ) ) {
        $uit['regels'] = array();
        foreach ( array_values( $u['regels'] ) as $r ) {
            $r   = is_array( $r ) ? $r : array();
            $als = array();
            foreach ( isset( $r['als'] ) && is_array( $r['als'] ) ? array_values( $r['als'] ) : array() as $vw ) {
                $vw = is_array( $vw ) ? array_values( $vw ) : array();
                if ( 3 === count( $vw ) && is_scalar( $vw[0] ) && is_scalar( $vw[1] ) && is_scalar( $vw[2] ) && ! is_bool( $vw[2] ) ) {
                    $als[] = array( (string) $vw[0], (string) $vw[1], $vw[2] );
                } else {
                    $als[] = null;
                }
            }
            $uit['regels'][] = array(
                'als' => $als,
                'dan' => isset( $r['dan'] ) && is_scalar( $r['dan'] ) ? (string) $r['dan'] : '',
            );
        }
    }
    if ( isset( $u['gelijkspel_tekst'] ) && is_scalar( $u['gelijkspel_tekst'] ) ) {
        $uit['gelijkspel_tekst'] = (string) $u['gelijkspel_tekst'];
    }
    return $uit;
}

/**
 * Eén uitvoeronderdeel in vaste vorm.
 *
 * @param  array $u Onderdeel.
 * @return array
 */
function sfp_page_config_tool_uitvoer_schoon( array $u ) {
    $uit = array(
        'type' => (string) $u['type'],
        'kop'  => isset( $u['kop'] ) && is_scalar( $u['kop'] ) ? (string) $u['kop'] : '',
        'bron' => isset( $u['bron'] ) && is_scalar( $u['bron'] ) ? (string) $u['bron'] : '',
    );
    foreach ( array( 'tekst', 'selectie', 'markeer', 'leeg', 'sorteer', 'schaal' ) as $veld ) {
        if ( isset( $u[ $veld ] ) && is_scalar( $u[ $veld ] ) ) {
            $uit[ $veld ] = (string) $u[ $veld ];
        }
    }
    if ( isset( $u['aantal'] ) && is_numeric( $u['aantal'] ) ) {
        $uit['aantal'] = (int) $u['aantal'];
    }
    // Bij `alle`: dimensies met de topscore vallen weg (sinds 3.8.1).
    if ( ! empty( $u['zonder_hoogste'] ) ) {
        $uit['zonder_hoogste'] = true;
    }
    // Bij `vast` een lijst items, bij `laagste` de naam van een lijst.
    if ( isset( $u['lijst'] ) ) {
        $uit['lijst'] = 'vast' === $uit['type'] ? sfp_page_config_tool_teksten_schoon( $u['lijst'] ) : sfp_page_config_tool_tekst_schoon( $u['lijst'] );
    }
    foreach ( array( 'lijsten', 'koppen' ) as $veld ) {
        if ( isset( $u[ $veld ] ) ) {
            $uit[ $veld ] = sfp_page_config_tool_teksten_schoon( $u[ $veld ] );
        }
    }
    return $uit;
}

/**
 * Een lijst labels in vaste vorm, hoogste grens eerst.
 *
 * @param  mixed $ruw Lijst.
 * @return array
 */
function sfp_page_config_tool_labels_schoon( $ruw ) {
    $labels = array();
    if ( is_array( $ruw ) ) {
        foreach ( $ruw as $l ) {
            if ( ! is_array( $l ) ) {
                continue;
            }
            $tekst    = isset( $l['tekst'] ) ? (string) $l['tekst'] : '';
            $labels[] = array(
                'vanaf'   => isset( $l['vanaf'] ) ? (float) $l['vanaf'] : 0,
                'tekst'   => $tekst,
                'sleutel' => isset( $l['sleutel'] ) ? (string) $l['sleutel'] : $tekst,
            );
        }
        usort(
            $labels,
            function ( $a, $c ) {
                return $c['vanaf'] <=> $a['vanaf'];
            }
        );
    }
    return $labels;
}

/* =========================================================================
 * Controleren
 * ====================================================================== */

/**
 * Controleer een beschrijving als tekst (JSON).
 *
 * @param  string $tekst JSON.
 * @return array{fouten: string[], let_op: string[], beschrijving: array|null, samenvatting: array}
 */
function sfp_page_config_tool_controleer_tekst( $tekst ) {
    $data = json_decode( (string) $tekst, true );
    if ( ! is_array( $data ) ) {
        $fout = function_exists( 'json_last_error_msg' ) ? json_last_error_msg() : '';
        return array(
            'fouten'       => array( 'De beschrijving is geen geldige JSON' . ( '' !== $fout ? ' (' . $fout . ')' : '' ) . '.' ),
            'let_op'       => array(),
            'beschrijving' => null,
            'samenvatting' => array(),
        );
    }
    $b        = sfp_page_config_tool_normaliseer( $data );
    $uitkomst = sfp_page_config_tool_controleer( $b );
    $uitkomst['beschrijving'] = $b;
    $uitkomst['samenvatting'] = sfp_page_config_tool_samenvatting( $b );
    return $uitkomst;
}

/**
 * Controleer een genormaliseerde beschrijving. Fouten maken de tool
 * onbruikbaar; let-op-punten niet.
 *
 * @param  array $b Beschrijving.
 * @return array{fouten: string[], let_op: string[]}
 */
function sfp_page_config_tool_controleer( array $b ) {
    $fouten = array();
    $let_op = array();
    $soorten = sfp_page_config_tool_soorten();
    if ( ! isset( $soorten[ $b['soort'] ] ) ) {
        $fouten[] = 'Onbekende soort "' . $b['soort'] . '". Bekend: ' . implode( ', ', array_keys( $soorten ) ) . '.';
        return compact( 'fouten', 'let_op' );
    }
    if ( ! $b['dimensies'] ) {
        $fouten[] = 'Geen dimensies.';
    }
    if ( ! $b['vragen'] ) {
        $fouten[] = 'Geen vragen.';
    }
    $ids     = array();
    $scorend = 0;
    foreach ( $b['vragen'] as $v ) {
        if ( isset( $ids[ $v['id'] ] ) ) {
            $fouten[] = 'Vraag "' . $v['id'] . '" komt twee keer voor.';
        }
        $ids[ $v['id'] ] = true;
        if ( ! in_array( $v['type'], array( 'likert', 'keuze', 'context', 'tekst' ), true ) ) {
            $fouten[] = 'Vraag "' . $v['id'] . '": onbekend type "' . $v['type'] . '".';
            continue;
        }
        if ( '' === $v['tekst'] && 'tekst' !== $v['type'] ) {
            $fouten[] = 'Vraag "' . $v['id'] . '" heeft geen tekst.';
        }
        if ( 'likert' === $v['type'] ) {
            $scorend++;
            if ( '' === $v['dimensie'] ) {
                $fouten[] = 'Vraag "' . $v['id'] . '" (likert) hoort bij geen dimensie.';
            } elseif ( ! isset( $b['dimensies'][ $v['dimensie'] ] ) ) {
                $fouten[] = 'Vraag "' . $v['id'] . '" verwijst naar onbekende dimensie "' . $v['dimensie'] . '".';
            }
        }
        if ( 'keuze' === $v['type'] || 'context' === $v['type'] ) {
            if ( count( $v['opties'] ) < 2 ) {
                $fouten[] = 'Vraag "' . $v['id'] . '" heeft minder dan twee opties.';
            }
            $waarden = array();
            foreach ( $v['opties'] as $o ) {
                if ( isset( $waarden[ $o['waarde'] ] ) ) {
                    $fouten[] = 'Vraag "' . $v['id'] . '": optiewaarde "' . $o['waarde'] . '" komt twee keer voor.';
                }
                $waarden[ $o['waarde'] ] = true;
                if ( '' === $o['tekst'] ) {
                    $fouten[] = 'Vraag "' . $v['id'] . '": een optie heeft geen tekst.';
                }
                foreach ( $o['punten'] as $dim => $p ) {
                    if ( ! isset( $b['dimensies'][ $dim ] ) ) {
                        $fouten[] = 'Vraag "' . $v['id'] . '": punten voor onbekende dimensie "' . $dim . '".';
                    }
                }
                if ( 'keuze' === $v['type'] && $o['punten'] ) {
                    $scorend++;
                }
            }
            if ( 'context' === $v['type'] ) {
                foreach ( $v['opties'] as $o ) {
                    if ( $o['punten'] ) {
                        $let_op[] = 'Vraag "' . $v['id'] . '" is context en scoort niet; de punten bij de opties doen niets.';
                        break;
                    }
                }
            }
        }
    }
    if ( $b['vragen'] && 0 === $scorend ) {
        $fouten[] = 'Geen enkele vraag scoort.';
    }
    // Scoreregels die een soort vraag niet meetellen.
    $likert = 0;
    $punten = 0;
    foreach ( $b['vragen'] as $v ) {
        if ( 'likert' === $v['type'] ) {
            $likert++;
        } elseif ( 'keuze' === $v['type'] ) {
            foreach ( $v['opties'] as $o ) {
                if ( $o['punten'] ) {
                    $punten++;
                    break;
                }
            }
        }
    }
    if ( 'gecentreerd' === $b['score']['per_dimensie'] && $punten ) {
        $let_op[] = 'De scoreregel "gecentreerd" telt alleen Likert-vragen; de punten bij ' . $punten . ' keuzevraag of -vragen tellen niet mee.';
    }
    if ( 'telling' === $b['score']['per_dimensie'] && $likert ) {
        $let_op[] = 'De scoreregel "telling" telt alleen gekozen opties met punten; ' . $likert . ' Likert-vraag of -vragen tellen niet mee.';
    }
    foreach ( isset( $b['genegeerd'] ) ? $b['genegeerd'] : array() as $melding ) {
        $let_op[] = $melding;
    }
    if ( ! in_array( $b['score']['per_dimensie'], array( 'som', 'telling', 'percentage', 'gecentreerd' ), true ) ) {
        $fouten[] = 'Onbekende scoreregel "' . $b['score']['per_dimensie'] . '".';
    }
    $regel = $b['uitkomst']['regel'];
    if ( ! in_array( $regel, array( 'hoogste', 'top', 'drempels', 'totaal', 'profiel', 'paar', 'kwadrant', 'regels' ), true ) ) {
        $fouten[] = 'Onbekende uitkomstregel "' . $regel . '".';
    }
    if ( in_array( $regel, array( 'drempels', 'totaal' ), true ) && ! $b['labels'] && empty( $b['uitkomst']['labels'] ) ) {
        $fouten[] = 'De uitkomstregel "' . $regel . '" heeft labels nodig.';
    }
    if ( 'top' === $regel && ( ! isset( $b['uitkomst']['aantal'] ) || (int) $b['uitkomst']['aantal'] < 1 ) ) {
        $fouten[] = 'De uitkomstregel "top" heeft een aantal nodig.';
    }
    if ( 'kwadrant' === $regel ) {
        $assen = isset( $b['uitkomst']['assen'] ) && is_array( $b['uitkomst']['assen'] ) ? $b['uitkomst']['assen'] : array();
        foreach ( array( 'x', 'y' ) as $as ) {
            if ( empty( $assen[ $as ]['plus'] ) && empty( $assen[ $as ]['min'] ) ) {
                $fouten[] = 'De uitkomstregel "kwadrant" heeft voor as ' . $as . ' plus of min nodig.';
                continue;
            }
            foreach ( array_merge( isset( $assen[ $as ]['plus'] ) ? (array) $assen[ $as ]['plus'] : array(), isset( $assen[ $as ]['min'] ) ? (array) $assen[ $as ]['min'] : array() ) as $dim ) {
                if ( ! isset( $b['dimensies'][ $dim ] ) ) {
                    $fouten[] = 'As ' . $as . ' verwijst naar onbekende dimensie "' . $dim . '".';
                }
            }
        }
        $kw = isset( $b['uitkomst']['kwadranten'] ) && is_array( $b['uitkomst']['kwadranten'] ) ? $b['uitkomst']['kwadranten'] : array();
        foreach ( array( 'x+y+', 'x+y-', 'x-y+', 'x-y-' ) as $k ) {
            if ( empty( $kw[ $k ] ) ) {
                $fouten[] = 'Kwadrant "' . $k . '" heeft geen uitslag.';
            }
        }
    }
    if ( in_array( $regel, array( 'regels', 'profiel' ), true ) ) {
        $regels = isset( $b['uitkomst']['regels'] ) && is_array( $b['uitkomst']['regels'] ) ? $b['uitkomst']['regels'] : array();
        if ( ! $regels ) {
            $fouten[] = 'De uitkomstregel "' . $regel . '" heeft regels nodig.';
        }
        foreach ( $regels as $i => $r ) {
            if ( empty( $r['dan'] ) ) {
                $fouten[] = 'Regel ' . ( $i + 1 ) . ' heeft geen "dan".';
            }
            foreach ( isset( $r['als'] ) && is_array( $r['als'] ) ? $r['als'] : array() as $voorwaarde ) {
                if ( ! is_array( $voorwaarde ) || 3 !== count( $voorwaarde ) || ! in_array( $voorwaarde[1], array( '>=', '>', '<=', '<', '==' ), true ) ) {
                    $fouten[] = 'Regel ' . ( $i + 1 ) . ': een voorwaarde is niet [links, operator, waarde].';
                }
            }
        }
        if ( empty( $b['uitkomst']['anders'] ) ) {
            $let_op[] = 'De regels hebben geen vangnet ("anders"): zonder treffer is er geen uitslag.';
        }
    }

    // Elke mogelijke uitkomst heeft een tekst.
    foreach ( sfp_page_config_tool_mogelijke_uitslagen( $b ) as $sleutel ) {
        if ( ! isset( $b['uitslagen'][ $sleutel ] ) ) {
            $let_op[] = 'Uitslag "' . $sleutel . '" heeft geen tekst in uitslagen.';
        }
    }
    foreach ( $b['uitvoer'] as $u ) {
        if ( ! in_array( $u['type'], array( 'profiel', 'balken', 'lijst', 'tekst', 'onderbouwing', 'afvinklijst', 'laagste', 'niveaus', 'tabel', 'link', 'vast', 'alle', 'totaal' ), true ) ) {
            $fouten[] = 'Onbekend uitvoeronderdeel "' . $u['type'] . '".';
        }
        if ( in_array( $u['type'], array( 'lijst', 'tekst', 'afvinklijst', 'tabel' ), true ) && '' === $u['bron'] ) {
            $fouten[] = 'Uitvoeronderdeel "' . $u['type'] . '" heeft geen bron.';
        }
    }
    if ( $b['acties']['formulier'] > 0 && ! shortcode_exists( 'sureforms' ) ) {
        $let_op[] = 'Er is een formulier gekozen, maar SureForms staat niet op deze site.';
    }
    if ( $b['acties']['mail'] && '' === $b['mail']['onderwerp'] ) {
        $let_op[] = 'Mail staat aan, maar er is geen onderwerp; de titel van de tool wordt gebruikt.';
    }
    return compact( 'fouten', 'let_op' );
}

/**
 * De sleutels in `uitslagen` die de uitkomstregel kan opleveren.
 *
 * @param  array $b Beschrijving.
 * @return string[]
 */
function sfp_page_config_tool_mogelijke_uitslagen( array $b ) {
    $uit = sfp_page_config_tool_mogelijke_sleutels( $b, $b['uitkomst'] );
    if ( ! empty( $b['uitkomst']['vertaal'] ) && is_array( $b['uitkomst']['vertaal'] ) ) {
        $uit = array_map(
            function ( $k ) use ( $b ) {
                return isset( $b['uitkomst']['vertaal'][ $k ] ) ? (string) $b['uitkomst']['vertaal'][ $k ] : $k;
            },
            $uit
        );
    }
    return array_values( array_unique( $uit ) );
}

/**
 * De sleutels die één uitkomstregel kan opleveren (zonder vertaling).
 *
 * @param  array $b        Beschrijving.
 * @param  array $uitkomst Uitkomstregel (ook een geneste `anders`).
 * @return string[]
 */
function sfp_page_config_tool_mogelijke_sleutels( array $b, array $uitkomst ) {
    $dims   = sfp_page_config_tool_dims( $b );
    $labels = isset( $uitkomst['labels'] ) && is_array( $uitkomst['labels'] ) ? $uitkomst['labels'] : $b['labels'];
    $regel  = isset( $uitkomst['regel'] ) ? (string) $uitkomst['regel'] : 'hoogste';
    $extra  = array();
    if ( isset( $uitkomst['anders'] ) && is_array( $uitkomst['anders'] ) ) {
        $extra = sfp_page_config_tool_mogelijke_sleutels( $b, $uitkomst['anders'] );
    } elseif ( ! empty( $uitkomst['anders'] ) ) {
        $extra = array( (string) $uitkomst['anders'] );
    }
    if ( ! empty( $uitkomst['vertaal'] ) && is_array( $uitkomst['vertaal'] ) ) {
        $extra = array_merge( $extra, array_map( 'strval', array_values( $uitkomst['vertaal'] ) ) );
    }
    switch ( $regel ) {
        case 'hoogste':
        case 'top':
            return array_merge( $dims, $extra );
        case 'drempels':
            $uit = array();
            foreach ( $dims as $d ) {
                foreach ( $labels as $l ) {
                    $uit[] = $d . ':' . $l['sleutel'];
                }
            }
            return array_merge( $uit, $extra );
        case 'totaal':
            return array_merge(
                array_map(
                    function ( $l ) {
                        return $l['sleutel'];
                    },
                    $labels
                ),
                $extra
            );
        case 'paar':
            // Met een eigen tabel paren kan alleen wat daarin staat.
            if ( isset( $uitkomst['paren'] ) && is_array( $uitkomst['paren'] ) ) {
                return array_merge( array_values( array_map( 'strval', $uitkomst['paren'] ) ), $extra );
            }
            $uit = array();
            $n   = count( $dims );
            for ( $i = 0; $i < $n; $i++ ) {
                for ( $j = $i + 1; $j < $n; $j++ ) {
                    $uit[] = $dims[ $i ] . '+' . $dims[ $j ];
                }
            }
            return array_merge( $uit, $extra );
        case 'kwadrant':
            $kw = isset( $uitkomst['kwadranten'] ) && is_array( $uitkomst['kwadranten'] ) ? $uitkomst['kwadranten'] : array();
            return array_merge( array_values( array_unique( array_map( 'strval', $kw ) ) ), $extra );
        case 'regels':
        case 'profiel':
            $uit = array();
            foreach ( isset( $uitkomst['regels'] ) && is_array( $uitkomst['regels'] ) ? $uitkomst['regels'] : array() as $r ) {
                if ( ! empty( $r['dan'] ) ) {
                    $uit[] = (string) $r['dan'];
                }
            }
            return array_merge( $uit, $extra );
    }
    return $extra;
}

/**
 * Een korte samenvatting voor het paneel en het beheer.
 *
 * @param  array $b Beschrijving.
 * @return array
 */
function sfp_page_config_tool_samenvatting( array $b ) {
    $scorend = 0;
    foreach ( $b['vragen'] as $v ) {
        if ( 'likert' === $v['type'] || 'keuze' === $v['type'] ) {
            $scorend++;
        }
    }
    return array(
        'soort'     => $b['soort'],
        'vragen'    => count( $b['vragen'] ),
        'scorend'   => $scorend,
        'dimensies' => array_values(
            array_map(
                function ( $d ) {
                    return $d['naam'];
                },
                $b['dimensies']
            )
        ),
        'regel'     => $b['uitkomst']['regel'],
        'acties'    => array_keys( array_filter( $b['acties'] ) ),
        'formulier' => $b['acties']['formulier'],
    );
}

/* =========================================================================
 * Rekenen (soort test). Gelijk aan assets/tool/test.js.
 * ====================================================================== */

/**
 * Reken de uitslag van een test uit.
 *
 * @param  array $b          Beschrijving (genormaliseerd).
 * @param  array $antwoorden id => waarde (likert: 1 tot 5 als getal; keuze
 *                           en context: de optiewaarde; tekst: de tekst).
 * @return array{
 *   compleet: bool, ontbreekt: string[], dimensies: array, volgorde: string[],
 *   uitslag: string, top: string[], niveaus: array, totaal: float, totaal_max: float,
 *   gemiddelde: float, spreiding: float, assen: array, bijdragen: array, context: array, teksten: array
 * }
 */
function sfp_page_config_tool_reken( array $b, array $antwoorden ) {
    $dims    = sfp_page_config_tool_dims( $b );
    $som     = array_fill_keys( $dims, 0.0 );
    $n       = array_fill_keys( $dims, 0 );
    $maxsom  = array_fill_keys( $dims, 0.0 );
    $telling = array_fill_keys( $dims, 0 );
    $telmax  = array_fill_keys( $dims, 0 );
    $cent    = array_fill_keys( $dims, 0.0 );
    $gewicht = $b['score']['gewicht'];
    $bijdragen = array();
    $context   = array();
    $teksten   = array();
    $ontbreekt = array();

    foreach ( $b['vragen'] as $v ) {
        $id = $v['id'];
        $w  = array_key_exists( $id, $antwoorden ) ? $antwoorden[ $id ] : null;
        if ( 'tekst' === $v['type'] ) {
            $teksten[ $id ] = null === sfp_page_config_tool_antwoord( $w ) ? '' : trim( (string) $w );
            continue;
        }
        $w = sfp_page_config_tool_antwoord( $w );
        if ( 'context' === $v['type'] ) {
            $context[ $id ] = '';
            foreach ( $v['opties'] as $o ) {
                if ( null !== $w && $w === $o['waarde'] ) {
                    $context[ $id ] = $o['tekst'];
                }
            }
            if ( '' === $context[ $id ] && ! $v['optioneel'] ) {
                $ontbreekt[] = $id;
            }
            continue;
        }
        if ( 'likert' === $v['type'] ) {
            $dim = $v['dimensie'];
            if ( ! isset( $som[ $dim ] ) ) {
                continue;
            }
            $g = isset( $gewicht[ $dim ] ) ? (float) $gewicht[ $dim ] : 1.0;
            $n[ $dim ]++;
            $maxsom[ $dim ] += 5;
            // Alleen 1 tot 5; een waarde uit een deellink kan van alles zijn.
            if ( null === $w || ! preg_match( '/^[1-5]$/', $w ) ) {
                $ontbreekt[] = $id;
                continue;
            }
            $waarde = (int) $w;
            if ( $v['omgekeerd'] ) {
                $waarde = 6 - $waarde;
            }
            $som[ $dim ]  += $waarde;
            $cent[ $dim ] += ( $waarde - 3 ) * $g;
            $bijdragen[ $id ] = array( $dim => (float) $waarde );
            continue;
        }
        // keuze
        $perdim = array();
        foreach ( $v['opties'] as $o ) {
            foreach ( $o['punten'] as $dim => $p ) {
                if ( ! isset( $perdim[ $dim ] ) || $p > $perdim[ $dim ] ) {
                    $perdim[ $dim ] = $p;
                }
            }
        }
        foreach ( $perdim as $dim => $p ) {
            if ( isset( $maxsom[ $dim ] ) ) {
                $maxsom[ $dim ] += $p;
                $telmax[ $dim ]++;
            }
        }
        $gekozen = null;
        foreach ( $v['opties'] as $o ) {
            if ( null !== $w && $w === $o['waarde'] ) {
                $gekozen = $o;
            }
        }
        if ( null === $gekozen ) {
            if ( ! $v['optioneel'] ) {
                $ontbreekt[] = $id;
            }
            continue;
        }
        $bijdragen[ $id ] = array();
        foreach ( $gekozen['punten'] as $dim => $p ) {
            if ( ! isset( $som[ $dim ] ) ) {
                continue;
            }
            $som[ $dim ] += $p;
            if ( $p > 0 ) {
                $telling[ $dim ]++;
            }
            $bijdragen[ $id ][ $dim ] = (float) $p;
        }
    }

    // Waarde per dimensie.
    $waarden = array();
    $maxima  = array();
    $minima  = array();
    foreach ( $dims as $d ) {
        switch ( $b['score']['per_dimensie'] ) {
            case 'telling':
                $waarden[ $d ] = (float) $telling[ $d ];
                $maxima[ $d ]  = (float) $telmax[ $d ];
                $minima[ $d ]  = 0.0;
                break;
            case 'percentage':
                if ( $n[ $d ] > 0 ) {
                    $pct = ( $som[ $d ] - $n[ $d ] ) / ( $n[ $d ] * 4 ) * 100;
                } else {
                    $pct = $maxsom[ $d ] > 0 ? $som[ $d ] / $maxsom[ $d ] * 100 : 0;
                }
                $waarden[ $d ] = (float) max( 0, min( 100, sfp_page_config_tool_rond( $pct ) ) );
                $maxima[ $d ]  = 100.0;
                $minima[ $d ]  = 0.0;
                break;
            case 'gecentreerd':
                $g             = isset( $gewicht[ $d ] ) ? (float) $gewicht[ $d ] : 1.0;
                $waarden[ $d ] = (float) $cent[ $d ];
                $maxima[ $d ]  = (float) ( 2 * $n[ $d ] * $g );
                $minima[ $d ]  = -$maxima[ $d ];
                break;
            default:
                $waarden[ $d ] = (float) $som[ $d ];
                $maxima[ $d ]  = (float) $maxsom[ $d ];
                $minima[ $d ]  = 0.0;
        }
    }
    $volgorde = $dims;
    usort(
        $volgorde,
        function ( $a, $c ) use ( $waarden, $dims ) {
            if ( $waarden[ $a ] === $waarden[ $c ] ) {
                return array_search( $a, $dims, true ) <=> array_search( $c, $dims, true );
            }
            return $waarden[ $c ] <=> $waarden[ $a ];
        }
    );
    $totaal     = array_sum( $waarden );
    $totaal_max = array_sum( $maxima );
    $aantal     = max( 1, count( $dims ) );
    $gemiddelde = $totaal / $aantal;
    $spreiding  = $dims ? max( $waarden ) - min( $waarden ) : 0.0;

    $dimensies = array();
    foreach ( $dims as $d ) {
        $dimensies[ $d ] = array(
            'waarde' => $waarden[ $d ],
            'max'    => $maxima[ $d ],
            'min'    => $minima[ $d ],
            'label'  => sfp_page_config_tool_label( $b, $waarden[ $d ], $maxima[ $d ] ),
        );
    }

    $stand = array(
        'waarden'    => $waarden,
        'volgorde'   => $volgorde,
        'totaal'     => $totaal,
        'totaal_max' => $totaal_max,
        'gemiddelde' => $gemiddelde,
        'spreiding'  => $spreiding,
        'antwoorden' => $antwoorden,
        'dimensies'  => $dimensies,
    );
    $u       = sfp_page_config_tool_uitkomst( $b, $b['uitkomst'], $stand );
    $uitslag = $u['uitslag'];
    $top     = $u['top'];
    $niveaus = $u['niveaus'];
    $assen   = $u['assen'];
    $gelijk  = count( $volgorde ) > 1 && $waarden[ $volgorde[0] ] === $waarden[ $volgorde[1] ];

    return array(
        'compleet'   => ! $ontbreekt,
        'ontbreekt'  => $ontbreekt,
        'dimensies'  => $dimensies,
        'volgorde'   => $volgorde,
        'uitslag'    => $uitslag,
        'top'        => $top,
        'niveaus'    => $niveaus,
        'totaal'     => $totaal,
        'totaal_max' => $totaal_max,
        'gemiddelde' => $gemiddelde,
        'spreiding'  => $spreiding,
        'assen'      => $assen,
        'gelijkspel' => $gelijk,
        'bijdragen'  => $bijdragen,
        'context'    => $context,
        'teksten'    => $teksten,
    );
}

/**
 * Pas een uitkomstregel toe. `anders` mag een sleutel zijn of een geneste
 * regel (bijvoorbeeld paar, en daaronder weer hoogste). `vertaal` zet de
 * gevonden sleutel om naar een sleutel in uitslagen. `minimaal` (laagste
 * topscore) en `minimale_spreiding` sturen naar `anders` als er geen
 * duidelijke uitslag is.
 *
 * @param  array $b        Beschrijving.
 * @param  array $uitkomst Regel.
 * @param  array $stand    Waarden, volgorde, totaal, totaal_max, gemiddelde, spreiding, antwoorden, dimensies.
 * @return array{uitslag: string, top: string[], niveaus: array, assen: array}
 */
function sfp_page_config_tool_uitkomst( array $b, array $uitkomst, array $stand ) {
    $dims     = sfp_page_config_tool_dims( $b );
    $waarden  = $stand['waarden'];
    $volgorde = $stand['volgorde'];
    $regel    = isset( $uitkomst['regel'] ) ? (string) $uitkomst['regel'] : 'hoogste';
    $labels   = isset( $uitkomst['labels'] ) && is_array( $uitkomst['labels'] ) && $uitkomst['labels'] ? $uitkomst['labels'] : $b['labels'];
    $uit      = array( 'uitslag' => '', 'top' => array_slice( $volgorde, 0, 1 ), 'niveaus' => array(), 'assen' => array() );
    $anders   = function () use ( $b, $uitkomst, $stand, $uit ) {
        if ( isset( $uitkomst['anders'] ) && is_array( $uitkomst['anders'] ) ) {
            return sfp_page_config_tool_uitkomst( $b, $uitkomst['anders'], $stand );
        }
        $uit['uitslag'] = isset( $uitkomst['anders'] ) ? (string) $uitkomst['anders'] : '';
        return $uit;
    };
    $klaar = function ( $sleutel, $uit ) use ( $uitkomst ) {
        if ( ! empty( $uitkomst['vertaal'] ) && is_array( $uitkomst['vertaal'] ) && isset( $uitkomst['vertaal'][ $sleutel ] ) ) {
            $sleutel = (string) $uitkomst['vertaal'][ $sleutel ];
        }
        $uit['uitslag'] = $sleutel;
        return $uit;
    };
    if ( ! $volgorde ) {
        return $anders();
    }
    if ( isset( $uitkomst['minimaal'] ) && $waarden[ $volgorde[0] ] < (float) $uitkomst['minimaal'] ) {
        return $anders();
    }
    if ( isset( $uitkomst['minimale_spreiding'] ) && $stand['spreiding'] <= (float) $uitkomst['minimale_spreiding'] ) {
        return $anders();
    }
    switch ( $regel ) {
        case 'hoogste':
            return $klaar( $volgorde[0], $uit );
        case 'top':
            $k          = max( 1, (int) ( isset( $uitkomst['aantal'] ) ? $uitkomst['aantal'] : 1 ) );
            $uit['top'] = array_slice( $volgorde, 0, $k );
            return $klaar( $volgorde[0], $uit );
        case 'drempels':
            foreach ( $dims as $d ) {
                $uit['niveaus'][ $d ] = sfp_page_config_tool_label( $b, $waarden[ $d ], $stand['dimensies'][ $d ]['max'], false, $labels )['sleutel'];
            }
            return $klaar( $volgorde[0], $uit );
        case 'totaal':
            $pct    = ! empty( $uitkomst['percentage'] );
            $waarde = $pct ? ( $stand['totaal_max'] > 0 ? $stand['totaal'] / $stand['totaal_max'] * 100 : 0 ) : $stand['totaal'];
            $label  = sfp_page_config_tool_label( $b, $waarde, $pct ? 100 : $stand['totaal_max'], false, $labels );
            return $klaar( $label['sleutel'], $uit );
        case 'paar':
            if ( count( $volgorde ) < 2 ) {
                return $anders();
            }
            $twee = array_slice( $volgorde, 0, 2 );
            usort(
                $twee,
                function ( $a, $c ) use ( $dims ) {
                    return array_search( $a, $dims, true ) <=> array_search( $c, $dims, true );
                }
            );
            $uit['top'] = array_slice( $volgorde, 0, 2 );
            $sleutel    = implode( '+', $twee );
            $paren      = isset( $uitkomst['paren'] ) && is_array( $uitkomst['paren'] ) ? $uitkomst['paren'] : null;
            if ( null !== $paren ) {
                return isset( $paren[ $sleutel ] ) ? $klaar( (string) $paren[ $sleutel ], $uit ) : $anders();
            }
            if ( ! isset( $b['uitslagen'][ $sleutel ] ) && ( isset( $uitkomst['anders'] ) ) ) {
                return $anders();
            }
            return $klaar( $sleutel, $uit );
        case 'kwadrant':
            $sleutel = '';
            foreach ( array( 'x', 'y' ) as $as ) {
                $def  = isset( $uitkomst['assen'][ $as ] ) && is_array( $uitkomst['assen'][ $as ] ) ? $uitkomst['assen'][ $as ] : array();
                $plus = 0.0;
                $min  = 0.0;
                foreach ( isset( $def['plus'] ) ? (array) $def['plus'] : array() as $d ) {
                    $plus += isset( $waarden[ $d ] ) ? $waarden[ $d ] : 0;
                }
                foreach ( isset( $def['min'] ) ? (array) $def['min'] : array() as $d ) {
                    $min += isset( $waarden[ $d ] ) ? $waarden[ $d ] : 0;
                }
                $w   = $plus - $min;
                $nul = isset( $uitkomst['nul'] ) ? $uitkomst['nul'] : '+';
                $nul = is_array( $nul ) ? ( isset( $nul[ $as ] ) ? $nul[ $as ] : '+' ) : $nul;
                $teken = $w > 0 ? '+' : ( $w < 0 ? '-' : ( '-' === $nul ? '-' : '+' ) );
                $uit['assen'][ $as ] = array(
                    'waarde' => $w,
                    'naam'   => isset( $def['naam'] ) ? (string) $def['naam'] : $as,
                    'teken'  => $teken,
                );
                $sleutel .= $as . $teken;
            }
            $kw = isset( $uitkomst['kwadranten'] ) && is_array( $uitkomst['kwadranten'] ) ? $uitkomst['kwadranten'] : array();
            return isset( $kw[ $sleutel ] ) ? $klaar( (string) $kw[ $sleutel ], $uit ) : $anders();
        case 'regels':
        case 'profiel':
            $lijst = array(
                'totaal'     => $stand['totaal'],
                'gemiddelde' => $stand['gemiddelde'],
                'spreiding'  => $stand['spreiding'],
            );
            foreach ( $dims as $d ) {
                $lijst[ 'dimensie.' . $d ] = $waarden[ $d ];
            }
            // Het antwoord zoals het is: "1.0" blijft "1.0", ook bij ==. Vrije
            // tekst telt niet mee (die gaat niet mee in de mail).
            foreach ( $b['vragen'] as $v ) {
                $w = array_key_exists( $v['id'], $stand['antwoorden'] ) ? sfp_page_config_tool_antwoord( $stand['antwoorden'][ $v['id'] ] ) : null;
                if ( 'tekst' !== $v['type'] && null !== $w ) {
                    $lijst[ 'vraag.' . $v['id'] ] = $w;
                }
            }
            foreach ( isset( $uitkomst['regels'] ) && is_array( $uitkomst['regels'] ) ? $uitkomst['regels'] : array() as $r ) {
                $klopt = true;
                foreach ( isset( $r['als'] ) && is_array( $r['als'] ) ? $r['als'] : array() as $vw ) {
                    if ( ! is_array( $vw ) || 3 !== count( $vw ) ) {
                        $klopt = false;
                        break;
                    }
                    $links = array_key_exists( (string) $vw[0], $lijst ) ? $lijst[ (string) $vw[0] ] : null;
                    if ( null === $links || ! sfp_page_config_tool_vergelijk( $links, (string) $vw[1], $vw[2] ) ) {
                        $klopt = false;
                        break;
                    }
                }
                if ( $klopt ) {
                    return $klaar( (string) $r['dan'], $uit );
                }
            }
            return $anders();
    }
    return $anders();
}

/**
 * Vergelijk twee waarden met een operator uit een regel.
 *
 * @param  mixed  $links    Linkerwaarde.
 * @param  string $operator >=, >, <=, < of ==.
 * @param  mixed  $rechts   Rechterwaarde.
 * @return bool
 */
function sfp_page_config_tool_vergelijk( $links, $operator, $rechts ) {
    if ( '==' === $operator ) {
        return sfp_page_config_tool_als_tekst( $links ) === sfp_page_config_tool_als_tekst( $rechts );
    }
    if ( ! sfp_page_config_tool_is_getal( $links ) || ! sfp_page_config_tool_is_getal( $rechts ) ) {
        return false;
    }
    $l = (float) $links;
    $r = (float) $rechts;
    switch ( $operator ) {
        case '>=':
            return $l >= $r;
        case '>':
            return $l > $r;
        case '<=':
            return $l <= $r;
        case '<':
            return $l < $r;
    }
    return false;
}

/**
 * Een antwoord als tekst, zoals de browser het ook leest: tekst en getallen
 * wel, ja/nee, lijsten en leeg niet.
 *
 * @param  mixed $w Antwoord.
 * @return string|null
 */
function sfp_page_config_tool_antwoord( $w ) {
    if ( is_string( $w ) || is_int( $w ) || is_float( $w ) ) {
        return sfp_page_config_tool_als_tekst( $w );
    }
    return null;
}

/**
 * Een waarde als tekst, gelijk aan String() in de browser voor tekst, hele
 * getallen en getallen met weinig decimalen.
 *
 * @param  mixed $w Waarde.
 * @return string
 */
function sfp_page_config_tool_als_tekst( $w ) {
    if ( is_float( $w ) && floor( $w ) === $w && abs( $w ) < 1e15 ) {
        return (string) (int) $w;
    }
    if ( is_bool( $w ) ) {
        return $w ? 'true' : 'false';
    }
    return is_scalar( $w ) ? (string) $w : '';
}

/**
 * Is dit een gewoon getal (geen hex, geen lege tekst)? Gelijk aan isGetal()
 * in assets/tool/test.js.
 *
 * @param  mixed $w Waarde.
 * @return bool
 */
function sfp_page_config_tool_is_getal( $w ) {
    if ( is_int( $w ) || is_float( $w ) ) {
        return is_finite( (float) $w );
    }
    return is_string( $w ) && 1 === preg_match( '/^\s*-?(\d+\.?\d*|\.\d+)([eE][-+]?\d+)?\s*$/', $w );
}

/**
 * Afronden op een heel getal, half naar boven, met een kleine marge zodat
 * 57,49999999999999 (een rekenfout van de computer) 58 wordt. Gelijk aan
 * rond() in assets/tool/test.js; round() in PHP en Math.round() in de
 * browser verschillen juist daar.
 *
 * @param  float $w Getal.
 * @return float
 */
function sfp_page_config_tool_rond( $w ) {
    return floor( (float) $w + 0.5 + 1e-9 );
}

/**
 * Het label bij een waarde: het eerste label waarvan `vanaf` niet boven de
 * waarde ligt. Labels gelden als percentage van het maximum als de
 * scoreregel percentage is of als $procent waar is en er een maximum is.
 *
 * @param  array $b       Beschrijving.
 * @param  float $waarde  Waarde.
 * @param  float $max     Maximum van de schaal.
 * @param  bool  $procent Labels op een percentage leggen.
 * @return array{tekst: string, sleutel: string}
 */
function sfp_page_config_tool_label( array $b, $waarde, $max, $procent = false, $labels = null ) {
    $labels = null === $labels ? $b['labels'] : $labels;
    if ( ! $labels ) {
        return array( 'tekst' => '', 'sleutel' => '' );
    }
    $op = $waarde;
    if ( ! empty( $b['labels_percentage'] ) || ( $procent && 'percentage' !== $b['score']['per_dimensie'] ) ) {
        $op = $max > 0 ? $waarde / $max * 100 : 0;
    }
    foreach ( $labels as $l ) {
        if ( $op >= $l['vanaf'] ) {
            return array( 'tekst' => $l['tekst'], 'sleutel' => $l['sleutel'] );
        }
    }
    $laatste = $labels[ count( $labels ) - 1 ];
    return array( 'tekst' => $laatste['tekst'], 'sleutel' => $laatste['sleutel'] );
}

/* =========================================================================
 * Bronnen in de uitvoer
 * ====================================================================== */

/**
 * De uitslagtekst bij een sleutel, of een lege.
 *
 * @param  array  $b       Beschrijving.
 * @param  string $sleutel Sleutel in uitslagen.
 * @return array
 */
function sfp_page_config_tool_uitslagtekst( array $b, $sleutel ) {
    if ( '' !== $sleutel && isset( $b['uitslagen'][ $sleutel ] ) ) {
        return $b['uitslagen'][ $sleutel ];
    }
    return array( 'kop' => '', 'titel' => '', 'tekst' => '', 'lijsten' => array(), 'artikel' => '' );
}

/**
 * Los een bron op (uitslag.benutten, uitslag.laagste.tips, dimensie.x,
 * vraag.id, context.id, uitslag.tekst, uitslag.top[2].naam).
 *
 * @param  array  $b    Beschrijving.
 * @param  array  $r    Uitslag van sfp_page_config_tool_reken().
 * @param  string $bron Bron.
 * @return string|string[]|null
 */
function sfp_page_config_tool_bron( array $b, array $r, $bron ) {
    $delen = explode( '.', (string) $bron );
    $kop   = array_shift( $delen );
    if ( 'totaal' === $kop ) {
        return sfp_page_config_tool_getal( $r['totaal'] );
    }
    if ( 'totaal_max' === $kop ) {
        return sfp_page_config_tool_getal( $r['totaal_max'] );
    }
    if ( 'lijsten' === $kop ) {
        $naam = implode( '.', $delen );
        return isset( $b['lijsten'][ $naam ] ) ? $b['lijsten'][ $naam ] : null;
    }
    if ( 'vraag' === $kop ) {
        $id = implode( '.', $delen );
        return isset( $r['teksten'][ $id ] ) ? $r['teksten'][ $id ] : ( isset( $r['context'][ $id ] ) ? $r['context'][ $id ] : null );
    }
    if ( 'context' === $kop ) {
        $id = implode( '.', $delen );
        return isset( $r['context'][ $id ] ) ? $r['context'][ $id ] : null;
    }
    if ( 'dimensie' === $kop ) {
        $id = array_shift( $delen );
        if ( ! isset( $r['dimensies'][ $id ] ) ) {
            return null;
        }
        $veld = $delen ? implode( '.', $delen ) : 'waarde';
        if ( 'naam' === $veld ) {
            return $b['dimensies'][ $id ]['naam'];
        }
        if ( 'label' === $veld ) {
            return $r['dimensies'][ $id ]['label']['tekst'];
        }
        return isset( $r['dimensies'][ $id ][ $veld ] ) ? (string) $r['dimensies'][ $id ][ $veld ] : null;
    }
    if ( 'uitslag' !== $kop ) {
        return null;
    }
    $sleutel = $r['uitslag'];
    if ( $delen && 'laagste' === $delen[0] ) {
        array_shift( $delen );
        $sleutel = $r['volgorde'] ? $r['volgorde'][ count( $r['volgorde'] ) - 1 ] : '';
    } elseif ( $delen && preg_match( '/^top\[(\d+)\]$/', $delen[0], $m ) ) {
        array_shift( $delen );
        $i       = (int) $m[1] - 1;
        $sleutel = isset( $r['volgorde'][ $i ] ) ? $r['volgorde'][ $i ] : '';
    }
    $u    = sfp_page_config_tool_uitslagtekst( $b, $sleutel );
    $veld = $delen ? implode( '.', $delen ) : 'tekst';
    if ( in_array( $veld, array( 'kop', 'titel', 'tekst', 'artikel' ), true ) ) {
        return $u[ $veld ];
    }
    if ( 'naam' === $veld ) {
        return isset( $b['dimensies'][ $sleutel ] ) ? $b['dimensies'][ $sleutel ]['naam'] : $u['titel'];
    }
    return isset( $u['lijsten'][ $veld ] ) ? $u['lijsten'][ $veld ] : null;
}

/**
 * Vul plaatshouders als {uitslag.titel} of {dimensie.doener} in.
 *
 * @param  string $tekst Tekst met plaatshouders.
 * @param  array  $b     Beschrijving.
 * @param  array  $r     Uitslag.
 * @return string
 */
function sfp_page_config_tool_vul( $tekst, array $b, array $r, $diepte = 0 ) {
    $uit = (string) preg_replace_callback(
        '/\{([a-z0-9_.\[\]]+)\}/i',
        function ( $m ) use ( $b, $r ) {
            $w = sfp_page_config_tool_bron( $b, $r, $m[1] );
            if ( is_array( $w ) ) {
                return implode( ', ', array_map( 'sfp_page_config_tool_item', $w ) );
            }
            return null === $w ? '' : (string) $w;
        },
        (string) $tekst
    );
    // Een uitslagtekst mag zelf ook plaatshouders bevatten (één niveau dieper).
    return ( $diepte < 1 && false !== strpos( $uit, '{' ) ) ? sfp_page_config_tool_vul( $uit, $b, $r, $diepte + 1 ) : $uit;
}

/**
 * De uitslag als platte tekst (mail, Word, pdf): dezelfde volgorde als de
 * uitvoer op het scherm.
 *
 * @param  array  $b     Beschrijving.
 * @param  array  $r     Uitslag.
 * @param  string $titel Titel van de tool.
 * @return string
 */
function sfp_page_config_tool_tekst_uitvoer( array $b, array $r, $titel ) {
    $t      = 'sfp_page_config_artikel_tekst';
    $regels = array( $titel, str_repeat( '=', min( 60, max( 10, mb_strlen( $titel ) ) ) ), '' );
    foreach ( $b['uitvoer'] as $u ) {
        switch ( $u['type'] ) {
            case 'profiel':
                $sleutel = $r['uitslag'];
                if ( '' !== $u['bron'] && preg_match( '/^uitslag\.top\[(\d+)\]$/', $u['bron'], $m ) ) {
                    $sleutel = isset( $r['volgorde'][ (int) $m[1] - 1 ] ) ? $r['volgorde'][ (int) $m[1] - 1 ] : '';
                }
                $uit = sfp_page_config_tool_uitslagtekst( $b, $sleutel );
                if ( '' !== $u['kop'] ) {
                    $regels[] = sfp_page_config_tool_vul( $u['kop'], $b, $r );
                } elseif ( '' !== $uit['kop'] ) {
                    $regels[] = sfp_page_config_tool_vul( $uit['kop'], $b, $r );
                }
                if ( '' !== $uit['titel'] ) {
                    $regels[] = mb_strtoupper( sfp_page_config_tool_vul( $uit['titel'], $b, $r ), 'UTF-8' );
                }
                if ( '' !== $uit['tekst'] ) {
                    $regels[] = sfp_page_config_tool_vul( $uit['tekst'], $b, $r );
                }
                if ( ! empty( $r['gelijkspel'] ) && '' === $u['bron'] && ! empty( $b['uitkomst']['gelijkspel_tekst'] ) ) {
                    $regels[] = (string) $b['uitkomst']['gelijkspel_tekst'];
                }
                $regels[] = '';
                break;
            case 'vast':
                if ( '' !== $u['kop'] ) {
                    $regels[] = $u['kop'];
                }
                if ( ! empty( $u['tekst'] ) ) {
                    $regels[] = (string) $u['tekst'];
                }
                if ( ! empty( $u['lijst'] ) && is_array( $u['lijst'] ) ) {
                    foreach ( $u['lijst'] as $item ) {
                        $regels[] = '- ' . sfp_page_config_tool_item( $item );
                    }
                }
                $regels[] = '';
                break;
            case 'totaal':
                $regels[] = ( '' !== $u['kop'] ? $u['kop'] : $t( 'tool_scores' ) ) . ': ' . sfp_page_config_tool_getal( $r['totaal'] ) . ( $r['totaal_max'] > 0 ? ' / ' . sfp_page_config_tool_getal( $r['totaal_max'] ) : '' );
                $regels[] = '';
                break;
            case 'alle':
                $keuze = sfp_page_config_tool_selectie( $r, isset( $u['selectie'] ) ? (string) $u['selectie'] : 'alle', isset( $u['aantal'] ) ? (int) $u['aantal'] : 0, ! empty( $u['zonder_hoogste'] ) );
                if ( '' !== $u['kop'] ) {
                    $regels[] = $u['kop'];
                }
                if ( ! $keuze && isset( $u['leeg'] ) && '' !== $u['leeg'] ) {
                    $regels[] = $u['leeg'];
                    $regels[] = '';
                }
                $koppen = isset( $u['koppen'] ) && is_array( $u['koppen'] ) ? $u['koppen'] : array();
                foreach ( $keuze as $i => $d ) {
                    $ut   = sfp_page_config_tool_uitslagtekst( $b, $d );
                    $naam = '' !== $ut['titel'] ? $ut['titel'] : $b['dimensies'][ $d ]['volledig'];
                    $regels[] = ( isset( $koppen[ $i ] ) ? $koppen[ $i ] . ': ' : '' ) . $naam . ( $d === $r['uitslag'] && ! empty( $u['markeer'] ) ? ' ' . $u['markeer'] : '' );
                    if ( '' !== $ut['tekst'] ) {
                        $regels[] = sfp_page_config_tool_vul( $ut['tekst'], $b, $r );
                    }
                    foreach ( isset( $u['lijsten'] ) && is_array( $u['lijsten'] ) ? $u['lijsten'] : array() as $naam_l ) {
                        if ( ! empty( $ut['lijsten'][ $naam_l ] ) ) {
                            foreach ( $ut['lijsten'][ $naam_l ] as $item ) {
                                $regels[] = '- ' . sfp_page_config_tool_item( $item );
                            }
                        }
                    }
                    $regels[] = '';
                }
                break;
            case 'balken':
            case 'niveaus':
                $regels[] = '' !== $u['kop'] ? $u['kop'] : $t( 'tool_scores' );
                // Zelfde volgorde als op het scherm: vast of van hoog naar laag.
                foreach ( ( isset( $u['sorteer'] ) && 'vast' === $u['sorteer'] ) ? sfp_page_config_tool_dims( $b ) : $r['volgorde'] as $d ) {
                    $dim   = $r['dimensies'][ $d ];
                    $label = '' !== $dim['label']['tekst'] ? ' (' . $dim['label']['tekst'] . ')' : '';
                    $eenh  = 'percentage' === $b['score']['per_dimensie'] ? '%' : ( $dim['max'] > 0 ? ' / ' . sfp_page_config_tool_getal( $dim['max'] ) : '' );
                    $regels[] = '- ' . $b['dimensies'][ $d ]['volledig'] . ': ' . sfp_page_config_tool_getal( $dim['waarde'] ) . $eenh . $label;
                    if ( 'niveaus' === $u['type'] ) {
                        $nt = sfp_page_config_tool_uitslagtekst( $b, $d . ':' . $dim['label']['sleutel'] );
                        if ( '' !== $nt['tekst'] ) {
                            $regels[] = '  ' . $nt['tekst'];
                        }
                    }
                }
                $regels[] = '';
                break;
            case 'lijst':
            case 'afvinklijst':
                $lijst = sfp_page_config_tool_bron( $b, $r, $u['bron'] );
                if ( is_array( $lijst ) && $lijst ) {
                    if ( '' !== $u['kop'] ) {
                        $regels[] = sfp_page_config_tool_vul( $u['kop'], $b, $r );
                    }
                    $i = 0;
                    foreach ( $lijst as $item ) {
                        $i++;
                        $regels[] = ( 'afvinklijst' === $u['type'] ? '[ ] ' : $i . '. ' ) . sfp_page_config_tool_item( $item );
                    }
                    $regels[] = '';
                }
                break;
            case 'tekst':
                $w = sfp_page_config_tool_bron( $b, $r, $u['bron'] );
                if ( is_string( $w ) && '' !== $w ) {
                    if ( '' !== $u['kop'] ) {
                        $regels[] = $u['kop'];
                    }
                    $regels[] = $w;
                    $regels[] = '';
                }
                break;
            case 'laagste':
                $aantal = isset( $u['aantal'] ) ? max( 1, (int) $u['aantal'] ) : 1;
                $naam   = isset( $u['lijst'] ) ? (string) $u['lijst'] : '';
                $laag   = array_slice( array_reverse( $r['volgorde'] ), 0, $aantal );
                if ( '' !== $u['kop'] ) {
                    $regels[] = $u['kop'];
                }
                foreach ( $laag as $d ) {
                    $ut   = sfp_page_config_tool_uitslagtekst( $b, $d );
                    $tip  = isset( $ut['lijsten'][ $naam ][0] ) ? sfp_page_config_tool_item( $ut['lijsten'][ $naam ][0] ) : '';
                    $regels[] = '- ' . $b['dimensies'][ $d ]['volledig'] . ( '' !== $tip ? ': ' . $tip : '' );
                }
                $regels[] = '';
                break;
            case 'link':
                $url = sfp_page_config_tool_bron( $b, $r, '' !== $u['bron'] ? $u['bron'] : 'uitslag.artikel' );
                if ( is_string( $url ) && '' !== $url ) {
                    $regels[] = ( '' !== $u['kop'] ? $u['kop'] . ': ' : '' ) . $url;
                    $regels[] = '';
                }
                break;
            case 'onderbouwing':
                $regels[] = '' !== $u['kop'] ? $u['kop'] : $t( 'tool_onderbouwing' );
                foreach ( $b['vragen'] as $v ) {
                    if ( ! isset( $r['bijdragen'][ $v['id'] ] ) ) {
                        continue;
                    }
                    $delen = array();
                    foreach ( $r['bijdragen'][ $v['id'] ] as $d => $p ) {
                        $delen[] = ( isset( $b['dimensies'][ $d ] ) ? $b['dimensies'][ $d ]['naam'] : $d ) . ' ' . sfp_page_config_tool_getal( $p );
                    }
                    $regels[] = '- ' . $v['tekst'] . ': ' . implode( ', ', $delen );
                }
                $regels[] = '';
                break;
            case 'tabel':
                $rijen = sfp_page_config_tool_bron( $b, $r, $u['bron'] );
                if ( is_array( $rijen ) && $rijen ) {
                    if ( '' !== $u['kop'] ) {
                        $regels[] = $u['kop'];
                    }
                    foreach ( $rijen as $rij ) {
                        $regels[] = is_array( $rij ) ? implode( ' | ', array_map( 'strval', $rij ) ) : (string) $rij;
                    }
                    $regels[] = '';
                }
                break;
        }
    }
    return trim( implode( "\n", $regels ) ) . "\n";
}

/**
 * Welke dimensies een uitvoeronderdeel `alle` toont: alle, de hoogste (alle
 * met de topscore), de laagste (alle met de laagste score) of de top N.
 * Met $zonder_hoogste vallen de dimensies met de topscore weg: scoort alles
 * gelijk, dan blijft er bij `laagste` niets over.
 *
 * @param  array  $r              Uitslag.
 * @param  string $selectie       alle, hoogste, laagste of top.
 * @param  int    $aantal         Bij top.
 * @param  bool   $zonder_hoogste Dimensies met de topscore weglaten.
 * @return string[]
 */
function sfp_page_config_tool_selectie( array $r, $selectie, $aantal = 0, $zonder_hoogste = false ) {
    $v = $r['volgorde'];
    if ( ! $v ) {
        return array();
    }
    $w = function ( $d ) use ( $r ) {
        return $r['dimensies'][ $d ]['waarde'];
    };
    if ( $zonder_hoogste ) {
        $max = $w( $v[0] );
        $v   = array_values( array_filter( $v, function ( $d ) use ( $w, $max ) { return $w( $d ) !== $max; } ) );
        if ( ! $v ) {
            return array();
        }
    }
    switch ( $selectie ) {
        case 'hoogste':
            $max = $w( $v[0] );
            return array_values( array_filter( $v, function ( $d ) use ( $w, $max ) { return $w( $d ) === $max; } ) );
        case 'laagste':
            $min = $w( $v[ count( $v ) - 1 ] );
            return array_values( array_filter( $v, function ( $d ) use ( $w, $min ) { return $w( $d ) === $min; } ) );
        case 'top':
            return array_slice( $v, 0, max( 1, $aantal ) );
    }
    return $v;
}

/**
 * Een getal zonder overbodige decimalen.
 *
 * @param  float $w Getal.
 * @return string
 */
function sfp_page_config_tool_getal( $w ) {
    $w = (float) $w;
    return floor( $w ) === $w ? (string) (int) $w : (string) ( sfp_page_config_tool_rond( $w * 10 ) / 10 );
}

/**
 * Eén item van een lijst als tekst (een rij: de cellen achter elkaar).
 *
 * @param  string|string[] $item Item.
 * @return string
 */
function sfp_page_config_tool_item( $item ) {
    return is_array( $item ) ? implode( ' | ', array_map( 'strval', $item ) ) : (string) $item;
}

/**
 * De tekst "Hoe deze test rekent": de stellingen per dimensie en de
 * rekenregel in woorden. Staat ingeklapt op de pagina en is het doel van
 * isBasedOn in de gegevens voor Google.
 *
 * @param  array $b Beschrijving.
 * @return string HTML.
 */
function sfp_page_config_tool_hoe_rekent( array $b ) {
    $t    = 'sfp_page_config_artikel_tekst';
    $html = '';
    $per  = array();
    $los  = array();
    foreach ( $b['vragen'] as $v ) {
        if ( 'likert' === $v['type'] && isset( $b['dimensies'][ $v['dimensie'] ] ) ) {
            $per[ $v['dimensie'] ][] = $v['tekst'];
        } elseif ( 'keuze' === $v['type'] ) {
            $los[] = $v;
        }
    }
    foreach ( $b['dimensies'] as $id => $d ) {
        if ( empty( $per[ $id ] ) ) {
            continue;
        }
        $html .= '<h4>' . esc_html( $d['volledig'] ) . '</h4><ul>';
        foreach ( $per[ $id ] as $zin ) {
            $html .= '<li>' . esc_html( $zin ) . '</li>';
        }
        $html .= '</ul>';
    }
    if ( $los ) {
        $html .= '<h4>' . esc_html( $t( 'tool_keuzevragen' ) ) . '</h4><ul>';
        foreach ( $los as $v ) {
            $opties = array();
            foreach ( $v['opties'] as $o ) {
                $naar = array();
                foreach ( $o['punten'] as $dim => $p ) {
                    $naar[] = ( isset( $b['dimensies'][ $dim ] ) ? $b['dimensies'][ $dim ]['naam'] : $dim ) . ' ' . sfp_page_config_tool_getal( $p );
                }
                $opties[] = $o['tekst'] . ( $naar ? ' (' . implode( ', ', $naar ) . ')' : '' );
            }
            $html .= '<li>' . esc_html( $v['tekst'] ) . '<br><small>' . esc_html( implode( ' · ', $opties ) ) . '</small></li>';
        }
        $html .= '</ul>';
    }
    $html .= '<p>' . esc_html( sfp_page_config_tool_regel_in_woorden( $b ) ) . '</p>';
    if ( '' !== $b['bron']['model'] ) {
        $html .= '<p>' . esc_html( $t( 'tool_gebaseerd_op' ) ) . ' ' . ( '' !== $b['bron']['link'] ? '<a href="' . esc_url( $b['bron']['link'] ) . '" rel="nofollow">' . esc_html( $b['bron']['model'] ) . '</a>' : esc_html( $b['bron']['model'] ) ) . '</p>';
    }
    return $html;
}

/**
 * De rekenregel in woorden, in de taal van de site.
 *
 * @param  array $b Beschrijving.
 * @return string
 */
function sfp_page_config_tool_regel_in_woorden( array $b ) {
    $t      = 'sfp_page_config_artikel_tekst';
    $score  = $t( 'tool_score_' . $b['score']['per_dimensie'] );
    $regel  = $t( 'tool_regel_' . $b['uitkomst']['regel'] );
    $labels = array();
    foreach ( $b['labels'] as $l ) {
        $labels[] = $l['tekst'] . ' ' . sfp_page_config_tool_getal( $l['vanaf'] ) . ( 'percentage' === $b['score']['per_dimensie'] || ! empty( $b['uitkomst']['percentage'] ) ? '%' : '' );
    }
    return trim( $score . ' ' . $regel . ( $labels ? ' ' . sprintf( $t( 'tool_labels' ), implode( ', ', $labels ) ) : '' ) );
}
