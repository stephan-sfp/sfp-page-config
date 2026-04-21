# Changelog

Alle belangrijke wijzigingen aan SFP Page Config worden in dit bestand bijgehouden.

Formaat volgt [Keep a Changelog](https://keepachangelog.com/nl/1.1.0/), versies volgen [semver](https://semver.org/lang/nl/).

## [2.7.1] - 2026-04-21

### Gewijzigd

- **Revert van v2.7.0.** De nav-component voor portfolio-pagina's (paginatype `portfolio`, sticky jump-nav, accordion-blokken) is verwijderd omdat het ontwerp niet paste bij de uiteindelijke behoefte. Deze release bevat dezelfde code als v2.6.4 met alleen een versie-bump, zodat WP Update Agent v2.7.0 op alle 5 sites automatisch overschrijft en de plugin functioneel terug is op het v2.6.4-niveau. De navigatie en accordion worden op een later moment opnieuw ontworpen.

## [2.6.4] - 2026-04-19

### Toegevoegd

- **Safe Cleanup-knop op de Autoloaded Options-pagina.** Nieuwe groene-blok-sectie tussen voortgangsbalk en filter-tabs die aangeeft hoeveel verweesde opties (en hoeveel KB) in één druk op de knop kunnen worden opgeruimd. De knop werkt in twee stappen: "Safe Cleanup voorbereiden" toont een preview met de samenvatting per voormalige plugin en een uitklapbare volledige lijst van elke option_name + grootte. Pas na een tweede klik ("Bevestigen en verwijderen", met browser-confirm dialog) gaat de admin-post handler `sfp_ao_safe_cleanup` aan de slag. Deze flow vereist geen handmatige selectie van individuele opties meer.
- **Safe Cleanup-lijst in `sfp_ao_safe_cleanup_prefixes()`.** Expliciete whitelist van plugin-prefixes die 100% veilig te verwijderen zijn omdat ze buiten de SFP-stack vallen: Yoast SEO (`wpseo`, `yoast`), Rank Math (`rank_math`, `rank-math`, `rankmath`), All in One SEO (`aioseo`, `aioseop`), Surfer SEO (`surfer_`), Elementor (`elementor_`, `elementor-`), MainWP (`mainwp`, `mwp_`), FluentMail (`fluentmail`), Better Search Replace (`bsr_`), Custom Post Type UI (`cptui_`) en Google Site Kit (`googlesitekit`). Ambigue of algemene prefixes (`jetpack`, `w3tc`, `wordfence`) staan bewust niet op deze lijst.
- **Core-prefixed patroon whitelist (`sfp_ao_is_core_prefixed_option`).** Opties die eindigen op `user_roles`, `user_count` of `user_count_start`, ongeacht het voorloop-prefix, worden nu automatisch als Core herkend en kunnen nooit verwijderd worden. Dit voorkomt dat op hardened-prefix installaties (bijvoorbeeld CVD met `xrk_`) de kern-gebruikersrollen-optie (`xrk_user_roles`) foutief als Verweesd wordt gemarkeerd. Werkt ook bij `sfp_ao_handle_action` (bulk-verwijderen respecteert dezelfde whitelist).

### Gewijzigd

- **`xrk_` is uit `sfp_ao_known_orphan_prefixes()` gehaald.** Deze prefix is site-specifiek (random db-prefix na hardening) en hoort niet thuis in een site-agnostische orphan-lijst. De core-suffix-whitelist vangt de enige kritieke optie (`xrk_user_roles`) nu betrouwbaar op.

## [2.6.3] - 2026-04-19

### Gerepareerd

- **Verweesd-detectie gaf false positives bij actieve plugins met afwijkende option-prefixes.** Complianz (map `complianz-gdpr`, opties `cmplz_*`), SureForms (map `sureforms`, opties `srfm_*`), Progress Planner (map `progress-planner`, opties `html-regression-*`), ASE Pro (map `admin-site-enhancements-pro`, opties `admin_site_enhancements*`) en Brainstorm Force-opties (`bsf_*`, `brainstrom_*` die door de hele BSF-familie gedeeld worden) werden ten onrechte als Verweesd gemarkeerd. Opgelost met een expliciete `sfp_ao_source_to_active_slugs()` map die bron-labels koppelt aan de plugin-slugs die in `active_plugins` staan als die bron geïnstalleerd is. `sfp_ao_active_prefixes()` breidt zichzelf nu met deze option-prefixes uit zodra de bijbehorende plugin draait.
- **Detectievolgorde in `sfp_ao_detect_status()` gewijzigd.** De actieve-plugin-match wordt nu vóór de `known_orphan_prefixes`-check uitgevoerd. Dat betekent dat een heringestalleerde plugin (bijvoorbeeld Yoast of MainWP) automatisch terugkeert naar status Actief zonder codewijziging, en maakt de orphan-lijst veilig uit te breiden met plugins die mogelijk ooit terugkeren.

### Toegevoegd

- **Bronherkenning uitgebreid met oude plugins en gedeelde frameworks.** `sfp_ao_known_prefixes()` herkent nu ook: Yoast SEO (`wpseo`, `yoast`), Rank Math (`rank_math`, `rank-math`, `rankmath`), Surfer SEO (`surfer_`), Elementor (`elementor_`, `elementor-`), FluentMail (`fluentmail`), Better Search Replace (`bsr_`), Custom Post Type UI (`cptui_`), Google Site Kit (`googlesitekit`), All in One SEO (`aioseo`, `aioseop`), Freemius (`fs_`), Brainstorm Force (`bsf_`, `brainstrom`), en de SureForms interne prefix `srfm_`. Opties van deze herkomst verschijnen niet meer als `Onbekend` in de Bron-kolom.
- **Orphan-lijst uitgebreid.** `sfp_ao_known_orphan_prefixes()` bevat nu ook: `surfer_`, `mainwp`, `mwp_`, `fluentmail`, `bsr_`, `cptui_`, `googlesitekit`, `xrk_`, plus `rank-math` met streepje. Opties van deze uit-het-stack-verdwenen plugins worden daarmee direct als Verweesd gevlagd (tenzij het plugin-slug weer in `active_plugins` verschijnt, dan wint Actief).

## [2.6.2] - 2026-04-19

### Toegevoegd

- **Sorteerbare kolommen op de Autoloaded Options-pagina.** Alle vijf kolommen (option name, grootte, autoload, bron, status) zijn nu klikbaar voor sortering. Een tweede klik op dezelfde kolom draait de richting om. Tekstkolommen gebruiken `strnatcasecmp` zodat bijvoorbeeld `widget_2` na `widget_1` komt en niet na `widget_10`. Standaard-volgorde blijft grootte aflopend zodat de grootste kostenposten direct bovenaan staan. De filter-tabs houden de gekozen sortering vast bij het wisselen tussen Alles / Groot / Verweesd / Transients.

## [2.6.1] - 2026-04-19

### Gerepareerd

- **Spelling "Verweesd" in plaats van "Verweest".** De status-badge, filter-tab en dashboardkaart op de Autoloaded Options-pagina stonden op "Verweest" met een t. Dat is fout: de onderliggende stam `verwees-` eindigt op een z (woordfinale verscherping schrijft s, de klank is z) en z zit niet in 't kofschip, dus het voltooid deelwoord krijgt -d. Nu op alle drie de plekken "Verweesd".

## [2.6.0] - 2026-04-19

### Toegevoegd

- **Autoloaded Options Analyzer (nieuwe submenu-pagina onder SFP Page Config).** WordPress 6.6+ waarschuwt via Site Health zodra de autoloaded options over 800 KB gaan; op DPS, CVD en DST is die waarschuwing structureel. De nieuwe pagina (`SFP Page Config > Autoloaded Options`) leest `wp_options` uit met een prepared statement (autoload IN `on`, `auto`, `yes`), toont totalen en een kleurgecodeerde voortgangsbalk (groen < 600 KB, oranje 600-800 KB, rood > 800 KB), en rendert een `wp-list-table` gesorteerd op grootte. Filter-tabs: Alles, Groot (>1 KB), Verweesd, Transients.
- **Automatische bronherkenning per optie.** Een lengtegesorteerde prefix-map (`admin_site_enhancements` voor ASE Pro vóór de kortere varianten, `astra-` / `astra_` voor Astra, `surerank` voor SureRank, enz.) koppelt option_names aan de actieve SFP-stack. Prefixes die niet matchen worden als `Onbekend` getoond, zodat onverwachte opties direct opvallen.
- **Verweesde opties worden automatisch gemarkeerd.** Opties met een prefix die niet overlapt met een actieve plugin- of theme-slug (of die matcht met een hardcoded lijst van bekende deactivated plugins: `aioseo`, `yoast`, `rank_math`, `jetpack`, `elementor`, `wordfence`, `litespeed`, etc.) krijgen de status `Verweesd`. Transients en WordPress core-opties krijgen een eigen status-badge zodat ze niet per ongeluk in de opruim-flow belanden.
- **Bulk- en per-rij acties: autoload uitschakelen en verwijderen.** Via een admin-post handler met nonce + `manage_options` capability + beschermde-lijst. Autoload-uit gebruikt `wpdb->update` zodat de optiewaarde ongemoeid blijft en verwijdert daarna de `options` + `alloptions` object-cache entries. Beschermde opties (`siteurl`, `home`, `active_plugins`, `cron`, `widget_block`, `theme_mods_*`, `wp_user_roles`, enz.) kunnen niet verwijderd worden en zijn visueel gemarkeerd.
- **Cache-reminder na elke mutatie.** Elke succesvolle actie toont in de admin notice expliciet de purge-checklist (WP Rocket, SiteGround, Cloudflare, browser) zodat het onderhoud consistent blijft over de vijf sites.

### Gewijzigd

- **`sfp-page-config.php`:** versie-header en `SFP_PAGE_CONFIG_VERSION` opgehoogd naar 2.6.0, `includes/autoloaded-options.php` toegevoegd aan de `$sfp_includes` array.

## [2.5.4] - 2026-04-19

### Gerepareerd

- **FAQ schema repair op DPS `/spreekangsttraining/` werkt nu.** Google Search Console meldde nog steeds een parseerfout op de FAQPage JSON-LD ("',' of '}' ontbreekt", positie 311 bij `class="cursus-data-wrapper"`). Het `render_block_uagb/faq` filter uit v2.5.0 draaide wel, maar de DOMDocument-gebaseerde extractie vond geen Q&A-paren in de blok-HTML: de kapotte `<script>` tag met ongebalanceerde aanhalingstekens en losse HTML-fragmenten in de JSON destabiliseerde het DOMDocument-parsen, waardoor de herstelfunctie terugviel op de originele kapotte output.

### Gewijzigd

- **`includes/schema-fix.php` herschreven (Optie D: render_block + the_content, met script-strip en regex-fallback).** Het repair-pad werkt nu in vier lagen: (1) kapotte `<script type="application/ld+json">` tag wordt uit de blok-HTML gestript voordat DOMDocument de HTML parseert; (2) als DOMDocument geen Q&A-paren vindt, kickt een regex-gebaseerde fallback in die per `wp-block-uagb-faq-child` chunk de vraag en het antwoord extraheert; (3) naast het bestaande `render_block_uagb/faq` filter is er een `the_content` filter (priority 99) als vangnet voor gevallen waarin de render_block-hook het probleem niet oplost (bijvoorbeeld omdat een ander filter de blockoutput wrapt voordat DOM-parsing lukt); (4) wp_strip_all_tags + html_entity_decode + whitespace-collapse staat in één helperfunctie (`sfp_page_config_flatten_html_text`) zodat DOM- en regex-paden identieke schema-veilige tekst produceren.
- **Schema-repair blijft scoped op FAQPage.** Andere broken JSON-LD scripts (mocht er ooit eentje opduiken) worden bewust niet aangeraakt, zodat de fix geen onbedoelde neveneffecten heeft op schema's die SureRank of Spectra zelf genereren.

## [2.5.3] - 2026-04-18

### Gewijzigd

- **Specificiteit outline-button hide-regel verhoogd.** De sitewide mobiele knop-alignment CSS (`.entry-content a.uagb-buttons-repeater { display: flex !important }`, specificiteit 0,2,1) overschreef de plugin-regel die outline-knoppen verbergt op salespagina's. Opgelost door `body.is-sales-page .entry-content` als prefix toe te voegen (specificiteit 0,3,1), zodat de hide-regel altijd wint ongeacht laadvolgorde.

## [2.5.2] - 2026-04-18

### Gewijzigd

- **Leestijdmeter-CSS verplaatst naar de plugin.** De `.custom-read-meter` en `.custom-read-meter .tijd-getal` regels stonden in de sitewide CSS (ASE Pro), maar het HTML-element wordt door de plugin aangemaakt via de `[mijn_leestijd]` shortcode. CSS nu opgenomen in `assets/reading-time.css` zodat de styling bij de plugin hoort en de sitewide CSS lichter wordt. Gebruikt de bestaande sitewide brand-variabelen (`--brand-font`, `--brand-main`, `--brand-accent`).

## [2.5.1] - 2026-04-18

### Gewijzigd

- **Hero-detectie houdt rekening met responsieve Spectra-containers.** De auto-detectie koos het eerste top-level Spectra container-blok als hero, maar op DPS salespagina's is dat een statistiekenbalk met `uag-hide-mob` (verborgen op mobiel) die geen knoppen bevat. Het daadwerkelijke hero-blok met CTA-knoppen zit in de volgende container, waardoor `.sfp-hero-section` op het verkeerde element terechtkwam. De outline-button CSS-uitzondering matchte daardoor nooit: op tablet verdwenen alle outline-knoppen (ook die in de hero), op mobiel bleven ze juist staan. Nieuwe logica: auto-detectie zoekt nu het eerste top-level Spectra container-blok dat een outline-knop bevat (`.ast-outline-button` of `.wp-block-button.is-style-outline`). Valt terug op de eerste container als geen enkele een outline-knop heeft.

## [2.5.0] - 2026-04-17

### Toegevoegd

- **FAQ schema fix via `render_block` filter.** Spectra's FAQ-blok (`uagb/faq`) genereerde ongeldige FAQPage JSON-LD wanneer een FAQ-antwoord HTML met `class="..."` attributen bevatte (zoals de `[cursus_datum]` shortcode-output). De dubbele aanhalingstekens in HTML-attributen braken de JSON string voortijdig af. Nieuw filter `render_block_uagb/faq` onderschept de blockoutput, detecteert broken JSON-LD, en herbouwt het schema vanuit de FAQ-HTML met `DOMDocument` en `wp_json_encode()`. Betrokken DPS-pagina's: `/spreekangsttraining/` en `/presentatietraining/`. Pagina's zonder shortcodes in FAQ-antwoorden (bijv. `/spreekangst/`) worden niet aangeraakt.

### Gewijzigd

- **Hero-detectie in sticky CTA herschreven met drielaagse strategie.** De hardcoded CSS-selector `.uagb-block-0b4df88b` werkte alleen op pagina's die toevallig dat specifieke Spectra-blok bevatten. UAGB block-ID's worden random gegenereerd per blok-instantie, waardoor de selector op andere pagina's niet matchte. Nieuwe aanpak:
  1. **Handmatige override:** als de admin een hero-selector heeft ingevuld in Instellingen en die matcht, wordt die gebruikt.
  2. **Auto-detectie:** het eerste top-level Spectra container-blok (`.wp-block-uagb-container`) in `.entry-content` wordt automatisch als hero gemarkeerd. Dit is structureel betrouwbaar, onafhankelijk van random block-ID's.
  3. **Scroll-drempel fallback:** als geen hero-element gevonden wordt, verschijnt de sticky CTA na 400px scroll. Gebruikt `requestAnimationFrame`-throttled scroll-listener.
- **Hero-selector default leeggemaakt.** De drie paginatypes (coaching, training, incompany) hadden allemaal `.uagb-block-0b4df88b` als default. Die is nu leeg, zodat de auto-detectie het overneemt. Bestaande handmatige overrides in de Instellingen-tab blijven werken.
- **Outline-button CSS verbreed.** Naast `.ast-outline-button` wordt nu ook `.wp-block-button.is-style-outline` verborgen op mobiel/tablet, met dezelfde uitzondering voor de hero-sectie. Dit dekt zowel Astra- als Gutenberg-native outline-knoppen.
- **`scrollThreshold` toegevoegd aan `sfpStickyConfig`.** Het JS-configuratieobject bevat nu een `scrollThreshold` veld (standaard 400) voor de fallback-drempel.
- **Inline CSS geextraheerd naar losse bestanden.** Voor betere cachebaarheid en onderhoudbaarheid:
  - `includes/longread-nav.php` inline CSS naar `assets/longread-nav.css` (262 regels statische CSS; dynamische brandkleuren via `wp_add_inline_style` als CSS custom properties).
  - `includes/reading-time.php` inline CSS naar `assets/reading-time.css` (scroll progress bar; `--sfp-bar-color` via `wp_add_inline_style`).
  - `includes/whatsapp.php` inline CSS naar `assets/whatsapp.css` (volledig statische CSS, geen PHP-variabelen).
- Alle drie de stylesheets worden nu geladen via `wp_enqueue_style()` met `SFP_PAGE_CONFIG_VERSION` als cache-buster.
- WhatsApp CSS wordt alleen geladen op pagina's waar de WhatsApp-knop is ingeschakeld (conditional enqueue).

## [2.4.0] - 2026-04-16

### Gerepareerd

- **Voortgangsbalk onzichtbaar op desktop voor ingelogde gebruikers.** De WP admin bar (`z-index: 99999`, `position: fixed`, `top: 0`, hoogte 32px) bedekte de voortgangsbalk (`z-index: 9995`, `top: 0`, hoogte 3px). Op mobiel (<783px) is de admin bar niet fixed en scrollt mee, waardoor de balk daar wel zichtbaar was. Fix: `body.admin-bar #sfp-scroll-container { top: 32px }` met een `@media (max-width: 782px)` reset naar 0.
- **Scroll-to-top en Complianz-consentknop bleven zichtbaar op longread-pagina's met mobiele balk.** De CSS gebruikte `@media (max-width: 1023px)` wat niet alle viewports dekte waar de mobiele balk actief is (bijv. narrow desktops waar de sidebar niet past). Vervangen door `.is-longread:not(.sfp-lr-has-sidebar)` zodat de verberging exact meeloopt met de balktoestand. Daarnaast: `.cookie-toggle` bestond niet in Complianz; de juiste selector `.cmplz-manage-consent` is toegevoegd.
- **Zijbalk-TOC te ver van de content op desktop.** `positionSidebar()` gebruikte de breedste Spectra-container als referentie, maar op pagina's met full-width decoratieve secties (1280px) was dat veel breder dan de eigenlijke contentkolom (720px). De sidebar dacht dan dat er geen ruimte was. Referentie gewijzigd naar `.entry-content`, die betrouwbaar de contentkolombreedte vertegenwoordigt.

## [2.3.1] - 2026-04-16

### Gerepareerd

- **Outline-knop in hero niet full-width op mobiel/tablet.** De secundaire CTA ("Eerst kennismaken") bleef op contendbreedte in plaats van 100% van de container. Oorzaak: de CSS-uitzondering voor de hero-sectie gebruikte `display: inline-flex` (inline-level, neemt alleen contentbreedte in) in plaats van `display: flex` (block-level, vult de parent). Geldt voor alle salespages op alle 5 sites.

## [2.3.0] - 2026-04-16

### Toegevoegd

- **CSS-variabele `--lr-sidebar-active`.** Ontkoppelt de hover-/actieve kleur van de zijbalk-TOC van `--lr-bar-bg`. Defaults zijn per site gelijk aan de huidige `lr_bar_bg`, dus visueel verandert er niets bij de upgrade.
- **CSS-variabele `--lr-sidebar-h3`.** Vervangt de hardcoded `#575757` kleur van de H3-subitems in de zijbalk-TOC. Default is `#575757` voor alle sites.
- **Twee nieuwe kleurvelden in Longread-branding.** "Zijbalk-actief/hover (desktop)" en "Zijbalk H3-items (desktop)" in het Instellingen-tabblad, met Astra-paletpicker.

## [2.2.0] - 2026-04-16

### Toegevoegd

- **Spectra FAQ-accordion in de drawer.** Wanneer het actieve H2-hoofdstuk geen H3-subhoofdstukken bevat maar wel een Spectra FAQ-blok (`wp-block-uagb-faq`), toont de drawer nu de FAQ-vragen als navigeerbare items. Bij klikken scrollt de pagina naar de vraag en opent het accordion automatisch als het gesloten was.
- **Server-side `viewport-fit=cover` injectie.** Inline script in `wp_head` dat het viewport meta-element patcht voordat de longread CSS geparsed wordt. Voorheen deed alleen de JS in longread-nav.js dit, maar dat was te laat: `env(safe-area-inset-bottom)` was al als 0 verwerkt, waardoor er op iPhones een gap zat tussen de balk en de onderkant van het scherm.

### Gewijzigd

- **Drawer-icoon: chevron vervangen door plus/min.** Het omhoog-pijltje naast de hoofdstuknaam was visueel identiek aan de naar-boven-knop. Vervangen door een plus-icoon dat animeert naar een min-streepje wanneer de drawer open is.
- **Tablet-fix: breedste Spectra-container selecteren.** `positionSidebar()` pakte voorheen de eerste `.uagb-container-inner-blocks-wrap` die smaller was dan het scherm. Op pagina's met multi-kolom-layouts kon dit een smalle sub-kolom zijn, waardoor de sidebar onterecht "paste" en de balk verborgen werd. Nu wordt de breedste container gebruikt.

## [2.1.0] - 2026-04-16

### Toegevoegd

- **Drawer-kleurvelden in Longread-branding.** Twee nieuwe kleurvelden: "Drawer-achtergrond (mobiel)" en "Drawer-tekstkleur (mobiel)". De achtergrond van het mobiele H3-uitklapmenu was hardcoded op `#F7FCFE` en de tekstkleur was gekoppeld aan de balk-achtergrond. Beide zijn nu instelbaar via `Instellingen > Longread-branding` en als CSS-variabelen (`--lr-drawer-bg`, `--lr-drawer-text`) beschikbaar.
- **Domein-defaults voor drawer-kleuren.** Elke site in de sitematrix heeft nu een eigen `lr_drawer_bg` (standaard `#F7FCFE`) en `lr_drawer_text` (standaard de balk-achtergrondkleur van die site).

### Gewijzigd

- **Cursusdata-tab vereenvoudigd.** Filter-dropdown (Alle/Met/Zonder/Verborgen), zoekbalk en Verbergen-knoppen verwijderd. De server-side `meta_query` toont al alleen trainingpagina's, waardoor clientside filtering overbodig was. De teller toont nu enkel het totaal aantal trainingpagina's en hoeveel daarvan cursusdata hebben.

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
