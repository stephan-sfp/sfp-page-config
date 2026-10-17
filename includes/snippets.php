<?php
/**
 * SFP Page Config - Snippetbeheer via REST
 *
 * Laat het serviceaccount claude-api de PHP-snippets van ASE Pro (Code
 * Snippets Manager) lezen, aanmaken, bijwerken en aan- of uitzetten, zonder
 * browser. ASE zelf kan dat niet via REST: het posttype staat op
 * show_in_rest false en de opslaglogica (admin-screens.php) laadt alleen als
 * is_admin() waar is.
 *
 * Een opslag hier doet wat een opslag in de snippet-editor van ASE Pro 9.2.1
 * doet:
 *
 *   1. post_content begint met <?php;
 *   2. postmeta `options`, `code_snippet_description` en `_active`;
 *   3. de code als {id}.php in CSM_UPLOAD_DIR;
 *   4. de controle en md5 van ASE (`_csm_php_validated_file_hash`);
 *   5. de lijst actieve snippets opnieuw opbouwen (rebuild_snippets_tree).
 *
 * Alleen de openbare onderdelen van ASE worden gebruikt. Ontbreekt er een,
 * bijvoorbeeld na een update van ASE, dan weigeren de routes in plaats van
 * half op te slaan.
 *
 * Toegang: het onderdeel Snippetbeheer staat aan (standaard uit) en de
 * gebruiker is claude-api met manage_options. Verwijderen kan hier niet.
 *
 * @package SFP_Page_Config
 * @since   3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Toegang
 * ====================================================================== */

/**
 * De gebruiker die de routes mag gebruiken.
 *
 * @return string
 */
function sfp_page_config_snippets_gebruiker() {
    return (string) apply_filters( 'sfp_page_config_snippets_gebruiker', 'claude-api' );
}

/**
 * Mag dit verzoek de routes gebruiken?
 *
 * @return true|WP_Error
 */
function sfp_page_config_snippets_mag() {
    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'sfp_snippets_niet_ingelogd', 'Log in om snippets te beheren.', array( 'status' => 401 ) );
    }
    if ( ! sfp_page_config_onderdeel_aan( 'snippets' ) ) {
        return new WP_Error( 'sfp_snippets_uit', 'Snippetbeheer staat op deze site uit (SFP Beheer, Onderdelen).', array( 'status' => 403 ) );
    }
    $gebruiker = wp_get_current_user();
    if ( ! current_user_can( 'manage_options' ) || sfp_page_config_snippets_gebruiker() !== (string) $gebruiker->user_login ) {
        return new WP_Error( 'sfp_snippets_geen_toegang', 'Alleen het serviceaccount ' . sfp_page_config_snippets_gebruiker() . ' mag snippets beheren.', array( 'status' => 403 ) );
    }
    return true;
}

/* =========================================================================
 * ASE
 * ====================================================================== */

/**
 * Zijn alle onderdelen van ASE er die de opslag nodig heeft?
 *
 * @return true|WP_Error
 */
function sfp_page_config_snippets_ase() {
    $nodig = array(
        'Code Snippets Manager staat aan' => class_exists( 'Code_Snippets_Manager' ) && function_exists( 'Code_Snippets_Manager' ),
        'posttype asenha_code_snippet'    => post_type_exists( 'asenha_code_snippet' ),
        'CSM_UPLOAD_DIR'                  => defined( 'CSM_UPLOAD_DIR' ),
        'rebuild_snippets_tree'           => class_exists( 'Code_Snippets_Manager' ) && method_exists( 'Code_Snippets_Manager', 'rebuild_snippets_tree' ),
        'update_php_snippet_validated_hash_after_file_write' => class_exists( 'Code_Snippets_Manager' ) && method_exists( 'Code_Snippets_Manager', 'update_php_snippet_validated_hash_after_file_write' ),
    );
    $mist = array_keys( array_filter( $nodig, function ( $er ) {
        return ! $er;
    } ) );
    if ( $mist ) {
        return new WP_Error( 'sfp_snippets_ase_ontbreekt', 'ASE Pro mist wat het snippetbeheer nodig heeft: ' . implode( ', ', $mist ) . '. Er is niets opgeslagen.', array( 'status' => 503 ) );
    }
    return true;
}

/**
 * De optie waarin ASE zijn lijst actieve snippets bewaart.
 *
 * @return string
 */
