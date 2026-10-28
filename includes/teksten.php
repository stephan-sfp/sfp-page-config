<?php
/**
 * SFP Page Config - Vaste teksten voor bezoekers, per taal
 *
 * Teksten die de plugin zelf op de site zet (de knop onderaan, "Groep 1",
 * "Inhoudsopgave", de labels bij reviews) stonden tot en met 2.12.0 vast in
 * het Nederlands in de code, ook op de Engelse en de Braziliaanse site.
 *
 * Vanaf 3.0.0 heeft elke tekst een standaard in het Nederlands, Engels en
 * Braziliaans-Portugees. De taal volgt de sitetaal van WordPress. Een site kan
 * een tekst overschrijven op het scherm Teksten; leeg betekent de standaard.
 *
 * De Engelse en Portugese standaardteksten zijn een concept tot Stephan ze
 * heeft bevestigd. De labels van de artikelopmaak staan in
 * includes/artikel-teksten.php; een deel daarvan is hier ook aan te passen.
 *
 * @package SFP_Page_Config
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Taal van de site voor de vaste teksten: nl, en of pt.
 *
 * @return string
 */
function sfp_page_config_taal() {
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
 * Alle teksten die op het scherm Teksten staan.
 *
 * Per sleutel: de groep en het label in het beheer, de standaard per taal, of
 * het een lange tekst is, en waar de eigen tekst van de site staat ('opslag'
 * is een pad in sfp_settings; zonder 'opslag' is dat teksten.<sleutel>).
 * 'artikel' verwijst naar een sleutel in includes/artikel-teksten.php.
 *
 * @return array<string, array>
 */
function sfp_page_config_teksten_register() {
    static $register = null;
    if ( null !== $register ) {
        return $register;
    }

    $register = array(
        // Knop onderaan.
        'knop_coaching'      => array(
            'groep'  => 'Knop onderaan',
            'label'  => 'Coaching',
            'nl'     => 'Boek je gratis proefsessie',
            'en'     => 'Book a discovery call',
            'pt'     => 'Agende uma conversa',
            'opslag' => 'sticky_cta.coaching.text',
        ),
        'knop_training'      => array(
            'groep'  => 'Knop onderaan',
            'label'  => 'Training',
            'nl'     => 'Plan een kennismaking in',
            'en'     => 'Reserve your place',
            'pt'     => 'Reserve sua vaga',
            'opslag' => 'sticky_cta.training.text',
        ),
        'knop_incompany'     => array(
            'groep'  => 'Knop onderaan',
            'label'  => 'Incompany',
            // Netwerknorm sinds 3.0.0 (besluit 11-10-2026): gelijk aan wat de
            // sites al toonden.
            'nl'     => 'Plan je adviesgesprek',
            'en'     => 'Request a proposal',
            'pt'     => 'Peça uma proposta',
            'opslag' => 'sticky_cta.incompany.text',
        ),

        // Artikelen: een keuze uit de labels van de artikelopmaak.
        'artikel_samenvatting' => array(
            'groep'   => 'Artikelen',
            'label'   => 'Samenvatting',
            'artikel' => 'samenvatting',
        ),
        'artikel_inhoud'     => array(
            'groep'   => 'Artikelen',
            'label'   => 'Inhoudsopgave',
            'artikel' => 'inhoud',
        ),
        'artikel_naar_boven' => array(
            'groep'   => 'Artikelen',
            'label'   => 'Naar boven',
            'artikel' => 'naar_boven',
        ),
        'artikel_gecheckt'   => array(
            'groep'   => 'Artikelen',
            'label'   => 'Gecontroleerd',
            'artikel' => 'gecheckt',
        ),
        'artikel_redactiebeleid' => array(
            'groep'   => 'Artikelen',
            'label'   => 'Link naar de redactionele verantwoording',
            'artikel' => 'redactiebeleid',
        ),
        'affiliate_regel'    => array(
            'groep'  => 'Artikelen',
            'label'  => 'Affiliatelinks, korte regel',
            'nl'     => 'Bevat affiliate links',
            'en'     => '',
            'pt'     => '',
            'opslag' => 'affiliate_regel',
        ),
        'affiliate_uitleg'   => array(
            'groep'  => 'Artikelen',
            'label'  => 'Affiliatelinks, uitleg onder het artikel',
            'nl'     => 'In dit artikel verwijzen we soms naar boeken en hulpmiddelen via een affiliate link. Koop je via zo\'n link iets, dan ontvangen wij een kleine commissie. Jij betaalt niets extra. Zo kunnen we de kwaliteit van ons expertisecentrum waarborgen.',
            'en'     => '',
            'pt'     => '',
            'lang'   => true,
            'opslag' => 'affiliate_uitleg',
        ),

        // Leestijd en inhoudsopgave op pagina's zonder artikelopmaak.
        'leestijd_label'     => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Leestijd',
            'nl'    => 'LEESTIJD',
            'en'    => 'READING TIME',
            'pt'    => 'TEMPO DE LEITURA',
        ),
        'minuut'             => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Eén minuut',
            'nl'    => 'minuut',
            'en'    => 'minute',
            'pt'    => 'minuto',
        ),
        'minuten'            => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Meer minuten',
            'nl'    => 'minuten',
            'en'    => 'minutes',
            'pt'    => 'minutos',
        ),
        'inhoudsopgave'      => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Inhoudsopgave',
            'nl'    => 'Inhoudsopgave',
            'en'    => 'Contents',
            'pt'    => 'Conteúdo',
        ),
        'naar_boven'         => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Naar boven',
            'nl'    => 'Naar boven',
            'en'    => 'Back to top',
            'pt'    => 'Voltar ao topo',
        ),
        'vorig_hoofdstuk'    => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Vorig hoofdstuk',
            'nl'    => 'Vorig hoofdstuk',
            'en'    => 'Previous chapter',
            'pt'    => 'Capítulo anterior',
        ),
        'volgend_hoofdstuk'  => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Volgend hoofdstuk',
            'nl'    => 'Volgend hoofdstuk',
            'en'    => 'Next chapter',
            'pt'    => 'Próximo capítulo',
        ),

        // Reviews.
        'reviews_basis_een'  => array(
            'groep' => 'Reviews',
            'label' => 'Samenvatting bij één review',
            'nl'    => 'Op basis van %s review',
            'en'    => 'Based on %s review',
            'pt'    => 'Com base em %s avaliação',
        ),
        'reviews_basis'      => array(
            'groep' => 'Reviews',
            'label' => 'Samenvatting',
            'nl'    => 'Op basis van %s reviews',
            'en'    => 'Based on %s reviews',
            'pt'    => 'Com base em %s avaliações',
        ),
        'reviews_uitstekend' => array(
            'groep' => 'Reviews',
            'label' => 'Oordeel vanaf 4,5',
            'nl'    => 'Uitstekend',
            'en'    => 'Excellent',
            'pt'    => 'Excelente',
        ),
        'reviews_zeer_goed'  => array(
            'groep' => 'Reviews',
            'label' => 'Oordeel vanaf 4,0',
            'nl'    => 'Zeer goed',
            'en'    => 'Very good',
            'pt'    => 'Muito bom',
        ),
        'reviews_goed'       => array(
            'groep' => 'Reviews',
            'label' => 'Oordeel vanaf 3,5',
            'nl'    => 'Goed',
            'en'    => 'Good',
            'pt'    => 'Bom',
        ),
        'reviews_gemiddeld'  => array(
            'groep' => 'Reviews',
            'label' => 'Oordeel vanaf 3,0',
            'nl'    => 'Gemiddeld',
            'en'    => 'Average',
            'pt'    => 'Regular',
        ),
        'reviews_matig'      => array(
            'groep' => 'Reviews',
            'label' => 'Oordeel onder 3,0',
            'nl'    => 'Matig',
            'en'    => 'Fair',
            'pt'    => 'Fraco',
        ),
        'reviews_lees_verder' => array(
            'groep' => 'Reviews',
            'label' => 'Lange review openen',
            'nl'    => 'Lees verder',
            'en'    => 'Read more',
            'pt'    => 'Leia mais',
        ),

        'reviews_minder'     => array(
            'groep' => 'Reviews',
            'label' => 'Lange review sluiten',
            'nl'    => 'Minder tonen',
            'en'    => 'Show less',
            'pt'    => 'Mostrar menos',
        ),
        'reviews_geleden'    => array(
            'groep' => 'Reviews',
            'label' => 'Ouderdom van een eigen review',
            'nl'    => '%s geleden',
            'en'    => '%s ago',
            'pt'    => 'há %s',
        ),
        'reviews_vorige'     => array(
            'groep' => 'Reviews',
            'label' => 'Knop vorige, voor schermlezers',
            'nl'    => 'Vorige review',
            'en'    => 'Previous review',
            'pt'    => 'Avaliação anterior',
        ),
        'reviews_volgende'   => array(
            'groep' => 'Reviews',
            'label' => 'Knop volgende, voor schermlezers',
            'nl'    => 'Volgende review',
            'en'    => 'Next review',
            'pt'    => 'Próxima avaliação',
        ),
        'reviews_sterren'    => array(
            'groep' => 'Reviews',
            'label' => 'Sterren, voor schermlezers',
            'nl'    => '%d van de 5 sterren',
            'en'    => '%d out of 5 stars',
            'pt'    => '%d de 5 estrelas',
        ),

        // Logostrook (sinds 3.14.0).
        'logostrook_label'   => array(
            'groep' => 'Logostrook',
            'label' => 'Label boven de logo\'s, als het blok geen eigen label heeft',
            'nl'    => 'Vertrouwd door teams van',
            'en'    => 'Trusted by teams at',
            'pt'    => 'Com a confiança de equipes de',
        ),

        // Tooltips.
        'tooltip_toon'       => array(
            'groep' => 'Tooltips',
            'label' => 'Uitleg openen, voor schermlezers',
            'nl'    => 'Toon definitie van %s',
            'en'    => 'Show the explanation of %s',
            'pt'    => 'Mostrar a explicação de %s',
        ),

        // Formulieren en cursusdata.
        'groep'              => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Groep met nummer',
            'nl'    => 'Groep %d',
            'en'    => 'Group %d',
            'pt'    => 'Turma %d',
        ),
        'kies_groep'         => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Keuzelijst',
            'nl'    => 'Kies een groep',
            'en'    => 'Choose a group',
            'pt'    => 'Escolha uma turma',
        ),
        'geen_data'          => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Geen startmomenten',
            'nl'    => 'Neem contact op voor de eerstvolgende startdata.',
            'en'    => 'Get in touch for the next start dates.',
            'pt'    => 'Entre em contato para saber as próximas datas.',
        ),
        'wachtlijst_tekst'   => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Geen nieuwe groep, met wachtlijst (%s is de link)',
            'nl'    => 'Er staat nog geen nieuwe groep gepland. Wil je deze training volgen? %s en je hoort het als eerste wanneer de volgende groep van start gaat!',
            'en'    => 'No new group has been scheduled yet. Keen to join this training? %s and you will be the first to hear when the next group starts!',
            'pt'    => 'Ainda não há nova turma agendada. Quer fazer este treinamento? %s e seja o primeiro a saber quando a próxima turma começar!',
        ),
        'wachtlijst_link'    => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Link naar het wachtlijstformulier',
            'nl'    => 'Zet jezelf op de wachtlijst',
            'en'    => 'Join the waiting list',
            'pt'    => 'Entre na lista de espera',
        ),
        'tijd_vanaf'         => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Lestijd met alleen een begin',
            'nl'    => 'vanaf %s',
            'en'    => 'from %s',
            'pt'    => 'a partir das %s',
        ),
        'tijd_tot'           => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Lestijd met alleen een einde',
            'nl'    => 'tot %s',
            'en'    => 'until %s',
            'pt'    => 'até %s',
        ),
        'tijd_uur'           => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Achter een lestijd',
            'nl'    => 'uur',
            'en'    => '',
            'pt'    => '',
        ),

        // WhatsApp.
        'whatsapp_bericht'   => array(
            'groep' => 'WhatsApp',
            'label' => 'Eerste bericht',
            'nl'    => 'Hoi, ik heb een vraag',
            'en'    => 'Hi, I have a question',
            'pt'    => 'Olá, tenho uma dúvida',
        ),

        // Zelftest: de testpagina (sinds 3.5.0).
        'zelftest_sluiten'   => array(
            'groep' => 'Zelftest',
            'label' => 'Kruisje op de testpagina (voor schermlezers)',
            'nl'    => 'Sluiten',
            'en'    => 'Close',
            'pt'    => 'Fechar',
        ),
        'zelftest_stop'      => array(
            'groep' => 'Zelftest',
            'label' => 'Vraag bij het kruisje tijdens de test',
            'nl'    => 'Weet je zeker dat je stopt? Je antwoorden worden niet bewaard.',
            'en'    => 'Are you sure you want to stop? Your answers will not be saved.',
            'pt'    => 'Tem certeza de que quer parar? Suas respostas não serão salvas.',
        ),
        'zelftest_stop_ja'   => array(
            'groep' => 'Zelftest',
            'label' => 'Knop: stoppen',
            'nl'    => 'Stoppen',
            'en'    => 'Stop',
            'pt'    => 'Parar',
        ),
        'zelftest_stop_nee'  => array(
            'groep' => 'Zelftest',
            'label' => 'Knop: verder met de test',
            'nl'    => 'Verder met de test',
            'en'    => 'Continue the test',
            'pt'    => 'Continuar o teste',
        ),
    );

    return $register;
}

