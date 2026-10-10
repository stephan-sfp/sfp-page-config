<?php
/**
 * SFP Page Config - Artikelopmaak
 *
 * De automatische onderdelen van een artikel, volgens de goedgekeurde
 * dummy "SwS artikelopmaak" (v35) en de besluiten van 9 en 10 oktober 2026.
 * De schrijver schrijft alleen de tekst en plaatst blokken; de plugin zet
 * de rest neer:
 *
 *   - indeling met tekstkolom en meelopende rechterkolom
 *   - pijlerregel boven de titel (uit de categorie)
 *   - kopkaart: auteur, rol, data, leestijd, Fact-checked
 *   - acties: delen, voorkeursbron bij Google, warming-up, bronnen
 *   - affiliatemelding (alleen bij affiliatelinks; includes/affiliate.php)
 *   - inhoudsopgave rechts, hoofdstukbalk onderaan op telefoon en tablet
 *   - promokaart (rechts; op telefoon en tablet na de eerste alinea van
 *     het eerste hoofdstuk)
 *   - vervolgblok via de actie sfp_artikel_vervolg (Astra-layout per pijler)
 *   - auteurskaart en voetregels
 *
 * Aan per site met de instelling "Artikelopmaak" (standaard uit). Uit
 * betekent: de site toont berichten zoals voorheen.
 *
 * Kleuren via --sfp-b-* (rolvariabelen met Astra als terugval), fonts via
 * Astra en de kopfont-variabelen. Geen merkwaarden in code.
 *
 * Terugdraaien: de instelling uitzetten, of dit bestand uit de lijst in
 * sfp-page-config.php halen.
 *
 * @package SFP_Page_Config
 * @since   2.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
 * Aan of uit
 * ====================================================================== */

/**
 * Staat de artikelopmaak aan op deze site?
 *
 * @return bool
 */
function sfp_page_config_artikel_aan() {
    return '1' === (string) sfp_page_config_get_setting( 'artikel_aan', '' );
}

/**
 * Berichttypes die de artikelopmaak krijgen.
 *
 * @return string[]
 */
function sfp_page_config_artikel_posttypes() {
    return (array) apply_filters( 'sfp_page_config_artikel_posttypes', array( 'post' ) );
}

/**
 * Toont deze aanvraag een artikel in de artikelopmaak?
 *
 * @return bool
 */
function sfp_page_config_artikel_actief() {
    if ( is_admin() || ! sfp_page_config_artikel_aan() ) {
        return false;
    }
    return is_singular( sfp_page_config_artikel_posttypes() ) && ! is_feed() && ! is_embed();
}

/* =========================================================================
 * Sjabloon en Astra
 * ====================================================================== */

add_filter( 'template_include', 'sfp_page_config_artikel_sjabloon', 999 );

/**
 * Gebruik het artikelsjabloon van de plugin. Header en footer blijven die
 * van het thema.
 *
 * @param  string $sjabloon Pad van het gekozen sjabloon.
 * @return string
 */
function sfp_page_config_artikel_sjabloon( $sjabloon ) {
    if ( sfp_page_config_artikel_actief() ) {
        return SFP_PAGE_CONFIG_DIR . 'includes/artikel-sjabloon.php';
    }
    return $sjabloon;
}

add_filter( 'astra_page_layout', 'sfp_page_config_artikel_astra_zijbalk', 99 );
add_filter( 'astra_get_content_layout', 'sfp_page_config_artikel_astra_indeling', 99 );
add_filter( 'astra_is_transparent_header', 'sfp_page_config_artikel_astra_header', 99 );

/**
 * Geen Astra-zijbalk: de rechterkolom is van de artikelopmaak.
 *
 * @param  string $layout Indeling.
 * @return string
 */
function sfp_page_config_artikel_astra_zijbalk( $layout ) {
    return sfp_page_config_artikel_actief() ? 'no-sidebar' : $layout;
}

/**
 * Volle breedte zonder Astra-kader: de artikelopmaak bepaalt zelf de
 * breedte.
 *
 * @param  string $layout Indeling.
 * @return string
 */
function sfp_page_config_artikel_astra_indeling( $layout ) {
    return sfp_page_config_artikel_actief() ? 'page-builder' : $layout;
}

/**
 * Geen doorzichtige header boven een artikel: de titel staat op wit.
 *
 * @param  bool $aan Doorzichtige header aan.
 * @return bool
 */
function sfp_page_config_artikel_astra_header( $aan ) {
    return sfp_page_config_artikel_actief() ? false : $aan;
}

add_filter( 'body_class', 'sfp_page_config_artikel_body_class' );

/**
 * Body-klasse voor de artikelopmaak.
 *
 * @param  string[] $klassen Klassen.
 * @return string[]
 */
function sfp_page_config_artikel_body_class( $klassen ) {
    if ( sfp_page_config_artikel_actief() ) {
        $klassen[] = 'sfp-artikel-pagina';
    }
    return $klassen;
}

/* =========================================================================
 * CSS en script
 * ====================================================================== */

add_action( 'wp_head', 'sfp_page_config_artikel_viewport', 3 );

/**
 * viewport-fit=cover, zodat de hoofdstukbalk op een iPhone tot de rand van
 * het scherm loopt (zelfde aanpak als de longread-navigatie). Zet ook een
 * klasse op html, voor browsers zonder :has() (zie assets/artikel.css).
 */
function sfp_page_config_artikel_viewport() {
    if ( ! sfp_page_config_artikel_actief() ) {
        return;
    }
    echo '<script id="sfp-artikel-viewport">(function(){document.documentElement.classList.add("sfp-artikel-html");var m=document.querySelector("meta[name=viewport]");if(m&&m.content.indexOf("viewport-fit")===-1){m.content+=", viewport-fit=cover";}})();</script>' . "\n";
}

add_action( 'wp_head', 'sfp_page_config_artikel_head_css', 19 );

/**
 * De CSS van de artikelopmaak, inline in de head. Staat voor de CSS van
 * de blokken (prioriteit 20) en zet de gedeelde variabelen.
 */
function sfp_page_config_artikel_head_css() {
    if ( ! sfp_page_config_artikel_actief() ) {
        return;
    }
    $brand = sfp_page_config_get_brand();
    $css   = sfp_page_config_blokken_css_variabelen()
        . ':root{--sfp-art-balk:' . $brand['lr_bar_bg'] . ';--sfp-art-balk-tekst:' . $brand['lr_bar_text'] . '}'
        . sfp_page_config_artikel_css_bestand();
    sfp_page_config_blokken_geplaatst( 'variabelen' );
    echo '<style id="sfp-artikel">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste CSS uit de plugin plus gevalideerde kleuren.
}

/**
 * Lees assets/artikel.css, zonder commentaar en overtollige witruimte.
 *
 * @return string
 */
