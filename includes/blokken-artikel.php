<?php
/**
 * SFP Page Config - Bouwstenen voor artikelen
 *
 * Zeven dynamische blokken uit de goedgekeurde artikeldummy (v35, besluit
 * Stephan 10 oktober 2026):
 *
 *   sfp/samenvatting   "Wat je moet weten": witte kaart met een lijst en een deelbeeld
 *   sfp/kader          kader met label: wist je dat, kanttekening, grondslag,
 *                      reflectievraag, checklist of vrij
 *   sfp/citaat         citaat of anekdote met foto van de auteur en bijschrift
 *   sfp/inzicht        deelbaar inzicht: beeld met kernzin en deelknoppen
 *   sfp/lees-ook       kaart naar een ander artikel
 *   sfp/vervolg        slotblok met een of twee vlakken (sfp/vervolg-vlak)
 *   sfp/warming-up     drie vragen vooraf (sfp/warming-up-vraag); toont zich
 *                      in de kopkaart van het artikel, niet in de tekst
 *
 * De definities worden door sfp_page_config_blokken() in includes/blokken.php
 * samengevoegd met de paginablokken; registratie, CSS per blok en het
 * editorscript lopen via dat bestand.
 *
 * Kleuren: alleen via de variabelen --sfp-b-*. Fonts: Astra en de
 * kopfont-variabelen. Geen merkwaarden in code.
 *
 * Terugdraaien: dit bestand uit de lijst in sfp-page-config.php halen.
 *
 * @package SFP_Page_Config
 * @since   2.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De soorten kader.
 *
 * @return string[]
 */
function sfp_page_config_kader_soorten() {
    return array( 'wist-je-dat', 'kanttekening', 'grondslag', 'reflectievraag', 'checklist', 'vrij' );
}

/**
 * Definities van de artikelblokken, in de vorm van sfp_page_config_blokken().
 *
 * 'zonder_basis' betekent: dit blok heeft de CSS van de paginablokken
 * (sectie, knoppen) niet nodig.
 *
 * @return array<string, array>
 */
