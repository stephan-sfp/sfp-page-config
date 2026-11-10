<?php
/**
 * SFP Page Config - Incompanycalculator: opslag en kenmerk (sinds 3.36.0).
 *
 * Eigen tabel wp_sfp_calculator, een rij per begroting. Elke invulling is een
 * nieuwe rij met een nieuw kenmerk (DPS-B-26-0042, een teller per site en
 * jaar); oude begrotingen blijven bewaard. De rij ontstaat bij stap 8 (zonder
 * contactgegevens); de inzending van het formulier koppelt naam en e-mail.
 *
 * @package SFP_Page_Config
 * @since   3.36.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SFP_CALCULATOR_DB_VERSIE', '2' );

/**
 * Naam van de tabel.
 *
 * @return string
 */
function sfp_calculator_tabel() {
    global $wpdb;
    return $wpdb->prefix . 'sfp_calculator';
}

/**
 * Maak of werk de tabel bij als de versie niet klopt.
 *
 * @return bool
 */
function sfp_calculator_tabel_klaar() {
    static $klaar = null;
    if ( null !== $klaar ) {
        return $klaar;
    }
    if ( SFP_CALCULATOR_DB_VERSIE === (string) get_option( 'sfp_calculator_db', '' ) ) {
        $klaar = true;
        return true;
    }
    global $wpdb;
    $tabel   = sfp_calculator_tabel();
    $charset = $wpdb->get_charset_collate();
    $sql     = "CREATE TABLE {$tabel} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  kenmerk varchar(24) NOT NULL DEFAULT '',
  sleutel char(32) NOT NULL DEFAULT '',
  taal varchar(5) NOT NULL DEFAULT 'nl',
  route char(1) NOT NULL DEFAULT 'A',
  keuzes longtext NULL,
  berekening longtext NULL,
  situatie longtext NULL,
  email varchar(190) NOT NULL DEFAULT '',
  voornaam varchar(100) NOT NULL DEFAULT '',
  achternaam varchar(100) NOT NULL DEFAULT '',
  organisatie varchar(190) NOT NULL DEFAULT '',
  afdeling varchar(190) NOT NULL DEFAULT '',
  functie varchar(190) NOT NULL DEFAULT '',
  nieuwsbrief tinyint(1) NOT NULL DEFAULT 0,
  lead_source_url varchar(500) NOT NULL DEFAULT '',
  utm text NULL,
  totaal_excl int(11) NOT NULL DEFAULT 0,
  proef tinyint(1) NOT NULL DEFAULT 0,
  entry_id bigint(20) unsigned NOT NULL DEFAULT 0,
  gestart_op datetime NULL DEFAULT NULL,
  afgerond_op datetime NULL DEFAULT NULL,
  pdf_status varchar(10) NOT NULL DEFAULT '',
  pdf_pogingen tinyint(3) unsigned NOT NULL DEFAULT 0,
  pdf_volgende datetime NULL DEFAULT NULL,
  pdf_bestand varchar(100) NOT NULL DEFAULT '',
  geanonimiseerd tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY kenmerk (kenmerk),
  KEY email (email),
  KEY gestart_op (gestart_op),
  KEY afgerond_op (afgerond_op),
  KEY pdf_status (pdf_status)
) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    $bestaat = $tabel === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabel ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    if ( $bestaat ) {
        update_option( 'sfp_calculator_db', SFP_CALCULATOR_DB_VERSIE, true );
    }
    $klaar = $bestaat;
    return $klaar;
}

add_action( 'admin_init', 'sfp_calculator_tabel_admin' );

/**
 * Maak de tabel aan zodra een beheerder het beheer opent en het onderdeel aan staat.
 *
 * @return void
 */
function sfp_calculator_tabel_admin() {
    if ( function_exists( 'sfp_page_config_onderdeel_aan' ) && sfp_page_config_onderdeel_aan( 'calculator' ) ) {
        sfp_calculator_tabel_klaar();
    }
}

/**
 * Een nieuw kenmerk: CODE-B-JJ-NNNN, een teller per site en jaar.
 *
 * @return string
 */
