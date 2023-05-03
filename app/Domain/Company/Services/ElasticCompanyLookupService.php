<?php

namespace App\Domain\Company\Services;

use App\Domain\Company\Models\Company;
use App\Foundation\Support\Elasticsearch\ElasticsearchHelper;
use Elasticsearch\Client as Elasticsearch;

class ElasticCompanyLookupService
{
    const DEFAULT_LIMIT = 10;

    protected Elasticsearch $elasticsearch;

    public function __construct(Elasticsearch $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    public function performInternalCompaniesLookup(string $query, int $limit = self::DEFAULT_LIMIT): array
    {
        $companyModel = new Company();
        $type = 'Internal';
        $queryStringFields = ['name', 'short_code'];
        $sourceFields = ['name'];

        return $this->elasticsearch->search([
            'index' => $companyModel->getSearchIndex(),
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'query_string' => [
                                'query' => '*'.ElasticsearchHelper::escapeReservedChars($query).'*',
                                'fields' => $queryStringFields,
                            ],
                        ],
                        'filter' => [
                            ['match' => ['type' => $type]]],
                    ],
                ],

                '_source' => [
                    'includes' => $sourceFields,
                ],

                'terminate_after' => $limit,
            ],
        ]);
    }
}
