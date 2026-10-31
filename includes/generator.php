<?php
/**
 * SFP Page Config - Tools: soort generator
 *
 * De beschrijving van een generator normaliseren, controleren en het
 * resultaat bouwen. Het bouwen is gelijk aan assets/tool/generator.js; de
 * browser bouwt voor de bezoeker, de server bouwt opnieuw voor de mail.
 * tests/generator.php legt beide naast elkaar.
 *
 * Formaat: claude/tools-generator-bouwplan-2026-10-23.md (project) en
 * tests/tool-voorbeelden/.
 *
 * @package SFP_Page_Config
 * @since   3.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/reken.php';

/**
 * De soorten tool die op de motor van de generator draaien.
 *
 * @return string[]
 */
function sfp_page_config_generator_soorten() {
    return array( 'generator', 'rekenaar', 'checklist' );
}

/**
 * De veldtypen van een generator.
 *
 * @return string[]
 */
function sfp_page_config_generator_veldtypen() {
    return array( 'keuze', 'lijst', 'vinkjes', 'kort', 'tekstvak', 'regels', 'datum', 'getal', 'getallen' );
}

/**
 * De soorten sectie in de uitvoer.
 *
 * @return string[]
 */
function sfp_page_config_generator_sectietypen() {
    return array( 'tekst', 'lijst', 'vragen', 'afvink', 'alineas', 'gegevens', 'herhaal', 'getal', 'verdeling', 'kaarten' );
}

/* =========================================================================
 * Normaliseren
 * ====================================================================== */

/**
 * Vul de standaardwaarden in, zodat browser en server dezelfde vorm lezen.
 *
 * @param  array $b Ruwe beschrijving (soort generator).
 * @return array
 */
function sfp_page_config_generator_normaliseer( array $b ) {
    $genegeerd = array();
    $uit       = array(
        'versie'          => isset( $b['versie'] ) ? (int) $b['versie'] : 1,
        'soort'           => isset( $b['soort'] ) && in_array( $b['soort'], sfp_page_config_generator_soorten(), true ) ? $b['soort'] : 'generator',
        'resultaat_kop'   => isset( $b['resultaat_kop'] ) && is_scalar( $b['resultaat_kop'] ) ? (string) $b['resultaat_kop'] : '',
        'resultaat_intro' => isset( $b['resultaat_intro'] ) && is_scalar( $b['resultaat_intro'] ) ? (string) $b['resultaat_intro'] : '',
        'bewerkbaar'      => ! empty( $b['bewerkbaar'] ),
        'resultaat_slot'  => isset( $b['resultaat_slot'] ) && is_scalar( $b['resultaat_slot'] ) ? (string) $b['resultaat_slot'] : '',
    );

    $stappen = array();
    if ( isset( $b['stappen'] ) && is_array( $b['stappen'] ) ) {
        foreach ( array_values( $b['stappen'] ) as $i => $s ) {
            if ( ! is_array( $s ) ) {
                continue;
            }
            $velden = array();
            if ( isset( $s['velden'] ) && is_array( $s['velden'] ) ) {
                foreach ( array_values( $s['velden'] ) as $j => $v ) {
                    if ( is_array( $v ) ) {
                        $velden[] = sfp_page_config_generator_veld_schoon( $v, 's' . ( $i + 1 ) . 'v' . ( $j + 1 ) );
                    }
                }
            }
            $stappen[] = array(
                'titel'  => isset( $s['titel'] ) && is_scalar( $s['titel'] ) ? (string) $s['titel'] : '',
                'intro'  => isset( $s['intro'] ) && is_scalar( $s['intro'] ) ? (string) $s['intro'] : '',
                'velden' => $velden,
            );
        }
    }
    $uit['stappen'] = $stappen;

    $uit['banken'] = isset( $b['banken'] ) && is_array( $b['banken'] ) ? sfp_page_config_generator_bank_schoon( $b['banken'] ) : new stdClass();
    $waarden       = array();
    if ( isset( $b['waarden'] ) && is_array( $b['waarden'] ) ) {
        foreach ( $b['waarden'] as $naam => $w ) {
            if ( is_scalar( $w ) ) {
                $waarden[ (string) $naam ] = (string) $w;
            }
        }
    }
    $uit['waarden'] = $waarden ? $waarden : new stdClass();

    $uitvoer = array();
    if ( isset( $b['uitvoer'] ) && is_array( $b['uitvoer'] ) ) {
        foreach ( $b['uitvoer'] as $s ) {
            if ( is_array( $s ) ) {
                $uitvoer[] = sfp_page_config_generator_sectie_schoon( $s );
            }
        }
    }
    $uit['uitvoer'] = $uitvoer;

    $acties        = isset( $b['acties'] ) && is_array( $b['acties'] ) ? $b['acties'] : array();
    $uit['acties'] = array(
        'pdf_bestandsnaam' => isset( $acties['pdf_bestandsnaam'] ) ? (string) $acties['pdf_bestandsnaam'] : '',
        'pdf'       => ! isset( $acties['pdf'] ) || ! empty( $acties['pdf'] ),
        'word'      => ! isset( $acties['word'] ) || ! empty( $acties['word'] ),
        'kopieer'   => ! isset( $acties['kopieer'] ) || ! empty( $acties['kopieer'] ),
        'deellink'  => ! isset( $acties['deellink'] ) || ! empty( $acties['deellink'] ),
        'mail'      => ! isset( $acties['mail'] ) || ! empty( $acties['mail'] ),
        'duimpjes'  => ! isset( $acties['duimpjes'] ) || ! empty( $acties['duimpjes'] ),
        'formulier' => isset( $acties['formulier'] ) ? (int) $acties['formulier'] : 0,
        'kopieer_kaal' => ! empty( $acties['kopieer_kaal'] ),
        'formulier_velden' => new stdClass(),
    );
    if ( isset( $acties['formulier_velden'] ) && is_array( $acties['formulier_velden'] ) ) {
        $velden_f = array();
        foreach ( $acties['formulier_velden'] as $naam => $sjabloon ) {
            if ( is_scalar( $sjabloon ) && preg_match( '/^[a-zA-Z0-9_-]+$/', (string) $naam ) ) {
                $velden_f[ (string) $naam ] = (string) $sjabloon;
            }
        }
        if ( $velden_f ) {
            $uit['acties']['formulier_velden'] = $velden_f;
        }
    }

    $mail        = isset( $b['mail'] ) && is_array( $b['mail'] ) ? $b['mail'] : array();
    $uit['mail'] = array(
        'onderwerp' => isset( $mail['onderwerp'] ) ? (string) $mail['onderwerp'] : '',
        'intro'     => isset( $mail['intro'] ) ? (string) $mail['intro'] : '',
    );

    $bron = isset( $b['bron'] ) && is_array( $b['bron'] ) ? $b['bron'] : array();
    $link = isset( $bron['link'] ) && is_scalar( $bron['link'] ) ? (string) $bron['link'] : '';
    if ( '' !== trim( $link ) && '' === sfp_page_config_tool_url( $link ) ) {
        $genegeerd[] = 'De link bij bron is geen gewoon webadres en wordt genegeerd.';
    }
    $uit['bron'] = array(
        'model' => isset( $bron['model'] ) ? (string) $bron['model'] : '',
        'link'  => sfp_page_config_tool_url( $link ),
    );

    $reken = array();
    if ( isset( $b['reken'] ) && is_array( $b['reken'] ) ) {
        foreach ( $b['reken'] as $r ) {
            if ( is_array( $r ) && isset( $r['id'], $r['formule'] ) && is_scalar( $r['id'] ) && is_scalar( $r['formule'] ) ) {
                $reken[] = array(
                    'id'      => (string) $r['id'],
                    'formule' => (string) $r['formule'],
                );
            }
        }
    }
    $uit['reken'] = $reken;
    foreach ( array( 'controles', 'meldingen' ) as $soort ) {
        $lijst = array();
        if ( isset( $b[ $soort ] ) && is_array( $b[ $soort ] ) ) {
            foreach ( $b[ $soort ] as $c ) {
                if ( is_array( $c ) && isset( $c['formule'], $c['melding'] ) && is_scalar( $c['formule'] ) && is_scalar( $c['melding'] ) ) {
                    $lijst[] = array(
                        'formule' => (string) $c['formule'],
                        'melding' => (string) $c['melding'],
                        'stap'    => isset( $c['stap'] ) && is_numeric( $c['stap'] ) ? max( 1, (int) $c['stap'] ) : max( 1, count( $stappen ) ),
                    );
                }
            }
        }
        $uit[ $soort ] = $lijst;
    }
    $uit['oordeel'] = null;
    if ( isset( $b['oordeel'] ) && is_array( $b['oordeel'] ) && isset( $b['oordeel']['op'] ) && is_scalar( $b['oordeel']['op'] ) ) {
        $labels = array();
        foreach ( isset( $b['oordeel']['labels'] ) && is_array( $b['oordeel']['labels'] ) ? $b['oordeel']['labels'] : array() as $l ) {
            if ( is_array( $l ) && isset( $l['vanaf'] ) && is_numeric( $l['vanaf'] ) ) {
                $labels[] = array(
                    'vanaf'   => 0 + $l['vanaf'],
                    'sleutel' => isset( $l['sleutel'] ) && is_scalar( $l['sleutel'] ) ? (string) $l['sleutel'] : '',
                    'tekst'   => isset( $l['tekst'] ) && is_scalar( $l['tekst'] ) ? (string) $l['tekst'] : '',
                );
            }
        }
        usort(
            $labels,
            function ( $x, $y ) {
                return $y['vanaf'] <=> $x['vanaf'];
            }
        );
        $uit['oordeel'] = array(
            'op'     => (string) $b['oordeel']['op'],
            'labels' => $labels,
        );
    }
    $uit['live'] = ! empty( $b['live'] );
    $uit['weergave'] = sfp_page_config_generator_weergave_schoon( isset( $b['weergave'] ) ? $b['weergave'] : null, $stappen, $genegeerd );

    $uit['teksten']   = isset( $b['teksten'] ) && is_array( $b['teksten'] ) ? array_map( 'strval', array_filter( $b['teksten'], 'is_scalar' ) ) : array();
    $uit['genegeerd'] = $genegeerd;
    return $uit;
}

/**
 * Eén veld in een vaste vorm.
 *
 * @param  array  $v     Ruw veld.
 * @param  string $reserve Id als het veld er geen heeft.
 * @return array
 */
