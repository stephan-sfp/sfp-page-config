<?php
/**
 * SFP Page Config - Affiliate Box
 *
 * Registers `_has_affiliate_links` post meta. Since 3.22.0 the plugin no
 * longer appends a disclosure box itself; a site that wants one reads the
 * meta in its own snippet.
 *
 * The affiliate toggle is shown in the SFP Page Config metabox (on posts
 * only) instead of a separate Gutenberg sidebar panel.
 *
 * Replaces the old ASE Pro "Affiliate checkbox" snippet.
 *
 * Sinds 2.12.0, op sites met de artikelopmaak aan (besluit Stephan
 * 10 oktober 2026, roadmap eeat-06): de plugin herkent affiliatelinks zelf,
 * ook als ze alleen in een tooltip staan, aan de patronen uit de instelling
 * "affiliate_patronen". Gevonden: een korte regel in de kopkaart, als link
 * naar een uitlegblok onderaan met het anker #affiliate. Niet gevonden: geen
 * van beide. Het vinkje geldt dan niet meer;
 * op sites zonder artikelopmaak blijft alles zoals het was.
 *
 * De teksten staan in de instellingen "affiliate_regel" en
 * "affiliate_uitleg". Leeg op een Nederlandstalige site: de vastgestelde
 * Nederlandse teksten. Leeg op een andere site: geen melding, tot de tekst
 * in die taal is aangeleverd.
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * 1. Register post meta (needed for REST API / Gutenberg compat)
 * ====================================================================== */

add_action( 'init', 'sfp_page_config_register_affiliate_meta' );

/**
 * Register the _has_affiliate_links meta key for posts.
 */
function sfp_page_config_register_affiliate_meta() {
    register_post_meta(
        'post',
        '_has_affiliate_links',
        array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'boolean',
            'default'       => false,
            'auth_callback' => function () {
                return current_user_can( 'edit_posts' );
            },
        )
    );
}

/* =========================================================================
 * 2. Het vinkje "Bevat affiliate links"
 * ====================================================================== */

/**
 * Toont de editor het vinkje "Bevat affiliate links"?
 *
 * Sinds 3.22.0 (punt 23) plakt de plugin zelf geen uitlegblok meer onder
 * het bericht: dat was een Astra-layout van één site en staat nu in een
 * snippet op die site. De meta _has_affiliate_links en het vinkje blijven,
 * zodat dat snippet ze kan lezen. Het vinkje staat er alleen waar het in
 * gebruik is: zonder de artikelopmaak (die herkent affiliatelinks zelf) en
 * op een site waar minstens één bericht het vinkje aan heeft. Een site kan
 * dat met het filter sfp_page_config_affiliate_vinkje anders zetten.
 *
 * @return bool
 */
function sfp_page_config_affiliate_vinkje_toont() {
    $aan = ! ( function_exists( 'sfp_page_config_artikel_aan' ) && sfp_page_config_artikel_aan() )
        && sfp_page_config_affiliate_in_gebruik();
    return (bool) apply_filters( 'sfp_page_config_affiliate_vinkje', $aan );
}

/**
 * Heeft minstens één bericht het vinkje "Bevat affiliate links" aan?
 * Een dag onthouden; opslaan met het vinkje aan zet het meteen op ja.
 *
 * @return bool
 */
function sfp_page_config_affiliate_in_gebruik() {
    $stand = get_transient( 'sfp_pc_affiliate_in_gebruik' );
    if ( false === $stand ) {
        $ids   = get_posts(
            array(
                'post_type'      => 'post',
                'post_status'    => 'any',
                'meta_key'       => '_has_affiliate_links', // phpcs:ignore WordPress.DB.SlowDBQuery
                'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
                'fields'         => 'ids',
                'posts_per_page' => 1,
                'no_found_rows'  => true,
            )
        );
        $stand = $ids ? 'ja' : 'nee';
        set_transient( 'sfp_pc_affiliate_in_gebruik', $stand, DAY_IN_SECONDS );
    }
    return 'ja' === $stand;
}

