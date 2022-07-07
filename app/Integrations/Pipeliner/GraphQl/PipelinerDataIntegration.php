<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Exceptions\EntityNotFoundException;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\CreateDataInput;
use App\Integrations\Pipeliner\Models\DataEntity;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;

class PipelinerDataIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    public function create(CreateDataInput $input): DataEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateDataInput', isRequired: true)
            ->selectField(
                (new Mutation('createData'))
                    ->setArguments([
                        'input' => '$input',
                    ])
                    ->setSelectionSet([
                        (new Query('data'))
                            ->setSelectionSet(['id', 'optionName', 'calcValue']),
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

        return DataEntity::fromArray($response->json('data.createData.data'));
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws EntityNotFoundException
     * @throws GraphQlRequestException
     */
    public function getById(string $id): DataEntity
    {
        $builder = (new QueryBuilder())
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('data'))
                            ->selectField(
                                (new Query('getById'))
                                    ->setArguments(['entityId' => $id])
                                    ->setSelectionSet(['id', 'optionName', 'calcValue'])
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.data.getById');

        if (is_null($data)) {
            throw EntityNotFoundException::notFoundById($id, class_basename(DataEntity::class));
        }

        return DataEntity::fromArray($response->json('data.entities.data.getById'));
    }

    /**
     * @return DataEntity[]
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function getByIds(string ...$ids): array
    {
        $builder = (new QueryBuilder())
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('data'))
                            ->selectField(
                                (new Query('getByIds'))
                                    ->setArguments(['entityIds' => $ids])
                                    ->setSelectionSet(['id', 'optionName', 'calcValue'])
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.data.getByIds');

        return array_map(DataEntity::fromArray(...), $data);
    }

    public function tryGetById(string $id): ?DataEntity
    {
        try {
            return $this->getById($id);
        } catch (EntityNotFoundException) {
        }

        return null;
    }
}