function sfp_page_config_generator_veld_schoon( array $v, $reserve ) {
    $opties = array();
    if ( isset( $v['opties'] ) && is_array( $v['opties'] ) ) {
        foreach ( array_values( $v['opties'] ) as $j => $o ) {
            if ( is_scalar( $o ) ) {
                $o = array( 'tekst' => (string) $o );
            }
            if ( ! is_array( $o ) ) {
                continue;
            }
            $optie = array(
                'waarde' => isset( $o['waarde'] ) && is_scalar( $o['waarde'] ) ? (string) $o['waarde'] : (string) ( $j + 1 ),
                'tekst'  => isset( $o['tekst'] ) && is_scalar( $o['tekst'] ) ? (string) $o['tekst'] : '',
                'uitleg' => isset( $o['uitleg'] ) && is_scalar( $o['uitleg'] ) ? (string) $o['uitleg'] : '',
            );
            if ( isset( $o['icoon'] ) && is_scalar( $o['icoon'] ) && in_array( (string) $o['icoon'], sfp_page_config_generator_iconen(), true ) ) {
                $optie['icoon'] = (string) $o['icoon'];
            }
            if ( isset( $o['getallen'] ) && is_array( $o['getallen'] ) ) {
                $getallen = array();
                foreach ( $o['getallen'] as $kolom => $g ) {
                    if ( is_numeric( $g ) && preg_match( '/^[a-zA-Z0-9_]+$/', (string) $kolom ) ) {
                        $getallen[ (string) $kolom ] = 0 + $g;
                    }
                }
                if ( $getallen ) {
                    $optie['getallen'] = $getallen;
                }
            }
            $opties[] = $optie;
        }
    }
    $uit = array(
        'id'        => isset( $v['id'] ) && is_scalar( $v['id'] ) && '' !== (string) $v['id'] ? (string) $v['id'] : $reserve,
        'type'      => isset( $v['type'] ) && is_scalar( $v['type'] ) ? (string) $v['type'] : 'kort',
        'label'     => isset( $v['label'] ) && is_scalar( $v['label'] ) ? (string) $v['label'] : '',
        'hint'      => isset( $v['hint'] ) && is_scalar( $v['hint'] ) ? (string) $v['hint'] : '',
        'voorbeeld' => isset( $v['voorbeeld'] ) && is_scalar( $v['voorbeeld'] ) ? (string) $v['voorbeeld'] : '',
        'opties'    => $opties,
        'verder'    => ! empty( $v['verder'] ),
        'mail'      => isset( $v['mail'] ) ? (bool) $v['mail'] : ! in_array( isset( $v['type'] ) ? $v['type'] : 'kort', array( 'tekstvak', 'regels' ), true ),
    );
    if ( isset( $v['verplicht'] ) ) {
        $uit['verplicht'] = is_array( $v['verplicht'] ) ? $v['verplicht'] : (bool) $v['verplicht'];
    } else {
        $uit['verplicht'] = false;
    }
    if ( isset( $v['toon_als'] ) && is_array( $v['toon_als'] ) ) {
        $uit['toon_als'] = $v['toon_als'];
    }
    if ( isset( $v['label_als'] ) && is_array( $v['label_als'] ) ) {
        $regels = array();
        foreach ( $v['label_als'] as $r ) {
            if ( is_array( $r ) && isset( $r['tekst'] ) && is_scalar( $r['tekst'] ) ) {
                $regels[] = array(
                    'als'   => isset( $r['als'] ) && is_array( $r['als'] ) ? $r['als'] : null,
                    'tekst' => (string) $r['tekst'],
                );
            }
        }
        $uit['label_als'] = $regels;
    }
    $getaltype = in_array( $uit['type'], array( 'getal', 'getallen' ), true );
    foreach ( array( 'min', 'max', 'maxlengte' ) as $sleutel ) {
        if ( isset( $v[ $sleutel ] ) && is_numeric( $v[ $sleutel ] ) ) {
            $uit[ $sleutel ] = $getaltype && 'maxlengte' !== $sleutel ? 0 + $v[ $sleutel ] : max( 0, (int) $v[ $sleutel ] );
        }
    }
    if ( isset( $v['stap'] ) && is_numeric( $v['stap'] ) && $v['stap'] > 0 ) {
        $uit['stap'] = 0 + $v['stap'];
    }
    if ( isset( $v['eenheid'] ) && is_scalar( $v['eenheid'] ) ) {
        $uit['eenheid'] = (string) $v['eenheid'];
    }
    // Kaartweergave (3.21.0): tegels in een raster of als kwadrant met assen.
    if ( isset( $v['tegels'] ) && in_array( $v['tegels'], array( 'raster', 'kwadrant' ), true ) ) {
        $uit['tegels'] = (string) $v['tegels'];
    }
    if ( isset( $v['kolommen'] ) && is_numeric( $v['kolommen'] ) ) {
        $uit['kolommen'] = max( 1, min( 4, (int) $v['kolommen'] ) );
    }
    if ( isset( $v['assen'] ) && is_array( $v['assen'] ) ) {
        $assen = array();
        foreach ( array( 'kolommen', 'rijen' ) as $as ) {
            $lijst = isset( $v['assen'][ $as ] ) && is_array( $v['assen'][ $as ] ) ? array_values( array_map( 'strval', array_filter( $v['assen'][ $as ], 'is_scalar' ) ) ) : array();
            $assen[ $as ] = array_slice( $lijst, 0, 2 );
        }
        $uit['assen'] = $assen;
    }
    if ( isset( $v['standaard'] ) ) {
        if ( is_array( $v['standaard'] ) ) {
            $uit['standaard'] = array_values( array_map( 'strval', array_filter( $v['standaard'], 'is_scalar' ) ) );
        } elseif ( is_scalar( $v['standaard'] ) ) {
            $uit['standaard'] = (string) $v['standaard'];
        }
    }
    return $uit;
}

/**
 * De iconen die een optie in de kaartweergave kan hebben (3.21.0). De
 * tekening zelf staat in assets/tool/generator.js.
 *
 * @return string[]
 */
function sfp_page_config_generator_iconen() {
    return array( 'info', 'lijst', 'vinkje', 'pijl', 'ster', 'duo', 'persoon', 'groep', 'wereld', 'lagen', 'klok', 'doel', 'vraag', 'boek' );
}

/**
 * De weergave van een generator (3.21.0). Zonder modus "kaart" blijft alles
 * zoals het was. Groepen geven de letters bovenaan (bijvoorbeeld F A S T):
 * elke groep heeft een letter, een naam en het aantal stappen dat erbij
 * hoort, in volgorde.
 *
 * @param  mixed $w         Ruwe weergave.
 * @param  array $stappen   De stappen (al opgeschoond).
 * @param  array $genegeerd Meldingen over wat genegeerd wordt.
 * @return array
 */
function sfp_page_config_generator_weergave_schoon( $w, array $stappen, array &$genegeerd ) {
    $w   = is_array( $w ) ? $w : array();
    $uit = array(
        'modus'   => isset( $w['modus'] ) && 'kaart' === $w['modus'] ? 'kaart' : '',
        'groepen' => array(),
    );
    if ( isset( $w['groepen'] ) && is_array( $w['groepen'] ) ) {
        $totaal = 0;
        foreach ( $w['groepen'] as $g ) {
            if ( ! is_array( $g ) ) {
                continue;
            }
            $aantal           = isset( $g['stappen'] ) && is_numeric( $g['stappen'] ) ? max( 1, (int) $g['stappen'] ) : 1;
            $totaal          += $aantal;
            $uit['groepen'][] = array(
                'letter'  => isset( $g['letter'] ) && is_scalar( $g['letter'] ) ? mb_substr( (string) $g['letter'], 0, 3 ) : '',
                'naam'    => isset( $g['naam'] ) && is_scalar( $g['naam'] ) ? (string) $g['naam'] : '',
                'stappen' => $aantal,
            );
        }
        if ( $uit['groepen'] && count( $stappen ) !== $totaal ) {
            $genegeerd[]    = 'De groepen bij weergave tellen ' . $totaal . ' stappen, de tool heeft er ' . count( $stappen ) . '. De groepen worden genegeerd.';
            $uit['groepen'] = array();
        }
    }
    return $uit;
}

/**
 * Een bank: geneste lijsten en objecten met alleen tekst, of items
 * { tekst, als }.
 *
 * @param  mixed $w     Waarde.
 * @param  int   $diepte Diepte.
 * @return mixed
 */
function sfp_page_config_generator_bank_schoon( $w, $diepte = 0 ) {
    if ( $diepte > 8 ) {
        return null;
    }
    if ( is_scalar( $w ) ) {
        return $w;
    }
    if ( ! is_array( $w ) ) {
        return null;
    }
    $uit = array();
    foreach ( $w as $k => $x ) {
        $x = sfp_page_config_generator_bank_schoon( $x, $diepte + 1 );
        if ( null !== $x ) {
            $uit[ $k ] = $x;
        }
    }
    if ( array_values( $w ) === $w ) {
        return array_values( $uit );
    }
    return $uit ? $uit : new stdClass();
}

/**
 * Een sectie van de uitvoer. Onbekende sleutels vallen weg.
 *
 * @param  array $s Ruwe sectie.
 * @return array
 */
