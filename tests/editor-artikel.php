<?php
/**
 * Test 3.57.0: het canvas van een artikel zoals de site.
 *
 * - leeskolom 680 px en zijkolom in de editor-CSS;
 * - Komt vanzelf niet meer 760 px breed en links;
 * - FAQ-antwoorden niet vet in de editor;
 * - Rustig ritme zonder balk boven het canvas, wel winkel, paneel en markering;
 * - de zijkolom komt mee in de stand van de editor;
 * - de verhaalvorm in de editor volgt dezelfde regel als de site.
 *
 * Draaien: php tests/editor-artikel.php
 */

$basis = dirname( __DIR__ );
$fouten = 0;
$aantal = 0;
function controle( $ok, $tekst ) {
    global $fouten, $aantal;
    ++$aantal;
    if ( ! $ok ) {
        ++$fouten;
        echo "FOUT: $tekst\n";
    }
}

$css   = file_get_contents( $basis . '/assets/blokken/editor.css' );
$js    = file_get_contents( $basis . '/assets/blokken/editor.js' );
$dp    = file_get_contents( $basis . '/assets/editor/deze-pagina.js' );
$dpcss = file_get_contents( $basis . '/assets/editor/deze-pagina.css' );
$php   = file_get_contents( $basis . '/includes/editor.php' );
$blok  = file_get_contents( $basis . '/includes/blokken.php' );

// Leeskolom en zijkolom.
controle( false !== strpos( $css, 'max-width:680px;margin-left:var(--sfp-ed-links)' ), 'leeskolom 680 px met linkerkant uit --sfp-ed-links' );
controle( false !== strpos( $css, 'calc((100% - 1180px) / 2 + 24px)' ), 'met zijkolom: kolom op de plek van de site (raster 1180, 24 px binnenrand)' );
controle( false !== strpos( $css, 'left:calc(var(--sfp-ed-links) + 752px);width:300px' ), 'zijkolom 300 px op 72 px rechts van de kolom' );
controle( 1 === preg_match( '/\.sfp-vanzelf\{[^}]*max-width:760px/', $css ), 'de oude regel voor Komt vanzelf blijft voor andere schermen' );
controle( false !== strpos( $css, ':is(#sfp-vanzelf-kop,#sfp-vanzelf-voet){box-sizing:border-box;width:auto;max-width:680px' ), 'Komt vanzelf in het artikel even breed als de kolom' );
controle( false !== strpos( $css, '#sfp-vanzelf-kop{margin-top:16px;margin-bottom:24px}' ), 'ruimte onder de kopkaart zoals de site (24 px)' );

// FAQ.
controle( false === strpos( $css, '.sfp-editor-vraag .rich-text' ), 'niet meer alle tekst in een FAQ-vraag vet' );
controle( false !== strpos( $css, '.sfp-editor-vraag>.sfp-editor-vraagtekst{font-weight:700}' ), 'alleen de vraag zelf vet' );

// Rustig ritme.
controle( false === strpos( $js, "createWarningNotice(\n\t\t\t\t'Rustig ritme" ) && false === strpos( $js, "MELDING = 'sfp-ux-ritme'" ), 'geen balk Rustig ritme meer boven het canvas' );
controle( false !== strpos( $js, "data.createReduxStore( 'sfp/ritme'" ), 'winkel sfp/ritme' );
controle( false !== strpos( $js, "klassen.push( 'sfp-ed-ritme' )" ), 'markering in het canvas' );
controle( false !== strpos( $css, '.sfp-ed-ritme{' ), 'opmaak van de markering' );
controle( false !== strpos( $dp, 'function Ritme()' ) && false !== strpos( $dp, "el( Ritme, { key: 'ritme' } )" ), 'regel Rustig ritme in het paneel Deze pagina' );
controle( false !== strpos( $dpcss, '.sfp-dp-ritme__lijst' ), 'opmaak van de lijst in het paneel' );

// Stand van de editor.
controle( false !== strpos( $php, "'zij'         => ''," ) && false !== strpos( $php, "\$stand['raster'] = true;" ), 'stand kent zij en raster' );
controle( false !== strpos( $php, 'return array( $kop, $voet, $zij );' ), 'omlijsting geeft de zijkolom terug' );
controle( false !== strpos( $php, "sfp_page_config_artikel_zijkolom( \$post, \$hoofdstukken )" ), 'zijkolom komt uit dezelfde functie als de site' );
controle( false !== strpos( $dp, "doc.body.classList.toggle( 'sfp-ed-raster'" ), 'canvas krijgt de klasse van de leeskolom' );
controle( false !== strpos( $blok, "'verhaalMeer' => sfp_page_config_tekst( 'verhaal_meer' )" ), 'tekst van de uitklaplink uit teksten.php' );

