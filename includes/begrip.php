<?php
/**
 * SFP Page Config - Vorm Begrip
 *
 * Een begrip (berichttype begrippen) krijgt met het onderdeel Begrip aan de
 * artikelopmaak in de vorm van het goedgekeurde ontwerp (concept 5):
 *
 *   - regel boven de titel: Glossary term in <pijler>;
 *   - het blok Definitie: makersregel als band bovenin, de definitie in één
 *     zin, een uitleg van hoogstens twee zinnen en vier feitenvakken;
 *   - het blok In a talk: één voorbeeldzin en één regel uitleg;
 *   - geen kopkaart, warming-up, auteurskaart, inhoudsopgave, hoofdstukbalk
 *     of bronnenlijst in de rechterkolom;
 *   - onder de tekst: verwante begrippen, Used in, vorige en volgende;
 *   - de definitie als tooltip in artikelen (knop Begrip in de editor), als
 *     samenvatting in de begrippenlijst en als DefinedTerm voor Google.
 *
 * @package SFP_Page_Config
 * @since   3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Staat de vorm Begrip aan op deze site?
 *
 * @return bool
 */
function sfp_page_config_begrip_aan() {
    return post_type_exists( 'begrippen' )
        && function_exists( 'sfp_page_config_onderdeel_aan' )
        && sfp_page_config_onderdeel_aan( 'begrip' )
        && function_exists( 'sfp_page_config_artikel_aan' )
        && sfp_page_config_artikel_aan();
}

add_filter( 'sfp_page_config_artikel_posttypes', 'sfp_page_config_begrip_posttypes' );

/**
 * Begrippen krijgen de artikelopmaak als de vorm Begrip aan staat.
 *
 * @param  string[] $types Berichttypes.
 * @return string[]
 */
function sfp_page_config_begrip_posttypes( $types ) {
    if ( sfp_page_config_begrip_aan() && ! in_array( 'begrippen', (array) $types, true ) ) {
        $types[] = 'begrippen';
    }
    return $types;
}

/**
 * Is dit bericht een begrip in de vorm Begrip?
 *
 * @param  WP_Post|int|null $post Bericht.
 * @return bool
 */
function sfp_page_config_is_begrip( $post ) {
    $post = get_post( $post );
    return $post && 'begrippen' === $post->post_type && sfp_page_config_begrip_aan();
}

add_action( 'init', 'sfp_page_config_begrip_meta', 12 );

/**
 * Verwante begrippen: een lijst nummers. Leeg: de andere begrippen van
 * dezelfde pijler.
 */
function sfp_page_config_begrip_meta() {
    if ( ! post_type_exists( 'begrippen' ) ) {
        return;
    }
    register_post_meta(
        'begrippen',
        'sfp_verwant',
        array(
            'type'              => 'array',
            'single'            => true,
            'default'           => array(),
            'show_in_rest'      => array(
                'schema' => array(
                    'type'  => 'array',
                    'items' => array( 'type' => 'integer' ),
                ),
            ),
            'sanitize_callback' => function ( $waarde ) {
                return array_values( array_unique( array_filter( array_map( 'absint', (array) $waarde ) ) ) );
            },
            'auth_callback'     => function ( $toegestaan, $sleutel, $post_id ) {
                return current_user_can( 'edit_post', $post_id );
            },
        )
    );
}

/* =========================================================================
 * De definitie
 * ====================================================================== */

/**
 * Het blok Definitie van een begrip.
 *
 * @param  WP_Post|int $post Begrip.
 * @return array|null Attributen van het eerste blok Definitie, of null.
 */
function sfp_page_config_begrip_definitie_blok( $post ) {
    $post = get_post( $post );
    if ( ! $post || false === strpos( (string) $post->post_content, 'wp:sfp/definitie' ) ) {
        return null;
    }
    $zoek = function ( $blokken ) use ( &$zoek ) {
        foreach ( $blokken as $blok ) {
            if ( 'sfp/definitie' === $blok['blockName'] ) {
                return (array) $blok['attrs'];
            }
            if ( ! empty( $blok['innerBlocks'] ) ) {
                $gevonden = $zoek( $blok['innerBlocks'] );
                if ( null !== $gevonden ) {
                    return $gevonden;
                }
            }
        }
        return null;
    };
    return $zoek( parse_blocks( (string) $post->post_content ) );
}

