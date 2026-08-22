# Catatan Backend - Status Installation Ticket

**Tanggal:** 2026-05-26  
**Dari:** Frontend Team  
**Untuk:** Backend Team

---

## 1. State Machine - Tambah Transisi Status

**File:** `app/StateMachines/TicketStateMachine.php`

```php
private static array $transitions = [
    'draft'      => ['pending'],
    'pending'    => ['surveyed'],
    'surveyed'   => ['unpaid'],
    'unpaid'     => ['processing'],
    'processing' => ['completed'],
    'completed'  => ['suspended', 'terminated'],
    'suspended'  => ['completed', 'terminated'],
    'terminated' => [],
];
```

**Catatan:** `draft → pending`, `completed → suspended/terminated`, dan `suspended → completed/terminated` belum ada di state machine saat ini.

---

## 2. Eager Load Relasi di Endpoint Tickets

**File:** `app/Http/Controllers/InstallationTicketController.php`

Method `index` dan `show` perlu load relasi tambahan:

```php
->with([
    'package.tariffBlocks',
    'package',
    'survey',
    'user',
    'village',
    'customer.meterReadings', // ← BARU (untuk Informasi Meter di Detail Aktif)
    'payments',               // ← BARU (untuk Status Pembayaran)
])
```

---

## 3. Response API yang Dibutuhkan Frontend

### `GET /installation-tickets`

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "applicant_name": "...",
        "nik": "...",
        "phone": "...",
        "address": "...",
        "lat": -7.46,
        "lng": 110.25,
        "status": "completed",
        "order_date": "2026-05-26",
        "created_at": "...",
        "updated_at": "...",

        "package": {
          "id": 1,
          "name": "Paket A",
          "installation_fee": 1500000,
          "monthly_abodemen": 15000,
          "late_penalty": 10000
        },

        "village": {
          "id": 1,
          "village_name": "...",
          "hamlet_name": "..."
        },

        "customer": [
          {
            "id": 1,
            "customer_code": "INS-0001",
            "initial_meter_reading": 0,
            "activated_at": "2026-05-26",
            "meter_readings": [
              {
                "meter_value": 1345.5,
                "recorded_at": "2026-05-26",
                "reading_year": 2026,
                "reading_month": 5
              }
            ]
          }
        ]
      }
    ]
  }
}
```

**Field penting yang dipakai frontend:**

| Path | Dipakai untuk |
|------|---------------|
| `status` | Mapping kategori filter (Permohonan/Pasang Baru/Aktif/Blokir/Cabut) |
| `package.installation_fee` | Nominal Pasang Baru, otomatis saat konfirmasi pembayaran |
| `package.monthly_abodemen` | Tampilan Abodemen di registrasi |
| `package.late_penalty` | Tampilan Denda di registrasi |
| `customer[0].customer_code` | ID display di tabel & detail |
| `customer[0].initial_meter_reading` | Meter Awal di Detail Aktif |
| `customer[0].activated_at` | Tgl Pasang di Detail Aktif |
| `customer[0].meter_readings[]` | Pemakaian Terakhir & Total Pemakaian |
| `village.village_name` | Wilayah/Region di detail |

---

## 4. Endpoint yang Dipakai Frontend

| Endpoint | Method | Fungsi |
|----------|--------|--------|
| `/installation-tickets` | GET | List tiket (sudah ada) |
| `/installation-tickets/{id}` | GET | Detail tiket (sudah ada) |
| `/installation-tickets/{id}/transition` | PATCH | Ubah status (sudah ada) |
| `/installation-tickets/{id}/register` | PUT | Registrasi instalasi (sudah ada) |
| `/installation-tickets/{id}/payment` | POST | Konfirmasi pembayaran (sudah ada) |
| `/customers/{id}` | DELETE | Hapus pelanggan dari status Cabut |
| `/villages` | GET/POST/PUT/DELETE | CRUD desa (sudah ada) |

**Body untuk `transition`:**
```json
{ "status": "surveyed" }
```

**Body untuk `payment`:**
```json
{ "amount": 1500000 }
```

---

## 5. Format Error Response

Frontend membaca error dari path berikut, urut prioritas:
1. `response.data.message`
2. `response.data.errors.{field}[0]`

Contoh:
```json
{
  "success": false,
  "message": "Transisi status tidak diizinkan.",
  "errors": {
    "status": ["Transisi dari 'completed' ke 'pending' tidak diizinkan."]
  }
}
```

---

## 6. Checklist

- [ ] Update `TicketStateMachine.php` (transisi baru)
- [ ] Eager load `customer.meterReadings` di `index` & `show`
- [ ] Pastikan response sesuai struktur di atas
- [ ] Test semua transisi status (lihat flow di poin 1)
