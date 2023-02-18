<?php

namespace App\Domain\Pipeliner\Integration\GraphQl;

use App\Domain\Pipeliner\Integration\Defaults;
use App\Domain\Pipeliner\Integration\Exceptions\EntityNotFoundException;
use App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException;
use App\Domain\Pipeliner\Integration\Models\CreateTaskInput;
use App\Domain\Pipeliner\Integration\Models\RemoveReminderTaskInput;
use App\Domain\Pipeliner\Integration\Models\SetReminderTaskInput;
use App\Domain\Pipeliner\Integration\Models\TaskEntity;
use App\Domain\Pipeliner\Integration\Models\TaskFilterInput;
use App\Domain\Pipeliner\Integration\Models\UpdateTaskInput;
use App\Foundation\Http\Client\GuzzleReactBridge\Utils;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;
use GraphQL\RawObject;

use function React\Async\await;

class PipelinerTaskIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function create(CreateTaskInput $input): TaskEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateTaskInput', isRequired: true)
            ->selectField(
                (new Mutation('createTask'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('task'))
                            ->setSelectionSet(static::getTaskEntitySelectionSet()),
                    ])
            );

        $promise = $this->client->async()
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'input' => $input->jsonSerialize(),
                ],
            ]);

        $response = await(Utils::adapt($promise));

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        return TaskEntity::fromArray($response->json('data.createTask.task'));
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function update(UpdateTaskInput $input): TaskEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'UpdateTaskInput', isRequired: true)
            ->selectField(
                (new Mutation('updateTask'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('task'))
                            ->setSelectionSet(static::getTaskEntitySelectionSet()),
                    ])
            );

        $promise = $this->client->async()
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'input' => $input->jsonSerialize(),
                ],
            ]);

        $response = await(Utils::adapt($promise));

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        return TaskEntity::fromArray($response->json('data.updateTask.task'));
    }

    public function setReminder(SetReminderTaskInput $input): TaskEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'SetReminderTaskInput', isRequired: true)
            ->selectField(
                (new Mutation('setReminderTask'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('result'))
                            ->setSelectionSet(static::getTaskEntitySelectionSet()),
                    ])
            );

        $promise = $this->client->async()
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'input' => $input->jsonSerialize(),
                ],
            ]);

        $response = await(Utils::adapt($promise));

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        return TaskEntity::fromArray($response->json('data.setReminderTask.result'));
    }

    public function removeReminder(RemoveReminderTaskInput $input): TaskEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'RemoveReminderTaskInput', isRequired: true)
            ->selectField(
                (new Mutation('removeReminderTask'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('result'))
                            ->setSelectionSet(static::getTaskEntitySelectionSet()),
                    ])
            );

        $promise = $this->client->async()
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'input' => $input->jsonSerialize(),
                ],
            ]);

        $response = await(Utils::adapt($promise));

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        return TaskEntity::fromArray($response->json('data.removeReminderTask.result'));
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws EntityNotFoundException
     */
    public function getById(string $entityId): TaskEntity
    {
        $builder = (new QueryBuilder())
            ->setVariable('entityId', 'ID!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('task'))
                            ->selectField(
                                (new Query('getById'))
                                    ->setArguments([
                                        'entityId' => '$entityId',
                                    ])
                                    ->setSelectionSet(static::getTaskEntitySelectionSet())
                            )
                    )
            );

        $promise = $this->client->async()
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'entityId' => $entityId,
                ],
            ]);

        $response = await(Utils::adapt($promise));

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $value = $response->json('data.entities.task.getById');

        if (null === $value) {
            throw EntityNotFoundException::notFoundById($entityId, 'task');
        }

        return TaskEntity::fromArray($value);
    }

    /**
     * @return TaskEntity[]
     *
     * @throws \Illuminate\Http\Client\RequestException
     * @throws EntityNotFoundException
     * @throws GraphQlRequestException
     */
    public function getByIds(string ...$ids): array
    {
        $builder = (new QueryBuilder())
            ->setVariable('ids', '[ID!]!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('task'))
                            ->selectField(
                                (new Query('getByIds'))
                                    ->setArguments([
                                        'entityIds' => '$ids',
                                    ])
                                    ->setSelectionSet(static::getTaskEntitySelectionSet())
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

        $value = $response->json('data.entities.task.getByIds');

        return array_map(TaskEntity::fromArray(...), $value);
    }

    public function scroll(string $after = null,
                           string $before = null,
                           TaskFilterInput $filter = null,
                           int $first = Defaults::DEFAULT_LIMIT): TaskEntityScrollIterator
    {
        $iterator = $this->scrollGenerator(after: $after, before: $before, filter: $filter, first: $first);

        return new TaskEntityScrollIterator($iterator);
    }

    public function simpleScroll(string $after = null,
                                 string $before = null,
                                 TaskFilterInput $filter = null,
                                 int $first = Defaults::DEFAULT_LIMIT): \Generator
    {
        return $this->simpleScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
    }

    protected function simpleScrollGenerator(string $after = null,
                                             string $before = null,
                                             TaskFilterInput $filter = null,
                                             int $first = Defaults::DEFAULT_LIMIT): \Generator
    {
        $builder = (new QueryBuilder())
            ->setVariable('first', 'Int')
            ->setVariable('after', 'String')
            ->setVariable('before', 'String')
            ->setVariable('filter', 'TaskFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('task'))
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

        $hasNextPage = $response->json('data.entities.task.getByCriteria.pageInfo.hasNextPage');
        $after = $response->json('data.entities.task.getByCriteria.pageInfo.endCursor');

        foreach ($response->json('data.entities.task.getByCriteria.edges.*.node') as $node) {
            yield $after => $node;
        }

        if ($hasNextPage) {
            yield from $this->simpleScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        }
    }

    protected function scrollGenerator(string $after = null,
                                       string $before = null,
                                       TaskFilterInput $filter = null,
                                       int $first = Defaults::DEFAULT_LIMIT): \Generator
    {
        $builder = (new QueryBuilder())
            ->setVariable('first', 'Int')
            ->setVariable('after', 'String')
            ->setVariable('before', 'String')
            ->setVariable('filter', 'TaskFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('task'))
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
                                                    ->setSelectionSet(static::getTaskEntitySelectionSet()),
                                            ]),
                                        (new Query('pageInfo'))
                                            ->setSelectionSet([
                                                'startCursor', 'endCursor', 'hasNextPage', 'hasPreviousPage',
                                            ]),
                                    ])
                            )
                    )
            );

        $promise = $this->client->async()
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'first' => $first,
                    'after' => $after,
                    'before' => $before,
                    'filter' => $filter?->jsonSerialize(),
                ],
            ]);

        $response = await(Utils::adapt($promise));

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $hasNextPage = $response->json('data.entities.task.getByCriteria.pageInfo.hasNextPage');
        $after = $response->json('data.entities.task.getByCriteria.pageInfo.endCursor');

        foreach ($response->json('data.entities.task.getByCriteria.edges.*.node') as $node) {
            yield $after => TaskEntity::fromArray($node);
        }

        if ($hasNextPage) {
            yield from $this->scrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        }
    }

    public static function getTaskEntitySelectionSet(): array
    {
        return [
            'id',
            'subject',
            'callDuration',
            'priority',
            'status',
            'description',
            'startDate',
            'dueDate',
            'revision',
            'created',
            'modified',
            (new Query('activityType'))
                ->setSelectionSet(
                    PipelinerTaskTypeIntegration::getTaskTypeEntitySelectionSet()
                ),
            (new Query('owner'))
                ->setSelectionSet(
                    PipelinerClientIntegration::getClientEntitySelectionSet()
                ),
            (new Query('unit'))
                ->setSelectionSet(
                    PipelinerSalesUnitIntegration::getSalesUnitEntitySelectionSet()
                ),
            (new Query('taskRecurrence'))
                ->setSelectionSet([
                    'id',
                    'type',
                    'day',
                    'dayOfWeek',
                    'startDate',
                    'endDate',
                    'month',
                    'week',
                    'occurEvery',
                    'occurrencesCount',
                ]),
            (new Query('reminder'))
                ->setSelectionSet([
                    'id',
                    'setDate',
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
        ];
    }
}
