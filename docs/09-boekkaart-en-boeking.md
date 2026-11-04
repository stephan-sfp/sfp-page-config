# Boekkaart en boeking

De boekkaart is de plek op een dienstpagina waar de bezoeker een gesprek boekt via LatePoint. Hij is sinds 3.24.0 een indeling van het blok Kolommen; de losse HTML van de oude DPS-kaart is niet meer nodig. Gemeten op SwS 377 en 1078 (indeling boeking, klasse sfp-boekkaart) en DPS 22577.

## Bouwen

1. Een **Sectie** (tint) met anker, bijvoorbeeld discovery-call.
2. Daarin **Kolommen**, indeling **Tekst en boekkaart** (opgeslagen als `boeking`). De kaart is wit met rand en schaduw, 45/55 vanaf 768 px, hoogstens 1140 px breed, radius 6 px.
3. Linkerkolom (tekst, op tint 2): een alinea in stijl **Label** boven de kop; de kop (H2, krijgt vanzelf 28 px); een alinea; een **Lijst** met vinkjes (drie punten, elk op één regel); onderaan een alinea in stijl **Wie**: portret (44 px) met naam en de rol als tweede regel (na een regeleinde of in een span na de naam).
4. Rechterkolom: het LatePoint-blok **Booking Form** (`latepoint/book-form`) met de dienst, Hide Side Panel en Hide Summary aan, plus de bron (source_id, bijvoorbeeld sws-coaching) voor de meting. Kalenderkop 28 px, zonder schaduw, verticaal gecentreerd.
5. Op telefoon: één kolom, bovenrand 3 px in de pijlrol, intro verborgen, vaste knop onderaan.

Teksten in de kaart (label, kop, zin, drie vinkjes, rol) zijn redactiewerk en gaan eerst langs Stephan. Netwerknorm voor de volgorde onderaan dienstpagina's: eerst discovery call (de boekkaart), dan FAQ, dan formulier.

## Wat vanzelf komt

- "Eerstvolgende plek" (Next available) en het Google-cijfer onder de naam komen uit het netwerksnippet LatePoint: boekkaart in merkrollen, zodra dat snippet de nieuwe klasse aanspreekt (overdracht open). Op SwS verschijnt geen Google-cijfer zolang SFP Beheer geen place_id heeft.
- Vaste mobiele knop die naar de kaart springt.
- Boekingsmeting via GTM.

## Wat je niet doet

- Geen `[latepoint_book_form]`-shortcode; gebruik het blok.
- Geen eigen kaart-HTML, geen sfp-boek-* klassen.
- Geen bedragen als tekst op SwS-dienstpagina's (prijzen via de calculator, besluit 18-10).
- Geen tweede boekkaart op dezelfde pagina.

## Pagina's met een boekkaart (SwS)

Home 377, Presentation coaching 378, Event speaker coaching 379, Team presentation training 380, About 381, Last-minute presentation 1078. Op de NL-sites de coach- en trainingspagina's; die worden omgezet in de netwerktransformatie.

## Werktijden en Vervolggesprek

De werktijden in LatePoint en een vaste pagina Vervolggesprek (voor contacten na een belronde) zijn lopend werk van Stephan en WordPress Toolkit, geen redactiewerk.
