<?php

namespace Tests\Unit\Quote;

use Tests\TestCase;
use App\Events\Task\{
    TaskCreated,
    TaskDeleted,
    TaskUpdated,
};
use App\Listeners\TaskEventSubscriber;
use Tests\Unit\Traits\{
    AssertsListing,
    WithFakeQuote,
    WithFakeUser,
};
use App\Models\Task;
use App\Notifications\Task\TaskRevoked as RevokedNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\{
    Facades\Event,
    Facades\Notification,
};

/**
 * @group build
 */
class QuoteTaskTest extends TestCase
{
    use AssertsListing, WithFakeUser, WithFakeQuote, DatabaseTransactions;

    /**
     * Test quote tasks listing.
     *
     * @return void
     */
    public function testQuoteTaskListing()
    {
        $quote = $this->createQuote($this->user);

        $response = $this->getJson(url('api/quotes/tasks/' . $quote->getKey()));

        $this->assertListing($response);
    }

    /**
     * Test creating a new task for specific quote.
     *
     * @return void
     */
    public function testQuoteTaskCreating()
    {
        $quote = $this->createQuote($this->user);
        Event::fake(TaskCreated::class);

        Event::hasListeners(TaskEventSubscriber::class);

        $attributes = factory(Task::class)->state('users')->raw();

        $response = $this->postJson(url('api/quotes/tasks/' . $quote->id), $attributes)
            ->assertCreated()
            ->assertJsonStructure(['id', 'name', 'content', 'taskable_id', 'user_id']);

        $id = $response->json('id');

        Event::assertDispatched(TaskCreated::class, fn (TaskCreated $event) => $event->task->getKey() === $id);
    }

    /**
     * Test creating a new task for specific quote without users.
     *
     * @return void
     */
    public function testQuoteTaskCreatingWithoutUsers()
    {
        $quote = $this->createQuote($this->user);
        $attributes = factory(Task::class)->raw();

        $this->postJson(url('api/quotes/tasks/' . $quote->id), $attributes)
            ->assertCreated()
            ->assertJsonStructure(['id', 'name', 'content', 'taskable_id', 'user_id']);
    }

    /**
     * Test updating a newly created quote task.
     *
     * @return void
     */
    public function testQuoteTaskUpdating()
    {
        $quote = $this->createQuote($this->user);
        Event::fake(TaskUpdated::class);

        Event::hasListeners(TaskEventSubscriber::class);

        $task = factory(Task::class)->create([
            'taskable_id' => $quote->id,
            'taskable_type' => $quote->getMorphClass()
        ]);

        $attributes = factory(Task::class)->state('users')->raw();

        $this->patchJson(url('api/quotes/tasks/' . $quote->id . '/' . $task->id), $attributes)
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'content', 'taskable_id', 'user_id'])
            ->assertJsonFragment(['content' => $attributes['content']]);

        Event::assertDispatched(TaskUpdated::class, fn (TaskUpdated $event) => $event->task->getKey() === $task->getKey());
    }

    /**
     * Test updating a newly created quote task without users attribute.
     *
     * @return void
     */
    public function testQuoteTaskRevokeUsers()
    {
        $quote = $this->createQuote($this->user);
        Notification::fake();

        $task = factory(Task::class)->create([
            'taskable_id' => $quote->id,
            'taskable_type' => $quote->getMorphClass()
        ]);

        $usersCount = $task->users->count();

        $this->assertTrue($usersCount > 0);

        $attributes = factory(Task::class)->raw();

        $this->patchJson(url('api/quotes/tasks/' . $quote->id . '/' . $task->id), $attributes)
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'content', 'taskable_id', 'user_id'])
            ->assertJsonFragment(['content' => $attributes['content']]);

        $this->assertCount(0, $task->refresh()->users);

        Notification::assertTimesSent($usersCount, RevokedNotification::class);
    }

    /**
     * Test deleting a newly created quote task.
     *
     * @return void
     */
    public function testQuoteTaskDeleting()
    {
        $quote = $this->createQuote($this->user);
        Event::fake(TaskDeleted::class);

        Event::hasListeners(TaskEventSubscriber::class);

        $task = factory(Task::class)->create([
            'taskable_id' => $quote->id,
            'taskable_type' => $quote->getMorphClass()
        ]);

        $this->deleteJson(url('api/quotes/tasks/' . $quote->id . '/' . $task->id))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($task);

        Event::assertDispatched(TaskDeleted::class, fn (TaskDeleted $event) => $event->task->getKey() === $task->getKey());
    }
}
