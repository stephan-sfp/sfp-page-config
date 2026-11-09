<?php
/**
 * SFP Page Config - NL-vorm: bouwstenen van de home en de Over-pagina (3.34.0).
 *
 * Bron: dummy nl-vormen, Home (versie 86) en Over (versie 90), akkoord
 * Stephan 8-11-2026. Dit bestand levert de nieuwe blokken:
 *   - Team en Teamlid (H5): foto, naam, rol, zin, optioneel een link;
 *   - Laatste artikelen (H4): de nieuwste berichten als kaarten met beeld;
 *   - Reviewkop met Google-cijfer (H6): kop links, het cijfer rechts;
 *   - Netwerk (O2): de zusterlabels uit SFP Beheer, zonder de eigen site.
 *
 * De varianten van bestaande blokken (held foto, kaart met beeld boven,
 * kolommen slot, reviews als carrousel, tabs als kaart) staan in
 * includes/blokken.php en includes/nlvorm.php. De opmaak staat in
 * assets/blokken/nlvorm-home.css en nlvorm-over.css.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'sfp_page_config_blokken_extra', 'sfp_page_config_home_blokken' );

/**
 * De nieuwe blokken van de home en de Over-pagina.
 *
 * @param  array $alle Blokken.
 * @return array
 */
function sfp_page_config_home_blokken( $alle ) {
    $tekst = array(
        'type'    => 'string',
        'default' => '',
    );
    $getal = array(
        'type'    => 'number',
        'default' => 0,
    );
    $alle['sfp/team']                = array(
        'title'        => 'Team',
        'description'  => 'De trainers en coaches als kaarten: foto, naam, rol en een zin. Drie naast elkaar, op een telefoon als lijst.',
        'icon'         => 'groups',
        'css'          => 'nlvorm-over',
        'zonder_basis' => false,
        'attributes'   => array(),
        'render'       => 'sfp_page_config_render_team',
    );
    $alle['sfp/teamlid']             = array(
        'title'        => 'Teamlid',
        'description'  => 'Eén trainer of coach binnen Team.',
        'icon'         => 'admin-users',
        'css'          => 'nlvorm-over',
        'zonder_basis' => false,
        'parent'       => array( 'sfp/team' ),
        'attributes'   => array(
            'fotoId' => $getal,
            'naam'   => $tekst,
            'rol'    => $tekst,
            'zin'    => $tekst,
            'link'   => $tekst,
        ),
        'render'       => 'sfp_page_config_render_teamlid',
    );
    $alle['sfp/artikelen']           = array(
        'title'        => 'Laatste artikelen',
        'description'  => 'De nieuwste artikelen als kaarten met beeld, auteur en datum. Vult zichzelf.',
        'icon'         => 'excerpt-view',
        'css'          => 'nlvorm-home',
        'zonder_basis' => false,
        'attributes'   => array(
            'aantal'     => array(
                'type'    => 'number',
                'default' => 3,
            ),
            'categorie'  => $getal,
            'uittreksel' => array(
                'type'    => 'boolean',
                'default' => false,
            ),
        ),
        'render'       => 'sfp_page_config_render_artikelen',
    );
    $alle['sfp/google-samenvatting'] = array(
        'title'        => 'Reviewkop met Google-cijfer',
        'description'  => 'Label en kop links, rechts het Google-cijfer met het aantal reviews als link naar Google.',
        'icon'         => 'star-half',
        'css'          => 'nlvorm-home',
        'zonder_basis' => false,
        'attributes'   => array(),
        'render'       => 'sfp_page_config_render_google_samenvatting',
    );
    $alle['sfp/netwerk']             = array(
        'title'        => 'Netwerk',
        'description'  => 'De labels van het netwerk als kaarten met icoon, naam en zin. Uit SFP Beheer, Netwerk; de eigen site valt weg.',
        'icon'         => 'networking',
        'css'          => 'nlvorm-over',
        'zonder_basis' => false,
        'attributes'   => array(),
        'render'       => 'sfp_page_config_render_netwerk',
    );
    return $alle;
}

