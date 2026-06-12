<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Deteksi pelanggaran foreign key (SQLSTATE 23000 / errno 1451/1452 untuk MySQL).
     */
    protected function isForeignKeyViolation(QueryException $e): bool
    {
        if ($e->getCode() === '23000') {
            return true;
        }

        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;

        if ($sqlState === '23000' && in_array((int) $driverCode, [1451, 1452, 1217, 1216], true)) {
            return true;
        }

        return str_contains(strtolower($e->getMessage()), 'foreign key constraint');
    }

    /**
     * Coba deteksi tabel/kolom referensi dari pesan error SQL.
     * Mengembalikan array [code, usage, message] untuk response.
     */
    protected function detectForeignKeyUsage(string $message, string $entityCode, string $entityLabel, ?string $displayName = null): array
    {
        $haystack = strtolower($message);

        $candidates = [
            'installation_tickets' => 'tiket instalasi',
            'tickets' => 'tiket',
            'customers' => 'pelanggan',
            'customer' => 'pelanggan',
            'meter_readings' => 'pencatatan meter',
            'meter_reading' => 'pencatatan meter',
            'installation_results' => 'hasil instalasi',
            'survey_results' => 'hasil survey',
            'monthly_bills' => 'tagihan bulanan',
            'monthly_bill' => 'tagihan bulanan',
            'payments' => 'pembayaran',
            'trouble_reports' => 'laporan gangguan',
            'meters' => 'meteran',
            'usages' => 'pemakaian air',
            'bills' => 'tagihan',
            'users' => 'pengguna',
            'user' => 'pengguna',
            'villages' => 'desa',
            'village' => 'desa',
            'installation_packages' => 'paket instalasi',
            'water_tariff_blocks' => 'blok tarif air',
            'sop' => 'pengaturan SOP',
            'sops' => 'pengaturan SOP',
        ];

        $usage = null;
        foreach ($candidates as $needle => $label) {
            if (str_contains($haystack, $needle)) {
                $usage = $label;
                break;
            }
        }

        $name = $displayName ? "\"{$displayName}\"" : $entityLabel;
        $message = "{$name} tidak dapat dihapus karena masih digunakan pada data lain" . ($usage ? " ({$usage})." : '.');

        return [
            'code' => $entityCode,
            'usage' => $usage,
            'message' => $message,
        ];
    }

    /**
     * Try to delete a model; returns JsonResponse either success or FK-violation (409).
     * Pass a custom success message via $successMessage (otherwise defaults to "X berhasil dihapus").
     * Pass a custom success response builder via $successResponse($entityLabel) for non-standard shapes.
     */
    protected function safeDelete(callable $deleteFn, string $entityCode, string $entityLabel, ?string $displayName = null, ?string $successMessage = null, ?\Closure $successResponse = null): JsonResponse
    {
        try {
            $deleteFn();
        } catch (QueryException $e) {
            if ($this->isForeignKeyViolation($e)) {
                $info = $this->detectForeignKeyUsage($e->getMessage(), $entityCode, $entityLabel, $displayName);
                return response()->json([
                    'success' => false,
                    'code' => $info['code'],
                    'message' => $info['message'],
                    'usage' => $info['usage'],
                ], 409);
            }

            throw $e;
        }

        if ($successResponse) {
            return $successResponse($entityLabel);
        }

        return response()->json([
            'success' => true,
            'message' => $successMessage ?? "{$entityLabel} berhasil dihapus",
        ]);
    }
}
