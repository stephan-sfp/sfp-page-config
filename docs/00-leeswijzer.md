# SFP Page Config: handleiding voor de contentmarketeer

Versie 2026-11-05a (Pijllink in een kaart volgens branding.md, 3.27.1; SwS-vuistregelzin bevestigd), gemeten op SFP Page Config 3.27.0 en 3.27.1 (speakwithsteve.com als model, steekproef depresenteerschool.nl). Lees de versie altijd live uit SFP Beheer, Onderhoud of `/wp-json/sfp/v1/beheer`.

## Wat de plugin doet

SFP Page Config is de eigen WordPress-plugin van School for Professionals. Hij draait op alle acht sites van het netwerk en doet drie dingen:

1. **Vormen.** Een bericht krijgt een vaste opmaak op grond van zijn soort: Artikel, Pijler, Begrip of Tool. Kopkaart, pijlerregel, inhoudsopgave, auteurskaart, vervolg en gegevens voor Google komen vanzelf. Jij levert de inhoud.
2. **Bouwstenen.** 41 eigen Gutenberg-blokken (groep "Bouwstenen" in de editor) voor pagina's en artikelen: Sectie, Held, Kolommen, Kaart, Uitklap, FAQ, Samenvatting, Kader, Citaat, Inzicht, Definitie, Tool en meer.
3. **SFP Beheer.** Eén beheerscherm per site met onderdelen die aan of uit staan, vaste teksten, promokaart, banner, reviews, cursusdata, zelftest en onderhoud.

De plugin draagt geen merkkleur of font in zich. Alles loopt via kleurrollen (Astra global colors) en het kopfont van de site. De bron voor merkregels is `https://raw.githubusercontent.com/stephan-sfp/sfp-brand-source/main/branding.md`; lees die live.

## De acht sites

| Code | Domein | Taal | Stand vormen (3-11) |
|---|---|---|---|
| SFP | schoolforprofessionals.com | en, nl | artikelopmaak nog uit |
| DPS | depresenteerschool.nl | nl | artikelopmaak, pijler en begrip uit; tools en zelftest aan |
| DGA | degespreksacademie.nl | nl | idem DPS |
| CVD | centrumvoordidactiek.nl | nl | idem DPS |
| DST | deschrijftrainers.nl | nl | idem DPS |
| SLB | stephanborggreve.com | en, nl | begrippen heten Glossary op /glossary/ |
| SwS | speakwithsteve.com | en | alles aan; het model voor het netwerk |
| FL | falaliberada.com.br | pt-BR | tools aan |

De NL-sites gaan in de netwerktransformatie naar de SwS-vormgeving. Tot die tijd staan artikelopmaak, pijler en begrip daar uit en zie je er de oude Spectra-layouts. Nieuwe content schrijf je nu al volgens deze handleiding.

## Hoe je dit leest

- `01-vormen.md`: welke vorm je kiest en wat er dan vanzelf gebeurt.
- `02-bouwstenen.md`: elk blok, met wanneer, hoe en wat je niet doet.
- `03-automatische-onderdelen.md`: wat je nooit zelf bouwt.
- `04-editor.md`: panelen, controles en meldingen.
- `05-sfp-beheer.md`: instellingen per site en wat een redacteur aanraakt.
- `06-tooltips-en-bronnen.md`, `07-zelftest.md`, `08-tools.md`, `09-boekkaart-en-boeking.md`, `10-merkwaarden.md`, `11-gegevens-voor-google.md`.

Vaste begrippen: **vanzelf** betekent dat de plugin het maakt uit inhoud of instellingen; **blok** is iets dat jij in de editor plaatst; **instelling** staat in het paneel Deze pagina of in SFP Beheer.

## Drie regels die overal gelden

1. Koppen zijn altijd het gewone WordPress-kopblok (`core/heading`), zonder eigen kleur, font of grootte. Nooit een Spectra-kop, nooit ruwe HTML.
2. Hoofdletters typ je nooit; de opmaak zet labels en koppen zelf in kapitalen.
3. Wat vanzelf komt, bouw je niet na: geen eigen inhoudsopgave, kruimelpad, auteursregel, bronnenlijst of deelknoppen in de tekst.
