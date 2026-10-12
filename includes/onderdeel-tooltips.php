<?php
/**
 * SFP Page Config - Onderdeel Tooltips
 *
 * De functie van de losse plugin SFP Tooltip (1.4.0), als onderdeel van deze
 * plugin (besluit Stephan, 10 oktober 2026). Bestaande tooltips blijven
 * werken zonder omzetting: de inhoud is en blijft
 * `<span class="tooltip" data-tooltip="...">`, en de knop in de editor heet
 * nog steeds `custom/tooltip`.
 *
 * Overgang zonder gat of dubbeling: zolang de oude plugin op een site actief
 * is, doet dit onderdeel niets. Staat de oude plugin uit, dan neemt dit
 * onderdeel het bij het eerstvolgende verzoek over. Per verzoek is er dus
 * altijd precies één.
 *
 * Wat anders is dan in de oude plugin:
 *   - de opmaak zit in de plugin en volgt het merk van de site via de
 *     kleurrollen; het vak "Tooltip CSS" (optie `sfp_tooltip_css`) wordt niet
 *     meer gelezen. De uitkomst is op de zeven sites met rolvariabelen gelijk
 *     aan wat er stond (gemeten 11 oktober 2026);
 *   - de tekst voor schermlezers volgt de taal van de site.
 *
 * Terugdraaien op een site: de oude plugin weer aanzetten, of het onderdeel
 * Tooltips uitzetten in SFP Beheer.
 *
 * @package SFP_Page_Config
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Draait de oude plugin SFP Tooltip nog op deze site?
 *
 * @return bool
 */
function sfp_page_config_tooltips_oude_plugin() {
    return defined( 'SFP_TOOLTIP_VERSION' );
}

/**
 * Verzorgt dit onderdeel de tooltips op deze site?
 *
 * Pas betrouwbaar vanaf `plugins_loaded`: de oude plugin laadt na deze.
 *
 * @return bool
 */
function sfp_page_config_tooltips_actief() {
    return ! sfp_page_config_tooltips_oude_plugin() && sfp_page_config_onderdeel_aan( 'tooltips' );
}

add_filter( 'wp_kses_allowed_html', 'sfp_page_config_tooltips_kses', 10, 2 );

/**
 * WordPress mag het attribuut data-tooltip op een span bewaren.
 *
 * @param  array  $tags    Toegestane tags.
 * @param  string $context Context.
 * @return array
 */
function sfp_page_config_tooltips_kses( $tags, $context ) {
    if ( 'post' !== $context || ! sfp_page_config_tooltips_actief() ) {
        return $tags;
    }
    if ( ! isset( $tags['span'] ) || ! is_array( $tags['span'] ) ) {
        $tags['span'] = array();
    }
    foreach ( array( 'class', 'data-tooltip', 'tabindex', 'role', 'aria-label' ) as $attribuut ) {
        $tags['span'][ $attribuut ] = true;
    }
    return $tags;
}

/**
 * Staan er tooltips op deze pagina?
 *
 * Standaard: alleen op een enkele pagina waarvan de inhoud een tooltip
 * bevat. Het filter `sfp_tooltip_force_load` uit de oude plugin blijft
 * werken voor wie tooltips buiten de inhoud heeft staan.
 *
 * @return bool
 */
