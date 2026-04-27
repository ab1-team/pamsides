# Detailed Frontend Roadmap: Login to Logout (PAMSIDES-V2)

Dokumen ini merinci implementasi teknis alur pengguna dari autentikasi hingga keluar sistem, serta panduan persiapan lingkungan pengembangan.

---

## 0. Prasyarat & Persiapan (Setup Guide)
Sebelum memulai pengembangan, pastikan perangkat Anda telah terinstal software berikut:

### Perangkat Lunak Utama:
1.  **Node.js**: Versi `^20.19.0` atau `>=22.12.0` (Gunakan LTS direkomendasikan).
2.  **Package Manager**: `pnpm` (direkomendasikan) atau `npm`.
3.  **Code Editor**: VS Code dengan ekstensi:
    - **Volar** (Vue Language Features)
    - **ESLint** & **Prettier**
    - **Tailwind CSS IntelliSense**

### Langkah Instalasi:
1.  **Clone Repository**:
    ```bash
    git clone [url-repository]
    cd pamsides-v2/frontend
    ```
2.  **Instal Dependensi**:
    ```bash
    pnpm install
    ```
3.  **Konfigurasi Environment**:
    Salin file `.env.example` menjadi `.env` dan sesuaikan `VITE_API_BASE_URL` ke backend Anda.
4.  **Menjalankan Server Lokal**:
    ```bash
    pnpm dev
    ```

---

## 0.1 Tech Stack & Ekosistem
Frontend ini dibangun menggunakan teknologi modern untuk memastikan performa tinggi dan kemudahan pengembangan:

### Core Framework & Build Tools:
- **Vue.js (v3.5+)**: Framework utama menggunakan Composition API.
- **Vite (v7.3+)**: Build tool super cepat untuk pengembangan frontend modern.
- **Vue Router (v5.0+)**: Untuk manajemen navigasi SPA.
- **Pinia (v3.0+)**: State management terpusat (pengganti Vuex).

### Styling & UI Components:
- **Tailwind CSS (v4.2+)**: Utility-first CSS framework (v4) untuk desain yang cepat dan responsif.
- **PrimeVue (v4.5+)**: Library komponen UI premium (DataTables, Forms, Modals).
- **FontAwesome (v7.2+)**: Koleksi ikon SVG yang lengkap.

### Libraries & Utilities:
- **Axios**: Untuk komunikasi data dengan Backend API.
- **Leaflet (v1.9+)**: Untuk fitur pemetaan (koordinat lokasi pelanggan).
- **SweetAlert2**: Untuk tampilan dialog konfirmasi dan notifikasi cantik.
- **jsTree**: Digunakan untuk menampilkan struktur data hirarkis (seperti COA).
- **jQuery**: Digunakan sebagai pendukung library tertentu (seperti jsTree).

---

## 0.2 Pola Aliran Data (Composables & DataTables)
Aplikasi ini menggunakan pola *Separation of Concerns* (pemisahan logika dan tampilan) menggunakan Vue Composables.

### 1. Composables sebagai Logic Controller
**File Path**: `frontend/src/composables/use[NamaFitur].js` (Contoh: `usePelanggan.js`)
Setiap modul utama memiliki Composable yang mengelola:
- **State**: `searchQuery`, `currentPage`, `perPage`.
- **Data**: Menyimpan hasil fetch dari API ke dalam `ref` (misal: `tableData`).
- **Computed Logic**: Menangani pemfilteran data di sisi client (`filteredData`) dan pagination.
- **Actions**: Fungsi untuk manipulasi data seperti `handleEdit()` dan `handleDelete()`.

### 2. Pengambilan Data (Data Fetching)
- **Sumber Data**: Saat ini menggunakan data statis/dummy di dalam composable untuk pengembangan UI.
- **Integrasi API**: Ke depannya, data akan diambil dari Backend menggunakan **Axios** di dalam fungsi `onMounted` atau `fetchData`.
- **Services**: Logika pemanggilan API murni dipisahkan ke dalam folder `src/services/` untuk kemudahan maintenance.

