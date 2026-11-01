<?php
/**
 * Onderhoudsroute voor de cloud (sinds 3.22.0, punt 20).
 *
 * Overgenomen uit het snippet "Onderhoud: updates en Site Health via de
 * cloud" (v1.3), met dezelfde route, parameters en antwoordvorm:
 *
 *   GET  /wp-json/sfp/v1/onderhoud   status: updates, core, php, health
 *   POST /wp-json/sfp/v1/onderhoud   stage=plugins|themes|translations|health|purge
 *        usedcss=1  wist bij purge eerst alle gebruikte CSS van WP Rocket
 *        limit=5    hoogstens zoveel plugins per aanroep (stage=plugins)
 *        exclude[]  pluginbestanden die overgeslagen worden
 *
 * Alleen voor de AI-serviceaccounts (filter sfp_ai_serviceaccounts) met
 * updaterechten. Zolang het snippet nog actief is, registreert de plugin
 * de route niet: dan blijft het snippet leidend, zonder dubbele route.
 *
 * @package SFP_Page_Config
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', 'sfp_page_config_onderhoud_routes', 20 );

/**
 * Registreer de route, tenzij het oude snippet hem al registreert.
 */
function sfp_page_config_onderhoud_routes() {
    if ( function_exists( 'sfp_onderhoud_v1_routes' ) ) {
        return;
    }
    register_rest_route(
        'sfp/v1',
        '/onderhoud',
        array(
            array(
                'methods'             => 'GET',
                'callback'            => 'sfp_page_config_onderhoud_status',
                'permission_callback' => 'sfp_page_config_onderhoud_permission',
            ),
            array(
                'methods'             => 'POST',
                'callback'            => 'sfp_page_config_onderhoud_run',
                'permission_callback' => 'sfp_page_config_onderhoud_permission',
                'args'                => array(
                    'stage'   => array(
                        'type'     => 'string',
                        'required' => true
                    ),
                    'limit'   => array(
                        'type'    => 'integer',
                        'default' => 5
                    ),
                    'exclude' => array(
                        'type'    => 'array',
                        'default' => array( 'latepoint-pro-features/latepoint-pro-features.php' )
                    ),
                ),
            ),
        )
    );
}

function sfp_page_config_onderhoud_permission() {
    $user = wp_get_current_user();
    if ( ! $user || ! $user->exists() ) {
        return new WP_Error( 'sfp_onderhoud_auth', 'Niet ingelogd.', array( 'status' => 401 ) );
    }
    if ( ! in_array( $user->user_login, apply_filters( 'sfp_ai_serviceaccounts', array( 'claude-api', 'chatgpt-api' ) ), true ) ) {
        return new WP_Error( 'sfp_onderhoud_user', 'Alleen AI-serviceaccounts.', array( 'status' => 403 ) );
    }
    if ( ! current_user_can( 'update_plugins' ) || ! current_user_can( 'update_themes' ) || ! current_user_can( 'update_languages' ) ) {
        return new WP_Error( 'sfp_onderhoud_cap', 'Onvoldoende rechten.', array( 'status' => 403 ) );
    }
    return true;
}

function sfp_page_config_onderhoud_includes() {
    require_once ABSPATH . 'wp-admin/includes/admin.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/update.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    require_once ABSPATH . 'wp-admin/includes/theme.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    if ( ! class_exists( 'WP_Site_Health' ) ) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
    }
    @set_time_limit( 600 );
    ignore_user_abort( true );
}

function sfp_page_config_onderhoud_refresh() {
    wp_clean_plugins_cache( true );
    wp_clean_themes_cache( true );
    delete_site_transient( 'update_plugins' );
    delete_site_transient( 'update_themes' );
    delete_site_transient( 'update_core' );
    wp_version_check( array(), true );
    wp_update_plugins();
    wp_update_themes();
}

function sfp_page_config_onderhoud_pending() {
    $out = array(
        'plugins'      => array(),
        'themes'       => array(),
        'translations' => array(),
        'core'         => array()
    );

    $all = get_plugins();
    $pl  = get_site_transient( 'update_plugins' );
    if ( ! empty( $pl->response ) ) {
        foreach ( $pl->response as $file => $data ) {
            $out['plugins'][] = array(
                'file'    => $file,
                'name'    => isset( $all[ $file ]['Name'] ) ? $all[ $file ]['Name'] : $file,
                'current' => isset( $all[ $file ]['Version'] ) ? $all[ $file ]['Version'] : '',
                'new'     => isset( $data->new_version ) ? $data->new_version : '',
                'package' => ! empty( $data->package ),
            );
        }
    }

    $th = get_site_transient( 'update_themes' );
    if ( ! empty( $th->response ) ) {
        foreach ( $th->response as $slug => $data ) {
            $theme           = wp_get_theme( $slug );
            $out['themes'][] = array(
                'slug'    => $slug,
                'name'    => $theme->exists() ? $theme->get( 'Name' ) : $slug,
                'current' => $theme->exists() ? $theme->get( 'Version' ) : '',
                'new'     => isset( $data['new_version'] ) ? $data['new_version'] : '',
            );
        }
    }

    foreach ( (array) wp_get_translation_updates() as $t ) {
        $out['translations'][] = array(
            'type'     => $t->type,
            'slug'     => $t->slug,
            'language' => $t->language
        );
    }

    foreach ( (array) get_core_updates() as $c ) {
        if ( isset( $c->response ) && 'upgrade' === $c->response ) {
            $out['core'][] = array(
                'current' => get_bloginfo( 'version' ),
                'new'     => $c->version
            );
        }
    }

    return $out;
}

