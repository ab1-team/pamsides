<?php

namespace App\Http\Controllers;

use App\Models\Calk;
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
                'peraturan_desa' => $s?->peraturan_desa ?? '',
                'sk_kemenkumham' => $s?->sk_kemenkumham ?? '',
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
            'calk' => $this->buildCalkPayload($s),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Identitas lembaga yang boleh dilihat semua role login.
     *
     * Dipakai oleh chrome bersama (judul sidebar, kop surat/cetak) yang
     * dirender untuk admin, teknisi, surveyor, dan pelanggan. Sengaja hanya
     * mengembalikan nama & logo: data kontak lengkap (email, telepon,
     * alamat, SK) tetap khusus admin lewat `index()` di atas.
     */
    public function publicIdentity()
    {
        $s = Setting::first();

        return response()->json([
            'success' => true,
            'data' => [
                'nama' => $s?->nama ?? '',
                'logo' => $s?->logo ?? null,
            ],
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
            'peraturan_desa' => 'nullable|string|max:100',
            'sk_kemenkumham' => 'nullable|string|max:100',
        ]);

            $s = Setting::firstOrNew([]);
            $s->key = $s->key ?: 'sop';
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
            $s->key = $s->key ?: 'sop';
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
            $s->key = $s->key ?: 'sop';
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
            $s->key = $s->key ?: 'sop';

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
            $s->key = $s->key ?: 'sop';
            $s->pesan_tagihan = $data['templateTagihan'] ?? null;
            $s->pesan_pembayaran = $data['templatePembayaran'] ?? null;
            $s->save();

            return $this->success('Template WhatsApp berhasil disimpan');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * ============================================================
     * CALK (Catatan Atas Laporan Keuangan)
     * ============================================================
     *
     * Konsep diadaptasi dari `app/Models/Calk.php` & `resources/views/sop/partials/_calk.blade.php`
     * di aplikasi sidbm. Format JSON di field `settings.calk` mengikuti struktur:
     *
     *   {
     *     "peraturan_desa": "01 TAHUN 2022",
     *     "D": {
     *       "1": { "d": { "1": 25, "2": 25, "3": 50 } },
     *       "2": { "a": 0, "b": 0, "c": 0 }
     *     }
     *   }
     *
     * Point A ("Gambaran Umum") custom tersimpan di field `settings.calk_point_a` (HTML).
     * Catatan tambahan per periode ("Lain-lain") tetap memakai tabel `calks`
     * (lihat method `getCalkCatatan` / `updateCalkCatatan`).
     */

    /**
     * Bangun struktur payload CALK default dari settings saat ini.
     * Mengikuti struktur JSON yang dipakai sidbm.
     */
    private function buildCalkPayload(?Setting $s): array
    {
        $calk = $s?->calk ?? [];

        return [
            'peraturan_desa' => $calk['peraturan_desa'] ?? ($s?->peraturan_desa ?? ''),
            'D' => [
                '1' => [
                    'd' => [
                        '1' => (int) ($calk['D']['1']['d']['1'] ?? 0), // bantuan rumah tangga %
                        '2' => (int) ($calk['D']['1']['d']['2'] ?? 0), // pengembangan kapasitas %
                        '3' => (int) ($calk['D']['1']['d']['3'] ?? 0), // pelatihan masyarakat %
                    ],
                ],
                '2' => [
                    'a' => (float) ($calk['D']['2']['a'] ?? 0), // peningkatan modal DBM
                    'b' => (float) ($calk['D']['2']['b'] ?? 0), // penambahan investasi usaha
                    'c' => (float) ($calk['D']['2']['c'] ?? 0), // pendirian unit usaha
                ],
            ],
            'point_a' => $s?->calk_point_a ?? '',
        ];
    }

    /**
     * GET /settings/sop/calk
     * Ambil konfigurasi CALK (persentase bagian, laba ditahan, Point A).
     */
    public function getCalk()
    {
        $s = Setting::first();

        return response()->json([
            'success' => true,
            'data' => $this->buildCalkPayload($s),
        ]);
    }

    /**
     * POST /settings/sop/calk
     * Simpan konfigurasi CALK (persentase bagian, laba ditahan).
     *
     * Payload:
     *   peraturan_desa: string
     *   D.1.d.1: int (%)
     *   D.1.d.2: int (%)
     *   D.1.d.3: int (%)
     *   D.2.a: numeric (peningkatan modal DBM)
     *   D.2.b: numeric (penambahan investasi usaha)
     *   D.2.c: numeric (pendirian unit usaha)
     */
    public function updateCalk(Request $request)
    {
        try {
            $data = $request->validate([
                'peraturan_desa' => 'nullable|string|max:100',
                'bantuan_rumah_tangga' => 'required|numeric|min:0|max:100',
                'pengembangan_kapasitas' => 'required|numeric|min:0|max:100',
                'pelatihan_masyarakat' => 'required|numeric|min:0|max:100',
                'peningkatan_modal' => 'required|numeric|min:0',
                'penambahan_investasi' => 'required|numeric|min:0',
                'pendirian_unit_usaha' => 'required|numeric|min:0',
            ], [
                'bantuan_rumah_tangga.required' => 'Persentase bantuan rumah tangga wajib diisi.',
                'pengembangan_kapasitas.required' => 'Persentase pengembangan kapasitas wajib diisi.',
                'pelatihan_masyarakat.required' => 'Persentase pelatihan masyarakat wajib diisi.',
                'peningkatan_modal.required' => 'Nominal peningkatan modal wajib diisi.',
                'penambahan_investasi.required' => 'Nominal penambahan investasi wajib diisi.',
                'pendirian_unit_usaha.required' => 'Nominal pendirian unit usaha wajib diisi.',
            ]);

            // Konversi ke struktur sidbm
            $calk = [
                'peraturan_desa' => $data['peraturan_desa'] ?? '',
                'D' => [
                    '1' => [
                        'd' => [
                            '1' => (float) $data['bantuan_rumah_tangga'],
                            '2' => (float) $data['pengembangan_kapasitas'],
                            '3' => (float) $data['pelatihan_masyarakat'],
                        ],
                    ],
                    '2' => [
                        'a' => (float) $data['peningkatan_modal'],
                        'b' => (float) $data['penambahan_investasi'],
                        'c' => (float) $data['pendirian_unit_usaha'],
                    ],
                ],
            ];

            $s = Setting::firstOrNew([]);
            $s->key = $s->key ?: 'sop';
            $s->calk = $calk;
            // Samakan dengan kolom peraturan_desa supaya konsisten di view lembaga.
            if (! empty($data['peraturan_desa'])) {
                $s->peraturan_desa = $data['peraturan_desa'];
            }
            $s->save();

            return $this->success('Pengaturan CALK Berhasil Diperbarui.');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * GET /settings/sop/custom-calk
     * Ambil Point A (Gambaran Umum) kustom CALK.
     */
    public function getCustomCalk()
    {
        $s = Setting::first();

        return response()->json([
            'success' => true,
            'data' => [
                'point_a' => $s?->calk_point_a ?? '',
            ],
        ]);
    }

    /**
     * POST /settings/sop/custom-calk
     * Simpan Point A (Gambaran Umum) kustom CALK.
     *
     * Payload: { point_a: string (HTML) }
     */
    public function updateCustomCalk(Request $request)
    {
        try {
            $data = $request->validate([
                'point_a' => 'nullable|string',
            ], [
                'point_a.string' => 'Konten Point A harus berupa teks/HTML.',
            ]);

            $s = Setting::firstOrNew([]);
            $s->key = $s->key ?: 'sop';
            $s->calk_point_a = $data['point_a'] ?? '';
            $s->save();

            return $this->success('Custom CALK Berhasil Diperbarui.');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * GET /settings/sop/calk-catatan?tanggal=YYYY-MM-DD
     * Ambil catatan "Lain-lain" CALK untuk tanggal tertentu.
     * Mengikuti konsep `App\Models\Calk` di sidbm (lokasi + tanggal + catatan).
     */
    public function getCalkCatatan(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date_format:Y-m-d',
        ]);

        $calk = Calk::where('tanggal', $request->tanggal)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'tanggal' => $request->tanggal,
                'catatan' => $calk?->catatan ?? '',
            ],
        ]);
    }

    /**
     * POST /settings/sop/calk-catatan
     * Simpan catatan "Lain-lain" CALK untuk tanggal tertentu.
     *
     * Payload: { tanggal: YYYY-MM-DD, catatan: string (HTML) }
     */
    public function updateCalkCatatan(Request $request)
    {
        try {
            $data = $request->validate([
                'tanggal' => 'required|date_format:Y-m-d',
                'catatan' => 'nullable|string',
            ]);

            // Hapus catatan lama untuk tanggal tsb, lalu insert baru.
            // Sama pola dengan `PelaporanController::preview` di sidbm
            // (delete where lokasi+tanggal LIKE, lalu create).
            Calk::where('tanggal', $data['tanggal'])->delete();

            if (! empty($data['catatan'])) {
                Calk::create([
                    'tanggal' => $data['tanggal'],
                    'catatan' => $data['catatan'],
                ]);
            }

            return $this->success('Catatan CALK Berhasil Diperbarui.');
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
