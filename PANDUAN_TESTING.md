# 📋 Buku Panduan Pengujian Sistem (Software Testing & UAT Guide)
## **Erles Bakery — E-Commerce Storefront & Bakery ERP System**

Dokumen resmi pelaksanaan pengujian fungsional (*Functional Testing*), *User Acceptance Testing* (UAT), dan validasi alur kerja *End-to-End* (E2E) untuk ekosistem digital **Erles Bakery**.

---

## 1. Informasi Lingkungan & Kredensial Pengujian

### 1.1 Tautan Aplikasi (Environment URLs)
| Platform | Lingkungan Produksi / Staging (Netlify) | Lingkungan Pengembangan Lokal |
| :--- | :--- | :--- |
| **Storefront Publik** | [https://erlesbakery.netlify.app](https://erlesbakery.netlify.app) | `http://localhost:5173` |
| **Admin & ERP Portal** | [https://erlesbakery-admin.netlify.app](https://erlesbakery-admin.netlify.app) | `http://localhost:5174` |
| **API Backend (Laravel)** | Cloud API / Tunnel | `http://localhost:8000/api` |

### 1.2 Matriks Akun & Hak Akses Pengujian
| Role | Email Akun | Kata Sandi | Hak Akses Utama |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@erlesbakery.com` | `password` | Akses penuh ke seluruh modul ERP: Dashboard, Pesanan, Produk, Stok Bahan Baku, dan Laporan Keuangan. |
| **Kasir / Staff** | `staff@erlesbakery.com` | `password` | Operasional pesanan kasir, verifikasi pesanan, dan pencatatan bahan baku harian. |
| **Pelanggan (Guest)** | *(Tanpa Login)* | *(Tanpa Login)* | Eksplorasi katalog roti, pemesanan online, pelacakan pesanan via nomor resi/WhatsApp. |

> [!TIP]
> Pada portal admin, Anda dapat langsung mengklik tombol **👑 Admin** atau **🧑‍🍳 Kasir / Staff** di halaman login untuk pengisian instan tanpa perlu mengetik manual.

---

## 2. Alur Pengujian End-to-End (E2E Testing Flow)

1. **Sisi Pelanggan (Storefront)**:
   - Pelanggan membuka website publik `erlesbakery.netlify.app`.
   - Menjelajahi katalog roti & pastry.
   - Menambahkan produk ke keranjang belanja & mengisi form pemesanan (nama, alamat, kontak).
   - Mendapatkan nomor pesanan / rincian order.
2. **Sisi Operasional (Admin ERP)**:
   - Kasir/Admin login di `erlesbakery-admin.netlify.app`.
   - Melihat pesanan baru masuk di menu `/pesanan`.
   - Mengubah status pesanan dari *Menunggu Konfirmasi* $\rightarrow$ *Sedang Diproses*.
   - Mencetak nota / struk fisik untuk dapur (*thermal receipt*).
3. **Sisi Produksi & Inventaris**:
   - Tim bakehouse memeriksa sisa persediaan di menu `/stok-bahan`.
   - Mencatat pengurangan bahan baku (tepung, butter, ragi) sesuai volume produksi.
   - Sistem membunyikan/menampilkan peringatan jika bahan menipis.
4. **Sisi Finansial & Kas**:
   - Setelah pesanan diselesaikan (*Selesai*), omset otomatis masuk ke ringkasan harian.
   - Tim memeriksa laba kotor & arus kas di menu `/keuangan`.

---

## 3. Lembar Checklist Pengujian Modul per Modul

### MODUL A: Storefront Publik (`erlesbakery.netlify.app`)

| ID Kasus | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :---: |
| **PUB-01** | Navigasi Halaman Utama (*Home*) | Buka halaman utama, scroll dari hero banner hingga footer. | Animasi muncul halus (*fade-in*), tombol navigasi berfungsi, dan tampilan responsif di desktop maupun HP. | [ ] |
| **PUB-02** | Filter & Pencarian Menu Produk | Masuk ke menu/katalog, filter berdasarkan kategori (Roti Manis, Sourdough, Pastry, dll). | Daftar produk terfilter sesuai kategori tanpa reload halaman. | [ ] |
| **PUB-03** | Alur Pemesanan (*Checkout*) | 1. Pilih produk & kuantitas.<br>2. Masukkan ke keranjang.<br>3. Lengkapi formulir pemesan.<br>4. Konfirmasi pesanan. | Muncul ringkasan pesanan, kalkulasi total harga akurat, dan nomor pesanan dibuat. | [ ] |
| **PUB-04** | Pelacakan Pesanan (*Order Tracking*) | Buka menu *Lacak Pesanan*, masukkan nomor pesanan atau nomor WhatsApp. | Status pesanan terkini (Menunggu/Diproses/Siap/Selesai) ditampilkan dengan detail item. | [ ] |
| **PUB-05** | Integrasi Kontak & WhatsApp | Klik tombol mengambang (*Floating WhatsApp*) di pojok kanan bawah. | Membuka chat WhatsApp dengan template pesan konfirmasi yang sudah terisi otomatis. | [ ] |

---

### MODUL B: Otentikasi & Keamanan (`erlesbakery-admin.netlify.app`)

| ID Kasus | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :---: |
| **AUTH-01** | Login Berhasil (Admin) | Masukkan `admin@erlesbakery.com`, password `password`, klik *Masuk ke Sistem*. | Muncul toast notifikasi sukses dan langsung dialihkan ke `/dashboard`. | [ ] |
| **AUTH-02** | Tombol Isi Cepat (*Quick Fill*) | Klik tombol `👑 Admin` atau `🧑‍🍳 Kasir / Staff`. | Kolom email dan password otomatis terisi dengan benar. | [ ] |
| **AUTH-03** | Validasi Input Kosong | Kosongkan email atau password, lalu tekan Enter / klik submit. | Sistem menampilkan peringatan *"Harap isi email dan password"* dan membatalkan request. | [ ] |
| **AUTH-04** | Proteksi Rute (*Route Guard*) | Akses langsung URL `https://erlesbakery-admin.netlify.app/keuangan` di tab baru tanpa login. | Pengguna dicegat dan otomatis dialihkan ke halaman `/login`. | [ ] |
| **AUTH-05** | Logout Sistem | Klik tombol *Keluar* / *Logout* di bilah samping (*sidebar*). | Sesi token dihapus dan pengguna kembali ke halaman `/login`. | [ ] |

---

### MODUL C: Dashboard & Analitik Eksekutif

| ID Kasus | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :---: |
| **DASH-01** | Visualisasi Metrik KPI | Periksa 4 kartu ringkasan: Total Penjualan, Total Pesanan, Stok Menipis, dan Kas Bersih. | Nilai angka diformat dalam Rupiah (Rp) dan jumlah item valid. | [ ] |
| **DASH-02** | Grafik Tren Penjualan | Periksa grafik penjualan mingguan / bulanan. | Grafik interaktif merespons saat kursor diarahkan (*hover tooltip* menunjukkan angka riil). | [ ] |
| **DASH-03** | Tabel Pesanan Terkini | Periksa widget daftar pesanan terbaru di bagian bawah dashboard. | Menampilkan 5 transaksi paling baru beserta badge status berwarna. | [ ] |

---

### MODUL D: Manajemen Katalog Produk (`/produk`)

| ID Kasus | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :---: |
| **PROD-01** | Tambah Produk Baru | 1. Klik *Tambah Produk*.<br>2. Isi Nama (misal: *Croissant Almond Special*).<br>3. Tentukan Harga, Kategori, dan Stok Awal.<br>4. Simpan. | Produk baru berhasil masuk ke daftar katalog dengan status *Tersedia*. | [ ] |
| **PROD-02** | Edit Informasi & Harga Produk | Klik tombol ikon pensil (*Edit*) pada salah satu produk, ubah harga dan simpan. | Data terupdate seketika pada tabel tanpa merusak format data lain. | [ ] |
| **PROD-03** | Pengaturan Status Stok (*Toggle*) | Ubah status ketersediaan produk menjadi *Habis / Nonaktif*. | Badge status berubah merah (*Habis*) dan produk ditandai tidak dapat dipesan. | [ ] |
| **PROD-04** | Pencarian Produk | Ketik kata kunci pada kotak pencarian (misal: "Sourdough"). | Tabel memfilter hanya baris yang memuat nama produk yang dicari. | [ ] |

---

### MODUL E: Manajemen Pesanan Kasir (`/pesanan`)

| ID Kasus | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :---: |
| **ORD-01** | Filter Status Pesanan | Klik tab status: *Semua*, *Menunggu Konfirmasi*, *Sedang Diproses*, *Siap Diambil*, *Selesai*. | Tabel hanya menampilkan pesanan yang sesuai dengan status tab yang aktif. | [ ] |
| **ORD-02** | Perubahan Status Pesanan | Pilih salah satu pesanan berstatus *Menunggu*, lalu ubah menjadi *Diproses* lalu *Selesai*. | Status terbarui secara *real-time*, warna badge berubah, dan riwayat status tercatat. | [ ] |
| **ORD-03** | Cetak Struk / Nota Pesanan | Klik tombol ikon printer (*Cetak Struk*) pada baris pesanan. | Jendela print pratinjau muncul dengan tata letak struk kasir (*thermal paper*) Erles Bakery. | [ ] |
| **ORD-04** | Pembatalan Pesanan | Klik opsi batalkan pesanan pada pesanan yang belum diproses. | Muncul konfirmasi; status berubah menjadi *Dibatalkan*. | [ ] |

---

### MODUL F: Manajemen Stok Bahan Baku (`/stok-bahan`)

| ID Kasus | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :---: |
| **STK-01** | Peringatan Stok Kritis (*Low Stock Alert*) | Periksa bahan baku yang jumlahnya di bawah batas minimum (*threshold*). | Ditandai dengan banner kuning/merah bertuliskan *"Stok Menipis"*. | [ ] |
| **STK-02** | Tambah Mutasi Stok Masuk | Pilih bahan (misal: *Tepung Cakra Kembar*), klik *Restock/Masuk*, masukkan +25 kg dan harga beli. | Total sisa stok bertambah dan log mutasi tercatat dengan timestamp saat itu. | [ ] |
| **STK-03** | Catat Pemakaian Bahan Baku | Pilih bahan (misal: *Anchor Butter*), catat pemakaian produksi harian -5 kg. | Stok berkurang otomatis, validasi mencegah stok menjadi negatif jika melebihi sisa fisik. | [ ] |
| **STK-04** | Tambah Bahan Baku Baru | Klik *Tambah Bahan Baku*, masukkan nama bahan, satuan (kg/gr/liter), stok awal, dan batas minimum. | Bahan baku baru tampil di daftar inventaris. | [ ] |

---

### MODUL G: Keuangan & Arus Kas (`/keuangan`)

| ID Kasus | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :---: |
| **FIN-01** | Ringkasan Laba Bersih | Periksa kartu: *Pemasukan*, *Pengeluaran*, dan *Laba Bersih* (Pemasukan - Pengeluaran). | Perhitungan matematika tepat dan selisih laba bersih otomatis terkalkulasi. | [ ] |
| **FIN-02** | Catat Pengeluaran Operasional | Klik *Tambah Pengeluaran*, isi kategori (misal: *Gas Oven / Kemasan Box*), nominal Rp 350.000, lalu simpan. | Kas pengeluaran bertambah dan laba bersih langsung menyesuaikan. | [ ] |
| **FIN-03** | Filter Rentang Tanggal | Pilih filter periode: *Hari Ini*, *7 Hari Terakhir*, *Bulan Ini*, atau *Kustom*. | Tabel transaksi dan grafik mengagregasi data sesuai rentang tanggal yang dipilih. | [ ] |

---

## 4. Format Laporan Temuan Bug (Defect Template)

Jika selama pengujian ditemukan kesalahan atau bug, gunakan format pelaporan berikut:

```markdown
### 🐛 TEMUAN BUG
- ID: BUG-001
- Modul: [Storefront / Admin Login / Pesanan / Stok / Keuangan]
- Severity: [Kritis / Tinggi / Sedang / Rendah]
- URL: https://erlesbakery-admin.netlify.app/...
- Browser/Perangkat: Chrome Windows / Safari iOS

Langkah Mereproduksi:
1. ...
2. ...

Hasil yang Terjadi:
...

Hasil yang Seharusnya:
...
```

---

## 5. Kriteria Kelulusan Pengujian (Sign-off Criteria)

Sistem dinyatakan **Lulus UAT dan Siap Produksi** apabila:
1. Seluruh skenario inti (**Login**, **Katalog**, **Pesanan Masuk**, dan **Stok Bahan**) berstatus **PASS 100%**.
2. Tidak ada error kritis (*white screen / blank page / crash loop*).
3. Tata letak responsif dan tidak ada tombol yang terpotong pada layar ponsel pintar (*smartphone*).
