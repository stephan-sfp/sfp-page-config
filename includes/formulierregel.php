<?php
/**
 * Formulierregel (sinds 3.38.0, punt 16 van home en Over, besluit Stephan
 * 9-11-2026): onder een conversieformulier van SureForms de regel
 * "Bel 085 060 1355 of mail naar info@<domein>", zodat een bezoeker ook kan
 * bellen of mailen als het formulier niet werkt.
 *
 * Neemt het snippet "Formulieren: mail-link als fallback" over. Het nummer en
 * het adres komen uit SFP Beheer, Contact (één bron met het blok
 * Contactgegevens); de formulieren per site staan in SFP Beheer,
 * Formulierregel. Zolang het snippet nog aan staat, slaapt dit onderdeel.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Schoon de instelling Formulierregel op: een lijst formulier-ID's.
 *
 * @param  mixed $ruw Ruwe instelling (lijst of tekst met komma's).
 * @return array{formulieren: int[]}
 */
function sfp_page_config_formulierregel_schoon( $ruw ) {
    $lijst = is_array( $ruw ) && isset( $ruw['formulieren'] ) ? $ruw['formulieren'] : $ruw;
    if ( is_string( $lijst ) ) {
        $lijst = preg_split( '/[^0-9]+/', $lijst, -1, PREG_SPLIT_NO_EMPTY );
    }
    $ids = array();
    foreach ( is_array( $lijst ) ? $lijst : array() as $id ) {
        $id = absint( $id );
        if ( $id && ! in_array( $id, $ids, true ) ) {
            $ids[] = $id;
        }
    }
    return array( 'formulieren' => array_slice( $ids, 0, 100 ) );
}

/**
 * De instelling Formulierregel.
 *
 * @return array{formulieren: int[]}
 */
function sfp_page_config_formulierregel_instelling() {
    $alle = function_exists( 'sfp_page_config_instellingen' ) ? sfp_page_config_instellingen() : array();
    return sfp_page_config_formulierregel_schoon( isset( $alle['formulierregel'] ) ? $alle['formulierregel'] : array() );
}

/**
 * De formulieren met de regel: de lijst uit SFP Beheer, plus vanzelf het
 * inschrijf- en het wachtlijstformulier uit Cursusdata (3.38.1).
 *
 * @return int[]
 */
function sfp_page_config_formulierregel_ids() {
    $ids = sfp_page_config_formulierregel_instelling()['formulieren'];
    if ( function_exists( 'sfp_page_config_wachtlijst_formulier_id' ) ) {
        foreach ( array( 'inschrijf_formulier', 'wachtlijst_formulier' ) as $sleutel ) {
            $id = sfp_page_config_wachtlijst_formulier_id( $sleutel );
            if ( $id && ! in_array( $id, $ids, true ) ) {
                $ids[] = $id;
            }
        }
    }
    return $ids;
}

/**
 * Staat het oude snippet nog aan? Dan slaapt het onderdeel, zodat er nooit
 * twee regels onder een formulier staan.
 *
 * @return bool
 */
function sfp_page_config_formulierregel_snippet_actief() {
    return function_exists( 'sfp_form_fallback_render_block' );
}

/**
 * De regel als HTML.
 *
 * @return string
 */
function sfp_page_config_formulierregel_html() {
    if ( ! function_exists( 'sfp_page_config_contact' ) ) {
        return '';
    }
    $c    = sfp_page_config_contact();
    $tel  = $c['telefoon'];
    $mail = (string) $c['email'];
    $mail_html = '' !== $mail ? '<a href="mailto:' . esc_attr( antispambot( $mail ) ) . '">' . esc_html( antispambot( $mail ) ) . '</a>' : '';
    if ( '' !== $tel['tekst'] && '' !== $mail_html ) {
        $tel_html = '<a href="tel:' . esc_attr( $tel['tel'] ) . '">' . esc_html( $tel['tekst'] ) . '</a>';
        $zin      = sprintf( esc_html( sfp_page_config_tekst( 'formulier_regel' ) ), $tel_html, $mail_html );
    } elseif ( '' !== $mail_html ) {
        $zin = sprintf( esc_html( sfp_page_config_tekst( 'formulier_regel_mail' ) ), $mail_html );
    } else {
        return '';
    }
    return '<p class="sfp-form-fallback">' . $zin . '</p>';
}

