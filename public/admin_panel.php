<?php
// Gerekli dosyaları ve session'ı başlat
require_once '../src/database.php';
require_once '../src/auth.php';

// --- 1. YETKİ KONTROLÜ ---
// Bu sayfayı sadece 'admin' rolündeki kullanıcılar görebilir 
// auth.php'de require_role('admin') fonksiyonunuz olduğunu varsayıyoruz.
// Eğer yoksa, aşağıdaki manuel kontrolü kullanın:
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: access-denied.php"); // Yetkisiz erişim sayfası
    exit;
}

// --- 2. İŞLEM YÖNETİMİ (CRUD) ---

$db = getDB(); // src/database.php'den veritabanı bağlantısını al
$message = ''; // Kullanıcıya gösterilecek mesaj
$error = '';   // Hata mesajı

// Hangi sekmede olduğumuzu belirleyelim (Firma, Kullanıcı, Kupon)
$view = $_GET['view'] ?? 'companies'; // Varsayılan: Firma Yönetimi
// Hangi eylemi yapıyoruz (Ekle, Düzenle, Sil)
$action = $_GET['action'] ?? 'list'; // Varsayılan: Listele

try {
    // --- A. FİRMA YÖNETİMİ İŞLEMLERİ  ---
    if ($view === 'companies') {
        // YENİ FİRMA OLUŞTURMA
        if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $db->prepare("INSERT INTO Companies (name) VALUES (:name)");
            $stmt->execute([':name' => $_POST['name']]);
            $message = "Yeni firma başarıyla oluşturuldu.";
            $action = 'list'; // Listeye geri dön
        }
        // FİRMA GÜNCELLEME
        elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $db->prepare("UPDATE Companies SET name = :name WHERE id = :id");
            $stmt->execute([':name' => $_POST['name'], ':id' => $_POST['id']]);
            $message = "Firma başarıyla güncellendi.";
            $action = 'list';
        }
        // FİRMA SİLME
        elseif ($action === 'delete' && isset($_GET['id'])) {
            // Not: İlişkili kullanıcıları ve seferleri silmek/boşa çıkarmak gerekebilir.
            // Bu basit örnekte sadece firmayı siliyoruz.
            $stmt = $db->prepare("DELETE FROM Companies WHERE id = :id");
            $stmt->execute([':id' => $_GET['id']]);
            $message = "Firma silindi.";
            $action = 'list';
        }
    }

    // --- B. FİRMA ADMİN YÖNETİMİ İŞLEMLERİ  ---
    elseif ($view === 'users') {
        // YENİ FİRMA ADMİN OLUŞTURMA
        if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO Users (fullname, email, password, role, company_id, balance) 
                VALUES (:fullname, :email, :password, 'firma_admin', :company_id, 0)
            ");
            $stmt->execute([
                ':fullname' => $_POST['fullname'],
                ':email' => $_POST['email'],
                ':password' => $hashed_password,
                ':company_id' => $_POST['company_id'] // Firmaya atama 
            ]);
            $message = "Yeni Firma Admin kullanıcısı başarıyla oluşturuldu.";
            $action = 'list';
        }
        // FİRMA ADMİN GÜNCELLEME
        elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Şifre güncelleniyor mu?
            if (!empty($_POST['password'])) {
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    UPDATE Users 
                    SET fullname = :fullname, email = :email, password = :password, company_id = :company_id 
                    WHERE id = :id AND role = 'firma_admin'
                ");
                $stmt->execute([
                    ':fullname' => $_POST['fullname'],
                    ':email' => $_POST['email'],
                    ':password' => $hashed_password,
                    ':company_id' => $_POST['company_id'],
                    ':id' => $_POST['id']
                ]);
            } else {
                // Şifre güncellenmiyor
                $stmt = $db->prepare("
                    UPDATE Users 
                    SET fullname = :fullname, email = :email, company_id = :company_id 
                    WHERE id = :id AND role = 'firma_admin'
                ");
                $stmt->execute([
                    ':fullname' => $_POST['fullname'],
                    ':email' => $_POST['email'],
                    ':company_id' => $_POST['company_id'],
                    ':id' => $_POST['id']
                ]);
            }
            $message = "Firma Admin kullanıcısı güncellendi.";
            $action = 'list';
        }
        // FİRMA ADMİN SİLME
        elseif ($action === 'delete' && isset($_GET['id'])) {
            $stmt = $db->prepare("DELETE FROM Users WHERE id = :id AND role = 'firma_admin'");
            $stmt->execute([':id' => $_GET['id']]);
            $message = "Firma Admin kullanıcısı silindi.";
            $action = 'list';
        }
    }

    // --- C. GLOBAL KUPON YÖNETİMİ İŞLEMLERİ  ---
    elseif ($view === 'coupons') {
        // YENİ GLOBAL KUPON OLUŞTURMA
        if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $db->prepare("
                INSERT INTO Coupons (code, discount_rate, usage_limit, expires_at, company_id) 
                VALUES (:code, :rate, :limit, :expires, NULL)
            "); // company_id = NULL (Tüm firmalarda geçerli) 
            $stmt->execute([
                ':code' => $_POST['code'],
                ':rate' => $_POST['discount_rate'],
                ':limit' => $_POST['usage_limit'],
                ':expires' => $_POST['expires_at']
            ]);
            $message = "Yeni global kupon başarıyla oluşturuldu.";
            $action = 'list';
        }
        // KUPON GÜNCELLEME
        elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $db->prepare("
                UPDATE Coupons 
                SET code = :code, discount_rate = :rate, usage_limit = :limit, expires_at = :expires 
                WHERE id = :id AND company_id IS NULL
            ");
            $stmt->execute([
                ':code' => $_POST['code'],
                ':rate' => $_POST['discount_rate'],
                ':limit' => $_POST['usage_limit'],
                ':expires' => $_POST['expires_at'],
                ':id' => $_POST['id']
            ]);
            $message = "Global kupon güncellendi.";
            $action = 'list';
        }
        // KUPON SİLME
        elseif ($action === 'delete' && isset($_GET['id'])) {
            $stmt = $db->prepare("DELETE FROM Coupons WHERE id = :id AND company_id IS NULL");
            $stmt->execute([':id' => $_GET['id']]);
            $message = "Global kupon silindi.";
            $action = 'list';
        }
    }
} catch (PDOException $e) {
    // Özellikle 'UNIQUE constraint failed' hatalarını yakala
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
        if ($view === 'companies') $error = "Hata: Bu isimde bir firma zaten mevcut.";
        elseif ($view === 'users') $error = "Hata: Bu e-posta adresine sahip bir kullanıcı zaten mevcut.";
        elseif ($view === 'coupons') $error = "Hata: Bu kupon kodu zaten kullanılıyor.";
        else $error = "Veritabanı hatası: Kayıt zaten mevcut.";
    } else {
        $error = "Bir veritabanı hatası oluştu: " . $e->getMessage();
    }
    $action = 'list'; // Hata durumunda listeye dön
}


