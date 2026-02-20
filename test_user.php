<?php
require '/fleetbase/api/vendor/autoload.php';
$app = require_once '/fleetbase/api/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = Fleetbase\Models\User::where('email', 'admin@stalabard.com')->first();
echo 'User found: ' . ($user ? 'YES' : 'NO') . PHP_EOL;
if ($user) {
    echo 'ID: ' . $user->id . PHP_EOL;
    echo 'Email: ' . $user->email . PHP_EOL;
    echo 'Status: ' . $user->status . PHP_EOL;
    echo 'Deleted: ' . ($user->deleted_at ? 'YES' : 'NO') . PHP_EOL;
}
