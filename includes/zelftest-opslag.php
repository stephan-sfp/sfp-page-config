<?php
/**
 * SFP Page Config - Zelftest: opslag
 *
 * Eén tabel, {prefix}sfp_zelftest, met een rij per gestarte test. Opnieuw
 * doen is een nieuwe rij met een nieuwe code. Tijden in UTC.
 *
 * @package SFP_Page_Config
 * @since   3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SFP_ZELFTEST_DB_VERSIE', '1' );

/**
 * Naam van de tabel.
 *
 * @return string
 */
function sfp_zelftest_tabel() {
    global $wpdb;
    return $wpdb->prefix . 'sfp_zelftest';
}

/**
 * Maak of werk de tabel bij als de versie niet klopt.
 *
 * @return bool De tabel is er.
 */
function sfp_zelftest_tabel_klaar() {
    static $klaar = null;
    if ( null !== $klaar ) {
        return $klaar;
    }
    if ( SFP_ZELFTEST_DB_VERSIE === (string) get_option( 'sfp_zelftest_db', '' ) ) {
        $klaar = true;
        return true;
    }
    global $wpdb;
    $tabel   = sfp_zelftest_tabel();
    $charset = $wpdb->get_charset_collate();
    $sql     = "CREATE TABLE {$tabel} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  test varchar(40) NOT NULL DEFAULT '',
  pakket_versie varchar(40) NOT NULL DEFAULT '',
  sessie char(32) NOT NULL DEFAULT '',
  code varchar(24) DEFAULT NULL,
  deelcode varchar(16) DEFAULT NULL,
  email varchar(190) NOT NULL DEFAULT '',
  voornaam varchar(100) NOT NULL DEFAULT '',
  lengte varchar(5) NOT NULL DEFAULT 'long',
  antwoorden longtext NULL,
  uitkomst text NULL,
  nieuwsbrief tinyint(1) NOT NULL DEFAULT 0,
  lead_source_url varchar(500) NOT NULL DEFAULT '',
  utm_source varchar(200) NOT NULL DEFAULT '',
  utm_medium varchar(200) NOT NULL DEFAULT '',
  utm_campaign varchar(200) NOT NULL DEFAULT '',
  gclid varchar(200) NOT NULL DEFAULT '',
  extra text NULL,
  gestart_op datetime NULL DEFAULT NULL,
  afgerond_op datetime NULL DEFAULT NULL,
  duur_sec int(11) DEFAULT NULL,
  crm_start varchar(10) NOT NULL DEFAULT '',
  crm_afgerond varchar(10) NOT NULL DEFAULT '',
  crm_pogingen tinyint(3) unsigned NOT NULL DEFAULT 0,
  crm_volgende datetime NULL DEFAULT NULL,
  pdf_status varchar(10) NOT NULL DEFAULT '',
  pdf_pogingen tinyint(3) unsigned NOT NULL DEFAULT 0,
  pdf_volgende datetime NULL DEFAULT NULL,
  pdf_bestand varchar(100) NOT NULL DEFAULT '',
  pdf_eerste_klik datetime NULL DEFAULT NULL,
  deelbeelden text NULL,
  opnieuw_gestuurd tinyint(3) unsigned NOT NULL DEFAULT 0,
  geanonimiseerd tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY code (code),
  UNIQUE KEY deelcode (deelcode),
  KEY sessie (sessie),
  KEY email (email),
  KEY test_afgerond (test,afgerond_op),
  KEY gestart_op (gestart_op),
  KEY crm (crm_start,crm_afgerond),
  KEY pdf_status (pdf_status)
) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    $bestaat = $tabel === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabel ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    if ( $bestaat ) {
        update_option( 'sfp_zelftest_db', SFP_ZELFTEST_DB_VERSIE, true );
    }
    $klaar = $bestaat;
    return $klaar;
}

add_action( 'admin_init', 'sfp_zelftest_tabel_admin' );

/**
 * Maak de tabel aan zodra een beheerder het beheer opent en het onderdeel
 * aan staat.
 */
function sfp_zelftest_tabel_admin() {
    if ( function_exists( 'sfp_page_config_onderdeel_aan' ) && sfp_page_config_onderdeel_aan( 'zelftest' ) ) {
        sfp_zelftest_tabel_klaar();
    }
}

/**
 * Nu in UTC, als MySQL-datum.
 *
 * @param  int $plus Seconden erbij.
 * @return string
 */
function sfp_zelftest_nu( $plus = 0 ) {
    return gmdate( 'Y-m-d H:i:s', time() + (int) $plus );
}

/**
 * Een willekeurige tekenreeks uit Crockford-base32 (zonder I, L, O, U).
 *
 * @param  int $lengte Aantal tekens.
 * @return string
 */
function sfp_zelftest_willekeurig( $lengte ) {
    $tekens = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    $uit    = '';
    $bytes  = random_bytes( $lengte );
    for ( $i = 0; $i < $lengte; $i++ ) {
        $uit .= $tekens[ ord( $bytes[ $i ] ) & 31 ];
    }
    return $uit;
}

/**
 * Een nieuwe resultaatcode: VOORVOEGSEL-XXXX-XXXX-XXXX (60 bits), uniek.
 *
 * @param  string $voorvoegsel Hoofdletters uit het manifest.
 * @return string
 */
