<?php
/**
 * Run with: php tests/editor-gelijk.php
 *
 * Editor gelijk aan de site (3.16.0): controles op de bronbestanden, zonder
 * WordPress. De doorloop in een echte editor (alle voorbeeldtools, typen en
 * opslaan, uitklap en tabs) gebeurt in een lokale testinstallatie.
 */
error_reporting( E_ALL );
$map    = dirname( __DIR__ ) . '/';
$fouten = 0;
$checks = 0;
function check( $ok, $wat ) {
    global $fouten, $checks;
    ++$checks;
    if ( ! $ok ) {
        ++$fouten;
        echo "FOUT: $wat\n";
    }
}
$lees = function ( $pad ) use ( $map ) {
    return (string) file_get_contents( $map . $pad );
};

/* De editor laadt de opmaak van de begripsblokken. */
$blokken = $lees( 'includes/blokken.php' );
check( (bool) preg_match( "/function sfp_page_config_blokken_editor_css\(\).*?array\([^)]*'definitie'[^)]*\)/s", $blokken ), 'editor-CSS bevat definitie' );

/* Uitklap: elke summary-regel geldt ook voor de editorkop, zonder hogere specificiteit. */
$uitklap = $lees( 'assets/blokken/uitklap.css' );
$zonder  = preg_replace( '#/\*.*?\*/#s', '', $uitklap );
$kaal    = preg_replace( '/summary::-webkit-details-marker/', '', $zonder );
check( ! preg_match( '/(?<!:is\()\bsummary\b(?!,:where)/', $kaal ), 'geen kale summary meer in uitklap.css' );
check( ! preg_match( '/(?<!:is\()\[open\](?!,:where)/', $zonder ), 'geen kale [open] meer in uitklap.css' );
check( substr_count( $zonder, ':is(summary,:where(.sfp-uitklap__kop))' ) >= 15, 'summary-regels voor de editorkop' );
check( substr_count( $zonder, ':is([open],:where(.is-open))' ) >= 4, 'open-regels voor de editorkop' );
// Terug naar de oude selectors: dan staat er niets van de editor meer in.
$terug = str_replace( array( ':is(summary,:where(.sfp-uitklap__kop))', ':is([open],:where(.is-open))' ), array( 'summary', '[open]' ), $zonder );
check( false === strpos( $terug, 'sfp-uitklap__kop' ) && false === strpos( $terug, 'is-open' ), 'editorklassen alleen binnen :where()' );

/* Tabs: onderlijn over de volle breedte, ruimte links en rechts in de knop. */
$tabs = $lees( 'assets/blokken/tabs.css' );
check( (bool) preg_match( '/\.sfp-tablijst\{[^}]*width:100%/', $tabs ), 'tablijst over de volle breedte' );
check( false === strpos( $tabs, 'width:max-content' ), 'geen max-content meer op de tablijst' );
check( (bool) preg_match( '/\[role=tab\]\{[^}]*padding:18px 12px 14px/', $tabs ), 'tabknop 12 px links en rechts' );
check( (bool) preg_match( '/@media \(max-width:600px\)\{\.sfp-tablijst\{gap:0\}/', $tabs ), 'telefoon zonder ruimte tussen de tabs' );
check( ! preg_match( '/#[0-9a-f]{3,6}\b/i', $tabs ), 'geen vaste kleur in tabs.css' );

/* Editor: tabs en uitklap zoals op de site. */
$editor = $lees( 'assets/blokken/editor.js' );
check( false !== strpos( $editor, "className: 'sfp-tablijst', role: 'tablist'" ), 'editor tekent de tablijst' );
check( false !== strpos( $editor, 'sfp-editor-tabplus' ), 'plus in de tabbalk' );
check( false !== strpos( $editor, "'sfp-uitklap__regel' + ( open ? ' is-open' : '' )" ), 'uitklapregel open bij selecteren' );
check( false === strpos( $editor, "el( 'span', { className: 'sfp-editor-label' }, 'Tabblad' )" ), 'label Tabblad weg' );
check( false !== strpos( $editor, 'sfp-editor-vanzelfband' ), 'makersregel als Komt vanzelf' );
$css = $lees( 'assets/blokken/editor.css' );
check( false === strpos( $css, '.sfp-tabpaneel[hidden]{display:grid}' ), 'verborgen tabpaneel blijft verborgen in de editor' );

/* Stand voor de editor: makersregel en promokaart bij een begrip. */
$ep = $lees( 'includes/editor.php' );
check( false !== strpos( $ep, "'maker'     => sfp_page_config_editor_begrip_maker( \$post )" ), 'stand geeft de makersregel' );
check( false !== strpos( $ep, "'promo'     =>" ), 'stand geeft de promokaart' );

/* Tool in het canvas. */
$tc = $lees( 'assets/editor/tool-canvas.js' );
check( '' !== $tc, 'tool-canvas.js bestaat' );
check( ! preg_match( '/\.innerText\b(?!:)/', preg_replace( '#/\*.*?\*/#s', '', $tc ) ), 'tool-canvas leest geen innerText (hoofdletters van de opmaak)' );
check( false !== strpos( $tc, "editPost( { meta: { sfp_tool: tekstNu } } )" ), 'schrijft alleen het metaveld sfp_tool' );
check( false !== strpos( $tc, "'string' !== typeof x[ laatste ]" ), 'vervangt alleen een bestaande tekst' );
check( false !== strpos( $tc, 'Vangnet' ), 'vangnet per scherm' );
$tw = $lees( 'includes/editor.php' );
check( (bool) preg_match( "/function sfp_page_config_tool_canvas_editor\(\).*?'tool' !== \\\$scherm->post_type/s", $tw ), 'canvas alleen op een toolpagina' );
check( (bool) preg_match( "/function sfp_page_config_tool_canvas_css\(\).*?is_admin\(\)/s", $tw ), 'canvasopmaak alleen in het beheer' );
$dp = $lees( 'assets/editor/deze-pagina.js' );
check( false === strpos( $dp, 'claude/tools-motor-ontwerp' ), 'geen verwijzing naar een projectbestand in het paneel' );
check( false !== strpos( $dp, "'Geavanceerd'" ), 'beschrijving onder Geavanceerd' );

/* Versie op twee plekken gelijk. */
$hoofd = $lees( 'sfp-page-config.php' );
preg_match( '/\* Version:\s+(\S+)/', $hoofd, $a );
preg_match( "/define\( 'SFP_PAGE_CONFIG_VERSION', '([^']+)' \)/", $hoofd, $b );
check( isset( $a[1], $b[1] ) && $a[1] === $b[1], 'versie in header en define gelijk' );

/* Geen em dash in de gewijzigde bestanden. */
foreach ( array( 'assets/editor/tool-canvas.js', 'assets/editor/tool-canvas.css', 'assets/blokken/editor.js', 'assets/blokken/editor.css', 'assets/blokken/tabs.css', 'assets/blokken/uitklap.css', 'includes/editor.php', 'CHANGELOG.md' ) as $f ) {
    check( false === strpos( $lees( $f ), "\u{2014}" ), "geen em dash in $f" );
}

echo $fouten ? "MISLUKT: $fouten van $checks\n" : "OK: $checks checks\n";
exit( $fouten ? 1 : 0 );
