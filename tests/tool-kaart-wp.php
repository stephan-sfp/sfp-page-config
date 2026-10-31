<?php
/**
 * Tests voor de kaartweergave van de toolmotor (3.21.0) in een lokale
 * WordPress (nooit een live site):
 *   wp eval-file wp-content/plugins/sfp-page-config/tests/tool-kaart-wp.php [pad-naar-json]
 *
 * Standaard met tests/tool-voorbeelden/sws/fast-kaart.json (de FAST-tool met
 * weergave.modus "kaart"). Controleert de beschrijving (weergave, groepen,
 * iconen, tegels, kwadrant met assen, voorbeeld uit een bank), de controle
 * op een fout kwadrant en op groepen die niet kloppen, de klasse van de
 * kaart, dat tools zonder kaart onveranderd blijven, de held met de tool in
 * de tweede kolom, een concepttool in het blok alleen voor wie hem mag
 * bewerken, en de vaste teksten in drie talen. De motor zelf in de browser:
 * de proef met Playwright in het verslag. Ruimt alles op.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$fouten = 0;
$goed   = 0;
$check  = function ( $naam, $ok, $detail = '' ) use ( &$fouten, &$goed ) {
    if ( $ok ) {
        ++$goed;
        return;
    }
    ++$fouten;
    echo "FOUT: $naam" . ( '' !== $detail ? ' (' . $detail . ')' : '' ) . "\n";
};

$pad  = isset( $args[0] ) && '' !== $args[0] ? $args[0] : SFP_PAGE_CONFIG_DIR . 'tests/tool-voorbeelden/sws/fast-kaart.json';
$json = (string) file_get_contents( $pad );
$ruw  = json_decode( $json, true );
$check( 'voorbeeld gelezen', is_array( $ruw ) );

$bewaard = get_option( 'sfp_settings', array() );
$s       = is_array( $bewaard ) ? $bewaard : array();
$s['onderdelen']['tools'] = '1';
update_option( 'sfp_settings', $s );
if ( ! post_type_exists( 'tool' ) ) {
    sfp_page_config_tool_posttype();
    sfp_page_config_tool_meta();
}

/* De beschrijving. */
$b = sfp_page_config_tool_normaliseer( $ruw );
$c = sfp_page_config_tool_controleer( $b );
$check( 'geen fouten: ' . implode( ' ', $c['fouten'] ), empty( $c['fouten'] ) );
$check( 'weergave kaart', 'kaart' === $b['weergave']['modus'] );
$check( 'vier groepen F A S T', 'FAST' === implode( '', wp_list_pluck( $b['weergave']['groepen'], 'letter' ) ) );
$check( 'groepen tellen alle stappen', count( $b['stappen'] ) === array_sum( wp_list_pluck( $b['weergave']['groepen'], 'stappen' ) ) );
$velden = array();
foreach ( $b['stappen'] as $st ) {
    foreach ( $st['velden'] as $v ) {
        $velden[ $v['id'] ] = $v;
    }
}
$check( 'iconen bij de werkwoorden', 'info' === $velden['verb']['opties'][0]['icoon'] && 'ster' === $velden['verb']['opties'][4]['icoon'] );
$check( 'kwadrant met assen', 'kwadrant' === $velden['kwadrant']['tegels'] && array( 'Know little', 'Know a lot' ) === $velden['kwadrant']['assen']['kolommen'] && array( 'Not keen', 'Keen' ) === $velden['kwadrant']['assen']['rijen'] );
$check( 'voorbeeld uit een bank (M9)', false !== strpos( $velden['res']['voorbeeld'], 'bank:resvoorbeeld' ) && isset( $b['banken']['resvoorbeeld']['Persuade'] ) );

$fout = $ruw;
$fout['stappen'][0]['velden'][0]['opties'][0]['icoon'] = 'raket';
$x = sfp_page_config_tool_normaliseer( $fout );
$check( 'onbekend icoon valt weg', ! isset( $x['stappen'][0]['velden'][0]['opties'][0]['icoon'] ) );

$fout = $ruw;
foreach ( $fout['stappen'] as $i => $st ) {
    if ( 'kwadrant' === $st['velden'][0]['id'] ) {
        array_pop( $fout['stappen'][ $i ]['velden'][0]['opties'] );
    }
}
$x = sfp_page_config_tool_controleer( sfp_page_config_tool_normaliseer( $fout ) );
$check( 'kwadrant met drie opties is een fout', (bool) preg_grep( '/kwadrant/', $x['fouten'] ) );

$fout = $ruw;
$fout['weergave']['groepen'][0]['stappen'] = 2;
$x = sfp_page_config_tool_normaliseer( $fout );
$check( 'groepen die niet kloppen: genegeerd met melding', array() === $x['weergave']['groepen'] && (bool) preg_grep( '/groepen/', $x['genegeerd'] ) );

