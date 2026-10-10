<?php
/**
 * SFP Page Config - Artikelopmaak: vaste teksten per taal
 *
 * De labels van de artikelopmaak (kopkaart, blokken, inhoudsopgave) in de
 * taal van de site: Nederlands, Engels of Braziliaans-Portugees. De taal
 * volgt de sitetaal van WordPress. Geen vertaalbestanden: de lijst is kort
 * en hoort bij de opmaak.
 *
 * Engels komt uit de goedgekeurde dummy "SwS artikelopmaak" (v35).
 * Portugees is een concept en is nog niet door Stephan bevestigd.
 *
 * Een site kan een tekst overschrijven met het filter
 * `sfp_page_config_artikel_tekst` (waarde, sleutel, taal).
 *
 * @package SFP_Page_Config
 * @since   2.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Taal van de artikelopmaak: nl, en of pt.
 *
 * @return string
 */
function sfp_page_config_artikel_taal() {
    $locale = strtolower( (string) get_locale() );
    if ( 0 === strpos( $locale, 'nl' ) ) {
        return 'nl';
    }
    if ( 0 === strpos( $locale, 'pt' ) ) {
        return 'pt';
    }
    return 'en';
}

/**
 * Alle teksten.
 *
 * @return array<string, array<string, string>>
 */
