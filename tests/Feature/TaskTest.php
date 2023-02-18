<?php

namespace Tests\Feature;

use App\Domain\Company\Models\Company;
use App\Domain\Reminder\Enum\ReminderStatus;
use App\Domain\Rescue\Models\Quote;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Models\TaskReminder;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * @group build
 */
class TaskTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->faker);
    }

    public function testCanListTasksOfTaskable(): void
    {
        /** @var Company $company */
        $company = Company::factory()->create();

        /** @var \App\Domain\Task\Models\Task[] $tasks */
        $tasks = Task::factory()
            ->count(2)
            ->create();

        $company->tasks()->sync($tasks);

        $this->assertCount(2, $tasks);
        $this->assertCount(1, $tasks[0]->companies);
        $this->assertCount(1, $tasks[1]->companies);

        $this->assertSame($tasks[0]->companies[0]->getKey(), $tasks[1]->companies[0]->getKey());

        $model = $tasks[0]->companies->first();

        Task::factory()->count(4)
            ->has(Company::factory(), 'companies')
            ->create();

        $this->authenticateApi();

        $response = $this->getJson('api/tasks/taskable/'.$model->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user',
                        'taskable_id',
                        'taskable_type',
                        'activity_type',
                        'name',
                        'content',
                        'expiry_date',
                        'priority',
                        'users' => [
                            '*' => [
                                'id',
                                'email',
                                'first_name',
                                'last_name',
                                'user_fullname',
                            ],
                        ],
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertOk();

        $this->assertCount(2, $response->json('data'));

        $taskModelKeys = $tasks->modelKeys();

        foreach ($response->json('data.*.id') as $taskId) {
            $this->assertContains($taskId, $taskModelKeys);
        }
    }

    /**
     * Test an ability to view an existing task.
     */
    public function testCanViewTask(): void
    {
        $this->authenticateApi();

        $task = Task::factory()
            ->has(Company::factory(), 'companies')
            ->has(Opportunity::factory(), 'opportunities')
            ->create();

        $response = $this->getJson('api/tasks/'.$task->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'user' => [
                        'id',
                        'email',
                        'first_name',
                        'middle_name',
                        'last_name',
                        'user_fullname',
                    ],
                    'name',
                    'content',
                    'priority',
                    'expiry_date',
                    'recurrence',
                    'reminder',
                    'linked_relations' => [
                        '*' => [
                            'task_id',
                            'model_type',
                            'model_id',
                        ],
                    ],
                    'users',
                    'attachments',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertContains((new Company())->getMorphClass(), $response->json('data.linked_relations.*.model_type'));
        $this->assertContains((new Opportunity())->getMorphClass(),
            $response->json('data.linked_relations.*.model_type'));
    }

    /**
     * Test an ability to create a new task for allowed taskable entity.
     */
    public function testCanCreateTaskForTaskable(): void
    {
        $this->authenticateApi();

        $taskable = Opportunity::factory()->create();

        $this->postJson('api/tasks', [
            'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
            'activity_type' => 'Task',
            'name' => $this->faker->text(100),
            'content' => [],
            'expiry_date' => $this->faker->dateTimeBetween('+1d', '+3day')->format('Y-m-d H:i:s'),
            'priority' => 1,
            'taskable' => [
                'id' => $taskable->getKey(),
                'type' => 'Opportunity',
            ],
            'reminder' => [
                'status' => 0,
                'set_date' => now()->addDay()->toDateTimeString(),
            ],
        ])
            ->assertCreated();

        $taskable = Company::factory()->create();

        $this->postJson('api/tasks', [
            'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
            'activity_type' => 'Task',
            'name' => $this->faker->text(100),
            'content' => [],
            'expiry_date' => $this->faker->dateTimeBetween('+1d', '+3day')->format('Y-m-d H:i:s'),
            'priority' => 1,
            'taskable' => [
                'id' => $taskable->getKey(),
                'type' => 'Company',
            ],
        ])
            ->assertCreated();

        $taskable = factory(Quote::class)->create();

        $this->postJson('api/tasks', [
            'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
            'activity_type' => 'Task',
            'name' => $this->faker->text(100),
            'content' => [],
            'expiry_date' => $this->faker->dateTimeBetween('+1d', '+3day')->format('Y-m-d H:i:s'),
            'priority' => 1,
            'taskable' => [
                'id' => $taskable->getKey(),
                'type' => 'Quote',
            ],
        ])
            ->assertCreated();

        $taskable = factory(WorldwideQuote::class)->create();

        $this->postJson('api/tasks', [
            'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
            'activity_type' => 'Task',
            'name' => $this->faker->text(100),
            'content' => [],
            'expiry_date' => $this->faker->dateTimeBetween('+1d', '+3day')->format('Y-m-d H:i:s'),
            'priority' => 1,
            'taskable' => [
                'id' => $taskable->getKey(),
                'type' => 'WorldwideQuote',
            ],
        ])
            ->assertCreated();
    }

    /**
     * Test an ability to update an existing task.
     */
    public function testCanUpdateTask(): void
    {
        $task = Task::factory()
            ->has(Company::factory(), 'companies')
            ->create();

        $this->authenticateApi();

        $this->patchJson('api/tasks/'.$task->getKey(), [
            'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
            'activity_type' => 'Task',
            'name' => $this->faker->text(100),
            'content' => [],
            'expiry_date' => $this->faker->dateTimeBetween('+1d', '+3day')->format('Y-m-d H:i:s'),
            'priority' => 2,
            'reminder' => [
                'status' => 0,
                'set_date' => now()->addDay()->toDateTimeString(),
            ],
        ])
//            ->dump()
            ->assertOk();
    }

    /**
     * Test an ability to delete an existing task.
     */
    public function testCanDeleteTask(): void
    {
        $task = Task::factory()
            ->has(Company::factory(), 'companies')
            ->create();

        $this->authenticateApi();

        $this->getJson('api/tasks/'.$task->getKey())
            ->assertOk();

        $this->deleteJson('api/tasks/'.$task->getKey())
            ->assertNoContent();

        $this->getJson('api/tasks/'.$task->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to set task reminder.
     */
    public function testCanSetTaskReminder(): void
    {
        $this->authenticateApi();

        $reminder = TaskReminder::factory()->create();

        $data = [
            'set_date' => now()->addMinute()->toDateTimeString(),
            'status' => ReminderStatus::Snoozed->value,
        ];

        $this->putJson("api/task-reminders/{$reminder->getKey()}", $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'task_id',
                    'user_id',
                    'set_date',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.status', $data['status'])
            ->assertJsonPath('data.set_date', Carbon::parse($data['set_date'])->format('m/d/y H:i:s'));
    }

    /**
     * Test an ability to dismiss task reminder.
     */
    public function testCanDismissTaskReminder(): void
    {
        $this->authenticateApi();

        $reminder = TaskReminder::factory()->for($this->app['auth']->user())->create([
            'status' => ReminderStatus::Scheduled,
        ]);

        $this->getJson("api/tasks/{$reminder->task->getKey()}")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'reminder' => [
                        'id',
                    ],
                ],
            ])
            ->assertJsonPath('data.reminder.id', $reminder->getKey());

        $data = [
            'status' => ReminderStatus::Dismissed->value,
        ];

        $this->putJson("api/task-reminders/{$reminder->getKey()}", $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'task_id',
                    'user_id',
                    'set_date',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.status', $data['status']);

        $this->getJson("api/tasks/{$reminder->task->getKey()}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'reminder',
                ],
            ])
            ->assertJsonPath('reminder', null);
    }

    /**
     * Test an ability to delete an existing reminder from the task.
     */
    public function testCanDeleteReminderFromTask(): void
    {
        $this->authenticateApi();

        $task = Task::factory()
            ->has(
                TaskReminder::factory()->for($this->app['auth']->user())->state(['status' => ReminderStatus::Scheduled]), relationship: 'reminder')
            ->create();

        $r = $this->getJson("api/tasks/{$task->getKey()}")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'reminder' => [
                        'id',
                    ],
                ],
            ]);

        $reminderId = $r->json('data.reminder.id');

        $this->assertNotNull($reminderId);

        $this->deleteJson("api/task-reminders/$reminderId")
            ->assertNoContent();

        $this->getJson("api/tasks/{$task->getKey()}")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'reminder',
                ],
            ])
            ->assertJsonPath('data.reminder', null);
    }
}
