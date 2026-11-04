# Zelftest en Zelftest-oproep

De zelftest is de grote leadmagnet van een site: veertig vragen, tien minuten, een persoonlijk rapport als pdf per e-mail via het CRM (SureContact). Elke site heeft één test ("de test van de site"). Live: DPS /presentatietest/ (Presentatietest), SwS /presentation-self-assessment/ (Presentation self-assessment). Gepland: SFP (NL en EN), DGA, CVD, DST, FL (door Nina).

De zelftest is geen tool. Hij staat niet op /tools en krijgt geen toolvorm.

## Onderdelen

| Onderdeel | Wat | Wie |
|---|---|---|
| Testpagina | Pagina met alleen het blok **Zelftest** (`sfp/zelftest`, attribuut test = de code van de site). Layout volle breedte zonder paginatitel; het blok zet zelf de H1 (de testtitel). De pdf staat op `<testpagina>/result/` (NL: /resultaat/, PT: /resultado/) | Eén keer gebouwd door Claude |
| Testpakket | De vragen, blokken, rekenregels, profielen en resultaatteksten, als pakket ingelezen in SFP Beheer, Onderhoud, Zelftest | Claude bouwt, Stephan en Nina keuren de inhoud |
| Instellingen | SFP Beheer, Onderdelen, Zelftest: testpagina kiezen, logo op de testpagina, proefmodus, husselen (kernvragen en antwoorden willekeurig, zonder bloknaam; netwerknorm aan), oproeptekst (kop, zin van hoogstens 220 tekens, knoptekst) | Stephan |
| Meting | Events zelftest_start en zelftest_afgerond naar GA4 met test, lengte, taal en bronlabel | Vanzelf |
| Tip-artikelen | De pdf linkt per tip naar een vast artikel; de slugs staan in het pakket en mogen niet wijzigen | Redactie van die artikelen: zie B, planning |

## Zelftest-oproep (blok)

Het blok **Zelftest-oproep** (`sfp/zelftest-cta`) is de oproep om de test te doen: label, kop, één zin en een knop, gecentreerd, hoogstens 760 px breed, zonder icoon.

**Instellingen in het blok.** Test (de test van de site); Bronlabel (komt als `?bron=` in de link, voor GA4; leeg: soort pagina en adres, bijvoorbeeld pagina-presenteren-training); Icoon tonen (standaard uit).

**Tekst.** Kop, zin en knoptekst staan in SFP Beheer, Zelftest en gelden voor elke oproep en voor de promokaart en kopkaart van een pijler. Je typt in het blok niets.

**Waar wel.** Leadmagnetplek op trainings- en coachpagina's (niet incompany); onderaan artikelen waar het past; op de home (SwS 377, bronlabel home). Naast een beeld in Kolommen staat de oproep links uitgelijnd (klasse met-beeld).

**Waar niet.** Op toolpagina's en op /tools (de test is geen tool). Niet twee keer op één pagina. Niet in een artikel waar het vervolg al de zelftest toont.

**Mobiel.** De knop onderaan (dienstpagina's) wijkt zolang de oproep of een volle knop in beeld is.

**Bronlabel.** Kies per plaatsing een vast label (bijvoorbeeld artikel-slug of pagina-coaching), zodat GA4 laat zien welke plek tests oplevert. Geen spaties, geen hoofdletters.

## "Zelftest per pijler" in het SwS-clusterplan

Het clusterplan van 17-10 noemt per cluster een zelftest (What kind of speaker are you?, Presentation nerves check, How clear is your talk?, How persuasive is your pitch?). Dat zijn tools van de soort test op de toolmotor (08), geen tweede grote zelftest. Later is besloten (28-10, editor-panelen-en-beheer, wens 3): geen aparte pijlertests meer; overal de ene zelftest van de site via Zelftest-oproep, kopkaart en promokaart. De vier pijlertests uit het plan staan daarmee geparkeerd met de andere SwS-tools (content-12). De Thermometer (02) is iets anders: een kort instrument op een pijler, als knop in de kopkaart, zonder uitslagrapport.

## Pijler en zelftest

Een pijler toont de zelftest vanzelf: als actie in de kopkaart en als promokaart. Zonder zelftest valt de pijler terug op de promokaart per vorm. De redacteur plaatst op een pijler dus geen Zelftest-oproep.
