<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Pipeliner\Integration\Enum\ValidationLevel;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAccountIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerGraphQlClient;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerOpportunityIntegration;
use App\Domain\Pipeliner\Integration\Models\AccountEntity;
use App\Domain\Pipeliner\Integration\Models\OpportunityEntity;
use App\Domain\Pipeliner\Integration\Models\UpdateAccountInput;
use App\Domain\Pipeliner\Integration\Models\UpdateAccountInputCollection;
use App\Domain\Pipeliner\Integration\Models\UpdateOpportunityInput;
use App\Domain\Pipeliner\Integration\Models\UpdateOpportunityInputCollection;
use App\Domain\Pipeliner\Integration\Models\ValidationLevelCollection;
use Illuminate\Support\Collection;

class PipelinerTouchEntityService
{
    public function __construct(protected PipelinerGraphQlClient $client,
                                protected PipelinerOpportunityIntegration $oppIntegration,
                                protected PipelinerAccountIntegration $accIntegration)
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
                    new UpdateOpportunityInput(id: $entity->id, name: $entity->name),
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
                    new UpdateAccountInput(id: $entity->id, name: $entity->name),
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
