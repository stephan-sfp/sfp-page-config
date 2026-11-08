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
        'knop_coaching'               => array(
            'groep'  => 'Knop onderaan',
            'label'  => 'Coaching',
            // Sinds 3.31.0 (besluit Stephan 7-11, NL-vorm coachpagina).
            'nl'     => 'Plan je proefsessie',
            'en'     => 'Book a discovery call',
            'pt'     => 'Agende uma conversa',
            'opslag' => 'sticky_cta.coaching.text',
        ),
        'knop_training'               => array(
            'groep'  => 'Knop onderaan',
            'label'  => 'Training',
            'nl'     => 'Plan een kennismaking in',
            'en'     => 'Reserve your place',
            'pt'     => 'Reserve sua vaga',
            'opslag' => 'sticky_cta.training.text',
        ),
        'knop_home'                   => array(
            'groep'  => 'Knop onderaan',
            'label'  => 'Home',
            // Sinds 3.34.0. Per pagina te kiezen (bijvoorbeeld de zelftest).
            'nl'     => 'Plan een kennismaking',
            'en'     => 'Book an introduction',
            'pt'     => 'Agende uma conversa',
            'opslag' => 'sticky_cta.home.text',
        ),
        'knop_incompany'              => array(
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
        // Begrip (3.22.0): het label van het blok "In een presentatie"
        // past niet op elke site (DST, CVD); per site aan te passen.
        'artikel_in_een_talk'         => array(
            'groep'   => 'Artikelen',
            'label'   => 'Label van het blok "In een presentatie" bij een begrip',
            'artikel' => 'in_een_talk',
        ),
        'artikel_samenvatting'        => array(
            'groep'   => 'Artikelen',
            'label'   => 'Samenvatting',
            'artikel' => 'samenvatting',
        ),
        'artikel_inhoud'              => array(
            'groep'   => 'Artikelen',
            'label'   => 'Inhoudsopgave',
            'artikel' => 'inhoud',
        ),
        'artikel_naar_boven'          => array(
            'groep'   => 'Artikelen',
            'label'   => 'Naar boven',
            'artikel' => 'naar_boven',
        ),
        'artikel_gecheckt'            => array(
            'groep'   => 'Artikelen',
            'label'   => 'Gecontroleerd',
            'artikel' => 'gecheckt',
        ),
        'artikel_redactiebeleid'      => array(
            'groep'   => 'Artikelen',
            'label'   => 'Link naar de redactionele verantwoording',
            'artikel' => 'redactiebeleid',
        ),
        'affiliate_regel'             => array(
            'groep'  => 'Artikelen',
            'label'  => 'Affiliatelinks, korte regel',
            'nl'     => 'Bevat affiliate links',
            'en'     => '',
            'pt'     => '',
            'opslag' => 'affiliate_regel',
        ),
        'affiliate_uitleg'            => array(
            'groep'  => 'Artikelen',
            'label'  => 'Affiliatelinks, uitleg onder het artikel',
            'nl'     => 'In dit artikel verwijzen we soms naar boeken en hulpmiddelen via een affiliate link. Koop je via zo\'n link iets, dan ontvangen wij een kleine commissie. Jij betaalt niets extra. Zo kunnen we de kwaliteit van ons expertisecentrum waarborgen.',
            'en'     => '',
            'pt'     => '',
            'lang'   => true,
            'opslag' => 'affiliate_uitleg',
        ),

        // Leestijd en inhoudsopgave op pagina's zonder artikelopmaak.
        'leestijd_label'              => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Leestijd',
            'nl'    => 'LEESTIJD',
            'en'    => 'READING TIME',
            'pt'    => 'TEMPO DE LEITURA',
        ),
        'minuut'                      => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Eén minuut',
            'nl'    => 'minuut',
            'en'    => 'minute',
            'pt'    => 'minuto',
        ),
        'minuten'                     => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Meer minuten',
            'nl'    => 'minuten',
            'en'    => 'minutes',
            'pt'    => 'minutos',
        ),
        'inhoudsopgave'               => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Inhoudsopgave',
            'nl'    => 'Inhoudsopgave',
            'en'    => 'Contents',
            'pt'    => 'Conteúdo',
        ),
        'naar_boven'                  => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Naar boven',
            'nl'    => 'Naar boven',
            'en'    => 'Back to top',
            'pt'    => 'Voltar ao topo',
        ),
        'vorig_hoofdstuk'             => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Vorig hoofdstuk',
            'nl'    => 'Vorig hoofdstuk',
            'en'    => 'Previous chapter',
            'pt'    => 'Capítulo anterior',
        ),
        'volgend_hoofdstuk'           => array(
            'groep' => 'Leestijd en inhoudsopgave',
            'label' => 'Volgend hoofdstuk',
            'nl'    => 'Volgend hoofdstuk',
            'en'    => 'Next chapter',
            'pt'    => 'Próximo capítulo',
        ),

        // Reviews.
        'reviews_basis_een'           => array(
            'groep' => 'Reviews',
            'label' => 'Samenvatting bij één review',
            'nl'    => 'Op basis van %s review',
            'en'    => 'Based on %s review',
            'pt'    => 'Com base em %s avaliação',
        ),
        'reviews_basis'               => array(
            'groep' => 'Reviews',
            'label' => 'Samenvatting',
            'nl'    => 'Op basis van %s reviews',
            'en'    => 'Based on %s reviews',
            'pt'    => 'Com base em %s avaliações',
        ),
        'reviews_uitstekend'          => array(
            'groep' => 'Reviews',
            'label' => 'Oordeel vanaf 4,5',
            'nl'    => 'Uitstekend',
            'en'    => 'Excellent',
            'pt'    => 'Excelente',
        ),
        'reviews_zeer_goed'           => array(
            'groep' => 'Reviews',
            'label' => 'Oordeel vanaf 4,0',
            'nl'    => 'Zeer goed',
            'en'    => 'Very good',
            'pt'    => 'Muito bom',
        ),
        'reviews_goed'                => array(
            'groep' => 'Reviews',
            'label' => 'Oordeel vanaf 3,5',
            'nl'    => 'Goed',
            'en'    => 'Good',
            'pt'    => 'Bom',
        ),
        'reviews_gemiddeld'           => array(
            'groep' => 'Reviews',
            'label' => 'Oordeel vanaf 3,0',
            'nl'    => 'Gemiddeld',
            'en'    => 'Average',
            'pt'    => 'Regular',
        ),
        'reviews_matig'               => array(
            'groep' => 'Reviews',
            'label' => 'Oordeel onder 3,0',
            'nl'    => 'Matig',
            'en'    => 'Fair',
            'pt'    => 'Fraco',
        ),
        'reviews_lees_verder'         => array(
            'groep' => 'Reviews',
            'label' => 'Lange review openen',
            'nl'    => 'Lees verder',
            'en'    => 'Read more',
            'pt'    => 'Leia mais',
        ),

        'reviews_minder'              => array(
            'groep' => 'Reviews',
            'label' => 'Lange review sluiten',
            'nl'    => 'Minder tonen',
            'en'    => 'Show less',
            'pt'    => 'Mostrar menos',
        ),
        'reviews_geleden'             => array(
            'groep' => 'Reviews',
            'label' => 'Ouderdom van een eigen review',
            'nl'    => '%s geleden',
            'en'    => '%s ago',
            'pt'    => 'há %s',
        ),
        'reviews_vorige'              => array(
            'groep' => 'Reviews',
            'label' => 'Knop vorige, voor schermlezers',
            'nl'    => 'Vorige review',
            'en'    => 'Previous review',
            'pt'    => 'Avaliação anterior',
        ),
        'reviews_volgende'            => array(
            'groep' => 'Reviews',
            'label' => 'Knop volgende, voor schermlezers',
            'nl'    => 'Volgende review',
            'en'    => 'Next review',
            'pt'    => 'Próxima avaliação',
        ),
        'reviews_sterren'             => array(
            'groep' => 'Reviews',
            'label' => 'Sterren, voor schermlezers',
            'nl'    => '%d van de 5 sterren',
            'en'    => '%d out of 5 stars',
            'pt'    => '%d de 5 estrelas',
        ),

        // Logostrook (sinds 3.14.0).
        'logostrook_label'            => array(
            'groep' => 'Logostrook',
            'label' => 'Label boven de logo\'s, als het blok geen eigen label heeft',
            'nl'    => 'Vertrouwd door teams van',
            'en'    => 'Trusted by teams at',
            'pt'    => 'Com a confiança de equipes de',
        ),

        // Kopieerblok (sinds 3.20.0).
        'kopieer_knop'                => array(
            'groep' => 'Kopieerblok',
            'label' => 'Knop',
            'nl'    => 'Kopieer',
            'en'    => 'Copy',
            'pt'    => 'Copiar',
        ),
        'kopieer_klaar'               => array(
            'groep' => 'Kopieerblok',
            'label' => 'Na het kopiëren',
            'nl'    => 'Gekopieerd',
            'en'    => 'Copied',
            'pt'    => 'Copiado',
        ),
        'kopieer_mislukt'             => array(
            'groep' => 'Kopieerblok',
            'label' => 'Als kopiëren niet lukt',
            'nl'    => 'De tekst is geselecteerd. Druk op Ctrl+C (Mac: Cmd+C) om te kopiëren.',
            'en'    => 'The text is selected. Press Ctrl+C (Mac: Cmd+C) to copy.',
            'pt'    => 'O texto está selecionado. Pressione Ctrl+C (Mac: Cmd+C) para copiar.',
        ),
        'kopieer_meer'                => array(
            'groep' => 'Kopieerblok',
            'label' => 'Lange tekst openen',
            'nl'    => 'Toon alles',
            'en'    => 'Show all',
            'pt'    => 'Mostrar tudo',
        ),
        'kopieer_minder'              => array(
            'groep' => 'Kopieerblok',
            'label' => 'Lange tekst inklappen',
            'nl'    => 'Toon minder',
            'en'    => 'Show less',
            'pt'    => 'Mostrar menos',
        ),

        // Bronnenlijst (sinds 3.20.0; teksten gelijk aan het snippet v1.11).
        'bronnen_intro_een'           => array(
            'groep' => 'Bronnenlijst',
            'label' => 'Inleiding bij één bron (%1$s is de site, %2$s het aantal)',
            'nl'    => 'De content van %1$s is gebaseerd op <strong>%2$s</strong> bron. Deze pagina toont die bron en de artikelen waarin hij voorkomt.',
            'en'    => 'The content of %1$s is based on <strong>%2$s</strong> source. This page shows that source and the articles that cite it.',
            'pt'    => 'O conteúdo de %1$s se baseia em <strong>%2$s</strong> fonte. Esta página mostra essa fonte e os artigos em que ela aparece.',
            'lang'  => true,
        ),
        'bronnen_intro_meer'          => array(
            'groep' => 'Bronnenlijst',
            'label' => 'Inleiding bij meer bronnen (%1$s is de site, %2$s het aantal)',
            'nl'    => 'De content van %1$s is gebaseerd op <strong>%2$s</strong> voornamelijk academische bronnen. Deze pagina bevat een overzicht van al deze bronnen en de artikelen waarin ze voorkomen.',
            'en'    => 'The content of %1$s is based on <strong>%2$s</strong> sources, most of them academic. This page lists every source and the articles that cite it.',
            'pt'    => 'O conteúdo de %1$s se baseia em <strong>%2$s</strong> fontes, em sua maioria acadêmicas. Esta página lista todas as fontes e os artigos em que elas aparecem.',
            'lang'  => true,
        ),
        'bronnen_zoek'                => array(
            'groep' => 'Bronnenlijst',
            'label' => 'Zoekveld',
            'nl'    => 'Zoek op auteur of titel',
            'en'    => 'Search by author or title',
            'pt'    => 'Busque por autor ou título',
        ),
        'bronnen_geen'                => array(
            'groep' => 'Bronnenlijst',
            'label' => 'Niets gevonden',
            'nl'    => 'Geen bron gevonden.',
            'en'    => 'No source found.',
            'pt'    => 'Nenhuma fonte encontrada.',
        ),
        'bronnen_nav'                 => array(
            'groep' => 'Bronnenlijst',
            'label' => 'Letters (voor schermlezers)',
            'nl'    => 'Navigeer op beginletter',
            'en'    => 'Jump to letter',
            'pt'    => 'Ir para a letra',
        ),
        'bronnen_geciteerd'           => array(
            'groep' => 'Bronnenlijst',
            'label' => 'Geciteerd in',
            'nl'    => 'Geciteerd in:',
            'en'    => 'Cited in:',
            'pt'    => 'Citado em:',
        ),
        'bronnen_schema'              => array(
            'groep' => 'Bronnenlijst',
            'label' => 'Naam voor Google',
            'nl'    => 'Bibliografie',
            'en'    => 'Bibliography',
            'pt'    => 'Bibliografia',
        ),

        // Praktijkblok (sinds 3.20.0).
        'praktijkblok_kop'            => array(
            'groep' => 'Praktijkblok',
            'label' => 'Kop van een nieuw praktijkblok',
            'nl'    => 'Uit de trainingspraktijk',
            'en'    => 'From the training room',
            'pt'    => 'Da prática de treinamento',
        ),

        // Tooltips.
        'tooltip_toon'                => array(
            'groep' => 'Tooltips',
            'label' => 'Uitleg openen, voor schermlezers',
            'nl'    => 'Toon definitie van %s',
            'en'    => 'Show the explanation of %s',
            'pt'    => 'Mostrar a explicação de %s',
        ),

        // Formulieren en cursusdata.
        'groep'                       => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Groep met nummer',
            'nl'    => 'Groep %d',
            'en'    => 'Group %d',
            'pt'    => 'Turma %d',
        ),
        'kies_groep'                  => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Keuzelijst',
            'nl'    => 'Kies een groep',
            'en'    => 'Choose a group',
            'pt'    => 'Escolha uma turma',
        ),
        'geen_data'                   => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Geen startmomenten',
            'nl'    => 'Neem contact op voor de eerstvolgende startdata.',
            'en'    => 'Get in touch for the next start dates.',
            'pt'    => 'Entre em contato para saber as próximas datas.',
        ),
        'wachtlijst_tekst'            => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Geen nieuwe groep, met wachtlijst (%s is de link)',
            'nl'    => 'Er staat nog geen nieuwe groep gepland. Wil je deze training volgen? %s en je hoort het als eerste wanneer de volgende groep van start gaat!',
            'en'    => 'No new group has been scheduled yet. Keen to join this training? %s and you will be the first to hear when the next group starts!',
            'pt'    => 'Ainda não há nova turma agendada. Quer fazer este treinamento? %s e seja o primeiro a saber quando a próxima turma começar!',
        ),
        'wachtlijst_link'             => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Link naar het wachtlijstformulier',
            'nl'    => 'Zet jezelf op de wachtlijst',
            'en'    => 'Join the waiting list',
            'pt'    => 'Entre na lista de espera',
        ),
        // Trainingspagina (3.30.0).
        'groep_vrij' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Groepskaart: status nog plekken vrij',
            'nl'    => 'Nog plekken vrij',
            'en'    => 'Places available',
            'pt'    => 'Vagas abertas',
        ),
        'groep_laatste' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Groepskaart: status laatste plekken',
            'nl'    => 'Laatste plekken',
            'en'    => 'Last places',
            'pt'    => 'Últimas vagas',
        ),
        'groep_vol' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Groepskaart: status vol',
            'nl'    => 'Vol',
            'en'    => 'Full',
            'pt'    => 'Esgotada',
        ),
        'groep_start' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Groepskaart: kop (%s is de eerste dag)',
            'nl'    => 'Start %s',
            'en'    => 'Starts %s',
            'pt'    => 'Início em %s',
        ),
        'groep_dag' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Groepskaart: dag met nummer',
            'nl'    => 'Dag %d',
            'en'    => 'Day %d',
            'pt'    => 'Dia %d',
        ),
        'groep_inschrijven' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Groepskaart: link (%d is het groepsnummer)',
            'nl'    => 'Schrijf je in voor groep %d',
            'en'    => 'Sign up for group %d',
            'pt'    => 'Inscreva-se na turma %d',
        ),
        'groep_excl_btw' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Groepskaart: bij de prijs',
            'nl'    => 'excl. btw',
            'en'    => 'excl. VAT',
            'pt'    => 'sem impostos',
        ),
        'groep_wachtlijst_vraag' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Groepskaart: regel onder de kaarten',
            'nl'    => 'Past geen datum?',
            'en'    => 'No date that suits you?',
            'pt'    => 'Nenhuma data serve?',
        ),
        'groep_wachtlijst_link' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Groepskaart: link naar de wachtlijst',
            'nl'    => 'Zet mij op de wachtlijst',
            'en'    => 'Put me on the waiting list',
            'pt'    => 'Quero entrar na lista de espera',
        ),
        'groep_wachtlijst_optie' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Keuzelijst: geen groep, wel de wachtlijst',
            'nl'    => 'Geen van beide: wachtlijst',
            'en'    => 'Neither: waiting list',
            'pt'    => 'Nenhuma: lista de espera',
        ),
        'artikel_lees'       => array(
            'groep' => 'Home',
            'label' => 'Laatste artikelen: link op de kaart',
            'nl'    => 'Lees het artikel',
            'en'    => 'Read the article',
            'pt'    => 'Leia o artigo',
        ),
        'archief_blog' => array(
            'groep' => 'Archieven',
            'label' => 'Archiefkop: label op het blogoverzicht',
            'nl'    => 'Blog',
            'en'    => 'Blog',
            'pt'    => 'Blog',
        ),
        'archief_categorie' => array(
            'groep' => 'Archieven',
            'label' => 'Archiefkop: label op een categorie',
            'nl'    => 'Categorie',
            'en'    => 'Category',
            'pt'    => 'Categoria',
        ),
        'archief_zoeken' => array(
            'groep' => 'Archieven',
            'label' => 'Archiefkop: label op de zoekresultaten',
            'nl'    => 'Zoeken',
            'en'    => 'Search',
            'pt'    => 'Busca',
        ),
        'archief_profiel' => array(
            'groep' => 'Archieven',
            'label' => 'Auteurskop: label boven de naam',
            'nl'    => 'Profiel',
            'en'    => 'Profile',
            'pt'    => 'Perfil',
        ),
        'archief_resultaten_voor' => array(
            'groep' => 'Archieven',
            'label' => 'Zoekresultaten: titel (%s is de zoekterm)',
            'nl'    => 'Resultaten voor %s',
            'en'    => 'Results for %s',
            'pt'    => 'Resultados para %s',
        ),
        'archief_n_resultaten' => array(
            'groep' => 'Archieven',
            'label' => 'Zoekresultaten: aantal (%d is het aantal)',
            'nl'    => '%d resultaten',
            'en'    => '%d results',
            'pt'    => '%d resultados',
        ),
        'archief_n_resultaten_1' => array(
            'groep' => 'Archieven',
            'label' => 'Zoekresultaten: aantal bij één resultaat',
            'nl'    => '%d resultaat',
            'en'    => '%d result',
            'pt'    => '%d resultado',
        ),
        'archief_zoek_artikelen' => array(
            'groep' => 'Archieven',
            'label' => 'Zoekveld op blog en categorie',
            'nl'    => 'Zoek in de artikelen',
            'en'    => 'Search the articles',
            'pt'    => 'Pesquise nos artigos',
        ),
        'archief_zoek_site' => array(
            'groep' => 'Archieven',
            'label' => 'Zoekveld op de zoekresultaten',
            'nl'    => 'Zoek op de site',
            'en'    => 'Search the site',
            'pt'    => 'Pesquise no site',
        ),
        'archief_zoek_begrip' => array(
            'groep' => 'Archieven',
            'label' => 'Zoekveld bij de begrippen',
            'nl'    => 'Zoek een begrip',
            'en'    => 'Search the glossary',
            'pt'    => 'Procure um termo',
        ),
        'archief_zoek_tool' => array(
            'groep' => 'Archieven',
            'label' => 'Zoekveld bij de tools',
            'nl'    => 'Zoek een tool',
            'en'    => 'Search the tools',
            'pt'    => 'Procure uma ferramenta',
        ),
        'archief_zoekknop' => array(
            'groep' => 'Archieven',
            'label' => 'Knop van het zoekveld',
            'nl'    => 'Zoeken',
            'en'    => 'Search',
            'pt'    => 'Buscar',
        ),
        'archief_alle' => array(
            'groep' => 'Archieven',
            'label' => 'Filterrij: alle artikelen',
            'nl'    => 'Alle artikelen',
            'en'    => 'All articles',
            'pt'    => 'Todos os artigos',
        ),
        'archief_alle_kort' => array(
            'groep' => 'Archieven',
            'label' => 'Alfabet: alle begrippen',
            'nl'    => 'Alle',
            'en'    => 'All',
            'pt'    => 'Todos',
        ),
        'archief_filter' => array(
            'groep' => 'Archieven',
            'label' => 'Filterrij: naam (voor schermlezers)',
            'nl'    => 'Categorieën',
            'en'    => 'Categories',
            'pt'    => 'Categorias',
        ),
        'archief_alfabet' => array(
            'groep' => 'Archieven',
            'label' => 'Alfabet: naam (voor schermlezers)',
            'nl'    => 'Alfabet',
            'en'    => 'Alphabet',
            'pt'    => 'Alfabeto',
        ),
        'archief_gidsen' => array(
            'groep' => 'Archieven',
            'label' => 'Gidsen: label',
            'nl'    => 'Gidsen',
            'en'    => 'Guides',
            'pt'    => 'Guias',
        ),
        'archief_gidsen_kop' => array(
            'groep' => 'Archieven',
            'label' => 'Gidsen: kop op het blogoverzicht',
            'nl'    => 'Begin bij de basis',
            'en'    => 'Start with the basics',
            'pt'    => 'Comece pelo básico',
        ),
        'archief_gids_kop' => array(
            'groep' => 'Archieven',
            'label' => 'Gidsen: kop op een categorie',
            'nl'    => 'Lees eerst de gids',
            'en'    => 'Read the guide first',
            'pt'    => 'Leia o guia primeiro',
        ),
        'archief_gids_lees' => array(
            'groep' => 'Archieven',
            'label' => 'Gidsen: link op de kaart',
            'nl'    => 'Lees de gids',
            'en'    => 'Read the guide',
            'pt'    => 'Leia o guia',
        ),
        'archief_artikelen' => array(
            'groep' => 'Archieven',
            'label' => 'Artikelraster: label',
            'nl'    => 'Artikelen',
            'en'    => 'Articles',
            'pt'    => 'Artigos',
        ),
        'archief_resultaten' => array(
            'groep' => 'Archieven',
            'label' => 'Artikelraster: label op de zoekresultaten',
            'nl'    => 'Resultaten',
            'en'    => 'Results',
            'pt'    => 'Resultados',
        ),
        'archief_atotz' => array(
            'groep' => 'Archieven',
            'label' => 'Artikelraster: label bij de begrippen',
            'nl'    => 'Van A tot Z',
            'en'    => 'From A to Z',
            'pt'    => 'De A a Z',
        ),
        'archief_nieuwste' => array(
            'groep' => 'Archieven',
            'label' => 'Artikelraster: kop op het blogoverzicht',
            'nl'    => 'Nieuwste artikelen',
            'en'    => 'Latest articles',
            'pt'    => 'Artigos mais recentes',
        ),
        'archief_alle_over' => array(
            'groep' => 'Archieven',
            'label' => 'Artikelraster: kop op een categorie (%s is de categorie)',
            'nl'    => 'Alle artikelen over %s',
            'en'    => 'All articles on %s',
            'pt'    => 'Todos os artigos sobre %s',
        ),
        'archief_alle_soort' => array(
            'groep' => 'Archieven',
            'label' => 'Artikelraster: kop bij begrippen en tools (%s is de soort)',
            'nl'    => 'Alle %s',
            'en'    => 'All %s',
            'pt'    => 'Todos os %s',
        ),
        'archief_pagina_van' => array(
            'groep' => 'Archieven',
            'label' => 'Artikelraster: kop op de zoekresultaten',
            'nl'    => 'Pagina %1$d van %2$d',
            'en'    => 'Page %1$d of %2$d',
            'pt'    => 'Página %1$d de %2$d',
        ),
        'archief_van' => array(
            'groep' => 'Archieven',
            'label' => 'Artikelraster: kop op een auteurspagina (%s is de voornaam)',
            'nl'    => 'Artikelen van %s',
            'en'    => 'Articles by %s',
            'pt'    => 'Artigos de %s',
        ),
        'archief_soort_artikel' => array(
            'groep' => 'Archieven',
            'label' => 'Zoekresultaten: soort van een artikel',
            'nl'    => 'Artikel',
            'en'    => 'Article',
            'pt'    => 'Artigo',
        ),
        'archief_soort_pagina' => array(
            'groep' => 'Archieven',
            'label' => 'Zoekresultaten: soort van een pagina',
            'nl'    => 'Pagina',
            'en'    => 'Page',
            'pt'    => 'Página',
        ),
        'archief_volgende' => array(
            'groep' => 'Archieven',
            'label' => 'Paginering: volgende',
            'nl'    => 'Volgende',
            'en'    => 'Next',
            'pt'    => 'Próxima',
        ),
        'archief_vorige' => array(
            'groep' => 'Archieven',
            'label' => 'Paginering: vorige',
            'nl'    => 'Vorige',
            'en'    => 'Previous',
            'pt'    => 'Anterior',
        ),
        'archief_paginering' => array(
            'groep' => 'Archieven',
            'label' => 'Paginering: naam (voor schermlezers)',
            'nl'    => 'Paginering',
            'en'    => 'Pagination',
            'pt'    => 'Paginação',
        ),
        'archief_geen' => array(
            'groep' => 'Archieven',
            'label' => 'Artikelraster: als er niets is',
            'nl'    => 'Niets gevonden. Probeer een ander woord.',
            'en'    => 'Nothing found. Try another word.',
            'pt'    => 'Nada encontrado. Tente outra palavra.',
        ),
        'archief_meer_over' => array(
            'groep' => 'Archieven',
            'label' => 'Auteurskop: link naar de profielpagina (%s is de voornaam)',
            'nl'    => 'Meer over %s',
            'en'    => 'More about %s',
            'pt'    => 'Mais sobre %s',
        ),
        'contact_telefoon' => array(
            'groep' => 'Contact',
            'label' => 'Contactgegevens: label telefoon',
            'nl'    => 'Telefoon',
            'en'    => 'Phone',
            'pt'    => 'Telefone',
        ),
        'contact_whatsapp' => array(
            'groep' => 'Contact',
            'label' => 'Contactgegevens: label WhatsApp',
            'nl'    => 'WhatsApp',
            'en'    => 'WhatsApp',
            'pt'    => 'WhatsApp',
        ),
        'contact_email' => array(
            'groep' => 'Contact',
            'label' => 'Contactgegevens: label e-mail',
            'nl'    => 'E-mail',
            'en'    => 'Email',
            'pt'    => 'E-mail',
        ),
        'contact_adres' => array(
            'groep' => 'Contact',
            'label' => 'Contactgegevens: label adres',
            'nl'    => 'Adres',
            'en'    => 'Address',
            'pt'    => 'Endereço',
        ),
        'contact_maps' => array(
            'groep' => 'Contact',
            'label' => 'Contactgegevens: link onder het adres',
            'nl'    => 'Bekijk op Google Maps',
            'en'    => 'View on Google Maps',
            'pt'    => 'Ver no Google Maps',
        ),
        'review_google_bron' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Review: bron bij een Google-review',
            'nl'    => 'Google-review',
            'en'    => 'Google review',
            'pt'    => 'Avaliação no Google',
        ),
        'groep_wachtlijst_status' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Wachtlijstkaart: status',
            'nl'    => 'Wachtlijst',
            'en'    => 'Waiting list',
            'pt'    => 'Lista de espera',
        ),
        'groep_wachtlijst_label' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Wachtlijstkaart: label',
            'nl'    => 'Volgende groep',
            'en'    => 'Next group',
            'pt'    => 'Próxima turma',
        ),
        'groep_wachtlijst_kop' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Wachtlijstkaart: kop',
            'nl'    => 'Nog geen datum gepland',
            'en'    => 'No date scheduled yet',
            'pt'    => 'Ainda sem data marcada',
        ),
        'groep_wachtlijst_zin' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Wachtlijstkaart: zin',
            'nl'    => 'Zet je op de wachtlijst en je hoort als eerste wanneer de volgende groep start.',
            'en'    => 'Join the waiting list and be the first to hear when the next group starts.',
            'pt'    => 'Entre na lista de espera e seja o primeiro a saber quando a próxima turma começar.',
        ),
        'held_kerngegevens' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Held: naam van de feitenkaart (voor schermlezers)',
            'nl'    => 'Kerngegevens',
            'en'    => 'Key facts',
            'pt'    => 'Dados principais',
        ),
        'held_volgende_start' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Held: label van de eerstvolgende start',
            'nl'    => 'Volgende start',
            'en'    => 'Next start',
            'pt'    => 'Próximo início',
        ),
        'held_op_google' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Held en USP-strook: achter het Google-cijfer',
            'nl'    => 'op Google',
            'en'    => 'on Google',
            'pt'    => 'no Google',
        ),
        'founder_citaat' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Founder-citaat: het netwerkcitaat',
            'nl'    => 'Het vermogen om anderen te bereiken was, is en blijft doorslaggevend.',
            'en'    => 'The ability to reach others was, is and remains decisive.',
            'pt'    => 'A capacidade de alcançar os outros foi, é e continua sendo decisiva.',
        ),
        'founder_rol' => array(
            'groep' => 'Trainingspagina',
            'label' => 'Founder-citaat: rol (%s is de naam van de site)',
            'nl'    => 'Oprichter van %s',
            'en'    => 'Founder of %s',
            'pt'    => 'Fundador da %s',
        ),
        'usp_bel' => array(
            'groep' => 'Trainingspagina',
            'label' => 'USP-strook: knop met het telefoonnummer',
            'nl'    => 'Bel ons',
            'en'    => 'Call us',
            'pt'    => 'Ligue para nós',
        ),
        'usp_regio' => array(
            'groep' => 'Trainingspagina',
            'label' => 'USP-strook: naam van de strook (voor schermlezers)',
            'nl'    => 'Waarom %s',
            'en'    => 'Why %s',
            'pt'    => 'Por que a %s',
        ),
        'banner_cursus'               => array(
            'groep' => 'Banner',
            'label' => 'Cursusbanner (%1$s is de training, %2$s de startdatum)',
            'nl'    => 'Nog plekken vrij: %1$s, start %2$s.',
            'en'    => 'Places available: %1$s, starting %2$s.',
            'pt'    => 'Vagas abertas: %1$s, início em %2$s.',
        ),
        'banner_cursus_link'          => array(
            'groep' => 'Banner',
            'label' => 'Link in de cursusbanner',
            'nl'    => 'Schrijf je in',
            'en'    => 'Sign up',
            'pt'    => 'Inscreva-se',
        ),
        'banner_sluit'                => array(
            'groep' => 'Banner',
            'label' => 'Sluitknop (voor schermlezers)',
            'nl'    => 'Sluit deze melding',
            'en'    => 'Close this message',
            'pt'    => 'Fechar este aviso',
        ),
        'banner_regio'                => array(
            'groep' => 'Banner',
            'label' => 'Naam van de banner (voor schermlezers)',
            'nl'    => 'Aankondiging',
            'en'    => 'Announcement',
            'pt'    => 'Aviso',
        ),
        'tijd_vanaf'                  => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Lestijd met alleen een begin',
            'nl'    => 'vanaf %s',
            'en'    => 'from %s',
            'pt'    => 'a partir das %s',
        ),
        'tijd_tot'                    => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Lestijd met alleen een einde',
            'nl'    => 'tot %s',
            'en'    => 'until %s',
            'pt'    => 'até %s',
        ),
        // Ankers van het formulier (3.22.0): per taal, en per site aan te
        // passen. De Nederlandse ankers blijven daarnaast altijd werken.
        'anker_inschrijven'           => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Anker van het inschrijfformulier (zonder #)',
            'nl'    => 'inschrijven',
            'en'    => 'register',
            'pt'    => 'inscricao',
        ),
        'anker_aanvragen'             => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Anker van het aanvraagformulier (zonder #)',
            'nl'    => 'aanvragen',
            'en'    => 'request',
            'pt'    => 'solicitar',
        ),
        'tijd_uur'                    => array(
            'groep' => 'Formulieren en cursusdata',
            'label' => 'Achter een lestijd',
            'nl'    => 'uur',
            'en'    => '',
            'pt'    => '',
        ),

        // Feitencodes (3.22.0): [sfp_reviews] en [sfp_publicaties] in de
        // taal van de site.
        'feiten_reviews_geen'         => array(
            'groep' => 'Feitencodes',
            'label' => '[sfp_reviews] zonder meting',
            'nl'    => 'ons actuele cijfer lees je op ons Google-bedrijfsprofiel',
            'en'    => 'you can find our current rating on our Google Business Profile',
            'pt'    => 'nossa nota atual está no nosso Perfil da Empresa no Google',
        ),
        'feiten_reviews_een'          => array(
            'groep' => 'Feitencodes',
            'label' => '[sfp_reviews] met één review (%1$s is het cijfer, %2$s het aantal)',
            'nl'    => '%1$s op basis van %2$s review',
            'en'    => '%1$s based on %2$s review',
            'pt'    => '%1$s com base em %2$s avaliação',
        ),
        'feiten_reviews_meer'         => array(
            'groep' => 'Feitencodes',
            'label' => '[sfp_reviews] met meer reviews (%1$s is het cijfer, %2$s het aantal)',
            'nl'    => '%1$s op basis van %2$s reviews',
            'en'    => '%1$s based on %2$s reviews',
            'pt'    => '%1$s com base em %2$s avaliações',
        ),
        'feiten_publicaties_terugval' => array(
            'groep' => 'Feitencodes',
            'label' => '[sfp_publicaties] als er nog niet geteld is',
            'nl'    => 'ruim 200',
            'en'    => 'more than 200',
            'pt'    => 'mais de 200',
        ),

        // WhatsApp.
        'whatsapp_bericht'            => array(
            'groep' => 'WhatsApp',
            'label' => 'Eerste bericht',
            'nl'    => 'Hoi, ik heb een vraag',
            'en'    => 'Hi, I have a question',
            'pt'    => 'Olá, tenho uma dúvida',
        ),

        // Merkwaarden (sinds 3.23.0). De tekst zelf komt uit de merkbron.
        'merkwaarden_grens'           => array(
            'groep' => 'Merkwaarden',
            'label' => 'Label voor de grens in het vlak',
            'nl'    => 'Grens',
            'en'    => 'Boundary',
            'pt'    => 'Limite',
        ),
        'merkwaarden_meer'            => array(
            'groep' => 'Merkwaarden',
            'label' => 'Link onder in een kaart (%s is de waarde)',
            'nl'    => 'Meer over %s',
            'en'    => 'More about %s',
            'pt'    => 'Mais sobre %s',
        ),
        'merkwaarden_kaarten'         => array(
            'groep' => 'Merkwaarden',
            'label' => 'Naam van de drie kaarten (voor schermlezers)',
            'nl'    => 'De drie merkwaarden',
            'en'    => 'The three brand values',
            'pt'    => 'Os três valores de marca',
        ),
        'merkwaarden_geen_slot'       => array(
            'groep' => 'Merkwaarden',
            'label' => 'Melding voor de beheerder als het slot ontbreekt (%s is de taal)',
            'nl'    => 'Alleen voor beheerders: vul de slotzin en de knop (%s) in bij SFP Beheer, Onderdelen, Merkwaarden. Bezoekers zien hier niets.',
            'en'    => 'Admins only: fill in the closing line and the button (%s) in SFP Beheer, Onderdelen, Merkwaarden. Visitors see nothing here.',
            'pt'    => 'Só para administradores: preencha a frase final e o botão (%s) em SFP Beheer, Onderdelen, Merkwaarden. Os visitantes não veem nada aqui.',
        ),
        'merkwaarden_terugval'        => array(
            'groep' => 'Merkwaarden',
            'label' => 'Melding voor de beheerder als de bron niet bereikbaar was',
            'nl'    => 'Alleen voor beheerders: de merkbron was niet bereikbaar of niet te lezen. Dit is de laatst opgehaalde tekst. Zie SFP Beheer, Onderdelen, Merkwaarden.',
            'en'    => 'Admins only: the brand source could not be reached or read. This is the last text fetched. See SFP Beheer, Onderdelen, Merkwaarden.',
            'pt'    => 'Só para administradores: não foi possível acessar ou ler a fonte da marca. Este é o último texto obtido. Veja SFP Beheer, Onderdelen, Merkwaarden.',
        ),
        'merkwaarden_geen_bron'       => array(
            'groep' => 'Merkwaarden',
            'label' => 'Melding voor de beheerder als er geen tekst is',
            'nl'    => 'Alleen voor beheerders: de merkwaarden konden niet worden geladen.',
            'en'    => 'Admins only: the brand values could not be loaded.',
            'pt'    => 'Só para administradores: não foi possível carregar os valores de marca.',
        ),

        // Zelftest: de testpagina (sinds 3.5.0).
        'zelftest_sluiten'            => array(
            'groep' => 'Zelftest',
            'label' => 'Kruisje op de testpagina (voor schermlezers)',
            'nl'    => 'Sluiten',
            'en'    => 'Close',
            'pt'    => 'Fechar',
        ),
        'zelftest_stop'               => array(
            'groep' => 'Zelftest',
            'label' => 'Vraag bij het kruisje tijdens de test',
            'nl'    => 'Weet je zeker dat je stopt? Je antwoorden worden niet bewaard.',
            'en'    => 'Are you sure you want to stop? Your answers will not be saved.',
            'pt'    => 'Tem certeza de que quer parar? Suas respostas não serão salvas.',
        ),
        'zelftest_stop_ja'            => array(
            'groep' => 'Zelftest',
            'label' => 'Knop: stoppen',
            'nl'    => 'Stoppen',
            'en'    => 'Stop',
            'pt'    => 'Parar',
        ),
        'zelftest_stop_nee'           => array(
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
            'nl' => array(
                'meervoud'  => 'Begrippen',
                'enkelvoud' => 'Begrip'
            ),
            'en' => array(
                'meervoud'  => 'Glossary',
                'enkelvoud' => 'Term'
            ),
            'pt' => array(
                'meervoud'  => 'Glossario',
                'enkelvoud' => 'Termo'
            ),
        ),
        'tool'      => array(
            'nl' => array(
                'meervoud'  => 'Tools',
                'enkelvoud' => 'Tool'
            ),
            'en' => array(
                'meervoud'  => 'Tools',
                'enkelvoud' => 'Tool'
            ),
            'pt' => array(
                'meervoud'  => 'Ferramentas',
                'enkelvoud' => 'Ferramenta'
            ),
        ),
    );
    $type  = isset( $namen[ $type ] ) ? $type : 'tool';
    return $namen[ $type ][ $taal ] ?? $namen[ $type ]['nl'];
}

