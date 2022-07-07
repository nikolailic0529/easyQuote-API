<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\CloudObjectEntity;
use App\Integrations\Pipeliner\Models\CreateCloudObjectInput;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;

class CloudObjectIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    public function create(CreateCloudObjectInput $input): CloudObjectEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateCloudObjectInput', isRequired: true)
            ->selectField(
                (new Mutation('createCloudObject'))
                    ->setArguments([
                        'input' => '$input',
                    ])
                    ->setSelectionSet([
                        (new Query('cloudObject'))
                            ->setSelectionSet(static::getCloudObjectEntitySelectionSet()),
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

        return CloudObjectEntity::fromArray($response->json('data.createCloudObject.cloudObject'));
    }

    public static function getCloudObjectEntitySelectionSet(): array
    {
        return [
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
            'revision',
        ];
    }
}