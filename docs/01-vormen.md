# Vormen: Artikel, Pijler, Begrip, Tool

Een vorm is de vaste opmaak die een bericht krijgt. De vorm volgt het berichttype: een bericht (post) is een Artikel of een Pijler; begrippen en tools zijn eigen berichttypes met een eigen adres. Je kiest de vorm niet met een blok maar met het berichttype en, bij een bericht, met het paneel Deze pagina.

| Vorm | Berichttype | Adres (SwS) | Paneel Deze pagina | Sleutelblok |
|---|---|---|---|---|
| Artikel | Bericht | /blog/slug/ | Dit bericht is: Een artikel; Hoort bij de pijler | geen (gewone tekst, Lead als eerste alinea) |
| Pijler | Bericht | adres van de categorie, bijv. /c/presence/ | Dit bericht is: Een pijler (de gids van een categorie) | geen; de kaart van de pijler komt vanzelf |
| Begrip | Begrippen (Glossary) | /glossary/slug/ (NL: /begrippen/slug/) | Bij dit begrip: Hoort bij de pijler, Verwante begrippen | Definitie (verplicht, bovenaan) |
| Tool | Online tools (Tools) | /tools/slug/ | Bij deze tool: Hoort bij de pijler, Soort | de toolbeschrijving onder Geavanceerd |

Gemeten: SwS 759 (artikel), 863 (begrip), 1076 (tool). Op SwS bestaat op 3-11 nog geen gepubliceerde pijler; de pijlervorm staat aan en is in het paneel te kiezen.

## Artikel

**Wanneer.** Elk blogartikel: explainer, how-to, spoke van een pijler.

**Hoe.**

1. Nieuw bericht. Titel typen. Categorie kies je niet bij Categorieën maar in Deze pagina onder "Hoort bij de pijler"; dat zet de categorie. Onderwerpen kies je bij Tags.
2. Eerste alinea in de alineastijl **Lead**: 40 tot 70 woorden die de vraag uit de titel direct beantwoorden, zonder metazin ("In dit artikel...").
3. Daarna gewone alinea's en koppen (H2, H3). Elk hoofdstuk begint met een zin die de kop zelfstandig beantwoordt.
4. Bouwstenen waar ze de tekst dienen: Samenvatting (direct na de lead), Kader, Citaat, Inzicht, Uitklap als verhaal, Lees ook, FAQ als laatste hoofdstuk. Tussen twee bouwstenen minstens drie alinea's; de editor meldt het als dat niet zo is.
5. Warming-up (drie vragen) mag erbij; hij staat in de editor bovenaan maar verschijnt op de site als knop in de kopkaart.
6. Uitgelichte afbeelding instellen: nodig voor Lees ook, overzichten en delen. Op de pagina zelf toont het artikel hem niet.
7. Bronnen als tooltip op het woord (zie 06). SureRank: titel, omschrijving, focus keyword.

**Wat vanzelf komt.** Pijlerregel boven de titel, kopkaart (auteur, rol, datums, leestijd, Fact-checked, acties), inhoudsopgave en hoofdstukbalk, promokaart naast en in de tekst, bronnenlijst in de zijbalk en onderaan, voet met Editorial statement en Bibliography, auteurskaart, vervolg van de pijler, deelbeeld, Article-schema met citation en FAQPage.

**Wat je niet doet.** Geen eigen intro-kop, geen "Samenvatting" als kop boven het blok, geen handgemaakte inhoudsopgave of bronnenlijst, geen featured image in de tekst herhalen, geen link naar diensten in de lopende tekst (dat doen vervolg en promokaart), geen Spectra-blokken.

## Pijler

**Wanneer.** De gids van een categorie: de hoofdpijler en elke pijler eronder. Eén pijler per categorie.

**Hoe.** Als een artikel, met in Deze pagina "Een pijler (de gids van een categorie)" en de categorie waarvan hij de gids is. Lead van 50 tot 80 woorden: wat het onderwerp is en het antwoord op de hoofdvraag. Geen Samenvatting; de kaart van de pijler neemt die plek in. Elk hoofdstuk eindigt met Lees ook naar zijn artikel (label Go deeper komt vanzelf als het artikel bij de pijler hoort). Lees ook kiest alleen uit gepubliceerde artikelen; een spoke die nog concept is, kun je niet kiezen. Publiceer de pijler dan zonder dat Lees ook en voeg het toe zodra de spoke live is; of plak het latere adres als "andere link" met eigen titel (dan zonder beeld en met een 404 tot de spoke live is: alleen doen als de spoke binnen dezelfde publicatieronde live gaat). Thermometer mag erbij: hij verschijnt als knop in de kopkaart.

