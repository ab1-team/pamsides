<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\MonthlyBill;
use App\Models\InstallationTicket;
use App\Models\JenisLaporan;
use App\Models\SubLaporan;
use App\Models\Account; 
use App\Models\Calk;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class PelaporanController extends Controller
{
    public function index()
    {
        $laporan = JenisLaporan::where('file', '!=', '0')->orderBy('urut', 'ASC')->get();
        $dataLaporan = collect([
            [
                'id' => '', 
                'text' => '---'
            ]
        ]);

        $laporan->each(function ($lap, $index) use ($dataLaporan) {
            $dataLaporan->push([
                'id' => strval($lap->file), 
                'text' => str_pad($index + 1, 2, '0', STR_PAD_LEFT) . '. ' . strtoupper($lap->nama_laporan)
            ]);
        });

        $minBillYear = DB::table('monthly_bills')->min('billing_period_year');
        $minTicketDate = DB::table('installation_tickets')->min('created_at');
        $minTicketYear = $minTicketDate ? \Carbon\Carbon::parse($minTicketDate)->year : null;

        $tahunTerkecil = collect([$minBillYear, $minTicketYear])->filter()->min();
        $tahunAwal = $tahunTerkecil ? intval($tahunTerkecil) : intval(date('Y'));

        return response()->json([
            'success' => true,
            'data' => [
                'laporan' => $dataLaporan, 
                'tahun_awal' => $tahunAwal
            ]
        ]);
    }
        
    public function subLaporan(Request $request, $file)
    {
        if (empty($file) || $file === 'null' || $file === 'undefined') {
            return response()->json([
                'success' => true,
                'data' => [
                    ['value' => '', 'title' => '--']
                ]
            ]);
        }

        $jenisLaporan = JenisLaporan::where('file', $file)->first();
        $kodeFile = $jenisLaporan ? $jenisLaporan->file : $file;

        if ($kodeFile == 'buku_besar' || $file == 'buku_besar') {
            $sub_laporan = [
                ['value' => '', 'title' => 'Pilih Sub Laporan']
            ];
            $accounts = Account::where('kode_akun', '!=', '3.2.02.01')->get();
            foreach ($accounts as $acc) {
                $sub_laporan[] = [
                    'value' => $acc->kode_akun,
                    'title' => $acc->kode_akun . '. ' . $acc->nama_akun
                ];
            }
            return response()->json(['success' => true, 'data' => $sub_laporan]);
        }

        if ($kodeFile == 'e_budgeting' || $file == 'e_budgeting') {
            $sub_laporan = [
                ['title' => 'Pilih Sub Laporan', 'value' => ''],
                ['title' => '01. Januari - Maret', 'value' => '1,2,3'],
                ['title' => '02. April - Juni', 'value' => '4,5,6'],
                ['title' => '03. Juli - September', 'value' => '7,8,9'],
                ['title' => '04. Oktober - Desember', 'value' => '10,11,12']
            ];
            return response()->json(['success' => true, 'data' => $sub_laporan]);
        }

        if ($kodeFile == 'tutup_buku' || $file == 'tutup_buku') {
            $sub_laporan = [
                ['title' => 'Pengalokasian Laba', 'value' => 'alokasi_laba'],
                ['title' => 'Jurnal Tutup Buku', 'value' => 'jurnal_tutup_buku'],
                ['title' => 'Neraca', 'value' => 'neraca_tutup_buku'],
                ['title' => 'Laba Rugi', 'value' => 'laba_rugi_tutup_buku'],
                ['title' => 'CALK', 'value' => 'CALK_tutup_buku']
            ];
            return response()->json(['success' => true, 'data' => $sub_laporan]);
        }

        $laporanOperasional = ['daftar_pelanggan', 'piutang_pelanggan', 'tagihan_pelanggan', '11', '12', '13']; 

        if (in_array($kodeFile, $laporanOperasional) || in_array($file, $laporanOperasional)) {
            $sub_laporan = [
                ['value' => '', 'title' => 'Pilih Teknisi']
            ];
            $teknisi = User::where('role', 'teknisi')->orderBy('name', 'ASC')->get();
            foreach ($teknisi as $tk) {
                $sub_laporan[] = [
                    'value' => $tk->id,
                    'title' => $tk->name
                ];
            }
            return response()->json(['success' => true, 'data' => $sub_laporan]);
        }

        $dbSubLaporan = SubLaporan::where('file', $file)->orderBy('urut', 'ASC')->get();
        if ($dbSubLaporan->isNotEmpty()) {
            $sub_laporan = [
                ['value' => '', 'title' => 'Pilih Sub Laporan']
            ];
            foreach ($dbSubLaporan as $sub) {
                $sub_laporan[] = [
                    'value' => $sub->file_kab, 
                    'title' => $sub->nama_laporan
                ];
            }
            return response()->json(['success' => true, 'data' => $sub_laporan]);
        }

        return response()->json([
            'success' => true,
            'data' => []
        ], 200);
    }

    public function preview(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
        $bulan = $request->bulan ?? '';
        $tanggal = $request->tanggal ?? '';
        $namaLaporan = $request->nama_laporan ?? '';
        $subLaporan = $request->nama_sub_laporan ?? '';

        $jenis = JenisLaporan::where('file', $namaLaporan)->first();
        $judulLaporan = $jenis ? $jenis->nama_laporan : 'Laporan';
        $subJudul = '';
        if ($subLaporan) {
            $sub = SubLaporan::where('file_kab', $subLaporan)->first();
            if ($sub) {
                $subJudul = $sub->nama_laporan;
            } else {
                $acc = Account::where('kode_akun', $subLaporan)->first();
                if ($acc) {
                    $subJudul = $acc->nama_akun;
                }
            }
        }

        $dataHasil = [];

        $pdf = Pdf::loadView('pelaporan.pdf_template', [
            'dataHasil' => $dataHasil,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'tanggal' => $tanggal,
            'judulLaporan' => $judulLaporan,
            'subJudul' => $subJudul,
            'jenisLaporan' => $namaLaporan,
            'tanggalCetak' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        $filename = 'Preview_' . ($namaLaporan ?: 'laporan') . '_' . $tahun . '.pdf';

        return $pdf->stream($filename);
    }

    public function exportExcel(Request $request)
    {
        return response()->json(['message' => 'Proses export spreadsheet dijalankan']);
    }

    public function simpanSaldo(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Saldo periode berhasil dibukukan!']);
    }

    private function daftar_pelanggan(array $data)
    {
        $sub = $data['sub_laporan']; 
        
        $query = Customer::with(['user', 'ticket.package']);

        if ($sub === 'DRP') {
            $query->whereHas('ticket', function($q) {
                $q->where('status', 'completed');
            });
        } elseif ($sub === 'DRPY') {
            $query->whereYear('activated_at', $data['tahun']);
            if ($data['bulanan']) {
                $query->whereMonth('activated_at', $data['bulan']);
            }
        }

        $resultData = $query->get();

        return response()->json([
            'success' => true,
            'view_target' => 'daftar_pelanggan',
            'meta' => $data,
            'payload' => $resultData
        ]);
    }

    private function tagihan_pelanggan(array $data)
    {
        $sub = $data['sub_laporan'];

        $query = MonthlyBill::with(['customer.user', 'customer.ticket.package'])
            ->where('billing_period_year', $data['tahun']);

        if ($data['bulanan']) {
            $query->where('billing_period_month', $data['bulan']);
        }

        if ($sub === 'KBP') {
        }

        $resultData = $query->get();

        return response()->json([
            'success' => true,
            'view_target' => 'tagihan_pelanggan',
            'meta' => $data,
            'payload' => $resultData
        ]);
    }

    private function piutang_pelanggan(array $data)
    {
        $query = MonthlyBill::with(['customer.user', 'customer.ticket.package'])
            ->where('status', 'unpaid')
            ->where('billing_period_year', $data['tahun']);

        if ($data['bulanan']) {
            $query->where('billing_period_month', $data['bulan']);
        }

        $resultData = $query->get();

        return response()->json([
            'success' => true,
            'view_target' => 'piutang_pelanggan',
            'meta' => $data,
            'payload' => $resultData
        ]);
    }

    private function cover(array $data)
    {
        return response()->json(['success' => true, 'view_target' => 'cover', 'payload' => $data]);
    }

    private function surat_pengantar(array $data)
    {
        return response()->json(['success' => true, 'view_target' => 'surat_pengantar', 'payload' => $data]);
    }

    private function jurnal_transaksi(array $data)
    {
        return response()->json(['success' => true, 'view_target' => 'jurnal_transaksi', 'payload' => $data]);
    }

    private function neraca_saldo(array $data)
    {
        return response()->json(['success' => true, 'view_target' => 'neraca_saldo', 'payload' => $data]);
    }

    private function neraca(array $data)
    {
        return response()->json(['success' => true, 'view_target' => 'neraca', 'payload' => $data]);
    }

    private function laba_rugi(array $data)
    {
        return response()->json(['success' => true, 'view_target' => 'laba_rugi', 'payload' => $data]);
    }
}
