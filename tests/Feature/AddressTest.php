<?php

namespace Tests\Feature;

use App\Domain\Address\Models\Address;
use App\Domain\Authorization\Models\Role;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Country\Models\Country;
use App\Domain\User\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class AddressTest extends TestCase
{
    /**
     * Test an ability to view a list of available addresses.
     *
     * @return void
     */
    public function testCanViewListOfAddresses()
    {
        $this->authenticateApi();

        $this->getJson('api/addresses')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'location_id', 'address_type', 'address_1', 'city', 'state', 'state_code', 'post_code',
                        'address_2', 'country_id', 'contact_name', 'contact_number', 'contact_email', 'created_at',
                        'updated_at', 'activated_at',
                        'country' => [
//                            'id', 'iso_3166_2', 'name', 'default_currency_id', 'user_id', 'is_system', 'currency_code', 'currency_symbol', 'currency_code', 'flag', 'created_at', 'updated_at', 'deleted_at', 'activated_at',
                        ],
                    ],
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links' => [
                    '*' => [
                        'url', 'label', 'active',
                    ],
                ],
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);

        $orderFields = [
            'created_at',
            'country',
            'address_type',
            'city',
            'post_code',
            'state',
            'street_address',
        ];

        foreach ($orderFields as $field) {
            $this->getJson('api/addresses?order_by_'.$field.'=desc')->assertOk();
            $this->getJson('api/addresses?order_by_'.$field.'=asc')->assertOk();
        }
    }

    /**
     * Test an ability to view only owned addresses
     * when user doesn't have super permissions.
     *
     * @return void
     */
    public function testCanViewOnlyOwnedAddressesWithoutSuperPermissions()
    {
        /** @var \App\Domain\Authorization\Models\Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions([
            'view_addresses',
            'update_addresses',
            'delete_addresses',
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $user->syncRoles($role);

        $this->authenticateApi($user);

        $response = $this->getJson('api/addresses')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'location_id', 'address_type', 'address_1', 'city', 'state', 'state_code', 'post_code',
                        'address_2', 'country_id', 'contact_name', 'contact_number', 'contact_email', 'created_at',
                        'updated_at', 'activated_at',
                        'country' => [
//                            'id', 'iso_3166_2', 'name', 'default_currency_id', 'user_id', 'is_system', 'currency_code', 'currency_symbol', 'currency_code', 'flag', 'created_at', 'updated_at', 'deleted_at', 'activated_at',
                        ],
                    ],
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links' => [
                    '*' => [
                        'url', 'label', 'active',
                    ],
                ],
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);

        $this->assertEmpty($response->json('data'));

        factory(Address::class)->create(['user_id' => $user->getKey()]);

        $response = $this->getJson('api/addresses')
//            ->dump()
            ->assertOk();

        $this->assertSame($user->getKey(), $response->json('data.0.user_id'));
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test an ability to create a new address.
     */
    public function testCanCreateNewAddress(): string
    {
        $this->authenticateApi();

        $data = [
            'address_type' => 'Equipment',
            'address_1' => Str::random(40),
            'address_2' => Str::random(40),
            'city' => Str::random(20),
            'post_code' => Str::random(10),
            'state' => Str::random(10),
            'state_code' => Str::random(10),
            'country_id' => Country::query()->where('iso_3166_2', 'GB')->value('id'),
            'contact_id' => Contact::factory()->create()->getKey(),
        ];

        $response = $this->postJson('api/addresses', $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'contact_id',
                'address_type',
                'address_1',
                'city',
                'state',
                'state_code',
                'post_code',
                'address_2',
                'country_id',
                'created_at',
                'updated_at',
                'activated_at',
                'country' => [
                    'id', 'iso_3166_2', 'name',
                ],
            ]);

        foreach ($data as $attribute => $value) {
            $this->assertSame($response->json($attribute), $value);
        }

        $this->assertSame($response->json('country.id'), $data['country_id']);

        return $response->json('id');
    }

    /**
     * Test an ability to create a new address with company relations.
     */
    public function testCanCreateNewAddressWithCompanyRelations(): string
    {
        $this->authenticateApi();

        $data = [
            'address_type' => 'Equipment',
            'address_1' => Str::random(40),
            'address_2' => Str::random(40),
            'city' => Str::random(20),
            'post_code' => Str::random(10),
            'state' => Str::random(10),
            'state_code' => Str::random(10),
            'country_id' => Country::query()->where('iso_3166_2', 'GB')->value('id'),
            'contact_id' => Contact::factory()->create()->getKey(),
            'company_relations' => Company::factory()->count(2)->create()->map->only('id')->all(),
        ];

        $response = $this->postJson('api/addresses', $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'contact_id',
                'address_type',
                'address_1',
                'city',
                'state',
                'state_code',
                'post_code',
                'address_2',
                'country_id',
                'created_at',
                'updated_at',
                'activated_at',
                'country' => [
                    'id', 'iso_3166_2', 'name',
                ],
            ]);

        foreach (Arr::except($data, 'company_relations') as $attribute => $value) {
            $this->assertSame($response->json($attribute), $value);
        }

        $this->assertSame($response->json('country.id'), $data['country_id']);

        foreach ($data['company_relations'] as $relation) {
            $r = $this->getJson('api/companies/'.$relation['id'])
//                ->dump()
                ->assertOk()
                ->assertJsonStructure([
                    'id',
                    'addresses' => [
                        '*' => [
                            'id',
                        ],
                    ],
                ]);

            $this->assertContains($response->json('id'), $r->json('addresses.*.id'));
        }

        return $response->json('id');
    }

    /**
     * Test an ability to view an existing address.
     *
     * @return void
     */
    public function testCanViewAddress()
    {
        $addressID = $this->testCanCreateNewAddress();

        $this->authenticateApi();

        $this->getJson('api/addresses/'.$addressID)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'location_id',
                'contact_id',
                'address_type',
                'address_1',
                'city',
                'state',
                'state_code',
                'post_code',
                'address_2',
                'country_id',
                'created_at',
                'updated_at',
                'activated_at',
                'country' => [
                    'id', 'iso_3166_2', 'name',
                ],
            ]);
    }

    /**
     * Test an ability to update an existing address.
     *
     * @return void
     */
    public function testCanUpdateAddress()
    {
        $addressID = $this->testCanCreateNewAddress();

        $this->authenticateApi();

        $data = [
            'address_type' => 'Software',
            'address_1' => Str::random(40),
            'address_2' => Str::random(40),
            'city' => Str::random(20),
            'post_code' => Str::random(10),
            'state' => Str::random(10),
            'state_code' => Str::random(10),
            'country_id' => Country::query()->where('iso_3166_2', 'GB')->value('id'),
            'contact_id' => Contact::factory()->create()->getKey(),
        ];

        $this->patchJson('api/addresses/'.$addressID, $data)
            ->assertOk();

        $response = $this->getJson('api/addresses/'.$addressID)
            ->assertOk();

        foreach ($data as $attribute => $value) {
            $this->assertSame($response->json($attribute), $value);
        }

        $this->assertSame($response->json('country.id'), $data['country_id']);
    }

    /**
     * Test an ability to update an existing address.
     */
    public function testCanUpdateAddressWithCompanyRelations(): void
    {
        $addressID = $this->testCanCreateNewAddress();

        $this->authenticateApi();

        $this->travelTo(now()->subMinute());
        $company = Company::factory()->create();
        $this->travelBack();

        $companyUpdatedAt = $this->getJson('api/companies/'.$company->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'updated_at',
            ])
            ->json('updated_at');

        $data = [
            'address_type' => 'Software',
            'address_1' => Str::random(40),
            'address_2' => Str::random(40),
            'city' => Str::random(20),
            'post_code' => Str::random(10),
            'state' => Str::random(10),
            'state_code' => Str::random(10),
            'country_id' => Country::query()->where('iso_3166_2', 'GB')->value('id'),
            'contact_id' => Contact::factory()->create()->getKey(),
            'company_relations' => [['id' => $company->getKey()]],
        ];

        $this->patchJson('api/addresses/'.$addressID, $data)
            ->assertOk();

        $response = $this->getJson('api/addresses/'.$addressID)
            ->assertOk();

        foreach (Arr::except($data, 'company_relations') as $attribute => $value) {
            $this->assertSame($response->json($attribute), $value);
        }

        foreach ($data['company_relations'] as $relation) {
            $r = $this->getJson('api/companies/'.$relation['id'])
//                ->dump()
                ->assertOk()
                ->assertJsonStructure([
                    'id',
                    'addresses' => [
                        '*' => [
                            'id',
                        ],
                    ],
                ]);

            $this->assertContains($response->json('id'), $r->json('addresses.*.id'));
        }

        $this->assertSame($response->json('country.id'), $data['country_id']);

        $r = $this->getJson('api/companies/'.$company->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'updated_at',
            ]);

        $this->assertNotSame($companyUpdatedAt, $r->json('updated_at'));
    }

    /**
     * Test an ability to delete an existing address.
     *
     * @return void
     */
    public function testCanDeleteAddress()
    {
        $addressID = $this->testCanCreateNewAddress();

        $this->authenticateApi();

        $this->deleteJson('api/addresses/'.$addressID)
            ->assertNoContent();

        $this->getJson('api/addresses/'.$addressID)
            ->assertNotFound();
    }

    /**
     * Test an ability to mark an existing address as active.
     *
     * @return void
     */
    public function testCanMarkAddressAsActive()
    {
        $addressID = $this->testCanCreateNewAddress();

        $this->authenticateApi();

        $this->putJson('api/addresses/deactivate/'.$addressID)
            ->assertNoContent();

        $response = $this->getJson('api/addresses/'.$addressID)
            ->assertOk();

        $this->assertEmpty($response->json('activated_at'));

        $this->putJson('api/addresses/activate/'.$addressID)
            ->assertNoContent();

        $response = $this->getJson('api/addresses/'.$addressID)
            ->assertOk();

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to mark an existing address as inactive.
     *
     * @return void
     */
    public function testCanMarkAddressAsInactive()
    {
        $addressID = $this->testCanCreateNewAddress();

        $this->authenticateApi();

        $response = $this->getJson('api/addresses/'.$addressID)
            ->assertOk();

        $this->assertNotEmpty($response->json('activated_at'));

        $this->putJson('api/addresses/deactivate/'.$addressID)
            ->assertNoContent();

        $response = $this->getJson('api/addresses/'.$addressID)
            ->assertOk();

        $this->assertEmpty($response->json('activated_at'));
    }
}
