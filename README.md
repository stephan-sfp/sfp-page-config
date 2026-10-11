# SFP Page Config

WordPress-plugin voor het netwerk van School for Professionals (acht sites). In wp-admin heet het beheer **SFP Beheer**; de map en de naam in de pluginlijst blijven `sfp-page-config` en SFP Page Config.

## Wat de plugin doet

- **Pagina's**: soort pagina (coaching, training, incompany), de knop onderaan op een telefoon, startmomenten van trainingen, en een controle die meldt wat ontbreekt.
- **Artikelen**: kopkaart, inhoudsopgave, promokaart, vervolg per pijler, affiliatemelding en deelbeelden.
- **Reviews**: reviews van Google en eigen reviews via `[google_reviews]` (was de plugin SFP Google Reviews).
- **Tooltips**: uitleg bij een woord, gemaakt in de editor (was de plugin SFP Tooltip).
- **Footerlinks**, **WhatsApp-knop**, **voortgangsbalk** en de bouwstenen voor pagina's en artikelen.
- **Teksten** per taal van de site (Nederlands, Engels, Portugees), per site aan te passen.

Kleuren en lettertypes komen uit Astra en uit het snippet met de kleurrollen. Er staat geen merkkleur en geen fontnaam in de code.

## Overgang van SFP Tooltip en SFP Google Reviews

Zolang een van die plugins op een site actief is, doet het onderdeel in deze plugin niets. Staat de oude plugin uit, dan neemt het onderdeel het over bij het eerstvolgende verzoek. Instellingen, eigen reviews, bestaande tooltips en de code `[google_reviews]` blijven werken. Terugdraaien: de oude plugin weer aanzetten.

## Installeren en bijwerken

Download de zip van de laatste release en upload hem via Plugins, Nieuwe plugin, Plugin uploaden. Daarna werkt de plugin zichzelf bij vanuit de releases in deze repository. Onder SFP Beheer, Onderhoud staat of een site ook proefversies krijgt.

Vereist: WordPress 6.4 of hoger, PHP 8.0 of hoger.

## Tests

```sh
php tests/beheer.php
php tests/brand-regression.php
php tests/brand-regression.php raw
php tests/ajax-lean.php
```

Zie `tests/README.md`.
