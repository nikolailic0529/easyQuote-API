<?php

namespace App\Console\Commands;

use App\Console\Commands\Routine\UpdateExchangeRates;
use HpeContractTemplatesSeeder;
use Illuminate\Console\Command;

class Update extends Command
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
    protected $description = 'Run necessary commands after pulling the code';

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

        $this->call('db:seed', [
            '--class' => 'MySQLSeeder',
            '--force' => true
        ]);

        $this->call('db:seed', [
            '--class' => 'TimezonesSeeder',
            '--force' => true
        ]);

        $this->call(ImportableColumnsUpdate::class);
        $this->call(CompaniesUpdate::class);
        $this->call(VendorsUpdate::class);
        $this->call(RolesUpdate::class);
        $this->call(SystemSettingsSync::class);
        $this->call(UpdateExchangeRates::class);
        $this->call(TemplateFieldsUpdate::class);
        $this->call(TemplatesUpdate::class);

        $this->call('db:seed', [
            '--class' => HpeContractTemplatesSeeder::class,
            '--force' => true
        ]);

        $this->call(UpdateTemplatesAssets::class);
        $this->call(CurrenciesUpdate::class);
        $this->call(CountriesUpdate::class);
        $this->call(ResetTaskTemplates::class);
        $this->call(CreatePersonalAccessClient::class);
        $this->call(CreateClientCredentials::class);
        $this->call(ReindexCommand::class);
        $this->call(CacheRelations::class);
        $this->call(UpdateQuoteGroupDescription::class);

        $this->call('cache:clear');
        $this->call('optimize:clear');
    }
}
