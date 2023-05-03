<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Activitylog\ActivityLogStatus;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        app(ActivityLogStatus::class)->disable();

        $this->call(DateDaySeeder::class);
        $this->command->info('Seeded the date days');

        $this->call(DateWeekSeeder::class);
        $this->command->info('Seeded the date weeks');

        $this->call(DateMonthSeeder::class);
        $this->command->info('Seeded the date months');

        $this->call(RecurrenceTypeSeeder::class);
        $this->command->info('Seeded the recurrence types');

        $this->call(AssetCategorySeeder::class);
        $this->command->info('Seeded the asset categories');

        $this->call(BusinessDivisionSeeder::class);
        $this->command->info('Seeded the business divisions');

        $this->call(TeamSeeder::class);
        $this->command->info('Seeded the default teams');

        $this->call(ContractTypeSeeder::class);
        $this->command->info('Seeded the contract types');

        $this->call(SystemSettingSeeder::class);
        $this->command->info('Seeded the default settings');

        $this->call(CountrySeeder::class);
        $this->command->info('Seeded the countries');

        $this->call(StateSeeder::class);
        $this->command->info('Seeded the states');

        $this->call(TimezoneSeeder::class);
        $this->command->info('Seeded the timezones');

        $this->call(LanguageSeeder::class);
        $this->command->info('Seeded the languages');

        $this->call(CurrencySeeder::class);
        $this->command->info('Seeded the currencies');

        $this->call(QuoteFileFormatsSeeder::class);
        $this->command->info('Seeded the file formats');

        $this->call(ImportableColumnSeeder::class);
        $this->command->info('Seeded the default importable columns');

        $this->call(MySQLSeeder::class);
        $this->command->info('Seeded the stored MySQL functions');

        $this->call(VendorSeeder::class);
        $this->command->info('Seeded the default vendors');

        $this->call(CompanyCategorySeeder::class);
        $this->command->info('Seeded the company categories');

        $this->call(IndustrySeeder::class);
        $this->command->info('Seeded the industries');

        $this->call(CompanySeeder::class);
        $this->command->info('Seeded the default companies');

        $this->call(PermissionSeeder::class);
        $this->command->info('Seeded the default permissions');

        $this->call(RoleSeeder::class);
        $this->command->info('Seeded the default roles');

        $this->call(UsersSeeder::class);
        $this->command->info('Seeded the default users');

        $this->call(DataSelectSeparatorsSeeder::class);
        $this->command->info('Seeded the data select separators');

        $this->call(TemplateFieldTypeSeeder::class);
        $this->command->info('Seeded the default template field types');

        $this->call(TemplateFieldsSeeder::class);
        $this->command->info('Seeded the default template fields');

        $this->call(QuoteTemplatesSeeder::class);
        $this->command->info('Seeded the default quote templates');

        $this->call(ContractTemplatesSeeder::class);
        $this->command->info('Seeded the default contract templates');

        $this->call(HpeContractTemplatesSeeder::class);
        $this->command->info('Seeded the default hpe contract templates');

        $this->call(WorldwideQuoteTemplateSeeder::class);
        $this->command->info('Seeded the default worldwide quote templates');

        $this->call(WorldwideSalesOrderTemplateSeeder::class);
        $this->command->info('Seeded the default worldwide contract templates');

        $this->call(CountryMarginsSeeder::class);
        $this->command->info('Seeded the default country margins');

        $this->call(SpaceSeeder::class);
        $this->command->info('Seeded the default spaces');

        $this->call(PipelineSeeder::class);
        $this->command->info('Seeded the default pipelines');

        app(ActivityLogStatus::class)->enable();
    }
}
