<?php
/**
 * SFP Page Config - Vervolg per pijler
 *
 * Onder elk artikel staat het vervolg van zijn pijler: wat de lezer hierna
 * kan doen. Tot en met 2.12.0 was dat een Astra-layout aan de actie
 * `sfp_artikel_vervolg`, met een weergaveregel per pijler. Vanaf 3.0.0 beheert
 * de plugin het zelf (besluit Stephan, 10 oktober 2026):
 *
 *   - elk vervolg is een eigen bericht van het type `sfp_vervolg`, met het
 *     blok Vervolg erin. Je bewerkt het in de gewone editor, dus het ziet er
 *     daar uit zoals op de site;
 *   - de pijler (categorie) onthoudt welk vervolg erbij hoort, in de termmeta
 *     `sfp_vervolg_post`;
 *   - een artikel krijgt het vervolg van zijn diepste categorie, of anders
 *     van de eerste bovenliggende categorie die er een heeft.
 *
 * Een bestaande Astra-layout neem je in SFP Beheer over met één klik: de
 * inhoud gaat naar een vervolg van de plugin en de layout wordt een concept.
 * Niets wordt verwijderd.
 *
 * Terugdraaien: dit bestand uit de lijst in sfp-page-config.php halen en de
 * Astra-layout weer publiceren.
 *
 * @package SFP_Page_Config
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'sfp_page_config_vervolg_registreer' );

/**
 * Registreer het berichttype. Niet openbaar: het heeft geen eigen pagina op
 * de site en staat niet in het menu. Wel te bewerken in de editor.
 */
function sfp_page_config_vervolg_registreer() {
    register_post_type(
        'sfp_vervolg',
        array(
            'labels'              => array(
                'name'          => 'Vervolg per pijler',
                'singular_name' => 'Vervolg',
                'edit_item'     => 'Vervolg bewerken',
                'add_new_item'  => 'Vervolg maken',
            ),
            'public'              => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false,
            'show_in_rest'        => true,
            'rest_base'           => 'sfp-vervolg',
            'capability_type'     => 'page',
            'map_meta_cap'        => true,
            'supports'            => array( 'title', 'editor', 'revisions' ),
            'template'            => array(
                array(
                    'sfp/vervolg',
                    array(),
                    array(
                        array( 'sfp/vervolg-vlak', array() ),
                    ),
                ),
            ),
        )
    );

    register_term_meta(
        'category',
        'sfp_vervolg_post',
        array(
            'type'              => 'integer',
            'single'            => true,
            'sanitize_callback' => 'absint',
            'show_in_rest'      => false,
        )
    );
}

/**
 * Het vervolg dat bij een pijler hoort.
 *
 * @param  int $term_id Categorie.
 * @return WP_Post|null Het bericht, of null als de pijler geen vervolg heeft.
 */
function sfp_page_config_vervolg_van_pijler( $term_id ) {
    $post_id = (int) get_term_meta( (int) $term_id, 'sfp_vervolg_post', true );
    if ( ! $post_id ) {
        return null;
    }
    $post = get_post( $post_id );
    if ( ! $post || 'sfp_vervolg' !== $post->post_type || 'trash' === $post->post_status ) {
        return null;
    }
    return $post;
}

/**
 * De pijler van een artikel: de diepste categorie, zonder de
 * standaardcategorie als er een andere is. Zelfde keuze als de pijlerregel
 * boven het artikel.
 *
 * @param  WP_Post|int $post Bericht.
 * @return WP_Term|null
 */
function sfp_page_config_vervolg_pijler( $post ) {
    $termen = get_the_terms( $post, 'category' );
    if ( ! is_array( $termen ) || ! $termen ) {
        return null;
    }
    $standaard = (int) get_option( 'default_category' );
    $keuze     = null;
    $diepte    = -1;
    foreach ( $termen as $term ) {
        if ( (int) $term->term_id === $standaard && count( $termen ) > 1 ) {
            continue;
        }
        $d = count( get_ancestors( $term->term_id, 'category' ) );
        if ( $d > $diepte ) {
            $diepte = $d;
            $keuze  = $term;
        }
    }
    return $keuze;
}

