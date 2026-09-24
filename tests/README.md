# Branding regression tests

Run the production colour resolver with Astra's API and with raw stored options:

```sh
php tests/brand-regression.php
php tests/brand-regression.php raw
```

The tests execute the production brand section with small WordPress stubs. They verify the eight host mappings, the FL foreground exception, dynamic theme values, preserved overrides and unsafe-input rejection. They do not replace a real WordPress deployment or a rendered desktop/mobile check.

The palette references for SwS and FL deliberately identify roles, not colour values. If those sites' palette roles move, update the mapping and test it against their live settings before deployment.