function sfp_page_config_generator_sectie_schoon( array $s ) {
    $uit = array( 'type' => isset( $s['type'] ) && is_scalar( $s['type'] ) ? (string) $s['type'] : 'tekst' );
    foreach ( array( 'kop', 'tekst', 'bank', 'regels', 'voor', 'over', 'leeg', 'elk', 'label', 'waarde', 'formaat', 'onder', 'filter', 'sorteer', 'aantal', 'kaartkop' ) as $sleutel ) {
        if ( isset( $s[ $sleutel ] ) && is_scalar( $s[ $sleutel ] ) ) {
            $uit[ $sleutel ] = (string) $s[ $sleutel ];
        }
    }
    if ( isset( $s['als'] ) && is_array( $s['als'] ) ) {
        $uit['als'] = $s['als'];
    }
    if ( isset( $s['pad'] ) && is_array( $s['pad'] ) ) {
        $uit['pad'] = array_values( array_map( 'strval', array_filter( $s['pad'], 'is_scalar' ) ) );
    }
    if ( isset( $s['items'] ) && is_array( $s['items'] ) ) {
        $items = array();
        foreach ( $s['items'] as $it ) {
            if ( is_scalar( $it ) ) {
                $items[] = (string) $it;
            } elseif ( is_array( $it ) && isset( $it['tekst'] ) && is_scalar( $it['tekst'] ) ) {
                $item = array(
                    'tekst' => (string) $it['tekst'],
                    'als'   => isset( $it['als'] ) && is_array( $it['als'] ) ? $it['als'] : null,
                );
                if ( isset( $it['id'] ) && is_scalar( $it['id'] ) && '' !== (string) $it['id'] ) {
                    $item['id'] = (string) $it['id'];
                }
                $items[] = $item;
            }
        }
        $uit['items'] = $items;
    }
    if ( isset( $s['regels'] ) && is_array( $s['regels'] ) ) {
        $regels = array();
        foreach ( $s['regels'] as $r ) {
            if ( is_array( $r ) && isset( $r['tekst'] ) && is_scalar( $r['tekst'] ) ) {
                $regels[] = array(
                    'label' => isset( $r['label'] ) && is_scalar( $r['label'] ) ? (string) $r['label'] : '',
                    'tekst' => (string) $r['tekst'],
                    'als'   => isset( $r['als'] ) && is_array( $r['als'] ) ? $r['als'] : null,
                );
            }
        }
        $uit['regels'] = $regels;
    }
    if ( isset( $s['inhoud'] ) && is_array( $s['inhoud'] ) ) {
        $inhoud = array();
        foreach ( $s['inhoud'] as $x ) {
            if ( is_array( $x ) ) {
                $inhoud[] = sfp_page_config_generator_sectie_schoon( $x );
            }
        }
        $uit['inhoud'] = $inhoud;
    }
    if ( isset( $s['nummers'] ) ) {
        $uit['nummers'] = (bool) $s['nummers'];
    }
    if ( isset( $s['altijd'] ) ) {
        $uit['altijd'] = (bool) $s['altijd'];
    }
    if ( isset( $s['lijnen'] ) && is_numeric( $s['lijnen'] ) ) {
        $uit['lijnen'] = (int) $s['lijnen'];
    }
    if ( isset( $s['aflopend'] ) ) {
        $uit['aflopend'] = (bool) $s['aflopend'];
    }
    if ( isset( $s['delen'] ) && is_array( $s['delen'] ) ) {
        $delen = array();
        foreach ( $s['delen'] as $d ) {
            if ( is_array( $d ) && isset( $d['waarde'] ) && is_scalar( $d['waarde'] ) ) {
                $delen[] = array(
                    'label'  => isset( $d['label'] ) && is_scalar( $d['label'] ) ? (string) $d['label'] : '',
                    'waarde' => (string) $d['waarde'],
                );
            }
        }
        $uit['delen'] = $delen;
    }
    if ( isset( $s['voorkeur'] ) && is_array( $s['voorkeur'] ) && isset( $s['voorkeur']['formule'] ) && is_scalar( $s['voorkeur']['formule'] ) ) {
        $uit['voorkeur'] = array(
            'formule'  => (string) $s['voorkeur']['formule'],
            'minimaal' => isset( $s['voorkeur']['minimaal'] ) && is_numeric( $s['voorkeur']['minimaal'] ) ? max( 1, (int) $s['voorkeur']['minimaal'] ) : 1,
        );
    }
    if ( isset( $s['terugval'] ) && is_array( $s['terugval'] ) ) {
        $uit['terugval'] = array(
            'filter' => isset( $s['terugval']['filter'] ) && is_scalar( $s['terugval']['filter'] ) ? (string) $s['terugval']['filter'] : '',
            'aantal' => isset( $s['terugval']['aantal'] ) && is_numeric( $s['terugval']['aantal'] ) ? max( 0, (int) $s['terugval']['aantal'] ) : 0,
        );
    }
    if ( isset( $s['om_en_om'] ) && is_array( $s['om_en_om'] ) && isset( $s['om_en_om']['veld'] ) && is_scalar( $s['om_en_om']['veld'] ) ) {
        $uit['om_en_om'] = array(
            'veld'    => (string) $s['om_en_om']['veld'],
            'sleutel' => isset( $s['om_en_om']['sleutel'] ) && is_scalar( $s['om_en_om']['sleutel'] ) ? (string) $s['om_en_om']['sleutel'] : 'categorie',
        );
    }
    if ( isset( $s['groeperen'] ) && is_array( $s['groeperen'] ) && isset( $s['groeperen']['per'] ) && is_scalar( $s['groeperen']['per'] ) ) {
        $uit['groeperen'] = array(
            'per' => (string) $s['groeperen']['per'],
            'kop' => isset( $s['groeperen']['kop'] ) && is_scalar( $s['groeperen']['kop'] ) ? (string) $s['groeperen']['kop'] : '',
        );
    }
    return $uit;
}

/* =========================================================================
 * Controleren
 * ====================================================================== */

/**
 * Controleer een genormaliseerde beschrijving van een generator.
 *
 * @param  array $b Beschrijving.
 * @return array{fouten: string[], let_op: string[]}
 */
function sfp_page_config_generator_controleer( array $b ) {
    $fouten = array();
    $let_op = isset( $b['genegeerd'] ) ? (array) $b['genegeerd'] : array();
    if ( ! $b['stappen'] ) {
        $fouten[] = 'Geen stappen.';
    }
    $velden = array();
    foreach ( $b['stappen'] as $i => $s ) {
        if ( ! $s['velden'] ) {
            $fouten[] = 'Stap ' . ( $i + 1 ) . ' heeft geen velden.';
        }
        foreach ( $s['velden'] as $v ) {
            if ( isset( $velden[ $v['id'] ] ) ) {
                $fouten[] = 'Het veld "' . $v['id'] . '" komt twee keer voor.';
            }
            if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $v['id'] ) ) {
                $fouten[] = 'Het veld "' . $v['id'] . '" heeft een id met andere tekens dan letters, cijfers en een liggend streepje.';
            }
            $velden[ $v['id'] ] = $v;
            if ( ! in_array( $v['type'], sfp_page_config_generator_veldtypen(), true ) ) {
                $fouten[] = 'Het veld "' . $v['id'] . '" heeft een onbekend type "' . $v['type'] . '". Bekend: ' . implode( ', ', sfp_page_config_generator_veldtypen() ) . '.';
            }
            if ( in_array( $v['type'], array( 'keuze', 'lijst', 'vinkjes' ), true ) && ! $v['opties'] ) {
                $fouten[] = 'Het veld "' . $v['id'] . '" heeft geen opties.';
            }
            if ( '' === trim( $v['label'] ) ) {
                $let_op[] = 'Het veld "' . $v['id'] . '" heeft geen label.';
            }
            if ( isset( $v['tegels'] ) && 'kwadrant' === $v['tegels'] ) {
                if ( 'keuze' !== $v['type'] || 4 !== count( $v['opties'] ) ) {
                    $fouten[] = 'Het veld "' . $v['id'] . '" is een kwadrant: dat is een keuze met precies vier opties.';
                }
                if ( ! isset( $v['assen'] ) || 2 !== count( $v['assen']['kolommen'] ) || 2 !== count( $v['assen']['rijen'] ) ) {
                    $fouten[] = 'Het veld "' . $v['id'] . '" is een kwadrant: geef bij assen twee kolommen en twee rijen.';
                }
            }
        }
    }
    if ( ! $b['uitvoer'] ) {
        $fouten[] = 'Geen uitvoer.';
    }
    $formule = function ( $f, $plek ) use ( &$fouten ) {
        if ( '' !== trim( (string) $f ) && null === sfp_page_config_reken_boom( $f ) ) {
            $fouten[] = $plek . ': de formule "' . $f . '" klopt niet.';
        }
    };
    $rekenids = array();
    foreach ( $b['reken'] as $r ) {
        if ( ! preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $r['id'] ) || isset( $velden[ $r['id'] ] ) ) {
            $fouten[] = 'Reken: de naam "' . $r['id'] . '" is geen geldige naam of is al een veld.';
        }
        $formule( $r['formule'], 'Reken ' . $r['id'] );
        $rekenids[] = $r['id'];
    }
    foreach ( array( 'controles', 'meldingen' ) as $soort ) {
        foreach ( $b[ $soort ] as $i => $c ) {
            $formule( $c['formule'], ucfirst( $soort ) . ' ' . ( $i + 1 ) );
            if ( $c['stap'] > count( $b['stappen'] ) ) {
                $fouten[] = ucfirst( $soort ) . ' ' . ( $i + 1 ) . ': stap ' . $c['stap'] . ' bestaat niet.';
            }
        }
    }
    if ( $b['oordeel'] ) {
        $formule( $b['oordeel']['op'], 'Oordeel' );
        if ( ! $b['oordeel']['labels'] ) {
            $fouten[] = 'Oordeel zonder labels.';
        }
    }
    $banken = is_array( $b['banken'] ) ? $b['banken'] : array();
    $controleer_sectie = function ( $s, $plek ) use ( &$controleer_sectie, &$fouten, &$let_op, $velden, $banken, $formule ) {
        if ( ! in_array( $s['type'], sfp_page_config_generator_sectietypen(), true ) ) {
            $fouten[] = 'Uitvoer ' . $plek . ': onbekend type "' . $s['type'] . '". Bekend: ' . implode( ', ', sfp_page_config_generator_sectietypen() ) . '.';
            return;
        }
        foreach ( array( 'waarde', 'filter', 'sorteer', 'aantal' ) as $sleutel ) {
            if ( isset( $s[ $sleutel ] ) ) {
                $formule( $s[ $sleutel ], 'Uitvoer ' . $plek . ' (' . $sleutel . ')' );
            }
        }
        foreach ( isset( $s['delen'] ) ? $s['delen'] : array() as $d ) {
            $formule( $d['waarde'], 'Uitvoer ' . $plek . ' (delen)' );
        }
        foreach ( array( 'voorkeur' => 'formule', 'terugval' => 'filter', 'groeperen' => 'per' ) as $sleutel => $deel ) {
            if ( isset( $s[ $sleutel ][ $deel ] ) ) {
                $formule( $s[ $sleutel ][ $deel ], 'Uitvoer ' . $plek . ' (' . $sleutel . ')' );
            }
        }
        if ( 'getal' === $s['type'] && empty( $s['waarde'] ) ) {
            $fouten[] = 'Uitvoer ' . $plek . ': een getal zonder waarde.';
        }
        if ( 'verdeling' === $s['type'] && empty( $s['delen'] ) ) {
            $fouten[] = 'Uitvoer ' . $plek . ': een verdeling zonder delen.';
        }
        if ( 'kaarten' === $s['type'] ) {
            if ( isset( $s['bank'] ) && ! array_key_exists( $s['bank'], $banken ) ) {
                $fouten[] = 'Uitvoer ' . $plek . ': de bank "' . $s['bank'] . '" bestaat niet.';
            }
            if ( empty( $s['bank'] ) && empty( $s['aantal'] ) ) {
                $fouten[] = 'Uitvoer ' . $plek . ': kaarten zonder bank of aantal.';
            }
            if ( isset( $s['om_en_om'] ) && ( ! isset( $velden[ $s['om_en_om']['veld'] ] ) || empty( $s['aantal'] ) ) ) {
                $fouten[] = 'Uitvoer ' . $plek . ': om en om vraagt een bestaand veld en een aantal.';
            }
            foreach ( isset( $s['inhoud'] ) ? $s['inhoud'] : array() as $j => $x ) {
                $controleer_sectie( $x, $plek . '.' . ( $j + 1 ) );
            }
            return;
        }
        if ( in_array( $s['type'], array( 'getal', 'verdeling' ), true ) ) {
            return;
        }
        if ( 'herhaal' === $s['type'] ) {
            if ( empty( $s['over'] ) || ! isset( $velden[ $s['over'] ] ) || ! in_array( $velden[ $s['over'] ]['type'], array( 'vinkjes', 'keuze', 'lijst' ), true ) ) {
                $fouten[] = 'Uitvoer ' . $plek . ': herhaal moet over een veld met opties gaan (over).';
            }
            foreach ( isset( $s['inhoud'] ) ? $s['inhoud'] : array() as $j => $x ) {
                $controleer_sectie( $x, $plek . '.' . ( $j + 1 ) );
            }
            return;
        }
        if ( isset( $s['bank'] ) && ! array_key_exists( $s['bank'], $banken ) ) {
            $fouten[] = 'Uitvoer ' . $plek . ': de bank "' . $s['bank'] . '" bestaat niet.';
        }
        if ( isset( $s['regels'] ) && is_string( $s['regels'] ) && ! isset( $velden[ $s['regels'] ] ) ) {
            $fouten[] = 'Uitvoer ' . $plek . ': het veld "' . $s['regels'] . '" bij regels bestaat niet.';
        }
        if ( 'tekst' === $s['type'] && ! isset( $s['tekst'] ) ) {
            $fouten[] = 'Uitvoer ' . $plek . ': een tekst zonder tekst.';
        }
        if ( in_array( $s['type'], array( 'lijst', 'vragen', 'afvink', 'alineas' ), true ) && ! isset( $s['items'] ) && ! isset( $s['bank'] ) && ! ( isset( $s['regels'] ) && is_string( $s['regels'] ) ) ) {
            $fouten[] = 'Uitvoer ' . $plek . ': geen items, bank of regels.';
        }
    };
    foreach ( $b['uitvoer'] as $i => $s ) {
        $controleer_sectie( $s, (string) ( $i + 1 ) );
    }
    // Plaatshouders die nergens naar wijzen.
    $bekend = array_merge( array_keys( $velden ), array_keys( is_array( $b['waarden'] ) ? $b['waarden'] : array() ), $rekenids, array( 'oordeel', 'oordeel_tekst', 'nr', 'groep', 'groepen' ) );
    $tekst  = wp_json_encode( array( $b['uitvoer'], $b['waarden'], $b['banken'], $b['mail'], $b['resultaat_kop'], $b['resultaat_intro'], $b['acties']['pdf_bestandsnaam'] ), JSON_UNESCAPED_UNICODE );
    if ( preg_match_all( '/\{([a-zA-Z0-9_]+)(?:\|[^{}|]+)*\}/', (string) $tekst, $m ) ) {
        foreach ( array_unique( $m[1] ) as $naam ) {
            if ( ! in_array( $naam, $bekend, true ) ) {
                $let_op[] = 'De plaatshouder {' . $naam . '} wijst niet naar een veld of waarde en blijft leeg.';
            }
        }
    }
    return compact( 'fouten', 'let_op' );
}

