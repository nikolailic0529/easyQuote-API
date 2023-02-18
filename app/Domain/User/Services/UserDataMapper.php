<?php

namespace App\Domain\User\Services;

use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerMasterRightIntegration;
use App\Domain\Pipeliner\Integration\Models\CreateClientInput;
use App\Domain\Pipeliner\Integration\Models\CreateSalesUnitClientRelationInput;
use App\Domain\Pipeliner\Integration\Models\CreateSalesUnitClientRelationInputCollection;
use App\Domain\Pipeliner\Integration\Models\MasterRight;
use App\Domain\Pipeliner\Services\CachedSalesUnitResolver;
use App\Domain\User\Models\User;

class UserDataMapper
{
    public function __construct(protected PipelinerMasterRightIntegration $masterRightIntegration,
                                protected CachedSalesUnitResolver $salesUnitResolver)
    {
    }

    public function mapPipelinerCreateClientInput(User $user): CreateClientInput
    {
        /** @var MasterRight|null $masterRight */
        $masterRight = collect($this->masterRightIntegration->getAll())->keyBy('name')['Standard user'];

        $salesUnit = ($this->salesUnitResolver)('Worldwide');

        if (null === $salesUnit) {
            throw new \RuntimeException("Unable to resolve sales unit: 'Worldwide'.");
        }

        return new CreateClientInput(
            email: (string) $user->email,
            masterRightId: $masterRight->id,
            defaultUnitId: $salesUnit->id,
            unitMembership: new CreateSalesUnitClientRelationInputCollection(
                new CreateSalesUnitClientRelationInput(unitId: $salesUnit->id)
            ),
            firstName: $user->first_name ?? InputValueEnum::Miss,
            lastName: $user->last_name ?? InputValueEnum::Miss,
        );
    }
}
