<?php
/**
 * SFP Page Config - Prijstabel (sinds 3.76.0)
 *
 * Eén prijstabel voor het hele netwerk, in SFP Beheer op de bronsite (SFP).
 * De andere sites halen de tabel op, bewaren de laatste versie en rekenen
 * daarmee door als de bron even niet bereikbaar is. Zonder opgehaalde tabel
 * gelden de bedragen uit sfp_prijzen_regels() als vangnet.
 *
 * Bedragen staan in centen, exclusief btw, in euro. Elke regel heeft twee
 * kolommen: nl (de Nederlandse sites) en sws (SpeakwithSteve). Een site leest
 * de kolom uit de instelling prijs_kolom; een lege waarde valt terug op de
 * andere kolom.
 *
 * Besluiten Stephan 16-11-2026: claude/ontwerp-prijstabel-sfp-beheer-2026-11-16.md.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * De regels van de tabel met hun vangnetbedragen (centen; null = leeg).
 *
 * @return array<string, array{groep: string, label: string, eenheid: string, nl: int|null, sws: int|null}>
 */
function sfp_prijzen_regels() {
    $r = function ( $groep, $label, $eenheid, $nl, $sws = null ) {
        return array(
            'groep'   => $groep,
            'label'   => $label,
            'eenheid' => $eenheid,
            'nl'      => null === $nl ? null : (int) $nl,
            'sws'     => null === $sws ? null : (int) $sws,
        );
    };
    return array(
        'open_1'             => $r( 'Open inschrijving', '1 dag', 'cursist', 99500 ),
        'open_2'             => $r( 'Open inschrijving', '2 dagen', 'cursist', 149500 ),
        'open_3'             => $r( 'Open inschrijving', '3 dagen', 'cursist', 199500 ),
        'ic_dagdeel'         => $r( 'Incompany', '1 dagdeel', 'groep', 150000 ),
        'ic_1'               => $r( 'Incompany', '1 dag zonder coaching (SwS: team training per dag, max. 8)', 'groep', 300000, 300000 ),
        'ic_1c'              => $r( 'Incompany', '1 dag incl. coaching', 'groep', 400000 ),
        'ic_2'               => $r( 'Incompany', '2 dagen zonder coaching', 'groep', 600000 ),
        'ic_2c'              => $r( 'Incompany', '2 dagen incl. coaching', 'groep', 700000 ),
        'ic_3c'              => $r( 'Incompany', '3 dagen incl. coaching', 'groep', 900000 ),
        'c_4x2'              => $r( 'Coaching', '4 sessies van 2 uur', 'traject', 220000 ),
        'c_4x4'              => $r( 'Coaching', '4 sessies van 4 uur', 'traject', 380000 ),
        'c_6x4'              => $r( 'Coaching', '6 sessies van 4 uur', 'traject', 540000 ),
        'sws_fast'           => $r( 'Coaching SwS', 'Fast Track, 8 uur in 2 weken', 'traject', null, 350000 ),
        'sws_intensive'      => $r( 'Coaching SwS', 'Intensive, 16 uur in 3 maanden', 'traject', null, 550000 ),
        'sws_transformation' => $r( 'Coaching SwS', 'Transformation, 32 uur in 6 maanden', 'traject', null, 950000 ),
        'begeleiding'        => $r( 'Calculator', 'Persoonlijke begeleiding bij 2 dagen', 'groep', 100000 ),
        'coachblok'          => $r( 'Calculator', 'Extra coachuren, blok van 4 uur', 'deelnemer', 100000 ),
        'clinic'             => $r( 'Calculator', 'Presentation Clinic', 'deelnemer', 25000 ),
        'boek'               => $r( 'Calculator', 'Boek De FASTER-methode', 'stuk', 1995 ),
        'acteur'             => $r( 'Calculator', 'Trainingsacteur', 'dag', 125000 ),
        'terug_dagdeel'      => $r( 'Calculator', 'Terugkomsessie, dagdeel', 'groep', 150000 ),
        'terug_online'       => $r( 'Calculator', 'Terugkomsessie, 2 uur online', 'groep', 75000 ),
        'scan_ind'           => $r( 'Calculator', 'Scan', 'deelnemer', 25000 ),
        'scan_team'          => $r( 'Calculator', 'Teamscan', 'groep', 75000 ),
        'groepsvoordeel'     => $r( 'Calculator', 'Groepsvoordeel vanaf 3 groepen', 'groep', 100000 ),
        'evenement'          => $r( 'Calculator', 'Eigen evenement, per coach per dag', 'dag', 200000, 225000 ),
        'advies_uur'         => $r( 'Advies', 'Adviesconsult', 'uur', 20000 ),
    );
}

