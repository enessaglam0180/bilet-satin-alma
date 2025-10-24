<?php
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/auth.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$ticketId = $_GET['ticket_id'] ?? null;

if (!$ticketId) {
    header("Location: my-tickets.php");
    exit;
}

// Bilet bilgilerini getir
try {
    $stmt = $pdo->prepare("
        SELECT 
            tickets.*,
            routes.departure_point,
            routes.arrival_point,
            routes.departure_date,
            routes.departure_time,
            companies.name AS company_name,
            users.full_name AS passenger_name
        FROM tickets
        INNER JOIN routes ON tickets.route_id = routes.id
        INNER JOIN companies ON routes.company_id = companies.id
        INNER JOIN users ON tickets.user_id = users.id
        WHERE tickets.id = ? AND tickets.user_id = ?
    ");
    $stmt->execute([$ticketId, $_SESSION['user_id']]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        die("Bilet bulunamadı!");
    }
    
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// Basit HTML/CSS ile PDF benzeri çıktı
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Bilet #<?php echo $ticket['id']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .ticket {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .ticket-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .ticket-header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        .ticket-status {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 15px;
            <?php if($ticket['status'] === 'active'): ?>
                background: #10B981;
            <?php else: ?>
                background: #EF4444;
            <?php endif; ?>
        }
        .ticket-route {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        .ticket-route .arrow {
            color: #667eea;
            margin: 0 20px;
        }
        .ticket-body {
            padding: 40px;
        }
        .info-row {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            flex: 0 0 200px;
            font-weight: bold;
            color: #666;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
        .seat-number {
            font-size: 32px;
            color: #667eea;
            font-weight: bold;
        }
        .ticket-footer {
            background: #667eea;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .price {
            font-size: 40px;
            font-weight: bold;
            margin: 10px 0;
        }
        .footer-text {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.3);
            font-size: 14px;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .print-btn:hover {
            background: #5568d3;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .print-btn {
                display: none;
            }
            .ticket {
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Yazdır / PDF Kaydet</button>
    
    <div class="ticket">
        <div class="ticket-header">
            <h1>🚌 E-BİLET</h1>
            <p>Bilet Satın Alma Platformu</p>
            <div class="ticket-status">
                <?php echo $ticket['status'] === 'active' ? '✓ AKTİF' : '✗ İPTAL EDİLDİ'; ?>
            </div>
        </div>
        
        <div class="ticket-route">
            <?php echo htmlspecialchars($ticket['departure_point']); ?>
            <span class="arrow">→</span>
            <?php echo htmlspecialchars($ticket['arrival_point']); ?>
        </div>
        
        <div class="ticket-body">
            <div class="info-row">
                <div class="info-label">Firma:</div>
                <div class="info-value"><?php echo htmlspecialchars($ticket['company_name']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Yolcu Adı:</div>
                <div class="info-value"><?php echo htmlspecialchars($ticket['passenger_name']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Tarih:</div>
                <div class="info-value"><?php echo date('d.m.Y', strtotime($ticket['departure_date'])); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Kalkış Saati:</div>
                <div class="info-value"><?php echo htmlspecialchars($ticket['departure_time']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Koltuk No:</div>
                <div class="info-value seat-number"><?php echo $ticket['seat_number']; ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Bilet No:</div>
                <div class="info-value">#<?php echo str_pad($ticket['id'], 6, '0', STR_PAD_LEFT); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Satın Alma:</div>
                <div class="info-value"><?php echo date('d.m.Y H:i', strtotime($ticket['purchase_date'])); ?></div>
            </div>
        </div>
        
        <div class="ticket-footer">
            <div style="font-size: 18px; margin-bottom: 10px;">ÖDENEN TUTAR</div>
            <div class="price">₺<?php echo number_format($ticket['price'], 2, ',', '.'); ?></div>
            
            <div class="footer-text">
                <p style="font-size: 18px; margin-bottom: 15px;"><strong>İyi yolculuklar dileriz!</strong></p>
                <p>Bu bilet elektronik olarak oluşturulmuştur.</p>
                <p>&copy; 2025 Bilet Satın Alma Platformu</p>
            </div>
        </div>
    </div>
</body>
</html>
