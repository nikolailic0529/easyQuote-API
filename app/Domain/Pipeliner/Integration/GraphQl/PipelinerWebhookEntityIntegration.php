<?php

namespace App\Domain\Pipeliner\Integration\GraphQl;

use App\Domain\Pipeliner\Integration\Exceptions\EntityNotFoundException;
use App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException;
use App\Domain\Pipeliner\Integration\Models\CreateWebhookInput;
use App\Domain\Pipeliner\Integration\Models\WebhookEntity;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;
use Illuminate\Http\Client\RequestException;

class PipelinerWebhookEntityIntegration
{
    public function __construct(protected PipelinerGraphQlClient $client)
    {
    }

    /**
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function create(CreateWebhookInput $input): WebhookEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateWebhookInput', isRequired: true)
            ->selectField(
                (new Mutation('createWebhook'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('webhook'))
                            ->setSelectionSet(static::getWebhookEntitySelectionSet()),
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

        return WebhookEntity::fromArray($response->json('data.createWebhook.webhook'));
    }

    public function delete(string $id): void
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'DeleteWebhookInput', isRequired: true)
            ->selectField(
                (new Mutation('deleteWebhook'))
                    ->setArguments(['input' => '$input'])
                    ->setSelectionSet([
                        (new Query('webhook'))
                            ->setSelectionSet(['id']),
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
     * @return WebhookEntity[]
     *
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function getAll(): array
    {
        $builder = (new QueryBuilder())
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('webhook'))
                            ->selectField(
                                (new Query('getAll'))
                                    ->setSelectionSet(self::getWebhookEntitySelectionSet())
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.webhook.getAll');

        return array_map(WebhookEntity::fromArray(...), $data);
    }

    /**
     * @return WebhookEntity[]
     *
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function getByIds(string $id, string ...$ids): array
    {
        $ids = [$id, ...$ids];

        $builder = (new QueryBuilder())
            ->setVariable('ids', '[ID!]!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('webhook'))
                            ->selectField(
                                (new Query('getByIds'))
                                    ->setArguments([
                                        'entityIds' => '$ids',
                                    ])
                                    ->setSelectionSet(self::getWebhookEntitySelectionSet())
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

        $data = $response->json('data.entities.webhook.getByIds');

        return array_map(WebhookEntity::fromArray(...), $data);
    }

    /**
     * @throws RequestException
     * @throws GraphQlRequestException
     * @throws EntityNotFoundException
     */
    public function getById(string $id): WebhookEntity
    {
        $builder = (new QueryBuilder())
            ->setVariable('id', 'ID!')
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('webhook'))
                            ->selectField(
                                (new Query('getById'))
                                    ->setArguments([
                                        'entityId' => '$id',
                                    ])
                                    ->setSelectionSet(static::getWebhookEntitySelectionSet())
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

        $value = $response->json('data.entities.webhook.getById');

        if (null === $value) {
            throw EntityNotFoundException::notFoundById($id, 'webhook');
        }

        return WebhookEntity::fromArray($value);
    }

    public static function getWebhookEntitySelectionSet(): array
    {
        return [
            'id',
            'insecureSsl',
            'options',
            'signature',
            'url',
            'events',
            'created',
            'modified',
            'isDeleted',
        ];
    }
}
