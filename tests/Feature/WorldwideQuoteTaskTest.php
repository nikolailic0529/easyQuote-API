<?php

namespace Tests\Feature;

use App\Events\Task\TaskCreated;
use App\Events\Task\TaskDeleted;
use App\Events\Task\TaskUpdated;
use App\Listeners\TaskEventSubscriber;
use App\Models\Quote\WorldwideQuote;
use App\Models\Task;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Class WorldwideQuoteTaskTest
 * @group worldwide
 */
class WorldwideQuoteTaskTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test can view paginated worldwide quote tasks.
     *
     * @return void
     */
    public function testCanViewPaginatedWorldwideQuoteTasks()
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create();

        $wwQuoteTasks = factory(Task::class, 30)->create([
            'taskable_id' => $wwQuote->getKey(),
            'taskable_type' => WorldwideQuote::class
        ]);

        $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/tasks')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user' => [
                            'id', 'email', 'first_name', 'middle_name', 'last_name'
                        ],
                        'taskable_id',
                        'name',
                        'content',
                        'expiry_date',
                        'priority',
                        'users' => [
                            '*' => ['id', 'email', 'first_name', 'middle_name', 'last_name']
                        ],
                        'attachments' => [
                            '*' => [
                                'id', 'type', 'filepath', 'filename', 'extension', 'size', 'created_at'
                            ]
                        ],
                        'created_at',
                        'updated_at'

                    ]
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total'
            ]);
    }

    /**
     * Test can create a new task on worldwide quote.
     *
     * @return void
     */
    public function testCanCreateTaskOnWorldwideQuote()
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create();

        Event::fake(TaskCreated::class);

        Event::hasListeners(TaskEventSubscriber::class);

        $attributes = factory(Task::class)->state('users')->raw();

        $response = $this->postJson('api/ww-quotes/'.$wwQuote->getKey().'/tasks', $attributes)
            ->assertCreated()
            ->assertJsonStructure(['id', 'name', 'content', 'taskable_id', 'user_id']);

        $id = $response->json('id');

        Event::assertDispatched(TaskCreated::class, fn(TaskCreated $event) => $event->task->getKey() === $id);
    }

    /**
     * Test can update task on worldwide quote.
     *
     * @return void
     */
    public function testCanUpdateTaskOnWorldwideQuote()
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create();

        Event::fake(TaskUpdated::class);

        Event::hasListeners(TaskEventSubscriber::class);

        $task = factory(Task::class)->create([
            'taskable_id' => $wwQuote->getKey(),
            'taskable_type' => $wwQuote->getMorphClass()
        ]);

        $attributes = factory(Task::class)->state('users')->raw();

        $this->patchJson('api/ww-quotes/'.$wwQuote->getKey().'/tasks/'.$task->getKey(), $attributes)
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'content', 'taskable_id', 'user_id'])
            ->assertJsonFragment(['content' => $attributes['content']]);

        Event::assertDispatched(TaskUpdated::class, fn(TaskUpdated $event) => $event->task->getKey() === $task->getKey());
    }

    /**
     * Test can delete an existing task on worldwide quote.
     *
     * @return void
     */
    public function testCanDeleteTaskOnWorldwideQuote()
    {
        $this->authenticateApi();

        $wwQuote = factory(WorldwideQuote::class)->create();

        Event::fake(TaskDeleted::class);

        Event::hasListeners(TaskEventSubscriber::class);

        $task = factory(Task::class)->create([
            'taskable_id' => $wwQuote->getKey(),
            'taskable_type' => $wwQuote->getMorphClass()
        ]);

        $this->deleteJson('api/ww-quotes/'.$wwQuote->getKey().'/tasks/'.$task->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($task);

        Event::assertDispatched(TaskDeleted::class, fn(TaskDeleted $event) => $event->task->getKey() === $task->getKey());
    }
}