/**
 * De kolommen van de tabel.
 *
 * @return array<string, string>
 */
function sfp_prijzen_kolommen() {
    return array(
        'nl'  => 'Nederlandse sites',
        'sws' => 'SpeakwithSteve',
    );
}

/**
 * Netwerknorm voor het adres van de bronsite.
 *
 * @return string
 */
function sfp_prijzen_bron_norm() {
    return 'https://schoolforprofessionals.com/';
}

/**
 * Het adres van de bronsite: de instelling prijsbron, anders de norm.
 *
 * @return string
 */
function sfp_prijzen_bron_url() {
    $url = function_exists( 'sfp_page_config_get_setting' ) ? trim( (string) sfp_page_config_get_setting( 'prijsbron', '' ) ) : '';
    return trailingslashit( '' !== $url ? $url : sfp_prijzen_bron_norm() );
}

/**
 * De host van een adres, zonder www.
 *
 * @param  string $url Adres.
 * @return string
 */
function sfp_prijzen_host( $url ) {
    $host = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_HOST ) );
    return 0 === strpos( $host, 'www.' ) ? substr( $host, 4 ) : $host;
}

/**
 * Is deze site de bron van de tabel?
 *
 * @return bool
 */
function sfp_prijzen_is_bron() {
    return sfp_prijzen_host( home_url( '/' ) ) === sfp_prijzen_host( sfp_prijzen_bron_url() );
}

/**
 * De kolom die deze site leest: instelling prijs_kolom, anders nl.
 *
 * @return string
 */
function sfp_prijzen_kolom() {
    $k = function_exists( 'sfp_page_config_get_setting' ) ? (string) sfp_page_config_get_setting( 'prijs_kolom', '' ) : '';
    return array_key_exists( $k, sfp_prijzen_kolommen() ) ? $k : 'nl';
}

/**
 * Schoon een tabel op: alleen bekende sleutels en kolommen, bedragen als
 * gehele centen van 0 tot 10 miljoen, of null.
 *
 * @param  mixed $ruw Tabel (sleutel => [kolom => centen]).
 * @return array<string, array<string, int|null>>
 */
function sfp_prijzen_schoon( $ruw ) {
    $uit = array();
    $ruw = is_array( $ruw ) ? $ruw : array();
    foreach ( sfp_prijzen_regels() as $sleutel => $regel ) {
        $rij = isset( $ruw[ $sleutel ] ) && is_array( $ruw[ $sleutel ] ) ? $ruw[ $sleutel ] : array();
        foreach ( array_keys( sfp_prijzen_kolommen() ) as $kolom ) {
            $w = array_key_exists( $kolom, $rij ) ? $rij[ $kolom ] : null;
            if ( null === $w || '' === $w || ! is_numeric( $w ) ) {
                $uit[ $sleutel ][ $kolom ] = null;
                continue;
            }
            $uit[ $sleutel ][ $kolom ] = max( 0, min( 1000000000, (int) round( (float) $w ) ) );
        }
    }
    return $uit;
}

/**
 * De vangnettabel uit de code.
 *
 * @return array<string, array<string, int|null>>
 */
function sfp_prijzen_vangnet() {
    $uit = array();
    foreach ( sfp_prijzen_regels() as $sleutel => $regel ) {
        foreach ( array_keys( sfp_prijzen_kolommen() ) as $kolom ) {
            $uit[ $sleutel ][ $kolom ] = $regel[ $kolom ];
        }
    }
    return $uit;
}

/**
 * De tabel die deze site gebruikt, met herkomst.
 *
 * @return array{regels: array, bijgewerkt: int, opgehaald: int, herkomst: string}
 */
function sfp_prijzen_stand() {
    if ( isset( $GLOBALS['sfp_prijzen_stand'] ) ) {
        return $GLOBALS['sfp_prijzen_stand'];
    }
    $GLOBALS['sfp_prijzen_stand'] = sfp_prijzen_stand_lees();
    return $GLOBALS['sfp_prijzen_stand'];
}

/**
 * Lees de tabel uit de database (zonder geheugen).
 *
 * @return array{regels: array, bijgewerkt: int, opgehaald: int, herkomst: string}
 */
