# Backend Requirements: UI Cleanup & Sinkronisasi

> Dokumen perubahan UI dan cleanup data hardcoded.
> Fitur ini sebagian besar adalah perbaikan frontend, **TIDAK ADA endpoint baru yang dibutuhkan**.

---

## Konteks

Ada beberapa perbaikan UI dan cleanup yang dilakukan di sisi frontend:

1. Sinkronisasi tampilan Pemakaian Air antara Admin dan Teknisi
2. Modal "Hasil Input" disamakan antara Admin dan Teknisi
3. Hapus notifikasi error "Sesi Anda telah berakhir" saat token expired
4. Hapus fallback dummy data di registrasi instalasi
5. Hitung trend completion rate dinamis di dashboard Surveyor

---

## Yang Tidak Membutuhkan Backend

### 1. Sinkronisasi UI Pemakaian Air (Teknisi & Admin)
- File: `teknisi/PemakaianAir.vue`, `admin/tagihan/partials/hasilInputModal.vue`
- Hanya perubahan UI (tombol, layout modal, warna)
- **Tidak ada endpoint baru**

### 2. Hapus Notifikasi Error Logout
- File: `router/index.js`
- Notifikasi merah "Sesi Anda telah berakhir" dihapus saat token expired
- User langsung diarahkan ke halaman login tanpa popup error
- Notifikasi sukses "Logout Berhasil" tetap ada (di pojok kiri atas)
- **Tidak ada endpoint baru**

### 3. Hapus Fallback Dummy Data di Registrasi
- File: `admin/instalasi/registrasi.vue`
- Sebelumnya jika `GET /users?role=teknisi` gagal, fallback ke data dummy
- Sekarang return array kosong (lebih jujur, error ditampilkan)
- **Tidak ada endpoint baru**

### 4. Trend Dinamis di Dashboard Surveyor
- File: `surveyor/DashboardMain.vue`
- Sebelumnya `completionTrend` hardcoded `+12`
- Sekarang dihitung dari completion rate dinamis
- **Tidak ada endpoint baru**

### 5. Maksimal Bayar Dinamis
- File: `teknisi/PemakaianAir.vue`
- Sebelumnya tanggal "5/06/2026" hardcoded
- Sekarang dihitung dinamis: tanggal 5 bulan berikutnya dari periode pencatatan
- **Tidak ada endpoint baru**

---

## Yang Perlu Dipastikan Backend

### Endpoint Existing yang Dipakai
Pastikan endpoint berikut tetap berfungsi dan return data dengan benar:

| Endpoint | Method | Keterangan |
|----------|--------|------------|
| `/api/users?role=teknisi` | GET | List user teknisi untuk dropdown cater |
| `/api/users?role=surveyor` | GET | List user surveyor |
| `/api/installation-tickets?status=pending` | GET | List tiket pending untuk surveyor |
| `/api/installation-tickets?status=surveyed` | GET | List tiket sudah disurvey |

### Validasi Filter Role
Pastikan filter `?role=` di endpoint `/api/users` benar-benar memfilter berdasarkan role:
- `?role=teknisi` → return user dengan role teknisi
- `?role=surveyor` → return user dengan role surveyor
- `?role=admin` → return user dengan role admin

---

## File Frontend yang Berubah

| File | Perubahan |
|------|-----------|
| `frontend/src/router/index.js` | Hapus notifikasi error saat token expired |
| `frontend/src/presentations/views/dashboard/teknisi/PemakaianAir.vue` | Sinkronisasi UI dengan admin + maksimal bayar dinamis |
| `frontend/src/presentations/views/dashboard/admin/tagihan/partials/hasilInputModal.vue` | Sinkronisasi modal hasil input |
| `frontend/src/presentations/views/dashboard/admin/instalasi/registrasi.vue` | Hapus fallback dummy data |
| `frontend/src/presentations/views/dashboard/surveyor/DashboardMain.vue` | Trend completion dihitung dinamis |

---

## Testing

Setelah deploy frontend, test:
1. **Logout & token expired** → harus tidak ada notifikasi merah di pojok kanan atas
2. **Registrasi instalasi** → dropdown teknisi harus dari API real, bukan dummy "Budi Surveyor"
3. **Dashboard surveyor** → trend percentage berubah sesuai data
4. **Tampilan teknisi/admin Pemakaian Air** → konsisten antar role

---

**Prioritas:** LOW (hanya update permission filter `?role=` jika belum berfungsi)
**Estimasi:** 0-30 menit (paling mungkin tidak ada perubahan backend)
