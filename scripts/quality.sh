#!/usr/bin/env bash
set -euo pipefail

SITE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../../.." && pwd)"
MODULE_DIR="${SITE_ROOT}/web/modules/contrib/shorthand"

if command -v lando >/dev/null 2>&1; then
  RUNNER=(lando)
else
  RUNNER=()
fi

phpcs_bin="${SITE_ROOT}/vendor/bin/phpcs"
phpstan_bin="${SITE_ROOT}/vendor/bin/phpstan"
phpstan_config="${SITE_ROOT}/web/core/phpstan.neon.dist"

if [[ ! -x "${phpcs_bin}" ]]; then
  echo "phpcs not found at ${phpcs_bin}. Install drupal/coder in the site root." >&2
  exit 1
fi

if [[ ! -x "${phpstan_bin}" ]]; then
  echo "phpstan not found at ${phpstan_bin}. Install phpstan/phpstan in the site root." >&2
  exit 1
fi

if [[ ! -f "${phpstan_config}" ]]; then
  echo "PHPStan config not found at ${phpstan_config}." >&2
  exit 1
fi

"${RUNNER[@]}" "${phpcs_bin}" --standard=Drupal,DrupalPractice "${MODULE_DIR}"
"${RUNNER[@]}" "${phpstan_bin}" analyse -c "${phpstan_config}" "${MODULE_DIR}"
