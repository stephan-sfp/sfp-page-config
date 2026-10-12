<?php
/**
 * SFP Page Config - Gegevens van de site als blok
 *
 * Vier blokken die een gegeven tonen dat de site al kent, met de echte
 * waarde in de editor (ontwerpregel 9, wijziging 17):
 *
 *   sfp/cursusdatum  de startmomenten van deze training
 *   sfp/lestijd      de lestijd van deze training
 *   sfp/leestijd     de leestijd van deze pagina
 *   sfp/reviews      de reviews van Google en de eigen reviews
 *
 * Ze gebruiken dezelfde code als de codes tussen haken ([cursus_datum],
 * [cursus_tijd], [mijn_leestijd], [google_reviews]); die blijven werken.
 * Een blok staat alleen in de blokkenlijst als zijn onderdeel aan staat.
 *
 * @package SFP_Page_Config
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Definities, in de vorm van sfp_page_config_blokken().
 *
 * @return array<string, array>
 */
function sfp_page_config_blokken_gegevens() {
    $cursus  = ! function_exists( 'sfp_page_config_onderdeel_aan' ) || sfp_page_config_onderdeel_aan( 'cursusdata' );
    $reviews = ! function_exists( 'sfp_page_config_onderdeel_aan' ) || sfp_page_config_onderdeel_aan( 'reviews' );

    return array(
        'sfp/cursusdatum' => array(
            'title'        => 'Startmomenten',
            'description'  => 'De data van deze training, zoals ingevuld bij Cursusdata.',
            'icon'         => 'calendar-alt',
            'css'          => '',
            'zonder_basis' => true,
            'inserter'     => $cursus,
            'context'      => array( 'postId' ),
            'attributes'   => array(
                'groep'    => array( 'type' => 'integer', 'default' => 0 ),
                'toon'     => array( 'type' => 'string', 'default' => 'alle', 'enum' => array( 'alle', 'eerste' ) ),
                'indeling' => array( 'type' => 'string', 'default' => 'regel', 'enum' => array( 'regel', 'lijst' ) ),
            ),
            'render'       => 'sfp_page_config_render_cursusdatum',
        ),
        'sfp/lestijd'     => array(
            'title'        => 'Lestijd',
            'description'  => 'Hoe laat de lessen van deze training beginnen en eindigen.',
            'icon'         => 'clock',
            'css'          => '',
            'zonder_basis' => true,
            'inserter'     => $cursus,
            'context'      => array( 'postId' ),
            'attributes'   => array(
                'groep'    => array( 'type' => 'integer', 'default' => 0 ),
                'indeling' => array( 'type' => 'string', 'default' => 'regel', 'enum' => array( 'regel', 'lijst' ) ),
            ),
            'render'       => 'sfp_page_config_render_lestijd',
        ),
        'sfp/leestijd'    => array(
            'title'        => 'Leestijd',
            'description'  => 'Hoe lang het lezen van deze pagina duurt.',
            'icon'         => 'book',
            'css'          => '',
            'zonder_basis' => true,
            'context'      => array( 'postId' ),
            'attributes'   => array(),
            'render'       => 'sfp_page_config_render_leestijd',
        ),
        'sfp/reviews'     => array(
            'title'        => 'Reviews',
            'description'  => 'Reviews van Google en je eigen reviews, met het cijfer.',
            'icon'         => 'star-filled',
            'css'          => '',
            'zonder_basis' => true,
            'inserter'     => $reviews,
            'attributes'   => array(
                'weergave'     => array( 'type' => 'string', 'default' => '', 'enum' => array( '', 'slider', 'grid', 'banner', 'cijfer' ) ),
                'samenvatting' => array( 'type' => 'string', 'default' => '', 'enum' => array( '', 'ja', 'nee' ) ),
            ),
            'render'       => 'sfp_page_config_render_reviews_blok',
        ),
    );
}

/**
 * Het bericht waar het blok bij hoort.
 *
 * @param  WP_Block|null $blok Blok.
 * @return int
 */
