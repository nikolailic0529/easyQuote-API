<?php

namespace App\Console\Commands;

use App\Console\Commands\Routine\UpdateExchangeRates;
use App\Models\CancelSalesOrderReason;
use Database\Seeders\{AssetCategorySeeder,
    BusinessDivisionSeeder,
    CancelSalesOrderReasonSeeder,
    CompanySeeder,
    ContractTypeSeeder,
    CountryFlagSeeder,
    CountrySeeder,
    CurrencySeeder,
    CustomFieldSeeder,
    DateDaySeeder,
    DateMonthSeeder,
    DateWeekSeeder,
    DocumentProcessorDriverSeeder,
    HpeContractTemplatesSeeder,
    MySQLSeeder,
    PipelineSeeder,
    RecurrenceTypeSeeder,
    SalesUnitSeeder,
    SpaceSeeder,
    SystemSettingSeeder,
    TeamSeeder,
    TemplateFieldTypeSeeder,
    TimezoneSeeder,
    VendorSeeder,
    WorldwideSalesOrderTemplateSeeder,
    WorldwideQuoteTemplateSeeder};
use Illuminate\Console\Command;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Foundation\Console\{OptimizeClearCommand, OptimizeCommand};

class UpdateApplicationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run commands pipeline after a fresh app build';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->call('migrate', [
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
           '--class' => SystemSettingSeeder::class,
           '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
           '--class' => DocumentProcessorDriverSeeder::class,
           '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => DateDaySeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => DateWeekSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => DateMonthSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => RecurrenceTypeSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => MySQLSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => TeamSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => TimezoneSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => CurrencySeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => CountrySeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
           '--class' => CountryFlagSeeder::class,
           '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => BusinessDivisionSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => ContractTypeSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => TemplateFieldTypeSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => VendorSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => CompanySeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => HpeContractTemplatesSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => WorldwideQuoteTemplateSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => WorldwideSalesOrderTemplateSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => AssetCategorySeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => CustomFieldSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => CancelSalesOrderReasonSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => SpaceSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => SalesUnitSeeder::class,
            '--force' => true
        ]);

        $this->call(SeedCommand::class, [
            '--class' => PipelineSeeder::class,
            '--force' => true
        ]);

        $this->call(UpdateCompanies::class);
        $this->call(UpdateVendors::class);
        $this->call(UpdateRoles::class);
        $this->call(UpdateExchangeRates::class);

        $this->call(UpdateDocumentMapping::class);
        $this->call(UpdateTemplateFields::class);

        $this->call(UpdateRescueQuoteTemplates::class);
        $this->call(UpdateTemplatesAssets::class);

        $this->call(ResetTaskTemplates::class);

        $this->call(CreatePersonalAccessClient::class);
        $this->call(CreateClientCredentials::class);
        $this->call(RebuildSearchMapping::class);

        $this->call(OptimizeClearCommand::class);
        $this->call(OptimizeCommand::class);

        return Command::SUCCESS;
    }
}
