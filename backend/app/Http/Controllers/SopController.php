<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SopController extends Controller
{
    public function index()
    {
        $rows = DB::table('settings')->get();

        $result = [];

        foreach ($rows as $row) {
            $value = json_decode($row->value, true);

            if ($row->key === 'logo' && is_array($value)) {
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
            'data'    => $result,
        ]);
    }

    public function updateLembaga(Request $request)
    {
        try {
            $this->saveSetting('lembaga', $request->all());
            return $this->success('Profil lembaga berhasil disimpan');
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    public function updatePasangBaru(Request $request)
    {
        try {
            $this->saveSetting('pasang_baru', $request->all());
            return $this->success('Aturan pasang baru berhasil disimpan');
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    public function updateSistemTagihan(Request $request)
    {
        try {
            $this->saveSetting('sistem_tagihan', $request->all());
            return $this->success('Sistem tagihan berhasil disimpan');
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    public function updateLogo(Request $request)
    {
        try {
            $data = [];

            if ($request->hasFile('mainLogo')) {
                $this->deleteOld('mainLogo', 'logo', 'sop/logo');
                $data['mainLogo'] = $request->file('mainLogo')->store('sop/logo', 'public');
            }

            if ($request->hasFile('dashboardLogo')) {
                $this->deleteOld('dashboardLogo', 'logo', 'sop/logo');
                $data['dashboardLogo'] = $request->file('dashboardLogo')->store('sop/logo', 'public');
            }

            if ($request->hasFile('favicon')) {
                $this->deleteOld('favicon', 'logo', 'sop/logo');
                $data['favicon'] = $request->file('favicon')->store('sop/logo', 'public');
            }

            $this->mergeSetting('logo', $data);

            $existing = DB::table('settings')->where('key', 'logo')->first();
            $merged = $existing ? json_decode($existing->value, true) : [];
            $merged = array_merge($merged, $data);

            return response()->json([
                'success' => true,
                'message' => 'Logo & branding berhasil disimpan',
                'data'    => [
                    'mainLogo'         => $merged['mainLogo'] ?? null,
                    'mainLogo_url'     => isset($merged['mainLogo']) ? Storage::url($merged['mainLogo']) : null,
                    'dashboardLogo'    => $merged['dashboardLogo'] ?? null,
                    'dashboardLogo_url'=> isset($merged['dashboardLogo']) ? Storage::url($merged['dashboardLogo']) : null,
                    'favicon'          => $merged['favicon'] ?? null,
                    'favicon_url'      => isset($merged['favicon']) ? Storage::url($merged['favicon']) : null,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    public function updateWhatsapp(Request $request)
    {
        try {
            $this->saveSetting('whatsapp', $request->all());
            return $this->success('Template WhatsApp berhasil disimpan');
        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    private function deleteOld(string $field, string $key, string $folder): void
    {
        $existing = DB::table('settings')->where('key', $key)->first();
        if (! $existing) {
            return;
        }

        $old = json_decode($existing->value, true);
        if (! empty($old[$field]) && Storage::disk('public')->exists($old[$field])) {
            Storage::disk('public')->delete($old[$field]);
        }
    }

    private function saveSetting($key, $value)
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()]
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
            'message' => $message,
        ]);
    }

    private function error($e)
    {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}
