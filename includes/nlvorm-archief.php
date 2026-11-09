<?php
/**
 * SFP Page Config - NL-vorm: bouwstenen van de archieven en de leeskolom (3.35.0).
 *
 * Bron: dummy nl-vormen, Blogoverzicht, Categorie, Zoekresultaten, Auteur,
 * Begrippen, Tools en Leeskolom (versie 97), akkoord Stephan 8-11-2026.
 * Besluiten 8-11: de archieven blijven Astra-layouts; deze blokken staan in
 * die layouts. Teksten van blog, begrippen en tools typ je in het blok
 * Archiefkop; categorie, zoeken en auteur vullen zichzelf. De Auteurskop
 * leest het WordPress-profiel. Telefoonnummers in de notatie 085 060 1355.
 *
 *   - Archiefkop (R1): label, H1 met accent, kernzin, zoekveld, filterrij of
 *     alfabet;
 *   - Gidsen (R2): de pijlerpagina's als brede kaart; op een categorie alleen
 *     de eigen gids;
 *   - Artikelraster (R3): de lijst van het archief als kaarten (de kaart van
 *     Laatste artikelen, H3 en H4) met label, uittreksel en paginering;
 *   - Auteurskop (R4): foto, naam, rol, biografie, feitenlijst en links;
 *   - Contactgegevens (L1): telefoon, WhatsApp, e-mail en adres uit SFP
 *     Beheer, Contact.
 * De uitklap van WordPress in de leeskolom (L2) is alleen opmaak
 * (assets/blokken/basis.css). Deze blokken maken elk hun eigen sectie, zodat
 * een layout alleen de blokken onder elkaar zet. De opmaak staat in
 * assets/blokken/nlvorm-archief.css.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'sfp_page_config_blokken_extra', 'sfp_page_config_archief_blokken' );

/**
 * De blokken van de archieven en de leeskolom.
 *
 * @param  array $alle Blokken.
 * @return array
 */
function sfp_page_config_archief_blokken( $alle ) {
    $teksten = array(
        'type'    => 'object',
        'default' => array(),
    );
    $alle['sfp/archiefkop']      = array(
        'title'        => 'Archiefkop',
        'description'  => 'De kop van een archief: label, titel met accent, kernzin, zoekveld en categorieën of alfabet. Op een categorie en bij zoeken vult hij zichzelf.',
        'icon'         => 'archive',
        'css'          => 'nlvorm-archief',
        'zonder_basis' => false,
        'align'        => array( 'full' ),
        'attributes'   => array( 'teksten' => $teksten ),
        'render'       => 'sfp_page_config_render_archiefkop',
    );
    $alle['sfp/gidsen']          = array(
        'title'        => 'Gidsen',
        'description'  => 'De pijlerpagina\'s als brede kaart. Op een categorie alleen de gids van die categorie. Vult zichzelf.',
        'icon'         => 'book-alt',
        'css'          => 'nlvorm-archief',
        'zonder_basis' => false,
        'align'        => array( 'full' ),
        'attributes'   => array( 'teksten' => $teksten ),
        'render'       => 'sfp_page_config_render_gidsen',
    );
    $alle['sfp/artikelraster']   = array(
        'title'        => 'Artikelraster',
        'description'  => 'De artikelen van dit archief als kaarten, drie naast elkaar, met paginering. Vult zichzelf.',
        'icon'         => 'grid-view',
        'css'          => 'nlvorm-archief',
        'css_extra'    => array( 'nlvorm-home', 'kaart', 'kolommen' ),
        'zonder_basis' => false,
        'align'        => array( 'full' ),
        'attributes'   => array(
            'teksten'     => $teksten,
            'achtergrond' => array(
                'type'    => 'string',
                'default' => 'auto',
            ),
        ),
        'render'       => 'sfp_page_config_render_artikelraster',
    );
    $alle['sfp/auteurskop']      = array(
        'title'        => 'Auteurskop',
        'description'  => 'Op een auteurspagina: foto, naam, rol, biografie en links uit het profiel van de auteur.',
        'icon'         => 'id',
        'css'          => 'nlvorm-archief',
        'css_extra'    => array( 'lijst' ),
        'zonder_basis' => false,
        'align'        => array( 'full' ),
        'attributes'   => array(),
        'render'       => 'sfp_page_config_render_auteurskop',
    );
    $alle['sfp/contactgegevens'] = array(
        'title'        => 'Contactgegevens',
        'description'  => 'Telefoon, WhatsApp, e-mail en adres uit SFP Beheer, Contact. Twee kolommen, op een telefoon één.',
        'icon'         => 'phone',
        'css'          => 'nlvorm-archief',
        'zonder_basis' => false,
        'attributes'   => array(),
        'render'       => 'sfp_page_config_render_contactgegevens',
    );
    return $alle;
}

/* =========================================================================
 * Waar staan we
 * ====================================================================== */

/**
 * Toont de editor een voorbeeld (ServerSideRender of de beheerkant)?
 *
 * @return bool
 */
function sfp_page_config_archief_voorbeeld() {
    return is_admin() || wp_is_json_request() || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
}

/**
 * De soort archief van deze pagina.
 *
 * @return string blog, categorie, tag, zoeken, auteur, begrippen, tool,
 *                voorbeeld of anders.
 */
function sfp_page_config_archief_soort() {
    if ( sfp_page_config_archief_voorbeeld() ) {
        return 'voorbeeld';
    }
    if ( is_search() ) {
        return 'zoeken';
    }
    if ( is_category() ) {
        return 'categorie';
    }
    if ( is_tag() ) {
        return 'tag';
    }
    if ( is_author() ) {
        return 'auteur';
    }
    if ( is_post_type_archive( 'begrippen' ) ) {
        return 'begrippen';
    }
    if ( is_post_type_archive( 'tool' ) ) {
        return 'tool';
    }
    if ( is_home() ) {
        return 'blog';
    }
    return 'anders';
}

/**
 * De soort waarvan de getypte teksten gelden: blog, begrippen of tool. Een
 * layout voor meer soorten (SwS: begrippen, tools en auteur samen) houdt zo
 * per soort zijn eigen teksten.
 *
 * @param  string $soort Soort uit sfp_page_config_archief_soort().
 * @return string
 */
function sfp_page_config_archief_tekstsoort( $soort ) {
    return in_array( $soort, array( 'begrippen', 'tool' ), true ) ? $soort : 'blog';
}

/**
 * Een getypte tekst van een blok voor een soort.
 *
 * @param  array  $attrs   Attributen.
 * @param  string $soort   blog, begrippen of tool.
 * @param  string $sleutel label, titel, kernzin of kop.
 * @return string          Ruwe tekst, nog niet geschoond.
 */
function sfp_page_config_archief_getypt( $attrs, $soort, $sleutel ) {
    $teksten = isset( $attrs['teksten'] ) && is_array( $attrs['teksten'] ) ? $attrs['teksten'] : array();
    $deel    = isset( $teksten[ $soort ] ) && is_array( $teksten[ $soort ] ) ? $teksten[ $soort ] : array();
    return isset( $deel[ $sleutel ] ) && is_scalar( $deel[ $sleutel ] ) ? trim( (string) $deel[ $sleutel ] ) : '';
}

/**
 * Een titel met hoogstens het accent (mark.sfp-accent), cursief en vet.
 *
 * @param  string $html Getypte titel.
 * @return string
 */
function sfp_page_config_archief_titel_schoon( $html ) {
    return wp_kses(
        (string) $html,
        array(
            'mark'   => array( 'class' => true ),
            'em'     => array(),
            'strong' => array(),
            'br'     => array(),
        )
    );
}

/**
 * Het accent om een stuk tekst.
 *
 * @param  string $tekst Platte tekst.
 * @return string
 */
