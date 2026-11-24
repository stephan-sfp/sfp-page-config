<?php
/**
 * Test: heldfoto scherp op elk scherm (3.79.0).
 *
 * Sizes uit de uitsnede per heldvariant, de telefoonfoto als picture-bron
 * en de melding voor een te klein bestand. Zonder WordPress: de paar
 * functies die nodig zijn, staan hieronder als stub.
 *
 * Draai: php tests/held-beeld.php
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['hb_meta'] = array(
    927  => array( 'width' => 2560, 'height' => 1443, 'sizes' => array( 'large' => array( 'width' => 1024 ), 'x2' => array( 'width' => 2048 ) ) ),
    2316 => array( 'width' => 428, 'height' => 926, 'sizes' => array() ),
    500  => array( 'width' => 1200, 'height' => 1600, 'sizes' => array( 'large' => array( 'width' => 768 ) ) ),
);
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function wp_get_attachment_metadata( $id ) { return $GLOBALS['hb_meta'][ $id ] ?? false; }
function wp_attachment_is_image( $id ) { return isset( $GLOBALS['hb_meta'][ $id ] ); }
function wp_get_attachment_image_srcset( $id ) { return isset( $GLOBALS['hb_meta'][ $id ] ) ? 'm-' . $id . '.jpg 1200w' : false; }
function wp_get_attachment_image_url( $id ) { return 'm-' . $id . '.jpg'; }
function parse_blocks( $c ) { return $GLOBALS['hb_blokken']; }

require dirname( __DIR__ ) . '/includes/held-beeld.php';

$goed = 0;
$fout = 0;
function hb_check( $ok, $naam ) {
    global $goed, $fout;
    if ( $ok ) { $goed++; } else { $fout++; echo "FOUT: $naam\n"; }
}

$a = 2560 / 1443;

// Sizes per variant.
$s = sfp_page_config_held_sizes( 'gesplitst', $a );
hb_check( 0 === strpos( $s, '(max-width: 460px) calc(100vw * 1.774)' ), 'gesplitst: telefoon vierkant, factor = verhouding' );
hb_check( false !== strpos( $s, '(min-width: 461px) and (max-width: 700px) 816px' ), 'gesplitst: 461-700 vaste hoogte 460' );
hb_check( '42vw' === substr( $s, -4 ), 'gesplitst: terugval 42vw' );
$s = sfp_page_config_held_sizes( 'recht', $a );
hb_check( 0 === strpos( $s, '(max-width: 980px) calc(100vw * 1.331)' ), 'recht: telefoon 4:3' );
$s = sfp_page_config_held_sizes( 'breed', $a );
hb_check( 0 === strpos( $s, '(max-width: 980px) calc(100vw * 1.331)' ), 'breed: gelijk aan recht' );
$s = sfp_page_config_held_sizes( 'wit', $a );
hb_check( 0 === strpos( $s, '(max-width: 700px) calc((100vw - 48px) * 1.331)' ), 'wit: telefoon met marges' );
hb_check( false !== strpos( $s, '(min-width: 701px) and (max-width: 980px) calc(100vw - 48px)' ), 'wit: tablet 16:9 geen factor' );
$s = sfp_page_config_held_sizes( 'foto', $a );
hb_check( 0 === strpos( $s, '(max-width: 700px) and (max-aspect-ratio: 1508/1000) calc(85vh * 1.774)' ), 'foto: telefoon op hoogte' );
hb_check( '100vw' === substr( $s, -5 ), 'foto: terugval 100vw' );
// Staande foto in een liggend vlak: nooit kleiner dan de vlakbreedte.
$s = sfp_page_config_held_sizes( 'recht', 0.75 );
hb_check( 0 === strpos( $s, '(max-width: 980px) 100vw' ), 'staand beeld: geen factor' );
hb_check( false === strpos( sfp_page_config_held_sizes( 'gesplitst', 0.462 ), ' 3px' ), 'getal: 300 blijft 300' );
hb_check( '' === sfp_page_config_held_sizes( 'recht', 0 ), 'onbekende verhouding: geen sizes' );
// Telefoondeel.
$t = sfp_page_config_held_sizes( 'foto', 428 / 926, true );
hb_check( false === strpos( $t, '981' ) && '100vw' === substr( $t, -5 ), 'telefoondeel zonder tablet en computer' );

// Sizes op de afbeelding: alleen de foto van de held, ook zonder srcset.
$html = '<p><img src="i.png" class="wp-image-12"></p><figure class="wp-block-image"><img src="x.jpg" class="wp-image-927" sizes="auto, (max-width: 2560px) 100vw, 2560px"></figure>';
$uit  = sfp_page_config_held_beeld_sizes( $html, 'gesplitst', 927 );
hb_check( false !== strpos( $uit, 'class="wp-image-927" sizes="(max-width: 460px)' ), 'bestaande sizes vervangen' );
hb_check( false !== strpos( $uit, '<img src="i.png" class="wp-image-12">' ), 'andere afbeelding ongemoeid' );
$uit = sfp_page_config_held_beeld_sizes( '<img src="x.jpg" class="wp-image-927">', 'recht' );
hb_check( false !== strpos( $uit, '<img sizes="(max-width: 980px)' ), 'sizes erbij zonder srcset (WordPress vult srcset later)' );

// Telefoonfoto als picture-bron.
$uit = sfp_page_config_held_telefoonbron( '<figure><img src="x.jpg" class="wp-image-927"></figure>', 500, 'recht' );
hb_check( false !== strpos( $uit, '<picture><source media="(max-width: 700px)" srcset="m-500.jpg 1200w" sizes="' ), 'telefoonbron bij recht' );
hb_check( $uit === sfp_page_config_held_telefoonbron( $uit, 500, 'recht' ), 'geen tweede picture' );
hb_check( '<img>' === sfp_page_config_held_telefoonbron( '<img>', 0, 'wit' ), 'zonder telefoonfoto gelijk' );

// Benodigde breedte op 390 x 844 bij 3x.
hb_check( 2076 === sfp_page_config_held_nodig_telefoon( 'gesplitst', $a ), 'nodig gesplitst SLB-home 2076' );
hb_check( 1170 === sfp_page_config_held_nodig_telefoon( 'foto', 428 / 926 ), 'nodig foto met staande telefoonfoto 1170' );

// Melding in de paginacontrole.
$post = (object) array( 'post_content' => '<!-- wp:sfp/held -->' );
$GLOBALS['hb_blokken'] = array( array( 'blockName' => 'sfp/held', 'attrs' => array( 'variant' => 'foto', 'mobielId' => 2316 ), 'innerBlocks' => array( array( 'blockName' => 'core/image', 'attrs' => array( 'id' => 927 ) ) ) ) );
$m = sfp_page_config_held_beeld_controle( $post );
hb_check( 1 === count( $m ) && 'heldbeeld:2316' === $m[0]['code'] && false !== strpos( $m[0]['tekst'], 'telefoonfoto in de held is 428 px' ), 'melding: telefoonfoto te klein' );
$GLOBALS['hb_blokken'][0]['attrs'] = array( 'variant' => 'gesplitst' );
$m = sfp_page_config_held_beeld_controle( $post );
hb_check( array() === $m, 'geen melding: 2560 px is genoeg voor 2076' );
$GLOBALS['hb_blokken'][0]['attrs'] = array( 'variant' => 'foto' );
$m = sfp_page_config_held_beeld_controle( $post );
hb_check( 1 === count( $m ) && false !== strpos( $m[0]['tekst'], 'staande foto voor de telefoon' ), 'melding: liggende foto in de foto-held' );
hb_check( array() === sfp_page_config_held_beeld_controle( (object) array( 'post_content' => '<p>x</p>' ) ), 'geen held: geen melding' );

echo "$goed goed, $fout fout\n";
exit( $fout ? 1 : 0 );