function sfp_page_config_onderhoud_health( $with_details = true ) {
    $health = WP_Site_Health::get_instance();
    $tests  = WP_Site_Health::get_tests();
    $result = array(
        'critical'      => array(),
        'recommended'   => array(),
        'good'          => 0,
        'async_skipped' => array()
    );

    foreach ( $tests['direct'] as $name => $test ) {
        $r = null;
        if ( is_string( $test['test'] ) && is_callable( array( $health, 'get_test_' . $test['test'] ) ) ) {
            $r = call_user_func( array( $health, 'get_test_' . $test['test'] ) );
        } elseif ( is_callable( $test['test'] ) ) {
            $r = call_user_func( $test['test'] );
        }
        if ( ! is_array( $r ) || empty( $r['status'] ) ) {
            continue;
        }
        if ( 'good' === $r['status'] ) {
            $result['good']++;
            continue;
        }
        $item = array(
            'test'  => $name,
            'label' => isset( $r['label'] ) ? wp_strip_all_tags( $r['label'] ) : $name,
            'badge' => isset( $r['badge']['label'] ) ? $r['badge']['label'] : '',
        );
        if ( $with_details && isset( $r['description'] ) ) {
            $item['description'] = trim( wp_strip_all_tags( $r['description'] ) );
        }
        $result[ $r['status'] ][] = $item;
    }

    foreach ( $tests['async'] as $name => $test ) {
        $result['async_skipped'][] = array(
            'test' => $name,
            'rest' => isset( $test['has_rest'] ) && $test['has_rest'] ? rest_url( 'wp-site-health/v1/tests/' . $name ) : '',
        );
    }

    return $result;
}

function sfp_page_config_onderhoud_status( WP_REST_Request $req ) {
    sfp_page_config_onderhoud_includes();
    sfp_page_config_onderhoud_refresh();

    $theme = wp_get_theme();
    return rest_ensure_response(
        array(
            'site'       => home_url(),
            'wp'         => get_bloginfo( 'version' ),
            'php'        => PHP_VERSION,
            'theme'      => array(
                'name'    => $theme->get( 'Name' ),
                'version' => $theme->get( 'Version' )
            ),
            'pending'    => sfp_page_config_onderhoud_pending(),
            'health'     => sfp_page_config_onderhoud_health( false ),
            'checked_at' => current_time( 'mysql' ),
        )
    );
}

function sfp_page_config_onderhoud_run( WP_REST_Request $req ) {
    sfp_page_config_onderhoud_includes();
    $stage = $req->get_param( 'stage' );

    switch ( $stage ) {
        case 'plugins':
            return rest_ensure_response( sfp_page_config_onderhoud_update_plugins( (int) $req->get_param( 'limit' ), (array) $req->get_param( 'exclude' ) ) );
        case 'themes':
            return rest_ensure_response( sfp_page_config_onderhoud_update_themes() );
        case 'translations':
            return rest_ensure_response( sfp_page_config_onderhoud_update_translations() );
        case 'health':
            return rest_ensure_response( array( 'health' => sfp_page_config_onderhoud_health( true ) ) );
        case 'purge':
            return rest_ensure_response( sfp_page_config_onderhoud_purge( rest_sanitize_boolean( $req->get_param( 'usedcss' ) ) ) );
    }
    return new WP_Error( 'sfp_onderhoud_stage', 'Onbekende stage.', array( 'status' => 400 ) );
}

function sfp_page_config_onderhoud_result_to_text( $r ) {
    if ( is_wp_error( $r ) ) {
        return 'fout: ' . $r->get_error_message();
    }
    if ( false === $r || null === $r ) {
        return 'fout: geen resultaat (bestandssysteem of download)';
    }
    return 'ok';
}

