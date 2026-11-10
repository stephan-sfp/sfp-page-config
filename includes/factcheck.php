<?php
/**
 * Fact-checked-paneel (sinds 3.44.0)
 *
 * Het label Fact-checked in de kopkaart van een artikel en in de
 * makersregel van een begrip opent een paneel met de controleur (foto,
 * naam, rol), de datum van de laatste controle, de geschiedenis van
 * controles en inhoudelijke herzieningen, en de link naar de redactionele
 * verantwoording.
 *
 * Besluiten Stephan 10-11-2026 (register factcheck-nina-kopkaart):
 *   - de datum vult de controleur zelf in, nooit automatisch;
 *   - zonder vastgelegde controle blijft het label een link naar de
 *     redactionele verantwoording (er is wel gecontroleerd, alleen niet
 *     vastgelegd);
 *   - na een inhoudelijke herziening blijft het label staan; de
 *     geschiedenis toont de herziening;
 *   - de controleur is kiesbaar; niet alleen Nina mag controleren.
 *
 * Opslag per bericht of begrip: post meta sfp_factcheck, een lijst van
 * { datum: JJJJ-MM-DD, door: gebruikersnummer }. De eerdere waarden van
 * Inhoudelijk herzien op (sfp_herzien) bewaart de plugin in
 * sfp_herzien_log, zodat de geschiedenis ze kan tonen.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Laatst gekozen controleur op deze site: de standaard in editor en beheer. */
const SFP_PAGE_CONFIG_FACTCHECK_DOOR = 'sfp_page_config_factcheck_door';

/**
 * De soorten inhoud met een paneel: artikelen en, als het onderdeel aan
 * staat, begrippen.
 *
 * @return string[]
 */
function sfp_page_config_factcheck_types() {
    $types = array( 'post' );
    if ( post_type_exists( 'begrippen' ) && function_exists( 'sfp_page_config_begrip_aan' ) && sfp_page_config_begrip_aan() ) {
        $types[] = 'begrippen';
    }
    return $types;
}

add_action( 'init', 'sfp_page_config_factcheck_meta', 13 );

/**
 * Registreer het veld met de controles.
 */
function sfp_page_config_factcheck_meta() {
    foreach ( array( 'post', 'begrippen' ) as $type ) {
        if ( ! post_type_exists( $type ) ) {
            continue;
        }
        register_post_meta(
            $type,
            'sfp_factcheck',
            array(
                'type'              => 'array',
                'single'            => true,
                'default'           => array(),
                'show_in_rest'      => array(
                    'schema' => array(
                        'type'  => 'array',
                        'items' => array(
                            'type'                 => 'object',
                            'properties'           => array(
                                'datum' => array( 'type' => 'string' ),
                                'door'  => array( 'type' => 'integer' ),
                            ),
                            'additionalProperties' => false,
                        ),
                    ),
                ),
                'sanitize_callback' => 'sfp_page_config_factcheck_schoon',
                'auth_callback'     => function ( $toegestaan, $sleutel, $post_id ) {
                    return current_user_can( 'edit_post', $post_id );
                },
            )
        );
    }
}

/**
 * Maak een lijst controles schoon: geldige datum (niet in de toekomst),
 * een bestaande gebruiker, geen dubbele regels, oudste eerst.
 *
 * @param  mixed $lijst Ruwe lijst.
 * @return array
 */
