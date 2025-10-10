<?php
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/auth.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $full_name = $_POST['full_name'] ?? '';

    // Şifre kontrol
    if ($password !== $password_confirm) {
        $message = 'Şifreler eşleşmiyor!';
        $messageType = 'danger';
    } else {
        $result = registerUser($username, $email, $password, $full_name, $pdo);
        $message = $result['error'] ?? $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';

        if ($result['success']) {
            header("Location: /login.php?success=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Bilet Satın Alma</title>
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
                    <li class="nav-item"><a class="nav-link" href="login.php">Giriş Yap</a></li>
                    <li class="nav-item"><a class="nav-link active" href="register.php">Kayıt Ol</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Ana İçerik -->
    <main class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="card-title text-center mb-4">Kayıt Ol</h2>

                        <!-- Mesaj Göster -->
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Kayıt Formu -->
                        <form method="POST">
                            <!-- Ad Soyad -->
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Ad Soyad</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="full_name" 
                                    name="full_name" 
                                    placeholder="Adınız Soyadınız"
                                    required
                                    value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                >
                            </div>

                            <!-- Kullanıcı Adı -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="username" 
                                    name="username" 
                                    placeholder="Kullanıcı adı"
                                    required
                                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                >
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    placeholder="email@example.com"
                                    required
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
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
                                    placeholder="En az 6 karakter"
                                    required
                                >
                            </div>

                            <!-- Şifre Onayla -->
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Şifre (Tekrar)</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    placeholder="Şifreyi tekrar girin"
                                    required
                                >
                            </div>

                            <!-- Kayıt Butonu -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Kayıt Ol</button>
                            </div>
                        </form>

                        <!-- Giriş Linki -->
                        <div class="text-center mt-3">
                            <p>Zaten hesabınız var mı? <a href="login.php">Giriş yap</a></p>
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