<?php
// Yarın ve 2 gün sonrası için sefer ekle
require_once __DIR__ . '/../src/database.php';

try {
    // Yarın
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    // 2 gün sonra
    $dayAfterTomorrow = date('Y-m-d', strtotime('+2 days'));
    
    $stmt = $pdo->prepare("
        INSERT INTO routes (company_id, departure_point, arrival_point, departure_date, departure_time, price, total_seats, available_seats)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Yarın sabah 10:00 (iptal edilebilir)
    $stmt->execute([1, 'İstanbul', 'Ankara', $tomorrow, '10:00', 150.00, 50, 50]);
    
    // Yarın 30 dakika sonra (iptal edilemez - test için)
    $currentTime = date('H:i', strtotime('+30 minutes'));
    $stmt->execute([1, 'İstanbul', 'İzmir', date('Y-m-d'), $currentTime, 200.00, 50, 50]);
    
    echo "✅ Test seferleri eklendi!\n";
    echo "📅 Yarın: " . date('d.m.Y', strtotime($tomorrow)) . " 10:00\n";
    echo "📅 Bugün: " . date('d.m.Y') . " " . $currentTime . " (30 dk sonra - iptal edilemez test)\n";
    
} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage();
}
?>