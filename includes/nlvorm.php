<?php
/**
 * SFP Page Config - NL-vorm: bouwstenen van de trainingspagina (3.30.0).
 *
 * De trainingspagina van de NL-labels (dummy nl-vormen, versie 63) vraagt om
 * een paar nieuwe bouwstenen en een eigen opmaak. Dit bestand levert:
 *   - de blokken Groepskaarten en Founder-citaat;
 *   - de USP-strook onder de header (onderdeel uspstrook);
 *   - het Course-schema uit de groepskaarten;
 *   - de opmaak NL-vorm (assets/blokken/nlvorm.css), alleen op pagina's met
 *     de held "wit" of een van de nieuwe blokken;
 *   - de kapcorrectie van de H1 in de held "wit", gemeten per kopfont.
 *
 * De varianten van bestaande blokken (held wit, kolommen, uitklap programma,
 * lijst als vakjes, stappen als uitkomsten) staan in includes/blokken.php.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Blokken
 * ====================================================================== */

add_filter( 'sfp_page_config_blokken_extra', 'sfp_page_config_nlvorm_blokken' );

/**
 * De nieuwe blokken van de NL-vorm.
 *
 * @param  array $alle Blokken.
 * @return array
 */
function sfp_page_config_nlvorm_blokken( $alle ) {
    $tekst                      = array(
        'type'    => 'string',
        'default' => '',
    );
    $alle['sfp/groepskaarten'] = array(
        'title'        => 'Groepskaarten',
        'description'  => 'De groepen van deze training uit de cursusdata: status, data, plaats en prijs, met een link die de groep in het inschrijfformulier kiest.',
        'icon'         => 'calendar-alt',
        'css'          => 'nlvorm-groepen',
        'zonder_basis' => false,
        'attributes'   => array(
            'wachtlijst' => array(
                'type'    => 'boolean',
                'default' => true,
            ),
            'doel'       => array(
                'type'    => 'string',
                'default' => 'inschrijven',
            ),
        ),
        'render'       => 'sfp_page_config_render_groepskaarten',
    );
    $alle['sfp/founder']       = array(
        'title'        => 'Founder-citaat',
        'description'  => 'Foto van de oprichter met het netwerkcitaat, naam en rol. Alleen op een groot scherm, naast het inschrijfformulier.',
        'icon'         => 'format-quote',
        'css'          => 'nlvorm-inschrijven',
        'zonder_basis' => false,
        'attributes'   => array(
            'fotoId'  => array(
                'type'    => 'number',
                'default' => 0,
            ),
            'fotoUrl' => $tekst,
            'fotoAlt' => $tekst,
            'focus'   => array(
                'type'    => 'string',
                'default' => '50% 30%',
            ),
            'citaat'  => $tekst,
            'naam'    => array(
                'type'    => 'string',
                'default' => 'Stephan Borggreve',
            ),
            'rol'     => $tekst,
        ),
        'render'       => 'sfp_page_config_render_founder',
    );
    return $alle;
}

/**
 * Een datum in delen, in de taal van de site.
 *
 * @param  string $d Datum JJJJ-MM-DD.
 * @return array{dag: string, nr: string, maand: string, maand_kort: string}
 */
function sfp_page_config_nlvorm_datum( $d ) {
    $leeg = array(
        'dag'        => '',
        'nr'         => '',
        'maand'      => '',
        'maand_kort' => '',
    );
    if ( ! function_exists( 'sfp_page_config_geldige_datum' ) || ! sfp_page_config_geldige_datum( (string) $d ) ) {
        return $leeg;
    }
    $kort = explode( ' ', (string) sfp_page_config_format_datum( $d, 'short' ) );
    $lang = explode( ' ', (string) sfp_page_config_format_datum( $d, 'long' ) );
    if ( count( $kort ) < 3 || count( $lang ) < 3 ) {
        return $leeg;
    }
    $dag = (string) $kort[0];
    return array(
        'dag'        => function_exists( 'mb_strtoupper' ) ? mb_strtoupper( mb_substr( $dag, 0, 1 ) ) . mb_substr( $dag, 1 ) : ucfirst( $dag ),
        'nr'         => (string) $kort[1],
        'maand'      => (string) $lang[2],
        'maand_kort' => (string) $kort[2],
    );
}

/**
 * De groepen van een training met een eerste dag vandaag of later, met hun
 * nummer zoals het keuzemenu ze noemt.
 *
 * @param  int $post_id Pagina.
 * @return array<int, array{nr: int, data: string[], status: string, start: string, eind: string}>
 */
