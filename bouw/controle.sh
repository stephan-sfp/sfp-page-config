#!/usr/bin/env bash
# Controles voor elke pull request en elke release:
# php -l op alle PHP, node --check op eigen JS, de losse tests in tests/.
set -euo pipefail
cd "$(dirname "$0")/.."
fout=0
while IFS= read -r -d '' f; do
  php -l "$f" >/dev/null || { echo "php -l faalt: $f"; fout=1; }
done < <(find . -name '*.php' -not -path './.git/*' -not -path './vendor/*' -print0)
while IFS= read -r -d '' f; do
  node --check "$f" || { echo "node --check faalt: $f"; fout=1; }
done < <(find assets bouw -name '*.js' -not -path 'assets/vendor/*' -not -name '*.min.js' -print0)
for t in tests/*.php; do
  case "$t" in *-wp.php) continue ;; esac
  if ! uit=$(php "$t" 2>&1); then echo "Test faalt: $t"; echo "$uit" | tail -20; fout=1; else echo "OK  $t: $(echo "$uit" | tail -1)"; fi
done
exit $fout
