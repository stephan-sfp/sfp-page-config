<?php
/**
 * SFP Page Config - Heldfoto scherp op elk scherm
 *
 * De foto in het blok Held staat in een vlak met een vaste vorm (object-fit
 * cover). Op een telefoon is dat vlak vaak hoger dan de foto, zodat de
 * uitsnede op hoogte vult. WordPress geeft de foto `sizes` op schermbreedte
 * mee; de browser kiest dan een bestand dat te smal is voor de uitsnede en
 * de foto oogt zacht (gemeten 19-11-2026: SLB-home 1,35x te weinig pixels).
 *
 * Dit bestand rekent per heldvariant uit hoe breed het bestand moet zijn,
 * uit de vlakmaat in de CSS en de verhouding van de foto, en zet dat in
 * `sizes`. Daarnaast: de telefoonfoto (picture-bron) voor alle varianten en
 * een melding in de paginacontrole als het bestand zelf te klein is.
 *
 * De vlakmaten volgen assets/blokken/held.css, nlvorm-held.css,
 * nlvorm-home.css en held-gesplitst.css. Verandert daar de vorm van het
 * fotovlak, dan hier ook.
 *
 * @package SFP_Page_Config
 * @since   3.79.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Grens tussen telefoon en tablet voor de telefoonfoto.
 */
const SFP_HELD_TELEFOON_MAX = 700;

/**
 * Vlakken per heldvariant, van smal naar breed.
 *
 * Per stuk: van en tot (schermbreedte in px, 0 = geen grens), de breedte
 * van het vlak als vw-deel plus px, en de hoogte: een verhouding van de
 * breedte (r), een vaste hoogte (px) of een deel van de schermhoogte (vh).
 *
 * @param  string $variant recht, breed, wit, foto of gesplitst.
 * @return array<int, array{van:int, tot:int, vw:float, px:float, r?:float, h?:float, vh?:float}>
 */
function sfp_page_config_held_vlakken( $variant ) {
    switch ( $variant ) {
        case 'wit':
            return array(
                array( 'van' => 0, 'tot' => 700, 'vw' => 1.0, 'px' => -48.0, 'r' => 0.75 ),
                array( 'van' => 701, 'tot' => 980, 'vw' => 1.0, 'px' => -48.0, 'r' => 0.5625 ),
                array( 'van' => 981, 'tot' => 0, 'vw' => 0.0, 'px' => 485.0, 'r' => 0.75 ),
            );
        case 'foto':
            return array(
                array( 'van' => 0, 'tot' => 700, 'vw' => 1.0, 'px' => 0.0, 'vh' => 0.85 ),
                array( 'van' => 701, 'tot' => 980, 'vw' => 0.64, 'px' => 0.0, 'h' => 720.0 ),
                array( 'van' => 981, 'tot' => 0, 'vw' => 1.0, 'px' => 0.0, 'vh' => 0.75 ),
            );
        case 'gesplitst':
            return array(
                array( 'van' => 0, 'tot' => 460, 'vw' => 1.0, 'px' => 0.0, 'r' => 1.0 ),
                array( 'van' => 461, 'tot' => 700, 'vw' => 1.0, 'px' => 0.0, 'h' => 460.0 ),
                array( 'van' => 701, 'tot' => 980, 'vw' => 0.42, 'px' => 0.0, 'h' => 650.0 ),
                array( 'van' => 981, 'tot' => 0, 'vw' => 0.42, 'px' => 0.0, 'h' => 720.0 ),
            );
        default: // recht en breed.
            return array(
                array( 'van' => 0, 'tot' => 980, 'vw' => 1.0, 'px' => 0.0, 'r' => 0.75 ),
                array( 'van' => 981, 'tot' => 0, 'vw' => 0.58, 'px' => 0.0, 'vh' => 0.78 ),
            );
    }
}

/**
 * Getal kort en zonder landinstelling (punt als decimaalteken).
 *
 * @param  float $n Getal.
 * @param  int   $d Decimalen.
 * @return string
 */
function sfp_page_config_held_getal( $n, $d = 3 ) {
    $s = number_format( (float) $n, $d, '.', '' );
    return false === strpos( $s, '.' ) ? $s : rtrim( rtrim( $s, '0' ), '.' );
}

/**
 * De breedte van het vlak als CSS-lengte, eventueel maal een factor.
 *
 * @param  array $v Vlak.
 * @param  float $f Factor.
 * @return string
 */