/**
 * Korte samenvatting voor het paneel en het beheer.
 *
 * @param  array $b Beschrijving.
 * @return array
 */
function sfp_page_config_generator_samenvatting( array $b ) {
    $velden = 0;
    foreach ( $b['stappen'] as $s ) {
        $velden += count( $s['velden'] );
    }
    return array(
        'soort'     => $b['soort'],
        'stappen'   => count( $b['stappen'] ),
        'velden'    => $velden,
        'secties'   => count( $b['uitvoer'] ),
        'acties'    => array_keys( array_filter( $b['acties'] ) ),
        'formulier' => $b['acties']['formulier'],
    );
}

/* =========================================================================
 * Bouwen (gelijk aan assets/tool/generator.js)
 * ====================================================================== */

/**
 * Alle velden op id.
 *
 * @param  array $b Beschrijving.
 * @return array
 */
function sfp_page_config_generator_velden( array $b ) {
    $uit = array();
    foreach ( $b['stappen'] as $s ) {
        foreach ( $s['velden'] as $v ) {
            $uit[ $v['id'] ] = $v;
        }
    }
    return $uit;
}

/**
 * Tekst uit een waarde, zoals de browser hem leest.
 *
 * @param  mixed $w Waarde.
 * @return string
 */
function sfp_page_config_generator_als_tekst( $w ) {
    if ( is_string( $w ) ) {
        return $w;
    }
    if ( ( is_int( $w ) || is_float( $w ) ) && is_finite( (float) $w ) ) {
        return sfp_page_config_generator_js_getal( $w );
    }
    return '';
}

/**
 * Een getal zoals String() het in de browser schrijft.
 *
 * @param  int|float $w Getal.
 * @return string
 */
function sfp_page_config_generator_js_getal( $w ) {
    if ( is_int( $w ) ) {
        return (string) $w;
    }
    if ( floor( $w ) === $w && abs( $w ) < 1e21 ) {
        return sprintf( '%.0f', $w );
    }
    return rtrim( rtrim( sprintf( '%.15g', $w ), '0' ), '.' );
}

/**
 * Leeg zoals in de browser: geen waarde, lege lijst of alleen spaties.
 *
 * @param  mixed $w Waarde.
 * @return bool
 */
function sfp_page_config_generator_leeg( $w ) {
    if ( is_array( $w ) ) {
        return ! $w;
    }
    return '' === sfp_page_config_generator_trim( sfp_page_config_generator_als_tekst( $w ) );
}

/**
 * De optie van een veld bij een waarde.
 *
 * @param  array|null $v Veld.
 * @param  string     $w Waarde.
 * @return array|null
 */
function sfp_page_config_generator_optie( $v, $w ) {
    foreach ( $v && isset( $v['opties'] ) ? $v['opties'] : array() as $o ) {
        if ( (string) $o['waarde'] === (string) $w ) {
            return $o;
        }
    }
    return null;
}

/**
 * Tekens tellen zoals Array.from() in de browser.
 *
 * @param  string $s Tekst.
 * @return string[]
 */
function sfp_page_config_generator_tekens( $s ) {
    $uit = preg_split( '//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY );
    return is_array( $uit ) ? $uit : array();
}

/**
 * Het antwoord op een veld, opgeschoond.
 *
 * @param  array  $b  Beschrijving.
 * @param  array  $a  Antwoorden.
 * @param  string $id Veld.
 * @return string|string[]
 */
function sfp_page_config_generator_antwoord( array $b, array $a, $id ) {
    $velden = sfp_page_config_generator_velden( $b );
    $v      = isset( $velden[ $id ] ) ? $velden[ $id ] : null;
    $w      = array_key_exists( $id, $a ) ? $a[ $id ] : null;
    if ( ! $v ) {
        return '';
    }
    if ( 'vinkjes' === $v['type'] ) {
        $lijst = is_array( $w ) && array_values( $w ) === $w ? array_map( 'sfp_page_config_generator_als_tekst', $w ) : array();
        $uit   = array();
        foreach ( $v['opties'] as $o ) {
            if ( in_array( (string) $o['waarde'], $lijst, true ) ) {
                $uit[] = (string) $o['waarde'];
            }
        }
        return $uit;
    }
    $w = sfp_page_config_generator_als_tekst( $w );
    if ( 'keuze' === $v['type'] || 'lijst' === $v['type'] ) {
        return sfp_page_config_generator_optie( $v, $w ) ? $w : '';
    }
    if ( 'datum' === $v['type'] ) {
        return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $w ) ? $w : '';
    }
    if ( 'getal' === $v['type'] ) {
        $w = sfp_page_config_generator_trim( $w );
        return preg_match( '/^-?\d+(\.\d+)?$/', $w ) ? $w : '';
    }
    $max = ! empty( $v['maxlengte'] ) ? (int) $v['maxlengte'] : ( 'kort' === $v['type'] ? 160 : 2000 );
    if ( 'kort' === $v['type'] ) {
        $w = sfp_page_config_generator_trim( $w );
    }
    return implode( '', array_slice( sfp_page_config_generator_tekens( $w ), 0, $max ) );
}

/**
 * De getallen uit een veld getallen: losse getallen gescheiden door spaties,
 * komma's, puntkomma's of regels, binnen min en max van het veld.
 *
 * @param  array  $v Veld.
 * @param  string $w Antwoord.
 * @return float[]
 */
function sfp_page_config_generator_getallen( array $v, $w ) {
    $uit = array();
    foreach ( preg_split( '/[ \t\r\n;,]+/', (string) $w ) as $deel ) {
        if ( ! preg_match( '/^-?\d+(\.\d+)?$/', $deel ) ) {
            continue;
        }
        $n = (float) $deel;
        if ( ( isset( $v['min'] ) && $n < $v['min'] ) || ( isset( $v['max'] ) && $n > $v['max'] ) ) {
            continue;
        }
        $uit[] = $n;
    }
    return $uit;
}

/**
 * Reken een formule uit, met de namen van deze tool.
 *
 * @param  array      $b       Beschrijving.
 * @param  array      $a       Antwoorden.
 * @param  string     $formule Formule.
 * @param  array|null $ctx     Context (item, diepte, extra).
 * @return mixed
 */
function sfp_page_config_generator_reken( array $b, array $a, $formule, $ctx = null ) {
    $boom = sfp_page_config_reken_boom( $formule );
    if ( null === $boom ) {
        return null;
    }
    return sfp_page_config_reken_uit(
        $boom,
        function ( $naam ) use ( $b, $a, $ctx ) {
            return sfp_page_config_generator_waarde( $b, $a, $naam, $ctx );
        }
    );
}

/**
 * De waarde van een naam in een formule.
 *
 * @param  array      $b    Beschrijving.
 * @param  array      $a    Antwoorden.
 * @param  string     $naam Naam.
 * @param  array|null $ctx  Context.
 * @return mixed
 */
