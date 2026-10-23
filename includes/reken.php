<?php
/**
 * SFP Page Config - Tools: de rekentaal
 *
 * Eén kleine taal voor formules in tools: rekenen, vergelijken en een paar
 * vaste functies. Geen toewijzing, lussen of eigen functies: alleen rekenen,
 * geen code. Gelijk aan de rekentaal in assets/tool/generator.js; de browser
 * rekent voor de bezoeker, de server rekent opnieuw voor de mail.
 *
 * Een formule ziet er zo uit:
 *   afronden(p / totaal * 100 - d / totaal * 100)
 *   als(methode = "los", tel(scores, 9, 10), promoters)
 *   item.fase = "activatie" en item.tijd <= act + 5
 *
 * @package SFP_Page_Config
 * @since   3.10.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De functies van de rekentaal.
 *
 * @return string[]
 */
function sfp_page_config_reken_functies() {
    return array( 'min', 'max', 'abs', 'afronden', 'omlaag', 'omhoog', 'als', 'som', 'aantal', 'tel', 'bevat', 'woorden' );
}

/**
 * Een formule in delen: getallen, tekst, namen en tekens.
 *
 * @param  string $s Formule.
 * @return array|null Null bij een teken dat de taal niet kent.
 */
function sfp_page_config_reken_delen( $s ) {
    $s     = (string) $s;
    $uit   = array();
    $pos   = 0;
    $lengte = strlen( $s );
    while ( $pos < $lengte ) {
        if ( ! preg_match( '/\G\s*(?:(\d+(?:\.\d+)?)|"([^"]*)"|([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+)?)|(<=|>=|!=|[-+*\/()<>=,]))/', $s, $m, 0, $pos ) ) {
            if ( '' === trim( substr( $s, $pos ) ) ) {
                break;
            }
            return null;
        }
        $pos += strlen( $m[0] );
        if ( isset( $m[1] ) && '' !== $m[1] ) {
            $uit[] = array( 'g', (float) $m[1] );
        } elseif ( isset( $m[2] ) && '"' === substr( trim( $m[0] ), 0, 1 ) ) {
            $uit[] = array( 't', $m[2] );
        } elseif ( isset( $m[3] ) && '' !== $m[3] ) {
            $uit[] = in_array( $m[3], array( 'en', 'of', 'niet' ), true ) ? array( 'o', $m[3] ) : array( 'n', $m[3] );
        } else {
            $uit[] = array( 'o', $m[4] );
        }
    }
    return $uit;
}

/**
 * Een formule als boom. Null als de formule niet klopt.
 *
 * @param  string $s Formule.
 * @return array|null
 */
function sfp_page_config_reken_boom( $s ) {
    static $cache = array();
    $s = (string) $s;
    if ( array_key_exists( $s, $cache ) ) {
        return $cache[ $s ];
    }
    $delen = sfp_page_config_reken_delen( $s );
    $boom  = null;
    if ( $delen ) {
        $i    = 0;
        $boom = sfp_page_config_reken_ontleed( $delen, $i, 0 );
        if ( $i !== count( $delen ) ) {
            $boom = null;
        }
    }
    if ( count( $cache ) > 500 ) {
        $cache = array();
    }
    $cache[ $s ] = $boom;
    return $boom;
}

/**
 * Ontleed vanaf positie $i op een niveau: 0 of, 1 en, 2 niet, 3 vergelijken,
 * 4 plus en min, 5 keer en delen, 6 min ervoor, 7 waarde.
 *
 * @param  array $d     Delen.
 * @param  int   $i     Positie (wordt bijgewerkt).
 * @param  int   $niveau Niveau.
 * @return array|null
 */
