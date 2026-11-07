<?php
// create_admin.php - run once, then delete this file

require __DIR__ . '/app/includes/db.php';

$name = 'Admin';
$pin  = '1234';   // change this to your own PIN
$role = 'admin';

$hash = password_hash($pin, PASSWORD_DEFAULT);

$stmt = db()->prepare('INSERT INTO users (name, role, pin_hash) VALUES (?, ?, ?)');
$stmt->execute([$name, $role, $hash]);

echo "Admin created. Name: $name, PIN: $pin";