function sfp_page_config_generator_waarde( array $b, array $a, $naam, $ctx ) {
    $diepte = $ctx && isset( $ctx['diepte'] ) ? $ctx['diepte'] : 0;
    if ( $diepte > 20 ) {
        return null;
    }
    if ( $ctx && isset( $ctx['extra'] ) && is_array( $ctx['extra'] ) && array_key_exists( $naam, $ctx['extra'] ) ) {
        return $ctx['extra'][ $naam ];
    }
    $item = $ctx && ! empty( $ctx['item'] ) ? $ctx['item'] : null;
    if ( $item && in_array( $naam, array( 'nr', 'groep', 'groepen' ), true ) && isset( $item[ $naam ] ) ) {
        return (float) $item[ $naam ];
    }
    $velden = sfp_page_config_generator_velden( $b );
    if ( false !== strpos( $naam, '.' ) ) {
        list( $voor, $na ) = explode( '.', $naam, 2 );
        if ( 'item' === $voor ) {
            $obj = $item && isset( $item['obj'] ) && is_array( $item['obj'] ) ? $item['obj'] : array();
            if ( ! array_key_exists( $na, $obj ) ) {
                return null;
            }
            $x = $obj[ $na ];
            return is_scalar( $x ) || ( is_array( $x ) && array_values( $x ) === $x ) ? $x : null;
        }
        if ( ! isset( $velden[ $voor ] ) ) {
            return null;
        }
        $v = $velden[ $voor ];
        $w = sfp_page_config_generator_antwoord( $b, $a, $voor );
        $som = null;
        foreach ( is_array( $w ) ? $w : array( $w ) as $x ) {
            $o = sfp_page_config_generator_optie( $v, $x );
            if ( $o && isset( $o['getallen'][ $na ] ) ) {
                $som = ( null === $som ? 0.0 : $som ) + (float) $o['getallen'][ $na ];
            }
        }
        return $som;
    }
    if ( isset( $velden[ $naam ] ) ) {
        $v = $velden[ $naam ];
        $w = sfp_page_config_generator_antwoord( $b, $a, $naam );
        if ( 'getal' === $v['type'] ) {
            return sfp_page_config_reken_getal( $w );
        }
        if ( 'getallen' === $v['type'] ) {
            return sfp_page_config_generator_getallen( $v, $w );
        }
        return $w;
    }
    foreach ( $b['reken'] as $r ) {
        if ( $r['id'] === $naam ) {
            return sfp_page_config_generator_reken( $b, $a, $r['formule'], array( 'item' => $item, 'diepte' => $diepte + 1, 'extra' => $ctx && isset( $ctx['extra'] ) ? $ctx['extra'] : null ) );
        }
    }
    if ( 'oordeel' === $naam || 'oordeel_tekst' === $naam ) {
        $o = sfp_page_config_generator_oordeel( $b, $a, $ctx );
        return $o ? ( 'oordeel' === $naam ? $o['sleutel'] : $o['tekst'] ) : '';
    }
    $waarden = is_array( $b['waarden'] ) ? $b['waarden'] : array();
    if ( array_key_exists( $naam, $waarden ) ) {
        return sfp_page_config_generator_vul( $waarden[ $naam ], $b, $a, array( 'item' => $item, 'diepte' => $diepte + 1, 'extra' => $ctx && isset( $ctx['extra'] ) ? $ctx['extra'] : null ) );
    }
    return null;
}

/**
 * Het oordeel: het eerste label (hoogste vanaf eerst) waar de uitkomst aan
 * voldoet.
 *
 * @param  array      $b   Beschrijving.
 * @param  array      $a   Antwoorden.
 * @param  array|null $ctx Context.
 * @return array|null
 */
function sfp_page_config_generator_oordeel( array $b, array $a, $ctx = null ) {
    if ( empty( $b['oordeel'] ) ) {
        return null;
    }
    $diepte = $ctx && isset( $ctx['diepte'] ) ? $ctx['diepte'] : 0;
    $n      = sfp_page_config_reken_getal( sfp_page_config_generator_reken( $b, $a, $b['oordeel']['op'], array( 'diepte' => $diepte + 1, 'extra' => $ctx && isset( $ctx['extra'] ) ? $ctx['extra'] : null ) ) );
    if ( null === $n ) {
        return null;
    }
    foreach ( $b['oordeel']['labels'] as $l ) {
        if ( $n >= $l['vanaf'] ) {
            return $l;
        }
    }
    return null;
}

/**
 * De vinkjes die de bezoeker heeft gezet (ids), uit _vink in de antwoorden.
 *
 * @param  array $a Antwoorden.
 * @return string[]
 */
function sfp_page_config_generator_vinkjes( array $a ) {
    $w = isset( $a['_vink'] ) && is_array( $a['_vink'] ) && array_values( $a['_vink'] ) === $a['_vink'] ? $a['_vink'] : array();
    $uit = array();
    foreach ( $w as $x ) {
        if ( is_string( $x ) ) {
            $uit[] = $x;
        }
    }
    return $uit;
}

/**
 * De kaarten van een sectie kaarten: items uit een bank (gefilterd, met
 * voorkeur, terugval, sortering en aantal), om en om over gekozen
 * categorieen, of een aantal lege kaarten.
 *
 * @param  array      $b   Beschrijving.
 * @param  array      $a   Antwoorden.
 * @param  array      $s   Sectie.
 * @param  array|null $ctx Context.
 * @return array[] Objecten.
 */
function sfp_page_config_generator_kaarten( array $b, array $a, array $s, $ctx ) {
    $extra = $ctx && isset( $ctx['extra'] ) ? $ctx['extra'] : null;
    $alle  = array();
    if ( ! empty( $s['bank'] ) ) {
        $banken = is_array( $b['banken'] ) ? $b['banken'] : array();
        $knoop  = isset( $banken[ $s['bank'] ] ) ? $banken[ $s['bank'] ] : null;
        foreach ( isset( $s['pad'] ) ? $s['pad'] : array() as $p ) {
            $k     = sfp_page_config_generator_vul( $p, $b, $a, $ctx );
            $knoop = is_array( $knoop ) && array_values( $knoop ) !== $knoop && array_key_exists( $k, $knoop ) ? $knoop[ $k ] : null;
        }
        if ( is_array( $knoop ) && array_values( $knoop ) === $knoop ) {
            foreach ( $knoop as $x ) {
                if ( is_string( $x ) ) {
                    $alle[] = array( 'tekst' => $x );
                } elseif ( is_array( $x ) && array_values( $x ) !== $x ) {
                    $alle[] = $x;
                }
            }
        }
    }
    $aantal = null;
    if ( isset( $s['aantal'] ) && '' !== $s['aantal'] ) {
        $n      = sfp_page_config_reken_getal( sfp_page_config_generator_reken( $b, $a, $s['aantal'], $ctx ) );
        $aantal = null === $n ? 0 : (int) max( 0, min( 200, floor( $n ) ) );
    }
    $test = function ( $formule, $obj ) use ( $b, $a, $extra ) {
        return sfp_page_config_reken_waar( sfp_page_config_generator_reken( $b, $a, $formule, array( 'item' => array( 'obj' => $obj ), 'extra' => $extra ) ) );
    };
    if ( isset( $s['om_en_om'] ) ) {
        $w     = sfp_page_config_generator_antwoord( $b, $a, $s['om_en_om']['veld'] );
        $cats  = is_array( $w ) ? $w : ( sfp_page_config_generator_leeg( $w ) ? array() : array( $w ) );
        $per   = array();
        foreach ( $cats as $c ) {
            $per[ $c ] = array();
            foreach ( $alle as $x ) {
                if ( isset( $x[ $s['om_en_om']['sleutel'] ] ) && is_scalar( $x[ $s['om_en_om']['sleutel'] ] ) && (string) $x[ $s['om_en_om']['sleutel'] ] === (string) $c ) {
                    $per[ $c ][] = $x;
                }
            }
        }
        $uit = array();
        $nc  = count( $cats );
        for ( $k = 0; $nc && $k < (int) $aantal; $k++ ) {
            $lijst = $per[ $cats[ $k % $nc ] ];
            if ( $lijst ) {
                $uit[] = $lijst[ intdiv( $k, $nc ) % count( $lijst ) ];
            }
        }
        return $uit;
    }
    if ( empty( $s['bank'] ) ) {
        return array_fill( 0, (int) $aantal, array() );
    }
    $lijst = $alle;
    if ( isset( $s['filter'] ) && '' !== $s['filter'] ) {
        $lijst = array_values( array_filter( $lijst, function ( $x ) use ( $test, $s ) { return $test( $s['filter'], $x ); } ) );
    }
    if ( isset( $s['voorkeur'] ) ) {
        $voorkeur = array_values( array_filter( $lijst, function ( $x ) use ( $test, $s ) { return $test( $s['voorkeur']['formule'], $x ); } ) );
        if ( count( $voorkeur ) >= $s['voorkeur']['minimaal'] ) {
            $lijst = $voorkeur;
        }
    }
    if ( ! $lijst && isset( $s['terugval'] ) ) {
        $lijst = '' !== $s['terugval']['filter'] ? array_values( array_filter( $alle, function ( $x ) use ( $test, $s ) { return $test( $s['terugval']['filter'], $x ); } ) ) : $alle;
        if ( $s['terugval']['aantal'] > 0 ) {
            $lijst = array_slice( $lijst, 0, $s['terugval']['aantal'] );
        }
    }
    if ( isset( $s['sorteer'] ) && '' !== $s['sorteer'] ) {
        $sleutels = array();
        foreach ( $lijst as $i => $x ) {
            $n            = sfp_page_config_reken_getal( sfp_page_config_generator_reken( $b, $a, $s['sorteer'], array( 'item' => array( 'obj' => $x ), 'extra' => $extra ) ) );
            $sleutels[ $i ] = array( null === $n ? INF : $n, $i );
        }
        $aflopend = ! empty( $s['aflopend'] );
        $volgorde = array_keys( $lijst );
        usort(
            $volgorde,
            function ( $x, $y ) use ( $sleutels, $aflopend ) {
                $kx = $sleutels[ $x ][0];
                $ky = $sleutels[ $y ][0];
                if ( INF === $kx || INF === $ky ) {
                    $c = ( INF === $kx ? 1 : 0 ) - ( INF === $ky ? 1 : 0 );
                } else {
                    $c = $aflopend ? $ky <=> $kx : $kx <=> $ky;
                }
                return $c ? $c : $x <=> $y;
            }
        );
        $lijst = array_map( function ( $i ) use ( $lijst ) { return $lijst[ $i ]; }, $volgorde );
    }
    if ( null !== $aantal ) {
        $lijst = array_slice( $lijst, 0, $aantal );
    }
    return $lijst;
}

/**
 * De waarde achter een naam: een veld, een afgeleide waarde of het huidige
 * item (@, @label, @uitleg, @nr).
 *
 * @param  array      $b    Beschrijving.
 * @param  array      $a    Antwoorden.
 * @param  string     $naam Naam.
 * @param  array|null $ctx  Item en diepte.
 * @return string|string[]
 */
