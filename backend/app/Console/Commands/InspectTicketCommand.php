<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InstallationTicket;
use App\Models\Customer;

class InspectTicketCommand extends Command
{
    protected $signature = 'inspect:ticket
                            {--id= : ID tiket legacy}
                            {--package= : Lihat tiket yg reference package_id}';

    protected $description = 'Intip detail tiket';

    public function handle(): int
    {
        if ($pid = $this->option('package')) {
            $tickets = InstallationTicket::where('package_id', $pid)->get();
            $this->info("Tiket yang reference package_id={$pid}: ".$tickets->count());
            foreach ($tickets as $t) {
                $this->line(json_encode($t->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->line('---');
            }
            return self::SUCCESS;
        }

        if ($id = $this->option('id')) {
            $t = InstallationTicket::find($id);
            if (! $t) { $this->error('Tidak ditemukan.'); return self::FAILURE; }
            $this->line(json_encode($t->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $this->error('Pakai --id= atau --package=');
        return self::FAILURE;
    }
}