function sfp_page_config_held_breedte( array $v, $f = 1.0 ) {
    $vw = (float) $v['vw'] * 100;
    $px = (float) $v['px'];
    if ( 0.0 === $vw ) {
        return sfp_page_config_held_getal( max( 1, $px * $f ), 0 ) . 'px';
    }
    $basis = sfp_page_config_held_getal( $vw, 2 ) . 'vw';
    if ( 0.0 !== $px ) {
        $basis = '(' . $basis . ' ' . ( $px < 0 ? '- ' : '+ ' ) . sfp_page_config_held_getal( abs( $px ), 0 ) . 'px)';
    }
    if ( abs( $f - 1.0 ) < 0.0005 ) {
        return 0.0 !== $px ? 'calc' . $basis : $basis;
    }
    return 'calc(' . $basis . ' * ' . sfp_page_config_held_getal( $f ) . ')';
}

/**
 * Mediavoorwaarde voor een breedtebereik.
 *
 * @param  int $van Van (0 = geen ondergrens).
 * @param  int $tot Tot (0 = geen bovengrens).
 * @return string[]
 */
function sfp_page_config_held_bereik( $van, $tot ) {
    $delen = array();
    if ( $van > 0 ) {
        $delen[] = '(min-width: ' . (int) $van . 'px)';
    }
    if ( $tot > 0 ) {
        $delen[] = '(max-width: ' . (int) $tot . 'px)';
    }
    return $delen;
}

/**
 * De sizes voor de heldfoto.
 *
 * Per vlak: nodig is de vlakbreedte, of de vlakhoogte maal de verhouding van
 * de foto als de uitsnede op hoogte vult (object-fit cover). Bij een hoogte
 * in vh hangt dat af van de vorm van het scherm; dat vangt een
 * aspect-ratio-voorwaarde.
 *
 * @param  string $variant    Heldvariant.
 * @param  float  $verhouding Breedte gedeeld door hoogte van de foto.
 * @param  bool   $telefoon   Alleen het telefoondeel (voor de picture-bron).
 * @return string Lege string als de verhouding onbekend is.
 */
function sfp_page_config_held_sizes( $variant, $verhouding, $telefoon = false ) {
    $a = (float) $verhouding;
    if ( $a <= 0 ) {
        return '';
    }
    $vlakken = sfp_page_config_held_vlakken( $variant );
    if ( $telefoon ) {
        $vlakken = array_values(
            array_filter(
                $vlakken,
                function ( $v ) {
                    return $v['tot'] > 0 && $v['tot'] <= SFP_HELD_TELEFOON_MAX;
                }
            )
        );
        if ( $vlakken ) {
            $vlakken[ count( $vlakken ) - 1 ]['tot'] = 0;
        }
    }
    $regels = array();
    $aantal = count( $vlakken );
    foreach ( $vlakken as $i => $v ) {
        $laatste = ( $i === $aantal - 1 );
        // Het laatste vlak is de terugval zonder voorwaarde.
        $bereik = $laatste ? array() : sfp_page_config_held_bereik( $v['van'], $v['tot'] );
        $vanaf  = sfp_page_config_held_bereik( $v['van'], 0 );
        if ( isset( $v['r'] ) ) {
            // Vaste vorm: de factor hangt niet van het scherm af.
            $regels[] = array( $bereik, sfp_page_config_held_breedte( $v, max( 1.0, $a * (float) $v['r'] ) ) );
        } elseif ( isset( $v['h'] ) ) {
            // Vaste hoogte: tot de grens vult de uitsnede op hoogte.
            $nodig = (float) $v['h'] * $a;
            $hoogte = sfp_page_config_held_getal( $nodig, 0 ) . 'px';
            if ( 0.0 === (float) $v['vw'] ) {
                $regels[] = array( $bereik, $nodig > (float) $v['px'] ? $hoogte : sfp_page_config_held_breedte( $v ) );
                continue;
            }
            $grens = (int) floor( ( $nodig - (float) $v['px'] ) / (float) $v['vw'] );
            if ( ! $laatste && $grens >= (int) $v['tot'] ) {
                $regels[] = array( $bereik, $hoogte );
                continue;
            }
            if ( $grens >= (int) $v['van'] ) {
                $regels[] = array( array_merge( $vanaf, array( '(max-width: ' . $grens . 'px)' ) ), $hoogte );
            }
            $regels[] = array( $bereik, sfp_page_config_held_breedte( $v ) );
        } else {
            // Hoogte in vh: op een smal, hoog scherm vult de uitsnede op hoogte.
            $vh       = (float) $v['vh'];
            $grens    = (int) round( $vh * $a / (float) $v['vw'] * 1000 );
            $regels[] = array( array_merge( $laatste ? $vanaf : $bereik, array( '(max-aspect-ratio: ' . $grens . '/1000)' ) ), 'calc(' . sfp_page_config_held_getal( $vh * 100, 2 ) . 'vh * ' . sfp_page_config_held_getal( $a ) . ')' );
            $regels[] = array( $bereik, sfp_page_config_held_breedte( $v ) );
        }
    }
    $uit = array();
    foreach ( $regels as $r ) {
        $uit[] = ( $r[0] ? implode( ' and ', $r[0] ) . ' ' : '' ) . $r[1];
    }
    return implode( ', ', $uit );
}

