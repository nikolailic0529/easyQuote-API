<?php

namespace App\Services\User;

use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\GraphQl\PipelinerMasterRightIntegration;
use App\Integrations\Pipeliner\Models\CreateClientInput;
use App\Integrations\Pipeliner\Models\CreateSalesUnitClientRelationInput;
use App\Integrations\Pipeliner\Models\CreateSalesUnitClientRelationInputCollection;
use App\Integrations\Pipeliner\Models\MasterRight;
use App\Models\User;
use App\Services\Pipeliner\RuntimeCachedSalesUnitResolver;

class UserDataMapper
{
    public function __construct(protected PipelinerMasterRightIntegration $masterRightIntegration,
                                protected RuntimeCachedSalesUnitResolver  $salesUnitResolver)
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
            email: (string)$user->email,
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