# Tools: soorten, de toolpagina, het blok Tool, meting

Een tool is een bericht van het type **Online tools** met een korte beschrijving (JSON, metaveld `sfp_tool`) als inhoud. Per soort draait één vaste motor in de browser van de bezoeker; de site bewaart geen antwoorden. Alle 49 oude tools op de NL-sites zijn naar deze motor omgezet; SwS heeft één tool (FAST presentation builder, 1076).

## Soorten

| Soort | Wat de bezoeker doet | Resultaat |
|---|---|---|
| Zelftest (test) | Stellingen beantwoorden, voortgang, profiel | Uitslag met passend artikel; optioneel formulier na de uitslag |
| Generator | Keuzes en korte invulvelden, één handeling per scherm | Tekst om te kopiëren, pdf, Word |
| Rekenaar | Velden, geen knop | Uitkomst terwijl je typt |
| Checklist | Punten afvinken | Telt wat is afgevinkt |
| Tekstanalyse | Tekst plakken | Beoordeling; slimme laag (AI) komt later |

Waar de soort staat: in de toolbeschrijving als `"soort"` (test, generator, rekenaar, checklist, tekstanalyse) en in Deze pagina onder Tool, Soort (Generator, Rekenaar, Checklist). Het paneel toont alleen de drie die hetzelfde motorformat delen en daar alleen het label boven de titel wisselen; test en tekstanalyse hebben een eigen motor en volgen uit de beschrijving. Voorbeelden: checklist DPS "Presentatie checklist" (punten met vinkjes, telt wat is afgevinkt); tekstanalyse DST "B1-checker" (tekst plakken, taalniveau). Een test (zelftest-soort) heeft stellingen, een rekenregel en profielen; voorbeeld CVD "Kolb leerstijlentest".

Netwerkprincipe (besluit 30-10): elk scherm van een tool vraagt één handeling. Een keuze gaat vanzelf door; alleen bij typen een knop Verder. Een keuze in de kaartweergave kan een hulptegel krijgen (sinds 3.27.0): `"hulp"` op het veld (de tekst op de tegel, bijvoorbeeld "What is the difference?") plus een `"uitleg"` per optie; de extra tegel opent onder de tegels een paneel met per optie de uitleg en telt niet als antwoord. Voorbeeld SwS 1076, eerste stap. Voortgang als de letters van het model bovenaan (F A S T), afgeronde letters klikbaar. Uitslag: kop is het antwoord in één zin, daaronder dichte uitklapblokken; acties Download PDF, Copy, Word, Share link, Start again, duimpjes.

## De toolpagina

**Boven de titel:** de soort als regel (Generator; met pijler: Self-test in Presence). **Onder de titel:** één alinea (wat de tool doet, hoe lang het duurt, wat je krijgt; gemeten 1076: "Answer a few short questions and get a plan for your talk: ..."). **Dan de tool** (komt vanzelf). **Daarna de pagina-inhoud** die de redacteur schrijft: Stappen (de methode in stappen), een Uitklap compact "How your plan is built" met de regels achter de uitkomst als tekst, FAQ. **Onderaan:** makerskaart Made by (foto, naam, rol, bio, Updated, Fact-checked, Based on), voet, geen promokaart.

De plugin zet een noscript-tekst: "This tool needs JavaScript. The statements and the calculation rule are below." Daarom moet de werking als tekst op de pagina staan (LLMO-norm): de stellingen en puntentelling van een test, de regels of formule van een generator of rekenaar.

## Wat de redacteur doet

1. Tekst van de pagina schrijven: alinea onder de titel, Stappen, How it works, FAQ; in de taal en stijl van de site.
2. In Deze pagina: pijler kiezen (anders alleen de soort in de regel boven de titel), uitgelichte afbeelding instellen.
3. SureRank: titel, omschrijving, focus keyword; OG-beeld.
4. Teksten in de tool (vragen, opties, uitslagteksten) aanleveren als lijst; Claude zet ze in de beschrijving via REST. De beschrijving staat onder Geavanceerd in Deze pagina en is als JSON te lezen; Controleren telt soort, stappen, velden, resultaatonderdelen en acties.
5. Sjabloonwoorden tussen rechte haken ([Verb], [your audience]) vervangen of laten staan als ze bewust een invulplek zijn; SFP Beheer meldt ze als "sjabloontekst niet ingevuld".

**Meldingen bij een tool en wat je ermee doet**

| Melding (SFP Beheer, Overzicht of Controleren) | Herstellen of bewust laten staan |
|---|---|
| Sjabloontekst niet ingevuld | Herstellen, tenzij de haken een invulplek in de uitslag zijn (zoals "[Verb] [your audience], so that [what changes]" op 1076): dan bewust laten staan |
| Geen uitgelichte afbeelding | Herstellen: nodig voor overzichten en delen |
| Geen pijler | Herstellen zodra de pijler bestaat; tot dan toont de regel alleen de soort |
| Geen formulier na de uitslag | Bewuste keuze per tool (besluit Stephan); laten staan als er geen formulier hoort |
| Fouten in de beschrijving (Controleren) | Altijd herstellen; de tool rendert anders niet |

## Wat de redacteur niet doet

- De JSON zelf bewerken zonder afspraak; een fout breekt de tool.
- De tool nabouwen met blokken of HTML.
- Een promokaart of Zelftest-oproep op een toolpagina zetten.
- De slug wijzigen van een tool die vanuit een pdf of artikel wordt gelinkt.

## Het blok Tool op een andere pagina

**Tool** (`sfp/tool`): kies de tool; weergave Hele tool of Compact. Compact in de held van een pagina maakt een tool-held (SwS /last-minute-presentation/, tool 1076 in de tweede kolom van de Held). Op de pagina van de tool zelf plaats je het blok niet.

## Meting

Elke tool stuurt het event `sfp_tool` naar de dataLayer met actie (start, stap, afgerond, kopieren, download_pdf, download_word, slides_prompt, opnieuw, hulp), tool_id, tool_slug, stap_nummer, stap_totaal en weergave. Nooit antwoorden of ingevulde tekst. GTM per site stuurt het naar GA4; "afgerond" is geen sleutelgebeurtenis. Tekstfeedback bij een duimpje gaat naar het adres in SFP Beheer, Online tools.

## Formulier na de uitslag

Een test kan een SureForms-formulier na de uitslag krijgen (acties.formulier). Of een test een formulier krijgt, besluit Stephan per test (registerklus tools-pijler-en-formulier: "besluiten per test bij Stephan", na afstemming met Nina). Nina toetst de inhoud van de test; het formulierbesluit is van Stephan. De pluginkant (verborgen velden met tool-slug en uitslagtitel) staat gepland.
