<?php
/**
 * SFP Page Config - Instellingen, onderdelen en netwerknorm
 *
 * Eén plek voor alles wat de plugin per site bewaart (optie `sfp_settings`):
 *
 *   - de onderdelen van de plugin, per site aan of uit;
 *   - de netwerknorm: de waarde die geldt als een instelling leeg is;
 *   - welke instellingen op deze site van de norm afwijken;
 *   - opslaan per sleutel. Alleen de meegestuurde sleutels veranderen; al het
 *     andere in `sfp_settings` blijft staan. Tot en met 2.12.0 herbouwde de
 *     sanitizer de hele optie bij elke opslag.
 *
 * Bestaande sleutels houden hun naam en betekenis. Vervallen in 3.0.0 (de
 * plugin leest ze niet meer; de bewaarde waarde blijft staan zodat terugzetten
 * naar 2.12.0 niets kost): de negen kleurvelden `lr_*`, `progress_bar_color`,
 * `reading_time_accent_color`, `custom_css_rp`, `promo_scroll_gate`,
 * `promo_cooldown_hours`. De velden `anchor`, `hero` en `href` van de knop
 * onderaan staan niet meer in het beheer, maar een bewaarde waarde blijft
 * meetellen tot de bestemming van die soort pagina in het beheer is gekozen.
 *
 * @package SFP_Page_Config
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/bestanden.php';

/* =========================================================================
 * Lezen
 * ====================================================================== */

/**
 * Alle bewaarde instellingen.
 *
 * @return array
 */
function sfp_page_config_instellingen() {
    $alle = get_option( 'sfp_settings', array() );
    return is_array( $alle ) ? $alle : array();
}

/**
 * Eén instelling, met terugval als hij ontbreekt of leeg is.
 *
 * @param  string $key     Sleutel.
 * @param  mixed  $default Terugval.
 * @return mixed
 */
/**
 * De soorten pagina met een knop onderaan.
 *
 * @return string[]
 */
function sfp_page_config_paginasoorten() {
    return array( 'coaching', 'training', 'incompany', 'home', 'over' );
}

/* =========================================================================
 * Onderdelen
 * ====================================================================== */

/**
 * De onderdelen van de plugin.
 *
 * 'detail' is true als het onderdeel een eigen scherm heeft in het beheer.
 *
 * @return array<string, array{naam: string, uitleg: string, detail: bool}>
 */
