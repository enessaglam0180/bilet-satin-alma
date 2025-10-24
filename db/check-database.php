<?php
// Veritabanı içeriğini kontrol et

$dbPath = __DIR__ . '/app.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ GERÇEK VERİTABANI KULLANILMAKTADIR!\n";
    echo "==========================================\n\n";
    
    echo "📍 Veritabanı Dosyası:\n";
    echo "Konum: " . realpath($dbPath) . "\n";
    echo "Boyut: " . round(filesize($dbPath) / 1024, 2) . " KB\n";
    echo "Tip: SQLite Database\n\n";
    
    echo "📊 VERİTABANI İÇERİĞİ:\n";
    echo "==========================================\n\n";
    
    // Firmalar
    $count = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
    echo "🏢 Firmalar: $count adet\n";
    $companies = $pdo->query("SELECT name FROM companies LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($companies as $name) {
        echo "   - $name\n";
    }
    echo "\n";
    
    // Seferler
    $count = $pdo->query("SELECT COUNT(*) FROM routes")->fetchColumn();
    $futureCount = $pdo->query("SELECT COUNT(*) FROM routes WHERE departure_date >= DATE('now')")->fetchColumn();
    echo "🚌 Seferler: $count adet (Gelecek: $futureCount)\n";
    
    $routes = $pdo->query("
        SELECT r.departure_point, r.arrival_point, r.departure_date, r.departure_time, c.name as company 
        FROM routes r 
        JOIN companies c ON r.company_id = c.id 
        WHERE r.departure_date >= DATE('now')
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($routes as $route) {
        echo "   - {$route['company']}: {$route['departure_point']} → {$route['arrival_point']} ({$route['departure_date']} {$route['departure_time']})\n";
    }
    echo "\n";
    
    // Kullanıcılar
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "👤 Kullanıcılar: $count adet\n";
    
    $users = $pdo->query("SELECT username, role FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        echo "   - {$user['username']} ({$user['role']})\n";
    }
    echo "\n";
    
    // Biletler
    $count = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
    $activeCount = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'active'")->fetchColumn();
    echo "🎫 Biletler: $count adet (Aktif: $activeCount)\n\n";
    
    // Kuponlar
    $count = $pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
    echo "🎟️ Kuponlar: $count adet\n\n";
    
    // Tablolar
    echo "📋 TABLOLAR:\n";
    echo "==========================================\n";
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "- $table: $count kayıt\n";
    }
    
    echo "\n✅ TÜM VERİLER GERÇEK VERİTABANINDA SAKLANMAKTADIR!\n";
    echo "Bu bir SQLite veritabanıdır ve tüm veriler kalıcı olarak saklanır.\n";
    
} catch (PDOException $e) {
    die("❌ Veritabanı hatası: " . $e->getMessage());
}
?>
