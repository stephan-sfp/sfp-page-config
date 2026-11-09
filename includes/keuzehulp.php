<?php
/**
 * Blok sfp/keuzehulp (sinds 3.41.0, punt 17 van home en Over, besluit
 * Stephan 9-11-2026): "Welke training past bij mij?" als bouwsteen in plaats
 * van een los HTML-blok met eigen opmaak en script.
 *
 * Eén witte kaart; per vraag "Vraag n van N", de vraag en de antwoorden als
 * keuzekaarten met een pijl. Een antwoord gaat naar een volgende vraag, naar
 * Help mij kiezen of naar een pagina. Help mij kiezen en Terug zijn
 * tekstlinks. De stap Help mij kiezen heeft een kop, een zin, een knop naar
 * een gesprek en eventueel een SureForms-formulier (nummer achterlaten).
 *
 * Attributen:
 *   vragen  [ { id, vraag, antwoorden: [ { tekst, naar } ] } ]
 *           naar = "v:<id>" (volgende vraag), "hulp" of een adres.
 *   hulp    { kop, tekst, knoptekst, knoplink, label, formulier }
 *
 * Teksten en kleuren uit de plugin; geen merkkleur of fontnaam.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'sfp_page_config_blokken_extra', 'sfp_page_config_keuzehulp_blok' );

/**
 * Het blok, in de vorm van sfp_page_config_blokken().
 *
 * @param  array $blokken Blokken.
 * @return array
 */
function sfp_page_config_keuzehulp_blok( $blokken ) {
    $blokken['sfp/keuzehulp'] = array(
        'title'        => 'Keuzehulp',
        'description'  => 'Twee of drie vragen die de bezoeker naar de passende training, coaching of incompany leiden, met Help mij kiezen.',
        'icon'         => 'randomize',
        'css'          => 'keuzehulp',
        'zonder_basis' => true,
        'attributes'   => array(
            'vragen'    => array(
                'type'    => 'array',
                'default' => array(),
            ),
            'hulp'      => array(
                'type'    => 'object',
                'default' => array(),
            ),
            'voorbeeld' => array(
                'type'    => 'boolean',
                'default' => false,
            ),
        ),
        'render'       => 'sfp_page_config_render_keuzehulp',
    );
    return $blokken;
}

/**
 * Schoon de vragen op.
 *
 * @param  mixed $ruw Ruwe vragen.
 * @return array<int, array{id: string, vraag: string, antwoorden: array}>
 */
function sfp_page_config_keuzehulp_vragen( $ruw ) {
    $uit = array();
    foreach ( is_array( $ruw ) ? $ruw : array() as $i => $v ) {
        if ( ! is_array( $v ) ) {
            continue;
        }
        $id    = sanitize_key( (string) ( $v['id'] ?? '' ) );
        $id    = '' !== $id ? $id : 'v' . ( $i + 1 );
        $vraag = trim( wp_strip_all_tags( (string) ( $v['vraag'] ?? '' ) ) );
        $ant   = array();
        foreach ( is_array( $v['antwoorden'] ?? null ) ? $v['antwoorden'] : array() as $a ) {
            $tekst = is_array( $a ) ? trim( wp_strip_all_tags( (string) ( $a['tekst'] ?? '' ) ) ) : '';
            $naar  = is_array( $a ) ? trim( (string) ( $a['naar'] ?? '' ) ) : '';
            if ( '' !== $tekst ) {
                $ant[] = array(
                    'tekst' => $tekst,
                    'naar'  => $naar,
                );
            }
        }
        if ( '' !== $vraag && $ant ) {
            $uit[] = array(
                'id'         => $id,
                'vraag'      => $vraag,
                'antwoorden' => $ant,
            );
        }
    }
    return $uit;
}

/**
 * Per vraag het nummer en het aantal vragen op het langste pad vanaf daar,
 * zodat "Vraag n van N" klopt voor de route die de bezoeker neemt.
 *
 * @param  array $vragen Vragen.
 * @return array<string, array{0: int, 1: int}> id => [nummer, totaal]
 */
