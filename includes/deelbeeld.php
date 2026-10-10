<?php
/**
 * SFP Page Config - Deelbeelden bij een artikel
 *
 * Bij het opslaan van een bericht maakt de server van elk blok Inzicht en
 * Samenvatting een afbeelding in de huisstijl van de site en zet die in de
 * mediabibliotheek, gekoppeld aan het bericht:
 *
 *   Inzicht        1200 x 627 (LinkedIn), ook het deelbeeld (og:image) van
 *                  het artikel als de redacteur geen eigen beeld koos
 *   Samenvatting   1080 x 1080
 *
 * Kleuren: de hexwaarden uit het Astra-palet van de site, via de rollen van
 * het snippet "Kleuren: rollen van het palet". Fonts: de families die in
 * Astra staan; de plugin haalt het TTF-bestand één keer op bij Google Fonts
 * en bewaart het in uploads/sfp-deelbeeld/fonts. Er staat geen kleur en geen
 * fontnaam in dit bestand.
 *
 * Kan de server geen beeld maken (geen GD of FreeType, font niet op te
 * halen, kleurrollen onbekend), dan toont het blok dezelfde kaart in HTML
 * en blijft de reden staan in de stand (GET /sfp/v1/artikel/instellingen).
 *
 * Een beeld houdt zijn plek in de mediabibliotheek; verandert de tekst, dan
 * vervangt de plugin het bestand. Verdwijnt een blok, dan blijft het beeld
 * staan: de plugin verwijdert niets uit de mediabibliotheek.
 *
 * Terugdraaien: dit bestand uit de lijst in sfp-page-config.php halen. De
 * blokken tonen dan de kaart in HTML; gemaakte beelden blijven staan.
 *
 * @package SFP_Page_Config
 * @since   2.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Versie van het ontwerp. Ophogen laat alle beelden opnieuw maken bij de
 * eerstvolgende keer opslaan.
 */
define( 'SFP_PAGE_CONFIG_DEELBEELD_ONTWERP', 2 );

/* =========================================================================
 * Wat de server kan
 * ====================================================================== */

/**
 * Kan deze server tekenen? GD met FreeType en JPEG.
 *
 * @return bool
 */
function sfp_page_config_deelbeeld_kan() {
    return function_exists( 'imagecreatetruecolor' ) && function_exists( 'imagettftext' ) && function_exists( 'imagettfbbox' ) && function_exists( 'imagejpeg' );
}

/**
 * De werkmap in uploads, met een submap. Maakt de map als hij ontbreekt.
 *
 * @param  string $sub Submap (fonts of portret), of '' voor de werkmap zelf.
 * @return string      Pad met afsluitende slash, of '' als de map niet te maken is.
 */
function sfp_page_config_deelbeeld_map( $sub = '' ) {
    $uploads = wp_upload_dir( null, false );
    if ( ! empty( $uploads['error'] ) ) {
        return '';
    }
    $map = trailingslashit( $uploads['basedir'] ) . 'sfp-deelbeeld/' . ( '' !== $sub ? $sub . '/' : '' );
    if ( ! is_dir( $map ) && ! wp_mkdir_p( $map ) ) {
        return '';
    }
    return $map;
}

/**
 * Onthoud de laatste reden waarom een beeld niet gemaakt kon worden.
 *
 * @param  string|null $reden Reden om te bewaren, '' om te wissen, null om te lezen.
 * @return string
 */
function sfp_page_config_deelbeeld_fout( $reden = null ) {
    if ( null === $reden ) {
        $bewaard = get_option( 'sfp_deelbeeld_fout', array() );
        return is_array( $bewaard ) && ! empty( $bewaard['reden'] ) ? $bewaard['reden'] . ' (' . wp_date( 'Y-m-d H:i', (int) $bewaard['tijd'] ) . ')' : '';
    }
    if ( '' === $reden ) {
        delete_option( 'sfp_deelbeeld_fout' );
        return '';
    }
    update_option( 'sfp_deelbeeld_fout', array( 'reden' => $reden, 'tijd' => time() ), false );
    return $reden;
}

/* =========================================================================
 * Fonts
 * ====================================================================== */

/**
 * De eerste familienaam uit een Astra-fontwaarde ("'Naam', sans-serif").
 *
 * @param  string $waarde Waarde uit Astra.
 * @return string         Familienaam, of '' bij inherit of een lege waarde.
 */
