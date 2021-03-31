<?php

namespace App\Queries;

use App\Enum\QuoteStatus;
use App\Http\Query\{ActiveFirst,
    DefaultOrderBy,
    OrderByCompleteness,
    OrderByCustomerName,
    OrderByRfqNumber,
    OrderByUpdatedAt,
    Quote\OrderByCompanyName};
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;

class UnifiedQuoteQueries
{
    protected Pipeline $pipeline;

    public function __construct(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function paginateDraftedQuotesQuery(Request $request = null): BaseBuilder
    {
        $request ??= new Request();

        $rescueQuotesQuery = Quote::query()
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
            ->whereNull('quotes.submitted_at')
//            ->whereNotNull('quotes.activated_at')
            ->select([
                'quotes.id as id',
                DB::raw("'Rescue' as business_division"),
                DB::raw("'Contract' as contract_type"),
                DB::raw('NULL as opportunity_id'),
                'customers.id as customer_id',
                'customers.name as customer_name',
                'companies.name as company_name',
                'customers.rfq as rfq_number',
                DB::raw('COALESCE(quote_versions.completeness, quotes.completeness) as completeness'),
                'quotes.updated_at as updated_at',
                'quotes.activated_at as activated_at',
                'quotes.is_active as is_active'
            ]);

        $worldwideQuotesQuery = WorldwideQuote::query()
            ->join('opportunities', function (JoinClause $joinClause) {
                $joinClause->on('opportunities.id', 'worldwide_quotes.opportunity_id');
            })
            ->join('companies as company', function (JoinClause $joinClause) {
                $joinClause->on('company.id', 'worldwide_quotes.company_id');
            })
            ->join('companies as primary_account', function (JoinClause $joinClause) {
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
                DB::raw("'Worldwide' as business_division"),
                'contract_types.type_short_name as contract_type',
                'opportunities.id as opportunity_id',
                DB::raw('NULL as customer_id'),
                'primary_account.name as customer_name',
                'company.name as company_name',
                'worldwide_quotes.quote_number as rfq_number',
                'worldwide_quotes.completeness as completeness',
                'worldwide_quotes.updated_at as updated_at',
                'worldwide_quotes.activated_at as activated_at',
                'worldwide_quotes.is_active as is_active'
            ]);

        $unifiedQuery = $rescueQuotesQuery
            ->unionAll($worldwideQuotesQuery->toBase());

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

    public function paginateSubmittedQuotesQuery(Request $request = null): BaseBuilder
    {
        $request ??= new Request();

        $rescueQuotesQuery = Quote::query()
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
                DB::raw("'Rescue' as business_division"),
                DB::raw("'Contract' as contract_type"),
                DB::raw('NULL as opportunity_id'),
                'customers.id as customer_id',
                'customers.name as customer_name',
                'companies.name as company_name',
                'customers.rfq as rfq_number',
                DB::raw('COALESCE(quote_versions.completeness, quotes.completeness) as completeness'),

                DB::raw('NULL as sales_order_id'),
                DB::raw('NULL as sales_order_submitted_at'),

                'contracts.id as contract_id',
                'contracts.submitted_at as contract_submitted_at',

                'quotes.updated_at as updated_at',
                'quotes.activated_at as activated_at',
                'quotes.is_active as is_active'
            ]);

        $worldwideQuotesQuery = WorldwideQuote::query()
            ->join('opportunities', function (JoinClause $joinClause) {
                $joinClause->on('opportunities.id', 'worldwide_quotes.opportunity_id');
            })
            ->join('companies as company', function (JoinClause $joinClause) {
                $joinClause->on('company.id', 'worldwide_quotes.company_id');
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
                DB::raw("'Worldwide' as business_division"),
                'contract_types.type_short_name as contract_type',
                'opportunities.id as opportunity_id',
                DB::raw('NULL as customer_id'),
                'primary_account.name as customer_name',
                'company.name as company_name',
                'worldwide_quotes.quote_number as rfq_number',
                'worldwide_quotes.completeness as completeness',

                'sales_orders.id as sales_order_id',
                'sales_orders.submitted_at as sales_order_submitted_at',

                DB::raw('NULL as contract_id'),
                DB::raw('NULL as contract_submitted_at'),

                'worldwide_quotes.updated_at as updated_at',
                'worldwide_quotes.activated_at as activated_at',
                'worldwide_quotes.is_active as is_active',
            ]);

        $unifiedQuery = $rescueQuotesQuery
            ->unionAll($worldwideQuotesQuery->toBase());

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

    public function paginateExpiringQuotesQuery(Request $request = null): BaseBuilder
    {
        $request ??= new Request();

        $rescueQuotesQuery = Quote::query()
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
            ->whereNull('quotes.submitted_at')
            ->where('customers.valid_until', '>=', today())
            ->select([
                'quotes.id as id',
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

        $worldwideQuotesQuery = WorldwideQuote::query()
            ->join('opportunities', function (JoinClause $joinClause) {
                $joinClause->on('opportunities.id', 'worldwide_quotes.opportunity_id');
            })
            ->join('companies as company', function (JoinClause $joinClause) {
                $joinClause->on('company.id', 'worldwide_quotes.company_id');
            })
            ->join('companies as primary_account', function (JoinClause $joinClause) {
                $joinClause->on('primary_account.id', 'opportunities.primary_account_id');
            })
            ->join('contract_types', function (JoinClause $joinClause) {
                $joinClause->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->whereNull('worldwide_quotes.submitted_at')
            ->where('opportunities.opportunity_closing_date', '>=', today())
            ->where('worldwide_quotes.status', QuoteStatus::ALIVE)
            ->select([
                'worldwide_quotes.id as id',
                DB::raw("'Worldwide' as business_division"),
                'contract_types.type_short_name as contract_type',
                'opportunities.id as opportunity_id',
                DB::raw('NULL as customer_id'),
                'primary_account.name as customer_name',
                'company.name as company_name',
                'worldwide_quotes.quote_number as rfq_number',
                DB::raw('DATE(opportunities.opportunity_closing_date) as valid_until_date'),
                'worldwide_quotes.completeness as completeness',
                'worldwide_quotes.updated_at as updated_at',
                'worldwide_quotes.activated_at as activated_at',
                'worldwide_quotes.is_active as is_active'
            ]);

        $unifiedQuery = $rescueQuotesQuery
            ->unionAll($worldwideQuotesQuery->toBase());

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
}
