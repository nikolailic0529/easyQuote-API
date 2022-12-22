<?php

namespace Tests\Feature;

use App\Enum\CompanyType;
use App\Enum\DataAllocationStageEnum;
use App\Models\BusinessDivision;
use App\Models\Company;
use App\Models\DataAllocation\DataAllocation;
use App\Models\DataAllocation\DataAllocationFile;
use App\Models\DataAllocation\DataAllocationRecord;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Opportunity\ImportedOpportunityDataValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @group data-allocation
 * @group build
 */
class DataAllocationTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    public function testCanPaginateDataAllocations(): void
    {
        $this->authenticateApi();

        DataAllocation::factory(2)->create();

        $this->getJson('api/data-allocations/')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'distribution_algorithm',
                        'assignment_start_date',
                        'assignment_end_date',
                        'stage',
                        'stage_value',
                        'permissions' => [
                            'update',
                            'delete'
                        ],
                        'company_name',
                        'division_name',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->getJson('api/data-allocations?'.Arr::query(['search' => 'Support Warehouse']))
            ->assertOk();
    }

    /**
     * Test an ability to filter data allocations by assignment dates.
     */
    public function testCanFilterDataAllocationsByAssignmentDates(): void
    {
        $this->authenticateApi();

        /** @var DataAllocation $allocation */
        $allocation = DataAllocation::factory()->create([
            'assignment_start_date' => now()->addYears(100),
            'assignment_end_date' => now()->addYears(200),
        ]);

        $allocation2 = DataAllocation::factory()->create([
            'assignment_start_date' => now()->addYears(99),
            'assignment_end_date' => now()->addYears(199),
        ]);

        // gte, gt
        $this->getJson('api/data-allocations?'.Arr::query([
                'filter' => [
                    'gt' => [
                        'assignment_start_date' => $allocation->assignment_start_date->subDay()->toDateString(),
                    ],
                ],
            ]))
//        ->dump()
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $allocation->getKey());

        $this->getJson('api/data-allocations?'.Arr::query([
                'filter' => [
                    'gte' => [
                        'assignment_start_date' => $allocation->assignment_start_date->toDateString(),
                    ],
                ],
            ]))
//        ->dump()
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $allocation->getKey());

        $this->getJson('api/data-allocations?'.Arr::query([
                'filter' => [
                    'gt' => [
                        'assignment_end_date' => $allocation->assignment_end_date->subDay()->toDateString(),
                    ],
                ],
            ]))
//        ->dump()
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $allocation->getKey());

        $this->getJson('api/data-allocations?'.Arr::query([
                'filter' => [
                    'gte' => [
                        'assignment_end_date' => $allocation->assignment_end_date->toDateString(),
                    ],
                ],
            ]))
//        ->dump()
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $allocation->getKey());

        // lte, lt
        $this->getJson('api/data-allocations?'.Arr::query([
                'filter' => [
                    'lt' => [
                        'assignment_start_date' => $allocation->assignment_start_date->toDateString(),
                    ],
                ],
            ]))
//        ->dump()
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $allocation2->getKey());


        $this->getJson('api/data-allocations?'.Arr::query([
                'filter' => [
                    'lte' => [
                        'assignment_start_date' => $allocation->assignment_start_date->subDay()->toDateString(),
                    ],
                ],
            ]))
//        ->dump()
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $allocation2->getKey());

        $this->getJson('api/data-allocations?'.Arr::query([
                'filter' => [
                    'lt' => [
                        'assignment_end_date' => $allocation->assignment_end_date->subDay()->toDateString(),
                    ],
                ],
            ]))
//        ->dump()
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $allocation2->getKey());

        $this->getJson('api/data-allocations?'.Arr::query([
                'filter' => [
                    'lte' => [
                        'assignment_end_date' => $allocation->assignment_end_date->subDay()->toDateString(),
                    ],
                ],
            ]))