function sfp_calculator_nieuw_kenmerk() {
    global $wpdb;
    $code  = sfp_calculator_site_afk();
    $jaar  = wp_date( 'y' );
    $basis = ( '' !== $code ? $code . '-' : '' ) . 'B-' . $jaar . '-';
    $tabel = sfp_calculator_tabel();
    // Het hoogste volgnummer van dit jaar in de tabel; de optie is een vangnet
    // voor rijen die al zijn opgeruimd.
    $hoogste = (string) $wpdb->get_var( $wpdb->prepare( "SELECT kenmerk FROM {$tabel} WHERE kenmerk LIKE %s ORDER BY kenmerk DESC LIMIT 1", $wpdb->esc_like( $basis ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $nr      = '' !== $hoogste ? (int) substr( $hoogste, strlen( $basis ) ) : 0;
    $teller  = get_option( 'sfp_calculator_teller', array() );
    $teller  = is_array( $teller ) ? $teller : array();
    $nr      = max( $nr, isset( $teller[ $basis ] ) ? (int) $teller[ $basis ] : 0 ) + 1;
    $teller  = array( $basis => $nr );
    update_option( 'sfp_calculator_teller', $teller, false );
    return $basis . str_pad( (string) $nr, 4, '0', STR_PAD_LEFT );
}

/**
 * Een rij op kenmerk of id.
 *
 * @param  string     $kolom  kenmerk of id.
 * @param  string|int $waarde Waarde.
 * @return array|null
 */
function sfp_calculator_rij_op( $kolom, $waarde ) {
    global $wpdb;
    if ( ! in_array( $kolom, array( 'kenmerk', 'id' ), true ) || '' === (string) $waarde || ! sfp_calculator_tabel_klaar() ) {
        return null;
    }
    $tabel = sfp_calculator_tabel();
    $rij   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabel} WHERE {$kolom} = %s LIMIT 1", (string) $waarde ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return is_array( $rij ) ? $rij : null;
}

/**
 * Werk velden van een rij bij.
 *
 * @param  int   $id     Rij.
 * @param  array $velden kolom => waarde.
 * @return bool
 */
function sfp_calculator_rij_bij( $id, array $velden ) {
    global $wpdb;
    return false !== $wpdb->update( sfp_calculator_tabel(), $velden, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/**
 * Een json-kolom als array.
 *
 * @param  array  $rij   Rij.
 * @param  string $kolom keuzes, berekening, situatie of utm.
 * @return array
 */
function sfp_calculator_json( array $rij, $kolom ) {
    $w = isset( $rij[ $kolom ] ) ? json_decode( (string) $rij[ $kolom ], true ) : null;
    return is_array( $w ) ? $w : array();
}

/**
 * Klopt de sleutel bij deze rij?
 *
 * @param  array  $rij     Rij.
 * @param  string $sleutel Sleutel uit de link of het formulier.
 * @return bool
 */
function sfp_calculator_sleutel_klopt( array $rij, $sleutel ) {
    return is_string( $sleutel ) && 32 === strlen( $sleutel ) && hash_equals( (string) $rij['sleutel'], $sleutel );
}

/**
 * Tellingen voor het beheer.
 *
 * @return array
 */
function sfp_calculator_tellingen() {
    global $wpdb;
    $leeg = array(
        'gestart'     => 0,
        'afgerond'    => 0,
        'afgerond_30' => 0,
        'route_a'     => 0,
        'route_b'     => 0,
        'pdf_klaar'   => 0,
        'pdf_wacht'   => 0,
        'pdf_mislukt' => 0,
    );
    if ( ! sfp_calculator_tabel_klaar() ) {
        return $leeg;
    }
    $tabel = sfp_calculator_tabel();
    $grens = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
    $rij   = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->prepare(
            "SELECT COUNT(*) AS gestart,
                SUM(afgerond_op IS NOT NULL) AS afgerond,
                SUM(afgerond_op >= %s) AS afgerond_30,
                SUM(afgerond_op IS NOT NULL AND route = 'A') AS route_a,
                SUM(afgerond_op IS NOT NULL AND route = 'B') AS route_b,
                SUM(pdf_status = 'klaar') AS pdf_klaar,
                SUM(pdf_status = 'wacht') AS pdf_wacht,
                SUM(pdf_status = 'mislukt') AS pdf_mislukt
             FROM {$tabel} WHERE proef = 0", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $grens
        ),
        ARRAY_A
    );
    foreach ( $leeg as $k => $v ) {
        $leeg[ $k ] = isset( $rij[ $k ] ) ? (int) $rij[ $k ] : 0;
    }
    return $leeg;
}

/**
 * Een regel in het logboek van de calculator (laatste 50).
 *
 * @param  string $soort    proef, fout of info.
 * @param  string $tekst    Korte beschrijving.
 * @param  array  $gegevens Extra.
 * @return void
 */
function sfp_calculator_log( $soort, $tekst, array $gegevens = array() ) {
    $log = get_option( 'sfp_calculator_log', array() );
    $log = is_array( $log ) ? $log : array();
    array_unshift(
        $log,
        array(
            'tijd'     => gmdate( 'Y-m-d H:i:s' ),
            'soort'    => (string) $soort,
            'tekst'    => (string) $tekst,
            'gegevens' => $gegevens,
        )
    );
    update_option( 'sfp_calculator_log', array_slice( $log, 0, 50 ), false );
}

/* =========================================================================
 * Bewaartermijn en AVG
 * ====================================================================== */

add_action( 'sfp_calculator_dagelijks', 'sfp_calculator_dagelijks' );
add_action( 'init', 'sfp_calculator_dagelijks_plannen' );

/**
 * Plan de dagelijkse taak zolang het onderdeel aan staat of er een tabel is.
 *
 * @return void
 */
function sfp_calculator_dagelijks_plannen() {
    if ( wp_next_scheduled( 'sfp_calculator_dagelijks' ) ) {
        return;
    }
    if ( ( function_exists( 'sfp_page_config_onderdeel_aan' ) && sfp_page_config_onderdeel_aan( 'calculator' ) ) || '' !== (string) get_option( 'sfp_calculator_db', '' ) ) {
        wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'sfp_calculator_dagelijks' );
    }
}

/**
 * Dagelijks: begrotingen zonder inzending na 24 uur weg, pdf weg na de
 * geldigheid, rij na 2 jaar geanonimiseerd.
 *
 * @return void
 */
function sfp_calculator_dagelijks() {
    global $wpdb;
    if ( ! get_option( 'sfp_calculator_db' ) || ! sfp_calculator_tabel_klaar() ) {
        return;
    }
    $tabel = sfp_calculator_tabel();
    // Zonder inzending: geen contact, geen begroting voor iemand.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$tabel} WHERE afgerond_op IS NULL AND gestart_op < %s", gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    $dagen = (int) sfp_calculator_norm()['geldig_dagen'];
    $oud   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabel} WHERE pdf_bestand <> '' AND afgerond_op < %s LIMIT 200", gmdate( 'Y-m-d H:i:s', time() - $dagen * DAY_IN_SECONDS ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    foreach ( (array) $oud as $rij ) {
        sfp_calculator_pdf_weg( $rij );
        sfp_calculator_rij_bij( (int) $rij['id'], array( 'pdf_bestand' => '', 'pdf_status' => 'verlopen' ) );
    }

    $anoniem = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$tabel} WHERE geanonimiseerd = 0 AND afgerond_op < %s LIMIT 200", gmdate( 'Y-m-d H:i:s', time() - 730 * DAY_IN_SECONDS ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    foreach ( (array) $anoniem as $rij ) {
        sfp_calculator_anonimiseer( (int) $rij['id'] );
    }
}

