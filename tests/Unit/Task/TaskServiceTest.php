<?php

namespace Tests\Unit\Task;

use App\Console\Commands\Routine\Notifications\TaskExpiration;
use App\DTO\Tasks\UpdateTaskData;
use App\Events\Task\TaskExpired;
use App\Listeners\TaskEventSubscriber;
use App\Models\{Task,};
use App\Services\TaskService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test task expiry notification.
     *
     * @return void
     */
    public function testTaskExpiryNotification()
    {
        Event::fake(TaskExpired::class);
        Event::hasListeners(TaskEventSubscriber::class);

        $taskable = factory(Arr::random(Task::TASKABLES))->create();

        $task = tap(factory(Task::class)->state('expired')->make(), fn(Task $task) => $task->taskable()->associate($taskable)->save());

        Artisan::call(TaskExpiration::class);

        $notificationKey = 'expired';

        $this->assertEquals(1, $task->notifications()->where(['notification_key' => $notificationKey])->count());

        Event::assertDispatched(TaskExpired::class, fn(TaskExpired $event) => $event->task->getKey() === $task->getKey());

        /** Assert that expiry notification for the task is sent only once. */
        Event::fake(TaskExpired::class);

        Artisan::call(TaskExpiration::class);

        $this->assertEquals(1, $task->notifications()->where(['notification_key' => $notificationKey])->count());

        Event::assertNotDispatched(TaskExpired::class);
    }

    /**
     * Test task expiry notification after update.
     * Assert that model notification mark will be removed if new expiry_date attribute is not equal original.
     *
     * @return void
     */
    public function testTaskExpiryNotificationAfterUpdate()
    {
        Event::fake(TaskExpired::class);
        Event::hasListeners(TaskEventSubscriber::class);

        $taskable = factory(Arr::random(Task::TASKABLES))->create();

        $task = tap(factory(Task::class)->state('expired')->make(), fn(Task $task) => $task->taskable()->associate($taskable)->save());

        Artisan::call(TaskExpiration::class);

        $notificationKey = 'expired';

        $this->assertEquals(1, $task->notifications()->where(['notification_key' => $notificationKey])->count());

        Event::assertDispatched(TaskExpired::class, fn(TaskExpired $event) => $event->task->getKey() === $task->getKey());

        $this->app[TaskService::class]->updateTask($task, new UpdateTaskData([
            'name' => $task->name,
            'content' => $task->content,
            'expiry_date' => $task->expiry_date->subDay(),
            'priority' => $task->priority,
            'users' => $task->users->all(),
            'attachments' => $task->attachments->all(),
        ]));

        $this->assertEquals(0, $task->notifications()->where(['notification_key' => $notificationKey])->count());

        Event::fake(TaskExpired::class);

        Artisan::call(TaskExpiration::class);

        Event::assertDispatched(TaskExpired::class, fn(TaskExpired $event) => $event->task->getKey() === $task->getKey());
    }
}
