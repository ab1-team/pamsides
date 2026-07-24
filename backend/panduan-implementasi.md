# Panduan Implementasi Fitur SIDBM

> Dokumen ini adalah panduan **porting** 3 fitur dari aplikasi SIDBM (Sistem Informasi Desa Berdaya Mandiri) ke aplikasi baru.
> Fitur yang di-porting: **(1) Tutup Buku**, **(2) Simpan Saldo**, **(3) Inventaris di Jurnal Umum**.
> Backend target: **Laravel** (murni API/AJAX). Frontend: ditangani tim lain (Vue/React) — backend cukup return JSON.

---

## Daftar Isi

1. [Asumsi Stack & Penyesuaian Umum](#asumsi-stack--penyesuaian-umum)
2. [Fitur 1: Tutup Buku](#fitur-1-tutup-buku)
3. [Fitur 2: Simpan Saldo](#fitur-2-simpan-saldo)
4. [Fitur 3: Inventaris di Jurnal Umum](#fitur-3-inventaris-di-jurnal-umum)
5. [Lampiran A: Migration SQL](#lampiran-a-migration-sql)
6. [Lampiran B: Contoh Payload Request/Response JSON](#lampiran-b-contoh-payload-requestresponse-json)
7. [Lampiran C: Test Case](#lampiran-c-test-case)

---

## Asumsi Stack & Penyesuaian Umum

| Aspek | SIDBM (asal) | Aplikasi Baru (target) |
|---|---|---|
| Backend | Laravel + Blade view | Laravel (API only) |
| Frontend | Blade + jQuery + SweetAlert | Vue/React (handle sendiri) |
| Multi-tenant | Tabel `saldo_<lokasi>`, `inventaris_<lokasi>`, dll via `TenantAware` trait + `Session::get('lokasi')` | **Tidak ada** — tabel flat, single-tenant |
| RBAC | `Session::get('tombol')` (session-based permission) | `auth()->user()->can(...)` (Spatie) atau middleware `role:...` |
| Date/Number plugin | Flatpickr, MaskMoney, Choices.js | Tidak relevan (FE tanggung jawab) |
| Format response | HTML partial (`view(...)->render()`) | JSON murni (return struktur data) |
| Idempoten key | `id` deterministik: `kode_akun_tanpa_titik . tahun . bulan_zero_padded` | **Pertahankan** — penting untuk delete-then-insert |

### Aturan Porting Wajib

1. **Hapus semua referensi `Session::get('lokasi')`** dan `TenantAware` trait. Ganti dengan ID statis atau `auth()->user()->id`.
2. **Hapus semua `->with()` / `->render()` view** — return `response()->json([...])`.
3. **Naming endpoint**: pakai prefix `/api/` (atau sesuai konvensi project baru) dan ganti underscore dengan dash. Mis. `POST /transaksi/tutup_buku/saldo` → `POST /api/transaksi/tutup-buku/preview`.
4. **Format response** konsisten: `{ success: bool, message?: string, data?: {...} }`. Frontend FE yang handle toast/notification.
5. **Field `id` deterministik** di tabel `saldo` & `inventaris`: tetap pakai pola `kode_akun_tanpa_titik . tahun . bulan_zero_padded` agar fitur `delete-then-insert` (idempotent) tetap valid.
6. **Transaksi DB**: bungkus loop insert `saldo_tutup_buku` & insert multi-record inventaris dalam `DB::transaction()`.

### Service Class yang Perlu Dibuat

Agar controller tetap ramping, pecah logic ke service:

- `App\Services\TutupBukuService` → method `preview()`, `eksekusi()`, `alokasiLaba()`
- `App\Services\SaldoService` → method `simpanSnapshot()`
- `App\Services\InventarisService` → method `nilaiBuku()`, `bulan()`, `penyusutan()`, `saldoSusut()`

(Porting langsung dari `App\Utils\Keuangan` & `App\Utils\Inventaris` di SIDBM, ubah static method → instance method atau biarkan static dengan helper `Rekening`, `Transaksi` plain.)

---

## Fitur 1: Tutup Buku

### 1.1 Overview

Proses **closing buku tahunan** untuk lembaga keuangan desa. Bertugas:
- Mengunci transaksi tahun buku `N`.
- Menghitung surplus (laba/rugi) sampai `tgl_kondisi`.
- Menulis **saldo akhir tahun N** (`bulan = 13`) untuk akun nominal/riil (lev1 ≥ 4).
- Menulis **saldo awal tahun N+1** (`bulan = 0`) untuk akun ekuitas/aset/liabilitas (lev1 < 4), plus menambahkan surplus ke `3.2.01.01` (laba ditahan).
- (Opsional) **Pembagian laba** ke pos-pos ekuitas tertentu + buat jurnal alokasi.

### 1.2 Skema DB

Lihat [Lampiran A](#lampiran-a-migration-sql) untuk migration SQL lengkap. Tabel yang relevan:

- `saldo` — snapshot kumulatif per `(kode_akun, tahun, bulan)`
- `rekening` — master kode akun + `lev1`/`lev2`/`lev3`/`lev4`
- `transaksi` — jurnal umum (sumber data snapshot)
- `kecamatan`, `desa` —(opsional) untuk alokasi laba per desa

### 1.3 Routes (SIDBM)

```php
// routes/web.php (SIDBM)
Route::get('/transaksi/tutup_buku', [TransaksiController::class, 'jurnalTutupBuku'])->middleware('auth');
Route::post('/transaksi/tutup_buku/saldo', [TransaksiController::class, 'saldoTutupBuku'])->middleware('auth');
Route::post('/transaksi/tutup_buku', [TransaksiController::class, 'simpanTutupBuku'])->middleware('auth');
Route::post('/transaksi/simpan_laba', [TransaksiController::class, 'simpanAlokasiLaba'])->middleware('auth');
```

### 1.4 Routes (Aplikasi Baru — API)

```php
// routes/api.php
Route::middleware('auth:sanctum')->prefix('transaksi/tutup-buku')->group(function () {
    Route::get('/preview', [TutupBukuController::class, 'preview']);
    Route::post('/eksekusi', [TutupBukuController::class, 'eksekusi']);
    Route::post('/alokasi-laba', [TutupBukuController::class, 'alokasiLaba']);
    Route::post('/reopen', [TutupBukuController::class, 'reopen']); // opsional
});
```

### 1.5 Controller (Aplikasi Baru)

```php
// app/Http/Controllers/Api/TutupBukuController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TutupBukuService;
use Illuminate\Http\Request;

class TutupBukuController extends Controller
{
    public function __construct(private TutupBukuService $service) {}

    public function preview(Request $request)
    {
        $request->validate([
            'tahun' => 'required|integer|min:2000|max:2100',
            'tgl_pakai' => 'required|date',
        ]);

        // Constraint: hanya boleh tutup buku bulan November/Desember (bulan >= 11) atau tahun < tahun sekarang
        $bulanSekarang = (int) date('m');
        $tahunSekarang = (int) date('Y');
        if ($request->tahun >= $tahunSekarang && $bulanSekarang < 11) {
            return response()->json([
                'success' => false,
                'message' => 'Tutup buku hanya dapat dilakukan mulai bulan November.',
            ], 422);
        }

        $data = $this->service->preview($request->tahun, $request->tgl_pakai);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function eksekusi(Request $request)
    {
        $request->validate([
            'tahun' => 'required|integer',
            'tgl_pakai' => 'required|date',
            'pembagian_laba' => 'required|boolean',
        ]);

        $result = $this->service->eksekusi(
            tahun: $request->tahun,
            tglPakai: $request->tgl_pakai,
            includeAlokasiLaba: $request->pembagian_laba,
        );

        return response()->json(['success' => true, 'message' => "Tutup Buku Tahun {$request->tahun} berhasil.", 'data' => $result]);
    }

    public function alokasiLaba(Request $request)
    {
        $request->validate([
            'tahun' => 'required|integer',
            'tgl_mad' => 'required|date',
            'surplus' => 'required|numeric',
            'masyarakat' => 'required|numeric',
            'desa' => 'required|numeric',
            'laba_ditahan' => 'required|numeric',
            // dst...
        ]);

        $this->service->alokasiLaba($request->all());
        return response()->json(['success' => true, 'message' => 'Alokasi laba berhasil disimpan.']);
    }

    public function reopen(Request $request)
    {
        $request->validate(['tahun' => 'required|integer']);

        // Hapus saldo bulan=0 tahun=tahun+1 (saldo awal hasil closing)
        // Hapus saldo bulan=13 tahun=tahun (saldo akhir yang di-snapshot)
        // Hanya boleh jika belum ada transaksi di tahun tutup buku
        $this->service->reopen($request->tahun);

        return response()->json(['success' => true, 'message' => 'Buku tahun '.$request->tahun.' berhasil dibuka kembali.']);
    }
}
```

### 1.6 Service — Logika Kunci

```php
// app/Services/TutupBukuService.php
namespace App\Services;

use App\Models\Saldo;
use App\Models\Rekening;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

class TutupBukuService
{
    public function preview(int $tahun, string $tglPakai): array
    {
        $bulan = $tahun < (int) date('Y') ? 12 : (int) date('m');
        $tglKondisi = $tahun.'-'.str_pad($bulan, 2, '0', STR_PAD_LEFT).'-'.date('t', strtotime("$tahun-$bulan-01"));

        $saldo = Saldo::where('tahun', $tahun)
            ->where(function ($q) use ($bulan) {
                $q->where('bulan', 0)->orWhere('bulan', $bulan);
            })
            ->get()
            ->keyBy('kode_akun');

        $surplus = $this->hitungLabaRugi($tglKondisi);

        return [
            'tahun' => $tahun,
            'tgl_kondisi' => $tglKondisi,
            'surplus' => $surplus,
            'saldo' => $saldo->map(fn ($s) => [
                'kode_akun' => $s->kode_akun,
                'debit' => (float) $s->debit,
                'kredit' => (float) $s->kredit,
                'bulan' => $s->bulan,
            ])->values(),
        ];
    }

    public function eksekusi(int $tahun, string $tglPakai, bool $includeAlokasiLaba = false): array
    {
        $bulan = $tahun < (int) date('Y') ? 12 : (int) date('m');
        $tglKondisi = $tahun.'-'.str_pad($bulan, 2, '0', STR_PAD_LEFT).'-'.date('t', strtotime("$tahun-$bulan-01"));
        $tahunTb = $tahun + 1;
        $surplus = $this->hitungLabaRugi($tglKondisi);

        return DB::transaction(function () use ($tahun, $bulan, $tglKondisi, $tahunTb, $surplus) {
            $rekening = Rekening::where(function ($q) use ($tglKondisi) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', $tglKondisi);
            })->get();

            $dataId = [];
            $rows = [];

            foreach ($rekening as $rek) {
                $saldo = Saldo::where('kode_akun', $rek->kode_akun)
                    ->where('tahun', $tahun)
                    ->whereIn('bulan', [0, $bulan])
                    ->get();

                $debitAwal = $kreditAwal = $debit = $kredit = 0;
                foreach ($saldo as $s) {
                    if ($s->bulan == 0) {
                        $debitAwal += (float) $s->debit;
                        $kreditAwal += (float) $s->kredit;
                    } else {
                        $debit += (float) $s->debit;
                        $kredit += (float) $s->kredit;
                    }
                }

                // Akun nominal/riil (lev1 >= 4): tulis saldo akhir tahun (bulan=13)
                if ($rek->lev1 >= 4) {
                    $id = str_replace('.', '', $rek->kode_akun) . $tahun . '13';
                    if ($debit + $kredit != 0) {
                        $rows[] = [
                            'id' => $id,
                            'kode_akun' => $rek->kode_akun,
                            'tahun' => $tahun,
                            'bulan' => 13,
                            'debit' => (string) $debit,
                            'kredit' => (string) $kredit,
                        ];
                        $dataId[] = $id;
                    }
                }

                // Akun neraca (lev1 < 4), kecuali 3.2.02.01 (ikhtisar laba/rugi): tulis saldo awal tahun depan (bulan=0)
                if ($rek->lev1 < 4 && $rek->kode_akun !== '3.2.02.01') {
                    $saldoDebit = $debitAwal + $debit;
                    $saldoKredit = $kreditAwal + $kredit;

                    if ($rek->kode_akun === '3.2.01.01') {
                        // Laba ditahan: tambahkan surplus
                        $saldoKredit += $surplus;
                    }

                    $id = str_replace('.', '', $rek->kode_akun) . $tahunTb . '00';
                    $rows[] = [
                        'id' => $id,
                        'kode_akun' => $rek->kode_akun,
                        'tahun' => $tahunTb,
                        'bulan' => 0,
                        'debit' => (string) $saldoDebit,
                        'kredit' => (string) $saldoKredit,
                    ];
                    $dataId[] = $id;
                }
            }

            Saldo::whereIn('id', $dataId)->delete();
            Saldo::insert($rows);

            return [
                'tahun_tutup' => $tahun,
                'tahun_awal_baru' => $tahunTb,
                'jumlah_saldo_ditulis' => count($rows),
                'surplus' => $surplus,
            ];
        });
    }

    public function hitungLabaRugi(string $tglKondisi): float
    {
        // Pendapatan (kode_akun lev1 = 4) - Beban (kode_akun lev1 = 5)
        // Implementasi porting dari App\Utils\Keuangan::laba_rugi() di SIDBM
        $pendapatan = Transaksi::where('tgl_transaksi', '<=', $tglKondisi)
            ->whereHas('rek_debit', fn ($q) => $q->where('lev1', 4))
            ->sum('jumlah');

        $beban = Transaksi::where('tgl_transaksi', '<=', $tglKondisi)
            ->whereHas('rek_debit', fn ($q) => $q->where('lev1', 5))
            ->sum('jumlah');

        return (float) ($pendapatan - $beban);
    }

    public function alokasiLaba(array $data): void
    {
        DB::transaction(function () use ($data) {
            $tahun = $data['tahun'];
            $tahunTb = $tahun + 1;

            // 1. Insert 6 baris saldo alokasi (laba ditahan, modal DBM, investasi, dll.)
            //    id format: <kd_kec><tahun_tb><urut 001-006>
            // 2. Insert Transaksi jurnal alokasi:
            //    rekening_debit='3.2.01.01', rekening_kredit='2.1.04.01' / '2.1.04.02' / '2.1.04.03'
            // Lihat implementasi lengkap di SIDBM TransaksiController::simpanAlokasiLaba() (line 395-...)
        });
    }

    public function reopen(int $tahun): void
    {
        DB::transaction(function () use ($tahun) {
            // Hapus saldo hasil tutup buku
            Saldo::where('tahun', $tahun)->where('bulan', 13)->delete();
            Saldo::where('tahun', $tahun + 1)->where('bulan', 0)
                ->whereIn('kode_akun', function ($q) {
                    $q->select('kode_akun')->from('rekening')->where('lev1', '<', 4);
                })
                ->delete();
        });
    }
}
```

### 1.7 Status Tutup Buku (Cara Dikenali)

SIDBM **tidak** punya kolom `is_closed`/`status_tutup`. Status tutup buku dikenali lewat **keberadaan baris `saldo`** dengan:
- `bulan = 0` (saldo awal tahun)
- `tahun = tahun_tb = tahun_target + 1`

Query cek status:
```sql
SELECT COUNT(*) AS sudah_tutup
FROM saldo
WHERE bulan = 0
  AND tahun = :tahun + 1
  AND kode_akun IN (SELECT kode_akun FROM rekening WHERE lev1 < 4);
```

**Rekomendasi app baru**: tambahkan kolom `closed_at TIMESTAMP NULL` di tabel `periode_buku` (tabel baru) untuk audit trail. Lihat [Lampiran A](#lampiran-a-migration-sql).

### 1.8 Constraint & Validasi

| Constraint | Sumber | Tindak Lanjut |
|---|---|---|
| Hanya bulan Nov/Des ATAU tahun < tahun_skrg | `index.blade.php` line 33 (`{{ date('m') <= 10 ? 'disabled' : '' }}`) | Validasi di controller `preview()` & `eksekusi()` |
| Hanya rekening aktif | `whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tgl_kondisi)` | Tetap dipakai di `eksekusi()` |
| `kode_akun != '3.2.02.01'` saat loop lev1<4 | `simpanTutupBuku` line 325 | Penting — `3.2.02.01` adalah ikhtisar laba/rugi, jangan dibuatkan saldo awal |
| `kode_akun == '3.2.01.01'` (laba ditahan) → tambah surplus | `simpanTutupBuku` line 348-350 | Tetap dipakai di `eksekusi()` |
| `bulan = 13` hanya untuk lev1 ≥ 4 | `simpanTutupBuku` line 283 | Saldo akhir tahun hanya untuk akun nominal/riil (pendapatan, beban, aset tetap) |

### 1.9 View/UI (SIDBM)

Tombol disable constraint (JS):

```javascript
// resources/views/transaksi/tutup_buku/index.blade.php line 67-76
$(document).on('change', 'select#tahun', function(e) {
    var tahunVal = $(this).val();
    if ((tahun == tahunVal && bulan <= 10)) {
        $('#TutupBuku').prop("disabled", true)
    } else {
        $('#TutupBuku').prop("disabled", false)
    }
})
```

**Untuk FE Vue/React**: cukup set `disabled` di state berdasarkan response API `preview` (cek field `can_execute: bool` di response, atau rules `bulan >= 11 || tahun < tahun_ini`).

### 1.10 Penyesuaian App Baru

- **Hapus** semua `$kec = Kecamatan::where('id', Session::get('lokasi'))->first()` (SIDBM) → jika app baru punya `user->lokasi`, bisa filter `saldo` by `lokasi`. Jika single-tenant, **hapus** kolom `lokasi` di semua tabel.
- **Ganti** `view('...')->render()` (SIDBM return HTML) → `response()->json(['data' => [...]])` untuk FE render.
- **Tambah** endpoint `POST /api/transaksi/tutup-buku/reopen` (tidak ada di SIDBM) untuk rollback.
- **Tambah** tabel `periode_buku` (tahun, status: `open|closed`, closed_at) untuk audit.

---

## Fitur 2: Simpan Saldo

### 2.1 Overview

Tombol **"Simpan Saldo"** di halaman `/pelaporan`. Fungsinya: membuat **snapshot kumulatif** `saldo` per `(kode_akun, tahun, bulan)` dari data transaksi, untuk mempercepat query pelaporan (tidak perlu agregasi on-the-fly).

Trigger dari: tombol `/pelaporan`, alert dashboard, atau URL manual `/simpan_saldo?tahun=X&bulan=YY`.

### 2.2 Skema DB

Tabel `saldo` (sama dengan Fitur 1, digunakan sebagai materialized view).

**Field dinamis `rekening`** di SIDBM:
- `tb2024`, `tb2023`, ... — saldo debit akhir tahun tutup buku
- `tbk2024`, `tbk2023`, ... — saldo kredit akhir tahun tutup buku

**Rekomendasi app baru**: **hapus field dinamis** dari tabel `rekening`. Ambil snapshot dari query:
```sql
SELECT kode_akun, debit, kredit
FROM saldo
WHERE tahun = :tahun - 1 AND bulan = 13;
```

### 2.3 Routes (SIDBM)

```php
Route::get('/simpan_saldo', [DashboardController::class, 'simpanSaldo']);
```

### 2.4 Routes (Aplikasi Baru — API)

```php
// routes/api.php
Route::middleware('auth:sanctum')->post('/simpan-saldo', [SaldoController::class, 'simpan']);
```

### 2.5 Controller (Aplikasi Baru)

```php
// app/Http/Controllers/Api/SaldoController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SaldoService;
use Illuminate\Http\Request;

class SaldoController extends Controller
{
    public function __construct(private SaldoService $service) {}

    public function simpan(Request $request)
    {
        $request->validate([
            'tahun' => 'required|integer|min:2000|max:2100',
            'bulan' => 'required|integer|min:0|max:12', // 0 = saldo awal tahun
        ]);

        $result = $this->service->simpanSnapshot($request->tahun, $request->bulan);

        return response()->json([
            'success' => true,
            'message' => "Saldo bulan {$request->bulan} tahun {$request->tahun} berhasil disimpan.",
            'data' => $result,
        ]);
    }
}
```

### 2.6 Service — Logika Kunci

```php
// app/Services/SaldoService.php
namespace App\Services;

use App\Models\Saldo;
use App\Models\Rekening;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

class SaldoService
{
    /**
     * Simpan snapshot saldo untuk 1 bulan.
     *
     * @param int $tahun  Tahun buku
     * @param int $bulan  0 = saldo awal tahun (dari tutup buku tahun sebelumnya)
     *                    1-12 = saldo kumulatif sampai akhir bulan tsb
     */
    public function simpanSnapshot(int $tahun, int $bulan): array
    {
        return DB::transaction(function () use ($tahun, $bulan) {
            if ($bulan === 0) {
                return $this->simpanSaldoAwal($tahun);
            }
            return $this->simpanSaldoBulanan($tahun, $bulan);
        });
    }

    private function simpanSaldoAwal(int $tahun): array
    {
        // Ambil saldo akhir tahun sebelumnya dari snapshot tutup buku
        // (SELECT * FROM saldo WHERE tahun = :tahun - 1 AND bulan = 13)
        $saldoTahunLalu = Saldo::where('tahun', $tahun - 1)
            ->where('bulan', 13)
            ->get();

        $rows = [];
        $ids = [];
        foreach ($saldoTahunLalu as $s) {
            $id = str_replace('.', '', $s->kode_akun) . $tahun . '00';
            $rows[] = [
                'id' => $id,
                'kode_akun' => $s->kode_akun,
                'tahun' => $tahun,
                'bulan' => 0,
                'debit' => (string) $s->debit,
                'kredit' => (string) $s->kredit,
            ];
            $ids[] = $id;
        }

        if (!empty($ids)) {
            Saldo::whereIn('id', $ids)->delete();
            Saldo::insert($rows);
        }

        return ['jumlah_saldo' => count($rows), 'bulan' => 0, 'tahun' => $tahun];
    }

    private function simpanSaldoBulanan(int $tahun, int $bulan): array
    {
        $tglKondisi = date('Y-m-t', strtotime("$tahun-$bulan-01"));

        // Agregasi debit & kredit per kode_akun dari transaksi dalam range tahun
        $rekening = Rekening::withSum([
            'trx_debit' => fn ($q) => $q->whereBetween('tgl_transaksi', ["$tahun-01-01", $tglKondisi]),
        ], 'jumlah')
        ->withSum([
            'trx_kredit' => fn ($q) => $q->whereBetween('tgl_transaksi', ["$tahun-01-01", $tglKondisi]),
        ], 'jumlah')
        ->orderBy('kode_akun')
        ->get();

        $rows = [];
        $ids = [];
        foreach ($rekening as $rek) {
            $id = str_replace('.', '', $rek->kode_akun)
                . $tahun
                . str_pad($bulan, 2, '0', STR_PAD_LEFT);
            $rows[] = [
                'id' => $id,
                'kode_akun' => $rek->kode_akun,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'debit' => (string) ($rek->trx_debit_sum_jumlah ?? 0),
                'kredit' => (string) ($rek->trx_kredit_sum_jumlah ?? 0),
            ];
            $ids[] = $id;
        }

        Saldo::whereIn('id', $ids)->delete();
        Saldo::insert($rows);

        return ['jumlah_saldo' => count($rows), 'bulan' => $bulan, 'tahun' => $tahun];
    }

    /**
     * Simpan snapshot untuk semua bulan (1-12) sekaligus.
     * Dipakai untuk backfill awal atau tombol "Simpan Semua".
     */
    public function simpanSemua(int $tahun): array
    {
        $results = [];
        // Selalu simpan saldo awal (bulan=0) dulu
        $results[] = $this->simpanSnapshot($tahun, 0);
        for ($b = 1; $b <= 12; $b++) {
            $results[] = $this->simpanSnapshot($tahun, $b);
        }
        return $results;
    }
}
```

### 2.7 UI SIDBM (untuk Referensi FE)

```javascript
// resources/views/pelaporan/index.blade.php line 242-269
$(document).on('click', '#SimpanSaldo', function(e) {
    e.preventDefault()
    var tahun = $('select#tahun').val()
    var bulan = $('select#bulan').val()
    if (bulan < 1) { bulan = 0 }

    loading = Swal.fire({
        title: "Mohon Menunggu..",
        html: "Menyimpan Saldo Bulan " + bulan + " " + tahun,
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    })

    // SIDBM pakai window.open + postMessage untuk batch processing multi-bulan
    // App baru: cukup fetch API biasa
    childWindow = window.open('/simpan_saldo?tahun=' + tahun + '&bulan=' + bulan, '_blank');
})

window.addEventListener('message', function(event) {
    if (event.data === 'closed') {
        loading.close()
        window.location.reload()
    }
})
```

**Pattern SIDBM `window.open` + `postMessage`**: karena SIDBM self-redirect loop untuk proses 12 bulan sekaligus (lihat [§2.8 Self-Redirect Loop](#28-self-redirect-loop-pattern-sidbm)). App baru tidak butuh ini — frontend bisa panggil `simpanSemua()` dalam satu request atau looping `simpan(bulan=1..12)` di FE.

### 2.8 Self-Redirect Loop (Pattern SIDBM)

Logika SIDBM yang **perlu diketahui** tapi **tidak perlu di-porting**:

```php
// app/Http/Controllers/DashboardController.php line 953-979
$link = request()->url('');
$query = request()->query();

if (isset($query['bulan'])) {
    $query['bulan'] += 1;
} else {
    $query['bulan'] = date('m') + 1;
}
if (! isset($query['tahun'])) {
    $query['tahun'] = date('Y');
}

$query['bulan'] = str_pad($query['bulan'], 2, '0', STR_PAD_LEFT);
$next = $link.'?'.http_build_query($query);

if ((! ($kode_akun == '0' || $tahun != date('Y')) && $bulan >= date('m'))) {
    echo '<script>window.opener.postMessage("closed", "*"); window.close();</script>';
    exit;
}

if ($query['bulan'] < 13) {
    echo '<a href="'.$next.'" id="next"></a><script>document.querySelector("#next").click()</script>';
    exit;
} else {
    echo '<script>window.opener.postMessage("closed", "*"); window.close();</script>';
    exit;
}
```

**Penyesuaian**: ganti self-redirect loop dengan method `simpanSemua()` di service (lihat §2.6) atau looping di FE. Ini lebih clean dan timeout-safe.

### 2.9 Penyesuaian App Baru

- **Hapus field dinamis `tb2024`, `tbk2024`** di tabel `rekening`. Query dari tabel `saldo` langsung.
- **Hapus self-redirect loop** — gunakan batch method di service.
- **FE Vue/React**: cukup panggil `POST /api/simpan-saldo` dengan body `{tahun, bulan}` — tidak perlu `window.open` + `postMessage`. Tampilkan loading state dengan spinner FE.
- **Tambah endpoint** opsional: `POST /api/simpan-saldo/semua` untuk backfill semua bulan sekaligus.

---

## Fitur 3: Inventaris di Jurnal Umum

### 3.1 Overview

Modul **inventaris** terintegrasi dengan form **Jurnal Umum**. Tergantung kombinasi `sumber_dana` + `disimpan_ke` + `jenis_transaksi`, form yang ditampilkan berbeda:

| Skenario | sumber_dana | disimpan_ke | jenis_transaksi | Form | Aksi |
|---|---|---|---|---|---|
| **Pembelian inventaris** | (kas) | `1.2.01.*` atau `1.2.03.*` | (any) | `form_inventaris` | Insert Transaksi + Insert Inventaris |
| **Penghapusan / Jual / Revaluasi** | `1.2.01.01` atau `1.2.02.*` | `5.3.02.01` | `2` | `form_hapus_inventaris` | Insert/Update Inventaris + Insert Transaksi |
| **Penyusutan (auto-hitung)** | `1.2.02.01/02/03` | `5.1.07.08/09/10` | (any) | `form_nominal` (prefill `susut`) | Insert Transaksi biaya penyusutan |
| **Transaksi umum** | (lainnya) | (lainnya) | (any) | `form_nominal` | Insert Transaksi |

### 3.2 Skema DB

Lihat [Lampiran A](#lampiran-a-migration-sql). Tabel `inventaris`:

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT auto_increment | PK |
| `nama_barang` | VARCHAR | Nama barang |
| `tgl_beli` | DATE | Tanggal pembelian |
| `unit` | INT | Jumlah unit |
| `harsat` | DECIMAL(15,2) | Harga satuan |
| `umur_ekonomis` | INT | Dalam bulan |
| `jenis` | TINYINT | 1-4 (dari `rekening.lev3`) |
| `kategori` | TINYINT | 1-4 (dari `rekening.lev4`) |
| `status` | ENUM/VARCHAR | `Baik`, `Rusak`, `Hilang`, `Dijual`, `Hapus`, `Dihapus` |
| `tgl_validasi` | DATE | Tanggal update status |

### 3.3 Routes (SIDBM)

```php
Route::get('/transaksi/jurnal_umum', [TransaksiController::class, 'jurnalUmum'])->middleware('auth');
Route::get('/transaksi/form_nominal/', [TransaksiController::class, 'form'])->middleware('auth');
Route::post('/transaksi', [TransaksiController::class, 'store'])->middleware('auth');
Route::post('/transaksi/hapus', [TransaksiController::class, 'hapus'])->middleware('auth');
Route::post('/transaksi/reversal', [TransaksiController::class, 'reversal'])->middleware('auth');
```

### 3.4 Routes (Aplikasi Baru — API)

```php
// routes/api.php
Route::middleware('auth:sanctum')->prefix('transaksi')->group(function () {
    Route::get('/jurnal-umum/form', [JurnalUmumController::class, 'form']); // polymorphic form dispatch
    Route::post('/', [JurnalUmumController::class, 'store']); // universal store
    Route::delete('/{id}', [JurnalUmumController::class, 'destroy']);
    Route::post('/reversal', [JurnalUmumController::class, 'reversal']);
});
```

### 3.5 Controller (Aplikasi Baru)

```php
// app/Http/Controllers/Api/JurnalUmumController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventaris;
use App\Models\Rekening;
use App\Models\Transaksi;
use App\Services\InventarisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class JurnalUmumController extends Controller
{
    public function __construct(private InventarisService $invService) {}

    /**
     * GET /api/transaksi/jurnal-umum/form
     *
     * Dispatch form berdasarkan kombinasi sumber_dana + disimpan_ke + jenis_transaksi.
     * Return JSON deskripsi form, bukan HTML.
     */
    public function form(Request $request)
    {
        $request->validate([
            'tgl_transaksi' => 'required|date',
            'jenis_transaksi' => 'required|integer',
            'sumber_dana' => 'required|string',
            'disimpan_ke' => 'required|string',
        ]);

        $sumberDana = $request->sumber_dana;
        $disimpanKe = $request->disimpan_ke;
        $jenisTransaksi = (int) $request->jenis_transaksi;

        // SKENARIO 1: Penghapusan / Jual / Revaluasi inventaris
        if ($this->isHapusInventaris($sumberDana, $disimpanKe, $jenisTransaksi)) {
            [$jenis, $kategori] = $this->resolveJenisKategori($sumberDana);

            $inventaris = Inventaris::where('jenis', $jenis)
                ->where('kategori', $kategori)
                ->whereNotNull('tgl_beli')
                ->whereIn('status', ['Baik', 'Rusak'])
                ->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'nama_barang' => $i->nama_barang,
                    'unit' => $i->unit,
                    'nilai_buku' => $this->invService->nilaiBuku($request->tgl_transaksi, $i),
                    'encoded' => $i->id . '#' . $i->unit . '#' . $this->invService->nilaiBuku($request->tgl_transaksi, $i),
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'form_type' => 'hapus_inventaris',
                    'fields' => ['alasan', 'harsat', 'unit', 'harga_jual', '_nilai_buku'],
                    'inventaris_list' => $inventaris,
                ],
            ]);
        }

        // SKENARIO 2: Pembelian inventaris
        if ($this->isPembelianInventaris($disimpanKe)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'form_type' => 'inventaris',
                    'fields' => ['nama_barang', 'jumlah', 'harga_satuan', 'umur_ekonomis'],
                ],
            ]);
        }

        // SKENARIO 3: Default (transaksi umum) + cek auto-hitung penyusutan
        $susut = 0;
        if (in_array($disimpanKe, ['5.1.07.08', '5.1.07.09', '5.1.07.10'])) {
            $kategori = match ($sumberDana) {
                '1.2.02.01' => '2',
                '1.2.02.02' => '3',
                default => '4',
            };
            $susut = $this->invService->hitungSusutBulanan($request->tgl_transaksi, $kategori);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'form_type' => 'nominal',
                'fields' => ['keterangan', 'nominal'],
                'prefill' => ['nominal' => $susut],
            ],
        ]);
    }

    /**
     * POST /api/transaksi
     *
     * Universal store. Dispatch ke handler inventaris atau jurnal biasa.
     */
    public function store(Request $request)
    {
        $sumberDana = $request->sumber_dana;
        $disimpanKe = $request->disimpan_ke;
        $jenisTransaksi = (int) $request->jenis_transaksi;

        if ($this->isHapusInventaris($sumberDana, $disimpanKe, $jenisTransaksi)) {
            return $this->storeHapusInventaris($request);
        }
        if ($this->isPembelianInventaris($disimpanKe)) {
            return $this->storePembelianInventaris($request);
        }
        return $this->storeJurnalBiasa($request);
    }

    private function storeHapusInventaris(Request $request)
    {
        $data = $request->validate([
            'tgl_transaksi' => 'required|date',
            'nama_barang' => 'required|string', // format: "id#unit#nilai_buku"
            'alasan' => 'required|in:hapus,dijual,revaluasi,rusak,hilang',
            'unit' => 'required|integer|min:1',
            'harsat' => 'required|numeric',
            'harga_jual' => 'required|numeric',
            '_nilai_buku' => 'required|numeric',
        ]);

        [$idInv, $jumlahBarang, $nilaiBukuEnc] = explode('#', $data['nama_barang']);
        $inv = Inventaris::findOrFail($idInv);

        return DB::transaction(function () use ($data, $inv, $jumlahBarang, $idInv) {
            $status = ucfirst($data['alasan']);
            $sisaUnit = $jumlahBarang - $data['unit'];
            $nilaiBuku = $data['unit'] * $data['harsat'];

            $params = [
                'tgl_transaksi' => $data['tgl_transaksi'],
                'id_user' => auth()->id(),
            ];

            if ($data['alasan'] !== 'rusak') {
                // Insert Transaksi jurnal penghapusan
                Transaksi::create([
                    ...$params,
                    'rekening_debit' => $data['disimpan_ke'],
                    'rekening_kredit' => $data['sumber_dana'],
                    'jumlah' => $nilaiBuku,
                    'keterangan_transaksi' => "Penghapusan {$data['unit']} unit {$inv->nama_barang} ({$idInv}) karena {$data['alasan']}",
                ]);
            }

            if ($data['unit'] < $jumlahBarang) {
                // Update record asli (kurangi unit)
                $inv->update(['unit' => $sisaUnit, 'tgl_validasi' => $data['tgl_transaksi']]);
                if ($data['alasan'] !== 'revaluasi') {
                    // Insert record baru dengan status baru
                    Inventaris::create([
                        'nama_barang' => $inv->nama_barang,
                        'tgl_beli' => $inv->tgl_beli,
                        'unit' => $data['unit'],
                        'harsat' => $inv->harsat,
                        'umur_ekonomis' => $inv->umur_ekonomis,
                        'jenis' => $inv->jenis,
                        'kategori' => $inv->kategori,
                        'status' => $status,
                        'tgl_validasi' => $data['tgl_transaksi'],
                    ]);
                }
            } else {
                // Update record asli (ubah status)
                $inv->update(['status' => $status, 'tgl_validasi' => $data['tgl_transaksi']]);
            }

            // Revaluasi: insert inventaris baru dengan harga baru
            if ($data['alasan'] === 'revaluasi') {
                $hargaJual = (float) $data['harga_jual'];
                Inventaris::create([
                    'nama_barang' => $inv->nama_barang,
                    'tgl_beli' => $data['tgl_transaksi'],
                    'unit' => $data['unit'],
                    'harsat' => $hargaJual / $data['unit'],
                    'umur_ekonomis' => $inv->umur_ekonomis,
                    'jenis' => $inv->jenis,
                    'kategori' => $inv->kategori,
                    'status' => 'Baik',
                    'tgl_validasi' => $data['tgl_transaksi'],
                ]);

                // Jika ada selisih, insert jurnal surplus revaluasi
                if ($hargaJual != $data['_nilai_buku']) {
                    $jumlah = $hargaJual - $data['_nilai_buku'];
                    Transaksi::create([
                        ...$params,
                        'rekening_debit' => '1.1.01.01',
                        'rekening_kredit' => '4.3.01.01',
                        'jumlah' => abs($jumlah),
                        'keterangan_transaksi' => "Revaluasi {$data['unit']} unit {$inv->nama_barang} ({$idInv})",
                    ]);
                }
            }

            // Dijual: insert jurnal penjualan
            if ($data['alasan'] === 'dijual') {
                Transaksi::create([
                    ...$params,
                    'rekening_debit' => '1.1.01.01',
                    'rekening_kredit' => '4.2.01.04',
                    'jumlah' => $data['harga_jual'],
                    'keterangan_transaksi' => "Penjualan {$data['unit']} unit {$inv->nama_barang} ({$idInv})",
                ]);
            }

            $msg = $data['alasan'] === 'dijual'
                ? "Penjualan {$data['unit']} unit {$inv->nama_barang}"
                : "Penghapusan {$data['unit']} unit {$inv->nama_barang} karena {$data['alasan']}";

            return response()->json(['success' => true, 'message' => $msg]);
        });
    }

    private function storePembelianInventaris(Request $request)
    {
        $data = $request->validate([
            'tgl_transaksi' => 'required|date',
            'nama_barang' => 'required|string',
            'jumlah' => 'required|integer|min:1',
            'harga_satuan' => 'required|numeric|min:0',
            'umur_ekonomis' => 'required|integer|min:1',
            'relasi' => 'nullable|string',
        ]);

        $rekSimpan = Rekening::where('kode_akun', $request->disimpan_ke)->firstOrFail();

        return DB::transaction(function () use ($data, $rekSimpan) {
            $hargaSatuan = (float) $data['harga_satuan'];
            $total = $hargaSatuan * $data['jumlah'];

            // 1. Insert Transaksi jurnal
            $trx = Transaksi::create([
                'tgl_transaksi' => $data['tgl_transaksi'],
                'rekening_debit' => $request->disimpan_ke,
                'rekening_kredit' => $request->sumber_dana,
                'jumlah' => $total,
                'keterangan_transaksi' => "({$rekSimpan->nama_akun}) {$data['nama_barang']}",
                'relasi' => $data['relasi'] ?? '',
                'id_user' => auth()->id(),
            ]);

            // 2. Insert Inventaris baru
            $inv = Inventaris::create([
                'nama_barang' => $data['nama_barang'],
                'tgl_beli' => $data['tgl_transaksi'],
                'unit' => $data['jumlah'],
                'harsat' => $hargaSatuan,
                'umur_ekonomis' => $data['umur_ekonomis'],
                'jenis' => str_pad((string) $rekSimpan->lev3, 1, '0', STR_PAD_LEFT),
                'kategori' => str_pad((string) $rekSimpan->lev4, 1, '0', STR_PAD_LEFT),
                'status' => 'Baik',
                'tgl_validasi' => $data['tgl_transaksi'],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Transaksi {$rekSimpan->nama_akun} berhasil disimpan.",
                'data' => ['transaksi_id' => $trx->id, 'inventaris_id' => $inv->id],
            ]);
        });
    }

    private function storeJurnalBiasa(Request $request)
    {
        $data = $request->validate([
            'tgl_transaksi' => 'required|date',
            'sumber_dana' => 'required|string',
            'disimpan_ke' => 'required|string',
            'nominal' => 'required|numeric',
            'keterangan' => 'nullable|string',
            'relasi' => 'nullable|string',
        ]);

        $trx = Transaksi::create([
            'tgl_transaksi' => $data['tgl_transaksi'],
            'rekening_debit' => $data['disimpan_ke'],
            'rekening_kredit' => $data['sumber_dana'],
            'jumlah' => $data['nominal'],
            'keterangan_transaksi' => $data['keterangan'] ?? '',
            'relasi' => $data['relasi'] ?? '',
            'id_user' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil disimpan.',
            'data' => ['transaksi_id' => $trx->id],
        ]);
    }

    private function isHapusInventaris(string $sumberDana, string $disimpanKe, int $jenisTransaksi): bool
    {
        return (str_starts_with($sumberDana, '1.2.01.01') || str_starts_with($sumberDana, '1.2.02'))
            && str_starts_with($disimpanKe, '5.3.02.01')
            && $jenisTransaksi === 2;
    }

    private function isPembelianInventaris(string $disimpanKe): bool
    {
        return str_starts_with($disimpanKe, '1.2.01') || str_starts_with($disimpanKe, '1.2.03');
    }

    private function resolveJenisKategori(string $sumberDana): array
    {
        $kode = explode('.', $sumberDana);
        if (str_starts_with($sumberDana, '1.2.01.01')) {
            $jenis = (int) $kode[2];
            $kategori = (int) $kode[3];
        } else {
            // 1.2.02.x
            $jenis = (int) $kode[2] - 1;
            $kategori = (int) $kode[3] + 1;
        }
        return [$jenis, $kategori];
    }
}
```

### 3.6 Service Inventaris (Porting dari `App\Utils\Inventaris`)

```php
// app/Services/InventarisService.php
namespace App\Services;

use App\Models\Inventaris as ModelsInventaris;

class InventarisService
{
    /**
     * Hitung nilai buku (harga perolehan - akumulasi penyusutan).
     * Minimum 1 untuk klasifikasi khusus.
     */
    public function nilaiBuku(string $tgl, ModelsInventaris $inv): float
    {
        $hargaTotal = $inv->harsat * $inv->unit;

        // Kategori 1 Jenis 1: tanah (tidak disusutkan)
        if ($inv->kategori == 1 && $inv->jenis == 1) {
            return (float) $hargaTotal;
        }

        if ($inv->harsat <= 0) {
            return 0;
        }

        $penyusutan = round($hargaTotal / $inv->umur_ekonomis, 2);
        $bulanPakai = $this->bulan($inv->tgl_beli, $tgl);
        $nilai = $hargaTotal - ($penyusutan * $bulanPakai);

        return $nilai < 0 ? 1 : (float) $nilai;
    }

    /**
     * Hitung selisih bulan antara 2 tanggal.
     */
    public function bulan(string $start, string $end, string $periode = 'bulan'): int|float
    {
        // ... (porting dari App\Utils\Inventaris::bulan() SIDBM)
        // Implementasi lihat SIDBM line 40-114
    }

    /**
     * Hitung total akumulasi penyusutan sampai tgl_kondisi untuk kategori tertentu.
     */
    public function hitungSusutBulanan(string $tglKondisi, string $kategori): float
    {
        // ... (porting dari App\Utils\Inventaris::penyusutan() SIDBM line 116-245)
    }
}
```

**Catatan**: Method `bulan()` dan `hitungSusutBulanan()` logikanya panjang (60+ baris di SIDBM). Porting 1:1 dengan menghapus:
- `Session::get('lokasi') == '273'` (hack lokasi tertentu) → hapus
- `use DB; use Session;` → tidak perlu

### 3.7 Format `nama_barang = "id#unit#nilai_buku"`

Pattern SIDBM: di dropdown form hapus inventaris, value option di-encode:

```php
// form_hapus_inventaris.blade.php (SIDBM)
$inv->id . '#' . $inv->unit . '#' . UtilsInventaris::nilaiBuku($tgl_transaksi, $inv)
```

Saat submit, backend decode:

```php
$nama_barang = explode('#', $request->nama_barang);
$id_inv = $nama_barang[0];
$jumlah_barang = $nama_barang[1];
$nilai_buku_enc = $nama_barang[2];
```

**Untuk FE Vue/React**: lebih clean jika backend return list `inventaris` lengkap (id, nama, unit, nilai_buku) di response `/form`, FE select by `id`, dan submit `{inventaris_id, unit, ...}`. **Rekomendasi app baru**: kirim `inventaris_id` terpisah, **bukan** encoded string.

### 3.8 Penyesuaian App Baru

- **Hapus** `lokasi` field dari `inventaris` (single-tenant).
- **Ganti** encoded `nama_barang` → field terpisah `inventaris_id`.
- **Hapus** logic `Session::get('lokasi') == '273'` (hack lokasi tertentu) di `bulan()` & `nilaiBuku()`.
- **Pertahankan** enum string `status` (`Baik|Rusak|Hilang|Dijual|Hapus|Dihapus`) atau migrasi ke TINYINT dengan tabel lookup. Saran: **pertahankan string** untuk konsistensi dengan field lain (mis. `tgl_validasi`).
- **Tambah** endpoint `GET /api/inventaris` (CRUD inventaris) di luar jurnal umum, agar user bisa lihat/edit/daftar inventaris terpisah.

---

## Lampiran A: Migration SQL

### A.1 Tabel `rekening`

```sql
CREATE TABLE rekening (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_akun       VARCHAR(20) NOT NULL UNIQUE,
    nama_akun       VARCHAR(255) NOT NULL,
    lev1            TINYINT UNSIGNED NOT NULL,
    lev2            TINYINT UNSIGNED,
    lev3            TINYINT UNSIGNED,
    lev4            TINYINT UNSIGNED,
    tgl_nonaktif    DATE NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    INDEX idx_lev1 (lev1),
    INDEX idx_kode_akun (kode_akun)
);
```

**Catatan**: Field dinamis `tb2024`, `tb2023`, `tbk2024`, ... di SIDBM **tidak perlu di-porting**. Ambil snapshot dari tabel `saldo` langsung.

### A.2 Tabel `transaksi`

```sql
CREATE TABLE transaksi (
    id                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tgl_transaksi            DATE NOT NULL,
    rekening_debit           VARCHAR(20) NOT NULL,
    rekening_kredit          VARCHAR(20) NOT NULL,
    idtp                     BIGINT UNSIGNED DEFAULT 0,
    id_pinj                  BIGINT UNSIGNED DEFAULT 0,
    id_pinj_i                BIGINT UNSIGNED DEFAULT 0,
    keterangan_transaksi     TEXT,
    relasi                   VARCHAR(255),
    jumlah                   DECIMAL(15,2) NOT NULL,
    urutan                   INT DEFAULT 0,
    id_user                  BIGINT UNSIGNED NOT NULL,
    deleted_at               TIMESTAMP NULL,
    created_at               TIMESTAMP NULL,
    updated_at               TIMESTAMP NULL,
    INDEX idx_tgl (tgl_transaksi),
    INDEX idx_rek_debit (rekening_debit),
    INDEX idx_rek_kredit (rekening_kredit),
    INDEX idx_rek_tgl_debit (rekening_debit, tgl_transaksi),
    INDEX idx_rek_tgl_kredit (rekening_kredit, tgl_transaksi),
    FOREIGN KEY (rekening_debit) REFERENCES rekening(kode_akun),
    FOREIGN KEY (rekening_kredit) REFERENCES rekening(kode_akun)
);
```

### A.3 Tabel `saldo`

```sql
CREATE TABLE saldo (
    id          VARCHAR(30) NOT NULL PRIMARY KEY, -- format: <kode_akun_no_dot><tahun><bulan_2_digit>
    kode_akun   VARCHAR(20) NOT NULL,
    tahun       SMALLINT UNSIGNED NOT NULL,
    bulan       TINYINT UNSIGNED NOT NULL, -- 0 = saldo awal, 1-12 = bulanan, 13 = saldo akhir tahun
    debit       DECIMAL(15,2) DEFAULT 0,
    kredit      DECIMAL(15,2) DEFAULT 0,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    INDEX idx_kode_akun (kode_akun),
    INDEX idx_tahun_bulan (tahun, bulan),
    INDEX idx_kode_tahun_bulan (kode_akun, tahun, bulan)
);
```

**Contoh `id`**:
- `1210101202400` = `1.2.01.01` (tanpa titik) + `2024` + `00` (bulan=0, saldo awal 2024)
- `1210101202401` = `1.2.01.01` + `2024` + `01` (saldo Januari 2024)
- `1210101202413` = `1.2.01.01` + `2024` + `13` (saldo akhir tahun 2024)

### A.4 Tabel `inventaris`

```sql
CREATE TABLE inventaris (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_barang      VARCHAR(255) NOT NULL,
    tgl_beli         DATE NOT NULL,
    unit             INT UNSIGNED NOT NULL DEFAULT 1,
    harsat           DECIMAL(15,2) NOT NULL DEFAULT 0,
    umur_ekonomis    INT UNSIGNED NOT NULL, -- dalam bulan
    jenis            TINYINT UNSIGNED NOT NULL, -- 1-4 (dari rekening.lev3)
    kategori         TINYINT UNSIGNED NOT NULL, -- 1-4 (dari rekening.lev4)
    status           ENUM('Baik','Rusak','Hilang','Dijual','Hapus','Dihapus') NOT NULL DEFAULT 'Baik',
    tgl_validasi     DATE NULL,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,
    INDEX idx_jenis_kategori (jenis, kategori),
    INDEX idx_tgl_beli (tgl_beli),
    INDEX idx_status (status)
);
```

### A.5 Tabel `periode_buku` (Baru — Audit Trail Tutup Buku)

```sql
CREATE TABLE periode_buku (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tahun       SMALLINT UNSIGNED NOT NULL UNIQUE,
    status      ENUM('open','closed') NOT NULL DEFAULT 'open',
    closed_at   TIMESTAMP NULL,
    closed_by   BIGINT UNSIGNED NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL
);
```

**Cara pakai**: setiap eksekusi tutup buku, insert/update `periode_buku` set `status='closed'`. Query cek status:
```sql
SELECT status FROM periode_buku WHERE tahun = :tahun;
```

---

## Lampiran B: Contoh Payload Request/Response JSON

### B.1 Tutup Buku — Preview

**Request**:
```http
GET /api/transaksi/tutup-buku/preview?tahun=2024&tgl_pakai=2024-01-01
Authorization: Bearer <token>
```

**Response (success)**:
```json
{
    "success": true,
    "data": {
        "tahun": 2024,
        "tgl_kondisi": "2024-12-31",
        "surplus": 15000000,
        "can_execute": true,
        "saldo": [
            {
                "kode_akun": "1.1.01.01",
                "nama_akun": "Kas",
                "lev1": 1,
                "debit_akhir": 25000000,
                "kredit_akhir": 10000000
            },
            {
                "kode_akun": "3.2.01.01",
                "nama_akun": "Laba Ditahan",
                "lev1": 3,
                "debit_akhir": 0,
                "kredit_akhir": 50000000
            }
        ],
        "riwayat": {
            "jumlah_tutup_tahun_sebelumnya": 2,
            "total_periode": 5,
            "migrasi_saldo_required": true
        }
    }
}
```

**Response (constraint: bulan < 11)**:
```json
{
    "success": false,
    "message": "Tutup buku hanya dapat dilakukan mulai bulan November."
}
```

### B.2 Tutup Buku — Eksekusi

**Request**:
```http
POST /api/transaksi/tutup-buku/eksekusi
Content-Type: application/json
Authorization: Bearer <token>

{
    "tahun": 2024,
    "tgl_pakai": "2024-01-01",
    "pembagian_laba": false
}
```

**Response**:
```json
{
    "success": true,
    "message": "Tutup Buku Tahun 2024 berhasil.",
    "data": {
        "tahun_tutup": 2024,
        "tahun_awal_baru": 2025,
        "jumlah_saldo_ditulis": 87,
        "surplus": 15000000
    }
}
```

### B.3 Tutup Buku — Alokasi Laba

**Request**:
```http
POST /api/transaksi/tutup-buku/alokasi-laba
Content-Type: application/json
Authorization: Bearer <token>

{
    "tahun": 2024,
    "tgl_mad": "2024-12-31",
    "surplus": 15000000,
    "masyarakat": 3000000,
    "desa": 6000000,
    "penyerta_modal": 3000000,
    "laba_ditahan": 3000000
}
```

**Response**:
```json
{
    "success": true,
    "message": "Alokasi laba berhasil disimpan."
}
```

### B.4 Simpan Saldo — Single Bulan

**Request**:
```http
POST /api/simpan-saldo
Content-Type: application/json
Authorization: Bearer <token>

{
    "tahun": 2024,
    "bulan": 5
}
```

**Response**:
```json
{
    "success": true,
    "message": "Saldo bulan 5 tahun 2024 berhasil disimpan.",
    "data": {
        "tahun": 2024,
        "bulan": 5,
        "jumlah_saldo": 87
    }
}
```

### B.5 Simpan Saldo — Semua Bulan

**Request**:
```http
POST /api/simpan-saldo/semua
Content-Type: application/json
Authorization: Bearer <token>

{
    "tahun": 2024
}
```

**Response**:
```json
{
    "success": true,
    "data": [
        {"bulan": 0, "jumlah_saldo": 87},
        {"bulan": 1, "jumlah_saldo": 87},
        {"bulan": 2, "jumlah_saldo": 87},
        ...
        {"bulan": 12, "jumlah_saldo": 87}
    ]
}
```

### B.6 Jurnal Umum — Get Form (Pembelian Inventaris)

**Request**:
```http
GET /api/transaksi/jurnal-umum/form?tgl_transaksi=2024-06-15&jenis_transaksi=2&sumber_dana=1.1.01.01&disimpan_ke=1.2.01.01
Authorization: Bearer <token>
```

**Response**:
```json
{
    "success": true,
    "data": {
        "form_type": "inventaris",
        "fields": ["nama_barang", "jumlah", "harga_satuan", "umur_ekonomis"],
        "prefill": {}
    }
}
```

### B.7 Jurnal Umum — Get Form (Hapus Inventaris)

**Request**:
```http
GET /api/transaksi/jurnal-umum/form?tgl_transaksi=2024-06-15&jenis_transaksi=2&sumber_dana=1.2.01.01&disimpan_ke=5.3.02.01
Authorization: Bearer <token>
```

**Response**:
```json
{
    "success": true,
    "data": {
        "form_type": "hapus_inventaris",
        "fields": ["alasan", "harsat", "unit", "harga_jual", "_nilai_buku"],
        "inventaris_list": [
            {
                "id": 12,
                "nama_barang": "Laptop Asus",
                "unit": 3,
                "nilai_buku": 8500000
            },
            {
                "id": 15,
                "nama_barang": "Printer Canon",
                "unit": 1,
                "nilai_buku": 1200000
            }
        ]
    }
}
```

### B.8 Jurnal Umum — Get Form (Auto Susut)

**Request**:
```http
GET /api/transaksi/jurnal-umum/form?tgl_transaksi=2024-06-15&jenis_transaksi=1&sumber_dana=1.2.02.01&disimpan_ke=5.1.07.08
Authorization: Bearer <token>
```

**Response**:
```json
{
    "success": true,
    "data": {
        "form_type": "nominal",
        "fields": ["keterangan", "nominal"],
        "prefill": {
            "nominal": 250000
        }
    }
}
```

### B.9 Jurnal Umum — Store Pembelian Inventaris

**Request**:
```http
POST /api/transaksi
Content-Type: application/json
Authorization: Bearer <token>

{
    "tgl_transaksi": "2024-06-15",
    "jenis_transaksi": 2,
    "sumber_dana": "1.1.01.01",
    "disimpan_ke": "1.2.01.01",
    "nama_barang": "Laptop Asus ROG",
    "jumlah": 3,
    "harga_satuan": 10000000,
    "umur_ekonomis": 48,
    "relasi": "Toko ABC"
}
```

**Response**:
```json
{
    "success": true,
    "message": "Transaksi Peralatan Kantor berhasil disimpan.",
    "data": {
        "transaksi_id": 1245,
        "inventaris_id": 28
    }
}
```

### B.10 Jurnal Umum — Store Hapus Inventaris

**Request**:
```http
POST /api/transaksi
Content-Type: application/json
Authorization: Bearer <token>

{
    "tgl_transaksi": "2024-06-15",
    "jenis_transaksi": 2,
    "sumber_dana": "1.2.01.01",
    "disimpan_ke": "5.3.02.01",
    "inventaris_id": 12,
    "alasan": "dijual",
    "harsat": 3000000,
    "unit": 1,
    "harga_jual": 2500000,
    "_nilai_buku": 3000000
}
```

**Response**:
```json
{
    "success": true,
    "message": "Penjualan 1 unit Laptop Asus"
}
```

---

## Lampiran C: Test Case

### C.1 Tutup Buku

```php
// tests/Feature/TutupBukuTest.php
namespace Tests\Feature;

use App\Models\Rekening;
use App\Models\Saldo;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutupBukuTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutup_buku_berhasil_tahun_lalu()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Setup: rekening + saldo bulan=0 dan bulan=12 tahun 2023
        $kas = Rekening::factory()->create(['kode_akun' => '1.1.01.01', 'lev1' => 1, 'nama_akun' => 'Kas']);
        Saldo::create(['kode_akun' => '1.1.01.01', 'tahun' => 2023, 'bulan' => 0, 'debit' => 10000000, 'kredit' => 0]);
        Saldo::create(['kode_akun' => '1.1.01.01', 'tahun' => 2023, 'bulan' => 12, 'debit' => 5000000, 'kredit' => 0]);

        $response = $this->postJson('/api/transaksi/tutup-buku/eksekusi', [
            'tahun' => 2023,
            'tgl_pakai' => '2023-01-01',
            'pembagian_laba' => false,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        // Assert: saldo awal 2024 untuk 1.1.01.01 = 15.000.000
        $this->assertDatabaseHas('saldo', [
            'kode_akun' => '1.1.01.01',
            'tahun' => 2024,
            'bulan' => 0,
            'debit' => '15000000.00',
        ]);
    }

    public function test_tutup_buku_ditolak_jika_bulan_kurang_dari_11()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Misal sekarang bulan Oktober (10)
        // Tutup buku tahun sekarang harus ditolak
        $response = $this->postJson('/api/transaksi/tutup-buku/eksekusi', [
            'tahun' => (int) date('Y'),
            'tgl_pakai' => date('Y-m-d'),
            'pembagian_laba' => false,
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_tutup_buku_idempotent()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Rekening::factory()->create(['kode_akun' => '1.1.01.01', 'lev1' => 1]);
        Saldo::create(['kode_akun' => '1.1.01.01', 'tahun' => 2023, 'bulan' => 0, 'debit' => 1000, 'kredit' => 0]);

        // Eksekusi 2x
        $this->postJson('/api/transaksi/tutup-buku/eksekusi', [
            'tahun' => 2023, 'tgl_pakai' => '2023-01-01', 'pembagian_laba' => false,
        ])->assertOk();
        $this->postJson('/api/transaksi/tutup-buku/eksekusi', [
            'tahun' => 2023, 'tgl_pakai' => '2023-01-01', 'pembagian_laba' => false,
        ])->assertOk();

        // Assert: hanya 1 baris saldo untuk 1.1.01.01 tahun 2024 bulan 0
        $this->assertEquals(1, Saldo::where('kode_akun', '1.1.01.01')
            ->where('tahun', 2024)
            ->where('bulan', 0)
            ->count());
    }

    public function test_tutup_buku_skip_ikhtisar_laba_rugi()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // 3.2.02.01 = Ikhtisar Laba/Rugi (lev1=3)
        Rekening::factory()->create(['kode_akun' => '3.2.02.01', 'lev1' => 3]);
        Saldo::create(['kode_akun' => '3.2.02.01', 'tahun' => 2023, 'bulan' => 0, 'debit' => 5000, 'kredit' => 0]);

        $this->postJson('/api/transaksi/tutup-buku/eksekusi', [
            'tahun' => 2023, 'tgl_pakai' => '2023-01-01', 'pembagian_laba' => false,
        ])->assertOk();

        // 3.2.02.01 TIDAK boleh dibuatkan saldo awal tahun 2024
        $this->assertDatabaseMissing('saldo', [
            'kode_akun' => '3.2.02.01',
            'tahun' => 2024,
            'bulan' => 0,
        ]);
    }

    public function test_tutup_buku_laba_ditahan_ditambah_surplus()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // 3.2.01.01 = Laba Ditahan (lev1=3)
        Rekening::factory()->create(['kode_akun' => '3.2.01.01', 'lev1' => 3]);
        Saldo::create(['kode_akun' => '3.2.01.01', 'tahun' => 2023, 'bulan' => 0, 'debit' => 0, 'kredit' => 10000]);

        // Pendapatan 4.1.01.01 + Beban 5.1.01.01 = surplus
        Rekening::factory()->create(['kode_akun' => '4.1.01.01', 'lev1' => 4]);
        Rekening::factory()->create(['kode_akun' => '5.1.01.01', 'lev1' => 5]);
        Transaksi::factory()->create([
            'rekening_debit' => '1.1.01.01', 'rekening_kredit' => '4.1.01.01',
            'jumlah' => 50000, 'tgl_transaksi' => '2023-12-31',
        ]);
        Transaksi::factory()->create([
            'rekening_debit' => '5.1.01.01', 'rekening_kredit' => '1.1.01.01',
            'jumlah' => 20000, 'tgl_transaksi' => '2023-12-31',
        ]);

        $this->postJson('/api/transaksi/tutup-buku/eksekusi', [
            'tahun' => 2023, 'tgl_pakai' => '2023-01-01', 'pembagian_laba' => false,
        ])->assertOk();

        // Laba ditahan 2024 = 10000 (saldo awal) + 30000 (surplus) = 40000
        $this->assertDatabaseHas('saldo', [
            'kode_akun' => '3.2.01.01',
            'tahun' => 2024,
            'bulan' => 0,
            'kredit' => '40000.00',
        ]);
    }
}
```

### C.2 Simpan Saldo

```php
// tests/Feature/SimpanSaldoTest.php
namespace Tests\Feature;

use App\Models\Rekening;
use App\Models\Saldo;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpanSaldoTest extends TestCase
{
    use RefreshDatabase;

    public function test_simpan_saldo_awal_tahun_dari_snapshot_tutup_buku()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $rek = Rekening::factory()->create(['kode_akun' => '1.1.01.01', 'lev1' => 1]);

        // Snapshot tutup buku tahun sebelumnya (bulan=13)
        Saldo::create([
            'id' => '1210101202300', 'kode_akun' => '1.1.01.01',
            'tahun' => 2023, 'bulan' => 0, 'debit' => 10000000, 'kredit' => 0,
        ]);

        $response = $this->postJson('/api/simpan-saldo', [
            'tahun' => 2024,
            'bulan' => 0,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        // Saldo awal 2024 harus sama dengan snapshot 2023
        $this->assertDatabaseHas('saldo', [
            'kode_akun' => '1.1.01.01',
            'tahun' => 2024,
            'bulan' => 0,
            'debit' => '10000000.00',
        ]);
    }

    public function test_simpan_saldo_bulanan_agregat_transaksi()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Rekening::factory()->create(['kode_akun' => '1.1.01.01', 'lev1' => 1]);
        Rekening::factory()->create(['kode_akun' => '4.1.01.01', 'lev1' => 4]);

        Transaksi::factory()->create([
            'rekening_debit' => '1.1.01.01', 'rekening_kredit' => '4.1.01.01',
            'jumlah' => 100000, 'tgl_transaksi' => '2024-03-15',
        ]);
        Transaksi::factory()->create([
            'rekening_debit' => '1.1.01.01', 'rekening_kredit' => '4.1.01.01',
            'jumlah' => 50000, 'tgl_transaksi' => '2024-05-20',
        ]);

        $response = $this->postJson('/api/simpan-saldo', [
            'tahun' => 2024,
            'bulan' => 6,
        ]);

        $response->assertOk();

        // Saldo 1.1.01.01 bulan 6 (akhir Juni) harus agregat s/d Juni = 150.000
        $this->assertDatabaseHas('saldo', [
            'kode_akun' => '1.1.01.01',
            'tahun' => 2024,
            'bulan' => 6,
            'debit' => '150000.00',
        ]);
    }

    public function test_simpan_saldo_idempotent()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Rekening::factory()->create(['kode_akun' => '1.1.01.01', 'lev1' => 1]);
        Transaksi::factory()->create([
            'rekening_debit' => '1.1.01.01', 'rekening_kredit' => '4.1.01.01',
            'jumlah' => 100000, 'tgl_transaksi' => '2024-03-15',
        ]);

        // Panggil 2x
        $this->postJson('/api/simpan-saldo', ['tahun' => 2024, 'bulan' => 6])->assertOk();
        $this->postJson('/api/simpan-saldo', ['tahun' => 2024, 'bulan' => 6])->assertOk();

        // Hanya 1 baris (delete-then-insert)
        $this->assertEquals(1, Saldo::where('kode_akun', '1.1.01.01')
            ->where('tahun', 2024)
            ->where('bulan', 6)
            ->count());
    }
}
```

### C.3 Inventaris

```php
// tests/Feature/InventarisTest.php
namespace Tests\Feature;

use App\Models\Inventaris;
use App\Models\Rekening;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarisTest extends TestCase
{
    use RefreshDatabase;

    public function test_pembelian_inventaris_insert_transaksi_dan_inventaris()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Rekening::factory()->create(['kode_akun' => '1.2.01.01', 'lev1' => 1, 'lev3' => 1, 'lev4' => 1, 'nama_akun' => 'Peralatan Kantor']);

        $response = $this->postJson('/api/transaksi', [
            'tgl_transaksi' => '2024-06-15',
            'jenis_transaksi' => 2,
            'sumber_dana' => '1.1.01.01',
            'disimpan_ke' => '1.2.01.01',
            'nama_barang' => 'Laptop Asus ROG',
            'jumlah' => 3,
            'harga_satuan' => 10000000,
            'umur_ekonomis' => 48,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('transaksi', [
            'rekening_debit' => '1.2.01.01',
            'rekening_kredit' => '1.1.01.01',
            'jumlah' => 30000000,
        ]);
        $this->assertDatabaseHas('inventaris', [
            'nama_barang' => 'Laptop Asus ROG',
            'unit' => 3,
            'harsat' => '10000000.00',
            'status' => 'Baik',
        ]);
    }

    public function test_form_dispatch_pembelian()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/transaksi/jurnal-umum/form?' . http_build_query([
            'tgl_transaksi' => '2024-06-15',
            'jenis_transaksi' => 2,
            'sumber_dana' => '1.1.01.01',
            'disimpan_ke' => '1.2.01.01',
        ]));

        $response->assertOk()
            ->assertJson(['data' => ['form_type' => 'inventaris']]);
    }

    public function test_form_dispatch_hapus_dengan_daftar_inventaris()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Inventaris::factory()->create([
            'nama_barang' => 'Laptop', 'jenis' => 1, 'kategori' => 1,
            'status' => 'Baik', 'unit' => 3, 'harsat' => 10000000,
        ]);

        $response = $this->getJson('/api/transaksi/jurnal-umum/form?' . http_build_query([
            'tgl_transaksi' => '2024-06-15',
            'jenis_transaksi' => 2,
            'sumber_dana' => '1.2.01.01',
            'disimpan_ke' => '5.3.02.01',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.form_type', 'hapus_inventaris')
            ->assertJsonCount(1, 'data.inventaris_list');
    }

    public function test_penjualan_inventaris_insert_transaksi_penjualan()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Rekening::factory()->create(['kode_akun' => '1.2.01.01', 'lev1' => 1]);
        $inv = Inventaris::factory()->create([
            'jenis' => 1, 'kategori' => 1, 'status' => 'Baik',
            'unit' => 3, 'harsat' => 1000000,
        ]);

        $response = $this->postJson('/api/transaksi', [
            'tgl_transaksi' => '2024-06-15',
            'jenis_transaksi' => 2,
            'sumber_dana' => '1.2.01.01',
            'disimpan_ke' => '5.3.02.01',
            'inventaris_id' => $inv->id,
            'alasan' => 'dijual',
            'harsat' => 1000000,
            'unit' => 1,
            'harga_jual' => 800000,
            '_nilai_buku' => 1000000,
        ]);

        $response->assertOk();

        // Jurnal penjualan
        $this->assertDatabaseHas('transaksi', [
            'rekening_debit' => '1.1.01.01',
            'rekening_kredit' => '4.2.01.04',
            'jumlah' => 800000,
        ]);
        // Inventaris diupdate status=Dijual
        $this->assertDatabaseHas('inventaris', [
            'id' => $inv->id,
            'status' => 'Dijual',
        ]);
    }

    public function test_hapus_sebagian_unit_insert_record_baru()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $inv = Inventaris::factory()->create([
            'jenis' => 1, 'kategori' => 1, 'status' => 'Baik',
            'unit' => 5, 'harsat' => 1000000,
        ]);

        $this->postJson('/api/transaksi', [
            'tgl_transaksi' => '2024-06-15',
            'jenis_transaksi' => 2,
            'sumber_dana' => '1.2.01.01',
            'disimpan_ke' => '5.3.02.01',
            'inventaris_id' => $inv->id,
            'alasan' => 'hapus',
            'harsat' => 1000000,
            'unit' => 2,
            'harga_jual' => 0,
            '_nilai_buku' => 2000000,
        ])->assertOk();

        // Record asli: unit berkurang jadi 3
        $this->assertDatabaseHas('inventaris', [
            'id' => $inv->id,
            'unit' => 3,
            'status' => 'Baik',
        ]);
        // Record baru: unit=2, status=Hapus
        $this->assertDatabaseHas('inventaris', [
            'unit' => 2,
            'status' => 'Hapus',
        ]);
        // Total 2 baris inventaris
        $this->assertEquals(2, Inventaris::count());
    }

    public function test_auto_hitung_penyusutan_prefill_nominal()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Setup inventaris kategori 2
        Inventaris::factory()->create([
            'jenis' => 1, 'kategori' => 2, 'status' => 'Baik',
            'tgl_beli' => '2024-01-15', 'unit' => 1, 'harsat' => 12000000, 'umur_ekonomis' => 48,
        ]);

        // Form untuk akun biaya penyusutan 5.1.07.08 + sumber 1.2.02.01 (kategori 2)
        $response = $this->getJson('/api/transaksi/jurnal-umum/form?' . http_build_query([
            'tgl_transaksi' => '2024-06-15',
            'jenis_transaksi' => 1,
            'sumber_dana' => '1.2.02.01',
            'disimpan_ke' => '5.1.07.08',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.form_type', 'nominal');

        // Prefill nominal harus > 0
        $nominal = $response->json('data.prefill.nominal');
        $this->assertGreaterThan(0, $nominal);
    }
}
```

### C.4 Menjalankan Test

```bash
# Setup database testing
php artisan migrate:fresh --env=testing
php artisan db:seed --env=testing

# Jalankan test fitur terkait
php artisan test --filter=TutupBukuTest
php artisan test --filter=SimpanSaldoTest
php artisan test --filter=InventarisTest

# Coverage report
php artisan test --coverage --min=80
```

---

## Ringkasan Checklist Implementasi

- [ ] Setup migration: `saldo`, `rekening`, `transaksi`, `inventaris`, `periode_buku`
- [ ] Hapus `TenantAware` trait dari semua model (`Saldo`, `Inventaris`, `Rekening`, `Transaksi`, dst.)
- [ ] Hapus semua `Session::get('lokasi')` di controller
- [ ] Buat service classes: `TutupBukuService`, `SaldoService`, `InventarisService`
- [ ] Buat controller API: `TutupBukuController`, `SaldoController`, `JurnalUmumController`
- [ ] Daftarkan routes di `routes/api.php` dengan prefix `/api`
- [ ] **Tutup Buku**: implement `preview`, `eksekusi`, `alokasiLaba`, `reopen`
- [ ] **Simpan Saldo**: implement `simpan` (single) + `simpanSemua` (batch)
- [ ] **Inventaris**: implement `form` (polymorphic dispatch) + `store` (3 cabang)
- [ ] Test: jalankan `php artisan test` dan pastikan semua hijau
- [ ] **Field `id` deterministik** di `saldo` & `inventaris` (jaga idempotency)
- [ ] **DB::transaction** untuk multi-insert (tutup buku, alokasi laba, hapus inventaris)
- [ ] **Validasi constraint** tutup buku: bulan ≥ 11 atau tahun < tahun_skrg
- [ ] **Dokumentasikan** response format untuk FE team (konsisten `{success, message, data}`)

---

**Versi dokumen**: 1.0
**Tanggal**: 2026-07-24
**Asal**: SIDBM (Sistem Informasi Desa Berdaya Mandiri)
**Tujuan porting**: Aplikasi baru (Laravel API + Vue/React FE)
