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

    $words_per_minute = 250;

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

add_action( 'wp_footer', 'sfp_page_config_scroll_progress_bar', 25 );

/**
 * Render the scroll progress bar container and its JS driver.
 *
 * Only outputs on singular longread pages.
 */
function sfp_page_config_scroll_progress_bar() {

    if ( ! is_singular( sfp_page_config_post_types() ) ) {
        return;
    }

    if ( ! function_exists( 'sfp_page_config_is_longread' ) || ! sfp_page_config_is_longread() ) {
        return;
    }

    ?>
    <div id="custom-scroll-container"><div id="custom-scroll-bar"></div></div>
    <script>
    window.addEventListener('scroll', function () {
        var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        var height    = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        var scrolled  = (winScroll / height) * 100;
        var bar       = document.getElementById('custom-scroll-bar');
        if (bar) { bar.style.width = scrolled + '%'; }
    });
    </script>
    <?php
}
