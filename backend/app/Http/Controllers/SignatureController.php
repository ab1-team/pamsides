<?php

namespace App\Http\Controllers;

use App\Models\SignatureSetting;
use App\Services\SignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class SignatureController extends Controller
{
    public function __construct(
        private readonly SignatureService $signatures,
    ) {}

    /**
     * GET /api/settings/signatures
     * Return semua template + URL image per report key.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'templates' => $this->signatures->templates(),
                'images'    => $this->buildImageUrlMap(),
                'report_types' => SignatureSetting::reportTypes(),
                'starter_html' => SignatureSetting::starterHtml(),
            ],
        ]);
    }

    /**
     * PUT /api/settings/signatures
     * Simpan template HTML per report key.
     */
    public function updateTemplates(Request $request): JsonResponse
    {
        $data = $request->validate([
            'templates'   => 'required|array',
            'templates.*' => 'nullable|string',
        ]);

        $this->signatures->saveTemplates($data['templates']);

        return response()->json([
            'success' => true,
            'message' => 'Template tanda tangan berhasil disimpan.',
            'data' => [
                'templates' => $this->signatures->templates(),
            ],
        ]);
    }

    /**
     * POST /api/settings/signatures/image
     * Upload gambar tanda tangan (data URI base64).
     */
    public function storeImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'report_key' => [
                'required',
                'string',
                'in:'.implode(',', array_keys(SignatureSetting::REPORT_TYPES)),
            ],
            'image' => [
                'required',
                'string',
                'regex:/^data:image\/(png|jpeg|jpg|webp);base64,/i',
            ],
        ], [
            'report_key.required' => 'Jenis laporan wajib dipilih.',
            'report_key.in'       => 'Jenis laporan tidak dikenal.',
            'image.required'      => 'Gambar tanda tangan wajib diisi.',
            'image.regex'         => 'Format gambar harus data URI PNG/JPG/WebP base64.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $path = $this->signatures->storeImage(
                $request->input('report_key'),
                $request->input('image'),
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tanda tangan gambar berhasil disimpan.',
            'data' => [
                'image_url' => asset('storage/'.$path),
            ],
        ]);
    }

    /**
     * DELETE /api/settings/signatures/image
     * Hapus tanda tangan gambar per report key.
     */
    public function destroyImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'report_key' => [
                'required',
                'string',
                'in:'.implode(',', array_keys(SignatureSetting::REPORT_TYPES)),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $this->signatures->deleteImage($request->input('report_key'));
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tanda tangan gambar berhasil dihapus.',
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function buildImageUrlMap(): array
    {
        $paths = $this->signatures->imagePaths();
        $out = [];
        foreach ($paths as $key => $path) {
            $out[$key] = $path ? asset('storage/'.$path) : null;
        }

        return $out;
    }
}