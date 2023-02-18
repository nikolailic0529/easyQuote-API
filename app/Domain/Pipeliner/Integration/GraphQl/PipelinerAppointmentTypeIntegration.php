<?php

namespace App\Domain\Pipeliner\Integration\GraphQl;

use App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException;
use App\Domain\Pipeliner\Integration\Models\AppointmentTypeEntity;
use GraphQL\Query;
use GraphQL\QueryBuilder\QueryBuilder;

class PipelinerAppointmentTypeIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    /**
     * @return AppointmentTypeEntity[]
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
                        (new QueryBuilder('appointmentType'))
                            ->selectField(
                                (new Query('getAll'))
                                    ->setSelectionSet(static::getAppointmentTypeEntitySelectionSet())
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.appointmentType.getAll');

        return array_map(AppointmentTypeEntity::fromArray(...), $data);
    }

    public static function getAppointmentTypeEntitySelectionSet(): array
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
