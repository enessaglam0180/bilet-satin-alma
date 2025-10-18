<?php
// Test verileri ekle

require_once __DIR__ . '/../src/database.php';

try {
    // Bugünden 3 gün sonrasına sefer ekle
    $futureDate = date('Y-m-d', strtotime('+3 days'));
    
    // Metro Turizm için sefer
    $stmt = $pdo->prepare("
        INSERT INTO routes (company_id, departure_point, arrival_point, departure_date, departure_time, price, total_seats, available_seats)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // İstanbul -> Ankara
    $stmt->execute([1, 'İstanbul', 'Ankara', $futureDate, '09:00', 150.00, 50, 50]);
    $stmt->execute([1, 'İstanbul', 'Ankara', $futureDate, '14:00', 180.00, 50, 50]);
    $stmt->execute([1, 'İstanbul', 'Ankara', $futureDate, '21:00', 160.00, 50, 50]);
    
    // Ankara -> İstanbul
    $stmt->execute([1, 'Ankara', 'İstanbul', $futureDate, '10:00', 150.00, 50, 50]);
    $stmt->execute([1, 'Ankara', 'İstanbul', $futureDate, '15:00', 170.00, 50, 50]);
    
    // İstanbul -> İzmir
    $stmt->execute([1, 'İstanbul', 'İzmir', $futureDate, '08:00', 200.00, 50, 50]);
    $stmt->execute([1, 'İstanbul', 'İzmir', $futureDate, '20:00', 210.00, 50, 50]);
    
    echo "✅ Test seferleri başarıyla eklendi!\n";
    echo "📅 Sefer tarihi: " . date('d.m.Y', strtotime($futureDate)) . "\n";
    
} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage();
}
?>