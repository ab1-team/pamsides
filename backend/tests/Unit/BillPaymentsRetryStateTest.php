<?php

namespace Tests\Unit;

use App\Support\LegacyTransactionCursor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Regression test untuk logika retry import:bill-payments.
 *
 * Dua bug nyata ditemukan lewat test ini, keduanya hanya muncul saat koneksi
 * legacy PUTUS di tengah pemindaian (jalur yang justru ada untuk ditangani):
 *
 *  1. DATA LOSS -- versi lama mensyaratkan `! $pendingResume` sebelum flush grup.
 *     Setelah retry, `$pendingResume` tetap true sampai grup berganti, sehingga
 *     grup yang sedang dibangun dilewati flush, lalu ditimpa `$cur = $emptyGroup(...)`.
 *     Payment grup itu hilang total.
 *
 *  2. TOTAL KURANG -- me-reset `$cur` di awal percobaan menghapus total parsial
 *     yang sudah terkumpul sebelum koneksi putus.
 *
 * Yang benar: `$cur` justru HARUS dibawa lintas percobaan. Query resume membaca
 * baris STRICTLY SETELAH anchor, jadi baris yang sudah masuk `$cur` tidak pernah
 * dibaca ulang -- tidak ada risiko double count. Partial state dilanjutkan,
 * bukan di-reset.
 *
 * Test ini mereplikasi bentuk loop retry dari ImportLegacyBillPaymentsCommand
 * dengan sumber data in-memory, lalu menyuntikkan kegagalan pada baris tertentu.
 */
class BillPaymentsRetryStateTest extends TestCase
{
    /**
     * Baris legacy fiktif, sudah terurut (usage_id, tgl_transaksi, id) seperti
     * query aslinya. usage_id=10 punya 3 baris dalam satu grup.
     */
    private function legacyRows(): array
    {
        return [
            ['id' => 1, 'usage_id' => 9,  'tgl_transaksi' => '2026-01-05', 'total' => 100, 'user_id' => 3],
            ['id' => 2, 'usage_id' => 10, 'tgl_transaksi' => '2026-01-06', 'total' => 200, 'user_id' => 4],
            ['id' => 3, 'usage_id' => 10, 'tgl_transaksi' => '2026-01-06', 'total' => 300, 'user_id' => 4],
            ['id' => 4, 'usage_id' => 10, 'tgl_transaksi' => '2026-01-06', 'total' => 500, 'user_id' => 4],
            ['id' => 5, 'usage_id' => 11, 'tgl_transaksi' => '2026-01-07', 'total' => 700, 'user_id' => 5],
        ];
    }

    /**
     * Padanan `applyAfter` versi production:
     * WHERE usage_id > :au
     *    OR (usage_id = :au AND (tgl_transaksi > :at OR (tgl_transaksi = :at AND id > :ai)))
     *
     * @param  array  $after  [usage_id, tgl_transaksi, id]
     */
    private function applyAfter(array $rows, ?array $after): array
    {
        return LegacyTransactionCursor::filterAfter($rows, $after);
    }

