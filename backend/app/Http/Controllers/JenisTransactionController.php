<?php

namespace App\Http\Controllers;

use App\Models\JenisTransaction;
use Illuminate\Http\Request;

class JenisTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = JenisTransaction::query();

        if ($request->has('search') && $request->search) {
            $query->where('nama_jt', 'like', '%' . $request->search . '%');
        }

        $items = $query->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_jt' => 'required|string|max:100|unique:jenis_transactions,nama_jt',
        ], [
            'nama_jt.required' => 'Nama jenis transaksi wajib diisi.',
            'nama_jt.unique'   => 'Nama jenis transaksi sudah ada.',
        ]);

        $item = JenisTransaction::create([
            'nama_jt' => $request->nama_jt,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jenis transaksi berhasil ditambahkan.',
            'data'    => $item,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $item = JenisTransaction::findOrFail($id);

        $request->validate([
            'nama_jt' => 'required|string|max:100|unique:jenis_transactions,nama_jt,' . $id,
        ]);

        $item->update(['nama_jt' => $request->nama_jt]);

        return response()->json([
            'success' => true,
            'message' => 'Jenis transaksi berhasil diperbarui.',
            'data'    => $item,
        ]);
    }

    public function destroy($id)
    {
        $item = JenisTransaction::findOrFail($id);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jenis transaksi berhasil dihapus.',
        ]);
    }
}
