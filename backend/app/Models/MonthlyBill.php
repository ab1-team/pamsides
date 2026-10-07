<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyBill extends Model
{
    use SoftDeletes;

    /**
     * Kolom tanggal di model ini perlu di-cast eksplisit.
     *
     * Tanpa cast, `due_date` dikembalikan sebagai string polos
     * (`"2024-11-27"`), bukan objek Carbon. Kode yang memformatnya
     * sebagai tanggal — `DashboardController::popupTagihan()` sudah
     * memakai `$b->due_date?->toDateString()` — lalu menjalankan method
     * pada string dan melempar Error 500.
     */
    protected $casts = [
        'due_date' => 'date',
    ];

    protected $fillable = [
        'customer_id',
        'billing_period_year',
        'billing_period_month',
        'meter_reading_start',
        'meter_reading_end',
        'usage_m3',
        'usage_charge',
        'abodemen',
        'penalty_amount',
        'total_amount',
        'status',
        'due_date',
    ];

    /**
     * Label periode tagihan dalam Bahasa Indonesia, mis. "Agustus 2026".
     * Dipakai untuk menempelkan periode tagihan pada keterangan jurnal,
     * karena tanggal transaksi = tanggal bayar, bukan bulan tagihan.
     */
    public function periodLabel(): string
    {
        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $month = $months[(int) $this->billing_period_month] ?? null;

        return $month ? $month.' '.$this->billing_period_year : '';
    }

    /**
     * Keterangan jurnal pembayaran tagihan, mis.
     * "Tagihan Denda bulan Agustus 2026 an. Yuli Iswanto (1.04.0996)".
     *
     * Tanggal jurnal = tanggal bayar, jadi periode tagihan ditulis eksplisit
     * di keterangan; nama + kode pelanggan supaya jurnal tetap terbaca.
     * Kalau bulan tidak valid, kembalikan string kosong agar pemanggil
     * bisa memutuskan fallback-nya.
     */
    public function paymentDescription(string $jenis, string $kode, string $nama = ''): string
    {
        $periode = $this->periodLabel();

        if ($periode === '') {
            return '';
        }

        $keterangan = trim('Tagihan '.$jenis.' bulan '.$periode);

        if ($nama !== '') {
            $keterangan .= ' an. '.$nama;
        }

        return $keterangan.' ('.$kode.')';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function billPayments()
    {
        return $this->hasMany(BillPayment::class, 'bill_id');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'reverence');
    }
}
