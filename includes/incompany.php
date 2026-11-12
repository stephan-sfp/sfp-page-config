<?php
/**
 * SFP Page Config - Bouwstenen voor incompany (sinds 3.53.0).
 *
 * Het incompany-overzicht (/incompany/, ontwerp akkoord 8-11-2026, dummy
 * nl-vormen v100) en de incompany-cursuspagina (akkoord 11-11-2026, v105)
 * vragen vier nieuwe bouwstenen:
 *   I1 Portfolio: de vorm Portfolio van het blok Tabs. Per tab een logo; het
 *      paneel heeft links de tekst (naam als label, H3, twee lijsten,
 *      tekstlink) en rechts de foto met een review uit SFP Beheer.
 *   I2 Onderwerpenregel (sfp/onderwerpen): de onderwerppagina's onder
 *      /incompany/ (soort pagina Incompany) als tekstlinks met pijl. Een
 *      nieuwe onderwerppagina staat er vanzelf bij; de volgorde is het veld
 *      Volgorde van de pagina.
 *   I3 Slot kort: de indeling Slot kort van het blok Kolommen.
 *   I4 Keuzehulp vorm (sfp/keuzehulp-vorm): drie vragen en vier vormkaarten.
 *
 * Plus de gegevens voor Google: Service met provider en areaServed, en een
 * ItemList van de onderwerpen. Besluiten Stephan 12-11-2026 (AUQ): reviews
 * uit SFP Beheer, op de link alleen het onderwerp, vorm C (bestaande
 * tekstlink met pijl), teksten van de keuzehulp in het blok typen, de
 * incompanykaart met een tweede actie.
 *
 * Geen vaste adressen: knopteksten en doelen zijn per blok instelbaar. Geen
 * merkkleur of fontnaam; kleuren uit de rolvariabelen.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'sfp_page_config_blokken_extra', 'sfp_page_config_incompany_blokken' );

/**
 * De blokken Onderwerpenregel en Keuzehulp vorm.
 *
 * @param  array $alle Blokken.
 * @return array
 */
function sfp_page_config_incompany_blokken( $alle ) {
    $tekst = array(
        'type'    => 'string',
        'default' => '',
    );
    $alle['sfp/onderwerpen'] = array(
        'title'        => 'Onderwerpenregel',
        'description'  => 'De onderwerppagina\'s onder deze pagina (soort pagina Incompany) als links met pijl. Een nieuwe onderwerppagina staat er vanzelf bij.',
        'icon'         => 'tag',
        'css'          => 'incompany',
        'zonder_basis' => false,
        'attributes'   => array(
            // Leeg: de pagina zelf is de ouder.
            'ouder' => array(
                'type'    => 'number',
                'default' => 0,
            ),
        ),
        'render'       => 'sfp_page_config_render_onderwerpen',
    );
    $alle['sfp/keuzehulp-vorm'] = array(
        'title'        => 'Keuzehulp vorm',
        'description'  => 'Drie vragen en vier vormkaarten (incompany, open training, coaching, e-learning): de kaart die past komt bovenaan.',
        'icon'         => 'yes-alt',
        'css'          => 'keuzehulp-vorm',
        'css_extra'    => array( 'lijst' ),
        'zonder_basis' => false,
        'attributes'   => array(
            'vragen'  => array(
                'type'    => 'object',
                'default' => array(),
            ),
            'kaarten' => array(
                'type'    => 'object',
                'default' => array(),
            ),
            'label'   => $tekst,
        ),
        'render'       => 'sfp_page_config_render_keuzehulp_vorm',
    );
    return $alle;
}

/* =========================================================================
 * I1 Portfolio (vorm van Tabs)
 * ====================================================================== */

/**
 * Een doel uit een instelling: een pagina-id, een pad op de site, een anker
 * of een volledig adres. Leeg of ongeldig: leeg.
 *
 * @param  string $doel Doel.
 * @return string
 */
function sfp_page_config_incompany_adres( $doel ) {
    $doel = trim( (string) $doel );
    if ( '' === $doel ) {
        return '';
    }
    if ( '#' === $doel[0] ) {
        return '#' . sanitize_title( substr( $doel, 1 ) );
    }
    if ( ctype_digit( $doel ) ) {
        $link = get_permalink( (int) $doel );
        return $link ? (string) $link : '';
    }
    if ( '/' === $doel[0] && '/' !== ( $doel[1] ?? '' ) ) {
        return home_url( $doel );
    }
    return (string) esc_url_raw( $doel, array( 'http', 'https', 'tel', 'mailto' ) );
}

