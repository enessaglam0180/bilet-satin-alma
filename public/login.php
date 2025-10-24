<?php
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/auth.php';

// Zaten giriş yaptıysa ana sayfaya yönlendir
if (isLoggedIn()) {
    header("Location: /index.php");
    exit;
}

$message = '';
$messageType = '';
$success = $_GET['success'] ?? 0;

if ($success) {
    $message = '✅ Kayıt başarılı! Şimdi giriş yapabilirsiniz.';
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = loginUser($username, $password, $pdo);
    
    if ($result['success']) {
        if ($result['user']['role'] === 'admin') {
            header("Location: admin_panel.php");
        } elseif ($result['user']['role'] === 'firma_admin') {
            header("Location: firma-admin/dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $message = $result['error'];
        $messageType = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Bilet Satın Alma</title>
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
                    <li class="nav-item"><a class="nav-link active" href="login.php">Giriş Yap</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Kayıt Ol</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Ana İçerik -->
    <main class="container mt-5">
        <div class="row">
            <div class="col-md-5 offset-md-3">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="card-title text-center mb-4">Giriş Yap</h2>

                        <!-- Mesaj Göster -->
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>


                        <!-- Giriş Formu -->
                        <form method="POST">
                            <!-- Kullanıcı Adı -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="username" 
                                    name="username" 
                                    placeholder="Kullanıcı adınız"
                                    required
                                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                >
                            </div>

                            <!-- Şifre -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Şifreniz"
                                    required
                                >
                            </div>

                            <!-- Giriş Butonu -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Giriş Yap</button>
                            </div>
                        </form>

                        <!-- Kayıt Linki -->
                        <div class="text-center mt-3">
                            <p>Hesabınız yok mu? <a href="register.php">Kayıt olun</a></p>
                        </div>
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