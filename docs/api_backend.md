# Dokumentasi API Backend pamsides

## Base URL
```
http://localhost:8000/api
```

## Autentikasi
Semua endpoint (kecuali login) menggunakan **Bearer Token** di header:
```
Authorization: Bearer {token}
```

---

## Auth

| Method | Endpoint | Role | Keterangan |
|---|---|---|---|
| POST | `/login` | Public | Login |
| POST | `/logout` | Semua | Logout |
| POST | `/refresh` | Semua | Refresh token |
| GET | `/me` | Semua | Data user login |

**Request Login:**
```json
{
    "email": "admin@pamsides.test",
    "password": "password"
}
```
//sesuai dengan data awal di seeder, bisa login dengan email
**Response Login:**
```json
{
    "success": true,
    "data": {
        "user": {...},
        "token": "1|xxxxxxxxxxxxxxxx"
    }
}
```

---

## Paket Instalasi

| Method | Endpoint | Role | Keterangan |
|---|---|---|---|
| GET | `/installation-packages` | Admin | Daftar semua paket |
| POST | `/installation-packages` | Admin | Tambah paket baru |
| GET | `/installation-packages/{id}` | Admin | Detail paket |
| PUT | `/installation-packages/{id}` | Admin | Update paket |
| DELETE | `/installation-packages/{id}` | Admin | Hapus paket |

**Request POST/PUT:**
```json
{
    "name": "Paket Reguler",
    "installation_fee": 1500000,
    "monthly_abodemen": 15000,
    "late_penalty": 10000
}
```

---

## Blok Tarif

| Method | Endpoint | Role | Keterangan |
|---|---|---|---|
| GET | `/installation-packages/{id}/water-tariff-blocks` | Admin | Daftar blok tarif |
| POST | `/installation-packages/{id}/water-tariff-blocks` | Admin | Tambah blok tarif |
| GET | `/installation-packages/{id}/water-tariff-blocks/{blockId}` | Admin | Detail blok tarif |
| PUT | `/installation-packages/{id}/water-tariff-blocks/{blockId}` | Admin | Update blok tarif |
| DELETE | `/installation-packages/{id}/water-tariff-blocks/{blockId}` | Admin | Hapus blok tarif |

**Request POST/PUT:**
```json
{
    "usage_min_m3": 0,
    "usage_max_m3": 10,
    "price_per_m3": 1500
}
```

> ⚠️ `usage_max_m3` boleh `null` untuk blok terakhir (tidak terbatas).
> Validasi: tidak boleh overlap antar blok dalam satu paket.

---

## Tiket Instalasi

| Method | Endpoint | Role | Keterangan |
|---|---|---|---|
| GET | `/installation-tickets` | Admin | Daftar tiket |
| POST | `/installation-tickets` | Admin | Buat tiket baru |
| GET | `/installation-tickets/{id}` | Admin | Detail tiket |
| PATCH | `/installation-tickets/{id}/transition` | Admin | Ubah status tiket |
| POST | `/installation-tickets/{id}/survey` | Surveyor | Input hasil survey + foto |
| POST | `/installation-tickets/{id}/payment` | Admin | Konfirmasi pembayaran instalasi |
| POST | `/installation-tickets/{id}/installation-result` | Teknisi | Input hasil instalasi + foto meter |
| POST | `/installation-tickets/{id}/activate` | Admin | Aktivasi pelanggan |

**Query Parameters GET `/installation-tickets`:**
```
?status=pending|surveyed|unpaid|processing|completed
?search=nama_atau_nik
```

**Alur Status Tiket:**
```
pending → surveyed → unpaid → processing → completed
```

**Request POST `/installation-tickets`:**
```json
{
    "package_id": 1,
    "applicant_name": "Budi Santoso",
    "nik": "3300000000000001",
    "address": "Jl. Magelang No. 10, Yogyakarta",
    "lat": -7.797068,
    "lng": 110.370529
}
```

**Request PATCH `/installation-tickets/{id}/transition`:**
```json
{
    "status": "surveyed"
}
```

**Request POST `/installation-tickets/{id}/survey` (form-data):**
```
distance_to_pipe_m : 15
material_notes     : Butuh pipa 15 meter
photo              : [file gambar]
```

**Request POST `/installation-tickets/{id}/payment`:**
```json
{
    "amount": 1500000
}
```

**Request POST `/installation-tickets/{id}/installation-result` (form-data):**
```
initial_meter_reading : 0
photo                 : [file gambar]
```

---

## Pencatatan Meter

