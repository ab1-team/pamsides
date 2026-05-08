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

        if ($user) {
            
            $ticket = InstallationTicket::updateOrCreate(
                ['nik' => '3201234567890001'], 
                [
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
                    'created_by'     => 1, 
                ]
            );

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
}
