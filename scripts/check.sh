#!/usr/bin/env bash
# Run the same three checks drupal.org's GitLab CI runs, locally.
#
# There is deliberately NO Woodpecker pipeline in this repository. The canonical
# CI for a contributed Drupal module is drupal.org's GitLab CI (.gitlab-ci.yml),
# which runs the whole matrix of supported core versions against real databases.
# A second, weaker Woodpecker copy that could not do that would be a green check
# guarding very little, which is worse than no check at all.
#
# This script is the developer's local equivalent. It builds a throwaway Drupal
# site, symlinks this module into it and runs phpcs, phpstan and phpunit.
#
#   ./scripts/check.sh              # reuse an existing harness if present
#   ./scripts/check.sh --fresh      # rebuild the harness from scratch
#
# Requires php and composer on PATH. Takes a few minutes the first time.

set -euo pipefail

MODULE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HARNESS="${PULSE_DRUPAL_HARNESS:-${TMPDIR:-/tmp}/pulse-drupal-harness}"
CORE_CONSTRAINT="${DRUPAL_CORE:-^11}"

command -v php >/dev/null || { echo "REFUSING: php is not on PATH"; exit 1; }
command -v composer >/dev/null || { echo "REFUSING: composer is not on PATH"; exit 1; }

if [ "${1:-}" = "--fresh" ]; then
  rm -rf "$HARNESS"
fi

if [ ! -d "$HARNESS/web/core" ]; then
  echo "==> building a Drupal $CORE_CONSTRAINT harness in $HARNESS (first run, a few minutes)"
  composer create-project "drupal/recommended-project:$CORE_CONSTRAINT" "$HARNESS" \
    --no-interaction --quiet
  # -W so composer may move packages the project template already locked;
  # without it, core-dev's phpunit and json-schema constraints cannot resolve.
  (cd "$HARNESS" && composer require --dev "drupal/core-dev:$CORE_CONSTRAINT" -W \
    --no-interaction --quiet)
fi

echo "==> linking the module into the harness"
mkdir -p "$HARNESS/web/modules/custom"
rm -rf "$HARNESS/web/modules/custom/pulse_analytics"
ln -s "$MODULE_DIR" "$HARNESS/web/modules/custom/pulse_analytics"

cd "$HARNESS"
TARGET="web/modules/custom/pulse_analytics"
STATUS=0

echo
echo "==> phpcs (Drupal, DrupalPractice)"
./vendor/bin/phpcs --standard=Drupal,DrupalPractice \
  --extensions=php,module,inc,install,test,info,yml "$TARGET" \
  && echo "    clean" || STATUS=1

echo
echo "==> phpstan"
cat > phpstan-pulse.neon <<NEON
parameters:
  level: 1
  customRulesetUsed: true
  paths:
    - $TARGET
NEON
./vendor/bin/phpstan analyse -c phpstan-pulse.neon --no-progress || STATUS=1

echo
echo "==> phpunit (unit + kernel)"
# SQLite in memory: kernel tests need a database, and nothing here touches one
# in a way that cares which. Run from web/core because that is where Drupal's
# phpunit.xml.dist and its bootstrap live.
[ -f web/core/phpunit.xml ] || cp web/core/phpunit.xml.dist web/core/phpunit.xml
( cd web/core \
  && SIMPLETEST_DB="sqlite://localhost/:memory:" \
     ../../vendor/bin/phpunit -c phpunit.xml ../modules/custom/pulse_analytics/tests ) || STATUS=1

echo
if [ "$STATUS" -eq 0 ]; then
  echo "ALL CHECKS PASSED"
else
  echo "CHECKS FAILED"
fi
exit "$STATUS"