function sfp_page_config_archief_accent( $tekst ) {
    return '<mark class="sfp-accent">' . esc_html( $tekst ) . '</mark>';
}

/**
 * Het adres van het blogoverzicht.
 *
 * @return string
 */
function sfp_page_config_archief_blog_url() {
    $pagina = (int) get_option( 'page_for_posts' );
    if ( $pagina && 'page' === get_option( 'show_on_front' ) ) {
        return (string) get_permalink( $pagina );
    }
    return home_url( '/' );
}

/**
 * De naam van het blogoverzicht: de titel van de berichtenpagina, anders Blog.
 *
 * @return string
 */
function sfp_page_config_archief_blog_naam() {
    $pagina = (int) get_option( 'page_for_posts' );
    $naam   = $pagina ? wp_strip_all_tags( get_the_title( $pagina ) ) : '';
    return '' !== $naam ? $naam : sfp_page_config_tekst( 'archief_blog' );
}

/**
 * De hoofdlijst van de pagina (het archief zelf).
 *
 * @return WP_Query|null
 */
function sfp_page_config_archief_query() {
    $q = isset( $GLOBALS['wp_the_query'] ) ? $GLOBALS['wp_the_query'] : ( isset( $GLOBALS['wp_query'] ) ? $GLOBALS['wp_query'] : null );
    return $q instanceof WP_Query ? $q : null;
}

/**
 * Een sectie om een blok heen.
 *
 * @param  string[] $klassen Extra klassen.
 * @param  string   $binnen  Inhoud.
 * @return string
 */
function sfp_page_config_archief_sectie( array $klassen, $binnen ) {
    return '<section ' . sfp_page_config_blok_omhulling( array_merge( array( 'sfp-sectie' ), $klassen ) ) . '><div class="sfp-sectie__binnen">' . $binnen . '</div></section>';
}

/**
 * De CSS van deze blokken als vangnet (de layout staat niet in de inhoud).
 *
 * @param  string $naam Blok.
 * @return string
 */
function sfp_page_config_archief_css( $naam ) {
    return sfp_page_config_blokken_css_vangnet( 'sfp/sectie' ) . sfp_page_config_blokken_css_vangnet( $naam );
}

/* =========================================================================
 * Archiefkop (R1)
 * ====================================================================== */

/**
 * Label, titel (HTML) en kernzin van de archiefkop voor deze pagina.
 *
 * @param  array  $attrs Attributen.
 * @param  string $soort Soort archief.
 * @return array{label: string, titel: string, kernzin: string}
 */
function sfp_page_config_archiefkop_teksten( $attrs, $soort ) {
    $q = sfp_page_config_archief_query();
    switch ( $soort ) {
        case 'categorie':
        case 'tag':
            $term = get_queried_object();
            $naam = $term instanceof WP_Term ? $term->name : '';
            return array(
                'label'   => sfp_page_config_tekst( 'archief_categorie' ),
                'titel'   => sfp_page_config_archief_accent( $naam ),
                'kernzin' => $term instanceof WP_Term ? trim( wp_strip_all_tags( term_description( $term ) ) ) : '',
            );
        case 'zoeken':
            $term   = get_search_query( false );
            $aantal = $q ? (int) $q->found_posts : 0;
            return array(
                'label'   => sfp_page_config_tekst( 'archief_zoeken' ),
                'titel'   => sprintf( esc_html( sfp_page_config_tekst( 'archief_resultaten_voor' ) ), sfp_page_config_archief_accent( $term ) ),
                'kernzin' => sfp_page_config_tekst_f( 1 === $aantal ? 'archief_n_resultaten_1' : 'archief_n_resultaten', $aantal ),
            );
        case 'auteur':
            $user = get_queried_object();
            return array(
                'label'   => sfp_page_config_tekst( 'archief_profiel' ),
                'titel'   => esc_html( $user instanceof WP_User ? $user->display_name : '' ),
                'kernzin' => '',
            );
    }
    $tsoort  = sfp_page_config_archief_tekstsoort( $soort );
    $label   = wp_strip_all_tags( sfp_page_config_archief_getypt( $attrs, $tsoort, 'label' ) );
    $titel   = sfp_page_config_archief_titel_schoon( sfp_page_config_archief_getypt( $attrs, $tsoort, 'titel' ) );
    $kernzin = wp_strip_all_tags( sfp_page_config_archief_getypt( $attrs, $tsoort, 'kernzin' ) );
    if ( 'blog' === $tsoort ) {
        $naam = sfp_page_config_archief_blog_naam();
        return array(
            'label'   => '' !== $label ? $label : $naam,
            'titel'   => '' !== trim( wp_strip_all_tags( $titel ) ) ? $titel : esc_html( $naam ),
            'kernzin' => $kernzin,
        );
    }
    $obj  = get_post_type_object( $tsoort );
    $naam = $obj ? (string) $obj->labels->name : '';
    return array(
        'label'   => '' !== $label ? $label : $naam,
        'titel'   => '' !== trim( wp_strip_all_tags( $titel ) ) ? $titel : esc_html( $naam ),
        'kernzin' => '' !== $kernzin ? $kernzin : ( $obj ? trim( wp_strip_all_tags( (string) $obj->description ) ) : '' ),
    );
}

/**
 * Het zoekveld. De zoekterm staat erin op de zoekresultaten.
 *
 * @param  string $soort Soort archief.
 * @return string
 */
function sfp_page_config_archief_zoekveld( $soort ) {
    static $nummer = 0;
    ++$nummer;
    $sleutels = array(
        'zoeken'    => 'archief_zoek_site',
        'begrippen' => 'archief_zoek_begrip',
        'tool'      => 'archief_zoek_tool',
    );
    // Sinds 3.39.0 (besluit Stephan 8-11-2026): zoeken hoort bij de pagina
    // waar je zoekt. Op de zoekresultaten blijft de gekozen context staan.
    $context  = 'zoeken' === $soort ? sfp_page_config_zoek_context() : sfp_page_config_zoek_context_van( $soort );
    $sleutel  = 'zoeken' === $soort && '' !== $context ? array( 'blog' => 'archief_zoek_artikelen', 'begrippen' => 'archief_zoek_begrip', 'tool' => 'archief_zoek_tool' )[ $context ] : ( isset( $sleutels[ $soort ] ) ? $sleutels[ $soort ] : 'archief_zoek_artikelen' );
    $plaats   = sfp_page_config_tekst( $sleutel );
    $id       = 'sfp-zoek-' . $nummer;
    $waarde   = 'zoeken' === $soort ? get_search_query() : '';
    return '<form class="sfp-zoek" role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '">'
        . ( '' !== $context ? '<input type="hidden" name="sfp_in" value="' . esc_attr( $context ) . '">' : '' )
        . '<label class="screen-reader-text" for="' . esc_attr( $id ) . '">' . esc_html( $plaats ) . '</label>'
        . '<input type="search" id="' . esc_attr( $id ) . '" name="s" value="' . esc_attr( $waarde ) . '" placeholder="' . esc_attr( $plaats ) . '">'
        . '<button type="submit" class="wp-block-button__link wp-element-button">' . esc_html( sfp_page_config_tekst( 'archief_zoekknop' ) ) . '</button></form>';
}

/* =========================================================================
 * Zoeken per context (3.39.0)
 * ====================================================================== */

/**
 * De zoekcontext bij een soort archief: blog (ook categorie en tag),
 * begrippen of tool; anders leeg (het algemene zoeken).
 *
 * @param  string $soort Soort archief.
 * @return string
 */
function sfp_page_config_zoek_context_van( $soort ) {
    if ( in_array( $soort, array( 'blog', 'categorie', 'tag' ), true ) ) {
        return 'blog';
    }
    return in_array( $soort, array( 'begrippen', 'tool' ), true ) ? $soort : '';
}