/**
 * Haal de pdf van een rij weg.
 *
 * @param  array $rij Rij.
 * @return void
 */
function sfp_calculator_pdf_weg( array $rij ) {
    $pad = function_exists( 'sfp_calculator_pdf_pad' ) ? sfp_calculator_pdf_pad( $rij ) : '';
    if ( '' !== $pad ) {
        wp_delete_file( $pad );
    }
}

/**
 * Persoonsgegevens van een rij weg; bedragen en keuzes blijven voor de tellingen.
 *
 * @param  int $id Rij.
 * @return void
 */
function sfp_calculator_anonimiseer( $id ) {
    $rij = sfp_calculator_rij_op( 'id', (int) $id );
    if ( ! $rij ) {
        return;
    }
    sfp_calculator_pdf_weg( $rij );
    $keuzes = sfp_calculator_json( $rij, 'keuzes' );
    unset( $keuzes['inkoop'], $keuzes['intern'] );
    sfp_calculator_rij_bij(
        (int) $id,
        array(
            'email'           => '',
            'voornaam'        => '',
            'achternaam'      => '',
            'organisatie'     => '',
            'afdeling'        => '',
            'functie'         => '',
            'situatie'        => wp_json_encode( array() ),
            'keuzes'          => wp_json_encode( $keuzes ),
            'lead_source_url' => '',
            'utm'             => wp_json_encode( array() ),
            'pdf_bestand'     => '',
            'geanonimiseerd'  => 1,
        )
    );
}

