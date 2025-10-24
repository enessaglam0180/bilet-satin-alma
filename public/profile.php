<?php
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/auth.php';

// Giriş kontrolü
requireLogin();

$message = '';
$messageType = '';

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $_SESSION['user_id']]);
            $message = 'Profil başarıyla güncellendi!';
            $messageType = 'success';
            
            // Güncel bilgileri al
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $message = 'Hata: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
    
    // Şifre değiştirme
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($current_password, $user['password'])) {
            $message = 'Mevcut şifre yanlış!';
            $messageType = 'danger';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Yeni şifreler eşleşmiyor!';
            $messageType = 'danger';
        } elseif (strlen($new_password) < 6) {
            $message = 'Yeni şifre en az 6 karakter olmalıdır!';
            $messageType = 'danger';
        } else {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['user_id']]);
            $message = 'Şifre başarıyla değiştirildi!';
            $messageType = 'success';
        }
    }
}

// Bilet istatistikleri
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(price) as spent FROM tickets WHERE user_id = ? AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - Bilet Satın Alma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Bilet Satın Alma</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Ana Sayfa</a></li>
                    <li class="nav-item"><a class="nav-link" href="my-tickets.php">Biletlerim</a></li>
                    <li class="nav-item"><a class="nav-link active" href="profile.php">Profil</a></li>
                    <?php if (getUserRole() === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin_panel.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Ana İçerik -->
    <main class="container my-5">
        <h2 class="mb-4">Profilim</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Sol Kolon - İstatistikler -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                        </div>
                        <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                        <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">İstatistikler</h6>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Aktif Bilet:</span>
                            <strong><?php echo $stats['total'] ?? 0; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Toplam Harcama:</span>
                            <strong>₺<?php echo number_format($stats['spent'] ?? 0, 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Bakiye:</span>
                            <strong class="text-success">₺<?php echo number_format($user['virtual_credit'], 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sağ Kolon - Formlar -->
            <div class="col-md-8">
                <!-- Profil Bilgileri -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Profil Bilgileri</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <small class="text-muted">Kullanıcı adı değiştirilemez</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ad Soyad</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-posta</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">Güncelle</button>
                        </form>
                    </div>
                </div>

                <!-- Şifre Değiştir -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Şifre Değiştir</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Mevcut Şifre</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Yeni Şifre</label>
                                <input type="password" class="form-control" name="new_password" required>
                                <small class="text-muted">En az 6 karakter</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Yeni Şifre (Tekrar)</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-warning">Şifre Değiştir</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p class="mb-0">&copy; 2025 Bilet Satın Alma Platformu</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
