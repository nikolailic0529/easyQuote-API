<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\CreateSalesUnitInput;
use App\Integrations\Pipeliner\Models\SalesUnitEntity;
use App\Integrations\Pipeliner\Models\SalesUnitFilterInput;
use App\Integrations\Pipeliner\Models\ValidationLevelCollection;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;
use Illuminate\Http\Client\RequestException;

class PipelinerSalesUnitIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    /**
     * @param CreateSalesUnitInput $input
     * @param ValidationLevelCollection|null $validationLevel
     * @return SalesUnitEntity
     * @throws GraphQlRequestException
     * @throws RequestException
     */
    public function create(CreateSalesUnitInput      $input,
                           ValidationLevelCollection $validationLevel = null): SalesUnitEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateSalesUnitInput', isRequired: true)
            ->setVariable(name: 'validationLevel', type: '[ValidationLevel!]')
            ->selectField(
                (new Mutation('createSalesUnit'))
                    ->setArguments(['input' => '$input', 'validationLevel' => '$validationLevel'])
                    ->setSelectionSet([
                        (new Query('salesUnit'))
                            ->setSelectionSet(static::getSalesUnitEntitySelectionSet()),
                    ])
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'input' => $input->jsonSerialize(),
                    'validationLevel' => $validationLevel?->jsonSerialize(),
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        return SalesUnitEntity::fromArray($response->json('data.createSalesUnit.salesUnit'));
    }

    /**
     * @return SalesUnitEntity[]
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getAll(SalesUnitFilterInput $filter = null): array
    {
        $builder = (new QueryBuilder())
            ->setVariable('filter', 'SalesUnitFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('salesUnit'))
                            ->selectField(
                                (new Query('getAll'))
                                    ->setArguments([
                                        'filter' => '$filter',
                                    ])
                                    ->setSelectionSet(self::getSalesUnitEntitySelectionSet())
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'filter' => $filter,
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.salesUnit.getAll');

        return array_map(SalesUnitEntity::fromArray(...), $data);
    }

    public function getById(string $id): SalesUnitEntity
    {
        $builder = (new QueryBuilder())
            ->setVariable('id', 'ID', true)
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('salesUnit'))
                            ->selectField(
                                (new Query('getById'))
                                    ->setArguments(['entityId' => '$id'])
                                    ->setSelectionSet(self::getSalesUnitEntitySelectionSet())
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

        $data = $response->json('data.entities.salesUnit.getById');

        return SalesUnitEntity::fromArray($data);
    }

    public static function getSalesUnitEntitySelectionSet(): array
    {
        return [
            'id',
            'name',
            'created',
            'modified',
            'revision',
        ];
    }
}