/**
 * De standaardtekst in de taal van de site.
 *
 * @param  string      $sleutel Sleutel uit het register.
 * @param  string|null $taal    nl, en of pt; null voor de taal van de site.
 * @return string
 */
function sfp_page_config_tekst_standaard( $sleutel, $taal = null ) {
    $register = sfp_page_config_teksten_register();
    if ( ! isset( $register[ $sleutel ] ) ) {
        return '';
    }
    $taal  = null === $taal ? sfp_page_config_taal() : $taal;
    $regel = $register[ $sleutel ];
    if ( isset( $regel['artikel'] ) ) {
        $alle = function_exists( 'sfp_page_config_artikel_teksten' ) ? sfp_page_config_artikel_teksten() : array();
        if ( isset( $alle[ $taal ][ $regel['artikel'] ] ) ) {
            return (string) $alle[ $taal ][ $regel['artikel'] ];
        }
        return isset( $alle['en'][ $regel['artikel'] ] ) ? (string) $alle['en'][ $regel['artikel'] ] : '';
    }
    return isset( $regel[ $taal ] ) ? (string) $regel[ $taal ] : '';
}

/**
 * Waar de eigen tekst van de site staat, als pad in sfp_settings.
 *
 * @param  string $sleutel Sleutel uit het register.
 * @return string
 */
