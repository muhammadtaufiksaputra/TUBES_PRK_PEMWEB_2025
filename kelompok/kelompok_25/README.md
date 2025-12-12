# Inventory Manager – Kelompok 25

# 📦 Sistem Manajemen Inventori Bahan Baku

Aplikasi web untuk mengelola inventori bahan baku dengan fitur manajemen supplier, kategori, material, stok masuk/keluar, dan pelaporan.

---

## 🚀 Cara Menjalankan Proyek

### **1. Prerequisites (Persyaratan Sistem)**

Pastikan sistem Anda sudah terinstal:

- **PHP 8.0 atau lebih tinggi** (Disarankan PHP 8.4)
- **MySQL 5.7 atau lebih tinggi** (atau MariaDB)
- **Web Server** (Apache/Nginx) atau PHP Built-in Server
- **Composer** (optional, untuk dependency management)
- **Git** (untuk clone repository)

**Cek versi PHP:**
```powershell
php -v
```

**Cek versi MySQL:**
```powershell
mysql --version
```

---

### **2. Clone Repository**

```powershell
git clone <repository-url>
cd TUBES_PRK_PEMWEB_2025/kelompok/kelompok_25
```

---

### **3. Setup Database**

#### **A. Buat Database Baru**

Buka MySQL client atau phpMyAdmin, kemudian jalankan:

```sql
CREATE DATABASE inventory_system;
```

#### **B. Import Database Schema**

**Via Command Line:**
```powershell
mysql -u root -p inventory_system < database.sql
```

**Via phpMyAdmin:**
1. Buka phpMyAdmin
2. Pilih database `inventory_system`
3. Klik tab "Import"
4. Pilih file `database.sql`
5. Klik "Go"

---

### **4. Konfigurasi Database**

Edit file `src/config/database.php` sesuai dengan konfigurasi MySQL Anda:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'inventory_system');
define('DB_USER', 'root');           // Sesuaikan dengan username MySQL Anda
define('DB_PASS', '');               // Sesuaikan dengan password MySQL Anda
define('DB_CHARSET', 'utf8mb4');
```

---

### **5. Setup Folder Upload**

Pastikan folder upload memiliki permission yang tepat:

```powershell
# Buat folder jika belum ada
mkdir -p src/public/assets/uploads/materials
mkdir -p src/public/assets/uploads/profiles
```

**Untuk Linux/Mac:**
```bash
chmod -R 755 src/public/assets/uploads
```

---

### **6. Jalankan Server**

#### **Opsi 1: PHP Built-in Server (Recommended untuk Development)**

```powershell
cd kelompok_25
php -S localhost:8000 -t src/public
```

Akses aplikasi di: **http://localhost:8000**

#### **Opsi 2: Apache/Nginx**

Konfigurasikan document root ke folder `src/public/`

**Apache VirtualHost Example:**
```apache
<VirtualHost *:80>
    ServerName inventory.local
    DocumentRoot "D:/path/to/kelompok_25/src/public"
    <Directory "D:/path/to/kelompok_25/src/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

### **7. Login ke Aplikasi**

Buka browser dan akses: **http://localhost:8000**

#### **Default Login Credentials:**

**Admin:**
- Email: `admin@example.com`
- Password: `admin123`

**Manager:**
- Email: `manager@example.com`
- Password: `manager123`

**Staff:**
- Email: `staff@example.com`
- Password: `staff123`

---

### **8. Verifikasi Instalasi**

Setelah login, cek beberapa hal berikut:

✅ Dashboard menampilkan statistik dengan benar  
✅ Menu sidebar dapat diakses  
✅ Halaman Supplier, Category, Material dapat dibuka  
✅ Data sample sudah muncul (3 suppliers, 5 categories, 10 materials)  

---

## 📁 Struktur Proyek