/* =========================================================================
 * Incompanycalculator (sinds 3.36.0)
 * ====================================================================== */

/**
 * Vaste teksten van de incompanycalculator: scherm, pdf en notitie.
 *
 * Nederlands is de bron. Engels is een concept tot Stephan het bevestigt
 * (SwS, fase 3); Portugees valt terug op Engels (FL heeft geen calculator).
 * Labelteksten (programma, uitleg per extra, profiel) staan in SFP Beheer,
 * Calculator. Plaatsvervangers: {n}, {prijs}, {datum}, {kenmerk}, {van}, {tot}.
 *
 * @param  string $taal nl, en of pt; leeg is de taal van de site.
 * @return array<string, string>
 */
function sfp_calculator_teksten( $taal = '' ) {
    $taal = '' !== $taal ? $taal : sfp_page_config_taal();
    $nl   = array(
        // Kop en navigatie.
        'titel'             => 'Bereken je incompanytraining',
        'intro'             => 'Beantwoord een paar vragen en je ziet direct wat een training voor je team kost. Je ontvangt de begroting als pdf, om intern voor te leggen.',
        'verder'            => 'Verder',
        'terug'             => 'Terug',
        'stap'              => 'Stap {n}',
        'optioneel'         => 'optioneel',
        'excl'              => 'excl. btw',
        'per_groep'         => 'per groep',
        'sluiten'           => 'Sluiten',
        'uitleg'            => 'Uitleg',
        // Stap 1: route.
        'route_vraag'       => 'Waarvoor zoek je een begroting?',
        'route_A'           => 'Een training voor ons team',
        'route_A_zin'       => 'Eén of meer groepen, 1, 2 of 3 dagen.',
        'route_B'           => 'Sprekers voorbereiden op ons eigen evenement',
        'route_B_zin'       => 'Coaching van sprekers voor een congres, kick-off of symposium.',
        'route_C'           => 'We vragen meerdere aanbieders om een offerte',
        'route_C_zin'       => 'Deel de criteria van de uitvraag; we nemen direct contact op.',
        'afwijkend'         => 'Zoek je iets anders, zoals een inspiratiesessie of een dagdeel? Neem dan direct contact met ons op.',
        // Stap 2: situatie.
        'situatie_vraag'    => 'Wat moet er na de training anders zijn?',
        'situatie_hulp'     => 'In je eigen woorden, een paar zinnen is genoeg.',
        'waarom_vraag'      => 'Waarom nu?',
        'waarom_nieuwe_rol' => 'Nieuwe rol of functie',
        'waarom_groei'      => 'Het team groeit of verandert',
        'waarom_moment'     => 'Er komt een belangrijk moment aan',
        'waarom_feedback'   => 'Feedback of een ontwikkelgesprek',
        'waarom_beleid'     => 'Onderdeel van ons opleidingsbeleid',
        'waarom_anders'     => 'Anders',
        'eerder_vraag'      => 'Heeft het team eerder een training gehad op dit onderwerp?',
        'eerder_hulp'       => 'Wat werkte wel en wat niet?',
        // Stap 3: mensen.
        'mensen_vraag'      => 'Hoeveel mensen doen mee?',
        'mensen_getal'      => 'Precies',
        'mensen_marge'      => 'Ongeveer, van tot',
        'mensen_onbekend'   => 'Weet ik nog niet',
        'mensen_van'        => 'Van',
        'mensen_tot'        => 'Tot',
        'mensen_aantal'     => 'Aantal deelnemers',
        'doelgroep_vraag'   => 'Wie doen er mee?',
        'doelgroep_starters'      => 'Starters',
        'doelgroep_ervaren'       => 'Ervaren medewerkers',
        'doelgroep_senioren'      => 'Senioren of specialisten',
        'doelgroep_leidinggevend' => 'Leidinggevenden',
        'doelgroep_gemengd'       => 'Gemengd',
        'doelgroep_anders'        => 'Anders, namelijk',
        'melding_klein'     => 'Met dit aantal is individuele coaching of de open inschrijving doorgaans voordeliger.',
        'link_coaching'     => 'Individuele coaching',
        'link_open'         => 'Open inschrijving',
        'melding_negen_tien' => 'Een groep telt bij ons 8 deelnemers. Bij 9 of 10 overleggen we graag hoe we dat aanpakken.',
        'melding_maatwerk'  => 'Vanaf 5 groepen maken we een programma op maat. Je ziet hieronder een richtprijs.',
        'melding_groot'     => 'Een plenaire sessie of een inspiratiesessie voor de hele groep kan ook. Neem daarvoor contact met ons op.',
        'melding_onbekend'  => 'We rekenen met een groep van 8. Je ziet de prijs per groep.',
        'melding_abonnement' => 'Vanaf 12 coachuren per deelnemer bieden we een abonnement aan. Dat bespreken we graag.',
        'groepen'           => '{n} groepen',
        'groep'             => '1 groep',
        'verdeling'         => 'Verdeling: {n}',
        // Stap 4: vorm.
        'vorm_vraag'        => 'Hoe lang duurt de training?',
        'vorm_1'            => '1 dag',
        'vorm_2'            => '2 dagen',
        'vorm_3'            => '3 dagen',
        'vorm_1_zin'        => 'Eén trainingsdag per groep.',
        'vorm_2_zin'        => 'Twee trainingsdagen per groep; persoonlijke begeleiding als keuze.',
        'vorm_3_zin'        => 'Een traject met persoonlijke begeleiding, Presentation Clinic en boek inbegrepen.',
        'vorm_inleveren'    => 'Wat je erin steekt: {n} trainingsdag(en) per deelnemer.',
        'begeleiding'       => 'Persoonlijke begeleiding tijdens de cursus',
        'begeleiding_zin'   => 'Online intake, voortgangsgesprek en coachgesprek per deelnemer.',
        'inbegrepen'        => 'inbegrepen',
        'gratis'            => 'gratis',
        'altijd_inbegrepen' => 'Altijd inbegrepen: een call vooraf en een evaluatiecall.',
        // Stap 5: extra's.
        'extras_vraag'      => 'Wil je er iets aan toevoegen?',
        'extras_hulp'       => 'Alles is optioneel. Klik op het i-teken voor uitleg.',
        'extra_coachuren'   => 'Extra coachuren',
        'extra_coachuren_eenheid' => 'blokken van 4 uur per deelnemer',
        'extra_clinic'      => 'Presentation Clinic',
        'extra_clinic_eenheid' => 'per deelnemer',
        'extra_boek'        => 'Boek',
        'extra_boek_eenheid' => 'per deelnemer',
        'extra_acteur'      => 'Trainingsacteur',
        'extra_acteur_eenheid' => 'dagen',
        'extra_terugkom'    => 'Terugkomsessie per groep',
        'extra_terugkom_geen' => 'Geen',
        'extra_terugkom_dagdeel' => 'Een dagdeel',
        'extra_terugkom_online' => '2 uur online',
        'extra_lotj'        => 'Learning on the job',
        'extra_lotj_eenheid' => 'blokken van 4 uur per deelnemer',
        'extra_scan'        => 'Scan',
        'extra_scan_geen'   => 'Geen',
        'extra_scan_individueel' => 'Individueel, per deelnemer',
        'extra_scan_team'   => 'Teamscan, per groep',
        'voordeel_vraag'    => 'Groepsvoordeel vanaf de 3e groep: wat kies je?',
        'voordeel_korting'  => '€ 1.000 korting per groep',
        'voordeel_coachuren' => 'Een gratis blok coachuren per groep',
        // Stap 6: locatie.
        'locatie_vraag'     => 'Waar vindt de training plaats?',
        'locatie_klant'     => 'Bij jullie op locatie',
        'locatie_aanvraag'  => 'Op aanvraag (we sturen een voorstel)',
        'locatie_extern'    => 'Een externe locatie',
        'locatie_lunch'     => 'Lunch',
        'locatie_borrel'    => 'Borrel',
        'locatie_teamdag'   => 'Een teamdag eromheen',
        // Stap 7: intern proces.
        'intern_vraag'      => 'Hoe gaat het bij jullie verder?',
        'intern_hulp'       => 'Optioneel. Zo maken we de begroting en het gesprek bruikbaarder.',
        'intern_beslist'    => 'Wie beslist er mee?',
        'intern_budget'     => 'Is er al een budget?',
        'intern_vorig'      => 'Hoe ging een vorig traject?',
        'inkoop_vakje'      => 'Onze inkoop heeft gegevens nodig',
        'inkoop_po'         => 'PO-nummer',
        'inkoop_kostenplaats' => 'Kostenplaats',
        'inkoop_referentie' => 'Referentie',
        'inkoop_factuuradres' => 'Factuuradres',
        // Stap 8 en 9.
        'gegevens_vraag'    => 'Waar mogen we de begroting naartoe sturen?',
        'gegevens_hulp'     => 'Je ontvangt de begroting als pdf. We gebruiken je gegevens alleen om je over deze begroting te mailen.',
        'geen_formulier'    => 'De laatste stap is nog niet ingesteld. Beheerder: kies het formulier in SFP Beheer, Calculator.',
        'klaar_kop'         => 'Je begroting is onderweg',
        'klaar_zin'         => 'Je ontvangt de begroting binnen een paar minuten per mail. Kenmerk: {kenmerk}.',
        'klaar_c_kop'       => 'Dank je wel',
        'klaar_c_zin'       => 'We nemen zo snel mogelijk contact met je op over de uitvraag.',
        'knop_gesprek'      => 'Plan een adviesgesprek',
        'knop_offerte'      => 'Vraag de offerte aan',
        'knop_pdf'          => 'Bekijk de begroting',
        'fout'              => 'Er ging iets mis. Probeer het opnieuw of neem contact met ons op.',
        'proef'             => 'Proef',
        'proef_balk'        => 'Proefmodus: alleen beheerders zien de calculator.',
        // Begroting.
        'begroting'         => 'Begroting',
        'totaal'            => 'Totaal',
        'richtprijs'        => 'Richtprijs',
        'bandbreedte'       => 'Bij {van} tot {tot} deelnemers: {prijs}',
        'evenement_prijs'   => '{prijs} per coach per dag. Het aantal coachdagen bepalen we samen.',
        'sprekers_vraag'    => 'Hoeveel sprekers bereiden we voor?',
        'soort_vraag'       => 'Wat voor voorbereiding zoek je?',
        'soort_keynote'     => 'Een keynote of hoofdspreker',
        'soort_meerdere'    => 'Meerdere sprekers of een programma',
        'soort_pitch'       => 'Pitches',
        'soort_anders'      => 'Anders',
        'criteria_vraag'    => 'Wat zijn de criteria van de uitvraag?',
        'criteria_hulp'     => 'Bijvoorbeeld onderwerp, aantal deelnemers, planning en de manier van beoordelen.',
        'op_aanvraag'       => 'op aanvraag',
        'regel_vorm_1'      => '1 trainingsdag',
        'regel_vorm_2'      => '2 trainingsdagen',
        'regel_vorm_3'      => '3 trainingsdagen',
        'regel_begeleiding' => 'Persoonlijke begeleiding tijdens de cursus',
        'regel_coachuren'   => 'Extra coachuren (blokken van 4 uur)',
        'regel_clinic'      => 'Presentation Clinic',
        'regel_boek'        => 'Boek',
        'regel_acteur'      => 'Trainingsacteur (dagen)',
        'regel_terugkom_dagdeel' => 'Terugkomsessie, dagdeel',
        'regel_terugkom_online' => 'Terugkomsessie, 2 uur online',
        'regel_lotj'        => 'Learning on the job (blokken van 4 uur)',
        'regel_scan_individueel' => 'Scan per deelnemer',
        'regel_scan_team'   => 'Teamscan per groep',
        'regel_voordeel_korting' => 'Groepsvoordeel vanaf de 3e groep',
        'regel_voordeel_coachuren' => 'Groepsvoordeel: blok coachuren',
        'btw_regel'         => 'Btw {n}%',
        'incl'              => 'Totaal incl. btw',
        // Pdf.
        'pdf_titel'         => 'Begroting incompanytraining',
        'pdf_kenmerk'       => 'Kenmerk',
        'pdf_datum'         => 'Datum',
        'pdf_geldig'        => 'Geldig tot',
        'pdf_beslisser'     => 'Voor wie beslist',
        'pdf_situatie'      => 'Jullie situatie',
        'pdf_doel'          => 'Wat er anders moet',
        'pdf_waarom'        => 'Waarom nu',
        'pdf_vorig'         => 'Eerder traject',
        'pdf_programma'     => 'Het programma',
        'pdf_opbouw'        => 'Elk programma is opgebouwd volgens de activatiecurve.',
        'pdf_methode'       => 'Daarbinnen werken we met {n}.',
        'pdf_doelgroep'     => 'Voor deze groep',
        'pdf_contact'       => 'Je contactpersoon',
        'pdf_groepsgrootte' => 'Groepsgrootte',
        'pdf_groepsgrootte_zin' => 'Een groep telt bij ons hoogstens 8 deelnemers. Zo krijgt iedereen genoeg tijd om te oefenen en feedback te krijgen.',
        'pdf_inbegrepen'    => 'Inbegrepen',
        'pdf_inbegrepen_lijst' => "Een call vooraf om het programma af te stemmen\nEen evaluatiecall na afloop",
        'pdf_niet'          => 'Niet inbegrepen',
        'pdf_niet_lijst'    => "Een externe locatie, lunch en borrel (standaard bij jullie op locatie)",
        'pdf_locatie'       => 'Locatie',
        'pdf_voorwaarden'   => 'Voorwaarden',
        'pdf_voorwaarden_zin' => 'Facturatie vooraf. Bij interne inschrijving zijn minimaal 4 aanmeldingen per groep nodig; twee weken vooraf besluiten we samen over doorgaan. Deze begroting is geen offerte: na het gesprek ontvang je een formele offerte.',
        'pdf_voorwaarden_link' => 'Algemene voorwaarden',
        'pdf_leverancier'   => 'Leveranciersgegevens',
        'pdf_inkoop'        => 'Voor inkoop',
        'pdf_aankondiging'  => 'Wil je de training intern aankondigen? We leveren graag een tekst aan.',
        'pdf_referentie'    => 'Wat een klant zegt',
        'pdf_volgende'      => 'De volgende stap',
        'pdf_volgende_zin'  => 'Plan een adviesgesprek, het liefst met de persoon die mee beslist. Dan stemmen we het programma af en ontvang je een formele offerte.',
        'pdf_maatwerk'      => 'Dit is een richtprijs. Vraag een offerte op maat aan.',
        'pdf_klaar'         => 'Je begroting wordt gemaakt. Deze pagina ververst vanzelf.',
        'pdf_verlopen'      => 'Deze begroting is niet meer geldig. Maak een nieuwe begroting of neem contact met ons op.',
        'pdf_onbekend'      => 'Deze begroting bestaat niet.',
        'pdf_bestand'       => 'Begroting incompany',
        'pdf_pagina'        => 'Pagina',
        'pdf_van'           => 'van',
        'ingebed_kop'       => 'Wat kost een training voor jouw team?',
        'ingebed_zin'       => 'Kies wat je zoekt en hoeveel mensen meedoen. Je ziet direct een eerste begroting.',
        'ingebed_vanaf'     => 'Eerste indicatie',
    );
    if ( 'nl' === $taal ) {
        return $nl;
    }
    $en = array(
        'titel'             => 'Calculate your in-company training',
        'intro'             => 'Answer a few questions and see straight away what a training for your team costs. You receive the budget as a PDF to share internally.',
        'verder'            => 'Next',
        'terug'             => 'Back',
        'stap'              => 'Step {n}',
        'optioneel'         => 'optional',
        'excl'              => 'excl. VAT',
        'per_groep'         => 'per group',
        'sluiten'           => 'Close',
        'uitleg'            => 'Explanation',
        'route_vraag'       => 'What do you need a budget for?',
        'route_A'           => 'A training for our team',
        'route_A_zin'       => 'One or more groups, 1, 2 or 3 days.',
        'route_B'           => 'Preparing speakers for our own event',
        'route_B_zin'       => 'Speaker coaching for a conference, kick-off or symposium.',
        'route_C'           => 'We are asking several providers for a proposal',
        'route_C_zin'       => 'Share the criteria of the tender; we will contact you directly.',
        'afwijkend'         => 'Looking for something else, such as an inspiration session or a half day? Please contact us directly.',
        'situatie_vraag'    => 'What should be different after the training?',
        'situatie_hulp'     => 'In your own words, a few sentences is enough.',
        'waarom_vraag'      => 'Why now?',
        'waarom_nieuwe_rol' => 'New role or position',
        'waarom_groei'      => 'The team is growing or changing',
        'waarom_moment'     => 'An important moment is coming up',
        'waarom_feedback'   => 'Feedback or a development review',
        'waarom_beleid'     => 'Part of our learning policy',
        'waarom_anders'     => 'Other',
        'eerder_vraag'      => 'Has the team had training on this topic before?',
        'eerder_hulp'       => 'What worked and what did not?',
        'mensen_vraag'      => 'How many people will take part?',
        'mensen_getal'      => 'Exactly',
        'mensen_marge'      => 'Roughly, from to',
        'mensen_onbekend'   => 'Not sure yet',
        'mensen_van'        => 'From',
        'mensen_tot'        => 'To',
        'mensen_aantal'     => 'Number of participants',
        'doelgroep_vraag'   => 'Who will take part?',
        'doelgroep_starters'      => 'Starters',
        'doelgroep_ervaren'       => 'Experienced staff',
        'doelgroep_senioren'      => 'Seniors or specialists',
        'doelgroep_leidinggevend' => 'Managers',
        'doelgroep_gemengd'       => 'Mixed',
        'doelgroep_anders'        => 'Other, namely',
        'melding_klein'     => 'With this number, individual coaching or an open course is usually better value.',
        'link_coaching'     => 'Individual coaching',
        'link_open'         => 'Open course',
        'melding_negen_tien' => 'A group has 8 participants. With 9 or 10 we would like to discuss how to approach it.',
        'melding_maatwerk'  => 'From 5 groups we design a tailored programme. Below you see an indicative price.',
        'melding_groot'     => 'A plenary or inspiration session for the whole group is also possible. Please contact us.',
        'melding_onbekend'  => 'We calculate with a group of 8. You see the price per group.',
        'melding_abonnement' => 'From 12 coaching hours per participant we offer a subscription. We are happy to discuss it.',
        'groepen'           => '{n} groups',
        'groep'             => '1 group',
        'verdeling'         => 'Split: {n}',
        'vorm_vraag'        => 'How long is the training?',
        'vorm_1'            => '1 day',
        'vorm_2'            => '2 days',
        'vorm_3'            => '3 days',
        'vorm_1_zin'        => 'One training day per group.',
        'vorm_2_zin'        => 'Two training days per group; personal guidance optional.',
        'vorm_3_zin'        => 'A programme with personal guidance, Presentation Clinic and book included.',
        'vorm_inleveren'    => 'What it takes: {n} training day(s) per participant.',
        'begeleiding'       => 'Personal guidance during the course',
        'begeleiding_zin'   => 'Online intake, progress meeting and coaching session per participant.',
        'inbegrepen'        => 'included',
        'gratis'            => 'free',
        'altijd_inbegrepen' => 'Always included: a call beforehand and an evaluation call.',
        'extras_vraag'      => 'Would you like to add anything?',
        'extras_hulp'       => 'Everything is optional. Click the i for an explanation.',
        'extra_coachuren'   => 'Extra coaching hours',
        'extra_coachuren_eenheid' => 'blocks of 4 hours per participant',
        'extra_clinic'      => 'Presentation Clinic',
        'extra_clinic_eenheid' => 'per participant',
        'extra_boek'        => 'Book',
        'extra_boek_eenheid' => 'per participant',
        'extra_acteur'      => 'Training actor',
        'extra_acteur_eenheid' => 'days',
        'extra_terugkom'    => 'Follow-up session per group',
        'extra_terugkom_geen' => 'None',
        'extra_terugkom_dagdeel' => 'Half a day',
        'extra_terugkom_online' => '2 hours online',
        'extra_lotj'        => 'Learning on the job',
        'extra_lotj_eenheid' => 'blocks of 4 hours per participant',
        'extra_scan'        => 'Scan',
        'extra_scan_geen'   => 'None',
        'extra_scan_individueel' => 'Individual, per participant',
        'extra_scan_team'   => 'Team scan, per group',
        'voordeel_vraag'    => 'Group benefit from the 3rd group: what do you choose?',
        'voordeel_korting'  => 'A discount per group',
        'voordeel_coachuren' => 'A free block of coaching hours per group',
        'locatie_vraag'     => 'Where will the training take place?',
        'locatie_klant'     => 'At your premises',
        'locatie_aanvraag'  => 'On request (we send a proposal)',
        'locatie_extern'    => 'An external venue',
        'locatie_lunch'     => 'Lunch',
        'locatie_borrel'    => 'Drinks',
        'locatie_teamdag'   => 'A team day around it',
        'intern_vraag'      => 'What happens next on your side?',
        'intern_hulp'       => 'Optional. It makes the budget and the conversation more useful.',
        'intern_beslist'    => 'Who else decides?',
        'intern_budget'     => 'Is there a budget yet?',
        'intern_vorig'      => 'How did a previous programme go?',
        'inkoop_vakje'      => 'Our procurement needs details',
        'inkoop_po'         => 'PO number',
        'inkoop_kostenplaats' => 'Cost centre',
        'inkoop_referentie' => 'Reference',
        'inkoop_factuuradres' => 'Invoice address',
        'gegevens_vraag'    => 'Where can we send the budget?',
        'gegevens_hulp'     => 'You receive the budget as a PDF. We only use your details to email you about this budget.',
        'geen_formulier'    => 'The last step is not set up yet. Admin: choose the form in SFP Beheer, Calculator.',
        'klaar_kop'         => 'Your budget is on its way',
        'klaar_zin'         => 'You will receive the budget by email within a few minutes. Reference: {kenmerk}.',
        'klaar_c_kop'       => 'Thank you',
        'klaar_c_zin'       => 'We will contact you about the tender as soon as possible.',
        'knop_gesprek'      => 'Book an advisory call',
        'knop_offerte'      => 'Request the proposal',
        'knop_pdf'          => 'View the budget',
        'fout'              => 'Something went wrong. Please try again or contact us.',
        'proef'             => 'Test',
        'proef_balk'        => 'Test mode: only admins see the calculator.',
        'begroting'         => 'Budget',
        'totaal'            => 'Total',
        'richtprijs'        => 'Indicative price',
        'bandbreedte'       => 'With {van} to {tot} participants: {prijs}',
        'evenement_prijs'   => '{prijs} per coach per day. We decide the number of coaching days together.',
        'sprekers_vraag'    => 'How many speakers do we prepare?',
        'soort_vraag'       => 'What kind of preparation are you looking for?',
        'soort_keynote'     => 'A keynote or main speaker',
        'soort_meerdere'    => 'Several speakers or a programme',
        'soort_pitch'       => 'Pitches',
        'soort_anders'      => 'Other',
        'criteria_vraag'    => 'What are the criteria of the tender?',
        'criteria_hulp'     => 'For example topic, number of participants, planning and how proposals are assessed.',
        'op_aanvraag'       => 'on request',
        'regel_vorm_1'      => '1 training day',
        'regel_vorm_2'      => '2 training days',
        'regel_vorm_3'      => '3 training days',
        'regel_begeleiding' => 'Personal guidance during the course',
        'regel_coachuren'   => 'Extra coaching hours (blocks of 4 hours)',
        'regel_clinic'      => 'Presentation Clinic',
        'regel_boek'        => 'Book',
        'regel_acteur'      => 'Training actor (days)',
        'regel_terugkom_dagdeel' => 'Follow-up session, half day',
        'regel_terugkom_online' => 'Follow-up session, 2 hours online',
        'regel_lotj'        => 'Learning on the job (blocks of 4 hours)',
        'regel_scan_individueel' => 'Scan per participant',
        'regel_scan_team'   => 'Team scan per group',
        'regel_voordeel_korting' => 'Group benefit from the 3rd group',
        'regel_voordeel_coachuren' => 'Group benefit: block of coaching hours',
        'btw_regel'         => 'VAT {n}%',
        'incl'              => 'Total incl. VAT',
        'pdf_titel'         => 'Budget in-company training',
        'pdf_kenmerk'       => 'Reference',
        'pdf_datum'         => 'Date',
        'pdf_geldig'        => 'Valid until',
        'pdf_beslisser'     => 'For the decision maker',
        'pdf_situatie'      => 'Your situation',
        'pdf_doel'          => 'What should change',
        'pdf_waarom'        => 'Why now',
        'pdf_vorig'         => 'Previous programme',
        'pdf_programma'     => 'The programme',
        'pdf_opbouw'        => 'Every programme is built on the activation curve.',
        'pdf_methode'       => 'Within it we work with {n}.',
        'pdf_doelgroep'     => 'For this group',
        'pdf_contact'       => 'Your contact',
        'pdf_groepsgrootte' => 'Group size',
        'pdf_groepsgrootte_zin' => 'A group has at most 8 participants, so everyone has enough time to practise and receive feedback.',
        'pdf_inbegrepen'    => 'Included',
        'pdf_inbegrepen_lijst' => "A call beforehand to tune the programme\nAn evaluation call afterwards",
        'pdf_niet'          => 'Not included',
        'pdf_niet_lijst'    => "An external venue, lunch and drinks (by default at your premises)",
        'pdf_locatie'       => 'Venue',
        'pdf_voorwaarden'   => 'Terms',
        'pdf_voorwaarden_zin' => 'Invoiced in advance. For internal registration at least 4 sign-ups per group are needed; two weeks beforehand we decide together whether it goes ahead. This budget is not a proposal: after our conversation you receive a formal proposal.',
        'pdf_voorwaarden_link' => 'Terms and conditions',
        'pdf_leverancier'   => 'Supplier details',
        'pdf_inkoop'        => 'For procurement',
        'pdf_aankondiging'  => 'Would you like to announce the training internally? We are happy to provide a text.',
        'pdf_referentie'    => 'What a client says',
        'pdf_volgende'      => 'Next step',
        'pdf_volgende_zin'  => 'Book an advisory call, ideally with the person who decides as well. We tune the programme and you receive a formal proposal.',
        'pdf_maatwerk'      => 'This is an indicative price. Request a tailored proposal.',
        'pdf_klaar'         => 'Your budget is being prepared. This page refreshes automatically.',
        'pdf_verlopen'      => 'This budget is no longer valid. Create a new budget or contact us.',
        'pdf_onbekend'      => 'This budget does not exist.',
        'pdf_bestand'       => 'Budget in-company',
        'pdf_pagina'        => 'Page',
        'pdf_van'           => 'of',
        'ingebed_kop'       => 'What does a training for your team cost?',
        'ingebed_zin'       => 'Choose what you need and how many people take part. You see a first budget straight away.',
        'ingebed_vanaf'     => 'First indication',
    );
    return array_merge( $nl, $en );
}