/**
 * De definitie in één zin, als platte tekst.
 *
 * @param  WP_Post|int $post Begrip.
 * @return string '' zonder definitie.
 */
function sfp_page_config_begrip_definitie( $post ) {
    $blok = sfp_page_config_begrip_definitie_blok( $post );
    if ( ! $blok || empty( $blok['zin'] ) ) {
        return '';
    }
    return trim( html_entity_decode( wp_strip_all_tags( (string) $blok['zin'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
}

add_filter( 'get_the_excerpt', 'sfp_page_config_begrip_samenvatting', 20, 2 );

/**
 * In de begrippenlijst en elders staat de definitie als samenvatting, als
 * er geen eigen samenvatting is.
 *
 * @param  string  $samenvatting Samenvatting.
 * @param  WP_Post $post         Bericht.
 * @return string
 */
function sfp_page_config_begrip_samenvatting( $samenvatting, $post = null ) {
    if ( ! $post instanceof WP_Post || 'begrippen' !== $post->post_type || has_excerpt( $post ) || ! sfp_page_config_begrip_aan() ) {
        return $samenvatting;
    }
    $zin = sfp_page_config_begrip_definitie( $post );
    return '' !== $zin ? $zin : $samenvatting;
}

/* =========================================================================
 * Blokken
 * ====================================================================== */

add_filter( 'sfp_page_config_blokken_extra', 'sfp_page_config_begrip_blokken' );

/**
 * De blokken Definitie en In a talk.
 *
 * @param  array $blokken Blokken.
 * @return array
 */
function sfp_page_config_begrip_blokken( $blokken ) {
    $tekst = array(
        'type'    => 'string',
        'default' => '',
    );
    $blokken['sfp/definitie'] = array(
        'title'        => 'Definitie',
        'description'  => 'De definitie van een begrip in één zin, een korte uitleg en vier feiten. Komt ook terug als tooltip, in de begrippenlijst en voor Google.',
        'icon'         => 'book-alt',
        'css'          => 'definitie',
        'zonder_basis' => true,
        'attributes'   => array(
            'zin'       => $tekst,
            'uitleg'    => $tekst,
            'ook'       => $tekst,
            'ook2'      => $tekst,
            'herkomst'  => $tekst,
            'herkomst2' => $tekst,
            'eerst'     => $tekst,
            'eerst2'    => $tekst,
            'deel'      => $tekst,
            'deel2'     => $tekst,
        ),
        'render'       => 'sfp_page_config_render_definitie',
    );
    $blokken['sfp/in-een-talk'] = array(
        'title'        => 'In a talk',
        'description'  => 'Eén voorbeeldzin uit een echte presentatie, met één regel uitleg. Hoogstens één per begrip.',
        'icon'         => 'format-chat',
        'css'          => 'definitie',
        'zonder_basis' => true,
        'attributes'   => array(
            'zin'    => $tekst,
            'uitleg' => $tekst,
        ),
        'render'       => 'sfp_page_config_render_in_een_talk',
    );
    return $blokken;
}

/**
 * Toegestane opmaak in de velden van de blokken.
 *
 * @return array
 */
function sfp_page_config_begrip_kses() {
    return array(
        'em'     => array(),
        'i'      => array(),
        'strong' => array(),
        'b'      => array(),
        'a'      => array( 'href' => true ),
    );
}

/**
 * sfp/definitie.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Niet gebruikt.
 * @return string
 */
function sfp_page_config_render_definitie( $attrs, $content ) {
    $t      = 'sfp_page_config_artikel_tekst';
    $k      = sfp_page_config_begrip_kses();
    $zin    = wp_kses( sfp_page_config_blok_attr( $attrs, 'zin' ), $k );
    $uitleg = wp_kses( sfp_page_config_blok_attr( $attrs, 'uitleg' ), $k );
    if ( '' === trim( wp_strip_all_tags( $zin ) ) ) {
        return '';
    }
    $feiten = '';
    foreach ( array( 'ook' => 'ook_wel', 'herkomst' => 'herkomst', 'eerst' => 'eerst_beschreven', 'deel' => 'deel_van' ) as $sleutel => $label ) {
        $hoofd = wp_kses( sfp_page_config_blok_attr( $attrs, $sleutel ), $k );
        if ( '' === trim( wp_strip_all_tags( $hoofd ) ) ) {
            continue;
        }
        $sub     = wp_kses( sfp_page_config_blok_attr( $attrs, $sleutel . '2' ), $k );
        $feiten .= '<div><dt>' . esc_html( $t( $label ) ) . '</dt><dd>' . $hoofd . ( '' !== trim( wp_strip_all_tags( $sub ) ) ? '<small>' . $sub . '</small>' : '' ) . '</dd></div>';
    }
    $post  = get_post();
    $maker = ( $post && sfp_page_config_is_begrip( $post ) ) ? sfp_page_config_begrip_makersregel( $post ) : '';
    $id    = wp_unique_id( 'sfp-def-' );
    return sfp_page_config_blokken_css_vangnet( 'sfp/definitie' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-definitie' ) ) . ' aria-labelledby="' . esc_attr( $id ) . '">'
        . ( '' !== $maker ? '<div class="sfp-definitie__maker">' . $maker . '</div>' : '' )
        . '<span class="sfp-blok-label" id="' . esc_attr( $id ) . '">' . esc_html( $t( 'definitie' ) ) . '</span>'
        . '<p class="sfp-definitie__zin">' . $zin . '</p>'
        . ( '' !== trim( wp_strip_all_tags( $uitleg ) ) ? '<p class="sfp-definitie__uitleg">' . $uitleg . '</p>' : '' )
        . ( '' !== $feiten ? '<dl class="sfp-definitie__feiten">' . $feiten . '</dl>' : '' )
        . '</div>';
}

/**
 * sfp/in-een-talk.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Niet gebruikt.
 * @return string
 */
function sfp_page_config_render_in_een_talk( $attrs, $content ) {
    $k   = sfp_page_config_begrip_kses();
    $zin = wp_kses( sfp_page_config_blok_attr( $attrs, 'zin' ), $k );
    if ( '' === trim( wp_strip_all_tags( $zin ) ) ) {
        return '';
    }
    $uitleg = wp_kses( sfp_page_config_blok_attr( $attrs, 'uitleg' ), $k );
    $id     = wp_unique_id( 'sfp-talk-' );
    return sfp_page_config_blokken_css_vangnet( 'sfp/in-een-talk' )
        . '<aside ' . sfp_page_config_blok_omhulling( array( 'sfp-in-talk' ) ) . ' aria-labelledby="' . esc_attr( $id ) . '">'
        . '<span class="sfp-blok-label" id="' . esc_attr( $id ) . '">' . esc_html( sfp_page_config_artikel_tekst( 'in_een_talk' ) ) . '</span>'
        . '<p class="sfp-in-talk__zin">' . $zin . '</p>'
        . ( '' !== trim( wp_strip_all_tags( $uitleg ) ) ? '<p>' . $uitleg . '</p>' : '' )
        . '</aside>';
}

/* =========================================================================
 * Makersregel
 * ====================================================================== */

/**
 * De makersregel: maker, wijzigingsdatum, leestijd, Fact-checked en de
 * bronnen achter een knop. Dezelfde scheidingsbolletjes als de kopkaart.
 *
 * @param  WP_Post $post Bericht.
 * @return string
 */
function sfp_page_config_begrip_makersregel( $post ) {
    $t      = 'sfp_page_config_artikel_tekst';
    $auteur = (int) $post->post_author;
    $naam   = get_the_author_meta( 'display_name', $auteur );
    $rol    = trim( (string) get_user_meta( $auteur, 'sfp_rol', true ) );
    $delen  = array();
    if ( $auteur && '' !== (string) $naam ) {
        $foto    = get_avatar( $auteur, 28, '', $naam, array( 'class' => 'sfp-art-maker__foto', 'loading' => 'eager' ) );
        $delen[] = '<span class="sfp-art-maker__wie">' . $foto . sprintf( esc_html( $t( 'door' ) ), '<a href="' . esc_url( sfp_page_config_artikel_auteur_url( $auteur ) ) . '">' . esc_html( $naam ) . '</a>' ) . ( '' !== $rol ? ', ' . esc_html( $rol ) : '' ) . '</span>';
    }
    $bijgewerkt = (int) get_post_timestamp( $post, 'modified' );
    $bijgewerkt = $bijgewerkt ? $bijgewerkt : time();
    $delen[]    = '<span><time datetime="' . esc_attr( wp_date( 'c', $bijgewerkt ) ) . '">' . esc_html( sprintf( $t( 'bijgewerkt' ), wp_date( 'j M Y', $bijgewerkt ) ) ) . '</time></span>';
    $delen[]    = '<span>' . esc_html( sprintf( $t( 'leestijd' ), sfp_page_config_artikel_leestijd( $post->post_content ) ) ) . '</span>';

    $check_url = sfp_page_config_link_instelling( 'artikel_factcheck' );
    if ( '' === $check_url ) {
        $check_url = sfp_page_config_link_instelling( 'artikel_redactie' );
    }
    $check   = sfp_page_config_artikel_icoon( 'vink' ) . esc_html( $t( 'gecheckt' ) );
    $delen[] = '' !== $check_url ? '<a class="sfp-art-kop__check" href="' . esc_url( $check_url ) . '">' . $check . '</a>' : '<span class="sfp-art-kop__check">' . $check . '</span>';

    $paneel  = '';
    $bronnen = sfp_page_config_artikel_bronnen( $post->post_content );
    if ( $bronnen ) {
        $delen[] = '<button type="button" class="sfp-art-maker__bron" aria-expanded="false" aria-controls="sfp-art-maker-bronnen" data-sfp-paneel>' . sfp_page_config_artikel_icoon( 'bron' ) . '<span>' . esc_html( sprintf( $t( 'bronnen' ), count( $bronnen ) ) ) . '</span></button>';
        $paneel  = '<ol class="sfp-art-bronnen sfp-art-maker__lijst" id="sfp-art-maker-bronnen" hidden><li>' . implode( '</li><li>', $bronnen ) . '</li></ol>';
    }
    return '<p class="sfp-art-maker">' . implode( '', $delen ) . '</p>' . $paneel;
}

/* =========================================================================
 * Onder de tekst: verwant, Used in, vorige en volgende
 * ====================================================================== */

/**
 * De pijler van een begrip of tool.
 *
 * @param  WP_Post|int $post Begrip of tool.
 * @return WP_Term|null
 */
function sfp_page_config_begrip_pijler( $post ) {
    $id = (int) get_post_meta( is_object( $post ) ? $post->ID : (int) $post, 'sfp_pijler', true );
    if ( ! $id ) {
        return null;
    }
    $term = get_term( $id, 'category' );
    return ( $term && ! is_wp_error( $term ) ) ? $term : null;
}

/**
 * Verwante begrippen: de gekozen, anders de andere begrippen van dezelfde
 * pijler. Hoogstens drie.
 *
 * @param  WP_Post $post Begrip.
 * @return WP_Post[]
 */
function sfp_page_config_begrip_verwant( $post ) {
    $gekozen = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, 'sfp_verwant', true ) ) );
    $lijst   = array();
    foreach ( $gekozen as $id ) {
        $b = get_post( $id );
        if ( $b && 'begrippen' === $b->post_type && 'publish' === $b->post_status && (int) $b->ID !== (int) $post->ID ) {
            $lijst[] = $b;
        }
    }
    if ( ! $lijst ) {
        $pijler = sfp_page_config_begrip_pijler( $post );
        if ( $pijler && function_exists( 'sfp_page_config_pijler_begrippen' ) ) {
            foreach ( sfp_page_config_pijler_begrippen( $pijler->term_id ) as $b ) {
                if ( (int) $b->ID !== (int) $post->ID ) {
                    $lijst[] = $b;
                }
            }
        }
    }
    return array_slice( $lijst, 0, 3 );
}

/**
 * De artikelen die een begrip noemen: met de knop Begrip, of met een link.
 *
 * @param  WP_Post $post Begrip.
 * @return WP_Post[]
 */
function sfp_page_config_begrip_gebruikt_in( $post ) {
    global $wpdb;
    $types = array( 'post' );
    $link  = wp_make_link_relative( get_permalink( $post ) );
    $ids   = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- per weergave één query, kleine set.
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('" . implode( "','", array_map( 'esc_sql', $types ) ) . "') AND ( post_content LIKE %s OR post_content LIKE %s ) ORDER BY post_date DESC LIMIT 6",
            '%' . $wpdb->esc_like( 'data-sfp-begrip="' . (int) $post->ID . '"' ) . '%',
            '%' . $wpdb->esc_like( 'href="' ) . '%' . $wpdb->esc_like( $link ) . '"%'
        )
    );
    return array_values( array_filter( array_map( 'get_post', array_map( 'intval', (array) $ids ) ) ) );
}

