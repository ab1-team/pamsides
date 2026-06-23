<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateAmountController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000',
        ], [
            'bulan.required' => 'Bulan wajib diisi.',
            'bulan.between'  => 'Bulan harus antara 1-12.',
            'tahun.required' => 'Tahun wajib diisi.',
        ]);

        $startDate = Carbon::create($request->tahun, $request->bulan, 1);
        $endDate   = Carbon::now();

        if ($startDate->greaterThan($endDate)) {
            return response()->json([
                'success' => false,
                'data'    => ['message' => 'Bulan dan tahun tidak boleh lebih dari bulan ini.'],
            ], 422);
        }

        // Kumpulkan semua bulan dari input sampai bulan ini
        $periods = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $periods[] = [
                'tahun' => $current->year,
                'bulan' => str_pad($current->month, 2, '0', STR_PAD_LEFT),
            ];
            $current->addMonth();
        }

        // Hapus amount dari bulan input sampai bulan ini
        foreach ($periods as $period) {
            DB::table('amount')
                ->where('tahun', $period['tahun'])
                ->where('bulan', $period['bulan'])
                ->delete();
        }

        // Insert ulang amount dari transaksi
        foreach ($periods as $period) {
            $tahun = $period['tahun'];
            $bulan = $period['bulan'];

            // Ambil semua kode_akun yang terlibat di periode ini
            $kodeAkuns = DB::table('transactions')
                ->whereYear('tgl_transaksi', $tahun)
                ->whereMonth('tgl_transaksi', (int) $bulan)
                ->whereNull('deleted_at')
                ->selectRaw('account_debet as kode_akun')
                ->union(
                    DB::table('transactions')
                        ->whereYear('tgl_transaksi', $tahun)
                        ->whereMonth('tgl_transaksi', (int) $bulan)
                        ->whereNull('deleted_at')
                        ->selectRaw('account_kredit as kode_akun')
                )
                ->pluck('kode_akun')
                ->unique();

            foreach ($kodeAkuns as $kodeAkun) {
                $account = DB::table('accounts')->where('kode_akun', $kodeAkun)->first();

                if (! $account) continue;

                $startOfYear = "{$tahun}-01-01";
                $endOfMonth  = Carbon::create($tahun, (int) $bulan, 1)->endOfMonth()->toDateString();

                $debit = DB::table('transactions')
                    ->where('account_debet', $kodeAkun)
                    ->whereNull('deleted_at')
                    ->whereBetween('tgl_transaksi', [$startOfYear, $endOfMonth])
                    ->sum('saldo');

                $kredit = DB::table('transactions')
                    ->where('account_kredit', $kodeAkun)
                    ->whereNull('deleted_at')
                    ->whereBetween('tgl_transaksi', [$startOfYear, $endOfMonth])
                    ->sum('saldo');

                $id = (string) $account->id . $tahun . $bulan;

                DB::table('amount')->updateOrInsert(
                    ['id' => $id],
                    [
                        'account_id' => $account->id,
                        'tahun'      => $tahun,
                        'bulan'      => $bulan,
                        'debit'      => $debit,
                        'kredit'     => $kredit,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'message'         => 'Rekalibrasi amount berhasil.',
                'total_periode'   => count($periods),
                'dari'            => $startDate->format('Y-m'),
                'sampai'          => $endDate->format('Y-m'),
            ],
        ]);
    }
}
