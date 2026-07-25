<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventory::orderBy('tgl_beli', 'desc');

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $query->where('nama_barang', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('tgl_dari')) {
            $query->whereDate('tgl_beli', '>=', $request->tgl_dari);
        }
        if ($request->filled('tgl_sampai')) {
            $query->whereDate('tgl_beli', '<=', $request->tgl_sampai);
        }

        $perPage = (int) ($request->per_page ?? 20);
        $items = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(Inventory $inventory)
    {
        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'tgl_beli' => 'required|date',
            'unit' => 'required|integer|min:1',
            'harsat' => 'required|numeric|min:0',
            'umur_ekonomis' => 'required|integer|min:0',
            'jenis' => 'required|string|max:10',
            'kategori' => 'required|string|max:10',
            'status' => 'nullable|string|max:20',
            'tgl_validasi' => 'nullable|date',
        ]);

        $data['status'] = $data['status'] ?? 'Baik';

        $inventory = Inventory::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Inventaris berhasil disimpan.',
            'data' => $inventory,
        ], 201);
    }

    public function update(Request $request, Inventory $inventory)
    {
        $data = $request->validate([
            'nama_barang' => 'sometimes|string|max:255',
            'tgl_beli' => 'sometimes|date',
            'unit' => 'sometimes|integer|min:1',
            'harsat' => 'sometimes|numeric|min:0',
            'umur_ekonomis' => 'sometimes|integer|min:0',
            'jenis' => 'sometimes|string|max:10',
            'kategori' => 'sometimes|string|max:10',
            'status' => 'sometimes|string|max:20',
            'tgl_validasi' => 'nullable|date',
        ]);

        $inventory->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Inventaris berhasil diperbarui.',
            'data' => $inventory,
        ]);
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inventaris berhasil dihapus.',
        ]);
    }
}
