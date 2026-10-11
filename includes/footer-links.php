<?php
/**
 * SFP Page Config - Footerlinks als lijst
 *
 * De groepen met links onderaan de site (juridisch, transparantie, service)
 * stonden tot en met 2.12.0 als geplakte HTML in een widget in het vlak
 * footer-widget-4. Vanaf 3.0.0 beheer je ze in SFP Beheer als lijst: per groep
 * een naam en links, elk een pagina van de site of een andere link.
 *
 * Zolang er geen lijst is ingesteld, verandert er niets: de widget blijft
 * werken. Is er een lijst, dan toont de plugin die op de plek van de widget
 * (in hetzelfde footervlak) en blijft de widget ongebruikt staan. De opmaak en
 * het in- en uitklappen komen uit includes/footer-groepen.php en zijn gelijk
 * gebleven.
 *
 * Terugdraaien: de lijst leegmaken of het onderdeel Footerlinks uitzetten.
 *
 * @package SFP_Page_Config
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Schoon de ingestelde groepen op.
 *
 * @param  mixed $ruw Ruwe invoer: ['groepen' => [...]] of direct de lijst.
 * @return array      ['groepen' => [ ['naam' => '', 'links' => [ ... ]] ]]
 */
function sfp_page_config_footer_schoon( $ruw ) {
    $groepen = array();
    $lijst   = is_array( $ruw ) && isset( $ruw['groepen'] ) ? $ruw['groepen'] : $ruw;
    if ( ! is_array( $lijst ) ) {
        return array( 'groepen' => array() );
    }
    foreach ( array_slice( $lijst, 0, 6 ) as $groep ) {
        if ( ! is_array( $groep ) ) {
            continue;
        }
        $naam  = isset( $groep['naam'] ) ? sanitize_text_field( (string) $groep['naam'] ) : '';
        $links = array();
        foreach ( isset( $groep['links'] ) && is_array( $groep['links'] ) ? array_slice( $groep['links'], 0, 20 ) : array() as $link ) {
            if ( ! is_array( $link ) ) {
                continue;
            }
            $label = isset( $link['label'] ) ? sanitize_text_field( (string) $link['label'] ) : '';
            if ( isset( $link['soort'] ) && 'pagina' === $link['soort'] ) {
                $id = isset( $link['id'] ) ? absint( $link['id'] ) : 0;
                if ( $id ) {
                    $links[] = array(
                        'soort' => 'pagina',
                        'id'    => $id,
                        'label' => $label,
                    );
                }
            } else {
                $url = isset( $link['url'] ) ? esc_url_raw( trim( (string) $link['url'] ) ) : '';
                if ( '' !== $url && '' !== $label ) {
                    $links[] = array(
                        'soort' => 'link',
                        'url'   => $url,
                        'label' => $label,
                    );
                }
            }
        }
        if ( '' !== $naam && $links ) {
            $groepen[] = array(
                'naam'  => $naam,
                'links' => $links,
            );
        }
    }
    return array( 'groepen' => $groepen );
}

/**
 * De ingestelde groepen, met per link het adres en de tekst zoals de site ze
 * toont. Een pagina die niet (meer) gepubliceerd is, valt weg.
 *
 * @return array<int, array{naam: string, links: array<int, array{url: string, label: string}>}>
 */
function sfp_page_config_footer_groepen() {
    $alle    = sfp_page_config_instellingen();
    $groepen = isset( $alle['footer']['groepen'] ) && is_array( $alle['footer']['groepen'] ) ? $alle['footer']['groepen'] : array();
    $uit     = array();
    foreach ( $groepen as $groep ) {
        $links = array();
        foreach ( isset( $groep['links'] ) && is_array( $groep['links'] ) ? $groep['links'] : array() as $link ) {
            if ( isset( $link['soort'] ) && 'pagina' === $link['soort'] ) {
                $id = isset( $link['id'] ) ? (int) $link['id'] : 0;
                if ( ! $id || 'publish' !== get_post_status( $id ) ) {
                    continue;
                }
                $links[] = array(
                    'url'   => (string) get_permalink( $id ),
                    'label' => ! empty( $link['label'] ) ? (string) $link['label'] : get_the_title( $id ),
                );
            } elseif ( ! empty( $link['url'] ) && ! empty( $link['label'] ) ) {
                $links[] = array(
                    'url'   => (string) $link['url'],
                    'label' => (string) $link['label'],
                );
            }
        }
        if ( $links && ! empty( $groep['naam'] ) ) {
            $uit[] = array(
                'naam'  => (string) $groep['naam'],
                'links' => $links,
            );
        }
    }
    return $uit;
}

/**
 * De groepen als HTML, in dezelfde opbouw als de widget van voor 3.0.0.
 *
 * @param  array|null $groepen Groepen uit sfp_page_config_footer_groepen(); null om ze te lezen.
 * @return string
 */
function sfp_page_config_footer_html( $groepen = null ) {
    $groepen = null === $groepen ? sfp_page_config_footer_groepen() : $groepen;
    if ( ! $groepen ) {
        return '';
    }
    $namen = array();
    $html  = '';
    foreach ( $groepen as $groep ) {
        $namen[] = $groep['naam'];
        $items   = '';
        foreach ( $groep['links'] as $link ) {
            $items .= '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a></li>';
        }
        $html .= '<details class="sfp-fj__groep" open><summary class="sfp-fj__kop">' . esc_html( $groep['naam'] ) . '</summary><ul class="sfp-fj__links">' . $items . '</ul></details>';
    }
    return '<nav class="sfp-fj" aria-label="' . esc_attr( implode( ', ', $namen ) ) . '">' . $html . '</nav>';
}

/**
 * Toont de plugin de lijst op de plek van de widget?
 *
 * @return bool
 */
