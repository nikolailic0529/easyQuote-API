<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Foundation\Http\Client\GuzzleReactBridge\Utils;
use App\Integrations\Pipeliner\Defaults;
use App\Integrations\Pipeliner\Exceptions\EntityNotFoundException;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\CreateNoteInput;
use App\Integrations\Pipeliner\Models\NoteEntity;
use App\Integrations\Pipeliner\Models\NoteFilterInput;
use App\Integrations\Pipeliner\Models\UpdateNoteInput;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;
use GraphQL\RawObject;
use function React\Async\await;

class PipelinerNoteIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function create(CreateNoteInput $input): NoteEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateNoteInput', isRequired: true)
            ->selectField(
                (new Mutation('createNote'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('note'))
                            ->setSelectionSet(static::getNoteEntitySelectionSet()),
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

        return NoteEntity::fromArray($response->json('data.createNote.note'));
    }

    /**
     * @param UpdateNoteInput $input
     * @return NoteEntity
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function update(UpdateNoteInput $input): NoteEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'UpdateNoteInput', isRequired: true)
            ->selectField(
                (new Mutation('updateNote'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('note'))
                            ->setSelectionSet(static::getNoteEntitySelectionSet()),
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

        return NoteEntity::fromArray($response->json('data.updateNote.note'));
    }

    public function getById(string $entityId): NoteEntity
    {
        $builder = (new QueryBuilder())
            ->setVariable('entityId', 'ID!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('note'))
                            ->selectField(
                                (new Query('getById'))
                                    ->setArguments([
                                        'entityId' => '$entityId',
                                    ])
                                    ->setSelectionSet(static::getNoteEntitySelectionSet())
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

        $value = $response->json('data.entities.note.getById');

        if (null === $value) {
            throw EntityNotFoundException::notFoundById($entityId, 'note');
        }

        return NoteEntity::fromArray($value);
    }

    /**
     * @param string ...$ids
     * @return NoteEntity[]
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
                        (new QueryBuilder('note'))
                            ->selectField(
                                (new Query('getByIds'))
                                    ->setArguments([
                                        'entityIds' => '$ids',
                                    ])
                                    ->setSelectionSet(static::getNoteEntitySelectionSet())
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

        $value = $response->json('data.entities.note.getByIds');

        return array_map(NoteEntity::fromArray(...), $value);
    }

    public function scroll(string $after = null, string $before = null, NoteFilterInput $filter = null, int $first = Defaults::DEFAULT_LIMIT): NoteEntityScrollIterator
    {
        $iterator = $this->scrollGenerator(after: $after, before: $before, filter: $filter, first: $first);

        return new NoteEntityScrollIterator($iterator);
    }

    public function simpleScroll(string $after = null, string $before = null, NoteFilterInput $filter = null, int $first = Defaults::DEFAULT_LIMIT): \Generator
    {
        return $this->simpleScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
    }

    protected function simpleScrollGenerator(string $after = null, string $before = null, NoteFilterInput $filter = null, int $first = Defaults::DEFAULT_LIMIT): \Generator
    {
        $builder = (new QueryBuilder())
            ->setVariable('first', 'Int')
            ->setVariable('after', 'String')
            ->setVariable('before', 'String')
            ->setVariable('filter', 'NoteFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('note'))
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

        $hasNextPage = $response->json('data.entities.note.getByCriteria.pageInfo.hasNextPage');
        $after = $response->json('data.entities.note.getByCriteria.pageInfo.endCursor');

        foreach ($response->json('data.entities.note.getByCriteria.edges.*.node') as $node) {
            yield $after => $node;
        }

        if ($hasNextPage) {
            yield from $this->simpleScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        }
    }

    protected function scrollGenerator(string $after = null, string $before = null, NoteFilterInput $filter = null, int $first = Defaults::DEFAULT_LIMIT): \Generator
    {
        $builder = (new QueryBuilder())
            ->setVariable('first', 'Int')
            ->setVariable('after', 'String')
            ->setVariable('before', 'String')
            ->setVariable('filter', 'NoteFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('note'))
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
                                                    ->setSelectionSet(static::getNoteEntitySelectionSet()),
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

        $hasNextPage = $response->json('data.entities.note.getByCriteria.pageInfo.hasNextPage');
        $after = $response->json('data.entities.note.getByCriteria.pageInfo.endCursor');

        foreach ($response->json('data.entities.note.getByCriteria.edges.*.node') as $node) {
            yield $after => NoteEntity::fromArray($node);
        }

        if ($hasNextPage) {
            yield from $this->scrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        }
    }

    public static function getNoteEntitySelectionSet(): array
    {
        return [
            'id',
            'accountId',
            'contactId',
            'leadOpptyId',
            'projectId',
            'note',
            'created',
            'modified',
            'revision',

            (new Query('owner'))
                ->setSelectionSet(
                    PipelinerClientIntegration::getClientEntitySelectionSet()
                ),
        ];
    }
}