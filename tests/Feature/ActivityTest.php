<?php

namespace Tests\Feature;

use App\Models\Asset;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Traits\{AssertsListing,};

/**
 * @group build
 */
class ActivityTest extends TestCase
{
    use AssertsListing, DatabaseTransactions;

    /**
     * Test Activity listing.
     *
     * @return void
     */
    public function testCanViewPaginatedActivityLog()
    {
        $this->authenticateApi();

        $response = $this->postJson(url('api/activities'));

        $this->assertListing($response);

        $response = $this->postJson(url('api/activities'), [
            'order_by_created' => 'asc',
            'search' => Str::random(10)
        ]);

        $this->assertListing($response);
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

        collect($types)->each(function ($type) {
            $response = $this->postJson(url('api/activities'), ['types' => [$type]]);
            $this->assertListing($response);
        });

        $response = $this->postJson(url('api/activities'), compact('types'));

        $this->assertListing($response);
    }

    /**
     * Test Activity listing by specified subject types.
     *
     * @return void
     */
    public function testCanViewActivityLogBySubjectTypes()
    {
        $this->authenticateApi();

        $subject_types = array_keys(config('activitylog.subject_types'));

        collect($subject_types)->each(function ($type) {
            $response = $this->postJson(url('api/activities'), ['subject_types' => [$type]]);
            $this->assertListing($response);
        });

        $response = $this->postJson(url('api/activities'), compact('subject_types'));
        $this->assertListing($response);
    }

    /**
     * Test Activity listing by specified periods.
     *
     * @return void
     */
    public function testCanViewActivityLogByPeriods()
    {
        $this->authenticateApi();

        collect(config('activitylog.periods'))->each(function ($period) {
            $response = $this->postJson(url('api/activities'), compact('period'));
            $this->assertListing($response);
        });
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
            ->log('updated');

        $this->post(url('api/activities/export/pdf'))
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

        $this->post(url('api/activities/export/csv'))
            ->assertOk()
            ->assertHeader('content-type', 'text/plain');
    }
}
