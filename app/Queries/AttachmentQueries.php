<?php

namespace App\Queries;

use App\Models\Attachment;
use App\Models\Company;
use App\Models\Customer\Customer;
use App\Models\Opportunity;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;

class AttachmentQueries
{
    public function listOfCompanyUnifiedAttachmentsQuery(Company $company): BaseBuilder
    {
        $attachmentModel = new Attachment();

        $rescueQuoteModel = new Quote();
        $rescueCustomerModel = new Customer();
        $worldwideQuoteModel = new WorldwideQuote();
        $opportunityModel = new Opportunity();

        $rescueQuoteAttachmentsQuery = $attachmentModel->newQuery()
            ->select([
                $attachmentModel->getQualifiedKeyName(),
                'attachables.attachable_type',
                "{$rescueQuoteModel->getQualifiedKeyName()} as quote_id",
                "{$rescueCustomerModel->getQualifiedKeyName()} as customer_id",
                "{$rescueCustomerModel->qualifyColumn('rfq')} as quote_number",
                $attachmentModel->qualifyColumn('type'),
                $attachmentModel->qualifyColumn('filepath'),
                $attachmentModel->qualifyColumn('filename'),
                $attachmentModel->qualifyColumn('extension'),
                $attachmentModel->qualifyColumn('size'),
                $attachmentModel->qualifyColumn('created_at'),
            ])
            ->join('attachables', function (JoinClause $join) use ($attachmentModel, $rescueQuoteModel) {
                $join->where('attachables.attachable_type', $rescueQuoteModel->getMorphClass())
                    ->on('attachables.attachment_id', $attachmentModel->getQualifiedKeyName());
            })
            ->join($rescueQuoteModel->getTable(), function (JoinClause $join) use ($rescueQuoteModel, $attachmentModel) {
                $join->on($rescueQuoteModel->getQualifiedKeyName(), 'attachables.attachable_id');
            })
            ->join($rescueCustomerModel->getTable(), function (JoinClause $join) use ($company, $rescueQuoteModel, $rescueCustomerModel) {
                $join->on($rescueCustomerModel->getQualifiedKeyName(), $rescueQuoteModel->qualifyColumn('customer_id'))
                    ->where($rescueCustomerModel->qualifyColumn('company_reference_id'), $company->getKey());
            });

        $worldwideQuoteAttachmentsQuery = $attachmentModel->newQuery()
            ->select([
                $attachmentModel->getQualifiedKeyName(),
                'attachables.attachable_type',
                "{$worldwideQuoteModel->getQualifiedKeyName()} as quote_id",
                new Expression("null as customer_id"),
                $worldwideQuoteModel->qualifyColumn('quote_number'),
                $attachmentModel->qualifyColumn('type'),
                $attachmentModel->qualifyColumn('filepath'),
                $attachmentModel->qualifyColumn('filename'),
                $attachmentModel->qualifyColumn('extension'),
                $attachmentModel->qualifyColumn('size'),
                $attachmentModel->qualifyColumn('created_at'),
            ])
            ->join('attachables', function (JoinClause $join) use ($attachmentModel, $worldwideQuoteModel) {
                $join->where('attachables.attachable_type', $worldwideQuoteModel->getMorphClass())
                    ->on('attachables.attachment_id', $attachmentModel->getQualifiedKeyName());
            })
            ->join($worldwideQuoteModel->getTable(), function (JoinClause $join) use ($worldwideQuoteModel, $attachmentModel) {
                $join->on($worldwideQuoteModel->getQualifiedKeyName(), 'attachables.attachable_id');
            })
            ->join($opportunityModel->getTable(), function (JoinClause $join) use ($company, $worldwideQuoteModel, $opportunityModel) {
                $join->on($opportunityModel->getQualifiedKeyName(), $worldwideQuoteModel->qualifyColumn('opportunity_id'))
                    ->where($opportunityModel->qualifyColumn('primary_account_id'), $company->getKey());
            });

        $companyAttachmentsQuery = $attachmentModel->newQuery()
            ->select([
                $attachmentModel->getQualifiedKeyName(),
                'attachables.attachable_type',
                new Expression("null as quote_id"),
                new Expression("null as customer_id"),
                new Expression("null as quote_number"),
                $attachmentModel->qualifyColumn('type'),
                $attachmentModel->qualifyColumn('filepath'),
                $attachmentModel->qualifyColumn('filename'),
                $attachmentModel->qualifyColumn('extension'),
                $attachmentModel->qualifyColumn('size'),
                $attachmentModel->qualifyColumn('created_at'),
            ])
            ->join('attachables', function (JoinClause $join) use ($attachmentModel, $company) {
                $join->where('attachables.attachable_type', $company->getMorphClass())
                    ->where('attachables.attachable_id', $company->getKey())
                    ->on('attachables.attachment_id', $attachmentModel->getQualifiedKeyName());
            });

        /** @var Builder[] $queries */
        $queries = [
            $rescueQuoteAttachmentsQuery,
            $worldwideQuoteAttachmentsQuery,
            $companyAttachmentsQuery,
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