```
kelompok_25/
├── src/
│   ├── config/              # Konfigurasi (database, app)
│   ├── core/                # Core classes (Auth, Router, Model)
│   ├── controllers/         # Controllers (web & api)
│   │   ├── api/            # API controllers
│   │   └── web/            # Web controllers
│   ├── models/              # Models (User, Supplier, Category, Material)
│   ├── views/               # Views (Blade-like templates)
│   │   ├── auth/           # Login, register
│   │   ├── dashboard/      # Dashboard
│   │   ├── suppliers/      # Supplier management
│   │   ├── categories/     # Category management
│   │   ├── materials/      # Material management
│   │   ├── stock-in/       # Stock in management
│   │   ├── stock-out/      # Stock out management
│   │   ├── layouts/        # Layout templates
│   │   └── partials/       # Reusable components
│   ├── middleware/          # Middleware (Auth, Role)
│   ├── helpers/             # Helper functions
│   ├── routes/              # Route definitions (web.php, api.php)
│   └── public/              # Public assets (CSS, JS, images)
│       └── assets/
│           ├── css/        # Tailwind CSS
│           ├── js/         # JavaScript modules
│           └── uploads/    # Upload folder
├── database.sql            # Database schema & sample data
└── README.md              # Dokumentasi ini
```

---

## 🛠️ Troubleshooting

### **❌ Error: SQLSTATE[HY000] [2002] No such file or directory**
**Solusi:**
- Pastikan MySQL server sudah running
- Cek konfigurasi di `src/config/database.php`
- Gunakan `127.0.0.1` bukan `localhost` jika perlu

### **❌ Error: Column not found**
**Solusi:**
- Pastikan Anda sudah import `database.sql` dengan benar
- Drop database dan import ulang jika perlu
```sql
DROP DATABASE inventory_system;
CREATE DATABASE inventory_system;
```

### **❌ Error: Permission denied untuk folder uploads**
**Solusi:**
```powershell
chmod -R 755 src/public/assets/uploads  # Linux/Mac
icacls src\public\assets\uploads /grant Users:F  # Windows
```

### **❌ Error: Class not found**
**Solusi:**
- Pastikan path `ROOT_PATH` sudah benar di `src/public/index.php`
- Cek case-sensitive pada nama file (Linux/Mac case-sensitive)

### **❌ Halaman tidak ada CSS/JS**
**Solusi:**
- Pastikan menjalankan server dengan document root di `src/public/`
- Cek path asset di browser console (F12)

---

## 🧪 Testing API

Gunakan file `*.http` untuk testing API dengan REST Client extension di VS Code:

- `SUPPLIER_API_TEST.http` - Test supplier endpoints
- `CATEGORY_API_TEST.http` - Test category endpoints
- `MATERIAL_API_TEST.http` - Test material endpoints

**Atau gunakan curl:**

```powershell
# Login untuk mendapat token
curl -X POST http://localhost:8000/api/auth/login `
  -H "Content-Type: application/json" `
  -d '{"email":"admin@example.com","password":"admin123"}'

# Get suppliers
curl http://localhost:8000/api/suppliers `
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

Dashboard stok bahan baku berbasis PHP native dengan arsitektur MVC ringan. Frontend memakai Tailwind CSS, sedangkan backend menggunakan router kustom, controller, dan model PDO. Projek ini disiapkan agar tim BE dan FE dapat bekerja paralel: tampilan bisa dikembangkan lebih dulu, sementara integrasi data tetap mengikuti struktur controller/model yang sudah ada.

## Teknologi Utama
- PHP 8 (native, tanpa framework)
- Tailwind CSS (via CDN untuk pengembangan cepat)
- Router & Controller kustom
- PDO untuk akses database MySQL
- Struktur modular (views/layouts/partials) agar mudah di-scale

## Struktur Folder

```
kelompok_25/
├─ public/                      # Hanya direktori ini yang diakses browser
│  ├─ index.php                 # Front controller (semua request masuk sini)
│  ├─ .htaccess                 # Rewrite ke index.php (untuk Apache)
│  └─ assets/
│     ├─ css/app.css            # Style global
│     ├─ js/app.js             # Script global
│     ├─ js/modules/           # Script per fitur (auth/materials/stock/reports)
│     ├─ img/                  # Static assets
│     └─ uploads/materials/    # Foto bahan hasil upload
│
├─ src/
│  ├─ config/                  # Konfigurasi environment & koneksi DB
│  ├─ core/                    # Router, Base Controller, Auth helper, dll
│  ├─ routes/                  # `web.php` (view) & `api.php` (JSON)
│  ├─ models/                  # User, Role, Material, Supplier, Stock, dll
│  ├─ controllers/
│  │  ├─ web/                  # Controller yang merender view
│  │  └─ api/                  # Controller untuk request AJAX/JSON
│  ├─ views/                   # Layout, partial, dashboard, materials, dsb.
│  ├─ middleware/              # AuthMiddleware & RoleMiddleware
│  └─ helpers/                 # Utility (redirect, csrf, validator)
│
├─ tailwind.config.js
├─ package.json
└─ README.md
```

