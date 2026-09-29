<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\InstallationTicket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'pelanggan@pamsides.test')->first();
        $adminId = User::where('email', 'admin@pamsides.test')->value('id');

        if (! $user || ! $adminId) {
            return;
        }

        $existing = InstallationTicket::where('nik', '3201234567890001')->first();

        if ($existing) {
            $ticket = $existing;
        } else {
            $ticket = InstallationTicket::create([
                'nik'            => '3201234567890001',
                'package_id'     => 1,
                'user_id'        => $user->id,
                'applicant_name' => $user->name,
                'phone'          => '08123456789',
                'gender'         => 'male',
                'birth_place'    => 'Magelang',
                'birth_date'     => '1998-05-07',
                'address'        => 'Jl. Mawar No. 12, Magelang',
                'lat'            => 0,
                'lng'            => 0,
                'status'         => 'draft',
                'created_by'     => $adminId,
            ]);
        }

        Customer::updateOrCreate(
            ['user_id' => $user->id],
            [
                'ticket_id'             => $ticket->id,
                'customer_code'         => 'PAM-' . date('Ym') . '-' . Str::padLeft($user->id, 4, '0'),
                'initial_meter_reading' => 0,
                'activated_at'          => now(),
            ]
        );
    }
}
