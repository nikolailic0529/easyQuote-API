<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Defaults;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\ClientEntity;
use App\Integrations\Pipeliner\Models\ClientFilterInput;
use App\Integrations\Pipeliner\Models\CreateClientInput;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;
use Illuminate\Http\Client\Response;

class PipelinerClientIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    /**
     * @param string $id
     * @return ClientEntity
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getById(string $id): ClientEntity
    {
        $builder = (new QueryBuilder())
            ->setVariable('id', 'ID', true)
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('client'))
                            ->selectField(
                                (new Query('getById'))
                                    ->setArguments(['entityId' => '$id'])
                                    ->setSelectionSet(self::getClientEntitySelectionSet())
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'id' => $id,
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        return ClientEntity::fromArray($response->json('data.entities.client.getById'));
    }

    /**
     * @return ClientEntity[]
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getAll(): array
    {
        $builder = (new QueryBuilder())
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('client'))
                            ->selectField(
                                (new Query('getAll'))
                                    ->setSelectionSet(self::getClientEntitySelectionSet())
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.client.getAll');

        return array_map(ClientEntity::fromArray(...), $data);
    }

    /**
     * @param ClientFilterInput|null $filter
     * @param int $first
     * @return ClientEntity[]
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getByCriteria(ClientFilterInput $filter = null, int $first = Defaults::DEFAULT_LIMIT): array
    {
        $builder = (new QueryBuilder())
            ->setVariable('filter', 'ClientFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('client'))
                            ->selectField(
                                (new Query('getByCriteria'))
                                    ->setArguments([
                                        'filter' => '$filter',
                                        'first' => $first,
                                    ])
                                    ->setSelectionSet([
                                        (new Query('edges'))
                                            ->setSelectionSet([
                                                (new Query('node'))
                                                    ->setSelectionSet(self::getClientEntitySelectionSet()),
                                            ]),
                                    ])
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'filter' => $filter?->jsonSerialize(),
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.client.getByCriteria.edges.*.node');

        return array_map(ClientEntity::fromArray(...), $data);
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function create(CreateClientInput $input): ClientEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateClientInput', isRequired: true)
            ->selectField(
                (new Mutation('createClient'))
                    ->setArguments([
                        'input' => '$input',
                    ])
                    ->setSelectionSet([
                        (new Query('client'))
                            ->setSelectionSet(self::getClientEntitySelectionSet()),
                    ])
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'input' => $input->jsonSerialize(),
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        return ClientEntity::fromArray($response->json('data.createClient.client'));
    }

    public static function getClientEntitySelectionSet(): array
    {
        return ['id', 'formattedName', 'email', 'firstName', 'lastName'];
    }

}