function sfp_prijzen_stand_lees() {
    if ( sfp_prijzen_is_bron() ) {
        $eigen = get_option( 'sfp_prijzen', array() );
        if ( is_array( $eigen ) && ! empty( $eigen['regels'] ) ) {
            return array(
                'regels'     => sfp_prijzen_schoon( $eigen['regels'] ),
                'bijgewerkt' => isset( $eigen['bijgewerkt'] ) ? (int) $eigen['bijgewerkt'] : 0,
                'opgehaald'  => 0,
                'herkomst'   => 'bron',
            );
        }
    } else {
        $kopie = get_option( 'sfp_prijzen_kopie', array() );
        if ( is_array( $kopie ) && ! empty( $kopie['regels'] ) ) {
            return array(
                'regels'     => sfp_prijzen_schoon( $kopie['regels'] ),
                'bijgewerkt' => isset( $kopie['bijgewerkt'] ) ? (int) $kopie['bijgewerkt'] : 0,
                'opgehaald'  => isset( $kopie['opgehaald'] ) ? (int) $kopie['opgehaald'] : 0,
                'herkomst'   => 'kopie',
            );
        }
    }
    return array(
        'regels'     => sfp_prijzen_vangnet(),
        'bijgewerkt' => 0,
        'opgehaald'  => 0,
        'herkomst'   => 'vangnet',
    );
}

/**
 * Vergeet de tabel in het geheugen van dit verzoek (na opslaan of ophalen).
 *
 * @return void
 */
function sfp_prijzen_vergeet() {
    unset( $GLOBALS['sfp_prijzen_stand'] );
}

/**
 * Eén bedrag in centen, of null als de regel leeg is.
 *
 * @param  string      $sleutel Regel.
 * @param  string|null $kolom   Kolom; null = de kolom van deze site.
 * @return int|null
 */
function sfp_prijs( $sleutel, $kolom = null ) {
    $kolom  = null === $kolom ? sfp_prijzen_kolom() : (string) $kolom;
    $regels = sfp_prijzen_stand()['regels'];
    if ( ! isset( $regels[ $sleutel ] ) ) {
        return null;
    }
    $rij = $regels[ $sleutel ];
    if ( isset( $rij[ $kolom ] ) && null !== $rij[ $kolom ] ) {
        return (int) $rij[ $kolom ];
    }
    // Lege kolom: terugval op de andere kolom.
    foreach ( $rij as $w ) {
        if ( null !== $w ) {
            return (int) $w;
        }
    }
    return null;
}

/**
 * Eén bedrag in hele euro's (afgerond), of de terugval als de regel leeg is.
 *
 * @param  string $sleutel  Regel.
 * @param  int    $terugval Bedrag in euro's.
 * @return int
 */
function sfp_prijs_euro( $sleutel, $terugval = 0 ) {
    $c = sfp_prijs( $sleutel );
    return null === $c ? (int) $terugval : (int) round( $c / 100 );
}

/**
 * Bedrag als tekst volgens de taal van de site: € 3.500 (nl, pt) of €3,500 (en).
 *
 * @param  int $centen Bedrag.
 * @return string
 */
function sfp_prijs_tekst( $centen ) {
    $taal   = function_exists( 'sfp_page_config_taal' ) ? (string) sfp_page_config_taal() : substr( (string) get_locale(), 0, 2 );
    $heel   = 0 === $centen % 100;
    $dec    = $heel ? 0 : 2;
    if ( 'en' === $taal ) {
        return '€' . number_format( $centen / 100, $dec, '.', ',' );
    }
    return '€ ' . number_format( $centen / 100, $dec, ',', '.' );
}

/* =========================================================================
 * Opslaan op de bron, ophalen op de andere sites
 * ====================================================================== */

/**
 * Bewaar de tabel op de bron en geef de andere sites een seintje.
 *
 * @param  mixed $ruw Tabel.
 * @return true|WP_Error
 */
function sfp_prijzen_bewaar( $ruw ) {
    if ( ! sfp_prijzen_is_bron() ) {
        return new WP_Error( 'sfp_prijzen_geen_bron', 'De prijzen bewerk je op ' . sfp_prijzen_host( sfp_prijzen_bron_url() ) . '.', array( 'status' => 403 ) );
    }
    update_option(
        'sfp_prijzen',
        array(
            'regels'     => sfp_prijzen_schoon( $ruw ),
            'bijgewerkt' => time(),
        ),
        false
    );
    sfp_prijzen_vergeet();
    sfp_prijzen_seintje();
    return true;
}

/**
 * Laat de sites uit SFP Beheer, Netwerk weten dat er een nieuwe tabel is.
 * Zonder antwoord af te wachten; het uurlijkse ophalen vangt een gemiste op.
 *
 * @return void
 */
