<?php

namespace App\Queries\Pipeline;

use App\Helpers\ElasticsearchHelper;
use App\Queries\Elasticsearch\ElasticsearchQuery;
use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Elasticsearch\Client;

class PerformElasticsearchSearch implements RequestQueryBuilderPipe
{
    const SIZE = 1_000;

    public function __construct(protected Client $client,
                                protected string $searchParameterName = 'search')
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(BuildQueryParameters $parameters): void
    {
        $builder = $parameters->getBuilder();
        $request = $parameters->getRequest();

        if (false === $this->validateRequestValue($searchQuery = $request->input($this->searchParameterName))) {
            return;
        }

        $result = rescue(function () use ($builder, $searchQuery) {
            return $this->client->search(
                ElasticsearchQuery::new()
                    ->modelIndex($builder->getModel())
                    ->queryString($searchQuery)
                    ->escapeQueryString()
                    ->size(self::SIZE)
                    ->toArray()
            );
        });

        $entityKeys = ElasticsearchHelper::pluckDocumentKeys($result);

        $builder
            ->whereKey($entityKeys);

        if (!empty($entityKeys)) {
            $builder
                ->reorder()
                ->orderByRaw(sprintf(
                    "field(%s %s) asc",
                    $builder->getModel()->getQualifiedKeyName(),
                    str_repeat(', ?', count($entityKeys))
                ), $entityKeys);
        }

    }

    protected function validateRequestValue(mixed $value): bool
    {
        return is_string($value)
            && trim($value) !== '';
    }
}