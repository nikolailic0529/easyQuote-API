<?php

namespace App\Queries;

use App\Models\Company;
use App\Models\CompanyNote;
use App\Models\Customer\Customer;
use App\Models\Opportunity;
use App\Models\Quote\Quote;
use App\Models\Quote\QuoteNote;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteNote;
use App\Models\User;
use App\Services\UnifiedNote\UnifiedNoteDataMapper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;

class UnifiedNoteQueries
{
    public function listOfCompanyNotesQuery(Company $company): BaseBuilder
    {
        $userModel = new User();

        $rescueQuoteNoteModel = new QuoteNote();
        $worldwideQuoteNoteModel = new WorldwideQuoteNote();
        $companyNoteModel = new CompanyNote();
        $rescueQuoteModel = new Quote();
        $rescueCustomerModel = new Customer();
        $worldwideQuoteModel = new WorldwideQuote();
        $opportunityModel = new Opportunity();

        $rescueQuoteNotesQuery = $rescueQuoteNoteModel->newQuery()
            ->select([
                $rescueQuoteNoteModel->getQualifiedKeyName(),
                new Expression(sprintf("'{$rescueQuoteNoteModel->getMorphClass()}' as `%s`", UnifiedNoteDataMapper::ENTITY_TYPE_ATTR)),
                $rescueQuoteNoteModel->qualifyColumn('user_id'),
                $userModel->qualifyColumn('user_fullname'),
                "{$rescueQuoteModel->getQualifiedKeyName()} as quote_id",
                "{$rescueCustomerModel->getQualifiedKeyName()} as customer_id",
                "{$rescueCustomerModel->qualifyColumn('rfq')} as quote_number",
                $rescueQuoteNoteModel->qualifyColumn('text'),
                $rescueQuoteNoteModel->qualifyColumn('created_at'),
            ])
            ->leftJoin($userModel->getTable(), function (JoinClause $join) use ($userModel, $rescueQuoteNoteModel) {
                $join->on($userModel->getQualifiedKeyName(), $rescueQuoteNoteModel->qualifyColumn('user_id'));
            })
            ->join($rescueQuoteModel->getTable(), function (JoinClause $join) use ($rescueQuoteNoteModel, $rescueQuoteModel) {
                $join->on($rescueQuoteModel->getQualifiedKeyName(), $rescueQuoteNoteModel->qualifyColumn('quote_id'));
            })
            ->join($rescueCustomerModel->getTable(), function (JoinClause $join) use ($company, $rescueQuoteModel, $rescueQuoteNoteModel, $rescueCustomerModel) {
                $join->on($rescueCustomerModel->getQualifiedKeyName(), $rescueQuoteModel->qualifyColumn('customer_id'))
                    ->where($rescueCustomerModel->qualifyColumn('company_reference_id'), $company->getKey());
            });

        $worldwideQuoteNotesQuery = $worldwideQuoteNoteModel->newQuery()
            ->select([
                $worldwideQuoteNoteModel->getQualifiedKeyName(),
                new Expression(sprintf("'{$worldwideQuoteNoteModel->getMorphClass()}' as `%s`", UnifiedNoteDataMapper::ENTITY_TYPE_ATTR)),
                $worldwideQuoteNoteModel->qualifyColumn('user_id'),
                $userModel->qualifyColumn('user_fullname'),
                "{$worldwideQuoteModel->getQualifiedKeyName()} as quote_id",
                new Expression("null as customer_id"),
                $worldwideQuoteModel->qualifyColumn('quote_number'),
                $worldwideQuoteNoteModel->qualifyColumn('text'),
                $worldwideQuoteNoteModel->qualifyColumn('created_at'),
            ])
            ->leftJoin($userModel->getTable(), function (JoinClause $join) use ($userModel, $worldwideQuoteNoteModel) {
                $join->on($userModel->getQualifiedKeyName(), $worldwideQuoteNoteModel->qualifyColumn('user_id'));
            })
            ->join($worldwideQuoteModel->getTable(), function (JoinClause $join) use ($worldwideQuoteNoteModel, $worldwideQuoteModel) {
                $join->on($worldwideQuoteModel->getQualifiedKeyName(), $worldwideQuoteNoteModel->qualifyColumn('worldwide_quote_id'));
            })
            ->join($opportunityModel->getTable(), function (JoinClause $join) use ($company, $worldwideQuoteModel, $opportunityModel) {
                $join->on($opportunityModel->getQualifiedKeyName(), $worldwideQuoteModel->qualifyColumn('opportunity_id'))
                    ->where($opportunityModel->qualifyColumn('primary_account_id'), $company->getKey());
            });

        $companyNotesQuery = $companyNoteModel->newQuery()
            ->select([
                $companyNoteModel->getQualifiedKeyName(),
                new Expression(sprintf("'{$companyNoteModel->getMorphClass()}' as `%s`", UnifiedNoteDataMapper::ENTITY_TYPE_ATTR)),
                $companyNoteModel->qualifyColumn('user_id'),
                $userModel->qualifyColumn('user_fullname'),
                new Expression("null as quote_id"),
                new Expression("null as customer_id"),
                new Expression("null as quote_number"),
                $companyNoteModel->qualifyColumn('text'),
                $companyNoteModel->qualifyColumn('created_at'),
            ])
            ->leftJoin($userModel->getTable(), function (JoinClause $join) use ($userModel, $companyNoteModel) {
                $join->on($userModel->getQualifiedKeyName(), $companyNoteModel->qualifyColumn('user_id'));
            })
            ->where($companyNoteModel->qualifyColumn('company_id'), $company->getKey());

        /** @var Builder[] $queries */
        $queries = [
            $rescueQuoteNotesQuery,
            $worldwideQuoteNotesQuery,
            $companyNotesQuery,
        ];

        $unifiedQuery = array_shift($queries);

        foreach ($queries as $query) {
            $unifiedQuery->unionAll($query);
        }

        $unifiedQuery = $unifiedQuery->toBase();

        return tap($unifiedQuery, function (BaseBuilder $builder) {
            $builder->orderByDesc('created_at');
        });
    }
}