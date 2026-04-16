# Changelog

Alle belangrijke wijzigingen aan SFP Page Config worden in dit bestand bijgehouden.

Formaat volgt [Keep a Changelog](https://keepachangelog.com/nl/1.1.0/), versies volgen [semver](https://semver.org/lang/nl/).

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
