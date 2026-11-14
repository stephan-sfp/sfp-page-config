<?php
/**
 * Auteurskop: foto 144 px in een meegroeiende kolom (3.56.1).
 */
$fout = 0; $ok = 0;
function check( $cond, $msg ) { global $fout, $ok; if ( $cond ) { $ok++; } else { $fout++; echo "FOUT: $msg\n"; } }
$css = file_get_contents( __DIR__ . '/../assets/blokken/nlvorm-archief.css' );
check( false !== strpos( $css, ':root .sfp-auteur{display:grid;grid-template-columns:144px minmax(0,1fr);column-gap:28px;' ), 'kolom 144 px, gap 28' );
check( false !== strpos( $css, ':root .sfp-auteur .sfp-auteur__foto{display:block;width:144px;height:144px;max-width:none;max-height:none;' ), 'foto 144 px' );
check( false !== strpos( $css, ':root .sfp-auteur{grid-template-columns:96px minmax(0,1fr);column-gap:18px;padding:20px}' ), 'telefoon kolom 96 px' );
check( false !== strpos( $css, ':root .sfp-auteur .sfp-auteur__foto{width:96px;height:96px}' ), 'telefoon foto 96 px' );
check( false === strpos( $css, 'sfp-auteur{display:grid;grid-template-columns:72px' ), 'oude kolom van 72 px weg' );
// De auteurskaart onder artikelen blijft ongewijzigd.
$php = file_get_contents( __DIR__ . '/../includes/nlvorm-archief.php' );
check( 1 === preg_match( '/preg_replace\( \'(.+?)\', \'\', \$foto, 1 \)/', $php, $m ), 'inline stijl wordt verwijderd' );
$avatar = '<img style="max-width:144px;max-height:144px;width:auto;height:auto;" alt=\'Stephan\' src=\'x.jpg\' class=\'avatar sfp-auteur__foto\' height=\'144\' width=\'144\' />';
$schoon = preg_replace( str_replace( "\\'", "'", $m[1] ), '', $avatar, 1 );
check( false === strpos( $schoon, 'style=' ), 'geen style meer op de foto' );
check( false !== strpos( $schoon, "class='avatar sfp-auteur__foto'" ) && false !== strpos( $schoon, "width='144'" ), 'overige attributen blijven' );
$zonder = '<img alt="Stephan" src="x.jpg" class="sfp-auteur__foto" />';
check( $zonder === preg_replace( str_replace( "\\'", "'", $m[1] ), '', $zonder, 1 ), 'foto zonder stijl blijft gelijk' );
echo "$ok controles, $fout fouten\n";
exit( $fout ? 1 : 0 );