function sfp_page_config_nlvorm_groepen( $post_id ) {
    if ( ! function_exists( 'sfp_page_config_cursusdata_toekomst' ) ) {
        return array();
    }
    $uit = array();
    foreach ( array_values( (array) sfp_page_config_cursusdata_toekomst( (int) $post_id ) ) as $i => $sm ) {
        if ( empty( $sm['data'] ) || ! is_array( $sm['data'] ) ) {
            continue;
        }
        $uit[] = array(
            'nr'     => $i + 1,
            'data'   => array_values( $sm['data'] ),
            'status' => isset( $sm['status'] ) && in_array( $sm['status'], array( 'laatste', 'vol' ), true ) ? (string) $sm['status'] : '',
            'start'  => isset( $sm['start'] ) ? (string) $sm['start'] : '',
            'eind'   => isset( $sm['eind'] ) ? (string) $sm['eind'] : '',
        );
    }
    return $uit;
}

/**
 * sfp/groepskaarten.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_groepskaarten( $attrs ) {
    $post_id = function_exists( 'sfp_page_config_gegeven_post_id' ) ? (int) sfp_page_config_gegeven_post_id( null ) : (int) get_the_ID();
    if ( ! $post_id ) {
        $post_id = (int) get_the_ID();
    }
    $groepen = sfp_page_config_nlvorm_groepen( $post_id );
    $info    = function_exists( 'sfp_page_config_cursusinfo' ) ? sfp_page_config_cursusinfo( $post_id ) : array(
        'plaats' => '',
        'prijs'  => 0,
    );
    $doel    = sanitize_title( sfp_page_config_blok_attr( $attrs, 'doel', 'inschrijven' ) );
    $doel    = '' !== $doel ? $doel : 'inschrijven';
    $css     = sfp_page_config_blokken_css_vangnet( 'sfp/groepskaarten' );

    if ( ! $groepen ) {
        // Geen groep meer: dezelfde tekst als [cursus_datum] (includes/wachtlijst.php).
        $link = '<a class="sfp-tekstlink" href="#' . esc_attr( $doel ) . '" data-sfp-groep="wachtlijst">' . esc_html( sfp_page_config_tekst( 'wachtlijst_link' ) ) . '</a>';
        return $css . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-groepen', 'sfp-groepen--leeg' ) ) . '><p class="sfp-wachtlijstregel">' . sprintf( esc_html( sfp_page_config_tekst( 'wachtlijst_tekst' ) ), $link ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- link hierboven opgebouwd.
    }

    $prijs = '';
    if ( $info['prijs'] > 0 ) {
        $prijs = '<p class="is-style-sfp-prijs">&euro;&nbsp;' . esc_html( number_format_i18n( $info['prijs'] ) ) . ' <span class="sfp-excl">' . esc_html( sfp_page_config_tekst( 'groep_excl_btw' ) ) . '</span></p>';
    }
    $statussen = array(
        ''        => sfp_page_config_tekst( 'groep_vrij' ),
        'laatste' => sfp_page_config_tekst( 'groep_laatste' ),
        'vol'     => sfp_page_config_tekst( 'groep_vol' ),
    );

    $kaarten = '';
    foreach ( $groepen as $g ) {
        $eerste = sfp_page_config_nlvorm_datum( $g['data'][0] );
        $rijen  = '';
        foreach ( $g['data'] as $i => $d ) {
            $deel   = sfp_page_config_nlvorm_datum( $d );
            $rijen .= '<li><span class="sfp-groep__dag">' . esc_html( sfp_page_config_tekst_f( 'groep_dag', $i + 1 ) ) . '</span><span>' . esc_html( trim( $deel['dag'] . ' ' . $deel['nr'] . ' ' . $deel['maand'] ) ) . '</span></li>';
        }
        $vol     = 'vol' === $g['status'];
        $kaarten .= '<div class="sfp-kaart sfp-kaart--accent sfp-groep' . ( $vol ? ' sfp-groep--vol' : '' ) . '">'
            . '<div class="sfp-groep__kopregel"><p class="is-style-sfp-label">' . esc_html( sfp_page_config_tekst_f( 'groep', $g['nr'] ) ) . '</p>'
            . '<span class="sfp-groep__status' . ( '' !== $g['status'] ? ' sfp-groep__status--' . esc_attr( $g['status'] ) : '' ) . '">' . esc_html( $statussen[ $g['status'] ] ) . '</span></div>'
            . '<h3>' . esc_html( sfp_page_config_tekst_f( 'groep_start', trim( $eerste['nr'] . ' ' . $eerste['maand'] ) ) ) . '</h3>'
            . '<ul class="sfp-groep__data">' . $rijen . '</ul>'
            . ( '' !== $info['plaats'] ? '<p class="sfp-groep__meta">' . esc_html( $info['plaats'] ) . '</p>' : '' )
            . $prijs
            . ( $vol ? '' : '<a class="sfp-tekstlink" href="#' . esc_attr( $doel ) . '" data-sfp-groep="' . esc_attr( (string) $g['nr'] ) . '">' . esc_html( sfp_page_config_tekst_f( 'groep_inschrijven', $g['nr'] ) ) . '</a>' )
            . '</div>';
    }

    $wachtlijst = '';
    if ( ! isset( $attrs['wachtlijst'] ) || ! empty( $attrs['wachtlijst'] ) ) {
        $wachtlijst = '<p class="sfp-wachtlijstregel">' . esc_html( sfp_page_config_tekst( 'groep_wachtlijst_vraag' ) ) . ' <a class="sfp-tekstlink" href="#' . esc_attr( $doel ) . '" data-sfp-groep="wachtlijst">' . esc_html( sfp_page_config_tekst( 'groep_wachtlijst_link' ) ) . '</a></p>';
    }

    sfp_page_config_nlvorm_schema_groepen( $post_id, $groepen, $info );

    return $css . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-groepen' ) ) . '>'
        . '<div class="sfp-kolommen sfp-kolommen--groepen">' . $kaarten . '</div>'
        . $wachtlijst
        . '</div>';
}

/**
 * sfp/founder.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_founder( $attrs ) {
    $id  = isset( $attrs['fotoId'] ) ? (int) $attrs['fotoId'] : 0;
    $url = sfp_page_config_blok_attr( $attrs, 'fotoUrl' );
    $alt = sfp_page_config_blok_attr( $attrs, 'fotoAlt' );
    if ( $id && wp_attachment_is_image( $id ) ) {
        $src = wp_get_attachment_image_url( $id, 'large' );
        $url = $src ? $src : $url;
        if ( '' === $alt ) {
            $alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
        }
    }
    $focus = sfp_page_config_blok_attr( $attrs, 'focus', '50% 30%' );
    $focus = preg_match( '/^\d{1,3}% \d{1,3}%$/', $focus ) ? $focus : '50% 30%';

    $citaat = sfp_page_config_blok_attr( $attrs, 'citaat' );
    $citaat = '' !== trim( wp_strip_all_tags( $citaat ) ) ? $citaat : esc_html( sfp_page_config_tekst( 'founder_citaat' ) );
    $naam   = sfp_page_config_blok_attr( $attrs, 'naam', 'Stephan Borggreve' );
    $rol    = sfp_page_config_blok_attr( $attrs, 'rol' );
    $rol    = '' !== $rol ? $rol : sfp_page_config_tekst_f( 'founder_rol', html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ) );

    $foto = '' !== $url
        ? '<img class="sfp-founder__foto" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async" style="object-position:' . esc_attr( $focus ) . '">'
        : '';

    return sfp_page_config_blokken_css_vangnet( 'sfp/founder' )
        . '<figure ' . sfp_page_config_blok_omhulling( array( 'sfp-founder' ) ) . '>'
        . $foto
        . '<figcaption class="sfp-founder__tekst">'
        . '<blockquote class="sfp-founder__citaat"><p>' . wp_kses( $citaat, array( 'br' => array() ) ) . '</p></blockquote>'
        . '<p class="sfp-founder__wie"><strong>' . esc_html( $naam ) . '</strong><span>' . esc_html( $rol ) . '</span></p>'
        . '</figcaption></figure>';
}

/* =========================================================================
 * Held, variant wit: feitenkaart, bewijsregel en kapcorrectie
 * ====================================================================== */

