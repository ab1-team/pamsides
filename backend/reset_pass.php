<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Hash;
use App\Models\User;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = User::all();
foreach ($users as $user) {
    $user->password = Hash::make('password');
    $user->save();
    echo "Reset password for: {$user->email}\n";
}
echo "Done.\n";
