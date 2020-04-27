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
use App\Notifications\Task\TaskAssigned as AssignedNotification;
use App\Notifications\Task\TaskRevoked as RevokedNotification;
use Illuminate\Support\{
    Facades\Event,
    Facades\Notification,
};

class QuoteTaskTest extends TestCase
{
    use AssertsListing, WithFakeUser, WithFakeQuote;

    /**
     * Test quote tasks listing.
     *
     * @return void
     */
    public function testQuoteTaskListing()
    {
        $response = $this->getJson(url('api/quotes/tasks/' . $this->quote->getKey()));

        $this->assertListing($response);
    }

    /**
     * Test creating a new task for specific quote.
     *
     * @return void
     */
    public function testQuoteTaskCreating()
    {
        Event::fake(TaskCreated::class);

        Event::hasListeners(TaskEventSubscriber::class);

        $attributes = factory(Task::class)->state('users')->raw();

        $response = $this->postJson(url('api/quotes/tasks/' . $this->quote->id), $attributes)
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
        $attributes = factory(Task::class)->raw();

        $this->postJson(url('api/quotes/tasks/' . $this->quote->id), $attributes)
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
        Event::fake(TaskUpdated::class);

        Event::hasListeners(TaskEventSubscriber::class);

        $task = factory(Task::class)->create([
            'taskable_id' => $this->quote->id,
            'taskable_type' => $this->quote->getMorphClass()
        ]);

        $attributes = factory(Task::class)->state('users')->raw();

        $this->patchJson(url('api/quotes/tasks/' . $this->quote->id . '/' . $task->id), $attributes)
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
        Notification::fake();

        $task = factory(Task::class)->create([
            'taskable_id' => $this->quote->id,
            'taskable_type' => $this->quote->getMorphClass()
        ]);

        $usersCount = $task->users->count();

        $this->assertTrue($usersCount > 0);

        $attributes = factory(Task::class)->raw();

        $this->patchJson(url('api/quotes/tasks/' . $this->quote->id . '/' . $task->id), $attributes)
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
        Event::fake(TaskDeleted::class);

        Event::hasListeners(TaskEventSubscriber::class);

        $task = factory(Task::class)->create([
            'taskable_id' => $this->quote->id,
            'taskable_type' => $this->quote->getMorphClass()
        ]);

        $this->deleteJson(url('api/quotes/tasks/' . $this->quote->id . '/' . $task->id))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($task);

        Event::assertDispatched(TaskDeleted::class, fn (TaskDeleted $event) => $event->task->getKey() === $task->getKey());
    }
}