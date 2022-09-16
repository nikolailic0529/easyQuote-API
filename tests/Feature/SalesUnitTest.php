<?php

namespace Tests\Feature;

use App\Models\PipelinerModelScrollCursor;
use App\Models\Role;
use App\Models\SalesUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
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
     * Test an ability to filter assigned sales units to current user.
     */
    public function testCanFilterAssignedSalesUnits(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory(2), relationship: 'salesUnits')
            ->create();
        $user->syncRoles(factory(Role::class)->create());

        $this->authenticateApi($user);

        $response = $this->get('api/sales-units/list?'.Arr::query([
                'filter' => ['assigned_to_me' => "true"],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'unit_name', 'is_default', 'is_enabled', 'created_at', 'updated_at'],
                ],
            ]);

        $this->assertCount($user->salesUnits->count(), $response->json('data'));

        foreach ($user->salesUnits as $unit) {
            $this->assertContainsEquals($unit->getKey(), $response->json('data.*.id'));
        }
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

    public function testItFlushesPipelinerScrollCursorsOnUpdateOfEnabledSalesUnits(): void
    {
        $con = $this->app['db.connection'];
        $con->table('sales_units')->delete();

        PipelinerModelScrollCursor::factory()->create();

        $units = SalesUnit::factory()
            ->count(2)
            ->sequence(
                ['is_enabled' => true, 'is_default' => true],
                ['is_enabled' => false, 'is_default' => false],
            )
            ->create();

        $data = SalesUnit::factory()
            ->count(2)
            ->sequence(
                ['id' => $units[0]->getKey(), 'is_enabled' => false, 'is_default' => false],
                ['id' => $units[1]->getKey(), 'is_enabled' => true, 'is_default' => true],
            )
            ->raw();

        $this->authenticateApi();

        $this->putJson('api/sales-units', ['sales_units' => $data])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'unit_name', 'is_default', 'is_enabled', 'created_at', 'updated_at'],
                ],
            ]);

        $this->assertDatabaseCount('pipeliner_model_scroll_cursors', 0);
    }

    /**
     * @depends testCanViewListOfSalesUnits
     */
    public function testCanSetExactlyOneSalesUnitAsDefault(array $response): void
    {
        $this->authenticateApi();

        $data = collect($response)
            ->take(2)
            ->map(static fn($v) => [...$v, ...['is_default' => true]])
            ->all();

        $assertResponseInvalid = static function (TestResponse $response): void {
            $response->assertUnprocessable()
                ->assertInvalid(['sales_units' => 'Exactly 1 unit must be set as default.'], responseKey: 'Error.original');
       };

        $assertResponseInvalid($this->putJson('api/sales-units', ['sales_units' => $data]));

        $data = collect($response)
            ->take(2)
            ->map(static fn($v) => [...$v, ...['is_default' => false]])
            ->all();

        $assertResponseInvalid($this->putJson('api/sales-units', ['sales_units' => $data]));

        $data = collect($response)
            ->take(2)
            ->map(static fn($v) => [...$v, ...['is_default' => null]])
            ->all();

        $assertResponseInvalid($this->putJson('api/sales-units', ['sales_units' => $data]));
    }
}