/**
 * Een tekstlink met pijl (de bestaande stijl Tekstlink), of leeg.
 *
 * @param  string $tekst Tekst.
 * @param  string $doel  Doel.
 * @param  string $klasse Extra klasse.
 * @return string
 */
function sfp_page_config_incompany_tekstlink( $tekst, $doel, $klasse = '' ) {
    $tekst = trim( wp_strip_all_tags( (string) $tekst ) );
    $adres = sfp_page_config_incompany_adres( $doel );
    if ( '' === $tekst || '' === $adres ) {
        return '';
    }
    return '<p class="is-style-sfp-pijl' . ( '' !== $klasse ? ' ' . esc_attr( $klasse ) : '' ) . '"><a href="' . esc_url( $adres ) . '">' . esc_html( $tekst ) . '</a></p>';
}

/**
 * Een knop (primair), of leeg.
 *
 * @param  string $tekst Tekst.
 * @param  string $doel  Doel.
 * @return string
 */
function sfp_page_config_incompany_knop( $tekst, $doel ) {
    $tekst = trim( wp_strip_all_tags( (string) $tekst ) );
    $adres = sfp_page_config_incompany_adres( $doel );
    if ( '' === $tekst || '' === $adres ) {
        return '';
    }
    return '<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $adres ) . '">' . esc_html( $tekst ) . '</a></div></div>';
}

/**
 * Tabs in de vorm Portfolio: logo's als tabs, per opdrachtgever een paneel.
 * Foto's en reviews staan rechts, de rest links met de naam als label.
 *
 * @param  array    $attrs Attributen van Tabs.
 * @param  WP_Block $block Blok Tabs.
 * @return string
 */
