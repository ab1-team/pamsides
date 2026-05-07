<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    // Ambil daftar nama kecamatan
    public function getKecamatan()
    {
        $kecamatan = DB::table('settings')
            ->where('key', 'kecamatan')
            ->pluck('value');

        return response()->json([
            'success' => true,
            'data' => $kecamatan
        ]);
    }

  
    public function getDesa(Request $request)
    {
        $kecamatanName = strtolower($request->kecamatan);
        
        $desa = DB::table('settings')
            ->where('key', 'desa_' . $kecamatanName)
            ->pluck('value');

        return response()->json([
            'success' => true,
            'data' => $desa
        ]);
    }
}
