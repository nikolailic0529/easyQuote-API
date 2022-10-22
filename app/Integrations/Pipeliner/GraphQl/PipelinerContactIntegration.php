<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Defaults;
use App\Integrations\Pipeliner\Exceptions\EntityNotFoundException;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\BulkUpdateResults;
use App\Integrations\Pipeliner\Models\ContactEntity;
use App\Integrations\Pipeliner\Models\ContactFilterInput;
use App\Integrations\Pipeliner\Models\CreateContactInput;
use App\Integrations\Pipeliner\Models\CreateOrUpdateContactInputCollection;
use App\Integrations\Pipeliner\Models\UpdateContactInput;
use App\Integrations\Pipeliner\Models\ValidationLevelCollection;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;
use GraphQL\RawObject;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Str;

class PipelinerContactIntegration
{
    /** @var PromiseInterface[] */
    protected array $scrollPromiseCache = [];

    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    public function scroll(string $after = null, string $before = null, ContactFilterInput $filter = null, int $first = Defaults::DEFAULT_LIMIT)
    {
        $queryReference = Str::orderedUuid();

        /** @noinspection PhpUnhandledExceptionInspection */
        $iterator = $this->scrollGenerator(after: $after, before: $before, filter: $filter, first: $first, queryReference: $queryReference);

        return new ContactEntityScrollIterator($iterator);
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    protected function scrollGenerator(string $after = null, string $before = null, ContactFilterInput $filter = null, int $first = 10, string $queryReference = ''): \Generator
    {
        $builder = (new QueryBuilder())
            ->setVariable('after', 'String')
            ->setVariable('before', 'String')
            ->setVariable('filter', 'ContactFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('contact'))
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
                                                    ->setSelectionSet(static::getContactEntitySelectionSet()),
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

        if ($queryReference && isset($this->scrollPromiseCache[$queryReference])) {
            $response = $this->scrollPromiseCache[$queryReference]->wait();
        } else {
            $response = $this->client
                ->post($this->client->buildSpaceEndpoint(), [
                    'query' => $builder->getQuery()->__toString(),
                    'variables' => [
                        'after' => $after,
                        'before' => $before,
                        'filter' => $filter?->jsonSerialize(),
                    ],
                ]);
        }

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $hasNextPage = $response->json('data.entities.contact.getByCriteria.pageInfo.hasNextPage');
        $after = $response->json('data.entities.contact.getByCriteria.pageInfo.endCursor');

        if ($hasNextPage && $queryReference) {
            $this->scrollPromiseCache[$queryReference] = $this->client
                ->async()
                ->post($this->client->buildSpaceEndpoint(), [
                    'query' => $builder->getQuery()->__toString(),
                    'variables' => [
                        'after' => $after,
                        'before' => $before,
                        'filter' => $filter?->jsonSerialize(),
                    ],
                ]);
        }

        foreach ($response->json('data.entities.contact.getByCriteria.edges.*.node') as $node) {
            yield $after => ContactEntity::fromArray($node);
        }

        if ($hasNextPage) {
            yield from $this->scrollGenerator(after: $after, before: $before, first: $first, queryReference: $queryReference);
        }
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws EntityNotFoundException
     */
    public function getById(string $id): ContactEntity
    {
        $builder = (new QueryBuilder())
            ->setVariable('id', 'ID!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('contact'))
                            ->selectField(
                                (new Query('getById'))
                                    ->setArguments([
                                        'entityId' => '$id',
                                    ])
                                    ->setSelectionSet(static::getContactEntitySelectionSet())
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

        $value = $response->json('data.entities.contact.getById');

        if (null === $value) {
            throw EntityNotFoundException::notFoundById($id, 'contact');
        }

        return ContactEntity::fromArray($value);
    }

    /**
     * @return ContactEntity[]
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getByIds(string ...$ids): array
    {
        $builder = (new QueryBuilder())
            ->setVariable('ids', '[ID!]!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('contact'))
                            ->selectField(
                                (new Query('getByIds'))
                                    ->setArguments([
                                        'entityIds' => '$ids',
                                    ])
                                    ->setSelectionSet(static::getContactEntitySelectionSet())
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

        $value = $response->json('data.entities.contact.getByIds');

        return array_map(ContactEntity::fromArray(...), $value);
    }

    /**
     * @param ContactFilterInput|null $filter
     * @param int $first
     * @return ContactEntity[]
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getByCriteria(ContactFilterInput $filter = null, int $first = Defaults::DEFAULT_LIMIT): array
    {
        $builder = (new QueryBuilder())
            ->setVariable('filter', 'ContactFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('contact'))
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
                                                    ->setSelectionSet(static::getContactEntitySelectionSet()),
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

        $data = $response->json('data.entities.contact.getByCriteria.edges.*.node');

        return array_map(ContactEntity::fromArray(...), $data);
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function create(CreateContactInput $input, ValidationLevelCollection $validationLevel = null): ContactEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateContactInput', isRequired: true)
            ->setVariable(name: 'validationLevel', type: '[ValidationLevel!]')
            ->selectField(
                (new Mutation('createContact'))
                    ->setArguments([
                        'input' => '$input',
                        'validationLevel' => '$validationLevel',
                    ])
                    ->setSelectionSet([
                        (new Query('contact'))
                            ->setSelectionSet(static::getContactEntitySelectionSet()),
                    ])
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'input' => $input,
                    'validationLevel' => $validationLevel,
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        return ContactEntity::fromArray($response->json('data.createContact.contact'));
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function update(UpdateContactInput $input, ValidationLevelCollection $validationLevel = null): ContactEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'UpdateContactInput', isRequired: true)
            ->setVariable(name: 'validationLevel', type: '[ValidationLevel!]')
            ->selectField(
                (new Mutation('updateContact'))
                    ->setArguments([
                        'input' => '$input',
                        'validationLevel' => '$validationLevel',
                    ])
                    ->setSelectionSet([
                        (new Query('contact'))
                            ->setSelectionSet(static::getContactEntitySelectionSet()),
                    ])
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'input' => $input,
                    'validationLevel' => $validationLevel,
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        return ContactEntity::fromArray($response->json('data.updateContact.contact'));
    }

    public function bulkUpdate(CreateOrUpdateContactInputCollection $input, ValidationLevelCollection $validationLevel = null): BulkUpdateResults
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: '[CreateOrUpdateContactInput!]', isRequired: true)
            ->setVariable(name: 'validationLevel', type: '[ValidationLevel!]')
            ->selectField(
                (new Mutation('bulkUpdateContact'))
                    ->setArguments([
                        'input' => '$input',
                        'validationLevel' => '$validationLevel',
                    ])
                    ->setSelectionSet([
                        (new Query('result'))
                            ->setSelectionSet([
                                'updated',
                                (new Query('created'))
                                    ->setSelectionSet(['index', 'id']),
                                (new Query('errors'))
                                    ->setSelectionSet(['entityId', 'code', 'message', 'index', 'message'])
                            ]),
                    ])
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'input' => $input,
                    'validationLevel' => $validationLevel,
                ],
            ]);

        return BulkUpdateResults::fromArray($response->json('data.bulkUpdateContact.result'));
    }

    public static function getContactEntitySelectionSet(): array
    {
        return [
            'id',
            'address',
            'city',
            'country',
            'email1',
            'phone1',
            'phone2',
            'title',
            'formattedName',
            'firstName',
            'middleName',
            'lastName',
            'zipCode',
            'stateProvince',
            'city',
            'gender',
            'customFields',
            'isDeleted',

            (new Query('owner'))
                ->setSelectionSet(PipelinerClientIntegration::getClientEntitySelectionSet()),

            (new Query('unit'))
                ->setSelectionSet(PipelinerSalesUnitIntegration::getSalesUnitEntitySelectionSet()),

            (new Query('accountRelations'))
                ->setSelectionSet([
                    (new Query('edges'))
                        ->setSelectionSet([
                            (new Query('node'))
                                ->setSelectionSet([
                                    'id',
                                    'accountId',
                                    'isPrimary',
                                    'isAssistant',
                                    'isSibling',
                                ]),
                        ]),
                ]),
        ];
    }
}