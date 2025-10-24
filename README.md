# Bilet Satın Alma Platformu

Online otobüs bileti satış ve rezervasyon sistemi. PHP ve SQLite kullanarak geliştirdiğim web tabanlı bir uygulama.

## Proje Hakkında

Gerçek hayattaki otobüs firmaları gibi çalışan, bilet satışı yapabilen ve yönetim panelleri olan tam özellikli bir platform.

Projeyi yaparken özellikle kullanıcı deneyimine önem verdim. Koltuk seçimi ekranını gerçek otobüs düzenine benzettim, kupon sistemi ekledim ve her şeyin güvenli çalışmasına dikkat ettim.

## Özellikler

### Kullanıcı Rolleri
- **Ziyaretçi:** Seferleri görüntüleyebilir ama bilet alamaz
- **Normal Kullanıcı:** Bilet satın alabilir, iptal edebilir, profilini yönetebilir
- **Firma Admin:** Kendi firmasının seferlerini ve kuponlarını yönetir
- **Admin:** Tüm sistemi yönetir (firmalar, kullanıcılar, global kuponlar)

### Ana Özellikler
- Sefer arama ve filtreleme
- Gerçekçi otobüs koltuk seçimi (2+2 düzen)
- Kupon sistemi (anlık doğrulama ile)
- Bilet iptal (kalkışa 1 saatten az kala iptal edilemez)
- PDF bilet indirme
- Sanal kredi ile ödeme
- Firma admin paneli (sefer ve kupon yönetimi)
- Admin paneli (sistem yönetimi)

## Teknolojiler

- **Backend:** PHP
- **Veritabanı:** SQLite
- **Frontend:** HTML, CSS, Bootstrap 5
- **JavaScript:** AJAX ile dinamik işlemler





## Kullanım

### Normal Kullanıcı İçin
1. Ana sayfada kayıt olun
2. Sefer arayın (kalkış, varış, tarih)
3. Uygun seferi seçin
4. Koltuk seçin (dolu koltuklar kırmızı gözükür)
5. Varsa kupon kodunuzu girin
6. Bilet satın alın
7. "Biletlerim" sayfasından biletinizi görüntüleyin veya iptal edin
8. PDF olarak indirebilirsiniz

### Firma Admin İçin
1. Firma admin hesabıyla giriş yapın
2. Dashboard'da firma istatistiklerini görün
3. "Seferlerim" bölümünden sefer ekleyin/düzenleyin/silin
4. "Kuponlarım" bölümünden firma özel kupon oluşturun

### Admin İçin
1. Admin hesabıyla giriş yapın
2. Yeni otobüs firması ekleyin
3. Firma admin kullanıcısı oluşturup firmaya atayın
4. Tüm firmalara geçerli global kuponlar oluşturun

## Veritabanı Yapısı

Proje 8 tablodan oluşuyor:
- `users` - Kullanıcı bilgileri
- `companies` - Otobüs firmaları
- `company_admins` - Firma yöneticileri
- `routes` - Otobüs seferleri
- `tickets` - Satın alınan biletler
- `coupons` - İndirim kuponları
- `coupon_usage` - Kupon kullanım geçmişi

## Güvenlik

Projeyi geliştirirken güvenlik konusuna özen gösterdim:
- Şifreler bcrypt ile hashleniyor
- SQL Injection'a karşı PDO prepared statements kullandım
- Session ile kimlik doğrulama
- Rol bazlı yetkilendirme (her kullanıcı sadece yetkisi olan sayfaları görebiliyor)

## Öne Çıkan Özellikler

### Koltuk Seçimi
Gerçek otobüs gibi 2+2 koltuk düzeni. Dolu koltuklar kırmızı ve tıklanamaz, seçili koltuk mavi olup animasyon yapıyor. Hover efektleri ekledim.

### Kupon Sistemi
AJAX ile anlık kupon doğrulama yaptım. Kupon girince sayfa yenilenmeden fiyat güncellenip indirim gösteriliyor.

### Bilet İptal Kuralı
Kalkışa 1 saatten az süre kaldıysa bilet iptal edilemiyor. Bu kontrolü yaptım çünkü gerçek hayatta da böyle oluyor.

### PDF Bilet
Biletleri PDF formatında indirebiliyorsunuz. Modern ve şık bir tasarım yaptım.

## Proje Yapısı

```
bilet-satin-alma/
├── db/                          # Veritabanı dosyaları
│   ├── app.db                   # SQLite veritabanı
│   ├── schema.sql               # Tablo yapıları
│   └── *.php                    # Yardımcı scriptler
├── public/                      # Web dosyaları
│   ├── css/
│   │   └── style.css           # Ana CSS
│   ├── firma-admin/            # Firma admin paneli
│   └── *.php                   # Ana sayfalar
└── src/                        # Backend kodları
    ├── database.php            # Veritabanı bağlantısı
    └── auth.php               # Kimlik doğrulama

```

## Ekran Görüntüleri

Ana sayfada sefer arama, bilet satın alma ekranında otobüs koltukları, admin panelinde yönetim ekranları var. Modern ve kullanıcı dostu bir arayüz tasarladım.

## Geliştirme Süreci

Projeyi yaklaşık 2 hafta içinde tamamladım. İlk başta veritabanı tasarımını yaptım, sonra kullanıcı sistemi, ardından bilet alma mantığı ve en son admin panellerini geliştirdim.

En çok uğraştığım kısımlar:
- Koltuk seçimi sistemini responsive yapmak
- AJAX ile kupon doğrulama
- Transaction'ları düzgün yönetmek (bilet alırken/iptal ederken)

En keyif aldığım kısımlar:
- Koltuk seçimi ekranını tasarlamak
- Animasyonlar ve hover efektleri eklemek
- Admin panellerini şekillendirmek



## Notlar

- Şu an 1600+ test seferi var veritabanında
- 10 farklı otobüs firması ekledim
- Responsive tasarım yaptım, mobilde de çalışıyor
- Modern tarayıcılarda test ettim (Chrome, Firefox, Edge)




