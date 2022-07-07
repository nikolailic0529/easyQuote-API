<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\MasterRight;
use GraphQL\Query;
use GraphQL\QueryBuilder\QueryBuilder;

class PipelinerMasterRightIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    /**
     * @return MasterRight[]
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getAll(): array
    {
        $builder = (new QueryBuilder())
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('masterRight'))
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

        $data = $response->json('data.entities.masterRight.getAll');

        return array_map(MasterRight::fromArray(...), $data);
    }
}