function sfp_page_config_gegeven_post_id( $blok ) {
    if ( $blok instanceof WP_Block && ! empty( $blok->context['postId'] ) ) {
        return (int) $blok->context['postId'];
    }
    $queried = get_queried_object_id();
    return $queried ? (int) $queried : (int) get_the_ID();
}

/**
 * Omhulling voor een gegeven. Leeg blijft leeg.
 *
 * @param  string $soort Soort gegeven.
 * @param  string $html  Inhoud.
 * @return string
 */
function sfp_page_config_gegeven_omhulling( $soort, $html ) {
    if ( '' === trim( (string) $html ) ) {
        return '';
    }
    return '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-gegeven', 'sfp-gegeven--' . $soort ) ) . '>' . $html . '</div>';
}

/**
 * sfp/cursusdatum.
 *
 * @param  array         $attrs   Attributen.
 * @param  string        $content Niet gebruikt.
 * @param  WP_Block|null $blok    Blok.
 * @return string
 */
function sfp_page_config_render_cursusdatum( $attrs, $content = '', $blok = null ) {
    if ( ! function_exists( 'sfp_page_config_shortcode_cursus_datum' ) ) {
        return '';
    }
    $groep = isset( $attrs['groep'] ) ? max( 0, (int) $attrs['groep'] ) : 0;
    $html  = sfp_page_config_shortcode_cursus_datum(
        array(
            'post_id' => (string) sfp_page_config_gegeven_post_id( $blok ),
            'groep'   => $groep ? (string) $groep : '',
            'show'    => isset( $attrs['toon'] ) && 'eerste' === $attrs['toon'] ? 'first' : 'all',
            'layout'  => isset( $attrs['indeling'] ) && 'lijst' === $attrs['indeling'] ? 'list' : 'inline',
        )
    );
    return sfp_page_config_gegeven_omhulling( 'cursusdatum', $html );
}

/**
 * sfp/lestijd.
 *
 * @param  array         $attrs   Attributen.
 * @param  string        $content Niet gebruikt.
 * @param  WP_Block|null $blok    Blok.
 * @return string
 */
function sfp_page_config_render_lestijd( $attrs, $content = '', $blok = null ) {
    if ( ! function_exists( 'sfp_page_config_shortcode_cursus_tijd' ) ) {
        return '';
    }
    $groep = isset( $attrs['groep'] ) ? max( 0, (int) $attrs['groep'] ) : 0;
    $html  = sfp_page_config_shortcode_cursus_tijd(
        array(
            'post_id' => (string) sfp_page_config_gegeven_post_id( $blok ),
            'groep'   => $groep ? (string) $groep : '',
            'layout'  => isset( $attrs['indeling'] ) && 'lijst' === $attrs['indeling'] ? 'list' : 'inline',
        )
    );
    return sfp_page_config_gegeven_omhulling( 'lestijd', $html );
}

/**
 * sfp/leestijd.
 *
 * @param  array         $attrs   Attributen.
 * @param  string        $content Niet gebruikt.
 * @param  WP_Block|null $blok    Blok.
 * @return string
 */
function sfp_page_config_render_leestijd( $attrs, $content = '', $blok = null ) {
    $post = get_post( sfp_page_config_gegeven_post_id( $blok ) );
    if ( ! $post || ! function_exists( 'sfp_page_config_calculate_reading_time' ) ) {
        return '';
    }
    return sfp_page_config_gegeven_omhulling( 'leestijd', '<div class="custom-read-meter">' . sfp_page_config_calculate_reading_time( $post->post_content ) . '</div>' );
}

/**
 * sfp/reviews.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_reviews_blok( $attrs ) {
    if ( ! function_exists( 'sfp_page_config_reviews_render' ) ) {
        return '';
    }
    $atts = array();
    if ( ! empty( $attrs['weergave'] ) ) {
        $atts['layout'] = (string) $attrs['weergave'];
    }
    if ( ! empty( $attrs['samenvatting'] ) ) {
        $atts['summary'] = 'ja' === $attrs['samenvatting'] ? 'true' : 'false';
    }
    return sfp_page_config_gegeven_omhulling( 'reviews', sfp_page_config_reviews_render( $atts ) );
}
