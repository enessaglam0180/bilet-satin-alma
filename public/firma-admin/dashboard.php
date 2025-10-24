<?php
require_once __DIR__ . '/../../src/database.php';
require_once __DIR__ . '/../../src/auth.php';

// Firma admin kontrolü
if (!isLoggedIn() || getUserRole() !== 'firma_admin') {
    header("Location: ../access-denied.php");
    exit;
}

// Firma admin'in firma ID'sini getir
$stmt = $pdo->prepare("SELECT company_id FROM company_admins WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$companyAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$companyAdmin) {
    die("Firma bilgisi bulunamadı!");
}

$companyId = $companyAdmin['company_id'];

// Firma bilgilerini getir
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$companyId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// İstatistikler
$stmt = $pdo->prepare("SELECT COUNT(*) FROM routes WHERE company_id = ?");
$stmt->execute([$companyId]);
$totalRoutes = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM routes WHERE company_id = ? AND departure_date >= DATE('now')");
$stmt->execute([$companyId]);
$futureRoutes = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM tickets t
    JOIN routes r ON t.route_id = r.id
    WHERE r.company_id = ? AND t.status = 'active'
");
$stmt->execute([$companyId]);
$activeTickets = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM coupons WHERE company_id = ?
");
$stmt->execute([$companyId]);
$totalCoupons = $stmt->fetchColumn();

$message = $_GET['message'] ?? '';
$messageType = $_GET['type'] ?? 'info';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Yönetim Paneli - <?php echo htmlspecialchars($company['name']); ?></title>
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
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Firma Panelim</a></li>
                    <li class="nav-item"><a class="nav-link" href="routes.php">Seferlerim</a></li>
                    <li class="nav-item"><a class="nav-link" href="coupons.php">Kuponlarım</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php">Ana Sayfa</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Çıkış (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="admin-header">
        <div class="container">
            <h1 class="mb-2">Firma Yönetim Paneli</h1>
            <p class="mb-0"><?php echo htmlspecialchars($company['name']); ?></p>
        </div>
    </div>

    <main class="container my-5">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- İstatistikler -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h2 class="mb-0"><?php echo $totalRoutes; ?></h2>
                        <p class="card-title mb-0">Toplam Sefer</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h2 class="mb-0"><?php echo $futureRoutes; ?></h2>
                        <p class="card-title mb-0">Aktif Sefer</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h2 class="mb-0"><?php echo $activeTickets; ?></h2>
                        <p class="card-title mb-0">Satılan Bilet</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h2 class="mb-0"><?php echo $totalCoupons; ?></h2>
                        <p class="card-title mb-0">Aktif Kupon</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hızlı İşlemler -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Sefer Yönetimi</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Firmanıza ait seferleri görüntüleyin, yeni sefer ekleyin veya mevcut seferleri düzenleyin.</p>
                        <div class="d-grid gap-2">
                            <a href="routes.php" class="btn btn-primary">Seferleri Yönet</a>
                            <a href="routes.php?action=add" class="btn btn-outline-primary">Yeni Sefer Ekle</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Kupon Yönetimi</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Firmanız için özel indirim kuponları oluşturun ve yönetin.</p>
                        <div class="d-grid gap-2">
                            <a href="coupons.php" class="btn btn-success">Kuponları Yönet</a>
                            <a href="coupons.php?action=add" class="btn btn-outline-success">Yeni Kupon Oluştur</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Son Seferler -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Son Eklenen Seferler</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->prepare("
                    SELECT * FROM routes 
                    WHERE company_id = ? 
                    ORDER BY id DESC 
                    LIMIT 5
                ");
                $stmt->execute([$companyId]);
                $recentRoutes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($recentRoutes)): ?>
                    <p class="text-muted">Henüz sefer eklenmemiş.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kalkış</th>
                                    <th>Varış</th>
                                    <th>Tarih</th>
                                    <th>Saat</th>
                                    <th>Fiyat</th>
                                    <th>Müsait</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRoutes as $route): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($route['departure_point']); ?></td>
                                        <td><?php echo htmlspecialchars($route['arrival_point']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($route['departure_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($route['departure_time']); ?></td>
                                        <td>₺<?php echo number_format($route['price'], 2); ?></td>
                                        <td><span class="badge bg-success"><?php echo $route['available_seats']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p class="mb-0">&copy; 2025 Bilet Satın Alma Platformu</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
