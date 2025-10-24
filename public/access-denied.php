<?php
require_once __DIR__ . '/../src/auth.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erişim Reddedildi - Bilet Satın Alma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Bilet Satın Alma</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Ana Sayfa</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="my-tickets.php">Biletlerim</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Giriş Yap</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card border-danger shadow-sm">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" fill="currentColor" class="text-danger" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                            </svg>
                        </div>
                        <h1 class="display-4 text-danger mb-3">Erişim Reddedildi</h1>
                        <h4 class="card-title mb-4">403 - Yetkisiz Erişim</h4>
                        <p class="card-text text-muted mb-4">Bu sayfaya erişim yetkiniz bulunmamaktadır. Bu sayfayı görüntülemek için gerekli izinlere sahip değilsiniz.</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="index.php" class="btn btn-primary">Ana Sayfaya Dön</a>
                            <?php if (!isLoggedIn()): ?>
                                <a href="login.php" class="btn btn-secondary">Giriş Yap</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p class="mb-0">&copy; 2025 Bilet Satın Alma Platformu</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>