**Wat vanzelf komt.** Pijlerregel "Pillar in Blog", kaart van de pijler na de lead (artikelen en begrippen met beeld, titel en leestijd; op telefoon ingeklapt; vanaf vijf artikelen een slider), kopkaart met actie Zelftest als de site een zelftest heeft, promokaart met de zelftest, vervolg, Article-schema met de artikelen.

**Wat je niet doet.** Geen eigen overzicht van artikelen, geen ingebedde zelftest in de tekst, geen tweede pijler voor dezelfde categorie.

## Begrip

**Wanneer.** Eén term uit de woordenlijst, 900 woorden of meer lopende tekst (werkwijze SwS-begrippen).

**Hoe.**

1. Nieuw begrip (menu Begrippen of Glossary). Titel is de term.
2. Bovenaan het blok **Definitie**: één zin die met de term begint, 15 tot 30 woorden, hoogstens 160 tekens; de teller staat eronder. Daaronder een uitleg van hoogstens twee zinnen en vier feitenvakken (Also called, Origin, First described, Part of); een leeg vak verschijnt niet.
3. In Deze pagina: pijler kiezen; Verwante begrippen hoogstens drie (leeg: de andere begrippen van dezelfde pijler).
4. Tekst met 6 tot 8 H2's. Eén blok **In een presentatie** (In a talk): één voorbeeldzin en één regel uitleg. Optioneel **Check je begrip**: één tot drie vragen. Optioneel een verhaal als Uitklap-variant Verhaal, niet direct naast In a talk. FAQ alleen met echte vragen.
5. Uitgelichte afbeelding instellen; de pagina toont hem niet.

**Wat vanzelf komt.** Pijlerregel "Glossary term in [pijler]", makersregel als band boven de definitie (auteur, rol, wijzigingsdatum, leestijd, Fact-checked, Sources), zijkolom rechts zoals bij een artikel (inhoudsopgave vanaf twee H2's, bronnen, promokaart; sinds 3.28.0, tot dan alleen de promokaart), Used in (artikelen die het begrip noemen), All terms A to Z, vervolg, tooltip met de definitie in artikelen die het begrip markeren, DefinedTerm en DefinedTermSet.

**Wat je niet doet.** Geen kopkaart of hoofdstukbalk verwachten (een begrip heeft die niet), geen Samenvatting, geen Warming-up, geen definitie als gewone alinea, geen metazin als eerste woorden.

## Tool

**Wanneer.** Een kleine online tool: test, generator, rekenaar, checklist of tekstanalyse. Grote leadmagnet-tests zijn het onderdeel Zelftest (07), geen tool.

**Hoe.** Zie 08. Kort: nieuw item in Online tools; de toolbeschrijving (JSON) staat in Deze pagina onder Geavanceerd en wordt door Claude gevuld; de redacteur schrijft de pagina eromheen: één alinea onder de titel (wat de tool doet, hoe lang, wat je krijgt), Stappen, een Uitklap compact met "How it works", FAQ.

**Wat vanzelf komt.** Regel boven de titel met de soort (Generator, Calculator, Checklist, Self-test in [pijler]), de tool zelf onder de titel, makerskaart "Made by" onderaan, voet, WebApplication-schema, toolmeting naar GA4.

**Wat je niet doet.** Geen promokaart (een tool heeft er standaard geen), geen tool in een artikel plakken (gebruik het blok Tool), geen antwoorden of uitslagteksten in de pagina typen: die staan in de toolbeschrijving.

## Pagina's (geen vorm, wel een soort)

Een pagina kiest in Deze pagina een **soort**: Gewone pagina, Coaching, Training of Incompany. Coaching, Training en Incompany krijgen de opmaak van een dienstpagina en de knop onderaan op telefoon; Training ook de startmomenten uit Cursusdata. Pagina's bouw je met de bouwstenen uit 02 (Held, Sectie, Kolommen, Kaart, FAQ, boekkaart).
