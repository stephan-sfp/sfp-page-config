<?php
/**
 * SFP Page Config - Vormen: artikel, pijler, begrip en tool
 *
 * De vorm volgt het berichttype (besluit Stephan 18-10-2026): een bericht is
 * een artikel of een pijler, een begrip is een begrip, een tool een tool.
 * Dit bestand regelt wat de vormen delen en de pijler:
 *
 *   - de gegevens per bericht (vorm, gids van, eigen adres, zelftest, en bij
 *     begrippen en tools: hoort bij de pijler);
 *   - welke categorie welke gids heeft;
 *   - het adres: de gids staat op het adres van zijn categorie, tenzij hij
 *     zijn eigen adres houdt (dan verwijst de categorie naar de gids);
 *   - de kaart In this guide, de gegevens voor Google en de controle.
 *
 * De pijler staat aan per site (onderdeel Pijler, standaard uit) en werkt
 * alleen met de artikelopmaak aan.
 *
 * @package SFP_Page_Config
 * @since   3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * De vorm van een bericht
 * ====================================================================== */

/**
 * Staat de pijler aan op deze site?
 *
 * @return bool
 */
function sfp_page_config_pijler_aan() {
    return function_exists( 'sfp_page_config_onderdeel_aan' )
        && sfp_page_config_onderdeel_aan( 'pijler' )
        && function_exists( 'sfp_page_config_artikel_aan' )
        && sfp_page_config_artikel_aan();
}

/**
 * De vorm van een bericht: artikel, pijler, begrip, tool of '' (geen vorm).
 *
 * @param  WP_Post|int|null $post Bericht.
 * @return string
 */
function sfp_page_config_vorm( $post ) {
    $post = get_post( $post );
    if ( ! $post ) {
        return '';
    }
    switch ( $post->post_type ) {
        case 'post':
            return ( sfp_page_config_pijler_aan() && 'pijler' === get_post_meta( $post->ID, 'sfp_vorm', true ) ) ? 'pijler' : 'artikel';
        case 'begrippen':
            return 'begrip';
        case 'tool':
            return 'tool';
    }
    return '';
}

add_action( 'init', 'sfp_page_config_vormen_meta', 12 );

/**
 * Registreer de gegevens per bericht voor het paneel Deze pagina en REST.
 */
function sfp_page_config_vormen_meta() {
    $mag = function ( $toegestaan, $sleutel, $post_id ) {
        return current_user_can( 'edit_post', $post_id );
    };
    $vlag = function ( $waarde ) {
        return '1' === (string) $waarde || true === $waarde ? '1' : '';
    };

    register_post_meta(
        'post',
        'sfp_vorm',
        array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => function ( $waarde ) {
                return 'pijler' === $waarde ? 'pijler' : '';
            },
            'auth_callback'     => $mag,
            'revisions_enabled' => true,
        )
    );
    foreach ( array( 'sfp_gids_van', 'sfp_zelftest_tool' ) as $sleutel ) {
        register_post_meta(
            'post',
            $sleutel,
            array(
                'type'              => 'integer',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
                'auth_callback'     => $mag,
            )
        );
    }
    register_post_meta(
        'post',
        'sfp_eigen_adres',
        array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => $vlag,
            'auth_callback'     => $mag,
        )
    );
    foreach ( array( 'begrippen', 'tool' ) as $type ) {
        register_post_meta(
            $type,
            'sfp_pijler',
            array(
                'type'              => 'integer',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
                'auth_callback'     => $mag,
            )
        );
    }
}

/* =========================================================================
 * Welke categorie welke gids heeft
 * ====================================================================== */

/**
 * De categorie waarvan een pijler de gids is: de gekozen categorie, anders
 * de diepste categorie van het bericht.
 *
 * @param  WP_Post|int $post Bericht.
 * @return WP_Term|null
 */
function sfp_page_config_gids_term( $post ) {
    $post = get_post( $post );
    if ( ! $post ) {
        return null;
    }
    $gekozen = (int) get_post_meta( $post->ID, 'sfp_gids_van', true );
    if ( $gekozen ) {
        $term = get_term( $gekozen, 'category' );
        if ( $term && ! is_wp_error( $term ) ) {
            return $term;
        }
    }
    return function_exists( 'sfp_page_config_vervolg_pijler' ) ? sfp_page_config_vervolg_pijler( $post ) : null;
}

/**
 * Alle gidsen van de site: categorie => bericht. Bij twee gidsen voor één
 * categorie telt de oudste; de controle meldt de andere.
 *
 * @param  bool $vers True om de opgeslagen lijst over te slaan.
 * @return array<int, int>
 */
