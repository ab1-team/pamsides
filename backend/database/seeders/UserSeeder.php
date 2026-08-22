<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [

            [
                'name'  => 'User Admin',
                'email' => 'admin@pamsides.test',
                'role'  => 'admin',
            ],

            [
                'name'  => 'Budi Surveyor',
                'email' => 'surveyor@pamsides.test',
                'role'  => 'surveyor',
            ],

            [
                'name'  => 'Ini Teknisi',
                'email' => 'teknisi@pamsides.test',
                'role'  => 'teknisi',
            ],

            [
                'name'  => 'Andi Pelanggan',
                'email' => 'pelanggan@pamsides.test',
                'role'  => 'pelanggan',
            ],
        ];

        foreach ($users as $data) {

            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    ...$data,
                    'password' => Hash::make('password')
                ]
            );
        }
    }
}