/* =========================================================================
 * Team (H5)
 * ====================================================================== */

/**
 * sfp/team.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Teamleden.
 * @return string
 */
function sfp_page_config_render_team( $attrs, $content ) {
    if ( '' === trim( (string) $content ) ) {
        return '';
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/team' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-team' ) ) . '>' . $content . '</div>';
}

/**
 * sfp/teamlid. Zonder naam: niets.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_teamlid( $attrs ) {
    $naam = trim( wp_strip_all_tags( sfp_page_config_blok_attr( $attrs, 'naam' ) ) );
    if ( '' === $naam ) {
        return '';
    }
    $rol  = trim( wp_strip_all_tags( sfp_page_config_blok_attr( $attrs, 'rol' ) ) );
    $zin  = trim( wp_strip_all_tags( sfp_page_config_blok_attr( $attrs, 'zin' ) ) );
    $link = esc_url( sfp_page_config_blok_attr( $attrs, 'link' ) );
    $id   = isset( $attrs['fotoId'] ) ? (int) $attrs['fotoId'] : 0;
    $foto = $id > 0 && wp_attachment_is_image( $id )
        ? wp_get_attachment_image(
            $id,
            'medium_large',
            false,
            array(
                'class'    => 'sfp-team__foto',
                'alt'      => $naam,
                'loading'  => 'lazy',
                'decoding' => 'async',
                'sizes'    => '(max-width: 700px) 84px, (max-width: 980px) 33vw, 380px',
            )
        )
        : '';
    $kop  = '' !== $link ? '<a href="' . $link . '">' . esc_html( $naam ) . '</a>' : esc_html( $naam );
    return '<div class="sfp-team__lid">' . $foto . '<div class="sfp-team__tekst"><h3 class="wp-block-heading">' . $kop . '</h3>'
        . ( '' !== $rol ? '<p class="is-style-sfp-label">' . esc_html( $rol ) . '</p>' : '' )
        . ( '' !== $zin ? '<p class="sfp-team__zin">' . esc_html( $zin ) . '</p>' : '' )
        . '</div></div>';
}

/* =========================================================================
 * Laatste artikelen (H4)
 * ====================================================================== */

/**
 * De berichten voor Laatste artikelen.
 *
 * @param  int $aantal    Aantal (1 tot 12).
 * @param  int $categorie Categorie, 0 is alle.
 * @param  int $niet      Bericht dat niet mee mag (de pagina zelf).
 * @return WP_Post[]
 */
function sfp_page_config_artikelen_lijst( $aantal, $categorie = 0, $niet = 0 ) {
    $args = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => max( 1, min( 12, (int) $aantal ) ),
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'orderby'             => 'date',
        'order'               => 'DESC',
    );
    if ( $categorie > 0 ) {
        $args['cat'] = (int) $categorie;
    }
    if ( $niet > 0 ) {
        $args['post__not_in'] = array( (int) $niet );
    }
    return get_posts( $args );
}

/**
 * Eén artikelkaart: beeld boven, kop, auteur en datum, een pijllink. Zonder
 * uitgelichte afbeelding een vlak in tint met het site-icoon (R3).
 *
 * Sinds 3.35.0 ook voor het Artikelraster (R3): met $opties een label boven
 * de titel, een eigen regel onder de kaart (leeg: geen regel), een eigen
 * uittreksel en zonder pijllink.
 *
 * @param  WP_Post $post       Bericht.
 * @param  bool    $uittreksel Met de eerste regels van het artikel.
 * @param  array   $opties     label, meta, uittreksel (tekst), pijl (bool).
 * @return string
 */