function sfp_page_config_gidsen( $vers = false ) {
    static $geheugen = null;
    static $stand    = -1;
    if ( ! sfp_page_config_pijler_aan() ) {
        return array();
    }
    $nu = isset( $GLOBALS['sfp_page_config_gidsen_stand'] ) ? (int) $GLOBALS['sfp_page_config_gidsen_stand'] : 0;
    if ( null !== $geheugen && ! $vers && $stand === $nu ) {
        return $geheugen;
    }
    $stand = $nu;
    $opgeslagen = $vers ? false : get_transient( 'sfp_pc_gidsen' );
    if ( is_array( $opgeslagen ) ) {
        $geheugen = $opgeslagen;
        return $geheugen;
    }
    $ids = get_posts(
        array(
            'post_type'        => 'post',
            'post_status'      => 'publish',
            'posts_per_page'   => 200,
            'fields'           => 'ids',
            'orderby'          => 'date',
            'order'            => 'ASC',
            'meta_key'         => 'sfp_vorm', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- kleine set, een uur bewaard.
            'meta_value'       => 'pijler', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'suppress_filters' => true,
            'no_found_rows'    => true,
        )
    );
    $kaart = array();
    foreach ( $ids as $id ) {
        $term = sfp_page_config_gids_term( $id );
        if ( $term && ! isset( $kaart[ (int) $term->term_id ] ) ) {
            $kaart[ (int) $term->term_id ] = (int) $id;
        }
    }
    set_transient( 'sfp_pc_gidsen', $kaart, HOUR_IN_SECONDS );
    $geheugen = $kaart;
    return $geheugen;
}

/**
 * De gids van een categorie.
 *
 * @param  int $term_id Categorie.
 * @return int Bericht, of 0.
 */
function sfp_page_config_gids_van_term( $term_id ) {
    $gidsen = sfp_page_config_gidsen();
    return isset( $gidsen[ (int) $term_id ] ) ? (int) $gidsen[ (int) $term_id ] : 0;
}

/**
 * Is dit bericht de gids van een categorie? Geeft die categorie.
 *
 * @param  WP_Post|int $post Bericht.
 * @return WP_Term|null
 */
function sfp_page_config_is_gids( $post ) {
    $post = get_post( $post );
    if ( ! $post || 'post' !== $post->post_type ) {
        return null;
    }
    // Een pijler die nog niet gepubliceerd is (concept, voorbeeldlink): toon
    // hem zoals hij wordt, met de kaart van zijn categorie. Het adres en de
    // verwijzingen gelden pas na publiceren.
    if ( 'publish' !== $post->post_status ) {
        return 'pijler' === sfp_page_config_vorm( $post ) ? sfp_page_config_gids_term( $post ) : null;
    }
    $term_id = array_search( (int) $post->ID, sfp_page_config_gidsen(), true );
    if ( false === $term_id ) {
        return null;
    }
    $term = get_term( (int) $term_id, 'category' );
    return ( $term && ! is_wp_error( $term ) ) ? $term : null;
}

/**
 * Wis de opgeslagen lijst met gidsen.
 */
function sfp_page_config_gidsen_wis() {
    delete_transient( 'sfp_pc_gidsen' );
    $GLOBALS['sfp_page_config_gidsen_stand'] = ( isset( $GLOBALS['sfp_page_config_gidsen_stand'] ) ? (int) $GLOBALS['sfp_page_config_gidsen_stand'] : 0 ) + 1; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- prefix staat erin.
}
add_action( 'save_post_post', 'sfp_page_config_gidsen_wis' );
add_action( 'deleted_post', 'sfp_page_config_gidsen_wis' );
add_action( 'trashed_post', 'sfp_page_config_gidsen_wis' );
add_action( 'untrashed_post', 'sfp_page_config_gidsen_wis' );
add_action( 'edited_category', 'sfp_page_config_gidsen_wis' );
add_action( 'delete_category', 'sfp_page_config_gidsen_wis' );
add_action( 'update_option_sfp_settings', 'sfp_page_config_gidsen_wis' );
add_action( 'added_post_meta', 'sfp_page_config_gidsen_meta_wis', 10, 3 );
add_action( 'updated_post_meta', 'sfp_page_config_gidsen_meta_wis', 10, 3 );
add_action( 'deleted_post_meta', 'sfp_page_config_gidsen_meta_wis', 10, 3 );

/**
 * Wis de lijst als de vorm of de categorie van een gids verandert. De
 * editor bewaart de gegevens na save_post, dus save_post alleen is niet
 * genoeg.
 *
 * @param  int|int[] $meta_id  Niet gebruikt.
 * @param  int       $post_id  Bericht.
 * @param  string    $sleutel  Meta-sleutel.
 */
function sfp_page_config_gidsen_meta_wis( $meta_id, $post_id, $sleutel ) {
    if ( in_array( $sleutel, array( 'sfp_vorm', 'sfp_gids_van', 'sfp_eigen_adres' ), true ) ) {
        sfp_page_config_gidsen_wis();
    }
}

/* =========================================================================
 * Het adres van de pijler
 * ====================================================================== */

/**
 * Houdt deze gids zijn eigen adres?
 *
 * @param  int $post_id Gids.
 * @return bool
 */
function sfp_page_config_gids_eigen_adres( $post_id ) {
    return '1' === (string) get_post_meta( (int) $post_id, 'sfp_eigen_adres', true );
}

add_filter( 'post_link', 'sfp_page_config_gids_link', 20, 2 );

/**
 * Het adres van een gids is het adres van zijn categorie.
 *
 * @param  string  $url  Adres.
 * @param  WP_Post $post Bericht.
 * @return string
 */
