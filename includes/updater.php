<?php
/**
 * SFP Page Config - GitHub Auto-Updater
 *
 * Checks for new releases on GitHub and integrates with the
 * WordPress plugin update mechanism. Modelled after the
 * SFP Tooltip updater.
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
     * Fetch the latest release from GitHub (cached for 12 hours).
     *
     * @return object|false Release object or false on failure.
     */
    private function get_latest_release() {

        $release = get_transient( $this->transient_key );
        if ( false !== $release ) {
            return $release;
        }

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
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
        if ( empty( $body->tag_name ) ) {
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
            $body->download_url = $body->zipball_url;
        }

        set_transient( $this->transient_key, $body, 12 * HOUR_IN_SECONDS );

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

        $release = $this->get_latest_release();
        if ( ! $release ) {
            return $transient;
        }

        if ( version_compare( $release->version, $this->plugin_version, '>' ) ) {
            $transient->response[ $this->plugin_slug ] = (object) array(
                'slug'        => dirname( $this->plugin_slug ),
                'new_version' => $release->version,
                'url'         => $release->html_url,
                'package'     => $release->download_url,
            );
        }

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
