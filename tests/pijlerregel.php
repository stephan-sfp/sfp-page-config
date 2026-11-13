<?php
/**
 * Pijlerregel boven de H1 (3.26.0): tikdoel van 24 px zonder ander uiterlijk,
 * en op de pijler de regel Guide in Blog (NL Gids in Blog). Het beeld zelf controleer je in een
 * browser (375, 768 en 1280 px).
 *
 * Draaien: php tests/pijlerregel.php
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}
$basis  = dirname( __DIR__ ) . '/';
$css    = (string) file_get_contents( $basis . 'assets/artikel.css' );
$php    = (string) file_get_contents( $basis . 'includes/artikel.php' );
$tekst  = (string) file_get_contents( $basis . 'includes/artikel-teksten.php' );
$fouten = 0;
$checks = 0;
function pr_is( $v, $wat ) {
    global $fouten, $checks;
    $checks++;
    if ( ! $v ) {
        $fouten++;
        echo "FOUT: $wat\n";
    }
}
preg_match( '/:root \.sfp-art-pijler a\{([^}]*)\}/', $css, $m );
$a = $m[1] ?? '';
pr_is( false !== strpos( $a, 'padding:6px 0' ), 'link 12 px plus 12 px opvulling: minstens 24 px tikdoel (3.55.0)' );
pr_is( false !== strpos( $a, 'font-weight:900' ), 'link op 900, zoals de regel (3.55.0)' );
pr_is( false === strpos( $a, 'display:inline-block' ), 'link blijft inline: de regel verschuift niet' );
pr_is( false === strpos( $a, 'border-bottom' ), 'geen rand meer die met de opvulling meezakt' );
pr_is( false !== strpos( $a, 'color:var(--sfp-b-tekstlink)' ) && false !== strpos( $a, 'text-decoration:underline' ), 'gewone tekstlink: kleur tekstlink, onderstreept (3.46.0)' );
pr_is( false !== strpos( $css, ':root .sfp-art-pijler a:is(:hover,:focus){color:var(--sfp-b-tekstlink);text-decoration-thickness:3px;text-decoration-color:var(--sfp-b-links)}' ), 'aanwijzen: onderstreping 3 px in de actiekleur' );
preg_match( '/:root \\.sfp-art-pijler\{([^}]*)\}/', $css, $r );
$r = $r[1] ?? '';
foreach ( array( 'font-family:var(--sfp-b-kop)', 'font-weight:900', 'font-size:12px', 'text-transform:uppercase', 'letter-spacing:.08em', 'color:var(--sfp-b-label)' ) as $eis ) {
    pr_is( false !== strpos( $r, $eis ), "regel als bloklabel: $eis (besluit Stephan 13-11-2026)" );
}
pr_is( ! preg_match( '/#[0-9a-fA-F]{3,8}\b|Roboto|Nunito|Archivo|Rubik/', $r ), 'geen kleur- of fontnaam in de regel' );
pr_is( ! preg_match( '/#[0-9a-fA-F]{3,8}\b/', $a ), 'geen hexkleur' );
foreach ( array( "'pijler_regel'           => 'Guide'", "'pijler_regel_in'        => 'Guide in %s'", "'pijler_regel'           => 'Gids'", "'pijler_regel_in'        => 'Gids in %s'", "'pijler_regel'           => 'Guia'", "'pijler_regel_in'        => 'Guia em %s'" ) as $t ) {
    pr_is( false !== strpos( $tekst, $t ), "tekst $t" );
}
pr_is( false !== strpos( $php, "get_option( 'page_for_posts' )" ) && false !== strpos( $php, "sfp_page_config_artikel_tekst( 'pijler_regel_in' )" ), 'pijler: link naar de blogpagina' );
pr_is( false !== strpos( $php, "sfp_page_config_artikel_tekst( 'pijler_regel' )" ), 'pijler zonder blogpagina: alleen het woord' );
pr_is( false === strpos( $php, "sfp_page_config_artikel_tekst( 'gids_in' )" ), 'regel zegt niet meer Guide' );
echo $fouten ? "$fouten van $checks controles faalden\n" : "OK: $checks checks\n";
exit( $fouten ? 1 : 0 );