### 3. Penyajian Data (DataTable)
Data yang dikelola oleh Composable kemudian di-*bind* ke komponen UI di folder `src/presentations/views/`:
- **PrimeVue DataTable**: Digunakan untuk tabel yang kompleks dengan fitur built-in (sorting, filtering).
- **Custom Table**: Digunakan untuk tampilan yang lebih spesifik atau ringan menggunakan Tailwind CSS.
- **Reaktivitas**: Karena menggunakan `ref` dan `computed` dari Vue 3, tabel akan otomatis terupdate saat user mengetik di kolom pencarian tanpa perlu reload halaman.

---

## 1. Authentication Layer (State Management)
**File Path**: `frontend/src/stores/authStore.js`

Menggunakan Pinia untuk mengelola status login secara global.

### Key Functions:
- **`login(credentials)`**: 
  - Melakukan POST request ke `/api/login`.
  - Menyimpan `token` dan `user` ke state dan `localStorage`.
  - Mengupdate `axios.defaults.headers.common['Authorization']`.
- **`logout()`**: 
  - Menghapus `token` dan `user` dari state dan `localStorage`.
  - Menghapus header Authorization di Axios.
  - Reset state UI lainnya jika diperlukan.
- **`initAuth()`**: 
  - Dipanggil saat aplikasi pertama kali dimuat (`main.js`).
  - Mengecek keberadaan token di `localStorage`.
  - Jika ada, validasi token ke backend atau set state `isAuthenticated = true`.
- **`fetchCurrentUser()`**: 
  - Mengambil data profil terbaru dari server untuk sinkronisasi state.

---

## 2. Routing & Authorization
**File Path**: `frontend/src/router/index.js`

Mengatur aksesibilitas halaman berdasarkan status login dan peran pengguna.

### Logic & Guards:
- **`beforeEach` Navigation Guard**:
  - `to.meta.requiresAuth`: Jika rute butuh login dan user belum login, arahkan ke `/login`.
- **Role-Based Landing (`/dashboard`)**:
  Di kode Anda, rute `/dashboard` menggunakan logika dinamis untuk menentukan komponen mana yang dimuat:
  ```javascript
  // src/router/index.js
  switch (uiStore.userRole) {
    case 'surveyor': return SurveyorDashboard
    case 'teknisi': return TeknisiDashboard
    case 'pelanggan': return PelangganDashboard
    default: return AdminDashbord
  }
  ```

---

## 3. Login Flow
**File Path**: `frontend/src/presentations/views/auth/LoginView.vue`

### Key Methods:
- **`handleLogin()`**: 
  - Trigger loading state.
  - Panggil `authStore.login()`.
  - Jika sukses, tampilkan Toast sukses dan redirect menggunakan `router.push()`.
  - Jika gagal, tampilkan pesan error dari API (misal: "Email atau Password salah").
- **`validateForm()`**: 
  - Memastikan input email valid dan password tidak kosong sebelum submit.

---

## 4. Main Layout & Navigation
**File Path**: `frontend/src/presentations/layouts/dashboard/MainView.vue` & `SidebarView.vue`

### MainView.vue Functions:
- **`handleLogout()`**: 
  - Menampilkan SweetAlert2 konfirmasi.
  - Jika "Ya", panggil `authStore.logout()` dan redirect ke `/login`.
- **`toggleSidebar()`**: 
  - Mengelola state `sidebarOpen` untuk tampilan responsif.

### SidebarView.vue Logic:
- **`filteredMenuItems()`**: 
  - Computed property yang memfilter daftar menu berdasarkan `authStore.user.role`.
  - Contoh: Menampilkan menu "Input Meter" hanya untuk role `teknisi`.

---

## 5. Multi-User Journey Breakdown

Berikut adalah detail alur kerja dan fungsi yang dipisahkan berdasarkan peran pengguna (Role):

### A. ROLE: ADMIN
Admin memiliki akses penuh untuk manajemen sistem dan data master.
- **Landing**: `/dashboard` (AdminDashboard component).
- **Alur Utama**:
  1.  **Manajemen Master**: Mengelola `/data-pelanggan`, `/data-desa`, dan `/kelas-biaya`.
  2.  **Monitoring Instalasi**: Memantau `/dataInstalasi` dan status sambungan baru di `/instalasi/status`.
  3.  **Finance & Billing**: Mengelola `/transaksi/tagihan-bulanan` dan `/transaksi/jurnal-umum`.
  4.  **Reporting**: Mengakses rekap data di `/Pelaporan`.
- **Fitur Kunci**: Validasi data, Generate tagihan massal, Export laporan.