## Alur Singkat
1. Request masuk ke `public/index.php` lalu diteruskan ke Router.
2. Router mencocokkan path dengan `routes/web.php` (atau `routes/api.php`).
3. Middleware auth/role dijalankan jika dibutuhkan.
4. Controller mempersiapkan data, memanggil view (`views/...`) melalui `layouts/main.php` sehingga navbar dan sidebar otomatis ikut.
5. Asset CSS/JS di `public/assets` menangani tampilan dan interaksi ringan.

## Setup Awal (Clone dari GitHub)

### Prasyarat
- PHP 8.x terpasang di mesin lokal
- MySQL/MariaDB server aktif
- Composer (opsional, untuk dependencies jika ada)

### Langkah Setup dari Awal

#### 1. Clone Repository
```bash
git clone <repository-url>
cd kelompok_25
```

#### 2. Konfigurasi Database
Buat file `src/config/config.php` dengan mengcopy dari template (jika ada) atau buat baru:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'inventory_manager');
define('DB_USER', 'root');
define('DB_PASS', '');
define('ROOT_PATH', dirname(__DIR__));
```

#### 3. Import Database
1. Buat database baru di MySQL:
   ```sql
   CREATE DATABASE inventory_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Import schema dan data awal:
   ```bash
   mysql -u root -p inventory_manager < database.sql
   ```

   File `database.sql` sudah include:
   - ✅ Struktur tabel lengkap
   - ✅ User admin default (`admin@inventory.com` / `admin123`)
   - ✅ Roles & Permissions
   - ✅ Data sample (kategori & supplier)

3. **[OPSIONAL]** Jika ada kolom yang perlu ditambahkan setelah import:
   ```bash
   mysql -u root -p inventory_manager < add_destination_column.sql
   ```

#### 4. Verifikasi Permissions (OPSIONAL)
Script `setup_permissions.php` dan `create_admin.php` **TIDAK PERLU** dijalankan karena `database.sql` sudah include semuanya. 

**Hanya jalankan jika:**
- Lupa password admin → jalankan `php create_admin.php` untuk reset atau ketika login awal gagal
- Permissions tidak lengkap → jalankan `php setup_permissions.php`

#### 5. Jalankan Server Development
```powershell
# Windows PowerShell
cd src
php -S localhost:8000 -t public

# Linux/Mac
cd src
php -S localhost:8000 -t public
```

#### 6. Akses Aplikasi
- URL: `http://localhost:8000`
- Login: `admin@inventory.com` / `admin123`
- Ganti password setelah login pertama!

### Troubleshooting

**Error koneksi database:**
- Cek kredensial di `src/config/config.php`
- Pastikan MySQL service aktif
- Pastikan database `inventory_manager` sudah dibuat

**Permissions tidak bekerja:**
- Jalankan `php setup_permissions.php` untuk rebuild permissions
- Cek tabel `role_permissions` apakah terisi

**Lupa password admin:**
- Jalankan `php create_admin.php` untuk reset ke default (`admin123`)

---

## Cara Menjalankan Aplikasi (Development)

### Langkah Development
1. Buka terminal PowerShell dan arahkan ke direktori src:
	```powershell
	cd D:\BelajarPemrograman\TUBES_PRK_PEMWEB_2025\kelompok\kelompok_25\src
	```
2. Jalankan server PHP built-in:
	```powershell
	php -S localhost:8000 -t public
	```
3. Buka `http://localhost:8000` di browser.

> Catatan: Untuk lingkungan Apache/Nginx, arahkan document root ke folder `public/` dan pastikan rewrite rule mengarahkan semua request ke `index.php`.

## Pengembangan Lanjutan
- Tambahkan halaman baru dengan membuat folder view (`views/<fitur>/index.php`) dan mapping route di `routes/web.php`.
- Integrasikan data nyata dengan membuat model & controller API, kemudian panggil via AJAX dari `public/assets/js/modules/<fitur>.js`.
- Gunakan Tailwind CDN saat prototyping; pindah ke build pipeline (`npm run build`) jika perlu optimisasi produksi.

---
Kelompok 25 – Sistem Informasi Manajemen Stok Bahan Baku
