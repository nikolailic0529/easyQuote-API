<?php

namespace App\Domain\Pipeliner\Integration\GraphQl;

use App\Domain\Pipeliner\Integration\Exceptions\EntityNotFoundException;
use App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException;
use App\Domain\Pipeliner\Integration\Models\CreateOpportunityInput;
use App\Domain\Pipeliner\Integration\Models\OpportunityEntity;
use App\Domain\Pipeliner\Integration\Models\OpportunityFilterInput;
use App\Domain\Pipeliner\Integration\Models\UpdateOpportunityInput;
use App\Domain\Pipeliner\Integration\Models\UpdateOpportunityInputCollection;
use App\Domain\Pipeliner\Integration\Models\ValidationLevelCollection;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;
use GraphQL\RawObject;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\LazyCollection;

class PipelinerOpportunityIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    public function scroll(
        string $after = null,
        string $before = null,
        OpportunityFilterInput $filter = null,
        int $first = 10
    ): OpportunityEntityScrollIterator {
        /** @noinspection PhpUnhandledExceptionInspection */
        $iterator = $this->scrollGenerator(after: $after, before: $before, filter: $filter, first: $first);

        return new OpportunityEntityScrollIterator($iterator);
    }

    public function rawScroll(
        string $after = null,
        string $before = null,
        OpportunityFilterInput $filter = null,
        int $first = 10
    ): \Generator {
        /* @noinspection PhpUnhandledExceptionInspection */
        return $this->rawScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
    }

    public function simpleScroll(
        string $after = null,
        string $before = null,
        OpportunityFilterInput $filter = null,
        int $first = 10
    ): \Generator {
        /* @noinspection PhpUnhandledExceptionInspection */
        return $this->simpleScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     * @throws EntityNotFoundException
     */
    public function getById(string $id): OpportunityEntity
    {
        $builder = (new QueryBuilder())
            ->setVariable('id', 'ID!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('opportunity'))
                            ->selectField(
                                (new Query('getById'))
                                    ->setArguments([
                                        'entityId' => '$id',
                                    ])
                                    ->setSelectionSet(static::getOpportunityEntitySelectionSet())
                            )
                    )
            );

        $response = $this->client->post($this->client->buildSpaceEndpoint(), [
            'query' => $builder->getQuery()->__toString(),
            'variables' => [
                'id' => $id,
            ],
        ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $value = $response->json('data.entities.opportunity.getById');

        if (null === $value) {
            throw EntityNotFoundException::notFoundById($id, 'opportunity');
        }

        return OpportunityEntity::fromArray($value);
    }

    /**
     * @return OpportunityEntity[]
     *
     * @throws GraphQlRequestException
     * @throws RequestException
     */
    public function getByIds(string ...$ids): array
    {
        $builder = (new QueryBuilder())
            ->setVariable('ids', '[ID!]!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('opportunity'))
                            ->selectField(
                                (new Query('getByIds'))
                                    ->setArguments([
                                        'entityIds' => '$ids',
                                    ])
                                    ->setSelectionSet(static::getOpportunityEntitySelectionSet())
                            )
                    )
            );

        $response = $this->client->post($this->client->buildSpaceEndpoint(), [
            'query' => $builder->getQuery()->__toString(),
            'variables' => [
                'ids' => $ids,
            ],
        ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $value = $response->json('data.entities.opportunity.getByIds');

        return array_map(OpportunityEntity::fromArray(...), $value);
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    protected function scrollGenerator(
        string $after = null,
        string $before = null,
        OpportunityFilterInput $filter = null,
        int $first = 10
    ): \Generator {
        yield from LazyCollection::make(function () use ($first, $filter, $before, $after): \Generator {
            yield from $this->rawScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        })
            ->map(static function (array $item): OpportunityEntity {
                return OpportunityEntity::fromArray($item);
            });
    }

    protected function rawScrollGenerator(
        string $after = null,
        string $before = null,
        OpportunityFilterInput $filter = null,
        int $first = 10
    ): \Generator {
        $builder = (new QueryBuilder())
            ->setVariable('after', 'String')
            ->setVariable('before', 'String')
            ->setVariable('filter', 'OpportunityFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('opportunity'))
                            ->selectField(
                                (new Query('getByCriteria'))
                                    ->setArguments([
                                        'filter' => '$filter',
                                        'orderBy' => new RawObject('{modified: Asc}'),
                                        'first' => $first,
                                        'after' => '$after',
                                        'before' => '$before',
                                    ])
                                    ->setSelectionSet([
                                        (new Query('edges'))
                                            ->setSelectionSet([
                                                (new Query('node'))
                                                    ->setSelectionSet(static::getOpportunityEntitySelectionSet()),
                                            ]),
                                        (new Query('pageInfo'))
                                            ->setSelectionSet([
                                                'startCursor', 'endCursor', 'hasNextPage', 'hasPreviousPage',
                                            ]),
                                        'totalCount',
                                    ])
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'after' => $after,
                    'before' => $before,
                    'filter' => $filter?->jsonSerialize(),
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $hasNextPage = $response->json('data.entities.opportunity.getByCriteria.pageInfo.hasNextPage');
        $after = $response->json('data.entities.opportunity.getByCriteria.pageInfo.endCursor');

        foreach ($response->json('data.entities.opportunity.getByCriteria.edges.*.node') as $node) {
            yield $after => $node;
        }

        unset($builder, $response);

        if ($hasNextPage) {
            yield from $this->rawScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        }
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    protected function simpleScrollGenerator(
        string $after = null,
        string $before = null,
        OpportunityFilterInput $filter = null,
        int $first = 10
    ): \Generator {
        $builder = (new QueryBuilder())
            ->setVariable('after', 'String')
            ->setVariable('before', 'String')
            ->setVariable('filter', 'OpportunityFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('opportunity'))
                            ->selectField(
                                (new Query('getByCriteria'))
                                    ->setArguments([
                                        'orderBy' => new RawObject('{modified: Asc}'),
                                        'first' => $first,
                                        'after' => '$after',
                                        'before' => '$before',
                                        'filter' => '$filter',
                                    ])
                                    ->setSelectionSet([
                                        (new Query('edges'))
                                            ->setSelectionSet([
                                                (new Query('node'))
                                                    ->setSelectionSet([
                                                        'id',
                                                        'modified',
                                                        'name',
                                                        (new Query('unit'))
                                                            ->setSelectionSet([
                                                                'name',
                                                            ]),
                                                    ]),
                                            ]),
                                        (new Query('pageInfo'))
                                            ->setSelectionSet([
                                                'startCursor', 'endCursor', 'hasNextPage', 'hasPreviousPage',
                                            ]),
                                    ])
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'after' => $after,
                    'before' => $before,
                    'filter' => $filter?->jsonSerialize(),
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $hasNextPage = $response->json('data.entities.opportunity.getByCriteria.pageInfo.hasNextPage');
        $after = $response->json('data.entities.opportunity.getByCriteria.pageInfo.endCursor');

        foreach ($response->json('data.entities.opportunity.getByCriteria.edges.*.node') as $node) {
            yield $after => $node;
        }

        unset($builder, $response);

        if ($hasNextPage) {
            yield from $this->simpleScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        }
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function create(
        CreateOpportunityInput $input,
        ValidationLevelCollection $validationLevel = null
    ): OpportunityEntity {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateOpportunityInput', isRequired: true)
            ->setVariable(name: 'validationLevel', type: '[ValidationLevel!]')
            ->selectField(
                (new Mutation('createOpportunity'))
                    ->setArguments(['input' => '$input', 'validationLevel' => '$validationLevel'])
                    ->setSelectionSet([
                        (new Query('opportunity'))
                            ->setSelectionSet(static::getOpportunityEntitySelectionSet()),
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

        return OpportunityEntity::fromArray($response->json('data.createOpportunity.opportunity'));
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function update(
        UpdateOpportunityInput $input,
        ValidationLevelCollection $validationLevel = null
    ): OpportunityEntity {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'UpdateOpportunityInput', isRequired: true)
            ->setVariable(name: 'validationLevel', type: '[ValidationLevel!]')
            ->selectField(
                (new Mutation('updateOpportunity'))
                    ->setArguments(['input' => '$input', 'validationLevel' => '$validationLevel'])
                    ->setSelectionSet([
                        (new Query('opportunity'))
                            ->setSelectionSet(static::getOpportunityEntitySelectionSet()),
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

        return OpportunityEntity::fromArray($response->json('data.updateOpportunity.opportunity'));
    }

    /**
     * @throws GraphQlRequestException
     * @throws RequestException
     */
    public function restore(string $id): void
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'RestoreOpportunityInput', isRequired: true)
            ->selectField(
                (new Mutation('restoreOpportunity'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('result'))
                            ->setSelectionSet(static::getOpportunityEntitySelectionSet()),
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

    public function bulkUpdate(
        UpdateOpportunityInputCollection $input,
        ValidationLevelCollection $validationLevel = null
    ): array {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: '[CreateOrUpdateOpportunityInput!]', isRequired: true)
            ->setVariable(name: 'validationLevel', type: '[ValidationLevel!]')
            ->selectField(
                (new Mutation('bulkUpdateOpportunity'))
                    ->setArguments(['input' => '$input', 'validationLevel' => '$validationLevel'])
                    ->setSelectionSet([
                        (new Query('result'))
                            ->setSelectionSet([
                                'updated',
                                (new Query('errors'))
                                    ->setSelectionSet(['entityId', 'code', 'message', 'index', 'message']),
                            ]),
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

        return $response->json('data.bulkUpdateOpportunity.result');
    }

    public static function getOpportunityEntitySelectionSet(): array
    {
        return [
            'id',
            'formattedName',
            'name',
            'description',
            'status',
            'closingDate',
            'qualifyDate',
            'lostDate',
            'wonDate',

            'quickAccountEmail',
            'quickAccountName',
            'quickAccountPhone',
            'quickContactName',
            'quickEmail',
            'quickPhone',

            'ranking',
            'created',
            'modified',
            'revision',
            'isArchived',

            (new Query('accountRelations'))
                ->setSelectionSet([
                    (new Query('edges'))
                        ->setSelectionSet([
                            (new Query('node'))
                                ->setSelectionSet([
                                    'id',
                                    'accountId',
                                    'isPrimary',
                                    (new Query('account'))
                                        ->setSelectionSet(PipelinerAccountIntegration::getAccountEntitySelectionSet()),
                                ]),
                        ]),
                ]),

            (new Query('contactRelations'))
                ->setSelectionSet([
                    (new Query('edges'))
                        ->setSelectionSet([
                            (new Query('node'))
                                ->setSelectionSet([
                                    'id',
                                    'isPrimary',
                                    (new Query('contact'))
                                        ->setSelectionSet(PipelinerContactIntegration::getContactEntitySelectionSet()),
                                ]),
                        ]),
                ]),

            (new Query('documents'))
                ->setSelectionSet([
                    (new Query('edges'))
                        ->setSelectionSet([
                            (new Query('node'))
                                ->setSelectionSet([
                                    'id',
                                    (new Query('cloudObject'))
                                        ->setSelectionSet(CloudObjectIntegration::getCloudObjectEntitySelectionSet()),
                                    'created',
                                    'modified',
                                ]),
                        ]),
                ]),

            (new Query('value'))
                ->setSelectionSet([
                    'baseValue',
                    'currencyId',
                    'valueForeign',
                ]),

            (new Query('unit'))
                ->setSelectionSet(PipelinerSalesUnitIntegration::getSalesUnitEntitySelectionSet()),

            (new Query('step'))
                ->setSelectionSet([
                    ...PipelinerStepIntegration::getStepEntitySelectionSet(),
                    (new Query('pipeline'))
                        ->setSelectionSet(['id', 'name']),
                ]),

            (new Query('owner'))
                ->setSelectionSet([
                    'id',
                    'formattedName',
                    'email',
                    'firstName',
                    'lastName',
                ]),

            (new Query('primaryAccount'))
                ->setSelectionSet(PipelinerAccountIntegration::getAccountEntitySelectionSet()),

            (new Query('primaryContact'))
                ->setSelectionSet(PipelinerContactIntegration::getContactEntitySelectionSet()),

            (new Query('productCurrency'))
                ->setSelectionSet(PipelinerCurrencyIntegration::getCurrencyEntitySelectionSet()),

            'customFields',
        ];
    }
}
