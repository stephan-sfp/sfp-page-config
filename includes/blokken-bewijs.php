<?php
/**
 * SFP Page Config - Bouwstenen Logostrook en Review
 *
 * Twee blokken voor sociale bewijskracht op een pagina, in de editor gelijk
 * aan de site (ontwerpregel 19):
 *
 *   sfp/logostrook  label en logo's van klanten op een rij; de logo's kies je
 *                   uit de mediabibliotheek, het label typ je in de strook
 *   sfp/review      één eigen review uit SFP Beheer als kaart met sterren,
 *                   tekst, naam en vermelding
 *
 * Ze vervangen de groepen met de klassen sws-logos en sws-review__kaart op
 * SwS, waarvan de opmaak alleen aan de voorkant bestond (stijlblok
 * sws-vormgeving). Maten overgenomen uit de meting op SwS van 28-10-2026.
 * Besluiten Stephan 28-10-2026: vermelding als eigen veld bij een eigen
 * review, gevulde sterren Google-geel (functionele kleur uit de merkbron),
 * rand van de kaart zoals de andere kaarten, label in de strook met iets
 * meer ruimte naar de logo's dan op SwS.
 *
 * Geen instelling voor kleur, lettertype of maat (ontwerpregel 21). Kleuren
 * en fonts uit de rolvariabelen van de bouwstenen.
 *
 * @package SFP_Page_Config
 * @since   3.14.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'sfp_page_config_blokken_extra', 'sfp_page_config_bewijs_blokken' );

/**
 * De twee blokken, in de vorm van sfp_page_config_blokken().
 *
 * @param  array $blokken Blokken.
 * @return array
 */
function sfp_page_config_bewijs_blokken( $blokken ) {
    $reviews = ! function_exists( 'sfp_page_config_onderdeel_aan' ) || sfp_page_config_onderdeel_aan( 'reviews' );

    $blokken['sfp/logostrook'] = array(
        'title'        => 'Logostrook',
        'description'  => 'Logo\'s van klanten op een rij, met een label erboven. De logo\'s kies je uit de mediabibliotheek.',
        'icon'         => 'images-alt2',
        'css'          => 'logostrook',
        'zonder_basis' => true,
        'attributes'   => array(
            'label' => array( 'type' => 'string', 'default' => '' ),
            'logos'     => array(
                'type'    => 'array',
                'default' => array(),
                'items'   => array( 'type' => 'integer' ),
            ),
            // Alleen voor het voorbeeld in de blokkenlijst (sinds 3.20.0).
            'voorbeeld' => array( 'type' => 'boolean', 'default' => false ),
        ),
        'render'       => 'sfp_page_config_render_logostrook',
    );
    $blokken['sfp/review']     = array(
        'title'        => 'Review',
        'description'  => 'Eén eigen review als kaart met sterren. De reviews staan in SFP Beheer, Reviews.',
        'icon'         => 'format-quote',
        'css'          => 'review-kaart',
        'zonder_basis' => true,
        'inserter'     => $reviews,
        'attributes'   => array(
            'review'    => array( 'type' => 'string', 'default' => '' ),
            // Alleen voor het voorbeeld in de blokkenlijst (sinds 3.20.0).
            'voorbeeld' => array( 'type' => 'boolean', 'default' => false ),
        ),
        'render'       => 'sfp_page_config_render_review_kaart',
    );
    return $blokken;
}

/* =========================================================================
 * Logostrook
 * ====================================================================== */

/**
 * De logo's die bestaan en een afbeelding zijn, in de bewaarde volgorde.
 *
 * @param  mixed $logos Waarde van het attribuut.
 * @return int[]
 */
function sfp_page_config_logostrook_ids( $logos ) {
    $uit = array();
    foreach ( (array) $logos as $id ) {
        $id = (int) $id;
        if ( $id > 0 && ! in_array( $id, $uit, true ) && wp_attachment_is_image( $id ) ) {
            $uit[] = $id;
        }
    }
    return $uit;
}

/**
 * sfp/logostrook. Zonder logo's toont de site niets (ontwerpregel 14).
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_logostrook( $attrs ) {
    $ids = sfp_page_config_logostrook_ids( isset( $attrs['logos'] ) ? $attrs['logos'] : array() );
    if ( ! $ids ) {
        return '';
    }
    $label = isset( $attrs['label'] ) ? trim( wp_strip_all_tags( (string) $attrs['label'] ) ) : '';
    if ( '' === $label && function_exists( 'sfp_page_config_tekst' ) ) {
        $label = sfp_page_config_tekst( 'logostrook_label' );
    }

    $items = '';
    foreach ( $ids as $id ) {
        $img = wp_get_attachment_image(
            $id,
            'medium',
            false,
            array(
                'class'    => 'sfp-logostrook__logo',
                'loading'  => 'lazy',
                'decoding' => 'async',
                'sizes'    => '(max-width: 600px) 120px, 150px',
            )
        );
        if ( '' !== $img ) {
            $items .= '<li>' . $img . '</li>';
        }
    }
    if ( '' === $items ) {
        return '';
    }

    return sfp_page_config_blokken_css_vangnet( 'sfp/logostrook' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-logostrook' ) ) . '>'
        . ( '' !== $label ? '<p class="sfp-logostrook__label">' . esc_html( $label ) . '</p>' : '' )
        . '<ul class="sfp-logostrook__logos" role="list">' . $items . '</ul>'
        . '</div>';
}

/* =========================================================================
 * Review
 * ====================================================================== */

