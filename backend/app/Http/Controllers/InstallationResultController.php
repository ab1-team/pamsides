<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\InstallationTicket;
use App\StateMachines\TicketStateMachine;
use Illuminate\Http\Request;

class InstallationResultController extends Controller
{
    public function store(Request $request, InstallationTicket $installationTicket)
    {
        $request->validate([
            'initial_meter_reading' => 'required|numeric|min:0',
            'photo'                 => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'initial_meter_reading.required' => 'Angka meter awal wajib diisi.',
            'initial_meter_reading.numeric'  => 'Angka meter awal harus berupa angka.',
            'initial_meter_reading.min'      => 'Angka meter awal tidak boleh negatif.',
            'photo.image'                    => 'File harus berupa gambar.',
            'photo.mimes'                    => 'Format foto harus jpg, jpeg, atau png.',
            'photo.max'                      => 'Ukuran foto maksimal 2MB.',
        ]);

        // Validasi status tiket harus processing
        TicketStateMachine::validate($installationTicket->status, 'completed');

        // Upload foto meteran (opsional)
        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = FileHelper::uploadPhoto($request->file('photo'), 'meter-photos');
        }

        // Simpan sementara di cache untuk digunakan saat aktivasi
        cache()->put(
            "installation_result_{$installationTicket->id}",
            [
                'initial_meter_reading' => $request->initial_meter_reading,
                'meter_photo_url'       => $photoUrl,
            ],
            now()->addHours(24)
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'ticket_id'             => $installationTicket->id,
                'initial_meter_reading' => $request->initial_meter_reading,
                'meter_photo_url'       => $photoUrl,
            ],
        ]);
    }
}
