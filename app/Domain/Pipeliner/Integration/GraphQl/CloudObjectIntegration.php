<?php

namespace App\Domain\Pipeliner\Integration\GraphQl;

use App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException;
use App\Domain\Pipeliner\Integration\Models\CloudObjectEntity;
use App\Domain\Pipeliner\Integration\Models\CreateCloudObjectInput;
use App\Foundation\Http\Client\GuzzleReactBridge\Utils;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;

use function React\Async\await;

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

        return CloudObjectEntity::fromArray($response->json('data.createCloudObject.cloudObject'));
    }

    public static function getCloudObjectEntitySelectionSet(): array
    {
        return [
            'id',
            (new Query('creator'))
                ->setSelectionSet(
                    PipelinerClientIntegration::getClientEntitySelectionSet()
                ),
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
