# Dokumentasi Perubahan Backend - Fitur Tagihan & Pembayaran
Dokumen ini berisi daftar perubahan yang perlu diterapkan pada sisi backend Laravel agar fitur transaksi tagihan bulanan dan pembayarannya dapat berfungsi selaras dengan frontend yang baru diunggah.

---

## 📌 Ringkasan Kebutuhan Frontend (Untuk Pengembang Backend)
Berikut adalah daftar kebutuhan API, hak akses, dan perbaikan logika bisnis yang dibutuhkan oleh tim Frontend:

1. **API Pencarian Pelanggan Terintegrasi (`GET /api/customers/search`)**
   - **Kebutuhan:** Autocomplete pencarian pelanggan aktif berdasarkan NIK, kode, nama user, atau nama pendaftar.
   - **Data Balikan:** Harus menyertakan detail paket air (`packageName`), abodemen, nominal denda, dan array blok tarif air lengkap agar bisa dihitung secara real-time di UI.

2. **API Konfirmasi Pembayaran (`POST /api/monthly-bills/{id}/pay`)**
   - **Kebutuhan:** Mengubah status pembayaran tagihan menjadi `paid` (Lunas).
   - **Logika Bisnis:** Secara otomatis mencatat data transaksi pembayaran baru ke dalam tabel riwayat pembayaran (`bill_payments`) beserta user-ID admin pengonfirmasi (`confirmed_by`).

3. **API Rekap Penggunaan Air Bulanan (`GET /api/monthly-bills/usage`)**
   - **Kebutuhan:** Membaca data pemakaian air (meter awal, meter akhir, volume pemakaian, nominal tagihan, status bayar) per bulan/tahun tertentu untuk diisikan di tabel rekap/laporan.

4. **Peluasan Hak Akses Catat Meteran (Middleware)**
   - **Kebutuhan:** Izinkan role `admin` mengakses endpoint `GET /api/meter-readings/pending` and `POST /api/meter-readings` agar admin bisa membantu memasukkan catatan meteran (tidak hanya role `teknisi` saja).

5. **Perbaikan Logika Kalkulasi di Backend (`BillingService` & `MonthlyBillService`)**
   - **Batas Blok Akhir (Tak Terhingga):** Logika batas tak terhingga (`max` bernilai `null`) pada blok tarif progresif perlu diganti dari menggunakan rumus rentang (`max - min + 1`) ke pengambilan sisa pemakaian secara utuh agar tidak menghasilkan hitungan bernilai minus/nol.
   - **Kumulatif Denda:** Ubah pengecekan denda agar mengecek *semua* tagihan belum terbayar (`unpaid`) di bulan-bulan sebelumnya, bukan hanya tepat 2 bulan ke belakang.
   - **Jatuh Tempo:** Ubah tanggal jatuh tempo default tagihan baru menjadi **tanggal 20 bulan berikutnya**.

---

## 💻 Panduan Teknis & Implementasi Kode Backend

## 1. Perubahan Router (`backend/routes/api.php`)
Tambahkan beberapa endpoint baru untuk kebutuhan pencarian pelanggan terintegrasi dan data penggunaan air bulanan, serta sesuaikan middleware untuk pencatatan meteran:

```php
// Di dalam group Route::middleware(['auth:sanctum', 'role:admin'])->group(...)
// -------------------------------------------------------------
// Tambahkan endpoint pencarian pelanggan aktif
Route::get('/customers/search', [CustomerController::class, 'searchActive']);

// Tambahkan endpoint recap penggunaan air
Route::get('monthly-bills/usage', [MonthlyBillController::class, 'usage']);
```

```php
// Sesuaikan grup middleware pencatatan meteran agar bisa diakses baik oleh teknisi maupun admin
// -------------------------------------------------------------
Route::middleware(['auth:sanctum', 'role:admin,teknisi'])->group(function () {
    Route::get('meter-readings/pending', [MeterReadingController::class, 'index']);
    Route::post('meter-readings', [MeterReadingController::class, 'store']);
});
```

---

## 2. Model & Kontroler Pelanggan (`CustomerController.php`)
Tambahkan metode `searchActive` pada `CustomerController` untuk memproses pencarian pelanggan aktif beserta data tarif dan abodemennya yang akan dikonsumsi oleh formulir transaksi tagihan:

```php
public function searchActive(Request $request)
{
    $query = Customer::with(['user', 'ticket.package.waterTariffBlocks']);

    if ($request->search) {
        $searchTerm = '%' . $request->search . '%';
        $query->where(function($q) use ($searchTerm) {
            $q->where('customer_code', 'like', $searchTerm)
              ->orWhereHas('ticket', function ($tq) use ($searchTerm) {
                  $tq->where('applicant_name', 'like', $searchTerm);
              })
              ->orWhereHas('user', function ($uq) use ($searchTerm) {
                  $uq->where('name', 'like', $searchTerm);
              });
        });
    }

    $customers = $query->take(20)->get();

    return response()->json([
        'success' => true,
        'data' => $customers->map(function ($c) {
            $address = $c->ticket ? $c->ticket->address : '';
            $village = $address ? explode(',', $address)[0] : '-';
            
            $rt = '-';
            if (preg_match('/rt\s*[:\.]?\s*(\d+)/i', $address, $matches)) {
                $rt = $matches[1];
            }

            $package = $c->ticket && $c->ticket->package ? $c->ticket->package : null;
            $packageName = $package ? $package->name : 'Paket Standar';
            $abodemen = $package ? (float)$package->monthly_abodemen : 10000.0;
            $penalty = $package ? (float)$package->late_penalty : 5000.0;
            
            $tariffBlocks = [];
            if ($package && $package->waterTariffBlocks && count($package->waterTariffBlocks) > 0) {
                foreach ($package->waterTariffBlocks as $block) {
                    $tariffBlocks[] = [
                        'min' => (int)$block->usage_min_m3,
                        'max' => $block->usage_max_m3 === null ? '∞' : (int)$block->usage_max_m3,
                        'price' => (float)$block->price_per_m3,
                    ];
                }
            } else {
                $tariffBlocks = [
                    ['min' => 0, 'max' => 10, 'price' => 2000.0],
                    ['min' => 11, 'max' => 20, 'price' => 3000.0],
                    ['min' => 21, 'max' => '∞', 'price' => 4500.0],
                ];
            }

            return [
                'id' => $c->customer_code,
                'customer_id' => $c->id,
                'installationCode' => $c->customer_code,
                'name' => $c->ticket ? $c->ticket->applicant_name : ($c->user ? $c->user->name : 'N/A'),
                'village' => $village,
                'hamlet' => '-',
                'rt' => $rt,
                'rw' => '-',
                'cater' => 'Admin',
                'status' => 'AKTIF',
                'packageName' => $packageName,
                'abodemen' => $abodemen,
                'penalty' => $penalty,
                'tariffBlocks' => $tariffBlocks,
            ];
        })
    ]);
}
```

---

## 3. Kontroler Tagihan Bulanan (`MonthlyBillController.php`)
Tambahkan logika pembayaran tagihan dan pencatatan riwayat pemakaian air:

```php
// Konfirmasi pembayaran tagihan bulanan
public function pay($id)
{
    $bill = MonthlyBill::findOrFail($id);

    if ($bill->status == 'paid') {
        return response()->json([
            'success' => false,
            'message' => 'Tagihan sudah dibayar'
        ], 400);
    }

    // Update status ke Lunas
    $bill->update([
        'status' => 'paid'
    ]);

    // Simpan data pembayaran ke bill_payments
    $payment = BillPayment::create([
        'bill_id' => $bill->id,
        'amount_paid' => $bill->total_amount,
        'confirmed_by' => Auth::id(),
        'paid_at' => now()
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Pembayaran berhasil dikonfirmasi',
        'payment' => $payment
    ]);
}

// Rekap daftar pemakaian air bulanan
public function usage(Request $request)
{
    $month = $request->month ?: Carbon::now()->month;
    $year = $request->year ?: Carbon::now()->year;

    $customers = Customer::with(['user', 'ticket'])->get();

    $readings = MeterReading::where('reading_month', $month)
        ->where('reading_year', $year)
        ->get()->keyBy('customer_id');

    $bills = MonthlyBill::where('billing_period_month', $month)
        ->where('billing_period_year', $year)
        ->get()->keyBy('customer_id');

    $data = $customers->map(function ($c) use ($readings, $bills, $year, $month) {
        $reading = $readings->get($c->id);
        $bill = $bills->get($c->id);
        
        $meterAwal = $reading ? ($reading->meter_value - ($bill ? $bill->usage_m3 : ($reading->meter_value - $c->initial_meter_reading))) : $c->initial_meter_reading;
        $meterAkhir = $reading ? $reading->meter_value : $c->initial_meter_reading;
        $pemakaian = $reading ? ($meterAkhir - $meterAwal) : 0;
        $tagihan = $bill ? $bill->total_amount : ($pemakaian * 4500);

        $status = 'PENDING';
        if ($bill) {
            $status = $bill->status == 'paid' ? 'PAID' : 'UNPAID';
        }

        $rt = '-';
        if ($c->ticket && $c->ticket->address) {
            if (preg_match('/rt\s*[:\.]?\s*(\d+)/i', $c->ticket->address, $matches)) {
                $rt = 'RT ' . $matches[1];
            }
        }

        return [
            'id' => $c->id,
            'customer_code' => $c->customer_code,
            'nama' => $c->ticket ? $c->ticket->applicant_name : ($c->user ? $c->user->name : 'N/A'),
            'initials' => strtoupper(substr($c->ticket ? $c->ticket->applicant_name : ($c->user ? $c->user->name : 'N/A'), 0, 2)),
            'avatarColor' => '#' . substr(md5($c->id), 0, 6),
            'desa' => $c->ticket ? $c->ticket->address : '',
            'dusun' => $c->ticket ? explode(',', $c->ticket->address)[0] : '',
            'rt' => $rt,
            'meterAwal' => $meterAwal,
            'meterAkhir' => $meterAkhir,
            'pemakaian' => $pemakaian,
            'tagihan' => $tagihan,
            'tanggalAkhir' => $reading ? Carbon::parse($reading->recorded_at)->format('Y-m-d') : Carbon::now()->format('Y-m-d'),
            'jatuhTempo' => $bill ? $bill->due_date : Carbon::create($year, $month, 20)->addMonth()->format('Y-m-d'),
            'status' => $status
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}
```

---

## 4. Perbaikan Layanan Tagihan (`BillingService.php` & `MonthlyBillService.php`)

### A. Perubahan Tanggal Jatuh Tempo (`due_date`)
Tanggal jatuh tempo diseragamkan ke tanggal **20 bulan berikutnya**:
```php
// Di file BillingService.php & MonthlyBillService.php ganti penetapan due_date ke:
'due_date' => \Carbon\Carbon::create($year, $month, 20)->addMonth()->format('Y-m-d'),
```

### B. Perbaikan Logika Perhitungan Tarif Progresif
Ubah logika perhitungan blok tarif progresif untuk memastikan blok tak terhingga (`max` adalah `null` / infinity) tidak menghasilkan hitungan sisa pemakaian negatif (`minus`):

```php
// Ganti logika kalkulasi di calculateProgressiveCharge (BillingService) & calculateTariff (MonthlyBillService) menjadi:
foreach ($blocks as $block) {
    if ($remaining <= 0) break;

    $min = (float) $block->usage_min_m3;
    $max = $block->usage_max_m3 !== null ? (float) $block->usage_max_m3 : null;

    if ($max !== null) {
        $range = $max - $min + 1;
        $used = min($remaining, $range);
    } else {
        $used = $remaining; // Jika blok terakhir (tidak memiliki batas atas), ambil semua sisa penggunaan air
    }

    $total += $used * (float) $block->price_per_m3;
    $remaining -= $used;
}
```

### C. Akumulasi Denda Berkelanjutan (`calculatePenalty`)
Ubah logika perhitungan denda dari yang sebelumnya hanya mengecek tepat 2 bulan lalu, menjadi **mengecek apakah ada tagihan berstatus `unpaid` di bulan-bulan sebelumnya**:

```php
private function calculatePenalty(Customer $customer, int $year, int $month): float
{
    if (!$customer->ticket || !$customer->ticket->package) {
        return 0;
    }

    // Cek apakah ada tagihan belum terbayar di bulan-bulan sebelumnya
    $hasUnpaidBefore = MonthlyBill::where('customer_id', $customer->id)
        ->where('status', 'unpaid')
        ->where(function ($query) use ($year, $month) {
            $query->where('billing_period_year', '<', $year)
                  ->orWhere(function ($q) use ($year, $month) {
                      $q->where('billing_period_year', $year)
                        ->where('billing_period_month', '<', $month);
                  });
        })
        ->exists();

    if ($hasUnpaidBefore) {
        return (float) $customer->ticket->package->late_penalty;
    }

    return 0.0;
}
```
---