function sfp_page_config_onderdelen() {
    return array(
        'knop'         => array(
            'naam'   => 'Knop onderaan',
            'uitleg' => 'Op een telefoon blijft op een dienstpagina één knop in beeld.',
            'detail' => true,
        ),
        'artikel'      => array(
            'naam'   => 'Artikelen',
            'uitleg' => 'Kopkaart, inhoudsopgave, auteurskaart en vervolg bij elk artikel.',
            'detail' => true,
        ),
        'reviews'      => array(
            'naam'   => 'Reviews',
            'uitleg' => 'Reviews van Google en je eigen reviews.',
            'detail' => true,
        ),
        'tooltips'     => array(
            'naam'   => 'Tooltips',
            'uitleg' => 'Uitleg bij een woord, die verschijnt bij aanwijzen of aantikken.',
            'detail' => true,
        ),
        'footer'       => array(
            'naam'   => 'Footerlinks',
            'uitleg' => 'De groepen met links onderaan de site.',
            'detail' => true,
        ),
        'voortgang'    => array(
            'naam'   => 'Voortgangsbalk',
            'uitleg' => 'Een dunne balk bovenaan die meeloopt terwijl de lezer scrolt.',
            'detail' => false,
        ),
        'whatsapp'     => array(
            'naam'   => 'WhatsApp-knop',
            'uitleg' => 'Een zwevende knop op de pagina\'s waar je hem aanzet.',
            'detail' => true,
        ),
        'cursusdata'   => array(
            'naam'   => 'Cursusdata',
            'uitleg' => 'Startmomenten van trainingen, op de pagina en in het aanmeldformulier.',
            'detail' => true,
        ),
        'zelftest'     => array(
            'naam'   => 'Zelftest',
            'uitleg' => 'Een zelftest voor bezoekers, met het resultaat als pdf per e-mail via het CRM.',
            'detail' => true,
        ),
        'pijler'       => array(
            'naam'   => 'Pijler',
            'uitleg' => 'Een pagina kan de gids van een categorie zijn: in de artikelopmaak, met de kaart van alle artikelen en begrippen erin. Links naar de categorie gaan naar die pagina.',
            'detail' => false,
        ),
        'begrip'       => array(
            'naam'   => 'Begrip',
            'uitleg' => 'Begrippen krijgen de vorm van de woordenlijst: een definitie bovenaan, verwante begrippen eronder en de definitie als tooltip in artikelen.',
            'detail' => false,
        ),
        'tools'        => array(
            'naam'   => 'Online tools',
            'uitleg' => 'Kleine online tools voor bezoekers: tests, generatoren, rekenaars, checklists en tekstanalyses. Met pdf, Word, deellink, mail naar jezelf en duimpjes.',
            'detail' => true,
        ),
        'kleurrollen'  => array(
            'naam'   => 'Kleurrollen',
            'uitleg' => 'Welke kleur van het Astra-palet elke merkrol draagt (primair, secundair, links en knoppen en de rest). Bouwstenen en opmaak gebruiken die rollen.',
            'detail' => true,
        ),
        'kopfont'      => array(
            'naam'   => 'Kopfont als variabele',
            'uitleg' => 'Het lettertype van H1 en van de andere koppen als variabele, zodat kaarten, reviews en tools het kopfont krijgen.',
            'detail' => false,
        ),
        'berichttypes' => array(
            'naam'   => 'Berichttypes',
            'uitleg' => 'Begrippen en online tools als eigen soort bericht, met hun adres, namen en menu-icoon.',
            'detail' => false,
        ),
        'paginabeheer' => array(
            'naam'   => 'Paginabeheer voor AI',
            'uitleg' => 'Pagina\'s, berichten, begrippen en tools lezen, bijwerken en herstellen via de MCP-koppeling, met revisiecontrole.',
            'detail' => false,
        ),
        'banner'       => array(
            'naam'   => 'Banner',
            'uitleg' => 'Eén regel boven de header: een boek of e-course in een periode die je kiest, anders vanzelf de eerstvolgende groep uit de cursusdata.',
            'detail' => true,
        ),
        'netwerk'      => array(
            'naam'   => 'Netwerk',
            'uitleg' => 'De labels van het netwerk voor het blok Netwerk (bijvoorbeeld op de Over-pagina): naam, adres, zin en icoon. De eigen site valt vanzelf weg.',
            'detail' => true,
        ),
        'logostrook'   => array(
            'naam'   => 'Logostrook',
            'uitleg' => 'De logo\'s van opdrachtgevers voor elke logostrook van de site: één lijst in een vaste volgorde, met een label en per logo eventueel een Engelse variant. Gaat voor de lijst in het blok.',
            'detail' => true,
        ),
        'latepoint'    => array(
            'naam'   => 'LatePoint-opmaak',
            'uitleg' => 'Leesbare knoppen en het lettertype van de site op de pagina waar een klant zijn afspraak beheert of annuleert (link uit de bevestigingsmail).',
            'detail' => false,
        ),
        'contact'      => array(
            'naam'   => 'Contact',
            'uitleg' => 'Telefoon, e-mail en adres voor het blok Contactgegevens (bijvoorbeeld op de contactpagina). Leeg: het nummer en het e-mailadres van het netwerk.',
            'detail' => true,
        ),
        'formulierregel' => array(
            'naam'   => 'Formulierregel',
            'uitleg' => 'Onder de gekozen formulieren de regel Bel ... of mail naar ..., met het nummer en het adres uit Contact. Zolang het snippet Formulieren: mail-link als fallback aan staat, doet dat het.',
            'detail' => true,
        ),
        'uspstrook'    => array(
            'naam'   => 'USP-strook',
            'uitleg' => 'Eén regel onder de header op trainings- en coachpagina\'s: drie redenen om te kiezen, het Google-cijfer en een belknop. Niet op een telefoon.',
            'detail' => true,
        ),
        'kopieerblok'  => array(
            'naam'   => 'Kopieerblok',
            'uitleg' => 'Tekst die de lezer met één klik kopieert, zoals een prompt. Lange tekst staat ingeklapt met Toon alles.',
            'detail' => false,
        ),
        'bronnenlijst' => array(
            'naam'   => 'Bronnenlijst',
            'uitleg' => 'Alle bronnen uit de tooltips op alfabet, met een zoekveld en de artikelen waarin ze staan. Voor de bibliografiepagina.',
            'detail' => false,
        ),
        'praktijkblok' => array(
            'naam'   => 'Praktijkblok',
            'uitleg' => 'Een citaat in de stijl Uit de trainingspraktijk: een ervaring van de trainer, met naam en merk eronder.',
            'detail' => false,
        ),
        'auteursbio'   => array(
            'naam'   => 'Auteursbio',
            'uitleg' => 'Opmaak en links in de biografie van een auteur, en regels die met een vinkje beginnen als lijst met vinkjes.',
            'detail' => false,
        ),
        'merkwaarden'  => array(
            'naam'   => 'Merkwaarden',
            'uitleg' => 'De merkwaarden voor bezoekers, live uit de merkbron, met per taal een slotzin en een knop.',
            'detail' => true,
        ),
        'formulierbewaking' => array(
            'naam'   => 'Formulierbewaking',
            'uitleg' => 'De dagelijkse formuliercontrole: de testinzending onzichtbaar houden en opruimen, een mail als een formulier bij een bezoeker misgaat, en de logs voor de cloudcontrole. Zolang de snippets nog aan staan, doen die het.',
            'detail' => false,
        ),
        'snippets'     => array(
            'naam'   => 'Snippets beheren op afstand',
            'uitleg' => 'Claude leest, maakt en wijzigt de codefragmenten van ASE Pro en zet ze aan of uit, zonder browser. Alleen voor onderhoud.',
            'detail' => false,
        ),
    );
}

/**
 * Is er minstens één gepubliceerd bericht met deze meta? Bewaard per aanvraag
 * en een uur in een transient, want het antwoord verandert zelden.
 *
 * @param  string $meta_key   Meta-sleutel.
 * @param  string $meta_value Waarde, of '' voor elke niet-lege waarde.
 * @return bool
 */