function sfp_page_config_factcheck_schoon( $lijst ) {
    if ( ! is_array( $lijst ) ) {
        return array();
    }
    $morgen = wp_date( 'Y-m-d', time() + DAY_IN_SECONDS );
    $uit    = array();
    foreach ( $lijst as $regel ) {
        if ( is_object( $regel ) ) {
            $regel = (array) $regel;
        }
        if ( ! is_array( $regel ) ) {
            continue;
        }
        $datum = isset( $regel['datum'] ) ? trim( (string) $regel['datum'] ) : '';
        $door  = isset( $regel['door'] ) ? (int) $regel['door'] : 0;
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $datum ) || ! checkdate( (int) substr( $datum, 5, 2 ), (int) substr( $datum, 8, 2 ), (int) substr( $datum, 0, 4 ) ) ) {
            continue;
        }
        // Een controle ligt nooit in de toekomst (een dag speling voor tijdzones).
        if ( $datum > $morgen ) {
            continue;
        }
        if ( $door <= 0 || ! get_userdata( $door ) ) {
            continue;
        }
        $uit[ $datum . '|' . $door ] = array(
            'datum' => $datum,
            'door'  => $door,
        );
    }
    ksort( $uit );
    return array_slice( array_values( $uit ), -50 );
}

/**
 * De vastgelegde controles van een bericht of begrip, oudste eerst.
 *
 * @param  WP_Post|int $post Bericht of begrip.
 * @return array
 */
function sfp_page_config_factcheck_controles( $post ) {
    $post = get_post( $post );
    if ( ! $post || ! in_array( $post->post_type, array( 'post', 'begrippen' ), true ) ) {
        return array();
    }
    return sfp_page_config_factcheck_schoon( get_post_meta( $post->ID, 'sfp_factcheck', true ) );
}

/**
 * De laatste controle, of null zonder vastgelegde controle.
 *
 * @param  WP_Post|int $post Bericht of begrip.
 * @return array|null datum, door
 */
function sfp_page_config_factcheck_laatste( $post ) {
    $controles = sfp_page_config_factcheck_controles( $post );
    return $controles ? end( $controles ) : null;
}

/* =========================================================================
 * Geschiedenis van Inhoudelijk herzien
 * ====================================================================== */

add_action( 'update_postmeta', 'sfp_page_config_factcheck_herzien_bewaar', 10, 4 );

/**
 * Bewaar de vorige waarde van Inhoudelijk herzien op voordat hij wordt
 * overschreven, zodat de geschiedenis ook eerdere herzieningen toont.
 *
 * @param int    $meta_id Nummer van de meta.
 * @param int    $post_id Bericht.
 * @param string $sleutel Sleutel.
 * @param mixed  $waarde  Nieuwe waarde.
 */
function sfp_page_config_factcheck_herzien_bewaar( $meta_id, $post_id, $sleutel, $waarde ) {
    if ( 'sfp_herzien' !== $sleutel ) {
        return;
    }
    $oud = (string) get_post_meta( $post_id, 'sfp_herzien', true );
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $oud ) || $oud === (string) $waarde ) {
        return;
    }
    $log = get_post_meta( $post_id, 'sfp_herzien_log', true );
    $log = is_array( $log ) ? $log : array();
    if ( ! in_array( $oud, $log, true ) ) {
        $log[] = $oud;
        sort( $log );
        update_post_meta( $post_id, 'sfp_herzien_log', array_slice( $log, -50 ) );
    }
}

/**
 * De geschiedenis van een bericht of begrip: controles, inhoudelijke
 * herzieningen en de publicatie, nieuwste eerst. Een herziening op of voor
 * de publicatiedag telt niet (zoals Bijgewerkt in de kopkaart).
 *
 * @param  WP_Post|int $post Bericht of begrip.
 * @return array Lijst van [ moment, soort (controle, herzien, gepub), door ].
 */
