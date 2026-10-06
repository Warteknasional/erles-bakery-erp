# 🍞 Erles Bakery — Micro ERP

Sistem ERP terintegrasi untuk **Erles Bakery**, mencakup manajemen pesanan, produk, keuangan, dan halaman publik.

## Arsitektur

```
erles-bakery-erp/
├── backend/          # Laravel 13 (API-only) + PostgreSQL (Supabase) + Sanctum
├── admin/            # [Git Submodule] React + Vite — Dashboard Admin (port 5174)
├── public/           # [Git Submodule] React + Vite — Website Publik (port 5173)
├── supabase/         # Skrip SQL Supabase (RLS, dsb.)
├── docker-compose.yml
├── run.sh            # Script orchestrator
└── README.md
```

### Diagram Arsitektur

```
┌─────────────────────────────────────────────────────────┐
│                    Docker Compose                       │
│                                                         │
│                     ┌──────────────┐  ┌──────────────┐  │
│                     │   Backend    │  │    Admin      │  │
│                     │  Laravel     │  │  React+Vite   │  │
│                     │  :8000       │◄─│  :5174        │  │
│                     └──────┬───────┘  └──────────────┘  │
│                            │                            │
│                            ▼                            │
│                     ┌──────────────┐                    │
│                     │   Public     │                    │
│                     │  React+Vite  │                    │
│                     │  :5173       │                    │
│                     └──────────────┘                    │
└────────────────────────────┼────────────────────────────┘
                             │ (Session Pooler :5432)
                             ▼
              ┌──────────────────────────────┐
              │      Supabase (Hosted)       │
              │         PostgreSQL           │
              └──────────────────────────────┘
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

### 2. Setup Supabase Database

Proyek ini menggunakan PostgreSQL hosted di **Supabase** (bukan database container lokal). Hanya service **backend** yang membutuhkan kredensial database; frontend admin & public berkomunikasi melalui backend API.

1. Buka dashboard Supabase (Region Singapore, Project ref: `iohzdauarxzbcynxvgpr`).
2. Masuk ke **Connect** > pilih tab **Session pooler** (Port 5432, mode session).
   > **Catatan Penting**: Gunakan host Session pooler (`aws-0-ap-southeast-1.pooler.supabase.com`), **BUKAN** host direct `db.<ref>.supabase.co` karena direct connection di free tier bersifat IPv6-only dan sering gagal dijangkau dari lingkungan Docker.
3. Salin kredensial ke `backend/.env` (buat dari `backend/.env.example` jika belum ada):
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
   DB_PORT=5432
   DB_DATABASE=postgres
   DB_USERNAME=postgres.iohzdauarxzbcynxvgpr
   DB_PASSWORD=<isi-password-database>
   DB_SSLMODE=require
   ```
   > ⚠️ **Keamanan**: Jangan pernah membagikan atau meng-commit file `backend/.env` ke git repository!
4. Jalankan aplikasi via `./run.sh dev` (script akan otomatis memeriksa koneksi database dan menjalankan migrasi).
5. Jalankan skrip RLS: Buka **Supabase SQL Editor**, salin dan jalankan seluruh isi file `supabase/rls.sql` untuk mengaktifkan Row Level Security pada semua tabel di skema public. Anda dapat menjalankan `./run.sh rls` untuk melihat petunjuk lengkap.

> **Peringatan Free Tier**: Project Supabase pada tier gratis akan otomatis di-pause jika tidak aktif selama ~1 minggu. Jika backend gagal terhubung, buka dashboard Supabase dan klik **Restore Project**.

### 3. Jalankan Aplikasi

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
| `./run.sh dev`       | (Default) Build & jalankan semua layanan, verifikasi koneksi Supabase, dan migrasi. |
| `./run.sh stop`      | Hentikan dan hapus semua container (setara dengan `docker compose down`).|
| `./run.sh restart`   | Lakukan proses stop, lalu jalankan `dev` kembali.                        |
| `./run.sh logs`      | Lihat log semua container (atau layanan spesifik, misal `./run.sh logs backend`). |
| `./run.sh fresh`     | Refresh DB (Drop tabel, migrasi ulang, seed) dengan konfirmasi interaktif (gunakan `--force` untuk skip). |
| `./run.sh rls`       | Tampilkan petunjuk pengaktifan Row Level Security (RLS) di Supabase SQL Editor. |
| `./run.sh help`      | Tampilkan menu bantuan ini di terminal Anda.                             |

| Service          | URL                            |
|------------------|--------------------------------|
| **Backend API**  | http://localhost:8000/api      |
| **Health Check** | http://localhost:8000/api/health |
| **Admin Panel**  | http://localhost:5174          |
| **Website Publik** | http://localhost:5173        |
| **PostgreSQL**   | Supabase (hosted)              |

## Skema Database

| Tabel                  | Deskripsi                                    |
|------------------------|----------------------------------------------|
| `users`                | Admin/karyawan dengan role                   |
| `products`             | Produk (Roti, Kue, Hampers, dll.)           |
| `orders`               | Pesanan pelanggan                            |
| `order_items`          | Detail item per pesanan                      |
| `finance_transactions` | Catatan kas masuk/keluar                     |

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

# Reset database Supabase bersama (memerlukan konfirmasi "yes")
./run.sh fresh

# Jalankan artisan command di backend
docker compose exec backend php artisan <command>
```

## Teknologi

- **Backend**: Laravel 13, PHP 8.2, Laravel Sanctum
- **Database**: PostgreSQL (Supabase Hosted)
- **Frontend Admin**: React 19, Vite, Axios, React Router, Lucide Icons
- **Frontend Public**: React 19, Vite, Axios, React Router, Lucide Icons
- **Orkestrasi**: Docker Compose

## Lisensi

Hak Cipta © 2024 Erles Bakery. All rights reserved.
