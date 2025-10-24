<?php
// Firmaları düzelt ve yenilerini ekle

$dbPath = __DIR__ . '/app.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Firmalar güncelleniyor...\n\n";
    
    // Mevcut firmaları temizle
    $pdo->exec("DELETE FROM companies WHERE id > 1");
    
    // Yeni firmaları ekle
    $companies = [
        'Metro Turizm',
        'Pamukkale Turizm',
        'Kamil Koç',
        'Ulusoy',
        'Varan Turizm',
        'Truva Turizm',
        'Anadolu Jet',
        'Has Turizm',
        'Süha Turizm',
        'Nilüfer Turizm'
    ];
    
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO companies (name) VALUES (?)");
    
    foreach ($companies as $company) {
        $stmt->execute([$company]);
    }
    
    echo "✅ Firmalar başarıyla güncellendi!\n\n";
    
    // Firma listesini göster
    $result = $pdo->query("SELECT * FROM companies ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Güncel Firma Listesi:\n";
    foreach ($result as $company) {
        echo "- {$company['name']} (ID: {$company['id']})\n";
    }
    
} catch (PDOException $e) {
    die("❌ Veritabanı hatası: " . $e->getMessage());
}
?>