function sfp_page_config_gids_link( $url, $post ) {
    if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
        return $url;
    }
    $term = sfp_page_config_is_gids( $post );
    if ( ! $term || sfp_page_config_gids_eigen_adres( $post->ID ) ) {
        return $url;
    }
    $link = get_term_link( $term );
    return is_wp_error( $link ) ? $url : $link;
}

add_filter( 'request', 'sfp_page_config_gids_verzoek', 20 );

/**
 * Een verzoek om een categorie met een gids toont de gids op dat adres.
 * Feeds en pagina 2 en verder van de categorie blijven gewoon.
 *
 * @param  array $vars Queryvariabelen.
 * @return array
 */
function sfp_page_config_gids_verzoek( $vars ) {
    if ( is_admin() || empty( $vars['category_name'] ) || ! empty( $vars['feed'] ) || ! empty( $vars['paged'] ) || ! sfp_page_config_pijler_aan() ) {
        return $vars;
    }
    $pad  = trim( (string) $vars['category_name'], '/' );
    $term = get_category_by_path( $pad, true );
    if ( ! $term || is_wp_error( $term ) ) {
        return $vars;
    }
    $gids = sfp_page_config_gids_van_term( $term->term_id );
    if ( ! $gids || sfp_page_config_gids_eigen_adres( $gids ) || 'publish' !== get_post_status( $gids ) ) {
        return $vars;
    }
    $GLOBALS['sfp_page_config_gids_op_categorie'] = $gids; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- prefix staat erin.
    $nieuw = array(
        'p'         => $gids,
        'post_type' => 'post',
    );
    foreach ( array( 'preview', 'sfp_voorbeeld' ) as $houd ) {
        if ( isset( $vars[ $houd ] ) ) {
            $nieuw[ $houd ] = $vars[ $houd ];
        }
    }
    return $nieuw;
}

add_action( 'template_redirect', 'sfp_page_config_gids_doorsturen', 5 );

/**
 * Stuur door naar het ene adres van een pijler:
 *   - een gids op het categorieadres: zijn eigen adres gaat naar de categorie;
 *   - een gids met eigen adres: de categorie gaat naar de gids.
 */
function sfp_page_config_gids_doorsturen() {
    if ( ! sfp_page_config_pijler_aan() || is_preview() || is_feed() ) {
        return;
    }
    if ( is_category() && ! is_paged() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) {
            $gids = sfp_page_config_gids_van_term( $term->term_id );
            if ( $gids && sfp_page_config_gids_eigen_adres( $gids ) && 'publish' === get_post_status( $gids ) ) {
                wp_safe_redirect( get_permalink( $gids ), 301 );
                exit;
            }
        }
        return;
    }
    if ( ! is_singular( 'post' ) ) {
        return;
    }
    $post = get_queried_object();
    if ( ! $post instanceof WP_Post || ! empty( $GLOBALS['sfp_page_config_gids_op_categorie'] ) ) {
        return;
    }
    $term = sfp_page_config_is_gids( $post );
    if ( $term && ! sfp_page_config_gids_eigen_adres( $post->ID ) && 'publish' === $post->post_status ) {
        $link = get_term_link( $term );
        if ( ! is_wp_error( $link ) ) {
            wp_safe_redirect( $link, 301 );
            exit;
        }
    }
}

/* =========================================================================
 * Wat bij een pijler hoort
 * ====================================================================== */

/**
 * De artikelen van een categorie (zonder gidsen), in de volgorde van de
 * kaart: eerst wat de gids zelf noemt, in die volgorde; daarna de rest,
 * nieuwste eerst.
 *
 * @param  WP_Term $term     Categorie.
 * @param  WP_Post $gids     De gids (om zijn links te lezen), of null.
 * @param  bool    $diep     Ook de artikelen in subcategorieën.
 * @return WP_Post[]
 */
function sfp_page_config_pijler_artikelen( $term, $gids = null, $diep = false ) {
    $gidsen = array_values( sfp_page_config_gidsen() );
    $posts  = get_posts(
        array(
            'post_type'        => 'post',
            'post_status'      => 'publish',
            'posts_per_page'   => 300,
            'orderby'          => 'date',
            'order'            => 'DESC',
            'post__not_in'     => $gidsen, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- korte lijst gidsen.
            'no_found_rows'    => true,
            'suppress_filters' => false,
            'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                array(
                    'taxonomy'         => 'category',
                    'field'            => 'term_id',
                    'terms'            => (int) $term->term_id,
                    'include_children' => (bool) $diep,
                ),
            ),
        )
    );
    if ( ! $gids || ! $posts ) {
        return $posts;
    }
    // Volgorde uit de tekst van de gids: links en blokken Lees ook, zoals ze in de tekst staan.
    $volgorde = array();
    foreach ( parse_blocks( (string) $gids->post_content ) as $blok ) {
        sfp_page_config_pijler_lees_ook_ids( $blok, $volgorde );
    }
    if ( ! $volgorde ) {
        return $posts;
    }
    $eerst = array();
    $rest  = array();
    $plek  = array_flip( $volgorde );
    foreach ( $posts as $p ) {
        if ( isset( $plek[ (int) $p->ID ] ) ) {
            $eerst[ $plek[ (int) $p->ID ] ] = $p;
        } else {
            $rest[] = $p;
        }
    }
    ksort( $eerst );
    return array_merge( array_values( $eerst ), $rest );
}