/**
 * Het vervolg voor een artikel: dat van zijn pijler, anders van de eerste
 * bovenliggende categorie met een gepubliceerd vervolg.
 *
 * @param  WP_Post|int $post Bericht.
 * @return WP_Post|null
 */
function sfp_page_config_vervolg_voor( $post ) {
    $pijler = sfp_page_config_vervolg_pijler( $post );
    if ( ! $pijler ) {
        return null;
    }
    // Een Astra-layout die onder alle artikelen hangt, verzorgt het vervolg
    // zelf. Anders staat het er twee keer.
    if ( sfp_page_config_vervolg_astra_dekt( 0 ) ) {
        return null;
    }
    $kandidaten = array_merge( array( (int) $pijler->term_id ), array_map( 'intval', get_ancestors( $pijler->term_id, 'category' ) ) );
    foreach ( $kandidaten as $i => $term_id ) {
        $vervolg = sfp_page_config_vervolg_van_pijler( $term_id );
        if ( $vervolg && 'publish' === $vervolg->post_status && '' !== trim( $vervolg->post_content ) ) {
            return $vervolg;
        }
        // De eigen pijler heeft nog een Astra-layout: die toont het vervolg.
        // Het vervolg van de pijler erboven komt er dan niet bij.
        if ( 0 === $i && sfp_page_config_vervolg_astra_dekt( $term_id ) ) {
            return null;
        }
    }
    return null;
}

/**
 * Verzorgt een gepubliceerde Astra-layout het vervolg nog?
 *
 * @param  int $term_id Pijler; 0 vraagt naar een layout die onder alle
 *                      artikelen hangt (een regel die niet per categorie is).
 * @return bool
 */
function sfp_page_config_vervolg_astra_dekt( $term_id ) {
    static $layouts = null;
    if ( null === $layouts ) {
        $layouts = get_transient( 'sfp_pc_vervolg_layouts' );
        if ( ! is_array( $layouts ) ) {
            $layouts = sfp_page_config_vervolg_astra_layouts();
            set_transient( 'sfp_pc_vervolg_layouts', $layouts, DAY_IN_SECONDS );
        }
    }
    foreach ( $layouts as $layout ) {
        if ( 0 === (int) $term_id ? ! empty( $layout['algemeen'] ) : in_array( (int) $term_id, $layout['pijlers'], true ) ) {
            return true;
        }
    }
    return false;
}

/**
 * De inhoud van een vervolg als HTML.
 *
 * @param  WP_Post $vervolg Bericht van het type sfp_vervolg.
 * @return string
 */
function sfp_page_config_vervolg_html( $vervolg ) {
    $html = do_blocks( $vervolg->post_content );
    $html = do_shortcode( $html );
    return trim( (string) $html );
}

add_action( 'sfp_artikel_vervolg', 'sfp_page_config_vervolg_toon' );

/**
 * Toon het vervolg onder een artikel.
 *
 * @param WP_Post $post Het artikel.
 */
