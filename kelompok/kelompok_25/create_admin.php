<?php
// Generate password hash untuk admin
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hash: $hash\n\n";

echo "Jalankan query ini di phpMyAdmin:\n\n";
echo "UPDATE users SET password_hash = '$hash' WHERE email = 'admin@inventory.com';\n";
