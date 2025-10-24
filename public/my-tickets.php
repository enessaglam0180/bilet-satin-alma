<?php
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/auth.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    header("Location: /login.php");
    exit;
}

$message = '';
$messageType = '';

// Mesajları kontrol et
if (isset($_GET['success'])) {
    $message = '✅ Bilet başarıyla satın alındı!';
    $messageType = 'success';
} elseif (isset($_GET['cancelled'])) {
    $message = '✅ Bilet başarıyla iptal edildi! Ücret hesabınıza iade edilmiştir.';
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] === 'already_cancelled') {
        $message = '❌ Bu bilet zaten iptal edilmiş!';
        $messageType = 'danger';
    }
}

// Kullanıcının biletlerini getir
try {
    $stmt = $pdo->prepare("
        SELECT 
            tickets.*,
            routes.departure_point,
            routes.arrival_point,
            routes.departure_date,
            routes.departure_time,
            companies.name AS company_name
        FROM tickets
        INNER JOIN routes ON tickets.route_id = routes.id
        INNER JOIN companies ON routes.company_id = companies.id
        WHERE tickets.user_id = ?
        ORDER BY tickets.purchase_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kullanıcının kredisini getir
    $stmt = $pdo->prepare("SELECT virtual_credit FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userCredit = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biletlerim</title>
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
                    <li class="nav-item"><a class="nav-link active" href="my-tickets.php">Biletlerim</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profil</a></li>
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
        <h2 class="mb-4">Biletlerim</h2>

        <!-- Mesaj -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Kredi Bilgisi -->
        <div class="alert alert-info">
            <strong>Mevcut Krediniz:</strong> ₺<?php echo number_format($userCredit, 2); ?>
        </div>

        <!-- Bilet Listesi -->
        <?php if (empty($tickets)): ?>
            <div class="alert alert-warning">
                Henüz biletiniz yok. <a href="index.php">Sefer arayıp bilet satın alabilirsiniz.</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 <?php echo $ticket['status'] === 'cancelled' ? 'border-danger' : ''; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title"><?php echo htmlspecialchars($ticket['company_name']); ?></h5>
                                    <span class="badge <?php echo $ticket['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $ticket['status'] === 'active' ? 'Aktif' : 'İptal Edildi'; ?>
                                    </span>
                                </div>

                                <p class="mb-2">
                                    <strong>Güzergah:</strong><br>
                                    <?php echo htmlspecialchars($ticket['departure_point']); ?> 
                                    → 
                                    <?php echo htmlspecialchars($ticket['arrival_point']); ?>
                                </p>

                                <p class="mb-2">
                                    <strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($ticket['departure_date'])); ?><br>
                                    <strong>Saat:</strong> <?php echo htmlspecialchars($ticket['departure_time']); ?>
                                </p>

                                <p class="mb-2">
                                    <strong>Koltuk No:</strong> <?php echo $ticket['seat_number']; ?>
                                </p>

                                <p class="mb-3">
                                    <strong>Fiyat:</strong> <span class="text-success">₺<?php echo number_format($ticket['price'], 2); ?></span>
                                </p>

                                <?php if ($ticket['status'] === 'active'): ?>
                                    <div class="d-flex gap-2">
                                        <a href="cancel-ticket.php?ticket_id=<?php echo $ticket['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Bileti iptal etmek istediğinizden emin misiniz?')">
                                            İptal Et
                                        </a>
                                        <a href="download-pdf.php?ticket_id=<?php echo $ticket['id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            PDF İndir
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <small class="text-muted d-block mt-2">
                                    Satın Alma: <?php echo date('d.m.Y H:i', strtotime($ticket['purchase_date'])); ?>
                                </small>
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