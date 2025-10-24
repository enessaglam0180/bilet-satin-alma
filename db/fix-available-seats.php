<?php
// Müsait koltuk sayılarını düzelt

$dbPath = __DIR__ . '/app.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Koltuk sayıları düzeltiliyor...\n\n";
    
    // Tüm seferleri al
    $routes = $pdo->query("SELECT id, total_seats FROM routes")->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    
    foreach ($routes as $route) {
        // Bu sefere ait aktif biletlerin sayısını bul
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM tickets 
            WHERE route_id = ? AND status = 'active'
        ");
        $stmt->execute([$route['id']]);
        $soldTickets = $stmt->fetchColumn();
        
        // Müsait koltuk = Toplam koltuk - Satılan bilet
        $availableSeats = $route['total_seats'] - $soldTickets;
        
        // Güncelle
        $stmt = $pdo->prepare("
            UPDATE routes 
            SET available_seats = ? 
            WHERE id = ?
        ");
        $stmt->execute([$availableSeats, $route['id']]);
        
        $updated++;
    }
    
    echo "✅ $updated sefer güncellendi!\n\n";
    
    // Kontrol için birkaç örnek göster
    echo "Örnek Seferler:\n";
    echo "================\n";
    
    $stmt = $pdo->query("
        SELECT id, departure_point, arrival_point, total_seats, available_seats,
               (SELECT COUNT(*) FROM tickets WHERE route_id = routes.id AND status = 'active') as sold_tickets
        FROM routes 
        LIMIT 5
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Sefer #{$row['id']}: {$row['departure_point']} → {$row['arrival_point']}\n";
        echo "  Toplam: {$row['total_seats']} | Satılan: {$row['sold_tickets']} | Müsait: {$row['available_seats']}\n";
    }
    
} catch (PDOException $e) {
    die("❌ Hata: " . $e->getMessage());
}
?>
