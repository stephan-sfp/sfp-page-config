<?php
/**
 * SFP Page Config - Instellingen, onderdelen en netwerknorm
 *
 * Eén plek voor alles wat de plugin per site bewaart (optie `sfp_settings`):
 *
 *   - de onderdelen van de plugin, per site aan of uit;
 *   - de netwerknorm: de waarde die geldt als een instelling leeg is;
 *   - welke instellingen op deze site van de norm afwijken;
 *   - opslaan per sleutel. Alleen de meegestuurde sleutels veranderen; al het
 *     andere in `sfp_settings` blijft staan. Tot en met 2.12.0 herbouwde de
 *     sanitizer de hele optie bij elke opslag.
 *
 * Bestaande sleutels houden hun naam en betekenis. Vervallen in 3.0.0 (de
 * plugin leest ze niet meer; de bewaarde waarde blijft staan zodat terugzetten
 * naar 2.12.0 niets kost): de negen kleurvelden `lr_*`, `progress_bar_color`,
 * `reading_time_accent_color`, `custom_css_rp`, `promo_scroll_gate`,
 * `promo_cooldown_hours`. De velden `anchor`, `hero` en `href` van de knop
 * onderaan staan niet meer in het beheer, maar een bewaarde waarde blijft
 * meetellen tot de bestemming van die soort pagina in het beheer is gekozen.
 *
 * @package SFP_Page_Config
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Lezen
 * ====================================================================== */

/**
 * Alle bewaarde instellingen.
 *
 * @return array
 */
function sfp_page_config_instellingen() {
    $alle = get_option( 'sfp_settings', array() );
    return is_array( $alle ) ? $alle : array();
}

/**
 * Eén instelling, met terugval als hij ontbreekt of leeg is.
 *
 * @param  string $key     Sleutel.
 * @param  mixed  $default Terugval.
 * @return mixed
 */
function sfp_page_config_get_setting( $key, $default = '' ) {
    $settings = sfp_page_config_instellingen();
    return isset( $settings[ $key ] ) && '' !== $settings[ $key ] ? $settings[ $key ] : $default;
}

/**
 * De soorten pagina met een knop onderaan.
 *
 * @return string[]
 */
function sfp_page_config_paginasoorten() {
    return array( 'coaching', 'training', 'incompany' );
}

/* =========================================================================
 * Onderdelen
 * ====================================================================== */

/**
 * De onderdelen van de plugin.
 *
 * 'detail' is true als het onderdeel een eigen scherm heeft in het beheer.
 *
 * @return array<string, array{naam: string, uitleg: string, detail: bool}>
 */
function sfp_page_config_onderdelen() {
    return array(
        'knop'       => array(
            'naam'   => 'Knop onderaan',
            'uitleg' => 'Op een telefoon blijft op een dienstpagina één knop in beeld.',
            'detail' => true,
        ),
        'artikel'    => array(
            'naam'   => 'Artikelen',
            'uitleg' => 'Kopkaart, inhoudsopgave, auteurskaart en vervolg bij elk artikel.',
            'detail' => true,
        ),
        'reviews'    => array(
            'naam'   => 'Reviews',
            'uitleg' => 'Reviews van Google en je eigen reviews.',
            'detail' => true,
        ),
        'tooltips'   => array(
            'naam'   => 'Tooltips',
            'uitleg' => 'Uitleg bij een woord, die verschijnt bij aanwijzen of aantikken.',
            'detail' => true,
        ),
        'footer'     => array(
            'naam'   => 'Footerlinks',
            'uitleg' => 'De groepen met links onderaan de site.',
            'detail' => true,
        ),
        'voortgang'  => array(
            'naam'   => 'Voortgangsbalk',
            'uitleg' => 'Een dunne balk bovenaan die meeloopt terwijl de lezer scrolt.',
            'detail' => false,
        ),
        'whatsapp'   => array(
            'naam'   => 'WhatsApp-knop',
            'uitleg' => 'Een zwevende knop op de pagina\'s waar je hem aanzet.',
            'detail' => true,
        ),
        'cursusdata' => array(
            'naam'   => 'Cursusdata',
            'uitleg' => 'Startmomenten van trainingen, op de pagina en in het aanmeldformulier.',
            'detail' => true,
        ),
    );
}

