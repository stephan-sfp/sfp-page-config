<?php
/**
 * SFP Page Config - Het paneel Deze pagina in de blokeditor
 *
 * Vervangt in de blokeditor het metavak SFP Page Config (wijzigingen 9, 10
 * en 19 uit het ontwerp van SFP Beheer). Het paneel staat in de rechterkolom
 * bij de instellingen van het bericht en toont alleen wat op deze pagina iets
 * doet: de soort pagina, de knop onderaan, de training, de extra's, wat bij
 * een artikel hoort, het beeld om te delen, de uitsnede van de foto en de
 * voorbeeldlink.
 *
 * In de klassieke editor blijft het oude metavak staan.
 *
 * Daarnaast:
 *   - alle gegevens per pagina zijn via de REST API te lezen en te zetten
 *     (backlog PC-11), zodat het paneel ze kan bewaren;
 *   - in de editor staat op een artikel wat er vanzelf bij komt (kopkaart
 *     boven, auteurskaart, vervolg en voetregels onder), zoals op de site
 *     (wijziging 15).
 *
 * @package SFP_Page_Config
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Gegevens per pagina via REST (PC-11)
 * ====================================================================== */

add_action( 'init', 'sfp_page_config_editor_meta', 11 );

/**
 * Registreer de gegevens per pagina die het paneel leest en bewaart.
 *
 * sfp_page_type, sfp_cta_text en sfp_cta_href staan al in metabox.php.
 * De cursusdata worden alleen in SFP Beheer, Cursusdata, bewerkt en staan
 * hier niet bij.
 */
