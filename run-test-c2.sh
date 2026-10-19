#!/usr/bin/env bash
# Lane C2 — verifikasi import:bill-payments (anchor legacy_installation_id).
# DB tes lane: 127.0.0.1:3393 (pamsides / root).
set -uo pipefail
cd "$(dirname "$0")/backend" || exit 1

export DB_HOST=127.0.0.1 DB_PORT=3393 DB_DATABASE=pamsides DB_USERNAME=root DB_PASSWORD=root_secret
export LEGACY_DB_HOST=127.0.0.1 LEGACY_DB_PORT=3393 LEGACY_DB_DATABASE=pamsides_legacy_ignored LEGACY_DB_USERNAME=root LEGACY_DB_PASSWORD=root_secret
export APP_ENV=testing

echo "== BEFORE: jumlah tes =="
BEFORE=$(DB_PORT=3393 DB_PASSWORD=root_secret ./vendor/bin/pest --list-tests 2>/dev/null | grep -c ' - ')
echo "tests before: $BEFORE"

echo "== NEW TEST =="
DB_PORT=3393 DB_PASSWORD=root_secret ./vendor/bin/pest tests/Feature/LegacyBillPaymentsIdentityTest.php 2>&1 | tail -30
NEW_RC=${PIPESTATUS[0]}

echo "== SHELL LINT =="
bash -n ../run-test-c2.sh && echo "shell ok"

echo "== AFTER: jumlah tes =="
AFTER=$(DB_PORT=3393 DB_PASSWORD=root_secret ./vendor/bin/pest --list-tests 2>/dev/null | grep -c ' - ')
echo "tests after: $AFTER"

echo "== PINT =="
./vendor/bin/pint --test app/Console/Commands/ImportLegacyBillPaymentsCommand.php tests/Feature/LegacyBillPaymentsIdentityTest.php 2>&1 | tail -15
PINT_RC=${PIPESTATUS[0]}

echo "new_test_rc=$NEW_RC pint_rc=$PINT_RC"
