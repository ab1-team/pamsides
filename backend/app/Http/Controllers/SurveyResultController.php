<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\InstallationTicket;
use App\Models\SurveyResult;
use App\StateMachines\TicketStateMachine;
use Illuminate\Http\Request;

class SurveyResultController extends Controller
{
    public function store(Request $request, InstallationTicket $installationTicket)
    {
        $request->validate([
            'distance_to_pipe_m' => 'required|integer|min:0',
            'material_notes'     => 'required|string',
            'photo'              => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'distance_to_pipe_m.required' => 'Jarak ke pipa utama wajib diisi.',
            'distance_to_pipe_m.integer'  => 'Jarak ke pipa utama harus berupa angka.',
            'distance_to_pipe_m.min'      => 'Jarak ke pipa utama tidak boleh negatif.',
            'material_notes.required'     => 'Catatan material wajib diisi.',
            'photo.required'              => 'Foto lokasi wajib diupload.',
            'photo.image'                 => 'File harus berupa gambar.',
            'photo.mimes'                 => 'Format foto harus jpg, jpeg, atau png.',
            'photo.max'                   => 'Ukuran foto maksimal 2MB.',
        ]);

        // Validasi status tiket harus pending
        TicketStateMachine::validate($installationTicket->status, 'surveyed');

        // Upload foto
        $photoUrl = FileHelper::uploadPhoto($request->file('photo'), 'survey-photos');

        // Simpan hasil survey
        $surveyResult = SurveyResult::create([
            'ticket_id'          => $installationTicket->id,
            'surveyor_id'        => $request->user()->id,
            'distance_to_pipe_m' => $request->distance_to_pipe_m,
            'material_notes'     => $request->material_notes,
            'photo_url'          => $photoUrl,
            'surveyed_at'        => now(),
        ]);

        // Update status tiket ke surveyed
        $installationTicket->update(['status' => 'surveyed']);

        return response()->json([
            'success' => true,
            'data'    => $surveyResult,
        ], 201);
    }
}
