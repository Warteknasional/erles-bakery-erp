# 🍞 Erles Bakery — Micro ERP

Sistem ERP terintegrasi untuk **Erles Bakery**, mencakup manajemen pesanan, produk, keuangan, dan halaman publik.

## Arsitektur

```
erles-bakery-erp/
├── backend/          # Laravel 13 (API-only) + PostgreSQL + Sanctum
├── admin/            # [Git Submodule] React + Vite — Dashboard Admin (port 5174)
├── public/           # [Git Submodule] React + Vite — Website Publik (port 5173)
├── docker-compose.yml
├── run.sh            # Script orchestrator
└── README.md
```

### Diagram Arsitektur

```
┌─────────────────────────────────────────────────────────┐
│                    Docker Compose                       │
│                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │  PostgreSQL   │  │   Backend    │  │    Admin      │  │
│  │  :5432        │◄─│  Laravel     │  │  React+Vite   │  │
│  │              │  │  :8000       │◄─│  :5174        │  │
│  └──────────────┘  └──────┬───────┘  └──────────────┘  │
│                           │                             │
│                           ▼                             │
│                    ┌──────────────┐                     │
│                    │   Public     │                     │
│                    │  React+Vite  │                     │
│                    │  :5173       │                     │
│                    └──────────────┘                     │
└─────────────────────────────────────────────────────────┘
```

## Cara Memulai

### Prasyarat

