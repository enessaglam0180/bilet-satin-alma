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

// CRUD İşlemleri
$message = '';
$messageType = 'info';

// Silme işlemi
if (isset($_GET['delete'])) {
    $routeId = $_GET['delete'];
    try {
        // Önce bu sefere ait bilet var mı kontrol et
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE route_id = ? AND status = 'active'");
        $stmt->execute([$routeId]);
        $activeTickets = $stmt->fetchColumn();
        
        if ($activeTickets > 0) {
            $message = "Bu sefere ait aktif biletler olduğu için silinemez!";
            $messageType = 'danger';
        } else {
            $stmt = $pdo->prepare("DELETE FROM routes WHERE id = ? AND company_id = ?");
            $stmt->execute([$routeId, $companyId]);
            $message = "Sefer başarıyla silindi!";
            $messageType = 'success';
        }
    } catch (PDOException $e) {
        $message = "Hata: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Seferleri listele
$stmt = $pdo->prepare("
    SELECT * FROM routes 
    WHERE company_id = ? 
    ORDER BY departure_date DESC, departure_time DESC
");
$stmt->execute([$companyId]);
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Yönetimi</title>
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
                    <li class="nav-item"><a class="nav-link active" href="routes.php">Seferlerim</a></li>
                    <li class="nav-item"><a class="nav-link" href="coupons.php">Kuponlarım</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php">Ana Sayfa</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Çıkış</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="admin-header">
        <div class="container">
            <h1 class="mb-2">Sefer Yönetimi</h1>
            <p class="mb-0">Firmanıza ait seferleri yönetin</p>
        </div>
    </div>

    <main class="container my-5">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Seferler (<?php echo count($routes); ?>)</h5>
                <a href="add-route.php" class="btn btn-primary">+ Yeni Sefer Ekle</a>
            </div>
            <div class="card-body">
                <?php if (empty($routes)): ?>
                    <p class="text-muted text-center py-5">Henüz sefer eklenmemiş.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kalkış</th>
                                    <th>Varış</th>
                                    <th>Tarih</th>
                                    <th>Saat</th>
                                    <th>Fiyat</th>
                                    <th>Toplam Koltuk</th>
                                    <th>Müsait</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($routes as $route): ?>
                                    <tr>
                                        <td><?php echo $route['id']; ?></td>
                                        <td><?php echo htmlspecialchars($route['departure_point']); ?></td>
                                        <td><?php echo htmlspecialchars($route['arrival_point']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($route['departure_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($route['departure_time']); ?></td>
                                        <td>₺<?php echo number_format($route['price'], 2); ?></td>
                                        <td><?php echo $route['total_seats']; ?></td>
                                        <td><span class="badge bg-success"><?php echo $route['available_seats']; ?></span></td>
                                        <td>
                                            <a href="edit-route.php?id=<?php echo $route['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                                            <a href="?delete=<?php echo $route['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Bu seferi silmek istediğinizden emin misiniz?')">Sil</a>
                                        </td>
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
