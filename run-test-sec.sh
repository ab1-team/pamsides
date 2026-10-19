#!/usr/bin/env bash
# Lane SEC — verifikasi wajib ganti password (B3/B4). DB tes: 127.0.0.1:3392.
set -o pipefail
export DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3392 DB_DATABASE=pamsides DB_USERNAME=root DB_PASSWORD=root_secret
cd "$(dirname "$0")/backend" || exit 1
echo "=== BEFORE: jumlah tes feature (collect) ==="
./vendor/bin/pest tests/Feature/AuthTest.php tests/Feature/PasswordForceChangeTest.php tests/Feature/UsersResetPasswordCommandTest.php --list-tests 2>/dev/null | grep -c ' - '
echo "=== RUN: seluruh tests/Feature ==="
./vendor/bin/pest tests/Feature
frc=$?
echo "=== RUN: filter lane SEC ==="
./vendor/bin/pest tests/Feature/AuthTest.php tests/Feature/PasswordForceChangeTest.php tests/Feature/UsersResetPasswordCommandTest.php
rc=$?
echo "=== PINT ==="
./vendor/bin/pint --test \
  app/Http/Controllers/AuthController.php \
  app/Http/Middleware/EnforcePasswordChange.php \
  bootstrap/app.php \
  app/Models/User.php \
  app/Console/Commands/ImportLegacyCustomersCommand.php \
  app/Console/Commands/ImportLegacyUsersCommand.php \
  app/Console/Commands/UsersResetPasswordCommand.php \
  tests/Feature/PasswordForceChangeTest.php \
  tests/Feature/UsersResetPasswordCommandTest.php \
  database/migrations/2026_10_19_120000_add_must_change_password_to_users_table.php
prc=$?
echo "FEATURE_RC=$frc PEST_RC=$rc PINT_RC=$prc"
exit $(( frc + rc + prc ))