/**
 * De zoekcontext uit het adres (sfp_in), of leeg.
 *
 * @return string
 */
function sfp_page_config_zoek_context() {
    $in = isset( $_GET['sfp_in'] ) ? sanitize_key( wp_unslash( $_GET['sfp_in'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- alleen lezen, zoeken.
    return in_array( $in, array( 'blog', 'begrippen', 'tool' ), true ) ? $in : '';
}

add_action( 'pre_get_posts', 'sfp_page_config_zoek_per_context' );

/**
 * Zoeken vanaf het blog of een categorie toont alleen berichten en
 * pijlerpagina's; vanaf begrippen alleen begrippen, vanaf tools alleen
 * tools. Zonder context blijft het zoeken alles tonen.
 *
 * @param  WP_Query $query Lijst.
 * @return void
 */
function sfp_page_config_zoek_per_context( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
        return;
    }
    $context = sfp_page_config_zoek_context();
    if ( '' === $context ) {
        return;
    }
    if ( 'blog' !== $context ) {
        if ( post_type_exists( $context ) ) {
            $query->set( 'post_type', $context );
        }
        return;
    }
    $query->set( 'post_type', array( 'post', 'page' ) );
    $query->set( 'sfp_zoek_blog', 1 );
}

add_filter( 'posts_where', 'sfp_page_config_zoek_blog_where', 10, 2 );

/**
 * Blogcontext: van de pagina's alleen de pijlerpagina's.
 *
 * @param  string   $where Voorwaarde.
 * @param  WP_Query $query Lijst.
 * @return string
 */
function sfp_page_config_zoek_blog_where( $where, $query ) {
    if ( ! $query->get( 'sfp_zoek_blog' ) ) {
        return $where;
    }
    global $wpdb;
    $pijlers = function_exists( 'sfp_page_config_pijler_aan' ) && sfp_page_config_pijler_aan()
        ? get_posts(
            array(
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'posts_per_page' => 200,
                'no_found_rows'  => true,
                'meta_key'       => 'sfp_vorm', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key -- kleine lijst, alleen bij zoeken.
                'meta_value'     => 'pijler', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
            )
        )
        : array();
    $pijlers = array_map( 'intval', $pijlers );
    $pagina  = $pijlers ? " OR ( {$wpdb->posts}.post_type = 'page' AND {$wpdb->posts}.ID IN (" . implode( ',', $pijlers ) . ') )' : '';
    return $where . " AND ( {$wpdb->posts}.post_type = 'post'" . $pagina . ' )';
}

/**
 * De categorieën voor de filterrij: hoofdcategorieën met artikelen, de
 * standaardcategorie niet, de grootste eerst.
 *
 * @return WP_Term[]
 */
function sfp_page_config_archief_categorieen() {
    $terms = get_terms(
        array(
            'taxonomy'   => 'category',
            'parent'     => 0,
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
            'number'     => 12,
            'exclude'    => array( (int) get_option( 'default_category' ) ),
        )
    );
    return is_array( $terms ) ? $terms : array();
}

/**
 * De filterrij met categorieën, de huidige actief. Links gaan naar het
 * archief van de categorie, ook als die een pijler heeft.
 *
 * @param  string $soort Soort archief.
 * @return string
 */
function sfp_page_config_archief_filter( $soort ) {
    $terms = sfp_page_config_archief_categorieen();
    if ( ! $terms ) {
        return '';
    }
    $nu    = 'categorie' === $soort && get_queried_object() instanceof WP_Term ? (int) get_queried_object()->term_id : 0;
    $links = '<a href="' . esc_url( sfp_page_config_archief_blog_url() ) . '"' . ( 'blog' === $soort ? ' class="is-actief" aria-current="page"' : '' ) . '>' . esc_html( sfp_page_config_tekst( 'archief_alle' ) ) . '</a>';
    foreach ( $terms as $term ) {
        $url    = function_exists( 'sfp_page_config_categorie_archief_url' ) ? sfp_page_config_categorie_archief_url( $term ) : get_term_link( $term );
        $actief = (int) $term->term_id === $nu;
        $links .= '<a href="' . esc_url( is_string( $url ) ? $url : '' ) . '"' . ( $actief ? ' class="is-actief" aria-current="page"' : '' ) . '>' . esc_html( $term->name ) . '</a>';
    }
    return '<nav class="sfp-filter" aria-label="' . esc_attr( sfp_page_config_tekst( 'archief_filter' ) ) . '">' . $links . '</nav>';
}

/**
 * De beginletter van een titel, zonder accent, als hoofdletter.
 *
 * @param  string $titel Titel.
 * @return string        A tot Z, of ''.
 */
function sfp_page_config_archief_letter( $titel ) {
    $l = strtoupper( mb_substr( remove_accents( trim( wp_strip_all_tags( (string) $titel ) ) ), 0, 1 ) );
    return preg_match( '/^[A-Z]$/', $l ) ? $l : '';
}

/**
 * De beginletters van de gepubliceerde begrippen.
 *
 * @return string[]
 */
function sfp_page_config_archief_letters() {
    $bewaard = get_transient( 'sfp_pc_begrip_letters' );
    if ( is_array( $bewaard ) ) {
        return $bewaard;
    }
    $letters = array();
    if ( post_type_exists( 'begrippen' ) ) {
        $ids = get_posts(
            array(
                'post_type'        => 'begrippen',
                'post_status'      => 'publish',
                'posts_per_page'   => 2000,
                'fields'           => 'ids',
                'no_found_rows'    => true,
                'suppress_filters' => true,
            )
        );
        foreach ( $ids as $id ) {
            $l = sfp_page_config_archief_letter( get_the_title( $id ) );
            if ( '' !== $l ) {
                $letters[ $l ] = true;
            }
        }
    }
    $letters = array_keys( $letters );
    sort( $letters );
    set_transient( 'sfp_pc_begrip_letters', $letters, DAY_IN_SECONDS );
    return $letters;
}

add_action( 'save_post_begrippen', 'sfp_page_config_archief_letters_wis' );
add_action( 'deleted_post', 'sfp_page_config_archief_letters_wis' );

/**
 * Een nieuw of verwijderd begrip kan een letter toevoegen of weghalen.
 */
function sfp_page_config_archief_letters_wis() {
    delete_transient( 'sfp_pc_begrip_letters' );
}

/**
 * De gekozen letter in het archief van de begrippen (?letter=A).
 *
 * @return string A tot Z, of ''.
 */
function sfp_page_config_archief_gekozen_letter() {
    $ruw = isset( $_GET['letter'] ) ? sanitize_text_field( wp_unslash( $_GET['letter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- alleen lezen, een filter op letter.
    $l   = strtoupper( $ruw );
    return preg_match( '/^[A-Z]$/', $l ) ? $l : '';
}

/**
 * Het alfabet bij de begrippen: alleen letters met een begrip.
 *
 * @return string
 */
function sfp_page_config_archief_alfabet() {
    $letters = sfp_page_config_archief_letters();
    $archief = get_post_type_archive_link( 'begrippen' );
    if ( ! $letters || ! $archief ) {
        return '';
    }
    $nu    = sfp_page_config_archief_gekozen_letter();
    $links = '<a href="' . esc_url( $archief ) . '"' . ( '' === $nu ? ' class="is-actief" aria-current="page"' : '' ) . '>' . esc_html( sfp_page_config_tekst( 'archief_alle_kort' ) ) . '</a>';
    foreach ( $letters as $l ) {
        $links .= '<a href="' . esc_url( add_query_arg( 'letter', $l, $archief ) ) . '"' . ( $l === $nu ? ' class="is-actief" aria-current="page"' : '' ) . '>' . esc_html( $l ) . '</a>';
    }
    return '<nav class="sfp-filter sfp-filter--abc" aria-label="' . esc_attr( sfp_page_config_tekst( 'archief_alfabet' ) ) . '">' . $links . '</nav>';
}

add_filter( 'posts_where', 'sfp_page_config_archief_letter_filter', 10, 2 );

/**
 * Het archief van de begrippen met ?letter=A toont alleen begrippen die met
 * die letter beginnen.
 *
 * @param  string   $where Voorwaarde.
 * @param  WP_Query $query Lijst.
 * @return string
 */
function sfp_page_config_archief_letter_filter( $where, $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'begrippen' ) ) {
        return $where;
    }
    $l = sfp_page_config_archief_gekozen_letter();
    if ( '' === $l ) {
        return $where;
    }
    global $wpdb;
    return $where . $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", $wpdb->esc_like( $l ) . '%' );
}

/**
 * sfp/archiefkop.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_archiefkop( $attrs ) {
    $soort = sfp_page_config_archief_soort();
    $t     = sfp_page_config_archiefkop_teksten( $attrs, $soort );
    $html  = ( '' !== $t['label'] ? '<p class="is-style-sfp-label">' . esc_html( $t['label'] ) . '</p>' : '' )
        . '<h1 class="wp-block-heading h1-xl">' . $t['titel'] . '</h1>'
        . ( '' !== $t['kernzin'] ? '<p class="is-style-sfp-kernzin sfp-archiefkop__kernzin">' . esc_html( $t['kernzin'] ) . '</p>' : '' );
    if ( 'auteur' !== $soort ) {
        $html .= sfp_page_config_archief_zoekveld( $soort );
    }
    if ( in_array( $soort, array( 'blog', 'categorie', 'tag', 'voorbeeld', 'anders' ), true ) ) {
        $html .= sfp_page_config_archief_filter( $soort );
    } elseif ( 'begrippen' === $soort ) {
        $html .= sfp_page_config_archief_alfabet();
    }
    return sfp_page_config_archief_css( 'sfp/archiefkop' ) . sfp_page_config_archief_sectie( array( 'sfp-archiefkop' ), $html );
}

/* =========================================================================
 * Gidsen (R2)
 * ====================================================================== */

/**
 * Is er op deze pagina al een Gidsen-blok met inhoud getoond? Het
 * Artikelraster kiest dan wit (anders tint), zodat wit en tint wisselen.
 *
 * @param  bool|null $zet True om te onthouden.
 * @return bool
 */
function sfp_page_config_archief_gidsen_getoond( $zet = null ) {
    static $getoond = false;
    if ( null !== $zet ) {
        $getoond = (bool) $zet;
    }
    return $getoond;
}

/**
 * De gidsen voor deze pagina.
 *
 * @param  string $soort Soort archief.
 * @return WP_Post[]
 */
function sfp_page_config_archief_gidsen_lijst( $soort ) {
    if ( ! function_exists( 'sfp_page_config_gidsen' ) ) {
        return array();
    }
    $ids = array();
    if ( 'categorie' === $soort ) {
        $term = get_queried_object();
        $id   = $term instanceof WP_Term && function_exists( 'sfp_page_config_gids_van_term' ) ? sfp_page_config_gids_van_term( $term->term_id ) : 0;
        $ids  = $id ? array( $id ) : array();
    } elseif ( in_array( $soort, array( 'blog', 'voorbeeld' ), true ) ) {
        $ids = array_values( sfp_page_config_gidsen() );
    }
    $uit = array();
    foreach ( $ids as $id ) {
        $p = get_post( (int) $id );
        if ( $p && 'publish' === $p->post_status ) {
            $uit[] = $p;
        }
    }
    return $uit;
}

/**
 * Eén gids als brede kaart: beeld, regel Gids in Blog, titel, zin, aantal
 * artikelen en de link.
 *
 * @param  WP_Post $gids Pijlerpagina.
 * @return string
 */
function sfp_page_config_archief_gids_kaart( WP_Post $gids ) {
    $term  = function_exists( 'sfp_page_config_gids_term' ) ? sfp_page_config_gids_term( $gids ) : null;
    $beeld = has_post_thumbnail( $gids )
        ? get_the_post_thumbnail(
            $gids,
            'medium_large',
            array(
                'loading'  => 'lazy',
                'decoding' => 'async',
                'alt'      => '',
                'sizes'    => '(max-width: 700px) 112px, 560px',
            )
        )
        : '';
    $beeld = '' !== $beeld
        ? '<figure class="sfp-archiefgids__beeld">' . $beeld . '</figure>'
        : '<figure class="sfp-archiefgids__beeld sfp-geenbeeld" aria-hidden="true">' . sfp_page_config_archief_icoon() . '</figure>';
    $zin   = has_excerpt( $gids ) ? trim( wp_strip_all_tags( $gids->post_excerpt ) ) : '';
    $regel = function_exists( 'sfp_page_config_artikel_tekst' ) ? sprintf( sfp_page_config_artikel_tekst( 'gids_in' ), sfp_page_config_archief_blog_naam() ) : '';
    $meta  = $term && function_exists( 'sfp_page_config_gids_tel' ) ? sfp_page_config_gids_tel( (int) $term->count, 'n_artikelen' ) : '';
    $link  = get_permalink( $gids );
    return '<article class="sfp-kaart sfp-kaart--vlak sfp-kaart--link sfp-archiefgids">' . $beeld
        . '<div class="sfp-archiefgids__tekst">'
        . ( '' !== $regel ? '<p class="is-style-sfp-label">' . esc_html( $regel ) . '</p>' : '' )
        . '<h3 class="wp-block-heading sfp-archiefgids__titel">' . esc_html( wp_strip_all_tags( get_the_title( $gids ) ) ) . '</h3>'
        . ( '' !== $zin ? '<p class="sfp-archiefgids__zin">' . esc_html( $zin ) . '</p>' : '' )
        . ( '' !== $meta ? '<p class="sfp-archiefgids__meta">' . esc_html( $meta ) . '</p>' : '' )
        . '<p class="sfp-archiefgids__link"><a class="sfp-tekstlink" href="' . esc_url( $link ) . '">' . esc_html( sfp_page_config_tekst( 'archief_gids_lees' ) ) . '</a></p>'
        . '</div><a class="sfp-kaart__link" href="' . esc_url( $link ) . '" tabindex="-1" aria-hidden="true"></a></article>';
}

/**
 * Het site-icoon voor een kaart zonder foto, of niets.
 *
 * @return string
 */
function sfp_page_config_archief_icoon() {
    $icoon = get_site_icon_url( 192 );
    return $icoon ? '<img src="' . esc_url( $icoon ) . '" alt="" loading="lazy" decoding="async" width="96" height="96">' : '';
}

/**
 * sfp/gidsen. Zonder gids: niets, ook geen sectie.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_gidsen( $attrs ) {
    $soort  = sfp_page_config_archief_soort();
    $gidsen = sfp_page_config_archief_gidsen_lijst( $soort );
    if ( ! $gidsen ) {
        return '';
    }
    $label = wp_strip_all_tags( sfp_page_config_archief_getypt( $attrs, 'blog', 'label' ) );
    $kop   = wp_strip_all_tags( sfp_page_config_archief_getypt( $attrs, 'blog', 'kop' ) );
    if ( 'categorie' === $soort || '' === $kop ) {
        $kop = sfp_page_config_tekst( 'categorie' === $soort ? 'archief_gids_kop' : 'archief_gidsen_kop' );
    }
    $kaarten = '';
    foreach ( $gidsen as $g ) {
        $kaarten .= sfp_page_config_archief_gids_kaart( $g );
    }
    sfp_page_config_archief_gidsen_getoond( true );
    $html = '<p class="is-style-sfp-label">' . esc_html( '' !== $label ? $label : sfp_page_config_tekst( 'archief_gidsen' ) ) . '</p>'
        . '<h2 class="wp-block-heading">' . esc_html( $kop ) . '</h2>'
        . '<div class="sfp-archiefgidsen' . ( count( $gidsen ) > 1 ? ' sfp-archiefgidsen--meer' : '' ) . '">' . $kaarten . '</div>';
    return sfp_page_config_archief_css( 'sfp/gidsen' ) . sfp_page_config_blokken_css_vangnet( 'sfp/kaart' )
        . sfp_page_config_archief_sectie( array( 'sfp-sectie--tint', 'sfp-sectie--gidsen' ), $html );
}

/* =========================================================================
 * Artikelraster (R3)
 * ====================================================================== */

/**
 * Het label boven de titel van een kaart: de categorie van een artikel, of
 * de soort (op de zoekresultaten en bij een ander soort bericht).
 *
 * @param  WP_Post $post  Bericht.
 * @param  string  $soort Soort archief.
 * @return string
 */
function sfp_page_config_archief_kaart_label( WP_Post $post, $soort ) {
    if ( 'post' === $post->post_type && 'zoeken' !== $soort ) {
        $standaard = (int) get_option( 'default_category' );
        foreach ( get_the_category( $post->ID ) as $cat ) {
            if ( (int) $cat->term_id !== $standaard ) {
                return $cat->name;
            }
        }
        return '';
    }
    if ( 'post' === $post->post_type ) {
        return sfp_page_config_tekst( 'archief_soort_artikel' );
    }
    if ( 'page' === $post->post_type ) {
        return function_exists( 'sfp_page_config_is_gids' ) && sfp_page_config_is_gids( $post ) && function_exists( 'sfp_page_config_artikel_tekst' )
            ? sfp_page_config_artikel_tekst( 'gids' )
            : sfp_page_config_tekst( 'archief_soort_pagina' );
    }
    $obj = get_post_type_object( $post->post_type );
    return $obj ? (string) $obj->labels->singular_name : '';
}

/**
 * De eerste regels van een kaart: de samenvatting, bij een begrip de
 * definitie, bij een tool de beschrijving, anders het begin van de tekst.
 *
 * @param  WP_Post $post Bericht.
 * @return string        Platte tekst.
 */
function sfp_page_config_archief_uittreksel( WP_Post $post ) {
    if ( has_excerpt( $post ) ) {
        return wp_trim_words( wp_strip_all_tags( $post->post_excerpt ), 30 );
    }
    if ( 'begrippen' === $post->post_type && function_exists( 'sfp_page_config_begrip_definitie' ) ) {
        $zin = trim( wp_strip_all_tags( (string) sfp_page_config_begrip_definitie( $post ) ) );
        if ( '' !== $zin ) {
            return wp_trim_words( $zin, 30 );
        }
    }
    if ( 'tool' === $post->post_type && function_exists( 'sfp_page_config_tool_beschrijving' ) ) {
        $zin = trim( wp_strip_all_tags( (string) sfp_page_config_tool_beschrijving( $post ) ) );
        if ( '' !== $zin ) {
            return wp_trim_words( $zin, 30 );
        }
    }
    return wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 30 );
}

/**
 * De regel onder de kaart: auteur en datum bij een artikel, alleen de datum
 * op de pagina van die auteur, niets bij een ander soort.
 *
 * @param  WP_Post $post  Bericht.
 * @param  string  $soort Soort archief.
 * @return string
 */
function sfp_page_config_archief_kaart_meta( WP_Post $post, $soort ) {
    if ( 'post' !== $post->post_type ) {
        return '';
    }
    $datum = wp_date( 'j F Y', (int) get_post_timestamp( $post ) );
    if ( 'auteur' === $soort ) {
        return (string) $datum;
    }
    $auteur = (string) get_the_author_meta( 'display_name', (int) $post->post_author );
    return trim( ( '' !== $auteur ? $auteur . ' · ' : '' ) . $datum );
}

/**
 * Label en kop van het raster voor deze pagina.
 *
 * @param  array    $attrs Attributen.
 * @param  string   $soort Soort archief.
 * @param  WP_Query $q     Hoofdlijst.
 * @return array{label: string, kop: string}
 */
function sfp_page_config_artikelraster_teksten( $attrs, $soort, $q ) {
    switch ( $soort ) {
        case 'categorie':
        case 'tag':
            $term = get_queried_object();
            return array(
                'label' => sfp_page_config_tekst( 'archief_artikelen' ),
                'kop'   => sfp_page_config_tekst_f( 'archief_alle_over', $term instanceof WP_Term ? mb_strtolower( $term->name ) : '' ),
            );
        case 'zoeken':
            return array(
                'label' => sfp_page_config_tekst( 'archief_resultaten' ),
                'kop'   => sfp_page_config_tekst_f( 'archief_pagina_van', max( 1, (int) get_query_var( 'paged' ) ), max( 1, (int) $q->max_num_pages ) ),
            );
        case 'auteur':
            $user = get_queried_object();
            $voor = $user instanceof WP_User ? ( '' !== (string) $user->first_name ? (string) $user->first_name : (string) $user->display_name ) : '';
            return array(
                'label' => sfp_page_config_tekst( 'archief_artikelen' ),
                'kop'   => sfp_page_config_tekst_f( 'archief_van', $voor ),
            );
    }
    $tsoort = sfp_page_config_archief_tekstsoort( $soort );
    $label  = wp_strip_all_tags( sfp_page_config_archief_getypt( $attrs, $tsoort, 'label' ) );
    $kop    = wp_strip_all_tags( sfp_page_config_archief_getypt( $attrs, $tsoort, 'kop' ) );
    if ( 'blog' === $tsoort ) {
        return array(
            'label' => '' !== $label ? $label : sfp_page_config_tekst( 'archief_artikelen' ),
            'kop'   => '' !== $kop ? $kop : sfp_page_config_tekst( 'archief_nieuwste' ),
        );
    }
    $obj  = get_post_type_object( $tsoort );
    $naam = $obj ? mb_strtolower( (string) $obj->labels->name ) : '';
    return array(
        'label' => '' !== $label ? $label : ( 'begrippen' === $tsoort ? sfp_page_config_tekst( 'archief_atotz' ) : ( $obj ? (string) $obj->labels->name : '' ) ),
        'kop'   => '' !== $kop ? $kop : sfp_page_config_tekst_f( 'archief_alle_soort', $naam ),
    );
}

/**
 * De paginering: nummers, de huidige actief, en Volgende als tekstlink.
 *
 * @param  WP_Query $q Hoofdlijst.
 * @return string      Leeg bij één pagina.
 */
function sfp_page_config_archief_paginering( $q ) {
    $totaal = (int) $q->max_num_pages;
    if ( $totaal < 2 ) {
        return '';
    }
    $nu    = max( 1, (int) get_query_var( 'paged' ) );
    $delen = paginate_links(
        array(
            'total'     => $totaal,
            'current'   => $nu,
            'mid_size'  => 1,
            'end_size'  => 1,
            'prev_next' => false,
            'type'      => 'array',
        )
    );
    if ( ! is_array( $delen ) ) {
        return '';
    }
    $html = $nu > 1 ? '<a class="sfp-tekstlink sfp-paginering__vorige" href="' . esc_url( get_pagenum_link( $nu - 1 ) ) . '">' . esc_html( sfp_page_config_tekst( 'archief_vorige' ) ) . '</a>' : '';
    foreach ( $delen as $deel ) {
        $html .= str_replace( 'page-numbers current', 'page-numbers current is-actief', $deel );
    }
    if ( $nu < $totaal ) {
        $html .= '<a class="sfp-tekstlink sfp-paginering__volgende" href="' . esc_url( get_pagenum_link( $nu + 1 ) ) . '">' . esc_html( sfp_page_config_tekst( 'archief_volgende' ) ) . '</a>';
    }
    return '<nav class="sfp-paginering" aria-label="' . esc_attr( sfp_page_config_tekst( 'archief_paginering' ) ) . '">' . $html . '</nav>';
}

/**
 * Het aantal onder een raster van één pagina.
 *
 * @param  int    $aantal Aantal.
 * @param  string $soort  Soort archief.
 * @return string
 */
function sfp_page_config_archief_aantal( $aantal, $soort ) {
    if ( 'zoeken' === $soort ) {
        return sfp_page_config_tekst_f( 1 === $aantal ? 'archief_n_resultaten_1' : 'archief_n_resultaten', $aantal );
    }
    if ( ! function_exists( 'sfp_page_config_gids_tel' ) ) {
        return '';
    }
    if ( 'begrippen' === $soort ) {
        return sfp_page_config_gids_tel( $aantal, 'n_begrippen' );
    }
    if ( 'tool' === $soort ) {
        return '';
    }
    return sfp_page_config_gids_tel( $aantal, 'n_artikelen' );
}

/**
 * sfp/artikelraster.
 *
 * @param  array $attrs Attributen.
 * @return string
 */
function sfp_page_config_render_artikelraster( $attrs ) {
    $soort = sfp_page_config_archief_soort();
    if ( 'voorbeeld' === $soort ) {
        $posts = function_exists( 'sfp_page_config_artikelen_lijst' ) ? sfp_page_config_artikelen_lijst( 3 ) : array();
        $q     = new WP_Query();
    } else {
        $q = sfp_page_config_archief_query();
        if ( ! $q || ! ( $q->is_archive() || $q->is_home() || $q->is_search() ) ) {
            return '';
        }
        $posts = $q->posts;
    }
    if ( ! function_exists( 'sfp_page_config_artikel_kaart' ) ) {
        return '';
    }
    $t       = sfp_page_config_artikelraster_teksten( $attrs, 'voorbeeld' === $soort ? 'blog' : $soort, $q );
    $kaarten = '';
    foreach ( $posts as $p ) {
        $p = get_post( $p );
        if ( ! $p instanceof WP_Post ) {
            continue;
        }
        $kaarten .= sfp_page_config_artikel_kaart(
            $p,
            true,
            array(
                'label'      => sfp_page_config_archief_kaart_label( $p, $soort ),
                'meta'       => sfp_page_config_archief_kaart_meta( $p, $soort ),
                'pijl'       => false,
                'uittreksel' => sfp_page_config_archief_uittreksel( $p ),
            )
        );
    }
    $onder = '';
    if ( '' === $kaarten ) {
        $onder = '<p class="sfp-aantal">' . esc_html( sfp_page_config_tekst( 'archief_geen' ) ) . '</p>';
    } elseif ( 'voorbeeld' !== $soort ) {
        $onder = sfp_page_config_archief_paginering( $q );
        if ( '' === $onder ) {
            $aantal = sfp_page_config_archief_aantal( (int) $q->found_posts, $soort );
            $onder  = '' !== $aantal ? '<p class="sfp-aantal">' . esc_html( $aantal ) . '</p>' : '';
        }
    }
    $achter = sfp_page_config_blok_kies( sfp_page_config_blok_attr( $attrs, 'achtergrond', 'auto' ), array( 'auto', 'wit', 'tint' ) );
    if ( 'auto' === $achter ) {
        $achter = sfp_page_config_archief_gidsen_getoond() ? 'wit' : 'tint';
    }
    $html = '<p class="is-style-sfp-label">' . esc_html( $t['label'] ) . '</p>'
        . '<h2 class="wp-block-heading">' . esc_html( $t['kop'] ) . '</h2>'
        . ( '' !== $kaarten ? '<div class="sfp-kolommen sfp-kolommen--drie sfp-artikelen sfp-raster">' . $kaarten . '</div>' : '' )
        . $onder;
    return sfp_page_config_archief_css( 'sfp/artikelraster' )
        . sfp_page_config_archief_sectie( array( 'tint' === $achter ? 'sfp-sectie--tint' : '', 'sfp-archiefraster' ), $html );
}

/* =========================================================================
 * Auteurskop (R4)
 * ====================================================================== */

/**
 * De gebruiker van de Auteurskop: de auteur van de pagina, in de editor de
 * eigen gebruiker.
 *
 * @return WP_User|null
 */
function sfp_page_config_auteurskop_gebruiker() {
    if ( sfp_page_config_archief_voorbeeld() ) {
        $u = wp_get_current_user();
        return $u && $u->exists() ? $u : null;
    }
    $u = is_author() ? get_queried_object() : null;
    return $u instanceof WP_User ? $u : null;
}

/**
 * De biografie: alinea's, regels met √ als lijst met vinkjes.
 *
 * @param  WP_User $user Gebruiker.
 * @return string
 */
function sfp_page_config_auteurskop_bio( WP_User $user ) {
    $bio = trim( (string) get_the_author_meta( 'description', $user->ID ) );
    if ( '' === $bio ) {
        return '';
    }
    $html = wpautop( wp_kses_post( $bio ) );
    // Een alinea waarvan elke regel met √ begint, wordt een lijst met vinkjes
    // (dezelfde afspraak als het onderdeel Auteursbio, zonder diens opmaak).
    return (string) preg_replace_callback(
        '#<p>(.*?)</p>#su',
        static function ( $m ) {
            $regels = array_values( array_filter( array_map( 'trim', preg_split( '#<br\s*/?>#i', $m[1] ) ), 'strlen' ) );
            if ( ! $regels ) {
                return $m[0];
            }
            $items = '';
            foreach ( $regels as $regel ) {
                if ( 0 !== strpos( $regel, '√' ) ) {
                    return $m[0];
                }
                $items .= '<li>' . trim( (string) preg_replace( '/^\s*√\s*/u', '', $regel ) ) . '</li>';
            }
            return '<div class="sfp-lijst sfp-lijst--vink"><ul>' . $items . '</ul></div>';
        },
        $html
    );
}

/**
 * sfp/auteurskop. Alleen op een auteurspagina (en als voorbeeld in de editor).
 *
 * @return string
 */
function sfp_page_config_render_auteurskop() {
    $user = sfp_page_config_auteurskop_gebruiker();
    if ( ! $user ) {
        return '';
    }
    $naam    = (string) $user->display_name;
    $voor    = '' !== (string) $user->first_name ? (string) $user->first_name : $naam;
    $foto_id = (int) get_user_meta( $user->ID, 'sfp_auteur_foto', true );
    $foto    = $foto_id > 0 && wp_attachment_is_image( $foto_id )
        ? wp_get_attachment_image(
            $foto_id,
            'medium_large',
            false,
            array(
                'class'         => 'sfp-auteur__foto',
                'alt'           => $naam,
                'fetchpriority' => 'high',
                'decoding'      => 'async',
                'sizes'         => '(max-width: 700px) 120px, (max-width: 980px) 200px, 280px',
            )
        )
        : '';
    $rol     = trim( (string) get_user_meta( $user->ID, 'sfp_auteur_rol', true ) );
    $profiel = esc_url( (string) get_user_meta( $user->ID, 'sfp_auteur_profiel', true ) );
    $linkedin = esc_url( (string) get_user_meta( $user->ID, 'sfp_auteur_linkedin', true ) );
    $links   = ( '' !== $profiel ? '<a class="sfp-tekstlink" href="' . $profiel . '">' . esc_html( sfp_page_config_tekst_f( 'archief_meer_over', $voor ) ) . '</a>' : '' )
        . ( '' !== $linkedin ? '<a class="sfp-tekstlink" href="' . $linkedin . '" rel="me noopener" target="_blank">LinkedIn</a>' : '' );
    $tekst   = '<p class="is-style-sfp-label">' . esc_html( sfp_page_config_tekst( 'archief_profiel' ) ) . '</p>'
        . '<h1 class="wp-block-heading">' . esc_html( $naam ) . '</h1>'
        . ( '' !== $rol ? '<p class="sfp-auteur__rol">' . esc_html( $rol ) . '</p>' : '' )
        . sfp_page_config_auteurskop_bio( $user )
        . ( '' !== $links ? '<p class="sfp-auteur__links">' . $links . '</p>' : '' );
    $html    = '<div class="sfp-auteur' . ( '' === $foto ? ' sfp-auteur--zonder-foto' : '' ) . '">' . $foto . '<div class="sfp-auteur__tekst">' . $tekst . '</div></div>';
    return sfp_page_config_archief_css( 'sfp/auteurskop' ) . sfp_page_config_blokken_css_vangnet( 'sfp/lijst' )
        . sfp_page_config_archief_sectie( array( 'sfp-auteurkop' ), $html );
}

add_action( 'show_user_profile', 'sfp_page_config_auteur_profielvelden' );
add_action( 'edit_user_profile', 'sfp_page_config_auteur_profielvelden' );

/**
 * De velden van de Auteurskop in het profiel van een gebruiker.
 *
 * @param WP_User $user Gebruiker.
 */
function sfp_page_config_auteur_profielvelden( $user ) {
    if ( ! $user instanceof WP_User || ! current_user_can( 'edit_user', $user->ID ) ) {
        return;
    }
    wp_enqueue_media();
    $foto_id  = (int) get_user_meta( $user->ID, 'sfp_auteur_foto', true );
    $foto_url = $foto_id > 0 ? (string) wp_get_attachment_image_url( $foto_id, 'thumbnail' ) : '';
    wp_nonce_field( 'sfp_auteur_profiel', 'sfp_auteur_nonce' );
    echo '<h2>Auteurskop (SFP Page Config)</h2><p class="description">Voor het blok Auteurskop op de auteurspagina. Naam en biografie staan hierboven; regels in de biografie die met √ beginnen worden een lijst met vinkjes.</p>';
    echo '<table class="form-table" role="presentation">';
    echo '<tr><th><label for="sfp_auteur_foto_knop">Foto</label></th><td>'
        . '<img id="sfp_auteur_foto_beeld" src="' . esc_url( $foto_url ) . '" alt="" width="96" height="96" style="border-radius:6px;object-fit:cover;display:' . ( '' !== $foto_url ? 'block' : 'none' ) . ';margin-bottom:8px">'
        . '<input type="hidden" id="sfp_auteur_foto" name="sfp_auteur_foto" value="' . esc_attr( (string) $foto_id ) . '">'
        . '<button type="button" class="button" id="sfp_auteur_foto_knop">Kies een foto</button> '
        . '<button type="button" class="button-link" id="sfp_auteur_foto_weg">Geen foto</button>'
        . '<p class="description">Vierkant werkt het best. Zonder foto staat de tekst alleen.</p></td></tr>';
    echo '<tr><th><label for="sfp_auteur_rol">Rol</label></th><td><input type="text" class="regular-text" id="sfp_auteur_rol" name="sfp_auteur_rol" value="' . esc_attr( (string) get_user_meta( $user->ID, 'sfp_auteur_rol', true ) ) . '"><p class="description">Bijvoorbeeld: Presentatietrainer en oprichter.</p></td></tr>';
    echo '<tr><th><label for="sfp_auteur_profiel">Profielpagina</label></th><td><input type="url" class="regular-text" id="sfp_auteur_profiel" name="sfp_auteur_profiel" value="' . esc_attr( (string) get_user_meta( $user->ID, 'sfp_auteur_profiel', true ) ) . '"><p class="description">De link Meer over. Leeg: geen link.</p></td></tr>';
    echo '<tr><th><label for="sfp_auteur_linkedin">LinkedIn</label></th><td><input type="url" class="regular-text" id="sfp_auteur_linkedin" name="sfp_auteur_linkedin" value="' . esc_attr( (string) get_user_meta( $user->ID, 'sfp_auteur_linkedin', true ) ) . '"></td></tr>';
    echo '</table>';
    ?>
<script>
document.addEventListener( 'DOMContentLoaded', function () {
	var knop = document.getElementById( 'sfp_auteur_foto_knop' ), weg = document.getElementById( 'sfp_auteur_foto_weg' ), veld = document.getElementById( 'sfp_auteur_foto' ), beeld = document.getElementById( 'sfp_auteur_foto_beeld' );
	if ( ! knop || ! window.wp || ! wp.media ) { return; }
	knop.addEventListener( 'click', function () {
		var kader = wp.media( { title: 'Kies een foto', library: { type: 'image' }, multiple: false } );
		kader.on( 'select', function () {
			var k = kader.state().get( 'selection' ).first().toJSON();
			veld.value = k.id;
			beeld.src = k.sizes && k.sizes.thumbnail ? k.sizes.thumbnail.url : k.url;
			beeld.style.display = 'block';
		} );
		kader.open();
	} );
	weg.addEventListener( 'click', function () { veld.value = ''; beeld.style.display = 'none'; } );
} );
</script>
    <?php
}

add_action( 'personal_options_update', 'sfp_page_config_auteur_profiel_bewaar' );
add_action( 'edit_user_profile_update', 'sfp_page_config_auteur_profiel_bewaar' );

/**
 * Bewaar de velden van de Auteurskop.
 *
 * @param int $user_id Gebruiker.
 */
function sfp_page_config_auteur_profiel_bewaar( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) || ! isset( $_POST['sfp_auteur_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sfp_auteur_nonce'] ) ), 'sfp_auteur_profiel' ) ) {
        return;
    }
    $foto = isset( $_POST['sfp_auteur_foto'] ) ? absint( $_POST['sfp_auteur_foto'] ) : 0;
    if ( $foto && wp_attachment_is_image( $foto ) ) {
        update_user_meta( $user_id, 'sfp_auteur_foto', $foto );
    } else {
        delete_user_meta( $user_id, 'sfp_auteur_foto' );
    }
    $rol = isset( $_POST['sfp_auteur_rol'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['sfp_auteur_rol'] ) ), 0, 120 ) : '';
    '' !== $rol ? update_user_meta( $user_id, 'sfp_auteur_rol', $rol ) : delete_user_meta( $user_id, 'sfp_auteur_rol' );
    foreach ( array( 'sfp_auteur_profiel', 'sfp_auteur_linkedin' ) as $sleutel ) {
        $url = isset( $_POST[ $sleutel ] ) ? esc_url_raw( trim( wp_unslash( (string) $_POST[ $sleutel ] ) ), array( 'https', 'http' ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- esc_url_raw.
        '' !== $url ? update_user_meta( $user_id, $sleutel, $url ) : delete_user_meta( $user_id, $sleutel );
    }
}

/* =========================================================================
 * Contactgegevens (L1)
 * ====================================================================== */

/**
 * Een telefoonnummer in de netwerknotatie (besluit Stephan 8-11-2026):
 * 085 060 1355, mobiel 06 1234 5678. Een buitenlands nummer blijft zoals
 * het is ingevuld.
 *
 * @param  string $nummer Nummer, met of zonder +31.
 * @return array{tekst: string, tel: string} Weergave en het nummer voor tel:.
 */
function sfp_page_config_telefoon( $nummer ) {
    $nummer = trim( (string) $nummer );
    $cijfer = preg_replace( '/[^0-9]/', '', $nummer );
    if ( '' === $cijfer ) {
        return array(
            'tekst' => '',
            'tel'   => '',
        );
    }
    if ( 0 === strpos( $cijfer, '0031' ) ) {
        $cijfer = substr( $cijfer, 2 );
    }
    $nl = '';
    if ( 0 === strpos( $cijfer, '31' ) && 11 === strlen( $cijfer ) ) {
        $nl = '0' . substr( $cijfer, 2 );
    } elseif ( 0 === strpos( $cijfer, '0' ) && 10 === strlen( $cijfer ) ) {
        $nl = $cijfer;
    }
    if ( '' === $nl ) {
        return array(
            'tekst' => preg_replace( '/\s+/', ' ', $nummer ),
            'tel'   => ( 0 === strpos( $nummer, '+' ) ? '+' : '' ) . $cijfer,
        );
    }
    $tekst = 0 === strpos( $nl, '06' )
        ? substr( $nl, 0, 2 ) . ' ' . substr( $nl, 2, 4 ) . ' ' . substr( $nl, 6 )
        : substr( $nl, 0, 3 ) . ' ' . substr( $nl, 3, 3 ) . ' ' . substr( $nl, 6 );
    return array(
        'tekst' => $tekst,
        'tel'   => '+31' . substr( $nl, 1 ),
    );
}

/**
 * Schoon de instelling Contact op.
 *
 * @param  mixed $ruw Ruwe instelling.
 * @return array{telefoon: string, email: string, adres: string}
 */
function sfp_page_config_contact_schoon( $ruw ) {
    $ruw = is_array( $ruw ) ? $ruw : array();
    return array(
        'telefoon' => mb_substr( trim( (string) preg_replace( '/[^0-9+ ()-]/', '', (string) ( $ruw['telefoon'] ?? '' ) ) ), 0, 24 ),
        'email'    => sanitize_email( (string) ( $ruw['email'] ?? '' ) ),
        'adres'    => mb_substr( trim( sanitize_text_field( (string) ( $ruw['adres'] ?? '' ) ) ), 0, 160 ),
    );
}

/**
 * De bewaarde instelling Contact.
 *
 * @return array{telefoon: string, email: string, adres: string}
 */
function sfp_page_config_contact_instelling() {
    $alle = function_exists( 'sfp_page_config_instellingen' ) ? sfp_page_config_instellingen() : array();
    return sfp_page_config_contact_schoon( isset( $alle['contact'] ) ? $alle['contact'] : array() );
}

/**
 * Het e-mailadres van het netwerk: info@ op het domein van de site.
 *
 * @return string
 */
function sfp_page_config_contact_email_norm() {
    $host = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
    $host = preg_replace( '/^www\./', '', $host );
    return '' !== $host && false !== strpos( $host, '.' ) ? 'info@' . $host : '';
}

/**
 * De contactgegevens zoals de site ze toont: ingevuld in SFP Beheer, anders
 * de netwerknorm. Telefoon: Contact, anders het nummer van de USP-strook,
 * anders het WhatsApp-nummer. WhatsApp: het nummer van de WhatsApp-knop.
 *
 * @return array{telefoon: array, whatsapp: array, whatsapp_url: string, email: string, adres: string, maps: string}
 */
function sfp_page_config_contact() {
    $c   = sfp_page_config_contact_instelling();
    $wa  = trim( (string) sfp_page_config_get_setting( 'whatsapp_number', '' ) );
    $wa  = '' !== $wa ? $wa : sfp_page_config_whatsapp_norm();
    $tel = $c['telefoon'];
    if ( '' === $tel && function_exists( 'sfp_page_config_uspstrook_instelling' ) ) {
        $usp = sfp_page_config_uspstrook_instelling();
        $tel = (string) $usp['telefoon'];
    }
    $tel     = '' !== $tel ? $tel : $wa;
    $wa_cijf = preg_replace( '/[^0-9]/', '', $wa );
    $bericht = function_exists( 'sfp_page_config_tekst' ) ? sfp_page_config_tekst( 'whatsapp_bericht' ) : '';
    return array(
        'telefoon'     => sfp_page_config_telefoon( $tel ),
        'whatsapp'     => sfp_page_config_telefoon( $wa ),
        'whatsapp_url' => '' !== $wa_cijf ? 'https://wa.me/' . $wa_cijf . ( '' !== $bericht ? '?text=' . rawurlencode( $bericht ) : '' ) : '',
        'email'        => '' !== $c['email'] ? $c['email'] : sfp_page_config_contact_email_norm(),
        'adres'        => $c['adres'],
        'maps'         => '' !== $c['adres'] ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $c['adres'] ) : '',
    );
}

