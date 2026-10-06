# 📖 Erles Bakery ERP — Dokumentasi Lengkap API (Backend)

Dokumentasi resmi RESTful API **Erles Bakery ERP** yang dikonsumsi oleh:
1. **Aplikasi Admin (React)**: Manajemen produk, kategori, pelanggan, pesanan, pembayaran, keuangan, dan dashboard statistik.
2. **Aplikasi Publik (React)**: Katalog produk & kategori, pemesanan publik tanpa login (kalkulasi harga aman di server), dan pelacakan status pesanan.

---

## 🌐 Konfigurasi Dasar & Konvensi

- **Base URL**: `http://localhost:8000/api`
- **Format Payload**: JSON (`application/json`)
- **Headers Wajib**:
  ```http
  Accept: application/json
  Content-Type: application/json
  ```
- **Protected Endpoints**: Menggunakan Laravel Sanctum Bearer Token:
  ```http
  Authorization: Bearer <token_anda>
  ```

---

## 🛡️ Format Respon Standar

### 1. Respon Sukses (Standard)
```json
{
  "success": true,
  "message": "Pesan keberhasilan",
  "data": { ... },
  "meta": { ... } // opsional
}
```

### 2. Respon Paginasi
```json
{
  "success": true,
  "message": "Daftar data berhasil diambil.",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 4,
    "per_page": 15,
    "total": 52
  }
}
```

### 3. Respon Error & Validasi (422 Unprocessable Entity)
```json
{
  "success": false,
  "message": "Validasi gagal.",
  "errors": {
    "nama": ["Nama produk wajib diisi."],
    "harga": ["Harga produk minimal Rp 100."]
  }
}
```

### 4. Respon Error Otorisasi (401 / 403)
```json
{
  "success": false,
  "message": "Unauthenticated. Silakan login terlebih dahulu."
}
```
```json
{
  "success": false,
  "message": "Akses ditolak. Anda tidak memiliki izin untuk tindakan ini."
}
```

---

## 🔐 1. Autentikasi & Akun Pengguna

### Login Admin / Staff
- **Method**: `POST`
- **URL**: `/api/login`
- **Rate Limit**: 10 request / menit
- **Body**:
```json
{
  "email": "admin@erlesbakery.com",
  "password": "password"
}
```
- **Respon Sukses (200)**:
```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin Erles",
      "email": "admin@erlesbakery.com",
      "phone": "081234567890",
      "role": "admin"
    },
    "token": "1|s1oP8QWbM8X9vYv0...",
    "token_type": "Bearer"
  }
}
```

### Profil Pengguna (Me)
- **Method**: `GET`
- **URL**: `/api/me`
- **Header**: `Authorization: Bearer <token>`
- **Respon Sukses (200)**:
```json
{
  "success": true,
  "message": "Data profil berhasil diambil.",
  "data": {
    "id": 1,
    "name": "Admin Erles",
    "email": "admin@erlesbakery.com",
    "phone": "081234567890",
    "role": "admin"
  }
}
```

### Logout
- **Method**: `POST`
- **URL**: `/api/logout`
- **Header**: `Authorization: Bearer <token>`
- **Respon Sukses (200)**:
```json
{
  "success": true,
  "message": "Logout berhasil.",
  "data": null
}
```

---

## 🍰 2. Produk & Kategori (Katalog Publik & Admin)

### Daftar Kategori (Publik / Admin)
- **Method**: `GET`
- **URL**: `/api/categories`
- **Query Params**: `with_products=1` (opsional)

### Buat Kategori (Admin & Staff)
- **Method**: `POST`
- **URL**: `/api/categories`
- **Header**: `Authorization: Bearer <token>`
- **Body**:
```json
{
  "name": "Roti Manis",
  "description": "Koleksi aneka roti manis lembut"
}
```

### Hapus Kategori (Admin Only)
- **Method**: `DELETE`
- **URL**: `/api/categories/{id}`
- **Header**: `Authorization: Bearer <token>`
*(Catatan: Kategori yang masih memiliki produk aktif ditolak untuk dihapus demi integritas data).*

