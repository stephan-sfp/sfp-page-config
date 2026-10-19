<?php
/**
 * SFP Page Config - Tools: weergave
 *
 * De tool op de pagina: het kader met de beschrijving als JSON, de tekst
 * zonder script, "Hoe deze test rekent" en het formulier na de uitslag.
 * Plus de shortcode [sfp_tool] en het blok Tool voor een tool op een
 * andere pagina (hele tool of compact).
 *
 * @package SFP_Page_Config
 * @since   3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De instellingen voor het script, één keer per pagina.
 *
 * @return array
 */
function sfp_page_config_tool_config() {
    $teksten = array();
    $alle    = function_exists( 'sfp_page_config_artikel_teksten' ) ? sfp_page_config_artikel_teksten() : array();
    $taal    = function_exists( 'sfp_page_config_artikel_taal' ) ? sfp_page_config_artikel_taal() : 'en';
    $basis   = isset( $alle['en'] ) ? $alle['en'] : array();
    $eigen   = isset( $alle[ $taal ] ) ? $alle[ $taal ] : array();
    foreach ( $basis as $sleutel => $waarde ) {
        if ( 0 === strpos( $sleutel, 'tool' ) ) {
            $teksten[ $sleutel ] = isset( $eigen[ $sleutel ] ) ? $eigen[ $sleutel ] : $waarde;
        }
    }
    return array(
        'rest'     => esc_url_raw( rest_url( 'sfp/v1/tool/' ) ),
        'vendor'   => esc_url_raw( SFP_PAGE_CONFIG_URL . 'assets/vendor/' ),
        'site'     => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
        'sitenaam' => wp_strip_all_tags( get_bloginfo( 'name' ) ),
        'siteurl'  => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
        'versie'   => SFP_PAGE_CONFIG_VERSION,
        'teksten'  => $teksten,
    );
}

/**
 * De bestanden van de motor, één keer per pagina.
 *
 * @param string $soort Soort tool (bepaalt welk soortscript meegaat).
 */
function sfp_page_config_tool_bestanden( $soort = 'test' ) {
    static $klaar = array();
    if ( empty( $klaar['basis'] ) ) {
        $klaar['basis'] = true;
        wp_enqueue_style( 'sfp-tool', SFP_PAGE_CONFIG_URL . 'assets/tool/tool.css', array(), SFP_PAGE_CONFIG_VERSION );
        if ( function_exists( 'sfp_page_config_blokken_css_variabelen' ) ) {
            wp_add_inline_style( 'sfp-tool', sfp_page_config_blokken_css_variabelen() );
        }
        wp_enqueue_script( 'sfp-tool-motor', SFP_PAGE_CONFIG_URL . 'assets/tool/motor.js', array(), SFP_PAGE_CONFIG_VERSION, true );
        wp_add_inline_script( 'sfp-tool-motor', 'window.sfpToolConfig=' . wp_json_encode( sfp_page_config_tool_config(), JSON_UNESCAPED_UNICODE ) . ';', 'before' );
    }
    if ( empty( $klaar[ $soort ] ) && isset( sfp_page_config_tool_soorten()[ $soort ] ) ) {
        $klaar[ $soort ] = true;
        wp_enqueue_script( 'sfp-tool-' . $soort, SFP_PAGE_CONFIG_URL . 'assets/tool/' . $soort . '.js', array( 'sfp-tool-motor' ), SFP_PAGE_CONFIG_VERSION, true );
    }
}

add_action( 'wp_enqueue_scripts', 'sfp_page_config_tool_bestanden_vooraf', 20 );

/**
 * Op de pagina van een tool de bestanden al in de head aanmelden, zodat de
 * stijl niet in de footer belandt.
 */
function sfp_page_config_tool_bestanden_vooraf() {
    if ( ! sfp_page_config_tool_aan() || ! is_singular( 'tool' ) ) {
        return;
    }
    $b = sfp_page_config_tool_beschrijving( get_queried_object() );
    if ( $b ) {
        sfp_page_config_tool_bestanden( $b['soort'] );
    }
}

add_filter( 'rocket_delay_js_exclusions', 'sfp_page_config_tool_delay' );

/**
 * WP Rocket stelt de motor niet uit: de tool moet er staan zodra de pagina
 * er is, ook zonder aanraking (deellink opent direct op het resultaat).
 *
 * @param  array $uitsluitingen Uitsluitingen.
 * @return array
 */
