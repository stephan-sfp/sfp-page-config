<?php
/**
 * SFP Page Config - GitHub Auto-Updater
 *
 * Checks for new releases on GitHub and integrates with the
 * WordPress plugin update mechanism. Modelled after the
 * SFP Tooltip updater.
 *
 * Kanaal (sinds 2.12.0): standaard volgt een site de laatste gewone
 * release. Staat de instelling update_kanaal op "proef", dan volgt de site
 * ook pre-releases; zo kan één site een proefversie draaien terwijl de
 * andere op de laatste gewone release blijven.
 *
 * REST (sinds 2.12.0): POST /sfp/v1/updater/ververs leegt de 12-uurscache
 * van deze updater en laat WordPress opnieuw naar updates kijken. Alleen
 * voor gebruikers die plugins mogen bijwerken.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFP_Page_Config_Updater {

    /**
     * Plugin slug (e.g. "sfp-page-config/sfp-page-config.php").
     *
     * @var string
     */
    private $plugin_slug;

    /**
     * Full path to the main plugin file.
     *
     * @var string
     */
    private $plugin_file;

    /**
     * Currently installed version.
     *
     * @var string
     */
    private $plugin_version;

    /**
     * GitHub username.
     *
     * @var string
     */
    private $github_user = 'stephan-sfp';

    /**
     * GitHub repository name.
     *
     * @var string
     */
    private $github_repo = 'sfp-page-config';

    /**
     * Transient key for caching the latest release data.
     *
     * @var string
     */
    private $transient_key = 'sfp_page_config_github_release';

    /**
     * Transient key that throttles forced checks.
     *
     * @var string
     */
    private $throttle_key = 'sfp_page_config_github_forced';

    /**
     * @param string $plugin_file    Full path to the main plugin file.
     * @param string $plugin_version Current version string.
     */
    public function __construct( $plugin_file, $plugin_version ) {
        $this->plugin_file    = $plugin_file;
        $this->plugin_slug    = plugin_basename( $plugin_file );
        $this->plugin_version = $plugin_version;

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
        add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
    }

    /* -----------------------------------------------------------------
     * GitHub API
     * -------------------------------------------------------------- */

    /**
     * Is this request a manual "check again" from the updates screen?
     *
     * WordPress sets force-check=1 on update-core.php. Three limits apply,
     * because this URL carries no nonce and a logged-in administrator can
     * be made to load it from elsewhere:
     *
     *   1. Admin context only, and only for a user who may update plugins.
     *   2. At most once per request.
     *   3. At most once per five minutes, so repeated or forged requests
     *      cannot burn through GitHub's hourly rate limit. Running into
     *      that limit would make every later check fail.
     *
     * @return bool
     */
    private function is_forced_check() {

        static $done = false;

        if ( $done ) {
            return false;
        }
        if ( ! is_admin() || empty( $_GET['force-check'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return false;
        }
        if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'update_plugins' ) ) {
            return false;
        }
        if ( false !== get_transient( $this->throttle_key ) ) {
            return false;
        }

        set_transient( $this->throttle_key, time(), 5 * MINUTE_IN_SECONDS );

        $done = true;

        return true;
    }

    /**
     * Build the entry WordPress expects in its update transient.
     *
     * @param  string $version New version to report.
     * @param  string $url     Release page URL.
     * @param  string $package Download URL, empty when there is nothing to install.
     * @return object
     */
    private function build_item( $version, $url = '', $package = '' ) {

        return (object) array(
            'id'          => 'github.com/' . $this->github_user . '/' . $this->github_repo,
            'slug'        => dirname( $this->plugin_slug ),
            'plugin'      => $this->plugin_slug,
            'new_version' => $version,
            'url'         => $url,
            'package'     => $package,
        );
    }

    /**
     * Het updatekanaal van deze site: '' (gewone releases) of 'proef'
     * (ook pre-releases).
     *
     * @return string
     */
    public static function kanaal() {
        $settings = get_option( 'sfp_settings', array() );
        return is_array( $settings ) && isset( $settings['update_kanaal'] ) && 'proef' === $settings['update_kanaal'] ? 'proef' : '';
    }

    /**
     * Leeg de cache van de updater, voor beide kanalen.
     */
    public static function wis_cache() {
        delete_transient( 'sfp_page_config_github_release' );
        delete_transient( 'sfp_page_config_github_release_proef' );
    }

    /**
     * Fetch the latest release from GitHub (cached for 12 hours).
     *
     * @return object|false Release object or false on failure.
     */
    private function get_latest_release() {

        $proef = 'proef' === self::kanaal();
        $key   = $proef ? $this->transient_key . '_proef' : $this->transient_key;

        // A forced check from Beheer > Updates must actually reach GitHub.
        // WordPress clears its own update transient there, but not ours, so
        // without this a fresh release stayed invisible for up to 12 hours.
        if ( $this->is_forced_check() ) {
            delete_transient( $key );
        }

        $release = get_transient( $key );
        if ( false !== $release ) {
            return $release;
        }

        // Gewoon kanaal: de laatste release die geen pre-release is. Kanaal
        // proef: de lijst met releases, waaruit hieronder de hoogste versie
        // wordt gekozen, pre-releases meegeteld.
        $url = sprintf(
            $proef ? 'https://api.github.com/repos/%s/%s/releases?per_page=10' : 'https://api.github.com/repos/%s/%s/releases/latest',
            $this->github_user,
            $this->github_repo
        );

        $response = wp_remote_get( $url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept'     => 'application/vnd.github.v3+json',
                // GitHub's API requires a User-Agent. Without it the
                // response is 403. Using the plugin slug + version makes
                // the request identifiable in rate-limit diagnostics.
                'User-Agent' => 'SFP-Page-Config/' . $this->plugin_version . ' (+https://schoolforprofessionals.com)',
            ),
        ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ) );
        if ( $proef ) {
            $beste = null;
            foreach ( is_array( $body ) ? $body : array() as $kandidaat ) {
                if ( empty( $kandidaat->tag_name ) || ! empty( $kandidaat->draft ) ) {
                    continue;
                }
                if ( null === $beste || version_compare( ltrim( $kandidaat->tag_name, 'vV' ), ltrim( $beste->tag_name, 'vV' ), '>' ) ) {
                    $beste = $kandidaat;
                }
            }
            $body = $beste;
        }
        if ( ! is_object( $body ) || empty( $body->tag_name ) ) {
            return false;
        }

        // Strip leading "v" from tag (e.g. "v1.9.5" -> "1.9.5").
        $body->version = ltrim( $body->tag_name, 'vV' );

        // Find a .zip asset; fall back to GitHub's auto-generated zipball.
        $body->download_url = '';
        if ( ! empty( $body->assets ) && is_array( $body->assets ) ) {
            foreach ( $body->assets as $asset ) {
                if ( ! empty( $asset->browser_download_url ) && '.zip' === substr( $asset->browser_download_url, -4 ) ) {
                    $body->download_url = $asset->browser_download_url;
                    break;
                }
            }
        }
        if ( empty( $body->download_url ) ) {
            $body->download_url = isset( $body->zipball_url ) ? $body->zipball_url : '';
        }

        set_transient( $key, $body, 12 * HOUR_IN_SECONDS );

        return $body;
    }

    /* -----------------------------------------------------------------
     * WordPress update hooks
     * -------------------------------------------------------------- */

    /**
     * Inject update information when a newer version is available.
     *
     * @param  object $transient The update_plugins transient.
     * @return object
     */
    public function check_update( $transient ) {

        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
            $transient->response = array();
        }
        if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
            $transient->no_update = array();
        }

        $release = $this->get_latest_release();

        // GitHub unreachable or rate limited. Report "up to date" instead of
        // dropping out of both lists: staying listed keeps the auto-update
        // toggle visible, and clearing any stale response entry stops
        // WordPress from offering an update we cannot download.
        if ( ! $release ) {
            $transient->no_update[ $this->plugin_slug ] = $this->build_item( $this->plugin_version );
            unset( $transient->response[ $this->plugin_slug ] );

            return $transient;
        }

        if ( version_compare( $release->version, $this->plugin_version, '>' ) ) {
            $transient->response[ $this->plugin_slug ] = $this->build_item(
                $release->version,
                isset( $release->html_url ) ? $release->html_url : '',
                $release->download_url
            );
            unset( $transient->no_update[ $this->plugin_slug ] );

            return $transient;
        }

        // Up to date. WordPress only shows the auto-update toggle for
        // plugins it knows about, which means being listed in either
        // response or no_update. Without this branch the toggle was simply
        // absent on every site that happened to be current. The package is
        // left empty because there is nothing to install.
        $transient->no_update[ $this->plugin_slug ] = $this->build_item(
            $this->plugin_version,
            isset( $release->html_url ) ? $release->html_url : ''
        );
        unset( $transient->response[ $this->plugin_slug ] );

        return $transient;
    }

    /**
     * Provide plugin information for the update details modal.
     *
     * @param  false|object|array $result
     * @param  string             $action
     * @param  object             $args
     * @return false|object
     */
    public function plugin_info( $result, $action, $args ) {

        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        if ( dirname( $this->plugin_slug ) !== $args->slug ) {
            return $result;
        }

        $release = $this->get_latest_release();
        if ( ! $release ) {
            return $result;
        }

        $info = (object) array(
            'name'          => 'SFP Page Config',
            'slug'          => dirname( $this->plugin_slug ),
            'version'       => $release->version,
            'author'        => '<a href="https://schoolforprofessionals.com">School for Professionals</a>',
            'homepage'      => 'https://github.com/' . $this->github_user . '/' . $this->github_repo,
            'download_link' => $release->download_url,
            'sections'      => array(
                'description'  => 'Centrale paginaconfiguratie, cursusdata, sales-page styling, longread-modus en shortcodes voor het School for Professionals netwerk.',
                'changelog'    => nl2br( esc_html( $release->body ) ),
            ),
        );

        return $info;
    }

    /**
     * Rename the extracted directory after installation so it matches
     * the expected plugin folder name.
     *
     * @param  bool  $response
     * @param  array $hook_extra
     * @param  array $result
     * @return array
     */
    public function after_install( $response, $hook_extra, $result ) {

        global $wp_filesystem;

        // Only act on our own plugin.
        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_slug ) {
            return $result;
        }

        $proper_destination = WP_PLUGIN_DIR . '/sfp-page-config/';
        $wp_filesystem->move( $result['destination'], $proper_destination );
        $result['destination'] = $proper_destination;

        // Re-activate the plugin after update.
        activate_plugin( $this->plugin_slug );

        return $result;
    }
}

