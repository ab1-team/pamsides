# 📝 Rekap Singkat Integrasi Backend

### 1. Endpoint: `GET /api/installation-tickets`
*   **Apa**: `with('customer')` & `$request->get('per_page')`.
*   **Gimana**: Biar frontend bisa nampilin **Kode Pelanggan resmi** dan **rekap data total tiket** di arsip dashboard.

### 2. Endpoint: `GET /api/monthly-bills`
*   **Apa**: `with(['customer.ticket.package', 'customer.user'])`.
*   **Gimana**: Biar frontend bisa nampilin **Nama Pelanggan, Alamat, dan Jenis Tarif** di tabel riwayat meteran air bulanan.



