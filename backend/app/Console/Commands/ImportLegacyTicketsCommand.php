<?php

namespace App\Console\Commands;

use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\User;
use App\Models\Village;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyTicketsCommand extends Command
{
    protected $signature = 'import:tickets
                            {--dry-run : Cuma simulasi, tidak insert}
                            {--force   : Jalankan insert/update beneran}
                            {--business= : Filter business_id tertentu di DB lama (mis. 5)}
                            {--limit=0 : Batasi jumlah baris (0 = semua)}
                            {--chunk=500 : Ukuran chunk progress logging}';

    protected $description = 'Import legacy installations → installation_tickets + customers + users pelanggan';

    /**
     * Map legacy package_id (per business_id + kelas) ke installation_packages.id baru.
     * Diset saat handle() dari nama paket di DB baru.
     */
    private array $packageMap = [];

    /**
     * Map legacy user_id → new user.id (existingMax + legacy).
     * Pre-load sekali di awal.
     */
    private array $userMap = [];

    /**
     * Map legacy status (A/R/I/C/B) → enum DB baru.
     */
    private array $statusMap = [
        'A' => 'completed',
        'R' => 'pending',
        'I' => 'terminated',
        'C' => 'terminated',
        'B' => 'suspended',
    ];

    /**
     * Log progress per N baris (progress bar lambat di Windows).
     */
    private function maybeLog(int $current, int $chunkSize, int $total, array $stats): void
    {
        if ($current % $chunkSize === 0 || $current === $total) {
            $pct = round($current / max(1, $total) * 100, 1);
            $this->line(sprintf(
                '  [%5d/%5d] %5.1f%%  created=%d updated=%d skipped=%d failed=%d',
                $current, $total, $pct,
                $stats['created'], $stats['updated'], $stats['skipped'], $stats['failed']
            ));
        }
    }

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForce  = (bool) $this->option('force');
        $limit    = (int)  $this->option('limit');
        $bizFilter = $this->option('business');

        if (! $isDryRun && ! $isForce) {
            $this->error('Wajib pakai --dry-run atau --force');
            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->warn('>>> MODE DRY-RUN: tidak ada insert/update <<<');
        } else {
            $this->error('>>> MODE FORCE: data akan di-insert <<<');
        }
        if ($bizFilter) {
            $this->info("Filter: hanya business_id={$bizFilter} di DB lama.");
        }

        $this->buildPackageMap();
        $this->buildUserMap();

        // Quick stats legacy
        $totalQuery = DB::connection('legacy')->table('installations');
        if ($bizFilter) $totalQuery->where('business_id', $bizFilter);
        $totalLegacy = $totalQuery->count();
        $this->info("Total legacy installations: {$totalLegacy}");

        $rows = DB::connection('legacy')
            ->table('installations')
            ->when($bizFilter, fn ($q) => $q->where('business_id', $bizFilter))
            ->orderBy('id')
            ->when($limit > 0, fn ($q) => $q->limit($limit))
            ->get();

        $totalRows = count($rows);
        $chunkSize = max(1, (int) $this->option('chunk'));
        $this->info("Memproses {$totalRows} baris (chunk={$chunkSize})...");

        $stats = [
            'created'   => 0,
            'updated'   => 0,
            'skipped'   => 0,
            'failed'    => 0,
        ];
        $skipped = [];
        $failed  = [];

        foreach ($rows as $i => $row) {
            try {
                $reason = null;
                $mapped = $this->mapRow($row, $reason);

                if ($mapped === null) {
                    $stats['skipped']++;
                    $skipped[] = ['legacy_id' => $row->id, 'kode' => $row->kode_instalasi, 'reason' => $reason];
                    $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
                    continue;
                }

                if ($isDryRun) {
                    $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
                    continue;
                }

                // Idempotent: match by applicant + village + package + nik
                $existing = InstallationTicket::query()
                    ->where('package_id', $mapped['package_id'])
                    ->where('village_id', $mapped['village_id'])
                    ->where('applicant_name', $mapped['applicant_name'])
                    ->where('nik', $mapped['nik'])
                    ->first();

                if ($existing) {
                    $existing->update($mapped);
                    $stats['updated']++;
                } else {
                    InstallationTicket::create($mapped);
                    $stats['created']++;
                }

                $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
            } catch (\Throwable $e) {
                $stats['failed']++;
                $failed[] = ['legacy_id' => $row->id, 'kode' => $row->kode_instalasi, 'error' => $e->getMessage()];
                $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
            }
        }

        $this->line('');
        $this->info('Ringkasan:');
        $this->line(sprintf('  Dibuat       : %d', $stats['created']));
        $this->line(sprintf('  Diupdate     : %d', $stats['updated']));
        $this->line(sprintf('  Dilewati     : %d', $stats['skipped']));
        $this->line(sprintf('  Gagal        : %d', $stats['failed']));

        if (! empty($skipped)) {
            $this->line('');
            $this->warn('Dilewati (10 pertama):');
            foreach (array_slice($skipped, 0, 10) as $s) {
                $this->line("  legacy_id={$s['legacy_id']} kode={$s['kode']} reason={$s['reason']}");
            }
            if (count($skipped) > 10) {
                $this->line('  ... +'.(count($skipped) - 10).' lainnya');
            }
        }
        if (! empty($failed)) {
            $this->line('');
            $this->error('Gagal (10 pertama):');
            foreach (array_slice($failed, 0, 10) as $f) {
                $this->line("  legacy_id={$f['legacy_id']} kode={$f['kode']} error={$f['error']}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Build map legacy package_id → DB baru installation_packages.id,
     * berdasarkan nama paket "KELAS (B{business_id})" di DB baru.
     */
    private function buildPackageMap(): void
    {
        $rows = DB::connection('legacy')
            ->table('packages')
            ->get(['id', 'business_id', 'kelas']);

        $newPkgs = InstallationPackage::all(['id', 'name'])->keyBy('name');

        foreach ($rows as $legacy) {
            $wantName = "{$legacy->kelas} (B{$legacy->business_id})";
            $match = null;
            foreach ($newPkgs as $p) {
                if (strcasecmp($p->name, $wantName) === 0) {
                    $match = $p;
                    break;
                }
            }
            if ($match) {
                $this->packageMap[(int) $legacy->id] = $match->id;
            } else {
                $this->warn("Package legacy {$legacy->id} ({$wantName}) tidak ada match di DB baru");
            }
        }
    }

    /**
     * Build map legacy user_id → new user.id.
     * Pendekatan: load semua legacy users, lalu lookup di DB baru by nama + role.
     * Nama legacy diasumsikan unik untuk jabatan yang sama. Kalau dobel, fallback ke urutan.
     */
    private function buildUserMap(): void
    {
        $existingMax = User::max('id') ?? 0;

        $legacyUsers = DB::connection('legacy')->table('users')->orderBy('id')->get(['id', 'nama', 'jabatan']);

        // Cache new users by (name, role)
        $newUsers = User::all(['id', 'name', 'role']);
        $byNameRole = [];
        foreach ($newUsers as $u) {
            $key = strtolower(trim($u->name)).'|'.strtolower($u->role);
            $byNameRole[$key][] = $u->id;
        }

        foreach ($legacyUsers as $lu) {
            if (trim((string) $lu->nama) === '') {
                continue;
            }
            $role = $this->jabatanToRole((int) ($lu->jabatan ?? 0));
            $key = strtolower(trim($lu->nama)).'|'.strtolower($role);

            if (isset($byNameRole[$key]) && ! empty($byNameRole[$key])) {
                $this->userMap[(int) $lu->id] = (int) array_shift($byNameRole[$key]);
            } else {
                // fallback formula
                $this->userMap[(int) $lu->id] = $existingMax + (int) $lu->id;
            }
        }
    }

    private function jabatanToRole(int $jabatan): string
    {
        return match ($jabatan) {
            1, 2, 3, 4, 6, 8 => 'admin',
            5 => 'surveyor',
            7 => 'teknisi',
            default => 'admin',
        };
    }

    /**
     * Map satu baris legacy installations → array siap insert.
     * Return null kalau skip (alasan di $reason).
     */
    private function mapRow(object $row, ?string &$reason): ?array
    {
        // 1. Package
        if (! isset($this->packageMap[(int) $row->package_id])) {
            $reason = "package_id={$row->package_id} tidak ada mapping ke DB baru";
            return null;
        }

        // 2. Village (legacy desa = village id, ID match 1:1)
        $villageId = (int) $row->desa;
        if (! Village::where('id', $villageId)->exists()) {
            $reason = "desa={$villageId} tidak ada di villages DB baru";
            return null;
        }

        // 3. Created_by (cater_id → user.id)
        if (! isset($this->userMap[(int) $row->cater_id])) {
            $reason = "cater_id={$row->cater_id} (legacy user) tidak ada di DB baru";
            return null;
        }
        $createdBy = $this->userMap[(int) $row->cater_id];

        // 4. Status
        $status = $this->statusMap[(string) $row->status] ?? null;
        if ($status === null) {
            $reason = "status legacy='{$row->status}' tidak dikenal";
            return null;
        }

        // 5. Customer → applicant_name + NIK + gender + birth + phone
        $customer = DB::connection('legacy')
            ->table('customers')
            ->where('id', $row->customer_id)
            ->first();

        if (! $customer || trim((string) $customer->nama) === '') {
            $reason = "customer_id={$row->customer_id} missing/nama kosong";
            return null;
        }

        $applicantName = trim((string) $customer->nama);
        // NIK di DB baru varchar(20), legacy biasanya 16 digit.
        // Kalau null/kosong di legacy → NULL di new (jangan fabricate).
        $nikRaw = $customer->nik !== null ? trim((string) $customer->nik) : '';
        if ($nikRaw === '' || $nikRaw === '0') {
            $nik = null;
        } else {
            $nik = strlen($nikRaw) > 20 ? substr($nikRaw, -20) : $nikRaw;
        }

        $gender = match (strtoupper((string) $customer->jk)) {
            'L'  => 'male',
            'P'  => 'female',
            default => null,
        };

        $birthDate = $this->parseDate($customer->tgl_lahir ?? null);
        $birthPlace = ($customer->tempat_lahir !== null && trim((string) $customer->tempat_lahir) !== '')
            ? trim((string) $customer->tempat_lahir)
            : null;

        // Phone: customers.hp biasanya numeric, dibersihkan ke digit saja, fallback null
        $phone = $this->cleanPhone($customer->hp ?? null);

        // 6. Address: gabung `alamat` + RT/RW
        $address = $this->buildAddress(
            $customer->alamat ?? null,
            $row->alamat ?? null,
            $row->rt ?? null,
            $row->rw ?? null
        );

        // 7. order_date
        $orderDate = $this->parseDate($row->order ?? null);

        // 8. lat/lng: kolom DB baru NOT NULL, data legacy invalid → fallback (0,0)
        // Bisa di-update nanti via surveyor di lapangan.
        $lat = 0.0;
        $lng = 0.0;

        return [
            'package_id'     => $this->packageMap[(int) $row->package_id],
            'applicant_name' => $applicantName,
            'nik'            => $nik,
            'gender'         => $gender,
            'birth_place'    => $birthPlace,
            'birth_date'     => $birthDate,
            'phone'          => $phone,
            'address'        => $address,
            'village_id'     => $villageId,
            'lat'            => $lat,
            'lng'            => $lng,
            'status'         => $status,
            'order_date'     => $orderDate,
            'created_by'     => $createdBy,
        ];
    }

    private function parseDate(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }
        $val = trim((string) $val);
        if ($val === '' || $val === '-' || $val === '0000-00-00') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($val)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function cleanPhone(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }
        $val = trim((string) $val);
        if ($val === '' || $val === '-' || $val === '0') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $val);
        if ($digits === '' || $digits === '0') {
            return null;
        }
        if (strlen($digits) > 20) {
            $digits = substr($digits, -20);
        }
        return $digits;
    }

    private function buildAddress(mixed $customerAlamat, mixed $installationAlamat, mixed $rt = null, mixed $rw = null): string
    {
        foreach ([$customerAlamat, $installationAlamat] as $src) {
            if ($src !== null) {
                $s = trim((string) $src);
                if ($s !== '' && $s !== '-') {
                    return $s;
                }
            }
        }
        return '-';
    }
}
