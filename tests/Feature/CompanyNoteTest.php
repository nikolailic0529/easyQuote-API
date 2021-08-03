<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyNoteTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to create a new company note.
     *
     * @return void
     */
    public function testCanCreateCompanyNote()
    {
        $company = factory(Company::class)->create();

        $this->authenticateApi();

        $this->postJson('api/companies/'.$company->getKey().'/company-notes', [
            'text' => Str::random(2000),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'user_id',
                'company_id',
                'text',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * Test an ability to update an existing company note.
     *
     * @return void
     */
    public function testCanUpdateCompanyNote()
    {
        $company = factory(Company::class)->create();

        $this->authenticateApi();

        $response = $this->postJson('api/companies/'.$company->getKey().'/company-notes', [
            'text' => Str::random(2000),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'user_id',
                'company_id',
                'text',
                'created_at',
                'updated_at',
            ]);

        $this->patchJson('api/companies/company-notes/'.$response->json('id'), [
            'text' => $newNoteText = Str::random(2000),
        ])
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'company_id',
                'text',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'text' => $newNoteText,
            ]);

        $this->getJson('api/companies/company-notes/'.$response->json('id'))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'company_id',
                'text',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'text' => $newNoteText,
            ]);
    }

    /**
     * Test an ability to delete an existing company note.
     *
     * @return void
     */
    public function testCanDeleteCompanyNote()
    {
        $company = factory(Company::class)->create();

        $this->authenticateApi();

        $response = $this->postJson('api/companies/'.$company->getKey().'/company-notes', [
            'text' => Str::random(2000),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'user_id',
                'company_id',
                'text',
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
