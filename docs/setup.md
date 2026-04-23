# 🚀 Panduan Setup Proyek Pamsides

Selamat datang di panduan instalasi resmi **Pamsides**. Ikuti langkah-langkah di bawah ini untuk menyiapkan lingkungan pengembangan Anda dari nol hingga siap digunakan.

---

## 🛠️ Prasyarat (Prerequisites)

Sebelum memulai, pastikan mesin Anda telah terinstal perangkat lunak berikut:

*   **Node.js**: Versi LTS (v22 ke atas direkomendasikan)
*   **pnpm**: Versi 10+ (`npm install -g pnpm`)
*   **PHP**: Versi 8.3 ke atas (dengan ekstensi yang diperlukan oleh Laravel)
*   **Composer**: Versi 2.x
*   **Database**: MySQL atau MariaDB (direkomendasikan melalui **Laragon** untuk pengguna Windows)

---

## 🏗️ Langkah-Langkah Instalasi

### 1. Clone Repositori

Clone repositori ini dan masuk ke dalam direktori proyek:

```bash
git clone -b develop git@github.com:ab1-team/pamsides.git
cd pamsides
```

### 2. Setup Root & Git Hooks

Proyek ini menggunakan **Husky** untuk mengelola Git Hooks. Jalankan instalasi di direktori utama (root) untuk mengaktifkannya:

```bash
pnpm install
```

> [!NOTE]
> Langkah ini akan menginstal dependensi root (seperti Husky dan lint-staged) dan secara otomatis menjalankan script `prepare`.

### 3. Konfigurasi Backend (Laravel)

Pindah ke folder `backend`, instal dependensi, dan siapkan lingkungan:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

#### 🗄️ Konfigurasi Database
Buka file `.env` di folder `backend` dan sesuaikan pengaturan database Anda:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pamsides_db
DB_USERNAME=root
DB_PASSWORD=
```

Setelah konfigurasi selesai, jalankan migrasi untuk membuat tabel:

```bash
php artisan migrate --seed
```

### 4. Konfigurasi Frontend (Vue.js + Vite)

Pindah ke folder `frontend` dan instal dependensi:

```bash
cd ../frontend
pnpm install
```

---

## ⚡ Menjalankan Aplikasi

Untuk menjalankan aplikasi dalam mode pengembangan, Anda perlu membuka dua terminal:

### Terminal 1: Backend
```bash
cd backend
php artisan serve
```
Aplikasi backend biasanya akan berjalan di `http://127.0.0.1:8000`.

### Terminal 2: Frontend
```bash
cd frontend
pnpm run dev
```
Aplikasi frontend akan berjalan di `http://localhost:5173` (atau port lain yang muncul di terminal).

---

## 🤝 Standar Pengembangan

### Git Workflow
Jangan melakukan push langsung ke branch `develop`. Gunakan alur kerja berikut:
1.  Buat branch baru: `git checkout -b feature/nama-fitur`.
2.  Gunakan **Conventional Commits** (misal: `feat:`, `fix:`, `docs:`).
3.  Lakukan Pull Request ke branch `develop`.

### Linting & Formatting
Proyek ini secara otomatis menjalankan pemeriksaan kode saat Anda melakukan commit:
*   **PHP**: Menggunakan Laravel Pint.
*   **JS/Vue**: Menggunakan ESLint, Oxlint, dan Prettier.

Jika commit gagal karena lint error, perbaiki error yang muncul sebelum mencoba commit kembali.

---

> [!TIP]
> Jika Anda mengalami masalah saat instalasi atau memiliki pertanyaan, silakan hubungi tim pengembang melalui kanal resmi.
