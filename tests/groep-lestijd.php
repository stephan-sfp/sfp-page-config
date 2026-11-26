<?php
/**
 * Lestijd op de groepskaart en de wachtlijstkaart (3.80.0).
 *
 * Gebruik: php tests/groep-lestijd.php
 */
$fout = 0;
$goed = 0;
$is   = function ( $verwacht, $echt, $wat ) use ( &$fout, &$goed ) {
	if ( $verwacht === $echt ) { ++$goed; } else { ++$fout; echo "FOUT: $wat\n  verwacht: " . var_export( $verwacht, true ) . "\n  kreeg:    " . var_export( $echt, true ) . "\n"; }
};

// Stubs.
$GLOBALS['inst']    = array();
$GLOBALS['teksten'] = array( 'tijd_teken' => '.', 'tijd_uur' => 'uur' );
function sfp_page_config_get_setting( $k, $d = '' ) { return $GLOBALS['inst'][ $k ] ?? $d; }
function sfp_page_config_tekst( $k ) { return $GLOBALS['teksten'][ $k ] ?? ''; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function sfp_page_config_sanitize_time( $v ) {
	$v = str_replace( '.', ':', trim( (string) $v ) );
	if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', $v, $m ) || (int) $m[1] > 23 || (int) $m[2] > 59 ) { return ''; }
	return sprintf( '%02d:%02d', $m[1], $m[2] );
}

// Alleen de twee nieuwe functies uit nlvorm.php laden.
$s   = file_get_contents( dirname( __DIR__ ) . '/includes/nlvorm.php' );
$van = strpos( $s, 'function sfp_page_config_lestijd_kort(' );
$tot = strpos( $s, '/**', strpos( $s, 'function sfp_page_config_groep_lestijd_html(' ) );
eval( substr( $s, $van, $tot - $van ) );

$is( '09.00-17.00 uur', sfp_page_config_lestijd_kort( '09:00', '17:00', '.', 'uur' ), 'nl: punt en uur' );
$is( '09:00-17:00', sfp_page_config_lestijd_kort( '09:00', '17:00', ':', '' ), 'en: dubbele punt, geen achtervoegsel' );
$is( '09:30-16:00', sfp_page_config_lestijd_kort( '9.30', '16:00', '', '' ), 'leeg teken wordt dubbele punt, 9.30 wordt 09:30' );
$is( '', sfp_page_config_lestijd_kort( '09:00', '', '.', 'uur' ), 'geen einde: niets' );
$is( '', sfp_page_config_lestijd_kort( 'x', '17:00', '.', 'uur' ), 'ongeldig begin: niets' );

$is( '<p class="sfp-groep__meta sfp-groep__tijd">09.00-17.00 uur</p>', sfp_page_config_groep_lestijd_html( '09:00', '17:00' ), 'groep met lestijd' );
$is( '', sfp_page_config_groep_lestijd_html( '', '' ), 'geen groepstijd en geen standaard: geen regel' );
$GLOBALS['inst'] = array( 'cursus_tijd_default_start' => '09:00', 'cursus_tijd_default_eind' => '17:00' );
$is( '<p class="sfp-groep__meta sfp-groep__tijd">09.00-17.00 uur</p>', sfp_page_config_groep_lestijd_html( '', '' ), 'terugval op standaardlestijd' );
$is( '<p class="sfp-groep__meta sfp-groep__tijd">10.00-16.00 uur</p>', sfp_page_config_groep_lestijd_html( '10:00', '16:00' ), 'groepstijd gaat voor standaard' );
$is( '<p class="sfp-groep__meta sfp-groep__tijd">09.00-17.00 uur</p>', sfp_page_config_groep_lestijd_html( '10:00', '' ), 'halve groepstijd: standaard' );

// Plek in de render: groepskaart onder de dagen, wachtlijstkaart onder de zin.
$is( 1, substr_count( $s, "'</ul>'\n            . sfp_page_config_groep_lestijd_html( \$g['start'], \$g['eind'] )" ), 'groepskaart: regel direct onder de dagen' );
$is( 1, substr_count( $s, "groep_wachtlijst_zin' ) ) . '</p>'\n            . sfp_page_config_groep_lestijd_html( '', '' )" ), 'wachtlijstkaart: regel onder de zin' );

// Tekst bestaat in drie talen.
$t = file_get_contents( dirname( __DIR__ ) . '/includes/teksten.php' );
$is( 1, preg_match( "/'tijd_teken'\\s*=> array\\([^)]*'nl'\\s*=> '\\.',[^)]*'en'\\s*=> ':',[^)]*'pt'\\s*=> ':'/s", $t ), 'tijd_teken in nl, en, pt' );

echo "groep-lestijd: $goed goed, $fout fout\n";
exit( $fout ? 1 : 0 );
