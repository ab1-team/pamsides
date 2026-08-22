<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\InstallationTicket;
use Illuminate\Database\Seeder;

class InstallationTicketSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil user 'Andi Pelanggan' yang dibuat di UserSeeder
        $user = User::where('email', 'pelanggan@pamsides.test')->first();
        $admin = User::where('role', 'admin')->first();

        if ($user) {
            InstallationTicket::updateOrCreate(
                ['user_id' => $user->id], 
                [
                    'package_id'     => 1, 
                    'applicant_name' => $user->name, 
                    'nik'            => '3201234567890001',
                    'address'        => 'Jl. Mawar No. 12, Magelang',
                    'lat'            => 0, 
                    'lng'            => 0, 
                    'status'         => 'draft', 
                    'created_by'     => $admin->id ?? 1,
                ]
            );
        }
    }
}
