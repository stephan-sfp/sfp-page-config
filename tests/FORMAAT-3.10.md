# Beschrijvingsformaat 3.10.0 (aanvulling op de generator van 3.9.0)

Voorbeelden van 3.9.0: `tests/tool-voorbeelden/*/*.json` met `"soort": "generator"`. Lees er minstens twee (bijv. `dga/slechtnieuwsgesprek-script.json`, `dst/rapport-outline-tool.json`). De code: `includes/generator.php`, `includes/reken.php`, `includes/tekstanalyse.php`, `assets/tool/generator.js`, `assets/tool/tekstanalyse.js`. Testen: `php tests/generator.php` (bouwt elke beschrijving met soort generator, rekenaar of checklist in PHP en JS en vergelijkt; meldt fouten in de beschrijving).

## Soorten

- `generator`, `rekenaar`, `checklist`: dezelfde motor (stappen, velden, uitvoer). De soort bepaalt alleen het label en kleine dingen in de weergave (checklist toont "x van y afgevinkt").
- `tekstanalyse`: eigen motor, zie onder.

## Nieuw in de generator-motor

### Velden
- Type `getallen`: tekstvak met losse getallen (spatie, komma, puntkomma of regel ertussen). `min`/`max` begrenzen; getallen erbuiten vallen weg.
- `getal`: ook `min`, `max` (mag negatief/decimaal), `stap`, `eenheid` (tekst achter het veld).
- Opties kunnen getallen dragen: `{ "waarde": "zorg", "tekst": "Zorg", "getallen": { "gem": 8, "top25": 20 } }`. In formules: `sector.gem` (bij vinkjes: som over gekozen opties).

### Rekentaal (formules)
- Getallen met punt (`2.5`), tekst tussen dubbele aanhalingstekens, `+ - * / ( )`, vergelijken `= != < <= > >=`, `en`, `of`, `niet`.
- Functies: `min, max, abs, afronden(x[,dec]), omlaag, omhoog, als(voorwaarde, dan, anders), som(lijst), aantal(lijst of regels), tel(lijst, van, tot), bevat(lijst of tekst, waarde), woorden(tekst)`.
- Namen: veld-id's (getal geeft een getal, getallen een lijst, vinkjes een lijst, keuze de waarde), `veld.kolom`, eerdere `reken`-id's, `oordeel` (sleutel), `oordeel_tekst`; in kaarten `item.x`, `nr`, `groep`, `groepen`.
- Afronden gaat de helft van nul af (-12,5 wordt -13). Delen door nul of een leeg veld geeft leeg.

### Bovenaan de beschrijving
- `reken`: `[ { "id": "totaal", "formule": "p + pas + d" } ]` in volgorde.
- `controles`: `[ { "formule": "totaal < 1", "melding": "Vul ...", "stap": 1 } ]` blokkeert die stap (en de mail) zolang de formule waar is. `stap` telt vanaf 1, standaard de laatste.
- `meldingen`: zelfde vorm, maar alleen een tip onder de velden van die stap; blokkeert niet.
- `oordeel`: `{ "op": "score", "labels": [ { "vanaf": 31, "sleutel": "sterk", "tekst": "Sterk" }, ... ] }`; het eerste label (hoogste vanaf) waar de uitkomst aan voldoet.
- `live: true`: één stap, de uitkomst staat onder de velden en rekent mee bij elke toets (geen knop).

### In sjablonen
- `{naam}` werkt ook voor reken-id's, `oordeel`, `oordeel_tekst`, `veld.kolom`, `item.x`, `nr`.
- `{=formule}`: een formule midden in tekst (geen `{`, `}` of `|` in de formule).
- Opmaakfilters op getallen: `|getal:1` (decimalen, scheidingstekens van de sitetaal), `|euro` / `|euro:2`, `|procent:1`, `|teken` (+ voor positieve), `|minsec` (seconden naar m:ss), `|klok` (minuten naar u:mm).
- Geen geneste plaatshouders in filters; gebruik `|of:andereNaam` voor terugval op een andere waarde.

### Voorwaarden
- Naast `{veld, is|niet|in|gevuld|bevat}`: `{ "formule": "dagen.n >= 7" }`.

