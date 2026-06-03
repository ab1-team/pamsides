# PAMSIDES v2 - Dokumen Kebutuhan Backend (Consolidated)

Dokumen ini merekap seluruh endpoint, middleware, perbaikan logika, dan database schema yang dibutuhkan oleh frontend untuk diselesaikan oleh tim Backend.

---

## 1. Fitur CRUD Hasil Survey (Admin & Surveyor)

Frontend memerlukan API CRUD lengkap untuk hasil survey agar admin dapat mengubah atau menghapus data jika surveyor melakukan kesalahan input.

### A. Endpoint & Request Detail
*   **Update Hasil Survey**
    *   **Method / URL**: `POST /api/survey-results/{id}` (dengan body parameter `_method: PUT` untuk upload file via multipart/form-data)
    *   **Headers**: `Content-Type: multipart/form-data`
    *   **Request Body**:
        ```json
        {
          "distance_to_pipe_m": "float (required)",
          "material_notes": "text (required)",
          "photo": "file (image, optional, max 2MB)",
          "_method": "PUT"
        }
        ```
*   **Hapus Hasil Survey**
    *   **Method / URL**: `DELETE /api/survey-results/{id}`
    *   **Fungsi**: Menghapus data survey dan mengembalikan status tiket instalasi terkait kembali ke `pending` (atau status awal sebelum disurvey).

### B. Eager Loading Relasi Tiket
Pada endpoint `GET /api/installation-tickets?status=surveyed`, pastikan relasi `survey.surveyor` ikut di-load agar nama surveyor dapat langsung ditampilkan di list hasil survey admin:
```php
// Pastikan relasi diload di Controller:
$tickets = InstallationTicket::with(['survey.surveyor', 'village', 'package'])->where('status', 'surveyed')->get();
```

---

## 2. Fitur Daftar Tagihan & Pemasukan (Teknisi & Admin)

### A. Hak Akses Tagihan untuk Teknisi
*   **Endpoint**: `GET /api/bills?status=unpaid`
*   **Kebutuhan**: Berikan akses kepada `role: teknisi` untuk dapat memanggil endpoint ini agar mereka dapat melihat daftar tagihan tertunggak langsung dari dashboard handphone mereka.

### B. Konfirmasi Pembayaran Tagihan
*   **Method / URL**: `POST /api/monthly-bills/{id}/pay`
*   **Request Body**:
    ```json
    {
      "payment_method": "cash | transfer (required)",
      "amount_paid": "numeric (required)"
    }
    ```
*   **Logika Backend**:
    *   Ubah status tagihan menjadi `paid`.
    *   Catat ke tabel riwayat pemasukan desa (`village_revenues` / `income_logs`).

---

## 3. Fitur Pencatatan Meter & Tagihan Bulanan (Cater & Teknisi)

### A. Hak Akses Catat Meter untuk Teknisi
*   **Endpoint**: 
    *   `GET /api/meter-readings/pending` (List pelanggan belum dicatat bulan ini)
    *   `POST /api/meter-readings` (Input stand meter baru)
*   **Kebutuhan**: Tambahkan `role: teknisi` ke dalam middleware/kebijakan hak akses kedua endpoint ini.

### B. Logika Tanggal Jatuh Tempo (`due_date`)
Setiap kali tagihan bulanan dibuat (`MonthlyBill`), set `due_date` secara otomatis ke **tanggal 20 bulan berikutnya**.
*   *Contoh*: Jika pemakaian dicatat pada 28 Mei 2026 (Periode Mei 2026), maka `due_date` adalah **20 Juni 2026**.

### C. Logika Denda Akumulatif & Tarif Progresif
*   **Pengecekan Denda**: Saat menghitung tagihan, cek apakah pelanggan memiliki tagihan bulan-bulan sebelumnya yang berstatus `unpaid` dan sudah melewati jatuh tempo. Jika ada, denda bulanan ditambahkan ke total tagihan.
*   **Kalkulasi Blok Tarif**: Pastikan kalkulasi volume air menggunakan block tarif (progresif) sesuai paket layanan (`water_tariff_blocks`).

---

## 4. Fitur Laporan Gangguan (Pelanggan)

Pelanggan dapat mengirimkan laporan kendala air langsung dari aplikasi.

*   **Method / URL**: `POST /api/pelanggan/trouble-report`
*   **Headers**: `Content-Type: multipart/form-data`
*   **Request Body**:
    ```json
    {
      "trouble_type": "string (required, e.g. air_mati, pipa_bocor, meter_rusak, dll)",
      "description": "text (required)",
      "contact_phone": "string (required)",
      "photo": "file (image/video, optional, max 5MB)"
    }
    ```
*   **Database Schema (Tabel `trouble_reports` / `complaints`)**:
    *   `id` (PK)
    *   `customer_id` (FK to customers, diambil dari auth user)
    *   `trouble_type` (varchar)
    *   `description` (text)
    *   `contact_phone` (varchar)
    *   `photo_path` (varchar, nullable)
    *   `status` (enum: pending, processing, resolved. Default: pending)

---

## 5. Fitur Hasil Instalasi & Aktivasi Pelanggan (Teknisi & Admin)

Setelah pembayaran biaya pasang baru, Teknisi memasang meteran air di lokasi pelanggan lalu mengupload hasilnya.

### A. Simpan Hasil Instalasi (Teknisi)
*   **Method / URL**: `POST /api/installation-tickets/{id}/installation-result`
*   **Headers**: `Content-Type: multipart/form-data`
*   **Request Body**:
    ```json
    {
      "meter_number": "string (required)",
      "initial_meter_value": "integer (required, default: 0)",
      "notes": "string (optional)",
      "installation_photo": "file (image, required, max 2MB)"
    }
    ```
*   **Logika Backend**:
    *   Simpan nomor seri meteran, stand awal, foto instalasi, dan catatan teknisi ke dalam database.
    *   Ubah status tiket instalasi menjadi `installed` (siap diaktivasi).

### B. Aktivasi Layanan (Admin)
*   **Method / URL**: `POST /api/installation-tickets/{id}/activate`
*   **Fungsi**: Admin melakukan verifikasi hasil pemasangan teknisi dan mengaktifkan akun pelanggan (membuat record user pelanggan aktif dan men-generate `customer_code` resmi).
*   **Status Tiket**: Berubah menjadi `active` / `completed`.
