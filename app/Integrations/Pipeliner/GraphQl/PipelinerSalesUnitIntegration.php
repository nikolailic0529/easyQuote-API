<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\SalesUnitEntity;
use GraphQL\Query;
use GraphQL\QueryBuilder\QueryBuilder;

class PipelinerSalesUnitIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    /**
     * @return SalesUnitEntity[]
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getAll(): array
    {
        $builder = (new QueryBuilder())
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('salesUnit'))
                            ->selectField(
                                (new Query('getAll'))
                                    ->setSelectionSet(['id', 'name'])
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.salesUnit.getAll');

        return array_map(SalesUnitEntity::fromArray(...), $data);
    }
}