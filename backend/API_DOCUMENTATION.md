# Dokumentasi API PAM Sides

## Daftar Isi
1. [Rute API](#1-rute-api)
2. [Checklist Tabel yang Belum Diimplementasi](#2-checklist-tabel-yang-belum-diimplementasi)

---

## 1. Rute API

### RUTE PUBLIK (Tanpa Autentikasi)

| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| POST | `/api/login` | AuthController | `{ success, data: { user, token } }` |
| GET | `/api/health` | Closure | `{"status":"OK"}` |

---

### TERAUTENTIKASI (Semua Role)

| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| POST | `/api/logout` | AuthController | `{ success, data: { message } }` |
| POST | `/api/refresh` | AuthController | `{ success, data: { token } }` |
| GET | `/api/me` | AuthController | Profil pengguna + data pelanggan |
| PUT | `/api/me` | AuthController | Profil yang diperbarui |
| PUT | `/api/me/password` | AuthController | Konfirmasi perubahan kata sandi |
| POST | `/api/me/avatar` | AuthController | URL avatar |
| GET | `/api/settings/kecamatan` | SettingController | Daftar kecamatan |
| GET | `/api/settings/desa` | SettingController | Daftar desa |
| GET | `/api/settings/payment-mode` | SettingController | Pengaturan mode pembayaran |

---

### RUTE BERSAMA (Admin + Surveyor)

| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/installation-tickets` | InstallationTicketController | Tiket (paginasi) |
| GET | `/api/installation-tickets/{id}` | InstallationTicketController | Detail satu tiket |

---

### RUTE BERSAMA (Admin + Surveyor) - Pengiriman Survei

| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| POST | `/api/installation-tickets/{id}/survey` | SurveyResultController | Hasil survei + pembaruan status |

---

### RUTE BERSAMA (Admin + Teknisi)

| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/test-teknisi` | Closure | `{message: "Kamu teknisi!"}` |
| GET | `/api/meter-readings/completed` | MeterReadingController | Daftar pembacaan selesai |
| GET | `/api/dashboard/statistics` | DashboardController | Statistik dashboard |
| GET | `/api/meter-readings/pending` | MeterReadingController | Pelanggan pending (belum dicatat) |
| POST | `/api/meter-readings` | MeterReadingController | Buat pembacaan + generate tagihan otomatis |
| GET | `/api/meter-readings/{id}` | MeterReadingController | Detail satu pembacaan |
| PUT | `/api/meter-readings/{id}` | MeterReadingController | Perbarui pembacaan |
| DELETE | `/api/meter-readings/{id}` | MeterReadingController | Hapus pembacaan |
| GET | `/api/customers` | CustomerController | Daftar pelanggan |
| GET | `/api/customers/search` | CustomerController | Cari pelanggan |
| GET | `/api/customers/{id}` | CustomerController | Detail pelanggan |
| GET | `/api/monthly-bills` | MonthlyBillController | Daftar tagihan |
| GET | `/api/monthly-bills/usage` | MonthlyBillController | Laporan penggunaan |
| POST | `/api/installation-tickets/{id}/installation-result` | InstallationResultController | Catat hasil pemasangan |

---

### RUTE KHUSUS ADMIN

| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/test-admin` | Closure | `{message: "Kamu admin!"}` |

#### Pengguna
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/users` | UserController | Daftar pengguna |
| POST | `/api/users` | UserController | Buat pengguna |
| GET | `/api/users/{id}` | UserController | Detail pengguna |
| PUT | `/api/users/{id}` | UserController | Perbarui pengguna |
| DELETE | `/api/users/{id}` | UserController | Hapus pengguna |

#### Desa
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/villages` | VillageController | Daftar desa |
| POST | `/api/villages` | VillageController | Buat desa |
| GET | `/api/villages/{id}` | VillageController | Detail desa |
| PUT | `/api/villages/{id}` | VillageController | Perbarui desa |
| DELETE | `/api/villages/{id}` | VillageController | Hapus desa |

#### Pelanggan (CRUD Admin)
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/customers` | CustomerController | Daftar pelanggan |
| POST | `/api/customers` | CustomerController | Buat pelanggan |
| GET | `/api/customers/{id}` | CustomerController | Detail pelanggan |
| PUT | `/api/customers/{id}` | CustomerController | Perbarui pelanggan |
| DELETE | `/api/customers/{id}` | CustomerController | Hapus pelanggan |

#### Paket Pemasangan
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/installation-packages` | InstallationPackageController | Daftar paket |
| POST | `/api/installation-packages` | InstallationPackageController | Buat paket |
| GET | `/api/installation-packages/{id}` | InstallationPackageController | Detail paket |
| PUT | `/api/installation-packages/{id}` | InstallationPackageController | Perbarui paket |
| DELETE | `/api/installation-packages/{id}` | InstallationPackageController | Hapus paket |

#### Blok Tarif Air (nested di bawah paket)
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/installation-packages/{id}/water-tariff-blocks` | WaterTariffBlockController | Blok tarif |
| POST | `/api/installation-packages/{id}/water-tariff-blocks` | WaterTariffBlockController | Buat blok tarif |
| GET | `/api/installation-packages/{id}/water-tariff-blocks/{bid}` | WaterTariffBlockController | Detail blok tarif |
| PUT | `/api/installation-packages/{id}/water-tariff-blocks/{bid}` | WaterTariffBlockController | Perbarui blok tarif |
| DELETE | `/api/installation-packages/{id}/water-tariff-blocks/{bid}` | WaterTariffBlockController | Hapus blok tarif |

#### Tiket Pemasangan
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| POST | `/api/installation-tickets` | InstallationTicketController | Buat tiket (draft) |
| PUT | `/api/installation-tickets/{id}/register` | InstallationTicketController | Daftarkan pemasangan |
| PATCH | `/api/installation-tickets/{id}/transition` | InstallationTicketController | Transisi status |
| GET | `/api/installation-tickets-unpaid` | InstallationTicketController | Tiket belum bayar |
| POST | `/api/installation-tickets/{id}/payment` | PaymentController | Catat pembayaran |
| POST | `/api/installation-tickets/{id}/advance-stage` | InstallationTicketController | Majukan tahap |
| POST | `/api/installation-tickets/{id}/activate` | ActivationController | Aktifkan pelanggan |
| GET | `/api/reports/installations` | InstallationTicketController | Laporan pemasangan |

#### Hasil Survei
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/survey-results` | SurveyResultController | Daftar survei |
| GET | `/api/survey-results/{id}` | SurveyResultController | Detail survei |
| PUT | `/api/survey-results/{id}` | SurveyResultController | Perbarui survei |
| POST | `/api/survey-results/{id}` | SurveyResultController | Perbarui survei (alternatif) |
| DELETE | `/api/survey-results/{id}` | SurveyResultController | Hapus survei |

#### Laporan Gangguan
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/trouble-reports` | TroubleReportController | Daftar laporan |
| GET | `/api/trouble-reports/{id}` | TroubleReportController | Detail laporan |
| PATCH | `/api/trouble-reports/{id}/status` | TroubleReportController | Perbarui status |

#### Penagihan
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/bills/recap` | BillingController | Rekap tagihan |
| POST | `/api/bills/generate` | BillingController | Generate tagihan |
| GET | `/api/bills/{id}` | BillingController | Detail tagihan |

#### Tagihan Bulanan
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/monthly-bills` | MonthlyBillController | Tagihan |
| GET | `/api/monthly-bills/{id}` | MonthlyBillController | Detail tagihan |
| GET | `/api/monthly-bills/usage` | MonthlyBillController | Penggunaan |
| POST | `/api/monthly-bills/{id}/pay` | MonthlyBillController | Bayar tagihan |
| DELETE | `/api/monthly-bills/{id}` | MonthlyBillController | Rollback tagihan yang sudah dibayar |
| POST | `/api/monthly-bills/generate` | MonthlyBillController | Generate tagihan |
| GET | `/api/reports/bills` | MonthlyBillController | Laporan tagihan |

#### Laporan (Admin)
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/reports/billing` | ReportController | Laporan penagihan |
| GET | `/api/reports/installation` | ReportController | Laporan pemasangan |
| GET | `/api/reports/billing/export-csv` | ReportController | CSV penagihan |
| GET | `/api/reports/billing/export-pdf` | ReportController | PDF penagihan |
| GET | `/api/reports/installation/export-csv` | ReportController | CSV pemasangan |
| GET | `/api/reports/installation/export-pdf` | ReportController | PDF pemasangan |

#### Transaksi (Akuntansi)
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/transactions` | TransactionController | Daftar transaksi (paginasi) |
| POST | `/api/transactions` | TransactionController | Buat transaksi |
| GET | `/api/transactions/{id}` | TransactionController | Detail transaksi |
| PUT | `/api/transactions/{id}` | TransactionController | Perbarui transaksi |
| DELETE | `/api/transactions/{id}` | TransactionController | Hapus transaksi |
| POST | `/api/generate-amount` | GenerateAmountController | Generate jumlah akun |

#### Amount
| Method | Endpoint | Controller | Response |
|--------|----------|------------|----------|
| GET | `/api/amount` | AmountController | Debit/kredit by bulan, tahun, account_id |

#### Jenis Transactions
| Method | Endpoint | Controller | Response |
|--------|----------|------------|----------|
| GET | `/api/jenis-transactions` | JenisTransactionController | List semua jenis transaksi |
| POST | `/api/jenis-transactions` | JenisTransactionController | Buat jenis transaksi |
| GET | `/api/jenis-transactions/{id}` | JenisTransactionController | Detail jenis transaksi |
| PUT | `/api/jenis-transactions/{id}` | JenisTransactionController | Update jenis transaksi |
| DELETE | `/api/jenis-transactions/{id}` | JenisTransactionController | Hapus jenis transaksi |

#### Inventaris (CRUD)
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/inventaris` | InventoryController | List inventaris (filter: `kategori`, `jenis`, `status`, `q`, `tgl_dari`, `tgl_sampai`, `per_page`) |
| GET | `/api/inventaris/{id}` | InventoryController | Detail inventaris |
| POST | `/api/inventaris` | InventoryController | Buat inventaris |
| PUT | `/api/inventaris/{id}` | InventoryController | Perbarui inventaris |
| DELETE | `/api/inventaris/{id}` | InventoryController | Hapus inventaris |

**Body `POST /api/inventaris`:**
```json
{
  "nama_barang": "Laptop Asus ROG",
  "tgl_beli": "2026-07-01",
  "unit": 1,
  "harsat": 10000000,
  "umur_ekonomis": 48,
  "jenis": "1",
  "kategori": "4",
  "status": "Baik",
  "tgl_validasi": null
}
```

#### Jurnal Umum - Form Dispatch & Inventaris
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/transaksi/jurnal-umum/form` | JurnalUmumController | Dispatch form (4 skenario: inventaris pembelian, hapus inventaris, auto-susut, jurnal biasa) |
| POST | `/api/transaksi/inventaris` | JurnalUmumController | Pembelian inventaris (insert `transactions` + `inventories`) |
| POST | `/api/transaksi/inventaris/{inventory}/hapus` | JurnalUmumController | Hapus/jual/revaluasi/rusak/hilang inventaris |

**Query `GET /api/transaksi/jurnal-umum/form`:**
- `tgl_transaksi` (required, date)
- `jenis_transaksi` (required, integer)
- `sumber_dana` (required, kode akun)
- `disimpan_ke` (required, kode akun)

**Response skenario (berdasarkan kombinasi sumber_dana + disimpan_ke + jenis_transaksi):**
- Pembelian (disimpan_ke di `1.2.01.*` atau `1.2.03.*`):
  ```json
  {
    "success": true,
    "data": {
      "form_type": "inventaris",
      "fields": ["nama_barang", "jumlah", "harga_satuan", "umur_ekonomis", "relasi"]
    }
  }
  ```
- Hapus/Jual/Revaluasi (sumber_dana `1.2.01.*`/`1.2.02.*` + disimpan_ke `5.3.02.01` + jenis_transaksi=2):
  ```json
  {
    "success": true,
    "data": {
      "form_type": "hapus_inventaris",
      "fields": ["inventory_id", "alasan", "unit", "harsat", "harga_jual"],
      "inventaris_list": [
        {"id": 12, "nama_barang": "Laptop", "unit": 3, "harsat": 10000000, "nilai_buku": 8500000}
      ]
    }
  }
  ```
- Auto-susut (disimpan_ke di `5.1.07.08/09/10`):
  ```json
  {
    "success": true,
    "data": {
      "form_type": "nominal",
      "fields": ["keterangan", "nominal", "relasi"],
      "prefill": {"nominal": 250000}
    }
  }
  ```
- Default:
  ```json
  {
    "success": true,
    "data": {
      "form_type": "nominal",
      "fields": ["keterangan", "nominal", "relasi"]
    }
  }
  ```

**Body `POST /api/transaksi/inventaris` (pembelian):**
```json
{
  "tgl_transaksi": "2026-07-01",
  "jenis_transaksi": 1,
  "sumber_dana": "1.1.01.01",
  "disimpan_ke": "1.2.01.04",
  "nama_barang": "Laptop Asus",
  "jumlah": 3,
  "harga_satuan": 10000000,
  "umur_ekonomis": 48,
  "relasi": "Toko ABC"
}
```
Response:
```json
{
  "success": true,
  "message": "Pembelian inventaris berhasil disimpan.",
  "data": {"transaksi_id": 1245, "inventory_id": 28, "total": 30000000}
}
```

**Body `POST /api/transaksi/inventaris/{id}/hapus`:**
```json
{
  "tgl_transaksi": "2026-07-15",
  "sumber_dana": "1.2.01.04",
  "disimpan_ke": "5.3.02.01",
  "alasan": "dijual",
  "unit": 1,
  "harsat": 1000000,
  "harga_jual": 800000
}
```
`alasan` ∈ `['hapus', 'dijual', 'revaluasi', 'rusak', 'hilang']`. Response:
```json
{
  "success": true,
  "message": "Penjualan 1 unit Laptop Asus",
  "data": {"transaksi_ids": [1246, 1247], "inventory_id": 28, "sisa_unit": 2}
}
```

#### Pelaporan
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/pelaporan` | PelaporanController | Daftar jenis laporan |
| GET | `/api/pelaporan/sub-laporan/{file}` | PelaporanController | Daftar sub laporan |
| POST | `/api/pelaporan/preview` | PelaporanController | Preview PDF (stub) |
| POST | `/api/pelaporan/excel` | PelaporanController | Ekspor Excel (stub) |
| POST | `/api/pelaporan/simpan-saldo` | PelaporanController | Rekalibrasi amount 1-12 (delegasi ke `GenerateAmountController::generate`) |

**Body `POST /api/pelaporan/simpan-saldo`:**
```json
{
  "tahun": 2026,
  "bulan": "05"
}
```
- `bulan` opsional: kosong → rekalk semua bulan 1-12 untuk tahun tsb; `01`-`12` → rekalk bulan tertentu.

Response:
```json
{
  "success": true,
  "message": "Saldo periode berhasil direkalibrasi.",
  "data": {"tahun": 2026, "bulan": ["05"], "jumlah_rekord": 5}
}
```

#### Pengaturan/SOP
| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/settings/sop` | SopController | Pengaturan SOP |
| POST | `/api/settings/sop/lembaga` | SopController | Perbarui lembaga |
| POST | `/api/settings/sop/pasang-baru` | SopController | Perbarui pasang baru |
| POST | `/api/settings/sop/sistem-tagihan` | SopController | Perbarui sistem tagihan |
| POST | `/api/settings/sop/logo` | SopController | Perbarui logo |
| POST | `/api/settings/sop/whatsapp` | SopController | Perbarui whatsapp |

---

### RUTE PELANGGAN

| Method | Endpoint | Controller | Respons |
|--------|----------|------------|---------|
| GET | `/api/test-pelanggan` | Closure | `{message: "Kamu pelanggan!"}` |
| GET | `/api/pelanggan/dashboard` | PelangganPortalController | Data dashboard |
| GET | `/api/pelanggan/bill-detail/{id?}` | PelangganPortalController | Detail tagihan |
| GET | `/api/pelanggan/bill-history` | PelangganPortalController | Riwayat tagihan |
| GET | `/api/pelanggan/profile` | PelangganPortalController | Profil pelanggan |
| POST | `/api/pelanggan/trouble-report` | TroubleReportController | Kirim laporan gangguan |

---

## 2. Checklist Tabel yang Belum Diimplementasi

### TABEL DENGAN MODEL TAPI TANPA CONTROLLER

- [x] **`accounts`** - Bagan akun (kode_akun, nama_akun, hierarki level)
  - Model: `Account.php` (relasi ke AkunLevel1/2/3)
  - Digunakan oleh: `TransactionController`, `PelaporanController`, `GenerateAmountController`
  - Belum ada controller CRUD

- [ ] **`amount`** - Saldo akun terhitung per periode (tahun, bulan, debit, kredit)
  - Tidak ada file model
  - Digunakan oleh: `GenerateAmountController` (akses langsung via DB::table)
  - DB trigger mengisi otomatis dari `transactions`

### TABEL TANPA MODEL DAN TANPA CONTROLLER

- [x] **`akun_level_1`** - Akun level 1 (lev1, kode_akun, nama_akun, jenis_mutasi)
  - Model: `AkunLevel1.php` (relasi: hasMany AkunLevel2, accounts)
  - Controller: - (belum ada)

- [x] **`akun_level_2`** - Akun level 2 (parent_id, lev1-4, kode_akun, nama_akun, jenis_mutasi)
  - Model: `AkunLevel2.php` (relasi: belongsTo AkunLevel1, hasMany AkunLevel3, accounts)
  - Controller: - (belum ada)

- [x] **`akun_level_3`** - Akun level 3 (parent_id, lev1-4, kode_akun, nama_akun, posisi, jenis_mutasi)
  - Model: `AkunLevel3.php` (relasi: belongsTo AkunLevel2, hasMany accounts)
  - Controller: - (belum ada)

- [x] **`ebudgeting`** - Entri e-budgeting (account_id, tahun, bulan, jumlah)
  - Model: `Ebudgeting.php` (relasi: belongsTo Account)
  - Controller: `EbudgetingController` (CRUD lengkap + bulkStore)
  - Migration ada: `2026_06_18_020404_create_ebudgeting_table.php`

- [x] **`jenis_transactions`** - Master jenis transaksi (nama_jt)
  - Model: `JenisTransaction.php`
  - Controller: `JenisTransactionController` (CRUD lengkap + search)
  - Migration ada: `2026_06_18_021046_create_jenis_transactions_table.php`

- [x] **`master_arus_kas`** - Master arus kas (nama_akun, debit, kredit, parent_id)
  - Model: `MasterArusKas.php` (relasi: hasMany children, belongsTo parent)
  - Controller: - (belum ada)
  - Migration ada: `2026_06_18_021550_create_master_arus_kas_table.php`

- [x] **`inventories`** - Inventaris barang (nama_barang, tgl_beli, unit, harsat, umur_ekonomis, jenis, kategori, status, tgl_validasi)
  - Model: `Inventory.php` (HasFactory)
  - Controller: `InventoryController` (CRUD lengkap)
  - Service: `InventoryService` (hitungBulan, hitungItemSatuan, hitungPenyusutan)
  - Integrasi jurnal umum: `JurnalUmumController` (form dispatch + 4 skenario: pembelian, hapus, jual, revaluasi, rusak, hilang, auto-susut)
  - Seeder: `InventorySampleSeeder`

### YANG HILANG/TIDAK LENGKAP

- [ ] **`PelaporanController::preview()`** - Mengembalikan view template PDF yang tidak ada
  - `resources/views/pelaporan/pdf_template.blade.php` tidak ditemukan

- [ ] **View Pelaporan** - Direktori `resources/views/pelaporan/` tidak ada
  - Method: `preview()`, `exportExcel()` masih stub
  - Method private: `cover`, `surat_pengantar`, `jurnal_transaksi`, `neraca_saldo`, `neraca`, `laba_rugi`, `e_budgeting`, `tutup_buku_*`, `buku_besar`, `calkk`, `LPM`, `arus_kas`, `ati`, `atb`, `piutang_komisi` - semua implementasi ada di controller (return JSON view_target, FE yang render)
  - `simpanSaldo()` SUDAH diimplementasi (delegasi rekalk amount 1-12)

### TABEL FRAMEWORK LARAVEL (dikelola otomatis)

- [ ] `cache` - Cache Laravel (otomatis)
- [ ] `cache_locks` - Cache locks Laravel (otomatis)
- [ ] `failed_jobs` - Failed jobs Laravel (otomatis)
- [ ] `job_batches` - Job batches Laravel (otomatis)
- [ ] `jobs` - Queue jobs Laravel (otomatis)
- [ ] `migrations` - Tabel migrations Laravel (otomatis)
- [ ] `password_reset_tokens` - Reset kata sandi (otomatis)
- [ ] `personal_access_tokens` - Token Sanctum (otomatis, via model)
- [ ] `sessions` - Sesi Laravel (otomatis)

### TABEL YANG SUDAH DIIMPLEMENTASI PENUH (model + controller + routes)

- [x] `users` - UserController + model User
- [x] `villages` - VillageController + model Village
- [x] `customers` - CustomerController + model Customer
- [x] `installation_packages` - InstallationPackageController + model InstallationPackage
- [x] `water_tariff_blocks` - WaterTariffBlockController + model WaterTariffBlock
- [x] `installation_tickets` - InstallationTicketController + model InstallationTicket
- [x] `payments` - PaymentController + model Payment
- [x] `survey_results` - SurveyResultController + model SurveyResult
- [x] `trouble_reports` - TroubleReportController + model TroubleReport
- [x] `meter_readings` - MeterReadingController + model MeterReading
- [x] `monthly_bills` - MonthlyBillController + model MonthlyBill
- [x] `bill_payments` - model BillPayment (digunakan oleh MonthlyBillController)
- [x] `settings` - SettingController + model Setting
- [x] `transactions` - TransactionController + model Transaction
- [x] `jenis_transactions` - JenisTransactionController + model JenisTransaction
- [x] `jenis_laporans` - model JenisLaporan (digunakan oleh PelaporanController)
- [x] `sub_laporans` - model SubLaporan (digunakan oleh PelaporanController)
- [x] `akun_level_1` - model AkunLevel1 (relasi: hasMany AkunLevel2, accounts)
- [x] `akun_level_2` - model AkunLevel2 (relasi: belongsTo AkunLevel1, hasMany AkunLevel3, accounts)
- [x] `akun_level_3` - model AkunLevel3 (relasi: belongsTo AkunLevel2, hasMany accounts)
- [x] `ebudgeting` - EbudgetingController + model Ebudgeting
- [x] `master_arus_kas` - model MasterArusKas (relasi: hasMany children, belongsTo parent)
- [x] `inventories` - InventoryController + JurnalUmumController + model Inventory + InventoryService

---

## Ringkasan

| Kategori | Jumlah |
|----------|--------|
| Total tabel di DB | 34 |
| Tabel dengan model | 27 |
| Tabel dengan controller | 22 |
| Tabel dengan rute API | 22 |
| **Tabel tanpa model** | **1** (`amount` — diakses via DB::table) |
| **Tabel tanpa controller/rute** | **5** (`accounts` read-only via `AccountController`, `akun_level_1/2/3`, `master_arus_kas`) |
| **Masalah kritis** | **1** (view `resources/views/pelaporan/pdf_template.blade.php` hilang — `preview()` masih stub) |

---

## 3. Changelog

### v1.1 (2026-07-24) — Fitur Tutup Buku, Simpan Saldo, Inventaris Jurnal Umum

**Baru:**
- `GET /api/transaksi/jurnal-umum/form` — Form dispatch polymorphic (4 skenario: inventaris/hapus/auto-susut/nominal)
- `POST /api/transaksi/inventaris` — Pembelian inventaris (insert `transactions` + `inventories`)
- `POST /api/transaksi/inventaris/{id}/hapus` — Hapus/jual/revaluasi/rusak/hilang
- `GET /api/inventaris` — List inventaris (filter: kategori, jenis, status, q, tgl_dari, tgl_sampai, per_page)
- `GET /api/inventaris/{id}` — Detail inventaris
- `POST /api/inventaris` — Buat inventaris
- `PUT /api/inventaris/{id}` — Perbarui inventaris
- `DELETE /api/inventaris/{id}` — Hapus inventaris

**Diubah:**
- `POST /api/pelaporan/simpan-saldo` — **Tidak lagi stub**. Sekarang delegasi rekalk `amount` bulan 1-12 untuk tahun tertentu (single bulan atau semua bulan). Body: `{tahun, bulan?}`. Response: `{success, message, data: {tahun, bulan, jumlah_rekord}}`

**Catatan migrasi:**
- Tidak ada migration baru
- `Inventory` model tambah `HasFactory` trait
- `phpunit.xml` set `DB_DATABASE=pamsides` (sebelumnya `pamsides_test`) — karena user DB `pamsides` tidak punya hak GRANT ke DB baru. Test sekarang pakai DB dev yang sama dengan `migrate:fresh` per test class.
