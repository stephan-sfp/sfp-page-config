<?php
/**
 * Boekkaart in sfp/kolommen, indeling boeking (3.24.0): vaste waarden uit de
 * DPS-kaart, alleen merkrollen, de vaste klasse en de blokstijl Label.
 * Het beeld zelf controleer je in een browser (desktop 1280, telefoon 375).
 *
 * Draaien: php tests/boekkaart.php
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
$basis  = dirname( __DIR__ ) . '/';
$css    = (string) file_get_contents( $basis . 'assets/blokken/kolommen.css' );
$bcss   = (string) file_get_contents( $basis . 'assets/blokken/basis.css' );
$php    = (string) file_get_contents( $basis . 'includes/blokken.php' );
$js     = (string) file_get_contents( $basis . 'assets/blokken/editor.js' );
$fouten = 0;
$checks = 0;
function boek_is( $v, $wat ) {
    global $fouten, $checks;
    $checks++;
    if ( ! $v ) {
        $fouten++;
        echo "FOUT: $wat\n";
    }
}

// Alleen de regels van de boekkaart.
preg_match_all( '/[^{}]*sfp-kolommen--boeking[^{}]*\{[^}]*\}/', $css, $m );
$boek = implode( "\n", $m[0] );

boek_is( false !== strpos( $boek, 'grid-template-columns:minmax(0,45fr) minmax(0,55fr)' ), 'kolommen 45/55' );
boek_is( false !== strpos( $css, '@media (min-width:768px){' . "\n" . '  :root .sfp-kolommen--boeking{grid-template-columns:minmax(0,45fr) minmax(0,55fr)}' ), 'twee kolommen vanaf 768 px' );
boek_is( false !== strpos( $boek, 'max-width:1140px' ), 'maximaal 1140 px' );
boek_is( false !== strpos( $boek, 'border-radius:var(--sfp-b-r-kaart)' ), 'radius uit de netwerknorm (6 px)' );
boek_is( false !== strpos( $boek, 'overflow:hidden' ), 'inhoud afgesneden op de radius' );
boek_is( false !== strpos( $boek, 'border:1px solid color-mix(in srgb,var(--sfp-b-primair) 8%,transparent)' ), 'rand primair 8%' );
boek_is( false !== strpos( $boek, 'box-shadow:0 14px 40px color-mix(in srgb,var(--sfp-b-primair) 7%,transparent)' ), 'schaduw primair 7%' );
boek_is( false !== strpos( $boek, 'background:var(--sfp-b-tint2)' ) && false !== strpos( $boek, 'padding:40px 40px 32px' ) && false !== strpos( $boek, 'gap:16px' ), 'tekstkolom tint 2, 40/40/32, 16 px' );
boek_is( false !== strpos( $boek, 'padding:32px 28px' ), 'tekstkolom 32/28 onder 1024 px' );
boek_is( false !== strpos( $boek, 'font-size:26px' ) && false !== strpos( $boek, 'text-transform:uppercase' ), 'kop 26 px in hoofdletters' );
boek_is( false !== strpos( $boek, 'content:"\2713"' ) && false !== strpos( $boek, 'var(--sfp-rol-pijl,var(--sfp-b-primair))' ), 'vinkjes in de pijlrol' );
boek_is( false === strpos( $boek, 'border-radius:50%;background' ), 'vinkjes zonder cirkel' );
boek_is( false !== strpos( $boek, 'width:44px;height:44px;border-radius:50%' ), 'portret 44 px rond' );
boek_is( false !== strpos( $boek, 'margin-top:auto' ), 'naam en portret onderaan' );
boek_is( false !== strpos( $boek, '.is-style-sfp-wie br{display:none}' ), 'rol als tweede regel na een regeleinde' );
boek_is( false !== strpos( $boek, 'font-size:28px !important' ), 'kalenderkop 28 px' );
boek_is( false !== strpos( $boek, 'box-shadow:none !important' ), 'formulier zonder eigen schaduw' );
boek_is( false !== strpos( $boek, 'justify-content:center' ), 'kalender verticaal gecentreerd' );
boek_is( false !== strpos( $css, '@media (max-width:767px){' ) && false !== strpos( $boek, 'padding:24px 0' ) && false !== strpos( $boek, 'padding:0 16px' ), 'mobiel: sectie 24/16' );
boek_is( false !== strpos( $boek, 'border-top:3px solid var(--sfp-rol-pijl,var(--sfp-b-primair))' ), 'mobiel: bovenrand 3 px in de pijlrol' );
boek_is( false !== strpos( $boek, 'p:not([class*="is-style-"]){display:none}' ), 'mobiel: intro verborgen' );
boek_is( false !== strpos( $boek, 'font-size:18px !important' ), 'mobiel: kalenderkop 18 px' );
boek_is( ! preg_match( '/#[0-9a-fA-F]{3,8}\b/', $css ), 'geen hexkleur in kolommen.css' );
boek_is( ! preg_match( '/font-family:\s*["\']?[A-Z]/', $css ), 'geen fontnaam in kolommen.css' );
boek_is( false === strpos( $boek, 'border-radius:16px' ), 'geen 16 px radius' );

boek_is( (bool) preg_match( "/'boeking' === \\\$indeling \? 'sfp-boekkaart' : ''/", $php ), 'render: vaste klasse sfp-boekkaart bij boeking' );
boek_is( false !== strpos( $js, "( 'boeking' === a.indeling ? ' sfp-boekkaart' : '' )" ), 'editor: dezelfde klasse' );
boek_is( false !== strpos( $php, "'sfp-label'        => 'Label (boven een kop)'" ), 'blokstijl Label op de alinea' );
boek_is( (bool) preg_match( '/\.is-style-sfp-label\{[^}]*font-size:12px[^}]*letter-spacing:\.12em[^}]*text-transform:uppercase[^}]*color:var\(--sfp-b-label\)/', $bcss ), 'label 12 px, .12em, hoofdletters, rol label (3.46.0)' );

if ( $fouten ) {
    echo "$fouten van $checks controles mislukt\n";
    exit( 1 );
}
echo "OK: $checks checks\n";
