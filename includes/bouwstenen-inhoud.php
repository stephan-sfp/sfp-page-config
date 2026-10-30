<?php
/**
 * SFP Page Config - Inhoudsbouwstenen uit de oude snippets
 *
 * Wat tot en met 3.19.1 alleen in ASE-snippets stond en waar inhoud op
 * leunt, nu in de plugin (besluiten Stephan 26-10 en 29-10-2026, klus
 * shortcodes-naar-plugin, UX-check 30-10-2026):
 *
 *   kopieerblok    blok sfp/copy, dezelfde naam en opslag als het snippet
 *                  "Kopieerblok"; werkt bij de eerste klik, grote knop,
 *                  lange tekst ingeklapt met Toon alles, kopieert altijd
 *                  alles, kleuren op rol in plaats van eigen kleurkeuzes
 *   bronnenlijst   blok sfp/bronnenlijst en de code [bronnenlijst], met
 *                  zoekveld; logica van het snippet v1.11
 *   praktijkblok   blokstijl "Uit de trainingspraktijk" op Citaat, met de
 *                  opmaak van het DPS-snippet (variant A, op rol)
 *   auteursbio     opmaak in de biografie toegestaan, regels met √ als
 *                  vinkjeslijst
 *
 * Elk van deze onderdelen staat standaard uit en gaat per site aan na een
 * nameting. Staat een onderdeel uit, dan blijft het snippet het doen; is er
 * geen snippet meer, dan neemt de plugin het over, zodat bestaande inhoud
 * blijft werken.
 *
 * Daarnaast vangt de plugin oude codes op als het snippet er niet meer is:
 * [publicatiedatum], [bewerkingsdatum], [dynamische_cta],
 * [dynamische_pijlerlink], [dynamische_zelftest] en de koppelcode van de
 * tools ([sfp_tool_auto], [dps_tool_auto] enz.). Dezelfde uitvoer.
 *
 * Geen merkkleur en geen fontnaam: opmaak via de variabelen van de
 * bouwstenen (--sfp-b-*), die op de kleurrollen staan.
 *
 * @package SFP_Page_Config
 * @since   3.20.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De onderdelen van dit bestand.
 *
 * @return string[]
 */
function sfp_page_config_inhoud_onderdelen() {
    return array( 'kopieerblok', 'bronnenlijst', 'praktijkblok', 'auteursbio' );
}

/**
 * Staat een onderdeel van dit bestand aan?
 *
 * @param  string $id Onderdeel.
 * @return bool
 */
function sfp_page_config_inhoud_aan( $id ) {
    return function_exists( 'sfp_page_config_onderdeel_aan' ) && sfp_page_config_onderdeel_aan( $id );
}

/* =========================================================================
 * CSS en script op de voorkant
 * ====================================================================== */

/**
 * Houd bij wat al op de pagina staat en wat er nog nodig is.
 *
 * @param  string|null $wat Te markeren, of null om te lezen.
 * @return array<string, bool>
 */
function sfp_page_config_inhoud_geplaatst( $wat = null ) {
    static $geplaatst = array();
    if ( null !== $wat ) {
        $geplaatst[ $wat ] = true;
    }
    return $geplaatst;
}

/**
 * Een stijlelement met de CSS van een onderdeel, als die nog niet op de
 * pagina staat. Met de variabelen van de bouwstenen erbij als die nog
 * ontbreken.
 *
 * @param  string $naam Bestand onder assets/blokken/ zonder .css.
 * @return string
 */
function sfp_page_config_inhoud_css( $naam ) {
    if ( is_admin() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
        return '';
    }
    $geplaatst = sfp_page_config_inhoud_geplaatst();
    if ( ! empty( $geplaatst[ 'css-' . $naam ] ) ) {
        return '';
    }
    sfp_page_config_inhoud_geplaatst( 'css-' . $naam );
    $css = '';
    if ( function_exists( 'sfp_page_config_blokken_geplaatst' ) ) {
        $blokken = sfp_page_config_blokken_geplaatst();
        if ( empty( $blokken['variabelen'] ) ) {
            $css .= sfp_page_config_blokken_css_variabelen();
            sfp_page_config_blokken_geplaatst( 'variabelen' );
        }
    }
    $css .= sfp_page_config_blokken_css_bestand( $naam );
    return '<style id="sfp-' . esc_attr( $naam ) . '-inline-css">' . $css . '</style>';
}

add_action( 'wp_head', 'sfp_page_config_inhoud_head', 21 );

/**
 * CSS in de head voor wat in de inhoud van deze pagina staat, zodat er
 * niets verspringt. Wat in een Astra-layout staat, krijgt zijn CSS bij het
 * tonen (vangnet).
 */
function sfp_page_config_inhoud_head() {
    if ( is_admin() ) {
        return;
    }
    $uit = '';
    if ( sfp_page_config_inhoud_aan( 'auteursbio' ) && ( is_singular() || is_author() ) ) {
        $uit .= sfp_page_config_inhoud_css( 'auteursbio' );
    }
    if ( is_singular() ) {
        $post   = get_queried_object();
        $inhoud = $post instanceof WP_Post ? (string) $post->post_content : '';
        if ( false !== strpos( $inhoud, '<!-- wp:sfp/copy' ) && sfp_page_config_kopieer_van_plugin() ) {
            $uit .= sfp_page_config_inhoud_css( 'kopieer' );
        }
        if ( ( false !== strpos( $inhoud, '<!-- wp:sfp/bronnenlijst' ) || false !== strpos( $inhoud, '[bronnenlijst' ) ) && sfp_page_config_bronnen_van_plugin() ) {
            $uit .= sfp_page_config_inhoud_css( 'bronnen' );
            $uit .= sfp_page_config_bronnen_schema();
        }
        if ( false !== strpos( $inhoud, 'praktijkblok' ) && sfp_page_config_inhoud_aan( 'praktijkblok' ) ) {
            $uit .= sfp_page_config_inhoud_css( 'praktijkblok' );
        }
    }
    echo $uit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste CSS uit de plugin en JSON uit wp_json_encode.
}

