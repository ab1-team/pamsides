<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Ambil semua user (bisa filter role)
     */
    public function index(Request $request)
    {
        $query = User::with('jabatan:id,nama_jabatan');

        // Filter berdasarkan role (teknisi, surveyor)
        if ($request->has('role')) {
            $roles = explode(',', $request->role);
            $query->whereIn('role', $roles);
        }

        $users = $query->orderBy('name', 'ASC')->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Simpan user baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|string',
            'jabatan_id' => 'nullable|integer|exists:jabatans,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'jabatan_id' => $request->jabatan_id,
        ]);

        $user->load('jabatan:id,nama_jabatan');

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat',
            'data' => $user,
        ], 201);
    }

    /**
     * Detail user
     */
    public function show($id)
    {
        $user = User::with('jabatan:id,nama_jabatan')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'role' => 'sometimes|string',
            'jabatan_id' => 'nullable|integer|exists:jabatans,id',
        ]);

        $user->update($request->only([
            'name',
            'email',
            'role',
            'jabatan_id',
        ]));

        $user->load('jabatan:id,nama_jabatan');

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diupdate',
            'data' => $user,
        ]);
    }

    /**
     * Hapus user
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        return $this->safeDelete(
            fn () => $user->delete(),
            'USER_IN_USE',
            'Pengguna',
            $user->name,
        );
    }

    /**
     * Lookup penandatangan berdasarkan nama jabatan.
     * Dipakai oleh laporan/struk/surat_pengantar untuk ambil nama direktur, bendahara, dll.
     *
     * Contoh: GET /api/users/penandatangan?nama_jabatan=Direktur,Bendahara
     * Response: { "Direktur": { "id":..., "name":"Iswanto" }, "Bendahara": { ... } }
     */
    public function penandatangan(Request $request)
    {
        $names = $request->filled('nama_jabatan')
            ? array_map('trim', explode(',', $request->nama_jabatan))
            : ['Direktur', 'Bendahara'];

        $result = [];
        foreach ($names as $nama) {
            $u = User::findByJabatan($nama);
            $result[$nama] = $u ? [
                'id'            => $u->id,
                'name'          => $u->name,
                'email'         => $u->email,
                'jabatan_id'    => $u->jabatan_id,
                'nama_jabatan'  => $u->jabatan?->nama_jabatan,
            ] : null;
        }

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }
}
