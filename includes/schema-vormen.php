<?php
/**
 * SFP Page Config - Gegevens voor Google per vorm (sinds 3.25.0)
 *
 * - Artikel en Pijler: de bronnen uit de bronnenlijst als citation in de
 *   Article van SureRank (zelfde @id, Google voegt de knopen samen).
 * - Homepage: de kaarten in de sectie met anker "services" als ItemList met
 *   een Service per kaart. Wat zichtbaar is, staat in het schema.
 *
 * Een pagina krijgt maar één citation-lijst en maar één aanbod: staat het
 * snippet "Bronvermeldingen in structured data" of een snippet
 * "Trainingsaanbod op homepage" aan, dan levert de plugin dat deel niet.
 *
 * @package SFP_Page_Config
 * @since   3.25.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Staat er een callback op wp_head waarvan de naam op dit patroon past?
 * Zo ziet de plugin of een ASE-snippet hetzelfde schema al levert.
 *
 * @param  string $patroon Reguliere expressie op de functienaam.
 * @return bool
 */
function sfp_page_config_schema_snippet_actief( $patroon ) {
    global $wp_filter;
    if ( empty( $wp_filter['wp_head'] ) || ! is_object( $wp_filter['wp_head'] ) || empty( $wp_filter['wp_head']->callbacks ) ) {
        return false;
    }
    foreach ( $wp_filter['wp_head']->callbacks as $callbacks ) {
        foreach ( (array) $callbacks as $cb ) {
            if ( isset( $cb['function'] ) && is_string( $cb['function'] ) && preg_match( $patroon, $cb['function'] ) ) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Levert het snippet "Bronvermeldingen in structured data" de citation al?
 *
 * @return bool
 */
function sfp_page_config_citation_door_snippet() {
    return (bool) apply_filters( 'sfp_page_config_citation_door_snippet', sfp_page_config_schema_snippet_actief( '/^sfp_cs_output_schema$/' ) );
}

/**
 * Levert een snippet "Trainingsaanbod op homepage" het aanbod al?
 *
 * @return bool
 */
function sfp_page_config_aanbod_door_snippet() {
    return (bool) apply_filters( 'sfp_page_config_aanbod_door_snippet', sfp_page_config_schema_snippet_actief( '/_homepage_courselist_schema$/' ) );
}

/**
 * De bronnen van een artikel als CreativeWork: de bronregel als naam en de
 * eerste link (ook een DOI of kaal adres) als url. Elke bron één keer.
 *
 * @param  string $content Ruwe inhoud.
 * @return array<int, array<string, string>>
 */
function sfp_page_config_bronnen_schema( $content ) {
    if ( ! function_exists( 'sfp_page_config_artikel_bronnen' ) ) {
        return array();
    }
    $uit = array();
    $al  = array();
    foreach ( sfp_page_config_artikel_bronnen( $content ) as $bron ) {
        $naam = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $bron ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
        if ( '' === $naam || isset( $al[ strtolower( $naam ) ] ) ) {
            continue;
        }
        $al[ strtolower( $naam ) ] = true;
        $werk                      = array(
            '@type' => 'CreativeWork',
            'name'  => $naam,
        );
        if ( preg_match( '/<a\s[^>]*href=([\x27\x22])(https?:\/\/[^\x27\x22]+)\1/i', $bron, $m ) ) {
            $url = esc_url_raw( html_entity_decode( $m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
            if ( '' !== $url ) {
                $werk['url'] = $url;
            }
        }
        $uit[] = $werk;
    }
    return $uit;
}

add_action( 'wp_head', 'sfp_page_config_artikel_schema', 30 );

/**
 * Vorm Artikel: de bronnen als citation in de Article van SureRank. Een
 * pijler regelt dat zelf in zijn eigen knoop (vormen.php).
 */
function sfp_page_config_artikel_schema() {
    if ( ! is_singular( 'post' ) || ! function_exists( 'sfp_page_config_artikel_aan' ) || ! sfp_page_config_artikel_aan() ) {
        return;
    }
    $post = get_queried_object();
    if ( ! $post instanceof WP_Post || 'artikel' !== sfp_page_config_vorm( $post ) ) {
        return;
    }
    if ( function_exists( 'sfp_page_config_is_gids' ) && sfp_page_config_is_gids( $post ) ) {
        return;
    }
    if ( sfp_page_config_citation_door_snippet() ) {
        return;
    }
    $bronnen = sfp_page_config_bronnen_schema( $post->post_content );
    if ( ! $bronnen ) {
        return;
    }
    $gegevens = array(
        '@context' => 'https://schema.org',
        '@type'    => 'Article',
        '@id'      => get_permalink( $post ) . '#article',
        'citation' => $bronnen,
    );
    echo sfp_page_config_json_ld( $gegevens, 'sfp-artikel-schema' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON met JSON_HEX_TAG.
}

/* =========================================================================
 * Homepage: aanbod
 * ====================================================================== */

/**
 * Zoek een blok sfp/sectie met dit anker, ook genest.
 *
 * @param  array  $blokken Geparste blokken.
 * @param  string $anker   Anker.
 * @return array|null
 */
function sfp_page_config_aanbod_sectie( array $blokken, $anker ) {
    foreach ( $blokken as $blok ) {
        if ( ! is_array( $blok ) ) {
            continue;
        }
        if ( 'sfp/sectie' === ( $blok['blockName'] ?? '' ) && isset( $blok['attrs']['anker'] ) && $anker === (string) $blok['attrs']['anker'] ) {
            return $blok;
        }
        if ( ! empty( $blok['innerBlocks'] ) ) {
            $gevonden = sfp_page_config_aanbod_sectie( $blok['innerBlocks'], $anker );
            if ( $gevonden ) {
                return $gevonden;
            }
        }
    }
    return null;
}

/**
 * Alle kaarten in een blok, in volgorde.
 *
 * @param  array $blok Blok.
 * @return array
 */
function sfp_page_config_aanbod_kaarten( array $blok ) {
    $uit = array();
    foreach ( (array) ( $blok['innerBlocks'] ?? array() ) as $binnen ) {
        if ( 'sfp/kaart' === ( $binnen['blockName'] ?? '' ) ) {
            $uit[] = $binnen;
        } elseif ( ! empty( $binnen['innerBlocks'] ) ) {
            $uit = array_merge( $uit, sfp_page_config_aanbod_kaarten( $binnen ) );
        }
    }
    return $uit;
}

/**
 * Platte tekst uit de HTML van een blok.
 *
 * @param  array $blok Blok.
 * @return string
 */
function sfp_page_config_aanbod_tekst( array $blok ) {
    return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) ( $blok['innerHTML'] ?? '' ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

/**
 * Het aanbod van de homepage als ItemList, of null.
 *
 * @param  WP_Post $post De homepage.
 * @return array|null
 */
function sfp_page_config_aanbod_gegevens( WP_Post $post ) {
    $anker  = (string) apply_filters( 'sfp_page_config_aanbod_anker', 'services' );
    $sectie = sfp_page_config_aanbod_sectie( parse_blocks( (string) $post->post_content ), $anker );
    if ( ! $sectie ) {
        return null;
    }
    $naam = '';
    foreach ( (array) $sectie['innerBlocks'] as $binnen ) {
        if ( 'core/heading' === ( $binnen['blockName'] ?? '' ) ) {
            $naam = sfp_page_config_aanbod_tekst( $binnen );
            break;
        }
    }
    $items = array();
    foreach ( sfp_page_config_aanbod_kaarten( $sectie ) as $kaart ) {
        $link = trim( (string) ( $kaart['attrs']['link'] ?? '' ) );
        if ( '' !== $link && '/' === $link[0] && '/' !== ( $link[1] ?? '' ) ) {
            $link = home_url( $link );
        }
        $link = esc_url_raw( $link );
        $kop  = '';
        $zin  = '';
        foreach ( (array) $kaart['innerBlocks'] as $binnen ) {
            $soort = $binnen['blockName'] ?? '';
            if ( '' === $kop && 'core/heading' === $soort ) {
                $kop = sfp_page_config_aanbod_tekst( $binnen );
            } elseif ( '' === $zin && 'core/paragraph' === $soort && false === strpos( (string) ( $binnen['attrs']['className'] ?? '' ), 'is-style-sfp-pijl' ) ) {
                $zin = sfp_page_config_aanbod_tekst( $binnen );
            }
        }
        if ( '' === $kop || '' === $link || 0 !== strpos( $link, 'http' ) ) {
            continue;
        }
        $dienst = array(
            '@type' => 'Service',
            'name'  => $kop,
            'url'   => $link,
        );
        if ( '' !== $zin ) {
            $dienst['description'] = $zin;
        }
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => count( $items ) + 1,
            'item'     => $dienst,
        );
    }
    if ( ! $items ) {
        return null;
    }
    $gegevens = array(
        '@context' => 'https://schema.org',
        '@type'    => 'ItemList',
        '@id'      => home_url( '/' ) . '#' . rawurlencode( $anker ),
    );
    if ( '' !== $naam ) {
        $gegevens['name'] = $naam;
    }
    $gegevens['itemListElement'] = $items;
    return $gegevens;
}

add_action( 'wp_head', 'sfp_page_config_aanbod_schema', 30 );

/**
 * Homepage: het aanbod als ItemList.
 */
function sfp_page_config_aanbod_schema() {
    if ( ! is_front_page() || ! is_page() ) {
        return;
    }
    $post = get_queried_object();
    if ( ! $post instanceof WP_Post || sfp_page_config_aanbod_door_snippet() ) {
        return;
    }
    $gegevens = sfp_page_config_aanbod_gegevens( $post );
    if ( $gegevens ) {
        echo sfp_page_config_json_ld( $gegevens, 'sfp-aanbod-schema' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON met JSON_HEX_TAG.
    }
}
