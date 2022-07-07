<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\CreateWebhookInput;
use App\Integrations\Pipeliner\Models\WebhookEntity;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;

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
        ];
    }
}