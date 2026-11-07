<?php
/**
 * SFP Page Config - Tools: kern
 *
 * Een tool is een bericht van het type `tool` met een beschrijving (JSON)
 * in het metaveld `sfp_tool`. Per soort tool draait één vaste motor in de
 * browser (assets/tool/); de server bewaart niets over bezoekers.
 *
 * Dit bestand regelt het berichttype (alleen als het nog niet bestaat), het
 * metaveld met revisies, het onderdeel Tools en de controle bij het
 * opslaan via REST. De beschrijving zelf: includes/tool-beschrijving.php.
 * De weergave: includes/tool-weergave.php. De vorm van de pagina:
 * includes/tool-vorm.php. De routes voor mail en duimpjes:
 * includes/tool-rest.php.
 *
 * @package SFP_Page_Config
 * @since   3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Staat het onderdeel Tools aan op deze site?
 *
 * @return bool
 */
function sfp_page_config_tool_aan() {
    return post_type_exists( 'tool' )
        && function_exists( 'sfp_page_config_onderdeel_aan' )
        && sfp_page_config_onderdeel_aan( 'tools' );
}

/**
 * Krijgt een tool de pagina-opmaak (regel boven de titel, makerskaart,
 * andere tools)? Daarvoor moet ook de artikelopmaak aan staan.
 *
 * @return bool
 */
function sfp_page_config_tool_vorm_aan() {
    return sfp_page_config_tool_aan()
        && function_exists( 'sfp_page_config_artikel_aan' )
        && sfp_page_config_artikel_aan();
}

/**
 * Is dit bericht een tool?
 *
 * @param  WP_Post|int|null $post Bericht.
 * @return bool
 */
function sfp_page_config_is_tool( $post ) {
    $post = get_post( $post );
    return $post && 'tool' === $post->post_type;
}

add_action( 'init', 'sfp_page_config_tool_meta', 25 );

/**
 * Het metaveld met de beschrijving. Met Tools aan ook revisies: elke versie
 * van het bericht bewaart dan de beschrijving van dat moment.
 */
function sfp_page_config_tool_meta() {
    if ( ! post_type_exists( 'tool' ) ) {
        return;
    }
    $aan = sfp_page_config_tool_aan();
    // Het snippet "Tools: berichttype" registreert het type zonder revisies;
    // zonder revisies weigert WordPress een metaveld met revisies. Alleen met
    // Tools aan verandert de plugin iets aan het bestaande type.
    if ( $aan && ! post_type_supports( 'tool', 'revisions' ) ) {
        add_post_type_support( 'tool', 'revisions' );
    }
    register_post_meta(
        'tool',
        'sfp_tool',
        array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'revisions_enabled' => $aan,
            'default'           => '',
            'sanitize_callback' => 'sfp_page_config_tool_meta_schoon',
            'auth_callback'     => function ( $toegestaan, $sleutel, $post_id ) {
                return current_user_can( 'edit_post', $post_id );
            },
        )
    );
    // Alleen in artikel (3.28.0): de tool hoort bij een artikel en staat
    // niet op /tools/, niet in Andere tools, niet in de sitemap. Het blok
    // sfp/tool toont hem gewoon; zijn eigen adres stuurt door naar het artikel.
    register_post_meta(
        'tool',
        'sfp_tool_alleen_artikel',
        array(
            'type'              => 'boolean',
            'single'            => true,
            'show_in_rest'      => true,
            'default'           => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
            'auth_callback'     => function ( $toegestaan, $sleutel, $post_id ) {
                return current_user_can( 'edit_post', $post_id );
            },
        )
    );
}

/**
 * Bewaar de beschrijving zoals hij is, zonder omliggende witruimte.
 * Controleren gebeurt bij het opslaan via REST (sfp_page_config_tool_rest_controle)
 * en in het paneel; een ongeldige beschrijving wordt daar geweigerd.
 *
 * @param  mixed $waarde Ingevoerde waarde.
 * @return string
 */
function sfp_page_config_tool_meta_schoon( $waarde ) {
    $waarde = is_string( $waarde ) ? trim( $waarde ) : '';
    return wp_check_invalid_utf8( $waarde );
}

add_filter( 'rest_pre_insert_tool', 'sfp_page_config_tool_rest_controle', 10, 2 );

/**
 * Een beschrijving die niet klopt, wordt bij het opslaan via REST
 * geweigerd, met de fouten in het antwoord. Een lege beschrijving mag:
 * dan toont de pagina alleen de tekst.
 *
 * @param  stdClass        $post    Het bericht zoals het wordt opgeslagen.
 * @param  WP_REST_Request $verzoek Het verzoek.
 * @return stdClass|WP_Error
 */