function sfp_page_config_footer_lijst_actief() {
    return ! is_admin() && sfp_page_config_onderdeel_aan( 'footer' ) && (bool) sfp_page_config_footer_groepen();
}

add_action( 'dynamic_sidebar_before', 'sfp_page_config_footer_voor', 10, 1 );
add_action( 'dynamic_sidebar_after', 'sfp_page_config_footer_na', 10, 1 );

/**
 * Vang op wat het footervlak toont, zodat de lijst op de plek van de oude
 * links kan komen.
 *
 * @param string|int $index Het vlak.
 */
function sfp_page_config_footer_voor( $index ) {
    if ( 'footer-widget-4' !== $index || ! sfp_page_config_footer_lijst_actief() ) {
        return;
    }
    ob_start();
    $GLOBALS['sfp_page_config_footer_buffer'] = ob_get_level();
}

/**
 * Zet de lijst op de plek van de oude links. Andere widgets in hetzelfde
 * vlak blijven staan. Staan de oude links er niet (meer), dan komt de lijst
 * vooraan in het vlak.
 *
 * @param string|int $index Het vlak.
 */
function sfp_page_config_footer_na( $index ) {
    if ( 'footer-widget-4' !== $index || empty( $GLOBALS['sfp_page_config_footer_buffer'] ) ) {
        return;
    }
    $niveau = (int) $GLOBALS['sfp_page_config_footer_buffer'];
    $GLOBALS['sfp_page_config_footer_buffer'] = 0;

    // Heeft een widget zelf een buffer open laten staan, dan sluiten we die
    // eerst, zodat onze eigen buffer weer bovenop ligt.
    $inhoud = '';
    while ( ob_get_level() > $niveau ) {
        $inhoud = ob_get_clean() . $inhoud;
    }
    if ( ob_get_level() !== $niveau ) {
        echo $inhoud; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- uitvoer van de widgets zelf.
        return;
    }
    $inhoud = ob_get_clean() . $inhoud;

    echo sfp_page_config_footer_vervang( (string) $inhoud, sfp_page_config_footer_html() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- uitvoer van de widgets plus de lijst, opgebouwd met esc_url en esc_html.
}

/**
 * Vervang de oude links in de uitvoer van het footervlak door de lijst.
 *
 * @param  string $inhoud Uitvoer van de widgets in het vlak.
 * @param  string $lijst  De lijst als HTML.
 * @return string
 */
function sfp_page_config_footer_vervang( $inhoud, $lijst ) {
    $aantal = 0;
    $nieuw  = preg_replace_callback(
        '#<nav\b[^>]*class="[^"]*\bsfp-fj\b[^"]*"[^>]*>.*?</nav>#is',
        function () use ( $lijst ) {
            return $lijst;
        },
        $inhoud,
        1,
        $aantal
    );
    if ( null !== $nieuw && $aantal > 0 ) {
        return $nieuw;
    }
    return '<section class="widget widget_block sfp-footerlinks">' . $lijst . '</section>' . $inhoud;
}

/**
 * Lees de groepen uit de HTML die nu in de widget staat, om ze over te nemen
 * in de lijst. Een link naar een pagina van de site wordt een pagina; al het
 * andere blijft een losse link.
 *
 * @return array|null ['groepen' => [...]] of null als er geen widget met de opbouw is.
 */
function sfp_page_config_footer_uit_widget() {
    $blokken = get_option( 'widget_block', array() );
    $vlakken = wp_get_sidebars_widgets();
    $in_vlak = isset( $vlakken['footer-widget-4'] ) && is_array( $vlakken['footer-widget-4'] ) ? $vlakken['footer-widget-4'] : array();
    $html    = '';
    foreach ( $in_vlak as $widget_id ) {
        if ( preg_match( '/^block-(\d+)$/', (string) $widget_id, $m ) && isset( $blokken[ (int) $m[1] ]['content'] ) ) {
            $inhoud = (string) $blokken[ (int) $m[1] ]['content'];
            if ( false !== strpos( $inhoud, 'sfp-fj__groep' ) ) {
                $html = $inhoud;
                break;
            }
        }
    }
    if ( '' === $html ) {
        return null;
    }
    if ( ! preg_match_all( '#<summary[^>]*class="[^"]*sfp-fj__kop[^"]*"[^>]*>(.*?)</summary>\s*<ul[^>]*>(.*?)</ul>#is', $html, $groepen, PREG_SET_ORDER ) ) {
        return null;
    }
    $uit = array();
    foreach ( $groepen as $groep ) {
        $links = array();
        if ( preg_match_all( '#<a[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)</a>#is', $groep[2], $treffers, PREG_SET_ORDER ) ) {
            foreach ( $treffers as $t ) {
                $url   = html_entity_decode( $t[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                $label = trim( wp_strip_all_tags( html_entity_decode( $t[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
                $id    = 0 === strpos( $url, home_url() ) || 0 === strpos( $url, '/' ) ? url_to_postid( $url ) : 0;
                if ( $id && 'page' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) {
                    $links[] = array(
                        'soort' => 'pagina',
                        'id'    => $id,
                        // Alleen een eigen tekst bewaren als die afwijkt van de paginatitel.
                        'label' => html_entity_decode( get_the_title( $id ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) === $label ? '' : $label,
                    );
                } else {
                    $links[] = array(
                        'soort' => 'link',
                        'url'   => $url,
                        'label' => $label,
                    );
                }
            }
        }
        $uit[] = array(
            'naam'  => trim( wp_strip_all_tags( html_entity_decode( $groep[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ),
            'links' => $links,
        );
    }
    return sfp_page_config_footer_schoon( array( 'groepen' => $uit ) );
}
