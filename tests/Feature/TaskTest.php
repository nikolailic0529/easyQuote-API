<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\Task\Task;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    public function testCanListTasksOfTaskable(): void
    {
        /** @var Company $company */
        $company = Company::factory()->create();

        /** @var Task[] $tasks */
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
                ]
            ]);

        $this->assertContains((new Company())->getMorphClass(), $response->json('data.linked_relations.*.model_type'));
        $this->assertContains((new Opportunity())->getMorphClass(), $response->json('data.linked_relations.*.model_type'));
    }

    /**
     * Test an ability to create a new task for allowed taskable entity.
     */
    public function testCanCreateTaskForTaskable(): void
    {
        $this->authenticateApi();

        $taskable = Opportunity::factory()->create();

        $this->postJson('api/tasks', [
            'activity_type' => 'Task',
            'name' => $this->faker->text(100),
            'content' => [],
            'expiry_date' => $this->faker->dateTimeBetween('+1d', '+3day')->format('Y-m-d H:i:s'),
            'priority' => 1,
            'taskable' => [
                'id' => $taskable->getKey(),
                'type' => 'Opportunity',
            ],
        ])
            ->assertCreated();

        $taskable = Company::factory()->create();

        $this->postJson('api/tasks', [
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

        $this->patchJson("api/tasks/".$task->getKey(), [
            'activity_type' => 'Task',
            'name' => $this->faker->text(100),
            'content' => [],
            'expiry_date' => $this->faker->dateTimeBetween('+1d', '+3day')->format('Y-m-d H:i:s'),
            'priority' => 2,
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

}
