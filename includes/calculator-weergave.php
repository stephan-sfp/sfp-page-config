<?php
/**
 * SFP Page Config - Incompanycalculator: blok, code en pagina (sinds 3.36.0).
 *
 * Blok sfp/calculator en code [sfp_calculator]. Volledig op de
 * calculatorpagina (/incompany/calculator/), ingebed op de incompany-pagina's
 * (ingebed="1"): alleen route en aantal met een eerste begroting, en Verder
 * opent de calculatorpagina met die keuzes. Script en stijl laden alleen
 * waar het blok staat. De calculatorpagina krijgt een eigen sjabloon zonder
 * kop en voet van het thema, zoals de testpagina van de zelftest.
 *
 * @package SFP_Page_Config
 * @since   3.36.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'sfp_calculator_registreer' );

/**
 * Registreer code en blok.
 *
 * @return void
 */
function sfp_calculator_registreer() {
    if ( ! shortcode_exists( 'sfp_calculator' ) ) {
        add_shortcode( 'sfp_calculator', 'sfp_calculator_shortcode' );
    }
    wp_register_script( 'sfp-calculator-editor', sfp_page_config_asset_url( 'calculator/editor.js' ), array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor' ), SFP_PAGE_CONFIG_VERSION, true );
    if ( ! WP_Block_Type_Registry::get_instance()->is_registered( 'sfp/calculator' ) ) {
        register_block_type(
            'sfp/calculator',
            array(
                'api_version'     => 3,
                'title'           => 'Incompanycalculator',
                'description'     => 'De incompanycalculator: volledig op de calculatorpagina, ingebed (route en aantal) op de incompany-pagina\'s.',
                'category'        => 'sfp-bouwstenen',
                'editor_script'   => 'sfp-calculator-editor',
                'attributes'      => array(
                    'ingebed' => array(
                        'type'    => 'boolean',
                        'default' => false,
                    ),
                ),
                'supports'        => array( 'html' => false ),
                'render_callback' => function ( $attributen ) {
                    return sfp_calculator_shortcode( array( 'ingebed' => ! empty( $attributen['ingebed'] ) ? '1' : '0' ) );
                },
            )
        );
    }
}

add_action( 'enqueue_block_editor_assets', 'sfp_calculator_editor_gegevens' );

/**
 * Stand voor het blok in de editor.
 *
 * @return void
 */
function sfp_calculator_editor_gegevens() {
    wp_add_inline_script(
        'sfp-calculator-editor',
        'window.sfpCalculatorEditor=' . wp_json_encode(
            array(
                'aan'   => sfp_page_config_onderdeel_aan( 'calculator' ),
                'proef' => sfp_calculator_proef(),
            )
        ) . ';',
        'before'
    );
}

/**
 * De calculator op de pagina.
 *
 * @param  array|string $atts Attributen.
 * @return string
 */
function sfp_calculator_shortcode( $atts ) {
    $atts    = shortcode_atts( array( 'ingebed' => '0' ), (array) $atts, 'sfp_calculator' );
    $ingebed = in_array( (string) $atts['ingebed'], array( '1', 'true', 'ja' ), true );
    if ( ! sfp_calculator_zichtbaar() ) {
        if ( current_user_can( 'edit_posts' ) && function_exists( 'sfp_page_config_onderdeel_aan' ) && ! sfp_page_config_onderdeel_aan( 'calculator' ) ) {
            return '<p class="sfp-calc-leeg">Incompanycalculator: het onderdeel staat uit (SFP Beheer, Onderdelen).</p>';
        }
        return '';
    }
    static $al = false;
    if ( $al ) {
        return '';
    }
    $al = true;

    $inst  = sfp_calculator_instellingen();
    $norm  = sfp_calculator_site_norm( $inst );
    $taal  = sfp_page_config_taal();
    $form  = (int) $inst['formulier'];
    $html  = '';
    if ( ! $ingebed && $form > 0 && 'publish' === get_post_status( $form ) && shortcode_exists( 'sureforms' ) ) {
        $html = do_shortcode( '[sureforms id="' . $form . '"]' );
    }
    $config = array(
        'taal'       => $taal,
        'rest'       => esc_url_raw( rest_url( 'sfp/v1/calculator/' ) ),
        // Alleen ingelogd een nonce: een bewaarde pagina voor bezoekers heeft er geen nodig.
        'nonce'      => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
        'ingebed'    => $ingebed,
        'norm'       => $norm,
        't'          => sfp_calculator_teksten( $taal ),
        'uitleg'     => $inst['uitleg'],
        'pagina'     => sfp_calculator_url( 'calculatorpagina' ),
        'boek'       => sfp_calculator_url( 'boekpagina' ),
        'coaching'   => sfp_calculator_url( 'coaching_pagina' ),
        'open'       => sfp_calculator_url( 'open_pagina' ),
        'formulier'  => '' !== $html ? $form : 0,
        'proef'      => sfp_calculator_proef(),
        'beheerder'  => current_user_can( 'manage_options' ),
        'privacy'    => (string) get_privacy_policy_url(),
        'contact'    => sfp_calculator_contact_config( $inst ),
    );

    sfp_page_config_css_inline( 'sfp-calculator', 'calculator/calculator.css' );
    if ( function_exists( 'sfp_page_config_blokken_css_variabelen' ) ) {
        wp_add_inline_style( 'sfp-calculator', sfp_page_config_blokken_css_variabelen() );
    }
    wp_enqueue_script( 'sfp-calculator-motor', sfp_page_config_asset_url( 'calculator/motor.js' ), array(), SFP_PAGE_CONFIG_VERSION, true );
    wp_enqueue_script( 'sfp-calculator', sfp_page_config_asset_url( 'calculator/calculator.js' ), array( 'sfp-calculator-motor' ), SFP_PAGE_CONFIG_VERSION, true );
    wp_add_inline_script( 'sfp-calculator', 'window.sfpCalculatorConfig=' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );

    $t = sfp_calculator_teksten( $taal );
    return '<div class="sfp-calc' . ( $ingebed ? ' sfp-calc--ingebed' : '' ) . '" id="sfp-calculator" lang="' . esc_attr( $taal ) . '">'
        . ( sfp_calculator_proef() ? '<p class="sfp-calc-proef">' . esc_html( $t['proef_balk'] ) . '</p>' : '' )
        . '<div class="sfp-calc-app" aria-live="polite"><p class="sfp-calc-laden">&nbsp;</p></div>'
        . ( '' !== $html ? '<div class="sfp-calc-formulier" hidden>' . $html . '</div>' : '' )
        . '</div>';
}

/* =========================================================================
 * Calculatorpagina
 * ====================================================================== */

/**
 * Is dit de calculatorpagina?
 *
 * @return bool
 */
function sfp_calculator_is_pagina() {
    static $uit = null;
    if ( null !== $uit ) {
        return $uit;
    }
    $id  = (int) sfp_calculator_instellingen()['calculatorpagina'];
    $uit = $id > 0 && is_page( $id ) && function_exists( 'sfp_page_config_onderdeel_aan' ) && sfp_page_config_onderdeel_aan( 'calculator' );
    return $uit;
}

add_filter( 'template_include', 'sfp_calculator_pagina_sjabloon', 99 );

/**
 * Eigen sjabloon voor de calculatorpagina.
 *
 * @param  string $sjabloon Sjabloon van het thema.
 * @return string
 */
function sfp_calculator_pagina_sjabloon( $sjabloon ) {
    if ( ! sfp_calculator_is_pagina() ) {
        return $sjabloon;
    }
    $eigen = SFP_PAGE_CONFIG_DIR . 'templates/calculator-pagina.php';
    return is_readable( $eigen ) ? $eigen : $sjabloon;
}

add_filter( 'body_class', 'sfp_calculator_pagina_body_class' );

/**
 * Klasse op body voor de calculatorpagina.
 *
 * @param  array $klassen Klassen.
 * @return array
 */
function sfp_calculator_pagina_body_class( $klassen ) {
    if ( ! is_admin() && did_action( 'wp' ) && sfp_calculator_is_pagina() ) {
        $klassen[] = 'sfp-calc-pagina';
    }
    return $klassen;
}

add_filter( 'pre_get_rocket_option_remove_unused_css', 'sfp_calculator_pagina_geen_usedcss' );

/**
 * Geen Remove Unused CSS op de calculatorpagina: de schermen tekent het script.
 *
 * @param  mixed $waarde Waarde.
 * @return mixed
 */
function sfp_calculator_pagina_geen_usedcss( $waarde ) {
    if ( is_admin() || ! did_action( 'wp' ) ) {
        return $waarde;
    }
    return sfp_calculator_is_pagina() ? 0 : $waarde;
}

/**
 * Het logo bovenaan de calculatorpagina.
 *
 * @return string HTML.
 */
function sfp_calculator_pagina_logo() {
    $id = (int) sfp_calculator_instellingen()['logo'];
    if ( $id <= 0 ) {
        $id = (int) get_theme_mod( 'custom_logo' );
    }
    if ( $id > 0 && 'image/svg+xml' === get_post_mime_type( $id ) ) {
        $url = wp_get_attachment_url( $id );
        if ( $url ) {
            return '<img src="' . esc_url( $url ) . '" class="sfp-calc-top-logo" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" loading="eager" decoding="async">';
        }
    }
    if ( $id > 0 ) {
        $img = wp_get_attachment_image(
            $id,
            'medium',
            false,
            array(
                'class'    => 'sfp-calc-top-logo',
                'alt'      => get_bloginfo( 'name' ),
                'loading'  => 'eager',
                'decoding' => 'async',
            )
        );
        if ( '' !== $img ) {
            return $img;
        }
    }
    return '<span class="sfp-calc-top-naam">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
}

/**
 * De contactpersoon voor het start- en eindscherm: uit Calculator, anders
 * telefoon en e-mail uit het onderdeel Contact van de site.
 *
 * @param  array $inst Instellingen van de calculator.
 * @return array{naam: string, functie: string, telefoon: string, email: string, foto: string}
 */
function sfp_calculator_contact_config( array $inst ) {
    $c    = isset( $inst['contact'] ) && is_array( $inst['contact'] ) ? $inst['contact'] : array();
    $site = function_exists( 'sfp_page_config_contact' ) ? sfp_page_config_contact() : array();
    $tel  = trim( (string) ( $c['telefoon'] ?? '' ) );
    if ( '' === $tel && ! empty( $site['telefoon']['tekst'] ) ) {
        $tel = (string) $site['telefoon']['tekst'];
    }
    $mail = trim( (string) ( $c['email'] ?? '' ) );
    if ( '' === $mail && ! empty( $site['email'] ) ) {
        $mail = (string) $site['email'];
    }
    $foto = (int) ( $c['foto'] ?? 0 );
    return array(
        'naam'     => (string) ( $c['naam'] ?? '' ),
        'functie'  => (string) ( $c['functie'] ?? '' ),
        'telefoon' => $tel,
        'email'    => $mail,
        'foto'     => $foto ? (string) wp_get_attachment_image_url( $foto, 'thumbnail' ) : '',
    );
}
