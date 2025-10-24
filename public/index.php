<?php
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/auth.php';

// Tüm güncel seferleri getir (bugünden sonraki)
try {
    $stmt = $pdo->prepare("
        SELECT 
            routes.*,
            companies.name AS company_name
        FROM routes
        INNER JOIN companies ON routes.company_id = companies.id
        WHERE routes.departure_date >= DATE('now')
        AND routes.available_seats > 0
        ORDER BY routes.departure_date ASC, routes.departure_time ASC
        LIMIT 20
    ");
    $stmt->execute();
    $allRoutes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allRoutes = [];
}
?>


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satın Alma</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
            <a class="navbar-brand" href="index.php">
            Bilet Satın Alma
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Ana Sayfa</a>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    <!-- Giriş yapıldıysa -->
                    <li class="nav-item">
                        <a class="nav-link" href="my-tickets.php">Biletlerim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profil</a>
                    </li>
                    <?php if (getUserRole() === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_panel.php">Admin Panel</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                    </li>
                <?php else: ?>
                    <!-- Giriş yapılmadıysa -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Giriş Yap</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Kayıt Ol</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

    <!-- Hero Section -->
    <section class="bg-light py-5">
        <div class="container">
            <h1 class="display-5 fw-bold mb-3">Bilet Ara ve Satın Al</h1>
            <p class="lead text-muted">Türkiye'nin en güvenilir bilet satış platformu</p>
        </div>
    </section>

    <!-- Ana İçerik -->
    <main class="container my-5">
        <div class="row">
            <div class="col-lg-10 offset-lg-1">
                <!-- Arama Formu -->
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Sefer Ara</h5>
                        
                        <form method="GET" action="search.php">
                            <div class="row g-3">
                                <!-- Kalkış Noktası -->
                                <div class="col-md-4">
                                    <label for="departure" class="form-label">Kalkış Noktası</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="departure" 
                                        name="departure" 
                                        placeholder="İstanbul"
                                        required
                                    >
                                </div>
                                
                                <!-- Varış Noktası -->
                                <div class="col-md-4">
                                    <label for="arrival" class="form-label">Varış Noktası</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="arrival" 
                                        name="arrival" 
                                        placeholder="Ankara"
                                        required
                                    >
                                </div>
                                
                                <!-- Tarih -->
                                <div class="col-md-4">
                                    <label for="date" class="form-label">Tarih</label>
                                    <input 
                                        type="date" 
                                        class="form-control" 
                                        id="date" 
                                        name="date"
                                        required
                                    >
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Sefer Ara
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Popüler Seferler -->
                <?php if (!empty($allRoutes)): ?>
                <div class="mt-5">
                    <h3 class="mb-4">Popüler Seferler</h3>
                    <div class="row">
                        <?php foreach ($allRoutes as $route): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">
                                            <?php echo htmlspecialchars($route['company_name']); ?>
                                        </h5>
                                        <p class="mb-2">
                                            <strong><?php echo htmlspecialchars($route['departure_point']); ?></strong>
                                            →
                                            <strong><?php echo htmlspecialchars($route['arrival_point']); ?></strong>
                                        </p>
                                        <p class="text-muted mb-2">
                                            <?php echo date('d.m.Y', strtotime($route['departure_date'])); ?> - <?php echo htmlspecialchars($route['departure_time']); ?>
                                        </p>
                                        <p class="mb-2">
                                            <span class="badge bg-success">
                                                <?php echo $route['available_seats']; ?> koltuk müsait
                                            </span>
                                        </p>
                                        <h4 class="text-success mb-3">
                                            ₺<?php echo number_format($route['price'], 2, ',', '.'); ?>
                                        </h4>
                                        <?php if (isLoggedIn()): ?>
                                            <a href="buy-ticket.php?route_id=<?php echo $route['id']; ?>" class="btn btn-primary w-100">
                                                Bilet Satın Al
                                            </a>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-warning w-100">
                                                Giriş Yapın
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2025 Bilet Satın Alma Platformu. Tüm hakları saklıdır.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>