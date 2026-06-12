<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Ambil semua user (bisa filter role)
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter berdasarkan role (teknisi, surveyor)
        if ($request->has('role')) {
            $roles = explode(',', $request->role);
            $query->whereIn('role', $roles);
        }

        $users = $query->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Simpan user baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role'     => 'required|string'
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
            'role'     => $request->role
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat',
            'data'    => $user
        ], 201);
    }

    /**
     * Detail user
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->update($request->only([
            'name',
            'email',
            'role'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diupdate',
            'data' => $user
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
}
