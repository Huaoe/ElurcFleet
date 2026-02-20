<?php
require '/fleetbase/api/vendor/autoload.php';
$app = require_once '/fleetbase/api/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$users = [
    'admin@stalabard.com',
    'thomasberrod42@gmail.com'
];

foreach ($users as $email) {
    $user = Fleetbase\Models\User::where('email', $email)->first();
    if ($user) {
        $user->password = bcrypt('password123');
        $user->save();
        echo 'Updated password for: ' . $user->email . PHP_EOL;
    }
}