function sfp_prijzen_seintje() {
    $labels = function_exists( 'sfp_page_config_netwerk_instelling' ) ? sfp_page_config_netwerk_instelling() : array();
    $eigen  = sfp_prijzen_host( home_url( '/' ) );
    $gehad  = array();
    foreach ( $labels as $l ) {
        $host = sfp_prijzen_host( $l['url'] );
        if ( '' === $host || $host === $eigen || isset( $gehad[ $host ] ) ) {
            continue;
        }
        $gehad[ $host ] = true;
        wp_remote_post(
            'https://' . $host . '/wp-json/sfp/v1/prijzen/ververs',
            array(
                'timeout'  => 3,
                'blocking' => false,
            )
        );
    }
}

/**
 * Haal de tabel op bij de bron en bewaar hem.
 *
 * @return true|WP_Error
 */
function sfp_prijzen_ophalen() {
    if ( sfp_prijzen_is_bron() ) {
        return true;
    }
    $antwoord = wp_remote_get(
        sfp_prijzen_bron_url() . 'wp-json/sfp/v1/prijzen',
        array(
            'timeout'    => 10,
            'user-agent' => 'SFP Page Config/' . ( defined( 'SFP_PAGE_CONFIG_VERSION' ) ? SFP_PAGE_CONFIG_VERSION : '' ) . '; ' . home_url( '/' ),
        )
    );
    if ( is_wp_error( $antwoord ) ) {
        return $antwoord;
    }
    $code = (int) wp_remote_retrieve_response_code( $antwoord );
    $data = json_decode( (string) wp_remote_retrieve_body( $antwoord ), true );
    if ( 200 !== $code || ! is_array( $data ) || empty( $data['regels'] ) || 'bron' !== ( $data['herkomst'] ?? '' ) ) {
        return new WP_Error( 'sfp_prijzen_ophalen', 'De bron gaf geen bruikbare tabel (status ' . $code . ').' );
    }
    update_option(
        'sfp_prijzen_kopie',
        array(
            'regels'     => sfp_prijzen_schoon( $data['regels'] ),
            'bijgewerkt' => isset( $data['bijgewerkt'] ) ? (int) $data['bijgewerkt'] : 0,
            'opgehaald'  => time(),
        ),
        false
    );
    sfp_prijzen_vergeet();
    return true;
}

/**
 * Elk uur ophalen.
 *
 * @return void
 */
function sfp_prijzen_plan() {
    if ( ! wp_next_scheduled( 'sfp_prijzen_uur' ) ) {
        wp_schedule_event( time() + 300, 'hourly', 'sfp_prijzen_uur' );
    }
}
add_action( 'init', 'sfp_prijzen_plan' );
add_action( 'sfp_prijzen_uur', 'sfp_prijzen_ophalen' );

/* =========================================================================
 * REST
 * ====================================================================== */

/**
 * Routes: de tabel lezen (openbaar), verversen na een seintje (openbaar,
 * hooguit eens per minuut), bewaren en nu ophalen (beheerders).
 *
 * @return void
 */
function sfp_prijzen_rest() {
    register_rest_route(
        'sfp/v1',
        '/prijzen',
        array(
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => function () {
                $s = sfp_prijzen_stand();
                $r = rest_ensure_response(
                    array(
                        'regels'     => $s['regels'],
                        'labels'     => array_map(
                            function ( $r ) {
                                return array(
                                    'groep'   => $r['groep'],
                                    'label'   => $r['label'],
                                    'eenheid' => $r['eenheid'],
                                );
                            },
                            sfp_prijzen_regels()
                        ),
                        'bijgewerkt' => $s['bijgewerkt'],
                        'herkomst'   => $s['herkomst'],
                        'valuta'     => 'EUR',
                        'btw'        => 'excl',
                    )
                );
                $r->header( 'Cache-Control', 'no-store' );
                $r->header( 'Access-Control-Allow-Origin', '*' );
                return $r;
            },
        )
    );
    register_rest_route(
        'sfp/v1',
        '/prijzen/ververs',
        array(
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => function () {
                if ( get_transient( 'sfp_prijzen_ververs' ) ) {
                    return rest_ensure_response( array( 'ok' => true, 'wacht' => true ) );
                }
                set_transient( 'sfp_prijzen_ververs', 1, MINUTE_IN_SECONDS );
                $uit = sfp_prijzen_ophalen();
                return rest_ensure_response( array( 'ok' => ! is_wp_error( $uit ) ) );
            },
        )
    );
    register_rest_route(
        'sfp/v1',
        '/beheer/prijzen',
        array(
            'methods'             => 'POST',
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'callback'            => function ( WP_REST_Request $verzoek ) {
                $d   = $verzoek->get_json_params();
                $uit = sfp_prijzen_bewaar( is_array( $d ) && isset( $d['regels'] ) ? $d['regels'] : array() );
                if ( is_wp_error( $uit ) ) {
                    return $uit;
                }
                return rest_ensure_response( array( 'stand' => sfp_page_config_beheer_stand() ) );
            },
        )
    );
    register_rest_route(
        'sfp/v1',
        '/beheer/prijzen/ophalen',
        array(
            'methods'             => 'POST',
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'callback'            => function () {
                $uit = sfp_prijzen_ophalen();
                if ( is_wp_error( $uit ) ) {
                    return new WP_Error( 'sfp_prijzen_ophalen', $uit->get_error_message(), array( 'status' => 502 ) );
                }
                return rest_ensure_response( array( 'stand' => sfp_page_config_beheer_stand() ) );
            },
        )
    );
}
add_action( 'rest_api_init', 'sfp_prijzen_rest' );

