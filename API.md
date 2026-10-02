# 📖 Erles Bakery ERP — Dokumentasi Kontrak API (Backend)

Dokumentasi REST API untuk **Erles Bakery ERP** yang dikonsumsi oleh:
1. **Admin Panel** (React, dashboard owner: kelola produk, pesanan, dan keuangan)
2. **Public Website** (React, landing page katalog, keranjang pesanan Hampers/Roti/Kue, tracking pesanan)

---

## 🌐 Base URL & Konvensi

- **Base URL**: `http://localhost:8000/api`
- **Header Standar**:
  ```http
  Accept: application/json
  Content-Type: application/json
  ```
- **Protected Endpoint Header**:
  ```http
  Authorization: Bearer <token>
  ```

---

## 📦 Format Respon Standar

### Respon Sukses (Standard Success)
```json
{
  "success": true,
  "message": "Pesan keberhasilan",
  "data": { ... }
}
```

### Respon Paginasi (Paginated Success)
```json
{
  "success": true,
  "message": "Daftar data berhasil diambil.",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 45
  }
}
```

### Respon Error (Standard Error)
```json
{
  "success": false,
  "message": "Deskripsi pesan error",
  "errors": {
    "field_name": [
      "Pesan validasi detail"
    ]
  }
}
```

---

## 🔑 1. Autentikasi Admin

Default Akun Admin (dari Seeder):
- **Email**: `admin@erlesbakery.com`
- **Password**: `password`

### 1.1 Login
`POST /api/login` (Rate limited: 10/min)

**Request Body**:
```json
{
  "email": "admin@erlesbakery.com",
  "password": "password"
}
```