### Daftar Produk (Publik & Admin)
- **Method**: `GET`
- **URL**: `/api/products`
- **Query Params**:
  - `search`: cari nama atau deskripsi
  - `kategori`: filter nama kategori (misal `Roti`, `Hampers`, `Kue`)
  - `is_active`: `true` / `false`
  - `per_page`: jumlah per halaman (default 15)
- **Respon Sukses (200)**:
```json
{
  "success": true,
  "message": "Daftar produk berhasil diambil.",
  "data": [
    {
      "id": 1,
      "nama": "Roti Tawar Gandum",
      "slug": "roti-tawar-gandum",
      "kategori": "Roti",
      "category_id": 1,
      "harga": 25000,
      "stok": 50,
      "deskripsi": "Roti tawar gandum utuh, lembut dan sehat.",
      "foto_url": null,
      "is_active": true
    }
  ]
}
```

### Detail Produk
- **Method**: `GET`
- **URL**: `/api/products/{idOrSlug}` (dapat diakses dengan ID integer atau slug URL)

### Tambah Produk (Admin & Staff)
- **Method**: `POST`
- **URL**: `/api/products`
- **Header**: `Authorization: Bearer <token>`
- **Body**:
```json
{
  "nama": "Donat Cokelat Keju",
  "category_id": 1,
  "kategori": "Roti",
  "harga": 12000,
  "stok": 25,
  "deskripsi": "Donat kentang dengan topping cokelat leleh dan keju parut.",
  "foto_url": "https://example.com/donat.jpg",
  "is_active": true
}
```

### Update Produk (Admin & Staff)
- **Method**: `PUT` / `PATCH`
- **URL**: `/api/products/{id}`

### Hapus Produk (Admin Only)
- **Method**: `DELETE`
- **URL**: `/api/products/{id}`

---

## 👥 3. Pelanggan (Customer)

### Daftar Pelanggan
- **Method**: `GET`
- **URL**: `/api/customers`
- **Query Params**: `search`, `sort_by`, `sort_order`, `per_page`
- **Respon Sukses (200)**:
```json
{
  "success": true,
  "message": "Daftar pelanggan berhasil diambil.",
  "data": [
    {
      "id": 1,
      "name": "Budi Santoso",
      "phone": "081298765432",
      "email": "budi@example.com",
      "address": "Jl. Merdeka No. 10, Jakarta Pusat",
      "notes": "Pelanggan setia roti gandum",
      "total_orders": 3,
      "total_spent": 175000,
      "created_at": "2026-10-06T12:00:00.000000Z"
    }
  ]
}
```

### Tambah Pelanggan
- **Method**: `POST`
- **URL**: `/api/customers`
- **Body**:
```json
{
  "name": "Citra Lestari",
  "phone": "085811223344",
  "email": "citra@example.com",
  "address": "Jl. Melati No. 4, Surabaya",
  "notes": "Pesanan khusus gluten-free"
}
```

---

## 🛒 4. Pesanan (Order)

### Buat Pesanan (Publik Tanpa Auth)
- **Method**: `POST`
- **URL**: `/api/orders`
- **Rate Limit**: 15 request / menit
- **Keamanan**: Harga total dan subtotal dihitung otomatis oleh server dari harga produk di database. Pelanggan otomatis terdata di tabel `customers`.
- **Body**:
```json
{
  "customer_name": "Ahmad Fauzi",
  "customer_phone": "081234567890",
  "alamat": "Jl. Kenanga No. 12, Jakarta",
  "tanggal_ambil": "2026-10-15",
  "catatan": "Tolong berikan ucapan 'Selamat Ulang Tahun'",
  "items": [
    { "product_id": 1, "qty": 2 },
    { "product_id": 5, "qty": 1 }
  ]
}
```
- **Respon Sukses (201)**:
```json
{
  "success": true,
  "message": "Pesanan berhasil dibuat.",
  "data": {
    "id": 14,
    "customer_id": 3,
    "kode_pesanan": "ORD-20261006-0001",
    "customer_name": "Ahmad Fauzi",
    "customer_phone": "081234567890",
    "alamat": "Jl. Kenanga No. 12, Jakarta",
    "tanggal_ambil": "2026-10-15",
    "total_price": 95000,
    "status": "pending",
    "payment_status": "unpaid",
    "paid_amount": 0,
    "sisa_pembayaran": 95000,
    "items": [
      {
        "id": 21,
        "product_id": 1,
        "product_name": "Roti Tawar Gandum",
        "qty": 2,
        "unit_price": 25000,
        "subtotal": 50000
      },
      {
        "id": 22,
        "product_id": 5,
        "product_name": "Brownies Panggang",
        "qty": 1,
        "unit_price": 45000,
        "subtotal": 45000
      }
    ]
  }
}
```

