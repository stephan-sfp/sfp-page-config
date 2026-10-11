<?php
/**
 * SFP Page Config - Onderdeel Reviews
 *
 * De functie van de losse plugin SFP Google Reviews (2.1.9), als onderdeel
 * van deze plugin (besluit Stephan, 10 oktober 2026). Alles wat bestaande
 * inhoud en andere code gebruikt, is gelijk gebleven:
 *
 *   - de code [google_reviews] met al haar opties;
 *   - de opties `sfp_reviews_options` en `sfp_reviews_manual`;
 *   - de transient `sfp_reviews_<md5 van de place-id>`;
 *   - de classes SFP_Reviews_Fetcher, SFP_Reviews_Renderer en
 *     SFP_Reviews_Manual (het snippet "LatePoint: boekkaart in merkrollen"
 *     leest het Google-cijfer via SFP_Reviews_Fetcher).
 *
 * Overgang zonder gat of dubbeling: zolang de oude plugin op een site actief
 * is, laadt dit onderdeel zijn classes en zijn code niet. Staat de oude
 * plugin uit, dan neemt dit onderdeel het bij het eerstvolgende verzoek over.
 *
 * Wat anders is dan in de oude plugin:
 *   - de netwerkopmaak zit in de plugin; het vak Custom CSS (optie
 *     `sfp_reviews_custom_css`) wordt niet meer gelezen. Op de zes sites met
 *     de plugin stond daar hetzelfde blok (gemeten 11 oktober 2026);
 *   - vaste teksten volgen de taal van de site;
 *   - een eigen review heeft een datum en is te bewerken;
 *   - een leeg antwoord van Google wordt niet een dag bewaard.
 *
 * Terugdraaien op een site: de oude plugin weer aanzetten, of het onderdeel
 * Reviews uitzetten in SFP Beheer.
 *
 * @package SFP_Page_Config
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Draait de oude plugin SFP Google Reviews nog op deze site?
 *
 * @return bool
 */
function sfp_page_config_reviews_oude_plugin() {
    return defined( 'SFP_REVIEWS_VERSION' );
}

/**
 * Verzorgt dit onderdeel de reviews op deze site?
 *
 * Pas betrouwbaar vanaf `plugins_loaded`.
 *
 * @return bool
 */
function sfp_page_config_reviews_actief() {
    return ! sfp_page_config_reviews_oude_plugin() && sfp_page_config_onderdeel_aan( 'reviews' );
}

// Na alle plugins: dan is bekend of de oude plugin er is. De classes hebben
// dezelfde naam als in de oude plugin en mogen dus nooit twee keer bestaan.
add_action( 'plugins_loaded', 'sfp_page_config_reviews_laad', 1 );

/**
 * Laad de classes en de code [google_reviews], als de oude plugin er niet is.
 *
 * De classes laden ook als het onderdeel uit staat: het beheer leest er de
 * eigen reviews mee.
 */
function sfp_page_config_reviews_laad() {
    if ( sfp_page_config_reviews_oude_plugin() || class_exists( 'SFP_Reviews_Fetcher', false ) ) {
        return;
    }
    // Wordt de oude plugin in dit verzoek weer aangezet, dan laadt WordPress
    // hem straks nog. Met onze classes erbij loopt dat vast op dubbele namen.
    if ( sfp_page_config_plugin_wordt_aangezet( 'sfp-google-reviews' ) ) {
        return;
    }
    require_once SFP_PAGE_CONFIG_DIR . 'includes/reviews/class-sfp-reviews-fetcher.php';
    require_once SFP_PAGE_CONFIG_DIR . 'includes/reviews/class-sfp-reviews-manual.php';
    require_once SFP_PAGE_CONFIG_DIR . 'includes/reviews/class-sfp-reviews-renderer.php';

    add_action( 'init', 'sfp_page_config_reviews_shortcode' );
    add_action( 'wp_enqueue_scripts', 'sfp_page_config_reviews_assets' );
}

/**
 * Registreer [google_reviews]. Ook met het onderdeel uit: de code toont dan
 * niets, in plaats van als tekst op de pagina te staan.
 */
function sfp_page_config_reviews_shortcode() {
    if ( ! shortcode_exists( 'google_reviews' ) ) {
        add_shortcode( 'google_reviews', 'sfp_page_config_reviews_render' );
    }
}

/**
 * De instellingen van reviews, met de standaard voor wat ontbreekt.
 *
 * @return array
 */
