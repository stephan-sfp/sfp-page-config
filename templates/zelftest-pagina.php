<?php
/**
 * SFP Page Config - Zelftest: sjabloon van de testpagina
 *
 * Geen kop, menu en voet van het thema. Wel wp_head(), wp_body_open() en
 * wp_footer(), zodat statistiek, conversiemeting en cookiemelding laden.
 *
 * @package SFP_Page_Config
 * @since   3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sfp_zt_sluiten = sfp_zelftest_pagina_tekst( 'zelftest_sluiten' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="sfp-zt-top">
    <div class="sfp-zt-top-in">
        <?php echo sfp_zelftest_pagina_logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image of esc_html. ?>
        <button type="button" class="sfp-zt-sluit" id="sfp-zt-sluit" aria-label="<?php echo esc_attr( $sfp_zt_sluiten ); ?>" title="<?php echo esc_attr( $sfp_zt_sluiten ); ?>" data-home="<?php echo esc_url( home_url( '/' ) ); ?>">
            <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
        </button>
    </div>
</header>
<main id="sfp-zt-inhoud" class="sfp-zt-testpagina-inhoud">
<?php
while ( have_posts() ) {
    the_post();
    the_content();
}
?>
</main>
<dialog class="sfp-zt-stop" id="sfp-zt-stop" aria-labelledby="sfp-zt-stop-vraag">
    <p id="sfp-zt-stop-vraag"><?php echo esc_html( sfp_zelftest_pagina_tekst( 'zelftest_stop' ) ); ?></p>
    <div class="sfp-zt-stop-knoppen">
        <button type="button" class="sfp-zt-stop-nee" id="sfp-zt-stop-nee"><?php echo esc_html( sfp_zelftest_pagina_tekst( 'zelftest_stop_nee' ) ); ?></button>
        <button type="button" class="sfp-zt-stop-ja" id="sfp-zt-stop-ja"><?php echo esc_html( sfp_zelftest_pagina_tekst( 'zelftest_stop_ja' ) ); ?></button>
    </div>
</dialog>
<?php wp_footer(); ?>
</body>
</html>
