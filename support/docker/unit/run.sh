#!/bin/sh
#
# Runs `arc unit` inside the `unit` container. Use `bin/unit` on the host, which
# starts the container and runs this.
#
# Messages go to stderr. In report mode (PHLAB_UNIT_REPORT, for
# `PhlabDockerUnitTestEngine`), the results go to that file instead of stdout.

set -eu

readonly ARCANIST=/usr/local/src/arcanist
readonly PHABRICATOR=/usr/local/src/phabricator
readonly PHLAB=/usr/local/src/phlab

# Prints the output of a failed step, then exits.
fail() {
  printf '%s\n' "$2" >&2
  echo "bin/unit: $1" >&2
  exit 1
}

# `bin/unit` starts this in the directory to run `arc` from.
workdir=$(pwd)
cd "$PHLAB"

# Install when `composer.lock` is newer than the last install. Composer leaves
# `installed.json` alone when it has nothing to do (e.g. after switching
# branches and back), so touch it. The lock keeps two runs in this checkout from
# installing at once.
installed=vendor/composer/installed.json
mkdir -p vendor
(
  flock 9
  if [ ! -f "$installed" ] ||
     [ -n "$(find composer.lock -newer "$installed")" ]; then
    echo 'Installing Composer dependencies...' >&2
    output=$(composer install --no-interaction --no-progress 2>&1) ||
      fail 'Installing Composer dependencies failed.' "$output"
    touch "$installed"
  fi
) 9> vendor/.install-lock

# `arc` builds XHPAST on demand, but its Makefile passes an x86-only flag that
# fails on arm64 (e.g. Apple Silicon), so build it first without that flag. The
# lock keeps two runs from building into the shared Arcanist at once, and the
# version check rebuilds a binary that an interrupted build left broken.
xhpast="$ARCANIST/support/xhpast"
(
  flock 9
  if ! "$ARCANIST/src/parser/xhpast/bin/xhpast" --version >/dev/null 2>&1; then
    echo 'Building XHPAST (first run only)...' >&2
    output=$(make -C "$xhpast" SKIP_PARSER=1 SKIP_SCANNER=1 \
      CPPFLAGS='-fPIC -Wall -O3' clean all install 2>&1) ||
      fail 'Building XHPAST failed.' "$output"
  fi
) 9> "$xhpast/.build-lock"

if [ -n "${PHLAB_UNIT_NEW_DATABASE:-}" ]; then
  echo 'Loading the database schema...' >&2
fi

# Phabricator reads its configuration from the database on startup, so the
# schema has to exist before any test runs. A new test database starts from
# Phabricator's quickstart schema, which needs no adjusting, and skipping that
# step saves most of the time an upgrade with nothing to do would take. The
# upgrade takes a lock without waiting for it, so retry while a run from another
# checkout holds it.
attempt=1
until output=$("$PHABRICATOR/bin/storage" upgrade --force --no-adjust 2>&1); do
  if [ "$attempt" -eq 3 ]; then
    fail 'Loading the database schema failed; "bin/unit --down" resets it.' \
      "$output"
  fi
  attempt=$((attempt + 1))
  sleep 2
done

# An interrupted run leaves the databases of its test fixtures behind. Drop
# those more than 10 minutes old, rather than all of them like `bin/storage
# destroy --unittest-fixtures`, which would also drop those of a run in progress
# from another checkout.
output=$(php 2>&1 <<'PHP'
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(
  getenv('PHABRICATOR_MYSQL_HOST'),
  getenv('PHABRICATOR_MYSQL_USER'));
$stale = $conn->query(
  "SELECT TABLE_SCHEMA FROM information_schema.TABLES
    WHERE TABLE_SCHEMA LIKE 'phabricator\\_unittest\\_%'
    GROUP BY TABLE_SCHEMA
    HAVING MAX(CREATE_TIME) < NOW() - INTERVAL 10 MINUTE");
foreach ($stale as $row) {
  $conn->query('DROP DATABASE `'.$row['TABLE_SCHEMA'].'`');
}
PHP
) || fail 'Removing old test fixtures failed.' "$output"

cd "$workdir"

if [ -z "${PHLAB_UNIT_REPORT:-}" ]; then
  exec "$ARCANIST/bin/arc" unit "$@"
fi

# With `--output ugly`, `arc` prints the results as JSON on one last line,
# after the results it shows as tests run. Pass those through, and keep the JSON
# as the report. Without results, `arc` prints why in its place.
no_effect='No tests to run.'
report="$PHLAB/$PHLAB_UNIT_REPORT"
{
  status=0
  "$ARCANIST/bin/arc" unit --output ugly "$@" || status=$?
  echo "$status" > /tmp/arc-status
} | {
  while IFS= read -r line || [ -n "$line" ]; do
    case "$line" in
      '[{'*|'[]'|"$no_effect") printf '%s' "$line" > "$report" ;;
      *) printf '%s\n' "$line" ;;
    esac
  done
}

exit "$(cat /tmp/arc-status)"
