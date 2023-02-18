<?php

namespace App\Domain\Attachment\Queries;

use App\Domain\Attachment\Models\Attachment;
use App\Domain\Company\Models\Company;
use App\Domain\Rescue\Models\Customer;
use App\Domain\Rescue\Models\Quote;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
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
                $attachmentModel->owner()->getQualifiedForeignKeyName(),
                $attachmentModel->qualifyColumn('type'),
                $attachmentModel->qualifyColumn('filepath'),
                $attachmentModel->qualifyColumn('filename'),
                $attachmentModel->qualifyColumn('extension'),
                $attachmentModel->qualifyColumn('size'),
                $attachmentModel->qualifyColumn('created_at'),
            ])
            ->with([
                'owner' => static function (Relation $relation) {
                    $model = new User();

                    $relation->select([
                        $model->getQualifiedKeyName(),
                        $model->qualifyColumns([
                            'email',
                            'user_fullname',
                            'first_name',
                            'middle_name',
                            'last_name',
                        ]),
                    ]);
                },
            ])
            ->join('attachables', function (JoinClause $join) use ($attachmentModel, $rescueQuoteModel) {
                $join->where('attachables.attachable_type', $rescueQuoteModel->getMorphClass())
                    ->on('attachables.attachment_id', $attachmentModel->getQualifiedKeyName());
            })
            ->join($rescueQuoteModel->getTable(),
                function (JoinClause $join) use ($rescueQuoteModel) {
                    $join->on($rescueQuoteModel->getQualifiedKeyName(), 'attachables.attachable_id');
                })
            ->join($rescueCustomerModel->getTable(),
                function (JoinClause $join) use ($company, $rescueQuoteModel, $rescueCustomerModel) {
                    $join->on($rescueCustomerModel->getQualifiedKeyName(),
                        $rescueQuoteModel->qualifyColumn('customer_id'))
                        ->where($rescueCustomerModel->qualifyColumn('company_reference_id'), $company->getKey());
                });

        $worldwideQuoteAttachmentsQuery = $attachmentModel->newQuery()
            ->select([
                $attachmentModel->getQualifiedKeyName(),
                'attachables.attachable_type',
                "{$worldwideQuoteModel->getQualifiedKeyName()} as quote_id",
                new Expression('null as customer_id'),
                $worldwideQuoteModel->qualifyColumn('quote_number'),
                $attachmentModel->owner()->getQualifiedForeignKeyName(),
                $attachmentModel->qualifyColumn('type'),
                $attachmentModel->qualifyColumn('filepath'),
                $attachmentModel->qualifyColumn('filename'),
                $attachmentModel->qualifyColumn('extension'),
                $attachmentModel->qualifyColumn('size'),
                $attachmentModel->qualifyColumn('created_at'),
            ])
            ->with([
                'owner' => static function (Relation $relation) {
                    $model = new User();

                    $relation->select([
                        $model->getQualifiedKeyName(),
                        $model->qualifyColumns([
                            'email',
                            'user_fullname',
                            'first_name',
                            'middle_name',
                            'last_name',
                        ]),
                    ]);
                },
            ])
            ->join('attachables', function (JoinClause $join) use ($attachmentModel, $worldwideQuoteModel) {
                $join->where('attachables.attachable_type', $worldwideQuoteModel->getMorphClass())
                    ->on('attachables.attachment_id', $attachmentModel->getQualifiedKeyName());
            })
            ->join($worldwideQuoteModel->getTable(),
                function (JoinClause $join) use ($worldwideQuoteModel) {
                    $join->on($worldwideQuoteModel->getQualifiedKeyName(), 'attachables.attachable_id');
                })
            ->join($opportunityModel->getTable(),
                function (JoinClause $join) use ($company, $worldwideQuoteModel, $opportunityModel) {
                    $join->on($opportunityModel->getQualifiedKeyName(),
                        $worldwideQuoteModel->qualifyColumn('opportunity_id'))
                        ->where($opportunityModel->qualifyColumn('primary_account_id'), $company->getKey());
                });

        $companyAttachmentsQuery = $attachmentModel->newQuery()
            ->select([
                $attachmentModel->getQualifiedKeyName(),
                'attachables.attachable_type',
                new Expression('null as quote_id'),
                new Expression('null as customer_id'),
                new Expression('null as quote_number'),
                $attachmentModel->owner()->getQualifiedForeignKeyName(),
                $attachmentModel->qualifyColumn('type'),
                $attachmentModel->qualifyColumn('filepath'),
                $attachmentModel->qualifyColumn('filename'),
                $attachmentModel->qualifyColumn('extension'),
                $attachmentModel->qualifyColumn('size'),
                $attachmentModel->qualifyColumn('created_at'),
            ])
            ->with([
                'owner' => static function (Relation $relation) {
                    $model = new User();

                    $relation->select([
                        $model->getQualifiedKeyName(),
                        $model->qualifyColumns([
                            'email',
                            'user_fullname',
                            'first_name',
                            'middle_name',
                            'last_name',
                        ]),
                    ]);
                },
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
