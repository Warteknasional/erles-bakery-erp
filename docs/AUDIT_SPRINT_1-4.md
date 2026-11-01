# AUDIT TEKNIS SPRINT 1–4: MICRO ERP ERLES BAKERY

> **Peran:** Auditor Teknis Independen  
> **Tanggal Audit:** 01 November 2026  
> **Metode Pemeriksaan:** Static Code Analysis, Route Verification (`php artisan route:list`), Database Schema & Relations Inspection, Automated Feature Testing (`php artisan test`), dan Docker Runtime Verification.  
> **Komitmen:** Hanya memeriksa berbasis bukti kode nyata (`file:line`), tanpa mengubah/menambah kode program.

---

## 1. Acuan Sprint & Konteks Audit

Berdasarkan pemeriksaan menyeluruh pada repositori, **Backlog sprint resmi (Sprint 1–4) tidak ditemukan** dalam bentuk dokumen terpisah (`sprint.md`, issue roadmap, maupun project plan).

Sesuai instruksi audit, daftar fitur diturunkan dari batasan scope inti perkuliahan PTT dan ditandai sebagai **ASUMSI**:
- **Scope Inti:** Modul Pemesanan (Order) dan Keuangan (Finance).
- **Scope Opsional:** Modul Stok Sederhana.
- **Stretch Goal:** Bot WhatsApp (WAHA).
- **Keputusan Arsitektur:** Notifikasi owner/admin dikirim via Telegram Bot; WhatsApp diperuntukkan bagi pelanggan yang memesan via WA.
- **Arsitektur Sistem:** Backend Laravel 11 API-only + Sanctum, Database PostgreSQL lokal (Docker), Frontend React (Submodule Admin & Submodule Publik).

---

## 2. Ringkasan Kesesuaian per Sprint

| Sprint | Fokus Fitur (ASUMSI) | Total Item | Selesai | Parsial | Belum | Menyimpang | Persentase Selesai |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **Sprint 1** | Fondasi Docker, Auth Sanctum & Master Produk/Katalog | 4 | 4 | 0 | 0 | 0 | **100%** |
| **Sprint 2** | Modul Pelanggan & Alur Pemesanan Inti (Order Lifecycle) | 4 | 2 | 2 | 0 | 0 | **50%** |
| **Sprint 3** | Modul Pembayaran & Keuangan Inti (Finance & Report) | 3 | 3 | 0 | 0 | 0 | **100%** |
| **Sprint 4** | Integrasi Notifikasi Telegram, WA, Stok & QA | 4 | 1 | 2 | 1 | 1 | **25%** |
| **TOTAL** | **Keseluruhan Proyek (Sprint 1–4)** | **15** | **10** | **4** | **1** | **1** | **66.7%** |

*Catatan: Item "Stok Bahan" berstatus ganda (Parsial sekaligus Menyimpang) karena ada backend penyesuaian stok produk jadi, namun UI admin menyajikan bahan baku mentah berbasis data tiruan/dummy.*

---

## 3. Tabel Detail Bukti per Item (File:Line)

### Sprint 1: Fondasi Docker, Autentikasi, & Manajemen Produk/Katalog

