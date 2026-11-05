# Tooltips, bronnen, bronnenlijst en citation

## Tooltip

Een tooltip legt een woord uit of geeft de bron van een bewering, zonder de tekst te onderbreken. Het woord krijgt een stippellijn; de uitleg verschijnt bij aanwijzen, tikken of tabben.

**Hoe.** Selecteer in de editor een woord of zinsdeel en klik in de werkbalk op **Tooltip** (formaat `custom/tooltip`). Typ de uitleg of de bronregel. Opgeslagen als `<span class="tooltip" data-tooltip="...">woord</span>`.

**Regels.**

- Een tooltip staat nooit in een link; een link staat nooit in een tooltip.
- Hoogstens één tooltip per zin; hij onderbreekt het lezen niet.
- Een tooltip is gewone tekst, geen opmaak.

## Begrip-markering

Voor een term uit de woordenlijst gebruik je niet Tooltip maar **Begrip** (formaat `sfp/begrip`): selecteer het woord, klik Begrip, kies het begrip. Je typt niets: de definitie en "Read more" komen uit het blok Definitie van dat begrip; het artikel verschijnt bij het begrip onder "Used in". Werkt alleen voor begrippen die op de site gepubliceerd zijn: de keuzelijst toont geen concepten.

**Zolang het doelbegrip nog concept is.** Markeer het woord nog niet als Begrip. Gebruik de tijdelijke link die de begrippenworkflow kent: `<a href="#future-link" data-future="begrip-<slug>">woord</a>` (gemeten op SwS 863: `data-future="begrip-glossophobia"`). Zodra het begrip live staat, vervangt Claude die link door een Begrip-markering. Een gewone link naar een conceptadres geeft de bezoeker een 404; doe dat niet.

## Drie dingen op één plek: bron, begrip, interne link

Ze bestaan naast elkaar in één alinea, maar nooit op hetzelfde woord:

- *Bron* op het zinsdeel dat de bewering draagt: "Their guess was <span tooltip>46 per cent</span>" met `Source: Gilovich, T., ...`.
- *Begrip* op de vakterm: "<span sfp-begrip>self-focused attention</span>" (definitie en Read more komen vanzelf).
- *Interne link* op bestaande woorden naar een artikel of pijler: "how to <a>calm your nerves before a talk</a>". Geen verwijszin ("read our article on..."), geen link naar diensten in de tekst.

Volgorde van voorkeur als twee elkaar raken: de bron hoort bij de bewering, de begrip-markering bij de term, de link bij het onderwerp. Staat een term in een zin met een bron, zet de bron dan op het cijfer of de uitkomst, niet op de term.

## Bronnen in tooltips

Elke feitelijke bewering met cijfer, onderzoek of citaat krijgt een bron als tooltip op het woord of zinsdeel. De plugin herkent een bron aan het openingswoord van de tooltip: **Bron:** op de NL-sites (de editorhulp bij het blok Bronnenlijst zegt letterlijk "een regel die begint met Bron:"), **Source:** op SwS (gemeten op 759: "Source: Gilovich, T., Medvec, V. H., & Savitsky, K. (2000). ..." staat in de bronnenlijst). FL: nog niet te meten; FL heeft op 4-11 geen artikelen of brontooltips. De plugin kent wel Portugese bronnenlijstteksten (Bibliografia, Citado em). Of de plugin op FL `Fonte:` herkent voor de bronnenlijst én voor citation staat in de plugincode; de vraag staat uit bij SFP Plugin Development (overdracht 4-11) en is op de site pas te meten zodra FL een artikel met een brontooltip heeft. Tot het antwoord er is: schrijf op FL `Fonte:` en laat Claude na publicatie de bronnenlijst en de JSON-LD controleren. Alles achter het dubbele punt is de bronregel. Uit alle brontooltips maakt de plugin:

- "Sources (n)" in de kopkaart en zijkolom;
- de bronnenlijst onderaan het artikel;
- citation in het Article-schema (CreativeWork met de bronregel als naam en de link of DOI als url);
- de bibliografiepagina van de site (blok Bronnenlijst): alle bronnen op alfabet met zoekveld en "Geciteerd in".

**Schrijfconventie (netwerk, NL).** Volledig in de tooltip, met paginanummer en raadpleegdatum; de plugin haalt die weg voor de bibliografie.

- Auteurs: `Dijk, B. van`; `Anderson, L. W.`; twee auteurs met `en`, drie of meer met komma's en `en`; geen `&` of `and`.
- Rechte enkele aanhalingstekens: `'Titel van het artikel'.` daarna `Tijdschrift, 35(3).` (jaargang en nummer, zie de tabel). Boektitels zonder aanhalingstekens: `Titel: Ondertitel.`
- Plaats en uitgever: `Amsterdam: Boom.`; Nederlandse stadsnamen waar die bestaan (Londen, Parijs).
- Volume en nummer zonder spatie: `63(6)`.
- Pagina's: `, p. 28` of `, pp. 12-15`. Raadpleegdatum: `Geraadpleegd op 15-04-2026.`