function sfp_page_config_artikel_css_bestand() {
    $pad = SFP_PAGE_CONFIG_DIR . 'assets/artikel.css';
    $css = is_readable( $pad ) ? (string) file_get_contents( $pad ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokaal pluginbestand.
    $css = preg_replace( '#/\*.*?\*/#s', '', $css );
    $css = preg_replace( '/\s+/', ' ', $css );
    return trim( str_replace( array( ' {', '{ ', ' }', '; ', ': ', ', ' ), array( '{', '{', '}', ';', ':', ',' ), $css ) );
}

add_filter( 'rocket_rucss_inline_atts_exclusions', 'sfp_page_config_artikel_rucss' );

/**
 * WP Rocket Remove Unused CSS laat de CSS van de artikelopmaak staan:
 * panelen en de hoofdstukbalk zijn bij het meten niet in beeld.
 *
 * @param  array $uitsluitingen Uitsluitingen op attribuut.
 * @return array
 */
function sfp_page_config_artikel_rucss( $uitsluitingen ) {
    $uitsluitingen   = (array) $uitsluitingen;
    $uitsluitingen[] = 'sfp-artikel';
    return $uitsluitingen;
}

/**
 * Markeer dat het artikelscript nodig is (ook voor de samenvatting buiten
 * de artikelopmaak).
 *
 * @param  bool $zet True om te markeren.
 * @return bool
 */
function sfp_page_config_artikel_js_nodig( $zet = false ) {
    static $nodig = false;
    if ( $zet ) {
        $nodig = true;
    }
    return $nodig;
}

add_action( 'wp_footer', 'sfp_page_config_artikel_js', 20 );

/**
 * Het script van de artikelopmaak: panelen, link kopiëren, warming-up,
 * inhoudsopgave en hoofdstukbalk. Aanvullend: zonder script blijft het
 * artikel volledig leesbaar.
 */
function sfp_page_config_artikel_js() {
    if ( ! sfp_page_config_artikel_js_nodig() && ! sfp_page_config_artikel_actief() ) {
        return;
    }
    $pad = SFP_PAGE_CONFIG_DIR . 'assets/artikel.js';
    $js  = is_readable( $pad ) ? (string) file_get_contents( $pad ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokaal pluginbestand.
    if ( '' === $js ) {
        return;
    }
    echo '<script id="sfp-artikel-js" nowprocket>' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vaste JS uit de plugin.
}

add_filter( 'rocket_delay_js_exclusions', 'sfp_page_config_artikel_delay' );

/**
 * WP Rocket stelt het script niet uit; anders loopt de inhoudsopgave pas
 * mee na de eerste aanraking.
 *
 * @param  array $uitsluitingen Uitsluitingen.
 * @return array
 */
function sfp_page_config_artikel_delay( $uitsluitingen ) {
    $uitsluitingen   = (array) $uitsluitingen;
    $uitsluitingen[] = 'sfp-artikel-js';
    $uitsluitingen[] = 'sfp-artikel-viewport';
    return $uitsluitingen;
}

/* =========================================================================
 * Rol van de auteur (gebruikersprofiel)
 * ====================================================================== */

add_action( 'init', 'sfp_page_config_artikel_registreer_rol' );

/**
 * Het veld "Rol in de byline" bij een gebruiker, ook via REST.
 */
function sfp_page_config_artikel_registreer_rol() {
    register_meta(
        'user',
        'sfp_rol',
        array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function ( $toegestaan, $sleutel, $user_id ) {
                return current_user_can( 'edit_user', $user_id );
            },
        )
    );
}

add_action( 'show_user_profile', 'sfp_page_config_artikel_rol_veld' );
add_action( 'edit_user_profile', 'sfp_page_config_artikel_rol_veld' );

/**
 * Toon het veld op het profielscherm.
 *
 * @param WP_User $user Gebruiker.
 */
function sfp_page_config_artikel_rol_veld( $user ) {
    ?>
    <h2>Artikelen (SFP Page Config)</h2>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="sfp_rol">Rol in de byline</label></th>
            <td>
                <input type="text" name="sfp_rol" id="sfp_rol" value="<?php echo esc_attr( get_user_meta( $user->ID, 'sfp_rol', true ) ); ?>" class="regular-text" />
                <?php wp_nonce_field( 'sfp_rol_' . $user->ID, 'sfp_rol_nonce' ); ?>
                <p class="description">Staat achter de naam boven elk artikel, bijvoorbeeld: public speaking expert. Leeg: alleen de naam.</p>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'personal_options_update', 'sfp_page_config_artikel_rol_opslaan' );
add_action( 'edit_user_profile_update', 'sfp_page_config_artikel_rol_opslaan' );

/**
 * Sla het veld op.
 *
 * @param int $user_id Gebruiker.
 */
function sfp_page_config_artikel_rol_opslaan( $user_id ) {
    if ( ! isset( $_POST['sfp_rol'], $_POST['sfp_rol_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sfp_rol_nonce'] ), 'sfp_rol_' . $user_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return;
    }
    update_user_meta( $user_id, 'sfp_rol', sanitize_text_field( wp_unslash( $_POST['sfp_rol'] ) ) );
}

/* =========================================================================
 * Gegevens
 * ====================================================================== */

/**
 * Leestijd in hele minuten.
 *
 * @param  string $content Ruwe inhoud van het bericht.
 * @return int
 */
function sfp_page_config_artikel_leestijd( $content ) {
    $per_minuut = (int) sfp_page_config_get_setting( 'words_per_minute', 250 );
    if ( $per_minuut < 100 ) {
        $per_minuut = 250;
    }
    // Alleen lopende tekst telt: geen warming-up, blokcommentaar of scripts.
    $content = preg_replace( '#<!-- wp:sfp/warming-up.*?<!-- /wp:sfp/warming-up -->#s', '', (string) $content );
    $content = preg_replace( '#<(script|style)\b.*?</\1>#is', '', $content );
    $tekst   = wp_strip_all_tags( strip_shortcodes( $content ) );
    $woorden = count( preg_split( '/\s+/u', $tekst, -1, PREG_SPLIT_NO_EMPTY ) );
    return max( 1, (int) ceil( $woorden / $per_minuut ) );
}

/**
 * De bronnen van een artikel, uit de tooltips van SFP Tooltip. Een
 * tooltip telt als bron als hij een regel "Bron:", "Source:" of "Fonte:"
 * bevat. Dubbele bronnen komen één keer terug.
 *
 * @param  string $content Ruwe inhoud van het bericht.
 * @return string[]        Bronnen als veilige HTML (links en nadruk).
 */
function sfp_page_config_artikel_bronnen( $content ) {
    if ( false === strpos( (string) $content, 'data-tooltip' ) ) {
        return array();
    }
    if ( ! preg_match_all( '/data-tooltip=([\x27\x22])(.*?)\1/s', $content, $treffers ) ) {
        return array();
    }
    $toegestaan = array(
        'a'      => array( 'href' => true ),
        'em'     => array(),
        'i'      => array(),
        'strong' => array(),
        'b'      => array(),
    );
    $bronnen = array();
    foreach ( $treffers[2] as $tooltip ) {
        $tooltip = html_entity_decode( $tooltip, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        if ( ! preg_match( '/(?:^|>|\s)(?:Bron|Source|Fonte):\s*(.+?)(?:<br[\s\/]*>|\n|$)/isu', $tooltip, $m ) ) {
            continue;
        }
        $bron = trim( wp_kses( $m[1], $toegestaan ) );
        if ( strlen( wp_strip_all_tags( $bron ) ) < 5 ) {
            continue;
        }
        $sleutel = strtolower( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $bron ) ) );
        if ( ! isset( $bronnen[ $sleutel ] ) ) {
            $bronnen[ $sleutel ] = preg_replace( '/<a /', '<a target="_blank" rel="noopener" ', $bron );
        }
    }
    return array_values( $bronnen );
}

