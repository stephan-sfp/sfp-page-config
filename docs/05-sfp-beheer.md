# SFP Beheer

Het beheerscherm van de plugin: wp-admin, menu **SFP Beheer** (`admin.php?page=sfp-page-config`). Gemeten op SwS 3.26.1. Vijf tabs: Overzicht, Pagina's, Onderdelen, Teksten, Onderhoud.

## Wat een redacteur hier doet

Weinig. De instellingen zijn per site en bepalen wat alle pagina's tonen. Een contentmarketeer kijkt in **Overzicht** (wat vraagt aandacht) en **Pagina's** (per pagina de soort, de knop onderaan en wat er ontbreekt). Instellingen wijzigen, onderdelen aan- of uitzetten en Onderhoud: alleen in overleg met Stephan; dat werk ligt bij WordPress Toolkit.

## Overzicht

"Wat op deze site je aandacht vraagt": per pagina de ontbrekende dingen (sjabloontekst niet ingevuld, geen uitgelichte afbeelding, geen pijler, geen formulier na de uitslag), met knoppen Bekijken en Oplossen. Daaronder: aantal onderdelen aan en uit, afwijkingen van de netwerknorm (SwS 3-11: tekst bij WhatsApp, kleur en tekstkleur van de banner), versie.

## Pagina's

Alle pagina's, artikelen, begrippen en tools met hun soort (Gewone pagina, Coaching, Incompany, Artikel, Begrip) en status (In orde of wat ontbreekt). Knop Controleer opnieuw. Per pagina kun je de knop onderaan anders instellen dan de soort voorschrijft.

## Onderdelen

Elk onderdeel staat Aan of Uit; de meeste hebben Instellen. Stand SwS 3-11: 23 aan, Cursusdata uit. Stand DPS: Artikelen, Pijler en Begrip uit (tot de omzetting), de rest aan.

| Onderdeel | Wat het doet | Instellen |
|---|---|---|
| Knop onderaan | Op telefoon één knop op dienstpagina's | Tekst en bestemming per soort (Coaching, Training, Incompany); leeg: netwerktekst |
| Artikelen | Kopkaart, inhoudsopgave, auteurskaart en vervolg bij elk artikel | Kopkaart (actie Voorkeursbron bij Google; leestekens in de titel; pagina's factchecken, redactionele verantwoording, bibliografie), Promokaart (kaart van de site, tweede kaart, per vorm), Vervolg per pijler (per categorie Vervolg maken of Bewerken), Affiliatelinks (patronen) |
| Reviews | Google-reviews en eigen reviews | Weergave (één tegelijk, naast elkaar, namen met sterren, alleen cijfer), vanaf hoeveel sterren, cijfer boven de reviews; Eigen reviews; Koppeling met Google (place_id) |
| Tooltips | Uitleg bij een woord | Niets; een tooltip maak je in de editor |
| Footerlinks | Groepen links in de footer | Groepen met naam, pagina's of andere links, hoogstens zes |
| Voortgangsbalk | Balk bovenaan | aan of uit |
| WhatsApp-knop | Zwevende knop per pagina | Nummer en eerste bericht (Teksten) |
| Cursusdata | Startmomenten van trainingen | Groepen met data, plaats, prijs; uit op SwS |
| Zelftest | De zelftest van de site | Zie 07 |
| Pijler, Begrip | De vormen | aan of uit |
| Online tools | Tools op de motor | Adres voor tekstfeedback; lijst van tools met soort, meldingen, duimpjes |
| Kleurrollen | Welke Astra-paletkleur elke merkrol draagt | Per rol een paletkleur of Niet gevoerd; de kleuren zelf staan in Astra. Sinds 3.29.0 ook de rol Label (bloklabels zoals Inhoud en Samenvatting): norm het primair, zonder keuze valt hij daarop terug |
| Kopfont als variabele | Kopfont beschikbaar voor kaarten, reviews en tools | aan of uit |
| Berichttypes | Begrippen en tools als eigen berichttype met adres en naam | aan of uit |
| Paginabeheer voor AI | Lezen en bijwerken via de MCP-koppeling, met revisiecontrole | aan of uit |
| Banner | Eén regel boven de header | Kleur, aanbod met periode en plaatsing, cursusbanner |
| Contact | Gegevens voor het blok Contactgegevens (3.35.0) | Telefoon (leeg: het nummer van de USP-strook, anders van het netwerk; altijd getoond als 085 060 1355), e-mail (leeg: info@ op het domein), adres (leeg: geen adres) |
| Kopieerblok, Bronnenlijst, Praktijkblok, Auteursbio | Blokken en opmaak | aan of uit |
| Merkwaarden | Merkwaarden live uit de merkbron | Zie 10 |
| Formulierbewaking | Dagelijkse formuliercontrole | aan of uit |
| Snippets beheren op afstand | Onderhoud door Claude | aan of uit |

## Teksten

Vaste teksten die bezoekers zien, per groep (Knop onderaan, Artikelen, Leestijd en inhoudsopgave, Reviews, Logostrook, Kopieerblok, Bronnenlijst, Praktijkblok, Tooltips, Formulieren en cursusdata, Banner, Feitencodes, WhatsApp, Merkwaarden, Zelftest). In elk veld staat de standaardtekst in de taal van de site; je typt er alleen iets in als het anders moet. Voorbeeld SwS, Artikelen: What you need to know, Contents, Back to top, Fact-checked, Editorial statement, In a talk; aangepast: de affiliateregel en -uitleg.

Een redacteur die een vaste tekst anders wil (bijvoorbeeld het label van de samenvatting), vraagt het aan Stephan; het is een netwerkkeuze.

## Onderhoud

Versie en updates, resten van oude plugins, boekformulier sneller, beelden om te delen, koppeling met Google voor reviews, kennisbanken voor `[sfp_publicaties]`, zelftest (pakket inlezen, CRM, pdf-maker, wissen, logboek), snippets. Niet voor redactiewerk.

## Promokaart: hoe hij werkt

- **Kaart van de site**: beeld (omslag of ronde foto), label, titel, tekst, linktekst, bestemming. Zonder titel of bestemming geen kaart. SwS: het boek The FASTER Method.
- **Tweede kaart**: dezelfde velden, voor een tweede aanbod.
- **Per vorm**: Artikel, Pijler, Begrip, Tool elk De kaart van de site, De tweede kaart of Geen kaart. Een pijler toont de zelftest van de site op de plek van het boek; de keuze geldt alleen als er geen zelftest is. Een tool heeft standaard geen kaart.
- De kaart staat in de zijkolom en één keer in de tekst (inline), op een vaste plek die de plugin kiest. Je plaatst hem niet zelf.
