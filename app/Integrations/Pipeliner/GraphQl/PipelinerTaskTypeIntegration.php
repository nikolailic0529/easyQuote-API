<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\AppointmentTypeEntity;
use App\Integrations\Pipeliner\Models\TaskTypeEntity;
use GraphQL\Query;
use GraphQL\QueryBuilder\QueryBuilder;

class PipelinerTaskTypeIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    /**
     * @return TaskTypeEntity[]
     * @throws GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function getAll(): array
    {
        $builder = (new QueryBuilder())
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('taskType'))
                            ->selectField(
                                (new Query('getAll'))
                                    ->setSelectionSet(static::getTaskTypeEntitySelectionSet())
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.taskType.getAll');

        return array_map(TaskTypeEntity::fromArray(...), $data);
    }


    public static function getTaskTypeEntitySelectionSet(): array
    {
        return [
            'id',
            'name',
            'isReadonly',
            'canChangeReadonly',
            'created',
            'modified',
            'revision',
        ];
    }
}