### Nieuwe secties in `uitvoer`
- `getal`: `{ "type": "getal", "label": "Jouw eNPS", "waarde": "score", "formaat": "teken", "onder": "{oordeel_tekst}" }`.
- `verdeling`: `{ "type": "verdeling", "kop": "...", "delen": [ { "label": "Promoters", "waarde": "p" } ], "formaat": "getal" }`; toont balken met aantal en percentage.
- `kaarten`: herhaalt `inhoud` per kaart.
  - Bron: `bank` (+ `pad`) met een lijst objecten (of teksten, die worden `{ "tekst": ... }`), of zonder bank alleen `aantal` lege kaarten.
  - `filter` (formule per item), `voorkeur: { formule, minimaal }` (als minstens zoveel items voldoen, alleen die), `terugval: { filter, aantal }` (als er niets over is), `sorteer` (formule, oplopend; `aflopend: true`), `aantal` (formule).
  - `om_en_om: { veld, sleutel }`: k-de kaart = categorie `gekozen[k mod n]`, item `floor(k/n) mod aantal-in-categorie` uit de items met `item[sleutel] = categorie`. Gekozen categorieën staan in de volgorde van de opties.
  - `groeperen: { per: "formule", kop: "Dag {groep} van {groepen}" }`: kop boven elke groep van `per` kaarten.
  - `kaartkop`: sjabloon per kaart (subkop). `leeg`: tekst als er geen kaarten zijn.
- `afvink`: items mogen een vaste `id` hebben (`{ "id": "oef-hardop", "tekst": "...", "als": {...} }`); de bezoeker vinkt aan, de stand gaat mee in deellink, browser en mail.

### Acties
Zoals 3.9.0: `pdf, word, kopieer, kopieer_kaal, deellink, mail, duimpjes, formulier, formulier_velden, pdf_bestandsnaam`. Mail stuurt alleen keuzes, getallen en korte velden mee (tekstvakken niet, tenzij `mail: true` op het veld).

## Tekstanalyse

```json
{
  "soort": "tekstanalyse",
  "resultaat_kop": "...",
  "invoer": { "label": "Plak je tekst", "hint": "...", "voorbeeld": "...", "min_tekens": 50, "max_tekens": 5000 },
  "afkortingen": [ "bijv.", "o.a.", "d.w.z.", "enz.", "ca.", "mr.", "dr." ],
  "lijsten": { "jargon": [ "middels", "ten behoeve van" ] },
  "per_zin": [ { "id": "jargon", "meet": "lijst", "lijst": "jargon", "label": "Jargon" }, { "id": "passief", "meet": "passief", "label": "Lijdende vorm" } ],
  "klassen": { "op": "woorden", "labels": [ { "vanaf": 26, "sleutel": "rood", "tekst": "Te lang" }, { "vanaf": 16, "sleutel": "oranje", "tekst": "Lang" }, { "vanaf": 0, "sleutel": "groen", "tekst": "Goed" } ] },
  "reken": [ { "id": "leesgemak", "formule": "afronden(206.835 - 0.93 * woorden / zinnen - 77 * lettergrepen / woorden)" } ],
  "oordeel": { "op": "leesgemak", "labels": [ { "vanaf": 80, "sleutel": "a2", "tekst": "...", "uitleg": "..." } ] },
  "splitsen": { "vanaf": 26, "voegwoorden": [ "omdat", "terwijl" ], "min_tekens": 5 },
  "uitvoer": [
    { "type": "getal", "label": "Leesgemak", "waarde": "leesgemak", "formaat": "getal", "onder": "{oordeel_tekst}" },
    { "type": "tellers", "items": [ { "label": "Zinnen", "waarde": "zinnen" }, { "label": "Gemiddelde zinslengte", "waarde": "woorden / zinnen", "formaat": "getal:1" } ] },
    { "type": "tekst", "kop": "...", "tekst": "{oordeel_uitleg}", "als": { "formule": "jargon > 0" } },
    { "type": "gemarkeerd", "kop": "Je tekst", "markeer": [ "klasse", "jargon", "passief" ], "legenda": true },
    { "type": "zinnen", "kop": "Per zin", "alleen": "woorden >= 16", "regel": "Zin {nr} · {woorden} woorden · {kommas} komma's", "markeer": [ "klasse" ], "suggestie": true }
  ],
  "acties": { "pdf": true, "word": true, "duimpjes": true }
}
```

- Metingen per zin: `woorden`, `lettergrepen`, `kommas`, `passief` (0/1: vorm van worden met een voltooid deelwoord erna), `lijst` (aantal treffers uit `lijsten`), `lange_woorden` (8+ letters en 4+ lettergrepen).
- Namen voor totalen: `zinnen`, `woorden`, `lettergrepen`, `kommas`, `tekens`, per meting de som (`jargon`), `zinnen_met_<id>`, `klasse_<sleutel>` (aantal zinnen), reken-id's, `oordeel`, `oordeel_tekst`, `oordeel_uitleg`. Per zin (in `alleen`, `regel` en `klassen.op`): de metingen van die zin, `nr`, `klasse`, `klasse_tekst`.
- Markeren: het hoogste klasse-niveau krijgt een golflijn, het tweede een stippellijn, het laagste niets; metingen uit een lijst worden per woord gemarkeerd, andere metingen markeren de zin.
- Geen mail, geen deellink: de tekst blijft bij de bezoeker.
