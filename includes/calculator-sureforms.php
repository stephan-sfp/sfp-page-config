<?php
/**
 * SFP Page Config - Incompanycalculator: koppeling met het SureForms-formulier (sinds 3.36.0).
 *
 * Stap 8 van de calculator is een SureForms-formulier (per site gekozen in
 * SFP Beheer, Calculator). Gemeten in SureForms 2.13.1:
 *
 * - Server: het filter srfm_form_submit_data krijgt de ruwe inzending vóór
 *   alle verwerking (mail, SureContact). Hier zet de plugin de verborgen
 *   velden op de waarden van de eigen rij bij het kenmerk, zodat een
 *   aangepaste waarde in de browser nooit doorkomt. Klopt het kenmerk of de
 *   sleutel niet, dan weigert het formulier de inzending.
 * - Server: de actie srfm_submission_accepted (sinds SureForms 2.12.9) komt
 *   na een geaccepteerde inzending. Hier koppelt de plugin naam, e-mail en
 *   organisatie aan de rij en zet de pdf in de wachtrij.
 * - Browser: het document krijgt het event srfm_form_submission_success met
 *   detail.formId = "srfm-form-<id>", vóór de bevestiging of de doorverwijzing.
 *   Het formulier moet de bevestiging "bericht op dezelfde pagina" hebben,
 *   anders gaat de bezoeker weg voor de calculator het eindscherm toont.
 *
 * Velden herkent de plugin aan het label: SureForms zet het label base64 in
 * de veldnaam (srfm-<type>-<id>-lbl-<label>-<slug>).
 *
 * @package SFP_Page_Config
 * @since   3.36.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Het label uit een SureForms-veldnaam, of ''.
 *
 * @param  string $sleutel Veldnaam.
 * @return string          Label in kleine letters.
 */
function sfp_calculator_srfm_label( $sleutel ) {
    $sleutel = (string) $sleutel;
    if ( false === strpos( $sleutel, '-lbl-' ) ) {
        return '';
    }
    $deel = explode( '-lbl-', $sleutel, 2 )[1];
    $deel = explode( '-', $deel )[0];
    // Base64 zonder opvulling; SureForms gebruikt het gewone alfabet.
    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- label van SureForms.
    $label = base64_decode( $deel . str_repeat( '=', ( 4 - strlen( $deel ) % 4 ) % 4 ), true );
    return false === $label ? '' : strtolower( trim( html_entity_decode( wp_strip_all_tags( $label ) ) ) );
}

/**
 * Is dit het calculatorformulier van deze site?
 *
 * @param  mixed $form_id Formulier.
 * @return bool
 */
function sfp_calculator_is_formulier( $form_id ) {
    $eigen = (int) sfp_calculator_instellingen()['formulier'];
    return $eigen > 0 && (int) $form_id === $eigen && function_exists( 'sfp_page_config_onderdeel_aan' ) && sfp_page_config_onderdeel_aan( 'calculator' );
}

/**
 * Controle van het gekozen formulier: welke verborgen velden ontbreken, en
 * staat de bevestiging op dezelfde pagina?
 *
 * @return array ok, ontbreekt (labels), bevestiging (tekst of '').
 */
