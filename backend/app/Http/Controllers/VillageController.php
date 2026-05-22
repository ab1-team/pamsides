<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Village;

class VillageController extends Controller
{
    /**
     * List data village
     */
    public function index()
    {
        $villages = Village::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $villages
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'village_name' => 'required',
            'hamlet_name' => 'required',
        ]);

        $village = Village::create([
            'village_name' => $request->village_name,
            'hamlet_name' => $request->hamlet_name,
            'address'      => $request->address,
            'phone'        => $request->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Village berhasil ditambahkan',
            'data' => $village
        ]);
    }
    public function show($id)
    {
        $village = Village::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $village
        ]);
    }

    /**
     * Update village
     */
    public function update(Request $request, $id)
    {
        $village = Village::findOrFail($id);

        $request->validate([
            'village_name' => 'required',
            'hamlet_name' => 'required',
        ]);

        $village->update([
            'village_name' => $request->village_name,
            'hamlet_name' => $request->hamlet_name,
            'address' => $request->address,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Village berhasil diupdate',
            'data' => $village
        ]);
    }

    /**
     * Delete village
     */
    public function destroy($id)
    {
        $village = Village::findOrFail($id);

        $village->delete();

        return response()->json([
            'success' => true,
            'message' => 'Village berhasil dihapus'
        ]);
    }
}