function sfp_page_config_reken_ontleed( array $d, &$i, $niveau ) {
    $is = function ( $w ) use ( $d, &$i ) {
        return isset( $d[ $i ] ) && 'o' === $d[ $i ][0] && $d[ $i ][1] === $w;
    };
    if ( 0 === $niveau || 1 === $niveau || 4 === $niveau || 5 === $niveau ) {
        $ops  = array( 0 => array( 'of' ), 1 => array( 'en' ), 4 => array( '+', '-' ), 5 => array( '*', '/' ) );
        $l    = sfp_page_config_reken_ontleed( $d, $i, $niveau + 1 );
        while ( null !== $l && isset( $d[ $i ] ) && 'o' === $d[ $i ][0] && in_array( $d[ $i ][1], $ops[ $niveau ], true ) ) {
            $op = $d[ $i ][1];
            $i++;
            $r = sfp_page_config_reken_ontleed( $d, $i, $niveau + 1 );
            if ( null === $r ) {
                return null;
            }
            $l = array( 'op', $op, $l, $r );
        }
        return $l;
    }
    if ( 2 === $niveau ) {
        if ( $is( 'niet' ) ) {
            $i++;
            $x = sfp_page_config_reken_ontleed( $d, $i, 2 );
            return null === $x ? null : array( 'niet', $x );
        }
        return sfp_page_config_reken_ontleed( $d, $i, 3 );
    }
    if ( 3 === $niveau ) {
        $l = sfp_page_config_reken_ontleed( $d, $i, 4 );
        if ( null !== $l && isset( $d[ $i ] ) && 'o' === $d[ $i ][0] && in_array( $d[ $i ][1], array( '=', '!=', '<', '<=', '>', '>=' ), true ) ) {
            $op = $d[ $i ][1];
            $i++;
            $r = sfp_page_config_reken_ontleed( $d, $i, 4 );
            return null === $r ? null : array( 'op', $op, $l, $r );
        }
        return $l;
    }
    if ( 6 === $niveau ) {
        if ( $is( '-' ) ) {
            $i++;
            $x = sfp_page_config_reken_ontleed( $d, $i, 6 );
            return null === $x ? null : array( 'neg', $x );
        }
        return sfp_page_config_reken_ontleed( $d, $i, 7 );
    }
    if ( ! isset( $d[ $i ] ) ) {
        return null;
    }
    $t = $d[ $i ];
    if ( 'g' === $t[0] ) {
        $i++;
        return array( 'w', $t[1] );
    }
    if ( 't' === $t[0] ) {
        $i++;
        return array( 'w', $t[1] );
    }
    if ( $is( '(' ) ) {
        $i++;
        $x = sfp_page_config_reken_ontleed( $d, $i, 0 );
        if ( null === $x || ! $is( ')' ) ) {
            return null;
        }
        $i++;
        return $x;
    }
    if ( 'n' === $t[0] ) {
        $i++;
        if ( $is( '(' ) ) {
            if ( ! in_array( $t[1], sfp_page_config_reken_functies(), true ) ) {
                return null;
            }
            $i++;
            $args = array();
            if ( ! $is( ')' ) ) {
                while ( true ) {
                    $x = sfp_page_config_reken_ontleed( $d, $i, 0 );
                    if ( null === $x ) {
                        return null;
                    }
                    $args[] = $x;
                    if ( $is( ',' ) ) {
                        $i++;
                        continue;
                    }
                    break;
                }
            }
            if ( ! $is( ')' ) ) {
                return null;
            }
            $i++;
            return array( 'f', $t[1], $args );
        }
        return array( 'n', $t[1] );
    }
    return null;
}

/**
 * Een waarde als getal, of null.
 *
 * @param  mixed $v Waarde.
 * @return float|null
 */
function sfp_page_config_reken_getal( $v ) {
    if ( is_bool( $v ) ) {
        return $v ? 1.0 : 0.0;
    }
    if ( is_int( $v ) || is_float( $v ) ) {
        return is_finite( (float) $v ) ? (float) $v : null;
    }
    if ( is_string( $v ) && preg_match( '/^\s*-?\d+(\.\d+)?\s*$/', $v ) ) {
        return (float) $v;
    }
    return null;
}

