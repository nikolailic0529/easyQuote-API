<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Note\Note;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyNoteTest extends TestCase
{
    use DatabaseTransactions;

    public function testCanViewNotesOfCompany(): void
    {
        $company = Company::factory()
            ->hasAttached(Note::factory(2), relationship: 'notes')
            ->create();

        $this->authenticateApi();

        $this->getJson('api/companies/'.$company->getKey().'/notes')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'note_entity_type',
                        'note_entity_class',
                        'parent_entity_type',
                        'quote_id',
                        'customer_id',
                        'quote_number',
                        'text',
                        'owner_user_id',
                        'owner_fullname',
                        'permissions' => [
                            'update',
                            'delete'
                        ],
                        'created_at',
                    ]
                ]
            ]);
    }

    /**
     * Test an ability to create a new company note.
     *
     * @return void
     */
    public function testCanCreateCompanyNote(): void
    {
        $company = Company::factory()->create();

        $this->authenticateApi();

        $this->postJson('api/companies/'.$company->getKey().'/company-notes', [
            'text' => Str::random(2000),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'user_id',
                'note',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * Test an ability to update an existing company note.
     *
     * @return void
     */
    public function testCanUpdateCompanyNote(): void
    {
        $company = Company::factory()->create();

        $this->authenticateApi();

        $response = $this->postJson('api/companies/'.$company->getKey().'/company-notes', [
            'text' => Str::random(2000),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'user_id',
                'note',
                'created_at',
                'updated_at',
            ]);

        $this->patchJson('api/companies/company-notes/'.$response->json('id'), [
            'text' => $newNoteText = Str::random(2000),
        ])
            ->assertOk()
            ->assertJsonStructure([
                'id',
//                'user_id',
                'note',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'note' => $newNoteText,
            ]);

        $this->getJson('api/companies/company-notes/'.$response->json('id'))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'note',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'note' => $newNoteText,
            ]);
    }

    /**
     * Test an ability to delete an existing company note.
     *
     * @return void
     */
    public function testCanDeleteCompanyNote(): void
    {
        $company = Company::factory()->create();

        $this->authenticateApi();

        $response = $this->postJson('api/companies/'.$company->getKey().'/company-notes', [
            'text' => Str::random(2000),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'user_id',
                'note',
                'created_at',
                'updated_at',
            ]);

        $this->getJson('api/companies/company-notes/'.$response->json('id'))
            ->assertOk();

        $this->deleteJson('api/companies/company-notes/'.$response->json('id'))
            ->assertNoContent();

        $this->getJson('api/companies/company-notes/'.$response->json('id'))
            ->assertNotFound();
    }
}