function sfp_page_config_generator_lees( array $b, array $a, $naam, $ctx ) {
    if ( '@' === substr( $naam, 0, 1 ) ) {
        if ( ! $ctx || empty( $ctx['item'] ) ) {
            return '';
        }
        $item = $ctx['item'];
        $deel = substr( $naam, 1 );
        if ( '' === $deel ) {
            return $item['waarde'];
        }
        if ( 'label' === $deel ) {
            return $item['optie'] ? $item['optie']['tekst'] : $item['waarde'];
        }
        if ( 'uitleg' === $deel ) {
            return $item['optie'] ? $item['optie']['uitleg'] : '';
        }
        if ( 'nr' === $deel ) {
            return (string) $item['nr'];
        }
        return '';
    }
    if ( '=' === substr( $naam, 0, 1 ) ) {
        return sfp_page_config_reken_tekst( sfp_page_config_generator_reken( $b, $a, substr( $naam, 1 ), $ctx ) );
    }
    if ( $ctx && isset( $ctx['extra'] ) && is_array( $ctx['extra'] ) && array_key_exists( $naam, $ctx['extra'] ) ) {
        return sfp_page_config_reken_tekst( $ctx['extra'][ $naam ] );
    }
    $velden = sfp_page_config_generator_velden( $b );
    if ( isset( $velden[ $naam ] ) ) {
        return sfp_page_config_generator_antwoord( $b, $a, $naam );
    }
    $waarden = is_array( $b['waarden'] ) ? $b['waarden'] : array();
    if ( array_key_exists( $naam, $waarden ) ) {
        $diepte = $ctx && isset( $ctx['diepte'] ) ? $ctx['diepte'] : 0;
        if ( $diepte > 4 ) {
            return '';
        }
        return sfp_page_config_generator_vul(
            $waarden[ $naam ],
            $b,
            $a,
            array(
                'item'   => $ctx && isset( $ctx['item'] ) ? $ctx['item'] : null,
                'diepte' => $diepte + 1,
                'extra'  => $ctx && isset( $ctx['extra'] ) ? $ctx['extra'] : null,
            )
        );
    }
    return sfp_page_config_reken_tekst( sfp_page_config_generator_waarde( $b, $a, $naam, $ctx ) );
}

/**
 * Een datum als 3 oktober 2026, met de maandnamen uit de teksten.
 *
 * @param  string $w Datum als JJJJ-MM-DD.
 * @return string
 */
function sfp_page_config_generator_lange_datum( $w ) {
    if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $w, $m ) ) {
        return $w;
    }
    $maanden = explode( ',', (string) sfp_page_config_artikel_tekst( 'tool_maanden' ) );
    $maand   = isset( $maanden[ (int) $m[2] - 1 ] ) ? trim( $maanden[ (int) $m[2] - 1 ] ) : $m[2];
    return (int) $m[3] . ' ' . $maand . ' ' . $m[1];
}

/**
 * Pas één filter toe.
 *
 * @param  array           $b    Beschrijving.
 * @param  array           $a    Antwoorden.
 * @param  string|string[] $w    Waarde.
 * @param  string          $naam Naam van de plaatshouder.
 * @param  string          $f    Filter (met :argument).
 * @param  array|null      $ctx  Context.
 * @return string|string[]
 */
function sfp_page_config_generator_filter( array $b, array $a, $w, $naam, $f, $ctx ) {
    $dp     = strpos( $f, ':' );
    $soort  = false !== $dp ? substr( $f, 0, $dp ) : $f;
    $arg    = false !== $dp ? substr( $f, $dp + 1 ) : '';
    $velden = sfp_page_config_generator_velden( $b );
    $v      = isset( $velden[ $naam ] ) ? $velden[ $naam ] : null;
    if ( 'label' === $soort ) {
        $label = function ( $x ) use ( $v ) {
            $o = sfp_page_config_generator_optie( $v, $x );
            return $o ? $o['tekst'] : $x;
        };
        return is_array( $w ) ? array_map( $label, $w ) : $label( $w );
    }
    if ( 'samen' === $soort ) {
        return is_array( $w ) ? implode( $arg, $w ) : $w;
    }
    if ( 'bank' === $soort ) {
        $banken = is_array( $b['banken'] ) ? $b['banken'] : array();
        $bank   = isset( $banken[ $arg ] ) && is_array( $banken[ $arg ] ) ? $banken[ $arg ] : null;
        $zoek   = function ( $x ) use ( $bank, $b, $a, $ctx ) {
            return $bank && array_values( $bank ) !== $bank && isset( $bank[ $x ] ) && is_string( $bank[ $x ] ) ? sfp_page_config_generator_vul( $bank[ $x ], $b, $a, $ctx ) : '';
        };
        return is_array( $w ) ? array_map( $zoek, $w ) : $zoek( $w );
    }
    if ( is_array( $w ) ) {
        $w = implode( ', ', $w );
    }
    $w = (string) $w;
    if ( in_array( $soort, sfp_page_config_reken_formaten(), true ) ) {
        return sfp_page_config_reken_formaat( $soort, $arg, $w );
    }
    switch ( $soort ) {
        case 'hoofdletter':
        case 'kleinbegin':
            $t = sfp_page_config_generator_tekens( $w );
            foreach ( $t as $i => $c ) {
                if ( mb_strtoupper( $c, 'UTF-8' ) !== mb_strtolower( $c, 'UTF-8' ) ) {
                    $t[ $i ] = 'kleinbegin' === $soort ? mb_strtolower( $c, 'UTF-8' ) : mb_strtoupper( $c, 'UTF-8' );
                    break;
                }
            }
            return implode( '', $t );
        case 'slug':
            $s = class_exists( 'Normalizer' ) ? Normalizer::normalize( $w, Normalizer::FORM_D ) : $w;
            $s = preg_replace( '/\p{Mn}+/u', '', (string) $s );
            $s = preg_replace( '/[^a-z0-9]+/', '-', mb_strtolower( (string) $s, 'UTF-8' ) );
            return trim( (string) $s, '-' );
        case 'klein':
            return mb_strtolower( $w, 'UTF-8' );
        case 'zin':
            $w = sfp_page_config_generator_trim( $w );
            return ( '' === $w || preg_match( '/[.!?…:]$/u', $w ) ) ? $w : $w . '.';
        case 'eerstezin':
            $w = sfp_page_config_generator_trim( $w );
            return preg_match( '/(*UCP)^[\s\S]*?[.!?](?=\s|$)/u', $w, $m ) ? $m[0] : $w;
        case 'eersteregel':
            $regels = preg_split( '/\r?\n/', sfp_page_config_generator_trim( $w ) );
            return sfp_page_config_generator_trim( $regels[0] );
        case 'kort':
            $n      = (int) $arg > 0 ? (int) $arg : 60;
            $tekens = sfp_page_config_generator_tekens( sfp_page_config_generator_trim( $w ) );
            if ( count( $tekens ) <= $n ) {
                return implode( '', $tekens );
            }
            $knip   = implode( '', array_slice( $tekens, 0, $n ) );
            $spatie = mb_strrpos( $knip, ' ', 0, 'UTF-8' );
            $deel   = ( false !== $spatie && $spatie > $n / 2 ) ? mb_substr( $knip, 0, $spatie, 'UTF-8' ) : $knip;
            return preg_replace( '/(*UCP)[\s,;:.]+$/u', '', $deel ) . '…';
        case 'datum':
            return sfp_page_config_generator_lange_datum( $w );
        case 'anders':
            return '' === sfp_page_config_generator_trim( $w ) ? sfp_page_config_generator_vul( $arg, $b, $a, $ctx ) : $w;
        case 'voor':
            return '' === sfp_page_config_generator_trim( $w ) ? $w : $arg . $w;
        case 'na':
            return '' === sfp_page_config_generator_trim( $w ) ? $w : $w . $arg;
        case 'of':
            if ( '' !== sfp_page_config_generator_trim( $w ) ) {
                return $w;
            }
            $ander = sfp_page_config_generator_lees( $b, $a, $arg, $ctx );
            return is_array( $ander ) ? implode( ', ', $ander ) : (string) $ander;
    }
    return $w;
}

/**
 * Spaties weghalen zoals trim() in de browser (ook harde spaties).
 *
 * @param  string $s Tekst.
 * @return string
 */
function sfp_page_config_generator_trim( $s ) {
    return (string) preg_replace( '/^[\s\p{Zs}\x{FEFF}\x{2028}\x{2029}]+|[\s\p{Zs}\x{FEFF}\x{2028}\x{2029}]+$/u', '', (string) $s );
}

/**
 * Vul de plaatshouders {naam|filter|filter:arg} in een sjabloon.
 *
 * @param  string     $sjabloon Sjabloon.
 * @param  array      $b        Beschrijving.
 * @param  array      $a        Antwoorden.
 * @param  array|null $ctx      Context.
 * @return string
 */
function sfp_page_config_generator_vul( $sjabloon, array $b, array $a, $ctx = null ) {
    return (string) preg_replace_callback(
        '/\{(@?[a-zA-Z0-9_.]*|=[^{}|]+)((?:\|[^{}|]+)*)\}/u',
        function ( $m ) use ( $b, $a, $ctx ) {
            if ( '' === $m[1] ) {
                return $m[0];
            }
            $w = sfp_page_config_generator_lees( $b, $a, $m[1], $ctx );
            if ( '' !== $m[2] ) {
                foreach ( explode( '|', substr( $m[2], 1 ) ) as $f ) {
                    $w = sfp_page_config_generator_filter( $b, $a, $w, $m[1], $f, $ctx );
                }
            }
            return is_array( $w ) ? implode( ', ', $w ) : (string) $w;
        },
        (string) $sjabloon
    );
}

/**
 * Een voorwaarde: { veld, is | niet | in | gevuld | bevat }, een lijst
 * (alle moeten kloppen) of { of: [...] }.
 *
 * @param  array      $b   Beschrijving.
 * @param  array      $a   Antwoorden.
 * @param  mixed      $als Voorwaarde.
 * @param  array|null $ctx Context.
 * @return bool
 */
