<?php
require '/fleetbase/api/vendor/autoload.php';
$app = require_once '/fleetbase/api/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$password = 'password123';
$newHash = password_hash($password, PASSWORD_BCRYPT);
echo 'Generated hash: ' . $newHash . PHP_EOL;

$users = Fleetbase\Models\User::whereIn('email', ['admin@stalabard.com', 'thomasberrod42@gmail.com'])->get();
foreach ($users as $user) {
    // Bypass the mutator by directly setting attributes
    $user->setRawAttributes(['password' => $newHash], true);
    $user->save();
    echo 'Updated: ' . $user->email . PHP_EOL;
}

// Verify
$user = Fleetbase\Models\User::where('email', 'admin@stalabard.com')->first();
$isValid = password_verify($password, $user->password);
echo 'Verification: ' . ($isValid ? 'SUCCESS' : 'FAILED') . PHP_EOL;