function sfp_page_config_incompany_portfolio( $attrs, $block ) {
    sfp_page_config_blokken_js_nodig( true );
    $label   = sfp_page_config_blok_attr( $attrs, 'label' );
    $actie   = sfp_page_config_incompany_tekstlink( sfp_page_config_blok_attr( $attrs, 'actieTekst' ), sfp_page_config_blok_attr( $attrs, 'actieDoel' ), 'sfp-portfolio__actie' );
    $knoppen = '';
    $panelen = '';
    $nr      = 0;
    foreach ( $block->inner_blocks as $tab ) {
        if ( 'sfp/tab' !== $tab->name ) {
            continue;
        }
        $id    = wp_unique_id( 'sfp-tab-' );
        $naam  = trim( html_entity_decode( wp_strip_all_tags( sfp_page_config_blok_attr( $tab->attributes, 'titel' ) ), ENT_QUOTES, 'UTF-8' ) );
        $logo  = (int) ( $tab->attributes['logoId'] ?? 0 );
        $open  = 0 === $nr;
        $beeld = '';
        if ( $logo > 0 && wp_attachment_is_image( $logo ) ) {
            $beeld = wp_get_attachment_image(
                $logo,
                'medium',
                false,
                array(
                    // Norm N1 (7-11-2026): "[naam] (logo)" met de volledige naam.
                    'alt'      => '' !== $naam ? sprintf( sfp_page_config_tekst( 'portfolio_logo_alt' ), $naam ) : '',
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                    'class'    => 'sfp-portfolio__logo',
                )
            );
        }
        $knoppen .= '<button type="button" role="tab" id="' . esc_attr( $id ) . '-k" aria-controls="' . esc_attr( $id ) . '" aria-selected="' . ( $open ? 'true' : 'false' ) . '"' . ( $open ? '' : ' tabindex="-1"' ) . '>'
            . ( '' !== $beeld ? $beeld : '<span class="sfp-portfolio__naam">' . esc_html( $naam ) . '</span>' ) . '</button>';

        $tekst = '' !== $naam ? '<p class="is-style-sfp-label">' . esc_html( $naam ) . '</p>' : '';
        $rechts = '';
        foreach ( $tab->inner_blocks as $binnen ) {
            $html = $binnen->render();
            if ( in_array( $binnen->name, array( 'core/image', 'sfp/review' ), true ) ) {
                $rechts .= $html;
            } else {
                $tekst .= $html;
            }
        }
        $panelen .= '<div class="sfp-tabpaneel sfp-portfolio__paneel" role="tabpanel" id="' . esc_attr( $id ) . '" aria-labelledby="' . esc_attr( $id ) . '-k"' . ( $open ? '' : ' hidden' ) . '>'
            . '<div class="sfp-portfolio__tekst">' . $tekst . $actie . '</div>'
            . ( '' !== $rechts ? '<div class="sfp-portfolio__beeld">' . $rechts . '</div>' : '' )
            . '</div>';
        ++$nr;
    }
    $lijst = '<div class="sfp-tablijst sfp-portfolio__logos" role="tablist"' . ( '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : '' ) . '>' . $knoppen . '</div>';
    return sfp_page_config_blokken_css_vangnet( 'sfp/tabs' ) . sfp_page_config_blokken_css_vangnet_bestand( 'lijst' ) . sfp_page_config_blokken_css_vangnet_bestand( 'incompany' )
        . '<div ' . sfp_page_config_blok_omhulling( array( 'sfp-tabs', 'sfp-tabs--portfolio' ) ) . '>' . $lijst . $panelen . '</div>';
}

/* =========================================================================
 * I2 Onderwerpenregel
 * ====================================================================== */

/**
 * De naam van een onderwerp: de titel zonder het eerste woord als dat
 * Cursus, Training of Incompany is (besluit Stephan 12-11-2026: "Pitchen",
 * niet "Cursus Pitchen").
 *
 * @param  string $titel Paginatitel.
 * @return string
 */
function sfp_page_config_onderwerp_naam( $titel ) {
    $titel = trim( html_entity_decode( wp_strip_all_tags( (string) $titel ), ENT_QUOTES, 'UTF-8' ) );
    $kort  = preg_replace( '/^(incompany\s*cursus|incompany\s*training|incompanycursus|incompanytraining|incompany|cursus|training|course|curso|treinamento)\s+/iu', '', $titel );
    $kort  = trim( (string) $kort );
    if ( '' === $kort ) {
        return $titel;
    }
    if ( function_exists( 'mb_strtoupper' ) ) {
        return mb_strtoupper( mb_substr( $kort, 0, 1 ) ) . mb_substr( $kort, 1 );
    }
    return ucfirst( $kort );
}

/**
 * De onderwerppagina's onder een pagina: gepubliceerd, soort pagina
 * Incompany, op Volgorde en dan op titel.
 *
 * @param  int $ouder Ouderpagina.
 * @return array<int, array{id: int, naam: string, titel: string, url: string}>
 */
function sfp_page_config_onderwerpen( $ouder ) {
    $ouder = (int) $ouder;
    if ( $ouder <= 0 ) {
        return array();
    }
    static $cache = array();
    if ( isset( $cache[ $ouder ] ) ) {
        return $cache[ $ouder ];
    }
    $paginas = get_posts(
        array(
            'post_type'        => 'page',
            'post_status'      => 'publish',
            'post_parent'      => $ouder,
            'posts_per_page'   => 100,
            'orderby'          => array(
                'menu_order' => 'ASC',
                'title'      => 'ASC',
            ),
            'meta_key'         => 'sfp_page_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- kleine set kinderen van één pagina.
            'meta_value'       => 'incompany', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'no_found_rows'    => true,
            'suppress_filters' => false,
        )
    );
    $uit = array();
    foreach ( $paginas as $p ) {
        $titel = html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' );
        $uit[] = array(
            'id'    => (int) $p->ID,
            'naam'  => sfp_page_config_onderwerp_naam( $titel ),
            'titel' => $titel,
            'url'   => (string) get_permalink( $p ),
        );
    }
    $cache[ $ouder ] = $uit;
    return $uit;
}

/**
 * De ouder van de onderwerpenregel: de ingestelde pagina, anders de pagina zelf.
 *
 * @param  array $attrs Attributen.
 * @return int
 */
function sfp_page_config_onderwerpen_ouder( $attrs ) {
    $ouder = (int) ( $attrs['ouder'] ?? 0 );
    return $ouder > 0 ? $ouder : (int) get_the_ID();
}

/**
 * sfp/onderwerpen. Zonder onderwerppagina's toont de site niets.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_onderwerpen( $attrs ) {
    $lijst = sfp_page_config_onderwerpen( sfp_page_config_onderwerpen_ouder( $attrs ) );
    if ( ! $lijst ) {
        return '';
    }
    $items = '';
    foreach ( $lijst as $o ) {
        $items .= '<li class="is-style-sfp-pijl"><a href="' . esc_url( $o['url'] ) . '">' . esc_html( $o['naam'] ) . '</a></li>';
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/onderwerpen' )
        . '<ul ' . sfp_page_config_blok_omhulling( array( 'sfp-onderwerpen' ) ) . '>' . $items . '</ul>';
}

/* =========================================================================
 * I4 Keuzehulp vorm
 * ====================================================================== */

/**
 * De vier vormen in vaste volgorde: incompany eerst (zonder script ook).
 *
 * @return string[]
 */
function sfp_page_config_kv_vormen() {
    return array( 'incompany', 'training', 'coaching', 'elearning' );
}

/**
 * Teksten van de vragen, met de standaard uit de teksten van de site.
 *
 * @param  mixed $ruw Attribuut vragen.
 * @return array{v1: string, v2: string, v3: string, opties: string[], ja: string, nee: string, start: string, gekozen: string, vraag2: string}
 */
function sfp_page_config_kv_vragen( $ruw ) {
    $ruw = is_array( $ruw ) ? $ruw : array();
    $s   = function ( $sleutel, $tekst_sleutel ) use ( $ruw ) {
        $eigen = isset( $ruw[ $sleutel ] ) && is_scalar( $ruw[ $sleutel ] ) ? trim( wp_strip_all_tags( (string) $ruw[ $sleutel ] ) ) : '';
        return '' !== $eigen ? $eigen : sfp_page_config_tekst( $tekst_sleutel );
    };
    $opties = array();
    $eigen  = isset( $ruw['opties'] ) && is_array( $ruw['opties'] ) ? array_values( $ruw['opties'] ) : array();
    foreach ( array( 1, 2, 3, 4 ) as $i ) {
        $o        = isset( $eigen[ $i - 1 ] ) && is_scalar( $eigen[ $i - 1 ] ) ? trim( wp_strip_all_tags( (string) $eigen[ $i - 1 ] ) ) : '';
        $opties[] = '' !== $o ? $o : sfp_page_config_tekst( 'kv_optie' . $i );
    }
    return array(
        'v1'      => $s( 'v1', 'kv_vraag1' ),
        'v2'      => $s( 'v2', 'kv_vraag2' ),
        'v3'      => $s( 'v3', 'kv_vraag3' ),
        'opties'  => $opties,
        'ja'      => $s( 'ja', 'kv_ja' ),
        'nee'     => $s( 'nee', 'kv_nee' ),
        'start'   => $s( 'start', 'kv_regel_start' ),
        'gekozen' => $s( 'gekozen', 'kv_regel_gekozen' ),
        'vraag2'  => $s( 'vraag2', 'kv_regel_vraag2' ),
    );
}

/**
 * Eén vormkaart met de standaard uit de teksten van de site.
 *
 * @param  string $vorm Vorm.
 * @param  mixed  $ruw  Attribuut kaarten.
 * @return array{label: string, titel: string, feiten: string[], extra: string, actie: string, tekst: string, doel: string, tekst2: string, doel2: string}
 */
function sfp_page_config_kv_kaart( $vorm, $ruw ) {
    $ruw  = is_array( $ruw ) && isset( $ruw[ $vorm ] ) && is_array( $ruw[ $vorm ] ) ? $ruw[ $vorm ] : array();
    $los  = function ( $sleutel ) use ( $ruw ) {
        return isset( $ruw[ $sleutel ] ) && is_scalar( $ruw[ $sleutel ] ) ? trim( wp_strip_all_tags( (string) $ruw[ $sleutel ] ) ) : '';
    };
    $met  = function ( $sleutel, $tekst_sleutel ) use ( $los ) {
        $eigen = $los( $sleutel );
        return '' !== $eigen ? $eigen : sfp_page_config_tekst( $tekst_sleutel );
    };
    $feiten = array();
    if ( isset( $ruw['feiten'] ) && is_array( $ruw['feiten'] ) ) {
        foreach ( $ruw['feiten'] as $f ) {
            $f = is_scalar( $f ) ? trim( wp_strip_all_tags( (string) $f ) ) : '';
            if ( '' !== $f ) {
                $feiten[] = $f;
            }
            if ( count( $feiten ) >= 4 ) {
                break;
            }
        }
    }
    if ( ! $feiten && 'elearning' === $vorm ) {
        $feiten = array( sfp_page_config_tekst( 'kv_elearning_feit1' ), sfp_page_config_tekst( 'kv_elearning_feit2' ) );
    }
    $standaard_actie = array(
        'incompany' => 'knop',
        'training'  => 'tekstlink',
        'coaching'  => 'tekstlink',
        'elearning' => 'geen',
    );
    $actie = $los( 'actie' );
    $actie = in_array( $actie, array( 'knop', 'tekstlink', 'geen' ), true ) ? $actie : $standaard_actie[ $vorm ];
    return array(
        'label'  => $met( 'label', 'kv_' . $vorm . '_label' ),
        'titel'  => $met( 'titel', 'kv_' . $vorm . '_titel' ),
        'feiten' => $feiten,
        'extra'  => 'incompany' === $vorm ? $met( 'extra', 'kv_incompany_extra' ) : '',
        'actie'  => $actie,
        'tekst'  => 'elearning' === $vorm ? $los( 'tekst' ) : $met( 'tekst', 'kv_' . $vorm . '_actie' ),
        'doel'   => $los( 'doel' ),
        'tekst2' => 'incompany' === $vorm ? $met( 'tekst2', 'kv_incompany_actie2' ) : '',
        'doel2'  => 'incompany' === $vorm ? $los( 'doel2' ) : '',
    );
}

/**
 * sfp/keuzehulp-vorm. Zonder script staan alle kaarten onder elkaar,
 * incompany eerst; het script toont de passende kaart bovenaan.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_keuzehulp_vorm( $attrs ) {
    $v      = sfp_page_config_kv_vragen( $attrs['vragen'] ?? array() );
    $basis  = wp_unique_id( 'sfp-kv-' );
    $vraag  = function ( $nr, $legend, $naam, array $opties ) use ( $basis ) {
        $html = '';
        foreach ( $opties as $waarde => $tekst ) {
            $html .= '<label><input type="radio" name="' . esc_attr( $basis . '-' . $naam ) . '" value="' . esc_attr( $waarde ) . '"><span>' . esc_html( $tekst ) . '</span></label>';
        }
        return '<fieldset class="sfp-kv__vraag"><legend><span class="sfp-kv__nr" aria-hidden="true">' . (int) $nr . '</span>' . esc_html( $legend ) . '</legend><div class="sfp-kv__opties">' . $html . '</div></fieldset>';
    };
    $jn     = array(
        'ja'  => $v['ja'],
        'nee' => $v['nee'],
    );
    $vragen = $vraag( 1, $v['v1'], 'aantal', array_combine( array( '1', '2-3', '4-8', '9+' ), $v['opties'] ) )
        . $vraag( 2, $v['v2'], 'praktijk', $jn )
        . $vraag( 3, $v['v3'], 'tempo', $jn );

    $kaarten = '';
    foreach ( sfp_page_config_kv_vormen() as $vorm ) {
        $k      = sfp_page_config_kv_kaart( $vorm, $attrs['kaarten'] ?? array() );
        $feiten = '';
        foreach ( $k['feiten'] as $f ) {
            $feiten .= '<li>' . esc_html( $f ) . '</li>';
        }
        if ( '' !== $k['extra'] ) {
            $feiten .= '<li class="sfp-kv__extra">' . esc_html( $k['extra'] ) . '</li>';
        }
        $actie = '';
        if ( 'knop' === $k['actie'] ) {
            $actie = sfp_page_config_incompany_knop( $k['tekst'], $k['doel'] );
        } elseif ( 'tekstlink' === $k['actie'] ) {
            $actie = sfp_page_config_incompany_tekstlink( $k['tekst'], $k['doel'] );
        }
        // De tweede actie van de incompanykaart (besluit Stephan 12-11-2026).
        $tweede = 'incompany' === $vorm ? sfp_page_config_incompany_tekstlink( $k['tekst2'], $k['doel2'] ) : '';
        if ( '' !== $tweede ) {
            $actie = '<div class="sfp-kv__acties">' . $actie . $tweede . '</div>';
        }
        $kaarten .= '<div class="sfp-kv__kaart' . ( 'incompany' === $vorm ? ' is-uitgelicht' : '' ) . '" data-vorm="' . esc_attr( $vorm ) . '">'
            . '<p class="is-style-sfp-label">' . esc_html( $k['label'] ) . '</p>'
            . '<h3 class="wp-block-heading">' . esc_html( $k['titel'] ) . '</h3>'
            . ( '' !== $feiten ? '<div class="sfp-lijst ' . ( 'elearning' === $vorm ? 'sfp-lijst--plat' : 'sfp-lijst--vink' ) . '"><ul>' . $feiten . '</ul></div>' : '' )
            . $actie . '</div>';
    }

    sfp_page_config_kv_js_nodig( true );
    $label = sfp_page_config_blok_attr( $attrs, 'label' );
    return sfp_page_config_blokken_css_vangnet( 'sfp/keuzehulp-vorm' )
        . '<form ' . sfp_page_config_blok_omhulling( array( 'sfp-kv' ) ) . ' data-sfp-kv aria-label="' . esc_attr( '' !== $label ? $label : sfp_page_config_tekst( 'kv_naam' ) ) . '" onsubmit="return false">'
        . '<div class="sfp-kv__vragen">' . $vragen . '</div>'
        . '<div class="sfp-kv__kaarten"><p class="is-style-sfp-label sfp-kv__uitkomst" aria-live="polite" data-start="' . esc_attr( $v['start'] ) . '" data-gekozen="' . esc_attr( $v['gekozen'] ) . '" data-vraag2="' . esc_attr( $v['vraag2'] ) . '">' . esc_html( $v['start'] ) . '</p>'
        . $kaarten . '</div></form>';
}

/**
 * Staat er een keuzehulp vorm op de pagina?
 *
 * @param  bool $zet True om te markeren.
 * @return bool
 */
function sfp_page_config_kv_js_nodig( $zet = false ) {
    static $nodig = false;
    if ( $zet ) {
        $nodig = true;
    }
    return $nodig;
}

add_action( 'wp_footer', 'sfp_page_config_kv_js', 30 );

/**
 * Het script van de keuzehulp vorm, niet uitgesteld door WP Rocket.
 */
function sfp_page_config_kv_js() {
    if ( sfp_page_config_kv_js_nodig() && function_exists( 'sfp_page_config_script_tag' ) ) {
        sfp_page_config_script_tag( 'sfp-keuzehulp-vorm-js', 'blokken/keuzehulp-vorm.js' );
    }
}

/* =========================================================================
 * Gegevens voor Google: Service en ItemList (I2)
 * ====================================================================== */

/**
 * Heeft de pagina een van de incompanybouwstenen (Onderwerpenregel,
 * Portfolio of Keuzehulp vorm)? Alleen dan levert de plugin het schema;
 * bestaande pagina's veranderen niet.
 *
 * @param  int $post_id Pagina.
 * @return bool
 */
function sfp_page_config_incompany_heeft_bouwsteen( $post_id ) {
    $inhoud = (string) get_post_field( 'post_content', $post_id );
    return false !== strpos( $inhoud, '<!-- wp:sfp/onderwerpen' )
        || false !== strpos( $inhoud, '<!-- wp:sfp/keuzehulp-vorm' )
        || false !== strpos( $inhoud, '"vorm":"portfolio"' );
}

/**
 * Het land voor areaServed uit de sitetaal (nl_NL: NL, pt_BR: BR). Een
 * taal zonder land (en_US op een internationale site): leeg.
 *
 * @return string
 */
function sfp_page_config_incompany_land() {
    $locale = (string) get_locale();
    if ( 0 === strpos( $locale, 'nl' ) ) {
        return 'NL';
    }
    if ( 'pt_BR' === $locale ) {
        return 'BR';
    }
    return '';
}

/**
 * De onderwerpen van de pagina als ItemList, of leeg.
 *
 * @param  int $post_id Pagina.
 * @return array
 */
function sfp_page_config_incompany_itemlist( $post_id ) {
    $inhoud = (string) get_post_field( 'post_content', $post_id );
    if ( ! preg_match( '/<!-- wp:sfp\/onderwerpen(\s+(\{.*?\}))?\s*\/?-->/s', $inhoud, $m ) ) {
        return array();
    }
    $attrs = isset( $m[2] ) ? json_decode( $m[2], true ) : array();
    $ouder = isset( $attrs['ouder'] ) && (int) $attrs['ouder'] > 0 ? (int) $attrs['ouder'] : (int) $post_id;
    $items = array();
    foreach ( sfp_page_config_onderwerpen( $ouder ) as $o ) {
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => count( $items ) + 1,
            'name'     => $o['titel'],
            'url'      => $o['url'],
        );
    }
    if ( ! $items ) {
        return array();
    }
    return array(
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        '@id'             => get_permalink( $post_id ) . '#onderwerpen',
        'name'            => html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ),
        'itemListElement' => $items,
    );
}

