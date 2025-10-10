<?php
// src/auth.php
// Giriş, kayıt ve oturum yönetimi

// Oturum başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================
// Giriş durumu kontrol
// =============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? 'visitor';
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'email' => $_SESSION['email']
        ];
    }
    return null;
}

// =============================================
// Rol kontrolü
// =============================================

function requireRole($roles) {
    $roles = (array) $roles;
    if (!in_array(getUserRole(), $roles)) {
        header("Location: /access-denied.php");
        exit;
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /login.php");
        exit;
    }
}

// =============================================
// Kullanıcı kayıt
// =============================================

function registerUser($username, $email, $password, $fullName, $pdo) {
    // Validasyon
    if (strlen($username) < 3) {
        return ['success' => false, 'error' => 'Kullanıcı adı en az 3 karakter olmalıdır'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'error' => 'Şifre en az 6 karakter olmalıdır'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Geçerli bir email girin'];
    }

    try {
        // Şifreyi hash'le
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Veritabanına ekle
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, role, virtual_credit)
            VALUES (?, ?, ?, ?, 'user', 100)
        ");
        
        $stmt->execute([$username, $email, $hashedPassword, $fullName]);

        return ['success' => true, 'message' => 'Kayıt başarılı! Giriş yapabilirsiniz.'];

    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
            return ['success' => false, 'error' => 'Bu kullanıcı adı veya email zaten kayıtlı'];
        }
        return ['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()];
    }
}

// =============================================
// Kullanıcı giriş
// =============================================

function loginUser($username, $password, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, password, role, virtual_credit FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'error' => 'Kullanıcı adı bulunamadı'];
        }

        // Şifreyi kontrol et
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Şifre yanlış'];
        }

        // Session'a kayıt et
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        return ['success' => true, 'message' => 'Giriş başarılı!', 'user' => $user];

    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()];
    }
}

// =============================================
// Çıkış
// =============================================

function logoutUser() {
    session_destroy();
    header("Location: /index.php");
    exit;
}
?>