/**
 * Eén eigen review op ID, of null.
 *
 * @param  string $id ID van de review.
 * @return array|null
 */
function sfp_page_config_review_kaart_vind( $id ) {
    $id = (string) $id;
    if ( '' === $id || ! class_exists( 'SFP_Reviews_Manual' ) ) {
        return null;
    }
    foreach ( SFP_Reviews_Manual::get_all() as $review ) {
        if ( is_array( $review ) && isset( $review['id'] ) && (string) $review['id'] === $id ) {
            return $review;
        }
    }
    return null;
}

/**
 * Tekst van een review als alinea's: een lege regel begint een nieuwe
 * alinea, een losse regel wordt een regeleinde.
 *
 * @param  string $tekst Tekst.
 * @return string
 */
function sfp_page_config_review_kaart_alineas( $tekst ) {
    $tekst  = str_replace( array( "\r\n", "\r" ), "\n", trim( (string) $tekst ) );
    $delen  = preg_split( '/\n\s*\n/', $tekst );
    $uit    = '';
    foreach ( (array) $delen as $deel ) {
        $deel = trim( $deel );
        if ( '' === $deel ) {
            continue;
        }
        $uit .= '<p>' . implode( '<br>', array_map( 'esc_html', explode( "\n", $deel ) ) ) . '</p>';
    }
    return $uit;
}

/**
 * Vijf sterren, gevuld tot het cijfer.
 *
 * @param  int $cijfer 1 tot en met 5.
 * @return string
 */
function sfp_page_config_review_kaart_sterren( $cijfer ) {
    $cijfer = min( 5, max( 1, (int) $cijfer ) );
    $label  = function_exists( 'sfp_page_config_tekst_f' ) ? sfp_page_config_tekst_f( 'reviews_sterren', $cijfer ) : $cijfer . '/5';
    $uit    = '';
    for ( $i = 1; $i <= 5; $i++ ) {
        $uit .= '<svg class="sfp-review-kaart__ster' . ( $i <= $cijfer ? ' is-vol' : '' ) . '" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">'
            . '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
    }
    return '<div class="sfp-review-kaart__sterren" role="img" aria-label="' . esc_attr( $label ) . '">' . $uit . '</div>';
}

/**
 * sfp/review. Geen review gekozen, review weg of onderdeel uit: de site
 * toont niets; de editor zegt waarom (ontwerpregel 14 en 15).
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_review_kaart( $attrs ) {
    if ( function_exists( 'sfp_page_config_reviews_actief' ) && ! sfp_page_config_reviews_actief() ) {
        return '';
    }
    $review = sfp_page_config_review_kaart_vind( isset( $attrs['review'] ) ? $attrs['review'] : '' );
    if ( ! $review ) {
        return '';
    }
    $tekst = sfp_page_config_review_kaart_alineas( isset( $review['text'] ) ? $review['text'] : '' );
    if ( '' === $tekst ) {
        return '';
    }
    $naam       = isset( $review['author'] ) ? trim( (string) $review['author'] ) : '';
    $vermelding = isset( $review['vermelding'] ) ? trim( (string) $review['vermelding'] ) : '';

    return sfp_page_config_blokken_css_vangnet( 'sfp/review' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-review-kaart' ) ) . '>'
        . sfp_page_config_review_kaart_sterren( isset( $review['rating'] ) ? $review['rating'] : 5 )
        . '<div class="sfp-review-kaart__tekst">' . $tekst . '</div>'
        . ( '' !== $naam ? '<p class="sfp-review-kaart__naam">' . esc_html( $naam ) . '</p>' : '' )
        . ( '' !== $vermelding ? '<p class="sfp-review-kaart__bron">' . esc_html( $vermelding ) . '</p>' : '' )
        . '</div>';
}

/* =========================================================================
 * Editor
 * ====================================================================== */

add_action( 'enqueue_block_editor_assets', 'sfp_page_config_bewijs_editor_data' );

/**
 * De keuzelijst met eigen reviews en het standaardlabel van de strook, voor
 * het editorscript van de bouwstenen.
 */
function sfp_page_config_bewijs_editor_data() {
    if ( ! wp_script_is( 'sfp-blokken-editor', 'registered' ) ) {
        return;
    }
    $reviews = array();
    if ( class_exists( 'SFP_Reviews_Manual' ) ) {
        foreach ( SFP_Reviews_Manual::get_all() as $review ) {
            if ( ! is_array( $review ) || empty( $review['id'] ) ) {
                continue;
            }
            $tekst     = trim( preg_replace( '/\s+/', ' ', (string) ( $review['text'] ?? '' ) ) );
            $reviews[] = array(
                'id'     => (string) $review['id'],
                'author' => (string) ( $review['author'] ?? '' ),
                'begin'  => mb_strlen( $tekst ) > 60 ? rtrim( mb_substr( $tekst, 0, 60 ) ) . '…' : $tekst,
            );
        }
    }
    $data = array(
        'aan'     => function_exists( 'sfp_page_config_reviews_actief' ) ? sfp_page_config_reviews_actief() : false,
        'reviews' => $reviews,
        'beheer'  => admin_url( 'admin.php?page=sfp-page-config#onderdelen/reviews' ),
        'label'   => function_exists( 'sfp_page_config_tekst' ) ? sfp_page_config_tekst( 'logostrook_label' ) : '',
    );
    wp_add_inline_script( 'sfp-blokken-editor', 'window.sfpBewijs=' . wp_json_encode( $data ) . ';', 'before' );
}
