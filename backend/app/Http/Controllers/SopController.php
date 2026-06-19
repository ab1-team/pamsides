<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SopController extends Controller
{
    public function index()
    {
        $s = Setting::first();

        $data = [
            'lembaga' => [
                'nama' => $s?->nama ?? '',
                'alamat' => $s?->alamat ?? '',
                'email' => $s?->email ?? '',
                'telepon' => $s?->telepon ?? '',
                'domain' => $s?->domain ?? '',
            ],
            'sistemTagihan' => [
                'batasTagihan' => $s?->batas_tagihan ?? 27,
                'toleransiTunggakan' => $s?->toleransi_tunggakan ?? 0,
            ],
            'pasangBaru' => [
                'statusPembayaran' => (bool) ($s?->status_pembayaran ?? false),
            ],
            'logo' => [
                'logo' => $s?->logo ?? null,
            ],
            'whatsapp' => [
                'templateTagihan' => $s?->pesan_tagihan ?? '',
                'templatePembayaran' => $s?->pesan_pembayaran ?? '',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function updateLembaga(Request $request)
    {
        try {
            $data = $request->validate([
                'nama' => 'nullable|string|max:150',
                'alamat' => 'nullable|string',
                'email' => 'nullable|email|max:150',
                'telepon' => 'nullable|string|max:30',
                'domain' => 'nullable|string|max:255',
            ]);

            $s = Setting::firstOrNew([]);
            $s->fill($data);
            $s->save();

            return $this->success('Profil lembaga berhasil disimpan');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    public function updatePasangBaru(Request $request)
    {
        try {
            $data = $request->validate([
                'statusPembayaran' => 'required|boolean',
            ]);

            $s = Setting::firstOrNew([]);
            $s->status_pembayaran = (bool) $data['statusPembayaran'];
            $s->save();

            return $this->success('Aturan pasang baru berhasil disimpan');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    public function updateSistemTagihan(Request $request)
    {
        try {
            $data = $request->validate([
                'batasTagihan' => 'required|integer|min:1|max:28',
                'toleransiTunggakan' => 'required|integer|min:0|max:120',
            ]);

            $s = Setting::firstOrNew([]);
            $s->batas_tagihan = (int) $data['batasTagihan'];
            $s->toleransi_tunggakan = (int) $data['toleransiTunggakan'];
            $s->save();

            return $this->success('Sistem tagihan berhasil disimpan');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    public function updateLogo(Request $request)
    {
        try {
            $request->validate([
                'logo' => 'required|file|image|max:2048',
            ]);

            $s = Setting::firstOrNew([]);

            if ($s->logo) {
                $oldPath = 'sop/logo/'.$s->logo;
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $file = $request->file('logo');
            $fileName = time().'_'.$file->getClientOriginalName();
            $file->storeAs('sop/logo', $fileName, 'public');

            $s->logo = $fileName;
            $s->save();

            return response()->json([
                'success' => true,
                'message' => 'Logo berhasil disimpan',
                'data' => [
                    'logo' => $fileName,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    public function updateWhatsapp(Request $request)
    {
        try {
            $data = $request->validate([
                'templateTagihan' => 'nullable|string',
                'templatePembayaran' => 'nullable|string',
            ]);

            $s = Setting::firstOrNew([]);
            $s->pesan_tagihan = $data['templateTagihan'] ?? null;
            $s->pesan_pembayaran = $data['templatePembayaran'] ?? null;
            $s->save();

            return $this->success('Template WhatsApp berhasil disimpan');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
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
        $payload = [
            'success' => false,
            'message' => $e->getMessage(),
        ];
        if ($e instanceof ValidationException) {
            $payload['errors'] = $e->errors();

            return response()->json($payload, 422);
        }

        return response()->json($payload, 500);
    }
}
