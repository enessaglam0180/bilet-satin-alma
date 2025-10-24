<?php
// Firma Admin kullanıcısı oluştur

$dbPath = __DIR__ . '/app.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Firma Admin kullanıcısı oluşturuluyor...\n\n";
    
    // Önce bir firma seç (Metro Turizm - ID: 1)
    $companyId = 1;
    
    $stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
    $stmt->execute([$companyId]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        die("Firma bulunamadı!\n");
    }
    
    echo "Firma: {$company['name']}\n\n";
    
    // Kullanıcı bilgileri
    $username = 'metroadmin';
    $password = 'metro123';
    $email = 'metro@admin.com';
    $fullName = 'Metro Turizm Yöneticisi';
    $role = 'firma_admin';
    
    // Şifreyi hashle
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    // Kullanıcıyı oluştur
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, full_name, role, virtual_credit)
        VALUES (?, ?, ?, ?, ?, 5000)
    ");
    $stmt->execute([$username, $email, $passwordHash, $fullName, $role]);
    $userId = $pdo->lastInsertId();
    
    echo "✅ Kullanıcı oluşturuldu (ID: $userId)\n";
    
    // Company_admins tablosuna ekle
    $stmt = $pdo->prepare("
        INSERT INTO company_admins (user_id, company_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$userId, $companyId]);
    
    echo "✅ Firma ile ilişkilendirildi\n\n";
    
    echo "═══════════════════════════════════\n";
    echo "FIRMA ADMIN GİRİŞ BİLGİLERİ\n";
    echo "═══════════════════════════════════\n";
    echo "Firma: {$company['name']}\n";
    echo "Kullanıcı Adı: $username\n";
    echo "Şifre: $password\n";
    echo "Rol: Firma Admin\n";
    echo "═══════════════════════════════════\n";
    
} catch (PDOException $e) {
    die("❌ Hata: " . $e->getMessage() . "\n");
}
?>
