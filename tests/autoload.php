<?php
/** Run with: php tests/autoload.php */
error_reporting( E_ALL );
set_error_handler(
    function ( $severity, $message, $file, $line ) {
        throw new ErrorException( $message, 0, $severity, $file, $line );
    }
);
define( 'ABSPATH', __DIR__ . '/' );
define( 'SFP_PAGE_CONFIG_VERSION', 'test' );
define( 'HOUR_IN_SECONDS', 3600 );

/* ---------- Kleine WordPress-stubs ---------- */

$GLOBALS['active']     = array();
$GLOBALS['transients'] = array();
$GLOBALS['db']         = array();

function add_action() {}
function apply_filters( $hook, $value ) {
    return $value;
}
function get_option( $k, $d = false ) {
    return 'active_plugins' === $k ? $GLOBALS['active'] : $d;
}
function delete_option( $k ) {
    unset( $GLOBALS['db'][ $k ] );
    return true;
}
function wp_get_theme() {
    return new class() {
        public function get_stylesheet() {
            return 'astra';
        }
        public function get_template() {
            return 'astra';
        }
    };
}
function home_url( $p = '' ) {
    return 'https://example.test' . $p;
}
function wp_date( $f ) {
    return '2026-10-17T10:00:00+02:00';
}
function get_current_user_id() {
    return 7;
}
function set_transient( $k, $v, $t = 0 ) {
    $GLOBALS['transients'][ $k ] = $v;
    return true;
}
function get_transient( $k ) {
    return $GLOBALS['transients'][ $k ] ?? false;
}

/**
 * Nepdatabase: begrijpt precies de queries uit autoloaded-options.php.
 */
class Nep_WPDB {
    public $options = 'wp_options';
    public function esc_like( $s ) {
        return $s;
    }
    public function prepare( $sql, $args ) {
        return array( $sql, $args );
    }
    public $posts = 'wp_posts';
    public function get_col( $q ) {
        return $GLOBALS['snippets'] ?? array();
    }
    public function get_row( $q ) {
        $on = array( 'on', 'auto', 'yes' );
        $n  = 0;
        $b  = 0;
        foreach ( $GLOBALS['db'] as $naam => $o ) {
            if ( in_array( $o[1], $on, true ) ) {
                $n++;
                $b += strlen( $o[0] );
            }
        }
        return (object) array( 'total_count' => $n, 'total_bytes' => $b );
    }
    public function get_results( $q ) {
        list( $sql, $args ) = $q;
        $on  = array( 'on', 'auto', 'yes' );
        $uit = array();
        foreach ( $GLOBALS['db'] as $naam => $o ) {
            $rij = (object) array( 'option_id' => 1, 'option_name' => $naam, 'size' => strlen( $o[0] ), 'autoload' => $o[1], 'option_value' => $o[0] );
            if ( false !== strpos( $sql, 'option_name IN' ) ) {
                if ( in_array( $naam, $args, true ) ) {
                    $uit[] = $rij;
                }
            } elseif ( false !== strpos( $sql, 'NOT IN' ) ) {
                if ( ! in_array( $o[1], $on, true ) && 0 !== strpos( $naam, '_transient_' ) && 0 !== strpos( $naam, '_site_transient_' ) ) {
                    $uit[] = $rij;
                }
            } elseif ( in_array( $o[1], $on, true ) ) {
                $uit[] = $rij;
            }
        }
        usort( $uit, function ( $a, $b ) {
            return $b->size <=> $a->size ?: strcmp( $a->option_name, $b->option_name );
        } );
        return $uit;
    }
}
$GLOBALS['wpdb'] = new Nep_WPDB();

require dirname( __DIR__ ) . '/includes/autoloaded-options.php';

/* ---------- Testhulp ---------- */

$fouten = 0;
$aantal = 0;
function zelfde( $verwacht, $echt, $wat ) {
    global $fouten, $aantal;
    $aantal++;
    if ( $verwacht !== $echt ) {
        $fouten++;
        echo "FOUT: $wat\n  verwacht: " . var_export( $verwacht, true ) . "\n  kreeg:    " . var_export( $echt, true ) . "\n";
    }
}

