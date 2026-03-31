<?php

namespace App\Http\Controllers;

use App\Models\InstallationPackage;
use Illuminate\Http\Request;

class InstallationPackageController extends Controller
{
    public function index()
    {
        $packages = InstallationPackage::with('waterTariffBlocks')->get();

        return response()->json([
            'success' => true,
            'data'    => $packages,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255|unique:installation_packages',
            'installation_fee' => 'required|numeric|min:0',
            'monthly_abodemen' => 'required|numeric|min:0',
            'late_penalty'     => 'required|numeric|min:0',
        ]);

        $package = InstallationPackage::create($request->only([
            'name',
            'installation_fee',
            'monthly_abodemen',
            'late_penalty',
        ]));

        return response()->json([
            'success' => true,
            'data'    => $package,
        ], 201);
    }

    public function show(InstallationPackage $installationPackage)
    {
        return response()->json([
            'success' => true,
            'data'    => $installationPackage->load('waterTariffBlocks'),
        ]);
    }

    public function update(Request $request, InstallationPackage $installationPackage)
    {
        $request->validate([
            'name'             => 'sometimes|string|max:255|unique:installation_packages,name,' . $installationPackage->id,
            'installation_fee' => 'sometimes|numeric|min:0',
            'monthly_abodemen' => 'sometimes|numeric|min:0',
            'late_penalty'     => 'sometimes|numeric|min:0',
        ]);

        $installationPackage->update($request->only([
            'name',
            'installation_fee',
            'monthly_abodemen',
            'late_penalty',
        ]));

        return response()->json([
            'success' => true,
            'data'    => $installationPackage,
        ]);
    }

    public function destroy(InstallationPackage $installationPackage)
    {
        $installationPackage->delete();

        return response()->json([
            'success' => true,
            'data'    => ['message' => 'Paket instalasi berhasil dihapus.'],
        ]);
    }
}
