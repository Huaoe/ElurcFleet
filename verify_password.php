<?php
require '/fleetbase/api/vendor/autoload.php';
$app = require_once '/fleetbase/api/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = Fleetbase\Models\User::where('email', 'admin@stalabard.com')->first();
if ($user) {
    echo 'Password hash: ' . $user->password . PHP_EOL;
    echo 'Hash length: ' . strlen($user->password) . PHP_EOL;
    
    // Test password verification
    $testPassword = 'password123';
    $isValid = password_verify($testPassword, $user->password);
    echo 'Password verification: ' . ($isValid ? 'VALID' : 'INVALID') . PHP_EOL;
    
    // Try Laravel's Hash check
    $isValidLaravel = Hash::check($testPassword, $user->password);
    echo 'Laravel Hash check: ' . ($isValidLaravel ? 'VALID' : 'INVALID') . PHP_EOL;
}
