-- =============================================
-- USERS TABLOSU (Kullanıcılar)
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    full_name TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    virtual_credit REAL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- COMPANIES TABLOSU (Otobüs Şirketleri)
-- =============================================
CREATE TABLE IF NOT EXISTS companies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    contact_info TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- COMPANY_ADMINS TABLOSU (Firma Admin Atama)
-- =============================================
CREATE TABLE IF NOT EXISTS company_admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    company_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- =============================================
-- ROUTES TABLOSU (Otobüs Seferleri)
-- =============================================
CREATE TABLE IF NOT EXISTS routes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER NOT NULL,
    departure_point TEXT NOT NULL,
    arrival_point TEXT NOT NULL,
    departure_date TEXT NOT NULL,
    departure_time TEXT NOT NULL,
    price REAL NOT NULL,
    total_seats INTEGER NOT NULL,
    available_seats INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- =============================================
-- TICKETS TABLOSU (Satılan Biletler)
-- =============================================
CREATE TABLE IF NOT EXISTS tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    route_id INTEGER NOT NULL,
    seat_number INTEGER NOT NULL,
    purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'active',
    price REAL NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
);

-- =============================================
-- COUPONS TABLOSU (İndirim Kuponları)
-- =============================================
CREATE TABLE IF NOT EXISTS coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    discount_rate REAL NOT NULL,
    usage_limit INTEGER NOT NULL,
    used_count INTEGER DEFAULT 0,
    expiry_date TEXT NOT NULL,
    company_id INTEGER,
    is_global INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- =============================================
-- COUPON_USAGE TABLOSU (Kupon Kullanım Kaydı)
-- =============================================
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coupon_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- TEST VERİSİ - Admin Kullanıcı
-- =============================================
INSERT OR IGNORE INTO users (username, email, password, full_name, role, virtual_credit) 
VALUES (
    'admin',
    'admin@test.com',
    -- Şifre: admin123
    '$2y$10$N9qo8uLOickgx2ZMRZoMyeiKZiukpQLVMsqKBsXa1E2kyW2xf.vO',
    'Admin Kullanıcı',
    'admin',
    1000
);

-- Test Şirket
INSERT OR IGNORE INTO companies (name, contact_info)
VALUES ('Metro Turizm', 'metro@example.com');

-- Test Sefer
INSERT OR IGNORE INTO routes (company_id, departure_point, arrival_point, departure_date, departure_time, price, total_seats, available_seats)
VALUES (1, 'İstanbul', 'Ankara', '2025-10-25', '09:00', 150.00, 50, 50);