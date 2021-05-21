<?php

namespace Database\Seeders;

use App\Models\ContractType;
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
        activity()->disableLogging();

        $this->call(TeamSeeder::class);
        $this->command->info('Seeded the system defined Teams!');

        $this->call(AssetCategorySeeder::class);
        $this->command->info('Seeded the Asset Categories!');

        $this->call(BusinessDivisionSeeder::class);
        $this->command->info('Seeded the Business Divisions!');

        $this->call(ContractTypeSeeder::class);
        $this->command->info('Seeded the Contract Types!');

        $this->call(SystemSettingsSeeder::class);
        $this->command->info('Seeded the default system settings!');

        $this->call(CountrySeeder::class);
        $this->command->info('Seeded the countries!');

        $this->call(TimezoneSeeder::class);
        $this->command->info('Seeded the timezones!');

        $this->call(PermissionSeeder::class);
        $this->command->info('Seeded the permissions!');

        $this->call(RolesSeeder::class);
        $this->command->info('Seeded the roles!');

        $this->call(UsersSeeder::class);
        $this->command->info('Seeded the users!');

        $this->call(LanguagesSeeder::class);
        $this->command->info('Seeded the languages!');

        $this->call(CurrencySeeder::class);
        $this->command->info('Seeded the currencies!');

        $this->call(QuoteFileFormatsSeeder::class);
        $this->command->info('Seeded the file formats!');

        $this->call(ImportableColumnsSeeder::class);
        $this->command->info('Seeded the file importable columns!');

        $this->call(MySQLSeeder::class);
        $this->command->info('Seeded the stored MySQL functions!');

        $this->call(VendorSeeder::class);
        $this->command->info('Seeded the vendors!');

        $this->call(CompanySeeder::class);
        $this->command->info('Seeded the companies!');

        $this->call(DataSelectSeparatorsSeeder::class);
        $this->command->info('Seeded the data select separators for csv files!');

        $this->call(TemplateFieldTypeSeeder::class);
        $this->command->info('Seeded the template field types!');

        $this->call(TemplateFieldsSeeder::class);
        $this->command->info('Seeded the system defined template fields!');

        $this->call(QuoteTemplatesSeeder::class);
        $this->command->info('Seeded the system defined quote templates!');

        $this->call(ContractTemplatesSeeder::class);
        $this->command->info('Seeded the system defined contract templates!');

        $this->call(HpeContractTemplatesSeeder::class);
        $this->command->info('Seeded the system defined hpe contract templates!');

        $this->call(WorldwideQuoteTemplateSeeder::class);
        $this->command->info('Seeded the system defined worldwide quote templates!');

        $this->call(WorldwideSalesOrderTemplateSeeder::class);
        $this->command->info('Seeded the system defined worldwide contract templates!');
//
//        $this->call(CustomersSeeder::class);
//        $this->command->info('Seeded the S4 customers!');
//
//        $this->call(CustomersAddressesSeeder::class);
//        $this->command->info('Seeded the S4 customers addresses!');
//
//        $this->call(CustomersContactsSeeder::class);
//        $this->command->info('Seeded the S4 customers contacts!');

        $this->call(CountryMarginsSeeder::class);
        $this->command->info('Seeded the country margins!');

        $this->call(SpaceSeeder::class);
        $this->command->info('Seeded the spaces!');

        $this->call(PipelineSeeder::class);
        $this->command->info('Seeded the pipelines!');

        activity()->enableLogging();
    }
}
