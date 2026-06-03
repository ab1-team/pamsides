# Backend Requirements: Hasil Survey (Admin CRUD)

> Dokumen kebutuhan backend untuk fitur **Hasil Survey** di dashboard Admin.
> Frontend sudah siap. Mohon dikerjakan oleh tim backend.

---

## Konteks Fitur

Admin perlu fitur untuk **melihat, edit, dan hapus** hasil survey yang sudah diinput oleh Surveyor lapangan. Tujuannya untuk koreksi data jika ada kesalahan input.

**Alur:**
1. Surveyor input hasil survey via mobile
2. Admin lihat daftar hasil survey di menu Master Instalasi → Hasil Survey
3. Admin klik baris untuk lihat detail (foto, jarak pipa, catatan, surveyor)
4. Admin bisa edit jarak pipa, catatan, dan ganti foto
5. Admin bisa hapus hasil survey (status ticket kembali ke `pending` agar bisa di-survey ulang)

> Catatan: Approve/Reject TIDAK perlu. Ubah status ticket dilakukan di menu Status Instalasi yang sudah ada.

---

## Endpoint yang Dibutuhkan

### 1. Update Hasil Survey

| Field | Value |
|-------|-------|
| Method | `POST` (dengan `_method=PUT` untuk multipart upload) atau `PUT` |
| URL | `/api/survey-results/{id}` |
| Auth | `auth:sanctum`, `role:admin` |
| Content-Type | `multipart/form-data` |

**Request Body:**
```
distance_to_pipe_m  : integer  (required, min: 0)
material_notes      : string   (required)
photo               : image    (optional, mimes: jpg/jpeg/png, max: 2048KB)
_method             : "PUT"    (jika via POST)
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Survey berhasil diupdate.",
  "data": {
    "id": 1,
    "ticket_id": 11,
    "surveyor_id": 3,
    "distance_to_pipe_m": 15,
    "material_notes": "Lokasi gang sempit",
    "photo_url": "http://...",
    "surveyed_at": "2026-05-28 03:26:54",
    "surveyor": { "id": 3, "name": "Budi Surveyor" },
    "ticket": { "id": 11, "applicant_name": "...", "nik": "...", "address": "..." }
  }
}
```

**Logic Required:**
- Jika ada file `photo` baru → hapus foto lama dari storage, upload yang baru
- Jika tidak ada `photo` baru → biarkan foto lama tetap ada
- Update `distance_to_pipe_m` dan `material_notes`

---

### 2. Hapus Hasil Survey

| Field | Value |
|-------|-------|
| Method | `DELETE` |
| URL | `/api/survey-results/{id}` |
| Auth | `auth:sanctum`, `role:admin` |

**Response Success (200):**
```json
{
  "success": true,
  "message": "Survey berhasil dihapus."
}
```

**Logic Required:**
- Hapus foto dari storage (`storage/app/public/survey-photos/...`)
- Update `installation_tickets.status` → `pending` (agar bisa di-survey ulang oleh surveyor)
- Hapus record `survey_results`

---

### 3. List Hasil Survey (sudah ada, perlu eager load)

| Field | Value |
|-------|-------|
| Method | `GET` |
| URL | `/api/installation-tickets?status=surveyed` |
| Auth | `auth:sanctum`, `role:admin` |

**Yang Perlu Dipastikan:**
- Eager load relasi `survey.surveyor` (untuk menampilkan nama surveyor di tabel)
- Response data tetap format yang sudah ada

**Update di Controller:**
```php
$query = InstallationTicket::with([
    'package.tariffBlocks',
    'package',
    'survey.surveyor',  // <-- pastikan relasi ini di-load
    'user',
    'village'
])->orderBy('created_at', 'desc');
```

---

## Update Model Required

`App\Models\SurveyResult.php` perlu menambahkan relasi `surveyor()`:

```php
public function surveyor()
{
    return $this->belongsTo(User::class, 'surveyor_id');
}
```

---

## Route Definition (Contoh)

Tambahkan di grup admin pada `routes/api.php`:

```php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // ... route admin lainnya
    
    // Survey CRUD (Admin)
    Route::put('survey-results/{id}', [SurveyResultController::class, 'update']);
    Route::post('survey-results/{id}', [SurveyResultController::class, 'update']); // untuk multipart
    Route::delete('survey-results/{id}', [SurveyResultController::class, 'destroy']);
});
```

---

## Storage

Pastikan symlink storage sudah dibuat di server:
```bash
php artisan storage:link
```

Foto survey harus bisa diakses public via:
- `http://[backend-domain]/storage/survey-photos/[filename].jpg`

---

## Testing

Setelah selesai, bisa di-test langsung dari frontend:
1. Login sebagai admin
2. Buka menu **Master Instalasi → Hasil Survey**
3. Klik baris untuk lihat detail
4. Klik tombol **Edit** untuk update data
5. Klik tombol **Hapus** untuk delete

---

## File Frontend yang Konsumsi Endpoint

- `frontend/src/services/ticket.service.js` - method `updateSurvey()`, `deleteSurvey()`
- `frontend/src/presentations/views/dashboard/admin/instalasi/hasilSurvey.vue` - List & action
- `frontend/src/presentations/views/dashboard/admin/instalasi/partials/EditSurveyModal.vue` - Form edit
- `frontend/src/presentations/views/dashboard/admin/instalasi/partials/DetailSurveyModal.vue` - Modal detail

---

**Prioritas:** HIGH
**Estimasi:** 2-3 jam