add_filter( 'wp_privacy_personal_data_erasers', 'sfp_calculator_privacy_wisser' );
add_filter( 'wp_privacy_personal_data_exporters', 'sfp_calculator_privacy_exporteur' );

/**
 * Wisser voor Extra, Persoonsgegevens wissen.
 *
 * @param  array $wissers Wissers.
 * @return array
 */
function sfp_calculator_privacy_wisser( $wissers ) {
    $wissers['sfp-calculator'] = array(
        'eraser_friendly_name' => 'SFP Incompanycalculator',
        'callback'             => 'sfp_calculator_wis_email',
    );
    return $wissers;
}

/**
 * Wis alle begrotingen van een e-mailadres.
 *
 * @param  string $email E-mailadres.
 * @return array
 */
function sfp_calculator_wis_email( $email ) {
    global $wpdb;
    $aantal = 0;
    if ( is_email( $email ) && sfp_calculator_tabel_klaar() ) {
        $tabel = sfp_calculator_tabel();
        $ids   = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$tabel} WHERE email = %s", strtolower( $email ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        foreach ( (array) $ids as $id ) {
            sfp_calculator_anonimiseer( (int) $id );
            $aantal++;
        }
    }
    return array(
        'items_removed'  => $aantal > 0,
        'items_retained' => false,
        'messages'       => array(),
        'done'           => true,
        'aantal'         => $aantal,
    );
}

/**
 * Exporteur voor Extra, Persoonsgegevens exporteren.
 *
 * @param  array $exporteurs Exporteurs.
 * @return array
 */
function sfp_calculator_privacy_exporteur( $exporteurs ) {
    $exporteurs['sfp-calculator'] = array(
        'exporter_friendly_name' => 'SFP Incompanycalculator',
        'callback'               => 'sfp_calculator_exporteer_email',
    );
    return $exporteurs;
}

/**
 * Exporteer de begrotingen van een e-mailadres.
 *
 * @param  string $email E-mailadres.
 * @return array
 */
function sfp_calculator_exporteer_email( $email ) {
    global $wpdb;
    $items = array();
    if ( is_email( $email ) && sfp_calculator_tabel_klaar() ) {
        $tabel = sfp_calculator_tabel();
        $rijen = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabel} WHERE email = %s", strtolower( $email ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        foreach ( (array) $rijen as $rij ) {
            $data = array();
            foreach ( array( 'kenmerk', 'voornaam', 'achternaam', 'organisatie', 'afdeling', 'functie', 'afgerond_op', 'totaal_excl' ) as $kolom ) {
                $data[] = array(
                    'name'  => $kolom,
                    'value' => (string) $rij[ $kolom ],
                );
            }
            $data[]  = array(
                'name'  => 'situatie',
                'value' => (string) $rij['situatie'],
            );
            $items[] = array(
                'group_id'    => 'sfp-calculator',
                'group_label' => 'Incompanybegrotingen',
                'item_id'     => 'sfp-calculator-' . $rij['id'],
                'data'        => $data,
            );
        }
    }
    return array(
        'data' => $items,
        'done' => true,
    );
}
