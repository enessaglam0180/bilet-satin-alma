<?php
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/auth.php';

// Admin yetkisi kontrolü
if (!isLoggedIn() || getUserRole() !== 'admin') {
    header("Location: access-denied.php");
    exit;
}

$message = '';
$error = '';
$view = $_GET['view'] ?? 'companies';
$action = $_GET['action'] ?? 'list';

try {
    // FİRMA YÖNETİMİ İŞLEMLERİ
    if ($view === 'companies') {
        if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $pdo->prepare("INSERT INTO companies (name) VALUES (?)");
            $stmt->execute([$_POST['name']]);
            $message = "Firma başarıyla oluşturuldu.";
            $action = 'list';
        } elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $pdo->prepare("UPDATE companies SET name = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['id']]);
            $message = "Firma güncellendi.";
            $action = 'list';
        } elseif ($action === 'delete' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $message = "Firma silindi.";
            $action = 'list';
        }
    }
    
    // KULLANICI YÖNETİMİ İŞLEMLERİ
    elseif ($view === 'users') {
        if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['username'], $_POST['email'], $hashedPassword, $_POST['full_name'], $_POST['role']]);
            $message = "Kullanıcı başarıyla oluşturuldu.";
            $action = 'list';
        } elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST['password'])) {
                $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, full_name = ?, role = ? WHERE id = ?");
                $stmt->execute([$_POST['username'], $_POST['email'], $hashedPassword, $_POST['full_name'], $_POST['role'], $_POST['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, role = ? WHERE id = ?");
                $stmt->execute([$_POST['username'], $_POST['email'], $_POST['full_name'], $_POST['role'], $_POST['id']]);
            }
            $message = "Kullanıcı güncellendi.";
            $action = 'list';
        } elseif ($action === 'delete' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $message = "Kullanıcı silindi.";
            $action = 'list';
        }
    }
    
    // KUPON YÖNETİMİ İŞLEMLERİ
    elseif ($view === 'coupons') {
        if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_rate, usage_limit, expiry_date, is_global) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$_POST['code'], $_POST['discount_rate'], $_POST['usage_limit'], $_POST['expiry_date']]);
            $message = "Kupon başarıyla oluşturuldu.";
            $action = 'list';
        } elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $pdo->prepare("UPDATE coupons SET code = ?, discount_rate = ?, usage_limit = ?, expiry_date = ? WHERE id = ?");
            $stmt->execute([$_POST['code'], $_POST['discount_rate'], $_POST['usage_limit'], $_POST['expiry_date'], $_POST['id']]);
            $message = "Kupon güncellendi.";
            $action = 'list';
        } elseif ($action === 'delete' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $message = "Kupon silindi.";
            $action = 'list';
        }
    }
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
        $error = "Bu kayıt zaten mevcut!";
    } else {
        $error = "Veritabanı hatası: " . $e->getMessage();
    }
    $action = 'list';
}

