<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixTicketTechnicianCommand extends Command
{
    protected $signature = 'legacy:fix-ticket-technician
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perubahan}
                            {--from-created-by=1 : Gunakan created_by sebagai acuan teknisi}';

    protected $description = 'Perbaiki installation_tickets.user_id agar menunjuk teknisi (bukan user pelanggan), mengikuti legacy installations.cater_id';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }

        $this->info('=== Perbaiki installation_tickets.user_id (teknisi) ===');
        $this->warn($dry ? 'MODE DRY-RUN.' : 'MODE FORCE.');
        $this->line('');

        $N = DB::connection();
        $L = DB::connection('legacy');

        // Role yang boleh jadi teknisi
        $staffIds = $N->table('users')->whereIn('role', ['teknisi', 'admin', 'surveyor'])->pluck('id')->flip()->all();
        $this->info('User yang bisa jadi teknisi: ' . count($staffIds));
        $this->line('');

        // Legacy: installations.cater_id → user legacy (harus staff)
        $legacyStaffNames = [];
        foreach ($L->table('users')->get(['id', 'nama', 'jabatan', 'business_id']) as $lu) {
            $k = strtolower(trim((string) $lu->nama));
            if ($k !== '') {
                $legacyStaffNames[$k] = $lu;
            }
        }

        $tickets = $N->table('installation_tickets')->get(['id', 'user_id', 'created_by', 'applicant_name', 'village_id']);
        $this->line('Total tiket: ' . count($tickets));

        $fixToCreatedBy = [];
        $fixToLegacyCater = [];
        $wrongUserId = [];
        $alreadyOk = 0;

        foreach ($tickets as $t) {
            $curIsStaff = isset($staffIds[(int) $t->user_id]);
            if ($curIsStaff) {
                $alreadyOk++;

                continue;
            }

            // user_id saat ini = user pelanggan → salah
            $wrongUserId[] = $t->id;

            // Opsi 1: pakai created_by (sudah staff, dari mapping legacy user_id)
            if (isset($staffIds[(int) $t->created_by])) {
                $fixToCreatedBy[] = [
                    'id' => (int) $t->id,
                    'name' => (string) $t->applicant_name,
                    'from' => (int) $t->user_id,
                    'to' => (int) $t->created_by,
                ];

                continue;
            }

            // Opsi 2: cari dari legacy cater via kode instalasi
            $cust = $N->table('customers')->where('ticket_id', $t->id)->first();
            if (! $cust) {
                continue;
            }
            $code = trim((string) $cust->customer_code);
            $li = $L->table('installations')->where('kode_instalasi', $code)->first();
            if (! $li) {
                continue;
            }
            $lu = $legacyStaffNames[strtolower(trim((string) ($L->table('users')->where('id', $li->cater_id)->value('nama') ?? '')))] ?? null;
            $newUser = null;
            if ($lu) {
                $newUser = $N->table('users')->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim((string) $lu->nama))])
                    ->whereIn('role', ['teknisi', 'admin', 'surveyor'])->value('id');
            }
            if ($newUser) {
                $fixToLegacyCater[] = [
                    'id' => (int) $t->id,
                    'name' => (string) $t->applicant_name,
                    'from' => (int) $t->user_id,
                    'to' => (int) $newUser,
                ];
            }
        }

        $this->line('');
        $this->info('--- Analisis ---');
        $this->line('  user_id sudah staff (benar) : ' . $alreadyOk);
        $this->line('  user_id salah (pelanggan)  : ' . count($wrongUserId));
        $this->line('  bisa diperbaiki dgn created_by : ' . count($fixToCreatedBy));
        $this->line('  bisa diperbaiki dgn legacy cater : ' . count($fixToLegacyCater));
        $this->line('');

        if ($fixToCreatedBy) {
            $this->info('--- Contoh perbaikan (created_by) ---');
            $toCount = [];
            foreach ($fixToCreatedBy as $f) {
                $toCount[$f['to']] = ($toCount[$f['to']] ?? 0) + 1;
            }
            arsort($toCount);
            foreach (array_slice($toCount, 0, 12, true) as $toId => $cnt) {
                $u = $N->table('users')->where('id', $toId)->first();
                printf("   -> teknisi id=%-5s %-14s untuk %d tiket\n", $toId, $u->name ?? '?', $cnt);
            }
        }

        if ($dry) {
            $this->line('');
            $this->warn('DRY-RUN. Jalankan --force untuk menerapkan.');

            return self::SUCCESS;
        }

        $applied = 0;
        foreach (array_merge($fixToCreatedBy, $fixToLegacyCater) as $f) {
            $N->table('installation_tickets')->where('id', $f['id'])
                ->update(['user_id' => $f['to'], 'updated_at' => now()]);
            $applied++;
        }

        $this->line('');
        $this->info('Selesai. Tiket diperbaiki: ' . $applied);
        $this->line('');
        $this->info('Distribusi user_id setelah perbaikan:');
        foreach ($N->table('installation_tickets')->join('users', 'users.id', '=', 'installation_tickets.user_id')
            ->select('users.role', DB::raw('count(*) c'))->groupBy('users.role')->get() as $d) {
            printf("   role=%-10s %6d\n", $d->role, $d->c);
        }

        return self::SUCCESS;
    }
}