function sfp_page_config_generator_klopt( array $b, array $a, $als, $ctx = null ) {
    if ( ! $als || ! is_array( $als ) ) {
        return true;
    }
    if ( array_values( $als ) === $als ) {
        foreach ( $als as $x ) {
            if ( ! sfp_page_config_generator_klopt( $b, $a, $x, $ctx ) ) {
                return false;
            }
        }
        return true;
    }
    if ( isset( $als['of'] ) && is_array( $als['of'] ) && array_values( $als['of'] ) === $als['of'] ) {
        foreach ( $als['of'] as $x ) {
            if ( sfp_page_config_generator_klopt( $b, $a, $x, $ctx ) ) {
                return true;
            }
        }
        return false;
    }
    if ( isset( $als['formule'] ) && is_scalar( $als['formule'] ) ) {
        return sfp_page_config_reken_waar( sfp_page_config_generator_reken( $b, $a, (string) $als['formule'], $ctx ) );
    }
    $w     = sfp_page_config_generator_lees( $b, $a, isset( $als['veld'] ) && is_scalar( $als['veld'] ) ? (string) $als['veld'] : '', $ctx );
    $bevat = function ( $x ) use ( $w ) {
        $x = is_bool( $x ) ? ( $x ? 'true' : 'false' ) : ( is_scalar( $x ) ? sfp_page_config_generator_als_tekst( is_string( $x ) ? $x : ( is_int( $x ) || is_float( $x ) ? $x : (string) $x ) ) : '' );
        return is_array( $w ) ? in_array( $x, $w, true ) : $w === $x;
    };
    if ( array_key_exists( 'is', $als ) ) {
        return $bevat( $als['is'] );
    }
    if ( array_key_exists( 'niet', $als ) ) {
        return ! $bevat( $als['niet'] );
    }
    if ( isset( $als['in'] ) && is_array( $als['in'] ) && array_values( $als['in'] ) === $als['in'] ) {
        foreach ( $als['in'] as $x ) {
            if ( $bevat( $x ) ) {
                return true;
            }
        }
        return false;
    }
    if ( array_key_exists( 'gevuld', $als ) ) {
        return (bool) $als['gevuld'] === ! sfp_page_config_generator_leeg( $w );
    }
    if ( array_key_exists( 'bevat', $als ) ) {
        if ( is_array( $w ) ) {
            return $bevat( $als['bevat'] );
        }
        $x = is_scalar( $als['bevat'] ) ? (string) $als['bevat'] : '';
        return '' === $x || false !== strpos( (string) $w, $x );
    }
    return true;
}

/**
 * De items van een lijst: vaste sjablonen, een bank op een pad of de regels
 * van een tekstvak.
 *
 * @param  array      $b   Beschrijving.
 * @param  array      $a   Antwoorden.
 * @param  array      $s   Sectie.
 * @param  array|null $ctx Context.
 * @return string[]
 */
function sfp_page_config_generator_items( array $b, array $a, array $s, $ctx, &$ids = null, $pad = '' ) {
    $uit  = array();
    $ids  = array();
    $nr   = 0;
    $elk  = function ( $w ) use ( $s, $b, $a, $ctx ) {
        if ( empty( $s['elk'] ) ) {
            return $w;
        }
        foreach ( explode( '|', $s['elk'] ) as $f ) {
            $w = sfp_page_config_generator_filter( $b, $a, $w, '', $f, $ctx );
            $w = is_array( $w ) ? implode( ', ', $w ) : (string) $w;
        }
        return $w;
    };
    $voeg = function ( $x ) use ( &$uit, &$ids, &$nr, $b, $a, $ctx, $elk, $pad ) {
        $id = $pad . '.' . $nr;
        $nr++;
        if ( is_array( $x ) && array_values( $x ) !== $x ) {
            if ( ! sfp_page_config_generator_klopt( $b, $a, isset( $x['als'] ) ? $x['als'] : null, $ctx ) ) {
                return;
            }
            if ( isset( $x['id'] ) && is_scalar( $x['id'] ) && '' !== (string) $x['id'] ) {
                $id = (string) $x['id'];
            }
            $x = isset( $x['tekst'] ) ? $x['tekst'] : null;
        }
        if ( ! is_string( $x ) ) {
            return;
        }
        $w = sfp_page_config_generator_trim( sfp_page_config_generator_vul( $x, $b, $a, $ctx ) );
        if ( '' !== $w ) {
            $uit[] = $elk( $w );
            $ids[] = $id;
        }
    };
    if ( isset( $s['items'] ) && is_array( $s['items'] ) ) {
        foreach ( $s['items'] as $x ) {
            $voeg( $x );
        }
    }
    if ( ! empty( $s['bank'] ) ) {
        $banken = is_array( $b['banken'] ) ? $b['banken'] : array();
        $knoop  = isset( $banken[ $s['bank'] ] ) ? $banken[ $s['bank'] ] : null;
        foreach ( isset( $s['pad'] ) ? $s['pad'] : array() as $p ) {
            $k     = sfp_page_config_generator_vul( $p, $b, $a, $ctx );
            $knoop = ( $knoop instanceof stdClass ) ? null : $knoop;
            $knoop = is_array( $knoop ) && array_values( $knoop ) !== $knoop && array_key_exists( $k, $knoop ) ? $knoop[ $k ] : null;
        }
        if ( is_array( $knoop ) && array_values( $knoop ) === $knoop ) {
            foreach ( $knoop as $x ) {
                $voeg( $x );
            }
        } elseif ( is_string( $knoop ) ) {
            $voeg( $knoop );
        }
    }
    if ( isset( $s['regels'] ) && is_string( $s['regels'] ) && '' !== $s['regels'] ) {
        $tekst = sfp_page_config_generator_lees( $b, $a, $s['regels'], $ctx );
        $tekst = is_array( $tekst ) ? '' : (string) $tekst;
        foreach ( preg_split( '/\r?\n/', $tekst ) as $r ) {
            $r = preg_replace( '/(*UCP)^[-*•]\s*/u', '', sfp_page_config_generator_trim( $r ) );
            if ( '' !== $r ) {
                $uit[] = ( isset( $s['voor'] ) ? sfp_page_config_generator_vul( $s['voor'], $b, $a, $ctx ) : '' ) . $elk( $r );
                $ids[] = $pad . '.r' . count( $uit );
            }
        }
    }
    return $uit;
}

/**
 * Het resultaat als blokken, gelijk aan bouw() in generator.js.
 *
 * @param  array $b Beschrijving (genormaliseerd).
 * @param  array $a Antwoorden.
 * @return array[]
 */
function sfp_page_config_generator_bouw( array $b, array $a ) {
    $blokken = array();
    $velden  = sfp_page_config_generator_velden( $b );
    $sectie  = function ( $s, $ctx, $pad ) use ( &$sectie, &$blokken, $b, $a, $velden ) {
        if ( ! $s || ! sfp_page_config_generator_klopt( $b, $a, isset( $s['als'] ) ? $s['als'] : null, $ctx ) ) {
            return;
        }
        $kop  = isset( $s['kop'] ) && '' !== $s['kop'] ? sfp_page_config_generator_trim( sfp_page_config_generator_vul( $s['kop'], $b, $a, $ctx ) ) : '';
        $type = isset( $s['type'] ) ? $s['type'] : 'tekst';
        if ( 'herhaal' === $type ) {
            $over    = isset( $s['over'] ) ? $s['over'] : '';
            $v       = isset( $velden[ $over ] ) ? $velden[ $over ] : null;
            $gekozen = $v ? sfp_page_config_generator_antwoord( $b, $a, $over ) : array();
            if ( ! is_array( $gekozen ) ) {
                $gekozen = sfp_page_config_generator_leeg( $gekozen ) ? array() : array( $gekozen );
            }
            foreach ( $gekozen as $i => $w ) {
                $c  = array(
                    'item' => array(
                        'waarde' => $w,
                        'optie'  => sfp_page_config_generator_optie( $v, $w ),
                        'nr'     => $i + 1,
                    ),
                );
                $ik = isset( $s['kop'] ) && '' !== $s['kop'] ? sfp_page_config_generator_trim( sfp_page_config_generator_vul( $s['kop'], $b, $a, $c ) ) : '';
                if ( '' !== $ik ) {
                    $blokken[] = array( 'type' => 'subkop', 'tekst' => $ik );
                }
                foreach ( isset( $s['inhoud'] ) ? $s['inhoud'] : array() as $j => $x ) {
                    $sectie( $x, $c, $pad . '.' . $w . '.' . $j );
                }
            }
            return;
        }
        if ( 'kaarten' === $type ) {
            $lijst = sfp_page_config_generator_kaarten( $b, $a, $s, $ctx );
            if ( ! $lijst ) {
                if ( ! empty( $s['leeg'] ) ) {
                    if ( '' !== $kop ) {
                        $blokken[] = array( 'type' => 'subkop', 'tekst' => $kop );
                    }
                    $blokken[] = array( 'type' => 'tekst', 'tekst' => sfp_page_config_generator_vul( $s['leeg'], $b, $a, $ctx ) );
                }
                return;
            }
            if ( '' !== $kop ) {
                $blokken[] = array( 'type' => 'subkop', 'tekst' => $kop );
            }
            $extra = $ctx && isset( $ctx['extra'] ) ? $ctx['extra'] : null;
            $per   = 0;
            if ( isset( $s['groeperen'] ) ) {
                $n   = sfp_page_config_reken_getal( sfp_page_config_generator_reken( $b, $a, $s['groeperen']['per'], $ctx ) );
                $per = null === $n ? 0 : (int) max( 0, floor( $n ) );
            }
            $groepen = $per > 0 ? (int) ceil( count( $lijst ) / $per ) : 0;
            foreach ( $lijst as $k => $obj ) {
                $c = array(
                    'item'  => array(
                        'obj'     => $obj,
                        'waarde'  => (string) ( $k + 1 ),
                        'optie'   => null,
                        'nr'      => $k + 1,
                        'groep'   => $per > 0 ? intdiv( $k, $per ) + 1 : 0,
                        'groepen' => $groepen,
                    ),
                    'extra' => $extra,
                );
                if ( $per > 0 && 0 === $k % $per ) {
                    $gk = sfp_page_config_generator_trim( sfp_page_config_generator_vul( $s['groeperen']['kop'], $b, $a, $c ) );
                    if ( '' !== $gk ) {
                        $blokken[] = array( 'type' => 'subkop', 'tekst' => $gk );
                    }
                }
                if ( ! empty( $s['kaartkop'] ) ) {
                    $kk = sfp_page_config_generator_trim( sfp_page_config_generator_vul( $s['kaartkop'], $b, $a, $c ) );
                    if ( '' !== $kk ) {
                        $blokken[] = array( 'type' => 'subkop', 'tekst' => $kk );
                    }
                }
                foreach ( isset( $s['inhoud'] ) ? $s['inhoud'] : array() as $j => $x ) {
                    $sectie( $x, $c, $pad . '.' . ( $k + 1 ) . '.' . $j );
                }
            }
            return;
        }
        if ( 'getal' === $type ) {
            $w = sfp_page_config_generator_reken( $b, $a, isset( $s['waarde'] ) ? $s['waarde'] : '', $ctx );
            $t = sfp_page_config_reken_tekst( $w );
            if ( '' === $t || is_array( $w ) ) {
                return;
            }
            foreach ( isset( $s['formaat'] ) && '' !== $s['formaat'] ? explode( '|', $s['formaat'] ) : array() as $f ) {
                $t = sfp_page_config_generator_filter( $b, $a, $t, '', $f, $ctx );
                $t = is_array( $t ) ? implode( ', ', $t ) : (string) $t;
            }
            if ( '' !== $kop ) {
                $blokken[] = array( 'type' => 'subkop', 'tekst' => $kop );
            }
            $blokken[] = array(
                'type'  => 'getal',
                'label' => sfp_page_config_generator_trim( sfp_page_config_generator_vul( isset( $s['label'] ) ? $s['label'] : '', $b, $a, $ctx ) ),
                'tekst' => $t,
                'onder' => sfp_page_config_generator_trim( sfp_page_config_generator_vul( isset( $s['onder'] ) ? $s['onder'] : '', $b, $a, $ctx ) ),
            );
            return;
        }
        if ( 'verdeling' === $type ) {
            $delen  = array();
            $totaal = 0.0;
            foreach ( isset( $s['delen'] ) ? $s['delen'] : array() as $d ) {
                $n       = sfp_page_config_reken_getal( sfp_page_config_generator_reken( $b, $a, $d['waarde'], $ctx ) );
                $n       = null === $n ? 0.0 : max( 0.0, $n );
                $totaal += $n;
                $delen[] = array( sfp_page_config_generator_trim( sfp_page_config_generator_vul( $d['label'], $b, $a, $ctx ) ), $n );
            }
            if ( $totaal <= 0 ) {
                return;
            }
            $rijen = array();
            foreach ( $delen as $d ) {
                $t = sfp_page_config_reken_tekst( $d[1] );
                foreach ( isset( $s['formaat'] ) && '' !== $s['formaat'] ? explode( '|', $s['formaat'] ) : array() as $f ) {
                    $t = (string) sfp_page_config_generator_filter( $b, $a, $t, '', $f, $ctx );
                }
                $rijen[] = array( $d[0], $t, (int) sfp_page_config_reken_afronden( $d[1] / $totaal * 100, 0 ) );
            }
            if ( '' !== $kop ) {
                $blokken[] = array( 'type' => 'subkop', 'tekst' => $kop );
            }
            $blokken[] = array( 'type' => 'verdeling', 'rijen' => $rijen );
            return;
        }
        if ( 'tekst' === $type ) {
            $tekst = sfp_page_config_generator_trim( sfp_page_config_generator_vul( isset( $s['tekst'] ) ? $s['tekst'] : '', $b, $a, $ctx ) );
            if ( '' === $tekst && empty( $s['altijd'] ) ) {
                return;
            }
            if ( '' !== $kop ) {
                $blokken[] = array( 'type' => 'subkop', 'tekst' => $kop );
            }
            if ( '' !== $tekst ) {
                $blokken[] = array( 'type' => 'tekst', 'tekst' => $tekst );
            }
            return;
        }
        if ( 'gegevens' === $type ) {
            $rijen = array();
            foreach ( isset( $s['regels'] ) && is_array( $s['regels'] ) ? $s['regels'] : array() as $r ) {
                if ( ! sfp_page_config_generator_klopt( $b, $a, isset( $r['als'] ) ? $r['als'] : null, $ctx ) ) {
                    continue;
                }
                $w = sfp_page_config_generator_trim( sfp_page_config_generator_vul( $r['tekst'], $b, $a, $ctx ) );
                if ( '' !== $w ) {
                    $rijen[] = array( sfp_page_config_generator_trim( sfp_page_config_generator_vul( $r['label'], $b, $a, $ctx ) ), $w );
                }
            }
            if ( ! $rijen ) {
                return;
            }
            if ( '' !== $kop ) {
                $blokken[] = array( 'type' => 'subkop', 'tekst' => $kop );
            }
            $blokken[] = array( 'type' => 'tabel', 'rijen' => $rijen );
            return;
        }
        $ids   = array();
        $lijst = sfp_page_config_generator_items( $b, $a, $s, $ctx, $ids, $pad );
        if ( ! $lijst ) {
            if ( ! empty( $s['leeg'] ) ) {
                if ( '' !== $kop ) {
                    $blokken[] = array( 'type' => 'subkop', 'tekst' => $kop );
                }
                $blokken[] = array( 'type' => 'tekst', 'tekst' => sfp_page_config_generator_vul( $s['leeg'], $b, $a, $ctx ) );
            }
            return;
        }
        if ( '' !== $kop ) {
            $blokken[] = array( 'type' => 'subkop', 'tekst' => $kop );
        }
        if ( 'afvink' === $type ) {
            $gezet     = sfp_page_config_generator_vinkjes( $a );
            $blokken[] = array(
                'type'  => 'afvink',
                'items' => $lijst,
                'ids'   => $ids,
                'aan'   => array_map(
                    function ( $id ) use ( $gezet ) {
                        return in_array( $id, $gezet, true );
                    },
                    $ids
                ),
            );
            return;
        }
        if ( 'vragen' === $type ) {
            $blokken[] = array(
                'type'    => 'invul',
                'items'   => $lijst,
                'nummers' => ! isset( $s['nummers'] ) || false !== $s['nummers'],
                'sleutel' => $pad,
                'lijnen'  => isset( $s['lijnen'] ) ? max( 0, min( 12, (int) $s['lijnen'] ) ) : 3,
            );
            return;
        }
        if ( 'alineas' === $type ) {
            foreach ( $lijst as $x ) {
                $blokken[] = array( 'type' => 'tekst', 'tekst' => $x );
            }
            return;
        }
        $blokken[] = array(
            'type'    => 'lijst',
            'items'   => $lijst,
            'nummers' => ! empty( $s['nummers'] ),
        );
    };
    foreach ( $b['uitvoer'] as $i => $s ) {
        $sectie( $s, null, (string) $i );
    }
    return $blokken;
}