// Veri çekme
$companies = $pdo->query("SELECT * FROM companies ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT * FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
$coupons = $pdo->query("SELECT * FROM coupons WHERE is_global = 1 ORDER BY expiry_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Bilet Satın Alma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stats-card {
            border-left: 4px solid #667eea;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .nav-tabs .nav-link {
            color: #666;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #667eea;
            border-bottom: 3px solid #667eea;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">🚌 Bilet Satın Alma</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_panel.php">Admin Panel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <h1 class="mb-2">Admin Paneli</h1>
            <p class="mb-0">Sistem yönetimi ve kontrol merkezi</p>
        </div>
    </div>

    <!-- Ana İçerik -->
    <main class="container mb-5">
        <!-- İstatistik Kartları -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title text-muted">Toplam Firma</h5>
                        <h2 class="mb-0"><?php echo count($companies); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title text-muted">Toplam Kullanıcı</h5>
                        <h2 class="mb-0"><?php echo count($users); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title text-muted">Aktif Kupon</h5>
                        <h2 class="mb-0"><?php echo count($coupons); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mesajlar -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Sekmeler -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php if ($view === 'companies') echo 'active'; ?>" href="?view=companies">Firmalar</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if ($view === 'users') echo 'active'; ?>" href="?view=users">Kullanıcılar</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if ($view === 'coupons') echo 'active'; ?>" href="?view=coupons">Kuponlar</a>
            </li>
        </ul>

        <!-- İçerik -->
        <div class="tab-content">
            <!-- FİRMA YÖNETİMİ -->
            <?php if ($view === 'companies'): ?>
                <?php if ($action === 'list'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Firma Yönetimi</h3>
                        <a href="?view=companies&action=new" class="btn btn-primary">+ Yeni Firma</a>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Firma Adı</th>
                                        <th style="width: 150px;">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td><?php echo $company['id']; ?></td>
                                        <td><?php echo htmlspecialchars($company['name']); ?></td>
                                        <td>
                                            <a href="?view=companies&action=edit&id=<?php echo $company['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                                            <a href="?view=companies&action=delete&id=<?php echo $company['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinizden emin misiniz?');">Sil</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                <?php elseif ($action === 'new' || $action === 'edit'): 
                    $isEdit = $action === 'edit';
                    $company = null;
                    if ($isEdit) {
                        $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
                        $stmt->execute([$_GET['id']]);
                        $company = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                ?>
                    <div class="card">
                        <div class="card-body">
                            <h4><?php echo $isEdit ? 'Firma Düzenle' : 'Yeni Firma Ekle'; ?></h4>
                            <form method="POST" action="?view=companies&action=<?php echo $isEdit ? 'edit' : 'create'; ?>">
                                <?php if ($isEdit): ?>
                                    <input type="hidden" name="id" value="<?php echo $company['id']; ?>">
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="form-label">Firma Adı</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($company['name'] ?? ''); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Güncelle' : 'Oluştur'; ?></button>
                                <a href="?view=companies" class="btn btn-secondary">İptal</a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- KULLANICI YÖNETİMİ -->
            <?php if ($view === 'users'): ?>
                <?php if ($action === 'list'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Kullanıcı Yönetimi</h3>
                        <a href="?view=users&action=new" class="btn btn-primary">+ Yeni Kullanıcı</a>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Kullanıcı Adı</th>
                                        <th>E-posta</th>
                                        <th>Ad Soyad</th>
                                        <th>Rol</th>
                                        <th>Bakiye</th>
                                        <th style="width: 150px;">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>"><?php echo $user['role']; ?></span></td>
                                        <td><?php echo number_format($user['virtual_credit'], 2); ?> ₺</td>
                                        <td>
                                            <a href="?view=users&action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                                            <a href="?view=users&action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinizden emin misiniz?');">Sil</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                <?php elseif ($action === 'new' || $action === 'edit'): 
                    $isEdit = $action === 'edit';
                    $user = null;
                    if ($isEdit) {
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$_GET['id']]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                ?>
                    <div class="card">
                        <div class="card-body">
                            <h4><?php echo $isEdit ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı Ekle'; ?></h4>
                            <form method="POST" action="?view=users&action=<?php echo $isEdit ? 'edit' : 'create'; ?>">
                                <?php if ($isEdit): ?>
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="form-label">Kullanıcı Adı</label>
                                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">E-posta</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Ad Soyad</label>
                                    <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Şifre <?php if ($isEdit) echo '(Değiştirmek istemiyorsanız boş bırakın)'; ?></label>
                                    <input type="password" class="form-control" name="password" <?php if (!$isEdit) echo 'required'; ?>>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Rol</label>
                                    <select class="form-select" name="role" required>
                                        <option value="user" <?php echo (isset($user['role']) && $user['role'] === 'user') ? 'selected' : ''; ?>>Kullanıcı</option>
                                        <option value="admin" <?php echo (isset($user['role']) && $user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Güncelle' : 'Oluştur'; ?></button>
                                <a href="?view=users" class="btn btn-secondary">İptal</a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- KUPON YÖNETİMİ -->
            <?php if ($view === 'coupons'): ?>
                <?php if ($action === 'list'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Kupon Yönetimi</h3>
                        <a href="?view=coupons&action=new" class="btn btn-primary">+ Yeni Kupon</a>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Kupon Kodu</th>
                                        <th>İndirim Oranı</th>
                                        <th>Kullanım Limiti</th>
                                        <th>Kullanım Sayısı</th>
                                        <th>Son Kullanma</th>
                                        <th style="width: 150px;">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coupons as $coupon): ?>
                                    <tr>
                                        <td><?php echo $coupon['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                        <td><?php echo $coupon['discount_rate']; ?>%</td>
                                        <td><?php echo $coupon['usage_limit']; ?></td>
                                        <td><?php echo $coupon['used_count']; ?></td>
                                        <td><?php echo $coupon['expiry_date']; ?></td>
                                        <td>
                                            <a href="?view=coupons&action=edit&id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                                            <a href="?view=coupons&action=delete&id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinizden emin misiniz?');">Sil</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                <?php elseif ($action === 'new' || $action === 'edit'): 
                    $isEdit = $action === 'edit';
                    $coupon = null;
                    if ($isEdit) {
                        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
                        $stmt->execute([$_GET['id']]);
                        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                ?>
                    <div class="card">
                        <div class="card-body">
                            <h4><?php echo $isEdit ? 'Kupon Düzenle' : 'Yeni Kupon Ekle'; ?></h4>
                            <form method="POST" action="?view=coupons&action=<?php echo $isEdit ? 'edit' : 'create'; ?>">
                                <?php if ($isEdit): ?>
                                    <input type="hidden" name="id" value="<?php echo $coupon['id']; ?>">
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="form-label">Kupon Kodu</label>
                                    <input type="text" class="form-control" name="code" value="<?php echo htmlspecialchars($coupon['code'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">İndirim Oranı (%)</label>
                                    <input type="number" step="0.01" class="form-control" name="discount_rate" value="<?php echo htmlspecialchars($coupon['discount_rate'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kullanım Limiti</label>
                                    <input type="number" class="form-control" name="usage_limit" value="<?php echo htmlspecialchars($coupon['usage_limit'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Son Kullanma Tarihi</label>
                                    <input type="date" class="form-control" name="expiry_date" value="<?php echo htmlspecialchars($coupon['expiry_date'] ?? ''); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Güncelle' : 'Oluştur'; ?></button>
                                <a href="?view=coupons" class="btn btn-secondary">İptal</a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p class="mb-0">&copy; 2025 Bilet Satın Alma Platformu</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