/**
 * Vorige en volgende in de begrippenlijst (op alfabet).
 *
 * @param  WP_Post $post Begrip.
 * @return array{0: WP_Post|null, 1: WP_Post|null}
 */
function sfp_page_config_begrip_buren( $post ) {
    $alle = get_posts(
        array(
            'post_type'        => 'begrippen',
            'post_status'      => 'publish',
            'posts_per_page'   => 1000,
            'orderby'          => 'title',
            'order'            => 'ASC',
            'fields'           => 'ids',
            'no_found_rows'    => true,
            'suppress_filters' => false,
        )
    );
    $plek = array_search( (int) $post->ID, array_map( 'intval', $alle ), true );
    if ( false === $plek ) {
        return array( null, null );
    }
    return array(
        $plek > 0 ? get_post( $alle[ $plek - 1 ] ) : null,
        $plek < count( $alle ) - 1 ? get_post( $alle[ $plek + 1 ] ) : null,
    );
}

/**
 * Het deel Lees verder onder een begrip.
 *
 * @param  WP_Post $post Begrip.
 * @return string
 */
function sfp_page_config_begrip_leesverder( $post ) {
    $t      = 'sfp_page_config_artikel_tekst';
    $html   = '';
    $verwant = sfp_page_config_begrip_verwant( $post );
    if ( $verwant ) {
        $items = '';
        foreach ( $verwant as $b ) {
            $zin    = sfp_page_config_begrip_definitie( $b );
            $items .= '<a class="sfp-verwant__item" href="' . esc_url( get_permalink( $b ) ) . '"><b>' . esc_html( wp_strip_all_tags( get_the_title( $b ) ) ) . '</b>' . ( '' !== $zin ? '<span>' . esc_html( $zin ) . '</span>' : '' ) . '</a>';
        }
        $html .= '<div class="sfp-leesverder__deel"><span class="sfp-blok-label">' . esc_html( $t( 'verwant' ) ) . '</span><div class="sfp-verwant">' . $items . '</div></div>';
    }
    $gebruikt = sfp_page_config_begrip_gebruikt_in( $post );
    if ( $gebruikt ) {
        $items = '';
        foreach ( $gebruikt as $p ) {
            $gids   = function_exists( 'sfp_page_config_vorm' ) && 'pijler' === sfp_page_config_vorm( $p );
            $meta   = sprintf( $t( 'leestijd' ), sfp_page_config_artikel_leestijd( $p->post_content ) );
            $items .= function_exists( 'sfp_page_config_gids_item' )
                ? str_replace( '<em>' . esc_html( $meta ) . '</em>', '<em>' . esc_html( ( $gids ? $t( 'gids' ) . ' • ' : '' ) . $meta ) . '</em>', sfp_page_config_gids_item( $p ) )
                : '';
        }
        $html .= '<div class="sfp-leesverder__deel"><span class="sfp-blok-label">' . esc_html( $t( 'gebruikt_in' ) ) . '</span><div class="sfp-gebruikt">' . $items . '</div></div>';
    }
    list( $vorige, $volgende ) = sfp_page_config_begrip_buren( $post );
    $archief = get_post_type_archive_link( 'begrippen' );
    if ( $vorige || $volgende || $archief ) {
        $html .= '<nav class="sfp-termnav" aria-label="' . esc_attr( $t( 'woordenlijst' ) ) . '">'
            . ( $vorige ? '<a class="sfp-termnav__vorige" href="' . esc_url( get_permalink( $vorige ) ) . '"><small>' . esc_html( $t( 'vorige_term' ) ) . '</small>&larr; ' . esc_html( wp_strip_all_tags( get_the_title( $vorige ) ) ) . '</a>' : '<span></span>' )
            . ( $archief ? '<a class="sfp-termnav__alle" href="' . esc_url( $archief ) . '">' . esc_html( $t( 'alle_termen' ) ) . '</a>' : '<span></span>' )
            . ( $volgende ? '<a class="sfp-termnav__volgende" href="' . esc_url( get_permalink( $volgende ) ) . '"><small>' . esc_html( $t( 'volgende_term' ) ) . '</small>' . esc_html( wp_strip_all_tags( get_the_title( $volgende ) ) ) . ' &rarr;</a>' : '<span></span>' )
            . '</nav>';
    }
    return '' === $html ? '' : '<section class="sfp-leesverder" aria-label="' . esc_attr( $t( 'lees_verder' ) ) . '">' . $html . '</section>';
}

