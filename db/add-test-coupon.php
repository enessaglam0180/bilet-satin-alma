<?php
// Test kuponu ekle

$dbPath = __DIR__ . '/app.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Test kuponları oluşturuluyor...\n\n";
    
    // Global kuponlar (tüm firmalara geçerli)
    $coupons = [
        [
            'code' => 'INDIRIM20',
            'discount_rate' => 20,
            'usage_limit' => 100,
            'expiry_date' => date('Y-m-d', strtotime('+30 days')),
            'is_global' => 1,
            'company_id' => null
        ],
        [
            'code' => 'YENI50',
            'discount_rate' => 50,
            'usage_limit' => 50,
            'expiry_date' => date('Y-m-d', strtotime('+7 days')),
            'is_global' => 1,
            'company_id' => null
        ],
        [
            'code' => 'ERKEN10',
            'discount_rate' => 10,
            'usage_limit' => 200,
            'expiry_date' => date('Y-m-d', strtotime('+60 days')),
            'is_global' => 1,
            'company_id' => null
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT OR REPLACE INTO coupons (code, discount_rate, company_id, is_global, usage_limit, used_count, expiry_date)
        VALUES (?, ?, ?, ?, ?, 0, ?)
    ");
    
    foreach ($coupons as $coupon) {
        $stmt->execute([
            $coupon['code'],
            $coupon['discount_rate'],
            $coupon['company_id'],
            $coupon['is_global'],
            $coupon['usage_limit'],
            $coupon['expiry_date']
        ]);
        
        echo "✅ Kupon eklendi: {$coupon['code']} (%{$coupon['discount_rate']} indirim)\n";
    }
    
    echo "\n📋 KULLANILABILIR KUPONLAR:\n";
    echo "===================================\n";
    echo "1. INDIRIM20 - %20 indirim (100 kullanım)\n";
    echo "2. YENI50 - %50 indirim (50 kullanım) - 7 gün geçerli\n";
    echo "3. ERKEN10 - %10 indirim (200 kullanım)\n";
    echo "\nTüm kuponlar global olarak tüm firmalara geçerlidir.\n";
    
} catch (PDOException $e) {
    die("❌ Veritabanı hatası: " . $e->getMessage());
}
?>