/**
 * Verhouding (breedte/hoogte) van een bijlage.
 *
 * @param  int $id Bijlage.
 * @return float 0 als onbekend.
 */
function sfp_page_config_held_verhouding( $id ) {
    $meta = $id > 0 ? wp_get_attachment_metadata( $id ) : false;
    if ( is_array( $meta ) && ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
        return (float) $meta['width'] / (float) $meta['height'];
    }
    return 0.0;
}

/**
 * Grootste beschikbare breedte van een bijlage (origineel of tussenmaat).
 *
 * @param  int $id Bijlage.
 * @return int
 */
function sfp_page_config_held_max_breedte( $id ) {
    $meta = $id > 0 ? wp_get_attachment_metadata( $id ) : false;
    if ( ! is_array( $meta ) ) {
        return 0;
    }
    $max = (int) ( $meta['width'] ?? 0 );
    foreach ( (array) ( $meta['sizes'] ?? array() ) as $maat ) {
        $max = max( $max, (int) ( $maat['width'] ?? 0 ) );
    }
    return $max;
}

/**
 * Bijlage-ID van de eerste afbeelding in een stuk HTML.
 *
 * @param  string $html HTML.
 * @return int
 */
function sfp_page_config_held_beeld_id( $html ) {
    if ( preg_match( '/<img\b[^>]*\bclass="[^"]*\bwp-image-(\d+)\b/i', (string) $html, $m ) ) {
        return (int) $m[1];
    }
    return 0;
}

/**
 * Zet de berekende sizes op de eerste afbeelding.
 *
 * Een bestaande sizes (ook "auto, ...") wordt vervangen. Bij het renderen
 * van het blok heeft de afbeelding nog geen srcset: die zet WordPress later
 * in the_content (wp_filter_content_tags), en een sizes die er al staat laat
 * WordPress dan staan.
 *
 * @param  string $html    HTML met de foto.
 * @param  string $variant Heldvariant.
 * @param  int    $id      Bijlage van de foto (0 = de eerste afbeelding).
 * @return string
 */
function sfp_page_config_held_beeld_sizes( $html, $variant, $id = 0 ) {
    $id = $id > 0 ? (int) $id : sfp_page_config_held_beeld_id( $html );
    if ( ! $id ) {
        return (string) $html;
    }
    $sizes = sfp_page_config_held_sizes( $variant, sfp_page_config_held_verhouding( $id ) );
    if ( '' === $sizes ) {
        return (string) $html;
    }
    return (string) preg_replace_callback(
        '/<img\b[^>]*\bwp-image-' . $id . '\b[^>]*>/i',
        function ( $m ) use ( $sizes ) {
            $img = $m[0];
            if ( preg_match( '/\ssizes="[^"]*"/i', $img ) ) {
                return preg_replace( '/\ssizes="[^"]*"/i', ' sizes="' . esc_attr( $sizes ) . '"', $img, 1 );
            }
            return preg_replace( '/<img\b/i', '<img sizes="' . esc_attr( $sizes ) . '"', $img, 1 );
        },
        (string) $html,
        1
    );
}

/**
 * Telefoonfoto als picture-bron om de eerste afbeelding.
 *
 * @param  string $html     HTML met de foto.
 * @param  int    $mobiel   Bijlage van de telefoonfoto (0 = geen).
 * @param  string $variant  Heldvariant.
 * @return string
 */
