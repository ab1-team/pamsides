<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Hash;
use App\Models\User;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hash = '$2y$12$oYRCWXp1dQCG5RObqx/V9.VhzMYRW5XUH11K6OMBLHDsYJDnQ1poe';
$check = Hash::check('password', $hash);

echo "Hash check for 'password': " . ($check ? "MATCH" : "NO MATCH") . "\n";

$users = User::all();
foreach ($users as $user) {
    echo "User: {$user->email}, Hash: {$user->password}, Check: " . (Hash::check('password', $user->password) ? "MATCH" : "NO MATCH") . "\n";
}
