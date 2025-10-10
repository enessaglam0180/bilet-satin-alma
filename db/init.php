<?php
// db/init.php
// Veritabanını ilk kez kurulduğunda başlatma

$dbPath = __DIR__ . '/app.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // schema.sql'i oku
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
    // Tüm SQL komutlarını çalıştır
    $pdo->exec($sql);
    
    echo "✅ Veritabanı başarıyla oluşturuldu!";
    
} catch (PDOException $e) {
    die("❌ Veritabanı hatası: " . $e->getMessage());
}
?>