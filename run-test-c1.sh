#!/usr/bin/env bash
# Lane C1 — verifikasi import:monthly-bills anchor legacy_installation_id.
set -uo pipefail
cd "$(dirname "$0")/backend" || exit 1
export DB_PORT=3391 DB_HOST=127.0.0.1 DB_PASSWORD=root_secret
php -l app/Console/Commands/ImportLegacyMonthlyBillsCommand.php || exit 1
php -l tests/Feature/LegacyMonthlyBillsIdentityTest.php || exit 1
echo "=== PEST target ==="
./vendor/bin/pest --filter LegacyMonthlyBillsIdentityTest 2>&1 | tail -15
echo "=== PINT ==="
./vendor/bin/pint --test app/Console/Commands/ImportLegacyMonthlyBillsCommand.php tests/Feature/LegacyMonthlyBillsIdentityTest.php 2>&1 | tail -10
echo "=== jumlah tes FEATURE (collect) ==="
./vendor/bin/phpunit --testsuite Feature --list-tests 2>/dev/null | grep -c '^ - '