function sfp_page_config_blokken_artikel() {
    $tekst = array( 'type' => 'string', 'default' => '' );

    return array(
        'sfp/samenvatting'     => array(
            'title'        => 'Samenvatting',
            'description'  => 'Wat je moet weten: drie of vier punten in een kaart, met een deelbaar beeld.',
            'icon'         => 'list-view',
            'css'          => 'samenvatting',
            'css_extra'    => array( 'deelkaart' ),
            'zonder_basis' => true,
            'attributes'   => array(
                'label'     => $tekst,
                'onderwerp' => $tekst,
            ),
            'render'       => 'sfp_page_config_render_samenvatting',
        ),
        'sfp/kader'            => array(
            'title'        => 'Kader',
            'description'  => 'Kader met label: wist je dat, kanttekening, grondslag, reflectievraag, checklist of een eigen label.',
            'icon'         => 'info-outline',
            'css'          => 'kader',
            'zonder_basis' => true,
            'attributes'   => array(
                'soort' => array( 'type' => 'string', 'default' => 'wist-je-dat', 'enum' => sfp_page_config_kader_soorten() ),
                'label' => $tekst,
                'anker' => $tekst,
            ),
            'render'       => 'sfp_page_config_render_kader',
        ),
        'sfp/citaat'           => array(
            'title'        => 'Citaat',
            'description'  => 'Citaat of anekdote met de foto van de auteur en een bijschrift.',
            'icon'         => 'format-quote',
            'css'          => 'citaat',
            'zonder_basis' => true,
            'attributes'   => array(
                'bijschrift' => $tekst,
                'portret'    => array( 'type' => 'boolean', 'default' => true ),
            ),
            'render'       => 'sfp_page_config_render_citaat',
        ),
        'sfp/inzicht'          => array(
            'title'        => 'Inzicht',
            'description'  => 'Deelbaar inzicht: een kernzin als beeld, met deelknoppen. De server maakt het beeld bij opslaan.',
            'icon'         => 'lightbulb',
            'css'          => 'inzicht',
            'css_extra'    => array( 'deelkaart' ),
            'zonder_basis' => true,
            'attributes'   => array(
                'tekst' => $tekst,
                'label' => $tekst,
            ),
            'render'       => 'sfp_page_config_render_inzicht',
        ),
        'sfp/lees-ook'         => array(
            'title'        => 'Lees ook',
            'description'  => 'Kaart naar een ander artikel: titel en uitgelichte afbeelding komen vanzelf.',
            'icon'         => 'admin-links',
            'css'          => 'lees-ook',
            'zonder_basis' => true,
            'attributes'   => array(
                'postId' => array( 'type' => 'integer', 'default' => 0 ),
                'url'    => $tekst,
                'titel'  => $tekst,
            ),
            'render'       => 'sfp_page_config_render_lees_ook',
        ),
        'sfp/vervolg'          => array(
            'title'        => 'Vervolg',
            'description'  => 'Slotblok met een of twee vlakken: wat de lezer hierna kan doen.',
            'icon'         => 'controls-forward',
            'css'          => 'vervolg',
            'zonder_basis' => true,
            'attributes'   => array(),
            'render'       => 'sfp_page_config_render_vervolg',
        ),
        'sfp/vervolg-vlak'     => array(
            'title'        => 'Vervolgvlak',
            'description'  => 'Eén vlak binnen Vervolg: label, kop, tekst en een link of knop.',
            'icon'         => 'controls-forward',
            'css'          => 'vervolg',
            'zonder_basis' => true,
            'parent'       => array( 'sfp/vervolg' ),
            'attributes'   => array(
                'label' => $tekst,
            ),
            'render'       => 'sfp_page_config_render_vervolg_vlak',
        ),
        'sfp/warming-up'       => array(
            'title'        => 'Warming-up',
            'description'  => 'Drie vragen vooraf. Verschijnt als knop in de kopkaart van het artikel, niet in de tekst.',
            'icon'         => 'editor-help',
            'css'          => '',
            'zonder_basis' => true,
            'attributes'   => array(),
            'render'       => 'sfp_page_config_render_warming_up',
        ),
        'sfp/warming-up-vraag' => array(
            'title'        => 'Warming-upvraag',
            'description'  => 'Vraag met drie antwoorden en het hoofdstuk waar het antwoord staat.',
            'icon'         => 'editor-help',
            'css'          => '',
            'zonder_basis' => true,
            'parent'       => array( 'sfp/warming-up' ),
            'attributes'   => array(
                'vraag'  => $tekst,
                'optie1' => $tekst,
                'optie2' => $tekst,
                'optie3' => $tekst,
                'anker'  => $tekst,
            ),
            'render'       => 'sfp_page_config_render_warming_up_vraag',
        ),
    );
}

/* =========================================================================
 * Hulpfuncties
 * ====================================================================== */

/**
 * Volgnummer van een bloksoort binnen het bericht dat nu getoond wordt.
 * Nodig om het juiste deelbeeld bij het juiste blok te vinden.
 *
 * @param  string $soort inzicht of samenvatting.
 * @return int           1 voor het eerste blok, 2 voor het tweede, enzovoort.
 */
function sfp_page_config_blok_volgnummer( $soort ) {
    static $tellers = array();
    $post_id = (int) get_the_ID();
    if ( ! isset( $tellers[ $post_id ][ $soort ] ) ) {
        $tellers[ $post_id ][ $soort ] = 0;
    }
    return ++$tellers[ $post_id ][ $soort ];
}

/**
 * De deelknoppen onder een beeld: LinkedIn, WhatsApp, X en downloaden.
 *
 * @param  string $url      URL van het artikel.
 * @param  string $titel    Titel voor de deeltekst.
 * @param  string $download URL van het beeld, of '' als er geen beeld is.
 * @return string
 */
