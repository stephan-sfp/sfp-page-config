# Bouwstenen: de blokken van SFP Page Config

Alle eigen blokken staan in de editor in de groep **Bouwstenen** (`sfp/...`). Gemeten op SwS 3.26.1: 41 blokken. Opmaak komt uit kleurrollen en kopfont; je kiest nooit een kleur, font of grootte. Een blok met een onderliggend blok (zoals FAQ met FAQ-vraag) vul je door het onderliggende blok toe te voegen met de plus in het blok.

Per blok: instellingen in het zijpaneel, wanneer, hoe, wat je niet doet.

## Paginabouw

**Sectie** (`sfp/sectie`). Band over de volle breedte. Instelling: Achtergrond Wit of Tint; Link naar dit blok (anker kopiëren). Wanneer: elk deel van een pagina is één sectie; wit en tint afwisselen. Niet: secties in secties; geen anker typen, de plugin maakt hem uit de kop.

**Held** (`sfp/held`). Bovenkant van een pagina: titel, intro, beeld en knoppen typ je in het blok. Instelling: Beeld "Tekst naast beeld" of "Beeld over de volle breedte". Op telefoon valt de intro weg. Een blok Tool in de tweede kolom maakt een tool-held (SwS /last-minute-presentation/). Niet: een H1 toevoegen; de titel in de held is de H1.

**Kolommen** (`sfp/kolommen`) met **Kolom**. Indeling: Twee gelijk, Drie gelijk, Tekst en beeld, Tekst en boek, Tekst en boekkaart (de boeking, zie 09), Breed en smal. Verticale uitlijning: Zoals de indeling, Boven, Midden, Onder, Even hoog. Op telefoon onder elkaar. Kolom heeft de stijl **Wachtlijst** (veld en knop op één regel) voor een SureForms-wachtlijstformulier. Niet: core/columns gebruiken.

**Kaart** (`sfp/kaart`). Vorm: Met accentrand, Vlak, Donker. Opties Ruim (meer ruimte op groot scherm) en Smal en in het midden (bijvoorbeeld voor een formulier). "De hele kaart gaat naar": een adres maakt de hele kaart klikbaar; leeg: alleen de links erin. Wanneer: dienstkaarten (drie naast elkaar), "wat je overslaat"-kaarten, uitleg. Niet: een kop in de kaart als H2 opmaken om hem groter te maken; een kaarttitel volgt vanzelf de H3-schaal.

**Stappen** (`sfp/stappen`) met **Stap**. Genummerde stappen; kop en tekst per stap typ je in de stap. Wanneer: werkwijze, een methode in drie tot zes stappen.

**Tabs** (`sfp/tabs`) met **Tab**. Instelling: Naam voor schermlezers. Klik op een tab om die te bewerken; de plus voegt een tab toe. Wanneer: drie gelijkwaardige invalshoeken (SwS home: Credibility, Clarity, Connection). Niet: meer dan vier tabs.

**Lijst** (`sfp/lijst`). Stijl: vink (standaard), kruis, nummers, regels, letters. De punten typ je als gewone lijst erin. Harde regel op pagina's: elk punt past op een telefoon op één regel. Letters: voor een model met letters (F A S T E R). Niet: core/list met eigen opmaak.

**Uitklap** (`sfp/uitklap`) met **Uitklapregel**. Vorm: Grote regels, Lees meer (compact), Kaarten, Verhaal (in een artikel, met een label boven de titel). Alleen bij Grote regels, Lees meer en Kaarten: schakelaar "Een regel tegelijk open". Uitklapregel: titel, bij Verhaal ook sub (teaser) en label ("A typical case (fictional)"); Link naar dit blok. Wanneer: FAQ-achtige details op een pagina (Grote regels), lange uitleg inkorten (Lees meer), een anekdote in een artikel (Verhaal). Niet: een verhaal direct naast In een presentatie; een verhaal dat begint met "Imagine".

**FAQ** (`sfp/faq`) met **FAQ-vraag**. Vraag typ je in de regel, antwoord eronder. Geeft FAQPage-schema. Wanneer: als laatste hoofdstuk, minstens drie echte vragen; op een begrip alleen als er minstens twee vragen zijn die de definitie niet beantwoordt. Niet: een FAQ om de FAQ; vragen in hoofdletters.

**Logostrook** (`sfp/logostrook`). Logo's uit de mediabibliotheek via "Logo's wijzigen"; volgorde en alt-tekst in de mediabibliotheek; label leeg toont de standaardtekst. Wanneer: klantlogo's onder de held.

**Review** (`sfp/review`). Eén eigen review als kaart met sterren; kies uit de lijst (bron: SFP Beheer, Reviews, Eigen reviews). Wanneer: één review lager op de pagina als sociaal bewijs. **Reviews** (`sfp/reviews`): alle reviews, weergave slider, grid, banner of alleen cijfer; samenvatting ja of nee.

**Zelftest-oproep** (`sfp/zelftest-cta`). Zie 07. **Tool** (`sfp/tool`). Zie 08. **Merkwaarden** (`sfp/merkwaarden`). Zie 10.

**Kopieerblok** (`sfp/copy`). Tekst die de lezer met één klik kopieert, zoals een prompt; lange tekst ingeklapt met Toon alles. Taal (code) kiezen voor codefragmenten.

