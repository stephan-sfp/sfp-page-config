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

    $label = ( 1 === $mins ) ? 'minuut' : 'minuten';

    return 'LEESTIJD: <span class="tijd-getal">' . $mins . '</span> ' . $label;
}

/* =========================================================================
 * 2. Shortcode [mijn_leestijd]
 * ====================================================================== */

add_shortcode( 'mijn_leestijd', 'sfp_page_config_shortcode_reading_time' );

/**
 * Shortcode: [mijn_leestijd]
 *
 * Uses a remove/re-add pattern to prevent infinite recursion when
 * the shortcode appears inside the content it is measuring.
 *
 * @return string HTML output.
 */
function sfp_page_config_shortcode_reading_time() {

    global $post;

    if ( empty( $post->post_content ) ) {
        return '';
    }

    // Prevent infinite loop.
    remove_shortcode( 'mijn_leestijd' );

    $result = '<div class="custom-read-meter">'
        . sfp_page_config_calculate_reading_time( $post->post_content )
        . '</div>';

    add_shortcode( 'mijn_leestijd', 'sfp_page_config_shortcode_reading_time' );

    return $result;
}

/* =========================================================================
 * 3. Scroll progress bar (HTML + JS)
 * ====================================================================== */

add_action( 'wp_head', 'sfp_page_config_reading_progress_custom_css', 99 );

/**
 * Emit the Aangepaste CSS from the Instellingen tab so editors can style
 * the reading-time meter and scroll progress bar without a code snippet.
 *
 * Runs sitewide on singular posts/pages. The value is sanitized on save
 * (wp_strip_all_tags) so no </style> can escape the block, but we still
 * print it inside a clearly-labeled <style id> so it is obvious in the
 * source where the rules came from.
 */
function sfp_page_config_reading_progress_custom_css() {

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return;
    }

    $css = sfp_page_config_get_setting( 'custom_css_rp', '' );
    if ( '' === trim( (string) $css ) ) {
        return;
    }

    echo "\n<style id=\"sfp-custom-css-rp\">\n" . $css . "\n</style>\n";
}

add_action( 'wp_enqueue_scripts', 'sfp_page_config_scroll_progress_enqueue' );

/**
 * Enqueue the scroll progress bar stylesheet on singular pages.
 */
function sfp_page_config_scroll_progress_enqueue() {

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return;
    }

    wp_enqueue_style(
        'sfp-reading-time',
        SFP_PAGE_CONFIG_URL . 'assets/reading-time.css',
        array(),
        SFP_PAGE_CONFIG_VERSION
    );

    // Inject the dynamic bar colour as a CSS custom property.
    $brand     = sfp_page_config_get_brand();
    $bar_color = isset( $brand['cta_bg'] ) ? sanitize_hex_color( $brand['cta_bg'] ) : '#d22d00';

    wp_add_inline_style( 'sfp-reading-time', ':root{--sfp-bar-color:' . esc_attr( $bar_color ) . '}' );
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

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
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
