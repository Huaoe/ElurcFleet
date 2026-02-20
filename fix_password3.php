<?php
require '/fleetbase/api/vendor/autoload.php';
$app = require_once '/fleetbase/api/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$password = 'password123';
$newHash = password_hash($password, PASSWORD_BCRYPT);
echo 'Generated hash: ' . $newHash . PHP_EOL;

// Use DB facade to bypass model mutators
$emails = ['admin@stalabard.com', 'thomasberrod42@gmail.com'];
foreach ($emails as $email) {
    $affected = DB::table('users')
        ->where('email', $email)
        ->update(['password' => $newHash]);
    echo 'Updated ' . $affected . ' row(s) for: ' . $email . PHP_EOL;
}

// Verify
$user = DB::table('users')->where('email', 'admin@stalabard.com')->first();
$isValid = password_verify($password, $user->password);
echo 'Verification: ' . ($isValid ? 'SUCCESS' : 'FAILED') . PHP_EOL;
echo 'Stored hash: ' . $user->password . PHP_EOL;