/**
 * Lees de artikelen waar een blok naar verwijst, in de volgorde van de
 * tekst: gewone links en het blok Lees ook.
 *
 * @param array $blok     Blok.
 * @param int[] $volgorde Lijst om aan te vullen.
 */
function sfp_page_config_pijler_lees_ook_ids( $blok, array &$volgorde ) {
    $voeg = function ( $id ) use ( &$volgorde ) {
        $id = (int) $id;
        if ( $id && ! in_array( $id, $volgorde, true ) ) {
            $volgorde[] = $id;
        }
    };
    if ( 'sfp/lees-ook' === $blok['blockName'] && ! empty( $blok['attrs']['postId'] ) ) {
        $voeg( $blok['attrs']['postId'] );
    }
    $eigen = isset( $blok['innerHTML'] ) ? (string) $blok['innerHTML'] : '';
    if ( ! empty( $blok['innerBlocks'] ) ) {
        // Tekst van het blok zelf en de binnenblokken in volgorde.
        $stukken = isset( $blok['innerContent'] ) ? $blok['innerContent'] : array( $eigen );
        $binnen  = $blok['innerBlocks'];
        $i       = 0;
        foreach ( $stukken as $stuk ) {
            if ( null === $stuk ) {
                if ( isset( $binnen[ $i ] ) ) {
                    sfp_page_config_pijler_lees_ook_ids( $binnen[ $i ], $volgorde );
                }
                ++$i;
                continue;
            }
            sfp_page_config_pijler_links_ids( (string) $stuk, $voeg );
        }
        return;
    }
    sfp_page_config_pijler_links_ids( $eigen, $voeg );
}

/**
 * Lees de berichten waar de links in een stuk HTML naartoe gaan.
 *
 * @param string   $html HTML.
 * @param callable $voeg Wordt aangeroepen met elk bericht.
 */
function sfp_page_config_pijler_links_ids( $html, $voeg ) {
    if ( false === stripos( $html, 'href' ) || ! preg_match_all( '#<a\s[^>]*href=(["\'])(.*?)\1#i', $html, $links ) ) {
        return;
    }
    foreach ( $links[2] as $href ) {
        $voeg( url_to_postid( html_entity_decode( $href ) ) );
    }
}

/**
 * De begrippen die bij een pijler horen, op alfabet.
 *
 * @param  int $term_id Categorie.
 * @return WP_Post[]
 */
