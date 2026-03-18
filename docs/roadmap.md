# Roadmap Pengembangan Sistem Instalasi Sambungan Air Baru

## Tech Stack
- **Backend**: Laravel (REST API)
- **Frontend**: Vue.js (SPA)
- **Database**: MySQL
- **Autentikasi**: Laravel Sanctum

---

## Phase 1 — Fondasi & Autentikasi
**Estimasi: 1–2 minggu**

### Backend
- Setup project Laravel + konfigurasi MySQL
- Install & konfigurasi Laravel Sanctum
- Buat migration semua tabel (sesuai skema database)
- Buat Model & Eloquent relationship
- Sistem autentikasi (login, logout, refresh token)
- Role & Permission (admin, surveyor, teknisi, pelanggan)
- Seeder data awal (user admin, contoh paket instalasi)

### Frontend
- Setup project Vue.js + Vue Router + Pinia (state management)
- Konfigurasi Axios untuk komunikasi API
- Halaman login
- Layout dasar per role (admin, surveyor, teknisi)
- Guard route berdasarkan role

---

## Phase 2 — Manajemen Paket & Master Data
**Estimasi: 1 minggu**

### Backend
- CRUD endpoint `installation_packages`
- CRUD endpoint `water_tariff_blocks` (nested dalam paket)
- Validasi blok tarif tidak boleh overlap range pemakaian

### Frontend
- Halaman manajemen paket instalasi (Admin)
- Form tambah/edit paket beserta blok tarif progresifnya
- Tampilan daftar paket dengan detail tarif per blok

---

## Phase 3 — Alur Instalasi (Tiket)
**Estimasi: 2–3 minggu**

### Backend
- Endpoint pengajuan sambungan baru (create ticket) — status awal `pending`
- Endpoint daftar tiket dengan filter status & pencarian
- Endpoint detail tiket
- State machine tiket: validasi transisi status yang diizinkan
- Endpoint input hasil survey (upload foto, input jarak & material) — status → `surveyed`
- Endpoint konfirmasi pembayaran instalasi — status → `unpaid` lalu `processing`
- Endpoint input hasil instalasi (upload foto meteran, angka meter awal) — status → `processing`
- Endpoint verifikasi & aktivasi oleh admin — status → `completed`
- Auto-generate `customer_code` saat aktivasi
- Laravel Storage untuk upload & serving foto

### Frontend
- Form pengajuan sambungan baru (Admin) — input nama, NIK, alamat, pin peta
- Daftar tiket dengan badge status (Admin)
- Detail tiket lengkap dengan timeline status
- Halaman tugas surveyor — daftar tiket `pending`, form input hasil survey + upload foto
- Halaman tugas teknisi — daftar SPK digital tiket `processing`, form input angka meter + upload foto
- Halaman verifikasi & aktivasi (Admin) — review foto + tombol aktifkan
- Integrasi peta (Google Maps / Leaflet) untuk pin koordinat lokasi

---

## Phase 4 — Sistem Penagihan Bulanan
**Estimasi: 2–3 minggu**

### Alur Bulanan
```
Teknisi catat meter → Admin generate tagihan → Pelanggan bayar → Admin konfirmasi
```

### Backend
**Pencatatan meter:**
- Endpoint daftar pelanggan yang belum dicatat meternya bulan ini (tugas teknisi)
- Endpoint input angka meter bulanan per pelanggan + upload foto meteran
- Validasi: angka meter baru tidak boleh lebih kecil dari bulan sebelumnya

**Generate tagihan:**
- Endpoint generate tagihan manual oleh admin (trigger setelah semua meter tercatat)
- Logika kalkulasi tagihan:
  - Ambil `meter_reading` bulan ini vs bulan lalu dari tabel `meter_readings`
  - Hitung pemakaian (m³)
  - Kalkulasi biaya pemakaian menggunakan blok tarif progresif sesuai paket pelanggan
  - Tambahkan abodemen dari paket
  - Cek tagihan 2 bulan lalu — jika belum lunas saat jatuh tempo, tambahkan denda di tagihan bulan ini
- Endpoint daftar tagihan per pelanggan
- Endpoint detail tagihan (breakdown biaya)
- Endpoint konfirmasi pembayaran tagihan oleh admin
- Endpoint rekap tagihan (filter bulan, status)

### Frontend
- Halaman tugas pencatatan meter (Teknisi) — daftar pelanggan bulan ini, form input angka meter + upload foto
- Indikator progres pencatatan meter bulan ini (Admin) — misal "120 dari 150 pelanggan sudah dicatat"
- Tombol generate tagihan bulanan (Admin) — aktif setelah semua meter tercatat
- Halaman tagihan pelanggan — daftar tagihan bulanan dengan status
- Detail tagihan — breakdown pemakaian, abodemen, denda (jika ada)
- Halaman konfirmasi pembayaran (Admin)
- Rekap & riwayat pembayaran

---

## Phase 5 — Laporan & Dashboard
**Estimasi: 1–2 minggu**

### Backend
- Endpoint statistik dashboard (jumlah pelanggan aktif, tiket per status, pendapatan bulan ini)
- Endpoint laporan tagihan per periode
- Endpoint laporan instalasi per periode
- Export laporan ke PDF / Excel

### Frontend
- Dashboard Admin — ringkasan statistik, tiket terbaru, tagihan jatuh tempo
- Halaman laporan dengan filter periode
- Grafik pemakaian air per pelanggan
- Fitur export laporan

---

## Phase 6 — Polish & Deployment
**Estimasi: 1 minggu**

- Testing (Feature test Laravel, unit test kalkulasi tagihan)
- Optimasi query (indexing MySQL)
- Konfigurasi environment production
- Setup server & deployment
- Dokumentasi API (Laravel Scribe / Swagger)

---

## Ringkasan Timeline

| Phase | Fokus | Estimasi |
|---|---|---|
| 1 | Fondasi & Autentikasi | 1–2 minggu |
| 2 | Master Data & Paket | 1 minggu |
| 3 | Alur Instalasi (Tiket) | 2–3 minggu |
| 4 | Sistem Penagihan | 2–3 minggu |
| 5 | Laporan & Dashboard | 1–2 minggu |
| 6 | Polish & Deployment | 1 minggu |
| **Total** | | **8–12 minggu** |

---

## Urutan Prioritas Pengembangan

1. Phase 1 & 2 harus selesai sebelum phase lainnya — fondasi tidak bisa dilewati
2. Phase 3 & 4 adalah inti sistem — dikerjakan berurutan
3. Phase 5 & 6 bisa dikerjakan paralel di akhir
