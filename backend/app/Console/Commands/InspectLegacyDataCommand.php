<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InspectLegacyDataCommand extends Command
{
    protected $signature = 'legacy:inspect
                            {--table= : Tabel legacy yang mau diintip}
                            {--column=* : (opsional) Batasi kolom, repeatable}
                            {--where= : (opsional) Klausa WHERE mentah, mis. "status=\'A\'"}
                            {--limit=20 : Jumlah baris}';

    protected $description = 'Intip isi tabel DB lama dengan kolom panjang (JSON, text, dll)';

    public function handle(): int
    {
        $table = $this->option('table');
        if (! $table) {
            $this->error('Wajib sebutkan --table=');
            return self::FAILURE;
        }

        $query = DB::connection('legacy')->table($table);
        if ($cols = $this->option('column')) {
            $query->select($cols);
        }
        if ($where = $this->option('where')) {
            $query->whereRaw($where);
        }
        $rows = $query->limit((int) $this->option('limit'))->get();

        $this->info("Isi tabel legacy '{$table}' (".count($rows)." baris):");
        $this->line('');

        foreach ($rows as $i => $row) {
            $this->line(str_repeat('─', 80));
            $this->line('Baris #'.($i + 1).'  |  legacy_id = '.($row->id ?? '-'));
            $this->line(str_repeat('─', 80));
            foreach ((array) $row as $col => $val) {
                if (is_string($val) && mb_strlen($val) > 200) {
                    $val = mb_substr($val, 0, 200).'…[truncated]';
                }
                $this->line(sprintf('  %-25s : %s', $col, is_scalar($val) ? $val : json_encode($val)));
            }
        }

        return self::SUCCESS;
    }
}