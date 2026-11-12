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
            // Sinds 3.43.0: het aantal dagen van een groep is het aantal data.
            'dagen'  => count( $sm['data'] ),
        );
    }
    return $uit;
}

/* =========================================================================
 * Cursusvarianten (3.43.0): 1, 2 of 3 dagen met een eigen prijs
 *
 * Aan zodra in Cursusdata een prijs per aantal dagen staat. Het aantal dagen
 * van een groep volgt uit het aantal data. Zonder prijs per aantal dagen
 * blijft alles zoals voor 3.43.0.
 * ====================================================================== */

/**
 * Naam van een variant: "1 dag", "3 dagen", in de taal van de site.
 *
 * @param  int $dagen Aantal dagen.
 * @return string
 */
function sfp_page_config_variant_naam( $dagen ) {
    $dagen = (int) $dagen;
    return 1 === $dagen ? sfp_page_config_tekst( 'variant_dag' ) : sfp_page_config_tekst_f( 'variant_dagen', $dagen );
}

/**
 * De varianten van een training.
 *
 * @param  int $post_id Pagina.
 * @return array{aan: bool, info: array, groepen: array, lijst: int[], standaard: int}
 */
function sfp_page_config_cursus_varianten( $post_id ) {
    $post_id      = (int) $post_id;
    static $cache = array();
    if ( isset( $cache[ $post_id ] ) ) {
        return $cache[ $post_id ];
    }
    $info    = function_exists( 'sfp_page_config_cursusinfo' ) ? sfp_page_config_cursusinfo( $post_id ) : array(
        'plaats'  => '',
        'prijs'   => 0,
        'prijzen' => array(),
    );
    $groepen = sfp_page_config_nlvorm_groepen( $post_id );
    $aan     = function_exists( 'sfp_page_config_cursus_varianten_aan' ) && sfp_page_config_cursus_varianten_aan( $info );
    $lijst   = array();
    foreach ( $groepen as $g ) {
        $lijst[ (int) $g['dagen'] ] = (int) $g['dagen'];
    }
    ksort( $lijst, SORT_NUMERIC );
    $standaard = 0;
    foreach ( $groepen as $g ) {
        if ( 'vol' !== $g['status'] ) {
            $standaard = (int) $g['dagen'];
            break;
        }
    }
    if ( ! $standaard && $groepen ) {
        $standaard = (int) $groepen[0]['dagen'];
    }
    if ( ! $standaard && $aan ) {
        // Zonder groep: de langste variant met een prijs (bij DPS 3 dagen).
        $sleutels  = array_map( 'intval', array_keys( $info['prijzen'] ) );
        $standaard = $sleutels ? max( $sleutels ) : 0;
    }
    $cache[ $post_id ] = array(
        'aan'       => $aan,
        'info'      => $info,
        'groepen'   => $groepen,
        'lijst'     => array_values( $lijst ),
        'standaard' => $standaard,
    );
    return $cache[ $post_id ];
}

/**
 * De eerstvolgende start (niet vol) van een variant, als datumdelen.
 *
 * @param  array $groepen Groepen.
 * @param  int   $dagen   Aantal dagen; 0 is alle groepen.
 * @return array|null
 */
function sfp_page_config_variant_volgende( array $groepen, $dagen = 0 ) {
    foreach ( $groepen as $g ) {
        if ( 'vol' === $g['status'] || ( $dagen && (int) $g['dagen'] !== (int) $dagen ) ) {
            continue;
        }
        return sfp_page_config_nlvorm_datum( $g['data'][0] );
    }
    return null;
}

/**
 * De waarde "di 3 maart" met het lange deel van de maand alleen op brede
 * schermen, zoals de feitenkaart hem toont.
 *
 * @param  array $deel Datumdelen.
 * @return string HTML.
 */
function sfp_page_config_nlvorm_start_html( array $deel ) {
    $maand = $deel['maand'];
    $kort  = $deel['maand_kort'];
    $html  = 0 === strpos( $maand, $kort ) && strlen( $maand ) > strlen( $kort )
        ? esc_html( $kort ) . '<span class="sfp-lang">' . esc_html( substr( $maand, strlen( $kort ) ) ) . '</span>'
        : esc_html( $maand );
    return esc_html( $deel['dag'] . ' ' . $deel['nr'] ) . ' ' . $html;
}

/**
 * De keuze 1 • 2 • 3 dagen boven de groepskaarten. Alleen bij meer dan één
 * geplande variant.
 *
 * @param  array $v Varianten (sfp_page_config_cursus_varianten()).
 * @return string
 */
function sfp_page_config_variantkeuze_html( array $v ) {
    if ( count( $v['lijst'] ) < 2 ) {
        return '';
    }
    $knoppen = '';
    foreach ( $v['lijst'] as $dagen ) {
        $aan       = (int) $dagen === (int) $v['standaard'];
        $volgende  = sfp_page_config_variant_volgende( $v['groepen'], $dagen );
        $knoppen  .= '<button type="button" role="radio" class="sfp-variantkeuze__knop" data-sfp-variant="' . esc_attr( (string) $dagen ) . '"'
            . ' data-sfp-duur="' . esc_attr( sfp_page_config_variant_naam( $dagen ) ) . '"'
            . ' data-sfp-start="' . esc_attr( $volgende ? sfp_page_config_nlvorm_start_html( $volgende ) : '' ) . '"'
            . ' aria-checked="' . ( $aan ? 'true' : 'false' ) . '" tabindex="' . ( $aan ? '0' : '-1' ) . '">'
            . esc_html( sfp_page_config_variant_naam( $dagen ) ) . '</button>';
    }
    sfp_page_config_variant_script_nodig( true );
    return '<div class="sfp-variantkeuze" role="radiogroup" aria-label="' . esc_attr( sfp_page_config_tekst( 'variant_keuze' ) ) . '">' . $knoppen . '</div>';
}

/**
 * Markeer dat het script van de variantkeuze nodig is.
 *
 * @param  bool $zet True om te markeren.
 * @return bool
 */
function sfp_page_config_variant_script_nodig( $zet = false ) {
    static $nodig = false;
    if ( $zet ) {
        $nodig = true;
    }
    return $nodig;
}

add_action( 'wp_footer', 'sfp_page_config_variant_script', 22 );

/**
 * De variantkeuze wisselt de groepskaarten, de feitenkaart in de held en de
 * programmatab (Tabs met een tab per variant). Pijltjestoetsen zoals bij een
 * radiogroep. Zonder script blijft de standaardvariant staan.
 */
