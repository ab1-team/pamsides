<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Inventory;
use App\Models\Transaction;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalUmumController extends Controller
{
    private const ALASAN_HAPUS = ['hapus', 'dijual', 'revaluasi', 'rusak', 'hilang'];

    public function __construct(private InventoryService $service) {}

    public function form(Request $request)
    {
        $request->validate([
            'tgl_transaksi' => 'required|date',
            'jenis_transaksi' => 'required|integer',
            'sumber_dana' => 'required|string|exists:accounts,kode_akun',
            'disimpan_ke' => 'required|string|exists:accounts,kode_akun',
        ]);

        $sumberDana = $request->sumber_dana;
        $disimpanKe = $request->disimpan_ke;
        $jenis = (int) $request->jenis_transaksi;
        $tgl = $request->tgl_transaksi;

        if ($this->isHapusInventaris($sumberDana, $disimpanKe, $jenis)) {
            return $this->formHapusInventaris($sumberDana, $tgl);
        }

        if ($this->isPembelianInventaris($disimpanKe)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'form_type' => 'inventaris',
                    'fields' => ['nama_barang', 'jumlah', 'harga_satuan', 'umur_ekonomis', 'relasi'],
                ],
            ]);
        }

        if (in_array($disimpanKe, ['5.1.07.08', '5.1.07.09', '5.1.07.10'], true)) {
            $kategori = match ($sumberDana) {
                '1.2.02.01' => '2',
                '1.2.02.02' => '3',
                default => '4',
            };
            $data = $this->service->hitungPenyusutan($tgl, $kategori);
            $nominal = (float) ($data['t_penyusutan'] ?? 0);

            return response()->json([
                'success' => true,
                'data' => [
                    'form_type' => 'nominal',
                    'fields' => ['keterangan', 'nominal', 'relasi'],
                    'prefill' => ['nominal' => $nominal],
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'form_type' => 'nominal',
                'fields' => ['keterangan', 'nominal', 'relasi'],
                'prefill' => [],
            ],
        ]);
    }

    public function storeInventaris(Request $request)
    {
        $data = $request->validate([
            'tgl_transaksi' => 'required|date',
            'jenis_transaksi' => 'nullable|integer',
            'sumber_dana' => 'required|string|exists:accounts,kode_akun',
            'disimpan_ke' => 'required|string|exists:accounts,kode_akun',
            'nama_barang' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1',
            'harga_satuan' => 'required|numeric|min:0',
            'umur_ekonomis' => 'required|integer|min:0',
            'relasi' => 'nullable|string|max:255',
        ]);

        $akun = Account::where('kode_akun', $data['disimpan_ke'])->firstOrFail();

        return DB::transaction(function () use ($data, $akun, $request) {
            $hargaSatuan = (float) $data['harga_satuan'];
            $total = $hargaSatuan * (int) $data['jumlah'];

            $trx = Transaction::create([
                'tgl_transaksi' => $data['tgl_transaksi'],
                'account_debet' => $data['disimpan_ke'],
                'account_kredit' => $data['sumber_dana'],
                'transaction_group' => $data['jenis_transaksi'] ?? null,
                'keterangan_transaksi' => '('.$akun->nama_akun.') '.$data['nama_barang'],
                'relasi' => $data['relasi'] ?? '',
                'saldo' => $total,
                'id_user' => $request->user()->id,
            ]);

            $inventory = Inventory::create([
                'nama_barang' => $data['nama_barang'],
                'tgl_beli' => $data['tgl_transaksi'],
                'unit' => $data['jumlah'],
                'harsat' => $hargaSatuan,
                'umur_ekonomis' => $data['umur_ekonomis'],
                'jenis' => (string) $akun->lev3,
                'kategori' => (string) $akun->lev4,
                'status' => 'Baik',
                'tgl_validasi' => $data['tgl_transaksi'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pembelian inventaris berhasil disimpan.',
                'data' => [
                    'transaksi_id' => $trx->id,
                    'inventory_id' => $inventory->id,
                    'total' => $total,
                ],
            ], 201);
        });
    }

    public function storeHapusInventaris(Request $request, Inventory $inventory)
    {
        $data = $request->validate([
            'tgl_transaksi' => 'required|date',
            'alasan' => 'required|in:'.implode(',', self::ALASAN_HAPUS),
            'unit' => 'required|integer|min:1',
            'harsat' => 'required|numeric|min:0',
            'harga_jual' => 'nullable|numeric|min:0',
        ]);

        if ($data['unit'] > (int) $inventory->unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit yang dihapus melebihi stok tersedia.',
            ], 422);
        }

        $sumberDana = $request->input('sumber_dana');
        $disimpanKe = $request->input('disimpan_ke');
        if (! $sumberDana || ! $disimpanKe) {
            return response()->json([
                'success' => false,
                'message' => 'sumber_dana dan disimpan_ke wajib diisi.',
            ], 422);
        }

        return DB::transaction(function () use ($data, $inventory, $sumberDana, $disimpanKe, $request) {
            $tgl = $data['tgl_transaksi'];
            $alasan = $data['alasan'];
            $unitHapus = (int) $data['unit'];
            $harsat = (float) $data['harsat'];
            $hargaJual = (float) ($data['harga_jual'] ?? 0);
            $sisaUnit = (int) $inventory->unit - $unitHapus;
            $nilaiBukuTot = $harsat * $unitHapus;
            $now = now();
            $userId = $request->user()->id;
            $trxIds = [];

            if (! in_array($alasan, ['rusak'], true)) {
                $trx = Transaction::create([
                    'tgl_transaksi' => $tgl,
                    'account_debet' => $disimpanKe,
                    'account_kredit' => $sumberDana,
                    'keterangan_transaksi' => "Penghapusan {$unitHapus} unit {$inventory->nama_barang} karena {$alasan}",
                    'saldo' => $nilaiBukuTot,
                    'id_user' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $trxIds[] = $trx->id;
            }

            if ($sisaUnit > 0) {
                $inventory->update([
                    'unit' => $sisaUnit,
                    'tgl_validasi' => $tgl,
                ]);
                if ($alasan !== 'revaluasi') {
                    Inventory::create([
                        'nama_barang' => $inventory->nama_barang,
                        'tgl_beli' => $inventory->tgl_beli,
                        'unit' => $unitHapus,
                        'harsat' => $inventory->harsat,
                        'umur_ekonomis' => $inventory->umur_ekonomis,
                        'jenis' => $inventory->jenis,
                        'kategori' => $inventory->kategori,
                        'status' => ucfirst($alasan),
                        'tgl_validasi' => $tgl,
                    ]);
                }
            } elseif ($alasan !== 'revaluasi') {
                $inventory->update([
                    'status' => ucfirst($alasan),
                    'tgl_validasi' => $tgl,
                ]);
            }

            if ($alasan === 'revaluasi' && $hargaJual > 0) {
                Inventory::create([
                    'nama_barang' => $inventory->nama_barang,
                    'tgl_beli' => $tgl,
                    'unit' => $unitHapus,
                    'harsat' => $hargaJual / $unitHapus,
                    'umur_ekonomis' => $inventory->umur_ekonomis,
                    'jenis' => $inventory->jenis,
                    'kategori' => $inventory->kategori,
                    'status' => 'Baik',
                    'tgl_validasi' => $tgl,
                ]);

                $detail = $this->service->hitungItemSatuan($inventory, $tgl);
                $nilaiBukuPerUnit = (float) ($detail['nilai_buku'] ?? 0) / max((int) $inventory->unit, 1);
                $nilaiBukuTotal = $nilaiBukuPerUnit * $unitHapus;
                $selisih = $hargaJual - $nilaiBukuTotal;

                if (abs($selisih) >= 0.01) {
                    $trxSelisih = Transaction::create([
                        'tgl_transaksi' => $tgl,
                        'account_debet' => '1.1.01.01',
                        'account_kredit' => '4.3.01.01',
                        'keterangan_transaksi' => "Revaluasi {$unitHapus} unit {$inventory->nama_barang}",
                        'saldo' => abs($selisih),
                        'id_user' => $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $trxIds[] = $trxSelisih->id;
                }
            }

            if ($alasan === 'dijual' && $hargaJual > 0) {
                $trxJual = Transaction::create([
                    'tgl_transaksi' => $tgl,
                    'account_debet' => '1.1.01.01',
                    'account_kredit' => '4.2.01.04',
                    'keterangan_transaksi' => "Penjualan {$unitHapus} unit {$inventory->nama_barang}",
                    'saldo' => $hargaJual,
                    'id_user' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $trxIds[] = $trxJual->id;
            }

            $msg = $alasan === 'dijual'
                ? "Penjualan {$unitHapus} unit {$inventory->nama_barang}"
                : "Penghapusan {$unitHapus} unit {$inventory->nama_barang} karena {$alasan}";

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => [
                    'transaksi_ids' => $trxIds,
                    'inventory_id' => $inventory->id,
                    'sisa_unit' => $sisaUnit,
                ],
            ], 201);
        });
    }

    private function formHapusInventaris(string $sumberDana, string $tgl): JsonResponse
    {
        $kode = explode('.', $sumberDana);
        if (str_starts_with($sumberDana, '1.2.02.')) {
            $jenis = ((int) ($kode[2] ?? 2)) - 1;
            $kategori = ((int) ($kode[3] ?? 1)) + 1;
        } else {
            $jenis = (int) ($kode[2] ?? 1);
            $kategori = (int) ($kode[3] ?? 1);
        }

        $inventories = Inventory::all()
            ->filter(fn ($inv) => (int) $inv->jenis === $jenis && (int) $inv->kategori === $kategori)
            ->filter(fn ($inv) => in_array($inv->status, ['Baik', 'Rusak'], true))
            ->sortBy('tgl_beli')
            ->values()
            ->map(function ($inv) use ($tgl) {
                $detail = $this->service->hitungItemSatuan($inv, $tgl);

                return [
                    'id' => $inv->id,
                    'nama_barang' => $inv->nama_barang,
                    'unit' => (int) $inv->unit,
                    'harsat' => (float) $inv->harsat,
                    'nilai_buku' => (float) ($detail['nilai_buku'] ?? 0),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'form_type' => 'hapus_inventaris',
                'fields' => ['inventory_id', 'alasan', 'unit', 'harsat', 'harga_jual'],
                'inventaris_list' => $inventories,
            ],
        ]);
    }

    private function isHapusInventaris(string $sumberDana, string $disimpanKe, int $jenis): bool
    {
        $sumberOk = str_starts_with($sumberDana, '1.2.01.')
            || str_starts_with($sumberDana, '1.2.02.');

        return $sumberOk
            && str_starts_with($disimpanKe, '5.3.02.01')
            && $jenis === 2;
    }

    private function isPembelianInventaris(string $disimpanKe): bool
    {
        return str_starts_with($disimpanKe, '1.2.01')
            || str_starts_with($disimpanKe, '1.2.03');
    }
}
