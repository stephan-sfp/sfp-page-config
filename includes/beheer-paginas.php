<?php
/**
 * SFP Page Config - Pagina's: wat er per pagina is ingesteld en wat ontbreekt
 *
 * Eén controle over alle gepubliceerde pagina's, berichten en begrippen. Ze
 * meldt wat tot en met 2.12.0 stil misging of op een andere plek stond:
 *
 *   knop        de knop onderaan staat aan, maar de pagina heeft geen
 *               formulier en geen andere bestemming
 *   inhoud      de inhoudsopgave staat aan, maar de pagina heeft minder dan
 *               twee tussenkoppen
 *   data        een training zonder startmomenten, of met alleen
 *               startmomenten die voorbij zijn
 *   invul       tekst uit het sjabloon die niet is ingevuld (was het tabblad
 *               Invulplekken)
 *   beeld       een artikel in de artikelopmaak zonder uitgelichte afbeelding
 *
 * Een melding die bewust zo is, kun je per pagina wegklikken. De keuze staat
 * in de meta `_sfp_controle_negeer`. Bij sjabloontekst geldt de keuze voor de
 * gevonden tekst: komt er andere sjabloontekst bij, dan meldt de controle
 * die weer.
 *
 * @package SFP_Page_Config
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De soorten pagina met hun naam en wat de keuze verandert.
 *
 * @return array<string, array{naam: string, uitleg: string}>
 */
function sfp_page_config_soorten() {
    return array(
        ''          => array(
            'naam'   => 'Gewone pagina',
            'uitleg' => 'Geen extra\'s.',
        ),
        'coaching'  => array(
            'naam'   => 'Coaching',
            'uitleg' => 'Krijgt de opmaak van een dienstpagina en de knop onderaan.',
        ),
        'training'  => array(
            'naam'   => 'Training',
            'uitleg' => 'Krijgt de opmaak van een dienstpagina, de knop onderaan en startmomenten.',
        ),
        'incompany' => array(
            'naam'   => 'Incompany',
            'uitleg' => 'Krijgt de opmaak van een dienstpagina en de knop onderaan.',
        ),
    );
}

/**
 * Heeft de inhoud een formulier of boekkaart waar de knop onderaan heen kan?
 *
 * Zelfde volgorde als assets/sticky-cta.js: een bekend anker, de boekkaart,
 * een formulier. Herbruikbare blokken worden één niveau diep meegelezen.
 *
 * @param  string   $content Ruwe inhoud.
 * @param  string[] $ankers  Ankers waaraan het formulier te herkennen is.
 * @return bool
 */
function sfp_page_config_inhoud_heeft_formulier( $content, array $ankers ) {
    $content = (string) $content;
    if ( preg_match_all( '/<!-- wp:block \{"ref":(\d+)/', $content, $refs ) ) {
        foreach ( array_unique( $refs[1] ) as $ref ) {
            $blok = get_post( (int) $ref );
            if ( $blok && 'wp_block' === $blok->post_type ) {
                $content .= "\n" . $blok->post_content;
            }
        }
    }
    foreach ( $ankers as $anker ) {
        $anker = preg_quote( $anker, '/' );
        if ( preg_match( '/\bid=["\']' . $anker . '["\']/', $content ) || preg_match( '/"(?:anker|anchor)":"' . $anker . '"/', $content ) ) {
            return true;
        }
    }
    // Alleen wat het script ook als formulier vindt. Een boekknop van
    // LatePoint die een venster opent, is geen formulier op de pagina.
    $kenmerken = array( 'sfp-boek-sectie', '<!-- wp:srfm/form', '[sureforms', '[latepoint_book_form', '<!-- wp:latepoint/book-form', 'latepoint-book-form-wrapper', 'srfm-form-container', '<form' );
    foreach ( $kenmerken as $kenmerk ) {
        if ( false !== strpos( $content, $kenmerk ) ) {
            return true;
        }
    }
    return false;
}

/**
 * Aantal tussenkoppen (H2) in de inhoud.
 *
 * @param  string $content Ruwe inhoud.
 * @return int
 */
function sfp_page_config_aantal_tussenkoppen( $content ) {
    return (int) preg_match_all( '/<h2\b/i', (string) $content );
}

/**
 * Controleer één pagina.
 *
 * @param  WP_Post $post Pagina, bericht of begrip.
 * @return array<int, array{code: string, kort: string, tekst: string, actie: string}>
 */