/**
 * De feitenkaart van de held "wit": de eerstvolgende start uit de
 * cursusdata, daarna de feiten uit het blok. Prijs hoort er niet in.
 *
 * @param  array $attrs Attributen van de held.
 * @return string
 */
function sfp_page_config_nlvorm_feitenkaart( $attrs ) {
    $feiten = array();
    if ( ! isset( $attrs['volgendeStart'] ) || ! empty( $attrs['volgendeStart'] ) ) {
        $groepen = sfp_page_config_nlvorm_groepen( (int) get_the_ID() );
        foreach ( $groepen as $g ) {
            if ( 'vol' === $g['status'] ) {
                continue;
            }
            $deel  = sfp_page_config_nlvorm_datum( $g['data'][0] );
            $label = sfp_page_config_tekst( 'held_volgende_start' );
            $spatie = strrpos( $label, ' ' );
            $label_html = false === $spatie ? esc_html( $label ) : '<span class="sfp-lang">' . esc_html( substr( $label, 0, $spatie + 1 ) ) . '</span>' . esc_html( substr( $label, $spatie + 1 ) );
            $maand      = $deel['maand'];
            $kort       = $deel['maand_kort'];
            $maand_html = 0 === strpos( $maand, $kort ) && strlen( $maand ) > strlen( $kort )
                ? esc_html( $kort ) . '<span class="sfp-lang">' . esc_html( substr( $maand, strlen( $kort ) ) ) . '</span>'
                : esc_html( $maand );
            $feiten[] = array( $label_html, esc_html( $deel['dag'] . ' ' . $deel['nr'] ) . ' ' . $maand_html );
            break;
        }
    }
    foreach ( isset( $attrs['feiten'] ) && is_array( $attrs['feiten'] ) ? $attrs['feiten'] : array() as $f ) {
        $label  = is_array( $f ) && isset( $f['label'] ) ? trim( (string) $f['label'] ) : '';
        $waarde = is_array( $f ) && isset( $f['waarde'] ) ? trim( (string) $f['waarde'] ) : '';
        if ( '' !== $label && '' !== $waarde ) {
            $feiten[] = array( esc_html( $label ), esc_html( $waarde ) );
        }
    }
    $feiten = array_slice( $feiten, 0, 3 );
    if ( ! $feiten ) {
        return '';
    }
    $uit = '';
    foreach ( $feiten as $f ) {
        $uit .= '<div><span class="is-style-sfp-label">' . $f[0] . '</span><strong>' . $f[1] . '</strong></div>';
    }
    return '<div class="sfp-held__feit" role="group" aria-label="' . esc_attr( sfp_page_config_tekst( 'held_kerngegevens' ) ) . '">' . $uit . '</div>';
}

