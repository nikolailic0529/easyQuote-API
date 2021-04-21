<?php

namespace App\Queries;

use App\DTO\UnifiedQuote\UnifiedQuotesRequestData;
use App\Enum\QuoteStatus;
use App\Http\Query\{ActiveFirst,
    DefaultOrderBy,
    OrderByCompleteness,
    OrderByCustomerName,
    OrderByRfqNumber,
    OrderByUpdatedAt,
    Quote\OrderByCompanyName};
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UnifiedQuoteQueries
{
    protected ConnectionInterface $connection;

    protected ValidatorInterface $validator;

    protected Pipeline $pipeline;

    public function __construct(ConnectionInterface $connection, ValidatorInterface $validator, Pipeline $pipeline)
    {
        $this->connection = $connection;
        $this->validator = $validator;
        $this->pipeline = $pipeline;
    }

    public function paginateDraftedQuotesQuery(UnifiedQuotesRequestData $requestData = null, Request $request = null): BaseBuilder
    {
        $requestData ??= new UnifiedQuotesRequestData();
        $request ??= new Request();

        $violations = $this->validator->validate($requestData);

        if (count($violations)) {
            throw new ValidationFailedException($requestData, $violations);
        }

        /** @var Builder[] $queries */
        $queries = [];

        if ($requestData->get_rescue_entities) {
            $queries[] = $this->draftedRescueQuotesListingQuery()
                ->when(false === $requestData->get_any_owner_entities, function (Builder $builder) use ($requestData) {
                    $builder->where(function (Builder $builder) use ($requestData) {

                        $builder->where($builder->qualifyColumn('user_id'), $requestData->acting_user_id)
                            ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $requestData->acting_user_led_teams));

                    });
                });
        }

        if ($requestData->get_worldwide_entities) {
            $queries[] = $this->draftedWorldwideQuotesListingQuery()
                ->when(false === $requestData->get_any_owner_entities, function (Builder $builder) use ($requestData) {
                    $builder->where(function (Builder $builder) use ($requestData) {

                        $builder->where($builder->qualifyColumn('user_id'), $requestData->acting_user_id)
                            ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $requestData->acting_user_led_teams));

                    });
                });
        }

        /** @var Builder $unifiedQuery */
        $unifiedQuery = array_shift($queries);

        foreach ($queries as $query) {
            $unifiedQuery->unionAll($query->toBase());
        }

        $this->pipeline
            ->send($unifiedQuery)
            ->through([
                ActiveFirst::class,
                OrderByCompanyName::class,
                OrderByCustomerName::class,
                OrderByRfqNumber::class,
                OrderByCompleteness::class,
                (new OrderByUpdatedAt)->qualifyColumnName(false),
                new DefaultOrderBy('updated_at')
            ])
            ->thenReturn();

        return $unifiedQuery->toBase();
    }

    public function paginateSubmittedQuotesQuery(UnifiedQuotesRequestData $requestData = null, Request $request = null): BaseBuilder
    {
        $requestData ??= new UnifiedQuotesRequestData();
        $request ??= new Request();

        $violations = $this->validator->validate($requestData);

        if (count($violations)) {
            throw new ValidationFailedException($requestData, $violations);
        }

        /** @var Builder[] $queries */
        $queries = [];

        if ($requestData->get_rescue_entities) {
            $queries[] = $this->submittedRescueQuotesListingQuery()
                ->when(false === $requestData->get_any_owner_entities, function (Builder $builder) use ($requestData) {
                    $builder->where(function (Builder $builder) use ($requestData) {

                        $builder->where($builder->qualifyColumn('user_id'), $requestData->acting_user_id)
                            ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $requestData->acting_user_led_teams));

                    });
                });
        }

        if ($requestData->get_worldwide_entities) {
            $queries[] = $this->submittedWorldwideQuotesListingQuery()
                ->when(false === $requestData->get_any_owner_entities, function (Builder $builder) use ($requestData) {
                    $builder->where(function (Builder $builder) use ($requestData) {

                        $builder->where($builder->qualifyColumn('user_id'), $requestData->acting_user_id)
                            ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $requestData->acting_user_led_teams));

                    });
                });
        }

        /** @var Builder $unifiedQuery */
        $unifiedQuery = array_shift($queries);

        foreach ($queries as $query) {
            $unifiedQuery->unionAll($query->toBase());
        }

        $this->pipeline
            ->send($unifiedQuery)
            ->through([
                ActiveFirst::class,
                OrderByCompanyName::class,
                OrderByCustomerName::class,
                OrderByRfqNumber::class,
                OrderByCompleteness::class,
                (new OrderByUpdatedAt)->qualifyColumnName(false),
                new DefaultOrderBy('updated_at')
            ])
            ->thenReturn();

        return $unifiedQuery->toBase();
    }

    public function paginateExpiringQuotesQuery(UnifiedQuotesRequestData $requestData = null, Request $request = null): BaseBuilder
    {
        $requestData ??= new UnifiedQuotesRequestData();
        $request ??= new Request();

        $violations = $this->validator->validate($requestData);

        if (count($violations)) {
            throw new ValidationFailedException($requestData, $violations);
        }

        /** @var Builder[] $queries */
        $queries = [];

        if ($requestData->get_rescue_entities) {
            $queries[] = $this->expiringRescueQuotesListingQuery()
                ->when(false === $requestData->get_any_owner_entities, function (Builder $builder) use ($requestData) {
                    $builder->where(function (Builder $builder) use ($requestData) {

                        $builder->where($builder->qualifyColumn('user_id'), $requestData->acting_user_id)
                            ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $requestData->acting_user_led_teams));

                    });
                });
        }

        if ($requestData->get_worldwide_entities) {
            $queries[] = $this->expiringWorldwideQuotesListingQuery()
                ->when(false === $requestData->get_any_owner_entities, function (Builder $builder) use ($requestData) {
                    $builder->where(function (Builder $builder) use ($requestData) {

                        $builder->where($builder->qualifyColumn('user_id'), $requestData->acting_user_id)
                            ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $requestData->acting_user_led_teams));

                    });
                });
        }

        /** @var Builder $unifiedQuery */
        $unifiedQuery = array_shift($queries);

        foreach ($queries as $query) {
            $unifiedQuery->unionAll($query->toBase());
        }

        $this->pipeline
            ->send($unifiedQuery)
            ->through([
                ActiveFirst::class,
                OrderByCompanyName::class,
                OrderByCustomerName::class,
                OrderByRfqNumber::class,
                OrderByCompleteness::class,
                (new OrderByUpdatedAt)->qualifyColumnName(false),
                new DefaultOrderBy('updated_at')
            ])
            ->thenReturn();

        return $unifiedQuery->toBase();
    }

    public function expiringRescueQuotesListingQuery(): Builder
    {
        return Quote::query()
            ->leftJoin('quote_versions', function (JoinClause $joinClause) {
                $joinClause->on('quote_versions.id', 'quotes.active_version_id');
            })
            ->join('customers', function (JoinClause $joinClause) {
                $joinClause->on('customers.id', 'quotes.customer_id');
            })
            ->leftJoin('companies', function (JoinClause $joinClause) {
                $joinClause->on('companies.id', 'quote_versions.company_id')
                    ->orOn('companies.id', 'quotes.company_id');
            })
//            ->whereNull('quotes.submitted_at')
            ->where('customers.valid_until', '<=', today())
            ->select([
                'quotes.id as id',
                'quotes.user_id as user_id',
                DB::raw("'Rescue' as business_division"),
                DB::raw("'Contract' as contract_type"),
                DB::raw('NULL as opportunity_id'),
                'customers.id as customer_id',
                'customers.name as customer_name',
                'companies.name as company_name',
                'customers.rfq as rfq_number',
                DB::raw('DATE(customers.valid_until) as valid_until_date'),
                DB::raw('COALESCE(quote_versions.completeness, quotes.completeness) as completeness'),
                'quotes.updated_at as updated_at',
                'quotes.activated_at as activated_at',
                'quotes.is_active as is_active'
            ]);
    }

    public function expiringWorldwideQuotesListingQuery(): Builder
    {
        return WorldwideQuote::query()
            ->join('opportunities', function (JoinClause $joinClause) {
                $joinClause->on('opportunities.id', 'worldwide_quotes.opportunity_id');
            })
            ->join('worldwide_quote_versions as quote_active_version', function (JoinClause $joinClause) {
                $joinClause->on('quote_active_version.id', 'worldwide_quotes.active_version_id');
            })
            ->leftJoin('companies as company', function (JoinClause $joinClause) {
                $joinClause->on('company.id', 'quote_active_version.company_id');
            })
            ->leftJoin('companies as primary_account', function (JoinClause $joinClause) {
                $joinClause->on('primary_account.id', 'opportunities.primary_account_id');
            })
            ->join('contract_types', function (JoinClause $joinClause) {
                $joinClause->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
//            ->whereNull('worldwide_quotes.submitted_at')
            ->where('opportunities.opportunity_closing_date', '<=', today())
            ->where('worldwide_quotes.status', QuoteStatus::ALIVE)
            ->select([
                'worldwide_quotes.id as id',
                'worldwide_quotes.user_id as user_id',
                DB::raw("'Worldwide' as business_division"),
                'contract_types.type_short_name as contract_type',
                'opportunities.id as opportunity_id',
                DB::raw('NULL as customer_id'),
                'primary_account.name as customer_name',
                'company.name as company_name',
                'worldwide_quotes.quote_number as rfq_number',
                DB::raw('DATE(opportunities.opportunity_closing_date) as valid_until_date'),
                'quote_active_version.completeness as completeness',
                'worldwide_quotes.updated_at as updated_at',
                'worldwide_quotes.activated_at as activated_at',
                'worldwide_quotes.is_active as is_active'
            ]);
    }

    public function draftedRescueQuotesListingQuery(): Builder
    {
        return Quote::query()
            ->leftJoin('quote_versions', function (JoinClause $joinClause) {
                $joinClause->on('quote_versions.id', 'quotes.active_version_id');
            })
            ->join('customers', function (JoinClause $joinClause) {
                $joinClause->on('customers.id', 'quotes.customer_id');
            })
            ->leftJoin('companies', function (JoinClause $joinClause) {
                $joinClause->on('companies.id', 'quote_versions.company_id')
                    ->orOn('companies.id', 'quotes.company_id');
            })
            ->whereNull('quotes.submitted_at')
            ->select([
                'quotes.id as id',
                'quotes.user_id',
                DB::raw("'Rescue' as business_division"),
                DB::raw("'Contract' as contract_type"),
                DB::raw('NULL as opportunity_id'),
                'customers.id as customer_id',
                'customers.name as customer_name',
                'companies.name as company_name',
                'customers.rfq as rfq_number',
                DB::raw('COALESCE(quote_versions.completeness, quotes.completeness) as completeness'),

                'quotes.active_version_id as active_version_id',
                'quote_versions.distributor_file_id as active_version_distributor_file_id',
                'quote_versions.schedule_file_id as active_version_schedule_file_id',

                'quotes.distributor_file_id as distributor_file_id',
                'quotes.schedule_file_id as schedule_file_id',

                DB::raw('NULL as has_distributor_files'),
                DB::raw('NULL as has_schedule_files'),

                'quotes.updated_at as updated_at',
                'quotes.activated_at as activated_at',
                'quotes.is_active as is_active'
            ]);
    }

    public function draftedWorldwideQuotesListingQuery(): Builder
    {
        $distributorFileExistenceQuery = WorldwideDistribution::query()->selectRaw('1')
            ->whereColumn('worldwide_distributions.worldwide_quote_id', 'worldwide_quotes.active_version_id')
            ->has('opportunitySupplier')
            ->has('distributorFile')
            ->limit(1);

        $scheduleFileExistenceQuery = WorldwideDistribution::query()->selectRaw('1')
            ->whereColumn('worldwide_distributions.worldwide_quote_id', 'worldwide_quotes.active_version_id')
            ->has('opportunitySupplier')
            ->has('scheduleFile')
            ->limit(1);

        return WorldwideQuote::query()
            ->join('opportunities', function (JoinClause $joinClause) {
                $joinClause->on('opportunities.id', 'worldwide_quotes.opportunity_id');
            })
            ->join('worldwide_quote_versions as quote_active_version', function (JoinClause $joinClause) {
                $joinClause->on('quote_active_version.id', 'worldwide_quotes.active_version_id');
            })
            ->leftJoin('companies as company', function (JoinClause $joinClause) {
                $joinClause->on('company.id', 'quote_active_version.company_id');
            })
            ->leftJoin('companies as primary_account', function (JoinClause $joinClause) {
                $joinClause->on('primary_account.id', 'opportunities.primary_account_id');
            })
            ->join('contract_types', function (JoinClause $joinClause) {
                $joinClause->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->whereNull('worldwide_quotes.submitted_at')
//            ->whereNotNull('worldwide_quotes.activated_at')
            ->where('worldwide_quotes.status', QuoteStatus::ALIVE)
            ->select([
                'worldwide_quotes.id as id',
                'worldwide_quotes.user_id',
                DB::raw("'Worldwide' as business_division"),
                'contract_types.type_short_name as contract_type',
                'opportunities.id as opportunity_id',
                DB::raw('NULL as customer_id'),
                'primary_account.name as customer_name',
                'company.name as company_name',
                'worldwide_quotes.quote_number as rfq_number',
                'quote_active_version.completeness as completeness',

                'worldwide_quotes.active_version_id as active_version_id',

                DB::raw('NULL as active_version_distributor_file_id'),
                DB::raw('NULL as active_version_schedule_file_id'),

                DB::raw('NULL as distributor_file_id'),
                DB::raw('NULL as schedule_file_id'),

                'has_distributor_files' => $this->connection->query()
                    ->selectRaw('exists ('.$distributorFileExistenceQuery->toSql().')', $scheduleFileExistenceQuery->getBindings()),
                'has_schedule_files' => $this->connection->query()
                    ->selectRaw('exists ('.$scheduleFileExistenceQuery->toSql().')', $scheduleFileExistenceQuery->getBindings()),

                'worldwide_quotes.updated_at as updated_at',
                'worldwide_quotes.activated_at as activated_at',
                'worldwide_quotes.is_active as is_active'
            ]);
    }

    public function submittedRescueQuotesListingQuery(): Builder
    {
        return Quote::query()
            ->leftJoin('quote_versions', function (JoinClause $joinClause) {
                $joinClause->on('quote_versions.id', 'quotes.active_version_id');
            })
            ->join('customers', function (JoinClause $joinClause) {
                $joinClause->on('customers.id', 'quotes.customer_id');
            })
            ->join('companies', function (JoinClause $joinClause) {
                $joinClause->on('companies.id', 'quote_versions.company_id')
                    ->orOn('companies.id', 'quotes.company_id');
            })
            ->leftJoin('contracts', function (JoinClause $joinClause) {
                $joinClause->on('contracts.quote_id', 'quotes.id')
                    ->whereNull('contracts.deleted_at');
            })
            ->whereNotNull('quotes.submitted_at')
            ->select([
                'quotes.id as id',
                'quotes.user_id',
                DB::raw("'Rescue' as business_division"),
                DB::raw("'Contract' as contract_type"),
                DB::raw('NULL as opportunity_id'),
                'customers.id as customer_id',
                'customers.name as customer_name',
                'companies.name as company_name',
                'customers.rfq as rfq_number',
                DB::raw('COALESCE(quote_versions.completeness, quotes.completeness) as completeness'),

                'quotes.active_version_id as active_version_id',
                'quote_versions.distributor_file_id as active_version_distributor_file_id',
                'quote_versions.schedule_file_id as active_version_schedule_file_id',

                'quotes.distributor_file_id as distributor_file_id',
                'quotes.schedule_file_id as schedule_file_id',

                DB::raw('NULL as has_distributor_files'),
                DB::raw('NULL as has_schedule_files'),

                DB::raw('NULL as sales_order_id'),
                DB::raw('NULL as sales_order_submitted_at'),

                'contracts.id as contract_id',
                'contracts.submitted_at as contract_submitted_at',

                'quotes.updated_at as updated_at',
                'quotes.activated_at as activated_at',
                'quotes.is_active as is_active'
            ]);
    }

    public function submittedWorldwideQuotesListingQuery(): Builder
    {
        $distributorFileExistenceQuery = WorldwideDistribution::query()->selectRaw('1')
            ->whereColumn('worldwide_distributions.worldwide_quote_id', 'worldwide_quotes.active_version_id')
            ->has('opportunitySupplier')
            ->has('distributorFile')
            ->limit(1);

        $scheduleFileExistenceQuery = WorldwideDistribution::query()->selectRaw('1')
            ->whereColumn('worldwide_distributions.worldwide_quote_id', 'worldwide_quotes.active_version_id')
            ->has('opportunitySupplier')
            ->has('scheduleFile')
            ->limit(1);

        return WorldwideQuote::query()
            ->join('opportunities', function (JoinClause $joinClause) {
                $joinClause->on('opportunities.id', 'worldwide_quotes.opportunity_id');
            })
            ->join('worldwide_quote_versions as quote_active_version', function (JoinClause $joinClause) {
                $joinClause->on('quote_active_version.id', 'worldwide_quotes.active_version_id');
            })
            ->join('companies as company', function (JoinClause $joinClause) {
                $joinClause->on('company.id', 'quote_active_version.company_id');
            })
            ->join('companies as primary_account', function (JoinClause $joinClause) {
                $joinClause->on('primary_account.id', 'opportunities.primary_account_id');
            })
            ->join('contract_types', function (JoinClause $joinClause) {
                $joinClause->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->leftJoin('sales_orders', function (JoinClause $joinClause) {
                $joinClause->on('sales_orders.worldwide_quote_id', 'worldwide_quotes.id')
                    ->whereNull('sales_orders.deleted_at');
            })
            ->whereNotNull('worldwide_quotes.submitted_at')
//            ->whereNotNull('worldwide_quotes.activated_at')
            ->where('worldwide_quotes.status', QuoteStatus::ALIVE)
            ->select([
                'worldwide_quotes.id as id',
                'worldwide_quotes.user_id',
                DB::raw("'Worldwide' as business_division"),
                'contract_types.type_short_name as contract_type',
                'opportunities.id as opportunity_id',
                DB::raw('NULL as customer_id'),
                'primary_account.name as customer_name',
                'company.name as company_name',
                'worldwide_quotes.quote_number as rfq_number',
                'quote_active_version.completeness as completeness',

                'worldwide_quotes.active_version_id as active_version_id',

                DB::raw('NULL as active_version_distributor_file_id'),
                DB::raw('NULL as active_version_schedule_file_id'),

                DB::raw('NULL as distributor_file_id'),
                DB::raw('NULL as schedule_file_id'),

                'has_distributor_files' => $this->connection->query()
                    ->selectRaw('exists ('.$distributorFileExistenceQuery->toSql().')', $scheduleFileExistenceQuery->getBindings()),
                'has_schedule_files' => $this->connection->query()
                    ->selectRaw('exists ('.$scheduleFileExistenceQuery->toSql().')', $scheduleFileExistenceQuery->getBindings()),

                'sales_orders.id as sales_order_id',
                'sales_orders.submitted_at as sales_order_submitted_at',

                DB::raw('NULL as contract_id'),
                DB::raw('NULL as contract_submitted_at'),

                'worldwide_quotes.updated_at as updated_at',
                'worldwide_quotes.activated_at as activated_at',
                'worldwide_quotes.is_active as is_active',
            ]);
    }
}