function sfp_page_config_controleer_pagina( $post ) {
    $meldingen = array();
    $soort     = (string) get_post_meta( $post->ID, 'sfp_page_type', true );
    $artikel   = sfp_page_config_onderdeel_aan( 'artikel' ) && function_exists( 'sfp_page_config_artikel_posttypes' ) && in_array( $post->post_type, sfp_page_config_artikel_posttypes(), true );

    // De knop onderaan.
    if ( '' !== $soort && sfp_page_config_onderdeel_aan( 'knop' ) && function_exists( 'sfp_page_config_knop_voor_pagina' ) ) {
        $knop = sfp_page_config_knop_voor_pagina( $post->ID, $soort );
        if ( $knop && 'form' === $knop['doel'] && ! sfp_page_config_inhoud_heeft_formulier( $post->post_content, $knop['ankers'] ) ) {
            $meldingen[] = array(
                'code'  => 'knop',
                'kort'  => 'Geen formulier voor de knop',
                'tekst' => 'De knop onderaan springt naar het formulier op de pagina, maar er staat geen formulier op. De knop is daarom niet te zien.',
                'actie' => 'bestemming',
            );
        }
    }

    // De inhoudsopgave die meeloopt.
    if ( '' === $soort && ! $artikel && in_array( $post->post_type, array( 'post', 'page' ), true )
        && ( '1' === get_post_meta( $post->ID, 'sfp_longread', true ) || '1' === get_post_meta( $post->ID, 'longread', true ) ) ) {
        $koppen = sfp_page_config_aantal_tussenkoppen( $post->post_content );
        if ( $koppen < 2 ) {
            $meldingen[] = array(
                'code'  => 'inhoud',
                'kort'  => 'Te weinig tussenkoppen',
                'tekst' => ( 1 === $koppen ? 'Deze pagina heeft één tussenkop.' : 'Deze pagina heeft geen tussenkoppen.' ) . ' Een inhoudsopgave verschijnt vanaf twee.',
                'actie' => 'negeer',
            );
        }
    }

    // Startmomenten van een training.
    if ( 'training' === $soort && 'page' === $post->post_type && sfp_page_config_onderdeel_aan( 'cursusdata' ) ) {
        $json   = get_post_meta( $post->ID, 'sfp_cursusdata', true );
        $data   = $json ? json_decode( $json, true ) : array();
        $laatst = '';
        foreach ( is_array( $data ) ? $data : array() as $sm ) {
            foreach ( isset( $sm['data'] ) && is_array( $sm['data'] ) ? $sm['data'] : array() as $dag ) {
                if ( (string) $dag > $laatst ) {
                    $laatst = (string) $dag;
                }
            }
        }
        if ( '' === $laatst ) {
            $meldingen[] = array(
                'code'  => 'data',
                'kort'  => 'Geen startmomenten',
                'tekst' => 'Deze training heeft geen startmomenten. Bezoekers zien geen data en het aanmeldformulier toont een melding in plaats van de keuzelijst.',
                'actie' => 'cursusdata',
            );
        } elseif ( $laatst < wp_date( 'Y-m-d' ) ) {
            $meldingen[] = array(
                'code'  => 'data',
                'kort'  => 'Startmomenten zijn voorbij',
                'tekst' => 'De laatste dag van deze training was ' . sfp_page_config_format_date_nl( $laatst, 'long' ) . '. Alle startmomenten zijn voorbij.',
                'actie' => 'cursusdata',
            );
        }
    }

    // Tekst uit het sjabloon.
    $invul = function_exists( 'sfp_page_config_find_placeholders' ) ? sfp_page_config_find_placeholders( $post->post_content ) : array();
    if ( $invul ) {
        $meldingen[] = array(
            'code'  => 'invul:' . substr( md5( implode( '|', $invul ) ), 0, 10 ),
            'kort'  => 'Sjabloontekst niet ingevuld',
            'tekst' => 'Op deze pagina staat tekst uit het sjabloon die niet is ingevuld: ' . implode( ', ', array_slice( $invul, 0, 6 ) ) . ( count( $invul ) > 6 ? ' en nog ' . ( count( $invul ) - 6 ) . ' meer' : '' ) . '.',
            'actie' => 'negeer',
        );
    }

    // Artikel zonder uitgelichte afbeelding.
    if ( $artikel && 'begrippen' !== $post->post_type && ! has_post_thumbnail( $post ) ) {
        $meldingen[] = array(
            'code'  => 'beeld',
            'kort'  => 'Geen uitgelichte afbeelding',
            'tekst' => 'Dit artikel heeft geen uitgelichte afbeelding. Die is nodig voor Lees ook, de overzichten en het delen.',
            'actie' => 'editor',
        );
    }

    // Pijler: categorie, lead en artikelen.
    if ( function_exists( 'sfp_page_config_pijler_controle' ) ) {
        $meldingen = array_merge( $meldingen, sfp_page_config_pijler_controle( $post ) );
    }
    if ( function_exists( 'sfp_page_config_begrip_controle' ) ) {
        $meldingen = array_merge( $meldingen, sfp_page_config_begrip_controle( $post ) );
    }
    if ( function_exists( 'sfp_page_config_tool_controle' ) ) {
        $meldingen = array_merge( $meldingen, sfp_page_config_tool_controle( $post ) );
    }

    // Wat bewust zo is, telt niet mee.
    $negeer = sfp_page_config_controle_genegeerd( $post->ID );
    if ( $negeer ) {
        $meldingen = array_values(
            array_filter(
                $meldingen,
                function ( $melding ) use ( $negeer ) {
                    return ! in_array( $melding['code'], $negeer, true );
                }
            )
        );
    }
    return $meldingen;
}

