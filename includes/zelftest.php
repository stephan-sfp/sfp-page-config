<?php
/**
 * SFP Page Config - Zelftest: kern (map, instellingen, testpakket)
 *
 * De zelftest is een onderdeel van de plugin, per site aan of uit (standaard
 * uit). De module levert de techniek; de inhoud per label is een testpakket
 * dat in SFP Beheer of via REST wordt ingelezen, geen code in de repo.
 *
 * Een testpakket bestaat uit:
 *
 *   manifest.json  structuur (delen, scorende vragen, scoretabel, lengtes),
 *                  toegestane CRM-waarden, geldigheid van de pdf-link,
 *                  kleurrollen en fonts van het pdf-blad;
 *   inhoud.json    alle teksten per taal;
 *   motor.js       de rekenregel van het label (draait in de browser);
 *   label.js       CRM-velden, notitie en het resultaat op het eindscherm;
 *   blad.html      het pdf-blad (optioneel bij inlezen; zonder blad geen pdf).
 *
 * Bestanden staan in een eigen map onder uploads met een willekeurige naam,
 * afgesloten voor bezoekers. Daarin ook de pdf's.
 *
 * @package SFP_Page_Config
 * @since   3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Map
 * ====================================================================== */

/**
 * De werkmap van de zelftest, met slash aan het eind. Maakt hem aan als hij
 * er nog niet is, met een .htaccess die alles weigert en een lege index.php.
 *
 * @param  string $sub Submap (pakket/<test>/huidig, pdf), of ''.
 * @return string      Pad, of '' als de map niet te maken is.
 */
function sfp_zelftest_map( $sub = '' ) {
    $geheim = (string) get_option( 'sfp_zelftest_map', '' );
    if ( ! preg_match( '/^[a-f0-9]{24}$/', $geheim ) ) {
        $geheim = bin2hex( random_bytes( 12 ) );
        update_option( 'sfp_zelftest_map', $geheim, false );
    }
    $basis = '';
    foreach ( sfp_zelftest_map_plekken( $geheim ) as $plek ) {
        if ( is_dir( $plek ) ) {
            $basis = $plek;
            break;
        }
    }
    if ( '' === $basis ) {
        foreach ( sfp_zelftest_map_plekken( $geheim ) as $plek ) {
            if ( is_writable( dirname( untrailingslashit( $plek ) ) ) && wp_mkdir_p( $plek ) ) {
                $basis = $plek;
                break;
            }
        }
    }
    if ( '' === $basis ) {
        return '';
    }
    if ( ! file_exists( $basis . '.htaccess' ) ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- eigen werkmap.
        file_put_contents( $basis . '.htaccess', "# SFP Page Config: geen toegang voor bezoekers.\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n" );
    }
    if ( ! file_exists( $basis . 'index.php' ) ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- eigen werkmap.
        file_put_contents( $basis . 'index.php', "<?php\n// Stilte.\n" );
    }
    if ( '' === $sub ) {
        return $basis;
    }
    $sub = trim( preg_replace( '#[^a-z0-9/_-]#', '', strtolower( $sub ) ), '/' );
    $pad = $basis . $sub . '/';
    if ( ! is_dir( $pad ) && ! wp_mkdir_p( $pad ) ) {
        return '';
    }
    // Een lege index.php in elke submap, voor servers die .htaccess niet volgen.
    $tussen = $basis;
    foreach ( explode( '/', $sub ) as $deel ) {
        $tussen .= $deel . '/';
        if ( is_dir( $tussen ) && ! file_exists( $tussen . 'index.php' ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- eigen werkmap.
            file_put_contents( $tussen . 'index.php', "<?php\n// Stilte.\n" );
        }
    }
    return $pad;
}

/**
 * Waar de werkmap mag staan, in volgorde van voorkeur: naast de map van
 * WordPress (buiten de webroot, als die schrijfbaar is), anders onder
 * uploads. In beide gevallen met een willekeurige naam en afgesloten.
 *
 * @param  string $geheim Willekeurig deel van de naam.
 * @return string[]       Paden met slash aan het eind.
 */
function sfp_zelftest_map_plekken( $geheim ) {
    $plekken = array( trailingslashit( dirname( untrailingslashit( ABSPATH ) ) ) . 'sfp-zelftest-' . $geheim . '/' );
    $uploads = wp_upload_dir( null, false );
    if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
        $plekken[] = trailingslashit( $uploads['basedir'] ) . 'sfp-zelftest-' . $geheim . '/';
    }
    return (array) apply_filters( 'sfp_zelftest_map_plekken', $plekken, $geheim );
}

/**
 * Verwijder een map met alles erin. Alleen binnen de werkmap.
 *
 * @param  string $pad Map.
 * @return void
 */