function sfp_page_config_factcheck_geschiedenis( $post ) {
    $post = get_post( $post );
    if ( ! $post ) {
        return array();
    }
    $zone   = wp_timezone();
    $moment = function ( $datum ) use ( $zone ) {
        $d = date_create_immutable( $datum . ' 12:00:00', $zone );
        return $d ? $d->getTimestamp() : 0;
    };
    $gepub     = (int) get_post_timestamp( $post, 'date' );
    $gepub_dag = $gepub ? wp_date( 'Y-m-d', $gepub ) : '';
    $regels    = array();

    foreach ( sfp_page_config_factcheck_controles( $post ) as $c ) {
        $regels[] = array( $moment( $c['datum'] ), 'controle', (int) $c['door'], $c['datum'] );
    }

    $herzien = array();
    if ( 'post' === $post->post_type ) {
        $log = get_post_meta( $post->ID, 'sfp_herzien_log', true );
        foreach ( is_array( $log ) ? $log : array() as $datum ) {
            $herzien[] = (string) $datum;
        }
        $nu = (string) get_post_meta( $post->ID, 'sfp_herzien', true );
        if ( '' !== $nu ) {
            $herzien[] = $nu;
        } else {
            $herzien[] = wp_date( 'Y-m-d', (int) get_post_timestamp( $post, 'modified' ) );
        }
    } else {
        $herzien[] = wp_date( 'Y-m-d', (int) get_post_timestamp( $post, 'modified' ) );
    }
    foreach ( array_unique( $herzien ) as $datum ) {
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $datum ) && $datum > $gepub_dag ) {
            $regels[] = array( $moment( $datum ), 'herzien', 0, $datum );
        }
    }
    if ( $gepub ) {
        $regels[] = array( $gepub, 'gepub', 0, $gepub_dag );
    }

    // Nieuwste eerst; op dezelfde dag de controle boven de herziening.
    $volgorde = array(
        'controle' => 0,
        'herzien'  => 1,
        'gepub'    => 2,
    );
    usort(
        $regels,
        function ( $a, $b ) use ( $volgorde ) {
            if ( $a[3] !== $b[3] ) {
                return strcmp( $b[3], $a[3] );
            }
            return $volgorde[ $a[1] ] - $volgorde[ $b[1] ];
        }
    );
    return $regels;
}

/* =========================================================================
 * Weergave
 * ====================================================================== */

/**
 * De pagina achter het label: de redactionele verantwoording, anders de
 * pagina over factchecken (besluit Stephan 10-11-2026).
 *
 * @return string Adres, of ''.
 */
function sfp_page_config_factcheck_link() {
    $url = sfp_page_config_link_instelling( 'artikel_redactie' );
    if ( '' === $url ) {
        $url = sfp_page_config_link_instelling( 'artikel_factcheck' );
    }
    return $url;
}

/**
 * Het label Fact-checked. Met een vastgelegde controle een knop die het
 * paneel opent, anders een link naar de redactionele verantwoording.
 *
 * @param  WP_Post|int $post  Bericht of begrip.
 * @param  string      $id    Id van het paneel.
 * @param  string      $groep Groep van panelen (één tegelijk open).
 * @return string
 */
function sfp_page_config_factcheck_label( $post, $id = 'sfp-art-paneel-check', $groep = 'kop' ) {
    $t     = 'sfp_page_config_artikel_tekst';
    $vink  = sfp_page_config_artikel_icoon( 'vink' );
    $tekst = esc_html( $t( 'gecheckt' ) );
    if ( sfp_page_config_factcheck_laatste( $post ) ) {
        return '<button type="button" class="sfp-art-kop__check sfp-art-kop__check--knop" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '" data-sfp-paneel' . ( '' !== $groep ? ' data-sfp-groep="' . esc_attr( $groep ) . '"' : '' ) . '>'
            . $vink . '<span>' . $tekst . '</span>' . str_replace( '<svg ', '<svg class="sfp-art-kop__pijl" ', sfp_page_config_artikel_icoon( 'omlaag' ) ) . '</button>';
    }
    $url = sfp_page_config_factcheck_link();
    return '' !== $url
        ? '<a class="sfp-art-kop__check" href="' . esc_url( $url ) . '">' . $vink . $tekst . '</a>'
        : '<span class="sfp-art-kop__check">' . $vink . $tekst . '</span>';
}

/**
 * Het paneel, of '' zonder vastgelegde controle.
 *
 * @param  WP_Post|int $post Bericht of begrip.
 * @param  string      $id   Id van het paneel.
 * @return string
 */
