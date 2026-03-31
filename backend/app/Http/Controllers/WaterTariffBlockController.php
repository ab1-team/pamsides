<?php

namespace App\Http\Controllers;

use App\Models\InstallationPackage;
use App\Models\WaterTariffBlock;
use Illuminate\Http\Request;

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
}
