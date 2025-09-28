# API Laravel Laundry Multi-Outlet

Sistem manajemen laundry multi-outlet yang komprehensif dibangun dengan Laravel 12, menampilkan REST API yang robust untuk mengelola layanan laundry, pesanan, pelanggan, dan operasi outlet di berbagai lokasi bisnis.

## Daftar Isi

-   [Fitur](#fitur)
-   [Gambaran Arsitektur](#gambaran-arsitektur)
-   [Kebutuhan Sistem](#kebutuhan-sistem)
-   [Instalasi & Pengaturan](#instalasi--pengaturan)
-   [Dokumentasi API](#dokumentasi-api)
-   [Aturan Bisnis](#aturan-bisnis)
-   [Izin & Manajemen Peran](#izin--manajemen-peran)
-   [Pelaporan](#pelaporan)
-   [Pengujian](#pengujian)
-   [Deployment](#deployment)
-   [Pengembangan](#pengembangan)
-   [Konvensi API](#konvensi-api)
-   [Kontribusi](#kontribusi)

## Fitur

### Fitur Bisnis Utama

-   **Manajemen Multi-Outlet**: Dukungan untuk berbagai outlet laundry dengan manajemen terpusat
-   **Manajemen Pelanggan**: Registrasi pelanggan, profil, dan riwayat layanan per outlet
-   **Manajemen Layanan**: Definisi layanan yang fleksibel dengan varian, harga, dan alur kerja pemrosesan
-   **Pemrosesan Pesanan**: Siklus hidup pesanan lengkap dari check-in hingga pengambilan
-   **Pemrosesan Pembayaran**: Berbagai metode pembayaran dengan pelacakan transaksi
-   **Manajemen Inventori**: Manajemen dasar parfum dan diskon
-   **Pelacakan Status Real-time**: Pembaruan status pesanan dengan kalkulasi ETA
-   **Generasi Invoice**: Penomoran invoice otomatis dengan format JL-YYMMDDNNNN
-   **Pencatatan Aktivitas**: Jejak audit komprehensif untuk semua operasi bisnis

### Fitur Teknis

-   **RESTful API**: Endpoint API yang bersih dan berversi mengikuti konvensi REST
-   **Autentikasi**: Laravel Sanctum untuk autentikasi API yang aman
-   **Otorisasi**: Izin berbasis peran per outlet
-   **Validasi Data**: Validasi input dan penanganan error yang komprehensif
-   **Optimisasi Database**: Query berindeks dan relasi yang efisien
-   **Pencatatan Aktivitas**: Jejak audit lengkap menggunakan Spatie Activity Log
-   **Test Suite**: Cakupan test komprehensif dengan PHPUnit
-   **Kualitas Kode**: Laravel Pint untuk format kode yang konsisten

## Gambaran Arsitektur

### Stack Teknologi

-   **Framework**: Laravel 12.26.3
-   **Versi PHP**: 8.3.22
-   **Database**: MySQL 8.0+
-   **Autentikasi**: Laravel Sanctum
-   **Testing**: PHPUnit 11.5+
-   **Format Kode**: Laravel Pint 1.24
-   **Pencatatan Aktivitas**: Spatie Laravel Activity Log 4.10

### Pola Desain

-   **Repository Pattern**: Abstraksi service layer untuk logika bisnis
-   **Factory Pattern**: Database seeder dan generasi data test
-   **Observer Pattern**: Event model untuk pencatatan aktivitas
-   **Resource Pattern**: Transformasi response API
-   **Form Request Pattern**: Logika validasi terpusat

### Desain Database

Sistem menggunakan struktur database yang dinormalisasi dengan entitas inti berikut:

-   **Users**: Pengguna sistem dengan autentikasi
-   **Outlets**: Lokasi bisnis laundry individual
-   **Customers**: Profil pelanggan yang di-scope ke outlet
-   **Services**: Definisi layanan dengan varian dan harga
-   **Orders**: Manajemen pesanan lengkap dengan pelacakan siklus hidup
-   **Payments**: Pemrosesan pembayaran dan catatan transaksi
-   **Activity Logs**: Jejak audit komprehensif

## Kebutuhan Sistem

### Kebutuhan Minimum

-   PHP 8.2 atau lebih tinggi
-   MySQL 8.0 atau MariaDB 10.3+
-   Composer 2.0+
-   Node.js 18+ (untuk kompilasi asset)
-   RAM minimum 512MB (direkomendasikan 2GB)
-   Ruang disk 1GB

### Lingkungan Produksi yang Direkomendasikan

-   PHP 8.3+ dengan OPCache diaktifkan
-   MySQL 8.0+ dengan query cache
-   Redis untuk penyimpanan session/cache
-   Nginx atau Apache dengan HTTP/2
-   Sertifikat SSL untuk HTTPS
-   RAM 4GB
-   Penyimpanan SSD

## Instalasi & Pengaturan

### 1. Clone Repository

```bash
git clone <repository-url>
cd laravel-laundry-jf-backend
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Konfigurasi file `.env` Anda dengan pengaturan penting berikut:

```env
APP_NAME="Laravel Laundry API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laundry_production
DB_USERNAME=username_anda
DB_PASSWORD=password_anda

SANCTUM_STATEFUL_DOMAINS=domain-frontend-anda.com
SESSION_DOMAIN=.domain-anda.com
```

### 4. Setup Database

```bash
php artisan migrate
php artisan db:seed
```

### 5. Setup Storage

```bash
php artisan storage:link
```

### 6. Konfigurasi Cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Izin File

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Dokumentasi API

### Base URL

```
Produksi: https://api.domainanda.com/api/v1
Development: http://localhost:8000/api/v1
```

### Autentikasi

Semua endpoint API memerlukan autentikasi menggunakan token Laravel Sanctum.

#### Login

```http
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

#### Response

```json
{
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com"
        },
        "token": "sanctum-token-here"
    }
}
```

### Endpoint API Utama

#### Manajemen Outlet

```http
GET    /api/v1/outlets                    # List semua outlet
POST   /api/v1/outlets                    # Buat outlet baru
GET    /api/v1/outlets/{id}               # Detail outlet
PUT    /api/v1/outlets/{id}               # Update outlet
```

#### Manajemen Pelanggan

```http
GET    /api/v1/outlets/{outlet}/customers          # List pelanggan
POST   /api/v1/outlets/{outlet}/customers          # Buat pelanggan
GET    /api/v1/outlets/{outlet}/customers/{id}     # Detail pelanggan
PUT    /api/v1/outlets/{outlet}/customers/{id}     # Update pelanggan
```

#### Manajemen Layanan

```http
GET    /api/v1/outlets/{outlet}/services           # List layanan
POST   /api/v1/outlets/{outlet}/services           # Buat layanan
PUT    /api/v1/outlets/{outlet}/services/{id}      # Update layanan
POST   /api/v1/outlets/{outlet}/services/{id}/variants  # Tambah varian
```

#### Manajemen Pesanan

```http
GET    /api/v1/outlets/{outlet}/orders             # List pesanan
POST   /api/v1/outlets/{outlet}/orders             # Buat pesanan
GET    /api/v1/outlets/{outlet}/orders/{id}        # Detail pesanan
PUT    /api/v1/outlets/{outlet}/orders/{id}        # Update status pesanan
DELETE /api/v1/outlets/{outlet}/orders/{id}        # Batalkan pesanan
```

**Contoh Request Buat Pesanan:**

```json
{
    "customer_id": 1,
    "perfume_id": 2,
    "notes": "Instruksi khusus untuk pesanan",
    "discount_value_snapshot": 5000,
    "items": [
        {
            "service_variant_id": 1,
            "qty": 2.5
        }
    ]
}
```

#### Manajemen Pembayaran

```http
GET    /api/v1/outlets/{outlet}/payment-methods    # List metode pembayaran
POST   /api/v1/outlets/{outlet}/payment-methods    # Buat metode pembayaran
POST   /api/v1/orders/{order}/payments             # Proses pembayaran
```

### Format Response

Semua response API mengikuti struktur yang konsisten:

#### Response Sukses

```json
{
    "data": {
        // Data response di sini
    },
    "meta": {
        "pagination": {
            "current_page": 1,
            "total": 100,
            "per_page": 15
        }
    }
}
```

#### Response Error

```json
{
    "message": "Validasi gagal",
    "errors": {
        "field_name": ["Deskripsi error"]
    }
}
```

## Aturan Bisnis

### Manajemen Pesanan

1. **Alur Status Pesanan**: `ANTRIAN` → `PROSES` → `SIAP_DIAMBIL` → `SELESAI`
2. **Status Pembayaran**: Pesanan bisa `UNPAID` atau `PAID`
3. **Kalkulasi ETA**: Dihitung otomatis berdasarkan waktu pemrosesan layanan
4. **Penomoran Invoice**: Format `JL-YYMMDDNNNN` (JL-241225001)
5. **Pembatalan Pesanan**: Hanya diizinkan dalam status `ANTRIAN`

### Harga Layanan

1. **Jenis Unit**: Layanan diharga per `PIECE`, `KG`, atau `SET`
2. **Kuantitas Minimum**: Setiap varian layanan memiliki persyaratan kuantitas minimum
3. **Validasi Harga**: Harga negatif tidak diizinkan
4. **Waktu Penyelesaian**: Setiap varian mendefinisikan waktu pemrosesan dalam jam

### Manajemen Pelanggan

1. **Scoping Outlet**: Pelanggan di-scope ke outlet tertentu
2. **Validasi Kontak**: Validasi telepon dan email per outlet
3. **Status Aktif**: Pelanggan dapat diaktifkan/dinonaktifkan

### Sistem Diskon

1. **Jenis Diskon**: `PERCENTAGE` atau `FIXED_AMOUNT`
2. **Batasan Nilai**: Diskon persentase dibatasi 0-100%
3. **Preservasi Snapshot**: Nilai diskon disimpan saat pembuatan pesanan

### Pemrosesan Pembayaran

1. **Pembayaran Tunggal**: Satu pembayaran per pesanan
2. **Metode Pembayaran**: Tunai, Transfer Bank, Dompet Digital, Kartu Kredit
3. **Pelacakan Status**: Status pembayaran otomatis memperbarui status pembayaran pesanan

## Izin & Manajemen Peran

### Peran Outlet

-   **Owner**: Kontrol penuh atas operasi outlet
-   **Manager**: Manajemen operasional tanpa akses finansial
-   **Staff**: Pemrosesan pesanan dasar dan layanan pelanggan
-   **Viewer**: Akses baca-saja ke data outlet

### Matriks Izin

| Fitur                 | Owner | Manager | Staff | Viewer |
| --------------------- | ----- | ------- | ----- | ------ |
| Pengaturan Outlet     | ✅    | ❌      | ❌    | ❌     |
| Manajemen User        | ✅    | ✅      | ❌    | ❌     |
| Manajemen Layanan     | ✅    | ✅      | ❌    | ❌     |
| Manajemen Pesanan     | ✅    | ✅      | ✅    | ❌     |
| Manajemen Pelanggan   | ✅    | ✅      | ✅    | ❌     |
| Pemrosesan Pembayaran | ✅    | ✅      | ✅    | ❌     |
| Laporan Keuangan      | ✅    | ✅      | ❌    | ❌     |
| Lihat Laporan         | ✅    | ✅      | ✅    | ✅     |

### Implementasi

Izin diimplementasikan menggunakan:

-   Sistem otorisasi bawaan Laravel
-   Middleware outlet-scoped
-   Kontrol akses berbasis peran dalam resource API
-   Relasi user-outlet level database

## Pelaporan

### Laporan yang Tersedia

1. **Ringkasan Penjualan Harian**: Pendapatan, jumlah pesanan, nilai rata-rata pesanan
2. **Performa Layanan**: Layanan populer, pendapatan per layanan
3. **Analitik Pelanggan**: Pelanggan baru, pelanggan berulang, nilai seumur hidup pelanggan
4. **Analisis Pembayaran**: Distribusi metode pembayaran, pelacakan status pembayaran
5. **Metrik Operasional**: Waktu pemrosesan pesanan, tingkat penyelesaian
6. **Performa Staff**: Pesanan yang diproses per anggota staff

### Endpoint Laporan

```http
GET /api/v1/outlets/{outlet}/reports/daily-sales?date=2024-01-15
GET /api/v1/outlets/{outlet}/reports/service-performance?period=monthly
GET /api/v1/outlets/{outlet}/reports/customer-analytics?start_date=2024-01-01
```

### Format Export

-   JSON (response API)
-   CSV (comma-separated values)
-   PDF (laporan terformat)
-   Excel (format XLSX)

## Pengujian

### Gambaran Test Suite

Aplikasi mencakup cakupan test yang komprehensif:

-   **Unit Tests**: Validasi service layer dan logika bisnis
-   **Feature Tests**: Pengujian endpoint API dengan interaksi database
-   **Integration Tests**: Pengujian alur kerja lengkap
-   **Performance Tests**: Validasi optimisasi query database

### Menjalankan Tests

```bash
# Jalankan semua tests
php artisan test

# Jalankan test suite tertentu
php artisan test --testsuite=Feature

# Jalankan dengan coverage
php artisan test --coverage

# Jalankan file test tertentu
php artisan test tests/Feature/Api/V1/OrderFlowTest.php
```

### Database Test

Tests menggunakan database SQLite terpisah untuk isolasi:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### File Test Utama

-   `tests/Feature/Api/V1/OrderFlowTest.php`: Alur kerja pemrosesan pesanan lengkap
-   `tests/Unit/OrderServiceTest.php`: Validasi logika bisnis pesanan
-   `tests/Unit/InvoiceServiceTest.php`: Pengujian generasi invoice
-   `tests/Feature/Api/V1/OrderDebugTest.php`: Validasi edge case

### Continuous Integration

Tests dijalankan otomatis pada:

-   Pembuatan pull request
-   Push kode ke branch main
-   Jadwal harian
-   Verifikasi pre-deployment

## Deployment

### Checklist Deployment Produksi

#### 1. Kebutuhan Server

-   [ ] PHP 8.3+ dengan ekstensi yang diperlukan
-   [ ] MySQL 8.0+ dikonfigurasi dengan benar
-   [ ] Nginx/Apache dengan SSL
-   [ ] Redis untuk caching (direkomendasikan)
-   [ ] Supervisor untuk pemrosesan queue

#### 2. Konfigurasi Environment

-   [ ] File `.env` produksi dikonfigurasi
-   [ ] Kredensial database diamankan
-   [ ] Application key dibuat
-   [ ] Domain CORS dikonfigurasi
-   [ ] Pengaturan mail dikonfigurasi

#### 3. Setup Keamanan

-   [ ] Sertifikat SSL terinstal
-   [ ] Aturan firewall dikonfigurasi
-   [ ] Akses database dibatasi
-   [ ] Izin file diatur dengan benar
-   [ ] Mode debug dinonaktifkan

#### 4. Optimisasi Performa

-   [ ] OPCache diaktifkan
-   [ ] Cache aplikasi dibuat
-   [ ] Indeks database dioptimalkan
-   [ ] CDN dikonfigurasi untuk asset
-   [ ] Load balancer dikonfigurasi (jika diperlukan)

#### 5. Setup Monitoring

-   [ ] Monitoring aplikasi (New Relic, Bugsnag)
-   [ ] Monitoring server (Datadog, Prometheus)
-   [ ] Agregasi log (ELK Stack, CloudWatch)
-   [ ] Monitoring uptime
-   [ ] Monitoring performa

### Script Deployment

```bash
#!/bin/bash
# deployment-script.sh

echo "Memulai deployment..."

# Pull kode terbaru
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Clear dan cache konfigurasi
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan migrasi
php artisan migrate --force

# Restart services
php artisan queue:restart
sudo supervisorctl restart laravel-worker:*

# Clear cache aplikasi
php artisan cache:clear

echo "Deployment berhasil diselesaikan!"
```

### Konfigurasi Spesifik Environment

#### Produksi

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

#### Staging

```env
APP_ENV=staging
APP_DEBUG=true
LOG_LEVEL=debug
QUEUE_CONNECTION=database
CACHE_DRIVER=file
```

## Pengembangan

### Setup Development

```bash
# Install development dependencies
composer install
npm install

# Jalankan migrasi dengan seeder
php artisan migrate:fresh --seed

# Start development server
php artisan serve

# Watch perubahan file
npm run dev
```

### Standar Kode

-   **PSR-12**: Standar coding PHP
-   **Konvensi Laravel**: Ikuti best practice Laravel
-   **Type Hints**: Gunakan strict typing jika memungkinkan
-   **Dokumentasi**: Block PHPDoc untuk semua method
-   **Testing**: Pertahankan cakupan test 80%+

### Tool Development

-   **Laravel Pint**: Format kode (`vendor/bin/pint`)
-   **PHPStan**: Analisis statis
-   **Laravel Debugbar**: Debugging development
-   **Laravel Telescope**: Monitoring aplikasi
-   **Clockwork**: Profiling performa

### Git Workflow

1. Buat feature branch dari `main`
2. Implementasi perubahan dengan tests
3. Jalankan pemeriksaan kualitas kode
4. Submit pull request
5. Code review dan approval
6. Merge ke main
7. Deploy ke staging/produksi

### Migrasi Database

```bash
# Buat migrasi baru
php artisan make:migration create_new_table

# Jalankan migrasi
php artisan migrate

# Rollback migrasi
php artisan migrate:rollback

# Reset database
php artisan migrate:fresh --seed
```

## Konvensi API

### Kode Status HTTP

-   `200 OK`: GET, PUT, PATCH berhasil
-   `201 Created`: POST berhasil
-   `204 No Content`: DELETE berhasil
-   `400 Bad Request`: Data request tidak valid
-   `401 Unauthorized`: Autentikasi diperlukan
-   `403 Forbidden`: Akses ditolak
-   `404 Not Found`: Resource tidak ditemukan
-   `409 Conflict`: Konflik resource
-   `422 Unprocessable Entity`: Error validasi
-   `500 Internal Server Error`: Error server

### Header Request/Response

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
X-Requested-With: XMLHttpRequest
```

### Pagination

```json
{
    "data": [...],
    "meta": {
        "pagination": {
            "current_page": 1,
            "from": 1,
            "last_page": 10,
            "per_page": 15,
            "to": 15,
            "total": 150
        }
    },
    "links": {
        "first": "http://api.example.com/orders?page=1",
        "last": "http://api.example.com/orders?page=10",
        "prev": null,
        "next": "http://api.example.com/orders?page=2"
    }
}
```

### Penanganan Error

```json
{
    "message": "Data yang diberikan tidak valid.",
    "errors": {
        "customer_id": ["Field customer id wajib diisi."],
        "items.0.service_variant_id": [
            "Varian layanan yang dipilih tidak valid."
        ]
    }
}
```

### Rate Limiting

-   **Pengguna Terautentikasi**: 1000 request per jam
-   **Pengguna Guest**: 100 request per jam
-   **API Keys**: 5000 request per jam

## Kontribusi

### Memulai

1. Fork repository
2. Buat feature branch
3. Buat perubahan Anda
4. Tambah/update tests
5. Pastikan semua tests berhasil
6. Submit pull request

### Panduan Kontribusi

-   Ikuti standar coding PSR-12
-   Tulis tests yang komprehensif
-   Update dokumentasi
-   Tambahkan commit message yang bermakna
-   Satu fitur per pull request

### Proses Code Review

1. Automated tests harus berhasil
2. Pemeriksaan kualitas kode harus berhasil
3. Peer review diperlukan
4. Security review untuk perubahan sensitif
5. Penilaian dampak performa

## Support

### Dokumentasi API

-   Dokumentasi API: Tersedia di `/api/documentation`
-   Koleksi Postman: Tersedia di `/docs/postman/`
-   Schema Database: Dibuat dengan `php artisan schema:dump`
-   **Perubahan API Terbaru**: Lihat [docs/api-order-changes.md](docs/api-order-changes.md) untuk perubahan struktur order

### Mendapatkan Bantuan

-   **Masalah Teknis**: Buat GitHub issue
-   **Kerentanan Keamanan**: Email security@domainanda.com
-   **Pertanyaan Bisnis**: Kontak support@domainanda.com
-   **Feature Request**: Submit GitHub feature request

### Maintenance

-   **Update Keamanan**: Diterapkan bulanan atau sesuai kebutuhan
-   **Update Framework**: Triwulanan dengan periode testing
-   **Maintenance Database**: Script optimisasi mingguan
-   **Jadwal Backup**: Backup otomatis harian dengan retensi 30 hari

### Monitoring Performa

-   Response time aplikasi dipantau
-   Performa query database dipantau
-   Error rate dan metrik sukses dipantau
-   Utilisasi resource dipantau

---

## Lisensi

Proyek ini dilisensikan di bawah MIT License. Lihat file [LICENSE](LICENSE) untuk detail.

## Changelog

Lihat [CHANGELOG.md](CHANGELOG.md) untuk daftar detail perubahan dan riwayat versi.

---

**Dibangun dengan ❤️ menggunakan Laravel 12**

Untuk update dan pengumuman terbaru, ikuti [blog development](https://blog.domainanda.com) atau beri star repository ini.

```bash
curl -H "Authorization: Bearer {your-token}" \
     -H "Accept: application/json" \
     http://localhost:8000/api/v1/me
```

**Dapatkan token melalui login:**

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{
       "email": "owner@demo.com",
       "password": "password"
     }'
```

### Response Envelope

Semua response API mengikuti struktur JSON berikut:

```json
{
  "success": true|false,
  "message": "Pesan deskriptif",
  "data": {...}|null,
  "errors": {...}|null,
  "meta": {
    "pagination": {...},
    "timestamps": {...}
  }|null
}
```

### Kode Status HTTP

| Kode | Deskripsi             | Penggunaan               |
| ---- | --------------------- | ------------------------ |
| 200  | OK                    | GET, PUT, PATCH berhasil |
| 201  | Created               | POST berhasil            |
| 204  | No Content            | DELETE berhasil          |
| 400  | Bad Request           | Data request tidak valid |
| 401  | Unauthorized          | Token hilang/tidak valid |
| 403  | Forbidden             | Izin tidak cukup         |
| 404  | Not Found             | Resource tidak ditemukan |
| 422  | Unprocessable Entity  | Error validasi           |
| 500  | Internal Server Error | Error server             |

### Penanganan Error

Error validasi (422):

```json
{
    "success": false,
    "message": "Validasi gagal",
    "data": null,
    "errors": {
        "email": ["Field email wajib diisi."],
        "password": ["Password minimal 8 karakter."]
    },
    "meta": null
}
```

## Modul & Endpoint

| Modul                 | Endpoint                                                                                                                                                                                                                   | Deskripsi                   |
| --------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------- |
| **Auth**              | `POST /auth/register`<br>`POST /auth/login`<br>`GET /auth/me`<br>`POST /auth/logout`                                                                                                                                       | Autentikasi & profil user   |
| **Outlet**            | `GET /outlets`<br>`POST /outlets`<br>`GET /outlets/{id}`<br>`PUT /outlets/{id}`                                                                                                                                            | Manajemen outlet            |
| **User-Outlet**       | `GET /outlets/{id}/members`<br>`POST /outlets/{id}/invite`<br>`PUT /outlets/{id}/members/{userId}`                                                                                                                         | Staff & izin                |
| **Metode Pembayaran** | `GET /outlets/{id}/payment-methods`<br>`POST /outlets/{id}/payment-methods`<br>`PUT /payment-methods/{id}`<br>`DELETE /payment-methods/{id}`                                                                               | Manajemen metode pembayaran |
| **Layanan**           | `GET /outlets/{id}/services`<br>`POST /outlets/{id}/services`<br>`GET /services/{id}/variants`                                                                                                                             | Manajemen layanan & varian  |
| **Master**            | `GET /outlets/{id}/perfumes`<br>`GET /outlets/{id}/discounts`                                                                                                                                                              | Parfum & diskon             |
| **Pesanan**           | `GET /outlets/{id}/orders`<br>`POST /outlets/{id}/orders`<br>`GET /outlets/{id}/orders/{id}`<br>`POST /outlets/{id}/orders/{id}/status`<br>`POST /outlets/{id}/orders/{id}/pay`<br>`POST /outlets/{id}/orders/{id}/pickup` | Siklus hidup pesanan        |
| **Dashboard**         | `GET /outlets/{id}/dashboard/summary`                                                                                                                                                                                      | Analitik bisnis             |

> 📖 **Dokumentasi API Detail**: Lihat [docs/integration-guide.md](docs/integration-guide.md) untuk spesifikasi endpoint lengkap, contoh request/response, dan panduan integrasi.

## Aturan Bisnis

### Manajemen Pesanan

-   **Metode Pembayaran Tunggal**: Setiap pesanan mendukung hanya satu metode pembayaran (tidak ada pembayaran parsial)
-   **Format Invoice**: `JL-YYMMDDNNNN` (urutan per outlet per hari)
    -   `JL`: Prefix untuk Jaya Laundry
    -   `YYMMDD`: Tanggal dalam format 2-digit tahun, bulan, hari
    -   `NNNN`: Urutan 4-digit mulai dari 0001
-   **Parfum & Notes**: Disimpan di level order, bukan di order item individual
-   **Struktur Order Item**: Hanya menyimpan `service_variant_id` dan `qty`, tidak ada note atau parfum

### Alur Status Pesanan

```
ANTRIAN → PROSES → SIAP_DIAMBIL → SELESAI
                       ↓
                     BATAL (hanya sebelum SELESAI)
```

-   **ANTRIAN**: Mengantri untuk diproses
-   **PROSES**: Sedang diproses
-   **SIAP_DIAMBIL**: Siap untuk diambil pelanggan
-   **SELESAI**: Pesanan selesai dan telah diambil
-   **BATAL**: Pesanan dibatalkan

### Pengambilan & Penyelesaian

-   **Aksi Pickup**: Menandai pesanan sebagai `SELESAI` dan mengatur timestamp `collected_at`
-   **Metrik**: `collected_at` digunakan untuk melacak pengambilan item aktual vs penyelesaian

### Harga & Kuantitas

-   **Unit Kuantitas**:
    -   `kg`, `meter`: Memungkinkan nilai desimal (mis. 2.5 kg)
    -   `pcs`: Harus nilai integer saja (mis. 5 pcs)

### Diskon

-   **Diskon level pesanan**: Diterapkan ke seluruh pesanan
-   **Jenis**: Jumlah nominal atau persentase
-   **Snapshot**: Nilai diskon dibekukan saat pembuatan pesanan

## Izin & Scoping

### Kontrol Akses Berbasis Peran

-   **Owner**: Akses penuh ke manajemen outlet dan semua fitur
-   **Admin**: Akses manajemen kecuali pengaturan outlet kritis
-   **Staff**: Akses terbatas ke operasi harian

### Struktur Izin

```json
{
    "orders": ["view", "create", "update", "delete"],
    "payments": ["view", "process"],
    "reports": ["view", "export"],
    "settings": ["view", "update"]
}
```

### Scoping Data

-   Semua data di-scope berdasarkan `outlet_id`
-   User hanya bisa mengakses data untuk outlet yang ditugaskan
-   Akses data lintas outlet dicegah di level query

## Pelaporan

Sistem menyediakan kemampuan pelaporan yang komprehensif:

| Jenis Laporan            | Deskripsi                    | Metrik                       |
| ------------------------ | ---------------------------- | ---------------------------- |
| **Pesanan Masuk**        | Pesanan baru yang diterima   | Jumlah, pendapatan           |
| **Jatuh Tempo Hari Ini** | Pesanan jatuh tempo hari ini | Jumlah, terlambat            |
| **Pesanan Terlambat**    | Pesanan yang terlambat       | Jumlah, hari terlambat       |
| **Pendapatan**           | Nilai pesanan                | Total, rata-rata             |
| **Pembayaran**           | Pembayaran aktual diterima   | Jumlah, metode               |
| **Pengeluaran**          | Biaya operasional            | Kategori, total              |
| **Item Diambil**         | Pengambilan pelanggan        | Jumlah, tingkat penyelesaian |

Laporan dapat difilter berdasarkan:

-   Rentang tanggal
-   Status pesanan
-   Status pembayaran
-   Jenis layanan
-   Anggota staff

## Pengujian

### Menjalankan Tests

```bash
# Jalankan semua tests
./vendor/bin/phpunit

# Jalankan test suite tertentu
./vendor/bin/phpunit tests/Feature/
./vendor/bin/phpunit tests/Unit/

# Jalankan dengan coverage (memerlukan Xdebug)
./vendor/bin/phpunit --coverage-html coverage/
```

### Cakupan Test

-   **Feature Tests**: Pengujian endpoint API dengan autentikasi
-   **Unit Tests**: Validasi logika bisnis
-   **Integration Tests**: Alur pesanan dan pemrosesan pembayaran

Area test utama:

-   Autentikasi & otorisasi
-   Pembuatan pesanan dan transisi status
-   Validasi pemrosesan pembayaran
-   Generasi nomor invoice
-   Scoping data dan izin

## Dokumentasi API

### Koleksi Postman

Import koleksi dan environment Postman untuk pengujian API yang mudah:

-   **Koleksi**: `postman/Laundry-API-v1.postman_collection.json`
-   **Environment**: `postman/Laundry-API-Local.postman_environment.json`

### Spesifikasi OpenAPI

Spesifikasi API lengkap tersedia di:

-   **File**: `docs/openapi.v1.yaml`
-   **Swagger UI**: `http://localhost:8000/api/documentation` (jika diaktifkan)

### Panduan Integrasi

Instruksi integrasi detail dan contoh:

-   **Panduan**: `docs/integration-guide.md`
-   **Aplikasi Mobile**: `docs/mobile-integration.md`
-   **Webhooks**: `docs/webhooks.md`

## Deployment Checklist

### Environment Configuration

```bash
# Production environment
APP_ENV=production
APP_DEBUG=false
APP_KEY=your-32-character-random-string

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=your-production-db

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
```

### Performance Optimization

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### Keamanan & Monitoring

-   [ ] Konfigurasi pengaturan CORS untuk domain Anda
-   [ ] Aktifkan rate limiting untuk endpoint auth
-   [ ] Setup log rotation dan monitoring
-   [ ] Konfigurasi backup database otomatis
-   [ ] Aktifkan queue workers untuk pemrosesan background
-   [ ] Setup cron jobs untuk Laravel scheduler

### Queue & Scheduler

```bash
# Tambahkan ke crontab
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1

# Jalankan queue worker
php artisan queue:work --daemon
```

### Backup Database

```bash
# Script backup otomatis
php artisan backup:run --only-db
```

## Kontribusi

1. Fork repository
2. Buat feature branch Anda (`git checkout -b feature/fitur-menakjubkan`)
3. Commit perubahan Anda (`git commit -m 'Tambah fitur menakjubkan'`)
4. Push ke branch (`git push origin feature/fitur-menakjubkan`)
5. Buka Pull Request

### Standar Development

-   Ikuti standar coding PSR-12
-   Tulis tests untuk fitur baru
-   Update dokumentasi untuk perubahan API
-   Gunakan conventional commit messages

## Lisensi

Proyek ini adalah perangkat lunak proprietary. Semua hak dilindungi.

---

**Versi API**: v1  
**Versi Laravel**: 12.x  
**Versi PHP**: 8.3+  
**Terakhir Diperbarui**: Agustus 2025

Untuk dukungan teknis atau pertanyaan, silakan hubungi tim development.
