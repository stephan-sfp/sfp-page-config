#!/usr/bin/env bash
# Bouwt het pakket voor de sites: sfp-page-config.zip met de map sfp-page-config/.
# Alleen wat in git staat, zonder wat .gitattributes uitsluit (tests/, .github/, bouw/).
# Daarna verkleinen (bouw/verklein.js, als node er is) en het controlegetal.
#
#   bash bouw/pakket.sh [uitvoermap]
set -euo pipefail
cd "$(dirname "$0")/.."
uit="${1:-dist}"
werk="$(mktemp -d)"
trap 'rm -rf "$werk"' EXIT
mkdir -p "$uit"
uit="$(cd "$uit" && pwd)"
git archive --format=tar --prefix=sfp-page-config/ HEAD | tar -x -C "$werk"
if [ -d bouw/node_modules ]; then
  node bouw/verklein.js "$werk/sfp-page-config"
else
  echo "Let op: bouw/node_modules ontbreekt, pakket zonder verkleinde bestanden (npm ci in bouw/)." >&2
fi
for verboden in tests .github bouw; do
  if [ -e "$werk/sfp-page-config/$verboden" ]; then
    echo "Fout: $verboden zit in het pakket" >&2; exit 1
  fi
done
rm -f "$uit/sfp-page-config.zip"
( cd "$werk" && zip -qrX "$uit/sfp-page-config.zip" sfp-page-config )
som=$(sha256sum "$uit/sfp-page-config.zip" | cut -d' ' -f1)
printf '%s  sfp-page-config.zip\n' "$som" > "$uit/sfp-page-config.zip.sha256"
echo "$som"
