<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\CurrencyEntity;
use App\Integrations\Pipeliner\Models\DataEntity;
use GraphQL\Query;
use GraphQL\QueryBuilder\QueryBuilder;

class PipelinerCurrencyIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    /**
     * @return CurrencyEntity[]
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getByIds(string ...$ids): array
    {
        $builder = (new QueryBuilder())
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('currency'))
                            ->selectField(
                                (new Query('getByIds'))
                                    ->setArguments(['entityIds' => $ids])
                                    ->setSelectionSet(['id', 'code'])
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.currency.getByIds');

        return array_map(CurrencyEntity::fromArray(...), $data);
    }

    /**
     * @return CurrencyEntity[]
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getAll(): array
    {
        $builder = (new QueryBuilder())
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('currency'))
                            ->selectField(
                                (new Query('getAll'))
                                    ->setSelectionSet(['id', 'code'])
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.currency.getAll');

        return array_map(CurrencyEntity::fromArray(...), $data);
    }
}