function sfp_page_config_tekst_pad( $sleutel ) {
    $register = sfp_page_config_teksten_register();
    return isset( $register[ $sleutel ]['opslag'] ) ? $register[ $sleutel ]['opslag'] : 'teksten.' . $sleutel;
}

/**
 * De eigen tekst van de site, of '' als de site de standaard volgt.
 *
 * @param  string $sleutel Sleutel uit het register.
 * @return string
 */
function sfp_page_config_tekst_eigen( $sleutel ) {
    $waarde = sfp_page_config_pad_lees( sfp_page_config_instellingen(), sfp_page_config_tekst_pad( $sleutel ) );
    return is_scalar( $waarde ) ? trim( (string) $waarde ) : '';
}

/**
 * Eén tekst voor de bezoeker: de eigen tekst van de site, anders de standaard.
 *
 * @param  string $sleutel Sleutel uit het register.
 * @return string
 */
function sfp_page_config_tekst( $sleutel ) {
    $eigen = sfp_page_config_tekst_eigen( $sleutel );
    if ( '' !== $eigen ) {
        return $eigen;
    }
    if ( 'whatsapp_bericht' === $sleutel ) {
        // Tot en met 2.12.0 stond het bericht in een eigen instelling.
        $oud = trim( (string) sfp_page_config_get_setting( 'whatsapp_message', '' ) );
        if ( '' !== $oud ) {
            return $oud;
        }
    }
    return sfp_page_config_tekst_standaard( $sleutel );
}

