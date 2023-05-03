<?php

namespace App\Domain\Note\Queries;

use App\Domain\Company\Models\Company;
use App\Domain\Note\Models\ModelHasNotes;
use App\Domain\Note\Models\Note;
use App\Domain\Rescue\Models\Customer;
use App\Domain\Rescue\Models\Quote;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class UnifiedNoteQueries
{
    public function listOfCompanyNotesQuery(Company $company, Request $request = new Request()): Builder
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
                new Expression('null as quote_id'),
                new Expression('null as customer_id'),
                new Expression('null as quote_number'),
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
                new Expression('null as customer_id'),
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

        return RequestQueryBuilder::for(
            builder: $unifiedQuery,
            request: $request
        )
            ->enforceOrderBy('created_at')
            ->process();
    }

    public function simpleListOfCompanyNotesQuery(Company $company, Request $request = new Request()): Builder
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
                $noteModel->getQualifiedCreatedAtColumn(),
                $noteModel->getQualifiedUpdatedAtColumn(),
            ])
            ->leftJoin($userModel->getTable(), $userModel->getQualifiedKeyName(),
                $noteModel->owner()->getQualifiedForeignKeyName());

        $rescueQuoteNotesQuery = $noteModel->newQuery()
            ->select([
                $noteModel->getQualifiedKeyName(),
                $noteModel->owner()->getQualifiedForeignKeyName(),
                $noteModel->owner()->qualifyColumn('user_fullname'),
                $noteModel->qualifyColumn('note'),
                $noteModel->qualifyColumn('flags'),
                $noteModel->getQualifiedCreatedAtColumn(),
                $noteModel->getQualifiedUpdatedAtColumn(),
            ])
            ->join($modelHasNotesModel->getTable(), $modelHasNotesModel->note()->getQualifiedForeignKeyName(),
                $noteModel->getQualifiedKeyName())
            ->join($rescueQuoteModel->getTable(), $rescueQuoteModel->getQualifiedKeyName(),
                $modelHasNotesModel->related()->getQualifiedForeignKeyName())
            ->join($rescueCustomerModel->getTable(),
                static function (JoinClause $join) use ($rescueCustomerModel, $rescueQuoteModel, $company): void {
                    $join->on($rescueCustomerModel->getQualifiedKeyName(),
                        $rescueQuoteModel->qualifyColumn('customer_id'))
                        ->where($rescueCustomerModel->qualifyColumn('company_reference_id'), $company->getKey());
                })
            ->leftJoin($userModel->getTable(), $userModel->getQualifiedKeyName(),
                $noteModel->owner()->getQualifiedForeignKeyName());

        $worldwideQuoteNotesQuery = $noteModel->newQuery()
            ->select([
                $noteModel->getQualifiedKeyName(),
                $noteModel->owner()->getQualifiedForeignKeyName(),
                $noteModel->owner()->qualifyColumn('user_fullname'),
                $noteModel->qualifyColumn('note'),
                $noteModel->qualifyColumn('flags'),
                $noteModel->getQualifiedCreatedAtColumn(),
                $noteModel->getQualifiedUpdatedAtColumn(),
            ])
            ->join($modelHasNotesModel->getTable(), $modelHasNotesModel->note()->getQualifiedForeignKeyName(),
                $noteModel->getQualifiedKeyName())
            ->join($worldwideQuoteModel->getTable(), $worldwideQuoteModel->getQualifiedKeyName(),
                $modelHasNotesModel->related()->getQualifiedForeignKeyName())
            ->join($opportunityModel->getTable(),
                function (JoinClause $join) use ($company, $worldwideQuoteModel, $opportunityModel) {
                    $join->on($opportunityModel->getQualifiedKeyName(),
                        $worldwideQuoteModel->opportunity()->getQualifiedForeignKeyName())
                        ->where($opportunityModel->primaryAccount()->getQualifiedForeignKeyName(), $company->getKey());
                })
            ->leftJoin($userModel->getTable(), $userModel->getQualifiedKeyName(),
                $noteModel->owner()->getQualifiedForeignKeyName());

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

        $unifiedQuery->with('owner:'.collect([
                $userModel->getKeyName(), 'first_name', 'last_name', 'user_fullname',
            ])->join(','));

        return RequestQueryBuilder::for(
            builder: $unifiedQuery,
            request: $request
        )
            ->allowQuickSearchFields('note')
            ->allowOrderFields(
                'created_at',
                'note',
                'text',
            )
            ->qualifyOrderFields(
                text: 'note',
            )
            ->enforceOrderBy($noteModel->getCreatedAtColumn())
            ->process();
    }
}