function sfp_page_config_held_telefoonbron( $html, $mobiel, $variant ) {
    $mobiel = (int) $mobiel;
    if ( $mobiel <= 0 || ! wp_attachment_is_image( $mobiel ) || false !== stripos( (string) $html, '<picture' ) ) {
        return (string) $html;
    }
    $src = wp_get_attachment_image_srcset( $mobiel, 'full' );
    if ( ! $src ) {
        $src = (string) wp_get_attachment_image_url( $mobiel, 'full' );
    }
    if ( ! $src ) {
        return (string) $html;
    }
    $sizes = sfp_page_config_held_sizes( $variant, sfp_page_config_held_verhouding( $mobiel ), true );
    $bron  = '<source media="(max-width: ' . SFP_HELD_TELEFOON_MAX . 'px)" srcset="' . esc_attr( $src ) . '"' . ( '' !== $sizes ? ' sizes="' . esc_attr( $sizes ) . '"' : ' sizes="100vw"' ) . '>';
    return (string) preg_replace( '/(<img\b[^>]*>)/i', '<picture>' . $bron . '$1</picture>', (string) $html, 1 );
}

/**
 * Hoeveel pixels breed het bestand moet zijn op een gewone telefoon
 * (390 x 844, 3x scherm), voor de melding in de paginacontrole.
 *
 * @param  string $variant    Heldvariant.
 * @param  float  $verhouding Verhouding van de foto.
 * @return int
 */
function sfp_page_config_held_nodig_telefoon( $variant, $verhouding ) {
    $a = (float) $verhouding;
    if ( $a <= 0 ) {
        return 0;
    }
    $vw = 390.0;
    $vh = 844.0;
    foreach ( sfp_page_config_held_vlakken( $variant ) as $v ) {
        if ( $vw < $v['van'] || ( $v['tot'] > 0 && $vw > $v['tot'] ) ) {
            continue;
        }
        $w = $v['vw'] * $vw + $v['px'];
        if ( isset( $v['r'] ) ) {
            $h = $w * $v['r'];
        } elseif ( isset( $v['h'] ) ) {
            $h = $v['h'];
        } else {
            $h = $v['vh'] * $vh;
        }
        return (int) ceil( max( $w, $h * $a ) * 3 );
    }
    return 0;
}

/**
 * Melding voor de paginacontrole: heldfoto te klein voor een telefoon.
 *
 * Kijkt naar het eerste blok Held met een foto. Met een telefoonfoto telt
 * die; anders de gewone foto. Het bestand moet minstens 90% halen van wat
 * een telefoon met 3x scherm nodig heeft.
 *
 * @param  WP_Post $post Pagina.
 * @return array<int, array{code: string, kort: string, tekst: string, actie: string}>
 */
function sfp_page_config_held_beeld_controle( $post ) {
    $content = (string) $post->post_content;
    if ( false === strpos( $content, '<!-- wp:sfp/held' ) ) {
        return array();
    }
    foreach ( parse_blocks( $content ) as $blok ) {
        if ( 'sfp/held' !== $blok['blockName'] ) {
            continue;
        }
        $attrs   = (array) $blok['attrs'];
        $variant = isset( $attrs['variant'] ) ? (string) $attrs['variant'] : 'recht';
        $id      = 0;
        foreach ( (array) $blok['innerBlocks'] as $kind ) {
            if ( 'core/image' === $kind['blockName'] && ! empty( $kind['attrs']['id'] ) ) {
                $id = (int) $kind['attrs']['id'];
                break;
            }
        }
        $mobiel = isset( $attrs['mobielId'] ) ? (int) $attrs['mobielId'] : 0;
        $beeld  = $mobiel > 0 && wp_attachment_is_image( $mobiel ) ? $mobiel : $id;
        if ( ! $beeld ) {
            return array();
        }
        $nodig = sfp_page_config_held_nodig_telefoon( $variant, sfp_page_config_held_verhouding( $beeld ) );
        $heeft = sfp_page_config_held_max_breedte( $beeld );
        if ( $nodig > 0 && $heeft > 0 && $heeft < 0.9 * $nodig ) {
            return array(
                array(
                    'code'  => 'heldbeeld:' . $beeld,
                    'kort'  => 'Heldfoto te klein voor telefoon',
                    'tekst' => sprintf(
                        'De %1$s in de held is %2$d px breed. Een telefoon met een scherp scherm heeft voor deze uitsnede %3$d px nodig, dus de foto oogt zacht. Upload een groter bestand%4$s.',
                        $mobiel === $beeld ? 'telefoonfoto' : 'foto',
                        $heeft,
                        $nodig,
                        $mobiel === $beeld ? '' : ' of kies in het blok Held een staande foto voor de telefoon'
                    ),
                    'actie' => 'editor',
                ),
            );
        }
        return array();
    }
    return array();
}