function sfp_page_config_editor_meta() {
    $mag = function ( $toegestaan, $sleutel, $post_id ) {
        return current_user_can( 'edit_post', $post_id );
    };
    $vlag = function ( $waarde ) {
        return '1' === (string) $waarde || true === $waarde ? '1' : '';
    };
    $procent = function ( $waarde ) {
        return min( 100, max( 0, (int) $waarde ) );
    };

    foreach ( sfp_page_config_post_types() as $type ) {
        register_post_meta(
            $type,
            'training_naam',
            array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback'     => $mag,
            )
        );
        foreach ( array( 'sfp_longread', 'sfp_whatsapp' ) as $sleutel ) {
            register_post_meta(
                $type,
                $sleutel,
                array(
                    'type'              => 'string',
                    'single'            => true,
                    'show_in_rest'      => true,
                    'sanitize_callback' => $vlag,
                    'auth_callback'     => $mag,
                )
            );
        }
        foreach ( array( 'sfp_hero_focal_x', 'sfp_hero_focal_y' ) as $sleutel ) {
            register_post_meta(
                $type,
                $sleutel,
                array(
                    'type'              => 'integer',
                    'single'            => true,
                    'default'           => 50,
                    'show_in_rest'      => true,
                    'sanitize_callback' => $procent,
                    'auth_callback'     => $mag,
                )
            );
        }
        register_post_meta(
            $type,
            'sfp_cta_pagina',
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

add_filter( 'add_post_metadata', 'sfp_page_config_editor_leeg_is_weg', 10, 4 );
add_filter( 'update_post_metadata', 'sfp_page_config_editor_leeg_is_weg', 10, 4 );

/**
 * De blokeditor stuurt bij opslaan alle gegevens van de pagina mee, ook wat
 * leeg is. Een lege waarde (of de standaard) bewaren we niet: dan staat er
 * geen rij, zoals voor 3.1.0. Zo blijft "niet ingesteld" herkenbaar.
 *
 * @param  null|bool $kort    Kortsluiting.
 * @param  int       $post_id Bericht.
 * @param  string    $sleutel Sleutel.
 * @param  mixed     $waarde  Waarde.
 * @return null|bool
 */
function sfp_page_config_editor_leeg_is_weg( $kort, $post_id, $sleutel, $waarde ) {
    $leeg = array(
        'sfp_page_type'    => array( '' ),
        'sfp_cta_text'     => array( '' ),
        'sfp_cta_href'     => array( '' ),
        'training_naam'    => array( '' ),
        'sfp_longread'     => array( '' ),
        'sfp_whatsapp'     => array( '' ),
        'sfp_cta_pagina'   => array( '', '0' ),
        'sfp_hero_focal_x' => array( '50' ),
        'sfp_hero_focal_y' => array( '50' ),
    );
    if ( null !== $kort || ! isset( $leeg[ $sleutel ] ) || is_array( $waarde ) || is_object( $waarde ) ) {
        return $kort;
    }
    if ( ! in_array( trim( (string) $waarde ), $leeg[ $sleutel ], true ) ) {
        return $kort;
    }
    if ( metadata_exists( 'post', $post_id, $sleutel ) ) {
        delete_post_meta( $post_id, $sleutel );
    }
    return true;
}

add_filter( 'register_meta_args', 'sfp_page_config_editor_soort_args', 10, 4 );

/**
 * sfp_page_type wordt in het paneel gekozen. Schrijven via REST kan alleen
 * wie dit bericht mag bewerken, en alleen een bekende soort (of leeg).
 *
 * @param  array  $args      Argumenten.
 * @param  array  $standaard Standaardargumenten.
 * @param  string $type      Objecttype.
 * @param  string $sleutel   Sleutel.
 * @return array
 */
function sfp_page_config_editor_soort_args( $args, $standaard, $type, $sleutel ) {
    if ( 'post' === $type && 'sfp_page_type' === $sleutel ) {
        $args['sanitize_callback'] = function ( $waarde ) {
            $waarde = sanitize_key( (string) $waarde );
            return in_array( $waarde, sfp_page_config_paginasoorten(), true ) ? $waarde : '';
        };
        $args['auth_callback']     = function ( $toegestaan, $sleutel, $post_id ) {
            return current_user_can( 'edit_post', $post_id );
        };
    }
    return $args;
}

/* =========================================================================
 * Het paneel
 * ====================================================================== */

add_action( 'enqueue_block_editor_assets', 'sfp_page_config_editor_paneel' );

/**
 * Laad het paneel Deze pagina bij de soorten berichten van de plugin.
 */
function sfp_page_config_editor_paneel() {
    $scherm = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $scherm || empty( $scherm->post_type ) || ! in_array( $scherm->post_type, sfp_page_config_post_types(), true ) ) {
        return;
    }

    wp_enqueue_script(
        'sfp-deze-pagina',
        SFP_PAGE_CONFIG_URL . 'assets/editor/deze-pagina.js',
        array( 'wp-plugins', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-api-fetch', 'wp-url', 'wp-blocks', 'wp-block-editor', 'wp-editor', 'wp-edit-post', 'wp-dom-ready' ),
        SFP_PAGE_CONFIG_VERSION,
        true
    );
    wp_enqueue_style(
        'sfp-deze-pagina',
        SFP_PAGE_CONFIG_URL . 'assets/editor/deze-pagina.css',
        array(),
        SFP_PAGE_CONFIG_VERSION
    );
    wp_add_inline_script( 'sfp-deze-pagina', 'window.sfpDezePagina=' . wp_json_encode( sfp_page_config_editor_gegevens( $scherm->post_type ) ) . ';', 'before' );
}

/**
 * Wat het paneel over de site moet weten.
 *
 * @param  string $post_type Soort bericht in de editor.
 * @return array
 */
function sfp_page_config_editor_gegevens( $post_type ) {
    $soorten = array();
    foreach ( sfp_page_config_soorten() as $id => $soort ) {
        $knop = '' !== $id ? sfp_page_config_get_sticky_cta( $id ) : null;
        $doel = '';
        if ( $knop ) {
            if ( 'form' === $knop['doel'] ) {
                $doel = 'het formulier op de pagina';
            } elseif ( 'pagina' === $knop['doel'] ) {
                $pagina = url_to_postid( $knop['href'] );
                $doel   = $pagina ? 'de pagina ' . get_the_title( $pagina ) : 'een pagina van de site';
            } else {
                $doel = $knop['href'];
            }
        }
        $soorten[] = array(
            'id'     => $id,
            'naam'   => $soort['naam'],
            'uitleg' => $soort['uitleg'],
            'tekst'  => $knop ? $knop['text'] : '',
            'doel'   => $knop ? $knop['doel'] : '',
            'naar'   => $doel,
        );
    }

    $artikel_types = function_exists( 'sfp_page_config_artikel_posttypes' ) ? sfp_page_config_artikel_posttypes() : array( 'post' );
    $artikel       = function_exists( 'sfp_page_config_artikel_aan' ) && sfp_page_config_artikel_aan() && in_array( $post_type, $artikel_types, true );

    return array(
        'soorten'        => $soorten,
        'artikel'        => $artikel,
        'paginatype'     => 'page' === $post_type,
        'knop'           => sfp_page_config_onderdeel_aan( 'knop' ),
        'whatsapp'       => sfp_page_config_onderdeel_aan( 'whatsapp' ),
        'cursusdata'     => sfp_page_config_onderdeel_aan( 'cursusdata' ),
        'longread'       => in_array( $post_type, array( 'post', 'page' ), true ) && ! $artikel,
        'affiliate'      => 'post' === $post_type && function_exists( 'sfp_page_config_get_affiliate_layout_id' ) && sfp_page_config_get_affiliate_layout_id() && ! ( function_exists( 'sfp_page_config_artikel_aan' ) && sfp_page_config_artikel_aan() ),
        'pijler'         => function_exists( 'sfp_page_config_pijler_aan' ) && sfp_page_config_pijler_aan(),
        'begrip'         => function_exists( 'sfp_page_config_begrip_aan' ) && sfp_page_config_begrip_aan(),
        'tools'          => function_exists( 'sfp_page_config_tool_aan' ) && sfp_page_config_tool_aan(),
        // Alleen deze alineastijlen in een artikel, begrip of tool (sinds 3.19.0).
        'alineastijlen'  => sfp_page_config_editor_is_vorm( $post_type ) ? sfp_page_config_vorm_alineastijlen() : null,
        'beheer'         => admin_url( 'admin.php?page=sfp-page-config' ),
        'home'           => home_url( '/' ),
    );
}

/* =========================================================================
 * REST: wat het paneel per pagina laat zien
 * ====================================================================== */

add_action( 'rest_api_init', 'sfp_page_config_editor_rest' );

/**
 * GET /sfp/v1/editor/<id>
 */
function sfp_page_config_editor_rest() {
    register_rest_route(
        'sfp/v1',
        '/editor/(?P<id>\d+)',
        array(
            'methods'             => 'GET',
            'permission_callback' => function ( WP_REST_Request $verzoek ) {
                return current_user_can( 'edit_post', (int) $verzoek['id'] );
            },
            'callback'            => function ( WP_REST_Request $verzoek ) {
                $post = get_post( (int) $verzoek['id'] );
                if ( ! $post ) {
                    return new WP_Error( 'sfp_editor_geen_bericht', 'Bericht niet gevonden.', array( 'status' => 404 ) );
                }
                return rest_ensure_response( sfp_page_config_editor_stand( $post ) );
            },
        )
    );
}

/**
 * De stand van één bericht voor het paneel.
 *
 * @param  WP_Post $post Bericht.
 * @return array
 */
function sfp_page_config_editor_stand( WP_Post $post ) {
    $stand = array(
        'cursusdata' => sfp_page_config_editor_cursusdata( $post->ID ),
        'pijler'     => '',
        'onderwerpen' => array(),
        'vervolg'    => '',
        'focus'      => false,
        'voorbeeld'  => null,
        'kop'        => '',
        'voet'       => '',
        'promo'      => false,
        'vorm'       => null,
    );

    // Begrip: gebruikt in en de controle.
    if ( function_exists( 'sfp_page_config_is_begrip' ) && sfp_page_config_is_begrip( $post ) ) {
        $stand['begrip'] = array(
            'gebruikt'  => count( sfp_page_config_begrip_gebruikt_in( $post ) ),
            'meldingen' => sfp_page_config_begrip_controle( $post ),
            // Wat de site vanzelf bij de Definitie zet, zodat de editor het
            // als Komt vanzelf kan tonen (sinds 3.16.0).
            'maker'     => sfp_page_config_editor_begrip_maker( $post ),
            'promo'     => function_exists( 'sfp_page_config_artikel_promokaart' ) ? sfp_page_config_artikel_promokaart( 'zij', $post ) : '',
        );
    }

    // Tool: de beschrijving, de samenvatting en de controle.
    if ( function_exists( 'sfp_page_config_is_tool' ) && sfp_page_config_is_tool( $post ) ) {
        $tekst          = trim( (string) get_post_meta( $post->ID, 'sfp_tool', true ) );
        $uitkomst       = '' !== $tekst ? sfp_page_config_tool_controleer_tekst( $tekst ) : array( 'fouten' => array(), 'let_op' => array(), 'samenvatting' => array() );
        $stand['tool']  = array(
            'aan'          => sfp_page_config_tool_aan(),
            'fouten'       => $uitkomst['fouten'],
            'let_op'       => $uitkomst['let_op'],
            'samenvatting' => $uitkomst['samenvatting'],
            'meldingen'    => sfp_page_config_tool_controle( $post ),
            'soorten'      => array_keys( sfp_page_config_tool_soorten() ),
        );
    }

    // Vorm en pijler.
    if ( 'post' === $post->post_type && function_exists( 'sfp_page_config_pijler_aan' ) && sfp_page_config_pijler_aan() ) {
        $eigen = function_exists( 'sfp_page_config_vervolg_pijler' ) ? sfp_page_config_vervolg_pijler( $post ) : null;
        $vorm  = array(
            'vorm'        => sfp_page_config_vorm( $post ),
            'automatisch' => $eigen ? html_entity_decode( $eigen->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '',
            'adres'       => '',
            'meldingen'   => array(),
        );
        if ( 'pijler' === $vorm['vorm'] ) {
            $vorm['meldingen'] = sfp_page_config_pijler_controle( $post );
            $term              = sfp_page_config_gids_term( $post );
            if ( $term ) {
                $ouder              = $term->parent ? get_term( $term->parent, 'category' ) : null;
                $vorm['onder']      = ( $ouder && ! is_wp_error( $ouder ) ) ? html_entity_decode( $ouder->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '';
                $vorm['artikelen']  = count( sfp_page_config_pijler_artikelen( $term, null, true ) );
                $vorm['begrippen']  = count( sfp_page_config_pijler_begrippen( $term->term_id ) );
            }
            $vorm['zelftests'] = array(
                'site'  => function_exists( 'sfp_zelftest_site_test' ) ? sfp_zelftest_site_test() : '',
                'tests' => array(),
            );
            if ( function_exists( 'sfp_zelftest_tests' ) && sfp_page_config_onderdeel_aan( 'zelftest' ) ) {
                foreach ( sfp_zelftest_tests() as $zt ) {
                    $pakket                       = sfp_zelftest_pakket( $zt );
                    $vorm['zelftests']['tests'][] = array(
                        'test' => $zt,
                        'naam' => $pakket ? (string) sfp_zelftest_tekst( $pakket, 'algemeen.testnaam' ) : $zt,
                    );
                }
            }
            if ( 'publish' === $post->post_status && sfp_page_config_is_gids( $post ) ) {
                $vorm['adres'] = get_permalink( $post );
            }
        }
        $stand['vorm'] = $vorm;
    }

    // Uitsnede van de foto: alleen waar een held met die foto staat.
    $tags           = wp_get_object_terms( $post->ID, 'post_tag', array( 'fields' => 'slugs' ) );
    $stand['focus'] = false !== strpos( (string) $post->post_content, 'lcp-hero-image' )
        || ( is_array( $tags ) && in_array( 'pijler', $tags, true ) )
        || metadata_exists( 'post', $post->ID, 'sfp_hero_focal_x' );

    $link = get_post_meta( $post->ID, '_sfp_voorbeeld', true );
    if ( is_array( $link ) && ! empty( $link['sleutel'] ) && ! empty( $link['tot'] ) && (int) $link['tot'] > time() ) {
        $stand['voorbeeld'] = array(
            'url' => add_query_arg(
                array(
                    'p'             => $post->ID,
                    'sfp_voorbeeld' => $link['sleutel'],
                ),
                home_url( '/' )
            ),
            'tot' => wp_date( 'Y-m-d H:i', (int) $link['tot'] ),
        );
    }

    $artikel_types = function_exists( 'sfp_page_config_artikel_posttypes' ) ? sfp_page_config_artikel_posttypes() : array( 'post' );
    if ( ! function_exists( 'sfp_page_config_artikel_aan' ) || ! sfp_page_config_artikel_aan() || ! in_array( $post->post_type, $artikel_types, true ) ) {
        return $stand;
    }

    $pijler = null;
    if ( function_exists( 'sfp_page_config_vervolg_pijler' ) ) {
        $pijler          = sfp_page_config_vervolg_pijler( $post );
        $stand['pijler'] = $pijler ? html_entity_decode( $pijler->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '';
    }
    $onderwerpen = wp_get_object_terms( $post->ID, 'post_tag', array( 'fields' => 'names' ) );
    $stand['onderwerpen'] = is_array( $onderwerpen ) ? array_values( array_diff( $onderwerpen, array( 'pijler' ) ) ) : array();

    if ( function_exists( 'sfp_page_config_vervolg_voor' ) ) {
        $vervolg = sfp_page_config_vervolg_voor( $post );
        if ( $vervolg ) {
            $naam = '';
            $kandidaten = $pijler ? array_merge( array( (int) $pijler->term_id ), array_map( 'intval', get_ancestors( $pijler->term_id, 'category' ) ) ) : array();
            foreach ( $kandidaten as $term_id ) {
                if ( (int) get_term_meta( $term_id, 'sfp_vervolg_post', true ) === (int) $vervolg->ID ) {
                    $naam = (string) get_term_field( 'name', $term_id, 'category' );
                    break;
                }
            }
            $stand['vervolg'] = '' !== $naam ? 'van de pijler ' . html_entity_decode( $naam, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : 'van de pijler';
        } elseif ( $pijler && function_exists( 'sfp_page_config_vervolg_astra_dekt' ) && ( sfp_page_config_vervolg_astra_dekt( 0 ) || sfp_page_config_vervolg_astra_dekt( (int) $pijler->term_id ) ) ) {
            $stand['vervolg'] = 'uit een Astra-layout';
        }
    }

    $stand['promo'] = function_exists( 'sfp_page_config_artikel_promokaart' ) && '' !== sfp_page_config_artikel_promokaart( 'zij' );

    // Wat er vanzelf om het artikel heen staat, zoals bij de laatste keer opslaan.
    list( $kop, $voet ) = sfp_page_config_editor_omlijsting( $post );
    $stand['kop']       = $kop;
    $stand['voet']      = $voet;

    return $stand;
}

/**
 * De startmomenten van een training, kort.
 *
 * @param  int $post_id Pagina.
 * @return array{groepen: array, verlopen: bool}
 */
function sfp_page_config_editor_cursusdata( $post_id ) {
    $json    = get_post_meta( $post_id, 'sfp_cursusdata', true );
    $data    = $json ? json_decode( (string) $json, true ) : array();
    $groepen = array();
    $laatste = '';
    foreach ( is_array( $data ) ? $data : array() as $i => $sm ) {
        if ( empty( $sm['data'] ) || ! is_array( $sm['data'] ) ) {
            continue;
        }
        $dagen = array();
        foreach ( $sm['data'] as $d ) {
            $f = sfp_page_config_format_datum( $d, 'short' );
            if ( $f ) {
                $dagen[] = $f;
            }
            if ( (string) $d > $laatste ) {
                $laatste = (string) $d;
            }
        }
        if ( ! $dagen ) {
            continue;
        }
        $groepen[] = array(
            'nr'    => $i + 1,
            'dagen' => implode( ' • ', $dagen ),
            'tijd'  => sfp_page_config_format_lestijd( isset( $sm['start'] ) ? $sm['start'] : '', isset( $sm['eind'] ) ? $sm['eind'] : '' ),
        );
    }
    return array(
        'groepen'  => $groepen,
        'verlopen' => '' !== $laatste && $laatste < wp_date( 'Y-m-d' ),
    );
}

/**
 * De kopkaart en wat onder het artikel staat, zoals de site ze toont.
 *
 * @param  WP_Post $post Bericht.
 * @return array{0: string, 1: string}
 */
function sfp_page_config_editor_omlijsting( WP_Post $post ) {
    if ( ! function_exists( 'sfp_page_config_artikel_kopkaart' ) ) {
        return array( '', '' );
    }
    $vorige          = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
    $GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- tijdelijk, hieronder teruggezet.
    setup_postdata( $post );
    try {
        $inhoud = do_blocks( (string) $post->post_content );
        list( , $hoofdstukken ) = sfp_page_config_artikel_hoofdstukken( $inhoud, sfp_page_config_artikel_hoofdstuktitels( $post->post_content ) );

        $begrip = function_exists( 'sfp_page_config_is_begrip' ) && sfp_page_config_is_begrip( $post );
        $tool   = function_exists( 'sfp_page_config_is_tool_vorm' ) && sfp_page_config_is_tool_vorm( $post );
        // Een tool: de regel boven de titel en de melding waar de tool komt; de
        // motor zelf draait niet in de editor.
        $kop    = $tool ? sfp_page_config_tool_pijlerregel( $post ) . sfp_page_config_tool_ondertitel( $post ) . '<p class="sfp-tool__noscript">' . esc_html( sfp_page_config_artikel_tekst( 'tool_plek' ) ) . '</p>' : ( $begrip ? sfp_page_config_begrip_pijlerregel( $post ) : sfp_page_config_artikel_pijlerregel( $post ) . sfp_page_config_artikel_kopkaart( $post, $hoofdstukken ) );

        ob_start();
        do_action( 'sfp_artikel_vervolg', $post ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- eigen actie, zie artikel.php.
        $vervolg = trim( (string) ob_get_clean() );

        $voet  = function_exists( 'sfp_page_config_affiliate_uitleg' ) ? sfp_page_config_affiliate_uitleg( $post ) : '';
        $voet .= $begrip ? sfp_page_config_begrip_leesverder( $post ) : '';
        $voet .= $tool ? sfp_page_config_tool_makerskaart( $post ) . sfp_page_config_tool_andere( $post ) : '';
        $voet .= '' !== $vervolg ? '<div class="sfp-art-vervolg">' . $vervolg . '</div>' : '';
        $voet .= ( $begrip || $tool ) ? '' : sfp_page_config_artikel_auteurskaart( $post );
        $voet .= sfp_page_config_artikel_voetregels( $post );
    } catch ( Throwable $e ) {
        $kop  = '';
        $voet = '';
    } finally {
        $GLOBALS['post'] = $vorige; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        if ( $vorige instanceof WP_Post ) {
            setup_postdata( $vorige );
        }
    }
    return array( $kop, $voet );
}

/* =========================================================================
 * De opmaak van de artikelonderdelen in de editor
 * ====================================================================== */

add_action( 'enqueue_block_assets', 'sfp_page_config_editor_artikel_css' );

/**
 * In de editor staan de kopkaart en de onderdelen onder het artikel zoals op
 * de site. Daarvoor laadt de editor de opmaak van de artikelen.
 */
function sfp_page_config_editor_artikel_css() {
    if ( ! is_admin() || ! function_exists( 'sfp_page_config_artikel_aan' ) || ! sfp_page_config_artikel_aan() ) {
        return;
    }
    wp_enqueue_style( 'sfp-artikel-editor', SFP_PAGE_CONFIG_URL . 'assets/artikel.css', array(), SFP_PAGE_CONFIG_VERSION );
}

/**
 * De makersregel van een begrip zoals de site hem bovenin de Definitie zet,
 * voor de editor (Komt vanzelf). Zonder bericht of bij een fout: leeg.
 *
 * @since  3.16.0
 * @param  WP_Post $post Begrip.
 * @return string
 */
function sfp_page_config_editor_begrip_maker( WP_Post $post ) {
    if ( ! function_exists( 'sfp_page_config_begrip_makersregel' ) ) {
        return '';
    }
    try {
        return sfp_page_config_begrip_makersregel( $post );
    } catch ( Throwable $e ) {
        return '';
    }
}

/* =========================================================================
 * De tool in het canvas (sinds 3.16.0)
 * ====================================================================== */

add_action( 'enqueue_block_editor_assets', 'sfp_page_config_tool_canvas_editor' );

/**
 * Op de pagina van een tool: de tool in het canvas, met de teksten om ter
 * plekke te typen (sinds 3.16.0). De beschrijving blijft de opslag.
 */
function sfp_page_config_tool_canvas_editor() {
    $scherm = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $scherm || 'tool' !== $scherm->post_type || ! sfp_page_config_tool_aan() ) {
        return;
    }
    wp_enqueue_script(
        'sfp-tool-canvas',
        SFP_PAGE_CONFIG_URL . 'assets/editor/tool-canvas.js',
        array( 'wp-element', 'wp-data', 'wp-editor', 'wp-core-data' ),
        SFP_PAGE_CONFIG_VERSION,
        true
    );
    $teksten = array();
    foreach ( array( 'tool_vragen', 'tool_resultaat', 'tool_context', 'tool_start', 'tool_volgende', 'tool_vorige', 'tool_hoe_rekent' ) as $sleutel ) {
        $teksten[ $sleutel ] = sfp_page_config_artikel_tekst( $sleutel );
    }
    $post = get_post();
    wp_add_inline_script(
        'sfp-tool-canvas',
        'window.sfpToolCanvas=' . wp_json_encode(
            array(
                'teksten'    => $teksten,
                'hoe'        => $post instanceof WP_Post ? sfp_page_config_tool_canvas_hoe( $post ) : '',
                // De site toont de samenvatting als ondertitel: in de vorm Tool,
                // en op DPS via de Astra-layout van de tools.
                'ondertitel' => true,
            )
        ) . ';',
        'before'
    );
}

/**
 * De inhoud van "Hoe deze test rekent" voor het canvas, uit de beschrijving
 * zoals bij de laatste keer opslaan (sinds 3.18.0). Alleen bij een test die
 * de controle van de site doorstaat; anders leeg.
 *
 * @since  3.18.0
 * @param  WP_Post $post Tool.
 * @return string HTML.
 */
function sfp_page_config_tool_canvas_hoe( WP_Post $post ) {
    if ( ! function_exists( 'sfp_page_config_tool_beschrijving' ) || ! function_exists( 'sfp_page_config_tool_hoe_rekent' ) ) {
        return '';
    }
    try {
        $b = sfp_page_config_tool_beschrijving( $post );
        if ( ! $b || 'test' !== $b['soort'] ) {
            return '';
        }
        $controle = sfp_page_config_tool_controleer( $b );
        return $controle['fouten'] ? '' : (string) sfp_page_config_tool_hoe_rekent( $b );
    } catch ( Throwable $e ) {
        return '';
    }
}

add_action( 'enqueue_block_assets', 'sfp_page_config_tool_canvas_css' );

/**
 * De opmaak van de tool in het canvas van de editor, zodat hij er daar
 * uitziet zoals op de site (sinds 3.16.0).
 */
function sfp_page_config_tool_canvas_css() {
    if ( ! is_admin() ) {
        return;
    }
    $scherm = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $scherm || 'tool' !== $scherm->post_type || ! sfp_page_config_tool_aan() ) {
        return;
    }
    wp_enqueue_style( 'sfp-tool-editor', SFP_PAGE_CONFIG_URL . 'assets/tool/tool.css', array(), SFP_PAGE_CONFIG_VERSION );
    wp_enqueue_style( 'sfp-tool-canvas', SFP_PAGE_CONFIG_URL . 'assets/editor/tool-canvas.css', array( 'sfp-tool-editor' ), SFP_PAGE_CONFIG_VERSION );
}

/* =========================================================================
 * Artikel, begrip en tool: alleen de stijlen van de vorm (sinds 3.19.0)
 * ====================================================================== */

/**
 * De alineastijlen die bij een artikel, begrip of tool horen. De andere
 * stijlen (Getal, Prijs, Mailregel, Naam met portret ...) betekenen alleen
 * iets op een dienstpagina.
 *
 * @return string[]
 */
function sfp_page_config_vorm_alineastijlen() {
    return array( 'sfp-lead', 'sfp-pijl', 'sfp-klein' );
}

/**
 * Staat dit soort bericht in de editor in een vorm van de plugin (artikel,
 * begrip, tool)? Alleen met het onderdeel Artikelen aan: zonder die
 * opmaak is een bericht een gewone pagina en houdt het alle keuzes.
 *
 * @param  string $post_type Soort bericht.
 * @return bool
 */
function sfp_page_config_editor_is_vorm( $post_type ) {
    if ( ! function_exists( 'sfp_page_config_artikel_aan' ) || ! sfp_page_config_artikel_aan() ) {
        return false;
    }
    $types = function_exists( 'sfp_page_config_artikel_posttypes' ) ? sfp_page_config_artikel_posttypes() : array( 'post' );
    return in_array( (string) $post_type, $types, true );
}

add_filter( 'block_editor_settings_all', 'sfp_page_config_editor_vorm_instellingen', 20, 2 );

/**
 * In een artikel, begrip of tool geen vrije kleur, achtergrond, verloop of
 * lettergrootte (ontwerpregel 21): het merk komt uit de vorm. Wat al is
 * opgeslagen, blijft op de site staan; alleen de keuze verdwijnt.
 *
 * @param  array                   $instellingen Instellingen van de editor.
 * @param  WP_Block_Editor_Context $context      Context.
 * @return array
 */
function sfp_page_config_editor_vorm_instellingen( $instellingen, $context ) {
    $post = ( is_object( $context ) && isset( $context->post ) ) ? $context->post : null;
    if ( ! $post instanceof WP_Post || ! sfp_page_config_editor_is_vorm( $post->post_type ) ) {
        return $instellingen;
    }
    return sfp_page_config_editor_zonder_kleur( $instellingen );
}

/**
 * Haal kleur, achtergrond, verloop en lettergrootte uit de instellingen van
 * de editor, ook per blok.
 *
 * @param  array $instellingen Instellingen van de editor.
 * @return array
 */
function sfp_page_config_editor_zonder_kleur( array $instellingen ) {
    $uit = function ( array $f ) {
        if ( ! isset( $f['color'] ) || ! is_array( $f['color'] ) ) {
            $f['color'] = array();
        }
        $f['color']['text']             = false;
        $f['color']['background']       = false;
        $f['color']['link']             = false;
        $f['color']['custom']           = false;
        $f['color']['customGradient']   = false;
        $f['color']['defaultGradients'] = false;
        $f['color']['defaultPalette']   = false;
        $f['color']['gradients']        = array();
        if ( ! isset( $f['typography'] ) || ! is_array( $f['typography'] ) ) {
            $f['typography'] = array();
        }
        $f['typography']['customFontSize']   = false;
        $f['typography']['defaultFontSizes'] = false;
        $f['typography']['fontSizes']        = array();
        return $f;
    };
    if ( isset( $instellingen['__experimentalFeatures'] ) && is_array( $instellingen['__experimentalFeatures'] ) ) {
        $instellingen['__experimentalFeatures'] = $uit( $instellingen['__experimentalFeatures'] );
        if ( isset( $instellingen['__experimentalFeatures']['blocks'] ) && is_array( $instellingen['__experimentalFeatures']['blocks'] ) ) {
            foreach ( $instellingen['__experimentalFeatures']['blocks'] as $blok => $f ) {
                if ( is_array( $f ) && ( isset( $f['color'] ) || isset( $f['typography'] ) ) ) {
                    $instellingen['__experimentalFeatures']['blocks'][ $blok ] = $uit( $f );
                }
            }
        }
    }
    // De oudere sleutels, voor thema's zonder theme.json (Astra).
    $instellingen['disableCustomColors']    = true;
    $instellingen['disableCustomGradients'] = true;
    $instellingen['disableCustomFontSizes'] = true;
    $instellingen['gradients']              = array();
    $instellingen['fontSizes']              = array();
    return $instellingen;
}
