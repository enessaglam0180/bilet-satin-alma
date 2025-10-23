<?php
// ÖNEMLİ UYARI: Bu PHP dosyasının "UTF-8" olarak kodlandığından emin ol!
// Eğer dosya ANSI veya başka bir formatta kayıtlıysa, "İyi yolculuklar" gibi
// koda gömülü Türkçe karakterler yine de soru işareti (?) olarak görünebilir.
// Kullandığın kod editöründen (VSCode, Notepad++ vb.) "UTF-8 olarak kaydet" seçeneğini kullan.

require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

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

// TCPDF ile PDF oluştur
try {
    // PDF oluştur (UTF-8 ayarı önemli)
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Döküman bilgileri
    $pdf->SetCreator('Bilet Satın Alma Platformu');
    $pdf->SetAuthor('Bilet Platformu');
    $pdf->SetTitle('Otobüs Bileti');
    
    // Header/Footer kaldır
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Margin ayarla
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    
    // Sayfa ekle
    $pdf->AddPage();
    
    // *** DÜZELTME 1: TÜRKÇE KARAKTER SORUNU İÇİN FONT DEĞİŞİKLİĞİ ***
    // 'helvetica' yerine 'dejavusans' kullanarak tüm Unicode (Türkçe) karakterlerin
    // düzgün görüntülenmesini sağlıyoruz.
    $pdf->SetFont('dejavusans', '', 12);
    
    // *** DÜZELTME 2: UI SORUNU İÇİN HTML'İN YENİDEN YAPILANDIRILMASI ***
    // TCPDF, DIV tabanlı CSS düzenlerini iyi yorumlayamaz.
    // En sağlam yöntem, tüm düzeni tablolar (<table>) kullanarak yapmaktır.
    // CSS'ler <style> etiketi yerine inline (style="") olarak eklendi.
    
    // Bilet durumuna göre renk belirle
    $status_color = ($ticket['status'] === 'active') ? '#008000' : '#D90000'; // Yeşil veya Kırmızı
    $status_text = ($ticket['status'] === 'active') ? 'AKTİF' : 'İPTAL EDİLDİ';

    $html = '
    <table border="0" cellpadding="10" cellspacing="0" style="width: 100%; font-family: dejavusans, sans-serif; color: #333;">
        
        <tr>
            <td style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px;">
                <span style="font-size: 28px; font-weight: bold;">🚌 E-BİLET</span>
                <br><br>
                <span style="font-size: 12px;">Bilet Satın Alma Platformu</span>
                <br><br>
                <span style="font-size: 16px; font-weight: bold; color: ' . $status_color . ';">
                    ' . $status_text . '
                </span>
            </td>
        </tr>
        
        <tr>
            <td style="font-size: 20px; text-align: center; padding: 15px; background-color: #f0f0f0; margin: 15px 0;">
                <strong>' . htmlspecialchars($ticket['departure_point']) . '</strong>
                &nbsp; → &nbsp;
                <strong>' . htmlspecialchars($ticket['arrival_point']) . '</strong>
            </td>
        </tr>
        
        <tr>
            <td style="padding-top: 15px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold; color: #666; width: 40%;">Firma:</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #333; width: 60%;">' . htmlspecialchars($ticket['company_name']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold; color: #666;">Yolcu Adı:</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #333;">' . htmlspecialchars($ticket['passenger_name']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold; color: #666;">Tarih:</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #333;">' . date('d.m.Y', strtotime($ticket['departure_date'])) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold; color: #666;">Kalkış Saati:</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #333;">' . htmlspecialchars($ticket['departure_time']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold; color: #666;">Koltuk No:</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #333; font-size: 20px; font-weight: bold;">' . $ticket['seat_number'] . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold; color: #666;">Bilet No:</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #333;">#' . str_pad($ticket['id'], 6, '0', STR_PAD_LEFT) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold; color: #666;">Satın Alma:</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #333;">' . date('d.m.Y H:i', strtotime($ticket['purchase_date'])) . '</td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr>
            <td style="background-color: #667eea; color: white; padding: 15px; text-align: center; margin-top: 15px;">
                <span style="font-weight: bold; font-size: 14px;">ÖDENEN TUTAR</span>
                <br>
                <span style="font-size: 24px; font-weight: bold;">₺' . number_format($ticket['price'], 2, ',', '.') . '</span>
            </td>
        </tr>
        
        <tr>
            <td style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #333; font-size: 11px; color: #555;">
                <p style="font-weight: bold; font-size: 13px; color: #000;">İyi yolculuklar dileriz!</p>
                <p>Bu bilet elektronik olarak oluşturulmuştur.</p>
                <p>© 2025 Bilet Satın Alma Platformu</p>
            </td>
        </tr>
        
    </table>
    ';
    
    // HTML'i PDF'e yaz
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // PDF'i indir
    $filename = 'bilet-' . $ticket['id'] . '-' . date('Y-m-d') . '.pdf';
    // 'D' -> Doğrudan indirme (Download)
    // 'I' -> Tarayıcıda gösterme (Inline)
    $pdf->Output($filename, 'D');
    
} catch (Exception $e) {
    die("PDF oluşturma hatası: " . $e->getMessage());
}
?>