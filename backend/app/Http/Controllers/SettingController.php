<?php

namespace App\Http\Controllers;

use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function getKecamatan()
    {
        $rows = DB::table('settings')
            ->whereIn('key', ['kecamatan', 'kecamatan_list'])
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $decoded = json_decode($row->value, true);
            if (is_array($decoded)) {
                foreach ($decoded as $v) {
                    if (is_string($v)) {
                        $items[] = $v;
                    } elseif (is_array($v) && isset($v['name'])) {
                        $items[] = $v['name'];
                    }
                }
            }
        }

        if (empty($items)) {
            $items = [
                'Temon', 'Wates', 'Panjatan', 'Galur', 'Lendah', 'Sentolo',
                'Pengasih', 'Kokap', 'Girimulyo', 'Nanggulan', 'Samigaluh',
                'Kalibawang', 'Srandakan', 'Bambanglipuro', 'Pandak', 'Bantul',
                'Jetis', 'Imogiri', 'Dlingo', 'Banguntapan', 'Pleret',
                'Piyungan', 'Sewon', 'Kasihan', 'Sedayu',
            ];
        }

        return response()->json([
            'success' => true,
            'data' => array_values(array_unique($items)),
        ]);
    }

    public function getDesa(Request $request)
    {
        $query = Village::query();

        if ($request->has('search') && $request->search) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('village_name', 'like', "%{$q}%")
                    ->orWhere('hamlet_name', 'like', "%{$q}%");
            });
        }

        $villages = $query->orderBy('village_name')
            ->limit(100)
            ->get(['id', 'village_name', 'hamlet_name', 'address', 'phone']);

        return response()->json([
            'success' => true,
            'data' => $villages,
        ]);
    }
}
