<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Village;
use App\Models\InstallationTicket;

class InspectVillagesCommand extends Command
{
    protected $signature = 'inspect:villages';
    protected $description = 'Lihat isi villages di DB baru';

    public function handle(): int
    {
        $rows = Village::orderBy('id')->get();
        $this->info('Villages di DB baru ('.$rows->count().'):');
        $this->table(
            ['ID', 'Village Name', 'Hamlet', 'Phone', 'Address'],
            $rows->map(fn($v) => [
                $v->id,
                $v->village_name,
                $v->hamlet_name ?? '-',
                $v->phone ?? '-',
                $v->address ? mb_substr($v->address, 0, 50) : '-',
            ])->toArray()
        );

        $this->line('');
        $this->info('Cek referensi dari installation_tickets:');
        foreach ($rows as $v) {
            $cnt = InstallationTicket::where('village_id', $v->id)->count();
            if ($cnt > 0) {
                $this->line("  Village #{$v->id} ({$v->village_name}) ← {$cnt} tiket");
            }
        }
        $orphan = InstallationTicket::whereNotNull('village_id')
            ->whereNotIn('village_id', Village::pluck('id')->toArray())
            ->count();
        $this->line("Tiket dgn village_id NULL atau tidak ada di villages: ".InstallationTicket::whereNull('village_id')->count());
        $this->line("Tiket dgn village_id ngambang (orphan): {$orphan}");

        return self::SUCCESS;
    }
}