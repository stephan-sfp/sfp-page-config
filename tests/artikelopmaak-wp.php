<?php
/**
 * Tests voor de artikelopmaak van 3.21.0 in een lokale WordPress (nooit een
 * live site):
 *   wp eval-file wp-content/plugins/sfp-page-config/tests/artikelopmaak-wp.php
 *
 * P2: de H2 in de artikelinhoud loopt terug met pretty, niet met balance.
 * P3: in de kopkaart geen lijn boven het paneel, meer ruimte boven de lijst,
 * een focusring alleen bij het toetsenbord; URL's en DOI's in de
 * bronnenlijst worden een link (paneel en zijbalk), bestaande links blijven
 * zoals ze zijn. P5: het linklabel in de auteursbio is beschrijvend, in
 * alle talen. Maakt niets aan.
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

/* P3: links in de bronnenlijst. */
$l = 'sfp_page_config_artikel_bron_links';
$gevallen = array(
    'doi-adres'          => array( 'Gilovich (2000). https://doi.org/10.1037/0022-3514.78.2.211', '<a href="https://doi.org/10.1037/0022-3514.78.2.211">https://doi.org/10.1037/0022-3514.78.2.211</a>' ),
    'punt erachter'      => array( 'Zie https://example.com/pad.', '<a href="https://example.com/pad">https://example.com/pad</a>.' ),
    'doi: voorvoegsel'   => array( 'Smith, doi:10.1000/xyz123', '<a href="https://doi.org/10.1000/xyz123">doi:10.1000/xyz123</a>' ),
    'kale doi'           => array( 'Smith, 10.1000/xyz123', '<a href="https://doi.org/10.1000/xyz123">10.1000/xyz123</a>' ),
    'haakje erachter'    => array( '(https://example.com/a)', '(<a href="https://example.com/a">https://example.com/a</a>)' ),
    'ampersand'          => array( 'https://example.com/?a=1&amp;b=2', '<a href="https://example.com/?a=1&#038;b=2">https://example.com/?a=1&amp;b=2</a>' ),
);
foreach ( $gevallen as $naam => $g ) {
    $uit = $l( $g[0] );
    $check( 'link: ' . $naam, false !== strpos( $uit, $g[1] ), $uit );
}
$al = 'Bron: <a href="https://example.com/x">https://example.com/x</a> en <em>titel</em>';
$check( 'bestaande link blijft gelijk', $al === $l( $al ), $l( $al ) );
$check( 'tekst zonder adres blijft gelijk', 'Kahneman, D. (2011). Thinking, fast and slow.' === $l( 'Kahneman, D. (2011). Thinking, fast and slow.' ) );
$check( 'jaartal en pagina blijven tekst', false === strpos( $l( 'Journal 78(2), 211-222.' ), '<a' ) );
$check( 'geen javascript-adres', false === stripos( $l( 'javascript:alert(1)' ), '<a' ) );

$inhoud = '<p>Tekst <span class="sfp-tooltip" data-tooltip="Het effect.&lt;br&gt;Source: Gilovich, T. (2000). Journal, 78(2), 211-222. https://doi.org/10.1037/0022-3514.78.2.211">woord</span>.</p>';
$bronnen = sfp_page_config_artikel_bronnen( $inhoud );
$check( 'bron gevonden', 1 === count( $bronnen ), (string) count( $bronnen ) );
$check( 'bron met link en nieuw tabblad', isset( $bronnen[0] ) && false !== strpos( $bronnen[0], '<a target="_blank" rel="noopener" href="https://doi.org/10.1037/0022-3514.78.2.211">' ), isset( $bronnen[0] ) ? $bronnen[0] : '' );

/* P2 en P3: de opmaak. */
$css = (string) file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/artikel.css' );
$check( 'H2 in de inhoud: pretty', false !== strpos( $css, ':root .sfp-art-inhoud>h2{margin:32px 0 4px;text-wrap:pretty}' ) );
$check( 'geen balance meer in de artikelopmaak', false === strpos( $css, 'balance' ) );
$check( 'paneel zonder lijn erboven', false !== strpos( $css, '.sfp-art-paneel{padding:20px 0 16px}' ) && false === strpos( $css, '.sfp-art-paneel{border-top' ) );
$check( 'lijn onder de auteursregel blijft', false !== strpos( $css, 'padding-bottom:16px;border-bottom:1px solid var(--sfp-b-lijn)}' ) );
$check( 'onderstreping actie bij toetsenbord, niet na klikken', false !== strpos( $css, '.sfp-art-actie:is(:hover,:focus-visible)' ) && false === strpos( $css, '.sfp-art-actie:is(:hover,:focus){' ) );
$check( 'geen focusring na een muisklik', false !== strpos( $css, ':root :is(.sfp-art-kop,.sfp-art-zijbron) :is(button,a):focus:not(:focus-visible){outline:none;box-shadow:none}' ) );
$check( 'focusring bij het toetsenbord blijft', false !== strpos( $css, '.sfp-artikel :focus-visible,.sfp-art-balk :focus-visible{outline:2px solid var(--sfp-b-primair)' ) );
$held = (string) file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/blokken/basis.css' );
$check( 'balance op koppen in secties ongewijzigd', false !== strpos( $held, ':root .sfp-sectie :is(h1,h2,h3){text-wrap:balance}' ) );

/* P5: het linklabel in de auteursbio. */
$alle = sfp_page_config_artikel_teksten();
$check( 'label en', 'About %s' === $alle['en']['meer_over'] );
$check( 'label nl', 'Over %s' === $alle['nl']['meer_over'] );
$check( 'label pt', 'Sobre %s' === $alle['pt']['meer_over'] );
foreach ( array( 'en', 'nl', 'pt' ) as $taal ) {
    $check( 'geen "More about" of "Meer over" of "Mais sobre" in het label (' . $taal . ')', ! preg_match( '/^(More about|Meer over|Mais sobre) /', $alle[ $taal ]['meer_over'] ) );
}

echo $fouten ? "$fouten fouten, $goed goed\n" : "OK: $goed controles\n";
