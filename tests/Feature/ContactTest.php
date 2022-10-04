<?php

namespace Tests\Feature;

use App\Enum\GenderEnum;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Role;
use App\Models\SalesUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * Test an ability to view a list of available contacts.
     */
    public function testCanViewListOfContacts(): void
    {
        $this->authenticateApi();

        Contact::factory()->create();

        $this->getJson('api/contacts')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'contact_type', 'gender', 'phone', 'first_name', 'last_name', 'mobile', 'job_title',
                        'image_id', 'is_verified', 'email', 'created_at', 'activated_at',
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

        $orderFields = ['created_at', 'email', 'first_name', 'last_name', 'is_verified', 'job_title', 'mobile',
            'phone'];

        foreach ($orderFields as $field) {
            $this->getJson('api/contacts?order_by_'.$field.'=desc')->assertOk();
            $this->getJson('api/contacts?order_by_'.$field.'=asc')->assertOk();
        }
    }

    /**
     * Test an ability to view only owned contacts
     * when user doesn't have super permissions.
     */
    public function testCanViewOnlyOwnedContactsWithoutSuperPermissions(): void
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions([
            'view_contacts',
            'update_contacts',
            'delete_contacts',
        ]);

        /** @var User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        $this->authenticateApi($user);

        $response = $this->getJson('api/contacts')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'contact_type', 'gender', 'phone', 'first_name', 'last_name', 'mobile', 'job_title',
                        'image_id', 'is_verified', 'email', 'created_at', 'activated_at',
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

        Contact::factory()
            ->for($user->salesUnits->first())
            ->create(['user_id' => $user->getKey()]);

        $response = $this->getJson('api/contacts')
            ->assertOk();

        $this->assertSame($user->getKey(), $response->json('data.0.user_id'));
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test an ability to create a new contact.
     *
     * @return string
     */
    public function testCanCreateContact(): string
    {
        $this->authenticateApi();

        $performContactCreation = function (string $contactType, string $firstName, string $lastName): string {
            $image = UploadedFile::fake()->image('contact.jpg');

            $response = $this->postJson('api/contacts', [
                'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
                'contact_type' => $contactType,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'picture' => $image,
            ])
//                ->dump()
                ->assertOk()
                ->assertJsonStructure([
                    'id',
                    'sales_unit_id',
                    'contact_type',
                    'first_name',
                    'last_name',
                    'is_verified',
                    'picture',
                    'image',

                    'created_at',
                ])
                ->assertJson([
                    'contact_type' => $contactType,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ])
                ->assertOk();

            $contactModelKey = $response->json('id');

            $this->assertNotEmpty($contactModelKey);

            $this->getJson('api/contacts/'.$contactModelKey)
//            ->dump()
                ->assertJsonStructure([
                    'id',
                    'sales_unit_id',
                    'contact_type',
                    'gender',
                    'first_name',
                    'last_name',
                    'is_verified',
                    'picture',
                    'image',
                    'created_at',
                ])
                ->assertJson([
                    'contact_type' => $contactType,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ]);

            return $contactModelKey;
        };

        $contactIDs = [];

        foreach (['Hardware', 'Software', 'Invoice'] as $contactType) {
            $contactIDs[] = $performContactCreation(
                $contactType,
                $this->faker->firstName,
                $this->faker->lastName
            );
        }

        return $contactIDs[0];
    }

    /**
     * Test an ability to create a new contact associated with address.
     *
     * @return string
     */
    public function testCanCreateContactAssociatedWithAddress(): string
    {
        $this->authenticateApi();

        $image = UploadedFile::fake()->image('contact.jpg');

        $response = $this->postJson('api/contacts', $data = [
            'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
            'address_id' => Address::factory()->create()->getKey(),
            'contact_type' => $this->faker->randomElement(['Invoice', 'Hardware', 'Software']),
            'gender' => $this->faker->randomElement(GenderEnum::cases())->value,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'picture' => $image,
        ])
//                ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'sales_unit_id',
                'address_id',
                'contact_type',
                'gender',
                'first_name',
                'last_name',
                'is_verified',
                'picture',
                'image',
                'created_at',
            ])
            ->assertJson([
                'sales_unit_id' => $data['sales_unit_id'],
                'address_id' => $data['address_id'],
                'contact_type' => $data['contact_type'],
                'gender' => $data['gender'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
            ])
            ->assertOk();

        $contactModelKey = $response->json('id');

        $this->assertNotEmpty($contactModelKey);

        $this->getJson('api/contacts/'.$contactModelKey)
//            ->dump()
            ->assertJsonStructure([
                'id',
                'sales_unit_id',
                'address_id',
                'contact_type',
                'first_name',
                'last_name',
                'is_verified',
                'picture',
                'image',
                'created_at',
            ])
            ->assertJson([
                'sales_unit_id' => $data['sales_unit_id'],
                'address_id' => $data['address_id'],
                'contact_type' => $data['contact_type'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
            ]);

        return $contactModelKey;
    }

    /**
     * Test an ability to update an existing contact.
     */
    public function testCanUpdateContact(): void
    {
        $this->authenticateApi();

        $contact = Contact::factory()->create();

        $performContactUpdate = function (string $contactKey,
                                          string $gender,
                                          string $contactType,
                                          string $firstName,
                                          string $lastName) {
            $image = UploadedFile::fake()->image('contact.jpg');

            $response = $this->patchJson('api/contacts/'.$contactKey, [
                'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
                'contact_type' => $contactType,
                'gender' => $gender,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'picture' => $image,
            ])
//            ->dump()
                ->assertJsonStructure([
                    'id',
                    'sales_unit_id',
                    'contact_type',
                    'first_name',
                    'last_name',
                    'is_verified',
                    'picture',
                    'image',
                    'created_at',
                ])
                ->assertJson([
                    'contact_type' => $contactType,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ])
                ->assertOk();

            $this->getJson('api/contacts/'.$contactKey)
//            ->dump()
                ->assertJsonStructure([
                    'id',
                    'sales_unit_id',
                    'contact_type',
                    'gender',
                    'first_name',
                    'last_name',
                    'is_verified',
                    'picture',
                    'image',
                    'created_at',
                ])
                ->assertJson([
                    'contact_type' => $contactType,
                    'gender' => $gender,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ]);
        };

        foreach (['Hardware', 'Software', 'Invoice'] as $contactType) {
            $performContactUpdate(
                $contact->getKey(),
                $this->faker->randomElement(GenderEnum::cases())->value,
                $contactType,
                $this->faker->firstName,
                $this->faker->lastName
            );
        }
    }

    /**
     * Test an ability to update an existing contact associated with address
     */
    public function testCanUpdateContactAssociatedWithAddress(): void
    {
        $this->authenticateApi();

        $image = UploadedFile::fake()->image('contact.jpg');

        $contact = Contact::factory()
            ->for(Address::factory())
            ->create();

        $response = $this->patchJson('api/contacts/'.$contact->getKey(), $data = [
            'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
            'contact_type' => $this->faker->randomElement(['Invoice', 'Hardware', 'Software']),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'picture' => $image,
        ])
//                ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'sales_unit_id',
                'address_id',
                'contact_type',
                'first_name',
                'last_name',
                'is_verified',
                'picture',
                'image',
                'created_at',
            ])
            ->assertJson([
                'sales_unit_id' => $data['sales_unit_id'],
                'contact_type' => $data['contact_type'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
            ]);

        $contactModelKey = $response->json('id');

        $this->assertNotEmpty($contactModelKey);

        $this->getJson('api/contacts/'.$contactModelKey)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'sales_unit_id',
                'contact_type',
                'first_name',
                'last_name',
                'is_verified',
                'picture',
                'image',
                'created_at',
            ])
            ->assertJson([
                'sales_unit_id' => $data['sales_unit_id'],
                'contact_type' => $data['contact_type'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
            ]);
    }

    /**
     * Test an ability to associate an existing contact with the address.
     */
    public function testCanAssociateContactWithAddress(): void
    {
        $this->authenticateApi();

        $contact = Contact::factory()->create();

        $this->getJson("api/contacts/{$contact->getKey()}")
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'address_id'
            ])
            ->assertJsonPath('address_id', null);

        $address = Address::factory()->create();

        $this->putJson("api/contacts/{$contact->getKey()}/address/{$address->getKey()}")
            ->assertOk();

        $this->getJson("api/contacts/{$contact->getKey()}")
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'address_id'
            ])
            ->assertJsonPath('address_id', $address->getKey());
    }

    /**
     * Test an ability to mark an existing contact as active.
     */
    public function testCanMarkContactAsActive(): void
    {
        $contactID = $this->testCanCreateContact();

        $this->authenticateApi();

        $this->putJson('api/contacts/deactivate/'.$contactID)
            ->assertNoContent();

        $response = $this->getJson('api/contacts/'.$contactID)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->putJson('api/contacts/activate/'.$contactID)
            ->assertNoContent();

        $response = $this->getJson('api/contacts/'.$contactID)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to mark an existing contact as inactive.
     */
    public function testCanMarkContactAsInactive(): void
    {
        $contactID = $this->testCanCreateContact();

        $this->authenticateApi();

        $response = $this->getJson('api/contacts/'.$contactID)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));

        $this->putJson('api/contacts/deactivate/'.$contactID)
            ->assertNoContent();

        $response = $this->getJson('api/contacts/'.$contactID)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));
    }
}
