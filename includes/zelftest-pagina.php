<?php
/**
 * SFP Page Config - Zelftest: de testpagina
 *
 * De pagina die in SFP Beheer, Zelftest, als testpagina staat, krijgt een
 * eigen weergave: geen kop, menu en voet van het thema, alleen een smalle
 * balk met het logo (niet klikbaar) en een kruisje rechtsboven. Het logo is
 * per test in te stellen (versie voor een witte achtergrond), anders dat van
 * de site.
 *
 * Het kruisje gaat terug naar de pagina op deze site waar de bezoeker
 * vandaan kwam, en anders (direct binnengekomen, via een advertentie of een
 * andere site) naar de homepage. Midden in de test vraagt het eerst of de
 * bezoeker wil stoppen.
 *
 * wp_head() en wp_footer() blijven: statistiek, conversiemeting,
 * cookiemelding en A/B-test laden zoals op elke andere pagina.
 *
 * @package SFP_Page_Config
 * @since   3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De test waarvan deze pagina de testpagina is, of ''.
 *
 * @return string
 */
function sfp_zelftest_pagina_test() {
    static $test = null;
    if ( null !== $test ) {
        return $test;
    }
    $test = '';
    if ( ! function_exists( 'sfp_page_config_onderdeel_aan' ) || ! sfp_page_config_onderdeel_aan( 'zelftest' ) || ! is_page() ) {
        return $test;
    }
    $id = (int) get_queried_object_id();
    if ( $id <= 0 ) {
        return $test;
    }
    foreach ( sfp_zelftest_tests() as $naam ) {
        $inst = sfp_zelftest_instellingen( $naam );
        if ( (int) $inst['testpagina'] === $id && sfp_zelftest_pakket( $naam ) ) {
            $test = $naam;
            break;
        }
    }
    return $test;
}

add_filter( 'template_include', 'sfp_zelftest_pagina_sjabloon', 99 );

/**
 * Eigen sjabloon voor de testpagina.
 *
 * @param  string $sjabloon Sjabloon van het thema.
 * @return string
 */
function sfp_zelftest_pagina_sjabloon( $sjabloon ) {
    if ( '' === sfp_zelftest_pagina_test() ) {
        return $sjabloon;
    }
    $eigen = SFP_PAGE_CONFIG_DIR . 'templates/zelftest-pagina.php';
    return is_readable( $eigen ) ? $eigen : $sjabloon;
}

add_action( 'wp_enqueue_scripts', 'sfp_zelftest_pagina_bestanden', 20 );

/**
 * Stijl en script van de testpagina, al in de kop (geen verspringing).
 *
 * @return void
 */
function sfp_zelftest_pagina_bestanden() {
    if ( '' === sfp_zelftest_pagina_test() ) {
        return;
    }
    sfp_zelftest_stijl();
    wp_enqueue_script( 'sfp-zelftest-pagina', SFP_PAGE_CONFIG_URL . 'assets/zelftest/pagina.js', array(), SFP_PAGE_CONFIG_VERSION, true );
}

add_filter( 'body_class', 'sfp_zelftest_pagina_body_class' );

/**
 * Klasse op body voor de testpagina.
 *
 * @param  array $klassen Klassen.
 * @return array
 */
function sfp_zelftest_pagina_body_class( $klassen ) {
    if ( '' !== sfp_zelftest_pagina_test() ) {
        $klassen[] = 'sfp-zt-testpagina';
    }
    return $klassen;
}

/**
 * Het logo van de site (niet klikbaar), of de naam van de site.
 *
 * @return string HTML.
 */
function sfp_zelftest_pagina_logo() {
    // Eerst het logo dat in SFP Beheer, Zelftest, voor de testpagina staat
    // (versie origineel, voor een witte achtergrond), anders het logo van de site.
    $test = sfp_zelftest_pagina_test();
    $id   = '' !== $test ? (int) sfp_zelftest_instellingen( $test )['logo'] : 0;
    if ( $id <= 0 ) {
        $id = (int) get_theme_mod( 'custom_logo' );
    }
    if ( $id > 0 ) {
        $img = wp_get_attachment_image(
            $id,
            'full',
            false,
            array(
                'class'    => 'sfp-zt-top-logo',
                'alt'      => get_bloginfo( 'name' ),
                'loading'  => 'eager',
                'decoding' => 'async',
            )
        );
        if ( '' !== $img ) {
            return $img;
        }
    }
    return '<span class="sfp-zt-top-naam">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
}
