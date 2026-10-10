<?php
/**
 * SFP Page Config - Affiliate Box
 *
 * Registers `_has_affiliate_links` post meta and appends the affiliate
 * disclosure box (from an Astra Custom Layout) after the post content
 * when the checkbox is enabled.
 *
 * The affiliate toggle is shown in the SFP Page Config metabox (on posts
 * only) instead of a separate Gutenberg sidebar panel.
 *
 * The Astra layout post ID is configured per site so each domain can
 * use its own disclosure template.
 *
 * Replaces the old ASE Pro "Affiliate checkbox" snippet.
 *
 * Sinds 2.12.0, op sites met de artikelopmaak aan (besluit Stephan
 * 10 oktober 2026, roadmap eeat-06): de plugin herkent affiliatelinks zelf,
 * ook als ze alleen in een tooltip staan, aan de patronen uit de instelling
 * "affiliate_patronen". Gevonden: een korte regel in de kopkaart, als link
 * naar een uitlegblok onderaan met het anker #affiliate. Niet gevonden: geen
 * van beide. Het vinkje en de Astra-layout hieronder gelden dan niet meer;
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
    register_post_meta( 'post', '_has_affiliate_links', array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'boolean',
        'default'       => false,
        'auth_callback' => function () {
            return current_user_can( 'edit_posts' );
        },
    ) );
}

/* =========================================================================
 * 2. Affiliate layout post ID per site
 * ====================================================================== */

/**
 * Get the Astra Custom Layout post ID that contains the affiliate box.
 *
 * Returns null when no layout is configured for the current site, which
 * effectively disables the feature on that domain.
 *
 * @return int|null
 */
function sfp_page_config_get_affiliate_layout_id() {

    $domain = wp_parse_url( home_url(), PHP_URL_HOST );

    $layouts = array(
        'depresenteerschool.nl' => 27038,
    );

    foreach ( $layouts as $host => $post_id ) {
        if ( false !== strpos( $domain, $host ) ) {
            return $post_id;
        }
    }

    return null;
}

/* =========================================================================
 * 3. Append affiliate box via the_content
 * ====================================================================== */

add_filter( 'the_content', 'sfp_page_config_affiliate_content', 50 );

/**
 * Append the affiliate disclosure box after the post content when enabled.
 *
 * @param  string $content The post content.
 * @return string
 */
function sfp_page_config_affiliate_content( $content ) {

    static $running = false;

    // Prevent recursion.
    if ( $running ) {
        return $content;
    }

    if ( ! is_singular( 'post' ) ) {
        return $content;
    }

    // Met de artikelopmaak aan regelt de automatische melding dit.
    if ( function_exists( 'sfp_page_config_artikel_aan' ) && sfp_page_config_artikel_aan() ) {
        return $content;
    }

    if ( ! get_post_meta( get_the_ID(), '_has_affiliate_links', true ) ) {
        return $content;
    }

    $layout_id = sfp_page_config_get_affiliate_layout_id();
    if ( ! $layout_id ) {
        return $content;
    }

    $layout_content = get_post_field( 'post_content', $layout_id );
    if ( ! $layout_content ) {
        return $content;
    }

    try {
        $running = true;
        $rendered = apply_filters( 'the_content', $layout_content );
    } finally {
        $running = false;
    }

    $content .= $rendered;

    return $content;
}

/* =========================================================================
 * 4. Automatische melding in de artikelopmaak (sinds 2.12.0)
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
            $delen = array_map(
                function ( $deel ) {
                    return preg_quote( $deel, '#' );
                },
                explode( '*', $patroon )
            );
            if ( preg_match( '#' . implode( '[^\\s"\'<>]*', $delen ) . '#i', $inhoud ) ) {
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
    $regel  = trim( (string) sfp_page_config_get_setting( 'affiliate_regel', '' ) );
    $uitleg = trim( (string) sfp_page_config_get_setting( 'affiliate_uitleg', '' ) );
    // De Nederlandse teksten zijn vastgesteld door Stephan (8 oktober 2026).
    // Voor andere talen is er geen standaard: zonder tekst geen melding.
    if ( function_exists( 'sfp_page_config_artikel_taal' ) && 'nl' === sfp_page_config_artikel_taal() ) {
        if ( '' === $regel ) {
            $regel = 'Bevat affiliate links';
        }
        if ( '' === $uitleg ) {
            $uitleg = 'In dit artikel verwijzen we soms naar boeken en hulpmiddelen via een affiliate link. Koop je via zo\'n link iets, dan ontvangen wij een kleine commissie. Jij betaalt niets extra. Zo kunnen we de kwaliteit van ons expertisecentrum waarborgen.';
        }
    }
    return array(
        'regel'  => $regel,
        'uitleg' => $uitleg,
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