function sfp_page_config_artikel_kaart( WP_Post $post, $uittreksel = false, array $opties = array() ) {
    $link  = get_permalink( $post );
    $titel = get_the_title( $post );
    $beeld = has_post_thumbnail( $post )
        ? get_the_post_thumbnail(
            $post,
            'medium_large',
            array(
                'loading'  => 'lazy',
                'decoding' => 'async',
                'sizes'    => '(max-width: 700px) 112px, (max-width: 980px) 40vw, 380px',
            )
        )
        : '';
    if ( '' !== $beeld ) {
        $beeld = '<figure class="wp-block-image">' . $beeld . '</figure>';
    } else {
        $icoon = get_site_icon_url( 192 );
        $beeld = '<figure class="wp-block-image sfp-geenbeeld" aria-hidden="true">' . ( $icoon ? '<img src="' . esc_url( $icoon ) . '" alt="" loading="lazy" decoding="async" width="96" height="96">' : '' ) . '</figure>';
    }
    if ( array_key_exists( 'meta', $opties ) ) {
        $meta = trim( (string) $opties['meta'] );
    } else {
        $auteur = get_the_author_meta( 'display_name', (int) $post->post_author );
        $datum  = wp_date( 'j F Y', (int) get_post_timestamp( $post ) );
        $meta   = trim( ( '' !== (string) $auteur ? $auteur . ' · ' : '' ) . $datum );
    }
    $tekst = '';
    if ( $uittreksel ) {
        $tekst = isset( $opties['uittreksel'] ) ? (string) $opties['uittreksel'] : wp_trim_words( wp_strip_all_tags( has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content ) ), 30 );
        $tekst = '' !== $tekst ? '<p class="sfp-artikel__uittreksel">' . esc_html( $tekst ) . '</p>' : '';
    }
    $label = isset( $opties['label'] ) ? trim( (string) $opties['label'] ) : '';
    $pijl  = ! isset( $opties['pijl'] ) || $opties['pijl'];
    // Zonder pijllink staat de titel zelf als link (voor schermlezers en het toetsenbord).
    $kop = $pijl ? esc_html( $titel ) : '<a href="' . esc_url( $link ) . '">' . esc_html( $titel ) . '</a>';
    return '<article class="sfp-kaart sfp-kaart--vlak sfp-kaart--beeld sfp-kaart--link sfp-artikel">' . $beeld
        . '<div class="sfp-kaart__tekst">' . ( '' !== $label ? '<p class="is-style-sfp-label">' . esc_html( $label ) . '</p>' : '' )
        . '<h3 class="wp-block-heading">' . $kop . '</h3>' . $tekst
        . ( '' !== $meta ? '<p class="sfp-artikel__meta">' . esc_html( $meta ) . '</p>' : '' )
        . ( $pijl ? '<p class="is-style-sfp-pijl"><a href="' . esc_url( $link ) . '">' . esc_html( sfp_page_config_tekst( 'artikel_lees' ) ) . '</a></p>' : '' ) . '</div>'
        . '<a class="sfp-kaart__link" href="' . esc_url( $link ) . '" tabindex="-1" aria-hidden="true"></a></article>';
}

/**
 * sfp/artikelen.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_artikelen( $attrs ) {
    $berichten = sfp_page_config_artikelen_lijst(
        isset( $attrs['aantal'] ) ? (int) $attrs['aantal'] : 3,
        isset( $attrs['categorie'] ) ? (int) $attrs['categorie'] : 0,
        is_singular( 'post' ) ? (int) get_queried_object_id() : 0
    );
    if ( ! $berichten ) {
        return '';
    }
    $kaarten = '';
    foreach ( $berichten as $post ) {
        $kaarten .= sfp_page_config_artikel_kaart( $post, ! empty( $attrs['uittreksel'] ) );
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/artikelen' ) . sfp_page_config_blokken_css_vangnet( 'sfp/kaart' ) . sfp_page_config_blokken_css_vangnet( 'sfp/kolommen' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-kolommen', 'sfp-kolommen--drie', 'sfp-artikelen' ) ) . '>' . $kaarten . '</div>';
}

/* =========================================================================
 * Reviewkop met Google-cijfer (H6)
 * ====================================================================== */