function sfp_page_config_deelknoppen( $url, $titel, $download = '' ) {
    $html = '';
    foreach ( sfp_page_config_artikel_deelkanalen( $url, $titel ) as $kanaal ) {
        if ( 'facebook' === $kanaal[0] ) {
            continue;
        }
        $html .= '<a href="' . esc_url( $kanaal[2] ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( sprintf( sfp_page_config_artikel_tekst( 'deel_op' ), $kanaal[1] ) ) . '">' . sfp_page_config_artikel_icoon( $kanaal[0] ) . '</a>';
    }
    if ( '' !== $download ) {
        $html .= '<a href="' . esc_url( $download ) . '" download aria-label="' . esc_attr( sfp_page_config_artikel_tekst( 'download' ) ) . '">' . sfp_page_config_artikel_icoon( 'download' ) . '</a>';
    }
    return '<span class="sfp-deelknoppen">' . $html . '</span>';
}

/**
 * De voet van een deelkaart in HTML: foto, naam en domein. Alleen gebruikt
 * als de server (nog) geen beeld heeft gemaakt.
 *
 * @return string
 */
function sfp_page_config_deelkaart_voet() {
    $auteur = (int) get_post_field( 'post_author', get_the_ID() );
    $naam   = $auteur ? get_the_author_meta( 'display_name', $auteur ) : get_bloginfo( 'name' );
    $domein = preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
    $foto   = $auteur ? get_avatar( $auteur, 32, '', '', array( 'loading' => 'lazy' ) ) : '';
    return '<span class="sfp-deelkaart__voet">' . $foto . '<span><b>' . esc_html( $naam ) . '</b>' . esc_html( $domein ) . '</span></span>';
}

/**
 * Sta in een kernzin alleen nadruk toe; <em> markeert het accentwoord.
 *
 * @param  string $tekst Ruwe tekst uit het blok.
 * @return string
 */
function sfp_page_config_inzicht_kses( $tekst ) {
    return trim( wp_kses( (string) $tekst, array( 'em' => array() ) ) );
}

/* =========================================================================
 * Weergave per blok
 * ====================================================================== */

/**
 * sfp/samenvatting.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken (een lijst).
 * @return string
 */
function sfp_page_config_render_samenvatting( $attrs, $content ) {
    $label = sfp_page_config_blok_attr( $attrs, 'label' );
    if ( '' === $label ) {
        $label = sfp_page_config_artikel_tekst( 'samenvatting' );
    }
    $id  = wp_unique_id( 'sfp-samenvatting-' );
    $nr  = sfp_page_config_blok_volgnummer( 'samenvatting' );
    $uit = '<aside ' . sfp_page_config_blok_omhulling( array( 'sfp-samenvatting' ) ) . ' aria-labelledby="' . esc_attr( $id ) . '-kop">';

    // Het deelpaneel alleen op het artikel zelf, niet in overzichten of feeds.
    $paneel = '';
    if ( is_singular() && ! is_feed() ) {
        $post_id   = (int) get_the_ID();
        $onderwerp = sfp_page_config_blok_attr( $attrs, 'onderwerp' );
        if ( '' === $onderwerp ) {
            $onderwerp = get_the_title( $post_id );
        }
        preg_match_all( '#<li[^>]*>(.*?)</li>#s', (string) $content, $m );
        $punten = array_values( array_filter( array_map( 'wp_strip_all_tags', $m[1] ), 'strlen' ) );
        // Alleen het beeld dat bij deze punten hoort; anders de kaart in HTML.
        $beeld = function_exists( 'sfp_page_config_deelbeeld_voor' ) ? sfp_page_config_deelbeeld_voor( $post_id, 'samenvatting', $nr, sfp_page_config_deelbeeld_controle( implode( "\n", $punten ) ) ) : null;

        if ( $beeld ) {
            $kaart = '<img class="sfp-samenvatting__beeld" src="' . esc_url( $beeld['url'] ) . '" width="' . (int) $beeld['breedte'] . '" height="' . (int) $beeld['hoogte'] . '" loading="lazy" decoding="async" alt="' . esc_attr( sprintf( sfp_page_config_artikel_tekst( 'deelbeeld_alt' ), sprintf( sfp_page_config_artikel_tekst( 'dingen' ), count( $punten ) ) . ' ' . $onderwerp ) ) . '">';
        } else {
            $lijst = '';
            foreach ( $punten as $punt ) {
                $lijst .= '<li>' . esc_html( $punt ) . '</li>';
            }
            $kaart = '<div class="sfp-deelkaart sfp-deelkaart--vierkant"><span class="sfp-deelkaart__label">' . esc_html( sprintf( sfp_page_config_artikel_tekst( 'dingen' ), count( $punten ) ) ) . '</span><span class="sfp-deelkaart__titel">' . esc_html( $onderwerp ) . '</span><ol>' . $lijst . '</ol>' . sfp_page_config_deelkaart_voet() . '</div>';
        }
        $paneel = '<div class="sfp-samenvatting__paneel" id="' . esc_attr( $id ) . '" hidden>' . $kaart
            . '<div class="sfp-samenvatting__knoppen"><span>' . esc_html( sfp_page_config_artikel_tekst( 'deel_samenvatting' ) ) . '</span>'
            . sfp_page_config_deelknoppen( get_permalink( $post_id ), get_the_title( $post_id ), $beeld ? $beeld['url'] : '' ) . '</div></div>';
        if ( function_exists( 'sfp_page_config_artikel_js_nodig' ) ) {
            sfp_page_config_artikel_js_nodig( true );
        }
    }

    $knop = '' === $paneel ? '' : '<button type="button" class="sfp-samenvatting__deel" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '" data-sfp-paneel>' . sfp_page_config_artikel_icoon( 'delen' ) . '<span>' . esc_html( sfp_page_config_artikel_tekst( 'delen' ) ) . '</span></button>';

    return sfp_page_config_blokken_css_vangnet( 'sfp/samenvatting' )
        . $uit . '<div class="sfp-samenvatting__kop"><span class="sfp-blok-label" id="' . esc_attr( $id ) . '-kop">' . esc_html( $label ) . '</span>' . $knop . '</div>'
        . $content . $paneel . '</aside>';
}

/**
 * sfp/kader.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_kader( $attrs, $content ) {
    $soort = sfp_page_config_blok_kies( sfp_page_config_blok_attr( $attrs, 'soort', 'wist-je-dat' ), sfp_page_config_kader_soorten() );
    $label = sfp_page_config_blok_attr( $attrs, 'label' );
    if ( '' === $label && 'vrij' !== $soort ) {
        $label = sfp_page_config_artikel_tekst( 'kader_' . $soort );
    }
    $id  = wp_unique_id( 'sfp-kader-' );
    $kop = '' === $label ? '' : '<span class="sfp-blok-label" id="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</span>';
    return sfp_page_config_blokken_css_vangnet( 'sfp/kader' )
        . '<aside ' . sfp_page_config_blok_omhulling( array( 'sfp-kader', 'sfp-kader--' . $soort ), sfp_page_config_blok_attr( $attrs, 'anker' ) ) . ( '' === $kop ? '' : ' aria-labelledby="' . esc_attr( $id ) . '"' ) . '>' . $kop . $content . '</aside>';
}

/**
 * sfp/citaat.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken (de alinea's van het citaat).
 * @return string
 */
function sfp_page_config_render_citaat( $attrs, $content ) {
    $bijschrift = wp_kses( sfp_page_config_blok_attr( $attrs, 'bijschrift' ), sfp_page_config_blok_titel_kses() );
    $voet       = '';
    if ( '' !== $bijschrift ) {
        $foto = '';
        if ( ! isset( $attrs['portret'] ) || $attrs['portret'] ) {
            $auteur = (int) get_post_field( 'post_author', get_the_ID() );
            $foto   = $auteur ? get_avatar( $auteur, 36, '', '', array( 'loading' => 'lazy' ) ) : '';
        }
        $voet = '<footer>' . $foto . '<span>' . $bijschrift . '</span></footer>';
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/citaat' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-citaat' ) ) . '><blockquote>' . $content . '</blockquote>' . $voet . '</div>';
}

