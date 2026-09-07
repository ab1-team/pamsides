<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RestoreFromXlsBackupCommand extends Command
{
    protected $signature = 'restore:xls
                            {--source= : Folder berisi file .xls backup}
                            {--table=* : Tabel yang akan di-restore (default: transactions, accounts, akun_level_1, akun_level_2, akun_level_3, jenis_transactions)}
                            {--truncate : Truncate target table sebelum insert}
                            {--batch=2000 : Insert batch size}
                            {--limit=0 : Batasi jumlah row (0=semua)}';

    protected $description = 'Restore data dari file .xls backup (binary Excel 97-2003) ke DB lokal.';

    public function handle(): int
    {
        $src = $this->option('source') ?: 'C:\Users\ASUS\Downloads\pamsides next';
        $src = rtrim($src, '/\\');
        $tables = $this->option('table') ?: [
            'jenis_transactions',
            'akun_level_1',
            'akun_level_2',
            'akun_level_3',
            'accounts',
            'transactions',
        ];
        $batch = max(100, (int) $this->option('batch'));
        $limit = (int) $this->option('limit');
        $truncate = (bool) $this->option('truncate');

        $this->info("Source: $src");
        $this->line('Tables: '.implode(', ', $tables));

        foreach ($tables as $table) {
            $file = "$src\\$table.xls";
            if (! file_exists($file)) {
                $this->warn("File $file tidak ditemukan, skip.");
                continue;
            }
            $this->info("\n=== $table ===");
            $this->line("Reading $file ...");

            try {
                $reader = IOFactory::createReaderForFile($file);
                $reader->setReadDataOnly(true);
                $sheet = $reader->load($file)->getActiveSheet();
            } catch (\Throwable $e) {
                $this->error('Load gagal: '.$e->getMessage());
                continue;
            }

            $rows = $sheet->toArray(null, true, true, true);
            if (empty($rows)) {
                $this->warn('Sheet kosong.');
                continue;
            }

            // baris pertama = header
            $headers = array_values(array_map('strtolower', array_map('trim', array_shift($rows))));
            $this->line('Header ('.count($headers).'): '.implode(', ', $headers));

            // filter baris kosong (semua null)
            $rows = array_values(array_filter($rows, function ($r) {
                foreach ($r as $v) if ($v !== null && $v !== '') return true;
                return false;
            }));

            if ($limit > 0 && count($rows) > $limit) {
                $rows = array_slice($rows, 0, $limit);
            }
            $this->line('Rows: '.count($rows));

            if ($truncate) {
                $this->warn("Truncate $table ...");
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::table($table)->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            $buffer = [];
            $inserted = 0;
            $skipped = 0;
            foreach ($rows as $r) {
                $vals = array_values($r);
                if (count($vals) < count($headers)) {
                    $vals = array_pad($vals, count($headers), null);
                } elseif (count($vals) > count($headers)) {
                    $vals = array_slice($vals, 0, count($headers));
                }
                $row = array_combine($headers, $vals);

                // Normalisasi: kosong string → null
                foreach ($row as $k => $v) {
                    if ($v === '' || $v === '0' && in_array($k, ['tgl_nonaktif'])) {
                        $row[$k] = null;
                    }
                }

                // Tanggal Excel numeric → date string Y-m-d
                foreach ($row as $k => $v) {
                    if (is_numeric($v) && in_array($k, ['tgl_transaksi', 'tgl_nonaktif', 'paid_at', 'created_at', 'updated_at', 'deleted_at'])) {
                        $ts = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((float) $v);
                        if ($ts > 0) {
                            $row[$k] = date('Y-m-d H:i:s', $ts);
                        }
                    }
                }

                // Convert id_user numeric → int
                foreach (['id_user', 'penerima_komisi_id', 'reverence_id', 'transaction_group', 'urutan'] as $k) {
                    if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                        $row[$k] = (int) $row[$k];
                    } elseif (array_key_exists($k, $row)) {
                        $row[$k] = null;
                    }
                }
                // saldo decimal
                if (array_key_exists('saldo', $row) && $row['saldo'] !== null && $row['saldo'] !== '') {
                    $row['saldo'] = (float) $row['saldo'];
                }

                // Khusus transactions: tanggal hanya date, datetime → date
                if ($table === 'transactions' && isset($row['tgl_transaksi'])) {
                    $row['tgl_transaksi'] = substr((string) $row['tgl_transaksi'], 0, 10);
                }

                $buffer[] = $row;
                if (count($buffer) >= $batch) {
                    try {
                        DB::table($table)->insert($buffer);
                        $inserted += count($buffer);
                    } catch (\Throwable $e) {
                        $skipped += count($buffer);
                        $this->warn('Batch insert error: '.$e->getMessage());
                    }
                    $buffer = [];
                }
            }
            if (! empty($buffer)) {
                try {
                    DB::table($table)->insert($buffer);
                    $inserted += count($buffer);
                } catch (\Throwable $e) {
                    $skipped += count($buffer);
                    $this->warn('Final insert error: '.$e->getMessage());
                }
            }

            $this->info("Inserted: $inserted, Skipped: $skipped");
            $this->line('Count in DB: '.DB::table($table)->count());
        }

        return self::SUCCESS;
    }
}
