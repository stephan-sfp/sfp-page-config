<?php
/**
 * SFP Page Config - WhatsApp Floating Button
 *
 * Renders a floating WhatsApp chat button in the footer when enabled
 * per page via the `sfp_whatsapp` post meta (checkbox in the metabox).
 *
 * The button is hidden by default. Only pages/posts where the checkbox
 * is checked will show it.
 *
 * Features:
 *  - Appears after 200px scroll (slide-up animation)
 *  - Auto-hides when a SureForms form is in the viewport
 *  - Respects longread pages (hidden there via longread-nav.php CSS)
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', 'sfp_page_config_whatsapp_enqueue' );

/**
 * Enqueue the WhatsApp button stylesheet when enabled for the current page.
 */
function sfp_page_config_whatsapp_enqueue() {

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return;
    }

    $enabled = get_post_meta( get_queried_object_id(), 'sfp_whatsapp', true );
    if ( '1' !== $enabled ) {
        return;
    }

    wp_enqueue_style(
        'sfp-whatsapp',
        SFP_PAGE_CONFIG_URL . 'assets/whatsapp.css',
        array(),
        SFP_PAGE_CONFIG_VERSION
    );
}

add_action( 'wp_footer', 'sfp_page_config_whatsapp_button', 30 );

/**
 * Render the WhatsApp floating button when enabled for the current page.
 */
function sfp_page_config_whatsapp_button() {

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return;
    }

    $enabled = get_post_meta( get_queried_object_id(), 'sfp_whatsapp', true );
    if ( '1' !== $enabled ) {
        return;
    }

    // Get WhatsApp number + message from settings with sensible fallbacks.
    $whatsapp_number  = sfp_page_config_get_setting( 'whatsapp_number', '31850601355' );
    $whatsapp_message = sfp_page_config_get_setting( 'whatsapp_message', 'Hoi, ik heb een vraag' );

    // Only digits in the phone number for the URL.
    $whatsapp_number = preg_replace( '/[^0-9]/', '', $whatsapp_number );

    $whatsapp_href = add_query_arg(
        array(
            'phone'      => $whatsapp_number,
            'text'       => $whatsapp_message,
            'type'       => 'phone_number',
            'app_absent' => '0',
        ),
        'https://api.whatsapp.com/send/'
    );

    ?>
<a href="<?php echo esc_url( $whatsapp_href ); ?>"
   class="whatsapp-float" id="waButton" target="_blank" rel="noopener noreferrer"
   aria-label="WhatsApp">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32" aria-hidden="true" focusable="false">
        <path fill="#ffffff" d="M26.6 5.4C23.8 2.6 20 1 16 1 7.7 1 1 7.7 1 16c0 2.6.7 5.2 2 7.5L1 31l7.7-2c2.2 1.2 4.7 1.9 7.3 1.9 8.3 0 15-6.7 15-15 0-4-1.6-7.8-4.4-10.5zM16 28.4c-2.3 0-4.6-.6-6.6-1.8l-.5-.3-4.6 1.2 1.2-4.5-.3-.5C4 20.4 3.3 18.2 3.3 16c0-7 5.7-12.7 12.7-12.7 3.4 0 6.6 1.3 9 3.7 2.4 2.4 3.7 5.6 3.7 9C28.7 22.7 23 28.4 16 28.4zm7-9.5c-.4-.2-2.3-1.1-2.6-1.2-.3-.1-.6-.2-.9.2-.3.4-1 1.2-1.3 1.5-.2.2-.5.3-.8.1-.4-.2-1.7-.6-3.2-2-1.2-1.1-2-2.4-2.2-2.8-.2-.4 0-.6.2-.8.2-.2.4-.4.6-.7.2-.2.2-.4.3-.6.1-.2 0-.5 0-.7-.1-.2-.9-2.1-1.2-2.9-.3-.8-.6-.7-.9-.7h-.7c-.2 0-.6.1-1 .5s-1.3 1.3-1.3 3.2 1.3 3.7 1.5 3.9c.2.2 2.6 3.9 6.2 5.5.9.4 1.5.6 2.1.8.9.3 1.7.2 2.3.1.7-.1 2.3-.9 2.6-1.8.3-.9.3-1.7.2-1.8 0-.2-.3-.3-.7-.5z"/>
    </svg>
</a>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var waButton = document.getElementById('waButton');
    if (!waButton) return;

    var sureForms = document.querySelectorAll('.srfm-form-container');

    /* Scroll: show after 200px */
    window.addEventListener('scroll', function () {
        if (window.scrollY > 200) {
            waButton.classList.add('is-active');
        } else {
            waButton.classList.remove('is-active');
        }
    }, { passive: true });

    /* Hide when a SureForms form is in the viewport */
    if (sureForms.length > 0) {
        var visibleForms = new Set();
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    visibleForms.add(entry.target);
                } else {
                    visibleForms.delete(entry.target);
                }
            });
            if (visibleForms.size > 0) {
                waButton.classList.add('is-hidden-by-form');
            } else {
                waButton.classList.remove('is-hidden-by-form');
            }
        }, { threshold: 0.05 });

        sureForms.forEach(function (form) { observer.observe(form); });
    }
});
</script>
<?php
}
