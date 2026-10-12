# Dokumentasi Perbedaan APK: `siap_mapan` (APK Lama) vs `pamsides` (APK Baru)

Dokumen ini merangkum **seluruh perbedaan** antara dua aplikasi, disusun menu per menu
sesuai sidebar yang tampil di aplikasi.

| | APK Lama | APK Baru |
|---|---|---|
| Nama folder | `C:\laragon\www\siap_mapan` | `C:\laragon\www\pamsides` |
| Stack | Laravel + Blade + wkhtmltopdf | Laravel API + Vue 3 SPA |
| Database | `103.177.95.92` / `pamsides` (server, multi-tenant) | `127.0.0.1` / `pamsides` (lokal, single-tenant) |
| Akun | `userSimSppg` | `root` |
| Tampilan | Server-side render → PDF inline | Client-side render → preview → print PDF |

> Semua temuan di dokumen ini berdasarkan pembacaan kode. **Tidak ada data yang diubah.**

---

## Daftar Isi

1. [Konsep inti yang paling berbeda](#1-konsep-inti-yang-paling-berbeda)
2. [Struktur menu](#2-struktur-menu)
3. [Pencatatan Tagihan](#3-pencatatan-tagihan)
4. [Transaksi](#4-transaksi)
5. [Master Instalasi](#5-master-instalasi)
6. [Basis Data & Pengaturan](#6-basis-data--pengaturan)
7. [Beranda (Dashboard)](#7-beranda-dashboard)
8. [Pelaporan](#8-pelaporan)
9. [Ringkasan tabel kontras](#9-ringkasan-tabel-kontras)

---

## 1. Konsep inti yang paling berbeda

### 1.1 Bulan pemakaian vs bulan jatuh tempo ⭐

Ini perbedaan **paling penting** dan sering menjadi sumber misunderstand.

**APK Lama (`siap_mapan`)** — **tidak konsisten**, tiap layar beda:

| Konteks | Kolom yang dipakai | File |
|---|---|---|
| Daftar pemakaian air / filter | `tgl_pemakaian` (bulan pemakaian) | `UsageController.php:41` |
| Judul form pembayaran | **`tgl_akhir`** (bulan jatuh tempo) | `usage.blade.php:211-212` |
| Perhitungan denda | `tgl_pemakaian` bulan sebelumnya | `usage.blade.php:188-202` |
| Jurnal piutang tunggakan | **`tgl_akhir`** | `SystemController.php:75` |
| Laporan tagihan / piutang | **`tgl_akhir`** | `tagihan_pelanggan.blade.php:106` |

Pada APK lama `tgl_akhir` dihitung saat input sebagai:

```php
// UsageController.php:303-320
$tglPakai  = $data['tgl_pemakaian'];              // mis. 2025-02-24
$tglAkhir  = date('Y-m', strtotime('+1 month', $tglPakai))
           . '-' . str_pad($data['toleransi'], 2, '0');  // mis. 2025-03-27
```

Artinya: **pemakaian Februari → dibayar 27 Maret**. Selama bulan Maret yang ditagihkan
adalah pemakaian Februari. Tidak ada kolom periode tagihan yang eksplisit — periode hanya
disimpulkan dari `LIKE 'YYYY-MM%'` atas `tgl_pemakaian`.

**APK Baru (`pamsides`)** — **konsisten 100%**, periode = bulan pemakaian:

```php
// MonthlyBillService.php:127-139  (generator massal)
'billing_period_month' => $bulan,   // = $now->month (bulan pemakaian)
'billing_period_year'  => $tahun,
'due_date' => $this->computeDueDate($tahun, $bulan, $batasTagihan),
```

Tersedia kolom eksplisit `billing_period_month`, `billing_period_year`, dan `due_date`.
Jatuh tempo **dihitung dari** periode pemakaian, tidak pernah sebaliknya:

```php
// MonthlyBillService.php:199-205
public function computeDueDate(int $year, int $month, int $day = 27): string
{
    $carbon = Carbon::create($year, $month, 1);
    return $carbon->setDay(min($day, $carbon->daysInMonth))->toDateString();
}
```

Tercetak di 4 tempat berbeda (generator massal, per pelanggan, import legacy, dan
threshold pembayaran dini), semuanya konsisten memakai periode pemakaian.

> **Dampak praktis:** Tagihan Agustus 2026 di APK lama jatuh tempo **27 September 2026**.
> Di APK baru jatuh tempo **10 Agustus 2026** (nilai `settings.batas_tagihan` = 10).

---

## 2. Struktur menu

### 2.1 APK Lama — menu dari database

Menu **tidak ditulis di kode**, melainkan diambil dari tabel `menu` saat login:

```php
// AuthController.php:77-90
Menu::where('parent_id', '0')->whereNotIn('id', $akses)->where('status', 'A')
    ->with(['child'])->get();
// disimpan ke Session::get('menu')

// sidebar.blade.php:74
@foreach (Session::get('menu') as $menu)
```

Bisa dibatasi per pengguna lewat `users.akses_menu` (menu **Hak Akses**).

### 2.2 APK Baru — menu hardcoded di Vue

```js
// SidebarView.vue:269-358
const menuItems = [ ... ]
```

Dibatasi per **role** (`admin`, `surveyor`, `teknisi`, `pelanggan`) — bukan per pengguna.

### 2.3 Tabel kontras menu

| Menu | APK Lama | APK Baru |
|---|---|---|
| Beranda | ✅ | ✅ (beda isi, §7) |
| Pengaturan | ✅ (6 tab SOP) | ✅ (8 tab, +CALK +Tanda Tangan) |
| Basis Data | ✅ | ✅ |
| Master Instalasi | ✅ | ✅ |
| **Pencatatan Tagihan** | ❌ (gabung ke Pemakaian Air) | ✅ **menu sendiri** |
| Transaksi | ✅ | ✅ |
| Pelaporan | ✅ | ✅ |
| Profil | ✅ | ❌ |
| **Hak Akses** (menu per user) | ✅ | ❌ (ganti role tetap) |
| **Retribusi Sampah** | ✅ modul lengkap | ❌ **dihapus total** |
| **Master Cater** | ✅ (`/caters`) | ❌ (cater = user `role=teknisi`) |
| **Reversal Piutang** | ✅ halaman tersendiri | ❌ **dihapus** |
| **Arsip** (4 halaman) | ❌ | ✅ (ada, tapi **tidak ada di sidebar** — hanya via URL) |
| **Alokasi Laba** | ❌ | ✅ (ada, tapi **tidak ada di sidebar** — hanya via URL) |
| Role **pelanggan** (portal) | ❌ | ✅ |
| Role **surveyor** | ❌ | ✅ |

---

## 3. Pencatatan Tagihan

### 3.1 Model data

| | APK Lama | APK Baru |
|---|---|---|
| Tabel tagihan | **tidak ada** — tagihan = baris `usages` | ✅ `monthly_bills` |
| Tabel pembayaran | tidak ada | ✅ `bill_payments` |
| Tabel meter | tidak ada (`usages.awal`/`akhir`) | ✅ `meter_readings` |
| Kolom periode | ❌ tidak ada | ✅ `billing_period_month`, `billing_period_year` |
| Kolom jatuh tempo | ❌ tidak ada | ✅ `due_date` |
| Kolom total | ❌ tidak ada | ✅ `total_amount` |
| Denda | dihitung saat tampil | ✅ `penalty_amount` beku di tagihan |

### 3.2 Skema tabel `monthly_bills` (APK Baru)

```
id, customer_id
billing_period_year   int          <-- bulan TAHUN pemakaian
billing_period_month  int          <-- bulan PEMAKAIAN
meter_reading_start   decimal(10,2)
meter_reading_end     decimal(10,2)
usage_m3              decimal(10,2)
usage_charge          decimal(12,2)
abodemen              decimal(12,2)
penalty_amount        decimal(12,2) default 0
total_amount          decimal(12,2)
status                enum('unpaid','paid')
due_date              date
deleted_at            timestamp NULL
```

Index: `idx_bills_customer_period (customer_id, billing_period_year, billing_period_month)`.

> ⚠️ **Tidak ada unique constraint** di `(customer_id, year, month)` — hanya index.
> Yang punya unique adalah `meter_readings (customer_id, reading_year, reading_month)`.

### 3.3 Halaman Input Pemakaian Air

| Aspek | APK Lama | APK Baru |
|---|---|---|
| Route | `/usages/create` | `/instalasi/pemakaian-air/input` |
| Kolom tabel | Nama, Meter, Pemakaian, Tagihan, **TGL AKHIR BAYAR** | Nama, Meter, Pemakaian, Tagihan, **TGL AKHIR BAYAR**, Status, Aksi |
| Denda ditampilkan | Ya, dihitung runtime | Ya: `+ denda Agustus 2026 Rp.10.000,00` |
| **Foto meter** | ❌ wajib | ✅ **wajib** + **OCR meter otomatis** (tesseract.js) |
| Filter teknisi | ❌ (cater) | ✅ (wajib pilih teknisi, tabel kosong kalau belum dipilih) |
| Default bulan | bulan berjalan | bulan berjalan |
| Grouping wilayah | ada | ada |
| Biaya(SAMPAH) | ✅ modul tersendiri | ❌ dihapus |

**Fitur baru di APK Baru:**
- 📸 Foto meter **wajib** sebelum disimpan
- 🔍 **OCR meter otomatis** dari foto (`inputMeterModal.vue:587-626`)
- 📍 Validasi: meter tidak boleh lebih kecil dari meter sebelumnya
- 🔒 Rekaman terkunci jika tagihan periode tersebut sudah `paid`

### 3.4 Formulir Pembayaran Tagihan

| Field | APK Lama (`usage.blade.php`) | APK Baru (`BillingForm.vue`) |
|---|---|---|
| Judul kartu | "Tagihan Bulan {bulan **jatuh tempo**}" | dari periode pemakaian |
| Tanggal transaksi | default **hari ini** | datepicker, max = hari ini |
| Meter Awal / Akhir | disabled | disabled |
| Pemakaian | disabled | disabled |
| Tagihan | `usage.nominal` (readonly) | `usage_charge` (disabled) |
| Abodemen | dari `settings.abodemen` | dari `installation_packages.monthly_abodemen` |
| Denda | runtime dari `usage.blade.php:323` | beku di `penalty_amount` |
| **Metode bayar** | ❌ tidak ada | ✅ Tunai / **Transfer BRI** |
| Jatuh tempo | tidak tampil | ✅ banner + "Terlambat N hari" |
| Bayar paling lama dulu | ❌ | ✅ wajib (tombol nonaktif bila ada yang lebih lama) |

### 3.5 Perhitungan denda ⭐

**APK Lama** — 2 algoritma berbedaudin dalam 1 aplikasi:

```php
// (a) di form pembayaran — usage.blade.php:188-202
$bulanLalu = date('Y-m', strtotime('-1 month', $usage->tgl_pemakaian));
if ($usageLalu->status == 'UNPAID' && date('Y-m-d') > $usageLalu->tgl_akhir) {
    $dendaPemakaianLalu = $package->denda;
}
```

```php
// (b) di daftar — UsageController.php:83-101
$tgl_akhhir_lalu = date('Y-m', strtotime('-0 month', $usage->tgl_akhir)); // '-0 month' = bulan yang SAMA
// lalu cari jurnal denda existing
```

**APK Baru** — 1 aturan tunggal:

```php
// MonthlyBillService.php:183-197
$date = Carbon::create($tahun, $bulan, 1)->subMonths(1);
$bill = MonthlyBill::where('customer_id', $customer_id)
    ->where('billing_period_month', $date->month)
    ->where('billing_period_year',  $date->year)->first();
if ($bill && $bill->status == 'unpaid') {
    return $package->late_penalty;
}
```

| Aspek | APK Lama | APK Baru |
|---|---|---|
| Sumber nilai denda | `packages.denda` (string) | `installation_packages.late_penalty` |
| Kapan dihitung | runtime tiap buka form | **saat tagihan dibuat**, beku di `penalty_amount` |
|ZSatuhu | bisa berubah tiap dibuka | konsisten |
| Kumulatif? | tidak | tidak |

### 3.6 Status

**APK Lama:** hanya 2 nilai di DB — `usages.status` = `PAID` / `UNPAID`.
`Lancar/Menunggak/SP/SPS` dihitung on-the-fly di Blade, tidak disimpan.

**APK Baru:** `monthly_bills.status` = `unpaid` / `paid` (enum).
`Lancar/Menunggak/SP/SPS` juga label saja, dihitung dari selisih periode.

| Nilai | Arti | Dipicu |
|---|---|---|
| `Lancar` | Lancar | 0 bulan menunggak |
| `Menunggak` | Menunggak 1 bulan | 1 |
| `SP` | Surat Peringatan | 2 |
| `SPS` | Surat Pemutusan Sementara | ≥3 |

> ⚠️ **Dua implementasi berbeda di APK baru:**
> - `tagihan_pelanggan` menghitung **jumlah baris tagihan unpaid**
> - `piutang_pelanggan` menghitung **jumlah bulan dengan sisa saldo** (`max(total - dibayar, 0)`)

---

## 4. Transaksi

### 4.1 Sub-menu

| Sub-menu | APK Lama | APK Baru |
|---|---|---|
| Jurnal Umum | ✅ | ✅ |
| Tagihan Bulanan | ✅ | ✅ |
| Tagihan Instalasi (Pelunasan) | ✅ | ✅ |
| E-Budgeting | ✅ | ✅ |
| Tutup Buku | ✅ | ✅ (**lebih lengkap**, §8.5) |
| Komisi SPS | ✅ | ✅ |
| **Alokasi Laba** | ❌ | ✅ (route ada, tidak di sidebar) |
| **Reversal** (per transaksi) | ✅ | ❌ **dihapus** |
| **Reversal Piutang** (batch) | ✅ | ❌ **dihapus** |
| Dokumen / struk | 8 template Blade | Cetak dari browser |

### 4.2 Perbedaan penting

**Jurnal pembukuan tagihan** — APK Lama `TransactionController.php:1213-1429`:
- Insert **langsung ke `transactions`** dengan `rekening_debit`/`rekening_kredit` berupa **ID integer**
- Denda otomatis sebagai jurnal terpisah jika telat: `'tgl_transaksi' => tanggal 1 bulan setelah tgl_akhir`
- Komisi kolektor 10% otomatis bila ≥3 bulan menunggak (`TransactionController.php:1396-1413`)
- **Tidak ada `DB::transaction()`** — tulis parsial bisa terjadi

APK Baru `MonthlyBillController.php:354-697`:
- **`bill_payments`** dulu (terpisah), lalu jurnal ditautkan via `reverence_type='bill_payment'`
- Akun pakai **string `kode_akun`** (`1.1.01.01`), bukan ID
- Semua dalam **satu `DB::transaction()`**
- Denda **tidak** otomatis sebagai jurnal terpisah; hanya jika sudah ada `overdue_bill`
- Jurnal `overdue_bill` **di-reclass** (bukan dihapus) saat dibayar (**dibiarkan** demi jejak audit)
- Guard: `lockForUpdate()` untuk cegah double-pay
- **Metode bayar**: `cash` → `1.1.01.01`, `transfer` → `1.1.01.03` (Kas BRI)

### 4.3 Hapus transaksi

| | APK Lama | APK Beru |
|---|---|---|
| Hapus | `POST /transactions/hapus` — hard delete + cascade | `DELETE /transactions/{id}` — hard delete |
| Reversal | ✅ buat jurnal pembalik (`total * -1`, keterangan `KOREKSI idt...`) | ❌ |
| Efek samping | set `usage.status = UNPAID` + `installations.status = 'I'` + hapus inventory | hanya hapus baris |

> ⚠️ **Kekurangan APK baru:** tidak ada reversal → tidak ada jejak audit koreksi.

### 4.4 Pengakuan piutang tunggakan

**APK Lama:** cron/manual route `/dataset/{time}` → `SystemController::dataset`.
```php
// SystemController.php:75
$usage->tgl_akhir <= $date
// jurnal: tgl_transaksi = tgl_akhir (JATUH TEMPO)
```

**APK Baru:** `GenerateOverdueTransactions` (artisan) atau auto saat login.
```php
// GenerateOverdueTransactions.php:52
$thresholdDate = today - toleransi_tunggakan DAYS
// pilih: status='unpaid' AND due_date < $thresholdDate
```

> ⚠️ **Perbedaan semantic sama sekali:** `toleransi_tunggakan` di APK lama = **tanggal** (dalam bulan), di APK baru = **jumlah HARI**.

---

## 5. Master Instalasi

### 5.1 Model data

| | APK Lama | APK Baru |
|---|---|---|
| Tabel | `installations` | ✅ `installation_tickets` + `customers` |
| Relasi | 1 baris = 1 sambungan | 1 pelanggan = **N tiket** |
| ID | `kode_instalasi` (string, diisi manual) | `customer_code` (otomatis, unik) |
| Status | **6 kode 1 huruf**: `0`/`R`/`I`/`A`/`B`/`C` | **8 status**: `draft`/`pending`/`surveyed`/`unpaid`/`processing`/`completed`/`suspended`/`terminated` |
| Kategori | `1` = Air, `2` = Sampah | tidak ada (Sampah dihapus) |
| Koordinat | 1 kolom `koordinate` | ✅ `lat` + `lng` terpisah + peta |
| Cater | `cater_id` (tabel `caters`) | ✅ user `role=teknisi` |

### 5.2 Field formulir registrasi

| Field | APK Lama | APK Baru |
|---|---|---|
| Tanggal Order | ✅ | ✅ |
| Nama Pelanggan | ✅ (buat terpisah dulu) | ✅ (pilih dari ticket yang sudah disurvey) |
| Desa / Dusun | ✅ | ✅ |
| Cater | ✅ | ✅ (technisi) |
| RW / RT | ✅ | ✅ |
| Jalan | ✅ | ✅ (otomatis) |
| **Koordinat** | 1 field | ✅ lat/lng + tombol **"Lokasi Saya"** |
| **Preview Google Maps** | ❌ | ✅ |
| Paket / Kelas | ✅ | ✅ + preview tabel blok tarif |
| Abodemen / Denda | ✅ | ✅ |
| Nominal Pasang Baru | ✅ | ✅ + pengunci **Wajib Lunas / Bisa Dicicil** |
| Kategori Air/Sampah | ✅ | ❌ |

### 5.3 Alur hidup instalasi

**APK Lama** — status di-*dispatch* ke 6 blade berbeda:
```php
// InstallationsController.php:540-545
$func = 'detail'.$installation->status;
// → detail0, detailR, detailI, detailA, detailB, detailC
```

**APK Baru** — endpoint transisi eksplisit:
```
/installation-tickets/{id}/transition      (ubah status)
/installation-tickets/{id}/payment         (bayar)
/installation-tickets/{id}/advance-stage
/installation-tickets/{id}/activate
/installation-tickets/{id}/change-package  (+ audit trail)
```

**Penjaga tambahan APK Baru:**
- Pelanggan `suspended`/`terminated` **dilarang** registrasi ulang
- Otomatis `suspended` bila tagihan menunggak > `toleransi_tunggakan`
- Otomatis **pulih ke `completed`** bila semua tagihan lunas

---

## 6. Basis Data & Pengaturan

### 6.1 Master data

| Master | APK Lama | APK Baru |
|---|---|---|
| Paket / Kelas | `packages` + `block_paket` (2 halaman) | ✅ `installation_packages` + `water_tariff_blocks` (2 tabel) |
| Blok tarif | free-text `"0 - 10 M3"` | ✅ 2 field numerik `from`/`to` |
| **Wilayah** | ✅ `regions` 4 tingkat (Prov/Kab/Kec/Desa) via kode BPS | ⚠️ **tidak ada tabel wilayah** — Prov/Kab/Kec hardcoded dropdown; hanya `villages` (desa+dusun) |
| Pengguna | ✅ halaman CRUD + `positions` | ⚠️ `apiResource('users')` ada tapi **tanpa halaman UI** |
| Cater | ✅ tabel `caters` | ❌ diganti user `role=teknisi` |
| Rekening / CoA | `accounts` datar | ✅ `accounts` + `akun_level_1/2/3` (hierarki 3 tingkat) |
| Retribusi Sampah | ✅ modul penuh | ❌ dihapus |
| **Hak Akses** | ✅ per pengguna | ❌ role tetap |

### 6.2 Pengaturan (SOP)

| Tab | APK Lama | APK Baru |
|---|---|---|
| Wellcome | ✅ | ✅ |
| Identitas Lembaga | ✅ | ✅ (+ `nama_lembaga`, `peraturan_desa`, `sk_kemenkumham`) |
| Pasang Baru | ✅ | ✅ |
| Sistem Tagihan | ✅ | ✅ |
| **CALK** | ❌ | ✅ **baru** |
| Logo | ✅ | ✅ |
| Whatsapp | ✅ | ✅ |
| **Tanda Tangan** | ❌ | ✅ **baru** |

### 6.3 Multi-tenant

**APK Lama:** multi-tenant. Hampir semua query di-scope `Session::business_id`.
**APK Baru:** single-tenant. Tidak ada konsep `business_id` sama sekali.

---

## 7. Beranda (Dashboard)

| Aspek | APK Lama | APK Baru |
|---|---|---|
| Card Instalasi | `Installations::count()` | **Permohonan** (ticket `draft/pending/surveyed/unpaid`) |
| Card Pemakaian | instalasi aktif + usage | `InstallationTicket` status `completed` |
| Card Tunggakan | ≥3 usage unpaid | `MonthlyBill` unpaid dengan `penalty_amount > 0` |
| Card Tagihan | `usage` UNPAID + lewat jatuh tempo | `MonthlyBill` unpaid |
| Grafik | 1 (bar/line pendapatan-beban-surplus) | ✅ **2** (doughnut komposisi + bar/line) |
| Pilih tahun | ❌ | ✅ |
| Modal detail | `limit(200/300)`, tanpa pagination | ✅ **server-side paginated** |
| Dashboard per role | khusus cater (Air vs Sampah) | ✅ 5 (admin/surveyor/teknisi/pelanggan) |
| Auto generate tunggakan | ❌ (manual `/dataset/{time}`) | ✅ **otomatis saat login** (hanya admin, hari ke-N) |
| Lonceng tagihan | ❌ | ✅ `monthly-bills/unpaid-summary` |
| Arsip tagihan | ❌ | ✅ 4 halaman |
| Cache | 5 menit | 3 lapis (60s/300s/3600s) |

---

## 8. Pelaporan

### 8.1 Arsitektur

```
APK LAMA:   Controller → Blade → wkhtmltopdf → PDF inline (server-side)
APK BARU:   Controller → JSON → Vue chunking → preview → print → PDF (client-side)
```

Perpaginasi di APK baru dihitung di `PelaporanPreview.vue::buildPages()` (baris 356-826):

| view_target | Strategi |
|---|---|
| cover, surat_pengantar | 1 halaman |
| daftar_pelanggan/tagihan/piutang | chunk per **grup desa+dusun** (19/25 baris) |
| neraca_saldo | chunk 25 baris, `summary` hanya di halaman terakhir |
| buku_besar | chunk 20, **`startRunningSaldo` dibawa antar halaman** |
| jurnal_transaksi | chunk 20 item (~40 baris tabel) |
| laba_rugi / tutup_buku_laba_rugi | flatten grup → `flatRows`, chunk 45 |
| e_budgeting, piutang_komisi | chunk 20 |
| **ati / atb** | **1 halaman per kategori aset** |
| **CALK** | **probe offscreen** — ukur tinggi asli, hitung chunk per halaman |

### 8.2 Laporan yang ada

Semua 20 laporan APK lama **ada** di APK baru. Yang **tidak ada**: tidak ada laporan yang hilang.

**Hanya di APK Baru:**

| Fitur | Keterangan |
|---|---|
| Subsistem **Tanda Tangan (TTD)** | `SignatureService.php` — template + gambar per laporan, sanitasi HTML, hanya di halaman **terakhir** |
| CALK **editable** | `SopController` — persentase CALK & catatan bisa diubah dari UI |
| Fallback aman | `view_target` tak dikenal → JSON kosong, bukan PHP fatal |
| **Alokasi Laba** | laporan tutup buku versi APK baru sudah jadi (APK lama cuma stub 254 byte) |

### 8.3 Aset Tetap (ATI) — bug yang baru saja diperbaiki

**APK Baru** tabel `accounts` punya **2 baris per `kode_akun`** (sisa migrasi lama):

```
id=17  1.2.01.02  Gedung & Bangunan
id=610 1.2.01.02  Gedung & Bangunan     <-- duplikat
```

Karena frontend memecah 1 halaman per kategori, tiap kategori **tampil 2x**.
Diperbaiki di `PelaporanController::ati()` (baris 1577-1611) dengan `akunUnikLevel3()`.

> ⚠️ **Catatan teknis:** relasi `inventory` **harus di-load sebelum** dedupe, karena
> `akunUnikLevel3()` mengembalikan `Collection` biasa yang tidak punya method `load()`.

### 8.4 Perbedaan perhitungan inti

#### 8.4.1 `amount` — kumulatif vs per-bulan ⭐

| | APK Lama | APK Baru |
|---|---|---|
| Sifat tabel `amount` | **snapshot kumulatif** | **mutasi per bulan** |
| Baris `bulan='0'` | carry-over 1 Januari | **tidak ikut** (sudah tercermin di mutasi) |
| Baris `bulan='13'` | — | saldo akhir tahun (hasil TutupBuku), **tidak ikut** |
| Cara baca | baca 2 baris, jumlahkan | jumlahkan ulang lintas tahun |

```php
// PelaporanService.php:119-131 (APK BARU)
$q->where(function ($q) use ($tahun, $bulan) {
    $q->where('tahun', '<', $tahun)
      ->where('bulan', '>=', '01')->where('bulan', '<=', '12');   // tahun-tahun sebelumnya
})->orWhere(function ($q) use ($tahun, $bulan) {
    $q->where('tahun', $tahun)
      ->where('bulan', '>=', '01')->where('bulan', '<=', $bulan);  // tahun berjalan
});
```

Tanpa filter ini saldo terhitung 2x (contoh terdokumentasi di kode: Kas Tunai Januari 2026
tampil `1.676.823.680`, seharusnya `868.785.590`).

#### 8.4.2 Neraca

| Aspek | APK Lama | APK Baru |
|---|---|---|
| Laba/Rugi Berjalan (3.2.02.01) | hanya `$surplus` tahun berjalan | ✅ `labaTahunTertahun + surplus` |
| Total Aset | dib prioritisekan agar seimbang | ✅ **dibiarkan apa adanya** |
| Dedupe akun | ❌ | ✅ `dedupePerKode()` (akun kembar akan menghitung surplus 2x) |

#### 8.4.3 Tagihan Pelanggan

| Aspek | APK Lama | APK Baru |
|---|---|---|
| Sumber | `usage` UNPAID dengan `tgl_akhir <= tgl_kondisi` | `monthly_bills` unpaid dengan `billing_period <= target` |
| Periode | **jatuh tempo** (`tgl_akhir`) | **pemakaian** (`billing_period_month`) |
| Nominal | `nominal + abodemen` + `denda` bila lewat toleransi | ✅ `total_amount` (denda sudah termasuk) |
| Pembayaran | jumlahkan jurnal, kecuali yang debit `1.1.03.01` | ✅ `SUM(bill_payments.amount_paid)` |
| Subtotal per desa | ✅ ada | ❌ hilang (dihitung ulang di Vue) |
| Page break per cater | ✅ ada | ❌ hilang |

#### 8.4.4 Piutang Pelanggan

| Aspek | APK Lama | APK Baru |
|---|---|---|
| Bucket | 2 (bulan lalu / bulan ini) | ✅ **3** (sd 3 bulan lalu / bulan lalu / bulan ini) |
| Sisa tagihan | `nominal + abodemen + denda` mentah | ✅ `max(total_amount - dibayar, 0)` |

#### 8.4.5 Arus Kas

| Aspek | APK Lama | APK Baru |
|---|---|---|
| Akun kas | `1.1.01%` **dan** `1.1.02%` | ✅ hanya `1.1.01.%` |
| Tanda | kredit dikurangi, debit ditambah (**terbalik**) | ✅ kredit menambah, debit mengurangi |
| Query | `account_debit` (kolom **tidak ada** → error) | ✅ `account_debet` |

#### 8.4.6 CALK

| Aspek | APK Lama | APK Baru |
|---|---|---|
| Konfigurasi | hardcoded | ✅ `settings.calk` JSON, editable dari UI |
| Tabel Pembagian Laba | ✅ ada tabel nominal | ⚠️ **hilang**, jadi teks narasi |
| Peringatan aset ≠ kewajiban | ✅ merah | ❌ tidak ada |
| Catatan (`Calk.catatan`) | ❌ di-comment | ✅ aktif, dirender `v-html` |
| Pagination | CSS `page-break` | ✅ probe DOM offscreen |

#### 8.4.7 Tanda Tangan (TTD)

| Aspek | APK Lama | APK Baru |
|---|---|---|
| Jumlah laporan bertanda tangan | **2 saja** (satu pun di-comment) | ✅ **semua** laporan |
| Sumber | nama+jabatan hardcoded | ✅ template + gambar, via `SignatureService` |
| Penempatan | tidak ada | ✅ hanya halaman **terakhir** |
| Sanitasi | ❌ | ✅ whitelist tag, buang `on*`, batas 50 KB |

### 8.5 Tutup Buku — fitur yang tidak ada di APK Lama

| Aspek | APK Lama | APK Baru |
|---|---|---|
| Tulis baris `bulan='13'` | ❌ | ✅ saldo akhir tahun |
| Tulis baris `bulan='00'` | ❌ | ✅ carry-over ke tahun+1 (**hanya akun 1/2/3**) |
| Laba tahun ditutup | ❌ | ✅ masuk `3.2.02.01` |
| `DB::transaction()` | ❌ | ✅ |
| Alokasi Laba / dividen | ❌ (stub) | ✅ default 50% Pemdes / 50% Masy |
| Aset / penyusutan | ❌ (mask di Blade) | ✅ `InventoryService`, bisa jurnal |

---

## 9. Ringkasan tabel kontras

### 9.1 Perbedaan paling berdampak

| # | Aspek | APK Lama | APK Baru | Dampak |
|---|---|---|---|---|
| 1 | **Periode tagihan** | jatuh tempo | **pemakaian** | Angka tagihan per bulan **bergesar 1 bulan** |
| 2 | **Jatuh tempo** | `tgl_pemakaian + 1 bulan` | **bulan yang sama** (hari `batas_tagihan`) | APK lama: tagihan Agustus dibayar 27 Sep. APK baru: **10 Agu** |
| 3 | **Entitas tagihan** | tidak ada | `monthly_bills` + `bill_payments` | APK baru punya riwayat pembayaran riil |
| 4 | **`amount`** | kumulatif | **mutasi per bulan** | APK baru harus jumlah ulang lintas tahun |
| 5 | **TTD** | 2 laporan | **semua laporan** | APK baru siap cetak resmi |
| 6 | **Reversal** | ✅ ada | ❌ **tidak ada** | **Kekurangan** — koreksi tidak berjejak audit |
| 7 | **Retribusi Sampah** | ✅ ada | ❌ dihapus | Fitur hilang |
| 8 | **Wilayah 4 tingkat** | ✅ `regions` | ⚠️ hardcoded | Data wilayah tidak bisa dikelola dari UI |
| 9 | **Multi-tenant** | ✅ | ❌ single | Tidak bisa multi BUMDes dalam 1 DB |
| 10 | **Hak Akses per user** | ✅ | ❌ role tetap | Tidak bisa batasi menu per orang |

### 9.2 Fitur yang hanya ada di APK Baru

| Fitur | Lokasi |
|---|---|
| OCR meter otomatis | `inputMeterModal.vue:587-626` |
| Foto meter wajib | `inputMeterModal.vue:492-500` |
| Metode bayar Transfer BRI | `MonthlyBillController::pay()` |
| Laporan Tanda Tangan | `SignatureService.php` |
| CALK editable | `SopController` |
| Tutup Buku lengkap (bulan 13/00) | `TutupBukuController.php` |
| Alokasi Laba / dividen | `AlokasiLabaController.php` |
| Penyusutan aset (layanan) | `InventoryService.php` |
| Auto generate tunggakan | `DashboardController::autoGenerateOverdue()` |
| Role **pelanggan** | portal mandiri |
| Role **surveyor** | alur survei |
| Arsip tagihan/tunggakan | 4 halaman |
| Ekspor CSV/PDF laporan | `api.php:245-246` |

### 9.3 Fitur yang hanya ada di APK Lama

| Fitur | Alasan hilang/tidak ada |
|---|---|
| Retribusi Sampah | modul dihapus total |
| Reversal + Reversal Piutang | tidak ada di APK baru |
| Master Cater | digantikan user `role=teknisi` |
| Tabel wilayah (`regions`) | digantikan dropdown hardcoded |
| Hak Akses per pengguna | digantikan role tetap |
| Multi-tenant (`business_id`) | single-tenant |
| Halaman Profil | tidak ada |
| Halaman Users | tidak ada |

---

## Lampiran: Peta file untuk referensi

### APK Lama — `C:\laragon\www\siap_mapan`

| Area | File |
|---|---|
| Tagihan air | `app/Http/Controllers/UsageController.php` |
| Pembayaran | `app/Http/Controllers/TransactionController.php` |
| Piutang tunggakan (cron) | `app/Http/Controllers/SystemController.php` |
| Dashboard | `app/Http/Controllers/DashboardController.php` |
| Pelaporan | `app/Http/Controllers/PelaporanController.php` |
| Hitung saldo | `app/Utils/Keuangan.php` |
| Form bayar | `resources/views/transaksi/partials/usage.blade.php` |
| Laporan tagihan | `resources/views/pelaporan/partials/views/tagihan_pelanggan.blade.php` |
| Menu (dinamis) | `app/Models/Menu.php` |

### APK Baru — `C:\laragon\www\pamsides`

| Area | File |
|---|---|
| Tagihan | `backend/app/Http/Controllers/MonthlyBillController.php` |
| Meter | `backend/app/Http/Controllers/MeterReadingController.php` |
| Generator tagihan | `backend/app/Services/MonthlyBillService.php` |
| Hitung tagihan per pelanggan | `backend/app/Services/BillingService.php` |
| Import legacy | `backend/app/Services/LegacyUsageSource.php` |
| Tutup buku | `backend/app/Http/Controllers/TutupBukuController.php` |
| Alokasi laba | `backend/app/Http/Controllers/AlokasiLabaController.php` |
| Penyusutan | `backend/app/Services/InventoryService.php` |
| TTD | `backend/app/Services/SignatureService.php` |
| Pelaporan | `backend/app/Http/Controllers/PelaporanController.php` |
| Hitung laporan | `backend/app/Services/PelaporanService.php` |
| Pagination laporan | `frontend/src/presentations/views/app/admin/pelaporan/PelaporanPreview.vue` |
| Sidebar | `frontend/src/presentations/layouts/app/SidebarView.vue` |
| Form bayar | `frontend/src/presentations/components/billing/BillingForm.vue` |
| Input meter | `frontend/src/presentations/components/billing/inputMeterModal.vue` |