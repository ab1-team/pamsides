<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\MeterReading;
use App\Models\MonthlyBill;
use App\Models\Payment;
use App\Models\SurveyResult;
use App\Models\User;
use App\Models\Village;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoPelangganSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Membuat data demo 10 pelanggan...');

            $this->ensureCoreUsers();
            $this->ensureVillages();

            $admin    = User::where('role', 'admin')->first();
            $surveyor = User::where('role', 'surveyor')->first();
            $teknisi  = User::where('role', 'teknisi')->first();
            $pkgReg   = InstallationPackage::where('name', 'Paket Reguler')->first();
            $pkgSos   = InstallationPackage::where('name', 'Paket Sosial')->first();

            $villages = Village::orderBy('id')->get();

            $tunggakanMonths = [3, 4, 5]; // Maret, April, Mei

            $plan = [
                ['status' => 'pending',     'pkg' => $pkgReg, 'tunggakan' => false],
                ['status' => 'pending',     'pkg' => $pkgSos, 'tunggakan' => false],
                ['status' => 'surveyed',    'pkg' => $pkgReg, 'tunggakan' => false],
                ['status' => 'unpaid',      'pkg' => $pkgReg, 'tunggakan' => false],
                ['status' => 'processing',  'pkg' => $pkgReg, 'tunggakan' => false],
                ['status' => 'completed',   'pkg' => $pkgReg, 'tunggakan' => false],
                ['status' => 'completed',   'pkg' => $pkgSos, 'tunggakan' => false],
                ['status' => 'completed',   'pkg' => $pkgReg, 'tunggakan' => false],
                ['status' => 'suspended',   'pkg' => $pkgReg, 'tunggakan' => true],
                ['status' => 'terminated',  'pkg' => $pkgReg, 'tunggakan' => false],
            ];

            $baseNik = 3201234567890100;
            $basePhone = '0812345';
            $now = now();

            foreach ($plan as $i => $row) {
                $status = $row['status'];
                $pkg = $row['pkg'];
                $hasTunggakan = $row['tunggakan'];

                $idx = $i + 1;
                $nik   = (string) ($baseNik + $idx);
                $phone = $basePhone . str_pad((string) (100 + $idx), 4, '0', STR_PAD_LEFT);
                $email = "demo{$idx}@pamsides.test";
                $name  = "Pelanggan Demo {$idx}";

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name'  => $name,
                        'role'  => 'pelanggan',
                        'password' => Hash::make('password'),
                    ]
                );

                $village = $villages[$i % max(1, $villages->count())];

                $orderDate = $now->copy()->subMonths(rand(2, 5))->subDays(rand(0, 25));

                $ticket = InstallationTicket::create([
                    'package_id'     => $pkg->id,
                    'user_id'        => $user->id,
                    'applicant_name' => $name,
                    'nik'            => $nik,
                    'phone'          => $phone,
                    'gender'         => $i % 2 === 0 ? 'male' : 'female',
                    'birth_place'    => 'Magelang',
                    'birth_date'     => '199' . ($i % 9) . '-0' . (($i % 9) + 1) . '-1' . ($i % 9),
                    'address'        => 'Jl. Demo No. ' . $idx . ', ' . $village->village_name,
                    'lat'            => -7.4700 + ($i * 0.001),
                    'lng'            => 110.2100 + ($i * 0.001),
                    'status'         => $status,
                    'village_id'     => $village->id,
                    'created_by'     => $admin->id,
                    'order_date'     => $orderDate->toDateString(),
                ]);

                $this->command->info("  [#{$idx}] {$status} → {$name} ({$pkg->name})");

                // SURVEYED → tambahkan survey
                if ($status === 'surveyed') {
                    SurveyResult::create([
                        'ticket_id'          => $ticket->id,
                        'surveyor_id'        => $surveyor->id,
                        'distance_to_pipe_m' => rand(5, 40),
                        'material_notes'     => 'Material standar, akses mudah ke titik sambungan.',
                        'photo_url'          => 'survey-photos/demo_' . $idx . '.jpg',
                        'surveyed_at'        => $now->copy()->subDays(rand(1, 10)),
                    ]);
                }

                // UNPAID → ada payment sebagian
                if ($status === 'unpaid') {
                    Payment::create([
                        'ticket_id'    => $ticket->id,
                        'amount'       => (int) round($pkg->installation_fee * 0.5),
                        'type'         => 'installation_fee',
                        'status'       => 'confirmed',
                        'confirmed_by' => $admin->id,
                        'paid_at'      => $now->copy()->subDays(rand(5, 15)),
                    ]);
                }

                // PROCESSING → sudah lunas + survey
                if ($status === 'processing') {
                    SurveyResult::create([
                        'ticket_id'          => $ticket->id,
                        'surveyor_id'        => $surveyor->id,
                        'distance_to_pipe_m' => rand(5, 40),
                        'material_notes'     => 'Material standar, akses mudah ke titik sambungan.',
                        'photo_url'          => 'survey-photos/demo_' . $idx . '.jpg',
                        'surveyed_at'        => $now->copy()->subDays(rand(10, 20)),
                    ]);
                    Payment::create([
                        'ticket_id'    => $ticket->id,
                        'amount'       => $pkg->installation_fee,
                        'type'         => 'installation_fee',
                        'status'       => 'confirmed',
                        'confirmed_by' => $admin->id,
                        'paid_at'      => $now->copy()->subDays(rand(2, 8)),
                    ]);
                }

                // COMPLETED → ada customer + meter_readings
                if ($status === 'completed') {
                    $initial = rand(0, 20);

                    $yearMonth = $now->copy()->format('Ym');
                    $latest = Customer::where('customer_code', 'like', 'PAM-' . $yearMonth . '-%')
                        ->orderBy('customer_code', 'desc')
                        ->first();
                    $next = $latest
                        ? str_pad((int) substr($latest->customer_code, -4) + 1, 4, '0', STR_PAD_LEFT)
                        : '0001';
                    $customerCode = 'PAM-' . $yearMonth . '-' . $next;

                    $customer = Customer::create([
                        'ticket_id'             => $ticket->id,
                        'user_id'               => $user->id,
                        'customer_code'         => $customerCode,
                        'initial_meter_reading' => $initial,
                        'meter_photo_url'       => 'meter-photos/demo_' . $idx . '.jpg',
                        'activated_at'          => $now->copy()->subDays(rand(15, 40)),
                    ]);

                    SurveyResult::create([
                        'ticket_id'          => $ticket->id,
                        'surveyor_id'        => $surveyor->id,
                        'distance_to_pipe_m' => rand(5, 40),
                        'material_notes'     => 'Material standar.',
                        'photo_url'          => 'survey-photos/demo_' . $idx . '.jpg',
                        'surveyed_at'        => $now->copy()->subDays(rand(30, 50)),
                    ]);
                    Payment::create([
                        'ticket_id'    => $ticket->id,
                        'amount'       => $pkg->installation_fee,
                        'type'         => 'installation_fee',
                        'status'       => 'confirmed',
                        'confirmed_by' => $admin->id,
                        'paid_at'      => $now->copy()->subDays(rand(20, 35)),
                    ]);

                    // Meter readings untuk Maret, April, Mei (2026) → menghasilkan monthly_bills
                    $this->seedMeterReadingsAndBills($customer, $pkg, $initial, $tunggakanMonths, false);
                }

                // SUSPENDED → punya 3 tunggakan unpaid (Maret, April, Mei)
                if ($status === 'suspended') {
                    $initial = rand(0, 20);

                    $yearMonth = $now->copy()->format('Ym');
                    $latest = Customer::where('customer_code', 'like', 'PAM-' . $yearMonth . '-%')
                        ->orderBy('customer_code', 'desc')
                        ->first();
                    $next = $latest
                        ? str_pad((int) substr($latest->customer_code, -4) + 1, 4, '0', STR_PAD_LEFT)
                        : '0001';
                    $customerCode = 'PAM-' . $yearMonth . '-' . $next;

                    $customer = Customer::create([
                        'ticket_id'             => $ticket->id,
                        'user_id'               => $user->id,
                        'customer_code'         => $customerCode,
                        'initial_meter_reading' => $initial,
                        'meter_photo_url'       => 'meter-photos/demo_' . $idx . '.jpg',
                        'activated_at'          => $now->copy()->subMonths(6),
                    ]);

                    SurveyResult::create([
                        'ticket_id'          => $ticket->id,
                        'surveyor_id'        => $surveyor->id,
                        'distance_to_pipe_m' => rand(5, 40),
                        'material_notes'     => 'Material standar.',
                        'photo_url'          => 'survey-photos/demo_' . $idx . '.jpg',
                        'surveyed_at'        => $now->copy()->subMonths(7),
                    ]);
                    Payment::create([
                        'ticket_id'    => $ticket->id,
                        'amount'       => $pkg->installation_fee,
                        'type'         => 'installation_fee',
                        'status'       => 'confirmed',
                        'confirmed_by' => $admin->id,
                        'paid_at'      => $now->copy()->subMonths(6),
                    ]);

                    $this->seedMeterReadingsAndBills($customer, $pkg, $initial, $tunggakanMonths, true);
                }

                // TERMINATED → ada payment sebagian lalu dibatalkan
                if ($status === 'terminated') {
                    Payment::create([
                        'ticket_id'    => $ticket->id,
                        'amount'       => (int) round($pkg->installation_fee * 0.3),
                        'type'         => 'installation_fee',
                        'status'       => 'confirmed',
                        'confirmed_by' => $admin->id,
                        'paid_at'      => $now->copy()->subMonths(2),
                    ]);
                }
            }

            $this->command->info('Selesai membuat 10 pelanggan demo.');
        });
    }

    private function ensureCoreUsers(): void
    {
        $core = [
            ['name' => 'User Admin',      'email' => 'admin@pamsides.test',     'role' => 'admin'],
            ['name' => 'Budi Surveyor',   'email' => 'surveyor@pamsides.test',  'role' => 'surveyor'],
            ['name' => 'Ini Teknisi',     'email' => 'teknisi@pamsides.test',   'role' => 'teknisi'],
        ];

        foreach ($core as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                array_merge($u, ['password' => Hash::make('password')])
            );
        }
    }

    private function ensureVillages(): void
    {
        $list = [
            ['village_name' => 'Sonosari', 'hamlet_name' => 'Dusun Krajan', 'address' => 'Jl. Raya Sonosari', 'phone' => '0811110001'],
            ['village_name' => 'Maguwoharjo', 'hamlet_name' => 'Dusun Ngabean', 'address' => 'Jl. Maguwo No. 1', 'phone' => '0811110002'],
            ['village_name' => 'Sinduadi', 'hamlet_name' => 'Dusun Cebongan', 'address' => 'Jl. Sinduadi No. 2', 'phone' => '0811110003'],
            ['village_name' => 'Tirtonirmolo', 'hamlet_name' => 'Dusun Kasihan', 'address' => 'Jl. Tirto No. 3', 'phone' => '0811110004'],
        ];

        foreach ($list as $v) {
            Village::firstOrCreate(['village_name' => $v['village_name']], $v);
        }
    }

    /**
     * Buat meter_readings untuk Maret-April-Mei 2026 + monthly_bills sesuai status bayar.
     * - completed: semua bill PAID
     * - suspended: semua bill UNPAID (tunggakan 3 bulan)
     */
    private function seedMeterReadingsAndBills(
        Customer $customer,
        \App\Models\InstallationPackage $pkg,
        int $initialMeter,
        array $months,
        bool $unpaid
    ): void {
        $teknisi = User::where('role', 'teknisi')->first();
        $billingService = app(BillingService::class);

        $year = 2026;
        $running = $initialMeter;

        $previousReading = null;

        foreach ($months as $month) {
            $usage = rand(8, 22);
            $running += $usage;

            $reading = MeterReading::create([
                'customer_id'   => $customer->id,
                'recorded_by'   => $teknisi->id,
                'reading_year'  => $year,
                'reading_month' => $month,
                'meter_value'   => $running,
                'photo_url'     => 'meter-readings/demo_' . $customer->id . '_' . $month . '.jpg',
                'recorded_at'   => Carbon::create($year, $month, 25),
            ]);

            $start = $previousReading
                ? $previousReading->meter_value
                : $customer->initial_meter_reading;

            $usageM3 = max(0, $running - $start);
            $usageCharge = $billingService->calculateProgressiveCharge($pkg, $usageM3);
            $abodemen = $pkg->monthly_abodemen;

            // Denda hanya jika ada tunggakan 2 bulan sebelumnya
            $penalty = 0;
            $twoMonthsAgo = $month <= 2 ? 12 + $month - 2 : $month - 2;
            $hasOldUnpaid = MonthlyBill::where('customer_id', $customer->id)
                ->where('billing_period_year', $year)
                ->where('billing_period_month', $twoMonthsAgo)
                ->where('status', 'unpaid')
                ->exists();
            if ($hasOldUnpaid) {
                $penalty = $pkg->late_penalty;
            }

            $total = $usageCharge + $abodemen + $penalty;
            $status = $unpaid ? 'unpaid' : 'paid';

            $dueDate = Carbon::create($year, $month, 1)->addMonth()->setDay(20);

            $bill = MonthlyBill::create([
                'customer_id'           => $customer->id,
                'billing_period_year'   => $year,
                'billing_period_month'  => $month,
                'meter_reading_start'   => $start,
                'meter_reading_end'     => $running,
                'usage_m3'              => $usageM3,
                'usage_charge'          => $usageCharge,
                'abodemen'              => $abodemen,
                'penalty_amount'        => $penalty,
                'total_amount'          => $total,
                'status'                => $status,
                'due_date'              => $dueDate->toDateString(),
            ]);

            if ($status === 'paid') {
                \App\Models\BillPayment::create([
                    'bill_id'      => $bill->id,
                    'amount_paid'  => $total,
                    'confirmed_by' => $teknisi->id,
                    'paid_at'      => $dueDate->copy()->subDays(rand(1, 5)),
                ]);
            }

            $previousReading = $reading;
        }
    }
}
