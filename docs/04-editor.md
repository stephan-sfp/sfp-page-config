# De editor: panelen, controles en meldingen

Gemeten ingelogd op SwS 3.26.1 (artikel 759, begrip 863, tool 1076, pagina 377), alleen bekeken.

## Zijpaneel per vorm

Het zijpaneel heeft twee tabs: het document (Post, Begrip, Online tool of Page) en Block. In het documenttabblad staan drie panelen: **Deze pagina** (van de plugin, bovenaan en open), **SureRank SEO** en **Tags** (alleen bij een artikel). Het paneel Categorieën is uit beeld: de categorie zet je via de pijler.

**Artikel (bericht).**

- Vorm: "Dit bericht is" Een artikel of Een pijler (de gids van een categorie).
- Extra: WhatsApp-knop.
- Bij dit artikel: "Hoort bij de pijler" (keuzelijst van de categorieën; bepaalt de regel boven de titel, de kaart van de pijler en het vervolg; het is ook de categorie); daaronder leest het paneel voor: Onderwerpen (de tags), Warming-up (aantal vragen), Vervolg (van welke pijler), Uitgelichte afbeelding (ingesteld of ontbreekt), Promokaart (waar).
- Beeld om te delen: knop Opnieuw maken.

**Begrip.** Bij dit begrip: Hoort bij de pijler (Geen pijler of een categorie); Verwante begrippen (hoogstens drie; leeg: dezelfde pijler); voorgelezen: Gebruikt in, Vervolg, Uitgelichte afbeelding, Promokaart. Extra: WhatsApp-knop.

**Tool.** Bij deze tool: Hoort bij de pijler; voorgelezen: Uitgelichte afbeelding, Promokaart. Tool: Soort (Generator, Rekenaar, Checklist; "Zelfde tool, ander label boven de titel. Een checklist telt wat is afgevinkt"). Geavanceerd: de Beschrijving (de hele tool als tekst), knop Controleren met de telling (soort, stappen, velden, onderdelen van het resultaat, acties, formulier) en meldingen.

**Pagina.** Soort pagina: Gewone pagina, Coaching, Training, Incompany. Extra: WhatsApp-knop; Inhoudsopgave die meeloopt.

## Wat je in het canvas ziet

- Grijze banden **KOMT VANZELF** op de plek van kopkaart, makersregel, promokaart, makerskaart en tool: je kunt er niet in typen.
- De Warming-up staat bovenaan met het opschrift "staat op de site als knop in de kopkaart, niet op deze plek".
- Het blok Definitie toont de teller: "19 woorden · 121 van 160 tekens".
- Blokken met vragen (Warming-up, Check je begrip, FAQ) tonen vraag en antwoorden als invulvakken.
- De toolpagina toont de tool als band "komt vanzelf, zoals bij de laatste keer opslaan".

## Controles en meldingen

| Melding | Waar | Betekenis |
|---|---|---|
| "Rustig ritme: tussen twee UX-elementen horen minstens drie alinea's. Te dicht op elkaar: FAQ na Citaat (1 alinea ertussen)." | gele balk bovenin de editor, met "Ga naar het eerste" | Twee bouwstenen staan te dicht op elkaar; voeg tekst toe of verplaats een blok. Publiceren blijft mogelijk |
| "Dit artikel heeft geen uitgelichte afbeelding" | gele balk bovenin | Instellen; nodig voor Lees ook, overzichten en delen |
| Teller onder de definitie | blok Definitie | 15 tot 30 woorden en hoogstens 160 tekens |
| "Sjabloontekst niet ingevuld" | SFP Beheer, Overzicht en Pagina's | Een tool of pagina bevat nog sjabloonwoorden tussen rechte haken |
| "Geen uitgelichte afbeelding" | SFP Beheer | Nodig voor Lees ook, overzichten en delen |
| "Geen pijler" | SFP Beheer, bij een tool | De regel boven de titel toont dan alleen de soort |
| "Geen formulier na de uitslag" | SFP Beheer, bij een tool | acties.formulier staat op 0 |

**Wat de ritme-controle telt** (uit de editorcode, 3.26.1): een *alinea* is een gevulde paragraaf of een lijst; koppen tellen niet mee en onderbreken de telling niet. Een *UX-element* is elk eigen blok (Samenvatting, Kader, Citaat, Inzicht, Lees ook, Uitklap, Kolommen, FAQ) en uit WordPress beeld, tabel, citaat en video. De controle draait alleen op berichten (artikel en pijler), niet op begrippen, tools en pagina's. Een toolpagina met Stappen, één alinea, Uitklap compact en FAQ krijgt dus geen melding; een pijlerhoofdstuk met Lees ook, één alinea en een Kader wel. Dat is alleen de techniek: de redactieregel (B, 02) geldt op alle vormen. Op een toolpagina is de tekst eromheen kort en vast van opbouw; of de drie-alinearegel daar versoepeld mag worden, is een besluit voor Stephan (B, 06). Tot dan geldt de regel ook daar.

Niet aanwezig op 3.26.1 (gemeten): een teller of waarschuwing op de lead (40 tot 70 woorden, metazin) en op de eerste zin onder een H2. Die controle doe je zelf tegen de LLMO-norm.

## Blokinstellingen die je vaak nodig hebt

Zie 02 per blok. Algemeen: elk blok met een kop heeft "Link naar dit blok" met Kopieer de link; plak die bij een knop of tekstlink om naar het blok te springen. Hernoem je de kop, dan blijft de link werken.

## Opslaan en publiceren

- Opslaan als concept kan altijd; de voorbeeldlink toont de vorm zoals een bezoeker hem ziet (behalve een concepttool in een held).
- Publiceren: SureRank-velden ingevuld (titel, omschrijving, focus keyword), uitgelichte afbeelding ingesteld, pijler gekozen, geen gele meldingen.
- Na publiceren of een wijziging aan een gepubliceerd stuk: caches legen in de vaste volgorde: WP Rocket used CSS, dan de WP Rocket-cache (die leegt sinds WP Rocket 3.24 ook de SiteGround dynamic cache), dan Cloudflare (Purge Everything via de officiële plugin); daarna uitgelogd controleren. De plugin leegt zelf geen caches, ook niet na opslaan in SFP Beheer. Dat doet Claude; vraag het als het nog niet is gebeurd.
- Niets definitief verwijderen; een stuk dat weg moet gaat naar de prullenbak.
