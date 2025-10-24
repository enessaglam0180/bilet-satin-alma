<?php
// Firma admin için test kuponu oluştur

$dbPath = __DIR__ . '/app.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Firma admin test kuponları oluşturuluyor...\n\n";
    
    // Metro Turizm (company_id = 1) için kuponlar
    $coupons = [
        [
            'code' => 'METRO20',
            'discount_rate' => 20,
            'company_id' => 1,
            'usage_limit' => 50,
            'expiry_date' => date('Y-m-d', strtotime('+30 days'))
        ],
        [
            'code' => 'METRO50',
            'discount_rate' => 50,
            'company_id' => 1,
            'usage_limit' => 20,
            'expiry_date' => date('Y-m-d', strtotime('+7 days'))
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT OR REPLACE INTO coupons (code, discount_rate, company_id, is_global, usage_limit, used_count, expiry_date)
        VALUES (?, ?, ?, 0, ?, 0, ?)
    ");
    
    foreach ($coupons as $coupon) {
        $stmt->execute([
            $coupon['code'],
            $coupon['discount_rate'],
            $coupon['company_id'],
            $coupon['usage_limit'],
            $coupon['expiry_date']
        ]);
        
        echo "✅ Kupon eklendi: {$coupon['code']} (%{$coupon['discount_rate']} indirim) - Metro Turizm\n";
    }
    
    echo "\n📋 TEST KUPONLARI:\n";
    echo "==================\n";
    echo "METRO20 - %20 indirim (Sadece Metro Turizm)\n";
    echo "METRO50 - %50 indirim (Sadece Metro Turizm)\n";
    
} catch (PDOException $e) {
    die("❌ Hata: " . $e->getMessage());
}
?>
