<?php

namespace App\Http\Controllers;

use App\Models\Account;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::select('id', 'kode_akun', 'nama_akun', 'lev1', 'lev2', 'lev3', 'lev4', 'parent_id')
            ->orderBy('kode_akun')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }
}