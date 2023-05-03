<?php

namespace App\Domain\Pipeliner\Integration\GraphQl;

use App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException;
use App\Domain\Pipeliner\Integration\Models\CreateDraftFieldInput;
use App\Domain\Pipeliner\Integration\Models\FieldEntity;
use App\Domain\Pipeliner\Integration\Models\FieldFilterInput;
use App\Domain\Pipeliner\Integration\Models\UpdateDraftFieldInput;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;

class PipelinerFieldIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    public function create(CreateDraftFieldInput $input): FieldEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateDraftFieldInput', isRequired: true)
            ->selectField(
                (new Mutation('createField'))
                    ->setArguments([
                        'input' => '$input',
                    ])
                    ->setSelectionSet([
                        (new Query('field'))
                            ->setSelectionSet(self::getFieldSelectionSet()),
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

        return FieldEntity::fromArray($response->json('data.createField.field'));
    }

    public function update(UpdateDraftFieldInput $input): FieldEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'UpdateDraftFieldInput', isRequired: true)
            ->selectField(
                (new Mutation('updateField'))
                    ->setArguments([
                        'input' => '$input',
                    ])
                    ->setSelectionSet([
                        (new Query('field'))
                            ->setSelectionSet(self::getFieldSelectionSet()),
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

        return FieldEntity::fromArray($response->json('data.updateField.field'));
    }

    public function delete(string $id): void
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'DeleteDraftFieldInput', isRequired: true)
            ->selectField(
                (new Mutation('deleteField'))
                    ->setArguments([
                        'input' => '$input',
                    ])
                    ->setSelectionSet([
                        (new Query('field'))
                            ->setSelectionSet(['id', 'isDeleted']),
                    ])
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'input' => ['id' => $id],
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();
    }

    /**
     * @param string|null $entityName
     * @param string|null $apiName
     *
     * @return FieldEntity[]
     *
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getByCriteria(FieldFilterInput $filter = null): array
    {
        $builder = (new QueryBuilder())
            ->setVariable('filter', 'FieldFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('field'))
                            ->selectField(
                                (new Query('getByCriteria'))
                                    ->setArguments([
                                        'filter' => '$filter',
                                    ])
                                    ->setSelectionSet([
                                        (new Query('edges'))
                                            ->setSelectionSet([
                                                (new Query('node'))
                                                    ->setSelectionSet(self::getFieldSelectionSet()),
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

        $data = $response->json('data.entities.field.getByCriteria.edges.*.node');

        return array_map(FieldEntity::fromArray(...), $data);
    }

    public static function getFieldSelectionSet(): array
    {
        return [
            'id',
            'parentDataSetId',
            'entityName',
            'apiName',
            'name',
            'columnName',
            'dataSetId',
            'created',
            'modified',
            (new Query('dataSet'))
                ->setSelectionSet([
                    'allowedBy',
                    (new Query('entity'))
                        ->setSelectionSet([
                            'id', 'optionName', 'calcValue',
                        ]),
                ]),
        ];
    }
}
