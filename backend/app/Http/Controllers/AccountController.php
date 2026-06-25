<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AkunLevel1;
use App\Models\AkunLevel2;
use App\Models\AkunLevel3;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $kategori = $request->query('kategori');
        $lev1Filter = null;
        if ($kategori && $kategori !== 'all') {
            $map = [
                'aset' => 1,
                'utang' => 2,
                'modal' => 3,
                'pendapatan' => 4,
                'beban' => 5,
            ];
            $lev1Filter = $map[$kategori] ?? null;
        }

        $level1 = AkunLevel1::select('id', 'kode_akun', 'nama_akun', 'jenis_mutasi')
            ->when($lev1Filter, fn ($q) => $q->where('lev1', $lev1Filter))
            ->orderBy('kode_akun')
            ->get();

        $level2 = AkunLevel2::select('id', 'parent_id', 'kode_akun', 'nama_akun', 'jenis_mutasi')
            ->when($lev1Filter, fn ($q) => $q->where('lev1', $lev1Filter))
            ->orderBy('kode_akun')
            ->get();

        $level3 = AkunLevel3::select('id', 'parent_id', 'kode_akun', 'nama_akun', 'jenis_mutasi')
            ->when($lev1Filter, fn ($q) => $q->where('lev1', $lev1Filter))
            ->orderBy('kode_akun')
            ->get();

        $accounts = Account::select('id', 'parent_id', 'kode_akun', 'nama_akun', 'jenis_mutasi')
            ->when($lev1Filter, fn ($q) => $q->where('lev1', $lev1Filter))
            ->orderBy('kode_akun')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'level1' => $level1,
                'level2' => $level2,
                'level3' => $level3,
                'accounts' => $accounts,
            ],
        ]);
    }

    public function byLevel($level)
    {
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Level tidak valid. Gunakan 1, 2, atau 3.',
            ], 422);
        }

        if ($level === 1) {
            $data = AkunLevel1::select('id', 'lev1', 'kode_akun', 'nama_akun', 'jenis_mutasi')
                ->orderBy('kode_akun')
                ->get();
        } elseif ($level === 2) {
            $data = AkunLevel2::with('parent:id,nama_akun,kode_akun')
                ->select('id', 'parent_id', 'lev1', 'lev2', 'kode_akun', 'nama_akun', 'jenis_mutasi')
                ->orderBy('kode_akun')
                ->get()
                ->map(function ($row) {
                    $row->parent_nama = $row->parent?->nama_akun;
                    $row->parent_kode = $row->parent?->kode_akun;
                    unset($row->parent);
                    return $row;
                });
        } else {
            $data = AkunLevel3::with('parent:id,nama_akun,kode_akun')
                ->select('id', 'parent_id', 'lev1', 'lev2', 'lev3', 'kode_akun', 'nama_akun', 'posisi', 'jenis_mutasi')
                ->orderBy('kode_akun')
                ->get()
                ->map(function ($row) {
                    $row->parent_nama = $row->parent?->nama_akun;
                    $row->parent_kode = $row->parent?->kode_akun;
                    unset($row->parent);
                    return $row;
                });
        }

        return response()->json([
            'success' => true,
            'level' => $level,
            'data' => $data,
        ]);
    }
}