function sfp_page_config_reviews_opties() {
    $opts = get_option( 'sfp_reviews_options', array() );
    $opts = is_array( $opts ) ? $opts : array();
    return array_merge(
        array(
            'api_key'      => '',
            'place_id'     => '',
            'min_stars'    => 4,
            'layout'       => 'slider',
            'auto'         => 'true',
            'interval'     => 5000,
            'cols_mobile'  => 1,
            'cols_desktop' => 1,
            'summary'      => 'true',
            'cache_ttl'    => DAY_IN_SECONDS,
        ),
        $opts
    );
}

/**
 * Schoon de instellingen op. Alleen de meegestuurde sleutels veranderen.
 *
 * @param  array $invoer Ruwe invoer.
 * @return array         De volledige, opgeschoonde instellingen.
 */
function sfp_page_config_reviews_opties_schoon( array $invoer ) {
    $opts = sfp_page_config_reviews_opties();
    if ( array_key_exists( 'api_key', $invoer ) ) {
        $opts['api_key'] = sanitize_text_field( (string) $invoer['api_key'] );
    }
    if ( array_key_exists( 'place_id', $invoer ) ) {
        $opts['place_id'] = sanitize_text_field( (string) $invoer['place_id'] );
    }
    if ( array_key_exists( 'min_stars', $invoer ) ) {
        $opts['min_stars'] = min( 5, max( 1, (int) $invoer['min_stars'] ) );
    }
    if ( array_key_exists( 'layout', $invoer ) ) {
        // "cijfer" (alleen het cijfer) is nieuw in 3.0.0 en bestaat niet in de oude plugin.
        $opts['layout'] = in_array( $invoer['layout'], array( 'slider', 'grid', 'banner', 'cijfer' ), true ) ? $invoer['layout'] : 'slider';
    }
    foreach ( array( 'auto', 'summary' ) as $vink ) {
        if ( array_key_exists( $vink, $invoer ) ) {
            $opts[ $vink ] = ( true === $invoer[ $vink ] || 'true' === $invoer[ $vink ] || '1' === (string) $invoer[ $vink ] ) ? 'true' : 'false';
        }
    }
    if ( array_key_exists( 'cols_mobile', $invoer ) ) {
        $opts['cols_mobile'] = min( 3, max( 1, (int) $invoer['cols_mobile'] ) );
    }
    if ( array_key_exists( 'cols_desktop', $invoer ) ) {
        $opts['cols_desktop'] = min( 5, max( 1, (int) $invoer['cols_desktop'] ) );
    }
    if ( array_key_exists( 'interval', $invoer ) ) {
        $opts['interval'] = max( 2000, (int) $invoer['interval'] );
    }
    if ( array_key_exists( 'cache_ttl', $invoer ) ) {
        $opts['cache_ttl'] = max( HOUR_IN_SECONDS, (int) $invoer['cache_ttl'] );
    }
    return $opts;
}

/**
 * De code [google_reviews].
 *
 * @param  array|string $atts Opties.
 * @return string
 */
function sfp_page_config_reviews_render( $atts ) {
    if ( ! sfp_page_config_reviews_actief() ) {
        return '';
    }
    $opts = sfp_page_config_reviews_opties();

    $atts = shortcode_atts(
        array(
            'place_id'     => $opts['place_id'],
            'layout'       => $opts['layout'],
            'min_stars'    => $opts['min_stars'],
            'auto'         => $opts['auto'],
            'interval'     => $opts['interval'],
            'limit'        => 20,
            'summary'      => $opts['summary'],
            'cols_mobile'  => $opts['cols_mobile'],
            'cols_desktop' => $opts['cols_desktop'],
            'manual_only'  => 'false',
        ),
        $atts,
        'google_reviews'
    );

    $min          = (int) $atts['min_stars'];
    $limit        = max( 1, (int) $atts['limit'] );
    $manual_only  = filter_var( $atts['manual_only'], FILTER_VALIDATE_BOOLEAN );
    $manual       = SFP_Reviews_Manual::get_filtered( $min );
    $show_summary = filter_var( $atts['summary'], FILTER_VALIDATE_BOOLEAN );
    $renderer     = new SFP_Reviews_Renderer();

    sfp_page_config_reviews_nodig( true );

    // Alleen eigen reviews, of geen koppeling met Google.
    if ( $manual_only || '' === (string) $atts['place_id'] || '' === (string) $opts['api_key'] ) {
        if ( empty( $manual ) ) {
            return '';
        }
        return sfp_page_config_reviews_vangnet() . $renderer->render( array_slice( $manual, 0, $limit ), $atts, array(), false );
    }

    $fetcher = new SFP_Reviews_Fetcher( (string) $opts['api_key'], (int) $opts['cache_ttl'] );
    $data    = $fetcher->get( (string) $atts['place_id'] );

    $meta   = array();
    $google = array();
    if ( ! is_wp_error( $data ) && ! empty( $data['reviews'] ) ) {
        $meta   = $data['meta'];
        $google = array_values(
            array_filter(
                $data['reviews'],
                function ( $r ) use ( $min ) {
                    return (int) $r['rating'] >= $min;
                }
            )
        );
    }

    // Alleen het cijfer: het oordeel, de sterren en het aantal reviews.
    if ( 'cijfer' === $atts['layout'] ) {
        return $meta ? sfp_page_config_reviews_vangnet() . $renderer->samenvatting( $meta ) : '';
    }

    // Google eerst, dan de eigen reviews.
    $reviews = array_slice( array_merge( $google, $manual ), 0, $limit );
    if ( empty( $reviews ) ) {
        return '';
    }
    return sfp_page_config_reviews_vangnet() . $renderer->render( $reviews, $atts, $meta, $show_summary );
}

