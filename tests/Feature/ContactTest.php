<?php

namespace Tests\Feature;

use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * Test an ability to create a new contact.
     *
     * @return void
     */
    public function testCanCreateContact()
    {
        $this->authenticateApi();

        $performContactCreation = function (string $contactType, string $firstName, string $lastName) {
            $response = $this->postJson('api/contacts', [
                'contact_type' => $contactType,
                'first_name' => $firstName,
                'last_name' => $lastName
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
                    'created_at'
                ])
                ->assertJson([
                    'contact_type' => $contactType,
                    'first_name' => $firstName,
                    'last_name' => $lastName
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
                    'created_at'
                ])
                ->assertJson([
                    'contact_type' => $contactType,
                    'first_name' => $firstName,
                    'last_name' => $lastName
                ]);
        };

        foreach (['Hardware', 'Software', 'Invoice'] as $contactType) {
            $performContactCreation(
                $contactType,
                $this->faker->firstName,
                $this->faker->lastName
            );
        }
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
            $response = $this->patchJson('api/contacts/'.$contactKey, [
                'contact_type' => $contactType,
                'first_name' => $firstName,
                'last_name' => $lastName
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
                    'created_at'
                ])
                ->assertJson([
                    'contact_type' => $contactType,
                    'first_name' => $firstName,
                    'last_name' => $lastName
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
                    'created_at'
                ])
                ->assertJson([
                    'contact_type' => $contactType,
                    'first_name' => $firstName,
                    'last_name' => $lastName
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
}
