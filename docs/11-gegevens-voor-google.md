# Gegevens voor Google (structured data) per vorm

De plugin levert per vorm JSON-LD, naast wat SureRank levert (WebSite, WebPage, Organization, Person, BreadcrumbList, SearchAction). Je plakt nooit zelf schema-HTML in een pagina. Gemeten uitgelogd op SwS 3-11.

| Vorm of pagina | Door de plugin | Door SureRank | Uit de inhoud |
|---|---|---|---|
| Artikel (759) | Article met author naar het anker `https://stephanborggreve.com/#person`, datePublished, dateModified, image, **citation** (CreativeWork per bron uit de tooltips), FAQPage bij een FAQ-blok | WebPage, BreadcrumbList (Home, Persuasive speaking, Presence, titel), Organization, Person | Lead, koppen, bronnen, FAQ |
| Pijler | Article met de artikelen van de pijler, citation, FAQPage | BreadcrumbList | Kaart van de pijler |
| Begrip (863) | DefinedTerm (name, description = de definitiezin, inDefinedTermSet, url) en DefinedTermSet | WebPage, BreadcrumbList (Home, Glossary, titel) | Blok Definitie |
| Tool (1076) | WebApplication (script sfp-tool-schema): name, description, isAccessibleForFree, Offer met prijs 0, dateModified, author en creator; FAQPage (sfp-faq-schema). Gemeten 4-11: author en creator wijzen naar `speakwithsteve.com/#person` (bestaat niet; moet het anker zijn), operatingSystem "Any", geen isBasedOn op 1076 | SoftwareApplication/WebApplication (tweede knoop, zonder prijs), BreadcrumbList (Home, Tools, titel) | Alinea onder de titel, toolbeschrijving, FAQ |
| Home (377) | ItemList met Service per dienstkaart in de sectie met anker services (naam = de zichtbare sectiekop); FAQPage | WebSite, Organization, Person | De drie kaarten en de FAQ |
| Boekpagina (635) | Book met Offer (snippet, niet plugin) | | |
| Zelftestpagina (964) | geen eigen schema | WebPage, BreadcrumbList | |
| Archieven (/glossary/, /tools/, /blog/) | geen | WebPage, BreadcrumbList | |

Op de NL-sites leveren de netwerksnippets "Bronvermeldingen in structured data" en "Trainingsaanbod op homepage" nu de citation en de ItemList; de plugin levert dan niets dubbel. Na de omzetting beslist Stephan of die snippets uit gaan.

## Wat de plugin nu levert en wat nog openstaat (4-11)

| Vorm | Levert de plugin nu | Nog open (planning) |
|---|---|---|
| Artikel | Article met author naar het anker, datums, image, citation, FAQPage | Herzieningsdatum als dateModified (kopkaart-herzieningsdatum); Fact-checked-uitklap met Nina (factcheck-nina-kopkaart) |
| Pijler, het bericht | Article met de artikelen van de pijler, citation, FAQPage (gebouwd in 3.25.0; nog niet gemeten: geen gepubliceerde pijler) | Nameting na de eerste pijler |
| Pijler, het categorieadres | Het bericht wordt op het categorieadres geserveerd; SureRank levert daar de BreadcrumbList. Categorieën staan nu op noindex (SureRank-instelling) | indexatie-09: categorieadres naar index bij publicatie van de pijlertekst |
| Begrip | DefinedTerm en DefinedTermSet | author naar het anker en de set op /glossary/ (schema-tool-definedterm, na fase C) |
| Tool | WebApplication met prijs 0, dateModified, FAQPage; isBasedOn alleen als de beschrijving een model noemt (1076 niet) | Eén applicatieknoop (nu twee: SureRank en plugin), author en creator naar het anker (nu `<site>/#person`), operatingSystem Web (nu Any): klus schema-tool-definedterm, na fase C |
| Home | ItemList met Service, FAQPage | NL-homes: nu via snippet; plugin levert pas als de home een sectie met anker services heeft |

**Controle bij een pijler, in twee stappen.** (1) Het bericht in de editor: vorm Pijler, categorie gekozen, SureRank-velden. (2) Het categorieadres uitgelogd: 200, één H1, Article met de artikelen, BreadcrumbList, robots index (nu nog noindex tot indexatie-09 is uitgevoerd).

**Artikel 759.** Staat bewust op noindex en buiten de sitemap (proef, besluit Stephan 17-10). Het is de referentie voor de vorm, niet voor de indexstand. Wanneer het naar index gaat: open vraag (B, 06).

## Wat de redacteur hiervoor doet

- Bronnen als tooltip met Bron: of Source:, anders geen citation.
- FAQ met het blok FAQ, anders geen FAQPage.
- Op een begrip de definitie in het blok Definitie, anders geen DefinedTerm.
- Op de home de dienstkaarten in een Sectie met anker services; de zichtbare sectiekop wordt de naam van de lijst.
- Uitgelichte afbeelding instellen (image in Article).
- Auteur is Stephan (één anker voor de hele graaf).
- Index aan en in de sitemap: zonder index geen citatie via de zoekindex. Uitzondering op SwS: artikel 759 staat bewust op noindex (proef).

## Wat de redacteur niet doet

- Geen schema in Custom HTML, geen "schema-plugin" naast SureRank.
- Geen aggregateRating uit Google-reviews.
- Geen aparte BreadcrumbList: SureRank levert hem; de pijlerregel is het zichtbare kruimelpad.

## Controleren

Uitgelogd de pagina openen en de JSON-LD lezen (Rich Results Test of de eigen toets van Claude).

| Vorm | Goed als | Bekende afwijking tot de correctie |
|---|---|---|
| Artikel, pijler | één Article, author naar het anker, één citation-lijst, één FAQPage bij een FAQ | geen |
| Begrip | één DefinedTerm met de definitiezin, DefinedTermSet | geen author (schema-tool-definedterm) |
| Tool | WebApplication van de plugin (sfp-tool-schema) met prijs 0 en dateModified; FAQPage bij een FAQ | tweede knoop van SureRank zonder prijs; author `<site>/#person`; operatingSystem Any. Niet zelf corrigeren; noteer in publication.md "toolschema: bekende afwijking, klus schema-tool-definedterm" en ga door |
| Home | één ItemList met de dienstkaarten | NL-homes via snippet |