function sfp_page_config_in_gebruik( $meta_key, $meta_value = '' ) {
    static $geheugen = array();
    $sleutel         = $meta_key . '|' . $meta_value;
    if ( isset( $geheugen[ $sleutel ] ) ) {
        return $geheugen[ $sleutel ];
    }
    $bewaard = get_transient( 'sfp_pc_in_gebruik' );
    $bewaard = is_array( $bewaard ) ? $bewaard : array();
    if ( isset( $bewaard[ $sleutel ] ) ) {
        $geheugen[ $sleutel ] = (bool) $bewaard[ $sleutel ];
        return $geheugen[ $sleutel ];
    }
    $meta = array( 'key' => $meta_key );
    if ( '' !== $meta_value ) {
        $meta['value'] = $meta_value;
    } else {
        $meta['value']   = '';
        $meta['compare'] = '!=';
    }
    $gevonden             = get_posts(
        array(
            'post_type'        => sfp_page_config_post_types(),
            'post_status'      => array( 'publish', 'draft', 'pending', 'future', 'private' ),
            'posts_per_page'   => 1,
            'fields'           => 'ids',
            'no_found_rows'    => true,
            'suppress_filters' => true,
            'meta_query'       => array( $meta ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- één keer per uur.
        )
    );
    $geheugen[ $sleutel ] = ! empty( $gevonden );
    $bewaard[ $sleutel ]  = $geheugen[ $sleutel ] ? 1 : 0;
    set_transient( 'sfp_pc_in_gebruik', $bewaard, HOUR_IN_SECONDS );
    return $geheugen[ $sleutel ];
}

add_action( 'save_post', 'sfp_page_config_in_gebruik_wis' );
add_action( 'deleted_post', 'sfp_page_config_in_gebruik_wis' );

/**
 * Het antwoord van sfp_page_config_in_gebruik() kan na een opslag anders zijn.
 */
function sfp_page_config_in_gebruik_wis() {
    delete_transient( 'sfp_pc_in_gebruik' );
}

/**
 * De stand van een onderdeel als de site niets heeft gekozen.
 *
 * Uitgangspunt: een update verandert niets aan wat een site doet. Een
 * onderdeel dat de site gebruikt staat aan, een onderdeel dat nergens voor
 * dient staat uit.
 *
 * @param  string $id Onderdeel.
 * @return bool
 */
function sfp_page_config_onderdeel_standaard( $id ) {
    switch ( $id ) {
        case 'knop':
            return sfp_page_config_in_gebruik( 'sfp_page_type' );
        case 'cursusdata':
            return sfp_page_config_in_gebruik( 'sfp_page_type', 'training' );
        case 'whatsapp':
            return sfp_page_config_in_gebruik( 'sfp_whatsapp', '1' );
        case 'reviews':
            $opties = get_option( 'sfp_reviews_options', array() );
            if ( is_array( $opties ) && ! empty( $opties['place_id'] ) && ! empty( $opties['api_key'] ) ) {
                return true;
            }
            $eigen = json_decode( (string) get_option( 'sfp_reviews_manual', '[]' ), true );
            return is_array( $eigen ) && ! empty( $eigen );
        case 'snippets':
            // Schrijft code die de site uitvoert: alleen aan als iemand dat kiest.
            return false;
        case 'banner':
            // Verschijnt op elke pagina: per site aan na het overzetten van de Astra-layout.
            return false;
        case 'uspstrook':
            // Zonder ingevulde USP's toont de strook niets (3.30.0).
            return true;
        case 'zelftest':
            // Verzamelt gegevens van bezoekers: alleen aan als iemand dat kiest.
            return false;
        case 'kleurrollen':
        case 'kopfont':
        case 'berichttypes':
        case 'paginabeheer':
            // Fundament (3.15.0): per site aan na een nameting, daarna gaat het snippet uit.
            return false;
        case 'kopieerblok':
        case 'bronnenlijst':
        case 'praktijkblok':
        case 'auteursbio':
            // Inhoudsbouwstenen (3.20.0): per site aan na een nameting, daarna gaat het snippet uit.
            return false;
        case 'begrip':
        case 'pijler':
        case 'tools':
            // Verandert adressen en de opmaak van berichten: alleen aan als iemand dat kiest.
            return false;
        default:
            return true;
    }
}

/**
 * Staat een onderdeel aan op deze site?
 *
 * Artikelen volgt de bestaande instelling `artikel_aan`. De andere onderdelen
 * staan in `onderdelen[id]` als '1' of '0'; ontbreekt de keuze, dan geldt
 * sfp_page_config_onderdeel_standaard().
 *
 * @param  string $id Onderdeel.
 * @return bool
 */
function sfp_page_config_onderdeel_aan( $id ) {
    $alle = sfp_page_config_instellingen();
    if ( 'artikel' === $id ) {
        return isset( $alle['artikel_aan'] ) && '1' === (string) $alle['artikel_aan'];
    }
    if ( isset( $alle['onderdelen'][ $id ] ) && '' !== (string) $alle['onderdelen'][ $id ] ) {
        return '1' === (string) $alle['onderdelen'][ $id ];
    }
    return sfp_page_config_onderdeel_standaard( $id );
}

/* =========================================================================
 * Links naar eigen pagina's
 * ====================================================================== */

/**
 * Lees een link die als pagina is gekozen of als adres is bewaard.
 *
 * Het beheer laat een pagina kiezen en bewaart het nummer onder
 * `<basis>_pagina`. Zo blijft de link kloppen als het adres van de pagina
 * verandert. Het oude veld `<basis>_url` blijft werken als er geen pagina is
 * gekozen.
 *
 * @param  string $basis Sleutel zonder achtervoegsel, bijvoorbeeld artikel_redactie.
 * @return string        Adres, of '' als er niets is ingesteld.
 */
function sfp_page_config_link_instelling( $basis ) {
    $alle    = sfp_page_config_instellingen();
    $post_id = isset( $alle[ $basis . '_pagina' ] ) ? (int) $alle[ $basis . '_pagina' ] : 0;
    if ( $post_id && 'publish' === get_post_status( $post_id ) ) {
        $url = get_permalink( $post_id );
        if ( $url ) {
            return (string) $url;
        }
    }
    return isset( $alle[ $basis . '_url' ] ) ? (string) $alle[ $basis . '_url' ] : '';
}

/* =========================================================================
 * Knop onderaan: instelling per soort pagina
 * ====================================================================== */

/**
 * De knop onderaan voor één soort pagina.
 *
 * @param  string $type coaching, training of incompany.
 * @return array|null   tekst, doel (form, pagina of link), href en target; null bij een onbekende soort.
 */
function sfp_page_config_get_sticky_cta( $type ) {
    if ( ! in_array( $type, sfp_page_config_paginasoorten(), true ) ) {
        return null;
    }
    $alle  = sfp_page_config_instellingen();
    $eigen = isset( $alle['sticky_cta'][ $type ] ) && is_array( $alle['sticky_cta'][ $type ] ) ? $alle['sticky_cta'][ $type ] : array();

    $tekst = isset( $eigen['text'] ) ? trim( (string) $eigen['text'] ) : '';
    if ( '' === $tekst ) {
        $tekst = sfp_page_config_tekst( 'knop_' . $type );
    }

    $doel = isset( $eigen['doel'] ) ? (string) $eigen['doel'] : '';
    $href = '';
    if ( 'pagina' === $doel ) {
        $post_id = isset( $eigen['pagina'] ) ? (int) $eigen['pagina'] : 0;
        $href    = $post_id && 'publish' === get_post_status( $post_id ) ? (string) get_permalink( $post_id ) : '';
    } elseif ( 'link' === $doel ) {
        $href = isset( $eigen['link'] ) ? trim( (string) $eigen['link'] ) : '';
        // Een anker wijst naar een onderdeel van de pagina zelf. De knop
        // gaat dan naar het formulier; het anker telt mee als herkenning.
        if ( 0 === strpos( $href, '#' ) ) {
            $href = '';
        }
    } elseif ( '' === $doel && ! empty( $eigen['href'] ) ) {
        // Tot en met 2.12.0: één veld voor een anker of een adres.
        $oud = trim( (string) $eigen['href'] );
        if ( '' !== $oud && 0 !== strpos( $oud, '#' ) ) {
            $doel = 'link';
            $href = $oud;
        }
    }
    if ( '' === $href ) {
        $doel = 'form';
    }

    return sfp_page_config_sticky_cta_vorm( $tekst, $doel, $href );
}

/**
 * Zet tekst, doel en adres in de vorm die het script verwacht.
 *
 * @param  string $tekst Tekst op de knop.
 * @param  string $doel  form, pagina of link.
 * @param  string $href  Adres bij pagina of link; bij form leeg.
 * @return array
 */
function sfp_page_config_sticky_cta_vorm( $tekst, $doel, $href ) {
    $eigen_site = '' !== $href && 0 === strpos( $href, home_url() );
    return array(
        'text'   => (string) $tekst,
        'doel'   => 'form' === $doel ? 'form' : ( 'pagina' === $doel ? 'pagina' : 'link' ),
        'href'   => 'form' === $doel ? '' : (string) $href,
        'target' => ( 'form' === $doel || 'pagina' === $doel || $eigen_site ) ? '_self' : '_blank',
    );
}

/* =========================================================================
 * Netwerknorm en afwijkingen
 * ====================================================================== */

/**
 * De instellingen die een netwerknorm hebben: leeg betekent de norm.
 *
 * @return array<string, array{label: string, norm: string}> Per pad (met punten).
 */
function sfp_page_config_norm() {
    $norm = array();
    foreach ( sfp_page_config_teksten_register() as $sleutel => $tekst ) {
        // Een tekst zonder standaard (de affiliatemelding) is per site eigen:
        // er is geen norm om van af te wijken.
        if ( '' === sfp_page_config_tekst_standaard( $sleutel ) ) {
            continue;
        }
        $norm[ sfp_page_config_tekst_pad( $sleutel ) ] = array(
            'label' => 'Tekst bij ' . $tekst['groep'] . ': ' . $tekst['label'],
            'norm'  => sfp_page_config_tekst_standaard( $sleutel ),
        );
    }
    $norm['whatsapp_number']    = array(
        'label' => 'Telefoonnummer van de WhatsApp-knop',
        'norm'  => sfp_page_config_whatsapp_norm(),
    );
    $norm['words_per_minute']   = array(
        'label' => 'Leessnelheid voor de leestijd',
        'norm'  => '250',
    );
    $norm['banner.kleur']       = array(
        'label' => 'Kleur van de banner',
        'norm'  => 'links',
    );
    $norm['banner.tekst_kleur'] = array(
        'label' => 'Tekstkleur van de banner',
        'norm'  => 'wit',
    );
    $norm['coach_persoon']      = array(
        'label' => 'Site van de coach in de gegevens voor Google',
        'norm'  => function_exists( 'sfp_page_config_coach_norm' ) ? sfp_page_config_coach_norm() : '',
    );
    $norm['update_kanaal']      = array(
        'label' => 'Soort versies die deze site krijgt',
        'norm'  => '',
    );
    return $norm;
}

/**
 * Het telefoonnummer van het netwerk voor de WhatsApp-knop, alleen cijfers.
 *
 * @return string
 */
function sfp_page_config_whatsapp_norm() {
    return '31850601355';
}

/**
 * Lees een waarde op een pad met punten uit een geneste array.
 *
 * @param  array  $bron Array.
 * @param  string $pad  Pad, bijvoorbeeld sticky_cta.coaching.text.
 * @return mixed|null
 */
function sfp_page_config_pad_lees( array $bron, $pad ) {
    foreach ( explode( '.', $pad ) as $deel ) {
        if ( ! is_array( $bron ) || ! array_key_exists( $deel, $bron ) ) {
            return null;
        }
        $bron = $bron[ $deel ];
    }
    return $bron;
}

/**
 * Zet een waarde op een pad met punten in een geneste array. Een lege waarde
 * onder `teksten` haalt de sleutel weg.
 *
 * @param  array  $bron   Array.
 * @param  string $pad    Pad, bijvoorbeeld sticky_cta.coaching.text.
 * @param  string $waarde Waarde.
 * @return array
 */
function sfp_page_config_pad_schrijf( array $bron, $pad, $waarde ) {
    $delen = explode( '.', $pad );
    $ref   = &$bron;
    $n     = count( $delen );
    foreach ( $delen as $i => $deel ) {
        if ( $i === $n - 1 ) {
            if ( '' === $waarde && 'teksten' === $delen[0] ) {
                unset( $ref[ $deel ] );
            } else {
                $ref[ $deel ] = $waarde;
            }
        } else {
            if ( ! isset( $ref[ $deel ] ) || ! is_array( $ref[ $deel ] ) ) {
                $ref[ $deel ] = array();
            }
            $ref = &$ref[ $deel ];
        }
    }
    unset( $ref );
    return $bron;
}

/**
 * Wat deze site anders heeft ingesteld dan de netwerknorm.
 *
 * @return array<int, array{pad: string, label: string, waarde: string, norm: string}>
 */
function sfp_page_config_afwijkingen() {
    $alle = sfp_page_config_instellingen();
    $uit  = array();
    foreach ( sfp_page_config_norm() as $pad => $regel ) {
        $waarde = sfp_page_config_pad_lees( $alle, $pad );
        if ( null === $waarde || is_array( $waarde ) ) {
            continue;
        }
        $waarde = trim( (string) $waarde );
        if ( '' === $waarde || $waarde === $regel['norm'] ) {
            continue;
        }
        if ( 'whatsapp_number' === $pad && preg_replace( '/[^0-9]/', '', $waarde ) === $regel['norm'] ) {
            continue;
        }
        $toon = $waarde;
        $norm = $regel['norm'];
        if ( 'update_kanaal' === $pad ) {
            $toon = 'Ook proefversies';
            $norm = 'Gewone versies';
        }
        $uit[] = array(
            'pad'    => $pad,
            'label'  => $regel['label'],
            'waarde' => $toon,
            'norm'   => $norm,
        );
    }
    // Sinds 3.13.3: de schakelaar Husselen per zelftest (norm: aan).
    if ( function_exists( 'sfp_zelftest_instellingen_alle' ) ) {
        $zt = sfp_zelftest_instellingen_alle();
        foreach ( (array) $zt['tests'] as $test => $inst ) {
            if ( is_array( $inst ) && isset( $inst['husselen'] ) && '0' === (string) $inst['husselen'] ) {
                $uit[] = array(
                    'pad'    => 'zelftest.' . $test . '.husselen',
                    'label'  => 'Zelftest ' . $test . ': vragen en antwoorden husselen',
                    'waarde' => 'Uit',
                    'norm'   => 'Aan',
                );
            }
        }
    }
    // Sinds 3.19.0: het adres van een berichttype (norm: de standaard in de
    // taal van de site, zoals /glossary/ op een Engelse site).
    if ( function_exists( 'sfp_page_config_berichttype_afwijking' ) ) {
        foreach ( array_keys( sfp_page_config_berichttypes() ) as $type ) {
            $af = sfp_page_config_berichttype_afwijking( $type );
            if ( $af ) {
                $uit[] = array(
                    'pad'    => 'berichttypes.' . $type,
                    'label'  => 'Adres en namen van ' . ( 'tool' === $type ? 'de online tools' : 'de begrippen' ) . ' (de oude adressen sturen door)',
                    'waarde' => '/' . $af['nu'] . '/',
                    'norm'   => '/' . $af['norm'] . '/',
                );
            }
        }
    }
    return $uit;
}

/* =========================================================================
 * Opslaan
 * ====================================================================== */

/**
 * Bewaar een of meer instellingen. Alleen de meegestuurde sleutels
 * veranderen. Onbekende sleutels worden overgeslagen.
 *
 * @param  array $wijzigingen Sleutel => ruwe waarde.
 * @return array              De sleutels die zijn bewaard.
 */
function sfp_page_config_instellingen_bewaar( array $wijzigingen ) {
    $alle    = sfp_page_config_instellingen();
    $bewaard = array();

    foreach ( $wijzigingen as $sleutel => $ruw ) {
        switch ( $sleutel ) {
            case 'onderdelen':
                if ( ! is_array( $ruw ) ) {
                    break;
                }
                if ( ! isset( $alle['onderdelen'] ) || ! is_array( $alle['onderdelen'] ) ) {
                    $alle['onderdelen'] = array();
                }
                foreach ( $ruw as $id => $aan ) {
                    if ( ! array_key_exists( $id, sfp_page_config_onderdelen() ) ) {
                        continue;
                    }
                    $staat = ( true === $aan || '1' === (string) $aan || 'true' === $aan ) ? '1' : '0';
                    if ( 'artikel' === $id ) {
                        $alle['artikel_aan'] = '1' === $staat ? '1' : '';
                    } else {
                        $alle['onderdelen'][ $id ] = $staat;
                    }
                }
                $bewaard[] = $sleutel;
                break;

            case 'sticky_cta':
                if ( ! is_array( $ruw ) ) {
                    break;
                }
                if ( ! isset( $alle['sticky_cta'] ) || ! is_array( $alle['sticky_cta'] ) ) {
                    $alle['sticky_cta'] = array();
                }
                foreach ( sfp_page_config_paginasoorten() as $type ) {
                    if ( ! isset( $ruw[ $type ] ) || ! is_array( $ruw[ $type ] ) ) {
                        continue;
                    }
                    $oud         = isset( $alle['sticky_cta'][ $type ] ) && is_array( $alle['sticky_cta'][ $type ] ) ? $alle['sticky_cta'][ $type ] : array();
                    $in          = $ruw[ $type ];
                    $doel        = isset( $in['doel'] ) && in_array( $in['doel'], array( 'form', 'pagina', 'link' ), true ) ? $in['doel'] : ( isset( $oud['doel'] ) ? $oud['doel'] : 'form' );
                    $oud['text'] = array_key_exists( 'text', $in ) ? sanitize_text_field( (string) $in['text'] ) : ( isset( $oud['text'] ) ? $oud['text'] : '' );
                    if ( isset( $in['doel'] ) || isset( $oud['doel'] ) ) {
                        $oud['doel'] = $doel;
                    }
                    $oud['pagina'] = array_key_exists( 'pagina', $in ) ? absint( $in['pagina'] ) : ( isset( $oud['pagina'] ) ? absint( $oud['pagina'] ) : 0 );
                    $oud['link']   = array_key_exists( 'link', $in ) ? esc_url_raw( trim( (string) $in['link'] ) ) : ( isset( $oud['link'] ) ? $oud['link'] : '' );
                    // Het oude veld telt niet meer mee zodra het doel hier is gekozen.
                    if ( isset( $in['doel'] ) ) {
                        $oud['href'] = '';
                    }
                    $alle['sticky_cta'][ $type ] = $oud;
                }
                $bewaard[] = $sleutel;
                break;

            case 'coach_persoon':
                // Sinds 3.31.1: de site van de coach (Service-schema). Leeg is de norm.
                $url              = esc_url_raw( trim( (string) $ruw ), array( 'http', 'https' ) );
                $alle[ $sleutel ] = ( '' !== $url && function_exists( 'sfp_page_config_coach_norm' ) && untrailingslashit( $url ) === untrailingslashit( sfp_page_config_coach_norm() ) ) ? '' : $url;
                $bewaard[]        = $sleutel;
                break;

            case 'whatsapp_number':
                $alle[ $sleutel ] = preg_replace( '/[^0-9+ ]/', '', sanitize_text_field( (string) $ruw ) );
                $bewaard[]        = $sleutel;
                break;

            case 'whatsapp_message':
            case 'artikel_promo_label':
            case 'artikel_promo_titel':
            case 'artikel_promo_linktekst':
            case 'artikel_promo2_label':
            case 'artikel_promo2_titel':
            case 'artikel_promo2_linktekst':
                $alle[ $sleutel ] = sanitize_text_field( (string) $ruw );
                $bewaard[]        = $sleutel;
                break;

            case 'artikel_promo_tekst':
            case 'artikel_promo2_tekst':
            case 'affiliate_uitleg':
                $alle[ $sleutel ] = sanitize_textarea_field( (string) $ruw );
                $bewaard[]        = $sleutel;
                break;

            case 'affiliate_regel':
                $alle[ $sleutel ] = sanitize_text_field( (string) $ruw );
                $bewaard[]        = $sleutel;
                break;

            case 'words_per_minute':
                $getal            = absint( $ruw );
                $alle[ $sleutel ] = $getal ? max( 100, min( 500, $getal ) ) : '';
                $bewaard[]        = $sleutel;
                break;

            case 'artikel_voorkeursbron':
            case 'kop_leestekens':
            case 'ajax_lean_enabled':
                $alle[ $sleutel ] = ( true === $ruw || '1' === (string) $ruw || 'true' === $ruw ) ? '1' : '';
                $bewaard[]        = $sleutel;
                break;

            case 'artikel_factcheck_pagina':
            case 'artikel_redactie_pagina':
            case 'artikel_bibliografie_pagina':
            case 'artikel_promo_pagina':
            case 'artikel_promo2_pagina':
                $alle[ $sleutel ] = absint( $ruw ) ? (string) absint( $ruw ) : '';
                $bewaard[]        = $sleutel;
                break;

            case 'artikel_promo_beeld':
            case 'artikel_promo2_beeld':
                $alle[ $sleutel ] = absint( $ruw ) ? (string) absint( $ruw ) : '';
                $bewaard[]        = $sleutel;
                break;

            case 'artikel_factcheck_url':
            case 'artikel_redactie_url':
            case 'artikel_bibliografie_url':
            case 'artikel_promo_url':
            case 'artikel_promo2_url':
                $alle[ $sleutel ] = esc_url_raw( trim( (string) $ruw ) );
                $bewaard[]        = $sleutel;
                break;

            case 'artikel_promo_vorm':
            case 'artikel_promo2_vorm':
                $alle[ $sleutel ] = 'rond' === $ruw ? 'rond' : '';
                $bewaard[]        = $sleutel;
                break;

            case 'promo_vorm_artikel':
            case 'promo_vorm_pijler':
            case 'promo_vorm_begrip':
            case 'promo_vorm_tool':
                $alle[ $sleutel ] = in_array( $ruw, array( 'site', 'tweede', 'geen' ), true ) ? $ruw : '';
                $bewaard[]        = $sleutel;
                break;

            case 'publicaties_sites':
                // Kennisbanken voor [sfp_publicaties]: alleen https-adressen,
                // bewaard als basisadres per regel.
                $regels = is_array( $ruw ) ? $ruw : preg_split( '/\r\n|\r|\n|,/', (string) $ruw );
                $sites  = array();
                foreach ( $regels as $regel ) {
                    $regel = trim( (string) $regel );
                    if ( '' === $regel ) {
                        continue;
                    }
                    $host = (string) wp_parse_url( false === strpos( $regel, '://' ) ? 'https://' . $regel : $regel, PHP_URL_HOST );
                    if ( '' !== $host && preg_match( '/^[a-z0-9.-]+\.[a-z]{2,}$/i', $host ) ) {
                        $sites[] = 'https://' . strtolower( $host );
                    }
                }
                $alle[ $sleutel ] = implode( "\n", array_values( array_unique( $sites ) ) );
                $bewaard[]        = $sleutel;
                break;

            case 'affiliate_patronen':
                $regels           = is_array( $ruw ) ? $ruw : preg_split( '/\r\n|\r|\n/', (string) $ruw );
                $regels           = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', array_map( 'trim', array_map( 'strval', $regels ) ) ), 'strlen' ) ) );
                $alle[ $sleutel ] = implode( "\n", $regels );
                $bewaard[]        = $sleutel;
                break;

            case 'update_kanaal':
                $alle[ $sleutel ] = 'proef' === $ruw ? 'proef' : '';
                if ( class_exists( 'SFP_Page_Config_Updater' ) ) {
                    SFP_Page_Config_Updater::wis_cache();
                }
                $bewaard[] = $sleutel;
                break;

            case 'ajax_lean_skip':
                // Een lege lijst is een keuze: de versnelling slaat dan niets
                // over. De standaardlijst geldt alleen zolang er niets is bewaard.
                $alle[ $sleutel ] = function_exists( 'sfp_ajax_lean_sanitize_skip' ) ? sfp_ajax_lean_sanitize_skip( $ruw ) : array();
                $bewaard[]        = $sleutel;
                break;

            case 'cron_email':
            case 'tool_feedback_mail':
                $alle[ $sleutel ] = sanitize_email( (string) $ruw );
                $bewaard[]        = $sleutel;
                break;

            case 'banner':
                $alle['banner'] = function_exists( 'sfp_page_config_banner_schoon' ) ? sfp_page_config_banner_schoon( $ruw ) : array();
                $bewaard[]      = $sleutel;
                break;

            case 'uspstrook':
                $alle['uspstrook'] = function_exists( 'sfp_page_config_uspstrook_schoon' ) ? sfp_page_config_uspstrook_schoon( $ruw ) : array();
                $bewaard[]         = $sleutel;
                break;

            case 'contact':
                // Sinds 3.35.0: telefoon, e-mail en adres voor het blok Contactgegevens.
                $alle['contact'] = function_exists( 'sfp_page_config_contact_schoon' ) ? sfp_page_config_contact_schoon( $ruw ) : array();
                $bewaard[]       = $sleutel;
                break;

            case 'formulierregel':
                // Sinds 3.38.0: de formulieren met de regel Bel of mail eronder.
                $alle['formulierregel'] = function_exists( 'sfp_page_config_formulierregel_schoon' ) ? sfp_page_config_formulierregel_schoon( $ruw ) : array();
                $bewaard[]              = $sleutel;
                break;

            case 'knop_wachtlijst_anker':
                // Sinds 3.38.0: waar de knop onderaan in de wachtlijststand heen gaat.
                $anker                 = function_exists( 'sfp_page_config_anker_schoon' ) ? sfp_page_config_anker_schoon( (string) $ruw ) : '';
                $alle[ $sleutel ]      = function_exists( 'sfp_page_config_knop_wachtlijst_anker_norm' ) && sfp_page_config_knop_wachtlijst_anker_norm() === $anker ? '' : $anker;
                $bewaard[]             = $sleutel;
                break;

            case 'logostrook':
                // Sinds 3.37.0: label en logo's voor elke logostrook van de site.
                $alle['logostrook'] = function_exists( 'sfp_page_config_logostrook_schoon' ) ? sfp_page_config_logostrook_schoon( $ruw ) : array();
                $bewaard[]          = $sleutel;
                break;

            case 'netwerk':
                // Sinds 3.34.0: de labels voor het blok Netwerk.
                $alle['netwerk'] = function_exists( 'sfp_page_config_netwerk_schoon' ) ? sfp_page_config_netwerk_schoon( $ruw ) : array();
                $bewaard[]       = $sleutel;
                break;

            case 'inschrijf_formulier':
            case 'wachtlijst_formulier':
                // Een SureForms-formulier van deze site, of leeg.
                $id               = absint( $ruw );
                $alle[ $sleutel ] = $id && 'sureforms_form' === get_post_type( $id ) ? $id : '';
                $bewaard[]        = $sleutel;
                break;

            case 'cursus_tijd_default_start':
            case 'cursus_tijd_default_eind':
                $alle[ $sleutel ] = sfp_page_config_sanitize_time( $ruw );
                $bewaard[]        = $sleutel;
                break;

            case 'teksten':
                if ( ! is_array( $ruw ) ) {
                    break;
                }
                $bekend = sfp_page_config_teksten_register();
                foreach ( $ruw as $id => $tekst ) {
                    if ( ! isset( $bekend[ $id ] ) ) {
                        continue;
                    }
                    $tekst = ! empty( $bekend[ $id ]['lang'] ) ? sanitize_textarea_field( (string) $tekst ) : sanitize_text_field( (string) $tekst );
                    // Gelijk aan de standaard is hetzelfde als leeg: de site volgt de norm.
                    if ( $tekst === sfp_page_config_tekst_standaard( $id ) ) {
                        $tekst = '';
                    }
                    $alle = sfp_page_config_pad_schrijf( $alle, sfp_page_config_tekst_pad( $id ), $tekst );
                    if ( 'whatsapp_bericht' === $id ) {
                        // Het oude veld telt niet meer mee zodra de tekst hier is gekozen.
                        $alle['whatsapp_message'] = '';
                    }
                }
                $bewaard[] = $sleutel;
                break;

            case 'kleurrollen':
                if ( ! is_array( $ruw ) ) {
                    break;
                }
                $alle['kleurrollen'] = function_exists( 'sfp_page_config_kleurrollen_schoon' ) ? sfp_page_config_kleurrollen_schoon( $ruw ) : array();
                $bewaard[]           = $sleutel;
                break;

            case 'footer':
                $alle['footer'] = function_exists( 'sfp_page_config_footer_schoon' ) ? sfp_page_config_footer_schoon( $ruw ) : array();
                $bewaard[]      = $sleutel;
                break;

            case 'merkwaarden':
                // Alleen het slot per taal (3.23.0); de tekst zelf staat in de merkbron.
                if ( ! is_array( $ruw ) || ! isset( $ruw['slot'] ) ) {
                    break;
                }
                $oud = isset( $alle['merkwaarden'] ) && is_array( $alle['merkwaarden'] ) ? $alle['merkwaarden'] : array();
                $nieuw = function_exists( 'sfp_page_config_merkwaarden_slot_schoon' ) ? sfp_page_config_merkwaarden_slot_schoon( $ruw['slot'] ) : array();
                // Talen die niet zijn meegestuurd, blijven zoals ze waren.
                $slot = isset( $oud['slot'] ) && is_array( $oud['slot'] ) ? $oud['slot'] : array();
                foreach ( array( 'nl', 'en', 'pt' ) as $taal ) {
                    if ( is_array( $ruw['slot'] ) && array_key_exists( $taal, $ruw['slot'] ) ) {
                        if ( isset( $nieuw[ $taal ] ) ) {
                            $slot[ $taal ] = $nieuw[ $taal ];
                        } else {
                            unset( $slot[ $taal ] );
                        }
                    }
                }
                $oud['slot']         = $slot;
                $alle['merkwaarden'] = $oud;
                $bewaard[]           = $sleutel;
                break;
        }
    }

    if ( $bewaard ) {
        update_option( 'sfp_settings', $alle );
        delete_transient( 'sfp_pc_in_gebruik' );
        // Rechtstreeks: bij de eerste opslag op een site bestaat de optie nog
        // niet en gaat de actie update_option_sfp_settings niet af.
        delete_transient( 'sfp_pc_paginas' );
        if ( function_exists( 'sfp_ajax_lean_sync' ) && array_intersect( $bewaard, array( 'ajax_lean_enabled', 'ajax_lean_skip' ) ) ) {
            sfp_ajax_lean_sync();
        }
    }
    return $bewaard;
}

/**
 * Zet één instelling terug naar de netwerknorm: de bewaarde waarde vervalt.
 *
 * @param  string $pad Pad uit sfp_page_config_norm().
 * @return bool        True als er iets is teruggezet.
 */
function sfp_page_config_instelling_naar_norm( $pad ) {
    if ( preg_match( '/^berichttypes\.(begrippen|tool)$/', (string) $pad, $m ) ) {
        return function_exists( 'sfp_page_config_berichttype_naar_norm' ) && sfp_page_config_berichttype_naar_norm( $m[1] );
    }
    if ( preg_match( '/^zelftest\.([a-z0-9][a-z0-9-]{0,39})\.husselen$/', (string) $pad, $m ) ) {
        return function_exists( 'sfp_zelftest_instellingen_bewaar' ) && true === sfp_zelftest_instellingen_bewaar( $m[1], array( 'husselen' => '1' ) );
    }
    if ( ! array_key_exists( $pad, sfp_page_config_norm() ) ) {
        return false;
    }
    $alle = sfp_page_config_instellingen();
    if ( null === sfp_page_config_pad_lees( $alle, $pad ) ) {
        return false;
    }
    $alle = sfp_page_config_pad_schrijf( $alle, $pad, '' );
    if ( 'teksten.whatsapp_bericht' === $pad ) {
        $alle['whatsapp_message'] = '';
    }
    update_option( 'sfp_settings', $alle );
    if ( 'update_kanaal' === $pad && class_exists( 'SFP_Page_Config_Updater' ) ) {
        SFP_Page_Config_Updater::wis_cache();
    }
    return true;
}
