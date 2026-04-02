<?php

namespace App\Http\Controllers;

use App\Models\InstallationTicket;
use App\Models\MonthlyBill;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function billing(Request $request)
    {
        $request->validate([
            'year'   => 'sometimes|integer|min:2000',
            'month'  => 'sometimes|integer|between:1,12',
            'status' => 'sometimes|string|in:unpaid,paid',
        ]);

        $query = MonthlyBill::with('customer.user')
            ->orderBy('billing_period_year', 'desc')
            ->orderBy('billing_period_month', 'desc');

        if ($request->has('year')) {
            $query->where('billing_period_year', $request->year);
        }
        if ($request->has('month')) {
            $query->where('billing_period_month', $request->month);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate(10),
        ]);
    }

    public function installation(Request $request)
    {
        $request->validate([
            'status'     => 'sometimes|string|in:pending,surveyed,unpaid,processing,completed',
            'start_date' => 'sometimes|date',
            'end_date'   => 'sometimes|date|after_or_equal:start_date',
        ]);

        $query = InstallationTicket::with('package')
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate(10),
        ]);
    }

    public function exportBillingCsv(Request $request)
    {
        $query = MonthlyBill::with('customer.user');

        if ($request->has('year')) {
            $query->where('billing_period_year', $request->year);
        }
        if ($request->has('month')) {
            $query->where('billing_period_month', $request->month);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bills = $query->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-tagihan.csv"',
        ];

        $callback = function () use ($bills) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID', 'Kode Pelanggan', 'Nama', 'Tahun', 'Bulan',
                'Meter Awal', 'Meter Akhir', 'Pemakaian (m³)',
                'Biaya Pemakaian', 'Abodemen', 'Denda', 'Total',
                'Status', 'Jatuh Tempo',
            ]);

            foreach ($bills as $bill) {
                fputcsv($file, [
                    $bill->id,
                    $bill->customer->customer_code,
                    $bill->customer->user->name,
                    $bill->billing_period_year,
                    $bill->billing_period_month,
                    $bill->meter_reading_start,
                    $bill->meter_reading_end,
                    $bill->usage_m3,
                    $bill->usage_charge,
                    $bill->abodemen,
                    $bill->penalty_amount,
                    $bill->total_amount,
                    $bill->status,
                    $bill->due_date,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportBillingPdf(Request $request)
    {
        $query = MonthlyBill::with('customer.user');

        if ($request->has('year')) {
            $query->where('billing_period_year', $request->year);
        }
        if ($request->has('month')) {
            $query->where('billing_period_month', $request->month);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bills = $query->get();

        $pdf = Pdf::loadView('reports.billing', compact('bills'));

        return $pdf->download('laporan-tagihan.pdf');
    }

    public function exportInstallationCsv(Request $request)
    {
        $query = InstallationTicket::with('package');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $tickets = $query->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-instalasi.csv"',
        ];

        $callback = function () use ($tickets) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID', 'Nama Pemohon', 'NIK', 'Alamat',
                'Paket', 'Status', 'Tanggal',
            ]);

            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $ticket->id,
                    $ticket->applicant_name,
                    $ticket->nik,
                    $ticket->address,
                    $ticket->package->name,
                    $ticket->status,
                    $ticket->created_at->format('d-m-Y'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportInstallationPdf(Request $request)
    {
        $query = InstallationTicket::with('package');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $tickets = $query->get();

        $pdf = Pdf::loadView('reports.installation', compact('tickets'));

        return $pdf->download('laporan-instalasi.pdf');
    }
}
