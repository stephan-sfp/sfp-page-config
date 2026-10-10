<?php
/**
 * SFP Page Config - Deelbeeld tekenen
 *
 * Tekent de deelbare beelden van de artikelopmaak met GD en FreeType:
 * het inzicht (1200 x 627, LinkedIn-formaat) en de samenvatting
 * (1080 x 1080). Ontwerp goedgekeurd door Stephan op 10 oktober 2026.
 *
 * Zuivere functies: ze krijgen kleuren, fontbestanden en tekst en geven
 * een GD-beeld terug. Er staat hier geen merkwaarde; kleuren en fonts
 * levert includes/deelbeeld.php uit de instellingen van de site. Het beeld
 * wordt op dubbele grootte getekend en daarna verkleind, zodat randen en
 * letters glad zijn.
 *
 * @package SFP_Page_Config
 * @since   2.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokale bestanden; een kapot beeld mag het opslaan niet breken.
/** Hex naar [r,g,b]. */
function sfp_page_config_db_rgb( $hex ) {
    $hex = ltrim( (string) $hex, '#' );
    if ( 3 === strlen( $hex ) ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
}

/** Kleur met dekking 0..1. */
function sfp_page_config_db_kleur( $im, $hex, $dekking = 1.0 ) {
    list( $r, $g, $b ) = sfp_page_config_db_rgb( $hex );
    return imagecolorallocatealpha( $im, $r, $g, $b, (int) round( 127 * ( 1 - $dekking ) ) );
}

/** Breedte van een tekst, met letterafstand en woordafstand in px. */
function sfp_page_config_db_breedte( $tekst, $font, $maat, $letter = 0.0, $woord = 0.0 ) {
    if ( '' === $tekst ) {
        return 0;
    }
    if ( 0.0 === (float) $letter && 0.0 === (float) $woord ) {
        $b = imagettfbbox( $maat, 0, $font, $tekst );
        return $b[2] - $b[0];
    }
    $w = 0;
    foreach ( mb_str_split( $tekst ) as $c ) {
        $b  = imagettfbbox( $maat, 0, $font, $c );
        $w += ( $b[2] - $b[0] ) + $letter + ( ' ' === $c ? $woord : 0 );
    }
    return $w - $letter;
}

/** Schrijf tekst op de basislijn (x links, y basislijn). */
function sfp_page_config_db_schrijf( $im, $tekst, $x, $y, $font, $maat, $kleur, $letter = 0.0, $woord = 0.0 ) {
    if ( 0.0 === (float) $letter && 0.0 === (float) $woord ) {
        imagettftext( $im, $maat, 0, (int) round( $x ), (int) round( $y ), $kleur, $font, $tekst );
        return;
    }
    foreach ( mb_str_split( $tekst ) as $c ) {
        imagettftext( $im, $maat, 0, (int) round( $x ), (int) round( $y ), $kleur, $font, $c );
        $b  = imagettfbbox( $maat, 0, $font, $c );
        $x += ( $b[2] - $b[0] ) + $letter + ( ' ' === $c ? $woord : 0 );
    }
}

/**
 * Breek woorden in regels binnen een breedte. Een woord tussen *sterretjes*
 * krijgt de accentkleur. Geeft regels terug als lijst van [woord, accent].
 */
function sfp_page_config_db_regels( $tekst, $font, $maat, $max, $letter = 0.0, $woord = 0.0 ) {
    $woorden = array();
    $accent  = false;
    foreach ( preg_split( '/\s+/u', trim( $tekst ) ) as $w ) {
        $start = false;
        if ( 0 === strpos( $w, '*' ) ) {
            $accent = true;
            $w      = substr( $w, 1 );
        }
        $eind = false;
        if ( '' !== $w && false !== strpos( $w, '*' ) ) {
            $eind = true;
            $w    = str_replace( '*', '', $w );
        }
        if ( '' !== $w ) {
            $woorden[] = array( $w, $accent );
        }
        if ( $eind ) {
            $accent = false;
        }
    }
    $spatie = sfp_page_config_db_breedte( ' ', $font, $maat, $letter, $woord ) + $letter;
    $regels = array();
    $regel  = array();
    $breed  = 0;
    foreach ( $woorden as $w ) {
        $wb = sfp_page_config_db_breedte( $w[0], $font, $maat, $letter, $woord );
        if ( $regel && $breed + $spatie + $wb > $max ) {
            $regels[] = $regel;
            $regel    = array();
            $breed    = 0;
        }
        $breed  += ( $regel ? $spatie : 0 ) + $wb;
        $regel[] = array( $w[0], $w[1], $wb );
    }
    if ( $regel ) {
        $regels[] = $regel;
    }
    return array( $regels, $spatie );
}

/** Schrijf gebroken regels; geeft de y na de laatste regel terug. */
function sfp_page_config_db_schrijf_regels( $im, $regels, $spatie, $x, $y, $font, $maat, $regelhoogte, $kleur, $accentkleur, $letter = 0.0, $woord = 0.0 ) {
    foreach ( $regels as $regel ) {
        $cx = $x;
        foreach ( $regel as $w ) {
            sfp_page_config_db_schrijf( $im, $w[0], $cx, $y, $font, $maat, $w[1] ? $accentkleur : $kleur, $letter, $woord );
            $cx += $w[2] + $spatie;
        }
        $y += $regelhoogte;
    }
    return $y;
}

/** Schuine band in de secundaire kleur, zoals het ::after-vlak in de dummy. */
function sfp_page_config_db_band( $im, $B, $H, $kleur, $rechts, $boven, $breedte, $hoogte ) {
    $w  = $B * $breedte;
    $h  = $H * $hoogte;
    $cx = $B - $B * $rechts - $w / 2;
    $cy = $H * $boven + $h / 2;
    $a  = deg2rad( 18 );
    $p  = array();
    foreach ( array( array( -1, -1 ), array( 1, -1 ), array( 1, 1 ), array( -1, 1 ) ) as $hoek ) {
        $dx  = $hoek[0] * $w / 2;
        $dy  = $hoek[1] * $h / 2;
        $p[] = (int) round( $cx + $dx * cos( $a ) - $dy * sin( $a ) );
        $p[] = (int) round( $cy + $dx * sin( $a ) + $dy * cos( $a ) );
    }
    imagefilledpolygon( $im, $p, $kleur );
}

/** Ronde foto op (x,y) met doorsnede d. */
function sfp_page_config_db_portret( $im, $bestand, $x, $y, $d ) {
    $data = @file_get_contents( $bestand );
    $bron = $data ? @imagecreatefromstring( $data ) : false;
    if ( ! $bron ) {
        return false;
    }
    $sw  = imagesx( $bron );
    $sh  = imagesy( $bron );
    $kant = min( $sw, $sh );
    $rond = imagecreatetruecolor( $d, $d );
    imagealphablending( $rond, false );
    imagesavealpha( $rond, true );
    imagefill( $rond, 0, 0, imagecolorallocatealpha( $rond, 0, 0, 0, 127 ) );
    $vier = imagecreatetruecolor( $d, $d );
    imagecopyresampled( $vier, $bron, 0, 0, (int) ( ( $sw - $kant ) / 2 ), (int) ( ( $sh - $kant ) / 2 ), $d, $d, $kant, $kant );
    $r = $d / 2;
    for ( $py = 0; $py < $d; $py++ ) {
        for ( $px = 0; $px < $d; $px++ ) {
            $af = sqrt( ( $px + .5 - $r ) ** 2 + ( $py + .5 - $r ) ** 2 );
            if ( $af <= $r ) {
                imagesetpixel( $rond, $px, $py, imagecolorat( $vier, $px, $py ) );
            }
        }
    }
    imagealphablending( $im, true );
    imagecopy( $im, $rond, (int) $x, (int) $y, 0, 0, $d, $d );
    return true;
}

/**
 * Teken een deelbeeld.
 *
 * @param array $s soort (inzicht|samenvatting), kleuren (primair, secundair, wit),
 *                 fonts (kop, tekst, vet), label, tekst of titel + punten, naam, domein, portret.
 * @return GdImage
 */
function sfp_page_config_deelbeeld_teken( array $s ) {
    $inzicht = 'samenvatting' !== $s['soort'];
    $doelB   = $inzicht ? 1200 : 1080;
    $doelH   = $inzicht ? 627 : 1080;
    $f       = 2; // dubbel tekenen, daarna verkleinen.
    $B       = $doelB * $f;
    $H       = $doelH * $f;
    $im      = imagecreatetruecolor( $B, $H );
    imagealphablending( $im, true );
    imagefill( $im, 0, 0, sfp_page_config_db_kleur( $im, $s['primair'] ) );

    $wit   = sfp_page_config_db_kleur( $im, $s['wit'] );
    $sec   = sfp_page_config_db_kleur( $im, $s['secundair'] );
    $kop   = $s['fonts']['kop'];
    $tekst = $s['fonts']['tekst'];
    $vet   = $s['fonts']['vet'];
    // px naar punten: GD rekent in punten op 96 dpi.
    $pt = static function ( $px ) use ( $f ) {
        return $px * $f * 0.75;
    };

    if ( $inzicht ) {
        sfp_page_config_db_band( $im, $B, $H, sfp_page_config_db_kleur( $im, $s['secundair'], .16 ), -0.12, -0.30, 0.55, 1.60 );
        $rand = 64 * $f;
        // Label.
        sfp_page_config_db_schrijf( $im, mb_strtoupper( $s['label'] ), $rand, $rand + 21 * $f, $kop, $pt( 21 ), $sec, 21 * $f * .1 );
        // Voet.
        $d  = 58 * $f;
        $vy = $H - $rand - $d;
        $heeft = ! empty( $s['portret'] ) && sfp_page_config_db_portret( $im, $s['portret'], $rand, $vy, $d );
        $vx = $rand + ( $heeft ? $d + 18 * $f : 0 );
        sfp_page_config_db_schrijf( $im, $s['naam'], $vx, $vy + 24 * $f, $vet, $pt( 23 ), $wit );
        sfp_page_config_db_schrijf( $im, $s['domein'], $vx, $vy + 52 * $f, $tekst, $pt( 23 ), $wit );
        // Tekst: zo groot als past, tussen label en voet.
        $ruimte_b = $B * 0.60;
        $boven    = $rand + 21 * $f + 28 * $f;
        $onder    = $vy - 28 * $f;
        foreach ( array( 62, 56, 50, 45, 40, 36, 32 ) as $maat ) {
            list( $regels, $spatie ) = sfp_page_config_db_regels( mb_strtoupper( $s['tekst'] ), $kop, $pt( $maat ), $ruimte_b );
            $rh = $maat * 1.2 * $f;
            if ( count( $regels ) * $rh <= $onder - $boven ) {
                break;
            }
        }
        $blok = count( $regels ) * $rh;
        $y    = $boven + ( ( $onder - $boven ) - $blok ) / 2 + $maat * $f * 0.86;
        sfp_page_config_db_schrijf_regels( $im, $regels, $spatie, $rand, $y, $kop, $pt( $maat ), $rh, $wit, $sec );
    } else {
        sfp_page_config_db_band( $im, $B, $H, sfp_page_config_db_kleur( $im, $s['secundair'], .14 ), -0.30, -0.20, 0.70, 1.40 );
        $rand = 70 * $f;
        $y    = $rand + 30 * $f;
        sfp_page_config_db_schrijf( $im, mb_strtoupper( $s['label'] ), $rand, $y, $kop, $pt( 30 ), $sec, 30 * $f * .1 );
        $y += 30 * $f;
        list( $regels, $spatie ) = sfp_page_config_db_regels( mb_strtoupper( $s['titel'] ), $kop, $pt( 58 ), $B - 2 * $rand );
        $y = sfp_page_config_db_schrijf_regels( $im, $regels, $spatie, $rand, $y + 54 * $f, $kop, $pt( 58 ), 58 * 1.15 * $f, $wit, $sec );
        // Voet.
        $d  = 88 * $f;
        $vy = $H - $rand - $d;
        $heeft = ! empty( $s['portret'] ) && sfp_page_config_db_portret( $im, $s['portret'], $rand, $vy, $d );
        $vx = $rand + ( $heeft ? $d + 24 * $f : 0 );
        sfp_page_config_db_schrijf( $im, $s['naam'], $vx, $vy + 36 * $f, $vet, $pt( 32 ), $wit );
        sfp_page_config_db_schrijf( $im, $s['domein'], $vx, $vy + 76 * $f, $tekst, $pt( 32 ), $wit );
        // Punten: zo groot als past.
        $boven = $y - 10 * $f;
        $onder = $vy - 40 * $f;
        $inspr = 76 * $f;
        foreach ( array( 40, 37, 34, 31, 28 ) as $maat ) {
            $rh     = $maat * 1.4 * $f;
            $tussen = $maat * 0.75 * $f;
            $hoogte = 0;
            $sets   = array();
            foreach ( $s['punten'] as $punt ) {
                $set     = sfp_page_config_db_regels( $punt, $tekst, $pt( $maat ), $B - 2 * $rand - $inspr );
                $sets[]  = $set;
                $hoogte += count( $set[0] ) * $rh + $tussen;
            }
            if ( $hoogte - $tussen <= $onder - $boven ) {
                break;
            }
        }
        $y  = $boven + $maat * $f;
        $nr = 1;
        foreach ( $sets as $set ) {
            // Het rondje staat op het optische midden van de eerste regel: halverwege
            // de basislijn en de bovenkant van de kleine letters, iets opgehoogd
            // voor de hoofdletter. Het cijfer staat op zijn eigen omtrek gecentreerd.
            $xb = imagettfbbox( $pt( $maat ), 0, $tekst, 'x' );
            $hb = imagettfbbox( $pt( $maat ), 0, $tekst, 'H' );
            $xh = abs( $xb[7] - $xb[1] );
            $ch = abs( $hb[7] - $hb[1] );
            $cd = (int) round( $maat * 1.3 * $f );
            $cx = (int) round( $rand + $cd / 2 );
            // Midden van de hele zin: bij meer regels zakt het rondje een halve regel per extra regel.
            $cy = (int) round( $y - ( $xh + $ch ) / 4 + ( count( $set[0] ) - 1 ) * $rh / 2 );
            imagefilledellipse( $im, $cx, $cy, $cd, $cd, $sec );
            $nm = $pt( $maat * 0.72 );
            $nb = imagettfbbox( $nm, 0, $kop, (string) $nr );
            $nx = $cx - ( $nb[0] + $nb[2] ) / 2;
            $ny = $cy - ( $nb[1] + $nb[7] ) / 2;
            imagettftext( $im, $nm, 0, (int) round( $nx ), (int) round( $ny ), $wit, $kop, (string) $nr );
            $y = sfp_page_config_db_schrijf_regels( $im, $set[0], $set[1], $rand + $inspr, $y, $tekst, $pt( $maat ), $rh, $wit, $wit ) + $tussen;
            ++$nr;
        }
    }

    $uit = imagecreatetruecolor( $doelB, $doelH );
    imagecopyresampled( $uit, $im, 0, 0, 0, 0, $doelB, $doelH, $B, $H );
    imagedestroy( $im );
    return $uit;
}
// phpcs:enable
