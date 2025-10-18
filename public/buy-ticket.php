<?php
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/auth.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    header("Location: /login.php");
    exit;
}

$routeId = $_GET['route_id'] ?? null;

if (!$routeId) {
    header("Location: /index.php");
    exit;
}

// Sefer bilgilerini getir
try {
    $stmt = $pdo->prepare("
        SELECT 
            routes.*,
            companies.name AS company_name
        FROM routes
        INNER JOIN companies ON routes.company_id = companies.id
        WHERE routes.id = ?
    ");
    $stmt->execute([$routeId]);
    $route = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$route) {
        die("Sefer bulunamadı!");
    }
    
    // Dolu koltukları getir
    $stmt = $pdo->prepare("
        SELECT seat_number 
        FROM tickets 
        WHERE route_id = ? AND status = 'active'
    ");
    $stmt->execute([$routeId]);
    $occupiedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Kullanıcının kredisini getir
    $stmt = $pdo->prepare("SELECT virtual_credit FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userCredit = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// Mesaj değişkenleri
$message = '';
$messageType = '';

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seatNumber = $_POST['seat_number'] ?? null;
    $couponCode = trim($_POST['coupon_code'] ?? '');
    
    if (!$seatNumber) {
        $message = 'Lütfen koltuk seçin!';
        $messageType = 'danger';
    } else {
        // Koltuk dolu mu kontrol et
        if (in_array($seatNumber, $occupiedSeats)) {
            $message = 'Bu koltuk dolu! Lütfen başka koltuk seçin.';
            $messageType = 'danger';
        } else {
            // Fiyat hesapla
            $finalPrice = $route['price'];
            $discount = 0;
            $couponId = null;
            
            // Kupon var mı?
            if (!empty($couponCode)) {
                $stmt = $pdo->prepare("
                    SELECT * FROM coupons 
                    WHERE code = ? 
                    AND (company_id = ? OR company_id IS NULL)
                    AND used_count < usage_limit
                    AND expiry_date >= DATE('now')
                ");
                $stmt->execute([$couponCode, $route['company_id']]);
                $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($coupon) {
                    $discount = $finalPrice * ($coupon['discount_rate'] / 100);
                    $finalPrice = $finalPrice - $discount;
                    $couponId = $coupon['id'];
                } else {
                    $message = 'Geçersiz veya süresi dolmuş kupon!';
                    $messageType = 'warning';
                }
            }
            
            // Kredi yeterli mi?
            if ($finalPrice > $userCredit) {
                $message = 'Yetersiz kredi! Krediniz: ₺' . number_format($userCredit, 2);
                $messageType = 'danger';
            } else {
                // Bilet satın alma işlemi (Transaction)
                try {
                    $pdo->beginTransaction();
                    
                    // 1. Bilet ekle
                    $stmt = $pdo->prepare("
                        INSERT INTO tickets (user_id, route_id, seat_number, price, status)
                        VALUES (?, ?, ?, ?, 'active')
                    ");
                    $stmt->execute([$_SESSION['user_id'], $routeId, $seatNumber, $finalPrice]);
                    
                    // 2. Kullanıcı kredisini düş
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET virtual_credit = virtual_credit - ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$finalPrice, $_SESSION['user_id']]);
                    
                    // 3. Seferdeki müsait koltuk sayısını azalt
                    $stmt = $pdo->prepare("
                        UPDATE routes 
                        SET available_seats = available_seats - 1 
                        WHERE id = ?
                    ");
                    $stmt->execute([$routeId]);
                    
                    // 4. Kupon kullanıldıysa kaydet
                    if ($couponId) {
                        $stmt = $pdo->prepare("
                            INSERT INTO coupon_usage (coupon_id, user_id)
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$couponId, $_SESSION['user_id']]);
                        
                        $stmt = $pdo->prepare("
                            UPDATE coupons 
                            SET used_count = used_count + 1 
                            WHERE id = ?
                        ");
                        $stmt->execute([$couponId]);
                    }
                    
                    $pdo->commit();
                    
                    // Başarılı - biletlerim sayfasına yönlendir
                    header("Location: /my-tickets.php?success=1");
                    exit;
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $message = 'İşlem sırasında hata oluştu: ' . $e->getMessage();
                    $messageType = 'danger';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satın Al</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Koltuk stilleri */
        .seat-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .seat {
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }
        
        .seat:hover:not(.occupied) {
            border-color: #667eea;
            background-color: #e7eaf6;
            transform: scale(1.05);
        }
        
        .seat.occupied {
            background-color: #ff6b6b;
            color: white;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .seat input[type="radio"] {
            display: none;
        }
        
        .seat input[type="radio"]:checked + label {
            border-color: #667eea;
            background-color: #667eea;
            color: white;
            font-weight: bold;
        }
    </style>
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
            <!-- Sol: Sefer Bilgileri -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Sefer Bilgileri</h5>
                        <hr>
                        <p><strong>Firma:</strong> <?php echo htmlspecialchars($route['company_name']); ?></p>
                        <p><strong>Güzergah:</strong><br>
                            <?php echo htmlspecialchars($route['departure_point']); ?> 
                            → 
                            <?php echo htmlspecialchars($route['arrival_point']); ?>
                        </p>
                        <p><strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($route['departure_date'])); ?></p>
                        <p><strong>Saat:</strong> <?php echo htmlspecialchars($route['departure_time']); ?></p>
                        <p><strong>Fiyat:</strong> <span class="text-success h4">₺<?php echo number_format($route['price'], 2); ?></span></p>
                        <p><strong>Müsait Koltuk:</strong> <?php echo $route['available_seats']; ?></p>
                        <hr>
                        <p><strong>Krediniz:</strong> <span class="text-primary">₺<?php echo number_format($userCredit, 2); ?></span></p>
                    </div>
                </div>
            </div>

            <!-- Sağ: Koltuk Seçimi -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Koltuk Seçimi</h5>

                        <!-- Mesaj -->
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Form -->
                        <form method="POST" id="purchaseForm">
                            <!-- Koltuk Grid -->
                            <div class="mb-4">
                                <div class="seat-grid">
                                    <?php for ($i = 1; $i <= $route['total_seats']; $i++): ?>
                                        <?php $isOccupied = in_array($i, $occupiedSeats); ?>
                                        
                                        <?php if ($isOccupied): ?>
                                            <!-- Dolu Koltuk -->
                                            <div class="seat occupied" title="Dolu">
                                                <?php echo $i; ?>
                                            </div>
                                        <?php else: ?>
                                            <!-- Boş Koltuk -->
                                            <div class="seat">
                                                <input 
                                                    type="radio" 
                                                    name="seat_number" 
                                                    value="<?php echo $i; ?>" 
                                                    id="seat_<?php echo $i; ?>"
                                                    onchange="updatePrice()"
                                                >
                                                <label for="seat_<?php echo $i; ?>" style="cursor: pointer; display: block;">
                                                    <?php echo $i; ?>
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>

                                <!-- Açıklama -->
                                <div class="mt-3 text-center">
                                    <span class="badge bg-secondary">Boş</span>
                                    <span class="badge bg-danger">Dolu</span>
                                    <span class="badge bg-primary">Seçili</span>
                                </div>
                            </div>

                            <!-- Kupon Kodu -->
                            <div class="mb-3">
                                <label for="coupon_code" class="form-label">İndirim Kuponu (Opsiyonel)</label>
                                <div class="input-group">
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="coupon_code" 
                                        name="coupon_code"
                                        placeholder="Kupon kodunuz varsa girin"
                                    >
                                    <button type="button" class="btn btn-outline-secondary" onclick="applyCoupon()">
                                        Uygula
                                    </button>
                                </div>
                                <small class="text-muted">İndirim kuponu varsa fiyat otomatik hesaplanacak</small>
                            </div>

                            <!-- Fiyat Özeti -->
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6>Fiyat Özeti</h6>
                                    <div class="d-flex justify-content-between">
                                        <span>Bilet Fiyatı:</span>
                                        <strong id="basePrice">₺<?php echo number_format($route['price'], 2); ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between text-success">
                                        <span>İndirim:</span>
                                        <strong id="discount">₺0,00</strong>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Ödenecek Tutar:</strong>
                                        <strong class="text-primary h5" id="finalPrice">₺<?php echo number_format($route['price'], 2); ?></strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Satın Al Butonu -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    🎫 Bilet Satın Al
                                </button>
                            </div>
                        </form>
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
    <script>
        // Koltuk seçimi ve fiyat güncellemesi
        function updatePrice() {
            const selectedSeat = document.querySelector('input[name="seat_number"]:checked');
            if (selectedSeat) {
                // Koltuk seçildi - burada kupon kontrolü yapılabilir
                console.log('Seçilen koltuk: ' + selectedSeat.value);
            }
        }

        // Kupon uygulama (basit versiyon - sunucu tarafında kontrol edilir)
        function applyCoupon() {
            const couponCode = document.getElementById('coupon_code').value;
            if (couponCode) {
                alert('Kupon kodu: ' + couponCode + '\nSatın alma işleminde kontrol edilecektir.');
            } else {
                alert('Lütfen kupon kodu girin!');
            }
        }
    </script>
</body>
</html>