<?php

namespace App\Domain\Mail\Queries;

use App\Domain\Mail\Models\MailLog;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class MailLogQueries
{
    public function __construct(
        private readonly Client $elasticsearch,
    ) {
    }

    public function paginateMailLogQuery(Request $request = new Request()): Builder
    {
        $model = new MailLog();

        $query = $model->newQuery()
            ->select(
                $model->qualifyColumns([
                    $model->getKeyName(),
                    'subject',
                    'sent_at',
                    'from',
                    'to',
                ]),
            );

        return RequestQueryBuilder::for($query, $request)
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields('sent_at', 'subject')
            ->enforceOrderBy($model->qualifyColumn('sent_at'))
            ->process();
    }
}