/**
 * De meldingen die voor deze pagina zijn weggeklikt.
 *
 * Een training die tot en met 2.12.0 op het tabblad Cursusdata was verborgen
 * (optie `sfp_cursusdata_hidden`), telt als "startmomenten bewust leeg".
 *
 * @param  int $post_id Pagina.
 * @return string[]
 */
function sfp_page_config_controle_genegeerd( $post_id ) {
    $negeer = get_post_meta( $post_id, '_sfp_controle_negeer', true );
    $negeer = is_array( $negeer ) ? array_map( 'strval', $negeer ) : array();
    if ( '1' !== (string) get_post_meta( $post_id, '_sfp_controle_meld_weer', true ) ) {
        $verborgen = get_option( 'sfp_cursusdata_hidden', array() );
        if ( is_array( $verborgen ) && in_array( (int) $post_id, array_map( 'intval', $verborgen ), true ) ) {
            $negeer[] = 'data';
        }
    }
    return array_values( array_unique( $negeer ) );
}

/**
 * Alle pagina's met hun instellingen en meldingen.
 *
 * Pagina's staan er altijd in. Berichten en begrippen staan er allemaal in
 * als het er hoogstens 25 zijn; zijn het er meer, dan alleen die met een
 * melding, met een telling van de rest.
 *
 * @param  bool $force Sla de bewaarde uitkomst over.
 * @return array{paginas: array, overig_in_orde: int, aandacht: int}
 */
function sfp_page_config_paginas_overzicht( $force = false ) {
    if ( ! $force ) {
        $bewaard = get_transient( 'sfp_pc_paginas' );
        if ( is_array( $bewaard ) && isset( $bewaard['paginas'] ) ) {
            return $bewaard;
        }
    }

    $query = new WP_Query(
        array(
            'post_type'              => sfp_page_config_post_types(),
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'suppress_filters'       => true,
        )
    );

    $soorten    = sfp_page_config_soorten();
    $overig     = array();
    $paginas    = array();
    $aandacht   = 0;
    $voorpagina = (int) get_option( 'page_on_front' );
    $artikel_op = sfp_page_config_onderdeel_aan( 'artikel' );

    foreach ( $query->posts as $post ) {
        $soort     = (string) get_post_meta( $post->ID, 'sfp_page_type', true );
        $soort     = isset( $soorten[ $soort ] ) ? $soort : '';
        $meldingen = sfp_page_config_controleer_pagina( $post );
        $knop      = null;
        if ( '' !== $soort && sfp_page_config_onderdeel_aan( 'knop' ) && function_exists( 'sfp_page_config_knop_voor_pagina' ) ) {
            $knop = sfp_page_config_knop_voor_pagina( $post->ID, $soort );
        }

        if ( 'page' === $post->post_type ) {
            $soortnaam = $soorten[ $soort ]['naam'];
        } elseif ( 'begrippen' === $post->post_type ) {
            $soortnaam = 'Begrip';
        } else {
            $soortnaam = $artikel_op ? 'Artikel' : 'Bericht';
        }

        $rij = array(
            'id'        => (int) $post->ID,
            'titel'     => html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . ( (int) $post->ID === $voorpagina ? ' (startpagina)' : '' ),
            'type'      => $post->post_type,
            'soort'     => $soort,
            'soortnaam' => $soortnaam,
            'knoptekst' => $knop ? $knop['text'] : '',
            'eigen'     => array(
                'tekst'  => (string) get_post_meta( $post->ID, 'sfp_cta_text', true ),
                'doel'   => (int) get_post_meta( $post->ID, 'sfp_cta_pagina', true ) ? 'pagina' : ( '' !== (string) get_post_meta( $post->ID, 'sfp_cta_href', true ) ? 'link' : 'form' ),
                'pagina' => (int) get_post_meta( $post->ID, 'sfp_cta_pagina', true ),
                'link'   => (string) get_post_meta( $post->ID, 'sfp_cta_href', true ),
            ),
            'meldingen' => $meldingen,
            'genegeerd' => count( sfp_page_config_controle_genegeerd( $post->ID ) ),
            'link'      => (string) get_permalink( $post ),
            'bewerk'    => (string) get_edit_post_link( $post->ID, 'raw' ),
        );
        if ( $meldingen ) {
            ++$aandacht;
        }
        if ( 'page' === $post->post_type ) {
            $paginas[] = $rij;
        } else {
            $overig[] = $rij;
        }
    }

    $in_orde = 0;
    if ( count( $overig ) <= 25 ) {
        $paginas = array_merge( $paginas, $overig );
    } else {
        foreach ( $overig as $rij ) {
            if ( $rij['meldingen'] ) {
                $paginas[] = $rij;
            } else {
                ++$in_orde;
            }
        }
    }

    $uit = array(
        'paginas'        => $paginas,
        'overig_in_orde' => $in_orde,
        'aandacht'       => $aandacht,
    );
    set_transient( 'sfp_pc_paginas', $uit, HOUR_IN_SECONDS );
    return $uit;
}

