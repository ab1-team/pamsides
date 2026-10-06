<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesSafeUploadNames;
use App\Models\InstallationTicket;
use App\Models\SurveyResult;
use App\StateMachines\TicketStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SurveyResultController extends Controller
{
    use GeneratesSafeUploadNames;

    public function index(Request $request)
    {
        $query = SurveyResult::with(['surveyor', 'ticket.package', 'ticket.village', 'ticket.user']);

        if ($request->has('ticket_id')) {
            $query->where('ticket_id', $request->ticket_id);
        }

        if ($request->has('surveyor_id')) {
            $query->where('surveyor_id', $request->surveyor_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('ticket', function ($q) use ($search) {
                $q->where('applicant_name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        $surveys = $query->latest('surveyed_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $surveys,
        ]);
    }

    public function show($id)
    {
        $survey = SurveyResult::with(['surveyor', 'ticket.package', 'ticket.village'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $survey,
        ]);
    }

    public function store(Request $request, InstallationTicket $installationTicket)
    {
        $request->validate([
            'distance_to_pipe_m' => 'required|integer|min:0',
            'material_notes' => 'required|string',
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'distance_to_pipe_m.required' => 'Jarak ke pipa utama wajib diisi.',
            'distance_to_pipe_m.integer' => 'Jarak ke pipa utama harus berupa angka.',
            'distance_to_pipe_m.min' => 'Jarak ke pipa utama tidak boleh negatif.',
            'material_notes.required' => 'Catatan material wajib diisi.',
            'photo.required' => 'Foto lokasi wajib diupload.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus jpg, jpeg, atau png.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        // Hanya tiket `pending` boleh disurvey. Sebelumnya `draft` ikut
        // diterima, sehingga admin yang masih mengisi draft bisa
        // di-dorong ke `surveyed` dan seluruh langkah registrasi
        // terlewati — transisi itu memang dilarang di TicketStateMachine.
        if ($installationTicket->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Tiket dengan status '.$installationTicket->status.' tidak dapat di-survey.',
            ], 422);
        }

        // Lewati helper state machine supaya aturan transisi tetap satu
        // sumber kebenaran (rulenya mengizinkan pending -> surveyed).
        TicketStateMachine::validate($installationTicket->status, 'surveyed');

        // Penjaga terakhir terhadap survey ganda. Cek status di atas biasanya
        // sudah cukup (store() selalu mengubah status ke `surveyed`), tapi
        // dua request bersamaan bisa sama-sama lolos sebelum salah satunya
        // commit. survey_results.ticket_id sengaja tidak diberi unique
        // constraint, jadi pengecekan eksplisit ini diperlukan.
        if ($installationTicket->survey()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket ini sudah memiliki hasil survey.',
            ], 409);
        }

        $fileName = $this->storeUploadedImage($request->file('photo'), 'survey-photos');

        DB::beginTransaction();
        try {
            $survey = SurveyResult::create([
                'ticket_id' => $installationTicket->id,
                'surveyor_id' => $request->user()->id,
                'distance_to_pipe_m' => $request->distance_to_pipe_m,
                'material_notes' => $request->material_notes,
                'photo_url' => $fileName,
                'surveyed_at' => now(),
            ]);

            $installationTicket->update(['status' => 'surveyed']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Survey berhasil disimpan. Status tiket berubah menjadi surveyed.',
                'data' => $survey->load(['surveyor', 'ticket.package', 'ticket.payments']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // Bersihkan file yang barusan ditulis supaya tidak ada file
            // yatim di disk kalau insert-nya gagal.
            Storage::disk('public')->delete('survey-photos/'.$fileName);

            // Jangan bocorkan pesan exception internal ke klien.
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan survey. Silakan coba lagi.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $survey = SurveyResult::findOrFail($id);

        $request->validate([
            'distance_to_pipe_m' => 'sometimes|integer|min:0',
            'material_notes' => 'sometimes|string',
            'photo' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'distance_to_pipe_m.integer' => 'Jarak ke pipa utama harus berupa angka.',
            'distance_to_pipe_m.min' => 'Jarak ke pipa utama tidak boleh negatif.',
            'material_notes.required' => 'Catatan material wajib diisi.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus jpg, jpeg, atau png.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('photo')) {
                if ($survey->photo_url) {
                    Storage::disk('public')->delete('survey-photos/'.$survey->photo_url);
                }
                $survey->photo_url = $this->storeUploadedImage($request->file('photo'), 'survey-photos');
            }

            if ($request->filled('distance_to_pipe_m')) {
                $survey->distance_to_pipe_m = $request->distance_to_pipe_m;
            }
            if ($request->filled('material_notes')) {
                $survey->material_notes = $request->material_notes;
            }
            $survey->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Survey berhasil diupdate.',
                'data' => $survey->fresh()->load(['surveyor', 'ticket.package']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal update survey: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $survey = SurveyResult::findOrFail($id);
        $ticket = $survey->ticket;

        return $this->safeDelete(
            fn () => DB::transaction(function () use ($survey, $ticket) {
                if ($survey->photo_url) {
                    Storage::disk('public')->delete('survey-photos/'.$survey->photo_url);
                }

                $survey->delete();

                if ($ticket && $ticket->status === 'surveyed') {
                    $ticket->update(['status' => 'pending']);
                }
            }),
            'SURVEY_IN_USE',
            'Survey',
            $ticket?->applicant_name,
            'Survey berhasil dihapus.',
        );
    }
}