function sfp_page_config_factcheck_paneel( $post, $id = 'sfp-art-paneel-check' ) {
    $laatste = sfp_page_config_factcheck_laatste( $post );
    if ( ! $laatste ) {
        return '';
    }
    $t       = 'sfp_page_config_artikel_tekst';
    $door    = (int) $laatste['door'];
    $naam    = (string) get_the_author_meta( 'display_name', $door );
    $zone    = wp_timezone();
    $datum   = function ( $iso ) use ( $zone ) {
        $d = date_create_immutable( $iso . ' 12:00:00', $zone );
        return $d ? wp_date( 'j M Y', $d->getTimestamp() ) : $iso;
    };
    $foto    = get_avatar(
        $door,
        64,
        '',
        $naam,
        array(
            'class'   => 'sfp-art-fc__foto',
            'loading' => 'lazy',
        )
    );
    $vink    = sfp_page_config_artikel_icoon( 'vink' );
    $link    = sfp_page_config_factcheck_link();
    $rijen   = '';
    foreach ( sfp_page_config_factcheck_geschiedenis( $post ) as $r ) {
        $tijd = '<time datetime="' . esc_attr( $r[3] ) . '">' . esc_html( $datum( $r[3] ) ) . '</time>';
        if ( 'controle' === $r[1] ) {
            $rijen .= '<li class="is-controle">' . $tijd . '<b>' . $vink . esc_html( $t( 'fc_controle' ) ) . '</b></li>';
        } elseif ( 'herzien' === $r[1] ) {
            $rijen .= '<li>' . $tijd . '<b>' . esc_html( $t( 'fc_herzien' ) ) . '</b></li>';
        } else {
            $rijen .= '<li>' . $tijd . '<span>' . esc_html( $t( 'fc_gepub' ) ) . '</span></li>';
        }
    }
    return '<div class="sfp-art-paneel sfp-art-paneel--check" id="' . esc_attr( $id ) . '" hidden><div class="sfp-art-fc">'
        . '<div class="sfp-art-fc__wie' . ( '' === $foto ? ' sfp-art-fc__wie--zonder-foto' : '' ) . '"><span class="sfp-blok-label">' . esc_html( $t( 'fc_kop' ) ) . '</span>'
        . $foto
        . '<span class="sfp-art-fc__naam">' . esc_html( $naam ) . '</span>'
        . '<span class="sfp-art-fc__rol">' . esc_html( $t( 'fc_rol' ) ) . '</span>'
        // De datum breekt nooit af (7 nov / 2026).
        . '<span class="sfp-art-fc__op">' . $vink . '<span>' . sprintf( esc_html( $t( 'fc_op' ) ), '<span class="sfp-art-fc__datum">' . esc_html( $datum( $laatste['datum'] ) ) . '</span>' ) . '</span></span>'
        . ( '' !== $link ? '<a class="sfp-pijl" href="' . esc_url( $link ) . '">' . esc_html( $t( 'fc_link' ) ) . '</a>' : '' )
        . '</div>'
        . '<div class="sfp-art-fc__log"><span class="sfp-blok-label">' . esc_html( $t( 'fc_log' ) ) . '</span><ol>' . $rijen . '</ol></div>'
        . '</div></div>';
}

/* =========================================================================
 * Controleurs
 * ====================================================================== */

/**
 * Wie een controle kan vastleggen: gebruikers die berichten mogen
 * bewerken, op naam.
 *
 * @return array Lijst van [ id, naam ].
 */
function sfp_page_config_factcheck_controleurs() {
    $uit = array();
    foreach ( get_users(
        array(
            'capability' => 'edit_posts',
            'orderby'    => 'display_name',
            'fields'     => array( 'ID', 'display_name' ),
        )
    ) as $u ) {
        $uit[] = array(
            'id'   => (int) $u->ID,
            'naam' => (string) $u->display_name,
        );
    }
    return $uit;
}