/**
 * Waar of niet waar.
 *
 * @param  mixed $v Waarde.
 * @return bool
 */
function sfp_page_config_reken_waar( $v ) {
    if ( is_array( $v ) ) {
        return (bool) $v;
    }
    if ( is_string( $v ) ) {
        return '' !== $v;
    }
    if ( is_int( $v ) || is_float( $v ) ) {
        return 0.0 !== (float) $v && ! is_nan( (float) $v );
    }
    return (bool) $v;
}

/**
 * Afronden, de helft van nul af (-12,5 wordt -13), gelijk in browser en PHP.
 *
 * @param  float $x   Getal.
 * @param  int   $dec Decimalen.
 * @return float
 */
function sfp_page_config_reken_afronden( $x, $dec = 0 ) {
    $f = pow( 10, max( 0, min( 10, (int) $dec ) ) );
    $r = floor( abs( (float) $x ) * $f + 0.5 + 1e-9 ) / $f;
    return $x < 0 && $r > 0 ? -$r : $r;
}

/**
 * Een getal als tekst zoals de browser het schrijft, op hoogstens zes
 * decimalen.
 *
 * @param  float $n Getal.
 * @return string
 */
function sfp_page_config_reken_tekst_getal( $n ) {
    $r = sfp_page_config_reken_afronden( $n, 6 );
    if ( 0.0 === (float) $r ) {
        return '0';
    }
    return sfp_page_config_generator_js_getal( (float) $r );
}

/**
 * Een waarde als tekst.
 *
 * @param  mixed $v Waarde.
 * @return string
 */
function sfp_page_config_reken_tekst( $v ) {
    if ( null === $v ) {
        return '';
    }
    if ( is_bool( $v ) ) {
        return $v ? '1' : '';
    }
    if ( is_int( $v ) || is_float( $v ) ) {
        return is_finite( (float) $v ) ? sfp_page_config_reken_tekst_getal( (float) $v ) : '';
    }
    if ( is_array( $v ) ) {
        return implode( ', ', array_map( 'sfp_page_config_reken_tekst', array_values( $v ) ) );
    }
    return (string) $v;
}

/**
 * Alle getallen uit waarden en lijsten.
 *
 * @param  array $args Waarden.
 * @return float[]
 */
function sfp_page_config_reken_platte_getallen( array $args ) {
    $uit = array();
    foreach ( $args as $a ) {
        foreach ( is_array( $a ) ? $a : array( $a ) as $x ) {
            $n = sfp_page_config_reken_getal( $x );
            if ( null !== $n ) {
                $uit[] = $n;
            }
        }
    }
    return $uit;
}

/**
 * Het aantal woorden in een tekst: stukken tussen spaties met minstens één
 * letter of cijfer.
 *
 * @param  string $s Tekst.
 * @return int
 */
function sfp_page_config_reken_woorden( $s ) {
    $n = 0;
    foreach ( preg_split( '/[ \t\r\n\f\x0B\x{00A0}]+/u', (string) $s ) as $w ) {
        if ( preg_match( '/[\p{L}\p{N}]/u', $w ) ) {
            $n++;
        }
    }
    return $n;
}

/**
 * Reken een boom uit.
 *
 * @param  array    $boom  Boom.
 * @param  callable $naam  Functie die een naam opzoekt.
 * @return mixed
 */