/**
 * Een tekst met ingevulde waarden. Een eigen tekst waarin het invulteken
 * ontbreekt of kapot is, valt terug op de standaard in plaats van een fout.
 *
 * @param  string $sleutel Sleutel uit het register.
 * @param  mixed  ...$args Waarden voor de invultekens.
 * @return string
 */
function sfp_page_config_tekst_f( $sleutel, ...$args ) {
    $tekst = sfp_page_config_tekst( $sleutel );
    try {
        return vsprintf( $tekst, $args );
    } catch ( \Throwable $e ) {
        try {
            return vsprintf( sfp_page_config_tekst_standaard( $sleutel ), $args );
        } catch ( \Throwable $e2 ) {
            return $tekst;
        }
    }
}

add_filter( 'sfp_page_config_artikel_tekst', 'sfp_page_config_teksten_artikel_filter', 5, 2 );

/**
 * Een label van de artikelopmaak dat op het scherm Teksten is aangepast.
 *
 * @param  string $waarde  De standaardtekst.
 * @param  string $sleutel Sleutel in includes/artikel-teksten.php.
 * @return string
 */
function sfp_page_config_teksten_artikel_filter( $waarde, $sleutel ) {
    static $kaart = null;
    if ( null === $kaart ) {
        $kaart = array();
        foreach ( sfp_page_config_teksten_register() as $id => $regel ) {
            if ( isset( $regel['artikel'] ) ) {
                $kaart[ $regel['artikel'] ] = $id;
            }
        }
    }
    if ( isset( $kaart[ $sleutel ] ) ) {
        $eigen = sfp_page_config_tekst_eigen( $kaart[ $sleutel ] );
        if ( '' !== $eigen ) {
            return $eigen;
        }
    }
    return $waarde;
}

