# Pamsides Project Setup Guide

Selamat datang di repositori **Pamsides**. Ikuti langkah-langkah di bawah ini untuk melakukan setup awal setelah melakukan clone repositori ini.

## 🛠 Prasyarat

Pastikan Anda sudah menginstal software berikut di komputer Anda:

- **Node.js**: Versi lts/iron (v22+)
- **pnpm**: Versi 10+ (`npm install -g pnpm`)
- **PHP**: Versi 8.3+
- **Composer**: Versi 2+
- **Web Server**: Laragon (Direkomendasikan) atau Apache/Nginx

---

## 🚀 Setup Awal

### 1. Clone Repositori

```bash
git clone git@github.com:ab1-team/pamsides.git
cd pamsides
```

### 2. Setup Root (Husky & Git Hooks)

Di direktori utama (root), jalankan pnpm install untuk mengaktifkan Husky.

```bash
pnpm install
```

_Langkah ini akan secara otomatis menjalankan script `prepare` yang menginisialisasi Git Hooks._

### 3. Setup Backend (Laravel)

Pindah ke direktori `backend` dan lakukan instalasi:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

_Jangan lupa sesuaikan konfigurasi database Anda di file `.env`._

### 4. Setup Frontend (Vue.js)

Pindah ke direktori `frontend` dan lakukan instalasi:

```bash
cd ../frontend
pnpm install
```

---

## 💻 Menjalankan Aplikasi

### Backend

Pastikan server database Anda menyala (mysql), lalu jalankan:

```bash
php artisan serve
```

### Frontend

Jalankan dev server Vite:

```bash
pnpm run dev
```

---

## 🤝 Standar Koding & Git Hooks

Proyek ini menggunakan **Husky** dan **lint-staged** untuk menjaga kualitas kode. Setiap kali Anda melakukan `git commit`, sistem akan otomatis menjalankan:

- **Frontend**: `oxlint`, `eslint`, dan `prettier` pada file `.js`, `.vue`, dan `.json`.
- **Backend**: `php artisan pint` untuk merapikan kode PHP.

### Jika Commit Gagal (Lint Error):

Jika terdapat error saat commit, perbaiki kode sesuai saran linter, lalu `git add` kembali file tersebut dan ulangi commit.

> [!IMPORTANT]
> Jangan gunakan flag `--no-verify` saat commit kecuali benar-benar diperlukan, agar kualitas kode tim tetap terjaga.
