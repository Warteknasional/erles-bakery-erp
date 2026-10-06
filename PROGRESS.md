# Progress Backend Micro ERP Erles Bakery

Status implementasi backend Laravel API-only untuk Erles Bakery ERP.

---

## 1. Audit Kondisi Awal (Langkah 1) - [x] Done

Hasil audit struktur repositori, migrasi, model, rute, kontroler, dan pengujian per 06 Oktober 2026:

| Modul / Komponen | Status | Catatan Audit & Kondisi Saat Ini |
| :--- | :---: | :--- |
| **1. Audit Kondisi** | **DONE** | Seluruh struktur kode, rute, dan migrasi terverifikasi; 22 unit & feature test hijau (285 assertions). |
| **2. Fondasi Sistem** | **PARTIAL** | Docker Compose untuk `db` (Postgres 16) & `backend` berjalan. CORS mengizinkan port 5173 & 5174. Format response `ApiResponse` sudah ada. Error handler di `bootstrap/app.php` sudah menangani validasi/auth/404. *Kekurangan:* Belum ada rate limiting khusus endpoint publik; `.env.example` perlu dibersihkan sepenuhnya dari ketergantungan Supabase. |
| **3. Autentikasi & Role** | **PARTIAL** | Login, logout, me via Sanctum token Bearer sudah ada di `AuthController`. User model memiliki kolom role (`admin`, `karyawan`). *Kekurangan:* Belum ada role `staff`/`karyawan` eksplisit di seeder dan belum ada middleware otorisasi per role (`admin`, `staff`). |
| **4. Produk & Kategori** | **PARTIAL** | CRUD produk lengkap di `ProductController`. Endpoint katalog publik (`/api/products`, `/api/products/{idOrSlug}`) sudah read-only. *Kekurangan:* Kategori saat ini hanya berupa kolom string pada tabel `products`; belum ada CRUD / master kategori terpisah. |
| **5. Pelanggan (Customer)**| **BELUM** | Data pemesan saat ini disimpan langsung di kolom `customer_name` dan `customer_phone` pada tabel `orders`. Belum ada tabel `customers`, model `Customer`, CRUD pelanggan, maupun auto-create pelanggan dari pemesanan publik. |
| **6. Pesanan (Order)** | **PARTIAL** | Endpoint publik buat pesanan (`POST /api/orders`) sudah menghitung total di server dan membuat kode pesanan unik. Lacak pesanan (`GET /api/orders/track/{idOrCode}`) sudah ada. Admin list/filter/detail/status sudah ada. *Kekurangan:* Status order masih menggunakan `['pending', 'diproses', 'selesai', 'dibatalkan']`, sedangkan spesifikasi memerlukan transisi resmi: `['pending', 'confirmed', 'processing', 'ready', 'completed', 'cancelled']` dengan penolakan transisi status yang tidak valid serta integrasi relasi ke `Customer`. |
| **7. Pembayaran (Payment)**| **BELUM** | Belum ada tabel/model `payments` untuk mencatat pembayaran pesanan (DP / Lunas, metode pembayaran, tanggal, referensi). Belum ada kolom tracking `payment_status` (`unpaid`, `partial`, `paid`) dan validasi agar pembayaran tidak melebihi total pesanan. |
| **8. Keuangan & Dashboard**| **PARTIAL** | Transaksi keuangan `finance_transactions` mendukung pemasukan & pengeluaran. Ringkasan umum ada di `FinanceController@summary`. *Kekurangan:* Pemasukan belum otomatis sinkron dari modul pembayaran pesanan; belum ada filter/agregasi harian & bulanan (omzet, pengeluaran, laba); belum ada endpoint ringkasan khusus `/api/dashboard`. |
| **9. Stok Sederhana** | **BELUM** | Opsional: modul pengurangan/penyesuaian stok bahan belum diimplementasikan (akan dikerjakan terakhir setelah 1-8 stabil). |
| **10. Kualitas & Pengujian**| **PARTIAL** | FormRequest dan API Resource sudah digunakan di sebagian modul. Seeder ada untuk admin dan 15 produk. Dokumentasi awal ada di `API.md`. *Kekurangan:* Perlu diperluas untuk modul customer, payment, category, middleware role, dan dashboard. |
| **11. Docker & Portabilitas**| **PARTIAL** | Docker Compose sudah ada. Perlu memastikan startup bersih dari nol tanpa konfigurasi Supabase dan file `.env.example` baku. |

---

## Rencana Eksekusi Berurutan (1 Langkah = 1 Commit)

1. [x] **Langkah 1: Audit Kondisi** - Dokumentasi status modul di `PROGRESS.md`.
2. [x] **Langkah 2: Fondasi** - Konfigurasi CORS (dukung localhost & 127.0.0.1 untuk port 5173 & 5174), error handler JSON seragam (401, 403, 404, 405, 422, 429), rate limiter bernama (login, public-api, public-order), dan pembersihan .env.example murni Postgres lokal tanpa Supabase.
3. [x] **Langkah 3: Auth & Role** - Autentikasi Sanctum (login, logout, me), helper role (`isAdmin()`, `isStaff()`, `hasRole()`), middleware `EnsureUserHasRole` (`role:admin`, `role:admin,staff`), penanganan 403 Forbidden, dan pengujian otorisasi peran.
4. [x] **Langkah 4: Produk & Kategori** - Tabel/migrasi `categories` baru, model `Category`, relasi `Category` <-> `Product`, sinkronisasi kategori nama & category_id, FormRequest (`CategoryStoreRequest`, `CategoryUpdateRequest`), `CategoryResource`, `CategoryController` (CRUD kategori, cegah hapus kategori berelasi), serta endpoint katalog publik read-only.
5. [ ] **Langkah 5: Pelanggan** - Tabel/model `Customer`, auto-create / sync saat pesanan publik masuk, CRUD pelanggan admin.
6. [ ] **Langkah 6: Pesanan** - Alur status order lengkap (`pending`, `confirmed`, `processing`, `ready`, `completed`, `cancelled`), validasi transisi alur, pembatalan, kalkulasi server, DB transactions.
7. [ ] **Langkah 7: Pembayaran** - Model `Payment`, pencatatan pembayaran pesanan (DP/lunas), auto update `payment_status` (`unpaid`, `partial`, `paid`), validasi nominal <= total.
8. [ ] **Langkah 8: Keuangan & Dashboard** - Pemasukan otomatis dari `Payment`, pengeluaran manual, laporan omzet/pengeluaran/laba harian & bulanan, endpoint `/api/dashboard`.
9. [ ] **Langkah 9: Stok (Opsional)** - Modul stok sederhana jika 1-8 tuntas.
10. [ ] **Langkah 10: Kualitas & Dokumentasi** - Lengkapi FormRequest, API Resource, DatabaseSeeder, Feature Tests per modul, update `docs/API.md`.
11. [ ] **Langkah 11: Uji Docker Dari Nol** - Verifikasi migrasi, seed, dan endpoint siap pakai.
