<?php

namespace Tests\Feature;

use App\Domain\Asset\Models\Asset;
use App\Domain\Company\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class ActivityTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view paginated activity log.
     */
    public function testCanViewPaginatedActivityLog(): void
    {
        $this->authenticateApi();

        $this->postJson('api/activities')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'log_name',
                        'description',
                        'subject_id',
                        'subject_name',
                        'subject_type',
                        'causer_name',
                        'changes' => [
                            'old' => [
                                '*' => [
                                    'attribute',
                                    'value',
                                ],
                            ],
                            'attributes' => [
                                '*' => [
                                    'attribute',
                                    'value',
                                ],
                            ],
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
                'summary' => [
                    '*' => [
                        'type', 'count',
                    ],
                ],
            ]);

        $this->postJson('api/activities', [
            'order_by_created' => 'asc',
            'search' => Str::random(10),
        ])
//            ->dump()
            ->assertOk();
    }

    /**
     * Test an ability to view paginated activity log of subject.
     */
    public function testCanViewPaginatedActivityLogOfSubject(): void
    {
        $this->authenticateApi();

        $subject = Company::factory()->create();

        $this->postJson("api/activities/subject/{$subject->getKey()}")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'log_name',
                        'description',
                        'subject_id',
                        'subject_name',
                        'subject_type',
                        'causer_name',
                        'changes' => [
                            'old' => [
                                '*' => [
                                    'attribute',
                                    'value',
                                ],
                            ],
                            'attributes' => [
                                '*' => [
                                    'attribute',
                                    'value',
                                ],
                            ],
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
                'summary' => [
                    '*' => [
                        'type', 'count',
                    ],
                ],
            ]);

        $this->postJson('api/activities', [
            'order_by_created' => 'asc',
            'search' => Str::random(10),
        ])
//            ->dump()
            ->assertOk();
    }

    /**
     * Test an ability to view activity log of specific description.
     */
    public function testCanViewActivityLogByDescription(): void
    {
        $this->authenticateApi();

        $types = config('activitylog.types');

        foreach ($types as $type) {
            $this->postJson(url('api/activities'), ['types' => [$type]])
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'log_name',
                            'description',
                            'subject_id',
                            'subject_name',
                            'subject_type',
                            'causer_name',
                            'changes' => [
                                'old' => [
                                    '*' => [
                                        'attribute',
                                        'value',
                                    ],
                                ],
                                'attributes' => [
                                    '*' => [
                                        'attribute',
                                        'value',
                                    ],
                                ],
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
                    'summary' => [
                        '*' => [
                            'type', 'count',
                        ],
                    ],
                ]);
        }

        $this->postJson('api/activities', ['types' => $types]);
    }

    /**
     * Test an ability to filter activity log by subject types.
     */
    public function testCanViewActivityLogBySubjectTypes(): void
    {
        $this->authenticateApi();

        $subjectTypes = array_keys(config('activitylog.subject_types'));

        foreach ($subjectTypes as $type) {
            $this->postJson('api/activities', ['subject_types' => [$type]])
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'log_name',
                            'description',
                            'subject_id',
                            'subject_name',
                            'subject_type',
                            'causer_name',
                            'changes' => [
                                'old' => [
                                    '*' => [
                                        'attribute',
                                        'value',
                                    ],
                                ],
                                'attributes' => [
                                    '*' => [
                                        'attribute',
                                        'value',
                                    ],
                                ],
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
                    'summary' => [
                        '*' => [
                            'type', 'count',
                        ],
                    ],
                ]);
        }

        $this->postJson('api/activities', ['subject_types' => $subjectTypes])
            ->assertOk();
    }

    /**
     * Test an ability to filter activity log by periods.
     */
    public function testCanViewActivityLogByPeriods(): void
    {
        $this->authenticateApi();

        foreach (config('activitylog.periods') as $period) {
            $this->postJson(url('api/activities'), ['period' => $period])
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'log_name',
                            'description',
                            'subject_id',
                            'subject_name',
                            'subject_type',
                            'causer_name',
                            'changes' => [
                                'old' => [
                                    '*' => [
                                        'attribute',
                                        'value',
                                    ],
                                ],
                                'attributes' => [
                                    '*' => [
                                        'attribute',
                                        'value',
                                    ],
                                ],
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
                    'summary' => [
                        '*' => [
                            'type', 'count',
                        ],
                    ],
                ]);
        }
    }

    /**
     * Test an ability to export activity log to pdf.
     */
    public function testCanExportActivityLogToPdf(): void
    {
        $this->authenticateApi();

        $this->app['db.connection']->table('activity_log')->delete();

        $model = factory(Asset::class)->create();

        activity()
            ->on($model)
            ->log('created');

        activity()
            ->on($model)
            ->log('updated');

        activity()
            ->on($model)
            ->withProperties([
                'old' => [
                    'product_no' => 'ABCD',
                ],
                'new' => [
                    'product_no' => 'GHJK',
                ],
            ])
            ->log('updated');

        $this->post('api/activities/export/pdf')
//            ->dump()
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test an ability to export activity log to csv.
     */
    public function testCanExportActivityLogToCsv(): void
    {
        $this->authenticateApi();

        $this->app['db.connection']->table('activity_log')->delete();

        $model = factory(Asset::class)->create();

        activity()
            ->on($model)
            ->log('created');

        activity()
            ->on($model)
            ->log('updated');

        activity()
            ->on($model)
            ->withProperties([
                'old' => [
                    'product_no' => 'ABCD',
                ],
                'new' => [
                    'product_no' => 'GHJK',
                ],
            ])
            ->log('updated');

        $this->post('api/activities/export/csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/plain');
    }
}
