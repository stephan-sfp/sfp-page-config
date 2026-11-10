<?php
/**
 * SFP Page Config - Incompanycalculator: instellingen per site (sinds 3.36.0).
 *
 * De norm staat in includes/calculator-norm.php. Hier staan de gegevens van
 * de site: sitecode, pagina's, formulier, labelteksten, contactpersoon en de
 * afwijkingen van de norm. Eén optie, sfp_calculator, niet automatisch geladen.
 *
 * Proefmodus: de calculator is alleen zichtbaar voor beheerders en de pdf
 * draagt het woord Proef. Zo kan een site hem doorlopen voor bezoekers hem zien.
 *
 * @package SFP_Page_Config
 * @since   3.36.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Lege instellingen.
 *
 * @return array
 */
function sfp_calculator_leeg() {
    return array(
        'code'              => '',
        'proefmodus'        => '1',
        'calculatorpagina'  => 0,
        'boekpagina'        => 0,
        'formulier'         => 0,
        'voorwaarden'       => 0,
        'coaching_pagina'   => 0,
        'open_pagina'       => 0,
        'logo'              => 0,
        'methode'           => '',
        'programma'         => array(
            '1' => '',
            '2' => '',
            '3' => '',
        ),
        'doelgroepzin'      => array(
            'starters'      => '',
            'ervaren'       => '',
            'senioren'      => '',
            'leidinggevend' => '',
            'gemengd'       => '',
            'anders'        => '',
        ),
        'uitleg'            => array(
            'begeleiding' => '',
            'coachuren'   => '',
            'clinic'      => '',
            'boek'        => '',
            'acteur'      => '',
            'terugkom'    => '',
            'lotj'        => '',
            'scan'        => '',
            'voordeel'    => '',
        ),
        'contact'           => array(
            'naam'     => '',
            'functie'  => '',
            'profiel'  => '',
            'foto'     => 0,
            'telefoon' => '',
            'email'    => '',
        ),
        'referentie'        => array(
            'citaat'  => '',
            'naam'    => '',
            'functie' => '',
        ),
        'valuta'            => '',
        'prijzen'           => array(),
        'extras'            => array(),
        'routes'            => array(),
    );
}

/**
 * De instellingen van deze site.
 *
 * @return array
 */
function sfp_calculator_instellingen() {
    $ruw = get_option( 'sfp_calculator', array() );
    $ruw = is_array( $ruw ) ? $ruw : array();
    return array_replace_recursive( sfp_calculator_leeg(), $ruw );
}

/**
 * Sitecode voor het kenmerk (DPS, SFP, ...), uit de instellingen.
 *
 * @return string
 */
function sfp_calculator_site_afk() {
    $inst = get_option( 'sfp_calculator', array() );
    $code = is_array( $inst ) && isset( $inst['code'] ) ? strtoupper( (string) $inst['code'] ) : '';
    return preg_match( '/^[A-Z]{2,4}$/', $code ) ? $code : '';
}

/**
 * Staat de calculator in proefmodus?
 *
 * @return bool
 */
function sfp_calculator_proef() {
    return '1' === (string) sfp_calculator_instellingen()['proefmodus'];
}

/**
 * Mag deze bezoeker de calculator zien? Uit: niemand. Proefmodus: beheerders.
 *
 * @return bool
 */
function sfp_calculator_zichtbaar() {
    if ( ! function_exists( 'sfp_page_config_onderdeel_aan' ) || ! sfp_page_config_onderdeel_aan( 'calculator' ) ) {
        return false;
    }
    if ( sfp_calculator_proef() ) {
        return current_user_can( 'manage_options' );
    }
    return true;
}

/**
 * Een gepubliceerde pagina-id, of 0.
 *
 * @param  mixed $id Waarde.
 * @return int
 */
function sfp_calculator_pagina_id( $id ) {
    $id = absint( $id );
    return $id && 'page' === get_post_type( $id ) ? $id : 0;
}

/**
 * Maak ruwe instellingen schoon. Alleen meegestuurde sleutels veranderen.
 *
 * @param  array $ruw Invoer.
 * @return array      Nieuwe instellingen.
 */
