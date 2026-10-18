<?php
/**
 * SFP Page Config - Thermometer in een pijler (sinds 3.6.0)
 *
 * Een korte test die de lezer bij het probleem van de pijler betrekt
 * (besluit Stephan, 18-10-2026). Geen zelftest (geen lead, niets bewaard),
 * geen tool (geen eigen pagina) en geen kaart naar een artikel.
 *
 *   sfp/thermometer           kop en drie uitslagen (laag, gemiddeld, hoog),
 *                             elk met een tekst en het hoofdstuk om verder te lezen
 *   sfp/thermometer-stelling  één stelling; de lezer kiest Klopt, Soms of Klopt niet
 *
 * Net als de warming-up bij een artikel staat de Thermometer niet in de
 * tekst: hij klapt uit de kopkaart van de pijler. De uitslag rekent in de
 * browser: Klopt 2, Soms 1, Klopt niet 0; onder een derde laag, onder twee
 * derde gemiddeld, anders hoog. De uitslag toont geen algemeen woord als
 * "hoog": de schrijver geeft elke uitslag een eigen naam en de schaal twee
 * einden in de woorden van de vraag (feedback Stephan 18-10-2026). De meter
 * ziet eruit als een thermometer (wens Stephan 19-10-2026): een buis die van
 * groen naar rood volloopt, met een wijzer (bolletje in de kleur van de stand)
 * die naar de stand schuift. Geen vaste bol links: die las als de stand. Groen, oranje en rood zijn vaste
 * signaalkleuren, geen merkkleuren; een site kan ze overschrijven met
 * --sfp-thermo-koel, --sfp-thermo-warm en --sfp-thermo-heet.
 *
 * Kleuren: alleen via de variabelen --sfp-b-*. Geen merkwaarden in code.
 *
 * @package SFP_Page_Config
 * @since   3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Minimum en maximum aantal stellingen. */
const SFP_PC_THERMO_MIN = 3;
const SFP_PC_THERMO_MAX = 8;

add_filter( 'sfp_page_config_blokken_extra', 'sfp_page_config_thermometer_blokken' );

/**
 * De blokken Thermometer en Stelling.
 *
 * @param  array $blokken Blokken.
 * @return array
 */
function sfp_page_config_thermometer_blokken( $blokken ) {
    $tekst = array(
        'type'    => 'string',
        'default' => '',
    );
    $blokken['sfp/thermometer']          = array(
        'title'        => 'Thermometer',
        'description'  => 'Korte test die de lezer bij het probleem van de pijler betrekt. Verschijnt als knop in de kopkaart van de pijler, niet in de tekst.',
        'icon'         => 'performance',
        'css'          => '',
        'zonder_basis' => true,
        'attributes'   => array(
            'kop'          => $tekst,
            'links'        => $tekst,
            'rechts'       => $tekst,
            'laag_label'   => $tekst,
            'midden_label' => $tekst,
            'hoog_label'   => $tekst,
            'laag'         => $tekst,
            'laag_anker'   => $tekst,
            'midden'       => $tekst,
            'midden_anker' => $tekst,
            'hoog'         => $tekst,
            'hoog_anker'   => $tekst,
        ),
        'render'       => 'sfp_page_config_render_thermometer',
    );
    $blokken['sfp/thermometer-stelling'] = array(
        'title'        => 'Stelling',
        'description'  => 'Eén stelling van de Thermometer.',
        'icon'         => 'performance',
        'css'          => '',
        'zonder_basis' => true,
        'parent'       => array( 'sfp/thermometer' ),
        'attributes'   => array(
            'tekst' => $tekst,
        ),
        'render'       => 'sfp_page_config_render_thermometer_stelling',
    );
    return $blokken;
}

/**
 * Stellingen die wachten op hun Thermometer. De stellingen worden voor hun
 * ouderblok getekend.
 *
 * @param  string|null|false $stelling Toevoegen; null leest; false leegt.
 * @return string[]
 */