    /**
     * Replikasi loop retry versi yang DIPERBAIKI.
     *
     * @param  int  $failAtId  id baris legacy yang memicu exception saat dibaca
     *                         (-1 = tidak pernah putus)
     * @return array{groups: array<int,array{usage_id:int,total:int}>, attempts: int}
     */
    private function runRetryLoop(int $failAtId): array
    {
        $rows = $this->legacyRows();

        $after = null;
        $attempt = 0;
        $maxRetries = 8;

        $groups = [];
        $curKey = null;
        $cur = null;

        // PENTING: TIDAK di-reset di sini (lihat catatan di docblock kelas).
        while (true) {
            try {
                $batch = $this->applyAfter($rows, $after);

                foreach ($batch as $t) {
                    if ((int) $t['id'] === $failAtId) {
                        throw new \RuntimeException('Legacy connection lost');
                    }

                    $key = $t['usage_id'].'|'.$t['tgl_transaksi'];

                    if ($curKey !== $key) {
                        // Flush grup sebelumnya TANPA syarat pendingResume.
                        if ($curKey !== null) {
                            $groups[] = ['usage_id' => $cur['usage_id'], 'total' => $cur['total']];
                        }
                        $curKey = $key;
                        $cur = [
                            'usage_id' => (int) $t['usage_id'],
                            'tgl_transaksi' => $t['tgl_transaksi'],
                            'total' => 0,
                            'legacy_user_id' => $t['user_id'],
                        ];
                    }
                    $cur['total'] += (int) $t['total'];

                    $after = [(int) $t['usage_id'], $t['tgl_transaksi'], (int) $t['id']];
                }
                break;
            } catch (\Throwable $e) {
                $attempt++;
                if ($attempt > $maxRetries) {
                    break;
                }
                // Legacy "dipulihkan" pada percobaan berikutnya.
                $failAtId = -1;
            }
        }

        if ($curKey !== null) {
            $groups[] = ['usage_id' => $cur['usage_id'], 'total' => $cur['total']];
        }

        return ['groups' => $groups, 'attempts' => $attempt];
    }

    /**
     * Hasil yang WAJIB sama dengan run tanpa gangguan -- ini kontrak idempotensi.
     */
    private function expectedGroups(): array
    {
        return [
            ['usage_id' => 9,  'total' => 100],
            ['usage_id' => 10, 'total' => 1000],   // 200 + 300 + 500
            ['usage_id' => 11, 'total' => 700],
        ];
    }

    #[Test]
    public function test_baseline_tanpa_retry(): void
    {
        $result = $this->runRetryLoop(-1);

        $this->assertSame(0, $result['attempts']);
        $this->assertSame($this->expectedGroups(), $result['groups']);
    }

    #[Test]
    #[DataProvider('titikPutusProvider')]
    public function test_retry_di_tiap_titik_tidak_mengubah_hasil(int $failAtId): void
    {
        $result = $this->runRetryLoop($failAtId);

        $this->assertSame(1, $result['attempts'], 'Harus retry tepat satu kali');
        $this->assertSame(
            $this->expectedGroups(),
            $result['groups'],
            'Putus di legacy id '.$failAtId.': hasil harus identik dengan run tanpa retry'
        );
    }

    public static function titikPutusProvider(): array
    {
        return [
            'putus sebelum grup pertama' => [1],
            'putus di baris pertama grup 10' => [2],
            'putus di tengah grup 10' => [3],
            'putus di baris terakhir grup 10' => [4],
            'putus saat berganti ke grup 11' => [5],
        ];
    }

    #[Test]
    public function test_retry_tidak_menggandakan_jumlah_event(): void
    {
        $result = $this->runRetryLoop(3);

        $this->assertCount(3, $result['groups'], 'Harus tetap 3 event unik');
    }

    #[Test]
    public function test_grup_tidak_hilang_setelah_retry(): void
    {
        // Bug #1 (data loss): grup yang dibangun saat retry tidak boleh hilang.
        $result = $this->runRetryLoop(2);

        $usageIds = array_column($result['groups'], 'usage_id');
        $this->assertSame([9, 10, 11], $usageIds, 'Semua grup harus ada');
    }

    #[Test]
    public function test_apply_after_membaca_strictly_setelah_anchor(): void
    {
        $rows = $this->legacyRows();

        // Anchor = baris terakhir grup 10 (id=4).
        $after = [10, '2026-01-06', 4];
        $resumed = $this->applyAfter($rows, $after);

        $this->assertSame(
            [5],
            array_column($resumed, 'id'),
            'Resume hanya boleh melihat baris setelah anchor'
        );

        // Anchor = baris pertama grup 10 (id=2) -> sisa grup 10 harus ikut terbaca.
        $after = [10, '2026-01-06', 2];
        $resumed = $this->applyAfter($rows, $after);

        $this->assertSame([3, 4, 5], array_column($resumed, 'id'));
    }
}