// Netwerkstack (zonder Spectra, zoals SwS).
$GLOBALS['active'] = array(
    'admin-site-enhancements-pro/a.php', 'surerank/a.php', 'sureforms/a.php', 'surecontact/a.php',
    'suretriggers/a.php', 'sfp-page-config/a.php', 'imagify/a.php', 'wp-rocket/a.php',
    'complianz-gdpr/a.php', 'presto-player/a.php', 'fluent-smtp/a.php', 'sigmize/a.php',
    'sg-cachepress/a.php', 'sg-security/a.php', 'latepoint/a.php', 'cloudflare/a.php',
);
$actief = sfp_ao_active_prefixes();

/* ---------- a. Bronherkenning ---------- */

$verweesd = array(
    'gtm_server_side_settings' => 'Stape Conversion Tracking',
    'tagging_settings_x'       => 'Stape Conversion Tracking',
    'suremails_settings'       => 'SureMails',
    'wpforms_settings'         => 'WPForms',
    'cp_x_setting'             => 'Convert Pro',
    'cpro-x'                   => 'Convert Pro',
    'su_option_custom-css'     => 'Shortcodes Ultimate',
    'grw_version'              => 'Google Reviews Widget',
    'rplg_x'                   => 'Google Reviews Widget',
    'tlwp_x'                   => 'Temporary Login Without Password',
    'woocommerce_db_version'   => 'WooCommerce',
    'rtafar_x'                 => 'Real-Time Find and Replace',
    'acf_version'              => 'Advanced Custom Fields',
    'vwo_x'                    => 'VWO',
    'bitforms_x'               => 'Bit Form',
    'atfp_x'                   => 'Automatic Translations for Polylang',
    'polylang'                 => 'Polylang',
    'pll_x'                    => 'Polylang',
    'cfkef_x'                  => 'Cool FormKit (Elementor)',
    'cfef_x'                   => 'Conditional Fields (Elementor)',
    'uagb_x'                   => 'Spectra',
    'spectra_x'                => 'Spectra',
);
foreach ( $verweesd as $naam => $bron ) {
    zelfde( $bron, sfp_ao_detect_source( $naam ), "bron van $naam" );
    zelfde( 'orphan', sfp_ao_detect_status( $naam, $actief ), "status van $naam" );
}

// Actieve plugins blijven actief, core blijft core.
foreach ( array( 'astra-settings', 'admin_site_enhancements_extra', 'surerank_settings', 'cmplz_x', 'srfm_x', 'latepoint_x', 'rocket_x', 'fluentmail-settings', 'sfp_page_config' ) as $naam ) {
    zelfde( 'active', sfp_ao_detect_status( $naam, $actief ), "actief: $naam" );
}
foreach ( array( 'siteurl', 'rewrite_rules', 'widget_block', 'xrk_user_roles', 'nonce_key', 'auth_salt', 'default_term_product_cat', 'category_children', 'recovery_mode_auth_key', 'auto_updater.lock', 'dashboard_widget_options' ) as $naam ) {
    zelfde( 'core', sfp_ao_detect_status( $naam, $actief ), "core: $naam" );
}
zelfde( 'unknown', sfp_ao_detect_status( 'iets_onherkenbaars', $actief ), 'onherkend blijft onbekend' );

/* ---------- 3.2.1: actieve plugins met afwijkende namen ---------- */

foreach ( array(
    'suretrigger_options'           => 'Ottokit (SureTriggers)',
    '_fluentsmtp_connection_health' => 'FluentSMTP',
    '_fluent_smtp_notify_settings'  => 'FluentSMTP',
    '_fsmtp_last_notification_sent' => 'FluentSMTP',
    '_surerank_seo_last_updated'    => 'SureRank SEO',
    'zip_ai_settings'               => 'Brainstorm Force',
    'mcp_jwt_secret'                => 'WP Rocket',
    'wpmedia_mcp_oauth_rewrite_version' => 'WP Rocket',
) as $naam => $bron ) {
    zelfde( $bron, sfp_ao_detect_source( $naam ), "bron van $naam" );
    zelfde( 'active', sfp_ao_detect_status( $naam, $actief ), "actief: $naam" );
    zelfde( false, sfp_ao_is_deletable( $naam, sfp_ao_detect_status( $naam, $actief ) ), "niet verwijderbaar: $naam" );
}
foreach ( array( 'edd_sl_1f42911b731d42f47d569f23cf2d5abb', 'edd_api_request_a1d5', 'external_updates-siteground-wizard', 'as_has_wp_comment_logs' ) as $naam ) {
    zelfde( 'active', sfp_ao_detect_status( $naam, $actief ), "gedeeld blijft actief: $naam" );
}
zelfde( 'core', sfp_ao_detect_status( 'using_application_passwords', $actief ), 'core: using_application_passwords' );
// Een verweesde plugin met streepje vooraan blijft verweesd.
zelfde( 'orphan', sfp_ao_detect_status( '_elementor_pro_license_data', $actief ), 'verweesd met streepje vooraan' );
zelfde( 'orphan', sfp_ao_detect_status( '_cp_v2_template_styles', $actief ), 'Convert Pro met streepje vooraan' );