/**
 * Een datum in de taal van de site.
 *
 * Nederlands gebruikt de eigen namen van de plugin (onafhankelijk van een
 * taalpakket); andere talen volgen WordPress.
 *
 * @param  string $date_string Datum, bijvoorbeeld 2026-10-11.
 * @param  string $format      short, long of een PHP-datumformaat.
 * @return string
 */
function sfp_page_config_format_datum( $date_string, $format = 'short' ) {
    if ( 'nl' === sfp_page_config_taal() ) {
        return sfp_page_config_format_date_nl( $date_string, $format );
    }
    $ts = strtotime( (string) $date_string );
    if ( ! $ts ) {
        return '';
    }
    if ( 'short' === $format ) {
        $format = 'D j M';
    } elseif ( 'long' === $format ) {
        $format = 'l j F Y';
    }
    $uit = wp_date( $format, $ts );
    return $uit ? (string) $uit : '';
}

/**
 * Een lestijd in de taal van de site.
 *
 * @param  string $start     "HH:MM" of leeg.
 * @param  string $eind      "HH:MM" of leeg.
 * @param  string $separator Tussen begin en einde.
 * @param  string $suffix    Achter de tijd; null voor de tekst van de site.
 * @return string
 */
function sfp_page_config_format_lestijd( $start, $eind, $separator = ' - ', $suffix = null ) {
    $start = sfp_page_config_sanitize_time( $start );
    $eind  = sfp_page_config_sanitize_time( $eind );
    if ( null === $suffix ) {
        $uur    = sfp_page_config_tekst( 'tijd_uur' );
        $suffix = '' !== $uur ? ' ' . $uur : '';
    }
    if ( '' !== $start && '' !== $eind ) {
        $body = $start . $separator . $eind;
    } elseif ( '' !== $start ) {
        $body = sfp_page_config_tekst_f( 'tijd_vanaf', $start );
    } elseif ( '' !== $eind ) {
        $body = sfp_page_config_tekst_f( 'tijd_tot', $eind );
    } else {
        return '';
    }
    return $body . $suffix;
}

/**
 * Namen van de berichttypes begrippen en tool in de taal van de site, voor
 * een site waar de plugin het berichttype registreert zonder dat er iets is
 * overgenomen (sinds 3.15.0). Gelijk aan wat de snippets per taal zetten.
 * Geen tekst op het scherm Teksten: de namen in het beheer zijn een besluit
 * per netwerk, niet per site.
 *
 * @param  string      $type begrippen of tool.
 * @param  string|null $taal nl, en of pt; null voor de taal van de site.
 * @return array{meervoud: string, enkelvoud: string}
 */
function sfp_page_config_berichttype_namen( $type, $taal = null ) {
    $taal  = null === $taal ? sfp_page_config_taal() : $taal;
    $namen = array(
        'begrippen' => array(
            'nl' => array( 'meervoud' => 'Begrippen', 'enkelvoud' => 'Begrip' ),
            'en' => array( 'meervoud' => 'Glossary', 'enkelvoud' => 'Term' ),
            'pt' => array( 'meervoud' => 'Glossario', 'enkelvoud' => 'Termo' ),
        ),
        'tool'      => array(
            'nl' => array( 'meervoud' => 'Tools', 'enkelvoud' => 'Tool' ),
            'en' => array( 'meervoud' => 'Tools', 'enkelvoud' => 'Tool' ),
            'pt' => array( 'meervoud' => 'Ferramentas', 'enkelvoud' => 'Ferramenta' ),
        ),
    );
    $type = isset( $namen[ $type ] ) ? $type : 'tool';
    return $namen[ $type ][ $taal ] ?? $namen[ $type ]['nl'];
}