function sfp_zelftest_map_weg( $pad ) {
    $basis = sfp_zelftest_map();
    if ( '' === $basis || 0 !== strpos( wp_normalize_path( $pad ), wp_normalize_path( $basis ) ) || ! is_dir( $pad ) ) {
        return;
    }
    $items = scandir( $pad );
    foreach ( (array) $items as $item ) {
        if ( '.' === $item || '..' === $item ) {
            continue;
        }
        $vol = trailingslashit( $pad ) . $item;
        if ( is_dir( $vol ) ) {
            sfp_zelftest_map_weg( $vol );
        } else {
            wp_delete_file( $vol );
        }
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- eigen werkmap.
    @rmdir( $pad );
}

/* =========================================================================
 * Instellingen
 * ====================================================================== */

/**
 * Geldige naam van een test: kleine letters, cijfers en streepje.
 *
 * @param  mixed $test Ruwe naam.
 * @return string      Naam, of ''.
 */
function sfp_zelftest_naam( $test ) {
    $test = strtolower( trim( (string) $test ) );
    return preg_match( '/^[a-z0-9][a-z0-9-]{0,39}$/', $test ) ? $test : '';
}

/**
 * Alle instellingen van de zelftest. Niet automatisch geladen.
 *
 * Vorm: array( 'tests' => array( <test> => array(...) ), 'cloudflare' => array( 'account' => '', 'token' => '' ) ).
 *
 * @return array
 */
function sfp_zelftest_instellingen_alle() {
    $alle = get_option( 'sfp_zelftest', array() );
    $alle = is_array( $alle ) ? $alle : array();
    if ( ! isset( $alle['tests'] ) || ! is_array( $alle['tests'] ) ) {
        $alle['tests'] = array();
    }
    if ( ! isset( $alle['cloudflare'] ) || ! is_array( $alle['cloudflare'] ) ) {
        $alle['cloudflare'] = array();
    }
    return $alle;
}

/**
 * De instellingen van één test, aangevuld met de standaardwaarden.
 *
 * @param  string $test Naam van de test.
 * @return array
 */
function sfp_zelftest_instellingen( $test ) {
    $alle  = sfp_zelftest_instellingen_alle();
    $eigen = isset( $alle['tests'][ $test ] ) && is_array( $alle['tests'][ $test ] ) ? $alle['tests'][ $test ] : array();
    $standaard = array(
        'testpagina'       => 0,
        'logo'             => 0,
        'pdf_pad'          => '',
        'webhook_start'    => '',
        'webhook_afgerond' => '',
        'proefmodus'       => '1',
        // Sinds 3.6.0: de tekst van de oproep (blok Zelftest-oproep en de
        // promokaart van een pijler). Leeg: de naam van de test en de vaste knoptekst.
        'cta_kop'          => '',
        'cta_zin'          => '',
        'cta_knop'         => '',
        // Sinds 3.11.0: per extra taal van het pakket een eigen testpagina en
        // pdf-adres: array( 'en' => array( 'testpagina' => 0, 'pdf_pad' => '' ) ).
        // De eerste taal gebruikt testpagina en pdf_pad hierboven.
        'talen'            => array(),
        // Sinds 3.13.3: vragen en antwoorden husselen (netwerknorm: aan).
        // Aan: de kernvragen wisselen van plek, hun antwoorden en die van de
        // profielvragen wisselen van volgorde, en er staat geen bloknaam boven
        // een vraag. Uit: vaste volgorde met bloknaam.
        'husselen'         => '1',
    );
    // Alleen bekende sleutels: een oude sleutel (deel_pad tot 3.4.0-beta.4) valt weg.
    return array_merge( $standaard, array_intersect_key( $eigen, $standaard ) );
}

/**
 * Bewaar instellingen van een test. Alleen meegestuurde sleutels veranderen.
 * Een leeg webhookveld laat de bewaarde waarde staan; '-' wist hem.
 *
 * @param  string $test        Naam.
 * @param  array  $wijzigingen Ruwe waarden.
 * @return true|WP_Error
 */
function sfp_zelftest_instellingen_bewaar( $test, array $wijzigingen ) {
    $alle = sfp_zelftest_instellingen_alle();
    $nu   = sfp_zelftest_instellingen( $test );

    if ( array_key_exists( 'testpagina', $wijzigingen ) ) {
        $nu['testpagina'] = absint( $wijzigingen['testpagina'] );
    }
    if ( array_key_exists( 'logo', $wijzigingen ) ) {
        // Het logo op de testpagina: een afbeelding uit de mediabibliotheek.
        $logo       = absint( $wijzigingen['logo'] );
        // Een afbeelding of een SVG (scherp op elke grootte, sinds 3.5.2).
        $nu['logo'] = ( $logo > 0 && ( wp_attachment_is_image( $logo ) || 'image/svg+xml' === get_post_mime_type( $logo ) ) ) ? $logo : 0;
    }
    if ( array_key_exists( 'pdf_pad', $wijzigingen ) ) {
        $pad = sfp_zelftest_pad_schoon( $wijzigingen['pdf_pad'] );
        if ( '' === $pad && '' !== trim( (string) $wijzigingen['pdf_pad'] ) ) {
            return new WP_Error( 'sfp_zelftest_pad', 'Een adres bestaat uit kleine letters, cijfers, streepjes en schuine strepen, zonder domein.', array( 'status' => 400 ) );
        }
        $nu['pdf_pad'] = $pad;
    }
    foreach ( array( 'webhook_start', 'webhook_afgerond' ) as $sleutel ) {
        if ( ! array_key_exists( $sleutel, $wijzigingen ) ) {
            continue;
        }
        $ruw = trim( (string) $wijzigingen[ $sleutel ] );
        if ( '' === $ruw ) {
            continue;
        }
        if ( '-' === $ruw ) {
            $nu[ $sleutel ] = '';
            continue;
        }
        $url = esc_url_raw( $ruw, array( 'https' ) );
        if ( '' === $url || 0 !== strpos( $url, 'https://' ) ) {
            return new WP_Error( 'sfp_zelftest_webhook', 'Een webhookadres begint met https://.', array( 'status' => 400 ) );
        }
        $nu[ $sleutel ] = $url;
    }
    foreach ( array( 'cta_kop' => 90, 'cta_zin' => 220, 'cta_knop' => 40 ) as $sleutel => $max ) {
        if ( array_key_exists( $sleutel, $wijzigingen ) ) {
            $tekst          = trim( sanitize_text_field( (string) $wijzigingen[ $sleutel ] ) );
            $nu[ $sleutel ] = function_exists( 'mb_substr' ) ? mb_substr( $tekst, 0, $max ) : substr( $tekst, 0, $max );
        }
    }
    if ( array_key_exists( 'talen', $wijzigingen ) && is_array( $wijzigingen['talen'] ) ) {
        $talen = array();
        foreach ( $wijzigingen['talen'] as $taal => $waarden ) {
            $taal = sfp_zelftest_taalcode( $taal );
            if ( '' === $taal || ! is_array( $waarden ) ) {
                continue;
            }
            $pad = sfp_zelftest_pad_schoon( isset( $waarden['pdf_pad'] ) ? $waarden['pdf_pad'] : '' );
            if ( '' === $pad && isset( $waarden['pdf_pad'] ) && '' !== trim( (string) $waarden['pdf_pad'] ) ) {
                return new WP_Error( 'sfp_zelftest_pad', 'Een adres bestaat uit kleine letters, cijfers, streepjes en schuine strepen, zonder domein.', array( 'status' => 400 ) );
            }
            $talen[ $taal ] = array(
                'testpagina' => absint( isset( $waarden['testpagina'] ) ? $waarden['testpagina'] : 0 ),
                'pdf_pad'    => $pad,
            );
        }
        $nu['talen'] = $talen;
    }
    foreach ( array( 'proefmodus', 'husselen' ) as $sleutel ) {
        if ( array_key_exists( $sleutel, $wijzigingen ) ) {
            $w              = $wijzigingen[ $sleutel ];
            $nu[ $sleutel ] = ( true === $w || '1' === (string) $w || 'true' === $w ) ? '1' : '0';
        }
    }

    $alle['tests'][ $test ] = $nu;
    update_option( 'sfp_zelftest', $alle, false );
    // WP Rocket neemt de uitsluiting van de pdf-link pas over na een nieuw configuratiebestand.
    if ( function_exists( 'rocket_generate_config_file' ) ) {
        rocket_generate_config_file();
    }
    return true;
}

/**
 * Een adres op de site als pad: kleine letters, cijfers, streepjes en
 * schuine strepen, zonder domein en zonder schuine streep aan de randen.
 *
 * @param  mixed $ruw Invoer.
 * @return string
 */
function sfp_zelftest_pad_schoon( $ruw ) {
    $pad = strtolower( trim( (string) $ruw ) );
    $pad = preg_replace( '#^https?://[^/]+#', '', $pad );
    $pad = trim( $pad, '/' );
    return preg_match( '#^[a-z0-9-]+(?:/[a-z0-9-]+){0,4}$#', $pad ) ? $pad : '';
}

/**
 * De gegevens van Cloudflare Browser Run.
 *
 * @return array{account: string, token: string}
 */
function sfp_zelftest_cloudflare() {
    $alle = sfp_zelftest_instellingen_alle();
    $cf   = $alle['cloudflare'];
    return array(
        'account' => isset( $cf['account'] ) ? (string) $cf['account'] : '',
        'token'   => isset( $cf['token'] ) ? (string) $cf['token'] : '',
    );
}

/**
 * Bewaar account-ID en token van Cloudflare. Een leeg veld laat de bewaarde
 * waarde staan; '-' wist hem. Het token is nooit terug te lezen.
 *
 * @param  array $wijzigingen account, token.
 * @return true|WP_Error
 */
function sfp_zelftest_cloudflare_bewaar( array $wijzigingen ) {
    $alle = sfp_zelftest_instellingen_alle();
    $cf   = sfp_zelftest_cloudflare();
    if ( isset( $wijzigingen['account'] ) ) {
        $ruw = strtolower( trim( (string) $wijzigingen['account'] ) );
        if ( '-' === $ruw ) {
            $cf['account'] = '';
        } elseif ( '' !== $ruw ) {
            if ( ! preg_match( '/^[a-f0-9]{32}$/', $ruw ) ) {
                return new WP_Error( 'sfp_zelftest_cloudflare', 'Het account-ID van Cloudflare bestaat uit 32 tekens (0-9, a-f).', array( 'status' => 400 ) );
            }
            $cf['account'] = $ruw;
        }
    }
    if ( isset( $wijzigingen['token'] ) ) {
        $ruw = trim( (string) $wijzigingen['token'] );
        if ( '-' === $ruw ) {
            $cf['token'] = '';
        } elseif ( '' !== $ruw ) {
            if ( ! preg_match( '/^[A-Za-z0-9_\-]{20,200}$/', $ruw ) ) {
                return new WP_Error( 'sfp_zelftest_cloudflare', 'Dit lijkt geen token van Cloudflare.', array( 'status' => 400 ) );
            }
            $cf['token'] = $ruw;
        }
    }
    $alle['cloudflare'] = $cf;
    update_option( 'sfp_zelftest', $alle, false );
    // Pdf's die wachtten omdat Cloudflare nog niet was ingesteld, weer in de wachtrij.
    if ( '' !== $cf['account'] && '' !== $cf['token'] && function_exists( 'sfp_zelftest_uitgesteld_hervat' ) ) {
        sfp_zelftest_uitgesteld_hervat();
    }
    return true;
}

/* =========================================================================
 * Testpakket
 * ====================================================================== */

/**
 * De bestanden van een pakket. 'nodig' moet er zijn bij inlezen.
 *
 * @return array<string, bool> bestand => nodig
 */
function sfp_zelftest_pakket_bestanden() {
    return array(
        'manifest.json' => true,
        'inhoud.json'   => true,
        'motor.js'      => true,
        'label.js'      => true,
        'blad.html'     => false,
    );
}

/**
 * De tests die op deze site een pakket hebben.
 *
 * @return string[]
 */
function sfp_zelftest_tests() {
    $map = sfp_zelftest_map( 'pakket' );
    if ( '' === $map ) {
        return array();
    }
    $tests = array();
    foreach ( (array) scandir( $map ) as $item ) {
        if ( '' !== sfp_zelftest_naam( $item ) && is_readable( $map . $item . '/huidig/manifest.json' ) ) {
            $tests[] = $item;
        }
    }
    sort( $tests );
    return $tests;
}

/**
 * Lees een pakket (huidige versie). Per verzoek bewaard.
 *
 * @param  string $test    Naam van de test.
 * @param  bool   $vergeet Leeg het geheugen van dit verzoek (na inlezen of terugzetten).
 * @return array|null      manifest, inhoud, motor, label, blad (string of ''), map, ingelezen; null zonder pakket.
 */
function sfp_zelftest_pakket( $test, $vergeet = false ) {
    static $geheugen = array();
    if ( $vergeet ) {
        $geheugen = array();
        return null;
    }
    $test = sfp_zelftest_naam( $test );
    if ( '' === $test ) {
        return null;
    }
    if ( array_key_exists( $test, $geheugen ) ) {
        return $geheugen[ $test ];
    }
    $basis = sfp_zelftest_map( 'pakket' );
    $map   = '' === $basis ? '' : $basis . $test . '/huidig/';
    if ( '' === $map || ! is_readable( $map . 'manifest.json' ) ) {
        $geheugen[ $test ] = null;
        return null;
    }
    $lees     = function ( $naam ) use ( $map ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokaal bestand.
        return is_readable( $map . $naam ) ? (string) file_get_contents( $map . $naam ) : '';
    };
    $manifest = json_decode( $lees( 'manifest.json' ), true );
    $inhoud   = json_decode( $lees( 'inhoud.json' ), true );
    if ( ! is_array( $manifest ) || ! is_array( $inhoud ) ) {
        $geheugen[ $test ] = null;
        return null;
    }
    $geheugen[ $test ] = array(
        'test'      => $test,
        'manifest'  => $manifest,
        'inhoud'    => $inhoud,
        'motor'     => $lees( 'motor.js' ),
        'label'     => $lees( 'label.js' ),
        'blad'      => $lees( 'blad.html' ),
        'map'       => $map,
        'ingelezen' => (int) filemtime( $map . 'manifest.json' ),
    );
    return $geheugen[ $test ];
}

/**
 * Controleer de bestanden van een pakket voordat iets wordt vervangen.
 *
 * @param  array $bestanden naam => inhoud.
 * @return array{fouten: string[], let_op: string[], manifest: array|null}
 */
function sfp_zelftest_pakket_controle( array $bestanden ) {
    $fouten = array();
    $let_op = array();

    foreach ( sfp_zelftest_pakket_bestanden() as $naam => $nodig ) {
        if ( $nodig && ( ! isset( $bestanden[ $naam ] ) || '' === trim( (string) $bestanden[ $naam ] ) ) ) {
            $fouten[] = $naam . ' ontbreekt.';
        }
    }
    foreach ( array_keys( $bestanden ) as $naam ) {
        if ( ! array_key_exists( $naam, sfp_zelftest_pakket_bestanden() ) ) {
            $let_op[] = $naam . ' hoort niet in een pakket en wordt overgeslagen.';
        }
    }
    if ( $fouten ) {
        return array( 'fouten' => $fouten, 'let_op' => $let_op, 'manifest' => null );
    }

    $manifest = json_decode( (string) $bestanden['manifest.json'], true );
    $inhoud   = json_decode( (string) $bestanden['inhoud.json'], true );
    if ( ! is_array( $manifest ) ) {
        $fouten[] = 'manifest.json is geen geldige JSON.';
    }
    if ( ! is_array( $inhoud ) ) {
        $fouten[] = 'inhoud.json is geen geldige JSON.';
    }
    if ( $fouten ) {
        return array( 'fouten' => $fouten, 'let_op' => $let_op, 'manifest' => null );
    }

    if ( '' === sfp_zelftest_naam( isset( $manifest['test'] ) ? $manifest['test'] : '' ) ) {
        $fouten[] = 'manifest.json: "test" ontbreekt of is ongeldig.';
    }
    if ( empty( $manifest['pakket_versie'] ) || ! is_string( $manifest['pakket_versie'] ) ) {
        $fouten[] = 'manifest.json: "pakket_versie" ontbreekt.';
    }
    if ( empty( $manifest['code_voorvoegsel'] ) || ! preg_match( '/^[A-Z]{2,6}$/', (string) $manifest['code_voorvoegsel'] ) ) {
        $fouten[] = 'manifest.json: "code_voorvoegsel" moet 2 tot 6 hoofdletters zijn.';
    }
    $talen = isset( $manifest['talen'] ) && is_array( $manifest['talen'] ) ? $manifest['talen'] : array();
    if ( ! $talen ) {
        $fouten[] = 'manifest.json: "talen" ontbreekt.';
    }

    // Vragen in de inhoud.
    $vragen = isset( $inhoud['vragen'] ) && is_array( $inhoud['vragen'] ) ? $inhoud['vragen'] : array();
    $nrs    = array();
    foreach ( $vragen as $i => $vraag ) {
        $nr = isset( $vraag['nr'] ) ? (int) $vraag['nr'] : 0;
        if ( $nr < 1 ) {
            $fouten[] = 'inhoud.json: vraag ' . ( $i + 1 ) . ' heeft geen nummer.';
            continue;
        }
        $nrs[] = $nr;
        foreach ( $talen as $taal ) {
            $tekst = isset( $vraag[ $taal ] ) && is_array( $vraag[ $taal ] ) ? $vraag[ $taal ] : array();
            if ( empty( $tekst['v'] ) || empty( $tekst['o'] ) || ! is_array( $tekst['o'] ) ) {
                $fouten[] = 'inhoud.json: vraag ' . $nr . ' mist tekst of opties in "' . $taal . '".';
            } elseif ( count( $tekst['o'] ) > 9 ) {
                $fouten[] = 'inhoud.json: vraag ' . $nr . ' heeft meer dan 9 opties.';
            }
        }
    }
    if ( count( $nrs ) !== count( array_unique( $nrs ) ) ) {
        $fouten[] = 'inhoud.json: een vraagnummer komt dubbel voor.';
    }

    // Lengtes en delen in het manifest.
    $lengtes = isset( $manifest['lengtes'] ) && is_array( $manifest['lengtes'] ) ? $manifest['lengtes'] : array();
    if ( ! isset( $lengtes['long'] ) ) {
        $fouten[] = 'manifest.json: "lengtes.long" ontbreekt.';
    }
    foreach ( $lengtes as $naam => $lengte ) {
        if ( ! in_array( $naam, array( 'long', 'short' ), true ) ) {
            $fouten[] = 'manifest.json: lengte "' . $naam . '" is onbekend (long of short).';
            continue;
        }
        $lijst = isset( $lengte['vragen'] ) && is_array( $lengte['vragen'] ) ? array_map( 'intval', $lengte['vragen'] ) : array();
        if ( ! $lijst ) {
            $fouten[] = 'manifest.json: lengte ' . $naam . ' heeft geen vragen.';
        }
        $vreemd = array_diff( $lijst, $nrs );
        if ( $vreemd ) {
            $fouten[] = 'manifest.json: lengte ' . $naam . ' noemt vragen die niet bestaan: ' . implode( ', ', $vreemd ) . '.';
        }
    }
    $scoretabel = isset( $manifest['scoretabel'] ) && is_array( $manifest['scoretabel'] ) ? $manifest['scoretabel'] : array();
    $scorend    = array();
    foreach ( ( isset( $manifest['delen'] ) && is_array( $manifest['delen'] ) ? $manifest['delen'] : array() ) as $deel ) {
        foreach ( ( isset( $deel['scorend'] ) && is_array( $deel['scorend'] ) ? $deel['scorend'] : array() ) as $q ) {
            $scorend[] = (int) $q;
            if ( ! isset( $scoretabel[ (string) $q ] ) ) {
                $fouten[] = 'manifest.json: scorende vraag ' . (int) $q . ' staat niet in de scoretabel.';
            }
        }
    }
    if ( array_diff( array_map( 'intval', array_keys( $scoretabel ) ), $scorend ) ) {
        $let_op[] = 'manifest.json: de scoretabel noemt vragen die in geen deel scorend zijn.';
    }

    // CRM.
    $afgerond = isset( $manifest['crm']['afgerond'] ) && is_array( $manifest['crm']['afgerond'] ) ? $manifest['crm']['afgerond'] : array();
    if ( ! $afgerond ) {
        $fouten[] = 'manifest.json: "crm.afgerond" (toegestane waarden per veld) ontbreekt.';
    }
    foreach ( $afgerond as $veld => $waarden ) {
        if ( ! preg_match( '/^[a-z_]{2,40}$/', (string) $veld ) || ! is_array( $waarden ) || ! $waarden ) {
            $fouten[] = 'manifest.json: CRM-veld "' . $veld . '" is ongeldig.';
        }
    }
    $dagen = isset( $manifest['geldigheid_dagen'] ) ? (int) $manifest['geldigheid_dagen'] : 0;
    if ( $dagen < 1 || $dagen > 730 ) {
        $fouten[] = 'manifest.json: "geldigheid_dagen" moet tussen 1 en 730 liggen.';
    }

    // Teksten die de module zelf gebruikt.
    $nodig = array(
        'start'        => array( 'kop', 'p3_knop', 'p4_knop', 'p6_voornaam', 'p6_email', 'p8_nieuwsbrief', 'p9_avg', 'fout_naam', 'fout_email' ),
        'vragenscherm' => array( 'voortgang', 'vorige', 'volgende', 'naarResultaat', 'meerdere', 'kiesEen' ),
        'resultaat'    => array( 'inMailbox', 'p10', 'p10_spam', 'p10_corrigeer', 'p10_corrigeerKnop', 'p7_knop', 'openHier', 'openHierKnop' ),
        'verlopen'     => array( 'kop', 'tekst', 'knop' ),
        'onbekend'     => array( 'kop', 'tekst', 'knop' ),
        'module'       => array( 'wachten_kop', 'wachten_tekst', 'fout_versturen', 'fout_te_vaak' ),
    );
    foreach ( $nodig as $groep => $sleutels ) {
        foreach ( $sleutels as $sleutel ) {
            foreach ( $talen as $taal ) {
                if ( ! isset( $inhoud[ $groep ][ $sleutel ][ $taal ] ) || ! is_string( $inhoud[ $groep ][ $sleutel ][ $taal ] ) ) {
                    $fouten[] = 'inhoud.json: tekst ' . $groep . '.' . $sleutel . ' ontbreekt in "' . $taal . '".';
                }
            }
        }
    }
    $plaatshouders = substr_count( (string) $bestanden['inhoud.json'], '[TEKST:' );
    if ( $plaatshouders ) {
        $let_op[] = $plaatshouders . ' tekst(en) staan nog als plaatshouder [TEKST: ...].';
    }

    // Het blad.
    if ( empty( $bestanden['blad.html'] ) ) {
        $let_op[] = 'Geen blad.html: zonder blad maakt de site geen pdf.';
    } else {
        $kleuren = isset( $manifest['blad']['kleuren'] ) && is_array( $manifest['blad']['kleuren'] ) ? array_map( 'strtoupper', array_keys( $manifest['blad']['kleuren'] ) ) : array();
        preg_match_all( '/#[0-9A-Fa-f]{6}\b/', (string) $bestanden['blad.html'], $treffers );
        $los = array_diff( array_unique( array_map( 'strtoupper', $treffers[0] ) ), $kleuren );
        if ( $los ) {
            $let_op[] = 'blad.html: kleuren zonder rol blijven zoals ze zijn: ' . implode( ', ', $los ) . '.';
        }
        // De voettekst: de Chrome van Cloudflare tekent de margevakken van het blad niet.
        $voet = isset( $manifest['blad']['voet'] ) ? $manifest['blad']['voet'] : null;
        if ( null === $voet ) {
            if ( preg_match( '/@(top|bottom|left|right)-[a-z-]+\s*\{/i', (string) $bestanden['blad.html'] ) ) {
                $let_op[] = 'blad.html heeft een kop- of voettekst in de paginamarge; die tekent Cloudflare niet. Zet hem in manifest.blad.voet.';
            }
        } elseif ( ! is_array( $voet ) || empty( $voet['tekst'] ) || ! is_array( $voet['tekst'] ) ) {
            $fouten[] = 'manifest.blad.voet heeft geen tekst per taal.';
        } else {
            $zonder = array_diff( $talen, array_keys( $voet['tekst'] ) );
            if ( $zonder ) {
                $let_op[] = 'manifest.blad.voet: geen tekst voor ' . implode( ', ', $zonder ) . '; de eerste taal wordt gebruikt.';
            }
        }
    }

    return array( 'fouten' => array_values( array_unique( $fouten ) ), 'let_op' => $let_op, 'manifest' => $fouten ? null : $manifest );
}

/**
 * Lees een pakket in. De huidige versie schuift door naar 'vorig'; bij een
 * fout blijft alles zoals het was.
 *
 * @param  string $test      Naam van de test (moet gelijk zijn aan manifest.test).
 * @param  array  $bestanden naam => inhoud.
 * @return array|WP_Error    controle-uitkomst bij succes.
 */
function sfp_zelftest_pakket_inlezen( $test, array $bestanden ) {
    $test = sfp_zelftest_naam( $test );
    if ( '' === $test ) {
        return new WP_Error( 'sfp_zelftest_test', 'Ongeldige naam van de test.', array( 'status' => 400 ) );
    }
    $controle = sfp_zelftest_pakket_controle( $bestanden );
    if ( $controle['fouten'] ) {
        return new WP_Error( 'sfp_zelftest_pakket', 'Het pakket is niet ingelezen.', array( 'status' => 400, 'fouten' => $controle['fouten'], 'let_op' => $controle['let_op'] ) );
    }
    if ( $test !== $controle['manifest']['test'] ) {
        return new WP_Error( 'sfp_zelftest_pakket', 'Het manifest hoort bij test "' . $controle['manifest']['test'] . '", niet bij "' . $test . '".', array( 'status' => 400 ) );
    }

    $basis = sfp_zelftest_map( 'pakket/' . $test );
    if ( '' === $basis ) {
        return new WP_Error( 'sfp_zelftest_map', 'De werkmap van de zelftest is niet te maken.', array( 'status' => 500 ) );
    }
    $nieuw = $basis . 'nieuw/';
    sfp_zelftest_map_weg( $nieuw );
    if ( ! wp_mkdir_p( $nieuw ) ) {
        return new WP_Error( 'sfp_zelftest_map', 'De werkmap van de zelftest is niet te maken.', array( 'status' => 500 ) );
    }
    foreach ( sfp_zelftest_pakket_bestanden() as $naam => $nodig ) {
        if ( ! isset( $bestanden[ $naam ] ) || '' === (string) $bestanden[ $naam ] ) {
            continue;
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- eigen werkmap.
        if ( false === file_put_contents( $nieuw . $naam, (string) $bestanden[ $naam ] ) ) {
            sfp_zelftest_map_weg( $nieuw );
            return new WP_Error( 'sfp_zelftest_map', 'Schrijven in de werkmap mislukte.', array( 'status' => 500 ) );
        }
    }

    sfp_zelftest_map_weg( $basis . 'vorig/' );
    if ( is_dir( $basis . 'huidig/' ) ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- eigen werkmap.
        rename( $basis . 'huidig', $basis . 'vorig' );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- eigen werkmap.
    if ( ! rename( $basis . 'nieuw', $basis . 'huidig' ) ) {
        if ( is_dir( $basis . 'vorig/' ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- eigen werkmap.
            rename( $basis . 'vorig', $basis . 'huidig' );
        }
        return new WP_Error( 'sfp_zelftest_map', 'Het pakket kon niet op zijn plek worden gezet; de vorige versie staat er nog.', array( 'status' => 500 ) );
    }

    sfp_zelftest_pakket( '', true );
    return $controle;
}

/**
 * Zet de vorige versie van een pakket terug (en de huidige wordt de vorige).
 *
 * @param  string $test Naam.
 * @return true|WP_Error
 */
function sfp_zelftest_pakket_terug( $test ) {
    $test  = sfp_zelftest_naam( $test );
    $basis = '' === $test ? '' : sfp_zelftest_map( 'pakket/' . $test );
    if ( '' === $basis || ! is_readable( $basis . 'vorig/manifest.json' ) ) {
        return new WP_Error( 'sfp_zelftest_pakket', 'Er is geen vorige versie.', array( 'status' => 404 ) );
    }
    sfp_zelftest_map_weg( $basis . 'wissel/' );
    // phpcs:disable WordPress.WP.AlternativeFunctions.rename_rename -- eigen werkmap.
    rename( $basis . 'huidig', $basis . 'wissel' );
    rename( $basis . 'vorig', $basis . 'huidig' );
    rename( $basis . 'wissel', $basis . 'vorig' );
    // phpcs:enable
    sfp_zelftest_pakket( '', true );
    return true;
}

/**
 * Bestanden uit een zip met een pakket (alleen de bekende namen, ook als ze
 * in een map in de zip staan).
 *
 * @param  string $pad Pad naar de zip.
 * @return array|WP_Error naam => inhoud.
 */
function sfp_zelftest_pakket_uit_zip( $pad ) {
    if ( ! class_exists( 'ZipArchive' ) ) {
        return new WP_Error( 'sfp_zelftest_zip', 'Deze server kan geen zip openen. Lees het pakket in via REST.', array( 'status' => 500 ) );
    }
    $zip = new ZipArchive();
    if ( true !== $zip->open( $pad ) ) {
        return new WP_Error( 'sfp_zelftest_zip', 'Dit is geen leesbare zip.', array( 'status' => 400 ) );
    }
    $bestanden = array();
    for ( $i = 0; $i < $zip->numFiles; $i++ ) {
        $naam = basename( (string) $zip->getNameIndex( $i ) );
        if ( array_key_exists( $naam, sfp_zelftest_pakket_bestanden() ) ) {
            $stat = $zip->statIndex( $i );
            if ( is_array( $stat ) && (int) $stat['size'] > 8 * MB_IN_BYTES ) {
                $zip->close();
                return new WP_Error( 'sfp_zelftest_zip', $naam . ' is groter dan 8 MB.', array( 'status' => 400 ) );
            }
            $inhoud = $zip->getFromIndex( $i );
            if ( false !== $inhoud ) {
                $bestanden[ $naam ] = $inhoud;
            }
        }
    }
    $zip->close();
    return $bestanden;
}

/* =========================================================================
 * Teksten uit het pakket
 * ====================================================================== */

/**
 * Een geldige taalcode (nl, en, pt, pt-br), of ''.
 *
 * @param  mixed $taal Ruwe code.
 * @return string
 */
function sfp_zelftest_taalcode( $taal ) {
    $taal = strtolower( trim( (string) $taal ) );
    return preg_match( '/^[a-z]{2}(-[a-z]{2,4})?$/', $taal ) ? $taal : '';
}

/**
 * De taal van dit verzoek of van de rij die nu wordt afgehandeld (sinds 3.11.0).
 * Zet hem met een taalcode, lees hem zonder argument; '' wist hem.
 *
 * @param  string|null $taal Nieuwe taal, of null om te lezen.
 * @return string
 */
function sfp_zelftest_taal_context( $taal = null ) {
    static $nu = '';
    if ( null !== $taal ) {
        $nu = sfp_zelftest_taalcode( $taal );
    }
    return $nu;
}

/**
 * De taal van een rij (bewaard bij de start in extra), of ''.
 *
 * @param  array $rij Rij.
 * @return string
 */
function sfp_zelftest_rij_taal( array $rij ) {
    $extra = isset( $rij['extra'] ) ? json_decode( (string) $rij['extra'], true ) : null;
    return is_array( $extra ) && isset( $extra['taal'] ) ? sfp_zelftest_taalcode( $extra['taal'] ) : '';
}

/**
 * De talen van een pakket.
 *
 * @param  array $pakket Pakket.
 * @return string[]
 */
function sfp_zelftest_pakket_talen( array $pakket ) {
    return isset( $pakket['manifest']['talen'] ) && is_array( $pakket['manifest']['talen'] ) ? array_values( array_map( 'strval', $pakket['manifest']['talen'] ) ) : array( 'nl' );
}

/**
 * De taal waarin een test de bezoeker aanspreekt: de taal van het verzoek of
 * de rij als het pakket die heeft (sinds 3.11.0), anders de taal van de site
 * als het pakket die heeft, anders de eerste taal van het pakket.
 *
 * @param  array $pakket Pakket.
 * @return string
 */
function sfp_zelftest_taal( array $pakket ) {
    $talen = sfp_zelftest_pakket_talen( $pakket );
    $nu    = sfp_zelftest_taal_context();
    if ( '' !== $nu && in_array( $nu, $talen, true ) ) {
        return $nu;
    }
    $site  = function_exists( 'sfp_page_config_taal' ) ? sfp_page_config_taal() : 'nl';
    foreach ( $talen as $taal ) {
        if ( 0 === strpos( (string) $taal, $site ) ) {
            return (string) $taal;
        }
    }
    return (string) reset( $talen );
}

/**
 * Een tekst uit inhoud.json, bijvoorbeeld 'verlopen.kop'.
 *
 * @param  array  $pakket Pakket.
 * @param  string $pad    groep.sleutel.
 * @return string
 */
function sfp_zelftest_tekst( array $pakket, $pad ) {
    $taal  = sfp_zelftest_taal( $pakket );
    $deel  = isset( $pakket['inhoud'] ) ? $pakket['inhoud'] : array();
    foreach ( explode( '.', $pad ) as $stap ) {
        if ( ! is_array( $deel ) || ! isset( $deel[ $stap ] ) ) {
            return '';
        }
        $deel = $deel[ $stap ];
    }
    if ( is_array( $deel ) ) {
        return isset( $deel[ $taal ] ) ? (string) $deel[ $taal ] : '';
    }
    return is_string( $deel ) ? $deel : '';
}

/**
 * Het adres van de pdf-link van een test.
 *
 * @param  string $test  Naam.
 * @param  string $soort pdf (de enige route).
 * @param  array  $query Queryparameters.
 * @return string        Adres, of '' als het pad niet is ingesteld.
 */
function sfp_zelftest_adres( $test, $soort = 'pdf', array $query = array() ) {
    $pad = sfp_zelftest_inst_taal( $test )['pdf_pad'];
    if ( '' === $pad ) {
        return '';
    }
    return add_query_arg( array_map( 'rawurlencode', $query ), home_url( '/' . $pad . '/' ) );
}

/**
 * Het adres van de testpagina.
 *
 * @param  string $test Naam.
 * @return string
 */
function sfp_zelftest_testpagina_url( $test ) {
    $id = (int) sfp_zelftest_inst_taal( $test )['testpagina'];
    if ( $id && 'publish' === get_post_status( $id ) ) {
        return (string) get_permalink( $id );
    }
    return home_url( '/' );
}

/* =========================================================================
 * Meer talen op één site (sinds 3.11.0)
 * ====================================================================== */

/**
 * Testpagina en pdf-adres voor een taal. De eerste taal van het pakket (en
 * elke taal zonder eigen instelling) gebruikt de gewone velden.
 *
 * @param  string      $test Naam.
 * @param  string|null $taal Taal; null voor de taal van het verzoek of de rij.
 * @return array{testpagina:int,pdf_pad:string,taal:string}
 */
function sfp_zelftest_inst_taal( $test, $taal = null ) {
    $inst = sfp_zelftest_instellingen( $test );
    $taal = null === $taal ? sfp_zelftest_taal_context() : sfp_zelftest_taalcode( $taal );
    $eigen = '' !== $taal && isset( $inst['talen'][ $taal ] ) && is_array( $inst['talen'][ $taal ] ) ? $inst['talen'][ $taal ] : null;
    if ( $eigen && ( ! empty( $eigen['testpagina'] ) || ! empty( $eigen['pdf_pad'] ) ) ) {
        return array(
            'testpagina' => ! empty( $eigen['testpagina'] ) ? (int) $eigen['testpagina'] : (int) $inst['testpagina'],
            'pdf_pad'    => ! empty( $eigen['pdf_pad'] ) ? (string) $eigen['pdf_pad'] : (string) $inst['pdf_pad'],
            'taal'       => $taal,
        );
    }
    return array( 'testpagina' => (int) $inst['testpagina'], 'pdf_pad' => (string) $inst['pdf_pad'], 'taal' => '' );
}

/**
 * Alle pdf-adressen van een test, per taal ('' is de eerste taal).
 *
 * @param  string $test Naam.
 * @return array<string, string> taal => pad
 */
function sfp_zelftest_pdf_paden( $test ) {
    $inst  = sfp_zelftest_instellingen( $test );
    $paden = array();
    if ( '' !== $inst['pdf_pad'] ) {
        $paden[''] = $inst['pdf_pad'];
    }
    foreach ( (array) $inst['talen'] as $taal => $w ) {
        if ( is_array( $w ) && ! empty( $w['pdf_pad'] ) ) {
            $paden[ (string) $taal ] = (string) $w['pdf_pad'];
        }
    }
    return $paden;
}

/**
 * Alle testpagina's van een test, per taal ('' is de eerste taal).
 *
 * @param  string $test Naam.
 * @return array<string, int> taal => pagina-ID
 */
function sfp_zelftest_testpaginas( $test ) {
    $inst    = sfp_zelftest_instellingen( $test );
    $paginas = array();
    if ( (int) $inst['testpagina'] > 0 ) {
        $paginas[''] = (int) $inst['testpagina'];
    }
    foreach ( (array) $inst['talen'] as $taal => $w ) {
        if ( is_array( $w ) && ! empty( $w['testpagina'] ) ) {
            $paginas[ (string) $taal ] = (int) $w['testpagina'];
        }
    }
    return $paginas;
}

/**
 * Een datum in de taal van de test. Is dat de taal van de site, dan zoals
 * altijd; anders even in de taal van de test (maandnamen).
 *
 * @param  string $formaat Datumformaat.
 * @param  int    $ts      Tijdstip.
 * @param  array  $pakket  Pakket.
 * @return string
 */
function sfp_zelftest_datum( $formaat, $ts, array $pakket ) {
    $taal = sfp_zelftest_taal( $pakket );
    $site = function_exists( 'sfp_page_config_taal' ) ? sfp_page_config_taal() : 'nl';
    if ( 0 === strpos( $taal, $site ) || ! function_exists( 'switch_to_locale' ) ) {
        return (string) wp_date( $formaat, $ts );
    }
    $locales = array( 'nl' => 'nl_NL', 'en' => 'en_US', 'pt' => 'pt_BR', 'pt-br' => 'pt_BR', 'pt-pt' => 'pt_PT' );
    $doel    = isset( $locales[ $taal ] ) ? $locales[ $taal ] : ( isset( $locales[ substr( $taal, 0, 2 ) ] ) ? $locales[ substr( $taal, 0, 2 ) ] : '' );
    if ( '' === $doel ) {
        return (string) wp_date( $formaat, $ts );
    }
    $gewisseld = switch_to_locale( $doel );
    $uit       = (string) wp_date( $formaat, $ts );
    if ( $gewisseld ) {
        restore_previous_locale();
    }
    return $uit;
}