/**
 * sfp/inzicht.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Niet gebruikt.
 * @return string
 */
function sfp_page_config_render_inzicht( $attrs, $content ) {
    $tekst = sfp_page_config_inzicht_kses( sfp_page_config_blok_attr( $attrs, 'tekst' ) );
    if ( '' === $tekst ) {
        return '';
    }
    $label = sfp_page_config_blok_attr( $attrs, 'label' );
    if ( '' === $label ) {
        $label = sfp_page_config_artikel_tekst( 'inzicht' );
    }
    $nr      = sfp_page_config_blok_volgnummer( 'inzicht' );
    $post_id = (int) get_the_ID();
    $kaal    = wp_strip_all_tags( $tekst );
    // Alleen het beeld dat bij deze zin hoort; anders de kaart in HTML.
    $beeld   = function_exists( 'sfp_page_config_deelbeeld_voor' ) ? sfp_page_config_deelbeeld_voor( $post_id, 'inzicht', $nr, sfp_page_config_deelbeeld_controle( $kaal ) ) : null;

    if ( $beeld ) {
        // In het artikel de lichtere versie waar die past; het JPG op volle maat blijft voor delen en downloaden.
        $srcset = ! empty( $beeld['klein'] ) ? ' srcset="' . esc_url( $beeld['klein'] ) . ' 800w, ' . esc_url( $beeld['url'] ) . ' ' . (int) $beeld['breedte'] . 'w" sizes="(max-width: 740px) calc(100vw - 32px), 680px"' : '';
        $kaart  = '<img class="sfp-inzicht__beeld" src="' . esc_url( $beeld['url'] ) . '"' . $srcset . ' width="' . (int) $beeld['breedte'] . '" height="' . (int) $beeld['hoogte'] . '" loading="lazy" decoding="async" alt="' . esc_attr( $label . ': ' . $kaal ) . '">';
    } else {
        $kaart = '<div class="sfp-deelkaart sfp-deelkaart--liggend" role="img" aria-label="' . esc_attr( $label . ': ' . $kaal ) . '"><span class="sfp-deelkaart__label">' . esc_html( $label ) . '</span><p class="sfp-deelkaart__tekst">' . $tekst . '</p>' . sfp_page_config_deelkaart_voet() . '</div>';
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/inzicht' )
        . '<figure ' . sfp_page_config_blok_omhulling( array( 'sfp-inzicht' ) ) . '>' . $kaart
        . '<figcaption class="sfp-inzicht__deel"><span>' . esc_html( sfp_page_config_artikel_tekst( 'deel_inzicht' ) ) . '</span>'
        . sfp_page_config_deelknoppen( get_permalink( $post_id ), $kaal, $beeld ? $beeld['url'] : '' ) . '</figcaption></figure>';
}

/**
 * sfp/lees-ook.
 *
 * Met een gekozen bericht komen titel, link en uitgelichte afbeelding uit
 * dat bericht; is het niet (meer) gepubliceerd, dan toont het blok niets.
 * Zonder bericht gelden de velden url en titel.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Niet gebruikt.
 * @return string
 */
function sfp_page_config_render_lees_ook( $attrs, $content ) {
    $post_id = isset( $attrs['postId'] ) ? (int) $attrs['postId'] : 0;
    $titel   = sfp_page_config_blok_attr( $attrs, 'titel' );
    $url     = esc_url( sfp_page_config_blok_attr( $attrs, 'url' ) );
    $beeld   = '';

    if ( $post_id ) {
        $doel = get_post( $post_id );
        if ( ! $doel || 'publish' !== $doel->post_status ) {
            return '';
        }
        if ( '' === $titel ) {
            $titel = get_the_title( $doel );
        }
        $url   = get_permalink( $doel );
        $beeld = get_the_post_thumbnail( $doel, 'medium', array( 'loading' => 'lazy', 'alt' => '' ) );
    }
    if ( '' === $titel || ! $url ) {
        return '';
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/lees-ook' )
        . '<a ' . sfp_page_config_blok_omhulling( array( 'sfp-lees-ook' ) ) . ' href="' . esc_url( $url ) . '"><span class="sfp-lees-ook__beeld' . ( '' === $beeld ? ' sfp-lees-ook__beeld--leeg' : '' ) . '" aria-hidden="true">' . $beeld . '</span>'
        . '<span class="sfp-lees-ook__tekst"><small class="sfp-blok-label">' . esc_html( sfp_page_config_artikel_tekst( 'lees_ook' ) ) . '</small><b>' . esc_html( $titel ) . '</b></span>'
        . '<span class="sfp-lees-ook__pijl" aria-hidden="true">&rarr;</span></a>';
}

/**
 * sfp/vervolg.
 *
 * @param  array    $attrs   Attributen.
 * @param  string   $content Binnenblokken (de vlakken).
 * @param  WP_Block $block   Blokinstantie.
 * @return string
 */
function sfp_page_config_render_vervolg( $attrs, $content, $block = null ) {
    $aantal = $block instanceof WP_Block ? count( $block->inner_blocks ) : substr_count( (string) $content, 'sfp-vervolg__vlak' );
    return sfp_page_config_blokken_css_vangnet( 'sfp/vervolg' )
        . '<section ' . sfp_page_config_blok_omhulling( array( 'sfp-vervolg', $aantal > 1 ? 'sfp-vervolg--twee' : '' ) ) . ' aria-label="' . esc_attr( sfp_page_config_artikel_tekst( 'vervolg' ) ) . '">' . $content . '</section>';
}

/**
 * sfp/vervolg-vlak.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $content Binnenblokken.
 * @return string
 */
function sfp_page_config_render_vervolg_vlak( $attrs, $content ) {
    $label = wp_kses( sfp_page_config_blok_attr( $attrs, 'label' ), sfp_page_config_blok_titel_kses() );
    return '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-vervolg__vlak' ) ) . '>' . ( '' === $label ? '' : '<span class="sfp-blok-label">' . $label . '</span>' ) . $content . '</div>';
}

