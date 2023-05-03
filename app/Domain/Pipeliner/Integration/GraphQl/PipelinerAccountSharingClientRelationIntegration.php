<?php

namespace App\Domain\Pipeliner\Integration\GraphQl;

use App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException;
use App\Domain\Pipeliner\Integration\Models\AccountSharingClientRelationEntity;
use App\Domain\Pipeliner\Integration\Models\AccountSharingClientRelationFilterInput;
use GraphQL\Query;
use GraphQL\QueryBuilder\QueryBuilder;
use Illuminate\Support\LazyCollection;

class PipelinerAccountSharingClientRelationIntegration
{
    public function __construct(
        protected readonly PipelinerGraphQlClient $client
    ) {
    }

    public function scroll(
        string $after = null,
        string $before = null,
        AccountSharingClientRelationFilterInput $filter = null,
        int $first = 10
    ): AccountSharingClientRelationScrollIterator {
        /** @noinspection PhpUnhandledExceptionInspection */
        $iterator = $this->scrollGenerator(after: $after, before: $before, filter: $filter, first: $first);

        return new AccountSharingClientRelationScrollIterator($iterator);
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    protected function scrollGenerator(
        string $after = null,
        string $before = null,
        AccountSharingClientRelationFilterInput $filter = null,
        int $first = 10
    ): \Generator {
        yield from LazyCollection::make(function () use ($after, $before, $filter, $first): \Generator {
            yield from $this->rawScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        })
            ->map(static function (array $item): AccountSharingClientRelationEntity {
                return AccountSharingClientRelationEntity::fromArray($item);
            });
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    protected function rawScrollGenerator(
        string $after = null,
        string $before = null,
        AccountSharingClientRelationFilterInput $filter = null,
        int $first = 10
    ): \Generator {
        $builder = (new QueryBuilder())
            ->setVariable('after', 'String')
            ->setVariable('before', 'String')
            ->setVariable('filter', 'AccountSharingClientRelationFilterInput')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('accountSharingClientRelation'))
                            ->selectField(
                                (new Query('getByCriteria'))
                                    ->setArguments([
                                        'filter' => '$filter',
                                        'first' => $first,
                                        'after' => '$after',
                                        'before' => '$before',
                                    ])
                                    ->setSelectionSet([
                                        (new Query('edges'))
                                            ->setSelectionSet([
                                                (new Query('node'))
                                                    ->setSelectionSet(static::getAccountSharingClientRelationSelectionSet()),
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

        $hasNextPage = $response->json('data.entities.accountSharingClientRelation.getByCriteria.pageInfo.hasNextPage');
        $after = $response->json('data.entities.accountSharingClientRelation.getByCriteria.pageInfo.endCursor');

        foreach ($response->json('data.entities.accountSharingClientRelation.getByCriteria.edges.*.node') as $node) {
            yield $after => $node;
        }

        unset($builder, $response);

        if ($hasNextPage) {
            yield from $this->rawScrollGenerator(after: $after, before: $before, filter: $filter, first: $first);
        }
    }

    public static function getAccountSharingClientRelationSelectionSet(): array
    {
        return [
            'id',
            'role',
            (new Query('client'))
                ->setSelectionSet(PipelinerClientIntegration::getClientEntitySelectionSet()),
        ];
    }
}