function sfp_calculator_formulier_controle() {
    $id = (int) sfp_calculator_instellingen()['formulier'];
    if ( $id <= 0 ) {
        return array(
            'ok'          => false,
            'ontbreekt'   => array(),
            'bevestiging' => '',
            'gekozen'     => false,
        );
    }
    $inhoud   = (string) get_post_field( 'post_content', $id );
    $gevonden = array();
    if ( preg_match_all( '/"label"\s*:\s*"([^"]+)"/', $inhoud, $m ) ) {
        foreach ( $m[1] as $label ) {
            $leesbaar = json_decode( '"' . $label . '"' );
            if ( is_string( $leesbaar ) ) {
                $gevonden[] = strtolower( trim( $leesbaar ) );
            }
        }
    }
    $nodig     = array_keys( sfp_calculator_velden_leeg() );
    $ontbreekt = array_values( array_diff( $nodig, $gevonden ) );
    $bev       = get_post_meta( $id, '_srfm_form_confirmation', true );
    $soort     = '';
    if ( is_array( $bev ) && isset( $bev[0] ) && is_array( $bev[0] ) ) {
        $soort = isset( $bev[0]['confirmation_type'] ) ? (string) $bev[0]['confirmation_type'] : '';
    }
    $zelfde = '' === $soort || 'same page' === $soort;
    return array(
        'ok'          => ! $ontbreekt && $zelfde,
        'ontbreekt'   => $ontbreekt,
        'bevestiging' => $soort,
        'gekozen'     => true,
    );
}

add_filter( 'srfm_form_submit_data', 'sfp_calculator_srfm_voor', 5 );

/**
 * Vóór de verwerking: de verborgen velden uit de eigen rij.
 *
 * @param  mixed $data Ruwe inzending.
 * @return mixed
 */
function sfp_calculator_srfm_voor( $data ) {
    if ( ! is_array( $data ) || empty( $data['form-id'] ) || ! sfp_calculator_is_formulier( $data['form-id'] ) ) {
        return $data;
    }
    $sleutels = array();
    foreach ( array_keys( $data ) as $sleutel ) {
        $label = sfp_calculator_srfm_label( $sleutel );
        if ( '' !== $label ) {
            $sleutels[ $label ] = $sleutel;
        }
    }
    $kenmerk = isset( $sleutels['kenmerk'] ) ? strtoupper( sanitize_text_field( (string) $data[ $sleutels['kenmerk'] ] ) ) : '';
    $geheim  = isset( $sleutels['sleutel'] ) ? sanitize_text_field( (string) $data[ $sleutels['sleutel'] ] ) : '';
    $rij     = '' !== $kenmerk ? sfp_calculator_rij_op( 'kenmerk', $kenmerk ) : null;
    if ( ! $rij || ! sfp_calculator_sleutel_klopt( $rij, $geheim ) || ! empty( $rij['afgerond_op'] ) ) {
        sfp_calculator_log( 'fout', 'Inzending zonder geldige begroting geweigerd' . ( '' !== $kenmerk ? ' (' . $kenmerk . ')' : '' ) . '.' );
        $data['error'] = sfp_calculator_t( 'fout' );
        return $data;
    }
    foreach ( sfp_calculator_velden( $rij ) as $label => $waarde ) {
        if ( isset( $sleutels[ $label ] ) ) {
            $data[ $sleutels[ $label ] ] = $waarde;
        }
    }
    $GLOBALS['sfp_calculator_srfm_rij'] = (int) $rij['id'];
    return $data;
}

add_action( 'srfm_submission_accepted', 'sfp_calculator_srfm_na', 10, 3 );

/**
 * Na de inzending: contact aan de rij en de pdf in de wachtrij.
 *
 * @param  int   $entry_id Inzending.
 * @param  int   $form_id  Formulier.
 * @param  array $data     Inzending.
 * @return void
 */
function sfp_calculator_srfm_na( $entry_id, $form_id, $data ) {
    if ( empty( $GLOBALS['sfp_calculator_srfm_rij'] ) || ! sfp_calculator_is_formulier( $form_id ) || ! is_array( $data ) ) {
        return;
    }
    $id      = (int) $GLOBALS['sfp_calculator_srfm_rij'];
    $contact = sfp_calculator_srfm_contact( $data );
    sfp_calculator_rij_bij(
        $id,
        array(
            'voornaam'    => $contact['voornaam'],
            'achternaam'  => $contact['achternaam'],
            'email'       => $contact['email'],
            'organisatie' => $contact['organisatie'],
            'afdeling'    => $contact['afdeling'],
            'functie'     => $contact['functie'],
            'nieuwsbrief' => $contact['nieuwsbrief'] ? 1 : 0,
            'entry_id'    => (int) $entry_id,
            'afgerond_op' => gmdate( 'Y-m-d H:i:s' ),
            'pdf_status'  => 'wacht',
        )
    );
    sfp_calculator_na_antwoord( $id );
}

