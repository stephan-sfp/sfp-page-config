<?php
/** Run with: php tests/beheer.php */
error_reporting( E_ALL );
set_error_handler(
    function ( $severity, $message, $file, $line ) {
        throw new ErrorException( $message, 0, $severity, $file, $line );
    }
);
define( 'ABSPATH', __DIR__ . '/' );
define( 'SFP_PAGE_CONFIG_DIR', dirname( __DIR__ ) . '/' );
define( 'SFP_PAGE_CONFIG_URL', 'https://example.test/wp-content/plugins/sfp-page-config/' );
define( 'SFP_PAGE_CONFIG_VERSION', 'test' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'ENT_HTML5_TEST', 1 );

/* ---------- Kleine WordPress-stubs ---------- */

$GLOBALS['options']    = array();
$GLOBALS['transients'] = array();
$GLOBALS['posts']      = array();
$GLOBALS['meta']       = array();
$GLOBALS['locale']     = 'nl_NL';
$GLOBALS['admin']      = false;

function get_option( $k, $d = false ) {
    return $GLOBALS['options'][ $k ] ?? $d;
}
function update_option( $k, $v ) {
    $GLOBALS['options'][ $k ] = $v;
    return true;
}
function delete_option( $k ) {
    unset( $GLOBALS['options'][ $k ] );
    return true;
}
function get_transient( $k ) {
    return $GLOBALS['transients'][ $k ] ?? false;
}
function set_transient( $k, $v, $t = 0 ) {
    $GLOBALS['transients'][ $k ] = $v;
    return true;
}
function delete_transient( $k ) {
    unset( $GLOBALS['transients'][ $k ] );
    return true;
}
function add_action() {}
function add_filter() {}
function has_action() {
    return false;
}
function do_action() {}
function apply_filters( $hook, $value ) {
    return $value;
}
function is_admin() {
    return $GLOBALS['admin'];
}
function get_locale() {
    return $GLOBALS['locale'];
}
function determine_locale() {
    return $GLOBALS['locale'];
}
function home_url( $path = '' ) {
    return 'https://example.test' . $path;
}
function wp_unslash( $v ) {
    return is_array( $v ) ? array_map( 'wp_unslash', $v ) : ( is_string( $v ) ? stripslashes( $v ) : $v );
}
function wp_strip_all_tags( $s ) {
    return trim( strip_tags( (string) $s ) );
}
function sanitize_text_field( $s ) {
    return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $s ) ) );
}
function sanitize_textarea_field( $s ) {
    return trim( strip_tags( (string) $s ) );
}
function sanitize_key( $s ) {
    return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) );
}
function sanitize_email( $s ) {
    return filter_var( trim( (string) $s ), FILTER_VALIDATE_EMAIL ) ? trim( (string) $s ) : '';
}
function is_email( $s ) {
    return (bool) filter_var( (string) $s, FILTER_VALIDATE_EMAIL );
}
function absint( $n ) {
    return abs( (int) $n );
}
function esc_url_raw( $u ) {
    $u = trim( (string) $u );
    return preg_match( '#^(https?:)?//|^/|^\#|^mailto:|^tel:#i', $u ) ? $u : '';
}
function esc_url( $u ) {
    return esc_url_raw( $u );
}
function esc_html( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $s ) {
    return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function wp_json_encode( $v, $flags = 0 ) {
    return json_encode( $v, $flags );
}
function wp_parse_url( $url, $component = -1 ) {
    return parse_url( $url, $component );
}
function wp_date( $format, $ts = null ) {
    return gmdate( $format, null === $ts ? time() : $ts );
}
function current_time( $type ) {
    return 'timestamp' === $type ? time() : gmdate( 'Y-m-d H:i:s' );
}
function date_i18n( $format, $ts = false ) {
    return gmdate( $format, false === $ts ? time() : $ts );
}
function get_post_status( $id ) {
    return $GLOBALS['posts'][ $id ]['status'] ?? false;
}
function get_post_type( $id ) {
    return $GLOBALS['posts'][ $id ]['type'] ?? false;
}
function get_permalink( $id ) {
    return isset( $GLOBALS['posts'][ $id ] ) ? home_url( '/' . $GLOBALS['posts'][ $id ]['slug'] . '/' ) : false;
}
function get_the_title( $id ) {
    return $GLOBALS['posts'][ $id ]['titel'] ?? '';
}
function url_to_postid( $url ) {
    foreach ( $GLOBALS['posts'] as $id => $p ) {
        if ( rtrim( $url, '/' ) === rtrim( home_url( '/' . $p['slug'] . '/' ), '/' ) || rtrim( $url, '/' ) === '/' . $p['slug'] ) {
            return $id;
        }
    }
    return 0;
}
function wp_get_sidebars_widgets() {
    return $GLOBALS['options']['sidebars_widgets'] ?? array();
}
function get_post_meta( $id, $key = '', $single = false ) {
    return $GLOBALS['meta'][ $id ][ $key ] ?? '';
}
function update_post_meta( $id, $key, $value ) {
    $GLOBALS['meta'][ $id ][ $key ] = is_string( $value ) ? stripslashes( $value ) : $value;
    return true;
}
function delete_post_meta( $id, $key ) {
    unset( $GLOBALS['meta'][ $id ][ $key ] );
    return true;
}
function wp_slash( $v ) {
    return is_string( $v ) ? addslashes( $v ) : $v;
}
function get_post( $id ) {
    return null;
}
function sfp_page_config_sanitize_time( $t ) {
    return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', (string) $t ) ? (string) $t : '';
}
function sfp_page_config_sanitize_css_color( $c ) {
    return (string) $c;
}
function sfp_page_config_format_date_nl( $date, $format = 'short' ) {
    return 'nl:' . $date;
}

$checks = 0;
function same( $expected, $actual, $message ) {
    global $checks;
    ++$checks;
    if ( $expected !== $actual ) {
        throw new RuntimeException( $message . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
    }
}
function instellingen() {
    return $GLOBALS['options']['sfp_settings'] ?? array();
}

require dirname( __DIR__ ) . '/includes/instellingen.php';
require dirname( __DIR__ ) . '/includes/teksten.php';
require dirname( __DIR__ ) . '/includes/artikel-teksten.php';
require dirname( __DIR__ ) . '/includes/cursusdata.php';
require dirname( __DIR__ ) . '/includes/footer-links.php';
require dirname( __DIR__ ) . '/includes/body-class.php';
require dirname( __DIR__ ) . '/includes/beheer-paginas.php';
require dirname( __DIR__ ) . '/includes/reviews/class-sfp-reviews-manual.php';

$GLOBALS['posts'] = array(
    4 => array( 'status' => 'publish', 'type' => 'page', 'slug' => 'contact', 'titel' => 'Contact' ),
    5 => array( 'status' => 'draft', 'type' => 'page', 'slug' => 'concept', 'titel' => 'Concept' ),
    6 => array( 'status' => 'publish', 'type' => 'page', 'slug' => 'privacy', 'titel' => 'Privacyverklaring' ),
    7 => array( 'status' => 'publish', 'type' => 'post', 'slug' => 'artikel', 'titel' => 'Artikel' ),
);

/* ---------- Teksten per taal ---------- */

same( 'nl', sfp_page_config_taal(), 'nl_NL is Nederlands' );
same( 'Boek je gratis proefsessie', sfp_page_config_tekst( 'knop_coaching' ), 'Nederlandse standaard gelijk aan 2.12.0' );
same( 'Plan een kennismaking in', sfp_page_config_tekst( 'knop_training' ), 'Nederlandse standaard training gelijk aan 2.12.0' );
same( 'Groep 2', sfp_page_config_tekst_f( 'groep', 2 ), 'Groep met nummer' );
$GLOBALS['locale'] = 'en_GB';
same( 'en', sfp_page_config_taal(), 'en_GB is Engels' );
same( 'Group 3', sfp_page_config_tekst_f( 'groep', 3 ), 'Engelse groep' );
$GLOBALS['locale'] = 'pt_BR';
same( 'pt', sfp_page_config_taal(), 'pt_BR is Portugees' );
same( 'Turma 1', sfp_page_config_tekst_f( 'groep', 1 ), 'Portugese groep' );
$GLOBALS['locale'] = 'de_DE';
same( 'en', sfp_page_config_taal(), 'Een andere taal valt terug op Engels, zoals de artikelteksten' );
$GLOBALS['locale'] = 'nl_NL';

// Elke tekst heeft een standaard in alle drie de talen, met dezelfde invulplekken.
foreach ( sfp_page_config_teksten_register() as $sleutel => $tekst ) {
    $plekken = null;
    foreach ( array( 'nl', 'en', 'pt' ) as $taal ) {
        $standaard = sfp_page_config_tekst_standaard( $sleutel, $taal );
        $vrij_leeg = in_array( $sleutel, array( 'affiliate_regel', 'affiliate_uitleg', 'tijd_uur' ), true );
        if ( ! $vrij_leeg ) {
            same( true, '' !== $standaard, "Tekst $sleutel heeft een standaard in $taal" );
        }
        preg_match_all( '/%(?:\d+\$)?[sd]/', $standaard, $m );
        $aantal = count( $m[0] );
        if ( null === $plekken || '' === $standaard ) {
            $plekken = '' === $standaard ? $plekken : $aantal;
        } else {
            same( $plekken, $aantal, "Tekst $sleutel heeft in $taal evenveel invulplekken" );
        }
    }
}

// Een eigen tekst met een kapotte invulplek valt terug op de standaard.
sfp_page_config_instellingen_bewaar( array( 'teksten' => array( 'groep' => 'Ploeg %d van %s en %s' ) ) );
same( 'Groep 4', sfp_page_config_tekst_f( 'groep', 4 ), 'Eigen tekst met te veel invulplekken valt terug' );
sfp_page_config_instellingen_bewaar( array( 'teksten' => array( 'groep' => 'Ploeg %d' ) ) );
same( 'Ploeg 4', sfp_page_config_tekst_f( 'groep', 4 ), 'Eigen tekst wordt gebruikt' );
sfp_page_config_instellingen_bewaar( array( 'teksten' => array( 'groep' => '' ) ) );
same( 'Groep 4', sfp_page_config_tekst_f( 'groep', 4 ), 'Leeg betekent de standaard' );

/* ---------- Instellingen bewaren ---------- */

// Onbekende sleutels in de opslag blijven staan; onbekende invoer wordt overgeslagen.
$GLOBALS['options']['sfp_settings'] = array(
    'iets_van_vroeger' => 'blijft',
    'lr_brand'         => '#ff3c38',
    'artikel_aan'      => '',
);
$bewaard = sfp_page_config_instellingen_bewaar(
    array(
        'whatsapp_number' => '+31 85 060 13 55',
        'onbekend'        => 'x',
        'onderdelen'      => array(
            'whatsapp' => true,
            'artikel'  => true,
            'nep'      => true,
        ),
    )
);
$alle = instellingen();
same( 'blijft', $alle['iets_van_vroeger'], 'Onbekende bewaarde sleutel blijft staan' );
same( '#ff3c38', $alle['lr_brand'], 'Oude kleurinstelling wordt niet gewist (alleen niet meer toegepast)' );
same( false, isset( $alle['onbekend'] ), 'Onbekende invoer wordt niet bewaard' );
same( false, in_array( 'onbekend', $bewaard, true ), 'Onbekende invoer staat niet in de bewaarde sleutels' );
same( '1', $alle['onderdelen']['whatsapp'], 'Onderdeel aan' );
same( false, isset( $alle['onderdelen']['nep'] ), 'Onbekend onderdeel wordt overgeslagen' );
same( '1', $alle['artikel_aan'], 'Onderdeel artikel schrijft artikel_aan' );
same( true, sfp_page_config_onderdeel_aan( 'artikel' ), 'Artikelen aan' );
same( true, sfp_page_config_onderdeel_aan( 'whatsapp' ), 'WhatsApp aan' );
sfp_page_config_instellingen_bewaar( array( 'onderdelen' => array( 'artikel' => false, 'whatsapp' => '0' ) ) );
same( false, sfp_page_config_onderdeel_aan( 'artikel' ), 'Artikelen uit' );
same( false, sfp_page_config_onderdeel_aan( 'whatsapp' ), 'WhatsApp uit' );

// Netwerknorm: een nummer dat alleen in schrijfwijze verschilt, is geen afwijking.
same( array(), sfp_page_config_afwijkingen(), 'Zelfde nummer anders geschreven wijkt niet af' );
sfp_page_config_instellingen_bewaar(
    array(
        'whatsapp_number' => '31600000000',
        'update_kanaal'   => 'proef',
        'teksten'         => array( 'knop_coaching' => 'Plan je sessie' ),
    )
);
$paden = array_column( sfp_page_config_afwijkingen(), 'pad' );
sort( $paden );
same( array( 'sticky_cta.coaching.text', 'update_kanaal', 'whatsapp_number' ), $paden, 'Drie afwijkingen van de norm' );
same( 'Plan je sessie', instellingen()['sticky_cta']['coaching']['text'], 'Knoptekst uit Teksten staat op de plek van de knop' );
sfp_page_config_instelling_naar_norm( 'update_kanaal' );
sfp_page_config_instelling_naar_norm( 'whatsapp_number' );
sfp_page_config_instelling_naar_norm( 'sticky_cta.coaching.text' );
same( array(), sfp_page_config_afwijkingen(), 'Terug naar de norm' );
same( 'blijft', instellingen()['iets_van_vroeger'], 'Terug naar de norm laat de rest staan' );
sfp_page_config_instellingen_bewaar( array( 'update_kanaal' => 'nachtelijk' ) );
same( '', (string) ( instellingen()['update_kanaal'] ?? '' ), 'Onbekend updatekanaal wordt gewoon' );

/* ---------- Knop onderaan ---------- */

same( null, sfp_page_config_get_sticky_cta( 'onbekend' ), 'Onbekende soort pagina heeft geen knop' );
$GLOBALS['options']['sfp_settings'] = array();
same(
    array( 'text' => 'Boek je gratis proefsessie', 'doel' => 'form', 'href' => '', 'target' => '_self' ),
    sfp_page_config_get_sticky_cta( 'coaching' ),
    'Zonder instelling: standaardtekst en naar het formulier'
);
// Opslag van 2.12.0: een anker betekent het formulier, een adres een link.
$GLOBALS['options']['sfp_settings'] = array( 'sticky_cta' => array( 'coaching' => array( 'text' => 'Oud', 'href' => '#aanvragen' ) ) );
same( 'form', sfp_page_config_get_sticky_cta( 'coaching' )['doel'], 'Oud anker wordt het formulier' );
same( 'Oud', sfp_page_config_get_sticky_cta( 'coaching' )['text'], 'Oude tekst blijft' );
$GLOBALS['options']['sfp_settings'] = array( 'sticky_cta' => array( 'coaching' => array( 'href' => 'https://elders.test/boek' ) ) );
same(
    array( 'text' => 'Boek je gratis proefsessie', 'doel' => 'link', 'href' => 'https://elders.test/boek', 'target' => '_blank' ),
    sfp_page_config_get_sticky_cta( 'coaching' ),
    'Oud adres wordt een link in een nieuw venster'
);
sfp_page_config_instellingen_bewaar( array( 'sticky_cta' => array( 'training' => array( 'text' => ' Schrijf je in ', 'doel' => 'pagina', 'pagina' => 4 ) ) ) );
same(
    array( 'text' => 'Schrijf je in', 'doel' => 'pagina', 'href' => 'https://example.test/contact/', 'target' => '_self' ),
    sfp_page_config_get_sticky_cta( 'training' ),
    'Knop naar een pagina van de site'
);
sfp_page_config_instellingen_bewaar( array( 'sticky_cta' => array( 'training' => array( 'doel' => 'pagina', 'pagina' => 5 ) ) ) );
same( 'form', sfp_page_config_get_sticky_cta( 'training' )['doel'], 'Een pagina die niet gepubliceerd is, valt terug op het formulier' );
sfp_page_config_instellingen_bewaar( array( 'sticky_cta' => array( 'incompany' => array( 'doel' => 'link', 'link' => 'javascript:alert(1)' ) ) ) );
same( 'form', sfp_page_config_get_sticky_cta( 'incompany' )['doel'], 'Een onveilig adres wordt niet bewaard' );
sfp_page_config_instellingen_bewaar( array( 'sticky_cta' => array( 'incompany' => array( 'doel' => 'link', 'link' => 'https://example.test/offerte/' ) ) ) );
same( '_self', sfp_page_config_get_sticky_cta( 'incompany' )['target'], 'Een link naar de eigen site opent in hetzelfde venster' );

// Links die als pagina zijn gekozen.
sfp_page_config_instellingen_bewaar( array( 'artikel_redactie_pagina' => 6, 'artikel_redactie_url' => 'https://example.test/oud/' ) );
same( 'https://example.test/privacy/', sfp_page_config_link_instelling( 'artikel_redactie' ), 'Gekozen pagina gaat voor het oude adres' );
sfp_page_config_instellingen_bewaar( array( 'artikel_redactie_pagina' => 5 ) );
same( 'https://example.test/oud/', sfp_page_config_link_instelling( 'artikel_redactie' ), 'Concept valt terug op het oude adres' );

/* ---------- Cursusdata ---------- */

$schoon = sfp_page_config_cursusdata_schoon(
    array(
        array( 'data' => array( '2027-03-11', '2027-03-04', '2027-03-04', 'geen-datum' ), 'start' => '09:30', 'eind' => '25:00', 'vreemd' => 'x' ),
        array( 'data' => array() ),
        array( 'data' => array( '2027-01-14' ), 'start' => '', 'eind' => '16:30' ),
        'geen-array',
    )
);
same(
    array(
        array( 'data' => array( '2027-01-14' ), 'eind' => '16:30' ),
        array( 'data' => array( '2027-03-04', '2027-03-11' ), 'start' => '09:30' ),
    ),
    $schoon,
    'Cursusdata: gesorteerd, ontdubbeld, ongeldige waarden weg, lestijd bewaard'
);
same( array(), sfp_page_config_cursusdata_schoon( 'tekst' ), 'Cursusdata: geen array geeft een lege lijst' );

/* ---------- Footerlinks ---------- */

$footer = sfp_page_config_footer_schoon(
    array(
        'groepen' => array(
            array(
                'naam'  => ' Juridisch <b>x</b> ',
                'links' => array(
                    array( 'soort' => 'pagina', 'id' => '6', 'label' => 'Privacy' ),
                    array( 'soort' => 'pagina', 'id' => 0 ),
                    array( 'soort' => 'link', 'url' => 'https://example.test/sitemap_index.xml', 'label' => 'Sitemap' ),
                    array( 'soort' => 'link', 'url' => 'javascript:alert(1)', 'label' => 'Fout' ),
                    array( 'soort' => 'link', 'url' => 'https://example.test/zonder-tekst/', 'label' => '' ),
                ),
            ),
            array( 'naam' => '', 'links' => array( array( 'soort' => 'pagina', 'id' => 4 ) ) ),
            array( 'naam' => 'Leeg', 'links' => array() ),
        ),
    )
);
same( 1, count( $footer['groepen'] ), 'Footer: groep zonder naam of zonder links vervalt' );
same( 'Juridisch x', $footer['groepen'][0]['naam'], 'Footer: naam zonder HTML' );
same( 2, count( $footer['groepen'][0]['links'] ), 'Footer: ongeldige links vervallen' );

sfp_page_config_instellingen_bewaar( array( 'footer' => array( 'groepen' => array( array( 'naam' => 'Service', 'links' => array( array( 'soort' => 'pagina', 'id' => 4, 'label' => '' ), array( 'soort' => 'pagina', 'id' => 5, 'label' => 'Concept' ), array( 'soort' => 'link', 'url' => 'https://example.test/a?b=1&c=2', 'label' => 'A & B' ) ) ) ) ) ) );
$groepen = sfp_page_config_footer_groepen();
same( 2, count( $groepen[0]['links'] ), 'Footer: een pagina die niet gepubliceerd is, staat niet in de lijst' );
same( 'Contact', $groepen[0]['links'][0]['label'], 'Footer: zonder eigen tekst de paginatitel' );
same( 'https://example.test/contact/', $groepen[0]['links'][0]['url'], 'Footer: het adres volgt de pagina' );
$html = sfp_page_config_footer_html();
same( true, false !== strpos( $html, '<nav class="sfp-fj" aria-label="Service">' ), 'Footer: zelfde opbouw als de widget' );
same( true, false !== strpos( $html, '<summary class="sfp-fj__kop">Service</summary>' ), 'Footer: groepskop' );
same( true, false !== strpos( $html, 'A &amp; B' ), 'Footer: tekst is veilig gemaakt' );

// Overnemen uit de widget zoals hij nu op de sites staat.
$GLOBALS['options']['sidebars_widgets'] = array( 'footer-widget-4' => array( 'block-9', 'block-2' ) );
$GLOBALS['options']['widget_block']     = array(
    9 => array( 'content' => '<p>iets anders</p>' ),
    2 => array( 'content' => '<nav class="sfp-fj" aria-label="x"><details class="sfp-fj__groep" open><summary class="sfp-fj__kop">Legal</summary><ul class="sfp-fj__links"><li><a href="https://example.test/privacy/">Privacy</a></li><li><a href="https://example.test/concept/">Concept</a></li></ul></details><details class="sfp-fj__groep" open><summary class="sfp-fj__kop">Service</summary><ul class="sfp-fj__links"><li><a href="https://example.test/sitemap_index.xml">Sitemap</a></li><li><a href="https://example.test/contact/">Contact</a></li></ul></details></nav>' ),
);
$over = sfp_page_config_footer_uit_widget();
same( array( 'Legal', 'Service' ), array_column( $over['groepen'], 'naam' ), 'Widget: twee groepen' );
same( array( 'soort' => 'pagina', 'id' => 6, 'label' => 'Privacy' ), $over['groepen'][0]['links'][0], 'Widget: link naar een pagina wordt een pagina, eigen tekst blijft' );
same( 'link', $over['groepen'][0]['links'][1]['soort'], 'Widget: link naar een concept blijft een losse link' );
same( array( 'soort' => 'pagina', 'id' => 4, 'label' => '' ), $over['groepen'][1]['links'][1], 'Widget: tekst gelijk aan de titel wordt niet apart bewaard' );
$GLOBALS['options']['widget_block'] = array( 2 => array( 'content' => '<p>geen lijst</p>' ) );
same( null, sfp_page_config_footer_uit_widget(), 'Widget zonder de opbouw: niets over te nemen' );

/* ---------- Pagina's: controles ---------- */

$ankers = array( 'inschrijven', 'aanvragen' );
same( false, sfp_page_config_inhoud_heeft_formulier( '<p>Tekst</p>', $ankers ), 'Geen formulier' );
same( true, sfp_page_config_inhoud_heeft_formulier( '<div id="inschrijven"></div>', $ankers ), 'Bekend anker' );
same( true, sfp_page_config_inhoud_heeft_formulier( '<!-- wp:sfp/sectie {"anker":"aanvragen"} -->', $ankers ), 'Anker als blokeigenschap' );
same( true, sfp_page_config_inhoud_heeft_formulier( '<!-- wp:srfm/form {"id":12} /-->', $ankers ), 'SureForms-blok' );
same( true, sfp_page_config_inhoud_heeft_formulier( '<div class="sfp-boek-sectie">', $ankers ), 'Boekkaart' );
same( true, sfp_page_config_inhoud_heeft_formulier( '[latepoint_book_form]', $ankers ), 'LatePoint-code' );
same( 2, sfp_page_config_aantal_tussenkoppen( '<h2>A</h2><h3>B</h3><H2 class="x">C</H2>' ), 'Twee tussenkoppen' );

/* ---------- Eigen reviews ---------- */

$r = SFP_Reviews_Manual::schoon( array( 'author' => ' <b>Anna</b> ', 'rating' => 9, 'datum' => '2026-13-45', 'text' => "Regel 1\nRegel 2", 'photo' => 'https://x.test/a.jpg' ) );
same( 'Anna', $r['author'], 'Review: naam zonder HTML' );
same( 5, $r['rating'], 'Review: hoogstens vijf sterren' );
same( '', $r['datum'], 'Review: ongeldige datum vervalt' );
same( '', $r['photo'], 'Review: geen externe foto' );
same( true, 0 === strpos( $r['id'], 'mr_' ), 'Review: krijgt een id' );
SFP_Reviews_Manual::save_all( array() );
SFP_Reviews_Manual::add( array( 'author' => 'Tom', 'rating' => 5, 'text' => 'Goed', 'datum' => '2026-06-01' ) );
SFP_Reviews_Manual::add( array( 'author' => 'Eva', 'rating' => 3, 'text' => 'Matig' ) );
$alle = SFP_Reviews_Manual::get_all();
same( 2, count( $alle ), 'Review: twee bewaard' );
same( true, SFP_Reviews_Manual::update( $alle[0]['id'], array( 'author' => 'Tom B', 'rating' => 4, 'text' => 'Prima' ) ), 'Review: bijwerken' );
same( 'Tom B', SFP_Reviews_Manual::get_all()[0]['author'], 'Review: bijgewerkt' );
same( $alle[0]['id'], SFP_Reviews_Manual::get_all()[0]['id'], 'Review: id blijft bij bijwerken' );
same( false, SFP_Reviews_Manual::update( 'bestaat-niet', array( 'author' => 'x', 'text' => 'y' ) ), 'Review: onbekend id' );
same( 1, count( SFP_Reviews_Manual::get_filtered( 4 ) ), 'Review: filter op sterren' );
SFP_Reviews_Manual::delete( $alle[1]['id'] );
same( 1, count( SFP_Reviews_Manual::get_all() ), 'Review: weghalen' );

/* ---------- Knop onderaan per pagina, met gegevens van voor 3.0.0 ---------- */

same( 'Aanvraag_Form', sfp_page_config_anker_schoon( '#Aanvraag_Form' ), 'Anker houdt zijn hoofdletters' );
same( 'ab', sfp_page_config_anker_schoon( ' #a"><b ' ), 'Anker zonder vreemde tekens' );

$GLOBALS['options']['sfp_settings'] = array( 'sticky_cta' => array( 'training' => array( 'text' => 'Oud', 'anchor' => '#data', 'hero' => '.mijn-held', 'href' => '#Inschrijf_Blok' ) ) );
$knop = sfp_page_config_knop_voor_pagina( 4, 'training' );
same( 'form', $knop['doel'], 'Oude gegevens: de knop gaat naar het formulier' );
same( array( 'Inschrijf_Blok', 'data', 'inschrijven', 'aanvragen' ), $knop['ankers'], 'Oude ankers tellen mee, het adresveld eerst' );
same( '.mijn-held', $knop['hero'], 'Een met de hand aangewezen bovenkant blijft meetellen' );

// Een anker in het veld "een andere link" is het formulier, niet een nieuw venster.
sfp_page_config_instellingen_bewaar( array( 'sticky_cta' => array( 'incompany' => array( 'doel' => 'link', 'link' => '#offerte' ) ) ) );
$knop = sfp_page_config_knop_voor_pagina( 4, 'incompany' );
same( 'form', $knop['doel'], 'Link met een anker: naar het formulier' );
same( '_self', $knop['target'], 'Link met een anker: zelfde venster' );
same( 'offerte', $knop['ankers'][0], 'Link met een anker: het anker telt mee' );

// Alleen de tekst bewaren laat een oude link naar buiten staan.
$GLOBALS['options']['sfp_settings'] = array( 'sticky_cta' => array( 'coaching' => array( 'href' => 'https://elders.test/boek' ) ) );
sfp_page_config_instellingen_bewaar( array( 'sticky_cta' => array( 'coaching' => array( 'text' => 'Nieuw' ) ) ) );
same( 'https://elders.test/boek', sfp_page_config_get_sticky_cta( 'coaching' )['href'], 'Alleen tekst bewaren: de oude link blijft' );
same( 'Nieuw', sfp_page_config_get_sticky_cta( 'coaching' )['text'], 'Alleen tekst bewaren: de tekst verandert' );

// Per pagina: een gekozen pagina, een eigen link, een eigen anker.
$GLOBALS['options']['sfp_settings'] = array();
$GLOBALS['meta'][4] = array( 'sfp_cta_text' => 'Eigen tekst', 'sfp_cta_pagina' => 6 );
$knop = sfp_page_config_knop_voor_pagina( 4, 'coaching' );
same( 'https://example.test/privacy/', $knop['href'], 'Per pagina: de gekozen pagina' );
same( 'Eigen tekst', $knop['text'], 'Per pagina: eigen tekst' );
$GLOBALS['meta'][4] = array( 'sfp_cta_href' => '#Mijn_Formulier' );
$knop = sfp_page_config_knop_voor_pagina( 4, 'coaching' );
same( 'form', $knop['doel'], 'Per pagina: een anker is het formulier' );
same( 'Mijn_Formulier', $knop['ankers'][0], 'Per pagina: het anker houdt zijn hoofdletters' );
$GLOBALS['meta'][4] = array();

/* ---------- Footer: de lijst op de plek van de oude links ---------- */

$oud   = '<section id="block-2" class="widget widget_block"><nav class="sfp-fj" aria-label="x"><details class="sfp-fj__groep" open><summary class="sfp-fj__kop">Oud</summary><ul><li><a href="/a/">A</a></li></ul></details></nav></section>';
$logo  = '<section id="block-3" class="widget widget_block"><p>Logo</p></section>';
$lijst = '<nav class="sfp-fj" aria-label="Nieuw">NIEUW</nav>';
same(
    '<section id="block-2" class="widget widget_block">' . $lijst . '</section>' . $logo,
    sfp_page_config_footer_vervang( $oud . $logo, $lijst ),
    'Footer: de lijst vervangt de oude links, een andere widget blijft staan'
);
same(
    '<section class="widget widget_block sfp-footerlinks">' . $lijst . '</section>' . $logo,
    sfp_page_config_footer_vervang( $logo, $lijst ),
    'Footer: zonder oude links komt de lijst vooraan'
);

/* ---------- Meldingen die zijn weggeklikt ---------- */

$GLOBALS['meta'][4] = array( '_sfp_controle_negeer' => array( 'knop' ) );
same( array( 'knop' ), sfp_page_config_controle_genegeerd( 4 ), 'Weggeklikte melding' );
$GLOBALS['options']['sfp_cursusdata_hidden'] = array( 4, '6' );
same( array( 'knop', 'data' ), sfp_page_config_controle_genegeerd( 4 ), 'Een training die voor 3.0.0 verborgen was, meldt geen ontbrekende data' );
same( array( 'data' ), sfp_page_config_controle_genegeerd( 6 ), 'Verborgen training zonder andere keuzes' );
$GLOBALS['meta'][4]['_sfp_controle_meld_weer'] = '1';
same( array( 'knop' ), sfp_page_config_controle_genegeerd( 4 ), 'Na "Meld weer" telt de oude keuze niet meer' );
$GLOBALS['meta'][4] = array();

/* ---------- Oude plugin weer aanzetten ---------- */

$_REQUEST                  = array( 'action' => 'activate', 'plugin' => 'sfp-google-reviews/sfp-google-reviews.php' );
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI']    = '/?plugin=sfp-google-reviews/sfp-google-reviews.php';
same( false, sfp_page_config_plugin_wordt_aangezet( 'sfp-google-reviews' ), 'Een bezoeker kan het op de voorkant niet uitlokken' );
$GLOBALS['admin']       = true;
$_SERVER['REQUEST_URI'] = '/wp-admin/plugins.php?action=activate&plugin=sfp-google-reviews%2Fsfp-google-reviews.php';
same( true, sfp_page_config_plugin_wordt_aangezet( 'sfp-google-reviews' ), 'Aanzetten in wp-admin' );
same( false, sfp_page_config_plugin_wordt_aangezet( 'sfp-tooltip' ), 'Andere plugin' );
$_REQUEST               = array( 'action' => 'activate-selected', 'checked' => array( 'hello.php', 'sfp-google-reviews/sfp-google-reviews.php' ) );
$_SERVER['REQUEST_URI'] = '/wp-admin/plugins.php';
same( true, sfp_page_config_plugin_wordt_aangezet( 'sfp-google-reviews' ), 'Aanzetten in bulk' );
$_REQUEST = array();
same( false, sfp_page_config_plugin_wordt_aangezet( 'sfp-google-reviews' ), 'Het pluginscherm zonder actie' );
$GLOBALS['admin']          = false;
$_SERVER['REQUEST_URI']    = '/wp-json/wp/v2/plugins/sfp-google-reviews/sfp-google-reviews?context=edit';
same( false, sfp_page_config_plugin_wordt_aangezet( 'sfp-google-reviews' ), 'REST: alleen lezen telt niet' );
$_SERVER['REQUEST_METHOD'] = 'POST';
same( true, sfp_page_config_plugin_wordt_aangezet( 'sfp-google-reviews' ), 'Aanzetten via REST' );
$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/plugins/sfp-google-reviews-extra/x';
same( false, sfp_page_config_plugin_wordt_aangezet( 'sfp-google-reviews' ), 'Andere map met dezelfde start' );
$_SERVER['REQUEST_URI'] = '/';
$_REQUEST               = array( 'rest_route' => '/wp/v2/plugins/sfp-google-reviews/sfp-google-reviews' );
same( true, sfp_page_config_plugin_wordt_aangezet( 'sfp-google-reviews' ), 'Aanzetten via REST zonder nette adressen' );
$_REQUEST                  = array();
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "OK: $checks checks\n";
