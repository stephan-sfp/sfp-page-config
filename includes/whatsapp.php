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

    // Get WhatsApp number from settings with fallback to default.
    $whatsapp_number = sfp_page_config_get_setting( 'whatsapp_number', '31850601355' );
    $whatsapp_href = sprintf( 'https://api.whatsapp.com/send/?phone=%s&text=Hoi%%2C+ik+heb+een+vraag&type=phone_number&app_absent=0', esc_attr( $whatsapp_number ) );

    ?>
<a href="<?php echo esc_url( $whatsapp_href ); ?>"
   class="whatsapp-float" id="waButton" target="_blank" rel="noopener noreferrer">
    <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp Chat" width="32" height="32">
</a>
<style>
.whatsapp-float {
    position: fixed;
    width: 60px;
    height: 60px;
    bottom: 25px;
    left: 50%;
    transform: translateX(-50%) translateY(120px);
    background-color: #25d366;
    border-radius: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s ease;
}
.whatsapp-float.is-active {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
}
.whatsapp-float.is-hidden-by-form {
    opacity: 0 !important;
    visibility: hidden !important;
    transform: translateX(-50%) translateY(120px) !important;
    pointer-events: none;
}
.whatsapp-float img {
    width: 32px;
    height: 32px;
    filter: brightness(0) invert(1);
}
@media screen and (max-width: 767px) {
    .whatsapp-float { width: 55px; height: 55px; bottom: 20px; }
}
</style>
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
