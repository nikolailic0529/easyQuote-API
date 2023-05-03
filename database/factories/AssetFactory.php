<?php

namespace Database\Factories;

use App\Domain\Address\Models\Address;
use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\AssetCategory;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        /** @var Vendor $vendor */
        $vendor = Vendor::query()->firstOrFail();
        /** @var AssetCategory $category */
        $category = AssetCategory::query()->firstOrFail();

        return [
            'serial_number' => $this->faker->regexify('[A-Z0-9]{6}'),
            'product_number' => $this->faker->regexify('\d{4}[A-Z]{4}'),
            'product_description' => $this->faker->text(100),
            'unit_price' => (float) mt_rand(10, 10000),
            'vendor_id' => $vendor->getKey(),
            'asset_category_id' => $category->getKey(),
            'address_id' => Address::factory(),
            'vendor_short_code' => $vendor->short_code,
            'base_warranty_start_date' => now()->format('Y-m-d'),
            'base_warranty_end_date' => now()->addYears(mt_rand(1, 10))->format('Y-m-d'),
            'active_warranty_start_date' => now()->format('Y-m-d'),
            'active_warranty_end_date' => now()->addYears(mt_rand(1, 10))->format('Y-m-d'),
        ];
    }
}
