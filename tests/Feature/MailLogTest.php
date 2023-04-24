<?php

namespace Tests\Feature;

use App\Domain\Mail\Models\MailLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group build
 */
class MailLogTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view paginated mail log.
     */
    public function testCanViewPaginatedMailLog(): void
    {
        $this->authenticateApi();

        MailLog::factory()->create();

        $r = $this->getJson('api/mail-log')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'from',
                        'to',
                        'subject',
                        'sent_at',
                    ],
                ],
            ]);

        $this->assertNotEmpty($r->json('data'));

        $this->getJson('api/mail-log?order_by_sent_at=asc')
            ->assertOk();
    }

    /**
     * Test an ability to view mail log record.
     */
    public function testCanViewMailLogRecord(): void
    {
        $this->authenticateApi();

        $record = MailLog::factory()->create();

        $this->getJson('api/mail-log/'.$record->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'message_id',
                    'from',
                    'to',
                    'subject',
                    'body',
                    'sent_at',
                ],
            ]);
    }
}