add_action( 'updated_post_meta', 'sfp_page_config_affiliate_in_gebruik_bij', 10, 4 );
add_action( 'added_post_meta', 'sfp_page_config_affiliate_in_gebruik_bij', 10, 4 );

/**
 * Het vinkje ging aan: onthoud dat het in gebruik is.
 *
 * @param int    $meta_id  Meta-id.
 * @param int    $post_id  Bericht.
 * @param string $sleutel  Metasleutel.
 * @param mixed  $waarde   Nieuwe waarde.
 */
function sfp_page_config_affiliate_in_gebruik_bij( $meta_id, $post_id, $sleutel, $waarde ) {
    if ( '_has_affiliate_links' === $sleutel && ! empty( $waarde ) ) {
        set_transient( 'sfp_pc_affiliate_in_gebruik', 'ja', DAY_IN_SECONDS );
    }
}

/* =========================================================================
 * 3. Automatische melding in de artikelopmaak (sinds 2.12.0)
 * ====================================================================== */

/**
 * De herkenningspatronen van deze site: één per regel in de instelling
 * "affiliate_patronen". Een patroon is een stuk van de link, bijvoorbeeld
 * een domein; een sterretje staat voor een willekeurig stuk van de link.
 *
 * @return string[]
 */
function sfp_page_config_affiliate_patronen() {
    $ruw      = (string) sfp_page_config_get_setting( 'affiliate_patronen', '' );
    $patronen = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $ruw ) ), 'strlen' ) );
    /**
     * De herkenningspatronen voor affiliatelinks.
     *
     * @param string[] $patronen Patronen.
     */
    return (array) apply_filters( 'sfp_page_config_affiliate_patronen', $patronen );
}

/**
 * Het zoekpatroon bij een herkenningspatroon: een sterretje staat voor een
 * willekeurig stuk van de link, al het andere telt letterlijk.
 *
 * @param  string $patroon Herkenningspatroon.
 * @return string          Reguliere expressie.
 */
function sfp_page_config_affiliate_regex( $patroon ) {
    $delen = array_map(
        function ( $deel ) {
            return preg_quote( $deel, '#' );
        },
        explode( '*', (string) $patroon )
    );
    return '#' . implode( '[^\\s"\'<>]*', $delen ) . '#i';
}

/**
 * Per herkenningspatroon: op hoeveel gepubliceerde artikelen het een link
 * vindt. Een patroon dat nergens iets vindt, is waarschijnlijk verkeerd
 * getypt; SFP Beheer meldt dat bij Artikelen, Affiliatelinks.
 *
 * Wordt een dag bewaard en opnieuw geteld na het opslaan van een bericht of
 * van de instellingen.
 *
 * @return array<string, int>
 */