### Lacak Pesanan (Publik Tanpa Auth)
- **Method**: `GET`
- **URL**: `/api/orders/track/{idOrCode}`
- **Contoh**: `/api/orders/track/ORD-20261006-0001`

### Daftar Pesanan (Admin & Staff)
- **Method**: `GET`
- **URL**: `/api/orders`
- **Query Params**: `status`, `search`, `per_page`

### Perbarui Status Pesanan (Admin & Staff)
- **Method**: `PUT` / `PATCH`
- **URL**: `/api/orders/{id}/status`
- **Alur Status Resmi**:
  - `pending` ➔ `confirmed` | `processing` | `cancelled`
  - `confirmed` ➔ `processing` | `cancelled`
  - `processing` ➔ `ready` | `completed` | `cancelled`
  - `ready` ➔ `completed` | `cancelled`
  - `completed` ➔ (Terminal / Final)
  - `cancelled` ➔ (Terminal / Final)
- **Body**:
```json
{
  "status": "confirmed",
  "catatan": "Pesanan telah dikonfirmasi dan masuk antrean produksi"
}
```
*(Catatan: Transisi status ilegal seperti `completed` ➔ `pending` akan otomatis ditolak dengan kode status 422).*

### Batalkan Pesanan (Admin & Staff)
- **Method**: `POST`
- **URL**: `/api/orders/{id}/cancel`
- **Body**:
```json
{
  "alasan": "Bahan baku habis"
}
```

---

## 💳 5. Pembayaran (Payment)

### Catat Pembayaran Pesanan (Admin & Staff)
- **Method**: `POST`
- **URL**: `/api/orders/{order}/payments` atau `/api/payments`
- **Validasi Ketat**:
  - Tidak dapat mencatat pembayaran pada pesanan yang berstatus `cancelled`.
  - Nominal pembayaran tidak boleh melebihi sisa tagihan pesanan.
  - Otomatis memperbarui `payment_status` (`unpaid`, `partial`, `paid`) dan `paid_amount` pada pesanan.
  - Otomatis mencatat transaksi pemasukan (`tipe: pemasukan`) pada modul keuangan (`finance_transactions`).
- **Body**:
```json
{
  "nominal": 50000,
  "metode": "transfer", // cash, transfer, qris, debit, kartu_kredit
  "tipe": "dp", // dp, lunas, cicilan, pelunasan
  "tanggal": "2026-10-06",
  "catatan": "Uang muka transfer via BCA",
  "bukti_bayar": "https://example.com/bukti-transfer.jpg"
}
```
- **Respon Sukses (201)**:
```json
{
  "success": true,
  "message": "Pembayaran berhasil dicatat.",
  "data": {
    "id": 1,
    "order_id": 14,
    "kode_pesanan": "ORD-20261006-0001",
    "nominal": 50000,
    "metode": "transfer",
    "tipe": "dp",
    "tanggal": "2026-10-06",
    "catatan": "Uang muka transfer via BCA"
  }
}
```

### Riwayat Pembayaran Per Pesanan
- **Method**: `GET`
- **URL**: `/api/orders/{order}/payments`