/**
 * De koppen die een hoofdstuk zijn: de H2's die los in de tekst staan. Een
 * H2 in een kader, uitklapregel of ander blok is geen hoofdstuk.
 *
 * @param  string $content Ruwe inhoud van het bericht.
 * @return string[]|null   Genormaliseerde titels in volgorde, of null als de
 *                         inhoud geen blokken heeft (dan telt elke H2).
 */
function sfp_page_config_artikel_hoofdstuktitels( $content ) {
    if ( ! has_blocks( $content ) ) {
        return null;
    }
    $titels = array();
    foreach ( parse_blocks( $content ) as $blok ) {
        if ( 'core/heading' !== $blok['blockName'] ) {
            continue;
        }
        $niveau = isset( $blok['attrs']['level'] ) ? (int) $blok['attrs']['level'] : 2;
        if ( 2 === $niveau ) {
            $titels[] = sanitize_title( wp_strip_all_tags( $blok['innerHTML'] ) );
        }
    }
    return $titels;
}

/**
 * Geef elk hoofdstuk (H2) in de inhoud een anker en lees de hoofdstukken uit.
 *
 * @param  string        $html   Getoonde inhoud.
 * @param  string[]|null $alleen Titels uit sfp_page_config_artikel_hoofdstuktitels(), of null voor elke H2.
 * @return array{0: string, 1: array<int, array{id: string, titel: string}>}
 */