/**
 * Het Service-schema van een incompanypagina: naam, adres, beschrijving uit
 * de kernzin, provider (de organisatie van de site) en areaServed.
 *
 * @param  int $post_id Pagina.
 * @return array
 */
function sfp_page_config_incompany_service( $post_id ) {
    $data = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        '@id'         => get_permalink( $post_id ) . '#service',
        'name'        => html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ),
        'url'         => get_permalink( $post_id ),
        'serviceType' => sfp_page_config_tekst( 'incompany_service_type' ),
        'provider'    => array( '@id' => home_url( '/' ) . '#organization' ),
    );
    $land = sfp_page_config_incompany_land();
    if ( '' !== $land ) {
        $data['areaServed'] = array(
            '@type' => 'Country',
            'name'  => $land,
        );
    }
    $inhoud = (string) get_post_field( 'post_content', $post_id );
    if ( preg_match( '/<p[^>]*class="[^"]*is-style-sfp-kernzin[^"]*"[^>]*>(.*?)<\/p>/s', $inhoud, $m ) ) {
        $zin = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' ) );
        if ( '' !== $zin ) {
            $data['description'] = $zin;
        }
    }
    return $data;
}

/**
 * Levert de plugin op deze pagina het incompanyschema?
 *
 * @param  int $post_id Pagina.
 * @return bool
 */
