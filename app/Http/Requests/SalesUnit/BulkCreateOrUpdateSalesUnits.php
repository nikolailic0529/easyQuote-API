<?php

namespace App\Http\Requests\SalesUnit;

use App\DTO\SalesUnit\CreateOrUpdateSalesUnitData;
use App\DTO\SalesUnit\CreateOrUpdateSalesUnitDataCollection;
use App\Models\SalesUnit;
use App\Rules\Count;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class BulkCreateOrUpdateSalesUnits extends FormRequest
{
    protected ?CreateOrUpdateSalesUnitDataCollection $createOrUpdateSalesUnitDataCollection = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'sales_units' => ['bail', 'present', 'array',
                (new Count())
                    ->where('is_default', true)
                    ->exactly(1)
                    ->setExactMessage("Exactly :limit unit must be set as default.")
            ],
            'sales_units.*.id' => ['bail', 'present', 'nullable', 'uuid',
                Rule::exists(SalesUnit::class, (new SalesUnit())->getKeyName())->withoutTrashed()],
            'sales_units.*.unit_name' => ['bail', 'required', 'string', 'max:100'],
            'sales_units.*.is_default' => ['bail', 'present', 'boolean'],
            'sales_units.*.is_enabled' => ['bail', 'present', 'boolean'],
        ];
    }

    public function getCreateOrUpdateSalesUnitDataCollection(): CreateOrUpdateSalesUnitDataCollection
    {
        return $this->createOrUpdateSalesUnitDataCollection ??= $this->collect('sales_units')
            ->map(static function (array $item): CreateOrUpdateSalesUnitData {
                static $order = 1;

                return new CreateOrUpdateSalesUnitData([
                    'id' => $item['id'],
                    'unit_name' => $item['unit_name'],
                    'is_default' => $item['is_default'],
                    'is_enabled' => $item['is_enabled'],
                    'entity_order' => $order++,
                ]);
            })
            ->pipe(static function (Collection $collection): CreateOrUpdateSalesUnitDataCollection {
                return new CreateOrUpdateSalesUnitDataCollection($collection->all());
            });
    }
}