function sfp_zelftest_nieuwe_code( $voorvoegsel ) {
    global $wpdb;
    $tabel = sfp_zelftest_tabel();
    for ( $poging = 0; $poging < 5; $poging++ ) {
        $t    = sfp_zelftest_willekeurig( 12 );
        $code = $voorvoegsel . '-' . substr( $t, 0, 4 ) . '-' . substr( $t, 4, 4 ) . '-' . substr( $t, 8, 4 );
        if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tabel} WHERE code = %s", $code ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            return $code;
        }
    }
    return $voorvoegsel . '-' . sfp_zelftest_willekeurig( 4 ) . '-' . sfp_zelftest_willekeurig( 4 ) . '-' . sfp_zelftest_willekeurig( 4 );
}

/**
 * Een nieuwe deelcode (14 tekens, 70 bits), uniek. Opent nooit de pdf.
 *
 * @return string
 */
function sfp_zelftest_nieuwe_deelcode() {
    global $wpdb;
    $tabel = sfp_zelftest_tabel();
    for ( $poging = 0; $poging < 5; $poging++ ) {
        $code = sfp_zelftest_willekeurig( 14 );
        if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tabel} WHERE deelcode = %s", $code ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            return $code;
        }
    }
    return sfp_zelftest_willekeurig( 14 );
}

/**
 * Een rij op een kolom.
 *
 * @param  string $kolom code, deelcode of sessie.
 * @param  string $waarde Waarde.
 * @return array|null
 */
function sfp_zelftest_rij_op( $kolom, $waarde ) {
    global $wpdb;
    if ( ! in_array( $kolom, array( 'code', 'deelcode', 'sessie', 'id' ), true ) || '' === (string) $waarde || ! sfp_zelftest_tabel_klaar() ) {
        return null;
    }
    $tabel = sfp_zelftest_tabel();
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
function sfp_zelftest_rij_bij( $id, array $velden ) {
    global $wpdb;
    return false !== $wpdb->update( sfp_zelftest_tabel(), $velden, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/**
 * Antwoorden en uitkomst van een rij als array.
 *
 * @param  array  $rij   Rij.
 * @param  string $kolom antwoorden, uitkomst, extra of deelbeelden.
 * @return array
 */
function sfp_zelftest_json( array $rij, $kolom ) {
    $w = isset( $rij[ $kolom ] ) ? json_decode( (string) $rij[ $kolom ], true ) : null;
    return is_array( $w ) ? $w : array();
}

/**
 * Tellingen voor het beheer.
 *
 * @param  string $test Naam.
 * @return array
 */
function sfp_zelftest_tellingen( $test ) {
    global $wpdb;
    $leeg = array( 'gestart' => 0, 'afgerond' => 0, 'gestart_30' => 0, 'afgerond_30' => 0, 'crm_wacht' => 0, 'crm_mislukt' => 0, 'pdf_wacht' => 0, 'pdf_mislukt' => 0, 'pdf_klaar' => 0 );
    if ( ! sfp_zelftest_tabel_klaar() ) {
        return $leeg;
    }
    $tabel = sfp_zelftest_tabel();
    $grens = sfp_zelftest_nu( -30 * DAY_IN_SECONDS );
    // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $rij = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT COUNT(*) AS gestart,
                SUM(afgerond_op IS NOT NULL) AS afgerond,
                SUM(gestart_op >= %s) AS gestart_30,
                SUM(afgerond_op >= %s) AS afgerond_30,
                SUM(crm_start = 'wacht' OR crm_afgerond = 'wacht') AS crm_wacht,
                SUM(crm_start = 'mislukt' OR crm_afgerond = 'mislukt') AS crm_mislukt,
                SUM(pdf_status IN ('wacht','bezig')) AS pdf_wacht,
                SUM(pdf_status = 'mislukt') AS pdf_mislukt,
                SUM(pdf_status = 'klaar') AS pdf_klaar
             FROM {$tabel} WHERE test = %s AND geanonimiseerd = 0",
            $grens,
            $grens,
            $test
        ),
        ARRAY_A
    );
    // phpcs:enable
    if ( ! is_array( $rij ) ) {
        return $leeg;
    }
    return array_map( 'intval', array_merge( $leeg, array_filter( $rij, 'is_numeric' ) ) );
}

/* =========================================================================
 * Logboek (proefmodus en fouten), laatste 50 regels
 * ====================================================================== */

/**
 * Schrijf een regel in het logboek van de zelftest.
 *
 * @param  string $test   Naam.
 * @param  string $soort  proef, fout of info.
 * @param  string $tekst  Korte beschrijving.
 * @param  array  $gegevens Extra (bijvoorbeeld de payload in proefmodus).
 * @return void
 */
function sfp_zelftest_log( $test, $soort, $tekst, array $gegevens = array() ) {
    $log = get_option( 'sfp_zelftest_log', array() );
    $log = is_array( $log ) ? $log : array();
    array_unshift(
        $log,
        array(
            'tijd'     => sfp_zelftest_nu(),
            'test'     => (string) $test,
            'soort'    => (string) $soort,
            'tekst'    => (string) $tekst,
            'gegevens' => $gegevens,
        )
    );
    update_option( 'sfp_zelftest_log', array_slice( $log, 0, 50 ), false );
}