/* =========================================================================
 * Korte code en SFP Beheer
 * ====================================================================== */

/**
 * [sfp_prijs sleutel="sws_fast"]: het bedrag uit de tabel, in de kolom van
 * deze site. Lege regel: niets.
 *
 * @param  array $atts Attributen.
 * @return string
 */
function sfp_prijzen_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'sleutel' => '',
            'kolom'   => '',
        ),
        $atts,
        'sfp_prijs'
    );
    $kolom = array_key_exists( $atts['kolom'], sfp_prijzen_kolommen() ) ? $atts['kolom'] : null;
    $c     = sfp_prijs( sanitize_key( $atts['sleutel'] ), $kolom );
    return null === $c ? '' : esc_html( sfp_prijs_tekst( $c ) );
}
if ( ! shortcode_exists( 'sfp_prijs' ) ) {
    add_shortcode( 'sfp_prijs', 'sfp_prijzen_shortcode' );
}

/**
 * Trainingspagina's met een eigen prijs per aantal dagen die afwijkt van de
 * tabel (open_1, open_2, open_3).
 *
 * @return array<int, array{id: int, titel: string, dagen: int, eigen: int, tabel: int}>
 */
function sfp_prijzen_afwijkingen() {
    if ( ! function_exists( 'sfp_page_config_cursusinfo' ) ) {
        return array();
    }
    $ids = get_posts(
        array(
            'post_type'      => 'page',
            'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
            'meta_key'       => 'sfp_cursusinfo',
            'fields'         => 'ids',
            'posts_per_page' => 200,
            'no_found_rows'  => true,
        )
    );
    $uit = array();
    foreach ( $ids as $id ) {
        $info = sfp_page_config_cursusinfo( $id );
        foreach ( $info['prijzen'] as $dagen => $eigen ) {
            $tabel = sfp_prijs( 'open_' . (int) $dagen );
            if ( null === $tabel || (int) $eigen * 100 !== $tabel ) {
                $uit[] = array(
                    'id'    => (int) $id,
                    'titel' => get_the_title( $id ),
                    'dagen' => (int) $dagen,
                    'eigen' => (int) $eigen,
                    'tabel' => null === $tabel ? 0 : (int) round( $tabel / 100 ),
                );
            }
        }
    }
    return $uit;
}

/**
 * Wat SFP Beheer, Onderdelen, Prijzen nodig heeft.
 *
 * @return array
 */
function sfp_prijzen_beheer() {
    $s     = sfp_prijzen_stand();
    $oud   = 'kopie' === $s['herkomst'] && $s['opgehaald'] && ( time() - $s['opgehaald'] ) > DAY_IN_SECONDS;
    $tijd  = function ( $t ) {
        return $t ? wp_date( 'j-n-Y H:i', $t ) : '';
    };
    return array(
        'regels'     => sfp_prijzen_regels(),
        'waarden'    => $s['regels'],
        'kolommen'   => sfp_prijzen_kolommen(),
        'kolom'      => sfp_prijzen_kolom(),
        'bron'       => sfp_prijzen_is_bron(),
        'bron_host'  => sfp_prijzen_host( sfp_prijzen_bron_url() ),
        'herkomst'   => $s['herkomst'],
        'bijgewerkt' => $tijd( $s['bijgewerkt'] ),
        'opgehaald'  => $tijd( $s['opgehaald'] ),
        'oud'        => $oud || 'vangnet' === $s['herkomst'] && ! sfp_prijzen_is_bron(),
        'afwijkingen' => sfp_prijzen_afwijkingen(),
    );
}