function sfp_page_config_onderhoud_update_plugins( $limit, $exclude ) {
    sfp_page_config_onderhoud_refresh();
    $pending = sfp_page_config_onderhoud_pending();
    $todo    = array();
    $skipped = array();
    foreach ( $pending['plugins'] as $p ) {
        if ( in_array( $p['file'], $exclude, true ) ) {
            $skipped[] = $p;
            continue;
        }
        if ( ! $p['package'] ) {
            $p['reason'] = 'geen downloadpakket (licentie?)';
            $skipped[]   = $p;
            continue;
        }
        $todo[] = $p;
    }
    $batch     = array_slice( $todo, 0, max( 1, $limit ) );
    $remaining = count( $todo ) - count( $batch );
    $updated   = array();

    if ( $batch ) {
        $skin     = new Automatic_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );
        $files    = wp_list_pluck( $batch, 'file' );
        $results  = $upgrader->bulk_upgrade( $files );
        $all      = get_plugins();
        foreach ( $batch as $p ) {
            $r         = isset( $results[ $p['file'] ] ) ? $results[ $p['file'] ] : null;
            $now       = isset( $all[ $p['file'] ]['Version'] ) ? $all[ $p['file'] ]['Version'] : '';
            $updated[] = array(
                'name'    => $p['name'],
                'file'    => $p['file'],
                'from'    => $p['current'],
                'to'      => $p['new'],
                'now'     => $now,
                'result'  => sfp_page_config_onderhoud_result_to_text( $r ),
                'success' => ( $now === $p['new'] ),
                'active'  => is_plugin_active( $p['file'] ),
            );
        }
        $messages = $skin->get_upgrade_messages();
    } else {
        $messages = array();
    }

    return array(
        'stage'     => 'plugins',
        'updated'   => $updated,
        'skipped'   => $skipped,
        'remaining' => $remaining,
        'messages'  => array_slice( array_map( 'wp_strip_all_tags', $messages ), -20 ),
    );
}

function sfp_page_config_onderhoud_update_themes() {
    sfp_page_config_onderhoud_refresh();
    $pending = sfp_page_config_onderhoud_pending();
    $updated = array();
    if ( $pending['themes'] ) {
        $skin     = new Automatic_Upgrader_Skin();
        $upgrader = new Theme_Upgrader( $skin );
        $slugs    = wp_list_pluck( $pending['themes'], 'slug' );
        $results  = $upgrader->bulk_upgrade( $slugs );
        wp_clean_themes_cache( true );
        foreach ( $pending['themes'] as $t ) {
            $r         = isset( $results[ $t['slug'] ] ) ? $results[ $t['slug'] ] : null;
            $theme     = wp_get_theme( $t['slug'] );
            $now       = $theme->exists() ? $theme->get( 'Version' ) : '';
            $updated[] = array(
                'name'    => $t['name'],
                'slug'    => $t['slug'],
                'from'    => $t['current'],
                'to'      => $t['new'],
                'now'     => $now,
                'result'  => sfp_page_config_onderhoud_result_to_text( $r ),
                'success' => ( $now === $t['new'] ),
            );
        }
        $messages = $skin->get_upgrade_messages();
    } else {
        $messages = array();
    }
    return array(
        'stage'    => 'themes',
        'updated'  => $updated,
        'messages' => array_slice( array_map( 'wp_strip_all_tags', $messages ), -20 ),
    );
}

function sfp_page_config_onderhoud_update_translations() {
    sfp_page_config_onderhoud_refresh();
    $updates = wp_get_translation_updates();
    $out     = array();
    if ( $updates ) {
        $skin     = new Automatic_Upgrader_Skin();
        $upgrader = new Language_Pack_Upgrader( $skin );
        $results  = $upgrader->bulk_upgrade( $updates );
        foreach ( $updates as $i => $u ) {
            $r     = isset( $results[ $i ] ) ? $results[ $i ] : null;
            $out[] = array(
                'type'     => $u->type,
                'slug'     => $u->slug,
                'language' => $u->language,
                'result'   => sfp_page_config_onderhoud_result_to_text( $r ),
            );
        }
        $messages = $skin->get_upgrade_messages();
    } else {
        $messages = array();
    }
    $still = array();
    foreach ( (array) wp_get_translation_updates() as $u ) {
        $still[] = $u->type . ':' . $u->slug . ':' . $u->language;
    }
    return array(
        'stage'         => 'translations',
        'updated'       => $out,
        'still_offered' => $still,
        'messages'      => array_slice( array_map( 'wp_strip_all_tags', $messages ), -20 ),
    );
}

function sfp_page_config_onderhoud_purge( $usedcss_legen = false ) {
    $done = array();
    // Gebruikte CSS alleen op verzoek (usedcss=1), zoals de knop Gebruikte CSS legen.
    // Standaard niet: dat wist de used CSS van de hele site (v1.2).
    if ( $usedcss_legen && has_filter( 'rocket_saas_clean_all' ) ) {
        $usedcss = apply_filters( 'rocket_saas_clean_all', array() );
        $done[]  = 'wp_rocket_used_css:' . ( isset( $usedcss['status'] ) ? $usedcss['status'] : 'niet actief' );
    }
    if ( function_exists( 'rocket_clean_domain' ) ) {
        rocket_clean_domain();
        $done[] = 'wp_rocket';
    }
    if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
        sg_cachepress_purge_cache();
        $done[] = 'siteground';
    } elseif ( class_exists( '\SiteGround_Optimizer\Supercacher\Supercacher' ) && method_exists( '\SiteGround_Optimizer\Supercacher\Supercacher', 'purge_cache' ) ) {
        \SiteGround_Optimizer\Supercacher\Supercacher::purge_cache();
        $done[] = 'siteground';
    }
    return array(
        'stage'  => 'purge',
        'purged' => $done,
        'note'   => 'Cloudflare niet geleegd; dat blijft handmatig of via de Cloudflare-plugin bij publiceren.'
    );
}
