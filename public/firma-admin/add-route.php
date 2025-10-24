<?php
require_once __DIR__ . '/../../src/database.php';
require_once __DIR__ . '/../../src/auth.php';

if (!isLoggedIn() || getUserRole() !== 'firma_admin') {
    header("Location: ../access-denied.php");
    exit;
}

$stmt = $pdo->prepare("SELECT company_id FROM company_admins WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$companyAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
$companyId = $companyAdmin['company_id'];

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure = $_POST['departure_point'] ?? '';
    $arrival = $_POST['arrival_point'] ?? '';
    $date = $_POST['departure_date'] ?? '';
    $time = $_POST['departure_time'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $totalSeats = intval($_POST['total_seats'] ?? 40);
    
    if (empty($departure) || empty($arrival) || empty($date) || empty($time) || $price <= 0) {
        $message = "Lütfen tüm alanları doldurun!";
        $messageType = 'danger';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO routes (company_id, departure_point, arrival_point, departure_date, departure_time, price, total_seats, available_seats)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$companyId, $departure, $arrival, $date, $time, $price, $totalSeats, $totalSeats]);
            header("Location: routes.php?message=Sefer başarıyla eklendi&type=success");
            exit;
        } catch (PDOException $e) {
            $message = "Hata: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Sefer Ekle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Bilet Satın Alma</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Firma Panelim</a></li>
                    <li class="nav-item"><a class="nav-link" href="routes.php">Seferlerim</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Çıkış</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Yeni Sefer Ekle</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kalkış Noktası</label>
                                    <input type="text" class="form-control" name="departure_point" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Varış Noktası</label>
                                    <input type="text" class="form-control" name="arrival_point" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tarih</label>
                                    <input type="date" class="form-control" name="departure_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Saat</label>
                                    <input type="time" class="form-control" name="departure_time" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Fiyat (₺)</label>
                                    <input type="number" class="form-control" name="price" step="0.01" min="1" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Toplam Koltuk Sayısı</label>
                                    <input type="number" class="form-control" name="total_seats" value="40" min="1" max="60" required>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Sefer Ekle</button>
                                <a href="routes.php" class="btn btn-secondary">İptal</a>
                            </div>
                        </form>
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
