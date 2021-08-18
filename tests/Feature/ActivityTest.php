<?php

namespace Tests\Feature;

use App\Models\Asset;
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
     * Test Activity listing.
     *
     * @return void
     */
    public function testCanViewPaginatedActivityLog()
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
                                    'value'
                                ]
                            ],
                            'attributes' => [
                                '*' => [
                                    'attribute',
                                    'value'
                                ]
                            ]
                        ]
                    ]
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links' => [
                    '*' => [
                        'url', 'label', 'active'
                    ]
                ],
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
                'summary' => [
                    '*' => [
                        'type', 'count'
                    ]
                ]
            ]);

        $this->postJson('api/activities', [
            'order_by_created' => 'asc',
            'search' => Str::random(10)
        ])
//            ->dump()
            ->assertOk();
    }

    /**
     * Test Activity listing by specified types.
     *
     * @return void
     */
    public function testCanViewActivityLogByDescription()
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
                                        'value'
                                    ]
                                ],
                                'attributes' => [
                                    '*' => [
                                        'attribute',
                                        'value'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'current_page',
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ],
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total',
                    'summary' => [
                        '*' => [
                            'type', 'count'
                        ]
                    ]
                ]);
        }

        $this->postJson('api/activities', ['types' => $types]);
    }

    /**
     * Test Activity listing by specified subject types.
     *
     * @return void
     */
    public function testCanViewActivityLogBySubjectTypes()
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
                                        'value'
                                    ]
                                ],
                                'attributes' => [
                                    '*' => [
                                        'attribute',
                                        'value'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'current_page',
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ],
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total',
                    'summary' => [
                        '*' => [
                            'type', 'count'
                        ]
                    ]
                ]);
        }

        $this->postJson('api/activities', ['subject_types' => $subjectTypes])
            ->assertOk();
    }

    /**
     * Test Activity listing by specified periods.
     *
     * @return void
     */
    public function testCanViewActivityLogByPeriods()
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
                                        'value'
                                    ]
                                ],
                                'attributes' => [
                                    '*' => [
                                        'attribute',
                                        'value'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'current_page',
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ],
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
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
     * Test Activity Export as PDF.
     *
     * @return void
     */
    public function testCanExportActivityLogToPdf()
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
                    'product_no' => 'ABCD'
                ],
                'new' => [
                    'product_no' => 'GHJK'
                ]
            ])
            ->log('updated');

        $this->post('api/activities/export/pdf')
//            ->dump()
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test Activity Export as CSV.
     *
     * @return void
     */
    public function testCanExportActivityLogToCsv()
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
                    'product_no' => 'ABCD'
                ],
                'new' => [
                    'product_no' => 'GHJK'
                ]
            ])
            ->log('updated');

        $this->post('api/activities/export/csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/plain');
    }
}