| Item Fitur | Status | Bukti Kode (`file:line`) | Catatan Auditor |
| :--- | :---: | :--- | :--- |
| **Container & Environment Docker Compose** | **SELESAI** | - [`docker-compose.yml:1-83`](file:///d:/PTT/erles-bakery-erp/docker-compose.yml#L1-L83)<br>- [`.env.example:1-25`](file:///d:/PTT/erles-bakery-erp/.env.example#L1-L25)<br>- [`backend/.env.example:1-48`](file:///d:/PTT/erles-bakery-erp/backend/.env.example#L1-L48) | Service `db` (Postgres 16), `backend`, `admin`, dan `public` terkonfigurasi. Parameter `stdin_open: true` dan `tty: true` sudah dipasang pada Vite container untuk mencegah exit container. |
| **Autentikasi Sanctum & Role-Based Access** | **SELESAI** | - Migration: [`backend/database/migrations/0001_01_01_000000_create_users_table.php:13-33`](file:///d:/PTT/erles-bakery-erp/backend/database/migrations/0001_01_01_000000_create_users_table.php#L13-L33)<br>- Model: [`backend/app/Models/User.php:28-56`](file:///d:/PTT/erles-bakery-erp/backend/app/Models/User.php#L28-L56)<br>- Middleware: [`backend/app/Http/Middleware/EnsureUserHasRole.php:14-38`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Middleware/EnsureUserHasRole.php#L14-L38)<br>- Controller: [`backend/app/Http/Controllers/AuthController.php:17-48`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/AuthController.php#L17-L48)<br>- UI Admin: [`admin/src/pages/LoginPage.jsx:8-60`](file:///d:/PTT/erles-bakery-erp/admin/src/pages/LoginPage.jsx#L8-L60)<br>- UI Admin Context: [`admin/src/context/AuthContext.jsx:25-54`](file:///d:/PTT/erles-bakery-erp/admin/src/context/AuthContext.jsx#L25-L54)<br>- Test: [`backend/tests/Feature/AuthTest.php:17-104`](file:///d:/PTT/erles-bakery-erp/backend/tests/Feature/AuthTest.php#L17-L104) | Login Sanctum bearer token, rute terproteksi `/login`, `/me`, `/logout`. Role `admin` dan `staff` diuji 7 test otomatis (lulus 100%). Token tersimpan di `localStorage` admin. |
| **Master Kategori Produk** | **SELESAI** | - Migration: [`backend/database/migrations/2024_01_02_000001_create_categories_table.php:13-22`](file:///d:/PTT/erles-bakery-erp/backend/database/migrations/2024_01_02_000001_create_categories_table.php#L13-L22)<br>- Model: [`backend/app/Models/Category.php:9-24`](file:///d:/PTT/erles-bakery-erp/backend/app/Models/Category.php#L9-L24)<br>- Route: [`backend/routes/api.php:33-47`](file:///d:/PTT/erles-bakery-erp/backend/routes/api.php#L33-L47)<br>- Controller: [`backend/app/Http/Controllers/CategoryController.php:17-88`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/CategoryController.php#L17-L88)<br>- UI Admin: [`admin/src/pages/ProdukPage.jsx:481`](file:///d:/PTT/erles-bakery-erp/admin/src/pages/ProdukPage.jsx#L481)<br>- Test: [`backend/tests/Feature/ProductApiTest.php:111-140`](file:///d:/PTT/erles-bakery-erp/backend/tests/Feature/ProductApiTest.php#L111-L140) | CRUD Kategori lengkap, proteksi pencegahan penghapusan kategori yang masih terikat produk, pemanggilan API di dropdown modal produk. |
| **Katalog & Master Produk (Admin & Publik)** | **SELESAI** | - Migration: [`backend/database/migrations/2024_01_01_000002_create_products_table.php:13-28`](file:///d:/PTT/erles-bakery-erp/backend/database/migrations/2024_01_01_000002_create_products_table.php#L13-L28)<br>- Model: [`backend/app/Models/Product.php:11-53`](file:///d:/PTT/erles-bakery-erp/backend/app/Models/Product.php#L11-L53)<br>- Route: [`backend/routes/api.php:18-21`](file:///d:/PTT/erles-bakery-erp/backend/routes/api.php#L18-L21), [`backend/routes/api.php:40-44`](file:///d:/PTT/erles-bakery-erp/backend/routes/api.php#L40-L44)<br>- Controller: [`backend/app/Http/Controllers/ProductController.php:24-143`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/ProductController.php#L24-L143)<br>- UI Admin: [`admin/src/pages/ProdukPage.jsx:479-560`](file:///d:/PTT/erles-bakery-erp/admin/src/pages/ProdukPage.jsx#L479-L560)<br>- UI Publik: [`public/src/pages/PesananPage.jsx:318-353`](file:///d:/PTT/erles-bakery-erp/public/src/pages/PesananPage.jsx#L318-L353)<br>- Test: [`backend/tests/Feature/ProductApiTest.php:17-110`](file:///d:/PTT/erles-bakery-erp/backend/tests/Feature/ProductApiTest.php#L17-L110) | Publik read-only hanya menampilkan `is_active=true`. Admin dapat melakukan CRUD produk, filter kategori, dan melihat seluruh status produk dengan `is_active=all`. |

---

### Sprint 2: Modul Pelanggan & Pemesanan Inti (Order Lifecycle)

| Item Fitur | Status | Bukti Kode (`file:line`) | Catatan Auditor |
| :--- | :---: | :--- | :--- |
| **Modul Pelanggan (Customer Management & Auto-create)** | **PARSIAL** | - Migration: [`backend/database/migrations/2024_01_02_000002_create_customers_table.php:13-26`](file:///d:/PTT/erles-bakery-erp/backend/database/migrations/2024_01_02_000002_create_customers_table.php#L13-L26)<br>- Model: [`backend/app/Models/Customer.php:10-53`](file:///d:/PTT/erles-bakery-erp/backend/app/Models/Customer.php#L10-L53)<br>- Route: [`backend/routes/api.php:57-63`](file:///d:/PTT/erles-bakery-erp/backend/routes/api.php#L57-L63)<br>- Auto-create Order: [`backend/app/Http/Controllers/OrderController.php:72-76`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/OrderController.php#L72-L76)<br>- Test: [`backend/tests/Feature/CustomerApiTest.php:17-64`](file:///d:/PTT/erles-bakery-erp/backend/tests/Feature/CustomerApiTest.php#L17-L64) | **Kekurangan:** Backend memiliki endpoint CRUD pelanggan lengkap dan auto-create pelanggan dari pesanan publik. Namun **Frontend Admin tidak memiliki halaman/menu Pelanggan** (`CustomerPage.jsx` tidak ada). Data pelanggan hanya terlihat menempel pada tabel pesanan. |
| **Pembuatan Pesanan Publik & Kalkulasi Server-Side** | **SELESAI** | - Migration: [`backend/database/migrations/2024_01_01_000003_create_orders_table.php:13-33`](file:///d:/PTT/erles-bakery-erp/backend/database/migrations/2024_01_01_000003_create_orders_table.php#L13-L33)<br>- Model: [`backend/app/Models/Order.php:14-118`](file:///d:/PTT/erles-bakery-erp/backend/app/Models/Order.php#L14-L118)<br>- Request: [`backend/app/Http/Requests/OrderStoreRequest.php:14-40`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Requests/OrderStoreRequest.php#L14-L40)<br>- Controller: [`backend/app/Http/Controllers/OrderController.php:64-126`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/OrderController.php#L64-L126)<br>- UI Publik: [`public/src/pages/PesananPage.jsx:410-442`](file:///d:/PTT/erles-bakery-erp/public/src/pages/PesananPage.jsx#L410-L442)<br>- Test: [`backend/tests/Feature/OrderApiTest.php:17-46`](file:///d:/PTT/erles-bakery-erp/backend/tests/Feature/OrderApiTest.php#L17-L46) | Perhitungan total harga dilakukan di backend (anti manipulasi harga client), kode pesanan otomatis format `ERL-YYYYMMDD-XXXX`, DB Transaction membungkus insert order + order items. |
| **Pelacakan Pesanan Mandiri (Tracking)** | **PARSIAL** | - Route: [`backend/routes/api.php:19`](file:///d:/PTT/erles-bakery-erp/backend/routes/api.php#L19)<br>- Controller: [`backend/app/Http/Controllers/OrderController.php:131-145`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/OrderController.php#L131-L145)<br>- Service Publik: [`public/src/services/api.js:28-31`](file:///d:/PTT/erles-bakery-erp/public/src/services/api.js#L28-L31) | **Kekurangan:** Endpoint `GET /api/orders/track/{idOrCode}` dan fungsi JS `orderService.track()` tersedia. Namun pada UI publik (`public/src/pages`), tidak ada halaman atau form input "Cek Resi / Lacak Pesanan" mandiri untuk pelanggan yang kembali. |
| **Alur Status Pesanan & Validasi Transisi** | **SELESAI** | - Model Transisi: [`backend/app/Models/Order.php:49-86`](file:///d:/PTT/erles-bakery-erp/backend/app/Models/Order.php#L49-L86)<br>- Route: [`backend/routes/api.php:51-53`](file:///d:/PTT/erles-bakery-erp/backend/routes/api.php#L51-L53)<br>- Controller: [`backend/app/Http/Controllers/OrderController.php:150-220`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/OrderController.php#L150-L220)<br>- UI Admin: [`admin/src/pages/PesananPage.jsx:476-485`](file:///d:/PTT/erles-bakery-erp/admin/src/pages/PesananPage.jsx#L476-L485)<br>- Test: [`backend/tests/Feature/OrderApiTest.php:75-103`](file:///d:/PTT/erles-bakery-erp/backend/tests/Feature/OrderApiTest.php#L75-L103) | Alur resmi: `pending` -> `confirmed` -> `processing` -> `ready` -> `completed` / `cancelled`. Percobaan lompat status ilegal ditolak backend dengan HTTP 422. Admin UI menyediakan modal konfirmasi perubahan status. |

---

### Sprint 3: Modul Pembayaran & Keuangan Inti (Finance & Report)

| Item Fitur | Status | Bukti Kode (`file:line`) | Catatan Auditor |
| :--- | :---: | :--- | :--- |
| **Pencatatan Pembayaran Pesanan (DP/Lunas)** | **SELESAI** | - Migration: [`backend/database/migrations/2024_01_03_000001_create_payments_table.php:13-28`](file:///d:/PTT/erles-bakery-erp/backend/database/migrations/2024_01_03_000001_create_payments_table.php#L13-L28)<br>- Model: [`backend/app/Models/Payment.php:11-53`](file:///d:/PTT/erles-bakery-erp/backend/app/Models/Payment.php#L11-L53)<br>- Route: [`backend/routes/api.php:70-75`](file:///d:/PTT/erles-bakery-erp/backend/routes/api.php#L70-L75)<br>- Controller: [`backend/app/Http/Controllers/PaymentController.php:74-160`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/PaymentController.php#L74-L160)<br>- UI Admin: [`admin/src/pages/PesananPage.jsx:68-76`](file:///d:/PTT/erles-bakery-erp/admin/src/pages/PesananPage.jsx#L68-L76)<br>- Test: [`backend/tests/Feature/PaymentApiTest.php:17-128`](file:///d:/PTT/erles-bakery-erp/backend/tests/Feature/PaymentApiTest.php#L17-L128) | Mendukung pencatatan DP, cicilan, dan pelunasan. Otomatis menghitung akumulasi `paid_amount` dan memperbarui `payment_status` (`unpaid`, `partial`, `paid`). Validasi menolak nominal pembayaran yang melebihi sisa tagihan. |
| **Integrasi Otomatis Pemasukan & Pengeluaran Kas** | **SELESAI** | - Migration: [`backend/database/migrations/2024_01_01_000005_create_finance_transactions_table.php:13-28`](file:///d:/PTT/erles-bakery-erp/backend/database/migrations/2024_01_01_000005_create_finance_transactions_table.php#L13-L28)<br>- Model: [`backend/app/Models/FinanceTransaction.php:10-53`](file:///d:/PTT/erles-bakery-erp/backend/app/Models/FinanceTransaction.php#L10-L53)<br>- Auto-pemasukan: [`backend/app/Http/Controllers/PaymentController.php:140-149`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/PaymentController.php#L140-L149)<br>- Route: [`backend/routes/api.php:79-87`](file:///d:/PTT/erles-bakery-erp/backend/routes/api.php#L79-L87)<br>- Controller: [`backend/app/Http/Controllers/FinanceController.php:23-95`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/FinanceController.php#L23-L95)<br>- UI Admin: [`admin/src/pages/KeuanganPage.jsx:296-348`](file:///d:/PTT/erles-bakery-erp/admin/src/pages/KeuanganPage.jsx#L296-L348)<br>- Test: [`backend/tests/Feature/FinanceApiTest.php:17-106`](file:///d:/PTT/erles-bakery-erp/backend/tests/Feature/FinanceApiTest.php#L17-L106) | Setiap pembayaran pesanan otomatis membuat record `pemasukan` pada `FinanceTransaction`. Admin dapat mencatat pengeluaran operasional manual (kategori, nominal, catatan) dan menghapus transaksi dengan otorisasi role. |
| **Laporan Ringkasan Omzet & Dashboard Analitik** | **SELESAI** | - Route: [`backend/routes/api.php:80`](file:///d:/PTT/erles-bakery-erp/backend/routes/api.php#L80), [`backend/routes/api.php:90`](file:///d:/PTT/erles-bakery-erp/backend/routes/api.php#L90)<br>- Controller: [`backend/app/Http/Controllers/FinanceController.php:98-154`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/FinanceController.php#L98-L154), [`backend/app/Http/Controllers/DashboardController.php:18-120`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/DashboardController.php#L18-L120)<br>- UI Dashboard: [`admin/src/pages/DashboardPage.jsx:10`](file:///d:/PTT/erles-bakery-erp/admin/src/pages/DashboardPage.jsx#L10)<br>- UI Keuangan: [`admin/src/pages/KeuanganPage.jsx:297`](file:///d:/PTT/erles-bakery-erp/admin/src/pages/KeuanganPage.jsx#L297)<br>- Test: [`backend/tests/Feature/FinanceApiTest.php:67-79`](file:///d:/PTT/erles-bakery-erp/backend/tests/Feature/FinanceApiTest.php#L67-L79) | Endpoint `/api/dashboard` dan `/api/finance/summary` menghitung omzet harian, bulanan, total pengeluaran, laba bersih, tren grafik 7 hari, dan produk terlaris secara real-time dari database. |

---

### Sprint 4: Integrasi Notifikasi Telegram, WhatsApp, Stok & QA

| Item Fitur | Status | Bukti Kode (`file:line`) | Catatan Auditor |
| :--- | :---: | :--- | :--- |
| **Notifikasi Telegram ke Owner/Admin (Keputusan)** | **BELUM** | *Tidak ada file di codebase* | **Gap Kritis:** Tidak ditemukan integrasi Telegram bot, webhook, notification channel, maupun konfigurasi `TELEGRAM_BOT_TOKEN` di seluruh codebase. Ketika pesanan baru masuk atau status berubah, owner tidak menerima notifikasi Telegram. |
| **WhatsApp Link & WAHA (Stretch Goal)** | **PARSIAL** | - UI Floating WA: [`public/src/components/FloatingWhatsApp.tsx:1-40`](file:///d:/PTT/erles-bakery-erp/public/src/components/FloatingWhatsApp.tsx#L1-L40)<br>- UI Pesanan WA: [`public/src/pages/PesananPage.jsx:455-470`](file:///d:/PTT/erles-bakery-erp/public/src/pages/PesananPage.jsx#L455-L470) | Frontend publik menyediakan tautan langsung WhatsApp (`wa.me`) dengan pre-filled text struk pesanan. Server bot WAHA otomatis ditiadakan di docker-compose (sesuai status stretch goal). |
| **Modul Penyesuaian Stok (Opsional)** | **MENYIMPANG / PARSIAL** | - Migration Produk: [`backend/database/migrations/2024_01_01_000002_create_products_table.php:19`](file:///d:/PTT/erles-bakery-erp/backend/database/migrations/2024_01_01_000002_create_products_table.php#L19)<br>- Route Backend: [`backend/routes/api.php:45`](file:///d:/PTT/erles-bakery-erp/backend/routes/api.php#L45)<br>- Controller: [`backend/app/Http/Controllers/ProductController.php:145-177`](file:///d:/PTT/erles-bakery-erp/backend/app/Http/Controllers/ProductController.php#L145-L177)<br>- UI Admin: [`admin/src/pages/StokBahanPage.jsx:9-40`](file:///d:/PTT/erles-bakery-erp/admin/src/pages/StokBahanPage.jsx#L9-L40)<br>- Test: [`backend/tests/Feature/ProductApiTest.php:142-168`](file:///d:/PTT/erles-bakery-erp/backend/tests/Feature/ProductApiTest.php#L142-L168) | **Penyimpangan:** Backend hanya menyediakan penyesuaian stok produk jadi (`adjust-stock` pada produk). Namun di Admin UI, terdapat halaman sidebar `/stok-bahan` (`StokBahanPage.jsx`) yang menyajikan stok bahan baku mentah (tepung, gula, butter) menggunakan **100% data tiruan (dummy data `initialIngredients`)** tanpa integrasi backend. |
| **End-to-End QA & Dokumentasi API** | **SELESAI** | - Dokumen API: [`docs/API.md:1-250`](file:///d:/PTT/erles-bakery-erp/docs/API.md#L1-L250)<br>- Suite Test: [`backend/tests/Feature/`](file:///d:/PTT/erles-bakery-erp/backend/tests/Feature) (39 feature tests) | Dokumentasi kontrak API lengkap mencakup seluruh endpoint. Pengujian otomatis backend mencapai **39 test passed (398 assertions)** dengan 0 failure. |

---

## 4. Hasil Pemeriksaan Lintas Sprint (Cross-Cutting Concerns)

1. **Konsistensi Endpoint & Field Payload:**
   - Seluruh pemanggilan dari service frontend admin ([`admin/src/services/`](file:///d:/PTT/erles-bakery-erp/admin/src/services)) dan publik ([`public/src/services/api.js`](file:///d:/PTT/erles-bakery-erp/public/src/services/api.js)) sesuai dengan rute backend Laravel. Tidak ditemukan endpoint 404 pada alur operasional utama.
   - Response structure seragam berkat trait [`ApiResponse.php`](file:///d:/PTT/erles-bakery-erp/backend/app/Traits/ApiResponse.php) (`success`, `message`, `data`, `meta`).

2. **Verifikasi Alur End-to-End:**
   - Alur: **Pesanan Publik Dibuat** -> **Tercatat di Database** -> **Muncul di Admin Pesanan** -> **Admin Update Status / Catat Pembayaran** -> **Otomatis Masuk Transaksi Keuangan (Pemasukan)** berjalan 100% mulus di level database dan API.
   - **Putus di tahap akhir:** Tahap notifikasi Telegram ke owner/admin saat pesanan baru masuk belum terjadi karena belum ada modul bot Telegram.

3. **Keamanan & Kredensial:**
   - Tidak ada hardcoded credentials atau token produksi di git repository.
   - File `.env` tidak ter-commit (hanya `.env.example`).
   - Seeder menggunakan hash default aman untuk lingkungan lokal (`bcrypt('password')`).
   - Seluruh endpoint mutasi admin diproteksi middleware Sanctum dan otorisasi role (`admin`, `staff`).

---

## 5. Daftar Gap Prioritas (Action Plan)

| Prioritas | Deskripsi Gap | Dampak & Keterangan | Estimasi Usaha | Tanggung Jawab Repo |
| :---: | :--- | :--- | :---: | :--- |
| **P0** | **Belum ada Notifikasi Telegram ke Owner** | **Blocker Demo Scope.** Keputusan proyek mewajibkan notifikasi order masuk ke Telegram owner. Saat ini belum ada bot Telegram di backend. | **M (Medium)** | `erles-bakery-erp` (`backend`) |
| **P0** | **Halaman Stok Bahan di Admin 100% Mock / Dummy** | **Resiko Demo Ditandai Tidak Selesai.** Menu `/stok-bahan` menampilkan tabel bahan baku yang tidak tersambung ke database. Jika dosen menguji tambah/edit stok bahan, data tidak tersimpan ke server. Harus diputuskan: hapus menu ini atau buatkan model/tabel bahan baku. | **S (Small)** jika disembunyikan / **L (Large)** jika dibuatkan full stack | `erles-bakery-admin` |
| **P1** | **Tidak ada Halaman Manajemen Pelanggan di Admin** | **Penting.** Backend memiliki `GET/POST/PUT/DELETE /api/customers`, tetapi di Admin UI tidak ada menu Pelanggan untuk melihat riwayat belanja customer atau loyalitas pelanggan. | **M (Medium)** | `erles-bakery-admin` |
| **P1** | **Form Lacak Pesanan Mandiri di Web Publik** | **Penting.** Backend menyediakan endpoint `GET /api/orders/track/{code}`, tetapi di web publik belum ada input box "Lacak Pesanan Saya" bagi pembeli yang ingin cek status secara langsung. | **S (Small)** | `erles-bakery-publik` |
| **P2** | **Otomatisasi Bot WhatsApp (WAHA)** | **Stretch Goal.** Menggantikan tombol direct link `wa.me` dengan integrasi bot WhatsApp otomatis (sesuai status stretch goal opsional). | **L (Large)** | `erles-bakery-erp` (`backend`) |

---

## 6. Risiko untuk Demo / Penilaian Dosen

1. **Risiko Verifikasi Notifikasi Realtime:**
   Jika dosen meminta: *"Coba checkout pesanan baru, apakah ada notifikasi masuk ke Telegram owner?"*, sistem saat ini belum bisa menampilkannya karena integrasi Telegram belum dibangun.
2. **Risiko Pengecekan Menu Stok Bahan Baku:**
   Jika dosen membuka sidebar Admin menu **"Stok Bahan"**, data yang tampil adalah data statis JavaScript (`initialIngredients`). Perubahan data di halaman tersebut akan hilang saat browser di-refresh.
3. **Risiko Pertanyaan Modul Pelanggan:**
   Dosen mungkin menanyakan di mana daftar master pelanggan berada di aplikasi Admin. Karena belum ada halaman antarmuka pelanggan, admin hanya bisa melihat nama pelanggan melalui tabel pesanan.

