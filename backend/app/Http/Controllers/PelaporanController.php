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
use App\Models\BillPayment;
use App\Models\Setting;
use App\Models\Amount;
use App\Models\Calk;
use App\Models\AkunLevel1;
use App\Models\AkunLevel2;
use App\Models\AkunLevel3;
use App\Models\Ebudgeting;
use App\Models\MasterArusKas;
use App\Models\Transaction;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\InventoryService;
use App\Services\PelaporanService;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PelaporanController extends Controller
{
    protected $pelaporanService;

    public function __construct(PelaporanService $pelaporanService)
    {
        $this->pelaporanService = $pelaporanService;
    }
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
            $sub_laporan = [];
            $accounts = Account::where('kode_akun', '!=', '3.2.02.01')
                ->whereNull('tgl_nonaktif')
                ->orderBy('kode_akun', 'ASC')->get();
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
            'calk'              => 'calkk',
            'calkk'             => 'calkk',
            'daftar_pelanggan'  => 'daftar_pelanggan',
            'tagihan_pelanggan' => 'tagihan_pelanggan',
            'piutang_pelanggan' => 'piutang_pelanggan',
            'ati'               => 'ati',
            'atb'               => 'atb',
            'e_budgeting'       => 'e_budgeting',
            'tutup_buku'        => 'awal_tahun',
            'piutang_komisi'    => 'piutang_komisi',
        ];

        if (array_key_exists($file, $handlerMap) && method_exists($this, $handlerMap[$file])) {
            $response = $this->{$handlerMap[$file]}($data);

            return $this->injectLembaga($response);
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

    private function injectLembaga($response)
    {
        if (! $response instanceof \Illuminate\Http\JsonResponse) {
            return $response;
        }

        $body = $response->getData(true);

        if (! isset($body['payload']) || ! is_array($body['payload'])) {
            return $response;
        }

        if (empty($body['payload']['lembaga'])) {
            $lembaga = Setting::first();
            $body['payload']['lembaga'] = $lembaga ? [
                'nama' => $lembaga->nama,
                'alamat' => $lembaga->alamat,
                'telepon' => $lembaga->telepon,
                'email' => $lembaga->email,
                'logo' => $lembaga->logo,
            ] : null;

            $response->setData($body);
        }

        return $response;
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

    private function paperConfig($key)
    {
        $map = [
            'cover'            => ['A4', 'portrait'],
            'surat_pengantar'  => ['A4', 'portrait'],
            'arus_kas'         => ['A4', 'portrait'],
            'atb'              => ['A4', 'landscape'],
            'ati'              => ['A4', 'landscape'],
            'calkk'            => ['A4', 'portrait'],
            'buku_besar'       => ['A4', 'portrait'],
            'neraca_saldo'     => ['A4', 'landscape'],
            'neraca'           => ['A4', 'portrait'],
            'laba_rugi'        => ['A4', 'portrait'],
            'LPM'              => ['A4', 'portrait'],
            'e_budgeting'      => ['A4', 'landscape'],
            'jurnal_transaksi' => ['A4', 'portrait'],
            'piutang_komisi'   => ['A4', 'landscape'],
            'daftar_pelanggan'   => ['A4', 'landscape'],
            'tagihan_pelanggan'  => ['A4', 'landscape'],
            'piutang_pelanggan'  => ['A4', 'landscape'],
            'tutup_buku_neraca'  => ['A4', 'portrait'],
            'tutup_buku_calk'    => ['A4', 'portrait'],
            'tutup_buku_laba_rugi' => ['A4', 'portrait'],
            'tutup_buku_jurnal'   => ['A4', 'portrait'],
            'tutup_buku_alokasi_laba' => ['A4', 'portrait'],
        ];
        $cfg = $map[$key] ?? ['A4', 'portrait'];
        return [
            'paper_size'  => $cfg[0],
            'orientation' => $cfg[1],
        ];
    }

    private function cover(array $data)
    {
        $lembaga = Setting::first();

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
                'config' => $this->paperConfig('surat_pengantar'),
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

    private function calkk(array $data)
    {
        $tahun = $data['tahun'];
        $bulan = $data['bulan'];

        $akun1 = $this->pelaporanService->loadAkunTree($tahun, $bulan);
        $surplus = $this->pelaporanService->hitungSurplus($tahun, $bulan);
        $rows = $this->pelaporanService->susunFlatRows($akun1, $surplus);

        $data['bulan_name'] = $this->bulanName($bulan);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $tahun . ')';
        $calkContent = $data['sub_laporan'] ?? Calk::where('tanggal', $data['tgl_kondisi'])->value('catatan') ?? '';

        return response()->json([
            'success' => true,
            'view_target' => 'calkk',
            'title' => 'CALK' . $periodeText,
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('calkk'),
                'periode' => [
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'calk_content' => $calkContent,
                'rows' => $rows,
                'total_saldo' => $surplus,
            ],
        ]);
    }

    private function e_budgeting(array $data)
    {
        $thn = $data['tahun'];
        $bulan_input = explode(',', (string)($data['sub_laporan'] ?? '1,2,3'));
        $list_bulan = array_map('intval', $bulan_input);
        while(count($list_bulan) < 3) $list_bulan[] = 0;

        $items = $this->pelaporanService->getEBudgetingData($thn, $list_bulan);

        // Persiapan Title
        $bulanMap = [1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'];
        $periodeText = ' (' . ($bulanMap[$list_bulan[0]] ?? '') . ' - ' . ($bulanMap[end($list_bulan)] ?? '') . ' ' . $thn . ')';

        return response()->json([
            'success' => true,
            'view_target' => 'e_budgeting',
            'title' => 'E - Budgeting' . $periodeText,
            'payload' => [
                'config' => $this->paperConfig('e_budgeting'),
                'items' => $items,
                'bulan_tampil' => $list_bulan,
                'thn' => $thn
            ],
        ]);
    }

    private function LPM(array $data)
    {
        $tahun = $data['tahun'];
        $bulan = $data['bulan'];
        $data['bulan_name'] = $this->bulanName($bulan);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $tahun . ')';

        $accounts = Account::where('lev1', '3')
            ->with(['amount' => function ($q) use ($tahun, $bulan) {
                $q->where('tahun', $tahun)
                    ->where('bulan', '<=', $bulan);
            }])->get();

        $surplus = $this->pelaporanService->hitungSurplus($tahun, $bulan);

        $items = $accounts->map(function ($acc) use ($surplus) {
            if ($acc->kode_akun === '3.2.02.01') {
                $saldo = $surplus;
            } else {
                $saldo = $acc->amount ? ($acc->amount->sum('kredit') - $acc->amount->sum('debit')) : 0;
            }
            return [
                'kode_akun' => $acc->kode_akun,
                'nama_akun' => $acc->nama_akun,
                'saldo'     => $saldo,
            ];
        })->values();

        $total = $items->sum('saldo');

        return response()->json([
            'success' => true,
            'view_target' => 'perubahan_modal',
            'title' => 'Laporan Perubahan Modal' . $periodeText,
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('LPM'),
                'periode' => [
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => $items,
                'total_saldo' => $total,
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

        $query->whereHas('ticket', function ($q) {
            $q->where('status', '!=', 'draft');
        });

        if (!empty($sub) && $sub !== 'DRPY' && $sub !== 'null' && $sub !== 'undefined') {
            $query->whereHas('ticket', function ($q) use ($sub) {
                $q->where('user_id', $sub);
            });
        }

        $customers = $query->get();

        $resultData = $customers->map(function ($customer) {
            $ticket = $customer->ticket;

            $desa = 'Data Tidak Ada';
            $dusun = 'Data Tidak Ada';

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
                'name'          => $ticket?->applicant_name ?: $customer->user?->name ?: '-',
                'nik'           => $ticket?->nik ?: '-',
                'address'       => $ticket?->address ?: '-',
                'phone'         => $ticket?->phone ?: '-',
                'status'        => $ticket?->status ?: '-',
                'nama_desa'     => $desa ? strtoupper($desa) : 'Data Tidak Ada',
                'nama_dusun'    => $dusun ? strtoupper($dusun) : 'Data Tidak Ada'
            ];
        });

        $data['bulan_name'] = $this->bulanName($data['bulan']);

        return response()->json([
            'success' => true,
            'view_target' => 'daftar_pelanggan',
            'title' => 'Daftar Pelanggan',
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('daftar_pelanggan'),
                'items' => $resultData,
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

        $query->where(function ($q) use ($carbonKondisi) {
            $q->where('billing_period_year', '<', $carbonKondisi->year)
                ->orWhere(function ($sq) use ($carbonKondisi) {
                    $sq->where('billing_period_year', $carbonKondisi->year)
                        ->where('billing_period_month', '<=', $carbonKondisi->month);
                });
        });

        if (!empty($sub) && $sub !== 'DRPY') {
            $query->whereHas('customer.ticket', function ($q) use ($sub) {
                $q->where('user_id', $sub);
            });
        }

        if (isset($data['bulanan']) && $data['bulanan'] && !empty($data['bulan'])) {
            $query->where('billing_period_month', $data['bulan'])
                ->where('billing_period_year', $data['tahun']);
        }

        $bills = $query->get();

        $resultData = $bills->map(function ($bill) {
            $customer = $bill->customer;
            $ticket = $customer?->ticket;

            $desa = 'Data Tidak Ada';
            $dusun = 'Data Tidak Ada';

            if ($customer) {
                $village = $ticket?->village;
                if (!$village && $ticket?->village_id) {
                    $village = DB::table('village')->where('id', $ticket->village_id)->first();
                }
                if ($village) {
                    $desa = data_get($village, 'village_name') ?: data_get($village, 'nama_desa') ?: 'Data Tidak Ada';
                    $dusun = data_get($village, 'hamlet_name') ?: data_get($village, 'nama_dusun') ?: 'Data Tidak Ada';
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
                'config' => $this->paperConfig('tagihan_pelanggan'),
                'items' => $resultData,
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

        $query = Customer::with(['user', 'ticket.village', 'monthlyBills' => function ($q) {
            $q->where('status', 'unpaid');
        }]);

        $query->where('activated_at', '<=', $data['tgl_kondisi']);

        if (!empty($sub) && $sub !== 'DRPY') {
            $query->whereHas('ticket', function ($q) use ($sub) {
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
                $billBulan = (int) $bill->billing_period_month;
                $billTahun = (int) $bill->billing_period_year;

                $selisihBulan = (($targetTahun - $billTahun) * 12) + ($targetBulan - $billBulan);

                if ($selisihBulan >= 0) {
                    $jumlahBulanTunggakan++;
                    $nominalTagihan = (float) $bill->total_amount;

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
            $desa = $village ? (data_get($village, 'village_name') ?: data_get($village, 'nama_desa') ?: 'Data Tidak Ada') : 'Data Tidak Ada';
            $dusun = $village ? (data_get($village, 'hamlet_name') ?: data_get($village, 'nama_dusun') ?: 'Data Tidak Ada') : 'Data Tidak Ada';

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
                'config' => $this->paperConfig('piutang_pelanggan'),
                'items' => $resultData,
            ]
        ]);
    }

    private function jurnal_transaksi(array $data)
    {
        $tahun = $data['tahun'];
        $bulan = $data['bulan'];
        $data['bulan_name'] = $this->bulanName($bulan);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $tahun . ')';

        $trx = Transaction::with(['accountDebet', 'accountKredit'])
            ->whereYear('tgl_transaksi', $tahun)
            ->whereMonth('tgl_transaksi', $bulan)
            ->orderBy('tgl_transaksi', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        $items = $trx->map(function ($t) {
            return [
                'id'      => $t->id,
                'tgl'     => $t->tgl_transaksi,
                'debet'   => [
                    'kode'   => $t->account_debet,
                    'nama'   => $t->accountDebet?->nama_akun ?? '-',
                    'jumlah' => (float) $t->saldo,
                ],
                'kredit'  => [
                    'kode'   => $t->account_kredit,
                    'nama'   => $t->accountKredit?->nama_akun ?? '-',
                    'jumlah' => (float) $t->saldo,
                ],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'view_target' => 'jurnal_transaksi',
            'title' => 'Jurnal Transaksi' . $periodeText,
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('jurnal_transaksi'),
                'periode' => [
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => $items,
            ],
        ]);
    }

    private function neraca_saldo(array $data)
    {
        $tahun = $data['tahun'];
        $bulan = $data['bulan'];
        $data['bulan_name'] = $this->bulanName($bulan);
        
        // 1. Ambil data akun
        $accounts = Account::with(['amount' => function ($q) use ($tahun, $bulan) {
            $q->where('tahun', $tahun)->where('bulan', '<=', $bulan);
        }])->orderBy('kode_akun', 'ASC')->get();

        $surplus = $this->pelaporanService->hitungSurplus($tahun, $bulan);
        $items = $this->pelaporanService->getNeracaSaldoData($accounts, $surplus);

        $summary = [
            'jumlah_saldo_debit'      => (float) $items->sum('saldo_debit'),
            'jumlah_saldo_kredit'     => (float) $items->sum('saldo_kredit'),
            'jumlah_laba_rugi_debit'  => (float) $items->sum('saldo_laba_rugi_debit'),
            'jumlah_laba_rugi_kredit' => (float) $items->sum('saldo_laba_rugi_kredit'),
            'jumlah_neraca_debit'     => (float) $items->sum('saldo_neraca_debit'),
            'jumlah_neraca_kredit'    => (float) $items->sum('saldo_neraca_kredit'),
            'surplus_defisit'         => (float) $surplus,
        ];

        return response()->json([
            'success' => true,
            'view_target' => 'neraca_saldo',
            'title' => 'Neraca Saldo (' . $data['bulan_name'] . ' ' . $tahun . ')',
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('neraca_saldo'),
                'periode' => [
                    'tahun' => $tahun, 'bulan' => $bulan,
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => $items,
                'summary' => $summary,
            ],
        ]);
    }

    private function neraca(array $data)
    {
        $tahun = $data['tahun'];
        $bulan = $data['bulan'];
        $data['bulan_name'] = $this->bulanName($bulan);
        $periodeText = ' PER ' . Carbon::create($tahun, $bulan, 1)->endOfMonth()->format('d') . ' ' . $data['bulan_name'] . ' ' . $tahun;

        $akun1 = $this->pelaporanService->loadAkunTree($tahun, $bulan);
        $this->pelaporanService->susunAkunDenganTotal($akun1);

        $surplus = $this->pelaporanService->hitungSurplus($tahun, $bulan);

        $totalAset = 0;
        $aktiva = $akun1->firstWhere('lev1', 1);
        if ($aktiva) {
            $totalAset = (float) $aktiva->total_saldo_lev1;
        }

        $kewajibanEkuitas = $akun1->filter(fn($a) => in_array($a->lev1, [2, 3]));
        $totalLiabilitasEkuitas = (float) $kewajibanEkuitas->sum('total_saldo_lev1');

        if (abs($totalLiabilitasEkuitas - $totalAset) > 0.01) {
            $ekuitas3 = $akun1->firstWhere('lev1', 3);
            if ($ekuitas3) {
                $ekuitas3->total_saldo_lev1 = (float) $ekuitas3->total_saldo_lev1 + ($totalAset - $totalLiabilitasEkuitas);
                $totalLiabilitasEkuitas = (float) $kewajibanEkuitas->sum('total_saldo_lev1');
            }
        }

        $akun1->each(function ($lev1) use ($surplus) {
            $lev1->akunLevel2->each(function ($lev2) use ($surplus) {
                $lev2->akunLevel3->each(function ($lev3) use ($surplus) {
                    $lev3->total_saldo = $lev3->accountParent->sum(function ($acc) use ($surplus) {
                        if ($acc->kode_akun === '3.2.02.01') return $surplus;
                        $d = $acc->amount ? $acc->amount->sum('debit') : 0;
                        $k = $acc->amount ? $acc->amount->sum('kredit') : 0;
                        return ($acc->jenis_mutasi == 'kredit') ? ($k - $d) : ($d - $k);
                    });
                });
            });
        });

        return response()->json([
            'success' => true,
            'view_target' => 'neraca',
            'title' => 'Neraca' . $periodeText,
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('neraca'),
                'periode' => [
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => $akun1,
                'total_liabilitas_equitas' => $totalLiabilitasEkuitas,
                'total_aset' => $totalAset,
            ],
        ]);
    }

    private function laba_rugi(array $data)
    {
        $tahun = $data['tahun'];
        $bulan = (int) $data['bulan'];
        $data['bulan_name'] = $this->bulanName($bulan);

        // Panggil satu service saja
        $report = $this->pelaporanService->getLabaRugiReport($tahun, $bulan);

        return response()->json([
            'success'     => true,
            'view_target' => 'laba_rugi',
            'title'       => 'Laba Rugi (' . strtoupper($data['bulan_name']) . ' ' . $tahun . ')',
            'meta'        => $data,
            'payload'     => [
                'config'    => $this->paperConfig('laba_rugi'),
                'groups'    => $report['groups'],
                'laba_rugi' => $report['laba_rugi'],
            ],
        ]);
    }
    private function arus_kas(array $data)
    {
        $tahun = $data['tahun'];
        $bulan = $data['bulan'];
        $tgl_awal = "$tahun-$bulan-01";
        $tgl_akhir = date("Y-m-t", strtotime($tgl_awal));

        $data['bulan_name'] = $this->bulanName($bulan);
        $periodeText = ' (' . strtoupper($data['bulan_name']) . ' ' . $tahun . ')';

        $masters = MasterArusKas::with(['children.rek_debit:id,kode_akun,nama_akun', 'children.rek_kredit:id,kode_akun,nama_akun'])
            ->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->whereIn('id', [1, 2, 9, 27, 39])
            ->orderBy('id', 'ASC')
            ->get();

        $saldoKas = (float) Transaction::where(function ($q) {
                $q->where('account_debet', 'LIKE', '1.1.01.%')->orWhere('account_kredit', 'LIKE', '1.1.01.%');
            })
            ->where('tgl_transaksi', '<=', $tgl_akhir)
            ->selectRaw("SUM(CASE WHEN account_debet LIKE '1.1.01.%' THEN saldo ELSE -saldo END) as total")
            ->value('total') ?? 0;

        $items = [];
        $romawi = ['I', 'II', 'III', 'IV'];
        $idx = 0;

        if ($saldoKas != 0) {
            $items[] = [
                'type'   => 'section-header',
                'roman'  => $romawi[$idx] ?? '',
                'label'  => 'SALDO KAS SETARA KAS',
                'jumlah' => $saldoKas,
            ];
            $items[] = ['type' => 'spacer'];
            $idx++;
        }

        $buildPrefix = function ($code) {
            $parts = explode('.', $code);
            array_pop($parts);
            return implode('.', $parts);
        };

        $computeSaldo = function ($child) use ($tgl_awal, $tgl_akhir, $buildPrefix) {
            $saldo = 0;
            if ($child->debit) {
                $prefix = $buildPrefix($child->debit);
                $saldo += (float) Transaction::where('account_debet', 'LIKE', $prefix . '.%')
                    ->whereBetween('tgl_transaksi', [$tgl_awal, $tgl_akhir])
                    ->sum('saldo');
            }
            if ($child->kredit) {
                $prefix = $buildPrefix($child->kredit);
                $saldo -= (float) Transaction::where('account_kredit', 'LIKE', $prefix . '.%')
                    ->whereBetween('tgl_transaksi', [$tgl_awal, $tgl_akhir])
                    ->sum('saldo');
            }
            return $saldo;
        };

        $renderNode = function ($node, &$items, &$sub_total) use (&$renderNode, $computeSaldo, $idx) {
            foreach ($node->children as $child) {
                $child->load(['rek_debit:id,kode_akun,nama_akun', 'rek_kredit:id,kode_akun,nama_akun']);
                if ($child->children->count() > 0) {
                    $items[] = [
                        'type'   => 'sub-header',
                        'label'  => $child->nama_akun,
                    ];
                    $child_total = 0;
                    $renderNode($child, $items, $child_total);
                    $items[] = [
                        'type'   => 'sub-total',
                        'label'  => 'JUMLAH ' . $child->nama_akun,
                        'jumlah' => $child_total,
                    ];
                    $sub_total += $child_total;
                } else {
                    $saldo = $computeSaldo($child);
                    $label = $child->rek_debit->nama_akun ?? $child->rek_kredit->nama_akun ?? $child->nama_akun ?? 'Akun';
                    $items[] = [
                        'type'   => 'item-row',
                        'label'  => $label,
                        'jumlah' => $saldo,
                    ];
                    $sub_total += $saldo;
                }
            }
        };

        foreach ($masters as $master) {
            if ($master->id === 1) {
                continue;
            }
            $roman = $master->id === 9 ? '' : ($romawi[$idx] ?? '');
            if ($master->id !== 9) {
                $idx++;
            }
            $items[] = [
                'type'   => 'section-header',
                'roman'  => $roman,
                'label'  => strtoupper($master->nama_akun),
                'jumlah' => null,
            ];

            $sub_total = 0;
            $renderNode($master, $items, $sub_total);

            $items[] = [
                'type'   => 'total-row',
                'label'  => 'JUMLAH ' . strtoupper($master->nama_akun),
                'jumlah' => $sub_total,
            ];
            $items[] = ['type' => 'spacer'];
        }

        return response()->json([
            'success'     => true,
            'view_target' => 'arus_kas',
            'title'       => 'Arus Kas' . $periodeText,
            'payload'     => [
                'config'  => $this->paperConfig('arus_kas'),
                'periode' => [
                    'tahun'      => $tahun,
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items'   => $items,
            ],
        ]);
    }

    private function atb(array $data)
    {
        $tahun = $data['tahun'];
        $bulan = $data['bulan'];
        $data['bulan_name'] = $this->bulanName($bulan);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $tahun . ')';
        $tglKondisi = $data['tgl_kondisi'] ?? date('Y-m-t', strtotime("$tahun-$bulan-01"));

        // 1. Ganti filter kode_akun menjadi 1.2.03% untuk ATB
        $accounts = Account::where('kode_akun', 'LIKE', '1.2.03%')
            ->where('lev3', 1)
            ->orderBy('lev4', 'ASC')
            ->get();

        $accounts->load(['inventory' => function ($q) use ($tglKondisi) {
            $q->where('tgl_beli', '<=', $tglKondisi)
            ->where('status', '!=', '0')
            ->where('jenis', '1') // Pastikan 'jenis' sesuai untuk ATB
            ->orderBy('kategori', 'ASC')
            ->orderBy('tgl_beli', 'ASC');
        }]);

        $accounts = $accounts->map(function ($a) {
            $a->setRelation('inventory', $a->inventory->where('kategori', (string) $a->lev4)->values());
            return $a;
        });

        $accounts->transform(function ($account) use ($tglKondisi) {
            $account->inventory->transform(function ($inv) use ($tglKondisi) {
                $inv->detail_susut = InventoryService::hitungItemSatuan($inv, $tglKondisi);
                return $inv;
            });
            return $account;
        });

        return response()->json([
            'success' => true,
            'view_target' => 'atb', // Pastikan Vue menangkap ini
            'title' => 'Aset Tak Berwujud' . $periodeText,
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('atb'),
                'periode' => [
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => $accounts, // Data sudah terisi
            ],
        ]);
    }

    private function ati(array $data)
    {
        $tahun = $data['tahun'];
        $bulan = $data['bulan'];
        $data['bulan_name'] = $this->bulanName($bulan);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $tahun . ')';

        $tglKondisi = $data['tgl_kondisi'] ?? date('Y-m-t', strtotime("$tahun-$bulan-01"));

        $accounts = Account::where('kode_akun', 'LIKE', '1.2.01%')
            ->where('lev3', 1)
            ->orderBy('lev4', 'ASC')
            ->get();

        $accounts->load(['inventory' => function ($q) use ($tglKondisi) {
            $q->where('tgl_beli', '<=', $tglKondisi)
              ->where('status', '!=', '0')
              ->where('jenis', '1')
              ->orderBy('kategori', 'ASC')
              ->orderBy('tgl_beli', 'ASC');
        }]);

        $accounts = $accounts->map(function ($a) {
            $a->setRelation('inventory', $a->inventory->where('kategori', (string) $a->lev4)->values());
            return $a;
        });

        $accounts->transform(function ($account) use ($tglKondisi) {
            $account->inventory->transform(function ($inv) use ($tglKondisi) {

                $inv->detail_susut = InventoryService::hitungItemSatuan($inv, $tglKondisi);
                return $inv;
            });
            return $account;
        });

        return response()->json([
            'success' => true,
            'view_target' => 'ati',
            'title' => 'Aset Tetap' . $periodeText,
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('ati'),
                'periode' => [
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => $accounts,
            ],
        ]);
    }

    private function piutang_komisi(array $data)
    {
        $tahun = $data['tahun'];
        $bulan = $data['bulan'];
        $data['bulan_name'] = $this->bulanName($bulan);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $tahun . ')';

        $accKodeKomisi = '2.1.02.02';
        $accKodeBebanKomisi = '5.1.02.04';

        $billPayments = BillPayment::with([
                'transactions',
                'bill.customer.user',
                'bill.customer.ticket',
            ])
            ->whereHas('bill', function ($q) {
                $q->where('status', 'paid');
            })
            ->whereYear('paid_at', $tahun)
            ->whereMonth('paid_at', $bulan)
            ->get();

        $penerimaIds = $billPayments->map(fn ($bp) => $bp->transactions->pluck('penerima_komisi_id'))
            ->flatten()
            ->filter()
            ->unique()
            ->values();
        $penerimaMap = User::whereIn('id', $penerimaIds)->get()->keyBy('id');

        $items = $billPayments->map(function ($bp) use ($accKodeKomisi, $accKodeBebanKomisi, $penerimaMap) {
            $bill = $bp->bill;
            $tagihan = (float) ($bill->total_amount ?? 0);
            $transactions = $bp->transactions ?? collect();

            $komisi = $transactions
                ->filter(fn ($t) => $t->account_debet === $accKodeBebanKomisi)
                ->sum(fn ($t) => (float) $t->saldo);

            $dibayar = $transactions
                ->filter(fn ($t) => $t->account_kredit === $accKodeKomisi)
                ->sum(fn ($t) => (float) $t->saldo);

            $penerimaId = $transactions->first()?->penerima_komisi_id;
            $namaPelanggan = optional($bill?->customer?->user)->name
                ?? optional($bill?->customer?->ticket)->applicant_name
                ?? '-';

            return [
                'nama_pelanggan'  => $namaPelanggan,
                'kode_pelanggan'  => $bill?->customer?->customer_code ?? '-',
                'bill_id'         => $bill?->id,
                'bill_payment_id' => $bp->id,
                'total_tagihan'   => $tagihan,
                'komisi_total'    => $komisi,
                'dibayar'         => $dibayar,
                'penerima_komisi_id'   => $penerimaId,
                'penerima_komisi_name' => optional($penerimaMap->get($penerimaId))->name,
            ];
        })
        ->filter(fn ($item) => $item['komisi_total'] > 0 || $item['dibayar'] > 0 || $item['penerima_komisi_id'])
        ->values();

        return response()->json([
            'success' => true,
            'view_target' => 'piutang_komisi',
            'title' => 'Daftar Utang Komisi SPS' . $periodeText,
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('piutang_komisi'),
                'periode' => [
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'bulan_name' => strtoupper($data['bulan_name']),
                ],
                'items' => $items,
            ],
        ]);
    }

    private function buku_besar(array $data)
    {
        $tahun = $data['tahun'];
        $bulan = $data['bulan'];
        $kodeAkun = $data['sub_laporan'];
        $data['bulan_name'] = $this->bulanName($bulan);
        $periodeText = ' (' . $data['bulan_name'] . ' ' . $tahun . ')';

        if (empty($kodeAkun) || $kodeAkun === 'null' || $kodeAkun === 'undefined') {
            return response()->json([
                'success' => true,
                'view_target' => 'buku_besar',
                'title' => 'Buku Besar' . $periodeText,
                'meta' => $data,
                'payload' => [
                    'config' => $this->paperConfig('buku_besar'),
                    'periode' => [
                        'tahun' => $tahun,
                        'bulan' => $bulan,
                        'bulan_name' => strtoupper($data['bulan_name']),
                    ],
                    'kode_akun' => '',
                    'nama_akun' => '',
                    'jenis_mutasi' => 'debit',
                    'transactions' => [],
                ],
            ]);
        }

        $account = Account::where('kode_akun', $kodeAkun)->first();
        if (!$account) {
            $account = new Account();
            $account->kode_akun = $kodeAkun;
            $account->nama_akun = '-';
            $account->jenis_mutasi = 'debit';
        }

        $saldoAwal = Amount::where('account_id', $account->id)
            ->where('tahun', '<', $tahun)
            ->selectRaw('SUM(debit) as d, SUM(kredit) as k')->first();
        $saldoAwalTahun = [
            'debit'  => (float) ($saldoAwal->d ?? 0),
            'kredit' => (float) ($saldoAwal->k ?? 0),
        ];

        $saldoSebelum = Amount::where('account_id', $account->id)
            ->where('tahun', $tahun)
            ->where('bulan', '<', $bulan)
            ->selectRaw('SUM(debit) as d, SUM(kredit) as k')->first();
        $saldoBulanLalu = [
            'debit'  => (float) ($saldoSebelum->d ?? 0) + $saldoAwalTahun['debit'],
            'kredit' => (float) ($saldoSebelum->k ?? 0) + $saldoAwalTahun['kredit'],
        ];

        $trx = Transaction::where(function ($q) use ($kodeAkun) {
            $q->where('account_debet', $kodeAkun)->orWhere('account_kredit', $kodeAkun);
        })
            ->whereYear('tgl_transaksi', $tahun)
            ->whereMonth('tgl_transaksi', $bulan)
            ->orderBy('tgl_transaksi', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->map(function ($t) {
                return [
                    'id'                  => $t->id,
                    'tgl_transaksi'       => $t->tgl_transaksi,
                    'account_debet'       => $t->account_debet,
                    'account_kredit'      => $t->account_kredit,
                    'keterangan_transaksi'=> $t->keterangan_transaksi,
                    'saldo'               => (float) $t->saldo,
                    'urutan'              => $t->urutan,
                    'transaction_group'   => $t->transaction_group,
                ];
            })->values();

        return response()->json([
            'success' => true,
            'view_target' => 'buku_besar',
            'title' => 'Buku Besar' . $periodeText,
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('buku_besar'),
                'periode' => [
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'bulan_name' => $data['bulan_name'],
                ],
                'kode_akun' => $account->kode_akun,
                'nama_akun' => $account->nama_akun,
                'jenis_mutasi' => $account->jenis_mutasi,
                'saldo_awal_tahun' => $saldoAwalTahun,
                'saldo_bulan_lalu' => $saldoBulanLalu,
                'transactions' => $trx,
            ],
        ]);
    }

    private function awal_tahun(array $data)
    {
        $sub = $data['sub_laporan'] ?? '';

        $map = [
            'alokasi_laba'       => 'tutup_buku_alokasi_laba',
            'jurnal_tutup_buku'  => 'tutup_buku_jurnal',
            'neraca_tutup_buku'  => 'tutup_buku_neraca',
            'laba_rugi_tutup_buku' => 'tutup_buku_laba_rugi',
            'CALK_tutup_buku'    => 'tutup_buku_calk',
        ];

        $viewTarget = $map[$sub] ?? 'tutup_buku_neraca';
        $fn = str_replace('-', '_', $viewTarget);

        if (!method_exists($this, $fn)) {
            return response()->json([
                'success' => true,
                'view_target' => $viewTarget,
                'title' => 'Tutup Buku',
                'meta' => $data,
                'payload' => ['items' => []],
            ]);
        }

        return $this->$fn($data);
    }

    private function tutup_buku_alokasi_laba(array $data)
    {
        $tahun = $data['tahun'];
        $tahunLalu = $tahun - 1;
        $bulanLalu = 12;

        $surplus = $this->pelaporanService->hitungSurplus($tahunLalu, $bulanLalu);

        $alokasi = Account::where('kode_akun', 'like', '3.2.01.%')
            ->with(['amount' => function ($q) use ($tahunLalu, $bulanLalu) {
                $q->where('tahun', $tahunLalu)->where('bulan', $bulanLalu);
            }])->get()->map(function ($a) {
                $d = $a->amount ? $a->amount->sum('debit') : 0;
                $k = $a->amount ? $a->amount->sum('kredit') : 0;
                $a->saldo = ($a->jenis_mutasi == 'kredit') ? ($k - $d) : ($d - $k);
                $a->amount = $a->amount;
                return $a;
            });

        return response()->json([
            'success' => true,
            'view_target' => 'tutup_buku_alokasi_laba',
            'title' => 'Alokasi Pembagian Laba (' . $tahun . ')',
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('tutup_buku_alokasi_laba'),
                'periode' => ['tahun' => $tahun, 'bulan' => '0', 'bulan_name' => 'AWAL TAHUN'],
                'surplus' => $surplus,
                'alokasi' => $alokasi,
            ],
        ]);
    }

    private function tutup_buku_neraca(array $data)
    {
        $tahun = $data['tahun'];
        $tahunLalu = $tahun - 1;
        $bulanLalu = 12;

        $akun1 = $this->pelaporanService->loadAkunTree($tahunLalu, $bulanLalu);
        $this->pelaporanService->susunAkunDenganTotal($akun1);

        return response()->json([
            'success' => true,
            'view_target' => 'tutup_buku_neraca',
            'title' => 'Neraca Awal Tahun (' . $tahun . ')',
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('tutup_buku_neraca'),
                'periode' => ['tahun' => $tahun, 'bulan' => '0', 'bulan_name' => 'AWAL TAHUN'],
                'items' => $akun1,
                'total_liabilitas_equitas' => (float) $akun1->whereIn('lev1', [2, 3])->sum('total_saldo_lev1'),
            ],
        ]);
    }

    private function tutup_buku_laba_rugi(array $data)
    {
        $tahun = $data['tahun'];
        $tahunLalu = $tahun - 1;
        $bulanLalu = 12;

        $groups = [];
        $totalPendapatan = 0;
        $totalBeban = 0;

        $buildItems = function ($whereAkun, $tahunTarget, $bulanTarget) use ($tahun) {
            $accs = Account::with(['amount' => function ($q) use ($tahun, $bulanTarget) {
                $q->where('tahun', $tahun)->where('bulan', $bulanTarget);
            }])->where('kode_akun', 'like', $whereAkun)->orderBy('kode_akun', 'ASC')->get();

            $items = [];
            foreach ($accs as $a) {
                $d = $a->amount ? $a->amount->sum('debit') : 0;
                $k = $a->amount ? $a->amount->sum('kredit') : 0;
                $bulanIni = ($a->jenis_mutasi == 'kredit') ? ($k - $d) : ($d - $k);
                $items[] = [
                    'nama_akun'    => $a->kode_akun . '. ' . $a->nama_akun,
                    'sd_bulan_lalu'=> 0,
                    'bulan_ini'    => $bulanIni,
                    'sd_bulan_ini' => $bulanIni,
                ];
            }
            return $items;
        };

        $pendapatanItems = $buildItems('4.1.%', $tahunLalu, $bulanLalu);
        $pendapatanNon = $buildItems('4.2.%', $tahunLalu, $bulanLalu);
        $bebanOps = $buildItems('5.1.%', $tahunLalu, $bulanLalu);
        $bebanNon = $buildItems('5.3.%', $tahunLalu, $bulanLalu);
        $bebanPjk = $buildItems('5.4.%', $tahunLalu, $bulanLalu);

        $totalPendapatan = array_sum(array_column(array_merge($pendapatanItems, $pendapatanNon), 'bulan_ini'));
        $totalBeban = array_sum(array_column(array_merge($bebanOps, $bebanNon, $bebanPjk), 'bulan_ini'));
        $labaRugi = $totalPendapatan - $totalBeban;

        $groups[] = ['type' => 'main', 'label' => '4. PENDAPATAN', 'items' => []];
        $groups[] = ['type' => 'sub',  'label' => '4.1. Pendapatan Operasional', 'items' => $pendapatanItems];
        $groups[] = ['type' => 'sub',  'label' => '4.2. Pendapatan Non Usaha', 'items' => $pendapatanNon];
        $groups[] = [
            'type' => 'total', 'label' => 'Jumlah Pendapatan',
            'items' => [[
                'nama_akun' => 'Jumlah Pendapatan',
                'sd_bulan_lalu' => 0,
                'bulan_ini' => $totalPendapatan,
                'sd_bulan_ini' => $totalPendapatan,
                'isTotal' => true,
            ]],
        ];
        $groups[] = ['type' => 'main', 'label' => '5. BEBAN', 'items' => []];
        $groups[] = ['type' => 'sub',  'label' => '5.1. Beban Operasional', 'items' => $bebanOps];
        $groups[] = ['type' => 'sub',  'label' => '5.3. Beban Non Usaha', 'items' => $bebanNon];
        $groups[] = ['type' => 'sub',  'label' => '5.4. Beban Pajak', 'items' => $bebanPjk];
        $groups[] = [
            'type' => 'total', 'label' => 'Jumlah Beban',
            'items' => [[
                'nama_akun' => 'Jumlah Beban',
                'sd_bulan_lalu' => 0,
                'bulan_ini' => $totalBeban,
                'sd_bulan_ini' => $totalBeban,
                'isTotal' => true,
            ]],
        ];
        $groups[] = [
            'type' => 'grand', 'label' => 'LABA / (RUGI)',
            'items' => [[
                'nama_akun' => $labaRugi >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH',
                'sd_bulan_lalu' => 0,
                'bulan_ini' => $labaRugi,
                'sd_bulan_ini' => $labaRugi,
                'isTotal' => true,
            ]],
        ];

        return response()->json([
            'success' => true,
            'view_target' => 'tutup_buku_laba_rugi',
            'title' => 'Laba Rugi Awal Tahun (' . $tahun . ')',
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('tutup_buku_laba_rugi'),
                'periode' => ['tahun' => $tahunLalu, 'bulan' => '12', 'bulan_name' => 'DESEMBER'],
                'groups' => $groups,
                'laba_rugi' => $labaRugi,
            ],
        ]);
    }

    private function tutup_buku_jurnal(array $data)
    {
        $tahun = $data['tahun'];
        $tahunLalu = $tahun - 1;
        $tglAkhir = Carbon::create($tahunLalu, 12, 31)->toDateString();

        $trx = Transaction::with(['accountDebet', 'accountKredit'])
            ->whereDate('tgl_transaksi', $tglAkhir)
            ->where('keterangan_transaksi', 'like', '%TUTUP BUKU%')
            ->orderBy('id', 'ASC')
            ->get();

        $items = $trx->map(function ($t) {
            return [
                'id'      => $t->id,
                'tgl'     => $t->tgl_transaksi,
                'debet'   => [
                    'kode'   => $t->account_debet,
                    'nama'   => $t->accountDebet?->nama_akun ?? '-',
                    'jumlah' => (float) $t->saldo,
                ],
                'kredit'  => [
                    'kode'   => $t->account_kredit,
                    'nama'   => $t->accountKredit?->nama_akun ?? '-',
                    'jumlah' => (float) $t->saldo,
                ],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'view_target' => 'tutup_buku_jurnal',
            'title' => 'Jurnal Tutup Buku ' . $tahun,
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('tutup_buku_jurnal'),
                'periode' => ['tahun' => $tahun, 'bulan' => '0', 'bulan_name' => 'AWAL TAHUN'],
                'items' => $items,
            ],
        ]);
    }

    private function tutup_buku_calk(array $data)
    {
        $tahun = $data['tahun'];
        $tahunLalu = $tahun - 1;
        $bulanLalu = 12;

        $calkContent = $data['sub_laporan'] ?? '';

        if (empty(trim(strip_tags($calkContent)))) {
            $calk = Calk::where('tanggal', $data['tgl_kondisi'])->first();
            if ($calk && !empty(trim(strip_tags($calk->catatan)))) {
                $calkContent = $calk->catatan;
            } else {
                $calkContent = '';
            }
        }

        $akun1 = AkunLevel1::where('lev1', '<=', '3')
            ->with([
                'akunLevel2.akunLevel3' => fn($q) => $q->orderBy('kode_akun', 'ASC'),
                'akunLevel2.akunLevel3.accountParent.amount' => function ($q) use ($tahunLalu, $bulanLalu) {
                    $q->where('tahun', $tahunLalu)->where('bulan', $bulanLalu);
                }
            ])
            ->orderBy('kode_akun', 'ASC')
            ->get();

        $labaRugi = Account::where('lev1', '>=', '4')->with(['amount' => function ($q) use ($tahunLalu, $bulanLalu) {
            $q->where('tahun', $tahunLalu)->where('bulan', $bulanLalu);
        }])->get();

        $hitung = function($acc) {
            $d = $acc->amount ? $acc->amount->sum('debit') : 0;
            $k = $acc->amount ? $acc->amount->sum('kredit') : 0;
            return ($acc->jenis_mutasi == 'kredit') ? ($k - $d) : ($d - $k);
        };

        $pendapatan = $labaRugi->where('lev1', '4')->sum($hitung);
        $beban = $labaRugi->where('lev1', '5')->sum($hitung);
        $surplus = $pendapatan - $beban;

        $rows = $this->susunFlatRowsTutupBuku($akun1, $surplus);

        return response()->json([
            'success' => true,
            'view_target' => 'tutup_buku_calk',
            'title' => 'CALK Awal Tahun (' . $tahun . ')',
            'meta' => $data,
            'payload' => [
                'config' => $this->paperConfig('tutup_buku_calk'),
                'periode' => ['tahun' => $tahun, 'bulan' => '0', 'bulan_name' => 'AWAL TAHUN'],
                'calk_content' => $calkContent,
                'rows' => $rows,
                'total_saldo' => $surplus,
            ],
        ]);
    }

    private function susunFlatRowsTutupBuku($akun1, $surplus, $surplusAccount = '3.2.02.01')
    {
        $rows = [];
        foreach ($akun1 as $l1) {
            $rows[] = ['type' => 'lev1', 'kode_akun' => $l1->kode_akun, 'nama_akun' => $l1->nama_akun];
            foreach ($l1->akunLevel2 as $l2) {
                $rows[] = ['type' => 'lev2', 'kode_akun' => $l2->kode_akun, 'nama_akun' => $l2->nama_akun];
                foreach ($l2->akunLevel3 as $l3) {
                    $total_l3 = 0;
                    $temp_lev4 = [];
                    foreach ($l3->accountParent as $acc) {
                        $d = $acc->amount ? $acc->amount->sum('debit') : 0;
                        $k = $acc->amount ? $acc->amount->sum('kredit') : 0;
                        $saldo = ($acc->kode_akun === $surplusAccount)
                            ? $surplus
                            : (($acc->jenis_mutasi == 'kredit') ? ($k - $d) : ($d - $k));
                        $total_l3 += $saldo;
                        $temp_lev4[] = [
                            'type' => 'lev4',
                            'kode_akun' => $acc->kode_akun,
                            'nama_akun' => $acc->nama_akun,
                            'saldo' => $saldo,
                        ];
                    }
                    $rows[] = [
                        'type' => 'lev3',
                        'kode_akun' => $l3->kode_akun,
                        'nama_akun' => $l3->nama_akun,
                        'saldo' => $total_l3,
                    ];
                    $rows = array_merge($rows, $temp_lev4);
                }
            }
        }
        return $rows;
    }

    private function getAkunData($kode)
    {
        return Account::where('kode_akun', 'LIKE', $kode)
            ->get()
            ->map(function ($akun) {
                return [
                    'nama_akun'     => $akun->kode_akun . '. ' . $akun->nama_akun,
                    'sd_bulan_lalu' => 0,
                    'bulan_ini'     => 0,
                    'sd_bulan_ini'  => 0
                ];
            });
    }
}
