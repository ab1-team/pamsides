<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SopController extends Controller
{
   
    //  GET /settings/sop
    public function index()
    {
        $rows = DB::table('settings')->get();

        $result = [];

        foreach ($rows as $row) {
            $value = json_decode($row->value, true);

            // khusus logo → jadi URL
        if ($row->key === 'logo') {
            if (isset($value['mainLogo'])) {
                $value['mainLogo_url'] = Storage::url($value['mainLogo']);
            }

            if (isset($value['dashboardLogo'])) {
                $value['dashboardLogo_url'] = Storage::url($value['dashboardLogo']);
            }

            if (isset($value['favicon'])) {
                $value['favicon_url'] = Storage::url($value['favicon']);
            }
        }

            $result[$row->key] = $value;
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    //  POST /lembaga
    public function updateLembaga(Request $request)
    {
        try {
            $this->saveSetting('lembaga', $request->all());

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    //  POST /pasang-baru
    public function updatePasangBaru(Request $request)
    {
        try {
            $this->saveSetting('pasang_baru', $request->all());

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    //  POST /sistem-tagihan
    public function updateSistemTagihan(Request $request)
    {
        try {
            $this->saveSetting('sistem_tagihan', $request->all());

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    //  POST /logo (multipart)
    public function updateLogo(Request $request)
    {
        try {
            $data = [];

            if ($request->hasFile('mainLogo')) {
                $data['mainLogo'] = $request->file('mainLogo')->store('sop/logo', 'public');
            }

            if ($request->hasFile('dashboardLogo')) {
                $data['dashboardLogo'] = $request->file('dashboardLogo')->store('sop/logo', 'public');
            }

            if ($request->hasFile('favicon')) {
                $data['favicon'] = $request->file('favicon')->store('sop/logo', 'public');
            }

            $this->mergeSetting('logo', $data);

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    //  POST /whatsapp
    public function updateWhatsapp(Request $request)
    {
        try {
            $this->saveSetting('whatsapp', $request->all());

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    // HELPER
    private function saveSetting($key, $value)
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($value)]
        );
    }

    private function mergeSetting($key, $newData)
    {
        $existing = DB::table('settings')->where('key', $key)->first();

        $old = $existing ? json_decode($existing->value, true) : [];

        $merged = array_merge($old, $newData);

        $this->saveSetting($key, $merged);
    }

    private function success($message = 'Berhasil disimpan')
    {
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    private function error($e)
    {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
