<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Customer;
use App\Models\InstallationTicket;
use App\StateMachines\TicketStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstallationResultController extends Controller
{
    public function store(Request $request, InstallationTicket $installationTicket)
    {
        $request->validate([
            'initial_meter_reading' => 'required|numeric|min:0',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'initial_meter_reading.required' => 'Angka meter awal wajib diisi.',
            'initial_meter_reading.numeric' => 'Angka meter awal harus berupa angka.',
            'initial_meter_reading.min' => 'Angka meter awal tidak boleh negatif.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus jpg, jpeg, atau png.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        // Validasi status tiket harus processing
        TicketStateMachine::validate($installationTicket->status, 'completed');

        // Upload foto meteran (opsional)
        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = FileHelper::uploadPhoto($request->file('photo'), 'meter-photos');
        }

        DB::beginTransaction();
        try {
            $customer = Customer::where('ticket_id', $installationTicket->id)->first();

            if (! $customer) {
                $year = now()->format('Y');
                $latestCustomer = Customer::where('customer_code', 'like', 'PAM-'.$year.'-%')
                    ->orderBy('customer_code', 'desc')
                    ->first();

                $nextNumber = $latestCustomer
                    ? str_pad((int) substr($latestCustomer->customer_code, -4) + 1, 4, '0', STR_PAD_LEFT)
                    : '0001';

                $customerCode = 'PAM-'.$year.'-'.$nextNumber;

                $customer = Customer::create([
                    'ticket_id' => $installationTicket->id,
                    'user_id' => $installationTicket->user_id,
                    'customer_code' => $customerCode,
                    'initial_meter_reading' => $request->initial_meter_reading,
                    'meter_photo_url' => $photoUrl,
                    'activated_at' => now(),
                ]);
            } else {
                $customer->update([
                    'initial_meter_reading' => $request->initial_meter_reading,
                    'meter_photo_url' => $photoUrl,
                    'activated_at' => now(),
                ]);
            }

            // Update status tiket ke completed
            $installationTicket->update(['status' => 'completed']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan hasil pemasangan: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pemasangan berhasil diaktifkan. Pelanggan telah aktif.',
            'data' => [
                'ticket_id' => $installationTicket->fresh()->id,
                'status' => $installationTicket->fresh()->status,
                'customer_code' => $customer->customer_code,
                'initial_meter_reading' => $customer->initial_meter_reading,
                'meter_photo_url' => $customer->meter_photo_url,
                'activated_at' => $customer->activated_at,
            ],
        ]);
    }
}

