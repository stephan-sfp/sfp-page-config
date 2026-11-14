<?php
/**
 * Auteurskop: foto 144 px in een meegroeiende kolom (3.56.1, 3.56.2).
 */
$fout = 0; $ok = 0;
function check( $cond, $msg ) { global $fout, $ok; if ( $cond ) { $ok++; } else { $fout++; echo "FOUT: $msg\n"; } }
$css = file_get_contents( __DIR__ . '/../assets/blokken/nlvorm-archief.css' );
check( false !== strpos( $css, ':root .sfp-auteur{display:grid;grid-template-columns:144px minmax(0,1fr);column-gap:28px;' ), 'kolom 144 px, gap 28' );
check( false !== strpos( $css, ':root .sfp-auteur .sfp-auteur__foto{display:block;width:144px!important;height:144px!important;max-width:none!important;max-height:none!important;' ), 'foto 144 px, wint van een inline stijl' );
check( false !== strpos( $css, ':root .sfp-auteur{grid-template-columns:96px minmax(0,1fr);column-gap:18px;padding:20px}' ), 'telefoon kolom 96 px' );
check( false !== strpos( $css, ':root .sfp-auteur .sfp-auteur__foto{width:96px!important;height:96px!important}' ), 'telefoon foto 96 px, wint van een inline stijl' );
check( false === strpos( $css, 'sfp-auteur{display:grid;grid-template-columns:72px' ), 'oude kolom van 72 px weg' );
// De auteurskaart onder artikelen blijft ongewijzigd.
check( false === strpos( $css, 'sfp-art-auteur' ), 'auteurskaart artikelen niet in deze opmaak' );
echo "$ok controles, $fout fouten\n";
exit( $fout ? 1 : 0 );