function sfp_page_config_artikel_teksten() {
    return array(
        'en' => array(
            'onderdeel'        => 'Part of %s',
            'onderdeel_in'     => 'Part of %1$s in %2$s',
            'door'             => 'By %s',
            'gepubliceerd'     => 'Published %s',
            'bijgewerkt'       => 'Updated %s',
            'leestijd'         => '%d min read',
            'gecheckt'         => 'Fact-checked',
            'delen'            => 'Share',
            'favoriet'         => 'Favourite on Google',
            'favoriet_kort'    => 'Favourite',
            'warm'             => 'Warm-up: %d questions',
            'warm_kort'        => 'Warm-up',
            'bronnen'          => 'Sources (%d)',
            'link_kopieren'    => 'Copy link',
            'link_gekopieerd'  => 'Link copied',
            'email'            => 'Email',
            'vraag_van'        => 'Question %1$s of %2$d',
            'volgende_vraag'   => 'Next question',
            'start_lezen'      => 'Start reading',
            'antwoord_in'      => 'The answer is in %s.',
            'samenvatting'     => 'What you need to know',
            'deel_samenvatting' => 'Share this summary',
            'dingen'           => '%d things to know about',
            'lees_ook'         => 'Read also',
            'inzicht'          => 'Insight',
            'deel_inzicht'     => 'Share this insight',
            'deel_op'          => 'Share on %s',
            'download'         => 'Download image',
            'kader_wist-je-dat'    => 'Did you know',
            'kader_kanttekening'   => 'Caveat',
            'kader_grondslag'      => 'The basis',
            'kader_reflectievraag' => 'Ask yourself',
            'kader_checklist'      => 'Checklist',
            'auteur'           => 'Author',
            'meer_over'        => 'More about %s',
            'onderbouwd'       => 'Fact-checked and sourced',
            'onderbouwd_zin'   => 'Every claim in this article is checked against its source.',
            'redactiebeleid'   => 'Editorial policy',
            'bibliografie'     => 'Bibliography',
            'onderwerpen'      => 'More about',
            'inhoud'           => 'Contents',
            'minuten'          => '%d min',
            'hoofdstukken'     => 'Chapters',
            'vervolg'          => 'Next steps',
            'deelbeeld_alt'    => 'Shareable image: %s',
            'zij_label'        => 'Contents and more',
            'niet'             => 'Do not do this',
            'wel'              => 'Do this',
        ),
        'nl' => array(
            'onderdeel'        => 'Onderdeel van %s',
            'onderdeel_in'     => 'Onderdeel van %1$s in %2$s',
            'door'             => 'Door %s',
            'gepubliceerd'     => 'Gepubliceerd %s',
            'bijgewerkt'       => 'Bijgewerkt %s',
            'leestijd'         => '%d min leestijd',
            'gecheckt'         => 'Gefactcheckt',
            'delen'            => 'Delen',
            'favoriet'         => 'Voorkeursbron op Google',
            'favoriet_kort'    => 'Voorkeursbron',
            'warm'             => 'Warming-up: %d vragen',
            'warm_kort'        => 'Warming-up',
            'bronnen'          => 'Bronnen (%d)',
            'link_kopieren'    => 'Link kopiëren',
            'link_gekopieerd'  => 'Link gekopieerd',
            'email'            => 'E-mail',
            'vraag_van'        => 'Vraag %1$s van %2$d',
            'volgende_vraag'   => 'Volgende vraag',
            'start_lezen'      => 'Begin met lezen',
            'antwoord_in'      => 'Het antwoord staat in %s.',
            'samenvatting'     => 'Wat je moet weten',
            'deel_samenvatting' => 'Deel deze samenvatting',
            'dingen'           => '%d dingen om te weten over',
            'lees_ook'         => 'Lees ook',
            'inzicht'          => 'Inzicht',
            'deel_inzicht'     => 'Deel dit inzicht',
            'deel_op'          => 'Deel op %s',
            'download'         => 'Afbeelding downloaden',
            'kader_wist-je-dat'    => 'Wist je dat',
            'kader_kanttekening'   => 'Kanttekening',
            'kader_grondslag'      => 'Grondslag',
            'kader_reflectievraag' => 'Ga eens bij jezelf na',
            'kader_checklist'      => 'Checklist',
            'auteur'           => 'Auteur',
            'meer_over'        => 'Meer over %s',
            'onderbouwd'       => 'Gefactcheckt en onderbouwd',
            'onderbouwd_zin'   => 'Elke bewering in dit artikel is gecontroleerd aan de hand van de bron.',
            'redactiebeleid'   => 'Redactionele verantwoording',
            'bibliografie'     => 'Bibliografie',
            'onderwerpen'      => 'Meer over',
            'inhoud'           => 'Inhoud',
            'minuten'          => '%d min',
            'hoofdstukken'     => 'Hoofdstukken',
            'vervolg'          => 'Vervolgstappen',
            'deelbeeld_alt'    => 'Deelbaar beeld: %s',
            'zij_label'        => 'Inhoud en meer',
            'niet'             => 'Niet doen',
            'wel'              => 'Wel doen',
        ),
        // Concept: nog niet door Stephan bevestigd.
        'pt' => array(
            'onderdeel'        => 'Parte de %s',
            'onderdeel_in'     => 'Parte de %1$s em %2$s',
            'door'             => 'Por %s',
            'gepubliceerd'     => 'Publicado em %s',
            'bijgewerkt'       => 'Atualizado em %s',
            'leestijd'         => '%d min de leitura',
            'gecheckt'         => 'Checado',
            'delen'            => 'Compartilhar',
            'favoriet'         => 'Fonte preferida no Google',
            'favoriet_kort'    => 'Fonte preferida',
            'warm'             => 'Aquecimento: %d perguntas',
            'warm_kort'        => 'Aquecimento',
            'bronnen'          => 'Fontes (%d)',
            'link_kopieren'    => 'Copiar link',
            'link_gekopieerd'  => 'Link copiado',
            'email'            => 'E-mail',
            'vraag_van'        => 'Pergunta %1$s de %2$d',
            'volgende_vraag'   => 'Próxima pergunta',
            'start_lezen'      => 'Começar a ler',
            'antwoord_in'      => 'A resposta está em %s.',
            'samenvatting'     => 'O que você precisa saber',
            'deel_samenvatting' => 'Compartilhe este resumo',
            'dingen'           => '%d coisas para saber sobre',
            'lees_ook'         => 'Leia também',
            'inzicht'          => 'Insight',
            'deel_inzicht'     => 'Compartilhe este insight',
            'deel_op'          => 'Compartilhar no %s',
            'download'         => 'Baixar imagem',
            'kader_wist-je-dat'    => 'Você sabia',
            'kader_kanttekening'   => 'Ressalva',
            'kader_grondslag'      => 'Fundamento',
            'kader_reflectievraag' => 'Pergunte a si mesmo',
            'kader_checklist'      => 'Checklist',
            'auteur'           => 'Autor',
            'meer_over'        => 'Mais sobre %s',
            'onderbouwd'       => 'Checado e com fontes',
            'onderbouwd_zin'   => 'Cada afirmação deste artigo foi conferida com a fonte.',
            'redactiebeleid'   => 'Política editorial',
            'bibliografie'     => 'Bibliografia',
            'onderwerpen'      => 'Mais sobre',
            'inhoud'           => 'Conteúdo',
            'minuten'          => '%d min',
            'hoofdstukken'     => 'Capítulos',
            'vervolg'          => 'Próximos passos',
            'deelbeeld_alt'    => 'Imagem para compartilhar: %s',
            'zij_label'        => 'Conteúdo e mais',
            'niet'             => 'Não faça',
            'wel'              => 'Faça',
        ),
    );
}

/**
 * Eén tekst in de taal van de site, met Engels als terugval.
 *
 * @param  string $sleutel Sleutel uit de lijst.
 * @return string
 */
function sfp_page_config_artikel_tekst( $sleutel ) {
    static $teksten = null;
    if ( null === $teksten ) {
        $teksten = sfp_page_config_artikel_teksten();
    }
    $taal   = sfp_page_config_artikel_taal();
    $waarde = isset( $teksten[ $taal ][ $sleutel ] ) ? $teksten[ $taal ][ $sleutel ] : ( isset( $teksten['en'][ $sleutel ] ) ? $teksten['en'][ $sleutel ] : '' );

    /**
     * Overschrijf een tekst van de artikelopmaak.
     *
     * @param string $waarde  De tekst.
     * @param string $sleutel De sleutel.
     * @param string $taal    nl, en of pt.
     */
    return (string) apply_filters( 'sfp_page_config_artikel_tekst', $waarde, $sleutel, $taal );
}