- [Docker](https://docs.docker.com/get-docker/) & Docker Compose
- [Git](https://git-scm.com/)

### 1. Clone Repositori (dengan Submodule)

```bash
git clone --recurse-submodules https://github.com/Warteknasional/erles-bakery-erp.git
cd erles-bakery-erp
```

Jika sudah clone tanpa `--recurse-submodules`:

```bash
git submodule update --init --recursive
```

### 2. Jalankan Aplikasi

Cukup jalankan **satu perintah** berikut di terminal (berada di root proyek):

```bash
# Memberikan izin eksekusi jika script belum bisa dijalankan (opsional)
chmod +x run.sh

# Menjalankan script otomatis dengan mode default (dev)
./run.sh dev
```
*(ATAU sekadar menjalankan `./run.sh`, karena `dev` adalah mode default).*

Script pintar `run.sh` ini bertindak sebagai **CLI task runner** dengan berbagai opsi yang memudahkan Anda:

| Opsi Perintah        | Penjelasan                                                               |
|----------------------|--------------------------------------------------------------------------|
| `./run.sh dev`       | (Default) Build & jalankan semua layanan, tunggu DB siap, dan migrasi. |
| `./run.sh stop`      | Hentikan dan hapus semua container (setara dengan `docker compose down`).|
| `./run.sh restart`   | Lakukan proses stop, lalu jalankan `dev` kembali.                        |
| `./run.sh logs`      | Lihat log semua container (atau layanan spesifik, misal `./run.sh logs backend`). |
| `./run.sh fresh`     | Refresh DB secara total (Drop seluruh tabel, migrasi ulang, dan jalankan seeder). |
| `./run.sh help`      | Tampilkan menu bantuan ini di terminal Anda.                             |

| Service          | URL                            |
|------------------|--------------------------------|
| **Backend API**  | http://localhost:8000/api      |
| **Health Check** | http://localhost:8000/api/health |
| **Admin Panel**  | http://localhost:5174          |
| **Website Publik** | http://localhost:5173        |
| **PostgreSQL**   | localhost:5432                 |
| **Queue Worker** | Background worker (`erles_queue_worker`) |

> [!NOTE]
> Queue worker (`erles_queue_worker`) berjalan otomatis lewat `docker compose up` untuk memproses job Telegram dan WhatsApp secara asinkron.

## Skema Database

| Tabel                  | Deskripsi                                    |
|------------------------|----------------------------------------------|
| `users`                | Admin/karyawan dengan role                   |
| `products`             | Produk (Roti, Kue, Hampers, dll.)           |
| `orders`               | Pesanan pelanggan                            |
| `order_items`          | Detail item per pesanan                      |
| `finance_transactions` | Catatan kas masuk/keluar                     |

## 🤖 Notifikasi Telegram (Owner / Admin)

Backend terintegrasi dengan bot Telegram untuk mengirimkan notifikasi otomatis secara real-time ke owner/admin saat:
1. **Pesanan baru masuk dari website publik** (kode pesanan, nama pelanggan, daftar item, total harga, metode ambil/kirim, dan tanggal ambil).
2. **Pembayaran pesanan tercatat** (kode pesanan, nominal pembayaran, metode, dan status pelunasan).

### Panduan Setup Bot Telegram:

1. **Buat Bot Baru via BotFather:**
   - Buka Telegram dan cari akun resmi **[@BotFather](https://t.me/BotFather)**.
   - Kirim perintah `/newbot`.
   - Masukkan nama bot (misal: `Erles Bakery Notifier`) dan username bot yang berakhiran `bot` (misal: `erles_bakery_notif_bot`).
   - BotFather akan memberikan **HTTP API Token** (contoh: `7123456789:AAH...`). Salin token ini ke variabel `TELEGRAM_BOT_TOKEN`.

2. **Dapatkan Chat ID (Tujuan Notifikasi):**
   - **Opsi A (Personal Chat):** Cari bot yang baru Anda buat di Telegram, lalu klik **Start** (`/start`). Buka akun **[@userinfobot](https://t.me/userinfobot)** untuk melihat `Id` Telegram Anda (contoh: `123456789`).
   - **Opsi B (Grup Tim / Owner):** Masukkan bot Anda ke grup Telegram tim/owner, jadikan bot sebagai admin grup, kirim 1 pesan apa saja di grup, lalu buka browser:
     ```
     https://api.telegram.org/bot<TOKEN_ANDA>/getUpdates
     ```
     Cari nilai `"chat":{"id": -100xxxxxxxxxx}`. Salin angka ID tersebut (termasuk tanda minus `-` jika berupa grup) ke variabel `TELEGRAM_CHAT_ID`.

3. **Konfigurasi Environment:**
   Masukkan ke file `.env` (atau `backend/.env`):
   ```env
   TELEGRAM_BOT_TOKEN=7123456789:AAH...
   TELEGRAM_CHAT_ID=123456789
   ```
   *Catatan: Jika variabel ini dikosongkan, sistem akan otomatis melewati (skip) pengiriman notifikasi secara hening tanpa menyebabkan error atau mengganggu pembuatan pesanan.*

## Workflow Git Submodule untuk Tim

### Update submodule ke commit terbaru

```bash
# Dari root project
git submodule update --remote admin
git submodule update --remote public

# Commit perubahan pointer submodule
git add admin public
git commit -m "Update submodules to latest"
git push
```

### Bekerja di dalam submodule

```bash
cd admin  # atau cd public
git checkout main
git pull origin main

# Lakukan perubahan, commit, dan push
git add .
git commit -m "feat: tambah fitur baru"
git push

# Kembali ke root dan update pointer
cd ..
git add admin
git commit -m "Update admin submodule"
git push
```

## Perintah Berguna

```bash
# Lihat status container
docker compose ps

# Ikuti log semua service
docker compose logs -f

# Ikuti log satu service
docker compose logs -f backend

# Restart satu service
docker compose restart backend

# Hentikan semua service
docker compose down

# Hentikan dan hapus volume (reset database)
docker compose down -v

# Jalankan artisan command
docker compose exec backend php artisan <command>
```

## 📱 Integrasi WAHA (WhatsApp HTTP API)

Layanan WAHA dijalankan via Docker Compose (`erles_waha`) di jaringan internal Docker untuk memfasilitasi bot WhatsApp pemesanan pelanggan. Notifikasi owner/admin **tetap** dikirimkan melalui bot Telegram.

### 1. Konfigurasi Variabel Lingkungan (`.env`)
Salin variabel WAHA dari `.env.example` ke file `.env` Anda:
```env
WAHA_URL=http://waha:3000
WAHA_SESSION=default
WAHA_API_KEY=
WAHA_WEBHOOK_SECRET=
```

### 2. Cara Scan QR Code WhatsApp Session
Untuk menghubungkan nomor WhatsApp toko ke engine WAHA:
1. Pastikan layanan WAHA telah berjalan:
   ```bash
   docker compose up -d waha
   ```
2. Buka dashboard web WAHA di browser (atau sementara expose port `3000:3000` di `docker-compose.yml` untuk akses visual):
   - Buka `http://localhost:3000/dashboard`
   - Pada session `default`, klik tombol **Start** lalu **Scan QR**.
   - Buka WhatsApp di smartphone Anda: **Perangkat Tertaut (Linked Devices) > Tautkan Perangkat**, lalu arahkan kamera ke QR Code di layar.
3. Setelah status session berubah menjadi **`WORKING`**, webhook secara otomatis terhubung ke `/api/webhooks/waha` pada backend Laravel.
4. Pelanggan kini dapat berinteraksi dengan mengetik:
   - `menu` : Melihat daftar produk aktif & harga
   - `pesan` : Alur interaktif memilih produk, kuantitas, nama, dan tanggal
   - `cek <kode>` : Mengecek status pesanan dan pembayaran
   - `batal` : Membatalkan sesi aktif

## Teknologi

- **Backend**: Laravel 13, PHP 8.2, Laravel Sanctum
- **Database**: PostgreSQL 16
- **Frontend Admin**: React 19, Vite, Axios, React Router, Lucide Icons
- **Frontend Public**: React 19, Vite, Axios, React Router, Lucide Icons
- **Notifikasi Owner/Admin**: Telegram Bot API
- **Bot Pelanggan WA**: WAHA (WhatsApp HTTP API)
- **Orkestrasi**: Docker Compose

## Lisensi

Hak Cipta © 2024 Erles Bakery. All rights reserved.