/**
 * Het Google-profiel van de site, als de reviews gekoppeld zijn.
 *
 * @return string
 */
function sfp_page_config_nlvorm_google_link() {
    if ( ! function_exists( 'sfp_page_config_reviews_opties' ) ) {
        return '';
    }
    $opts = sfp_page_config_reviews_opties();
    $id   = isset( $opts['place_id'] ) ? trim( (string) $opts['place_id'] ) : '';
    return '' !== $id ? 'https://search.google.com/local/reviews?placeid=' . rawurlencode( $id ) : '';
}

/**
 * Het Google-cijfer van de site, of leeg.
 *
 * @return string
 */
function sfp_page_config_nlvorm_google_cijfer() {
    if ( ! function_exists( 'sfp_page_config_reviews_stand' ) || ! function_exists( 'sfp_page_config_onderdeel_aan' ) || ! sfp_page_config_onderdeel_aan( 'reviews' ) ) {
        return '';
    }
    $stand = sfp_page_config_reviews_stand();
    return ! empty( $stand['verbonden'] ) ? (string) $stand['cijfer_tekst'] : '';
}

/**
 * De bewijsregel onder de knoppen van de held "wit": sterren met het
 * Google-cijfer als link naar het profiel, en het aantal deelnemers.
 *
 * @param  array $attrs Attributen van de held.
 * @return string
 */
function sfp_page_config_nlvorm_bewijsregel( $attrs ) {
    $cijfer     = sfp_page_config_nlvorm_google_cijfer();
    $deelnemers = isset( $attrs['deelnemers'] ) ? trim( (string) $attrs['deelnemers'] ) : '';
    if ( '' === $cijfer && '' === $deelnemers ) {
        return '';
    }
    $uit = '';
    if ( '' !== $cijfer ) {
        $link  = sfp_page_config_nlvorm_google_link();
        $score = '<span class="sfp-held__sterren" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span><span><strong>' . esc_html( $cijfer ) . '</strong> ' . esc_html( sfp_page_config_tekst( 'held_op_google' ) ) . '</span>';
        $uit  .= '' !== $link
            ? '<a class="sfp-held__score" href="' . esc_url( $link ) . '" target="_blank" rel="noopener">' . $score . '</a>'
            : '<span class="sfp-held__score">' . $score . '</span>';
    }
    if ( '' !== $deelnemers ) {
        $koppen = '';
        foreach ( array_slice( preg_split( '/[\s,]+/', isset( $attrs['initialen'] ) ? (string) $attrs['initialen'] : '', -1, PREG_SPLIT_NO_EMPTY ), 0, 3 ) as $i ) {
            $koppen .= '<span>' . esc_html( mb_substr( $i, 0, 2 ) ) . '</span>';
        }
        $uit .= '<span class="sfp-held__deelnemers">' . ( '' !== $koppen ? '<span class="sfp-held__koppen" aria-hidden="true">' . $koppen . '</span>' : '' ) . '<span>' . esc_html( $deelnemers ) . '</span></span>';
    }
    return '<p class="sfp-held__bewijs">' . $uit . '</p>';
}

/**
 * De held "wit" als HTML: tekst links, beeld rechts met de feitenkaart.
 *
 * @param  array         $attrs Attributen.
 * @param  WP_Block|null $blok  Het blok, voor de binnenblokken apart.
 * @param  string        $content Binnenblokken als geheel (terugval).
 * @return string
 */
