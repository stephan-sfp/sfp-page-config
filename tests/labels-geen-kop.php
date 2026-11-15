<?php
/**
 * Labels in plugin-onderdelen zijn geen kop (sinds 3.70.0, besluit Stephan 15-11-2026).
 * Tool-uitklap, keuzehulpstappen en tabbladen als kaart: H3-/H4-opmaak, geen kop.
 *
 * Gebruik: php tests/labels-geen-kop.php
 */
$root = dirname( __DIR__ );
$lees = function ( $pad ) use ( $root ) { return file_get_contents( $root . '/' . $pad ); };
$tool = $lees( 'includes/tool-beschrijving.php' );
$kh   = $lees( 'includes/keuzehulp.php' );
$js   = $lees( 'assets/blokken/editor.js' );
$tcss = $lees( 'assets/tool/tool.css' );
$kcss = $lees( 'assets/blokken/keuzehulp.css' );
$ocss = $lees( 'assets/blokken/nlvorm-over.css' );
$tek  = $lees( 'includes/artikel-teksten.php' );
$ok = 0; $fout = 0;
function lg_check( $v, $wat ) { global $ok, $fout; if ( $v ) { ++$ok; } else { ++$fout; echo "FOUT: $wat\n"; } }
$hoe = substr( $tool, strpos( $tool, 'function sfp_page_config_tool_hoe_rekent' ), 2500 );
lg_check( false === strpos( $hoe, '<h4' ), 'tool-uitklap zonder h4' );
lg_check( 2 === substr_count( $hoe, '<p class="sfp-tool__hoe-kop">' ), 'tool-uitklap: twee labels' );
lg_check( false !== strpos( $tcss, ':root .sfp-tool__hoe .sfp-tool__hoe-kop{' ) && false !== strpos( $tcss, 'clamp(16px,10px + 1vw,20px)' ), 'tool-label met H4-maat' );
lg_check( false === strpos( $tcss, '.sfp-tool__hoe h4' ), 'geen oude h4-regel' );
lg_check( false === strpos( $kh, '<h3 class="sfp-keuzehulp__vraag"' ) && 2 === substr_count( $kh, '<p class="sfp-keuzehulp__vraag" tabindex="-1">' ), 'keuzehulpvraag is een alinea met focus' );
lg_check( false !== strpos( $kcss, 'font-family:var(--sfp-b-kop);font-weight:900;font-size:clamp(18px,1.5vw + 10px,22px);line-height:1.42;text-transform:uppercase' ), 'keuzehulpvraag in H3-opmaak' );
lg_check( false === strpos( $js, "el( 'h3', { className: 'sfp-keuzehulp__vraag' }" ), 'editor keuzehulp zonder h3' );
lg_check( false !== strpos( $js, "kaartVorm ? [ [ 'core/paragraph', { className: 'is-style-sfp-kaderkop' } ]" ), 'tab als kaart begint met Kop in kader' );
lg_check( false !== strpos( $js, 'kaderZonderKoppen( props.clientId, ! kaartVorm );' ), 'tab als kaart zet koppen om' );
lg_check( false !== strpos( $ocss, ':root .sfp-tabs--kaart .sfp-tabpaneel p.is-style-sfp-kaderkop{' ), 'opmaak Kop in kader in tabblad' );
lg_check( false !== strpos( $tek, "'Zo berekenen we je uitslag'" ) && false !== strpos( $tek, "'How we calculate your result'" ) && false !== strpos( $tek, "'Como calculamos o seu resultado'" ), 'uitklaptekst in drie talen' );
echo "labels-geen-kop: $ok goed, $fout fout\n";
exit( $fout ? 1 : 0 );