function sfp_page_config_incompany_schema_aan( $post_id ) {
    return 'incompany' === get_post_meta( $post_id, 'sfp_page_type', true )
        && sfp_page_config_incompany_heeft_bouwsteen( $post_id )
        && (bool) apply_filters( 'sfp_page_config_incompany_schema', true, $post_id );
}

add_action( 'wp_head', 'sfp_page_config_incompany_schema', 31 );

/**
 * Service en ItemList in de head.
 */
function sfp_page_config_incompany_schema() {
    if ( is_admin() || ! is_singular( 'page' ) ) {
        return;
    }
    $post_id = (int) get_queried_object_id();
    if ( ! sfp_page_config_incompany_schema_aan( $post_id ) ) {
        return;
    }
    $uit   = array( sfp_page_config_incompany_service( $post_id ) );
    $lijst = sfp_page_config_incompany_itemlist( $post_id );
    if ( $lijst ) {
        $uit[] = $lijst;
    }
    foreach ( $uit as $data ) {
        echo '<script type="application/ld+json" class="sfp-incompany-schema">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON met JSON_HEX_TAG.
    }
}

/* =========================================================================
 * Editor: script en standaardteksten
 * ====================================================================== */

add_action( 'enqueue_block_editor_assets', 'sfp_page_config_incompany_editor' );

