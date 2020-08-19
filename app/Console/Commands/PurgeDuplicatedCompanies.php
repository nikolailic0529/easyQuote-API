<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Customer\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeDuplicatedCompanies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:purge-duplicated-companies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge duplicated companies';

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
        /** @var \Illuminate\Database\Eloquent\Builder */
        $duplicatedNames = Company::query()->whereSource(Customer::S4_SOURCE)->groupBy('name')
            ->addSelect('name', DB::raw('COUNT(*) AS `names_count`'))->havingRaw('COUNT(*) >= ?', [1])
            ->toBase()
            ->pluck('name');

        DB::transaction(
            fn () => $duplicatedNames->each(fn ($name) => $this->handleCompanyName($name)),
            DB_TA
        );
    }

    protected function handleCompanyName(string $name): void
    {
        $company = Company::whereName($name)->whereSource(Customer::S4_SOURCE)->first();

        if (null === $company) {
            customlog(['ErrorDetails' => sprintf("Unable to find S4 Company with name '%'", $name)]);

            return;
        }

        $company->query()->whereKeyNot($company->getKey())->whereSource(Customer::S4_SOURCE)->whereName($company->name)->delete();

        customlog([
            'message' => sprintf("Duplicated S4 Companies with name '%s' have been deleted!", $company->name)
        ]);
    }
}
