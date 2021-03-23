<?php

namespace App\Queries;

use App\Models\Company;
use App\Models\Customer\WorldwideCustomer;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use App\Services\ElasticsearchQuery;
use DB;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;

class WorldwideQuoteQueries
{
    protected ConnectionInterface $connection;

    protected Elasticsearch $elasticsearch;

    protected Pipeline $pipeline;

    public function __construct(ConnectionInterface $connection,
                                Elasticsearch $elasticsearch,
                                Pipeline $pipeline)
    {
        $this->elasticsearch = $elasticsearch;
        $this->pipeline = $pipeline;
        $this->connection = $connection;
    }

    public function contractDraftedListingQuery(Request $request = null): Builder
    {
        return $this->listingQuery($request)
            ->where('worldwide_quotes.contract_type_id', CT_CONTRACT)
            ->whereNull('worldwide_quotes.submitted_at');
    }

    public function contractSubmittedListingQuery(Request $request = null): Builder
    {
        return $this->listingQuery($request)
            ->where('worldwide_quotes.contract_type_id', CT_CONTRACT)
            ->whereNotNull('worldwide_quotes.submitted_at');
    }

    public function packDraftedListingQuery(Request $request = null): Builder
    {
        return $this->listingQuery($request)
            ->where('worldwide_quotes.contract_type_id', CT_PACK)
            ->whereNull('worldwide_quotes.submitted_at');
    }

    public function packSubmittedListingQuery(Request $request = null): Builder
    {
        return $this->listingQuery($request)
            ->where('worldwide_quotes.contract_type_id', CT_PACK)
            ->whereNotNull('worldwide_quotes.submitted_at');
    }

    public function draftedListingQuery(Request $request = null): Builder
    {
        return $this->listingQuery($request)
            ->whereNull('worldwide_quotes.submitted_at');
    }

    public function submittedListingQuery(Request $request = null): Builder
    {
        $distributorFileExistenceQuery = WorldwideDistribution::query()->selectRaw('1')
            ->whereColumn('worldwide_distributions.worldwide_quote_id', 'worldwide_quotes.id')
            ->has('opportunitySupplier')
            ->has('distributorFile')
            ->limit(1);

        $scheduleFileExistenceQuery = WorldwideDistribution::query()->selectRaw('1')
            ->whereColumn('worldwide_distributions.worldwide_quote_id', 'worldwide_quotes.id')
            ->has('opportunitySupplier')
            ->has('scheduleFile')
            ->limit(1);

        return $this->listingQuery($request)
            ->addSelect([

                'has_distributor_files' => $this->connection->query()
                    ->selectRaw('exists ('.$distributorFileExistenceQuery->toSql().')', $scheduleFileExistenceQuery->getBindings()),
                'has_schedule_files' => $this->connection->query()
                    ->selectRaw('exists ('.$scheduleFileExistenceQuery->toSql().')', $scheduleFileExistenceQuery->getBindings()),
            ])
            ->whereNotNull('worldwide_quotes.submitted_at');
    }

    public function listingQuery(Request $request = null): Builder
    {
        $request ??= new Request;

        $query = WorldwideQuote::query()
            ->select(
                'worldwide_quotes.id',
                'worldwide_quotes.user_id',
                'worldwide_quotes.opportunity_id',
                'worldwide_quotes.contract_type_id',

                'sales_orders.id as sales_order_id',
                'sales_orders.submitted_at as sales_order_submitted_at',

                'contract_types.type_short_name as type_name',
                'worldwide_quotes.quote_number as rfq_number',
                'worldwide_quotes.company_id',
                'worldwide_quotes.completeness',
                'worldwide_quotes.created_at',
                'worldwide_quotes.updated_at',
                'worldwide_quotes.activated_at',
                DB::raw('(sales_orders.id is not null) as sales_order_exists')
            )
            ->join('contract_types', function (JoinClause $join) {
                $join->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->join('opportunities', function (JoinClause $join) {
                $join->on('opportunities.id', 'worldwide_quotes.opportunity_id');
            })
            ->join('companies', function (JoinClause $join) {
                $join->on('companies.id', 'opportunities.primary_account_id');
            })
            ->leftJoin('sales_orders', function (JoinClause $join) {
                $join->on('sales_orders.worldwide_quote_id', 'worldwide_quotes.id')
                    ->whereNull('sales_orders.deleted_at');
            })
//            ->joinSub(
//                WorldwideCustomer::select('id', 'customer_name', 'rfq_number', 'valid_until_date', 'support_start_date', 'support_end_date'),
//                'worldwide_customer',
//                fn (JoinClause $join) => $join->on('worldwide_customer.id', 'worldwide_quotes.worldwide_customer_id')
//            )
            ->addSelect([
                'user_fullname' => User::query()->select('user_fullname')->whereColumn('users.id', 'worldwide_quotes.user_id')->limit(1),
                'company_name' => Company::query()->select('name')->whereColumn('companies.id', 'worldwide_quotes.company_id')->limit(1),
                'companies.name as customer_name',
                'opportunities.opportunity_closing_date as valid_until_date',
                'opportunities.opportunity_start_date as customer_support_start_date',
                'opportunities.opportunity_end_date as customer_support_end_date',
            ]);

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($searchQuery) {
                return $this->elasticsearch->search(
                    (new ElasticsearchQuery)
                        ->modelIndex(new WorldwideQuote)
                        ->queryString(Str::of($searchQuery)->start('*')->finish('*'))
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        $this->pipeline->send($query)
            ->through([
                new \App\Http\Query\ActiveFirst('worldwide_quotes.is_active'),
                \App\Http\Query\OrderByTypeName::class,
                \App\Http\Query\OrderByCustomerName::class,
                \App\Http\Query\Quote\OrderByCompleteness::class,
                \App\Http\Query\OrderByUserFullname::class,
                \App\Http\Query\OrderByRfqNumber::class,
                \App\Http\Query\OrderByValidUntilDate::class,
                \App\Http\Query\OrderByCustomerSupportStartDate::class,
                \App\Http\Query\OrderByCustomerSupportEndDate::class,
                \App\Http\Query\OrderByCreatedAt::class,
                new \App\Http\Query\DefaultOrderBy('updated_at'),
            ])
            ->thenReturn();

        return $query;
    }
}
