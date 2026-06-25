<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EbudgetingController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('ebudgeting')
            ->join('accounts', 'ebudgeting.account_id', '=', 'accounts.id')
            ->select('ebudgeting.*', 'accounts.kode_akun', 'accounts.nama_akun')
            ->orderBy('ebudgeting.tahun', 'desc')
            ->orderBy('ebudgeting.bulan', 'desc');

        if ($request->has('tahun') && $request->tahun) {
            $query->where('ebudgeting.tahun', $request->tahun);
        }

        if ($request->has('bulan') && $request->bulan) {
            $query->where('ebudgeting.bulan', $request->bulan);
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function checkExists(Request $request)
    {
        $request->validate([
            'tahun' => 'required|integer',
            'bulan' => 'required|integer|between:1,12',
        ]);

        $count = DB::table('ebudgeting')
            ->where('tahun', $request->tahun)
            ->where('bulan', $request->bulan)
            ->count();

        return response()->json([
            'success' => true,
            'exists'  => $count > 0,
            'count'   => $count,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'account_id' => 'required|integer|exists:accounts,id',
            'tahun' => 'required|integer|min:2000|max:2100',
            'bulan' => 'required|integer|between:1,12',
            'jumlah' => 'required|numeric|min:0',
        ]);

        $existing = DB::table('ebudgeting')
            ->where('account_id', $request->account_id)
            ->where('tahun', $request->tahun)
            ->where('bulan', $request->bulan)
            ->first();

        if ($existing) {
            DB::table('ebudgeting')
                ->where('id', $existing->id)
                ->update(['jumlah' => $request->jumlah, 'updated_at' => now()]);
        } else {
            DB::table('ebudgeting')->insert([
                'account_id' => $request->account_id,
                'tahun' => $request->tahun,
                'bulan' => $request->bulan,
                'jumlah' => $request->jumlah,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'E-budgeting berhasil disimpan.',
        ]);
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'tahun'  => 'required|integer|min:2000|max:2100',
            'bulan'  => 'required|integer|between:1,12',
            'items'  => 'required|array|min:1',
            'items.*.account_id' => 'required|integer|exists:accounts,id',
            'items.*.jumlah'    => 'required|numeric|min:0',
        ]);

        $tahun = $request->tahun;
        $bulan = $request->bulan;
        $now   = now();

        $payload = collect($request->items)->map(fn ($i) => [
            'account_id' => $i['account_id'],
            'tahun'      => $tahun,
            'bulan'      => $bulan,
            'jumlah'     => $i['jumlah'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('ebudgeting')->upsert(
            $payload,
            ['account_id', 'tahun', 'bulan'],
            ['jumlah', 'updated_at']
        );

        return response()->json([
            'success' => true,
            'message' => 'Rencana anggaran berhasil disimpan.',
            'data'    => ['saved' => count($payload)],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'jumlah' => 'required|numeric|min:0',
        ]);

        DB::table('ebudgeting')
            ->where('id', $id)
            ->update(['jumlah' => $request->jumlah, 'updated_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'E-budgeting berhasil diupdate.',
        ]);
    }

    public function destroy($id)
    {
        DB::table('ebudgeting')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'E-budgeting berhasil dihapus.',
        ]);
    }
}