/**
 * Het Google-cijfer als kaartje: cijfer, sterren, oordeel en aantal. Leeg
 * als de reviews niet gekoppeld zijn.
 *
 * @return string
 */
function sfp_page_config_google_samenvatting_html() {
    if ( ! function_exists( 'sfp_page_config_reviews_stand' ) || ! function_exists( 'sfp_page_config_onderdeel_aan' ) || ! sfp_page_config_onderdeel_aan( 'reviews' ) ) {
        return '';
    }
    $stand = sfp_page_config_reviews_stand();
    if ( empty( $stand['verbonden'] ) ) {
        return '';
    }
    // Oordeel en aantal uit de bestaande teksten van Reviews (Uitstekend, op basis van ...).
    $basis = $stand['aantal'] > 0 ? (string) $stand['basis'] : '';
    if ( '' !== (string) $stand['oordeel'] && '' !== $basis ) {
        $basis = mb_strtolower( mb_substr( $basis, 0, 1 ) ) . mb_substr( $basis, 1 );
    }
    $regel = trim( implode( ', ', array_filter( array( (string) $stand['oordeel'], $basis ) ) ) );
    $binnen = '<span class="sfp-google-sam__cijfer">' . esc_html( $stand['cijfer_tekst'] ) . '</span><span class="sfp-google-sam__rechts"><span class="sfp-google-sam__sterren" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>'
        . ( '' !== $regel ? '<span>' . esc_html( $regel ) . '</span>' : '' ) . '</span>';
    $link   = sfp_page_config_nlvorm_google_link();
    return '' !== $link
        ? '<a class="sfp-google-sam sfp-klikkaart" href="' . esc_url( $link ) . '" target="_blank" rel="noopener">' . $binnen . '</a>'
        : '<span class="sfp-google-sam">' . $binnen . '</span>';
}

/**
 * sfp/google-samenvatting: label en kop links (binnenblokken), het cijfer rechts.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Label en kop.
 * @return string
 */
function sfp_page_config_render_google_samenvatting( $attrs, $content ) {
    return sfp_page_config_blokken_css_vangnet( 'sfp/google-samenvatting' ) . sfp_page_config_blokken_css_vangnet( 'sfp/kaart' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-reviews-kop' ) ) . '><div class="sfp-reviews-kop__tekst">' . $content . '</div>' . sfp_page_config_google_samenvatting_html() . '</div>';
}

/* =========================================================================
 * Netwerk (O2)
 * ====================================================================== */

/**
 * Schoon de instelling Netwerk op: hoogstens acht labels met naam, adres,
 * zin en icoon (een afbeelding uit de media).
 *
 * @param  mixed $ruw Ruwe instelling.
 * @return array<int, array{naam: string, url: string, zin: string, icoon: int}>
 */
function sfp_page_config_netwerk_schoon( $ruw ) {
    $uit = array();
    foreach ( is_array( $ruw ) ? $ruw : array() as $r ) {
        if ( ! is_array( $r ) ) {
            continue;
        }
        $naam = mb_substr( trim( sanitize_text_field( (string) ( $r['naam'] ?? '' ) ) ), 0, 60 );
        $url  = esc_url_raw( trim( (string) ( $r['url'] ?? '' ) ), array( 'https', 'http' ) );
        if ( '' === $naam || '' === $url ) {
            continue;
        }
        $uit[] = array(
            'naam'  => $naam,
            'url'   => $url,
            'zin'   => mb_substr( trim( sanitize_text_field( (string) ( $r['zin'] ?? '' ) ) ), 0, 200 ),
            'icoon' => absint( $r['icoon'] ?? 0 ),
        );
        if ( count( $uit ) >= 8 ) {
            break;
        }
    }
    return $uit;
}

/**
 * De labels uit SFP Beheer, Netwerk.
 *
 * @return array
 */
function sfp_page_config_netwerk_instelling() {
    $alle = function_exists( 'sfp_page_config_instellingen' ) ? sfp_page_config_instellingen() : array();
    return sfp_page_config_netwerk_schoon( isset( $alle['netwerk'] ) ? $alle['netwerk'] : array() );
}

/**
 * De labels voor het scherm in SFP Beheer, met het adres van het icoon.
 *
 * @return array
 */
function sfp_page_config_netwerk_beheer() {
    $uit = array();
    foreach ( sfp_page_config_netwerk_instelling() as $l ) {
        $l['icoon_url'] = $l['icoon'] > 0 ? (string) wp_get_attachment_image_url( $l['icoon'], 'thumbnail' ) : '';
        $uit[]          = $l;
    }
    return array(
        'labels' => $uit,
        'eigen'  => sfp_page_config_netwerk_host( home_url( '/' ) ),
    );
}

/**
 * De host van een adres zonder www.
 *
 * @param  string $url Adres.
 * @return string
 */
function sfp_page_config_netwerk_host( $url ) {
    $host = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_HOST ) );
    return 0 === strpos( $host, 'www.' ) ? substr( $host, 4 ) : $host;
}

