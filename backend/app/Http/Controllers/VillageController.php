<?php

namespace App\Http\Controllers;

use App\Models\Village;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

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
            'data' => $villages,
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
            'address' => $request->address,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Village berhasil ditambahkan',
            'data' => $village,
        ]);
    }

    public function show($id)
    {
        $village = Village::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $village,
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
            'data' => $village,
        ]);
    }

    /**
     * Delete village
     */
    public function destroy($id)
    {
        $village = Village::findOrFail($id);

        try {
            $village->delete();
        } catch (QueryException $e) {
            if ($this->isForeignKeyViolation($e)) {
                $usage = $this->detectVillageUsage($e->getMessage());

                return response()->json([
                    'success' => false,
                    'code' => 'VILLAGE_IN_USE',
                    'message' => "Desa \"{$village->village_name}\" tidak dapat dihapus karena masih digunakan pada data lain" . ($usage ? " ({$usage})." : '.'),
                    'usage' => $usage,
                ], 409);
            }

            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Village berhasil dihapus',
        ]);
    }

    /**
     * Deteksi pelanggaran foreign key (SQLSTATE 23000 / errno 1451 untuk MySQL).
     */
    protected function isForeignKeyViolation(QueryException $e): bool
    {
        if ($e->getCode() === '23000') {
            return true;
        }

        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;

        if ($sqlState === '23000' && in_array((int) $driverCode, [1451, 1452, 1217, 1216], true)) {
            return true;
        }

        return str_contains(strtolower($e->getMessage()), 'foreign key constraint');
    }

    /**
     * Coba deteksi tabel referensi dari pesan error SQL.
     */
    protected function detectVillageUsage(string $message): ?string
    {
        $haystack = strtolower($message);

        $candidates = [
            'installation_tickets' => 'tiket instalasi',
            'customers' => 'pelanggan',
            'customer' => 'pelanggan',
            'users' => 'pengguna',
            'meters' => 'meteran',
            'usages' => 'pemakaian air',
            'bills' => 'tagihan',
            'village_id' => 'data lain',
        ];

        foreach ($candidates as $needle => $label) {
            if (str_contains($haystack, $needle)) {
                return $label;
            }
        }

        return null;
    }
}