/**
 * De regel boven de titel van een begrip: Glossary term in <pijler>, of
 * alleen Glossary.
 *
 * @param  WP_Post $post Begrip.
 * @return string
 */
function sfp_page_config_begrip_pijlerregel( $post ) {
    $t       = 'sfp_page_config_artikel_tekst';
    $archief = get_post_type_archive_link( 'begrippen' );
    $woord   = $archief ? '<a href="' . esc_url( $archief ) . '">' . esc_html( $t( 'woordenlijst' ) ) . '</a>' : esc_html( $t( 'woordenlijst' ) );
    $pijler  = sfp_page_config_begrip_pijler( $post );
    if ( $pijler ) {
        $url  = get_term_link( $pijler );
        $naam = is_wp_error( $url ) ? esc_html( $pijler->name ) : '<a href="' . esc_url( $url ) . '">' . esc_html( $pijler->name ) . '</a>';
        return '<p class="sfp-art-pijler">' . sprintf( esc_html( $t( 'begrip_in' ) ), $woord, $naam ) . '</p>';
    }
    return '<p class="sfp-art-pijler">' . $woord . '</p>';
}

/* =========================================================================
 * Tooltip met de definitie in artikelen
 * ====================================================================== */

add_filter( 'the_content', 'sfp_page_config_begrip_tooltips', 8 );

