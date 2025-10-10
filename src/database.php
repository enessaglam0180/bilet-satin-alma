<?php
// src/database.php
// Veritabanı bağlantısı

$dbPath = __DIR__ . '/../db/app.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SQLite'de foreign key'leri etkinleştir
    $pdo->exec("PRAGMA foreign_keys = ON");
    
} catch (PDOException $e) {
    die("❌ Veritabanı hatası: " . $e->getMessage());
}
?>