/* ---------- 3.2.1: eigen gegevens ---------- */

foreach ( array( 'dps_bvfix_backup_5562', 'dps_ctatail_x', 'sws_cpt_rewrite_versie', 'fl_parity_20260710_done', 'cvd_tool_feedback_log', 'dga-x', 'dst_x', 'slb_x' ) as $naam ) {
    zelfde( 'eigen', sfp_ao_detect_status( $naam, $actief ), "eigen: $naam" );
    zelfde( false, sfp_ao_is_deletable( $naam, 'eigen' ), "eigen niet verwijderbaar: $naam" );
}
zelfde( 'Eigen gegevens (sitecode)', sfp_ao_detect_source( 'dps_bvfix_backup_5562' ), 'bron eigen via sitecode' );
zelfde( 'unknown', sfp_ao_detect_status( 'fluentx', $actief ), 'fl zonder scheidingsteken is geen sitecode' );
$GLOBALS['snippets'] = array( "<?php update_option( 'fix_wa_done', 1 ); get_option(\"mijn_snippet_optie\");" );
sfp_ao_snippet_code( true );
zelfde( 'eigen', sfp_ao_detect_status( 'fix_wa_done', $actief ), 'eigen via snippetcode (enkele quotes)' );
zelfde( 'eigen', sfp_ao_detect_status( 'mijn_snippet_optie', $actief ), 'eigen via snippetcode (dubbele quotes)' );
zelfde( 'Eigen gegevens (snippet)', sfp_ao_detect_source( 'fix_wa_done' ), 'bron eigen via snippet' );
zelfde( 'unknown', sfp_ao_detect_status( 'fix_wa', $actief ), 'deel van een naam telt niet' );
zelfde( 'transient', sfp_ao_detect_status( '_transient_x', $actief ), 'transient' );

/* ---------- b. Wat mag weg ---------- */

zelfde( true, sfp_ao_is_deletable( 'iets_onherkenbaars', 'unknown' ), 'onbekend mag na bevestiging' );
zelfde( true, sfp_ao_is_deletable( 'wpforms_settings', 'orphan' ), 'verweesd mag' );
zelfde( false, sfp_ao_is_deletable( 'astra-settings', 'active' ), 'actief nooit' );
zelfde( false, sfp_ao_is_deletable( 'siteurl', 'core' ), 'core nooit' );
zelfde( false, sfp_ao_is_deletable( '_transient_x', 'transient' ), 'transient nooit' );
zelfde( false, sfp_ao_is_deletable( 'cron', 'unknown' ), 'beschermd nooit, ook als onbekend' );
zelfde( false, sfp_ao_is_deletable( 'theme_mods_astra', 'unknown' ), 'theme_mods nooit' );
zelfde( false, sfp_ao_is_deletable( 'xrk_user_roles', 'unknown' ), 'prefix_user_roles nooit' );
zelfde( false, sfp_ao_is_deletable( 'nonce_salt', sfp_ao_detect_status( 'nonce_salt', $actief ) ), 'sleutels van WordPress nooit' );
zelfde( 'active', sfp_ao_detect_status( 'default_term_asenha', $actief ), 'ASE-standaardterm blijft van ASE' );

/* ---------- Analyse, d, e, f ---------- */

