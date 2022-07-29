<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Tests\TestCase;

class SalesUnitTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * Test can view list of available sales units.
     */
    public function testCanViewListOfSalesUnits(): array
    {
        $this->authenticateApi();

        $response = $this->get('api/sales-units/list')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'unit_name', 'is_default', 'is_enabled', 'created_at', 'updated_at'],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));

        return $response->json('data');
    }

    /**
     * Test an ability to bulk create or update sales units.
     * @depends testCanViewListOfSalesUnits
     */
    public function testCanBulkCreateOrUpdateSalesUnits(array $response): void
    {
        $this->authenticateApi();

        $defaultWasSet = false;

        $data = collect()->times(2, function () use (&$defaultWasSet) {
            $isDefault = !$defaultWasSet && $this->faker->boolean(99);
            $defaultWasSet = $defaultWasSet || $isDefault;

            return [
                'id' => null,
                'unit_name' => $this->faker->text(100),
                'is_default' => $isDefault,
                'is_enabled' => $this->faker->boolean(),
            ];
        })
            ->push(
                ...collect($response)->random(2)
                ->map(static function (array $item): array {
                    $item = Arr::only($item, ['id', 'unit_name', 'is_default', 'is_enabled']);
                    $item['is_default'] = false;

                    return $item;
                })
            );

        $this->putJson('api/sales-units', ['sales_units' => $data->all()])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'unit_name', 'is_default', 'is_enabled', 'created_at', 'updated_at'],
                ],
            ]);

        $response = $this->getJson('api/sales-units/list')
            ->assertOk();

        $this->assertCount($data->count(), $response->json('data'));

        $comparableAttributes = ['id', 'unit_name', 'is_default', 'is_enabled'];

        foreach ($data as $key => $item) {
            $itemFromResponse = $response->json("data.$key");

            foreach ($comparableAttributes as $attr) {
                if ('id' === $attr && null === $item[$attr]) {
                    continue;
                }

                $this->assertSame($item[$attr], $itemFromResponse[$attr], $attr);
            }
        }

    }
}