**Startmomenten** (`sfp/cursusdatum`), **Lestijd** (`sfp/lestijd`). Alleen op trainingspagina's met het onderdeel Cursusdata aan: data en lestijd van de groep uit SFP Beheer. Instelling groep, toon alle of eerste, indeling regel of lijst. **Leestijd** (`sfp/leestijd`): leesduur van de pagina.

## Artikelbouwstenen

**Samenvatting** (`sfp/samenvatting`). "What you need to know": drie of vier punten in een kaart met deelbaar beeld. Label en punten typ je in het blok; Onderwerp op het beeld (leeg: de titel). Wanneer: in elk artikel direct na de lead; niet op een pijler of begrip. Elk punt een volledige bewering van circa 70 tekens.

**Kader** (`sfp/kader`). Soort: Wist je dat, Kanttekening, Grondslag, Reflectievraag, Checklist, Eigen label of geen. Alle soorten hebben dezelfde vorm; alleen het label verschilt. De kaderkop is geen kop in de structuur. Wanneer: een feit of nuance die buiten de lijn van de tekst valt.

**Citaat** (`sfp/citaat`). Citaat of anekdote: een vierkant icoontje met aanhalingstekens in het secundair, het citaat groot in het kopfont, de bronregel in de bodykleur eronder, zonder kader (sinds 3.29.0); foto van de auteur (aan/uit) en een bijschrift dat zegt van wie en waarover. Wanneer: een ervaring uit de eigen praktijk. De stijl **Uit de trainingspraktijk** op core/quote (praktijkblok) doet hetzelfde voor een trainerservaring met naam en merk eronder.

**Inzicht** (`sfp/inzicht`). Eén kernzin als deelbaar beeld met deelknoppen; de server maakt het beeld bij opslaan. Zet het woord dat moet opvallen cursief: dat krijgt de accentkleur. Het eerste inzicht wordt het beeld om het artikel te delen. Wanneer: één keer per artikel, bij de kerngedachte.

**Lees ook** (`sfp/lees-ook`). Kaart naar een ander artikel; titel en beeld komen vanzelf. Kies het artikel of plak een adres. Op een pijler krijgt hij het label Go deeper. Niet: een verwijszin in de tekst ("lees ook ons artikel over...").

**Warming-up** (`sfp/warming-up`) met **Warming-upvraag**. Drie vragen met drie antwoorden en het hoofdstuk waar het antwoord staat (kies uit de koppen; hernoem je de kop, dan blijft de verwijzing kloppen). Staat in de editor bovenaan, op de site als knop in de kopkaart. Alleen op artikelen.

**Thermometer** (`sfp/thermometer`) met **Stelling**. Korte test die de lezer bij het probleem van de pijler betrekt: kop, uitersten links en rechts, drie uitslagen met label en anker. Op de site een knop in de kopkaart van de pijler. Alleen op pijlers.

**Vervolg** (`sfp/vervolg`) met **Vervolgvlak**. Slotblok met een of twee vlakken (label, kop, tekst, link of knop). Je bewerkt het niet in een artikel maar in het item "Vervolg per pijler" (SFP Beheer, Artikelen); de plugin plaatst het vervolg van de pijler onder elk artikel, begrip en pijler.

**Bronnenlijst** (`sfp/bronnenlijst`). Alle bronnen uit de tooltips van de site op alfabet met zoekveld. Alleen op de bibliografiepagina.

## Begripbouwstenen

**Definitie** (`sfp/definitie`), **In een presentatie** (`sfp/in-een-talk`), **Check je begrip** (`sfp/begrip-check`) met **Vraag**. Zie 01, Begrip.

## Alinea-, kop-, beeld- en knopstijlen

Stijlen kies je in het zijpaneel onder Styles. Kleur, achtergrond, verloop en lettergrootte staan in artikel, begrip en tool uit.

| Blok | Stijl | Gebruik |
|---|---|---|
| Alinea | Lead (eerste alinea van een artikel) | De eerste alinea van artikel en pijler |
| Alinea | Pijllink | Tweede call-to-action als tekstlink met pijl, op gewicht 700 (sinds 3.27.0; was 900). In een kaart (de aanwijzing onder in een klikbare kaart) in het bodyfont en altijd onderstreept, zoals branding.md de tweede call-to-action beschrijft (sinds 3.27.1). Buiten een kaart in het kopfont van de site. |
| Alinea | Klein | Kleine toelichting |
| Alinea (pagina's) | Label, Intro, Bovenschrift, Wie, Mailregel, Groot, Getal, Prijs | Label boven een kop; introzin in een held; opschrift boven een verhaal; naam en rol bij een portret (boekkaart); mailregel; grote cijfers; getal in een pakket; prijs. Gemeten in gebruik op SwS 377 en 1078 |
| Kop | Paginatitel over de volle breedte | Een H1 buiten de held, bijvoorbeeld op de boekpagina |
| Afbeelding | Afgerond, Boekomslag | 6 px radius; omslag zonder radius |
| Knop | Fill, Outline, Licht (op donker), Tweede (op licht), Tekstlink | Eén volle knop per blok; de tweede actie als Tekstlink (`is-style-sfp-tekst`; dit is een knopstijl, geen alineastijl) |
| Citaat | Uit de trainingspraktijk | Praktijkblok |
| Kolom | Wachtlijst | Veld en knop op één regel |

Koppen: altijd `core/heading`, opvulling en kleur blijven leeg. Het koptype volgt de structuur, niet de gewenste grootte.