function sfp_page_config_variant_script() {
    if ( ! sfp_page_config_variant_script_nodig() ) {
        return;
    }
    $js = '(function(){var k=[].slice.call(document.querySelectorAll(".sfp-variantkeuze__knop"));if(!k.length)return;'
        . 'function z(n,f){k.forEach(function(b){var o=b.getAttribute("data-sfp-variant")===n;b.setAttribute("aria-checked",o?"true":"false");b.tabIndex=o?0:-1;if(o&&f)b.focus();});'
        . 'document.querySelectorAll(".sfp-groepen .sfp-kaart[data-sfp-variant]").forEach(function(c){c.hidden=c.getAttribute("data-sfp-variant")!==n;});'
        . 'var b=document.querySelector(".sfp-variantkeuze__knop[data-sfp-variant=\\""+n+"\\"]");'
        . 'if(b){var d=document.querySelector(".sfp-held__feit [data-sfp-feit=duur]");if(d)d.textContent=b.getAttribute("data-sfp-duur");'
        . 'var s=document.querySelector(".sfp-held__feit [data-sfp-feit=start]");if(s&&b.getAttribute("data-sfp-start"))s.innerHTML=b.getAttribute("data-sfp-start");}'
        . 'document.querySelectorAll(".sfp-tabs--variant [role=tab][data-sfp-dagen=\\""+n+"\\"]").forEach(function(t){if(t.getAttribute("aria-selected")!=="true")t.click();});}'
        . 'k.forEach(function(b,i){b.addEventListener("click",function(){z(b.getAttribute("data-sfp-variant"));});'
        . 'b.addEventListener("keydown",function(e){var r=e.key==="ArrowRight"||e.key==="ArrowDown"?1:e.key==="ArrowLeft"||e.key==="ArrowUp"?-1:0;if(!r)return;e.preventDefault();var x=k[(i+r+k.length)%k.length];z(x.getAttribute("data-sfp-variant"),1);});});'
        . '})();';
    echo '<script id="sfp-variantkeuze" nowprocket>' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste JS uit dit bestand.
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
    $css     = sfp_page_config_blokken_css_vangnet( 'sfp/groepskaarten' ) . sfp_page_config_blokken_css_vangnet( 'sfp/kaart' );

    $prijs = '';
    if ( $info['prijs'] > 0 ) {
        $prijs = '<p class="is-style-sfp-prijs">&euro;&nbsp;' . esc_html( number_format_i18n( $info['prijs'] ) ) . ' <span class="sfp-excl">' . esc_html( sfp_page_config_tekst( 'groep_excl_btw' ) ) . '</span></p>';
    }
    $statussen = array(
        ''        => sfp_page_config_tekst( 'groep_vrij' ),
        'laatste' => sfp_page_config_tekst( 'groep_laatste' ),
        'vol'     => sfp_page_config_tekst( 'groep_vol' ),
    );

    if ( ! $groepen ) {
        // Sinds 3.43.0: met cursusvarianten ook zonder groep een Course-schema
        // met het aanbod per variant.
        if ( function_exists( 'sfp_page_config_cursus_varianten_aan' ) && sfp_page_config_cursus_varianten_aan( $info ) ) {
            sfp_page_config_nlvorm_schema_groepen( $post_id, array(), $info );
        }
        // Geen groep gepland (3.31.0): één wachtlijstkaart, zonder datumrijen.
        $link = '<a class="sfp-tekstlink" href="#' . esc_attr( $doel ) . '" data-sfp-groep="wachtlijst">' . esc_html( sfp_page_config_tekst( 'groep_wachtlijst_link' ) ) . '</a>';
        return $css . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-groepen', 'sfp-groepen--leeg' ) ) . '>'
            . '<div class="sfp-kolommen sfp-kolommen--groepen sfp-kolommen--een"><div class="sfp-kaart sfp-kaart--accent sfp-klikkaart sfp-groep sfp-groep--klikbaar sfp-groep--wachtlijst">'
            . '<div class="sfp-groep__kopregel">' . ( sfp_page_config_nlvorm_wachtlijstkop() ? '' : '<p class="is-style-sfp-label">' . esc_html( sfp_page_config_tekst( 'groep_wachtlijst_label' ) ) . '</p>' )
            . '<span class="sfp-groep__status sfp-groep__status--wachtlijst">' . esc_html( sfp_page_config_tekst( 'groep_wachtlijst_status' ) ) . '</span></div>'
            . '<h3>' . esc_html( sfp_page_config_tekst( 'groep_wachtlijst_kop' ) ) . '</h3>'
            . '<p class="sfp-groep__zin">' . esc_html( sfp_page_config_tekst( 'groep_wachtlijst_zin' ) ) . '</p>'
            . ( '' !== $info['plaats'] ? '<p class="sfp-groep__meta">' . esc_html( $info['plaats'] ) . '</p>' : '' )
            . $prijs
            . $link
            . '</div></div></div>';
    }

    // Sinds 3.43.0: cursusvarianten. Per kaart de prijs van het aantal dagen,
    // de kaarten per variant met een eigen nummering, en de keuze erboven.
    $v        = sfp_page_config_cursus_varianten( $post_id );
    $per_var  = array();
    $kaarten  = '';
    foreach ( $groepen as $g ) {
        $eerste = sfp_page_config_nlvorm_datum( $g['data'][0] );
        $label  = sfp_page_config_tekst_f( 'groep', $g['nr'] );
        $attr   = '';
        if ( $v['aan'] ) {
            $dagen             = (int) $g['dagen'];
            $per_var[ $dagen ] = isset( $per_var[ $dagen ] ) ? $per_var[ $dagen ] + 1 : 1;
            $label             = sfp_page_config_tekst_f( 'groep', $per_var[ $dagen ] );
            $bedrag            = sfp_page_config_cursus_prijs( $v['info'], $dagen );
            $prijs             = '<p class="is-style-sfp-prijs"><span class="sfp-groep__duur">' . esc_html( sfp_page_config_variant_naam( $dagen ) ) . '</span>'
                . ( $bedrag > 0 ? ' &bull; &euro;&nbsp;' . esc_html( number_format_i18n( $bedrag ) ) . ' <span class="sfp-excl">' . esc_html( sfp_page_config_tekst( 'groep_excl_btw' ) ) . '</span>' : '' ) . '</p>';
            $attr              = ' data-sfp-variant="' . esc_attr( (string) $dagen ) . '"' . ( count( $v['lijst'] ) > 1 && $dagen !== (int) $v['standaard'] ? ' hidden' : '' );
        }
        $rijen  = '';
        foreach ( $g['data'] as $i => $d ) {
            $deel   = sfp_page_config_nlvorm_datum( $d );
            $rijen .= '<li><span class="sfp-groep__dag">' . esc_html( sfp_page_config_tekst_f( 'groep_dag', $i + 1 ) ) . '</span><span>' . esc_html( trim( $deel['dag'] . ' ' . $deel['nr'] . ' ' . $deel['maand'] ) ) . '</span></li>';
        }
        $vol     = 'vol' === $g['status'];
        // Hele kaart klikbaar (3.36.0, groepskaart-klikbaar): de enige link in de kaart rekt uit over de kaart; een volle groep heeft geen link.
        $kaarten .= '<div class="sfp-kaart sfp-kaart--accent sfp-groep' . ( $vol ? ' sfp-groep--vol' : ' sfp-klikkaart sfp-groep--klikbaar' ) . '"' . $attr . '>'
            . '<div class="sfp-groep__kopregel"><p class="is-style-sfp-label">' . esc_html( $label ) . '</p>'
            . '<span class="sfp-groep__status' . ( '' !== $g['status'] ? ' sfp-groep__status--' . esc_attr( $g['status'] ) : '' ) . '">' . esc_html( $statussen[ $g['status'] ] ) . '</span></div>'
            . '<h3>' . esc_html( sfp_page_config_tekst_f( 'groep_start', trim( $eerste['nr'] . ' ' . $eerste['maand'] ) ) ) . '</h3>'
            . '<ul class="sfp-groep__data">' . $rijen . '</ul>'
            . ( '' !== $info['plaats'] ? '<p class="sfp-groep__meta">' . esc_html( $info['plaats'] ) . '</p>' : '' )
            . $prijs
            . ( $vol ? '' : '<a class="sfp-tekstlink" href="#' . esc_attr( $doel ) . '" data-sfp-groep="' . esc_attr( (string) $g['nr'] ) . '">' . esc_html( $v['aan'] ? sfp_page_config_tekst_f( 'groep_inschrijven', $per_var[ (int) $g['dagen'] ] ) : sfp_page_config_tekst_f( 'groep_inschrijven', $g['nr'] ) ) . '</a>' )
            . '</div>';
    }
    $keuze = $v['aan'] ? sfp_page_config_variantkeuze_html( $v ) : '';

    $wachtlijst = '';
    if ( ! isset( $attrs['wachtlijst'] ) || ! empty( $attrs['wachtlijst'] ) ) {
        $wachtlijst = '<p class="sfp-wachtlijstregel">' . esc_html( sfp_page_config_tekst( 'groep_wachtlijst_vraag' ) ) . ' <a class="sfp-tekstlink" href="#' . esc_attr( $doel ) . '" data-sfp-groep="wachtlijst">' . esc_html( sfp_page_config_tekst( 'groep_wachtlijst_link' ) ) . '</a></p>';
    }

    sfp_page_config_nlvorm_schema_groepen( $post_id, $groepen, $info );

    return $css . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-groepen', '' !== $keuze ? 'sfp-groepen--varianten' : '' ) ) . '>'
        . $keuze
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
    $max    = 3;
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
            $feiten[] = array( $label_html, sfp_page_config_nlvorm_start_html( $deel ), 'start' );
            // Sinds 3.43.0, met cursusvarianten: de duur van deze groep als
            // tweede feit; de variantkeuze wisselt start en duur mee.
            if ( sfp_page_config_cursus_varianten_aan( (int) get_the_ID() ) ) {
                $feiten[] = array( esc_html( sfp_page_config_tekst( 'held_duur' ) ), esc_html( sfp_page_config_variant_naam( (int) $g['dagen'] ) ), 'duur' );
                $max      = 4;
            }
            break;
        }
        // Sinds 3.38.0 (nazorg 9): zonder groep staat er Wachtlijst, niet niets.
        if ( ! $feiten && sfp_page_config_nlvorm_alleen_wachtlijst( (int) get_the_ID() ) ) {
            $feiten[] = array( esc_html( sfp_page_config_tekst( 'held_volgende_start' ) ), esc_html( sfp_page_config_tekst( 'groep_wachtlijst_status' ) ) );
        }
    }
    foreach ( isset( $attrs['feiten'] ) && is_array( $attrs['feiten'] ) ? $attrs['feiten'] : array() as $f ) {
        $label  = is_array( $f ) && isset( $f['label'] ) ? trim( (string) $f['label'] ) : '';
        $waarde = is_array( $f ) && isset( $f['waarde'] ) ? trim( (string) $f['waarde'] ) : '';
        if ( '' !== $label && '' !== $waarde ) {
            $feiten[] = array( esc_html( $label ), esc_html( $waarde ) );
        }
    }
    $feiten = array_slice( $feiten, 0, $max );
    if ( ! $feiten ) {
        return '';
    }
    $uit = '';
    foreach ( $feiten as $f ) {
        $uit .= '<div><span class="is-style-sfp-label">' . $f[0] . '</span><strong' . ( isset( $f[2] ) && 4 === $max ? ' data-sfp-feit="' . esc_attr( $f[2] ) . '"' : '' ) . '>' . $f[1] . '</strong></div>';
    }
    $afbreken = ! empty( $attrs['afbreken'] ) ? ' sfp-held__feit--afbreken' : '';
    $afbreken .= count( $feiten ) > 3 ? ' sfp-held__feit--vier' : '';
    return '<div class="sfp-held__feit' . $afbreken . '" role="group" aria-label="' . esc_attr( sfp_page_config_tekst( 'held_kerngegevens' ) ) . '">' . $uit . '</div>';
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
 * Het aantal Google-reviews van de site, of 0.
 *
 * @since  3.38.0
 * @return int
 */
