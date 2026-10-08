<?php

namespace App\Console\Commands;

use App\Services\LegacyTicketAligner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeLegacyForeignTicketsCommand extends Command
{
    protected $signature = 'legacy:purge-foreign-tickets
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan penghapusan}
                            {--business=5 : business_id legacy yang DIPERTAHANKAN}';

    protected $description = 'Hapus installation_tickets yang berasal dari legacy business_id lain (bukan yang diminta), beserta seluruh data turunannya';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $keepBiz = (int) $this->option('business');

        $this->info('=== Hapus tiket dari business_id selain '.$keepBiz.' ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: data akan dihapus.');
        $this->line('');

        $L = DB::connection('legacy');
        $N = DB::connection();

        $align = LegacyTicketAligner::build($keepBiz);
        $paired = array_flip($align['map']);
        $this->info(sprintf('Alignment: %d tiket ter-pair ke legacy business_id=%d', count($align['map']), $keepBiz));
        $this->line('');

        // Hanya tiket yang customer_code-nya jelas milik business_id lain.
        // Jangan pakai "tidak ter-pair" — alignment bisa saja tidak menutupi
        // tiket yang sah (mis. paket NULL di legacy), sehingga Nearly semua
        // tiket business_id=5 ikut terambil.
// Kode instalasi bisa DIPAKAI BERBARU oleh beberapa business sekaligus
        // (mis. "1.01.0001" ada di biz1 dan biz5). Jadi simpan daftar
        // business per kode, dan hanya tandai "asing" kalau kode itu TIDAK
        // pernah dipakai business yang dipertahankan.
        $legacyBizByCode = [];
        foreach ($L->table('installations')->get(['kode_instalasi', 'business_id']) as $li) {
            $k = trim((string) $li->kode_instalasi);
            if ($k !== '') {
                $legacyBizByCode[$k][(int) $li->business_id] = true;
            }
        }

        $allTickets = $N->table('installation_tickets')->orderBy('id')->get();
        $customersByTicket = $N->table('customers')->get(['ticket_id', 'customer_code'])->keyBy('ticket_id');

        $foreign = $allTickets->filter(function ($t) use ($legacyBizByCode, $keepBiz, $paired, $customersByTicket) {
            $cust = $customersByTicket->get($t->id);
            if (! $cust) {
                return false;
            }
            $code = trim((string) $cust->customer_code);
            if (! isset($legacyBizByCode[$code])) {
                return false; // kode tidak dikenal legacy -> jangan sentuh
            }
            if (isset($legacyBizByCode[$code][$keepBiz])) {
                return false; // kode ini juga milik business yang dipertahankan
            }
            if (isset($paired[(int) $t->id])) {
                return false; // sudah ter-pair ke legacy business yang dipertahankan
            }

            return true;
        });

        $candidates = $foreign->values();

        if ($candidates->isEmpty()) {
            $this->info('Tidak ada tiket yang perlu dihapus.');

            return self::SUCCESS;
        }

        $ticketIds = $candidates->pluck('id')->map(fn ($x) => (int) $x)->all();

        // Rincian data turunan per tiket
        $rows = [];
        foreach ($candidates as $t) {
            $cust = $N->table('customers')->where('ticket_id', $t->id)->first();
            $bills = $cust ? $N->table('monthly_bills')->where('customer_id', $cust->id)->pluck('id')->all() : [];
            $rows[] = [
                'ticket' => $t,
                'customer' => $cust,
                'bill_count' => count($bills),
                'bill_ids' => $bills,
                'reading_count' => $cust ? $N->table('meter_readings')->where('customer_id', $cust->id)->count() : 0,
                'bill_payment_count' => $bills ? $N->table('bill_payments')->whereIn('bill_id', $bills)->count() : 0,
                'payment_count' => $N->table('payments')->where('ticket_id', $t->id)->count(),
                'survey_count' => $N->table('survey_results')->where('ticket_id', $t->id)->count(),
                'history_count' => $N->table('installation_ticket_histories')->where('installation_ticket_id', $t->id)->count(),
                'trouble_count' => $cust ? $N->table('trouble_reports')->where('customer_id', $cust->id)->count() : 0,
            ];
        }

        $this->info('--- Tiket yang akan dihapus ('.count($rows).") ---\n");
        foreach ($rows as $r) {
            $t = $r['ticket'];
            $cust = $r['customer'];
            $legacyInfo = '';
            if ($cust) {
                $li = $L->table('installations')->where('kode_instalasi', trim((string) $cust->customer_code))->first();
                if ($li) {
                    $legacyInfo = 'legacy_inst='.$li->id.' business_id='.$li->business_id;
                }
            }
            $this->line(sprintf(
                '  ticket=%-5d %-24s v=%-4s p=%-3s %-10s code=%-18s %s',
                $t->id, "'{$t->applicant_name}'", (string) $t->village_id, (string) $t->package_id,
                $t->status, $cust ? (string) $cust->customer_code : '(tanpa customer)', $legacyInfo
            ));
            $this->line(sprintf(
                '     turunannya: customers=%s bills=%d readings=%d bill_payments=%d payments=%d surveys=%d histories=%d troubles=%d',
                $cust ? '1' : '0', $r['bill_count'], $r['reading_count'], $r['bill_payment_count'],
                $r['payment_count'], $r['survey_count'], $r['history_count'], $r['trouble_count']
            ));
        }

        $totals = [
            'customers' => count(array_filter($rows, fn ($r) => $r['customer'] !== null)),
            'bills' => array_sum(array_column($rows, 'bill_count')),
            'readings' => array_sum(array_column($rows, 'reading_count')),
            'bill_payments' => array_sum(array_column($rows, 'bill_payment_count')),
            'payments' => array_sum(array_column($rows, 'payment_count')),
            'surveys' => array_sum(array_column($rows, 'survey_count')),
            'histories' => array_sum(array_column($rows, 'history_count')),
            'troubles' => array_sum(array_column($rows, 'trouble_count')),
        ];
        $this->line('');
        $this->line('Total yang akan dihapus: tickets='.count($ticketIds).' customers='.$totals['customers']
            .' monthly_bills='.$totals['bills'].' meter_readings='.$totals['readings']
            .' bill_payments='.$totals['bill_payments'].' payments='.$totals['payments']
            .' surveys='.$totals['surveys'].' histories='.$totals['histories'].' troubles='.$totals['troubles']);

        if ($dry) {
            $this->line('');
            $this->warn('DRY-RUN selesai. Jalankan dengan --force untuk menghapus.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->error('Menghapus...');

        $deleted = [];
        $N->transaction(function () use ($N, $rows, $ticketIds, &$deleted) {
            foreach ($rows as $r) {
                $cust = $r['customer'];
                if ($cust) {
                    if ($r['bill_ids']) {
                        $deleted['bill_payments'] += $N->table('bill_payments')->whereIn('bill_id', $r['bill_ids'])->delete();
                        $deleted['monthly_bills'] += $N->table('monthly_bills')->whereIn('id', $r['bill_ids'])->delete();
                    }
                    $deleted['meter_readings'] += $N->table('meter_readings')->where('customer_id', $cust->id)->delete();
                    $deleted['trouble_reports'] += $N->table('trouble_reports')->where('customer_id', $cust->id)->delete();
                }
                $deleted['payments'] += $N->table('payments')->where('ticket_id', $r['ticket']->id)->delete();
                $deleted['survey_results'] += $N->table('survey_results')->where('ticket_id', $r['ticket']->id)->delete();
                $deleted['installation_ticket_histories'] += $N->table('installation_ticket_histories')
                    ->where('installation_ticket_id', $r['ticket']->id)->delete();
                $deleted['customers'] += $N->table('customers')->where('ticket_id', $r['ticket']->id)->delete();
                $deleted['installation_tickets'] += $N->table('installation_tickets')->where('id', $r['ticket']->id)->delete();
            }
        });

        $this->line('');
        $this->info('Selesai. Baris dihapus:');
        foreach ($deleted as $table => $n) {
            $this->line(sprintf('   %-32s %d', $table, $n));
        }

        return self::SUCCESS;
    }
}