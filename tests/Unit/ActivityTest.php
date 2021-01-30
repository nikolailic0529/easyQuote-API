<?php

namespace Tests\Unit;

use App\Models\Quote\Quote;
use App\Models\System\Activity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\ActivityLogStatus;
use Tests\TestCase;

/**
 * @group build
 */
class ActivityTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view activity by the specified subject.
     *
     * @return void
     */
    public function testCanViewPaginatedActivityBySubject()
    {
        $this->authenticateApi();

        $this->app[ActivityLogStatus::class]->disable();

        $activitySubject = factory(Quote::class)->create();

        factory(Activity::class)->create([
            'description' => 'created',
            'subject_id' => $activitySubject->getKey(),
            'subject_type' => get_class($activitySubject),
        ]);

        factory(Activity::class, 2)->create([
            'description' => 'updated',
            'subject_id' => $activitySubject->getKey(),
            'subject_type' => get_class($activitySubject),
        ]);

        $this->getJson('api/activities/subject/'.$activitySubject->getKey().'?per_page=20&page=1&order_by_created_at=desc')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'log_name',
                        'description',
                        'subject_id',
                        'subject_type',
                        'causer_name',
                        'changes' => [
                            'old',
                            'attributes'
                        ],
                        'created_at'
                    ]
                ],
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
                'summary' => [
                    '*' => [
                        'type', 'count'
                    ]
                ],
                'subject_name'
            ]);
    }

    /**
     * Test an ability to view paginated existing activity.
     *
     * @return void
     */
    public function testCanViewPaginatedActivity()
    {
        $this->authenticateApi();

        factory(Activity::class, 30)->create();

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
                        'subject_type',
                        'causer_name',
                        'changes' => [
                            'old',
                            'attributes'
                        ],
                        'created_at'
                    ]
                ],
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
//                'first_page_url',
//                'last_page_url',
//                'next_page_url',
//                'prev_page_url',
                'to',
                'total',
                'summary' => [
                    '*' => [
                        'type', 'count'
                    ]
                ]
            ]);
    }

    /**
     * Test an ability to filter activity by types.
     *
     * @return void
     */
    public function testCanViewPaginatedActivityByTypes()
    {
        $this->authenticateApi();

        factory(Activity::class, 30)->create();

        $types = config('activitylog.types');

        $this->postJson('api/activities', ['types' => $types])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'log_name',
                        'description',
                        'subject_id',
                        'subject_type',
                        'causer_name',
                        'changes' => [
                            'old',
                            'attributes'
                        ],
                        'created_at'
                    ]
                ],
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
                'summary' => [
                    '*' => [
                        'type', 'count'
                    ]
                ]
            ]);

        foreach ($types as $type) {
            $this->postJson('api/activities', ['types' => [$type]])
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'log_name',
                            'description',
                            'subject_id',
                            'subject_type',
                            'causer_name',
                            'changes' => [
                                'old',
                                'attributes'
                            ],
                            'created_at'
                        ]
                    ],
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                    'summary' => [
                        '*' => [
                            'type', 'count'
                        ]
                    ]
                ]);
        }
    }

    /**
     * Test an ability to view activity by subject types.
     *
     * @return void
     */
    public function testCanViewActivityBySubjectTypes()
    {
        $this->authenticateApi();

        $subjects = array_keys(config('activitylog.subject_types'));

        factory(Activity::class, 30)->create();


        $this->postJson('api/activities', [
            'subject_types' => $subjects
        ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'log_name',
                        'description',
                        'subject_id',
                        'subject_type',
                        'causer_name',
                        'changes' => [
                            'old',
                            'attributes'
                        ],
                        'created_at'
                    ]
                ],
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
                'summary' => [
                    '*' => [
                        'type', 'count'
                    ]
                ]
            ]);

        foreach ($subjects as $subject) {
            $this->postJson('api/activities', ['subject_types' => [$subject]])
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'log_name',
                            'description',
                            'subject_id',
                            'subject_type',
                            'causer_name',
                            'changes' => [
                                'old',
                                'attributes'
                            ],
                            'created_at'
                        ]
                    ],
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                    'summary' => [
                        '*' => [
                            'type', 'count'
                        ]
                    ]
                ]);
        }
    }

    /**
     * Test Activity listing by specified periods.
     *
     * @return void
     */
    public function testCanFilterActivityByPeriods()
    {
        $this->authenticateApi();

        foreach (config('activitylog.periods') as $period) {
            $this->postJson('api/activities', [
                'period' => $period
            ])
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'log_name',
                            'description',
                            'subject_id',
                            'subject_type',
                            'causer_name',
                            'changes' => [
                                'old',
                                'attributes'
                            ],
                            'created_at'
                        ]
                    ],
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                    'summary' => [
                        '*' => [
                            'type', 'count'
                        ]
                    ]
                ]);
        }
    }

    /**
     * Test an ability to export activity to pdf.
     *
     * @return void
     */
    public function testCanExportActivityReportToPdf()
    {
        $this->authenticateApi();

        $this->postJson('api/activities/export/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test an ability to export activity to csv.
     *
     * @return void
     */
    public function testCanExportActivityReportToCsv()
    {
        $this->authenticateApi();

        $this->postJson('api/activities/export/csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8');
    }
}
