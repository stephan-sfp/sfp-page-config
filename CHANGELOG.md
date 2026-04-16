# Changelog

Alle belangrijke wijzigingen aan SFP Page Config worden in dit bestand bijgehouden.

Formaat volgt [Keep a Changelog](https://keepachangelog.com/nl/1.1.0/), versies volgen [semver](https://semver.org/lang/nl/).

## [2.0.0] - 2026-04-16

Major bump omdat de Sticky CTA voor het eerst via de Instellingen-tab beheerbaar is. Geen breaking changes voor bestaande sites: de hardcoded defaults uit v1.9.x blijven als fallback staan, dus een site die zonder instellingen upgrade draait gedrag-identiek door.

### Toegevoegd

- **Sticky CTA beheerbaar vanuit Instellingen.** Nieuwe sectie `Instellingen → Sticky CTA` met per paginatype (coaching, training, incompany) vier velden: knoptekst, knoplink, anchor-ID en hero-selector. Leeg laten valt terug op de hardcoded default uit `sfp_page_config_get_sticky_cta_defaults()`. Kleuren blijven gekoppeld aan de CTA-branding uit de sitematrix zodat de sticky consistent is met de rest van de pagina.
- **`sfp_page_config_get_sticky_cta_defaults()`.** Nieuwe helper die de hardcoded defaults retourneert. Wordt zowel door de merge-laag in `sfp_page_config_get_sticky_cta()` als door de UI gebruikt voor de placeholders en de "Default:"-regels onder elk veld.

### Gewijzigd

- **`sfp_page_config_get_sticky_cta()` doet nu een merge.** Leest `sfp_settings.sticky_cta[<type>]` en overschrijft alleen de niet-lege velden op de default. Dat betekent dat een kapot of leeg veld automatisch terugvalt in plaats van een stukkende CTA op te leveren.
- **Anchor-invoer gesanitized via `sanitize_key()`.** Een editor die per ongeluk `#inschrijven` intypt krijgt automatisch `inschrijven` opgeslagen; de sticky-cta.js doet `getElementById(anchor)` en dus zonder hash-prefix.

## [1.9.10] - 2026-04-16

### Toegevoegd

- **WP Color Picker beperkt tot Astra-palet.** De vijf longread-kleurvelden in `Instellingen` laden nu uitsluitend de kleuren die in de Astra Customizer zijn vastgesteld (`astra-settings.global-color-palette.palette`). Zo wordt per ongeluk kiezen van een off-brand hex-code voorkomen. Bij een lege of ontbrekende Astra-palette valt de picker terug op de standaard WP-swatches.
- **Aangepaste CSS voor leestijdmeter en voortgangsbalk.** Nieuwe textarea in `Instellingen → Leestijd en voortgangsbalk` waarin je CSS-regels opgeeft voor `.custom-read-meter`, `.tijd-getal`, `#sfp-scroll-container` en `#sfp-scroll-bar`. Wordt via een `<style id="sfp-custom-css-rp">` in de head van elke singuliere pagina/post uitgevoerd. HTML-tags worden bij opslaan gestript zodat geen `</style>` kan ontsnappen.

### Gewijzigd

- **Cursusdata-tabblad toont nu alleen open-trainingpagina's.** Paginatype `training` wordt via `meta_query` gefilterd; andere paginatypes (coaching, incompany, about, contact, homepage, overig) komen niet meer in de lijst omdat ze geen cursusdata kunnen dragen. Cache-key gemigreerd naar `sfp_dashboard_pages_training`.
- **Affiliate-layout-ID is niet meer als instelling beschikbaar.** Het veld in `Instellingen` verwarde: affiliate is een Custom Hook die per post via de metabox wordt aangezet. De domein-specifieke fallback in `affiliate.php` (DPS: 27038) blijft als enige bron van waarheid. Eventuele oude `affiliate_layout_id` in de optiedatabase wordt genegeerd en mag blijven staan (harmless).
- **Longread-branding defaults voor DGA, CVD en DST gecorrigeerd.** `lr_brand`, `lr_bar_bg` en `lr_sidebar_text` hadden in 1.9.9 een onjuiste fallback (`#1a1a2e`/`#000000`) die niet bij de huisstijl paste. Defaults sluiten nu aan op de CTA-kleur van het domein.

### Gerepareerd

- **Mobiele longread-balk staat nu flush tegen de onderkant van het scherm.** `viewport-fit=cover` wordt via JS aan de viewport-meta toegevoegd zodat `env(safe-area-inset-bottom)` op iOS niet meer `0` retourneert. De bar gebruikt `height: calc(54px + safe-area)` met matching `padding-bottom`, waardoor de knoprij boven de home indicator staat en de bar-achtergrond zonder gat doorloopt tot de echte schermrand. De oude `::after`-hack met `bottom: -40px` is verwijderd. De drawer en de content-padding schalen mee met de safe-area.
- **Longread-navigatie verschijnt nu ook op tablets in landscape en narrow-desktop schermen.** Body class `sfp-lr-has-sidebar` wordt door de JS alleen geplaatst als de zijbalk daadwerkelijk naast de content past. De CSS verbergt de mobiele balk alleen op desktop als die class aanwezig is, zodat pagina's tussen ~768px en ~1100px terugvallen op de mobiele balk in plaats van zonder navigatie te zijn.

## [1.9.9] - 2026-04-16

### Toegevoegd

- **Longread-branding editable per site.** In `SFP Customizations → Instellingen` is een nieuwe sectie "Longread-branding" verschenen met vijf kleurkiezers (native WP color picker) voor de accentkleur, mobiele balk-achtergrond en -tekst, en de zijbalk-tekst- en muted-kleur. Leeg laten valt terug op de domein-default, zodat een per ongeluk gewist veld nooit een gebroken CSS-variabele oplevert.

### Gewijzigd

- **Longread-checkbox in de metabox werkt nu zoals verwacht.** De checkbox is beschikbaar op alle posts en pagina's, behalve op salespages (paginatype ingesteld op coaching/training/incompany). Op het moment dat je in de metabox zo'n paginatype kiest, verdwijnt de checkbox direct via JS. De pijler-tag-eis is losgelaten.
- **`sfp_page_config_is_longread()` vereist nu alleen de checkbox** (naast de salespage-uitsluiting), niet meer de `pijler`-tag op pagina's.

### Gerepareerd

- **Mobiele hoofdstuk-drawer scrollt nu bij veel H3's.** De drawer had `overflow: hidden` met een vaste `max-height: 400px`, waardoor H3's onderaan onbereikbaar waren bij long-form content. Gewijzigd naar `overflow-y: auto` met `max-height: calc(70vh - 54px)` plus `overscroll-behavior: contain` tegen scroll-chaining.

## [1.9.8] - 2026-04-16

### Gerepareerd

- **Longread-navigatie crashte op `Uncaught TypeError: Cannot read properties of null (reading 'appendChild')` in `longread-nav.js`.** Regressie geintroduceerd in 1.9.7: door de enqueue naar `wp_enqueue_scripts` te verplaatsen, werd het script door `wp_print_footer_scripts` geprint vóór de HTML-output van de plugin (beide op `wp_footer` priority 20, core-hook gaat eerst). Het script zocht daardoor DOM-elementen die nog niet bestonden en viel om op de eerste `appendChild`. De initialisatie wordt nu uitgesteld tot `DOMContentLoaded`, waarmee de order van printen niet meer uitmaakt. De scrollbar en leestijd waren niet geraakt (apart script).

## [1.9.7] - 2026-04-15

### Gerepareerd

- **Format-bug in `[cursus_datum]`-shortcode.** `sfp_page_config_format_date_nl()` ondersteunt nu naast de interne keywords `short` en `long` ook echte PHP-datumformaten (`l j F Y`, `j F`, `D j F Y`, etc.). Dag- en maandnamen worden in het Nederlands weergegeven, ongeacht de sitelocale.
- **Longread-navigatie laadde niet betrouwbaar.** De JS-enqueue stond op `wp_footer` priority 20, wat raceconditions veroorzaakte met `wp_print_footer_scripts`. Enqueue verplaatst naar de standaard `wp_enqueue_scripts`-hook.
- **Dashboard-cache invalideerde niet op AJAX-saves.** Cursusdata-wijzigingen via het dashboard verschijnen nu direct in plaats van pas na 6 uur of een pagina-save.
- **Externe WhatsApp-afbeelding verwijderd.** Het icoon werd vanaf `upload.wikimedia.org` geladen. Vervangen door inline SVG.
- **Cron stuurde dagelijks e-mails.** De dagelijkse check stuurt nu maximaal één e-mail per training per state-verandering. Reset automatisch zodra er nieuwe cursusdata worden ingevoerd.
- **Diverse `date()`-calls gebruikten servertijd.** Vervangen door `wp_date()` zodat de sitezone wordt gerespecteerd (van belang op SiteGround-servers die op UTC staan).
- **Unescaped font-variabele in longread-nav CSS.** Opgeschoond met whitelist-filter in lijn met body-class.php.

### Gewijzigd

- **Voortgangsbalk is nu sitewide.** Geen longread-gate meer; verschijnt op alle singuliere pagina's en posts. Gebruikt brand-kleur (CTA-achtergrond) en rAF-throttled scroll-updates. IDs hernoemd naar `sfp-scroll-container` / `sfp-scroll-bar`.
- **Leestijd gebruikt nu de `words_per_minute`-instelling** uit het Instellingen-tabblad.
- **Cron-recipient gebruikt nu de `cron_email`-instelling** uit het Instellingen-tabblad (fallback: site-admin).
- **WhatsApp-button gebruikt nu de `whatsapp_message`-instelling** uit het Instellingen-tabblad.
- **Affiliate-layout-ID gebruikt nu de `affiliate_layout_id`-instelling** als override (valt terug op de hardcoded domeinlijst).
- **Promo-scripts (Convert Pro) gebruiken nu de scroll-gate en cooldown-instellingen** uit het Instellingen-tabblad.
- **Longread-nav scope verscherpt.** Navigatie activeert alleen op blogposts én pagina's met de `pijler`-tag. Salespages (paginatype ingesteld op coaching/training/incompany) zijn hard uitgesloten in `sfp_page_config_is_longread()` en in de metabox.
- **Longread-checkbox in de metabox** toont alleen nog op toegestane post-types. Beschrijving verduidelijkt: "Longread-navigatie activeren" in plaats van de oude combitekst met leestijd en voortgang.
- **GitHub API-requests** sturen nu een User-Agent en timeout mee.
- **`sfp_page_config_cursusdata_updated` action** toegevoegd. Wordt gefired na elke AJAX-save in het cursusdata-dashboard zodat gerelateerde features (zoals de cron state) kunnen resetten.

## [1.9.6] - 2026-04-12

- Vorige release. Zie [v1.9.6 release notes](https://github.com/stephan-sfp/sfp-page-config/releases/tag/v1.9.6).
