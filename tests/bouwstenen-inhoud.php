<?php
/**
 * Run with: php tests/bouwstenen-inhoud.php
 *
 * Checks the content building blocks of 3.20.0 with small WordPress stubs:
 * sources from tooltips (same rules as the snippet v1.11: page numbers and
 * access dates stripped, duplicates merged, links kept, bare URLs linked,
 * grouping per letter), the copy block (stored wrapper removed, long text
 * detected), the author bio checklist, and that old codes are only added
 * when no snippet registered them.
 */
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );

define( 'ABSPATH', __DIR__ . '/' );
define( 'SFP_PAGE_CONFIG_DIR', dirname( __DIR__ ) . '/' );
define( 'DAY_IN_SECONDS', 86400 );

$GLOBALS['shortcode_tags'] = array();
function add_action() {}
function add_filter() {}
function add_shortcode( $tag, $cb ) { $GLOBALS['shortcode_tags'][ $tag ] = $cb; }
function remove_shortcode( $tag ) { unset( $GLOBALS['shortcode_tags'][ $tag ] ); }
function shortcode_exists( $tag ) { return isset( $GLOBALS['shortcode_tags'][ $tag ] ); }
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }
function wp_kses( $s, $toegestaan ) { return strip_tags( (string) $s, '<' . implode( '><', array_keys( $toegestaan ) ) . '>' ); }
function esc_url( $u ) { return (string) $u; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function is_admin() { return false; }
function doing_action() { return false; }
function is_singular() { return false; }
function wp_is_json_request() { return false; }
function sfp_page_config_onderdeel_aan( $id ) { return ! empty( $GLOBALS['aan'][ $id ] ); }
function sfp_page_config_blokken_css_bestand( $naam ) { return '/*' . $naam . '*/'; }

require SFP_PAGE_CONFIG_DIR . 'includes/bouwstenen-inhoud.php';

$checks = 0;
function same( $verwacht, $echt, $wat ) {
    global $checks;
    ++$checks;
    if ( $verwacht !== $echt ) {
        throw new RuntimeException( $wat . ': verwacht ' . var_export( $verwacht, true ) . ', kreeg ' . var_export( $echt, true ) );
    }
}

/* ---------- Bronnen uit tooltips ---------- */

$b = sfp_page_config_bronnen_uit_tooltip( "Angst neemt af door herhaling.<br>Bron: Barlow, D. H. (2002). Anxiety and its disorders, p. 45." );
same( 'Barlow, D. H. (2002). Anxiety and its disorders', $b['bron'], 'paginanummer weg uit de bron' );
same( 'Barlow, D. H. (2002). Anxiety and its disorders.', $b['bron_display'], 'weergave eindigt op een punt' );
same( 'Angst neemt af door herhaling.', $b['samenvatting'], 'samenvatting voor de bronregel' );

$b = sfp_page_config_bronnen_uit_tooltip( 'Source: Smith, J. (2020). Talks. Retrieved on 3 March 2024.' );
same( 'Smith, J. (2020). Talks', $b['bron'], 'Engelse bronregel, raadpleegdatum weg' );

$b = sfp_page_config_bronnen_uit_tooltip( 'Fonte: Silva, A. (2019). Medo de falar. https://doi.org/10.1000/xyz' );
same( true, false !== strpos( $b['bron_display'], '<a href="https://doi.org/10.1000/xyz" target="_blank" rel="noopener noreferrer">' ), 'kale link klikbaar, nieuw tabblad' );
same( 'ScholarlyArticle', sfp_page_config_bronnen_type( $b['bron_display'] ), 'doi.org is een artikel' );

same( null, sfp_page_config_bronnen_uit_tooltip( 'Gewone uitleg zonder bron.' ), 'geen bronregel: geen bron' );
same( null, sfp_page_config_bronnen_uit_tooltip( 'Bron: abc' ), 'te korte bron telt niet' );

$b = sfp_page_config_bronnen_uit_tooltip( 'Bron: **Cialdini**, R. (2006). *Influence*.' );
same( 'Cialdini, R. (2006). Influence', $b['bron'], 'sterretjes niet in de sleutel' );
same( '<strong>Cialdini</strong>, R. (2006). <em>Influence</em>.', $b['bron_display'], 'sterretjes worden vet en cursief' );

list( $bronnen, $groepen, $totaal ) = sfp_page_config_bronnen_verzamel(
    array(
        array( 'https://a.test/1', 'Een', '<span data-tooltip="Bron: Cialdini, R. (2006). Influence, p. 12.">x</span><span data-tooltip=\'Bron: Barlow, D. (2002). Anxiety.\'>y</span>' ),
        array( 'https://a.test/2', 'Twee', '<span data-tooltip="Bron: Cialdini, R. (2006). Influence, pp. 40-41.">x</span><span data-tooltip="Bron: 3M (2001). Visuals.">z</span>' ),
    )
);
same( 3, $totaal, 'dezelfde bron met andere paginas telt een keer' );
same( array( 'B', 'C', '#' ), array_keys( $groepen ), 'groepen op letter, cijfers achteraan onder #' );
same( 2, count( $groepen['C'][0]['posts'] ), 'Geciteerd in: beide artikelen' );

/* ---------- Kopieerblok ---------- */

$opgeslagen = '<div data-header-bg="#0b2a19" data-border-color="var(--ast-global-color-2)" class="wp-block-sfp-copy"><span class="sfp-copy__lang-saved">Kopieer deze prompt</span>' . "\n<p>Regel een</p>\n<p>Regel twee</p></div>";
same( "<p>Regel een</p>\n<p>Regel twee</p>", sfp_page_config_kopieer_tekst( $opgeslagen ), 'omhulling en label van het snippet weg' );
same( false, sfp_page_config_kopieer_lang( '<p>Kort.</p><p>Ook kort.</p>' ), 'korte tekst niet ingeklapt' );
same( true, sfp_page_config_kopieer_lang( str_repeat( '<p>Een regel tekst die lang genoeg is om iets te betekenen in de telling hier.</p>', 12 ) ), 'lange tekst ingeklapt' );
$attrs = sfp_page_config_kopieer_attributen();
same( 'data-header-bg', $attrs['headerBg']['attribute'], 'kleur komt uit de opgeslagen HTML' );
same( 'attribute', $attrs['doneColor']['source'], 'alle zes kleuren uit de HTML' );

/* ---------- Auteursbio ---------- */

$html = '<div class="wp-block-post-author-biography"><p>Ik train sprekers.</p><p>√ Twintig jaar ervaring<br>√ Trainer in drie talen</p></div>';
$uit  = sfp_page_config_auteursbio_vinkjes( $html );
same( true, false !== strpos( $uit, '<ul class="sfp-auteur-usp"><li>Twintig jaar ervaring</li><li>Trainer in drie talen</li></ul>' ), 'regels met een vinkje worden een lijst' );
same( true, false !== strpos( $uit, '<p>Ik train sprekers.</p>' ), 'gewone alinea blijft' );
same( $html, sfp_page_config_auteursbio_vinkjes( str_replace( '√', '-', $html ) ) === str_replace( '√', '-', $html ) ? $html : 'x', 'zonder vinkje niets veranderd' );
$gemengd = '<p>√ Een<br>Twee</p>';
same( $gemengd, sfp_page_config_auteursbio_vinkjes( $gemengd ), 'alinea met een regel zonder vinkje blijft' );

/* ---------- Oude codes alleen zonder snippet ---------- */

add_shortcode( 'dynamische_cta', 'snippet_cta' );
sfp_page_config_oude_codes();
same( 'snippet_cta', $GLOBALS['shortcode_tags']['dynamische_cta'], 'code van het snippet blijft' );
same( 'sfp_page_config_code_publicatiedatum', $GLOBALS['shortcode_tags']['publicatiedatum'], 'ontbrekende code komt van de plugin' );
same( 'sfp_page_config_code_tool_auto', $GLOBALS['shortcode_tags']['fl_tool_auto'], 'koppelcode van FL' );
same( false, isset( $GLOBALS['shortcode_tags']['ux_anekdote'] ), 'UX-codes vervallen' );

/* ---------- Bronnenlijst: overnemen alleen met het onderdeel aan ---------- */

add_shortcode( 'bronnenlijst', 'sfp_render_bronnenlijst' );
sfp_page_config_bronnen_registreer();
same( 'sfp_render_bronnenlijst', $GLOBALS['shortcode_tags']['bronnenlijst'], 'onderdeel uit: snippet blijft' );
$GLOBALS['aan']['bronnenlijst'] = true;
$GLOBALS['wp_filter'] = array();
sfp_page_config_bronnen_registreer();
same( 'sfp_page_config_bronnen_code', $GLOBALS['shortcode_tags']['bronnenlijst'], 'onderdeel aan: plugin neemt over' );
remove_shortcode( 'bronnenlijst' );
$GLOBALS['aan']['bronnenlijst'] = false;
sfp_page_config_bronnen_registreer();
same( 'sfp_page_config_bronnen_code', $GLOBALS['shortcode_tags']['bronnenlijst'], 'geen snippet: plugin vangt op' );

/* ---------- Snippet-hooks in een closure van ASE ---------- */

$GLOBALS['verwijderd'] = array();
function remove_action( $hook, $naam, $prio ) { $GLOBALS['verwijderd'][] = $hook . ':' . $naam . '@' . $prio; return true; }
$GLOBALS['wp_filter'] = array(
    'wp_head' => (object) array(
        'callbacks' => array(
            10 => array(
                'dps_bronnenlijst_css'    => array( 'function' => function () {}, 'accepted_args' => 1 ),
                'dps_bibliografie_schema' => array( 'function' => function () {}, 'accepted_args' => 1 ),
                'sfp_gtm_container'       => array( 'function' => function () {}, 'accepted_args' => 1 ),
            ),
            21 => array(
                'sfp_page_config_inhoud_head' => array( 'function' => 'sfp_page_config_inhoud_head', 'accepted_args' => 1 ),
            ),
        ),
    ),
);
sfp_page_config_bronnen_snippet_uit();
same( array( 'wp_head:dps_bronnenlijst_css@10', 'wp_head:dps_bibliografie_schema@10' ), $GLOBALS['verwijderd'], 'ASE-closures van het snippet weg, andere en eigen hooks blijven' );

echo "OK: $checks checks\n";
