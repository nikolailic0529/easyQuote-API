<?php

namespace App\Domain\App\Commands;

use App\Domain\Authentication\Commands\CreateClientCredentialsCommand;
use App\Domain\Authentication\Commands\CreatePersonalAccessClientCommand;
use App\Domain\Authorization\Commands\UpdateRolesCommand;
use App\Domain\Company\Commands\UpdateCompaniesCommand;
use App\Domain\ExchangeRate\Commands\UpdateExchangeRatesCommand;
use App\Domain\QuoteFile\Commands\UpdateDocumentMappingCommand;
use App\Domain\Search\Commands\RebuildSearchCommand;
use App\Domain\Template\Commands\ResetTaskTemplatesCommand;
use App\Domain\Template\Commands\UpdateRescueQuoteTemplatesCommand;
use App\Domain\Template\Commands\UpdateTemplateFieldsCommand;
use App\Domain\Template\Commands\UpdateTemplatesAssetsCommand;
use App\Domain\Vendor\Commands\UpdateVendorsCommand;
use App\Domain\Worldwide\Commands\ValidateOpportunitiesCommand;
use Database\Seeders\AssetCategorySeeder;
use Database\Seeders\BusinessDivisionSeeder;
use Database\Seeders\CancelSalesOrderReasonSeeder;
use Database\Seeders\CompanyCategorySeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\ContractTypeSeeder;
use Database\Seeders\CountryFlagSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\CustomFieldSeeder;
use Database\Seeders\DateDaySeeder;
use Database\Seeders\DateMonthSeeder;
use Database\Seeders\DateWeekSeeder;
use Database\Seeders\DocumentProcessorDriverSeeder;
use Database\Seeders\HpeContractTemplatesSeeder;
use Database\Seeders\IndustrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\MySQLSeeder;
use Database\Seeders\PipelineSeeder;
use Database\Seeders\RecurrenceTypeSeeder;
use Database\Seeders\SalesUnitSeeder;
use Database\Seeders\SpaceSeeder;
use Database\Seeders\StateSeeder;
use Database\Seeders\SystemSettingSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\TemplateFieldTypeSeeder;
use Database\Seeders\TimezoneSeeder;
use Database\Seeders\VendorSeeder;
use Database\Seeders\WorldwideQuoteTemplateSeeder;
use Database\Seeders\WorldwideSalesOrderTemplateSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Foundation\Console\OptimizeClearCommand;
use Illuminate\Foundation\Console\OptimizeCommand;
use Symfony\Component\Console\Input\InputOption;

class UpdateApplicationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'eq:update';

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
     */
    public function handle(): int
    {
        $this->call('migrate', [
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
           '--class' => SystemSettingSeeder::class,
           '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
           '--class' => DocumentProcessorDriverSeeder::class,
           '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => DateDaySeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => DateWeekSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => DateMonthSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => RecurrenceTypeSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => MySQLSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => TeamSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => TimezoneSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => CurrencySeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => LanguageSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => CountrySeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => StateSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
           '--class' => CountryFlagSeeder::class,
           '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => BusinessDivisionSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => ContractTypeSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => TemplateFieldTypeSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => VendorSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => CompanyCategorySeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => IndustrySeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => CompanySeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => HpeContractTemplatesSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => WorldwideQuoteTemplateSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => WorldwideSalesOrderTemplateSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => AssetCategorySeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => CustomFieldSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => CancelSalesOrderReasonSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => SpaceSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => SalesUnitSeeder::class,
            '--force' => true,
        ]);

        $this->call(SeedCommand::class, [
            '--class' => PipelineSeeder::class,
            '--force' => true,
        ]);

        $this->call(UpdateCompaniesCommand::class);
        $this->call(UpdateVendorsCommand::class);
        $this->call(UpdateRolesCommand::class);

        if ($this->shouldntSkip('update-exchange-rates')) {
            $this->call(UpdateExchangeRatesCommand::class);
        }

        $this->call(UpdateDocumentMappingCommand::class);
        $this->call(UpdateTemplateFieldsCommand::class);
        $this->call(UpdateRescueQuoteTemplatesCommand::class);
        $this->call(UpdateTemplatesAssetsCommand::class);
        $this->call(ResetTaskTemplatesCommand::class);
        $this->call(ValidateOpportunitiesCommand::class);
        $this->call(CreatePersonalAccessClientCommand::class);
        $this->call(CreateClientCredentialsCommand::class);

        if ($this->shouldntSkip('rebuild-search-mapping')) {
            $this->call(RebuildSearchCommand::class);
        }

        $this->call(OptimizeClearCommand::class);
        $this->call(OptimizeCommand::class);

        return Command::SUCCESS;
    }

    protected function shouldntSkip(string $action): bool
    {
        return !$this->shouldSkip($action);
    }

    protected function shouldSkip(string $action): bool
    {
        return in_array($action, $this->option('skip'), true);
    }

    protected function getOptions()
    {
        return [
            new InputOption('--skip', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, description: 'The actions to skip'),
        ];
    }
}