/**
 * Voor het scherm Contact in SFP Beheer: het bewaarde en de norm.
 *
 * @return array
 */
function sfp_page_config_contact_beheer() {
    $c   = sfp_page_config_contact_instelling();
    $toon = sfp_page_config_contact();
    return array(
        'telefoon'      => $c['telefoon'],
        'email'         => $c['email'],
        'adres'         => $c['adres'],
        'telefoon_toon' => $toon['telefoon']['tekst'],
        'email_norm'    => sfp_page_config_contact_email_norm(),
    );
}

/**
 * sfp/contactgegevens. Een regel zonder waarde valt weg.
 *
 * @return string
 */
function sfp_page_config_render_contactgegevens() {
    $c     = sfp_page_config_contact();
    $regel = static function ( $label, $waarde ) {
        return '<div><dt class="is-style-sfp-label">' . esc_html( $label ) . '</dt><dd>' . $waarde . '</dd></div>';
    };
    $html = '';
    if ( '' !== $c['telefoon']['tekst'] ) {
        $html .= $regel( sfp_page_config_tekst( 'contact_telefoon' ), '<a href="tel:' . esc_attr( $c['telefoon']['tel'] ) . '">' . esc_html( $c['telefoon']['tekst'] ) . '</a>' );
    }
    if ( '' !== $c['whatsapp']['tekst'] && '' !== $c['whatsapp_url'] ) {
        $html .= $regel( sfp_page_config_tekst( 'contact_whatsapp' ), '<a href="' . esc_url( $c['whatsapp_url'] ) . '" target="_blank" rel="noopener">' . esc_html( $c['whatsapp']['tekst'] ) . '</a>' );
    }
    if ( '' !== $c['email'] ) {
        $html .= $regel( sfp_page_config_tekst( 'contact_email' ), '<a href="mailto:' . esc_attr( antispambot( $c['email'] ) ) . '">' . esc_html( antispambot( $c['email'] ) ) . '</a>' );
    }
    if ( '' !== $c['adres'] ) {
        $html .= $regel( sfp_page_config_tekst( 'contact_adres' ), esc_html( $c['adres'] ) . '<br><a class="sfp-tekstlink" href="' . esc_url( $c['maps'] ) . '" target="_blank" rel="noopener">' . esc_html( sfp_page_config_tekst( 'contact_maps' ) ) . '</a>' );
    }
    if ( '' === $html ) {
        return '';
    }
    return sfp_page_config_blokken_css_vangnet( 'sfp/contactgegevens' ) . '<dl ' . sfp_page_config_blok_omhulling( array( 'sfp-gegevens' ) ) . '>' . $html . '</dl>';
}

/* =========================================================================
 * Editor
 * ====================================================================== */

add_action( 'enqueue_block_editor_assets', 'sfp_page_config_archief_editor_data', 20 );

/**
 * Gegevens voor de editor: of er een begrippen- en toolsarchief is.
 */
function sfp_page_config_archief_editor_data() {
    if ( ! wp_script_is( 'sfp-blokken-editor', 'registered' ) ) {
        return;
    }
    $data = array(
        'begrippen' => post_type_exists( 'begrippen' ),
        'tool'      => post_type_exists( 'tool' ),
        'blog'      => sfp_page_config_archief_blog_naam(),
    );
    wp_add_inline_script( 'sfp-blokken-editor', 'window.sfpArchief=' . wp_json_encode( $data ) . ';', 'before' );
}