function sfp_page_config_reken_uit( array $boom, $naam ) {
    switch ( $boom[0] ) {
        case 'w':
            return $boom[1];
        case 'n':
            return $naam( $boom[1] );
        case 'neg':
            $x = sfp_page_config_reken_getal( sfp_page_config_reken_uit( $boom[1], $naam ) );
            return null === $x ? null : -$x;
        case 'niet':
            return ! sfp_page_config_reken_waar( sfp_page_config_reken_uit( $boom[1], $naam ) );
        case 'op':
            $op = $boom[1];
            $l  = sfp_page_config_reken_uit( $boom[2], $naam );
            if ( 'en' === $op ) {
                return sfp_page_config_reken_waar( $l ) && sfp_page_config_reken_waar( sfp_page_config_reken_uit( $boom[3], $naam ) );
            }
            if ( 'of' === $op ) {
                return sfp_page_config_reken_waar( $l ) || sfp_page_config_reken_waar( sfp_page_config_reken_uit( $boom[3], $naam ) );
            }
            $r  = sfp_page_config_reken_uit( $boom[3], $naam );
            $ln = sfp_page_config_reken_getal( $l );
            $rn = sfp_page_config_reken_getal( $r );
            if ( '=' === $op || '!=' === $op ) {
                $gelijk = ( null !== $ln && null !== $rn ) ? $ln === $rn : sfp_page_config_reken_tekst( $l ) === sfp_page_config_reken_tekst( $r );
                return '=' === $op ? $gelijk : ! $gelijk;
            }
            if ( null === $ln || null === $rn ) {
                return in_array( $op, array( '<', '<=', '>', '>=' ), true ) ? false : null;
            }
            switch ( $op ) {
                case '+':
                    return $ln + $rn;
                case '-':
                    return $ln - $rn;
                case '*':
                    return $ln * $rn;
                case '/':
                    return 0.0 === $rn ? null : $ln / $rn;
                case '<':
                    return $ln < $rn;
                case '<=':
                    return $ln <= $rn;
                case '>':
                    return $ln > $rn;
                case '>=':
                    return $ln >= $rn;
            }
            return null;
        case 'f':
            $args = array();
            foreach ( $boom[2] as $x ) {
                $args[] = sfp_page_config_reken_uit( $x, $naam );
            }
            return sfp_page_config_reken_functie( $boom[1], $args );
    }
    return null;
}

/**
 * Eén functie van de rekentaal.
 *
 * @param  string $f    Naam.
 * @param  array  $args Waarden.
 * @return mixed
 */
function sfp_page_config_reken_functie( $f, array $args ) {
    $n = function ( $k ) use ( $args ) {
        return isset( $args[ $k ] ) ? sfp_page_config_reken_getal( $args[ $k ] ) : null;
    };
    switch ( $f ) {
        case 'min':
        case 'max':
            $g = sfp_page_config_reken_platte_getallen( $args );
            return $g ? ( 'min' === $f ? min( $g ) : max( $g ) ) : null;
        case 'abs':
            return null === $n( 0 ) ? null : abs( $n( 0 ) );
        case 'afronden':
            return null === $n( 0 ) ? null : sfp_page_config_reken_afronden( $n( 0 ), null === $n( 1 ) ? 0 : (int) $n( 1 ) );
        case 'omlaag':
            return null === $n( 0 ) ? null : floor( $n( 0 ) );
        case 'omhoog':
            return null === $n( 0 ) ? null : ceil( $n( 0 ) );
        case 'als':
            return sfp_page_config_reken_waar( isset( $args[0] ) ? $args[0] : null ) ? ( isset( $args[1] ) ? $args[1] : null ) : ( isset( $args[2] ) ? $args[2] : null );
        case 'som':
            return (float) array_sum( sfp_page_config_reken_platte_getallen( $args ) );
        case 'aantal':
            $x = isset( $args[0] ) ? $args[0] : null;
            if ( is_array( $x ) ) {
                return (float) count( $x );
            }
            if ( is_string( $x ) ) {
                return (float) count( array_filter( preg_split( '/\r?\n/', $x ), function ( $r ) { return '' !== trim( $r ); } ) );
            }
            return null === $x ? 0.0 : 1.0;
        case 'tel':
            $van = $n( 1 );
            $tot = $n( 2 );
            $c   = 0;
            foreach ( sfp_page_config_reken_platte_getallen( array( isset( $args[0] ) ? $args[0] : null ) ) as $g ) {
                if ( ( null === $van || $g >= $van ) && ( null === $tot || $g <= $tot ) ) {
                    $c++;
                }
            }
            return (float) $c;
        case 'bevat':
            $x = isset( $args[0] ) ? $args[0] : null;
            $w = sfp_page_config_reken_tekst( isset( $args[1] ) ? $args[1] : null );
            if ( is_array( $x ) ) {
                return in_array( $w, array_map( 'sfp_page_config_reken_tekst', $x ), true );
            }
            return '' !== $w && false !== strpos( sfp_page_config_reken_tekst( $x ), $w );
        case 'woorden':
            return (float) sfp_page_config_reken_woorden( sfp_page_config_reken_tekst( isset( $args[0] ) ? $args[0] : null ) );
    }
    return null;
}

