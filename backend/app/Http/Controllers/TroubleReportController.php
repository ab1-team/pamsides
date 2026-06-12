<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Customer;
use App\Models\TroubleReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TroubleReportController extends Controller
{
    public function index(Request $request)
    {
        $query = TroubleReport::with(['user', 'customer.ticket'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $reports = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'trouble_type' => 'required|string|max:100',
            'description' => 'required|string',
            'contact_phone' => 'required|string|max:20',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov|max:5120',
        ], [
            'trouble_type.required' => 'Jenis gangguan wajib dipilih.',
            'description.required' => 'Deskripsi gangguan wajib diisi.',
            'contact_phone.required' => 'Nomor telepon wajib diisi.',
            'photo.mimes' => 'Format foto/video harus jpg, jpeg, png, mp4, atau mov.',
            'photo.max' => 'Ukuran file maksimal 5MB.',
        ]);

        $user = Auth::user();

        $customer = null;
        if ($user) {
            $customer = Customer::where('user_id', $user->id)->first();
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = FileHelper::uploadPhoto($request->file('photo'), 'trouble-reports');
        }

        $report = TroubleReport::create([
            'customer_id' => $customer?->id,
            'user_id' => $user?->id,
            'trouble_type' => $request->trouble_type,
            'description' => $request->description,
            'contact_phone' => $request->contact_phone,
            'photo_path' => $photoPath,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan gangguan berhasil dikirim.',
            'data' => $report,
        ], 201);
    }

    public function show($id)
    {
        $report = TroubleReport::with(['user', 'customer.ticket', 'handler'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,resolved',
            'admin_note' => 'nullable|string',
        ]);

        $report = TroubleReport::findOrFail($id);

        $report->status = $request->status;
        $report->admin_note = $request->admin_note;
        $report->handled_by = Auth::id();

        if ($request->status === 'resolved') {
            $report->resolved_at = now();
        }

        $report->save();

        return response()->json([
            'success' => true,
            'message' => 'Status laporan diperbarui.',
            'data' => $report,
        ]);
    }
}
