<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\InstallationTicket;
use App\Models\MeterReading;
use App\Models\MonthlyBill;
use App\Models\WaterTariffBlock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Perbaiki pemakaian & tagihan Okt 2026 milik teknisi Sarji.
 *
 * Bug yang diperbaiki
 * ------------------
 * Pada 29 Okt 2026 seluruh pelanggan Sarji dicatat meter Okt lewat aplikasi.
 * Angka yang tersimpan sama persis dengan meter September (163 dari 163),
 * sehingga `usage_m3 = 0` dan `usage_charge = 0` untuk semua tagihan Okt.
 * Penyebabnya: form inputername "Meter Akhir" di-prefill dengan angka bulan
 * lalu, dan angka itu disimpan tanpa diubah.
 *
 * Data yang benar ada di file XLSX hasil transkripsi form kertas lapangan.
 * Command ini menerapkannya:
 *   1. `meter_readings.meter_value` periode Okt -> angka meter akhir dari XLSX
 *   2. `monthly_bills` di-recalculate (usage_m3, usage_charge, total_amount)
 *      memakai `TariffCalculator` yang sama dengan aplikasi
 *
 * Kenapa command, bukan SQL manual
 * -------------------------------
 * - hanya menyentuh baris pelanggan Sarji pada periode yang diminta,
 * - selalu membuat backup ke tabel terpisah sebelum mengubah apa pun,
 * - `--dry-run` untuk melihat rencana tanpa mengubah apa pun,
 * - TIDAK pernah truncate / drop / delete baris.
 *
 * Pemakaian:
 *   php artisan billing:fix-sarji-meter-okt --dry-run
 *   php artisan billing:fix-sarji-meter-okt --yes
 */
class FixSarjiMeterOkt extends Command
{
    protected $signature = 'billing:fix-sarji-meter-okt
                            {--data= : Path file XLSX berisi data meter (wajib)}
                            {--year=2026 : Tahun periode tagihan}
                            {--month=10 : Bulan periode tagihan}
                            {--teknisi=Sarji : Nama teknisi yang=data miliknya}
                            {--dry-run : Tampilkan rencana, jangan ubah apa pun}
                            {--yes : Lewati konfirmasi interaktif}';

    protected $description = 'Perbaiki meter Okt + recalculate tagihan Okt untuk pelanggan teknisi Sarji dari file XLSX.';

    /** Kode pelanggan yang salah ketik di XLSX -> kode yang benar di DB. */
    private const KODE_TYPO = [
        '4.03.0892' => '4.03.0992', // Sarmini
    ];

    /**
     * Baris yang TIDAK boleh otomatis diterapkan — angka di form kertas
     * meragukan (lihat sheet "Catatan" di file XLSX).
     *
     * Key = kode pelanggan, value = alasan.
     */
    private const SKIP_MANUAL = [
        '4.10.1030' => 'Angka Akhir di form tidak terbaca; DB juga tidak punya meter Sept/Okt (pelanggan ini tidak punya tagihan Okt).',
        '4.01.0921' => 'Meter akhir di form (881) lebih kecil dari meter Sept (964); ada tulisan "Salah" di kertas.',
    ];

    /**
     * Kode pelanggan yang angka akhirnya tertukar saat transkripsi.
     * Format: kode => [nilai salah di XLSX, nilai benar].
     */
    private const KOREKSI_TERUKAR = [
        '4.5.121021' => [845.0, 945.0], // Ngaji: 845 tertukar dengan 945
    ];

