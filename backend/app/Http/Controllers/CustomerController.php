<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallationTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Resolve {id} dari form Ubah/Hapus.
     *
     * ID yang dikirim frontend bisa berupa dua bentuk:
     *   - angka (installation_tickets.id) — untuk pelanggan yang belum punya
     *     record `customers` (mis. baru disimpan lewat form Tambah), dan
     *   - customer_code (mis. "005.0001.100.1") — untuk pelanggan aktif, sebab
     *     mapRow() di usePelanggan.js memakai `customer_code || id`.
     *
     * Dulu show() hanya findOrFail() tiket berdasarkan angka, sehingga tombol
     * Ubah untuk SETIAP pelanggan aktif melompat ke 404: kolom ID di UI
     * menampilkan kode pelanggan, tapi route menerimanya sebagai id tiket.
     */
    private function findTicketByIdentifier($id): ?InstallationTicket
    {
        $ticket = InstallationTicket::with(['user', 'customer'])
            ->find($id);

        if ($ticket) {
            return $ticket;
        }

        // Bukan id tiket: bisa jadi customer_code.
        return InstallationTicket::with(['user', 'customer'])
            ->whereHas('customer', fn ($q) => $q->where('customer_code', (string) $id))
            ->first();
    }

    /**
     * Daftar pelanggan untuk halaman /app/data-pelanggan.
     *
     * Paginasi dilakukan di SERVER (satu halaman per request). usePelanggan.js
     * dulunya menarik seluruh halaman lalu memfilter di browser; sekarang ia
     * hanya meminta halaman yang sedang ditampilkan.
     *
     * Catatan performa — `installation_tickets` punya kolom `address` TEXT,
     * `birth_place`, lat/lng, dan lain-lain yang tidak pernah dirender tabel,
     * jadi kolom select dibatasi. `user` juga tidak di-eager-load karena
     * mapper di bawah membaca nama dari `applicant_name`, bukan dari users —
     * tanpa pembatasan itu tiap halaman menambah satu query sia-sia.
     *
     * store() membuat tiket berstatus `draft`, dan tombol "Tidak, Cek Data" di
     * form Tambah langsung mengarahkan user ke halaman ini. Karena itu draft
     * tidak disaring: draft = pelanggan yang baru didaftarkan lewat halaman
     * ini, jadi memang harus ikut tampil.
     */
    public function index(Request $request)
    {
        // Batasi ukuran halaman supaya satu request tidak bisa menarik seluruh
        // tabel. Frontend|max 100 lewat dropdown "Tampilkan ... data".
        $perPage = max(1, min((int) $request->get('per_page', 10), 100));

        $query = InstallationTicket::query()
            ->select([
                'id',
                'applicant_name',
                'nik',
                'phone',
                'address',
                'status',
                'created_at',
            ])
            // customer_code hanya ada di tabel `customers` (dibuat saat
            // aktivasi, belum ada untuk pelanggan draft). Eager load
            // `customer` = satu query hasMany tambahan per halaman untuk satu
            // kolom; subquery skalar di sini digabung ke query utama.
            ->addSelect([
                'customer_code' => Customer::query()
                    ->select('customer_code')
                    ->whereColumn('customers.ticket_id', 'installation_tickets.id')
                    ->orderBy('customers.id')
                    ->limit(1),
            ]);

        $search = trim((string) $request->get('search', ''));
        $status = trim((string) $request->get('status', ''));

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            // Panjang dibatasi supaya satu request tidak bisa memaksa MySQL
            // membandingkan seluruh kolom teks dengan pola yang sangat panjang.
            $search = mb_substr($search, 0, 100);
            $like = "%{$search}%";

            $query->where(function ($sub) use ($like) {
                $sub->where('applicant_name', 'like', $like)
                    ->orWhere('nik', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('address', 'like', $like)
                    // Kolom "ID" di UI menampilkan customer_code, jadi kode
                    // pelanggan ikut bisa dicari — sebelumnya tidak, sehingga
                    // mengetik kode selalu mengembalikan "Pelanggan Tidak
                    // Ditemukan" walau datanya ada.
                    ->orWhereIn(
                        'id',
                        Customer::query()->select('ticket_id')->where('customer_code', 'like', $like)
                    );
            });
        }

        // `id` jadi tie-breaker: created_at berpresisi detik, jadi dua baris
        // yang dibuat pada detik sama bisa berganti urutan antar-request dan
        // menyebabkan baris yang sama muncul di dua halaman.
        $tickets = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $items = $tickets->getCollection()->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->applicant_name,
            'nik' => $t->nik,
            'no_telp' => $t->phone ?? '-',
            'address' => $t->address ?? '-',
            'status' => $t->status,
            'customer_code' => $t->customer_code,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $items,
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function search(Request $request)
    {
        $q = $request->get('q', $request->search, '');

        $hasBills = filter_var($request->get('with_bills', false), FILTER_VALIDATE_BOOLEAN);

        $query = InstallationTicket::with([
            'customer',
            'user',
            'package',
            'package.waterTariffBlocks',
            'village',
        ])->whereIn('status', ['completed', 'suspended', 'terminated']);

        if ($hasBills) {
            $query->whereHas('customer.monthlyBills');
        }

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('applicant_name', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%");
            });
        }

        $tickets = $query->limit(20)->get();

        $items = $tickets->map(function ($t) {
            $customer = $t->customer->first();
            $customerId = $customer->id ?? null;
            $package = $t->package;
            $tariffBlocks = $package?->waterTariffBlocks?->map(fn ($b) => [
                'id' => $b->id,
                'usage_min_m3' => (int) $b->usage_min_m3,
                'usage_max_m3' => $b->usage_max_m3 !== null ? (int) $b->usage_max_m3 : null,
                'price_per_m3' => (float) $b->price_per_m3,
                'min' => (float) $b->usage_min_m3,
                'max' => $b->usage_max_m3 !== null ? (float) $b->usage_max_m3 : null,
                'price' => (float) $b->price_per_m3,
            ])->values() ?? [];

            return [
                'id' => $customerId,
                'customer_id' => $customerId,
                'ticket_id' => $t->id,
                'customer_code' => $customer->customer_code ?? null,
                'installationCode' => $customer->customer_code ?? null,
                'name' => $t->applicant_name,
                'nik' => $t->nik,
                'phone' => $t->phone ?? '-',
                'address' => $t->address ?? '-',
                'village' => $t->village?->village_name ?? '-',
                'hamlet' => $t->village?->hamlet_name ?? '-',
                'rt' => $t->village?->rt ?? null,
                'rw' => $t->village?->rw ?? null,
                'cater' => $t->user?->name ?? '-',
                'package_id' => $package?->id ?? null,
                'packageName' => $package?->name ?? 'Paket Standar',
                'installation_fee' => $package ? (float) $package->installation_fee : 0,
                'abodemen' => $package ? (float) $package->monthly_abodemen : 0,
                'penalty' => $package ? (float) $package->late_penalty : 0,
                'tariffBlocks' => $tariffBlocks,
                'status' => 'Aktif',
            ];
        })->filter(fn ($i) => ! empty($i['id']))->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Normalisasi nilai "kosong" dari form.
     *
     * Kedua form (PelangganCreate.vue & PelangganEdit.vue) mengganti kolom kosong
     * dengan '0' atau '-' sebelum mengirim, sesuai catatan di bawah form. Nilai
     * placeholder itu TIDAK boleh dianggap data asli: kolom tanggal harus jadi
     * NULL (bukan 1970-01-01 hasil strtotime('-')), dan gender kosong tidak boleh
     * diam-diam dianggap laki-laki.
     */
    private function blankToNull($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return ($value === '' || $value === '-' || $value === '0') ? null : $value;
    }

    /**
     * Terjemahkan label Bahasa Indonesia dari form ke enum DB (male/female).
     * Nilai tidak dikenal (termasuk placeholder '-') menghasilkan null.
     */
    private function normalizeGender($value): ?string
    {
        return match ($this->blankToNull($value)) {
            'Perempuan' => 'female',
            'Laki-laki' => 'male',
            default => null,
        };
    }

    /**
     * Tanggal lahir dari date-picker. Nilai placeholder / tidak valid -> null.
     */
    private function normalizeBirthDate($value): ?string
    {
        $raw = $this->blankToNull($value);

        if ($raw === null) {
            return null;
        }

        try {
            $date = Carbon::parse($raw);
        } catch (\Exception $e) {
            return null;
        }

        // Hanya terima tahun yang masuk akal (>= 1900). Tanpa ini, nilai
        // placeholder '-' dari date-picker akan tersimpan sebagai 1970-01-01.
        return ($date->year >= 1900 && $date->year <= (int) date('Y')) ? $date->format('Y-m-d') : null;
    }

    public function store(Request $request)
    {
        $request->validate([
            'nik' => ['required', 'string', 'max:20', Rule::unique('installation_tickets', 'nik')],
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'alamat_lengkap' => 'required',
            'package_id' => 'nullable|exists:installation_packages,id',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
        ], [
            'nik.required' => 'NIK wajib diisi',
            'nik.unique' => 'NIK sudah digunakan oleh pelanggan lain',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 6 karakter',
            'alamat_lengkap.required' => 'Alamat lengkap wajib diisi',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->nama_lengkap,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'pelanggan',
                ]);

                $ticket = InstallationTicket::create([
                    'package_id' => $request->package_id ?? 1,
                    'user_id' => $user->id,
                    'applicant_name' => $request->nama_lengkap,
                    'nik' => $request->nik,
                    'address' => $request->alamat_lengkap,
                    'phone' => $this->blankToNull($request->no_telp) ?? '0',
                    'gender' => $this->normalizeGender($request->jenis_kelamin),
                    'birth_place' => $this->blankToNull($request->tempat_lahir) ?? '-',
                    'birth_date' => $this->normalizeBirthDate($request->tgl_lahir),
                    'lat' => 0,
                    'lng' => 0,
                    'status' => 'draft',
                    'created_by' => auth()->id() ?? 1,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Data pelanggan berhasil disimpan',
                    'data' => $ticket,
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // {id} bisa customer_code (lihat findTicketByIdentifier).
        $ticket = $this->findTicketByIdentifier($id);

        if (! $ticket) {
            abort(404, 'Data pelanggan tidak ditemukan');
        }

        $request->validate([
            'nik' => [
                'required', 'string', 'max:20',
                Rule::unique('installation_tickets', 'nik')->ignore($ticket->id),
            ],
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$ticket->user_id,
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
        ], [
            'nik.unique' => 'NIK sudah digunakan oleh pelanggan lain',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid',
        ]);

        DB::transaction(function () use ($request, $ticket) {
            $ticket->user->update([
                'name' => $request->nama_lengkap,
                'email' => $request->email,
                'password' => $request->password ? Hash::make($request->password) : $ticket->user->password,
            ]);

            $ticket->update([
                'applicant_name' => $request->nama_lengkap,
                'nik' => $request->nik,
                'address' => $request->alamat_lengkap,
                'phone' => $this->blankToNull($request->no_telp) ?? '0',
                'gender' => $this->normalizeGender($request->jenis_kelamin),
                'birth_place' => $this->blankToNull($request->tempat_lahir) ?? '-',
                'birth_date' => $this->normalizeBirthDate($request->tgl_lahir),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui',
        ]);
    }

    public function destroy($id)
    {
        $ticket = $this->findTicketByIdentifier($id);

        if (! $ticket) {
            abort(404, 'Data pelanggan tidak ditemukan');
        }

        return $this->safeDelete(
            fn () => DB::transaction(function () use ($ticket) {
                if ($ticket->user) {
                    $ticket->user->delete();
                }
                $ticket->delete();
            }),
            'TICKET_IN_USE',
            'Data tiket',
            $ticket->applicant_name,
            'Data berhasil dihapus',
        );
    }

    public function show($id)
    {
        $ticket = $this->findTicketByIdentifier($id);

        if (! $ticket) {
            abort(404, 'Data pelanggan tidak ditemukan');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $ticket->id,
                'name' => $ticket->applicant_name,
                'email' => optional($ticket->user)->email,
                'nik' => $ticket->nik,
                'phone' => $ticket->phone ?? '-',
                'address' => $ticket->address ?? '-',
                'gender' => $ticket->gender ?? 'male',
                'birth_place' => $ticket->birth_place ?? '-',
                'birth_date' => $ticket->birth_date,
                'status' => $ticket->status,
                'customer_code' => optional($ticket->customer->first())->customer_code,
            ],
        ]);
    }
}
