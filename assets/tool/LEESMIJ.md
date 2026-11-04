# Toolmotor

De toolmotor (`motor.js`) bouwt elke tool van het type `tool` in de browser uit de beschrijving in het bericht. De soorten registreren zich bij de motor: `test.js` (test), `generator.js` met `generator-bouw.js` (generator, rekenaar, checklist) en `tekstanalyse.js` (tekstanalyse). De motor bewaart niets op de server.

## Hulptegel bij een keuze (sinds 3.27.0)

Een keuzeveld in de kaartweergave kan naast de opties een hulptegel krijgen. Zet bij het veld `"hulp": "What is the difference?"` en bij de opties een `uitleg`. De motor tekent dan een extra tegel met een vraagteken; die opent een paneel onder de tegels met per optie de uitleg, en toont die uitleg niet meer in de tegels zelf. Tikken, Enter en spatie openen en sluiten het paneel, Escape sluit het. De tegel is geen keuze en telt niet mee. Zonder `hulp` verandert er niets.

```json
{ "id": "verb", "type": "keuze", "label": "What should your talk do?", "hulp": "What is the difference?",
  "opties": [ { "waarde": "Inform", "tekst": "Inform", "icoon": "info", "uitleg": "..." } ] }
```

## Meten van toolgebruik (sinds 3.24.0)

De motor pusht events naar `window.dataLayer`, zodat GTM ze kan doorgeven aan GA4. Dat gebeurt alleen als de pagina een `dataLayer` heeft. De plugin bevat geen eigen GA- of gtag-code en geen toestemmingslogica: toestemming loopt via Complianz en Consent Mode in GTM. Het werkt voor elke tool op de motor, op elke site, zonder instellingen.

### Het event

Eén eventnaam, zodat één GTM-trigger (aangepaste gebeurtenis `sfp_tool`) alles vangt:

```js
{
  event: 'sfp_tool',
  actie: 'stap',            // zie de tabel hieronder
  tool_id: 1076,            // bericht-ID van de tool
  tool_slug: 'fast-presentation-builder',
  stap_nummer: 3,           // waar de bezoeker staat, 1-based, of null
  stap_totaal: 7,           // aantal stappen of vragen, of null
  weergave: 'kaart'         // 'kaart' (weergave.modus kaart) of 'lijst' (alle andere)
}
```

Elk event heeft alle zeven sleutels. Een sleutel zonder waarde is `null`, omdat GTM anders de waarde van het vorige event blijft gebruiken.

Nooit in een event: antwoorden, ingevulde tekst, notities, e-mailadressen of de uitslag.

### Acties

| actie | Wanneer |
|---|---|
| `start` | De eerste interactie met de tool op deze pagina: een klik, keuze, toets of invoer in de tool. Niet bij het laden. Tab, pijltjes en Escape tellen niet. Eén keer per paginaweergave en altijd vóór elke andere actie. |
| `stap` | De bezoeker gaat naar een andere stap (generator, rekenaar, checklist: een stap; test per vraag: een vraag of groep). Alleen na `start` en alleen bij een wissel. `stap_nummer` en `stap_totaal` geven de positie. |
| `afgerond` | De uitslag staat er na een interactie van de bezoeker. Eén keer per sessie (sessionStorage) per tool. Een deellink of een herladen uitslag zonder interactie telt niet. Bij een live rekenaar: de eerste volledige uitkomst na de eerste invoer. Bij de tekstanalyse: de eerste analyse. |
| `kopieren` | De knop Kopieer heeft de tekst gekopieerd (generator, rekenaar, checklist, tekstanalyse). |
| `download_pdf` | De pdf is gemaakt en opgeslagen. |
| `download_word` | Het Word-bestand is gemaakt en opgeslagen. |
| `slides_prompt` | De bezoeker kopieert tekst uit een deel van de uitslag dat in de beschrijving `"meet": "slides_prompt"` heeft. |
| `opnieuw` | De bezoeker begint opnieuw (link of knop Opnieuw). |
| `hulp` | De bezoeker opent de hulptegel bij een keuze (veld met `hulp`, sinds 3.27.0). |

### Een deel van de uitslag apart meten

Een sectie in `uitvoer` van een generator, rekenaar of checklist kan de sleutel `meet` krijgen. Kopieert de bezoeker tekst uit dat deel (selecteren en kopiëren), dan pusht de motor de actie die in `meet` staat. Toegestaan: `slides_prompt` en `kopieren`. Andere waarden vallen weg bij het opslaan.

```json
{ "type": "tekst", "kop": "Slide prompt", "meet": "slides_prompt", "tekst": "..." }
```

In de kaartweergave geldt dat voor het hele uitklapblok van die sectie.

### In GTM

- Trigger: aangepaste gebeurtenis, naam `sfp_tool`.
- Variabelen (gegevenslaag, versie 2): `actie`, `tool_id`, `tool_slug`, `stap_nummer`, `stap_totaal`, `weergave`.
- GA4-event: bijvoorbeeld `sfp_tool` met dezelfde parameters, of per actie een eigen eventnaam via `{{actie}}`.

### Controleren

Open een toolpagina, typ `dataLayer.filter(e => e.event === 'sfp_tool')` in de console en doorloop de tool. In GTM: voorbeeldmodus.
