# Pamsides Project Setup Guide

Selamat datang di repositori **Pamsides**. Ikuti langkah-langkah di bawah ini untuk melakukan setup awal setelah melakukan clone repositori ini.

## 📖 Dokumentasi Proyek

- [🚀 Roadmap Proyek](docs/roadmap.md)
- [🗄️ Struktur Database](docs/database.md)

## 🛠 Prasyarat (Prerequisites)

Pastikan Anda sudah menginstal software berikut di komputer Anda:

- **Node.js**: Versi lts/iron (v22+)
- **pnpm**: Versi 10+ (`npm install -g pnpm`)
- **PHP**: Versi 8.3+
- **Composer**: Versi 2+
- **Web Server**: Laragon (Direkomendasikan) atau Apache/Nginx

---

## 🚀 Setup Awal

### 1. Clone Repositori

Clone repositori dari branch `develop`:

```bash
git clone -b develop git@github.com:ab1-team/pamsides.git
cd pamsides
```

### 2. Alur Kerja Git (Git Workflow)

Untuk perbaikan, update, atau penambahan fitur apapun, **wajib** membuat branch baru dari `develop` dengan format:
`feature/[nama-fitur]` atau `fix/[nama-bug]`.

Contoh:

```bash
git checkout develop
git pull origin develop
git checkout -b feature/update-dokumentasi
```

Setelah selesai, lakukan push dan buat **Pull Request** ke branch `develop`.

### 3. Pesan Commit (Conventional Commits)

Gunakan standar [Conventional Commits](https://www.conventionalcommits.org/) dalam setiap pesan commit. Format umum:
`<type>[optional scope]: <description>`

Contoh:

- `feat(auth): tambah integrasi login google`
- `fix(ui): perbaiki padding sidebar yang tidak rata`
- `docs: update panduan setup awal`
- `chore: update dependensi husky`

### 4. Setup Root (Husky & Git Hooks)

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

_Sesuaikan konfigurasi database di `.env` (DB_DATABASE, DB_USERNAME, DB_PASSWORD)._

**Buat database** sesuai `DB_DATABASE` di MySQL, lalu jalankan:

```bash
php artisan migrate --seed
```

### 4. Setup Frontend (Vue.js)

Pindah ke direktori `frontend` dan lakukan instalasi:

```bash
cd ../frontend
pnpm install
cp .env.example .env
```

**Penting:** Edit `frontend/.env` dan sesuaikan `VITE_BACKEND_URL` dengan path folder Anda.
Format: `http://localhost/<nama-folder-project>/backend/public`.

Contoh jika folder Anda bernama `pamsides-v2`:
```env
VITE_BACKEND_URL=http://localhost/pamsides-v2/backend/public
```

Contoh jika folder Anda bernama `pamsides`:
```env
VITE_BACKEND_URL=http://localhost/pamsides/backend/public
```

_Nama folder project tidak harus sama dengan aslinya — cukup sesuaikan `VITE_BACKEND_URL` di `.env`._

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

### 🔐 Akun Default (Hasil Seeder)

| Role      | Email                       | Password |
|-----------|-----------------------------|----------|
| Admin     | admin@pamsides.test         | password |
| Surveyor  | surveyor@pamsides.test      | password |
| Teknisi   | teknisi@pamsides.test       | password |
| Pelanggan | pelanggan@pamsides.test     | password |

> Password default bisa berbeda — cek `database/seeders/UserSeeder.php` untuk memastikan.

---

## 🤝 Standar Koding & Git Hooks

Proyek ini menggunakan **Husky** dan **lint-staged** untuk menjaga kualitas kode. Setiap kali Anda melakukan `git commit`, sistem akan otomatis menjalankan:

- **Frontend**: `oxlint`, `eslint`, dan `prettier` pada file `.js`, `.vue`, dan `.json`.
- **Backend**: `php artisan pint` untuk merapikan kode PHP.

### Jika Commit Gagal (Lint Error):

Jika terdapat error saat commit, perbaiki kode sesuai saran linter, lalu `git add` kembali file tersebut dan ulangi commit.

> [!IMPORTANT]
> Jangan gunakan flag `--no-verify` saat commit kecuali benar-benar diperlukan, agar kualitas kode tim tetap terjaga.