function sfp_calculator_instellingen_schoon( array $ruw ) {
    $inst = sfp_calculator_instellingen();
    $leeg = sfp_calculator_leeg();

    if ( array_key_exists( 'code', $ruw ) ) {
        $code         = strtoupper( preg_replace( '/[^a-z]/i', '', (string) $ruw['code'] ) );
        $inst['code'] = preg_match( '/^[A-Z]{2,4}$/', $code ) ? $code : '';
    }
    if ( array_key_exists( 'proefmodus', $ruw ) ) {
        $inst['proefmodus'] = ! empty( $ruw['proefmodus'] ) && 'false' !== $ruw['proefmodus'] ? '1' : '0';
    }
    foreach ( array( 'calculatorpagina', 'boekpagina', 'voorwaarden', 'coaching_pagina', 'open_pagina' ) as $sleutel ) {
        if ( array_key_exists( $sleutel, $ruw ) ) {
            $inst[ $sleutel ] = sfp_calculator_pagina_id( $ruw[ $sleutel ] );
        }
    }
    if ( array_key_exists( 'formulier', $ruw ) ) {
        $id                = absint( $ruw['formulier'] );
        $inst['formulier'] = $id && 'sureforms_form' === get_post_type( $id ) ? $id : 0;
    }
    if ( array_key_exists( 'logo', $ruw ) ) {
        $id           = absint( $ruw['logo'] );
        $inst['logo'] = $id && wp_attachment_is_image( $id ) ? $id : 0;
    }
    if ( array_key_exists( 'methode', $ruw ) ) {
        $inst['methode'] = sanitize_text_field( (string) $ruw['methode'] );
    }
    foreach ( array( 'programma', 'doelgroepzin', 'uitleg' ) as $groep ) {
        if ( isset( $ruw[ $groep ] ) && is_array( $ruw[ $groep ] ) ) {
            foreach ( array_keys( $leeg[ $groep ] ) as $sleutel ) {
                if ( array_key_exists( $sleutel, $ruw[ $groep ] ) ) {
                    $inst[ $groep ][ $sleutel ] = sanitize_textarea_field( (string) $ruw[ $groep ][ $sleutel ] );
                }
            }
        }
    }
    if ( isset( $ruw['contact'] ) && is_array( $ruw['contact'] ) ) {
        $c = $ruw['contact'];
        foreach ( array( 'naam', 'functie', 'telefoon' ) as $sleutel ) {
            if ( array_key_exists( $sleutel, $c ) ) {
                $inst['contact'][ $sleutel ] = sanitize_text_field( (string) $c[ $sleutel ] );
            }
        }
        if ( array_key_exists( 'profiel', $c ) ) {
            $inst['contact']['profiel'] = sanitize_textarea_field( (string) $c['profiel'] );
        }
        if ( array_key_exists( 'email', $c ) ) {
            $inst['contact']['email'] = sanitize_email( (string) $c['email'] );
        }
        if ( array_key_exists( 'foto', $c ) ) {
            $id                      = absint( $c['foto'] );
            $inst['contact']['foto'] = $id && wp_attachment_is_image( $id ) ? $id : 0;
        }
    }
    if ( isset( $ruw['referentie'] ) && is_array( $ruw['referentie'] ) ) {
        foreach ( array( 'naam', 'functie' ) as $sleutel ) {
            if ( array_key_exists( $sleutel, $ruw['referentie'] ) ) {
                $inst['referentie'][ $sleutel ] = sanitize_text_field( (string) $ruw['referentie'][ $sleutel ] );
            }
        }
        if ( array_key_exists( 'citaat', $ruw['referentie'] ) ) {
            $inst['referentie']['citaat'] = sanitize_textarea_field( (string) $ruw['referentie']['citaat'] );
        }
    }
    if ( array_key_exists( 'valuta', $ruw ) ) {
        $v              = strtoupper( (string) $ruw['valuta'] );
        $inst['valuta'] = preg_match( '/^[A-Z]{3}$/', $v ) && 'EUR' !== $v ? $v : '';
    }
    if ( isset( $ruw['prijzen'] ) && is_array( $ruw['prijzen'] ) ) {
        $norm = sfp_calculator_norm();
        foreach ( sfp_calculator_prijs_paden() as $pad => $label ) {
            if ( ! array_key_exists( $pad, $ruw['prijzen'] ) ) {
                continue;
            }
            $w = trim( (string) $ruw['prijzen'][ $pad ] );
            if ( '' === $w || ! preg_match( '/^\d{1,7}$/', $w ) || (int) $w === (int) sfp_calculator_pad( $norm, $pad ) ) {
                unset( $inst['prijzen'][ $pad ] );
            } else {
                $inst['prijzen'][ $pad ] = (int) $w;
            }
        }
    }
    foreach ( array( 'extras', 'routes' ) as $groep ) {
        if ( ! isset( $ruw[ $groep ] ) || ! is_array( $ruw[ $groep ] ) ) {
            continue;
        }
        $toegestaan = 'extras' === $groep ? array_keys( sfp_calculator_norm()['extras_aan'] ) : array( 'A', 'B' );
        foreach ( $toegestaan as $sleutel ) {
            if ( array_key_exists( $sleutel, $ruw[ $groep ] ) ) {
                $w = (string) $ruw[ $groep ][ $sleutel ];
                if ( '' === $w ) {
                    unset( $inst[ $groep ][ $sleutel ] );
                } else {
                    $inst[ $groep ][ $sleutel ] = in_array( $w, array( '1', 'true' ), true ) ? '1' : '0';
                }
            }
        }
    }
    return $inst;
}

