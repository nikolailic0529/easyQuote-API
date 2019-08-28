<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CountriesSeeder::class);
        $this->command->info('Seeded the countries!');
        
        $this->call(TimezonesSeeder::class);
        $this->command->info('Seeded the timezones!');

        $this->call(RolesSeeder::class);
        $this->command->info('Seeded the users roles!');
    }
}
