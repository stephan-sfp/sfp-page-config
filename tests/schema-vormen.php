<?php
/**
 * Gegevens voor Google per vorm (3.25.0): citation uit de bronnen, het aanbod
 * van de homepage als ItemList, ontdubbelen met de snippets, de H1 van de
 * testpagina en de opbouw van de keuzes. Met kleine WordPress-stubs; het
 * beeld en het schema op de site controleer je live.
 *
 * Draaien: php tests/schema-vormen.php
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
define( 'ABSPATH', __DIR__ . '/' );
$basis = dirname( __DIR__ ) . '/';

$GLOBALS['wp_filter'] = array();
class Stub_Hook {
    public $callbacks = array();
}
function add_action( $hook, $fn, $prio = 10 ) {
    global $wp_filter;
    if ( ! isset( $wp_filter[ $hook ] ) ) {
        $wp_filter[ $hook ] = new Stub_Hook();
    }
    $wp_filter[ $hook ]->callbacks[ $prio ][] = array( 'function' => $fn );
}
function add_filter( $hook, $fn, $prio = 10 ) {
    add_action( $hook, $fn, $prio );
}
function apply_filters( $hook, $waarde ) {
    return $waarde;
}
function wp_strip_all_tags( $s ) {
    return trim( strip_tags( (string) $s ) );
}
function wp_kses( $s ) {
    return strip_tags( (string) $s, '<a><em><i><strong><b>' );
}
function esc_url_raw( $u ) {
    return filter_var( $u, FILTER_SANITIZE_URL );
}
function esc_url( $u ) {
    return htmlspecialchars( filter_var( $u, FILTER_SANITIZE_URL ), ENT_QUOTES );
}
function esc_html( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES );
}
function home_url( $pad = '' ) {
    return 'https://voorbeeld.test' . $pad;
}

/*
 * Kleine blokparser voor de test: genoeg voor geneste blokken met
 * attributen en innerHTML, zoals parse_blocks() ze geeft.
 */
function parse_blocks( $html ) {
    $pos = 0;
    return stub_blokken( $html, $pos, null );
}
function stub_blokken( $html, &$pos, $eind ) {
    $uit = array();
    while ( preg_match( '/<!--\s+(\/)?wp:([a-z0-9\/-]+)\s*(\{.*?\})?\s*(\/)?-->/s', $html, $m, PREG_OFFSET_CAPTURE, $pos ) ) {
        $start = $m[0][1];
        $tekst = substr( $html, $pos, $start - $pos );
        $pos   = $start + strlen( $m[0][0] );
        $naam  = false === strpos( $m[2][0], '/' ) ? 'core/' . $m[2][0] : $m[2][0];
        if ( '/' === ( $m[1][0] ?? '' ) && $m[1][1] >= 0 ) {
            $GLOBALS['stub_rest'] = $tekst;
            return $uit;
        }
        $blok = array(
            'blockName'   => $naam,
            'attrs'       => isset( $m[3][0] ) && $m[3][1] >= 0 ? (array) json_decode( $m[3][0], true ) : array(),
            'innerBlocks' => array(),
            'innerHTML'   => '',
        );
        if ( ! ( isset( $m[4] ) && $m[4][1] >= 0 ) ) {
            $voor                 = $pos;
            $blok['innerBlocks']  = stub_blokken( $html, $pos, $naam );
            $blok['innerHTML']    = $blok['innerBlocks'] ? '' : (string) $GLOBALS['stub_rest'];
        }
        $uit[] = $blok;
    }
    return $uit;
}

require $basis . 'includes/schema-vormen.php';

$fouten = 0;
$checks = 0;
function sv_is( $v, $wat ) {
    global $fouten, $checks;
    $checks++;
    if ( ! $v ) {
        $fouten++;
        echo "FOUT: $wat\n";
    }
}

