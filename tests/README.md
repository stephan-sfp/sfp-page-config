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

# Editor tests (3.1.0)

```sh
wp eval-file wp-content/plugins/sfp-page-config/tests/editor-wp.php
```

Runs inside a real WordPress (a local test install, never a live site). Checks the page data over REST (sanitizing, rights, no empty rows), the panel state endpoint, the data blocks, the sticky button anchors, affiliate pattern matches, the reading-time guard, the focal point without the pijler tag, the course-date dropdown with lesson time, the daily mail links, and the block group and examples. Creates its own posts and a user and removes them at the end.

# Sjabloontekst (3.1.2)

```sh
php tests/sjabloon.php
php tests/sjabloon.php <map> [shortcode,shortcode,...]
```

Checks the page check "Sjabloontekst niet ingevuld": only the text a visitor reads counts (no scripts, styles, HTML comments, block attributes, tag attributes or blocks hidden with "Blok verbergen"), shortcodes that only exist for visitors are learned at a front-end visit, and a shortcode that exists nowhere is still reported. With a folder, it lists what the check still reports for each `.html` file of raw post content.

# Autoloaded Options tests (3.2.0)

```sh
php tests/autoload.php
```

Checks the analyzer with small WordPress stubs and a fake database: source recognition for removed and inactive plugins, what may be deleted (orphan and unknown) and what never (protected, core, transients, active plugins, WordPress keys), leftovers with autoload off, large options forced to autoload on, the REST data matching the page, the JSON backup and the confirmation list.

# Snippetbeheer (3.3.0)

```sh
SFP_TEST_URL=http://127.0.0.1:8899 wp eval-file wp-content/plugins/sfp-page-config/tests/snippets-wp.php
```

Runs inside a real WordPress (a local test install, never a live site) with ASE Pro and its Code Snippets Manager on, an administrator `claude-api` and a second administrator. Checks access (component off, other administrator, logged out), the checks before saving (syntax, existing functions, own functions on update), create, update, draft, on and off against the file and the active list of ASE, kses leaving the code intact, and reading. With `SFP_TEST_URL` pointing at the same install served over HTTP, it also checks on the front end that the code really runs, changes and stops. Creates its own snippets and removes them at the end.
