<?php
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/auth.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    header("Location: /login.php");
    exit;
}

$ticketId = $_GET['ticket_id'] ?? null;

if (!$ticketId) {
    header("Location: /my-tickets.php");
    exit;
}

$message = '';
$messageType = '';

// Bilet bilgilerini getir
try {
    $stmt = $pdo->prepare("
        SELECT 
            tickets.*,
            routes.departure_date,
            routes.departure_time,
            routes.departure_point,
            routes.arrival_point,
            companies.name AS company_name
        FROM tickets
        INNER JOIN routes ON tickets.route_id = routes.id
        INNER JOIN companies ON routes.company_id = companies.id
        WHERE tickets.id = ? AND tickets.user_id = ?
    ");
    $stmt->execute([$ticketId, $_SESSION['user_id']]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        die("Bilet bulunamadı veya size ait değil!");
    }
    
    // Bilet zaten iptal edilmiş mi?
    if ($ticket['status'] === 'cancelled') {
        header("Location: /my-tickets.php?error=already_cancelled");
        exit;
    }
    
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// İptal işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kalkış tarih ve saatini birleştir
    $departureDateTime = $ticket['departure_date'] . ' ' . $ticket['departure_time'];
    $departureTimestamp = strtotime($departureDateTime);
    $currentTimestamp = time();
    
    // Kalkışa kalan süreyi hesapla (saniye cinsinden)
    $timeRemaining = $departureTimestamp - $currentTimestamp;
    
    // 1 saat = 3600 saniye
    $oneHourInSeconds = 3600;
    
    // 1 saatten az kaldıysa iptal edilemez
    if ($timeRemaining < $oneHourInSeconds) {
        $message = '❌ Kalkışa 1 saatten az süre kaldığı için bilet iptal edilemez!';
        $messageType = 'danger';
    } else {
        // İptal işlemi (Transaction)
        try {
            $pdo->beginTransaction();
            
            // 1. Bilet durumunu iptal et
            $stmt = $pdo->prepare("
                UPDATE tickets 
                SET status = 'cancelled' 
                WHERE id = ?
            ");
            $stmt->execute([$ticketId]);
            
            // 2. Kullanıcıya parayı iade et
            $stmt = $pdo->prepare("
                UPDATE users 
                SET virtual_credit = virtual_credit + ? 
                WHERE id = ?
            ");
            $stmt->execute([$ticket['price'], $_SESSION['user_id']]);
            
            // 3. Seferdeki müsait koltuk sayısını arttır
            $stmt = $pdo->prepare("
                UPDATE routes 
                SET available_seats = available_seats + 1 
                WHERE id = ?
            ");
            $stmt->execute([$ticket['route_id']]);
            
            $pdo->commit();
            
            // Başarılı - biletlerim sayfasına yönlendir
            header("Location: /my-tickets.php?cancelled=1");
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = '❌ İptal işlemi sırasında hata oluştu: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Kalkışa kalan süreyi hesapla (gösterim için)
$departureDateTime = $ticket['departure_date'] . ' ' . $ticket['departure_time'];
$departureTimestamp = strtotime($departureDateTime);
$currentTimestamp = time();
$timeRemaining = $departureTimestamp - $currentTimestamp;

// Saat ve dakikaya çevir
$hoursRemaining = floor($timeRemaining / 3600);
$minutesRemaining = floor(($timeRemaining % 3600) / 60);

// İptal edilebilir mi kontrolü
$canCancel = $timeRemaining >= 3600;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet İptal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">🚌 Bilet Satın Alma</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Ana Sayfa</a></li>
                    <li class="nav-item"><a class="nav-link" href="my-tickets.php">Biletlerim</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Ana İçerik -->
    <main class="container my-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4">Bilet İptal</h3>

                        <!-- Mesaj -->
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Bilet Bilgileri -->
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Bilet Bilgileri</h5>
                                <hr>
                                <p><strong>Firma:</strong> <?php echo htmlspecialchars($ticket['company_name']); ?></p>
                                <p><strong>Güzergah:</strong><br>
                                    <?php echo htmlspecialchars($ticket['departure_point']); ?> 
                                    → 
                                    <?php echo htmlspecialchars($ticket['arrival_point']); ?>
                                </p>
                                <p><strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($ticket['departure_date'])); ?></p>
                                <p><strong>Saat:</strong> <?php echo htmlspecialchars($ticket['departure_time']); ?></p>
                                <p><strong>Koltuk No:</strong> <?php echo $ticket['seat_number']; ?></p>
                                <p><strong>Ücret:</strong> <span class="text-success h5">₺<?php echo number_format($ticket['price'], 2); ?></span></p>
                            </div>
                        </div>

                        <!-- Kalkışa Kalan Süre -->
                        <div class="alert <?php echo $canCancel ? 'alert-info' : 'alert-danger'; ?>">
                            <h6><strong>⏰ Kalkışa Kalan Süre:</strong></h6>
                            <?php if ($timeRemaining > 0): ?>
                                <p class="mb-0 h5">
                                    <?php echo $hoursRemaining; ?> saat <?php echo $minutesRemaining; ?> dakika
                                </p>
                            <?php else: ?>
                                <p class="mb-0 text-danger"><strong>Sefer kalkış yapmış</strong></p>
                            <?php endif; ?>
                        </div>

                        <!-- İptal Kuralı -->
                        <div class="alert alert-warning">
                            <strong>⚠️ İptal Kuralı:</strong><br>
                            Kalkış saatine en az 1 saat kala iptal yapabilirsiniz.
                            İptal edildiğinde ücret hesabınıza iade edilecektir.
                        </div>

                        <!-- İptal Butonu -->
                        <?php if ($canCancel): ?>
                            <form method="POST">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-danger" 
                                            onclick="return confirm('Bileti iptal etmek istediğinizden emin misiniz? ₺<?php echo number_format($ticket['price'], 2); ?> hesabınıza iade edilecektir.')">
                                        ❌ Bileti İptal Et
                                    </button>
                                    <a href="my-tickets.php" class="btn btn-secondary">
                                        Vazgeç
                                    </a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <strong>❌ Bu bilet iptal edilemez!</strong><br>
                                Kalkışa 1 saatten az süre kaldığı için iptal işlemi yapılamaz.
                            </div>
                            <a href="my-tickets.php" class="btn btn-secondary">
                                Biletlerime Dön
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p>&copy; 2025 Bilet Satın Alma Platformu</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>