//        ->dump()
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $allocation2->getKey());

    }

    /**
     * Test an ability to filter data allocations by stages.
     */
    public function testCanFilterDataAllocationsByStage(): void
    {
        $this->app['db.connection']->table('data_allocations')->delete();

        $this->authenticateApi();

        foreach (DataAllocationStageEnum::cases() as $stage) {
            $allocation = DataAllocation::factory()->create([
                'stage' => $stage,
            ]);

            $this->getJson('api/data-allocations?'.Arr::query([
                    'filter' => [
                        'stage' => $stage->name,
                    ],
                ]))
//        ->dump()
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $allocation->getKey());
        }
    }

    /**
     * Test an ability to view data allocation.
     */
    public function testCanViewDataAllocation(): void
    {
        $this->authenticateApi();

        $dataAllocation = DataAllocation::factory()
            ->hasAttached(User::factory(2), relationship: 'assignedUsers')
            ->create();

        $this->getJson('api/data-allocations/'.$dataAllocation->getKey().'?include=assigned_users')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'company_id',
                'business_division_id',
                'stage',
                'stage_value',
                'distribution_algorithm',
                'assignment_start_date',
                'assignment_end_date',
                'assigned_users' => [
                    '*' => ['id'],
                ],
                'created_at',
                'updated_at',
            ])
            ->assertJsonPath('stage', 'Init');
    }

    /**
     * Test an ability to delete data allocation.
     */
    public function testCanDeleteDataAllocation(): void
    {
        $this->authenticateApi();

        $dataAllocation = DataAllocation::factory()->create();

        $this->deleteJson('api/data-allocations/'.$dataAllocation->getKey())
            ->assertNoContent();

        $this->getJson('api/data-allocations/'.$dataAllocation->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to process init stage of data allocation.
     */
    public function testCanProcessInitStageOfDataAllocation(): void
    {
        $this->authenticateApi();

        $this->postJson('api/data-allocations?include=file')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
            ]);
    }

    /**
     * Test an ability to upload data allocation file.
     */
    public function testCanUploadDataAllocationFile(): void
    {
        $this->authenticateApi();

        $model = DataAllocation::factory()->create();

        $this->postJson("api/data-allocations/{$model->getKey()}/files", [
            'file' => UploadedFile::fake()->create('data-allocation.csv'),
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'filepath',
                'filename',
                'extension',
                'size',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * Test an ability to process import stage of data allocation.
     */
    public function testCanProcessImportStageOfDataAllocation(): void
    {
        $this->authenticateApi();

        $model = DataAllocation::factory()->create();
        $file = DataAllocationFile::factory()->create();

        Storage::disk('data_allocation_files')
            ->put($file->filepath, file_get_contents(base_path('tests/Feature/Data/opportunity/opps-alloc.xlsx')));

        $assignedUsers = User::factory(3)->create();

        ImportedOpportunityDataValidator::setFlag(ImportedOpportunityDataValidator::IGNORE_MISSING_ACC_DATA, true);

        $response = $this->postJson("api/data-allocations/{$model->getKey()}/import?include[]=assigned_users&include[]=file",
            $data = [
                'company_id' => Company::query()->where('type', CompanyType::INTERNAL)->get()->random()->getKey(),
                'business_division_id' => BusinessDivision::query()->get()->random()->getKey(),
                'file_id' => $file->getKey(),
                'assignment_start_date' => $this->faker->dateTimeBetween('now', '+3days')->format('Y-m-d'),
                'assignment_end_date' => $this->faker->dateTimeBetween('+3days', '+10days')->format('Y-m-d'),
                'assigned_users' => $assignedUsers->map->only('id')->all(),
            ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'company_id',
                'business_division_id',
                'stage',
                'distribution_algorithm',
                'assignment_start_date',
                'assignment_end_date',
                'assigned_users' => [
                    '*' => ['id'],
                ],
                'created_at',
                'updated_at',
            ])
            ->assertJsonPath('stage', 'Import');

        foreach (Arr::except($data, 'assigned_users') as $attribute => $value) {
            $this->assertSame($value, $response->json($attribute));
        }

        $this->assertCount(count($data['assigned_users']), $response->json('assigned_users'));

        foreach ($data['assigned_users'] as $user) {
            $this->assertContains($user['id'], $response->json('assigned_users.*.id'));
        }

        $r = $this->getJson("api/data-allocations/{$model->getKey()}/?include[]=opportunities&include[]=file")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'file' => [
                    'id',
                    'imported_at',
                ],
                'opportunities' => [
                    '*' => [
                        'id',
                        'assigned_user' => [
                            'id',
                            'first_name',
                            'middle_name',
                            'last_name',
                            'email',
                        ],
                        'project_name',
                        'opportunity_type',
                        'account_name',
                        'account_manager_name',
                        'opportunity_amount',
                        'opportunity_start_date',
                        'opportunity_end_date',
                        'opportunity_closing_date',
                        'sale_action_name',
                    ],
                ],
            ])
            ->assertJsonCount(17, 'opportunities');

        $c = 0;

        foreach ($r->json('opportunities.*.assigned_user.id') as $userId) {
            $c = $assignedUsers->count() === $c ? 0 : $c;

            $this->assertSame($assignedUsers[$c]->getKey(), $userId);

            $c++;
        }
    }

    /**
     * Test an ability to process review stage of data allocation.
     */
    public function testCanProcessReviewStageOfDataAllocation(): void
    {
        $this->authenticateApi();

        $allocation = DataAllocation::factory()
            ->for(DataAllocationFile::factory()
                ->has(DataAllocationRecord::factory(10)
                    ->for(User::factory(), relationship: 'assignedUser')
                    ->for(Opportunity::factory()->imported()),
                    relationship: 'allocationRecords'
                ),
                relationship: 'file'
            )
            ->create([
                'stage' => DataAllocationStageEnum::Import,
            ]);

        $r = $this->postJson("api/data-allocations/{$allocation->getKey()}/review?include[]=processed_opportunities", $data = [
            'selected_opportunities' => $allocation->file->allocationRecords->take(2)->map->only('id'),
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'stage',
                'processed_opportunities' => [
                    '*' => [
                        'id',
                        'is_selected',
                        'result',
                        'result_reason',
                    ],
                ],
            ])
            ->assertJsonPath('stage', 'Review');

        $selectedFromResponse = collect($r->json('processed_opportunities'))
            ->lazy()
            ->whereStrict('is_selected', true)
            ->pluck('id')
            ->all();

        $this->assertCount(count($data['selected_opportunities']), $selectedFromResponse);

        foreach ($data['selected_opportunities'] as $op) {
            $this->assertContains($op['id'], $selectedFromResponse);
        }
    }

    /**
     * Test an ability to process review stage of data allocation before import.
     */
    public function testCanNotProcessReviewStageOfDataAllocationBeforeImport(): void
    {
        $this->authenticateApi();

        $allocation = DataAllocation::factory()
            ->for(DataAllocationFile::factory()
                ->has(DataAllocationRecord::factory(10)
                    ->for(Opportunity::factory()->imported()),
                    relationship: 'allocationRecords'
                ),
                relationship: 'file'
            )
            ->create([
                'stage' => DataAllocationStageEnum::Init,
            ]);

        $r = $this->postJson("api/data-allocations/{$allocation->getKey()}/review?include[]=opportunities", $data = [
            'selected_opportunities' => $allocation->file->allocationRecords->take(2)->map->only('id'),
        ])
//            ->dump()
            ->assertForbidden();
    }

    /**
     * Test an ability to process results stage of data allocation.
     */
    public function testCanProcessResultsStageOfDataAllocation(): void
    {
        $this->authenticateApi();

        $allocation = DataAllocation::factory()
            ->for(DataAllocationFile::factory()
                ->has(DataAllocationRecord::factory(10)
                    ->for(Opportunity::factory()->imported()),
                    relationship: 'allocationRecords'
                ),
                relationship: 'file'
            )
            ->create([
                'stage' => DataAllocationStageEnum::Review,
            ]);

        $this->postJson("api/data-allocations/{$allocation->getKey()}/results")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'stage',
                'stage_value',
            ])
            ->assertJsonPath('stage', 'Results')
            ->assertJsonPath('stage_value', 100);
    }
}
