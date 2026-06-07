<?php
require __DIR__ . '/POS/vendor/autoload.php';
$app = require_once __DIR__ . '/POS/bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = User::all();
echo "Total users in system: " . count($users) . "\n";
foreach ($users as $user) {
    echo "ID: " . $user->id . "\n";
    echo "Full Name: '" . $user->full_name . "'\n";
    echo "Username: '" . $user->username . "'\n";
    echo "Email: '" . $user->email . "'\n";
    echo "Phone: '" . $user->phone . "'\n";
    echo "Password Hash: '" . $user->password . "'\n";
    echo "Is Active: " . $user->is_active . "\n";
    
    // Check if Hash::check works with '123456'
    $check_123456 = Hash::check('123456', $user->password);
    echo "Does '123456' match? " . ($check_123456 ? 'YES' : 'NO') . "\n";
    
    // Check if Hash::check works with '1234'
    $check_1234 = Hash::check('1234', $user->password);
    echo "Does '1234' match? " . ($check_1234 ? 'YES' : 'NO') . "\n";
    echo "-------------------\n";
}
