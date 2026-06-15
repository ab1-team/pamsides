# AGENTS.md - Project Agent Guide

Panduan ringkas untuk AI agent (Claude, opencode, dll) yang membantu pengembangan **Pamsides v2**.

---

## Tech Stack

| Layer | Stack |
|---|---|
| Frontend | Vue 3 (Composition API) + Vite + Pinia + Vue Router + TailwindCSS |
| Backend | Laravel (REST API) + Laravel Sanctum |
| Database | MySQL |
| Testing FE | Vitest + @vue/test-utils + jsdom |
| Testing BE | Pest |

---

## Perintah Penting

### Frontend (`/frontend`)
```bash
pnpm install          # Install deps
pnpm dev              # Dev server (port 5173)
pnpm build            # Production build
pnpm test             # Run unit tests (Vitest)
pnpm test:watch       # Watch mode
pnpm test:coverage    # With coverage report
pnpm lint             # Run all linters
pnpm format           # Prettier
```

### Backend (`/backend`)
```bash
composer install      # Install deps
php artisan migrate --seed
php artisan serve     # Dev server (port 8000)
composer test         # Run Pest tests
```

---

## Konvensi Kode

### Naming
- **Vue components**: PascalCase (`MeterReading.vue`, `BaseButton.vue`)
- **Composables**: camelCase dengan prefix `use` (`useCurrencyFormat.js`)
- **Services**: camelCase dengan suffix `Service` (`packageService.js`)
- **Pinia stores**: camelCase dengan suffix `Store` (`uiStore.js`)

### File Structure
```
frontend/src/
├── composables/      # Pure functions & Vue composables
├── services/         # API service classes (axios wrappers)
├── stores/           # Pinia stores
├── utils/            # Generic utilities
├── views/app/        # Route pages (organized by role)
├── components/       # Reusable components
├── router/           # Vue Router config
├── types/            # Type definitions / constants
└── utils/            # Helpers
```

### Test Placement
Test file **sejajar** dengan source file:
```
src/composables/useFormatCurrency.js
src/composables/useFormatCurrency.test.js
```

### Critical Rules
- **JANGAN tambah komentar** pada kode kecuali user minta
- **JANGAN commit** kecuali user minta eksplisit
- **Gunakan Conventional Commits** (`feat:`, `fix:`, `docs:`, `chore:`, `refactor:`)
- **Backend route `auth` middleware** untuk endpoint protected (lihat `routes/api.php`)
- **Frontend gunakan `@/` alias** untuk import (otomatis ke `src/`)

---

## Backend Patterns

### Controller Validation
```php
$request->validate([
    'name' => 'required|string|max:255',
    'amount' => 'required|numeric|min:0',
], [
    'name.required' => 'Nama wajib diisi.',
]);
```

### Service Pattern
Logic berat dipisah ke `app/Services/`. Contoh: `MonthlyBillService::generate()` untuk kalkulasi tagihan progresif.

### Safe Delete Helper
Gunakan `safeDelete()` dari base `Controller`:
```php
return $this->safeDelete(
    fn () => $model->delete(),
    'ENTITY_IN_USE',           // error code
    'Nama Entity',              // entity label
    $model->name,               // usage context
    null,
    fn () => response()->json(['success' => true, 'data' => ['message' => 'Dihapus.']])
);
```

### Foreign Key Cascade
```php
$table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
```

---

## Frontend Patterns

### Axios Interceptor
`utils/axios.js` sudah include:
- Auto Bearer token dari localStorage
- Auto refresh token pada 401 dengan queue system
- Redirect ke `/login?logout=expired` jika refresh gagal

### Role-Based Routing
Setiap route WAHIB punya `meta.roles`:
```js
{
  path: 'transaksi/tagihan-bulanan',
  component: tagihanBulanan,
  meta: { roles: ['admin'] },  // ⚠️ WAJIB
}
```

### Service Pattern
```js
import api from '@/utils/axios'

export const packageService = {
  async getPackages() {
    const response = await api.get('/installation-packages')
    return response.data
  },
  // ...
}
export default packageService
```

### Delete Handler
Gunakan `confirmDelete` helper untuk konsistensi UX:
```js
import { confirmDelete } from '@/utils/deleteHandler'

await confirmDelete({
  title: 'Hapus Data?',
  text: 'Data akan dihapus permanent',
  successMessage: 'Berhasil dihapus',
  entity: 'data',
  errorCode: 'ENTITY_IN_USE',
  onConfirm: async () => {
    await service.delete(id)
    await fetchData()
  },
})
```

---

## Testing Frontend

### Menulis Test
- Letakkan file `.test.js` di sebelah source `.js`
- Test pure functions: currency, date, validation, helpers
- Test composables: dengan `@vue/test-utils` + `mount` atau langsung panggil function
- Test Vue components: skip dulu kecuali high-value (low ROI karena styling berat)

### Conventions
```js
import { describe, it, expect } from 'vitest'
import { myFunction } from './myModule'

describe('myFunction', () => {
  it('handles basic case', () => {
    expect(myFunction(123)).toBe('result')
  })
})
```

### Intl Note (jsdom)
`Intl.NumberFormat` di jsdom kadang pakai **regular space** (` `), bukan NBSP (`\u00A0`). Untuk test yang sensitif, gunakan regex toleran:
```js
expect(result).toMatch(/^Rp[\s\u00A0]1\.500\.000$/)
```

### Jalankan Test
```bash
pnpm test             # CI mode
pnpm test:watch       # Dev mode
pnpm test:coverage    # HTML + text report di coverage/
```

---

## Git Workflow

1. **Buat branch dari `develop`**: `git checkout -b feature/nama-fitur`
2. **Conventional commits**: `feat:`, `fix:`, `docs:`, dll
3. **Husky pre-commit** jalan: oxlint, eslint, prettier
4. **Push + buat PR** ke `develop`
5. **Backend CI**: jalan `composer test` (Pest)
6. **Frontend CI**: jalan `pnpm test` (Vitest)

---

## Database Schema Highlights

Lihat `docs/database.md` untuk detail lengkap. Tabel utama:
- `users` (role: admin/surveyor/teknisi/pelanggan)
- `installation_tickets` (status: pending/surveyed/unpaid/processing/completed/terminated/suspended)
- `customers` (setelah aktivasi, dapat `customer_code`)
- `installation_packages` + `water_tariff_blocks` (tarif progresif)
- `meter_readings` (input teknisi bulanan)
- `monthly_bills` + `bill_payments` (tagihan)
- `trouble_reports` (lapor gangguan)

---

## Catatan Penting

- **Folder `riwayatHapus/`** di `src/presentations/` = legacy/deprecated, exclude dari test & build
- **Backend `use UiStore` Pinia** di axios interceptor = circular dependency potential, hati-hati saat refactor
- **Default port**: backend `8000`, frontend `5173`, MySQL `3306`
- **Path gambar meteran**: `storage/meter-readings/` (public disk)
