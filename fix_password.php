<?php
require '/fleetbase/api/vendor/autoload.php';
$app = require_once '/fleetbase/api/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$newHash = password_hash('password123', PASSWORD_BCRYPT);
echo 'New hash: ' . $newHash . PHP_EOL;

$users = Fleetbase\Models\User::whereIn('email', ['admin@stalabard.com', 'thomasberrod42@gmail.com'])->get();
foreach ($users as $user) {
    $user->password = $newHash;
    $user->save();
    echo 'Updated: ' . $user->email . PHP_EOL;
}

// Verify
$user = Fleetbase\Models\User::where('email', 'admin@stalabard.com')->first();
$isValid = password_verify('password123', $user->password);
echo 'Verification: ' . ($isValid ? 'SUCCESS' : 'FAILED') . PHP_EOL;