/**
 * Iconen van de artikelopmaak, als inline SVG. De platformiconen dragen
 * de kleur van het platform zelf, zoals in de goedgekeurde dummy; alle
 * andere volgen de tekstkleur.
 *
 * @param  string $naam Naam van het icoon.
 * @return string
 */
function sfp_page_config_artikel_icoon( $naam ) {
    $iconen = array(
        'delen'     => '<svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="12" cy="3.5" r="2" fill="none" stroke="currentColor" stroke-width="1.5"/><circle cx="4" cy="8" r="2" fill="none" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="12.5" r="2" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M5.8 7l4.4-2.5M5.8 9l4.4 2.5" stroke="currentColor" stroke-width="1.5"/></svg>',
        'ster'      => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.8l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 11.6l-3.8 2 .7-4.3-3.1-3 4.3-.6z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>',
        'vraag'     => '<svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="6.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M6.3 6.2a1.8 1.8 0 113 1.4c-.7.4-1.3.8-1.3 1.6M8 11.2v.1" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'bron'      => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 2.5h10v11H3z M5.5 5.5h5 M5.5 8h5 M5.5 10.5h3" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>',
        'vink'      => '<svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="8" fill="currentColor"/><path d="M4.5 8.2l2.3 2.3 4.7-5" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'label'     => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2 2.5h5.5l6.5 6.5-5 5-6.5-6.5z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="5.2" cy="5.7" r="1.1" fill="currentColor"/></svg>',
        'linkedin'  => '<svg viewBox="0 0 24 24" aria-hidden="true" style="color:#0A66C2"><rect x="2" y="2" width="20" height="20" rx="3" fill="currentColor"/><rect x="5.5" y="9.5" width="3" height="9" fill="#fff"/><circle cx="7" cy="6.6" r="1.7" fill="#fff"/><path d="M11 9.5h2.9v1.3c.5-.9 1.6-1.5 2.9-1.5 2.4 0 3.2 1.5 3.2 3.9v5.3h-3v-4.7c0-1.1-.2-2-1.4-2s-1.6.9-1.6 2v4.7h-3z" fill="#fff"/></svg>',
        'whatsapp'  => '<svg viewBox="0 0 24 24" aria-hidden="true" style="color:#25D366"><path d="M12 2.2a9.8 9.8 0 00-8.5 14.7L2.2 21.8l5-1.3A9.8 9.8 0 1012 2.2z" fill="currentColor"/><path d="M9 7.3c-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3.2 5 4.4 2.5 1 3 .8 3.5.7.5-.1 1.7-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.4l-1.8-.9c-.3-.1-.5-.1-.7.2l-.8 1c-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.5-.6c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5z" fill="#fff"/></svg>',
        'x'         => '<svg viewBox="0 0 24 24" aria-hidden="true" style="color:#000"><path d="M3 3h5.2l4.4 6.1L17.9 3h2.7l-6.8 7.7L21 21h-5.2l-4.7-6.5L5.3 21H2.6l7.3-8.2z" fill="currentColor"/></svg>',
        'facebook'  => '<svg viewBox="0 0 24 24" aria-hidden="true" style="color:#1877F2"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="M13.2 21.9v-7.1h2.4l.4-2.8h-2.8v-1.8c0-.8.2-1.4 1.4-1.4h1.5V6.3c-.3 0-1.1-.1-2.2-.1-2.2 0-3.6 1.3-3.6 3.7V12H7.9v2.8h2.4v7z" fill="#fff"/></svg>',
        'mail'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2.5" y="5" width="19" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3 6.5l9 6.5 9-6.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
        'link'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 14l4-4M10.5 6.5l1.8-1.8a4 4 0 015.7 5.7L16.2 12M13.5 17.5l-1.8 1.8a4 4 0 01-5.7-5.7L7.8 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'download'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v11M7 10.5l5 5 5-5M5 19.5h14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'omlaag'    => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 6l5 5 5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    );
    return isset( $iconen[ $naam ] ) ? $iconen[ $naam ] : '';
}

/**
 * Deelknoppen voor een URL, als lijst van [sleutel, naam, href].
 *
 * @param  string $url   De te delen URL.
 * @param  string $titel Titel voor de deeltekst.
 * @return array<int, array{0: string, 1: string, 2: string}>
 */
function sfp_page_config_artikel_deelkanalen( $url, $titel ) {
    $u = rawurlencode( $url );
    $t = rawurlencode( $titel );
    return array(
        array( 'linkedin', 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u ),
        array( 'whatsapp', 'WhatsApp', 'https://wa.me/?text=' . $t . '%20' . $u ),
        array( 'x', 'X', 'https://twitter.com/intent/tweet?url=' . $u . '&text=' . $t ),
        array( 'facebook', 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $u ),
    );
}