function sfp_page_config_nlvorm_held( $attrs, $blok, $content ) {
    $tekst = '';
    $beeld = '';
    if ( $blok instanceof WP_Block && $blok->inner_blocks ) {
        foreach ( $blok->inner_blocks as $kind ) {
            $html = $kind->render();
            if ( 'core/image' === $kind->name && '' === $beeld ) {
                $beeld = $html;
            } else {
                $tekst .= $html;
            }
        }
    } else {
        $tekst = $content;
    }
    $tekst .= sfp_page_config_nlvorm_bewijsregel( $attrs );
    $feit   = sfp_page_config_nlvorm_feitenkaart( $attrs );
    if ( '' !== $beeld && '' !== $feit ) {
        $pos   = strrpos( $beeld, '</figure>' );
        $beeld = false !== $pos ? substr( $beeld, 0, $pos ) . $feit . substr( $beeld, $pos ) : $beeld . $feit;
    }
    sfp_page_config_nlvorm_kap_nodig( true );
    return '<div class="sfp-held__binnen"><div class="sfp-held__tekst">' . $tekst . '</div>' . $beeld . '</div>';
}

/**
 * Markeer dat de kapcorrectie nodig is.
 *
 * @param  bool $zet True om te markeren.
 * @return bool
 */
function sfp_page_config_nlvorm_kap_nodig( $zet = false ) {
    static $nodig = false;
    if ( $zet ) {
        $nodig = true;
    }
    return $nodig;
}

add_action( 'wp_footer', 'sfp_page_config_nlvorm_kap_script', 21 );

/**
 * De kapcorrectie: de bovenkant van de hoofdletters van de H1 op de
 * bovenrand van de foto. Gemeten met het kopfont zelf (canvas measureText),
 * dus per site goed, ook als het kopfont wisselt. Zonder script staat de H1
 * gewoon iets lager.
 */
function sfp_page_config_nlvorm_kap_script() {
    if ( ! sfp_page_config_nlvorm_kap_nodig() ) {
        return;
    }
    $js = '(function(){function k(){document.querySelectorAll(".sfp-held--wit h1").forEach(function(h){'
        . 'var s=getComputedStyle(h),f=parseFloat(s.fontSize),l=parseFloat(s.lineHeight);if(isNaN(l))l=f*1.18;'
        . 'var c=document.createElement("canvas").getContext("2d");if(!c)return;c.font=s.fontWeight+" "+f+"px "+s.fontFamily;'
        . 'var m=c.measureText("H");var a=m.fontBoundingBoxAscent||f*.95,d=m.fontBoundingBoxDescent||f*.25,b=m.actualBoundingBoxAscent||f*.7;'
        // Afstand van de bovenrand van het blok tot de bovenkant van de H:
        // padding, de halve regelafstand (ten opzichte van de hoogte van het
        // font, niet van de lettergrootte) en de ruimte boven de hoofdletter.
        . 'var p=parseFloat(s.paddingTop)||0;h.style.setProperty("--sfp-kapcorrectie",(p+(l-(a+d))/2+(a-b)).toFixed(2)+"px");});}'
        . 'if(document.fonts&&document.fonts.ready){document.fonts.ready.then(k);}else{k();}'
        . 'var t;window.addEventListener("resize",function(){clearTimeout(t);t=setTimeout(k,150);});})();';
    echo '<script id="sfp-kapcorrectie" nowprocket>' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste JS uit dit bestand.
}

/* =========================================================================
 * Programma: twee dagen samen open
 * ====================================================================== */

/**
 * Markeer dat het script van het programma nodig is.
 *
 * @param  bool $zet True om te markeren.
 * @return bool
 */
function sfp_page_config_nlvorm_programma_nodig( $zet = false ) {
    static $nodig = false;
    if ( $zet ) {
        $nodig = true;
    }
    return $nodig;
}

add_action( 'wp_footer', 'sfp_page_config_nlvorm_programma_script', 21 );

/**
 * Twee dagen naast elkaar gaan samen open en dicht, zodat ze even hoog
 * blijven. Bij meer dagen staan ze gestapeld en gaat elke dag los.
 */
function sfp_page_config_nlvorm_programma_script() {
    if ( ! sfp_page_config_nlvorm_programma_nodig() ) {
        return;
    }
    $js = '(function(){document.querySelectorAll(".sfp-uitklap--programma:not(.sfp-uitklap--gestapeld)").forEach(function(w){'
        . 'var d=w.querySelectorAll("details.sfp-uitklap__regel");d.forEach(function(x){x.addEventListener("toggle",function(){'
        . 'd.forEach(function(o){if(o!==x&&o.open!==x.open)o.open=x.open;});});});});})();';
    echo '<script id="sfp-programma" nowprocket>' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste JS uit dit bestand.
}

/* =========================================================================
 * Opmaak NL-vorm laden
 * ====================================================================== */

/**
 * Staat er een bouwsteen van de NL-vorm in deze inhoud?
 *
 * @param  string $inhoud Berichtinhoud.
 * @return bool
 */
function sfp_page_config_nlvorm_in( $inhoud ) {
    return (bool) sfp_page_config_nlvorm_delen( $inhoud );
}

add_filter( 'sfp_page_config_blokken_bestanden', 'sfp_page_config_nlvorm_bestanden', 10, 2 );