/**
 * Contactgegevens uit de inzending, op label (nl en en).
 *
 * @param  array $data Inzending.
 * @return array
 */
function sfp_calculator_srfm_contact( array $data ) {
    $uit    = array(
        'voornaam'    => '',
        'achternaam'  => '',
        'email'       => '',
        'organisatie' => '',
        'afdeling'    => '',
        'functie'     => '',
        'nieuwsbrief' => false,
    );
    $namen  = array(
        'voornaam'    => array( 'voornaam', 'first name', 'firstname', 'naam', 'name' ),
        'achternaam'  => array( 'achternaam', 'last name', 'lastname', 'surname' ),
        'email'       => array( 'e-mail', 'email', 'e-mailadres', 'emailadres', 'email address', 'zakelijk e-mailadres' ),
        'organisatie' => array( 'organisatie', 'bedrijf', 'bedrijfsnaam', 'organisation', 'organization', 'company' ),
        'afdeling'    => array( 'afdeling', 'department', 'team' ),
        'functie'     => array( 'functie', 'job title', 'role', 'position', 'functietitel' ),
        'nieuwsbrief' => array( 'nieuwsbrief', 'newsletter' ),
    );
    foreach ( $data as $sleutel => $waarde ) {
        $label = sfp_calculator_srfm_label( $sleutel );
        if ( '' === $label || is_array( $waarde ) && 'nieuwsbrief' !== $label ) {
            continue;
        }
        foreach ( $namen as $veld => $lijst ) {
            if ( ! in_array( $label, $lijst, true ) || ( '' !== $uit[ $veld ] && false !== $uit[ $veld ] ) ) {
                continue;
            }
            if ( 'email' === $veld ) {
                $uit['email'] = strtolower( sanitize_email( (string) $waarde ) );
            } elseif ( 'nieuwsbrief' === $veld ) {
                $w                  = is_array( $waarde ) ? implode( ',', $waarde ) : (string) $waarde;
                $uit['nieuwsbrief'] = '' !== $w && ! in_array( strtolower( $w ), array( '0', 'false', 'nee', 'no' ), true );
            } else {
                $uit[ $veld ] = mb_substr( sanitize_text_field( (string) $waarde ), 0, in_array( $veld, array( 'organisatie', 'afdeling', 'functie' ), true ) ? 190 : 100 );
            }
        }
        // Een e-mailveld zonder herkenbaar label.
        if ( '' === $uit['email'] && 0 === strpos( (string) $sleutel, 'srfm-email' ) ) {
            $uit['email'] = strtolower( sanitize_email( (string) $waarde ) );
        }
    }
    return $uit;
}

/**
 * Handel een rij af na het antwoord aan de bezoeker (pdf maken).
 *
 * @param  int $id Rij.
 * @return void
 */
function sfp_calculator_na_antwoord( $id ) {
    static $rijen = array();
    static $haak  = false;
    $rijen[]      = (int) $id;
    if ( $haak ) {
        return;
    }
    $haak = true;
    add_action(
        'shutdown',
        function () use ( &$rijen ) {
            if ( function_exists( 'fastcgi_finish_request' ) ) {
                fastcgi_finish_request();
            } elseif ( function_exists( 'litespeed_finish_request' ) ) {
                litespeed_finish_request();
            }
            foreach ( array_unique( $rijen ) as $rij_id ) {
                sfp_calculator_pdf_verwerk( $rij_id );
            }
        },
        1
    );
    sfp_calculator_wachtrij_plannen();
}
