<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\Enum\ValidationLevel;
use App\Integrations\Pipeliner\GraphQl\PipelinerAccountIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerGraphQlClient;
use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Integrations\Pipeliner\Models\AccountEntity;
use App\Integrations\Pipeliner\Models\OpportunityEntity;
use App\Integrations\Pipeliner\Models\UpdateAccountInput;
use App\Integrations\Pipeliner\Models\UpdateAccountInputCollection;
use App\Integrations\Pipeliner\Models\UpdateOpportunityInput;
use App\Integrations\Pipeliner\Models\UpdateOpportunityInputCollection;
use App\Integrations\Pipeliner\Models\ValidationLevelCollection;
use Illuminate\Support\Collection;

class PipelinerTouchEntityService
{
    public function __construct(protected PipelinerGraphQlClient          $client,
                                protected PipelinerOpportunityIntegration $oppIntegration,
                                protected PipelinerAccountIntegration     $accIntegration)
    {
    }

    public function touchOpportunityById(string $id, string ...$ids): void
    {
        $ids = [$id, ...$ids];

        $entities = $this->oppIntegration->getByIds(...$ids);

        /** @var UpdateOpportunityInputCollection $inputCollection */
        $inputCollection = collect($entities)
            ->map(static function (OpportunityEntity $entity): array {
                return [
                    new UpdateOpportunityInput(id: $entity->id, name: $entity->name.' '),
                    new UpdateOpportunityInput(id: $entity->id, name: $entity->name)
                ];
            })
            ->collapse()
            ->pipe(static function (Collection $collection): UpdateOpportunityInputCollection {
                return new UpdateOpportunityInputCollection(...$collection->all());
            });

        $this->oppIntegration->bulkUpdate(
            $inputCollection,
            ValidationLevelCollection::from(ValidationLevel::SKIP_ALL),
        );
    }

    public function touchAccountById(string $id, string ...$ids): void
    {
        $ids = [$id, ...$ids];

        $entities = $this->accIntegration->getByIds(...$ids);

        /** @var UpdateAccountInputCollection $inputCollection */
        $inputCollection = collect($entities)
            ->map(static function (AccountEntity $entity): array {
                return [
                    new UpdateAccountInput(id: $entity->id, name: $entity->name.' '),
                    new UpdateAccountInput(id: $entity->id, name: $entity->name)
                ];
            })
            ->collapse()
            ->pipe(static function (Collection $collection): UpdateAccountInputCollection {
                return new UpdateAccountInputCollection(...$collection->all());
            });

        $this->accIntegration->bulkUpdate(
            $inputCollection,
            ValidationLevelCollection::from(ValidationLevel::SKIP_ALL),
        );
    }
}