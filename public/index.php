<?php
// Basit test sayfası
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                🚌 Bilet Satın Alma
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Giriş Yap</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Kayıt Ol</a>
                    </li>
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
                                    🔍 Sefer Ara
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Hakkında Kartlar -->
                <div class="row mt-5">
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">✅ Güvenli</h5>
                                <p class="card-text">Şifreli ve güvenli ödeme sistemi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">⚡ Hızlı</h5>
                                <p class="card-text">Saniyeler içinde bilet satın alın</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">💰 Ucuz</h5>
                                <p class="card-text">En iyi fiyatlarla bilet alın</p>
                            </div>
                        </div>
                    </div>
                </div>
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