<?php

namespace App\Console\Commands;

use App\Services\LegacyTicketAligner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLegacyTicketRtRwCommand extends Command
{
    protected $signature = 'legacy:sync-ticket-rtrw
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perubahan}
                            {--business=5 : business_id legacy yang jadi acuan}
                            {--min-accuracy=99 : batalkan kalau akurasi alignment di bawah ini}';

    protected $description = 'Selaraskan installation_tickets.rt / .rw dengan legacy installations.rt / .rw (termasuk nilai NULL)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $biz = (int) $this->option('business');
        $minAcc = (float) $this->option('min-accuracy');

        $this->info('=== Selaraskan rt/rw tiket dengan legacy (business_id='.$biz.') ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: rt/rw akan diubah.');
        $this->line('');

        $L = DB::connection('legacy');
        $N = DB::connection();

        // ---------------------------------------------------------------
        // ANCHOR UTAMA: legacy_installation_id pada tiket.
        //
        // Legacy desa (biz5 = 67/68) TIDAK sejajar dengan village_id baru
        // (61..75), jadi pengelompokan (desa|package) milik aligner lama tidak
        // pernah ketemu. `legacy_installation_id` adalah kunci identitas resmi
        // hasil migrasi, jadi tiket bisa langsung mengambil rt/rw/direction.
        // ---------------------------------------------------------------
        $legacyById = [];
        foreach ($L->table('installations AS i')
            ->leftJoin('customers AS c', 'c.id', '=', 'i.customer_id')
            ->where('i.business_id', $biz)
            ->get(['i.id', 'i.kode_instalasi', 'i.rt', 'i.rw', 'c.nama AS customer_nama']) as $i) {
            $legacyById[(int) $i->id] = $i;
        }
        $this->info(sprintf('Instalasi legacy (business_id=%d): %d', $biz, count($legacyById)));

        $tickets = $N->table('installation_tickets')->get(['id', 'legacy_installation_id', 'rt', 'rw'])->keyBy('id');

        $updates = [];
        $same = 0;
        $totalTickets = $tickets->count();
        $anchorValid = 0;       // tiket dgn legacy_installation_id yang ketemu di legacy
        $noLegacyId = 0;        // tiket tanpa legacy_installation_id -> jalur cadangan
        $unknownLegacyId = 0;   // legacy_installation_id ada tapi tak ada di legacy bisnis ini

        foreach ($tickets as $t) {
            $lid = $t->legacy_installation_id;
            if ($lid === null || (int) $lid <= 0) {
                $noLegacyId++;

                continue;
            }
            $row = $legacyById[(int) $lid] ?? null;
            if ($row === null) {
                $unknownLegacyId++;

                continue;
            }
            $anchorValid++;

            self::planUpdate($updates, $same, (int) $t->id, (string) $row->kode_instalasi, $t->rt, $t->rw, $row->rt, $row->rw);
        }

        $this->info(sprintf(
            'Anchor legacy-id valid: %d/%d | tanpa legacy-id: %d | legacy-id tak dikenal: %d',
            $anchorValid, $totalTickets, $noLegacyId, $unknownLegacyId
        ));

        // ---------------------------------------------------------------
        // JALUR CADANGAN (aligner lama desa|package) HANYA untuk tiket yang
        // legacy_installation_id-nya NULL. Perilaku lama dipertahankan.
        // ---------------------------------------------------------------
        $fallbackPairs = 0;
        if ($noLegacyId > 0) {
            $align = LegacyTicketAligner::build($biz);
            $map = $align['map'];
            $this->info(sprintf(
                'Cadangan aligner (desa|package): %d pasang | akurasi %.2f%% (anchor %d/%d)',
                count($map), $align['acc'], $align['anchors'], $align['total']
            ));

            // Jalur cadangan bersifat BEST-EFFORT: kalau akurasi di bawah batas,
            // cukup peringatkan dan LEWATI pembaruan dari cadangan — proses tetap
            // lanjut memakai hasil anchor utama. Jangan pernah membatalkan proses.
            if ($align['acc'] < $minAcc) {
                $this->warn(sprintf(
                    'Akurasi cadangan %.2f%% < batas %.2f%%: perbarui dari cadangan DILEWATI (anchor utama tetap diterapkan).',
                    $align['acc'], $minAcc
                ));
            } else {
                // Pemetaan legacy-installation.id => ticket.id dari aligner lama.
                $alreadyUsed = [];
                foreach ($tickets as $t) {
                    if ($t->legacy_installation_id !== null && (int) $t->legacy_installation_id > 0) {
                        $alreadyUsed[(int) $t->id] = true;
                    }
                }

                foreach ($legacyById as $legacyId => $legacyRow) {
                    $ticketId = $map[$legacyId] ?? null;
                    if ($ticketId === null || ! isset($tickets[$ticketId]) || isset($alreadyUsed[$ticketId])) {
                        continue;
                    }
                    $t = $tickets[$ticketId];
                    // Hanya untuk tiket yang memang belum punya legacy-id.
                    if ($t->legacy_installation_id !== null && (int) $t->legacy_installation_id > 0) {
                        continue;
                    }
                    $fallbackPairs++;
                    self::planUpdate($updates, $same, (int) $t->id, (string) $legacyRow->kode_instalasi, $t->rt, $t->rw, $legacyRow->rt, $legacyRow->rw);
                    $alreadyUsed[(int) $t->id] = true;
                }
            }
        } else {
            $this->line('Tidak ada tiket tanpa legacy-id: jalur cadangan aligner tidak dipakai.');
        }
        $this->line('');

        $rtFill = count(array_filter($updates, fn ($u) => array_key_exists('rt', $u['set'])));
        $rwFill = count(array_filter($updates, fn ($u) => array_key_exists('rw', $u['set'])));
        $toNull = 0;
        foreach ($updates as $u) {
            foreach ($u['set'] as $k => $v) {
                if ($v === null) {
                    $toNull++;
                }
            }
        }

        $this->line(sprintf('Total tiket              : %d', $totalTickets));
        $this->line(sprintf('Anchor legacy-id valid   : %d', $anchorValid));
        $this->line(sprintf('Tiket tanpa legacy-id    : %d', $noLegacyId));
        $this->line(sprintf('Cadangan (desa|package)  : %d', $fallbackPairs));
        $this->line(sprintf('Sudah sama               : %d', $same));
        $this->line(sprintf('Baris berubah            : %d', count($updates)));
        $this->line(sprintf('  terisi rt              : %d', $rtFill));
        $this->line(sprintf('  terisi rw              : %d', $rwFill));
        $this->line(sprintf('Menjadi NULL             : %d', $toNull));
        if ($unknownLegacyId) {
            $this->line(sprintf('Legacy-id tak dikenal    : %d', $unknownLegacyId));
        }
        $this->line('');

        $this->info('Contoh perubahan:');
        foreach (array_slice($updates, 0, 12) as $u) {
            $parts = [];
            foreach ($u['set'] as $k => $v) {
                $parts[] = $k.'='.json_encode($v);
            }
            $this->line(sprintf(
                '   ticket=%-5d kode=%-18s arah=%-6s %s',
                $u['ticket_id'], $u['kode'], $u['direction'], implode(' ', $parts)
            ));
        }
        if (count($updates) > 12) {
            $this->line('   ... +'.(count($updates) - 12).' lainnya');
        }

        if ($dry) {
            $this->line('');
            $this->warn('DRY-RUN selesai. Jalankan dengan --force untuk menerapkan.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->error('Menerapkan...');
        $done = 0;
        $N->transaction(function () use ($N, $updates, &$done) {
            foreach (array_chunk($updates, 300) as $chunk) {
                foreach ($chunk as $u) {
                    $N->table('installation_tickets')->where('id', $u['ticket_id'])
                        ->update($u['set'] + ['updated_at' => now()]);
                    $done++;
                }
            }
        });

        $this->info('Selesai. Tiket di-update: '.$done);

        return self::SUCCESS;
    }

    /**
     * Hitung perubahan rt/rw satu tiket dan tambahkan ke rencana bila berbeda.
     *
     * @param  array<int,array{ticket_id:int,kode:string,direction:string,set:array<string,?string>}>  $updates
     */
    private static function planUpdate(array &$updates, int &$same, int $ticketId, string $kode, $curRt, $curRw, $legacyRt, $legacyRw): void
    {
        $expRt = self::clean($legacyRt);
        $expRw = self::clean($legacyRw);
        $nowRt = self::clean($curRt);
        $nowRw = self::clean($curRw);

        $set = [];
        if ((string) $expRt !== (string) $nowRt) {
            $set['rt'] = $expRt;
        }
        if ((string) $expRw !== (string) $nowRw) {
            $set['rw'] = $expRw;
        }

        if ($set) {
            $updates[] = [
                'ticket_id' => $ticketId,
                'kode' => $kode,
                'direction' => self::direction($kode),
                'set' => $set,
            ];
        } else {
            $same++;
        }
    }

    /**
     * Arah/varian pemasangan dari kode instalasi legacy.
     *
     * Konvensi legacy: kode berakhiran `_D` (mis. `INS-501_D`) berarti
     * pemasangan DEMPET. Selain itu dianggap NORMAL.
     */
    private static function direction(string $kode): string
    {
        $k = strtoupper(trim($kode));

        return str_ends_with($k, '_D') ? 'DEMPET' : 'NORMAL';
    }

    /**
     * Bersihkan nilai legacy: NULL / '' / '-'(/0) menjadi NULL.
     * Nilai lain dipertahankan apa adanya (leading zero TIDAK dibuang).
     */
    private static function clean($v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);
        if ($s === '' || $s === '-' || $s === '0') {
            return null;
        }

        return mb_substr($s, 0, 10);
    }
}
