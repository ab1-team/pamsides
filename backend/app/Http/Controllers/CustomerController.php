<?php

namespace App\Http\Controllers;
use App\Models\Customer;

use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Customer::with('user');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('user', function ($q2) use ($request) {
                    $q2->where('name', 'like', '%' . $request->search . '%');
                })
                ->orWhere('customer_code', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()->map(function ($c) {
                return [
                    'id' => $c->id,
                    'customer_code' => $c->customer_code,
                    'name' => $c->user->name,
                    'address' => optional($c->ticket)->address ?? '-',
                    'status' => $c->activated_at ? 'Aktif' : 'Belum Terdaftar'
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
