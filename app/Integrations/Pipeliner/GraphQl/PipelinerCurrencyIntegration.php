<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\CreateCurrencyInput;
use App\Integrations\Pipeliner\Models\CurrencyEntity;
use App\Integrations\Pipeliner\Models\ValidationLevelCollection;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\QueryBuilder\MutationBuilder;
use GraphQL\QueryBuilder\QueryBuilder;

class PipelinerCurrencyIntegration
{
    public function __construct(protected readonly PipelinerGraphQlClient $client)
    {
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function create(CreateCurrencyInput       $input,
                           ValidationLevelCollection $validationLevel = null): CurrencyEntity
    {
        $builder = (new MutationBuilder())
            ->setVariable(name: 'input', type: 'CreateCurrencyInput', isRequired: true)
            ->setVariable(name: 'validationLevel', type: '[ValidationLevel!]')
            ->selectField(
                (new Mutation('createCurrency'))
                    ->setArguments([
                        'input' => '$input',
                        'validationLevel' => '$validationLevel',
                    ])
                    ->setSelectionSet([
                        (new Query('currency'))
                            ->setSelectionSet(static::getCurrencyEntitySelectionSet()),
                    ])
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
                'variables' => [
                    'input' => $input,
                    'validationLevel' => $validationLevel,
                ],
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        return CurrencyEntity::fromArray($response->json('data.createCurrency.currency'));
    }

    /**
     * @return CurrencyEntity[]
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getByIds(string ...$ids): array
    {
        $builder = (new QueryBuilder())
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('currency'))
                            ->selectField(
                                (new Query('getByIds'))
                                    ->setArguments(['entityIds' => $ids])
                                    ->setSelectionSet(static::getCurrencyEntitySelectionSet())
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.currency.getByIds');

        return array_map(CurrencyEntity::fromArray(...), $data);
    }

    /**
     * @return CurrencyEntity[]
     * @throws \Illuminate\Http\Client\RequestException
     * @throws GraphQlRequestException
     */
    public function getAll(): array
    {
        $builder = (new QueryBuilder())
            ->selectField(
                (new QueryBuilder('entities'))
                    ->selectField(
                        (new QueryBuilder('currency'))
                            ->selectField(
                                (new Query('getAll'))
                                    ->setSelectionSet(static::getCurrencyEntitySelectionSet())
                            )
                    )
            );

        $response = $this->client
            ->post($this->client->buildSpaceEndpoint(), [
                'query' => $builder->getQuery()->__toString(),
            ]);

        GraphQlRequestException::throwIfHasErrors($response);

        $response->throw();

        $data = $response->json('data.entities.currency.getAll');

        return array_map(CurrencyEntity::fromArray(...), $data);
    }

    public static function getCurrencyEntitySelectionSet(): array
    {
        return [
            'id', 'code', 'isBase',
        ];
    }
}