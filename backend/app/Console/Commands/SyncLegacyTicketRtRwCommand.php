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

        $align = LegacyTicketAligner::build($biz);
        $map = $align['map'];
        $this->info(sprintf(
            'Alignment: %d pasang | akurasi %.2f%% (anchor %d/%d)',
            count($map), $align['acc'], $align['anchors'], $align['total']
        ));
        if ($align['acc'] < $minAcc) {
            $this->error(sprintf('Akurasi %.2f%% < batas %.2f%%. DIBATALKAN.', $align['acc'], $minAcc));

            return self::FAILURE;
        }
        $this->line('');

        $legacy = $L->table('installations')->where('business_id', $biz)
            ->get(['id', 'kode_instalasi', 'rt', 'rw']);
        $tickets = $N->table('installation_tickets')->get(['id', 'rt', 'rw'])->keyBy('id');

        $updates = [];
        $same = 0;
        $noPair = 0;

        foreach ($legacy as $i) {
            $ticketId = $map[(int) $i->id] ?? null;
            if ($ticketId === null || ! isset($tickets[$ticketId])) {
                $noPair++;
                continue;
            }
            $t = $tickets[$ticketId];

            // Legacy kosong (NULL / '' / '-') disamakan menjadi NULL.
            $expRt = self::clean($i->rt);
            $expRw = self::clean($i->rw);
            $curRt = self::clean($t->rt);
            $curRw = self::clean($t->rw);

            $set = [];
            if ((string) $expRt !== (string) $curRt) {
                $set['rt'] = $expRt;
            }
            if ((string) $expRw !== (string) $curRw) {
                $set['rw'] = $expRw;
            }
            if ($set) {
                $updates[] = [
                    'ticket_id' => $ticketId,
                    'kode' => (string) $i->kode_instalasi,
                    'set' => $set,
                ];
            } else {
                $same++;
            }
        }

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

        $this->line(sprintf('Tiket_pairs   : %d', count($legacy) - $noPair));
        $this->line(sprintf('Sudah sama   : %d', $same));
        $this->line(sprintf('Perlu update  : %d', count($updates)));
        $this->line(sprintf('  rt berubah  : %d', $rtFill));
        $this->line(sprintf('  rw berubah  : %d', $rwFill));
        $this->line(sprintf('Di becoming NULL: %d', $toNull));
        if ($noPair) {
            $this->line(sprintf('Legacy tanpa pasangan tiket: %d', $noPair));
        }
        $this->line('');

        $this->info('Contoh perubahan:');
        foreach (array_slice($updates, 0, 12) as $u) {
            $parts = [];
            foreach ($u['set'] as $k => $v) {
                $parts[] = $k.'='.json_encode($v);
            }
            $this->line(sprintf('   ticket=%-5d kode=%-18s %s', $u['ticket_id'], $u['kode'], implode(' ', $parts)));
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
                $ids = array_column($chunk, 'ticket_id');
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
     * Bersihkan nilai legacy: NULL / '' / '-' menjadi NULL.
     * Nilai lain dipertahankan apa adanya (tanpa padding, legacy sudah
     * konsisten 2-3 karakter).
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