/**
 * Het editorscript van de incompanyblokken, met de standaardteksten.
 */
function sfp_page_config_incompany_editor() {
    wp_enqueue_script(
        'sfp-blokken-editor-incompany',
        sfp_page_config_asset_url( 'blokken/editor-incompany.js' ),
        array( 'sfp-blokken-editor', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-core-data' ),
        SFP_PAGE_CONFIG_VERSION,
        true
    );
    $sleutels = array( 'kv_vraag1', 'kv_vraag2', 'kv_vraag3', 'kv_optie1', 'kv_optie2', 'kv_optie3', 'kv_optie4', 'kv_ja', 'kv_nee', 'kv_regel_start', 'kv_incompany_extra', 'kv_incompany_actie2', 'kv_elearning_feit1', 'kv_elearning_feit2' );
    foreach ( sfp_page_config_kv_vormen() as $vorm ) {
        $sleutels[] = 'kv_' . $vorm . '_label';
        $sleutels[] = 'kv_' . $vorm . '_titel';
        if ( 'elearning' !== $vorm ) {
            $sleutels[] = 'kv_' . $vorm . '_actie';
        }
    }
    $teksten = array();
    foreach ( $sleutels as $s ) {
        $teksten[ $s ] = sfp_page_config_tekst( $s );
    }
    wp_add_inline_script( 'sfp-blokken-editor-incompany', 'window.sfpIncompany=' . wp_json_encode( array( 'teksten' => $teksten ) ) . ';', 'before' );
}
