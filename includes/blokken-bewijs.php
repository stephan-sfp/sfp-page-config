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
            'label'     => array(
                'type'    => 'string',
                'default' => ''
            ),
            'logos'     => array(
                'type'    => 'array',
                'default' => array(),
                'items'   => array( 'type' => 'integer' ),
            ),
            // Alleen voor het voorbeeld in de blokkenlijst (sinds 3.20.0).
            'voorbeeld' => array(
                'type'    => 'boolean',
                'default' => false
            ),
        ),
        'render'       => 'sfp_page_config_render_logostrook',
    );
    $blokken['sfp/review'] = array(
        'title'        => 'Review',
        'description'  => 'Eén eigen review als kaart met sterren. De reviews staan in SFP Beheer, Reviews.',
        'icon'         => 'format-quote',
        'css'          => 'review-kaart',
        'zonder_basis' => true,
        'inserter'     => $reviews,
        'attributes'   => array(
            'review'    => array(
                'type'    => 'string',
                'default' => ''
            ),
            // Alleen voor het voorbeeld in de blokkenlijst (sinds 3.20.0).
            'voorbeeld' => array(
                'type'    => 'boolean',
                'default' => false
            ),
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
 * Schoon de instelling Logostrook op (3.37.0): een label en de logo's in
 * volgorde, per logo eventueel een Engelse variant en Engelse alt-tekst
 * voor pagina's in het Engels.
 *
 * @param  mixed $ruw Ruwe instelling.
 * @return array{label: string, logos: array<int, array{id: int, en_id: int, en_alt: string}>}
 */
function sfp_page_config_logostrook_schoon( $ruw ) {
    $ruw   = is_array( $ruw ) ? $ruw : array();
    $logos = array();
    $gezien = array();
    foreach ( isset( $ruw['logos'] ) && is_array( $ruw['logos'] ) ? $ruw['logos'] : array() as $l ) {
        $l  = is_array( $l ) ? $l : array( 'id' => $l );
        $id = absint( $l['id'] ?? 0 );
        if ( ! $id || isset( $gezien[ $id ] ) ) {
            continue;
        }
        $gezien[ $id ] = true;
        $en_id         = absint( $l['en_id'] ?? 0 );
        $logos[]       = array(
            'id'     => $id,
            'en_id'  => $en_id === $id ? 0 : $en_id,
            'en_alt' => mb_substr( trim( sanitize_text_field( (string) ( $l['en_alt'] ?? '' ) ) ), 0, 150 ),
        );
        if ( count( $logos ) >= 200 ) {
            break;
        }
    }
    return array(
        'label' => mb_substr( trim( sanitize_text_field( (string) ( $ruw['label'] ?? '' ) ) ), 0, 80 ),
        'logos' => $logos,
    );
}

/**
 * De instelling Logostrook uit SFP Beheer.
 *
 * @return array
 */
function sfp_page_config_logostrook_instelling() {
    $alle = function_exists( 'sfp_page_config_instellingen' ) ? sfp_page_config_instellingen() : array();
    return sfp_page_config_logostrook_schoon( isset( $alle['logostrook'] ) ? $alle['logostrook'] : array() );
}

/**
 * Staat de pagina in het Engels terwijl de site dat niet is? Dan gelden de
 * Engelse varianten (zoals op SFP; vroeger het snippet 31312).
 *
 * @return bool
 */
function sfp_page_config_logostrook_engels() {
    static $en = null;
    if ( null === $en ) {
        $en = (bool) preg_match( '/lang="en/i', (string) get_language_attributes( 'html' ) );
    }
    return $en;
}

/**
 * De instelling voor het scherm in SFP Beheer, met beeld en alt-tekst.
 *
 * @return array
 */
function sfp_page_config_logostrook_beheer() {
    $inst = sfp_page_config_logostrook_instelling();
    $uit  = array();
    foreach ( $inst['logos'] as $l ) {
        $l['url']    = (string) wp_get_attachment_image_url( $l['id'], 'thumbnail' );
        $l['alt']    = (string) get_post_meta( $l['id'], '_wp_attachment_image_alt', true );
        $l['en_url'] = $l['en_id'] ? (string) wp_get_attachment_image_url( $l['en_id'], 'thumbnail' ) : '';
        $uit[]       = $l;
    }
    return array(
        'label' => $inst['label'],
        'norm'  => function_exists( 'sfp_page_config_tekst' ) ? sfp_page_config_tekst( 'logostrook_label' ) : '',
        'logos' => $uit,
    );
}

/**
 * Eenmalig (3.37.0): neem de lijst en het label van de eerste logostrook op
 * de homepage over in SFP Beheer, in dezelfde volgorde. Een site zonder
 * strook op de home houdt een lege lijst; dan geldt de lijst per blok.
 *
 * @return bool True als er iets is overgenomen.
 */
function sfp_page_config_logostrook_overnemen() {
    $alle = sfp_page_config_instellingen();
    if ( ! empty( $alle['logostrook']['logos'] ) ) {
        return false;
    }
    $home = (int) get_option( 'page_on_front' );
    $post = $home ? get_post( $home ) : null;
    if ( ! $post instanceof WP_Post ) {
        return false;
    }
    $zoek = function ( $blokken ) use ( &$zoek ) {
        foreach ( $blokken as $b ) {
            if ( 'sfp/logostrook' === $b['blockName'] && ! empty( $b['attrs']['logos'] ) ) {
                return $b['attrs'];
            }
            if ( ! empty( $b['innerBlocks'] ) ) {
                $gevonden = $zoek( $b['innerBlocks'] );
                if ( $gevonden ) {
                    return $gevonden;
                }
            }
        }
        return null;
    };
    $attrs = $zoek( parse_blocks( $post->post_content ) );
    if ( ! $attrs ) {
        return false;
    }
    $alle['logostrook'] = sfp_page_config_logostrook_schoon(
        array(
            'label' => isset( $attrs['label'] ) ? wp_strip_all_tags( (string) $attrs['label'] ) : '',
            'logos' => array_map( 'absint', (array) $attrs['logos'] ),
        )
    );
    update_option( 'sfp_settings', $alle );
    return true;
}

/**
 * Optische maat van een logo (3.36.1, besluit Stephan 9-11-2026): elk logo
 * krijgt dezelfde oppervlakte, zodat brede logo's niet te laag en vierkante
 * niet te groot ogen. Anker: een logo van 2,5 : 1 staat op 44 px hoog.
 * Grenzen: hoogstens 56 px hoog en 220 px breed (computer); de telefoon
 * neemt 75 procent (CSS). Zonder bekende afmetingen: null, dan geldt de
 * vaste hoogte uit de CSS.
 *
 * @param  int $id Bijlage.
 * @return int[]|null Breedte en hoogte in px.
 */
function sfp_page_config_logostrook_maat( $id ) {
    $meta = wp_get_attachment_metadata( (int) $id );
    $w    = is_array( $meta ) && ! empty( $meta['width'] ) ? (float) $meta['width'] : 0.0;
    $h    = is_array( $meta ) && ! empty( $meta['height'] ) ? (float) $meta['height'] : 0.0;
    if ( $w <= 0 || $h <= 0 ) {
        return null;
    }
    $r      = $w / $h;
    $hoogte = min( 56.0, 44.0 * sqrt( 2.5 / $r ) );
    $breedte = $hoogte * $r;
    if ( $breedte > 220.0 ) {
        $breedte = 220.0;
        $hoogte  = $breedte / $r;
    }
    return array( (int) round( $breedte ), (int) round( $hoogte ) );
}

/**
 * sfp/logostrook. Zonder logo's toont de site niets (ontwerpregel 14).
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_logostrook( $attrs ) {
    // Sinds 3.37.0 (punt 13, besluit Stephan 9-11-2026): de lijst en het label
    // uit SFP Beheer, Logostrook, gaan altijd voor. Is die lijst leeg, dan
    // geldt nog de lijst in het blok.
    $inst  = function_exists( 'sfp_page_config_onderdeel_aan' ) && ! sfp_page_config_onderdeel_aan( 'logostrook' ) ? array( 'label' => '', 'logos' => array() ) : sfp_page_config_logostrook_instelling();
    $en    = sfp_page_config_logostrook_engels();
    $rijen = array();
    if ( $inst['logos'] ) {
        foreach ( $inst['logos'] as $l ) {
            if ( ! wp_attachment_is_image( $l['id'] ) ) {
                continue;
            }
            $rijen[] = array(
                'id'  => $en && $l['en_id'] && wp_attachment_is_image( $l['en_id'] ) ? $l['en_id'] : $l['id'],
                'alt' => $en && '' !== $l['en_alt'] ? $l['en_alt'] : '',
            );
        }
    } else {
        foreach ( sfp_page_config_logostrook_ids( isset( $attrs['logos'] ) ? $attrs['logos'] : array() ) as $id ) {
            $rijen[] = array(
                'id'  => $id,
                'alt' => '',
            );
        }
    }
    if ( ! $rijen ) {
        return '';
    }
    $ids   = wp_list_pluck( $rijen, 'id' );
    $label = '' !== $inst['label'] ? $inst['label'] : ( isset( $attrs['label'] ) ? trim( wp_strip_all_tags( (string) $attrs['label'] ) ) : '' );
    if ( '' === $label && function_exists( 'sfp_page_config_tekst' ) ) {
        $label = sfp_page_config_tekst( 'logostrook_label' );
    }

    $items = '';
    foreach ( $rijen as $rij ) {
        $id   = $rij['id'];
        $attr = array(
            'class'    => 'sfp-logostrook__logo',
            'loading'  => 'lazy',
            'decoding' => 'async',
            'sizes'    => '(max-width: 600px) 165px, 220px',
        );
        if ( '' !== $rij['alt'] ) {
            $attr['alt'] = $rij['alt'];
        }
        $img = wp_get_attachment_image( $id, 'medium', false, $attr );
        if ( '' !== $img ) {
            $maat   = sfp_page_config_logostrook_maat( $id );
            $items .= '<li' . ( $maat ? ' style="--sfp-logo-b:' . $maat[0] . 'px;--sfp-logo-h:' . $maat[1] . 'px"' : '' ) . '>' . $img . '</li>';
        }
    }
    if ( '' === $items ) {
        return '';
    }

    // Sinds 3.37.1 (besluit Stephan 9-11-2026, variant B "rustig wisselen"):
    // vijf vaste plekken (telefoon drie). De lijst staat volledig in de HTML;
    // de CSS toont de eerste vijf (drie), het script laat om de ruim twee
    // seconden op een plek een logo uit de rest van de lijst invloeien. Zonder
    // script of bij prefers-reduced-motion blijven de eerste staan.
    $wissel = count( $ids ) > 3;
    if ( $wissel ) {
        sfp_page_config_logostrook_js_nodig( true );
    }

    return sfp_page_config_blokken_css_vangnet( 'sfp/logostrook' )
        . '<div ' . sfp_page_config_blok_omhulling( $wissel ? array( 'sfp-logostrook', 'sfp-logostrook--wissel' ) : array( 'sfp-logostrook' ) ) . '>'
        . ( '' !== $label ? '<p class="sfp-logostrook__label">' . esc_html( $label ) . '</p>' : '' )
        . '<ul class="sfp-logostrook__logos" role="list">' . $items . '</ul>'
        . '</div>';
}

/**
 * Staat er een wisselende logostrook op de pagina?
 *
 * @param  bool $zet True om te markeren.
 * @return bool
 */
function sfp_page_config_logostrook_js_nodig( $zet = false ) {
    static $nodig = false;
    if ( $zet ) {
        $nodig = true;
    }
    return $nodig;
}

add_action( 'wp_footer', 'sfp_page_config_logostrook_js', 30 );

/**
 * Het script van de wisselende logostrook, niet uitgesteld door WP Rocket.
 */
function sfp_page_config_logostrook_js() {
    if ( sfp_page_config_logostrook_js_nodig() && function_exists( 'sfp_page_config_script_tag' ) ) {
        sfp_page_config_script_tag( 'sfp-logostrook-js', 'blokken/logostrook.js' );
    }
}

/* =========================================================================
 * Review
 * ====================================================================== */

/**
 * De Google-reviews van de site uit de bewaarde ophaling, met een vast id
 * per review (3.31.0): google- plus een korte hash van naam en tekst.
 *
 * @return array<int, array>
 */
function sfp_page_config_review_google_lijst() {
    if ( ! function_exists( 'sfp_page_config_reviews_opties' ) ) {
        return array();
    }
    $opts = sfp_page_config_reviews_opties();
    if ( empty( $opts['place_id'] ) ) {
        return array();
    }
    $data = get_transient( 'sfp_reviews_' . md5( (string) $opts['place_id'] ) );
    $uit  = array();
    foreach ( is_array( $data ) && ! empty( $data['reviews'] ) && is_array( $data['reviews'] ) ? $data['reviews'] : array() as $r ) {
        if ( ! is_array( $r ) || empty( $r['author'] ) ) {
            continue;
        }
        $r['id']         = 'google-' . substr( md5( (string) $r['author'] . '|' . (string) ( $r['text'] ?? '' ) ), 0, 10 );
        $r['vermelding'] = isset( $r['vermelding'] ) && '' !== (string) $r['vermelding'] ? (string) $r['vermelding'] : ( function_exists( 'sfp_page_config_tekst' ) ? sfp_page_config_tekst( 'review_google_bron' ) : 'Google' );
        $uit[]           = $r;
    }
    return $uit;
}

/**
 * Eén review op ID, of null: een eigen review, of sinds 3.31.0 een
 * Google-review (id google-...).
 *
 * @param  string $id ID van de review.
 * @return array|null
 */
function sfp_page_config_review_kaart_vind( $id ) {
    $id = (string) $id;
    // Sinds 3.34.0 (H6): "google" kiest vanzelf de volgende Google-review
    // met tekst die op deze pagina nog niet staat, de hoogste score eerst.
    if ( 'google' === $id ) {
        $lijst = array_values(
            array_filter(
                sfp_page_config_review_google_lijst(),
                function ( $r ) {
                    return '' !== trim( (string) ( $r['text'] ?? '' ) ) && ! in_array( $r['id'], sfp_page_config_review_getoond(), true );
                }
            )
        );
        usort(
            $lijst,
            function ( $a, $b ) {
                return (int) ( $b['rating'] ?? 0 ) <=> (int) ( $a['rating'] ?? 0 );
            }
        );
        if ( ! $lijst ) {
            return null;
        }
        sfp_page_config_review_getoond( $lijst[0]['id'] );
        return $lijst[0];
    }
    if ( 0 === strpos( $id, 'google-' ) ) {
        sfp_page_config_review_getoond( $id );
        foreach ( sfp_page_config_review_google_lijst() as $review ) {
            if ( $review['id'] === $id ) {
                return $review;
            }
        }
        return null;
    }
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
 * De Google-reviews die op deze pagina al staan (3.34.0), zodat "vanzelf"
 * geen review dubbel toont. Een id meegeven zet hem op de lijst.
 *
 * @param  string|null $id Review die getoond wordt.
 * @return string[]
 */
function sfp_page_config_review_getoond( $id = null ) {
    static $getoond = array();
    if ( null !== $id ) {
        $getoond[] = (string) $id;
    }
    return $getoond;
}

/**
 * Tekst van een review als alinea's: een lege regel begint een nieuwe
 * alinea, een losse regel wordt een regeleinde.
 *
 * @param  string $tekst Tekst.
 * @return string
 */
function sfp_page_config_review_kaart_alineas( $tekst ) {
    $tekst = str_replace( array( "\r\n", "\r" ), "\n", trim( (string) $tekst ) );
    $delen = preg_split( '/\n\s*\n/', $tekst );
    $uit   = '';
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
    $naam       = isset( $review['author'] ) ? trim( (string) $review['author'] ) : '';
    // Sinds 3.31.0 ook zonder tekst (een Google-review met alleen sterren):
    // dan sterren, naam en bron. Zonder tekst en zonder naam: niets.
    if ( '' === $tekst && '' === $naam ) {
        return '';
    }
    $vermelding = isset( $review['vermelding'] ) ? trim( (string) $review['vermelding'] ) : '';

    return sfp_page_config_blokken_css_vangnet( 'sfp/review' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-review-kaart' ) ) . '>'
        . sfp_page_config_review_kaart_sterren( isset( $review['rating'] ) ? $review['rating'] : 5 )
        . ( '' !== $tekst ? '<div class="sfp-review-kaart__tekst">' . $tekst . '</div>' : '' )
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
    // Sinds 3.31.0 ook de Google-reviews, achter de eigen reviews.
    foreach ( sfp_page_config_review_google_lijst() as $review ) {
        $tekst     = trim( preg_replace( '/\s+/', ' ', (string) ( $review['text'] ?? '' ) ) );
        $reviews[] = array(
            'id'     => (string) $review['id'],
            'author' => (string) $review['author'] . ' (Google)',
            'begin'  => '' === $tekst ? '' : ( mb_strlen( $tekst ) > 60 ? rtrim( mb_substr( $tekst, 0, 60 ) ) . '…' : $tekst ),
        );
    }
    $data = array(
        'aan'     => function_exists( 'sfp_page_config_reviews_actief' ) ? sfp_page_config_reviews_actief() : false,
        'reviews' => $reviews,
        'beheer'  => admin_url( 'admin.php?page=sfp-page-config#onderdelen/reviews' ),
        'label'   => function_exists( 'sfp_page_config_tekst' ) ? sfp_page_config_tekst( 'logostrook_label' ) : '',
    );
    wp_add_inline_script( 'sfp-blokken-editor', 'window.sfpBewijs=' . wp_json_encode( $data ) . ';', 'before' );
}
