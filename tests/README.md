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

# Zelftest (3.4.0)

```sh
SFP_ZT_PAKKET=/pad/naar/pakket wp eval-file wp-content/plugins/sfp-page-config/tests/zelftest-wp.php
```

Runs inside a real WordPress (a local test install, never a live site) with a test package folder (manifest.json, inhoud.json, motor.js, label.js, blad.html). Reads the package in as test "proeftest" and checks: refusing broken packages and keeping the old version, the previous version, the closed work folder, settings (paths, https webhooks, secrets never returned), rights on the admin routes, the public routes (validation of answers and CRM values, honeypot, session, the same code on a double submit, a new code on a retake, the short version, the limit per e-mail address), the two CRM payloads in test mode with exactly the fields from the build note, resending to a corrected address, the prepared PDF sheet (data, no Google Fonts, colours replaced), retention (PDF removed after the link expires, anonymised after two years) and erasing by e-mail address. Removes its own rows and files at the end. Cloudflare and SureContact are not called.


# Vormen (3.6.0, 3.7.0)

```sh
wp eval-file wp-content/plugins/sfp-page-config/tests/vormen-wp.php
```

Runs inside a real WordPress (a local test install, never a live site). Needs the post types `begrippen` and `tool` for the glossary and self-test checks (since 3.8.0-beta.3 the plugin only registers `tool` itself with the component Tools on). Checks the pillar: form per post, which category has which guide (oldest wins), the address (category or own), the request on the category address, the order in the card In this guide (links in the guide first, then newest), slider pages, terms with the link to the glossary, the grouped main pillar, the card after the lead, the line above the title, the page checks, the promo card per form, the punctuation in the H1 and the switched-off state. For the glossary term (3.7.0): the definition from the block Definition, the excerpt, the tooltip in an article (only for a published term with a definition), the page checks, related terms (chosen or from the pillar, max three), Used in, the next term, the blocks and the DefinedTerm data. Creates its own categories and posts and removes them at the end.

# Tools (3.8.0)

```sh
php tests/tool.php
php tests/tool.php <folder-with-json>
wp eval-file wp-content/plugins/sfp-page-config/tests/tool-wp.php [path-to-json]
```

`tests/tool.php` checks the tool engine with small stubs: normalising, the checks and the scoring for every outcome rule. With node installed it always lays the PHP scoring next to `assets/tool/test.js` on the same answers, for the built-in examples and for edge cases (rounding of x.5, dimension ids of only digits, nested fallbacks with labels, `==` on raw text, odd values from a share link, null values, a quadrant axis as plain text, safe links). With a folder it also checks every description in it and compares PHP and JS on random answer sets, complete and with missing or odd values. Without node the comparison is skipped with a note.

`tests/tool-wp.php` runs inside a real WordPress (a local test install, never a live site). Checks the switched-off state (no own post type), the meta field with revisions, refusing a broken description over REST, the output, drafts and password-protected tools, the same tool twice on a page, unsafe links, the schema, the page checks, the mail route (the link the server builds, no visitor text, the limits per address including +labels), the thumbs (unknown tool, switching vote, taking over old counts), SFP Beheer and the article layout. Creates its own tool and removes it at the end.