function sfp_page_config_snippets_extra_optie() {
    return ( defined( 'ASENHA_SLUG_U' ) ? ASENHA_SLUG_U : 'admin_site_enhancements' ) . '_extra';
}

/**
 * De PHP-snippets in de lijst die ASE bij elke aanvraag uitvoert.
 *
 * @return array<int, array> Per post-id.
 */
function sfp_page_config_snippets_boom() {
    $extra = get_option( sfp_page_config_snippets_extra_optie(), array() );
    $php   = is_array( $extra ) && isset( $extra['code_snippets']['php'] ) && is_array( $extra['code_snippets']['php'] ) ? $extra['code_snippets']['php'] : array();
    $uit   = array();
    foreach ( $php as $id => $info ) {
        $uit[ (int) $id ] = is_array( $info ) ? $info : array();
    }
    return $uit;
}

/**
 * Staat de veilige modus van ASE aan? Dan draait geen enkel PHP-snippet.
 *
 * @return bool
 */
function sfp_page_config_snippets_veilige_modus() {
    return defined( 'CSM_SAFE_MODE' ) && CSM_SAFE_MODE;
}

/**
 * Het bestand van een snippet.
 *
 * @param  int    $id   Post-id.
 * @param  string $taal Taal van het snippet.
 * @return string
 */
function sfp_page_config_snippets_bestand( $id, $taal = 'php' ) {
    $map = defined( 'CSM_UPLOAD_DIR' ) ? (string) CSM_UPLOAD_DIR : '';
    return '' === $map ? '' : trailingslashit( $map ) . (int) $id . '.' . preg_replace( '/[^a-z]/', '', (string) $taal );
}

/* =========================================================================
 * Lezen
 * ====================================================================== */

/**
 * De opties van een snippet, met de standaard van ASE voor PHP.
 *
 * @param  int $id Post-id.
 * @return array
 */
function sfp_page_config_snippets_opties( $id ) {
    $opties = get_post_meta( $id, 'options', true );
    return is_array( $opties ) ? $opties : array();
}

/**
 * Een snippet zoals de routes het teruggeven.
 *
 * @param  WP_Post $post Snippet.
 * @param  bool    $code Ook de code meegeven.
 * @return array
 */
