<?php
/**
 * Tests voor 3.55.0: plek van de promokaart in de tekst (besluit Stephan
 * 13-11-2026). Vóór de tweede H2, minstens drie alinea's tot een ander
 * UX-element, anders het eerstvolgende punt dat voldoet, nooit na de
 * conclusie of de FAQ. Met kleine WordPress-stubs; alleen de functies voor
 * de bovenste laag en de plek worden geladen.
 *
 * Draaien: php tests/promo-plek.php
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
error_reporting( E_ALL );
function wp_strip_all_tags( $s ) {
    return trim( strip_tags( preg_replace( '#<(script|style)[^>]*?>.*?</\1>#si', '', (string) $s ) ) );
}
function apply_filters( $h, $v ) {
    return $v;
}
// Alleen de benodigde functies uit artikel.php laden (de rest vraagt WordPress).
$bron = (string) file_get_contents( dirname( __DIR__ ) . '/includes/artikel.php' );
foreach ( array( 'sfp_page_config_artikel_bovenlaag', 'sfp_page_config_artikel_bovenlaag_deel', 'sfp_page_config_artikel_is_slot', 'sfp_page_config_artikel_promo_in_tekst', 'sfp_page_config_artikel_promo_plek' ) as $f ) {
    if ( ! preg_match( '/^function ' . $f . '\(.*?^\}$/ms', $bron, $m ) ) {
        echo "FOUT: functie $f niet gevonden\n";
        exit( 1 );
    }
    eval( $m[0] );
}

$fouten = 0;
$checks = 0;
function pp_is( $v, $wat ) {
    global $fouten, $checks;
    $checks++;
    if ( ! $v ) {
        $fouten++;
        echo "FOUT: $wat\n";
    }
}
$K = '<aside class="sfp-art-promo sfp-art-promo--inline">KAART</aside>';
function pp_volgorde( $html ) {
    // Volgorde van koppen, alinea's (p), UX (u) en de kaart (K) in de bovenste laag.
    $uit = array();
    foreach ( sfp_page_config_artikel_bovenlaag( $html ) as $d ) {
        $stuk = substr( $html, $d['begin'], $d['eind'] - $d['begin'] );
        if ( false !== strpos( $stuk, 'KAART' ) ) {
            $uit[] = 'K';
        } elseif ( 'kop' === $d['soort'] ) {
            $uit[] = 'h' . $d['niveau'] . ':' . wp_strip_all_tags( $stuk );
        } else {
            $uit[] = 'tekst' === $d['soort'] ? 'p' : 'u';
        }
    }
    return implode( ' ', $uit );
}
$p = function ( $n ) {
    return str_repeat( '<p>Tekst.</p>', $n );
};
$u = '<aside class="sfp-kader">Kader</aside>';

// 1. Ruim: kaart direct vóór de tweede H2.
$html = $p( 2 ) . '<h2>Een</h2>' . $p( 4 ) . '<h2>Twee</h2>' . $p( 4 ) . '<h2>Conclusie</h2>' . $p( 1 );
pp_is( 'p p h2:Een p p p p K h2:Twee p p p p h2:Conclusie p' === pp_volgorde( sfp_page_config_artikel_promo_in_tekst( $html, $K ) ), 'ruim: vóór de tweede H2' );

// 2. Te weinig tekst vóór de tweede H2: eerstvolgende punt dat voldoet.
$html = '<h2>Een</h2>' . $p( 1 ) . $u . $p( 1 ) . '<h2>Twee</h2>' . $p( 1 ) . '<h3>a</h3>' . $p( 2 ) . '<h3>b</h3>' . $p( 3 ) . $u . '<h2>Conclusie</h2>' . $p( 1 );
$v = pp_volgorde( sfp_page_config_artikel_promo_in_tekst( $html, $K ) );
pp_is( 'h2:Een p u p h2:Twee p h3:a p K p h3:b p p p u h2:Conclusie p' === $v, 'eerstvolgend punt dat voldoet, niet tussen kop en alinea: ' . $v );

// 3. Nergens ruimte: direct vóór de tweede H2, nooit na de conclusie of FAQ.
$html = '<h2>Een</h2>' . $p( 1 ) . $u . '<h2>Twee</h2>' . $p( 1 ) . $u . '<h2>Conclusie</h2>' . $p( 3 ) . '<h2>Veelgestelde vragen</h2><div class="sfp-faq wp-block-sfp-faq">FAQ</div>';
pp_is( 'h2:Een p u K h2:Twee p u h2:Conclusie p p p h2:Veelgestelde vragen u' === pp_volgorde( sfp_page_config_artikel_promo_in_tekst( $html, $K ) ), 'terugval: vóór de tweede H2' );

// 4. Alleen een H2 en daarna FAQ: vóór de FAQ, niet erna.
$html = '<h2>Een</h2>' . $p( 1 ) . $u . $p( 1 ) . '<h2>Veelgestelde vragen</h2><div class="sfp-faq">FAQ</div>';
pp_is( 'h2:Een p u p K h2:Veelgestelde vragen u' === pp_volgorde( sfp_page_config_artikel_promo_in_tekst( $html, $K ) ), 'één H2 en FAQ: vóór de FAQ' );

// 5. Engels en Portugees slot.
$html = '<h2>One</h2>' . $p( 1 ) . $u . '<h2>Two</h2>' . $p( 1 ) . '<h2>Frequently asked questions</h2>' . $p( 5 );
$v    = pp_volgorde( sfp_page_config_artikel_promo_in_tekst( $html, $K ) );
pp_is( strpos( $v, 'K' ) < strpos( $v, 'Frequently' ), 'en: niet na de FAQ: ' . $v );
$html = '<h2>Um</h2>' . $p( 1 ) . $u . '<h2>Dois</h2>' . $p( 1 ) . '<h2>Conclusão</h2>' . $p( 5 );
$v    = pp_volgorde( sfp_page_config_artikel_promo_in_tekst( $html, $K ) );
pp_is( strpos( $v, 'K' ) < strpos( $v, 'Conclus' ), 'pt: niet na de conclusie: ' . $v );

// 6. Een kop met een slotwoord als deel van een ander woord is geen slot.
pp_is( ! sfp_page_config_artikel_is_slot( array( 'attr' => '', 'html' => '<h2>Faqtoren van succes</h2>' ), 'kop', 2 ), 'geen slot op een deel van een woord' );
pp_is( sfp_page_config_artikel_is_slot( array( 'attr' => '', 'html' => '<h2>Tot slot: oefen</h2>' ), 'kop', 2 ), 'tot slot' );
pp_is( ! sfp_page_config_artikel_is_slot( array( 'attr' => '', 'html' => '<h3>Conclusie</h3>' ), 'kop', 3 ), 'H3 is geen grens' );

// 7. Geen H2: onderaan (zoals voorheen).
$html = $p( 4 );
pp_is( 'p p p p K' === pp_volgorde( sfp_page_config_artikel_promo_in_tekst( $html, $K ) ), 'zonder H2 onderaan' );

// 8. Echte inhoud: DPS chunking (post 24660, gemeten 13-11), vier H2's.
$fix = getenv( 'SFP_FIXTURE_24660' );
if ( $fix && is_readable( $fix ) ) {
    $echt = json_decode( (string) file_get_contents( $fix ), true )['content']['rendered'];
    $uit  = sfp_page_config_artikel_promo_in_tekst( $echt, $K );
    $k    = strpos( $uit, 'KAART' );
    pp_is( $k < strpos( $uit, '>Conclusie<' ) && $k < strpos( $uit, '>Veelgestelde vragen<' ), 'chunking: kaart vóór conclusie en FAQ' );
    pp_is( $k < strpos( $uit, 'id="toepassing"' ), 'chunking: kaart vóór de tweede H2 (' . $k . ')' );
}

echo "$checks controles, $fouten fouten\n";
exit( $fouten ? 1 : 0 );
