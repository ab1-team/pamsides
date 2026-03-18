# Skema Database — Sistem Instalasi Sambungan Air Baru

**Database**: MySQL  
**ORM**: Laravel Eloquent  
**Konvensi**: snake_case, bigint auto increment sebagai primary key, timestamps di setiap tabel

---

## Daftar Tabel

1. [users](#1-users)
2. [installation_packages](#2-installation_packages)
3. [water_tariff_blocks](#3-water_tariff_blocks)
4. [installation_tickets](#4-installation_tickets)
5. [survey_results](#5-survey_results)
6. [payments](#6-payments)
7. [customers](#7-customers)
8. [meter_readings](#8-meter_readings)
9. [monthly_bills](#9-monthly_bills)
10. [bill_payments](#10-bill_payments)

---

## 1. `users`

Menyimpan semua pengguna sistem beserta rolenya.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint` | Primary key, auto increment |
| `name` | `varchar(255)` | Nama lengkap |
| `email` | `varchar(255)` | Unique, untuk login |
| `password` | `varchar(255)` | Bcrypt hash |
| `role` | `enum('admin','surveyor','teknisi','pelanggan')` | Role pengguna |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

## 2. `installation_packages`

Master data paket instalasi. Setiap paket memiliki biaya pasang, abodemen bulanan, dan denda keterlambatan yang berbeda.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint` | Primary key, auto increment |
| `name` | `varchar(255)` | Nama paket, misal "Paket Reguler" |
| `installation_fee` | `decimal(12,2)` | Biaya instalasi sambungan baru |
| `monthly_abodemen` | `decimal(12,2)` | Biaya tetap bulanan |
| `late_penalty` | `decimal(12,2)` | Nilai denda jika terlambat bayar |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

## 3. `water_tariff_blocks`

Blok tarif progresif per paket. Satu paket memiliki beberapa blok range pemakaian dengan harga berbeda.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint` | Primary key, auto increment |
| `package_id` | `bigint` | FK → `installation_packages.id` |
| `usage_min_m3` | `int` | Batas bawah pemakaian (m³) |
| `usage_max_m3` | `int` | Batas atas pemakaian (m³), `null` = tidak terbatas |
| `price_per_m3` | `decimal(12,2)` | Harga per m³ pada blok ini |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

**Catatan**: Pastikan tidak ada overlap antar blok dalam satu paket. Validasi dilakukan di layer aplikasi.

**Contoh data**:
| package_id | usage_min_m3 | usage_max_m3 | price_per_m3 |
|---|---|---|---|
| Paket A | 0 | 10 | 1.500 |
| Paket A | 11 | 20 | 2.000 |
| Paket A | 21 | null | 2.500 |

---

## 4. `installation_tickets`

Tiket pengajuan sambungan baru. Inti dari alur instalasi — setiap calon pelanggan memiliki satu tiket.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint` | Primary key, auto increment |
| `package_id` | `bigint` | FK → `installation_packages.id` |
| `applicant_name` | `varchar(255)` | Nama calon pelanggan |
| `nik` | `varchar(20)` | Nomor Induk Kependudukan |
| `address` | `text` | Alamat lengkap |
| `lat` | `decimal(10,7)` | Koordinat latitude |
| `lng` | `decimal(10,7)` | Koordinat longitude |
| `status` | `enum('pending','surveyed','unpaid','processing','completed')` | Status tiket saat ini |
| `created_by` | `bigint` | FK → `users.id` (admin yang input) |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

**Alur status**:
```
pending → surveyed → unpaid → processing → completed
```

---

## 5. `survey_results`

Hasil survey lapangan oleh surveyor. Satu tiket memiliki satu hasil survey.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint` | Primary key, auto increment |
| `ticket_id` | `bigint` | FK → `installation_tickets.id` |
| `surveyor_id` | `bigint` | FK → `users.id` |
| `distance_to_pipe_m` | `int` | Jarak rumah ke pipa utama (meter) |
| `material_notes` | `text` | Catatan kebutuhan material (pipa, fitting, dll) |
| `photo_url` | `varchar(500)` | Path foto kondisi lokasi |
| `surveyed_at` | `timestamp` | Waktu survey dilakukan |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

## 6. `payments`

Rekam pembayaran biaya instalasi (bukan tagihan bulanan). Satu tiket bisa memiliki beberapa record pembayaran jika ada revisi.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint` | Primary key, auto increment |
| `ticket_id` | `bigint` | FK → `installation_tickets.id` |
| `amount` | `decimal(12,2)` | Jumlah yang dibayarkan |
| `type` | `enum('installation_fee')` | Jenis pembayaran (dapat dikembangkan) |
| `status` | `enum('pending','confirmed')` | Status konfirmasi pembayaran |
| `confirmed_by` | `bigint` | FK → `users.id` (admin yang konfirmasi), nullable |
| `paid_at` | `timestamp` | Waktu pembayaran, nullable |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

## 7. `customers`

Data pelanggan aktif. Terbentuk otomatis saat admin mengaktifkan tiket yang sudah selesai instalasi.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint` | Primary key, auto increment |
| `ticket_id` | `bigint` | FK → `installation_tickets.id` |
| `user_id` | `bigint` | FK → `users.id` (akun pelanggan) |
| `customer_code` | `varchar(50)` | ID pelanggan resmi, unique, auto-generated |
| `initial_meter_reading` | `int` | Angka meter awal saat aktivasi |
| `meter_photo_url` | `varchar(500)` | Foto meteran yang sudah terpasang |
| `activated_at` | `timestamp` | Waktu aktivasi resmi |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

## 8. `meter_readings`

Catatan angka meter bulanan per pelanggan oleh teknisi. Satu record = satu pelanggan satu bulan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint` | Primary key, auto increment |
| `customer_id` | `bigint` | FK → `customers.id` |
| `recorded_by` | `bigint` | FK → `users.id` (teknisi yang catat) |
| `reading_year` | `int` | Tahun pencatatan |
| `reading_month` | `int` | Bulan pencatatan (1–12) |
| `meter_value` | `int` | Angka meter yang tercatat |
| `photo_url` | `varchar(500)` | Foto meteran saat pencatatan |
| `recorded_at` | `timestamp` | Waktu pencatatan dilakukan |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

**Catatan**: Kombinasi `customer_id + reading_year + reading_month` harus unique (satu pelanggan hanya boleh satu catatan per bulan).

---

## 9. `monthly_bills`

Tagihan bulanan pelanggan. Satu baris = satu tagihan untuk satu bulan tertentu.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint` | Primary key, auto increment |
| `customer_id` | `bigint` | FK → `customers.id` |
| `billing_period_year` | `int` | Tahun periode tagihan |
| `billing_period_month` | `int` | Bulan periode tagihan (1–12) |
| `meter_reading_start` | `int` | Angka meter awal periode |
| `meter_reading_end` | `int` | Angka meter akhir periode |
| `usage_m3` | `int` | Total pemakaian (end - start) |
| `usage_charge` | `decimal(12,2)` | Biaya pemakaian (hasil kalkulasi progresif) |
| `abodemen` | `decimal(12,2)` | Biaya abodemen bulan ini (snapshot dari paket) |
| `penalty_amount` | `decimal(12,2)` | Total denda dari tagihan sebelumnya yang terlambat |
| `total_amount` | `decimal(12,2)` | Total tagihan (usage_charge + abodemen + penalty_amount) |
| `status` | `enum('unpaid','paid')` | Status pembayaran tagihan |
| `due_date` | `date` | Tanggal jatuh tempo pembayaran |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

**Logika penetapan denda (otomatis saat generate tagihan):**

Ketika admin men-trigger generate tagihan bulan `N`, sistem menjalankan langkah berikut untuk setiap pelanggan aktif:

1. Cek tagihan bulan `N-2` — jika statusnya masih `unpaid`, pelanggan dianggap terlambat
2. Ambil nilai `late_penalty` dari `installation_packages` sesuai paket pelanggan tersebut
3. Masukkan nilai tersebut ke kolom `penalty_amount` pada tagihan bulan `N` yang baru dibuat
4. Jika tagihan bulan `N-2` sudah `paid`, maka `penalty_amount` diisi `0`

**Contoh:**
| Bulan Tagihan | Bulan Bayar Normal | Cek Keterlambatan Dari | Denda Muncul Di |
|---|---|---|---|
| Januari | Februari | Januari (cek saat buat tagihan Maret) | Maret |
| Februari | Maret | Februari (cek saat buat tagihan April) | April |

Setiap keterlambatan dihitung terpisah — jika Januari dan Februari sama-sama terlambat, denda keduanya muncul di bulan yang berbeda (Maret dan April), tidak digabung.

---

## 10. `bill_payments`

Rekam jejak pembayaran tagihan bulanan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint` | Primary key, auto increment |
| `bill_id` | `bigint` | FK → `monthly_bills.id` |
| `amount_paid` | `decimal(12,2)` | Jumlah yang dibayarkan |
| `confirmed_by` | `bigint` | FK → `users.id` (admin yang konfirmasi) |
| `paid_at` | `timestamp` | Waktu pembayaran dikonfirmasi |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

## Relasi Antar Tabel

```
installation_packages ──< water_tariff_blocks
installation_packages ──< installation_tickets
installation_tickets  ──< survey_results
installation_tickets  ──< payments
installation_tickets  ──o  customers
customers             ──< meter_readings
customers             ──< monthly_bills
monthly_bills         ──< bill_payments
users                 ──< survey_results    (sebagai surveyor)
users                 ──< meter_readings    (sebagai teknisi pencatat)
users                 ──o  customers        (akun pelanggan)
```

---

## Index yang Direkomendasikan

| Tabel | Kolom | Alasan |
|---|---|---|
| `installation_tickets` | `status` | Filter tiket per status sering dilakukan |
| `installation_tickets` | `nik` | Pencarian calon pelanggan by NIK |
| `customers` | `customer_code` | Lookup pelanggan by kode |
| `meter_readings` | `customer_id, reading_year, reading_month` | Lookup meter bulan ini & bulan lalu saat kalkulasi tagihan |
| `monthly_bills` | `customer_id, billing_period_year, billing_period_month` | Query tagihan per pelanggan per bulan |
| `monthly_bills` | `status` | Filter tagihan unpaid untuk proses denda |
| `water_tariff_blocks` | `package_id` | Lookup blok tarif saat kalkulasi tagihan |
