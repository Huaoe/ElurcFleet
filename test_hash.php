<?php
$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo 'Generated hash: ' . $hash . PHP_EOL;
echo 'Immediate verify: ' . (password_verify($password, $hash) ? 'PASS' : 'FAIL') . PHP_EOL;

// Test with different password to confirm logic works
echo 'Wrong password test: ' . (password_verify('wrongpassword', $hash) ? 'PASS' : 'FAIL') . PHP_EOL;
