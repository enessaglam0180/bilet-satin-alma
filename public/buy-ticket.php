<?php
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/auth.php';

requireLogin();

$routeId = $_GET['route_id'] ?? null;

if (!$routeId) {
    header("Location: index.php");
    exit;
}

// AJAX kupon doğrulama
if (isset($_POST['validate_coupon']) && isset($_POST['coupon_code'])) {
    header('Content-Type: application/json');
    
    $couponCode = trim($_POST['coupon_code']);
    $price = floatval($_POST['price']);
    
    if (empty($couponCode)) {
        echo json_encode(['success' => false, 'message' => 'Lütfen kupon kodu girin']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM coupons 
            WHERE code = ? 
            AND (company_id = ? OR company_id IS NULL OR is_global = 1)
            AND used_count < usage_limit
            AND expiry_date >= DATE('now')
        ");
        $stmt->execute([$couponCode, $_POST['company_id']]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($coupon) {
            $discount = $price * ($coupon['discount_rate'] / 100);
            $finalPrice = $price - $discount;
            
            echo json_encode([
                'success' => true,
                'message' => '%' . $coupon['discount_rate'] . ' indirim uygulandı!',
                'discount' => $discount,
                'finalPrice' => $finalPrice,
                'discountRate' => $coupon['discount_rate']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Geçersiz veya süresi dolmuş kupon!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Bir hata oluştu']);
    }
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

$message = '';
$messageType = '';

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['validate_coupon'])) {
    $seatNumber = $_POST['seat_number'] ?? null;
    $couponCode = trim($_POST['coupon_code'] ?? '');
    
    if (!$seatNumber) {
        $message = 'Lütfen koltuk seçin!';
        $messageType = 'danger';
    } elseif (in_array($seatNumber, $occupiedSeats)) {
        $message = 'Bu koltuk dolu! Lütfen başka koltuk seçin.';
        $messageType = 'danger';
    } else {
        $finalPrice = $route['price'];
        $discount = 0;
        $couponId = null;
        
        // Kupon kontrolü
        if (!empty($couponCode)) {
            $stmt = $pdo->prepare("
                SELECT * FROM coupons 
                WHERE code = ? 
                AND (company_id = ? OR company_id IS NULL OR is_global = 1)
                AND used_count < usage_limit
                AND expiry_date >= DATE('now')
            ");
            $stmt->execute([$couponCode, $route['company_id']]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($coupon) {
                $discount = $finalPrice * ($coupon['discount_rate'] / 100);
                $finalPrice = $finalPrice - $discount;
                $couponId = $coupon['id'];
            }
        }
        
        // Kredi kontrolü
        if ($finalPrice > $userCredit) {
            $message = 'Yetersiz kredi! Krediniz: ₺' . number_format($userCredit, 2);
            $messageType = 'danger';
        } else {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("
                    INSERT INTO tickets (user_id, route_id, seat_number, price, status)
                    VALUES (?, ?, ?, ?, 'active')
                ");
                $stmt->execute([$_SESSION['user_id'], $routeId, $seatNumber, $finalPrice]);
                
                $stmt = $pdo->prepare("UPDATE users SET virtual_credit = virtual_credit - ? WHERE id = ?");
                $stmt->execute([$finalPrice, $_SESSION['user_id']]);
                
                $stmt = $pdo->prepare("UPDATE routes SET available_seats = available_seats - 1 WHERE id = ?");
                $stmt->execute([$routeId]);
                
                if ($couponId) {
                    $stmt = $pdo->prepare("INSERT INTO coupon_usage (coupon_id, user_id) VALUES (?, ?)");
                    $stmt->execute([$couponId, $_SESSION['user_id']]);
                    
                    $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
                    $stmt->execute([$couponId]);
                }
                
                $pdo->commit();
                header("Location: my-tickets.php?success=1");
                exit;
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = 'İşlem sırasında hata oluştu: ' . $e->getMessage();
                $messageType = 'danger';
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
    <title>Bilet Satın Al - <?php echo htmlspecialchars($route['departure_point'] . ' → ' . $route['arrival_point']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --seat-empty: #ffffff;
            --seat-selected: #4F46E5;
            --seat-occupied: #EF4444;
            --seat-hover: #E0E7FF;
        }
        
        .bus-layout {
            max-width: 650px;
            margin: 0 auto;
            padding: 40px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
            position: relative;
        }
        
        .bus-windshield {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 8px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 0 0 20px 20px;
        }
        
        .driver-section {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 40px;
            text-align: center;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            position: relative;
        }
        
        .driver-section::before {
            content: '🚗';
            font-size: 2rem;
            display: block;
            margin-bottom: 8px;
        }
        
        .seats-container {
            background: rgba(255, 255, 255, 0.15);
            padding: 30px 20px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        
        .seat-row {
            display: grid;
            grid-template-columns: 1fr 1fr 0.6fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .aisle {
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }
        
        .seat {
            aspect-ratio: 1;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--seat-empty);
            position: relative;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .seat::before {
            content: '💺';
            position: absolute;
            top: 5px;
            font-size: 0.8rem;
            opacity: 0.6;
        }
        
        .seat:not(.occupied):hover {
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 12px 24px rgba(79, 70, 229, 0.4);
            border-color: var(--seat-selected);
            background: var(--seat-hover);
        }
        
        .seat.occupied {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: white;
            cursor: not-allowed;
            border-color: #DC2626;
            opacity: 0.8;
        }
        
        .seat.occupied::before {
            content: '🚫';
        }
        
        .seat input[type="radio"] {
            display: none;
        }
        
        .seat input[type="radio"]:checked + label {
            background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
            color: white;
            border-color: #4338CA;
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 12px 24px rgba(79, 70, 229, 0.5);
            animation: pulse 1.5s infinite;
        }
        
        .seat input[type="radio"]:checked + label::before {
            content: '✓';
            font-size: 1.2rem;
        }
        
        @keyframes pulse {
            0%, 100% { transform: translateY(-8px) scale(1.05); }
            50% { transform: translateY(-8px) scale(1.08); }
        }
        
        .seat label {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin: 0;
            border-radius: 12px;
        }
        
        .legend {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-top: 30px;
            flex-wrap: wrap;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            color: white;
            font-weight: 600;
        }
        
        .legend-box {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .legend-box.available {
            background: var(--seat-empty);
        }
        
        .legend-box.occupied {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            border-color: #DC2626;
        }
        
        .legend-box.selected {
            background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
            border-color: #4338CA;
        }
        
        .price-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .price-item:last-child {
            border-bottom: none;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 2px solid #4F46E5;
        }
        
        .price-label {
            font-weight: 600;
            color: #495057;
        }
        
        .price-value {
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .discount-value {
            color: #10B981;
        }
        
        .final-price {
            color: #4F46E5;
            font-size: 1.8rem;
        }
        
        .coupon-input-group {
            position: relative;
        }
        
        .coupon-status {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }
        
        .sticky-sidebar {
            position: sticky;
            top: 20px;
        }
        
        #couponMessage {
            margin-top: 10px;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Bilet Satın Alma</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Ana Sayfa</a></li>
                    <li class="nav-item"><a class="nav-link" href="my-tickets.php">Biletlerim</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profil</a></li>
                    <?php if (getUserRole() === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin_panel.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card sticky-sidebar shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Sefer Detayları</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="text-primary mb-3"><?php echo htmlspecialchars($route['company_name']); ?></h6>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Güzergah</small>
                            <p class="mb-0 fw-bold"><?php echo htmlspecialchars($route['departure_point']); ?> → <?php echo htmlspecialchars($route['arrival_point']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Tarih & Saat</small>
                            <p class="mb-0"><?php echo date('d.m.Y', strtotime($route['departure_date'])); ?> - <?php echo htmlspecialchars($route['departure_time']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Müsait Koltuk</small>
                            <span class="badge bg-success"><?php echo $route['available_seats']; ?> koltuk</span>
                        </div>
                        
                        <hr>
                        
                        <div class="price-summary">
                            <div class="price-item">
                                <span class="price-label">Bilet Fiyatı:</span>
                                <span class="price-value" id="basePrice">₺<?php echo number_format($route['price'], 2); ?></span>
                            </div>
                            <div class="price-item">
                                <span class="price-label discount-value">İndirim:</span>
                                <span class="price-value discount-value" id="discountAmount">₺0,00</span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">Ödenecek Tutar:</span>
                                <span class="price-value final-price" id="finalPrice">₺<?php echo number_format($route['price'], 2); ?></span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div>
                            <small class="text-muted d-block mb-1">Bakiyeniz</small>
                            <h5 class="text-success mb-0">₺<?php echo number_format($userCredit, 2); ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Koltuk Seçimi</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="purchaseForm">
                            <div class="bus-layout">
                                <div class="bus-windshield"></div>
                                
                                <div class="driver-section">
                                    ŞOFÖR
                                </div>
                                
                                <div class="seats-container">
                                    <?php 
                                    $totalSeats = $route['total_seats'];
                                    $seatsPerRow = 4;
                                    $rows = ceil($totalSeats / $seatsPerRow);
                                    
                                    for ($row = 0; $row < $rows; $row++): 
                                        echo '<div class="seat-row">';
                                        
                                        for ($col = 0; $col < 5; $col++): 
                                            if ($col == 2): 
                                                echo '<div class="aisle">│</div>';
                                                continue;
                                            endif;
                                            
                                            $seatNum = ($row * $seatsPerRow) + ($col > 2 ? $col - 1 : $col) + 1;
                                            
                                            if ($seatNum <= $totalSeats):
                                                $isOccupied = in_array($seatNum, $occupiedSeats);
                                                
                                                if ($isOccupied): ?>
                                                    <div class="seat occupied" title="Dolu Koltuk">
                                                        <?php echo $seatNum; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="seat">
                                                        <input type="radio" name="seat_number" value="<?php echo $seatNum; ?>" id="seat_<?php echo $seatNum; ?>">
                                                        <label for="seat_<?php echo $seatNum; ?>"><?php echo $seatNum; ?></label>
                                                    </div>
                                                <?php endif;
                                            else:
                                                echo '<div></div>';
                                            endif;
                                        endfor;
                                        
                                        echo '</div>';
                                    endfor;
                                    ?>
                                </div>
                                
                                <div class="legend">
                                    <div class="legend-item">
                                        <div class="legend-box available"></div>
                                        <span>Boş</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-box selected"></div>
                                        <span>Seçili</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-box occupied"></div>
                                        <span>Dolu</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="form-label fw-bold">İndirim Kuponu</label>
                                <div class="coupon-input-group">
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control" 
                                               id="couponCode" 
                                               name="coupon_code" 
                                               placeholder="Kupon kodunuzu girin">
                                        <button type="button" 
                                                class="btn btn-outline-primary" 
                                                id="applyCouponBtn"
                                                onclick="validateCoupon()">
                                            Uygula
                                        </button>
                                    </div>
                                    <div id="couponMessage"></div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" id="purchaseBtn">
                                    Satın Al
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">Vazgeç</a>
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
    <script>
        const basePrice = <?php echo $route['price']; ?>;
        let currentDiscount = 0;
        let currentFinalPrice = basePrice;
        let couponApplied = false;
        
        function validateCoupon() {
            const couponCode = document.getElementById('couponCode').value.trim();
            const messageDiv = document.getElementById('couponMessage');
            const applyBtn = document.getElementById('applyCouponBtn');
            
            if (!couponCode) {
                showCouponMessage('Lütfen kupon kodu girin', 'danger');
                return;
            }
            
            applyBtn.disabled = true;
            applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Kontrol ediliyor...';
            
            const formData = new FormData();
            formData.append('validate_coupon', '1');
            formData.append('coupon_code', couponCode);
            formData.append('price', basePrice);
            formData.append('company_id', <?php echo $route['company_id']; ?>);
            
            fetch('buy-ticket.php?route_id=<?php echo $routeId; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentDiscount = data.discount;
                    currentFinalPrice = data.finalPrice;
                    couponApplied = true;
                    
                    document.getElementById('discountAmount').textContent = '₺' + data.discount.toFixed(2);
                    document.getElementById('finalPrice').textContent = '₺' + data.finalPrice.toFixed(2);
                    
                    showCouponMessage(data.message, 'success');
                    
                    document.getElementById('couponCode').readOnly = true;
                    applyBtn.disabled = true;
                    applyBtn.innerHTML = '✓ Uygulandı';
                    applyBtn.classList.remove('btn-outline-primary');
                    applyBtn.classList.add('btn-success');
                } else {
                    showCouponMessage(data.message, 'danger');
                    applyBtn.disabled = false;
                    applyBtn.innerHTML = 'Uygula';
                }
            })
            .catch(error => {
                showCouponMessage('Bir hata oluştu', 'danger');
                applyBtn.disabled = false;
                applyBtn.innerHTML = 'Uygula';
            });
        }
        
        function showCouponMessage(message, type) {
            const messageDiv = document.getElementById('couponMessage');
            messageDiv.className = 'alert alert-' + type;
            messageDiv.textContent = message;
            messageDiv.style.display = 'block';
        }
        
        // Koltuk seçimi kontrolü
        document.getElementById('purchaseForm').addEventListener('submit', function(e) {
            const selectedSeat = document.querySelector('input[name="seat_number"]:checked');
            if (!selectedSeat) {
                e.preventDefault();
                alert('Lütfen bir koltuk seçin!');
            }
        });
        
        // Enter tuşu ile kupon uygulama
        document.getElementById('couponCode').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                validateCoupon();
            }
        });
    </script>
</body>
</html>