// Bronnen: de echte parser uit artikel.php, los ingeladen.
$artikel = (string) file_get_contents( $basis . 'includes/artikel.php' );
foreach ( array( 'sfp_page_config_artikel_bronnen', 'sfp_page_config_artikel_bron_links' ) as $fn ) {
    preg_match( '/function ' . $fn . '\(.*?\n}\n/s', $artikel, $f );
    sv_is( ! empty( $f[0] ), "functie $fn gevonden" );
    eval( $f[0] ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- alleen in de test.
}

$bron   = 'Gilovich, T., Medvec, V. H., &amp; Savitsky, K. (2000). The spotlight effect in social judgment. Journal of Personality and Social Psychology, 78(2), 211-222. https://doi.org/10.1037/0022-3514.78.2.211';
$inhoud = '<p>Tekst <span class="sfp-tooltip" data-tooltip="Uitleg.&lt;br&gt;Source: ' . $bron . '">a</span> en <span data-tooltip="Nog eens.&lt;br&gt;Source: ' . $bron . '">b</span> en <span data-tooltip="Bron: Anderson, C. (2016). &lt;em&gt;TED Talks&lt;/em&gt;. Boston: Houghton Mifflin Harcourt.">c</span> <span data-tooltip="Geen bron hier">d</span></p>';
$werken = sfp_page_config_citation_werken( $inhoud );
sv_is( 2 === count( $werken ), 'twee bronnen, de dubbele een keer' );
sv_is( 'CreativeWork' === ( $werken[0]['@type'] ?? '' ), 'bron als CreativeWork' );
sv_is( 0 === strpos( $werken[0]['name'] ?? '', 'Gilovich, T., Medvec, V. H., & Savitsky, K. (2000).' ), 'naam is de bronregel, zonder HTML-entiteiten' );
sv_is( 'https://doi.org/10.1037/0022-3514.78.2.211' === ( $werken[0]['url'] ?? '' ), 'url is de DOI-link' );
sv_is( ! isset( $werken[1]['url'] ), 'bron zonder link: geen url' );
sv_is( false === strpos( $werken[1]['name'], '<' ), 'naam zonder HTML' );
sv_is( array() === sfp_page_config_citation_werken( '<p>Geen tooltips</p>' ), 'geen bronnen: lege lijst' );

// Ontdubbelen met de snippets.
sv_is( ! sfp_page_config_citation_door_snippet(), 'zonder snippet: plugin levert citation' );
sv_is( ! sfp_page_config_aanbod_door_snippet(), 'zonder snippet: plugin levert het aanbod' );
add_action( 'wp_head', 'sfp_cs_output_schema', 99 );
add_action( 'wp_head', 'dps_homepage_courselist_schema', 100 );
sv_is( sfp_page_config_citation_door_snippet(), 'snippet Bronvermeldingen herkend' );
sv_is( sfp_page_config_aanbod_door_snippet(), 'snippet Trainingsaanbod herkend (naam per site)' );

// Aanbod op de homepage: de opbouw van SwS 377.
$home = '<!-- wp:sfp/sectie {"anker":"intro"} --><!-- wp:heading --><h2>Intro</h2><!-- /wp:heading --><!-- /wp:sfp/sectie -->'
    . '<!-- wp:sfp/sectie {"achtergrond":"tint","anker":"services"} -->'
    . '<!-- wp:heading --><h2 class="wp-block-heading">Three ways to work together</h2><!-- /wp:heading -->'
    . '<!-- wp:sfp/kolommen {"indeling":"drie"} -->'
    . '<!-- wp:sfp/kaart {"link":"/presentation-coaching/"} --><!-- wp:heading {"level":3} --><h3>Individual presentation coaching</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Sessions on your own talks &amp; demos.</p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"is-style-sfp-pijl"} --><p class="is-style-sfp-pijl"><a href="/presentation-coaching/">View packages</a></p><!-- /wp:paragraph --><!-- /wp:sfp/kaart -->'
    . '<!-- wp:sfp/kaart {"link":"https://elders.test/x/"} --><!-- wp:heading {"level":3} --><h3>Tweede</h3><!-- /wp:heading --><!-- /wp:sfp/kaart -->'
    . '<!-- wp:sfp/kaart --><!-- wp:heading {"level":3} --><h3>Zonder link</h3><!-- /wp:heading --><!-- /wp:sfp/kaart -->'
    . '<!-- /wp:sfp/kolommen --><!-- /wp:sfp/sectie -->';
$post               = new stdClass();
$post->post_content = $home;
class WP_Post {
}
$wp_post = new WP_Post();
foreach ( get_object_vars( $post ) as $k => $v ) {
    $wp_post->$k = $v;
}
$lijst = sfp_page_config_aanbod_gegevens( $wp_post );
sv_is( is_array( $lijst ) && 'ItemList' === $lijst['@type'], 'ItemList gemaakt' );
sv_is( 'Three ways to work together' === ( $lijst['name'] ?? '' ), 'naam is de zichtbare sectiekop' );
sv_is( 'https://voorbeeld.test/#services' === ( $lijst['@id'] ?? '' ), '@id op de home met het anker' );
sv_is( 2 === count( $lijst['itemListElement'] ?? array() ), 'kaart zonder link valt weg' );
$eerste = $lijst['itemListElement'][0] ?? array();
sv_is( 1 === ( $eerste['position'] ?? 0 ) && 'Service' === ( $eerste['item']['@type'] ?? '' ), 'eerste item: Service op positie 1' );
sv_is( 'Individual presentation coaching' === ( $eerste['item']['name'] ?? '' ), 'naam uit de kop van de kaart' );
sv_is( 'https://voorbeeld.test/presentation-coaching/' === ( $eerste['item']['url'] ?? '' ), 'relatieve link wordt een volledig adres' );
sv_is( 'Sessions on your own talks & demos.' === ( $eerste['item']['description'] ?? '' ), 'omschrijving uit de eerste alinea, niet uit de pijllink' );
sv_is( ! isset( $lijst['itemListElement'][1]['item']['description'] ), 'kaart zonder alinea: geen omschrijving' );
$zonder               = new WP_Post();
$zonder->post_content = '<!-- wp:paragraph --><p>Geen aanbod</p><!-- /wp:paragraph -->';
sv_is( null === sfp_page_config_aanbod_gegevens( $zonder ), 'geen sectie services: geen ItemList' );

// Pijler gebruikt dezelfde bronnen en dezelfde ontdubbeling.
$vormen = (string) file_get_contents( $basis . 'includes/vormen.php' );
sv_is( false !== strpos( $vormen, 'sfp_page_config_citation_door_snippet()' ) && false !== strpos( $vormen, 'sfp_page_config_citation_werken( $gids->post_content )' ), 'pijler: citation als CreativeWork, niet naast het snippet' );
$schema = (string) file_get_contents( $basis . 'includes/schema-vormen.php' );
sv_is( false !== strpos( $schema, "get_permalink( \$post ) . '#article'" ), 'artikel: zelfde @id als de Article van SureRank' );
sv_is( false !== strpos( $schema, 'sfp_page_config_is_gids( $post )' ), 'artikel: pijler krijgt geen tweede citation-knoop' );
$main = (string) file_get_contents( $basis . 'sfp-page-config.php' );
sv_is( false !== strpos( $main, "'includes/schema-vormen.php'" ), 'bestand wordt geladen' );

// Zelftest: H1 op de testpagina.
$weergave = (string) file_get_contents( $basis . 'includes/zelftest-weergave.php' );
preg_match( '/function sfp_zelftest_starttitel\(.*?\n}\n/s', $weergave, $f );
eval( $f[0] ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- alleen in de test.
$pakket = array( 'inhoud' => array( 'start' => array( 'kop' => array( 'en' => 'How effective are you when you speak to a group?', 'nl' => 'Hoe effectief spreek jij voor groepen?' ) ) ) );
sv_is( 'How effective are you when you speak to a group?' === sfp_zelftest_starttitel( $pakket, 'en' ), 'titel in de taal van de test' );
sv_is( '' === sfp_zelftest_starttitel( $pakket, 'pt' ), 'geen titel in die taal: leeg' );
sv_is( 'Losse kop' === sfp_zelftest_starttitel( array( 'inhoud' => array( 'start' => array( 'kop' => 'Losse kop' ) ) ), 'en' ), 'titel als tekst' );
sv_is( false !== strpos( $weergave, '<h1 class="sfp-zt-h1 sfp-zt-sr">' ) && false !== strpos( $weergave, "data-pagina=\"1\"" ), 'serverHTML: H1 en kenmerk paginamodus' );
$js = (string) file_get_contents( $basis . 'assets/zelftest/zelftest.js' );
sv_is( false !== strpos( $js, "wortel.getAttribute('data-pagina') === '1' ? 'h1' : 'h2'" ), 'startscherm: H1 op de testpagina, anders H2' );
sv_is( 1 === substr_count( $js, 'kopStart' ) - 2, 'alleen de titel van het startscherm wisselt' );
$css = (string) file_get_contents( $basis . 'assets/zelftest/zelftest.css' );
sv_is( false !== strpos( $css, '.sfp-zt .sfp-zt-h1{word-spacing:normal}' ), 'titel: geen woordafstand van een algemene H1-regel' );
sv_is( false !== strpos( $css, '.sfp-zt .sfp-zt-h1.sfp-zt-sr{position:absolute;width:1px;height:1px' ), 'H1 in het laadvak onzichtbaar' );

// Zelftest: keuzes op tablet en desktop zoals op de telefoon.
sv_is( false !== strpos( $css, "@media (min-width:641px) and (min-height:521px){\nbody.sfp-zt-testpagina .sfp-zt-keuzes{grid-template-columns:1fr}\nbody.sfp-zt-testpagina .sfp-zt-keuzes.heeft-mob{display:none}\nbody.sfp-zt-testpagina .sfp-zt-mob{display:flex;flex-direction:column;margin-top:14px}\n}" ), 'testpagina: een knop en de korte versie als link vanaf 641 px' );

// Geen functienaam dubbel in de plugin (een dubbele naam is een fatale fout bij het laden).
$namen = array();
$it    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $basis, FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $bestand ) {
    $pad = (string) $bestand;
    if ( '.php' !== substr( $pad, -4 ) || false !== strpos( $pad, '/tests/' ) || false !== strpos( $pad, '/.git/' ) || false !== strpos( $pad, '/vendor/' ) || false !== strpos( $pad, '/bouw/' ) ) {
        continue;
    }
    preg_match_all( '/^function\s+([a-zA-Z0-9_]+)\s*\(/m', (string) file_get_contents( $pad ), $mm );
    foreach ( $mm[1] as $naam ) {
        $namen[ strtolower( $naam ) ][] = substr( $pad, strlen( $basis ) );
    }
}
$dubbel = array_filter( $namen, function ( $plekken ) {
    return count( $plekken ) > 1;
} );
foreach ( $dubbel as $naam => $plekken ) {
    echo "Dubbel: $naam in " . implode( ', ', $plekken ) . "\n";
}
sv_is( ! $dubbel, 'geen functienaam dubbel in de plugin' );

// Auteursbio: radius uit de netwerknorm.
$bio = (string) file_get_contents( $basis . 'assets/blokken/auteursbio.css' );
sv_is( false !== strpos( $bio, 'border-radius:var(--sfp-b-r-kaart,6px)}' ) && false === strpos( $bio, 'border-radius:3px' ), 'auteursbio: vinkjeslijst 6 px' );

echo $fouten ? "$fouten van $checks controles faalden\n" : "Alle $checks controles geslaagd\n";
exit( $fouten ? 1 : 0 );
