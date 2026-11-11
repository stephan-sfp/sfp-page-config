<?php
/**
 * SFP Page Config - QR-code als SVG
 *
 * Een kleine QR-encoder zonder externe bron (geen externe image-URL's):
 * bytemodus, foutcorrectie M, versie 1 tot en met 9 (tot 180 tekens). Genoeg
 * voor een link naar een boekpagina. De maskerkeuze volgt de strafregels 1, 2
 * en 4 van de norm.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'sfp_qr_svg' ) ) {

    /**
     * Een QR-code als SVG.
     *
     * @param  string $tekst  De inhoud, meestal een URL.
     * @param  string $kleur  Kleur van de modules (CSS-waarde).
     * @param  string $titel  Toegankelijke titel.
     * @return string SVG, of '' als de tekst te lang is.
     */
    function sfp_qr_svg( $tekst, $kleur = 'currentColor', $titel = '' ) {
        $m = sfp_qr_matrix( (string) $tekst );
        if ( ! $m ) {
            return '';
        }
        $n   = count( $m );
        $pad = '';
        for ( $y = 0; $y < $n; $y++ ) {
            for ( $x = 0; $x < $n; $x++ ) {
                if ( $m[ $y ][ $x ] ) {
                    $pad .= 'M' . ( $x + 4 ) . ' ' . ( $y + 4 ) . 'h1v1h-1z';
                }
            }
        }
        $z = $n + 8;
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $z . ' ' . $z . '" shape-rendering="crispEdges" role="img"'
            . ( '' !== $titel ? ' aria-label="' . esc_attr( $titel ) . '"' : ' aria-hidden="true"' ) . '>'
            . '<rect width="' . $z . '" height="' . $z . '" fill="#fff"/><path fill="' . esc_attr( $kleur ) . '" d="' . $pad . '"/></svg>';
    }

    /**
     * De modules van een QR-code.
     *
     * @param  string $tekst Inhoud.
     * @return array<int, array<int, bool>>|null
     */
    function sfp_qr_matrix( $tekst ) {
        // Versie => array( ec-codewoorden per blok, datalengtes per blok ), niveau M.
        $tabel = array(
            1 => array( 10, array( 16 ) ),
            2 => array( 16, array( 28 ) ),
            3 => array( 26, array( 44 ) ),
            4 => array( 18, array( 32, 32 ) ),
            5 => array( 24, array( 43, 43 ) ),
            6 => array( 16, array( 27, 27, 27, 27 ) ),
            7 => array( 18, array( 31, 31, 31, 31 ) ),
            8 => array( 22, array( 38, 38, 39, 39 ) ),
            9 => array( 22, array( 36, 36, 36, 37, 37 ) ),
        );
        $uitlijn = array(
            1 => array(),
            2 => array( 6, 18 ),
            3 => array( 6, 22 ),
            4 => array( 6, 26 ),
            5 => array( 6, 30 ),
            6 => array( 6, 34 ),
            7 => array( 6, 22, 38 ),
            8 => array( 6, 24, 42 ),
            9 => array( 6, 26, 46 ),
        );
        $bytes  = array_values( unpack( 'C*', $tekst ) ?: array() );
        $versie = 0;
        foreach ( $tabel as $v => $rij ) {
            if ( 4 + 8 + 8 * count( $bytes ) <= 8 * array_sum( $rij[1] ) ) {
                $versie = $v;
                break;
            }
        }
        if ( ! $versie ) {
            return null;
        }
        list( $ec_len, $blokken ) = $tabel[ $versie ];
        $capaciteit               = array_sum( $blokken );

        // Bits: modus 0100, lengte (8 bits), data, afsluiter, opvulling.
        $bits = '0100' . str_pad( decbin( count( $bytes ) ), 8, '0', STR_PAD_LEFT );
        foreach ( $bytes as $b ) {
            $bits .= str_pad( decbin( $b ), 8, '0', STR_PAD_LEFT );
        }
        $bits .= str_repeat( '0', min( 4, $capaciteit * 8 - strlen( $bits ) ) );
        $bits .= str_repeat( '0', ( 8 - strlen( $bits ) % 8 ) % 8 );
        $data  = array();
        foreach ( str_split( $bits, 8 ) as $byte ) {
            $data[] = bindec( $byte );
        }
        for ( $i = 0; count( $data ) < $capaciteit; $i++ ) {
            $data[] = $i % 2 ? 0x11 : 0xEC;
        }

        // Blokken met foutcorrectie, dan vervlechten.
        $deler = sfp_qr_rs_deler( $ec_len );
        $dblok = array();
        $eblok = array();
        $pos   = 0;
        foreach ( $blokken as $lengte ) {
            $stuk     = array_slice( $data, $pos, $lengte );
            $pos     += $lengte;
            $dblok[]  = $stuk;
            $eblok[]  = sfp_qr_rs_rest( $stuk, $deler );
        }
        $woorden = array();
        for ( $i = 0; $i < max( $blokken ); $i++ ) {
            foreach ( $dblok as $stuk ) {
                if ( $i < count( $stuk ) ) {
                    $woorden[] = $stuk[ $i ];
                }
            }
        }
        for ( $i = 0; $i < $ec_len; $i++ ) {
            foreach ( $eblok as $stuk ) {
                $woorden[] = $stuk[ $i ];
            }
        }

        $n    = 17 + 4 * $versie;
        $mod  = array_fill( 0, $n, array_fill( 0, $n, false ) );
        $func = array_fill( 0, $n, array_fill( 0, $n, false ) );
        $zet  = function ( $x, $y, $aan ) use ( &$mod, &$func ) {
            $mod[ $y ][ $x ]  = (bool) $aan;
            $func[ $y ][ $x ] = true;
        };

        // Tijdlijnen.
        for ( $i = 0; $i < $n; $i++ ) {
            $zet( 6, $i, 0 === $i % 2 );
            $zet( $i, 6, 0 === $i % 2 );
        }
        // Zoekpatronen met scheiding.
        foreach ( array( array( 3, 3 ), array( $n - 4, 3 ), array( 3, $n - 4 ) ) as $p ) {
            for ( $dy = -4; $dy <= 4; $dy++ ) {
                for ( $dx = -4; $dx <= 4; $dx++ ) {
                    $x = $p[0] + $dx;
                    $y = $p[1] + $dy;
                    if ( $x >= 0 && $x < $n && $y >= 0 && $y < $n ) {
                        $d = max( abs( $dx ), abs( $dy ) );
                        $zet( $x, $y, 2 !== $d && 4 !== $d );
                    }
                }
            }
        }
        // Uitlijnpatronen.
        $al = $uitlijn[ $versie ];
        $k  = count( $al );
        for ( $i = 0; $i < $k; $i++ ) {
            for ( $j = 0; $j < $k; $j++ ) {
                if ( ( 0 === $i && 0 === $j ) || ( 0 === $i && $k - 1 === $j ) || ( $k - 1 === $i && 0 === $j ) ) {
                    continue;
                }
                for ( $dy = -2; $dy <= 2; $dy++ ) {
                    for ( $dx = -2; $dx <= 2; $dx++ ) {
                        $zet( $al[ $i ] + $dx, $al[ $j ] + $dy, 1 !== max( abs( $dx ), abs( $dy ) ) );
                    }
                }
            }
        }
        // Plek voor de formaatbits reserveren (met masker 0), en de versiebits.
        sfp_qr_formaat( $zet, $n, 0 );
        if ( $versie >= 7 ) {
            $rest = $versie;
            for ( $i = 0; $i < 12; $i++ ) {
                $rest = ( $rest << 1 ) ^ ( ( $rest >> 11 ) * 0x1F25 );
            }
            $vbits = $versie << 12 | $rest;
            for ( $i = 0; $i < 18; $i++ ) {
                $bit = ( $vbits >> $i ) & 1;
                $a   = $n - 11 + $i % 3;
                $b   = intdiv( $i, 3 );
                $zet( $a, $b, $bit );
                $zet( $b, $a, $bit );
            }
        }

        // Codewoorden plaatsen.
        $i      = 0;
        $totaal = count( $woorden ) * 8;
        for ( $rechts = $n - 1; $rechts >= 1; $rechts -= 2 ) {
            if ( 6 === $rechts ) {
                $rechts = 5;
            }
            for ( $v = 0; $v < $n; $v++ ) {
                for ( $j = 0; $j < 2; $j++ ) {
                    $x   = $rechts - $j;
                    $op  = 0 === ( ( $rechts + 1 ) & 2 );
                    $y   = $op ? $n - 1 - $v : $v;
                    if ( ! $func[ $y ][ $x ] && $i < $totaal ) {
                        $mod[ $y ][ $x ] = (bool) ( ( $woorden[ $i >> 3 ] >> ( 7 - ( $i & 7 ) ) ) & 1 );
                        $i++;
                    }
                }
            }
        }

        // Beste masker.
        $beste = null;
        $straf = PHP_INT_MAX;
        for ( $masker = 0; $masker < 8; $masker++ ) {
            $proef = $mod;
            for ( $y = 0; $y < $n; $y++ ) {
                for ( $x = 0; $x < $n; $x++ ) {
                    if ( ! $func[ $y ][ $x ] && sfp_qr_masker( $masker, $x, $y ) ) {
                        $proef[ $y ][ $x ] = ! $proef[ $y ][ $x ];
                    }
                }
            }
            $zet_p = function ( $x, $y, $aan ) use ( &$proef ) {
                $proef[ $y ][ $x ] = (bool) $aan;
            };
            sfp_qr_formaat( $zet_p, $n, $masker );
            $s = sfp_qr_straf( $proef );
            if ( $s < $straf ) {
                $straf = $s;
                $beste = $proef;
            }
        }
        return $beste;
    }

    /**
     * De formaatbits (niveau M) voor een masker zetten.
     *
     * @param  callable $zet    function( x, y, aan ).
     * @param  int      $n      Grootte.
     * @param  int      $masker Masker.
     * @return void
     */
    function sfp_qr_formaat( $zet, $n, $masker ) {
        $data = ( 0 << 3 ) | $masker;
        $rest = $data;
        for ( $i = 0; $i < 10; $i++ ) {
            $rest = ( $rest << 1 ) ^ ( ( $rest >> 9 ) * 0x537 );
        }
        $bits = ( ( $data << 10 ) | $rest ) ^ 0x5412;
        $bit  = function ( $i ) use ( $bits ) {
            return ( $bits >> $i ) & 1;
        };
        for ( $i = 0; $i <= 5; $i++ ) {
            $zet( 8, $i, $bit( $i ) );
        }
        $zet( 8, 7, $bit( 6 ) );
        $zet( 8, 8, $bit( 7 ) );
        $zet( 7, 8, $bit( 8 ) );
        for ( $i = 9; $i < 15; $i++ ) {
            $zet( 14 - $i, 8, $bit( $i ) );
        }
        for ( $i = 0; $i < 8; $i++ ) {
            $zet( $n - 1 - $i, 8, $bit( $i ) );
        }
        for ( $i = 8; $i < 15; $i++ ) {
            $zet( 8, $n - 15 + $i, $bit( $i ) );
        }
        $zet( 8, $n - 8, 1 );
    }

    /**
     * Masker toepassen op (x, y)?
     *
     * @param  int $m Masker.
     * @param  int $x Kolom.
     * @param  int $y Rij.
     * @return bool
     */
    function sfp_qr_masker( $m, $x, $y ) {
        switch ( $m ) {
            case 0:
                return 0 === ( $x + $y ) % 2;
            case 1:
                return 0 === $y % 2;
            case 2:
                return 0 === $x % 3;
            case 3:
                return 0 === ( $x + $y ) % 3;
            case 4:
                return 0 === ( intdiv( $x, 3 ) + intdiv( $y, 2 ) ) % 2;
            case 5:
                return 0 === ( $x * $y ) % 2 + ( $x * $y ) % 3;
            case 6:
                return 0 === ( ( $x * $y ) % 2 + ( $x * $y ) % 3 ) % 2;
            default:
                return 0 === ( ( $x + $y ) % 2 + ( $x * $y ) % 3 ) % 2;
        }
    }

    /**
     * Strafpunten (regels 1, 2 en 4).
     *
     * @param  array $m Modules.
     * @return int
     */
    function sfp_qr_straf( array $m ) {
        $n     = count( $m );
        $straf = 0;
        $donker = 0;
        for ( $a = 0; $a < $n; $a++ ) {
            $rij_kleur = null;
            $rij_run   = 0;
            $kol_kleur = null;
            $kol_run   = 0;
            for ( $b = 0; $b < $n; $b++ ) {
                foreach ( array( array( $m[ $a ][ $b ], &$rij_kleur, &$rij_run ), array( $m[ $b ][ $a ], &$kol_kleur, &$kol_run ) ) as $r ) {
                    if ( $r[0] === $r[1] ) {
                        $r[2]++;
                        if ( 5 === $r[2] ) {
                            $straf += 3;
                        } elseif ( $r[2] > 5 ) {
                            $straf++;
                        }
                    } else {
                        $r[1] = $r[0];
                        $r[2] = 1;
                    }
                }
                if ( $m[ $a ][ $b ] ) {
                    $donker++;
                }
                if ( $a < $n - 1 && $b < $n - 1 ) {
                    $c = $m[ $a ][ $b ];
                    if ( $c === $m[ $a ][ $b + 1 ] && $c === $m[ $a + 1 ][ $b ] && $c === $m[ $a + 1 ][ $b + 1 ] ) {
                        $straf += 3;
                    }
                }
            }
        }
        $straf += 10 * intdiv( abs( $donker * 20 - $n * $n * 10 ), $n * $n );
        return $straf;
    }

    /**
     * Vermenigvuldigen in GF(256).
     *
     * @param  int $x X.
     * @param  int $y Y.
     * @return int
     */
    function sfp_qr_gf( $x, $y ) {
        $z = 0;
        for ( $i = 7; $i >= 0; $i-- ) {
            $z  = ( $z << 1 ) ^ ( ( $z >> 7 ) * 0x11D );
            $z ^= ( ( $y >> $i ) & 1 ) * $x;
        }
        return $z & 0xFF;
    }

    /**
     * De deler van Reed-Solomon.
     *
     * @param  int $graad Graad.
     * @return int[]
     */
    function sfp_qr_rs_deler( $graad ) {
        $uit      = array_fill( 0, $graad, 0 );
        $uit[ $graad - 1 ] = 1;
        $wortel   = 1;
        for ( $i = 0; $i < $graad; $i++ ) {
            for ( $j = 0; $j < $graad; $j++ ) {
                $uit[ $j ] = sfp_qr_gf( $uit[ $j ], $wortel );
                if ( $j + 1 < $graad ) {
                    $uit[ $j ] ^= $uit[ $j + 1 ];
                }
            }
            $wortel = sfp_qr_gf( $wortel, 0x02 );
        }
        return $uit;
    }

    /**
     * De rest van Reed-Solomon: de foutcorrectiewoorden.
     *
     * @param  int[] $data  Data.
     * @param  int[] $deler Deler.
     * @return int[]
     */
    function sfp_qr_rs_rest( array $data, array $deler ) {
        $uit = array_fill( 0, count( $deler ), 0 );
        foreach ( $data as $b ) {
            $factor = $b ^ array_shift( $uit );
            $uit[]  = 0;
            foreach ( $deler as $i => $d ) {
                $uit[ $i ] ^= sfp_qr_gf( $d, $factor );
            }
        }
        return $uit;
    }
}