/**
 * Een woord dat met de knop Begrip aan een begrip is gekoppeld, krijgt de
 * definitie en Read more als tooltip. Zonder definitie of als het begrip
 * niet gepubliceerd is: geen tooltip, alleen het woord.
 *
 * @param  string $html Inhoud.
 * @return string
 */
function sfp_page_config_begrip_tooltips( $html ) {
    if ( false === strpos( (string) $html, 'data-sfp-begrip=' ) ) {
        return $html;
    }
    return preg_replace_callback(
        '#<span\b([^>]*)\bdata-sfp-begrip="(\d+)"([^>]*)>#i',
        function ( $m ) {
            $begrip = get_post( (int) $m[2] );
            $zin    = ( $begrip && 'begrippen' === $begrip->post_type && 'publish' === $begrip->post_status ) ? sfp_page_config_begrip_definitie( $begrip ) : '';
            $attrs  = preg_replace( '/\sdata-tooltip=("[^"]*"|\'[^\']*\')/', '', $m[1] . $m[3] );
            if ( '' === $zin ) {
                // Geen tooltip: haal de class tooltip weg, het woord blijft.
                $attrs = preg_replace( '/\bclass="([^"]*)"/', 'class="sfp-begrip-woord"', $attrs );
                return '<span' . $attrs . '>';
            }
            $tip = esc_html( $zin ) . '<br><a href="' . esc_url( get_permalink( $begrip ) ) . '">' . esc_html( sfp_page_config_artikel_tekst( 'lees_meer' ) ) . '</a>';
            // De tooltips van de plugin zoeken de class tooltip.
            if ( preg_match( '/\bclass="([^"]*)"/', $attrs, $klasse ) ) {
                $attrs = str_replace( $klasse[0], 'class="' . esc_attr( trim( 'tooltip ' . preg_replace( '/\btooltip\b/', '', $klasse[1] ) ) ) . '"', $attrs );
            } else {
                $attrs .= ' class="tooltip"';
            }
            return '<span' . $attrs . ' data-tooltip="' . esc_attr( $tip ) . '">';
        },
        $html
    );
}