/**
 * Welke velden in een stap nog ontbreken (ids), gelijk aan de browser.
 *
 * @param  array $b    Beschrijving.
 * @param  array $a    Antwoorden.
 * @param  array $stap Stap.
 * @return string[]
 */
function sfp_page_config_generator_ontbreekt( array $b, array $a, array $stap ) {
    $uit = array();
    foreach ( $stap['velden'] as $v ) {
        $zichtbaar = sfp_page_config_generator_klopt( $b, $a, isset( $v['toon_als'] ) ? $v['toon_als'] : null );
        $w         = sfp_page_config_generator_antwoord( $b, $a, $v['id'] );
        if ( 'vinkjes' === $v['type'] && $zichtbaar && ( isset( $v['min'] ) ? $v['min'] : 0 ) > count( $w ) ) {
            $uit[] = $v['id'];
            continue;
        }
        if ( 'regels' === $v['type'] && $zichtbaar && ( isset( $v['min'] ) ? $v['min'] : 0 ) > count( array_filter( preg_split( '/\r?\n/', (string) $w ), function ( $r ) { return '' !== sfp_page_config_generator_trim( $r ); } ) ) ) {
            $uit[] = $v['id'];
            continue;
        }
        $verplicht = $zichtbaar && ( true === $v['verplicht'] || ( is_array( $v['verplicht'] ) && sfp_page_config_generator_klopt( $b, $a, $v['verplicht'] ) ) );
        if ( $verplicht && sfp_page_config_generator_leeg( $w ) ) {
            $uit[] = $v['id'];
        }
    }
    $index = array_search( $stap, $b['stappen'], true );
    foreach ( isset( $b['controles'] ) ? $b['controles'] : array() as $i => $c ) {
        if ( false !== $index && $c['stap'] === $index + 1 && sfp_page_config_reken_waar( sfp_page_config_generator_reken( $b, $a, $c['formule'] ) ) ) {
            $uit[] = '!' . $i;
        }
    }
    return $uit;
}

/**
 * De antwoorden die mee mogen in een mail: alleen velden van deze tool,
 * keuzes uit de opties en korte velden afgekapt. Tekstvakken gaan niet mee,
 * tenzij het veld mail aan heeft; zo laat niemand eigen lappen tekst via de
 * site naar een willekeurig adres mailen.
 *
 * @param  array $b          Beschrijving.
 * @param  array $antwoorden Ruwe antwoorden.
 * @return array
 */
function sfp_page_config_generator_mail_antwoorden( array $b, array $antwoorden ) {
    $schoon = array();
    foreach ( sfp_page_config_generator_velden( $b ) as $id => $v ) {
        if ( ! $v['mail'] || ! array_key_exists( $id, $antwoorden ) ) {
            continue;
        }
        $w = sfp_page_config_generator_antwoord( $b, $antwoorden, $id );
        if ( is_string( $w ) && in_array( $v['type'], array( 'kort', 'tekstvak', 'regels', 'getallen' ), true ) ) {
            $w = implode( '', array_slice( sfp_page_config_generator_tekens( $w ), 0, 'kort' === $v['type'] ? 80 : ( 'getallen' === $v['type'] ? 2000 : 400 ) ) );
        }
        if ( ! sfp_page_config_generator_leeg( $w ) ) {
            $schoon[ $id ] = $w;
        }
    }
    $vink = array();
    foreach ( array_slice( sfp_page_config_generator_vinkjes( $antwoorden ), 0, 500 ) as $x ) {
        $vink[] = implode( '', array_slice( sfp_page_config_generator_tekens( $x ), 0, 80 ) );
    }
    if ( $vink ) {
        $schoon['_vink'] = $vink;
    }
    return $schoon;
}

/**
 * Het resultaat als platte tekst, voor de mail.
 *
 * @param  array  $blokken Blokken.
 * @param  string $titel   Titel van de tool.
 * @return string
 */
function sfp_page_config_generator_tekst( array $blokken, $titel ) {
    $regels = array( $titel, str_repeat( '=', min( 60, max( 10, mb_strlen( $titel ) ) ) ), '' );
    foreach ( $blokken as $bl ) {
        switch ( $bl['type'] ) {
            case 'subkop':
                $regels[] = '';
                $regels[] = mb_strtoupper( $bl['tekst'], 'UTF-8' );
                break;
            case 'tekst':
                $regels[] = $bl['tekst'];
                $regels[] = '';
                break;
            case 'tabel':
                foreach ( $bl['rijen'] as $r ) {
                    $regels[] = $r[0] . ': ' . $r[1];
                }
                $regels[] = '';
                break;
            case 'getal':
                $regels[] = ( '' !== $bl['label'] ? $bl['label'] . ': ' : '' ) . $bl['tekst'];
                if ( '' !== $bl['onder'] ) {
                    $regels[] = $bl['onder'];
                }
                $regels[] = '';
                break;
            case 'verdeling':
                foreach ( $bl['rijen'] as $r ) {
                    $regels[] = $r[0] . ': ' . $r[1] . ' (' . $r[2] . '%)';
                }
                $regels[] = '';
                break;
            default:
                foreach ( $bl['items'] as $i => $it ) {
                    $regels[] = ( 'afvink' === $bl['type'] ? ( ! empty( $bl['aan'][ $i ] ) ? '[x] ' : '[ ] ' ) : ( ! empty( $bl['nummers'] ) ? ( $i + 1 ) . '. ' : '- ' ) ) . $it;
                }
                $regels[] = '';
        }
    }
    return trim( preg_replace( "/\n{3,}/", "\n\n", implode( "\n", $regels ) ) ) . "\n";
}