function sfp_page_config_keuzehulp_tellers( array $vragen ) {
    $per_id = array();
    foreach ( $vragen as $v ) {
        $per_id[ $v['id'] ] = $v;
    }
    $rest = array();
    $diep = function ( $id, $pad ) use ( &$diep, &$rest, $per_id ) {
        if ( isset( $rest[ $id ] ) ) {
            return $rest[ $id ];
        }
        $max = 0;
        foreach ( $per_id[ $id ]['antwoorden'] as $a ) {
            if ( 0 === strpos( $a['naar'], 'v:' ) ) {
                $doel = substr( $a['naar'], 2 );
                if ( isset( $per_id[ $doel ] ) && ! in_array( $doel, $pad, true ) ) {
                    $max = max( $max, $diep( $doel, array_merge( $pad, array( $doel ) ) ) );
                }
            }
        }
        $rest[ $id ] = $max + 1;
        return $rest[ $id ];
    };
    $uit = array();
    if ( ! $vragen ) {
        return $uit;
    }
    // Breedte eerst vanaf de eerste vraag: het nummer is de stap waarop de
    // bezoeker de vraag ziet.
    $rij = array( array( $vragen[0]['id'], 1 ) );
    while ( $rij ) {
        list( $id, $nr ) = array_shift( $rij );
        if ( isset( $uit[ $id ] ) ) {
            continue;
        }
        $uit[ $id ] = array( $nr, $nr - 1 + $diep( $id, array( $id ) ) );
        foreach ( $per_id[ $id ]['antwoorden'] as $a ) {
            if ( 0 === strpos( $a['naar'], 'v:' ) && isset( $per_id[ substr( $a['naar'], 2 ) ] ) ) {
                $rij[] = array( substr( $a['naar'], 2 ), $nr + 1 );
            }
        }
    }
    foreach ( $vragen as $v ) {
        if ( ! isset( $uit[ $v['id'] ] ) ) {
            $uit[ $v['id'] ] = array( 1, $diep( $v['id'], array( $v['id'] ) ) );
        }
    }
    return $uit;
}

/**
 * Een adres uit een antwoord: een pagina-id, een pad op de site of een
 * volledig adres.
 *
 * @param  string $naar Doel.
 * @return string
 */
function sfp_page_config_keuzehulp_adres( $naar ) {
    $naar = trim( (string) $naar );
    if ( ctype_digit( $naar ) ) {
        $link = get_permalink( (int) $naar );
        return $link ? (string) $link : '';
    }
    if ( 0 === strpos( $naar, '/' ) ) {
        return home_url( $naar );
    }
    return esc_url_raw( $naar, array( 'http', 'https' ) );
}

