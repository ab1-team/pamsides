<?php

namespace App\Http\Controllers;

use App\Models\InstallationPackage;
use App\Models\WaterTariffBlock;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WaterTariffBlockController extends Controller
{
    public function index(InstallationPackage $installationPackage)
    {
        return response()->json([
            'success' => true,
            'data'    => $installationPackage->waterTariffBlocks,
        ]);
    }

    public function store(Request $request, InstallationPackage $installationPackage)
    {
        $request->validate([
            'usage_min_m3' => 'required|integer|min:0',
            'usage_max_m3' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value !== null && $value <= $request->usage_min_m3) {
                        $fail('usage_max_m3 harus lebih besar dari usage_min_m3.');
                    }
                },
            ],
            'price_per_m3' => 'required|numeric|min:0',
        ]);

        $this->checkOverlap(
            $installationPackage,
            $request->usage_min_m3,
            $request->usage_max_m3
        );

        $block = $installationPackage->waterTariffBlocks()->create($request->only([
            'usage_min_m3',
            'usage_max_m3',
            'price_per_m3',
        ]));

        return response()->json([
            'success' => true,
            'data'    => $block,
        ], 201);
    }

    public function show(InstallationPackage $installationPackage, WaterTariffBlock $waterTariffBlock)
    {
        return response()->json([
            'success' => true,
            'data'    => $waterTariffBlock,
        ]);
    }

    public function update(Request $request, InstallationPackage $installationPackage, WaterTariffBlock $waterTariffBlock)
    {
        $request->validate([
            'usage_min_m3' => 'sometimes|integer|min:0',
            'usage_max_m3' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($request, $waterTariffBlock) {
                    $min = $request->has('usage_min_m3')
                        ? $request->usage_min_m3
                        : $waterTariffBlock->usage_min_m3;

                    if ($value !== null && $value <= $min) {
                        $fail('usage_max_m3 harus lebih besar dari usage_min_m3.');
                    }
                },
            ],
            'price_per_m3' => 'sometimes|numeric|min:0',
        ]);

        $min = $request->has('usage_min_m3') ? $request->usage_min_m3 : $waterTariffBlock->usage_min_m3;
        $max = $request->has('usage_max_m3') ? $request->usage_max_m3 : $waterTariffBlock->usage_max_m3;

        $this->checkOverlap($installationPackage, $min, $max, $waterTariffBlock->id);

        $waterTariffBlock->update($request->only([
            'usage_min_m3',
            'usage_max_m3',
            'price_per_m3',
        ]));

        return response()->json([
            'success' => true,
            'data'    => $waterTariffBlock,
        ]);
    }

    public function destroy(InstallationPackage $installationPackage, WaterTariffBlock $waterTariffBlock)
    {
        $waterTariffBlock->delete();

        return response()->json([
            'success' => true,
            'data'    => ['message' => 'Blok tarif berhasil dihapus.'],
        ]);
    }

    private function checkOverlap(InstallationPackage $installationPackage, int $min, ?int $max, ?int $excludeId = null): void
    {
        $query = $installationPackage->waterTariffBlocks()
            ->where(function ($q) use ($min, $max) {
                $q->where(function ($q) use ($min, $max) {
                    // Blok existing yang usage_max_nya null (tidak terbatas) — selalu overlap jika min baru masuk di atasnya
                    $q->whereNull('usage_max_m3')
                      ->where('usage_min_m3', '<=', $min);
                })->orWhere(function ($q) use ($min, $max) {
                    // Overlap biasa — range baru berpotongan dengan range existing
                    $q->whereNotNull('usage_max_m3')
                      ->where('usage_min_m3', '<=', $max ?? PHP_INT_MAX)
                      ->where('usage_max_m3', '>=', $min);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'usage_min_m3' => ['Range pemakaian overlap dengan blok tarif yang sudah ada.'],
            ]);
        }
    }
}
