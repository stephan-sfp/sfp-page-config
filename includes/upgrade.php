<?php
/**
 * SFP Page Config - Eenmalige taken na een nieuwe versie.
 *
 * Draait één keer per versie (optie sfp_page_config_version) op init, niet
 * op plugins_loaded. Elke taak is veilig om twee keer te draaien.
 *
 * 3.40.0: elk artikel krijgt eenmalig Inhoudelijk herzien op = laatste opslag.
 * 3.38.0: de formulieren van het snippet Formulieren: mail-link als fallback
 *         gaan naar SFP Beheer, Formulierregel.
 * 3.37.0: de logolijst van de eerste logostrook op de home gaat naar SFP
 *   Beheer, Logostrook (één bron per site).
 *
 * 3.22.0:
 *   - eigen reviews (sfp_reviews_manual) laden niet meer bij elke pagina;
 *   - de publicaties tellen vanaf nu in de dagelijkse taak: een keer direct;
 *   - de lijst komende startmomenten opnieuw opbouwen;
 *   - de testmap en .github van een oud pakket staan niet meer op de site.
 *
 * Tot 3.21.0 stond hier (in ajax-lean.php) alleen het opruimen van een optie
 * uit 2.10.0; dat is op alle sites gebeurd.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'sfp_page_config_upgrade', 5 );

/**
 * Voer de taken uit als de opgeslagen versie anders is.
 */
function sfp_page_config_upgrade() {
    $was = (string) get_option( 'sfp_page_config_version', '' );
    if ( SFP_PAGE_CONFIG_VERSION === $was ) {
        return;
    }
    if ( '' === $was || version_compare( $was, '3.22.0', '<' ) ) {
        if ( function_exists( 'wp_set_option_autoload' ) && false !== get_option( 'sfp_reviews_manual', false ) ) {
            wp_set_option_autoload( 'sfp_reviews_manual', false );
        }
        if ( ! wp_next_scheduled( 'sfp_feiten_publicaties_tel_nu' ) ) {
            wp_schedule_single_event( time() + 60, 'sfp_feiten_publicaties_tel_nu' );
        }
        delete_option( 'sfp_pc_komende_cursussen' );
        sfp_page_config_upgrade_oude_mappen();
    }
    if ( '' === $was || version_compare( $was, '3.37.0', '<' ) ) {
        if ( function_exists( 'sfp_page_config_logostrook_overnemen' ) ) {
            sfp_page_config_logostrook_overnemen();
        }
    }
    if ( '' === $was || version_compare( $was, '3.38.0', '<' ) ) {
        if ( function_exists( 'sfp_page_config_formulierregel_overnemen' ) ) {
            sfp_page_config_formulierregel_overnemen();
        }
    }
    if ( '' !== $was && version_compare( $was, '3.40.0', '<' ) ) {
        if ( function_exists( 'sfp_page_config_herzien_startvulling' ) ) {
            sfp_page_config_herzien_startvulling();
        }
    }
    if ( '' !== $was && version_compare( $was, '3.41.2', '<' ) && function_exists( 'sfp_page_config_reviews_opties' ) ) {
        // De vaste kopie van de reviews vanuit de transient, als die er nog is.
        $opts = sfp_page_config_reviews_opties();
        $data = '' !== (string) $opts['place_id'] ? get_transient( 'sfp_reviews_' . md5( (string) $opts['place_id'] ) ) : false;
        if ( is_array( $data ) && ! get_option( 'sfp_reviews_laatste' ) ) {
            update_option( 'sfp_reviews_laatste', array( 'place' => md5( (string) $opts['place_id'] ), 'data' => $data ), false );
        }
    }
    update_option( 'sfp_page_config_version', SFP_PAGE_CONFIG_VERSION, true );
}

/**
 * Een site die voor 3.22.0 het hele archief van GitHub kreeg, heeft tests/
 * en .github/ nog in de pluginmap als het pakket over een bestaande map is
 * gezet. Die horen er niet: weg, alleen binnen de eigen map.
 */
function sfp_page_config_upgrade_oude_mappen() {
    foreach ( array( 'tests', '.github' ) as $map ) {
        $pad = SFP_PAGE_CONFIG_DIR . $map;
        if ( ! is_dir( $pad ) || 0 !== strpos( realpath( $pad ), realpath( SFP_PAGE_CONFIG_DIR ) ) ) {
            continue;
        }
        $items = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $pad, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
        foreach ( $items as $item ) {
            if ( $item->isDir() ) {
                @rmdir( $item->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
            } else {
                wp_delete_file( $item->getPathname() );
            }
        }
        @rmdir( $pad ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
    }
}