function sfp_page_config_pijler_begrippen( $term_id ) {
    if ( ! post_type_exists( 'begrippen' ) ) {
        return array();
    }
    return get_posts(
        array(
            'post_type'        => 'begrippen',
            'post_status'      => 'publish',
            'posts_per_page'   => 500,
            'orderby'          => 'title',
            'order'            => 'ASC',
            'no_found_rows'    => true,
            'suppress_filters' => false,
            'meta_key'         => 'sfp_pijler', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_value'       => (int) $term_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        )
    );
}

/**
 * Link naar de woordenlijst van een pijler: het archief van de begrippen,
 * gefilterd op de pijler.
 *
 * @param  WP_Term $term Categorie.
 * @return string
 */
function sfp_page_config_pijler_woordenlijst_url( $term ) {
    $archief = post_type_exists( 'begrippen' ) ? get_post_type_archive_link( 'begrippen' ) : '';
    return $archief ? add_query_arg( 'pijler', $term->slug, $archief ) : '';
}

add_action( 'pre_get_posts', 'sfp_page_config_woordenlijst_filter' );

/**
 * Het archief van de begrippen met ?pijler=<slug> toont alleen de begrippen
 * van die pijler.
 *
 * @param WP_Query $query Query.
 */
function sfp_page_config_woordenlijst_filter( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'begrippen' ) || empty( $_GET['pijler'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- alleen lezen.
        return;
    }
    $term = get_term_by( 'slug', sanitize_title( wp_unslash( $_GET['pijler'] ) ), 'category' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! $term ) {
        return;
    }
    $query->set( 'meta_key', 'sfp_pijler' );
    $query->set( 'meta_value', (int) $term->term_id );
    $query->set( 'orderby', 'title' );
    $query->set( 'order', 'ASC' );
}

/**
 * De zelftest van een pijler: een gepubliceerde tool.
 *
 * @param  WP_Post|int $post Gids.
 * @return WP_Post|null
 */
function sfp_page_config_pijler_zelftest( $post ) {
    $post = get_post( $post );
    if ( ! $post ) {
        return null;
    }
    $tool = (int) get_post_meta( $post->ID, 'sfp_zelftest_tool', true );
    if ( ! $tool ) {
        return null;
    }
    $tool = get_post( $tool );
    return ( $tool && 'publish' === $tool->post_status ) ? $tool : null;
}

/**
 * Het aantal vragen van een zelftest, als de tool dat zegt.
 *
 * @param  WP_Post $tool Tool.
 * @return int 0 als onbekend.
 */
function sfp_page_config_tool_aantal_vragen( $tool ) {
    $beschrijving = json_decode( (string) get_post_meta( $tool->ID, 'sfp_tool', true ), true );
    if ( is_array( $beschrijving ) && isset( $beschrijving['stellingen'] ) && is_array( $beschrijving['stellingen'] ) ) {
        return count( $beschrijving['stellingen'] );
    }
    return 0;
}

/* =========================================================================
 * De kaart In this guide
 * ====================================================================== */

/**
 * Eén artikel in de kaart.
 *
 * @param  WP_Post $p Artikel.
 * @return string
 */
function sfp_page_config_gids_item( $p ) {
    $t     = 'sfp_page_config_artikel_tekst';
    $beeld = get_the_post_thumbnail(
        $p,
        'medium',
        array(
            'loading' => 'lazy',
            'alt'     => '',
            'sizes'   => '(max-width:600px) 45vw, 120px',
        )
    );
    return '<a class="sfp-gids__item" href="' . esc_url( get_permalink( $p ) ) . '">'
        . '<span class="sfp-gids__vlak' . ( '' === $beeld ? ' sfp-gids__vlak--leeg' : '' ) . '" aria-hidden="true">' . $beeld . '</span>'
        . '<span class="sfp-gids__tekst"><b>' . esc_html( wp_strip_all_tags( get_the_title( $p ) ) ) . '</b><em>' . esc_html( sprintf( $t( 'leestijd' ), sfp_page_config_artikel_leestijd( $p->post_content ) ) ) . '</em></span></a>';
}

/**
 * Tel iets met het juiste woord: "1 article", "4 articles".
 *
 * @param  int    $aantal  Aantal.
 * @param  string $sleutel Basis van de sleutel (artikelen, begrippen, gidsen).
 * @return string
 */
function sfp_page_config_gids_tel( $aantal, $sleutel ) {
    return sprintf( sfp_page_config_artikel_tekst( 1 === (int) $aantal ? $sleutel . '_1' : $sleutel ), (int) $aantal );
}

/**
 * De kaart van de pijler.
 *
 * @param  WP_Post $gids De gids.
 * @return string        HTML, of '' als er niets bij de pijler hoort.
 */
function sfp_page_config_gids_kaart( $gids ) {
    $term = sfp_page_config_is_gids( $gids );
    if ( ! $term ) {
        return '';
    }
    $t         = 'sfp_page_config_artikel_tekst';
    $kinderen  = get_terms(
        array(
            'taxonomy'   => 'category',
            'parent'     => (int) $term->term_id,
            'hide_empty' => false,
        )
    );
    $groepen   = array();
    if ( is_array( $kinderen ) ) {
        foreach ( $kinderen as $kind ) {
            $kind_gids = sfp_page_config_gids_van_term( $kind->term_id );
            $lijst     = sfp_page_config_pijler_artikelen( $kind, $kind_gids ? get_post( $kind_gids ) : null, true );
            if ( $lijst ) {
                $groepen[] = array( $kind, $lijst );
            }
        }
    }
    $begrippen = sfp_page_config_pijler_begrippen( $term->term_id );
    $knop      = '<button type="button" class="sfp-gids__knop" aria-expanded="false" data-sfp-toon="' . esc_attr( $t( 'toon' ) ) . '" data-sfp-verberg="' . esc_attr( $t( 'verberg' ) ) . '"><span>' . esc_html( $t( 'toon' ) ) . '</span>' . sfp_page_config_artikel_icoon( 'omlaag' ) . '</button>';
    $kop_label = '<span class="sfp-blok-label" id="sfp-gids-kop">' . esc_html( $t( 'in_deze_gids' ) ) . '</span>';

    // Hoofdpijler: per pijler gegroepeerd, hoogstens vier per pijler.
    if ( $groepen ) {
        $totaal = 0;
        $html   = '';
        foreach ( $groepen as $groep ) {
            list( $kind, $lijst ) = $groep;
            $totaal += count( $lijst );
            $items   = '';
            foreach ( array_slice( $lijst, 0, 4 ) as $p ) {
                $items .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( wp_strip_all_tags( get_the_title( $p ) ) ) . '</a></li>';
            }
            $kind_url = get_term_link( $kind );
            $kind_url = is_wp_error( $kind_url ) ? '' : $kind_url;
            $html    .= '<div class="sfp-gids__groep">'
                . ( '' !== $kind_url ? '<a class="sfp-pijl" href="' . esc_url( $kind_url ) . '">' . esc_html( $kind->name ) . '</a>' : '<b>' . esc_html( $kind->name ) . '</b>' )
                . '<ul>' . $items . '</ul>'
                . ( count( $lijst ) > 4 && '' !== $kind_url ? '<a class="sfp-gids__alle" href="' . esc_url( $kind_url ) . '">' . esc_html( sprintf( $t( 'alle_in' ), count( $lijst ), $kind->name ) ) . '</a>' : '' )
                . '</div>';
        }
        $tel = sfp_page_config_gids_tel( count( $groepen ), 'n_gidsen' ) . ' • ' . sfp_page_config_gids_tel( $totaal, 'n_artikelen' );
        return '<nav class="sfp-gids sfp-gids--groepen" aria-labelledby="sfp-gids-kop" data-sfp-gids>'
            . '<div class="sfp-gids__kop">' . $kop_label . '<small>' . esc_html( $tel ) . '</small>' . $knop . '</div>'
            . '<div class="sfp-gids__inhoud"><div class="sfp-gids__groepen">' . $html . '</div>' . sfp_page_config_gids_begrippen( $term, $begrippen ) . '</div></nav>'
            . sfp_page_config_gids_zonder_script();
    }

    $artikelen = sfp_page_config_pijler_artikelen( $term, $gids );
    if ( ! $artikelen && ! $begrippen ) {
        return '';
    }
    $tel = array();
    if ( $artikelen ) {
        $tel[] = sfp_page_config_gids_tel( count( $artikelen ), 'n_artikelen' );
    }
    if ( $begrippen ) {
        $tel[] = sfp_page_config_gids_tel( count( $begrippen ), 'n_begrippen' );
    }

    // Tot vier artikelen een raster; vanaf vijf een slider die per vier schuift.
    $slider = count( $artikelen ) > 4;
    $lijst  = '';
    if ( $slider ) {
        $paginas = array_chunk( $artikelen, 4 );
        foreach ( $paginas as $i => $pagina ) {
            $lijst .= '<div class="sfp-gids__pagina" role="group" aria-label="' . esc_attr( sprintf( $t( 'pagina_van' ), $i + 1, count( $paginas ) ) ) . '">';
            foreach ( $pagina as $p ) {
                $lijst .= sfp_page_config_gids_item( $p );
            }
            $lijst .= '</div>';
        }
    } else {
        foreach ( $artikelen as $p ) {
            $lijst .= sfp_page_config_gids_item( $p );
        }
    }
    $pijlen = '';
    if ( $slider ) {
        $pijlen = '<span class="sfp-gids__teller" aria-live="polite">' . esc_html( sprintf( $t( 'pagina_van' ), 1, count( $paginas ) ) ) . '</span>'
            . '<span class="sfp-gids__pijlen"><button type="button" class="sfp-gids__vorige" aria-label="' . esc_attr( $t( 'vorige' ) ) . '" aria-controls="sfp-gids-strook" disabled>' . sfp_page_config_artikel_icoon( 'links' ) . '</button>'
            . '<button type="button" class="sfp-gids__volgende" aria-label="' . esc_attr( $t( 'volgende' ) ) . '" aria-controls="sfp-gids-strook">' . sfp_page_config_artikel_icoon( 'rechts' ) . '</button></span>';
    }
    return '<nav class="sfp-gids' . ( $slider ? ' sfp-gids--slider' : '' ) . '" aria-labelledby="sfp-gids-kop" data-sfp-gids>'
        . '<div class="sfp-gids__kop">' . $kop_label . '<small>' . esc_html( implode( ' • ', $tel ) ) . '</small>' . $pijlen . $knop . '</div>'
        . '<div class="sfp-gids__inhoud">'
        . ( $artikelen ? '<div class="sfp-gids__lijst' . ( $slider ? ' sfp-gids__lijst--slider' : '' ) . '" id="sfp-gids-strook">' . $lijst . '</div>' : '' )
        . sfp_page_config_gids_begrippen( $term, $begrippen )
        . '</div></nav>'
        . sfp_page_config_gids_zonder_script();
}

/**
 * Zonder script blijft de kaart open en verdwijnt de knop Toon.
 *
 * @return string
 */
function sfp_page_config_gids_zonder_script() {
    return '<noscript><style>.sfp-gids .sfp-gids__inhoud{display:grid!important}.sfp-gids .sfp-gids__knop{display:none!important}</style></noscript>';
}

/**
 * De regel met begrippen onder de kaart: hoogstens acht, daarna een link
 * naar de woordenlijst van de pijler.
 *
 * @param  WP_Term   $term      Categorie.
 * @param  WP_Post[] $begrippen Begrippen.
 * @return string
 */
function sfp_page_config_gids_begrippen( $term, array $begrippen ) {
    if ( ! $begrippen ) {
        return '';
    }
    $t     = 'sfp_page_config_artikel_tekst';
    $links = '';
    foreach ( array_slice( $begrippen, 0, 8 ) as $b ) {
        $links .= '<a href="' . esc_url( get_permalink( $b ) ) . '">' . esc_html( wp_strip_all_tags( get_the_title( $b ) ) ) . '</a>';
    }
    $alle = '';
    if ( count( $begrippen ) > 8 ) {
        $url = sfp_page_config_pijler_woordenlijst_url( $term );
        if ( '' !== $url ) {
            $alle = '<a class="sfp-gids__alle" href="' . esc_url( $url ) . '">' . esc_html( sprintf( $t( 'alle_begrippen' ), count( $begrippen ) ) ) . '</a>';
        }
    }
    return '<p class="sfp-gids__rij"><span class="sfp-blok-label">' . esc_html( $t( 'begrippen' ) ) . '</span>' . $links . $alle . '</p>';
}

/**
 * Zet de kaart direct na de lead: na het eerste stuk lopende tekst, of
 * bovenaan als de tekst niet met een alinea begint.
 *
 * @param  string $html  Getoonde inhoud.
 * @param  string $kaart Kaart.
 * @return string
 */
function sfp_page_config_gids_na_lead( $html, $kaart ) {
    if ( '' === $kaart ) {
        return $html;
    }
    foreach ( sfp_page_config_artikel_bovenlaag( $html ) as $deel ) {
        if ( 'neutraal' === $deel['soort'] ) {
            continue;
        }
        if ( 'tekst' === $deel['soort'] ) {
            return substr( $html, 0, $deel['eind'] ) . $kaart . substr( $html, $deel['eind'] );
        }
        break;
    }
    return $kaart . $html;
}

/* =========================================================================
 * De promokaart per vorm
 * ====================================================================== */

/**
 * Welke kaart een vorm toont: '' (de kaart van de site), 'tweede' of 'geen'.
 * Een tool toont standaard geen kaart (besluit O9).
 *
 * @param  string $vorm artikel, pijler, begrip of tool.
 * @return string
 */
function sfp_page_config_promo_keuze( $vorm ) {
    $waarde = (string) sfp_page_config_get_setting( 'promo_vorm_' . $vorm, '' );
    if ( '' === $waarde && 'tool' === $vorm ) {
        return 'geen';
    }
    return in_array( $waarde, array( 'tweede', 'geen' ), true ) ? $waarde : '';
}

/**
 * De kaart Zelftest van een pijler.
 *
 * @param  WP_Post $tool    De tool.
 * @param  string  $variant zij of inline.
 * @return string
 */
function sfp_page_config_promo_zelftest( $tool, $variant ) {
    $t      = 'sfp_page_config_artikel_tekst';
    $tekst  = has_excerpt( $tool ) ? get_the_excerpt( $tool ) : '';
    return '<aside class="sfp-art-promo sfp-art-promo--' . ( 'inline' === $variant ? 'inline' : 'zij' ) . ' sfp-art-promo--test" aria-label="' . esc_attr( $t( 'zelftest' ) ) . '">'
        . '<span class="sfp-art-promo__test" aria-hidden="true">' . sfp_page_config_artikel_icoon( 'test' ) . '</span><div>'
        . '<span class="sfp-blok-label">' . esc_html( $t( 'zelftest' ) ) . '</span>'
        . '<b>' . esc_html( wp_strip_all_tags( get_the_title( $tool ) ) ) . '</b>'
        . ( '' !== $tekst ? '<p>' . esc_html( wp_strip_all_tags( $tekst ) ) . '</p>' : '' )
        . '<a class="sfp-pijl" href="' . esc_url( get_permalink( $tool ) ) . '">' . esc_html( $t( 'doe_zelftest' ) ) . '</a>'
        . '</div></aside>';
}

/* =========================================================================
 * Gegevens voor Google
 * ====================================================================== */

add_action( 'wp_head', 'sfp_page_config_gids_schema', 30 );

/**
 * Vul de Article van SureRank aan voor een pijler: dezelfde @id, met de
 * artikelen (hasPart), de begrippen (mentions), de bronnen (citation) en de
 * wijzigingsdatum. Alle artikelen staan erin, ook die achter de slider.
 */
function sfp_page_config_gids_schema() {
    if ( ! is_singular( 'post' ) || ! sfp_page_config_pijler_aan() ) {
        return;
    }
    $gids = get_queried_object();
    $term = $gids instanceof WP_Post ? sfp_page_config_is_gids( $gids ) : null;
    if ( ! $term ) {
        return;
    }
    $delen = array();
    foreach ( sfp_page_config_pijler_artikelen( $term, $gids, true ) as $p ) {
        $delen[] = array(
            '@type'    => 'Article',
            'headline' => wp_strip_all_tags( get_the_title( $p ) ),
            'url'      => get_permalink( $p ),
        );
    }
    $noemt = array();
    foreach ( sfp_page_config_pijler_begrippen( $term->term_id ) as $b ) {
        $noemt[] = array(
            '@type' => 'DefinedTerm',
            'name'  => wp_strip_all_tags( get_the_title( $b ) ),
            'url'   => get_permalink( $b ),
        );
    }
    $bronnen = array();
    foreach ( sfp_page_config_artikel_bronnen( $gids->post_content ) as $bron ) {
        $tekst = trim( html_entity_decode( wp_strip_all_tags( $bron ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        if ( '' !== $tekst ) {
            $bronnen[] = $tekst;
        }
    }
    $gegevens = array(
        '@context'     => 'https://schema.org',
        '@type'        => 'Article',
        '@id'          => get_permalink( $gids ) . '#article',
        'dateModified' => wp_date( 'c', (int) get_post_timestamp( $gids, 'modified' ) ),
    );
    if ( $delen ) {
        $gegevens['hasPart'] = $delen;
    }
    if ( $noemt ) {
        $gegevens['mentions'] = $noemt;
    }
    if ( $bronnen ) {
        $gegevens['citation'] = $bronnen;
    }
    echo '<script type="application/ld+json" id="sfp-pijler-schema">' . wp_json_encode( $gegevens, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON uit eigen gegevens.
}

/* =========================================================================
 * Controle
 * ====================================================================== */

/**
 * Tel de woorden van de lead: het eerste stuk lopende tekst.
 *
 * @param  string $content Ruwe inhoud.
 * @return int
 */
function sfp_page_config_lead_woorden( $content ) {
    foreach ( parse_blocks( (string) $content ) as $blok ) {
        if ( empty( $blok['blockName'] ) ) {
            continue;
        }
        if ( 'core/paragraph' !== $blok['blockName'] ) {
            return 0;
        }
        $tekst = trim( wp_strip_all_tags( $blok['innerHTML'] ) );
        if ( '' === $tekst ) {
            continue;
        }
        return count( preg_split( '/\s+/u', $tekst, -1, PREG_SPLIT_NO_EMPTY ) );
    }
    return 0;
}

/**
 * De meldingen voor een pijler, in hetzelfde formaat als de controle van
 * SFP Beheer.
 *
 * @param  WP_Post $post Bericht.
 * @return array
 */
function sfp_page_config_pijler_controle( $post ) {
    $meldingen = array();
    if ( 'pijler' !== sfp_page_config_vorm( $post ) ) {
        return $meldingen;
    }
    $term = sfp_page_config_gids_term( $post );
    if ( ! $term ) {
        $meldingen[] = array(
            'code'  => 'pijler-categorie',
            'kort'  => 'Pijler zonder categorie',
            'tekst' => 'Deze pijler hoort bij geen categorie. Kies in het paneel Deze pagina van welke categorie dit de gids is.',
            'actie' => 'editor',
        );
        return $meldingen;
    }
    $gids = sfp_page_config_gids_van_term( $term->term_id );
    if ( 'publish' === $post->post_status && $gids && (int) $gids !== (int) $post->ID ) {
        $meldingen[] = array(
            'code'  => 'pijler-dubbel',
            'kort'  => 'Categorie heeft al een gids',
            'tekst' => 'De categorie ' . $term->name . ' heeft al een gids: ' . get_the_title( $gids ) . '. Deze pijler wordt daarom niet getoond op het adres van de categorie.',
            'actie' => 'editor',
        );
    }
    $woorden = sfp_page_config_lead_woorden( $post->post_content );
    if ( $woorden < 50 || $woorden > 80 ) {
        $meldingen[] = array(
            'code'  => 'pijler-lead',
            'kort'  => 0 === $woorden ? 'Geen lead' : 'Lead van ' . $woorden . ' woorden',
            'tekst' => 0 === $woorden ? 'De pijler begint niet met een alinea. De eerste alinea is het directe antwoord: 50 tot 80 woorden.' : 'De eerste alinea heeft ' . $woorden . ' woorden. De norm is 50 tot 80: het directe antwoord op de vraag van de pijler.',
            'actie' => 'editor',
        );
    }
    // Elk artikel van de pijler heeft een verwijzing in de tekst (niet op een
    // hoofdpijler: die verwijst naar zijn pijlers).
    $kinderen = get_term_children( $term->term_id, 'category' );
    if ( ! ( is_array( $kinderen ) && $kinderen ) ) {
        $genoemd = array();
        foreach ( parse_blocks( (string) $post->post_content ) as $blok ) {
            sfp_page_config_pijler_lees_ook_ids( $blok, $genoemd );
        }
        $missen = array();
        foreach ( sfp_page_config_pijler_artikelen( $term ) as $p ) {
            if ( ! in_array( (int) $p->ID, $genoemd, true ) ) {
                $missen[] = wp_strip_all_tags( get_the_title( $p ) );
            }
        }
        if ( $missen ) {
            $meldingen[] = array(
                'code'  => 'pijler-verwijzing',
                'kort'  => 1 === count( $missen ) ? 'Eén artikel zonder verwijzing' : count( $missen ) . ' artikelen zonder verwijzing',
                'tekst' => ( 1 === count( $missen ) ? 'Het artikel ' . $missen[0] . ' hoort bij deze pijler, maar de tekst verwijst er nog niet naar.' : 'Deze artikelen horen bij de pijler, maar de tekst verwijst er nog niet naar: ' . implode( ', ', array_slice( $missen, 0, 5 ) ) . ( count( $missen ) > 5 ? ' en nog ' . ( count( $missen ) - 5 ) : '' ) . '.' ) . ' Een link of een blok Lees ook is genoeg.',
                'actie' => 'negeer',
            );
        }
    }
    if ( ! sfp_page_config_pijler_artikelen( $term, null, true ) ) {
        $meldingen[] = array(
            'code'  => 'pijler-leeg',
            'kort'  => 'Nog geen artikelen in de pijler',
            'tekst' => 'In de categorie ' . $term->name . ' staat nog geen artikel. De kaart In this guide verschijnt zodra er een artikel of begrip bij hoort.',
            'actie' => 'negeer',
        );
    }
    return $meldingen;
}
