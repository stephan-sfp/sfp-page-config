<?php
/**
 * SFP Page Config - Sjabloon voor een artikel in de artikelopmaak
 *
 * Geladen via template_include (includes/artikel.php). Header en footer
 * zijn die van het thema; de inhoud bouwt sfp_page_config_artikel_render().
 *
 * @package SFP_Page_Config
 * @since   2.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<div id="primary" class="content-area primary sfp-artikel-primary">
    <main id="main" class="site-main">
        <?php
        while ( have_posts() ) {
            the_post();
            echo sfp_page_config_artikel_render( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- opgebouwd en ontsnapt in includes/artikel.php.
        }
        ?>
    </main>
</div>
<?php
get_footer();
