<?php
/**
 * Juridisch footerblok in groepen (footer-widget-4).
 *
 * Opmaak en gedrag voor de markup
 *   <nav class="sfp-fj"><details class="sfp-fj__groep" open>
 *     <summary class="sfp-fj__kop">Groep</summary>
 *     <ul class="sfp-fj__links"><li><a>...</a></li></ul>
 *   </details></nav>
 * die in footer-widget-4 van elke netwerksite staat.
 *
 * Desktop: groepen naast elkaar en altijd open. Tot 921 px: onder elkaar,
 * dicht, uitschuifbaar met een tik op de groepsnaam. Zonder JavaScript blijft
 * alles open.
 *
 * Kleur uit Astra global color 2 (primair), font uit --sfp-kopfont
 * (includes/kopfont.php). Geen merkhex en geen fontnaam in dit bestand.
 * Alleen geladen als footer-widget-4 inhoud heeft.
 *
 * Terugdraaien: dit bestand uit de lijst in sfp-page-config.php halen.
 *
 * @package SFP_Page_Config
 * @since   2.9.7
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Of het blok op deze pagina nodig is.
 *
 * @return bool
 */
function sfp_page_config_footer_groepen_actief() {
    return ! is_admin() && is_active_sidebar( 'footer-widget-4' );
}

add_action( 'wp_head', 'sfp_page_config_footer_groepen_css', 20 );

/**
 * Print de CSS van het footerblok.
 */
function sfp_page_config_footer_groepen_css() {
    if ( ! sfp_page_config_footer_groepen_actief() ) {
        return;
    }
    echo '<style id="sfp-footer-groepen">' . sfp_page_config_footer_groepen_css_code() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste CSS uit dit bestand.
}

/**
 * De CSS zelf.
 *
 * @return string
 */
function sfp_page_config_footer_groepen_css_code() {
    return '.sfp-fj{display:grid;grid-auto-flow:column;grid-auto-columns:max-content;justify-content:center;column-gap:56px;margin:0;text-align:center}.sfp-fj .sfp-fj__kop{display:block;list-style:none;margin:0 0 6px;font-family:var(--sfp-kopfont,inherit);font-weight:var(--sfp-kopfont-gewicht,inherit);font-size:16px;line-height:1.3;text-transform:uppercase;color:var(--ast-global-color-2);cursor:default}.sfp-fj .sfp-fj__kop::-webkit-details-marker{display:none}.sfp-fj .sfp-fj__kop::marker{content:""}.sfp-fj .sfp-fj__links{margin:0;padding:0;list-style:none;font-size:14px;line-height:1.8}.sfp-fj .sfp-fj__links li{display:inline;white-space:nowrap}.sfp-fj .sfp-fj__links li:not(:last-child)::after{content:"\\2022";display:inline-block;padding:0 6px;text-decoration:none}@media (max-width:921px){.sfp-fj{grid-auto-flow:row;grid-auto-columns:auto;grid-template-columns:minmax(0,1fr)}.sfp-fj .sfp-fj__kop{display:inline-flex;align-items:center;gap:10px;margin:0;padding:10px 0;cursor:pointer}.sfp-fj .sfp-fj__kop::after{content:"";width:7px;height:7px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:translateY(-2px) rotate(45deg);transition:transform .2s ease}.sfp-fj .sfp-fj__groep[open] .sfp-fj__kop::after{transform:translateY(2px) rotate(-135deg)}.sfp-fj .sfp-fj__links{padding:0 0 10px}}';
}

add_action( 'wp_footer', 'sfp_page_config_footer_groepen_js', 20 );

/**
 * Print het script dat de groepen tot 921 px dichtklapt.
 */
function sfp_page_config_footer_groepen_js() {
    if ( ! sfp_page_config_footer_groepen_actief() ) {
        return;
    }
    echo '<script id="sfp-footer-groepen-js">' . sfp_page_config_footer_groepen_js_code() . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste JS uit dit bestand.
}

/**
 * Het script zelf.
 *
 * @return string
 */
function sfp_page_config_footer_groepen_js_code() {
    return "(function(){var mq=window.matchMedia('(max-width: 921px)');function zet(){var g=document.querySelectorAll('.sfp-fj__groep');for(var i=0;i<g.length;i++){if(mq.matches){g[i].removeAttribute('open');}else{g[i].setAttribute('open','');}}}\nfunction klik(e){var s=e.target.closest?e.target.closest('.sfp-fj__kop'):null;if(s&&!mq.matches){e.preventDefault();}}\nif(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',zet);}else{zet();}\ndocument.addEventListener('click',klik);if(mq.addEventListener){mq.addEventListener('change',zet);}else if(mq.addListener){mq.addListener(zet);}})();";
}

add_filter( 'rocket_rucss_inline_atts_exclusions', 'sfp_page_config_footer_groepen_rucss' );

/**
 * Laat WP Rocket Remove Unused CSS het inline CSS-blok ongemoeid.
 *
 * @param  array $exclusions Uitsluitingen op attribuut.
 * @return array
 */
function sfp_page_config_footer_groepen_rucss( $exclusions ) {
    $exclusions   = (array) $exclusions;
    $exclusions[] = 'sfp-footer-groepen';
    return $exclusions;
}

add_filter( 'rocket_delay_js_exclusions', 'sfp_page_config_footer_groepen_delay' );

/**
 * Laat WP Rocket het script niet vertragen, anders staan de groepen op
 * mobiel open tot de eerste aanraking en verspringt de footer.
 *
 * @param  array $exclusions Uitsluitingen.
 * @return array
 */
function sfp_page_config_footer_groepen_delay( $exclusions ) {
    $exclusions   = (array) $exclusions;
    $exclusions[] = 'sfp-footer-groepen-js';
    $exclusions[] = 'sfp-fj__groep';
    return $exclusions;
}