**Response (200 OK)**:
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
    "token": "1|qwe987xyz...",
    "token_type": "Bearer"
  }
}
```

### 1.2 Profil Saya (Me)
`GET /api/me` *(Auth: Bearer Token)*

**Response (200 OK)**:
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

### 1.3 Logout
`POST /api/logout` *(Auth: Bearer Token)*

**Response (200 OK)**:
```json
{
  "success": true,
  "message": "Logout berhasil.",
  "data": null
}
```

---

## 🥐 2. Modul Produk (Products)

### 2.1 Daftar Produk (Katalog Publik / Admin)
`GET /api/products`

**Query Parameters**:
| Parameter | Tipe | Contoh | Keterangan |
| :--- | :--- | :--- | :--- |
| `kategori` | String | `Hampers` | Filter kategori (`Roti`, `Kue`, `Kue Kering`, `Hampers`) |
| `search` | String | `Brownies` | Pencarian nama atau deskripsi produk |
| `is_active` | Boolean | `true` | Filter status aktif (Publik otomatis hanya melihat `is_active=true`) |
| `all` | Boolean | `true` | Ambil semua tanpa paginasi |
| `page` | Integer | `1` | Nomor halaman paginasi |
| `per_page` | Integer | `15` | Jumlah data per halaman |

**Response (200 OK)**:
```json
{
  "success": true,
  "message": "Daftar produk berhasil diambil.",
  "data": [
    {
      "id": 12,
      "nama": "Hampers Lebaran Classic",
      "slug": "hampers-lebaran-classic",
      "deskripsi": "Paket 3 toples kue kering dalam box eksklusif.",
      "harga": 250000,
      "stok": 15,
      "kategori": "Hampers",
      "gambar": "https://...",
      "is_active": true
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

### 2.2 Detail Produk (Publik)
`GET /api/products/{idOrSlug}` (Bisa menggunakan `id` numerik atau `slug`)

### 2.3 Tambah Produk Baru (Admin)
`POST /api/products` *(Auth: Bearer Token)*

**Request Body**:
```json
{
  "nama": "Hampers Spesial Ramadhan",
  "kategori": "Hampers",
  "harga": 350000,
  "stok": 25,
  "deskripsi": "Paket hampers eksklusif isi 4 toples kue kering.",
  "gambar": "https://...",
  "is_active": true
}
```

### 2.4 Update Produk (Admin)
`PUT /api/products/{id}` *(Auth: Bearer Token)*

### 2.5 Hapus Produk (Admin)
`DELETE /api/products/{id}` *(Auth: Bearer Token)*

---

## 📋 3. Modul Pesanan (Orders)

### 3.1 Buat Pesanan Baru (Publik / Pelanggan)
`POST /api/orders` (Rate limited: 30/min)

> **Catatan Server-Side Calculation**: Client hanya mengirimkan `product_id` dan `qty`. Harga satuan (`unit_price`), subtotal, dan `total_price` dihitung 100% di server database demi integritas keamanan keuangan.

**Request Body**:
```json
{
  "customer_name": "Dewi Sartika",
  "customer_phone": "081234567890",
  "alamat": "Jl. Mawar No. 4, Jakarta Selatan",
  "catatan": "Tolong kirim sebelum jam 10 pagi",
  "tanggal_ambil": "2026-10-15",
  "items": [
    {
      "product_id": 12,
      "qty": 2
    },
    {
      "product_id": 5,
      "qty": 1
    }
  ]
}
```

**Response (201 Created)**:
```json
{
  "success": true,
  "message": "Pesanan berhasil dibuat. Kami akan segera menghubungi Anda.",
  "data": {
    "id": 6,
    "kode_pesanan": "ORD-20261001-0006",
    "customer_name": "Dewi Sartika",
    "customer_phone": "081234567890",
    "alamat": "Jl. Mawar No. 4, Jakarta Selatan",
    "catatan": "Tolong kirim sebelum jam 10 pagi",
    "tanggal_ambil": "2026-10-15",
    "total_price": 545000,
    "status": "pending",
    "items": [
      {
        "id": 10,
        "product_id": 12,
        "product": {
          "id": 12,
          "nama": "Hampers Lebaran Classic",
          "harga": 250000
        },
        "qty": 2,
        "unit_price": 250000,
        "subtotal": 500000
      },
      {
        "id": 11,
        "product_id": 5,
        "product": {
          "id": 5,
          "nama": "Brownies Panggang",
          "harga": 45000
        },
        "qty": 1,
        "unit_price": 45000,
        "subtotal": 45000
      }
    ]
  }
}
```

### 3.2 Lacak Pesanan (Publik / Pelanggan)
`GET /api/orders/track/{kode_pesanan}`

### 3.3 Daftar Pesanan (Admin)
`GET /api/orders` *(Auth: Bearer Token)*

**Query Parameters**:
- `status`: `pending`, `diproses`, `selesai`, `dibatalkan`
- `search`: cari nama pelanggan, no telp, atau kode pesanan
- `tanggal_mulai`, `tanggal_akhir`: filter rentang tanggal ambil

### 3.4 Detail Pesanan (Admin)
`GET /api/orders/{id}` *(Auth: Bearer Token)*

### 3.5 Ubah Status Pesanan (Admin)
`PUT /api/orders/{id}/status` *(Auth: Bearer Token)*

> **Otomatisasi Keuangan**: Ketika status diubah menjadi `selesai`, sistem secara otomatis mencatat transaksi pemasukan di tabel `finance_transactions` yang terhubung dengan `order_id` terkait.

**Request Body**:
```json
{
  "status": "selesai"
}
```

---

## 💰 4. Modul Keuangan (Finance)

### 4.1 Ringkasan Keuangan Dashboard (Admin)
`GET /api/finance/summary` *(Auth: Bearer Token)*

**Query Parameters**:
- `bulan`: 1 - 12 (opsional)
- `tahun`: contoh `2026` (opsional)

**Response (200 OK)**:
```json
{
  "success": true,
  "message": "Ringkasan keuangan berhasil diambil.",
  "data": {
    "total_pemasukan": 1430000,
    "total_pengeluaran": 2400000,
    "saldo": -970000,
    "breakdown_kategori": [
      { "tipe": "pemasukan", "kategori": "Penjualan", "total": "1250000.00" },
      { "tipe": "pengeluaran", "kategori": "Bahan Baku", "total": "850000.00" },
      { "tipe": "pengeluaran", "kategori": "Gaji", "total": "1200000.00" }
    ]
  }
}
```

### 4.2 Daftar Transaksi Keuangan (Admin)
`GET /api/finance` *(Auth: Bearer Token)*

**Query Parameters**:
- `tipe`: `pemasukan` / `pengeluaran`
- `kategori`: filter kategori (`Bahan Baku`, `Packaging`, `Gaji`, `Operasional`, `Penjualan`, dll.)
- `tanggal_mulai`, `tanggal_akhir`: filter rentang tanggal
- `search`: cari di catatan atau kategori

**Response (200 OK)**:
```json
{
  "success": true,
  "message": "Daftar transaksi keuangan berhasil diambil.",
  "data": [
    {
      "id": 1,
      "tipe": "pemasukan",
      "nominal": 450000,
      "kategori": "Penjualan",
      "catatan": "Pembayaran pesanan ORD-20261001-0003",
      "tanggal": "2026-10-01",
      "user_id": 1,
      "order_id": 3
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 1,
    "summary": {
      "total_pemasukan": 450000,
      "total_pengeluaran": 0,
      "saldo": 450000
    }
  }
}
```

### 4.3 Catat Transaksi Baru (Admin)
`POST /api/finance` *(Auth: Bearer Token)*

**Request Body**:
```json
{
  "tipe": "pengeluaran",
  "nominal": 350000,
  "kategori": "Bahan Baku",
  "catatan": "Beli mentega wisman 2 kaleng",
  "tanggal": "2026-10-01"
}
```

### 4.4 Update Transaksi (Admin)
`PUT /api/finance/{id}` *(Auth: Bearer Token)*

### 4.5 Hapus Transaksi (Admin)
`DELETE /api/finance/{id}` *(Auth: Bearer Token)*
