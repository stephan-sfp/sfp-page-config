<?php
/**
 * Tests voor 3.66.0: de ingebedde calculator als kaart met een
 * voorbeeldbegroting. Draait in een lokale WordPress, nooit op een live site.
 *
 * Draaien: wp eval-file wp-content/plugins/sfp-page-config/tests/calculatorkaart-wp.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}
$fouten = 0;
$aantal = 0;
$check  = function ( $naam, $ok ) use ( &$fouten, &$aantal ) {
    ++$aantal;
    if ( ! $ok ) {
        ++$fouten;
        echo "FOUT: $naam\n";
    }
};

$norm = sfp_calculator_norm();
$html = sfp_calculator_kaart_html( $norm, 'https://voorbeeld.test/incompany/calculator/', 'nl' );
$check( 'kaart is een link naar de calculatorpagina', false !== strpos( $html, '<a class="sfp-calc-vb" id="sfp-calc-naar" href="https://voorbeeld.test/incompany/calculator/"' ) );
$check( 'voorbeeld met de groepsnorm', false !== strpos( $html, 'Voorbeeld voor ' . (int) $norm['groep_norm'] . ' deelnemers' ) );
$check( 'bedrag uit de rekenregels', false !== strpos( $html, sfp_calculator_bedrag( (int) $norm['vormen']['2']['prijs'] ) ) );
$check( 'training van 2 dagen', false !== strpos( $html, 'Training van 2 dagen' ) );
$check( 'totaal excl. btw', false !== strpos( $html, 'Totaal excl. btw' ) );
$check( 'blad verborgen voor schermlezers, knoptekst is de naam', false !== strpos( $html, 'aria-hidden="true"' ) && false !== strpos( $html, '<span class="sfp-calc-knop">Bereken je training</span>' ) );
$check( 'geen pagina, geen kaart', '' === sfp_calculator_kaart_html( $norm, '', 'nl' ) );
$zonder = $norm;
unset( $zonder['vormen']['2'] );
$check( 'zonder 2 dagen de eerste vorm', false !== strpos( sfp_calculator_kaart_html( $zonder, 'https://voorbeeld.test/c/', 'nl' ), 'Training van 1 dag' ) );
$check( 'Engels', false !== strpos( sfp_calculator_kaart_html( $norm, 'https://voorbeeld.test/c/', 'en' ), 'Example for 8 participants' ) );

$type = WP_Block_Type_Registry::get_instance()->get_registered( 'sfp/calculator' );
$check( 'blok: kaart standaard aan', $type && true === $type->attributes['kaart']['default'] );
preg_match_all( '/#[0-9a-f]{3,6}\b/i', (string) file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/calculator/kaart.css' ), $hex );
$check( 'kaart.css zonder merkkleur (alleen de terugval wit)', array() === array_diff( array_map( 'strtolower', $hex[0] ), array( '#fff' ) ) );
$check( 'geen botsing met de keuzekaarten van de calculator', false === strpos( (string) file_get_contents( SFP_PAGE_CONFIG_DIR . 'assets/calculator/kaart.css' ), 'sfp-calc-kaart' ) );

echo ( $fouten ? "$fouten van $aantal controles mislukt\n" : "OK: $aantal controles\n" );
