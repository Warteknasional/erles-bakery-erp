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

# Hentikan dan hapus volume (reset database)
docker compose down -v

# Jalankan artisan command
docker compose exec backend php artisan <command>
```

## Teknologi

- **Backend**: Laravel 13, PHP 8.2, Laravel Sanctum
- **Database**: PostgreSQL 16
- **Frontend Admin**: React 19, Vite, Axios, React Router, Lucide Icons
- **Frontend Public**: React 19, Vite, Axios, React Router, Lucide Icons
- **Orkestrasi**: Docker Compose

## Lisensi

Hak Cipta © 2024 Erles Bakery. All rights reserved.
