# Backend Requirements: Daftar Tagihan Tertunggak (Teknisi)

> Dokumen kebutuhan backend untuk fitur **Daftar Tagihan** di dashboard Teknisi.
> Frontend sudah siap. Mohon dikerjakan oleh tim backend.

---

## Konteks Fitur

Teknisi perlu bisa **melihat daftar tagihan pelanggan yang belum lunas** agar saat di lapangan bisa tahu mana pelanggan yang menunggak (untuk follow-up langsung saat catat meter).

**Lokasi di Dashboard Teknisi:**
- Card "Daftar Tagihan" (menggantikan "Lapor Gangguan")
- Klik card → buka halaman `/teknisi/daftar-tagihan`

**Tampilan yang Sama dengan Admin:**
Halaman ini menggunakan komponen dan layout yang sama dengan admin di `/instalasi/daftar-tagihan`. Tujuannya konsistensi UI.

---

## Endpoint yang Dibutuhkan

### 1. List Tagihan Tertunggak

| Field | Value |
|-------|-------|
| Method | `GET` |
| URL | `/api/bills?status=unpaid` |
| Auth | `auth:sanctum`, `role:admin,teknisi` |

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "bills": [
      {
        "id": 1,
        "customer_id": 11,
        "billing_period_month": 5,
        "billing_period_year": 2026,
        "meter_reading_start": 1200,
        "meter_reading_end": 1235,
        "usage_m3": 35,
        "total_amount": 87500,
        "penalty_amount": 0,
        "due_date": "2026-06-05",
        "status": "unpaid",
        "customer": {
          "id": 11,
          "customer_code": "PAM-001",
          "user": { "name": "Irfan Hakim" },
          "ticket": { "applicant_name": "Irfan Hakim", "phone": "0812..." }
        }
      }
    ]
  }
}
```

---

## Yang Perlu Diperbaiki

### Permission/Role Update

**Endpoint sudah ada untuk Admin, hanya perlu tambah role `teknisi`:**

```php
// routes/api.php
// Sebelum (hanya admin):
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('bills', [BillingController::class, 'index']);
    // ...
});

// Sesudah (admin + teknisi untuk endpoint bills index):
Route::middleware(['auth:sanctum', 'role:admin,teknisi'])->group(function () {
    Route::get('bills', [BillingController::class, 'index']);
});

// Endpoint lainnya (generate, pay, dll) tetap admin-only
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('bills/generate', [BillingController::class, 'generate']);
    Route::post('monthly-bills/{id}/pay', [MonthlyBillController::class, 'pay']);
    // ...
});
```

> Teknisi hanya perlu **READ ONLY** (lihat daftar). TIDAK perlu akses generate, pay, atau update billing.

---

## Alur Frontend

1. Teknisi login → masuk dashboard
2. Klik card **"Daftar Tagihan"** di dashboard
3. Diarahkan ke `/teknisi/daftar-tagihan`
4. Frontend memanggil `GET /api/bills?status=unpaid`
5. Tampilkan tabel: Pelanggan/ID, Periode/Invoice, Stand Meter, Volume Air, Total Tagihan, Jatuh Tempo
6. Teknisi bisa search, klik baris untuk detail

---

## File Frontend yang Konsumsi Endpoint

- `frontend/src/services/billing.service.js` - method `getBills()` (sudah ada)
- `frontend/src/presentations/views/dashboard/teknisi/DaftarTagihan.vue` - Halaman baru
- `frontend/src/presentations/views/dashboard/teknisi/DashboardMain.vue` - Card update

---

## Testing

Setelah selesai, bisa di-test langsung dari frontend:
1. Login sebagai **teknisi**
2. Buka **Dashboard Teknisi**
3. Klik card **"Daftar Tagihan"**
4. Harus muncul daftar tagihan tertunggak

---

**Prioritas:** MEDIUM
**Estimasi:** 30 menit (hanya update permission)