/**
 * Is er minstens één gepubliceerd bericht met deze meta? Bewaard per aanvraag
 * en een uur in een transient, want het antwoord verandert zelden.
 *
 * @param  string $meta_key   Meta-sleutel.
 * @param  string $meta_value Waarde, of '' voor elke niet-lege waarde.
 * @return bool
 */
function sfp_page_config_in_gebruik( $meta_key, $meta_value = '' ) {
    static $geheugen = array();
    $sleutel = $meta_key . '|' . $meta_value;
    if ( isset( $geheugen[ $sleutel ] ) ) {
        return $geheugen[ $sleutel ];
    }
    $bewaard = get_transient( 'sfp_pc_in_gebruik' );
    $bewaard = is_array( $bewaard ) ? $bewaard : array();
    if ( isset( $bewaard[ $sleutel ] ) ) {
        $geheugen[ $sleutel ] = (bool) $bewaard[ $sleutel ];
        return $geheugen[ $sleutel ];
    }
    $meta = array( 'key' => $meta_key );
    if ( '' !== $meta_value ) {
        $meta['value'] = $meta_value;
    } else {
        $meta['value']   = '';
        $meta['compare'] = '!=';
    }
    $gevonden = get_posts(
        array(
            'post_type'        => sfp_page_config_post_types(),
            'post_status'      => array( 'publish', 'draft', 'pending', 'future', 'private' ),
            'posts_per_page'   => 1,
            'fields'           => 'ids',
            'no_found_rows'    => true,
            'suppress_filters' => true,
            'meta_query'       => array( $meta ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- één keer per uur.
        )
    );
    $geheugen[ $sleutel ] = ! empty( $gevonden );
    $bewaard[ $sleutel ]  = $geheugen[ $sleutel ] ? 1 : 0;
    set_transient( 'sfp_pc_in_gebruik', $bewaard, HOUR_IN_SECONDS );
    return $geheugen[ $sleutel ];
}

add_action( 'save_post', 'sfp_page_config_in_gebruik_wis' );
add_action( 'deleted_post', 'sfp_page_config_in_gebruik_wis' );

/**
 * Het antwoord van sfp_page_config_in_gebruik() kan na een opslag anders zijn.
 */
function sfp_page_config_in_gebruik_wis() {
    delete_transient( 'sfp_pc_in_gebruik' );
}

/**
 * De stand van een onderdeel als de site niets heeft gekozen.
 *
 * Uitgangspunt: een update verandert niets aan wat een site doet. Een
 * onderdeel dat de site gebruikt staat aan, een onderdeel dat nergens voor
 * dient staat uit.
 *
 * @param  string $id Onderdeel.
 * @return bool
 */
function sfp_page_config_onderdeel_standaard( $id ) {
    switch ( $id ) {
        case 'knop':
            return sfp_page_config_in_gebruik( 'sfp_page_type' );
        case 'cursusdata':
            return sfp_page_config_in_gebruik( 'sfp_page_type', 'training' );
        case 'whatsapp':
            return sfp_page_config_in_gebruik( 'sfp_whatsapp', '1' );
        case 'reviews':
            $opties = get_option( 'sfp_reviews_options', array() );
            if ( is_array( $opties ) && ! empty( $opties['place_id'] ) && ! empty( $opties['api_key'] ) ) {
                return true;
            }
            $eigen = json_decode( (string) get_option( 'sfp_reviews_manual', '[]' ), true );
            return is_array( $eigen ) && ! empty( $eigen );
        default:
            return true;
    }
}

/**
 * Staat een onderdeel aan op deze site?
 *
 * Artikelen volgt de bestaande instelling `artikel_aan`. De andere onderdelen
 * staan in `onderdelen[id]` als '1' of '0'; ontbreekt de keuze, dan geldt
 * sfp_page_config_onderdeel_standaard().
 *
 * @param  string $id Onderdeel.
 * @return bool
 */
function sfp_page_config_onderdeel_aan( $id ) {
    $alle = sfp_page_config_instellingen();
    if ( 'artikel' === $id ) {
        return isset( $alle['artikel_aan'] ) && '1' === (string) $alle['artikel_aan'];
    }
    if ( isset( $alle['onderdelen'][ $id ] ) && '' !== (string) $alle['onderdelen'][ $id ] ) {
        return '1' === (string) $alle['onderdelen'][ $id ];
    }
    return sfp_page_config_onderdeel_standaard( $id );
}

/* =========================================================================
 * Links naar eigen pagina's
 * ====================================================================== */

/**
 * Lees een link die als pagina is gekozen of als adres is bewaard.
 *
 * Het beheer laat een pagina kiezen en bewaart het nummer onder
 * `<basis>_pagina`. Zo blijft de link kloppen als het adres van de pagina
 * verandert. Het oude veld `<basis>_url` blijft werken als er geen pagina is
 * gekozen.
 *
 * @param  string $basis Sleutel zonder achtervoegsel, bijvoorbeeld artikel_redactie.
 * @return string        Adres, of '' als er niets is ingesteld.
 */
function sfp_page_config_link_instelling( $basis ) {
    $alle    = sfp_page_config_instellingen();
    $post_id = isset( $alle[ $basis . '_pagina' ] ) ? (int) $alle[ $basis . '_pagina' ] : 0;
    if ( $post_id && 'publish' === get_post_status( $post_id ) ) {
        $url = get_permalink( $post_id );
        if ( $url ) {
            return (string) $url;
        }
    }
    return isset( $alle[ $basis . '_url' ] ) ? (string) $alle[ $basis . '_url' ] : '';
}

/* =========================================================================
 * Knop onderaan: instelling per soort pagina
 * ====================================================================== */

/**
 * De knop onderaan voor één soort pagina.
 *
 * @param  string $type coaching, training of incompany.
 * @return array|null   tekst, doel (form, pagina of link), href en target; null bij een onbekende soort.
 */
function sfp_page_config_get_sticky_cta( $type ) {
    if ( ! in_array( $type, sfp_page_config_paginasoorten(), true ) ) {
        return null;
    }
    $alle  = sfp_page_config_instellingen();
    $eigen = isset( $alle['sticky_cta'][ $type ] ) && is_array( $alle['sticky_cta'][ $type ] ) ? $alle['sticky_cta'][ $type ] : array();

    $tekst = isset( $eigen['text'] ) ? trim( (string) $eigen['text'] ) : '';
    if ( '' === $tekst ) {
        $tekst = sfp_page_config_tekst( 'knop_' . $type );
    }

    $doel = isset( $eigen['doel'] ) ? (string) $eigen['doel'] : '';
    $href = '';
    if ( 'pagina' === $doel ) {
        $post_id = isset( $eigen['pagina'] ) ? (int) $eigen['pagina'] : 0;
        $href    = $post_id && 'publish' === get_post_status( $post_id ) ? (string) get_permalink( $post_id ) : '';
    } elseif ( 'link' === $doel ) {
        $href = isset( $eigen['link'] ) ? trim( (string) $eigen['link'] ) : '';
        // Een anker wijst naar een onderdeel van de pagina zelf. De knop
        // gaat dan naar het formulier; het anker telt mee als herkenning.
        if ( 0 === strpos( $href, '#' ) ) {
            $href = '';
        }
    } elseif ( '' === $doel && ! empty( $eigen['href'] ) ) {
        // Tot en met 2.12.0: één veld voor een anker of een adres.
        $oud = trim( (string) $eigen['href'] );
        if ( '' !== $oud && 0 !== strpos( $oud, '#' ) ) {
            $doel = 'link';
            $href = $oud;
        }
    }
    if ( '' === $href ) {
        $doel = 'form';
    }

    return sfp_page_config_sticky_cta_vorm( $tekst, $doel, $href );
}

/**
 * Zet tekst, doel en adres in de vorm die het script verwacht.
 *
 * @param  string $tekst Tekst op de knop.
 * @param  string $doel  form, pagina of link.
 * @param  string $href  Adres bij pagina of link; bij form leeg.
 * @return array
 */
function sfp_page_config_sticky_cta_vorm( $tekst, $doel, $href ) {
    $eigen_site = '' !== $href && 0 === strpos( $href, home_url() );
    return array(
        'text'   => (string) $tekst,
        'doel'   => 'form' === $doel ? 'form' : ( 'pagina' === $doel ? 'pagina' : 'link' ),
        'href'   => 'form' === $doel ? '' : (string) $href,
        'target' => ( 'form' === $doel || 'pagina' === $doel || $eigen_site ) ? '_self' : '_blank',
    );
}

/* =========================================================================
 * Netwerknorm en afwijkingen
 * ====================================================================== */

/**
 * De instellingen die een netwerknorm hebben: leeg betekent de norm.
 *
 * @return array<string, array{label: string, norm: string}> Per pad (met punten).
 */
function sfp_page_config_norm() {
    $norm = array();
    foreach ( sfp_page_config_teksten_register() as $sleutel => $tekst ) {
        $norm[ sfp_page_config_tekst_pad( $sleutel ) ] = array(
            'label' => 'Tekst bij ' . $tekst['groep'] . ': ' . $tekst['label'],
            'norm'  => sfp_page_config_tekst_standaard( $sleutel ),
        );
    }
    $norm['whatsapp_number']  = array(
        'label' => 'Telefoonnummer van de WhatsApp-knop',
        'norm'  => sfp_page_config_whatsapp_norm(),
    );
    $norm['words_per_minute'] = array(
        'label' => 'Leessnelheid voor de leestijd',
        'norm'  => '250',
    );
    $norm['update_kanaal']    = array(
        'label' => 'Soort versies die deze site krijgt',
        'norm'  => '',
    );
    return $norm;
}

/**
 * Het telefoonnummer van het netwerk voor de WhatsApp-knop, alleen cijfers.
 *
 * @return string
 */
function sfp_page_config_whatsapp_norm() {
    return '31850601355';
}

/**
 * Lees een waarde op een pad met punten uit een geneste array.
 *
 * @param  array  $bron Array.
 * @param  string $pad  Pad, bijvoorbeeld sticky_cta.coaching.text.
 * @return mixed|null
 */
function sfp_page_config_pad_lees( array $bron, $pad ) {
    foreach ( explode( '.', $pad ) as $deel ) {
        if ( ! is_array( $bron ) || ! array_key_exists( $deel, $bron ) ) {
            return null;
        }
        $bron = $bron[ $deel ];
    }
    return $bron;
}

/**
 * Zet een waarde op een pad met punten in een geneste array. Een lege waarde
 * onder `teksten` haalt de sleutel weg.
 *
 * @param  array  $bron   Array.
 * @param  string $pad    Pad, bijvoorbeeld sticky_cta.coaching.text.
 * @param  string $waarde Waarde.
 * @return array
 */
function sfp_page_config_pad_schrijf( array $bron, $pad, $waarde ) {
    $delen = explode( '.', $pad );
    $ref   = &$bron;
    $n     = count( $delen );
    foreach ( $delen as $i => $deel ) {
        if ( $i === $n - 1 ) {
            if ( '' === $waarde && 'teksten' === $delen[0] ) {
                unset( $ref[ $deel ] );
            } else {
                $ref[ $deel ] = $waarde;
            }
        } else {
            if ( ! isset( $ref[ $deel ] ) || ! is_array( $ref[ $deel ] ) ) {
                $ref[ $deel ] = array();
            }
            $ref = &$ref[ $deel ];
        }
    }
    unset( $ref );
    return $bron;
}

/**
 * Wat deze site anders heeft ingesteld dan de netwerknorm.
 *
 * @return array<int, array{pad: string, label: string, waarde: string, norm: string}>
 */
function sfp_page_config_afwijkingen() {
    $alle = sfp_page_config_instellingen();
    $uit  = array();
    foreach ( sfp_page_config_norm() as $pad => $regel ) {
        $waarde = sfp_page_config_pad_lees( $alle, $pad );
        if ( null === $waarde || is_array( $waarde ) ) {
            continue;
        }
        $waarde = trim( (string) $waarde );
        if ( '' === $waarde || $waarde === $regel['norm'] ) {
            continue;
        }
        if ( 'whatsapp_number' === $pad && preg_replace( '/[^0-9]/', '', $waarde ) === $regel['norm'] ) {
            continue;
        }
        $toon = $waarde;
        $norm = $regel['norm'];
        if ( 'update_kanaal' === $pad ) {
            $toon = 'Ook proefversies';
            $norm = 'Gewone versies';
        }
        $uit[] = array(
            'pad'    => $pad,
            'label'  => $regel['label'],
            'waarde' => $toon,
            'norm'   => $norm,
        );
    }
    return $uit;
}

/* =========================================================================
 * Opslaan
 * ====================================================================== */

/**
 * Bewaar een of meer instellingen. Alleen de meegestuurde sleutels
 * veranderen. Onbekende sleutels worden overgeslagen.
 *
 * @param  array $wijzigingen Sleutel => ruwe waarde.
 * @return array              De sleutels die zijn bewaard.
 */
function sfp_page_config_instellingen_bewaar( array $wijzigingen ) {
    $alle    = sfp_page_config_instellingen();
    $bewaard = array();

    foreach ( $wijzigingen as $sleutel => $ruw ) {
        switch ( $sleutel ) {
            case 'onderdelen':
                if ( ! is_array( $ruw ) ) {
                    break;
                }
                if ( ! isset( $alle['onderdelen'] ) || ! is_array( $alle['onderdelen'] ) ) {
                    $alle['onderdelen'] = array();
                }
                foreach ( $ruw as $id => $aan ) {
                    if ( ! array_key_exists( $id, sfp_page_config_onderdelen() ) ) {
                        continue;
                    }
                    $staat = ( true === $aan || '1' === (string) $aan || 'true' === $aan ) ? '1' : '0';
                    if ( 'artikel' === $id ) {
                        $alle['artikel_aan'] = '1' === $staat ? '1' : '';
                    } else {
                        $alle['onderdelen'][ $id ] = $staat;
                    }
                }
                $bewaard[] = $sleutel;
                break;

            case 'sticky_cta':
                if ( ! is_array( $ruw ) ) {
                    break;
                }
                if ( ! isset( $alle['sticky_cta'] ) || ! is_array( $alle['sticky_cta'] ) ) {
                    $alle['sticky_cta'] = array();
                }
                foreach ( sfp_page_config_paginasoorten() as $type ) {
                    if ( ! isset( $ruw[ $type ] ) || ! is_array( $ruw[ $type ] ) ) {
                        continue;
                    }
                    $oud  = isset( $alle['sticky_cta'][ $type ] ) && is_array( $alle['sticky_cta'][ $type ] ) ? $alle['sticky_cta'][ $type ] : array();
                    $in   = $ruw[ $type ];
                    $doel = isset( $in['doel'] ) && in_array( $in['doel'], array( 'form', 'pagina', 'link' ), true ) ? $in['doel'] : ( isset( $oud['doel'] ) ? $oud['doel'] : 'form' );
                    $oud['text']   = array_key_exists( 'text', $in ) ? sanitize_text_field( (string) $in['text'] ) : ( isset( $oud['text'] ) ? $oud['text'] : '' );
                    if ( isset( $in['doel'] ) || isset( $oud['doel'] ) ) {
                        $oud['doel'] = $doel;
                    }
                    $oud['pagina'] = array_key_exists( 'pagina', $in ) ? absint( $in['pagina'] ) : ( isset( $oud['pagina'] ) ? absint( $oud['pagina'] ) : 0 );
                    $oud['link']   = array_key_exists( 'link', $in ) ? esc_url_raw( trim( (string) $in['link'] ) ) : ( isset( $oud['link'] ) ? $oud['link'] : '' );
                    // Het oude veld telt niet meer mee zodra het doel hier is gekozen.
                    if ( isset( $in['doel'] ) ) {
                        $oud['href'] = '';
                    }
                    $alle['sticky_cta'][ $type ] = $oud;
                }
                $bewaard[] = $sleutel;
                break;

            case 'whatsapp_number':
                $alle[ $sleutel ] = preg_replace( '/[^0-9+ ]/', '', sanitize_text_field( (string) $ruw ) );
                $bewaard[]        = $sleutel;
                break;

            case 'whatsapp_message':
            case 'artikel_promo_label':
            case 'artikel_promo_titel':
            case 'artikel_promo_linktekst':
                $alle[ $sleutel ] = sanitize_text_field( (string) $ruw );
                $bewaard[]        = $sleutel;
                break;

            case 'artikel_promo_tekst':
            case 'affiliate_uitleg':
                $alle[ $sleutel ] = sanitize_textarea_field( (string) $ruw );
                $bewaard[]        = $sleutel;
                break;

            case 'affiliate_regel':
                $alle[ $sleutel ] = sanitize_text_field( (string) $ruw );
                $bewaard[]        = $sleutel;
                break;

            case 'words_per_minute':
                $getal            = absint( $ruw );
                $alle[ $sleutel ] = $getal ? max( 100, min( 500, $getal ) ) : '';
                $bewaard[]        = $sleutel;
                break;

            case 'artikel_voorkeursbron':
            case 'ajax_lean_enabled':
                $alle[ $sleutel ] = ( true === $ruw || '1' === (string) $ruw || 'true' === $ruw ) ? '1' : '';
                $bewaard[]        = $sleutel;
                break;

            case 'artikel_factcheck_pagina':
            case 'artikel_redactie_pagina':
            case 'artikel_bibliografie_pagina':
            case 'artikel_promo_pagina':
                $alle[ $sleutel ] = absint( $ruw ) ? (string) absint( $ruw ) : '';
                $bewaard[]        = $sleutel;
                break;

            case 'artikel_promo_beeld':
                $alle[ $sleutel ] = absint( $ruw ) ? (string) absint( $ruw ) : '';
                $bewaard[]        = $sleutel;
                break;

            case 'artikel_factcheck_url':
            case 'artikel_redactie_url':
            case 'artikel_bibliografie_url':
            case 'artikel_promo_url':
                $alle[ $sleutel ] = esc_url_raw( trim( (string) $ruw ) );
                $bewaard[]        = $sleutel;
                break;

            case 'affiliate_patronen':
                $regels           = is_array( $ruw ) ? $ruw : preg_split( '/\r\n|\r|\n/', (string) $ruw );
                $regels           = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', array_map( 'trim', array_map( 'strval', $regels ) ) ), 'strlen' ) ) );
                $alle[ $sleutel ] = implode( "\n", $regels );
                $bewaard[]        = $sleutel;
                break;

            case 'update_kanaal':
                $alle[ $sleutel ] = 'proef' === $ruw ? 'proef' : '';
                if ( class_exists( 'SFP_Page_Config_Updater' ) ) {
                    SFP_Page_Config_Updater::wis_cache();
                }
                $bewaard[] = $sleutel;
                break;

            case 'ajax_lean_skip':
                $alle[ $sleutel ] = function_exists( 'sfp_ajax_lean_sanitize_skip' ) ? sfp_ajax_lean_sanitize_skip( $ruw ) : array();
                if ( empty( $alle[ $sleutel ] ) && function_exists( 'sfp_ajax_lean_default_skip' ) ) {
                    $alle[ $sleutel ] = sfp_ajax_lean_default_skip();
                }
                $bewaard[] = $sleutel;
                break;

            case 'cron_email':
                $alle[ $sleutel ] = sanitize_email( (string) $ruw );
                $bewaard[]        = $sleutel;
                break;

            case 'cursus_tijd_default_start':
            case 'cursus_tijd_default_eind':
                $alle[ $sleutel ] = sfp_page_config_sanitize_time( $ruw );
                $bewaard[]        = $sleutel;
                break;

            case 'teksten':
                if ( ! is_array( $ruw ) ) {
                    break;
                }
                $bekend = sfp_page_config_teksten_register();
                foreach ( $ruw as $id => $tekst ) {
                    if ( ! isset( $bekend[ $id ] ) ) {
                        continue;
                    }
                    $tekst = ! empty( $bekend[ $id ]['lang'] ) ? sanitize_textarea_field( (string) $tekst ) : sanitize_text_field( (string) $tekst );
                    // Gelijk aan de standaard is hetzelfde als leeg: de site volgt de norm.
                    if ( $tekst === sfp_page_config_tekst_standaard( $id ) ) {
                        $tekst = '';
                    }
                    $alle = sfp_page_config_pad_schrijf( $alle, sfp_page_config_tekst_pad( $id ), $tekst );
                    if ( 'whatsapp_bericht' === $id ) {
                        // Het oude veld telt niet meer mee zodra de tekst hier is gekozen.
                        $alle['whatsapp_message'] = '';
                    }
                }
                $bewaard[] = $sleutel;
                break;

            case 'footer':
                $alle['footer'] = function_exists( 'sfp_page_config_footer_schoon' ) ? sfp_page_config_footer_schoon( $ruw ) : array();
                $bewaard[]      = $sleutel;
                break;
        }
    }

    if ( $bewaard ) {
        update_option( 'sfp_settings', $alle );
        delete_transient( 'sfp_pc_in_gebruik' );
        // Rechtstreeks: bij de eerste opslag op een site bestaat de optie nog
        // niet en gaat de actie update_option_sfp_settings niet af.
        delete_transient( 'sfp_pc_paginas' );
        if ( function_exists( 'sfp_ajax_lean_sync' ) && array_intersect( $bewaard, array( 'ajax_lean_enabled', 'ajax_lean_skip' ) ) ) {
            sfp_ajax_lean_sync();
        }
    }
    return $bewaard;
}

/**
 * Zet één instelling terug naar de netwerknorm: de bewaarde waarde vervalt.
 *
 * @param  string $pad Pad uit sfp_page_config_norm().
 * @return bool        True als er iets is teruggezet.
 */
function sfp_page_config_instelling_naar_norm( $pad ) {
    if ( ! array_key_exists( $pad, sfp_page_config_norm() ) ) {
        return false;
    }
    $alle = sfp_page_config_instellingen();
    if ( null === sfp_page_config_pad_lees( $alle, $pad ) ) {
        return false;
    }
    $alle = sfp_page_config_pad_schrijf( $alle, $pad, '' );
    if ( 'teksten.whatsapp_bericht' === $pad ) {
        $alle['whatsapp_message'] = '';
    }
    update_option( 'sfp_settings', $alle );
    if ( 'update_kanaal' === $pad && class_exists( 'SFP_Page_Config_Updater' ) ) {
        SFP_Page_Config_Updater::wis_cache();
    }
    return true;
}

/**
 * Wordt een plugin uit deze map in dit verzoek aangezet?
 *
 * Nodig bij terugdraaien: de oude plugins SFP Google Reviews en SFP Tooltip
 * gebruiken namen die deze plugin heeft overgenomen. Zet iemand zo'n oude
 * plugin weer aan, dan houdt deze plugin zich in dat ene verzoek in. Vanaf het
 * volgende verzoek is de oude plugin actief en blijft dit onderdeel slapen.
 *
 * Leest het verzoek alleen om te beslissen wat er niet geladen wordt; er
 * wordt niets mee opgeslagen of uitgevoerd.
 *
 * @param  string $map Mapnaam van de plugin.
 * @return bool
 */
function sfp_page_config_plugin_wordt_aangezet( $map ) {
    $map = (string) $map;
    if ( '' === $map ) {
        return false;
    }

    // WP-CLI: wp plugin activate <map>.
    if ( defined( 'WP_CLI' ) && WP_CLI && ! empty( $GLOBALS['argv'] ) && is_array( $GLOBALS['argv'] ) ) {
        $argv = array_map( 'strval', $GLOBALS['argv'] );
        if ( in_array( 'activate', $argv, true ) && in_array( $map, $argv, true ) ) {
            return true;
        }
    }

    // Alleen op de plekken waar een plugin aangezet kan worden: het
    // pluginscherm in wp-admin en de REST-route voor plugins. Een bezoeker
    // kan dit op de voorkant niet met een adres uitlokken. Wie er is
    // ingelogd, is hier nog niet te vragen zonder de aanmelding via een
    // applicatiewachtwoord te verstoren.
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- alleen lezen, zie de toelichting.
    $methode = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
    $uri     = isset( $_SERVER['REQUEST_URI'] ) ? rawurldecode( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '';

    if ( is_admin() && false !== strpos( $uri, '/plugins.php' ) ) {
        $kandidaten = array();
        if ( isset( $_REQUEST['plugin'] ) && is_string( $_REQUEST['plugin'] ) ) {
            $kandidaten[] = sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) );
        }
        if ( isset( $_REQUEST['checked'] ) && is_array( $_REQUEST['checked'] ) ) {
            foreach ( wp_unslash( $_REQUEST['checked'] ) as $gekozen ) {
                if ( is_string( $gekozen ) ) {
                    $kandidaten[] = sanitize_text_field( $gekozen );
                }
            }
        }
        foreach ( $kandidaten as $kandidaat ) {
            if ( 0 === strpos( $kandidaat, $map . '/' ) ) {
                return true;
            }
        }
        return false;
    }

    // REST: POST of PUT op /wp/v2/plugins/<map>/<bestand>.
    if ( ! in_array( $methode, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        return false;
    }
    $route = isset( $_REQUEST['rest_route'] ) && is_string( $_REQUEST['rest_route'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rest_route'] ) ) : '';
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
    return false !== strpos( $uri, '/wp/v2/plugins/' . $map . '/' ) || 0 === strpos( $route, '/wp/v2/plugins/' . $map . '/' );
}