function sfp_page_config_tooltips_nodig() {
    if ( apply_filters( 'sfp_tooltip_force_load', false ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- naam uit de oude plugin.
        return true;
    }
    if ( ! is_singular() ) {
        return false;
    }
    $post = get_queried_object();
    if ( ! $post instanceof WP_Post || '' === (string) $post->post_content ) {
        return false;
    }
    return false !== strpos( $post->post_content, 'data-tooltip' )
        || (bool) preg_match( '/class=["\'][^"\']*\btooltip\b/i', $post->post_content );
}

add_action( 'enqueue_block_editor_assets', 'sfp_page_config_tooltips_editor' );

/**
 * De knop Tooltip in de werkbalk van de editor.
 */
function sfp_page_config_tooltips_editor() {
    if ( ! sfp_page_config_tooltips_actief() ) {
        return;
    }
    wp_enqueue_script(
        'sfp-tooltip-format',
        SFP_PAGE_CONFIG_URL . 'assets/tooltip/format.js',
        array( 'wp-rich-text', 'wp-block-editor', 'wp-element', 'wp-data', 'wp-components' ),
        SFP_PAGE_CONFIG_VERSION,
        true
    );
    // De bron van een tooltip komt in de bronnenlijst van een artikel; dat
    // vinkje staat alleen waar die lijst bestaat.
    $talen = array(
        'nl' => 'Bron',
        'en' => 'Source',
        'pt' => 'Fonte',
    );
    $taal  = function_exists( 'sfp_page_config_taal' ) ? sfp_page_config_taal() : 'nl';
    wp_add_inline_script(
        'sfp-tooltip-format',
        'window.sfpTooltipEditor=' . wp_json_encode(
            array(
                'bron'    => isset( $talen[ $taal ] ) ? $talen[ $taal ] : 'Bron',
                'bronnen' => function_exists( 'sfp_page_config_artikel_aan' ) && sfp_page_config_artikel_aan(),
                'types'   => function_exists( 'sfp_page_config_artikel_posttypes' ) ? array_values( sfp_page_config_artikel_posttypes() ) : array( 'post' ),
            )
        ) . ';',
        'before'
    );
    wp_enqueue_style(
        'sfp-tooltip-editor',
        SFP_PAGE_CONFIG_URL . 'assets/tooltip/editor.css',
        array(),
        SFP_PAGE_CONFIG_VERSION
    );
}

add_action( 'wp_enqueue_scripts', 'sfp_page_config_tooltips_script' );

/**
 * Het script dat de tooltip toont, alleen op pagina's met tooltips.
 */
function sfp_page_config_tooltips_script() {
    if ( ! sfp_page_config_tooltips_actief() || ! sfp_page_config_tooltips_nodig() ) {
        return;
    }
    wp_enqueue_script(
        'sfp-tooltip-frontend',
        SFP_PAGE_CONFIG_URL . 'assets/tooltip/frontend.js',
        array(),
        SFP_PAGE_CONFIG_VERSION,
        true
    );
    wp_add_inline_script(
        'sfp-tooltip-frontend',
        'window.sfpTooltipTeksten=' . wp_json_encode( array( 'toon' => sfp_page_config_tekst( 'tooltip_toon' ) ) ) . ';',
        'before'
    );
}

/**
 * De kleurvariabelen van de tooltip, als losse declaraties (zonder selector),
 * zodat het beheer ze ook in een voorbeeld kan zetten.
 *
 * @return string
 */
function sfp_page_config_tooltips_css_variabelen() {
    $brand   = sfp_page_config_get_brand();
    $primair = isset( $brand['primary'] ) && '' !== $brand['primary'] ? $brand['primary'] : 'currentColor';
    $knop    = isset( $brand['cta_bg'] ) && '' !== $brand['cta_bg'] ? $brand['cta_bg'] : 'currentColor';
    return '--sfp-tt-vlak:var(--sfp-rol-primair,' . $primair . ');--sfp-tt-accent:var(--sfp-rol-tertiair,var(--sfp-rol-secundair,' . $knop . '));--sfp-tt-tekst:var(--sfp-rol-wit,#fff);';
}

/**
 * De opmaak van de tooltip. Kleuren uit de rolvariabelen van het snippet
 * "Kleuren: rollen van het palet", met de Astra-waarden als terugval. Geen
 * merkkleur in code.
 *
 *   vlak    primair
 *   accent  tertiair, anders secundair, anders de knopkleur
 *   tekst   wit
 *
 * @return string
 */
function sfp_page_config_tooltips_css() {
    return ':root{' . sfp_page_config_tooltips_css_variabelen() . '}'
        . '.tooltip{position:relative;display:inline;border-bottom:1px dotted var(--sfp-tt-vlak);cursor:pointer}'
        . '.tooltip:hover{border-bottom-color:var(--sfp-tt-accent)}'
        . '.tooltiptext{display:none;width:90vw;max-width:300px;background:var(--sfp-tt-vlak);color:var(--sfp-tt-tekst);text-align:center;border-radius:6px;padding:12px;position:absolute;z-index:9999!important;box-shadow:0 4px 15px rgba(0,0,0,.2);font-size:14px;line-height:1.4;pointer-events:auto!important}'
        . '.tooltiptext a{color:var(--sfp-tt-accent)!important;text-decoration:underline!important;cursor:pointer!important;pointer-events:auto!important}'
        . '.tooltiptext a:hover{text-decoration:none!important}'
        . '.tooltiptext::after{content:"";position:absolute;left:var(--arrow-position,50%);transform:translateX(-50%);border-width:8px;border-style:solid}'
        . '.tooltiptext:not(.is-bottom)::after{top:100%;border-color:var(--sfp-tt-vlak) transparent transparent transparent}'
        . '.tooltiptext.is-bottom::after{top:-16px;border-color:transparent transparent var(--sfp-tt-vlak) transparent}'
        . '.tooltip:focus-visible{outline:2px solid var(--sfp-tt-accent);outline-offset:2px;border-radius:2px}';
}

add_action( 'wp_head', 'sfp_page_config_tooltips_head_css' );

/**
 * Zet de opmaak in de head, alleen op pagina's met tooltips. Zelfde id als in
 * de oude plugin, zodat een bestaande uitzondering in een cacheplugin blijft
 * werken.
 */
function sfp_page_config_tooltips_head_css() {
    if ( ! sfp_page_config_tooltips_actief() || ! sfp_page_config_tooltips_nodig() ) {
        return;
    }
    echo '<style id="sfp-tooltip-css">' . sfp_page_config_tooltips_css() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste CSS uit de plugin plus gevalideerde kleuren.
}

add_filter( 'rocket_rucss_inline_atts_exclusions', 'sfp_page_config_tooltips_rucss' );

/**
 * WP Rocket Remove Unused CSS laat de opmaak staan: het tekstvlak bestaat
 * pas na een klik en is bij het meten dus niet in beeld.
 *
 * @param  array $uitsluitingen Uitsluitingen op attribuut.
 * @return array
 */
function sfp_page_config_tooltips_rucss( $uitsluitingen ) {
    $uitsluitingen   = (array) $uitsluitingen;
    $uitsluitingen[] = 'sfp-tooltip-css';
    return $uitsluitingen;
}
