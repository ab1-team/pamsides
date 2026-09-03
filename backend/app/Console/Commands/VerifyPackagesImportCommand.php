<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InstallationPackage;

class VerifyPackagesImportCommand extends Command
{
    protected $signature = 'verify:packages';
    protected $description = 'Cek hasil import paket di DB baru';

    public function handle(): int
    {
        $pkgs = InstallationPackage::orderBy('id')->get();
        $rows = [];
        foreach ($pkgs as $p) {
            $rows[] = [
                $p->id,
                $p->name,
                $p->installation_fee,
                $p->monthly_abodemen,
                $p->late_penalty,
                $p->waterTariffBlocks()->count(),
            ];
        }
        $this->table(
            ['ID', 'Name', 'Fee', 'Abodemen', 'Denda', 'Jumlah Blok'],
            $rows
        );
        $this->line('');
        $this->info('Total: '.InstallationPackage::count().' paket, '.\App\Models\WaterTariffBlock::count().' blok');
        return self::SUCCESS;
    }
}