/**
 * Een getal met vaste decimalen en de scheidingstekens van de sitetaal.
 *
 * @param  float $n   Getal.
 * @param  int   $dec Decimalen.
 * @return string
 */
function sfp_page_config_reken_opmaak( $n, $dec ) {
    $dec  = max( 0, min( 6, (int) $dec ) );
    $r    = sfp_page_config_reken_afronden( $n, $dec );
    $teken = sfp_page_config_artikel_tekst( 'tool_getal_scheiding' );
    $tekens = sfp_page_config_generator_tekens( (string) $teken );
    $komma  = isset( $tekens[0] ) ? $tekens[0] : ',';
    $punt   = isset( $tekens[1] ) ? $tekens[1] : '.';
    $s      = number_format( abs( $r ), $dec, '.', '' );
    $delen  = explode( '.', $s );
    $heel   = preg_replace( '/\B(?=(\d{3})+(?!\d))/', $punt, $delen[0] );
    return ( $r < 0 ? '-' : '' ) . $heel . ( $dec > 0 ? $komma . $delen[1] : '' );
}

/**
 * Een opmaakfilter op een getal: getal:n, euro:n, procent:n, teken:n,
 * minsec, klok. Geen getal: de tekst blijft zoals hij is.
 *
 * @param  string $soort Filter.
 * @param  string $arg   Decimalen.
 * @param  string $w     Waarde als tekst.
 * @return string
 */
function sfp_page_config_reken_formaat( $soort, $arg, $w ) {
    $n = sfp_page_config_reken_getal( $w );
    if ( null === $n ) {
        return $w;
    }
    $dec = '' !== $arg && is_numeric( $arg ) ? (int) $arg : 0;
    switch ( $soort ) {
        case 'getal':
            return sfp_page_config_reken_opmaak( $n, $dec );
        case 'euro':
            return sprintf( (string) sfp_page_config_artikel_tekst( 'tool_euro' ), sfp_page_config_reken_opmaak( $n, $dec ) );
        case 'procent':
            return sfp_page_config_reken_opmaak( $n, $dec ) . '%';
        case 'teken':
            return ( sfp_page_config_reken_afronden( $n, $dec ) > 0 ? '+' : '' ) . sfp_page_config_reken_opmaak( $n, $dec );
        case 'minsec':
            $s = max( 0, (int) sfp_page_config_reken_afronden( $n, 0 ) );
            return intdiv( $s, 60 ) . ':' . str_pad( (string) ( $s % 60 ), 2, '0', STR_PAD_LEFT );
        case 'klok':
            $m = max( 0, (int) sfp_page_config_reken_afronden( $n, 0 ) );
            return intdiv( $m, 60 ) . ':' . str_pad( (string) ( $m % 60 ), 2, '0', STR_PAD_LEFT );
    }
    return $w;
}

/**
 * De opmaakfilters van de rekentaal.
 *
 * @return string[]
 */
function sfp_page_config_reken_formaten() {
    return array( 'getal', 'euro', 'procent', 'teken', 'minsec', 'klok' );
}