/**
 * Houd bij of het reviewblok op deze pagina staat.
 *
 * @param  bool $zet True om te markeren.
 * @return bool
 */
function sfp_page_config_reviews_nodig( $zet = false ) {
    static $nodig = false;
    if ( $zet ) {
        $nodig = true;
    }
    return $nodig;
}

/**
 * Laad de opmaak en het script op pagina's met de code in de inhoud.
 */
function sfp_page_config_reviews_assets() {
    if ( ! sfp_page_config_reviews_actief() ) {
        return;
    }
    $post = get_post();
    if ( ! $post instanceof WP_Post || ! has_shortcode( $post->post_content, 'google_reviews' ) ) {
        return;
    }
    sfp_page_config_reviews_laad_assets();
}

/**
 * Zet de opmaak en het script in de wachtrij.
 */
function sfp_page_config_reviews_laad_assets() {
    if ( wp_style_is( 'sfp-reviews', 'enqueued' ) || wp_style_is( 'sfp-reviews', 'done' ) ) {
        return;
    }
    wp_enqueue_style( 'sfp-reviews', SFP_PAGE_CONFIG_URL . 'assets/reviews/reviews.css', array(), SFP_PAGE_CONFIG_VERSION );
    wp_enqueue_script( 'sfp-reviews', SFP_PAGE_CONFIG_URL . 'assets/reviews/reviews.js', array(), SFP_PAGE_CONFIG_VERSION, true );
    wp_add_inline_script(
        'sfp-reviews',
        'window.sfpReviewsTeksten=' . wp_json_encode(
            array(
                'meer'   => sfp_page_config_tekst( 'reviews_lees_verder' ),
                'minder' => sfp_page_config_tekst( 'reviews_minder' ),
            )
        ) . ';',
        'before'
    );
}

/**
 * Staat de code buiten de inhoud van de pagina (een widget, een Astra-layout),
 * dan is de opmaak in de head niet geladen. Laad hem dan alsnog; WordPress
 * zet een laat aangemelde stylesheet in de footer.
 *
 * @return string Altijd leeg; de functie bestaat om op de juiste plek aangeroepen te worden.
 */
function sfp_page_config_reviews_vangnet() {
    sfp_page_config_reviews_laad_assets();
    return '';
}

/**
 * De stand van de koppeling met Google, voor het beheer.
 *
 * @return array
 */
function sfp_page_config_reviews_stand() {
    $opts      = sfp_page_config_reviews_opties();
    $ingesteld = '' !== (string) $opts['place_id'] && '' !== (string) $opts['api_key'];
    $data      = $ingesteld ? get_transient( 'sfp_reviews_' . md5( (string) $opts['place_id'] ) ) : false;
    $meta      = is_array( $data ) && isset( $data['meta'] ) && is_array( $data['meta'] ) ? $data['meta'] : array();
    $teksten   = array(
        'oordeel' => '',
        'basis'   => '',
    );
    if ( ! empty( $meta['rating'] ) && class_exists( 'SFP_Reviews_Renderer' ) && method_exists( 'SFP_Reviews_Renderer', 'samenvatting_teksten' ) ) {
        $teksten = ( new SFP_Reviews_Renderer() )->samenvatting_teksten( $meta );
    }
    return array(
        'ingesteld' => $ingesteld,
        'verbonden' => ! empty( $meta['rating'] ),
        'cijfer'    => isset( $meta['rating'] ) ? (float) $meta['rating'] : 0.0,
        'cijfer_tekst' => isset( $meta['rating'] ) ? number_format_i18n( (float) $meta['rating'], 1 ) : '',
        'aantal'    => isset( $meta['total'] ) ? (int) $meta['total'] : 0,
        'oordeel'   => $teksten['oordeel'],
        'basis'     => $teksten['basis'],
    );
}
