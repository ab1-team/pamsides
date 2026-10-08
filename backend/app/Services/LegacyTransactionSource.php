<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Sumber kebenaran untuk tabel `transactions` (jurnal) dari legacy.
 *
 * Legacy memakai satu tabel `transactions` dengan:
 *   rekening_debit / rekening_kredit → accounts.id
 *   keterangan                      → jenis jurnal (awalan kata)
 *   usage_id / installation_id      → relasi ke tagihan / instalasi
 *   transaction_id                  → "rekening.inst_id.usage_id" (grouping)
 *
 * DB baru menyimpan kode akun sebagai STRING (kode_akun), bukan id, dan
 * tidak ada kolom usage_id/installation_id. Relasi diganti:
 *   reverence_type + reverence_id → monthly_bills / bill_payments / payments
 *
 * Karena id tidak dipertahankan, pencocokan transaksi memakai "signature":
 *   (tgl_transaksi, kode_debet, kode_kredit, nominal, keterangan)
 */
class LegacyTransactionSource
{
    /** Keterangan legacy → reverence_type di DB baru. */
    private const TYPE_RULES = [
        'Utang Komisi' => 'commission',        // 5.1.02.04 → 2.1.02.02
        'Biaya instalasi' => 'payment',   // 4.1.01.01
        'Penghapusan Piutang' => 'writeoff',    // 1.1.04.01 → 1.1.03.01
        'Bayar' => 'bill_payment',
        'Pendapatan' => 'monthly_bill',
        'Piutang Denda' => 'overdue_bill',
        'Utang' => 'overdue_bill',
    ];

    /** Akun header legacy yang tidak ada di tabel accounts. */
    private const ACCOUNT_FALLBACK = [
        113 => '1.1.03.01',
        114 => '1.1.04.01',
    ];

    /**
     * @return array{
     *   rows: array<int,array>,
     *   signatureIndex: array<string,int>,
     *   total: int
     * }
     */
    public static function collect(int $legacyBusinessId = 5): array
    {
        $L = DB::connection('legacy');

        $accCode = [];
        foreach ($L->table('accounts')->get(['id', 'kode_akun']) as $a) {
            $accCode[(int) $a->id] = (string) $a->kode_akun;
        }
        foreach (self::ACCOUNT_FALLBACK as $id => $code) {
            $accCode[$id] ??= $code;
        }

        $rows = $L->table('transactions')
            ->where('business_id', $legacyBusinessId)
            ->orderBy('id')
            ->get(['id', 'tgl_transaksi', 'rekening_debit', 'rekening_kredit', 'user_id',
                'usage_id', 'installation_id', 'total', 'transaction_id', 'relasi', 'keterangan', 'urutan',
                'created_at']);

        $out = [];
        $index = [];
        foreach ($rows as $t) {
            $debet = $accCode[(int) $t->rekening_debit] ?? null;
            $kredit = $accCode[(int) $t->rekening_kredit] ?? null;
            if ($debet === null || $kredit === null) {
                continue; // akun tidak dikenal → tidak bisa dipetakan
            }

            $date = self::parseDate($t->tgl_transaksi);
            if ($date === null) {
                continue;
            }

            $keterangan = trim((string) $t->keterangan);
            $row = [
                'legacy_id' => (int) $t->id,
                'tgl_transaksi' => $date->toDateString(),
                'account_debet' => $debet,
                'account_kredit' => $kredit,
                'keterangan_transaksi' => $keterangan,
                'relasi' => self::cleanRelasi($t->relasi),
                'saldo' => (float) $t->total,
                'urutan' => (int) $t->urutan ?: null,
                'legacy_user_id' => (int) $t->user_id,
                'usage_id' => (int) $t->usage_id,
                'installation_id' => (int) $t->installation_id,
                'transaction_group' => self::parseGroup($t->transaction_id),
                'reverence_type' => self::reverenceType($keterangan),
                'created_at' => $t->created_at ? self::parseDateTime($t->created_at) : null,
            ];
            $out[] = $row;
            $index[self::signature($row)] = ($index[self::signature($row)] ?? 0) + 1;
        }

        return ['rows' => $out, 'signatureIndex' => $index, 'total' => count($out)];
    }

    /** signature unik untuk mencocokkan transaksi legacy ↔ DB baru */
    public static function signature(array $row): string
    {
        return $row['tgl_transaksi']
            . '|' . $row['account_debet']
            . '|' . $row['account_kredit']
            . '|' . number_format((float) $row['saldo'], 2, '.', '')
            . '|' . trim((string) $row['keterangan_transaksi']);
    }

    public static function signatureOfNew(object $row): string
    {
        return $row->tgl_transaksi
            . '|' . $row->account_debet
            . '|' . $row->account_kredit
            . '|' . number_format((float) $row->saldo, 2, '.', '')
            . '|' . trim((string) $row->keterangan_transaksi);
    }

    public static function reverenceType(string $keterangan): string
    {
        foreach (self::TYPE_RULES as $prefix => $type) {
            if (str_starts_with($keterangan, $prefix)) {
                return $type;
            }
        }

        return 'other';
    }

    /**
     * "594.7350.18447" → 594735018447 (kode) supaya bisa dikelompokkan
     * seperti transaction_group di DB baru.
     */
    private static function parseGroup($transactionId): ?int
    {
        $v = trim((string) $transactionId);
        if ($v === '' || $v === '0') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $v);
        if ($digits === '') {
            return null;
        }
        // Batasi agar muat di bigint (max 20 digit)
        $digits = ltrim($digits, '0');
        if ($digits === '') {
            return null;
        }

        return strlen($digits) > 18 ? substr($digits, 0, 18) : (int) $digits;
    }

    private static function cleanRelasi($v): ?string
    {
        $s = trim((string) $v);
        if ($s === '' || $s === '0' || $s === '-') {
            return null;
        }

        return $s;
    }

    private static function parseDate($value): ?\Illuminate\Support\Carbon
    {
        if ($value === null) {
            return null;
        }
        $v = trim((string) $value);
        if ($v === '' || str_starts_with($v, '0000-00-00')) {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse($v);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function parseDateTime($value): ?string
    {
        $d = self::parseDate($value);

        return $d === null ? null : $d->toDateTimeString();
    }
}