| Method | Endpoint | Role | Keterangan |
|---|---|---|---|
| GET | `/meter-readings/pending` | Teknisi | Daftar pelanggan belum dicatat bulan ini |
| POST | `/meter-readings` | Teknisi | Input angka meter bulanan + foto |

**Response GET `/meter-readings/pending`:**
```json
{
    "message": "Daftar pelanggan yang belum dicatat meter bulan ini",
    "data": [...]
}
```

**Request POST `/meter-readings` (form-data):**
```
customer_id : 1
meter_value : 25
photo       : [file gambar]
```

**Response POST `/meter-readings`:**
```json
{
    "message": "Meter berhasil dicatat",
    "data": {...}
}
```

> Validasi: angka meter baru tidak boleh lebih kecil dari bulan sebelumnya.

---

## Tagihan

| Method | Endpoint | Role | Keterangan |
|---|---|---|---|
| GET | `/bills/recap` | Admin | Rekap tagihan |
| POST | `/bills/generate` | Admin | Generate tagihan bulanan (dengan parameter) |
| GET | `/bills/{id}` | Admin | Detail tagihan |
| GET | `/monthly-bills` | Admin | Daftar tagihan |
| POST | `/monthly-bills/generate` | Admin | Generate tagihan bulanan (otomatis bulan ini) |
| POST | `/monthly-bills/{id}/pay` | Admin | Konfirmasi pembayaran tagihan |
| GET | `/reports/bills` | Admin | Laporan tagihan per periode |

**Query Parameters GET `/bills/recap`:**
```
?year=2026
?month=3
?status=unpaid|paid
```

**Request POST `/bills/generate`:**
```json
{
    "year": 2026,
    "month": 3
}
```

**Query Parameters GET `/monthly-bills`:**
```
?customer_id=1
?status=unpaid|paid
?month=3
?year=2026
```

**Query Parameters GET `/reports/bills` (wajib):**
```
?month=3
?year=2026
```

**Response GET `/reports/bills`:**
```json
{
    "success": true,
    "data": {
        "summary": {
            "total_tagihan": 77500,
            "total_dibayar": 0,
            "total_belum_dibayar": 77500
        },
        "bills": [...]
    }
}
```

**Response POST `/monthly-bills/{id}/pay`:**
```json
{
    "success": true,
    "message": "Pembayaran berhasil dikonfirmasi"
}
```

---

## Dashboard & Laporan

| Method | Endpoint | Role | Keterangan |
|---|---|---|---|
| GET | `/dashboard/statistics` | Admin | Statistik dashboard |
| GET | `/reports/billing` | Admin | Laporan tagihan per periode |
| GET | `/reports/installation` | Admin | Laporan instalasi per periode |
| GET | `/reports/installations` | Admin | Laporan instalasi (alternatif) |
| GET | `/reports/billing/export-csv` | Admin | Export tagihan ke CSV |
| GET | `/reports/billing/export-pdf` | Admin | Export tagihan ke PDF |
| GET | `/reports/installation/export-csv` | Admin | Export instalasi ke CSV |
| GET | `/reports/installation/export-pdf` | Admin | Export instalasi ke PDF |

**Query Parameters `/reports/billing`:**
```
?year=2026
?month=3
?status=unpaid|paid
```

**Query Parameters `/reports/installation`:**
```
?status=pending|surveyed|unpaid|processing|completed
?start_date=2026-01-01
?end_date=2026-03-31
```

---

## Format Response

Semua endpoint menggunakan format response yang sama:

**Berhasil:**
```json
{
    "success": true,
    "data": {...}
}
```

**Berhasil dengan pesan:**
```json
{
    "success": true,
    "message": "Pesan sukses",
    "data": {...}
}
```

**Gagal Validasi (422):**
```json
{
    "message": "...",
    "errors": {
        "field": ["pesan error"]
    }
}
```

**Tidak Terautentikasi (401):**
```json
{
    "message": "Unauthenticated."
}
```

**Akses Ditolak (403):**
```json
{
    "message": "Akses ditolak."
}
```

---

## Role & Akses

| Role | Akses |
|---|---|
| `admin` | Semua endpoint admin |
| `surveyor` | Input hasil survey |
| `teknisi` | Pencatatan meter, input hasil instalasi |
| `pelanggan` | (belum ada endpoint khusus) |

---

## Data Awal (Seeder)

| Email | Password | Role |
|---|---|---|
| `admin@pamsides.test` | `password` | Admin |
| `surveyor@pamsides.test` | `password` | Surveyor |
| `teknisi@pamsides.test` | `password` | Teknisi |