/**
 * sfp/keuzehulp. Zonder vragen toont de site niets.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_keuzehulp( $attrs ) {
    $vragen = sfp_page_config_keuzehulp_vragen( $attrs['vragen'] ?? array() );
    if ( ! $vragen ) {
        return '';
    }
    $hulp   = is_array( $attrs['hulp'] ?? null ) ? $attrs['hulp'] : array();
    $heeft_hulp = '' !== trim( (string) ( $hulp['kop'] ?? '' ) ) || '' !== trim( (string) ( $hulp['knoplink'] ?? '' ) ) || ! empty( $hulp['formulier'] );
    $tellers = sfp_page_config_keuzehulp_tellers( $vragen );
    $t       = 'sfp_page_config_tekst';

    $stappen = '';
    foreach ( $vragen as $i => $v ) {
        $knoppen = '';
        foreach ( $v['antwoorden'] as $a ) {
            if ( 0 === strpos( $a['naar'], 'v:' ) || 'hulp' === $a['naar'] ) {
                $doel     = 'hulp' === $a['naar'] ? 'hulp' : substr( $a['naar'], 2 );
                $knoppen .= '<button type="button" class="sfp-keuzehulp__antwoord" data-naar="' . esc_attr( $doel ) . '">' . esc_html( $a['tekst'] ) . '</button>';
            } else {
                $adres    = sfp_page_config_keuzehulp_adres( $a['naar'] );
                $knoppen .= '' !== $adres ? '<a class="sfp-keuzehulp__antwoord" href="' . esc_url( $adres ) . '">' . esc_html( $a['tekst'] ) . '</a>' : '';
            }
        }
        $links = '';
        if ( $i > 0 && $heeft_hulp ) {
            $links .= '<button type="button" class="sfp-keuzehulp__link" data-naar="hulp">' . esc_html( $t( 'keuzehulp_help' ) ) . '</button>';
        }
        if ( $i > 0 ) {
            $links .= '<button type="button" class="sfp-keuzehulp__link sfp-keuzehulp__terug" data-terug>' . esc_html( $t( 'keuzehulp_terug' ) ) . '</button>';
        }
        $teller   = $tellers[ $v['id'] ];
        $stappen .= '<div class="sfp-keuzehulp__stap" data-stap="' . esc_attr( $v['id'] ) . '"' . ( 0 === $i ? '' : ' hidden' ) . '>'
            . ( $teller[1] > 1 ? '<p class="sfp-keuzehulp__teller">' . esc_html( sfp_page_config_tekst_f( 'keuzehulp_vraag_van', $teller[0], $teller[1] ) ) . '</p>' : '' )
            . '<h3 class="sfp-keuzehulp__vraag" tabindex="-1">' . esc_html( $v['vraag'] ) . '</h3>'
            . '<div class="sfp-keuzehulp__antwoorden">' . $knoppen . '</div>'
            . ( '' !== $links ? '<div class="sfp-keuzehulp__links">' . $links . '</div>' : '' )
            . '</div>';
    }

    if ( $heeft_hulp ) {
        $knop  = '';
        $adres = sfp_page_config_keuzehulp_adres( (string) ( $hulp['knoplink'] ?? '' ) );
        if ( '' !== $adres ) {
            $tekst = trim( (string) ( $hulp['knoptekst'] ?? '' ) );
            $knop  = '<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $adres ) . '">' . esc_html( '' !== $tekst ? $tekst : $t( 'keuzehulp_plan' ) ) . '</a></div></div>';
        }
        $form = '';
        if ( ! empty( $hulp['formulier'] ) && 'sureforms_form' === get_post_type( (int) $hulp['formulier'] ) ) {
            $label = trim( (string) ( $hulp['label'] ?? '' ) );
            $form  = '<p class="sfp-keuzehulp__label">' . esc_html( '' !== $label ? $label : $t( 'keuzehulp_nummer' ) ) . '</p>'
                . do_shortcode( '[sureforms id="' . (int) $hulp['formulier'] . '"]' );
        }
        $kop      = trim( (string) ( $hulp['kop'] ?? '' ) );
        $zin      = trim( (string) ( $hulp['tekst'] ?? '' ) );
        $stappen .= '<div class="sfp-keuzehulp__stap sfp-keuzehulp__stap--hulp" data-stap="hulp" hidden>'
            . '<h3 class="sfp-keuzehulp__vraag" tabindex="-1">' . esc_html( '' !== $kop ? $kop : $t( 'keuzehulp_hulp_kop' ) ) . '</h3>'
            . ( '' !== $zin ? '<p class="sfp-keuzehulp__zin">' . esc_html( $zin ) . '</p>' : '' )
            . $knop . $form
            . '<div class="sfp-keuzehulp__links"><button type="button" class="sfp-keuzehulp__link sfp-keuzehulp__terug" data-opnieuw>' . esc_html( $t( 'keuzehulp_opnieuw' ) ) . '</button></div>'
            . '</div>';
    }

    sfp_page_config_keuzehulp_js_nodig( true );
    return sfp_page_config_blokken_css_vangnet( 'sfp/keuzehulp' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-keuzehulp' ) ) . ' data-sfp-keuzehulp>' . $stappen . '</div>';
}

/**
 * Staat er een keuzehulp op de pagina?
 *
 * @param  bool $zet True om te markeren.
 * @return bool
 */
function sfp_page_config_keuzehulp_js_nodig( $zet = false ) {
    static $nodig = false;
    if ( $zet ) {
        $nodig = true;
    }
    return $nodig;
}

add_action( 'wp_footer', 'sfp_page_config_keuzehulp_js', 30 );

/**
 * Het script van de keuzehulp, niet uitgesteld door WP Rocket.
 */
function sfp_page_config_keuzehulp_js() {
    if ( sfp_page_config_keuzehulp_js_nodig() && function_exists( 'sfp_page_config_script_tag' ) ) {
        sfp_page_config_script_tag( 'sfp-keuzehulp-js', 'blokken/keuzehulp.js' );
    }
}
