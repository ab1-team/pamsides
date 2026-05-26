# Catatan Backend — Personalisasi SOP

Tolong buatkan route API untuk menu **Personalisasi SOP** (`/settings/personalisasi-sop`) dengan rincian berikut:

- Base prefix: `/settings/sop`
- Middleware: `auth:sanctum` + `role:admin`
- Frontend service: `frontend/src/services/sop.service.js`

---

## 1. Load Awal Halaman

- **Route:** `GET /settings/sop`
- **Fungsi:** ambil semua data SOP saat halaman dibuka
- **Response:** `{ lembaga, pasangBaru, sistemTagihan, logo, whatsapp }`

---

## 2. Menu Profil Lembaga

- **Route:** `POST /settings/sop/lembaga`
- **Content-Type:** `application/json`
- **Field yang dikirim:**
  - `nama` (string)
  - `alamat` (string)
  - `email` (string)
  - `telepon` (string)
  - `website` (string)
  - `deskripsi` (string)

---

## 3. Menu Pasang Baru

- **Route:** `POST /settings/sop/pasang-baru`
- **Content-Type:** `application/json`
- **Field yang dikirim:**
  - `biayaPasang` (integer)
  - `statusPembayaran` (enum: `"wajib"` / `"tidak"`)
  - `enableAir` (boolean)
  - `enableSampah` (boolean)

---

## 4. Menu Sistem Tagihan

- **Route:** `POST /settings/sop/sistem-tagihan`
- **Content-Type:** `application/json`
- **Field yang dikirim:**
  - `jatuhTempo` (integer 1-31)
  - `toleransiTunggakan` (integer)

---

## 5. Menu Logo & Branding

- **Route:** `POST /settings/sop/logo`
- **Content-Type:** `multipart/form-data`
- **Field yang dikirim (file, opsional masing-masing):**
  - `mainLogo` (File image)
  - `dashboardLogo` (File image)
  - `favicon` (File image)
- **Catatan:** hanya field yang baru diupload yang dikirim. Saat response/`GET`, kirim balik berupa **URL publik** agar bisa langsung dipakai di `<img>`.

---

## 6. Menu WhatsApp API

- **Route:** `POST /settings/sop/whatsapp`
- **Content-Type:** `application/json`
- **Field yang dikirim:**
  - `templateTagihan` (string, multi-line)
  - `templatePembayaran` (string, multi-line)
- **Placeholder pesan:** `{customer}`, `{desa}`, `{kode_instalasi}`, `{jatuh_tempo}`, `{jumlah_tagihan}`, `{tagihan}`

---

## Ringkasan Route

| Method | Route | Menu |
|---|---|---|
| GET | `/settings/sop` | Load semua |
| POST | `/settings/sop/lembaga` | Profil Lembaga |
| POST | `/settings/sop/pasang-baru` | Pasang Baru |
| POST | `/settings/sop/sistem-tagihan` | Sistem Tagihan |
| POST | `/settings/sop/logo` | Logo & Branding |
| POST | `/settings/sop/whatsapp` | WhatsApp API |
