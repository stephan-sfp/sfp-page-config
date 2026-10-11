# Branding regression tests

Run the production colour resolver with Astra's API and with raw stored options:

```sh
php tests/brand-regression.php
php tests/brand-regression.php raw
```

The tests execute the production brand section with small WordPress stubs. They verify the eight host mappings, the FL foreground exception, dynamic theme values, preserved overrides and unsafe-input rejection. They do not replace a real WordPress deployment or a rendered desktop/mobile check.

The palette references for SwS and FL deliberately identify roles, not colour values. If those sites' palette roles move, update the mapping and test it against their live settings before deployment.

# Ajax Lean tests

```sh
php tests/ajax-lean.php
```

Checks the skip-list sanitizing, the one-time cleanup, placing and removing the mu-plugin, and that only front-end `latepoint_route_call` requests run lean. Uses a temporary directory as `WPMU_PLUGIN_DIR`.

# SFP Beheer tests

```sh
php tests/beheer.php
```

Checks the logic behind SFP Beheer with small WordPress stubs: texts per language (every text has a default in nl, en and pt with the same placeholders), saving settings without losing unknown stored keys, the network norm and resetting to it, the sticky button for stored data from 2.12.0 and for the new destinations, course dates clean-up, footer links (clean-up, output and taking over the existing widget), the page checks, own reviews, and the guard that lets an old plugin be re-activated. It does not replace a run in a real WordPress with a browser.