add_action( 'wp_footer', 'sfp_page_config_inhoud_js', 30 );

/**
 * Het script voor kopiëren, Toon alles en zoeken in de bronnen. Alleen als
 * een van die blokken op de pagina staat. Niet uitgesteld door WP Rocket,
 * zodat de eerste klik meteen werkt.
 */
function sfp_page_config_inhoud_js() {
    $geplaatst = sfp_page_config_inhoud_geplaatst();
    if ( empty( $geplaatst['js-kopieer'] ) && empty( $geplaatst['js-bronnen'] ) ) {
        return;
    }
    $pad = SFP_PAGE_CONFIG_DIR . 'assets/blokken/inhoud.js';
    $js  = is_readable( $pad ) ? (string) file_get_contents( $pad ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokaal pluginbestand.
    if ( '' === $js ) {
        return;
    }
    $js = preg_replace( '#/\*.*?\*/#s', '', $js );
    echo '<script id="sfp-inhoud-js" nowprocket>' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste JS uit de plugin.
}

add_filter( 'rocket_delay_js_exclusions', 'sfp_page_config_inhoud_delay' );

/**
 * WP Rocket stelt het script niet uit.
 *
 * @param  array $uitsluitingen Uitsluitingen.
 * @return array
 */
function sfp_page_config_inhoud_delay( $uitsluitingen ) {
    $uitsluitingen   = (array) $uitsluitingen;
    $uitsluitingen[] = 'sfp-inhoud-js';
    return $uitsluitingen;
}

add_filter( 'rocket_rucss_inline_atts_exclusions', 'sfp_page_config_inhoud_rucss' );

/**
 * WP Rocket Remove Unused CSS laat de inline opmaak staan (ingeklapt, open,
 * melding en geen treffers verschijnen pas na een klik).
 *
 * @param  array $uitsluitingen Uitsluitingen.
 * @return array
 */
function sfp_page_config_inhoud_rucss( $uitsluitingen ) {
    $uitsluitingen = (array) $uitsluitingen;
    foreach ( array( 'kopieer', 'bronnen', 'praktijkblok', 'auteursbio' ) as $naam ) {
        $uitsluitingen[] = 'sfp-' . $naam . '-inline-css';
    }
    return $uitsluitingen;
}

/* =========================================================================
 * Kopieerblok (sfp/copy)
 * ====================================================================== */

/**
 * Is het blok van de plugin? Ja als het onderdeel aan staat, of als er
 * geen snippet meer is dat het blok registreert.
 *
 * @return bool
 */
function sfp_page_config_kopieer_van_plugin() {
    if ( sfp_page_config_inhoud_aan( 'kopieerblok' ) ) {
        return true;
    }
    $register = class_exists( 'WP_Block_Type_Registry' ) ? WP_Block_Type_Registry::get_instance()->get_registered( 'sfp/copy' ) : null;
    return $register && 'sfp_page_config_kopieer_render' === $register->render_callback;
}

/**
 * De attributen. De kleuren en het label staan in de opgeslagen HTML van het
 * snippet; de plugin leest ze daaruit terug zodat elk opgeslagen blok
 * geldig blijft, ook blokken die met oudere standaardkleuren zijn bewaard.
 * Ze sturen de opmaak niet meer: die volgt de rollen van de site.
 *
 * @return array
 */
function sfp_page_config_kopieer_attributen() {
    $attrs = array(
        'lang'      => array( 'type' => 'string', 'default' => '' ),
        'collapsed' => array( 'type' => 'boolean', 'default' => false ),
        'voorbeeld' => array( 'type' => 'boolean', 'default' => false ),
    );
    $kleuren = array(
        'headerBg'     => 'data-header-bg',
        'headerColor'  => 'data-header-color',
        'borderColor'  => 'data-border-color',
        'contentBg'    => 'data-content-bg',
        'contentColor' => 'data-content-color',
        'doneColor'    => 'data-done-color',
    );
    foreach ( $kleuren as $naam => $attribuut ) {
        $attrs[ $naam ] = array(
            'type'      => 'string',
            'source'    => 'attribute',
            'selector'  => 'div',
            'attribute' => $attribuut,
        );
    }
    return $attrs;
}

add_action( 'init', 'sfp_page_config_kopieer_registreer', 20 );

/**
 * Registreer het blok. Met het onderdeel aan neemt de plugin het blok over
 * van het snippet; staat het uit en is er geen snippet, dan registreert de
 * plugin het zodat bestaande blokken blijven werken.
 */
function sfp_page_config_kopieer_registreer() {
    if ( ! function_exists( 'register_block_type' ) || ! class_exists( 'WP_Block_Type_Registry' ) ) {
        return;
    }
    $register = WP_Block_Type_Registry::get_instance();
    $bestaat  = $register->is_registered( 'sfp/copy' );
    if ( $bestaat && ! sfp_page_config_inhoud_aan( 'kopieerblok' ) ) {
        return;
    }
    if ( $bestaat ) {
        unregister_block_type( 'sfp/copy' );
    }
    register_block_type(
        'sfp/copy',
        array(
            'api_version'     => 3,
            'title'           => 'Kopieerblok',
            'description'     => 'Tekst die de lezer met één klik kopieert, zoals een prompt. Lange tekst staat ingeklapt.',
            'category'        => 'sfp-bouwstenen',
            'icon'            => 'clipboard',
            'attributes'      => sfp_page_config_kopieer_attributen(),
            'supports'        => array(
                'html'            => false,
                'className'       => true,
                'customClassName' => true,
            ),
            'editor_script'   => 'sfp-blokken-editor',
            'render_callback' => 'sfp_page_config_kopieer_render',
            'example'         => array(
                'attributes'  => array( 'lang' => 'Kopieer deze prompt', 'voorbeeld' => true ),
                'innerBlocks' => array(
                    array( 'name' => 'core/paragraph', 'attributes' => array( 'content' => 'Jij bent een ervaren presentatiecoach. Geef drie concrete tips voor een sterke opening.' ), 'innerBlocks' => array() ),
                ),
            ),
        )
    );
}

add_action( 'enqueue_block_editor_assets', 'sfp_page_config_kopieer_editor', 99 );

/**
 * Het editorscript van het snippet niet laden als de plugin het blok heeft:
 * anders registreert het een tweede versie met kleurkeuzes.
 */
function sfp_page_config_kopieer_editor() {
    $van_plugin = sfp_page_config_kopieer_van_plugin();
    if ( wp_script_is( 'sfp-blokken-editor', 'registered' ) ) {
        wp_add_inline_script(
            'sfp-blokken-editor',
            'window.sfpInhoud=' . wp_json_encode(
                array(
                    'kopieer' => (bool) $van_plugin,
                    'knop'    => sfp_page_config_tekst( 'kopieer_knop' ),
                )
            ) . ';',
            'before'
        );
    }
    if ( ! $van_plugin ) {
        return;
    }
    if ( wp_script_is( 'sfp-copy-block', 'registered' ) ) {
        wp_dequeue_script( 'sfp-copy-block' );
        wp_deregister_script( 'sfp-copy-block' );
    }
}

/**
 * De tekst van het blok zonder de omhulling die het snippet opsloeg.
 *
 * @param  string $content Opgeslagen binnenkant.
 * @return string
 */
function sfp_page_config_kopieer_tekst( $content ) {
    $content = (string) $content;
    $content = preg_replace( '#<span class="sfp-copy__lang-saved"[^>]*>.*?</span>#s', '', $content );
    $content = trim( $content );
    if ( preg_match( '#^<div\b[^>]*\bwp-block-sfp-copy\b[^>]*>(.*)</div>$#s', $content, $m ) ) {
        $content = trim( $m[1] );
    }
    return $content;
}

/**
 * Is de tekst lang genoeg om in te klappen? Meer dan ongeveer acht regels.
 *
 * @param  string $html Tekst.
 * @return bool
 */
function sfp_page_config_kopieer_lang( $html ) {
    $regels = 0;
    $delen  = preg_split( '#</p>|<br\s*/?>|</li>|</h[1-6]>#i', (string) $html );
    foreach ( $delen as $deel ) {
        $tekst = trim( html_entity_decode( wp_strip_all_tags( $deel ), ENT_QUOTES, 'UTF-8' ) );
        if ( '' === $tekst ) {
            continue;
        }
        $regels += max( 1, (int) ceil( mb_strlen( $tekst ) / 75 ) );
    }
    return $regels > 9;
}

/**
 * sfp/copy op de site.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Opgeslagen binnenkant.
 * @return string
 */
function sfp_page_config_kopieer_render( $attrs, $content ) {
    if ( ! empty( $attrs['voorbeeld'] ) && ! is_admin() && ! ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
        return '';
    }
    $tekst = sfp_page_config_kopieer_tekst( $content );
    if ( '' === trim( wp_strip_all_tags( $tekst ) ) ) {
        return '';
    }
    static $nummer = 0;
    ++$nummer;
    $id    = 'sfp-kopieer-' . $nummer;
    $label = isset( $attrs['lang'] ) ? trim( wp_strip_all_tags( (string) $attrs['lang'] ) ) : '';
    $lang  = sfp_page_config_kopieer_lang( $tekst );
    sfp_page_config_inhoud_geplaatst( 'js-kopieer' );

    $knop = '<svg class="sfp-kopieer__icoon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><rect x="9" y="9" width="12" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
        . '<svg class="sfp-kopieer__vink" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    $html  = sfp_page_config_inhoud_css( 'kopieer' );
    $html .= '<div ' . get_block_wrapper_attributes( array( 'class' => 'sfp-kopieer' . ( $lang ? ' is-lang' : '' ) ) ) . ' data-sfp-kopieer'
        . ' data-klaar="' . esc_attr( sfp_page_config_tekst( 'kopieer_klaar' ) ) . '"'
        . ' data-mislukt="' . esc_attr( sfp_page_config_tekst( 'kopieer_mislukt' ) ) . '"'
        . ' data-meer="' . esc_attr( sfp_page_config_tekst( 'kopieer_meer' ) ) . '"'
        . ' data-minder="' . esc_attr( sfp_page_config_tekst( 'kopieer_minder' ) ) . '">';
    $html .= '<div class="sfp-kopieer__kop">'
        . ( '' !== $label ? '<p class="sfp-kopieer__label">' . esc_html( $label ) . '</p>' : '' )
        . '<button type="button" class="sfp-kopieer__knop" aria-controls="' . esc_attr( $id ) . '">' . $knop . '<span class="sfp-kopieer__knoptekst">' . esc_html( sfp_page_config_tekst( 'kopieer_knop' ) ) . '</span></button>'
        . '</div>';
    $html .= '<div class="sfp-kopieer__tekst" id="' . esc_attr( $id ) . '">' . $tekst . '</div>';
    if ( $lang ) {
        $html .= '<button type="button" class="sfp-kopieer__meer" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '">' . esc_html( sfp_page_config_tekst( 'kopieer_meer' ) ) . '</button>';
    }
    $html .= '<p class="sfp-kopieer__melding" role="status" aria-live="polite"></p>';
    $html .= '</div>';
    return $html;
}

/* =========================================================================
 * Bronnenlijst (sfp/bronnenlijst en [bronnenlijst])
 * ====================================================================== */

/**
 * Is de bronnenlijst van de plugin? Ja als het onderdeel aan staat, of als
 * er geen snippet meer is dat [bronnenlijst] levert.
 *
 * @return bool
 */
function sfp_page_config_bronnen_van_plugin() {
    global $shortcode_tags;
    if ( sfp_page_config_inhoud_aan( 'bronnenlijst' ) ) {
        return true;
    }
    return isset( $shortcode_tags['bronnenlijst'] ) && 'sfp_page_config_bronnen_code' === $shortcode_tags['bronnenlijst'];
}

add_action( 'init', 'sfp_page_config_bronnen_registreer', 20 );

/**
 * Neem [bronnenlijst] over van het snippet als het onderdeel aan staat, of
 * vang hem op als het snippet er niet meer is. Met het onderdeel aan gaan
 * ook de stijl en de gegevens voor Google van het snippet van de pagina:
 * anders staan ze er twee keer.
 */
function sfp_page_config_bronnen_registreer() {
    $aan = sfp_page_config_inhoud_aan( 'bronnenlijst' );
    if ( shortcode_exists( 'bronnenlijst' ) && ! $aan ) {
        return;
    }
    remove_shortcode( 'bronnenlijst' );
    add_shortcode( 'bronnenlijst', 'sfp_page_config_bronnen_code' );
    if ( $aan ) {
        sfp_page_config_bronnen_snippet_uit();
        // Nog een keer vlak voor de head geprint wordt: op DPS (beta.1, 30-10)
        // stonden de hooks van het snippet er na init nog.
        add_action( 'wp_head', 'sfp_page_config_bronnen_snippet_uit', 0 );
    }
}

/**
 * Haal de head-hooks van het snippet weg (stijl en CollectionPage). Het
 * functieprefix verschilt per site (sfp_, dps_ enz.); de naam eindigt
 * altijd op bronnenlijst_css of bibliografie_schema.
 */
function sfp_page_config_bronnen_snippet_uit() {
    global $wp_filter;
    $moment = doing_action( 'wp_head' ) ? 'head' : 'init';
    if ( empty( $wp_filter['wp_head'] ) || ! is_object( $wp_filter['wp_head'] ) ) {
        $GLOBALS['sfp_pc_bronnen_diag'][ $moment ] = 'geen wp_head';
        return;
    }
    $weg = array();
    foreach ( $wp_filter['wp_head']->callbacks as $prioriteit => $functies ) {
        foreach ( $functies as $functie ) {
            $naam = is_string( $functie['function'] ) ? $functie['function'] : '';
            if ( '' !== $naam && 0 !== strpos( $naam, 'sfp_page_config_' ) && preg_match( '/(bronnenlijst_css|bibliografie_schema)$/', $naam ) ) {
                $weg[] = $naam . '@' . $prioriteit . ( remove_action( 'wp_head', $naam, $prioriteit ) ? '' : '(mislukt)' );
            }
        }
    }
    // Tijdelijk (3.20.0-beta.2): wat er op welk moment weg ging.
    $GLOBALS['sfp_pc_bronnen_diag'][ $moment ] = $weg ? implode( ',', $weg ) : 'niets';
    if ( 'head' === $moment && is_singular() ) {
        echo '<!-- sfp-bronnen diag: init=' . esc_html( isset( $GLOBALS['sfp_pc_bronnen_diag']['init'] ) ? $GLOBALS['sfp_pc_bronnen_diag']['init'] : '-' ) . ' head=' . esc_html( $GLOBALS['sfp_pc_bronnen_diag']['head'] ) . ' -->' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped.
    }
}

add_filter( 'sfp_page_config_blokken_extra', 'sfp_page_config_bronnen_blok' );

/**
 * Het blok Bronnenlijst. In de blokkenlijst alleen als het onderdeel aan
 * staat; een geplaatst blok werkt altijd.
 *
 * @param  array $blokken Blokken.
 * @return array
 */
function sfp_page_config_bronnen_blok( $blokken ) {
    $blokken['sfp/bronnenlijst'] = array(
        'title'        => 'Bronnenlijst',
        'description'  => 'Alle bronnen uit de tooltips van de site, op alfabet, met een zoekveld en de artikelen waarin ze staan. Voor de bibliografiepagina.',
        'icon'         => 'book-alt',
        'css'          => 'bronnen',
        'zonder_basis' => true,
        'inserter'     => sfp_page_config_inhoud_aan( 'bronnenlijst' ),
        'attributes'   => array(
            'voorbeeld' => array( 'type' => 'boolean', 'default' => false ),
        ),
        'render'       => 'sfp_page_config_bronnen_render',
    );
    return $blokken;
}

/**
 * [bronnenlijst].
 *
 * @return string
 */
function sfp_page_config_bronnen_code() {
    return sfp_page_config_bronnen_render( array() );
}

add_action( 'save_post', 'sfp_page_config_bronnen_wis', 10, 2 );

/**
 * Na het opslaan van een gepubliceerd bericht opnieuw verzamelen.
 *
 * @param int     $post_id Bericht.
 * @param WP_Post $post    Bericht.
 */
function sfp_page_config_bronnen_wis( $post_id, $post = null ) {
    if ( $post instanceof WP_Post && 'publish' !== $post->post_status ) {
        return;
    }
    delete_transient( 'sfp_pc_bronnen' );
    unset( $GLOBALS['sfp_pc_bronnen'] );
}

/**
 * Verwijder paginanummers en raadpleegdatums aan het eind van een bron.
 *
 * @param  string $bron Bron.
 * @return string
 */
function sfp_page_config_bronnen_kaal( $bron ) {
    $bron = preg_replace( '/[,;.]?\s*(?:\(\s*)?(?:pp?|blz)\.?\s*[\d][\d\s,\-\x{2013}]*(?:\s*\))?[,;.]?\s*$/ui', '', (string) $bron );
    $bron = preg_replace( '/[,;.]?\s*\.?\s*[Gg]eraadpleegd\s+op\s+.+?\.?\s*$/u', '', $bron );
    $bron = preg_replace( '/[,;.]?\s*\.?\s*[Rr]etrieved\s+(?:on\s+)?.+?\.?\s*$/iu', '', $bron );
    return $bron;
}

/**
 * De bronnen uit één tooltip, zoals het snippet v1.11 ze leest.
 *
 * @param  string $tooltip Tekst van data-tooltip, al gedecodeerd.
 * @return array|null      bron, bron_display, samenvatting, sleutel; of null.
 */
function sfp_page_config_bronnen_uit_tooltip( $tooltip ) {
    if ( ! preg_match( '/(?:Bron|Source|Fonte):\s*(.+?)(?:<br[\s\/]*>|\n|$)/iu', $tooltip, $treffer ) ) {
        return null;
    }
    $ruw       = trim( $treffer[1] );
    $brontekst = trim( preg_replace( '/\*+/', '', trim( wp_strip_all_tags( $ruw ) ) ) );
    if ( '' === $brontekst || strlen( $brontekst ) < 5 ) {
        return null;
    }

    $ruw = sfp_page_config_bronnen_kaal( $ruw );
    $ruw = preg_match( '/https?:\/\/\S+$/u', $ruw ) ? rtrim( trim( $ruw ), '.,;' ) : rtrim( trim( $ruw ), '.,;' ) . '.';
    $toon = trim(
        wp_kses(
            $ruw,
            array(
                'a'      => array( 'href' => true, 'target' => true, 'rel' => true ),
                'em'     => array(),
                'strong' => array(),
                'i'      => array(),
                'b'      => array(),
            )
        )
    );
    $toon = preg_replace( '/\*\*([^*]+?)\*\*/', '<strong>$1</strong>', $toon );
    $toon = preg_replace( '/\*([^*]+?)\*/', '<em>$1</em>', $toon );
    if ( false === strpos( $toon, '<a ' ) ) {
        $toon = preg_replace_callback(
            '/https?:\/\/[^\s<]+[^\s<.,;)]/u',
            function ( $m ) {
                return '<a href="' . esc_url( $m[0] ) . '">' . esc_html( $m[0] ) . '</a>';
            },
            $toon
        );
    }
    if ( false !== strpos( $toon, '<a ' ) ) {
        $toon = preg_replace_callback(
            '/<a\b([^>]*)>/i',
            function ( $m ) {
                $a = $m[1];
                if ( false === stripos( $a, 'target=' ) ) {
                    $a .= ' target="_blank"';
                }
                if ( false === stripos( $a, 'rel=' ) ) {
                    $a .= ' rel="noopener noreferrer"';
                }
                return '<a' . $a . '>';
            },
            $toon
        );
    }

    $basis = rtrim( trim( sfp_page_config_bronnen_kaal( $brontekst ) ), '.,;' );
    if ( '' === $basis || strlen( $basis ) < 5 ) {
        $basis = $brontekst;
    }
    $sleutel = strtolower( preg_replace( '/\s+/', ' ', $basis ) );
    if ( function_exists( 'iconv' ) ) {
        $ascii   = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $sleutel ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- iconv meldt onvertaalbare tekens.
        $sleutel = false === $ascii ? $sleutel : $ascii;
    }

    $samenvatting = '';
    if ( preg_match( '/^(.+?)(?:<br[\s\/]*>|\n)?\s*(?:Bron|Source|Fonte):/isu', $tooltip, $s ) ) {
        $samenvatting = trim( wp_strip_all_tags( $s[1] ) );
    }
    return array(
        'bron'         => $basis,
        'bron_display' => $toon,
        'samenvatting' => $samenvatting,
        'sleutel'      => $sleutel,
    );
}

/**
 * Alle bronnen van de site, gegroepeerd per beginletter.
 *
 * @param  array|null $berichten Voor de tests: lijst van array( url, titel, inhoud ).
 * @return array                 array( bronnen, groepen, totaal ).
 */
function sfp_page_config_bronnen_verzamel( $berichten = null ) {
    if ( null === $berichten && isset( $GLOBALS['sfp_pc_bronnen'] ) ) {
        return $GLOBALS['sfp_pc_bronnen'];
    }
    if ( null === $berichten ) {
        $bewaard = get_transient( 'sfp_pc_bronnen' );
        if ( is_array( $bewaard ) && isset( $bewaard[2] ) ) {
            $GLOBALS['sfp_pc_bronnen'] = $bewaard;
            return $bewaard;
        }
        $berichten = array();
        $ids       = get_posts(
            array(
                'post_type'        => array( 'post', 'page', 'begrippen' ),
                'post_status'      => 'publish',
                'posts_per_page'   => -1,
                'fields'           => 'ids',
                'suppress_filters' => true,
            )
        );
        foreach ( $ids as $id ) {
            $inhoud = (string) get_post_field( 'post_content', $id );
            if ( false === strpos( $inhoud, 'data-tooltip=' ) ) {
                continue;
            }
            $berichten[] = array( get_permalink( $id ), get_the_title( $id ), $inhoud );
        }
        $opslaan = true;
    }

    $bronnen = array();
    foreach ( $berichten as $bericht ) {
        list( $url, $titel, $inhoud ) = $bericht;
        if ( ! preg_match_all( '/data-tooltip=([\x27\x22])(.*?)\1/s', (string) $inhoud, $treffers ) ) {
            continue;
        }
        foreach ( $treffers[2] as $tooltip ) {
            $bron = sfp_page_config_bronnen_uit_tooltip( html_entity_decode( $tooltip, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
            if ( ! $bron ) {
                continue;
            }
            $sleutel = $bron['sleutel'];
            if ( ! isset( $bronnen[ $sleutel ] ) ) {
                $bronnen[ $sleutel ] = array(
                    'bron'         => $bron['bron'],
                    'bron_display' => $bron['bron_display'],
                    'samenvatting' => $bron['samenvatting'],
                    'posts'        => array(),
                );
            } elseif ( false === strpos( $bronnen[ $sleutel ]['bron_display'], '<a ' ) && false !== strpos( $bron['bron_display'], '<a ' ) ) {
                $bronnen[ $sleutel ]['bron_display'] = $bron['bron_display'];
            }
            $al = false;
            foreach ( $bronnen[ $sleutel ]['posts'] as $p ) {
                if ( $p['url'] === $url ) {
                    $al = true;
                    break;
                }
            }
            if ( ! $al ) {
                $bronnen[ $sleutel ]['posts'][] = array( 'url' => $url, 'title' => $titel );
            }
        }
    }
    ksort( $bronnen );

    $groepen = array();
    $overig  = array();
    foreach ( $bronnen as $data ) {
        $letter = strtoupper( mb_substr( $data['bron'], 0, 1 ) );
        if ( ctype_alpha( $letter ) ) {
            $groepen[ $letter ][] = $data;
        } else {
            $overig[] = $data;
        }
    }
    ksort( $groepen );
    if ( $overig ) {
        $groepen['#'] = $overig;
    }
    $uit = array( $bronnen, $groepen, count( $bronnen ) );
    if ( ! empty( $opslaan ) ) {
        set_transient( 'sfp_pc_bronnen', $uit, DAY_IN_SECONDS );
        $GLOBALS['sfp_pc_bronnen'] = $uit;
    }
    return $uit;
}

/**
 * De lijst als HTML.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_bronnen_render( $attrs ) {
    $voorbeeld = ! empty( $attrs['voorbeeld'] );
    if ( $voorbeeld && ! is_admin() && ! ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
        return '';
    }
    if ( $voorbeeld ) {
        list( , $groepen, $totaal ) = sfp_page_config_bronnen_verzamel(
            array(
                array( '#', 'Spreekangst overwinnen', '<span data-tooltip="Angst neemt af door herhaling.<br>Bron: Barlow, D. H. (2002). Anxiety and its disorders. Guilford Press.">x</span>' ),
                array( '#', 'Presenteren met impact', '<span data-tooltip="Bron: Cialdini, R. B. (2006). Influence. Harper Business.">x</span>' ),
            )
        );
    } else {
        list( , $groepen, $totaal ) = sfp_page_config_bronnen_verzamel();
    }
    if ( ! $totaal ) {
        return '';
    }
    sfp_page_config_inhoud_geplaatst( 'js-bronnen' );
    $site  = get_bloginfo( 'name' );
    $intro = sfp_page_config_tekst( 1 === (int) $totaal ? 'bronnen_intro_een' : 'bronnen_intro_meer' );
    $intro = wp_kses( sprintf( $intro, esc_html( $site ), (int) $totaal ), array( 'strong' => array(), 'em' => array() ) );
    $zoek  = 'sfp-bronnen-zoek';

    $html  = sfp_page_config_inhoud_css( 'bronnen' );
    $html .= '<div ' . get_block_wrapper_attributes( array( 'class' => 'sfp-bronnen' ) ) . ' data-sfp-bronnen>';
    $html .= '<p class="sfp-bib-intro">' . $intro . '</p>';
    $html .= '<div class="sfp-bronnen__zoek"><label for="' . esc_attr( $zoek ) . '">' . esc_html( sfp_page_config_tekst( 'bronnen_zoek' ) ) . '</label>'
        . '<input type="search" id="' . esc_attr( $zoek ) . '" autocomplete="off" spellcheck="false"></div>';
    $html .= '<nav class="sfp-bib-nav" aria-label="' . esc_attr( sfp_page_config_tekst( 'bronnen_nav' ) ) . '">';
    foreach ( array_keys( $groepen ) as $letter ) {
        $anker = '#' === $letter ? 'bib-hash' : 'bib-' . strtolower( $letter );
        $html .= '<a href="#' . esc_attr( $anker ) . '">' . esc_html( $letter ) . '</a>';
    }
    $html .= '</nav>';
    $html .= '<p class="sfp-bronnen__geen" role="status" hidden>' . esc_html( sfp_page_config_tekst( 'bronnen_geen' ) ) . '</p>';
    $geciteerd = sfp_page_config_tekst( 'bronnen_geciteerd' );
    foreach ( $groepen as $letter => $items ) {
        $anker = '#' === $letter ? 'bib-hash' : 'bib-' . strtolower( $letter );
        $html .= '<section class="sfp-bronnen__groep" data-letter="' . esc_attr( $anker ) . '">';
        $html .= '<h2 id="' . esc_attr( $anker ) . '">' . esc_html( $letter ) . '</h2>';
        foreach ( $items as $item ) {
            $html .= '<div class="sfp-bron-item">';
            $html .= '<div class="sfp-bron-citaat">' . $item['bron_display'] . '</div>';
            if ( '' !== $item['samenvatting'] ) {
                $html .= '<div class="sfp-bron-samenvatting">' . esc_html( $item['samenvatting'] ) . '</div>';
            }
            if ( $item['posts'] ) {
                $links = '';
                foreach ( $item['posts'] as $p ) {
                    $links .= '<a href="' . esc_url( $p['url'] ) . '">' . esc_html( $p['title'] ) . '</a>';
                }
                $html .= '<div class="sfp-bron-geciteerd"><span>' . esc_html( $geciteerd ) . '</span>' . $links . '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Het type van een bron voor Google, zoals het snippet v1.11.
 *
 * @param  string $bron Bron.
 * @return string
 */
function sfp_page_config_bronnen_type( $bron ) {
    if ( preg_match( '/\bvol\.|\bnr\.|\bdoi:|doi\.org\/|\b\d+\s*\(\d+(?:[\-\x{2013}]\d+)?\)\s*,/iu', $bron ) ) {
        return 'ScholarlyArticle';
    }
    if ( preg_match( '/geraadpleegd\s+op|retrieved|accessed|acesso\s+em/i', $bron ) ) {
        return 'WebPage';
    }
    return 'Book';
}

/**
 * CollectionPage voor de pagina met de bronnenlijst.
 *
 * @return string
 */
function sfp_page_config_bronnen_schema() {
    $geplaatst = sfp_page_config_inhoud_geplaatst();
    if ( ! empty( $geplaatst['schema-bronnen'] ) ) {
        return '';
    }
    sfp_page_config_inhoud_geplaatst( 'schema-bronnen' );
    list( $bronnen, , $totaal ) = sfp_page_config_bronnen_verzamel();
    if ( ! $totaal ) {
        return '';
    }
    $items = array();
    $nr    = 1;
    foreach ( $bronnen as $data ) {
        $citaat = array(
            '@type' => sfp_page_config_bronnen_type( $data['bron'] ),
            'name'  => $data['bron'],
        );
        if ( preg_match( '/href=[\'"]([^\'"]+)[\'"]/i', $data['bron_display'], $u ) ) {
            $citaat['url'] = $u[1];
        }
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $nr++,
            'item'     => $citaat,
        );
    }
    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'CollectionPage',
        'name'       => sfp_page_config_tekst( 'bronnen_schema' ) . ' ' . get_bloginfo( 'name' ),
        'url'        => get_permalink( get_queried_object_id() ),
        'mainEntity' => array(
            '@type'           => 'ItemList',
            'numberOfItems'   => $totaal,
            'itemListElement' => $items,
        ),
    );
    return '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
}

/* =========================================================================
 * Praktijkblok
 * ====================================================================== */

add_action( 'init', 'sfp_page_config_praktijkblok_registreer', 20 );

/**
 * Blokstijl op Citaat en een patroon met kop, tekst en ondertekening.
 */
function sfp_page_config_praktijkblok_registreer() {
    if ( ! sfp_page_config_inhoud_aan( 'praktijkblok' ) ) {
        return;
    }
    if ( function_exists( 'register_block_style' ) ) {
        register_block_style( 'core/quote', array( 'name' => 'praktijkblok', 'label' => 'Uit de trainingspraktijk' ) );
    }
    if ( function_exists( 'register_block_pattern' ) ) {
        if ( function_exists( 'register_block_pattern_category' ) ) {
            register_block_pattern_category( 'sfp', array( 'label' => 'SFP bouwstenen' ) );
        }
        $kop = esc_html( sfp_page_config_tekst( 'praktijkblok_kop' ) );
        register_block_pattern(
            'sfp/praktijkblok',
            array(
                'title'       => 'Uit de trainingspraktijk',
                'description' => 'Een ervaring van de trainer in 60 tot 100 woorden, in de eerste persoon, met naam en merk eronder.',
                'categories'  => array( 'sfp' ),
                'blockTypes'  => array( 'core/quote' ),
                'content'     => '<!-- wp:quote {"className":"praktijkblok"} --><blockquote class="wp-block-quote praktijkblok">'
                    . '<!-- wp:paragraph {"className":"praktijkblok-kop"} --><p class="praktijkblok-kop">' . $kop . '</p><!-- /wp:paragraph -->'
                    . '<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->'
                    . '<cite>' . esc_html( get_bloginfo( 'name' ) ) . '</cite></blockquote><!-- /wp:quote -->',
            )
        );
    }
}

/* =========================================================================
 * Auteursbio
 * ====================================================================== */

add_action( 'init', 'sfp_page_config_auteursbio_registreer', 20 );

/**
 * Opmaak en links in de biografie bewaren en als alinea's tonen.
 */
function sfp_page_config_auteursbio_registreer() {
    if ( ! sfp_page_config_inhoud_aan( 'auteursbio' ) ) {
        return;
    }
    remove_filter( 'pre_user_description', 'wp_filter_kses' );
    add_filter( 'pre_user_description', 'wp_filter_post_kses' );
    add_filter( 'get_the_author_description', 'wpautop' );
    add_filter( 'render_block_core/post-author-biography', 'sfp_page_config_auteursbio_vinkjes', 19 );
}

/**
 * Regels die met √ beginnen worden een lijst met vinkjes. Voor het snippet
 * (prioriteit 20), dat daarna niets meer te doen heeft.
 *
 * @param  string $html Uitvoer van het blok.
 * @return string
 */
function sfp_page_config_auteursbio_vinkjes( $html ) {
    if ( false === strpos( (string) $html, '√' ) ) {
        return $html;
    }
    $lijst = static function ( $regels ) {
        $items = '';
        foreach ( $regels as $regel ) {
            $items .= '<li>' . trim( preg_replace( '/^\s*√\s*/u', '', $regel ) ) . '</li>';
        }
        return '<ul class="sfp-auteur-usp">' . $items . '</ul>';
    };
    $nieuw = preg_replace_callback(
        '#<p>(.*?)</p>#su',
        static function ( $m ) use ( $lijst ) {
            $regels = array_values( array_filter( array_map( 'trim', preg_split( '#<br\s*/?>#i', $m[1] ) ), 'strlen' ) );
            if ( ! $regels ) {
                return $m[0];
            }
            foreach ( $regels as $regel ) {
                if ( 0 !== strpos( $regel, '√' ) ) {
                    return $m[0];
                }
            }
            return $lijst( $regels );
        },
        $html
    );
    if ( $nieuw === $html && false === strpos( $html, '<p>' ) ) {
        $nieuw = preg_replace_callback(
            '#((?:^[ \t]*√[^\r\n]*(?:\r?\n|$))+)#mu',
            static function ( $m ) use ( $lijst ) {
                return $lijst( preg_split( '/\r?\n/', trim( $m[1] ) ) );
            },
            $html
        );
    }
    if ( $nieuw !== $html ) {
        $nieuw = sfp_page_config_inhoud_css( 'auteursbio' ) . $nieuw;
    }
    return $nieuw;
}

/* =========================================================================
 * Oude codes, als het snippet er niet meer is
 * ====================================================================== */

add_action( 'init', 'sfp_page_config_oude_codes', 20 );

/**
 * Registreer de oude codes alleen als geen snippet ze nog levert. Dezelfde
 * uitvoer als de snippets.
 */
function sfp_page_config_oude_codes() {
    $codes = array(
        'publicatiedatum'       => 'sfp_page_config_code_publicatiedatum',
        'bewerkingsdatum'       => 'sfp_page_config_code_bewerkingsdatum',
        'dynamische_cta'        => 'sfp_page_config_code_dynamisch',
        'dynamische_pijlerlink' => 'sfp_page_config_code_dynamisch',
        'dynamische_zelftest'   => 'sfp_page_config_code_dynamisch',
    );
    foreach ( array( 'sfp', 'dps', 'dga', 'cvd', 'dst', 'fl' ) as $site ) {
        $codes[ $site . '_tool_auto' ] = 'sfp_page_config_code_tool_auto';
    }
    foreach ( $codes as $code => $functie ) {
        if ( ! shortcode_exists( $code ) ) {
            add_shortcode( $code, $functie );
        }
    }
}

/**
 * [publicatiedatum].
 *
 * @return string
 */
function sfp_page_config_code_publicatiedatum() {
    $tijd = get_post_timestamp( get_the_ID(), 'date' );
    return $tijd ? '<span class="datum-tekst">' . esc_html( wp_date( 'j F Y', $tijd ) ) . '</span>' : '';
}

/**
 * [bewerkingsdatum].
 *
 * @return string
 */
function sfp_page_config_code_bewerkingsdatum() {
    $tijd = get_post_timestamp( get_the_ID(), 'modified' );
    return $tijd ? '<span class="datum-tekst">' . esc_html( wp_date( 'j F Y', $tijd ) ) . '</span>' : '';
}

/**
 * [dynamische_cta], [dynamische_pijlerlink], [dynamische_zelftest]: de
 * actie met dezelfde naam, waar de Astra-layouts aan hangen.
 *
 * @param  array|string $atts    Attributen.
 * @param  string|null  $content Inhoud.
 * @param  string       $tag     Code.
 * @return string
 */
function sfp_page_config_code_dynamisch( $atts, $content = null, $tag = '' ) {
    if ( ! in_array( $tag, array( 'dynamische_cta', 'dynamische_pijlerlink', 'dynamische_zelftest' ), true ) ) {
        return '';
    }
    ob_start();
    do_action( $tag );
    return (string) ob_get_clean();
}

/**
 * [xx_tool_auto] in de Tool-layout. Met een beschrijving toont de toolmotor
 * de tool (sfp_page_config_tool_oude_koppeling); zonder beschrijving de
 * oude code van de tool als die nog bestaat, anders niets.
 *
 * @param  array|string $atts    Attributen.
 * @param  string|null  $content Inhoud.
 * @param  string       $tag     Code.
 * @return string
 */
function sfp_page_config_code_tool_auto( $atts, $content = null, $tag = '' ) {
    if ( ! is_singular( 'tool' ) ) {
        return '';
    }
    $slug  = (string) get_post_field( 'post_name', get_the_ID() );
    $site  = preg_replace( '/_tool_auto$/', '', (string) $tag );
    $codes = array( $site . '_tool_' . str_replace( '-', '_', $slug ), $site . '_tool_' . $slug );
    foreach ( $codes as $code ) {
        if ( '' !== $slug && shortcode_exists( $code ) ) {
            return do_shortcode( '[' . $code . ']' );
        }
    }
    return '';
}