/**
 * Bewaar instellingen.
 *
 * @param  array $ruw Invoer.
 * @return array      Bewaarde instellingen.
 */
function sfp_calculator_instellingen_bewaar( array $ruw ) {
    $inst = sfp_calculator_instellingen_schoon( $ruw );
    // Een keuze voor een extra of route die gelijk is aan de norm valt weg.
    $norm            = sfp_calculator_norm();
    $norm_routes     = $norm['routes_aan'];
    $norm_routes['B'] = in_array( $inst['code'], sfp_calculator_evenement_sites(), true );
    foreach ( (array) $inst['extras'] as $k => $w ) {
        if ( ( '1' === $w ) === (bool) $norm['extras_aan'][ $k ] ) {
            unset( $inst['extras'][ $k ] );
        }
    }
    foreach ( (array) $inst['routes'] as $k => $w ) {
        if ( ( '1' === $w ) === (bool) $norm_routes[ $k ] ) {
            unset( $inst['routes'][ $k ] );
        }
    }
    update_option( 'sfp_calculator', $inst, false );
    return $inst;
}

/**
 * Adres van een pagina-instelling, of ''.
 *
 * @param  string $sleutel calculatorpagina, boekpagina, voorwaarden, coaching_pagina, open_pagina.
 * @return string
 */
function sfp_calculator_url( $sleutel ) {
    $id = (int) sfp_calculator_instellingen()[ $sleutel ];
    if ( $id <= 0 || 'publish' !== get_post_status( $id ) ) {
        return '';
    }
    return (string) get_permalink( $id );
}

/* =========================================================================
 * Bedrijfsgegevens (sinds 3.36.0)
 * ====================================================================== */

/**
 * De velden van het blok Bedrijfsgegevens, met label.
 *
 * @return array<string, string>
 */
function sfp_page_config_bedrijf_velden() {
    return array(
        'label'          => 'Labelnaam',
        'juridische_naam' => 'Juridische naam',
        'rechtsvorm'     => 'Rechtsvorm',
        'kvk'            => 'KvK-nummer',
        'btw'            => 'Btw-nummer',
        'iban'           => 'IBAN',
        'adres'          => 'Adres',
    );
}

/**
 * Maak de bedrijfsgegevens schoon.
 *
 * @param  mixed $ruw Invoer.
 * @return array
 */
function sfp_page_config_bedrijf_schoon( $ruw ) {
    $ruw = is_array( $ruw ) ? $ruw : array();
    $uit = array();
    foreach ( array_keys( sfp_page_config_bedrijf_velden() ) as $sleutel ) {
        $w = isset( $ruw[ $sleutel ] ) ? (string) $ruw[ $sleutel ] : '';
        if ( 'adres' === $sleutel ) {
            $w = sanitize_textarea_field( $w );
        } elseif ( in_array( $sleutel, array( 'kvk', 'btw', 'iban' ), true ) ) {
            $w = strtoupper( preg_replace( '/[^A-Za-z0-9 .]/', '', $w ) );
            $w = trim( preg_replace( '/\s+/', ' ', $w ) );
        } else {
            $w = sanitize_text_field( $w );
        }
        $uit[ $sleutel ] = $w;
    }
    return $uit;
}

/**
 * De bedrijfsgegevens van deze site.
 *
 * @return array
 */
function sfp_page_config_bedrijf() {
    $alle = function_exists( 'sfp_page_config_instellingen' ) ? sfp_page_config_instellingen() : array();
    return sfp_page_config_bedrijf_schoon( isset( $alle['bedrijf'] ) ? $alle['bedrijf'] : array() );
}

/**
 * Voor SFP Beheer: de velden en de waarden.
 *
 * @return array
 */
function sfp_page_config_bedrijf_beheer() {
    return array(
        'velden'  => sfp_page_config_bedrijf_velden(),
        'waarden' => sfp_page_config_bedrijf(),
    );
}

/**
 * Ontbrekende bedrijfsgegevens (voor meldingen in het beheer).
 *
 * @return string[] Labels.
 */
function sfp_page_config_bedrijf_ontbreekt() {
    $b   = sfp_page_config_bedrijf();
    $uit = array();
    foreach ( sfp_page_config_bedrijf_velden() as $sleutel => $label ) {
        if ( '' === $b[ $sleutel ] ) {
            $uit[] = $label;
        }
    }
    return $uit;
}