/**
 * De standaard controleur: wie op deze site het laatst een controle
 * vastlegde, anders 0 (geen keuze voorgeschreven).
 *
 * @return int
 */
function sfp_page_config_factcheck_standaard() {
    $id = (int) get_option( SFP_PAGE_CONFIG_FACTCHECK_DOOR, 0 );
    return ( $id && get_userdata( $id ) ) ? $id : 0;
}

add_action( 'updated_post_meta', 'sfp_page_config_factcheck_onthoud', 10, 4 );
add_action( 'added_post_meta', 'sfp_page_config_factcheck_onthoud', 10, 4 );

/**
 * Onthoud de controleur van de nieuwste controle als standaard.
 *
 * @param int    $meta_id Nummer van de meta.
 * @param int    $post_id Bericht.
 * @param string $sleutel Sleutel.
 * @param mixed  $waarde  Waarde.
 */
function sfp_page_config_factcheck_onthoud( $meta_id, $post_id, $sleutel, $waarde ) {
    if ( 'sfp_factcheck' !== $sleutel || ! is_array( $waarde ) || ! $waarde ) {
        return;
    }
    $laatste = end( $waarde );
    if ( is_array( $laatste ) && ! empty( $laatste['door'] ) ) {
        update_option( SFP_PAGE_CONFIG_FACTCHECK_DOOR, (int) $laatste['door'], false );
    }
}

/* =========================================================================
 * Beheer: lijst, controle per artikel en de startvulling
 * ====================================================================== */

/**
 * De lijst voor het tabblad Factcheck in SFP Beheer.
 *
 * @return array
 */
function sfp_page_config_factcheck_stand() {
    $ids   = get_posts(
        array(
            'post_type'        => sfp_page_config_factcheck_types(),
            'post_status'      => 'publish',
            'posts_per_page'   => -1,
            'orderby'          => 'date',
            'order'            => 'DESC',
            'fields'           => 'ids',
            'suppress_filters' => true,
        )
    );
    $items = array();
    $met   = 0;
    foreach ( $ids as $id ) {
        $post    = get_post( $id );
        $laatste = sfp_page_config_factcheck_laatste( $post );
        $herzien = 'post' === $post->post_type ? (string) get_post_meta( $id, 'sfp_herzien', true ) : '';
        if ( $laatste ) {
            ++$met;
        }
        $items[] = array(
            'id'      => (int) $id,
            'titel'   => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
            'soort'   => 'begrippen' === $post->post_type ? 'Begrip' : 'Artikel',
            'bewerk'  => (string) get_edit_post_link( $id, 'raw' ),
            'link'    => (string) get_permalink( $post ),
            'laatste' => $laatste ? array(
                'datum' => $laatste['datum'],
                'naam'  => (string) get_the_author_meta( 'display_name', (int) $laatste['door'] ),
            ) : null,
            // Herzien na de laatste controle: ter informatie, het label blijft staan.
            'later'   => $laatste && '' !== $herzien && $herzien > $laatste['datum'],
        );
    }
    return array(
        'items'       => $items,
        'met'         => $met,
        'zonder'      => count( $items ) - $met,
        'controleurs' => sfp_page_config_factcheck_controleurs(),
        'standaard'   => sfp_page_config_factcheck_standaard(),
        'vandaag'     => wp_date( 'Y-m-d' ),
    );
}

/**
 * Leg een controle vast op een bericht of begrip.
 *
 * @param  int    $id    Bericht of begrip.
 * @param  string $datum JJJJ-MM-DD.
 * @param  int    $door  Gebruiker.
 * @return bool          Of er iets is vastgelegd.
 */
