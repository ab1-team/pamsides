<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\Village;
use App\Models\MonthlyBill;
use App\Models\InstallationTicket;
use App\Models\JenisLaporan;
use App\Models\SubLaporan;
use App\Models\Account; 
use App\Models\Setting;
use App\Models\Calk;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
        $tahun = $request->tahun;
        $bulan = $request->bulan ?: '01'; 
        $jenisLaporan = $request->nama_laporan;
        $subLaporan = $request->nama_sub_laporan;

        if (!empty($request->bulan)) {
            $tglKondisi = \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->toDateString();
        } else {
            $tanggal = $request->tanggal ?: '01';  
            $tglKondisi = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-' . str_pad($tanggal, 2, '0', STR_PAD_LEFT);
        }

        $bulanan = !empty($request->bulan);
        $meta = [
            'tahun' => $tahun,
            'bulan' => $request->bulan,
            'tanggal' => $request->tanggal ?: '01',
            'jenis_laporan' => $jenisLaporan,
            'sub_laporan' => $subLaporan,
            'tgl_kondisi' => $tglKondisi,
        ];

        $data = [
            'tahun' => $tahun,
            'bulan' => $request->bulan,
            'bulanan' => $bulanan,
            'sub_laporan' => $subLaporan,
            'tgl_kondisi' => $tglKondisi, 
        ];

        $file = $jenisLaporan;

        $handlerMap = [
            'cover'             => 'cover',
            'surat_pengantar'   => 'surat_pengantar',
            'jurnal_transaksi'  => 'jurnal_transaksi',
            'buku_besar'        => 'buku_besar',
            'neraca_saldo'      => 'neraca_saldo',
            'neraca'            => 'neraca',
            'laba_rugi'         => 'laba_rugi',
            'arus_kas'          => 'arus_kas',
            'LPM'               => 'LPM',
            'calkk'             => 'calkk',
            'daftar_pelanggan'  => 'daftar_pelanggan',
            'tagihan_pelanggan' => 'tagihan_pelanggan',
            'piutang_pelanggan' => 'piutang_pelanggan',
            'ati'               => 'ati',
            'atb'               => 'atb',
            'e_budgeting'       => 'e_budgeting',
            'tutup_buku'        => 'awal_tahun',       
            'piutang_komisi'    => 'piutang_komisi'    
        ];

        if (array_key_exists($file, $handlerMap) && method_exists($this, $handlerMap[$file])) {
            return $this->{$handlerMap[$file]}($data);
        }

        $lembaga = Setting::first();

        return response()->json([
            'success' => true,
            'view_target' => $file ?: 'cover',
            'meta' => $meta,
            'payload' => [
                'meta' => $meta,
                'lembaga' => $lembaga ? [
                    'nama' => $lembaga->nama,
                    'alamat' => $lembaga->alamat,
                    'telepon' => $lembaga->telepon,
                    'email' => $lembaga->email,
                    'logo' => $lembaga->logo,
                ] : null,
                'items' => [],
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        return response()->json(['message' => 'Proses export spreadsheet dijalankan']);
    }

    public function simpanSaldo(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Saldo periode berhasil dibukukan!']);
    }

    private function bulanName($bulan)
    {
        $map = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
            '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
            '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
        ];
        $key = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);
        return $map[$key] ?? '';
    }

    private function cover(array $data)
    {
        $lembaga = Setting::first();
        
        // Logika nama teknisi
        $sub = $data['sub_laporan'] ?? null;
        $namaTeknisi = null;
        if (!empty($sub) && $sub !== 'DRPY' && $sub !== 'null' && $sub !== 'undefined') {
            $user = User::find($sub);
            if ($user) $namaTeknisi = strtoupper($user->name);
        }

        return response()->json([
            'success' => true,
            'view_target' => 'cover',
            'title' => 'Cover',
            'meta' => $data,
            'payload' => [
                'lembaga' => $lembaga ? [
                    'nama' => $lembaga->nama,
                    'alamat' => $lembaga->alamat,
                    'telepon' => $lembaga->telepon,
                    'email' => $lembaga->email,
                    'logo' => $lembaga->logo,
                ] : null,
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => $this->bulanName($data['bulan']),
                ],
                'judul' => 'LAPORAN KEUANGAN',
            ],
        ]);
    }
    
    private function surat_pengantar(array $data)
    {
        $lembaga = Setting::first();

        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'surat_pengantar',
            'title' => 'Surat Pengantar' . $periodeText,
            'meta' => $data,
            'payload' => [ 
                'config' => [
                    'paper_size' => 'A4', 
                ],
                'lembaga' => $lembaga ? [
                    'nama' => $lembaga->nama,
                    'alamat' => $lembaga->alamat,
                    'telepon' => $lembaga->telepon,
                    'email' => $lembaga->email,
                ] : null,
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => strtoupper($data['bulan_name']),
                    'tanggal_surat' => $data['tahun'] . '-' . str_pad($data['bulan'], 2, '0', STR_PAD_LEFT) . '-01',
                ],
                'nomor_surat' => '001/LP/' . str_pad($data['bulan'], 2, '0', STR_PAD_LEFT) . '/' . $data['tahun'],
            ], 
        ]);
    }

    private function arus_kas(array $data)
    {
        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'arus_kas', 
            'title' => 'Arus Kas' . $periodeText,
            'meta' => $data,
            'payload' => [
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => [], 
            ], 
        ]);
    }

    private function atb(array $data)
    {
        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'atb', 
            'title' => 'Aset Tak Berwujud' . $periodeText,
            'meta' => $data,
            'payload' => [
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => [], 
            ], 
        ]);
    }

    private function ati(array $data)
    {
        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'ati', 
            'title' => 'Aset Tetap' . $periodeText,
            'meta' => $data,
            'payload' => [
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => [], 
            ], 
        ]);
    }

    private function awal_tahun(array $data)
    {
        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'awal_tahun', 
            'title' => 'Awal Tahun' . $periodeText,
            'meta' => $data,
            'payload' => [
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => [], 
            ], 
        ]);
    }

    private function buku_besar(array $data)
    {
        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'buku_besar', 
            'title' => 'Buku Besar' . $periodeText,
            'meta' => $data,
            'payload' => [
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => [], 
            ], 
        ]);
    }
    
    private function calkk(array $data)
    {
        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'calk', 
            'title' => 'Calk' . $periodeText,
            'meta' => $data,
            'payload' => [
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => [], 
            ], 
        ]);
    }

    private function e_budgeting(array $data)
    {
        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'ebudgeting', 
            'title' => 'E-Budgeting' . $periodeText,
            'meta' => $data,
            'payload' => [
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => [], 
            ], 
        ]);
    }

    private function LPM(array $data)
    {
        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'perubahan_modal', 
            'title' => 'Laporan Perubahan Modal' . $periodeText,
            'meta' => $data,
            'payload' => [
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => [], 
            ], 
        ]);
    }

    private function daftar_pelanggan(array $data)
    {
        $sub = $data['sub_laporan']; 
        
        $namaTeknisi = null;
        if (!empty($sub) && $sub !== 'DRPY' && $sub !== 'null' && $sub !== 'undefined') {
            $userTeknisi = User::find($sub);
            if ($userTeknisi) {
                $namaTeknisi = strtoupper($userTeknisi->name);
            }
        }
        
        $data['nama_teknisi'] = $namaTeknisi;

        $query = Customer::with(['user', 'ticket.village']); 
        $query->whereDate('activated_at', '<=', $data['tgl_kondisi']);

        $query->whereHas('ticket', function($q) {
            $q->where('status', '!=', 'draft');
        });

        // Filter berdasarkan teknisi tertentu
        if (!empty($sub) && $sub !== 'DRPY' && $sub !== 'null' && $sub !== 'undefined') {
            $query->whereHas('ticket', function($q) use ($sub) {
                $q->where('user_id', $sub); 
            });
        }

        $customers = $query->get();

        $resultData = $customers->map(function($customer) {
            $ticket = $customer->ticket; 

            $desa = 'BELUM DISET';
            $dusun = 'BELUM DISET';

            if ($ticket && $ticket->village_id) {
                $village = $ticket->village;
                
                if ($village) {
                    $desa = data_get($village, 'village_name') ?: data_get($village, 'nama_desa');
                    $dusun = data_get($village, 'hamlet_name') ?: data_get($village, 'nama_dusun');
                }
            }

            return [
                'id'            => $customer->id,
                'customer_code' => $customer->customer_code ?: '-',
                'activated_at'  => $customer->activated_at ?: null,
                'name'          => $customer->user?->name ?: '-', 
                'nik'           => $ticket?->nik ?: '-',
                'address'       => $ticket?->address ?: '-',
                'phone'         => $ticket?->phone ?: '-',
                'status'        => $ticket?->status ?: '-', 
                'nama_desa'     => $desa ? strtoupper($desa) : 'BELUM DISET',
                'nama_dusun'    => $dusun ? strtoupper($dusun) : 'BELUM DISET'
            ];
        });

        $data['bulan_name'] = $this->bulanName($data['bulan']);
        
        return response()->json([
            'success' => true,
            'view_target' => 'daftar_pelanggan',
            'title' => 'Daftar Pelanggan',
            'meta' => $data,
            'payload' => [
                'config' => [
                    'paper_size'  => 'A4',
                    'orientation' => 'landscape',
                ],
                'items' => $resultData
            ]
        ]);
    }
        
    private function tagihan_pelanggan(array $data)
    {
        $sub = $data['sub_laporan'];

        $namaTeknisi = null;
        if (!empty($sub) && $sub !== 'DRPY' && $sub !== 'null' && $sub !== 'undefined') {
            $userTeknisi = User::find($sub);
            if ($userTeknisi) {
                $namaTeknisi = strtoupper($userTeknisi->name);
            }
        }
        $data['nama_teknisi'] = $namaTeknisi;

        $query = MonthlyBill::with(['customer.user', 'customer.ticket.village'])
            ->where('status', 'unpaid');

        $carbonKondisi = \Carbon\Carbon::parse($data['tgl_kondisi']);
        
        $query->where(function($q) use ($carbonKondisi) {
            $q->where('billing_period_year', '<', $carbonKondisi->year)
              ->orWhere(function($sq) use ($carbonKondisi) {
                  $sq->where('billing_period_year', $carbonKondisi->year)
                    ->where('billing_period_month', '<=', $carbonKondisi->month);
              });
        });

        if (!empty($sub) && $sub !== 'DRPY') {
            $query->whereHas('customer.ticket', function($q) use ($sub) {
                $q->where('user_id', $sub); 
            });
        }

        if (isset($data['bulanan']) && $data['bulanan'] && !empty($data['bulan'])) {
            $query->where('billing_period_month', $data['bulan'])
                  ->where('billing_period_year', $data['tahun']);
        }

        $bills = $query->get();

        $resultData = $bills->map(function($bill) {
            $customer = $bill->customer;
            $ticket = $customer?->ticket;

            $desa = 'BELUM DISET';
            $dusun = 'BELUM DISET';

            if ($customer) {
                $village = $ticket?->village;
                if (!$village && $ticket?->village_id) {
                    $village = DB::table('village')->where('id', $ticket->village_id)->first();
                }
                if ($village) {
                    $desa = data_get($village, 'village_name') ?: data_get($village, 'nama_desa') ?: 'BELUM DISET';
                    $dusun = data_get($village, 'hamlet_name') ?: data_get($village, 'nama_dusun') ?: 'BELUM DISET';
                }
            }

            $dibayar = $bill->status === 'paid' ? $bill->total_amount : 0;
            
            return [
                'id'               => $bill->id,
                'nama_desa'        => strtoupper($desa),
                'nama_dusun'       => strtoupper($dusun),
                'customer_code'    => $customer?->customer_code ?? '-',
                'activated_at'     => $customer?->activated_at ? \Carbon\Carbon::parse($customer->activated_at)->format('d-m-Y') : '-',
                'name'             => $customer?->user?->name ?: '-', 
                'bulan_lalu'       => $bill->penalty_amount, 
                'bulan_ini'        => (string)($bill->usage_charge + $bill->abodemen), 
                'sampai_bulan_ini' => $bill->total_amount, 
                'dibayar'          => $dibayar,
                'status'           => $bill->status,
            ];
        });

        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = isset($data['bulanan']) && $data['bulanan'] 
            ? ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')' 
            : ' (Tahun ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'tagihan_pelanggan',
            'title' => 'Tagihan Pelanggan' . $periodeText,
            'meta' => $data,
            'payload' => [
                'config' => [
                    'paper_size'  => 'A4',
                    'orientation' => 'landscape',
                ],
                'items' => $resultData
            ]
        ]);
    }

    private function piutang_pelanggan(array $data)
    {
        $sub = $data['sub_laporan'] ?? null;
        
        $namaTeknisi = null;
        if (!empty($sub) && $sub !== 'DRPY' && $sub !== 'null' && $sub !== 'undefined') {
            $userTeknisi = User::find($sub);
            if ($userTeknisi) {
                $namaTeknisi = strtoupper($userTeknisi->name);
            }
        }
        $data['nama_teknisi'] = $namaTeknisi;

        $carbonKondisi = \Carbon\Carbon::parse($data['tgl_kondisi']);
        $targetBulan = $carbonKondisi->month;
        $targetTahun = $carbonKondisi->year;

        $query = Customer::with(['user', 'ticket.village', 'monthlyBills' => function($q) {
            $q->where('status', 'unpaid');
        }]);

        $query->where('activated_at', '<=', $data['tgl_kondisi']);

        if (!empty($sub) && $sub !== 'DRPY') {
            $query->whereHas('ticket', function($q) use ($sub) {
                $q->where('user_id', $sub);
            });
        }

        $customers = $query->get();
        $resultData = [];

        foreach ($customers as $customer) {
            $ticket = $customer->ticket;
            
            $sd3BulanLalu = 0;
            $bulanLalu = 0;
            $bulanIni = 0;
            $jumlahBulanTunggakan = 0;

            foreach ($customer->monthlyBills as $bill) {
                $billBulan = (int)$bill->billing_period_month;
                $billTahun = (int)$bill->billing_period_year;

                $selisihBulan = (($targetTahun - $billTahun) * 12) + ($targetBulan - $billBulan);

                if ($selisihBulan >= 0) {
                    $jumlahBulanTunggakan++;
                    $nominalTagihan = (float)$bill->total_amount;

                    if ($selisihBulan === 0) {
                        $bulanIni += $nominalTagihan;
                    } elseif ($selisihBulan === 1) {
                        $bulanLalu += $nominalTagihan;
                    } else {
                        $sd3BulanLalu += $nominalTagihan;
                    }
                }
            }

            $totalTunggakan = $sd3BulanLalu + $bulanLalu + $bulanIni;
            if ($totalTunggakan <= 0) {
                continue;
            }

            if ($jumlahBulanTunggakan === 1) {
                $kategori = 'LANCAR';
            } elseif ($jumlahBulanTunggakan === 2) {
                $kategori = 'KURANG LANCAR';
            } elseif ($jumlahBulanTunggakan === 3) {
                $kategori = 'DIRAGUKAN';
            } else {
                $kategori = 'MACET';
            }

            $village = $ticket?->village;
            if (!$village && $ticket?->village_id) {
                $village = DB::table('village')->where('id', $ticket->village_id)->first();
            }
            $desa = $village ? (data_get($village, 'village_name') ?: data_get($village, 'nama_desa') ?: 'BELUM DISET') : 'BELUM DISET';
            $dusun = $village ? (data_get($village, 'hamlet_name') ?: data_get($village, 'nama_dusun') ?: 'BELUM DISET') : 'BELUM DISET';

            $resultData[] = [
                'nama_desa'        => strtoupper($desa),
                'nama_dusun'       => strtoupper($dusun),
                'customer_code'    => $customer->customer_code ?? '-',
                'name'             => $customer->user?->name ?: '-',
                'sd_3_bulan_lalu'  => $sd3BulanLalu,
                'bulan_lalu'       => $bulanLalu,
                'bulan_ini'        => $bulanIni,
                'total_tunggakan'  => $totalTunggakan,
                'dibayar'          => 0.00, 
                'kategori'         => $kategori,
            ];
        }

        $data['bulan_name'] = $this->bulanName($targetBulan);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $targetTahun . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'piutang_pelanggan',
            'title' => 'Piutang Pelanggan' . $periodeText,
            'meta' => $data,
            'payload' => [
                'config' => [
                    'paper_size'  => 'A4',
                    'orientation' => 'landscape',
                ],
                'items' => $resultData
            ]
        ]);
    }

    private function jurnal_transaksi(array $data)
    {
        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'jurnal_transaksi', 
            'title' => 'Jurnal Transaksi' . $periodeText,
            'meta' => $data,
            'payload' => [
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => [], 
            ], 
        ]);
    }

    private function neraca_saldo(array $data)
    {
        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'neraca_saldo', 
            'title' => 'Neraca Saldo' . $periodeText,
            'meta' => $data,
            'payload' => [
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => [], 
            ], 
        ]);
    }

    private function neraca(array $data)
    {
        $data['bulan_name'] = $this->bulanName($data['bulan']);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $data['tahun'] . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'neraca', 
            'title' => 'Neraca' . $periodeText,
            'meta' => $data,
            'payload' => [
                'periode' => [
                    'tahun' => $data['tahun'],
                    'bulan' => $data['bulan'],
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => [], 
            ], 
        ]);
    }

    private function laba_rugi(array $data)
    {   
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $data['bulan_name'] = $this->bulanName($bln);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $thn . ')';

        $groups = [
            ['type' => 'main', 'label' => '4. PENDAPATAN', 'items' => []],
            ['type' => 'sub',  'label' => '4.1.00.00. Pendapatan Operasional', 'items' => $this->getAkunData('4.1.%')],
            ['type' => 'main', 'label' => '5. BEBAN', 'items' => []],
            ['type' => 'sub',  'label' => '5.1.00.00. Beban Operasional', 'items' => $this->getAkunData('5.1.%')],
            ['type' => 'sub',  'label' => '4.2.00.00. Pendapatan Non Usaha', 'items' => $this->getAkunData('4.2.%')],
            ['type' => 'sub',  'label' => '5.3.00.00. Beban Non Usaha', 'items' => $this->getAkunData('5.3.%')],
            ['type' => 'sub',  'label' => '5.4.00.00. Beban Pajak', 'items' => $this->getAkunData('5.4.01.01')],
        ];

        return response()->json([
            'success'     => true,
            'view_target' => 'laba_rugi',
            'title'       => 'Laba Rugi' . $periodeText,
            'meta'        => $data,
            'payload'     => [
                'periode' => [
                    'tahun'      => $thn,
                    'bulan'      => $bln,
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'groups' => $groups,
            ],
        ]);
    }

    private function getAkunData($kode) {
        return Account::where('kode_akun', 'LIKE', $kode)
            ->get()
            ->map(function($akun) {
                return [
                    'nama_akun'     => $akun->kode_akun . '. ' . $akun->nama_akun,
                    'sd_bulan_lalu' => 0, 
                    'bulan_ini'     => 0,
                    'sd_bulan_ini'  => 0
                ];
            });
    }
}