### B. ROLE: SURVEYOR
Surveyor fokus pada verifikasi lapangan untuk pengajuan sambungan baru.
- **Landing**: `/dashboard` (SurveyorDashboard component).
- **Alur Utama**:
  1.  **Cek Penugasan**: Melihat daftar permohonan yang perlu disurvey di Dashboard.
  2.  **Input Lapangan**: Masuk ke rute `/survey/input` untuk mengisi koordinat, jarak pipa, dan kebutuhan material.
  3.  **Bukti Foto**: Mengupload foto lokasi melalui form survey.
- **Fitur Kunci**: Integrasi Peta (Koordinat), Upload Gambar, Form Checklist Material.

### C. ROLE: TEKNISI
Teknisi bertanggung jawab atas operasional teknis seperti pencatatan meteran.
- **Landing**: `/dashboard` (TeknisiDashboard component).
- **Alur Utama**:
  1.  **Pencatatan Meter**: Mengakses `/teknisi/pencatatan-meter` untuk input angka stand meter bulanan pelanggan.
  2.  **Input Pemakaian**: Menggunakan `/instalasi/teknisiPemakaianAir` untuk pencatatan teknis lainnya.
  3.  **Update Status**: Mengubah status pengerjaan instalasi jika ditugaskan.
- **Fitur Kunci**: Input Angka Cepat, Validasi Angka Meter (tidak boleh lebih kecil dari bulan lalu), Upload Foto Meteran.

### D. ROLE: PELANGGAN
Pelanggan menggunakan sistem untuk memantau tagihan dan profil mereka.
- **Landing**: `/dashboard` (PelangganDashboard component).
- **Alur Utama**:
  1.  **Cek Tagihan**: Melihat rincian biaya di `/pelanggan/tagihan-detail`.
  2.  **Update Profil**: Mengelola data diri dan keamanan akun di `/profil`.
  3.  **Riwayat**: Melihat riwayat pemakaian air dari bulan ke bulan.
- **Fitur Kunci**: Tampilan Grafik Pemakaian, Download Invoice/Struk, Notifikasi Tagihan Baru.

---

## 6. Access Control Matrix (Hak Akses Fitur)

Berikut adalah rincian hak akses teknis untuk setiap role pada modul-modul utama:

| Modul / Fitur | Admin | Surveyor | Teknisi | Pelanggan |
| :--- | :---: | :---: | :---: | :---: |
| **Manajemen User/Role** | CRUD | - | - | - |
| **Data Master (Desa/Kelas)** | CRUD | Read | Read | - |
| **Data Pelanggan** | CRUD | Read | Read | Read (Self) |
| **Permohonan Sambungan** | CRUD | Read | Read | Create (Self) |
| **Input Hasil Survey** | CRUD | Create/Edit | - | - |
| **Input Angka Meter** | CRUD | - | Create/Edit | - |
| **Generate Tagihan** | Create | - | - | - |
| **Konfirmasi Pembayaran** | Create | - | - | - |
| **Lihat Tagihan/Invoice** | Read All | - | - | Read (Self) |
| **Laporan & Statistik** | Read All | - | - | - |
| **Update Profil & Password** | CRUD | Update Self | Update Self | Update Self |

**Keterangan:**
- **CRUD**: Create, Read, Update, Delete (Akses Penuh).
- **Read (Self)**: Hanya bisa melihat data milik sendiri.
- **-**: Tidak memiliki akses (Route dilindungi oleh Role Guard).

---

## 7. Security & Session Handling
- **Axios Interceptors**: 
  - **Request**: Otomatis menyisipkan Bearer Token di setiap request.
  - **Response**: Menangani error `401 Unauthorized`. Jika token expired, otomatis panggil `logout()` dan arahkan ke login.
- **Auto-Logout**: Timer sederhana untuk logout otomatis jika tidak ada aktivitas dalam waktu tertentu (optional).

---

## 7. Logout Implementation Details
Proses keluar yang bersih meliputi:
1.  **Backend Call**: (Optional) Memberitahu server untuk blacklist token.
2.  **Clear Local Storage**: `localStorage.clear()` atau hapus key spesifik.
3.  **Reset Pinia State**: Menghindari data user lama muncul saat user baru login di komputer yang sama.
4.  **Redirect**: Kembali ke root `/` atau `/login`.