function sfp_page_config_factcheck_leg_vast( $id, $datum, $door ) {
    $post = get_post( $id );
    if ( ! $post || ! in_array( $post->post_type, sfp_page_config_factcheck_types(), true ) ) {
        return false;
    }
    $oud   = sfp_page_config_factcheck_controles( $post );
    $nieuw = sfp_page_config_factcheck_schoon(
        array_merge(
            $oud,
            array(
                array(
                    'datum' => $datum,
                    'door'  => $door,
                ),
            )
        )
    );
    if ( $nieuw === $oud ) {
        return false;
    }
    update_post_meta( $post->ID, 'sfp_factcheck', $nieuw );
    return true;
}

/**
 * Controleer datum en controleur uit een verzoek.
 *
 * @param  array $g Gegevens.
 * @return array|WP_Error [ datum, door ]
 */
function sfp_page_config_factcheck_invoer( array $g ) {
    $datum = isset( $g['datum'] ) ? trim( (string) $g['datum'] ) : '';
    $door  = isset( $g['door'] ) ? (int) $g['door'] : 0;
    $proef = sfp_page_config_factcheck_schoon(
        array(
            array(
                'datum' => $datum,
                'door'  => $door,
            ),
        )
    );
    if ( ! $proef ) {
        return new WP_Error( 'sfp_fc_invoer', 'Kies een controleur en een datum die niet in de toekomst ligt.', array( 'status' => 400 ) );
    }
    $mag = wp_list_pluck( sfp_page_config_factcheck_controleurs(), 'id' );
    if ( ! in_array( $door, $mag, true ) ) {
        return new WP_Error( 'sfp_fc_door', 'Deze gebruiker kan geen controle vastleggen.', array( 'status' => 400 ) );
    }
    return array( $datum, $door );
}

add_action( 'rest_api_init', 'sfp_page_config_factcheck_rest' );

/**
 * GET  /sfp/v1/beheer/factcheck          de lijst
 * POST /sfp/v1/beheer/factcheck/<id>     een controle op één artikel
 * POST /sfp/v1/beheer/factcheck/start    een controle op elk artikel zonder controle
 */
function sfp_page_config_factcheck_rest() {
    $mag = function () {
        return current_user_can( 'manage_options' );
    };
    register_rest_route(
        'sfp/v1',
        '/beheer/factcheck',
        array(
            'methods'             => 'GET',
            'permission_callback' => $mag,
            'callback'            => function () {
                return rest_ensure_response( sfp_page_config_factcheck_stand() );
            },
        )
    );
    register_rest_route(
        'sfp/v1',
        '/beheer/factcheck/start',
        array(
            'methods'             => 'POST',
            'permission_callback' => $mag,
            'callback'            => function ( WP_REST_Request $verzoek ) {
                $invoer = sfp_page_config_factcheck_invoer( (array) $verzoek->get_json_params() );
                if ( is_wp_error( $invoer ) ) {
                    return $invoer;
                }
                $aantal = 0;
                foreach ( sfp_page_config_factcheck_stand()['items'] as $item ) {
                    if ( ! $item['laatste'] && sfp_page_config_factcheck_leg_vast( $item['id'], $invoer[0], $invoer[1] ) ) {
                        ++$aantal;
                    }
                }
                return rest_ensure_response(
                    array(
                        'aantal' => $aantal,
                        'lijst'  => sfp_page_config_factcheck_stand(),
                    )
                );
            },
        )
    );
    register_rest_route(
        'sfp/v1',
        '/beheer/factcheck/(?P<id>\d+)',
        array(
            'methods'             => 'POST',
            'permission_callback' => function ( WP_REST_Request $verzoek ) {
                return current_user_can( 'edit_post', (int) $verzoek['id'] );
            },
            'callback'            => function ( WP_REST_Request $verzoek ) {
                $invoer = sfp_page_config_factcheck_invoer( (array) $verzoek->get_json_params() );
                if ( is_wp_error( $invoer ) ) {
                    return $invoer;
                }
                sfp_page_config_factcheck_leg_vast( (int) $verzoek['id'], $invoer[0], $invoer[1] );
                return rest_ensure_response( array( 'lijst' => sfp_page_config_factcheck_stand() ) );
            },
        )
    );
}
