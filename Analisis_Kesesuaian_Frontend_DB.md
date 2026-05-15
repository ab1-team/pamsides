# 📊 Analisis Kesesuaian Frontend vs Database (ACC Atasan)

Dokumen ini dibuat untuk mencocokkan fungsionalitas aplikasi frontend dengan **struktur asli database backend** yang saat ini statusnya **sudah disetujui (di-ACC) oleh atasan**. 

Berdasarkan investigasi mendalam ke seluruh file Database Migration Anda, berikut adalah hasil audit kesesuaian fitur dan saran langkah penyesuaian frontend:

---

## ✅ KELOMPOK 1: Fitur yang 100% Selaras dengan Database
*Fitur-fitur ini sudah didukung penuh oleh tabel-tabel yang ada di database asli Anda saat ini. Tidak memerlukan perubahan database.*

| Modul Frontend | Tabel Database Pendukung | Keterangan Status |
| :--- | :--- | :--- |
| **Dashboard & Ringkasan** | `monthly_bills`, `installation_tickets` | ✅ **Lengkap**. Data perhitungan sudah sesuai. |
| **Data Pelanggan** | `customers`, `users` | ✅ **Lengkap**. Terelasi dengan detail tiket pasang baru. |
| **Progress Instalasi** | `installation_tickets` | ✅ **Lengkap**. Mendukung tahapan: *draft, pending, surveyed, unpaid, processing, completed, suspended, terminated*. |
| **Pemakaian Air (Meteran)** | `meter_readings`, `monthly_bills` | ✅ **Lengkap**. Kolom meter awal, meter akhir, dan volume pemakaian sudah sesuai tipe data numerik di database. |
| **Paket & Tarif Layanan** | `installation_packages`, `water_tariff_blocks` | ✅ **Lengkap**. Mendukung klasifikasi blok tarif (progresif). |

---

## ⚠️ KELOMPOK 2: Fitur yang Perlu Penyesuaian Konseptual
*Fitur ini ada di frontend, tetapi cara penyimpanannya di database menggunakan metode berbeda dari yang divisualisasikan di demo.*

### 🕵️‍♂️ 1. Modul Petugas Cater (Pembaca Meter)
*   **Kondisi Database (ACC):** Di tabel `meter_readings`, pencatat meter dihubungkan ke kolom `recorded_by` yang mengacu langsung ke tabel `users`. Selain itu, enum role di tabel `users` hanya terdiri dari: `admin`, `surveyor`, `teknisi`, dan `pelanggan`.
*   **Kesimpulan:** Database **TIDAK** menggunakan tabel khusus petugas cater. Siapa pun user dengan role teknisi/surveyor bertindak sebagai pencatat meter.
*   **Saran Penyesuaian Frontend:** 
    *   Halaman "Data Cater" disesuaikan fungsinya menjadi halaman **"Manajemen Tim Lapangan"** yang menampilkan data dari tabel `users` dengan filter `role='surveyor'` atau `role='teknisi'`.

### 📍 2. Modul Wilayah / Data Desa
*   **Kondisi Database (ACC):** Tabel `installation_tickets` menyimpan alamat dalam satu kolom Text panjang (`address`) dan koordinat (`lat`, `lng`). Database **TIDAK** memisahkan entitas desa/dusun dalam tabel master CRUD relasional khusus. Adapun referensi nama kecamatan/desa disimpan secara statis di dalam tabel `settings` (key-value) murni untuk keperluan dropdown pendaftaran saja.
*   **Kesimpulan:** Tidak diperlukan menu CRUD mandiri untuk "Data Desa" (tambah, edit, hapus desa) karena alamat pelanggan bersifat teks bebas.
*   **Saran Penyesuaian Frontend:**
    *   Menu "Data Desa" di Sidebar sebaiknya **disembunyikan (hidden)** agar tidak membingungkan user, dikarenakan pengisian desa sudah terakomodasi secara otomatis di form pendaftaran pemasangan baru.

---

## ❌ KELOMPOK 3: Fitur yang TIDAK ADA di Database (Surplus)
*Fitur ini murni visual di frontend dan sama sekali tidak direncanakan masuk ke dalam skema database yang di-ACC.*

### 🗑️ 1. Modul Retribusi Sampah
*   **Kondisi Database (ACC):** Tidak ada tabel, model, maupun relasi terkait biaya sampah. Aplikasi difokuskan 100% pada tata kelola air bersih desa (Pamsimas).
*   **Saran Penyesuaian Frontend:**
    *   Menu "Retribusi Sampah" di Sidebar sebaiknya **disembunyikan (hidden)** untuk menjaga kesederhanaan aplikasi produksi sesuai dengan ruang lingkup database yang disetujui.

---

## 🛠️ Rekomendasi Tindakan Berikutnya di Frontend
Agar frontend kita **100% disiplin** mengikuti database yang sudah disetujui pimpinan:
1.  **Nonaktifkan/Hide Menu yang Berlebih**: Sembunyikan navigasi untuk "Retribusi Sampah" dan "Data Desa" di Sidebar menu admin agar aplikasi bersih.
2.  **Alihkan data Cater**: Menghubungkan list petugas ke endpoint pemanggilan user dengan filter role (Surveyor/Teknisi).

*Catatan ini dapat Anda tunjukkan langsung ke atasan atau tim developer Anda untuk memvalidasi keselarasan alur kerja sistem.*