function sfp_page_config_deelbeeld_familie( $waarde ) {
    $eerste = trim( (string) strtok( (string) $waarde, ',' ) );
    $eerste = trim( $eerste, " '\"" );
    if ( '' === $eerste || 0 === strcasecmp( $eerste, 'inherit' ) || ! preg_match( '/^[A-Za-z0-9 ]+$/', $eerste ) ) {
        return '';
    }
    return $eerste;
}

/**
 * Pad van het TTF-bestand van een familie op een gewicht. Staat het er
 * nog niet en mag er opgehaald worden, dan haalt de plugin het één keer
 * op bij Google Fonts.
 *
 * @param  string $familie Familienaam.
 * @param  int    $gewicht Gewicht (400, 700, 900).
 * @param  bool   $ophalen Mag de plugin het bestand ophalen?
 * @return string          Pad, of '' als het bestand er niet is.
 */
function sfp_page_config_deelbeeld_font( $familie, $gewicht, $ophalen = false ) {
    $map = sfp_page_config_deelbeeld_map( 'fonts' );
    if ( '' === $familie || '' === $map ) {
        return '';
    }
    $pad = $map . sanitize_title( $familie ) . '-' . (int) $gewicht . '.ttf';
    if ( is_readable( $pad ) && filesize( $pad ) > 1000 ) {
        return $pad;
    }
    if ( ! $ophalen ) {
        return '';
    }
    // Na een mislukte poging een uur wachten, zodat opslaan niet traag blijft.
    $rem = 'sfp_deelbeeld_font_' . md5( $pad );
    if ( get_transient( $rem ) ) {
        return '';
    }
    $ttf = sfp_page_config_deelbeeld_font_ophalen( $familie, (int) $gewicht );
    if ( '' === $ttf ) {
        set_transient( $rem, 1, HOUR_IN_SECONDS );
        return '';
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- eigen werkmap in uploads.
    if ( false === file_put_contents( $pad, $ttf ) ) {
        return '';
    }
    return $pad;
}

/**
 * Haal een TTF op bij Google Fonts. Een oude browser in de user-agent
 * laat Google TTF leveren in plaats van WOFF2; GD leest alleen TTF.
 *
 * @param  string $familie Familienaam.
 * @param  int    $gewicht Gewicht.
 * @return string          De inhoud van het bestand, of ''.
 */
function sfp_page_config_deelbeeld_font_ophalen( $familie, $gewicht ) {
    $args = array(
        'timeout'    => 15,
        'user-agent' => 'Mozilla/4.0',
    );
    // Eerst het gevraagde gewicht; kent de familie maar één gewicht (een
    // displayfont), dan de familie zonder gewicht.
    $pogingen = array(
        'https://fonts.googleapis.com/css2?family=' . rawurlencode( $familie ) . ':wght@' . $gewicht,
        'https://fonts.googleapis.com/css2?family=' . rawurlencode( $familie ),
    );
    foreach ( $pogingen as $url ) {
        $css = wp_remote_get( $url, $args );
        if ( is_wp_error( $css ) || 200 !== (int) wp_remote_retrieve_response_code( $css ) ) {
            continue;
        }
        if ( ! preg_match( '#url\((https://fonts\.gstatic\.com/[^)\s]+\.ttf)\)#', wp_remote_retrieve_body( $css ), $m ) ) {
            continue;
        }
        $ttf = wp_remote_get( $m[1], array( 'timeout' => 20 ) );
        if ( is_wp_error( $ttf ) || 200 !== (int) wp_remote_retrieve_response_code( $ttf ) ) {
            continue;
        }
        $inhoud = wp_remote_retrieve_body( $ttf );
        // Een TrueType- of OpenType-bestand begint met een van deze vier tekens.
        $kop = substr( $inhoud, 0, 4 );
        if ( strlen( $inhoud ) > 1000 && in_array( $kop, array( "\x00\x01\x00\x00", 'true', 'OTTO' ), true ) ) {
            return $inhoud;
        }
    }
    return '';
}

/**
 * De drie fonts van een deelbeeld: kop, tekst en vette tekst.
 *
 * @param  bool $ophalen Mag de plugin ontbrekende bestanden ophalen?
 * @return array{kop: string, tekst: string, vet: string}|null Null als er een ontbreekt.
 */
function sfp_page_config_deelbeeld_fonts( $ophalen = false ) {
    $kop_fam  = sfp_page_config_deelbeeld_familie( sfp_page_config_astra_option( 'headings-font-family' ) );
    $body_fam = sfp_page_config_deelbeeld_familie( sfp_page_config_astra_option( 'body-font-family' ) );
    if ( '' === $kop_fam ) {
        $kop_fam = $body_fam;
    }
    $gewicht = (int) sfp_page_config_astra_option( 'headings-font-weight' );
    if ( $gewicht < 100 || $gewicht > 900 ) {
        $gewicht = 900;
    }
    $fonts = array(
        'kop'   => sfp_page_config_deelbeeld_font( $kop_fam, $gewicht, $ophalen ),
        'tekst' => sfp_page_config_deelbeeld_font( $body_fam, 400, $ophalen ),
        'vet'   => sfp_page_config_deelbeeld_font( $body_fam, 700, $ophalen ),
    );
    return in_array( '', $fonts, true ) ? null : $fonts;
}

/* =========================================================================
 * Kleuren
 * ====================================================================== */

/**
 * De hexwaarde van een Astra-paletslot.
 *
 * @param  int $slot Slot 0 tot en met 8.
 * @return string    Hex met #, of ''.
 */
function sfp_page_config_deelbeeld_slot_hex( $slot ) {
    $palet = function_exists( 'astra_get_option' ) ? astra_get_option( 'global-color-palette' ) : null;
    if ( ! is_array( $palet ) || empty( $palet['palette'] ) ) {
        $settings = get_option( 'astra-settings', array() );
        $palet    = is_array( $settings ) && isset( $settings['global-color-palette'] ) ? $settings['global-color-palette'] : array();
    }
    $kleur = isset( $palet['palette'][ $slot ] ) ? (string) $palet['palette'][ $slot ] : '';
    return preg_match( '/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $kleur ) ? $kleur : '';
}

/**
 * De kleuren van een deelbeeld: primair, secundair en wit, als hex.
 *
 * De rollen komen uit het snippet "Kleuren: rollen van het palet". Zonder
 * dat snippet is niet bekend welk slot de secundaire kleur draagt en maakt
 * de plugin geen beeld.
 *
 * @return array{primair: string, secundair: string, wit: string}|null
 */
function sfp_page_config_deelbeeld_kleuren() {
    $slots = function_exists( 'sfp_kleurrollen_slots' ) ? (array) sfp_kleurrollen_slots() : array();
    /**
     * De paletslots per rol voor het deelbeeld. Voor sites zonder het
     * rollensnippet.
     *
     * @param array<string, int> $slots rol => slot.
     */
    $slots   = (array) apply_filters( 'sfp_page_config_deelbeeld_slots', $slots );
    $kleuren = array();
    foreach ( array( 'primair', 'secundair', 'wit' ) as $rol ) {
        $hex = isset( $slots[ $rol ] ) ? sfp_page_config_deelbeeld_slot_hex( (int) $slots[ $rol ] ) : '';
        if ( '' === $hex ) {
            return null;
        }
        $kleuren[ $rol ] = $hex;
    }
    return $kleuren;
}

/* =========================================================================
 * Portret
 * ====================================================================== */

/**
 * De foto van de auteur als lokaal bestand. Een maand bewaard.
 *
 * @param  int  $user_id Auteur.
 * @param  bool $ophalen Mag de plugin de foto ophalen?
 * @return string        Pad, of '' zonder foto.
 */
function sfp_page_config_deelbeeld_portret( $user_id, $ophalen = false ) {
    $url = get_avatar_url( $user_id, array( 'size' => 192, 'default' => '404' ) );
    $map = sfp_page_config_deelbeeld_map( 'portret' );
    if ( ! $url || '' === $map ) {
        return '';
    }
    $pad = $map . (int) $user_id . '-' . substr( md5( $url ), 0, 10 ) . '.img';
    if ( is_readable( $pad ) && filesize( $pad ) > 500 && filemtime( $pad ) > time() - MONTH_IN_SECONDS ) {
        return $pad;
    }
    if ( ! $ophalen ) {
        return is_readable( $pad ) ? $pad : '';
    }
    $antwoord = wp_remote_get( $url, array( 'timeout' => 10 ) );
    if ( is_wp_error( $antwoord ) || 200 !== (int) wp_remote_retrieve_response_code( $antwoord ) ) {
        return is_readable( $pad ) ? $pad : '';
    }
    $inhoud = wp_remote_retrieve_body( $antwoord );
    // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- een onleesbaar beeld is geen fout.
    if ( strlen( $inhoud ) < 500 || ! @getimagesizefromstring( $inhoud ) ) {
        return '';
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- eigen werkmap in uploads.
    return false === file_put_contents( $pad, $inhoud ) ? '' : $pad;
}

/* =========================================================================
 * De blokken van een bericht
 * ====================================================================== */

/**
 * Controlewaarde van een tekst: gelijk bij het opslaan en bij het tonen,
 * zodat een blok alleen het beeld krijgt dat bij zijn eigen tekst hoort.
 *
 * @param  string $tekst Kale tekst.
 * @return string
 */
function sfp_page_config_deelbeeld_controle( $tekst ) {
    $tekst = html_entity_decode( (string) $tekst, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    return md5( trim( preg_replace( '/\s+/u', ' ', $tekst ) ) );
}

/**
 * De punten van een samenvatting uit de HTML van de lijst.
 *
 * @param  string $html HTML met li-elementen.
 * @return string[]
 */
function sfp_page_config_deelbeeld_punten( $html ) {
    preg_match_all( '#<li[^>]*>(.*?)</li>#s', (string) $html, $m );
    return array_values( array_filter( array_map( 'wp_strip_all_tags', $m[1] ), 'strlen' ) );
}

/**
 * Zoek de blokken Inzicht en Samenvatting in een bericht, in de volgorde
 * waarin ze getoond worden. De nummering is gelijk aan die van
 * sfp_page_config_blok_volgnummer() bij het tonen.
 *
 * @param  WP_Post $post Bericht.
 * @return array<string, array> sleutel (inzicht-1, samenvatting-1) => gegevens.
 */
function sfp_page_config_deelbeeld_slots( $post ) {
    $slots  = array();
    $teller = array( 'inzicht' => 0, 'samenvatting' => 0 );
    $loop   = function ( array $blokken ) use ( &$loop, &$slots, &$teller, $post ) {
        foreach ( $blokken as $blok ) {
            $naam  = isset( $blok['blockName'] ) ? (string) $blok['blockName'] : '';
            $attrs = isset( $blok['attrs'] ) && is_array( $blok['attrs'] ) ? $blok['attrs'] : array();
            if ( 'sfp/inzicht' === $naam ) {
                $tekst = sfp_page_config_inzicht_kses( sfp_page_config_blok_attr( $attrs, 'tekst' ) );
                if ( '' !== $tekst ) {
                    $label = sfp_page_config_blok_attr( $attrs, 'label' );
                    $kaal  = wp_strip_all_tags( $tekst );
                    $slots[ 'inzicht-' . ( ++$teller['inzicht'] ) ] = array(
                        'soort'    => 'inzicht',
                        'label'    => '' !== $label ? $label : sfp_page_config_artikel_tekst( 'inzicht' ),
                        // Het accentwoord staat tussen sterretjes voor de tekenfunctie.
                        'tekst'    => html_entity_decode( wp_strip_all_tags( preg_replace( '#<em>(.*?)</em>#s', '*$1*', str_replace( '*', '', $tekst ) ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
                        'alt'      => html_entity_decode( $kaal, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
                        'controle' => sfp_page_config_deelbeeld_controle( $kaal ),
                    );
                }
            } elseif ( 'sfp/samenvatting' === $naam ) {
                $nr     = ++$teller['samenvatting'];
                $punten = sfp_page_config_deelbeeld_punten( serialize_blocks( isset( $blok['innerBlocks'] ) ? $blok['innerBlocks'] : array() ) );
                if ( $punten ) {
                    $onderwerp = sfp_page_config_blok_attr( $attrs, 'onderwerp' );
                    if ( '' === $onderwerp ) {
                        $onderwerp = get_the_title( $post );
                    }
                    $decode = function ( $t ) {
                        return html_entity_decode( $t, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                    };
                    $slots[ 'samenvatting-' . $nr ] = array(
                        'soort'    => 'samenvatting',
                        'label'    => sprintf( sfp_page_config_artikel_tekst( 'dingen' ), count( $punten ) ),
                        'titel'    => $decode( wp_strip_all_tags( $onderwerp ) ),
                        'punten'   => array_map( $decode, $punten ),
                        'alt'      => sprintf( sfp_page_config_artikel_tekst( 'dingen' ), count( $punten ) ) . ' ' . $decode( wp_strip_all_tags( $onderwerp ) ),
                        'controle' => sfp_page_config_deelbeeld_controle( implode( "\n", $punten ) ),
                    );
                }
                continue; // De lijst in een samenvatting bevat geen andere deelblokken.
            }
            if ( ! empty( $blok['innerBlocks'] ) ) {
                $loop( $blok['innerBlocks'] );
            }
        }
    };
    $loop( parse_blocks( $post->post_content ) );
    return $slots;
}

/* =========================================================================
 * Maken
 * ====================================================================== */

/**
 * Maak of ververs de deelbeelden van een bericht.
 *
 * @param  int  $post_id Bericht.
 * @param  bool $forceer Ook opnieuw maken als de tekst niet veranderd is.
 * @return array         Verslag: per slot wat er gebeurd is, plus een reden als het niet kon.
 */
function sfp_page_config_deelbeeld_maak( $post_id, $forceer = false ) {
    $post    = get_post( $post_id );
    $verslag = array( 'gemaakt' => array(), 'ongewijzigd' => array(), 'mislukt' => array(), 'reden' => '' );
    if ( ! $post ) {
        $verslag['reden'] = 'Bericht niet gevonden.';
        return $verslag;
    }
    $slots   = sfp_page_config_deelbeeld_slots( $post );
    $bewaard = get_post_meta( $post_id, '_sfp_deelbeeld', true );
    $bewaard = is_array( $bewaard ) ? $bewaard : array();
    if ( ! $slots && ! $bewaard ) {
        return $verslag;
    }

    // Slots waarvan het blok weg is: het beeld blijft staan, maar hoort bij geen blok meer.
    foreach ( $bewaard as $sleutel => $slot ) {
        if ( ! isset( $slots[ $sleutel ] ) ) {
            $bewaard[ $sleutel ]['actueel'] = false;
        }
    }

    $kan = $slots ? sfp_page_config_deelbeeld_voorwaarden() : array( 'reden' => '' );
    if ( '' !== $kan['reden'] ) {
        foreach ( $slots as $sleutel => $slot ) {
            // Het oude beeld past niet meer bij een gewijzigde tekst.
            if ( isset( $bewaard[ $sleutel ] ) && ( ! isset( $bewaard[ $sleutel ]['controle'] ) || $bewaard[ $sleutel ]['controle'] !== $slot['controle'] ) ) {
                $bewaard[ $sleutel ]['actueel'] = false;
            }
            $verslag['mislukt'][] = $sleutel;
        }
        $verslag['reden'] = sfp_page_config_deelbeeld_fout( $kan['reden'] );
        update_post_meta( $post_id, '_sfp_deelbeeld', $bewaard );
        return $verslag;
    }

    require_once SFP_PAGE_CONFIG_DIR . 'includes/deelbeeld-tekenen.php';

    $auteur  = (int) $post->post_author;
    $portret = $slots ? sfp_page_config_deelbeeld_portret( $auteur, true ) : '';
    $basis   = $slots ? array_merge(
        $kan['kleuren'],
        array(
            'fonts'   => $kan['fonts'],
            'naam'    => get_the_author_meta( 'display_name', $auteur ),
            'domein'  => preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ),
            'portret' => $portret,
        )
    ) : array();

    foreach ( $slots as $sleutel => $slot ) {
        $gegevens = array_merge( $basis, $slot );
        $hash     = md5( wp_json_encode( array( SFP_PAGE_CONFIG_DEELBEELD_ONTWERP, $gegevens, '' !== $portret ? filemtime( $portret ) : 0 ) ) );
        $oud      = isset( $bewaard[ $sleutel ] ) ? $bewaard[ $sleutel ] : array();
        $bestaat  = ! empty( $oud['id'] ) && 'attachment' === get_post_type( (int) $oud['id'] ) && is_readable( (string) get_attached_file( (int) $oud['id'] ) );

        if ( ! $forceer && $bestaat && isset( $oud['hash'] ) && $oud['hash'] === $hash ) {
            $bewaard[ $sleutel ]['actueel'] = true;
            $verslag['ongewijzigd'][]       = $sleutel;
            continue;
        }

        $id = sfp_page_config_deelbeeld_schrijf( $post, $sleutel, $gegevens, $hash, $bestaat ? (int) $oud['id'] : 0 );
        if ( ! $id ) {
            if ( $oud ) {
                $bewaard[ $sleutel ]['actueel'] = false;
            }
            $verslag['mislukt'][] = $sleutel;
            continue;
        }
        $inzicht             = 'inzicht' === $slot['soort'];
        $bewaard[ $sleutel ] = array(
            'id'       => $id,
            'hash'     => $hash,
            'controle' => $slot['controle'],
            'breedte'  => $inzicht ? 1200 : 1080,
            'hoogte'   => $inzicht ? 627 : 1080,
            'klein'    => is_file( sfp_page_config_deelbeeld_klein_pad( (string) get_attached_file( $id ) ) ),
            'actueel'  => true,
        );
        $verslag['gemaakt'][] = $sleutel;
    }

    if ( $verslag['mislukt'] ) {
        $verslag['reden'] = sfp_page_config_deelbeeld_fout( 'Het beeld kon niet naar de uploads-map geschreven worden.' );
    } elseif ( $verslag['gemaakt'] ) {
        sfp_page_config_deelbeeld_fout( '' );
    }
    update_post_meta( $post_id, '_sfp_deelbeeld', $bewaard );
    return $verslag;
}

/**
 * Is alles aanwezig om te tekenen? Haalt ontbrekende fonts op.
 *
 * @return array{reden: string, kleuren?: array, fonts?: array}
 */
function sfp_page_config_deelbeeld_voorwaarden() {
    if ( ! sfp_page_config_deelbeeld_kan() ) {
        return array( 'reden' => 'De server mist GD met FreeType en JPEG.' );
    }
    $kleuren = sfp_page_config_deelbeeld_kleuren();
    if ( ! $kleuren ) {
        return array( 'reden' => 'De kleurrollen van deze site zijn niet bekend (snippet "Kleuren: rollen van het palet" ontbreekt of het Astra-palet is leeg).' );
    }
    $fonts = sfp_page_config_deelbeeld_fonts( true );
    if ( ! $fonts ) {
        return array( 'reden' => 'De fonts uit Astra konden niet als TTF worden opgehaald bij Google Fonts.' );
    }
    return array( 'reden' => '', 'kleuren' => $kleuren, 'fonts' => $fonts );
}

/**
 * Pad of URL van de lichtere versie die bij een deelbeeld hoort.
 *
 * @param  string $pad Pad of URL van het JPG.
 * @return string
 */
function sfp_page_config_deelbeeld_klein_pad( $pad ) {
    return preg_replace( '/\.jpg$/', '-800.webp', (string) $pad );
}

/**
 * Teken één beeld en zet het in de mediabibliotheek. Bestaat het beeld al,
 * dan vervangt de plugin het bestand en blijft het mediabericht hetzelfde.
 *
 * @param  WP_Post $post     Bericht.
 * @param  string  $sleutel  Slot, bijvoorbeeld inzicht-1.
 * @param  array   $gegevens Invoer voor de tekenfunctie.
 * @param  string  $hash     Hash van de invoer.
 * @param  int     $bestaand ID van het bestaande mediabericht, of 0.
 * @return int               ID van het mediabericht, of 0 als het mislukte.
 */
function sfp_page_config_deelbeeld_schrijf( $post, $sleutel, array $gegevens, $hash, $bestaand = 0 ) {
    $uploads = wp_upload_dir();
    if ( ! empty( $uploads['error'] ) ) {
        return 0;
    }
    $oud_pad = $bestaand ? (string) get_attached_file( $bestaand ) : '';
    $map     = '' !== $oud_pad ? trailingslashit( dirname( $oud_pad ) ) : trailingslashit( $uploads['path'] );
    $slug    = '' !== $post->post_name ? $post->post_name : 'artikel-' . $post->ID;
    // De hash in de naam geeft een gewijzigd beeld een nieuwe URL, zodat geen cache het oude toont.
    $pad = $map . sanitize_file_name( 'deelbeeld-' . $slug . '-' . $sleutel . '-' . substr( $hash, 0, 8 ) . '.jpg' );

    $beeld = sfp_page_config_deelbeeld_teken( $gegevens );
    if ( ! $beeld ) {
        return 0;
    }
    imageinterlace( $beeld, true );
    $gelukt = imagejpeg( $beeld, $pad, 88 );
    $b      = imagesx( $beeld );
    $h      = imagesy( $beeld );
    // Voor in het artikel: een lichtere versie van het inzicht (800 px, WebP).
    // Het JPG op volle maat blijft het beeld om te delen en te downloaden.
    if ( $gelukt && 'inzicht' === $gegevens['soort'] && function_exists( 'imagewebp' ) ) {
        $klein = imagescale( $beeld, 800, -1, IMG_BICUBIC );
        if ( $klein ) {
            imagewebp( $klein, sfp_page_config_deelbeeld_klein_pad( $pad ), 82 );
            imagedestroy( $klein );
        }
    }
    imagedestroy( $beeld );
    if ( ! $gelukt || ! is_readable( $pad ) ) {
        return 0;
    }

    $titel = sprintf( 'Deelbeeld %s: %s', str_replace( '-', ' ', $sleutel ), get_the_title( $post ) );
    if ( $bestaand ) {
        update_attached_file( $bestaand, $pad );
        wp_update_post(
            array(
                'ID'         => $bestaand,
                'post_title' => $titel,
            )
        );
        $id = $bestaand;
    } else {
        $id = wp_insert_attachment(
            array(
                'post_mime_type' => 'image/jpeg',
                'post_title'     => $titel,
                'post_content'   => '',
                'post_status'    => 'inherit',
            ),
            $pad,
            $post->ID
        );
        if ( ! $id || is_wp_error( $id ) ) {
            wp_delete_file( $pad );
            return 0;
        }
    }
    // Geen tussenmaten: het beeld wordt op zijn eigen maat gebruikt.
    wp_update_attachment_metadata(
        $id,
        array(
            'width'    => $b,
            'height'   => $h,
            'file'     => _wp_relative_upload_path( $pad ),
            'filesize' => filesize( $pad ),
            'sizes'    => array(),
        )
    );
    update_post_meta( $id, '_wp_attachment_image_alt', $gegevens['alt'] );
    update_post_meta( $id, '_sfp_deelbeeld_van', $post->ID . ':' . $sleutel );

    // Het vorige bestand van dit beeld opruimen (alleen het eigen, door de plugin gemaakte bestand).
    if ( '' !== $oud_pad && $oud_pad !== $pad && 0 === strpos( basename( $oud_pad ), 'deelbeeld-' ) && is_file( $oud_pad ) ) {
        wp_delete_file( $oud_pad );
        if ( is_file( sfp_page_config_deelbeeld_klein_pad( $oud_pad ) ) ) {
            wp_delete_file( sfp_page_config_deelbeeld_klein_pad( $oud_pad ) );
        }
    }
    return (int) $id;
}

add_action( 'wp_after_insert_post', 'sfp_page_config_deelbeeld_bij_opslaan', 20, 2 );

/**
 * Bij het opslaan van een bericht: maak of ververs de deelbeelden.
 *
 * @param int     $post_id Bericht.
 * @param WP_Post $post    Bericht.
 */
function sfp_page_config_deelbeeld_bij_opslaan( $post_id, $post ) {
    static $bezig = false;
    if ( $bezig || ! $post instanceof WP_Post ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
        return;
    }
    $types = function_exists( 'sfp_page_config_artikel_posttypes' ) ? sfp_page_config_artikel_posttypes() : array( 'post' );
    if ( ! in_array( $post->post_type, $types, true ) || in_array( $post->post_status, array( 'auto-draft', 'trash', 'inherit' ), true ) ) {
        return;
    }
    $heeft = false !== strpos( $post->post_content, '<!-- wp:sfp/inzicht' ) || false !== strpos( $post->post_content, '<!-- wp:sfp/samenvatting' );
    if ( ! $heeft && ! metadata_exists( 'post', $post_id, '_sfp_deelbeeld' ) ) {
        return;
    }
    $bezig = true;
    try {
        sfp_page_config_deelbeeld_maak( $post_id );
    } catch ( Throwable $e ) {
        sfp_page_config_deelbeeld_fout( 'Fout bij het tekenen: ' . $e->getMessage() );
    } finally {
        $bezig = false;
    }
}

/* =========================================================================
 * Gebruiken
 * ====================================================================== */

/**
 * Het deelbeeld van een blok, als het bestaat en bij de tekst van het blok hoort.
 *
 * @param  int    $post_id  Bericht.
 * @param  string $soort    inzicht of samenvatting.
 * @param  int    $nr       Volgnummer van het blok in het bericht.
 * @param  string $controle Controlewaarde van de tekst van het blok, of '' om niet te controleren.
 * @return array{url: string, breedte: int, hoogte: int, klein: string}|null klein is de URL van de lichtere versie, of ''.
 */
function sfp_page_config_deelbeeld_voor( $post_id, $soort, $nr, $controle = '' ) {
    $bewaard = get_post_meta( $post_id, '_sfp_deelbeeld', true );
    $sleutel = $soort . '-' . (int) $nr;
    if ( ! is_array( $bewaard ) || empty( $bewaard[ $sleutel ]['id'] ) || empty( $bewaard[ $sleutel ]['actueel'] ) ) {
        return null;
    }
    $slot = $bewaard[ $sleutel ];
    if ( '' !== $controle && ( ! isset( $slot['controle'] ) || $slot['controle'] !== $controle ) ) {
        return null;
    }
    $url = wp_get_attachment_url( (int) $slot['id'] );
    if ( ! $url ) {
        return null;
    }
    return array(
        'url'     => $url,
        'breedte' => (int) $slot['breedte'],
        'hoogte'  => (int) $slot['hoogte'],
        'klein'   => ! empty( $slot['klein'] ) ? sfp_page_config_deelbeeld_klein_pad( $url ) : '',
    );
}

add_filter( 'surerank_set_meta', 'sfp_page_config_deelbeeld_og', 20 );

/**
 * Het eerste inzicht is het deelbeeld (og:image) van het artikel, tenzij
 * de redacteur in SureRank zelf een beeld koos.
 *
 * @param  array $meta Meta van SureRank.
 * @return array
 */
function sfp_page_config_deelbeeld_og( $meta ) {
    if ( ! is_array( $meta ) || ! is_singular() || ! empty( $meta['facebook_image_url'] ) ) {
        return $meta;
    }
    $beeld = sfp_page_config_deelbeeld_voor( (int) get_queried_object_id(), 'inzicht', 1 );
    if ( ! $beeld ) {
        return $meta;
    }
    $meta['facebook_image_url']    = $beeld['url'];
    $meta['facebook_image_width']  = $beeld['breedte'];
    $meta['facebook_image_height'] = $beeld['hoogte'];
    if ( empty( $meta['twitter_image_url'] ) ) {
        $meta['twitter_image_url'] = $beeld['url'];
    }
    return $meta;
}

/**
 * De stand voor beheerders: wat de server kan en wat er ontbreekt.
 *
 * @return array
 */
function sfp_page_config_deelbeeld_stand() {
    $fonts = sfp_page_config_deelbeeld_fonts( false );
    return array(
        'gd'           => function_exists( 'imagecreatetruecolor' ),
        'freetype'     => function_exists( 'imagettftext' ),
        'jpeg'         => function_exists( 'imagejpeg' ),
        'werkmap'      => '' !== sfp_page_config_deelbeeld_map(),
        'kleurrollen'  => null !== sfp_page_config_deelbeeld_kleuren(),
        'fonts_aanwezig' => null !== $fonts,
        'laatste_fout' => sfp_page_config_deelbeeld_fout(),
    );
}

add_action( 'rest_api_init', 'sfp_page_config_deelbeeld_rest' );

/**
 * REST: de deelbeelden van een bericht lezen of opnieuw maken.
 *
 *   GET  /sfp/v1/deelbeeld/<id>   wat er staat
 *   POST /sfp/v1/deelbeeld/<id>   opnieuw maken (ook als de tekst gelijk is)
 */
function sfp_page_config_deelbeeld_rest() {
    $mag = function ( WP_REST_Request $verzoek ) {
        return current_user_can( 'edit_post', (int) $verzoek['id'] );
    };
    $lees = function ( WP_REST_Request $verzoek, $verslag = null ) {
        $post_id = (int) $verzoek['id'];
        $bewaard = get_post_meta( $post_id, '_sfp_deelbeeld', true );
        $beelden = array();
        foreach ( is_array( $bewaard ) ? $bewaard : array() as $sleutel => $slot ) {
            $beelden[ $sleutel ] = array(
                'id'      => (int) $slot['id'],
                'url'     => (string) wp_get_attachment_url( (int) $slot['id'] ),
                'actueel' => ! empty( $slot['actueel'] ),
            );
        }
        return rest_ensure_response(
            array(
                'beelden' => $beelden,
                'verslag' => $verslag,
                'stand'   => sfp_page_config_deelbeeld_stand(),
            )
        );
    };
    register_rest_route(
        'sfp/v1',
        '/deelbeeld/(?P<id>\d+)',
        array(
            array(
                'methods'             => 'GET',
                'permission_callback' => $mag,
                'callback'            => $lees,
            ),
            array(
                'methods'             => 'POST',
                'permission_callback' => $mag,
                'callback'            => function ( WP_REST_Request $verzoek ) use ( $lees ) {
                    return $lees( $verzoek, sfp_page_config_deelbeeld_maak( (int) $verzoek['id'], true ) );
                },
            ),
        )
    );
}
