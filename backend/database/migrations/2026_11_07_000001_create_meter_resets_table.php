<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat reset / ganti meteran.
     *
     * Satu baris = satu kejadian reset untuk satu pelanggan pada satu periode.
     * Angka akhir meter LAMA disimpan di sini karena `meter_readings` hanya
     * boleh punya satu baris per pelanggan per bulan (unique constraint) —
     * baris itu nanti dipakai untuk meter BARU.
     */
    public function up(): void
    {
        Schema::create('meter_resets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();

            $table->integer('period_year');
            $table->integer('period_month');

            // Meter awal periode (meter akhir bulan sebelumnya / nilai terakhir yang diketahui).
            $table->decimal('previous_meter_value', 10, 2);

            // Angka akhir meter LAMA saat dihentikan.
            $table->decimal('old_meter_final', 10, 2);

            // Angka awal meter BARU (biasanya 0).
            $table->decimal('new_meter_start', 10, 2)->default(0);

            $table->text('reason');

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index(
                ['customer_id', 'period_year', 'period_month'],
                'meter_resets_customer_period_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_resets');
    }
};
