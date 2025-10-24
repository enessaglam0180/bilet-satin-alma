<?php
// Veritabanına daha fazla sefer ekle

$dbPath = __DIR__ . '/app.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Yeni seferler ekleniyor...\n\n";
    
    // Önce mevcut firmaları kontrol et
    $companies = $pdo->query("SELECT id, name FROM companies")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($companies)) {
        echo "Firma bulunamadı! Önce firma ekleyin.\n";
        exit;
    }
    
    echo "Mevcut firmalar:\n";
    foreach ($companies as $company) {
        echo "- {$company['name']} (ID: {$company['id']})\n";
    }
    echo "\n";
    
    // Şehirler listesi
    $cities = [
        'İstanbul', 'Ankara', 'İzmir', 'Antalya', 'Bursa', 
        'Adana', 'Gaziantep', 'Konya', 'Kayseri', 'Eskişehir',
        'Trabzon', 'Samsun', 'Denizli', 'Mersin', 'Malatya'
    ];
    
    // Farklı güzergahlar oluştur
    $routes = [
        ['İstanbul', 'Ankara'],
        ['İstanbul', 'İzmir'],
        ['İstanbul', 'Antalya'],
        ['İstanbul', 'Bursa'],
        ['Ankara', 'İzmir'],
        ['Ankara', 'Antalya'],
        ['Ankara', 'Konya'],
        ['İzmir', 'Antalya'],
        ['İzmir', 'Bursa'],
        ['Bursa', 'Antalya'],
        ['Adana', 'İstanbul'],
        ['Adana', 'Ankara'],
        ['Gaziantep', 'İstanbul'],
        ['Konya', 'İstanbul'],
        ['Eskişehir', 'İzmir'],
        ['Trabzon', 'Ankara'],
        ['Samsun', 'İstanbul'],
        ['Denizli', 'İstanbul'],
        ['Mersin', 'Ankara'],
        ['Malatya', 'İstanbul'],
    ];
    
    // Saatler
    $times = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'];
    
    // Fiyat aralıkları (km'ye göre)
    $priceRanges = [
        'short' => [80, 150],    // Kısa mesafe
        'medium' => [150, 300],  // Orta mesafe
        'long' => [300, 500]     // Uzun mesafe
    ];
    
    $insertedCount = 0;
    
    // Önümüzdeki 15 gün için seferler ekle
    for ($day = 0; $day < 15; $day++) {
        $date = date('Y-m-d', strtotime("+$day days"));
        
        // Her güzergah için seferler ekle
        foreach ($routes as $route) {
            $departure = $route[0];
            $arrival = $route[1];
            
            // Her güzergah için 2-4 farklı firma seferi ekle
            $numCompanies = rand(2, min(4, count($companies)));
            $selectedCompanies = array_rand($companies, $numCompanies);
            
            if (!is_array($selectedCompanies)) {
                $selectedCompanies = [$selectedCompanies];
            }
            
            foreach ($selectedCompanies as $companyIndex) {
                $company = $companies[$companyIndex];
                
                // Rastgele saat seç
                $time = $times[array_rand($times)];
                
                // Mesafeye göre fiyat belirle
                $distanceType = (in_array($departure, ['İstanbul', 'Ankara', 'İzmir']) && 
                                 in_array($arrival, ['İstanbul', 'Ankara', 'İzmir'])) ? 'medium' : 'long';
                
                if ($departure == 'Bursa' || $arrival == 'Bursa') {
                    $distanceType = 'short';
                }
                
                $priceRange = $priceRanges[$distanceType];
                $price = rand($priceRange[0], $priceRange[1]);
                
                // Koltuk sayısı
                $totalSeats = rand(30, 50);
                $availableSeats = rand(15, $totalSeats);
                
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO routes (company_id, departure_point, arrival_point, departure_date, departure_time, price, total_seats, available_seats)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $company['id'],
                        $departure,
                        $arrival,
                        $date,
                        $time,
                        $price,
                        $totalSeats,
                        $availableSeats
                    ]);
                    
                    $insertedCount++;
                    
                } catch (PDOException $e) {
                    // Aynı sefer varsa atla
                    continue;
                }
            }
        }
    }
    
    echo "✅ Toplam $insertedCount yeni sefer eklendi!\n\n";
    
    // İstatistik göster
    $totalRoutes = $pdo->query("SELECT COUNT(*) FROM routes")->fetchColumn();
    $futureRoutes = $pdo->query("SELECT COUNT(*) FROM routes WHERE departure_date >= DATE('now')")->fetchColumn();
    
    echo "📊 İstatistikler:\n";
    echo "- Toplam sefer sayısı: $totalRoutes\n";
    echo "- Gelecek seferler: $futureRoutes\n";
    
} catch (PDOException $e) {
    die("❌ Veritabanı hatası: " . $e->getMessage());
}
?>