function sfp_page_config_affiliate_treffers() {
    $patronen = sfp_page_config_affiliate_patronen();
    if ( ! $patronen ) {
        return array();
    }
    $sleutel = 'sfp_pc_aff_treffers_' . md5( implode( "\n", $patronen ) );
    $bewaard = get_transient( $sleutel );
    if ( is_array( $bewaard ) ) {
        return $bewaard;
    }
    $types    = function_exists( 'sfp_page_config_artikel_posttypes' ) ? sfp_page_config_artikel_posttypes() : array( 'post' );
    $treffers = array_fill_keys( $patronen, 0 );
    $ids      = get_posts(
        array(
            'post_type'      => $types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        )
    );
    foreach ( $ids as $id ) {
        $inhoud = (string) get_post_field( 'post_content', $id );
        $inhoud = $inhoud . "\n" . html_entity_decode( $inhoud, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        foreach ( $patronen as $patroon ) {
            if ( preg_match( sfp_page_config_affiliate_regex( $patroon ), $inhoud ) ) {
                $treffers[ $patroon ]++;
            }
        }
    }
    set_transient( $sleutel, $treffers, DAY_IN_SECONDS );
    update_option( 'sfp_pc_aff_treffers_sleutel', $sleutel, false );
    return $treffers;
}

add_action( 'save_post', 'sfp_page_config_affiliate_treffers_wis' );
add_action( 'update_option_sfp_settings', 'sfp_page_config_affiliate_treffers_wis' );

/**
 * Tel de treffers opnieuw bij de volgende keer.
 */
function sfp_page_config_affiliate_treffers_wis() {
    $sleutel = get_option( 'sfp_pc_aff_treffers_sleutel' );
    if ( $sleutel ) {
        delete_transient( $sleutel );
    }
}

/**
 * Bevat dit bericht een affiliatelink? Zoekt in de inhoud en in de
 * tooltips (waar de link als gecodeerde HTML in een attribuut staat).
 *
 * @param  WP_Post $post Bericht.
 * @return bool
 */
function sfp_page_config_affiliate_heeft( $post ) {
    static $cache = array();
    if ( ! $post instanceof WP_Post ) {
        return false;
    }
    if ( isset( $cache[ $post->ID ] ) ) {
        return $cache[ $post->ID ];
    }
    $heeft    = false;
    $patronen = sfp_page_config_affiliate_patronen();
    if ( $patronen ) {
        $inhoud = $post->post_content . "\n" . html_entity_decode( $post->post_content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        foreach ( $patronen as $patroon ) {
            if ( preg_match( sfp_page_config_affiliate_regex( $patroon ), $inhoud ) ) {
                $heeft = true;
                break;
            }
        }
    }
    /**
     * Bevat dit bericht affiliatelinks?
     *
     * @param bool    $heeft Uitkomst van de herkenning.
     * @param WP_Post $post  Bericht.
     */
    $cache[ $post->ID ] = (bool) apply_filters( 'sfp_page_config_affiliate_heeft', $heeft, $post );
    return $cache[ $post->ID ];
}

/**
 * De teksten van de melding op deze site.
 *
 * @return array{regel: string, uitleg: string}
 */
function sfp_page_config_affiliate_teksten() {
    // De eigen tekst van de site, anders de standaard in de taal van de site
    // (includes/teksten.php). De Nederlandse teksten zijn vastgesteld door
    // Stephan (8 oktober 2026). Voor andere talen is er geen standaard:
    // zonder tekst geen melding.
    return array(
        'regel'  => sfp_page_config_tekst( 'affiliate_regel' ),
        'uitleg' => sfp_page_config_tekst( 'affiliate_uitleg' ),
    );
}

/**
 * Toont dit bericht de melding? Alleen met affiliatelinks en met beide teksten.
 *
 * @param  WP_Post $post Bericht.
 * @return bool
 */
function sfp_page_config_affiliate_toont( $post ) {
    if ( ! sfp_page_config_affiliate_heeft( $post ) ) {
        return false;
    }
    $teksten = sfp_page_config_affiliate_teksten();
    return '' !== $teksten['regel'] && '' !== $teksten['uitleg'];
}

/**
 * De korte regel in de kopkaart, als link naar het uitlegblok.
 *
 * @param  WP_Post $post Bericht.
 * @return string
 */
function sfp_page_config_affiliate_regel( $post ) {
    if ( ! sfp_page_config_affiliate_toont( $post ) ) {
        return '';
    }
    $teksten = sfp_page_config_affiliate_teksten();
    return '<p class="sfp-art-kop__affiliate"><a href="#affiliate">' . esc_html( $teksten['regel'] ) . '</a></p>';
}

/**
 * Het uitlegblok onderaan het artikel, met het anker #affiliate. Zelfde
 * rustige vorm als het blok Kader.
 *
 * @param  WP_Post $post Bericht.
 * @return string
 */
function sfp_page_config_affiliate_uitleg( $post ) {
    if ( ! sfp_page_config_affiliate_toont( $post ) ) {
        return '';
    }
    $teksten = sfp_page_config_affiliate_teksten();
    $css     = function_exists( 'sfp_page_config_blokken_css_vangnet' ) ? sfp_page_config_blokken_css_vangnet( 'sfp/kader' ) : '';
    return $css . '<aside class="sfp-kader sfp-art-affiliate" id="affiliate"><p>' . esc_html( $teksten['uitleg'] ) . '</p></aside>';
}
