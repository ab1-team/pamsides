<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track tabel `installation_ticket_histories` yang sudah ada di DB live
 * (dibuat manual via SQL/phpmyadmin) ke dalam version control Laravel.
 *
 * Tabel ini menyimpan snapshot paket lama setiap kali admin mengubah
 * paket pelanggan. Paket aktif tetap dibaca dari `installation_tickets.package_id`.
 *
 * Skema final sesuai DB live (19 kolom):
 *  - id (PK)
 *  - installation_ticket_id (FK → installation_packages via ticket)
 *  - customer_id (FK nullable → customers)
 *  - package_id (FK → installation_packages)            => paket LAMA
 *  - new_package_id (FK nullable → installation_packages) => paket BARU (null = 'initial')
 *  - package_name, installation_fee, monthly_abodemen, late_penalty (snapshot paket lama)
 *  - effective_from, effective_until (periode aktif paket lama)
 *  - total_paid_on_old_package (sum payments confirmed ticket)
 *  - total_billed_on_old_package (sum monthly_bills customer)
 *  - remaining_on_old_package (= total_billed − total_paid, tidak kurang dari 0)
 *  - change_type ENUM('initial','upgrade','downgrade','reset')
 *  - reason (TEXT nullable)
 *  - changed_by (FK nullable → users)
 *  - created_at, updated_at
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: tabel sudah dibuat manual di DB, cukup daftarkan agar
        // tracked oleh Laravel dan sinkron dengan Model.
        // Tidak membuat ulang — `migrate:fresh` akan drop tabel ini juga
        // karena tidak ada di snapshot migraion manapun.
        if (! Schema::hasTable('installation_ticket_histories')) {
            Schema::create('installation_ticket_histories', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->unsignedBigInteger('installation_ticket_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('package_id');          // paket lama (snapshot)
                $table->unsignedBigInteger('new_package_id')->nullable(); // paket baru (null = initial)

                $table->string('package_name', 100);
                $table->decimal('installation_fee', 12, 2);
                $table->decimal('monthly_abodemen', 12, 2);
                $table->decimal('late_penalty', 12, 2);

                $table->timestamp('effective_from')->nullable();
                $table->timestamp('effective_until')->nullable();

                $table->decimal('total_paid_on_old_package', 12, 2);
                $table->decimal('total_billed_on_old_package', 12, 2);
                $table->decimal('remaining_on_old_package', 12, 2);

                $table->enum('change_type', ['initial', 'upgrade', 'downgrade', 'reset']);

                $table->text('reason')->nullable();
                $table->unsignedBigInteger('changed_by')->nullable();

                $table->timestamps();

                // Indexes
                $table->index('installation_ticket_id');
                $table->index('customer_id');
                $table->index('package_id');
                $table->index('new_package_id');
                $table->index('changed_by');
                $table->index('change_type');
                $table->index('effective_from');
                $table->index('effective_until');
            });
        }
    }

    public function down(): void
    {
        // HATI-HATI: drop hanya jika Anda yakin tidak ada data penting.
        // Di-disable default untuk mencegah kehilangan data historis.
        // Uncomment manual jika memang ingin reset total.
        // Schema::dropIfExists('installation_ticket_histories');
    }
};