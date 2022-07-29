<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Defaults;
use App\Integrations\Pipeliner\Exceptions\EntityNotFoundException;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\AccountEntity;
use App\Integrations\Pipeliner\Models\AccountFilterInput;
use App\Integrations\Pipeliner\Models\CreateAccountInput;
use App\Integrations\Pipeliner\Models\CreateOrUpdateContactAccountRelationInputCollection;
use App\Integrations\Pipeliner\Models\UpdateAccountInput;
use App\Integrations\Pipeliner\Models\UpdateAccountInputCollection;
use App\Integrations\Pipeliner\Models\ValidationLevelCollection;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;
use GraphQL\RawObject;

class PipelinerAccountIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    public function scroll(string             $after = null,
                           string             $before = null,
                           AccountFilterInput $filter = null,
                           int                $first = 10): AccountEntityScrollIterator
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $iterator = $this->scrollGenerator(after: $after, before: $before, filter: $filter, first: $first);

        return new AccountEntityScrollIterator($iterator);
    }

    public function simpleScroll(string             $after = null,
                                 string             $before = null,
                                 AccountFilterInput $filter = null,
                                 int                $first = 10): \Generator
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->simpleScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    protected function scrollGenerator(string             $after = null,
                                       string             $before = null,
                                       AccountFilterInput $filter = null,
                                       int                $first = 10): \Generator
    {
        $builder = (new QueryBuilder())
            ->setVariable('after', 'String')
            ->setVariable('before', 'String')
            ->setVariable('filter', 'AccountFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('account'))
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
                                                    ->setSelectionSet(static::getAccountEntitySelectionSet()),
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

        $hasNextPage = $response->json('data.entities.account.getByCriteria.pageInfo.hasNextPage');
        $after = $response->json('data.entities.account.getByCriteria.pageInfo.endCursor');

        foreach ($response->json('data.entities.account.getByCriteria.edges.*.node') as $node) {
            yield $after => AccountEntity::fromArray($node);
        }

        if ($hasNextPage) {
            yield from $this->scrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        }
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    protected function simpleScrollGenerator(string             $after = null,
                                             string             $before = null,
                                             AccountFilterInput $filter = null,
                                             int                $first = 10): \Generator
    {
        $builder = (new QueryBuilder())
            ->setVariable('after', 'String')
            ->setVariable('before', 'String')
            ->setVariable('filter', 'AccountFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('account'))
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
                                                        'id', 'modified',
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

        $hasNextPage = $response->json('data.entities.account.getByCriteria.pageInfo.hasNextPage');
        $after = $response->json('data.entities.account.getByCriteria.pageInfo.endCursor');

        foreach ($response->json('data.entities.account.getByCriteria.edges.*.node') as $node) {
            yield $after => $node;
        }

        if ($hasNextPage) {
            yield from $this->simpleScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        }
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function getById(string $id): AccountEntity
    {
        $builder = (new QueryBuilder())
            ->setVariable('id', 'ID!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('account'))
                            ->selectField(
                                (new Query('getById'))
                                    ->setArguments([
                                        'entityId' => '$id',
                                    ])
                                    ->setSelectionSet(static::getAccountEntitySelectionSet())
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

        $value = $response->json('data.entities.account.getById');

        if (null === $value) {
            throw EntityNotFoundException::notFoundById($id, 'account');
        }

        return AccountEntity::fromArray($value);
    }

    /**
     * @param string ...$ids
     * @return AccountEntity[]
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function getByIds(string ...$ids): array
    {
        $builder = (new QueryBuilder())
            ->setVariable('ids', '[ID!]!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('account'))
                            ->selectField(
                                (new Query('getByIds'))
                                    ->setArguments([
                                        'entityIds' => '$ids',
                                    ])
                                    ->setSelectionSet(static::getAccountEntitySelectionSet())
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

        $value = $response->json('data.entities.account.getByIds');

        return array_map(AccountEntity::fromArray(...), $value);
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function getByCriteria(AccountFilterInput $filter = null, int $first = Defaults::DEFAULT_LIMIT): array
    {
        $builder = (new QueryBuilder())
            ->setVariable('filter', 'AccountFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('account'))
                            ->selectField(
                                (new Query('getByCriteria'))
                                    ->setArguments(['filter' => '$filter', 'first' => $first])
                                    ->setSelectionSet([
                                        (new Query('edges'))
                                            ->setSelectionSet([
                                                (new Query('node'))
                                                    ->setSelectionSet(static::getAccountEntitySelectionSet()),
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

        $data = $response->json('data.entities.account.getByCriteria.edges.*.node');

        return array_map(AccountEntity::fromArray(...), $data);
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function create(CreateAccountInput $input): AccountEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateAccountInput', isRequired: true)
            ->selectField(
                (new Mutation('createAccount'))
                    ->setArguments([
                        'input' => '$input',
                        'validationLevel' => new RawObject('[SKIP_USER_DEFINED_VALIDATIONS]'),
                    ])
                    ->setSelectionSet([
                        (new Query('account'))
                            ->setSelectionSet(static::getAccountEntitySelectionSet()),
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

        return AccountEntity::fromArray($response->json('data.createAccount.account'));
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function update(UpdateAccountInput $input): AccountEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'UpdateAccountInput', isRequired: true)
            ->selectField(
                (new Mutation('updateAccount'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('account'))
                            ->setSelectionSet(static::getAccountEntitySelectionSet()),
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

        return AccountEntity::fromArray($response->json('data.updateAccount.account'));
    }

    public function bulkUpdate(UpdateAccountInputCollection $input,
                               ValidationLevelCollection    $validationLevel = null): array
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: '[CreateOrUpdateAccountInput!]', isRequired: true)
            ->setVariable(name: 'validationLevel', type: '[ValidationLevel!]')
            ->selectField(
                (new Mutation('bulkUpdateAccount'))
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

        return $response->json('data.bulkUpdateAccount.result');
    }

    public function bulkUpdateContactAccountRelation(CreateOrUpdateContactAccountRelationInputCollection $input,
                                                     ValidationLevelCollection                           $validationLevel = null): array
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: '[CreateOrUpdateContactAccountRelationInput!]', isRequired: true)
            ->setVariable(name: 'validationLevel', type: '[ValidationLevel!]')
            ->selectField(
                (new Mutation('bulkUpdateContactAccountRelation'))
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

        return $response->json('data.bulkUpdateContactAccountRelation.result');
    }

    public function deleteContactAccountRelation(string $id): void
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'DeleteContactAccountRelationInput', isRequired: true)
            ->selectField(
                (new Mutation('deleteContactAccountRelation'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('contactAccountRelation'))
                            ->setSelectionSet([
                                'id',
                            ]),
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

    public static function getAccountEntitySelectionSet(): array
    {
        return [
            'id',
            'name',
            'formattedName',
            'email1',
            'phone1',
            'address',
            'city',
            'zipCode',
            'stateProvince',
            'country',
            'homePage',
            'customFields',
            'created',
            'modified',
            'revision',

            (new Query('unit'))
                ->setSelectionSet(
                    PipelinerSalesUnitIntegration::getSalesUnitEntitySelectionSet()
                ),

            (new Query('customerType'))
                ->setSelectionSet(['id', 'optionName', 'calcValue']),

            (new Query('picture'))
                ->setSelectionSet([
                    'id',
                    'filename',
                    'isPublic',
                    'mimeType',
                    'params',
                    'size',
                    'type',
                    'url',
                    'publicUrl',
                    'created',
                    'modified',
                ]),

            (new Query('documents'))
                ->setSelectionSet([
                    (new Query('edges'))
                        ->setSelectionSet([
                            (new Query('node'))
                                ->setSelectionSet([
                                    'id',
                                    (new Query('cloudObject'))
                                        ->setSelectionSet([
                                            'id',
                                            'filename',
                                            'isPublic',
                                            'mimeType',
                                            'params',
                                            'size',
                                            'type',
                                            'url',
                                            'publicUrl',
                                            'created',
                                            'modified',
                                        ]),
                                    'created',
                                    'modified',
                                ]),
                        ]),
                ]),
        ];
    }
}