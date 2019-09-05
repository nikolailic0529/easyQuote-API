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

        $this->call(UsersSeeder::class);
        $this->command->info('Seeded the users!');

        $this->call(LanguagesSeeder::class);
        $this->command->info('Seeded the languages!');

        $this->call(CurrenciesSeeder::class);
        $this->command->info('Seeded the currencies!');

        $this->call(QuoteFileFormatsSeeder::class);
        $this->command->info('Seeded the file formats!');

        $this->call(ImportableColumnsSeeder::class);
        $this->command->info('Seeded the file importable columns!');

        $this->call(VendorsSeeder::class);
        $this->command->info('Seeded the vendors!');

        $this->call(CompaniesSeeder::class);
        $this->command->info('Seeded the companies!');

        $this->call(CountryLanguageSeeder::class);
        $this->command->info('Seeded the links country → language!');

        $this->call(DataSelectSeparatorsSeeder::class);
        $this->command->info('Seeded the data select separators for csv files!');

        $this->call(TemplateFieldsSeeder::class);
        $this->command->info('Seeded the system defined template fields!');
    } 
}