function sfp_page_config_thermometer_stellingen( $stelling = null ) {
    static $lijst = array();
    if ( false === $stelling ) {
        $nu    = $lijst;
        $lijst = array();
        return $nu;
    }
    if ( null !== $stelling ) {
        $lijst[] = $stelling;
    }
    return $lijst;
}

/**
 * De Thermometer van het bericht dat nu getoond wordt.
 *
 * @param  array|null|false $thermo Zetten; null leest; false wist.
 * @return array|null
 */
function sfp_page_config_thermometer( $thermo = null ) {
    static $nu = null;
    if ( false === $thermo ) {
        $nu = null;
        return null;
    }
    if ( null !== $thermo && null === $nu ) {
        // De eerste Thermometer telt.
        $nu = $thermo;
    }
    return $nu;
}

/**
 * sfp/thermometer-stelling. Toont niets in de tekst.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_thermometer_stelling( $attrs ) {
    $tekst = trim( wp_strip_all_tags( sfp_page_config_blok_attr( $attrs, 'tekst' ) ) );
    if ( '' !== $tekst && ! is_admin() ) {
        sfp_page_config_thermometer_stellingen( $tekst );
    }
    return '';
}

/**
 * sfp/thermometer. Toont niets in de tekst; de kopkaart van de pijler toont
 * de test.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_thermometer( $attrs ) {
    $stellingen = array_slice( sfp_page_config_thermometer_stellingen( false ), 0, SFP_PC_THERMO_MAX );
    if ( is_admin() ) {
        return '';
    }
    $uitslagen = array();
    foreach ( array( 'laag', 'midden', 'hoog' ) as $band ) {
        $uitslagen[ $band ] = array(
            'label' => trim( wp_strip_all_tags( sfp_page_config_blok_attr( $attrs, $band . '_label' ) ) ),
            'tekst' => trim( wp_strip_all_tags( sfp_page_config_blok_attr( $attrs, $band ) ) ),
            'anker' => sanitize_title( sfp_page_config_blok_attr( $attrs, $band . '_anker' ) ),
        );
    }
    $kop = trim( wp_strip_all_tags( sfp_page_config_blok_attr( $attrs, 'kop' ) ) );
    if ( count( $stellingen ) >= SFP_PC_THERMO_MIN ) {
        sfp_page_config_thermometer(
            array(
                'kop'        => $kop,
                'links'      => trim( wp_strip_all_tags( sfp_page_config_blok_attr( $attrs, 'links' ) ) ),
                'rechts'     => trim( wp_strip_all_tags( sfp_page_config_blok_attr( $attrs, 'rechts' ) ) ),
                'stellingen' => $stellingen,
                'uitslagen'  => $uitslagen,
            )
        );
    }
    return '';
}

/**
 * Het paneel van de Thermometer in de kopkaart.
 *
 * @param  array $thermo       Gegevens.
 * @param  array $hoofdstukken Hoofdstukken van het bericht (id, titel).
 * @return string
 */