$zonder = $ruw;
unset( $zonder['weergave'] );
$x = sfp_page_config_tool_normaliseer( $zonder );
$check( 'zonder weergave: geen kaart', '' === $x['weergave']['modus'] );

/* Weergave in de pagina. */
$maak = function ( $json, $status = 'publish' ) {
    return (int) wp_insert_post(
        array(
            'post_type'    => 'tool',
            'post_status'  => $status,
            'post_title'   => 'Kaartproef ' . wp_generate_password( 6, false ),
            'post_author'  => 1,
            'post_content' => '<!-- wp:paragraph --><p>Uitleg.</p><!-- /wp:paragraph -->',
            'meta_input'   => array( 'sfp_tool' => wp_slash( $json ) ),
        )
    );
};
$id      = $maak( $json );
$id_oud  = $maak( wp_json_encode( $zonder ) );
$concept = $maak( $json, 'draft' );
$check( 'tools aangemaakt', $id && $id_oud && $concept );

$html = sfp_page_config_tool_html( $id );
$check( 'klasse kaart', false !== strpos( $html, 'sfp-tool--kaart' ) );
$check( 'weergave gaat mee naar de browser', false !== strpos( $html, '"modus":"kaart"' ) );
$check( 'tool zonder kaart: geen klasse', false === strpos( sfp_page_config_tool_html( $id_oud ), 'sfp-tool--kaart' ) );

$held = do_blocks( '<!-- wp:sfp/held --><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Kop</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Intro.</p><!-- /wp:paragraph --><!-- wp:sfp/tool {"tool":' . $id . '} /--><!-- /wp:sfp/held -->' );
$check( 'held met tool: klasse', false !== strpos( $held, 'sfp-held--tool' ) );
$held_zonder = do_blocks( '<!-- wp:sfp/held --><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Kop</h1><!-- /wp:heading --><!-- /wp:sfp/held -->' );
$check( 'held zonder tool: geen klasse', false === strpos( $held_zonder, 'sfp-held--tool' ) );
$css = (string) file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/blokken/held.css' );
$check( 'held: tool in de tweede kolom vanaf 981 px', false !== strpos( $css, '.sfp-held--tool .sfp-held__binnen>.sfp-tool{grid-column:2' ) );

/* Concepttool in het blok: alleen voor wie hem mag bewerken. */
$oude_gebruiker = get_current_user_id();
wp_set_current_user( 1 );
$check( 'concept: zichtbaar voor de beheerder', false !== strpos( sfp_page_config_render_tool_blok( array( 'tool' => $concept ) ), 'sfp-tool__app' ) );
wp_set_current_user( 0 );
$check( 'concept: leeg voor een bezoeker', '' === sfp_page_config_render_tool_blok( array( 'tool' => $concept ) ) );
$check( 'gepubliceerd: zichtbaar voor een bezoeker', false !== strpos( sfp_page_config_render_tool_blok( array( 'tool' => $id ) ), 'sfp-tool__app' ) );
wp_set_current_user( $oude_gebruiker );

/* Vaste teksten in drie talen met dezelfde plaatshouders. */
$alle = sfp_page_config_artikel_teksten();
foreach ( array( 'tool_terug', 'tool_voortgang', 'tool_groep_van', 'tool_letter_klaar' ) as $sleutel ) {
    $check( 'tekst ' . $sleutel . ' in drie talen', isset( $alle['en'][ $sleutel ], $alle['nl'][ $sleutel ], $alle['pt'][ $sleutel ] ) );
    preg_match_all( '/%(\d\$)?[sd]/', $alle['en'][ $sleutel ], $en );
    foreach ( array( 'nl', 'pt' ) as $taal ) {
        preg_match_all( '/%(\d\$)?[sd]/', $alle[ $taal ][ $sleutel ], $x );
        $check( 'plaatshouders ' . $sleutel . ' ' . $taal, $en[0] === $x[0] );
    }
}

/* Opmaak op rol: geen kleurcode en geen fontnaam in de nieuwe regels. */
$tcss  = (string) file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/tool/tool.css' );
$kaart = substr( $tcss, (int) strpos( $tcss, 'Kaartweergave (3.21.0)' ) );
$check( 'kaartopmaak zonder merkkleur', ! preg_match( '/#[0-9a-fA-F]{3,6}\b(?!\))/', str_replace( array( '#fff', '#000' ), '', $kaart ) ) );
$check( 'kaartopmaak zonder fontnaam', ! preg_match( '/font-family:\s*["\']?[A-Z]/', $kaart ) );

foreach ( array( $id, $id_oud, $concept ) as $x ) {
    wp_delete_post( $x, true );
}
update_option( 'sfp_settings', $bewaard );

echo $fouten ? "$fouten fouten, $goed goed\n" : "OK: $goed controles\n";