    public function handle()
    {
        $dataPath = (string) $this->option('data');
        $year = (int) $this->option('year');
        $month = (int) $this->option('month');
        $teknisi = (string) $this->option('teknisi');
        $dryRun = (bool) $this->option('dry-run');

        if ($dataPath === '' || ! is_file($dataPath)) {
            $this->error('File XLSX tidak ditemukan. Pakai --data=<path>.');

            return self::FAILURE;
        }

        $teknisiRow = DB::table('users')->where('name', $teknisi)->where('role', 'teknisi')->first();

        if (! $teknisiRow) {
            $this->error("Teknisi '{$teknisi}' tidak ditemukan.");

            return self::FAILURE;
        }

        $this->info("Teknisi: {$teknisiRow->name} (id {$teknisiRow->id}) | periode {$year}-{$month}");

        $rows = $this->bacaXlsx($dataPath);

        if (empty($rows)) {
            $this->error('Tidak ada baris data di file XLSX.');

            return self::FAILURE;
        }

        $this->info('Baris XLSX: '.count($rows));

        // Pelanggan milik teknisi tsb (filter utama — MENCEGAH data teknisi
        // lain ikut tersentuh).
        $pelanggan = DB::table('installation_tickets as t')
            ->join('customers as c', 'c.ticket_id', '=', 't.id')
            ->where('t.user_id', $teknisiRow->id)
            ->get([
                'c.id as customer_id', 'c.customer_code', 'c.initial_meter_reading',
                't.id as ticket_id', 't.applicant_name', 't.package_id',
            ]);

        $byKode = [];
        foreach ($pelanggan as $p) {
            $byKode[(string) $p->customer_code] = $p;
        }

        // Meter bulan sebelumnya (N-1) = meter awal periode ini.
        $prevReadings = DB::table('meter_readings')
            ->where('reading_year', $year)
            ->where('reading_month', $month - 1)
            ->whereIn('customer_id', $pelanggan->pluck('customer_id'))
            ->get(['customer_id', 'meter_value'])
            ->keyBy('customer_id');

        // Tagihan periode ini.
        $bills = DB::table('monthly_bills')
            ->where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->whereNull('deleted_at')
            ->whereIn('customer_id', $pelanggan->pluck('customer_id'))
            ->get(['id', 'customer_id', 'meter_reading_start', 'meter_reading_end', 'usage_m3',
                'usage_charge', 'abodemen', 'penalty_amount', 'total_amount', 'status'])
            ->keyBy('customer_id');

        // Blok tarif per paket.
        $blocks = WaterTariffBlock::orderBy('usage_min_m3')->get()->groupBy('package_id');

        $rencana = [];
        $lewati = [];
        $gagal = [];

        foreach ($rows as $row) {
            $kode = (string) $row['kode'];
            $kodeDb = self::KODE_TYPO[$kode] ?? $kode;

            $pelangganRow = $byKode[$kodeDb] ?? null;

            if (! $pelangganRow) {
                $gagal[] = "No{$row['no']} {$row['nama']}: kode {$kode} tidak ada di daftar pelanggan {$teknisi}.";

                continue;
            }

            if (isset(self::SKIP_MANUAL[$kodeDb])) {
                $lewati[] = "No{$row['no']} {$row['nama']} ({$kodeDb}): ".self::SKIP_MANUAL[$kodeDb];

                continue;
            }

            $customerId = (int) $pelangganRow->customer_id;

            // Meter awal = meter bulan sebelumnya.
            $prev = $prevReadings->get($customerId);
            $meterAwal = $prev ? (float) $prev->meter_value : (float) $pelangganRow->initial_meter_reading;

            $meterAkhir = $row['akhir'];

            if ($meterAkhir === null) {
                $lewati[] = "No{$row['no']} {$row['nama']} ({$kodeDb}): meter akhir kosong.";

                continue;
            }

            $meterAkhir = (float) $meterAkhir;

            // Angka yang tertukar saat transkripsi -> pakai nilai benar.
            if (isset(self::KOREKSI_TERUKAR[$kodeDb])) {
                [$salah, $benar] = self::KOREKSI_TERUKAR[$kodeDb];

                if ($meterAkhir === $salah) {
                    $meterAkhir = $benar;
                    $this->line("  koreksi transkripsi: {$kodeDb} {$salah} -> {$benar}");
                }
            }

            // Meter tidak boleh mundur.
            if ($meterAkhir < $meterAwal) {
                $lewati[] = "No{$row['no']} {$row['nama']} ({$kodeDb}): meter akhir {$meterAkhir} < meter awal {$meterAwal}.";

                continue;
            }

            $usage = max(0, $meterAkhir - $meterAwal);
            $harga = $this->cariBlok($blocks->get($pelangganRow->package_id, collect()), $usage);
            $charge = $usage > 0 && $harga !== null ? round($usage * $harga, 2) : 0.0;

            $bill = $bills->get($customerId);
            $abodemen = $bill ? (float) $bill->abodemen : 0.0;
            $penalty = $bill ? (float) $bill->penalty_amount : 0.0;
            $total = round($charge + $abodemen + $penalty, 2);

            // Tagihan yang sudah lunas TIDAK boleh diubah — uangnya sudah
            // masuk kas dan buktinya jadi bagian audit.
            if ($bill && $bill->status === 'paid') {
                $lewati[] = "No{$row['no']} {$row['nama']} ({$kodeDb}): tagihan sudah lunas (paid).";

                continue;
            }

            $rencana[] = [
                'no' => $row['no'],
                'nama' => $row['nama'],
                'kode' => $kodeDb,
                'customer_id' => $customerId,
                'ticket_id' => (int) $pelangganRow->ticket_id,
                'bill_id' => $bill ? (int) $bill->id : null,
                'meter_awal' => $meterAwal,
                'meter_akhir_baru' => $meterAkhir,
                'usage_baru' => $usage,
                'charge_baru' => $charge,
                'abodemen' => $abodemen,
                'penalty' => $penalty,
                'total_baru' => $total,
            ];
        }

        $this->info(sprintf(
            'Rencana: %d pelanggan akan diperbaiki, %d dilewati, %d gagal dipetakan.',
            count($rencana), count($lewati), count($gagal)
        ));

        $totalM3 = array_sum(array_column($rencana, 'usage_baru'));
        $totalCharge = array_sum(array_column($rencana, 'charge_baru'));
        $totalTagihan = array_sum(array_column($rencana, 'total_baru'));

        $this->info(sprintf(
            'Total pemakaian: %.2f m3 | total biaya pemakaian: Rp %s | total seluruh tagihan: Rp %s',
            $totalM3,
            number_format($totalCharge, 0, ',', '.'),
            number_format($totalTagihan, 0, ',', '.')
        ));

        if (! empty($gagal)) {
            $this->warn('--- gagal dipetakan ---');
            foreach ($gagal as $g) {
                $this->warn('  '.$g);
            }
        }

        if (! empty($lewati)) {
            $this->warn('--- dilewati (perlu konfirmasi manual) ---');
            foreach ($lewati as $l) {
                $this->warn('  '.$l);
            }
        }

        if (empty($rencana)) {
            $this->info('Tidak ada yang perlu diperbaiki.');

            return self::SUCCESS;
        }

        // Contoh 12 baris pertama.
        $this->info('--- contoh 12 baris pertama ---');
        $this->table(
            ['No', 'Nama', 'Kode', 'Meter Awal', 'Meter Akhir', 'Pemakaian', 'Tagihan'],
            array_map(fn ($r) => [
                $r['no'], $r['nama'], $r['kode'],
                $r['meter_awal'], $r['meter_akhir_baru'],
                $r['usage_baru'], number_format($r['total_baru'], 0, ',', '.'),
            ], array_slice($rencana, 0, 12))
        );

        if ($dryRun) {
            $this->warn('--dry-run: tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        if (! $this->option('yes') && ! $this->confirm('Lanjutkan koreksi data meter + tagihan?', true)) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        // ── Backup dulu ────────────────────────────────────────────────
        $suffix = strtolower($year.'_'.$month);
        $bakBill = 'monthly_bills_bak_sarji_'.$suffix;
        $bakMeter = 'meter_readings_bak_sarji_'.$suffix;

        DB::statement("CREATE TABLE IF NOT EXISTS `{$bakBill}` LIKE monthly_bills");
        DB::statement("CREATE TABLE IF NOT EXISTS `{$bakMeter}` LIKE meter_readings");

        DB::statement(
            "INSERT IGNORE INTO `{$bakBill}` SELECT * FROM monthly_bills
             WHERE deleted_at IS NULL AND billing_period_year = ? AND billing_period_month = ?
               AND customer_id IN (SELECT c.id FROM customers c
                                   JOIN installation_tickets t ON t.id = c.ticket_id
                                   WHERE t.user_id = ?)",
            [$year, $month, $teknisiRow->id]
        );

        DB::statement(
            "INSERT IGNORE INTO `{$bakMeter}` SELECT * FROM meter_readings
             WHERE reading_year = ? AND reading_month = ?
               AND customer_id IN (SELECT c.id FROM customers c
                                   JOIN installation_tickets t ON t.id = c.ticket_id
                                   WHERE t.user_id = ?)",
            [$year, $month, $teknisiRow->id]
        );

        $this->info("Backup dibuat: {$bakBill}, {$bakMeter}");

        // ── Terapkan ──────────────────────────────────────────────────
        $berhasil = 0;
        $gagalTerapkan = [];

        foreach ($rencana as $r) {
            try {
                DB::transaction(function () use ($r, $year, $month) {
                    // 1. Update / create meter reading.
                    $reading = MeterReading::where('customer_id', $r['customer_id'])
                        ->where('reading_year', $year)
                        ->where('reading_month', $month)
                        ->first();

                    if ($reading) {
                        $reading->update(['meter_value' => $r['meter_akhir_baru']]);
                    } else {
                        MeterReading::create([
                            'customer_id' => $r['customer_id'],
                            'recorded_by' => DB::table('installation_tickets')
                                ->where('id', $r['ticket_id'])->value('user_id'),
                            'reading_year' => $year,
                            'reading_month' => $month,
                            'meter_value' => $r['meter_akhir_baru'],
                            'photo_url' => null,
                            'recorded_at' => now(),
                        ]);
                    }

                    // 2. Update tagihan.
                    if ($r['bill_id']) {
                        DB::table('monthly_bills')
                            ->where('id', $r['bill_id'])
                            ->update([
                                'meter_reading_start' => $r['meter_awal'],
                                'meter_reading_end' => $r['meter_akhir_baru'],
                                'usage_m3' => $r['usage_baru'],
                                'usage_charge' => $r['charge_baru'],
                                'total_amount' => $r['total_baru'],
                                'updated_at' => now(),
                            ]);
                    } else {
                        MonthlyBill::create([
                            'customer_id' => $r['customer_id'],
                            'billing_period_year' => $year,
                            'billing_period_month' => $month,
                            'meter_reading_start' => $r['meter_awal'],
                            'meter_reading_end' => $r['meter_akhir_baru'],
                            'usage_m3' => $r['usage_baru'],
                            'usage_charge' => $r['charge_baru'],
                            'abodemen' => $r['abodemen'],
                            'penalty_amount' => $r['penalty'],
                            'total_amount' => $r['total_baru'],
                            'status' => 'unpaid',
                            'due_date' => now()->addMonthNoOverflow()->day(min(
                                (int) (DB::table('settings')->where('id', 1)->value('toleransi_tunggakan') ?? 27),
                                now()->addMonthNoOverflow()->daysInMonth
                            ))->toDateString(),
                        ]);
                    }
                });

                $berhasil++;
            } catch (\Throwable $e) {
                $gagalTerapkan[] = "No{$r['no']} {$r['nama']} ({$r['kode']}): ".$e->getMessage();
            }
        }

        $this->info(sprintf('Selesai. %d pelanggan diperbaiki, %d gagal.', $berhasil, count($gagalTerapkan)));

        foreach ($gagalTerapkan as $g) {
            $this->error('  '.$g);
        }

        $this->info("Backup: {$bakBill}, {$bakMeter}");

        return self::SUCCESS;
    }

    /**
     * Baca file XLSX -> array baris data.
     *
     * Format file: baris judul di baris 1-6, header di baris 7, data mulai
     * baris 8. Kolom: No | Nama | No. Induk | RT | Awal | Akhir | Pakai.
     */
    private function bacaXlsx(string $path): array
    {
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        // Shared strings.
        $shared = [];
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
            foreach ($xml->si ?? [] as $si) {
                $shared[] = $this->teksDariSi($si);
            }
        }

        // Sheet pertama.
        $sheetName = null;
        if ($zip->locateName('xl/workbook.xml') !== false) {
            $wb = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
            $sheetName = (string) ($wb->sheets->sheet[0]['name'] ?? 'Sheet1');
        }

        $sheetPath = 'xl/worksheets/'.preg_replace('/[^A-Za-z0-9_]/', '', $sheetName).'.xml';

        if ($zip->locateName($sheetPath) === false) {
            $sheetPath = 'xl/worksheets/sheet1.xml';
        }

        if ($zip->locateName($sheetPath) === false) {
            $zip->close();

            return [];
        }

        $sheet = simplexml_load_string($zip->getFromName($sheetPath));
        $zip->close();

        // Baris data: cari baris yang kolom A berisi angka urut.
        $rows = [];

        foreach ($sheet->sheetData->row ?? [] as $row) {
            $cells = [];
            $maxCol = 0;

            foreach ($row->c ?? [] as $c) {
                $ref = (string) $c['r'];
                $col = $this->kolomDariRef($ref);
                $maxCol = max($maxCol, $col);
                $cells[$col] = $this->nilaiCell($c, $shared);
            }

            $no = $cells[1] ?? null;

            if (! is_numeric($no)) {
                continue;
            }

            $rows[] = [
                'no' => (int) $no,
                'nama' => trim((string) ($cells[2] ?? '')),
                'kode' => trim((string) ($cells[3] ?? '')),
                'rt' => trim((string) ($cells[4] ?? '')),
                'awal' => $cells[5] ?? null,
                'akhir' => $cells[6] ?? null,
                'pakai' => $cells[7] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * Teks gabungan dari satu `<si>` (Shared String Item).
     */
    private function teksDariSi(\SimpleXMLElement $si): string
    {
        if (isset($si->t)) {
            return (string) $si->t;
        }

        $out = '';

        foreach ($si->r ?? [] as $r) {
            $out .= (string) ($r->t ?? '');
        }

        return $out;
    }

    /**
     * Nilai satu sel. Formula (kolom G = Akhir - Awal) diabaikan; yang
     * dipakai adalah kolom E (Awal) dan F (Akhir) yang nilainya literal.
     */
    private function nilaiCell(\SimpleXMLElement $c, array $shared)
    {
        $type = (string) ($c['t'] ?? '');

        if ($type === 's') {
            $idx = (int) $c->v;

            return $shared[$idx] ?? '';
        }

        if ($type === 'inlineStr') {
            return (string) ($c->is->t ?? '');
        }

        $v = (string) ($c->v ?? '');

        if ($v === '') {
            return null;
        }

        return is_numeric($v) ? (float) $v : $v;
    }

    /**
     * Nomor kolom dari referensi sel, mis. "C12" -> 3.
     */
    private function kolomDariRef(string $ref): int
    {
        preg_match('/^([A-Z]+)/', $ref, $m);

        $col = 0;

        foreach (str_split($m[1] ?? 'A') as $ch) {
            $col = $col * 26 + (ord($ch) - 64);
        }

        return $col;
    }

    /**
     * Blok tarif tempat pemakaian berada (`usage_min_m3` & `usage_max_m3`
     * inklusif). Sama seperti `TariffCalculator`.
     */
    private function cariBlok($blocks, float $usageM3): ?float
    {
        if ($blocks->isEmpty() || $usageM3 <= 0) {
            return null;
        }

        foreach ($blocks as $block) {
            $min = (float) $block->usage_min_m3;
            $max = $block->usage_max_m3 === null ? null : (float) $block->usage_max_m3;

            if ($usageM3 >= $min && ($max === null || $usageM3 <= $max)) {
                return (float) $block->price_per_m3;
            }
        }

        foreach ($blocks as $block) {
            if ((float) $block->usage_min_m3 > $usageM3) {
                return (float) $block->price_per_m3;
            }
        }

        return null;
    }
}
