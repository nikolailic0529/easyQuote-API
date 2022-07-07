<?php

namespace Tests\Unit\Task;

use App\Console\Commands\Routine\Notifications\TaskExpiration;
use App\DTO\Tasks\UpdateTaskData;
use App\Events\Task\TaskExpired;
use App\Listeners\TaskEventSubscriber;
use App\Models\{Company, Task\Task};
use App\Services\Task\TaskEntityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
    public function testTaskExpiryNotification(): void
    {
        Event::fake(TaskExpired::class);
        Event::hasListeners(TaskEventSubscriber::class);

        $task = Task::factory()->expired()
            ->has(Company::factory(), 'companies')
            ->create();

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
    public function testTaskExpiryNotificationAfterUpdate(): void
    {
        Event::fake(TaskExpired::class);
        Event::hasListeners(TaskEventSubscriber::class);

        /** @var Task $task */
        $task = Task::factory()
            ->expired()
            ->has(Company::factory(), 'companies')
            ->create();

        Artisan::call(TaskExpiration::class);

        $this->assertEquals(1, $task->notifications()->where(['notification_key' => TaskExpiration::NOTIFICATION_KEY])->count());

        Event::assertDispatched(TaskExpired::class, fn(TaskExpired $event) => $event->task->getKey() === $task->getKey());

        $this->app[TaskEntityService::class]->updateTask($task, new UpdateTaskData([
            'name' => $task->name,
            'content' => $task->content,
            'expiry_date' => $task->expiry_date->subDay(),
            'priority' => $task->priority,
            'users' => $task->users->all(),
            'attachments' => $task->attachments->all(),
        ]));

        $this->assertEquals(0, $task->notifications()->where(['notification_key' => TaskExpiration::NOTIFICATION_KEY])->count());

        Event::fake(TaskExpired::class);

        Artisan::call(TaskExpiration::class);

        Event::assertDispatched(TaskExpired::class, fn(TaskExpired $event) => $event->task->getKey() === $task->getKey());
    }
}