function sfp_page_config_tool_delay( $uitsluitingen ) {
    $uitsluitingen   = (array) $uitsluitingen;
    $uitsluitingen[] = 'sfp-page-config/assets/tool/';
    $uitsluitingen[] = 'sfpToolConfig';
    return $uitsluitingen;
}

/**
 * De tool als HTML.
 *
 * @param  WP_Post|int $post     Tool.
 * @param  string      $weergave volledig of compact.
 * @return string
 */
function sfp_page_config_tool_html( $post, $weergave = 'volledig' ) {
    $post = get_post( $post );
    if ( ! $post || 'tool' !== $post->post_type || ! sfp_page_config_tool_aan() ) {
        return '';
    }
    $b = sfp_page_config_tool_beschrijving( $post );
    if ( ! $b || ! isset( sfp_page_config_tool_soorten()[ $b['soort'] ] ) ) {
        return '';
    }
    $controle = sfp_page_config_tool_controleer( $b );
    if ( $controle['fouten'] ) {
        if ( current_user_can( 'edit_post', $post->ID ) ) {
            return '<p class="sfp-tool__noscript">Tool: de beschrijving klopt niet. ' . esc_html( implode( ' ', $controle['fouten'] ) ) . '</p>';
        }
        return '';
    }
    sfp_page_config_tool_bestanden( $b['soort'] );
    $t        = 'sfp_page_config_artikel_tekst';
    $weergave = 'compact' === $weergave ? 'compact' : 'volledig';
    $id       = 'sfp-tool-' . (int) $post->ID;
    $titel    = wp_strip_all_tags( get_the_title( $post ) );

    $html  = '<div class="sfp-tool sfp-tool--' . esc_attr( $b['soort'] ) . ' sfp-tool--' . $weergave . '" id="' . esc_attr( $id ) . '" data-tool="' . (int) $post->ID . '" data-slug="' . esc_attr( $post->post_name ) . '" data-titel="' . esc_attr( $titel ) . '" data-weergave="' . $weergave . '">';
    $html .= '<script type="application/json" class="sfp-tool__data">' . wp_json_encode( sfp_page_config_tool_voor_browser( $b ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
    $html .= '<noscript><p class="sfp-tool__noscript">' . esc_html( $t( 'tool_geen_script' ) ) . '</p></noscript>';
    $html .= '<div class="sfp-tool__app" hidden></div>';
    if ( $b['acties']['formulier'] > 0 && shortcode_exists( 'sureforms' ) ) {
        $html .= '<div class="sfp-tool__formulier" hidden>' . do_shortcode( '[sureforms id="' . (int) $b['acties']['formulier'] . '"]' ) . '</div>';
    }
    $html .= '<details class="sfp-tool__hoe" id="' . esc_attr( $id ) . '-hoe"><summary>' . esc_html( $t( 'tool_hoe_rekent' ) ) . '</summary>' . sfp_page_config_tool_hoe_rekent( $b ) . '</details>';
    $html .= '</div>';
    return $html;
}

/**
 * De beschrijving zoals de browser hem krijgt: zonder wat alleen de server
 * gebruikt (mailteksten).
 *
 * @param  array $b Beschrijving.
 * @return array
 */
function sfp_page_config_tool_voor_browser( array $b ) {
    unset( $b['mail'] );
    return $b;
}

/* =========================================================================
 * Shortcode en blok
 * ====================================================================== */

add_action( 'init', 'sfp_page_config_tool_shortcode', 20 );

/**
 * [sfp_tool id="123" weergave="volledig|compact"]
 */
function sfp_page_config_tool_shortcode() {
    if ( ! shortcode_exists( 'sfp_tool' ) ) {
        add_shortcode( 'sfp_tool', 'sfp_page_config_tool_shortcode_render' );
    }
}

/**
 * @param  array|string $atts Attributen.
 * @return string
 */
function sfp_page_config_tool_shortcode_render( $atts ) {
    $atts = shortcode_atts( array( 'id' => 0, 'weergave' => 'volledig' ), (array) $atts, 'sfp_tool' );
    $id   = (int) $atts['id'];
    // Zonder id op de pagina van een tool: die tool zelf.
    if ( ! $id && sfp_page_config_is_tool( get_post() ) ) {
        $id = (int) get_post()->ID;
    }
    if ( ! $id ) {
        return '';
    }
    return sfp_page_config_tool_html( $id, (string) $atts['weergave'] );
}

add_filter( 'the_content', 'sfp_page_config_tool_in_inhoud', 8 );

/**
 * Zonder de artikelopmaak (onderdeel Artikelen uit) zet de plugin de tool
 * zelf boven de tekst van zijn pagina, tenzij de tekst hem al plaatst met
 * de code of het blok. Met de artikelopmaak aan doet includes/artikel.php
 * dat, als onderdeel van de vorm tool.
 *
 * @param  string $inhoud Inhoud.
 * @return string
 */
function sfp_page_config_tool_in_inhoud( $inhoud ) {
    if ( ! is_singular( 'tool' ) || ! in_the_loop() || ! is_main_query() ) {
        return $inhoud;
    }
    $post = get_post();
    if ( ! sfp_page_config_tool_aan() || ! sfp_page_config_is_tool( $post ) ) {
        return $inhoud;
    }
    if ( function_exists( 'sfp_page_config_is_tool_vorm' ) && sfp_page_config_is_tool_vorm( $post ) ) {
        return $inhoud;
    }
    if ( false !== strpos( $inhoud, '[sfp_tool' ) || false !== strpos( $post->post_content, '[sfp_tool' ) || has_block( 'sfp/tool', $post ) ) {
        return $inhoud;
    }
    $html = sfp_page_config_tool_html( $post, 'volledig' );
    return '' === $html ? $inhoud : $html . $inhoud;
}

add_filter( 'sfp_page_config_blokken_extra', 'sfp_page_config_tool_blokken' );

/**
 * Het blok Tool: een tool kiezen, hele tool of compact.
 *
 * @param  array $blokken Blokken.
 * @return array
 */
function sfp_page_config_tool_blokken( $blokken ) {
    $blokken['sfp/tool'] = array(
        'title'        => 'Tool',
        'description'  => 'Een tool van deze site op deze pagina: de hele tool of compact. Op de pagina van de tool zelf staat hij vanzelf.',
        'icon'         => 'hammer',
        'css'          => '',
        'zonder_basis' => true,
        'attributes'   => array(
            'tool'     => array(
                'type'    => 'integer',
                'default' => 0,
            ),
            'weergave' => array(
                'type'    => 'string',
                'default' => 'volledig',
            ),
        ),
        'render'       => 'sfp_page_config_render_tool_blok',
    );
    return $blokken;
}

/**
 * sfp/tool.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_tool_blok( $attrs ) {
    $id = isset( $attrs['tool'] ) ? (int) $attrs['tool'] : 0;
    if ( ! $id ) {
        return current_user_can( 'edit_posts' ) && ( is_admin() || wp_is_json_request() ) ? '<p class="sfp-tool__noscript">Tool: kies een tool in het blok.</p>' : '';
    }
    $post = get_post( $id );
    if ( ! $post || 'publish' !== $post->post_status ) {
        return current_user_can( 'edit_posts' ) && ( is_admin() || wp_is_json_request() ) ? '<p class="sfp-tool__noscript">Tool: deze tool is niet gepubliceerd.</p>' : '';
    }
    return sfp_page_config_tool_html( $post, isset( $attrs['weergave'] ) ? (string) $attrs['weergave'] : 'volledig' );
}

add_action( 'enqueue_block_editor_assets', 'sfp_page_config_tool_editor_lijst' );

/**
 * De lijst tools voor het blok in de editor.
 */
function sfp_page_config_tool_editor_lijst() {
    if ( ! post_type_exists( 'tool' ) ) {
        return;
    }
    $lijst = array();
    foreach ( sfp_page_config_tools() as $p ) {
        $b       = sfp_page_config_tool_beschrijving( $p );
        $lijst[] = array(
            'id'    => (int) $p->ID,
            'naam'  => wp_strip_all_tags( get_the_title( $p ) ),
            'soort' => $b ? $b['soort'] : '',
        );
    }
    wp_add_inline_script( 'sfp-blokken-editor', 'window.sfpTools=' . wp_json_encode( array( 'tools' => $lijst, 'aan' => sfp_page_config_tool_aan() ) ) . ';', 'before' );
}