add_filter( 'render_block', 'sfp_page_config_formulierregel_blok', 20, 2 );

/**
 * Zet de regel onder een formulier uit de lijst.
 *
 * @param  string $html  Getekend blok.
 * @param  array  $block Blok.
 * @return string
 */
function sfp_page_config_formulierregel_blok( $html, $block ) {
    if ( empty( $block['blockName'] ) || 'srfm/form' !== $block['blockName'] || is_admin() || wp_is_json_request() ) {
        return $html;
    }
    if ( ! sfp_page_config_onderdeel_aan( 'formulierregel' ) || sfp_page_config_formulierregel_snippet_actief() ) {
        return $html;
    }
    $id = isset( $block['attrs']['id'] ) ? absint( $block['attrs']['id'] ) : 0;
    if ( ! $id || ! in_array( $id, sfp_page_config_formulierregel_ids(), true ) ) {
        return $html;
    }
    static $css = false;
    $stijl = '';
    if ( ! $css ) {
        $css   = true;
        $stijl = '<style id="sfp-formulierregel">.sfp-form-fallback{margin:.75rem 0 0;font-size:.9em;color:var(--ast-global-color-3)}</style>';
    }
    return $html . $stijl . sfp_page_config_formulierregel_html();
}

/**
 * Eenmalig (3.38.0): neem de lijst formulieren van deze site over uit het
 * snippet "Formulieren: mail-link als fallback". Het snippet zelf blijft
 * ongemoeid; het gaat na de nameting uit.
 *
 * @return void
 */
function sfp_page_config_formulierregel_overnemen() {
    $alle = function_exists( 'sfp_page_config_instellingen' ) ? sfp_page_config_instellingen() : array();
    if ( ! empty( $alle['formulierregel']['formulieren'] ) ) {
        return;
    }
    $snippets = get_posts(
        array(
            'post_type'      => 'asenha_code_snippet',
            'post_status'    => array( 'publish', 'draft', 'private' ),
            's'              => 'sfp_form_fallback_allowlist',
            'posts_per_page' => 5,
            'no_found_rows'  => true,
        )
    );
    $host = preg_replace( '/^www\./i', '', strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );
    foreach ( $snippets as $s ) {
        $code = (string) $s->post_content;
        if ( '' === $host || ! preg_match( "/'" . preg_quote( $host, '/' ) . "'\s*=>\s*array\(([^)]*)\)/", $code, $m ) ) {
            continue;
        }
        $lijst = sfp_page_config_formulierregel_schoon( (string) $m[1] );
        if ( $lijst['formulieren'] ) {
            $alle['formulierregel'] = $lijst;
            update_option( 'sfp_settings', $alle );
        }
        return;
    }
}

/**
 * Voor het scherm Formulierregel in SFP Beheer.
 *
 * @return array
 */
function sfp_page_config_formulierregel_beheer() {
    $formulieren = array();
    foreach ( get_posts(
        array(
            'post_type'      => 'sureforms_form',
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        )
    ) as $f ) {
        $formulieren[] = array(
            'id'    => (int) $f->ID,
            'titel' => html_entity_decode( get_the_title( $f ), ENT_QUOTES, 'UTF-8' ),
        );
    }
    return array(
        'gekozen'     => sfp_page_config_formulierregel_instelling()['formulieren'],
        'formulieren' => $formulieren,
        'snippet'     => sfp_page_config_formulierregel_snippet_actief(),
        'voorbeeld'   => html_entity_decode( wp_strip_all_tags( sfp_page_config_formulierregel_html() ), ENT_QUOTES, 'UTF-8' ),
    );
}
