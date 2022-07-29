<?php

namespace App\Services\SalesUnit;

use App\Integrations\Pipeliner\Models\SalesUnitEntity;
use App\Models\SalesUnit;

class SalesUnitDataMapper
{
    public function mapSalesUnitFromSalesUnitEntity(SalesUnitEntity $entity): SalesUnit
    {
        return tap(new SalesUnit(), static function (SalesUnit $unit) use ($entity): void {
            $unit->pl_reference = $entity->id;
            $unit->unit_name = $entity->name;
            $unit->entity_order = SalesUnit::query()->max('entity_order') + 1;
        });
    }

    public function mergeAttributesFrom(SalesUnit $unit, SalesUnit $another): void
    {
        $toBeMergedAttributes = [
            'pl_reference',
            'unit_name',
        ];

        foreach ($toBeMergedAttributes as $attr) {
            if (null !== $another->$attr) {
                $unit->$attr = $another->$attr;
            }
        }
    }
}