add_action( 'rest_api_init', 'sfp_page_config_updater_rest' );

/**
 * REST: leeg de cache van de updater en laat WordPress opnieuw kijken.
 *
 *   POST /sfp/v1/updater/ververs
 *
 * Geeft terug welke versie draait, welke versie de updater aanbiedt en op
 * welk kanaal de site staat. Installeert niets.
 */
function sfp_page_config_updater_rest() {
    register_rest_route(
        'sfp/v1',
        '/updater/ververs',
        array(
            'methods'             => 'POST',
            'permission_callback' => function () {
                return current_user_can( 'update_plugins' );
            },
            'callback'            => function () {
                SFP_Page_Config_Updater::wis_cache();
                delete_site_transient( 'update_plugins' );
                if ( ! function_exists( 'wp_update_plugins' ) ) {
                    require_once ABSPATH . 'wp-includes/update.php';
                }
                wp_update_plugins();
                $updates = get_site_transient( 'update_plugins' );
                $slug    = plugin_basename( SFP_PAGE_CONFIG_FILE );
                $aanbod  = is_object( $updates ) && isset( $updates->response[ $slug ] ) ? $updates->response[ $slug ] : null;
                return rest_ensure_response(
                    array(
                        'kanaal'      => '' === SFP_Page_Config_Updater::kanaal() ? 'gewoon' : 'proef',
                        'geinstalleerd' => SFP_PAGE_CONFIG_VERSION,
                        'aangeboden'  => $aanbod ? $aanbod->new_version : '',
                        'pakket'      => $aanbod ? $aanbod->package : '',
                    )
                );
            },
        )
    );
}