add_action( 'save_post', 'sfp_page_config_paginas_wis' );
add_action( 'deleted_post', 'sfp_page_config_paginas_wis' );
add_action( 'update_option_sfp_settings', 'sfp_page_config_paginas_wis' );
add_action( 'sfp_page_config_cursusdata_updated', 'sfp_page_config_paginas_wis' );

/**
 * De uitkomst van de controle kan na een wijziging anders zijn.
 */
function sfp_page_config_paginas_wis() {
    delete_transient( 'sfp_pc_paginas' );
}

/**
 * Bewaar wat in het beheer bij één pagina is gekozen.
 *
 * @param  int   $post_id Pagina.
 * @param  array $invoer  soort, tekst, doel, pagina, link, negeer (code) of meld_weer (true).
 * @return true|WP_Error
 */
function sfp_page_config_pagina_bewaar( $post_id, array $invoer ) {
    $post = get_post( $post_id );
    if ( ! $post || ! in_array( $post->post_type, sfp_page_config_post_types(), true ) ) {
        return new WP_Error( 'sfp_pagina_onbekend', 'Deze pagina bestaat niet.', array( 'status' => 404 ) );
    }

    if ( array_key_exists( 'soort', $invoer ) && 'page' === $post->post_type ) {
        $soort = (string) $invoer['soort'];
        if ( in_array( $soort, sfp_page_config_paginasoorten(), true ) ) {
            update_post_meta( $post_id, 'sfp_page_type', $soort );
        } else {
            delete_post_meta( $post_id, 'sfp_page_type' );
        }
    }

    if ( array_key_exists( 'tekst', $invoer ) ) {
        $tekst = sanitize_text_field( (string) $invoer['tekst'] );
        if ( '' !== $tekst ) {
            update_post_meta( $post_id, 'sfp_cta_text', $tekst );
        } else {
            delete_post_meta( $post_id, 'sfp_cta_text' );
        }
    }

    if ( array_key_exists( 'doel', $invoer ) ) {
        $doel = (string) $invoer['doel'];
        delete_post_meta( $post_id, 'sfp_cta_pagina' );
        delete_post_meta( $post_id, 'sfp_cta_href' );
        if ( 'pagina' === $doel && ! empty( $invoer['pagina'] ) ) {
            update_post_meta( $post_id, 'sfp_cta_pagina', absint( $invoer['pagina'] ) );
        } elseif ( 'link' === $doel && ! empty( $invoer['link'] ) ) {
            $link = sfp_page_config_sanitize_cta_href( $invoer['link'] );
            if ( '' !== $link ) {
                update_post_meta( $post_id, 'sfp_cta_href', $link );
            }
        }
    }

    if ( ! empty( $invoer['negeer'] ) ) {
        $negeer   = get_post_meta( $post_id, '_sfp_controle_negeer', true );
        $negeer   = is_array( $negeer ) ? $negeer : array();
        $negeer[] = sanitize_text_field( (string) $invoer['negeer'] );
        update_post_meta( $post_id, '_sfp_controle_negeer', array_values( array_unique( $negeer ) ) );
    }
    if ( ! empty( $invoer['meld_weer'] ) ) {
        delete_post_meta( $post_id, '_sfp_controle_negeer' );
        // Ook een training die voor 3.0.0 was verborgen, meldt zich weer.
        update_post_meta( $post_id, '_sfp_controle_meld_weer', '1' );
    }

    sfp_page_config_paginas_wis();
    sfp_page_config_in_gebruik_wis();
    if ( function_exists( 'sfp_page_config_cursusdata_wis_lijst' ) ) {
        sfp_page_config_cursusdata_wis_lijst();
    }
    return true;
}
