# Merkwaarden

De drie merkwaarden van het netwerk zijn Robuust, Eenvoudig, Aansprekend (en: Robust, Simple, Appealing; pt-BR: Robusto, Simples, Cativante). Ze gelden voor alles wat namens de merken naar buiten gaat, ook content. Bron: `branding.md`, Netwerknormen, Merkwaarden; de Engelse en Portugese versie in `merkwaarden.md` in dezelfde repo.

## Controlevragen bij elke tekst en elk ontwerp

- Robuust: kun je vertrouwen op wat je ziet? Alles past bij elkaar, elk onderdeel is op zichzelf te begrijpen, werkt op elk scherm; wat we beloven maken we waar. Grens: stevig zonder log of ingewikkeld te worden.
- Eenvoudig: vind je moeiteloos je weg? Eerst de kern, daarna meer in overzichtelijke stappen; wat afleidt laten we weg. Grens: eenvoud gaat nooit ten koste van de inhoud.
- Aansprekend: krijg je zin om verder te kijken? Rechtstreeks aanspreken, nieuwsgierig maken; fris, krachtig, energiek en positief. Grens: aandacht zonder te schreeuwen, te versieren of te joviaal te worden.
- In samenhang: denk aan LEGO, losse blokken die precies op elkaar passen. "Samen" is geen vierde waarde.

## Het blok Merkwaarden

**Merkwaarden** (`sfp/merkwaarden`, ook shortcode `[sfp_merkwaarden taal="nl|en|pt"]`) toont de bezoekerstekst van de merkwaarden live uit de merkbron (repo sfp-brand-source, branch main), twaalf uur bewaard, met de laatst gelukte versie en een meegeleverde kopie als terugval. Het blok zet zelf de H1 (NL "Onze merkwaarden", EN "Our brand values", PT "Nossos valores de marca"), de kernzin, de inleiding, de drie waarden met vraag, tekst en grens plus het slot "In samenhang / As a whole / Em conjunto" in tint 2 met slotzin en knop.

**Instellingen.** In het blok: taal (leeg: de taal van de site). In SFP Beheer, Onderdelen, Merkwaarden: per taal de slotzin, de knoptekst en de bestemming (pagina of adres); knop "Nu opnieuw ophalen". Zonder compleet slot ziet een beheerder een melding en een bezoeker geen knop (SwS 3-11: "Slot nog niet compleet").

## De merkwaardenpagina's (gepland na fase C)

| Site | Adres | Taal |
|---|---|---|
| SFP, DPS, DGA, CVD, DST, SLB | /merkwaarden/ | nl |
| SwS, SFP, SLB | /brand-values/ | en |
| FL | /valores-da-marca/ | pt |

Opbouw: pagina met alleen het blok Merkwaarden, layout volle breedte zonder paginatitel, footerlink bij Over. Dat is bouwwerk van WordPress Toolkit.

## Wat de redacteur ermee doet

- De tekst van de merkwaarden nooit overtypen in een pagina: het blok leest de bron. Wijzigt de tekst, dan in de merkbron (via Stephan), daarna "Nu opnieuw ophalen" en caches legen.
- De merkwaarden gebruiken als toets op eigen content, niet als marketingtekst in artikelen.
