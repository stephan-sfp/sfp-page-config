<?php
/**
 * Tests voor de kopinstelling sfpTocH3 (3.56.0) in een lokale WordPress
 * (nooit een live site):
 *   wp eval-file wp-content/plugins/sfp-page-config/tests/toc-h3-wp.php
 *
 * Een H2 met sfpTocH3 zet de losse H3's tot de volgende H2 als ingesprongen
 * links in de inhoudsopgave (zijkolom) en als inklapbare lijst in de
 * hoofdstukbalk. Standaard uit. Maakt niets aan.
 *
 * @package SFP_Page_Config
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}

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

$kop = function ( $niveau, $tekst, $id = '', $aan = false ) {
    $attrs = array();
    if ( 2 !== $niveau ) {
        $attrs['level'] = $niveau;
    }
    if ( $id ) {
        $attrs['anchor'] = $id;
    }
    if ( $aan ) {
        $attrs['sfpTocH3'] = true;
    }
    $json = $attrs ? ' ' . wp_json_encode( $attrs ) : '';
    return '<!-- wp:heading' . $json . ' --><h' . $niveau . ' class="wp-block-heading"' . ( $id ? ' id="' . $id . '"' : '' ) . '>' . $tekst . '</h' . $niveau . '><!-- /wp:heading -->'
        . '<!-- wp:paragraph --><p>Tekst.</p><!-- /wp:paragraph -->';
};

$inhoud = $kop( 2, 'Waarom', 'waarom' )
    . $kop( 3, 'Niet mee, zonder instelling' )
    . $kop( 2, '8 tips voor je spreekkaartjes', 'acht-tips', true )
    . $kop( 3, '1. Schrijf in steekwoorden', '1-presentatiekaartjes' )
    . '<!-- wp:group --><div class="wp-block-group"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">In een kader</h3><!-- /wp:heading --></div><!-- /wp:group -->'
    . $kop( 3, '2. Thema&rsquo;s een kleur' )
    . $kop( 4, 'Een H4 telt niet' )
    . $kop( 2, 'Slot', 'slot' )
    . $kop( 3, 'Na de volgende H2' );

$sub = sfp_page_config_artikel_subkoppen( $inhoud );
$check( 'subkoppen alleen bij het tweede hoofdstuk', array( 1 ) === array_keys( $sub ), wp_json_encode( $sub ) );
$check( 'twee losse H3s, niet die in het kader', isset( $sub[1] ) && 2 === count( $sub[1] ), wp_json_encode( $sub ) );

$html = do_blocks( $inhoud );
list( $html2, $h ) = sfp_page_config_artikel_hoofdstukken( $html, sfp_page_config_artikel_hoofdstuktitels( $inhoud ), $sub );
$check( 'drie hoofdstukken', 3 === count( $h ), wp_json_encode( $h ) );
$check( 'eerste hoofdstuk zonder sub', empty( $h[0]['sub'] ) );
$check( 'derde hoofdstuk zonder sub', empty( $h[2]['sub'] ) );
$check( 'twee onderdelen', isset( $h[1]['sub'] ) && 2 === count( $h[1]['sub'] ), wp_json_encode( $h[1] ) );
$check( 'bestaand anker blijft', isset( $h[1]['sub'][0] ) && '1-presentatiekaartjes' === $h[1]['sub'][0]['id'] );
$nieuw = isset( $h[1]['sub'][1]['id'] ) ? $h[1]['sub'][1]['id'] : '';
$check( 'nieuw anker op de H3', '' !== $nieuw && false !== strpos( $html2, 'id="' . $nieuw . '"' ), $nieuw );
$check( 'titel leesbaar', isset( $h[1]['sub'][1] ) && "2. Thema\u{2019}s een kleur" === $h[1]['sub'][1]['titel'], isset( $h[1]['sub'][1] ) ? $h[1]['sub'][1]['titel'] : '' );
$check( 'H3 in kader krijgt geen anker', false !== strpos( $html2, '<h3 class="wp-block-heading">In een kader</h3>' ) );
$check( 'H3 zonder instelling krijgt geen anker', false !== strpos( $html2, '<h3 class="wp-block-heading">Niet mee, zonder instelling</h3>' ) );

// Standaard uit: zonder $sub gelijk aan de oude uitkomst.
list( , $h0 ) = sfp_page_config_artikel_hoofdstukken( $html, sfp_page_config_artikel_hoofdstuktitels( $inhoud ) );
$check( 'zonder instelling geen sub', empty( $h0[1]['sub'] ) );

$post = get_posts( array( 'numberposts' => 1, 'post_type' => 'post', 'post_status' => 'any' ) );
$post = $post ? $post[0] : (object) array( 'ID' => 0, 'post_content' => $inhoud, 'post_author' => 1 );
$zij  = sfp_page_config_artikel_zijkolom( $post, $h );
$check( 'zijkolom: ingesprongen lijst', 1 === substr_count( $zij, '<ol class="sfp-art-toc__sub">' ) );
$check( 'zijkolom: link naar de H3', false !== strpos( $zij, 'href="#1-presentatiekaartjes"' ) );
$check( 'zijkolom: sub staat in het li van het hoofdstuk', false !== strpos( $zij, 'spreekkaartjes</a><ol class="sfp-art-toc__sub">' ) );
$balk = sfp_page_config_artikel_balk( $h );
$check( 'balk: klapknop dicht', false !== strpos( $balk, 'class="sfp-art-balk__klap" aria-expanded="false" aria-controls="sfp-art-balk-sub-2"' ), $balk );
$check( 'balk: lijst start verborgen', false !== strpos( $balk, '<ol class="sfp-art-balk__sub" id="sfp-art-balk-sub-2" hidden>' ) );
$check( 'balk: knop heeft een label', (bool) preg_match( '/aria-label="[^"]+8 tips voor je spreekkaartjes"/', $balk ), $balk );
$check( 'balk: geen klapknop zonder sub', 1 === substr_count( $balk, 'sfp-art-balk__klap' ) );

$type = WP_Block_Type_Registry::get_instance()->get_registered( 'core/heading' );
$check( 'attribuut geregistreerd, standaard uit', $type && isset( $type->attributes['sfpTocH3'] ) && false === $type->attributes['sfpTocH3']['default'] );

echo "Goed: $goed, fout: $fouten\n";
exit( $fouten ? 1 : 0 );
