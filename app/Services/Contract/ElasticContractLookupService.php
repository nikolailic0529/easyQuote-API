<?php

namespace App\Services\Contract;

use App\DTO\Contract\ContractLookupQueryData;
use App\Helpers\ElasticsearchHelper;
use App\Models\Quote\Contract;
use Elasticsearch\Client as Elasticsearch;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Database\Eloquent\Model;

class ElasticContractLookupService
{
    const DEFAULT_LIMIT = 10;

    protected Elasticsearch $elasticsearch;

    public function __construct(Elasticsearch $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    public function performGenericContractsLookup(string $query): array
    {
        $contractModel = new Contract();

        return $this->elasticsearch->search([
            'index' => $contractModel->getSearchIndex(),
            'body' => [
                'query' => [
                    'query_string' => [
                        'query' => '*'.ElasticsearchHelper::escapeReservedChars($query).'*',
                        'type' => 'cross_fields'
                    ]
                ]
            ]
        ]);
    }

    public function performComplexContractsLookup(ContractLookupQueryData $data): array
    {
        $requestBody = [];

        if (!is_null($data->search_query)) {
            $requestBody['bool']['must'][]['query_string'] = [
                'query' => '*'.ElasticsearchHelper::escapeReservedChars($data->search_query).'*',
                'type' => 'cross_fields'
            ];
        }

        foreach ($data->should_equal_fields as $field) {
            $requestBody['bool']['should'][]['match'][$field->field_name] = $field->field_value;
        }

        foreach ($data->must_not_equal_fields as $field) {
            $requestBody['bool']['must_not'][]['match'][$field->field_name] = $field->field_value;
        }

        foreach ($data->must_equal_fields as $field) {
            $requestBody['bool']['must'][]['match'][$field->field_name] = $field->field_value;
        }

        foreach ($data->term_equal_values as $term) {
            $requestBody['bool']['must'][]['terms'][$term->term_name.'.keyword'] = $term->term_values;
        }

        foreach ($data->term_not_equal_values as $term) {
            $requestBody['bool']['must_not'][]['terms'][$term->term_name.'.keyword'] = $term->term_values;
        }

        foreach ($data->range_gte_fields as $field) {
            $requestBody['bool']['must'][]['range'][$field->field_name] = ['gte' => $field->field_value];
        }

        foreach ($data->range_lte_fields as $field) {
            $requestBody['bool']['must'][]['range'][$field->field_name] = ['lte' => $field->field_value];
        }

        try {
            return $this->elasticsearch->search([
                'index' => (new Contract)->getSearchIndex(),
                'body' => [
                    'query' => $requestBody,
                    '_source' => false,
                    'from' => 0,
                    'size' => 10_000,
                ],
            ]);
        } catch (Missing404Exception $e) {
            return [];
        }
    }

    public function performContractCustomersLookup(string $query, int $limit = self::DEFAULT_LIMIT): array
    {
        $contractModel = new Contract();
        $queryStringFields = ['customer_name'];
        $sourceFields = ['customer_name'];

        return $this->elasticsearch->search([
            'index' => $contractModel->getSearchIndex(),
            'body' => [

                'query' => [
                    'bool' => [
                        'should' => [
                            'query_string' => [
                                'query' => '*'.ElasticsearchHelper::escapeReservedChars($query).'*',
                                'fields' => $queryStringFields,
                            ]
                        ],
                    ]
                ],

                '_source' => [
                    'includes' => $sourceFields,
                ],

                'collapse' => [
                    'field' => 'customer_name.keyword'
                ],

                'terminate_after' => $limit
            ]
        ]);
    }

    public function performContractCompaniesLookup(string $query, int $limit = self::DEFAULT_LIMIT): array
    {
        $contractModel = new Contract();
        $queryStringFields = ['company_name'];
        $sourceFields = ['company_name'];

        return $this->elasticsearch->search([
            'index' => $contractModel->getSearchIndex(),
            'body' => [

                'query' => [
                    'bool' => [
                        'should' => [
                            'query_string' => [
                                'query' => '*'.ElasticsearchHelper::escapeReservedChars($query).'*',
                                'fields' => $queryStringFields,
                            ]
                        ],
                    ]
                ],

                '_source' => [
                    'includes' => $sourceFields,
                ],

                'collapse' => [
                    'field' => 'company_name.keyword'
                ],

                'terminate_after' => $limit
            ]
        ]);
    }

    public function performContractNumbersLookup(string $query, int $limit = self::DEFAULT_LIMIT): array
    {
        $contractModel = new Contract();
        $queryStringFields = ['contract_number'];
        $sourceFields = ['contract_number'];

        return $this->elasticsearch->search([
            'index' => $contractModel->getSearchIndex(),
            'body' => [

                'query' => [
                    'bool' => [
                        'should' => [
                            'query_string' => [
                                'query' => '*'.ElasticsearchHelper::escapeReservedChars($query).'*',
                                'fields' => $queryStringFields,
                            ]
                        ],
                    ]
                ],

                '_source' => [
                    'includes' => $sourceFields,
                ],

                'terminate_after' => $limit
            ]
        ]);
    }
}