// --- 3. VERİ ÇEKME (GÖRÜNÜM İÇİN) ---

// Görüntüleme için gerekli verileri çek
$companies = [];
$users = [];
$coupons = [];

// Her zaman firma listesini çek (Kullanıcı eklerken/düzenlerken lazım)
$companies = $db->query("SELECT * FROM Companies ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($view === 'users') {
    // Firma Adminlerini çekerken, firmanın adını da (JOIN) alalım
    $users = $db->query("
        SELECT Users.*, Companies.name AS company_name 
        FROM Users 
        LEFT JOIN Companies ON Users.company_id = Companies.id
        WHERE Users.role = 'firma_admin'
        ORDER BY Users.fullname ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($view === 'coupons') {
    // Sadece global kuponları (company_id = NULL) listele 
    $coupons = $db->query("SELECT * FROM Coupons WHERE company_id IS NULL ORDER BY expires_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"> </head>
<body>
    <?php include 'layout/header.php'; // Header'ınızı include edin ?>

    <div class="container my-5">
        <h1 class="mb-4 text-center">Admin Paneli</h1>
        [cite_start]<p class="text-center text-muted">Sistemdeki en yetkili roldür. [cite: 31]</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <ul class="nav nav-tabs nav-fill mb-4">
            <li class="nav-item">
                [cite_start]<a class="nav-link <?php if ($view === 'companies') echo 'active'; ?>" href="?view=companies">Firma Yönetimi [cite: 32]</a>
            </li>
            <li class="nav-item">
                [cite_start]<a class="nav-link <?php if ($view === 'users') echo 'active'; ?>" href="?view=users">Firma Admin Yönetimi [cite: 32]</a>
            </li>
            <li class="nav-item">
                [cite_start]<a class="nav-link <?php if ($view === 'coupons') echo 'active'; ?>" href="?view=coupons">Global Kupon Yönetimi [cite: 33]</a>
            </li>
        </ul>

        <div class="tab-content">

            <div class="tab-pane fade <?php if ($view === 'companies') echo 'show active'; ?>">
                <?php if ($action === 'list'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Otobüs Firmaları</h2>
                        <a href="?view=companies&action=new" class="btn btn-success">Yeni Firma Ekle</a>
                    </div>
                    <table class="table table-striped table-hover shadow-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Firma Adı</th>
                                <th style="width: 150px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $company): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($company['name']); ?></td>
                                <td>
                                    <a href="?view=companies&action=edit&id=<?php echo $company['id']; ?>" class="btn btn-sm btn-primary">Düzenle</a>
                                    <a href="?view=companies&action=delete&id=<?php echo $company['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz?');">Sil</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ($action === 'new' || $action === 'edit'): 
                    $isEdit = $action === 'edit';
                    $company = null;
                    if ($isEdit) {
                        $stmt = $db->prepare("SELECT * FROM Companies WHERE id = :id");
                        $stmt->execute([':id' => $_GET['id']]);
                        $company = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                ?>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h3 class="card-title"><?php echo $isEdit ? 'Firmayı Düzenle' : 'Yeni Firma Oluştur'; ?></h3>
                            <form method="POST" action="?view=companies&action=<?php echo $isEdit ? 'edit' : 'create'; ?>">
                                <?php if ($isEdit): ?>
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($company['id']); ?>">
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label for="name" class="form-label">Firma Adı</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($company['name'] ?? ''); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Güncelle' : 'Oluştur'; ?></button>
                                <a href="?view=companies" class="btn btn-secondary">İptal</a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade <?php if ($view === 'users') echo 'show active'; ?>">
                <?php if ($action === 'list'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Firma Admin Kullanıcıları</h2>
                        <a href="?view=users&action=new" class="btn btn-success">Yeni Firma Admin Ekle</a>
                    </div>
                    <table class="table table-striped table-hover shadow-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Ad Soyad</th>
                                <th>E-posta</th>
                                [cite_start]<th>Atandığı Firma [cite: 32]</th>
                                <th style="width: 150px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['company_name'] ?? '<i>Atanmamış</i>'); ?></td>
                                <td>
                                    <a href="?view=users&action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Düzenle</a>
                                    <a href="?view=users&action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">Sil</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ($action === 'new' || $action === 'edit'): 
                    $isEdit = $action === 'edit';
                    $user = null;
                    if ($isEdit) {
                        $stmt = $db->prepare("SELECT * FROM Users WHERE id = :id AND role = 'firma_admin'");
                        $stmt->execute([':id' => $_GET['id']]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                ?>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h3 class="card-title"><?php echo $isEdit ? 'Firma Admin Düzenle' : 'Yeni Firma Admin Oluştur'; ?></h3>
                            <form method="POST" action="?view=users&action=<?php echo $isEdit ? 'edit' : 'create'; ?>">
                                <?php if ($isEdit): ?>
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label for="fullname" class="form-label">Ad Soyad</label>
                                    <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">E-posta</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Şifre</label>
                                    <input type="password" class="form-control" id="password" name="password" <?php if (!$isEdit) echo 'required'; ?>>
                                    <?php if ($isEdit): ?><small class="form-text text-muted">Değiştirmek istemiyorsanız boş bırakın.</small><?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    [cite_start]<label for="company_id" class="form-label">Ata / Değiştir (Firma) [cite: 32]</label>
                                    <select class="form-select" id="company_id" name="company_id" required>
                                        <option value="">Lütfen bir firma seçin...</option>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?php echo $company['id']; ?>" <?php echo (isset($user['company_id']) && $user['company_id'] === $company['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($company['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Güncelle' : 'Oluştur'; ?></button>
                                <a href="?view=users" class="btn btn-secondary">İptal</a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade <?php if ($view === 'coupons') echo 'show active'; ?>">
                <?php if ($action === 'list'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        [cite_start]<h2>Global İndirim Kuponları (Tüm Firmalar) [cite: 33]</h2>
                        <a href="?view=coupons&action=new" class="btn btn-success">Yeni Global Kupon Ekle</a>
                    </div>
                    <table class="table table-striped table-hover shadow-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Kupon Kodu</th>
                                <th>İndirim Oranı (%)</th>
                                <th>Kullanım Limiti</th>
                                <th>Son Kullanma Tarihi</th>
                                <th style="width: 150px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($coupon['code']); ?></td>
                                <td><?php echo htmlspecialchars($coupon['discount_rate']); ?>%</td>
                                <td><?php echo htmlspecialchars($coupon['usage_limit']); ?></td>
                                <td><?php echo htmlspecialchars($coupon['expires_at']); ?></td>
                                <td>
                                    <a href="?view=coupons&action=edit&id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-primary">Düzenle</a>
                                    <a href="?view=coupons&action=delete&id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kuponu silmek istediğinizden emin misiniz?');">Sil</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ($action === 'new' || $action === 'edit'): 
                    $isEdit = $action === 'edit';
                    $coupon = null;
                    if ($isEdit) {
                        $stmt = $db->prepare("SELECT * FROM Coupons WHERE id = :id AND company_id IS NULL");
                        $stmt->execute([':id' => $_GET['id']]);
                        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                ?>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h3 class="card-title"><?php echo $isEdit ? 'Global Kuponu Düzenle' : 'Yeni Global Kupon Oluştur'; ?></h3>
                            <form method="POST" action="?view=coupons&action=<?php echo $isEdit ? 'edit' : 'create'; ?>">
                                <?php if ($isEdit): ?>
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($coupon['id']); ?>">
                                <?php endif; ?>
                                <div class="mb-3">
                                    [cite_start]<label for="code" class="form-label">Kupon Kodu [cite: 33]</label>
                                    <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($coupon['code'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    [cite_start]<label for="discount_rate" class="form-label">İndirim Oranı (%) [cite: 33]</label>
                                    <input type="number" step="0.1" class="form-control" id="discount_rate" name="discount_rate" value="<?php echo htmlspecialchars($coupon['discount_rate'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    [cite_start]<label for="usage_limit" class="form-label">Kullanım Limiti [cite: 33]</label>
                                    <input type="number" class="form-control" id="usage_limit" name="usage_limit" value="<?php echo htmlspecialchars($coupon['usage_limit'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    [cite_start]<label for="expires_at" class="form-label">Son Kullanma Tarihi [cite: 33]</label>
                                    <input type="date" class="form-control" id="expires_at" name="expires_at" value="<?php echo htmlspecialchars(substr($coupon['expires_at'] ?? '', 0, 10)); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Güncelle' : 'Oluştur'; ?></button>
                                <a href="?view=coupons" class="btn btn-secondary">İptal</a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
        </div> </div> <?php include 'layout/footer.php'; // Footer'ınızı include edin ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>