/* =========================================================================
 * Warming-up: de vragen worden verzameld en in de kopkaart getoond
 * ====================================================================== */

/**
 * Verzamel de warming-upvragen van het bericht dat nu getoond wordt.
 *
 * @param  array|null $vraag Vraag om toe te voegen, of null om te lezen.
 * @return array
 */
function sfp_page_config_warming_up_verzamel( $vraag = null ) {
    static $vragen = array();
    if ( null !== $vraag ) {
        $vragen[] = $vraag;
    }
    return $vragen;
}

/**
 * sfp/warming-up. Toont niets in de tekst; de kopkaart toont de vragen.
 *
 * @return string
 */
function sfp_page_config_render_warming_up() {
    return '';
}

/**
 * sfp/warming-up-vraag.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_warming_up_vraag( $attrs ) {
    $vraag  = trim( wp_strip_all_tags( sfp_page_config_blok_attr( $attrs, 'vraag' ) ) );
    $opties = array();
    foreach ( array( 'optie1', 'optie2', 'optie3' ) as $sleutel ) {
        $optie = trim( wp_strip_all_tags( sfp_page_config_blok_attr( $attrs, $sleutel ) ) );
        if ( '' !== $optie ) {
            $opties[] = $optie;
        }
    }
    if ( '' !== $vraag && count( $opties ) >= 2 && ! is_admin() ) {
        sfp_page_config_warming_up_verzamel(
            array(
                'vraag'  => $vraag,
                'opties' => $opties,
                'anker'  => sanitize_title( sfp_page_config_blok_attr( $attrs, 'anker' ) ),
            )
        );
    }
    return '';
}

/* =========================================================================
 * Patroon: niet en wel
 * ====================================================================== */

