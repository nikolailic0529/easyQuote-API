<?php

namespace Tests\Feature;

use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * Test an ability to view a list of available contacts.
     *
     * @return void
     */
    public function testCanViewListOfContacts()
    {
        $this->authenticateApi();

        factory(Contact::class)->create();

        $this->getJson('api/contacts')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'contact_type', 'phone', 'first_name', 'last_name', 'mobile', 'job_title', 'image_id', 'is_verified', 'email', 'created_at', 'activated_at',
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

        $orderFields = ['created_at', 'email', 'first_name', 'last_name', 'is_verified', 'job_title', 'mobile', 'phone'];

        foreach ($orderFields as $field) {
            $this->getJson('api/contacts?order_by_'.$field.'=desc')->assertOk();
            $this->getJson('api/contacts?order_by_'.$field.'=asc')->assertOk();
        }
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
                'contact_type' => $contactType,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'picture' => $image,
            ])
//                ->dump()
                ->assertOk()
                ->assertJsonStructure([
                    'id',
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
     * Test an ability to update an existing contact.
     *
     * @return void
     */
    public function testCanUpdateContact()
    {
        $this->authenticateApi();

        $contact = factory(Contact::class)->create();

        $performContactUpdate = function (string $contactKey, string $contactType, string $firstName, string $lastName) {
            $image = UploadedFile::fake()->image('contact.jpg');

            $response = $this->patchJson('api/contacts/'.$contactKey, [
                'contact_type' => $contactType,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'picture' => $image,
            ])
//            ->dump()
                ->assertJsonStructure([
                    'id',
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
                ]);
        };

        foreach (['Hardware', 'Software', 'Invoice'] as $contactType) {
            $performContactUpdate(
                $contact->getKey(),
                $contactType,
                $this->faker->firstName,
                $this->faker->lastName
            );
        }
    }

    /**
     * Test an ability to mark an existing contact as active.
     *
     * @return void
     */
    public function testCanMarkContactAsActive()
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
     * Test an ability to mark an existing contact as active.
     *
     * @return void
     */
    public function testCanMarkContactAsInactive()
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