/**
 * De delen van de NL-vorm die deze pagina nodig heeft, in de head. Elk deel
 * blijft onder de 10 KB (tests/css-inline.php).
 *
 * @param  string[] $bestanden CSS-bestanden.
 * @param  WP_Post  $post      Pagina.
 * @return string[]
 */
function sfp_page_config_nlvorm_bestanden( $bestanden, $post ) {
    if ( ! $post instanceof WP_Post ) {
        return $bestanden;
    }
    foreach ( sfp_page_config_nlvorm_delen( $post->post_content ) as $deel ) {
        if ( ! in_array( $deel, $bestanden, true ) ) {
            $bestanden[] = $deel;
        }
    }
    return $bestanden;
}

/**
 * Welke delen van de NL-vorm horen bij deze inhoud?
 *
 * @param  string $inhoud Berichtinhoud.
 * @return string[]
 */
function sfp_page_config_nlvorm_delen( $inhoud ) {
    $inhoud = (string) $inhoud;
    $delen  = array();
    if ( preg_match( '/<!-- wp:sfp\/held \{[^}]*"variant":"wit"/', $inhoud ) ) {
        $delen[] = 'nlvorm-held';
    }
    if ( preg_match( '/<!-- wp:sfp\/kolommen \{[^}]*"indeling":"(situaties|reviews)"|<!-- wp:sfp\/stappen \{[^}]*"vorm":"uitkomsten"|<!-- wp:sfp\/uitklap \{[^}]*"variant":"programma"|<!-- wp:sfp\/lijst \{[^}]*"rij":true|<!-- wp:sfp\/zelftest-cta \{[^}]*"knop":"omlijnd"/', $inhoud ) ) {
        $delen[] = 'nlvorm-inhoud';
    }
    if ( false !== strpos( $inhoud, '<!-- wp:sfp/groepskaarten' ) ) {
        $delen[] = 'nlvorm-groepen';
    }
    if ( false !== strpos( $inhoud, '<!-- wp:sfp/founder' ) || preg_match( '/<!-- wp:sfp\/kolommen \{[^}]*"indeling":"(boek|inschrijven)"/', $inhoud ) ) {
        $delen[] = 'nlvorm-inschrijven';
    }
    return $delen;
}

/* =========================================================================
 * USP-strook onder de header (onderdeel uspstrook)
 * ====================================================================== */

/**
 * Schoon de instelling USP-strook op.
 *
 * @param  mixed $ruw Ruwe instelling.
 * @return array{usps: string[], telefoon: string, soorten: string[]}
 */
function sfp_page_config_uspstrook_schoon( $ruw ) {
    $ruw  = is_array( $ruw ) ? $ruw : array();
    $usps = array();
    foreach ( isset( $ruw['usps'] ) && is_array( $ruw['usps'] ) ? $ruw['usps'] : array() as $u ) {
        $u = trim( sanitize_text_field( (string) $u ) );
        if ( '' !== $u ) {
            $usps[] = mb_substr( $u, 0, 60 );
        }
    }
    $soorten = array();
    foreach ( isset( $ruw['soorten'] ) && is_array( $ruw['soorten'] ) ? $ruw['soorten'] : array( 'training', 'coaching' ) as $s ) {
        if ( in_array( $s, array( 'training', 'coaching', 'incompany' ), true ) ) {
            $soorten[] = (string) $s;
        }
    }
    $tel = isset( $ruw['telefoon'] ) ? preg_replace( '/[^0-9+ ]/', '', (string) $ruw['telefoon'] ) : '';
    return array(
        'usps'     => array_slice( $usps, 0, 3 ),
        'telefoon' => mb_substr( trim( (string) $tel ), 0, 20 ),
        'soorten'  => array_values( array_unique( $soorten ) ),
    );
}

/**
 * De instelling USP-strook van deze site.
 *
 * @return array{usps: string[], telefoon: string, soorten: string[]}
 */
function sfp_page_config_uspstrook_instelling() {
    $alle = function_exists( 'sfp_page_config_instellingen' ) ? sfp_page_config_instellingen() : array();
    return sfp_page_config_uspstrook_schoon( isset( $alle['uspstrook'] ) ? $alle['uspstrook'] : array() );
}

add_action( 'astra_header_after', 'sfp_page_config_uspstrook_toon', 5 );

/**
 * Print de USP-strook op een trainings- of coachpagina.
 */