$GLOBALS['db'] = array(
    'astra-settings'          => array( str_repeat( 'a', 160000 ), 'on' ),
    'admin_site_enhancements' => array( str_repeat( 'b', 160000 ), 'auto' ),
    'grote_onbekende'         => array( str_repeat( 'c', 151000 ), 'yes' ),
    'siteurl'                 => array( 'https://example.test', 'on' ),
    'wpforms_settings'        => array( 'x', 'on' ),
    'iets_onherkenbaars'      => array( 'yy', 'on' ),
    'acf_version'             => array( '6.0', 'off' ),
    'pll_x'                   => array( 'z', 'auto-off' ),
    'surerank_uit'            => array( 'z', 'off' ),
    'dps_bvfix_backup_1'      => array( 'b', 'off' ),
    'sws_x'                   => array( 'b', 'on' ),
    '_transient_oud'          => array( 'z', 'off' ),
);
$a = sfp_ao_analyse();
zelfde( 7, $a['totals']->total_count, 'totaal aantal' );
zelfde( 7, $a['tellingen']['all'], 'telling alles gelijk aan totaal' );
$eigen_namen = array_map( function ( $r ) {
    return $r->option_name;
}, $a['eigen'] );
sort( $eigen_namen );
zelfde( array( 'dps_bvfix_backup_1', 'sws_x' ), $eigen_namen, 'eigen gegevens: aan en uit' );
zelfde( 2, $a['tellingen']['eigen'], 'telling eigen gegevens' );
zelfde( 1, $a['tellingen']['orphan'], 'een verweesde met autoload aan' );
zelfde( 2, $a['tellingen']['unknown'], 'twee onbekende met autoload aan' );
zelfde( array( 'acf_version', 'pll_x' ), array_map( function ( $r ) {
    return $r->option_name;
}, $a['uit'] ), 'restanten met autoload uit: verweesd en onbekend, geen actief, geen transient' );
zelfde( array( 'astra-settings', 'grote_onbekende' ), array_map( function ( $r ) {
    return $r->option_name;
}, $a['groot'] ), 'groot en verplicht aan: on en yes, niet auto' );

$rest = sfp_ao_rest_data();
zelfde( $a['totals']->total_bytes, $rest['totaal']['bytes'], 'REST-totaal gelijk aan pagina' );
zelfde( count( $a['rows'] ), count( $rest['opties'] ), 'REST zelfde aantal rijen' );
zelfde( array( 'naam', 'grootte', 'autoload', 'bron', 'status', 'verwijderbaar' ), array_keys( $rest['opties'][0] ), 'REST-velden' );
zelfde( 2, count( $rest['groot_geforceerd'] ), 'REST toont grote opties' );
zelfde( 2, count( $rest['restanten_autoload_uit'] ), 'REST toont restanten met autoload uit' );
zelfde( 2, count( $rest['eigen_gegevens'] ), 'REST toont eigen gegevens' );

/* ---------- c. Back-up ---------- */

$exp = sfp_ao_export_data( array( 'acf_version', 'wpforms_settings', 'bestaat_niet', 'acf_version' ) );
zelfde( 2, $exp['aantal'], 'back-up telt gevonden opties' );
zelfde( array( 'bestaat_niet' ), $exp['niet_gevonden'], 'back-up meldt ontbrekende namen' );
zelfde( array( 'naam' => 'acf_version', 'autoload' => 'off', 'waarde' => '6.0' ), $exp['opties'][0], 'back-up bevat ruwe waarde en autoload' );

/* ---------- Bevestigingslijst ---------- */

$rijen = sfp_ao_confirm_rows( array( 'wpforms_settings', 'astra-settings', 'iets_onherkenbaars', 'siteurl', 'weg_al' ) );
$weg   = array();
foreach ( $rijen as $r ) {
    if ( $r->verwijderbaar ) {
        $weg[] = $r->option_name;
    }
}
sort( $weg );
zelfde( array( 'iets_onherkenbaars', 'wpforms_settings' ), $weg, 'bevestiging: alleen verweesd en onbekend' );
zelfde( 4, count( $rijen ), 'bevestiging: niet-bestaande naam valt af' );
zelfde( sfp_ao_list_hash( array( 'b', 'a' ) ), sfp_ao_list_hash( array( 'a', 'b', 'a' ) ), 'vingerafdruk los van volgorde' );
zelfde( false, sfp_ao_list_hash( array( 'a' ) ) === sfp_ao_list_hash( array( 'a', 'b' ) ), 'vingerafdruk verandert met de lijst' );

echo $fouten ? "$fouten van $aantal controles mislukt\n" : "Alle $aantal controles geslaagd\n";
exit( $fouten ? 1 : 0 );
