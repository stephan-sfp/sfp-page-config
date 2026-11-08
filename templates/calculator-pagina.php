<?php
/**
 * SFP Page Config - Incompanycalculator: sjabloon van de calculatorpagina
 *
 * Geen kop, menu en voet van het thema. Wel wp_head(), wp_body_open() en
 * wp_footer(), zodat statistiek, conversiemeting en cookiemelding laden.
 *
 * @package SFP_Page_Config
 * @since   3.36.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sfp_calc_t = sfp_calculator_teksten();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="sfp-calc-top">
    <div class="sfp-calc-top-in">
        <?php echo sfp_calculator_pagina_logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image of esc_html. ?>
        <a class="sfp-calc-sluit" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $sfp_calc_t['sluiten'] ); ?>" title="<?php echo esc_attr( $sfp_calc_t['sluiten'] ); ?>">
            <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
        </a>
    </div>
</header>
<main id="sfp-calc-inhoud" class="sfp-calc-pagina-inhoud">
<?php
while ( have_posts() ) {
    the_post();
    the_content();
}
?>
</main>
<?php wp_footer(); ?>
</body>
</html>
