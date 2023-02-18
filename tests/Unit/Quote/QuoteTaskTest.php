<?php

namespace Tests\Unit\Quote;

use App\Domain\Date\Enum\DateDayEnum;
use App\Domain\Date\Enum\DateMonthEnum;
use App\Domain\Date\Enum\DateWeekEnum;
use App\Domain\Date\Enum\DayOfWeekEnum;
use App\Domain\Recurrence\Enum\RecurrenceTypeEnum;
use App\Domain\Rescue\Models\Quote;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Task\Enum\TaskTypeEnum;
use App\Domain\Task\Events\TaskCreated;
use App\Domain\Task\Events\TaskDeleted;
use App\Domain\Task\Events\TaskUpdated;
use App\Domain\Task\Listeners\TaskEventSubscriber;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Models\TaskRecurrence;
use App\Domain\Task\Models\TaskReminder;
use App\Domain\Task\Notifications\RevokedInvitationFromTaskNotification as RevokedNotification;
use App\Domain\Timezone\Models\Timezone;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Unit\Traits\AssertsListing;

/**
 * @group build
 */
class QuoteTaskTest extends TestCase
{
    use WithFaker;
    use AssertsListing;
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->faker);
    }

    /**
     * Test can view paginated tasks of a quote.
     */
    public function testCanViewPaginatedTasksOfQuote(): void
    {
        $this->authenticateApi();

        $quote = Quote::factory()->create();

        $response = $this->getJson('api/quotes/tasks/'.$quote->getKey());

        $this->assertListing($response);
    }

    /**
     * Test can view an existing task of a quote.
     */
    public function testCanViewTaskOfQuote(): void
    {
        $this->authenticateApi();

        $quote = Quote::factory()
            ->hasAttached($task = Task::factory()
                ->has(TaskRecurrence::factory(), relationship: 'recurrence')
                ->has(TaskReminder::factory()->forCurrentUser()->scheduled(), relationship: 'reminder')
                ->create()
            )
            ->create();

        $this->getJson('api/quotes/tasks/'.$quote->getKey().'/'.$task->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'name',
                'content',
                'expiry_date',
                'priority',
                'recurrence' => [
                    'id',
                    'user_id',
                    'task_id',
                    'type',
                    'day',
                    'week',
                    'day_of_week',
                    'month',
                    'occur_every',
                    'month',
                    'occur_every',
                    'occurrences_count',
                    'created_at',
                    'updated_at',
                ],
                'reminder' => [
                    'id',
                    'task_id',
                    'user_id',
                    'set_date',
                    'created_at',
                    'updated_at',
                ],
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * Test an ability to create a new task for a quote.
     */
    public function testCanCreateTaskForQuote(): void
    {
        $this->authenticateApi();

        $quote = Quote::factory()->create();
        Event::fake(TaskCreated::class);

        Event::hasListeners(TaskEventSubscriber::class);

        $attributes = Task::factory()
            ->has(User::factory()->count(2), 'users')
            ->raw();

        $response = $this->postJson('api/quotes/tasks/'.$quote->getKey(), $attributes)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure(['id', 'name', 'content', 'user_id']);

        $id = $response->json('id');

        Event::assertDispatched(TaskCreated::class, fn (TaskCreated $event) => $event->task->getKey() === $id);
    }

    /**
     * Test an ability to create a task for a quote with reminder data.
     */
    public function testCanCreateTaskForQuoteWithReminder(): void
    {
        $this->authenticateApi();

        /** @var User $user */
        $user = $this->app['auth']->user();

        $this->assertTrue($user->timezone->exists);

        $quote = factory(Quote::class)->create();

        $task = Task::factory()->raw();

        $reminder = TaskReminder::factory()->raw();

        $data = [
            'sales_unit_id' => SalesUnit::query()->first()->getKey(),
            'activity_type' => TaskTypeEnum::Task,
            'name' => $task['name'],
            'content' => $task['content'],
            'expiry_date' => $task['expiry_date'],
            'priority' => $task['priority'],
            'reminder' => [
                'set_date' => $reminder['set_date']->format('Y-m-d H:i:s'),
                'status' => $reminder['status'],
            ],
        ];

        $response = $this->postJson('api/quotes/tasks/'.$quote->getKey(), $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'user_id',
                'user' => [
                    'id',
                    'email',
                    'first_name',
                    'middle_name',
                    'last_name',
                ],
                'name',
                'content',
                'expiry_date',
                'priority',
                'recurrence',
                'reminder' => [
                    'id',
                    'task_id',
                    'user_id',
                    'set_date',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $taskId = $response->json('id');

        $response = $this->getJson('api/quotes/tasks/'.$quote->getKey().'/'.$taskId)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'name',
                'content',
                'expiry_date',
                'priority',
                'recurrence',
                'reminder' => [
                    'id',
                    'task_id',
                    'user_id',
                    'set_date',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertSame($data['name'], $response->json('name'));
        $this->assertSame($data['content'], $response->json('content'));

        $expiryDateFromResponse = $response->json('expiry_date');
        $reminderSetDateFromResponse = $response->json('reminder.set_date');

        $this->assertSame(Carbon::parse($data['expiry_date'])->format('m/d/y H:i:s'), $expiryDateFromResponse);

        $this->assertSame(Carbon::parse($data['reminder']['set_date'], $user->timezone->utc)->format('m/d/y H:i:s'),
            $reminderSetDateFromResponse);
        $this->assertSame($data['reminder']['status']->value, $response->json('reminder.status'));

        $user->timezone()->associate(Timezone::query()->whereKeyNot($user->timezone->getKey())->first());
        $user->save();

        $responseWithNewTimezoneSet = $this->getJson('api/quotes/tasks/'.$quote->getKey().'/'.$response->json('id'))
            ->assertOk();

        $this->assertNotSame($expiryDateFromResponse, $responseWithNewTimezoneSet->json('expiry_date'));
        $this->assertNotSame($reminderSetDateFromResponse, $responseWithNewTimezoneSet->json('reminder.set_date'));
    }

    /**
     * Test an ability to update an existing task for a quote with reminder data.
     */
    public function testCanUpdateTaskForQuoteWithReminder(): void
    {
        $this->authenticateApi();

        /** @var User $user */
        $user = $this->app['auth']->user();

        $quote = factory(Quote::class)->create();
        /** @var \App\Domain\Task\Models\Task $task */
        $task = Task::factory()->create();
        $task->rescueQuotes()->attach($quote);

        $taskAttrs = Task::factory()->raw();

        $reminderAttrs = TaskReminder::factory()->raw();

        $data = [
            'sales_unit_id' => SalesUnit::query()->first()->getKey(),
            'activity_type' => TaskTypeEnum::Task,
            'name' => $taskAttrs['name'],
            'content' => $taskAttrs['content'],
            'expiry_date' => $taskAttrs['expiry_date'],
            'priority' => $taskAttrs['priority'],
            'reminder' => [
                'set_date' => $reminderAttrs['set_date']->format('Y-m-d H:i:s'),
                'status' => $reminderAttrs['status'],
            ],
        ];

        $this->patchJson('api/quotes/tasks/'.$quote->getKey().'/'.$task->getKey(), $data)
            ->assertOk();

        $response = $this->getJson('api/quotes/tasks/'.$quote->getKey().'/'.$task->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'name',
                'content',
                'expiry_date',
                'priority',
                'recurrence',
                'reminder' => [
                    'id',
                    'task_id',
                    'user_id',
                    'set_date',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertSame($data['name'], $response->json('name'));
        $this->assertSame($data['content'], $response->json('content'));

        $this->assertSame(Carbon::parse($data['expiry_date'])->format('m/d/y H:i:s'), $response->json('expiry_date'));

        $this->assertSame(Carbon::parse($data['reminder']['set_date'], $user->timezone->utc)->format('m/d/y H:i:s'),
            $response->json('reminder.set_date'));
        $this->assertSame($data['reminder']['status']->value, $response->json('reminder.status'));
    }

    /**
     * Test an ability to create a task for a quote with recurrence data.
     */
    public function testCanCreateTaskForQuoteWithRecurrence(): void
    {
        $this->authenticateApi();

        /** @var \App\Domain\User\Models\User $user */
        $user = $this->app['auth']->user();

        $this->assertTrue($user->timezone->exists);

        $quote = factory(Quote::class)->create();

        $task = Task::factory()->raw();

        $data = [
            'activity_type' => 'Task',
            'sales_unit_id' => SalesUnit::factory()->create()->getKey(),
            'name' => $task['name'],
            'content' => $task['content'],
            'expiry_date' => $task['expiry_date'],
            'priority' => $task['priority'],
            'recurrence' => [
                'type' => $this->faker->randomElement(RecurrenceTypeEnum::cases())->value,
                'occur_every' => $this->faker->numberBetween(1, 99),
                'occurrences_count' => $this->faker->numberBetween(-1, 9999),
                'start_date' => $this->faker->dateTimeBetween('+1day', '+3day')->format('Y-m-d H:i:s'),
                'end_date' => $this->faker->dateTimeBetween('+3day', '+7day')->format('Y-m-d H:i:s'),
                'day' => $this->faker->randomElement(DateDayEnum::cases())->value,
                'month' => $this->faker->randomElement(DateMonthEnum::cases())->value,
                'week' => $this->faker->randomElement(DateWeekEnum::cases())->value,
                'day_of_week' => $this->faker->randomElement([
                    DayOfWeekEnum::Sunday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value | DayOfWeekEnum::Wednesday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value | DayOfWeekEnum::Wednesday->value | DayOfWeekEnum::Thursday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value | DayOfWeekEnum::Wednesday->value | DayOfWeekEnum::Friday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value | DayOfWeekEnum::Wednesday->value | DayOfWeekEnum::Friday->value | DayOfWeekEnum::Saturday->value,
                ]),
            ],
        ];

        $response = $this->postJson('api/quotes/tasks/'.$quote->getKey(), $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'user_id',
                'user' => [
                    'id',
                    'email',
                    'first_name',
                    'middle_name',
                    'last_name',
                ],
                'name',
                'content',
                'expiry_date',
                'priority',
                'recurrence' => [
                    'id',
                    'user_id',
                    'task_id',
                    'type',
                    'day',
                    'week',
                    'day_of_week',
                    'month',
                    'occur_every',
                    'occurrences_count',
                    'created_at',
                    'updated_at',
                ],
                'reminder',
            ]);

        $taskId = $response->json('id');

        $response = $this->getJson('api/quotes/tasks/'.$quote->getKey().'/'.$taskId)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'name',
                'content',
                'expiry_date',
                'priority',
                'recurrence' => [
                    'id',
                    'user_id',
                    'task_id',
                    'type',
                    'day',
                    'week',
                    'day_of_week',
                    'month',
                    'occur_every',
                    'occurrences_count',
                    'created_at',
                    'updated_at',
                ],
                'reminder',
            ]);

        $this->assertSame($data['name'], $response->json('name'));
        $this->assertSame($data['content'], $response->json('content'));

        $this->assertSame(Carbon::parse($data['expiry_date'])->format('m/d/y H:i:s'), $response->json('expiry_date'));
        $this->assertSame($data['recurrence']['occur_every'], $response->json('recurrence.occur_every'));
        $this->assertSame($data['recurrence']['occurrences_count'], $response->json('recurrence.occurrences_count'));
        $this->assertSame(Carbon::parse($data['recurrence']['start_date'])->format('m/d/y H:i:s'),
            $response->json('recurrence.start_date'));
        $this->assertSame(Carbon::parse($data['recurrence']['end_date'])->format('m/d/y H:i:s'),
            $response->json('recurrence.end_date'));
        $this->assertSame($data['recurrence']['day'], $response->json('recurrence.day'));
        $this->assertSame($data['recurrence']['month'], $response->json('recurrence.month'));
        $this->assertSame($data['recurrence']['week'], $response->json('recurrence.week'));
        $this->assertSame($data['recurrence']['day_of_week'], $response->json('recurrence.day_of_week'));
    }

    /**
     * Test an ability to update an existing task for a quote with recurrence data.
     */
    public function testCanUpdateTaskForQuoteWithRecurrence(): void
    {
        $this->authenticateApi();

        /** @var User $user */
        $user = $this->app['auth']->user();

        $quote = factory(Quote::class)->create();
        /** @var \App\Domain\Task\Models\Task $task */
        $task = Task::factory()->create();
        $task->rescueQuotes()->attach($quote);

        $taskAttrs = Task::factory()->raw();

        $data = [
            'activity_type' => 'Task',
            'sales_unit_id' => SalesUnit::factory()->create()->getKey(),
            'name' => $taskAttrs['name'],
            'content' => $taskAttrs['content'],
            'expiry_date' => $taskAttrs['expiry_date'],
            'priority' => $taskAttrs['priority'],
            'recurrence' => [
                'type' => $this->faker->randomElement(RecurrenceTypeEnum::cases())->value,
                'occur_every' => $this->faker->numberBetween(1, 99),
                'occurrences_count' => $this->faker->numberBetween(-1, 9999),
                'start_date' => $this->faker->dateTimeBetween('+1day', '+3day')->format('Y-m-d H:i:s'),
                'end_date' => $this->faker->dateTimeBetween('+3day', '+7day')->format('Y-m-d H:i:s'),
                'day' => $this->faker->randomElement(DateDayEnum::cases())->value,
                'month' => $this->faker->randomElement(DateMonthEnum::cases())->value,
                'week' => $this->faker->randomElement(DateWeekEnum::cases())->value,
                'day_of_week' => $this->faker->randomElement([
                    DayOfWeekEnum::Sunday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value | DayOfWeekEnum::Wednesday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value | DayOfWeekEnum::Wednesday->value | DayOfWeekEnum::Thursday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value | DayOfWeekEnum::Wednesday->value | DayOfWeekEnum::Friday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value | DayOfWeekEnum::Wednesday->value | DayOfWeekEnum::Friday->value | DayOfWeekEnum::Saturday->value,
                ]),
            ],
        ];

        $this->patchJson('api/quotes/tasks/'.$quote->getKey().'/'.$task->getKey(), $data)
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/quotes/tasks/'.$quote->getKey().'/'.$task->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'name',
                'content',
                'expiry_date',
                'priority',
                'recurrence' => [
                    'id',
                    'user_id',
                    'task_id',
                    'type',
                    'day',
                    'week',
                    'day_of_week',
                    'month',
                    'occur_every',
                    'occurrences_count',
                    'created_at',
                    'updated_at',
                ],
                'reminder',
            ]);

        $this->assertSame($data['name'], $response->json('name'));
        $this->assertSame($data['content'], $response->json('content'));

        $this->assertSame(Carbon::parse($data['expiry_date'])->format('m/d/y H:i:s'), $response->json('expiry_date'));
        $this->assertSame($data['recurrence']['occur_every'], $response->json('recurrence.occur_every'));
        $this->assertSame($data['recurrence']['occurrences_count'], $response->json('recurrence.occurrences_count'));
        $this->assertSame(Carbon::parse($data['recurrence']['start_date'])->format('m/d/y H:i:s'),
            $response->json('recurrence.start_date'));
        $this->assertSame(Carbon::parse($data['recurrence']['end_date'])->format('m/d/y H:i:s'),
            $response->json('recurrence.end_date'));
        $this->assertSame($data['recurrence']['day'], $response->json('recurrence.day'));
        $this->assertSame($data['recurrence']['month'], $response->json('recurrence.month'));
        $this->assertSame($data['recurrence']['week'], $response->json('recurrence.week'));
        $this->assertSame($data['recurrence']['day_of_week'], $response->json('recurrence.day_of_week'));
    }

    /**
     * Test an ability to unset a recurrence from an existing task for a quote with reminder data.
     */
    public function testCanUnsetRecurrenceFromTaskForQuote(): void
    {
        $this->authenticateApi();

        $quote = factory(Quote::class)->create();
        /** @var \App\Domain\Task\Models\Task $task */
        $task = Task::factory()->create();
        $task->rescueQuotes()->attach($quote);

        $recurrence = factory(TaskRecurrence::class)->create([
            'task_id' => $task->getKey(),
        ]);

        $response = $this->getJson('api/quotes/tasks/'.$quote->getKey().'/'.$task->getKey())
//            ->dump()
            ->assertJsonStructure([
                'id',
                'recurrence' => [
                    'id',
                ],
            ]);

        $this->assertNotNull($response->json('recurrence'));

        $this->patchJson('api/quotes/tasks/'.$quote->getKey().'/'.$task->getKey(), [
            'activity_type' => 'Task',
            'sales_unit_id' => SalesUnit::factory()->create()->getKey(),
            'name' => $task->name,
            'content' => $task->content,
            'expiry_date' => $task->expiry_date->format('Y-m-d H:i:s'),
            'users' => $task->users->modelKeys(),
            'attachments' => $task->attachments->modelKeys(),
            'priority' => $task->priority->value,
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/quotes/tasks/'.$quote->getKey().'/'.$task->getKey())
//            ->dump()
            ->assertJsonStructure([
                'id',
                'recurrence',
            ]);

        $this->assertNull($response->json('reminder'));
    }

    /**
     *  Test an ability to create a new task for quote without relations to users.
     */
    public function testCanCreateTaskForQuoteWithoutRelationsToUsers(): void
    {
        $this->authenticateApi();

        $quote = \App\Domain\Rescue\Models\Quote::factory()->for($this->app['auth']->user())->create();
        $attributes = Task::factory()->raw();

        $this->postJson('api/quotes/tasks/'.$quote->getKey(), $attributes)
            ->assertCreated()
            ->assertJsonStructure(['id', 'name', 'content', 'user_id']);
    }

    /**
     * Test an ability to update an existing task.
     */
    public function testCanUpdateTaskOfQuote(): void
    {
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();
        Event::fake(TaskUpdated::class);

        Event::hasListeners(TaskEventSubscriber::class);
        /** @var \App\Domain\Task\Models\Task $task */
        $task = Task::factory()->create();
        $task->rescueQuotes()->attach($quote);

        $attributes = Task::factory()
            ->has(User::factory()->count(2), 'users')
            ->raw();

        $this->patchJson('api/quotes/tasks/'.$quote->getKey().'/'.$task->getKey(), $attributes)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'content', 'user_id'])
            ->assertJsonFragment(['content' => $attributes['content']]);

        Event::assertDispatched(TaskUpdated::class,
            static fn (TaskUpdated $event) => $event->task->getKey() === $task->getKey());
    }

    /**
     * Test an ability to remove relations to users from an existing task of a quote.
     */
    public function testCanRemoveRelationsToUsersFromTaskOfQuote(): void
    {
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();

        Notification::fake();
        /** @var \App\Domain\Task\Models\Task $task */
        $task = Task::factory()
            ->has(User::factory()->count(2), 'users')
            ->create();
        $task->rescueQuotes()->attach($quote);

        $usersCount = $task->users->count();

        $this->assertTrue($usersCount > 0);

        $attributes = Task::factory()->raw();

        $this->patchJson('api/quotes/tasks/'.$quote->getKey().'/'.$task->getKey(), $attributes)
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'content', 'user_id'])
            ->assertJsonFragment(['content' => $attributes['content']]);

        $this->assertCount(0, $task->refresh()->users);

        Notification::assertTimesSent($usersCount, RevokedNotification::class);
    }

    /**
     * Test an ability to delete an existing task of a quote.
     */
    public function testCanDeleteTaskOfQuote(): void
    {
        $this->authenticateApi();

        $quote = Quote::factory()->for($this->app['auth']->user())->create();
        Event::fake(TaskDeleted::class);

        Event::hasListeners(TaskEventSubscriber::class);
        /** @var \App\Domain\Task\Models\Task $task */
        $task = Task::factory()
            ->has(User::factory()->count(2), 'users')
            ->create();
        $task->rescueQuotes()->attach($quote);

        $this->deleteJson('api/quotes/tasks/'.$quote->getKey().'/'.$task->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($task);

        Event::assertDispatched(TaskDeleted::class,
            static fn (TaskDeleted $event) => $event->task->getKey() === $task->getKey());
    }
}
