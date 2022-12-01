<?php

namespace App\Queries;

use App\Models\Company;
use App\Models\Customer\Customer;
use App\Models\Note\ModelHasNotes;
use App\Models\Note\Note;
use App\Models\Opportunity;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;

class UnifiedNoteQueries
{
    public function listOfCompanyNotesQuery(Company $company): Builder
    {
        $userModel = new User();

        $rescueQuoteModel = new Quote();
        $rescueCustomerModel = new Customer();
        $worldwideQuoteModel = new WorldwideQuote();
        $opportunityModel = new Opportunity();

        $noteModel = new Note();
        $modelHasNotesModel = new ModelHasNotes();

        $companyNotesQuery = $company->notes()->getQuery()
            ->select([
                $noteModel->getQualifiedKeyName(),
                $noteModel->owner()->getQualifiedForeignKeyName(),
                $noteModel->owner()->qualifyColumn('user_fullname'),
                $noteModel->qualifyColumn('note'),
                $noteModel->qualifyColumn('flags'),
                new Expression(sprintf("'%s' as model_type", $company->getMorphClass())),
                new Expression("null as quote_id"),
                new Expression("null as customer_id"),
                new Expression("null as quote_number"),
                $noteModel->getQualifiedCreatedAtColumn(),
            ])
            ->leftJoin($userModel->getTable(), $userModel->getQualifiedKeyName(), $noteModel->owner()->getQualifiedForeignKeyName());

        $rescueQuoteNotesQuery = $noteModel->newQuery()
            ->select([
                $noteModel->getQualifiedKeyName(),
                $noteModel->owner()->getQualifiedForeignKeyName(),
                $noteModel->owner()->qualifyColumn('user_fullname'),
                $noteModel->qualifyColumn('note'),
                $noteModel->qualifyColumn('flags'),
                new Expression(sprintf("'%s' as model_type", $rescueQuoteModel->getMorphClass())),
                "{$rescueQuoteModel->getQualifiedKeyName()} as quote_id",
                "{$rescueCustomerModel->getQualifiedKeyName()} as customer_id",
                "{$rescueCustomerModel->qualifyColumn('rfq')} as quote_number",
                $noteModel->getQualifiedCreatedAtColumn(),
            ])
            ->join($modelHasNotesModel->getTable(), $modelHasNotesModel->note()->getQualifiedForeignKeyName(), $noteModel->getQualifiedKeyName())
            ->join($rescueQuoteModel->getTable(), $rescueQuoteModel->getQualifiedKeyName(), $modelHasNotesModel->related()->getQualifiedForeignKeyName())
            ->join($rescueCustomerModel->getTable(), static function (JoinClause $join) use ($rescueCustomerModel, $rescueQuoteModel, $company): void {
                $join->on($rescueCustomerModel->getQualifiedKeyName(), $rescueQuoteModel->qualifyColumn('customer_id'))
                    ->where($rescueCustomerModel->qualifyColumn('company_reference_id'), $company->getKey());
            })
            ->leftJoin($userModel->getTable(), $userModel->getQualifiedKeyName(), $noteModel->owner()->getQualifiedForeignKeyName());

        $worldwideQuoteNotesQuery = $noteModel->newQuery()
            ->select([
                $noteModel->getQualifiedKeyName(),
                $noteModel->owner()->getQualifiedForeignKeyName(),
                $noteModel->owner()->qualifyColumn('user_fullname'),
                $noteModel->qualifyColumn('note'),
                $noteModel->qualifyColumn('flags'),
                new Expression(sprintf("'%s' as model_type", $worldwideQuoteModel->getMorphClass())),
                "{$worldwideQuoteModel->getQualifiedKeyName()} as quote_id",
                new Expression("null as customer_id"),
                $worldwideQuoteModel->qualifyColumn('quote_number'),
                $noteModel->getQualifiedCreatedAtColumn(),
            ])
            ->join($modelHasNotesModel->getTable(), $modelHasNotesModel->note()->getQualifiedForeignKeyName(), $noteModel->getQualifiedKeyName())
            ->join($worldwideQuoteModel->getTable(), $worldwideQuoteModel->getQualifiedKeyName(), $modelHasNotesModel->related()->getQualifiedForeignKeyName())
            ->join($opportunityModel->getTable(), function (JoinClause $join) use ($company, $worldwideQuoteModel, $opportunityModel) {
                $join->on($opportunityModel->getQualifiedKeyName(), $worldwideQuoteModel->opportunity()->getQualifiedForeignKeyName())
                    ->where($opportunityModel->primaryAccount()->getQualifiedForeignKeyName(), $company->getKey());
            })
            ->leftJoin($userModel->getTable(), $userModel->getQualifiedKeyName(), $noteModel->owner()->getQualifiedForeignKeyName());

        /** @var Builder[] $queries */
        $queries = [
            $rescueQuoteNotesQuery,
            $worldwideQuoteNotesQuery,
            $companyNotesQuery,
        ];

        $unifiedQuery = array_shift($queries);

        foreach ($queries as $query) {
            $unifiedQuery->unionAll($query->toBase());
        }

        return tap($unifiedQuery, function (Builder $builder) {
            $builder->orderByDesc('created_at');
        });
    }
}