<?php
// db/add-credit.php
require_once __DIR__ . '/../src/database.php';

$stmt = $pdo->prepare("UPDATE users SET virtual_credit = 1000 WHERE username = 'mes'");
$stmt->execute();

echo "✅ Kredi eklendi!\n";
?>