function sfp_page_config_uspstrook_toon() {
    static $klaar = false;
    if ( $klaar || is_admin() || ! is_singular() || ! function_exists( 'sfp_page_config_onderdeel_aan' ) || ! sfp_page_config_onderdeel_aan( 'uspstrook' ) ) {
        return;
    }
    $klaar = true;
    $inst  = sfp_page_config_uspstrook_instelling();
    $soort = (string) get_post_meta( (int) get_queried_object_id(), 'sfp_page_type', true );
    if ( ! $inst['usps'] || ! in_array( $soort, $inst['soorten'], true ) ) {
        return;
    }
    echo sfp_page_config_uspstrook_html( $inst ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- opgebouwd met escaping.
}

/**
 * De USP-strook als HTML.
 *
 * @param  array $inst Instelling.
 * @return string
 */
function sfp_page_config_uspstrook_html( array $inst ) {
    $lijst = '';
    foreach ( $inst['usps'] as $u ) {
        $lijst .= '<li>' . esc_html( $u ) . '</li>';
    }
    $rechts = '';
    $cijfer = sfp_page_config_nlvorm_google_cijfer();
    $link   = sfp_page_config_nlvorm_google_link();
    if ( '' !== $cijfer && '' !== $link ) {
        $rechts .= '<a class="sfp-uspbalk__score" href="' . esc_url( $link ) . '" target="_blank" rel="noopener"><span class="sfp-uspbalk__sterren" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span><span><strong>' . esc_html( $cijfer ) . '</strong> ' . esc_html( sfp_page_config_tekst( 'held_op_google' ) ) . '</span></a>';
    }
    if ( '' !== $inst['telefoon'] ) {
        $rechts .= '<a class="sfp-uspbalk__bel" href="tel:' . esc_attr( str_replace( ' ', '', $inst['telefoon'] ) ) . '"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1l-2.3 2.2z"/></svg>' . esc_html( sfp_page_config_tekst( 'usp_bel' ) ) . '</a>';
    }
    $naam = html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' );
    return '<style id="sfp-uspstrook">' . sfp_page_config_uspstrook_css() . '</style>'
        . '<div class="sfp-uspbalk" role="region" aria-label="' . esc_attr( sfp_page_config_tekst_f( 'usp_regio', $naam ) ) . '">'
        . '<ul class="sfp-uspbalk__lijst">' . $lijst . '</ul>'
        . ( '' !== $rechts ? '<div class="sfp-uspbalk__rechts">' . $rechts . '</div>' : '' )
        . '</div>';
}

/**
 * De opmaak van de USP-strook. Staat los van nlvorm.css: de strook staat
 * buiten de inhoud en komt ook op pagina's zonder de nieuwe blokken.
 *
 * @return string
 */
function sfp_page_config_uspstrook_css() {
    $vink = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23000' d='M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z'/%3E%3C/svg%3E";
    return '.sfp-uspbalk{display:flex;flex-wrap:nowrap;justify-content:space-between;align-items:center;gap:10px 32px;padding:12px 40px;background:var(--sfp-rol-tint-3,var(--ast-global-color-8));color:var(--sfp-rol-primair,var(--ast-global-color-0));border-bottom:1px solid color-mix(in srgb,var(--sfp-rol-primair,var(--ast-global-color-0)) 10%,transparent);font-size:14px;font-weight:600;line-height:1.3}'
        . '.sfp-uspbalk__lijst{list-style:none;margin:0;padding:0;display:flex;flex-wrap:nowrap;gap:6px 24px;flex:1 1 auto;white-space:nowrap}'
        . '.sfp-uspbalk__lijst li{display:flex;align-items:center;gap:8px;margin:0}'
        . '.sfp-uspbalk__lijst li::before{content:"";width:16px;height:16px;flex:none;background:var(--sfp-rol-secundair,var(--ast-global-color-2));-webkit-mask:url("' . $vink . '") center/contain no-repeat;mask:url("' . $vink . '") center/contain no-repeat}'
        . '.sfp-uspbalk__rechts{display:flex;align-items:center;gap:20px;flex:none;margin-left:auto}'
        . '.sfp-uspbalk__score{display:inline-flex;align-items:center;gap:8px;color:inherit;text-decoration:none;font-weight:600}'
        . '.sfp-uspbalk__score strong{font-weight:700}'
        . '.sfp-uspbalk__score:hover span:last-child{text-decoration:underline;text-underline-offset:3px}'
        . '.sfp-uspbalk__sterren{color:#FBBC04;letter-spacing:1px}'
        . 'body:has(.sfp-held__bewijs .sfp-held__score) .sfp-uspbalk__score{display:none}'
        . '.sfp-uspbalk__bel{display:inline-flex;align-items:center;gap:8px;border:2px solid currentColor;border-radius:6px;padding:6px 14px;color:inherit;text-decoration:none;font-weight:700;line-height:1.2}'
        . '.sfp-uspbalk__bel svg{width:16px;height:16px}'
        . '.sfp-uspbalk__bel:hover,.sfp-uspbalk__bel:focus-visible{background:var(--sfp-rol-primair,var(--ast-global-color-0));color:var(--sfp-rol-wit,var(--ast-global-color-5))}'
        . '@media (max-width:1120px){.sfp-uspbalk__lijst li:nth-child(3){display:none}}'
        . '@media (max-width:980px){.sfp-uspbalk{padding:10px 24px}.sfp-uspbalk__lijst li:nth-child(n+2){display:none}.sfp-uspbalk__rechts{gap:14px}}'
        . '@media (max-width:700px){.sfp-uspbalk{display:none}}';
}

/* =========================================================================
 * Course-schema uit de groepskaarten
 * ====================================================================== */

/**
 * Bewaar de groepen voor het schema in de footer.
 *
 * @param  int        $post_id Pagina.
 * @param  array|null $groepen Groepen.
 * @param  array|null $info    Plaats en prijs.
 * @return array|null
 */
function sfp_page_config_nlvorm_schema_groepen( $post_id = 0, $groepen = null, $info = null ) {
    static $bewaard = null;
    if ( null !== $groepen && null === $bewaard ) {
        $bewaard = array(
            'post'    => (int) $post_id,
            'groepen' => $groepen,
            'info'    => $info,
        );
    }
    return $bewaard;
}

add_action( 'wp_footer', 'sfp_page_config_nlvorm_schema', 30 );

/**
 * Course met een CourseInstance per groep (LLMO-norm 4.5), alleen op een
 * pagina met Groepskaarten. Een filter zet het uit als een ander onderdeel
 * het Course-schema al levert.
 */
function sfp_page_config_nlvorm_schema() {
    $s = sfp_page_config_nlvorm_schema_groepen();
    if ( ! $s || ! (bool) apply_filters( 'sfp_page_config_course_schema', true, $s['post'] ) ) {
        return;
    }
    $data = sfp_page_config_nlvorm_schema_data( $s['post'], $s['groepen'], (array) $s['info'] );
    if ( $data ) {
        echo '<script type="application/ld+json" id="sfp-course-schema">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON van wp_json_encode.
    }
}

/**
 * Het Course-schema als array.
 *
 * @param  int   $post_id Pagina.
 * @param  array $groepen Groepen (sfp_page_config_nlvorm_groepen()).
 * @param  array $info    Plaats en prijs.
 * @return array
 */
function sfp_page_config_nlvorm_schema_data( $post_id, array $groepen, array $info ) {
    if ( ! $groepen ) {
        return array();
    }
    $site       = html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' );
    $aanbieder  = array(
        '@type' => 'Organization',
        'name'  => $site,
        'url'   => home_url( '/' ),
    );
    $instanties = array();
    foreach ( $groepen as $g ) {
        $instantie = array(
            '@type'      => 'CourseInstance',
            'name'       => sfp_page_config_tekst_f( 'groep', $g['nr'] ),
            'courseMode' => 'Onsite',
            'startDate'  => $g['data'][0],
            'endDate'    => $g['data'][ count( $g['data'] ) - 1 ],
        );
        if ( ! empty( $info['plaats'] ) ) {
            $instantie['location'] = array(
                '@type' => 'Place',
                'name'  => (string) $info['plaats'],
            );
        }
        if ( ! empty( $info['prijs'] ) ) {
            $instantie['offers'] = array(
                '@type'         => 'Offer',
                'price'         => (string) (int) $info['prijs'],
                'priceCurrency' => 'EUR',
                'availability'  => 'vol' === $g['status'] ? 'https://schema.org/SoldOut' : ( 'laatste' === $g['status'] ? 'https://schema.org/LimitedAvailability' : 'https://schema.org/InStock' ),
                'url'           => get_permalink( $post_id ),
                'validFrom'     => wp_date( 'Y-m-d' ),
            );
        }
        $instanties[] = $instantie;
    }
    $beschrijving = has_excerpt( $post_id ) ? wp_strip_all_tags( get_the_excerpt( $post_id ) ) : '';
    if ( '' === $beschrijving ) {
        // De kernzin onder de H1 zegt in één zin wat de training oplevert.
        $inhoud = (string) get_post_field( 'post_content', $post_id );
        if ( preg_match( '/<p[^>]*class="[^"]*is-style-sfp-kernzin[^"]*"[^>]*>(.*?)<\/p>/s', $inhoud, $m ) ) {
            $beschrijving = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' ) );
        }
    }
    $data         = array(
        '@context'          => 'https://schema.org',
        '@type'             => 'Course',
        '@id'               => get_permalink( $post_id ) . '#course',
        'name'              => html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ),
        'url'               => get_permalink( $post_id ),
        'provider'          => $aanbieder,
        'hasCourseInstance' => $instanties,
    );
    if ( '' !== $beschrijving ) {
        $data['description'] = $beschrijving;
    }
    if ( ! empty( $info['prijs'] ) ) {
        $data['offers'] = array(
            '@type'         => 'Offer',
            'price'         => (string) (int) $info['prijs'],
            'priceCurrency' => 'EUR',
            'category'      => 'Paid',
        );
    }
    return $data;
}