function sfp_page_config_vervolg_toon( $post ) {
    $vervolg = $post instanceof WP_Post ? sfp_page_config_vervolg_voor( $post ) : null;
    if ( ! $vervolg ) {
        return;
    }
    echo sfp_page_config_vervolg_html( $vervolg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- uitvoer van blokken.
}

/* =========================================================================
 * Beheer: lijst van pijlers, maken, koppelen, overnemen
 * ====================================================================== */

/**
 * Astra-layouts die aan de actie sfp_artikel_vervolg hangen, met de pijlers
 * uit hun weergaveregel.
 *
 * @return array<int, array{id: int, titel: string, pijlers: int[], algemeen: bool}>
 */
function sfp_page_config_vervolg_astra_layouts() {
    if ( ! post_type_exists( 'astra-advanced-hook' ) ) {
        return array();
    }
    $alle = get_posts(
        array(
            'post_type'        => 'astra-advanced-hook',
            'post_status'      => 'publish',
            'posts_per_page'   => 100,
            'suppress_filters' => true,
        )
    );
    // Astra bewaart een eigen actie als "custom_hook" met de naam in een
    // tweede veld. Een layout kan ook rechtstreeks aan de actie hangen.
    $posts = array();
    foreach ( $alle as $post ) {
        $actie = (string) get_post_meta( $post->ID, 'ast-advanced-hook-action', true );
        $eigen = (string) get_post_meta( $post->ID, 'ast-custom-hook', true );
        if ( 'sfp_artikel_vervolg' === $actie || ( 'custom_hook' === $actie && 'sfp_artikel_vervolg' === $eigen ) ) {
            $posts[] = $post;
        }
    }
    $uit = array();
    foreach ( $posts as $post ) {
        $pijlers = array();
        $regel   = get_post_meta( $post->ID, 'ast-advanced-hook-location', true );
        $los     = is_array( $regel ) && isset( $regel['specific'] ) && is_array( $regel['specific'] ) ? $regel['specific'] : array();
        // Een regel die niet (alleen) per onderdeel is, zoals "alle berichten".
        $regels   = is_array( $regel ) && isset( $regel['rule'] ) && is_array( $regel['rule'] ) ? $regel['rule'] : array();
        $algemeen = (bool) array_diff( array_map( 'strval', $regels ), array( 'specifics' ) );
        foreach ( $los as $doel ) {
            // Astra bewaart een categorie als "tax-<id>" of "tax-<id>-single-category".
            if ( is_string( $doel ) && preg_match( '/^tax-(\d+)/', $doel, $m ) ) {
                $term = get_term( (int) $m[1], 'category' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $pijlers[] = (int) $term->term_id;
                }
            }
        }
        $uit[] = array(
            'id'      => (int) $post->ID,
            'titel'   => html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
            'pijlers' => array_values( array_unique( $pijlers ) ),
            'algemeen' => $algemeen,
        );
    }
    return $uit;
}

/**
 * Korte omschrijving van een vervolg: hoeveel vlakken en hun labels.
 *
 * @param  WP_Post $vervolg Bericht.
 * @return array{vlakken: int, labels: string[]}
 */
function sfp_page_config_vervolg_omschrijving( $vervolg ) {
    $labels  = array();
    $vlakken = 0;
    $zoek    = function ( $blokken ) use ( &$zoek, &$labels, &$vlakken ) {
        foreach ( $blokken as $blok ) {
            if ( 'sfp/vervolg-vlak' === $blok['blockName'] ) {
                ++$vlakken;
                $label = isset( $blok['attrs']['label'] ) ? trim( wp_strip_all_tags( html_entity_decode( (string) $blok['attrs']['label'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) : '';
                if ( '' === $label ) {
                    foreach ( $blok['innerBlocks'] as $binnen ) {
                        if ( 'core/heading' === $binnen['blockName'] ) {
                            $label = trim( wp_strip_all_tags( $binnen['innerHTML'] ) );
                            break;
                        }
                    }
                }
                if ( '' !== $label ) {
                    $labels[] = $label;
                }
            }
            if ( ! empty( $blok['innerBlocks'] ) ) {
                $zoek( $blok['innerBlocks'] );
            }
        }
    };
    $zoek( parse_blocks( $vervolg->post_content ) );
    return array(
        'vlakken' => $vlakken,
        'labels'  => $labels,
    );
}

/**
 * De pijlers van de site met hun vervolg, voor het beheer.
 *
 * @return array<int, array>
 */
function sfp_page_config_vervolg_overzicht() {
    $termen = get_terms(
        array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
            'orderby'    => 'name',
        )
    );
    if ( is_wp_error( $termen ) ) {
        return array();
    }
    $standaard = (int) get_option( 'default_category' );
    $layouts   = sfp_page_config_vervolg_astra_layouts();
    $uit       = array();

    // Ouders eerst, met hun kinderen eronder.
    $per_ouder = array();
    foreach ( $termen as $term ) {
        $per_ouder[ (int) $term->parent ][] = $term;
    }
    $loop = function ( $ouder, $niveau ) use ( &$loop, &$uit, $per_ouder, $standaard, $layouts ) {
        if ( empty( $per_ouder[ $ouder ] ) ) {
            return;
        }
        foreach ( $per_ouder[ $ouder ] as $term ) {
            $heeft_kind = ! empty( $per_ouder[ (int) $term->term_id ] );
            if ( (int) $term->term_id === $standaard && 0 === (int) $term->count && ! $heeft_kind ) {
                continue;
            }
            $vervolg = sfp_page_config_vervolg_van_pijler( $term->term_id );
            $rij     = array(
                'id'       => (int) $term->term_id,
                'naam'     => html_entity_decode( $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
                'niveau'   => $niveau,
                'artikelen' => (int) $term->count,
                'vervolg'  => null,
                'astra'    => null,
            );
            if ( $vervolg ) {
                $oms            = sfp_page_config_vervolg_omschrijving( $vervolg );
                $rij['vervolg'] = array(
                    'id'      => (int) $vervolg->ID,
                    'status'  => $vervolg->post_status,
                    'vlakken' => $oms['vlakken'],
                    'labels'  => $oms['labels'],
                    'bewerk'  => (string) get_edit_post_link( $vervolg->ID, 'raw' ),
                    // Voorbeeld in het beheer: zonder scripts of gebeurtenissen.
                    'html'    => 'publish' === $vervolg->post_status ? wp_kses_post( sfp_page_config_vervolg_html( $vervolg ) ) : '',
                );
            }
            foreach ( $layouts as $layout ) {
                if ( in_array( (int) $term->term_id, $layout['pijlers'], true ) ) {
                    $rij['astra'] = array(
                        'id'    => $layout['id'],
                        'titel' => $layout['titel'],
                    );
                    break;
                }
            }
            $uit[] = $rij;
            $loop( (int) $term->term_id, $niveau + 1 );
        }
    };
    $loop( 0, 0 );
    return $uit;
}

/**
 * Maak een vervolg voor een pijler, of geef het bestaande terug.
 *
 * @param  int    $term_id Categorie.
 * @param  string $inhoud  Blokinhoud; leeg voor een nieuw, leeg vervolg.
 * @param  string $status  publish of draft.
 * @return int|WP_Error    Het ID van het vervolg.
 */
function sfp_page_config_vervolg_maak( $term_id, $inhoud = '', $status = 'draft' ) {
    $term = get_term( (int) $term_id, 'category' );
    if ( ! $term || is_wp_error( $term ) ) {
        return new WP_Error( 'sfp_vervolg_geen_pijler', 'Deze pijler bestaat niet.', array( 'status' => 404 ) );
    }
    $bestaand = sfp_page_config_vervolg_van_pijler( $term->term_id );
    if ( $bestaand ) {
        return (int) $bestaand->ID;
    }
    if ( '' === trim( $inhoud ) ) {
        $inhoud = '<!-- wp:sfp/vervolg --><!-- wp:sfp/vervolg-vlak --><!-- wp:heading {"level":3} -->' . "\n"
            . '<h3 class="wp-block-heading"></h3>' . "\n"
            . '<!-- /wp:heading --><!-- wp:paragraph -->' . "\n"
            . '<p></p>' . "\n"
            . '<!-- /wp:paragraph --><!-- wp:buttons -->' . "\n"
            . '<div class="wp-block-buttons"><!-- wp:button -->' . "\n"
            . '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"></a></div>' . "\n"
            . '<!-- /wp:button --></div>' . "\n"
            . '<!-- /wp:buttons --><!-- /wp:sfp/vervolg-vlak --><!-- /wp:sfp/vervolg -->';
    }
    $post_id = wp_insert_post(
        array(
            'post_type'    => 'sfp_vervolg',
            'post_status'  => 'publish' === $status ? 'publish' : 'draft',
            'post_title'   => 'Vervolg: ' . html_entity_decode( $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
            'post_content' => wp_slash( $inhoud ),
        ),
        true
    );
    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }
    update_term_meta( $term->term_id, 'sfp_vervolg_post', (int) $post_id );
    return (int) $post_id;
}

/**
 * Neem een Astra-layout over als vervolg van een pijler: de inhoud gaat naar
 * een vervolg van de plugin en de layout wordt een concept, zodat het vervolg
 * niet dubbel onder het artikel staat. De layout blijft bestaan.
 *
 * @param  int $layout_id De Astra-layout.
 * @param  int $term_id   De pijler.
 * @return int|WP_Error   Het ID van het vervolg.
 */
function sfp_page_config_vervolg_neem_over( $layout_id, $term_id ) {
    $layout = get_post( (int) $layout_id );
    if ( ! $layout || 'astra-advanced-hook' !== $layout->post_type ) {
        return new WP_Error( 'sfp_vervolg_geen_layout', 'Deze layout bestaat niet.', array( 'status' => 404 ) );
    }
    if ( sfp_page_config_vervolg_van_pijler( $term_id ) ) {
        return new WP_Error( 'sfp_vervolg_bestaat', 'Deze pijler heeft al een vervolg in de plugin.', array( 'status' => 409 ) );
    }
    $post_id = sfp_page_config_vervolg_maak( $term_id, $layout->post_content, 'publish' );
    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }
    update_post_meta( $post_id, '_sfp_vervolg_uit_layout', (int) $layout->ID );

    // Hangt de layout aan meer pijlers, dan krijgen die hetzelfde vervolg.
    // Anders verdwijnt het daar zodra de layout een concept wordt.
    foreach ( sfp_page_config_vervolg_astra_layouts() as $bekend ) {
        if ( (int) $bekend['id'] !== (int) $layout->ID ) {
            continue;
        }
        foreach ( $bekend['pijlers'] as $andere ) {
            if ( (int) $andere !== (int) $term_id && ! sfp_page_config_vervolg_van_pijler( $andere ) ) {
                update_term_meta( (int) $andere, 'sfp_vervolg_post', (int) $post_id );
            }
        }
        // Een layout die ook onder andere artikelen hangt, blijft staan: de
        // plugin toont zelf niets zolang die layout gepubliceerd is.
        if ( ! empty( $bekend['algemeen'] ) ) {
            return (int) $post_id;
        }
    }

    $klaar = wp_update_post(
        array(
            'ID'          => $layout->ID,
            'post_status' => 'draft',
        ),
        true
    );
    if ( is_wp_error( $klaar ) ) {
        return $klaar;
    }
    return (int) $post_id;
}

add_action( 'save_post_astra-advanced-hook', 'sfp_page_config_vervolg_layouts_wis' );
add_action( 'deleted_post', 'sfp_page_config_vervolg_layouts_wis' );
add_action( 'trashed_post', 'sfp_page_config_vervolg_layouts_wis' );
add_action( 'untrashed_post', 'sfp_page_config_vervolg_layouts_wis' );

/**
 * De bewaarde lijst van Astra-layouts met een vervolg is niet meer actueel.
 */
function sfp_page_config_vervolg_layouts_wis() {
    delete_transient( 'sfp_pc_vervolg_layouts' );
}

add_action( 'before_delete_post', 'sfp_page_config_vervolg_ontkoppel' );

/**
 * Een vervolg dat definitief wordt verwijderd, hoort niet meer bij een pijler.
 * In de prullenbak blijft de koppeling staan, zodat terugzetten genoeg is.
 *
 * @param int $post_id Bericht.
 */
function sfp_page_config_vervolg_ontkoppel( $post_id ) {
    if ( 'sfp_vervolg' !== get_post_type( $post_id ) ) {
        return;
    }
    $termen = get_terms(
        array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
            'fields'     => 'ids',
            'meta_key'   => 'sfp_vervolg_post', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- zeldzame actie.
            'meta_value' => (int) $post_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        )
    );
    if ( is_array( $termen ) ) {
        foreach ( $termen as $term_id ) {
            delete_term_meta( (int) $term_id, 'sfp_vervolg_post' );
        }
    }
}

add_filter( 'allowed_block_types_all', 'sfp_page_config_vervolg_blokken', 10, 2 );

/**
 * In een vervolg staan alleen het blok Vervolg en wat daarin hoort.
 *
 * @param  bool|string[]           $toegestaan Toegestane blokken.
 * @param  WP_Block_Editor_Context $context    Context van de editor.
 * @return bool|string[]
 */
function sfp_page_config_vervolg_blokken( $toegestaan, $context ) {
    if ( empty( $context->post ) || 'sfp_vervolg' !== $context->post->post_type ) {
        return $toegestaan;
    }
    return array( 'sfp/vervolg', 'sfp/vervolg-vlak', 'core/heading', 'core/paragraph', 'core/buttons', 'core/button', 'core/list', 'core/list-item', 'sfp/zelftest-cta' );
}