### Daftar Seluruh Pembayaran
- **Method**: `GET`
- **URL**: `/api/payments`
- **Query Params**: `order_id`, `metode`, `tipe`, `tanggal_start`, `tanggal_end`, `per_page`

---

## 📊 6. Keuangan (Finance) & Dashboard

### Ringkasan Dashboard ERP
- **Method**: `GET`
- **URL**: `/api/dashboard`
- **Header**: `Authorization: Bearer <token>`
- **Respon Sukses (200)**:
```json
{
  "success": true,
  "message": "Data ringkasan dashboard berhasil dimuat.",
  "data": {
    "ringkasan": {
      "total_pemasukan": 5450000,
      "total_pengeluaran": 2400000,
      "saldo": 3050000,
      "omzet": 5450000,
      "laba": 3050000,
      "pemasukan_bulan_ini": 5450000,
      "pengeluaran_bulan_ini": 2400000,
      "laba_bulan_ini": 3050000,
      "total_orders": 18,
      "active_orders": 6,
      "completed_orders": 10,
      "cancelled_orders": 2,
      "total_customers": 14,
      "total_products": 15
    },
    "pesanan_terbaru": [ ... ],
    "transaksi_terbaru": [ ... ],
    "trend_harian": [
      {
        "tanggal": "2026-10-06",
        "hari": "Selasa",
        "pemasukan": 850000,
        "pengeluaran": 200000,
        "laba": 650000
      }
    ],
    "produk_terlaris": [
      {
        "product_id": 1,
        "product_name": "Roti Tawar Gandum",
        "total_terjual": 45,
        "total_omzet": 1125000
      }
    ]
  }
}
```

### Laporan Ringkasan Keuangan (Harian & Bulanan)
- **Method**: `GET`
- **URL**: `/api/finance/summary`
- **Query Params**: `bulan`, `tahun`, `tanggal_mulai`, `tanggal_akhir`
- **Respon Sukses (200)**:
```json
{
  "success": true,
  "message": "Ringkasan keuangan berhasil diambil.",
  "data": {
    "total_pemasukan": 5450000,
    "total_pengeluaran": 2400000,
    "saldo": 3050000,
    "omzet": 5450000,
    "laba": 3050000,
    "breakdown_harian": [
      {
        "tanggal": "2026-10-01",
        "omzet": 650000,
        "pengeluaran": 150000,
        "laba": 500000
      }
    ],
    "breakdown_bulanan": [
      {
        "bulan": "2026-10",
        "omzet": 5450000,
        "pengeluaran": 2400000,
        "laba": 3050000
      }
    ],
    "breakdown_kategori": [
      { "tipe": "pemasukan", "kategori": "Penjualan", "total": 5450000 },
      { "tipe": "pengeluaran", "kategori": "Bahan Baku", "total": 850000 },
      { "tipe": "pengeluaran", "kategori": "Packaging", "total": 150000 }
    ]
  }
}
```

### Daftar Transaksi Keuangan
- **Method**: `GET`
- **URL**: `/api/finance`
- **Query Params**: `tipe` (`pemasukan` / `pengeluaran`), `kategori`, `tanggal_mulai`, `tanggal_akhir`, `search`, `per_page`

### Catat Pengeluaran / Pemasukan Manual
- **Method**: `POST`
- **URL**: `/api/finance`
- **Body**:
```json
{
  "tipe": "pengeluaran",
  "nominal": 250000,
  "kategori": "Bahan Baku", // Bahan Baku, Packaging, Gaji, Operasional, Peralatan, Lainnya
  "catatan": "Beli keju cheddar 5kg",
  "tanggal": "2026-10-06"
}
```

### Hapus Transaksi Keuangan (Admin Only)
- **Method**: `DELETE`
- **URL**: `/api/finance/{id}`

---

## 💓 7. Health Check
- **Method**: `GET`
- **URL**: `/api/health`
- **Respon Sukses (200)**:
```json
{
  "status": "ok",
  "timestamp": "2026-10-06T13:00:00.000000Z",
  "environment": "local"
}
```
