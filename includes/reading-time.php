<?php
/**
 * SFP Page Config - Reading Time + Scroll Progress Bar
 *
 * Provides:
 *  - [mijn_leestijd] shortcode (smart word count based on readable blocks)
 *  - Scroll progress bar rendered via wp_footer
 *  - Progress bar JS (scroll percentage)
 *
 * Replaces the old ASE Pro snippets "Leestijdberekenaar" and "Voortgangsbalk".
 *
 * @package SFP_Page_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * 1. Reading time calculation
 * ====================================================================== */

/**
 * Calculate reading time from readable Gutenberg blocks only.
 *
 * Ignores interactive tools, forms, custom HTML, embeds, etc.
 *
 * @param  string $content Raw post_content with block delimiters.
 * @return string          HTML like "LEESTIJD: <span>5</span> minuten".
 */
function sfp_page_config_calculate_reading_time( $content ) {

    // Read from settings (default 250). Clamped between 100 and 500 by
    // the sanitizer, so no need to re-validate here.
    $words_per_minute = (int) sfp_page_config_get_setting( 'words_per_minute', 250 );
    if ( $words_per_minute < 100 ) {
        $words_per_minute = 250;
    }

    $readable_blocks = array(
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/list-item',
        'core/quote',
        'core/pullquote',
        'core/verse',
        'core/preformatted',
        'core/freeform',
        'core/table',
        'core/media-text',
        'core/column',
        'core/columns',
        'core/group',
        'uagb/info-box',
        'uagb/icon-list',
        'uagb/inline-notice',
        'uagb/faq',
    );

    $blocks = parse_blocks( $content );

    /**
     * Recursively collect readable text from blocks.
     *
     * @param  array    $blocks          Parsed blocks.
     * @param  string[] $readable_blocks Allowed block names.
     * @return string                    Concatenated HTML.
     */
    $collect = function ( $blocks ) use ( &$collect, $readable_blocks ) {
        $output = '';
        foreach ( $blocks as $block ) {
            $type = $block['blockName'] ?? '';
            if ( in_array( $type, $readable_blocks, true ) ) {
                $output .= ' ' . $block['innerHTML'];
            }
            if ( ! empty( $block['innerBlocks'] ) ) {
                $output .= $collect( $block['innerBlocks'] );
            }
        }
        return $output;
    };

    $text  = $collect( $blocks );
    $words = count( preg_split( '/\s+/', strip_tags( $text ), -1, PREG_SPLIT_NO_EMPTY ) );
    $mins  = max( 1, (int) ceil( $words / $words_per_minute ) );

    $label = sfp_page_config_tekst( 1 === $mins ? 'minuut' : 'minuten' );

    return esc_html( sfp_page_config_tekst( 'leestijd_label' ) ) . ': <span class="tijd-getal">' . $mins . '</span> ' . esc_html( $label );
}

/* =========================================================================
 * 2. Shortcode [mijn_leestijd]
 * ====================================================================== */

if ( ! shortcode_exists( 'mijn_leestijd' ) ) {
    add_shortcode( 'mijn_leestijd', 'sfp_page_config_shortcode_reading_time' );
}

/**
 * Shortcode: [mijn_leestijd]
 *
 * Een vlag voorkomt dat de code zichzelf eindeloos aanroept als hij in de
 * inhoud staat die hij meet. Een geneste aanroep geeft een lege tekst.
 *
 * @return string HTML output.
 */
function sfp_page_config_shortcode_reading_time() {

    static $bezig = false;
    global $post;

    if ( $bezig || ! $post instanceof WP_Post || '' === (string) $post->post_content ) {
        return '';
    }

    $bezig = true;
    try {
        $result = '<div class="custom-read-meter">'
            . sfp_page_config_calculate_reading_time( $post->post_content )
            . '</div>';
    } finally {
        $bezig = false;
    }

    return $result;
}

add_action( 'wp_enqueue_scripts', 'sfp_page_config_scroll_progress_enqueue' );

/**
 * Enqueue the scroll progress bar stylesheet on singular pages.
 */
function sfp_page_config_scroll_progress_enqueue() {

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return;
    }

    // Laadt ook met de voortgangsbalk uit: het bestand bevat de opmaak van
    // de leestijd.
    wp_enqueue_style(
        'sfp-reading-time',
        SFP_PAGE_CONFIG_URL . 'assets/reading-time.css',
        array(),
        SFP_PAGE_CONFIG_VERSION
    );

    // De balk draagt de knopkleur van de site, uit Astra. Kleurvelden zijn
    // er sinds 3.0.0 niet meer.
    $brand = sfp_page_config_get_brand();

    wp_add_inline_style(
        'sfp-reading-time',
        ':root{--sfp-bar-color:' . $brand['cta_bg'] . ';}'
    );
}

add_action( 'wp_footer', 'sfp_page_config_scroll_progress_bar', 25 );

/**
 * Render the scroll progress bar container and its JS driver.
 *
 * Sitewide on all singular posts and pages (not gated by the longread
 * toggle anymore). Uses a brand-coloured bar with requestAnimationFrame
 * throttling. Brand colour comes from the site config (CTA background).
 * CSS is loaded via wp_enqueue_style in sfp_page_config_scroll_progress_enqueue().
 */
function sfp_page_config_scroll_progress_bar() {

    if ( ! is_singular( sfp_page_config_post_types() ) || ! sfp_page_config_onderdeel_aan( 'voortgang' ) ) {
        return;
    }
    // De testpagina van de zelftest heeft een eigen voortgang per vraag.
    if ( function_exists( 'sfp_zelftest_pagina_test' ) && '' !== sfp_zelftest_pagina_test() ) {
        return;
    }
    // Een tool heeft een eigen voortgang per vraag; de leesbalk zou daar
    // dubbelop zijn.
    if ( function_exists( 'sfp_page_config_tool_aan' ) && sfp_page_config_tool_aan() && is_singular( 'tool' ) ) {
        return;
    }

    ?>
    <div id="sfp-scroll-container" aria-hidden="true"><div id="sfp-scroll-bar"></div></div>
    <script>
    (function () {
        var bar = document.getElementById('sfp-scroll-bar');
        if (!bar) return;
        var ticking = false;
        function update() {
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            if (height <= 0) return;
            var pct = (scrollTop / height) * 100;
            if (pct < 0) pct = 0;
            if (pct > 100) pct = 100;
            bar.style.width = pct + '%';
            ticking = false;
        }
        window.addEventListener('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(update);
                ticking = true;
            }
        }, { passive: true });
        update();
    })();
    </script>
    <?php
}