add_action( 'init', 'sfp_page_config_blokken_artikel_patronen', 20 );

/**
 * Registreer het patroon "Niet en wel": twee kolommen met een kruislijst
 * en een vinklijst. Vervangt de shortcode [ux_niet_wel].
 */
function sfp_page_config_blokken_artikel_patronen() {
    if ( ! function_exists( 'register_block_pattern' ) ) {
        return;
    }
    if ( function_exists( 'register_block_pattern_category' ) ) {
        register_block_pattern_category( 'sfp', array( 'label' => 'SFP bouwstenen' ) );
    }
    $niet = esc_html( sfp_page_config_artikel_tekst( 'niet' ) );
    $wel  = esc_html( sfp_page_config_artikel_tekst( 'wel' ) );
    register_block_pattern(
        'sfp/niet-wel',
        array(
            'title'      => 'Niet en wel',
            'categories' => array( 'sfp' ),
            'content'    => '<!-- wp:sfp/kolommen {"indeling":"twee"} -->'
                . '<!-- wp:sfp/kolom --><!-- wp:paragraph {"className":"is-style-sfp-bovenschrift sfp-niet"} --><p class="is-style-sfp-bovenschrift sfp-niet">' . $niet . '</p><!-- /wp:paragraph -->'
                . '<!-- wp:sfp/lijst {"stijl":"kruis"} --><!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li></li><!-- /wp:list-item --></ul><!-- /wp:list --><!-- /wp:sfp/lijst --><!-- /wp:sfp/kolom -->'
                . '<!-- wp:sfp/kolom --><!-- wp:paragraph {"className":"is-style-sfp-bovenschrift sfp-wel"} --><p class="is-style-sfp-bovenschrift sfp-wel">' . $wel . '</p><!-- /wp:paragraph -->'
                . '<!-- wp:sfp/lijst {"stijl":"vink"} --><!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li></li><!-- /wp:list-item --></ul><!-- /wp:list --><!-- /wp:sfp/lijst --><!-- /wp:sfp/kolom -->'
                . '<!-- /wp:sfp/kolommen -->',
        )
    );
}