function sfp_page_config_thermometer_paneel( array $thermo, array $hoofdstukken ) {
    $t      = 'sfp_page_config_artikel_tekst';
    $titels = array();
    foreach ( $hoofdstukken as $h ) {
        $titels[ $h['id'] ] = $h['titel'];
    }
    $aantal  = count( $thermo['stellingen'] );
    $stip    = '';
    $blokken = '';
    $knoppen = '';
    foreach ( array( 2 => 'thermo_klopt', 1 => 'thermo_soms', 0 => 'thermo_niet' ) as $punt => $sleutel ) {
        $knoppen .= '<button type="button" aria-pressed="false" data-punt="' . (int) $punt . '">' . esc_html( $t( $sleutel ) ) . '</button>';
    }
    foreach ( $thermo['stellingen'] as $i => $stelling ) {
        $stip    .= '<i' . ( 0 === $i ? ' class="aan"' : '' ) . '></i>';
        $blokken .= '<div class="sfp-art-thermo__stelling"' . ( 0 === $i ? '' : ' hidden' ) . '><b>' . esc_html( $stelling ) . '</b><div class="sfp-art-warm__opties sfp-art-thermo__opties">' . $knoppen . '</div></div>';
    }
    $uitslagen = '';
    foreach ( $thermo['uitslagen'] as $band => $u ) {
        $link = '';
        if ( '' !== $u['anker'] && isset( $titels[ $u['anker'] ] ) ) {
            $link = '<a class="sfp-pijl" href="#' . esc_attr( $u['anker'] ) . '">' . sprintf( esc_html( $t( 'thermo_lees' ) ), esc_html( $titels[ $u['anker'] ] ) ) . '</a>';
        }
        $uitslagen .= '<div class="sfp-art-thermo__band" data-band="' . esc_attr( $band ) . '" hidden>'
            . ( '' !== $u['label'] ? '<b class="sfp-art-thermo__stand">' . esc_html( $u['label'] ) . '</b>' : '' )
            . ( '' !== $u['tekst'] ? '<p>' . esc_html( $u['tekst'] ) . '</p>' : '' )
            . $link
            . '</div>';
    }
    return '<div class="sfp-art-paneel" id="sfp-art-paneel-thermo" hidden><div class="sfp-art-thermo">'
        . ( '' !== $thermo['kop'] ? '<p class="sfp-art-thermo__vraag">' . esc_html( $thermo['kop'] ) . '</p>' : '' )
        . '<div class="sfp-art-warm__kop sfp-art-thermo__kop"><span>' . sprintf( esc_html( $t( 'thermo_stelling' ) ), '<b class="sfp-art-thermo__nr">1</b>', $aantal ) . '</span><span class="sfp-art-warm__stip" aria-hidden="true">' . $stip . '</span></div>'
        . $blokken
        . '<div class="sfp-art-thermo__uitslag" hidden>'
        . '<span class="sfp-blok-label" tabindex="-1">' . esc_html( $t( 'thermo_uitslag' ) ) . '</span>'
        . '<div class="sfp-art-thermo__schaal" aria-hidden="true"><div class="sfp-art-thermo__meter"><span class="sfp-art-thermo__buis"><span class="sfp-art-thermo__dek"></span></span><span class="sfp-art-thermo__bol"></span></div>'
        . ( '' !== $thermo['links'] || '' !== $thermo['rechts'] ? '<div class="sfp-art-thermo__einden"><span>' . esc_html( $thermo['links'] ) . '</span><span>' . esc_html( $thermo['rechts'] ) . '</span></div>' : '' )
        . '</div>'
        . $uitslagen
        . '<div class="sfp-art-warm__voet"><button type="button" class="sfp-art-thermo__opnieuw">' . esc_html( $t( 'thermo_opnieuw' ) ) . '</button></div>'
        . '</div></div></div>';
}

/**
 * Heeft een bericht een bruikbare Thermometer (minstens drie stellingen)?
 *
 * @param  WP_Post $post Bericht.
 * @return bool
 */
function sfp_page_config_heeft_thermometer( $post ) {
    if ( false === strpos( (string) $post->post_content, 'wp:sfp/thermometer' ) ) {
        return false;
    }
    foreach ( parse_blocks( (string) $post->post_content ) as $blok ) {
        if ( 'sfp/thermometer' !== $blok['blockName'] ) {
            continue;
        }
        $n = 0;
        foreach ( (array) $blok['innerBlocks'] as $kind ) {
            if ( 'sfp/thermometer-stelling' === $kind['blockName'] && '' !== trim( wp_strip_all_tags( isset( $kind['attrs']['tekst'] ) ? (string) $kind['attrs']['tekst'] : '' ) ) ) {
                ++$n;
            }
        }
        return $n >= SFP_PC_THERMO_MIN;
    }
    return false;
}