/* =========================================================================
 * Gegevens voor Google
 * ====================================================================== */

add_action( 'wp_head', 'sfp_page_config_begrip_schema', 30 );

/**
 * DefinedTerm voor een begrip, met de definitie, de maker en de
 * wijzigingsdatum.
 */
function sfp_page_config_begrip_schema() {
    if ( ! is_singular( 'begrippen' ) || ! sfp_page_config_begrip_aan() ) {
        return;
    }
    $post = get_queried_object();
    if ( ! $post instanceof WP_Post ) {
        return;
    }
    $zin = sfp_page_config_begrip_definitie( $post );
    if ( '' === $zin ) {
        return;
    }
    $gegevens = array(
        '@context'     => 'https://schema.org',
        '@type'        => 'DefinedTerm',
        '@id'          => get_permalink( $post ) . '#term',
        'name'         => wp_strip_all_tags( get_the_title( $post ) ),
        'description'  => $zin,
        'url'          => get_permalink( $post ),
        'dateModified' => wp_date( 'c', (int) get_post_timestamp( $post, 'modified' ) ),
    );
    $archief = get_post_type_archive_link( 'begrippen' );
    if ( $archief ) {
        $gegevens['inDefinedTermSet'] = array(
            '@type' => 'DefinedTermSet',
            '@id'   => $archief . '#termset',
            'name'  => sfp_page_config_artikel_tekst( 'woordenlijst' ),
            'url'   => $archief,
        );
    }
    $maker = sfp_page_config_maker_id( (int) $post->post_author );
    if ( '' !== $maker ) {
        $gegevens['author'] = array( '@id' => $maker );
    }
    echo '<script type="application/ld+json" id="sfp-begrip-schema">' . wp_json_encode( $gegevens, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON uit eigen gegevens.
}

/**
 * Het @id van de maker in de gegevens voor Google: de website uit het
 * profiel met #person, zoals de Person die SureRank op het netwerk zet.
 *
 * @param  int $auteur Gebruiker.
 * @return string '' zonder website in het profiel.
 */
function sfp_page_config_maker_id( $auteur ) {
    $url = (string) get_the_author_meta( 'user_url', $auteur );
    if ( '' === $url ) {
        return '';
    }
    $delen = wp_parse_url( $url );
    if ( empty( $delen['host'] ) ) {
        return '';
    }
    return ( isset( $delen['scheme'] ) ? $delen['scheme'] : 'https' ) . '://' . $delen['host'] . '/#person';
}

/* =========================================================================
 * Controle
 * ====================================================================== */

/**
 * De meldingen voor een begrip.
 *
 * @param  WP_Post $post Bericht.
 * @return array
 */
function sfp_page_config_begrip_controle( $post ) {
    $meldingen = array();
    if ( ! sfp_page_config_is_begrip( $post ) ) {
        return $meldingen;
    }
    $zin = sfp_page_config_begrip_definitie( $post );
    if ( '' === $zin ) {
        $meldingen[] = array(
            'code'  => 'begrip-definitie',
            'kort'  => 'Geen definitie',
            'tekst' => 'Dit begrip heeft nog geen blok Definitie. Het staat daarom zonder definitie in de begrippenlijst en heeft geen tooltip in artikelen.',
            'actie' => 'editor',
        );
        return $meldingen;
    }
    $term    = trim( wp_strip_all_tags( get_the_title( $post ) ) );
    $woorden = count( preg_split( '/\s+/u', $zin, -1, PREG_SPLIT_NO_EMPTY ) );
    $tekens  = function_exists( 'mb_strlen' ) ? mb_strlen( $zin ) : strlen( $zin );
    if ( '' !== $term && 0 !== stripos( $zin, $term ) && 0 !== stripos( preg_replace( '/^(the|a|an|de|het|een|o|a|os|as|um|uma)\s+/iu', '', $zin ), $term ) ) {
        $meldingen[] = array(
            'code'  => 'begrip-begin',
            'kort'  => 'Definitie begint niet met de term',
            'tekst' => 'De definitie begint niet met "' . $term . '". Een zin die met de term begint, is los te citeren.',
            'actie' => 'editor',
        );
    }
    if ( $woorden < 15 || $woorden > 30 || $tekens > 160 ) {
        $meldingen[] = array(
            'code'  => 'begrip-lengte',
            'kort'  => 'Definitie van ' . $woorden . ' woorden',
            'tekst' => 'De definitie heeft ' . $woorden . ' woorden en ' . $tekens . ' tekens. De norm is 15 tot 30 woorden en hoogstens 160 tekens.',
            'actie' => 'editor',
        );
    }
    $vragen = substr_count( (string) $post->post_content, '<!-- wp:sfp/faq-vraag' );
    if ( 1 === $vragen ) {
        $meldingen[] = array(
            'code'  => 'begrip-faq',
            'kort'  => 'FAQ met één vraag',
            'tekst' => 'De FAQ heeft één vraag. Een FAQ hoort alleen bij een begrip met minstens twee echte vragen.',
            'actie' => 'editor',
        );
    }
    if ( substr_count( (string) $post->post_content, '<!-- wp:sfp/in-een-talk' ) > 1 ) {
        $meldingen[] = array(
            'code'  => 'begrip-talk',
            'kort'  => 'Meer dan één In a talk',
            'tekst' => 'Een begrip heeft hoogstens één blok In a talk.',
            'actie' => 'editor',
        );
    }
    return $meldingen;
}


add_action( 'enqueue_block_editor_assets', 'sfp_page_config_begrip_editor' );

/**
 * De knop Begrip in de werkbalk, bij artikelen en pagina's.
 */
function sfp_page_config_begrip_editor() {
    if ( ! sfp_page_config_begrip_aan() ) {
        return;
    }
    wp_enqueue_script(
        'sfp-begrip-format',
        SFP_PAGE_CONFIG_URL . 'assets/editor/begrip-format.js',
        array( 'wp-rich-text', 'wp-block-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data' ),
        SFP_PAGE_CONFIG_VERSION,
        true
    );
}

add_filter( 'wp_kses_allowed_html', 'sfp_page_config_begrip_kses_span', 20, 2 );

/**
 * WordPress mag het attribuut data-sfp-begrip op een span bewaren.
 *
 * @param  array  $tags    Toegestane tags.
 * @param  string $context Context.
 * @return array
 */
function sfp_page_config_begrip_kses_span( $tags, $context ) {
    if ( 'post' !== $context ) {
        return $tags;
    }
    if ( ! isset( $tags['span'] ) || ! is_array( $tags['span'] ) ) {
        $tags['span'] = array();
    }
    $tags['span']['class']           = true;
    $tags['span']['data-sfp-begrip'] = true;
    return $tags;
}

add_filter( 'sfp_tooltip_force_load', 'sfp_page_config_begrip_tooltip_laden' );

/**
 * Laad de tooltips ook op een pagina die alleen begrippen als tooltip heeft.
 *
 * @param  bool $laden Laden.
 * @return bool
 */
function sfp_page_config_begrip_tooltip_laden( $laden ) {
    if ( $laden || ! is_singular() ) {
        return $laden;
    }
    $post = get_queried_object();
    return $post instanceof WP_Post && false !== strpos( (string) $post->post_content, 'data-sfp-begrip=' );
}
