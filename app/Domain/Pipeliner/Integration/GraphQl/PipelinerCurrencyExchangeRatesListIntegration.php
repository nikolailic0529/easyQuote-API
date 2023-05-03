<?php

namespace App\Domain\Pipeliner\Integration\GraphQl;

use App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException;
use App\Domain\Pipeliner\Integration\Models\CurrencyExchangeRatesListEntity;
use GraphQL\Query;
use GraphQL\QueryBuilder\QueryBuilder;

class PipelinerCurrencyExchangeRatesListIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    /**
     * @return CurrencyExchangeRatesListEntity[]
     *
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getAll(): array
    {
        $builder = (new QueryBuilder())
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('currencyExchangeRatesList'))
                            ->selectField(
                                (new Query('getAll'))
                                    ->setSelectionSet(static::getCurrencyExchangeRatesListSelectionSet())
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.currencyExchangeRatesList.getAll');

        return array_map(CurrencyExchangeRatesListEntity::fromArray(...), $data);
    }

    public static function getCurrencyExchangeRatesListSelectionSet(): array
    {
        return [
            'id',
            'validFrom',
            'created',
            'modified',
            'revision',
        ];
    }
}