function sfp_page_config_snippets_rij( WP_Post $post, $code = false ) {
    $opties  = sfp_page_config_snippets_opties( $post->ID );
    $taal    = isset( $opties['language'] ) ? (string) $opties['language'] : 'css';
    $actief  = 'no' !== get_post_meta( $post->ID, '_active', true );
    $boom    = sfp_page_config_snippets_boom();
    $bestand = sfp_page_config_snippets_bestand( $post->ID, $taal );
    $bestaat = '' !== $bestand && is_file( $bestand );
    $op_disk = $bestaat ? (string) file_get_contents( $bestand ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokaal bestand van ASE.

    $rij = array(
        'id'           => (int) $post->ID,
        'titel'        => html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
        'status'       => (string) $post->post_status,
        'taal'         => $taal,
        'actief'       => $actief,
        'in_lijst'     => 'php' === $taal && isset( $boom[ $post->ID ] ),
        'draait'       => 'php' === $taal && isset( $boom[ $post->ID ] ) && $actief && 'publish' === $post->post_status && $bestaat && ! sfp_page_config_snippets_veilige_modus(),
        'methode'      => isset( $opties['execution_method'] ) ? (string) $opties['execution_method'] : '',
        'hook'         => isset( $opties['execution_location'] ) ? (string) $opties['execution_location'] : '',
        'waar'         => isset( $opties['execution_location_details'] ) ? (string) $opties['execution_location_details'] : '',
        'prioriteit'   => isset( $opties['priority'] ) ? (int) $opties['priority'] : 10,
        'beschrijving' => (string) get_post_meta( $post->ID, 'code_snippet_description', true ),
        'bewerkt'      => (string) $post->post_modified_gmt,
        'bestand'      => array(
            'bestaat' => $bestaat,
            // Het bestand is wat ASE uitvoert; het moet gelijk zijn aan de code in de database.
            'gelijk'  => 'php' === $taal ? $bestaat && $op_disk === (string) $post->post_content : null,
        ),
    );
    if ( $code ) {
        $rij['code'] = (string) $post->post_content;
    }
    return $rij;
}

/**
 * Een snippet ophalen, of een fout.
 *
 * @param  int $id Post-id.
 * @return WP_Post|WP_Error
 */
function sfp_page_config_snippets_post( $id ) {
    $post = get_post( (int) $id );
    if ( ! $post || 'asenha_code_snippet' !== $post->post_type || 'trash' === $post->post_status ) {
        return new WP_Error( 'sfp_snippets_niet_gevonden', 'Dit snippet bestaat niet.', array( 'status' => 404 ) );
    }
    return $post;
}

/* =========================================================================
 * Controleren
 * ====================================================================== */

/**
 * Begin een PHP-snippet met <?php, zoals ASE dat doet.
 *
 * @param  string $code Code.
 * @return string
 */
function sfp_page_config_snippets_normaliseer( $code ) {
    if ( method_exists( 'Code_Snippets_Manager', 'normalize_php_snippet_post_content_for_disk' ) ) {
        return Code_Snippets_Manager::normalize_php_snippet_post_content_for_disk( (string) $code );
    }
    $code = preg_replace( '/^\s*\n/', '', (string) $code );
    return '<?php' === substr( $code, 0, 5 ) ? $code : '<?php' . PHP_EOL . $code;
}

/**
 * Controleer PHP-code voordat hij wordt opgeslagen.
 *
 * Eerst de syntaxis (zonder iets uit te voeren), daarna de controle van ASE
 * op functies en klassen die al bestaan. Bij bijwerken is een naam die ook in
 * de oude code staat geen fout: die bestaat omdat dit snippet zelf al draait.
 *
 * @param  string $code     Code met <?php.
 * @param  string $oud      Huidige code, bij bijwerken.
 * @return true|WP_Error
 */
function sfp_page_config_snippets_controleer( $code, $oud = '' ) {
    try {
        token_get_all( (string) $code, TOKEN_PARSE );
    } catch ( ParseError $fout ) {
        return new WP_Error( 'sfp_snippets_syntax', 'De code bevat een fout op regel ' . (int) $fout->getLine() . ': ' . $fout->getMessage() . '. Er is niets opgeslagen.', array( 'status' => 400 ) );
    }

    if ( ! class_exists( 'ASENHA\Classes\PHP_Validator' ) ) {
        return true;
    }
    $lichaam   = rtrim( ltrim( trim( (string) $code ), '<?php' ), '?>' );
    $validator = new ASENHA\Classes\PHP_Validator( $lichaam );
    $uitslag   = $validator->validate();
    if ( false === $uitslag || ! is_array( $uitslag ) ) {
        return true;
    }
    $melding = isset( $uitslag['message'] ) ? (string) $uitslag['message'] : 'onbekende fout';
    if ( '' !== $oud && preg_match( '/^Cannot redeclare (?:function|class|interface)?\s*([A-Za-z0-9_\\\\]+)\.?$/', $melding, $m ) ) {
        $naam = preg_quote( $m[1], '/' );
        if ( preg_match( '/\b(?:function|class|interface)\s+' . $naam . '\b/i', (string) $oud ) ) {
            return true;
        }
    }
    $regel = isset( $uitslag['line'] ) ? (int) $uitslag['line'] - 1 : 0;
    return new WP_Error( 'sfp_snippets_controle', 'ASE keurt de code af' . ( $regel > 0 ? ' (regel ' . $regel . ')' : '' ) . ': ' . $melding . ' Er is niets opgeslagen.', array( 'status' => 400 ) );
}

/**
 * De invoer van een aanvraag, schoon. Alleen meegestuurde velden komen terug.
 *
 * @param  array $invoer Ruwe invoer.
 * @return array|WP_Error
 */
function sfp_page_config_snippets_invoer( array $invoer ) {
    $uit = array();
    if ( array_key_exists( 'titel', $invoer ) ) {
        $uit['titel'] = sanitize_text_field( (string) $invoer['titel'] );
        if ( '' === $uit['titel'] ) {
            return new WP_Error( 'sfp_snippets_titel', 'Geef het snippet een titel.', array( 'status' => 400 ) );
        }
    }
    if ( array_key_exists( 'code', $invoer ) ) {
        if ( ! is_string( $invoer['code'] ) || '' === trim( $invoer['code'] ) ) {
            return new WP_Error( 'sfp_snippets_code', 'De code is leeg.', array( 'status' => 400 ) );
        }
        $uit['code'] = sfp_page_config_snippets_normaliseer( str_replace( "\r\n", "\n", $invoer['code'] ) );
    }
    if ( array_key_exists( 'status', $invoer ) ) {
        if ( ! in_array( $invoer['status'], array( 'publish', 'draft' ), true ) ) {
            return new WP_Error( 'sfp_snippets_status', 'Status is publish of draft.', array( 'status' => 400 ) );
        }
        $uit['status'] = $invoer['status'];
    }
    if ( array_key_exists( 'actief', $invoer ) ) {
        $uit['actief'] = true === $invoer['actief'] || 1 === $invoer['actief'] || '1' === $invoer['actief'] || 'true' === $invoer['actief'];
    }
    if ( array_key_exists( 'hook', $invoer ) ) {
        $hook = strtolower( trim( (string) $invoer['hook'] ) );
        if ( ! preg_match( '/^[a-z0-9_\-\/\.]{1,100}$/', $hook ) ) {
            return new WP_Error( 'sfp_snippets_hook', 'Geef een geldige hooknaam, bijvoorbeeld plugins_loaded of init.', array( 'status' => 400 ) );
        }
        $uit['hook'] = $hook;
    }
    if ( array_key_exists( 'waar', $invoer ) ) {
        if ( ! in_array( $invoer['waar'], array( 'everywhere', 'admin', 'frontend' ), true ) ) {
            return new WP_Error( 'sfp_snippets_waar', 'Waar is everywhere, admin of frontend.', array( 'status' => 400 ) );
        }
        $uit['waar'] = $invoer['waar'];
    }
    if ( array_key_exists( 'prioriteit', $invoer ) ) {
        $prioriteit = (int) $invoer['prioriteit'];
        if ( $prioriteit < 1 ) {
            return new WP_Error( 'sfp_snippets_prioriteit', 'Prioriteit is een heel getal vanaf 1.', array( 'status' => 400 ) );
        }
        $uit['prioriteit'] = $prioriteit;
    }
    if ( array_key_exists( 'beschrijving', $invoer ) ) {
        $uit['beschrijving'] = wp_kses_post( (string) $invoer['beschrijving'] );
    }
    return $uit;
}

/* =========================================================================
 * Opslaan
 * ====================================================================== */

/**
 * Schrijf de code naar het bestand dat ASE uitvoert en werk de controle, de
 * md5 en de lijst actieve snippets bij.
 *
 * @param  int $id Post-id.
 * @return true|WP_Error
 */
function sfp_page_config_snippets_naar_ase( $id ) {
    $post    = get_post( $id );
    $map     = (string) CSM_UPLOAD_DIR;
    $bestand = sfp_page_config_snippets_bestand( $id, 'php' );
    if ( ! is_dir( $map ) ) {
        wp_mkdir_p( $map );
    }
    if ( ! wp_is_writable( $map ) ) {
        return new WP_Error( 'sfp_snippets_map', 'De map van de snippets is niet schrijfbaar. De code staat in de database, maar niet in het bestand dat ASE uitvoert.', array( 'status' => 500 ) );
    }
    if ( false === file_put_contents( $bestand, (string) $post->post_content ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- zelfde opslag als ASE.
        return new WP_Error( 'sfp_snippets_bestand', 'Het bestand van het snippet kon niet worden geschreven.', array( 'status' => 500 ) );
    }
    // Zonder dit kan PHP de oude versie nog een tijd uit het geheugen uitvoeren.
    if ( function_exists( 'opcache_invalidate' ) ) {
        @opcache_invalidate( $bestand, true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- opcache kan beperkt zijn.
    }
    Code_Snippets_Manager::update_php_snippet_validated_hash_after_file_write( $id, $bestand );
    Code_Snippets_Manager()->rebuild_snippets_tree();
    return true;
}

/**
 * Een PHP-snippet aanmaken of bijwerken.
 *
 * @param  int   $id     Post-id, of 0 voor een nieuw snippet.
 * @param  array $invoer Schone invoer uit sfp_page_config_snippets_invoer().
 * @return int|WP_Error  Post-id.
 */
function sfp_page_config_snippets_bewaar( $id, array $invoer ) {
    $nieuw = 0 === (int) $id;
    $oud   = '';
    if ( ! $nieuw ) {
        $post = sfp_page_config_snippets_post( $id );
        if ( is_wp_error( $post ) ) {
            return $post;
        }
        $opties = sfp_page_config_snippets_opties( $id );
        if ( isset( $opties['language'] ) && 'php' !== $opties['language'] ) {
            return new WP_Error( 'sfp_snippets_taal', 'Alleen PHP-snippets zijn hier te bewerken. Dit snippet is ' . $opties['language'] . '.', array( 'status' => 409 ) );
        }
        $oud = (string) $post->post_content;
    } else {
        foreach ( array( 'titel', 'code' ) as $veld ) {
            if ( ! isset( $invoer[ $veld ] ) ) {
                return new WP_Error( 'sfp_snippets_onvolledig', 'Een nieuw snippet heeft een titel en code nodig.', array( 'status' => 400 ) );
            }
        }
        $opties = array();
    }

    if ( isset( $invoer['code'] ) ) {
        $goed = sfp_page_config_snippets_controleer( $invoer['code'], $oud );
        if ( is_wp_error( $goed ) ) {
            return $goed;
        }
    }

    // De post. Zonder de kses-filters: die zouden PHP-code als HTML schoonmaken.
    $postarr = array( 'post_type' => 'asenha_code_snippet' );
    if ( ! $nieuw ) {
        $postarr['ID'] = (int) $id;
    }
    if ( isset( $invoer['titel'] ) ) {
        $postarr['post_title'] = $invoer['titel'];
    }
    if ( isset( $invoer['code'] ) ) {
        $postarr['post_content'] = $invoer['code'];
    }
    if ( isset( $invoer['status'] ) ) {
        $postarr['post_status'] = $invoer['status'];
    } elseif ( $nieuw ) {
        $postarr['post_status'] = 'publish';
    }

    $kses = false !== has_filter( 'content_save_pre', 'wp_filter_post_kses' );
    if ( $kses ) {
        kses_remove_filters();
    }
    $resultaat = $nieuw ? wp_insert_post( wp_slash( $postarr ), true ) : wp_update_post( wp_slash( $postarr ), true );
    if ( $kses ) {
        kses_init_filters();
    }
    if ( is_wp_error( $resultaat ) ) {
        return new WP_Error( 'sfp_snippets_post', 'Opslaan in de database is niet gelukt: ' . $resultaat->get_error_message(), array( 'status' => 500 ) );
    }
    $id = (int) $resultaat;

    // De opties, met de standaard die ASE voor een PHP-snippet gebruikt.
    $opties = wp_parse_args(
        $opties,
        array(
            'language'                   => 'php',
            'linking'                    => 'external',
            'type'                       => 'none',
            'side'                       => 'sitewide',
            'execution_method'           => 'on_page_load',
            'secure_url_token'           => '',
            'execution_location_type'    => 'hook',
            'execution_location'         => 'plugins_loaded',
            'execution_location_details' => 'everywhere',
            'conditionals'               => array(),
            'priority'                   => 10,
            'compile_scss'               => 'no',
        )
    );
    if ( isset( $invoer['hook'] ) ) {
        $opties['execution_location'] = $invoer['hook'];
    }
    if ( isset( $invoer['waar'] ) ) {
        $opties['execution_location_details'] = $invoer['waar'];
    }
    if ( isset( $invoer['prioriteit'] ) ) {
        $opties['priority'] = $invoer['prioriteit'];
    }
    update_post_meta( $id, 'options', $opties );
    if ( isset( $invoer['beschrijving'] ) ) {
        update_post_meta( $id, 'code_snippet_description', $invoer['beschrijving'] );
    }
    // Een nieuw snippet draait pas als iemand het aanzet.
    if ( isset( $invoer['actief'] ) ) {
        update_post_meta( $id, '_active', $invoer['actief'] ? 'yes' : 'no' );
    } elseif ( $nieuw ) {
        update_post_meta( $id, '_active', 'no' );
    }

    $klaar = sfp_page_config_snippets_naar_ase( $id );
    return is_wp_error( $klaar ) ? $klaar : $id;
}

/**
 * Een snippet aan- of uitzetten, zoals de schakelaar in de lijst van ASE.
 *
 * @param  int  $id  Post-id.
 * @param  bool $aan Aan of uit.
 * @return true|WP_Error
 */
function sfp_page_config_snippets_schakel( $id, $aan ) {
    $post = sfp_page_config_snippets_post( $id );
    if ( is_wp_error( $post ) ) {
        return $post;
    }
    update_post_meta( $post->ID, '_active', $aan ? 'yes' : 'no' );
    Code_Snippets_Manager()->rebuild_snippets_tree();
    return true;
}

/* =========================================================================
 * REST
 * ====================================================================== */

add_action( 'rest_api_init', 'sfp_page_config_snippets_rest' );

/**
 * De routes onder /sfp/v1/snippets.
 */
function sfp_page_config_snippets_rest() {
    $mag = function () {
        $toegang = sfp_page_config_snippets_mag();
        if ( true !== $toegang ) {
            return $toegang;
        }
        return sfp_page_config_snippets_ase();
    };
    $json = function ( WP_REST_Request $verzoek ) {
        $gegevens = $verzoek->get_json_params();
        return is_array( $gegevens ) ? $gegevens : (array) $verzoek->get_body_params();
    };
    $antwoord = function ( $id, $status = 200 ) {
        $post = get_post( (int) $id );
        $uit  = rest_ensure_response( sfp_page_config_snippets_rij( $post, true ) );
        $uit->set_status( $status );
        return $uit;
    };

    // Alle snippets, zonder code.
    register_rest_route(
        'sfp/v1',
        '/snippets',
        array(
            array(
                'methods'             => 'GET',
                'permission_callback' => $mag,
                'callback'            => function () {
                    $posts = get_posts(
                        array(
                            'post_type'        => 'asenha_code_snippet',
                            'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
                            'posts_per_page'   => -1,
                            'orderby'          => 'ID',
                            'order'            => 'ASC',
                            'suppress_filters' => true,
                        )
                    );
                    return rest_ensure_response(
                        array(
                            'ase'           => defined( 'ASENHA_VERSION' ) ? ASENHA_VERSION : '',
                            'map'           => (string) CSM_UPLOAD_DIR,
                            'veilige_modus' => sfp_page_config_snippets_veilige_modus(),
                            'snippets'      => array_map( 'sfp_page_config_snippets_rij', $posts ),
                        )
                    );
                },
            ),
            array(
                'methods'             => 'POST',
                'permission_callback' => $mag,
                'callback'            => function ( WP_REST_Request $verzoek ) use ( $json, $antwoord ) {
                    $invoer = sfp_page_config_snippets_invoer( $json( $verzoek ) );
                    if ( is_wp_error( $invoer ) ) {
                        return $invoer;
                    }
                    $id = sfp_page_config_snippets_bewaar( 0, $invoer );
                    return is_wp_error( $id ) ? $id : $antwoord( $id, 201 );
                },
            ),
        )
    );

    // Eén snippet, met code: lezen en bijwerken.
    register_rest_route(
        'sfp/v1',
        '/snippets/(?P<id>\d+)',
        array(
            array(
                'methods'             => 'GET',
                'permission_callback' => $mag,
                'callback'            => function ( WP_REST_Request $verzoek ) {
                    $post = sfp_page_config_snippets_post( (int) $verzoek['id'] );
                    return is_wp_error( $post ) ? $post : rest_ensure_response( sfp_page_config_snippets_rij( $post, true ) );
                },
            ),
            array(
                'methods'             => 'POST',
                'permission_callback' => $mag,
                'callback'            => function ( WP_REST_Request $verzoek ) use ( $json, $antwoord ) {
                    $invoer = sfp_page_config_snippets_invoer( $json( $verzoek ) );
                    if ( is_wp_error( $invoer ) ) {
                        return $invoer;
                    }
                    $id = sfp_page_config_snippets_bewaar( (int) $verzoek['id'], $invoer );
                    return is_wp_error( $id ) ? $id : $antwoord( $id );
                },
            ),
        )
    );

    // Aan of uit.
    register_rest_route(
        'sfp/v1',
        '/snippets/(?P<id>\d+)/actief',
        array(
            'methods'             => 'POST',
            'permission_callback' => $mag,
            'callback'            => function ( WP_REST_Request $verzoek ) use ( $json, $antwoord ) {
                $gegevens = $json( $verzoek );
                if ( ! array_key_exists( 'aan', $gegevens ) ) {
                    return new WP_Error( 'sfp_snippets_aan', 'Stuur aan: true of aan: false.', array( 'status' => 400 ) );
                }
                $aan   = true === $gegevens['aan'] || 1 === $gegevens['aan'] || '1' === $gegevens['aan'] || 'true' === $gegevens['aan'];
                $klaar = sfp_page_config_snippets_schakel( (int) $verzoek['id'], $aan );
                return is_wp_error( $klaar ) ? $klaar : $antwoord( (int) $verzoek['id'] );
            },
        )
    );
}
