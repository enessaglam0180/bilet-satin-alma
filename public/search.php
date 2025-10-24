<?php
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/auth.php';

// Form verilerini al
$departure = $_GET['departure'] ?? '';
$arrival = $_GET['arrival'] ?? '';
$date = $_GET['date'] ?? '';

// Boş ise hata
$routes = [];
$errorMessage = '';

if (empty($departure) || empty($arrival) || empty($date)) {
    $errorMessage = 'Lütfen tüm alanları doldurun!';
} else {
    // Veritabanından seferleri ara
    try {
        $stmt = $pdo->prepare("
            SELECT 
                routes.*,
                companies.name AS company_name
            FROM routes
            INNER JOIN companies ON routes.company_id = companies.id
            WHERE 
                LOWER(routes.departure_point) LIKE LOWER(?)
                AND LOWER(routes.arrival_point) LIKE LOWER(?)
                AND routes.departure_date = ?
                AND routes.available_seats > 0
            ORDER BY routes.departure_time ASC
        ");
        
        $stmt->execute([
            '%' . $departure . '%',
            '%' . $arrival . '%',
            $date
        ]);
        
        $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($routes)) {
            $errorMessage = 'Bu kriterlere uygun sefer bulunamadı.';
        }
        
    } catch (PDOException $e) {
        $errorMessage = 'Veritabanı hatası: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Arama Sonuçları</title>
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
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="my-tickets.php">Biletlerim</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile.php">Profil</a></li>
                        <?php if (getUserRole() === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="admin_panel.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Giriş Yap</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Kayıt Ol</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Ana İçerik -->
    <main class="container my-5">
        <!-- Arama Kriterleri -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Arama Kriterleri</h5>
                <p class="mb-0">
                    <strong>Kalkış:</strong> <?php echo htmlspecialchars($departure); ?> 
                    → 
                    <strong>Varış:</strong> <?php echo htmlspecialchars($arrival); ?>
                    <strong class="ms-3">Tarih:</strong> <?php echo htmlspecialchars($date); ?>
                </p>
                <a href="index.php" class="btn btn-sm btn-secondary mt-2">Yeni Arama Yap</a>
            </div>
        </div>

        <!-- Hata Mesajı -->
        <?php if ($errorMessage): ?>
            <div class="alert alert-warning">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Sefer Listesi -->
        <?php if (!empty($routes)): ?>
            <h3 class="mb-4">Bulunan Seferler (<?php echo count($routes); ?>)</h3>
            
            <div class="row">
                <?php foreach ($routes as $route): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <!-- Firma Adı -->
                                <h5 class="card-title text-primary">
                                    <?php echo htmlspecialchars($route['company_name']); ?>
                                </h5>
                                
                                <!-- Güzergah -->
                                <p class="mb-2">
                                    <strong><?php echo htmlspecialchars($route['departure_point']); ?></strong>
                                    →
                                    <strong><?php echo htmlspecialchars($route['arrival_point']); ?></strong>
                                </p>
                                
                                <!-- Tarih ve Saat -->
                                <p class="text-muted mb-2">
                                    <?php echo date('d.m.Y', strtotime($route['departure_date'])); ?> - <?php echo htmlspecialchars($route['departure_time']); ?>
                                </p>
                                
                                <!-- Koltuk Bilgisi -->
                                <p class="mb-2">
                                    <span class="badge bg-success">
                                        <?php echo $route['available_seats']; ?> koltuk müsait
                                    </span>
                                </p>
                                
                                <!-- Fiyat -->
                                <h4 class="text-success mb-3">
                                    ₺<?php echo number_format($route['price'], 2, ',', '.'); ?>
                                </h4>
                                
                                <!-- Buton -->
                                <?php if (isLoggedIn()): ?>
                                    <a 
                                        href="buy-ticket.php?route_id=<?php echo $route['id']; ?>" 
                                        class="btn btn-primary w-100"
                                    >
                                        Bilet Satın Al
                                    </a>
                                <?php else: ?>
                                    <a 
                                        href="login.php" 
                                        class="btn btn-warning w-100"
                                    >
                                        Giriş Yapın
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p>&copy; 2025 Bilet Satın Alma Platformu</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>