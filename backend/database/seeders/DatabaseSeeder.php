<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            InstallationPackageSeeder::class,
            InstallationTicketSeeder::class, 
            CustomerSeeder::class,
            SettingsSeeder::class,
            AccountsTableSeeder::class,   
            AkunLevel1Seeder::class,
            AkunLevel2Seeder::class,
            AkunLevel3Seeder::class,
            EbudgetingSeeder::class,
            JenisTransactionsSeeder::class,
            MasterArusKasSeeder::class,
        ]);
    }
}
