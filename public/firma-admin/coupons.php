<?php
require_once __DIR__ . '/../../src/database.php';
require_once __DIR__ . '/../../src/auth.php';

if (!isLoggedIn() || getUserRole() !== 'firma_admin') {
    header("Location: ../access-denied.php");
    exit;
}

// Firma bilgisi
$stmt = $pdo->prepare("SELECT company_id FROM company_admins WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$companyAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
$companyId = $companyAdmin['company_id'];

// Firma adını al
$stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
$stmt->execute([$companyId]);
$companyName = $stmt->fetchColumn();

$message = '';
$messageType = 'info';

// Silme işlemi
if (isset($_GET['delete'])) {
    $couponId = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ? AND company_id = ?");
        $stmt->execute([$couponId, $companyId]);
        $message = "Kupon başarıyla silindi!";
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = "Hata: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Ekleme/Düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $couponId = $_POST['coupon_id'] ?? null;
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discountRate = floatval($_POST['discount_rate'] ?? 0);
    $usageLimit = intval($_POST['usage_limit'] ?? 100);
    $expiryDate = $_POST['expiry_date'] ?? '';
    
    if (empty($code) || $discountRate <= 0 || $discountRate > 100 || empty($expiryDate)) {
        $message = "Lütfen tüm alanları doğru doldurun! İndirim oranı 1-100 arası olmalı.";
        $messageType = 'danger';
    } else {
        try {
            if ($couponId) {
                // Güncelleme
                $stmt = $pdo->prepare("
                    UPDATE coupons 
                    SET code = ?, discount_rate = ?, usage_limit = ?, expiry_date = ?
                    WHERE id = ? AND company_id = ?
                ");
                $stmt->execute([$code, $discountRate, $usageLimit, $expiryDate, $couponId, $companyId]);
                $message = "Kupon başarıyla güncellendi!";
            } else {
                // Yeni ekleme
                $stmt = $pdo->prepare("
                    INSERT INTO coupons (code, discount_rate, company_id, is_global, usage_limit, used_count, expiry_date)
                    VALUES (?, ?, ?, 0, ?, 0, ?)
                ");
                $stmt->execute([$code, $discountRate, $companyId, $usageLimit, $expiryDate]);
                $message = "Kupon başarıyla eklendi!";
            }
            $messageType = 'success';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE') !== false) {
                $message = "Bu kupon kodu zaten kullanılıyor!";
            } else {
                $message = "Hata: " . $e->getMessage();
            }
            $messageType = 'danger';
        }
    }
}

// Düzenleme için kupon bilgisi
$editCoupon = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ? AND company_id = ?");
    $stmt->execute([$_GET['edit'], $companyId]);
    $editCoupon = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Kuponları listele
$stmt = $pdo->prepare("
    SELECT * FROM coupons 
    WHERE company_id = ? 
    ORDER BY id DESC
");
$stmt->execute([$companyId]);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kupon Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Bilet Satın Alma</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Firma Panelim</a></li>
                    <li class="nav-item"><a class="nav-link" href="routes.php">Seferlerim</a></li>
                    <li class="nav-item"><a class="nav-link active" href="coupons.php">Kuponlarım</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php">Ana Sayfa</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Çıkış</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="admin-header">
        <div class="container">
            <h1 class="mb-2">Kupon Yönetimi</h1>
            <p class="mb-0"><?php echo htmlspecialchars($companyName); ?> - İndirim Kuponları</p>
        </div>
    </div>

    <main class="container my-5">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Kupon Ekleme/Düzenleme Formu -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $editCoupon ? 'Kupon Düzenle' : 'Yeni Kupon Ekle'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($editCoupon): ?>
                                <input type="hidden" name="coupon_id" value="<?php echo $editCoupon['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Kupon Kodu</label>
                                <input type="text" 
                                       class="form-control" 
                                       name="code" 
                                       value="<?php echo $editCoupon ? htmlspecialchars($editCoupon['code']) : ''; ?>"
                                       placeholder="Örn: YENI2025" 
                                       style="text-transform: uppercase;"
                                       required>
                                <small class="text-muted">Büyük harflerle, benzersiz olmalı</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">İndirim Oranı (%)</label>
                                <input type="number" 
                                       class="form-control" 
                                       name="discount_rate" 
                                       value="<?php echo $editCoupon ? $editCoupon['discount_rate'] : ''; ?>"
                                       min="1" 
                                       max="100" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Kullanım Limiti</label>
                                <input type="number" 
                                       class="form-control" 
                                       name="usage_limit" 
                                       value="<?php echo $editCoupon ? $editCoupon['usage_limit'] : '100'; ?>"
                                       min="1" 
                                       required>
                                <small class="text-muted">Kaç kez kullanılabilir</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Son Kullanma Tarihi</label>
                                <input type="date" 
                                       class="form-control" 
                                       name="expiry_date" 
                                       value="<?php echo $editCoupon ? $editCoupon['expiry_date'] : date('Y-m-d', strtotime('+30 days')); ?>"
                                       min="<?php echo date('Y-m-d'); ?>"
                                       required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $editCoupon ? 'Güncelle' : 'Kupon Ekle'; ?>
                                </button>
                                <?php if ($editCoupon): ?>
                                    <a href="coupons.php" class="btn btn-secondary">İptal</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Kupon Listesi -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Kuponlar (<?php echo count($coupons); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($coupons)): ?>
                            <p class="text-muted text-center py-5">Henüz kupon eklenmemiş.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kod</th>
                                            <th>İndirim</th>
                                            <th>Kullanım</th>
                                            <th>Son Kullanma</th>
                                            <th>Durum</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coupons as $coupon): 
                                            $isExpired = strtotime($coupon['expiry_date']) < time();
                                            $isExhausted = $coupon['used_count'] >= $coupon['usage_limit'];
                                            $isActive = !$isExpired && !$isExhausted;
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($coupon['code']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">%<?php echo $coupon['discount_rate']; ?></span>
                                                </td>
                                                <td>
                                                    <?php echo $coupon['used_count']; ?> / <?php echo $coupon['usage_limit']; ?>
                                                </td>
                                                <td>
                                                    <?php echo date('d.m.Y', strtotime($coupon['expiry_date'])); ?>
                                                </td>
                                                <td>
                                                    <?php if ($isActive): ?>
                                                        <span class="badge bg-success">Aktif</span>
                                                    <?php elseif ($isExpired): ?>
                                                        <span class="badge bg-danger">Süresi Doldu</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Tükendi</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="?edit=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                                                    <a href="?delete=<?php echo $coupon['id']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Bu kuponu silmek istediğinizden emin misiniz?')">Sil</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bilgilendirme -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">💡 Kupon Kullanım Bilgisi</h6>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Oluşturduğunuz kuponlar sadece <strong><?php echo htmlspecialchars($companyName); ?></strong> seferlerinde geçerlidir.</li>
                            <li>Müşteriler bilet satın alırken kupon kodunu girebilir.</li>
                            <li>İndirim oranı 1-100 arası olmalıdır.</li>
                            <li>Kullanım limiti dolunca veya tarihi geçince kupon otomatik devre dışı kalır.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p class="mb-0">&copy; 2025 Bilet Satın Alma Platformu</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Kupon kodu otomatik büyük harf
        document.querySelector('input[name="code"]').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>