// Verhaalvorm: de JavaScript-regel naast de PHP-regel.
$start = strpos( $js, '		function klasseHeeft(' );
$eind  = strpos( $js, '		// De rol van één blok' );
controle( false !== $start && false !== $eind, 'verhaalregel gevonden in editor.js' );
$code = substr( $js, $start, $eind - $start );
$gevallen = <<<'JS'
var id = 0;
function b( name, cls, kids ) { return { clientId: 'b' + ( ++id ), name: name, attributes: cls ? { className: cls } : {}, innerBlocks: kids || [] }; }
function p( cls ) { return b( 'core/paragraph', cls ); }
var A = 'is-style-sfp-afgerond';
var uit = {};
uit.basis = verhaal( b( 'sfp/kolommen', '', [ b( 'sfp/kolom', '', [ p( 'is-style-sfp-label' ), b( 'core/heading' ), p(), p(), p() ] ), b( 'sfp/kolom', '', [ b( 'core/image', A ) ] ) ] ) );
uit.direct = verhaal( b( 'sfp/kolommen', '', [ b( 'sfp/kolom', '', [ p(), p(), p() ] ), b( 'core/image', A ) ] ) );
uit.twee = verhaal( b( 'sfp/kolommen', '', [ b( 'sfp/kolom', '', [ p( 'is-style-sfp-label' ), p(), p() ] ), b( 'core/image', A ) ] ) );
uit.nietAfgerond = verhaal( b( 'sfp/kolommen', '', [ b( 'sfp/kolom', '', [ p(), p(), p() ] ), b( 'core/image', '' ) ] ) );
uit.tweeTeksten = verhaal( b( 'sfp/kolommen', '', [ b( 'sfp/kolom', '', [ p(), p(), p() ] ), b( 'sfp/kolom', '', [ p() ] ), b( 'core/image', A ) ] ) );
uit.anderBlok = verhaal( b( 'sfp/kolommen', '', [ b( 'sfp/kolom', '', [ p(), p(), p() ] ), b( 'core/image', A ), b( 'core/heading' ) ] ) );
var r = {};
r.basis = uit.basis ? [ uit.basis.boven.length, uit.basis.meer.length ] : null;
r.direct = uit.direct ? [ uit.direct.boven.length, uit.direct.meer.length ] : null;
r.twee = uit.twee; r.nietAfgerond = uit.nietAfgerond; r.tweeTeksten = uit.tweeTeksten; r.anderBlok = uit.anderBlok;
console.log( JSON.stringify( r ) );
JS;
$bestand = tempnam( sys_get_temp_dir(), 'verhaal' ) . '.js';
file_put_contents( $bestand, $code . "\n" . $gevallen );
$uitvoer = shell_exec( 'node ' . escapeshellarg( $bestand ) . ' 2>&1' );
unlink( $bestand );
$r = json_decode( trim( (string) $uitvoer ), true );
controle( is_array( $r ), 'verhaalregel draait: ' . trim( (string) $uitvoer ) );
if ( is_array( $r ) ) {
    controle( array( 3, 2 ) === $r['basis'], 'label, kop, lead naast het beeld; twee alinea\'s eronder' );
    controle( array( 1, 2 ) === $r['direct'], 'beeld direct in Kolommen telt ook (zoals de site)' );
    controle( null === $r['twee'], 'twee alinea\'s plus label: geen verhaalvorm (label telt niet)' );
    controle( null === $r['nietAfgerond'], 'zonder afgerond beeld: geen verhaalvorm' );
    controle( null === $r['tweeTeksten'], 'twee tekstkolommen: geen verhaalvorm' );
    controle( null === $r['anderBlok'], 'een ander blok direct in Kolommen: geen verhaalvorm' );
}

// Dezelfde regel in PHP: drempel 3 alinea's en het label telt niet.
controle( false !== strpos( $blok, "if ( \$alineas < 3 ) {" ) && false !== strpos( $js, 'if ( alineas.length < 3 ) { return null; }' ), 'drempel van drie alinea\'s gelijk in PHP en JS' );

echo $fouten ? "$fouten van $aantal controles fout\n" : "OK: $aantal checks\n";
exit( $fouten ? 1 : 0 );