**Vaste volgorde van een bronregel.** Auteur(s) (jaar). Titel. Uitgever of tijdschrift met jaargang(nummer), pagina. Raadpleegdatum. Link of DOI als laatste.

**Wanneer welk element.**

| Element | Wanneer | Vorm |
|---|---|---|
| Paginanummer | Alleen als de bewering op een specifieke plek in een boek of lang rapport steunt; nooit bij een tijdschriftartikel dat je als geheel aanhaalt | `, p. 28` of `, pp. 12-15`; alleen in de tooltip, de bibliografie laat het weg |
| Raadpleegdatum | Alleen bij een webpagina zonder vaste datum of versie (geen DOI, geen jaargang) | `Geraadpleegd op 15-04-2026.`; vóór de link; alleen in de tooltip |
| `63(6)` | Tijdschrift met jaargang en nummer: jaargang, nummer tussen haakjes, geen spatie | `Tijdschrift voor Taalbeheersing, 35(3)` |
| `vol.` en `nr.` | Niet in nieuwe bronnen. Het oude snippet herkende artikelen aan `vol.`, `nr.` of `doi:`; de plugin leest de regel als geheel. Bestaande regels met `vol.` laat je staan | |
| DOI of link | Altijd als die er is, als laatste element, ook na de raadpleegdatum; de plugin maakt er de url in citation van | `https://doi.org/...` |

Boek met paginaverwijzing (geen raadpleegdatum: een boek heeft een vaste editie): `Bron: Kuenen, J. en Wackers, M. (2012). Presenteren: wat werkt echt en wat echt niet? Onderbouwde adviezen over presentatietechnieken. Amersfoort: Communicatiereeks, p. 44.`

Webpagina met raadpleegdatum en link (geen vaste datum of versie): `Bron: Onze Taal (z.d.). 'Wat is een meewerkend voorwerp?'. Geraadpleegd op 15-04-2026. https://onzetaal.nl/taalloket/meewerkend-voorwerp`

Tijdschrift NL: `Bron: Hoeken, H. en Sinkeldam, J. (2013). 'De rol van emoties in narratieve overtuiging'. Tijdschrift voor Taalbeheersing, 35(3).`

Voorbeeld EN (zoals live op SwS): `Source: Gilovich, T., Medvec, V. H., & Savitsky, K. (2000). The spotlight effect in social judgment: An egocentric bias in estimates of the salience of one's own actions and appearance. Journal of Personality and Social Psychology, 78(2), 211-222. https://doi.org/10.1037/0022-3514.78.2.211`

Open punt (besluit Stephan): de Engelse conventie is nog niet vastgelegd zoals de Nederlandse. SwS gebruikt nu APA met `&`. Zie B, 06 open vragen.

**Waarom één stijl.** Twee schrijfwijzen van dezelfde bron worden twee regels in de bibliografie. Wissel nooit tussen `Van Dijk` en `Dijk, van`, tussen `&` en `en`, tussen wel en geen ondertitel, tussen Londen en London.

## Vuistregel-label (NL-sites)

Een praktijkgetal zonder wetenschappelijke bron krijgt in de tekst het woord "vuistregel" tussen haakjes direct na het getal, met een tooltip met de vaste herkomstzin van het merk en het kenmerk `data-vuistregel="<merkcode>"`. Op de site toont de plugin (sinds 3.27.0) op die plek een informatie-icoon (rondje met een i, tikdoel 24 px) met dezelfde tooltip; de haakjes en het woord verdwijnen uit beeld, het woord blijft als tekst voor schermlezers staan. Je typt dus nog steeds het woord tussen haakjes; de omzetting gebeurt vanzelf, in elke taal. Geen aantal trainingen noemen. Geen label bij getallen met een bron (die worden toegeschreven), bij retorische getallen (herschrijven naar "meestal") en in fictieve casussen.

Vaste zinnen (norm 11-10-2026, `claude/vuistregel-label-norm-2026-10-11.md`): SFP, DPS, DGA, CVD, DST elk "Vuistregel uit de trainingspraktijk van <merknaam>, geen wetenschappelijke norm." (DPS goedgekeurd en uitgevoerd; de andere vier volgen het patroon). SwS "Rule of thumb from our coaching practice, not a scientific standard." (bevestigd door Stephan, 4-11-2026). FL "Regra prática da nossa experiência em treinamentos, não uma norma científica.": formulering nog te bevestigen door Stephan. SLB: nog vast te stellen.

Voorbeeld: `Deel je verhaal op in stukken van hooguit vijf minuten (<span class="tooltip" data-tooltip="Vuistregel uit de trainingspraktijk van De Presenteerschool, geen wetenschappelijke norm." data-vuistregel="dps">vuistregel</span>).`

## Affiliatelinks

Een link die op een patroon uit SFP Beheer past (SwS: partner.bol.com) zet vanzelf "Contains affiliate links" in de kopkaart en de uitleg onder het artikel. Je schrijft die melding niet zelf. Affiliatelinks in de lopende tekst zijn toegestaan; links naar eigen diensten in de lopende tekst niet.
