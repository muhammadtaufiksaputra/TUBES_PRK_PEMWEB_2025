# Inventory Manager – Kelompok 25

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