/**
 * De labels voor deze site: alles behalve de site zelf.
 *
 * @return array
 */
function sfp_page_config_netwerk_anderen() {
    $eigen = sfp_page_config_netwerk_host( home_url( '/' ) );
    return array_values(
        array_filter(
            sfp_page_config_netwerk_instelling(),
            function ( $l ) use ( $eigen ) {
                return sfp_page_config_netwerk_host( $l['url'] ) !== $eigen;
            }
        )
    );
}

/**
 * sfp/netwerk. Het icoon is het beeldmerk van dat label en houdt zijn eigen
 * kleuren.
 *
 * @return string
 */
function sfp_page_config_render_netwerk() {
    $labels = sfp_page_config_netwerk_anderen();
    if ( ! $labels ) {
        return '';
    }
    $kaarten = '';
    foreach ( $labels as $l ) {
        $icoon    = $l['icoon'] > 0 && wp_attachment_is_image( $l['icoon'] )
            ? wp_get_attachment_image(
                $l['icoon'],
                'thumbnail',
                false,
                array(
                    'class'    => 'sfp-netwerk__icoon',
                    'alt'      => '',
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                )
            )
            : '';
        $kaarten .= '<a class="sfp-kaart sfp-kaart--vlak sfp-klikkaart sfp-netwerk__kaart" href="' . esc_url( $l['url'] ) . '">' . $icoon
            . '<span class="sfp-netwerk__tekst"><strong>' . esc_html( $l['naam'] ) . '</strong>' . esc_html( $l['zin'] ) . '</span>'
            . '<span class="sfp-netwerk__naar" aria-hidden="true">' . esc_html( sfp_page_config_tekst( 'netwerk_naar' ) ) . '</span></a>';
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/netwerk' ) . sfp_page_config_blokken_css_vangnet( 'sfp/kaart' ) . sfp_page_config_blokken_css_vangnet( 'sfp/kolommen' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-kolommen', 'sfp-kolommen--drie', 'sfp-netwerk' ) ) . '>' . $kaarten . '</div>';
}

/* =========================================================================
 * Editor
 * ====================================================================== */

add_action( 'enqueue_block_editor_assets', 'sfp_page_config_home_editor_data', 20 );

/**
 * Voorbeelddata voor de editor: de netwerklabels en of de reviews gekoppeld zijn.
 */
function sfp_page_config_home_editor_data() {
    if ( ! wp_script_is( 'sfp-blokken-editor', 'registered' ) ) {
        return;
    }
    $data = array(
        'netwerk' => count( sfp_page_config_netwerk_anderen() ),
        'google'  => '' !== sfp_page_config_google_samenvatting_html(),
    );
    wp_add_inline_script( 'sfp-blokken-editor', 'window.sfpHome=' . wp_json_encode( $data ) . ';', 'before' );
}
