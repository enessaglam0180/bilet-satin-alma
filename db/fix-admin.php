<?php
// Admin kullanıcısını kontrol et ve düzelt

$dbPath = __DIR__ . '/app.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Önce mevcut admin kullanıcısını kontrol et
    $stmt = $pdo->query("SELECT * FROM users WHERE username = 'admin'");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "Mevcut admin kullanıcısı bulundu:\n";
        echo "ID: " . $admin['id'] . "\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Role: " . $admin['role'] . "\n";
        echo "Password Hash: " . substr($admin['password'], 0, 30) . "...\n\n";
        
        // Admin kullanıcısını güncelle - yeni şifre hash'i
        $newPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE username = 'admin'");
        $stmt->execute([$newPassword]);
        
        echo "✅ Admin kullanıcısı güncellendi!\n";
        echo "Yeni şifre hash: " . substr($newPassword, 0, 30) . "...\n";
        
    } else {
        echo "Admin kullanıcısı bulunamadı, yeni oluşturuluyor...\n\n";
        
        // Yeni admin kullanıcısı oluştur
        $passwordHash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, role, virtual_credit) 
            VALUES ('admin', 'admin@test.com', ?, 'Admin Kullanıcı', 'admin', 1000)
        ");
        $stmt->execute([$passwordHash]);
        
        echo "✅ Admin kullanıcısı başarıyla oluşturuldu!\n";
    }
    
    echo "\n=== GİRİŞ BİLGİLERİ ===\n";
    echo "Kullanıcı Adı: admin\n";
    echo "Şifre: admin123\n";
    
    // Şifre doğrulamasını test et
    $stmt = $pdo->query("SELECT password FROM users WHERE username = 'admin'");
    $storedHash = $stmt->fetchColumn();
    
    if (password_verify('admin123', $storedHash)) {
        echo "\n✅ Şifre doğrulama testi başarılı!\n";
    } else {
        echo "\n❌ Şifre doğrulama testi başarısız!\n";
    }
    
} catch (PDOException $e) {
    die("❌ Veritabanı hatası: " . $e->getMessage());
}
?>
