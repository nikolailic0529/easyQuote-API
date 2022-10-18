<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Defaults;
use App\Integrations\Pipeliner\Exceptions\EntityNotFoundException;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\AppointmentEntity;
use App\Integrations\Pipeliner\Models\AppointmentFilterInput;
use App\Integrations\Pipeliner\Models\CreateAppointmentInput;
use App\Integrations\Pipeliner\Models\UpdateAppointmentInput;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;
use GraphQL\RawObject;

class PipelinerAppointmentIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function create(CreateAppointmentInput $input): AppointmentEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateAppointmentInput', isRequired: true)
            ->selectField(
                (new Mutation('createAppointment'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('appointment'))
                            ->setSelectionSet(static::getAppointmentEntitySelectionSet()),
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

        return AppointmentEntity::fromArray($response->json('data.createAppointment.appointment'));
    }

    /**
     * @param UpdateAppointmentInput $input
     * @return AppointmentEntity
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function update(UpdateAppointmentInput $input): AppointmentEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'UpdateAppointmentInput', isRequired: true)
            ->selectField(
                (new Mutation('updateAppointment'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('appointment'))
                            ->setSelectionSet(static::getAppointmentEntitySelectionSet()),
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

        return AppointmentEntity::fromArray($response->json('data.updateAppointment.appointment'));
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     * @throws EntityNotFoundException
     */
    public function getById(string $entityId): AppointmentEntity
    {
        $builder = (new QueryBuilder())
            ->setVariable('entityId', 'ID!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('appointment'))
                            ->selectField(
                                (new Query('getById'))
                                    ->setArguments([
                                        'entityId' => '$entityId',
                                    ])
                                    ->setSelectionSet(static::getAppointmentEntitySelectionSet())
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'entityId' => $entityId,
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $value = $response->json('data.entities.appointment.getById');

        if (null === $value) {
            throw EntityNotFoundException::notFoundById($entityId, 'appointment');
        }

        return AppointmentEntity::fromArray($value);
    }

    /**
     * @return AppointmentEntity[]
     * @throws GraphQlRequestException
     * @throws EntityNotFoundException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function getByIds(string ...$ids): array
    {
        $builder = (new QueryBuilder())
            ->setVariable('ids', '[ID!]!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('appointment'))
                            ->selectField(
                                (new Query('getByIds'))
                                    ->setArguments([
                                        'entityIds' => '$ids',
                                    ])
                                    ->setSelectionSet(static::getAppointmentEntitySelectionSet())
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'ids' => $ids,
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $value = $response->json('data.entities.appointment.getByIds');

        return array_map(AppointmentEntity::fromArray(...), $value);
    }

    public function scroll(string                 $after = null,
                           string                 $before = null,
                           AppointmentFilterInput $filter = null,
                           int                    $first = Defaults::DEFAULT_LIMIT): AppointmentEntityScrollIterator
    {
        $iterator = $this->scrollGenerator(after: $after, before: $before, filter: $filter, first: $first);

        return new AppointmentEntityScrollIterator($iterator);
    }

    public function simpleScroll(string                 $after = null,
                                 string                 $before = null,
                                 AppointmentFilterInput $filter = null,
                                 int                    $first = Defaults::DEFAULT_LIMIT): \Generator
    {
        return $this->simpleScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
    }

    protected function simpleScrollGenerator(string                 $after = null,
                                             string                 $before = null,
                                             AppointmentFilterInput $filter = null,
                                             int                    $first = Defaults::DEFAULT_LIMIT): \Generator
    {
        $builder = (new QueryBuilder())
            ->setVariable('first', 'Int')
            ->setVariable('after', 'String')
            ->setVariable('before', 'String')
            ->setVariable('filter', 'AppointmentFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('appointment'))
                            ->selectField(
                                (new Query('getByCriteria'))
                                    ->setArguments([
                                        'orderBy' => new RawObject('{modified: Asc}'),
                                        'first' => '$first',
                                        'filter' => '$filter',
                                        'after' => '$after',
                                        'before' => '$before',
                                    ])
                                    ->setSelectionSet([
                                        (new Query('edges'))
                                            ->setSelectionSet([
                                                (new Query('node'))
                                                    ->setSelectionSet(['id', 'modified']),
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
                    'first' => $first,
                    'after' => $after,
                    'before' => $before,
                    'filter' => $filter?->jsonSerialize(),
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $hasNextPage = $response->json('data.entities.appointment.getByCriteria.pageInfo.hasNextPage');
        $after = $response->json('data.entities.appointment.getByCriteria.pageInfo.endCursor');

        foreach ($response->json('data.entities.appointment.getByCriteria.edges.*.node') as $node) {
            yield $after => $node;
        }

        if ($hasNextPage) {
            yield from $this->simpleScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        }
    }

    protected function scrollGenerator(string                 $after = null,
                                       string                 $before = null,
                                       AppointmentFilterInput $filter = null,
                                       int                    $first = Defaults::DEFAULT_LIMIT): \Generator
    {
        $builder = (new QueryBuilder())
            ->setVariable('first', 'Int')
            ->setVariable('after', 'String')
            ->setVariable('before', 'String')
            ->setVariable('filter', 'AppointmentFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('appointment'))
                            ->selectField(
                                (new Query('getByCriteria'))
                                    ->setArguments([
                                        'orderBy' => new RawObject('{modified: Asc}'),
                                        'first' => '$first',
                                        'filter' => '$filter',
                                        'after' => '$after',
                                        'before' => '$before',
                                    ])
                                    ->setSelectionSet([
                                        (new Query('edges'))
                                            ->setSelectionSet([
                                                (new Query('node'))
                                                    ->setSelectionSet(static::getAppointmentEntitySelectionSet()),
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
                    'first' => $first,
                    'after' => $after,
                    'before' => $before,
                    'filter' => $filter?->jsonSerialize(),
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $hasNextPage = $response->json('data.entities.appointment.getByCriteria.pageInfo.hasNextPage');
        $after = $response->json('data.entities.appointment.getByCriteria.pageInfo.endCursor');

        foreach ($response->json('data.entities.appointment.getByCriteria.edges.*.node') as $node) {
            yield $after => AppointmentEntity::fromArray($node);
        }

        if ($hasNextPage) {
            yield from $this->scrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        }
    }

    public static function getAppointmentEntitySelectionSet(): array
    {
        return [
            'id',
            'subject',
            'status',
            'description',
            'location',
            'startDate',
            'endDate',
            'created',
            'modified',
            'revision',
            (new Query('activityType'))
                ->setSelectionSet(
                    PipelinerAppointmentTypeIntegration::getAppointmentTypeEntitySelectionSet()
                ),
            (new Query('owner'))
                ->setSelectionSet(
                    PipelinerClientIntegration::getClientEntitySelectionSet()
                ),
            (new Query('unit'))
                ->setSelectionSet(
                    PipelinerSalesUnitIntegration::getSalesUnitEntitySelectionSet()
                ),
            (new Query('reminder'))
                ->setSelectionSet([
                    'id',
                    'endDateOffset',
                    'snoozeDate',
                    'status',
                ]),
            (new Query('accountRelations'))
                ->setSelectionSet([
                    (new Query('edges'))
                        ->setSelectionSet([
                            (new Query('node'))
                                ->setSelectionSet([
                                    'id',
                                    'accountId',
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
                                    'contactId',
                                ]),
                        ]),
                ]),
            (new Query('opportunityRelations'))
                ->setSelectionSet([
                    (new Query('edges'))
                        ->setSelectionSet([
                            (new Query('node'))
                                ->setSelectionSet([
                                    'id',
                                    'leadOpptyId',
                                ]),
                        ]),
                ]),
            (new Query('inviteesClients'))
                ->setSelectionSet([
                    (new Query('edges'))
                        ->setSelectionSet([
                            (new Query('node'))
                                ->setSelectionSet([
                                    'id',
                                    'clientId',
                                ]),
                        ]),
                ]),
            (new Query('inviteesContacts'))
                ->setSelectionSet([
                    (new Query('edges'))
                        ->setSelectionSet([
                            (new Query('node'))
                                ->setSelectionSet([
                                    'id',
                                    'email',
                                    'inviteeType',
                                    'response',
                                    'contactId',
                                    'firstName',
                                    'lastName',
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