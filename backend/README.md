# 🍞 Erles Bakery — Backend API

Backend API untuk Erles Bakery ERP berbasis **Laravel 13** dan database **PostgreSQL (Supabase)**.

## Setup Supabase

Aplikasi backend ini menggunakan database hosted di **Supabase** (bukan PostgreSQL lokal). Hanya service backend yang memerlukan kredensial database.

1. **Buka Project Supabase**: Region Singapore (Project ref: `iohzdauarxzbcynxvgpr`).
2. **Koneksi Pooler**: Masuk ke menu **Connect** > pilih tab **Session pooler** (Port 5432).
   > **Catatan Penting**: Gunakan host Session pooler (`aws-0-ap-southeast-1.pooler.supabase.com`), **BUKAN** host direct `db.<ref>.supabase.co` karena direct connection di free tier bersifat IPv6-only dan sering gagal dari Docker container.
3. **Konfigurasi Environment**: Isi kredensial di `backend/.env` (salin dari `.env.example` jika belum ada):
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
   DB_PORT=5432
   DB_DATABASE=postgres
   DB_USERNAME=postgres.iohzdauarxzbcynxvgpr
   DB_PASSWORD=<isi-password-database>
   DB_SSLMODE=require
   ```
   > ⚠️ **Peringatan**: Jangan pernah meng-commit file `backend/.env` ke Git repository!
4. **Jalankan Aplikasi**: Dari root repository, jalankan `./run.sh dev` untuk memvalidasi koneksi dan menjalankan migrasi secara otomatis.
5. **Aktifkan RLS**: Buka **Supabase SQL Editor**, lalu salin dan jalankan file `../supabase/rls.sql` untuk mengaktifkan Row Level Security pada seluruh tabel di skema public. (Lihat instruksi via `./run.sh rls`).

> **Catatan Free Tier**: Project Supabase pada tier gratis akan di-pause otomatis jika tidak ada aktivitas selama ~1 minggu. Jika koneksi database gagal, silakan buka dashboard Supabase dan klik **Restore Project**.

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects.