function sfp_page_config_tool_rest_controle( $post, $verzoek ) {
    sfp_page_config_tool_motor();
    if ( ! sfp_page_config_tool_aan() ) {
        return $post;
    }
    $meta = $verzoek->get_param( 'meta' );
    if ( ! is_array( $meta ) || ! array_key_exists( 'sfp_tool', $meta ) ) {
        return $post;
    }
    $tekst = is_string( $meta['sfp_tool'] ) ? trim( $meta['sfp_tool'] ) : '';
    if ( '' === $tekst ) {
        return $post;
    }
    $uitkomst = sfp_page_config_tool_controleer_tekst( $tekst );
    if ( $uitkomst['fouten'] ) {
        return new WP_Error(
            'sfp_tool_ongeldig',
            'De beschrijving van de tool klopt niet: ' . implode( ' ', $uitkomst['fouten'] ),
            array(
                'status' => 400,
                'fouten' => $uitkomst['fouten'],
                'let_op' => $uitkomst['let_op'],
            )
        );
    }
    return $post;
}

add_filter( 'sfp_page_config_artikel_posttypes', 'sfp_page_config_tool_posttypes' );

/**
 * Tools krijgen de artikelopmaak als het onderdeel aan staat.
 *
 * @param  string[] $types Berichttypes.
 * @return string[]
 */
function sfp_page_config_tool_posttypes( $types ) {
    if ( sfp_page_config_tool_vorm_aan() && ! in_array( 'tool', (array) $types, true ) ) {
        $types[] = 'tool';
    }
    return $types;
}

/**
 * De beschrijving van een tool, geparst. Per aanvraag bewaard; een opslag
 * van het metaveld wist het geheugen voor dat bericht.
 *
 * @param  WP_Post|int $post     Tool.
 * @param  bool        $vernieuw Het geheugen voor dit bericht wissen.
 * @return array|null            Null zonder (geldige) beschrijving.
 */
function sfp_page_config_tool_beschrijving( $post, $vernieuw = false ) {
    static $geheugen = array();
    $post            = get_post( $post );
    if ( ! $post || 'tool' !== $post->post_type ) {
        return null;
    }
    if ( $vernieuw ) {
        unset( $geheugen[ $post->ID ] );
        return null;
    }
    if ( array_key_exists( $post->ID, $geheugen ) ) {
        return $geheugen[ $post->ID ];
    }
    $geheugen[ $post->ID ] = sfp_page_config_tool_beschrijving_vers( $post );
    return $geheugen[ $post->ID ];
}

add_action( 'added_post_meta', 'sfp_page_config_tool_beschrijving_wis', 10, 3 );
add_action( 'updated_post_meta', 'sfp_page_config_tool_beschrijving_wis', 10, 3 );
add_action( 'deleted_post_meta', 'sfp_page_config_tool_beschrijving_wis', 10, 3 );

/**
 * Na een opslag van het metaveld leest de volgende aanroep vers.
 *
 * @param int|array $meta_id Meta-id (ongebruikt).
 * @param int       $post_id Bericht.
 * @param string    $sleutel Metasleutel.
 */
function sfp_page_config_tool_beschrijving_wis( $meta_id, $post_id, $sleutel ) {
    if ( 'sfp_tool' === $sleutel ) {
        sfp_page_config_tool_beschrijving( (int) $post_id, true );
    }
}

/**
 * De beschrijving opnieuw uit de database, buiten het geheugen om.
 *
 * @param  WP_Post|int $post Tool.
 * @return array|null
 */
function sfp_page_config_tool_beschrijving_vers( $post ) {
    sfp_page_config_tool_motor();
    $post = get_post( $post );
    if ( ! $post ) {
        return null;
    }
    $tekst = (string) get_post_meta( $post->ID, 'sfp_tool', true );
    $data  = '' !== trim( $tekst ) ? json_decode( $tekst, true ) : null;
    return is_array( $data ) ? sfp_page_config_tool_normaliseer( $data ) : null;
}

/**
 * De soorten tool die de motor kent, met hun naam in de regel boven de
 * titel (sleutel in de teksten van de artikelopmaak).
 *
 * @return array<string, string> soort => tekstsleutel
 */
function sfp_page_config_tool_soorten() {
    return array(
        'test'         => 'tool_test',
        'generator'    => 'tool_generator',
        'rekenaar'     => 'tool_rekenaar',
        'checklist'    => 'tool_checklist',
        'tekstanalyse' => 'tool_tekstanalyse',
    );
}

/**
 * Alle gepubliceerde tools van de site, voor het blok Tool en Andere tools.
 * Zonder de tools die alleen in een artikel staan (3.28.0), tenzij gevraagd.
 *
 * @param  bool $ook_alleen_artikel Ook de tools die alleen in een artikel staan (voor de keuzelijst in de editor).
 * @return WP_Post[]
 */
function sfp_page_config_tools( $ook_alleen_artikel = false ) {
    if ( ! post_type_exists( 'tool' ) ) {
        return array();
    }
    $args = array(
        'post_type'        => 'tool',
        'post_status'      => 'publish',
        'posts_per_page'   => 100,
        'orderby'          => 'title',
        'order'            => 'ASC',
        'no_found_rows'    => true,
        'suppress_filters' => true,
    );
    if ( $ook_alleen_artikel ) {
        $args['sfp_alle_tools'] = true;
    } else {
        $args['meta_query'] = sfp_page_config_tool_alleen_artikel_meta_query(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- hoogstens 100 tools.
    }
    return get_posts( $args );
}