function sfp_page_config_nlvorm_google_aantal() {
    if ( '' === sfp_page_config_nlvorm_google_cijfer() ) {
        return 0;
    }
    $stand = sfp_page_config_reviews_stand();
    return isset( $stand['aantal'] ) ? (int) $stand['aantal'] : 0;
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
        // Sinds 3.38.0 met het aantal reviews (de feitenbron eist het aantal bij een 5,0).
        $aantal = sfp_page_config_nlvorm_google_aantal();
        $achter = $aantal > 1 ? ' ' . sfp_page_config_tekst_f( 'held_reviews_aantal', $aantal ) : ( 1 === $aantal ? ' ' . sfp_page_config_tekst( 'held_reviews_een' ) : '' );
        $score  = '<span class="sfp-held__sterren" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span><span><strong>' . esc_html( $cijfer ) . '</strong> ' . esc_html( sfp_page_config_tekst( 'held_op_google' ) . $achter ) . '</span>';
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
 * De H1 in de held wit krijgt altijd de klasse h1-xl (sinds 3.32.0), zodat
 * de kop op elke trainings- en coachpagina dezelfde maat heeft, ook als die
 * klasse in de editor ontbreekt (gemeten op DPS 22577, 8-11). De maat zelf
 * komt uit Extra CSS van de site.
 *
 * @param  string $html Tekstdeel van de held.
 * @return string
 */
function sfp_page_config_nlvorm_h1_xl( $html ) {
    return (string) preg_replace_callback(
        '/<h1\b([^>]*)>/i',
        function ( $m ) {
            $attr = $m[1];
            if ( preg_match( '/\bclass="([^"]*)"/i', $attr, $k ) ) {
                if ( preg_match( '/(^|\s)h1-xl(\s|$)/', $k[1] ) ) {
                    return $m[0];
                }
                return '<h1' . str_replace( $k[0], 'class="' . trim( $k[1] . ' h1-xl' ) . '"', $attr ) . '>';
            }
            return '<h1 class="h1-xl"' . $attr . '>';
        },
        (string) $html,
        1
    );
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
    $tekst  = sfp_page_config_nlvorm_h1_xl( $tekst );
    $tekst .= sfp_page_config_nlvorm_bewijsregel( $attrs );
    // Sinds 3.34.0 (O4) kan de feitenkaart uit, ook met cursusdata.
    $feit   = isset( $attrs['feitenkaart'] ) && false === $attrs['feitenkaart'] ? '' : sfp_page_config_nlvorm_feitenkaart( $attrs );
    if ( '' !== $beeld && '' !== $feit ) {
        $pos   = strrpos( $beeld, '</figure>' );
        $beeld = false !== $pos ? substr( $beeld, 0, $pos ) . $feit . substr( $beeld, $pos ) : $beeld . $feit;
    }
    sfp_page_config_nlvorm_kap_nodig( true );
    return '<div class="sfp-held__binnen"><div class="sfp-held__tekst">' . $tekst . '</div>' . $beeld . '</div>';
}

/**
 * De held "foto" (3.34.0, H1): de foto over de hele held met een verloop in
 * de primaire kleur, de tekst erop. Op een telefoon een aparte foto als die
 * gekozen is. De eerste afbeelding in de held is de foto; de bewijsregel
 * komt onder de knoppen (op een telefoon erboven, H7).
 *
 * @param  array         $attrs   Attributen.
 * @param  WP_Block|null $blok    Het blok.
 * @param  string        $content Binnenblokken als geheel (terugval).
 * @return string
 */
function sfp_page_config_nlvorm_held_foto( $attrs, $blok, $content ) {
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
    $tekst = sfp_page_config_nlvorm_h1_xl( $tekst ) . sfp_page_config_nlvorm_bewijsregel( $attrs );
    if ( '' !== $beeld ) {
        // De foto is het grootste beeld boven de vouw: niet lui laden.
        $beeld = preg_replace( '/\sloading="lazy"/', '', $beeld );
        if ( false === strpos( $beeld, 'fetchpriority=' ) ) {
            $beeld = preg_replace( '/<img\b/', '<img fetchpriority="high"', $beeld, 1 );
        }
        $mobiel = isset( $attrs['mobielId'] ) ? (int) $attrs['mobielId'] : 0;
        $src    = $mobiel > 0 && wp_attachment_is_image( $mobiel ) ? wp_get_attachment_image_srcset( $mobiel, 'large' ) : '';
        if ( ! $src && $mobiel > 0 && wp_attachment_is_image( $mobiel ) ) {
            $src = (string) wp_get_attachment_image_url( $mobiel, 'large' );
        }
        if ( $src ) {
            $beeld = preg_replace( '/(<img\b[^>]*>)/', '<picture><source media="(max-width: 700px)" srcset="' . esc_attr( $src ) . '" sizes="100vw">$1</picture>', $beeld, 1 );
        }
        $beeld = preg_replace( '/class="([^"]*wp-block-image[^"]*)"/', 'class="$1 sfp-held__achter"', $beeld, 1 );
    }
    // De standaard van de held (50% 30%) is hier rechts, iets boven: daar staat de spreker op de foto.
    $focus = sfp_page_config_blok_attr( $attrs, 'focus', '68% 40%' );
    $stijl = '--sfp-held-focus:' . sfp_page_config_nlvorm_focus( '50% 30%' === $focus ? '68% 40%' : $focus )
        . ';--sfp-held-focus-m:' . sfp_page_config_nlvorm_focus( sfp_page_config_blok_attr( $attrs, 'focusMobiel', '60% 20%' ) );
    return str_replace( '<section ', '<section style="' . esc_attr( $stijl ) . '" ', '<section ' . sfp_page_config_blok_omhulling( array( 'sfp-held', 'sfp-held--foto', 'held-bewijs' ), sfp_page_config_blok_attr( $attrs, 'anker' ) ) . '>' )
        . $beeld . '<div class="sfp-held__binnen">' . $tekst . '</div></section>';
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
        // Telefoon (3.31.0, C7): de H1 krimpt in stappen van 0,5 px tot het
        // langste woord in zijn vak past, minimaal 20 px; vóór de kapcorrectie.
        // Sinds 3.31.4 met voorrang (important): de maat uit Extra CSS (h1-xl)
        // heeft zelf voorrang, en won tot nu van de verkleining.
        . 'function p(){document.querySelectorAll(".sfp-held--wit h1").forEach(function(h){h.style.removeProperty("font-size");if(innerWidth>700)return;'
        . 'var f=parseFloat(getComputedStyle(h).fontSize);function b(){var r=h.getBoundingClientRect(),g=document.createRange();g.selectNodeContents(h);var q=g.getClientRects();for(var i=0;i<q.length;i++){if(q[i].right>r.right+0.5)return true;}return false;}'
        . 'while(b()&&f>20){f-=0.5;h.style.setProperty("font-size",f+"px","important");}});}'
        . 'function a(){p();k();}'
        . 'if(document.fonts&&document.fonts.ready){document.fonts.ready.then(a);}else{a();}'
        . 'var t;window.addEventListener("resize",function(){clearTimeout(t);t=setTimeout(a,150);});})();';
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
 * Twee of drie dagen naast elkaar gaan samen open en dicht, zodat ze even
 * hoog blijven. Bij meer dagen staan ze gestapeld en gaat elke dag los.
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
 * Een focuspunt als "x% y%", anders de standaard (3.31.0).
 *
 * @param  string $focus Focuspunt.
 * @return string
 */
function sfp_page_config_nlvorm_focus( $focus ) {
    return preg_match( '/^\d{1,3}% \d{1,3}%$/', (string) $focus ) ? (string) $focus : '50% 30%';
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
    if ( preg_match( '/<!-- wp:sfp\/held \{[^}]*"variant":"(wit|foto)"/', $inhoud ) ) {
        $delen[] = 'nlvorm-held';
    }
    // Sinds 3.34.0: home (held foto, slot, kaart met beeld, reviews als
    // carrousel, laatste artikelen) en Over (team, tabs als kaart, netwerk).
    if ( preg_match( '/<!-- wp:sfp\/held \{[^}]*"variant":"foto"|<!-- wp:sfp\/kolommen \{[^}]*"indeling":"slot"|<!-- wp:sfp\/kolommen \{[^}]*"carrousel":true|<!-- wp:sfp\/kaart \{[^}]*"beeld":true|<!-- wp:sfp\/(artikelen|google-samenvatting)/', $inhoud ) ) {
        $delen[] = 'nlvorm-home';
    }
    if ( preg_match( '/<!-- wp:sfp\/kolommen \{[^}]*"indeling":"slot"/', $inhoud ) ) {
        $delen[] = 'nlvorm-slot';
    }
    if ( preg_match( '/<!-- wp:sfp\/tabs \{[^}]*"vorm":"kaart"|<!-- wp:sfp\/(team|netwerk)[ \/-]/', $inhoud ) ) {
        $delen[] = 'nlvorm-over';
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
    if ( preg_match( '/<!-- wp:sfp\/kolommen \{[^}]*"indeling":"pakketten"|<!-- wp:sfp\/uitklap \{[^}]*"variant":"vergelijk"/', $inhoud ) ) {
        $delen[] = 'nlvorm-coach';
    }
    // Sinds 3.53.0: Portfolio (Tabs) en Slot kort (Kolommen), zie includes/incompany.php.
    if ( preg_match( '/<!-- wp:sfp\/tabs \{[^}]*"vorm":"portfolio"|<!-- wp:sfp\/kolommen \{[^}]*"indeling":"kort"/', $inhoud ) ) {
        $delen[] = 'lijst';
        $delen[] = 'review-kaart';
        $delen[] = 'incompany';
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
        if ( in_array( $s, sfp_page_config_paginasoorten(), true ) ) {
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
    $inst  = sfp_page_config_uspstrook_pagina( (int) get_queried_object_id() );
    if ( ! $inst ) {
        return;
    }
    echo sfp_page_config_uspstrook_html( $inst ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- opgebouwd met escaping.
}

/**
 * De USP-strook voor een pagina (sinds 3.31.4 per pagina instelbaar).
 *
 * Uit op de pagina: geen strook. Eigen USP's op de pagina: die, ook als de
 * soort in SFP Beheer niet aanstaat. Anders de set van de site, op de soorten
 * die in SFP Beheer aanstaan. Belknop en Google-cijfer komen altijd van de site.
 *
 * @param  int $post_id Pagina.
 * @return array|null Instelling om te tonen, of null.
 */
function sfp_page_config_uspstrook_pagina( $post_id ) {
    $post_id = (int) $post_id;
    if ( ! $post_id || '1' === (string) get_post_meta( $post_id, 'sfp_usp_uit', true ) ) {
        return null;
    }
    $inst  = sfp_page_config_uspstrook_instelling();
    $eigen = array();
    foreach ( array( 'sfp_usp_1', 'sfp_usp_2', 'sfp_usp_3' ) as $sleutel ) {
        $u = mb_substr( trim( sanitize_text_field( (string) get_post_meta( $post_id, $sleutel, true ) ) ), 0, 60 );
        if ( '' !== $u ) {
            $eigen[] = $u;
        }
    }
    if ( $eigen ) {
        $inst['usps'] = $eigen;
        return $inst;
    }
    $soort = (string) get_post_meta( $post_id, 'sfp_page_type', true );
    if ( ! $inst['usps'] || ! in_array( $soort, $inst['soorten'], true ) ) {
        return null;
    }
    return $inst;
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
        $rechts .= '<a class="sfp-uspbalk__bel" href="tel:' . esc_attr( function_exists( 'sfp_page_config_telefoon' ) ? sfp_page_config_telefoon( $inst['telefoon'] )['tel'] : str_replace( ' ', '', $inst['telefoon'] ) ) . '"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1l-2.3 2.2z"/></svg>' . esc_html( sfp_page_config_tekst( 'usp_bel' ) ) . '</a>';
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
    // Sinds 3.43.0: met cursusvarianten een aanbod per variant, ook zonder groep.
    $varianten = function_exists( 'sfp_page_config_cursus_varianten_aan' ) && sfp_page_config_cursus_varianten_aan( $info );
    if ( ! $groepen && ! $varianten ) {
        return array();
    }
    $site       = html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' );
    $aanbieder  = array(
        '@type' => 'Organization',
        'name'  => $site,
        'url'   => home_url( '/' ),
    );
    $instanties = array();
    $per_var    = array();
    foreach ( $groepen as $g ) {
        $instantie = array(
            '@type'      => 'CourseInstance',
            'name'       => sfp_page_config_tekst_f( 'groep', $g['nr'] ),
            'courseMode' => 'Onsite',
            'startDate'  => $g['data'][0],
            'endDate'    => $g['data'][ count( $g['data'] ) - 1 ],
        );
        $bedrag = ! empty( $info['prijs'] ) ? (int) $info['prijs'] : 0;
        if ( $varianten ) {
            $dagen             = count( $g['data'] );
            $per_var[ $dagen ] = isset( $per_var[ $dagen ] ) ? $per_var[ $dagen ] + 1 : 1;
            $instantie['name'] = sfp_page_config_tekst_f( 'groep', $per_var[ $dagen ] ) . ' (' . sfp_page_config_variant_naam( $dagen ) . ')';
            $bedrag            = sfp_page_config_cursus_prijs( $info, $dagen );
            $uren              = sfp_page_config_schema_uren( $g );
            if ( $uren > 0 ) {
                $instantie['courseWorkload'] = 'PT' . ( $uren * $dagen ) . 'H';
            }
        }
        if ( ! empty( $info['plaats'] ) ) {
            $instantie['location'] = array(
                '@type' => 'Place',
                'name'  => (string) $info['plaats'],
            );
        }
        if ( $bedrag > 0 ) {
            $instantie['offers'] = array(
                '@type'         => 'Offer',
                'price'         => (string) $bedrag,
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
    if ( ! $instanties ) {
        unset( $data['hasCourseInstance'] );
    }
    if ( '' !== $beschrijving ) {
        $data['description'] = $beschrijving;
    }
    if ( $varianten ) {
        // Een aanbod per variant: naam (aantal dagen) en prijs.
        $data['offers'] = array();
        foreach ( $info['prijzen'] as $dagen => $bedrag ) {
            $data['offers'][] = array(
                '@type'         => 'Offer',
                'name'          => sfp_page_config_variant_naam( (int) $dagen ),
                'price'         => (string) (int) $bedrag,
                'priceCurrency' => 'EUR',
                'category'      => 'Paid',
                'url'           => get_permalink( $post_id ),
            );
        }
    } elseif ( ! empty( $info['prijs'] ) ) {
        $data['offers'] = array(
            '@type'         => 'Offer',
            'price'         => (string) (int) $info['prijs'],
            'priceCurrency' => 'EUR',
            'category'      => 'Paid',
        );
    }
    return $data;
}

/**
 * Lesuren per dag van een groep (3.43.0): de lestijd van de groep, anders de
 * standaardlestijd van de site. 0 als die onbekend is.
 *
 * @param  array $g Groep.
 * @return int
 */
function sfp_page_config_schema_uren( array $g ) {
    $start = '' !== ( $g['start'] ?? '' ) ? (string) $g['start'] : (string) sfp_page_config_get_setting( 'cursus_tijd_default_start', '' );
    $eind  = '' !== ( $g['eind'] ?? '' ) ? (string) $g['eind'] : (string) sfp_page_config_get_setting( 'cursus_tijd_default_eind', '' );
    if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', $start, $a ) || ! preg_match( '/^(\d{1,2}):(\d{2})$/', $eind, $b ) ) {
        return 0;
    }
    $minuten = ( (int) $b[1] * 60 + (int) $b[2] ) - ( (int) $a[1] * 60 + (int) $a[2] );
    return $minuten > 0 ? (int) round( $minuten / 60 ) : 0;
}

/* =========================================================================
 * Alleen de wachtlijst (3.31.0): Groepskaarten zonder geplande groep
 * ====================================================================== */

/**
 * Staat er op deze trainingspagina Groepskaarten zonder geplande groep? Dan
 * heet de knop onderaan "Zet mij op de wachtlijst". Sinds 3.32.0 wisselt het
 * inschrijfformulier dan naar het wachtlijstformulier uit SFP Beheer
 * (includes/wachtlijst.php); alleen zonder dat formulier blijft het gewone
 * formulier staan, met in het keuzemenu alleen de wachtlijst en die tekst
 * op de verzendknop.
 *
 * @param  int $post_id Pagina.
 * @return bool
 */
function sfp_page_config_nlvorm_alleen_wachtlijst( $post_id ) {
    $post_id = (int) $post_id;
    if ( ! $post_id || 'training' !== get_post_meta( $post_id, 'sfp_page_type', true ) ) {
        return false;
    }
    if ( false === strpos( (string) get_post_field( 'post_content', $post_id ), '<!-- wp:sfp/groepskaarten' ) ) {
        return false;
    }
    return ! sfp_page_config_nlvorm_groepen( $post_id );
}

add_filter( 'render_block', 'sfp_page_config_nlvorm_wachtlijstknop', 10, 2 );

/**
 * De verzendknop van het formulier heet "Zet mij op de wachtlijst".
 *
 * @param  string $html  Getekend blok.
 * @param  array  $block Blok.
 * @return string
 */
function sfp_page_config_nlvorm_wachtlijstknop( $html, $block ) {
    if ( empty( $block['blockName'] ) || 'srfm/form' !== $block['blockName'] || is_admin() ) {
        return $html;
    }
    $post_id = function_exists( 'sfp_page_config_wachtlijst_pagina' ) ? sfp_page_config_wachtlijst_pagina() : 0;
    if ( ! $post_id || ! sfp_page_config_nlvorm_alleen_wachtlijst( $post_id ) ) {
        return $html;
    }
    // Sinds 3.32.0: het wachtlijstformulier heeft zijn eigen knop, en een
    // ander formulier op de pagina (zoals een leadmagnet) houdt de zijne.
    if ( function_exists( 'sfp_page_config_wachtlijst_wisselt' ) && sfp_page_config_wachtlijst_wisselt( $post_id ) ) {
        return $html;
    }
    if ( ! sfp_page_config_nlvorm_is_inschrijfformulier( $block, $html ) ) {
        return $html;
    }
    $tekst = esc_html( sfp_page_config_tekst( 'groep_wachtlijst_link' ) );
    return (string) preg_replace( '/(<div class="srfm-submit-wrap">)\s*[^<]*?\s*(<div class="srfm-loader")/', '$1' . $tekst . '$2', $html, 1 );
}

/**
 * Is dit blok het inschrijfformulier? Het formulier uit SFP Beheer
 * (Cursusdata), of zonder die instelling een formulier met een keuzemenu
 * voor het startmoment.
 *
 * @since  3.32.0
 * @param  array  $block Blok.
 * @param  string $html  Getekend blok.
 * @return bool
 */
function sfp_page_config_nlvorm_is_inschrijfformulier( $block, $html ) {
    $inschrijf = function_exists( 'sfp_page_config_wachtlijst_formulier_id' ) ? sfp_page_config_wachtlijst_formulier_id( 'inschrijf_formulier' ) : 0;
    if ( $inschrijf ) {
        return ! empty( $block['attrs']['id'] ) && (int) $block['attrs']['id'] === $inschrijf;
    }
    return false !== strpos( (string) $html, 'sfp-startmoment-select' )
        || (bool) preg_match( '/srfm-slug-(datum|data|datas|startmoment|startmomenten|startdatum|startdata|startdate|startdates|date|dates|turma)(-\d+)?[\s"]/', (string) $html );
}

/* =========================================================================
 * Wachtlijststand op de pagina (3.38.0, nazorg 8)
 * ====================================================================== */

/**
 * Is de kop boven de Groepskaarten in de wachtlijststand vervangen? Dan
 * laat de wachtlijstkaart haar eigen label weg, zodat het er niet twee keer
 * staat.
 *
 * @param  bool|null $zet True om te markeren.
 * @return bool
 */
function sfp_page_config_nlvorm_wachtlijstkop( $zet = null ) {
    static $vervangen = false;
    if ( null !== $zet ) {
        $vervangen = (bool) $zet;
    }
    return $vervangen;
}

/**
 * Vervang de tekst binnen het eerste element van een getekend blok.
 *
 * @param  array  $blok  Blok uit parse_blocks.
 * @param  string $tag   Element (a, h2, p).
 * @param  string $tekst Nieuwe tekst.
 * @return array
 */
function sfp_page_config_nlvorm_bloktekst( array $blok, $tag, $tekst ) {
    $patroon = '/(<' . $tag . '\b[^>]*>)(.*?)(<\/' . $tag . '>)/s';
    $nieuw   = '${1}' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), esc_html( $tekst ) ) . '${3}';
    if ( isset( $blok['innerHTML'] ) ) {
        $blok['innerHTML'] = (string) preg_replace( $patroon, $nieuw, (string) $blok['innerHTML'], 1 );
    }
    if ( isset( $blok['innerContent'] ) && is_array( $blok['innerContent'] ) ) {
        foreach ( $blok['innerContent'] as $i => $deel ) {
            if ( is_string( $deel ) && preg_match( $patroon, $deel ) ) {
                $blok['innerContent'][ $i ] = (string) preg_replace( $patroon, $nieuw, $deel, 1 );
                break;
            }
        }
    }
    return $blok;
}

add_filter( 'render_block_data', 'sfp_page_config_nlvorm_wachtlijststand', 10, 3 );

/**
 * Een training zonder geplande groep: wat op de pagina naar het inschrijven
 * wijst, wisselt mee.
 * - een knop naar het inschrijfanker heet Zet mij op de wachtlijst;
 * - de laatste kop vóór de Groepskaarten wordt het wachtlijstlabel
 *   (Volgende groep), en de wachtlijstkaart laat dat label dan weg;
 *
 * @param  array         $blok   Blok.
 * @param  array         $bron   Bronblok.
 * @param  WP_Block|null $ouder  Ouderblok.
 * @return array
 */
function sfp_page_config_nlvorm_wachtlijststand( $blok, $bron = array(), $ouder = null ) {
    $naam = isset( $blok['blockName'] ) ? (string) $blok['blockName'] : '';
    if ( ! in_array( $naam, array( 'core/button', 'core/heading', 'core/paragraph' ), true ) || is_admin() || wp_is_json_request() ) {
        return $blok;
    }
    $post_id = (int) get_the_ID();
    if ( ! $post_id || ! sfp_page_config_nlvorm_alleen_wachtlijst( $post_id ) ) {
        return $blok;
    }
    $in = function_exists( 'sfp_page_config_anker_schoon' ) ? sfp_page_config_anker_schoon( sfp_page_config_tekst( 'anker_inschrijven' ) ) : 'inschrijven';
    $in = '' !== $in ? $in : 'inschrijven';

    if ( 'core/button' === $naam ) {
        $html = isset( $blok['innerHTML'] ) ? (string) $blok['innerHTML'] : '';
        if ( preg_match( '/href="#(' . preg_quote( $in, '/' ) . '|inschrijven)"/', $html ) ) {
            return sfp_page_config_nlvorm_bloktekst( $blok, 'a', sfp_page_config_tekst( 'groep_wachtlijst_link' ) );
        }
        return $blok;
    }
    if ( ! $ouder instanceof WP_Block || empty( $ouder->parsed_block['innerBlocks'] ) ) {
        return $blok;
    }
    $broers = $ouder->parsed_block['innerBlocks'];

    if ( 'core/heading' === $naam ) {
        // De laatste kop vóór de Groepskaarten in dezelfde ouder.
        $laatste_kop = null;
        foreach ( $broers as $i => $b ) {
            if ( 'core/heading' === ( $b['blockName'] ?? '' ) ) {
                $laatste_kop = $i;
            }
            if ( 'sfp/groepskaarten' === ( $b['blockName'] ?? '' ) ) {
                if ( null !== $laatste_kop && ( $broers[ $laatste_kop ]['innerHTML'] ?? '' ) === ( $blok['innerHTML'] ?? null ) ) {
                    sfp_page_config_nlvorm_wachtlijstkop( true );
                    return sfp_page_config_nlvorm_bloktekst( $blok, 'h[1-6]', sfp_page_config_tekst( 'groep_wachtlijst_label' ) );
                }
                break;
            }
        }
        return $blok;
    }

    return $blok;
}

add_filter( 'render_block', 'sfp_page_config_nlvorm_wachtlijst_kernzin', 10, 2 );

/**
 * De kernzin van de sectie met het inschrijfanker wordt in de
 * wachtlijststand de wachtlijstzin (3.38.1: ook als de kernzin dieper in de
 * sectie staat, zoals in kolommen).
 *
 * @param  string $html  Getekend blok.
 * @param  array  $block Blok.
 * @return string
 */
function sfp_page_config_nlvorm_wachtlijst_kernzin( $html, $block ) {
    if ( 'sfp/sectie' !== ( $block['blockName'] ?? '' ) || is_admin() || wp_is_json_request() ) {
        return $html;
    }
    $in    = function_exists( 'sfp_page_config_anker_schoon' ) ? sfp_page_config_anker_schoon( sfp_page_config_tekst( 'anker_inschrijven' ) ) : 'inschrijven';
    $anker = (string) ( $block['attrs']['anker'] ?? '' );
    if ( '' === $anker || ! in_array( $anker, array( $in, 'inschrijven' ), true ) ) {
        return $html;
    }
    $post_id = (int) get_the_ID();
    if ( ! $post_id || ! sfp_page_config_nlvorm_alleen_wachtlijst( $post_id ) ) {
        return $html;
    }
    $zin = str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), esc_html( sfp_page_config_tekst( 'groep_wachtlijst_kernzin' ) ) );
    return (string) preg_replace( '/(<p\b[^>]*class="[^"]*is-style-sfp-kernzin[^"]*"[^>]*>)(.*?)(<\/p>)/s', '${1}' . $zin . '${3}', (string) $html, 1 );
}

/* =========================================================================
 * Rolregel uit de labelnaam (3.31.0, C9)
 * ====================================================================== */

add_filter( 'render_block', 'sfp_page_config_nlvorm_labelnaam', 10, 2 );

/**
 * In een regel Naam met portret wordt [label] of [labelnaam] de naam van de
 * site, zodat "Oprichter van [label]" nooit vaste tekst is.
 *
 * @param  string $html  Getekend blok.
 * @param  array  $block Blok.
 * @return string
 */
function sfp_page_config_nlvorm_labelnaam( $html, $block ) {
    if ( empty( $block['blockName'] ) || 'core/paragraph' !== $block['blockName'] || false === strpos( (string) $html, '[label' ) ) {
        return $html;
    }
    if ( false === strpos( (string) ( $block['attrs']['className'] ?? '' ), 'is-style-sfp-wie' ) ) {
        return $html;
    }
    $naam = esc_html( html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ) );
    return str_replace( array( '[labelnaam]', '[label]' ), $naam, $html );
}

/* =========================================================================
 * Service-schema voor coaching (3.31.0, C5)
 * ====================================================================== */

add_action( 'wp_footer', 'sfp_page_config_nlvorm_service_schema', 30 );

/**
 * Service met een Offer per pakket, zonder prijs (besluit Stephan 7-11), op
 * een coachpagina met Kolommen, Pakketten.
 */
function sfp_page_config_nlvorm_service_schema() {
    if ( is_admin() || ! is_singular() ) {
        return;
    }
    $post_id = (int) get_queried_object_id();
    if ( 'coaching' !== get_post_meta( $post_id, 'sfp_page_type', true ) || ! (bool) apply_filters( 'sfp_page_config_service_schema', true, $post_id ) ) {
        return;
    }
    $data = sfp_page_config_nlvorm_service_data( $post_id, (string) get_post_field( 'post_content', $post_id ) );
    if ( $data ) {
        echo '<script type="application/ld+json" id="sfp-service-schema">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON van wp_json_encode.
    }
}

/**
 * De pakketten uit de inhoud: per kaart in Kolommen, Pakketten de kop (H3)
 * en de omvang (alinea Omvang).
 *
 * @param  string $inhoud Berichtinhoud.
 * @return array<int, array{naam: string, omvang: string}>
 */
function sfp_page_config_nlvorm_pakketten( $inhoud ) {
    $uit = array();
    $zoek = function ( $blokken ) use ( &$zoek, &$uit ) {
        foreach ( $blokken as $b ) {
            if ( 'sfp/kolommen' === ( $b['blockName'] ?? '' ) && 'pakketten' === ( $b['attrs']['indeling'] ?? '' ) ) {
                foreach ( $b['innerBlocks'] as $kaart ) {
                    $naam   = '';
                    $omvang = '';
                    foreach ( $kaart['innerBlocks'] ?? array() as $kind ) {
                        $tekst = trim( html_entity_decode( wp_strip_all_tags( (string) ( $kind['innerHTML'] ?? '' ) ), ENT_QUOTES, 'UTF-8' ) );
                        if ( '' === $naam && 'core/heading' === ( $kind['blockName'] ?? '' ) ) {
                            $naam = $tekst;
                        } elseif ( '' === $omvang && false !== strpos( (string) ( $kind['attrs']['className'] ?? '' ), 'is-style-sfp-omvang' ) ) {
                            $omvang = $tekst;
                        }
                    }
                    if ( '' !== $naam ) {
                        $uit[] = array(
                            'naam'   => $naam,
                            'omvang' => $omvang,
                        );
                    }
                }
            } elseif ( ! empty( $b['innerBlocks'] ) ) {
                $zoek( $b['innerBlocks'] );
            }
        }
    };
    $zoek( parse_blocks( (string) $inhoud ) );
    return $uit;
}

/**
 * Het Service-schema als array, of leeg zonder pakketten.
 *
 * @param  int    $post_id Pagina.
 * @param  string $inhoud  Berichtinhoud.
 * @return array
 */
function sfp_page_config_nlvorm_service_data( $post_id, $inhoud ) {
    $pakketten = sfp_page_config_nlvorm_pakketten( $inhoud );
    if ( ! $pakketten ) {
        return array();
    }
    $offers = array();
    foreach ( $pakketten as $p ) {
        $offer = array(
            '@type' => 'Offer',
            'name'  => $p['naam'],
        );
        if ( '' !== $p['omvang'] ) {
            $offer['description'] = $p['omvang'];
        }
        $offers[] = $offer;
    }
    $data = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'Service',
        '@id'        => get_permalink( $post_id ) . '#service',
        'name'       => html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ),
        'url'        => get_permalink( $post_id ),
        'provider'   => array( '@id' => home_url( '/' ) . '#organization' ),
        'performer'  => array( '@id' => sfp_page_config_coach_id() ),
        'areaServed' => array(
            '@type' => 'Country',
            'name'  => 'NL',
        ),
        'offers'     => $offers,
    );
    if ( preg_match( '/<p[^>]*class="[^"]*is-style-sfp-kernzin[^"]*"[^>]*>(.*?)<\/p>/s', (string) $inhoud, $m ) ) {
        $data['description'] = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' ) );
    }
    return $data;
}

/* =========================================================================
 * Coach in de gegevens voor Google (sinds 3.31.1)
 * ====================================================================== */

/**
 * De standaard van het netwerk voor de coach: de site van Stephan.
 *
 * @return string
 */
function sfp_page_config_coach_norm() {
    return 'https://stephanborggreve.com/';
}

/**
 * Het @id van de coach (performer in het Service-schema): de ingestelde
 * site met #person, zoals de Person die SureRank op die site zet.
 *
 * @return string
 */
function sfp_page_config_coach_id() {
    $url   = trim( (string) sfp_page_config_get_setting( 'coach_persoon', '' ) );
    $delen = wp_parse_url( '' !== $url ? $url : sfp_page_config_coach_norm() );
    if ( empty( $delen['host'] ) ) {
        $delen = wp_parse_url( sfp_page_config_coach_norm() );
    }
    return ( isset( $delen['scheme'] ) ? $delen['scheme'] : 'https' ) . '://' . $delen['host'] . '/#person';
}

/* =========================================================================
 * Geen tweede aanbod van SureRank (sinds 3.31.1)
 *
 * Op een pagina waar deze plugin het Course- of Service-schema levert, haalt
 * de plugin de Course en de Service uit het schema van SureRank. Anders krijgt
 * Google twee beschrijvingen van hetzelfde aanbod die elkaar deels
 * tegenspreken. De rest van het schema van SureRank blijft staan.
 * ====================================================================== */

add_action( 'surerank_print_meta', 'sfp_page_config_nlvorm_surerank_begin', 9 );
add_action( 'surerank_print_meta', 'sfp_page_config_nlvorm_surerank_eind', 11 );

/**
 * Levert deze plugin op de getoonde pagina een eigen aanbod (Course of Service)?
 *
 * @param  int $post_id Pagina; 0 is de getoonde pagina.
 * @return bool
 */
function sfp_page_config_nlvorm_eigen_aanbod( $post_id = 0 ) {
    if ( ! $post_id ) {
        if ( is_admin() || ! is_singular() ) {
            return false;
        }
        $post_id = (int) get_queried_object_id();
    }
    $soort = get_post_meta( $post_id, 'sfp_page_type', true );
    if ( 'training' === $soort ) {
        // Sinds 3.43.0 ook zonder groep als de cursusvarianten aan staan.
        return has_block( 'sfp/groepskaarten', $post_id )
            && ( array() !== sfp_page_config_nlvorm_groepen( $post_id ) || sfp_page_config_cursus_varianten_aan( $post_id ) )
            && (bool) apply_filters( 'sfp_page_config_course_schema', true, $post_id );
    }
    // Incompany met een van de nieuwe bouwstenen (3.53.0): de plugin levert de Service.
    if ( 'incompany' === $soort ) {
        return function_exists( 'sfp_page_config_incompany_schema_aan' ) && sfp_page_config_incompany_schema_aan( $post_id );
    }
    if ( 'coaching' === $soort ) {
        return (bool) apply_filters( 'sfp_page_config_service_schema', true, $post_id )
            && array() !== sfp_page_config_nlvorm_service_data( $post_id, (string) get_post_field( 'post_content', $post_id ) );
    }
    return false;
}

/**
 * Vang de uitvoer van SureRank op een pagina met eigen aanbod.
 */
function sfp_page_config_nlvorm_surerank_begin() {
    $GLOBALS['sfp_page_config_surerank_buffer'] = false;
    if ( ! (bool) apply_filters( 'sfp_page_config_surerank_aanbod_weg', true ) || ! sfp_page_config_nlvorm_eigen_aanbod() ) {
        return;
    }
    ob_start();
    $GLOBALS['sfp_page_config_surerank_buffer'] = true;
}

/**
 * Geef de uitvoer van SureRank terug zonder Course en Service.
 */
function sfp_page_config_nlvorm_surerank_eind() {
    if ( empty( $GLOBALS['sfp_page_config_surerank_buffer'] ) ) {
        return;
    }
    $GLOBALS['sfp_page_config_surerank_buffer'] = false;
    echo sfp_page_config_nlvorm_zonder_aanbod( (string) ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- uitvoer van SureRank, alleen het schema is opnieuw gecodeerd.
}

/**
 * Haal Course en Service uit het schema van SureRank (script surerank-schema).
 * Alles daarbuiten blijft letterlijk staan.
 *
 * @param  string $html Uitvoer van SureRank.
 * @return string
 */
function sfp_page_config_nlvorm_zonder_aanbod( $html ) {
    return (string) preg_replace_callback(
        '#(<script[^>]*id="surerank-schema"[^>]*>)(.*?)(</script>)#s',
        function ( $m ) {
            $data = json_decode( $m[2], true );
            if ( ! is_array( $data ) || ! isset( $data['@graph'] ) || ! is_array( $data['@graph'] ) ) {
                return $m[0];
            }
            $weg = array( 'Course', 'Service' );
            $rest = array_values(
                array_filter(
                    $data['@graph'],
                    function ( $knoop ) use ( $weg ) {
                        $type = is_array( $knoop ) && isset( $knoop['@type'] ) ? (array) $knoop['@type'] : array();
                        return ! array_intersect( $type, $weg );
                    }
                )
            );
            if ( count( $rest ) === count( $data['@graph'] ) ) {
                return $m[0];
            }
            if ( ! $rest ) {
                return '';
            }
            $data['@graph'] = $rest;
            return $m[1] . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . $m[3];
        },
        $html
    );
}

/* =========================================================================
 * Held wit in de editor: gegevens voor het voorbeeld (sinds 3.31.3)
 *
 * De feitenkaart en de bewijsregel maakt de plugin op de site. In de editor
 * tekent editor.js een voorbeeld; daarvoor de eerstvolgende start uit de
 * cursusdata van dit bericht en het Google-cijfer.
 * ====================================================================== */

add_action( 'enqueue_block_editor_assets', 'sfp_page_config_nlvorm_editor_data' );

/**
 * De eerstvolgende start (niet vol) als label en waarde, of null.
 *
 * @param  int $post_id Bericht.
 * @return array{label: string, waarde: string}|null
 */
function sfp_page_config_nlvorm_volgende_start( $post_id ) {
    foreach ( sfp_page_config_nlvorm_groepen( (int) $post_id ) as $g ) {
        if ( 'vol' === $g['status'] ) {
            continue;
        }
        $deel = sfp_page_config_nlvorm_datum( $g['data'][0] );
        return array(
            'label'  => sfp_page_config_tekst( 'held_volgende_start' ),
            'waarde' => trim( $deel['dag'] . ' ' . $deel['nr'] . ' ' . $deel['maand'] ),
        );
    }
    return null;
}

/**
 * Zet window.sfpHeldWit voor de editor.
 */
function sfp_page_config_nlvorm_editor_data() {
    $post_id = (int) get_the_ID();
    $data    = array(
        'volgende' => $post_id ? sfp_page_config_nlvorm_volgende_start( $post_id ) : null,
        'cijfer'   => sfp_page_config_nlvorm_google_cijfer(),
        'opGoogle' => sfp_page_config_tekst( 'held_op_google' ),
    );
    wp_add_inline_script( 'sfp-blokken-editor', 'window.sfpHeldWit=' . wp_json_encode( $data ) . ';', 'before' );
}