function sfp_page_config_artikel_hoofdstukken( $html, $alleen = null ) {
    $hoofdstukken = array();
    $gebruikt     = array();
    $wijzer       = 0;
    $html         = preg_replace_callback(
        '#<h2\b([^>]*)>(.*?)</h2>#is',
        function ( $m ) use ( &$hoofdstukken, &$gebruikt, &$wijzer, $alleen ) {
            $titel = trim( wp_strip_all_tags( $m[2] ) );
            if ( '' === $titel ) {
                return $m[0];
            }
            if ( null !== $alleen ) {
                // Alleen de eerstvolgende verwachte kop telt; zo blijft een H2 in een blok buiten de inhoudsopgave.
                if ( ! isset( $alleen[ $wijzer ] ) || sanitize_title( $titel ) !== $alleen[ $wijzer ] ) {
                    return $m[0];
                }
                ++$wijzer;
            }
            $attrs = $m[1];
            if ( preg_match( '/\bid=(["\'])(.*?)\1/i', $attrs, $id ) ) {
                $anker = $id[2];
            } else {
                $basis = sanitize_title( $titel );
                $basis = '' !== $basis ? $basis : 'hoofdstuk';
                $anker = $basis;
                $nr    = 2;
                while ( isset( $gebruikt[ $anker ] ) ) {
                    $anker = $basis . '-' . $nr++;
                }
                $attrs .= ' id="' . esc_attr( $anker ) . '"';
            }
            $gebruikt[ $anker ] = true;
            $hoofdstukken[]     = array( 'id' => $anker, 'titel' => html_entity_decode( $titel, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
            return '<h2' . $attrs . '>' . $m[2] . '</h2>';
        },
        (string) $html
    );
    return array( $html, $hoofdstukken );
}

/**
 * De promokaart van de site (bijvoorbeeld het boek), uit de instellingen.
 *
 * @param  string $variant zij (rechterkolom) of inline (in de tekst).
 * @return string          HTML, of '' als er geen kaart is ingesteld.
 */
function sfp_page_config_artikel_promokaart( $variant = 'zij' ) {
    $titel = (string) sfp_page_config_get_setting( 'artikel_promo_titel', '' );
    $url   = (string) sfp_page_config_get_setting( 'artikel_promo_url', '' );
    if ( '' === $titel || '' === $url ) {
        return '';
    }
    $label     = (string) sfp_page_config_get_setting( 'artikel_promo_label', '' );
    $tekst     = (string) sfp_page_config_get_setting( 'artikel_promo_tekst', '' );
    $linktekst = (string) sfp_page_config_get_setting( 'artikel_promo_linktekst', '' );
    $beeld_id  = (int) sfp_page_config_get_setting( 'artikel_promo_beeld', 0 );
    $beeld     = $beeld_id ? wp_get_attachment_image( $beeld_id, 'medium', false, array( 'loading' => 'lazy', 'sizes' => '72px' ) ) : '';

    return '<aside class="sfp-art-promo sfp-art-promo--' . ( 'inline' === $variant ? 'inline' : 'zij' ) . ( '' === $beeld ? ' sfp-art-promo--zonder-beeld' : '' ) . '"' . ( '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : '' ) . '>'
        . $beeld . '<div>'
        . ( '' !== $label ? '<span class="sfp-blok-label">' . esc_html( $label ) . '</span>' : '' )
        . '<b>' . esc_html( $titel ) . '</b>'
        . ( '' !== $tekst ? '<p>' . esc_html( $tekst ) . '</p>' : '' )
        . '<a class="sfp-pijl" href="' . esc_url( $url ) . '">' . esc_html( '' !== $linktekst ? $linktekst : $titel ) . '</a>'
        . '</div></aside>';
}

/**
 * Zet de promokaart in de tekst: na de eerste alinea van het eerste
 * hoofdstuk. Alleen zichtbaar op telefoon en tablet, waar de rechterkolom
 * ontbreekt.
 *
 * @param  string $html  Getoonde inhoud.
 * @param  string $kaart HTML van de kaart.
 * @return string
 */
function sfp_page_config_artikel_promo_in_tekst( $html, $kaart ) {
    if ( '' === $kaart ) {
        return $html;
    }
    if ( preg_match( '#</h2>\s*<p\b[^>]*>.*?</p>#is', $html, $m, PREG_OFFSET_CAPTURE ) ) {
        $pos = $m[0][1] + strlen( $m[0][0] );
        return substr( $html, 0, $pos ) . $kaart . substr( $html, $pos );
    }
    return $html;
}

/**
 * De pijlerregel: "Part of Presence in Persuasive speaking". Kiest de
 * diepste categorie van het bericht en haar ouder.
 *
 * @param  WP_Post $post Bericht.
 * @return string        HTML, of '' zonder bruikbare categorie.
 */
function sfp_page_config_artikel_pijlerregel( $post ) {
    $termen = get_the_terms( $post, 'category' );
    if ( ! is_array( $termen ) ) {
        return '';
    }
    $standaard = (int) get_option( 'default_category' );
    $keuze     = null;
    $diepte    = -1;
    foreach ( $termen as $term ) {
        if ( (int) $term->term_id === $standaard && count( $termen ) > 1 ) {
            continue;
        }
        $d = count( get_ancestors( $term->term_id, 'category' ) );
        if ( $d > $diepte ) {
            $diepte = $d;
            $keuze  = $term;
        }
    }
    if ( ! $keuze || ( (int) $keuze->term_id === $standaard && 'uncategorized' === $keuze->slug ) ) {
        return '';
    }
    $link = function ( $term ) {
        $url = get_term_link( $term );
        return is_wp_error( $url ) ? esc_html( $term->name ) : '<a href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a>';
    };
    $ouder = $keuze->parent ? get_term( $keuze->parent, 'category' ) : null;
    if ( $ouder && ! is_wp_error( $ouder ) ) {
        $tekst = sprintf( esc_html( sfp_page_config_artikel_tekst( 'onderdeel_in' ) ), $link( $keuze ), $link( $ouder ) );
    } else {
        $tekst = sprintf( esc_html( sfp_page_config_artikel_tekst( 'onderdeel' ) ), $link( $keuze ) );
    }
    return '<p class="sfp-art-pijler">' . $tekst . '</p>';
}

/**
 * Link naar de auteur: de website uit het profiel, anders de auteurspagina.
 *
 * @param  int $auteur Gebruikers-ID.
 * @return string
 */
function sfp_page_config_artikel_auteur_url( $auteur ) {
    $url = (string) get_the_author_meta( 'user_url', $auteur );
    return '' !== $url ? $url : get_author_posts_url( $auteur );
}

/* =========================================================================
 * Onderdelen
 * ====================================================================== */

/**
 * De kopkaart.
 *
 * @param  WP_Post $post         Bericht.
 * @param  array   $hoofdstukken Hoofdstukken (voor de warming-up).
 * @return string
 */
function sfp_page_config_artikel_kopkaart( $post, array $hoofdstukken ) {
    $t      = 'sfp_page_config_artikel_tekst';
    $auteur = (int) $post->post_author;
    $naam   = get_the_author_meta( 'display_name', $auteur );
    $rol    = trim( (string) get_user_meta( $auteur, 'sfp_rol', true ) );
    $url    = get_permalink( $post );
    $titel  = get_the_title( $post );

    // Wie en wanneer.
    $foto = '';
    $wie  = '';
    if ( $auteur && '' !== (string) $naam ) {
        $foto = get_avatar( $auteur, 44, '', $naam, array( 'class' => 'sfp-art-kop__foto', 'loading' => 'eager' ) );
        $wie  = '<div class="sfp-art-kop__naam">' . sprintf( esc_html( $t( 'door' ) ), '<a href="' . esc_url( sfp_page_config_artikel_auteur_url( $auteur ) ) . '">' . esc_html( $naam ) . '</a>' )
            . ( '' !== $rol ? '<span class="sfp-art-kop__rol">' . esc_html( $rol ) . '</span>' : '' ) . '</div>';
    }

    // get_post_timestamp() werkt ook bij een concept, dat nog geen GMT-datum heeft.
    $gepubliceerd = (int) get_post_timestamp( $post, 'date' );
    $bijgewerkt   = (int) get_post_timestamp( $post, 'modified' );
    if ( ! $gepubliceerd ) {
        $gepubliceerd = time();
    }
    if ( ! $bijgewerkt ) {
        $bijgewerkt = $gepubliceerd;
    }
    $meta         = '<span><time datetime="' . esc_attr( wp_date( 'c', $gepubliceerd ) ) . '">' . esc_html( sprintf( $t( 'gepubliceerd' ), wp_date( 'j M Y', $gepubliceerd ) ) ) . '</time></span>';
    if ( wp_date( 'Ymd', $bijgewerkt ) > wp_date( 'Ymd', $gepubliceerd ) ) {
        $meta .= '<span><time datetime="' . esc_attr( wp_date( 'c', $bijgewerkt ) ) . '">' . esc_html( sprintf( $t( 'bijgewerkt' ), wp_date( 'j M Y', $bijgewerkt ) ) ) . '</time></span>';
    }
    $meta .= '<i class="sfp-art-kop__breek" aria-hidden="true"></i>';
    $meta .= '<span>' . esc_html( sprintf( $t( 'leestijd' ), sfp_page_config_artikel_leestijd( $post->post_content ) ) ) . '</span>';

    $check_url = (string) sfp_page_config_get_setting( 'artikel_factcheck_url', '' );
    if ( '' === $check_url ) {
        $check_url = (string) sfp_page_config_get_setting( 'artikel_redactie_url', '' );
    }
    $check = sfp_page_config_artikel_icoon( 'vink' ) . esc_html( $t( 'gecheckt' ) );
    $meta .= '' !== $check_url ? '<a class="sfp-art-kop__check" href="' . esc_url( $check_url ) . '">' . $check . '</a>' : '<span class="sfp-art-kop__check">' . $check . '</span>';

    $affiliate = function_exists( 'sfp_page_config_affiliate_regel' ) ? sfp_page_config_affiliate_regel( $post ) : '';

    // Acties en panelen.
    $acties  = '';
    $panelen = '';
    $knop    = function ( $id, $icoon, $lang, $kort = '' ) {
        $label = '' === $kort ? '<span>' . $lang . '</span>' : '<span class="sfp-lang">' . $lang . '</span><span class="sfp-kort">' . $kort . '</span>';
        return '<button type="button" class="sfp-art-actie" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '" data-sfp-paneel data-sfp-groep="kop">' . sfp_page_config_artikel_icoon( $icoon ) . $label . '</button>';
    };

    // Delen.
    $acties .= $knop( 'sfp-art-paneel-delen', 'delen', esc_html( $t( 'delen' ) ) );
    $kanalen = '';
    foreach ( sfp_page_config_artikel_deelkanalen( $url, $titel ) as $kanaal ) {
        $kanalen .= '<a href="' . esc_url( $kanaal[2] ) . '" target="_blank" rel="noopener">' . sfp_page_config_artikel_icoon( $kanaal[0] ) . '<span>' . esc_html( $kanaal[1] ) . '</span></a>';
    }
    $kanalen .= '<a href="' . esc_url( 'mailto:?subject=' . rawurlencode( $titel ) . '&body=' . rawurlencode( $url ) ) . '">' . sfp_page_config_artikel_icoon( 'mail' ) . '<span>' . esc_html( $t( 'email' ) ) . '</span></a>';
    $kanalen .= '<button type="button" data-sfp-kopieer="' . esc_url( $url ) . '" data-sfp-klaar="' . esc_attr( $t( 'link_gekopieerd' ) ) . '">' . sfp_page_config_artikel_icoon( 'link' ) . '<span>' . esc_html( $t( 'link_kopieren' ) ) . '</span></button>';
    $panelen .= '<div class="sfp-art-paneel" id="sfp-art-paneel-delen" hidden><div class="sfp-art-deel">' . $kanalen . '</div></div>';

    // Voorkeursbron bij Google.
    if ( '1' === (string) sfp_page_config_get_setting( 'artikel_voorkeursbron', '' ) ) {
        $host    = preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
        $acties .= '<a class="sfp-art-actie" href="' . esc_url( 'https://www.google.com/preferences/source?q=' . rawurlencode( $host ) ) . '" target="_blank" rel="noopener">' . sfp_page_config_artikel_icoon( 'ster' ) . '<span class="sfp-lang">' . esc_html( $t( 'favoriet' ) ) . '</span><span class="sfp-kort">' . esc_html( $t( 'favoriet_kort' ) ) . '</span></a>';
    }

    // Warming-up.
    $vragen = function_exists( 'sfp_page_config_warming_up_verzamel' ) ? sfp_page_config_warming_up_verzamel() : array();
    if ( $vragen ) {
        $titels = array();
        foreach ( $hoofdstukken as $h ) {
            $titels[ $h['id'] ] = $h['titel'];
        }
        $aantal  = count( $vragen );
        $acties .= $knop( 'sfp-art-paneel-warm', 'vraag', esc_html( sprintf( $t( 'warm' ), $aantal ) ), esc_html( $t( 'warm_kort' ) ) );
        $stip    = '';
        $blokken = '';
        foreach ( $vragen as $i => $vraag ) {
            $stip  .= '<i' . ( 0 === $i ? ' class="aan"' : '' ) . '></i>';
            $opties = '';
            foreach ( $vraag['opties'] as $optie ) {
                $opties .= '<button type="button" aria-pressed="false">' . esc_html( $optie ) . '</button>';
            }
            $hint = '';
            if ( '' !== $vraag['anker'] && isset( $titels[ $vraag['anker'] ] ) ) {
                $hint = '<p class="sfp-art-warm__hint" hidden>' . sprintf( esc_html( $t( 'antwoord_in' ) ), '<a href="#' . esc_attr( $vraag['anker'] ) . '">' . esc_html( $titels[ $vraag['anker'] ] ) . '</a>' ) . '</p>';
            }
            $blokken .= '<div class="sfp-art-warm__vraag"' . ( 0 === $i ? '' : ' hidden' ) . '><b>' . esc_html( $vraag['vraag'] ) . '</b><div class="sfp-art-warm__opties">' . $opties . '</div>' . $hint . '</div>';
        }
        $eerste   = $hoofdstukken ? $hoofdstukken[0]['id'] : '';
        $panelen .= '<div class="sfp-art-paneel" id="sfp-art-paneel-warm" hidden><div class="sfp-art-warm" data-sfp-volgende="' . esc_attr( $t( 'volgende_vraag' ) ) . '" data-sfp-start="' . esc_attr( $t( 'start_lezen' ) ) . '" data-sfp-begin="' . esc_attr( $eerste ) . '">'
            . '<div class="sfp-art-warm__kop"><span>' . sprintf( esc_html( $t( 'vraag_van' ) ), '<b class="sfp-art-warm__nr">1</b>', $aantal ) . '</span><span class="sfp-art-warm__stip" aria-hidden="true">' . $stip . '</span></div>'
            . $blokken . '<div class="sfp-art-warm__voet"><button type="button" class="sfp-art-warm__volgende" hidden></button></div></div></div>';
    }

    // Bronnen.
    $bronnen = sfp_page_config_artikel_bronnen( $post->post_content );
    if ( $bronnen ) {
        $acties  .= $knop( 'sfp-art-paneel-bronnen', 'bron', esc_html( sprintf( $t( 'bronnen' ), count( $bronnen ) ) ) );
        $panelen .= '<div class="sfp-art-paneel" id="sfp-art-paneel-bronnen" hidden><ol class="sfp-art-bronnen"><li>' . implode( '</li><li>', $bronnen ) . '</li></ol></div>';
    }

    return '<div class="sfp-art-kop"><div class="sfp-art-kop__wie' . ( '' === $foto ? ' sfp-art-kop__wie--zonder-foto' : '' ) . '">' . $foto . $wie . '<div class="sfp-art-kop__meta">' . $meta . '</div>' . $affiliate . '</div>'
        . '<div class="sfp-art-acties">' . $acties . '</div>' . $panelen . '</div>';
}

/**
 * De rechterkolom: inhoudsopgave en promokaart.
 *
 * @param  WP_Post $post         Bericht.
 * @param  array   $hoofdstukken Hoofdstukken.
 * @return string                HTML, of '' als er niets te tonen is.
 */
function sfp_page_config_artikel_zijkolom( $post, array $hoofdstukken ) {
    $t   = 'sfp_page_config_artikel_tekst';
    $toc = '';
    if ( count( $hoofdstukken ) >= 2 ) {
        $items = '';
        foreach ( $hoofdstukken as $h ) {
            $items .= '<li><a href="#' . esc_attr( $h['id'] ) . '">' . esc_html( $h['titel'] ) . '</a></li>';
        }
        $toc = '<nav class="sfp-art-toc" aria-label="' . esc_attr( $t( 'inhoud' ) ) . '"><div class="sfp-blok-label sfp-art-toc__kop">' . esc_html( $t( 'inhoud' ) ) . ' <span>' . esc_html( sprintf( $t( 'minuten' ), sfp_page_config_artikel_leestijd( $post->post_content ) ) ) . '</span></div><ol>' . $items . '</ol></nav>';
    }
    $promo = sfp_page_config_artikel_promokaart( 'zij' );
    if ( '' === $toc && '' === $promo ) {
        return '';
    }
    return '<aside class="sfp-artikel__zij" aria-label="' . esc_attr( $t( 'zij_label' ) ) . '">' . $toc . $promo . '</aside>';
}

/**
 * De hoofdstukbalk voor telefoon en tablet: vorig, titel met lijst, volgend.
 *
 * @param  array $hoofdstukken Hoofdstukken.
 * @return string
 */
function sfp_page_config_artikel_balk( array $hoofdstukken ) {
    if ( count( $hoofdstukken ) < 2 ) {
        return '';
    }
    $t     = 'sfp_page_config_artikel_tekst';
    $items = '';
    foreach ( $hoofdstukken as $h ) {
        $items .= '<li><a href="#' . esc_attr( $h['id'] ) . '">' . esc_html( $h['titel'] ) . '</a></li>';
    }
    return '<nav class="sfp-art-balk" id="sfp-art-balk" aria-label="' . esc_attr( $t( 'hoofdstukken' ) ) . '" hidden>'
        . '<ol class="sfp-art-balk__lijst" id="sfp-art-balk-lijst" hidden>' . $items . '</ol>'
        . '<div class="sfp-art-balk__rij">'
        . '<button type="button" class="sfp-art-balk__pijl" data-sfp-stap="-1" aria-label="' . esc_attr( $t( 'vorig' ) ) . '">' . sfp_page_config_artikel_icoon( 'links' ) . '</button>'
        . '<button type="button" class="sfp-art-balk__titel" aria-expanded="false" aria-controls="sfp-art-balk-lijst"><span></span>' . sfp_page_config_artikel_icoon( 'omlaag' ) . '</button>'
        . '<button type="button" class="sfp-art-balk__pijl" data-sfp-stap="1" aria-label="' . esc_attr( $t( 'volgend' ) ) . '">' . sfp_page_config_artikel_icoon( 'rechts' ) . '</button>'
        . '</div></nav>';
}

/**
 * De auteurskaart. De bio komt uit het gebruikersprofiel; regels die met
 * √ beginnen worden de feitenlijst.
 *
 * @param  WP_Post $post Bericht.
 * @return string        HTML, of '' zonder bio.
 */
function sfp_page_config_artikel_auteurskaart( $post ) {
    $auteur = (int) $post->post_author;
    $bio    = trim( (string) get_the_author_meta( 'description', $auteur ) );
    if ( '' === $bio ) {
        return '';
    }
    $t          = 'sfp_page_config_artikel_tekst';
    $naam       = get_the_author_meta( 'display_name', $auteur );
    $voornaam   = trim( (string) get_the_author_meta( 'first_name', $auteur ) );
    $toegestaan = array(
        'a'      => array( 'href' => true ),
        'em'     => array(),
        'strong' => array(),
        'br'     => array(),
    );
    $alineas = array();
    $feiten  = array();
    foreach ( preg_split( '/\r\n|\r|\n|<br\s*\/?>/i', $bio ) as $regel ) {
        $regel = trim( $regel );
        if ( '' === $regel ) {
            continue;
        }
        if ( 0 === strpos( $regel, '√' ) ) {
            $feiten[] = trim( preg_replace( '/^√\s*/u', '', $regel ) );
        } else {
            $alineas[] = $regel;
        }
    }
    $tekst = '';
    foreach ( $alineas as $alinea ) {
        $tekst .= '<p>' . wp_kses( $alinea, $toegestaan ) . '</p>';
    }
    if ( $feiten ) {
        $tekst .= '<ul>';
        foreach ( $feiten as $feit ) {
            $tekst .= '<li>' . wp_kses( $feit, $toegestaan ) . '</li>';
        }
        $tekst .= '</ul>';
    }
    $tekst .= '<a class="sfp-pijl" href="' . esc_url( sfp_page_config_artikel_auteur_url( $auteur ) ) . '">' . esc_html( sprintf( $t( 'meer_over' ), '' !== $voornaam ? $voornaam : $naam ) ) . '</a>';

    return '<section class="sfp-art-auteur" aria-labelledby="sfp-art-auteur-kop">'
        . get_avatar( $auteur, 72, '', $naam, array( 'loading' => 'lazy' ) )
        . '<div class="sfp-art-auteur__kop"><span class="sfp-blok-label">' . esc_html( $t( 'auteur' ) ) . '</span><h3 id="sfp-art-auteur-kop">' . esc_html( $naam ) . '</h3></div>'
        . '<div class="sfp-art-auteur__tekst">' . $tekst . '</div></section>';
}

/**
 * De voetregels: verantwoording en onderwerpen.
 *
 * @param  WP_Post $post Bericht.
 * @return string
 */
function sfp_page_config_artikel_voetregels( $post ) {
    $t     = 'sfp_page_config_artikel_tekst';
    $links = '';
    foreach ( array( 'artikel_redactie_url' => 'redactiebeleid', 'artikel_bibliografie_url' => 'bibliografie' ) as $sleutel => $tekst ) {
        $url = (string) sfp_page_config_get_setting( $sleutel, '' );
        if ( '' !== $url ) {
            $links .= '<a href="' . esc_url( $url ) . '">' . esc_html( $t( $tekst ) ) . '</a>';
        }
    }
    $rijen = '<div class="sfp-art-voet__rij"><span class="sfp-blok-label">' . sfp_page_config_artikel_icoon( 'vink' ) . esc_html( $t( 'onderbouwd' ) ) . '</span><p>' . esc_html( $t( 'onderbouwd_zin' ) ) . '</p>'
        . ( '' !== $links ? '<p class="sfp-art-voet__links">' . $links . '</p>' : '' ) . '</div>';

    $tags = get_the_terms( $post, 'post_tag' );
    if ( is_array( $tags ) && $tags ) {
        $onderwerpen = '';
        foreach ( $tags as $tag ) {
            $url = get_term_link( $tag );
            if ( ! is_wp_error( $url ) ) {
                $onderwerpen .= '<a href="' . esc_url( $url ) . '">' . esc_html( $tag->name ) . '</a>';
            }
        }
        if ( '' !== $onderwerpen ) {
            $rijen .= '<div class="sfp-art-voet__rij"><span class="sfp-blok-label">' . sfp_page_config_artikel_icoon( 'label' ) . esc_html( $t( 'onderwerpen' ) ) . '</span><p class="sfp-art-voet__links">' . $onderwerpen . '</p></div>';
        }
    }
    return '<div class="sfp-art-voet">' . $rijen . '</div>';
}

/* =========================================================================
 * Het artikel
 * ====================================================================== */

/**
 * Bouw het hele artikel op. Aangeroepen vanuit includes/artikel-sjabloon.php,
 * binnen de lus.
 *
 * @param  WP_Post $post Bericht.
 * @return string
 */
function sfp_page_config_artikel_render( $post ) {
    // Eerst de inhoud: de blokken melden daarbij wat de kopkaart nodig heeft.
    $inhoud = apply_filters( 'the_content', get_the_content( null, false, $post ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- kernfilter.
    $inhoud = str_replace( ']]>', ']]&gt;', $inhoud );

    if ( post_password_required( $post ) ) {
        return '<article id="post-' . (int) $post->ID . '" class="' . esc_attr( implode( ' ', get_post_class( 'sfp-artikel sfp-artikel--zonder-zij', $post ) ) ) . '"><div class="sfp-artikel__raster"><div class="sfp-artikel__kolom"><h1 class="entry-title sfp-art-titel">' . get_the_title( $post ) . '</h1><div class="entry-content clear sfp-art-inhoud">' . $inhoud . '</div></div></div></article>';
    }

    list( $inhoud, $hoofdstukken ) = sfp_page_config_artikel_hoofdstukken( $inhoud, sfp_page_config_artikel_hoofdstuktitels( $post->post_content ) );
    $inhoud = sfp_page_config_artikel_promo_in_tekst( $inhoud, sfp_page_config_artikel_promokaart( 'inline' ) );

    // Vervolgblok: een Astra-layout (of andere code) aan de actie
    // sfp_artikel_vervolg, met weergaveregels per pijler.
    ob_start();
    /**
     * Plek voor het vervolgblok onder een artikel.
     *
     * @param WP_Post $post Het bericht.
     */
    do_action( 'sfp_artikel_vervolg', $post );
    $vervolg = trim( (string) ob_get_clean() );

    $affiliate = function_exists( 'sfp_page_config_affiliate_uitleg' ) ? sfp_page_config_affiliate_uitleg( $post ) : '';
    $zij       = sfp_page_config_artikel_zijkolom( $post, $hoofdstukken );

    $html  = '<article id="post-' . (int) $post->ID . '" class="' . esc_attr( implode( ' ', get_post_class( 'sfp-artikel' . ( '' === $zij ? ' sfp-artikel--zonder-zij' : '' ), $post ) ) ) . '">';
    $html .= '<div class="sfp-artikel__raster"><div class="sfp-artikel__kolom">';
    $html .= sfp_page_config_artikel_pijlerregel( $post );
    $html .= '<h1 class="entry-title sfp-art-titel">' . get_the_title( $post ) . '</h1>';
    $html .= sfp_page_config_artikel_kopkaart( $post, $hoofdstukken );
    $html .= '<div class="entry-content clear sfp-art-inhoud">' . $inhoud . '</div>';
    $html .= '' !== $vervolg ? '<div class="sfp-art-vervolg">' . $vervolg . '</div>' : '';
    $html .= $affiliate;
    $html .= sfp_page_config_artikel_auteurskaart( $post );
    $html .= sfp_page_config_artikel_voetregels( $post );
    $html .= '</div>' . $zij . '</div></article>';
    $html .= sfp_page_config_artikel_balk( $hoofdstukken );

    return $html;
}

/* =========================================================================
 * Instellingen en stand via REST
 * ====================================================================== */

/**
 * De instellingen van de artikelopmaak, met hun soort.
 *
 * @return array<string, string> sleutel => vink, url, tekst, lang, getal of regels.
 */
function sfp_page_config_artikel_instellingen() {
    return array(
        'artikel_aan'              => 'vink',
        'artikel_voorkeursbron'    => 'vink',
        'artikel_factcheck_url'    => 'url',
        'artikel_redactie_url'     => 'url',
        'artikel_bibliografie_url' => 'url',
        'artikel_promo_beeld'      => 'getal',
        'artikel_promo_label'      => 'tekst',
        'artikel_promo_titel'      => 'tekst',
        'artikel_promo_tekst'      => 'lang',
        'artikel_promo_linktekst'  => 'tekst',
        'artikel_promo_url'        => 'url',
        'affiliate_patronen'       => 'regels',
        'affiliate_regel'          => 'tekst',
        'affiliate_uitleg'         => 'lang',
        'update_kanaal'            => 'kanaal',
    );
}

/**
 * Schoon de instellingen van de artikelopmaak op.
 *
 * @param  array $input Ruwe invoer.
 * @return array        Alleen de sleutels van de artikelopmaak.
 */
function sfp_page_config_artikel_sanitize( $input ) {
    $schoon    = array();
    $input     = is_array( $input ) ? $input : array();
    $bewaard   = get_option( 'sfp_settings', array() );
    $bewaard   = is_array( $bewaard ) ? $bewaard : array();
    // Het instellingenformulier stuurt een niet-aangevinkt vakje niet mee;
    // daar betekent ontbreken "uit". Elders (REST, een andere opslag van
    // sfp_settings) betekent ontbreken: laat de bewaarde waarde staan.
    $formulier = ! empty( $input['artikel_formulier'] );
    foreach ( sfp_page_config_artikel_instellingen() as $sleutel => $soort ) {
        if ( ! array_key_exists( $sleutel, $input ) && ! ( $formulier && 'vink' === $soort ) ) {
            $schoon[ $sleutel ] = isset( $bewaard[ $sleutel ] ) ? $bewaard[ $sleutel ] : '';
            continue;
        }
        $ruw = isset( $input[ $sleutel ] ) ? $input[ $sleutel ] : '';
        switch ( $soort ) {
            case 'vink':
                $schoon[ $sleutel ] = ! empty( $ruw ) && 'false' !== $ruw ? '1' : '';
                break;
            case 'url':
                $schoon[ $sleutel ] = esc_url_raw( trim( (string) $ruw ) );
                break;
            case 'getal':
                $schoon[ $sleutel ] = absint( $ruw ) ? (string) absint( $ruw ) : '';
                break;
            case 'lang':
                $schoon[ $sleutel ] = sanitize_textarea_field( (string) $ruw );
                break;
            case 'regels':
                $regels = is_array( $ruw ) ? $ruw : preg_split( '/\r\n|\r|\n/', (string) $ruw );
                $regels = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', array_map( 'trim', $regels ) ), 'strlen' ) ) );
                $schoon[ $sleutel ] = implode( "\n", $regels );
                break;
            case 'kanaal':
                $schoon[ $sleutel ] = 'proef' === $ruw ? 'proef' : '';
                break;
            default:
                $schoon[ $sleutel ] = sanitize_text_field( (string) $ruw );
        }
    }
    return $schoon;
}

add_action( 'rest_api_init', 'sfp_page_config_artikel_rest' );

/**
 * REST-routes voor beheerders:
 *
 *   GET  /sfp/v1/artikel/instellingen   de instellingen en wat de server kan
 *   POST /sfp/v1/artikel/instellingen   een of meer instellingen bijwerken
 */
function sfp_page_config_artikel_rest() {
    $mag = function () {
        return current_user_can( 'manage_options' );
    };
    register_rest_route(
        'sfp/v1',
        '/artikel/instellingen',
        array(
            array(
                'methods'             => 'GET',
                'permission_callback' => $mag,
                'callback'            => 'sfp_page_config_artikel_rest_lees',
            ),
            array(
                'methods'             => 'POST',
                'permission_callback' => $mag,
                'callback'            => 'sfp_page_config_artikel_rest_schrijf',
            ),
        )
    );
}

/**
 * Lees de instellingen en de stand.
 *
 * @return WP_REST_Response
 */
function sfp_page_config_artikel_rest_lees() {
    $alle = get_option( 'sfp_settings', array() );
    $uit  = array();
    foreach ( array_keys( sfp_page_config_artikel_instellingen() ) as $sleutel ) {
        $uit[ $sleutel ] = isset( $alle[ $sleutel ] ) ? $alle[ $sleutel ] : '';
    }
    return rest_ensure_response(
        array(
            'versie'       => SFP_PAGE_CONFIG_VERSION,
            'taal'         => sfp_page_config_artikel_taal(),
            'instellingen' => $uit,
            'affiliate'    => function_exists( 'sfp_page_config_affiliate_teksten' ) ? sfp_page_config_affiliate_teksten() : null,
            'deelbeeld'    => function_exists( 'sfp_page_config_deelbeeld_stand' ) ? sfp_page_config_deelbeeld_stand() : null,
        )
    );
}

/**
 * Werk instellingen bij. Alleen de meegestuurde sleutels veranderen.
 *
 * @param  WP_REST_Request $verzoek Verzoek.
 * @return WP_REST_Response
 */
function sfp_page_config_artikel_rest_schrijf( WP_REST_Request $verzoek ) {
    $alle    = get_option( 'sfp_settings', array() );
    $alle    = is_array( $alle ) ? $alle : array();
    $bekend  = sfp_page_config_artikel_instellingen();
    $invoer  = array();
    $gewoon  = (array) $verzoek->get_json_params() + (array) $verzoek->get_body_params();
    foreach ( $bekend as $sleutel => $soort ) {
        $invoer[ $sleutel ] = array_key_exists( $sleutel, $gewoon ) ? $gewoon[ $sleutel ] : ( isset( $alle[ $sleutel ] ) ? $alle[ $sleutel ] : '' );
    }
    $alle = array_merge( $alle, sfp_page_config_artikel_sanitize( $invoer ) );
    update_option( 'sfp_settings', $alle );
    if ( class_exists( 'SFP_Page_Config_Updater' ) ) {
        SFP_Page_Config_Updater::wis_cache();
    }
    return sfp_page_config_artikel_rest_lees();
}

/**
 * De sectie Artikelopmaak op het tabblad Instellingen.
 *
 * @param array $s De bewaarde instellingen.
 */
function sfp_page_config_artikel_instellingen_formulier( $s ) {
    $w = function ( $sleutel ) use ( $s ) {
        return isset( $s[ $sleutel ] ) ? (string) $s[ $sleutel ] : '';
    };
    $veld = function ( $sleutel, $label, $hulp = '', $soort = 'text' ) use ( $w ) {
        echo '<tr><th><label for="sfp-' . esc_attr( $sleutel ) . '">' . esc_html( $label ) . '</label></th><td>';
        if ( 'textarea' === $soort ) {
            echo '<textarea id="sfp-' . esc_attr( $sleutel ) . '" name="sfp_settings[' . esc_attr( $sleutel ) . ']" rows="3" class="large-text">' . esc_textarea( $w( $sleutel ) ) . '</textarea>';
        } elseif ( 'checkbox' === $soort ) {
            echo '<label><input type="checkbox" id="sfp-' . esc_attr( $sleutel ) . '" name="sfp_settings[' . esc_attr( $sleutel ) . ']" value="1" ' . checked( '1' === $w( $sleutel ), true, false ) . ' /> ' . esc_html( $hulp ) . '</label>';
            $hulp = '';
        } else {
            echo '<input type="' . esc_attr( $soort ) . '" id="sfp-' . esc_attr( $sleutel ) . '" name="sfp_settings[' . esc_attr( $sleutel ) . ']" value="' . esc_attr( $w( $sleutel ) ) . '" class="regular-text" />';
        }
        if ( '' !== $hulp ) {
            echo '<p class="description">' . esc_html( $hulp ) . '</p>';
        }
        echo '</td></tr>';
    };
    $stand   = function_exists( 'sfp_page_config_deelbeeld_stand' ) ? sfp_page_config_deelbeeld_stand() : array();
    $teksten = function_exists( 'sfp_page_config_affiliate_teksten' ) ? sfp_page_config_affiliate_teksten() : array( 'regel' => '', 'uitleg' => '' );
    ?>
    <h2>Artikelopmaak</h2>
    <p style="color:#666;">De opmaak van berichten: kopkaart, inhoudsopgave, hoofdstukbalk, auteurskaart en voetregels. Uit betekent dat berichten blijven zoals ze zijn. De blokken voor artikelen werken ook als dit uit staat.</p>
    <input type="hidden" name="sfp_settings[artikel_formulier]" value="1" />
    <table class="form-table" role="presentation">
        <?php
        $veld( 'artikel_aan', 'Artikelopmaak', 'Berichten in de artikelopmaak tonen', 'checkbox' );
        $veld( 'artikel_voorkeursbron', 'Voorkeursbron bij Google', 'De actie in de kopkaart tonen', 'checkbox' );
        $veld( 'artikel_factcheck_url', 'Pagina over factchecken', 'Waar de regel Gefactcheckt naartoe linkt. Leeg: de redactionele verantwoording; ook leeg: geen link.', 'url' );
        $veld( 'artikel_redactie_url', 'Redactionele verantwoording', 'Link in de voetregels. Leeg: geen link.', 'url' );
        $veld( 'artikel_bibliografie_url', 'Bibliografie', 'Link in de voetregels. Leeg: geen link.', 'url' );
        ?>
    </table>
    <h3>Promokaart</h3>
    <p style="color:#666;">Eén kaart per site, rechts naast elk artikel en op telefoon en tablet na de eerste alinea van het eerste hoofdstuk. Zonder titel of link is er geen kaart.</p>
    <table class="form-table" role="presentation">
        <?php
        $veld( 'artikel_promo_beeld', 'Afbeelding', 'Het nummer (ID) van de afbeelding in de mediabibliotheek.', 'number' );
        $veld( 'artikel_promo_label', 'Label' );
        $veld( 'artikel_promo_titel', 'Titel' );
        $veld( 'artikel_promo_tekst', 'Tekst', '', 'textarea' );
        $veld( 'artikel_promo_linktekst', 'Tekst van de link' );
        $veld( 'artikel_promo_url', 'Link', '', 'url' );
        ?>
    </table>
    <h3>Affiliatemelding</h3>
    <p style="color:#666;">De plugin herkent affiliatelinks zelf, ook in tooltips. Bij een artikel met zo'n link staat een korte regel in de kopkaart en een uitlegblok onderaan. Zonder patronen of zonder teksten is er geen melding.</p>
    <table class="form-table" role="presentation">
        <?php
        $veld( 'affiliate_patronen', 'Herkenningspatronen', 'Eén per regel: een stuk van de link, bijvoorbeeld een domein. Een sterretje staat voor een willekeurig stuk.', 'textarea' );
        $veld( 'affiliate_regel', 'Regel in de kopkaart', 'Leeg op een Nederlandstalige site: de vastgestelde tekst. Nu getoond: ' . ( '' !== $teksten['regel'] ? $teksten['regel'] : '(geen)' ) );
        $veld( 'affiliate_uitleg', 'Uitleg onderaan', 'Leeg op een Nederlandstalige site: de vastgestelde tekst.', 'textarea' );
        ?>
    </table>
    <h3>Deelbeelden</h3>
    <p style="color:#666;">
        <?php
        if ( $stand ) {
            $klaar = ! empty( $stand['gd'] ) && ! empty( $stand['freetype'] ) && ! empty( $stand['jpeg'] ) && ! empty( $stand['kleurrollen'] );
            echo esc_html( $klaar ? 'De server kan deelbeelden maken.' : 'De server kan nu geen deelbeelden maken; de blokken tonen dezelfde kaart in HTML.' );
            echo ' ' . esc_html( empty( $stand['fonts_aanwezig'] ) ? 'De fonts worden opgehaald bij het eerste beeld.' : 'De fonts staan klaar.' );
            if ( ! empty( $stand['laatste_fout'] ) ) {
                echo '<br />Laatste melding: ' . esc_html( $stand['laatste_fout'] );
            }
        }
        ?>
    </p>
    <h3>Updates</h3>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="sfp-update-kanaal">Kanaal</label></th>
            <td>
                <select id="sfp-update-kanaal" name="sfp_settings[update_kanaal]">
                    <option value="" <?php selected( $w( 'update_kanaal' ), '' ); ?>>Gewone releases</option>
                    <option value="proef" <?php selected( $w( 'update_kanaal' ), 'proef' ); ?>>Ook proefversies (pre-releases)</option>
                </select>
                <p class="description">Proefversies zijn bedoeld voor één site tegelijk, om een nieuwe versie te beoordelen voordat het netwerk hem krijgt.</p>
            </td>
        </tr>
    </table>
    <?php
}
